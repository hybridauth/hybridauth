<?php
/*!
* HybridAuth
* https://hybridauth.github.io | http://github.com/hybridauth/hybridauth
* (c) 2015 HybridAuth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Exception;

/**
 * BadMethodCallException
 *
 * Exception thrown if a callback refers to an undefined method or if some arguments are missing.
 */
class BadMethodCallException extends RuntimeException implements ExceptionInterface
{
}
