# wordpress-code-snippets
A collection of WordPress PHP code snippets.

The original intent was for use in WP Codebox, but they can be used in any WordPress theme, plugin or as a mu-plugin.

# Status Field
The status field is used to indicate the current status of the snippet. This is useful for tracking the progress of a snippet. The following statuses are used:
* Complete - :heavy_check_mark:
* WIP - :construction:
* Unknown - :question:
* Broken - :x:

# Generate README.md
* Run generate-readme.sh to generate the README.md file.
# Minor Snippets
## [acf](acf)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [acf-pull-contact-form-name-ids.php](./acf/acf-pull-contact-form-name-ids.php) | snippet | :white_check_mark: | Pull Contact Form 6 form name and ID's into a select field called form_id dynamically. |

## [admin](admin)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [Change Admin Email](./admin/change-admin-email.php) | mu-plugin | :white_check_mark: | Changes the admin email address if it hasn't been changed already without triggering email validation. |
| [log-admin-notices.php](./admin/log-admin-notices.php) | snippet | :white_check_mark: | Log's admin notices on admin_init, great for finding admin_notices to hide. |
| [mu-dashboard-shortcode.php](./admin/mu-dashboard-shortcode.php) | snippet | :construction: | Unsure what the usage of this snippet is. |

## [ajaxlog](ajaxlog)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [Ajax Logger](./ajaxlog/ajaxlog.php) | Plugin | :white_check_mark: | Record all admin_init requests to troubleshoot high admin-ajax.php requests. |

## [buddyboss](buddyboss)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [BBP WPAL Filters](./buddyboss/bbp-wpal-filters.php) | Plugin | :white_check_mark: | BuddyBoss Platform Filters to improve page speed. |

## [caching](caching)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [disable-lscache-notice.php](./caching/disable-lscache-notice.php) | snippet | :construction: | Disables the admin notice Litespeeds LSCache generates about conflicting plugins installed |
| [disable-plugin-updates.php](./caching/disable-plugin-updates.php) | snippet | :white_check_mark: | Remove plugin update notices |
| [Disable WP Rocket Cache when Litespeed Cache is enabled.](./caching/disable-wp-rocket-lscache.php) | mu-plugin | :white_check_mark: | This plugin disables WP Rocket cache when Litespeed Cache is enabled. It checks if Litespeed Cache is active and if so, it disables WP Rocket cache. |
| [nginx-gravity-forms-stripe-cache-exclude.php](./caching/nginx-gravity-forms-stripe-cache-exclude.php) | snippet | :white_check_mark: | Exclude Gravity Forms or Stripe from caching |
| [nginx-helper-purge-schedule.php](./caching/nginx-helper-purge-schedule.php) | snippet | :white_check_mark: | Purge NGINX cache when a scheduled post is published |

## [core](core)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [enable-core-updates-version-control.php](./core/enable-core-updates-version-control.php) | Plugin | :white_check_mark: | Filters whether the automatic updater should consider a filesystem location to be potentially managed by a version control system. |

## [debug](debug)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [log-hook-calls.php](./debug/log-hook-calls.php) | Plugin | :white_check_mark: | Log all hook calls to a file |
| [PHP Memory Info](./debug/php-memory.php) | mu-plugin | :white_check_mark: | Adds a Tools -> PHP Memory page to display PHP ini and WordPress memory settings and current usage. |
| [show-browser-cookies.php](./debug/show-browser-cookies.php) | snippet | :white_check_mark: |  * Description Shows your browsers cookies |
| [Application Logs](./debug/show-logs-admin.php) | Plugin | :white_check_mark: | Displays top 10 lines from a log file in the WordPress admin section. |

## [development](development)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [staging-environment.php](./development/staging-environment.php) | Snippet | :white_check_mark: | This snippet will enable and disable plugins based on the WP_ENVIRONMENT_TYPE  |

## [mail](mail)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [wp-mail-log-to-file.php](./mail/wp-mail-log-to-file.php) | Plugin | :white_check_mark: | Log wp_mail function to a file |
| [wp-mail-test.php](./mail/wp-mail-test.php) | Plugin | :white_check_mark: | Used to send a test email to test the wp_mail function. |

## [maintenance](maintenance)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [wp-maintenance-mode.php](./maintenance/wp-maintenance-mode.php) | Plugin | :white_check_mark: | Maintenance mode for WordPress - Originally from https://wordpress.stackexchange.com/questions/398037/maintenance-mode-excluding-site-administrators |
| [wp-maintenance-mode2.php](./maintenance/wp-maintenance-mode2.php) | Plugin | :white_check_mark: | This plugin displays a maintenance message for non-administrative users. |

