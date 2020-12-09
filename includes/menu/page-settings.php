<?php
namespace MAXSMTP;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Max_SMTP_Settings_Page {
	static $instance;

	public function __construct(){
		add_action( 'admin_menu', [ __CLASS__, 'maxsmtp_plugin_sub_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'maxsmtp_setup_sections' ] );
		add_action( 'admin_init', [ __CLASS__, 'maxsmtp_setup_fields' ] );
	}

	public static function maxsmtp_plugin_sub_menu(){
		add_submenu_page(
			'max-smtp',
			'Max SMTP - ' . __( 'Settings', 'max-smtp' ),
			__( 'Settings', 'max-smtp' ),
			'manage_options',
			'max-smtp-settings',
			[ __CLASS__, 'maxsmtp_plugin_settings_page' ]
		);
	}

	public static function maxsmtp_setup_sections() {
		add_settings_section( 'sender_section', __( 'Email Sender', 'max-smtp' ), [ __CLASS__, 'maxsmtp_section_callback' ], 'max_smtp_fields' );
		add_settings_section( 'cron_section', __( 'Cron Actions', 'max-smtp' ), [ __CLASS__, 'maxsmtp_section_callback' ], 'max_smtp_fields' );
		add_settings_section( 'uninstall_section', __( 'Uninstall Options', 'max-smtp' ), [ __CLASS__, 'maxsmtp_section_callback' ], 'max_smtp_fields' );
	}

	public static function maxsmtp_section_callback( $arguments ){
		switch( $arguments['id'] ){
			case 'sender_section':
				echo '<p class="description">' . __( 'Override the default email from name and address.', 'max-smtp' ) . '</p>';
				break;
			case 'cron_section':
				echo '<p class="description">' . __( 'Automated action intervals for qued emails and daily limit reset times.', 'max-smtp' ) . '</p>';
				break;
		}
	}

	public static function maxsmtp_setup_fields() {
		$fields	= [
					[
						'uid'			=> 'max_smtp_sender_field_from',
						'label'		=> __( 'Sender Name', 'max-smtp' ),
						'section'		=> 'sender_section',
						'type' 		=> 'text',
						'options'		=> false,
						'placeholder'	=> get_bloginfo( 'name' ),
						'helper'		=> '',
						'supplemental'	=> '',
						'default'		=> get_bloginfo( 'name' )
					],
					[
						'uid'			=> 'max_smtp_sender_field_email',
						'label'		=> __( 'Email Address', 'max-smtp' ),
						'section'		=> 'sender_section',
						'type' 		=> 'email',
						'options'		=> false,
						'placeholder'	=> get_bloginfo( 'admin_email' ),
						'helper'		=> '',
						'supplemental'	=> '',
						'default'		=> get_bloginfo( 'admin_email' )
					],
					[
						'uid'			=> 'max_smtp_cron_field_interval',
						'label'		=> __( 'Email Queue Interval', 'max-smtp' ),
						'section'		=> 'cron_section',
						'type' 		=> 'select',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> __( 'Email queue send intervals.', 'max-smtp' ),
						'default'		=> 'maxsmtp_60'
					],
					[
						'uid'			=> 'max_smtp_queue_limit',
						'label'		=> __( 'Queue Send Limit', 'max-smtp' ),
						'section'		=> 'cron_section',
						'type' 		=> 'display',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> __( 'Emails Per Send Queue', 'max-smtp' ),
						'supplemental'	=> __( 'Total per second limit of your SMTP Accounts.', 'max-smtp' ),
						'default'		=> 0
					],
					[
						'uid'			=> 'max_smtp_cron_field_reset_time',
						'label'		=> __( 'Daily Reset Time', 'max-smtp' ),
						'section'		=> 'cron_section',
						'type' 		=> 'time',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> defined( 'DISABLE_WP_CRON' ) ? '<span class="warning"><b>' . __( 'WP CRON is disabled', 'max-smtp' ) . '</b>' . __( ', automated schedules might run late or not at all because of this.', 'max-smtp' ) . '</span>' : '',
						'default'		=> '08:00'
					],
					[
						'uid'			=> 'max_smtp_cron_last_resend',
						'label'		=> __( 'Previous Queue Resend', 'max-smtp' ),
						'section'		=> 'cron_section',
						'type' 		=> 'display',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> '',
						'default'		=> 'Pending'
					],
					[
						'uid'			=> 'max_smtp_cron_last_reset',
						'label'		=> __( 'Previous Limit Reset', 'max-smtp' ),
						'section'		=> 'cron_section',
						'type' 		=> 'display',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> '',
						'default'		=> 'Pending'
					],
					[
						'uid'			=> 'max_smtp_pause_status',
						'label'		=> '',
						'section'		=> '',
						'type' 		=> '',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> '',
						'default'		=> false
					],
					[
						'uid'			=> 'max_smtp_pause_message',
						'label'		=> '',
						'section'		=> '',
						'type' 		=> '',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> '',
						'default'		=> ''
					],
					[
						'uid'			=> 'max_smtp_delete_options',
						'label'		=> __( 'Saved Settings', 'max-smtp' ),
						'section'		=> 'uninstall_section',
						'type' 		=> 'checkbox',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> __( 'Delete saved settings when plugin is uninstalled.', 'max-smtp' ),
						'supplemental'	=> '',
						'default'		=> false
					],
					[
						'uid'			=> 'max_smtp_delete_smtp_table',
						'label'		=> __( 'SMTP Accounts', 'max-smtp' ),
						'section'		=> 'uninstall_section',
						'type' 		=> 'checkbox',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> __( 'Delete "SMTP Accounts" when plugin is uninstalled.', 'max-smtp' ),
						'supplemental'	=> '',
						'default'		=> false
					],
					[
						'uid'			=> 'max_smtp_delete_queue_table',
						'label'		=> __( 'Email Queue', 'max-smtp' ),
						'section'		=> 'uninstall_section',
						'type' 		=> 'checkbox',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> __( 'Delete "Email Queue" when plugin is uninstalled.', 'max-smtp' ),
						'supplemental'	=> '',
						'default'		=> false
					]
				];
		foreach( $fields as $field ){
			add_settings_field( $field['uid'], $field['label'], [ __CLASS__, 'maxsmtp_field_callback' ], 'max_smtp_fields', $field['section'], $field );
			register_setting( 'max_smtp_fields', $field['uid'] );
			if( empty( get_option( $field['uid'] ) ) && !empty( $field['default'] ) ){
				update_option( $field['uid'], $field['default'] );
			}
		}
	}

	public static function maxsmtp_field_callback( $arguments ) {
		$value	= get_option( $arguments['uid'] );
		if( ! $value ) {
			$value	= $arguments['default'];
		}

		switch( $arguments['type'] ){
			case 'text':
			case 'email':
			case 'time':
				printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], esc_attr( $value ) );
				break;
			case 'checkbox':
				$checked = $value === "true" ? 'checked' : '';
				printf( '<input name="%1$s" id="%1$s" type="%2$s" value="true" %3$s />', $arguments['uid'], $arguments['type'], $checked );
				break;
			case 'select':
				$schedules	= Max_SMTP_Plugin::maxsmtp_cron_intervals( [] );
				printf( '<select name="%1$s" id="%1$s">', $arguments['uid'] );
				foreach( $schedules as $schedule => $array ):
					$selected	= $value == $schedule ? 'selected' : '';
					printf( '<option value="%1$s" %2$s>%3$s</option>', $schedule, $selected, $array['display'] );
				endforeach;
				print( '</select>' );
				break;
			case 'display':
				printf( '<span class="%1$s">%2$s</span>', $arguments['uid'], esc_html( $value ) );
				break;
		}

		if( $helper = $arguments['helper'] ){
			printf( '<label class="helper"> %s</label>', $helper );
		}

		if( $supplimental = $arguments['supplemental'] ){
			printf( '<p class="description">%s</p>', $supplimental );
		}
	}

