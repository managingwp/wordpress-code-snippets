<?php
/**
 * Plugin Name: redirection-search-replace.php
 * Description: This script has two objectives, replace redirection_items and update
 * Version: 1.0.0
 * Type: script
 * Status: Complete
 *
 * This script has two objectives, replace redirection_items and update 
 * 
 * 1. Search and replace for the redirection_items table where the action_data column contains 
 * json and the substring 'domain.com', then replace action_data cell with the url_from value.
 * 
 * 2. Find all rows in the redirection_items table where the url or match_url column does not start 
 * with the sub-directory of the site and update it with the sub-directory prefix for all sites 
 * except the main site.
 */
global $wpdb;

$limit = 0; // Set this value to determine how many sites to process. 0 for all sites.
$domain_to_replace = 'domain.com'; // Set this value to the domain to search for in the action_data column
$redirect_group = "Group Name"; // Set this value to the name of the group to search for in the redirection_groups table, if empty skip.

// Construct the query based on the limit
$query = "SELECT blog_id, domain, path FROM {$wpdb->blogs}";
if ($limit > 0) {
    $query .= " LIMIT {$limit}";
}

// Fetch all site IDs for the multisite, including the main site, based on the limit
$sites = $wpdb->get_results($query);

// Iterate through each site
foreach ($sites as $site) {
    $total_scanned_for_site = 0;
    $total_updated_for_site = 0;
    $not_updated_ids = [];;

    // Switch to the site to set the correct table prefix and display its info
    switch_to_blog($site->blog_id);

    echo "Processing Site ID: {$site->blog_id}, URL: {$site->domain}{$site->path}\n";
    echo "Sub-directory: {$site->path}\n";
    echo "-----------------------------------------\n";

    // If $redirect_group not empty, fetch the group ID for from the redirection_groups table
    if (!empty($redirect_group)) {
        $group = $wpdb->get_row($wpdb->prepare("SELECT id, name FROM {$wpdb->prefix}redirection_groups WHERE name = %s", $redirect_group));
        if ($group) {
            echo "Found group: {$group->name} with ID: {$group->id}\n";
            // If we have a valid group ID, get all rows for that group from the redirection_items table where action_data starts with 'a:3'
            $rows = $wpdb->get_results($wpdb->prepare("SELECT id, action_data FROM {$wpdb->prefix}redirection_items WHERE group_id = %d AND action_data LIKE 'a:3%'", $group->id));
        } else {
            echo "Group '{$redirect_group}' not found for site ID {$site->blog_id}. Searching all rows in the redirection_items table.\n";
            // If we don't have a valid group ID, get all rows from the redirection_items table where action_data starts with 'a:3'
            $rows = $wpdb->get_results("SELECT id, action_data FROM {$wpdb->prefix}redirection_items WHERE action_data LIKE 'a:3%'");
        }
    } else {
        $group = null;
        $rows = $wpdb->get_results("SELECT id, action_data FROM {$wpdb->prefix}redirection_items WHERE action_data LIKE 'a:3%'");
    }    

    foreach ($rows as $row) {
        $total_scanned_for_site++;

        // Check if the action_data is serialized
        if (is_serialized($row->action_data)) {
            // Unserialize the action_data to an array
            $data = maybe_unserialize($row->action_data);

            // Check if the 'server' key contains the substring $domain_to_replace
            if (isset($data['server']) && strpos($data['server'], $domain_to_replace) !== false) {
                // Extract the url_from value
                $url_from = isset($data['url_from']) ? $data['url_from'] : null;

                if ($url_from) {
                    // Update the row with the url_from content and change match_type to "url"
                    $updated = $wpdb->update("{$wpdb->prefix}redirection_items", array('action_data' => $url_from, 'match_type' => 'url'), array('id' => $row->id));

                    if ($updated) {
                        $total_updated_for_site++;
                    } else {
                        $not_updated_ids[] = $row->id;
                    }
                }
            }
        }
    }

    echo "Total rows scanned for site ID {$site->blog_id}: {$total_scanned_for_site}\n";
    echo "Total rows updated for site ID {$site->blog_id}: {$total_updated_for_site}\n";

    if (count($not_updated_ids) > 0) {
        echo "Rows not updated for site ID {$site->blog_id}: " . implode(", ", $not_updated_ids) . "\n";
    }

    // If it's not the main site, update the url and match_url columns in the redirection_items table
    if ($site->blog_id != 1) {
        $subdir_name = trim($site->path, '/');
        $rows = $wpdb->get_results("SELECT id, url, match_url FROM {$wpdb->prefix}redirection_items WHERE url NOT LIKE '/{$subdir_name}/%' OR match_url NOT LIKE '/{$subdir_name}/%'");
        $url_updated_count = 0; // Added for url and match_url update count
        
        foreach ($rows as $row) {
            $updated_url = strpos($row->url, "/{$subdir_name}/") === 0 ? $row->url : "/{$subdir_name}{$row->url}";
            $updated_match_url = strpos($row->match_url, "/{$subdir_name}/") === 0 ? $row->match_url : "/{$subdir_name}{$row->match_url}";

            $updated = $wpdb->update("{$wpdb->prefix}redirection_items", ['url' => $updated_url, 'match_url' => $updated_match_url], ['id' => $row->id]);
            
            if ($updated) {
                $url_updated_count++;
            }
        }

        echo "Total rows updated with sub-directory prefix: {$url_updated_count}\n";
    }

    echo "\n"; // Space for separation between sites

    // Restore to the original blog context
    restore_current_blog();
}


?>