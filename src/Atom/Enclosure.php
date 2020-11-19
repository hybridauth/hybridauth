<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2020 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Atom;

/**
 * An enclosure (a file).
 * It is advisable for web media to be only image/gif, image/png, image/jpeg, or video/mp4.
 */
class Enclosure
{
    const ENCLOSURE_IMAGE = 1;
    const ENCLOSURE_VIDEO = 2;
    const ENCLOSURE_AUDIO = 4;
    const ENCLOSURE_BINARY = 8;

    /**
     * An ENCLOSURE_* constant.
     *
     * @var int
     */
    public $type = 8;

    /**
     * Mime-type.
     *
     * @var ?string
     */
    public $mimeType;

    /**
     * Content length.
     *
     * @var ?int
     */
    public $contentLength;

    /**
     * URL. Ideally to the raw file, but if necessary to the web page that views it.
     * Should be an absolute URL, not protocol relative.
     * Should be accessible to your own web server and the world.
     * Can be dynamic.
     *
     * @var string
     */
    public $url;

    /**
     * Thumbnail URL, to a raw image file.
     * Should be an absolute URL, not protocol relative.
     * Should be accessible to your own web server and the world.
     * Can be dynamic.
     *
     * @var ?string
     */
    public $thumbnailUrl;

    /**
     * Guess a mime-type based on file extension.
     *
     * @param string $url URL
     *
     * @return ?string Mime type or null
     */
    public static function guessMimeType($url)
    {
        if (preg_match('#\.(jpg|jpe|jpeg)($|\?)#', $url) != 0) {
            return 'image/jpeg';
        }
        if (preg_match('#\.(png)($|\?)#', $url) != 0) {
            return 'image/png';
        }
        if (preg_match('#\.(gif)($|\?)#', $url) != 0) {
            return 'image/gif';
        }
        if (preg_match('#\.(svg)($|\?)#', $url) != 0) {
            return 'image/svg+xml';
        }
        if (preg_match('#\.(webp)($|\?)#', $url) != 0) {
            return 'image/webp';
        }

        if (preg_match('#\.(mp4|m4v)($|\?)#', $url) != 0) {
            return 'video/mp4';
        }
        if (preg_match('#\.(webm)($|\?)#', $url) != 0) {
            return 'image/webm';
        }

        if (preg_match('#\.(mp3)($|\?)#', $url) != 0) {
            return 'video/mpeg';
        }
        if (preg_match('#\.(aac)($|\?)#', $url) != 0) {
            return 'image/aac';
        }
        if (preg_match('#\.(weba)($|\?)#', $url) != 0) {
            return 'image/webm';
        }

        return null;
    }
}
