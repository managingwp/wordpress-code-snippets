<?php
/**
 * Plugin Name: Updated Content Report
 * Description: Shows all posts by post type, sorted by last updated date for easy content migration tracking.
 * Version: 1.0.0
 * Author: Managing WP
 * Author URI: https://managingwp.io/
 * Type: mu-plugin
 * Status: Complete
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', function() {
    add_management_page(
        'Updated Content Report',
        'Updated Content',
        'manage_options',
        'updated-content-report',
        'render_updated_content_report'
    );
});

// Render the report page
function render_updated_content_report() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // Get date range from query params
    $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
    $selected_post_types = isset($_GET['post_types']) ? (array) $_GET['post_types'] : array();
    $sort_order = isset($_GET['sort_order']) ? sanitize_text_field($_GET['sort_order']) : 'DESC';
    $show_debug = isset($_GET['show_debug']) && $_GET['show_debug'] === '1';
    
    // Validate sort order
    if (!in_array($sort_order, array('ASC', 'DESC'))) {
        $sort_order = 'DESC';
    }
    
    // Get all public post types
    $post_types = get_post_types(array('public' => true), 'objects');
    
    // Add GeneratePress elements if post type exists
    if (post_type_exists('gp_elements')) {
        $gp_elements = get_post_type_object('gp_elements');
        if ($gp_elements) {
            $post_types['gp_elements'] = $gp_elements;
        }
    }
    
    // Filter to selected post types if any
    if (!empty($selected_post_types)) {
        $post_types = array_intersect_key($post_types, array_flip($selected_post_types));
    }
    
    ?>
    <div class="wrap">
        <h1>Updated Content Report</h1>
        <p>View all content sorted by last modified date to track updates across your site.</p>
        
        <?php if ($show_debug): ?>
        <div style="margin: 10px 0; padding: 10px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
            <strong>Debug Info:</strong>
            Sort Order: <code><?php echo esc_html($sort_order); ?></code> |
            Date From: <code><?php echo esc_html($date_from ?: 'none'); ?></code> |
            Date To: <code><?php echo esc_html($date_to ?: 'none'); ?></code> |
            Selected Post Types: <code><?php echo esc_html(implode(', ', $selected_post_types) ?: 'all'); ?></code>
        </div>
        <?php endif; ?>
        
        <!-- Filter Form -->
        <form method="get" action="" id="filter-form" style="margin: 20px 0; padding: 15px; background: #fff; border: 1px solid #ccd0d4;">
            <input type="hidden" name="page" value="updated-content-report">
            
            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
                <div>
                    <label for="date_from" style="display: block; margin-bottom: 5px; font-weight: 600;">From Date:</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>" style="padding: 5px;">
                </div>
                
                <div>
                    <label for="date_to" style="display: block; margin-bottom: 5px; font-weight: 600;">To Date:</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>" style="padding: 5px;">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Post Types:</label>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <?php
                        $all_post_types = get_post_types(array('public' => true), 'objects');
                        
                        // Add gp_elements if it exists (even if not public)
                        if (post_type_exists('gp_elements') && !isset($all_post_types['gp_elements'])) {
                            $gp_elements = get_post_type_object('gp_elements');
                            if ($gp_elements) {
                                $all_post_types['gp_elements'] = $gp_elements;
                            }
                        }
                        
                        foreach ($all_post_types as $pt_key => $pt_obj) {
                            $checked = empty($selected_post_types) || in_array($pt_key, $selected_post_types) ? 'checked' : '';
                            echo '<label style="display: flex; align-items: center; gap: 5px;">';
                            echo '<input type="checkbox" name="post_types[]" value="' . esc_attr($pt_key) . '" ' . $checked . '>';
                            echo esc_html($pt_obj->labels->name);
                            echo '</label>';
                        }
                        ?>
                    </div>
                </div>
                
                <div>
                    <label for="sort_order" style="display: block; margin-bottom: 5px; font-weight: 600;">Sort Order:</label>
                    <select id="sort_order" name="sort_order" style="padding: 5px;" onchange="document.getElementById('filter-form').submit();">
                        <option value="DESC" <?php selected($sort_order, 'DESC'); ?>>Newest First</option>
                        <option value="ASC" <?php selected($sort_order, 'ASC'); ?>>Oldest First</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: flex; align-items: center; gap: 5px; margin-bottom: 5px;">
                        <input type="checkbox" name="show_debug" value="1" <?php checked($show_debug); ?>>
                        <span style="font-weight: 600;">Show Debug Info</span>
                    </label>
                </div>
                
                <div>
                    <button type="submit" class="button button-primary">Filter</button>
                    <a href="<?php echo admin_url('tools.php?page=updated-content-report'); ?>" class="button">Reset</a>
                </div>
            </div>
        </form>
        
        <?php
        foreach ($post_types as $post_type_key => $post_type_obj) {
            $args = array(
                'post_type' => $post_type_key,
                'post_status' => 'any',
                'posts_per_page' => -1,
                'orderby' => 'modified',
                'order' => $sort_order,
                'ignore_custom_sort' => true, // For Post Types Order plugin
                'suppress_filters' => false, // Allow date_query to work
            );
            
            // Add date filtering if provided
            if (!empty($date_from) || !empty($date_to)) {
                $date_query = array();
                
                if (!empty($date_from)) {
                    $date_query['after'] = $date_from;
                }
                
                if (!empty($date_to)) {
                    $date_query['before'] = $date_to . ' 23:59:59';
                }
                
                $date_query['column'] = 'post_modified';
                $date_query['inclusive'] = true;
                
                $args['date_query'] = array($date_query);
            }
            
            $query = new WP_Query($args);
            
            // Force sorting by modified date (bypass Post Types Order plugin)
            add_filter('posts_orderby', function($orderby, $query_obj) use ($sort_order) {
                global $wpdb;
                if ($query_obj->get('orderby') === 'modified') {
                    return "{$wpdb->posts}.post_modified " . $sort_order;
                }
                return $orderby;
            }, 999, 2);
            
            if ($query->have_posts()) {
                ?>
                <div style="margin: 30px 0;">
                    <h2 style="margin-bottom: 10px; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                        <?php echo esc_html($post_type_obj->labels->name); ?> 
                        <span style="color: #666; font-weight: normal; font-size: 14px;">(<?php echo $query->found_posts; ?> items)</span>
                    </h2>
                    
                    <table class="wp-list-table widefat fixed striped" style="margin-top: 0;">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Title</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 180px;">Last Modified</th>
                                <th style="width: 150px;">Author</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($query->have_posts()) {
                                $query->the_post();
                                $post_id = get_the_ID();
                                $post_status = get_post_status();
                                $modified_date = get_the_modified_date('Y-m-d H:i:s');
                                $modified_date_display = get_the_modified_date('M j, Y g:i a');
                                $author = get_the_author();
                                $edit_url = get_edit_post_link($post_id);
                                $view_url = get_permalink($post_id);
                                
                                // Status badge colors
                                $status_colors = array(
                                    'publish' => '#00a32a',
                                    'draft' => '#996800',
                                    'pending' => '#c92c2c',
                                    'private' => '#2271b1',
                                    'future' => '#8c4fe5',
                                );
                                $status_color = isset($status_colors[$post_status]) ? $status_colors[$post_status] : '#666';
                                ?>
                                <tr>
                                    <td><?php echo esc_html($post_id); ?></td>
                                    <td>
                                        <strong>
                                            <a href="<?php echo esc_url($edit_url); ?>" target="_blank">
                                                <?php echo esc_html(get_the_title() ?: '(No Title)'); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td>
                                        <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; background: <?php echo esc_attr($status_color); ?>; color: #fff; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                            <?php echo esc_html($post_status); ?>
                                        </span>
                                    </td>
                                    <td title="<?php echo esc_attr($modified_date); ?>">
                                        <?php echo esc_html($modified_date_display); ?>
                                    </td>
                                    <td><?php echo esc_html($author); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url($edit_url); ?>" class="button button-small" target="_blank">Edit</a>
                                        <?php if ($post_status === 'publish') : ?>
                                            <a href="<?php echo esc_url($view_url); ?>" class="button button-small" target="_blank">View</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                    
                    <!-- Copy Summary Button -->
                    <button type="button" class="button" onclick="copyPostTypeList('<?php echo esc_js($post_type_key); ?>')" style="margin-top: 10px;">
                        📋 Copy List to Clipboard
                    </button>
                    
                    <!-- Hidden textarea for copying -->
                    <textarea id="copy-<?php echo esc_attr($post_type_key); ?>" style="position: absolute; left: -9999px;">
=== <?php echo $post_type_obj->labels->name; ?> (<?php echo $query->found_posts; ?> items) ===
<?php
                    $query->rewind_posts();
                    while ($query->have_posts()) {
                        $query->the_post();
                        echo sprintf(
                            "ID: %d | %s | %s | %s | %s\n",
                            get_the_ID(),
                            get_the_title() ?: '(No Title)',
                            get_post_status(),
                            get_the_modified_date('Y-m-d H:i:s'),
                            get_the_author()
                        );
                    }
?>
                    </textarea>
                </div>
                <?php
            }
            
            wp_reset_postdata();
        }
        ?>
        
        <script>
        function copyPostTypeList(postType) {
            const textarea = document.getElementById('copy-' + postType);
            textarea.style.position = 'static';
            textarea.select();
            document.execCommand('copy');
            textarea.style.position = 'absolute';
            
            // Show feedback
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = '✓ Copied!';
            button.style.background = '#00a32a';
            button.style.color = '#fff';
            
            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = '';
                button.style.color = '';
            }, 2000);
        }
        </script>
        
        <style>
            .wp-list-table th {
                font-weight: 600;
                background: #f6f7f7;
            }
            .wp-list-table a {
                text-decoration: none;
            }
            .wp-list-table a:hover {
                text-decoration: underline;
            }
        </style>
    </div>
    <?php
}
