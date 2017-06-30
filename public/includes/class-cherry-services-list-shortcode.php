<?php
/**
 * Cherry Team Shortcode.
 *
 * @package   Cherry_Services_List
 * @author    Cherry Team
 * @license   GPL-2.0+
 * @link      http://www.cherryframework.com/
 * @copyright 2014 Cherry Team
 */

/**
 * Class for Team shortcode.
 *
 * @since 1.0.0
 */
class Cherry_Services_List_Shortcode {

	/**
	 * Shortcode name.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public static $name = 'cherry_services';

	/**
	 * A reference to an instance of this class.
	 *
	 * @since 1.0.0
	 * @var   object
	 */
	private static $instance = null;

	/**
	 * Storage for data object
	 * @since 1.0.0
	 * @var   null|object
	 */
	public $data = null;

	/**
	 * Sets up our actions/filters.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Register shortcode on 'init'.
		add_action( 'init', array( $this, 'register_shortcode' ), -1 );

	}

	/**
	 * Registers the [$this->name] shortcode.
	 *
	 * @since 1.0.0
	 */
	public function register_shortcode() {

		add_shortcode( $this->tag(), array( $this, 'do_shortcode' ) );

		$base = cherry_services_list();

		if ( defined( 'ELEMENTOR_VERSION' ) ) {

			require $base->plugin_path( 'public/includes/ext/class-cherry-services-list-elementor-compat.php' );

			cherry_services_list()->elementor_compat = cherry_services_list_elementor_compat( array(
				$this->tag() => array(
					'title' => esc_html__( 'Cherry Services', 'cherry-services' ),
					'file'  => $base->plugin_path( 'public/includes/ext/class-cherry-services-list-elementor-module.php' ),
					'class' => 'Cherry_Services_Elementor_Widget',
					'icon'  => 'eicon-favorite',
					'atts'  => $this->shortcode_args(),
				),
			) );
		}

		if ( is_admin() ) {
			$this->register_shrtcode_for_builder();
		}
	}

	/**
	 * Returns shortcode tag.
	 *
	 * @return string
	 */
	public function tag() {

		/**
		 * Filters a shortcode name.
		 *
		 * @since 1.0.0
		 * @param string $this->name Shortcode name.
		 */
		$tag = apply_filters( self::$name . '_shortcode_name', self::$name );

		return $tag;
	}

