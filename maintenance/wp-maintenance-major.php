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

function wpmm_major_get_settings_defaults() {
	return array(
		'enabled'                         => 0,
		'maintenance_page'                => 1,
		'disable_managed_plugins'         => 1,
		'disable_action_scheduler_runner' => 1,
		'restrict_logins'                 => 1,
		'restrict_rest_authentication'    => 1,
	);
}

function wpmm_major_get_settings() {
	$defaults = wpmm_major_get_settings_defaults();
	$stored   = get_option( 'wpmm_major_settings', array() );
	$stored   = is_array( $stored ) ? $stored : array();

	return wp_parse_args( $stored, $defaults );
}

function wpmm_major_is_feature_enabled( $feature ) {
	$settings = wpmm_major_get_settings();

	return ! empty( $settings[ $feature ] );
}

function wpmm_major_is_active() {
	return wpmm_major_is_enabled() || wpmm_major_is_feature_enabled( 'enabled' );
}

function wpmm_major_get_feature_labels() {
	return array(
		'enabled'                         => 'Enable maintenance controls',
		'maintenance_page'                => 'Show maintenance page to public visitors',
		'disable_managed_plugins'         => 'Disable configured managed plugins',
		'disable_action_scheduler_runner' => 'Disable Action Scheduler queue runner',
		'restrict_logins'                 => 'Restrict non-admin logins',
		'restrict_rest_authentication'    => 'Restrict REST authentication for non-admins',
	);
}

function wpmm_major_get_feature_descriptions() {
	return array(
		'enabled'                         => 'Turns the maintenance workflow on from the admin without requiring a constant in wp-config.php.',
		'maintenance_page'                => 'Serves the custom maintenance page and 503 response to non-admin public requests.',
		'disable_managed_plugins'         => 'Hides and deactivates the configured WooCommerce-related plugins while maintenance is active.',
		'disable_action_scheduler_runner' => 'Stops the default Action Scheduler queue runner during maintenance.',
		'restrict_logins'                 => 'Prevents non-admin users from logging in while maintenance is active.',
		'restrict_rest_authentication'    => 'Returns a maintenance error for non-admin REST authentication attempts.',
	);
}

