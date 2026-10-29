---
title: Changelog
description: Every released version of the snippets, newest first.
---

Releases are grouped by git tag. Commits since the last tag are on
[GitHub](https://github.com/managingwp/wordpress-code-snippets/commits/main).

<!-- BEGIN GENERATED: changelog -->
### 1.4.19 (2026-03-14)

- fix(plugin): add inline Jummah and harden LiteSpeed purge call

### 1.4.18 (2026-03-13)

- improvement: Updated wp-redirect-log.php to include trace data

### 1.4.17 (2026-03-13)

- improvement: Updated prayer-times.php to have a table view
- feat: Added shortcode/prayer-times.php

### 1.4.16 (2026-03-03)

- feat: created theme/generatepress-animations-sal.php

### 1.4.15 (2026-02-12)

- feat(wp-failover): Add REST API status endpoint for external monitoring
- feat(wp-failover): add copyable status endpoint URL box

### 1.4.14 (2025-11-21)

- Added woocommerce/woocommerce-gateway-id-column.php

### 1.4.13 (2025-11-21)

- Added plugins/gravityforms-text-notifications.php

### 1.4.12 (2025-11-06)

- Updated wp-redirect-log.php to include siteurl and home as well as backtrace.

### 1.4.11 (2025-11-03)

- Added updated-content.php snippet

### 1.4.10 (2025-11-03)

- Created blocks footer-navigation.php

### 1.4.9 (2025-11-03)

- Updated email-subscribe-redirect-block.php to add submit button hover text color.

### 1.4.8 (2025-10-31)

- Added email-subcribe-redirect-block.php

### 1.4.7 (2025-10-28)

- Added AGENTS.md
- Updated multiple plugins without plugin name or type
- added general/qr-code-page-link.php

### 1.4.6 (2025-10-06)

- Added plugins/podcasts.php

### 1.4.5 (2025-10-06)

- Added block-http-requests.php

### 1.4.4 (2025-08-15)

- Added disable-wp-rocket-preload.php

### 1.4.3 (2025-08-02)

- Added wp-admin-login-message.php for adding messages to the WordPress admin login page.

### 1.4.2 (2025-07-16)

- improvement: Updated generate-readme.sh to check for missing fields
- Require version to be a field
- Added plugin powerkit-svg-lazyload-fix.php

### 1.4.1 (2025-07-08)

- Updated disable-checkout-and-limit-login.php to be disabled via define('WP_MAINTENANCE_MODE', true);

### 1.4.0 (2025-07-08)

- Added disable-checkout-and-limit-login.php

### 1.3.9 (2025-05-23)

- feat(debug): Added wp-redirect-log.php mu-plugin

### 1.3.8 (2025-04-29)

- feat(debug): Added php-memory.php

### 1.3.7 (2025-04-24)

- feat(ultimo): Added ultimo-api-fix.php

### 1.3.6 (2025-04-23)

- improvement: Added better detection to disable-wp-rocket-lscache.php
- fix: Errors with disable-lscache-notice.php

### 1.3.5 (2025-04-16)

- docs(readme): Updated README-header.md to add in generation process
- feat(cache): Added disable-wp-rocket-lscache.php

### 1.3.4 (2025-04-11)

- feat(caching): Added nginx-helper-purge-schedule.php

### 1.3.3 (2025-03-06)

- Removed wp-cli package cron logger and created it's own repository.

### 1.3.2 (2025-03-04)

- feat(wp-cli): Added wp-cli package cron-logger

### 1.3.1 (2025-02-03)

- feat: Addd Simple Membership member per page snippet
- improvement: Updated generate-readme.sh to generate CHANGELOG.md

### 1.3.0 (2024-10-24)

- refactor: Complete refactor of the entire repository.

### 1.2.2 (2024-10-24)

- Added cloudflare load balancing log url
- Merge pull request #2 from nickchomey/patch-2 - Update ajaxlog.php - Move logs to a subdirectory to keep root tidy

### 1.2.1 (2024-09-06)

- Updated wp-failover.php to include more features.

### 1.2 (2024-09-04)

- Added wp-failover code.

### 1.1 (2024-09-04)

- feat: Added acf-pull-contact-form-name-ids.php
- improvement: Nick Chomey merged PR#1 Mega improvements to ajaxlog.php
- feat: Created woocommerce-wc_product_loop_transient-expiration-to-1-day.php
- improvement: Moved all WooCommerce snippets to woocommerce folder
- feat(buddyboss): Found BBP WPAL Filters in the wild.
- fix(buddyboss): Fixed bbp-wpal-filters.php due to cut off.
- feat: Added check-ports.php
- feat: Added change-admin-email.php

### 1.0.3 (2023-09-18)

- feat(redirection-search-replace): Added redirection-search-replace.php
- docs(redirection-search-replace): Updated doc for redirection-search-replace.php
- fix: Fixed typo
- feat: Added multisite/sunrise-subdomain-to-subdiretory-mapping.php
- feat(caching): Added nginx-gravity-forms-stripe-cache-exclude.php

### 1.0.2 (2023-06-15)

- feat(show-logs-admin): Added show-logs-admin.php

### 1.0.0 (2023-06-01)

- 9ebde9d (HEAD -> dev, origin/dev) Merging in changes from dev branch
- 1315d61 Create ultimo-custom-domain-edits.php file
- 549760f Create mu-dashboard-shortcode.php
- 501ad64 Merge branch 'main' of github.com:managingwp/wordpress-code-snippets into main
- 804c764 Update wp_mail.php
- 81a5195 Update log-wp_mail.php
- 0804bdb Update log-wp_mail.php
- d83bea2 Create wp_mail.php
- ffa7a48 Update betteruptime-heartbeat.php
- db9e200 Added file log-admin-notices.php
- fbe1efa woocommerce-hide-costofgoodssold-metadata.php
- aef11bc Issue with disable-plugin-updates.php having no add_filter woops.
- 2174646 Update disable-plugin-updates.php
- 38b1c6d * Added file disable-lscache-notice.php Disables the admin notice Litespeeds LSCache generates about conflicting plugins installed * Added log-wp-mail.php to log mail() function to file.
- 5e19134 Added file disable-lscache-notice.php Disables the admin notice Litespeeds LSCache generates about conflicting plugins installed
- 4cd9b6e Create betteruptime-heartbeat.php
- 4d3d0db Merge branch 'main' into dev
- 7532325 Typo and added credit
- 8f89a22 Merge branch 'main' into dev
- 27b408a Changed code in post-title-permalink.php as it broke menus
- f6d7a50 Merge branch 'dev' of github.com:managingwp/wordpress-code-snippets into dev
- f68d8fe Changed code in post-title-permalink.php as it broke menus
- 0de3636 Update README.md
- d78036a Added disable-plugin-updates.php enable-core-updates-version-control.php log-hook-calls.php
- 1e7edc5 Merge branch 'dev' of github.com:jordantrizz/wordpress-code-snippets into dev
- 21c2507 Added log-hook-calls.php
- 74884b2 Merge branch 'main' into dev
- 5e83a9d Added post-title-permalink.php
- d607c30 Merge branch 'main' into dev
- f405fc5 Improved overall code, added if request is from a visitor or logged in admin
- 701e125 Improved overall code to be one line
- 92b82ec Improved overall code to be one line
- 17b9d24 Merge branch 'main' into dev
- fd65bcb Updated improper code syntax to support PHP8
- df5225a Merge branch 'main' into dev
- 2aedceb Restructure repository files
- ab023b4 Restructure repository files
- 852b0dc Update README.md
- 21c0198 Update README.md
- 2071565 Update README.md
- 4f15c9e Update README.md
- 20af5d4 Update README.md
- 0028bde Update README.md
- ac8a5a6 Delete ajaxlog.php
- 5bb8217 Create ajaxlog.php
- b9ecdb0 Create README.md
- 5328ed3 Update ajaxlog.php
- c1123ae Update ajaxlog.php
- e981c54 Update ajaxlog.php
- 2205405 Update ajaxlog.php
- a2a6648 Create ajaxlog.php
- 6d37733 Create wp-admin-maintenance-mode.php
- f42de0e Create show-browser-cookies.php
- c7489af Update woocommerce-order-search-in-admin-bar.php
- d01d9a6 Added staging-environment.php
- 86c0197 Another update
- 4bfc0d3 Updated by Ovidiu to work properly on WooCommerce order page
- 3a26821 Update woocommerce-order-search-in-admin-bar.php
- 1c17d42 Update woocommerce-order-search-in-admin-bar.php
- a9fab44 first commit

<!-- END GENERATED: changelog -->
