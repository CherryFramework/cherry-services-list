<?php
/**
 * Cherry Services - add post meta
 *
 * @package   Cherry_Team
 * @author    Cherry Services
 * @license   GPL-2.0+
 * @link      http://www.cherryframework.com/
 * @copyright 2015 Cherry Team
 */

/**
 * Class for post meta management.
 *
 * @since 1.0.0
 */
class Cherry_Services_List_Meta extends Cherry_Services_List {

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

		// Adds the services post type meta.
		add_action( 'admin_init', array( $this, 'init_metaboxes' ) );

		// Enqueue assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

	}

	/**
	 * Enqueue admin styles function.
	 *
	 * @param  string $hook_suffix Current page hook name.
	 * @return void
	 */
	public function enqueue_admin_styles( $hook_suffix ) {

		$allowed_pages = array( 'post-new.php', 'post.php', 'edit.php' );

		if ( ! in_array( $hook_suffix, $allowed_pages ) || $this->post_type() !== get_post_type() ) {
			return;
		}

		wp_enqueue_style(
			'cherry-services-admin',
			$this->plugin_url( 'admin/assets/css/cherry-services.css' ),
			false,
			$this->get_version()
		);
	}

	/**
	 * Loads custom meta boxes on the "Add New Testimonial" and "Edit Testimonial" screens.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init_metaboxes() {

		cherry_services_list()->get_core()->init_module(
			'cherry-post-meta',
			apply_filters( 'cherry_services_list_meta_options_args', array(
				'id'            => 'service-options',
				'title'         => esc_html__( 'Service Options', 'cherry-services' ),
				'page'          => array( $this->post_type() ),
				'context'       => 'normal',
				'priority'      => 'high',
				'callback_args' => false,
				'fields'        => array(
					'cherry-services-icon' => array(
						'type'        => 'iconpicker',
						'label'       => esc_html__( 'Service Icon', 'cherry-services' ),
						'icon_data'       => array(
							'icon_set'    => 'cherryServicesIcons',
							'icon_css'    => $this->plugin_url( 'public/assets/css/font-awesome.min.css' ),
							'icon_base'   => 'fa',
							'icon_prefix' => 'fa-',
							'icons'       => $this->get_icons_set(),
						),
						'sanitize_callback' => 'esc_attr'
					),
					'cherry-services-slogan' => array(
						'type'        => 'text',
						'placeholder' => esc_html__( 'Slogan', 'cherry-services' ),
						'label'       => esc_html__( 'Slogan', 'cherry-services' ),
					),
					'cherry-services-descr' => array(
						'type'              => 'textarea',
						'placeholder'       => esc_html__( 'Short Description', 'cherry-services' ),
						'label'             => esc_html__( 'Short Description', 'cherry-services' ),
						'sanitize_callback' => 'wp_kses_post',
					),
					'cherry-services-features' => array(
						'type'        => 'repeater',
						'label'       => esc_html__( 'Features', 'cherry-services' ),
						'add_label'   => esc_html__( 'Add New Feature', 'cherry-services' ),
						'title_field' => 'label',
						'fields'      => array(
							'label' => array(
								'type'             => 'text',
								'id'               => 'label',
								'name'             => 'label',
								'placeholder'      => esc_html__( 'Feature Label', 'cherry-services' ),
								'label'            => esc_html__( 'Feature Label', 'cherry-services' ),
								'sanitize_callback' => 'sanitize_text_field',
							),
							'value' => array(
								'type'             => 'text',
								'id'               => 'value',
								'name'             => 'value',
								'placeholder'      => esc_html__( 'Feature Value', 'cherry-services' ),
								'label'            => esc_html__( 'Feature Value', 'cherry-services' ),
								'sanitize_callback' => 'sanitize_text_field',
							),
						),
						'sanitize_callback' => array( $this, 'sanitize_repeater' ),
					),
				),
				'admin_columns' => array(
					'thumbnail' => array(
						'label'    => __( 'Thumbnail', 'cherry-services' ),
						'callback' => array( $this, 'show_thumb' ),
						'position' => 1,
					),
					'cherry-services-slogan' => array(
						'label' => __( 'Slogan', 'cherry-services' ),
					),
				),
			)
		) );

		cherry_services_list()->get_core()->init_module(
			'cherry-post-meta',
			apply_filters( 'cherry_services_list_meta_cta_args', array(
				'id'            => 'service-cta',
				'title'         => esc_html__( 'Call To Action Section', 'cherry-services' ),
				'page'          => array( $this->post_type() ),
				'context'       => 'normal',
				'priority'      => 'high',
				'callback_args' => false,
				'fields'        => array(
					'cherry-services-cta-title' => array(
						'type'        => 'text',
						'placeholder' => esc_html__( 'Title', 'cherry-services' ),
						'label'       => esc_html__( 'Title', 'cherry-services' ),
					),
					'cherry-services-cta-descr' => array(
						'type'        => 'textarea',
						'placeholder' => esc_html__( 'Description', 'cherry-services' ),
						'label'       => esc_html__( 'Description', 'cherry-services' ),
					),
					'cherry-services-cta-type' => array(
						'type'        => 'radio',
						'label'       => esc_html__( 'Call to Action Type', 'cherry-services' ),
						'options'     => array(
							'form' => array(
								'label' => esc_html__( 'Call to Action Type', 'cherry-services' ),
								'slave' => 'cherry-services-cta-type-form',
							),
							'button' => array(
								'label' => esc_html__( 'Call to Action Type', 'cherry-services' ),
								'slave' => 'cherry-services-cta-type-button',
							),
						),
					),
					'cherry-services-cta-form' => array(
						'type'        => 'repeater',
						'label'       => esc_html__( 'Form Fields', 'cherry-services' ),
						'add_label'   => esc_html__( 'Add New Field', 'cherry-services' ),
						'title_field' => 'label',
						'master'      => 'cherry-services-cta-type-form',
						'fields'      => array(
							'type'  => array(
								'type'        => 'select',
								'id'          => 'type',
								'name'        => 'type',
								'label'       => esc_html__( 'Field Type', 'cherry-services' ),
								'options'     => array(
									'text'     => __( 'Text', 'cherry-sevices' ),
									'textarea' => __( 'Textarea', 'cherry-services' ),
								),
								'sanitize_callback' => 'esc_attr',
							),
							'label' => array(
								'type'             => 'text',
								'id'               => 'label',
								'name'             => 'label',
								'placeholder'      => esc_html__( 'Field Label', 'cherry-services' ),
								'label'            => esc_html__( 'Field Label', 'cherry-services' ),
								'sanitize_callback' => 'sanitize_text_field',
							),
							'name' => array(
								'type'             => 'text',
								'id'               => 'value',
								'name'             => 'value',
								'placeholder'      => esc_html__( 'Field Name', 'cherry-services' ),
								'label'            => esc_html__( 'Field Name(Should be unique)', 'cherry-services' ),
								'sanitize_callback' => 'esc_attr',
							),
						),
						'sanitize_callback' => array( $this, 'sanitize_repeater' ),
					),
					'cherry-services-cta-submit' => array(
						'type'             => 'text',
						'master'           => 'cherry-services-cta-type-form',
						'placeholder'      => esc_html__( 'Form Submit Button Text', 'cherry-services' ),
						'label'            => esc_html__( 'Form Submit Button Text', 'cherry-services' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'cherry-services-cta-link-text' => array(
						'type'             => 'text',
						'master'           => 'cherry-services-cta-type-button',
						'placeholder'      => esc_html__( 'Button Text', 'cherry-services' ),
						'label'            => esc_html__( 'CTA Button Text', 'cherry-services' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'cherry-services-cta-link-url' => array(
						'type'             => 'text',
						'master'           => 'cherry-services-cta-type-button',
						'placeholder'      => esc_html__( 'Button URL', 'cherry-services' ),
						'label'            => esc_html__( 'CTA Button URL', 'cherry-services' ),
						'sanitize_callback' => 'esc_url',

					),
				),
			)
		) );

	}

	/**
	 * Sanitize features repeater field
	 *
	 * @param  string $value Field value.
	 * @param  string $key   Key value.
	 * @param  array  $field Field data array.
	 * @return string
	 */
	public function sanitize_repeater( $value, $key, $field ) {

		if ( ! is_array( $value ) ) {
			return $value;
		}

		$new_value = array();
		$fields    = $field['fields'];

		foreach ( $value as $index => $row ) {
			$new_value[ $index ] = $this->sanitize_repeater_row( $row, $fields );
		}

	}

	/**
	 * Sanitize single repeater row
	 *
	 * @param  array $row   Single repeater row.
	 * @param  array $field Field data array.
	 * @return array
	 */
	public function sanitize_repeater_row( $row, $field ) {

		$sanitized_row = array();

		foreach ( $row as $col_key => $col_val ) {

			if ( empty( $fields[ $col_key ] ) ) {
				$sanitized_row[ $col_key ] = $col_val;
				continue;
			}

			$column = $fields[ $col_key ];

			if ( isset( $column['sanitize_callback'] ) && is_callable( $column['sanitize_callback'] ) ) {
				$sanitized_row[ $col_key ] = call_user_func( $column['sanitize_callback'] );
			} else {
				$sanitized_row[ $col_key ] = $col_val;
			}
		}

		return $sanitized_row;
	}

	/**
	 * Return FontAwesome icons set for iconpicker
	 *
	 * @return array
	 */
	public function get_icons_set() {

		ob_start();
		include $this->plugin_path( 'admin/assets/js/icons.json' );
		$json = ob_get_clean();

		$result = array();
		$icons  = json_decode( $json, true );

		foreach ( $icons['icons'] as $icon ) {
			$result[] = $icon['id'];
		}

		return $result;
	}

	/**
	 * Show post thumbnail in admin columns
	 *
	 * @return void
	 */
	public function show_thumb( $column, $post_id ) {

		if ( has_post_thumbnail( $post_id ) ) {
			the_post_thumbnail( array( 50, 50 ) );
		}

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
 * Returns Cherry_Services_List_Meta instance.
 *
 * @return object
 */
function cherry_services_list_meta() {
	Cherry_Services_List_Meta::get_instance();
}

cherry_services_list_meta();
