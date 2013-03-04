<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Entity;

class Video
{
	protected $providerId  = null;
	protected $identifier  = null; // Entity ID
	protected $from        = null; // Owner UID
	protected $name        = null;
	protected $description = null;
	protected $thumbnail   = null;
	protected $embed       = null;
	protected $link        = null; // perma link
	protected $time        = null; // created time
}
