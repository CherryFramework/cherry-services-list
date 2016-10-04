<?php
/**
 * Cherry Services templater object
 *
 * @package   Cherry_Services_List
 * @author    Cherry Team
 * @license   GPL-2.0+
 * @link      http://www.cherryframework.com/
 * @copyright 2016 Cherry Team
 */

/**
 * Class for including page templates.
 *
 * @since 1.0.0
 */
class Cherry_Services_List_Templater extends Cherry_Services_List {

	/**
	 * Templater macros regular expression
	 *
	 * @var string
	 */
	private $macros_regex = '/%%.+?%%/';

	/**
	 * Templates data to replace
	 *
	 * @var array
	 */
	private $replace_data = array();

	/**
	 * Parent WP_Query.
	 *
	 * @var object
	 */
	private $parent_query = null;

	/**
	 * Template callbacks object.
	 *
	 * @var object
	 */
	public $callbacks = null;

	/**
	 * A reference to an instance of this class.
	 *
	 * @since 1.0.0
	 * @var   object
	 */
	private static $instance = null;

	/**
	 * Sets up needed actions/filters.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add a filter to the template include in order to determine if the page has our template assigned and return it's path.
		add_filter( 'template_include', array( $this, 'view_template' ) );

	}

	/**
	 * Checks if the template is assigned to the page.
	 *
	 * @since  1.0.0
	 * @param  string $template current template name.
	 * @return string
	 */
	public function view_template( $template ) {

		$find = array();
		$file = '';

		if ( is_single() && $this->post_type() === get_post_type() ) {

			$file   = 'single-services.php';
			$find[] = $file;
			$find[] = $this->template_path() . $file;

		} elseif ( is_tax( $this->tax( 'category' ) ) ) {

			$term = get_queried_object();
			$file = 'archive-services.php';

			$file_term = 'taxonomy-' . $term->taxonomy . '.php';

			$find[] = 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$find[] = $this->template_path() . 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$find[] = 'taxonomy-' . $term->taxonomy . '.php';
			$find[] = $this->template_path() . 'taxonomy-' . $term->taxonomy . '.php';
			$find[] = $file_term;
			$find[] = $this->template_path() . $file_term;
			$find[] = $file;
			$find[] = $this->template_path() . $file;

		} elseif ( is_post_type_archive( $this->post_type() ) ) {

			$file   = 'archive-services.php';
			$find[] = $file;
			$find[] = $this->template_path() . $file;

		} elseif ( $this->get_option( 'archive-page' ) && is_page( $this->get_option( 'archive-page' ) ) ) {
			$file   = 'archive-services.php';
			$find[] = $file;
			$find[] = $this->template_path() . $file;
		}

		if ( $file ) {
			$template = locate_template( array_unique( $find ) );
			if ( ! $template ) {
				$template = $this->plugin_path( 'templates/' . $file );
			}
		}

		return $template;
	}

	/**
	 * Returns macros regular expression.
	 *
	 * @return string
	 */
	public function macros_regex() {
		return $this->macros_regex;
	}

	/**
	 * Prepare template data to replace.
	 *
	 * @param array $atts Output attributes.
	 */
	function setup_template_data( $atts ) {

		require_once $this->plugin_path( 'public/includes/class-cherry-services-list-template-callbacks.php' );

		$this->callbacks = new Cherry_Services_List_Template_Callbacks( $atts );

		$data = array(
			'image'    => array( $this->callbacks, 'get_image' ),
			'title'    => array( $this->callbacks, 'get_title' ),
			'slogan'   => array( $this->callbacks, 'get_slogan' ),
			'desc'     => array( $this->callbacks, 'get_desc' ),
			'features' => array( $this->callbacks, 'get_features' ),
			'cta'      => array( $this->callbacks, 'get_cta' ),
			'content'  => array( $this->callbacks, 'get_content' ),
			'testi'    => array( $this->callbacks, 'get_testi' ),
			'icon'     => array( $this->callbacks, 'get_icon' ),
			'button'   => array( $this->callbacks, 'get_button' ),
		);

		/**
		 * Filters item data.
		 *
		 * @since 1.0.2
		 * @param array $data Item data.
		 * @param array $atts Attributes.
		 */
		$this->replace_data = apply_filters( 'cherry_services_data_callbacks', $data, $atts );

		return $this->callbacks;
	}

