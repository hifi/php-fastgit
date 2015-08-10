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

class GitCommit extends GitObject
{
    protected $tree = null;
    protected $parents = [];
    protected $author = null;
    protected $committer = null;
    protected $message = null;

    protected function init($body)
    {
        list ($headers, $message) = self::messageParser($body);

        $this->tree = $headers['tree'];
        $this->parents = array_key_exists('parent', $headers) ? $headers['parent'] : [];
        $this->author = $headers['author'];
        $this->committer = $headers['committer'];
    }

    public function getTree() { return $this->tree; }
    public function getParents() { return $this->parents; }
    public function getAuthor() { return $this->author; }
    public function getCommitter() { return $this->committer; }
    public function getMessage() { return $this->message; }
}
