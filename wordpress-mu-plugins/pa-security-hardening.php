<?php
/**
 * Plugin Name: PA SINOVA Starter - Security Hardening, Anti-Judol WAF, Rate Limiter, TOTP MFA & Hidden Login
 * Description: Pengamanan berlapis portal Pengadilan Agama: Brute-Force Rate Limiter, TOTP MFA RFC 6238 bawaan, idle timeout 60 menit, kebijakan password kuat, Hidden Login URL 404, OWASP Headers, proteksi folder .htaccess, Anti-Judol WAF, & CAPTCHA login.
 * Version: 1.4.0-starter
 */

defined('ABSPATH') || exit;

defined('DISALLOW_FILE_EDIT') || define('DISALLOW_FILE_EDIT', true);
@ini_set('display_errors', '0');

if (function_exists('header_remove') && !headers_sent()) {
    @header_remove('X-Powered-By');
}

add_filter('xmlrpc_enabled', '__return_false');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
add_filter('the_generator', '__return_empty_string');
add_filter('elementor/frontend/print_generator_tag', '__return_false', 999);

function pa_starter_client_ip_hash(): string {
    $ip   = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $salt = function_exists('wp_salt') ? wp_salt('auth') : __FILE__;
    return substr(hash_hmac('sha256', $ip, $salt), 0, 24);
}

function pa_starter_security_log(string $event, string $detail = ''): void {
    if (!function_exists('get_option') || !function_exists('update_option')) {
        return;
    }
    $logs = get_option('pa_starter_security_logs', array());
    if (!is_array($logs)) {
        $logs = array();
    }
    array_unshift($logs, array(
        'at'      => gmdate('Y-m-d H:i:s') . ' UTC',
        'event'   => sanitize_key($event),
        'detail'  => substr(preg_replace('/[\r\n]+/', ' ', $detail), 0, 140),
        'ip_hash' => substr(hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? '')), 0, 12),
    ));
    if (count($logs) > 40) {
        $logs = array_slice($logs, 0, 40);
    }
    update_option('pa_starter_security_logs', $logs, false);
}

add_action('wp_head', static function () {
    if (!is_admin()) ob_start();
}, -9999);

add_action('wp_head', static function () {
    if (!is_admin() && ob_get_level() > 0) {
        $head = (string) ob_get_clean();
        $head = preg_replace('/<meta\s+name=["\']generator["\'][^>]*>\s*/i', '', $head);
        echo $head;
    }
}, 99999);

add_filter('wp_headers', static function ($headers) {
    if (!is_array($headers)) {
        $headers = array();
    }
    unset($headers['X-Pingback'], $headers['x-pingback'], $headers['X-Powered-By']);
    $scriptBase = strtolower(basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
    $isAdminOrLogin = (function_exists('is_admin') && is_admin())
        || (defined('PA_SERVING_CUSTOM_LOGIN') && PA_SERVING_CUSTOM_LOGIN)
        || $scriptBase === 'wp-login.php'
        || (function_exists('is_customize_preview') && is_customize_preview());
    if (function_exists('pa_starter_security_headers_map')) {
        $nonce = (!$isAdminOrLogin && function_exists('pa_starter_csp_nonce')) ? pa_starter_csp_nonce() : null;
        foreach (pa_starter_security_headers_map(!$isAdminOrLogin, $nonce) as $k => $v) {
            $headers[$k] = $v;
        }
    }
    return $headers;
});

function pa_starter_rate_limit(string $bucket, int $maxHits, int $windowSec): bool {
    if (PHP_SAPI === 'cli' || !function_exists('get_transient')) {
        return true;
    }
    $key  = 'pa_rl_' . sanitize_key($bucket) . '_' . pa_starter_client_ip_hash();
    $now  = time();
    $data = get_transient($key);
    if (!is_array($data) || empty($data['e']) || (int) $data['e'] <= $now) {
        $data = array('c' => 1, 'e' => $now + max(10, $windowSec));
        set_transient($key, $data, max(10, $windowSec));
        return true;
    }
    if ((int) ($data['c'] ?? 0) >= $maxHits) {
        return false;
    }
    $data['c'] = (int) $data['c'] + 1;
    set_transient($key, $data, max(1, (int) $data['e'] - $now));
    return true;
}

add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);
add_filter('feed_links_show_comments_feed', '__return_false');

add_filter('robots_txt', static function ($output, $public) {
    if ((string) $public === '0') {
        return $output;
    }
    $out = (string) $output;
    if (stripos($out, 'Disallow: /?s=') === false) {
        $out .= "\nDisallow: /?s=\nDisallow: /search/\n";
    }
    if (stripos($out, 'Sitemap:') === false) {
        $out .= "\nSitemap: " . esc_url_raw(home_url('/wp-sitemap.xml')) . "\n";
    }
    return $out;
}, 20, 2);

