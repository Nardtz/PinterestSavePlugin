<?php
/**
 * Plugin Name:       Pinterest Save & Follow
 * Description:       Adds a hover Save/Pin overlay to post images and prompts users to follow your Pinterest page after pinning.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-pinterest-save-follow
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'WPSF_PLUGIN_FILE', __FILE__ );
define( 'WPSF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPSF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSF_PLUGIN_VERSION', '1.0.0' );

// Autoload includes
require_once WPSF_PLUGIN_DIR . 'includes/class-wpsf-plugin.php';

// Initialize plugin
add_action( 'plugins_loaded', [ 'WPSF_Plugin', 'init' ] );


