<?php
/**
 * Plugin Name: Prayer Times
 * Description: Displays Islamic prayer times via the AlAdhan API. Use shortcode [prayer_times] anywhere on your site.
 * Version: 1.0.2
 * Status: complete
 * Type: mu-plugin 
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ──────────────────────────────────────────────────────────────────
define( 'PT_VERSION',    '1.0.1' );
define( 'PT_OPTION_KEY', 'prayer_times_settings' );
define( 'PT_CACHE_KEY',  'prayer_times_daily_cache' );
define( 'PT_API_BASE',   'https://api.aladhan.com/v1/timingsByCity' );

// ── Settings defaults ──────────────────────────────────────────────────────────
function pt_default_settings(): array {
    return [
        'city'                    => 'Toronto',
        'country'                 => 'Canada',
        'state'                   => '',
        'method'                  => 2,       // ISNA — common in North America
        'school'                  => 0,       // 0 = Shafi, 1 = Hanafi (affects Asr only)
        'latitude_adj_method'     => 3,       // 3 = Angle Based (recommended for high latitudes)
        'time_format'             => '12',    // '12' or '24'
        'show_sunrise'            => true,
        'show_jumuah'             => false,
        'jumuah_time'             => '1:30 PM',
        'timezone'                => 'America/Toronto',
    ];
}

function pt_get_settings(): array {
    $saved    = get_option( PT_OPTION_KEY, [] );
    $defaults = pt_default_settings();
    return wp_parse_args( $saved, $defaults );
}

// ── Calculation methods list ───────────────────────────────────────────────────
function pt_methods(): array {
    return [
        0  => 'Shia Ithna-Ansari',
        1  => 'University of Islamic Sciences, Karachi',
        2  => 'Islamic Society of North America (ISNA)',
        3  => 'Muslim World League',
        4  => 'Umm Al-Qura University, Makkah',
        5  => 'Egyptian General Authority of Survey',
        7  => 'Institute of Geophysics, University of Tehran',
        8  => 'Gulf Region',
        9  => 'Kuwait',
        10 => 'Qatar',
        11 => 'Majlis Ugama Islam Singapura (MUIS)',
        12 => 'Union Organization Islamic de France',
        13 => 'Diyanet İşleri Başkanlığı, Turkey',
        14 => 'Spiritual Administration of Muslims of Russia',
        15 => 'Moonsighting Committee Worldwide',
    ];
}

// ── API fetch with daily transient cache ───────────────────────────────────────
function pt_fetch_timings(): array|WP_Error {
    $cached = get_transient( PT_CACHE_KEY );
    if ( false !== $cached ) {
        return $cached;
    }

    $s = pt_get_settings();

    // Build query — pass timezonestring so AlAdhan handles DST automatically.
    $args = [
        'city'                  => $s['city'],
        'country'               => $s['country'],
        'method'                => (int) $s['method'],
        'school'                => (int) $s['school'],
        'latitudeAdjustmentMethod' => (int) $s['latitude_adj_method'],
        'timezonestring'        => $s['timezone'],
    ];

    if ( ! empty( $s['state'] ) ) {
        $args['state'] = $s['state'];
    }

    $url      = add_query_arg( $args, PT_API_BASE );
    $response = wp_remote_get( $url, [ 'timeout' => 10 ] );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body['data']['timings'] ) ) {
        return new WP_Error( 'pt_api_error', 'AlAdhan API returned unexpected data.' );
    }

    $data = $body['data'];

    // Cache until midnight in the mosque's local timezone.
    $tz      = new DateTimeZone( $s['timezone'] );
    $now     = new DateTime( 'now', $tz );
    $midnight = new DateTime( 'tomorrow midnight', $tz );
    $ttl     = $midnight->getTimestamp() - $now->getTimestamp();

    set_transient( PT_CACHE_KEY, $data, max( $ttl, 300 ) );

    return $data;
}

// ── Format a single time string ────────────────────────────────────────────────
function pt_format_time( string $raw_time, string $format ): string {
    // Raw times from API are HH:MM (24-hour).
    $dt = DateTime::createFromFormat( 'H:i', $raw_time );
    if ( ! $dt ) {
        return $raw_time;
    }
    return $dt->format( $format === '12' ? 'g:i A' : 'H:i' );
}

// ── LiteSpeed Cache purge helper ───────────────────────────────────────────────
/**
 * Purge the front-page LiteSpeed cache after clearing prayer times.
 * Works via LSCWP's API class when the plugin is active, with a fallback
 * to the ESI/tag-based purge hook that LiteSpeed Server itself listens to.
 */
