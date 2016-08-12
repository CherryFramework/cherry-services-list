<?php
/**
 * The Template for displaying archive CPT Team listing.
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

global $wp_query;

$cols = cherry_team_members_tools()->get_cols();

$args = array(
	'template'   => cherry_team_members_tools()->get_template( 'listing' ),
	'wrap_class' => 'team-wrap container',
	'container'  => '<div class="team-listing row">%s</div>',
	'item_class' => 'team-listing_item',
	'col_xs'     => $cols['xs'],
	'col_sm'     => $cols['sm'],
	'col_md'     => $cols['md'],
	'col_lg'     => false,
	'size'       => 'thumbnail',
	'pager'      => true,
	'limit'      => cherry_team_members()->get_option( 'posts-per-page', 10 ),
	'group'      => ! empty( $wp_query->query_vars['term'] ) ? $wp_query->query_vars['term'] : '',
);

$data = new Cherry_Team_Members_Data;
$data->the_team( $args );