	/**
	 * Register shortcode arguments.
	 *
	 * @return array
	 */
	public function shortcode_args() {

		$column_opt = array(
			1 => 1,
			2 => 2,
			3 => 3,
			4 => 4,
			6 => 6,
		);

		return apply_filters( 'cherry_services_list_shortcode_arguments', array(
			'super_title'    => array(
				'type'  => 'text',
				'title' => esc_html__( 'Super title', 'cherry-services' ),
				'value' => '',
			),
			'title'          => array(
				'type'  => 'text',
				'title' => esc_html__( 'Title', 'cherry-services' ),
				'value' => '',
			),
			'subtitle'       => array(
				'type'  => 'text',
				'title' => esc_html__( 'Subtitle', 'cherry-services' ),
				'value' => '',
			),
			'columns'        => array(
				'type'    => 'select',
				'title'   => esc_html__( 'Desktop columns', 'cherry-services' ),
				'value'   => 3,
				'options' => $column_opt,
			),
			'columns_laptop' => array(
				'type'    => 'select',
				'title'   => esc_html__( 'Laptop columns', 'cherry-services' ),
				'value'   => 3,
				'options' => $column_opt,
			),
			'columns_tablet' => array(
				'type'    => 'select',
				'title'   => esc_html__( 'Tablet columns', 'cherry-services' ),
				'value'   => 1,
				'options' => $column_opt,
			),
			'columns_phone'  => array(
				'type'    => 'select',
				'title'   => esc_html__( 'Phone columns', 'cherry-services' ),
				'value'   => 1,
				'options' => $column_opt,
			),
			'posts_per_page' => array(
				'type'        => 'slider',
				'title'       => esc_html__( 'Posts per page', 'cherry-services' ),
				'description' => esc_html__( 'Select how many posts per page do you want to display(-1 means that will show all services)', 'cherry-services' ),
				'max_value'   => 50,
				'min_value'   => -1,
				'value'       => 6,
			),
			'category'       => array(
				'type'       => 'select',
				'title'      => esc_html__( 'Show services from categories', 'cherry-services' ),
				'multiple'   => true,
				'value'      => '',
				'class'      => 'cherry-multi-select',
				'options'    => false,
				'options_cb' => array( $this, 'get_categories' ),
			),
			'id'             => array(
				'type'  => 'text',
				'title' => esc_html__( 'Show services by ID', 'cherry-services' ),
				'value' => '',
			),
			'excerpt_length' => array(
				'type'        => 'slider',
				'title'       => esc_html__( 'Description length', 'cherry-services' ),
				'description' => esc_html__( 'Select how many words show in desciption', 'cherry-services' ),
				'max_value'   => 200,
				'min_value'   => 0,
				'value'       => 20,
			),
			'more'           => array(
				'type'        => 'switcher',
				'title'       => esc_html__( 'Show more button', 'cherry-services' ),
				'description' => esc_html__( 'Show/hide more button', 'cherry-services' ),
				'value'       => 'true',
				'toggle'      => array(
					'true_toggle'  => esc_html__( 'Yes', 'cherry-services' ),
					'false_toggle' => esc_html__( 'No', 'cherry-services' ),
					'true_slave'   => 'services-more-filter-visible-true',
				),
			),
			'more_text'      => array(
				'type'   => 'text',
				'title'  => esc_html__( 'More button text', 'cherry-services' ),
				'value'  => esc_html__( 'More', 'cherry-services' ),
				'master' => 'services-more-filter-visible-true',
			),
			'more_url'       => array(
				'type'   => 'text',
				'title'  => esc_html__( 'More button URL', 'cherry-services' ),
				'value'  => '#',
				'master' => 'services-more-filter-visible-true',
			),
			'ajax_more'      => array(
				'type'        => 'switcher',
				'title'       => esc_html__( 'AJAX load more', 'cherry-services' ),
				'description' => esc_html__( 'Enable AJAX load more event on more button', 'cherry-services' ),
				'value'       => 'true',
				'toggle'      => array(
					'true_toggle'  => esc_html__( 'Yes', 'cherry-services' ),
					'false_toggle' => esc_html__( 'No', 'cherry-services' ),
				),
				'master' => 'services-more-filter-visible-true',
			),
			'pagination'     => array(
				'type'        => 'switcher',
				'title'       => esc_html__( 'Pagination', 'cherry-services' ),
				'description' => esc_html__( 'Enable paging navigation', 'cherry-services' ),
				'value'       => 'false',
				'toggle'      => array(
					'true_toggle'  => esc_html__( 'Yes', 'cherry-services' ),
					'false_toggle' => esc_html__( 'No', 'cherry-services' ),
				),
			),
			'show_title'     => array(
				'type'        => 'switcher',
				'title'       => esc_html__( 'Show service title', 'cherry-services' ),
				'value'       => 'true',
				'toggle'      => array(
					'true_toggle'  => esc_html__( 'Yes', 'cherry-services' ),
					'false_toggle' => esc_html__( 'No', 'cherry-services' ),
				),
			),
			'show_media'     => array(
				'type'        => 'switcher',
				'title'       => esc_html__( 'Show featured image', 'cherry-services' ),
				'value'       => 'true',
				'toggle'      => array(
					'true_toggle'  => esc_html__( 'Yes', 'cherry-services' ),
					'false_toggle' => esc_html__( 'No', 'cherry-services' ),
				),
			),
			'show_content'   => array(
				'type'        => 'switcher',
				'title'       => esc_html__( 'Show service content', 'cherry-services' ),
				'value'       => 'true',
				'toggle'      => array(
					'true_toggle'  => esc_html__( 'Yes', 'cherry-services' ),
					'false_toggle' => esc_html__( 'No', 'cherry-services' ),
				),
			),
			'show_item_more'   => array(
				'type'        => 'switcher',
				'title'       => esc_html__( 'Show service item Read More button (if allowed in template)', 'cherry-services' ),
				'value'       => 'true',
				'toggle'      => array(
					'true_toggle'  => esc_html__( 'Yes', 'cherry-services' ),
					'false_toggle' => esc_html__( 'No', 'cherry-services' ),
				),
			),
			'item_more_text' => array(
				'type'   => 'text',
				'title'  => esc_html__( 'Item Read More button text (if empty - used default value from template)', 'cherry-services' ),
				'value'  => '',
			),
			'show_filters'   => array(
				'type'        => 'switcher',
				'title'       => esc_html__( 'Show filter by category before services listing', 'cherry-services' ),
				'value'       => 'false',
				'toggle'      => array(
					'true_toggle'  => esc_html__( 'Yes', 'cherry-services' ),
					'false_toggle' => esc_html__( 'No', 'cherry-services' ),
				),
			),
			'image_size'     => array(
				'type'       => 'select',
				'title'      => esc_html__( 'Listing item image size (if used in template)', 'cherry-services' ),
				'value'      => 'thumbnail',
				'options'    => false,
				'options_cb' => array( cherry_services_tools(), 'get_image_sizes' ),
			),
			'template'       => array(
				'type'       => 'select',
				'title'      => esc_html__( 'Listing item template', 'cherry-services' ),
				'value'      => 'default',
				'options'    => false,
				'options_cb' => array( cherry_services_tools(), 'get_templates' ),
			),
			'use_space'      => array(
				'type'        => 'switcher',
				'title'       => esc_html__( 'Add space between services coumns', 'cherry-services' ),
				'value'       => 'true',
				'toggle'      => array(
					'true_toggle'  => esc_html__( 'Yes', 'cherry-services' ),
					'false_toggle' => esc_html__( 'No', 'cherry-services' ),
				),
			),
			'use_rows_space' => array(
				'type'        => 'switcher',
				'title'       => esc_html__( 'Add space between services rows', 'cherry-services' ),
				'value'       => 'true',
				'toggle'      => array(
					'true_toggle'  => esc_html__( 'Yes', 'cherry-services' ),
					'false_toggle' => esc_html__( 'No', 'cherry-services' ),
				),
			),
		) );

	}

