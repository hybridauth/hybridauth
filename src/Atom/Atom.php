<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2020 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Atom;

/**
 * An atom, which represents a content entry on a provider.
 * Roughly follows the conventions of the Atom data format (https://tools.ietf.org/html/rfc4287).
 * When saved, code will do its best with what is has, typically reducing to what a provider can support.
 * It is not designed to be round-trip safe - retrieval and saving are optimized independently.
 * We are optimizing for user-experience, not trying to cram things in.
 */
class Atom
{
    /**
     * Identifier.
     * Null means auto-set for a new one, no existing one will be null.
     *
     * @var ?string
     */
    public $identifier;

    /**
     * Whether this is an incomplete object.
     * For use when reading, not writing.
     * False means getAtomFull must be called to get full content.
     *
     * @var bool
     */
    public $isIncomplete = true;

    /**
     * Author.
     *
     * @var ?\Hybridauth\Atom\Author
     */
    public $author;

    /**
     * List of category objects.
     *
     * @var array
     */
    public $categories = [];

    /**
     * Published date.
     *
     * @var \DateTime
     */
    public $published;

    /**
     * Modification date.
     *
     * @var ?\DateTime
     */
    public $updated;

    /**
     * Title.
     * In plain text.
     * Assumed less that 256 characters long (if we are choosing whether to make a single text result title or content).
     *
     * @var ?string
     */
    public $title;

    /**
     * Summary. Defined as a reduced version of the full content.
     * In HTML.
     *
     * @var ?string
     */
    public $summary;

    /**
     * Content.
     * In HTML.
     *
     * @var ?string
     */
    public $content;

    /**
     * List of Enclosure objects.
     * Should be in precedence priority.
     *
     * @var array
     */
    public $enclosures = [];

    /**
     * Permalink URL.
     *
     * @var ?string
     */
    public $url;

    /**
     * Standalone hashtags, in precedence order. No leading "#".
     * Likely to only work for saving, as detecting standalone vs in-sentence is usually not possible.
     *
     * @var array
     */
    public $hashTags = [];
}
