<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Exception;

/**
 * UnexpectedValueException
 *
 * Exception thrown if a value does not match with a set of values. Typically this happens when a function calls 
 * another function and expects the return value to be of a certain type or value not including arithmetic or 
 * buffer related errors.
 */
class UnexpectedValueException extends RuntimeException implements ExceptionInterface
{
}