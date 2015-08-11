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

    protected function init($body)
    {
        list ($headers, $message) = self::messageParser($body);

        $this->tree = $headers['tree'][0];
        $this->parents = array_key_exists('parent', $headers) ? $headers['parent'] : [];
        $this->author = $headers['author'][0];
        $this->committer = $headers['committer'][0];
        $this->message = $message;
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
