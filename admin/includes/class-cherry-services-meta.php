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

		add_filter( 'post_row_actions', array( $this, 'duplicate_link' ), 10, 2 );
		add_action( 'admin_action_cherry_services_clone_post', array( $this, 'duplicate_post_as_draft' ) );

		add_filter( 'cherry_post_meta_custom_box', array( $this, 'check_testi' ), 10, 3 );

	}

	/**
	 * Add 'Clone' link into posts actions list
	 *
	 * @param  array  $actions Available actions.
	 * @param  object $post    Current post.
	 * @return [type]          [description]
	 */
	public function duplicate_link( $actions, $post ) {

		if ( ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}

		if ( $this->post_type() !== $post->post_type ) {
			return $actions;
		}

		$url = add_query_arg(
			array(
				'action' => 'cherry_services_clone_post',
				'post'   => $post->ID,
			),
			admin_url( 'admin.php' )
		);

		$actions['clone'] = sprintf(
			'<a href="%1$s" title="%3$s" rel="permalink">%2$s</a>',
			$url,
			__( 'Clone', 'cherry-services' ),
			__( 'Clone this post', 'cherry-services' )
		);

		return $actions;
	}

	/**
	 * Process post cloning
	 */
	function duplicate_post_as_draft() {

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You don\'t have permissions to do this', 'cherry-services' ) );
		}

		if ( empty( $_REQUEST['action'] ) || 'cherry_services_clone_post' !== $_REQUEST['action'] ) {
			wp_die( __( 'Not allowed function call!', 'cherry-services' ) );
		}

		if ( empty( $_REQUEST['post'] ) ) {
			wp_die( __( 'No post to duplicate has been supplied!', 'cherry-services' ) );
		}

		global $wpdb;

		$post_id         = absint( $_REQUEST['post'] );
		$post            = get_post( $post_id );
		$current_user    = wp_get_current_user();
		$new_post_author = $current_user->ID;

		if ( ! $post ) {
			wp_die(
				sprintf( __( 'Post creation failed, could not find original post: %s', 'cherry-services' ) ),
				$post_id
			);
		}

		$args = array(
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'post_author'    => $new_post_author,
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_name'      => $post->post_name,
			'post_parent'    => $post->post_parent,
			'post_password'  => $post->post_password,
			'post_status'    => 'draft',
			'post_title'     => $post->post_title,
			'post_type'      => $post->post_type,
			'to_ping'        => $post->to_ping,
			'menu_order'     => $post->menu_order
		);

		$new_post_id = wp_insert_post( $args );

		$post_terms = wp_get_object_terms( $post_id, 'group', array( 'fields' => 'slugs' ) );
		wp_set_object_terms( $new_post_id, $post_terms, 'group', false );

		$post_meta_infos = $wpdb->get_results(
			"SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = $post_id"
		);

		if ( 0 !== count( $post_meta_infos ) ) {

			$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";

			foreach ( $post_meta_infos as $meta_info ) {

				$meta_key        = $meta_info->meta_key;
				$meta_value      = addslashes( $meta_info->meta_value );
				$sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";

			}

			$sql_query.= implode( " UNION ALL ", $sql_query_sel );
			$wpdb->query( $sql_query );
		}

		wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );

		exit;
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

		wp_enqueue_style( 'cherry-services-admin' );
	}

	/**
	 * Check - if testimonials plugin is not active - return notice.
	 *
	 * @param  bool   $box_content Current box content (bool false by default).
	 * @param  object $post        Post object.
	 * @param  array  $box_data    Current metabox data.
	 * @return bool|string
	 */
	public function check_testi( $box_content, $post, $box_data ) {

		if ( empty( $box_data['id'] ) || 'service-testi' !== $box_data['id'] ) {
			return $box_content;
		}

		if ( class_exists( 'TM_Testimonials_Plugin' ) ) {
			return $box_content;
		}

		return esc_html__( 'Testimonials management are not available. Please, install Cherry Testi plugin' );

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
					'tabs' => array(
						'element'     => 'component',
						'type'        => 'component-tab-horizontal',
					),
					'general' => array(
						'type'    => 'settings',
						'element' => 'settings',
						'parent'  => 'tabs',
						'title'   => esc_html__( 'General', 'cherry-services' ),
					),
					'features' => array(
						'type'    => 'settings',
						'element' => 'settings',
						'parent'  => 'tabs',
						'title'   => esc_html__( 'Features', 'cherry-services' ),
					),
					'cta' => array(
						'type'    => 'settings',
						'element' => 'settings',
						'parent'  => 'tabs',
						'title'   => esc_html__( 'Call To Action', 'cherry-services' ),
					),
					'styling' => array(
						'type'    => 'settings',
						'element' => 'settings',
						'parent'  => 'tabs',
						'title'   => esc_html__( 'Styling', 'cherry-services' ),
					),
					'cherry-services-icon' => array(
						'type'        => 'iconpicker',
						'element'     => 'control',
						'parent'      => 'general',
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
						'element'     => 'control',
						'parent'      => 'general',
						'placeholder' => esc_html__( 'Slogan', 'cherry-services' ),
						'label'       => esc_html__( 'Slogan', 'cherry-services' ),
					),
					'cherry-services-descr' => array(
						'type'              => 'textarea',
						'element'           => 'control',
						'parent'            => 'general',
						'placeholder'       => esc_html__( 'Short Description', 'cherry-services' ),
						'label'             => esc_html__( 'Short Description', 'cherry-services' ),
						'sanitize_callback' => 'wp_kses_post',
					),
					'cherry-services-features-title' => array(
						'type'        => 'text',
						'element'     => 'control',
						'parent'      => 'features',
						'placeholder' => esc_html__( 'Features Title', 'cherry-services' ),
						'label'       => esc_html__( 'Features Title', 'cherry-services' ),
					),
					'cherry-services-features' => array(
						'type'        => 'repeater',
						'element'     => 'control',
						'parent'      => 'features',
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
					'cherry-services-show-cta' => array(
						'type'    => 'checkbox',
						'element' => 'control',
						'parent'  => 'cta',
						'options' => array(
							'enable' => esc_html__( 'Enable CTA block', 'cherry-services' ),
						),
					),
					'cherry-services-cta-title' => array(
						'type'        => 'text',
						'element'     => 'control',
						'parent'      => 'cta',
						'placeholder' => esc_html__( 'Title', 'cherry-services' ),
						'label'       => esc_html__( 'Title', 'cherry-services' ),
					),
					'cherry-services-cta-descr' => array(
						'type'        => 'textarea',
						'element'     => 'control',
						'parent'      => 'cta',
						'placeholder' => esc_html__( 'Description', 'cherry-services' ),
						'label'       => esc_html__( 'Description', 'cherry-services' ),
					),
					'cherry-services-cta-type' => array(
						'type'    => 'radio',
						'element' => 'control',
						'parent'  => 'cta',
						'label'   => esc_html__( 'Call to Action Type', 'cherry-services' ),
						'value'   => 'form',
						'options' => array(
							'form' => array(
								'label' => esc_html__( 'Contact Form', 'cherry-services' ),
								'slave' => 'cherry-services-cta-type-form',
							),
							'button' => array(
								'label' => esc_html__( 'Link', 'cherry-services' ),
								'slave' => 'cherry-services-cta-type-button',
							),
						),
					),
					'cherry-services-cta-form' => array(
						'type'        => 'repeater',
						'element'     => 'control',
						'parent'      => 'cta',
						'label'       => esc_html__( 'Form Fields', 'cherry-services' ),
						'add_label'   => esc_html__( 'Add New Field', 'cherry-services' ),
						'title_field' => 'label',
						'master'      => 'cherry-services-cta-type-form',
						'fields'      => array(
							'type'  => array(
								'type'        => 'select',
								'id'          => 'type',
								'name'        => 'type',
								'placeholder' => esc_html__( 'Field Type', 'cherry-services' ),
								'label'       => esc_html__( 'Type', 'cherry-services' ),
								'options'     => array(
									'text'     => __( 'Text', 'cherry-sevices' ),
									'email'    => __( 'Email', 'cherry-services' ),
									'textarea' => __( 'Textarea', 'cherry-services' ),
								),
								'sanitize_callback' => 'esc_attr',
							),
							'label' => array(
								'type'             => 'text',
								'id'               => 'label',
								'name'             => 'label',
								'placeholder'      => esc_html__( 'Field Label', 'cherry-services' ),
								'label'            => esc_html__( 'Label', 'cherry-services' ),
								'sanitize_callback' => 'sanitize_text_field',
							),
							'name' => array(
								'type'             => 'text',
								'id'               => 'name',
								'name'             => 'name',
								'placeholder'      => esc_html__( 'Field Name', 'cherry-services' ),
								'label'            => esc_html__( 'Name(Should be unique)', 'cherry-services' ),
								'sanitize_callback' => 'esc_attr',
							),
							'width'  => array(
								'type'        => 'select',
								'id'          => 'width',
								'name'        => 'width',
								'label'       => esc_html__( 'Field Width', 'cherry-services' ),
								'options'     => array(
									'1'   => __( 'Fullwidth', 'cherry-sevices' ),
									'1/3' => __( '1/3', 'cherry-services' ),
									'1/2' => __( '1/2', 'cherry-services' ),
									'2/3' => __( '2/3', 'cherry-services' ),
								),
								'sanitize_callback' => 'esc_attr',
							),
							'required'  => array(
								'type'        => 'select',
								'id'          => 'required',
								'name'        => 'required',
								'label'       => esc_html__( 'Is Required Field?', 'cherry-services' ),
								'options'     => array(
									'yes' => __( 'Yes', 'cherry-sevices' ),
									'no'  => __( 'No', 'cherry-services' ),
								),
								'sanitize_callback' => 'esc_attr',
							),
						),
						'sanitize_callback' => array( $this, 'sanitize_repeater' ),
					),
					'cherry-services-cta-submit' => array(
						'type'              => 'text',
						'element'           => 'control',
						'parent'            => 'cta',
						'master'            => 'cherry-services-cta-type-form',
						'placeholder'       => esc_html__( 'Form Submit Button Text', 'cherry-services' ),
						'label'             => esc_html__( 'Form Submit Button Text', 'cherry-services' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'cherry-services-form-message' => array(
						'type'              => 'text',
						'element'           => 'control',
						'parent'            => 'cta',
						'value'             => esc_html__( 'Thanks for your request', 'cherry-services' ),
						'master'            => 'cherry-services-cta-type-form',
						'label'             => esc_html__( 'Success Message', 'cherry-services' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'cherry-services-cta-link-text' => array(
						'type'              => 'text',
						'element'           => 'control',
						'parent'            => 'cta',
						'master'            => 'cherry-services-cta-type-button',
						'placeholder'       => esc_html__( 'Button Text', 'cherry-services' ),
						'label'             => esc_html__( 'CTA Button Text', 'cherry-services' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'cherry-services-cta-link-url' => array(
						'type'              => 'text',
						'element'           => 'control',
						'parent'            => 'cta',
						'master'            => 'cherry-services-cta-type-button',
						'placeholder'       => esc_html__( 'Button URL', 'cherry-services' ),
						'label'             => esc_html__( 'CTA Button URL', 'cherry-services' ),
						'sanitize_callback' => 'esc_url',
					),
					'cherry-services-single-layout' => array(
						'type'              => 'select',
						'element'           => 'control',
						'parent'            => 'styling',
						'value'             => 'fullwidth',
						'options'           => array(
							'fullwidth' => esc_html__( 'Fullwidth', 'cherry-services' ),
							'boxed'     => esc_html__( 'Boxed', 'cherry-services' ),
						),
						'label'             => esc_html__( 'Single page content', 'cherry-services' ),
						'sanitize_callback' => 'esc_attr',
					),
					'cherry-services-cta-bg-image' => array(
						'type'               => 'media',
						'element'            => 'control',
						'parent'             => 'styling',
						'multi_upload'       => false,
						'library_type'       => 'image',
						'upload_button_text' => esc_html__( 'Add Image', 'cherry-services' ),
						'label'              => esc_html__( 'Call to action background image', 'cherry-services' ),
						'sanitize_callback'  => 'esc_attr',
					),
					'cherry-services-cta-bg-color' => array(
						'type'              => 'colorpicker',
						'element'           => 'control',
						'parent'            => 'styling',
						'label'             => esc_html__( 'Call to action background color', 'cherry-services' ),
						'sanitize_callback' => 'esc_attr',
					),
					'cherry-services-cta-bg-position' => array(
						'type'               => 'select',
						'element'            => 'control',
						'parent'             => 'styling',
						'value'              => 'center center',
						'options'            => array(
							'left top'      => esc_html__( 'Left Top', 'cherry-services' ),
							'center top'    => esc_html__( 'Center Top', 'cherry-services' ),
							'right top'     => esc_html__( 'Right Top', 'cherry-services' ),
							'left center'   => esc_html__( 'Left Center', 'cherry-services' ),
							'center center' => esc_html__( 'Center Center', 'cherry-services' ),
							'right center'  => esc_html__( 'Right Center', 'cherry-services' ),
							'left bottom'   => esc_html__( 'Left Bottom', 'cherry-services' ),
							'center bottom' => esc_html__( 'Center Bottom', 'cherry-services' ),
							'right bottom'  => esc_html__( 'Right Bottom', 'cherry-services' ),
						),
						'label'              => esc_html__( 'Call to action background position', 'cherry-services' ),
						'sanitize_callback'  => 'esc_attr',
					),
					'cherry-services-cta-bg-repeat' => array(
						'type'               => 'select',
						'element'            => 'control',
						'parent'             => 'styling',
						'value'              => 'repeat',
						'options'            => array(
							'repeat'    => esc_html__( 'Repeat', 'cherry-services' ),
							'repeat-x'  => esc_html__( 'Repeat X', 'cherry-services' ),
							'repeat-y'  => esc_html__( 'Repeat Y', 'cherry-services' ),
							'no-repeat' => esc_html__( 'No Repeat', 'cherry-services' ),
						),
						'label'              => esc_html__( 'Call to action background repeat', 'cherry-services' ),
						'sanitize_callback'  => 'esc_attr',
					),
					'cherry-services-cta-bg-size' => array(
						'type'              => 'select',
						'element'           => 'control',
						'parent'            => 'styling',
						'value'             => 'repeat',
						'options'           => array(
							'auto'    => esc_html__( 'Auto', 'cherry-services' ),
							'cover'   => esc_html__( 'Cover', 'cherry-services' ),
							'contain' => esc_html__( 'Contain', 'cherry-services' ),
						),
						'label'             => esc_html__( 'Call to action background image size', 'cherry-services' ),
						'sanitize_callback' => 'esc_attr',
					),
					'cherry-services-cta-text-color' => array(
						'type'              => 'colorpicker',
						'element'           => 'control',
						'parent'            => 'styling',
						'label'             => esc_html__( 'Call to action text color', 'cherry-services' ),
						'sanitize_callback' => 'esc_attr',
					),
					'cherry-services-cta-title-color' => array(
						'type'              => 'colorpicker',
						'element'           => 'control',
						'parent'            => 'styling',
						'label'             => esc_html__( 'Call to action title color', 'cherry-services' ),
						'sanitize_callback' => 'esc_attr',
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
			apply_filters( 'cherry_services_list_meta_testi_args', array(
				'id'            => 'service-testi',
				'single'        => array( 'key' => 'cherry-services-testi' ),
				'title'         => esc_html__( 'Testimonials', 'cherry-services' ),
				'page'          => array( $this->post_type() ),
				'context'       => 'normal',
				'priority'      => 'low',
				'callback_args' => false,
				'fields'        => array(
					'show' => array(
						'type'    => 'checkbox',
						'id'      => $this->get_field_id( 'cherry-services-testi', 'show' ),
						'name'    => $this->get_field_name( 'cherry-services-testi', 'show' ),
						'options' => array(
							'enable' => esc_html__( 'Enable testimonials block', 'cherry-services' ),
						),
					),
					'title' => array(
						'type'              => 'text',
						'id'                => $this->get_field_id( 'cherry-services-testi', 'title' ),
						'name'              => $this->get_field_name( 'cherry-services-testi', 'title' ),
						'placeholder'       => esc_html__( 'Testimonials block title', 'cherry-services' ),
						'label'             => esc_html__( 'Title', 'cherry-services' ),
						'sanitize_callback' => 'wp_kses_post',
					),
					'cat'  => array(
						'type'              => 'select',
						'id'                => $this->get_field_id( 'cherry-services-testi', 'cat' ),
						'name'              => $this->get_field_name( 'cherry-services-testi', 'cat' ),
						'label'             => esc_html__( 'Get testimonials from category:', 'cherry-services' ),
						'options'           => false,
						'options_callback'  => array( $this, 'get_testi_cat' ),
						'sanitize_callback' => 'esc_attr',
					),
					'cols'  => array(
						'type'              => 'select',
						'id'                => $this->get_field_id( 'cherry-services-testi', 'cols' ),
						'name'              => $this->get_field_name( 'cherry-services-testi', 'cols' ),
						'label'             => esc_html__( 'Columns number:', 'cherry-services' ),
						'options'           => array(
							1 => 1,
							2 => 2,
							3 => 3,
							4 => 4,
						),
						'sanitize_callback' => 'esc_attr',
					),
					'limit' => array(
						'type'              => 'slider',
						'id'                => $this->get_field_id( 'cherry-services-testi', 'limit' ),
						'name'              => $this->get_field_name( 'cherry-services-testi', 'limit' ),
						'max_value'         => 30,
						'min_value'         => -1,
						'value'             => 3,
						'step_value'        => 1,
						'label'             => esc_html__( 'Testimonials limit (set -1 to show all)' ),
						'sanitize_callback' => array( $this, 'sanitize_num' ),
					),
					'show-avatar' => array(
						'type'              => 'select',
						'id'                => $this->get_field_id( 'cherry-services-testi', 'show-avatar' ),
						'name'              => $this->get_field_name( 'cherry-services-testi', 'show-avatar' ),
						'label'             => esc_html__( 'Show avatar?', 'cherry-services' ),
						'options'           => array(
							'on'  => __( 'Yes', 'cherry-sevices' ),
							'off' => __( 'No', 'cherry-services' ),
						),
						'sanitize_callback' => 'esc_attr',
					),
					'size' => array(
						'type'              => 'slider',
						'id'                => $this->get_field_id( 'cherry-services-testi', 'size' ),
						'name'              => $this->get_field_name( 'cherry-services-testi', 'size' ),
						'max_value'         => 512,
						'min_value'         => 1,
						'value'             => 100,
						'step_value'        => 1,
						'label'             => esc_html__( 'Testimonial avatar size' ),
						'sanitize_callback' => array( $this, 'sanitize_num' ),
					),
					'content-length'  => array(
						'type'              => 'slider',
						'id'                => $this->get_field_id( 'cherry-services-testi', 'content-length' ),
						'name'              => $this->get_field_name( 'cherry-services-testi', 'content-length' ),
						'max_value'         => 100,
						'min_value'         => -1,
						'value'             => 55,
						'step_value'        => 1,
						'label'             => esc_html__( 'Content Length (set -1 to show full content)' ),
						'sanitize_callback' => array( $this, 'sanitize_num' ),
					),
					'show-email'    => array(
						'type'        => 'select',
						'id'                => $this->get_field_id( 'cherry-services-testi', 'show-email' ),
						'name'              => $this->get_field_name( 'cherry-services-testi', 'show-email' ),
						'label'       => esc_html__( 'Show email?', 'cherry-services' ),
						'options'     => array(
							'on'  => __( 'Yes', 'cherry-sevices' ),
							'off' => __( 'No', 'cherry-services' ),
						),
						'sanitize_callback' => 'esc_attr',
					),
					'show-position' => array(
						'type'        => 'select',
						'id'                => $this->get_field_id( 'cherry-services-testi', 'show-position' ),
						'name'              => $this->get_field_name( 'cherry-services-testi', 'show-position' ),
						'label'       => esc_html__( 'Show position?', 'cherry-services' ),
						'options'     => array(
							'on'  => __( 'Yes', 'cherry-sevices' ),
							'off' => __( 'No', 'cherry-services' ),
						),
						'sanitize_callback' => 'esc_attr',
					),
					'show-company'  => array(
						'type'        => 'select',
						'id'                => $this->get_field_id( 'cherry-services-testi', 'show-company' ),
						'name'              => $this->get_field_name( 'cherry-services-testi', 'show-company' ),
						'label'       => esc_html__( 'Show company?', 'cherry-services' ),
						'options'     => array(
							'on'  => __( 'Yes', 'cherry-sevices' ),
							'off' => __( 'No', 'cherry-services' ),
						),
						'sanitize_callback' => 'esc_attr',
					),
					'template' => array(
						'type'              => 'text',
						'id'                => $this->get_field_id( 'cherry-services-testi', 'template' ),
						'name'              => $this->get_field_name( 'cherry-services-testi', 'template' ),
						'value'             => 'default.tmpl',
						'placeholder'       => esc_html__( 'Template name', 'cherry-services' ),
						'label'             => esc_html__( 'Custom template', 'cherry-services' ),
						'sanitize_callback' => 'esc_attr',
					),
				),
			)
		) );

	}

	/**
	 * Sanitize numeric values.
	 *
	 * @param  string $value Value to sanitize.
	 * @return int
	 */
	public function sanitize_num( $value ) {
		return intval( $value );
	}

	/**
	 * Returns field name
	 *
	 * @param  string $base Base key.
	 * @param  string $key  Nested key.
	 * @return string
	 */
	public function get_field_name( $base = 'cherry-services', $key = '' ) {
		return sprintf( '%s[%s]', $base, $key );
	}

	/**
	 * Returns field ID
	 *
	 * @param  string $base Base key.
	 * @param  string $key  Nested key.
	 * @return string
	 */
	public function get_field_id( $base = 'cherry-services', $key = '' ) {
		return sprintf( '%s-%s', $base, $key );
	}

	/**
	 * Get testimonials categories for service
	 *
	 * @return array
	 */
	public function get_testi_cat() {

		$terms  = get_terms( array( 'taxonomy' => 'tm-testimonials_category', 'hide_empty' => false ) );
		$result = array();

		if ( ! empty( $terms ) ) {
			$result = wp_list_pluck( $terms, 'name', 'term_id' );
		}

		$result[0] = esc_html__( 'Select category...', 'cherry-services' );
		ksort( $result );

		return $result;
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

		return $new_value;
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
