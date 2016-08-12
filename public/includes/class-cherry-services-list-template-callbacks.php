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
	 * Constructor for the class
	 *
	 * @since 1.0.0
	 * @param array $atts input attributes array.
	 */
	function __construct( $atts ) {
		$this->atts = $atts;
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

		$cta_type = get_post_meta( $post->ID, 'cherry-services-cta-type', true );

		$title = get_post_meta( $post->ID, 'cherry-services-cta-title', true );
		$descr = get_post_meta( $post->ID, 'cherry-services-cta-descr', true );

		if ( 'form' === $cta_type ) {
			$action = $this->get_cta_form();
		} else {
			$action = $this->get_cta_link();
		}

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
	 * Return CTA form HTML
	 *
	 * @return string
	 */
	public function get_cta_form() {

		global $post;
		$form = get_post_meta( $post->ID, 'cherry-services-cta-form', true );

		if ( empty( $form ) ) {
			return;
		}

		$defaults = array(
			'type'  => 'text',
			'width' => '1',
			'name'  => 'name',
			'label' => __( 'Label', 'cherry-services' ),
		);

		$fields_format = apply_filters(
			'cherry_services_cta_fields_formats',
			array(
				'text'     => '<input type="text" name="%1$s" value="" placeholder="%2$s">',
				'textarea' => '<textarea name="%1$s" placeholder="%2$s"></textarea>',
			)
		);

		$result = '';

		foreach ( $form as $field ) {

			$field = wp_parse_args( $field, $defaults );
			$result .= sprintf( $fields_format[ $field['type'] ], $field['name'], $field['label'] );

		}

		return $result;

	}

	/**
	 * Return CTA link
	 *
	 * @return string
	 */
	public function get_cta_link() {

	}

	/**
	 * Get post thumbnail
	 *
	 * @since  1.0.0
	 * @param  array $args Arguments array.
	 * @return string
	 */
	public function get_image( $args = array() ) {

		if ( isset( $this->atts['show_image'] ) && false === $this->atts['show_image'] ) {
			return;
		}

		global $post;

		$args = wp_parse_args( $args, array(
			'wrap'  => 'div',
			'class' => '',
			'size'  => ! empty( $this->atts['size'] ) ? esc_attr( $this->atts['size'] ) : 'thumbnail',
			'link'  => true,
		) );

		$photo = $this->post_image();

		if ( ! $photo ) {
			return;
		}

		$args['link'] = filter_var( $args['link'], FILTER_VALIDATE_BOOLEAN );

		if ( true === $args['link'] ) {
			$format = '<a href="%2$s">%1$s</a>';
			$link   = $this->post_permalink();
		} else {
			$format = '%1$s';
			$link   = false;
		}

		if ( true === $this->atts['show_image'] || 'yes' === $this->atts['show_image'] ) {
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
		return $this->get_meta_html( 'descr', $args );
	}

	/**
	 * Show service font icon
	 *
	 * @return string
	 */
	public function get_icon( $args = array() ) {

		global $post;
		$icon = get_post_meta( $post->ID, 'cherry-services-icon', true );

		if ( ! $icon ) {
			return;
		}

		$args = wp_parse_args( $args, array(
			'wrap'   => 'div',
			'class'  => '',
			'format' => apply_filters( 'cherry_services_default_icon_format', '<i class="fa %s"></i>' ),
		) );

		$result = '<div class="service-icon">' . sprintf( $args['format'], $icon ) . '</div>';

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

		$args = wp_parse_args( $args, array(
			'wrap'  => 'div',
			'class' => '',
		) );

		$result = '';
		$feature_format = apply_filters(
			'cherry_services_feature_format',
			'<div class="service-features_row">
				<span class="service-features_label">%1$s</span><span class="service-features_value">%2$s</span>
			</div>'
		);

		foreach ( $features as $feature ) {
			$result .= sprintf( $feature_format, $feature['label'], $feature['value'] );
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

		if ( isset( $this->atts['show_name'] ) && false === $this->atts['show_name'] ) {
			return;
		}

		$args = wp_parse_args( $args, array(
			'wrap'  => 'div',
			'class' => '',
			'link'  => false
		) );

		$result       = $this->post_title();
		$args['link'] = filter_var( $args['link'], FILTER_VALIDATE_BOOLEAN );

		if ( true === $args['link'] ) {
			$result = '<a href="' . get_permalink() . '">' . $result . '</a>';
		}

		return $this->macros_wrap( $args, $result );
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

		if ( empty( $args ) ) {
			$args['class'] = $class;
		} else {
			$args['class'] .= ' ' . $class;
		}

		return ( ! empty( $value ) ) ? $this->meta_wrap( $value, $args ) : '';
	}

	/**
	 * Get post content
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_content() {

		$content = apply_filters( 'the_content', get_the_content() );

		if ( ! $content ) {
			return;
		}

		$format = '<div class="post-content">%s</div>';

		return sprintf( $format, $content );
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

		return sprintf( '<%1$s class="%2$s">%3$s</%1$s>', $tag, $class, $string );

	}

}
