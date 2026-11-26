<?php
/**
 * Plugin Name: PA SINOVA Starter - Safe Page HTML Cache (Early-Serve + Stale-While-Revalidate)
 * Description: Menyajikan cache HTML beranda & halaman publik statis sejak fase mu-plugins dengan TTL segar 30 menit, Stale-While-Revalidate 24 jam, dan shortcode [waktu_render] real-time.
 * Version: 2.5.0-starter
 */

defined('ABSPATH') || exit;

defined('PA_HOME_CACHE_FRESH_TTL') || define('PA_HOME_CACHE_FRESH_TTL', 1800);
defined('PA_HOME_CACHE_STALE_TTL') || define('PA_HOME_CACHE_STALE_TTL', 86400);

add_action('init', static function () {
    if (!shortcode_exists('waktu_render')) {
        add_shortcode('waktu_render', static function () {
            return 'Rendering : ' . timer_stop(0, 3) . ' detik.';
        });
    }
}, 20);

function pa_home_page_cache_request_path(): string {
    $uri  = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $path = (string) parse_url($uri, PHP_URL_PATH);
    return ($path === '' || $path === '/') ? '/' : $path;
}

function pa_home_page_cache_pages_dir(): string {
    return WP_CONTENT_DIR . '/cache/pa-pages';
}

function pa_home_page_cache_file(?string $path = null): string {
    $dir = WP_CONTENT_DIR . '/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $path = $path ?? pa_home_page_cache_request_path();
    if ($path === '' || $path === '/') {
        return $dir . '/pa-homepage.html';
    }
    $pagesDir = pa_home_page_cache_pages_dir();
    if (!is_dir($pagesDir)) {
        @mkdir($pagesDir, 0755, true);
    }
    return $pagesDir . '/page-' . md5($path) . '.html';
}

function pa_home_page_cache_is_early_eligible(): bool {
    if (PHP_SAPI === 'cli' || (defined('WP_ADMIN') && WP_ADMIN)) {
        return false;
    }
    if ((defined('DOING_AJAX') && DOING_AJAX) || (defined('DOING_CRON') && DOING_CRON) || (defined('REST_REQUEST') && REST_REQUEST) || (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)) {
        return false;
    }
    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
    if ($method !== 'GET' && $method !== 'HEAD') {
        return false;
    }
    $query = isset($_SERVER['QUERY_STRING']) ? trim((string) $_SERVER['QUERY_STRING']) : '';
    if ($query !== '') {
        return false;
    }

    $path = pa_home_page_cache_request_path();
    if (function_exists('pa_sinova_custom_login_slug')) {
        $customLoginSlug = pa_sinova_custom_login_slug();
        if ($customLoginSlug !== '' && preg_match('#/' . preg_quote($customLoginSlug, '#') . '(?:/|$)#i', $path)) {
            return false;
        }
    }
    if ($path !== '/') {
        if (substr($path, -1) !== '/') {
            return false;
        }
        // Dukung instalasi WordPress di root maupun subfolder (misal /web_pa/feed/, /web_pa/author/)
        if (preg_match('#/(?:wp-admin|wp-content|wp-includes|wp-json|wp-login|xmlrpc|feed|author|comments|cart|checkout|my-account)(?:/|$)#i', $path)) {
            return false;
        }
        if (preg_match('/(?:sitemap|\.[a-z0-9]{2,5}\/?$)/i', $path)) {
            return false;
        }
    }

    if (!empty($_COOKIE) && is_array($_COOKIE)) {
        foreach (array_keys($_COOKIE) as $cName) {
            if (preg_match('/^(?:wordpress_logged_in_|wp-postpass_|comment_author_|wp_woocommerce_session_)/i', (string) $cName)) {
                return false;
            }
        }
    }
    return true;
}

function pa_home_page_cache_is_eligible(): bool {
    if (!pa_home_page_cache_is_early_eligible()) {
        return false;
    }
    if (function_exists('is_admin') && is_admin()) {
        return false;
    }
    if (function_exists('is_user_logged_in') && is_user_logged_in()) {
        return false;
    }
    if (defined('DONOTCACHEPAGE') && DONOTCACHEPAGE) {
        return false;
    }
    if ((function_exists('is_404') && is_404()) || (function_exists('is_search') && is_search()) || (function_exists('is_feed') && is_feed()) || (function_exists('is_preview') && is_preview())) {
        return false;
    }
    return true;
}

