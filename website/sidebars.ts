import type {SidebarsConfig} from '@docusaurus/plugin-content-docs';

// Only the pages owned by hand are listed here. The snippet categories below the
// marker are written by ./generate-readme.sh from the snippet headers - do not
// edit that block by hand.
const sidebars: SidebarsConfig = {
  docs: [
    {type: 'doc', id: 'index', label: 'Overview'},
    {type: 'doc', id: 'getting-started/install-a-snippet', label: 'Install a snippet'},
    {type: 'doc', id: 'reference/snippet-headers', label: 'Snippet headers'},
    {type: 'doc', id: 'contributing/add-a-snippet', label: 'Add a snippet'},
    {type: 'doc', id: 'history/changelog', label: 'Changelog'},
    // BEGIN GENERATED: snippets
    {type: 'category', label: 'ACF', items: ['snippets/acf/acf-pull-contact-form-name-ids']},
    {type: 'category', label: 'Admin', items: ['snippets/admin/change-admin-email', 'snippets/admin/disable-checkout-and-limit-login', 'snippets/admin/log-admin-notices', 'snippets/admin/mu-dashboard-shortcode', 'snippets/admin/wp-admin-login-message']},
    {type: 'category', label: 'Ajax Log', items: ['snippets/ajaxlog/ajaxlog']},
    {type: 'category', label: 'Blocks', items: ['snippets/blocks/email-subscribe-redirect-block', 'snippets/blocks/footer-navigation']},
    {type: 'category', label: 'BuddyBoss', items: ['snippets/buddyboss/bbp-wpal-filters']},
    {type: 'category', label: 'Caching', items: ['snippets/caching/disable-lscache-notice', 'snippets/caching/disable-plugin-updates', 'snippets/caching/disable-wp-rocket-lscache', 'snippets/caching/disable-wp-rocket-preload', 'snippets/caching/nginx-gravity-forms-stripe-cache-exclude', 'snippets/caching/nginx-helper-purge-schedule']},
    {type: 'category', label: 'Core', items: ['snippets/core/block-http-requests', 'snippets/core/enable-core-updates-version-control']},
    {type: 'category', label: 'Debug', items: ['snippets/debug/log-hook-calls', 'snippets/debug/php-memory', 'snippets/debug/show-browser-cookies', 'snippets/debug/show-logs-admin', 'snippets/debug/show-server-ip-admin', 'snippets/debug/wp-redirect-log']},
    {type: 'category', label: 'Development', items: ['snippets/development/staging-environment']},
    {type: 'category', label: 'General', items: ['snippets/general/qr-code-page-link', 'snippets/general/updated-content']},
    {type: 'category', label: 'Mail', items: ['snippets/mail/wp-mail-log-to-file', 'snippets/mail/wp-mail-test']},
    {type: 'category', label: 'Maintenance', items: ['snippets/maintenance/wp-maintenance-major', 'snippets/maintenance/wp-maintenance-mode', 'snippets/maintenance/wp-maintenance-mode2']},
    {type: 'category', label: 'Monitoring', items: ['snippets/monitoring/betteruptime-heartbeat']},
    {type: 'category', label: 'Multisite', items: ['snippets/multisite/sunrise-subdomain-to-subdirectory-mapping']},
    {type: 'category', label: 'Plugins', items: ['snippets/plugins/gravityforms-text-notifications', 'snippets/plugins/podcast', 'snippets/plugins/powerkit-svg-lazyload-fix', 'snippets/plugins/redirection-search-replace', 'snippets/plugins/simple-membership-members-per-page', 'snippets/plugins/ultimo-custom-domain-edits']},
    {type: 'category', label: 'Shortcodes', items: ['snippets/shortcodes/prayer-times']},
    {type: 'category', label: 'Tests', items: ['snippets/tests/check-ports']},
    {type: 'category', label: 'Theme', items: ['snippets/theme/generatepress-animations-sal', 'snippets/theme/post-title-permalink']},
    {type: 'category', label: 'Ultimo', items: ['snippets/ultimo/ultimo-api-fix']},
    {type: 'category', label: 'WooCommerce', items: ['snippets/woocommerce/woocommerce-gateway-id-column', 'snippets/woocommerce/woocommerce-hide-costofgoodssold-metadata', 'snippets/woocommerce/woocommerce-order-search-in-admin-bar', 'snippets/woocommerce/woocommerce-wc_product_loop_transient-expiration-to-1-day']},
    {type: 'category', label: 'WP Failover', items: ['snippets/wp-failover/wp-failover']},
    // END GENERATED: snippets
  ],
};

export default sidebars;
