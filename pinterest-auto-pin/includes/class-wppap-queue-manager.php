<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPPAP_Queue_Manager {
	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'wppap_pin_queue';
	}

	public static function init() {
		add_action( 'wp_ajax_wppap_remove_from_queue', [ __CLASS__, 'remove_from_queue' ] );
	}

	public static function create_tables() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'wppap_pin_queue';
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) NOT NULL,
			image_url varchar(500) NOT NULL,
			image_alt text,
			post_title varchar(255) NOT NULL,
			post_url varchar(500) NOT NULL,
			description text NOT NULL,
			status varchar(20) DEFAULT 'pending',
			scheduled_time bigint(20) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			error_message text,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY status (status),
			KEY scheduled_time (scheduled_time)
		) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function add_to_queue( $data ) {
		global $wpdb;
		
		$settings = WPPAP_Settings::get_settings();
		$interval = $settings['default_pin_interval'];
		
		// Calculate scheduled time
		$last_scheduled = $this->get_last_scheduled_time();
		$scheduled_time = $last_scheduled ? $last_scheduled + $interval : time() + $interval;
		
		$result = $wpdb->insert(
			$this->table_name,
			[
				'post_id' => $data['post_id'],
				'image_url' => $data['image_url'],
				'image_alt' => $data['image_alt'],
				'post_title' => $data['post_title'],
				'post_url' => $data['post_url'],
				'description' => $data['description'],
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
				'%s',
				'%d',
			]
		);
		
		if ( $result ) {
			// Update scheduled time for next item
			$this->update_scheduled_times();
		}
		
		return $result;
	}

	public function get_queue_items( $status = null, $limit = 50 ) {
		global $wpdb;
		
		$where = '';
		$params = [];
		
		if ( $status ) {
			$where = 'WHERE status = %s';
			$params[] = $status;
		}
		
		$sql = "SELECT * FROM {$this->table_name} $where ORDER BY scheduled_time ASC";
		
		if ( $limit ) {
			$sql .= " LIMIT $limit";
		}
		
		if ( $params ) {
			$sql = $wpdb->prepare( $sql, $params );
		}
		
		return $wpdb->get_results( $sql );
	}

	public function get_next_pin_item() {
		global $wpdb;
		
		$sql = "SELECT * FROM {$this->table_name} 
				WHERE status = 'pending' 
				AND scheduled_time <= %d 
				ORDER BY scheduled_time ASC 
				LIMIT 1";
		
		return $wpdb->get_row( $wpdb->prepare( $sql, time() ) );
	}

	public function get_queue_item( $id ) {
		global $wpdb;
		
		$sql = "SELECT * FROM {$this->table_name} WHERE id = %d";
		
		return $wpdb->get_row( $wpdb->prepare( $sql, $id ) );
	}

	public function update_queue_item_status( $id, $status, $error_message = null ) {
		global $wpdb;
		
		$data = [ 'status' => $status ];
		$format = [ '%s' ];
		
		if ( $error_message ) {
			$data['error_message'] = $error_message;
			$format[] = '%s';
		}
		
		return $wpdb->update(
			$this->table_name,
			$data,
			[ 'id' => $id ],
			$format,
			[ '%d' ]
		);
	}

	public function is_image_queued( $image_url, $post_id ) {
		global $wpdb;
		
		$sql = "SELECT COUNT(*) FROM {$this->table_name} 
				WHERE image_url = %s 
				AND post_id = %d 
				AND status IN ('pending', 'processing')";
		
		$count = $wpdb->get_var( $wpdb->prepare( $sql, $image_url, $post_id ) );
		
		return $count > 0;
	}

	public function remove_from_queue( $id ) {
		global $wpdb;
		
		$result = $wpdb->delete(
			$this->table_name,
			[ 'id' => $id ],
			[ '%d' ]
		);
		
		if ( $result ) {
			// Update scheduled times for remaining items
			$this->update_scheduled_times();
		}
		
		return $result;
	}

	public function get_queue_stats() {
		global $wpdb;
		
		$sql = "SELECT status, COUNT(*) as count 
				FROM {$this->table_name} 
				GROUP BY status";
		
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

	private function get_last_scheduled_time() {
		global $wpdb;
		
		$sql = "SELECT MAX(scheduled_time) FROM {$this->table_name} WHERE status = 'pending'";
		
		return (int) $wpdb->get_var( $sql );
	}

	private function update_scheduled_times() {
		global $wpdb;
		
		$settings = WPPAP_Settings::get_settings();
		$interval = $settings['default_pin_interval'];
		
		// Get all pending items
		$pending_items = $this->get_queue_items( 'pending' );
		
		$current_time = time();
		
		foreach ( $pending_items as $index => $item ) {
			$new_scheduled_time = $current_time + ( $index + 1 ) * $interval;
			
			$wpdb->update(
				$this->table_name,
				[ 'scheduled_time' => $new_scheduled_time ],
				[ 'id' => $item->id ],
				[ '%d' ],
				[ '%d' ]
			);
		}
	}

	public static function remove_from_queue() {
		check_ajax_referer( 'wppap_nonce', 'nonce' );
		
		$item_id = absint( $_POST['item_id'] ?? 0 );
		
		if ( ! $item_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid item ID', 'wp-pinterest-auto-pin' ) ] );
		}

		$queue_manager = new self();
		$result = $queue_manager->remove_from_queue( $item_id );
		
		if ( $result ) {
			wp_send_json_success( [ 'message' => __( 'Item removed from queue', 'wp-pinterest-auto-pin' ) ] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Failed to remove item', 'wp-pinterest-auto-pin' ) ] );
		}
	}
}
