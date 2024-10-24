<?php
/**
 * log-admin-notices.php
 * Description: Log's admin notices on admin_init, great for finding admin_notices to hide.
 * Type: snippet
 * Status: Complete
*/

add_action( 'admin_init', function () {
  # -- Use the global wp_filter
  global $wp_filter;

  # -- Log stff
  $log_file = ABSPATH . 'wp-content/mu-plugins/temp.log';

  # -- List all admin_notices
  $log_message = "<!-- ".var_export( $wp_filter[ 'admin_notices' ], true )."-->";

  # -- List all admin_notices functions and their details.
  $log_message .= "\n#### Go through all Admin Notices Content ####\n";
  foreach($wp_filter['admin_notices'] as $weight => $callbacks) {
    foreach($callbacks as $name => $details) {
      # -- Check if function is an array
      if ( is_array($details['function']) ) {
        $log_message .= "\n-- Start Function Array";
        $log_message .= "\n".print_r($details['function'],true);
        #$log_message .= "\nArray[0]".$details['function']['0'];
      } else {
        $log_message .= "\n-- Start Function ".$details['function'];
      }

      # -- Buffer and output details
      ob_start();
      call_user_func($details['function']);
      $log_message .= ob_get_clean();
      $log_message .= "\n-- End Function ".$details['function']."\n\n";

      #// Check if this contains our forbidden string
        #foreach($forbidden_message_strings as $forbidden_string) {
          #if(strpos($message, $forbidden_string) !== FALSE) {
            // Found it - under this callback
           # $wp_filter['admin_notices']->remove_filter('admin_notices', $details['function'], $weight);
          #}
        #}

    }

  }
  file_put_contents($log_file, $log_message, FILE_APPEND);
});