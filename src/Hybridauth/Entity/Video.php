<?php
namespace Hybridauth\Entity;

class Video
{
	public $providerId = null;

	public $identifier  = null; // Entity ID
	public $from        = null; // Owner UID
	public $name        = null;
	public $description = null;
	public $thumbnail   = null;
	public $embed       = null;

	public $link        = null; // perma link
	public $time        = null; // created time
}
