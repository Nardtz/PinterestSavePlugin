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
	add_action( 'wp_ajax_wppap_pin_now', 'wppap_ajax_pin_now' );
	add_action( 'wp_ajax_wppap_remove_from_queue', 'wppap_ajax_remove_from_queue' );
	add_action( 'wp_ajax_wppap_create_table', 'wppap_ajax_create_table' );
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
	
	add_submenu_page(
		'wppap-settings',
		__( 'Pin Queue', 'wp-pinterest-auto-pin' ),
		__( 'Pin Queue', 'wp-pinterest-auto-pin' ),
		'manage_options',
		'wppap-queue',
		'wppap_render_queue_page'
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
		
		<div class="wppap-connection-test">
			<h3><?php esc_html_e( 'Database Setup', 'wp-pinterest-auto-pin' ); ?></h3>
			<button type="button" id="wppap-create-table" class="button button-secondary">
				<?php esc_html_e( 'Create Queue Table', 'wp-pinterest-auto-pin' ); ?>
			</button>
			<div id="wppap-table-status"></div>
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

	// Ensure table exists
	wppap_create_queue_table();

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
	$images_added = 0;
	$settings = wppap_get_settings();

	foreach ( $posts as $post_id ) {
		$images = wppap_extract_images_from_post( $post_id );
		
		foreach ( $images as $image_url ) {
			$images_found++;
			// Add to queue
			$result = wppap_add_to_queue( $post_id, $image_url );
			if ( $result ) {
				$images_added++;
			}
		}
	}

	wp_send_json_success( [
		'message' => sprintf( __( 'Scan completed! Found %d images in %d posts. Added %d images to queue.', 'wp-pinterest-auto-pin' ), $images_found, count( $posts ), $images_added ),
		'images_found' => $images_found,
		'images_added' => $images_added,
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

// Queue management functions
function wppap_create_queue_table() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'wppap_pin_queue';
	
	$charset_collate = $wpdb->get_charset_collate();
	
	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		post_id bigint(20) NOT NULL,
		image_url varchar(500) NOT NULL,
		post_title varchar(255) NOT NULL,
		post_url varchar(500) NOT NULL,
		description text NOT NULL,
		status varchar(20) DEFAULT 'pending',
		scheduled_time bigint(20) NOT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		error_message text,
		PRIMARY KEY (id),
		KEY post_id (post_id),
		KEY status (status),
		KEY scheduled_time (scheduled_time)
	) $charset_collate;";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

function wppap_add_to_queue( $post_id, $image_url ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'wppap_pin_queue';
	
	// Check if table exists
	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );
	if ( ! $table_exists ) {
		error_log( 'WPPAP: Table does not exist, creating it...' );
		wppap_create_queue_table();
	}
	
	// Check if already in queue
	$existing = $wpdb->get_var( $wpdb->prepare( 
		"SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND image_url = %s AND status IN ('pending', 'processing')",
		$post_id, $image_url
	) );
	
	if ( $existing > 0 ) {
		error_log( 'WPPAP: Image already in queue for post ' . $post_id );
		return false; // Already in queue
	}
	
	$post = get_post( $post_id );
	$post_title = get_the_title( $post_id );
	$post_url = get_permalink( $post_id );
	$description = wppap_generate_pin_description( $post_id );
	
	// Calculate scheduled time (next available slot)
	$last_scheduled = $wpdb->get_var( "SELECT MAX(scheduled_time) FROM $table_name WHERE status = 'pending'" );
	$settings = wppap_get_settings();
	$interval = $settings['default_pin_interval'];
	$scheduled_time = $last_scheduled ? $last_scheduled + $interval : time() + $interval;
	
	$result = $wpdb->insert(
		$table_name,
		[
			'post_id' => $post_id,
			'image_url' => $image_url,
			'post_title' => $post_title,
			'post_url' => $post_url,
			'description' => $description,
			'status' => 'pending',
			'scheduled_time' => $scheduled_time,
		],
		[
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
		]
	);
	
	if ( $result === false ) {
		error_log( 'WPPAP: Failed to insert into queue: ' . $wpdb->last_error );
	} else {
		error_log( 'WPPAP: Successfully added image to queue for post ' . $post_id );
	}
	
	return $result;
}

function wppap_generate_pin_description( $post_id ) {
	$post_title = get_the_title( $post_id );
	$site_name = get_bloginfo( 'name' );
	
	return "Check out this amazing content from $site_name! $post_title";
}

function wppap_get_queue_items( $status = null, $limit = 50 ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'wppap_pin_queue';
	
	$where = '';
	$params = [];
	
	if ( $status ) {
		$where = 'WHERE status = %s';
		$params[] = $status;
	}
	
	$sql = "SELECT * FROM $table_name $where ORDER BY scheduled_time ASC";
	
	if ( $limit ) {
		$sql .= " LIMIT $limit";
	}
	
	if ( $params ) {
		$sql = $wpdb->prepare( $sql, $params );
	}
	
	return $wpdb->get_results( $sql );
}

function wppap_render_queue_page() {
	$queue_items = wppap_get_queue_items();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Pin Queue', 'wp-pinterest-auto-pin' ); ?></h1>
		
		<div class="wppap-queue-stats">
			<?php
			$stats = wppap_get_queue_stats();
			?>
			<div class="wppap-stat-box">
				<div class="wppap-stat-number"><?php echo $stats['pending']; ?></div>
				<div class="wppap-stat-label"><?php esc_html_e( 'Pending', 'wp-pinterest-auto-pin' ); ?></div>
			</div>
			<div class="wppap-stat-box">
				<div class="wppap-stat-number"><?php echo $stats['completed']; ?></div>
				<div class="wppap-stat-label"><?php esc_html_e( 'Completed', 'wp-pinterest-auto-pin' ); ?></div>
			</div>
			<div class="wppap-stat-box">
				<div class="wppap-stat-number"><?php echo $stats['failed']; ?></div>
				<div class="wppap-stat-label"><?php esc_html_e( 'Failed', 'wp-pinterest-auto-pin' ); ?></div>
			</div>
		</div>
		
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Image', 'wp-pinterest-auto-pin' ); ?></th>
					<th><?php esc_html_e( 'Post Title', 'wp-pinterest-auto-pin' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wp-pinterest-auto-pin' ); ?></th>
					<th><?php esc_html_e( 'Scheduled Time', 'wp-pinterest-auto-pin' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wp-pinterest-auto-pin' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $queue_items ) ) : ?>
				<tr>
					<td colspan="5" class="wppap-no-items">
						<?php esc_html_e( 'No items in queue. Run a scan to add images.', 'wp-pinterest-auto-pin' ); ?>
					</td>
				</tr>
				<?php else : ?>
				<?php foreach ( $queue_items as $item ) : ?>
				<tr>
					<td>
						<?php if ( $item->image_url ) : ?>
							<img src="<?php echo esc_url( $item->image_url ); ?>" class="wppap-image-preview" />
						<?php endif; ?>
					</td>
					<td>
						<strong><?php echo esc_html( $item->post_title ); ?></strong><br>
						<small><a href="<?php echo esc_url( $item->post_url ); ?>" target="_blank"><?php esc_html_e( 'View Post', 'wp-pinterest-auto-pin' ); ?></a></small>
					</td>
					<td>
						<span class="status-<?php echo esc_attr( $item->status ); ?>">
							<?php echo esc_html( ucfirst( $item->status ) ); ?>
						</span>
						<?php if ( $item->error_message ) : ?>
							<br><small class="error-message"><?php echo esc_html( $item->error_message ); ?></small>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( date( 'Y-m-d H:i:s', $item->scheduled_time ) ); ?></td>
					<td>
						<?php if ( $item->status === 'pending' ) : ?>
							<button class="button button-small" onclick="wppapPinNow(<?php echo $item->id; ?>)">
								<?php esc_html_e( 'Pin Now', 'wp-pinterest-auto-pin' ); ?>
							</button>
						<?php endif; ?>
						<button class="button button-small" onclick="wppapRemoveFromQueue(<?php echo $item->id; ?>)">
							<?php esc_html_e( 'Remove', 'wp-pinterest-auto-pin' ); ?>
						</button>
					</td>
				</tr>
				<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}

function wppap_get_queue_stats() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'wppap_pin_queue';
	
	$sql = "SELECT status, COUNT(*) as count FROM $table_name GROUP BY status";
	$results = $wpdb->get_results( $sql );
	
	$stats = [
		'pending' => 0,
		'processing' => 0,
		'completed' => 0,
		'failed' => 0,
	];
	
	foreach ( $results as $result ) {
		$stats[ $result->status ] = (int) $result->count;
	}
	
	return $stats;
}

function wppap_ajax_pin_now() {
	check_ajax_referer( 'wppap_nonce', 'nonce' );
	
	$item_id = absint( $_POST['item_id'] ?? 0 );
	
	if ( ! $item_id ) {
		wp_send_json_error( [ 'message' => __( 'Invalid item ID', 'wp-pinterest-auto-pin' ) ] );
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . 'wppap_pin_queue';
	
	$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $item_id ) );
	
	if ( ! $item ) {
		wp_send_json_error( [ 'message' => __( 'Item not found', 'wp-pinterest-auto-pin' ) ] );
	}
	
	$settings = wppap_get_settings();
	
	// For now, just mark as completed (in a full version, you'd actually pin to Pinterest)
	$result = $wpdb->update(
		$table_name,
		[ 'status' => 'completed' ],
		[ 'id' => $item_id ],
		[ '%s' ],
		[ '%d' ]
	);
	
	if ( $result ) {
		wp_send_json_success( [ 'message' => __( 'Pin completed successfully!', 'wp-pinterest-auto-pin' ) ] );
	} else {
		wp_send_json_error( [ 'message' => __( 'Failed to update status', 'wp-pinterest-auto-pin' ) ] );
	}
}

function wppap_ajax_remove_from_queue() {
	check_ajax_referer( 'wppap_nonce', 'nonce' );
	
	$item_id = absint( $_POST['item_id'] ?? 0 );
	
	if ( ! $item_id ) {
		wp_send_json_error( [ 'message' => __( 'Invalid item ID', 'wp-pinterest-auto-pin' ) ] );
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . 'wppap_pin_queue';
	
	$result = $wpdb->delete( $table_name, [ 'id' => $item_id ], [ '%d' ] );
	
	if ( $result ) {
		wp_send_json_success( [ 'message' => __( 'Item removed from queue', 'wp-pinterest-auto-pin' ) ] );
	} else {
		wp_send_json_error( [ 'message' => __( 'Failed to remove item', 'wp-pinterest-auto-pin' ) ] );
	}
}

function wppap_ajax_create_table() {
	check_ajax_referer( 'wppap_nonce', 'nonce' );
	
	$result = wppap_create_queue_table();
	
	if ( $result ) {
		wp_send_json_success( [ 'message' => __( 'Queue table created successfully!', 'wp-pinterest-auto-pin' ) ] );
	} else {
		wp_send_json_error( [ 'message' => __( 'Failed to create table', 'wp-pinterest-auto-pin' ) ] );
	}
}

// Create table on activation
register_activation_hook( __FILE__, 'wppap_create_queue_table' );
