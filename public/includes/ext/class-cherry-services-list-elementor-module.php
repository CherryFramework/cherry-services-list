<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Cherry_Services_Elementor_Widget extends Elementor\Widget_Base {

	/**
	 * Shortcode tag
	 *
	 * @var string
	 */
	protected $tag = 'cherry_services';

	/**
	 * Rewritten shortcode arguments
	 *
	 * @var array
	 */
	protected $rewrite = array(
		'id' => 'post_id',
	);

	/**
	 * Get shortcode data by name
	 *
	 * @param  string $name Data name to get.
	 * @return mixed
	 */
	public function get_shortcode( $name ) {

		if ( ! isset( cherry_services_list_elementor_compat()->shortcodes[ $this->tag ] ) ) {
			return;
		}

		$shortcode = wp_parse_args( cherry_services_list_elementor_compat()->shortcodes[ $this->tag ], array(
			'title' => null,
			'icon'  => 'eicon-shortcode',
			'atts'  => array(),
		) );

		return isset( $shortcode[ $name ] ) ? $shortcode[ $name ] : false;
	}

	public function get_name() {
		return $this->tag;
	}

	public function get_title() {
		return $this->get_shortcode( 'title' );
	}

	public function get_icon() {
		return $this->get_shortcode( 'icon' );
	}

	public function get_categories() {
		return array( 'cherry' );
	}

	public function is_reload_preview_required() {
		return true;
	}

	/**
	 * Map controls
	 *
	 * @param  string $name Default control name.
	 * @return string
	 */
	protected function _get_mapped_control( $name = null ) {

		$mapped_controls = array(
			'media'    => Elementor\Controls_Manager::MEDIA,
			'text'     => Elementor\Controls_Manager::TEXT,
			'textarea' => Elementor\Controls_Manager::TEXTAREA,
			'select'   => Elementor\Controls_Manager::SELECT,
			'switcher' => Elementor\Controls_Manager::SWITCHER,
			'slider'   => Elementor\Controls_Manager::SLIDER,
		);

		if ( isset( $mapped_controls[ $name ] ) ) {
			return $mapped_controls[ $name ];
		} else {
			return false;
		}

	}

	/**
	 * Sanitize attribute arguments data.
	 *
	 * @param  array  $data Input arguments.
	 * @param  string $type Attribute control type.
	 * @return array
	 */
	protected function _sanitize_attr_data( $data ) {

		$type = $this->_get_mapped_control( $data['type'] );

		if ( ! $type ) {
			return;
		}

		$mapped_args = array(
			'label'   => $data['title'],
			'type'    => $type,
			'default' => array( $data['value'] ),
		);

		if ( 'switcher' === $data['type'] ) {
			$mapped_args['default'] = ( 'true' === $data['value'] ) ? 'yes' : '';
		}

		if ( isset( $data['options'] ) ) {
			$mapped_args['options'] = $data['options'];
		}

		if ( isset( $data['options_cb'] ) ) {
			$mapped_args['options'] = call_user_func( $data['options_cb'] );
		}

		if ( isset( $data['min_value'] ) && isset( $data['max_value'] ) ) {
			$mapped_args['default'] = array(
				'size' => $data['value'],
			);
			$mapped_args['range'] = array(
				'px' => array(
					'min' => $data['min_value'],
					'max' => $data['max_value'],
				),
			);
		}

		return $mapped_args;
	}

	/**
	 * Sanitize attribute name
	 *
	 * @param  string $name Attribute name.
	 * @return string
	 */
	protected function _sanitize_attr_name( $name ) {
		return isset( $this->rewrite[ $name ] ) ? $this->rewrite[ $name ] : $name;
	}

	protected function _register_controls() {

		$args = $this->get_shortcode( 'atts' );

		if ( ! $args ) {
			return;
		}

		$this->start_controls_section(
			'section_main',
			array(
				'label' => $this->get_shortcode( 'title' ),
			)
		);

		foreach ( $args as $name => $arg ) {

			$name        = $this->_sanitize_attr_name( $name );
			$mapped_args = $this->_sanitize_attr_data( $arg );

			$this->add_control( $name, $mapped_args );

		}

		$this->end_controls_section();

	}

	protected function render() {

		$settings       = $this->get_settings();
		$shortcode      = '[%1$s%2$s]';
		$shortcode_atts = '';
		$args           = $this->get_shortcode( 'atts' );

		// fix rewritten
		foreach ( $this->rewrite as $before => $after ) {
			$settings[ $before ] = isset( $settings[ $after ] ) ? $settings[ $after ] : false;
		}

		foreach ( $args as $name => $arg ) {

			if ( empty( $settings[ $name ] ) ) {

				if ( 'switcher' === $arg['type'] ) {
					$shortcode_atts .= sprintf( ' %1$s="%2$s"', $name, 'false' );
				}

				continue;
			}

			if ( ! is_array( $settings[ $name ] ) ) {
				$val = $settings[ $name ];
			} else {
				if ( isset( $settings[ $name ]['size'] ) ) {
					$val = $settings[ $name ]['size'];
				} else {
					$val = $settings[ $name ][0];
				}
			}

			$shortcode_atts .= sprintf( ' %1$s="%2$s"', $name, $val );
		}

		?>
		<div class="elementor-<?php $this->tag; ?>"><?php
			if ( cherry_services_list()->elementor_compat->in_elementor() ) {
				echo do_shortcode( sprintf( $shortcode, $this->tag, $shortcode_atts ) );
			} else {
				echo sprintf( $shortcode, $this->tag, $shortcode_atts );
			}
		?></div>
		<?php
	}

	protected function _content_template() {}

	/**
	 * Returns widget instance for register function
	 *
	 * @return object
	 */
	public static function get_instance() {
		return new self();
	}

}
