<?php
/**
 * sunrise-subdomain-to-subdirectory-mapping.php
 * Description: Redirect domain names to multisite subdirectories and include query arguments in the redirect.
 * Status: Complete
 * Status: Complete
 * 
 * You must have the domain name setup on your hosting account, typically as a addon domain or domain alias.
 */

$current_domain = $_SERVER['HTTP_HOST'];
$current_path = $_SERVER['REQUEST_URI'];
$log_file = ABSPATH . '../redirect.log';
$debug_sunrise = "0";
$log_unmatched = "0";

// Grab main site domain
$main_domain = 'fremontunified.org';

// Define the source and target domains in a map
$domain_map = array(
    // Add more mappings here as needed
    'domain.com'=>'domain2.com/site1',
    'test2.com'=>'domain2.com/site2',
    'test3.com'=>'domain2.com/site3',
    'test4.com'=>'domain2.com/site4',
    'test5.com'=>'domain2.com/site5',
);

// Check if the current domain is in the map
if (array_key_exists($current_domain, $domain_map)) {
    // Get the corresponding target domain and path from the map
    $target_parts = parse_url($domain_map[$current_domain]);
    $target_domain = $target_parts['host'];
    $target_path = $target_parts['path'];

    // Build the new URL with target domain, path, and query string
    $new_url = 'https://' . $target_domain . $target_path . $current_path;
    // Log rewrite mappings.
    if ( $debug_sunrise == "1" ) {
        file_put_contents($log_file, "Matched:".$current_domain." New:".$new_url."\n", FILE_APPEND);
    }

    // Redirect to the new URL
    header('Location: ' . $new_url, true, 301);
    exit;
} else {
    // Enable logging of failed mappings
    if ( $debug_sunrise == "1" || $log_unmatched == "1") {
        if ( $current_domain != $main_domain ) {
            file_put_contents($log_file, "Not Matched: ".$current_domain." not in \$domain_map - Request: ".$current_domain.$current_path."\n", FILE_APPEND);
        }
    }
}
?>