<?php 
/**
 * Example of an autoloader.
 *
 * This implementation is compliant with the PSR-4 recommendation.
 *
 * For more information about PHP Standard Recommendation (PSR) and the PHP Framework
 * Interoperability Group (FIG), refer to:
 *
 * http://www.php-fig.org/
 * http://www.php-fig.org/psr/psr-4/
 */
spl_autoload_register(
	function( $class )
	{
		$prefix = 'Hybridauth\\';         // < only kick in for Hybridauth namespace

		$base_dir = __DIR__ . '/../src/'; // < change this path if necessary

		$len = strlen( $prefix );

		if( strncmp( $prefix, $class, $len ) !== 0 )
		{
			return;
		}

		$relative_class = substr( $class, $len );

		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if( file_exists( $file ) )
		{
			require $file;
		}
	}
);
