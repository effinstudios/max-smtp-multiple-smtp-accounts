<?php
	namespace MAXSMTP;

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class Max_SMTP_Mail_Functions {
		public function __construct(){}

		public static function maxsmtp_mail_init_actions(){
			add_filter( 'wp_mail', [ __CLASS__, 'maxsmtp_set_mail_cache' ] );
			add_action( 'phpmailer_init', [ __CLASS__, 'maxsmtp_set_smtp_phpmailer' ] );
			add_action( 'init', [ __CLASS__, 'maxsmtp_mail_view_page' ] );
		}

		public static function maxsmtp_set_mail_cache( $atts ){
			wp_cache_set( 'max_smtp_mail_cache', $atts, 'max_smtp_cache', 600 );
			return $atts;
		}

		public static function maxsmtp_set_smtp_option(){
			global $wpdb;
			$sql		= 'SELECT * FROM ' . $wpdb->base_prefix . 'maxsmtp_smtps';
			$sql		.= ' WHERE smtp_active = 1';
			$sql		.= ' AND smtp_limit_second > smtp_sesssion_second';
			$sql		.= ' AND smtp_limit_hour > smtp_sesssion_hour';
			$sql		.= ' AND smtp_limit_day > smtp_sesssion_day';
			$sql		.= ' AND smtp_active_time < "' . current_time( 'mysql' ) . '"';
			$sql		.= ' LIMIT 1';
			$acct_arr	= $wpdb->get_results( $sql, ARRAY_A );
			if( !empty( $acct_arr[0] ) ){
				if( update_option( 'max_smtp_set_mail_account', $acct_arr[0] ) ){
					return $acct_arr[0];
				}
			} else {
				update_option( 'max_smtp_set_mail_account', [] );
			}
			return false;
		}

		public static function maxsmtp_set_smtp_phpmailer( $phpmailer ){
			global $wpdb;
			global $phpmailer;
			$maybelimit	= '';
			$pause_status	= false;

			Max_SMTP_Retry_Phpmailer:

			$smtp_setting	= get_option( 'max_smtp_set_mail_account' );
			if( empty( $smtp_setting ) ){
				$smtp_setting	= self::maxsmtp_set_smtp_option();
			}

			if( !empty( $smtp_setting ) ){
				$now			= strtotime( current_time( 'mysql' ) );
				$less_day		= $now - 86400;
				$less_hour		= $now - 3600;
				$less_second	= $now - 1;

				if( strtotime( $smtp_setting['smtp_time_day'] ) >= $less_day ){
					if( $smtp_setting['smtp_sesssion_day'] >= $smtp_setting['smtp_limit_day'] ){
						$smtp_setting['smtp_sesssion_day']	= 0;
						$smtp_setting['smtp_time_day']		= date( 'Y-m-d H:i:s', $now );
						$smtp_setting['smtp_active_time']	= date( 'Y-m-d H:i:s', $now + 86400 );
						$wpdb->update( $wpdb->prefix . 'maxsmtp_smtps', $smtp_setting, [ 'id' => $smtp_setting['id'] ] );
						update_option( 'max_smtp_set_mail_account', 0 );
						$maybelimit	= '(Daily limit reached)';
						goto Max_SMTP_Retry_Phpmailer;
					}
				} else {
					$smtp_setting['smtp_sesssion_day'] = 0;
					$smtp_setting['smtp_time_day'] = date( 'Y-m-d H:i:s', $now );
				}

				if( strtotime( $smtp_setting['smtp_time_hour'] ) >= $less_hour ){
					if( $smtp_setting['smtp_sesssion_hour'] >= $smtp_setting['smtp_limit_hour'] ){
						$smtp_setting['smtp_sesssion_hour']	= 0;
						$smtp_setting['smtp_time_hour']		= date( 'Y-m-d H:i:s', $now );
						$smtp_setting['smtp_active_time']	= date( 'Y-m-d H:i:s', $now + 3600 );
						$wpdb->update( $wpdb->prefix . 'maxsmtp_smtps', $smtp_setting, [ 'id' => $smtp_setting['id'] ] );
						update_option( 'max_smtp_set_mail_account', 0 );
						$maybelimit	= '(Hourly limit reached)';
						goto Max_SMTP_Retry_Phpmailer;
					}
				} else {
					$smtp_setting['smtp_sesssion_hour'] = 0;
					$smtp_setting['smtp_time_hour'] = date( 'Y-m-d H:i:s', $now );
				}

				if( strtotime( $smtp_setting['smtp_time_second'] ) >= $less_second ){
					if( $smtp_setting['smtp_sesssion_second'] >= $smtp_setting['smtp_limit_second'] ){
						$smtp_setting['smtp_sesssion_second']	= 0;
						$smtp_setting['smtp_time_second']	= date( 'Y-m-d H:i:s', $now );
						$smtp_setting['smtp_active_time']	= date( 'Y-m-d H:i:s', $now + 1 );
						$wpdb->update( $wpdb->prefix . 'maxsmtp_smtps', $smtp_setting, [ 'id' => $smtp_setting['id'] ] );
						update_option( 'max_smtp_set_mail_account', 0 );
						goto Max_SMTP_Retry_Phpmailer;
					}
				} else {
					$smtp_setting['smtp_sesssion_second'] = 0;
					$smtp_setting['smtp_time_second'] = date( 'Y-m-d H:i:s', $now );
				}

				$maybelimit	= '';
			} else {
				wp_cache_set( 'max_smtp_pause', true, 'max_smtp_cache', 600 );
				$pause_status = update_option( 'max_smtp_pause_status', 1 );
				if( !empty( $maybelimit ) ){
					update_option( 'max_smtp_pause_message', $maybelimit );
				}
			}

			if( ! $pause_status ){
				$sent				= false;
				$error_pass		= '';
				$mail_error_data      = [];

				$smtp_setting		= apply_filters( 'maxsmtp_filter_smtp_settings', $smtp_setting );

				try {
					$phpmailer->From		= get_option( 'max_smtp_sender_field_email', get_bloginfo( 'admin_email' ) );
					$phpmailer->FromName		= get_option( 'max_smtp_sender_field_from', get_bloginfo( 'name' ) );
					$phpmailer->SMTPAuth		= $smtp_setting['smtp_auth'] ? true : false;
					$phpmailer->Port		= (int) $smtp_setting['smtp_port'];
					$phpmailer->SMTPSecure	= $smtp_setting['smtp_secure'];
					$phpmailer->Host		= $smtp_setting['smtp_host'];
					$phpmailer->Username		= $smtp_setting['smtp_user'];
					$phpmailer->Password		= self::maxsmtp_decrypt( $smtp_setting['smtp_password'] );
					$phpmailer->SMTPAutoTLS	= $smtp_setting['smtp_autotls'] ? true : false;
					$phpmailer->SMTPOptions	= [ 'ssl' => [ 'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true ] 	];
					$phpmailer->isSMTP();

					if( $phpmailer->send() ){
						$smtp_setting['smtp_sesssion_day']	= $smtp_setting['smtp_sesssion_day'] + 1;
						$smtp_setting['smtp_sesssion_hour']	= $smtp_setting['smtp_sesssion_hour'] + 1;
						$smtp_setting['smtp_sesssion_second']	= $smtp_setting['smtp_sesssion_second'] + 1;
						update_option( 'max_smtp_set_mail_account', $smtp_setting );
						$phpmailer	= new maxsmtp_hijack_phpmailer();
					}

					wp_cache_delete( 'max_smtp_mail_cache', 'max_smtp_cache' );
				} catch ( \PHPMailer\PHPMailer\Exception $e ) {
					$error_pass		= $e->getMessage();
					$mail_error_data	= wp_cache_get( 'max_smtp_mail_cache', 'max_smtp_cache' );
					$mail_error_data['phpmailer_exception_code'] = $error_pass;

					if( ! wp_cache_get( 'max_smtp_resending', 'max_smtp_cache' ) ){
						$email_queue	= [
										'mail_to'			=> $mail_error_data['to'],
										'mail_subject'		=> $mail_error_data['subject'],
										'mail_message'		=> $mail_error_data['message'],
										'mail_headers'		=> $mail_error_data['headers'],
										'mail_attachments'	=> $mail_error_data['attachments'],
										'mail_failed'		=> current_time( 'mysql' ),
										'mail_status'		=> 'Pending',
										'mail_error'		=> $mail_error_data['phpmailer_exception_code'],
										'mail_body'		=> $phpmailer->Body,
									];

						$email_queue	= apply_filters( 'maxsmtp_filter_email_queue_before_save', $email_queue );

						if( ! empty( $email_queue ) ){
							$wpdb->insert( $wpdb->prefix . 'maxsmtp_queue', $email_queue );
						}

						$email_queue	= '';
					}

					$phpmailer 	= new maxsmtp_hijack_phpmailer( $error_pass, $mail_error_data );
					$error_pass	= '';
					$mail_error_data	= '';

					wp_cache_delete( 'max_smtp_mail_cache', 'max_smtp_cache' );
				}
			}
		}

		private static function maxsmtp_decrypt( $ciphertext ) {
			$password		= defined( 'NONCE_KEY' ) ? NONCE_KEY : AUTH_KEY;
			$ciphertext	= hex2bin( $ciphertext );
			if( !hash_equals( hash_hmac( 'sha256', substr($ciphertext, 48).substr( $ciphertext, 0, 16 ), hash( 'sha256', $password, true ), true ), substr( $ciphertext, 16, 32 ) ) ) return null;
			return openssl_decrypt( substr( $ciphertext, 48 ), "AES-256-CBC", hash( 'sha256', $password, true ), OPENSSL_RAW_DATA, substr( $ciphertext, 0, 16 ) );
		}
	}

	class maxsmtp_hijack_phpmailer {
		public $error;
		public $mail_error_pass;

		public function __construct( $error_pass = false, $mail_error_pass = [] ){
			$this->error		= $error_pass;
			$this->mail_error	= $mail_error_pass;
		}

		public function send(){
			if( ! empty( $this->error ) ){
				require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
				throw new \PHPMailer\PHPMailer\Exception( $this->error );
				do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $this->error, $this->mail_error ) );
				return false;
			} else {
				return true;
			}
		}
	}

	add_action( 'plugins_loaded', [ __NAMESPACE__ . '\Max_SMTP_Mail_Functions', 'maxsmtp_mail_init_actions' ] );
?>
