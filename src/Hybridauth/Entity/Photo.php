<?php
namespace Hybridauth\Entity;

class Photo
{
	public $providerId = null;

	public $identifier  = null; // Entity ID
	public $from        = null; // Owner UID
	public $name        = null;
	public $description = null;

	public $thumbnail   = null; // photo thumbnail link
	public $height      = null;
	public $width       = null;

	public $link        = null; // perma link
	public $time        = null; // created time
}