## [monitoring](monitoring)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [betteruptime-heartbeat.php](./monitoring/betteruptime-heartbeat.php) | Plugin | :white_check_mark: | Monitor WordPress cron via Better Uptime heartbeat checks. Originally from https://www.sprucely.net/knowledge-base/monitoring-wordpress-cron-via-heartbeat-checks/ |

## [multisite](multisite)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [sunrise-subdomain-to-subdirectory-mapping.php](./multisite/sunrise-subdomain-to-subdirectory-mapping.php) | Plugin | :white_check_mark: | Redirect domain names to multisite subdirectories and include query arguments in the redirect. |

## [plugins](plugins)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [redirection-search-replace.php](./plugins/redirection-search-replace.php) | script | :white_check_mark: | This script has two objectives, replace redirection_items and update |
| [Plugin Name: Simple Membership Members Per Page](./plugins/simple-membership-members-per-page.php) | script | :white_check_mark: | This script has two objectives, replace redirection_items and update |
| [Ultimo Custom Domain Edits](./plugins/ultimo-custom-domain-edits.php) | Plugin | :white_check_mark: | Custom Domain Edits for Ultimo |

## [tests](tests)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [check-ports.php](./tests/check-ports.php) | Plugin | :white_check_mark: | Original created to test if ports are open outbound on hosting providers to be able to send tranational email |

## [theme](theme)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [post-title-permalink.php](./theme/post-title-permalink.php) | Snippet | :white_check_mark: | This code will add a link to the title of the post, so that the title is clickable. |

## [ultimo](ultimo)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [WP Ultimo API Fix](./ultimo/ultimo-api-fix.php) | * Type: snippet | :white_check_mark: | Fixes the WP Ultimo API calls that fail and timeout after 10 seconds. |

## [woocommerce](woocommerce)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [woocommerce-hide-costofgoodssold-metadata.php](./woocommerce/woocommerce-hide-costofgoodssold-metadata.php) | snippet | :white_check_mark: | Hides item metadata for the WooCommerce Costs of Goods Sold on specific Booster for WooCommerce plugin shortcodes |
| [woocommerce-order-search-in-admin-bar.php](./woocommerce/woocommerce-order-search-in-admin-bar.php) | Snippet | :white_check_mark: | Add shop order search in the admin bar |
| [woocommerce-wc_product_loop_transient-expiration-to-1-day.php](./woocommerce/woocommerce-wc_product_loop_transient-expiration-to-1-day.php) | snippet | :white_check_mark: | Change wc_product_loop transient expiration to 1 day |

## [wp-failover](wp-failover)

| Title | Type | Status | Description |
| ----- | ---- | ------ | ----------- |
| [Failover Status Monitor](./wp-failover/wp-failover.php) | Plugin | :white_check_mark: | Monitors failover status and provides notifications. |

# Changelog
## Relase 1.3.8
* feat(debug): Added php-memory.php


## Release 1.3.7
* feat(ultimo): Added ultimo-api-fix.php


## Release 1.3.6
* improvement: Added better detection to disable-wp-rocket-lscache.php
* fix: Errors with disable-lscache-notice.php


## Relase 1.3.5
* docs(readme): Updated README-header.md to add in generation process
* feat(cache): Added disable-wp-rocket-lscache.php


## Release 1.3.4
* feat(caching): Added nginx-helper-purge-schedule.php


## Release 1.3.3
* Removed wp-cli package cron logger and created it's own repository.


## Release 1.3.2
* feat(wp-cli): Added wp-cli package cron-logger


## Release 1.3.1
* feat: Addd Simple Membership member per page snippet
* improvement: Updated generate-readme.sh to generate CHANGELOG.md


## Relase 1.3.0
* refactor: Complete refactor of the entire repository.


## Release 1.2.2
* Added cloudflare load balancing log url
* Merge pull request #2 from nickchomey/patch-2 - Update ajaxlog.php - Move logs to a subdirectory to keep root tidy


## Release 1.2.1
* Updated wp-failover.php to include more features.


## Release 1.2
* Added wp-failover code.


