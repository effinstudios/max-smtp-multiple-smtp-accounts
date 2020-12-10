<?php
namespace MAXSMTP;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Max_SMTP_Queue_Page {
	static $instance;
	public $items_obj;

	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'maxsmtp_set_screen' ], 10, 3 );
		add_action( 'admin_menu', [ $this, 'maxsmtp_plugin_menu' ] );
	}

	public static function maxsmtp_set_screen( $status, $option, $value ) {
		return $value;
	}

	public function maxsmtp_plugin_menu() {
		$hook = add_submenu_page(
			'max-smtp',
			'Max SMTP - ' . __( 'Email Queue', 'max-smtp' ),
			__( 'Email Queue', 'max-smtp' ),
			'manage_options',
			'max-smtp-queue',
			[ $this, 'maxsmtp_plugin_settings_page' ]
		);
		add_action( "load-$hook", [ $this, 'maxsmtp_screen_option' ] );
	}

	public function maxsmtp_screen_option() {
		$option = 'per_page';
		$args   = [
			'label'   => __( 'Email Queues', 'max-smtp' ),
			'default' => 20,
			'option'  => 'items_per_page'
		];
		add_screen_option( $option, $args );
		$this->items_obj = new \Max_SMTP_Queue_Page_Ex();
	}

	public function maxsmtp_plugin_settings_page() {
		$status	= get_option( 'max_smtp_pause_status' );
		?>
			<div class="wrap max-smtp max-smtp-queue">
				<h1><img class="max-smtp-logo" src="<?php echo esc_url( MAXSMTP_URL . '/assets/images/logo.png' ); ?>" alt="Max SMTP"> <?php _e( 'Email Queue', 'max-smtp' ); ?></h1>
				<?php if( $status ): ?>
					<div id="message" class="error notice notice-warning is-dismissible">
						<p><?php _e( 'Max SMTP: Sending emails via SMTP is currently paused.', 'max-smtp' ); ?> <?php esc_html_e( get_option( 'max_smtp_pause_message' ) ); ?></p>
					</div>
				<?php endif; ?>
				<div id="max-smtp-menu">
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
	Max_SMTP_Queue_Page::maxsmtp_get_instance();
} );

?>