add_action('wp_loaded', static function () {
    if (PHP_SAPI === 'cli' || (defined('DOING_CRON') && DOING_CRON)) {
        return;
    }
    $reqUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path   = strtolower(trim((string) parse_url($reqUri, PHP_URL_PATH), '/'));
    $base   = trim((string) parse_url((string) get_option('home', '/'), PHP_URL_PATH), '/');
    if ($base !== '' && stripos($path, $base) === 0) {
        $path = trim(substr($path, strlen($base)), '/');
    }

    if ($path === '.well-known/security.txt' || $path === 'security.txt') {
        if (!headers_sent()) {
            status_header(200);
            if (function_exists('pa_starter_emit_security_headers')) {
                pa_starter_emit_security_headers(false);
            }
            header('Content-Type: text/plain; charset=UTF-8');
            header('Cache-Control: public, max-age=86400');
        }
        $cfg   = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
        $email = sanitize_email((string) ($cfg['email'] ?? '')) ?: 'admin@pengadilan.go.id';
        echo "# Kebijakan Pengungkapan Kerentanan Keamanan (RFC 9116)\n";
        echo "Contact: mailto:{$email}\n";
        echo 'Expires: ' . gmdate('Y-m-d\TH:i:s\Z', time() + 180 * 86400) . "\n";
        echo "Preferred-Languages: id, en\n";
        echo 'Canonical: ' . esc_url_raw(home_url('/.well-known/security.txt')) . "\n";
        echo 'Policy: ' . esc_url_raw(home_url('/informasi-serta-merta/')) . "\n";
        exit;
    }

    if ($path === 'manifest.webmanifest') {
        if (!headers_sent()) {
            status_header(200);
            if (function_exists('pa_starter_emit_security_headers')) {
                pa_starter_emit_security_headers(false);
            }
            header('Content-Type: application/manifest+json; charset=UTF-8');
            header('Cache-Control: public, max-age=86400');
        }
        $satker = function_exists('pa_sinova_satker_name') ? pa_sinova_satker_name() : (get_bloginfo('name') ?: 'Pengadilan Agama');
        echo wp_json_encode(array(
            'name'             => $satker,
            'short_name'       => mb_substr($satker, 0, 28, 'UTF-8'),
            'description'      => 'Portal Resmi Keterbukaan Informasi & Layanan Peradilan',
            'lang'             => 'id-ID',
            'start_url'        => trailingslashit(home_url('/')),
            'scope'            => trailingslashit(home_url('/')),
            'display'          => 'browser',
            'theme_color'      => '#043927',
            'background_color' => '#f4faf7',
            'icons'            => array(
                array(
                    'src'     => home_url('/favicon.svg'),
                    'sizes'   => 'any',
                    'type'    => 'image/svg+xml',
                    'purpose' => 'any maskable',
                ),
            ),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($path === 'favicon.svg') {
        if (!headers_sent()) {
            status_header(200);
            if (function_exists('pa_starter_emit_security_headers')) {
                pa_starter_emit_security_headers(false);
            }
            header('Content-Type: image/svg+xml; charset=UTF-8');
            header('Cache-Control: public, max-age=604800');
        }
        echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="#043927"/><path d="M32 8L52 15V31C52 44 43.2 53.2 32 57C20.8 53.2 12 44 12 31V15L32 8Z" fill="#056e4f" stroke="#E5C158" stroke-width="3"/><path d="M32 18V43M21 25H43M21 25L17 34H25L21 25ZM43 25L39 34H47L43 25ZM26 43H38" stroke="#E5C158" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>';
        exit;
    }
}, 0);

add_filter('rest_pre_dispatch', static function ($result, $server, $request) {
    if (!is_user_logged_in() && $request instanceof WP_REST_Request) {
        $route = (string) $request->get_route();
        if (
            $route === '/wp/v2/users'
            || strpos($route, '/wp/v2/users/') === 0
            || $route === '/wp/v2/comments'
            || strpos($route, '/wp/v2/comments/') === 0
            || strpos($route, '/oembed/1.0/proxy') === 0
        ) {
            return new WP_Error('rest_cannot_list_users', 'Akses endpoint dibatasi kebijakan keamanan.', array('status' => 403));
        }
    }
    return $result;
}, 10, 3);

add_action('template_redirect', static function () {
    if (is_admin() || is_user_logged_in()) return;
    if (is_author() || (isset($_GET['author']) && $_GET['author'] !== '')) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
}, 1);

// Amankan direktori dokumen agar tidak bisa mengeksekusi PHP ataupun directory listing
add_action('admin_init', static function () {
    if (get_option('pa_starter_uploads_hardened') === '1') {
        return;
    }
    $docsRoot = WP_CONTENT_DIR . '/uploads/sinova-docs';
    if (!is_dir($docsRoot)) {
        wp_mkdir_p($docsRoot);
    }
    $hta = $docsRoot . '/.htaccess';
    if (is_dir($docsRoot) && !is_file($hta)) {
        $rules = "Options -Indexes\n"
            . "<FilesMatch \"\\.(php[0-9]*|phtml|phar|pht|cgi|pl|py|sh|asp|aspx)$\">\n"
            . "  <IfModule mod_authz_core.c>\n    Require all denied\n  </IfModule>\n"
            . "  <IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n  </IfModule>\n"
            . "</FilesMatch>\n"
            . "<IfModule mod_headers.c>\n  Header set X-Content-Type-Options \"nosniff\"\n</IfModule>\n";
        @file_put_contents($hta, $rules, LOCK_EX);
    }
    if (is_dir($docsRoot) && !is_file($docsRoot . '/index.php')) {
        @file_put_contents($docsRoot . '/index.php', "<?php // Silence is golden.\n", LOCK_EX);
    }
    update_option('pa_starter_uploads_hardened', '1', false);
});

// Layer 1 WAF: Anti-Judol & Webshell Probes
(static function () {
    if (PHP_SAPI === 'cli') {
        return;
    }
    $rawUri  = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $rawQs   = (string) ($_SERVER['QUERY_STRING'] ?? '');
    $ua      = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    $decoded = strtolower(rawurldecode($rawUri . ' ' . $rawQs));

    $block = static function ($reason) use ($rawUri) {
        if (!headers_sent()) {
            status_header(403);
            header('Content-Type: application/json; charset=UTF-8');
            header('X-PA-WAF-Block: ' . $reason);
        }
        echo json_encode(array('ok' => false, 'code' => 403, 'reason' => $reason));
        exit;
    };

    if (preg_match('#/(wp-comments-post|wp-trackback)\.php\b#i', $rawUri)) {
        $block('comments-trackback-disabled');
    }
    if (preg_match('#\b(alfa|wso|c99|r57|b374k|adminer|filemanager)\.php\b#i', $decoded)
        || preg_match('#(eval\s*\(\s*base64_decode|passthru\s*\(|shell_exec\s*\()#i', $decoded)
    ) {
        $block('webshell-probe');
    }
    if (strpos($rawUri, '/wp-admin/') === false) {
        if (preg_match('#(\bunion\b[\s+/%]+(?:all[\s+/%]+)?\bselect\b|information_schema|\.\./\.\./etc/passwd|<script\b|javascript\s*:)#i', $decoded)) {
            $block('sqli-lfi-xss');
        }
        if (preg_match('#(slot[\s_-]*gacor|situs[\s_-]*judi|togel[\s_-]*online|mahjong[\s_-]*ways|gates[\s_-]*of[\s_-]*olympus|\bmaxwin\b|rtp[\s_-]*slot|\bsbobet\b|pragmatic[\s_-]*play|bandar[\s_-]*togel)#i', $decoded)) {
            $block('anti-judol-uri');
        }
    }
    if ($ua !== '' && preg_match('#\b(sqlmap|nikto|acunetix|havij|wpscan|masscan)\b#i', $ua)) {
        $block('scanner-ua');
    }
})();

// Layer 2: Database Content Guard (dinamis memakai domain site saat ini)
add_filter('wp_insert_post_data', static function ($data) {
    if (!is_array($data)) return $data;
    $combined = (string) ($data['post_title'] ?? '') . "\n" . (string) ($data['post_content'] ?? '');
    if (preg_match('/\b(slot\s+gacor|judi\s+online|situs\s+toto|bandar\s+togel|rtp\s+live|daftar\s+slot|maxwin\s+hari\s+ini)\b/i', $combined)) {
        pa_starter_security_log('content-judol-blocked', (string) ($data['post_title'] ?? ''));
        wp_die('Penyimpanan dibatalkan oleh Anti-Judol Guard.', 'Blokir Keamanan', array('response' => 403));
    }
    $host = preg_quote((string) parse_url(home_url('/'), PHP_URL_HOST), '/');
    if ($host !== '' && preg_match('/<[^>]+style=["\'][^"\']*(display\s*:\s*none|visibility\s*:\s*hidden|left\s*:\s*-\d{3,}px)[^"\']*["\'][^>]*>.*?<a\s+[^>]*href=["\']https?:\/\/(?!(?:[a-z0-9.-]*\.)?' . $host . '\b)/is', (string) ($data['post_content'] ?? ''))) {
        pa_starter_security_log('content-cloaking-blocked', (string) ($data['post_title'] ?? ''));
        wp_die('Penyimpanan dibatalkan oleh Cloaking Guard.', 'Blokir Keamanan', array('response' => 403));
    }
    return $data;
}, 10, 1);

add_filter('wp_handle_upload_prefilter', static function ($file) {
    if (!is_array($file) || empty($file['name'])) return $file;
    if (preg_match('/\.(php[0-9]*|phtml|phar|pht|cgi|pl|py|sh|asp|aspx|htaccess)(\.|$)/i', (string) $file['name'])) {
        pa_starter_security_log('upload-ext-blocked', (string) $file['name']);
        $file['error'] = 'Ekstensi berkas ditolak kebijakan keamanan.';
    }
    return $file;
});

// Layer 3: Brute-Force Login Rate Limiter (5x gagal / 15 menit) + Dual-Mode Login CAPTCHA
function pa_starter_login_turnstile(): ?array {
    if (!function_exists('pa_sinova_get_settings')) return null;
    $cfg = pa_sinova_get_settings();
    if (!empty($cfg['turnstile_site_key']) && !empty($cfg['turnstile_secret_key'])) {
        return array('site_key' => $cfg['turnstile_site_key'], 'secret_key' => $cfg['turnstile_secret_key']);
    }
    return null;
}

add_action('login_enqueue_scripts', static function () {
    if (pa_starter_login_turnstile() !== null) {
        wp_enqueue_script('cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true);
    }
    echo '<style>.pa-captcha-box{margin:0 0 16px;padding:12px 14px;border:1.5px solid #C9A227;border-radius:8px;background:#f4faf7}.pa-captcha-box label{display:block;font-weight:700;color:#056e4f;margin-bottom:6px;font-size:13px}.pa-captcha-box input[type="text"]{width:100%;margin-bottom:0!important;font-size:16px;padding:6px 10px;border:1px solid #056e4f;border-radius:6px}.pa-hp-wrap{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;opacity:0}</style>';
});

add_action('login_form', static function () {
    $ts = pa_starter_login_turnstile();
    echo '<div class="pa-hp-wrap" aria-hidden="true"><label for="pa_hp_website">Website</label><input type="text" name="pa_hp_website" id="pa_hp_website" value="" tabindex="-1" autocomplete="off"></div>';
    if ($ts !== null) {
        echo '<div class="pa-captcha-box"><label>Verifikasi Cloudflare Turnstile</label><div class="cf-turnstile" data-sitekey="' . esc_attr($ts['site_key']) . '" data-theme="light"></div></div>';
        return;
    }
    $a = wp_rand(2, 9);
    $b = wp_rand(1, 9);
    $exp = (string) (time() + 900);
    $nonce = wp_generate_password(12, false, false);
    $sig = hash_hmac('sha256', ($a + $b) . '|' . $exp . '|' . $nonce, wp_salt('auth'));
    $token = base64_encode($exp . '|' . $nonce . '|' . $sig);
    echo '<div class="pa-captcha-box"><label for="pa_captcha_ans">Verifikasi Keamanan: Berapa hasil <strong>' . esc_html("{$a} + {$b}") . '</strong>?</label><input type="text" id="pa_captcha_ans" name="pa_captcha_ans" inputmode="numeric" pattern="[0-9]*" autocomplete="off" required><input type="hidden" name="pa_captcha_token" value="' . esc_attr($token) . '"></div>';
});

add_filter('authenticate', static function ($user) {
    if ((defined('WP_CLI') && WP_CLI) || (defined('REST_REQUEST') && REST_REQUEST) || (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) || wp_doing_cron()) return $user;
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !isset($_POST['log'], $_POST['pwd'])) return $user;

    $ipHash = pa_starter_client_ip_hash();
    if (get_transient('pa_bf_lock_' . $ipHash)) {
        return new WP_Error('pa_rate_limited', '<strong>Keamanan:</strong> Terlalu banyak percobaan login gagal. Silakan tunggu 15 menit.');
    }
    if (!empty($_POST['pa_hp_website'])) {
        pa_starter_security_log('login-honeypot', sanitize_user((string) $_POST['log']));
        return new WP_Error('pa_captcha_failed', '<strong>Keamanan:</strong> Verifikasi anti-bot gagal.');
    }

    $ts = pa_starter_login_turnstile();
    if ($ts !== null) {
        $resp = sanitize_text_field(wp_unslash((string) ($_POST['cf-turnstile-response'] ?? '')));
        if ($resp === '') return new WP_Error('pa_turnstile_missing', '<strong>Keamanan:</strong> Harap selesaikan verifikasi Turnstile.');
        $res = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
            'timeout' => 8,
            'body'    => array('secret' => $ts['secret_key'], 'response' => $resp),
        ));
        if (is_wp_error($res) || empty(json_decode((string) wp_remote_retrieve_body($res), true)['success'])) {
            pa_starter_security_log('login-turnstile-fail', sanitize_user((string) $_POST['log']));
            return new WP_Error('pa_turnstile_failed', '<strong>Keamanan:</strong> Token Turnstile tidak valid.');
        }
        return $user;
    }

    $ans   = trim((string) ($_POST['pa_captcha_ans'] ?? ''));
    $raw   = base64_decode((string) ($_POST['pa_captcha_token'] ?? ''), true);
    $parts = is_string($raw) ? explode('|', $raw) : array();
    if (count($parts) !== 3 || !ctype_digit($parts[0]) || (int) $parts[0] < time()) {
        return new WP_Error('pa_captcha_expired', '<strong>Keamanan:</strong> Sesi CAPTCHA kedaluwarsa, muat ulang halaman.');
    }
    $expected = hash_hmac('sha256', $ans . '|' . $parts[0] . '|' . $parts[1], wp_salt('auth'));
    if (!hash_equals($expected, $parts[2])) {
        pa_starter_security_log('login-captcha-wrong', sanitize_user((string) $_POST['log']));
        return new WP_Error('pa_captcha_wrong', '<strong>Keamanan:</strong> Jawaban verifikasi salah.');
    }
    return $user;
}, 15);

add_action('wp_login_failed', static function ($username) {
    $ipHash = pa_starter_client_ip_hash();
    $fails  = (int) get_transient('pa_bf_fail_' . $ipHash) + 1;
    set_transient('pa_bf_fail_' . $ipHash, $fails, 900);
    if ($fails >= 5) {
        set_transient('pa_bf_lock_' . $ipHash, 1, 900);
        pa_starter_security_log('login-ratelimit-lock', (string) $username . " ({$fails}x gagal)");
    }
});

add_action('wp_login', static function ($userLogin = '', $user = null) {
    $ipHash = pa_starter_client_ip_hash();
    delete_transient('pa_bf_fail_' . $ipHash);
    delete_transient('pa_bf_lock_' . $ipHash);

    if (!$user instanceof WP_User || PHP_SAPI === 'cli' || defined('PA_MFA_COMPLETING')) {
        return;
    }
    if ((defined('REST_REQUEST') && REST_REQUEST) || (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    update_user_meta((int) $user->ID, 'pa_last_admin_seen', time());

    if (!pa_starter_mfa_disabled() && get_user_meta((int) $user->ID, 'pa_totp_enabled', true) === '1') {
        $secret = (string) get_user_meta((int) $user->ID, 'pa_totp_secret', true);
        if ($secret !== '') {
            $token      = bin2hex(random_bytes(24));
            $remember   = !empty($_POST['rememberme']);
            $redirectTo = !empty($_REQUEST['redirect_to']) ? esc_url_raw(wp_unslash((string) $_REQUEST['redirect_to'])) : admin_url();
            set_transient('pa_mfa_chal_' . $token, array(
                'user_id'     => (int) $user->ID,
                'remember'    => $remember,
                'redirect_to' => $redirectTo,
                'tries'       => 0,
            ), 300);
            wp_clear_auth_cookie();
            setcookie('pa_mfa_chal', $token, array(
                'expires'  => time() + 300,
                'path'     => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
                'domain'   => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ));
            wp_safe_redirect(add_query_arg('pa_mfa', '1', wp_login_url()));
            exit;
        }
    }
}, 5, 2);

// Layer 4: Menyembunyikan Halaman Login Admin (Custom Login URL + Stealth 404 pada wp-login.php / wp-admin)
function pa_starter_active_login_slug(): string {
    if (defined('PA_DISABLE_CUSTOM_LOGIN') && PA_DISABLE_CUSTOM_LOGIN) {
        return '';
    }
    if (function_exists('pa_sinova_custom_login_slug')) {
        return pa_sinova_custom_login_slug();
    }
    return '';
}

function pa_starter_rel_request_path(): string {
    $reqUri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $reqPath = trim((string) parse_url($reqUri, PHP_URL_PATH), '/');
    $homeRaw = function_exists('get_option') ? (string) get_option('home', '/') : '/';
    $base    = trim((string) parse_url($homeRaw, PHP_URL_PATH), '/');
    if ($base !== '' && stripos($reqPath, $base) === 0) {
        $reqPath = trim(substr($reqPath, strlen($base)), '/');
    }
    return strtolower($reqPath);
}

function pa_starter_rewrite_login_url(string $url, string $slug, ?string $scheme = null): string {
    if ($slug === '' || strpos($url, 'wp-login.php') === false) {
        return $url;
    }
    $target = trailingslashit(home_url('/' . $slug));
    if ($scheme !== null && ($scheme === 'https' || $scheme === 'login' || $scheme === 'login_post') && is_ssl()) {
        $target = set_url_scheme($target, 'https');
    } elseif (stripos($url, 'https://') === 0) {
        $target = set_url_scheme($target, 'https');
    }
    $query = (string) parse_url($url, PHP_URL_QUERY);
    return $query !== '' ? ($target . '?' . $query) : $target;
}

function pa_starter_send_stealth_404(): void {
    if (!headers_sent()) {
        if (function_exists('nocache_headers')) {
            nocache_headers();
        }
        if (function_exists('pa_starter_emit_security_headers')) {
            pa_starter_emit_security_headers(false);
        }
        if (function_exists('status_header')) {
            status_header(404);
        } else {
            header('HTTP/1.1 404 Not Found', true, 404);
        }
        header('X-Robots-Tag: noindex, nofollow');
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><title>404 Tidak Ditemukan</title><style>body{font-family:system-ui,-apple-system,sans-serif;background:#f8fafc;color:#1e293b;display:flex;align-items:center;justify-content:center;min-height:90vh;margin:0}main{max-width:460px;padding:28px;background:#fff;border:1px solid #cbd5e1;border-radius:12px;text-align:center}h1{margin:0 0 8px;font-size:22px;color:#043927}p{margin:0;color:#475569;font-size:14px}</style></head><body><main><h1>404 Halaman Tidak Ditemukan</h1><p>Tautan yang Anda tuju tidak tersedia pada portal ini.</p></main></body></html>';
    exit;
}

// ============================================================================
// Layer 5: Built-in TOTP MFA (RFC 6238), Session Idle Timeout, & Password Policy
// ============================================================================

defined('PA_ADMIN_IDLE_SECONDS') || define('PA_ADMIN_IDLE_SECONDS', 3600);

function pa_starter_mfa_disabled(): bool {
    return defined('PA_DISABLE_CUSTOM_MFA') && PA_DISABLE_CUSTOM_MFA;
}

function pa_starter_base32_encode(string $bytes): string {
    $alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits  = '';
    $len   = strlen($bytes);
    for ($i = 0; $i < $len; $i++) {
        $bits .= str_pad(decbin(ord($bytes[$i])), 8, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 5) as $chunk) {
        if (strlen($chunk) < 5) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        }
        $out .= $alpha[bindec($chunk)];
    }
    return $out;
}

function pa_starter_base32_decode(string $b32): string {
    $alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $clean = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $b32));
    $bits  = '';
    $len   = strlen($clean);
    for ($i = 0; $i < $len; $i++) {
        $pos = strpos($alpha, $clean[$i]);
        if ($pos === false) {
            continue;
        }
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) === 8) {
            $out .= chr(bindec($byte));
        }
    }
    return $out;
}

