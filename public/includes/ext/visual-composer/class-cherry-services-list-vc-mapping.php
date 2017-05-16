<?php
/**
 * PHP-class for adding Services-shortcode to the Visual Composer plugin.
 *
 * @package    Cherry_Services_List
 * @subpackage Public
 * @author     Cherry Team
 * @license    GPL-3.0+
 * @copyright  2002-2017, Cherry Team
 */

if ( ! class_exists( 'TM_Abstract_VC_Compat' ) ) {
	require_once( cherry_services_list()->plugin_path( 'public/includes/ext/visual-composer/class-tm-abstract-vc-compat.php' ) );
}

class Cherry_Services_List_VC_Mapping extends TM_Abstract_VC_Compat {

	/**
	 * Shortcode name.
	 *
	 * @since 1.3.1
	 * @var string
	 */
	public $tag = '';

	/**
	 * A reference to an instance of this class.
	 *
	 * @since 1.3.1
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Constructor for the class.
	 *
	 * @since 1.3.1
	 */
	public function __construct( $tag, $atts ) {
		$this->tag  = $tag;
		$this->atts = $atts;

		add_action( 'vc_before_init', array( $this, 'mapping' ) );
		add_filter( 'cherry_services_public_scripts_ver', array( $this, 'scripts_ver' ) );
		parent::__construct();
	}

	/**
	 * Added shortcode to the Visual Composer content elements list.
	 *
	 * @since 1.3.1
	 */
	public function mapping() {
		$_params = $this->get_params();
		$params  = $this->unique_fix( $_params );

		vc_map( array(
			'base'           => $this->tag,
			'name'           => esc_html__( 'Cherry Services List', 'cherry-services' ),
			'description'    => esc_html__( 'Shortcode is used to display the services', 'cherry-services' ),
			'category'       => esc_html__( 'Cherry', 'cherry-services' ),
			'php_class_name' => 'Cherry_Services_List_VC_ShortCode', // important
			'params'         => $params,
		) );
	}

	/**
	 * `Category` control-type fix.
	 *
	 * Cause, e.g. the dropdown(select) control-type is not good for selecting categories.
	 *
	 * @since  1.3.1
	 * @param  array $params
	 * @return array
	 */
	public function unique_fix( $params ) {
		$params['category']['type']  = 'textfield';
		$params['category']['value'] = '';

		return $params;
	}

	/**
	 * Returns the instance.
	 *
	 * @since  1.3.1
	 * @return object
	 */
	public static function get_instance( $tag, $atts ) {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self( $tag, $atts );
		}

		return self::$instance;
	}

	/**
	 * Set dynamic script version.
	 * Don't cache javascript file `cherry-services.js` on fronted-editor mode.
	 *
	 * @since  1.3.1
	 * @param  string $version
	 * @return string
	 */
	public function scripts_ver( $version ) {

		if ( did_action( 'vc_inline_editor_page_view' ) ) {
			return time();
		}

		return $version;
	}
}

if ( class_exists( 'WPBakeryShortCode' ) ) {
	class Cherry_Services_List_VC_ShortCode extends WPBakeryShortCode {

		/**
		 * This methods returns HTML code for frontend representation of your shortcode.
		 * You can use your own html markup.
		 *
		 * @since  1.3.1
		 * @param  $atts    Shortcode attributes.
		 * @param  $content Shortcode content.
		 * @return string
		 */
		protected function content( $atts, $content = null ) {
			$shortcode = Cherry_Services_List_Shortcode::get_instance();

			return $shortcode->do_shortcode( $atts, $content );
		}
	}
}

/**
 * Returns instance of Cherry_Services_List_VC_Mapping.
 *
 * @since  1.3.1
 * @return object
 */
function cherry_services_list_vc_mapping( $tag, $atts ) {
	return Cherry_Services_List_VC_Mapping::get_instance( $tag, $atts );
}