	public static function maxsmtp_handle_form() {
		if( ! isset( $_POST['max_smtp_update_form'] ) || ! wp_verify_nonce( $_POST['max_smtp_update_form'], 'max_smtp_update' ) ){
			Max_SMTP_Plugin::maxsmtp_admin_notification( 'Somthing terrible happend... Your settings was not saved, please try again.', 'error' );
			wp_redirect( esc_url_raw( add_query_arg( [] ) ) );
		} else {
			$option_fields	= isset( $_POST ) && !empty( $_POST ) ? $_POST : null;
			if( isset( $_POST ) && !empty( $_POST ) && is_array( $_POST ) ){
				$options	= $_POST;
				foreach( [ 'max_smtp_delete_options', 'max_smtp_delete_smtp_table', 'max_smtp_delete_queue_table' ] as $maybeaddkey ){
					array_key_exists( $maybeaddkey, $options ) ? null : $options[ $maybeaddkey ] = false;
				}
				foreach( $options as $opt_key => $opt_val ){
					$opt_val_confirmed	= '';
					switch( $opt_key ){
						case 'max_smtp_sender_field_from':
							$opt_val_confirmed = sanitize_text_field( $opt_val );
							break;
						case 'max_smtp_sender_field_email':
							$opt_val_confirmed = sanitize_email( $opt_val );
							break;
						case 'max_smtp_cron_field_interval':
							$opt_val_confirmed = in_array( $opt_val, [ 'maxsmtp_10', 'maxsmtp_20', 'maxsmtp_30', 'maxsmtp_40', 'maxsmtp_50', 'maxsmtp_60', ] ) ? $opt_val : null;
							break;
						case 'max_smtp_cron_field_reset_time':
							$opt_val_confirmed = preg_match( '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $opt_val ) ? $opt_val : null;;
							break;
						case 'max_smtp_delete_options':
						case 'max_smtp_delete_smtp_table':
						case 'max_smtp_delete_queue_table':
							$opt_val_confirmed = $opt_val === "true" ? "true" : false;
							break;
					}
					if( !empty( $opt_val_confirmed ) ){
						update_option( $opt_key, $opt_val_confirmed );
					}
				}
			}
			Max_SMTP_Plugin::maxsmtp_clear_cron();
			Max_SMTP_Plugin::maxsmtp_update_queue_send_limits();
			Max_SMTP_Plugin::maxsmtp_admin_notification( __( 'Settings saved.', 'max-smtp' ), 'success' );
			wp_redirect( esc_url_raw( add_query_arg( [] ) ) );
		}
	}

	public static function maxsmtp_plugin_settings_page() {
		if( isset( $_POST['updated'] ) && $_POST['updated'] === 'true'  ){
			Max_SMTP_Settings_Page::maxsmtp_handle_form();
		}
		?>
			<div class="wrap max-smtp max-smtp-settings">
				<h1><img class="max-smtp-logo" src="<?php echo esc_url( MAXSMTP_URL . '/assets/images/logo.png' ); ?>" alt="Max SMTP"> <?php _e( 'Settings', 'max-smtp' ); ?></h1>
				<form method="post">
					<input type="hidden" name="updated" value="true" />
					<?php wp_nonce_field( 'max_smtp_update', 'max_smtp_update_form' ); ?>
					<?php
						settings_fields( 'max_smtp_fields' );
						do_settings_sections( 'max_smtp_fields' );
						submit_button();
					?>
				</form>
			</div>
		<?php
	}

	public static function maxsmtp_get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

add_action( 'plugins_loaded', function(){ Max_SMTP_Settings_Page::maxsmtp_get_instance(); });
?>
