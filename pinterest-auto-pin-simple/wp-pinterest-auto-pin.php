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

// Initialize plugin
add_action( 'init', 'wppap_init' );

function wppap_init() {
	// Load text domain
	load_plugin_textdomain( 'wp-pinterest-auto-pin', false, dirname( plugin_basename( WPPAP_PLUGIN_FILE ) ) . '/languages' );
	
	// Add admin menu
	add_action( 'admin_menu', 'wppap_add_admin_menu' );
	add_action( 'admin_enqueue_scripts', 'wppap_enqueue_admin_assets' );
	add_action( 'wp_ajax_wppap_scan_posts', 'wppap_ajax_scan_posts' );
	add_action( 'wp_ajax_wppap_test_connection', 'wppap_ajax_test_connection' );
}

function wppap_add_admin_menu() {
	add_menu_page(
		__( 'Pinterest Auto-Pin', 'wp-pinterest-auto-pin' ),
		__( 'Pinterest Auto-Pin', 'wp-pinterest-auto-pin' ),
		'manage_options',
		'wppap-settings',
		'wppap_render_settings_page',
		'dashicons-pinterest',
		30
	);
}

function wppap_enqueue_admin_assets( $hook ) {
	if ( strpos( $hook, 'wppap-' ) === false ) {
		return;
	}
	
	wp_enqueue_style( 'wppap-admin', WPPAP_PLUGIN_URL . 'assets/css/admin.css', [], WPPAP_PLUGIN_VERSION );
	wp_enqueue_script( 'wppap-admin', WPPAP_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], WPPAP_PLUGIN_VERSION, true );
	wp_localize_script( 'wppap-admin', 'WPPAP', [
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'wppap_nonce' ),
	] );
}

