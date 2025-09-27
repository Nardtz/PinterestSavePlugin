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
	add_action( 'wp_ajax_wppap_process_queue', 'wppap_ajax_process_queue' );
	add_action( 'wp_ajax_wppap_get_boards', 'wppap_ajax_get_boards' );
	
	// Schedule cron events
	add_action( 'wppap_process_queue', 'wppap_process_pending_pins' );
	add_action( 'wp', 'wppap_schedule_cron' );
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
			<h3><?php esc_html_e( 'Pinterest API Setup', 'wp-pinterest-auto-pin' ); ?></h3>
			<p><?php esc_html_e( 'To use this plugin, you need to set up Pinterest API credentials:', 'wp-pinterest-auto-pin' ); ?></p>
			<ol>
				<li><?php esc_html_e( 'Go to Pinterest Developer Portal: https://developers.pinterest.com/', 'wp-pinterest-auto-pin' ); ?></li>
				<li><?php esc_html_e( 'Create a new app and get your App ID, App Secret, and Access Token', 'wp-pinterest-auto-pin' ); ?></li>
				<li><?php esc_html_e( 'Get your Board ID from your Pinterest board URL (e.g., https://pinterest.com/username/board-name/ → board-name)', 'wp-pinterest-auto-pin' ); ?></li>
				<li><?php esc_html_e( 'Fill in the credentials below and test the connection', 'wp-pinterest-auto-pin' ); ?></li>
			</ol>
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
		
		<div class="wppap-connection-test">
			<h3><?php esc_html_e( 'Queue Processing', 'wp-pinterest-auto-pin' ); ?></h3>
			<button type="button" id="wppap-process-queue" class="button button-primary">
				<?php esc_html_e( 'Process Queue Now', 'wp-pinterest-auto-pin' ); ?>
			</button>
			<div id="wppap-queue-status"></div>
			<p class="description"><?php esc_html_e( 'Manually process pending pins that are due. The system also runs automatically every hour.', 'wp-pinterest-auto-pin' ); ?></p>
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
						<button type="button" id="wppap-get-boards" class="button button-secondary" style="margin-left: 10px;">
							<?php esc_html_e( 'Get My Boards', 'wp-pinterest-auto-pin' ); ?>
						</button>
						<div id="wppap-boards-list" style="margin-top: 10px;"></div>
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
	
	// Mark as processing
	$wpdb->update(
		$table_name,
		[ 'status' => 'processing' ],
		[ 'id' => $item_id ],
		[ '%s' ],
		[ '%d' ]
	);
	
	// Process the pin
	$result = wppap_pin_to_pinterest( $item );
	
	if ( $result['success'] ) {
		// Mark as completed
		$wpdb->update(
			$table_name,
			[ 'status' => 'completed' ],
			[ 'id' => $item_id ],
			[ '%s' ],
			[ '%d' ]
		);
		wp_send_json_success( [ 'message' => __( 'Pin completed successfully!', 'wp-pinterest-auto-pin' ) ] );
	} else {
		// Mark as failed
		$wpdb->update(
			$table_name,
			[ 
				'status' => 'failed',
				'error_message' => $result['error']
			],
			[ 'id' => $item_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
		wp_send_json_error( [ 'message' => __( 'Pin failed: ', 'wp-pinterest-auto-pin' ) . $result['error'] ] );
	}
}

function wppap_ajax_process_queue() {
	check_ajax_referer( 'wppap_nonce', 'nonce' );
	
	// Process pending pins
	wppap_process_pending_pins();
	
	// Get updated stats
	$stats = wppap_get_queue_stats();
	
	wp_send_json_success( [ 
		'message' => __( 'Queue processed successfully!', 'wp-pinterest-auto-pin' ),
		'stats' => $stats
	] );
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

// Cron scheduling functions
function wppap_schedule_cron() {
	if ( ! wp_next_scheduled( 'wppap_process_queue' ) ) {
		wp_schedule_event( time(), 'hourly', 'wppap_process_queue' );
	}
}

function wppap_process_pending_pins() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'wppap_pin_queue';
	$current_time = time();
	
	// Get pending pins that are due
	$pending_pins = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM $table_name WHERE status = 'pending' AND scheduled_time <= %d ORDER BY scheduled_time ASC LIMIT 5",
		$current_time
	) );
	
	if ( empty( $pending_pins ) ) {
		error_log( 'WPPAP: No pending pins to process' );
		return;
	}
	
	error_log( 'WPPAP: Processing ' . count( $pending_pins ) . ' pending pins' );
	
	foreach ( $pending_pins as $pin ) {
		// Mark as processing
		$wpdb->update(
			$table_name,
			[ 'status' => 'processing' ],
			[ 'id' => $pin->id ],
			[ '%s' ],
			[ '%d' ]
		);
		
		// Process the pin
		$result = wppap_pin_to_pinterest( $pin );
		
		if ( $result['success'] ) {
			// Mark as completed
			$wpdb->update(
				$table_name,
				[ 'status' => 'completed' ],
				[ 'id' => $pin->id ],
				[ '%s' ],
				[ '%d' ]
			);
			error_log( 'WPPAP: Successfully pinned image for post ' . $pin->post_id );
		} else {
			// Mark as failed
			$wpdb->update(
				$table_name,
				[ 
					'status' => 'failed',
					'error_message' => $result['error']
				],
				[ 'id' => $pin->id ],
				[ '%s', '%s' ],
				[ '%d' ]
			);
			error_log( 'WPPAP: Failed to pin image for post ' . $pin->post_id . ': ' . $result['error'] );
		}
	}
}

