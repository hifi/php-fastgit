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

class Git
{
    protected $path;
    protected $refs = [];
    protected $packs = [];

    public function __construct($path)
    {
        $this->path = $path;

        if (!is_dir($this->path))
            throw new \Exception('Given path is not a directory.');

        if (!file_exists($this->path . '/HEAD'))
            throw new \Exception('Given path is not a git repository.');

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

    public function get($name)
    {
        $hash = false;

        if (ctype_xdigit($name)) {
            if (strlen($name) < 4)
                throw new \Exception('Hash needs to be at least 4 characters long.');

            $hash = $name;
        } elseif (array_key_exists($name, $this->refs)) {
            $hash = $this->refs[$name];
        } else {
            throw new \Exception('Invalid ref: ' . $name);
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
            throw new \Exception($name);

        return Object::create($data, $hash);
    }

    public function getHead($name)
    {
        $head = $this->get('heads/' . $name);

        if (!($head instanceof Commit))
            throw new \Exception('Commit expected, got' . get_class($head));

        return $head;
    }

    public function getTree($name)
    {
        $tree = $this->get($name);

        if (!($tree instanceof Tree))
            throw new \Exception('Tree expected, got ' . get_class($tree));

        return $tree;
    }

    public function getBlob($name)
    {
        $blob = $this->get($name);

        if (!($blob instanceof Blob))
            throw new \Exception('Blob expected, got ' . get_class($blob));

        return $blob;
    }
}
