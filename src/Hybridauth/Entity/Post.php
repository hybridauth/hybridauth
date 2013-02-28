<?php
namespace Hybridauth\Entity;

class Post
{
	public $providerId = null;

	public $identifier  = null; // Entity ID
	public $from        = null; // Owner UID
	public $to          = null;
	public $message     = null;

	public $link        = null; // perma link
	public $time        = null; // created time
}
