<?php
	namespace MAXSMTP;

	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	if( !isset( $_GET['_wpnonce'] ) || !isset( $_GET['id'] ) ){
		exit;
	}

	if( !current_user_can( 'manage_options' ) ){
		exit;
	}

	class Max_SMTP_Email_View {
		public $content;
		public function __construct() {
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( wp_verify_nonce( $nonce, 'max_smtp_nounce' ) ) {
				global $wpdb;
				$queue_id	= absint( esc_attr( $_GET['id'] ) );
				$email_content	= $wpdb->get_var( 'SELECT mail_body FROM ' . $wpdb->base_prefix . 'maxsmtp_queue WHERE id = "' . $queue_id . '"' );

				if( !empty( $email_content ) ){
					$this->content = $email_content;
				}
			}
		}
		public function __destruct() {
			echo $this->content;
		}
	}
	new Max_SMTP_Email_View();
?>