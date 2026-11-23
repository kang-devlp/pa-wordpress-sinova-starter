<?php
/**
 * Plugin Name: PA SINOVA Starter - Security Hardening, Anti-Judol WAF, Rate Limiter & Dual-Mode CAPTCHA
 * Description: Pengamanan berlapis portal Pengadilan Agama: Brute-Force Rate Limiter (5x gagal/15 menit), OWASP Headers & HSTS, pelindung folder unggahan (.htaccess), blokir XML-RPC/enumerasi user, Anti-Judol WAF, serta Dual-Mode Login CAPTCHA (Turnstile / Math Fallback + Honeypot).
 * Version: 1.2.0-starter
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
    $headers['X-Content-Type-Options'] = 'nosniff';
    $headers['X-Frame-Options']        = 'SAMEORIGIN';
    $headers['Referrer-Policy']        = 'strict-origin-when-cross-origin';
    $headers['Permissions-Policy']     = 'camera=(), microphone=(), geolocation=(), payment=()';
    if (function_exists('is_ssl') && is_ssl()) {
        $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
    }
    return $headers;
});

add_filter('rest_pre_dispatch', static function ($result, $server, $request) {
    if (!is_user_logged_in() && $request instanceof WP_REST_Request) {
        $route = (string) $request->get_route();
        if ($route === '/wp/v2/users' || strpos($route, '/wp/v2/users/') === 0) {
            return new WP_Error('rest_cannot_list_users', 'Akses daftar pengguna dibatasi.', array('status' => 403));
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

add_action('wp_login', static function () {
    $ipHash = pa_starter_client_ip_hash();
    delete_transient('pa_bf_fail_' . $ipHash);
    delete_transient('pa_bf_lock_' . $ipHash);
});
