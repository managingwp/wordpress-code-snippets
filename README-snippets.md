# Minor Snippets
## [acf](acf)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [ACF Pull Contact Form Name IDs into Select Field](./acf/acf-pull-contact-form-name-ids.php) | 1.0.0 | snippet | :white_check_mark: | Pull Contact Form 6 form name and ID's into a select field called form_id dynamically. |

## [admin](admin)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [Change Admin Email](./admin/change-admin-email.php) | 1.0.0 | mu-plugin | :white_check_mark: | Changes the admin email address if it hasn't been changed already without triggering email validation. |
| [Disable WooCommerce Checkout & Restrict Logins to Admins](./admin/disable-checkout-and-limit-login.php) | 1.0.0 | mu-plugin | :white_check_mark: | Disables WooCommerce checkout and blocks login for all users except administrators. |
| [Log Admin Notices](./admin/log-admin-notices.php) | 1.0.0 | snippet | :white_check_mark: | Log's admin notices on admin_init, great for finding admin_notices to hide. |
| [Menu Shortcode](./admin/mu-dashboard-shortcode.php) | 1.0.0 | snippet | :construction: | Adds a shortcode to output the dashboard link. |
| [WP Admin Login Message](./admin/wp-admin-login-message.php) | 1.0.0 | mu-plugin | :white_check_mark: | Places a message on the WordPress Admin login page. |

## [ajaxlog](ajaxlog)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [Ajax Logger](./ajaxlog/ajaxlog.php) | 1.0.0 | mu-plugin | :white_check_mark: | Record all admin_init requests to troubleshoot high admin-ajax.php requests. |

## [blocks](blocks)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [Email Subscribe Block](./blocks/email-subscribe-redirect-block.php) | 1.0 |                                     type: 'email',  | :white_check_mark: | A simple form that redirects with email query param. |
| [Footer Navigation Block](./blocks/footer-navigation.php) | 1.0.0 |                         type: 'string', | :white_check_mark: | A block that renders a WordPress menu as an unordered list. |

## [buddyboss](buddyboss)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [BBP WPAL Filters](./buddyboss/bbp-wpal-filters.php) | 1.0.1 | mu-plugin | :white_check_mark: | BuddyBoss Platform Filters to improve page speed. |

## [caching](caching)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [disable-lscache-notice.php](./caching/disable-lscache-notice.php) | 1.0.0 | snippet | :construction: | Disables the admin notice Litespeeds LSCache generates about conflicting plugins installed |
| [disable-plugin-updates.php](./caching/disable-plugin-updates.php) | 1.0.0 | snippet | :white_check_mark: | Remove plugin update notices |
| [Disable WP Rocket Cache when Litespeed Cache is enabled.](./caching/disable-wp-rocket-lscache.php) |                 echo 'LiteSpeed Cache is active, version: ' . LSCWP_V; | mu-plugin | :white_check_mark: | This plugin disables WP Rocket cache when Litespeed Cache is enabled. It checks if Litespeed Cache is active and if so, it disables WP Rocket cache. |
| [Disable WP Rocket Cache Preload](./caching/disable-wp-rocket-preload.php) | 0.1.0 | mu-plugin | :white_check_mark: | This plugin disables WP Rocket cache preload functionality on a WordPress site. |
| [nginx-gravity-forms-stripe-cache-exclude.php](./caching/nginx-gravity-forms-stripe-cache-exclude.php) | 1.0.0 | snippet | :white_check_mark: | Exclude Gravity Forms or Stripe from caching |
| [nginx-helper-purge-schedule.php](./caching/nginx-helper-purge-schedule.php) | 1.0.0 | snippet | :white_check_mark: | Purge NGINX cache when a scheduled post is published |

## [core](core)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [Block HTTP Requests to a list of URLs](./core/block-http-requests.php) | 0.1.0 | * Type: snippet | :white_check_mark: | This plugin blocks HTTP requests to specific URLs, such as the WP Ultimo update server. |
| [enable-core-updates-version-control.php](./core/enable-core-updates-version-control.php) | 1.0.0 | mu-plugin | :white_check_mark: | Filters whether the automatic updater should consider a filesystem location to be potentially managed by a version control system. |

## [debug](debug)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [log-hook-calls.php](./debug/log-hook-calls.php) | 1.0.0 | mu-plugin | :white_check_mark: | Log all hook calls to a file |
| [PHP Memory Info](./debug/php-memory.php) | 1.0 | mu-plugin | :white_check_mark: | Adds a Tools -> PHP Memory page to display PHP ini and WordPress memory settings and current usage. |
| [show-browser-cookies.php](./debug/show-browser-cookies.php) | 1.0.0 | snippet | :question: |  * Description Shows your browsers cookies |
| [Application Logs](./debug/show-logs-admin.php) | 1.0 | snippet | :white_check_mark: | Displays top 10 lines from a log file in the WordPress admin section. |
| [WP Redirect Log](./debug/wp-redirect-log.php) | 1.0 | mu-plugin | :white_check_mark: | Logs all wp_redirect and wp_safe_redirect calls to a log file in the uploads directory. |

## [development](development)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [staging-environment.php](./development/staging-environment.php) | 1.0.0 | Snippet | :white_check_mark: | This snippet will enable and disable plugins based on the WP_ENVIRONMENT_TYPE  |

