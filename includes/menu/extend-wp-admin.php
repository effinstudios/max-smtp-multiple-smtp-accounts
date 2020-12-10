<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Max_SMTP_Queue_Page_Ex extends WP_List_Table {
	public function __construct() {
		parent::__construct( [
			'singular' => __( 'Email Queue', 'max-smtp' ),
			'plural'   => __( 'Email Queues', 'max-smtp' ),
			'ajax'     => false
		] );

	}

	public function no_items() {
		_e( 'No emails queued.', 'max-smtp' );
	}

	function column_mail_to( $item ) {
		$action_nonce = wp_create_nonce( 'max_smtp_nounce' );
		$title = '<strong>' . esc_html( $item['mail_to'] ) . '</strong>';
		$actions = [
			'delete' => sprintf( '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">' . __( 'Delete', 'max-smtp' ) . '</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $action_nonce ),
			'send' => sprintf( '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">' . __( 'Send', 'max-smtp' ) . '</a>', esc_attr( $_REQUEST['page'] ), 'send', absint( $item['id'] ), $action_nonce ),
		];
		return $title . $this->row_actions( $actions );
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'mail_to':
			case 'mail_subject':
			case 'mail_failed':
			case 'mail_status':
			case 'mail_error':
				return esc_html( $item[ $column_name ] );
		}
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="batch-action[]" value="%s" />', esc_attr( $item['id'] )
		);
	}

	function get_columns() {
		$columns = [
			'cb'				=> '<input type="checkbox" />',
			'id'				=> __( 'ID', 'max-smtp' ),
			'mail_to'			=> __( 'To', 'max-smtp' ),
			'mail_subject'		=> __( 'Subject', 'max-smtp' ),
			'mail_failed'		=> __( 'Date', 'max-smtp' ),
			'mail_status'		=> __( 'Status', 'max-smtp' ),
			'mail_error'		=> __( 'Error', 'max-smtp' ),
		];
		return $columns;
	}

	function extra_tablenav( $which ) {
		$total_items  = self::maxsmtp_queue_count();
		if( $total_items ){
			$action_nonce = wp_create_nonce( 'max_smtp_nounce' );
			$url	= esc_url_raw( remove_query_arg( [ 'id' ], esc_url_raw( add_query_arg( [ 'action' => 'truncate', '_wpnonce' => $action_nonce ] ) ) ) );
			if ( $which == "top" ) : ?>
				<div class="alignleft actions">
					<?php echo sprintf( '<a href="%s" class="button button-primary action" onclick="return confirm(\'' . __( 'Are you sure you want to delete everything?', 'max-smtp' ) . '\')">' . __( 'Clear Queue', 'max-smtp' ) . '</a>', $url ); ?>
				</div>
			<?php endif;
		}
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
			'id'				=> array( 'id', true ),
			'mail_to'			=> array( 'mail_to', true ),
			'mail_subject'		=> array( 'mail_subject', true ),
			'mail_failed'		=> array( 'mail_failed', true ),
			'mail_status'		=> array( 'mail_status', true ),
			'mail_error'		=> array( 'mail_error', true ),
		);
		return $sortable_columns;
	}

	public function get_bulk_actions() {
		$actions = [
			'batch-delete' => __( 'Delete Emails', 'max-smtp' ),
			'batch-resend' => __( 'Send Emails', 'max-smtp' )
		];
		return $actions;
	}

	public function prepare_items() {
		$this->_column_headers = $this->get_column_info();
		$this->maxsmtp_process_bulk_queue_action();
		$per_page     = $this->get_items_per_page( 'items_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = self::maxsmtp_queue_count();
		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page
		] );
		$this->items = self::maxsmtp_get_queues( $per_page, $current_page );
	}

	public static function maxsmtp_get_queues( $per_page = 20, $page_number = 1 ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->prefix}maxsmtp_queue";
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}
		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		return $result;
	}

	public static function maxsmtp_delete_queue( $id ) {
		if( !is_int( $id ) ){
			return false;
		}

		global $wpdb;
		return $wpdb->delete(
				"{$wpdb->prefix}maxsmtp_queue",
				[ 'id' => $id ],
				[ '%d' ]
			);
	}

	public static function maxsmtp_queue_count() {
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}maxsmtp_queue";
		return $wpdb->get_var( $sql );
	}

	public function maxsmtp_process_bulk_queue_action() {
		if ( 'delete' === $this->current_action() ) {
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			if ( ! wp_verify_nonce( $nonce, 'max_smtp_nounce' ) ) {
				MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'Somthing terrible happend... Your settings was not saved, please try again.', 'max-smtp' ), 'error' );
				wp_redirect( esc_url_raw( remove_query_arg( ['id','action','_wpnonce'] ) ) );
			}
			else {
				$queue_deleted	= self::maxsmtp_delete_queue( absint( $_GET['id'] ) );
				if( $queue_deleted ){
					MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'Email queue deleted.', 'max-smtp' ), 'success' );
				} else {
					MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'Email queue was not deleted, please try again.', 'max-smtp' ), 'error' );
				}
				wp_redirect( esc_url_raw( remove_query_arg( ['id','action','_wpnonce'] ) ) );
				exit;
			}
		}

		if ( 'send' === $this->current_action() ) {
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			if ( ! wp_verify_nonce( $nonce, 'max_smtp_nounce' ) ) {
				MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'Somthing terrible happend... Your settings was not saved, please try again.', 'max-smtp' ), 'error' );
				wp_redirect( esc_url_raw( remove_query_arg( ['id','action','_wpnonce'] ) ) );
			}
			else {
				$email_sent	= MAXSMTP\Max_SMTP_Plugin::maxsmtp_resend_queue( 'id IN (' . @implode( ', ', [ absint( $_GET['id'] ) ] ) . ')' );
				if( $email_sent ){
					MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( $email_sent . __( ' email queue sent.', 'max-smtp' ), 'success' );
				} else {
					MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'Email queue was not sent, please try again.', 'max-smtp' ), 'error' );
				}
				wp_redirect( esc_url_raw( remove_query_arg( ['id','action','_wpnonce'] ) ) );
				exit;
			}
		}

		if ( 'truncate' === $this->current_action() ) {
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			if ( ! wp_verify_nonce( $nonce, 'max_smtp_nounce' ) ) {
				MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'Somthing terrible happend... Your settings was not saved, please try again.', 'max-smtp' ), 'error' );
				wp_redirect( esc_url_raw( remove_query_arg( ['id','action','_wpnonce'] ) ) );
			}
			else {
				global $wpdb;
				$delete_all	= $wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'maxsmtp_queue' );
				if( $delete_all ){
					MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'Deleted all queued emails.', 'max-smtp' ), 'success' );
				} else {
					MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'Cannot delete queued emails, please try again.', 'max-smtp' ), 'error' );
				}
				wp_redirect( esc_url_raw( remove_query_arg( ['id','action','_wpnonce'] ) ) );
				exit;
			}
		}

		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'batch-delete' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'batch-delete' ) ) {
			$delete_ids	= esc_sql( $_POST['batch-action'] );
			$queues_deleted	= 0;
			foreach ( $delete_ids as $id ) {
				if( self::maxsmtp_delete_queue( $id ) ){
					$queues_deleted++;
				}
			}
			if( $queues_deleted ){
				MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( $queues_deleted . __( ' email queues deleted.', 'max-smtp' ), 'success' );
			} else {
				MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'Email queue was not deleted, please try again.', 'max-smtp' ), 'error' );
			}
			wp_redirect( esc_url( add_query_arg() ) );
			exit;
		}

		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'batch-resend' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'batch-resend' ) ) {
			$resend_ids	= esc_sql( $_POST['batch-action'] );
			$emails_sent	= MAXSMTP\Max_SMTP_Plugin::maxsmtp_resend_queue( 'id IN (' . @implode( ', ', $resend_ids ) . ')' );
			if( $emails_sent ){
				MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( $emails_sent . __( ' email queues sent.', 'max-smtp' ), 'success' );
			} else {
				MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'Email queues was not sent, please try again.', 'max-smtp' ), 'error' );
			}
			wp_redirect( esc_url( add_query_arg() ) );
			exit;
		}

	}

}

