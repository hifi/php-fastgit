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

class Pack
{
    // streams
    protected $idx;
    protected $pack;

    // index data
    protected $fanout = [];
    protected $fanout_last;

    const OBJ_COMMIT    = 1;
    const OBJ_TREE      = 2;
    const OBJ_BLOB      = 3;
    const OBJ_TAG       = 4;
    const OBJ_OFS_DELTA = 6;
    const OBJ_REF_DELTA = 7;

    protected static $types = [ '!0!', 'commit', 'tree', 'blob', 'tag', '!5!', 'ofs_delta', 'ref_delta', '!8!' ];

    public function __construct($idxPath, $packPath)
    {
        $this->idx = fopen($idxPath, 'r');
        $this->pack = fopen($packPath, 'r');

        if ($this->idx === false || $this->pack === false)
            throw new \Exception('Failed to open index or pack for reading.');

        // validate index header
        if (fread($this->idx, 4) != "\377tOc")
            throw new \Exception('Index file magic is invalid.');

        if (fread($this->idx, 4) != "\0\0\0\2")
            throw new \Exception('Index file version is unsupported.');

        // populate fanout
        $blob = fread($this->idx, 1024);
        if ($blob === false || strlen($blob) < 1024)
            throw new \Exception('Failed to read fanout table.');

        $p = 0;
        for ($i = 0; $i < 1024; $i+=4) {
            $v = ord($blob[$i + 3]) | ord($blob[$i + 2]) << 8 | ord($blob[$i + 1]) << 16 | ord($blob[$i]) << 24;
            $this->fanout[$i >> 2] = [ $v - ($v - $p), $v - $p - 1 ];
            $p = $v;
        }
        $this->fanout_last = $p;

        // validate pack header
        if (fread($this->pack, 4) != 'PACK')
            throw new \Exception('Pack file magic is invalid.');

        if (fread($this->pack, 4) != "\0\0\0\2")
            throw new \Exception('Pack file version is unsupported.');
    }

    public function __destruct()
    {
        fclose($this->idx);
        fclose($this->pack);
    }

    static function inflateStream($fh, $num)
    {
        fseek($fh, ftell($fh)); // avoid bug in PHP stream buffering when applying a filter
        $filter = stream_filter_append($fh, 'zlib.inflate', STREAM_FILTER_READ, ['window' => 15]);
        $ret = fread($fh, $num);
        stream_filter_remove($filter);
        return $ret;
    }

    // very direct port from jgit
    public static function applyDelta($base, $delta)
    {
        list($baseHeader, $baseData) = explode("\0", $base, 2);
        list($baseType, $baseDataLen) = explode(' ', $baseHeader, 2);

        $resultPtr = 0;
        $deltaPtr = 0;

        $baseLen = 0;
        $shift = 0;
        do {
            $c = ord($delta[$deltaPtr++]);
            $baseLen |= ($c & 0x7f) << $shift;
            $shift += 7;
        } while ($c & 0x80);

        if ($baseLen != $baseDataLen)
            throw new \Exception('base length incorrect');

        $resLen = 0;
        $shift = 0;
        do {
            $c = ord($delta[$deltaPtr++]);
            $resLen |= ($c & 0x7f) << $shift;
            $shift += 7;
        } while ($c & 0x80);

        $result = str_repeat("\0", $resLen);
        $deltaLen = strlen($delta);

        while ($deltaPtr < $deltaLen) {
            $cmd = ord($delta[$deltaPtr++]);

            if ($cmd & 0x80) {
                $copyOffset = 0;
                if ($cmd & 0x01)
                    $copyOffset = ord($delta[$deltaPtr++]);
                if ($cmd & 0x02)
                    $copyOffset |= ord($delta[$deltaPtr++]) << 8;
                if ($cmd & 0x04)
                    $copyOffset |= ord($delta[$deltaPtr++]) << 16;
                if ($cmd & 0x08)
                    $copyOffset |= ord($delta[$deltaPtr++]) << 24;

                $copySize = 0;
                if ($cmd & 0x10)
                    $copySize = ord($delta[$deltaPtr++]);
                if ($cmd & 0x20)
                    $copySize |= ord($delta[$deltaPtr++]) << 8;
                if ($cmd & 0x40)
                    $copySize |= ord($delta[$deltaPtr++]) << 16;

                if ($copySize == 0)
                    $copySize = 0x10000;

                for ($i = 0; $i < $copySize; $i++)
                    $result[$resultPtr + $i] = $baseData[$copyOffset + $i];

                $resultPtr += $copySize;
            } elseif ($cmd != 0) {
                for ($i = 0; $i < $cmd; $i++)
                    $result[$resultPtr + $i] = $delta[$deltaPtr + $i];

                $deltaPtr += $cmd;
                $resultPtr += $cmd;
            } else {
                throw new \Exception('Zero delta command reserved.');
            }
        }

        return "$baseType $resLen\0" . $result;
    }

    public function search($hash)
    {
        $offset = $this->findPackOffset($hash);
        if (!$offset)
            return false;

        return $this->loadPackData($offset);
    }

    public function findPackOffset($hash)
    {
        $bhash = hex2bin($hash);
        $bhash_len = strlen($bhash);
        list($first, $count) = $this->fanout[ord($bhash[0])];

        if ($count == 0)
            return false;

        $idx_offset = 1032 + ($first * 20);

        // quick btree search from the index
        $lo = 0;
        $hi = $count;

        $index = false;
        while (1) {
            $mid = (int)(($hi - $lo) / 2) + $lo;
            fseek($this->idx, $idx_offset + ($mid * 20));
            $v = fread($this->idx, $bhash_len);
            if ($v < $bhash) {
                $lo = $mid + 1;
            } elseif ($v > $bhash) {
                $hi = $mid - 1;
            } else {
                $index = $first + $mid;
                break;
            }

            if ($lo > $hi)
                return false;
        }

        // find pack offset
        fseek($this->idx, 1032 + ($this->fanout_last * 20) + ($this->fanout_last * 4) + ($index * 4));
        $blob = fread($this->idx, 4);
        return ord($blob[3]) | ord($blob[2]) << 8 | ord($blob[1]) << 16 | ord($blob[0]) << 24;
    }

    public function loadPackData($pack_offset)
    {
        fseek($this->pack, $pack_offset);

        $c = ord(fread($this->pack, 1));

        $type = ($c & 127) >> 4;
        $size = $c & 0xF;

        $sh = 4;
        while ($c & 128) {
            $c = ord(fread($this->pack, 1));
            $size |= ($c & 127) << $sh;
            $sh += 7;
        }

        // handle delta
        if ($type == self::OBJ_OFS_DELTA) {
            $c = ord(fread($this->pack, 1));
            $off = $c & 127;
            while ($c & 128) {
                $off += 1;
                $c = ord(fread($this->pack, 1));
                $off = ($off << 7) + ($c & 127);
            }

            $delta = self::inflateStream($this->pack, $size);
            $base = $this->loadPackData($pack_offset - $off);
            return self::applyDelta($base, $delta);
        }

        return self::$types[$type] . ' ' . "$size\0" . self::inflateStream($this->pack, $size);
    }
}