function wpmm_major_sanitize_settings( $raw_settings ) {
	$defaults  = wpmm_major_get_settings_defaults();
	$raw       = is_array( $raw_settings ) ? $raw_settings : array();
	$sanitized = array();

	foreach ( array_keys( $defaults ) as $feature ) {
		$sanitized[ $feature ] = empty( $raw[ $feature ] ) ? 0 : 1;
	}

	return $sanitized;
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
			'action_scheduler_helper_zip'    => 'https://github.com/woocommerce/action-scheduler-disable-default-runner/archive/refs/heads/master.zip',
			'maintenance_title'              => 'Scheduled Maintenance',
			'maintenance_heading'            => 'We will be back shortly.',
			'maintenance_message'            => 'The site is undergoing scheduled maintenance. Please check back soon.',
			'login_message'                  => 'Logins are temporarily limited to administrators during scheduled maintenance.',
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

function wpmm_major_get_manual_disable_plugins() {
	return array(
		'woocommerce/woocommerce.php'                           => 'WooCommerce',
		'woocommerce-subscriptions/woocommerce-subscriptions.php' => 'WooCommerce Subscriptions',
	);
}

function wpmm_major_filter_active_plugins( $plugins ) {
	if ( ! wpmm_major_is_active() || ! wpmm_major_is_feature_enabled( 'disable_managed_plugins' ) ) {
		return $plugins;
	}

	if ( empty( $plugins ) ) {
		return $plugins;
	}

	return array_values( array_diff( $plugins, wpmm_major_get_disabled_plugins() ) );
}

function wpmm_major_filter_network_active_plugins( $plugins ) {
	if ( ! wpmm_major_is_active() || ! wpmm_major_is_feature_enabled( 'disable_managed_plugins' ) ) {
		return $plugins;
	}

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

function wpmm_major_get_helper_plugin_candidates() {
	$config = wpmm_major_get_config();

	return array(
		$config['action_scheduler_helper_plugin'],
		'action-scheduler-disable-default-runner-master/as-disable-default-runner.php',
		'action-scheduler-disable-default-runner-main/as-disable-default-runner.php',
	);
}

function wpmm_major_get_helper_plugin_basename() {
	wpmm_major_load_plugin_functions();

	$plugins = get_plugins();

	foreach ( wpmm_major_get_helper_plugin_candidates() as $plugin ) {
		if ( isset( $plugins[ $plugin ] ) ) {
			return $plugin;
		}
	}

	foreach ( array_keys( $plugins ) as $plugin ) {
		if ( '/as-disable-default-runner.php' === substr( $plugin, -25 ) ) {
			return $plugin;
		}
	}

	return '';
}

function wpmm_major_plugin_is_active( $plugin ) {
	wpmm_major_load_plugin_functions();

	return is_plugin_active( $plugin ) || is_plugin_active_for_network( $plugin );
}

function wpmm_major_plugin_is_installed( $plugin ) {
	wpmm_major_load_plugin_functions();

	$plugins = get_plugins();

	return isset( $plugins[ $plugin ] );
}

function wpmm_major_get_helper_plugin_status() {
	$plugin = wpmm_major_get_helper_plugin_basename();

	if ( '' === $plugin ) {
		return 'missing';
	}

	if ( wpmm_major_plugin_is_active( $plugin ) ) {
		return 'active';
	}

	return 'installed';
}

function wpmm_major_get_command_center_url( $args = array() ) {
	return add_query_arg( $args, admin_url( 'index.php?page=wpmm-major-maintenance' ) );
}

function wpmm_major_get_admin_notice_message( $code ) {
	$messages = array(
		'settings-saved'   => array( 'success', 'Maintenance settings saved.' ),
		'helper-activated' => array( 'success', 'Action Scheduler helper activated.' ),
		'helper-installed' => array( 'success', 'Action Scheduler helper installed and activated.' ),
		'helper-failed'    => array( 'error', 'Action Scheduler helper could not be installed automatically. Check filesystem permissions and try again.' ),
		'plugin-disabled'  => array( 'success', 'Plugin disabled successfully.' ),
		'plugin-inactive'  => array( 'warning', 'Plugin was already inactive.' ),
		'plugin-failed'    => array( 'error', 'Plugin could not be disabled automatically.' ),
	);

	return $messages[ $code ] ?? null;
}

function wpmm_major_get_manual_disable_plugin_label( $plugin ) {
	$plugins = wpmm_major_get_manual_disable_plugins();

	return $plugins[ $plugin ] ?? $plugin;
}

function wpmm_major_render_status_badge( $label, $status ) {
	$colors = array(
		'active'    => '#067647',
		'enabled'   => '#067647',
		'inactive'  => '#b54708',
		'disabled'  => '#b54708',
		'missing'   => '#b42318',
		'installed' => '#1d4ed8',
	);

	$color = $colors[ $status ] ?? '#475467';

	return sprintf(
		'<span style="display:inline-block;padding:5px 10px;border-radius:999px;background:%1$s;color:#fff;font-size:12px;font-weight:600;letter-spacing:.03em;text-transform:uppercase;">%2$s</span>',
		esc_attr( $color ),
		esc_html( $label )
	);
}

function wpmm_major_render_plugin_row( $label, $plugin ) {
	$status = 'missing';

	if ( wpmm_major_plugin_is_installed( $plugin ) ) {
		$status = wpmm_major_plugin_is_active( $plugin ) ? 'active' : 'inactive';
	}

	$badge  = wpmm_major_render_status_badge( $status, $status );

	echo '<tr>';
	echo '<td>' . esc_html( $label ) . '</td>';
	echo '<td><code>' . esc_html( $plugin ) . '</code></td>';
	echo '<td>' . $badge . '</td>';
	echo '</tr>';
}

function wpmm_major_get_server_ip() {
	if ( ! empty( $_SERVER['SERVER_ADDR'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) );
	}

	$hostname = gethostname();

	if ( $hostname ) {
		$resolved_ip = gethostbyname( $hostname );

		if ( $resolved_ip && $resolved_ip !== $hostname ) {
			return $resolved_ip;
		}
	}

	return 'Unavailable';
}

function wpmm_major_get_web_server_label() {
	if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) );
	}

	return php_sapi_name();
}