function pt_purge_litespeed_cache(): void {
    // Method 1: LiteSpeed Cache plugin API (supports static and instance methods).
    if ( class_exists( '\\LiteSpeed\\Purge' ) ) {
        try {
            $did_purge = false;
            $method    = new ReflectionMethod( '\\LiteSpeed\\Purge', 'purge_url' );

            if ( $method->isStatic() ) {
                \LiteSpeed\Purge::purge_url( home_url( '/' ) );
                $did_purge = true;
            } else {
                $purger = null;

                if ( method_exists( '\\LiteSpeed\\Purge', 'cls' ) ) {
                    $purger = \LiteSpeed\Purge::cls();
                } elseif ( method_exists( '\\LiteSpeed\\Purge', 'instance' ) ) {
                    $purger = \LiteSpeed\Purge::instance();
                } else {
                    $purger = new \LiteSpeed\Purge();
                }

                if ( is_object( $purger ) && method_exists( $purger, 'purge_url' ) ) {
                    $purger->purge_url( home_url( '/' ) );
                    $did_purge = true;
                }
            }

            if ( $did_purge ) {
                do_action( 'litespeed_purge_post', get_option( 'page_on_front' ) );
                return;
            }
        } catch ( Throwable $e ) {
            // Fall through to action-based and HTTP PURGE fallbacks.
        }
    }

    // Method 2: Trigger LSCWP purge action hooks (older LSCWP versions).
    if ( has_action( 'litespeed_purge_url' ) ) {
        do_action( 'litespeed_purge_url', home_url( '/' ) );
        return;
    }

    // Method 3: Direct HTTP PURGE request to LiteSpeed Server (no plugin needed).
    wp_remote_request( home_url( '/' ), [
        'method'   => 'PURGE',
        'headers'  => [ 'X-LiteSpeed-Purge' => '*' ],
        'timeout'  => 5,
        'blocking' => false,
    ] );
}

// ── Shortcode: [prayer_times] ──────────────────────────────────────────────────
add_shortcode( 'prayer_times', 'pt_shortcode' );

function pt_shortcode( $atts ): string {
    $atts = shortcode_atts( [ 'view' => 'inline' ], $atts, 'prayer_times' );
    $view = ( strtolower( trim( $atts['view'] ) ) === 'table' ) ? 'table' : 'inline';

    $s    = pt_get_settings();
    $data = pt_fetch_timings();

    if ( is_wp_error( $data ) ) {
        return '<p class="pt-error">Prayer times are temporarily unavailable. Please check back soon.</p>';
    }

    $timings = $data['timings'];
    $date    = $data['date']['readable'] ?? '';
    $fmt     = $s['time_format'];

    // Core prayers — Sunrise flagged separately as it is not a salah.
    $prayers = [
        'Fajr'    => [ 'label' => __( 'Fajr',    'prayer-times' ), 'is_prayer' => true  ],
        'Sunrise' => [ 'label' => __( 'Sunrise', 'prayer-times' ), 'is_prayer' => false ],
        'Dhuhr'   => [ 'label' => __( 'Dhuhr',   'prayer-times' ), 'is_prayer' => true  ],
        'Asr'     => [ 'label' => __( 'Asr',     'prayer-times' ), 'is_prayer' => true  ],
        'Maghrib' => [ 'label' => __( 'Maghrib', 'prayer-times' ), 'is_prayer' => true  ],
        'Isha'    => [ 'label' => __( 'Isha',    'prayer-times' ), 'is_prayer' => true  ],
    ];

    if ( ! $s['show_sunrise'] ) {
        unset( $prayers['Sunrise'] );
    }

    return $view === 'table'
        ? pt_render_table( $prayers, $timings, $date, $fmt, $s )
        : pt_render_inline( $prayers, $timings, $fmt, $s );
}

