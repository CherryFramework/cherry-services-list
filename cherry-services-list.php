<?php
/**
 * Plugin Name: Cherry Services List
 * Plugin URI:  http://www.templatemonster.com/wordpress-themes.php
 * Description: Cherry Services is a flexible WordPress plugin that lets you display your company’s services in a variety of ways.
 * Version:     1.0.4
 * Author:      TemplateMonster
 * Author URI:  http://www.templatemonster.com
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
		 * Holder for dynamic CSS instance
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    object
		 */
		public $dynamic_css = null;

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
		private $version = '1.0.4';

		/**
		 * Plugin CPT name
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string
		 */
		private $post_type = 'cherry-services';

		/**
		 * Option to store all services data in
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string
		 */
		private $option_key = 'cherry-services';

		/**
		 * Theme supports array
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    array
		 */
		public $theme_supports = null;

		/**
		 * Default options list
		 *
		 * @var array
		 */
		public $default_options = array(
			'archive-page'       => '',
			'posts-per-page'     => 9,
			'archive-columns'    => 3,
			'single-template'    => 'single',
			'single-image-size'  => 'thumbnail',
			'listing-template'   => 'default',
			'listing-image-size' => 'thumbnail',
		);

		/**
		 * Options storage
		 *
		 * @var array
		 */
		private $options_val = null;

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
			// Load the admin files.
			add_action( 'init', array( $this, 'init_modules' ), 2 );

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
			do_action( 'cherry_services_list_core_before' );

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
					'cherry-interface-builder' => array(
						'autoload' => false,
					),
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
					'cherry-dynamic-css' => array(
						'autoload' => false,
					),
				),
			) );

			return $this->core;
		}

		/**
		 * Check if passed capability disabled in theme.
		 *
		 * @since  1.0.0
		 * @param  string $cap Capability name.
		 * @return boolean
		 */
		public function check_theme_supports( $cap ) {

			if ( null === $this->theme_supports ) {
				$this->theme_supports = apply_filters(
					'cherry_services_theme_supports',
					array(
						'blank_theme'        => true,
						'customizer_options' => true,
					)
				);
			}

			if ( ! isset( $this->theme_supports[ $cap ] ) ) {
				return false;
			}

			return $this->theme_supports[ $cap ];
		}

		/**
		 * Returns plugin version
		 *
		 * @return string
		 */
		public function get_version() {
			return $this->version;
		}

		/**
		 * Load all globally available files.
		 *
		 * @return void
		 */
		public function load_files() {

			$this->setup();

			require $this->plugin_path( 'public/includes/class-cherry-services-list-templater.php' );
			require $this->plugin_path( 'public/includes/class-cherry-services-list-tools.php' );
			require $this->plugin_path( 'public/includes/class-cherry-services-list-data.php' );
			require $this->plugin_path( 'public/includes/class-cherry-services-list-shortcode.php' );
			require $this->plugin_path( 'public/includes/class-cherry-services-list-form.php' );

		}

		/**
		 * Manually init required modules.
		 *
		 * @return void
		 */
		public function init_modules() {

			$this->dynamic_css = $this->get_core()->init_module(
				'cherry_dynamic_css',
				array()
			);

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

			add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ), 0 );

			require $this->plugin_path( 'admin/includes/class-cherry-services-meta.php' );
			require $this->plugin_path( 'admin/includes/class-cherry-services-options-page.php' );

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				require $this->plugin_path( 'admin/includes/class-cherry-services-ajax.php' );
			}

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
			return $this->post_type() . '_' . esc_attr( $type );
		}

		/**
		 * Get the template path.
		 *
		 * @return string
		 */
		public function template_path() {
			return apply_filters( 'cherry_services_template_path', 'cherry-services/' );
		}

		/**
		 * Returns key to store options in
		 *
		 * @since  1.0.0
		 * @return string
		 */
		public function option_key() {
			return $this->option_key;
		}

		/**
		 * Get option value
		 *
		 * @param  string  $option  Option name
		 * @param  boolean $default Default value
		 * @return mixed
		 */
		public function get_option( $option, $default = false ) {

			if ( null === $this->options_val ) {
				$this->options_val = get_option( $this->option_key() );
			}

			if ( isset( $this->options_val[ $option ] ) ) {
				return $this->options_val[ $option ];
			} else {
				return $default;
			}
		}

		/**
		 * Register admin-related scripts and style.
		 *
		 * @return void
		 */
		public function register_admin_assets() {

			wp_register_style(
				'cherry-services-admin',
				$this->plugin_url( 'admin/assets/css/cherry-services.css' ),
				false,
				$this->get_version()
			);

			wp_register_script(
				'serialize-object',
				$this->plugin_url( 'admin/assets/js/serialize-object.js' ),
				array(),
				$this->get_version(),
				true
			);

			wp_register_script(
				'cherry-services-admin',
				$this->plugin_url( 'admin/assets/js/cherry-services-admin.js' ),
				array( 'serialize-object' ),
				$this->get_version(),
				true
			);
		}

		/**
		 * Enqueue public-facing stylesheets.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function enqueue_styles() {

			$styles = apply_filters(
				'cherry_services_styles',
				array(
					'cherry-services' => array(
						'src'   => $this->plugin_url( 'public/assets/css/cherry-services.css' ),
						'deps'  => array(),
						'ver'   => $this->get_version(),
						'media' => 'all',
					),
					'cherry-services-theme' => array(
						'src'   => $this->plugin_url( 'public/assets/css/cherry-services-theme.css' ),
						'deps'  => array(),
						'ver'   => $this->get_version(),
						'media' => 'all',
					),
					'cherry-services-grid' => array(
						'src'   => $this->plugin_url( 'public/assets/css/cherry-services-grid.css' ),
						'deps'  => array(),
						'ver'   => $this->get_version(),
						'media' => 'all',
					),
					'font-awesome' => array(
						'src'   => $this->plugin_url( 'public/assets/css/font-awesome.min.css' ),
						'deps'  => array(),
						'ver'   => '4.6.3',
						'media' => 'all',
					),
				)
			);

			if ( ! $this->check_theme_supports( 'blank_theme' ) && isset( $styles['cherry-services-theme'] ) ) {
				unset( $styles['cherry-services-theme'] );
			}

			foreach ( $styles as $handle => $style ) {
				wp_enqueue_style( $handle, $style['src'], $style['deps'], $style['ver'], $style['media'] );
			}

		}

		/**
		 * Enqueue public-facing JavaScripts.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function enqueue_scripts() {

			wp_register_script(
				'cherry-services',
				$this->plugin_url( 'public/assets/js/cherry-services.js' ),
				array( 'jquery' ),
				'1.0.0',
				true
			);

			wp_localize_script(
				'cherry-services',
				'cherryServices',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'loader'  => apply_filters(
						'cherry_services_loader_html',
						'<div class="cherry-spinner cherry-spinner-double-bounce"><div class="cherry-double-bounce1"></div><div class="cherry-double-bounce2"></div></div>'
					)
				)
			);
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

			$options = array(
				$this->option_key()              => get_option( $this->option_key() ),
				$this->option_key() . '_default' => get_option( $this->option_key() . '_default' ),
			);

			foreach ( $options as $key => $value ) {
				if ( empty( $value ) ) {
					add_option( $key, $this->default_options, '', false );
				}
			}

		}

		/**
		 * Do some stuff on plugin activation
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function deactivation() {
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
