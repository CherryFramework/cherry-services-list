<?php
/**
 * The Template for displaying archive CPT Services.
 *
 * @package   Cherry_Services_List
 * @author    Cherry Team
 * @license   GPL-2.0+
 * @link      http://www.cherryframework.com/
 * @copyright 2015 Cherry Team
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

get_header( 'services' );
?>

	<?php
		/**
		 * Fires before main content output started
		 */
		do_action( 'cherry_services_before_main_content' );
	?>
	<?php if ( apply_filters( 'cherry_services_show_page_title', true ) ) : ?>
		<?php cherry_services_tools()->page_title( '<h1 class="page-title">%s</h1>' ); ?>
	<?php endif; ?>
	<div class="services-container">
	<?php

		global $wp_query;

		$cols = cherry_services_tools()->get_cols();

		$args = array(
			'template'   => cherry_services_tools()->get_template( 'listing' ),
			'wrap_class' => 'services-wrap cherry-services-container',
			'container'  => '<div class="services-listing cherry-services-row">%s</div>',
			'item_class' => 'services-item',
			'col_xs'     => $cols['xs'],
			'col_sm'     => $cols['sm'],
			'col_md'     => $cols['md'],
			'col_xl'     => false,
			'size'       => cherry_services_list()->get_option( 'listing-image-size', 'thumbnail' ),
			'pager'      => true,
			'more'       => false,
			'limit'      => cherry_services_list()->get_option( 'posts-per-page', 10 ),
			'category'   => ! empty( $wp_query->query_vars['term'] ) ? $wp_query->query_vars['term'] : '',
		);

		$data = new Cherry_Services_List_Data( $args );
		$data->the_services();
	?>
	</div>
	<?php
		/**
		 * Fires after main content output
		 */
		do_action( 'cherry_services_after_main_content' );
	?>

	<?php
		/**
		 * Hook for placing page sidebar
		 */
		do_action( 'cherry_services_sidebar' );
	?>

<?php get_footer( 'services' );
