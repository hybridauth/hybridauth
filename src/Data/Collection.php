<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Data;

/**
 * A very basic Data collection.
 */
final class Collection
{
	/**
	* Data collection
	*
	* @var mixed
	*/
	protected $collection = null;

	/**
	* @param mixed $data
	*/
	function __construct( $data = null )
	{
		$this->collection = new \stdClass();

		if( is_object( $data ) )
		{
			$this->collection = $data;
		}

		if( is_array( $data ) )
		{
			$this->collection = (object) $data;
		}
	}

	/**
	* Retrieves an item
	*
	* @param $property
	*
	* @return mixed
	*/
	function get( $property )
	{
		if( $this->exists( $property ) )
		{
			return $this->collection->$property;
		}
	}

	/**
	* Add or update an item
	*
	* @param $property
	* @param mixed $value
	*/
	function set( $property, $value )
	{
		if( $property )
		{
			$this->collection->$property = $value;
		}
	}

	/**
	* Returns the whole collection
	*
	* @return Collection
	*/
	function all()
	{
		return new Collection( $this->collection );
	}

	/**
	* .. until I come with a better name..
	*
	* @param $property
	*
	* @return Collection
	*/
	function filter( $property )
	{
		if( $this->exists( $property ) )
		{
			$data = $this->get( $property );

			if( ! is_a( $data, 'Collection' ) )
			{
				$data = new Collection( $data );
			}

			return $data;
		}

		return $this;
	}

	/**
	* Checks whether an item within the collection
	*
	* @param $property
	*
	* @return bool
	*/
	function exists( $property )
	{
		return property_exists( $this->collection, $property );
	}

	/**
	* Finds whether the collection is empty
	*
	* @return bool
	*/
	function isEmpty()
	{
		return ! (bool) $this->count();
	}

	/**
	* Count all items in collection
	*
	* @return int
	*/
	function count()
	{
		return count( $this->properties() );
	}

	/**
	* Returns all items properties names
	*
	* @return array
	*/
	function properties()
	{
		$properties = array();

		foreach( $this->collection as $property )
		{
			$properties[] = $property;
		}

		return $properties;
	}

	/**
	* Returns all items values
	*
	* @return array
	*/
	function values()
	{
		$values = array();

		foreach( $this->collection as $property )
		{
			$values[] = $this->get( $property );
		}

		return $values;
	}
}
