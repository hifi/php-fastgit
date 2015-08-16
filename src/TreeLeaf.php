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
 * A single leaf of a git tree object.
 */
class TreeLeaf
{
    /** @var int Type of the leaf. */
    protected $type;

    /** @var int UNIX file permissions. */
    protected $mode;

    /** @var string Hash of the object. */
    protected $hash;

    /** @var string Name of the leaf. */
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

    /**
     * Construct a tree leaf.
     * 
     * @param int $type Type of the leaf.
     * @param int $mode UNIX file permissions.
     * @param string $hash Hash of the blob object.
     * @param string $name Name of the leaf.
     */
    public function __construct($type, $mode, $hash, $name)
    {
        $this->type = $type;
        $this->mode = $mode;
        $this->hash = $hash;
        $this->name = $name;
    }

    public function __toString()
    {
        return sprintf("%o%04o %s\0%s", $this->type, $this->mode, $this->name, hex2bin($this->hash));
    }

    /**
     * Get type of the leaf.
     * 
     * @return int
     */
    public function getType() { return $this->type; }

    /**
     * Get UNIX file permissions of the leaf.
     * 
     * @return int
     */
    public function getMode() { return $this->mode; }

    /**
     * Get hash of the referenced object.
     * 
     * @return string
     */
    public function getHash() { return $this->hash; }

    /**
     * Get name of the leaf.
     * 
     * @return string
     */
    public function getName() { return $this->name; }
}
