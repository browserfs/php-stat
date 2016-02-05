<?php

	define('FOO', 'bar');

	require __DIR__ . '/vendor/autoload.php';

	$paths = null;
	$filefind = null;

	function ignore_files( $phpstat_buffer, &$files_list ) {

		$lines = explode( "\n", $phpstat_buffer );

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( !$line || substr( $line, 0, 1 ) == '#' ) {
				continue;
			}

			if ( in_array( $line, $files_list ) ) {

				array_splice( $files_list, array_search( $line, $files_list ), 1 );

			}

		}

	}

	if ( isset( $argv[1] ) ) {
		
		if ( is_file( $argv[1] ) ) {

			$paths = [ $argv[1] ];

		} else {

			$path = realpath( $argv[1] );

			if ( !is_dir( $path ) ) {
				echo( 'PATH ' . $path . ' not found!' );
				die(1);
			}

			chdir( $path );

		}

	}

	if ( $paths === null ) {

		$rootDir = getcwd();	

		$filefind = function( $path ) use ( &$paths, &$filefind ) {

			$flist = @scandir( $path );

			if ( is_array( $flist ) ) {

				if ( in_array( '.php-stat', $flist ) && is_file( $path . '/.php-stat' ) ) {
					ignore_files( file_get_contents( $path . '/.php-stat' ), $flist );
				}

				foreach ( $flist as $name ) {

					if ( in_array( $name, [ '.', '..' ] ) ) {
						continue;
					}

					if ( is_dir( $scansub = $path . "/" . $name ) ) {

						$filefind( $scansub );

					} else {

						if ( is_file( $scansub ) ) {

							if ( preg_match( '/\\.php$/', $scansub ) ) {

								$paths[] = $scansub;

							}

						}

					}

				}

			}

		};

		$filefind( $rootDir );

	} else {

		$rootDir = '';

	}


	foreach ( $paths as $phpfile ) {

		try {

			$normalizedPath = trim( str_replace( '\\', '/', substr( $phpfile, strlen( $rootDir ) ) ), '/' );

			$parser = new \browserfs\phpstat\Parser( $normalizedPath );

			$items = $parser->toArray();

			foreach ( $items as $item ) {
				echo '# ', $normalizedPath , "\n", $item, "\n\n";
			}

			$parser = null;

		} catch( \Exception $e ) {

			echo "ERROR: " . $e->getMessage() . " IN " . $e->getFile() . "#" . $e->getLine() . "\n";

		}

	}