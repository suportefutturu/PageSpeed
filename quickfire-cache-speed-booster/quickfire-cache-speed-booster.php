<?php
/*
Plugin Name: Quickfire Cache and Speed Booster
Plugin URI: https://www.plugins-for-wp.com/
Description: Boost website performance with caching, minification, lazy loading, and script optimization.
Version: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: quickfire-cache-speed-booster
*/

if (!defined('ABSPATH')) exit;
define('SSBC_VERSION', '1.0.0');
define('SSBC_PATH', plugin_dir_path(__FILE__));
define('SSBC_URL', plugin_dir_url(__FILE__));
define('SSBC_CACHE_DIR', WP_CONTENT_DIR.'/cache/ssbc/');
define('SSBC_CACHE_URL', WP_CONTENT_URL.'/cache/ssbc/');
define('SSBC_PRO_URL', 'https://www.plugins-for-wp.com/?ssp_src=repo-speed-booster');

function ssbc_speed_tests_table_name() { global $wpdb; return $wpdb->prefix.'ssbc_speed_tests'; }
function ssbc_is_pro() { return false; }
register_activation_hook(__FILE__, 'ssbc_activate_plugin');
function ssbc_activate_plugin() { ssbc_ensure_tables(); ssbc_set_default_options(); ssbc_create_cache_dirs(); ssbc_write_htaccess_rules(); }
register_deactivation_hook(__FILE__, 'ssbc_deactivate_plugin');
function ssbc_deactivate_plugin() { ssbc_remove_htaccess_rules(); ssbc_clear_all_cache(); }
add_action('plugins_loaded', 'ssbc_plugins_loaded');
function ssbc_plugins_loaded() { ssbc_ensure_tables(); ssbc_create_cache_dirs(); }

function ssbc_create_cache_dirs() {
    $dirs = array(SSBC_CACHE_DIR, SSBC_CACHE_DIR.'page/', SSBC_CACHE_DIR.'css/', SSBC_CACHE_DIR.'js/', SSBC_CACHE_DIR.'critical/');
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
            global $wp_filesystem;
            if (empty($wp_filesystem)) { require_once ABSPATH.'/wp-admin/includes/file.php'; WP_Filesystem(); }
            if ($wp_filesystem) $wp_filesystem->put_contents($dir.'index.php', '<?php // Silence is golden', FS_CHMOD_FILE);
        }
    }
}

function ssbc_set_default_options() { if (!get_option('ssbc_settings')) update_option('ssbc_settings', ssbc_get_default_settings(), false); }
function ssbc_get_default_settings() { return array('minify_html'=>0,'minify_html_aggressive'=>0,'minify_css'=>0,'minify_css_aggressive'=>0,'minify_css_files'=>0,'minify_css_files_aggressive'=>0,'combine_css'=>0,'async_css'=>0,'critical_css'=>0,'critical_css_code'=>'','remove_query_strings'=>0,'minify_inline_js'=>0,'minify_inline_js_aggressive'=>0,'minify_js_files'=>0,'minify_js_files_aggressive'=>0,'defer_js'=>0,'delay_js'=>0,'delay_js_timeout'=>0,'delay_js_exclusions'=>'','combine_js'=>0,'exclude_js'=>'','remove_jquery_migrate'=>0,'lazy_load_images'=>0,'lazy_load_iframes'=>0,'lazy_load_videos'=>0,'page_cache'=>0,'force_no_cache'=>0,'page_cache_lifetime'=>3600,'gzip_compression'=>0,'browser_caching'=>0,'disable_emojis'=>0,'disable_embeds'=>0,'disable_xmlrpc'=>0,'remove_shortlink'=>0,'remove_rsd_link'=>0,'remove_wlwmanifest'=>0,'remove_feed_links'=>0,'remove_rest_api_link'=>0,'remove_wp_version'=>0,'heartbeat_behavior'=>'default','heartbeat_frequency'=>60,'preload_fonts'=>'','dns_prefetch'=>'','preconnect'=>''); }
function ssbc_get_settings() { global $ssbc_settings_cache; if (null !== $ssbc_settings_cache) return $ssbc_settings_cache; $opt = get_option('ssbc_settings'); $ssbc_settings_cache = wp_parse_args(is_array($opt) ? $opt : array(), ssbc_get_default_settings()); return $ssbc_settings_cache; }
function ssbc_reset_settings_cache() { global $ssbc_settings_cache; $ssbc_settings_cache = null; }

function ssbc_get_free_features() {
    return array('page_cache','browser_caching','gzip_compression','minify_html','minify_css','minify_css_files','minify_inline_js','minify_js_files','defer_js','delay_js','remove_jquery_migrate','lazy_load_images','lazy_load_iframes','lazy_load_videos','remove_query_strings','disable_emojis','disable_embeds','disable_xmlrpc','remove_shortlink','remove_rsd_link','remove_wlwmanifest','remove_feed_links','remove_rest_api_link','remove_wp_version');
}

function ssbc_get_pro_features() {
    return array('minify_html_aggressive','minify_css_aggressive','minify_css_files_aggressive','minify_inline_js_aggressive','minify_js_files_aggressive','combine_css','combine_js','async_css','critical_css','force_no_cache','heartbeat_disable','preconnect','dns_prefetch');
}

function ssbc_ensure_tables() {
    global $wpdb;
    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta("CREATE TABLE ".ssbc_speed_tests_table_name()." (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, ts DATETIME NOT NULL, url VARCHAR(500) NOT NULL, label VARCHAR(100) NULL, load_time FLOAT NOT NULL, ttfb FLOAT NOT NULL, dom_ready FLOAT NOT NULL DEFAULT 0, first_paint FLOAT NOT NULL DEFAULT 0, page_size BIGINT UNSIGNED NOT NULL, requests INT UNSIGNED NOT NULL, html_size BIGINT UNSIGNED NOT NULL DEFAULT 0, css_size BIGINT UNSIGNED NOT NULL DEFAULT 0, js_size BIGINT UNSIGNED NOT NULL DEFAULT 0, img_size BIGINT UNSIGNED NOT NULL DEFAULT 0, other_size BIGINT UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (id), KEY ts (ts), KEY url (url(191))) ".$wpdb->get_charset_collate().";");
    update_option('ssbc_db_version', SSBC_VERSION, false);
}

function ssbc_htaccess_is_writable() { $f = ABSPATH.'.htaccess'; if (!file_exists($f)) return false; global $wp_filesystem; if (empty($wp_filesystem)) { require_once ABSPATH.'/wp-admin/includes/file.php'; WP_Filesystem(); } return $wp_filesystem ? $wp_filesystem->is_writable($f) : false; }

function ssbc_write_htaccess_rules() {
    $opts = get_option('ssbc_settings'); if (!is_array($opts)) $opts = array();
    if (empty($opts['browser_caching']) && empty($opts['gzip_compression'])) { ssbc_remove_htaccess_rules(); return true; }
    if (!ssbc_htaccess_is_writable()) return false;
    $rules = "\n# BEGIN Quickfire Cache Speed Booster\n";
    if (!empty($opts['gzip_compression'])) $rules .= "<IfModule mod_deflate.c>\nAddOutputFilterByType DEFLATE text/plain text/html text/xml text/css text/javascript application/javascript application/xml application/xhtml+xml application/rss+xml application/json image/svg+xml\n</IfModule>\n";
    if (!empty($opts['browser_caching'])) $rules .= "<IfModule mod_expires.c>\nExpiresActive On\nExpiresDefault \"access plus 1 month\"\nExpiresByType text/html \"access plus 0 seconds\"\nExpiresByType text/css \"access plus 1 year\"\nExpiresByType application/javascript \"access plus 1 year\"\nExpiresByType image/jpeg \"access plus 1 year\"\nExpiresByType image/png \"access plus 1 year\"\nExpiresByType image/gif \"access plus 1 year\"\nExpiresByType image/webp \"access plus 1 year\"\nExpiresByType image/svg+xml \"access plus 1 year\"\nExpiresByType font/woff \"access plus 1 year\"\nExpiresByType font/woff2 \"access plus 1 year\"\n</IfModule>\n";
    $rules .= "# END Quickfire Cache Speed Booster\n";
    global $wp_filesystem; if (empty($wp_filesystem)) { require_once ABSPATH.'/wp-admin/includes/file.php'; WP_Filesystem(); } if (!$wp_filesystem) return false;
    $content = $wp_filesystem->get_contents(ABSPATH.'.htaccess'); if (false === $content) return false;
    $content = preg_replace('/\n# BEGIN Quickfire Cache Speed Booster.*# END Quickfire Cache Speed Booster\n/s', '', $content);
    return $wp_filesystem->put_contents(ABSPATH.'.htaccess', $content.$rules, FS_CHMOD_FILE);
}

function ssbc_remove_htaccess_rules() { if (!ssbc_htaccess_is_writable()) return false; global $wp_filesystem; if (empty($wp_filesystem)) { require_once ABSPATH.'/wp-admin/includes/file.php'; WP_Filesystem(); } if (!$wp_filesystem) return false; $content = $wp_filesystem->get_contents(ABSPATH.'.htaccess'); if (false === $content) return false; $new = preg_replace('/\n# BEGIN Quickfire Cache Speed Booster.*# END Quickfire Cache Speed Booster\n/s', '', $content); return ($new === $content) ? true : $wp_filesystem->put_contents(ABSPATH.'.htaccess', $new, FS_CHMOD_FILE); }

function ssbc_should_cache_page() { if (is_admin() || is_user_logged_in() || (defined('DOING_AJAX') && DOING_AJAX) || (defined('DOING_CRON') && DOING_CRON) || (defined('WP_CLI') && WP_CLI)) return false; if (!isset($_SERVER['REQUEST_METHOD']) || 'GET' !== $_SERVER['REQUEST_METHOD']) return false; if (!empty(isset($_SERVER['QUERY_STRING']) ? sanitize_text_field(wp_unslash($_SERVER['QUERY_STRING'])) : '')) return false; if (is_search() || is_404()) return false; if ((function_exists('is_cart') && is_cart()) || (function_exists('is_checkout') && is_checkout())) return false; return true; }