## Release 1.1
* feat: Added acf-pull-contact-form-name-ids.php
* improvement: Nick Chomey merged PR#1 Mega improvements to ajaxlog.php
* feat: Created woocommerce-wc_product_loop_transient-expiration-to-1-day.php
* improvement: Moved all WooCommerce snippets to woocommerce folder
* feat(buddyboss): Found BBP WPAL Filters in the wild.
* fix(buddyboss): Fixed bbp-wpal-filters.php due to cut off.
* feat: Added check-ports.php
* feat: Added change-admin-email.php


## Release 1.0.3
* feat(redirection-search-replace): Added redirection-search-replace.php
* docs(redirection-search-replace): Updated doc for redirection-search-replace.php
* fix: Fixed typo
* feat: Added multisite/sunrise-subdomain-to-subdiretory-mapping.php
* feat(caching): Added nginx-gravity-forms-stripe-cache-exclude.php


## Release v1.0.2
* feat(show-logs-admin): Added show-logs-admin.php


## Release 1.0.1
* chore(ajaxlog): Put ajaxlog.php and ajaxlog.md into own directory
* chore(ajaxlog): Renamed ajaxlog.md to README.md


## Squased git commit history.
* 9ebde9d (HEAD -> dev, origin/dev) Merging in changes from dev branch
* 1315d61 Create ultimo-custom-domain-edits.php file
* 549760f Create mu-dashboard-shortcode.php
* 501ad64 Merge branch 'main' of github.com:managingwp/wordpress-code-snippets into main
* 804c764 Update wp_mail.php
* 81a5195 Update log-wp_mail.php
* 0804bdb Update log-wp_mail.php
* d83bea2 Create wp_mail.php
* ffa7a48 Update betteruptime-heartbeat.php
* db9e200 Added file log-admin-notices.php
* fbe1efa woocommerce-hide-costofgoodssold-metadata.php
* aef11bc Issue with disable-plugin-updates.php having no add_filter woops.
* 2174646 Update disable-plugin-updates.php
* 38b1c6d * Added file disable-lscache-notice.php Disables the admin notice Litespeeds LSCache generates about conflicting plugins installed * Added log-wp-mail.php to log mail() function to file.
* 5e19134 Added file disable-lscache-notice.php Disables the admin notice Litespeeds LSCache generates about conflicting plugins installed
* 4cd9b6e Create betteruptime-heartbeat.php
* 4d3d0db Merge branch 'main' into dev
* 7532325 Typo and added credit
* 8f89a22 Merge branch 'main' into dev
* 27b408a Changed code in post-title-permalink.php as it broke menus
* f6d7a50 Merge branch 'dev' of github.com:managingwp/wordpress-code-snippets into dev
* f68d8fe Changed code in post-title-permalink.php as it broke menus
* 0de3636 Update README.md
* d78036a Added disable-plugin-updates.php enable-core-updates-version-control.php log-hook-calls.php
* 1e7edc5 Merge branch 'dev' of github.com:jordantrizz/wordpress-code-snippets into dev
* 21c2507 Added log-hook-calls.php
* 74884b2 Merge branch 'main' into dev
* 5e83a9d Added post-title-permalink.php
* d607c30 Merge branch 'main' into dev
* f405fc5 Improved overall code, added if request is from a visitor or logged in admin
* 701e125 Improved overall code to be one line
* 92b82ec Improved overall code to be one line
* 17b9d24 Merge branch 'main' into dev
* fd65bcb Updated improper code syntax to support PHP8
* df5225a Merge branch 'main' into dev
* 2aedceb Restructure repository files
* ab023b4 Restructure repository files
* 852b0dc Update README.md
* 21c0198 Update README.md
* 2071565 Update README.md
* 4f15c9e Update README.md
* 20af5d4 Update README.md
* 0028bde Update README.md
* ac8a5a6 Delete ajaxlog.php
* 5bb8217 Create ajaxlog.php
* b9ecdb0 Create README.md
* 5328ed3 Update ajaxlog.php
* c1123ae Update ajaxlog.php
* e981c54 Update ajaxlog.php
* 2205405 Update ajaxlog.php
* a2a6648 Create ajaxlog.php
* 6d37733 Create wp-admin-maintenance-mode.php
* f42de0e Create show-browser-cookies.php
* c7489af Update woocommerce-order-search-in-admin-bar.php
* d01d9a6 Added staging-environment.php
* 86c0197 Another update
* 4bfc0d3 Updated by Ovidiu to work properly on WooCommerce order page
* 3a26821 Update woocommerce-order-search-in-admin-bar.php
* 1c17d42 Update woocommerce-order-search-in-admin-bar.php
* a9fab44 first commit

