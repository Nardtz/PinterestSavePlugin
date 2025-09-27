<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPPAP_Scheduler {
	public static function init() {
		add_action( 'wppap_process_pin_queue', [ __CLASS__, 'process_pin_queue' ] );
		add_action( 'wp_ajax_wppap_pin_now', [ __CLASS__, 'pin_now' ] );
	}

	public static function schedule_events() {
		if ( ! wp_next_scheduled( 'wppap_process_pin_queue' ) ) {
			wp_schedule_event( time(), 'wppap_pin_interval', 'wppap_process_pin_queue' );
		}
	}

	public static function clear_events() {
		wp_clear_scheduled_hook( 'wppap_process_pin_queue' );
	}

	public static function process_pin_queue() {
		$queue_manager = new WPPAP_Queue_Manager();
		$settings = WPPAP_Settings::get_settings();
		
		// Check if auto-pin is enabled
		if ( ! $settings['auto_pin_enabled'] ) {
			return;
		}

		// Get next item to pin
		$next_item = $queue_manager->get_next_pin_item();
		
		if ( ! $next_item ) {
			return;
		}

		// Pin the image
		$result = WPPAP_Pinterest_API::create_pin(
			$next_item->image_url,
			$next_item->post_title,
			$next_item->description,
			$settings['pinterest_board_id']
		);

		if ( is_wp_error( $result ) ) {
			// Mark as failed
			$queue_manager->update_queue_item_status( $next_item->id, 'failed', $result->get_error_message() );
		} else {
			// Mark as completed
			$queue_manager->update_queue_item_status( $next_item->id, 'completed', 'Pin created successfully' );
		}
	}

	public static function pin_now() {
		check_ajax_referer( 'wppap_nonce', 'nonce' );
		
		$item_id = absint( $_POST['item_id'] ?? 0 );
		
		if ( ! $item_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid item ID', 'wp-pinterest-auto-pin' ) ] );
		}

		$queue_manager = new WPPAP_Queue_Manager();
		$item = $queue_manager->get_queue_item( $item_id );
		
		if ( ! $item ) {
			wp_send_json_error( [ 'message' => __( 'Item not found', 'wp-pinterest-auto-pin' ) ] );
		}

		$settings = WPPAP_Settings::get_settings();
		
		// Pin the image
		$result = WPPAP_Pinterest_API::create_pin(
			$item->image_url,
			$item->post_title,
			$item->description,
			$settings['pinterest_board_id']
		);

		if ( is_wp_error( $result ) ) {
			$queue_manager->update_queue_item_status( $item_id, 'failed', $result->get_error_message() );
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		} else {
			$queue_manager->update_queue_item_status( $item_id, 'completed', 'Pin created successfully' );
			wp_send_json_success( [ 'message' => __( 'Pin created successfully!', 'wp-pinterest-auto-pin' ) ] );
		}
	}

	public static function schedule_next_pin( $interval_seconds = null ) {
		$settings = WPPAP_Settings::get_settings();
		$interval = $interval_seconds ?? $settings['default_pin_interval'];
		
		// Clear existing scheduled event
		wp_clear_scheduled_hook( 'wppap_process_pin_queue' );
		
		// Schedule next event
		wp_schedule_single_event( time() + $interval, 'wppap_process_pin_queue' );
	}

	public static function add_custom_intervals( $schedules ) {
		$settings = WPPAP_Settings::get_settings();
		$interval = $settings['default_pin_interval'];
		
		$schedules['wppap_pin_interval'] = [
			'interval' => $interval,
			'display' => sprintf( __( 'Every %d seconds', 'wp-pinterest-auto-pin' ), $interval ),
		];
		
		return $schedules;
	}
}

// Add custom cron intervals
add_filter( 'cron_schedules', [ 'WPPAP_Scheduler', 'add_custom_intervals' ] );