// ── Inline view: Prayer times : Fajr - 4:28 AM  ||  Dhuhr - 1:23 PM … ─────────
function pt_render_inline( array $prayers, array $timings, string $fmt, array $s ): string {
    $segments = [];
    foreach ( $prayers as $key => $info ) {
        $time       = isset( $timings[ $key ] ) ? pt_format_time( $timings[ $key ], $fmt ) : '—';
        $segments[] = sprintf(
            '<span class="pt-item pt-%s"><span class="pt-name">%s</span> <span class="pt-dash">-</span> <span class="pt-time">%s</span></span>',
            esc_attr( strtolower( $key ) ),
            esc_html( $info['label'] ),
            esc_html( $time )
        );
    }

    if ( ! empty( $s['show_jumuah'] ) && ! empty( $s['jumuah_time'] ) ) {
        $segments[] = sprintf(
            '<span class="pt-item pt-jummah"><span class="pt-name">%s</span> <span class="pt-dash">-</span> <span class="pt-time">%s</span></span>',
            esc_html__( 'Jummah', 'prayer-times' ),
            esc_html( $s['jumuah_time'] )
        );
    }

    $sep = '<span class="pt-sep">||</span>';

    return '<div class="pt-prayer-times pt-view-inline">'
        . '<span class="pt-label">' . esc_html__( 'Prayer times', 'prayer-times' ) . ' :</span> '
        . implode( ' ' . $sep . ' ', $segments )
        . '</div>';
}

