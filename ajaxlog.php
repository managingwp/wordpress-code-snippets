<?php
/**
 * ajaxlog.php - Record all admin_init requests to troubleshoot high admin-ajax.php requests.
 *
 * Original code from - https://stackoverflow.com/questions/69234458/how-to-log-queries-that-go-to-wp-admin-admin-ajax-php
 *
**/

add_action( 'admin_init', 'my_ajax_checker', 10, 2);

function my_ajax_checker() {
        $message .= "*** Running on " . $_SERVER['REQUEST_URI'] . " based on " . $match_uri . "\n";

        // Enable and Disable http headers and post data
        $enable_http_headers="0";
        $enable_http_post="1";

        // Log to file
        $file = dirname(__FILE__) . '/ajaxlog.log';
        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        // Grab HTTP headers.
        if ( $enable_http_headers == "1" ) {
            foreach (getallheaders() as $name => $value) {
                $http_headers .= "$name: $value;\n";
            }
        } else {
            $http_headers = "\$enable_http_headers=0 change to 1 to enable.";
        }

        // Grab HTTP post data.
        if ( $enable_http_post == "1" ) {
            $http_post = print_r($_POST,true);
        } else {
            $http_post = "\$enable_http_post=0 change to 1 to enable";
        }
        $postdata = print_r($_POST,true);
        $message .= $actual_link . " - " . date('m/d/Y h:i:s a', time()) . " - " .$_SERVER['REMOTE_ADDR'];
        $message .= "\nRequest URI: " . $_SERVER[REQUEST_URI];
        $message .= "\nMatch URI: " . $match_uri;
        $message .= "\n>> HTTP Headers <<:\n" . $http_headers;
        $message .= "\n>> HTTP POST Data <<:\n" . $http_post;
        $message .= "\r\n\n";
        file_put_contents($file, $message, FILE_APPEND);   
}

?>
