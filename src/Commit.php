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
 * A git commit object.
 */
class Commit extends Object
{
    /** @var string Referenced tree hash. */
    protected $tree = null;

    /** @var string[] Commit parent hashes. */
    protected $parents = [];

    /** @var string Author name, email and timestamp. */
    protected $author = null;

    /** @var string Committer name, email and timestamp */
    protected $committer = null;

    /** @var string Commit message. */
    protected $message = null;

    /**
     * Construct new immutable Commit object.
     * 
     * @param string $tree Referenced tree hash.
     * @param string[] $parents Commit parent hashes.
     * @param string $author Author name, email and timestamp.
     * @param string $committer Committer name, email and timestamp.
     * @param string $message Commit message.
     * @param int $size Size of the commit, optional.
     * @param string $hash Hash of the commit, optional.
     * @return Commit
     */
    protected function __construct($tree, $parents, $author, $committer, $message, $size = false, $hash = false)
    {
        $this->tree         = $tree;
        $this->parents      = $parents;
        $this->author       = $author;
        $this->committer    = $committer;
        $this->message      = $message;
        $this->size         = $size ? $size : strlen($this->toRaw());
        $this->hash         = $hash ? $hash : sha1((string)$this);
    }

    /**
     * Create new immutable Commit object.
     * 
     * @param string $tree Referenced tree hash.
     * @param string[] $parents Commit parent hashes.
     * @param string $author Author name, email and timestamp.
     * @param string $committer Committer name, email and timestamp.
     * @param string $message Commit message.
     * @return Commit
     */
    public static function create($tree, $parents, $author, $committer, $message)
    {
        return new self($tree, $parents, $author, $committer, $message);
    }

    public function __toString()
    {
        return 'commit ' . $this->size . "\0" . $this->toRaw();
    }

    /**
     * Convert raw object into immutable Commit.
     * 
     * @param string $body Raw body of the commit.
     * @param int $size Size of the body, optional.
     * @param string $hash SHA-1 hash of the full commit including header, optional.
     * @return Commit
     */
    public static function fromRaw($body, $size = false, $hash = false)
    {
        list ($headers, $message) = self::messageParser($body);

        return new self(
            $headers['tree'][0],
            array_key_exists('parent', $headers) ? $headers['parent'] : [],
            $headers['author'][0],
            $headers['committer'][0],
            $message,
            $size,
            $hash
        );
    }

    /**
     * Convert commit into raw object body.
     * 
     * @return string
     */
    public function toRaw()
    {
        if ($this->body === null) {
            $lines = ["tree " . $this->tree];

            foreach ($this->parents as $parent)
                $lines[] = "parent " . $parent;

            $lines[] = 'author ' . $this->author;

            if ($this->committer)
                $lines[] = 'committer ' . $this->committer;

            $lines[] = '';
            $lines[] = $this->message;

            $this->body = implode("\n", $lines);
        }

        return $this->body;
    }

    /**
     * Parses an object body text into header and message.
     * 
     * @param string $body Object body text.
     * @return array
     */
    static function messageParser($body)
    {
        list ($rawHeaders, $message) = explode("\n\n", $body, 2);

        $headers = [];
        foreach (explode("\n", $rawHeaders) as $header) {
            list ($type, $value) = explode(' ', $header, 2);
            $headers[$type][] = $value;
        }

        return [ $headers, $message ];
    }

    /**
     * Get the first line of commit message.
     * 
     * @return string
     */
    public function getShortMessage()
    {
        return explode("\n", $this->message, 2)[0];
    }

    /**
     * Get referenced tree hash.
     * 
     * @return string
     */
    public function getTree() { return $this->tree; }

    /**
     * Get commit parent hashes.
     * 
     * @return string[]
     */
    public function getParents() { return $this->parents; }

    /**
     * Get author name, email and timestamp.
     * 
     * @return string
     */
    public function getAuthor() { return $this->author; }

    /**
     * Get committer name, email and timestamp.
     * 
     * @return string
     */
    public function getCommitter() { return $this->committer; }

    /**
     * Get full commit message.
     * 
     * @return string
     */
    public function getMessage() { return $this->message; }
}