	/**
	 * Returns services categories list.
	 *
	 * @return array
	 */
	public function get_categories() {

		$tax        = cherry_services_list()->tax( 'category' );
		$categories = cherry_services_list()->utilities->utility->satellite->get_terms_array( $tax, 'slug' );

		if ( empty( $categories ) ) {
			$categories = array();
		}

		$categories = array_merge( array( 0 => esc_html__( 'Get From All', 'cherry-services' ) ), $categories );

		return $categories;
	}

	/**
	 * Register services shortcode for shortcodes builder
	 *
	 * @return void
	 */
	public function register_shrtcode_for_builder() {

		cherry_services_list()->get_core()->init_module( 'cherry5-insert-shortcode', array() );

		cherry5_register_shortcode(
			array(
				'title'       => esc_html__( 'Services', 'cherry-services' ),
				'description' => esc_html__( 'Showcase your services with Cherry Services List plugin', 'cherry-services' ),
				'icon'        => '<span class="dashicons dashicons-lightbulb"></span>',
				'slug'        => 'cherry-services-plugin',
				'shortcodes'  => array(
					array(
						'title'       => esc_html__( 'Services', 'cherry-projects' ),
						'description' => esc_html__( 'Shortcode is used to display the services list', 'cherry-services' ),
						'icon'        => '<span class="dashicons dashicons-lightbulb"></span>',
						'slug'        => $this->tag(),
						'options'     => $this->shortcode_args(),
					),
				),
			)
		);
	}

	/**
	 * Set defaults callback.
	 *
	 * @param array &$item Shortcode fields data.
	 */
	public function set_defaults( &$item ) {
		$item = $item['value'];
	}

