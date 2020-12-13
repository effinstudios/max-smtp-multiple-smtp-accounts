<?php

namespace MAXSMTP;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Max_SMTP_Debug_Page {
	static $instance;

	public function __construct(){
		add_action( 'admin_menu', [ $this, 'maxsmtp_plugin_sub_menu' ] );
	}

	public function maxsmtp_plugin_sub_menu(){
		add_submenu_page(
			'max-smtp',
			'Max SMTP - Debug',
			'Debug',
			'manage_options',
			'max-smtp-debug',
			[ $this, 'maxsmtp_plugin_settings_page' ]
		);
	}

	public function maxsmtp_plugin_settings_page() {
		echo '<div class="wrap">';
		echo '<h1>Debug</h1>';
		echo '<code style="white-space:pre-wrap;">';
		/** Test Area Start **/

			$smtp_setting	= get_option( 'max_smtp_set_mail_account' );
			//unset( $smtp_setting['smtp_steps'] );
			//update_option( 'max_smtp_set_mail_account', $smtp_setting );
			$smtp_setting['smtp_password'] = Max_SMTP_Mail_Functions::maxsmtp_decrypt( $smtp_setting['smtp_password'] );
			echo print_r( $smtp_setting, true );

			if( true ){
				foreach( _get_cron_array() as $time => $array ){
					$var = date( 'Y-m-d h:i:s A', $time ) . ': <code style="white-space: pre-wrap;">' . print_r( $array, true ) . '</code><br>';
					if( isset( $array['max_smtp_resend_queue'] ) || isset( $array['max_smtp_reset_limits'] ) ){
						//echo $var;
					}
				}
			}

		/** Test Area End **/
		echo '</code>';
		echo '</div>';
	}

	public static function maxsmtp_get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
add_action( 'plugins_loaded', function(){ Max_SMTP_Debug_Page::maxsmtp_get_instance(); });
?>