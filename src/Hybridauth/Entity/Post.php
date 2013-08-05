<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Entity;

class Post extends Entity
{
	protected $providerId = null;
	protected $from       = null; // Owner UID
	protected $to         = null;
	protected $message    = null;
	protected $link       = null; // perma link
	protected $time       = null; // created time
}