## [general](general)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [MU Shortcode: SVG QR for Current URL](./general/qr-code-page-link.php) | 1.0.0 | Snippet | :white_check_mark: | Adds [qr_svg] shortcode that renders an SVG QR code for the current page URL (or a provided URL). |
| [Updated Content Report](./general/updated-content.php) | 1.0.0 | mu-plugin | :white_check_mark: | Shows all posts by post type, sorted by last updated date for easy content migration tracking. |

## [mail](mail)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [wp-mail-log-to-file.php](./mail/wp-mail-log-to-file.php) | 1.0.0 | Snippet | :white_check_mark: | Log wp_mail function to a file |
| [wp-mail-test.php](./mail/wp-mail-test.php) | 1.0.0 | mu-plugin | :white_check_mark: | Used to send a test email to test the wp_mail function. |

## [maintenance](maintenance)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [wp-maintenance-mode.php](./maintenance/wp-maintenance-mode.php) | 1.0.0 | mu-plugin | :white_check_mark: | Maintenance mode for WordPress - Originally from https://wordpress.stackexchange.com/questions/398037/maintenance-mode-excluding-site-administrators |
| [wp-maintenance-mode2.php](./maintenance/wp-maintenance-mode2.php) | 1.0.0 | mu-plugin | :white_check_mark: | This plugin displays a maintenance message for non-administrative users. |

## [monitoring](monitoring)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [betteruptime-heartbeat.php](./monitoring/betteruptime-heartbeat.php) | 1.0.0 | mu-plugin | :white_check_mark: | Monitor WordPress cron via Better Uptime heartbeat checks. Originally from https://www.sprucely.net/knowledge-base/monitoring-wordpress-cron-via-heartbeat-checks/ |

## [multisite](multisite)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [sunrise-subdomain-to-subdirectory-mapping.php](./multisite/sunrise-subdomain-to-subdirectory-mapping.php) | 1.0.0 | mu-plugin | :white_check_mark: | Redirect domain names to multisite subdirectories and include query arguments in the redirect. |

## [plugins](plugins)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [Gravity Forms Notifications as Text not HTML](./plugins/gravityforms-text-notifications.php) | 0.1.0 | snippet | :white_check_mark: | All notifications will be sent as text versus the default html. |
| [Podcast Download Canonical Guard](./plugins/podcast.php) | 0.1.0 |             header('Content-Type: text/plain; charset=utf-8'); | :white_check_mark: | Prevents Seriously Simple Podcasting plugin redirect loops on missing podcast download URLs by disabling canonical redirects for 404 responses on /podcast-download/ paths. |
| [Plugin Name: PowerKit SVG Lazyload Fix](./plugins/powerkit-svg-lazyload-fix.php) | 1.0.0 | mu-plugin | :white_check_mark: | This script has two objectives, replace redirection_items and update |
| [redirection-search-replace.php](./plugins/redirection-search-replace.php) | 1.0.0 | script | :white_check_mark: | This script has two objectives, replace redirection_items and update |
| [Plugin Name: Simple Membership Members Per Page](./plugins/simple-membership-members-per-page.php) | Version: 1.0 | script | :white_check_mark: | This script has two objectives, replace redirection_items and update |
| [Ultimo Custom Domain Edits](./plugins/ultimo-custom-domain-edits.php) | 1.0 | snippet | :white_check_mark: | Custom Domain Edits for Ultimo |

## [tests](tests)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [test-open-tcp-ports.php](./tests/check-ports.php) | 1.0.0 | mu-plugin | :white_check_mark: | Original created to test if ports are open outbound on hosting providers to be able to send tranational email |

## [theme](theme)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [post-title-permalink.php](./theme/post-title-permalink.php) | 1.0.0 | Snippet | :white_check_mark: | This code will add a link to the title of the post, so that the title is clickable. |

## [ultimo](ultimo)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [WP Ultimo API Fix](./ultimo/ultimo-api-fix.php) | 0.1.0 | * Type: snippet | :white_check_mark: | Fixes the WP Ultimo API calls that fail and timeout after 10 seconds. |

## [woocommerce](woocommerce)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [WooCommerce Gateway ID Column](./woocommerce/woocommerce-gateway-id-column.php) | 2.1.0 | mu-plugin | :white_check_mark: | Adds a Gateway ID column to WooCommerce Orders and Subscriptions admin lists showing the payment gateway |
| [woo-commerce-hide-costofgoodssold-metadata.php](./woocommerce/woocommerce-hide-costofgoodssold-metadata.php) | 1.0.0 | snippet | :white_check_mark: | Hides item metadata for the WooCommerce Costs of Goods Sold on specific Booster for WooCommerce plugin shortcodes |
| [woocommerce-order-search-in-admin-bar.php](./woocommerce/woocommerce-order-search-in-admin-bar.php) | 1.0.0 | Snippet | :white_check_mark: | Add shop order search in the admin bar |
| [woocommerce-wc_product_loop_transient-expiration-to-1-day.php](./woocommerce/woocommerce-wc_product_loop_transient-expiration-to-1-day.php) | 1.0.0 | snippet | :white_check_mark: | Change wc_product_loop transient expiration to 1 day |

## [wp-failover](wp-failover)

| Title | Version | Type | Status | Description |
| ----- | ------- | ---- | ------ | ----------- |
| [Failover Status Monitor](./wp-failover/wp-failover.php) | 1.0.1 | mu-plugin | :white_check_mark: | Monitors failover status and provides notifications. |