	/**
	 * The shortcode function.
	 *
	 * @since  1.0.0
	 * @param  array  $atts      The user-inputted arguments.
	 * @param  string $content   The enclosed content (if the shortcode is used in its enclosing form).
	 * @param  string $shortcode The shortcode tag, useful for shared callback functions.
	 * @return string
	 */
	public function do_shortcode( $atts, $content = null, $shortcode = 'cherry_services' ) {

		// Set up the default arguments.
		$defaults = $this->shortcode_args();
		array_walk( $defaults, array( $this, 'set_defaults' ) );

		/**
		 * Parse the arguments.
		 *
		 * @link http://codex.wordpress.org/Function_Reference/shortcode_atts
		 */
		$atts = shortcode_atts( $defaults, $atts, $shortcode );

		// Fix integers.
		if ( isset( $atts['posts_per_page'] ) ) {
			$atts['posts_per_page'] = intval( $atts['posts_per_page'] );
		}

		if ( isset( $atts['image_size'] ) &&  ( 0 < intval( $atts['image_size'] ) ) ) {
			$atts['image_size'] = intval( $atts['image_size'] );
		} else {
			$atts['image_size'] = esc_attr( $atts['image_size'] );
		}

		$col_classes = '';

		// Fix columns
		foreach ( array( 'columns', 'columns_laptop', 'columns_tablet', 'columns_phone' ) as $col ) {
			$atts[ $col ] = ( 0 !== intval( $atts[ $col ] ) ) ? intval( $atts[ $col ] ) : 3;
		}

		$templates        = cherry_services_templater()->get_listing_templates_list();
		$atts['template'] = isset( $templates[ $atts['template'] ] ) ? $templates[ $atts['template'] ] : 'default.tmpl';

		$bool_to_fix = array(
			'show_title',
			'show_media',
			'show_content',
			'show_item_more',
			'show_filters',
			'ajax_more',
			'more',
			'pagination',
			'use_space',
			'use_rows_space',
		);

		// Fix booleans.
		foreach ( $bool_to_fix as $v ) {

			if ( ! isset( $atts[ $v ] ) ) {
				continue;
			}

			$atts[ $v ] = filter_var( $atts[ $v ], FILTER_VALIDATE_BOOLEAN );
		}

		if ( true === $atts['more'] ) {
			$atts['pagination'] = false;
		}

		$relations = array(
			'limit'          => 'posts_per_page',
			'id'             => 'id',
			'category'       => 'category',
			'size'           => 'image_size',
			'excerpt_length' => 'excerpt_length',
			'col_xs'         => 'columns_phone',
			'col_sm'         => 'columns_tablet',
			'col_md'         => 'columns_laptop',
			'col_xl'         => 'columns',
			'show_title'     => 'show_title',
			'show_media'     => 'show_media',
			'show_content'   => 'show_content',
			'show_item_more' => 'show_item_more',
			'item_more_text' => 'item_more_text',
			'show_filters'   => 'show_filters',
			'template'       => 'template',
			'pager'          => 'pagination',
			'more'           => 'more',
			'more_text'      => 'more_text',
			'more_url'       => 'more_url',
			'ajax_more'      => 'ajax_more',
			'use_space'      => 'use_space',
			'use_rows_space' => 'use_rows_space',
		);

		foreach ( $relations as $data_key => $atts_key ) {

			if ( ! isset( $atts[ $atts_key ] ) ) {
				continue;
			}

			$data_args[ $data_key ] = $atts[ $atts_key ];
		}

		// Make sure we return and don't echo.
		$data_args['echo'] = false;

		if ( ! empty( $data_args['item_class'] ) ) {
			$data_args['item_class'] .= $col_classes;
		} else {
			$data_args['item_class'] = trim( $col_classes );
		}

		$data_args['item_class'] .= ' services-item';

		if ( ! empty( $data_args['id'] ) ) {
			$data_args['orderby'] = 'none';
		}

		$heading = apply_filters(
			'cherry_services_shortcode_heading_format',
			array(
				'super_title' => '<h5 class="services-heading_super_title">%s</h5>',
				'title'       => '<h3 class="services-heading_title">%s</h3>',
				'subtitle'    => '<h6 class="services-heading_subtitle">%s</h6>',
			)
		);

		ob_start();

		echo '<div class="services-container">';

		/**
		 * Hook fires before titles output in services list shortcode started
		 *
		 * @param array $atts Shortcode attributes.
		 */
		do_action( 'cherry_services_before_headings', $atts );

		foreach ( $heading as $item => $format ) {

			if ( empty( $atts[ $item ] ) ) {
				continue;
			}

			printf( $format, $atts[ $item ] );
		}

		/**
		 * Hook fires after titles output in services list shortcode started
		 *
		 * @param array $atts Shortcode attributes.
		 */
		do_action( 'cherry_services_after_headings' );

		$before = ob_get_clean();

		$after = '</div>';

		$this->data = new Cherry_Services_List_Data( $data_args );
		return $before . $this->data->the_services() . $after;
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

Cherry_Services_List_Shortcode::get_instance();
