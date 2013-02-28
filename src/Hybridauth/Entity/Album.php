<?php
namespace Hybridauth\Entity;

class Album
{
	public $providerId = null;

	public $identifier  = null; // Entity ID
	public $from        = null; // Owner UID
	public $name        = null;
	public $description = null;
	public $thumbnail   = null; // cover photo link
	public $count       = null; // nb elements if any

	public $link        = null; // perma link
	public $time        = null; // created time
}