function wpmm_major_get_database_server_label() {
	if ( defined( 'DB_HOST' ) && DB_HOST ) {
		return DB_HOST;
	}

	return 'Unavailable';
}

function wpmm_major_register_admin_menu() {
	add_submenu_page(
		'index.php',
		'Maintenance Command Center',
		'Maintenance',
		'activate_plugins',
		'wpmm-major-maintenance',
		'wpmm_major_render_command_center'
	);
}

function wpmm_major_render_feature_toggle( $feature ) {
	$labels       = wpmm_major_get_feature_labels();
	$descriptions = wpmm_major_get_feature_descriptions();

	echo '<label style="display:flex;gap:12px;align-items:flex-start;padding:14px 0;border-top:1px solid #eaecf0;">';
	echo '<input type="checkbox" name="wpmm_major_settings[' . esc_attr( $feature ) . ']" value="1" ' . checked( wpmm_major_is_feature_enabled( $feature ), true, false ) . ' style="margin-top:3px;">';
	echo '<span>';
	echo '<strong style="display:block;">' . esc_html( $labels[ $feature ] ) . '</strong>';
	echo '<span style="display:block;margin-top:4px;color:#475467;">' . esc_html( $descriptions[ $feature ] ) . '</span>';
	echo '</span>';
	echo '</label>';
}

function wpmm_major_render_manual_disable_form( $plugin ) {
	$label  = wpmm_major_get_manual_disable_plugin_label( $plugin );
	$status = wpmm_major_plugin_is_installed( $plugin ) ? ( wpmm_major_plugin_is_active( $plugin ) ? 'active' : 'inactive' ) : 'missing';

	echo '<div style="padding:16px 0;border-top:1px solid #eaecf0;">';
	echo '<p style="margin:0 0 8px;"><strong>' . esc_html( $label ) . '</strong></p>';
	echo '<p style="margin:0 0 12px;"><code>' . esc_html( $plugin ) . '</code> ' . wpmm_major_render_status_badge( $status, $status ) . '</p>';

	if ( 'missing' === $status ) {
		echo '<p style="margin:0;color:#475467;">Plugin is not installed on this site.</p>';
		echo '</div>';
		return;
	}

	if ( 'inactive' === $status ) {
		echo '<p style="margin:0;color:#475467;">Plugin is already inactive.</p>';
		echo '</div>';
		return;
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'wpmm_major_disable_plugin' );
	echo '<input type="hidden" name="action" value="wpmm_major_disable_plugin">';
	echo '<input type="hidden" name="plugin" value="' . esc_attr( $plugin ) . '">';
	submit_button( 'Disable ' . $label, 'secondary', 'submit', false );
	echo '</form>';
	echo '</div>';
}

