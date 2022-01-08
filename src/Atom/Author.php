<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2020 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Atom;

/**
 * An enclosure (a file)
 */
class Author
{
    /**
     * Identifier.
     *
     * @var string
     */
    public $identifier;

    /**
     * Human-readable name.
     *
     * @var string
     */
    public $displayName;

    /**
     * URL.
     *
     * @var ?string
     */
    public $profileURL;

    /**
     * Photo image URL (to raw file).
     *
     * @var ?string
     */
    public $photoURL;
}
