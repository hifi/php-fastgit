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

class Object
{
    protected $hash;
    protected $type;
    protected $size;

    private function __construct($hash, $type, $size, $body)
    {
        $this->hash = $hash;
        $this->type = $type;
        $this->size = $size;

        $this->init($body);
    }

    protected function init($body) { }

    static function create($raw, $hash = false)
    {
        $hash = $hash ? $hash : sha1($raw);

        list ($prefix, $body) = explode("\0", $raw, 2);
        list ($type, $size) = explode(' ', $prefix, 2);

        switch ($type) {
            case 'commit':  return new Commit($hash, $type, $size, $body);
            case 'tag':     return new Tag($hash, $type, $size, $body);
            case 'tree':    return new Tree($hash, $type, $size, $body);
            case 'blob':    return new Blob($hash, $type, $size, $body);
            default:        return new Object($hash, $type, $size, $body);
        }
    }

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

    public function __toString()
    {
        return sprintf('%s %d %s', $this->type, $this->size, $this->hash);
    }

    public function getHash() { return $this->hash; }
    public function getType() { return $this->type; }
    public function getSize() { return $this->size; }
}