function wpmm_major_render_command_center() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_die( 'Access denied.' );
	}

	$helper_status = wpmm_major_get_helper_plugin_status();
	$notice_code   = isset( $_GET['wpmm_notice'] ) ? sanitize_key( wp_unslash( $_GET['wpmm_notice'] ) ) : '';
	$notice        = wpmm_major_get_admin_notice_message( $notice_code );

	echo '<div class="wrap">';
	echo '<h1>Maintenance Command Center</h1>';
	echo '<p>Control maintenance operations from one place, including helper plugin installation and current plugin status.</p>';

	if ( $notice ) {
		echo '<div class="notice notice-' . esc_attr( $notice[0] ) . ' is-dismissible"><p>' . esc_html( $notice[1] ) . '</p></div>';
	}

	echo '<div style="max-width:1100px;display:grid;grid-template-columns:2fr 1fr;gap:24px;align-items:start;">';

	echo '<div style="background:#fff;border:1px solid #d0d5dd;border-radius:12px;padding:24px;box-shadow:0 1px 2px rgba(16,24,40,.04);">';
	echo '<h2 style="margin-top:0;">Current Status</h2>';
	echo '<table class="widefat striped" style="border:0;box-shadow:none;">';
	echo '<thead><tr><th>Item</th><th>Target</th><th>Status</th></tr></thead>';
	echo '<tbody>';
	echo '<tr><td>Maintenance Mode</td><td><code>Admin setting</code> or <code>WP_MAJOR_MAINTENANCE_MODE</code> / <code>WP_MAINTENANCE_MODE</code></td><td>' . wpmm_major_render_status_badge( wpmm_major_is_active() ? 'enabled' : 'disabled', wpmm_major_is_active() ? 'enabled' : 'disabled' ) . '</td></tr>';
	echo '<tr><td>Server IP</td><td><code>' . esc_html( wpmm_major_get_server_ip() ) . '</code></td><td>' . wpmm_major_render_status_badge( 'current host', 'installed' ) . '</td></tr>';
	echo '<tr><td>Web Server</td><td><code>' . esc_html( wpmm_major_get_web_server_label() ) . '</code></td><td>' . wpmm_major_render_status_badge( 'detected', 'installed' ) . '</td></tr>';
	echo '<tr><td>Database Server</td><td><code>' . esc_html( wpmm_major_get_database_server_label() ) . '</code></td><td>' . wpmm_major_render_status_badge( 'configured', 'installed' ) . '</td></tr>';
	foreach ( wpmm_major_get_feature_labels() as $feature => $label ) {
		if ( 'enabled' === $feature ) {
			continue;
		}

		echo '<tr><td>' . esc_html( $label ) . '</td><td><code>' . esc_html( $feature ) . '</code></td><td>' . wpmm_major_render_status_badge( wpmm_major_is_feature_enabled( $feature ) ? 'enabled' : 'disabled', wpmm_major_is_feature_enabled( $feature ) ? 'enabled' : 'disabled' ) . '</td></tr>';
	}
	foreach ( wpmm_major_get_disabled_plugins() as $plugin ) {
		wpmm_major_render_plugin_row( 'Managed Plugin', $plugin );
	}
	echo '<tr><td>Action Scheduler Helper</td><td><code>' . esc_html( $helper_status === 'missing' ? wpmm_major_get_helper_plugin_candidates()[0] : wpmm_major_get_helper_plugin_basename() ) . '</code></td><td>' . wpmm_major_render_status_badge( $helper_status, $helper_status ) . '</td></tr>';
	echo '</tbody>';
	echo '</table>';
	echo '</div>';

	echo '<div style="background:#fff;border:1px solid #d0d5dd;border-radius:12px;padding:24px;box-shadow:0 1px 2px rgba(16,24,40,.04);">';
	echo '<h2 style="margin-top:0;">Actions</h2>';
	echo '<h3 style="margin:0 0 8px;">Maintenance Settings</h3>';
	echo '<p>Use these checkboxes to turn maintenance behavior on or off from the admin.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'wpmm_major_save_settings' );
	echo '<input type="hidden" name="action" value="wpmm_major_save_settings">';
	foreach ( array_keys( wpmm_major_get_feature_labels() ) as $feature ) {
		wpmm_major_render_feature_toggle( $feature );
	}
	submit_button( 'Save Maintenance Settings', 'primary', 'submit', false, array( 'style' => 'margin-top:16px;' ) );
	echo '</form>';

	echo '<hr style="margin:24px 0;">';
	echo '<p>This snippet already disables the default Action Scheduler queue runner during maintenance. Install the helper plugin if you also want the dedicated plugin present on the site.</p>';

	if ( 'active' !== $helper_status ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'wpmm_major_install_helper' );
		echo '<input type="hidden" name="action" value="wpmm_major_install_helper">';
		submit_button( 'Install Action Scheduler Helper', 'primary', 'submit', false );
		echo '</form>';
	} else {
		echo '<p>' . wpmm_major_render_status_badge( 'active', 'active' ) . '</p>';
	}

	echo '<hr style="margin:24px 0;">';
	echo '<h3 style="margin:0 0 8px;">Plugin Controls</h3>';
	echo '<p>Use these buttons for immediate plugin deactivation from the maintenance command center.</p>';
	foreach ( array_keys( wpmm_major_get_manual_disable_plugins() ) as $plugin ) {
		wpmm_major_render_manual_disable_form( $plugin );
	}

	echo '<hr style="margin:24px 0;">';
	echo '<p><strong>Refresh cadence:</strong> The public maintenance page refreshes every 15 seconds.</p>';
	echo '<p><strong>Manual theme check:</strong> Only comment out child-theme code if it directly calls WooCommerce Subscriptions functions or classes.</p>';
	echo '</div>';

	echo '</div>';
	echo '</div>';
}

