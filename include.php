<?php
if (! defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}

foreach ( array_diff( scandir( RDWCEON_PATH . '/classes' ), array( '..', '.' ) ) as $rdwceon_filename ) {
	if ( substr( $rdwceon_filename, 0, 6 ) == 'class.' || substr( $rdwceon_filename, 0, 10 ) == 'interface.' ) {
		include RDWCEON_PATH . '/classes/' . $rdwceon_filename;
	}
}
