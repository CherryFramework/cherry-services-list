<?php
/**
 * CTA from handler
 *
 * @package   package_name
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Cherry_Services_List_Form' ) ) {

	/**
	 * Define Cherry_Services_List_Form class
	 */
	class Cherry_Services_List_Form {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Constructor for the class
		 */
		function __construct() {
			add_action( 'init', array( $this, 'start_session' ) );
			add_action( 'init', array( $this, 'maybe_process_cta_form' ) );
		}

		/**
		 * Run session
		 */
		public function start_session() {

			if ( ! session_id() ) {
				session_start();
			}

		}

		/**
		 * Check if we need CTA form to process - do it and redirect.
		 *
		 * @return void
		 */
		public function maybe_process_cta_form() {

			if ( empty( $_POST['cherry-services-form'] ) ) {
				return;
			}

			$post_id = (int) $_POST['cherry-services-form'];

			if ( cherry_services_list()->post_type() !== get_post_type( $post_id ) ) {
				return;
			}

			if ( empty( $_POST['service-cta'] ) || ! is_array( $_POST['service-cta'] ) ) {
				return;
			}

			$to = get_post_meta( $post_id, 'cherry-services-form-mail', true );

			$success_message = get_post_meta(
				$post_id,
				'cherry-services-form-message',
				esc_html__( 'Thanks for your request', 'cherry-services' )
			);

			if ( ! $to ) {
				$to = get_bloginfo( 'admin_email' );
			}

			$subject = sprintf( esc_html__( 'Request on %s', 'cherry-services' ), get_the_title( $post_id ) );
			$message = $subject . "\r\n\r\n";

			foreach ( $_POST['service-cta'] as $field => $value ) {
				$message .= esc_attr( $field ) . ": " . esc_attr( $value ) . "\r\n";
			}

			wp_mail( $to, $subject, $message );

			$_SESSION['cherry-services']['messages'][ $post_id ] = $success_message;

			if ( ! empty( $_POST['cherry-services-ref'] ) && false !== strpos( $_POST['cherry-services-ref'], home_url() ) ) {
				wp_safe_redirect( esc_url( $_POST['cherry-services-ref'] ) );
				die();
			}

		}

		/**
		 * Get form message and clear after
		 * @param  int $post_id Post ID to get message for.
		 * @return string
		 */
		public function get_message( $post_id ) {

			if ( ! $post_id ) {
				return;
			}

			if ( empty( $_SESSION['cherry-services']['messages'][ $post_id ] ) ) {
				return;
			}

			$message = esc_html( $_SESSION['cherry-services']['messages'][ $post_id ] );
			unset( $_SESSION['cherry-services']['messages'][ $post_id ] );

			return $message;
		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @return object
		 */
		public static function get_instance() {

			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}
	}

}

/**
 * Returns instance of Cherry_Services_List_Form
 *
 * @return object
 */
function cherry_services_list_form() {
	return Cherry_Services_List_Form::get_instance();
}

cherry_services_list_form();
