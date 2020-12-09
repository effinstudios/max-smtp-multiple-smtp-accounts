<?php
namespace MAXSMTP;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Max_SMTP_Accounts_Page {
	static $instance;
	public $items_obj;

	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'maxsmtp_set_screen' ], 10, 3 );
		add_action( 'admin_menu', [ $this, 'maxsmtp_plugin_menu' ] );
		add_action( 'admin_menu', [ $this, 'maxsmtp_plugin_sub_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'maxsmtp_setup_sections' ] );
		add_action( 'admin_init', [ __CLASS__, 'maxsmtp_setup_fields' ] );
	}

	public static function maxsmtp_set_screen( $status, $option, $value ) {
		return $value;
	}

	public function maxsmtp_plugin_menu() {
		$hook = add_menu_page(
			'Max SMTP',
			'Max SMTP',
			'manage_options',
			'max-smtp',
			[ $this, 'maxsmtp_plugin_settings_page' ],
			self::maxsmtp_get_svg_icon(),
			81
		);
		add_action( "load-$hook", [ $this, 'maxsmtp_screen_option' ] );
	}

	public static function maxsmtp_get_svg_icon() {
		$svg	= '<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="120px" height="120px" viewBox="0 0 120 120" style="enable-background:new 0 0 120 120;" xml:space="preserve">
			<g>
				<path d="M110,57.033v14.565l1.862-0.678c0.753-0.273,1.354-0.825,1.693-1.551c0.338-0.727,0.374-1.541,0.1-2.294L110,57.033z"/>
				<path d="M6.345,52.925L10,62.969V48.402L8.138,49.08C6.583,49.646,5.779,51.371,6.345,52.925z"/>
				<path d="M118.536,15.231c-1.06-1.06-2.419-1.601-3.715-1.601c-1.093,0-2.141,0.385-2.925,1.169l-12.659,12.66l-2.682-7.368
				c-0.43-1.181-1.563-1.975-2.819-1.975c-0.348,0-0.693,0.062-1.025,0.183L60.559,30h36.135l-2,2H25.305L8.104,14.799
				C7.319,14.015,6.272,13.63,5.18,13.63c-1.296,0-2.656,0.542-3.715,1.601c-1.953,1.953-2.146,4.925-0.432,6.639l11.49,11.489
				C12.204,33.833,12,34.388,12,35v50c0,1.654,1.346,3,3,3h5.494l-4.243,11.658c-0.944,2.595,0.394,5.464,2.988,6.408
				c0.564,0.205,1.143,0.303,1.71,0.303c2.042,0,3.959-1.26,4.699-3.291l0.441-1.212c0.059,0.004,0.115,0.019,0.175,0.019
				c0.348,0,0.693-0.062,1.025-0.182L59.443,90H30.409l0.728-2h57.727l5.488,15.078c0.739,2.031,2.656,3.292,4.698,3.292
				c0.568,0,1.146-0.098,1.711-0.304c2.595-0.944,3.933-3.813,2.988-6.408L99.506,88H105c1.654,0,3-1.346,3-3V35
				c0-0.613-0.203-1.168-0.521-1.642l11.488-11.489C120.682,20.156,120.489,17.184,118.536,15.231z M117.553,20.456L85.639,52.371
				l-0.901,0.901l0.437,1.197l16.695,45.874c0.566,1.555-0.238,3.279-1.793,3.846c-0.333,0.121-0.678,0.183-1.026,0.183
				c-1.256,0-2.389-0.795-2.819-1.976L81.129,60.903l-1.073-2.95l-2.22,2.22L62.122,75.888L61.69,76.32
				c-0.465,0.465-1.077,0.563-1.508,0.564h-0.078l-0.059,0.004l-0.051,0.002l-0.059-0.002l-0.059-0.004h-0.059
				c-0.431-0.001-1.043-0.1-1.508-0.565l-0.336-0.336l-0.001-0.001l-0.097-0.097L42.164,60.173l-2.22-2.22l-1.074,2.95L23.77,102.394
				c-0.43,1.182-1.563,1.976-2.819,1.976c-0.349,0-0.694-0.062-1.026-0.182c-1.554-0.566-2.359-2.291-1.793-3.846L34.828,54.47
				l0.436-1.197l-0.901-0.901L2.447,20.456c-0.932-0.932-0.738-2.641,0.432-3.811C3.524,16,4.363,15.63,5.18,15.63
				c0.422,0,1.028,0.101,1.51,0.583l51.896,51.896L60,69.523l1.414-1.414l51.896-51.896c0.482-0.482,1.088-0.583,1.511-0.583
				c0.816,0,1.655,0.37,2.301,1.015c0.601,0.601,0.96,1.356,1.01,2.125C118.174,19.441,117.969,20.04,117.553,20.456z"/>
			</g>
			</svg>';
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}
	public function maxsmtp_plugin_sub_menu() {
		add_submenu_page(
			'max-smtp',
			'Max SMTP - ' . __( 'SMTP Accounts', 'max-smtp' ),
			__( 'SMTP Accounts', 'max-smtp' ),
			'manage_options',
			'max-smtp',
			[ $this, 'maxsmtp_plugin_settings_page' ]
		);
	}

	public function maxsmtp_screen_option() {
		$option = 'per_page';
		$args   = [
			'label'   => _( 'SMTP Accounts', 'max-smtp' ),
			'default' => 20,
			'option'  => 'items_per_page'
		];
		add_screen_option( $option, $args );
		$this->items_obj = new \Max_SMTP_Accounts_Page_Ex();
	}

	public static function maxsmtp_setup_sections() {
		$what_heading	= isset( $_GET['action'] ) && $_GET['action'] === 'edit' ? __( 'Edit', 'max-smtp' ) : __( 'Add', 'max-smtp' );
		add_settings_section( 'smtp_smtp_action', $what_heading . __( ' SMTP Account', 'max-smtp' ), [ __CLASS__, 'maxsmtp_section_callback' ], 'max_smtp_account' );
	}

	public static function maxsmtp_section_callback( $arguments ){
		switch( $arguments['id'] ){
			case 'smtp_smtp_action':
				$what_heading	= isset( $_GET['action'] ) && $_GET['action'] === 'edit' ? __( 'Edit', 'max-smtp' ) : __( 'Add', 'max-smtp' );
				echo '<p class="description">' . $what_heading . __( ' your SMTP account settings below.', 'max-smtp' ) . '</p>';
				break;
		}
	}

	public static function maxsmtp_setup_fields() {
		global $wpdb;

		if( isset( $_GET['action'] ) && ( $_GET['action'] === 'edit' || $_GET['action'] === 'add' ) ){
			if( isset( $_GET['id'] ) && is_int( $this_id = absint( $_GET['id'] ) ) ){
				$dbdata	= $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'maxsmtp_smtps WHERE id = ' . $this_id, ARRAY_A );
				empty( $dbdata[0] ) ? null : $input = $dbdata[0];
			} else if( isset( $_POST ) && is_array( $_POST ) ){
				foreach( [ 'smtp_name', 'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_auth', 'smtp_autotls', 'smtp_user', 'smtp_limit_day', 'smtp_limit_hour', 'smtp_limit_second', 'id' ] as $input_key ){
					isset( $_POST[ $input_key ] ) ? $input[ $input_key ] = esc_attr( $_POST[ $input_key ] ) : null;
				}
			}
		}

		$what_heading	= isset( $_GET['action'] ) && $_GET['action'] === 'edit' ? __( 'Edit', 'max-smtp' ) : __( 'Add', 'max-smtp' );

		$fields	= [
					[
						'uid'			=> 'smtp_name',
						'label'		=> __( 'Server Name', 'max-smtp' ),
						'section'		=> 'smtp_smtp_action',
						'type' 		=> 'text',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> __( 'You can type in any name here, this is used primarily to easily differentiate an account.', 'max-smtp' ),
						'default'		=> isset( $input['smtp_name'] ) ? $input['smtp_name'] : '',
						'required'		=> 'required'
					],
					[
						'uid'			=> 'smtp_host',
						'label'		=> __( 'Host', 'max-smtp' ),
						'section'		=> 'smtp_smtp_action',
						'type' 		=> 'text',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> $what_heading . __( ' your SMTP host, for example <i>smtp.google.com</i>.', 'max-smtp' ),
						'default'		=> isset( $input['smtp_host'] ) ? $input['smtp_host'] : '',
						'required'		=> 'required'
					],
					[
						'uid'			=> 'smtp_port',
						'label'		=> __( 'Port', 'max-smtp' ),
						'section'		=> 'smtp_smtp_action',
						'type' 		=> 'number',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> $what_heading . __( ' your SMTP Port, for example the most common ports are <i>25</i>, <i>465</i>, or <i>587</i>.', 'max-smtp' ),
						'default'		=> isset( $input['smtp_port'] ) ? $input['smtp_port'] : '',
						'required'		=> 'required'
					],
					[
						'uid'			=> 'smtp_secure',
						'label'		=> __( 'Security Type', 'max-smtp' ),
						'section'		=> 'smtp_smtp_action',
						'type' 		=> 'select',
						'options'		=> [ '' => 'None', 'ssl' => 'SSL', 'tls' => 'TLS/STARTTLS' ],
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> $what_heading . __( ' your SMTP security type.', 'max-smtp' ),
						'default'		=> isset( $input['smtp_secure'] ) ? $input['smtp_secure'] : '',
						'required'		=> ''
					],
					[
						'uid'			=> 'smtp_auth',
						'label'		=> __( 'Authentication', 'max-smtp' ),
						'section'		=> 'smtp_smtp_action',
						'type' 		=> 'select',
						'options'		=> [ 1 => 'Yes', 0 => 'No' ],
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> '',
						'default'		=> isset( $input['smtp_auth'] ) ? $input['smtp_auth'] : '',
						'required'		=> ''
					],
					[
						'uid'			=> 'smtp_autotls',
						'label'		=> __( 'AutoTLS', 'max-smtp' ),
						'section'		=> 'smtp_smtp_action',
						'type' 		=> 'select',
						'options'		=> [ 1 => 'Yes', 0 => 'No' ],
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> '',
						'default'		=> isset( $input['smtp_autotls'] ) ? $input['smtp_autotls'] : '',
						'required'		=> ''
					],
					[
						'uid'			=> 'smtp_user',
						'label'		=> __( 'User Name', 'max-smtp' ),
						'section'		=> 'smtp_smtp_action',
						'type' 		=> 'text',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> '',
						'default'		=> isset( $input['smtp_user'] ) ? $input['smtp_user'] : '',
						'required'		=> 'required'
					],
					[
						'uid'			=> 'smtp_password',
						'label'		=> __( 'Password', 'max-smtp' ),
						'section'		=> 'smtp_smtp_action',
						'type' 		=> 'password',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> '',
						'default'		=> '',
						'required'		=> ''
					],
					[
						'uid'			=> 'smtp_limit_day',
						'label'		=> __( 'Daily Limit', 'max-smtp' ),
						'section'		=> 'smtp_smtp_action',
						'type' 		=> 'number',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> $what_heading . __( ' your SMTP email sending daily limit.', 'max-smtp' ),
						'default'		=> isset( $input['smtp_limit_day'] ) ? $input['smtp_limit_day'] : '',
						'required'		=> 'required'
					],
					[
						'uid'			=> 'smtp_limit_hour',
						'label'		=> __( 'Limit Per Hour', 'max-smtp' ),
						'section'		=> 'smtp_smtp_action',
						'type' 		=> 'number',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> $what_heading . __( ' your SMTP sent emails per hour limit.', 'max-smtp' ),
						'default'		=> isset( $input['smtp_limit_hour'] ) ? $input['smtp_limit_hour'] : '',
						'required'		=> 'required'
					],
					[
						'uid'			=> 'smtp_limit_second',
						'label'		=> __( 'Limit Per Second', 'max-smtp' ),
						'section'		=> 'smtp_smtp_action',
						'type' 		=> 'number',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> $what_heading . __( ' your SMTP sent emails per second limit.', 'max-smtp' ),
						'default'		=> isset( $input['smtp_limit_second'] ) ? $input['smtp_limit_second'] : '',
						'required'		=> 'required'
					],
					[
						'uid'			=> 'queu_limit_reference',
						'label'		=> '',
						'section'		=> 'smtp_smtp_action',
						'type' 		=> 'hidden',
						'class' 		=> 'hidden',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> '',
						'default'		=> isset( $input['smtp_limit_second'] ) ? $input['smtp_limit_second'] : '',
						'required'		=> ''
					],
					[
						'uid'			=> 'id',
						'label'		=> '',
						'section'		=> 'smtp_smtp_action',
						'type' 		=> 'hidden',
						'class' 		=> 'hidden',
						'options'		=> false,
						'placeholder'	=> '',
						'helper'		=> '',
						'supplemental'	=> '',
						'default'		=> isset( $input['id'] ) ? $input['id'] : '',
						'required'		=> ''
					],
				];
		foreach( $fields as $field ){
			add_settings_field( $field['uid'], $field['label'], [ __CLASS__, 'maxsmtp_field_callback' ], 'max_smtp_account', $field['section'], $field );
		}
	}

	public static function maxsmtp_field_callback( $arguments ) {
		switch( $arguments['type'] ){
			case 'text':
			case 'number':
				printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" %5$s />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], esc_attr( $arguments['default'] ), $arguments['required'] );
				break;
			case 'password':
				print( '<span class="maxsmtp-pass-wrapper">' );
				printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" autocomplete="off" %5$s />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], esc_attr( $arguments['default'] ), $arguments['required'] );
				print( ' <button type="button" class="button wp-hide-pw hide-if-no-js"><span class="dashicons dashicons-visibility"></span></button>' );
				print( '</span>' );
				break;
			case 'select':
				printf( '<select name="%1$s" id="%1$s" %2$s>', $arguments['uid'], $arguments['required'] );
				foreach( $arguments['options'] as $opkey => $opval ):
					$selected	= $arguments['default'] == $opkey ? 'selected' : '';
					printf( '<option value="%1$s" %2$s>%3$s</option>', $opkey, $selected, $opval );
				endforeach;
				print( '</select>' );
				break;
			case 'hidden':
				printf( '<input name="%1$s" type="%2$s" value="%3$s" />', $arguments['uid'], $arguments['type'], esc_attr( $arguments['default'] ) );
				break;
		}

		if( $helper = $arguments['helper'] ){
			printf( '<label class="helper"> %s</label>', $helper );
		}

		if( $supplimental = $arguments['supplemental'] ){
			printf( '<p class="description">%s</p>', $supplimental );
		}
	}

	public function maxsmtp_plugin_settings_page() {
		global $wpdb;
		$status	= get_option( 'max_smtp_pause_status' );

		?>
			<div class="wrap max-smtp max-smtp-accounts">
				<h1><img class="max-smtp-logo" src="<?php echo esc_url( MAXSMTP_URL . '/assets/images/logo.png' ); ?>" alt="Max SMTP"> <?php _e( 'SMTP Accounts', 'max-smtp' ); ?></h1>
				<?php if( $status ): ?>
					<div id="message" class="error notice notice-warning">
						<p><?php _e( 'Max SMTP: Sending emails via SMTP is currently paused.', 'max-smtp' ); ?> <?php esc_html_e( get_option( 'max_smtp_pause_message' ) ); ?></p>
					</div>
				<?php endif; ?>
				<div id="max-smtp-menu">
					<?php if( isset( $_GET['action'] ) && ( $_GET['action'] === 'edit' || $_GET['action'] === 'add' ) ): ?>
					<form method="post" >
						<?php
							settings_fields( 'max_smtp_account' );
							do_settings_sections( 'max_smtp_account' );
						?>
						<input type="hidden" name="_sub_nounce" value="<?php echo wp_create_nonce( 'max_smtp_submit_nounce' ); ?>">
						<?php if( isset( $_GET['action'] ) && ( $_GET['action'] == 'edit' ) ){ ?>
							<input type="submit" name="editsmtp" class="button button-primary" value="Edit SMTP">
						<?php } else { ?>
							<input type="submit" name="addsmtp" class="button button-primary" value="Add SMTP">
						<?php } ?>
						<a href="<?php echo esc_url_raw( remove_query_arg( ['id','action','_wpnonce'] ) ); ?>" class="button">Close</a>
					</form>
					<?php endif; ?>
					<div id="post-body" class="metabox-holder columns-2">
						<div id="post-body-content">
							<div class="meta-box-sortables ui-sortable">
								<form method="post">
									<?php
										$this->items_obj->prepare_items();
										$this->items_obj->display();
									?>
								</form>
							</div>
						</div>
					</div>
					<br class="clear">
				</div>
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

add_action( 'plugins_loaded', function () {
	Max_SMTP_Accounts_Page::maxsmtp_get_instance();
} );

?>