function wpmm_major_save_settings() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_die( 'Access denied.' );
	}

	check_admin_referer( 'wpmm_major_save_settings' );

	$settings = isset( $_POST['wpmm_major_settings'] ) ? wp_unslash( $_POST['wpmm_major_settings'] ) : array();
	update_option( 'wpmm_major_settings', wpmm_major_sanitize_settings( $settings ), false );

	wp_safe_redirect( add_query_arg( 'wpmm_notice', 'settings-saved', wpmm_major_get_command_center_url() ) );
	exit;
}

function wpmm_major_install_helper_plugin() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_die( 'Access denied.' );
	}

	check_admin_referer( 'wpmm_major_install_helper' );

	$redirect = wpmm_major_get_command_center_url();
	$status   = wpmm_major_get_helper_plugin_status();

	if ( 'active' === $status ) {
		wp_safe_redirect( add_query_arg( 'wpmm_notice', 'helper-activated', $redirect ) );
		exit;
	}

	if ( 'installed' === $status ) {
		wpmm_major_load_plugin_functions();
		$result = activate_plugin( wpmm_major_get_helper_plugin_basename() );

		wp_safe_redirect( add_query_arg( 'wpmm_notice', is_wp_error( $result ) ? 'helper-failed' : 'helper-activated', $redirect ) );
		exit;
	}

	$config = wpmm_major_get_config();

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
	$result   = $upgrader->install( $config['action_scheduler_helper_zip'] );
	$plugin   = wpmm_major_get_helper_plugin_basename();

	if ( is_wp_error( $result ) || ! $result || '' === $plugin ) {
		wp_safe_redirect( add_query_arg( 'wpmm_notice', 'helper-failed', $redirect ) );
		exit;
	}

	wpmm_major_load_plugin_functions();
	$result = activate_plugin( $plugin );

	wp_safe_redirect( add_query_arg( 'wpmm_notice', is_wp_error( $result ) ? 'helper-failed' : 'helper-installed', $redirect ) );
	exit;
}

function wpmm_major_disable_plugin_from_command_center() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_die( 'Access denied.' );
	}

	check_admin_referer( 'wpmm_major_disable_plugin' );

	$plugin   = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';
	$allowed  = wpmm_major_get_manual_disable_plugins();
	$redirect = wpmm_major_get_command_center_url();

	if ( ! isset( $allowed[ $plugin ] ) ) {
		wp_safe_redirect( add_query_arg( 'wpmm_notice', 'plugin-failed', $redirect ) );
		exit;
	}

	if ( ! wpmm_major_plugin_is_installed( $plugin ) || ! wpmm_major_plugin_is_active( $plugin ) ) {
		wp_safe_redirect( add_query_arg( 'wpmm_notice', 'plugin-inactive', $redirect ) );
		exit;
	}

	wpmm_major_load_plugin_functions();
	deactivate_plugins( $plugin, true, is_plugin_active_for_network( $plugin ) );

	if ( wpmm_major_plugin_is_active( $plugin ) ) {
		wp_safe_redirect( add_query_arg( 'wpmm_notice', 'plugin-failed', $redirect ) );
		exit;
	}

	wp_safe_redirect( add_query_arg( 'wpmm_notice', 'plugin-disabled', $redirect ) );
	exit;
}

function wpmm_major_persist_plugin_deactivation() {
	if ( ! wpmm_major_is_active() || ! wpmm_major_is_feature_enabled( 'disable_managed_plugins' ) ) {
		return;
	}

	wpmm_major_load_plugin_functions();

	foreach ( wpmm_major_get_disabled_plugins() as $plugin ) {
		if ( is_plugin_active( $plugin ) || is_plugin_active_for_network( $plugin ) ) {
			deactivate_plugins( $plugin, true, is_plugin_active_for_network( $plugin ) );
		}
	}
}

