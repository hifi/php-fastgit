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

class GitTreeLeaf
{
    protected $type;
    protected $mode;
    protected $hash;
    protected $name;

    const TYPE_DIR      = 4;
    const TYPE_FILE     = 8;
    const TYPE_SYMLINK  = 10;
    const TYPE_GITLINK  = 14;

    protected static $strTypes = [
        self::TYPE_DIR      => 'tree',
        self::TYPE_FILE     => 'blob',
        self::TYPE_SYMLINK  => 'link',
        self::TYPE_GITLINK  => 'gink',
    ];

    public function __construct($type, $mode, $hash, $name)
    {
        $this->type = $type;
        $this->mode = $mode;
        $this->hash = $hash;
        $this->name = $name;
    }

    public function __toString()
    {
        return sprintf('%02o%04o %s %s    %s', $this->type, $this->mode, self::$strTypes[$this->type], $this->hash, $this->name);
    }

    public function getType() { return $this->type; }
    public function getMode() { return $this->mode; }
    public function getHash() { return $this->hash; }
    public function getName() { return $this->name; }
}
