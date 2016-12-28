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
			'columns_laptop' => 3,
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
		foreach ( array( 'columns', 'columns_laptop', 'columns_tablet', 'columns_phone' ) as $col ) {
			$atts[ $col ] = ( 0 !== intval( $atts[ $col ] ) ) ? intval( $atts[ $col ] ) : 3;
		}

		$templates        = cherry_services_templater()->get_listing_templates_list();
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
			'col_md'         => 'columns_laptop',
			'col_xl'         => 'columns',
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
