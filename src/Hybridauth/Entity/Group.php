<?php
namespace Hybridauth\Entity;

class Group
{
	public $providerId = null;

	public $identifier  = null; // Entity ID
	public $owner       = null; // Owner UID
	public $name        = null;
	public $description = null;

	public $link        = null; // perma link
	public $time        = null; // created time
}
