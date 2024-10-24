<?php
/**
 * wp-mail-log-to-file.php
 * Description: Log wp_mail function to a file
 * Status: Complete
 *
 * Place into wp-content/mu-plugins directory and log the wp_mail function and data
 */

add_filter('wp_mail', function ($args) {

$current_date=date('m/d/Y h:i:s a', time());
$log_file = ABSPATH . 'logs/wpmail.log';

$data = "$args";

$log_message = "$current_date";
$log_message .= print_r($args,true);
echo "<pre>".print_r($args)."</pre>";

file_put_contents($log_file, $log_message, FILE_APPEND);

return $args;

}, 10, 1);

?>#
