<?php
/* 
Original code, which works but affects all titles, even latest post widgets.
# https://www.forumming.com/question/14734/change-the-title-of-a-page-dynamically
add_filter('loop_start', function () {
    add_filter('the_title', function ($title) {
            return '<a href="'.get_permalink( $post->ID ).'">'.$title.'</a>';
    });
});
*/

/*
Second Try, but also affects widgets

<?php
add_filter('loop_start', function () {
    if( in_the_loop() ) {
        add_filter('the_title', function ($title) {
            return '<a href="'.get_permalink( $post->ID ).'">'.$title.'</a>';
        });
    }
});*/

/* Actual solution https://wordpress.stackexchange.com/questions/309151/apply-the-title-filter-in-post-page-title-but-not-in-menu-title */

function wpse309151_title_update( $title, $id = null ) {
    if ( ! is_admin() && ! is_null( $id ) ) {
        $post = get_post( $id );
        if ( $post instanceof WP_Post && ( $post->post_type == 'post' || $post->post_type == 'page' ) ) {
            $new_title = '<a href="'.get_permalink( $post->ID ).'">'.$title.'</a>';
            if( ! empty( $new_title ) ) {
                return $new_title;
            }
        }
    }
    return $title;
}
add_filter( 'the_title', 'wpse309151_title_update', 10, 2 );

function wpse309151_remove_title_filter_nav_menu( $nav_menu, $args ) {
    // we are working with menu, so remove the title filter
    remove_filter( 'the_title', 'wpse309151_title_update', 10, 2 );
    return $nav_menu;
}
// this filter fires just before the nav menu item creation process
add_filter( 'pre_wp_nav_menu', 'wpse309151_remove_title_filter_nav_menu', 10, 2 );

function wpse309151_add_title_filter_non_menu( $items, $args ) {
    // we are done working with menu, so add the title filter back
    add_filter( 'the_title', 'wpse309151_title_update', 10, 2 );
    return $items;
}
// this filter fires after nav menu item creation is done
add_filter( 'wp_nav_menu_items', 'wpse309151_add_title_filter_non_menu', 10, 2 );