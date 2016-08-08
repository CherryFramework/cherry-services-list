<?php
/**
 * Plugin Name: Cherry Services List
 * Plugin URI:  http://www.templatemonster.com/wordpress-themes.php
 * Description: Services management.
 * Version:     1.0.0
 * Author:      TemplateMonster
 * Text Domain: cherry-services
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 *
 * @package Cherry_Services_List
 * @author  Cherry Team
 * @version 1.0.0
 * @license GPL-3.0+
 * @copyright  2002-2016, Cherry Team
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

// If class `Cherry_Services_List` doesn't exists yet.
if ( ! class_exists( 'Cherry_Services_List' ) ) {

	/**
	 * Sets up and initializes the plugin.
	 */
	class Cherry_Services_List {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    object
		 */
		private static $instance = null;

		/**
		 * A reference to an instance of cherry framework core class.
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    object
		 */
		private $core = null;

		/**
		 * Holder for base plugin URL
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string
		 */
		private $plugin_url = null;

		/**
		 * Holder for base plugin path
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string
		 */
		private $plugin_path = null;

		/**
		 * Plugin version
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string
		 */
		private $version = '1.0.0';

		/**
		 * Plugin CPT name
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string
		 */
		private $post_type = 'cherry-services';

		/**
		 * Sets up needed actions/filters for the plugin to initialize.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function __construct() {

			// Load the installer core.
			add_action( 'after_setup_theme', require( trailingslashit( __DIR__ ) . 'cherry-framework/setup.php' ), 0 );
			// Load the core functions/classes required by the rest of the plugin.
			add_action( 'after_setup_theme', array( $this, 'get_core' ), 1 );
			// Load the modules.
			add_action( 'after_setup_theme', array( 'Cherry_Core', 'load_all_modules' ), 2 );

			// Load files
			add_action( 'after_setup_theme', array( $this, 'load_files' ), 3 );

			// Internationalize the text strings used.
			add_action( 'init', array( $this, 'lang' ), 1 );
			// Load the admin files.
			add_action( 'init', array( $this, 'admin' ), 2 );

			// Load public-facing stylesheets.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			// Load public-facing JavaScripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Register activation and deactivation hook.
			register_activation_hook( __FILE__, array( $this, 'activation' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );
		}

		/**
		 * Loads the core functions. These files are needed before loading anything else in the
		 * plugin because they have required functions for use.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return object
		 */
		public function get_core() {

			/**
			 * Fires before loads the plugin's core.
			 *
			 * @since 1.0.0
			 */
			do_action( 'blank_plugin_core_before' );

			global $chery_core_version;

			if ( null !== $this->core ) {
				return $this->core;
			}

			if ( 0 < sizeof( $chery_core_version ) ) {
				$core_paths = array_values( $chery_core_version );
				require_once( $core_paths[0] );
			} else {
				die( 'Class Cherry_Core not found' );
			}

			$this->core = new Cherry_Core( array(
				'base_dir' => $this->plugin_path( 'cherry-framework' ),
				'base_url' => $this->plugin_url( 'cherry-framework' ),
				'modules'  => array(
					'cherry-toolkit' => array(
						'autoload' => false,
					),
					'cherry-js-core' => array(
						'autoload' => true,
					),
					'cherry-ui-elements' => array(
						'autoload' => false,
					),
					'cherry-utility' => array(
						'autoload' => true,
					),
					'cherry-post-meta' => array(
						'autoload' => false,
					),
				),
			) );

			return $this->core;
		}

		/**
		 * Load all globally available files.
		 *
		 * @return void
		 */
		public function load_files() {
			$this->setup();
		}

		/**
		 * Load base files.
		 *
		 * @return void
		 */
		public function setup() {
			require $this->plugin_path( 'public/includes/class-cherry-services-list-init.php' );
		}

		/**
		 * Returns path to file or dir inside plugin folder
		 *
		 * @param  string $path Path inside plugin dir.
		 * @return string
		 */
		public function plugin_path( $path = null ) {

			if ( ! $this->plugin_path ) {
				$this->plugin_path = trailingslashit( plugin_dir_path( __FILE__ ) );
			}

			return $this->plugin_path . $path;
		}
		/**
		 * Returns url to file or dir inside plugin folder
		 *
		 * @param  string $path Path inside plugin dir.
		 * @return string
		 */
		public function plugin_url( $path = null ) {

			if ( ! $this->plugin_url ) {
				$this->plugin_url = trailingslashit( plugin_dir_url( __FILE__ ) );
			}

			return $this->plugin_url . $path;
		}

		/**
		 * Loads admin files.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function admin() {
			require $this->plugin_path( 'admin/includes/class-cherry-services-meta.php' );
		}
		/**
		 * Loads the translation files.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function lang() {
			load_plugin_textdomain( 'blank-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Returns services post type name
		 *
		 * @since  1.0.0
		 * @return string
		 */
		public function post_type() {
			return apply_filters( 'cherry_services_post_type', $this->post_type );
		}

		/**
		 * Returns services taxonomy name
		 *
		 * @since  1.0.0
		 * @param  string $type Tax type.
		 * @return string
		 */
		public function tax( $type = 'category' ) {
			return $this->post_type() . esc_attr( $type );
		}

		/**
		 * Enqueue public-facing stylesheets.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function enqueue_styles() {

		}

		/**
		 * Enqueue public-facing JavaScripts.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function enqueue_scripts() {

		}

		/**
		 * Do some stuff on plugin activation
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function activation() {

			$this->setup();

			$init = Cherry_Services_List_Init::get_instance();

			/**
			 * Call CPT registration function.
			 *
			 * @link https://codex.wordpress.org/Function_Reference/flush_rewrite_rules#Examples
			 */
			$init->register_post();
			$init->register_tax();

			flush_rewrite_rules();

		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @access public
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

if ( ! function_exists( 'cherry_services_list' ) ) {

	/**
	 * Returns instanse of the plugin class.
	 *
	 * @since  1.0.0
	 * @return object
	 */
	function cherry_services_list() {
		return Cherry_Services_List::get_instance();
	}
}

cherry_services_list();
