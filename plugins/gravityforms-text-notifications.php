<?php
/**
 * Plugin Name: Gravity Forms Notifications as Text not HTML
 * Description: All notifications will be sent as text versus the default html.
 * Version: 0.1.0
 * Author: Jordan
 * Author URI: https://managingwp.io/
 * Type: snippet
 * Status: Complete
 */
 
add_filter( 'gform_notification', function( $notification, $form, $entry ) {
    if ( $notification['event'] === 'form_submission' && strpos( strtolower( $notification['toType'] ), 'email' ) !== false ) {
        $notification['message_format'] = 'text';
    }
    return $notification;
}, 10, 3 );