function pa_home_page_cache_stamp_render_time(string $html): string {
    global $timestart;
    $origin = (isset($timestart) && is_numeric($timestart))
        ? (float) $timestart
        : (isset($_SERVER['REQUEST_TIME_FLOAT']) ? (float) $_SERVER['REQUEST_TIME_FLOAT'] : microtime(true));
    $elapsed = max(0.001, microtime(true) - $origin);
    $label   = 'Rendering : ' . number_format($elapsed, 3, '.', '') . ' detik.';

    $updated = preg_replace('/Rendering\s*:\s*[0-9]+(?:[.,][0-9]+)?\s*detik\./i', $label, $html, 1);
    return is_string($updated) ? $updated : $html;
}

function pa_home_page_cache_mark_stale(): void {
    $staleAt = time() - (PA_HOME_CACHE_FRESH_TTL + 10);
    $home    = WP_CONTENT_DIR . '/cache/pa-homepage.html';
    if (is_file($home)) {
        @touch($home, $staleAt);
    }
    $pagesDir = pa_home_page_cache_pages_dir();
    if (is_dir($pagesDir)) {
        $files = @glob($pagesDir . '/page-*.html');
        if (is_array($files)) {
            foreach ($files as $f) {
                if (is_file($f)) {
                    @touch($f, $staleAt);
                }
            }
        }
    }
}
add_action('save_post', 'pa_home_page_cache_mark_stale');
add_action('wp_update_nav_menu', 'pa_home_page_cache_mark_stale');
add_action('customize_save_after', 'pa_home_page_cache_mark_stale');

function pa_home_page_cache_send_body(string $html, bool $isHead): void {
    if ($isHead) {
        return;
    }
    $acceptEnc = strtolower((string) ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''));
    $zlibOn    = ini_get('zlib.output_compression');
    $canGzip   = (!$zlibOn || strtolower((string) $zlibOn) === 'off')
        && strpos($acceptEnc, 'gzip') !== false
        && function_exists('gzencode')
        && strlen($html) > 1024;

    if ($canGzip && !headers_sent()) {
        $gz = @gzencode($html, 6);
        if (is_string($gz) && $gz !== '') {
            header('Content-Encoding: gzip');
            header('Content-Length: ' . strlen($gz));
            echo $gz;
            return;
        }
    }
    echo $html;
}

(static function () {
    if (!pa_home_page_cache_is_early_eligible()) {
        return;
    }
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua !== '' && preg_match('#\b(sqlmap|nikto|acunetix|havij|wpscan|masscan)\b#i', $ua)) {
        if (!headers_sent()) {
            status_header(403);
            header('Content-Type: application/json; charset=UTF-8');
            header('X-PA-WAF-Block: scanner-ua');
        }
        echo json_encode(array('ok' => false, 'code' => 403, 'reason' => 'scanner-ua'));
        exit;
    }

    $file = pa_home_page_cache_file();
    if (!is_readable($file)) {
        return;
    }
    $mtime = @filemtime($file);
    $size  = @filesize($file);
    if (!$mtime || !$size || $size < 4000) {
        return;
    }
    $age    = time() - $mtime;
    $isHead = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'HEAD';
    $etag   = 'W/"pa-' . dechex((int) $mtime) . '-' . dechex((int) $size) . '"';
    $nonce  = function_exists('pa_starter_csp_nonce') ? pa_starter_csp_nonce() : null;

    if ($age <= PA_HOME_CACHE_FRESH_TTL) {
        if (!headers_sent()) {
            if (function_exists('pa_starter_emit_security_headers')) {
                pa_starter_emit_security_headers(true, $nonce);
            }
            header('X-PA-Homepage-Cache: HIT-EARLY');
            header('X-PA-Cache-Age: ' . max(0, $age));
            header('ETag: ' . $etag);
            header('Vary: Accept-Encoding');
            header('Cache-Control: public, max-age=120, s-maxage=600, stale-while-revalidate=3600');
            header('Content-Type: text/html; charset=UTF-8');
        }

        $ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
        if ($ifNoneMatch !== '' && strpos($ifNoneMatch, $etag) !== false) {
            if (!headers_sent()) {
                status_header(304);
            }
            exit;
        }

        if (!$isHead) {
            $html = @file_get_contents($file);
            if (is_string($html) && $html !== '') {
                $html = pa_home_page_cache_stamp_render_time($html);
                if (
                    $nonce !== null
                    && function_exists('pa_starter_effective_csp_mode')
                    && pa_starter_effective_csp_mode() === 'strict'
                    && function_exists('pa_starter_inject_csp_nonce_into_html')
                ) {
                    $html = pa_starter_inject_csp_nonce_into_html($html, $nonce);
                }
                pa_home_page_cache_send_body($html, false);
            }
        }
        exit;
    }

    if ($age <= PA_HOME_CACHE_STALE_TTL && (function_exists('litespeed_finish_request') || function_exists('fastcgi_finish_request'))) {
        @touch($file, time() - (PA_HOME_CACHE_FRESH_TTL - 45));
        if (!headers_sent()) {
            if (function_exists('pa_starter_emit_security_headers')) {
                pa_starter_emit_security_headers(true, $nonce);
            }
            header('X-PA-Homepage-Cache: STALE-REVALIDATE');
            header('X-PA-Cache-Age: ' . max(0, $age));
            header('ETag: ' . $etag);
            header('Vary: Accept-Encoding');
            header('Cache-Control: public, max-age=60, s-maxage=300, stale-while-revalidate=3600');
            header('Content-Type: text/html; charset=UTF-8');
        }
        if (!$isHead) {
            $html = @file_get_contents($file);
            if (is_string($html) && $html !== '') {
                $html = pa_home_page_cache_stamp_render_time($html);
                if (
                    $nonce !== null
                    && function_exists('pa_starter_effective_csp_mode')
                    && pa_starter_effective_csp_mode() === 'strict'
                    && function_exists('pa_starter_inject_csp_nonce_into_html')
                ) {
                    $html = pa_starter_inject_csp_nonce_into_html($html, $nonce);
                }
                pa_home_page_cache_send_body($html, false);
            }
        }
        @ignore_user_abort(true);
        if (function_exists('litespeed_finish_request')) {
            @litespeed_finish_request();
        } elseif (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        $GLOBALS['pa_home_bg_revalidate'] = true;
    }
})();

