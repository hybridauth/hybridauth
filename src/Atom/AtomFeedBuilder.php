<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2020 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Atom;

/**
 * Build an Atom feed from an array of atoms.
 */
class AtomFeedBuilder
{
    /**
     * Build an Atom feed.
     *
     * @param string $title Title for feed
     * @param string $url URL to the web version of the feed
     * @param string $feedId ID for the feed
     * @param string $urnStub Stub to put before each entry's permalink
     * @param array $atoms List of atoms
     * @param bool $trulyValid Try extra hard to be valid, even if it makes things clunky
     *
     * @return string The feed
     */
    public function buildAtomFeed($title, $url, $feedId, $urnStub, $atoms, $trulyValid)
    {
        $xml = '';

        $updatedDate = isset($atoms[0]) ? $atoms[0]->published : new \DateTime();

        $xml .= '
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>' . htmlentities($title, ENT_XML1) . '</title>
    <updated>' . $updatedDate->format(\DateTime::ATOM) . '</updated>
    <id>' . htmlentities($feedId, ENT_XML1) . '</id>
    <link rel="alternate" href="' . htmlentities($url, ENT_XML1) . '" />
        ';

        foreach ($atoms as $atom) {
            $xml .= '
    <entry>
            ';

            $title = $atom->title;
            if (empty($title) && ($trulyValid)) {
                if ($atom->summary !== null) {
                    $title = AtomHelper::htmlToPlainText($atom->summary);
                } elseif ($atom->content !== null) {
                    $title = AtomHelper::htmlToPlainText($atom->content);
                }
                if ($title === null) {
                    $title = '';
                }
                AtomHelper::limitLengthTo($title, 50);
            } else {
                if ($title === null) {
                    $title = '';
                }
            }
            $xml .= '
        <title>' . htmlentities($title, ENT_XML1) . '</title>
            ';

            if ($atom->url !== null) {
                $xml .= '
        <link rel="alternate" href="' . htmlentities($atom->url, ENT_XML1) . '" />
                ';
            }

            if ($atom->identifier !== null) {
                $xml .= '
        <id>' . htmlentities($urnStub . $atom->identifier, ENT_XML1) . '</id>
                ';
            }

            $xml .= '
        <published>' . htmlentities($atom->published->format(\DateTime::ATOM), ENT_XML1) . '</published>
            ';

            $dateOb = ($atom->updated === null) ? $atom->published : $atom->updated;
            $xml .= '
        <updated>' . htmlentities($dateOb->format(\DateTime::ATOM), ENT_XML1) . '</updated>
            ';

            if ($atom->summary !== null) {
                $xml .= '
        <summary type="html">' . htmlentities($atom->summary, ENT_XML1) . '</summary>
                ';
            }

            if ($atom->content !== null) {
                $xml .= '
        <content type="html">' . htmlentities($atom->content, ENT_XML1) . '</content>
                ';
            }

            if (($atom->summary === null) && ($atom->content === null)) {
                // Mandated that we provide some kind of text, so we'll repurpose the title
                $xml .= '
        <summary type="html">' . htmlentities($atom->title, ENT_XML1) . '</summary>
                ';
            }

            if ($atom->author !== null) {
                $xml .= '
        <author><name>' . htmlentities($atom->author->displayName, ENT_XML1) . '</name></author>
                ';
            }

            foreach ($atom->categories as $category) {
                $term = $category->identifier;
                $label = $category->label;
                $xml .= '
        <category term="' . htmlentities($term, ENT_XML1) . '" label="' . htmlentities($label, ENT_XML1) . '" />
                ';
            }

            foreach ($atom->enclosures as $enclosure) {
                $lengthAttribute = '';
                $hrefAttribute = '';
                $typeAttribute = '';
                if ($enclosure->contentLength !== null) {
                    $lengthAttribute = ' length="' . strval($enclosure->contentLength) . '"';
                }
                if ($enclosure->url !== null) {
                    $hrefAttribute = ' href="' . htmlentities($enclosure->url, ENT_XML1) . '"';
                }
                $mimeType = $enclosure->mimeType;
                if ($mimeType === null) {
                    $mimeType = Enclosure::guessMimeType($enclosure->url);
                }
                if ($mimeType !== null) {
                    $typeAttribute = ' type="' . htmlentities($mimeType, ENT_XML1) . '"';
                }
                $xml .= '
        <link' . $lengthAttribute . $hrefAttribute . $typeAttribute . ' rel="enclosure" />
                ';
                if (($enclosure->thumbnailUrl !== null) && ($enclosure->type != Enclosure::ENCLOSURE_IMAGE)) {
                    $thumbHrefAttribute = ' href="' . htmlentities($enclosure->thumbnailUrl, ENT_XML1) . '"';
                    $thumbMimeType = Enclosure::guessMimeType($enclosure->thumbnailUrl);
                    if ($thumbMimeType != null) {
                        $thumbTypeAttribute = ' type="' . htmlentities($thumbMimeType, ENT_XML1) . '"';
                    } else {
                        $thumbTypeAttribute = '';
                    }
                    $xml .= '
        <link' . $thumbHrefAttribute . $thumbTypeAttribute . ' rel="enclosure" />
                    ';
                }
            }

            $xml .= '
    </entry>
            ';
        }

        $xml .= '
</feed>
        ';

        return trim($xml);
    }
}
