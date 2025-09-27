<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSF_Frontend {
	public static function init() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_filter( 'the_content', [ __CLASS__, 'inject_image_wrappers' ], 20 );
		add_action( 'wp_footer', [ __CLASS__, 'render_follow_modal' ] );
		add_shortcode( 'pinterest_follow', [ __CLASS__, 'shortcode_follow' ] );
	}

	public static function enqueue_assets() {
		$settings = WPSF_Settings::get_settings();

		wp_register_style( 'wpsf-frontend', WPSF_PLUGIN_URL . 'assets/css/frontend.css', [], WPSF_PLUGIN_VERSION );
		wp_enqueue_style( 'wpsf-frontend' );

		wp_register_script( 'wpsf-frontend', WPSF_PLUGIN_URL . 'assets/js/frontend.js', [ 'jquery' ], WPSF_PLUGIN_VERSION, true );
		wp_localize_script( 'wpsf-frontend', 'WPSF', [
			'profileUrl' => esc_url( $settings['pinterest_profile_url'] ),
			'overlayLabel' => $settings['overlay_label'],
			'followPromptTitle' => $settings['follow_prompt_title'],
			'followPromptBody' => wp_kses_post( $settings['follow_prompt_body'] ),
			'followButtonLabel' => $settings['follow_button_label'],
			'autoOverlay' => (bool) $settings['auto_overlay'],
		] );
		wp_enqueue_script( 'wpsf-frontend' );
	}

	public static function inject_image_wrappers( $content ) {
		$settings = WPSF_Settings::get_settings();
		if ( ! $settings['auto_overlay'] ) {
			return $content;
		}

		// Add a wrapper span around images to position overlay button
		$pattern = '/<img([^>]+)\/>/i';
		$content = preg_replace_callback( $pattern, function( $matches ) {
			$img = $matches[0];
			// Skip if already wrapped
			if ( strpos( $img, 'class="wpsf-image"' ) !== false ) {
				return $img;
			}
			// Inject class for targeting
			if ( strpos( $img, 'class=' ) !== false ) {
				$img = preg_replace( '/class=("|")/i', 'class=$1wpsf-image ', $img, 1 );
			} else {
				$img = preg_replace( '/<img/i', '<img class="wpsf-image"', $img, 1 );
			}
			return '<span class="wpsf-image-wrap">' . $img . '</span>';
		}, $content );

		return $content;
	}

	public static function render_follow_modal() {
		// Empty container; content built by JS via localized strings
		echo '<div id="wpsf-follow-modal" class="wpsf-hidden" aria-hidden="true"></div>';
	}

	public static function shortcode_follow( $atts ) {
		$settings = WPSF_Settings::get_settings();
		$url = esc_url( $settings['pinterest_profile_url'] );
		$label = esc_html( $settings['follow_button_label'] );
		if ( empty( $url ) ) {
			return '';
		}
		return '<a class="wpsf-follow-button" href="' . $url . '" target="_blank" rel="noopener nofollow">' . $label . '</a>';
	}
}


