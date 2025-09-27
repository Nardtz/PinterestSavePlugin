<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPPAP_Pinterest_API {
	private static $api_base_url = 'https://api.pinterest.com/v5';

	public static function init() {
		add_action( 'wp_ajax_wppap_test_connection', [ __CLASS__, 'test_connection' ] );
		add_action( 'wp_ajax_wppap_get_boards', [ __CLASS__, 'get_boards' ] );
	}

	public static function test_connection() {
		check_ajax_referer( 'wppap_nonce', 'nonce' );
		
		$settings = WPPAP_Settings::get_settings();
		$access_token = $settings['pinterest_access_token'];
		
		if ( empty( $access_token ) ) {
			wp_send_json_error( [ 'message' => __( 'Access token is required', 'wp-pinterest-auto-pin' ) ] );
		}

		$response = wp_remote_get( self::$api_base_url . '/user_account', [
			'headers' => [
				'Authorization' => 'Bearer ' . $access_token,
			],
		] );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( [ 'message' => $response->get_error_message() ] );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( wp_remote_retrieve_response_code( $response ) === 200 ) {
			wp_send_json_success( [ 'message' => __( 'Connection successful!', 'wp-pinterest-auto-pin' ), 'user' => $data ] );
		} else {
			wp_send_json_error( [ 'message' => $data['message'] ?? __( 'Connection failed', 'wp-pinterest-auto-pin' ) ] );
		}
	}

	public static function get_boards() {
		check_ajax_referer( 'wppap_nonce', 'nonce' );
		
		$settings = WPPAP_Settings::get_settings();
		$access_token = $settings['pinterest_access_token'];
		
		if ( empty( $access_token ) ) {
			wp_send_json_error( [ 'message' => __( 'Access token is required', 'wp-pinterest-auto-pin' ) ] );
		}

		$response = wp_remote_get( self::$api_base_url . '/boards', [
			'headers' => [
				'Authorization' => 'Bearer ' . $access_token,
			],
			'body' => [
				'page_size' => 25,
			],
		] );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( [ 'message' => $response->get_error_message() ] );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( wp_remote_retrieve_response_code( $response ) === 200 ) {
			wp_send_json_success( [ 'boards' => $data['items'] ?? [] ] );
		} else {
			wp_send_json_error( [ 'message' => $data['message'] ?? __( 'Failed to fetch boards', 'wp-pinterest-auto-pin' ) ] );
		}
	}

	public static function create_pin( $image_url, $title, $description, $board_id ) {
		$settings = WPPAP_Settings::get_settings();
		$access_token = $settings['pinterest_access_token'];
		
		if ( empty( $access_token ) ) {
			return new WP_Error( 'no_token', __( 'Pinterest access token is required', 'wp-pinterest-auto-pin' ) );
		}

		$pin_data = [
			'board_id' => $board_id,
			'media_source' => [
				'source_type' => 'image_url',
				'url' => $image_url,
			],
			'title' => $title,
			'description' => $description,
		];

		$response = wp_remote_post( self::$api_base_url . '/pins', [
			'headers' => [
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type' => 'application/json',
			],
			'body' => json_encode( $pin_data ),
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( wp_remote_retrieve_response_code( $response ) === 201 ) {
			return $data;
		} else {
			return new WP_Error( 'pin_failed', $data['message'] ?? __( 'Failed to create pin', 'wp-pinterest-auto-pin' ) );
		}
	}

	public static function validate_image( $image_url ) {
		$settings = WPPAP_Settings::get_settings();
		$min_width = $settings['pin_quality_min_width'];
		$min_height = $settings['pin_quality_min_height'];

		// Get image dimensions
		$image_info = wp_remote_get( $image_url, [ 'timeout' => 10 ] );
		
		if ( is_wp_error( $image_info ) ) {
			return false;
		}

		$image_data = wp_remote_retrieve_body( $image_info );
		$image_size = getimagesizefromstring( $image_data );

		if ( $image_size === false ) {
			return false;
		}

		$width = $image_size[0];
		$height = $image_size[1];

		// Check if image meets minimum size requirements
		return $width >= $min_width && $height >= $min_height;
	}

	public static function get_image_dimensions( $image_url ) {
		$image_info = wp_remote_get( $image_url, [ 'timeout' => 10 ] );
		
		if ( is_wp_error( $image_info ) ) {
			return false;
		}

		$image_data = wp_remote_retrieve_body( $image_info );
		$image_size = getimagesizefromstring( $image_data );

		if ( $image_size === false ) {
			return false;
		}

		return [
			'width' => $image_size[0],
			'height' => $image_size[1],
		];
	}
}