	/**
	 * Read template (static).
	 *
	 * @since  1.0.0
	 * @return bool|WP_Error|string - false on failure, stored text on success.
	 */
	public function get_contents( $template ) {

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			include_once( ABSPATH . '/wp-admin/includes/file.php' );
		}

		WP_Filesystem();
		global $wp_filesystem;

		// Check for existence.
		if ( ! $wp_filesystem->exists( $template ) ) {
			return false;
		}

		// Read the file.
		$content = $wp_filesystem->get_contents( $template );

		if ( ! $content ) {
			// Return error object.
			return new WP_Error( 'reading_error', 'Error when reading file' );
		}

		return $content;
	}

	/**
	 * Retrieve a *.tmpl file content.
	 *
	 * @since  1.0.0
	 * @param  string $template  File name.
	 * @return string
	 */
	public function get_template_by_name( $template ) {

		$file       = '';
		$default    = $this->plugin_path( 'templates/tmpl/default.tmpl' );
		$upload_dir = wp_upload_dir();
		$upload_dir = trailingslashit( $upload_dir['basedir'] );
		$subdir     = 'templates/tmpl/' . $template;

		/**
		 * Filters a default fallback-template.
		 *
		 * @since 1.0.0
		 * @param string $content.
		 */
		$content = apply_filters(
			'cherry_services_fallback_template',
			'<div class="inner-wrapper">%%title%%%%image%%%%content%%</div>'
		);

		if ( file_exists( $upload_dir . $subdir ) ) {
			$file = $upload_dir . $subdir;
		} elseif ( $theme_template = locate_template( array( $this->template_path() . 'tmpl/' . $template ) ) ) {
			$file = $theme_template;
		} elseif ( file_exists( $this->plugin_path( $subdir ) ) ) {
			$file = $this->plugin_path( $subdir );
		} else {
			$file = $default;
		}

		if ( ! empty( $file ) ) {
			$content = $this->get_contents( $file );
		}

		return $content;
	}

	/**
	 * Parse template content and replace macros with real data.
	 *
	 * @param  string $content Content to parse.
	 * @return string
	 */
	public function parse_template( $content ) {
		$this->callbacks->set_parent_query( $this->parent_query );
		return preg_replace_callback( $this->macros_regex(), array( $this, 'replace_callback' ), $content );
	}

	/**
	 * Callback to replace macros with data.
	 *
	 * @since 1.0.0
	 * @param array $matches Founded macros.
	 */
	public function replace_callback( $matches ) {

		if ( ! is_array( $matches ) ) {
			return;
		}

		if ( empty( $matches ) ) {
			return;
		}

		$item   = trim( $matches[0], '%%' );
		$arr    = explode( ' ', $item, 2 );
		$macros = strtolower( $arr[0] );
		$attr   = isset( $arr[1] ) ? shortcode_parse_atts( $arr[1] ) : array();

		if ( ! isset(  $this->replace_data[ $macros ] ) ) {
			return;
		}

		$callback = $this->replace_data[ $macros ];

		if ( ! is_callable( $callback ) || ! isset( $this->replace_data[ $macros ] ) ) {
			return;
		}

		if ( ! empty( $attr ) ) {

			// Call a WordPress function.
			return call_user_func( $callback, $attr );
		}

		return call_user_func( $callback );
	}

	/**
	 * Set up parent query for current page.
	 *
	 * @param  object $query WP_Query object.
	 * @return void
	 */
	public function set_parent_query( $query ) {
		$this->parent_query = $query;
	}

	/**
	 * Returns available listing templates list
	 *
	 * @return array
	 */
	public function get_listing_templates_list() {
		return apply_filters( 'cherry_services_listing_templates_list', array(
			'default'    => 'default.tmpl',
			'media-icon' => 'media-icon.tmpl',
		) );
	}

	/**
	 * Returns available single templates list
	 *
	 * @return array
	 */
	public function get_single_templates_list() {
		return apply_filters( 'cherry_services_single_templates_list', array(
			'single'     => 'single.tmpl',
		) );
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

/**
 * Returns instance of templater class.
 *
 * @return Cherry_Services_List_Templater
 */
function cherry_services_templater() {
	return Cherry_Services_List_Templater::get_instance();
}

cherry_services_templater();
