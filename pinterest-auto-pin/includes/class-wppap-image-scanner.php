<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPPAP_Image_Scanner {
	public static function init() {
		add_action( 'wp_ajax_wppap_scan_posts', [ __CLASS__, 'scan_posts' ] );
	}

	public static function scan_posts() {
		check_ajax_referer( 'wppap_nonce', 'nonce' );
		
		$start_date = sanitize_text_field( $_POST['start_date'] ?? '' );
		$end_date = sanitize_text_field( $_POST['end_date'] ?? '' );
		$post_types = array_map( 'sanitize_text_field', $_POST['post_types'] ?? [] );
		
		if ( empty( $start_date ) || empty( $end_date ) || empty( $post_types ) ) {
			wp_send_json_error( [ 'message' => __( 'All fields are required', 'wp-pinterest-auto-pin' ) ] );
		}

		$start_timestamp = strtotime( $start_date );
		$end_timestamp = strtotime( $end_date . ' 23:59:59' );

		if ( $start_timestamp === false || $end_timestamp === false ) {
			wp_send_json_error( [ 'message' => __( 'Invalid date format', 'wp-pinterest-auto-pin' ) ] );
		}

		$posts = get_posts( [
			'post_type' => $post_types,
			'post_status' => 'publish',
			'date_query' => [
				[
					'after' => date( 'Y-m-d H:i:s', $start_timestamp ),
					'before' => date( 'Y-m-d H:i:s', $end_timestamp ),
					'inclusive' => true,
				],
			],
			'posts_per_page' => -1,
			'fields' => 'ids',
		] );

		$images_found = 0;
		$queue_manager = new WPPAP_Queue_Manager();
		$settings = WPPAP_Settings::get_settings();

		foreach ( $posts as $post_id ) {
			$images = self::extract_images_from_post( $post_id );
			
			foreach ( $images as $image_data ) {
				// Validate image quality
				if ( ! WPPAP_Pinterest_API::validate_image( $image_data['url'] ) ) {
					continue;
				}

				// Check if already in queue
				if ( $queue_manager->is_image_queued( $image_data['url'], $post_id ) ) {
					continue;
				}

				// Add to queue
				$queue_manager->add_to_queue( [
					'post_id' => $post_id,
					'image_url' => $image_data['url'],
					'image_alt' => $image_data['alt'],
					'post_title' => get_the_title( $post_id ),
					'post_url' => get_permalink( $post_id ),
					'description' => self::generate_pin_description( $post_id, $image_data['alt'] ),
				] );

				$images_found++;
			}
		}

		wp_send_json_success( [
			'message' => sprintf( __( 'Scan completed! Found %d images and added them to the pin queue.', 'wp-pinterest-auto-pin' ), $images_found ),
			'images_found' => $images_found,
		] );
	}

	public static function extract_images_from_post( $post_id ) {
		$images = [];
		$post = get_post( $post_id );
		
		if ( ! $post ) {
			return $images;
		}

		// Extract images from post content
		$content = $post->post_content;
		
		// Find img tags
		preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches );
		
		if ( ! empty( $matches[1] ) ) {
			foreach ( $matches[1] as $index => $image_url ) {
				// Get alt text
				preg_match( '/alt=["\']([^"\']*)["\']/', $matches[0][$index], $alt_matches );
				$alt_text = $alt_matches[1] ?? '';

				// Convert relative URLs to absolute
				$image_url = self::make_absolute_url( $image_url );
				
				$images[] = [
					'url' => $image_url,
					'alt' => $alt_text,
				];
			}
		}

		// Also check for featured image
		$featured_image_id = get_post_thumbnail_id( $post_id );
		if ( $featured_image_id ) {
			$featured_image_url = wp_get_attachment_image_url( $featured_image_id, 'full' );
			$featured_image_alt = get_post_meta( $featured_image_id, '_wp_attachment_image_alt', true );
			
			if ( $featured_image_url ) {
				$images[] = [
					'url' => $featured_image_url,
					'alt' => $featured_image_alt,
				];
			}
		}

		// Check for gallery images
		$gallery_images = get_post_meta( $post_id, '_gallery_images', true );
		if ( is_array( $gallery_images ) ) {
			foreach ( $gallery_images as $gallery_image_id ) {
				$gallery_image_url = wp_get_attachment_image_url( $gallery_image_id, 'full' );
				$gallery_image_alt = get_post_meta( $gallery_image_id, '_wp_attachment_image_alt', true );
				
				if ( $gallery_image_url ) {
					$images[] = [
						'url' => $gallery_image_url,
						'alt' => $gallery_image_alt,
					];
				}
			}
		}

		// Remove duplicates
		$unique_images = [];
		$seen_urls = [];
		
		foreach ( $images as $image ) {
			if ( ! in_array( $image['url'], $seen_urls ) ) {
				$unique_images[] = $image;
				$seen_urls[] = $image['url'];
			}
		}

		return $unique_images;
	}

	private static function make_absolute_url( $url ) {
		if ( strpos( $url, 'http' ) === 0 ) {
			return $url;
		}
		
		return home_url( $url );
	}

	private static function generate_pin_description( $post_id, $image_alt ) {
		$settings = WPPAP_Settings::get_settings();
		$template = $settings['pin_description_template'];
		
		$post = get_post( $post_id );
		$post_title = get_the_title( $post_id );
		$post_excerpt = get_the_excerpt( $post_id );
		$post_url = get_permalink( $post_id );
		$site_name = get_bloginfo( 'name' );
		
		$description = str_replace(
			[ '{site_name}', '{post_title}', '{post_excerpt}', '{post_url}', '{image_alt}' ],
			[ $site_name, $post_title, $post_excerpt, $post_url, $image_alt ],
			$template
		);
		
		// Limit description length (Pinterest has a 500 character limit)
		if ( strlen( $description ) > 500 ) {
			$description = substr( $description, 0, 497 ) . '...';
		}
		
		return $description;
	}
}
