<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSF_Plugin {
	public static function init() {
		// Load text domain
		load_plugin_textdomain( 'wp-pinterest-save-follow', false, dirname( plugin_basename( WPSF_PLUGIN_FILE ) ) . '/languages' );

		// Includes
		require_once WPSF_PLUGIN_DIR . 'includes/class-wpsf-settings.php';
		require_once WPSF_PLUGIN_DIR . 'includes/class-wpsf-frontend.php';

		// Init components
		WPSF_Settings::init();
		WPSF_Frontend::init();
	}
}


