<?php
/**
 * PHP abstract class for base compatibility with Visual Composer plugin.
 *
 * @author     Template Monster
 * @license    GPL-3.0+
 * @copyright  2002-2016, Template Monster
 */

abstract class TM_Abstract_VC_Compat {

	/**
	 * List of shortcode attributes.
	 *
	 * @since 1.1.0
	 * @var array
	 */
	public $atts = array();

	/**
	 * List of shortcode params.
	 *
	 * @since 1.1.0
	 * @var array
	 */
	public $params = array();

	/**
	 * Constructor
	 */
	public function __construct() {}

	public function get_params() {

		if ( empty( $this->params ) ) {

			foreach ( $this->atts as $name => $attr ) {
				$params = array(
					'heading'     => $attr['title'],
					'description' => ! empty( $attr['description'] ) ? $attr['description'] : '',
					'type'        => $this->_get_attr_type( $attr ),
					'value'       => $this->_get_attr_value( $attr ),
					'param_name'  => $name,
				);

				if ( ! empty( $attr['default'] ) ) {
					$params = array_merge( $params, array( 'std' => $this->_get_attr_std( $attr ) ) );
				}

				if ( ! empty( $attr['master'] ) ) {
					$params = array_merge( $params, array( 'dependency' => $this->_get_attr_deps( $attr ) ) );
				}

				$this->params[ $name ] = $params;
			}
		}

		return $this->params;
	}

	public function _get_attr_type( $attr ) {
		$type = $attr['type'];

		switch ( $type ) {

			case 'textarea':
				$vc_type = 'textarea';
				break;

			case 'switcher':
				$vc_type = 'checkbox';
				break;

			case 'select':
			case 'radio':
			case 'checkbox':
				$vc_type = 'dropdown';
				break;

			default:
				$vc_type = 'textfield';
				break;
		}

		return $vc_type;
	}

	public function _get_attr_value( $attr ) {
		$type = $attr['type'];

		switch ( $type ) {
			case 'select':
			case 'radio':
			case 'checkbox':
				$options  = empty( $attr['options'] ) ? $this->apply_options_cb( $attr ) : $attr['options'];
				$_options = array();

				foreach ( $options as $option => $data ) {

					if ( is_array( $data ) ) {
						$_options[ $option ] = $data['label'];

					} else {
						$_options[ $option ] = $data;
					}
				}

				$value = array_flip( $_options );
				break;

			case 'switcher':
				$filtered = filter_var( $attr['value'], FILTER_VALIDATE_BOOLEAN );
				$value    = $filtered ? array( esc_html__( 'Yes', 'cherry-testi' ) => 'yes' ) : false;
				break;

			default:
				$value = $attr['value'];
				break;
		}

		return $value;
	}

	public function _get_attr_std( $attr ) {
		$type = $attr['type'];

		if ( 'switcher' === $type ) {
			$filtered = filter_var( $attr['default'], FILTER_VALIDATE_BOOLEAN );
			$std      = $filtered ? 'yes' : false;

		} else {
			$std = $attr['default'];
		}

		return $std;
	}

	public function _get_attr_deps( $attribute ) {
		$master = $attribute['master'];
		$deps   = array();

		foreach ( $this->atts as $key => $attr ) {
			$type = $attr['type'];

			switch ( $type ) {
				case 'select':
				case 'radio':
				case 'checkbox':

					if ( ! empty( $attr['options'] ) ) {
						foreach ( $attr['options'] as $option => $data ) {

							if ( ! is_array( $data ) ) {
								continue;
							}

							if ( empty( $data['slave'] ) ) {
								continue;
							}

							if ( $master == $data['slave'] ) {
								$deps = array(
									'element' => $key,
									'value'   => $option,
								);

								break;
							}
						}
					}
					break;

				case 'switcher':

					if ( ! empty( $attr['toggle']['true_slave'] ) ) {
						$slave = $attr['toggle']['true_slave'];

						if ( $master == $slave ) {
							$deps = array(
								'element' => $key,
								'value'   => 'yes',
							);
						}
					}

					break;

				default:

					if ( ! empty( $attr['slave'] ) ) {
						$deps = array(
							'element' => $key,
							'value'   => $attr['value'],
						);
					}

					break;
			}
		}

		return $deps;
	}

	/**
	 * Apply shortcode options callback if required.
	 *
	 * @since  1.1.0
	 * @param  array $atts
	 * @return array
	 */
	public function apply_options_cb( $atts ) {

		if ( empty( $atts['options_cb'] ) || ! is_callable( $atts['options_cb'] ) ) {
			return array();
		}

		return call_user_func( $atts['options_cb'] );
	}
}