function ssbc_get_cache_file_path() { $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : ''; $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : ''; return SSBC_CACHE_DIR.'page/'.md5($host.$uri).'.html'; }

add_action('template_redirect', 'ssbc_page_cache_start', -99999);
function ssbc_page_cache_start() {
    $opts = get_option('ssbc_settings');
    if (!is_array($opts) || empty($opts['page_cache']) || !ssbc_should_cache_page()) return;
    $cache_file = ssbc_get_cache_file_path();
    $lifetime = !empty($opts['page_cache_lifetime']) ? (int)$opts['page_cache_lifetime'] : 3600;
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $lifetime) {
        global $wp_filesystem;
        if (empty($wp_filesystem)) { require_once ABSPATH.'/wp-admin/includes/file.php'; WP_Filesystem(); }
        if ($wp_filesystem) {
            $cached_content = $wp_filesystem->get_contents($cache_file);
            header('X-SSBC-Cache: HIT');
            echo $cached_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }
    }
    ob_start('ssbc_cache_page_output');
    add_action('shutdown', 'ob_end_flush', 0);
}

function ssbc_cache_page_output($html) { if (strlen($html) < 100 || false === strpos($html, '</html>')) return $html; $html .= "\n<!-- Cached by Quickfire Cache and Speed Booster -->"; global $wp_filesystem; if (empty($wp_filesystem)) { require_once ABSPATH.'/wp-admin/includes/file.php'; WP_Filesystem(); } if ($wp_filesystem) $wp_filesystem->put_contents(ssbc_get_cache_file_path(), $html, FS_CHMOD_FILE); header('X-SSBC-Cache: MISS'); return $html; }

function ssbc_clear_page_cache() { $dir = SSBC_CACHE_DIR.'page/'; if (is_dir($dir)) { $files = glob($dir.'*.html'); if (is_array($files)) foreach ($files as $f) wp_delete_file($f); } }

function ssbc_clear_all_cache() {
    foreach (array('page/','css/','js/','critical/') as $d) { $path = SSBC_CACHE_DIR.$d; if (is_dir($path)) { $files = glob($path.'*'); if (is_array($files)) foreach ($files as $f) if (is_file($f) && 'index.php' !== basename($f)) wp_delete_file($f); } }
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ssbc_%'"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    ssbc_reset_settings_cache();
}

add_action('save_post', 'ssbc_clear_page_cache'); add_action('comment_post', 'ssbc_clear_page_cache'); add_action('switch_theme', 'ssbc_clear_all_cache');

function ssbc_url_to_filepath($url) { $url = preg_replace('/\?.*$/', '', $url); $site = trailingslashit(site_url()); $home = trailingslashit(home_url()); $abs = trailingslashit(ABSPATH); if (0 === strpos($url, $site)) $file = $abs.substr($url, strlen($site)); elseif (0 === strpos($url, $home)) $file = $abs.substr($url, strlen($home)); else return false; $real = realpath($file); return (false === $real || 0 !== strpos($real, realpath(ABSPATH))) ? false : $real; }
function ssbc_is_local_url($url) { if (0 === strpos($url, '//')) $url = 'https:'.$url; return (0 === strpos($url, site_url()) || 0 === strpos($url, home_url())); }
function ssbc_minify_css_content($css) { $css = preg_replace('/\/\*.*?\*\//s', '', $css); $css = preg_replace('/\s+/', ' ', $css); $css = preg_replace('/\s*([{};:,>~+])\s*/', '$1', $css); return trim(str_replace(';}', '}', $css)); }
function ssbc_minify_js_content($js) { $p = array(); $c = 0; $js = preg_replace_callback('/(["\'])(?:(?!\1|\\\\).|\\\\.)*\1/s', function($m) use (&$p, &$c) { $k = '___SSBCSTR'.$c++.'___'; $p[$k] = $m[0]; return $k; }, $js); $js = preg_replace('#^\s*//.*$#m', '', $js); $js = preg_replace('#/\*.*?\*/#s', '', $js); $js = preg_replace('/[ \t]+/', ' ', $js); $js = preg_replace('/\s*\n\s*/', "\n", $js); foreach ($p as $k => $v) $js = str_replace($k, $v, $js); return trim($js); }

function ssbc_get_minified_css_url($src, $handle) { $opts = get_option('ssbc_settings'); if (!is_array($opts) || empty($opts['minify_css_files']) || !ssbc_is_local_url($src)) return $src; $file = ssbc_url_to_filepath($src); if (false === $file || !file_exists($file)) return $src; $hash = md5($handle.filemtime($file)); $cache_file = SSBC_CACHE_DIR.'css/'.$hash.'.css'; $cache_url = SSBC_CACHE_URL.'css/'.$hash.'.css'; if (!file_exists($cache_file)) { global $wp_filesystem; if (empty($wp_filesystem)) { require_once ABSPATH.'/wp-admin/includes/file.php'; WP_Filesystem(); } if (!$wp_filesystem) return $src; $css = $wp_filesystem->get_contents($file); if (false === $css) return $src; $wp_filesystem->put_contents($cache_file, ssbc_minify_css_content($css), FS_CHMOD_FILE); } return $cache_url; }

function ssbc_get_minified_js_url($src, $handle) { $opts = get_option('ssbc_settings'); if (!is_array($opts) || empty($opts['minify_js_files']) || in_array($handle, array('jquery','jquery-core'), true) || !ssbc_is_local_url($src)) return $src; $file = ssbc_url_to_filepath($src); if (false === $file || !file_exists($file)) return $src; $hash = md5($handle.filemtime($file)); $cache_file = SSBC_CACHE_DIR.'js/'.$hash.'.js'; $cache_url = SSBC_CACHE_URL.'js/'.$hash.'.js'; if (!file_exists($cache_file)) { global $wp_filesystem; if (empty($wp_filesystem)) { require_once ABSPATH.'/wp-admin/includes/file.php'; WP_Filesystem(); } if (!$wp_filesystem) return $src; $js = $wp_filesystem->get_contents($file); if (false === $js) return $src; $wp_filesystem->put_contents($cache_file, ssbc_minify_js_content($js), FS_CHMOD_FILE); } return $cache_url; }

add_filter('style_loader_src', 'ssbc_filter_style_src', 20, 2);
function ssbc_filter_style_src($src, $handle) { if (is_admin()) return $src; $opts = get_option('ssbc_settings'); return !empty($opts['minify_css_files']) ? ssbc_get_minified_css_url($src, $handle) : $src; }

add_filter('script_loader_src', 'ssbc_filter_script_src', 20, 2);
function ssbc_filter_script_src($src, $handle) { if (is_admin()) return $src; $opts = get_option('ssbc_settings'); return !empty($opts['minify_js_files']) ? ssbc_get_minified_js_url($src, $handle) : $src; }

add_action('template_redirect', 'ssbc_minify_output_start', -9998);
function ssbc_minify_output_start() {
    $opts = get_option('ssbc_settings');
    if (!is_array($opts) || (empty($opts['minify_html']) && empty($opts['minify_css']) && empty($opts['minify_inline_js'])) || is_admin() || wp_doing_ajax() || is_feed()) return;
    ob_start('ssbc_minify_output');
    add_action('shutdown', 'ob_end_flush', 0);
}

function ssbc_minify_output($html) { if (empty($html) || strlen($html) < 100) return $html; $opts = get_option('ssbc_settings'); if (!is_array($opts)) return $html; $p = array(); $c = 0; $html = preg_replace_callback('/<(pre|textarea|code)[^>]*>.*?<\/\1>/is', function($m) use (&$p, &$c) { $k = '<!--SSBCP'.$c++.'-->'; $p[$k] = $m[0]; return $k; }, $html); $html = preg_replace_callback('/<script([^>]*)>(.*?)<\/script>/is', function($m) use (&$p, &$c, $opts) { $k = '<!--SSBCP'.$c++.'-->'; $a = $m[1]; $t = $m[2]; if (!empty($opts['minify_inline_js']) && !preg_match('/\ssrc\s*=/i', $a) && !preg_match('/type\s*=\s*["\']application\/json["\']/i', $a) && trim($t)) $t = ssbc_minify_js_content($t); $p[$k] = '<script'.$a.'>'.$t.'</script>'; return $k; }, $html); if (!empty($opts['minify_css'])) $html = preg_replace_callback('/<style([^>]*)>(.*?)<\/style>/is', function($m) { return '<style'.$m[1].'>'.ssbc_minify_css_content($m[2]).'</style>'; }, $html); if (!empty($opts['minify_html'])) { $html = preg_replace('/<!--(?!SSBCP|\[if).*?-->/s', '', $html); $html = preg_replace('/>\s+</', '> <', $html); $html = preg_replace('/\s{2,}/', ' ', $html); } foreach ($p as $k => $v) $html = str_replace($k, $v, $html); return trim($html); }

