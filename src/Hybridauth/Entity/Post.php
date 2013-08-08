<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Entity;

class Post extends Entity
{
    protected $from         = null; // Owner UID
    protected $to           = null;
    protected $message      = null;
    protected $link         = null; // perma link
    protected $time         = null; // created time
    protected $inResponseTo = null; //IE, a reply

    function setFrom($from) {
        $this->from = $from;
    }

    function setTo($to) {
        $this->to = $to;
    }

    function setMessage($message) {
        $this->message = $message;
    }

    function setLink($link) {
        $this->link = $link;
    }

    function setTime($time) {
        $this->time = $time;
    }

    function setInResponseTo($inResponseTo) {
        $this->inResponseTo = $inResponseTo;
    }

    function getFrom($from) {
        return $this->from;
    }

    function getTo($to) {
        return $this->to;
    }

    function getMessage($message) {
        return $this->message;
    }

    function getLink($link) {
        return $this->link;
    }

    function getTime($time) {
        return $this->time;
    }

    function getInResponseTo($inResponseTo) {
        return $this->inResponseTo;
    }
}