class Max_SMTP_Accounts_Page_Ex extends WP_List_Table {
	public function __construct() {
		parent::__construct( [
			'singular' => __( 'SMTP Account', 'max-smtp' ),
			'plural'   => __( 'SMTP Accounts', 'max-smtp' ),
			'ajax'     => false
		] );
	}

	public function no_items() {
		_e( 'No SMTP accounts saved.', 'max-smtp' );
	}

	function column_smtp_name( $item ) {
		$action_nonce = wp_create_nonce( 'max_smtp_nounce' );
		$title = '<strong>' . esc_html( $item['smtp_name'] ) . '</strong>';
		$actions = [
			'delete'	=> sprintf( '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">' . __( 'Delete', 'max-smtp' ) . '</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $action_nonce ),
			'edit'	=> sprintf( '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">' . __( 'Edit', 'max-smtp' ) . '</a>', esc_attr( $_REQUEST['page'] ), 'edit', absint( $item['id'] ), $action_nonce ),
		];
		return $title . $this->row_actions( $actions );
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'smtp_name':
			case 'smtp_host':
			case 'smtp_port':
			case 'smtp_user':
			case 'smtp_limit_day':
			case 'smtp_limit_hour':
			case 'smtp_limit_second':
				return esc_html( $item[ $column_name ] );
			case 'smtp_secure':
				return empty( $item[ $column_name ] ) ? __( 'None', 'max-smtp' ) : strtoupper( esc_html( $item[ $column_name ] ) );
			case 'smtp_auth':
			case 'smtp_autotls':
				return (int) $item[ $column_name ] ? __( 'Yes', 'max-smtp' ) : __( 'No', 'max-smtp' );
			case 'smtp_active':
				return (int) $item[ $column_name ] ? '<span class="smtp-active">' . __( 'Active', 'max-smtp' ) . '</span>' : '<span class="smtp-disabled">' . __( 'Disabled', 'max-smtp' ) . '</span>';
			case 'smtp_sesssion_day':
				$smtp_arr	= get_option( 'max_smtp_set_mail_account' );
				if( !empty( $smtp_arr ) ){
					if( $item['id'] === $smtp_arr['id'] ){
						return esc_html( $smtp_arr[ $column_name ] );
					}
				}
				return esc_html( $item[ $column_name ] );
		}
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="batch-action[]" value="%s" />', esc_attr( $item['id'] )
		);
	}

	function get_columns() {
		$columns = [
			'cb'				=> '<input type="checkbox" />',
			'smtp_name'		=> __( 'Name', 'max-smtp' ),
			'smtp_host'		=> __( 'Host', 'max-smtp' ),
			'smtp_port'		=> __( 'Port', 'max-smtp' ),
			'smtp_secure'		=> __( 'Security', 'max-smtp' ),
			'smtp_auth'		=> __( 'Auth', 'max-smtp' ),
			'smtp_autotls'		=> __( 'AutoTLS', 'max-smtp' ),
			'smtp_user'		=> __( 'User Name', 'max-smtp' ),
			'smtp_limit_day'	=> __( 'Max Daily Limit', 'max-smtp' ),
			'smtp_limit_hour'	=> __( 'Limit Per Hour', 'max-smtp' ),
			'smtp_limit_second'	=> __( 'Limit Per Second', 'max-smtp' ),
			'smtp_sesssion_day'	=> __( 'Sent Today', 'max-smtp' ),
			'smtp_active'		=> __( 'Status', 'max-smtp' ),
		];
		return $columns;
	}

	function extra_tablenav( $which ) {
		$url	= esc_url_raw( remove_query_arg( [ 'id', '_wpnonce' ], esc_url_raw( add_query_arg( [ 'action' => 'add' ] ) ) ) );
		if ( $which == "top" ) : ?>
			<div class="alignleft actions">
				<?php echo sprintf( '<a href="%s" class="button button-primary action">' . __( 'Add SMTP Account', 'max-smtp' ) . '</a>', $url ); ?>
			</div>
		<?php endif;
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
			'smtp_name'		=> array( 'smtp_name', true ),
			'smtp_host'		=> array( 'smtp_host', true ),
			'smtp_port'		=> array( 'smtp_port', true ),
			'smtp_secure'		=> array( 'smtp_secure', true ),
			'smtp_auth'		=> array( 'smtp_auth', true ),
			'smtp_autotls'		=> array( 'smtp_autotls', true ),
			'smtp_user'		=> array( 'smtp_user', true ),
			'smtp_limit_day'	=> array( 'smtp_limit_day', true ),
			'smtp_limit_hour'	=> array( 'smtp_limit_hour', true ),
			'smtp_limit_second'	=> array( 'smtp_limit_second', true ),
			'smtp_sesssion_day'	=> array( 'smtp_sesssion_day', true ),
			'smtp_active'		=> array( 'smtp_active', true ),
		);
		return $sortable_columns;
	}

	public function get_bulk_actions() {
		$actions = [
			'batch-delete' => __( 'Delete Selected', 'max-smtp' ),
			'batch-enable' => __( 'Enable Selected', 'max-smtp' ),
			'batch-disable' => __( 'Disable Selected', 'max-smtp' )
		];
		return $actions;
	}

	public function prepare_items() {
		$this->_column_headers = $this->get_column_info();
		$this->maxsmtp_process_bulk_smtp_action();
		$per_page     = $this->get_items_per_page( 'items_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = self::maxsmtp_smtp_count();
		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page
		] );
		$this->items = self::maxsmtp_get_smtps( $per_page, $current_page );
	}

	public static function maxsmtp_get_smtps( $per_page = 20, $page_number = 1 ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->prefix}maxsmtp_smtps";
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}
		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		return $result;
	}

	public static function maxsmtp_smtp_count() {
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}maxsmtp_smtps";
		return $wpdb->get_var( $sql );
	}

	public static function maxsmtp_delete_smtp( $id ) {
		if( !is_int( $id ) ){
			return false;
		}

		global $wpdb;
		if( $queue_limit = get_option( 'max_smtp_queue_limit' ) ){
			if( $queue_limit > 0 ){
				$thislimit	= $wpdb->get_var( 'SELECT smtp_limit_second FROM ' . $wpdb->prefix . 'maxsmtp_smtps WHERE id = "' . $id . '"' );
				if( is_int( absint( $thislimit ) ) ){
					update_option( 'max_smtp_queue_limit', $queue_limit - $thislimit );
				}
			}
		}

		if( $deleted = $wpdb->delete( "{$wpdb->prefix}maxsmtp_smtps", [ 'id' => $id ], [ '%d' ] ) ){
			$smtp_setting	= get_option( 'max_smtp_set_mail_account' );
			if( !empty( $smtp_setting['id'] ) && $smtp_setting['id'] == $id ){
				MAXSMTP\Max_SMTP_Mail_Functions::maxsmtp_set_smtp_option();
			}
			return $deleted;
		} else {
			return false;
		}
	}

	private static function maxsmtp_encrypt( $plaintext ) {
		$password		= defined( 'NONCE_KEY' ) ? NONCE_KEY : AUTH_KEY;
		$iv			= openssl_random_pseudo_bytes( 16 );
		$ciphertext	= openssl_encrypt( $plaintext, "AES-256-CBC", hash( 'sha256', $password, true ), OPENSSL_RAW_DATA, $iv );
		$hmac			= hash_hmac( 'sha256', $ciphertext.$iv, hash( 'sha256', $password, true ), true );
		return		bin2hex( $iv.$hmac.$ciphertext );
	}

	public function maxsmtp_process_bulk_smtp_action() {
		global $wpdb;

		if( isset( $_POST['addsmtp'] ) && wp_verify_nonce( $_POST['_sub_nounce'], 'max_smtp_submit_nounce' ) ){
			$data	= [];

			foreach( $_POST as $smtp_key => $smtp_val ){
				$sanitized	= '';
				switch( $smtp_key ){
					case 'smtp_password':
						$data[ $smtp_key ] = self::maxsmtp_encrypt( $smtp_val );
						break;
					case 'smtp_name':
					case 'smtp_host':
					case 'smtp_user':
						if( $sanitized = sanitize_text_field( $smtp_val ) ){
							$data[ $smtp_key ] = $sanitized;
						}
						break;
					case 'smtp_port':
					case 'smtp_limit_day':
					case 'smtp_limit_hour':
					case 'smtp_limit_second':
						if( $sanitized = absint( sanitize_text_field( $smtp_val ) ) ){
							if( is_int( $sanitized ) ){
								$data[ $smtp_key ] = $sanitized;
							}
						}
						break;
					case 'smtp_secure':
						if( $sanitized = sanitize_text_field( $smtp_val ) ){
							if( in_array( $sanitized, [ 'ssl', 'tls' ] ) ){
								$data[ $smtp_key ] = $sanitized;
							} else {
								$data[ $smtp_key ] = '';
							}
						}
						break;
					case 'smtp_auth':
					case 'smtp_autotls':
						if( $sanitized = sanitize_text_field( $smtp_val ) ){
							if( $sanitized == 1 ){
								$data[ $smtp_key ] = 1;
							} else {
								$data[ $smtp_key ] = 0;
							}
						}
						break;
				}
			}

			$last		= $wpdb->get_row( 'SHOW TABLE STATUS LIKE "' . $wpdb->prefix . 'maxsmtp_smtps"' );
			$lastid	= $last->Auto_increment;

			if( !empty( $data ) ){
				if( $smtp_inserted = $wpdb->insert( $wpdb->prefix . 'maxsmtp_smtps', $data ) ){
					if( isset( $data['smtp_limit_second'] ) ){
						$queue_limit = get_option( 'max_smtp_queue_limit' );
						update_option( 'max_smtp_queue_limit', $queue_limit + $data['smtp_limit_second'] );
					}
					$smtp_setting	= get_option( 'max_smtp_set_mail_account' );
					if( empty( $smtp_setting ) ){
						MAXSMTP\Max_SMTP_Mail_Functions::maxsmtp_set_smtp_option();
						update_option( 'max_smtp_pause_status', 0 );
					}
					MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'SMTP account saved.', 'max-smtp' ), 'success' );
				} else {
					MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'SMTP account was not saved, please try again.', 'max-smtp' ), 'error' );
				}
			}

			if( $smtp_inserted ){
				wp_redirect( esc_url_raw( add_query_arg( [ 'id' => $lastid ] ) ) );
			} else {
				wp_redirect( esc_url_raw( remove_query_arg( [ 'action' ] ) ) );
			}

			exit;
		}

		if( isset( $_POST['editsmtp'] ) && wp_verify_nonce( $_POST['_sub_nounce'], 'max_smtp_submit_nounce' ) ){
			$data					= [];
			$queu_limit_reference		= 0;
			foreach( $_POST as $smtp_key => $smtp_val ){
				$sanitized	= '';
				switch( $smtp_key ){
					case 'smtp_password':
						$data[ $smtp_key ] = self::maxsmtp_encrypt( $smtp_val );
						break;
					case 'smtp_name':
					case 'smtp_host':
					case 'smtp_user':
						if( $sanitized = sanitize_text_field( $smtp_val ) ){
							$data[ $smtp_key ] = $sanitized;
						}
						break;
					case 'smtp_port':
					case 'smtp_limit_day':
					case 'smtp_limit_hour':
					case 'smtp_limit_second':
					case 'id':
						if( $sanitized = absint( sanitize_text_field( $smtp_val ) ) ){
							if( is_int( $sanitized ) ){
								$data[ $smtp_key ] = $sanitized;
							}
						}
						break;
					case 'smtp_secure':
						if( $sanitized = sanitize_text_field( $smtp_val ) ){
							if( in_array( $sanitized, [ 'ssl', 'tls' ] ) ){
								$data[ $smtp_key ] = $sanitized;
							} else {
								$data[ $smtp_key ] = '';
							}
						}
						break;
					case 'smtp_auth':
					case 'smtp_autotls':
						if( $sanitized = sanitize_text_field( $smtp_val ) ){
							if( $sanitized == 1 ){
								$data[ $smtp_key ] = 1;
							} else {
								$data[ $smtp_key ] = 0;
							}
						}
						break;
					case 'queu_limit_reference':
						if( $sanitized = absint( sanitize_text_field( $smtp_val ) ) ){
							if( is_int( $sanitized ) ){
								$queu_limit_reference = $sanitized;
							}
						}
						break;
				}
			}

			if( !empty( $data ) ){
				if( $wpdb->update( $wpdb->prefix . 'maxsmtp_smtps', $data, [ 'id' => $data['id'] ] ) ){
					if( isset( $data['smtp_limit_second'] ) ){
						$queue_limit = get_option( 'max_smtp_queue_limit' );
						if( $data['smtp_limit_second'] > $queu_limit_reference ){
							$queue_limit = $queue_limit + ( $data['smtp_limit_second'] - $queu_limit_reference );
							update_option( 'max_smtp_queue_limit', $queue_limit );
						} else if( $data['smtp_limit_second'] < $queu_limit_reference ){
							$queue_limit = $queue_limit - ( $queu_limit_reference - $data['smtp_limit_second'] );
							update_option( 'max_smtp_queue_limit', $queue_limit );
						}
					}
					$smtp_setting	= get_option( 'max_smtp_set_mail_account' );
					if( !empty( $smtp_setting['id'] ) && $smtp_setting['id'] == $data['id'] ){
						MAXSMTP\Max_SMTP_Mail_Functions::maxsmtp_set_smtp_option();
					}
					MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'SMTP account updated.', 'max-smtp' ), 'success' );
				} else {
					MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'SMTP account was not updated, please try again.', 'max-smtp' ), 'error' );
				}
			}

			wp_redirect( esc_url_raw( add_query_arg( [] ) ) );
			exit;
		}

		if ( 'delete' === $this->current_action() ) {
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			if ( ! wp_verify_nonce( $nonce, 'max_smtp_nounce' ) ) {
				MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'Somthing terrible happend... Your settings was not saved, please try again.', 'max-smtp' ), 'error' );
				wp_redirect( esc_url_raw( remove_query_arg( ['id','action','_wpnonce'] ) ) );
			}
			else {
				$smtp_deleted	= self::maxsmtp_delete_smtp( absint( $_GET['id'] ) );
				if( $smtp_deleted ){
					MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'SMTP account deleted.', 'max-smtp' ), 'success' );
				} else {
					MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'SMTP account was not deleted, please try again.', 'max-smtp' ), 'error' );
				}
				wp_redirect( esc_url_raw( remove_query_arg( [ 'id','action','_wpnonce' ] ) ) );
				exit;
			}
		}

		if ( 'edit' === $this->current_action() ) {
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			if ( ! wp_verify_nonce( $nonce, 'max_smtp_nounce' ) ) {
				MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'Somthing terrible happend... Your settings was not saved, please try again.', 'max-smtp' ), 'error' );
				wp_redirect( esc_url_raw( remove_query_arg( ['id','action','_wpnonce'] ) ) );
			}
			else {
			}
		}

		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'batch-delete' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'batch-delete' ) ) {
			$delete_ids	= esc_sql( $_POST['batch-action'] );
			$smtps_deleted	= 0;
			foreach ( $delete_ids as $id ) {
				if( self::maxsmtp_delete_smtp( absint( $id ) ) ){
					$smtps_deleted++;
				}
			}
			if( $smtps_deleted ){
				MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( $smtps_deleted . __( ' SMTP accounts deleted.', 'max-smtp' ), 'success' );
			} else {
				MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'SMTP accounts was not deleted, please try again.', 'max-smtp' ), 'error' );
			}
			wp_redirect( esc_url( add_query_arg() ) );
			exit;
		}

		if ( 
			( isset( $_POST['action'] ) && $_POST['action'] == 'batch-enable' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'batch-enable' ) || 
			( isset( $_POST['action'] ) && $_POST['action'] == 'batch-disable' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'batch-disable' )
		 ) {
			$smtp_ids		= esc_sql( $_POST['batch-action'] );
			$active		= $_POST['action'] == 'batch-disable' ? false : true;
			$altactive		= $_POST['action'] == 'batch-disable' ? true : false;
			$smtp_array	= $wpdb->get_results( 'SELECT id, smtp_limit_second FROM ' . $wpdb->prefix . 'maxsmtp_smtps WHERE smtp_active = "' . $altactive . '" AND id IN (' . implode( ', ', $smtp_ids ) . ')', ARRAY_A );
			$this_smtp		= [];
			if( !empty( $smtp_array ) ){
				foreach ( $smtp_array as $smtp ) {
					$this_smtp[]	= $smtp['id'];
					$queue_limit	= get_option( 'max_smtp_queue_limit' );
					if( $active ){
						if( is_int( $smtp['smtp_limit_second'] ) ){
							update_option( 'max_smtp_queue_limit', $queue_limit + $smtp['smtp_limit_second'] );
						}
					} else {
						if( $queue_limit > 0 ){
							if( is_int( $smtp['smtp_limit_second'] ) ){
								update_option( 'max_smtp_queue_limit', $queue_limit - $smtp['smtp_limit_second'] );
							}
						}
					}
				}
			}
			$smtp_updates	= $wpdb->query( 'UPDATE ' . $wpdb->prefix . 'maxsmtp_smtps SET smtp_active = "' . $active . '" WHERE id IN (' . implode( ', ', $this_smtp ) . ')' );
			if( $smtp_updates ){
				$smtp_setting	= get_option( 'max_smtp_set_mail_account' );
				if( !empty( $smtp_setting['id'] ) && in_array( $smtp_setting['id'], $this_smtp ) ){
					MAXSMTP\Max_SMTP_Mail_Functions::maxsmtp_set_smtp_option();
				}
				MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( $smtp_updates . __( ' SMTP accounts updated.', 'max-smtp' ), 'success' );
			} else {
				MAXSMTP\Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'SMTP accounts was not updated, please try again.', 'max-smtp' ), 'error' );
			}
			wp_redirect( esc_url( add_query_arg() ) );
			exit;
		}
	}
}
?>