function pa_starter_totp_generate_secret(int $bytes = 20): string {
    return pa_starter_base32_encode(random_bytes(max(16, $bytes)));
}

function pa_starter_totp_code(string $secret, ?int $timeSlice = null): string {
    $key   = pa_starter_base32_decode($secret);
    $slice = $timeSlice ?? (int) floor(time() / 30);
    $time  = pack('N2', ($slice >> 32) & 0xffffffff, $slice & 0xffffffff);
    $hm    = hash_hmac('sha1', $time, $key, true);
    $off   = ord(substr($hm, -1)) & 0x0f;
    $part  = substr($hm, $off, 4);
    $val   = unpack('N', $part);
    $num   = ($val[1] & 0x7fffffff) % 1000000;
    return str_pad((string) $num, 6, '0', STR_PAD_LEFT);
}

function pa_starter_totp_verify(string $secret, string $code, int $window = 1, int $userId = 0): bool {
    $clean = preg_replace('/\D+/', '', $code);
    if (strlen($clean) !== 6) {
        return false;
    }
    $nowSlice = (int) floor(time() / 30);
    $lastStep = $userId > 0 ? (int) get_user_meta($userId, 'pa_totp_last_step', true) : -1;

    for ($i = -$window; $i <= $window; $i++) {
        $slice = $nowSlice + $i;
        if ($userId > 0 && $lastStep > 0 && $slice <= $lastStep) {
            continue;
        }
        if (hash_equals(pa_starter_totp_code($secret, $slice), $clean)) {
            if ($userId > 0) {
                update_user_meta($userId, 'pa_totp_last_step', $slice);
            }
            return true;
        }
    }
    return false;
}

