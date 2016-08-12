<?php
/**
 * Cherry Services - register post type and taxonomy
 *
 * @package   Cherry_Team
 * @author    Cherry Services
 * @license   GPL-2.0+
 * @link      http://www.cherryframework.com/
 * @copyright 2015 Cherry Team
 */

/**
 * Class for register post types.
 *
 * @since 1.0.0
 */
class Cherry_Services_List_Init extends Cherry_Services_List {

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

		// Adds the services post type.
		add_action( 'init', array( $this, 'register_post' ) );
		add_action( 'init', array( $this, 'register_tax' ) );

	}

	/**
	 * Register the custom post type.
	 *
	 * @since 1.0.0
	 * @link  https://codex.wordpress.org/Function_Reference/register_post_type
	 */
	public function register_post() {

		$labels = array(
			'name'               => __( 'Services', 'cherry-services' ),
			'singular_name'      => __( 'Service', 'cherry-services' ),
			'add_new'            => __( 'Add New', 'cherry-services' ),
			'add_new_item'       => __( 'Add New Service', 'cherry-services' ),
			'edit_item'          => __( 'Edit Service', 'cherry-services' ),
			'new_item'           => __( 'New Service', 'cherry-services' ),
			'view_item'          => __( 'View Service', 'cherry-services' ),
			'search_items'       => __( 'Search Services', 'cherry-services' ),
			'not_found'          => __( 'No Services found', 'cherry-services' ),
			'not_found_in_trash' => __( 'No Services found in trash', 'cherry-services' ),
		);

		$supports = array(
			'title',
			'editor',
			'thumbnail',
		);

		global $wp_version;

		$args = array(
			'labels'          => $labels,
			'supports'        => $supports,
			'public'          => true,
			'capability_type' => 'post',
			'hierarchical'    => false, // Hierarchical causes memory issues - WP loads all records!
			'rewrite'         => array(
				'slug'       => $this->post_type(),
				'with_front' => false,
				'feeds'      => true,
			),
			'query_var'       => true,
			'menu_position'   => null,
			'menu_icon'       => ( version_compare( $wp_version, '3.8', '>=' ) ) ? 'dashicons-lightbulb' : '',
			'can_export'      => true,
			'has_archive'     => true,
		);

		$args = apply_filters( 'cherry_services_post_type_args', $args );

		register_post_type( $this->post_type(), $args );

	}

	/**
	 * Register taxonomy for custom post type.
	 *
	 * @since 1.0.0
	 * @link  https://codex.wordpress.org/Function_Reference/register_taxonomy
	 */
	public function register_tax() {

		// Add new taxonomy, NOT hierarchical (like tags)
		$labels = array(
			'name'                       => __( 'Services Category', 'cherry-services' ),
			'singular_name'              => __( 'Edit Category', 'cherry-services' ),
			'search_items'               => __( 'Search Categories', 'cherry-services' ),
			'popular_items'              => __( 'Popular Categories', 'cherry-services' ),
			'all_items'                  => __( 'All Categories', 'cherry-services' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Category', 'cherry-services' ),
			'update_item'                => __( 'Update Category', 'cherry-services' ),
			'add_new_item'               => __( 'Add New Category', 'cherry-services' ),
			'new_item_name'              => __( 'New Category Name', 'cherry-services' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'cherry-services' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'cherry-services' ),
			'choose_from_most_used'      => __( 'Choose from the most used categories', 'cherry-services' ),
			'not_found'                  => __( 'No categories found.', 'cherry-services' ),
			'menu_name'                  => __( 'Categories', 'cherry-services' ),
		);

		$args = array(
			'hierarchical'          => true,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array(
				'slug' => $this->tax( 'category' )
			),
		);

		register_taxonomy( $this->tax( 'category' ), $this->post_type(), $args );

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
 * Returns Cherry_Services_List_Init instance.
 *
 * @return object
 */
function cherry_services_list_init() {
	Cherry_Services_List_Init::get_instance();
}

cherry_services_list_init();