function pa_home_page_cache_start(): void {
    $needStrictCsp = (!is_admin() && function_exists('pa_starter_effective_csp_mode') && pa_starter_effective_csp_mode() === 'strict');
    if (pa_home_page_cache_is_eligible() || !empty($GLOBALS['pa_home_bg_revalidate']) || $needStrictCsp) {
        ob_start('pa_home_page_cache_store');
    }
}
add_action('template_redirect', 'pa_home_page_cache_start', -999);

function pa_home_page_cache_store($html) {
    if (!is_string($html) || $html === '' || stripos($html, '</html>') === false) {
        return $html;
    }

    $code = function_exists('http_response_code') ? (int) http_response_code() : 200;
    $isBg = !empty($GLOBALS['pa_home_bg_revalidate']);

    $hasSensitiveForm = (
        stripos($html, 'id="loginform"') !== false ||
        stripos($html, 'name="wp-submit"') !== false ||
        stripos($html, 'cf-turnstile') !== false
    );

    $canCache = ($code === 200 && !$hasSensitiveForm && pa_home_page_cache_is_eligible());

    if ($canCache) {
        $optimized = preg_replace_callback('/<style\b([^>]*)>([\s\S]*?)<\/style>/i', static function ($m) {
            $css = $m[2];
            $css = preg_replace('/\/\*[\s\S]*?\*\//', '', $css);
            $css = preg_replace('/\s*\r?\n\s*/', '', $css);
            $css = preg_replace('/\s{2,}/', ' ', $css);
            $css = str_replace(array(' {', '{ ', ' }', '; ', ': '), array('{', '{', '}', ';', ':'), $css);
            return '<style' . $m[1] . '>' . trim($css) . '</style>';
        }, $html);
        if (is_string($optimized) && $optimized !== '') {
            $html = $optimized;
        }
        $strippedComments = preg_replace('/<(script|style|textarea)\b[^>]*>[\s\S]*?<\/\1>(*SKIP)(*FAIL)|<!--(?!\s*\[if\b)[\s\S]*?-->/i', '', $html);
        if (is_string($strippedComments) && $strippedComments !== '') {
            $html = $strippedComments;
        }

        $cacheHtml = function_exists('pa_starter_strip_csp_nonce_from_html')
            ? pa_starter_strip_csp_nonce_from_html($html)
            : $html;

        $file = pa_home_page_cache_file();
        $tmp  = $file . '.tmp.' . getmypid();
        if (@file_put_contents($tmp, $cacheHtml, LOCK_EX) !== false) {
            @rename($tmp, $file);
        } else {
            @unlink($tmp);
        }
    }

    if ($isBg) {
        return '';
    }

    if (
        !is_admin()
        && function_exists('pa_starter_effective_csp_mode')
        && pa_starter_effective_csp_mode() === 'strict'
        && function_exists('pa_starter_csp_nonce')
        && function_exists('pa_starter_inject_csp_nonce_into_html')
    ) {
        $html = pa_starter_inject_csp_nonce_into_html($html, pa_starter_csp_nonce());
    }

    return $html;
}
