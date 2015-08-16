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
    protected $type;

    /** @var string Tag name. */
    protected $tag;

    /** @var string Tagger name, email and timestamp. */
    protected $tagger;

    /** @var string Tag message. */
    protected $message;

    /**
     * Construct new immutable Tag object.
     * 
     * @param string $object Referenced object hash.
     * @param string $type Referenced object type.
     * @param string $tag Tag name.
     * @param string $tagger Tagger name, email and timestamp.
     * @param string $message Tag message.
     * @param int $size Size of the tag, optional.
     * @param string $hash Hash of the tag, optional.
     * @return Tag
     */
    protected function __construct($object, $type, $tag, $tagger, $message, $size = false, $hash = false)
    {
        $this->object   = $object;
        $this->type     = $type;
        $this->tag      = $tag;
        $this->tagger   = $tagger;
        $this->message  = $message;
        $this->size     = $size ? $size : strlen($this->toRaw());
        $this->hash     = $hash ? $hash : sha1((string)$this);
    }

    /**
     * Create new immutable Tag object.
     * 
     * @param string $object Referenced object hash.
     * @param string $type Referenced object type.
     * @param string $tag Tag name.
     * @param string $tagger Tagger name, email and timestamp.
     * @param string $message Tag message.
     * @return Tag
     */
    public static function create($object, $type, $tag, $tagger, $message)
    {
        return new self($object, $type, $tag, $tagger, $message);
    }

    public function __toString()
    {
        return 'tag ' . $this->size . "\0" . $this->toRaw();
    }

    /**
     * Convert raw object into immutable Tag.
     * 
     * @param string $body Raw body of the commit.
     * @param int $size Size of the body, optional.
     * @param string $hash SHA-1 hash of the full commit including header, optional.
     * @return Tag
     */
    public static function fromRaw($body, $size = false, $hash = false)
    {
        list($headers, $message) = Commit::messageParser($body);

        return new self(
            $headers['object'][0],
            $headers['type'][0],
            $headers['tag'][0],
            $headers['tagger'][0],
            $message,
            $size, 
            $hash
        );
    }

    /**
     * Convert tag into raw object body.
     * 
     * @return string
     */
    public function toRaw()
    {
        if ($this->body === null) {
            $lines = [
                'object '   . $this->object,
                'type '     . $this->type,
                'tag '      . $this->tag,
                'tagger '   . $this->tagger,
           ];

            $lines[] = '';
            $lines[] = $this->message;

            $this->body = implode("\n", $lines);
        }

        return $this->body;
    }

    /**
     * Get the first line of tag message.
     * 
     * @return string
     */
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
    public function getType() { return $this->type; }

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
