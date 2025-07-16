<?php
/**
 * wp-mail-test.php
 * Description: Used to send a test email to test the wp_mail function.
 * Version: 1.0.0
 * Status: Complete
 * 
 * Visit the media upload section to trigger the email.
 */

add_action('admin_init', 'send_test12345');

function send_test12345() {    
    global $pagenow;
    if( current_user_can('administrator') && ($pagenow == 'upload.php')) {
        echo "Test";
        $current_date=date('m/d/Y h:i:s a', time());
        $to = 'test@test.ca';
        $subject = 'wp_mail test - '.$current_date;
        $headers[] = 'From:  Yellow <yellow@test.com>'. "\r\n";
        $message = 'This is a test email using wp-content/mu-plugins/wm_mail.php';
        #$headers[] = 'Cc: Blue <bluet@test.com>';
        #$headers[] = 'Cc: yellow@test.com'; // note you can just use a simple email address
        wp_mail( $to, $subject, $message, $headers);
    }
}