// ── Table view: structured rows with optional Jumu'ah ────────────────────────────
function pt_render_table( array $prayers, array $timings, string $date, string $fmt, array $s ): string {
    ob_start();
    ?>
    <div class="pt-prayer-times pt-view-table">
        <?php if ( $date ) : ?>
            <h3 class="pt-date"><?php
                printf(
                    /* translators: %s: readable date */
                    esc_html__( 'Prayer Times for %s', 'prayer-times' ),
                    esc_html( $date )
                );
            ?></h3>
        <?php endif; ?>
        <h5 class="pt-subtitle"><?php esc_html_e( 'Start of each Prayer Time', 'prayer-times' ); ?></h5>
        <table class="prayer-timing-table-wrapper">
            <tbody>
            <?php foreach ( $prayers as $key => $info ) :
                $time       = isset( $timings[ $key ] ) ? pt_format_time( $timings[ $key ], $fmt ) : '—';
                $is_sunrise = ! $info['is_prayer'];
            ?>
                <tr class="pt-row-<?php echo esc_attr( strtolower( $key ) ); ?>"<?php echo $is_sunrise ? ' class="pt-sunrise-row"' : ''; ?>>
                    <td<?php echo $is_sunrise ? ' class="pt-sunrise"' : ''; ?>>
                        <strong><?php echo esc_html( $info['label'] ); ?></strong>
                    </td>
                    <td<?php echo $is_sunrise ? ' class="pt-sunrise"' : ''; ?>>
                        <?php echo esc_html( $time ); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ( ! empty( $s['show_jumuah'] ) && ! empty( $s['jumuah_time'] ) ) : ?>
                <tr class="pt-row-jumuah">
                    <td><strong><?php esc_html_e( "Jumu'ah", 'prayer-times' ); ?></strong></td>
                    <td><strong><?php echo esc_html( $s['jumuah_time'] ); ?></strong></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

// ── Inline styles (lightweight — override in your theme) ───────────────────────
add_action( 'wp_head', 'pt_inline_styles' );

function pt_inline_styles(): void {
    ?>
    <style id="prayer-times-styles">
        .pt-prayer-times { display: flex; flex-wrap: wrap; align-items: center; gap: .25em .1em; font-family: inherit; line-height: 1.6; }
        .pt-label        { font-weight: 700; margin-right: .4em; white-space: nowrap; }
        .pt-item         { white-space: nowrap; }
        .pt-name         { font-weight: 600; }
        .pt-dash         { margin: 0 .2em; }
        .pt-sep          { margin: 0 .75em; color: currentColor; opacity: .5; font-weight: 400; }
        .pt-error        { color: #c00; }
    </style>
    <?php
}

// ── Admin settings page ────────────────────────────────────────────────────────
add_action( 'admin_menu', 'pt_add_admin_page' );

function pt_add_admin_page(): void {
    add_options_page(
        __( 'Prayer Times Settings', 'prayer-times' ),
        __( 'Prayer Times', 'prayer-times' ),
        'manage_options',
        'prayer-times-settings',
        'pt_render_settings_page'
    );
}

add_action( 'admin_init', 'pt_register_settings' );

function pt_register_settings(): void {
    register_setting(
        'pt_settings_group',
        PT_OPTION_KEY,
        [ 'sanitize_callback' => 'pt_sanitize_settings' ]
    );
}

function pt_sanitize_settings( array $input ): array {
    $clean = pt_default_settings();

    $clean['city']                = sanitize_text_field( $input['city'] ?? '' );
    $clean['country']             = sanitize_text_field( $input['country'] ?? '' );
    $clean['state']               = sanitize_text_field( $input['state'] ?? '' );
    $clean['method']              = absint( $input['method'] ?? 2 );
    $clean['school']              = in_array( (int)($input['school'] ?? 0), [0,1], true ) ? (int)$input['school'] : 0;
    $clean['latitude_adj_method'] = in_array( (int)($input['latitude_adj_method'] ?? 3), [1,2,3], true ) ? (int)$input['latitude_adj_method'] : 3;
    $clean['time_format']         = in_array( $input['time_format'] ?? '12', ['12','24'], true ) ? $input['time_format'] : '12';
    $clean['show_sunrise']        = ! empty( $input['show_sunrise'] );
    $clean['show_jumuah']         = ! empty( $input['show_jumuah'] );
    $clean['jumuah_time']         = sanitize_text_field( $input['jumuah_time'] ?? '1:30 PM' );

    // Validate timezone string.
    $tz = sanitize_text_field( $input['timezone'] ?? 'UTC' );
    try {
        new DateTimeZone( $tz );
        $clean['timezone'] = $tz;
    } catch ( Exception $e ) {
        $clean['timezone'] = 'UTC';
        add_settings_error( PT_OPTION_KEY, 'bad_tz', __( 'Invalid timezone — reset to UTC.', 'prayer-times' ) );
    }

    // Clear transient and front-page LiteSpeed cache whenever settings change.
    delete_transient( PT_CACHE_KEY );
    pt_purge_litespeed_cache();

    return $clean;
}

function pt_render_settings_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $s       = pt_get_settings();
    $methods = pt_methods();
    $timezones = DateTimeZone::listIdentifiers();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Prayer Times Settings', 'prayer-times' ); ?></h1>
        <?php settings_errors( PT_OPTION_KEY ); ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'pt_settings_group' ); ?>

            <h2><?php esc_html_e( 'Location', 'prayer-times' ); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="pt_city"><?php esc_html_e( 'City', 'prayer-times' ); ?></label></th>
                    <td><input type="text" id="pt_city" name="<?php echo PT_OPTION_KEY; ?>[city]" value="<?php echo esc_attr( $s['city'] ); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pt_country"><?php esc_html_e( 'Country', 'prayer-times' ); ?></label></th>
                    <td><input type="text" id="pt_country" name="<?php echo PT_OPTION_KEY; ?>[country]" value="<?php echo esc_attr( $s['country'] ); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pt_state"><?php esc_html_e( 'State / Province', 'prayer-times' ); ?></label></th>
                    <td>
                        <input type="text" id="pt_state" name="<?php echo PT_OPTION_KEY; ?>[state]" value="<?php echo esc_attr( $s['state'] ); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Optional. Helps disambiguate cities with the same name.', 'prayer-times' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pt_timezone"><?php esc_html_e( 'Timezone', 'prayer-times' ); ?></label></th>
                    <td>
                        <select id="pt_timezone" name="<?php echo PT_OPTION_KEY; ?>[timezone]">
                            <?php foreach ( $timezones as $tz ) : ?>
                                <option value="<?php echo esc_attr( $tz ); ?>" <?php selected( $s['timezone'], $tz ); ?>>
                                    <?php echo esc_html( $tz ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'DST is handled automatically. Choose the timezone for the mosque\'s city.', 'prayer-times' ); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Calculation', 'prayer-times' ); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="pt_method"><?php esc_html_e( 'Calculation Method', 'prayer-times' ); ?></label></th>
                    <td>
                        <select id="pt_method" name="<?php echo PT_OPTION_KEY; ?>[method]">
                            <?php foreach ( $methods as $id => $name ) : ?>
                                <option value="<?php echo esc_attr( $id ); ?>" <?php selected( (int)$s['method'], $id ); ?>>
                                    <?php echo esc_html( $name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'ISNA (method 2) is most common in North America.', 'prayer-times' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Juristic School (Asr)', 'prayer-times' ); ?></th>
                    <td>
                        <label><input type="radio" name="<?php echo PT_OPTION_KEY; ?>[school]" value="0" <?php checked( (int)$s['school'], 0 ); ?>> <?php esc_html_e( 'Shafi / Maliki / Hanbali', 'prayer-times' ); ?></label><br>
                        <label><input type="radio" name="<?php echo PT_OPTION_KEY; ?>[school]" value="1" <?php checked( (int)$s['school'], 1 ); ?>> <?php esc_html_e( 'Hanafi', 'prayer-times' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pt_lat_adj"><?php esc_html_e( 'High-Latitude Adjustment', 'prayer-times' ); ?></label></th>
                    <td>
                        <select id="pt_lat_adj" name="<?php echo PT_OPTION_KEY; ?>[latitude_adj_method]">
                            <option value="1" <?php selected( (int)$s['latitude_adj_method'], 1 ); ?>><?php esc_html_e( 'Middle of the Night', 'prayer-times' ); ?></option>
                            <option value="2" <?php selected( (int)$s['latitude_adj_method'], 2 ); ?>><?php esc_html_e( 'One Seventh', 'prayer-times' ); ?></option>
                            <option value="3" <?php selected( (int)$s['latitude_adj_method'], 3 ); ?>><?php esc_html_e( 'Angle Based (recommended)', 'prayer-times' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Affects Fajr and Isha at higher latitudes (Canada, UK, Scandinavia, etc.).', 'prayer-times' ); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Display', 'prayer-times' ); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Time Format', 'prayer-times' ); ?></th>
                    <td>
                        <label><input type="radio" name="<?php echo PT_OPTION_KEY; ?>[time_format]" value="12" <?php checked( $s['time_format'], '12' ); ?>> <?php esc_html_e( '12-hour (e.g. 5:30 AM)', 'prayer-times' ); ?></label><br>
                        <label><input type="radio" name="<?php echo PT_OPTION_KEY; ?>[time_format]" value="24" <?php checked( $s['time_format'], '24' ); ?>> <?php esc_html_e( '24-hour (e.g. 05:30)', 'prayer-times' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Show Sunrise', 'prayer-times' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo PT_OPTION_KEY; ?>[show_sunrise]" value="1" <?php checked( $s['show_sunrise'] ); ?>>
                            <?php esc_html_e( 'Display sunrise time (not a prayer — used as Fajr end reference)', 'prayer-times' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( "Show Jumu'ah (table view only)", 'prayer-times' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo PT_OPTION_KEY; ?>[show_jumuah]" value="1" <?php checked( ! empty( $s['show_jumuah'] ) ); ?>>
                            <?php esc_html_e( "Display a Jumu'ah row at the bottom of the table", 'prayer-times' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pt_jumuah_time"><?php esc_html_e( "Jumu'ah Time", 'prayer-times' ); ?></label></th>
                    <td>
                        <input type="text" id="pt_jumuah_time" name="<?php echo PT_OPTION_KEY; ?>[jumuah_time]" value="<?php echo esc_attr( $s['jumuah_time'] ); ?>" class="small-text">
                        <p class="description"><?php esc_html_e( 'Fixed weekly time, e.g. 1:30 PM. Only shown when Jumu\'ah is enabled above.', 'prayer-times' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Save Settings', 'prayer-times' ) ); ?>
        </form>

        <hr>
        <h2><?php esc_html_e( 'Usage', 'prayer-times' ); ?></h2>
        <p><?php esc_html_e( 'Add one of the shortcodes below to any page, post, or widget:', 'prayer-times' ); ?></p>
        <p><code>[prayer_times]</code> — <?php esc_html_e( 'Single line: Prayer times : Fajr - 4:28 AM  ||  Dhuhr - 1:23 PM  …', 'prayer-times' ); ?></p>
        <p><code>[prayer_times view="table"]</code> — <?php esc_html_e( "Structured table with date heading and optional Jumu'ah row.", 'prayer-times' ); ?></p>

        <h2><?php esc_html_e( 'Cache', 'prayer-times' ); ?></h2>
        <p><?php esc_html_e( 'Prayer times are cached until midnight (local mosque time) to avoid unnecessary API calls. The cache clears automatically when you save settings.', 'prayer-times' ); ?></p>
        <?php
        $cached = get_transient( PT_CACHE_KEY );
        if ( $cached ) {
            $tz      = new DateTimeZone( $s['timezone'] );
            $midnight = new DateTime( 'tomorrow midnight', $tz );
            $now     = new DateTime( 'now', $tz );
            $diff    = $midnight->getTimestamp() - $now->getTimestamp();
            printf(
                '<p>' . esc_html__( 'Cache is active — refreshes in approximately %d minutes.', 'prayer-times' ) . '</p>',
                (int) round( $diff / 60 )
            );
        } else {
            echo '<p>' . esc_html__( 'No cache currently stored. Times will be fetched on the next page load.', 'prayer-times' ) . '</p>';
        }
        ?>

        <?php if ( $cached ) : ?>
        <form method="post">
            <?php wp_nonce_field( 'pt_clear_cache' ); ?>
            <input type="hidden" name="pt_action" value="clear_cache">
            <?php submit_button( __( 'Clear Cache Now', 'prayer-times' ), 'secondary' ); ?>
        </form>
        <?php endif; ?>
    </div>
    <?php
}

// Handle manual cache clear button.
add_action( 'admin_init', function () {
    if (
        isset( $_POST['pt_action'] ) &&
        $_POST['pt_action'] === 'clear_cache' &&
        current_user_can( 'manage_options' ) &&
        check_admin_referer( 'pt_clear_cache' )
    ) {
        delete_transient( PT_CACHE_KEY );
        pt_purge_litespeed_cache();
        wp_redirect( add_query_arg( 'pt_cleared', '1', wp_get_referer() ) );
        exit;
    }
} );

add_action( 'admin_notices', function () {
    if ( isset( $_GET['pt_cleared'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Prayer times cache cleared and LiteSpeed front-page cache purged.', 'prayer-times' ) . '</p></div>';
    }
} );