add_filter('script_loader_tag', 'ssbc_filter_script_tag', 99, 3);
function ssbc_filter_script_tag($tag, $handle, $src) { if (is_admin()) return $tag; $opts = get_option('ssbc_settings'); if (!is_array($opts)) return $tag; if (!empty($opts['delay_js'])) { foreach (array('jquery','jquery-core','jquery-migrate') as $s) if (false !== stripos($handle, $s) || false !== stripos($src, $s)) return $tag; $ex = array_filter(array_map('trim', explode("\n", isset($opts['delay_js_exclusions']) ? $opts['delay_js_exclusions'] : ''))); foreach ($ex as $e) if ($e && (false !== stripos($tag, $e) || false !== stripos($handle, $e))) return $tag; if (preg_match('/<script[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $tag, $m)) return '<template class="ssbc-delay-script" data-ssbc-src="'.esc_attr($m[1]).'"></template>'; } if (!empty($opts['defer_js']) && false === stripos($tag, 'defer') && false === stripos($tag, 'async') && !in_array($handle, array('jquery','jquery-core'), true)) return str_replace(' src=', ' defer src=', $tag); return $tag; }

add_action('wp_footer', 'ssbc_delay_js_loader', 99999);
function ssbc_delay_js_loader() { $opts = get_option('ssbc_settings'); if (!is_array($opts) || is_admin() || empty($opts['delay_js'])) return; $t = isset($opts['delay_js_timeout']) ? (int)$opts['delay_js_timeout'] : 0; $s = 'var ssbcLoaded=false,ssbcTimeout='.esc_js($t).';function ssbcLoad(){if(ssbcLoaded)return;ssbcLoaded=true;document.querySelectorAll(".ssbc-delay-script").forEach(function(el){var src=el.getAttribute("data-ssbc-src");var s=document.createElement("script");if(src){s.src=src;document.body.appendChild(s);}el.remove();});}'; $s .= $t > 0 ? 'setTimeout(ssbcLoad,ssbcTimeout);' : '["mousemove","touchstart","scroll","keydown","click"].forEach(function(e){window.addEventListener(e,ssbcLoad,{once:true,passive:true});});setTimeout(ssbcLoad,5000);'; wp_register_script('ssbc-delay-loader', false, array(), SSBC_VERSION, true); wp_enqueue_script('ssbc-delay-loader'); wp_add_inline_script('ssbc-delay-loader', $s); }

add_action('wp_default_scripts', 'ssbc_remove_jquery_migrate');
function ssbc_remove_jquery_migrate($scripts) { $opts = get_option('ssbc_settings'); if (!is_array($opts) || empty($opts['remove_jquery_migrate']) || is_admin()) return; if (!empty($scripts->registered['jquery'])) $scripts->registered['jquery']->deps = array_diff($scripts->registered['jquery']->deps, array('jquery-migrate')); }

add_filter('the_content', 'ssbc_lazy_load_content', 99); add_filter('post_thumbnail_html', 'ssbc_lazy_load_content', 99);
function ssbc_lazy_load_content($content) { $opts = get_option('ssbc_settings'); if (!is_array($opts) || is_admin() || is_feed()) return $content; if (!empty($opts['lazy_load_images'])) $content = preg_replace_callback('/<img([^>]+)>/i', function($m) { return false !== stripos($m[0], 'loading=') ? $m[0] : str_replace('<img', '<img loading="lazy"', $m[0]); }, $content); if (!empty($opts['lazy_load_iframes'])) $content = preg_replace_callback('/<iframe([^>]+)>/i', function($m) { return false !== stripos($m[0], 'loading=') ? $m[0] : str_replace('<iframe', '<iframe loading="lazy"', $m[0]); }, $content); if (!empty($opts['lazy_load_videos'])) $content = preg_replace_callback('/<video([^>]*)>/i', function($m) { return false !== stripos($m[0], 'preload=') ? preg_replace('/preload=["\'][^"\']*["\']/', 'preload="none"', $m[0]) : str_replace('<video', '<video preload="none"', $m[0]); }, $content); return $content; }

add_filter('script_loader_src', 'ssbc_remove_query_strings', 15); add_filter('style_loader_src', 'ssbc_remove_query_strings', 15);
function ssbc_remove_query_strings($src) { $opts = get_option('ssbc_settings'); return (!is_array($opts) || empty($opts['remove_query_strings']) || is_admin()) ? $src : remove_query_arg('ver', $src); }

add_action('init', 'ssbc_init_cleanup');
function ssbc_init_cleanup() { $opts = get_option('ssbc_settings'); if (!is_array($opts)) return; if (!empty($opts['disable_emojis'])) { remove_action('wp_head', 'print_emoji_detection_script', 7); remove_action('admin_print_scripts', 'print_emoji_detection_script'); remove_action('wp_print_styles', 'print_emoji_styles'); remove_action('admin_print_styles', 'print_emoji_styles'); remove_filter('the_content_feed', 'wp_staticize_emoji'); remove_filter('comment_text_rss', 'wp_staticize_emoji'); remove_filter('wp_mail', 'wp_staticize_emoji_for_email'); add_filter('tiny_mce_plugins', 'ssbc_disable_emojis_tinymce'); } if (!empty($opts['disable_embeds'])) { remove_action('rest_api_init', 'wp_oembed_register_route'); remove_action('wp_head', 'wp_oembed_add_discovery_links'); remove_action('wp_head', 'wp_oembed_add_host_js'); add_filter('embed_oembed_discover', '__return_false'); } if (!empty($opts['disable_xmlrpc'])) add_filter('xmlrpc_enabled', '__return_false'); if (!empty($opts['remove_shortlink'])) remove_action('wp_head', 'wp_shortlink_wp_head', 10); if (!empty($opts['remove_rsd_link'])) remove_action('wp_head', 'rsd_link'); if (!empty($opts['remove_wlwmanifest'])) remove_action('wp_head', 'wlwmanifest_link'); if (!empty($opts['remove_feed_links'])) { remove_action('wp_head', 'feed_links', 2); remove_action('wp_head', 'feed_links_extra', 3); } if (!empty($opts['remove_rest_api_link'])) remove_action('wp_head', 'rest_output_link_wp_head', 10); if (!empty($opts['remove_wp_version'])) { remove_action('wp_head', 'wp_generator'); add_filter('the_generator', '__return_empty_string'); } }
function ssbc_disable_emojis_tinymce($plugins) { return is_array($plugins) ? array_diff($plugins, array('wpemoji')) : array(); }

function ssbc_save_speed_test($data) {
    global $wpdb;
    ssbc_ensure_tables();
    $result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        ssbc_speed_tests_table_name(),
        array('ts'=>current_time('mysql',true),'url'=>sanitize_text_field($data['url']),'label'=>'','load_time'=>floatval($data['load_time']),'ttfb'=>floatval(isset($data['ttfb'])?$data['ttfb']:0),'dom_ready'=>floatval(isset($data['dom_ready'])?$data['dom_ready']:0),'first_paint'=>floatval(isset($data['first_paint'])?$data['first_paint']:0),'page_size'=>intval(isset($data['page_size'])?$data['page_size']:0),'requests'=>intval(isset($data['requests'])?$data['requests']:0),'html_size'=>0,'css_size'=>0,'js_size'=>0,'img_size'=>0,'other_size'=>0),
        array('%s','%s','%s','%f','%f','%f','%f','%d','%d','%d','%d','%d','%d','%d')
    );
    wp_cache_delete('ssbc_speed_tests_20', 'ssbc');
    return false !== $result;
}

function ssbc_get_speed_tests($limit = 20) {
    global $wpdb;
    $cache_key = 'ssbc_speed_tests_'.$limit;
    $cached = wp_cache_get($cache_key, 'ssbc');
    if (false !== $cached) return $cached;
    $results = $wpdb->get_results($wpdb->prepare('SELECT * FROM `'.esc_sql(ssbc_speed_tests_table_name()).'` ORDER BY ts DESC LIMIT %d', $limit), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    wp_cache_set($cache_key, $results, 'ssbc', 300);
    return $results;
}

function ssbc_format_bytes($bytes, $precision = 2) { $units = array('B','KB','MB','GB'); $bytes = max($bytes, 0); $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); $pow = min($pow, count($units) - 1); return round($bytes / pow(1024, $pow), $precision).' '.$units[$pow]; }
function ssbc_get_load_rating($time) { if ($time < 1.5) return array('label'=>'Excellent','color'=>'#00b894'); if ($time < 2.5) return array('label'=>'Great','color'=>'#00cec9'); if ($time < 3.5) return array('label'=>'Good','color'=>'#fdcb6e'); if ($time < 5) return array('label'=>'Moderate','color'=>'#e17055'); return array('label'=>'Slow','color'=>'#d63031'); }
function ssbc_get_ttfb_rating($time) { if ($time < 0.2) return array('color'=>'#00b894'); if ($time < 0.5) return array('color'=>'#00cec9'); if ($time < 1) return array('color'=>'#fdcb6e'); return array('color'=>'#d63031'); }
function ssbc_get_size_rating($size) { $kb = $size / 1024; if ($kb < 500) return array('color'=>'#00b894'); if ($kb < 1000) return array('color'=>'#00cec9'); if ($kb < 2000) return array('color'=>'#fdcb6e'); return array('color'=>'#d63031'); }

add_action('wp_ajax_ssbc_save_speed_test', 'ssbc_ajax_save_speed_test');
function ssbc_ajax_save_speed_test() { check_ajax_referer('ssbc_speed_test', 'nonce'); if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized'); wp_cache_delete('ssbc_speed_tests_20', 'ssbc'); wp_send_json_success(array('saved'=>ssbc_save_speed_test(array('url'=>isset($_POST['url'])?sanitize_text_field(wp_unslash($_POST['url'])):'','load_time'=>isset($_POST['load_time'])?floatval($_POST['load_time']):0,'ttfb'=>isset($_POST['ttfb'])?floatval($_POST['ttfb']):0,'dom_ready'=>isset($_POST['dom_ready'])?floatval($_POST['dom_ready']):0,'page_size'=>isset($_POST['page_size'])?intval($_POST['page_size']):0,'requests'=>isset($_POST['requests'])?intval($_POST['requests']):0)))); }

add_action('wp_ajax_ssbc_clear_cache', 'ssbc_ajax_clear_cache');
function ssbc_ajax_clear_cache() { check_ajax_referer('ssbc_clear_cache', 'nonce'); if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized'); ssbc_clear_all_cache(); wp_send_json_success(array('cleared'=>true)); }

add_action('admin_init', 'ssbc_admin_init_handler');
function ssbc_admin_init_handler() {
    if (!current_user_can('manage_options')) return;
    $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
    if ('quickfire-cache-speed-booster' !== $page) return;
    $base_url = admin_url('admin.php?page=quickfire-cache-speed-booster');
    $tab = 'dashboard';
    if (isset($_GET['tab'], $_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'ssbc_tab_nonce')) $tab = sanitize_key(wp_unslash($_GET['tab']));
    
    if (isset($_GET['ssbc_bulk_enable']) && check_admin_referer('ssbc_bulk_enable')) {
        $opts = get_option('ssbc_settings');
        if (!is_array($opts)) $opts = ssbc_get_default_settings();
        foreach (ssbc_get_free_features() as $key) { $opts[$key] = 1; }
        update_option('ssbc_settings', $opts, false);
        ssbc_reset_settings_cache();
        ssbc_write_htaccess_rules();
        ssbc_clear_all_cache();
        wp_safe_redirect(add_query_arg(array('tab'=>$tab,'bulk_enabled'=>'1','_wpnonce'=>wp_create_nonce('ssbc_tab_nonce')), $base_url));
        exit;
    }
    
    if (isset($_GET['ssbc_bulk_disable']) && check_admin_referer('ssbc_bulk_disable')) {
        $opts = get_option('ssbc_settings');
        if (!is_array($opts)) $opts = ssbc_get_default_settings();
        foreach (ssbc_get_free_features() as $key) { $opts[$key] = 0; }
        update_option('ssbc_settings', $opts, false);
        ssbc_reset_settings_cache();
        ssbc_write_htaccess_rules();
        ssbc_clear_all_cache();
        wp_safe_redirect(add_query_arg(array('tab'=>$tab,'bulk_disabled'=>'1','_wpnonce'=>wp_create_nonce('ssbc_tab_nonce')), $base_url));
        exit;
    }
    
    if (isset($_POST['ssbc_save_settings']) && check_admin_referer('ssbc_save')) { ssbc_save_settings(); ssbc_write_htaccess_rules(); wp_safe_redirect(add_query_arg(array('tab'=>$tab,'updated'=>'1','_wpnonce'=>wp_create_nonce('ssbc_tab_nonce')), $base_url)); exit; }
    if (isset($_GET['ssbc_clear_tests']) && check_admin_referer('ssbc_clear_tests')) {
        global $wpdb;
        $wpdb->query('DELETE FROM `'.esc_sql(ssbc_speed_tests_table_name()).'`'); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        wp_cache_delete('ssbc_speed_tests_20', 'ssbc');
        wp_safe_redirect(add_query_arg(array('tab'=>'speed-test','_wpnonce'=>wp_create_nonce('ssbc_tab_nonce')), $base_url));
        exit;
    }
    if (isset($_GET['ssbc_clear_cache']) && check_admin_referer('ssbc_clear_cache_link')) { ssbc_clear_all_cache(); wp_safe_redirect(add_query_arg(array('tab'=>$tab,'cache_cleared'=>'1','_wpnonce'=>wp_create_nonce('ssbc_tab_nonce')), $base_url)); exit; }
}

function ssbc_save_settings() {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'ssbc_save')) return;
    $opts = get_option('ssbc_settings');
    if (!is_array($opts)) $opts = ssbc_get_default_settings();
    $form_tab = isset($_POST['ssbc_form_tab']) ? sanitize_key(wp_unslash($_POST['ssbc_form_tab'])) : '';
    $caching_fields = array('page_cache', 'gzip_compression', 'browser_caching');
    $optimization_fields = array('minify_html', 'minify_css', 'minify_css_files', 'minify_inline_js', 'minify_js_files', 'defer_js', 'delay_js', 'remove_jquery_migrate', 'lazy_load_images', 'lazy_load_iframes', 'lazy_load_videos', 'remove_query_strings', 'disable_emojis', 'disable_embeds', 'disable_xmlrpc', 'remove_shortlink', 'remove_rsd_link', 'remove_wlwmanifest', 'remove_feed_links', 'remove_rest_api_link', 'remove_wp_version');
    if ('caching' === $form_tab) {
        foreach ($caching_fields as $key) { $opts[$key] = !empty($_POST[$key]) ? 1 : 0; }
        if (isset($_POST['page_cache_lifetime'])) $opts['page_cache_lifetime'] = max(60, intval($_POST['page_cache_lifetime']));
    } elseif ('optimization' === $form_tab) {
        foreach ($optimization_fields as $key) { $opts[$key] = !empty($_POST[$key]) ? 1 : 0; }
        if (isset($_POST['delay_js_timeout'])) $opts['delay_js_timeout'] = max(0, intval($_POST['delay_js_timeout']));
        if (isset($_POST['delay_js_exclusions'])) $opts['delay_js_exclusions'] = sanitize_textarea_field(wp_unslash($_POST['delay_js_exclusions']));
    }
    ssbc_clear_all_cache();
    update_option('ssbc_settings', $opts, false);
    ssbc_reset_settings_cache();
}

add_action('admin_menu', 'ssbc_admin_menu');
function ssbc_admin_menu() { add_menu_page(esc_html__('Quickfire Cache and Speed Booster','quickfire-cache-speed-booster'), esc_html__('Quickfire Cache','quickfire-cache-speed-booster'), 'manage_options', 'quickfire-cache-speed-booster', 'ssbc_render_page', 'dashicons-performance', 59); }

add_action('admin_bar_menu', 'ssbc_admin_bar_menu', 100);
function ssbc_admin_bar_menu($admin_bar) { if (!current_user_can('manage_options')) return; $opts = get_option('ssbc_settings'); if (!is_array($opts) || empty($opts['page_cache'])) return; $admin_bar->add_node(array('id'=>'ssbc-clear-cache','title'=>'<span class="ab-icon dashicons dashicons-trash"></span> Clear Cache','href'=>'#','meta'=>array('class'=>'ssbc-clear-cache-trigger'))); }

add_action('admin_enqueue_scripts', 'ssbc_admin_enqueue_scripts');
function ssbc_admin_enqueue_scripts($hook) {
    if (!current_user_can('manage_options')) return;
    wp_register_script('ssbc-admin-bar-script', false, array(), SSBC_VERSION, true);
    wp_enqueue_script('ssbc-admin-bar-script');
    wp_add_inline_script('ssbc-admin-bar-script', '(function(){document.addEventListener("DOMContentLoaded",function(){var t=document.querySelector(".ssbc-clear-cache-trigger a");if(!t)return;t.addEventListener("click",function(e){e.preventDefault();if(!confirm("Clear all cache?"))return;fetch(ajaxurl,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"action=ssbc_clear_cache&nonce='.wp_create_nonce('ssbc_clear_cache').'"}).then(function(r){return r.json();}).then(function(d){alert(d.success?"Cache cleared!":"Error");});});});})();');
    if ('toplevel_page_quickfire-cache-speed-booster' !== $hook) return;
    wp_register_style('ssbc-admin-style', false, array(), SSBC_VERSION);
    wp_enqueue_style('ssbc-admin-style');
    wp_add_inline_style('ssbc-admin-style', ssbc_get_admin_css());
    wp_register_script('ssbc-admin-page-script', false, array(), SSBC_VERSION, true);
    wp_enqueue_script('ssbc-admin-page-script');
    wp_add_inline_script('ssbc-admin-page-script', '(function(){document.addEventListener("DOMContentLoaded",function(){var c=document.querySelector(".ssbc-clear-history-btn");if(c)c.addEventListener("click",function(e){if(!confirm("Clear history?"))e.preventDefault();});var b=document.getElementById("ssbc-run-test");if(!b)return;var a=document.getElementById("ssbc-test-area"),h=document.getElementById("ssbc-history-body"),n="'.wp_create_nonce('ssbc_speed_test').'",aj="'.admin_url('admin-ajax.php').'",tu="'.home_url('/').'";function gr(t){if(t<1.5)return{label:"Excellent",color:"#00b894"};if(t<2.5)return{label:"Great",color:"#00cec9"};if(t<3.5)return{label:"Good",color:"#fdcb6e"};if(t<5)return{label:"Moderate",color:"#e17055"};return{label:"Slow",color:"#d63031"};}function fb(b){if(b<1024)return b+" B";if(b<1048576)return(b/1024).toFixed(1)+" KB";return(b/1048576).toFixed(1)+" MB";}function ar(d){var x=document.getElementById("ssbc-no-tests");if(x)x.remove();var r=gr(d.load_time);var row=document.createElement("tr");row.innerHTML="<td>Just now</td><td style=text-align:right>"+d.ttfb.toFixed(2)+"s</td><td style=text-align:right><span class=ssbc-cell-metric style=background:"+r.color+"22;color:"+r.color+">"+d.load_time.toFixed(2)+"s</span></td><td style=text-align:right>"+fb(d.page_size)+"</td><td style=text-align:center><span class=ssbc-badge style=background:"+r.color+"22;color:"+r.color+">"+r.label+"</span></td>";h.insertBefore(row,h.firstChild);}b.addEventListener("click",function(){b.disabled=true;a.style.display="block";var i=document.createElement("iframe");i.style.cssText="position:absolute;left:-9999px;width:1px;height:1px;";document.body.appendChild(i);var st=performance.now();var to=setTimeout(function(){cl();alert("Timeout");},30000);function cl(){clearTimeout(to);i.remove();b.disabled=false;a.style.display="none";}i.onload=function(){clearTimeout(to);var lt=(performance.now()-st)/1000;var d={url:tu,load_time:lt,ttfb:0,dom_ready:0,page_size:0,requests:1};try{var w=i.contentWindow;if(w.performance&&w.performance.timing){var t=w.performance.timing;var nv=t.navigationStart;if(nv>0){d.ttfb=Math.max(0,(t.responseStart-nv)/1000);var f=(t.loadEventEnd-nv)/1000;if(f>0)d.load_time=f;}}var dc=i.contentDocument;if(dc&&dc.documentElement)d.page_size=dc.documentElement.outerHTML.length;}catch(e){}ar(d);cl();var fd=new FormData();fd.append("action","ssbc_save_speed_test");fd.append("nonce",n);for(var k in d)fd.append(k,d[k]);fetch(aj,{method:"POST",body:fd});};i.onerror=function(){cl();alert("Error");};i.src=tu;});});})();');
}

function ssbc_get_admin_css() { return ':root{--ssbc-purple:#6c5ce7;--ssbc-blue:#0984e3;--ssbc-gradient:linear-gradient(135deg,#6c5ce7 0%,#0984e3 100%);--ssbc-success:#00b894;--ssbc-warning:#fdcb6e;--ssbc-danger:#d63031;--ssbc-dark:#2d3436;--ssbc-gray:#636e72;--ssbc-light:#dfe6e9;--ssbc-white:#fff}#ssbc-wrap{max-width:1400px;margin:20px 20px 20px 0}#ssbc-wrap h1{background:var(--ssbc-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-size:28px;font-weight:700}#ssbc-wrap .ssbc-header{background:var(--ssbc-gradient);color:#fff;padding:24px 30px;border-radius:16px;margin-bottom:24px}#ssbc-wrap .ssbc-header h2{margin:0;font-size:22px;color:#fff}#ssbc-wrap .nav-tab-wrapper{border-bottom:2px solid var(--ssbc-light);margin-bottom:24px}#ssbc-wrap .nav-tab{background:transparent;border:none;border-bottom:3px solid transparent;color:var(--ssbc-gray);padding:12px 20px;font-weight:600;margin-bottom:-2px}#ssbc-wrap .nav-tab:hover{background:rgba(108,92,231,0.05);color:var(--ssbc-purple)}#ssbc-wrap .nav-tab-active{background:rgba(108,92,231,0.1);border-bottom-color:var(--ssbc-purple);color:var(--ssbc-purple)}#ssbc-wrap .ssbc-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:24px}#ssbc-wrap .ssbc-card{background:var(--ssbc-white);border-radius:16px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,0.08)}#ssbc-wrap .ssbc-card h3{margin:0 0 20px;font-size:18px;color:var(--ssbc-dark);display:flex;align-items:center;gap:10px}#ssbc-wrap .ssbc-card h3 .dashicons{color:var(--ssbc-purple)}#ssbc-wrap .ssbc-toggle{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--ssbc-light)}#ssbc-wrap .ssbc-toggle:last-child{border-bottom:none}#ssbc-wrap .ssbc-toggle-label{display:flex;flex-direction:column;gap:2px}#ssbc-wrap .ssbc-toggle-label strong{color:var(--ssbc-dark);font-size:14px}#ssbc-wrap .ssbc-toggle-label span{color:var(--ssbc-gray);font-size:12px}#ssbc-wrap .ssbc-switch{position:relative;width:50px;height:26px}#ssbc-wrap .ssbc-switch input{opacity:0;width:0;height:0}#ssbc-wrap .ssbc-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background-color:var(--ssbc-light);border-radius:26px;transition:0.3s}#ssbc-wrap .ssbc-slider:before{position:absolute;content:"";height:20px;width:20px;left:3px;bottom:3px;background-color:#fff;border-radius:50%;transition:0.3s}#ssbc-wrap .ssbc-switch input:checked+.ssbc-slider{background:var(--ssbc-gradient)}#ssbc-wrap .ssbc-switch input:checked+.ssbc-slider:before{transform:translateX(24px)}#ssbc-wrap .ssbc-switch input:disabled+.ssbc-slider{opacity:0.5;cursor:pointer}#ssbc-wrap .ssbc-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:8px;font-size:14px;font-weight:600;border:none;cursor:pointer;text-decoration:none}#ssbc-wrap .ssbc-btn-primary{background:var(--ssbc-gradient);color:#fff}#ssbc-wrap .ssbc-btn-primary:hover{color:#fff}#ssbc-wrap .ssbc-btn-secondary{background:var(--ssbc-light);color:var(--ssbc-dark)}#ssbc-wrap .ssbc-btn-success{background:#00b894;color:#fff}#ssbc-wrap .ssbc-btn-success:hover{background:#00a884;color:#fff}#ssbc-wrap .ssbc-btn-danger{background:#d63031;color:#fff}#ssbc-wrap .ssbc-btn-danger:hover{background:#c0392b;color:#fff}#ssbc-wrap .ssbc-input{width:100%;padding:12px 16px;border:2px solid var(--ssbc-light);border-radius:8px;font-size:14px;box-sizing:border-box}#ssbc-wrap .ssbc-input:focus{outline:none;border-color:var(--ssbc-purple)}#ssbc-wrap textarea.ssbc-input{min-height:80px;resize:vertical}#ssbc-wrap .ssbc-notice{padding:16px 20px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:12px}#ssbc-wrap .ssbc-notice-success{background:rgba(0,184,148,0.1);border-left:4px solid var(--ssbc-success);color:#00a884}#ssbc-wrap .ssbc-section-title{font-size:12px;text-transform:uppercase;letter-spacing:1px;color:var(--ssbc-gray);margin:20px 0 12px;padding-bottom:8px;border-bottom:2px solid var(--ssbc-light)}#ssbc-wrap .ssbc-sub-toggle{margin-left:30px;padding:10px 0;border-bottom:1px dashed var(--ssbc-light)}#ssbc-wrap .ssbc-sub-toggle .ssbc-toggle-label strong{font-size:13px;color:var(--ssbc-gray)}#ssbc-wrap .ssbc-field-help{font-size:12px;color:var(--ssbc-gray);margin-top:6px}#ssbc-wrap .ssbc-cache-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:16px}#ssbc-wrap .ssbc-cache-stat{text-align:center;padding:16px;background:rgba(108,92,231,0.05);border-radius:10px}#ssbc-wrap .ssbc-cache-stat-value{font-size:24px;font-weight:700;color:var(--ssbc-purple)}#ssbc-wrap .ssbc-cache-stat-label{font-size:12px;color:var(--ssbc-gray);margin-top:4px}#ssbc-wrap .ssbc-pro-badge{background:linear-gradient(135deg,#f39c12 0%,#e74c3c 100%);color:#fff;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700;margin-left:8px;text-transform:uppercase}#ssbc-wrap .ssbc-score-card{text-align:center;padding:30px 20px;background:linear-gradient(135deg,rgba(108,92,231,0.03) 0%,rgba(9,132,227,0.03) 100%);border-radius:12px}#ssbc-wrap .ssbc-score-ring{width:160px;height:160px;border-radius:50%;background:var(--ssbc-gradient);display:flex;align-items:center;justify-content:center;margin:0 auto 20px}#ssbc-wrap .ssbc-score-inner{width:130px;height:130px;border-radius:50%;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center}#ssbc-wrap .ssbc-score-value{font-size:42px;font-weight:700;color:var(--ssbc-purple);line-height:1}#ssbc-wrap .ssbc-score-label{font-size:14px;color:var(--ssbc-gray)}#ssbc-wrap .ssbc-score-status{font-size:20px;font-weight:600;margin-bottom:8px}#ssbc-wrap .ssbc-score-items{text-align:left;margin-top:20px;max-height:400px;overflow-y:auto}#ssbc-wrap .ssbc-score-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--ssbc-light);font-size:13px}#ssbc-wrap .ssbc-score-item:last-child{border-bottom:none}#ssbc-wrap .ssbc-quick-stats{display:flex;gap:16px;margin-bottom:20px}#ssbc-wrap .ssbc-quick-stat{flex:1;display:flex;align-items:center;gap:12px;padding:16px;background:var(--ssbc-white);border:1px solid var(--ssbc-light);border-radius:12px}#ssbc-wrap .ssbc-quick-stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center}#ssbc-wrap .ssbc-quick-stat-value{font-size:18px;font-weight:700;color:var(--ssbc-dark)}#ssbc-wrap .ssbc-quick-stat-label{font-size:12px;color:var(--ssbc-gray)}#ssbc-wrap .ssbc-action-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}#ssbc-wrap .ssbc-action-card{display:flex;flex-direction:column;align-items:center;padding:20px 16px;background:linear-gradient(135deg,rgba(108,92,231,0.03) 0%,rgba(9,132,227,0.03) 100%);border:2px solid var(--ssbc-light);border-radius:12px;text-decoration:none;text-align:center}#ssbc-wrap .ssbc-action-card:hover{border-color:var(--ssbc-purple)}#ssbc-wrap .ssbc-action-card .dashicons{font-size:28px;color:var(--ssbc-purple);margin-bottom:8px}#ssbc-wrap .ssbc-action-card .ssbc-action-title{font-size:14px;font-weight:600;color:var(--ssbc-dark)}#ssbc-wrap .ssbc-action-card .ssbc-action-desc{font-size:11px;color:var(--ssbc-gray)}#ssbc-wrap .ssbc-action-card.ssbc-action-primary{background:var(--ssbc-gradient);border-color:transparent}#ssbc-wrap .ssbc-action-card.ssbc-action-primary .dashicons,#ssbc-wrap .ssbc-action-card.ssbc-action-primary .ssbc-action-title,#ssbc-wrap .ssbc-action-card.ssbc-action-primary .ssbc-action-desc{color:#fff}#ssbc-wrap .ssbc-action-card.ssbc-action-danger{background:linear-gradient(135deg,rgba(214,48,49,0.05) 0%,rgba(214,48,49,0.1) 100%);border-color:rgba(214,48,49,0.2)}#ssbc-wrap .ssbc-action-card.ssbc-action-danger .dashicons{color:var(--ssbc-danger)}#ssbc-wrap table.widefat{border:none;border-radius:12px;overflow:hidden}#ssbc-wrap table.widefat thead th{background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);font-weight:600;padding:14px 16px}#ssbc-wrap table.widefat td{padding:12px 16px}#ssbc-wrap .ssbc-badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}#ssbc-wrap .ssbc-cell-metric{padding:4px 8px;border-radius:4px;font-weight:600;font-size:12px}#ssbc-wrap .ssbc-pro-card{background:linear-gradient(135deg,rgba(243,156,18,0.1) 0%,rgba(231,76,60,0.1) 100%);border:2px solid rgba(243,156,18,0.3);padding:30px;text-align:center}#ssbc-wrap .ssbc-pro-features{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin:24px 0;text-align:left}#ssbc-wrap .ssbc-pro-feature{display:flex;align-items:center;gap:10px;padding:12px;background:#fff;border-radius:8px}#ssbc-wrap .ssbc-pro-feature .dashicons{color:#f39c12}#ssbc-wrap .ssbc-timer{background:linear-gradient(135deg,#e74c3c 0%,#c0392b 100%);color:#fff;padding:20px;border-radius:12px;margin:20px 0;text-align:center}#ssbc-wrap .ssbc-timer-title{font-size:14px;margin-bottom:10px;opacity:0.9}#ssbc-wrap .ssbc-timer-countdown{font-size:32px;font-weight:700;font-family:monospace}#ssbc-wrap .ssbc-coupon{display:flex;align-items:center;justify-content:center;gap:15px;background:#fff;border:2px dashed #f39c12;padding:15px 25px;border-radius:8px;margin:15px auto;max-width:400px}#ssbc-wrap .ssbc-coupon-label{font-size:14px;color:var(--ssbc-gray);font-weight:600}#ssbc-wrap .ssbc-coupon-code{font-size:20px;font-weight:700;color:#e74c3c;letter-spacing:2px}#ssbc-wrap .ssbc-coupon-copy{background:var(--ssbc-gradient);color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-weight:600;font-size:12px}#ssbc-wrap .ssbc-coupon-copy:hover{opacity:0.9}#ssbc-wrap a.ssbc-toggle{text-decoration:none;cursor:pointer}#ssbc-wrap a.ssbc-toggle:hover{background:rgba(243,156,18,0.05)}#ssbc-wrap .ssbc-bulk-actions{display:flex;gap:12px;margin-bottom:24px}@media (max-width:1100px){#ssbc-wrap .ssbc-grid{grid-template-columns:1fr}#ssbc-wrap .ssbc-cache-stats{grid-template-columns:repeat(2,1fr)}#ssbc-wrap .ssbc-quick-stats{flex-direction:column}#ssbc-wrap .ssbc-pro-features{grid-template-columns:1fr}}'; }

function ssbc_get_current_tab() { $tab = 'dashboard'; if (isset($_GET['tab'], $_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'ssbc_tab_nonce')) $tab = sanitize_key(wp_unslash($_GET['tab'])); return $tab; }
function ssbc_has_notice($key) { return isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'ssbc_tab_nonce') && isset($_GET[$key]); }

function ssbc_render_page() {
    if (!current_user_can('manage_options')) return;
    $base = admin_url('admin.php?page=quickfire-cache-speed-booster'); $tab = ssbc_get_current_tab(); $opts = get_option('ssbc_settings'); if (!is_array($opts)) $opts = ssbc_get_default_settings();
    echo '<div class="wrap" id="ssbc-wrap"><h1>'.esc_html__('Quickfire Cache and Speed Booster','quickfire-cache-speed-booster').'</h1><div class="ssbc-header"><h2>'.esc_html__('WordPress Speed Optimization','quickfire-cache-speed-booster').'</h2></div>';
    if (ssbc_has_notice('updated')) echo '<div class="ssbc-notice ssbc-notice-success"><span class="dashicons dashicons-yes-alt"></span> '.esc_html__('Settings saved!','quickfire-cache-speed-booster').'</div>';
    if (ssbc_has_notice('cache_cleared')) echo '<div class="ssbc-notice ssbc-notice-success"><span class="dashicons dashicons-yes-alt"></span> '.esc_html__('Cache cleared!','quickfire-cache-speed-booster').'</div>';
    if (ssbc_has_notice('bulk_enabled')) echo '<div class="ssbc-notice ssbc-notice-success"><span class="dashicons dashicons-yes-alt"></span> '.esc_html__('All free features enabled!','quickfire-cache-speed-booster').'</div>';
    if (ssbc_has_notice('bulk_disabled')) echo '<div class="ssbc-notice ssbc-notice-success"><span class="dashicons dashicons-yes-alt"></span> '.esc_html__('All features disabled!','quickfire-cache-speed-booster').'</div>';
    echo '<h2 class="nav-tab-wrapper">'; foreach (array('dashboard'=>array('Dashboard','dashicons-dashboard'),'optimization'=>array('Optimization','dashicons-admin-settings'),'caching'=>array('Caching','dashicons-database'),'speed-test'=>array('Speed Test','dashicons-performance'),'pro'=>array('Pro Features','dashicons-star-filled')) as $k => $v) echo '<a href="'.esc_url(add_query_arg(array('tab'=>$k,'_wpnonce'=>wp_create_nonce('ssbc_tab_nonce')), $base)).'" class="nav-tab '.($tab===$k?'nav-tab-active':'').'"><span class="dashicons '.esc_attr($v[1]).'"></span> '.esc_html($v[0]).'</a>'; echo '</h2>';
    switch ($tab) { case 'optimization': ssbc_render_optimization_tab($opts, $base); break; case 'caching': ssbc_render_caching_tab($opts, $base); break; case 'speed-test': ssbc_render_speed_test_tab($base); break; case 'pro': ssbc_render_pro_tab(); break; default: ssbc_render_dashboard_tab($opts, $base); }
    echo '</div>';
}

function ssbc_render_toggle($name, $label, $desc, $opts, $is_sub = false, $is_pro = false) {
    $class = $is_sub ? 'ssbc-toggle ssbc-sub-toggle' : 'ssbc-toggle';
    if ($is_pro) {
        echo '<a href="'.esc_url(SSBC_PRO_URL).'" target="_blank" class="'.esc_attr($class).'">';
        echo '<div class="ssbc-toggle-label"><strong>'.esc_html($label).'<span class="ssbc-pro-badge">PRO</span></strong><span>'.esc_html($desc).'</span></div>';
        echo '<label class="ssbc-switch"><input type="checkbox" disabled><span class="ssbc-slider"></span></label>';
        echo '</a>';
    } else {
        echo '<div class="'.esc_attr($class).'"><div class="ssbc-toggle-label"><strong>'.esc_html($label).'</strong><span>'.esc_html($desc).'</span></div>';
        echo '<label class="ssbc-switch"><input type="checkbox" name="'.esc_attr($name).'" value="1"'.(!empty($opts[$name])?' checked':'').'><span class="ssbc-slider"></span></label></div>';
    }
}

function ssbc_render_dashboard_tab($opts, $base) {
    echo '<div class="ssbc-bulk-actions">';
    echo '<a href="'.esc_url(wp_nonce_url(add_query_arg('ssbc_bulk_enable', '1', $base), 'ssbc_bulk_enable')).'" class="ssbc-btn ssbc-btn-success"><span class="dashicons dashicons-yes"></span> Enable All Free Features</a>';
    echo '<a href="'.esc_url(wp_nonce_url(add_query_arg('ssbc_bulk_disable', '1', $base), 'ssbc_bulk_disable')).'" class="ssbc-btn ssbc-btn-danger"><span class="dashicons dashicons-no"></span> Disable All Features</a>';
    echo '</div>';
    
    $free_checks = array('page_cache'=>array('Page Caching',20),'browser_caching'=>array('Browser Caching',15),'gzip_compression'=>array('GZIP Compression',10),'minify_html'=>array('Minify HTML',5),'minify_css'=>array('Minify Inline CSS',5),'minify_css_files'=>array('Minify CSS Files',5),'minify_inline_js'=>array('Minify Inline JS',5),'minify_js_files'=>array('Minify JS Files',5),'defer_js'=>array('Defer JavaScript',4),'delay_js'=>array('Delay JavaScript',4),'lazy_load_images'=>array('Lazy Load Images',5),'lazy_load_iframes'=>array('Lazy Load iFrames',2),'lazy_load_videos'=>array('Lazy Load Videos',2),'disable_emojis'=>array('Disable Emojis',2),'remove_wp_version'=>array('Hide WP Version',1));
    $pro_checks = array('minify_html_aggressive'=>array('Aggressive HTML',3),'minify_css_aggressive'=>array('Aggressive CSS',3),'combine_css'=>array('Combine CSS',5),'combine_js'=>array('Combine JS',5),'async_css'=>array('Async CSS',4),'critical_css'=>array('Critical CSS',5),'heartbeat_disable'=>array('Heartbeat Control',2),'preconnect'=>array('Preconnect',2),'dns_prefetch'=>array('DNS Prefetch',2));
    
    $score = 0; $max = 0; $items = array();
    foreach ($free_checks as $k => $v) { $on = !empty($opts[$k]); $max += $v[1]; if ($on) $score += $v[1]; $items[] = array('name'=>$v[0],'on'=>$on,'pts'=>$v[1],'pro'=>false); }
    foreach ($pro_checks as $k => $v) { $on = ssbc_is_pro() && !empty($opts[$k]); $max += $v[1]; if ($on) $score += $v[1]; $items[] = array('name'=>$v[0],'on'=>$on,'pts'=>$v[1],'pro'=>true); }
    
    $pct = $max > 0 ? round(($score/$max)*100) : 0;
    if ($pct >= 80) { $status='Excellent'; $color='#00b894'; } elseif ($pct >= 60) { $status='Good'; $color='#00cec9'; } elseif ($pct >= 40) { $status='Moderate'; $color='#fdcb6e'; } else { $status='Needs Work'; $color='#d63031'; }
    
    $active = 0;
    $total_features = count($free_checks) + count($pro_checks);
    foreach ($free_checks as $k => $v) if (!empty($opts[$k])) $active++;
    $cache_status = !empty($opts['page_cache']) ? 'Active' : 'Inactive'; $cache_color = !empty($opts['page_cache']) ? '#00b894' : '#636e72';
    
    echo '<div class="ssbc-grid"><div class="ssbc-card"><h3><span class="dashicons dashicons-awards"></span> Optimization Score</h3><div class="ssbc-score-card"><div class="ssbc-score-ring"><div class="ssbc-score-inner"><div class="ssbc-score-value">'.esc_html($pct).'</div><div class="ssbc-score-label">/ 100</div></div></div><div class="ssbc-score-status" style="color:'.esc_attr($color).'">'.esc_html($status).'</div><div class="ssbc-score-items">';
    foreach ($items as $i) {
        $icon_class = $i['on'] ? 'dashicons-yes' : 'dashicons-no-alt';
        $icon_style = $i['on'] ? 'color:var(--ssbc-success)' : 'color:var(--ssbc-gray)';
        echo '<div class="ssbc-score-item"><span class="dashicons '.esc_attr($icon_class).'" style="'.esc_attr($icon_style).'"></span><span>';
        echo esc_html($i['name']);
        if ($i['pro']) echo '<span class="ssbc-pro-badge" style="font-size:8px;">PRO</span>';
        echo '</span><span style="margin-left:auto;color:var(--ssbc-gray);">+'.esc_html($i['pts']).'</span></div>';
    }
    echo '</div></div></div><div class="ssbc-card"><h3><span class="dashicons dashicons-performance"></span> Quick Actions</h3><div class="ssbc-quick-stats"><div class="ssbc-quick-stat"><div class="ssbc-quick-stat-icon" style="background:'.esc_attr($color).'20;"><span class="dashicons dashicons-chart-bar" style="color:'.esc_attr($color).';"></span></div><div><span class="ssbc-quick-stat-value">'.esc_html($active).'/'.esc_html($total_features).'</span><span class="ssbc-quick-stat-label">Features</span></div></div><div class="ssbc-quick-stat"><div class="ssbc-quick-stat-icon" style="background:'.esc_attr($cache_color).'20;"><span class="dashicons dashicons-database" style="color:'.esc_attr($cache_color).';"></span></div><div><span class="ssbc-quick-stat-value">'.esc_html($cache_status).'</span><span class="ssbc-quick-stat-label">Page Cache</span></div></div></div><div class="ssbc-action-grid">';
    echo '<a href="'.esc_url(add_query_arg(array('tab'=>'speed-test','_wpnonce'=>wp_create_nonce('ssbc_tab_nonce')), $base)).'" class="ssbc-action-card ssbc-action-primary"><span class="dashicons dashicons-controls-play"></span><span class="ssbc-action-title">Run Speed Test</span><span class="ssbc-action-desc">Test performance</span></a>';
    echo '<a href="'.esc_url(wp_nonce_url(add_query_arg('ssbc_clear_cache', '1', $base), 'ssbc_clear_cache_link')).'" class="ssbc-action-card ssbc-action-danger"><span class="dashicons dashicons-trash"></span><span class="ssbc-action-title">Clear Cache</span><span class="ssbc-action-desc">Purge files</span></a>';
    echo '<a href="'.esc_url(add_query_arg(array('tab'=>'optimization','_wpnonce'=>wp_create_nonce('ssbc_tab_nonce')), $base)).'" class="ssbc-action-card"><span class="dashicons dashicons-admin-settings"></span><span class="ssbc-action-title">Optimization</span><span class="ssbc-action-desc">Minify, defer</span></a>';
    echo '<a href="'.esc_url(add_query_arg(array('tab'=>'caching','_wpnonce'=>wp_create_nonce('ssbc_tab_nonce')), $base)).'" class="ssbc-action-card"><span class="dashicons dashicons-database"></span><span class="ssbc-action-title">Caching</span><span class="ssbc-action-desc">Page cache</span></a></div></div></div>';
}

function ssbc_render_caching_tab($opts, $base) {
    $pc = 0; $ps = 0; $pd = SSBC_CACHE_DIR.'page/'; if (is_dir($pd)) { $f = glob($pd.'*.html'); if (is_array($f)) { $pc = count($f); foreach ($f as $x) $ps += filesize($x); } }
    $cc = 0; $cd = SSBC_CACHE_DIR.'css/'; if (is_dir($cd)) { $f = glob($cd.'*.css'); $cc = is_array($f) ? count($f) : 0; }
    $jc = 0; $jd = SSBC_CACHE_DIR.'js/'; if (is_dir($jd)) { $f = glob($jd.'*.js'); $jc = is_array($f) ? count($f) : 0; }
    echo '<form method="post" action="'.esc_url(add_query_arg(array('tab'=>'caching','_wpnonce'=>wp_create_nonce('ssbc_tab_nonce')), $base)).'">'; wp_nonce_field('ssbc_save'); echo '<input type="hidden" name="ssbc_form_tab" value="caching">';
    echo '<div class="ssbc-grid"><div class="ssbc-card"><h3><span class="dashicons dashicons-database"></span> Page Caching</h3>'; ssbc_render_toggle('page_cache', 'Enable Page Cache', 'Store HTML for instant delivery', $opts);
    echo '<div class="ssbc-toggle"><div class="ssbc-toggle-label"><strong>Cache Lifetime</strong><span>Seconds before expiry</span></div><input type="number" name="page_cache_lifetime" class="ssbc-input" style="width:120px;" min="60" value="'.esc_attr(isset($opts['page_cache_lifetime'])?$opts['page_cache_lifetime']:3600).'"></div>';
    echo '<div class="ssbc-cache-stats"><div class="ssbc-cache-stat"><div class="ssbc-cache-stat-value">'.esc_html($pc).'</div><div class="ssbc-cache-stat-label">Pages</div></div><div class="ssbc-cache-stat"><div class="ssbc-cache-stat-value">'.esc_html(ssbc_format_bytes($ps)).'</div><div class="ssbc-cache-stat-label">Size</div></div><div class="ssbc-cache-stat"><div class="ssbc-cache-stat-value">'.esc_html($cc).'</div><div class="ssbc-cache-stat-label">CSS</div></div><div class="ssbc-cache-stat"><div class="ssbc-cache-stat-value">'.esc_html($jc).'</div><div class="ssbc-cache-stat-label">JS</div></div></div></div>';
    echo '<div class="ssbc-card"><h3><span class="dashicons dashicons-admin-tools"></span> Server Optimization</h3>'; ssbc_render_toggle('gzip_compression', 'GZIP Compression', 'Compress text resources', $opts); ssbc_render_toggle('browser_caching', 'Browser Caching', 'Set cache headers', $opts); echo '</div>';
    echo '<div class="ssbc-card"><h3><span class="dashicons dashicons-dismiss"></span> Force No-Cache</h3>'; ssbc_render_toggle('force_no_cache', 'Force No-Cache Headers', 'Disable ALL caching', $opts, false, true); echo '</div>';
    echo '<div class="ssbc-card"><h3><span class="dashicons dashicons-art"></span> Critical CSS</h3>'; ssbc_render_toggle('critical_css', 'Enable Critical CSS', 'Inline critical CSS', $opts, false, true); ssbc_render_toggle('async_css', 'Async CSS Loading', 'Non-blocking CSS', $opts, false, true); echo '</div></div>';
    echo '<p style="margin-top:24px;"><button type="submit" name="ssbc_save_settings" value="1" class="ssbc-btn ssbc-btn-primary"><span class="dashicons dashicons-saved"></span> Save Settings</button></p></form>';
}

function ssbc_render_optimization_tab($opts, $base) {
    echo '<form method="post" action="'.esc_url(add_query_arg(array('tab'=>'optimization','_wpnonce'=>wp_create_nonce('ssbc_tab_nonce')), $base)).'">'; wp_nonce_field('ssbc_save'); echo '<input type="hidden" name="ssbc_form_tab" value="optimization">';
    echo '<div class="ssbc-grid"><div class="ssbc-card"><h3><span class="dashicons dashicons-editor-code"></span> HTML</h3>'; ssbc_render_toggle('minify_html', 'Minify HTML (Safe)', 'Remove whitespace', $opts); ssbc_render_toggle('minify_html_aggressive', 'Aggressive Mode', 'Remove optional tags', $opts, true, true); echo '</div>';
    echo '<div class="ssbc-card"><h3><span class="dashicons dashicons-admin-appearance"></span> CSS</h3>'; ssbc_render_toggle('minify_css', 'Minify Inline CSS', 'Compress style tags', $opts); ssbc_render_toggle('minify_css_aggressive', 'Aggressive Mode', 'Shorten colors', $opts, true, true); ssbc_render_toggle('minify_css_files', 'Minify CSS Files', 'Compress stylesheets', $opts); ssbc_render_toggle('minify_css_files_aggressive', 'Aggressive Mode', 'More aggressive', $opts, true, true); ssbc_render_toggle('combine_css', 'Combine CSS', 'Merge files', $opts, false, true); ssbc_render_toggle('remove_query_strings', 'Remove Query Strings', 'Strip ?ver=', $opts); echo '</div>';
    echo '<div class="ssbc-card"><h3><span class="dashicons dashicons-media-code"></span> JavaScript</h3>'; ssbc_render_toggle('minify_inline_js', 'Minify Inline JS', 'Compress scripts', $opts); ssbc_render_toggle('minify_inline_js_aggressive', 'Aggressive Mode', 'Replace true/false', $opts, true, true); ssbc_render_toggle('minify_js_files', 'Minify JS Files', 'Compress files', $opts); ssbc_render_toggle('minify_js_files_aggressive', 'Aggressive Mode', 'More aggressive', $opts, true, true); ssbc_render_toggle('combine_js', 'Combine JS', 'Merge files', $opts, false, true); ssbc_render_toggle('defer_js', 'Defer JavaScript', 'Add defer', $opts); ssbc_render_toggle('delay_js', 'Delay JavaScript', 'Load on interaction', $opts);
    echo '<div class="ssbc-toggle ssbc-sub-toggle"><div class="ssbc-toggle-label"><strong>Delay Timeout</strong><span>Milliseconds (0 = interaction)</span></div><input type="number" name="delay_js_timeout" class="ssbc-input" style="width:120px;" min="0" value="'.esc_attr(isset($opts['delay_js_timeout'])?$opts['delay_js_timeout']:0).'"></div>';
    ssbc_render_toggle('remove_jquery_migrate', 'Remove jQuery Migrate', 'Remove legacy', $opts);
    echo '<div class="ssbc-section-title">Delay JS Exclusions</div><textarea name="delay_js_exclusions" class="ssbc-input" placeholder="jquery">'.esc_textarea(isset($opts['delay_js_exclusions'])?$opts['delay_js_exclusions']:'').'</textarea><p class="ssbc-field-help">One keyword per line</p></div>';
    echo '<div class="ssbc-card"><h3><span class="dashicons dashicons-images-alt2"></span> Lazy Loading</h3>'; ssbc_render_toggle('lazy_load_images', 'Lazy Load Images', 'Defer images', $opts); ssbc_render_toggle('lazy_load_iframes', 'Lazy Load iFrames', 'Defer embeds', $opts); ssbc_render_toggle('lazy_load_videos', 'Lazy Load Videos', 'preload=none', $opts); echo '</div>';
    echo '<div class="ssbc-card"><h3><span class="dashicons dashicons-trash"></span> Cleanup</h3>'; ssbc_render_toggle('disable_emojis', 'Disable Emojis', 'Remove emoji scripts', $opts); ssbc_render_toggle('disable_embeds', 'Disable oEmbed', 'Remove embeds', $opts); ssbc_render_toggle('disable_xmlrpc', 'Disable XML-RPC', 'Block remote', $opts); echo '</div>';
    echo '<div class="ssbc-card"><h3><span class="dashicons dashicons-editor-removeformatting"></span> Header Cleanup</h3>'; ssbc_render_toggle('remove_shortlink', 'Remove Shortlink', 'Remove shortlink', $opts); ssbc_render_toggle('remove_rsd_link', 'Remove RSD Link', 'Remove RSD', $opts); ssbc_render_toggle('remove_wlwmanifest', 'Remove WLW Manifest', 'Remove WLW', $opts); ssbc_render_toggle('remove_feed_links', 'Remove Feed Links', 'Remove RSS', $opts); ssbc_render_toggle('remove_rest_api_link', 'Remove REST API Link', 'Remove REST', $opts); ssbc_render_toggle('remove_wp_version', 'Remove WP Version', 'Hide version', $opts); echo '</div>';
    echo '<div class="ssbc-card"><h3><span class="dashicons dashicons-heart"></span> Heartbeat</h3>'; ssbc_render_toggle('heartbeat_disable', 'Disable Heartbeat', 'Stop heartbeat', $opts, false, true); echo '</div>';
    echo '<div class="ssbc-card"><h3><span class="dashicons dashicons-download"></span> Resource Hints</h3>'; ssbc_render_toggle('preconnect', 'Preconnect', 'Preconnect domains', $opts, false, true); ssbc_render_toggle('dns_prefetch', 'DNS Prefetch', 'Prefetch DNS', $opts, false, true); echo '</div></div>';
    echo '<p style="margin-top:24px;"><button type="submit" name="ssbc_save_settings" value="1" class="ssbc-btn ssbc-btn-primary"><span class="dashicons dashicons-saved"></span> Save Settings</button></p></form>';
}

function ssbc_render_speed_test_tab($base) {
    $history = ssbc_get_speed_tests(20); $tu = home_url('/');
    echo '<div class="ssbc-card" style="margin-bottom:24px;"><h3><span class="dashicons dashicons-performance"></span> Run Speed Test</h3><p style="color:var(--ssbc-gray);">Test your homepage loading speed.</p><div style="background:rgba(108,92,231,0.05);padding:12px;border-radius:8px;margin:16px 0;"><strong>Testing:</strong> '.esc_html($tu).'</div><button type="button" id="ssbc-run-test" class="ssbc-btn ssbc-btn-primary"><span class="dashicons dashicons-controls-play"></span> Run Test</button><div id="ssbc-test-area" style="display:none;padding:40px;text-align:center;"><p>Testing...</p></div></div>';
    echo '<div class="ssbc-card"><h3><span class="dashicons dashicons-backup"></span> Test History</h3><table class="widefat"><thead><tr><th>Date</th><th style="text-align:right;">TTFB</th><th style="text-align:right;">Load</th><th style="text-align:right;">Size</th><th style="text-align:center;">Rating</th></tr></thead><tbody id="ssbc-history-body">';
    if ($history) foreach ($history as $t) ssbc_render_history_row($t); else echo '<tr id="ssbc-no-tests"><td colspan="5" style="text-align:center;padding:40px;color:var(--ssbc-gray);">No tests yet. Run your first test!</td></tr>';
    echo '</tbody></table>'; if ($history) echo '<p style="margin-top:16px;"><a href="'.esc_url(wp_nonce_url(add_query_arg(array('tab'=>'speed-test','ssbc_clear_tests'=>'1'), $base), 'ssbc_clear_tests')).'" class="ssbc-btn ssbc-btn-secondary ssbc-clear-history-btn"><span class="dashicons dashicons-trash"></span> Clear</a></p>'; echo '</div>';
}

function ssbc_render_history_row($t) {
    $r = ssbc_get_load_rating($t['load_time']); $tr = ssbc_get_ttfb_rating($t['ttfb']); $sr = ssbc_get_size_rating($t['page_size']); $ttfb = floatval(isset($t['ttfb'])?$t['ttfb']:0); $date = wp_date('M j, g:i a', strtotime($t['ts'].' UTC'));
    echo '<tr><td>'.esc_html($date).'</td><td style="text-align:right;"><span class="ssbc-cell-metric" style="background:'.esc_attr($tr['color']).'22;color:'.esc_attr($tr['color']).';">'.esc_html(number_format($ttfb, 2)).'s</span></td><td style="text-align:right;"><span class="ssbc-cell-metric" style="background:'.esc_attr($r['color']).'22;color:'.esc_attr($r['color']).';">'.esc_html(number_format($t['load_time'], 2)).'s</span></td><td style="text-align:right;"><span class="ssbc-cell-metric" style="background:'.esc_attr($sr['color']).'22;color:'.esc_attr($sr['color']).';">'.esc_html(ssbc_format_bytes($t['page_size'])).'</span></td><td style="text-align:center;"><span class="ssbc-badge" style="background:'.esc_attr($r['color']).'22;color:'.esc_attr($r['color']).';">'.esc_html($r['label']).'</span></td></tr>';
}

function ssbc_render_pro_tab() {
    $now = time(); $midnight = strtotime('tomorrow midnight'); $remaining = $midnight - $now;
    $hours = absint(floor($remaining / 3600)); $minutes = absint(floor(($remaining % 3600) / 60)); $seconds = absint($remaining % 60);
    echo '<div class="ssbc-card ssbc-pro-card"><h3 style="font-size:28px;margin-bottom:10px;"><span class="dashicons dashicons-star-filled" style="color:#f39c12;"></span> '.esc_html__('Upgrade to Pro','quickfire-cache-speed-booster').'</h3><p style="font-size:16px;color:var(--ssbc-gray);">'.esc_html__('Unlock advanced optimization features','quickfire-cache-speed-booster').'</p>';
    echo '<div class="ssbc-timer"><div class="ssbc-timer-title">Limited Time Offer - 50% OFF Expires In:</div><div class="ssbc-timer-countdown" id="ssbc-countdown">'.esc_html(sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds)).'</div></div>';
    echo '<div class="ssbc-coupon"><span class="ssbc-coupon-label">CODE:</span><span class="ssbc-coupon-code" id="ssbc-coupon-code">50%OFSPEEDPLUGIN</span><button type="button" class="ssbc-coupon-copy" id="ssbc-copy-btn">Copy</button></div>';
    echo '<div class="ssbc-pro-features">';
    foreach (array('Aggressive HTML Minification'=>'Remove optional tags','Aggressive CSS Minification'=>'Shorten colors','Aggressive JS Minification'=>'Replace true/false','Combine CSS Files'=>'Merge stylesheets','Combine JS Files'=>'Merge scripts','Async CSS Loading'=>'Non-blocking CSS','Critical CSS'=>'Inline above-fold CSS','Force No-Cache'=>'Complete control','Heartbeat Control'=>'Optimize Heartbeat','Resource Hints'=>'Preconnect, DNS Prefetch') as $title => $desc) {
        echo '<div class="ssbc-pro-feature"><span class="dashicons dashicons-yes-alt"></span><div><strong>'.esc_html($title).'</strong><br><span style="font-size:12px;color:var(--ssbc-gray);">'.esc_html($desc).'</span></div></div>';
    }
    echo '</div>';
    echo '<p style="margin-top:30px;"><a href="'.esc_url(SSBC_PRO_URL).'" target="_blank" class="ssbc-btn ssbc-btn-primary" style="font-size:18px;padding:16px 40px;"><span class="dashicons dashicons-cart"></span> Get Pro Version - 50% OFF</a></p></div>';
}

add_action('admin_footer', 'ssbc_pro_tab_scripts');
function ssbc_pro_tab_scripts() {
    $screen = get_current_screen();
    if (!$screen || 'toplevel_page_quickfire-cache-speed-booster' !== $screen->id) return;
    $tab = 'dashboard';
    if (isset($_GET['tab'], $_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'ssbc_tab_nonce')) $tab = sanitize_key(wp_unslash($_GET['tab']));
    if ('pro' !== $tab) return;
    $now = time(); $midnight = strtotime('tomorrow midnight'); $remaining = $midnight - $now;
    wp_register_script('ssbc-pro-tab', false, array(), SSBC_VERSION, true);
    wp_enqueue_script('ssbc-pro-tab');
    wp_add_inline_script('ssbc-pro-tab', '!function(){var e='.esc_js($remaining).';function t(){if(e<=0){e=86400;}var t=Math.floor(e/3600),n=Math.floor(e%3600/60),o=e%60;document.getElementById("ssbc-countdown").textContent=(t<10?"0":"")+t+":"+(n<10?"0":"")+n+":"+(o<10?"0":"")+o;e--;}t();setInterval(t,1e3);document.getElementById("ssbc-copy-btn").addEventListener("click",function(){var c=document.getElementById("ssbc-coupon-code").textContent;navigator.clipboard.writeText(c).then(function(){document.getElementById("ssbc-copy-btn").textContent="Copied!";setTimeout(function(){document.getElementById("ssbc-copy-btn").textContent="Copy";},2000);});});}();');
}