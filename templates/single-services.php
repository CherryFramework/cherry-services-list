<?php
/**
 * The Template for displaying single CPT Team.
 *
 * @package   Cherry_Team_Members
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
	<?php

		while ( have_posts() ) :

			the_post(); ?>

			<article <?php post_class(); ?>>
			<?php

				do_action( 'cherry_post_before' );

				$args = array(
					'id'             => get_the_ID(),
					'template'       => cherry_services_tools()->get_template( 'single' ),
					'custom_class'   => 'services-page-single',
					'size'           => cherry_services_list()->get_option( 'single-image-size', 'thumbnail' ),
					'container'      => false,
					'item_class'     => 'services-single-item',
					'pager'          => false,
					'more'           => false,
					'excerpt_length' => -1,
				);

				$data = new Cherry_Services_List_Data( $args );
				$data->the_services();
			?>
			</article>

			<?php do_action( 'cherry_post_after' ); ?>

	<?php endwhile; ?>

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