function pa_starter_totp_generate_recovery_codes(int $count = 8): array {
    $alpha  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $maxIdx = strlen($alpha) - 1;
    $plain  = array();
    $hashes = array();
    for ($i = 0; $i < $count; $i++) {
        $raw = '';
        for ($j = 0; $j < 10; $j++) {
            $raw .= $alpha[random_int(0, $maxIdx)];
        }
        $plain[]  = substr($raw, 0, 5) . '-' . substr($raw, 5, 5);
        $hashes[] = wp_hash_password($raw);
    }
    return array('plain' => $plain, 'hashes' => $hashes);
}

function pa_starter_totp_consume_recovery(int $userId, string $code): bool {
    $norm = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
    if (strlen($norm) < 8) {
        return false;
    }
    $hashes = get_user_meta($userId, 'pa_totp_recovery_hashes', true);
    if (!is_array($hashes) || empty($hashes)) {
        return false;
    }
    foreach ($hashes as $idx => $hash) {
        if (is_string($hash) && wp_check_password($norm, $hash)) {
            unset($hashes[$idx]);
            update_user_meta($userId, 'pa_totp_recovery_hashes', array_values($hashes));
            return true;
        }
    }
    return false;
}

function pa_starter_totp_provisioning_uri(WP_User $user, string $secret): string {
    $rawIssuer = function_exists('pa_sinova_satker_name') ? pa_sinova_satker_name() : (get_bloginfo('name') ?: 'PA Portal');
    $issuer    = trim((string) preg_replace('/[^A-Za-z0-9._-]/', '', $rawIssuer));
    $issuer    = substr($issuer !== '' ? $issuer : 'PAPortal', 0, 14);
    $login     = substr((string) preg_replace('/[^A-Za-z0-9._@-]/', '', (string) $user->user_login), 0, 14) ?: 'operator';
    $label     = rawurlencode($issuer . ':' . $login);
    return 'otpauth://totp/' . $label . '?secret=' . rawurlencode($secret) . '&issuer=' . rawurlencode($issuer);
}

