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
    protected $leafs = [];

    protected function init($body)
    {
        $p = 0;
        $body_len = strlen($body);

        while ($p < $body_len) {
            // leaf mode
            $mode_end = strpos($body, " ", $p);
            $smode = substr($body, $p, $mode_end - $p);
            $p = $mode_end + 1;

            // leaf name
            $name_end = strpos($body, "\0", $p);
            $name = substr($body, $p, $name_end - $p);
            $p = $name_end + 1;

            // leaf hash
            $hash = bin2hex(substr($body, $p, 20));
            $p += 20;

            $dmode = octdec($smode);
            $type = $dmode >> 12;
            $mode = $dmode & 0xFFF;

            $this->leafs[] = new TreeLeaf($type, $mode, $hash, $name);
        }
    }

    /**
     * Get the array of leaves this tree has.
     * 
     * @return TreeLeaf[]
     */
    public function getLeafs() { return $this->leafs; }
}
