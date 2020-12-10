<?php
/*
Plugin Name: Max SMTP - Multiple SMTP Accounts
Description: Add multiple SMTP accounts, cycle through multiple SMTP's maximum send limits, and queue failed emails on your WordPress website.
Version: 1.0.9
Author: Effin Studios
Author URI: http://effinstudios.com
License: GPLv2 or later

Copyright 2020 effin studios (email : support@effinstudios.com)
*/
	namespace MAXSMTP;

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	define( 'MAXSMTP_PATH', plugin_dir_path( __FILE__ ) );
	define( 'MAXSMTP_URL', plugin_dir_url(__FILE__) );

	require_once( MAXSMTP_PATH . 'includes/mail-functions.php' );
	require_once( MAXSMTP_PATH . 'includes/menu/extend-wp-admin.php' );
	require_once( MAXSMTP_PATH . 'includes/menu/page-smtp.php' );
	require_once( MAXSMTP_PATH . 'includes/menu/page-queue.php' );
	require_once( MAXSMTP_PATH . 'includes/menu/page-settings.php' );

	class Max_SMTP_Plugin {
		private static $dbver 	= 0.06;

		public static function maxsmtp_init_actions(){
			add_filter( 'cron_schedules', [ __CLASS__, 'maxsmtp_cron_intervals' ] );
			add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ __CLASS__, 'maxsmtp_add_settings_link' ] );
			add_action( 'max_smtp_resend_queue', [ __CLASS__, 'maxsmtp_resend_queue' ], 10, 0 );
			add_action( 'max_smtp_reset_limits', [ __CLASS__, 'maxsmtp_reset_limits' ], 10, 0 );
			add_action( 'admin_enqueue_scripts', [ __CLASS__, 'maxsmtp_plugin_styles' ] );
			add_action( 'admin_notices', [ __CLASS__, 'maxsmtp_admin_notification_handler' ] );
		}

		public static function maxsmtp_setup_plugin(){
			self::maxsmtp_smtps_database();
			self::maxsmtp_queue_database();
			self::maxsmtp_schedule_cron();
		}

		public static function maxsmtp_plugin_styles() {
			wp_register_style( 'max-smtp-style', plugins_url( '/assets/css/style.css', __FILE__ ) );
			wp_enqueue_style( 'max-smtp-style' );
			wp_register_script( 'max-smtp-script', plugins_url( '/assets/js/script.js', __FILE__ ), ['jquery'] );
			wp_enqueue_script( 'max-smtp-script' );
		}

		public static function maxsmtp_add_settings_link( $links ){
			$settings_link = '<a href="admin.php?page=max-smtp-settings">' . __( 'Settings' ) . '</a>';
			array_push( $links, $settings_link );
			return $links;
		}

		private static function maxsmtp_smtps_database(){
			global $wpdb;
			$sql	= "CREATE TABLE " . $wpdb->prefix . "maxsmtp_smtps (
					id bigint(20) NOT NULL AUTO_INCREMENT,
					smtp_name text NOT NULL,
					smtp_host text NOT NULL,
					smtp_port int NOT NULL,
					smtp_secure text,
					smtp_auth BOOLEAN NOT NULL,
					smtp_autotls BOOLEAN NOT NULL,
					smtp_user text NOT NULL,
					smtp_password text,
					smtp_limit_day int NOT NULL DEFAULT 0,
					smtp_limit_hour int NOT NULL DEFAULT 0,
					smtp_limit_second int NOT NULL DEFAULT 0,
					smtp_sesssion_day int DEFAULT 0,
					smtp_sesssion_hour int DEFAULT 0,
					smtp_sesssion_second int DEFAULT 0,
					smtp_time_day datetime DEFAULT CURRENT_TIMESTAMP,
					smtp_time_hour datetime DEFAULT CURRENT_TIMESTAMP,
					smtp_time_second datetime DEFAULT CURRENT_TIMESTAMP,
					smtp_active_time datetime DEFAULT CURRENT_TIMESTAMP,
					smtp_active BOOLEAN NOT NULL DEFAULT true,
					PRIMARY KEY (id)
				) " . $wpdb->get_charset_collate() . ";";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			add_option( 'max_smtp_db_version', self::$dbver );
		}

		private static function maxsmtp_queue_database(){
			global $wpdb;
			$sql	= "CREATE TABLE " . $wpdb->prefix . "maxsmtp_queue (
					id bigint(20) NOT NULL AUTO_INCREMENT,
					mail_to text,
					mail_subject text,
					mail_message text,
					mail_headers text,
					mail_attachments text,
					mail_failed datetime DEFAULT CURRENT_TIMESTAMP,
					mail_status text,
					mail_error text,
					mail_body text,
					send_status BOOLEAN DEFAULT false,
					PRIMARY KEY  (id)
				) " . $wpdb->get_charset_collate() . ";";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			add_option( 'max_smtp_db_version', self::$dbver );
		}

		public static function maxsmtp_deactivate_plugin(){
			if( wp_next_scheduled( 'max_smtp_resend_queue' ) ) {
				wp_clear_scheduled_hook( 'max_smtp_resend_queue' );
			}
			if( wp_next_scheduled( 'max_smtp_reset_limits' ) ) {
				wp_clear_scheduled_hook( 'max_smtp_reset_limits' );
			}
		}

		public static function maxsmtp_uninstall_plugin(){
			$delete_queue_table	= get_option( 'max_smtp_delete_queue_table' );
			$delete_smtp_table	= get_option( 'max_smtp_delete_smtp_table' );
			$delete_option		= get_option( 'max_smtp_delete_options' );
			if( $delete_queue_table || $delete_option ){
				global $wpdb;
			}
			if( $delete_queue_table ){
				$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'maxsmtp_queue' );
			}
			if( $delete_smtp_table ){
				$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'maxsmtp_smtps' );
			}
			if( $delete_option ){
				$options	= [
							'max_smtp_db_version',
							'max_smtp_sender_field_from',
							'max_smtp_sender_field_email',
							'max_smtp_cron_field_interval',
							'max_smtp_cron_field_reset_time',
							'max_smtp_cron_last_resend',
							'max_smtp_cron_last_reset',
							'max_smtp_pause_status',
							'max_smtp_pause_message',
							'max_smtp_queue_limit',
							'max_smtp_delete_options',
							'max_smtp_delete_smtp_table',
							'max_smtp_delete_queue_table',
							'max_smtp_set_mail_account'
						];
				foreach( $options as $option ){
					delete_option( $option );
				}
			}
		}

		public static function maxsmtp_cron_intervals( $schedules ){
			$schedules['maxsmtp_10']	= array(
									'interval' => 600,
									'display' => __( '10 Minutes', 'max-smtp' )
								);
			$schedules['maxsmtp_20']	= array(
									'interval' => 1200,
									'display' => __( '20 Minutes', 'max-smtp' )
								);
			$schedules['maxsmtp_30']	= array(
									'interval' => 1800,
									'display' => __( '30 Minutes', 'max-smtp' )
								);
			$schedules['maxsmtp_40']	= array(
									'interval' => 2400,
									'display' => __( '40 Minutes', 'max-smtp' )
								);
			$schedules['maxsmtp_50']	= array(
									'interval' => 3000,
									'display' => __( '50 Minutes', 'max-smtp' )
								);
			$schedules['maxsmtp_60']	= array(
									'interval' => 3600,
									'display' => __( '60 Minutes', 'max-smtp' )
								);
			return $schedules;
		}

		private static function maxsmtp_schedule_cron() {
			if( !wp_next_scheduled( 'max_smtp_resend_queue' ) ){
				wp_schedule_event( time(), get_option( 'max_smtp_cron_field_interval', 'maxsmtp_60' ), 'max_smtp_resend_queue' );
			}

			if( !wp_next_scheduled( 'max_smtp_reset_limits' ) ) {
				$reset_time	= strtotime( date( "Y-m-d" ) . ' ' . get_option( 'max_smtp_cron_field_reset_time', '08:00' ) );
				$gmt			= (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
				$reset_time	= $reset_time - $gmt;
				wp_schedule_event( $reset_time, 'daily', 'max_smtp_reset_limits' );
			}
		}

		public static function maxsmtp_clear_cron(){
			if( wp_next_scheduled( 'max_smtp_resend_queue' ) ) {
				wp_clear_scheduled_hook( 'max_smtp_resend_queue' );
			}
			if( wp_next_scheduled( 'max_smtp_reset_limits' ) ) {
				wp_clear_scheduled_hook( 'max_smtp_reset_limits' );
			}
			self::maxsmtp_schedule_cron();
		}

		public static function maxsmtp_update_queue_send_limits(){
			global $wpdb;
			$smtp_array	= $wpdb->get_results( 'SELECT smtp_limit_second FROM ' . $wpdb->prefix . 'maxsmtp_smtps WHERE smtp_active = 1', ARRAY_A );
			if( !empty( $smtp_array ) ){
				$updated_limit	= 0;
				foreach( $smtp_array as $smtp ){
					$updated_limit = $updated_limit + $smtp['smtp_limit_second'];
				}
				if( $updated_limit > 0 ){
					update_option( 'max_smtp_queue_limit', $updated_limit );
				}
			}
		}

		public static function maxsmtp_resend_queue( $where = 'send_status = 0' ){
			$email_sent	= 0;
			$pause_status	= get_option( 'max_smtp_pause_status', false );
			$queuq_limit	= get_option( 'max_smtp_queue_limit', 0 );
			if( ! $pause_status ){
				global $wpdb;
				$mails	= $wpdb->get_results( 'SELECT id, mail_to, mail_subject, mail_message, mail_headers, mail_attachments FROM ' . $wpdb->base_prefix . 'maxsmtp_queue WHERE ' . $where . ' LIMIT ' . $queuq_limit, ARRAY_A );
				if( !empty( $mails ) ){
					if( wp_cache_set( 'max_smtp_resending', true, 'max_smtp_cache', 600 ) ){
						$sent_mail	= [];
						foreach( $mails as $mail ){
							if( wp_cache_get( 'max_smtp_pause', 'max_smtp_cache' ) ){
								break;
							}
							$sent	= wp_mail( $mail['mail_to'], $mail['mail_subject'], $mail['mail_message'], $mail['mail_headers'], $mail['mail_attachments'] );
							if( $sent ){
								$sent_mail[]	= $mail['id'];
								$email_sent++;
							}
						}
						$mails	= null;
						if( !empty( $sent_mail ) ){
							$set		= @implode( ', ', [
										'mail_failed = "' . current_time( 'mysql' ) . '"',
										'mail_error = ""',
										'mail_status = "Sent"',
										'send_status = 1'
									] );
							$sent_mail	= implode( ' OR ', $sent_mail );
							$wpdb->query( 'UPDATE ' . $wpdb->prefix . 'maxsmtp_queue SET ' . $set . ' WHERE id = ' . $sent_mail );
						}
						wp_cache_delete( 'max_smtp_resending', 'max_smtp_cache' );
						update_option( 'max_smtp_cron_last_resend', date( 'Y-m-d h:i:s A', strtotime( current_time( 'mysql' ) ) ) );
					}
					$mails	= null;
				}
			}
			return $email_sent;
		}

		public static function maxsmtp_reset_limits() {
			global $wpdb;
			$smtp_settings	= $wpdb->get_results( 'SELECT id FROM ' . $wpdb->base_prefix . 'maxsmtp_smtps', ARRAY_A );
			if( is_array( $smtp_settings ) && !empty( $smtp_settings ) ){
				$reset_smtp	= [];
				foreach( $smtp_settings as $smtp_setting ){
					$reset_smtp[]	= $smtp_setting['id'];
				}
				$smtp_settings	= null;
				if( !empty( $reset_smtp ) ){
					$set		= @implode( ', ', [
								'smtp_sesssion_day = 0',
								'smtp_sesssion_hour = 0',
								'smtp_sesssion_second = 0',
								'smtp_time_day = "' . current_time( 'mysql' ) . '"',
								'smtp_time_hour = "' . current_time( 'mysql' ) . '"',
								'smtp_time_second = "' . current_time( 'mysql' ) . '"'
							] );
					$wpdb->query( 'UPDATE ' . $wpdb->prefix . 'maxsmtp_smtps SET ' . $set . ' WHERE id = ' . implode( ' OR ', $reset_smtp ) );
					update_option( 'max_smtp_cron_last_reset', date( 'Y-m-d h:i:s A', strtotime( current_time( 'mysql' ) ) ) );
				}
			}
			update_option( 'max_smtp_pause_status', false );
		}

		public static function maxsmtp_admin_notification( $message, $type ){
			if( !is_admin() ){
				return false;
			}

			if( !in_array( $type, array( 'error', 'info', 'success', 'warning' ) ) ){
				return false;
			}

			$transientName	= 'maxsmtp_admin_notice_'.get_current_user_id();
			$notifications	= get_transient( $transientName );

			if( !$notifications ){
				$notifications = [];
			}

			$notifications[]	= [
								'message'	=> $message,
								'type'	=> $type
							];
			set_transient( $transientName, $notifications );
		}

		public static function maxsmtp_admin_notification_handler(){

			if( !is_admin() ){
				return;
			}

			$transientName	= 'maxsmtp_admin_notice_'.get_current_user_id();
			$notifications	= get_transient( $transientName );

			if( $notifications ){
				foreach( $notifications as $notification ){
					echo '<div class="notice notice-custom notice-' . $notification['type'] . ' is-dismissible"><p>' . esc_html( $notification['message'] ) . '</p></div>';
				}
			}

			delete_transient( $transientName );

		}
	}

	register_activation_hook( __FILE__, [ __NAMESPACE__ . '\Max_SMTP_Plugin', 'maxsmtp_setup_plugin' ] );
	register_deactivation_hook( __FILE__, [ __NAMESPACE__ . '\Max_SMTP_Plugin', 'maxsmtp_deactivate_plugin' ] );
	register_uninstall_hook( __FILE__, [ __NAMESPACE__ . '\Max_SMTP_Plugin', 'maxsmtp_uninstall_plugin' ] );
	add_action( 'plugins_loaded', [ __NAMESPACE__ . '\Max_SMTP_Plugin', 'maxsmtp_init_actions' ] );
?>