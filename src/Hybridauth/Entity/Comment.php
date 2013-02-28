<?php
namespace Hybridauth\Entity;

class Comment
{
	public $providerId = null;

	public $identifier  = null; // Entity ID
	public $from        = null; // Owner UID
	public $message     = null;

	public $link        = null; // perma link
	public $time        = null; // created time
}
