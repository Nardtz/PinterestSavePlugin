<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPPAP_Settings {
	const OPTION_KEY = 'wppap_settings';

	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
	}

	public static function get_settings() {
		$defaults = [
			'pinterest_app_id' => '',
			'pinterest_app_secret' => '',
			'pinterest_access_token' => '',
			'pinterest_board_id' => '',
			'default_pin_interval' => 3600, // 1 hour in seconds
			'pin_description_template' => 'Check out this amazing content from {site_name}! {post_title}',
			'auto_pin_enabled' => 0,
			'pin_quality_min_width' => 600,
			'pin_quality_min_height' => 600,
			'exclude_categories' => [],
			'include_only_categories' => [],
		];
		return wp_parse_args( get_option( self::OPTION_KEY, [] ), $defaults );
	}

	public static function register_settings() {
		register_setting( 'wppap_settings_group', self::OPTION_KEY, [ __CLASS__, 'sanitize' ] );
	}

	public static function sanitize( $input ) {
		$output = [];
		$output['pinterest_app_id'] = isset( $input['pinterest_app_id'] ) ? sanitize_text_field( $input['pinterest_app_id'] ) : '';
		$output['pinterest_app_secret'] = isset( $input['pinterest_app_secret'] ) ? sanitize_text_field( $input['pinterest_app_secret'] ) : '';
		$output['pinterest_access_token'] = isset( $input['pinterest_access_token'] ) ? sanitize_text_field( $input['pinterest_access_token'] ) : '';
		$output['pinterest_board_id'] = isset( $input['pinterest_board_id'] ) ? sanitize_text_field( $input['pinterest_board_id'] ) : '';
		$output['default_pin_interval'] = isset( $input['default_pin_interval'] ) ? absint( $input['default_pin_interval'] ) : 3600;
		$output['pin_description_template'] = isset( $input['pin_description_template'] ) ? sanitize_textarea_field( $input['pin_description_template'] ) : '';
		$output['auto_pin_enabled'] = isset( $input['auto_pin_enabled'] ) ? (int) (bool) $input['auto_pin_enabled'] : 0;
		$output['pin_quality_min_width'] = isset( $input['pin_quality_min_width'] ) ? absint( $input['pin_quality_min_width'] ) : 600;
		$output['pin_quality_min_height'] = isset( $input['pin_quality_min_height'] ) ? absint( $input['pin_quality_min_height'] ) : 600;
		$output['exclude_categories'] = isset( $input['exclude_categories'] ) ? array_map( 'absint', $input['exclude_categories'] ) : [];
		$output['include_only_categories'] = isset( $input['include_only_categories'] ) ? array_map( 'absint', $input['include_only_categories'] ) : [];
		return $output;
	}

	public static function register_menu() {
		add_menu_page(
			__( 'Pinterest Auto-Pin', 'wp-pinterest-auto-pin' ),
			__( 'Pinterest Auto-Pin', 'wp-pinterest-auto-pin' ),
			'manage_options',
			'wppap-settings',
			[ __CLASS__, 'render_settings_page' ],
			'dashicons-pinterest',
			30
		);

		add_submenu_page(
			'wppap-settings',
			__( 'Settings', 'wp-pinterest-auto-pin' ),
			__( 'Settings', 'wp-pinterest-auto-pin' ),
			'manage_options',
			'wppap-settings',
			[ __CLASS__, 'render_settings_page' ]
		);

		add_submenu_page(
			'wppap-settings',
			__( 'Scan Posts', 'wp-pinterest-auto-pin' ),
			__( 'Scan Posts', 'wp-pinterest-auto-pin' ),
			'manage_options',
			'wppap-scan',
			[ __CLASS__, 'render_scan_page' ]
		);

		add_submenu_page(
			'wppap-settings',
			__( 'Pin Queue', 'wp-pinterest-auto-pin' ),
			__( 'Pin Queue', 'wp-pinterest-auto-pin' ),
			'manage_options',
			'wppap-queue',
			[ __CLASS__, 'render_queue_page' ]
		);
	}

	public static function enqueue_admin_assets( $hook ) {
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

	public static function render_settings_page() {
		$settings = self::get_settings();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'wppap_settings_group' ); ?>
				<?php do_settings_sections( 'wppap_settings_page' ); ?>
				
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="pinterest_app_id"><?php esc_html_e( 'Pinterest App ID', 'wp-pinterest-auto-pin' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION_KEY . '[pinterest_app_id]' ); ?>" id="pinterest_app_id" type="text" class="regular-text" value="<?php echo esc_attr( $settings['pinterest_app_id'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Your Pinterest App ID from Pinterest Developer Portal.', 'wp-pinterest-auto-pin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pinterest_app_secret"><?php esc_html_e( 'Pinterest App Secret', 'wp-pinterest-auto-pin' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION_KEY . '[pinterest_app_secret]' ); ?>" id="pinterest_app_secret" type="password" class="regular-text" value="<?php echo esc_attr( $settings['pinterest_app_secret'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Your Pinterest App Secret from Pinterest Developer Portal.', 'wp-pinterest-auto-pin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pinterest_access_token"><?php esc_html_e( 'Pinterest Access Token', 'wp-pinterest-auto-pin' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION_KEY . '[pinterest_access_token]' ); ?>" id="pinterest_access_token" type="password" class="regular-text" value="<?php echo esc_attr( $settings['pinterest_access_token'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Your Pinterest Access Token for API access.', 'wp-pinterest-auto-pin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pinterest_board_id"><?php esc_html_e( 'Pinterest Board ID', 'wp-pinterest-auto-pin' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION_KEY . '[pinterest_board_id]' ); ?>" id="pinterest_board_id" type="text" class="regular-text" value="<?php echo esc_attr( $settings['pinterest_board_id'] ); ?>" />
							<p class="description"><?php esc_html_e( 'The ID of the Pinterest board where pins will be posted.', 'wp-pinterest-auto-pin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="default_pin_interval"><?php esc_html_e( 'Pin Interval (seconds)', 'wp-pinterest-auto-pin' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION_KEY . '[default_pin_interval]' ); ?>" id="default_pin_interval" type="number" class="small-text" value="<?php echo esc_attr( $settings['default_pin_interval'] ); ?>" min="300" />
							<p class="description"><?php esc_html_e( 'How often to pin images (minimum 300 seconds = 5 minutes).', 'wp-pinterest-auto-pin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pin_description_template"><?php esc_html_e( 'Pin Description Template', 'wp-pinterest-auto-pin' ); ?></label></th>
						<td>
							<textarea name="<?php echo esc_attr( self::OPTION_KEY . '[pin_description_template]' ); ?>" id="pin_description_template" class="large-text" rows="3"><?php echo esc_textarea( $settings['pin_description_template'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Template for pin descriptions. Available variables: {site_name}, {post_title}, {post_excerpt}, {post_url}', 'wp-pinterest-auto-pin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto-Pin Enabled', 'wp-pinterest-auto-pin' ); ?></th>
						<td>
							<label><input name="<?php echo esc_attr( self::OPTION_KEY . '[auto_pin_enabled]' ); ?>" type="checkbox" value="1" <?php checked( $settings['auto_pin_enabled'], 1 ); ?> /> <?php esc_html_e( 'Enable automatic pinning', 'wp-pinterest-auto-pin' ); ?></label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function render_scan_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Scan Posts for Images', 'wp-pinterest-auto-pin' ); ?></h1>
			<div class="wppap-scan-container">
				<form id="wppap-scan-form">
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
						<tr>
							<th scope="row"><label for="scan_post_types"><?php esc_html_e( 'Post Types', 'wp-pinterest-auto-pin' ); ?></label></th>
							<td>
								<?php
								$post_types = get_post_types( [ 'public' => true ], 'objects' );
								foreach ( $post_types as $post_type ) {
									echo '<label><input type="checkbox" name="post_types[]" value="' . esc_attr( $post_type->name ) . '" checked /> ' . esc_html( $post_type->label ) . '</label><br>';
								}
								?>
							</td>
						</tr>
					</table>
					<p class="submit">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Start Scan', 'wp-pinterest-auto-pin' ); ?></button>
					</p>
				</form>
				<div id="wppap-scan-progress" style="display: none;">
					<h3><?php esc_html_e( 'Scan Progress', 'wp-pinterest-auto-pin' ); ?></h3>
					<div class="progress-bar">
						<div class="progress-fill"></div>
					</div>
					<p class="scan-status"></p>
				</div>
			</div>
		</div>
		<?php
	}

	public static function render_queue_page() {
		$queue_manager = new WPPAP_Queue_Manager();
		$queue_items = $queue_manager->get_queue_items();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Pin Queue', 'wp-pinterest-auto-pin' ); ?></h1>
			<div class="wppap-queue-container">
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
						<?php foreach ( $queue_items as $item ) : ?>
						<tr>
							<td>
								<?php if ( $item->image_url ) : ?>
									<img src="<?php echo esc_url( $item->image_url ); ?>" style="width: 50px; height: 50px; object-fit: cover;" />
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $item->post_title ); ?></td>
							<td>
								<span class="status-<?php echo esc_attr( $item->status ); ?>">
									<?php echo esc_html( ucfirst( $item->status ) ); ?>
								</span>
							</td>
							<td><?php echo esc_html( date( 'Y-m-d H:i:s', $item->scheduled_time ) ); ?></td>
							<td>
								<?php if ( $item->status === 'pending' ) : ?>
									<button class="button" onclick="wppapPinNow(<?php echo $item->id; ?>)"><?php esc_html_e( 'Pin Now', 'wp-pinterest-auto-pin' ); ?></button>
								<?php endif; ?>
								<button class="button" onclick="wppapRemoveFromQueue(<?php echo $item->id; ?>)"><?php esc_html_e( 'Remove', 'wp-pinterest-auto-pin' ); ?></button>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	public static function set_defaults() {
		$defaults = self::get_settings();
		update_option( self::OPTION_KEY, $defaults );
	}
}