function wpmm_major_disable_action_scheduler_runner() {
	if ( ! wpmm_major_is_active() || ! wpmm_major_is_feature_enabled( 'disable_action_scheduler_runner' ) ) {
		return;
	}

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

function wpmm_major_get_logo_markup() {
	if ( function_exists( 'has_custom_logo' ) && has_custom_logo() && function_exists( 'get_custom_logo' ) ) {
		return sprintf(
			'<div class="wpmm-major-logo" aria-label="%1$s">%2$s</div>',
			esc_attr( get_bloginfo( 'name' ) ),
			get_custom_logo()
		);
	}

	return sprintf(
		'<div class="wpmm-major-logo wpmm-major-logo-fallback" aria-label="%1$s">%2$s</div>',
		esc_attr( get_bloginfo( 'name' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}

function wpmm_major_render_maintenance_page() {
	$config = wpmm_major_get_config();
	$title  = esc_html( $config['maintenance_title'] );
	$logo   = wpmm_major_get_logo_markup();
	$body   = sprintf(
		'<!DOCTYPE html>'
		. '<html %4$s>'
		. '<head>'
		. '<meta charset="%5$s">'
		. '<meta name="viewport" content="width=device-width, initial-scale=1">'
		. '<meta http-equiv="refresh" content="15">'
		. '<title>%6$s</title>'
		. '<style>'
		. ':root{color-scheme:light;}'
		. '*{box-sizing:border-box;}'
		. 'html,body{margin:0;min-height:100%%;font-family:Georgia,"Times New Roman",serif;color:#2f2418;background:#f7f1e8;}'
		. 'body{min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at top,#f7f1e8 0%%,#eadcc8 48%%,#d3bea3 100%%);}'
		. '.wpmm-major-shell{width:min(100%%,720px);margin:0 auto;}'
		. '.wpmm-major-card{padding:48px 40px;text-align:center;}'
		. '.wpmm-major-logo{display:flex;align-items:center;justify-content:center;margin:0 auto 22px;max-width:240px;}'
		. '.wpmm-major-logo img{display:block;max-width:100%%;max-height:88px;width:auto;height:auto;}'
		. '.wpmm-major-logo a{display:inline-flex;align-items:center;justify-content:center;}'
		. '.wpmm-major-logo-fallback{padding:14px 18px;border-radius:18px;background:rgba(255,252,247,0.65);color:#2f2418;font-size:1.1rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;}'
		. '.wpmm-major-kicker{display:inline-block;margin-bottom:18px;padding:8px 14px;border-radius:999px;background:#2f2418;color:#f5eadc;font-size:12px;letter-spacing:.18em;text-transform:uppercase;}'
		. '.wpmm-major-card h1{margin:0 0 16px;font-size:clamp(2.2rem,5vw,4rem);line-height:1.05;letter-spacing:-0.03em;}'
		. '.wpmm-major-card p{margin:0 auto;max-width:34rem;font-size:1.08rem;line-height:1.7;color:#57412a;}'
		. '.wpmm-major-status{margin:28px auto 0;padding:16px 18px;max-width:28rem;border-radius:18px;background:rgba(255,252,247,0.6);color:#6b4d27;font-size:.98rem;}'
		. '.wpmm-major-status strong{display:block;margin-bottom:6px;color:#2f2418;font-size:1rem;}'
		. '.wpmm-major-pulse{width:14px;height:14px;margin:0 auto 22px;border-radius:50%%;background:#9d5c2f;box-shadow:0 0 0 0 rgba(157,92,47,0.45);animation:wpmm-major-pulse 2.2s infinite;}'
		. '@keyframes wpmm-major-pulse{0%%{transform:scale(.95);box-shadow:0 0 0 0 rgba(157,92,47,0.42);}70%%{transform:scale(1);box-shadow:0 0 0 18px rgba(157,92,47,0);}100%%{transform:scale(.95);box-shadow:0 0 0 0 rgba(157,92,47,0);}}'
		. '@media (max-width: 640px){body{padding:16px;}.wpmm-major-card{padding:36px 24px;}.wpmm-major-logo{max-width:180px;margin-bottom:18px;}.wpmm-major-card p{font-size:1rem;}.wpmm-major-status{padding:14px 16px;}}'
		. '</style>'
		. '</head>'
		. '<body>'
		. '<div class="wpmm-major-shell">'
		. '<div class="wpmm-major-card">'
		. '%3$s'
		. '<div class="wpmm-major-kicker">Temporary Maintenance</div>'
		. '<div class="wpmm-major-pulse" aria-hidden="true"></div>'
		. '<h1>%1$s</h1>'
		. '<p>%2$s</p>'
		. '<div class="wpmm-major-status"><strong>Back in a moment</strong>This page refreshes automatically every 15 seconds while we finish up.</div>'
		. '</div>'
		. '</div>'
		. '<script>window.setTimeout(function(){window.location.reload();},15000);</script>'
		. '</body>'
		. '</html>',
		esc_html( $config['maintenance_heading'] ),
		esc_html( $config['maintenance_message'] ),
		$logo,
		get_language_attributes(),
		esc_attr( get_bloginfo( 'charset' ) ),
		$title
	);

	status_header( 503 );
	nocache_headers();
	header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

	echo $body;
	exit;
}

function wpmm_major_block_public_requests() {
	if ( ! wpmm_major_is_active() || ! wpmm_major_is_feature_enabled( 'maintenance_page' ) ) {
		return;
	}

	if ( wpmm_major_can_bypass() || ! wpmm_major_is_public_request() ) {
		return;
	}

	wpmm_major_render_maintenance_page();
}

function wpmm_major_block_login( $user ) {
	if ( ! wpmm_major_is_active() || ! wpmm_major_is_feature_enabled( 'restrict_logins' ) ) {
		return $user;
	}

	if ( is_wp_error( $user ) || user_can( $user, 'activate_plugins' ) ) {
		return $user;
	}

	$config = wpmm_major_get_config();

	return new WP_Error( 'maintenance_mode', $config['login_message'] );
}

function wpmm_major_login_message( $message ) {
	if ( ! wpmm_major_is_active() || ! wpmm_major_is_feature_enabled( 'restrict_logins' ) ) {
		return $message;
	}

	if ( wpmm_major_can_bypass() ) {
		return $message;
	}

	$config = wpmm_major_get_config();

	return $message . '<p style="color: #b32d2e; font-weight: 600;">' . esc_html( $config['login_message'] ) . '</p>';
}

function wpmm_major_block_rest_authentication( $result ) {
	if ( ! wpmm_major_is_active() || ! wpmm_major_is_feature_enabled( 'restrict_rest_authentication' ) ) {
		return $result;
	}

	if ( is_wp_error( $result ) || wpmm_major_can_bypass() ) {
		return $result;
	}

	$config = wpmm_major_get_config();

	return new WP_Error( 'maintenance_mode', $config['maintenance_message'], array( 'status' => 503 ) );
}

function wpmm_major_helper_plugin_is_active() {
	return 'active' === wpmm_major_get_helper_plugin_status();
}

function wpmm_major_admin_notice() {
	if ( ! wpmm_major_can_bypass() || ! wpmm_major_is_active() ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>Major maintenance mode is enabled. Review plugin status and helper tools in <a href="' . esc_url( wpmm_major_get_command_center_url() ) . '">Maintenance Command Center</a>.</p></div>';
}

add_action( 'admin_menu', 'wpmm_major_register_admin_menu' );
add_action( 'admin_post_wpmm_major_save_settings', 'wpmm_major_save_settings' );
add_action( 'admin_post_wpmm_major_install_helper', 'wpmm_major_install_helper_plugin' );
add_action( 'admin_post_wpmm_major_disable_plugin', 'wpmm_major_disable_plugin_from_command_center' );
add_action( 'admin_notices', 'wpmm_major_admin_notice' );
add_filter( 'option_active_plugins', 'wpmm_major_filter_active_plugins', 1 );
add_filter( 'site_option_active_sitewide_plugins', 'wpmm_major_filter_network_active_plugins', 1 );
add_action( 'init', 'wpmm_major_persist_plugin_deactivation', 1 );
add_action( 'init', 'wpmm_major_disable_action_scheduler_runner', 10 );
add_action( 'template_redirect', 'wpmm_major_block_public_requests', 0 );
add_filter( 'wp_authenticate_user', 'wpmm_major_block_login', 10, 1 );
add_filter( 'login_message', 'wpmm_major_login_message' );
add_filter( 'rest_authentication_errors', 'wpmm_major_block_rest_authentication' );