function pa_starter_clear_mfa_cookie(): void {
    setcookie('pa_mfa_chal', '', array(
        'expires'  => time() - 3600,
        'path'     => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
        'domain'   => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ));
}

function pa_starter_render_mfa_screen(string $errorMsg = '', string $loginBackUrl = ''): void {
    if (!headers_sent()) {
        nocache_headers();
        if (function_exists('pa_starter_emit_security_headers')) {
            pa_starter_emit_security_headers(false);
        }
        status_header(200);
        header('X-Robots-Tag: noindex, nofollow');
        header('Content-Type: text/html; charset=UTF-8');
    }
    $back = $loginBackUrl ?: wp_login_url();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="robots" content="noindex,nofollow">
        <title>Verifikasi MFA (TOTP) · Portal Operator</title>
        <style>
            body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#022c22,#056e4f);font-family:system-ui,-apple-system,sans-serif;padding:16px}
            .box{width:100%;max-width:420px;background:#fff;border-radius:16px;border:2px solid #C9A227;padding:28px;box-shadow:0 18px 42px rgba(0,0,0,.35)}
            .badge{display:inline-block;padding:4px 10px;border-radius:999px;background:#ecfdf5;border:1px solid #056e4f;color:#056e4f;font-size:11px;font-weight:800;text-transform:uppercase;margin-bottom:8px}
            h1{margin:0 0 8px;font-size:20px;color:#043927}
            p{margin:0 0 16px;font-size:13.5px;line-height:1.5;color:#475569}
            .err{padding:10px 12px;margin-bottom:14px;border-radius:8px;background:#fef2f2;border:1px solid #ef4444;color:#991b1b;font-size:13px;font-weight:600}
            label{display:block;font-size:12.5px;font-weight:700;color:#043927;margin-bottom:6px}
            input[type="text"]{width:100%;box-sizing:border-box;padding:11px 12px;font-size:18px;letter-spacing:.12em;text-align:center;border:1.5px solid #056e4f;border-radius:10px;margin-bottom:14px}
            button{width:100%;padding:12px;border:none;border-radius:10px;background:linear-gradient(135deg,#043927,#056e4f);color:#E5C158;font-size:14px;font-weight:800;cursor:pointer}
            .back{display:block;text-align:center;margin-top:14px;font-size:12.5px;color:#056e4f;font-weight:700;text-decoration:none}
        </style>
    </head>
    <body>
        <main class="box">
            <span class="badge">Autentikasi Dua Faktor (MFA)</span>
            <h1>Masukkan Kode Verifikasi</h1>
            <p>Buka aplikasi <strong>Google / Microsoft Authenticator</strong> Anda lalu masukkan kode 6 digit, atau masukkan salah satu <em>Recovery Code</em>.</p>
            <?php if ($errorMsg !== '') : ?>
                <div class="err"><?php echo esc_html($errorMsg); ?></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(add_query_arg('pa_mfa', '1', $back)); ?>">
                <?php wp_nonce_field('pa_mfa_verify_action', 'pa_mfa_nonce'); ?>
                <label for="mfa_code">Kode TOTP (6 Digit) atau Recovery Code</label>
                <input type="text" id="mfa_code" name="mfa_code" autocomplete="one-time-code" autofocus required>
                <button type="submit">Verifikasi &amp; Masuk Dashboard</button>
            </form>
            <a class="back" href="<?php echo esc_url($back); ?>">← Kembali ke Halaman Login</a>
        </main>
    </body>
    </html>
    <?php
    exit;
}

function pa_starter_handle_mfa_gate(): void {
    if (empty($_GET['pa_mfa']) || pa_starter_mfa_disabled()) {
        return;
    }
    $token = sanitize_text_field((string) ($_COOKIE['pa_mfa_chal'] ?? ''));
    $chal  = ($token !== '' && preg_match('/^[a-f0-9]{48}$/', $token)) ? get_transient('pa_mfa_chal_' . $token) : false;
    if (!is_array($chal) || empty($chal['user_id'])) {
        pa_starter_clear_mfa_cookie();
        wp_safe_redirect(wp_login_url());
        exit;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['mfa_code'])) {
        $nonce = sanitize_text_field(wp_unslash((string) ($_POST['pa_mfa_nonce'] ?? '')));
        if (!wp_verify_nonce($nonce, 'pa_mfa_verify_action')) {
            pa_starter_render_mfa_screen('Sesi formulir tidak valid, silakan muat ulang.');
        }
        $uid    = (int) $chal['user_id'];
        $code   = sanitize_text_field(wp_unslash((string) $_POST['mfa_code']));
        $secret = (string) get_user_meta($uid, 'pa_totp_secret', true);
        $valid  = ($secret !== '' && pa_starter_totp_verify($secret, $code, 1, $uid)) || pa_starter_totp_consume_recovery($uid, $code);

        if ($valid) {
            delete_transient('pa_mfa_chal_' . $token);
            pa_starter_clear_mfa_cookie();
            define('PA_MFA_COMPLETING', true);
            update_user_meta($uid, 'pa_last_admin_seen', time());
            wp_set_auth_cookie($uid, !empty($chal['remember']));
            pa_starter_security_log('mfa-login-ok', 'UID ' . $uid);
            wp_safe_redirect(!empty($chal['redirect_to']) ? (string) $chal['redirect_to'] : admin_url());
            exit;
        }

        $tries = (int) ($chal['tries'] ?? 0) + 1;
        if ($tries >= 5) {
            delete_transient('pa_mfa_chal_' . $token);
            pa_starter_clear_mfa_cookie();
            pa_starter_security_log('mfa-failed-lock', 'UID ' . $uid);
            pa_starter_render_mfa_screen('Terlalu banyak kode MFA salah (5x). Silakan login ulang dari awal.');
        }
        $chal['tries'] = $tries;
        set_transient('pa_mfa_chal_' . $token, $chal, 300);
        pa_starter_render_mfa_screen('Kode verifikasi tidak cocok. Sisa percobaan: ' . (5 - $tries) . '.');
    }

    pa_starter_render_mfa_screen();
}

add_action('admin_init', static function () {
    if (PHP_SAPI === 'cli' || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    $uid = get_current_user_id();
    if ($uid <= 0) {
        return;
    }
    $last = (int) get_user_meta($uid, 'pa_last_admin_seen', true);
    if ($last > 0 && (time() - $last) > PA_ADMIN_IDLE_SECONDS) {
        update_user_meta($uid, 'pa_last_admin_seen', 0);
        pa_starter_security_log('session-idle-timeout', 'UID ' . $uid);
        wp_logout();
        wp_safe_redirect(add_query_arg('pa_idle', '1', wp_login_url()));
        exit;
    }
    if ((time() - $last) > 60) {
        update_user_meta($uid, 'pa_last_admin_seen', time());
    }
}, 5);

add_action('admin_notices', static function () {
    if (pa_starter_mfa_disabled() || !function_exists('pa_sinova_get_settings')) {
        return;
    }
    $cfg = pa_sinova_get_settings();
    if (empty($cfg['enforce_mfa']) || (string) $cfg['enforce_mfa'] !== '1') {
        return;
    }
    $uid = get_current_user_id();
    if ($uid > 0 && (current_user_can('manage_options') || current_user_can('pa_access_sinova_hub')) && get_user_meta($uid, 'pa_totp_enabled', true) !== '1') {
        $secUrl = admin_url('admin.php?page=pa-sinova-starter&tab=security');
        echo '<div class="notice notice-warning"><p><strong>Kebijakan Keamanan:</strong> Akun Anda belum mengaktifkan Autentikasi Dua Faktor (MFA TOTP). <a href="' . esc_url($secUrl) . '">Aktifkan MFA sekarang →</a></p></div>';
    }
});

add_filter('login_message', static function ($msg) {
    if (!empty($_GET['pa_idle'])) {
        $msg .= '<div id="login_error"><strong>Sesi Berakhir:</strong> Anda keluar otomatis setelah 60 menit tidak aktif demi keamanan. Silakan masuk kembali.</div>';
    }
    return $msg;
});

function pa_starter_check_password_policy(string $pass): ?string {
    if (strlen($pass) < 10) {
        return 'Password operator wajib minimal 10 karakter.';
    }
    if (!preg_match('/[a-z]/', $pass) || !preg_match('/[A-Z]/', $pass) || !preg_match('/\d/', $pass) || !preg_match('/[^a-zA-Z0-9]/', $pass)) {
        return 'Password operator harus memuat kombinasi huruf kecil, huruf besar, angka, dan simbol.';
    }
    return null;
}

add_action('user_profile_update_errors', static function ($errors, $update, $user) {
    if (empty($_POST['pass1'])) {
        return;
    }
    $role     = is_object($user) && !empty($user->role) ? (string) $user->role : '';
    $isPriv   = in_array($role, array('administrator', 'editor', 'author', 'pa_kepaniteraan', 'pa_kesekretariatan', 'pa_humas'), true)
        || (is_object($user) && !empty($user->ID) && (user_can((int) $user->ID, 'manage_options') || user_can((int) $user->ID, 'edit_pages') || user_can((int) $user->ID, 'edit_posts')));
    if (!$isPriv) {
        return;
    }
    $err = pa_starter_check_password_policy((string) wp_unslash($_POST['pass1']));
    if ($err !== null && $errors instanceof WP_Error) {
        $errors->add('pa_weak_password', '<strong>Keamanan Password:</strong> ' . $err);
    }
}, 10, 3);

add_action('validate_password_reset', static function ($errors, $user) {
    if (empty($_POST['pass1']) || !$user instanceof WP_User) {
        return;
    }
    if (user_can((int) $user->ID, 'manage_options') || user_can((int) $user->ID, 'edit_pages') || user_can((int) $user->ID, 'edit_posts')) {
        $err = pa_starter_check_password_policy((string) wp_unslash($_POST['pass1']));
        if ($err !== null && $errors instanceof WP_Error) {
            $errors->add('pa_weak_password', '<strong>Keamanan Password:</strong> ' . $err);
        }
    }
}, 10, 2);

add_action('init', static function () {
    if (pa_starter_active_login_slug() !== '') {
        remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
    }
}, 20);

add_action('wp_loaded', static function () {
    if (PHP_SAPI === 'cli' || (defined('DOING_CRON') && DOING_CRON)) {
        return;
    }

    pa_starter_handle_mfa_gate();

    $slug = pa_starter_active_login_slug();
    if ($slug === '') {
        return;
    }

    $rel        = pa_starter_rel_request_path();
    $scriptBase = strtolower(basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')));

    if ($rel === $slug) {
        defined('DONOTCACHEPAGE') || define('DONOTCACHEPAGE', true);
        defined('PA_SERVING_CUSTOM_LOGIN') || define('PA_SERVING_CUSTOM_LOGIN', true);
        global $pagenow, $error, $interim_login, $action, $user_login, $errors, $redirect_to;
        $pagenow = 'wp-login.php';
        if (!headers_sent()) {
            nocache_headers();
            status_header(200);
        }
        require_once ABSPATH . 'wp-login.php';
        exit;
    }

    if (!defined('PA_SERVING_CUSTOM_LOGIN') && ($scriptBase === 'wp-login.php' || $rel === 'wp-login.php')) {
        $reqAction = sanitize_key((string) ($_REQUEST['action'] ?? ''));
        if (!in_array($reqAction, array('postpass', 'confirmaction'), true)) {
            pa_starter_security_log('hidden-login-probe', (string) ($_SERVER['REQUEST_URI'] ?? '/wp-login.php'));
            pa_starter_send_stealth_404();
        }
    }

    if (!is_user_logged_in()) {
        $allowedAdminScripts = array('admin-ajax.php', 'admin-post.php', 'install.php', 'upgrade.php');
        $isWpAdminReq        = ((function_exists('is_admin') && is_admin()) || $rel === 'wp-admin' || strpos($rel, 'wp-admin/') === 0);
        if ($isWpAdminReq && !wp_doing_ajax() && !in_array($scriptBase, $allowedAdminScripts, true)) {
            pa_starter_security_log('hidden-admin-probe', (string) ($_SERVER['REQUEST_URI'] ?? '/wp-admin/'));
            pa_starter_send_stealth_404();
        }
    }
}, 1);

add_filter('site_url', static function ($url, $path, $scheme) {
    $slug = pa_starter_active_login_slug();
    return $slug !== '' ? pa_starter_rewrite_login_url((string) $url, $slug, is_string($scheme) ? $scheme : null) : $url;
}, 10, 3);

add_filter('network_site_url', static function ($url, $path, $scheme) {
    $slug = pa_starter_active_login_slug();
    return $slug !== '' ? pa_starter_rewrite_login_url((string) $url, $slug, is_string($scheme) ? $scheme : null) : $url;
}, 10, 3);

add_filter('wp_redirect', static function ($location) {
    $slug = pa_starter_active_login_slug();
    return $slug !== '' ? pa_starter_rewrite_login_url((string) $location, $slug) : $location;
}, 10, 1);
