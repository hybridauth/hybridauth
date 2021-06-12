<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2020 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Atom;

/**
 * A filter.
 */
class Filter
{
    /**
     * Maximum number of results to return.
     *
     * @var integer
     */
    public $limit = 12;

    /**
     * Whether to dip into pagination to get as close to $limit as possible.
     * This only has an effect if filtering isn't happening API-side,
     * or if $limit is too large for what the API supports in a single request.
     *
     * @var bool
     */
    public $deepProbe = true;

    /**
     * The ID of a category.
     *
     * @var string
     */
    public $categoryFilter = null;

    /**
     * A bitmask of Enclosure::ENCLOSURE_* constants.
     * Returned atoms must have at least one enclosure type referenced in the bitmask.
     *
     * @var int
     */
    public $enclosureTypeFilter = null;

    /**
     * Include content 3rd-parties have contributed, if applicable.
     *
     * @var boolean
     */
    public $includeContributedContent = false;

    /**
     * Include private content, if applicable.
     *
     * @var boolean
     */
    public $includePrivate = false;

    /**
     * Whether the filter's enclosure passes.
     *
     * @param array $enclosures
     *
     * @return boolean
     */
    public function passesEnclosureTest($enclosures)
    {
        if (($this->enclosureTypeFilter === null) || ($this->enclosureTypeFilter == 0)) {
            return true;
        }
        foreach ($enclosures as $enclosure) {
            if (($this->enclosureTypeFilter & $enclosure->type) != 0) {
                return true;
            }
        }
        return false;
    }
}