function wppap_pin_to_pinterest( $pin ) {
	$settings = wppap_get_settings();
	
	// Check if Pinterest API credentials are configured
	if ( empty( $settings['pinterest_access_token'] ) || empty( $settings['pinterest_board_id'] ) ) {
		return [
			'success' => false,
			'error' => 'Pinterest API credentials not configured. Please set your access token and board ID in settings.'
		];
	}
	
	// Pinterest API v5 endpoint for creating pins
	$api_url = 'https://api.pinterest.com/v5/pins';
	
	// Prepare the pin data
	$pin_data = [
		'board_id' => $settings['pinterest_board_id'],
		'media_source' => [
			'source_type' => 'image_url',
			'url' => $pin->image_url
		],
		'title' => $pin->post_title,
		'description' => $pin->description,
		'link' => $pin->post_url
	];
	
	// Make API request
	$response = wp_remote_post( $api_url, [
		'headers' => [
			'Authorization' => 'Bearer ' . $settings['pinterest_access_token'],
			'Content-Type' => 'application/json',
		],
		'body' => json_encode( $pin_data ),
		'timeout' => 30,
	] );
	
	// Check for errors
	if ( is_wp_error( $response ) ) {
		return [
			'success' => false,
			'error' => 'API request failed: ' . $response->get_error_message()
		];
	}
	
	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );
	$response_data = json_decode( $response_body, true );
	
	if ( $response_code === 201 ) {
		// Success - pin created
		return [
			'success' => true,
			'pin_id' => $response_data['id'] ?? 'unknown',
			'pin_url' => $response_data['url'] ?? ''
		];
	} else {
		// Error - log the response for debugging
		error_log( 'WPPAP Pinterest API Error: ' . $response_code . ' - ' . $response_body );
		
		$error_message = 'Pinterest API error';
		if ( isset( $response_data['message'] ) ) {
			$error_message = $response_data['message'];
		} elseif ( isset( $response_data['error'] ) ) {
			$error_message = $response_data['error'];
		}
		
		return [
			'success' => false,
			'error' => $error_message . ' (HTTP ' . $response_code . ')'
		];
	}
}

// Clean up cron on deactivation
function wppap_deactivate() {
	wp_clear_scheduled_hook( 'wppap_process_queue' );
}
register_deactivation_hook( __FILE__, 'wppap_deactivate' );

function wppap_ajax_get_boards() {
	check_ajax_referer( 'wppap_nonce', 'nonce' );
	
	$settings = wppap_get_settings();
	
	if ( empty( $settings['pinterest_access_token'] ) ) {
		wp_send_json_error( [ 'message' => __( 'Pinterest Access Token is required', 'wp-pinterest-auto-pin' ) ] );
	}
	
	// Get user's boards from Pinterest API
	$api_url = 'https://api.pinterest.com/v5/boards';
	
	$response = wp_remote_get( $api_url, [
		'headers' => [
			'Authorization' => 'Bearer ' . $settings['pinterest_access_token'],
		],
		'timeout' => 30,
	] );
	
	if ( is_wp_error( $response ) ) {
		wp_send_json_error( [ 'message' => 'API request failed: ' . $response->get_error_message() ] );
	}
	
	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );
	$response_data = json_decode( $response_body, true );
	
	if ( $response_code === 200 ) {
		$boards = $response_data['items'] ?? [];
		wp_send_json_success( [ 'boards' => $boards ] );
	} else {
		$error_message = 'Pinterest API error';
		if ( isset( $response_data['message'] ) ) {
			$error_message = $response_data['message'];
		} elseif ( isset( $response_data['error'] ) ) {
			$error_message = $response_data['error'];
		}
		wp_send_json_error( [ 'message' => $error_message . ' (HTTP ' . $response_code . ')' ] );
	}
}

// Create table on activation
register_activation_hook( __FILE__, 'wppap_create_queue_table' );
