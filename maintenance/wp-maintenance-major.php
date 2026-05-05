<?php
/**
 * Plugin Name: wp-maintenance-major.php
 * Description: Major maintenance mode that blocks public access and disables key WooCommerce automation plugins.
 * Version: 1.0.0
 * Status: Complete
 * Type: mu-plugin
 */

function wpmm_major_is_enabled() {
	return ( defined( 'WP_MAJOR_MAINTENANCE_MODE' ) && WP_MAJOR_MAINTENANCE_MODE )
		|| ( defined( 'WP_MAINTENANCE_MODE' ) && WP_MAINTENANCE_MODE );
}

if ( ! wpmm_major_is_enabled() ) {
	return;
}

function wpmm_major_get_config() {
	static $config = null;

	if ( null !== $config ) {
		return $config;
	}

	$config = apply_filters(
		'wpmm_major_config',
		array(
			'disabled_plugins'                => array(
				'woocommerce-subscriptions/woocommerce-subscriptions.php',
				'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php',
			),
			'action_scheduler_helper_plugin' => 'action-scheduler-disable-default-runner/as-disable-default-runner.php',
			'maintenance_title'              => 'Scheduled Maintenance',
			'maintenance_heading'            => 'We will be back shortly.',
			'maintenance_message'            => 'The site is undergoing scheduled maintenance. Please check back soon.',
			'login_message'                  => 'Logins are temporarily limited to administrators during scheduled maintenance.',
			'child_theme_notice'             => 'Reminder: temporarily comment out child-theme functions.php custom code before disabling WooCommerce Subscriptions.',
		)
	);

	return $config;
}

function wpmm_major_can_bypass() {
	return current_user_can( 'activate_plugins' );
}

function wpmm_major_get_disabled_plugins() {
	$config = wpmm_major_get_config();

	return array_values(
		array_filter(
			(array) $config['disabled_plugins'],
			'is_string'
		)
	);
}

function wpmm_major_filter_active_plugins( $plugins ) {
	if ( empty( $plugins ) ) {
		return $plugins;
	}

	return array_values( array_diff( $plugins, wpmm_major_get_disabled_plugins() ) );
}

function wpmm_major_filter_network_active_plugins( $plugins ) {
	if ( empty( $plugins ) || ! is_array( $plugins ) ) {
		return $plugins;
	}

	foreach ( wpmm_major_get_disabled_plugins() as $plugin ) {
		unset( $plugins[ $plugin ] );
	}

	return $plugins;
}

function wpmm_major_load_plugin_functions() {
	if ( ! function_exists( 'deactivate_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
}

function wpmm_major_persist_plugin_deactivation() {
	wpmm_major_load_plugin_functions();

	foreach ( wpmm_major_get_disabled_plugins() as $plugin ) {
		if ( is_plugin_active( $plugin ) || is_plugin_active_for_network( $plugin ) ) {
			deactivate_plugins( $plugin, true, is_plugin_active_for_network( $plugin ) );
		}
	}
}

function wpmm_major_disable_action_scheduler_runner() {
	if ( class_exists( 'ActionScheduler' ) ) {
		remove_action( 'action_scheduler_run_queue', array( ActionScheduler::runner(), 'run' ) );
	}

	if ( class_exists( 'ActionScheduler_QueueRunner' ) ) {
		remove_action( 'action_scheduler_run_queue', array( ActionScheduler_QueueRunner::instance(), 'run' ) );
	}
}

function wpmm_major_is_public_request() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return false;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return false;
	}

	return true;
}

function wpmm_major_render_maintenance_page() {
	$config = wpmm_major_get_config();
	$title  = esc_html( $config['maintenance_title'] );
	$body   = sprintf(
		'<h1>%1$s</h1><p>%2$s</p>',
		esc_html( $config['maintenance_heading'] ),
		esc_html( $config['maintenance_message'] )
	);

	wp_die( $body, $title, array( 'response' => 503 ) );
}

function wpmm_major_block_public_requests() {
	if ( wpmm_major_can_bypass() || ! wpmm_major_is_public_request() ) {
		return;
	}

	wpmm_major_render_maintenance_page();
}

function wpmm_major_block_login( $user ) {
	if ( is_wp_error( $user ) || user_can( $user, 'activate_plugins' ) ) {
		return $user;
	}

	$config = wpmm_major_get_config();

	return new WP_Error( 'maintenance_mode', $config['login_message'] );
}

function wpmm_major_login_message( $message ) {
	if ( wpmm_major_can_bypass() ) {
		return $message;
	}

	$config = wpmm_major_get_config();

	return $message . '<p style="color: #b32d2e; font-weight: 600;">' . esc_html( $config['login_message'] ) . '</p>';
}

function wpmm_major_block_rest_authentication( $result ) {
	if ( is_wp_error( $result ) || wpmm_major_can_bypass() ) {
		return $result;
	}

	$config = wpmm_major_get_config();

	return new WP_Error( 'maintenance_mode', $config['maintenance_message'], array( 'status' => 503 ) );
}

function wpmm_major_helper_plugin_is_active() {
	$config = wpmm_major_get_config();

	wpmm_major_load_plugin_functions();

	return is_plugin_active( $config['action_scheduler_helper_plugin'] ) || is_plugin_active_for_network( $config['action_scheduler_helper_plugin'] );
}

function wpmm_major_admin_notice() {
	if ( ! wpmm_major_can_bypass() ) {
		return;
	}

	$config  = wpmm_major_get_config();
	$notices = array( $config['child_theme_notice'] );

	if ( ! wpmm_major_helper_plugin_is_active() ) {
		$notices[] = 'The snippet is disabling Action Scheduler queue processing directly. Install the helper plugin as well if you want that dependency tracked explicitly on the site.';
	}

	echo '<div class="notice notice-warning"><p>' . esc_html( implode( ' ', $notices ) ) . '</p></div>';
}

add_filter( 'option_active_plugins', 'wpmm_major_filter_active_plugins', 1 );
add_filter( 'site_option_active_sitewide_plugins', 'wpmm_major_filter_network_active_plugins', 1 );

add_action( 'init', 'wpmm_major_persist_plugin_deactivation', 1 );
add_action( 'init', 'wpmm_major_disable_action_scheduler_runner', 10 );
add_action( 'template_redirect', 'wpmm_major_block_public_requests', 0 );
add_filter( 'wp_authenticate_user', 'wpmm_major_block_login', 10, 1 );
add_filter( 'login_message', 'wpmm_major_login_message' );
add_filter( 'rest_authentication_errors', 'wpmm_major_block_rest_authentication' );
add_action( 'admin_notices', 'wpmm_major_admin_notice' );
