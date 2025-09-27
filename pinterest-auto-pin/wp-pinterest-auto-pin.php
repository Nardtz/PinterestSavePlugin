<?php
/**
 * Plugin Name:       Pinterest Auto-Pin
 * Description:       Automatically scans blog posts by date range and schedules images to be pinned to Pinterest boards with custom intervals.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-pinterest-auto-pin
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'WPPAP_PLUGIN_FILE', __FILE__ );
define( 'WPPAP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPPAP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPPAP_PLUGIN_VERSION', '1.0.0' );

// Autoload includes
require_once WPPAP_PLUGIN_DIR . 'includes/class-wppap-plugin.php';

// Initialize plugin
add_action( 'plugins_loaded', [ 'WPPAP_Plugin', 'init' ] );

// Activation hook
register_activation_hook( __FILE__, [ 'WPPAP_Plugin', 'activate' ] );

// Deactivation hook
register_deactivation_hook( __FILE__, [ 'WPPAP_Plugin', 'deactivate' ] );
