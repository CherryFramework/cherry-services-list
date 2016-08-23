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
		add_action( 'init', array( $this, 'register_shortcode' ) );

	}

	/**
	 * Registers the [$this->name] shortcode.
	 *
	 * @since 1.0.0
	 */
	public function register_shortcode() {

		/**
		 * Filters a shortcode name.
		 *
		 * @since 1.0.0
		 * @param string $this->name Shortcode name.
		 */
		$tag = apply_filters( self::$name . '_shortcode_name', self::$name );

		add_shortcode( $tag, array( $this, 'do_shortcode' ) );
	}

	/**
	 * Filter to modify original shortcodes data and add [$this->name] shortcode.
	 *
	 * @since  1.0.0
	 * @param  array $shortcodes Original plugin shortcodes.
	 * @return array             Modified array.
	 */
	public function shortcodes( $shortcodes ) {

		$terms_list = array();

		if ( did_action( 'wp_ajax_cherry_shortcodes_generator_settings' ) ) {
			$terms = get_terms( 'group' );

			if ( ! is_wp_error( $terms ) ) {
				$terms_list = wp_list_pluck( $terms, 'name', 'slug' );
			}
		}

		$sizes_list = array();
		if ( class_exists( 'Cherry_Shortcodes_Tools' ) && method_exists( 'Cherry_Shortcodes_Tools', 'image_sizes' ) ) {
			$sizes_list = Cherry_Shortcodes_Tools::image_sizes();
		}

		$shortcodes[ self::$name ] = array(
			'name'  => __( 'Team', 'cherry-services' ), // Shortcode name.
			'desc'  => __( 'Cherry services shortcode', 'cherry-services' ),
			'type'  => 'single', // Can be 'wrap' or 'single'. Example: [b]this is wrapped[/b], [this_is_single]
			'group' => 'content', // Can be 'content', 'box', 'media' or 'other'. Groups can be mixed
			'atts'  => array( // List of shortcode params (attributes).
				'limit' => array(
					'type'    => 'slider',
					'min'     => -1,
					'max'     => 100,
					'step'    => 1,
					'default' => 3,
					'name'    => __( 'Limit', 'cherry-services' ),
					'desc'    => __( 'Maximum number of posts.', 'cherry-services' ),
				),
				'order' => array(
					'type' => 'select',
					'values' => array(
						'desc' => __( 'Descending', 'cherry-services' ),
						'asc'  => __( 'Ascending', 'su' ),
					),
					'default' => 'DESC',
					'name' => __( 'Order', 'cherry-services' ),
					'desc' => __( 'Posts order', 'cherry-services' ),
				),
				'orderby' => array(
					'type' => 'select',
					'values' => array(
						'none'          => __( 'None', 'cherry-services' ),
						'id'            => __( 'Post ID', 'cherry-services' ),
						'author'        => __( 'Post author', 'cherry-services' ),
						'title'         => __( 'Post title', 'cherry-services' ),
						'name'          => __( 'Post slug', 'cherry-services' ),
						'date'          => __( 'Date', 'cherry-services' ),
						'modified'      => __( 'Last modified date', 'cherry-services' ),
						'rand'          => __( 'Random', 'cherry-services' ),
						'comment_count' => __( 'Comments number', 'cherry-services' ),
						'menu_order'    => __( 'Menu order', 'cherry-services' ),
					),
					'default' => 'date',
					'name'    => __( 'Order by', 'cherry-services' ),
					'desc'    => __( 'Order posts by', 'cherry-services' ),
				),
				'category' => array(
					'type'     => 'select',
					'multiple' => true,
					'values'   => $terms_list,
					'default'  => '',
					'name'     => __( 'Category', 'cherry-services' ),
					'desc'     => __( 'Select groups to show services members from', 'cherry-services' ),
				),
				'id' => array(
					'default' => 0,
					'name'    => __( 'Post ID\'s', 'cherry-services' ),
					'desc'    => __( 'Enter comma separated ID\'s of the posts that you want to show', 'cherry-services' ),
				),
				'show_title' => array(
					'type'    => 'bool',
					'default' => 'yes',
					'name' => __( 'Show name?', 'cherry-services' ),
					'desc'    => __( 'Show name?', 'cherry-services' ),
				),
				'show_media' => array(
					'type'    => 'bool',
					'default' => 'yes',
					'name' => __( 'Show photo?', 'cherry-services' ),
					'desc'    => __( 'Show photo?', 'cherry-services' ),
				),
				'size' => array(
					'type'    => 'select',
					'values'  => $sizes_list,
					'default' => 'thumbnail',
					'name'    => __( 'Featured image size', 'cherry-services' ),
					'desc'    => __( 'Select size for a Featured image', 'cherry-services' ),
				),
				'excerpt_length' => array(
					'type'    => 'slider',
					'min'     => 5,
					'max'     => 150,
					'step'    => 1,
					'default' => 20,
					'name'    => __( 'Excerpt Length', 'cherry-services' ),
					'desc'    => __( 'Excerpt length (if used in template)', 'cherry-services' ),
				),
				'col' => array(
					'type'    => 'responsive',
					'default' => array(
						'col_xs' => 'none',
						'col_sm' => 'none',
						'col_md' => 'none',
						'col_lg' => 'none',
					),
					'name'    => __( 'Column class', 'cherry-services' ),
					'desc'    => __( 'Column class for each item.', 'cherry-services' ),
				),
				'template' => array(
					'type'   => 'select',
					'values' => array(
						'default.tmpl' => 'default.tmpl',
					),
					'default' => 'default.tmpl',
					'name'    => __( 'Template', 'cherry-services' ),
					'desc'    => __( 'Shortcode template', 'cherry-services' ),
				),
				'class' => array(
					'default' => '',
					'name'    => __( 'Class', 'cherry-services' ),
					'desc'    => __( 'Extra CSS class', 'cherry-services' ),
				),
			),
			'icon'     => 'users', // Custom icon (font-awesome).
			'function' => array( $this, 'do_shortcode' ), // Name of shortcode function.
		);

		return $shortcodes;
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
		$defaults = array(
			'super_title'    => '',
			'title'          => '',
			'subtitle'       => '',
			'columns'        => 3,
			'columns_tablet' => 2,
			'columns_phone'  => 1,
			'posts_per_page' => 6,
			'category'       => '',
			'id'             => 0,
			'excerpt_length' => 20,
			'more'           => true,
			'more_text'      => __( 'More', 'cherry-services' ),
			'more_url'       => '#',
			'ajax_more'      => true,
			'pagination'     => false,
			'show_title'     => true,
			'show_media'     => true,
			'show_content'   => true,
			'show_position'  => true,
			'show_social'    => true,
			'show_filters'   => false,
			'image_size'     => 'thumbnail',
			'template'       => 'default',
			'use_space'      => true,
			'use_rows_space' => true,
		);

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
		foreach ( array( 'columns', 'columns_tablet', 'columns_phone' ) as $col ) {
			$atts[ $col ] = ( 0 !== intval( $atts[ $col ] ) ) ? intval( $atts[ $col ] ) : 3;
		}

		$templates        = cherry_services_templater()->get_templates_list();
		$atts['template'] = isset( $templates[ $atts['template'] ] ) ? $templates[ $atts['template'] ] : 'default.tmpl';

		$bool_to_fix = array(
			'show_title',
			'show_media',
			'show_content',
			'show_position',
			'show_social',
			'show_filters',
			'ajax_more',
			'more',
			'pagination',
			'use_space',
			'use_rows_space',
		);

		// Fix booleans.
		foreach ( $bool_to_fix as $v ) {
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
			'col_md'         => 'columns',
			'show_title'     => 'show_title',
			'show_media'     => 'show_media',
			'show_content'   => 'show_content',
			'show_position'  => 'show_position',
			'show_social'    => 'show_social',
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
