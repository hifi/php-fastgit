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
 * A git tag object.
 */
class Tag extends Object
{
    /** @var string Referenced object hash. */
    protected $object;

    /** @var string Referenced object type. */
    protected $objectType;

    /** @var string Tagger name, email and timestamp. */
    protected $tagger;

    /** @var string Tag message. */
    protected $message;

    protected function init($body)
    {
        list($headers, $message) = self::messageParser($body);

        $this->object = $headers['object'][0];
        $this->objectType = $headers['type'][0];
        $this->tagger = $headers['tagger'][0];

        $this->message = $message;
    }

    public function getShortMessage()
    {
        return explode("\n", $this->message, 2)[0];
    }

    /**
     * Get referenced object hash.
     * 
     * @return string
     */
    public function getObject() { return $this->object; }

    /**
     * Get referenced object type.
     * 
     * @return string
     */
    public function getObjectType() { return $this->objectType; }

    /**
     * Get tagger name, email and timestamp.
     * 
     * @return string
     */
    public function getTagger() { return $this->tagger; }

    /**
     * Get tag message.
     * 
     * @return string
     */
    public function getMessage() { return $this->message; }
}
