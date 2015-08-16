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
 * A git tree object.
 */
class Tree extends Object
{
    /** @var TreeLeaf[] Leaves of the tree. */
    protected $leaves = [];

    /**
     * Construct new immutable Tree object.
     * 
     * @param TreeLeaf[] $leaves Leaves of the tree.
     * @param int $size Size of the tree, optional.
     * @param string $hash Hash of the tree, optional.
     * @return Tree
     */
    protected function __construct($leaves, $size = false, $hash = false)
    {
        $this->leaves   = $leaves;
        $this->size     = $size ? $size : strlen($this->toRaw());
        $this->hash     = $hash ? $hash : sha1((string)$this);
    }

    /**
     * Create new immutable Tree object.
     * 
     * @param TreeLeaf[] $leaves Leaves of the tree.
     * @return Tree
     */
    public static function create($leaves)
    {
        return new self($leaves);
    }

    public function __toString()
    {
        return 'tree ' . $this->size . "\0" . $this->toRaw();
    }

    /**
     * Convert raw object into immutable Tree.
     * 
     * @param string $body Raw body of the tree.
     * @param int $size Size of the body, optional.
     * @param string $hash SHA-1 hash of the full commit including header, optional.
     * @return Tree
     */
    public static function fromRaw($body, $size = false, $hash = false)
    {
        $p = 0;
        $size = $size ? $size : strlen($body);

        while ($p < $size) {
            // leaf mode
            $mode_end = strpos($body, " ", $p);
            $smode = substr($body, $p, $mode_end - $p);
            $p = $mode_end + 1;

            // leaf name
            $name_end = strpos($body, "\0", $p);
            $name = substr($body, $p, $name_end - $p);
            $p = $name_end + 1;

            // leaf hash
            $lhash = bin2hex(substr($body, $p, 20));
            $p += 20;

            $dmode = octdec($smode);
            $type = $dmode >> 12;
            $mode = $dmode & 0xFFF;

            $leaves[] = new TreeLeaf($type, $mode, $lhash, $name);
        }

        return new self($leaves, $size, $hash);
    }

    /**
     * Convert tree into raw object body.
     * 
     * @return string
     */
    public function toRaw()
    {
        if ($this->body === null) {
            $this->body = '';
            foreach ($this->leaves as $leaf) {
                $this->body .= (string)$leaf;
            }
        }

        return $this->body;
    }


    /**
     * Get the array of leaves this tree has.
     * 
     * @return TreeLeaf[]
     */
    public function getLeaves() { return $this->leaves; }
}
