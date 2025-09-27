<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPPAP_Plugin {
	public static function init() {
		// Load text domain
		load_plugin_textdomain( 'wp-pinterest-auto-pin', false, dirname( plugin_basename( WPPAP_PLUGIN_FILE ) ) . '/languages' );

		// Includes
		require_once WPPAP_PLUGIN_DIR . 'includes/class-wppap-settings.php';
		require_once WPPAP_PLUGIN_DIR . 'includes/class-wppap-pinterest-api.php';
		require_once WPPAP_PLUGIN_DIR . 'includes/class-wppap-image-scanner.php';
		require_once WPPAP_PLUGIN_DIR . 'includes/class-wppap-scheduler.php';
		require_once WPPAP_PLUGIN_DIR . 'includes/class-wppap-queue-manager.php';

		// Init components
		WPPAP_Settings::init();
		WPPAP_Pinterest_API::init();
		WPPAP_Image_Scanner::init();
		WPPAP_Scheduler::init();
		WPPAP_Queue_Manager::init();
	}

	public static function activate() {
		// Create database tables
		WPPAP_Queue_Manager::create_tables();
		
		// Schedule cron events
		WPPAP_Scheduler::schedule_events();
		
		// Set default options
		WPPAP_Settings::set_defaults();
	}

	public static function deactivate() {
		// Clear scheduled events
		WPPAP_Scheduler::clear_events();
	}
}
