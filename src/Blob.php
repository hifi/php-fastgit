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
 * A git blob object.
 */
class Blob extends Object
{
    /**
     * Construct new immutable Blob object.
     * 
     * @param string $body Raw body of the blob.
     * @param int $size Size of the blob, optional.
     * @param string $hash Hash of the blob, optional.
     * @return Blob
     */
    protected function __construct($body, $size = false, $hash = false)
    {
        $this->body = $body;
        $this->size = $size ? $size : strlen($this->toRaw());
        $this->hash = $hash ? $hash : sha1((string)$this);
    }

    /**
     * Create new immutable Blob object.
     * 
     * @param string $body Raw body of the blob.
     * @return Blob
     */
    public static function create($body)
    {
        return new self($body);
    }

    public function __toString()
    {
        return 'blob ' . $this->size . "\0" . $this->toRaw();
    }

    /**
     * Convert raw object into immutable Blob.
     * 
     * @param string $body Raw body of the blob.
     * @param int $size Size of the body, optional.
     * @param string $hash SHA-1 hash of the full object including header, optional.
     * @return Blob
     */
    public static function fromRaw($body, $size = false, $hash = false)
    {
        return new self($body, $size, $hash);
    }

    /**
     * Convert blob into raw object body.
     * 
     * @return string
     */
    public function toRaw()
    {
        return $this->body;
    }
}
