<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSF_Settings {
	const OPTION_KEY = 'wpsf_settings';

	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
	}

	public static function get_settings() {
		$defaults = [
			'pinterest_profile_url' => '',
			'auto_overlay' => 1,
			'overlay_label' => __( 'Save to Pinterest', 'wp-pinterest-save-follow' ),
			'follow_prompt_title' => __( 'Follow us on Pinterest', 'wp-pinterest-save-follow' ),
			'follow_prompt_body' => __( 'Thanks for pinning! Want more ideas? Follow our Pinterest page.', 'wp-pinterest-save-follow' ),
			'follow_button_label' => __( 'Follow on Pinterest', 'wp-pinterest-save-follow' ),
		];
		return wp_parse_args( get_option( self::OPTION_KEY, [] ), $defaults );
	}

	public static function register_settings() {
		register_setting( 'wpsf_settings_group', self::OPTION_KEY, [ __CLASS__, 'sanitize' ] );

		add_settings_section(
			'wpsf_main_section',
			__( 'Pinterest Save & Follow Settings', 'wp-pinterest-save-follow' ),
			function () { echo '<p>' . esc_html__( 'Configure your Pinterest profile and overlay behavior.', 'wp-pinterest-save-follow' ) . '</p>'; },
			'wpsf_settings_page'
		);

		add_settings_field(
			'pinterest_profile_url',
			__( 'Pinterest Profile URL', 'wp-pinterest-save-follow' ),
			[ __CLASS__, 'field_profile_url' ],
			'wpsf_settings_page',
			'wpsf_main_section'
		);

		add_settings_field(
			'auto_overlay',
			__( 'Enable automatic image overlay', 'wp-pinterest-save-follow' ),
			[ __CLASS__, 'field_auto_overlay' ],
			'wpsf_settings_page',
			'wpsf_main_section'
		);

		add_settings_field(
			'overlay_label',
			__( 'Overlay button label', 'wp-pinterest-save-follow' ),
			[ __CLASS__, 'field_overlay_label' ],
			'wpsf_settings_page',
			'wpsf_main_section'
		);

		add_settings_field(
			'follow_prompt_title',
			__( 'Follow prompt title', 'wp-pinterest-save-follow' ),
			[ __CLASS__, 'field_follow_prompt_title' ],
			'wpsf_settings_page',
			'wpsf_main_section'
		);

		add_settings_field(
			'follow_prompt_body',
			__( 'Follow prompt body', 'wp-pinterest-save-follow' ),
			[ __CLASS__, 'field_follow_prompt_body' ],
			'wpsf_settings_page',
			'wpsf_main_section'
		);

		add_settings_field(
			'follow_button_label',
			__( 'Follow button label', 'wp-pinterest-save-follow' ),
			[ __CLASS__, 'field_follow_button_label' ],
			'wpsf_settings_page',
			'wpsf_main_section'
		);
	}

	public static function sanitize( $input ) {
		$output = [];
		$output['pinterest_profile_url'] = isset( $input['pinterest_profile_url'] ) ? esc_url_raw( trim( $input['pinterest_profile_url'] ) ) : '';
		$output['auto_overlay'] = isset( $input['auto_overlay'] ) ? (int) (bool) $input['auto_overlay'] : 0;
		$output['overlay_label'] = isset( $input['overlay_label'] ) ? sanitize_text_field( $input['overlay_label'] ) : '';
		$output['follow_prompt_title'] = isset( $input['follow_prompt_title'] ) ? sanitize_text_field( $input['follow_prompt_title'] ) : '';
		$output['follow_prompt_body'] = isset( $input['follow_prompt_body'] ) ? wp_kses_post( $input['follow_prompt_body'] ) : '';
		$output['follow_button_label'] = isset( $input['follow_button_label'] ) ? sanitize_text_field( $input['follow_button_label'] ) : '';
		return $output;
	}

	public static function register_menu() {
		add_options_page(
			__( 'Pinterest Save & Follow', 'wp-pinterest-save-follow' ),
			__( 'Pinterest Save & Follow', 'wp-pinterest-save-follow' ),
			'manage_options',
			'wpsf_settings_page',
			[ __CLASS__, 'render_settings_page' ]
		);
	}

	public static function render_settings_page() {
		$settings = self::get_settings();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'wpsf_settings_group' ); ?>
				<?php do_settings_sections( 'wpsf_settings_page' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="pinterest_profile_url"><?php esc_html_e( 'Pinterest Profile URL', 'wp-pinterest-save-follow' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION_KEY . '[pinterest_profile_url]' ); ?>" id="pinterest_profile_url" type="url" class="regular-text" value="<?php echo esc_attr( $settings['pinterest_profile_url'] ); ?>" placeholder="https://www.pinterest.com/yourusername/" />
							<p class="description"><?php esc_html_e( 'Full URL to your Pinterest profile.', 'wp-pinterest-save-follow' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable automatic image overlay', 'wp-pinterest-save-follow' ); ?></th>
						<td>
							<label><input name="<?php echo esc_attr( self::OPTION_KEY . '[auto_overlay]' ); ?>" type="checkbox" value="1" <?php checked( $settings['auto_overlay'], 1 ); ?> /> <?php esc_html_e( 'Show a Save/Pin overlay on post images automatically', 'wp-pinterest-save-follow' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="overlay_label"><?php esc_html_e( 'Overlay button label', 'wp-pinterest-save-follow' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION_KEY . '[overlay_label]' ); ?>" id="overlay_label" type="text" class="regular-text" value="<?php echo esc_attr( $settings['overlay_label'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="follow_prompt_title"><?php esc_html_e( 'Follow prompt title', 'wp-pinterest-save-follow' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION_KEY . '[follow_prompt_title]' ); ?>" id="follow_prompt_title" type="text" class="regular-text" value="<?php echo esc_attr( $settings['follow_prompt_title'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="follow_prompt_body"><?php esc_html_e( 'Follow prompt body', 'wp-pinterest-save-follow' ); ?></label></th>
						<td>
							<textarea name="<?php echo esc_attr( self::OPTION_KEY . '[follow_prompt_body]' ); ?>" id="follow_prompt_body" class="large-text" rows="4"><?php echo esc_textarea( $settings['follow_prompt_body'] ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="follow_button_label"><?php esc_html_e( 'Follow button label', 'wp-pinterest-save-follow' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION_KEY . '[follow_button_label]' ); ?>" id="follow_button_label" type="text" class="regular-text" value="<?php echo esc_attr( $settings['follow_button_label'] ); ?>" />
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function field_profile_url() {}
	public static function field_auto_overlay() {}
	public static function field_overlay_label() {}
	public static function field_follow_prompt_title() {}
	public static function field_follow_prompt_body() {}
	public static function field_follow_button_label() {}
}


