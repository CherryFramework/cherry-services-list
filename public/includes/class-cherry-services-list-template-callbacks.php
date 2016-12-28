<?php
/**
 * Define callback functions for templater
 *
 * @package   Cherry_Team_Members
 * @author    Cherry Team
 * @license   GPL-2.0+
 * @link      http://www.cherryframework.com/
 * @copyright 2015 Cherry Team
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Callbcks for services shortcode templater
 *
 * @since  1.0.0
 */
class Cherry_Services_List_Template_Callbacks {

	/**
	 * Shortcode attributes array
	 * @var array
	 */
	public $atts = array();

	/**
	 * Specific post data
	 * @var array
	 */
	public $post_data = array();

	/**
	 * Parent WP_Query.
	 *
	 * @var object
	 */
	private $parent_query = null;

	/**
	 * Testimonial data array.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	public $testi_data = null;

	/**
	 * Constructor for the class
	 *
	 * @since 1.0.0
	 * @param array $atts input attributes array.
	 */
	function __construct( $atts ) {
		$this->atts = $atts;
		add_filter( 'tm_testimonials_item_classes', array( $this, 'pass_column_classes' ), 10, 2 );
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
	 * Clear post data after loop iteration
	 *
	 * @since  1.0.3
	 * @return void
	 */
	public function clear_data() {
		$this->post_data = array();
	}

	/**
	 * Get post title
	 *
	 * @since  1.0.3
	 * @return string
	 */
	public function post_title() {
		if ( ! isset( $this->post_data['title'] ) ) {
			$this->post_data['title'] = get_the_title();
		}
		return $this->post_data['title'];
	}

	/**
	 * Get post permalink
	 *
	 * @since  1.0.3
	 * @return string
	 */
	public function post_permalink() {
		if ( ! isset( $this->post_data['permalink'] ) ) {
			$this->post_data['permalink'] = get_permalink();
		}
		return $this->post_data['permalink'];
	}

	/**
	 * Get the image for the given ID. If no featured image, check for Gravatar e-mail.
	 *
	 * @since  1.0.0
	 * @param  string $size Image size.
	 * @return string
	 */
	public function post_image( $size = null ) {

		global $post;

		if ( ! isset( $this->post_data['image'] ) ) {

			if ( ! has_post_thumbnail( $post->ID ) ) {
				return false;
			}

			$this->post_data['image'] = '';

			if ( ! $size ) {
				// If not a string or an array, and not an integer, default to 150x9999.
				$size = isset( $this->atts['size'] ) ? $this->atts['size'] : 150;
			}

			if ( is_integer( $size ) ) {
				$size = array( $size, $size );
			} elseif ( ! is_string( $size ) ) {
				$size = 'thumbnail';
			}

			$this->post_data['image'] = get_the_post_thumbnail(
				intval( $post->ID ),
				$size,
				array( 'class' => 'avatar', 'alt' => $this->post_title() )
			);
		}

		return $this->post_data['image'];
	}

	/**
	 * Returns Call to Action block for single service
	 *
	 * @param  array  $args Callback arguments.
	 * @return string
	 */
	public function get_cta( $args = array() ) {

		global $post;
		$show_cta = get_post_meta( $post->ID, 'cherry-services-show-cta', true );

		if ( ! is_array( $show_cta ) || empty( $show_cta['enable'] ) || 'true' !== $show_cta['enable'] ) {
			return;
		}

		$cta_type   = get_post_meta( $post->ID, 'cherry-services-cta-type', true );
		$type_class = 'service-cta cta-type-' . $cta_type;

		$title = get_post_meta( $post->ID, 'cherry-services-cta-title', true );
		$descr = get_post_meta( $post->ID, 'cherry-services-cta-descr', true );

		if ( 'form' === $cta_type ) {
			$action = $this->get_cta_form();
		} else {
			$action = $this->get_cta_link();
		}

		if ( ! empty( $args['class'] ) ) {
			$args['class'] .= ' ' . $type_class;
		} else {
			$args['class'] = $type_class;
		}

		$cta_class      = 'cta-' . $post->ID . '_wrap';
		$args['class'] .= sprintf( ' %s', $cta_class );
		$args['base']   = 'cta_wrap ' . $cta_class;

		$this->add_cta_styling( $cta_class );

		/**
		 * Filter CTA block HTML format
		 *
		 * %1$s - Title
		 * %2$s - Description
		 * %3%s - Action block (form or button)
		 *
		 * @var string
		 */
		$cta_format = apply_filters(
			'cherry_services_cta_format',
			'<h3 class="service-cta_title">%1$s</h3><div class="service-cta_desc">%2$s</div>%3$s'
		);

		return $this->macros_wrap( $args, sprintf( $cta_format, $title, $descr, $action ) );
	}

	/**
	 * Add CTA styles
	 *
	 *@param  string $class CTA CSS class.
	 */
	public function add_cta_styling( $class ) {

		global $post;

		$selectros = apply_filters( 'cherry_services_cta_selectors', array(
			'title' => '.service-cta_title',
			'text'  => '.service-cta_desc',
		) );

		$base = '.' . $class;

		$bg = array(
			'background-image'    => 'cherry-services-cta-bg-image',
			'background-color'    => 'cherry-services-cta-bg-color',
			'background-position' => 'cherry-services-cta-bg-position',
			'background-repeat'   => 'cherry-services-cta-bg-repeat',
			'background-size'     => 'cherry-services-cta-bg-size',
		);

		$bg_result = array();

		foreach ( $bg as $property => $meta ) {

			$value = get_post_meta( $post->ID, $meta, true );

			if ( ! $value ) {
				continue;
			}

			if ( 'background-image' !== $property ) {
				$bg_result[ $property ] = esc_attr( $value );
				continue;
			}

			$url = wp_get_attachment_url( $value );

			if ( ! empty( $url ) ) {
				$bg_result[ $property ] = sprintf( 'url("%s")', esc_url( $url ) );
			}

		}

		if ( ! empty( $bg_result ) ) {
			cherry_services_list()->dynamic_css->add_style( $base, $bg_result );
		}

		$colors = array(
			'text'  => 'cherry-services-cta-text-color',
			'title' => 'cherry-services-cta-title-color',
		);

		foreach ( $colors as $selector => $meta ) {
			$color = get_post_meta( $post->ID, $meta, true );
			if ( ! empty( $color ) ) {
				cherry_services_list()->dynamic_css->add_style(
					sprintf( '%s %s', $base, $selectros[ $selector ] ),
					array(
						'color' => esc_attr( $color ),
					)
				);
			}
		}

	}

	/**
	 * Return CTA form HTML
	 *
	 * @return string
	 */
	public function get_cta_form() {

		global $post;

		$custom_form = apply_filters( 'cherry_services_custom_cta_form', false );

		if ( false !== $custom_form ) {
			return $custom_form;
		}

		$form = get_post_meta( $post->ID, 'cherry-services-cta-form', true );

		if ( empty( $form ) ) {
			return;
		}

		$sumbit_text = get_post_meta( $post->ID, 'cherry-services-cta-submit', true );

		$defaults = array(
			'type'     => 'text',
			'width'    => '1',
			'name'     => 'name',
			'label'    => __( 'Label', 'cherry-services' ),
			'required' => 'yes',
		);

		$fields_format = apply_filters(
			'cherry_services_cta_fields_formats',
			array(
				'text'     => '<input type="text" name="%1$s" class="cta-form_control" value="" placeholder="%2$s" %3$s>',
				'email'    => '<input type="email" name="%1$s" class="cta-form_control" value="" placeholder="%2$s" %3$s>',
				'textarea' => '<textarea name="%1$s" class="cta-form_control" placeholder="%2$s" %3$s></textarea>',
			)
		);

		$submit_format = apply_filters(
			'cherry_services_cta_submit_format',
			'<button type="submit" class="cta-form_submit btn">%s</button>'
		);

		$form_before = apply_filters( 'cherry_services_cta_form_before', '<form class="cta-form" method="post">' );
		$form_after  = apply_filters( 'cherry_services_cta_form_after', '</form>' );

		$message = cherry_services_list_form()->get_message( $post->ID );

		if ( ! empty( $message ) ) {
			$form_before .= sprintf( '<div class="cta-form_success">%s</div>', esc_html( $message ) );
			wp_cache_delete( $post->ID, 'cherry-services' );
		}

		$result = '';

		foreach ( $form as $field ) {

			$field    = wp_parse_args( $field, $defaults );
			$name     = 'service-cta[' . $field['name'] . ']';
			$required = ( isset( $field['required'] ) && 'no' !== $field['required'] ) ? 'required' : '';
			$control  = sprintf( $fields_format[ $field['type'] ], $name, $field['label'], $required );
			$result  .= $this->wrap_form_control( $control, $field['width'] );

		}

		$ref      = isset( $this->parent_query->post->ID ) ? get_permalink( $this->parent_query->post->ID ) : false;
		$hidden   = '<input type="hidden" name="cherry-services-form" value="' . $post->ID . '">';
		$hidden  .= '<input type="hidden" name="cherry-services-ref" value="' . esc_url( $ref ) . '">';
		$controls = sprintf( '<div class="cherry-services-row">%s</div>', $result );
		$submit   = sprintf( $submit_format, esc_html( $sumbit_text ) );

		return $form_before . $hidden . $controls . $submit . $form_after;
	}

	/**
	 * Wrap form control into div with column class
	 *
	 * @param  string $control Control HTML.
	 * @param  string $width   Width value.
	 * @return string
	 */
	public function wrap_form_control( $control, $width = '1' ) {

		switch ( $width ) {

			case '1/3':
				$column_classes = 'col-md-4 col-sm-4 col-xs-12';
				break;

			case '1/2':
				$column_classes = 'col-md-6 col-sm-6 col-xs-12';
				break;

			case '2/3':
				$column_classes = 'col-md-8 col-sm-8 col-xs-12';
				break;

			default:
				$column_classes = 'col-md-12';
				break;
		}

		return sprintf( '<div class="%s">%s</div>', $column_classes, $control );

	}

	/**
	 * Return CTA link
	 *
	 * @return string
	 */
	public function get_cta_link() {

		global $post;

		$link_text = get_post_meta( $post->ID, 'cherry-services-cta-link-text', true );
		$link_url  = get_post_meta( $post->ID, 'cherry-services-cta-link-url', true );

		if ( empty( $link_text ) || empty( $link_url ) ) {
			return;
		}

		$link_format = apply_filters(
			'cherry_services_cta_link_format',
			'<div class="cta-button-wrap"><a href="%s" div class="cta-button btn">%s</a></div>'
		);

		return sprintf( $link_format, esc_url( $link_url ), esc_html( $link_text ) );

	}

	/**
	 * Get post thumbnail
	 *
	 * @since  1.0.0
	 * @param  array $args Arguments array.
	 * @return string
	 */
	public function get_image( $args = array() ) {

		if ( isset( $this->atts['show_media'] ) && false === $this->atts['show_media'] ) {
			return;
		}

		global $post;

		$args = wp_parse_args( $args, array(
			'wrap'  => 'div',
			'class' => '',
			'base'  => 'image_wrap',
			'size'  => ! empty( $this->atts['size'] ) ? esc_attr( $this->atts['size'] ) : 'thumbnail',
			'link'  => true,
		) );

		$photo = $this->post_image( $args['size'] );

		if ( ! $photo ) {
			return;
		}

		$args['link']             = filter_var( $args['link'], FILTER_VALIDATE_BOOLEAN );
		$this->atts['show_media'] = filter_var( $this->atts['show_media'], FILTER_VALIDATE_BOOLEAN );

		if ( true === $args['link'] ) {
			$format = '<a href="%2$s">%1$s</a>';
			$link   = $this->post_permalink();
		} else {
			$format = '%1$s';
			$link   = false;
		}

		if ( true === $this->atts['show_media'] ) {
			return $this->macros_wrap( $args, sprintf( $format, $photo, $link ) );
		}

	}

	/**
	 * Show service slogan
	 *
	 * @return string
	 */
	public function get_slogan( $args = array() ) {
		return $this->get_meta_html( 'slogan', $args );
	}

	/**
	 * Show service description
	 *
	 * @return string
	 */
	public function get_desc( $args = array() ) {

		global $post;

		$value = get_post_meta( $post->ID, 'cherry-services-descr', true );
		$class = 'service-descr';

		if ( empty( $args['class'] ) ) {
			$args['class'] = $class;
		} else {
			$args['class'] .= ' ' . $class;
		}

		$args = wp_parse_args( $args, array(
			'base' => 'descr_wrap',
			'crop' => 'no',
			'more' => '&hellip;',
		) );

		$args['crop'] = filter_var( $args['crop'], FILTER_VALIDATE_BOOLEAN );

		if ( true === $args['crop'] ) {
			$length = intval( $this->atts['excerpt_length'] );
			$more   = esc_attr( $args['more'] );
			$value  = wp_trim_words( $value, $length, $more );
		}

		return ( ! empty( $value ) ) ? $this->meta_wrap( $value, $args ) : '';

	}

	/**
	 * Show service font icon
	 *
	 * @return string
	 */
	public function get_icon( $args = array() ) {

		if ( ! isset( $this->atts['show_media'] ) ) {
			return;
		}

		$this->atts['show_media'] = filter_var( $this->atts['show_media'], FILTER_VALIDATE_BOOLEAN );

		if ( true !== $this->atts['show_media'] ) {
			return;
		}

		global $post;
		$icon = get_post_meta( $post->ID, 'cherry-services-icon', true );

		if ( ! $icon ) {
			return;
		}

		$args = wp_parse_args( $args, array(
			'wrap'   => 'div',
			'class'  => '',
			'base'   => 'icon_wrap',
			'format' => apply_filters( 'cherry_services_default_icon_format', '<i class="fa %s"></i>' ),
		) );

		$result = '<div class="service-icon">' . sprintf( $args['format'], esc_attr( $icon ) ) . '</div>';

		return $this->macros_wrap( $args, $result );
	}

	/**
	 * Get features list
	 *
	 * @return string
	 */
	public function get_features( $args = array() ) {

		global $post;

		$features = get_post_meta( $post->ID, 'cherry-services-features', true );

		if ( empty( $features ) ) {
			return;
		}

		/**
		 * Filter features row format
		 *
		 * @var string Features row HTML format.
		 */
		$feature_format = apply_filters(
			'cherry_services_feature_format',
			'<div class="service-features_row">
				<span class="service-features_label">%1$s</span><span class="service-features_value">%2$s</span>
			</div>'
		);

		/**
		 * Filter features block title format
		 *
		 * @var string Features block title HTML format.
		 */
		$features_title_format = apply_filters(
			'cherry_services_features_title_format',
			'<h3 class="service-features_title">%s</h3>'
		);

		$args = wp_parse_args( $args, array(
			'wrap'   => 'div',
			'format' => $feature_format,
			'class'  => '',
			'base'   => 'features_wrap',
		) );

		$result = '';

		$features_title = get_post_meta( $post->ID, 'cherry-services-features-title', true);

		if ( $features_title ) {
			$result .= sprintf( $features_title_format, $features_title );
		}

		foreach ( $features as $feature ) {
			$result .= sprintf( $feature_format, esc_html( $feature['label'] ), esc_html( $feature['value'] ) );
		}

		return $this->macros_wrap( $args, sprintf( '<div class="service-features">%s</div>', $result ) );

	}

	/**
	 * Get service title
	 *
	 * @since  1.0.0
	 * @param  array $args Arguments array.
	 * @return string
	 */
	public function get_title( $args = array() ) {

		global $post;

		if ( isset( $this->atts['show_title'] ) && false === $this->atts['show_title'] ) {
			return;
		}

		$args = wp_parse_args( $args, array(
			'wrap'  => 'div',
			'class' => '',
			'link'  => false,
			'base'  => 'title_wrap',
		) );

		$result       = $this->post_title();
		$args['link'] = filter_var( $args['link'], FILTER_VALIDATE_BOOLEAN );

		if ( true === $args['link'] ) {
			$result = '<a href="' . get_permalink() . '">' . $result . '</a>';
		}

		return $this->macros_wrap( $args, $result );
	}

	/**
	 * Get read more button
	 *
	 * @since  1.0.0
	 * @param  array $args Arguments array.
	 * @return string
	 */
	public function get_button( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'class' => 'btn btn-primary',
			'label' => __( 'Read more', 'cherry-services' ),
		) );

