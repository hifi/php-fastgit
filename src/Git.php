<?php

/*
 * Copyright (c) 2015 Toni Spets <toni.spets@iki.fi>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

namespace FastGit;

/**
 * Git repository access.
 */
class Git
{
    /** @var string Path to git repository. */
    protected $path;

    /** @var string[] Lookup array for refs. */
    protected $refs = [];

    /** @var Pack[] Loaded packs. */
    protected $packs = [];

    /**
     * Open a git repository.
     * 
     * @param string $path Path to git repository.
     * @throws \InvalidArgumentException
     */
    public function __construct($path)
    {
        $this->path = $path;

        if (!is_dir($this->path))
            throw new \InvalidArgumentException('Given path is not a directory.');

        if (!file_exists($this->path . '/HEAD')) {
            if (file_exists($this->path . '/.git/HEAD')) {
                $this->path .= '/.git';
            } else {
                throw new \InvalidArgumentException('Given path is not a git repository.');
            }
        }

        // load packed refs
        if (file_exists($this->path . '/packed-refs')) {
            foreach (explode("\n", file_get_contents($this->path . '/packed-refs')) as $line) {
                if (strlen($line) == 0 || $line[0] == '^' || $line[0] == '#')
                    continue;

                list($hash, $name) = explode(' ', $line, 2);
                $this->refs[substr($name, 5)] = $hash;
            }
        }

        // loose refs
        foreach (glob($this->path . '/refs/heads/*') as $head) {
            $this->refs['heads/' . basename($head)] = trim(file_get_contents($head));
        }

        foreach (glob($this->path . '/refs/tags/*') as $tag) {
            $this->refs['tags/' . basename($tag)] = trim(file_get_contents($tag));
        }

        // load indexes
        foreach (glob($this->path . '/objects/pack/*.idx') as $idxPath) {
            $pi = pathinfo($idxPath);
            $packPath = sprintf('%s/%s.pack', $pi['dirname'], $pi['filename']);

            if (file_exists($packPath)) {
                $this->packs[] = new Pack($idxPath, $packPath);
            }
        }
    }

    /**
     * Get any object from the repository.
     * 
     * @param string $name Reference name or (partial) object hash
     * @return Object Immutable Object of given name.
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     */
    public function get($name)
    {
        $hash = false;

        if (ctype_xdigit($name)) {
            $hash_len = strlen($name);

            if ($hash_len < 4)
                throw new \InvalidArgumentException('Hash must be at least 4 characters long.');

            if ($hash_len % 2)
                throw new \InvalidArgumentException('Hash must be divisible by two.');

            $hash = $name;
        } elseif (array_key_exists($name, $this->refs)) {
            $hash = $this->refs[$name];
        } else {
            throw new \InvalidArgumentException('Invalid ref: ' . $name);
        }

        $data = false;

        $objectPath = $this->path . '/objects/' . substr($hash, 0, 2) . '/' . substr($hash, 2);
        if (strlen($hash) < 40) {
            $hits = glob($objectPath . '*');
            if (count($hits) > 0) {
                $data = gzuncompress(file_get_contents($hits[0]));
            }
        } elseif (file_exists($objectPath)) {
            $data = gzuncompress(file_get_contents($objectPath));
        }

        if (!$data) {
            foreach ($this->packs as &$pack) {
                $data = $pack->search($hash);
                if ($data)
                    break;
            }
        }

        if (!$data)
            throw new \OutOfBoundsException($name);

        $hash = strlen($hash) == 40 ? $hash : sha1($data);

        list ($prefix, $body) = explode("\0", $data, 2);
        list ($type, $size) = explode(' ', $prefix, 2);

        switch ($type) {
            case 'commit':  return Commit::fromRaw($body, $size, $hash);
            case 'tag':     return Tag::fromRaw($body, $size, $hash);
            case 'tree':    return Tree::fromRaw($body, $size, $hash);
            case 'blob':    return Blob::fromRaw($body, $size, $hash);
            default:        throw new InvalidArgumentException($type);
        }
    }

    /**
     * Get a head commit from repository.
     * 
     * @param string $name Head name.
     * @return Commit Immutable Commit of given name.
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     * @throws \UnexpectedValueException
     */
    public function getHead($name)
    {
        return $this->getCommit('heads/' . $name);
    }

    /**
     * Get a commit from repository.
     * 
     * @param string $name Commit hash (partial is ok) or any ref that points to a commit.
     * @return Commit Immutable Commit of given name.
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     * @throws \UnexpectedValueException
     */
    public function getCommit($name)
    {
        $head = $this->get($name);

        if (!($head instanceof Commit))
            throw new \UnexpectedValueException('Commit expected, got' . get_class($head));

        return $head;
    }

    /**
     * Get a tag from repository.
     * 
     * @param string $name Tag name.
     * @return Tag Immutable Tag of given name.
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     * @throws \UnexpectedValueException
     */
    public function getTag($name)
    {
        $tag = $this->get('tags/' . $name);

        if (!($tag instanceof Tag))
            throw new \UnexpectedValueException('Tag expected, got' . get_class($tag));

        return $tag;
    }

    /**
     * Get a tree from repository.
     * 
     * @param string $name Tree hash (partial is ok).
     * @return Tree Immutable Tree of given name.
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     * @throws \UnexpectedValueException
     */
    public function getTree($name)
    {
        $tree = $this->get($name);

        if (!($tree instanceof Tree))
            throw new \UnexpectedValueException('Tree expected, got ' . get_class($tree));

        return $tree;
    }

    /**
     * Get a blob from repository.
     * 
     * @param string $name Blob hash (partial is ok).
     * @return Blob Immutable Blob of given name.
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     * @throws \UnexpectedValueException
     */
    public function getBlob($name)
    {
        $blob = $this->get($name);

        if (!($blob instanceof Blob))
            throw new \UnexpectedValueException('Blob expected, got ' . get_class($blob));

        return $blob;
    }
}
