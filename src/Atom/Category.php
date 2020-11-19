<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2020 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Atom;

/**
 * A category (might be a literal category, or some way of subdividing different kinds of streams a provider may have)
 */
class Category
{
    /**
     * Identifier.
     *
     * @var string
     */
    public $identifier;

    /**
     * Title.
     *
     * @var string
     */
    public $label;

    /**
     * Parent category.
     *
     * @var ?string
     */
    public $parent_identifier;

    /**
     * Date last modified.
     *
     * @var ?\DateTime
     */
    public $modified;
}
