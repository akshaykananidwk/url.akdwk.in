<?php
/**
 * Plugin Name: Shortl URL Shortener
 * Description: Automatically create short links for your posts using your self-hosted Shortl instance, and shorten any URL from the editor.
 * Version: 1.0.0
 * Author: Shortl
 *
 * Setup: Settings → Shortl. Enter your site URL (e.g. https://url.akdwk.in) and
 * an API key (Developers → API Keys on your Shortl account).
 */

if (! defined('ABSPATH')) { exit; }

add_action('admin_menu', function () {
    add_options_page('Shortl', 'Shortl', 'manage_options', 'shortl', 'shortl_settings_page');
});

add_action('admin_init', function () {
    register_setting('shortl', 'shortl_base_url');
    register_setting('shortl', 'shortl_api_key');
    register_setting('shortl', 'shortl_auto');
});

function shortl_settings_page() {
    ?>
    <div class="wrap">
        <h1>Shortl URL Shortener</h1>
        <form method="post" action="options.php">
            <?php settings_fields('shortl'); ?>
            <table class="form-table">
                <tr><th>Shortl site URL</th><td><input type="url" name="shortl_base_url" value="<?php echo esc_attr(get_option('shortl_base_url')); ?>" class="regular-text" placeholder="https://url.akdwk.in"></td></tr>
                <tr><th>API key</th><td><input type="password" name="shortl_api_key" value="<?php echo esc_attr(get_option('shortl_api_key')); ?>" class="regular-text" placeholder="sk_..."></td></tr>
                <tr><th>Auto-shorten new posts</th><td><label><input type="checkbox" name="shortl_auto" value="1" <?php checked(get_option('shortl_auto'), '1'); ?>> Create a short link when a post is published, stored as the "shortl_url" custom field.</label></td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/** Call the Shortl REST API to shorten a URL. Returns the short URL or WP_Error. */
function shortl_shorten($url) {
    $base = rtrim(get_option('shortl_base_url'), '/');
    $key  = get_option('shortl_api_key');
    if (! $base || ! $key) { return new WP_Error('shortl', 'Shortl is not configured.'); }

    $res = wp_remote_post($base . '/api/v1/links', array(
        'headers' => array('Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json', 'Accept' => 'application/json'),
        'body'    => wp_json_encode(array('destination' => $url)),
        'timeout' => 15,
    ));
    if (is_wp_error($res)) { return $res; }
    $body = json_decode(wp_remote_retrieve_body($res), true);
    if (empty($body['data']['short_url'])) { return new WP_Error('shortl', 'Unexpected response.'); }
    return $body['data']['short_url'];
}

/** Auto-shorten on publish. */
add_action('publish_post', function ($post_id) {
    if (get_option('shortl_auto') !== '1') { return; }
    if (get_post_meta($post_id, 'shortl_url', true)) { return; }
    $short = shortl_shorten(get_permalink($post_id));
    if (! is_wp_error($short)) { update_post_meta($post_id, 'shortl_url', $short); }
}, 10, 1);

/** [shortl url="https://..."] shortcode. */
add_shortcode('shortl', function ($atts) {
    $atts = shortcode_atts(array('url' => ''), $atts);
    if (! $atts['url']) { return ''; }
    $short = shortl_shorten($atts['url']);
    return is_wp_error($short) ? esc_html($atts['url']) : esc_html($short);
});