		$format = apply_filters(
			'cherry_services_button_format',
			'<a href="%1$s" class="%2$s">%3$s</a>'
		);

		return sprintf( $format, get_permalink(), $args['class'], $args['label'] );
	}

	/**
	 * Geet service testimonials
	 *
	 * @param  array  $args Arguments array.
	 * @return string
	 */
	public function get_testi( $args = array() ) {

		global $post;

		$this->testi_data = $data = get_post_meta( $post->ID, 'cherry-services-testi', true );

		if ( empty( $data['show']['enable'] ) || 'true' !== $data['show']['enable'] ) {
			return;
		}

		$args = wp_parse_args( $args, array(
			'wrap'  => 'div',
			'class' => '',
			'base'  => 'testi_wrap',
		) );

		if ( ! class_exists( 'TM_Testimonials_Data' ) ) {
			return;
		}

		$atts = array(
			'category'        => esc_attr( Cherry_Toolkit::get_arg( $data, 'cat', '' ) ),
			'title'           => wp_kses_post( Cherry_Toolkit::get_arg( $data, 'title', '' ) ),
			'type'            => esc_attr( Cherry_Toolkit::get_arg( $data, 'type', 'list' ) ),
			'limit'           => esc_attr( Cherry_Toolkit::get_arg( $data, 'limit', 3 ) ),
			'show_avatar'     => esc_attr( Cherry_Toolkit::get_arg( $data, 'show-avatar', 'on' ) ),
			'size'            => esc_attr( Cherry_Toolkit::get_arg( $data, 'size', 100 ) ),
			'content_length'  => esc_attr( Cherry_Toolkit::get_arg( $data, 'content-length', 55 ) ),
			'show_email'      => esc_attr( Cherry_Toolkit::get_arg( $data, 'show-email', 'on' ) ),
			'show_position'   => esc_attr( Cherry_Toolkit::get_arg( $data, 'show-position', 'on' ) ),
			'show_company'    => esc_attr( Cherry_Toolkit::get_arg( $data, 'show-company', 'on' ) ),
			'loop'            => esc_attr( Cherry_Toolkit::get_arg( $data, 'loop', 'on' ) ),
			'pagination'      => esc_attr( Cherry_Toolkit::get_arg( $data, 'pagination', 'on' ) ),
			'navigation'      => esc_attr( Cherry_Toolkit::get_arg( $data, 'navigation', 'on' ) ),
			'slides_per_view' => esc_attr( Cherry_Toolkit::get_arg( $data, 'slides-per-view', 1 ) ),
			'space_between'   => esc_attr( Cherry_Toolkit::get_arg( $data, 'space-between', 15 ) ),
			'template'        => esc_attr( Cherry_Toolkit::get_arg( $data, 'template', 'default.tmpl' ) ),
			'is_service'      => true,
			'container'       => '<div class="tm-testi__list cherry-services-row">%s</div>',
			'custom_class'    => 'services-testi',
		);

		// Fix bool
		$bool = array(
			'show_avatar',
			'show_email',
			'show_position',
			'show_company',
			'pagination',
			'navigation',
		);
		foreach ( $bool as $att ) {
			if ( 'on' === $atts[ $att ] ) {
				$atts[ $att ] = true;
			} else {
				$atts[ $att ] = false;
			}
		}

		/**
		 * Never pass empty template.
		 */
		if ( empty( $atts['template'] ) ) {
			$atts['template'] = 'default.tmpl';
		}

		$data = new TM_Testimonials_Data;
		ob_start();
		$data->the_testimonials( $atts );
		return $this->macros_wrap( $args, ob_get_clean() );
	}

	/**
	 * Checks if is testimonials
	 *
	 * @return array
	 */
	public function pass_column_classes( $classes = array(), $args ) {

		if ( empty( $args['is_service'] ) ) {
			return $classes;
		}

		if ( empty( $this->testi_data['cols'] ) ) {
			$cols = 1;
		} else {
			$cols = $this->testi_data['cols'];
		}

		$columns   = 12 / $cols;
		$classes[] = 'col-md-' . $columns;

		return $classes;
	}

	/**
	 * Gets metadata by name and return HTML markup
	 *
	 * @param  string $meta Meta name to get
	 * @return string
	 */
	public function get_meta_html( $meta, $args = array() ) {

		global $post;

		$value = get_post_meta( $post->ID, 'cherry-services-' . $meta, true );
		$class = 'service-' . $meta;

		if ( empty( $args['class'] ) ) {
			$args['class'] = $class;
		} else {
			$args['class'] .= ' ' . $class;
		}

		$args = wp_parse_args( $args, array(
			'base' => $meta . '_wrap',
		) );

		return ( ! empty( $value ) ) ? $this->meta_wrap( $value, $args ) : '';
	}

	/**
	 * Get post content
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_content( $args = array() ) {

		if ( ! isset( $this->atts['show_content'] ) ) {
			return;
		}

		$this->atts['show_content'] = filter_var( $this->atts['show_content'], FILTER_VALIDATE_BOOLEAN );

		if ( ! $this->atts['show_content'] ) {
			return;
		}

		global $post;

		$_content       = apply_filters( 'cherry_services_content', get_the_content( '' ), $post );
		$content_length = intval( $this->atts['excerpt_length'] );

		$args = wp_parse_args( $args, array(
			'wrap'  => 'div',
			'class' => '',
			'base'  => 'content_wrap',
		) );

		if ( ! $_content || 0 == $content_length ) {
			return;
		}

		if ( -1 == $content_length || post_password_required() ) {
			$content = apply_filters( 'the_content', $_content );
		} else {
			/* wp_trim_excerpt analog */
			$content = strip_shortcodes( $_content );
			$content = apply_filters( 'the_content', $content );
			$content = str_replace( ']]>', ']]&gt;', $content );
			$content = wp_trim_words( $content, $content_length, '' );
			$content = '<p>' . $content . '</p>';
		}

		return $this->macros_wrap( $args, $content );
	}

	/**
	 * Get link URL to services member page
	 */
	public function get_link() {
		global $post;
		return $this->post_permalink();
	}

	/**
	 * Wrap single services item into HTML wrapper with custom class
	 *
	 * @since  1.0.0
	 * @param  string $value meta value.
	 * @param  string $args  arguments array.
	 * @return string
	 */
	public function meta_wrap( $value = null, $args = array() ) {

		if ( ! $value ) {
			return;
		}

		$args = wp_parse_args( $args, array(
			'wrap'  => 'div',
			'class' => '',
		) );

		return $this->macros_wrap( $args, $value );

	}

	/**
	 * Wrap macros output into wrapper passed via arguments
	 *
	 * @param  array  $args   Arguments array.
	 * @param  string $string Macros string to wrap.
	 * @return string
	 */
	public function macros_wrap( $args = array(), $string = '' ) {

		if ( ! $string ) {
			return '';
		}

		$tag   = ! empty( $args['wrap'] ) ? esc_attr( $args['wrap'] ) : 'div';
		$class = ! empty( $args['class'] ) ? esc_attr( $args['class'] ) : 'services-macros';

		global $post;

		$open_container = $close_container = '';

		$layout = get_post_meta( $post->ID, 'cherry-services-single-layout', true );

		if ( 'boxed' === $layout && is_singular( cherry_services_list()->post_type() ) ) {
			$open_container  = '<div class="container">';
			$close_container = '</div>';
		}

		$open_wrap = $close_wrap = '';

		$base = empty( $args['base'] ) || 'false' !== $args['base'] ? true : false;

		if ( $base ) {
			$open_wrap  = sprintf( '<div class="%s">', esc_attr( $args['base'] ) );
			$close_wrap = '</div>';
		}

		return sprintf(
			'%6$s%4$s<%1$s class="%2$s">%3$s</%1$s>%5$s%7$s',
			$tag, $class, $string, $open_container, $close_container, $open_wrap, $close_wrap
		);

	}

}
