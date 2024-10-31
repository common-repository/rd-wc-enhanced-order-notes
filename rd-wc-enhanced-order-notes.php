<?php 
/*
Plugin Name: RD Order Note Templates for WooCommerce
Plugin URI:
Description: Create predefined templates for order notes that you can apply to orders.
Version: 1.1.0
Author: Robot Dwarf
Author URI: https://www.robotdwarf.com/
WC requires at least: 4.7.2
WC tested up to: 9.1.1
Requires PHP: 7.2
Requires at least: 5.0
License: GPLv2 or later
Text Domain: rdwceon
Domain Path: /languages
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2010-2024 Robot Dwarf.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'RDWCEON_VERSION', '1.1.0' );
define( 'RDWCEON_URL', plugin_dir_url( __FILE__ ) );
define( 'RDWCEON_PATH', plugin_dir_path( __FILE__ ) );
define( 'RDWCEON_PLUGIN_FILE', __FILE__ );
define( 'RDWCEON_API_URL', 'https://www.robotdwarf.com/wp-json/robotdwarf/v1/' );

require RDWCEON_PATH . '/include.php';

if ( method_exists( 'RDWCEON_Manager', 'load' ) ) {
	RDWCEON_Manager::load( __FILE__ );

	register_activation_hook( RDWCEON_PLUGIN_FILE, array( 'RDWCEON_Manager', 'activate' ) );
}
