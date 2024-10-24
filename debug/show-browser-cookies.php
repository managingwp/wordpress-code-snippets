<?php
/** 
 * show-browser-cookies.php
 * Description Shows your browsers cookies
 * Type: snippet
 * Status: Complete
 */

add_action( 'admin_menu', function () {
		add_menu_page(
			__( 'Your Cookies', 'my-textdomain' ),
			__( 'Your Cookies', 'my-textdomain' ),
			'manage_options',
			'sample-page',
			'my_admin_page_contents',
			'dashicons-schedule',
			3
		);
	}
);

function my_admin_page_contents() {
	    echo '<h1>';
		esc_html_e( 'Your Cookies.', 'my-plugin-textdomain' );
		echo '</h1>';
		echo '<span>Cookies Set</span>';
        echo '<div><pre>'.print_r($_COOKIE,true).'</pre></div>';

}
