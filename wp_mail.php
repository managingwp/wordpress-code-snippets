<?php
// Used to send a test email to test the wp_mail function
// Visit the Media Upload page to send an email.

add_action('init', 'send_test12345');

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
