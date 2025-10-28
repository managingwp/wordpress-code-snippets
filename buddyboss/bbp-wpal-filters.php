<?php
/*
 * Plugin Name: BBP WPAL Filters
 * Plugin URI:
 * Description: BuddyBoss Platform Filters to improve page speed.
 * Version: 1.0.1
 * Author:
 * Author URI:
 * License: Copyright (c) 2012-2021 David Bullock, Web Power and Light
 * Text Domain: bbp-wpal
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Status: Complete
 * Type: mu-plugin
*/

if (! defined('ABSPATH') ) {
	die();
}

/**
 * Filters the users array before the query takes place.
 *
 * Preempt long running queries on user avatar list ( MUTU Mamas )
 *
 * @since WP 5.1.0
 *
 * @param array|null    $results Return an array of user data to short-circuit WP's user query
 *                               or null to allow WP to run its normal queries.
 * @param WP_User_Query $query   The WP_User_Query instance (passed by reference).
 */
add_action( 'users_pre_query', 'bbp_wpal_pre_user_query', PHP_INT_MAX, 2 );
function bbp_wpal_pre_user_query( $results, $query ){
	$key = isset( $query->query_vars['meta_key'] ) ? $query->query_vars['meta_key'] : false;
	if( $key === 'nickname' ){
		$meta = isset( $query->query_vars['meta_value'] ) ? $query->query_vars['meta_value'] : false;
		if( in_array($meta, ['2x.jpg', '2x-300x200.jpg', '2x-624x416.jpg']) ){
			$results = [];
		}
	}
	return $results;
}

/**
 * Filters the pulse frequency to be used for the BuddyBoss Activity heartbeat.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int $value The frequency in seconds between pulses.
 */
add_filter( 'bp_activity_heartbeat_pulse', 'bbp_wpal_bp_activity_heartbeat_pulse', PHP_INT_MAX );
function bbp_wpal_bp_activity_heartbeat_pulse( $value ){
	return 60;
}

add_action('bp_loaded', 'bbp_wpal_bp_loaded', PHP_INT_MAX);
function bbp_wpal_bp_loaded(){
	remove_action( 'bp_activity_mentions_prime_results', 'bp_groups_prime_mentions_results' );
	remove_action( 'bbp_forums_mentions_prime_results', 'bp_groups_prime_mentions_results' );

	remove_action( 'bp_activity_mentions_prime_results', 'bp_friends_prime_mentions_results' );
	remove_action( 'bbp_forums_mentions_prime_results', 'bp_friends_prime_mentions_results' );
	remove_action( 'bp_groups_posted_update', 'bb_subscription_send_subscribe_group_notifications',11, 4);
	remove_filter( 'bp_activity_mentioned_users', 'bp_find_mentions_by_at_sign', 10 );
	remove_action( 'bp_admin_init', 'bp_setup_updater',9999 );
	add_action ('wp_dashboard_setup', 'bbp_wpal_remove_dashboard_widget');
}


// Remove BBP Dashboard Widget
function bbp_wpal_remove_dashboard_widget () {
    remove_meta_box ( 'bbp-dashboard-right-now', 'dashboard', 'normal' );
}