function wppap_render_settings_page() {
	$settings = wppap_get_settings();
	
	if ( isset( $_POST['submit'] ) ) {
		check_admin_referer( 'wppap_settings' );
		$settings = wppap_sanitize_settings( $_POST['wppap_settings'] );
		update_option( 'wppap_settings', $settings );
		echo '<div class="notice notice-success"><p>' . __( 'Settings saved!', 'wp-pinterest-auto-pin' ) . '</p></div>';
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Pinterest Auto-Pin Settings', 'wp-pinterest-auto-pin' ); ?></h1>
		
		<div class="wppap-connection-test">
			<h3><?php esc_html_e( 'Test Pinterest Connection', 'wp-pinterest-auto-pin' ); ?></h3>
			<button type="button" id="wppap-test-connection" class="button button-secondary">
				<?php esc_html_e( 'Test Connection', 'wp-pinterest-auto-pin' ); ?>
			</button>
			<div id="wppap-connection-status"></div>
		</div>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'wppap_settings' ); ?>
			
			<table class="form-table">
				<tr>
					<th scope="row"><label for="pinterest_app_id"><?php esc_html_e( 'Pinterest App ID', 'wp-pinterest-auto-pin' ); ?></label></th>
					<td>
						<input name="wppap_settings[pinterest_app_id]" id="pinterest_app_id" type="text" class="regular-text" value="<?php echo esc_attr( $settings['pinterest_app_id'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Your Pinterest App ID from Pinterest Developer Portal.', 'wp-pinterest-auto-pin' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pinterest_app_secret"><?php esc_html_e( 'Pinterest App Secret', 'wp-pinterest-auto-pin' ); ?></label></th>
					<td>
						<input name="wppap_settings[pinterest_app_secret]" id="pinterest_app_secret" type="password" class="regular-text" value="<?php echo esc_attr( $settings['pinterest_app_secret'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Your Pinterest App Secret from Pinterest Developer Portal.', 'wp-pinterest-auto-pin' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pinterest_access_token"><?php esc_html_e( 'Pinterest Access Token', 'wp-pinterest-auto-pin' ); ?></label></th>
					<td>
						<input name="wppap_settings[pinterest_access_token]" id="pinterest_access_token" type="password" class="regular-text" value="<?php echo esc_attr( $settings['pinterest_access_token'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Your Pinterest Access Token for API access.', 'wp-pinterest-auto-pin' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pinterest_board_id"><?php esc_html_e( 'Pinterest Board ID', 'wp-pinterest-auto-pin' ); ?></label></th>
					<td>
						<input name="wppap_settings[pinterest_board_id]" id="pinterest_board_id" type="text" class="regular-text" value="<?php echo esc_attr( $settings['pinterest_board_id'] ); ?>" />
						<p class="description"><?php esc_html_e( 'The ID of the Pinterest board where pins will be posted.', 'wp-pinterest-auto-pin' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="default_pin_interval"><?php esc_html_e( 'Pin Interval (seconds)', 'wp-pinterest-auto-pin' ); ?></label></th>
					<td>
						<input name="wppap_settings[default_pin_interval]" id="default_pin_interval" type="number" class="small-text" value="<?php echo esc_attr( $settings['default_pin_interval'] ); ?>" min="300" />
						<p class="description"><?php esc_html_e( 'How often to pin images (minimum 300 seconds = 5 minutes).', 'wp-pinterest-auto-pin' ); ?></p>
					</td>
				</tr>
			</table>
			
			<h2><?php esc_html_e( 'Scan Posts for Images', 'wp-pinterest-auto-pin' ); ?></h2>
			<div class="wppap-scan-container">
				<table class="form-table">
					<tr>
						<th scope="row"><label for="scan_start_date"><?php esc_html_e( 'Start Date', 'wp-pinterest-auto-pin' ); ?></label></th>
						<td>
							<input type="date" id="scan_start_date" name="start_date" required />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="scan_end_date"><?php esc_html_e( 'End Date', 'wp-pinterest-auto-pin' ); ?></label></th>
						<td>
							<input type="date" id="scan_end_date" name="end_date" required />
						</td>
					</tr>
				</table>
				<p class="submit">
					<button type="button" id="wppap-start-scan" class="button button-primary"><?php esc_html_e( 'Start Scan', 'wp-pinterest-auto-pin' ); ?></button>
				</p>
				<div id="wppap-scan-progress" style="display: none;">
					<h3><?php esc_html_e( 'Scan Progress', 'wp-pinterest-auto-pin' ); ?></h3>
					<div class="progress-bar">
						<div class="progress-fill"></div>
					</div>
					<p class="scan-status"></p>
				</div>
			</div>
			
			<?php submit_button( __( 'Save Settings', 'wp-pinterest-auto-pin' ) ); ?>
		</form>
	</div>
	<?php
}

function wppap_get_settings() {
	$defaults = [
		'pinterest_app_id' => '',
		'pinterest_app_secret' => '',
		'pinterest_access_token' => '',
		'pinterest_board_id' => '',
		'default_pin_interval' => 3600,
	];
	return wp_parse_args( get_option( 'wppap_settings', [] ), $defaults );
}

function wppap_sanitize_settings( $input ) {
	$output = [];
	$output['pinterest_app_id'] = sanitize_text_field( $input['pinterest_app_id'] ?? '' );
	$output['pinterest_app_secret'] = sanitize_text_field( $input['pinterest_app_secret'] ?? '' );
	$output['pinterest_access_token'] = sanitize_text_field( $input['pinterest_access_token'] ?? '' );
	$output['pinterest_board_id'] = sanitize_text_field( $input['pinterest_board_id'] ?? '' );
	$output['default_pin_interval'] = absint( $input['default_pin_interval'] ?? 3600 );
	return $output;
}

function wppap_ajax_test_connection() {
	check_ajax_referer( 'wppap_nonce', 'nonce' );
	
	$settings = wppap_get_settings();
	$access_token = $settings['pinterest_access_token'];
	
	if ( empty( $access_token ) ) {
		wp_send_json_error( [ 'message' => __( 'Access token is required', 'wp-pinterest-auto-pin' ) ] );
	}

	$response = wp_remote_get( 'https://api.pinterest.com/v5/user_account', [
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
		wp_send_json_success( [ 'message' => __( 'Connection successful!', 'wp-pinterest-auto-pin' ) ] );
	} else {
		wp_send_json_error( [ 'message' => $data['message'] ?? __( 'Connection failed', 'wp-pinterest-auto-pin' ) ] );
	}
}

function wppap_ajax_scan_posts() {
	check_ajax_referer( 'wppap_nonce', 'nonce' );
	
	$start_date = sanitize_text_field( $_POST['start_date'] ?? '' );
	$end_date = sanitize_text_field( $_POST['end_date'] ?? '' );
	
	if ( empty( $start_date ) || empty( $end_date ) ) {
		wp_send_json_error( [ 'message' => __( 'Date range is required', 'wp-pinterest-auto-pin' ) ] );
	}

	$start_timestamp = strtotime( $start_date );
	$end_timestamp = strtotime( $end_date . ' 23:59:59' );

	if ( $start_timestamp === false || $end_timestamp === false ) {
		wp_send_json_error( [ 'message' => __( 'Invalid date format', 'wp-pinterest-auto-pin' ) ] );
	}

	$posts = get_posts( [
		'post_type' => 'post',
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
	$settings = wppap_get_settings();

	foreach ( $posts as $post_id ) {
		$images = wppap_extract_images_from_post( $post_id );
		
		foreach ( $images as $image_url ) {
			// For now, just count images (in a full version, you'd add to queue)
			$images_found++;
		}
	}

	wp_send_json_success( [
		'message' => sprintf( __( 'Scan completed! Found %d images in %d posts.', 'wp-pinterest-auto-pin' ), $images_found, count( $posts ) ),
		'images_found' => $images_found,
		'posts_scanned' => count( $posts ),
	] );
}

function wppap_extract_images_from_post( $post_id ) {
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
		foreach ( $matches[1] as $image_url ) {
			// Convert relative URLs to absolute
			if ( strpos( $image_url, 'http' ) !== 0 ) {
				$image_url = home_url( $image_url );
			}
			$images[] = $image_url;
		}
	}

	// Also check for featured image
	$featured_image_id = get_post_thumbnail_id( $post_id );
	if ( $featured_image_id ) {
		$featured_image_url = wp_get_attachment_image_url( $featured_image_id, 'full' );
		if ( $featured_image_url ) {
			$images[] = $featured_image_url;
		}
	}

	// Remove duplicates
	return array_unique( $images );
}
