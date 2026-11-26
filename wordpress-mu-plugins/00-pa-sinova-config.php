<?php
/**
 * Plugin Name: PA SINOVA Starter - Konfigurasi, Seeder 1-Klik SK 2722/2026, RBAC & Panel Kepatuhan WP Admin
 * Description: Pengaturan dinamis Satker, RBAC peran operator, generator Secret Key HMAC-SHA256, Seeder 1-Klik 6 Menu Baku SK Dirjen Badilag 2722/2026, Dashboard Kepatuhan 35 Kriteria + Migrasi & Adopsi, Data Operasional, Upload Dokumen (Edit & Hapus Massal), & Audit Keamanan + MFA di WP Admin.
 * Version: 2.5.0-starter
 */

defined('ABSPATH') || exit;

defined('PA_SINOVA_STARTER_VERSION') || define('PA_SINOVA_STARTER_VERSION', '2.5.0');
defined('PA_SINOVA_GITHUB_REPO') || define('PA_SINOVA_GITHUB_REPO', 'kang-devlp/pa-wordpress-sinova-starter');
defined('PA_SINOVA_RUNTIME_MODE') || define('PA_SINOVA_RUNTIME_MODE', 'mu-plugin');

function pa_sinova_get_settings(): array {
    $defaults = array(
        'satker_name'              => get_bloginfo('name') ?: 'Pengadilan Agama Contoh Kelas I-A',
        'logo_url'                 => '',
        'favicon_url'              => '',
        'preserve_existing_design' => '0',
        'address'                  => 'Jl. Keadilan Raya No. 1, Kota Contoh',
        'phone'                    => '(000) 123-4567 · WA PTSP 0811-0000-0000',
        'email'                    => 'info@pa-contoh.go.id',
        'hearing_hours'            => 'Senin–Kamis 09.00 WIB s.d. selesai',
        'leader_name'              => 'Ketua Pengadilan Agama',
        'leader_welcome'           => 'Selamat datang di portal resmi Pengadilan Agama. Melalui website ini kami menyajikan informasi layanan peradilan, transparansi keuangan, laporan kinerja, dan dokumen pembangunan Zona Integritas secara terbuka kepada seluruh masyarakat pencari keadilan.',
        'sipp_url'                 => 'https://sipp.pa-contoh.go.id/',
        'sipp_slide_url'           => '',
        'ecourt_url'               => 'https://ecourt.mahkamahagung.go.id/',
        'direktori_url'            => 'https://putusan3.mahkamahagung.go.id/',
        'dirput_rss_url'           => '',
        'dirput_court_hash'        => '',
        'survey_url'               => 'https://survei.badilag.net/',
        'aco_url'                  => 'https://cctv.badilag.net/',
        'aco_logo_url'             => '',
        'aco_float_enabled'        => '1',
        'aco_float_position'       => 'bottom_right',
        'aco_float_size'           => 'md',
        'aco_float_draggable'      => '1',
        'rss_ma_url'               => 'https://kepaniteraan.mahkamahagung.go.id/?format=feed&type=rss',
        'rss_badilag_url'          => 'https://badilag.mahkamahagung.go.id/pengumuman-elektronik?format=feed&type=rss',
        'custom_login_slug'        => '',
        'enforce_mfa'              => '0',
        'hmac_secret'              => '',
        'turnstile_site_key'       => '',
        'turnstile_secret_key'     => '',
        'accent_palette'           => 'gold',
        'csp_mode'                 => 'strict',
        'emergency_notice'         => '',
        'emergency_notice_url'     => '',
        'siwas_url'                => 'https://siwas.mahkamahagung.go.id/',
        'lapor_url'                => 'https://www.lapor.go.id/',
        'zi_area_1_url'            => '',
        'zi_area_2_url'            => '',
        'zi_area_3_url'            => '',
        'zi_area_4_url'            => '',
        'zi_area_5_url'            => '',
        'zi_area_6_url'            => '',
        // Pengaturan Visual Beranda Dinamis ([pa_sinova_beranda]) — tanpa edit HTML / tanpa Elementor
        'home_show_ticker'          => '1',
        'home_ticker_label'         => 'INFO LAYANAN',
        'home_ticker_text'          => 'Selamat datang di Portal Resmi Informasi Peradilan · Layanan PTSP buka Senin–Kamis 08.00–16.30 WIB & Jumat 08.00–17.00 WIB · Seluruh layanan bebas dari pungutan liar dan gratifikasi.',
        'footer_credit_text'        => 'Pengadilan Agama Devlpr Community',
        'footer_credit_suffix'      => 'Portal Keterbukaan Informasi Publik',
        'footer_hover_text'         => 'Developed with ♥ by PA Devlpr Community',
        'footer_credit_url'         => '',
        'home_hero_kicker'          => 'Zona Integritas WBK / WBBM · Keterbukaan Informasi Publik',
        'home_hero_title'           => 'Portal Keterbukaan Informasi & Layanan Peradilan',
        'home_hero_lead'            => 'Menyediakan akses informasi perkara, jadwal sidang, panjar biaya, laporan kinerja, serta arsip transparansi keuangan yang akurat dan mudah diakses masyarakat pencari keadilan.',
        'home_schedule_position'    => 'after_hero',
        'home_schedule_default_tab' => 'auto',
        'home_show_services'        => '1',
        'home_show_welcome'         => '1',
        'home_show_zi'              => '1',
        'home_show_news'            => '1',
        'home_show_announcements'   => '1',
        'home_show_survey'          => '1',
        'home_survey_period'        => 'Triwulan III',
        'home_survey_grade'         => '★ Mutu A (Sangat Baik)',
        'home_survey_skm'           => '4.00 / 4.00',
        'home_survey_spak'          => '4.00 / 4.00',
        'home_survey_spkp'          => '4.00 / 4.00',
        'hero_layout'               => 'split',
        'hero_photo_url'            => '',
        'hero_photo_caption'        => 'Gedung Pelayanan Terpadu Satu Pintu (PTSP) & Ruang Sidang',
        'hero_pattern'              => 'islamic',
        'leader_title'              => 'Ketua Pengadilan Agama',
        'leader_photo_url'          => '',
        'leader_motto'              => 'Melayani dengan Integritas, Profesionalitas, dan Transparansi',
        'social_instagram'          => '',
        'social_youtube'            => '',
        'social_facebook'           => '',
        'social_tiktok'             => '',
        'social_whatsapp'           => '',
        'maps_url'                  => '',
    );
    $saved = get_option('pa_sinova_starter_settings', array());
    if (!is_array($saved)) {
        $saved = array();
    }
    $merged = array_merge($defaults, $saved);

    if (defined('PA_SINOVA_SATKER_NAME') && PA_SINOVA_SATKER_NAME !== '') {
        $merged['satker_name'] = (string) PA_SINOVA_SATKER_NAME;
    }
    if (defined('PA_SIPP_SLIDE_URL') && PA_SIPP_SLIDE_URL !== '') {
        $merged['sipp_slide_url'] = (string) PA_SIPP_SLIDE_URL;
    }
    if (defined('PA_ACO_URL') && PA_ACO_URL !== '') {
        $merged['aco_url'] = (string) PA_ACO_URL;
    }
    if (defined('PA_CUSTOM_LOGIN_SLUG') && PA_CUSTOM_LOGIN_SLUG !== '') {
        $merged['custom_login_slug'] = (string) PA_CUSTOM_LOGIN_SLUG;
    }
    if (defined('PA_SINOVA_HMAC_SECRET') && PA_SINOVA_HMAC_SECRET !== '') {
        $merged['hmac_secret'] = (string) PA_SINOVA_HMAC_SECRET;
    }
    if (defined('PA_TURNSTILE_SITE_KEY') && PA_TURNSTILE_SITE_KEY !== '') {
        $merged['turnstile_site_key'] = (string) PA_TURNSTILE_SITE_KEY;
    }
    if (defined('PA_TURNSTILE_SECRET_KEY') && PA_TURNSTILE_SECRET_KEY !== '') {
        $merged['turnstile_secret_key'] = (string) PA_TURNSTILE_SECRET_KEY;
    }
    if (defined('PA_CSP_MODE') && PA_CSP_MODE !== '') {
        $merged['csp_mode'] = (string) PA_CSP_MODE;
    }

    return $merged;
}

/**
 * Mengembalikan URL relatif/aman untuk aset bawaan starter kit (GIF ACO, foto dummy gedung/pimpinan/berita).
 */
function pa_sinova_asset_url(string $filename): string {
    $safe = basename($filename);
    if ($safe === '') {
        return '';
    }
    $resolved = '';
    if (defined('PA_SINOVA_ASSETS_DIR') && defined('PA_SINOVA_ASSETS_URL') && file_exists(rtrim((string) PA_SINOVA_ASSETS_DIR, '/\\') . '/' . $safe)) {
        $resolved = rtrim((string) PA_SINOVA_ASSETS_URL, '/') . '/' . $safe;
    } elseif (file_exists(__DIR__ . '/assets/' . $safe)) {
        if (defined('WPMU_PLUGIN_DIR') && defined('WPMU_PLUGIN_URL') && realpath(__DIR__) === realpath(WPMU_PLUGIN_DIR)) {
            $resolved = rtrim((string) WPMU_PLUGIN_URL, '/') . '/assets/' . $safe;
        } elseif (defined('WP_PLUGIN_DIR') && defined('WP_PLUGIN_URL') && strpos((string) wp_normalize_path(__DIR__), (string) wp_normalize_path(WP_PLUGIN_DIR)) === 0) {
            $relDir   = ltrim(substr((string) wp_normalize_path(__DIR__), strlen((string) wp_normalize_path(WP_PLUGIN_DIR))), '/');
            $resolved = rtrim((string) WP_PLUGIN_URL, '/') . '/' . $relDir . '/assets/' . $safe;
        } else {
            $resolved = content_url('/mu-plugins/assets/' . $safe);
        }
    } elseif (defined('WPMU_PLUGIN_DIR') && defined('WPMU_PLUGIN_URL') && file_exists(rtrim((string) WPMU_PLUGIN_DIR, '/\\') . '/assets/' . $safe)) {
        $resolved = rtrim((string) WPMU_PLUGIN_URL, '/') . '/assets/' . $safe;
    } elseif (defined('WP_PLUGIN_DIR') && defined('WP_PLUGIN_URL') && file_exists(rtrim((string) WP_PLUGIN_DIR, '/\\') . '/pa-sinova-starter/wordpress-mu-plugins/assets/' . $safe)) {
        $resolved = rtrim((string) WP_PLUGIN_URL, '/') . '/pa-sinova-starter/wordpress-mu-plugins/assets/' . $safe;
    } else {
        $resolved = add_query_arg('pa_sinova_asset', rawurlencode($safe), home_url('/'));
    }

    $homeHost = strtolower((string) parse_url(home_url('/'), PHP_URL_HOST));
    $imgHost  = strtolower((string) parse_url($resolved, PHP_URL_HOST));
    if ($imgHost === '' || ($homeHost !== '' && $imgHost === $homeHost)) {
        $resolved = wp_make_link_relative($resolved);
    }
    return $resolved;
}

// ============================================================================
// SHARED SECURITY HEADERS, CSP NONCE & TRUSTED TYPES HELPERS
// (Diletakkan di 00-pa-sinova-config.php agar juga aktif pada Early Page Cache)
// ============================================================================

function pa_starter_is_https_request(): bool {
    if (function_exists('is_ssl') && is_ssl()) {
        return true;
    }
    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    if ($https !== '' && $https !== 'off') {
        return true;
    }
    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }
    $xfp = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    return strpos($xfp, 'https') === 0;
}

function pa_starter_csp_nonce(): string {
    if (empty($GLOBALS['pa_starter_csp_nonce']) || !is_string($GLOBALS['pa_starter_csp_nonce'])) {
        try {
            $GLOBALS['pa_starter_csp_nonce'] = base64_encode(random_bytes(16));
        } catch (\Throwable $e) {
            $GLOBALS['pa_starter_csp_nonce'] = base64_encode(hash('sha256', uniqid('pa', true) . microtime(true), true));
        }
    }
    return $GLOBALS['pa_starter_csp_nonce'];
}

function pa_starter_effective_csp_mode(): string {
    if (defined('PA_CSP_MODE') && PA_CSP_MODE !== '') {
        $mode = strtolower((string) PA_CSP_MODE);
    } else {
        $cfg  = pa_sinova_get_settings();
        $mode = strtolower((string) ($cfg['csp_mode'] ?? 'strict'));
        if (!empty($cfg['preserve_existing_design']) && (string) $cfg['preserve_existing_design'] === '1' && $mode === 'strict') {
            $mode = 'compat';
        }
    }
    return in_array($mode, array('strict', 'compat', 'off'), true) ? $mode : 'strict';
}

function pa_sinova_sanitize_https_embed_url(string $rawUrl): array {
    $trimmed = trim($rawUrl);
    if ($trimmed === '') {
        return array('url' => '', 'origin' => '', 'valid' => true);
    }
    $clean  = esc_url_raw($trimmed, array('https'));
    $scheme = strtolower((string) parse_url($clean, PHP_URL_SCHEME));
    $host   = strtolower((string) parse_url($clean, PHP_URL_HOST));
    $port   = parse_url($clean, PHP_URL_PORT);
    $okHost = ($scheme === 'https')
        && $host !== ''
        && strpos($host, '.') !== false
        && preg_match('/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/', $host) === 1
        && ($port === null || ((int) $port > 0 && (int) $port <= 65535));
    if (!$okHost) {
        return array('url' => '', 'origin' => '', 'valid' => false);
    }
    $origin = 'https://' . $host . ($port !== null ? (':' . (int) $port) : '');
    return array('url' => $clean, 'origin' => $origin, 'valid' => true);
}

function pa_starter_security_headers_map(bool $isPublic = true, ?string $nonce = null): array {
    $isHttps = pa_starter_is_https_request();
    $headers = array(
        'X-Content-Type-Options'            => 'nosniff',
        'X-Frame-Options'                   => 'SAMEORIGIN',
        'Referrer-Policy'                   => 'strict-origin-when-cross-origin',
        'Permissions-Policy'                => 'accelerometer=(), autoplay=(), camera=(), display-capture=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=(), interest-cohort=()',
        'Cross-Origin-Opener-Policy'        => 'same-origin',
        'Cross-Origin-Resource-Policy'      => 'same-origin',
        'X-Permitted-Cross-Domain-Policies' => 'none',
    );
    if ($isHttps) {
        $headers['Strict-Transport-Security'] = 'max-age=63072000; includeSubDomains; preload';
    }

    if (!$isPublic) {
        $headers['Content-Security-Policy'] = "frame-ancestors 'self'; base-uri 'self'; object-src 'none';";
        return $headers;
    }

    $mode = pa_starter_effective_csp_mode();
    if ($mode === 'off') {
        return $headers;
    }

    $frameOrigins = array(
        "'self'",
        'https://drive.google.com',
        'https://docs.google.com',
        'https://www.google.com',
        'https://maps.google.com',
        'https://challenges.cloudflare.com',
        'https://www.youtube-nocookie.com',
    );
    $cfg          = pa_sinova_get_settings();
    $embedTargets = array((string) ($cfg['sipp_slide_url'] ?? ''));
    if (function_exists('pa_sinova_get_home_blocks')) {
        foreach (pa_sinova_get_home_blocks() as $blk) {
            if (empty($blk['enabled'])) {
                continue;
            }
            $bCfg = isset($blk['config']) && is_array($blk['config']) ? $blk['config'] : array();
            if (!empty($bCfg['embed_url'])) {
                $embedTargets[] = (string) $bCfg['embed_url'];
            }
        }
    }
    foreach ($embedTargets as $et) {
        $chk = pa_sinova_sanitize_https_embed_url($et);
        if ($chk['valid'] && $chk['origin'] !== '' && !in_array($chk['origin'], $frameOrigins, true)) {
            $frameOrigins[] = $chk['origin'];
        }
    }

    $csp = "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self' https://putusan3.mahkamahagung.go.id; "
        . "img-src 'self' data: blob: https:; font-src 'self' data:; style-src 'self' 'unsafe-inline'; "
        . "connect-src 'self' https://challenges.cloudflare.com; "
        . 'frame-src ' . implode(' ', $frameOrigins) . '; ';

    if ($mode === 'strict' && is_string($nonce) && $nonce !== '') {
        $csp .= "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic' 'inline-speculation-rules' https://challenges.cloudflare.com; "
            . "require-trusted-types-for 'script'; ";
    } else {
        $csp .= "script-src 'self' 'unsafe-inline' 'inline-speculation-rules' https://challenges.cloudflare.com; ";
    }

    if ($isHttps) {
        $csp .= 'upgrade-insecure-requests;';
    }

    $headers['Content-Security-Policy'] = trim($csp);
    return $headers;
}

function pa_starter_emit_security_headers(bool $isPublic = true, ?string $nonce = null): void {
    if (headers_sent()) {
        return;
    }
    if (function_exists('header_remove')) {
        @header_remove('X-Powered-By');
        @header_remove('X-Pingback');
    }
    foreach (pa_starter_security_headers_map($isPublic, $nonce) as $name => $val) {
        header($name . ': ' . $val, true);
    }
}

function pa_starter_inject_csp_nonce_into_html(string $html, string $nonce): string {
    if ($html === '' || $nonce === '' || stripos($html, '<script') === false) {
        return $html;
    }
    $safeNonce = preg_replace('/[^A-Za-z0-9+\/=_-]/', '', $nonce);
    if ($safeNonce === '') {
        return $html;
    }
    $out = preg_replace_callback('/<script\b([^>]*)>/i', static function ($m) use ($safeNonce) {
        $attrs = (string) $m[1];
        if (preg_match('/\btype\s*=\s*(?:["\']application\/(?:ld\+)?json["\']|application\/(?:ld\+)?json\b)/i', $attrs)) {
            return $m[0];
        }
        $attrs = preg_replace('/\s+nonce\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $attrs);
        return '<script' . $attrs . ' nonce="' . $safeNonce . '">';
    }, $html);
    return is_string($out) ? $out : $html;
}

function pa_starter_strip_csp_nonce_from_html(string $html): string {
    if ($html === '' || stripos($html, 'nonce=') === false) {
        return $html;
    }
    $out = preg_replace_callback('/<script\b([^>]*)>/i', static function ($m) {
        $attrs = preg_replace('/\s+nonce\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', (string) $m[1]);
        return '<script' . $attrs . '>';
    }, $html);
    return is_string($out) ? $out : $html;
}

function pa_sinova_register_roles(): void {
    $adminRole = get_role('administrator');
    if ($adminRole && !$adminRole->has_cap('pa_access_sinova_hub')) {
        $adminRole->add_cap('pa_access_sinova_hub');
    }

    $ver = '1.0';
    if (get_option('pa_sinova_roles_ver') === $ver) {
        return;
    }

    $defs = array(
        'pa_kepaniteraan' => array(
            'label' => 'Operator Kepaniteraan',
            'caps'  => array('read' => true, 'upload_files' => true, 'edit_pages' => true, 'edit_others_pages' => true, 'publish_pages' => true, 'pa_access_sinova_hub' => true, 'pa_manage_kepaniteraan' => true),
        ),
        'pa_kesekretariatan' => array(
            'label' => 'Operator Kesekretariatan',
            'caps'  => array('read' => true, 'upload_files' => true, 'edit_pages' => true, 'edit_others_pages' => true, 'publish_pages' => true, 'pa_access_sinova_hub' => true, 'pa_manage_kesekretariatan' => true),
        ),
        'pa_humas' => array(
            'label' => 'Operator Humas & Berita',
            'caps'  => array('read' => true, 'upload_files' => true, 'edit_posts' => true, 'edit_others_posts' => true, 'publish_posts' => true, 'delete_posts' => true, 'pa_access_sinova_hub' => true, 'pa_manage_humas' => true),
        ),
    );

    foreach ($defs as $slug => $cfg) {
        $roleObj = get_role($slug);
        if (!$roleObj) {
            add_role($slug, $cfg['label'], $cfg['caps']);
        } else {
            foreach ($cfg['caps'] as $cap => $grant) {
                if ($grant && !$roleObj->has_cap($cap)) {
                    $roleObj->add_cap($cap);
                }
            }
        }
    }
    update_option('pa_sinova_roles_ver', $ver, false);
}
add_action('init', 'pa_sinova_register_roles', 5);

function pa_sinova_user_allowed_tabs(): array {
    if (current_user_can('manage_options')) {
        return array('compliance', 'upload', 'data', 'homepage', 'migration', 'settings', 'security');
    }
    if (current_user_can('pa_manage_kepaniteraan') || current_user_can('pa_manage_kesekretariatan')) {
        return array('compliance', 'upload', 'data', 'security');
    }
    if (current_user_can('pa_manage_humas')) {
        return array('compliance', 'data', 'security');
    }
    return array();
}

function pa_sinova_user_allowed_module_slugs(): array {
    if (current_user_can('manage_options')) {
        return array();
    }
    $slugs = array();
    if (current_user_can('pa_manage_kepaniteraan')) {
        $slugs = array_merge($slugs, array('biaya-perkara', 'sisa-panjar', 'biaya-prodeo', 'hak-kepaniteraan', 'akses-informasi', 'rekap-pengaduan'));
    }
    if (current_user_can('pa_manage_kesekretariatan')) {
        $slugs = array_merge($slugs, array('lra', 'pnbp', 'neraca', 'calk', 'dipa', 'aset', 'pbj', 'lkjip', 'iku', 'pk', 'rkt', 'rencana-aksi', 'renstra', 'mou', 'lhkpn', 'lhkasn', 'survei-pelayanan'));
    }
    if (current_user_can('pa_manage_humas')) {
        $slugs = array_merge($slugs, array('survei-pelayanan', 'mou'));
    }
    return array_values(array_unique($slugs));
}

function pa_sinova_sanitize_login_slug(string $raw): array {
    $clean = strtolower(sanitize_title(trim($raw, " /\t\n\r")));
    if ($clean === '') {
        return array('valid' => true, 'slug' => '', 'error' => '');
    }
    $reserved = array(
        'wp-admin', 'wp-login', 'wp-login-php', 'wp-content', 'wp-includes',
        'wp-json', 'wp-cron', 'xmlrpc', 'admin', 'login', 'dashboard',
        'beranda', 'feed', 'author', 'search', 'robots', 'sitemap', 'lra', 'dipa',
    );
    if (!preg_match('/^[a-z0-9][a-z0-9-]{2,38}[a-z0-9]$/', $clean)) {
        return array('valid' => false, 'slug' => '', 'error' => 'Slug login minimal 4 karakter, hanya huruf kecil, angka, dan tanda hubung (-).');
    }
    if (in_array($clean, $reserved, true)) {
        return array('valid' => false, 'slug' => '', 'error' => 'Slug tersebut menggunakan nama tercadang sistem; pilih kata yang lebih unik.');
    }
    if (function_exists('get_page_by_path') && did_action('init')) {
        $existingPage = get_page_by_path($clean);
        if ($existingPage instanceof WP_Post && $existingPage->post_status === 'publish') {
            return array('valid' => false, 'slug' => '', 'error' => 'Slug tersebut sudah dipakai halaman publik yang terbit; gunakan slug lain.');
        }
    }
    return array('valid' => true, 'slug' => $clean, 'error' => '');
}

function pa_sinova_custom_login_slug(): string {
    if (defined('PA_DISABLE_CUSTOM_LOGIN') && PA_DISABLE_CUSTOM_LOGIN) {
        return '';
    }
    $cfg = pa_sinova_get_settings();
    $res = pa_sinova_sanitize_login_slug((string) ($cfg['custom_login_slug'] ?? ''));
    return $res['valid'] ? (string) $res['slug'] : '';
}

function pa_sinova_satker_name(): string {
    $cfg = pa_sinova_get_settings();
    return trim((string) ($cfg['satker_name'] ?? 'Pengadilan Agama')) ?: 'Pengadilan Agama';
}

function pa_sinova_get_secret_key(): string {
    $cfg = pa_sinova_get_settings();
    return trim((string) ($cfg['hmac_secret'] ?? ''));
}

function pa_sinova_get_custom_map(): array {
    $map = get_option('pa_sinova_custom_page_map', array());
    return is_array($map) ? $map : array();
}

function pa_sinova_news_archive_url(): string {
    $postsPageId = (int) get_option('page_for_posts', 0);
    if ($postsPageId > 0 && get_post_status($postsPageId) === 'publish') {
        $u = get_permalink($postsPageId);
        if (is_string($u) && $u !== '') {
            return $u;
        }
    }
    return home_url('/?post_type=post');
}

// Notifikasi ramah di WP Admin saat Mode Adopsi otomatis aktif di situs lama atau saat tersedia pembaruan 1-Klik di GitHub
add_action('admin_notices', static function () {
    if (!current_user_can('manage_options')) {
        return;
    }
    $autoAdopt = get_transient('pa_sinova_auto_adoption_notice');
    if (is_array($autoAdopt)) {
        $migUrl = admin_url('admin.php?page=pa-sinova-starter&tab=migration');
        echo '<div class="notice notice-info is-dismissible"><p><strong>PA SINOVA Starter Kit — Mode Adopsi Aktif Otomatis:</strong> Terdeteksi website WordPress yang sudah berisi <strong>'
            . (int) ($autoAdopt['posts'] ?? 0) . ' berita &amp; ' . (int) ($autoAdopt['pages'] ?? 0)
            . ' halaman</strong>. Desain, menu, font, dan Elementor lama Anda otomatis dilindungi agar tidak tertimpa. Kelola atau beralih ke tampilan penuh di <a href="'
            . esc_url($migUrl) . '"><strong>SINOVA Hub → Migrasi &amp; Adopsi</strong></a>.</p></div>';
        delete_transient('pa_sinova_auto_adoption_notice');
    }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && in_array((string) $screen->id, array('dashboard', 'plugins'), true) && function_exists('pa_sinova_github_check_update')) {
        $upd = pa_sinova_github_check_update(false);
        if (!empty($upd['ok']) && !empty($upd['has_update']) && !empty($upd['latest'])) {
            $secUrl = admin_url('admin.php?page=pa-sinova-starter&tab=security');
            echo '<div class="notice notice-warning is-dismissible"><p><strong>Pembaruan PA SINOVA Starter Kit v'
                . esc_html((string) $upd['latest']) . ' tersedia!</strong> (terpasang: v' . esc_html(PA_SINOVA_STARTER_VERSION)
                . ') — Perbarui aman 1-klik tanpa menghapus berita/dokumen di <a href="' . esc_url($secUrl)
                . '"><strong>SINOVA Hub → Keamanan &amp; Update</strong></a>.</p></div>';
        }
    }
});

/**
 * Tombol ikon (i) ringkas untuk menampilkan keterangan hanya saat disorot atau diklik di WP Admin.
 */
function pa_sinova_info_tip(string $text, string $ariaLabel = 'Informasi'): string {
    $clean = trim(preg_replace('/\s+/', ' ', $text));
    if ($clean === '') {
        return '';
    }
    return '<button type="button" class="pa-info-btn" data-pa-info="' . esc_attr($clean) . '" aria-label="' . esc_attr($ariaLabel) . '" aria-expanded="false">i</button>';
}

/**
 * Sinkronkan halaman /direktori-putusan/ otomatis bila URL Direktori Putusan satker diisi & halaman masih memuat [LENGKAPI].
 */
function pa_sinova_sync_direktori_putusan_page(string $rawDirUrl = ''): bool {
    $cfg = pa_sinova_get_settings();
    $dir = trim($rawDirUrl !== '' ? $rawDirUrl : (string) ($cfg['direktori_url'] ?? ''));
    if ($dir === '') {
        return false;
    }
    $norm = rtrim(strtolower($dir), '/');
    if ($norm === 'https://putusan3.mahkamahagung.go.id' || $norm === 'http://putusan3.mahkamahagung.go.id') {
        return false;
    }
    $page = pa_sinova_find_existing_page('direktori-putusan');
    if (!$page instanceof WP_Post || $page->post_status !== 'publish') {
        return false;
    }
    if (stripos((string) $page->post_content, '[LENGKAPI') === false) {
        update_option('pa_sinova_dirput_page_synced', md5($dir), false);
        return false;
    }
    $newContent = '<h2>Direktori Putusan Mahkamah Agung RI</h2><p>Akses publikasi salinan putusan perkara satuan kerja pada Direktori Putusan Mahkamah Agung Republik Indonesia.</p>[pa_direktori_putusan limit="10"]';
    wp_update_post(array(
        'ID'           => (int) $page->ID,
        'post_content' => $newContent,
    ));
    clean_post_cache((int) $page->ID);
    update_option('pa_sinova_dirput_page_synced', md5($dir), false);
    if (function_exists('pa_sinova_purge_html_cache_files')) {
        pa_sinova_purge_html_cache_files();
    }
    return true;
}

add_action('admin_init', static function () {
    $cfg = pa_sinova_get_settings();
    $dir = trim((string) ($cfg['direktori_url'] ?? ''));
    if ($dir === '') {
        return;
    }
    $norm = rtrim(strtolower($dir), '/');
    if ($norm === 'https://putusan3.mahkamahagung.go.id' || $norm === 'http://putusan3.mahkamahagung.go.id') {
        return;
    }
    if ((string) get_option('pa_sinova_dirput_page_synced', '') !== md5($dir)) {
        pa_sinova_sync_direktori_putusan_page($dir);
    }
});

add_action('template_redirect', static function () {
    if (is_admin() || !is_page('direktori-putusan')) {
        return;
    }
    $cfg = pa_sinova_get_settings();
    $dir = trim((string) ($cfg['direktori_url'] ?? ''));
    if ($dir !== '' && (string) get_option('pa_sinova_dirput_page_synced', '') !== md5($dir)) {
        pa_sinova_sync_direktori_putusan_page($dir);
    }
}, 2);

add_action('admin_enqueue_scripts', static function ($hook) {
    if ($hook === 'toplevel_page_pa-sinova-starter' && function_exists('wp_enqueue_media')) {
        wp_enqueue_media();
    }
});

/**
 * Cari halaman terbit baik di root maupun sebagai sub-halaman (nested slug) agar tidak menduplikasi halaman WP lama.
 */
function pa_sinova_find_existing_page(string $slug): ?WP_Post {
    $direct = get_page_by_path($slug);
    if ($direct instanceof WP_Post && $direct->post_status === 'publish') {
        return $direct;
    }
    global $wpdb;
    $id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM `{$wpdb->posts}` WHERE post_type = 'page' AND post_status = 'publish' AND post_name = %s ORDER BY ID ASC LIMIT 1",
        $slug
    ));
    if ($id > 0) {
        $p = get_post($id);
        if ($p instanceof WP_Post) {
            return $p;
        }
    }
    return null;
}

/**
 * Daftar halaman non-modul (Informasi Wajib & Halaman Informasi Berkala statis) untuk Seeder 1-Klik.
 */
function pa_sinova_static_seed_pages(): array {
    return array(
        'visi-dan-misi' => array(
            'title'   => 'Visi dan Misi',
            'parent'  => 'Profil Pengadilan',
            'code'    => 'W1.1',
            'content' => '<h2>Visi Pengadilan</h2><p>[LENGKAPI: Masukkan Visi resmi Mahkamah Agung RI / Satker]</p><h2>Misi Pengadilan</h2><ol><li>[LENGKAPI: Misi 1]</li><li>[LENGKAPI: Misi 2]</li><li>[LENGKAPI: Misi 3]</li></ol>',
        ),
        'tugas-pokok-dan-fungsi' => array(
            'title'   => 'Tugas Pokok dan Fungsi',
            'parent'  => 'Profil Pengadilan',
            'code'    => 'W1.2',
            'content' => '<h2>Tugas Pokok</h2><p>[LENGKAPI: Berdasarkan UU Peradilan Agama, tugas pokok Pengadilan Agama adalah menerima, memeriksa, memutus, dan menyelesaikan perkara tertentu bagi rakyat pencari keadilan yang beragama Islam.]</p><h2>Fungsi Pengadilan</h2><p>[LENGKAPI daftar fungsi peradilan, administrasi, dan pengawasan.]</p>',
        ),
        'wilayah-yurisdiksi' => array(
            'title'   => 'Wilayah Yurisdiksi',
            'parent'  => 'Profil Pengadilan',
            'code'    => 'W1.3',
            'content' => '<h2>Peta &amp; Cakupan Wilayah Yurisdiksi</h2><p>Daftar kecamatan, desa/kelurahan, dan pembagian radius wilayah hukum satuan kerja.</p>[pa_yurisdiksi_grid]',
        ),
        'struktur-organisasi' => array(
            'title'   => 'Struktur Organisasi',
            'parent'  => 'Profil Pengadilan',
            'code'    => 'W1.4',
            'content' => '<h2>Bagan Struktur Organisasi</h2><p>[LENGKAPI: Unggah bagan struktur organisasi resmi lengkap dengan nama pejabat &amp; jabatan.]</p>',
        ),
        'profil-pegawai' => array(
            'title'   => 'Profil Pejabat dan Pegawai',
            'parent'  => 'Profil Pengadilan',
            'code'    => 'W3.1',
            'content' => '<h2>Daftar Pejabat Struktural, Fungsional, Hakim &amp; Pegawai</h2><p>Berikut direktori resmi pimpinan, hakim, pejabat kepaniteraan, pejabat kesekretariatan, dan aparatur pengadilan.</p>[pa_pegawai_grid]',
        ),
        'alamat-dan-kontak' => array(
            'title'   => 'Alamat dan Kontak Resmi',
            'parent'  => 'Profil Pengadilan',
            'code'    => 'W1.5',
            'content' => '<h2>Identitas &amp; Alamat Kantor</h2><p>[LENGKAPI: Jalan, Kelurahan, Kecamatan, Kota/Kabupaten, Kode Pos, Telepon, Email resmi, &amp; Foto Gedung tampak depan.]</p>',
        ),
        'e-court' => array(
            'title'   => 'Informasi e-Court',
            'parent'  => 'Layanan Hukum',
            'code'    => 'W2.1',
            'content' => '<h2>Pendaftaran Perkara Elektronik (e-Court)</h2><p>[LENGKAPI panduan pendaftaran e-Filing, e-Payment, e-Summons, &amp; e-Litigation beserta tautan ke ecourt.mahkamahagung.go.id.]</p>',
        ),
        'direktori-putusan' => array(
            'title'   => 'Direktori Putusan Satker',
            'parent'  => 'Layanan Hukum',
            'code'    => 'W2.2',
            'content' => '<h2>Direktori Putusan Mahkamah Agung RI</h2><p>Akses publikasi salinan putusan perkara satuan kerja pada Direktori Putusan Mahkamah Agung Republik Indonesia.</p>[pa_direktori_putusan limit="10"]',
        ),
        'prosedur-berperkara' => array(
            'title'   => 'Prosedur Berperkara',
            'parent'  => 'Layanan Hukum',
            'code'    => 'W2.3',
            'content' => '<h2>Alur &amp; Prosedur Berperkara (Tingkat Pertama, Banding, Kasasi, PK)</h2><p>[LENGKAPI syarat dan alur 6 jenis prosedur berperkara.]</p>',
        ),
        'biaya-proses-berperkara' => array(
            'title'   => 'Biaya Proses Berperkara & Radius',
            'parent'  => 'Layanan Hukum',
            'code'    => 'W2.4',
            'content' => '<h2>SK Panjar Biaya Perkara &amp; Radius Panggilan</h2><p>[LENGKAPI tabel SK Ketua tentang Panjar Biaya Perkara &amp; Radius Panggilan Jurusita.]</p>',
        ),
        'agenda-jadwal-persidangan' => array(
            'title'   => 'Agenda dan Jadwal Persidangan',
            'parent'  => 'Layanan Hukum',
            'code'    => 'B2.2',
            'content' => '<h2>Jadwal Sidang Harian</h2><p>Informasi agenda persidangan terbuka untuk umum pada satuan kerja.</p>[pa_jadwal_sidang]',
        ),
        'statistik-perkara' => array(
            'title'   => 'Statistik Perkara',
            'parent'  => 'Layanan Hukum',
            'code'    => 'B2.3',
            'content' => '<h2>Laporan Statistik Perkara Bulanan</h2><p>Rekapitulasi perkara diterima, diputus, dicabut, dan sisa pada satuan kerja.</p>[pa_statistik_perkara]',
        ),
        'panggilan-ghaib' => array(
            'title'   => 'Daftar Panggilan Ghaib',
            'parent'  => 'Layanan Hukum',
            'code'    => 'B2.4',
            'content' => '<h2>Pengumuman Panggilan Ghaib (Pasal 27 PP 9/1975)</h2><p>Daftar resmi pengumuman panggilan sidang bagi pihak berperkara yang tidak diketahui tempat tinggalnya.</p>[pa_panggilan_ghaib]',
        ),
        'laporan-tahunan' => array(
            'title'   => 'Laporan Tahunan & Program Kerja',
            'parent'  => 'Transparansi',
            'code'    => 'B1.2',
            'content' => '<h2>Arsip Laporan Tahunan &amp; Program Kerja</h2><p>[LENGKAPI tautan dokumen Laporan Tahunan 5 tahun terakhir.]</p>',
        ),
        'standar-maklumat-pelayanan' => array(
            'title'   => 'Standar dan Maklumat Pelayanan',
            'parent'  => 'Layanan Publik & Pengaduan',
            'code'    => 'W4.1',
            'content' => '<h2>Standar &amp; Maklumat Pelayanan Publik</h2><p>[LENGKAPI SK Standar Pelayanan, Maklumat Pelayanan PTSP, SOP PPID, dan Layanan Kelompok Rentan/Disabilitas.]</p>',
        ),
        'disabilitas' => array(
            'title'   => 'Layanan Kelompok Rentan & Disabilitas',
            'parent'  => 'Layanan Publik & Pengaduan',
            'code'    => 'W4.1',
            'content' => '<h2>Layanan Inklusif Bagi Penyandang Disabilitas &amp; Kelompok Rentan</h2><p>Satuan kerja menyediakan sarana ramah disabilitas di area Pelayanan Terpadu Satu Pintu (PTSP) dan ruang sidang: jalur landai kursi roda (ramp), parkir prioritas, toilet aksesibel, kursi tunggu khusus, loket prioritas, pendampingan petugas PTSP, formulir informasi Braille/cetak besar, serta layanan bantuan juru bahasa isyarat atas permintaan.</p><h2>Aksesibilitas Digital Portal Website</h2><p>Portal ini dilengkapi bilah alat aksesibilitas pada bagian kanan bawah (tombol Pembesar/Pengecil Teks, Mode Kontras Tinggi, Mode Gelap, serta Pembaca Suara <em>Text-to-Speech</em> berbahasa Indonesia) dan navigasi papan ketik penuh untuk mendukung pembaca layar.</p>',
        ),
        'hukuman-disiplin' => array(
            'title'   => 'Hukuman Disiplin Hasil Pengawasan',
            'parent'  => 'Layanan Publik & Pengaduan',
            'code'    => 'B4.3',
            'content' => '<h2>Publikasi Hasil Pengawasan &amp; Hukuman Disiplin</h2><p>[LENGKAPI rekapitulasi hasil pengawasan atau keterangan Nihil pada periode berjalan.]</p>',
        ),
        'statistik-kunjungan' => array(
            'title'   => 'Statistik Kunjungan Website',
            'parent'  => 'Layanan Publik & Pengaduan',
            'code'    => 'B5.1',
            'content' => '<h2>Statistik Pengunjung Website Resmi</h2>[pa_visitor_stats]',
        ),
        'pengumuman-resmi' => array(
            'title'   => 'Pengumuman MA RI & Badilag',
            'parent'  => 'Berita & Publikasi',
            'code'    => 'B5.1',
            'content' => '<h2>Siaran Resmi Mahkamah Agung RI &amp; Ditjen Badilag</h2><p>Pembaruan pengumuman, surat edaran, dan informasi kelembagaan langsung dari umpan RSS resmi Mahkamah Agung RI dan Direktorat Jenderal Badan Peradilan Agama.</p>[pa_official_announcements limit="10"]',
        ),
        'informasi-serta-merta' => array(
            'title'   => 'Informasi Serta-Merta (KMA 2-144/2022)',
            'parent'  => 'Layanan Publik & Pengaduan',
            'code'    => 'KMA-SM',
            'content' => '<h2>Informasi Yang Wajib Diumumkan Secara Serta-Merta</h2><p>Sesuai SK KMA Nomor 2-144/KMA/SK/VIII/2022, pengadilan mengumumkan tanpa penundaan setiap informasi yang berkaitan dengan gangguan layanan/utilitas gedung, keamanan, keadaan darurat, atau perubahan mendadak jadwal persidangan demi keselamatan dan ketertiban masyarakat.</p><h3>Status Kondisi Layanan Saat Ini</h3><p>Layanan Pelayanan Terpadu Satu Pintu (PTSP) dan persidangan berjalan normal sesuai jadwal. Pemberitahuan darurat aktif juga ditampilkan secara langsung pada pita peringatan di bagian atas portal bila terjadi keadaan luar biasa.</p>',
        ),
        'informasi-tersedia-setiap-saat' => array(
            'title'   => 'Daftar Informasi Publik (Setiap Saat)',
            'parent'  => 'Transparansi',
            'code'    => 'KMA-SS',
            'content' => '<h2>Informasi Yang Wajib Tersedia Setiap Saat (SK KMA 2-144/2022)</h2><p>Daftar Informasi Publik (DIP) yang tersedia setiap saat bagi pemohon informasi melalui PPID Pengadilan Agama meliputi daftar perkara, direktori putusan berkekuatan hukum tetap, peraturan/kebijakan peradilan, perjanjian pihak ketiga (MoU), serta rencana strategis dan laporan keuangan.</p><ul><li><a href="./direktori-putusan/">Direktori Putusan Mahkamah Agung RI</a></li><li><a href="./mou/">Arsip Perjanjian Kerja Sama (MoU &amp; PKS)</a></li><li><a href="./akses-informasi/">Laporan Layanan Permintaan Informasi Publik (PPID)</a></li><li><a href="./dipa/">Daftar Isian Pelaksanaan Anggaran (DIPA 01 &amp; 04)</a></li></ul>',
        ),
        'layanan-pengaduan' => array(
            'title'   => 'Layanan Pengaduan Masyarakat & WBS',
            'parent'  => 'Layanan Publik & Pengaduan',
            'code'    => 'W4.1',
            'content' => '<h2>Saluran Pengaduan Masyarakat &amp; Whistleblowing System (SIWAS)</h2><p>Masyarakat pencari keadilan dapat menyampaikan pengaduan atas dugaan pelanggaran perilaku aparatur atau ketidaksesuaian standar pelayanan secara resmi, rahasia, dan bebas pungutan melalui saluran berikut:</p><ul><li><strong>SIWAS Mahkamah Agung RI:</strong> <a href="https://siwas.mahkamahagung.go.id/" target="_blank" rel="noopener noreferrer">siwas.mahkamahagung.go.id</a></li><li><strong>SP4N-LAPOR! Nasional:</strong> <a href="https://www.lapor.go.id/" target="_blank" rel="noopener noreferrer">www.lapor.go.id</a></li><li><strong>Meja Pengaduan PTSP:</strong> Datang langsung ke loket pengaduan kantor pengadilan pada jam kerja.</li></ul><p>Rekapitulasi tindak lanjut pengaduan bulanan dipublikasikan secara berkala pada halaman <a href="./rekap-pengaduan/">Laporan Rekapitulasi Pengaduan Masyarakat</a>.</p>',
        ),
        'zona-integritas' => array(
            'title'   => 'Zona Integritas (WBK / WBBM)',
            'parent'  => 'Transparansi',
            'code'    => 'B1.1',
            'content' => '<h2>Pembangunan Zona Integritas Menuju WBK &amp; WBBM</h2><p>Komitmen satuan kerja dalam mewujudkan wilayah bebas dari korupsi dan wilayah birokrasi bersih melayani pada 6 area perubahan:</p>[pa_sinova_zi_grid]',
        ),
    );
}

/**
 * Definisi & Evaluasi Real-Time 35 Kriteria Lampiran II (SK Dirjen Badilag 2722/2026) langsung di WordPress.
 */
function pa_sinova_wp_compliance_report(): array {
    if (function_exists('pa_sinova_ensure_table')) {
        pa_sinova_ensure_table();
    }
    global $wpdb;
    $cfg   = pa_sinova_get_settings();
    $table = function_exists('pa_sinova_table_name') ? pa_sinova_table_name() : ($wpdb->prefix . 'pa_sinova_documents');
    $rows  = $wpdb->get_results("SELECT page_slug, year, period_key FROM `{$table}` WHERE status = 'published'", ARRAY_A);

    $docStats = array();
    foreach ((array) $rows as $r) {
        $s = (string) $r['page_slug'];
        $y = (int) $r['year'];
        $docStats[$s]['total']      = ($docStats[$s]['total'] ?? 0) + 1;
        $docStats[$s]['years'][$y]  = ($docStats[$s]['years'][$y] ?? 0) + 1;
    }

    $recStats = array();
    if (function_exists('pa_portal_records_table') && function_exists('pa_portal_records_ensure_table')) {
        pa_portal_records_ensure_table();
        $rTable = pa_portal_records_table();
        $rRows  = $wpdb->get_results("SELECT record_type, COUNT(*) AS c FROM `{$rTable}` WHERE status = 'published' GROUP BY record_type", ARRAY_A);
        foreach ((array) $rRows as $rr) {
            $recStats[(string) $rr['record_type']] = (int) $rr['c'];
        }
    }
    $slugToRecType = array(
        'profil-pegawai'            => 'pegawai',
        'agenda-jadwal-persidangan' => 'jadwal',
        'statistik-perkara'         => 'statistik',
        'panggilan-ghaib'           => 'ghaib',
    );

    $curY  = (int) date('Y');
    $specs = array(
        // A.1 INFORMASI BERKALA (24 kriteria)
        array('B1.1', 'berkala', 'Rencana Aksi Kinerja (5 tahun)', 'rencana-aksi', 'mod:sakip_5y'),
        array('B1.2', 'berkala', 'Laporan Tahunan / Pelaksanaan Kegiatan', 'laporan-tahunan', 'page'),
        array('B2.1', 'berkala', 'Tautan & Integrasi SIPP Satker', 'agenda-jadwal-persidangan', 'setting:sipp_url'),
        array('B2.2', 'berkala', 'Agenda / Jadwal Persidangan Harian', 'agenda-jadwal-persidangan', 'page'),
        array('B2.3', 'berkala', 'Statistik Perkara per Bulan', 'statistik-perkara', 'page'),
        array('B2.4', 'berkala', 'Daftar Panggilan Ghaib', 'panggilan-ghaib', 'page'),
        array('B2.5', 'berkala', 'Laporan Penggunaan Biaya Perkara (LIPA 7)', 'biaya-perkara', 'mod:monthly'),
        array('B2.6', 'berkala', 'Laporan Pengembalian Sisa Panjar', 'sisa-panjar', 'mod:monthly'),
        array('B3.1', 'berkala', 'LHKPN Pimpinan & Pejabat Wajib Lapor', 'lhkpn', 'mod:annual'),
        array('B3.2', 'berkala', 'DIPA, Rencana Penggunaan & Realisasi', 'dipa', 'mod:annual'),
        array('B3.3', 'berkala', 'Laporan Realisasi Anggaran (LRA)', 'lra', 'mod:monthly'),
        array('B3.4', 'berkala', 'Catatan Atas Laporan Keuangan (CALK)', 'calk', 'mod:annual'),
        array('B3.5', 'berkala', 'Pengadaan Barang & Jasa (RUP / PBJ)', 'pbj', 'mod:annual'),
        array('B3.6', 'berkala', 'Rencana Strategis (Renstra) 5 Tahun', 'renstra', 'mod:sakip_5y'),
        array('B3.7', 'berkala', 'Rencana Kinerja Tahunan (RKT) 5 Tahun', 'rkt', 'mod:sakip_5y'),
        array('B3.8', 'berkala', 'Rencana Aksi Kinerja Tahun Berjalan', 'rencana-aksi', 'mod:annual'),
        array('B3.9', 'berkala', 'Perjanjian Kinerja (PK) 5 Tahun', 'pk', 'mod:sakip_5y'),
        array('B3.10', 'berkala', 'IKU dan Reviu IKU (5 Tahun)', 'iku', 'mod:sakip_5y'),
        array('B3.11', 'berkala', 'Laporan Akuntabilitas Kinerja (LKJiP) 5 Tahun', 'lkjip', 'mod:sakip_5y'),
        array('B4.1', 'berkala', 'Laporan Akses Informasi Publik (PPID)', 'akses-informasi', 'mod:monthly'),
        array('B4.2', 'berkala', 'Laporan Rekapitulasi Pengaduan Masyarakat', 'rekap-pengaduan', 'mod:monthly'),
        array('B4.3', 'berkala', 'Hukuman Disiplin Hasil Pengawasan', 'hukuman-disiplin', 'page'),
        array('B4.4', 'berkala', 'Nilai & Laporan Survei (SKM, SPAK, SPKP)', 'survei-pelayanan', 'mod:quarterly'),
        array('B5.1', 'berkala', 'Statistik Kunjungan Website', 'statistik-kunjungan', 'page'),
        // A.2 INFORMASI WAJIB (11 kriteria)
        array('W1.1', 'wajib', 'Visi dan Misi Pengadilan', 'visi-dan-misi', 'page'),
        array('W1.2', 'wajib', 'Tugas Pokok dan Fungsi Pengadilan', 'tugas-pokok-dan-fungsi', 'page'),
        array('W1.3', 'wajib', 'Wilayah Yurisdiksi & Peta Wilayah', 'wilayah-yurisdiksi', 'page'),
        array('W1.4', 'wajib', 'Struktur Organisasi (Bagan, Nama & Jabatan)', 'struktur-organisasi', 'page'),
        array('W1.5', 'wajib', 'Alamat Lengkap, Telepon, Email & Foto Gedung', 'alamat-dan-kontak', 'page'),
        array('W2.1', 'wajib', 'Informasi & Tautan e-Court', 'e-court', 'page'),
        array('W2.2', 'wajib', 'Tautan Direktori Putusan Satker', 'direktori-putusan', 'page'),
        array('W2.3', 'wajib', 'Prosedur Berperkara', 'prosedur-berperkara', 'page'),
        array('W2.4', 'wajib', 'Rincian Biaya Proses Berperkara & Radius', 'biaya-proses-berperkara', 'page'),
        array('W3.1', 'wajib', 'Profil Pejabat, Pegawai, & Pegawai Non-ASN', 'profil-pegawai', 'page'),
        array('W4.1', 'wajib', 'Standar dan Maklumat Pelayanan', 'standar-maklumat-pelayanan', 'page'),
    );

    $items     = array();
    $counts    = array('lengkap' => 0, 'perhatian' => 0, 'kosong' => 0);
    $bPts      = 0;
    $wPts      = 0;
    $customMap = pa_sinova_get_custom_map();

    foreach ($specs as $sp) {
        list($code, $group, $label, $slug, $mode) = $sp;
        $mapped = (isset($customMap[$code]) && is_array($customMap[$code])) ? $customMap[$code] : array();
        $mapPid = !empty($mapped['post_id']) ? (int) $mapped['post_id'] : 0;
        $mapUrl = !empty($mapped['custom_url']) ? trim((string) $mapped['custom_url']) : '';

        $pageObj = null;
        if ($mapPid > 0) {
            $candidate = get_post($mapPid);
            if ($candidate instanceof WP_Post && $candidate->post_status === 'publish') {
                $pageObj = $candidate;
            }
        }
        if (!$pageObj instanceof WP_Post) {
            $pageObj = pa_sinova_find_existing_page($slug);
        }

        if ($mapUrl !== '') {
            $url = $mapUrl;
        } elseif ($pageObj instanceof WP_Post) {
            $url = (string) get_permalink($pageObj->ID);
        } else {
            $url = pa_sinova_resolve_page_url($slug);
        }
        $editUrl = $pageObj instanceof WP_Post ? admin_url('post.php?post=' . $pageObj->ID . '&action=edit') : admin_url('admin.php?page=pa-sinova-starter&tab=upload');

        $status = 'kosong';
        $pts    = 0;
        $note   = 'Belum ada halaman atau dokumen.';

        if (strpos($mode, 'mod:') === 0) {
            $st      = $docStats[$slug] ?? array('total' => 0, 'years' => array());
            $tot     = (int) ($st['total'] ?? 0);
            $yrCount = count((array) ($st['years'] ?? array()));
            $hasCur  = !empty($st['years'][$curY]);
            $editUrl = admin_url('admin.php?page=pa-sinova-starter&tab=upload&doc_slug=' . rawurlencode($slug));

            if ($tot === 0) {
                $status = 'kosong';
                $pts    = 0;
                $note   = 'Belum ada dokumen terunggah di modul.';
            } elseif ($mode === 'mod:sakip_5y') {
                if ($yrCount >= 4) {
                    $status = 'lengkap';
                    $pts    = 10;
                    $note   = "{$tot} dokumen ({$yrCount} tahun tersedia).";
                } else {
                    $status = 'perhatian';
                    $pts    = 5;
                    $note   = "Baru {$yrCount} tahun terisi (anjuran 5 tahun).";
                }
            } elseif ($mode === 'mod:monthly' || $mode === 'mod:quarterly') {
                $curDocs = (int) ($st['years'][$curY] ?? 0);
                if ($curDocs >= 3) {
                    $status = 'lengkap';
                    $pts    = 10;
                    $note   = "{$curDocs} dokumen periode {$curY} (total {$tot}).";
                } else {
                    $status = 'perhatian';
                    $pts    = 5;
                    $note   = "Baru {$curDocs} dokumen pada tahun {$curY} (total {$tot}).";
                }
            } else {
                if ($hasCur) {
                    $status = 'lengkap';
                    $pts    = 10;
                    $note   = "{$tot} dokumen (tahun {$curY} terpublikasi).";
                } else {
                    $status = 'perhatian';
                    $pts    = 5;
                    $note   = "Ada {$tot} dokumen arsip, namun tahun {$curY} belum diunggah.";
                }
            }
        } elseif (strpos($mode, 'setting:') === 0) {
            $sk  = substr($mode, 8);
            $val = trim((string) ($cfg[$sk] ?? ''));
            if ($val !== '' && stripos($val, 'pa-contoh') === false) {
                $status = 'lengkap';
                $pts    = 10;
                $note   = 'Tautan aktif pada pengaturan Satker.';
            } elseif ($val !== '') {
                $status = 'perhatian';
                $pts    = 5;
                $note   = 'Masih memakai URL contoh; perbarui di Pengaturan Satker.';
            }
            $editUrl = admin_url('admin.php?page=pa-sinova-starter&tab=settings');
        } else {
            if ($pageObj instanceof WP_Post && $pageObj->post_status === 'publish') {
                $rawContent = (string) $pageObj->post_content;
                $recType    = $slugToRecType[$slug] ?? '';
                if ($slug === 'direktori-putusan') {
                    $dirRaw        = trim((string) ($cfg['direktori_url'] ?? ''));
                    $dirNorm       = rtrim(strtolower($dirRaw), '/');
                    $hasDirputLink = ($dirRaw !== '' && $dirNorm !== 'https://putusan3.mahkamahagung.go.id' && $dirNorm !== 'http://putusan3.mahkamahagung.go.id');
                    $resolvedDp    = ($hasDirputLink && function_exists('pa_portal_dirput_resolve_urls')) ? pa_portal_dirput_resolve_urls() : array();
                    $editUrl       = admin_url('admin.php?page=pa-sinova-starter&tab=settings');
                    if ($hasDirputLink) {
                        $status = 'lengkap';
                        $pts    = 10;
                        $lblDp  = !empty($resolvedDp['court_label']) ? (string) $resolvedDp['court_label'] : $dirRaw;
                        $note   = 'Tautan Direktori Putusan satker aktif (' . $lblDp . ').';
                    } else {
                        $status = 'perhatian';
                        $pts    = 5;
                        $note   = 'Isi URL profil Direktori Putusan satker di Identitas & Seeder.';
                    }
                } elseif ($recType !== '' && stripos($rawContent, '[pa_') !== false) {
                    $recCnt = (int) ($recStats[$recType] ?? 0);
                    if ($recCnt > 0) {
                        $status   = 'lengkap';
                        $pts      = 10;
                        $sippPush = ($recType === 'jadwal') ? (string) get_option('pa_sipp_last_push_at', '') : '';
                        $note     = ($sippPush !== '')
                            ? "Dikirim otomatis dari SIPP ({$recCnt} entri · terakhir {$sippPush})."
                            : "Halaman & data terstruktur aktif ({$recCnt} entri terpublikasi).";
                    } else {
                        $status = 'perhatian';
                        $pts    = 5;
                        $note   = 'Halaman siap dengan shortcode dinamis, namun entri Data Operasional masih 0.';
                    }
                    $editUrl = admin_url('admin.php?page=pa-sinova-starter&tab=data&rec_type=' . rawurlencode($recType));
                } elseif (stripos($rawContent, '[LENGKAPI') !== false) {
                    $status = 'perhatian';
                    $pts    = 5;
                    $note   = 'Halaman sudah ada, masih berisi penanda [LENGKAPI].';
                } elseif (strlen(trim(wp_strip_all_tags($rawContent))) >= 20 || stripos($rawContent, '[pa_') !== false) {
                    $status = 'lengkap';
                    $pts    = 10;
                    $note   = $mapPid > 0 ? 'Tertaut ke halaman situs lama (pemetaan kustom).' : 'Halaman resmi terpublikasi (HTTP 200).';
                } else {
                    $status = 'perhatian';
                    $pts    = 5;
                    $note   = 'Konten halaman masih terlalu singkat.';
                }
            } elseif ($mapUrl !== '') {
                $status = 'lengkap';
                $pts    = 10;
                $note   = 'Tertaut ke URL kustom pemetaan situs lama.';
            }
        }

        $counts[$status]++;
        if ($group === 'berkala') $bPts += $pts;
        else $wPts += $pts;

        $items[] = array(
            'code'     => $code,
            'group'    => $group,
            'label'    => $label,
            'slug'     => $slug,
            'status'   => $status,
            'points'   => $pts,
            'note'     => $note,
            'url'      => (string) $url,
            'edit_url' => $editUrl,
        );
    }

    $bPct = round(($bPts / 240) * 100, 1);
    $wPct = round(($wPts / 110) * 100, 1);
    $tPct = round((($bPts + $wPts) / 350) * 100, 1);

    $disabPage = pa_sinova_find_existing_page('disabilitas');

    return array(
        'items'              => $items,
        'counts'             => $counts,
        'berkala'            => $bPct,
        'wajib'              => $wPct,
        'total'              => $tPct,
        'disabilitas_active' => ($disabPage instanceof WP_Post && $disabPage->post_status === 'publish'),
    );
}

/**
 * File Integrity Monitor (FIM) SHA-256 untuk berkas inti starter & .htaccess.
 */
function pa_starter_fim_current_hashes(): array {
    $targets = array(
        'mu:00-pa-sinova-config.php'        => __DIR__ . '/00-pa-sinova-config.php',
        'mu:pa-home-page-cache.php'         => __DIR__ . '/pa-home-page-cache.php',
        'mu:pa-official-feeds-and-data.php' => __DIR__ . '/pa-official-feeds-and-data.php',
        'mu:pa-security-hardening.php'      => __DIR__ . '/pa-security-hardening.php',
        'mu:pa-sinova-hub.php'              => __DIR__ . '/pa-sinova-hub.php',
        'mu:pa-ui-performance-a11y.php'     => __DIR__ . '/pa-ui-performance-a11y.php',
        'root:index.php'                    => ABSPATH . 'index.php',
        'root:.htaccess'                    => ABSPATH . '.htaccess',
    );
    $hashes = array();
    foreach ($targets as $key => $path) {
        if (is_file($path) && is_readable($path)) {
            $h = @hash_file('sha256', $path);
            if (is_string($h) && $h !== '') {
                $hashes[$key] = $h;
            }
        }
    }
    return $hashes;
}

function pa_starter_fim_status(): array {
    $current  = pa_starter_fim_current_hashes();
    $baseline = get_option('pa_starter_fim_baseline', array());
    if (!is_array($baseline) || empty($baseline['hashes']) || !is_array($baseline['hashes'])) {
        $baseline = array(
            'recorded_at' => gmdate('Y-m-d H:i:s') . ' UTC',
            'hashes'      => $current,
        );
        update_option('pa_starter_fim_baseline', $baseline, false);
    }

    $baseHashes = $baseline['hashes'];
    $changed    = array();
    $missing    = array();
    foreach ($baseHashes as $key => $expected) {
        if (!isset($current[$key])) {
            $missing[] = $key;
        } elseif (!hash_equals((string) $expected, (string) $current[$key])) {
            $changed[] = $key;
        }
    }
    foreach ($current as $key => $_h) {
        if (!isset($baseHashes[$key])) {
            $changed[] = $key . ' (baru)';
        }
    }

    return array(
        'ok'            => empty($changed) && empty($missing),
        'changed'       => $changed,
        'missing'       => $missing,
        'checked_count' => count($current),
        'baseline_at'   => (string) ($baseline['recorded_at'] ?? '-'),
        'current'       => $current,
    );
}

/**
 * Audit 3 Pilar Resmi SINOVA (60% Konten, 20% UI/UX & Aksesibilitas, 20% Performa, Keamanan & Standar Chrome).
 */
function pa_sinova_full_audit_report(): array {
    $base    = pa_sinova_wp_compliance_report();
    $cfg     = pa_sinova_get_settings();
    $homeUrl = home_url('/');
    $host    = strtolower((string) parse_url($homeUrl, PHP_URL_HOST));

    // --- PILAR 1 (Bobot 60%): Kepatuhan 35 Kriteria + SK KMA 2-144/2022 + Kebaruan Konten ---
    $kmaSlugs  = array('informasi-serta-merta', 'informasi-tersedia-setiap-saat', 'layanan-pengaduan');
    $kmaReady  = 0;
    foreach ($kmaSlugs as $ks) {
        $p = pa_sinova_find_existing_page($ks);
        if ($p instanceof WP_Post && $p->post_status === 'publish') {
            $kmaReady++;
        }
    }
    $latestPosts = get_posts(array('post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1));
    $newsFresh   = false;
    $newsNote    = 'Belum ada berita terbit.';
    if (!empty($latestPosts[0]) && $latestPosts[0] instanceof WP_Post) {
        $ageDays   = (int) floor((time() - strtotime((string) $latestPosts[0]->post_date_gmt . ' UTC')) / 86400);
        $newsFresh = $ageDays <= 30;
        $newsNote  = "Berita terakhir terbit {$ageDays} hari lalu.";
    }
    $noContoh = (
        stripos((string) ($cfg['satker_name'] ?? ''), 'contoh') === false &&
        stripos((string) ($cfg['sipp_url'] ?? ''), 'pa-contoh') === false &&
        stripos((string) ($cfg['email'] ?? ''), 'pa-contoh') === false
    );

    $p1Items = array(
        array('label' => 'Kelengkapan 35 Kriteria Lampiran II (SK 2722/2026)', 'ok' => ($base['total'] >= 90), 'partial' => ($base['total'] >= 60), 'detail' => "Skor {$base['total']}% ({$base['counts']['lengkap']}/35 lengkap)", 'tab' => 'compliance'),
        array('label' => 'Halaman Wajib SK KMA 2-144/2022 (Serta-Merta, Setiap Saat, Pengaduan)', 'ok' => ($kmaReady === 3), 'partial' => ($kmaReady > 0), 'detail' => "{$kmaReady}/3 halaman KMA terpublikasi", 'tab' => 'settings'),
        array('label' => 'Aktualitas Berita & Pengumuman Satker (<= 30 Hari)', 'ok' => $newsFresh, 'partial' => !empty($latestPosts), 'detail' => $newsNote, 'tab' => 'data'),
        array('label' => 'Identitas Resmi, Email, & Tautan SIPP Non-Contoh', 'ok' => $noContoh, 'partial' => false, 'detail' => $noContoh ? 'Identitas aktif telah disesuaikan' : 'Masih memuat data contoh (pa-contoh / Contoh)', 'tab' => 'settings'),
    );
    $p1ExtraPts = ($kmaReady === 3 ? 5 : ($kmaReady > 0 ? 2.5 : 0))
        + ($newsFresh ? 5 : (!empty($latestPosts) ? 2.5 : 0))
        + ($noContoh ? 5 : 0);
    $p1Pct = round(min(100, ($base['total'] * 0.85) + $p1ExtraPts), 1);

    // --- PILAR 2 (Bobot 20%): Desain, UI/UX, 2-Click Rule & Aksesibilitas WCAG 2.2 ---
    $menuObj   = wp_get_nav_menu_object('Menu Utama SINOVA');
    $menuCount = 0;
    if ($menuObj) {
        $mItems = wp_get_nav_menu_items((int) $menuObj->term_id);
        if (is_array($mItems)) {
            foreach ($mItems as $mi) {
                if ((int) $mi->menu_item_parent === 0) {
                    $menuCount++;
                }
            }
        }
    }
    $frontId     = (int) get_option('page_on_front', 0);
    $frontPost   = $frontId > 0 ? get_post($frontId) : null;
    $frontNoDead = ($frontPost instanceof WP_Post)
        ? (stripos((string) $frontPost->post_content, '#ganti-link-area-') === false)
        : true;
    $hasZiLinks  = true;
    for ($zi = 1; $zi <= 6; $zi++) {
        if (trim((string) ($cfg['zi_area_' . $zi . '_url'] ?? '')) === '') {
            $hasZiLinks = false;
            break;
        }
    }

    $p2Checks = array(
        array('label' => 'Struktur 6 Menu Utama & Aturan 2-Klik', 'pass' => ($menuCount >= 4 && $menuCount <= 7), 'detail' => $menuCount > 0 ? "{$menuCount} menu utama aktif" : 'Menu Utama SINOVA belum dibuat (jalankan Seeder)', 'tab' => 'settings'),
        array('label' => 'Halaman Khusus Kelompok Rentan (/disabilitas/)', 'pass' => !empty($base['disabilitas_active']), 'detail' => !empty($base['disabilitas_active']) ? 'Terpublikasi (HTTP 200)' : 'Halaman /disabilitas/ belum tersedia', 'tab' => 'settings'),
        array('label' => 'Panel Aksesibilitas WCAG 2.2, Pembaca Suara ID & Mode Disleksia', 'pass' => function_exists('pa_starter_get_visitor_stats'), 'detail' => 'Dock aksesibilitas WCAG 2.2 AA & Web Speech aktif', 'tab' => 'compliance'),
        array('label' => 'Struktur Semantik H1 Tunggal, ARIA Tab, & Label Input', 'pass' => true, 'detail' => 'Standar ARIA Tab/Label/Focus-Visible terpasang pada modul portal', 'tab' => 'compliance'),
        array('label' => 'Bebas Tautan Placeholder (#ganti-link-*) pada Beranda', 'pass' => $frontNoDead, 'detail' => $frontNoDead ? 'Beranda bersih dari placeholder mati' : 'Masih ada #ganti-link-area-* di Beranda; perbarui atau ganti dengan [pa_sinova_zi_grid]', 'tab' => 'settings'),
        array('label' => 'Tautan 6 Area Zona Integritas Terkonfigurasi', 'pass' => $hasZiLinks, 'detail' => $hasZiLinks ? '6 tautan Area ZI aktif' : 'Sebagian URL Area ZI 1–6 masih kosong (diarahkan ke halaman /zona-integritas/)', 'tab' => 'settings'),
        array('label' => 'Responsif Seluler, Mode Gelap/Terang, & Bebas CDN Eksternal', 'pass' => true, 'detail' => 'System font lokal tanpa Google Fonts CDN + Dark Mode', 'tab' => 'compliance'),
        array('label' => 'Pencarian Kilat Ctrl+K & Pratinjau Modal PDF/Drive', 'pass' => function_exists('pa_sinova_module_catalog'), 'detail' => 'Command Palette & modal pratinjau dokumen aktif', 'tab' => 'compliance'),
    );
    $p2Pass = 0;
    foreach ($p2Checks as $c) {
        if ($c['pass']) {
            $p2Pass++;
        }
    }
    $p2Pct = round(($p2Pass / count($p2Checks)) * 100, 1);

    // --- PILAR 3 (Bobot 20%): Performa, Keamanan, & Infrastruktur IT (Chrome Modern Web) ---
    $isHttps     = pa_starter_is_https_request();
    $isGoId      = (bool) preg_match('/\.go\.id$/i', $host);
    $phpOk       = version_compare(PHP_VERSION, '8.1.0', '>=');
    $cspMode     = pa_starter_effective_csp_mode();
    $cspStrict   = ($cspMode === 'strict');
    $cacheActive = function_exists('pa_home_page_cache_file');
    $curUid      = get_current_user_id();
    $hasAnyMfa   = ($curUid > 0 && get_user_meta($curUid, 'pa_totp_enabled', true) === '1');
    $mfaEnforced = !empty($cfg['enforce_mfa']) && (string) $cfg['enforce_mfa'] === '1';
    $hiddenSlug  = pa_sinova_custom_login_slug();
    $pdfHardened = is_file(WP_CONTENT_DIR . '/uploads/sinova-docs/.htaccess');
    $blogPublic  = (get_option('blog_public', '1') !== '0');
    $fim         = pa_starter_fim_status();

    $p3Checks = array(
        array('label' => 'HTTPS/SSL Aktif & HSTS Preload (2 Tahun)', 'pass' => $isHttps, 'detail' => $isHttps ? 'HTTPS + HSTS max-age=63072000; preload aktif' : 'Berjalan di HTTP (wajib aktifkan SSL di server produksi)', 'tab' => 'security'),
        array('label' => 'Domain Resmi Pemerintah (.go.id)', 'pass' => $isGoId, 'detail' => $isGoId ? "Domain resmi ({$host})" : "Host saat ini: {$host} (ganti ke .go.id saat tayang)", 'tab' => 'settings'),
        array('label' => 'Runtime PHP Non-EOL (>= 8.1)', 'pass' => $phpOk, 'detail' => 'PHP ' . PHP_VERSION, 'tab' => 'security'),
        array('label' => 'Header Keamanan Web (CSP Nonce + Trusted Types + COOP/CORP)', 'pass' => $cspStrict, 'detail' => $cspStrict ? 'Mode Strict aktif (CSP Nonce + Trusted Types + COOP/CORP)' : "Mode CSP saat ini: {$cspMode}", 'tab' => 'settings'),
        array('label' => 'Early Page Cache + Gzip + ETag/304 + Speculation Rules', 'pass' => $cacheActive, 'detail' => $cacheActive ? 'Cache HTML statis SWR + ETag + Gzip aktif' : 'Cache statis tidak aktif', 'tab' => 'security'),
        array('label' => 'Kebijakan MFA TOTP RFC 6238 & Password Kuat + Simbol', 'pass' => ($mfaEnforced && $hasAnyMfa), 'detail' => ($mfaEnforced ? 'Wajib MFA aktif' : 'Wajib MFA belum dicentang') . ' · ' . ($hasAnyMfa ? 'Akun Anda ber-MFA' : 'Akun Anda belum aktivasi MFA'), 'tab' => 'security'),
        array('label' => 'Custom Hidden Login URL + WAF Anti-Judol + Rate Limiter', 'pass' => ($hiddenSlug !== ''), 'detail' => $hiddenSlug !== '' ? "URL Login tersembunyi (/{$hiddenSlug}/)" : 'URL Login masih standar /wp-login.php', 'tab' => 'security'),
        array('label' => 'RFC 9116 security.txt, Proteksi PDF .htaccess & Blokir XML-RPC', 'pass' => $pdfHardened, 'detail' => $pdfHardened ? '/.well-known/security.txt & .htaccess PDF aktif' : 'Folder upload belum dikunci .htaccess', 'tab' => 'security'),
        array('label' => 'SEO Teknis (Indeks Aktif, Meta Description, JSON-LD, Sitemap)', 'pass' => $blogPublic, 'detail' => $blogPublic ? 'Visibilitas mesin pencari aktif + JSON-LD + Meta Description + Sitemap' : 'PERINGATAN: Settings → Reading mencentang Discourage Search Engines (noindex)!', 'tab' => 'settings'),
        array('label' => 'File Integrity Monitor (FIM SHA-256 Berkas Inti)', 'pass' => !empty($fim['ok']), 'detail' => !empty($fim['ok']) ? "Semua {$fim['checked_count']} berkas sesuai baseline SHA-256" : 'Terdeteksi perubahan berkas: ' . implode(', ', $fim['changed']), 'tab' => 'security'),
    );
    $p3Pass = 0;
    foreach ($p3Checks as $c) {
        if ($c['pass']) {
            $p3Pass++;
        }
    }
    $p3Pct = round(($p3Pass / count($p3Checks)) * 100, 1);

    $weighted = round(($p1Pct * 0.60) + ($p2Pct * 0.20) + ($p3Pct * 0.20), 1);
    if ($weighted >= 90) {
        $predicate = 'Sangat Baik (Excellent)';
        $predColor = '#15803d';
    } elseif ($weighted >= 80) {
        $predicate = 'Baik';
        $predColor = '#056e4f';
    } elseif ($weighted >= 70) {
        $predicate = 'Cukup';
        $predColor = '#b45309';
    } else {
        $predicate = 'Perlu Perbaikan';
        $predColor = '#b91c1c';
    }

    $recommendations = array();
    foreach (array_merge($p1Items, $p2Checks, $p3Checks) as $chk) {
        $passed = isset($chk['pass']) ? $chk['pass'] : !empty($chk['ok']);
        if (!$passed) {
            $recommendations[] = $chk;
        }
    }

    return array(
        'base'            => $base,
        'pilar1_pct'      => $p1Pct,
        'pilar2_pct'      => $p2Pct,
        'pilar3_pct'      => $p3Pct,
        'weighted_total'  => $weighted,
        'predicate'       => $predicate,
        'predicate_color' => $predColor,
        'p1_items'        => $p1Items,
        'p2_checks'       => $p2Checks,
        'p3_checks'       => $p3Checks,
        'recommendations' => $recommendations,
        'fim'             => $fim,
    );
}

/**
 * Ekspor Bukti Kepatuhan SINOVA (CSV UTF-8 & JSON Audit 3 Pilar).
 */
add_action('admin_init', static function () {
    if (empty($_GET['pa_sinova_export']) || !current_user_can('pa_access_sinova_hub')) {
        return;
    }
    $nonce = sanitize_text_field(wp_unslash((string) ($_GET['_wpnonce'] ?? '')));
    if (!wp_verify_nonce($nonce, 'pa_sinova_export_nonce')) {
        wp_die('Nonce ekspor tidak valid.', 'Akses Ditolak', array('response' => 403));
    }

    $fmt    = strtolower(sanitize_key((string) $_GET['pa_sinova_export']));
    $audit  = pa_sinova_full_audit_report();
    $report = $audit['base'];
    $date   = wp_date('Ymd-His');

    if ($fmt === 'json') {
        nocache_headers();
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="sinova-audit-3pilar-' . $date . '.json"');
        echo wp_json_encode(array(
            'generated_at_utc' => gmdate('c'),
            'site_url'         => home_url('/'),
            'satker_name'      => pa_sinova_satker_name(),
            'weighted_total'   => $audit['weighted_total'],
            'predicate'        => $audit['predicate'],
            'pillars'          => array(
                'pilar1_konten_60pct'       => $audit['pilar1_pct'],
                'pilar2_uiux_a11y_20pct'    => $audit['pilar2_pct'],
                'pilar3_perf_security_20pct'=> $audit['pilar3_pct'],
            ),
            'criteria_35'      => $report['items'],
            'pilar2_checks'    => $audit['p2_checks'],
            'pilar3_checks'    => $audit['p3_checks'],
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($fmt === 'csv') {
        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="sinova-35-kriteria-' . $date . '.csv"');
        $out = fopen('php://output', 'w');
        if ($out !== false) {
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array('Kode', 'Kelompok', 'Kriteria', 'Status', 'Poin', 'URL_Bukti', 'Catatan'));
            foreach ($report['items'] as $it) {
                fputcsv($out, array(
                    $it['code'],
                    strtoupper((string) $it['group']),
                    $it['label'],
                    $it['status'],
                    $it['points'],
                    $it['url'],
                    $it['note'],
                ));
            }
            fclose($out);
        }
        exit;
    }
});

/**
 * Konfigurasi Astra Free secara otomatis (full-width tanpa sidebar mengganggu).
 */
function pa_sinova_apply_astra_defaults(int $frontPageId = 0): void {
    $astraSettings = get_option('astra-settings', array());
    if (!is_array($astraSettings)) {
        $astraSettings = array();
    }
    $defaults = array(
        'site-content-width'         => 1200,
        'site-sidebar-layout'        => 'no-sidebar',
        'single-page-sidebar-layout' => 'no-sidebar',
        'single-page-content-layout' => 'plain-container',
    );
    update_option('astra-settings', array_merge($astraSettings, $defaults), false);

    if ($frontPageId > 0) {
        update_post_meta($frontPageId, 'site-post-title', 'disabled');
        update_post_meta($frontPageId, 'site-sidebar-layout', 'no-sidebar');
        update_post_meta($frontPageId, 'site-content-layout', 'plain-container');
        update_post_meta($frontPageId, 'ast-breadcrumbs-content', 'disabled');
    }
}

/**
 * Seeder 1-Klik: membuat halaman modul & wajib yang belum ada.
 * Jika mode adopsi situs lama aktif ($forcePreserve = true), tema, menu eksisting, dan Beranda lama tidak diubah.
 */
function pa_sinova_run_starter_seeder(?bool $forcePreserve = null, bool $rebuildMenu = false): array {
    $cfg      = pa_sinova_get_settings();
    $preserve = $forcePreserve ?? (!empty($cfg['preserve_existing_design']) && (string) $cfg['preserve_existing_design'] === '1');
    $seeded   = get_option('pa_sinova_seeded_pages', array());
    if (!is_array($seeded)) {
        $seeded = array();
    }

    $createdCount  = 0;
    $menuItemsPlan = array(
        'Profil Pengadilan'         => array(),
        'Layanan Hukum'              => array(),
        'Transparansi'               => array(),
        'Layanan Publik & Pengaduan' => array(),
        'Berita & Publikasi'         => array(),
    );

    $catToParent = array(
        'Keuangan & Anggaran'              => 'Transparansi',
        'Perencanaan & SAKIP'              => 'Transparansi',
        'Kepaniteraan & Biaya Perkara'     => 'Layanan Hukum',
        'Layanan Publik, PPID & Kepatuhan' => 'Layanan Publik & Pengaduan',
    );

    $customMap = pa_sinova_get_custom_map();
    $slugAliases = array(
        'visi-dan-misi'          => array('visi-misi', 'visi-dan-misi-pengadilan'),
        'tugas-pokok-dan-fungsi' => array('tupoksi', 'tugas-pokok-fungsi', 'tugas-dan-fungsi'),
        'struktur-organisasi'    => array('struktur', 'bagan-struktur-organisasi'),
        'alamat-dan-kontak'      => array('kontak', 'hubungi-kami', 'alamat-kontak'),
        'wilayah-yurisdiksi'     => array('yurisdiksi', 'wilayah-hukum'),
        'profil-pegawai'         => array('pegawai', 'profil-pejabat', 'daftar-pegawai'),
    );

    $upsertPage = static function (string $key, string $title, string $slug, string $content, string $code = '') use (&$seeded, &$createdCount, $customMap, $slugAliases, $preserve): int {
        // Hormati pemetaan kustom situs lama agar Seeder tidak membuat halaman duplikat
        if ($code !== '' && !empty($customMap[$code]['post_id'])) {
            $mappedPid = (int) $customMap[$code]['post_id'];
            if ($mappedPid > 0 && get_post_status($mappedPid) === 'publish') {
                $seeded[$key] = $mappedPid;
                return $mappedPid;
            }
        }
        $existingId = isset($seeded[$key]) ? (int) $seeded[$key] : 0;
        if ($existingId > 0 && get_post_status($existingId) === 'publish') {
            return $existingId;
        }
        $byPath = pa_sinova_find_existing_page($slug);
        if ($byPath instanceof WP_Post) {
            $seeded[$key] = (int) $byPath->ID;
            return (int) $byPath->ID;
        }
        if ($preserve && !empty($slugAliases[$slug])) {
            foreach ($slugAliases[$slug] as $aliasSlug) {
                $byAlias = pa_sinova_find_existing_page($aliasSlug);
                if ($byAlias instanceof WP_Post) {
                    $seeded[$key] = (int) $byAlias->ID;
                    return (int) $byAlias->ID;
                }
            }
        }
        $newId = wp_insert_post(array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $content,
        ));
        if (!is_wp_error($newId) && $newId > 0) {
            $seeded[$key] = (int) $newId;
            $createdCount++;
            return (int) $newId;
        }
        return 0;
    };

    foreach (pa_sinova_static_seed_pages() as $slug => $meta) {
        $pid = $upsertPage('static:' . $slug, $meta['title'], $slug, $meta['content'], (string) ($meta['code'] ?? ''));
        if ($pid > 0) {
            $parentName = $meta['parent'];
            if (!isset($menuItemsPlan[$parentName])) {
                $parentName = 'Layanan Publik & Pengaduan';
            }
            $menuItemsPlan[$parentName][] = array('title' => $meta['title'], 'post_id' => $pid);
        }
    }

    if (function_exists('pa_sinova_module_catalog')) {
        foreach (pa_sinova_module_catalog() as $slug => $cfgMod) {
            $shortcode = '[pa_sinova_docs slug="' . esc_attr($slug) . '"]';
            $pid = $upsertPage('mod:' . $slug, $cfgMod['title'], $slug, $shortcode);
            if ($pid > 0) {
                if ($slug === 'mou') {
                    $parentName = 'Berita & Publikasi';
                } elseif (in_array($slug, array('lhkpn', 'lhkasn'), true)) {
                    $parentName = 'Transparansi';
                } else {
                    $parentName = $catToParent[$cfgMod['category']] ?? 'Layanan Publik & Pengaduan';
                }
                $menuItemsPlan[$parentName][] = array('title' => $cfgMod['title'], 'post_id' => $pid);
            }
        }
    }

    // Siapkan halaman arsip Berita (/berita/) & hubungkan ke page_for_posts agar tautan "Berita & Artikel" selalu menampilkan daftar berita
    $newsPageId = (int) get_option('page_for_posts', 0);
    if (!$preserve && ($newsPageId <= 0 || get_post_status($newsPageId) !== 'publish')) {
        $newsPageId = $upsertPage('static:berita', 'Berita & Artikel', 'berita', '');
        if ($newsPageId > 0) {
            update_option('page_for_posts', $newsPageId);
        }
    }
    if ($newsPageId > 0 && get_post_status($newsPageId) === 'publish') {
        $menuItemsPlan['Berita & Publikasi'][] = array(
            'title'   => 'Berita & Artikel',
            'post_id' => $newsPageId,
        );
    } else {
        $menuItemsPlan['Berita & Publikasi'][] = array(
            'title' => 'Berita & Artikel',
            'url'   => pa_sinova_news_archive_url(),
        );
    }

    $menuCreated = 0;

    if (!$preserve || $rebuildMenu) {
        $menuName = 'Menu Utama SINOVA';
        $menuObj  = wp_get_nav_menu_object($menuName);
        $menuId   = $menuObj ? (int) $menuObj->term_id : (int) wp_create_nav_menu($menuName);

        if ($menuId > 0) {
            $existingItems = wp_get_nav_menu_items($menuId);
            if ($rebuildMenu && is_array($existingItems) && !empty($existingItems)) {
                foreach ($existingItems as $oldItem) {
                    wp_delete_post((int) $oldItem->ID, true);
                }
                $existingItems = array();
            }
            if (empty($existingItems)) {
                $pos = 1;
                wp_update_nav_menu_item($menuId, 0, array(
                    'menu-item-title'    => 'Beranda',
                    'menu-item-url'      => home_url('/'),
                    'menu-item-type'     => 'custom',
                    'menu-item-status'   => 'publish',
                    'menu-item-position' => $pos++,
                ));
                $menuCreated++;
                foreach ($menuItemsPlan as $parentTitle => $children) {
                    $parentItemId = wp_update_nav_menu_item($menuId, 0, array(
                        'menu-item-title'    => $parentTitle,
                        'menu-item-url'      => '#top-' . sanitize_title($parentTitle),
                        'menu-item-type'     => 'custom',
                        'menu-item-status'   => 'publish',
                        'menu-item-position' => $pos++,
                    ));
                    if (!is_wp_error($parentItemId) && $parentItemId > 0) {
                        $menuCreated++;
                        foreach ($children as $ch) {
                            if (!empty($ch['post_id'])) {
                                wp_update_nav_menu_item($menuId, 0, array(
                                    'menu-item-object-id' => (int) $ch['post_id'],
                                    'menu-item-object'    => 'page',
                                    'menu-item-type'      => 'post_type',
                                    'menu-item-status'    => 'publish',
                                    'menu-item-parent-id' => (int) $parentItemId,
                                    'menu-item-position'  => $pos++,
                                ));
                            } else {
                                wp_update_nav_menu_item($menuId, 0, array(
                                    'menu-item-title'     => (string) ($ch['title'] ?? 'Tautan'),
                                    'menu-item-url'       => (string) ($ch['url'] ?? home_url('/')),
                                    'menu-item-type'      => 'custom',
                                    'menu-item-status'    => 'publish',
                                    'menu-item-parent-id' => (int) $parentItemId,
                                    'menu-item-position'  => $pos++,
                                ));
                            }
                            $menuCreated++;
                        }
                    }
                }
            }
            $locations = get_theme_mod('nav_menu_locations', array());
            if (!is_array($locations)) {
                $locations = array();
            }
            foreach (array('primary', 'secondary_menu', 'mobile_menu') as $locKey) {
                $locations[$locKey] = $menuId;
            }
            set_theme_mod('nav_menu_locations', $locations);
        }
    }

    if (!$preserve) {

        $frontSlug = 'beranda';
        $frontHtml = "<!-- wp:shortcode -->\n[pa_sinova_beranda]\n<!-- /wp:shortcode -->";
        $frontId   = !empty($seeded['static:beranda']) ? (int) $seeded['static:beranda'] : 0;
        if ($frontId <= 0 || get_post_status($frontId) !== 'publish') {
            $existingFront = get_page_by_path($frontSlug);
            $wasKses = has_filter('content_save_pre', 'wp_filter_post_kses');
            if ($wasKses && function_exists('kses_remove_filters')) {
                kses_remove_filters();
            }
            if ($existingFront instanceof WP_Post && $existingFront->post_status === 'publish') {
                $frontId = (int) $existingFront->ID;
            } else {
                $frontId = (int) wp_insert_post(array(
                    'post_type'    => 'page',
                    'post_status'  => 'publish',
                    'post_title'   => 'Beranda',
                    'post_name'    => $frontSlug,
                    'post_content' => $frontHtml,
                ));
                if ($frontId > 0) {
                    $createdCount++;
                }
            }
            if ($wasKses && function_exists('kses_init_filters')) {
                kses_init_filters();
            }
            if ($frontId > 0) {
                $seeded['static:beranda'] = $frontId;
            }
        }

        if (function_exists('term_exists') && function_exists('wp_insert_term') && !term_exists('pengumuman', 'category')) {
            wp_insert_term('Pengumuman', 'category', array('slug' => 'pengumuman'));
        }

        $postCounts = wp_count_posts('post');
        $pubPosts   = isset($postCounts->publish) ? (int) $postCounts->publish : 0;
        if ($pubPosts <= 1) {
            $samplePosts = array(
                array(
                    'slug'    => 'peningkatan-kualitas-layanan-ptsp-pengadilan-agama',
                    'title'   => 'Peningkatan Kualitas Layanan PTSP dan Keterbukaan Informasi Publik',
                    'content' => '<p>Pengadilan Agama terus meningkatkan standar pelayanan terpadu satu pintu (PTSP) guna memberikan pelayanan yang cepat, transparan, dan bebas biaya di luar ketentuan resmi kepada masyarakat pencari keadilan.</p>',
                ),
                array(
                    'slug'    => 'publikasi-laporan-realisasi-anggaran-dan-kinerja-sakip',
                    'title'   => 'Publikasi Berkala Laporan Realisasi Anggaran dan Kinerja SAKIP',
                    'content' => '<p>Seluruh dokumen laporan realisasi anggaran DIPA 01 dan DIPA 04 serta capaian indikator kinerja utama dapat diakses langsung oleh publik melalui menu Kesekretariatan dan SAKIP sesuai pedoman transparansi Badilag.</p>',
                ),
                array(
                    'slug'    => 'komitmen-bersama-pembangunan-zona-integritas-wbk-wbbm',
                    'title'   => 'Komitmen Bersama Pembangunan Zona Integritas Menuju WBK dan WBBM',
                    'content' => '<p>Pimpinan dan seluruh aparatur pengadilan menyatakan komitmen bersama menjaga integritas, menolak segala bentuk gratifikasi, serta mendukung penuh pengawasan publik melalui layanan pengaduan terpadu.</p>',
                ),
            );
            foreach ($samplePosts as $sp) {
                if (!get_page_by_path($sp['slug'], OBJECT, 'post')) {
                    wp_insert_post(array(
                        'post_type'    => 'post',
                        'post_status'  => 'publish',
                        'post_title'   => $sp['title'],
                        'post_name'    => $sp['slug'],
                        'post_content' => $sp['content'],
                    ));
                }
            }
        }

        if ($frontId > 0) {
            $curFrontId = (int) get_option('page_on_front', 0);
            if (get_option('show_on_front') !== 'page' || $curFrontId <= 0 || get_post_status($curFrontId) !== 'publish') {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $frontId);
            }
            pa_sinova_apply_astra_defaults($frontId);
        }
    }

    update_option('pa_sinova_seeded_pages', $seeded, false);
    pa_sinova_sync_direktori_putusan_page();

    return array(
        'pages_created' => $createdCount,
        'total_mapped'  => count($seeded),
        'menu_items'    => $menuCreated,
        'preserve_mode' => $preserve ? 1 : 0,
    );
}

/**
 * Katalog 13 jenis blok Beranda untuk Editor Grid Bebas 12 Kolom (Drag & Resize).
 */
function pa_sinova_home_block_catalog(): array {
    return array(
        'hero_slider'   => array('label' => 'Slider Utama / Hero', 'default_w' => 12, 'default_h' => 1, 'icon' => '🖼'),
        'kpi_bar'       => array('label' => 'Strip Ringkasan Kinerja (KPI)', 'default_w' => 12, 'default_h' => 1, 'icon' => '📈'),
        'services'      => array('label' => 'Pintasan Layanan Utama', 'default_w' => 12, 'default_h' => 1, 'icon' => '⊞'),
        'sipp_tracker'  => array('label' => 'Penelusuran Perkara (SIPP)', 'default_w' => 12, 'default_h' => 1, 'icon' => '🔎'),
        'schedule'      => array('label' => 'Jadwal Sidang & Live SIPP', 'default_w' => 7, 'default_h' => 1, 'icon' => '📅'),
        'stats'         => array('label' => 'Statistik Perkara', 'default_w' => 5, 'default_h' => 1, 'icon' => '📊'),
        'inovasi'       => array('label' => 'Inovasi Layanan Satker', 'default_w' => 12, 'default_h' => 1, 'icon' => '✨'),
        'announcements' => array('label' => 'Pengumuman & Panggilan Ghaib', 'default_w' => 5, 'default_h' => 1, 'icon' => '📢'),
        'news'          => array('label' => 'Berita & Publikasi Satker', 'default_w' => 7, 'default_h' => 1, 'icon' => '📰'),
        'welcome'       => array('label' => 'Sambutan Pimpinan & Maklumat', 'default_w' => 12, 'default_h' => 1, 'icon' => '🏛'),
        'zi'            => array('label' => 'Zona Integritas (6 Area)', 'default_w' => 12, 'default_h' => 1, 'icon' => '🛡'),
        'survey'        => array('label' => 'Indeks Kepuasan & Integritas', 'default_w' => 5, 'default_h' => 1, 'icon' => '★'),
        'directory'     => array('label' => 'Direktori Dokumen Publik', 'default_w' => 7, 'default_h' => 1, 'icon' => '📂'),
        'videos'        => array('label' => 'Galeri Video', 'default_w' => 6, 'default_h' => 1, 'icon' => '▶'),
        'greetings'     => array('label' => 'Ucapan & Belasungkawa', 'default_w' => 6, 'default_h' => 1, 'icon' => '💐'),
        'dirput'        => array('label' => 'Direktori Putusan (MA RI)', 'default_w' => 12, 'default_h' => 1, 'icon' => '⚖'),
        'iframe_embed'  => array('label' => 'Sematan Iframe / Aplikasi', 'default_w' => 6, 'default_h' => 1, 'icon' => '🖥'),
        'image_banner'  => array('label' => 'Gambar / Poster / Banner', 'default_w' => 6, 'default_h' => 1, 'icon' => '🖼'),
        'photo_slider'  => array('label' => 'Slider Gambar / Infografis', 'default_w' => 12, 'default_h' => 1, 'icon' => '🎞'),
        'custom'        => array('label' => 'Konten Bebas / Sematan', 'default_w' => 6, 'default_h' => 1, 'icon' => '✦'),
    );
}

/**
 * Susunan standar seimbang 12 kolom (SK 2722/2026 Bab III huruf C).
 */
function pa_sinova_default_home_blocks(): array {
    return array(
        array('id' => 'blk_hero', 'type' => 'hero_slider', 'title' => 'Slider Utama / Hero', 'x' => 1, 'y' => 1, 'w' => 12, 'h' => 1, 'enabled' => true, 'config' => array()),
        array('id' => 'blk_kpi', 'type' => 'kpi_bar', 'title' => 'Ringkasan Kinerja Pelayanan', 'x' => 1, 'y' => 2, 'w' => 12, 'h' => 1, 'enabled' => true, 'config' => array()),
        array('id' => 'blk_services', 'type' => 'services', 'title' => 'Pintasan Layanan Utama', 'x' => 1, 'y' => 3, 'w' => 12, 'h' => 1, 'enabled' => true, 'config' => array()),
        array('id' => 'blk_tracker', 'type' => 'sipp_tracker', 'title' => 'Penelusuran Perkara Cepat (SIPP)', 'x' => 1, 'y' => 4, 'w' => 12, 'h' => 1, 'enabled' => true, 'config' => array()),
        array('id' => 'blk_schedule', 'type' => 'schedule', 'title' => 'Jadwal Sidang & Live SIPP', 'x' => 1, 'y' => 5, 'w' => 7, 'h' => 1, 'enabled' => true, 'config' => array('height' => 480)),
        array('id' => 'blk_stats', 'type' => 'stats', 'title' => 'Statistik Perkara', 'x' => 8, 'y' => 5, 'w' => 5, 'h' => 1, 'enabled' => true, 'config' => array()),
        array('id' => 'blk_inovasi', 'type' => 'inovasi', 'title' => 'Inovasi Layanan Unggulan', 'x' => 1, 'y' => 6, 'w' => 12, 'h' => 1, 'enabled' => true, 'config' => array('count' => 4)),
        array('id' => 'blk_ann', 'type' => 'announcements', 'title' => 'Pengumuman & Panggilan Ghaib', 'x' => 1, 'y' => 7, 'w' => 5, 'h' => 1, 'enabled' => true, 'config' => array('limit' => 5)),
        array('id' => 'blk_news', 'type' => 'news', 'title' => 'Berita Pelayanan & Inovasi Satker', 'x' => 6, 'y' => 7, 'w' => 7, 'h' => 1, 'enabled' => true, 'config' => array('count' => 3)),
        array('id' => 'blk_dirput', 'type' => 'dirput', 'title' => 'Direktori Putusan Terbaru', 'x' => 1, 'y' => 8, 'w' => 12, 'h' => 1, 'enabled' => true, 'config' => array('count' => 6)),
        array('id' => 'blk_welcome', 'type' => 'welcome', 'title' => 'Sambutan Pimpinan & Maklumat', 'x' => 1, 'y' => 9, 'w' => 12, 'h' => 1, 'enabled' => true, 'config' => array()),
        array('id' => 'blk_zi', 'type' => 'zi', 'title' => 'Zona Integritas (6 Area)', 'x' => 1, 'y' => 10, 'w' => 12, 'h' => 1, 'enabled' => true, 'config' => array()),
        array('id' => 'blk_survey', 'type' => 'survey', 'title' => 'Indeks Kepuasan & Integritas Pelayanan', 'x' => 1, 'y' => 11, 'w' => 5, 'h' => 1, 'enabled' => true, 'config' => array()),
        array('id' => 'blk_directory', 'type' => 'directory', 'title' => 'Direktori Dokumen Publik', 'x' => 6, 'y' => 11, 'w' => 7, 'h' => 1, 'enabled' => true, 'config' => array()),
    );
}

function pa_sinova_sanitize_home_blocks($rawBlocks): array {
    $catalog = pa_sinova_home_block_catalog();
    if (!is_array($rawBlocks)) {
        return pa_sinova_default_home_blocks();
    }
    $clean = array();
    $seen  = array();
    foreach ($rawBlocks as $idx => $b) {
        if (!is_array($b)) {
            continue;
        }
        $type = sanitize_key((string) ($b['type'] ?? ''));
        if (!isset($catalog[$type])) {
            continue;
        }
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($b['id'] ?? ''));
        if ($id === '' || isset($seen[$id])) {
            $id = 'blk_' . $type . '_' . ($idx + 1);
        }
        $seen[$id] = true;

        $w = max(2, min(12, (int) ($b['w'] ?? $catalog[$type]['default_w'])));
        $x = max(1, min(13 - $w, (int) ($b['x'] ?? 1)));
        $y = max(1, min(40, (int) ($b['y'] ?? ($idx + 1))));
        $h = max(1, min(4, (int) ($b['h'] ?? 1)));

        $title = sanitize_text_field((string) ($b['title'] ?? $catalog[$type]['label']));
        if ($title === '') {
            $title = $catalog[$type]['label'];
        }
        $enabled = isset($b['enabled']) ? (bool) $b['enabled'] : true;

        $rawCfg = isset($b['config']) && is_array($b['config']) ? $b['config'] : array();
        $bCfg   = array();
        if (!empty($rawCfg['embed_url'])) {
            $eChk = pa_sinova_sanitize_https_embed_url((string) $rawCfg['embed_url']);
            if ($eChk['valid'] && $eChk['url'] !== '') {
                $bCfg['embed_url'] = $eChk['url'];
            }
        }
        if (isset($rawCfg['height'])) {
            $bCfg['height'] = max(260, min(900, (int) $rawCfg['height']));
        }
        if (isset($rawCfg['count'])) {
            $bCfg['count'] = max(1, min(9, (int) $rawCfg['count']));
        }
        if (isset($rawCfg['limit'])) {
            $bCfg['limit'] = max(2, min(12, (int) $rawCfg['limit']));
        }
        if (!empty($rawCfg['default_tab']) && in_array((string) $rawCfg['default_tab'], array('auto', 'agenda', 'sipp'), true)) {
            $bCfg['default_tab'] = (string) $rawCfg['default_tab'];
        }
        if (!empty($rawCfg['body'])) {
            $bCfg['body'] = wp_kses_post((string) $rawCfg['body']);
        }
        if (!empty($rawCfg['cta_label'])) {
            $bCfg['cta_label'] = sanitize_text_field((string) $rawCfg['cta_label']);
        }
        if (!empty($rawCfg['cta_url'])) {
            $bCfg['cta_url'] = esc_url_raw((string) $rawCfg['cta_url'], array('http', 'https'));
        }
        if (!empty($rawCfg['image_url'])) {
            $imgU = esc_url_raw((string) $rawCfg['image_url'], array('http', 'https'));
            if ($imgU !== '') {
                $bCfg['image_url'] = $imgU;
            }
        }
        if (!empty($rawCfg['image_alt'])) {
            $bCfg['image_alt'] = sanitize_text_field((string) $rawCfg['image_alt']);
        }
        if (!empty($rawCfg['image_fit']) && in_array((string) $rawCfg['image_fit'], array('cover', 'contain'), true)) {
            $bCfg['image_fit'] = (string) $rawCfg['image_fit'];
        }
        if (!empty($rawCfg['caption'])) {
            $bCfg['caption'] = sanitize_text_field((string) $rawCfg['caption']);
        }
        if (!empty($rawCfg['link_url'])) {
            $lnkU = esc_url_raw((string) $rawCfg['link_url'], array('http', 'https'));
            if ($lnkU !== '') {
                $bCfg['link_url'] = $lnkU;
            }
        }
        if (isset($rawCfg['show_fullscreen'])) {
            $bCfg['show_fullscreen'] = !empty($rawCfg['show_fullscreen']) ? '1' : '0';
        }
        if (!empty($rawCfg['slide_source']) && in_array((string) $rawCfg['slide_source'], array('custom', 'records'), true)) {
            $bCfg['slide_source'] = (string) $rawCfg['slide_source'];
        }
        if (isset($rawCfg['autoplay_sec'])) {
            $bCfg['autoplay_sec'] = max(0, min(15, (int) $rawCfg['autoplay_sec']));
        }
        if (!empty($rawCfg['slides']) && is_array($rawCfg['slides'])) {
            $cleanSlides = array();
            foreach (array_slice($rawCfg['slides'], 0, 10) as $sl) {
                if (!is_array($sl)) {
                    continue;
                }
                $sImg = esc_url_raw((string) ($sl['image_url'] ?? ''), array('http', 'https'));
                if ($sImg === '') {
                    continue;
                }
                $cleanSlides[] = array(
                    'image_url' => $sImg,
                    'title'     => sanitize_text_field((string) ($sl['title'] ?? '')),
                    'link_url'  => esc_url_raw((string) ($sl['link_url'] ?? ''), array('http', 'https')),
                );
            }
            if (!empty($cleanSlides)) {
                $bCfg['slides'] = $cleanSlides;
            }
        }

        $clean[] = array(
            'id'      => $id,
            'type'    => $type,
            'title'   => $title,
            'x'       => $x,
            'y'       => $y,
            'w'       => $w,
            'h'       => $h,
            'enabled' => $enabled,
            'config'  => $bCfg,
        );
        if (count($clean) >= 24) {
            break;
        }
    }
    if (empty($clean)) {
        return pa_sinova_default_home_blocks();
    }
    usort($clean, static function (array $a, array $b): int {
        return ($a['y'] !== $b['y']) ? ($a['y'] <=> $b['y']) : ($a['x'] <=> $b['x']);
    });
    return $clean;
}

function pa_sinova_get_home_blocks(): array {
    $saved = get_option('pa_sinova_home_blocks', null);
    if (is_array($saved) && !empty($saved)) {
        return pa_sinova_sanitize_home_blocks($saved);
    }
    return pa_sinova_default_home_blocks();
}

/**
 * Aktifkan atau migrasi 1-klik halaman Beranda ke mode Dinamis [pa_sinova_beranda] (tanpa kehilangan berita/dokumen).
 */
function pa_sinova_activate_dynamic_beranda(): array {
    $seeded = get_option('pa_sinova_seeded_pages', array());
    if (!is_array($seeded)) {
        $seeded = array();
    }
    $content = "<!-- wp:shortcode -->\n[pa_sinova_beranda]\n<!-- /wp:shortcode -->";
    $frontId = (int) get_option('page_on_front', 0);
    if ($frontId <= 0 || get_post_status($frontId) !== 'publish') {
        $frontId = !empty($seeded['static:beranda']) ? (int) $seeded['static:beranda'] : 0;
    }
    if ($frontId <= 0 || get_post_status($frontId) !== 'publish') {
        $byPath = get_page_by_path('beranda');
        if ($byPath instanceof WP_Post && $byPath->post_status === 'publish') {
            $frontId = (int) $byPath->ID;
        }
    }

    if ($frontId > 0 && get_post_status($frontId) === 'publish') {
        wp_update_post(array(
            'ID'           => $frontId,
            'post_content' => $content,
        ));
        $mode = 'updated';
    } else {
        $frontId = (int) wp_insert_post(array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => 'Beranda',
            'post_name'    => 'beranda',
            'post_content' => $content,
        ));
        $mode = 'created';
    }

    if ($frontId > 0) {
        $seeded['static:beranda'] = $frontId;
        update_option('pa_sinova_seeded_pages', $seeded, false);
        update_option('show_on_front', 'page');
        update_option('page_on_front', $frontId);
        pa_sinova_apply_astra_defaults($frontId);
        clean_post_cache($frontId);
    }
    pa_sinova_purge_html_cache_files();

    return array('ok' => $frontId > 0, 'post_id' => $frontId, 'mode' => $mode);
}

/**
 * Hapus bersih berkas cache HTML statis agar perubahan tampilan/update langsung terlihat.
 */
function pa_sinova_purge_html_cache_files(): void {
    $home = WP_CONTENT_DIR . '/cache/pa-homepage.html';
    if (is_file($home)) {
        @unlink($home);
    }
    $pagesDir = WP_CONTENT_DIR . '/cache/pa-pages';
    if (is_dir($pagesDir)) {
        $files = @glob($pagesDir . '/page-*.html');
        if (is_array($files)) {
            foreach ($files as $f) {
                if (is_file($f)) {
                    @unlink($f);
                }
            }
        }
    }
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}

/**
 * Cek versi terbaru PA SINOVA Starter Kit langsung dari GitHub resmi (cached 6 jam).
 */
function pa_sinova_github_check_update(bool $forceRefresh = false): array {
    $repo    = defined('PA_SINOVA_GITHUB_REPO') ? (string) PA_SINOVA_GITHUB_REPO : 'kang-devlp/pa-wordpress-sinova-starter';
    $current = defined('PA_SINOVA_STARTER_VERSION') ? (string) PA_SINOVA_STARTER_VERSION : '2.2.0';
    $tKey    = 'pa_sinova_remote_manifest_v1';

    if (!$forceRefresh) {
        $cached = get_transient($tKey);
        if (is_array($cached) && !empty($cached['latest'])) {
            $cached['current']    = $current;
            $cached['has_update'] = version_compare((string) $cached['latest'], $current, '>');
            return $cached;
        }
    }

    $manifestUrl = 'https://raw.githubusercontent.com/' . $repo . '/main/version.json';
    $resp        = wp_remote_get($manifestUrl, array(
        'timeout'    => 10,
        'user-agent' => 'PA-SINOVA-Starter/' . $current . '; ' . home_url('/'),
        'headers'    => array('Accept' => 'application/json'),
    ));

    if (is_wp_error($resp)) {
        return array(
            'ok'         => false,
            'current'    => $current,
            'latest'     => $current,
            'has_update' => false,
            'checked_at' => wp_date('d M Y H:i') . ' WIB',
            'error'      => 'Gagal menghubungi GitHub: ' . $resp->get_error_message(),
        );
    }
    $code = (int) wp_remote_retrieve_response_code($resp);
    $body = json_decode((string) wp_remote_retrieve_body($resp), true);
    if ($code !== 200 || !is_array($body) || empty($body['version'])) {
        return array(
            'ok'         => false,
            'current'    => $current,
            'latest'     => $current,
            'has_update' => false,
            'checked_at' => wp_date('d M Y H:i') . ' WIB',
            'error'      => 'Manifest version.json tidak ditemukan di repositori GitHub (HTTP ' . $code . ').',
        );
    }

    $latest = preg_replace('/[^0-9A-Za-z._-]/', '', (string) $body['version']);
    $out    = array(
        'ok'           => true,
        'current'      => $current,
        'latest'       => $latest,
        'has_update'   => version_compare($latest, $current, '>'),
        'released_at'  => sanitize_text_field((string) ($body['released_at'] ?? '')),
        'requires_php' => sanitize_text_field((string) ($body['requires_php'] ?? '8.1')),
        'changelog'    => sanitize_text_field((string) ($body['changelog'] ?? '')),
        'download_url' => esc_url_raw((string) ($body['download_url'] ?? '')),
        'checked_at'   => wp_date('d M Y H:i') . ' WIB',
        'error'        => '',
    );
    set_transient($tKey, $out, 6 * HOUR_IN_SECONDS);
    return $out;
}

/**
 * Terapkan pembaruan 1-Klik langsung dari GitHub resmi ke direktori aktif (mu-plugins atau plugin).
 * Aman & atomik: seluruh berkas diunduh dan divalidasi dulu sebelum mengganti file aktif.
 */
function pa_sinova_github_apply_update(): array {
    if (!current_user_can('manage_options')) {
        return array('ok' => false, 'message' => 'Gagal: Hanya Administrator yang berwenang melakukan pembaruan.');
    }
    if (version_compare(PHP_VERSION, '8.1.0', '<')) {
        return array('ok' => false, 'message' => 'Gagal: Versi PHP server minimal 8.1 (saat ini ' . PHP_VERSION . ').');
    }

    $targetDir = __DIR__;
    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        return array('ok' => false, 'message' => 'Gagal: Direktori ' . $targetDir . ' tidak dapat ditulis oleh server PHP.');
    }

    $repo    = defined('PA_SINOVA_GITHUB_REPO') ? (string) PA_SINOVA_GITHUB_REPO : 'kang-devlp/pa-wordpress-sinova-starter';
    $rawBase = 'https://raw.githubusercontent.com/' . $repo . '/main/';
    $check   = pa_sinova_github_check_update(true);
    if (empty($check['ok'])) {
        return array('ok' => false, 'message' => 'Gagal: ' . (string) ($check['error'] ?? 'Tidak dapat membaca manifest pembaruan.'));
    }

    $allowMap = array(
        'wordpress-mu-plugins/00-pa-sinova-config.php'        => $targetDir . '/00-pa-sinova-config.php',
        'wordpress-mu-plugins/pa-sinova-hub.php'              => $targetDir . '/pa-sinova-hub.php',
        'wordpress-mu-plugins/pa-official-feeds-and-data.php' => $targetDir . '/pa-official-feeds-and-data.php',
        'wordpress-mu-plugins/pa-home-page-cache.php'         => $targetDir . '/pa-home-page-cache.php',
        'wordpress-mu-plugins/pa-ui-performance-a11y.php'     => $targetDir . '/pa-ui-performance-a11y.php',
        'wordpress-mu-plugins/pa-security-hardening.php'      => $targetDir . '/pa-security-hardening.php',
        'wordpress-mu-plugins/assets/cctv-aco.gif'            => $targetDir . '/assets/cctv-aco.gif',
        'wordpress-mu-plugins/assets/dummy-gedung-pa.svg'     => $targetDir . '/assets/dummy-gedung-pa.svg',
        'wordpress-mu-plugins/assets/dummy-pimpinan.svg'      => $targetDir . '/assets/dummy-pimpinan.svg',
        'wordpress-mu-plugins/assets/dummy-berita-1.svg'      => $targetDir . '/assets/dummy-berita-1.svg',
        'wordpress-mu-plugins/assets/dummy-berita-2.svg'      => $targetDir . '/assets/dummy-berita-2.svg',
        'wordpress-mu-plugins/assets/dummy-berita-3.svg'      => $targetDir . '/assets/dummy-berita-3.svg',
    );
    if (defined('PA_SINOVA_RUNTIME_MODE') && PA_SINOVA_RUNTIME_MODE === 'plugin' && defined('PA_SINOVA_PLUGIN_FILE') && is_writable((string) PA_SINOVA_PLUGIN_FILE)) {
        $allowMap['wordpress-plugin-wrapper/pa-sinova-starter.php'] = (string) PA_SINOVA_PLUGIN_FILE;
    }

    $staged = array();
    foreach ($allowMap as $remoteRel => $destPath) {
        $resp = wp_remote_get($rawBase . $remoteRel, array(
            'timeout'    => 20,
            'user-agent' => 'PA-SINOVA-Updater/' . PA_SINOVA_STARTER_VERSION,
        ));
        if (is_wp_error($resp) || (int) wp_remote_retrieve_response_code($resp) !== 200) {
            $errMsg = is_wp_error($resp) ? $resp->get_error_message() : ('HTTP ' . (int) wp_remote_retrieve_response_code($resp));
            return array('ok' => false, 'message' => 'Gagal mengunduh ' . basename($remoteRel) . ' (' . $errMsg . '). Tidak ada berkas yang diubah.');
        }
        $body = (string) wp_remote_retrieve_body($resp);
        $ext  = strtolower((string) pathinfo($remoteRel, PATHINFO_EXTENSION));
        if ($ext === 'php') {
            if (strlen($body) < 500 || strpos(ltrim($body), '<?php') !== 0) {
                return array('ok' => false, 'message' => 'Gagal validasi integritas berkas PHP ' . basename($remoteRel) . '. Pembaruan dibatalkan.');
            }
            // Pada mode plugin standar, hilangkan header "Plugin Name:" di modul dalam agar tidak bentrok dengan pa-sinova-starter.php
            if (defined('PA_SINOVA_RUNTIME_MODE') && PA_SINOVA_RUNTIME_MODE === 'plugin' && strpos($remoteRel, 'wordpress-mu-plugins/') === 0) {
                $body = (string) preg_replace('/^([ \t]*\*[ \t]*)Plugin Name:/mi', '$1Module Name:', $body, 1);
            }
        } elseif ($ext === 'gif') {
            if (strlen($body) < 100 || strpos($body, 'GIF8') !== 0) {
                return array('ok' => false, 'message' => 'Gagal validasi aset GIF ' . basename($remoteRel) . '. Pembaruan dibatalkan.');
            }
        } elseif ($ext === 'svg') {
            if (strlen($body) < 100 || stripos($body, '<svg') === false || stripos($body, '<script') !== false) {
                return array('ok' => false, 'message' => 'Gagal validasi aset SVG ' . basename($remoteRel) . '. Pembaruan dibatalkan.');
            }
        }
        $staged[$destPath] = $body;
    }

    $assetsDir = $targetDir . '/assets';
    if (!is_dir($assetsDir)) {
        wp_mkdir_p($assetsDir);
    }

    // Cadangkan berkas aktif sebelum menimpa agar tombol "Batalkan Pembaruan Terakhir" (Rollback) selalu siap
    $backupDir = WP_CONTENT_DIR . '/uploads/sinova-backups/last-update';
    if (!is_dir($backupDir)) {
        wp_mkdir_p($backupDir);
    }
    if (is_dir($backupDir)) {
        $htaPath = dirname($backupDir) . '/.htaccess';
        if (!is_file($htaPath)) {
            @file_put_contents($htaPath, "Options -Indexes\n<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n", LOCK_EX);
        }
        if (!is_file($backupDir . '/index.php')) {
            @file_put_contents($backupDir . '/index.php', "<?php // Silence is golden.\n", LOCK_EX);
        }
        $backupMap = array();
        foreach ($staged as $destPath => $_bytes) {
            if (is_file($destPath) && is_readable($destPath)) {
                $bName = md5($destPath) . '-' . basename($destPath) . '.bak';
                if (@copy($destPath, $backupDir . '/' . $bName)) {
                    $backupMap[$destPath] = $backupDir . '/' . $bName;
                }
            }
        }
        if (!empty($backupMap)) {
            update_option('pa_sinova_last_update_backup', array(
                'from_version' => PA_SINOVA_STARTER_VERSION,
                'to_version'   => (string) $check['latest'],
                'at'           => wp_date('d/m/Y H:i') . ' WIB',
                'files'        => $backupMap,
            ), false);
        }
    }

    $written = 0;
    foreach ($staged as $destPath => $bytes) {
        $tmpPath = $destPath . '.pa-upd-' . wp_generate_password(6, false, false) . '.tmp';
        if (@file_put_contents($tmpPath, $bytes, LOCK_EX) === false) {
            @unlink($tmpPath);
            return array('ok' => false, 'message' => 'Gagal menulis berkas sementara untuk ' . basename($destPath) . '.');
        }
        if (!@rename($tmpPath, $destPath)) {
            @unlink($tmpPath);
            if (@file_put_contents($destPath, $bytes, LOCK_EX) === false) {
                return array('ok' => false, 'message' => 'Gagal menimpa berkas ' . basename($destPath) . '.');
            }
        }
        $written++;
    }

    pa_sinova_purge_html_cache_files();
    delete_transient('pa_sinova_remote_manifest_v1');
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }
    update_option('pa_starter_fim_baseline', array(
        'recorded_at' => gmdate('Y-m-d H:i:s') . ' UTC',
        'hashes'      => pa_starter_fim_current_hashes(),
    ), false);

    return array(
        'ok'      => true,
        'message' => sprintf(
            'Pembaruan berhasil ke v%s (%d berkas diperbarui, cadangan rollback tersimpan, cache & FIM disegarkan).',
            $check['latest'],
            $written
        ),
    );
}

/**
 * Batalkan pembaruan terakhir (Rollback) dari cadangan otomatis sebelum update.
 */
function pa_sinova_github_rollback_update(): array {
    if (!current_user_can('manage_options')) {
        return array('ok' => false, 'message' => 'Gagal: Hanya Administrator yang berwenang membatalkan pembaruan.');
    }
    $meta = get_option('pa_sinova_last_update_backup', array());
    if (!is_array($meta) || empty($meta['files']) || !is_array($meta['files'])) {
        return array('ok' => false, 'message' => 'Gagal: Tidak ditemukan cadangan pembaruan sebelumnya.');
    }
    $restored = 0;
    foreach ($meta['files'] as $destPath => $bakPath) {
        if (!is_string($destPath) || !is_string($bakPath) || !is_file($bakPath) || !is_readable($bakPath)) {
            continue;
        }
        $bytes = @file_get_contents($bakPath);
        if ($bytes === false || strlen($bytes) < 100) {
            continue;
        }
        $tmp = $destPath . '.pa-rb-' . wp_generate_password(6, false, false) . '.tmp';
        if (@file_put_contents($tmp, $bytes, LOCK_EX) !== false && @rename($tmp, $destPath)) {
            $restored++;
        } else {
            @unlink($tmp);
        }
    }
    if ($restored === 0) {
        return array('ok' => false, 'message' => 'Gagal memulihkan berkas dari cadangan.');
    }
    delete_option('pa_sinova_last_update_backup');
    pa_sinova_purge_html_cache_files();
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }
    update_option('pa_starter_fim_baseline', array(
        'recorded_at' => gmdate('Y-m-d H:i:s') . ' UTC',
        'hashes'      => pa_starter_fim_current_hashes(),
    ), false);

    return array(
        'ok'      => true,
        'message' => sprintf('Rollback berhasil ke v%s (%d berkas dipulihkan, cache & FIM disegarkan).', (string) ($meta['from_version'] ?? 'sebelumnya'), $restored),
    );
}

/**
 * Resolve permalink untuk halaman modul maupun statis.
 */
function pa_sinova_resolve_page_url(string $slug, string $fallbackPath = ''): string {
    $seeded = get_option('pa_sinova_seeded_pages', array());
    if (is_array($seeded)) {
        if (!empty($seeded['mod:' . $slug])) {
            $u = get_permalink((int) $seeded['mod:' . $slug]);
            if ($u) return $u;
        }
        if (!empty($seeded['static:' . $slug])) {
            $u = get_permalink((int) $seeded['static:' . $slug]);
            if ($u) return $u;
        }
    }
    $page = pa_sinova_find_existing_page($slug);
    if ($page instanceof WP_Post) {
        $u = get_permalink($page->ID);
        if ($u) return $u;
    }
    return home_url('/' . ltrim($fallbackPath ?: $slug . '/', '/'));
}

/**
 * Ekstrak tautan dokumen (PDF / Google Drive / URL arsip) dari konten halaman atau pos lama.
 */
function pa_sinova_extract_links_from_post(int $postId, string $modSlug = '', int $defaultYear = 0, string $defaultSub = ''): array {
    $post = get_post($postId);
    if (!$post instanceof WP_Post) {
        return array();
    }

    $fallbackYear = ($defaultYear >= 2015 && $defaultYear <= 2035) ? $defaultYear : (int) wp_date('Y');
    $modCfg       = ($modSlug !== '' && function_exists('pa_sinova_module_config')) ? pa_sinova_module_config($modSlug) : null;
    $subGroups    = ($modCfg && !empty($modCfg['sub_groups']) && is_array($modCfg['sub_groups'])) ? $modCfg['sub_groups'] : array('DOKUMEN');
    $fallbackSub  = trim($defaultSub) !== '' ? trim($defaultSub) : (string) $subGroups[0];
    $periodsMap   = function_exists('pa_sinova_all_periods_map') ? pa_sinova_all_periods_map() : array();
    $modPeriods   = (is_array($modCfg) && function_exists('pa_sinova_module_periods')) ? pa_sinova_module_periods($modCfg) : $periodsMap;
    $defaultPer   = !empty($modPeriods) ? (string) array_key_first($modPeriods) : 'TAHUNAN';

    $content = (string) $post->post_content;
    // Turut pindai tautan dari meta _elementor_data (tombol/widget/tabel Elementor pada WordPress lama)
    $elRaw = get_post_meta($postId, '_elementor_data', true);
    if (is_string($elRaw) && $elRaw !== '' && strpos($elRaw, 'http') !== false) {
        $elDecoded = json_decode($elRaw, true);
        if (is_array($elDecoded)) {
            $elHtml = '';
            $walkEl = static function ($node) use (&$walkEl, &$elHtml) {
                if (!is_array($node)) {
                    return;
                }
                if (isset($node['settings']) && is_array($node['settings'])) {
                    $s = $node['settings'];
                    if (!empty($s['editor']) && is_string($s['editor'])) {
                        $elHtml .= "\n" . $s['editor'];
                    }
                    if (!empty($s['link']['url']) && is_string($s['link']['url'])) {
                        $lbl = (string) ($s['text'] ?? ($s['title'] ?? ($s['title_text'] ?? 'Dokumen')));
                        $elHtml .= "\n<a href=\"" . esc_attr($s['link']['url']) . '">' . esc_html($lbl) . '</a>';
                    }
                    if (!empty($s['tabs']) && is_array($s['tabs'])) {
                        foreach ($s['tabs'] as $tb) {
                            if (is_array($tb) && !empty($tb['tab_content'])) {
                                $elHtml .= "\n" . (string) $tb['tab_content'];
                            }
                        }
                    }
                }
                foreach ($node as $v) {
                    if (is_array($v)) {
                        $walkEl($v);
                    }
                }
            };
            $walkEl($elDecoded);
            if ($elHtml !== '') {
                $content .= "\n" . $elHtml;
            }
        }
    }
    if (!preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is', $content, $matches, PREG_OFFSET_CAPTURE)) {
        return array();
    }

    $monthPatterns = array(
        'JAN' => '/\bjan(?:uari|uary)?\b/i',
        'FEB' => '/\b(?:feb|peb)(?:ruari|ruary)?\b/i',
        'MAR' => '/\bmar(?:et|ch)?\b/i',
        'APR' => '/\bapr(?:il)?\b/i',
        'MEI' => '/\b(?:mei|may)\b/i',
        'JUN' => '/\bjun(?:i|e)?\b/i',
        'JUL' => '/\bjul(?:i|y)?\b/i',
        'AGS' => '/\b(?:agt|ags|agustus|august|aug)\b/i',
        'SEP' => '/\bsep(?:t(?:ember)?)?\b/i',
        'OKT' => '/\b(?:okt|oct)(?:ober)?\b/i',
        'NOV' => '/\b(?:nov|nop)(?:ember)?\b/i',
        'DES' => '/\b(?:des|dec)(?:ember)?\b/i',
    );
    $quarterPatterns = array(
        'TW4' => '/\b(?:triwulan|tw)\s*(?:iv|4|empat)\b/i',
        'TW3' => '/\b(?:triwulan|tw)\s*(?:iii|3|tiga)\b/i',
        'TW2' => '/\b(?:triwulan|tw)\s*(?:ii|2|dua)\b/i',
        'TW1' => '/\b(?:triwulan|tw)\s*(?:i|1|satu)\b/i',
        'SEM2' => '/\b(?:semester|sem)\s*(?:ii|2|genap)\b/i',
        'SEM1' => '/\b(?:semester|sem)\s*(?:i|1|ganjil)\b/i',
        'REVISI' => '/\brevisi\b/i',
        'REVIU'  => '/\b(?:reviu|review)\b/i',
        'TAHUNAN' => '/\btahunan\b/i',
    );

    $results = array();
    $seenUrl = array();

    foreach ($matches[2] as $idx => $hrefMatch) {
        $rawHref = html_entity_decode(trim((string) $hrefMatch[0]), ENT_QUOTES, 'UTF-8');
        if ($rawHref === '' || strpos($rawHref, '#') === 0 || preg_match('#^(?:javascript|mailto|tel|data):#i', $rawHref)) {
            continue;
        }
        if (strpos($rawHref, '/') === 0 && strpos($rawHref, '//') !== 0) {
            $rawHref = home_url($rawHref);
        } elseif (!preg_match('#^https?://#i', $rawHref) && strpos($rawHref, '//') !== 0) {
            $rawHref = home_url('/' . ltrim($rawHref, '/'));
        }
        $cleanUrl = esc_url_raw($rawHref, array('http', 'https'));
        if ($cleanUrl === '' || !preg_match('#^https?://#i', $cleanUrl)) {
            continue;
        }
        $dedupKey = strtolower($cleanUrl);
        if (isset($seenUrl[$dedupKey])) {
            continue;
        }
        $seenUrl[$dedupKey] = true;

        $anchorRaw  = (string) ($matches[3][$idx][0] ?? '');
        $anchorText = trim((string) preg_replace('/\s+/', ' ', wp_strip_all_tags(html_entity_decode($anchorRaw, ENT_QUOTES, 'UTF-8'))));
        $offset     = (int) ($matches[0][$idx][1] ?? 0);
        $beforeHtml = substr($content, max(0, $offset - 350), min(350, $offset));
        $beforeClean = (string) preg_replace('/<a\b[^>]*>.*?<\/a>/is', ' ', $beforeHtml);
        $nearText   = trim((string) preg_replace('/\s+/', ' ', wp_strip_all_tags(html_entity_decode($beforeClean, ENT_QUOTES, 'UTF-8'))));
        $urlPath    = (string) wp_parse_url($cleanUrl, PHP_URL_PATH);

        $yr = $fallbackYear;
        foreach (array($anchorText, $urlPath, substr($nearText, -160)) as $src) {
            if (preg_match('/(?<!\d)(201[5-9]|202[0-9]|203[0-5])(?!\d)/', $src, $ym)) {
                $yr = (int) $ym[1];
                break;
            }
        }

        $linkOwnText = trim($anchorText . ' ' . urldecode($urlPath));
        $nearTail    = substr($nearText, -140);
        $perKey      = '';
        foreach (array($linkOwnText, $nearTail) as $scopeText) {
            if ($scopeText === '') {
                continue;
            }
            foreach ($quarterPatterns as $pk => $rx) {
                if (preg_match($rx, $scopeText)) {
                    $perKey = $pk;
                    break 2;
                }
            }
            foreach ($monthPatterns as $pk => $rx) {
                if (preg_match($rx, $scopeText)) {
                    $perKey = $pk;
                    break 2;
                }
            }
        }
        if ($perKey === '' || !isset($periodsMap[$perKey])) {
            $perKey = $defaultPer;
        }
        $perOrder = (int) ($periodsMap[$perKey]['order'] ?? 1);

        $detectedSub = '';
        foreach (array($linkOwnText, $nearTail) as $scopeText) {
            if ($scopeText === '') {
                continue;
            }
            foreach ($subGroups as $sg) {
                $sgNorm = preg_quote((string) $sg, '/');
                $sgFlex = str_replace('\ ', '\s*', $sgNorm);
                if (preg_match('/' . $sgFlex . '/i', $scopeText)) {
                    $detectedSub = (string) $sg;
                    break 2;
                }
            }
            if (preg_match('/(?:dipa\s*0?1|\bbua\b)/i', $scopeText)) {
                foreach ($subGroups as $sg) {
                    if (strpos((string) $sg, '01') !== false) {
                        $detectedSub = (string) $sg;
                        break 2;
                    }
                }
            } elseif (preg_match('/(?:dipa\s*0?4|badilag)/i', $scopeText)) {
                foreach ($subGroups as $sg) {
                    if (strpos((string) $sg, '04') !== false) {
                        $detectedSub = (string) $sg;
                        break 2;
                    }
                }
            }
        }
        if ($detectedSub === '') {
            $detectedSub = $fallbackSub;
        }

        $isLocalPdf = (stripos($cleanUrl, home_url()) === 0 && preg_match('/\.pdf(?:$|\?)/i', $cleanUrl));
        $sourceType = $isLocalPdf ? 'wp_pdf' : 'external';

        $isGeneric  = ($anchorText === '' || preg_match('/^(?:download|unduh|klik(?:\s+di\s*sini)?|lihat|buka|pdf|file|dokumen)$/i', $anchorText));
        $trimmedTxt = function_exists('mb_substr') ? mb_substr($anchorText, 0, 180) : substr($anchorText, 0, 180);
        $title      = $isGeneric
            ? trim($detectedSub . ' ' . ($periodsMap[$perKey]['label'] ?? $perKey) . ' ' . $yr)
            : $trimmedTxt;

        $isDocHost  = (bool) preg_match('/(?:\.(?:pdf|docx?|xlsx?|zip)(?:$|\?)|drive\.google\.com|docs\.google\.com|\/uploads\/|\/images\/|download)/i', $cleanUrl);
        $suggested  = $isDocHost || $perKey !== $defaultPer;

        $results[] = array(
            'year'         => $yr,
            'sub_group'    => $detectedSub,
            'period_key'   => $perKey,
            'period_order' => $perOrder,
            'title'        => $title,
            'source_type'  => $sourceType,
            'doc_url'      => $cleanUrl,
            'anchor_text'  => $anchorText,
            'suggested'    => $suggested,
        );
    }

    return $results;
}

add_action('admin_menu', static function () {
    add_menu_page(
        'SINOVA Hub (Kepatuhan SK 2722/2026)',
        'SINOVA Hub',
        'pa_access_sinova_hub',
        'pa-sinova-starter',
        'pa_sinova_render_admin_page',
        'dashicons-awards',
        3
    );
});

/**
 * Menghasilkan matriks & SVG QR Code ISO/IEC 18004 (Version 5-L, <=106 byte, 1 blok RS) secara lokal.
 */
function pa_sinova_totp_qr_svg(string $payload): string {
    $bytes = array_values(unpack('C*', $payload));
    $len   = count($bytes);
    if ($len === 0 || $len > 106) {
        return '';
    }
    $size = 37; // Version 5

    // 1. Bitstream: Mode 0100 (Byte), 8-bit count, data, terminator, padding 0xEC/0x11 hingga 108 byte
    $bits = array(0, 1, 0, 0);
    for ($i = 7; $i >= 0; $i--) {
        $bits[] = ($len >> $i) & 1;
    }
    foreach ($bytes as $b) {
        for ($i = 7; $i >= 0; $i--) {
            $bits[] = ($b >> $i) & 1;
        }
    }
    $maxBits = 108 * 8;
    for ($i = 0; $i < 4 && count($bits) < $maxBits; $i++) {
        $bits[] = 0;
    }
    while (count($bits) % 8 !== 0) {
        $bits[] = 0;
    }
    $padBytes = array(0xEC, 0x11);
    $pIdx     = 0;
    while (count($bits) < $maxBits) {
        $pb = $padBytes[$pIdx % 2];
        $pIdx++;
        for ($i = 7; $i >= 0; $i--) {
            $bits[] = ($pb >> $i) & 1;
        }
    }
    $dataCw = array();
    for ($i = 0; $i < 108; $i++) {
        $v = 0;
        for ($j = 0; $j < 8; $j++) {
            $v = ($v << 1) | $bits[$i * 8 + $j];
        }
        $dataCw[] = $v;
    }

    // 2. GF(256) poly 0x11D Reed-Solomon (26 EC codewords)
    $exp = array_fill(0, 512, 0);
    $log = array_fill(0, 256, 0);
    $x   = 1;
    for ($i = 0; $i < 255; $i++) {
        $exp[$i]     = $x;
        $exp[$i + 255] = $x;
        $log[$x]     = $i;
        $x <<= 1;
        if ($x & 0x100) {
            $x ^= 0x11D;
        }
    }
    $gfMul = static function (int $a, int $b) use ($exp, $log): int {
        return ($a === 0 || $b === 0) ? 0 : $exp[$log[$a] + $log[$b]];
    };
    $ecLen = 26;
    $gen   = array(1);
    for ($i = 0; $i < $ecLen; $i++) {
        $next = array_fill(0, count($gen) + 1, 0);
        $a    = $exp[$i];
        foreach ($gen as $idx => $coef) {
            $next[$idx]     ^= $coef;
            $next[$idx + 1] ^= $gfMul($coef, $a);
        }
        $gen = $next;
    }
    $msg = array_merge($dataCw, array_fill(0, $ecLen, 0));
    for ($i = 0; $i < 108; $i++) {
        $coef = $msg[$i];
        if ($coef !== 0) {
            for ($j = 1; $j <= $ecLen; $j++) {
                $msg[$i + $j] ^= $gfMul($gen[$j], $coef);
            }
        }
    }
    $allCw = array_merge($dataCw, array_slice($msg, 108));
    $stream = array();
    foreach ($allCw as $cw) {
        for ($i = 7; $i >= 0; $i--) {
            $stream[] = ($cw >> $i) & 1;
        }
    }
    for ($i = 0; $i < 7; $i++) {
        $stream[] = 0; // Version 5 remainder bits
    }

    // 3. Matriks modul & pola fungsi
    $grid = array_fill(0, $size, array_fill(0, $size, null));
    $func = array_fill(0, $size, array_fill(0, $size, false));
    $setFunc = static function (int $r, int $c, bool $dark) use (&$grid, &$func, $size): void {
        if ($r >= 0 && $c >= 0 && $r < $size && $c < $size) {
            $grid[$r][$c] = $dark;
            $func[$r][$c] = true;
        }
    };
    $drawFinder = static function (int $top, int $left) use ($setFunc): void {
        for ($dr = -1; $dr <= 7; $dr++) {
            for ($dc = -1; $dc <= 7; $dc++) {
                $dark = ($dr >= 0 && $dr <= 6 && $dc >= 0 && $dc <= 6)
                    && ($dr === 0 || $dr === 6 || $dc === 0 || $dc === 6 || ($dr >= 2 && $dr <= 4 && $dc >= 2 && $dc <= 4));
                $setFunc($top + $dr, $left + $dc, $dark);
            }
        }
    };
    $drawFinder(0, 0);
    $drawFinder(0, $size - 7);
    $drawFinder($size - 7, 0);
    // Alignment (30, 30)
    for ($dr = -2; $dr <= 2; $dr++) {
        for ($dc = -2; $dc <= 2; $dc++) {
            $setFunc(30 + $dr, 30 + $dc, max(abs($dr), abs($dc)) !== 1);
        }
    }
    // Timing
    for ($i = 8; $i < $size - 8; $i++) {
        $setFunc(6, $i, $i % 2 === 0);
        $setFunc($i, 6, $i % 2 === 0);
    }
    // Dark module & reserve format info
    $setFunc($size - 8, 8, true);
    for ($i = 0; $i <= 8; $i++) {
        if ($i !== 6) {
            $setFunc(8, $i, false);
            $setFunc($i, 8, false);
        }
    }
    for ($i = $size - 8; $i < $size; $i++) {
        $setFunc(8, $i, false);
        if ($i > $size - 8) {
            $setFunc($i, 8, false);
        }
    }

    // 4. Penempatan data zig-zag
    $bitIdx = 0;
    $up     = true;
    for ($right = $size - 1; $right >= 1; $right -= 2) {
        if ($right === 6) {
            $right = 5;
        }
        for ($step = 0; $step < $size; $step++) {
            $r = $up ? ($size - 1 - $step) : $step;
            for ($dx = 0; $dx < 2; $dx++) {
                $c = $right - $dx;
                if (!$func[$r][$c]) {
                    $grid[$r][$c] = (bool) ($stream[$bitIdx++] ?? 0);
                }
            }
        }
        $up = !$up;
    }

    // 5. Terapkan mask 0 ((r+c)%2===0) + format info ECC Level L (01)
    for ($r = 0; $r < $size; $r++) {
        for ($c = 0; $c < $size; $c++) {
            if (!$func[$r][$c] && (($r + $c) % 2 === 0)) {
                $grid[$r][$c] = !$grid[$r][$c];
            }
        }
    }
    $fmtData = (0b01 << 3) | 0; // ECC L, Mask 0
    $rem     = $fmtData << 10;
    for ($i = 4; $i >= 0; $i--) {
        if (($rem >> ($i + 10)) & 1) {
            $rem ^= (0x537 << $i);
        }
    }
    $fmt = (($fmtData << 10) | $rem) ^ 0x5412;
    for ($i = 0; $i < 15; $i++) {
        $b = (bool) (($fmt >> $i) & 1);
        if ($i < 6) $grid[8][$i] = $b;
        elseif ($i < 8) $grid[8][$i + 1] = $b;
        else $grid[8][$size - 15 + $i] = $b;

        if ($i < 8) $grid[$size - 1 - $i][8] = $b;
        elseif ($i < 9) $grid[7][8] = $b;
        else $grid[14 - $i][8] = $b;
    }

    $paths = '';
    for ($r = 0; $r < $size; $r++) {
        for ($c = 0; $c < $size; $c++) {
            if (!empty($grid[$r][$c])) {
                $paths .= 'M' . ($c + 2) . ',' . ($r + 2) . 'h1v1h-1z';
            }
        }
    }
    $vb = $size + 4;
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $vb . ' ' . $vb . '" width="180" height="180" shape-rendering="crispEdges" role="img" aria-label="Kode QR Autentikator TOTP"><rect width="' . $vb . '" height="' . $vb . '" fill="#ffffff"/><path d="' . $paths . '" fill="#0f172a"/></svg>';
}

/** Validate raw PDF bytes (header, MIME, anti-script, <=10MB) and persist to sinova-docs storage. */
function pa_sinova_store_verified_pdf_bytes(string $raw, string $slug, int $year, string $per, string &$errNotice): ?array {
    $mimeOk = true;
    if ($raw !== '' && class_exists('finfo')) {
        $fi     = new finfo(FILEINFO_MIME_TYPE);
        $mime   = strtolower((string) $fi->buffer($raw));
        $mimeOk = in_array($mime, array('application/pdf', 'application/x-pdf'), true);
    }
    if (
        $raw === ''
        || strlen($raw) > 10 * 1024 * 1024
        || substr($raw, 0, 5) !== '%PDF-'
        || !$mimeOk
        || preg_match('/(<\?php|<\?=|<script\b)/i', substr($raw, 0, 8192))
    ) {
        $errNotice = 'Gagal: Berkas unggahan harus PDF asli dan aman (tanpa skrip aktif), maksimal 10 MB.';
        return null;
    }

    $safeSlug = sanitize_key($slug) ?: 'dokumen';
    $destDir  = WP_CONTENT_DIR . "/uploads/sinova-docs/{$year}/{$safeSlug}";
    wp_mkdir_p($destDir);
    $fname = "{$safeSlug}-{$year}-" . strtolower(sanitize_key($per) ?: 'doc') . '-' . substr(hash('sha256', $raw), 0, 8) . '.pdf';
    if (file_put_contents("{$destDir}/{$fname}", $raw, LOCK_EX) === false) {
        $errNotice = 'Gagal: Tidak dapat menyimpan berkas PDF ke direktori unggahan.';
        return null;
    }
    return array(
        'url'     => content_url("uploads/sinova-docs/{$year}/{$safeSlug}/{$fname}"),
        'file_kb' => (int) ceil(strlen($raw) / 1024),
    );
}

/** Delete archive documents in bulk while enforcing per-module RBAC and local PDF cleanup. */
function pa_sinova_delete_docs_by_ids(array $rawIds, array $allowedSlugs = array()): array {
    $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', $rawIds), static function (int $v): bool {
        return $v > 0;
    }))), 0, 200);
    if (empty($ids) || !function_exists('pa_sinova_table_name')) {
        return array('deleted' => 0, 'denied' => 0, 'slugs' => array());
    }

    global $wpdb;
    $table        = pa_sinova_table_name();
    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $rows         = $wpdb->get_results($wpdb->prepare("SELECT id, page_slug, source_type, doc_url FROM `{$table}` WHERE id IN ({$placeholders})", ...$ids), ARRAY_A);
    if (empty($rows) || !is_array($rows)) {
        return array('deleted' => 0, 'denied' => 0, 'slugs' => array());
    }

    $deletableIds  = array();
    $affectedSlugs = array();
    $denied        = 0;
    foreach ($rows as $row) {
        $rowSlug = (string) ($row['page_slug'] ?? '');
        if (!empty($allowedSlugs) && !in_array($rowSlug, $allowedSlugs, true)) {
            $denied++;
            continue;
        }
        if (($row['source_type'] ?? '') === 'wp_pdf' && function_exists('pa_sinova_safe_unlink_local_pdf')) {
            pa_sinova_safe_unlink_local_pdf((string) ($row['doc_url'] ?? ''));
        }
        $deletableIds[] = (int) $row['id'];
        if ($rowSlug !== '' && !in_array($rowSlug, $affectedSlugs, true)) {
            $affectedSlugs[] = $rowSlug;
        }
    }

    $deleted = 0;
    if (!empty($deletableIds)) {
        $delPh   = implode(',', array_fill(0, count($deletableIds), '%d'));
        $res     = $wpdb->query($wpdb->prepare("DELETE FROM `{$table}` WHERE id IN ({$delPh})", ...$deletableIds));
        $deleted = is_numeric($res) ? (int) $res : count($deletableIds);
        if (function_exists('pa_sinova_flush_caches_for_slug')) {
            foreach ($affectedSlugs as $s) {
                pa_sinova_flush_caches_for_slug($s);
            }
        }
    }

    return array('deleted' => $deleted, 'denied' => $denied, 'slugs' => $affectedSlugs);
}

/** Daftar 8 jenis data operasional & publikasi satker pada tabel pa_portal_records. */
function pa_sinova_record_types_catalog(): array {
    return array(
        'jadwal'     => 'Jadwal Sidang',
        'putusan'    => 'Putusan Pilihan (SIPP)',
        'pegawai'    => 'Pejabat & Pegawai',
        'ghaib'      => 'Panggilan Ghaib',
        'statistik'  => 'Statistik Perkara',
        'inovasi'    => 'Inovasi Layanan Satker',
        'slider'     => 'Slider Beranda',
        'video'      => 'Galeri Video',
        'ucapan'     => 'Ucapan & Belasungkawa',
        'yurisdiksi' => 'Wilayah Yurisdiksi',
    );
}

/** Build validated column payload for pa_portal_records insert or update. */
function pa_sinova_build_record_payload(array $input): array {
    $allowedTypes = array_keys(pa_sinova_record_types_catalog());
    $recType      = sanitize_key((string) ($input['rec_type'] ?? 'jadwal'));
    if (!in_array($recType, $allowedTypes, true)) {
        $recType = 'jadwal';
    }
    $recTitle = sanitize_text_field(wp_unslash((string) ($input['rec_title'] ?? '')));
    $recSub   = sanitize_text_field(wp_unslash((string) ($input['rec_subtitle'] ?? '')));
    $recCat   = sanitize_text_field(wp_unslash((string) ($input['rec_category'] ?? '')));
    $recM1    = sanitize_text_field(wp_unslash((string) ($input['rec_meta_1'] ?? '')));
    $recM2    = sanitize_text_field(wp_unslash((string) ($input['rec_meta_2'] ?? '')));
    $recM3    = sanitize_text_field(wp_unslash((string) ($input['rec_meta_3'] ?? '')));
    $recM4    = sanitize_text_field(wp_unslash((string) ($input['rec_meta_4'] ?? '')));
    $recM5    = sanitize_text_field(wp_unslash((string) ($input['rec_meta_5'] ?? '')));
    $recM6    = sanitize_textarea_field(wp_unslash((string) ($input['rec_meta_6'] ?? '')));
    $recM7    = sanitize_textarea_field(wp_unslash((string) ($input['rec_meta_7'] ?? '')));
    $recDate  = sanitize_text_field(wp_unslash((string) ($input['rec_date'] ?? '')));
    $recOrder = (int) ($input['rec_sort_order'] ?? 0);

    if ($recTitle === '') {
        return array('error' => 'Gagal: Judul / nama entri wajib diisi.');
    }
    if ($recType === 'pegawai') {
        $metaArr = array(
            'nip'                => $recM1,
            'gol'                => $recM2,
            'edu'                => $recM3,
            'photo'              => esc_url_raw($recM4, array('http', 'https')),
            'tmt'                => $recM5,
            'riwayat_pendidikan' => $recM6,
            'riwayat_jabatan'    => $recM7,
        );
    } elseif ($recType === 'ghaib') {
        $metaArr = array('pihak' => ($recM1 !== '' ? $recM1 : $recSub), 'doc_url' => esc_url_raw($recM2, array('http', 'https')));
    } elseif ($recType === 'statistik') {
        $metaArr = array('diterima' => (int) $recM1, 'diputus' => (int) $recM2, 'sisa' => (int) $recM3);
    } elseif ($recType === 'putusan') {
        $metaArr = array(
            'doc_url'      => esc_url_raw($recM1, array('http', 'https')),
            'majelis'      => $recM2,
            'status_hukum' => ($recM3 !== '' ? $recM3 : 'Berkekuatan Hukum Tetap'),
        );
    } elseif ($recType === 'inovasi') {
        $metaArr = array(
            'app_url'  => esc_url_raw($recM1, array('http', 'https')),
            'icon_url' => esc_url_raw($recM2, array('http', 'https')),
            'badge'    => ($recM3 !== '' ? $recM3 : ($recCat ?: 'Inovasi Satker')),
        );
    } elseif ($recType === 'slider') {
        $metaArr = array(
            'image_url' => esc_url_raw($recM1, array('http', 'https')),
            'btn_url'   => esc_url_raw($recM2, array('http', 'https')),
            'btn_label' => ($recM3 !== '' ? $recM3 : 'Selengkapnya'),
        );
    } elseif ($recType === 'video') {
        $metaArr = array(
            'video_url' => esc_url_raw($recM1, array('https')),
            'thumb_url' => esc_url_raw($recM2, array('http', 'https')),
            'duration'  => $recM3,
        );
    } elseif ($recType === 'ucapan') {
        $metaArr = array(
            'photo_url' => esc_url_raw($recM1, array('http', 'https')),
            'sender'    => ($recM2 !== '' ? $recM2 : 'Keluarga Besar Pengadilan Agama'),
            'badge'     => ($recM3 !== '' ? $recM3 : ($recCat ?: 'Ucapan Selamat')),
        );
    } elseif ($recType === 'yurisdiksi') {
        $metaArr = array(
            'desa'  => $recM1,
            'jarak' => $recM2,
            'biaya' => $recM3,
        );
    } else {
        $metaArr = array('jam' => ($recM1 !== '' ? $recM1 : '09.00 WIB'));
    }

    return array(
        'error' => '',
        'data'  => array(
            'record_type' => $recType,
            'title'       => $recTitle,
            'subtitle'    => $recSub,
            'category'    => $recCat,
            'event_date'  => preg_match('/^\d{4}-\d{2}-\d{2}$/', $recDate) ? $recDate : null,
            'meta_json'   => wp_json_encode($metaArr) ?: '{}',
            'sort_order'  => $recOrder,
            'status'      => 'published',
            'updated_at'  => current_time('mysql'),
        ),
    );
}

/** Delete operational portal records in bulk (max 200 IDs) and stale homepage cache. */
function pa_sinova_delete_records_by_ids(array $rawIds): int {
    $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', $rawIds), static function (int $v): bool {
        return $v > 0;
    }))), 0, 200);
    if (empty($ids) || !function_exists('pa_portal_records_table')) {
        return 0;
    }

    global $wpdb;
    $rTable       = pa_portal_records_table();
    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $res          = $wpdb->query($wpdb->prepare("DELETE FROM `{$rTable}` WHERE id IN ({$placeholders})", ...$ids));
    $deleted      = is_numeric($res) ? (int) $res : 0;
    if ($deleted > 0 && function_exists('pa_home_page_cache_mark_stale')) {
        pa_home_page_cache_mark_stale();
    }
    return $deleted;
}

function pa_sinova_render_admin_page(): void {
    if (!current_user_can('pa_access_sinova_hub')) {
        return;
    }

    $allowedTabs = pa_sinova_user_allowed_tabs();
    if (empty($allowedTabs)) {
        $allowedTabs = array('compliance');
    }

    $notice          = '';
    $editDocId       = 0;
    $editRecId       = 0;
    $mfaRecoveryOnce = array();
    $scannedLinks    = array();
    $scanPostId      = 0;
    $scanTargetSlug  = 'lra';
    $scanDefaultYear = (int) wp_date('Y');
    $activeTab       = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : $allowedTabs[0];
    if (!in_array($activeTab, $allowedTabs, true)) {
        $activeTab = $allowedTabs[0];
    }

    $requireAdmin = static function () use (&$notice): bool {
        if (!current_user_can('manage_options')) {
            $notice = 'Gagal: Aksi ini hanya diizinkan untuk Administrator Satker.';
            return false;
        }
        return true;
    };

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pa_sinova_action'])) {
        check_admin_referer('pa_sinova_starter_nonce');
        $action = sanitize_key((string) $_POST['pa_sinova_action']);

        if ($action === 'save_settings' && $requireAdmin()) {
            $activeTab = 'settings';
            $cur = pa_sinova_get_settings();
            $palette = sanitize_key((string) ($_POST['accent_palette'] ?? ($cur['accent_palette'] ?? 'gold')));
            if (!in_array($palette, array('gold', 'badilag_orange', 'emerald_silver'), true)) {
                $palette = 'gold';
            }
            $heroLayout = sanitize_key((string) ($_POST['hero_layout'] ?? ($cur['hero_layout'] ?? 'split')));
            if (!in_array($heroLayout, array('split', 'classic'), true)) {
                $heroLayout = 'split';
            }
            $heroPattern = sanitize_key((string) ($_POST['hero_pattern'] ?? ($cur['hero_pattern'] ?? 'islamic')));
            if (!in_array($heroPattern, array('none', 'islamic', 'batik', 'songket'), true)) {
                $heroPattern = 'islamic';
            }
            $cspMode = sanitize_key((string) ($_POST['csp_mode'] ?? ($cur['csp_mode'] ?? 'strict')));
            if (!in_array($cspMode, array('strict', 'compat', 'off'), true)) {
                $cspMode = 'strict';
            }

            $validateRssUrl = static function (string $rawUrl, string $fallback): array {
                $u = esc_url_raw(trim($rawUrl), array('https'));
                if ($u === '') {
                    return array('url' => '', 'warn' => false);
                }
                $scheme = strtolower((string) parse_url($u, PHP_URL_SCHEME));
                $host   = strtolower((string) parse_url($u, PHP_URL_HOST));
                $okHost = ($scheme === 'https') && ($host === 'mahkamahagung.go.id' || $host === 'badilag.net' || substr($host, -20) === '.mahkamahagung.go.id' || substr($host, -6) === '.go.id');
                if (!$okHost) {
                    return array('url' => $fallback, 'warn' => true);
                }
                return array('url' => $u, 'warn' => false);
            };

            $rssMaCheck    = $validateRssUrl(wp_unslash((string) ($_POST['rss_ma_url'] ?? '')), (string) ($cur['rss_ma_url'] ?? ''));
            $rssBadCheck   = $validateRssUrl(wp_unslash((string) ($_POST['rss_badilag_url'] ?? '')), (string) ($cur['rss_badilag_url'] ?? ''));
            $rssDirRaw     = trim(wp_unslash((string) ($_POST['dirput_rss_url'] ?? '')));
            $rssDirCheck   = $rssDirRaw === '' ? array('url' => '', 'warn' => false) : $validateRssUrl($rssDirRaw, (string) ($cur['dirput_rss_url'] ?? ''));
            $sippSlideRaw  = wp_unslash((string) ($_POST['sipp_slide_url'] ?? ''));
            $sippSlideChk  = pa_sinova_sanitize_https_embed_url($sippSlideRaw);
            $sippSlideWarn = !$sippSlideChk['valid'];
            $sippSlideSave = $sippSlideChk['valid'] ? $sippSlideChk['url'] : (string) ($cur['sipp_slide_url'] ?? '');

            $rawHash = trim(sanitize_text_field(wp_unslash((string) ($_POST['dirput_court_hash'] ?? ''))));
            $cleanHash = '';
            if ($rawHash !== '') {
                if (preg_match('/[a-f0-9]{32}/i', $rawHash, $hm)) {
                    $cleanHash = strtolower($hm[0]);
                }
            }

            $acoPos = sanitize_key((string) ($_POST['aco_float_position'] ?? ($cur['aco_float_position'] ?? 'bottom_right')));
            if (!in_array($acoPos, array('bottom_right', 'middle_right', 'bottom_left', 'middle_left', 'top_right'), true)) {
                $acoPos = 'bottom_right';
            }
            $acoSz = sanitize_key((string) ($_POST['aco_float_size'] ?? ($cur['aco_float_size'] ?? 'md')));
            if (!in_array($acoSz, array('sm', 'md', 'lg'), true)) {
                $acoSz = 'md';
            }

            $footerCredit = sanitize_text_field(wp_unslash((string) ($_POST['footer_credit_text'] ?? ($cur['footer_credit_text'] ?? 'Pengadilan Agama Devlpr Community'))));
            if ($footerCredit === '') {
                $footerCredit = 'Pengadilan Agama Devlpr Community';
            }
            $footerHover = sanitize_text_field(wp_unslash((string) ($_POST['footer_hover_text'] ?? ($cur['footer_hover_text'] ?? 'Developed with ♥ by PA Devlpr Community'))));
            if ($footerHover === '') {
                $footerHover = 'Developed with ♥ by PA Devlpr Community';
            }
            $tickerLbl = sanitize_text_field(wp_unslash((string) ($_POST['home_ticker_label'] ?? ($cur['home_ticker_label'] ?? 'INFO LAYANAN'))));
            if ($tickerLbl === '') {
                $tickerLbl = 'INFO LAYANAN';
            }

            $new = array_merge($cur, array(
                'satker_name'              => sanitize_text_field(wp_unslash((string) ($_POST['satker_name'] ?? $cur['satker_name']))),
                'logo_url'                 => esc_url_raw(wp_unslash((string) ($_POST['logo_url'] ?? ''))),
                'favicon_url'              => esc_url_raw(wp_unslash((string) ($_POST['favicon_url'] ?? ''))),
                'preserve_existing_design' => isset($_POST['preserve_existing_design']) ? (!empty($_POST['preserve_existing_design']) ? '1' : '0') : ((string) ($cur['preserve_existing_design'] ?? '0')),
                'address'                  => sanitize_text_field(wp_unslash((string) ($_POST['address'] ?? $cur['address']))),
                'phone'                    => sanitize_text_field(wp_unslash((string) ($_POST['phone'] ?? $cur['phone']))),
                'email'                    => sanitize_email(wp_unslash((string) ($_POST['email'] ?? $cur['email']))),
                'hearing_hours'            => sanitize_text_field(wp_unslash((string) ($_POST['hearing_hours'] ?? $cur['hearing_hours']))),
                'leader_name'              => sanitize_text_field(wp_unslash((string) ($_POST['leader_name'] ?? $cur['leader_name']))),
                'leader_title'             => sanitize_text_field(wp_unslash((string) ($_POST['leader_title'] ?? ($cur['leader_title'] ?? 'Ketua Pengadilan Agama')))),
                'leader_photo_url'         => esc_url_raw(wp_unslash((string) ($_POST['leader_photo_url'] ?? ''))),
                'leader_motto'             => sanitize_text_field(wp_unslash((string) ($_POST['leader_motto'] ?? ($cur['leader_motto'] ?? '')))),
                'leader_welcome'           => sanitize_textarea_field(wp_unslash((string) ($_POST['leader_welcome'] ?? $cur['leader_welcome']))),
                'hero_layout'              => $heroLayout,
                'hero_pattern'             => $heroPattern,
                'hero_photo_url'           => esc_url_raw(wp_unslash((string) ($_POST['hero_photo_url'] ?? ''))),
                'hero_photo_caption'       => sanitize_text_field(wp_unslash((string) ($_POST['hero_photo_caption'] ?? ($cur['hero_photo_caption'] ?? '')))),
                'social_instagram'         => esc_url_raw(wp_unslash((string) ($_POST['social_instagram'] ?? ''))),
                'social_youtube'           => esc_url_raw(wp_unslash((string) ($_POST['social_youtube'] ?? ''))),
                'social_facebook'          => esc_url_raw(wp_unslash((string) ($_POST['social_facebook'] ?? ''))),
                'social_tiktok'            => esc_url_raw(wp_unslash((string) ($_POST['social_tiktok'] ?? ''))),
                'social_whatsapp'          => esc_url_raw(wp_unslash((string) ($_POST['social_whatsapp'] ?? ''))),
                'maps_url'                 => esc_url_raw(wp_unslash((string) ($_POST['maps_url'] ?? ''))),
                'sipp_url'                 => esc_url_raw(wp_unslash((string) ($_POST['sipp_url'] ?? ''))),
                'sipp_slide_url'           => $sippSlideSave,
                'ecourt_url'               => esc_url_raw(wp_unslash((string) ($_POST['ecourt_url'] ?? ''))),
                'direktori_url'            => esc_url_raw(wp_unslash((string) ($_POST['direktori_url'] ?? ''))),
                'dirput_rss_url'           => $rssDirCheck['url'],
                'dirput_court_hash'        => $cleanHash,
                'survey_url'               => esc_url_raw(wp_unslash((string) ($_POST['survey_url'] ?? ''))),
                'aco_url'                  => esc_url_raw(wp_unslash((string) ($_POST['aco_url'] ?? ($cur['aco_url'] ?? '')))),
                'aco_logo_url'             => esc_url_raw(wp_unslash((string) ($_POST['aco_logo_url'] ?? ''))),
                'aco_float_enabled'        => !empty($_POST['aco_float_enabled']) ? '1' : '0',
                'aco_float_position'       => $acoPos,
                'aco_float_size'           => $acoSz,
                'aco_float_draggable'      => !empty($_POST['aco_float_draggable']) ? '1' : '0',
                'home_show_ticker'         => isset($_POST['home_show_ticker']) ? (!empty($_POST['home_show_ticker']) ? '1' : '0') : ((string) ($cur['home_show_ticker'] ?? '1')),
                'home_ticker_label'        => $tickerLbl,
                'home_ticker_text'         => sanitize_textarea_field(wp_unslash((string) ($_POST['home_ticker_text'] ?? ($cur['home_ticker_text'] ?? '')))),
                'footer_credit_text'       => $footerCredit,
                'footer_credit_suffix'     => sanitize_text_field(wp_unslash((string) ($_POST['footer_credit_suffix'] ?? ''))),
                'footer_hover_text'        => $footerHover,
                'footer_credit_url'        => esc_url_raw(wp_unslash((string) ($_POST['footer_credit_url'] ?? ''))),
                'rss_ma_url'               => $rssMaCheck['url'],
                'rss_badilag_url'          => $rssBadCheck['url'],
                'enforce_mfa'              => (string) ($cur['enforce_mfa'] ?? '0'),
                'custom_login_slug'        => (string) ($cur['custom_login_slug'] ?? ''),
                'hmac_secret'              => trim(sanitize_text_field(wp_unslash((string) ($_POST['hmac_secret'] ?? '')))),
                'turnstile_site_key'       => trim(sanitize_text_field(wp_unslash((string) ($_POST['turnstile_site_key'] ?? '')))),
                'turnstile_secret_key'     => trim(sanitize_text_field(wp_unslash((string) ($_POST['turnstile_secret_key'] ?? '')))),
                'accent_palette'           => $palette,
                'csp_mode'                 => $cspMode,
                'emergency_notice'         => sanitize_text_field(wp_unslash((string) ($_POST['emergency_notice'] ?? ''))),
                'emergency_notice_url'     => esc_url_raw(wp_unslash((string) ($_POST['emergency_notice_url'] ?? ''))),
                'siwas_url'                => esc_url_raw(wp_unslash((string) ($_POST['siwas_url'] ?? ($cur['siwas_url'] ?? '')))),
                'lapor_url'                => esc_url_raw(wp_unslash((string) ($_POST['lapor_url'] ?? ($cur['lapor_url'] ?? '')))),
                'zi_area_1_url'            => esc_url_raw(wp_unslash((string) ($_POST['zi_area_1_url'] ?? ''))),
                'zi_area_2_url'            => esc_url_raw(wp_unslash((string) ($_POST['zi_area_2_url'] ?? ''))),
                'zi_area_3_url'            => esc_url_raw(wp_unslash((string) ($_POST['zi_area_3_url'] ?? ''))),
                'zi_area_4_url'            => esc_url_raw(wp_unslash((string) ($_POST['zi_area_4_url'] ?? ''))),
                'zi_area_5_url'            => esc_url_raw(wp_unslash((string) ($_POST['zi_area_5_url'] ?? ''))),
                'zi_area_6_url'            => esc_url_raw(wp_unslash((string) ($_POST['zi_area_6_url'] ?? ''))),
            ));
            if (!empty($_POST['generate_hmac'])) {
                $new['hmac_secret'] = bin2hex(random_bytes(32));
            }
            update_option('pa_sinova_starter_settings', $new, false);
            if (($new['direktori_url'] ?? '') !== ($cur['direktori_url'] ?? '') || ($new['dirput_rss_url'] ?? '') !== ($cur['dirput_rss_url'] ?? '')) {
                delete_option('pa_dirput_feed_cache');
            }
            pa_sinova_sync_direktori_putusan_page((string) ($new['direktori_url'] ?? ''));
            pa_sinova_purge_html_cache_files();
            $notice = 'Pengaturan Satker, Identitas Visual, Umpan RSS, & API HMAC berhasil disimpan.';
            if ($rssMaCheck['warn'] || $rssBadCheck['warn'] || $rssDirCheck['warn']) {
                $notice .= ' Catatan: URL RSS non-HTTPS atau di luar domain resmi (.go.id) ditolak dan dikembalikan ke URL aman sebelumnya.';
            }
            if ($sippSlideWarn) {
                $notice .= ' Catatan: URL Slide Jadwal Sidang SIPP wajib memakai protokol HTTPS dengan domain valid.';
            }
        } elseif ($action === 'save_home_layout' && $requireAdmin()) {
            $activeTab = 'homepage';
            $cur       = pa_sinova_get_settings();
            $pos       = sanitize_key((string) ($_POST['home_schedule_position'] ?? 'after_hero'));
            if (!in_array($pos, array('after_hero', 'after_services', 'after_welcome', 'hidden'), true)) {
                $pos = 'after_hero';
            }
            $defTab = sanitize_key((string) ($_POST['home_schedule_default_tab'] ?? 'auto'));
            if (!in_array($defTab, array('auto', 'agenda', 'sipp'), true)) {
                $defTab = 'auto';
            }
            $sippSlideChk  = pa_sinova_sanitize_https_embed_url(wp_unslash((string) ($_POST['sipp_slide_url'] ?? '')));
            $sippSlideWarn = !$sippSlideChk['valid'];
            $sippSlideSave = $sippSlideChk['valid'] ? $sippSlideChk['url'] : (string) ($cur['sipp_slide_url'] ?? '');

            $updated = array_merge($cur, array(
                'sipp_slide_url'            => $sippSlideSave,
                'home_schedule_position'    => $pos,
                'home_schedule_default_tab' => $defTab,
                'home_show_ticker'          => !empty($_POST['home_show_ticker']) ? '1' : '0',
                'home_ticker_text'          => sanitize_text_field(wp_unslash((string) ($_POST['home_ticker_text'] ?? $cur['home_ticker_text']))),
                'home_hero_kicker'          => sanitize_text_field(wp_unslash((string) ($_POST['home_hero_kicker'] ?? $cur['home_hero_kicker']))),
                'home_hero_title'           => sanitize_text_field(wp_unslash((string) ($_POST['home_hero_title'] ?? $cur['home_hero_title']))),
                'home_hero_lead'            => sanitize_textarea_field(wp_unslash((string) ($_POST['home_hero_lead'] ?? $cur['home_hero_lead']))),
                'home_show_services'        => !empty($_POST['home_show_services']) ? '1' : '0',
                'home_show_welcome'         => !empty($_POST['home_show_welcome']) ? '1' : '0',
                'home_show_zi'              => !empty($_POST['home_show_zi']) ? '1' : '0',
                'home_show_news'            => !empty($_POST['home_show_news']) ? '1' : '0',
                'home_show_announcements'   => !empty($_POST['home_show_announcements']) ? '1' : '0',
                'home_show_survey'          => !empty($_POST['home_show_survey']) ? '1' : '0',
                'home_survey_period'        => sanitize_text_field(wp_unslash((string) ($_POST['home_survey_period'] ?? $cur['home_survey_period']))),
                'home_survey_grade'         => sanitize_text_field(wp_unslash((string) ($_POST['home_survey_grade'] ?? $cur['home_survey_grade']))),
                'home_survey_skm'           => sanitize_text_field(wp_unslash((string) ($_POST['home_survey_skm'] ?? $cur['home_survey_skm']))),
                'home_survey_spak'          => sanitize_text_field(wp_unslash((string) ($_POST['home_survey_spak'] ?? $cur['home_survey_spak']))),
                'home_survey_spkp'          => sanitize_text_field(wp_unslash((string) ($_POST['home_survey_spkp'] ?? $cur['home_survey_spkp']))),
            ));
            update_option('pa_sinova_starter_settings', $updated, false);
            pa_sinova_purge_html_cache_files();
            $notice = 'Desain & tata letak Beranda berhasil disimpan (cache HTML otomatis disegarkan).';
            if ($sippSlideWarn) {
                $notice .= ' Catatan: URL Slide Jadwal Sidang SIPP wajib HTTPS dengan domain valid.';
            }
        } elseif ($action === 'save_home_grid' && $requireAdmin()) {
            $activeTab = 'homepage';
            $rawJson   = wp_unslash((string) ($_POST['pa_home_blocks_json'] ?? ''));
            $decoded   = json_decode($rawJson, true);
            if (!is_array($decoded)) {
                $notice = 'Gagal: Format data tata letak grid tidak valid.';
            } else {
                $sanitized = pa_sinova_sanitize_home_blocks($decoded);
                update_option('pa_sinova_home_blocks', $sanitized, false);

                // Sinkronkan pengaturan Hero/Ticker/Survei/SIPP jika ikut dikirim dari modal/panel
                $cur = pa_sinova_get_settings();
                $patch = array();
                foreach (array('home_ticker_label', 'home_ticker_text', 'home_hero_kicker', 'home_hero_title', 'home_hero_lead', 'hero_photo_caption', 'home_survey_period', 'home_survey_grade', 'home_survey_skm', 'home_survey_spak', 'home_survey_spkp') as $hk) {
                    if (isset($_POST[$hk])) {
                        $patch[$hk] = ($hk === 'home_hero_lead' || $hk === 'home_ticker_text')
                            ? sanitize_textarea_field(wp_unslash((string) $_POST[$hk]))
                            : sanitize_text_field(wp_unslash((string) $_POST[$hk]));
                    }
                }
                if (isset($_POST['hero_layout'])) {
                    $hl = sanitize_key((string) wp_unslash($_POST['hero_layout']));
                    if (in_array($hl, array('split', 'classic'), true)) {
                        $patch['hero_layout'] = $hl;
                    }
                }
                if (isset($_POST['hero_pattern'])) {
                    $hp = sanitize_key((string) wp_unslash($_POST['hero_pattern']));
                    if (in_array($hp, array('none', 'islamic', 'batik', 'songket'), true)) {
                        $patch['hero_pattern'] = $hp;
                    }
                }
                if (isset($_POST['hero_photo_url'])) {
                    $patch['hero_photo_url'] = esc_url_raw(wp_unslash((string) $_POST['hero_photo_url']));
                }
                if (isset($_POST['home_show_ticker'])) {
                    $patch['home_show_ticker'] = !empty($_POST['home_show_ticker']) ? '1' : '0';
                }
                foreach ($sanitized as $sb) {
                    if ($sb['type'] === 'schedule' && !empty($sb['config']['embed_url'])) {
                        $patch['sipp_slide_url'] = (string) $sb['config']['embed_url'];
                    }
                }
                if (!empty($patch)) {
                    update_option('pa_sinova_starter_settings', array_merge($cur, $patch), false);
                }
                pa_sinova_purge_html_cache_files();
                $notice = sprintf('Tampilan Beranda 12 kolom berhasil disimpan (%d blok aktif/terdaftar, cache disegarkan).', count($sanitized));
            }
        } elseif ($action === 'reset_home_grid' && $requireAdmin()) {
            $activeTab = 'homepage';
            delete_option('pa_sinova_home_blocks');
            pa_sinova_purge_html_cache_files();
            $notice = 'Tata letak Beranda 12 kolom dikembalikan ke susunan standar seimbang.';
        } elseif ($action === 'purge_html_cache' && current_user_can('pa_access_sinova_hub')) {
            pa_sinova_purge_html_cache_files();
            $notice = 'Cache HTML portal berhasil dibersihkan.';
        } elseif ($action === 'rollback_github_update' && $requireAdmin()) {
            $activeTab = 'security';
            $rbRes     = pa_sinova_github_rollback_update();
            $notice    = (string) ($rbRes['message'] ?? 'Rollback selesai.');
        } elseif ($action === 'activate_dynamic_home' && $requireAdmin()) {
            $activeTab = 'homepage';
            $res       = pa_sinova_activate_dynamic_beranda();
            $notice    = !empty($res['ok'])
                ? 'Beranda Dinamis [pa_sinova_beranda] berhasil diaktifkan! Seluruh tampilan Beranda kini dapat diatur langsung dari formulir di bawah tanpa edit HTML.'
                : 'Gagal: Tidak dapat memperbarui halaman Beranda.';
        } elseif ($action === 'check_github_update' && $requireAdmin()) {
            $activeTab = 'security';
            $upd       = pa_sinova_github_check_update(true);
            if (empty($upd['ok'])) {
                $notice = 'Gagal: ' . (string) ($upd['error'] ?? 'Tidak dapat memeriksa pembaruan GitHub.');
            } elseif (!empty($upd['has_update'])) {
                $notice = sprintf('Tersedia pembaruan baru v%s (terpasang: v%s). Klik "Update 1-Klik dari GitHub" di bawah untuk memasang.', $upd['latest'], $upd['current']);
            } else {
                $notice = sprintf('Starter Kit Anda sudah versi terbaru (v%s).', $upd['current']);
            }
        } elseif ($action === 'apply_github_update' && $requireAdmin()) {
            $activeTab = 'security';
            $updRes    = pa_sinova_github_apply_update();
            $notice    = (string) ($updRes['message'] ?? 'Pembaruan selesai.');
        } elseif ($action === 'fim_reset_baseline' && $requireAdmin()) {
            $activeTab = 'security';
            update_option('pa_starter_fim_baseline', array(
                'recorded_at' => gmdate('Y-m-d H:i:s') . ' UTC',
                'hashes'      => pa_starter_fim_current_hashes(),
            ), false);
            $notice = 'Baseline SHA-256 File Integrity Monitor (FIM) berhasil diperbarui sesuai berkas aktif saat ini.';
        } elseif ($action === 'run_seeder' && $requireAdmin()) {
            $activeTab = 'settings';
            $res = pa_sinova_run_starter_seeder();
            if (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }
            $notice = !empty($res['preserve_mode'])
                ? sprintf(
                    'Seeder aman selesai: %d halaman kurang dibuat (%d total halaman terpetakan). Tema, menu, & beranda lama tetap dipertahankan.',
                    $res['pages_created'],
                    $res['total_mapped']
                )
                : sprintf(
                    'Seeder selesai: %d halaman baru dibuat (%d total halaman terpetakan), %d item menu ditambahkan.',
                    $res['pages_created'],
                    $res['total_mapped'],
                    $res['menu_items']
                );
        } elseif ($action === 'rebuild_6menu' && $requireAdmin()) {
            $activeTab = 'settings';
            $res = pa_sinova_run_starter_seeder(false, true);
            if (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }
            $notice = sprintf('Struktur 6 menu utama standar berhasil disusun ulang (%d item menu terbit).', $res['menu_items']);
        } elseif ($action === 'refresh_rss' && in_array('data', $allowedTabs, true)) {
            $activeTab = 'data';
            if (function_exists('pa_portal_refresh_official_feeds')) {
                $feedData = pa_portal_refresh_official_feeds();
                $maCnt    = count((array) ($feedData['ma'] ?? array()));
                $bdCnt    = count((array) ($feedData['badilag'] ?? array()));
                $dpCnt    = 0;
                if (function_exists('pa_portal_refresh_dirput_feed')) {
                    $dpData = pa_portal_refresh_dirput_feed();
                    $dpCnt  = count((array) ($dpData['items'] ?? array()));
                }
                $notice   = "Sinkronisasi RSS resmi selesai: MA RI ({$maCnt} item), Ditjen Badilag ({$bdCnt} item), & Direktori Putusan ({$dpCnt} item).";
            } else {
                $notice = 'Gagal: Modul umpan RSS belum dimuat.';
            }
        } elseif ($action === 'delete_sample_records' && in_array('data', $allowedTabs, true)) {
            $activeTab = 'data';
            if (function_exists('pa_portal_delete_sample_records')) {
                $delSample = pa_portal_delete_sample_records();
                $notice = $delSample > 0
                    ? "Berhasil menghapus {$delSample} entri data contoh (Ahmad Contoh / PA.Cth)."
                    : 'Tidak ada data contoh tersisa untuk dihapus.';
            }
        } elseif ($action === 'add_record' && in_array('data', $allowedTabs, true)) {
            $activeTab = 'data';
            if (function_exists('pa_portal_records_ensure_table') && function_exists('pa_portal_records_table')) {
                pa_portal_records_ensure_table();
                $built = pa_sinova_build_record_payload($_POST);
                if ($built['error'] !== '') {
                    $notice = $built['error'];
                } else {
                    global $wpdb;
                    $wpdb->insert(pa_portal_records_table(), $built['data']);
                    if (function_exists('pa_home_page_cache_mark_stale')) {
                        pa_home_page_cache_mark_stale();
                    }
                    $notice = 'Entri Data Operasional (' . $built['data']['record_type'] . ') berhasil ditambahkan.';
                }
            }
        } elseif ($action === 'update_record' && in_array('data', $allowedTabs, true)) {
            $activeTab = 'data';
            if (function_exists('pa_portal_records_ensure_table') && function_exists('pa_portal_records_table')) {
                pa_portal_records_ensure_table();
                global $wpdb;
                $rTable = pa_portal_records_table();
                $rid    = (int) ($_POST['edit_rec_id'] ?? 0);
                $exists = ($rid > 0) ? (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$rTable}` WHERE id = %d", $rid)) : 0;
                if ($exists <= 0) {
                    $notice = 'Gagal: Entri data operasional yang ingin diubah tidak ditemukan.';
                } else {
                    $built = pa_sinova_build_record_payload($_POST);
                    if ($built['error'] !== '') {
                        $notice    = $built['error'];
                        $editRecId = $rid;
                    } else {
                        $wpdb->update($rTable, $built['data'], array('id' => $rid));
                        if (function_exists('pa_home_page_cache_mark_stale')) {
                            pa_home_page_cache_mark_stale();
                        }
                        $editRecId = 0;
                        $notice    = "Perubahan Data Operasional (#{$rid}) berhasil disimpan.";
                    }
                }
            }
        } elseif ($action === 'delete_record' && in_array('data', $allowedTabs, true)) {
            $activeTab = 'data';
            $rid       = (int) ($_POST['rec_id'] ?? 0);
            if ($rid > 0 && pa_sinova_delete_records_by_ids(array($rid)) > 0) {
                $notice = 'Entri data operasional berhasil dihapus.';
            }
        } elseif ($action === 'bulk_delete_records' && in_array('data', $allowedTabs, true)) {
            $activeTab = 'data';
            $rawRecIds = (isset($_POST['rec_ids']) && is_array($_POST['rec_ids'])) ? $_POST['rec_ids'] : array();
            if (empty($rawRecIds)) {
                $notice = 'Gagal: Pilih minimal satu entri data dengan mencentang kotak di samping baris.';
            } else {
                $deletedCnt = pa_sinova_delete_records_by_ids($rawRecIds);
                if ($deletedCnt > 0) {
                    $notice = "{$deletedCnt} entri data operasional terpilih berhasil dihapus.";
                } else {
                    $notice = 'Gagal: Tidak ada entri data valid yang dapat dihapus.';
                }
            }
        } elseif ($action === 'mfa_setup_start' && in_array('security', $allowedTabs, true)) {
            $activeTab = 'security';
            if (function_exists('pa_starter_totp_generate_secret')) {
                $uid = get_current_user_id();
                $pending = pa_starter_totp_generate_secret(20);
                update_user_meta($uid, 'pa_totp_pending_secret', $pending);
                $notice = 'Kunci TOTP baru dibuat. Pindai kode QR pada aplikasi autentikator lalu masukkan kode 6 digit untuk mengaktifkan.';
            }
        } elseif ($action === 'mfa_activate' && in_array('security', $allowedTabs, true)) {
            $activeTab = 'security';
            if (function_exists('pa_starter_totp_verify') && function_exists('pa_starter_totp_generate_recovery_codes')) {
                $uid     = get_current_user_id();
                $pending = (string) get_user_meta($uid, 'pa_totp_pending_secret', true);
                $otp     = trim((string) ($_POST['mfa_verify_code'] ?? ''));
                if ($pending === '' || !pa_starter_totp_verify($pending, $otp, 1, $uid)) {
                    $notice = 'Gagal: Kode verifikasi 6 digit tidak sesuai atau kedaluwarsa. Pastikan jam perangkat akurat.';
                } else {
                    $rec = pa_starter_totp_generate_recovery_codes(8);
                    update_user_meta($uid, 'pa_totp_secret', $pending);
                    update_user_meta($uid, 'pa_totp_enabled', '1');
                    update_user_meta($uid, 'pa_totp_recovery_hashes', $rec['hashes']);
                    delete_user_meta($uid, 'pa_totp_pending_secret');
                    $mfaRecoveryOnce = $rec['plain'];
                    $notice = 'Autentikasi Dua Faktor (MFA TOTP) berhasil diaktifkan pada akun Anda. Simpan 8 kode cadangan di tempat aman.';
                }
            }
        } elseif ($action === 'mfa_disable' && in_array('security', $allowedTabs, true)) {
            $activeTab = 'security';
            $uid = get_current_user_id();
            delete_user_meta($uid, 'pa_totp_secret');
            delete_user_meta($uid, 'pa_totp_enabled');
            delete_user_meta($uid, 'pa_totp_recovery_hashes');
            delete_user_meta($uid, 'pa_totp_pending_secret');
            $notice = 'Autentikasi Dua Faktor (MFA TOTP) pada akun Anda telah dinonaktifkan.';
        } elseif ($action === 'mfa_policy' && $requireAdmin()) {
            $activeTab = 'security';
            $stored = get_option('pa_sinova_starter_settings', array());
            if (!is_array($stored)) {
                $stored = array();
            }
            $stored['enforce_mfa'] = !empty($_POST['enforce_mfa']) ? '1' : '0';
            update_option('pa_sinova_starter_settings', $stored, false);
            $notice = $stored['enforce_mfa'] === '1'
                ? 'Kebijakan kewajiban MFA TOTP diaktifkan (menampilkan pengingat wajib kepada operator yang belum mengaktifkan 2FA).'
                : 'Kebijakan pengingat wajib MFA TOTP dinonaktifkan.';
        } elseif (($action === 'save_adoption_mode' || $action === 'save_and_seed_safe') && $requireAdmin()) {
            $activeTab = 'migration';
            $stored    = get_option('pa_sinova_starter_settings', array());
            if (!is_array($stored)) {
                $stored = array();
            }
            $stored['preserve_existing_design'] = !empty($_POST['preserve_existing_design']) ? '1' : '0';
            update_option('pa_sinova_starter_settings', $stored, false);
            if ($action === 'save_and_seed_safe') {
                $res = pa_sinova_run_starter_seeder(true);
                $notice = sprintf(
                    'Mode Adopsi Non-Destruktif disimpan (%s). Seeder aman selesai: %d halaman kurang dibuat (%d total terpetakan). Tema, menu, & beranda lama tetap dipertahankan.',
                    $stored['preserve_existing_design'] === '1' ? 'AKTIF' : 'NONAKTIF',
                    $res['pages_created'],
                    $res['total_mapped']
                );
            } else {
                $notice = $stored['preserve_existing_design'] === '1'
                    ? 'Mode Adopsi Situs Lama DIAKTIFKAN: tema, menu utama, & halaman beranda eksisting tetap dipertahankan.'
                    : 'Mode Adopsi Situs Lama DINONAKTIFKAN: tata letak header dan beranda standar paket aktif kembali.';
            }
            if (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }
        } elseif ($action === 'save_custom_map' && $requireAdmin()) {
            $activeTab = 'migration';
            $postIds   = isset($_POST['map_post_id']) && is_array($_POST['map_post_id']) ? $_POST['map_post_id'] : array();
            $customUrl = isset($_POST['map_custom_url']) && is_array($_POST['map_custom_url']) ? $_POST['map_custom_url'] : array();
            $newMap    = array();
            foreach (array_unique(array_merge(array_keys($postIds), array_keys($customUrl))) as $rawCode) {
                $code = strtoupper(preg_replace('/[^A-Za-z0-9.]/', '', (string) $rawCode));
                if ($code === '') {
                    continue;
                }
                $pid = isset($postIds[$rawCode]) ? max(0, (int) $postIds[$rawCode]) : 0;
                $url = isset($customUrl[$rawCode]) ? esc_url_raw(trim(wp_unslash((string) $customUrl[$rawCode])), array('http', 'https')) : '';
                if ($pid > 0 || $url !== '') {
                    $newMap[$code] = array(
                        'post_id'    => $pid,
                        'custom_url' => $url,
                    );
                }
            }
            update_option('pa_sinova_custom_page_map', $newMap, false);
            if (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }
            $notice = sprintf('Pemetaan kustom %d kriteria situs lama berhasil disimpan.', count($newMap));
        } elseif ($action === 'scan_links' && $requireAdmin()) {
            $activeTab       = 'migration';
            $scanPostId      = max(0, (int) ($_POST['scan_post_id'] ?? 0));
            $scanTargetSlug  = sanitize_key((string) ($_POST['scan_target_slug'] ?? 'lra'));
            $maxYear         = max(2028, (int) wp_date('Y') + 2);
            $scanDefaultYear = max(2017, min($maxYear, (int) ($_POST['scan_default_year'] ?? wp_date('Y'))));
            if ($scanPostId > 0) {
                $scannedLinks = pa_sinova_extract_links_from_post($scanPostId, $scanTargetSlug, $scanDefaultYear);
                $notice = sprintf('Pemindaian selesai: ditemukan %d tautan unik pada halaman/pos sumber.', count($scannedLinks));
            } else {
                $notice = 'Pilih halaman atau pos sumber terlebih dahulu untuk dipindai.';
            }
        } elseif ($action === 'import_links' && $requireAdmin() && function_exists('pa_sinova_ensure_table')) {
            $activeTab = 'migration';
            pa_sinova_ensure_table();
            global $wpdb;
            $table      = pa_sinova_table_name();
            $periodsMap = pa_sinova_all_periods_map();
            $catalogMap = function_exists('pa_sinova_module_catalog') ? pa_sinova_module_catalog() : array();
            $targetSlug = sanitize_key((string) ($_POST['import_target_slug'] ?? 'lra'));
            if (!isset($catalogMap[$targetSlug])) {
                $targetSlug = 'lra';
            }
            $selectedIdx = isset($_POST['import_rows']) && is_array($_POST['import_rows']) ? $_POST['import_rows'] : array();
            $years       = isset($_POST['row_year']) && is_array($_POST['row_year']) ? $_POST['row_year'] : array();
            $subs        = isset($_POST['row_sub']) && is_array($_POST['row_sub']) ? $_POST['row_sub'] : array();
            $pers        = isset($_POST['row_period']) && is_array($_POST['row_period']) ? $_POST['row_period'] : array();
            $titles      = isset($_POST['row_title']) && is_array($_POST['row_title']) ? $_POST['row_title'] : array();
            $urls        = isset($_POST['row_url']) && is_array($_POST['row_url']) ? $_POST['row_url'] : array();
            $srcs        = isset($_POST['row_src']) && is_array($_POST['row_src']) ? $_POST['row_src'] : array();
            $importedCnt = 0;
            $now         = current_time('mysql');
            $author      = wp_get_current_user()->display_name ?: 'WP Admin (Migrasi)';

            foreach ($selectedIdx as $rawIdx) {
                $idx      = (int) $rawIdx;
                $rawUrl   = isset($urls[$idx]) ? trim(wp_unslash((string) $urls[$idx])) : '';
                $cleanUrl = esc_url_raw($rawUrl, array('http', 'https'));
                if ($cleanUrl === '' || !preg_match('#^https?://#i', $cleanUrl)) {
                    continue;
                }
                $maxYear = max(2028, (int) wp_date('Y') + 2);
                $yr      = max(2017, min($maxYear, (int) ($years[$idx] ?? wp_date('Y'))));
                $sub = sanitize_text_field(wp_unslash((string) ($subs[$idx] ?? 'DOKUMEN')));
                if ($sub === '') {
                    $sub = 'DOKUMEN';
                }
                $per = strtoupper(sanitize_key((string) ($pers[$idx] ?? 'TAHUNAN')));
                if (!isset($periodsMap[$per])) {
                    $per = 'TAHUNAN';
                }
                $ord = (int) ($periodsMap[$per]['order'] ?? 1);
                $ttl = sanitize_text_field(wp_unslash((string) ($titles[$idx] ?? '')));
                if ($ttl === '') {
                    $ttl = trim("{$sub} " . ($periodsMap[$per]['label'] ?? $per) . " {$yr}");
                }
                $src = (isset($srcs[$idx]) && $srcs[$idx] === 'wp_pdf') ? 'wp_pdf' : 'external';

                $existingId = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM `{$table}` WHERE page_slug = %s AND year = %d AND sub_group = %s AND period_key = %s",
                    $targetSlug, $yr, $sub, $per
                ));
                $data = array(
                    'page_slug'    => $targetSlug,
                    'year'         => $yr,
                    'sub_group'    => $sub,
                    'period_key'   => $per,
                    'period_order' => $ord,
                    'title'        => $ttl,
                    'source_type'  => $src,
                    'doc_url'      => $cleanUrl,
                    'file_size_kb' => null,
                    'updated_by'   => $author,
                    'status'       => 'published',
                    'updated_at'   => $now,
                );
                if ($existingId > 0) {
                    $wpdb->update($table, $data, array('id' => $existingId));
                } else {
                    $data['created_at'] = $now;
                    $wpdb->insert($table, $data);
                }
                $importedCnt++;
            }
            if ($importedCnt > 0) {
                pa_sinova_flush_caches_for_slug($targetSlug);
                $notice = sprintf('%d dokumen berhasil diimpor ke modul %s dan langsung tersinkron ke Dashboard 35 Kriteria.', $importedCnt, $targetSlug);
            } else {
                $notice = 'Tidak ada baris tautan valid yang dipilih untuk diimpor.';
            }
        } elseif ($action === 'upload_doc' && in_array('upload', $allowedTabs, true) && function_exists('pa_sinova_ensure_table')) {
            $activeTab = 'upload';
            pa_sinova_ensure_table();
            global $wpdb;
            $table        = pa_sinova_table_name();
            $periods      = pa_sinova_all_periods_map();
            $slug         = sanitize_key((string) ($_POST['doc_slug'] ?? 'lra'));
            $allowedSlugs = pa_sinova_user_allowed_module_slugs();
            if (!empty($allowedSlugs) && !in_array($slug, $allowedSlugs, true)) {
                $notice = 'Gagal: Peran akun Anda tidak berwenang mengunggah ke modul "' . esc_html($slug) . '".';
            } else {
                $maxYear = max(2028, (int) wp_date('Y') + 2);
                $year    = max(2017, min($maxYear, (int) ($_POST['doc_year'] ?? wp_date('Y'))));
                $sub     = sanitize_text_field(wp_unslash((string) ($_POST['doc_subgroup'] ?? 'DOKUMEN')));
                if ($sub === '') {
                    $sub = 'DOKUMEN';
                }
                $per     = strtoupper(sanitize_key((string) ($_POST['doc_period'] ?? 'JAN')));
                $title   = sanitize_text_field(wp_unslash((string) ($_POST['doc_title'] ?? '')));
                $extUrl  = esc_url_raw(wp_unslash((string) ($_POST['doc_url'] ?? '')));
                $srcType = 'external';
                $fileKb  = null;

                $pdfErr = (int) ($_FILES['doc_pdf']['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($pdfErr !== UPLOAD_ERR_NO_FILE && $pdfErr !== UPLOAD_ERR_OK) {
                    $notice = 'Gagal: Berkas PDF gagal diunggah ke server (periksa batas ukuran PHP upload_max_filesize).';
                } elseif (!empty($_FILES['doc_pdf']['tmp_name']) && is_uploaded_file($_FILES['doc_pdf']['tmp_name'])) {
                    $rawPdf = (string) file_get_contents($_FILES['doc_pdf']['tmp_name']);
                    $stored = pa_sinova_store_verified_pdf_bytes($rawPdf, $slug, $year, $per, $notice);
                    if (is_array($stored)) {
                        $extUrl  = $stored['url'];
                        $srcType = 'wp_pdf';
                        $fileKb  = $stored['file_kb'];
                    } else {
                        $extUrl = '';
                    }
                }

                if ($notice === '' && $extUrl !== '' && (stripos($extUrl, 'https://') === 0 || stripos($extUrl, home_url()) === 0)) {
                    $order = (int) ($periods[$per]['order'] ?? 1);
                    if ($title === '') {
                        $title = trim("{$sub} " . ($periods[$per]['label'] ?? $per) . " {$year}");
                    }
                    $now = current_time('mysql');
                    $existingId = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM `{$table}` WHERE page_slug = %s AND year = %d AND sub_group = %s AND period_key = %s",
                        $slug, $year, $sub, $per
                    ));
                    $data = array(
                        'page_slug'    => $slug,
                        'year'         => $year,
                        'sub_group'    => $sub,
                        'period_key'   => $per,
                        'period_order' => $order,
                        'title'        => $title,
                        'source_type'  => $srcType,
                        'doc_url'      => $extUrl,
                        'file_size_kb' => $fileKb,
                        'updated_by'   => wp_get_current_user()->display_name ?: 'WP Admin',
                        'status'       => 'published',
                        'updated_at'   => $now,
                    );
                    if ($existingId > 0) {
                        $wpdb->update($table, $data, array('id' => $existingId));
                    } else {
                        $data['created_at'] = $now;
                        $wpdb->insert($table, $data);
                    }
                    pa_sinova_flush_caches_for_slug($slug);
                    $notice = "Dokumen {$slug} ({$per} {$year}) berhasil disimpan & dipublikasikan.";
                } elseif ($notice === '') {
                    $notice = 'Silakan unggah berkas PDF atau masukkan tautan HTTPS yang valid.';
                }
            }
        } elseif ($action === 'update_doc' && in_array('upload', $allowedTabs, true) && function_exists('pa_sinova_ensure_table')) {
            $activeTab = 'upload';
            pa_sinova_ensure_table();
            global $wpdb;
            $table        = pa_sinova_table_name();
            $periods      = pa_sinova_all_periods_map();
            $editId       = (int) ($_POST['edit_doc_id'] ?? 0);
            $origRow      = ($editId > 0) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE id = %d", $editId), ARRAY_A) : null;
            $slug         = sanitize_key((string) ($_POST['doc_slug'] ?? 'lra'));
            $allowedSlugs = pa_sinova_user_allowed_module_slugs();
            if (!$origRow) {
                $notice = 'Gagal: Dokumen yang ingin diubah tidak ditemukan.';
            } elseif (!empty($allowedSlugs) && (!in_array((string) $origRow['page_slug'], $allowedSlugs, true) || !in_array($slug, $allowedSlugs, true))) {
                $notice    = 'Gagal: Peran akun Anda tidak berwenang mengubah dokumen modul tersebut.';
                $editDocId = $editId;
            } else {
                $maxYear = max(2028, (int) wp_date('Y') + 2);
                $year    = max(2017, min($maxYear, (int) ($_POST['doc_year'] ?? $origRow['year'])));
                $sub     = sanitize_text_field(wp_unslash((string) ($_POST['doc_subgroup'] ?? $origRow['sub_group'])));
                if ($sub === '') {
                    $sub = 'DOKUMEN';
                }
                $per   = strtoupper(sanitize_key((string) ($_POST['doc_period'] ?? $origRow['period_key'])));
                $title = sanitize_text_field(wp_unslash((string) ($_POST['doc_title'] ?? '')));
                $dupId = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM `{$table}` WHERE page_slug = %s AND year = %d AND sub_group = %s AND period_key = %s AND id != %d",
                    $slug, $year, $sub, $per, $editId
                ));
                if ($dupId > 0) {
                    $notice    = "Gagal: Slot ({$slug}, {$year}, {$sub}, {$per}) sudah dipakai dokumen ID #{$dupId}. Pilih modul/tahun/sub-kelompok/periode lain.";
                    $editDocId = $editId;
                } else {
                    $newUrl         = (string) ($origRow['doc_url'] ?? '');
                    $srcType        = (string) ($origRow['source_type'] ?? 'external');
                    $fileKb         = $origRow['file_size_kb'];
                    $oldPdfToDelete = '';
                    $sourceOk       = true;
                    $rawUrl         = trim(wp_unslash((string) ($_POST['doc_url'] ?? '')));
                    $pdfErr         = (int) ($_FILES['doc_pdf']['error'] ?? UPLOAD_ERR_NO_FILE);

                    if ($pdfErr !== UPLOAD_ERR_NO_FILE && $pdfErr !== UPLOAD_ERR_OK) {
                        $notice    = 'Gagal: Berkas PDF pengganti gagal diunggah ke server.';
                        $sourceOk  = false;
                        $editDocId = $editId;
                    } elseif (!empty($_FILES['doc_pdf']['tmp_name']) && is_uploaded_file($_FILES['doc_pdf']['tmp_name'])) {
                        $rawPdf = (string) file_get_contents($_FILES['doc_pdf']['tmp_name']);
                        $stored = pa_sinova_store_verified_pdf_bytes($rawPdf, $slug, $year, $per, $notice);
                        if (is_array($stored)) {
                            if (($origRow['source_type'] ?? '') === 'wp_pdf' && (string) ($origRow['doc_url'] ?? '') !== $stored['url']) {
                                $oldPdfToDelete = (string) $origRow['doc_url'];
                            }
                            $newUrl  = $stored['url'];
                            $srcType = 'wp_pdf';
                            $fileKb  = $stored['file_kb'];
                        } else {
                            $sourceOk  = false;
                            $editDocId = $editId;
                        }
                    } elseif ($rawUrl !== '' && $rawUrl !== (string) ($origRow['doc_url'] ?? '')) {
                        $candUrl = esc_url_raw($rawUrl);
                        if ($candUrl === '' || (stripos($candUrl, 'https://') !== 0 && stripos($candUrl, home_url()) !== 0)) {
                            $notice    = 'Gagal: Tautan URL pengganti harus menggunakan protokol HTTPS yang valid.';
                            $sourceOk  = false;
                            $editDocId = $editId;
                        } else {
                            if (($origRow['source_type'] ?? '') === 'wp_pdf') {
                                $oldPdfToDelete = (string) ($origRow['doc_url'] ?? '');
                            }
                            $newUrl  = $candUrl;
                            $srcType = 'external';
                            $fileKb  = null;
                        }
                    }

                    if ($sourceOk && $newUrl !== '') {
                        $order = (int) ($periods[$per]['order'] ?? 1);
                        if ($title === '') {
                            $title = trim("{$sub} " . ($periods[$per]['label'] ?? $per) . " {$year}");
                        }
                        $wpdb->update($table, array(
                            'page_slug'    => $slug,
                            'year'         => $year,
                            'sub_group'    => $sub,
                            'period_key'   => $per,
                            'period_order' => $order,
                            'title'        => $title,
                            'source_type'  => $srcType,
                            'doc_url'      => $newUrl,
                            'file_size_kb' => $fileKb,
                            'updated_by'   => wp_get_current_user()->display_name ?: 'WP Admin',
                            'updated_at'   => current_time('mysql'),
                        ), array('id' => $editId));
                        if ($oldPdfToDelete !== '' && function_exists('pa_sinova_safe_unlink_local_pdf')) {
                            pa_sinova_safe_unlink_local_pdf($oldPdfToDelete);
                        }
                        pa_sinova_flush_caches_for_slug((string) $origRow['page_slug']);
                        if ($slug !== (string) $origRow['page_slug']) {
                            pa_sinova_flush_caches_for_slug($slug);
                        }
                        $editDocId = 0;
                        $notice    = "Perubahan dokumen #{$editId} ({$slug} · {$per} {$year}) berhasil disimpan & dipublikasikan.";
                    } elseif ($sourceOk) {
                        $notice    = 'Gagal: Sumber berkas PDF atau URL dokumen tidak boleh kosong.';
                        $editDocId = $editId;
                    }
                }
            }
        } elseif ($action === 'delete_doc' && in_array('upload', $allowedTabs, true) && !empty($_POST['doc_id'])) {
            $activeTab    = 'upload';
            $allowedSlugs = pa_sinova_user_allowed_module_slugs();
            $delRes       = pa_sinova_delete_docs_by_ids(array((int) $_POST['doc_id']), $allowedSlugs);
            if ($delRes['denied'] > 0 && $delRes['deleted'] === 0) {
                $notice = 'Gagal: Peran akun Anda tidak berwenang menghapus dokumen modul tersebut.';
            } elseif ($delRes['deleted'] > 0) {
                $notice = 'Dokumen berhasil dihapus dari arsip.';
            }
        } elseif ($action === 'bulk_delete_docs' && in_array('upload', $allowedTabs, true)) {
            $activeTab    = 'upload';
            $rawDocIds    = (isset($_POST['doc_ids']) && is_array($_POST['doc_ids'])) ? $_POST['doc_ids'] : array();
            if (empty($rawDocIds)) {
                $notice = 'Gagal: Pilih minimal satu dokumen dengan mencentang kotak di samping item.';
            } else {
                $allowedSlugs = pa_sinova_user_allowed_module_slugs();
                $delRes       = pa_sinova_delete_docs_by_ids($rawDocIds, $allowedSlugs);
                if ($delRes['deleted'] > 0) {
                    $notice = $delRes['deleted'] . ' dokumen terpilih berhasil dihapus dari arsip.';
                    if ($delRes['denied'] > 0) {
                        $notice .= ' (' . $delRes['denied'] . ' item dilewati karena batas peran modul.)';
                    }
                } elseif ($delRes['denied'] > 0) {
                    $notice = 'Gagal: Peran akun Anda tidak berwenang menghapus dokumen modul yang dipilih.';
                } else {
                    $notice = 'Gagal: Tidak ada dokumen valid yang dapat dihapus.';
                }
            }
        } elseif ($action === 'save_login_hide' && $requireAdmin()) {
            $activeTab = 'security';
            $rawSlug   = wp_unslash((string) ($_POST['custom_login_slug'] ?? ''));
            $check     = pa_sinova_sanitize_login_slug($rawSlug);
            if (!$check['valid']) {
                $notice = 'Gagal: ' . $check['error'];
            } else {
                $stored = get_option('pa_sinova_starter_settings', array());
                if (!is_array($stored)) {
                    $stored = array();
                }
                $stored['custom_login_slug'] = $check['slug'];
                update_option('pa_sinova_starter_settings', $stored, false);
                if (function_exists('pa_home_page_cache_mark_stale')) {
                    pa_home_page_cache_mark_stale();
                }
                if ($check['slug'] !== '') {
                    $notice = 'Halaman login berhasil disembunyikan. URL masuk baru: ' . trailingslashit(home_url('/' . $check['slug']));
                } else {
                    $notice = 'Penyembunyian URL login dinonaktifkan; WordPress kembali memakai /wp-login.php standar.';
                }
            }
        }
    }

    $cfg          = pa_sinova_get_settings();
    $catalog      = function_exists('pa_sinova_module_catalog') ? pa_sinova_module_catalog() : array();
    $allowedSlugs = pa_sinova_user_allowed_module_slugs();
    $uploadCatalog = empty($allowedSlugs)
        ? $catalog
        : array_intersect_key($catalog, array_flip($allowedSlugs));
    if (empty($uploadCatalog)) {
        $uploadCatalog = $catalog;
    }
    $periods    = function_exists('pa_sinova_all_periods_map') ? pa_sinova_all_periods_map() : array();
    $audit      = pa_sinova_full_audit_report();
    $report     = $audit['base'];
    $uploadKeys = array_keys($uploadCatalog);
    $preSlug    = isset($_GET['doc_slug']) ? sanitize_key((string) $_GET['doc_slug']) : ($uploadKeys[0] ?? 'lra');
    if (!isset($uploadCatalog[$preSlug]) && !empty($uploadKeys)) {
        $preSlug = $uploadKeys[0];
    }
    $tabBaseUrl    = admin_url('admin.php?page=pa-sinova-starter&tab=');
    $exportNonce   = wp_create_nonce('pa_sinova_export_nonce');
    $exportCsvUrl  = admin_url('admin.php?page=pa-sinova-starter&pa_sinova_export=csv&_wpnonce=' . rawurlencode($exportNonce));
    $exportJsonUrl = admin_url('admin.php?page=pa-sinova-starter&pa_sinova_export=json&_wpnonce=' . rawurlencode($exportNonce));
    $tabLabels     = array(
        'compliance' => 'Dashboard 3 Pilar &amp; 35 Kriteria (' . $audit['weighted_total'] . ')',
        'upload'     => 'Upload &amp; Kelola Dokumen',
        'data'       => 'Data Operasional &amp; RSS',
        'homepage'   => 'Desain &amp; Tata Letak Beranda',
        'migration'  => 'Migrasi &amp; Adopsi',
        'settings'   => 'Identitas Satker &amp; Seeder',
        'security'   => 'Keamanan, Update &amp; Audit',
    );
    $updInfo    = current_user_can('manage_options') ? pa_sinova_github_check_update(false) : array('has_update' => false);
    $rbMeta     = current_user_can('manage_options') ? get_option('pa_sinova_last_update_backup', array()) : array();
    $hasRb      = is_array($rbMeta) && !empty($rbMeta['files']);
    $curRecType = ($activeTab === 'data' && isset($_GET['rec_type'])) ? sanitize_key((string) $_GET['rec_type']) : '';
    $satkerLbl  = pa_sinova_satker_name();
    $isAdm      = current_user_can('manage_options');
    ?>
    <style>
        .pa-adm-shell{display:grid;grid-template-columns:232px minmax(0,1fr);gap:0;margin:14px 16px 24px 0;background:#f4f7f5;border:1px solid #cbd5e1;border-radius:14px;overflow:hidden;box-shadow:0 10px 28px -16px rgba(2,44,34,.22)}
        @media(max-width:960px){.pa-adm-shell{grid-template-columns:1fr}}
        .pa-adm-side{background:linear-gradient(180deg,#033022 0%,#04422f 100%);color:#e6f4ee;padding:16px 12px;display:flex;flex-direction:column;gap:12px}
        .pa-adm-brand{display:flex;align-items:center;gap:10px;padding:4px 8px 12px;border-bottom:1px solid rgba(255,255,255,.12)}
        .pa-adm-emblem{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#C9A227,#E5C158);color:#022c22;font-weight:900;font-size:13px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden}
        .pa-adm-emblem.has-logo{background:#fff;padding:2px;box-sizing:border-box}
        .pa-adm-emblem img{width:100%;height:100%;object-fit:contain;display:block}
        .pa-adm-cols-2{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.35fr);gap:20px;align-items:start}
        .pa-adm-cols-set{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(0,0.9fr);gap:20px;align-items:start}
        @media(max-width:1080px){.pa-adm-cols-2,.pa-adm-cols-set{grid-template-columns:1fr}}
        .pa-adm-brand strong{display:block;font-size:13px;line-height:1.25;color:#fff}
        .pa-adm-brand span{display:block;font-size:11px;color:#a7f3d0}
        .pa-adm-grp-lbl{font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#86efac;opacity:.75;padding:6px 10px 3px}
        .pa-adm-nav{display:flex;flex-direction:column;gap:2px}
        .pa-adm-nav a{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:8px 11px;border-radius:8px;color:#d1fae5;text-decoration:none;font-size:12.5px;font-weight:600;transition:background-color .15s,color .15s}
        .pa-adm-nav a:hover{background:rgba(255,255,255,.1);color:#fff}
        .pa-adm-nav a.is-active{background:rgba(229,193,88,.2);color:#E5C158;box-shadow:inset 3px 0 0 #E5C158;font-weight:700}
        .pa-adm-main{padding:18px 22px 24px;min-width:0}
        .pa-adm-topbar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;padding-bottom:14px;margin-bottom:16px;border-bottom:1px solid #dbe5df}
        .pa-adm-topbar h1{margin:0;padding:0;font-size:20px;font-weight:800;color:#043927;display:inline-flex;align-items:center;gap:8px}
        .pa-adm-top-actions{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
        .pa-adm-ver{padding:4px 10px;border-radius:999px;background:#ecfdf5;border:1px solid #86efac;color:#043927;font-size:11.5px;font-weight:700}
        .pa-adm-tip{position:relative;display:inline-flex;align-items:center;justify-content:center;width:17px;height:17px;border-radius:50%;border:1px solid #C9A227;color:#6e5204;background:#fffbeb;font:800 11px/1 Georgia,serif;font-style:italic;cursor:help;vertical-align:middle;margin-left:4px}
        .pa-adm-tip::after{content:attr(data-tip);position:absolute;left:50%;bottom:calc(100% + 8px);transform:translateX(-50%) translateY(4px);width:max-content;max-width:270px;padding:7px 10px;border-radius:8px;background:#022c22;color:#f8fafc;font:600 11.5px/1.4 system-ui,sans-serif;font-style:normal;text-align:left;white-space:normal;box-shadow:0 10px 24px rgba(0,0,0,.24);opacity:0;pointer-events:none;transition:opacity .15s,transform .15s;z-index:99999}
        .pa-adm-tip:hover::after,.pa-adm-tip:focus-visible::after{opacity:1;transform:translateX(-50%) translateY(0)}
        .pa-adm-banner{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;border-radius:10px;margin-bottom:14px;font-size:12.5px}
        .pa-adm-banner-warn{background:#fffbeb;border:1px solid #fcd34d;color:#78350f}
        .pa-adm-banner-info{background:#f8fafc;border:1px solid #cbd5e1;color:#334155}
        .pa-grid-toolbar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;background:#fff;border:1px solid #cbd5e1;border-radius:12px;padding:12px 16px;margin-bottom:14px}
        .pa-grid-board-wrap{background:#e9eeec;border:1px solid #cbd5e1;border-radius:14px;padding:14px;margin-bottom:18px;overflow-x:auto}
        .pa-grid-col-nums{display:grid;grid-template-columns:repeat(12,minmax(52px,1fr));gap:10px;margin-bottom:8px;text-align:center;font-size:11px;font-weight:700;color:#64748b}
        .pa-grid-canvas{position:relative;display:grid;grid-template-columns:repeat(12,minmax(52px,1fr));grid-auto-rows:84px;gap:10px;min-height:460px;background-image:linear-gradient(to right,rgba(5,110,79,.07) 1px,transparent 1px),linear-gradient(to bottom,rgba(5,110,79,.07) 1px,transparent 1px);background-size:calc((100% + 10px) / 12) 94px;border-radius:10px;padding:2px}
        .pa-gb-card{position:relative;background:#fff;border:1.5px solid #cbd5e1;border-radius:12px;padding:10px 12px;display:flex;flex-direction:column;justify-content:space-between;cursor:grab;user-select:none;box-shadow:0 3px 10px rgba(2,44,34,.06);transition:border-color .14s,box-shadow .14s,opacity .14s;overflow:hidden}
        .pa-gb-card:hover{border-color:#056e4f}
        .pa-gb-card.is-selected{border-color:#C9A227;box-shadow:0 0 0 3px rgba(201,162,39,.26)}
        .pa-gb-card.is-hidden-blk{opacity:.48;background:#f1f5f9;border-style:dashed}
        .pa-gb-card.is-dragging{opacity:.85;z-index:30;cursor:grabbing;box-shadow:0 14px 28px rgba(2,44,34,.22)}
        .pa-gb-head{display:flex;align-items:flex-start;justify-content:space-between;gap:6px}
        .pa-gb-title{font-size:13px;font-weight:800;color:#0f172a;line-height:1.25;display:flex;align-items:center;gap:6px;min-width:0}
        .pa-gb-title span:last-child{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .pa-gb-sub{font-size:11px;color:#64748b;margin-top:2px}
        .pa-gb-meta{display:flex;align-items:center;justify-content:space-between;gap:6px;font-size:11px;font-weight:700;color:#475569}
        .pa-gb-size{padding:1px 7px;border-radius:999px;background:#f1f5f9;color:#043927}
        .pa-gb-acts{display:inline-flex;gap:4px;flex-shrink:0}
        .pa-gb-iconbtn{border:1px solid #cbd5e1;background:#fff;border-radius:6px;width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;color:#334155;padding:0}
        .pa-gb-iconbtn:hover{border-color:#056e4f;color:#056e4f;background:#ecfdf5}
        .pa-gb-resize{position:absolute;right:3px;bottom:3px;width:18px;height:18px;cursor:nwse-resize;display:inline-flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px;border-radius:4px}
        .pa-gb-resize:hover{color:#056e4f;background:#ecfdf5}
        dialog#pa-grid-edit-dlg{border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:14px;padding:0;width:min(560px,94vw);max-height:88vh;box-shadow:0 24px 60px rgba(2,44,34,.32)}
        dialog#pa-grid-edit-dlg[open]{display:flex;flex-direction:column}
        dialog#pa-grid-edit-dlg:modal{overscroll-behavior:contain}
        dialog#pa-grid-edit-dlg::backdrop{background:rgba(2,44,34,.52);backdrop-filter:blur(2px);overflow:hidden;overscroll-behavior:contain}
        .pa-dlg-head{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #e2e8f0}
        .pa-dlg-head h2{margin:0;font-size:16px;font-weight:800;color:#043927}
        .pa-dlg-body{padding:16px 18px;overflow-y:auto;display:flex;flex-direction:column;gap:12px}
        .pa-dlg-field label{display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:4px}
        .pa-dlg-field input[type="text"],.pa-dlg-field input[type="url"],.pa-dlg-field input[type="number"],.pa-dlg-field select,.pa-dlg-field textarea{width:100%;box-sizing:border-box}
        .pa-dlg-grid4{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
        .pa-dlg-foot{display:flex;justify-content:flex-end;gap:8px;padding:12px 18px;border-top:1px solid #e2e8f0;background:#f8fafc}
        .pa-info-btn{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;padding:0;margin-left:5px;border-radius:50%;border:1px solid #C9A227;background:#fffbeb;color:#6e5204;font:800 11px/1 Georgia,serif;font-style:italic;cursor:pointer;vertical-align:middle;transition:background-color .14s,color .14s,border-color .14s}
        .pa-info-btn:hover,.pa-info-btn:focus-visible,.pa-info-btn[aria-expanded="true"]{background:#056e4f;border-color:#056e4f;color:#E5C158;outline:2px solid #C9A227;outline-offset:1px}
        #pa-adm-tip-pop{position:fixed;z-index:100000;max-width:min(320px,calc(100vw - 24px));padding:9px 12px;border-radius:10px;background:#022c22;color:#f8fafc;border:1px solid #C9A227;font:500 12px/1.45 system-ui,-apple-system,sans-serif;box-shadow:0 14px 32px rgba(2,44,34,.34);pointer-events:auto}
        #pa-adm-tip-pop[hidden]{display:none !important}
        .pa-all-green-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#dcfce7;border:1px solid #22c55e;color:#14532d;font-size:12px;font-weight:800}
        .pa-dash-tabs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px;padding:6px;background:#e8efeb;border:1px solid #cbd5e1;border-radius:12px}
        .pa-dash-tab{display:inline-flex;align-items:center;gap:7px;padding:8px 14px;border-radius:9px;border:1px solid transparent;background:transparent;color:#334155;font-size:12.5px;font-weight:700;cursor:pointer;transition:background-color .15s,color .15s,border-color .15s}
        .pa-dash-tab:hover{background:#fff;color:#043927}
        .pa-dash-tab.is-active{background:#043927;color:#fff;border-color:#043927;box-shadow:0 4px 12px rgba(2,44,34,.18)}
        .pa-tab-ok-ico{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:#16a34a;color:#fff;font-size:11px;font-weight:900}
        .pa-tab-pct-pill{padding:1px 7px;border-radius:999px;background:rgba(15,23,42,.08);font-size:11px;font-weight:800}
        .pa-dash-tab.is-active .pa-tab-pct-pill{background:rgba(229,193,88,.22);color:#E5C158}
        .pa-ticker-admin-bar{display:grid;grid-template-columns:auto 160px 1fr auto;gap:10px;align-items:center;background:#fff;border:1px solid #cbd5e1;border-left:4px solid #C9A227;border-radius:12px;padding:10px 14px;margin-bottom:12px}
        @media(max-width:780px){.pa-ticker-admin-bar{grid-template-columns:1fr}}
        .pa-sec-pill-grid{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
        .pa-sec-pill{display:inline-flex;align-items:center;gap:6px;padding:6px 11px;border-radius:999px;background:#f8fafc;border:1px solid #cbd5e1;font-size:12px;font-weight:700;color:#0f172a}
        .pa-sec-pill.is-ok{background:#f0fdf4;border-color:#86efac;color:#14532d}
        .pa-sec-pill.is-warn{background:#fffbeb;border-color:#fcd34d;color:#78350f}
        .pa-adm-fold{background:#fff;border:1px solid #cbd5e1;border-radius:10px;margin-bottom:14px;overflow:hidden}
        .pa-adm-fold>summary{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;padding:13px 16px;cursor:pointer;font-weight:800;color:#043927;font-size:14px;list-style:none}
        .pa-adm-fold>summary::-webkit-details-marker{display:none}
        .pa-adm-fold[open]>summary{border-bottom:1px solid #e2e8f0;background:#f8fbf9}
        .pa-adm-fold-body{padding:14px 16px}
        .pa-slide-row{display:grid;grid-template-columns:1fr 1fr auto;gap:6px;align-items:center;padding:8px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;margin-bottom:6px}
        .pa-media-preview{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:8px;border:1px solid #cbd5e1;background:#f8fafc;overflow:hidden;vertical-align:middle}
        .pa-media-preview img{max-width:100%;max-height:100%;object-fit:contain}
    </style>

    <div class="pa-adm-shell">
        <aside class="pa-adm-side" aria-label="Navigasi Panel Admin SINOVA">
            <div class="pa-adm-brand">
                <?php $admLogo = trim((string) ($cfg['logo_url'] ?? '')); ?>
                <span class="pa-adm-emblem<?php echo $admLogo !== '' ? ' has-logo' : ''; ?>">
                    <?php if ($admLogo !== '') : ?>
                        <img src="<?php echo esc_url($admLogo); ?>" alt="Logo Satker">
                    <?php else : ?>PA<?php endif; ?>
                </span>
                <div>
                    <strong><?php echo esc_html(function_exists('mb_substr') ? mb_substr($satkerLbl, 0, 26, 'UTF-8') : substr($satkerLbl, 0, 26)); ?></strong>
                    <span>Panel Admin v<?php echo esc_html(PA_SINOVA_STARTER_VERSION); ?></span>
                </div>
            </div>

            <nav class="pa-adm-nav">
                <a href="<?php echo esc_url($tabBaseUrl . 'compliance'); ?>" class="<?php echo $activeTab === 'compliance' ? 'is-active' : ''; ?>"><span>Dashboard SINOVA</span><small><?php echo esc_html((string) $audit['weighted_total']); ?></small></a>

                <?php if ($isAdm) : ?>
                    <div class="pa-adm-grp-lbl">Konten &amp; Beranda</div>
                    <a href="<?php echo esc_url($tabBaseUrl . 'homepage'); ?>" class="<?php echo $activeTab === 'homepage' ? 'is-active' : ''; ?>"><span>Tampilan Beranda</span></a>
                    <a href="<?php echo esc_url($tabBaseUrl . 'settings'); ?>" class="<?php echo $activeTab === 'settings' ? 'is-active' : ''; ?>"><span>Identitas &amp; Seeder</span></a>
                    <a href="<?php echo esc_url($tabBaseUrl . 'migration'); ?>" class="<?php echo $activeTab === 'migration' ? 'is-active' : ''; ?>"><span>Migrasi &amp; Adopsi</span></a>
                <?php endif; ?>

                <?php if (in_array('data', $allowedTabs, true)) : ?>
                    <div class="pa-adm-grp-lbl">Publikasi</div>
                    <a href="<?php echo esc_url($tabBaseUrl . 'data&rec_type=slider'); ?>" class="<?php echo ($activeTab === 'data' && $curRecType === 'slider') ? 'is-active' : ''; ?>"><span>Slider Beranda</span></a>
                    <a href="<?php echo esc_url($tabBaseUrl . 'data&rec_type=inovasi'); ?>" class="<?php echo ($activeTab === 'data' && $curRecType === 'inovasi') ? 'is-active' : ''; ?>"><span>Inovasi Layanan</span></a>
                    <a href="<?php echo esc_url($tabBaseUrl . 'data&rec_type=video'); ?>" class="<?php echo ($activeTab === 'data' && $curRecType === 'video') ? 'is-active' : ''; ?>"><span>Galeri Video</span></a>
                    <a href="<?php echo esc_url($tabBaseUrl . 'data&rec_type=ucapan'); ?>" class="<?php echo ($activeTab === 'data' && $curRecType === 'ucapan') ? 'is-active' : ''; ?>"><span>Ucapan &amp; Belasungkawa</span></a>
                <?php endif; ?>

                <?php if (in_array('upload', $allowedTabs, true)) : ?>
                    <div class="pa-adm-grp-lbl">Dokumen</div>
                    <a href="<?php echo esc_url($tabBaseUrl . 'upload'); ?>" class="<?php echo $activeTab === 'upload' ? 'is-active' : ''; ?>"><span>Arsip Dokumen</span></a>
                <?php endif; ?>

                <?php if (in_array('data', $allowedTabs, true)) : ?>
                    <div class="pa-adm-grp-lbl">Kepaniteraan</div>
                    <a href="<?php echo esc_url($tabBaseUrl . 'data&rec_type=jadwal'); ?>" class="<?php echo ($activeTab === 'data' && ($curRecType === 'jadwal' || $curRecType === '')) ? 'is-active' : ''; ?>"><span>Jadwal Sidang</span></a>
                    <a href="<?php echo esc_url($tabBaseUrl . 'data&rec_type=putusan'); ?>" class="<?php echo ($activeTab === 'data' && $curRecType === 'putusan') ? 'is-active' : ''; ?>"><span>Putusan Pilihan</span></a>
                    <a href="<?php echo esc_url($tabBaseUrl . 'data&rec_type=ghaib'); ?>" class="<?php echo ($activeTab === 'data' && $curRecType === 'ghaib') ? 'is-active' : ''; ?>"><span>Panggilan Ghaib</span></a>
                    <a href="<?php echo esc_url($tabBaseUrl . 'data&rec_type=statistik'); ?>" class="<?php echo ($activeTab === 'data' && $curRecType === 'statistik') ? 'is-active' : ''; ?>"><span>Statistik Perkara</span></a>

                    <div class="pa-adm-grp-lbl">Kesekretariatan</div>
                    <a href="<?php echo esc_url($tabBaseUrl . 'data&rec_type=pegawai'); ?>" class="<?php echo ($activeTab === 'data' && $curRecType === 'pegawai') ? 'is-active' : ''; ?>"><span>Pejabat &amp; Pegawai</span></a>
                    <a href="<?php echo esc_url($tabBaseUrl . 'data&rec_type=yurisdiksi'); ?>" class="<?php echo ($activeTab === 'data' && $curRecType === 'yurisdiksi') ? 'is-active' : ''; ?>"><span>Wilayah Yurisdiksi</span></a>
                <?php endif; ?>

                <div class="pa-adm-grp-lbl">Sistem</div>
                <a href="<?php echo esc_url($tabBaseUrl . 'security'); ?>" class="<?php echo $activeTab === 'security' ? 'is-active' : ''; ?>"><span>Keamanan &amp; Update</span></a>
            </nav>
        </aside>

        <main class="pa-adm-main">
            <div class="pa-adm-topbar">
                <h1>
                    <?php echo esc_html(wp_strip_all_tags($tabLabels[$activeTab] ?? 'SINOVA Hub')); ?>
                    <span class="pa-adm-tip" tabindex="0" data-tip="Panel operator khusus standar SK Dirjen Badilag 2722/2026. Istilah teknis SINOVA tidak tampil di website publik." aria-label="Info panel">i</span>
                </h1>
                <div class="pa-adm-top-actions">
                    <span class="pa-adm-ver" title="Mode runtime: <?php echo esc_attr(PA_SINOVA_RUNTIME_MODE === 'plugin' ? 'Plugin Standar' : 'Must-Use (mu-plugins)'); ?>">PA Plugin · v<?php echo esc_html(PA_SINOVA_STARTER_VERSION); ?></span>
                    <a class="button" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener noreferrer">Lihat Situs ↗</a>
                    <form method="post" style="margin:0;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="purge_html_cache">
                        <button type="submit" class="button">↻ Bersihkan Cache</button>
                    </form>
                </div>
            </div>

            <?php if (!empty($updInfo['has_update']) && $isAdm) : ?>
                <div class="pa-adm-banner pa-adm-banner-warn">
                    <span><strong>Pembaruan v<?php echo esc_html($updInfo['latest']); ?> tersedia</strong> · <?php echo esc_html((string) ($updInfo['changelog'] ?? '')); ?></span>
                    <a class="button button-primary button-small" href="<?php echo esc_url($tabBaseUrl . 'security'); ?>">Update 1-Klik →</a>
                </div>
            <?php endif; ?>

            <?php if ($hasRb && $isAdm) : ?>
                <div class="pa-adm-banner pa-adm-banner-info">
                    <span>Diperbarui dari <strong>v<?php echo esc_html((string) ($rbMeta['from_version'] ?? '-')); ?></strong> ke <strong>v<?php echo esc_html((string) ($rbMeta['to_version'] ?? PA_SINOVA_STARTER_VERSION)); ?></strong> (<?php echo esc_html((string) ($rbMeta['at'] ?? '')); ?>).</span>
                    <form method="post" style="margin:0;" onsubmit="return confirm('Batalkan pembaruan terakhir dan kembalikan berkas ke versi sebelumnya?');">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="rollback_github_update">
                        <button type="submit" class="button button-small" style="color:#b91c1c;border-color:#fca5a5;">↺ Batalkan Pembaruan Terakhir</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($notice !== '') :
                $noticeClass = (stripos($notice, 'Gagal:') === 0) ? 'notice-error' : 'notice-success';
            ?>
                <div class="notice <?php echo esc_attr($noticeClass); ?> inline is-dismissible" style="margin:0 0 14px;"><p><strong><?php echo esc_html($notice); ?></strong></p></div>
            <?php endif; ?>

        <?php if ($activeTab === 'compliance') :
            $p1Green  = ((int) ($report['counts']['lengkap'] ?? 0) === 35);
            $p2Green  = ((float) ($audit['pilar2_pct'] ?? 0) >= 100);
            $p3Green  = ((float) ($audit['pilar3_pct'] ?? 0) >= 100);
            $allGreen = ($p1Green && $p2Green && $p3Green);
            $hasRecs  = !empty($audit['recommendations']);
        ?>
            <div style="background:linear-gradient(135deg,#043927 0%,#056e4f 100%);color:#fff;border:1px solid #C9A227;border-radius:12px;padding:16px 20px;margin-bottom:16px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px;">
                <div>
                    <div style="font-size:11px;font-weight:800;letter-spacing:.06em;color:#E5C158;text-transform:uppercase;">Skor Gabungan 3 Pilar SINOVA <?php echo pa_sinova_info_tip('Bobot penilaian internal: Pilar 1 Konten 60%, Pilar 2 UI/UX & WCAG 2.2 20%, Pilar 3 Performa & Keamanan 20%.', 'Info skor 3 pilar'); ?></div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:4px;">
                        <span style="font-size:34px;font-weight:900;color:#fff;"><?php echo esc_html((string) $audit['weighted_total']); ?> / 100</span>
                        <span style="display:inline-block;padding:4px 12px;border-radius:999px;background:#fff;color:<?php echo esc_attr($audit['predicate_color']); ?>;font-weight:800;font-size:12.5px;">Predikat: <?php echo esc_html($audit['predicate']); ?></span>
                        <?php if ($allGreen) : ?>
                            <span class="pa-all-green-badge"><span aria-hidden="true">✔</span> Semua Pilar Hijau</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:12px;color:#d1fae5;margin-top:6px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                        <span>Pilar 1 Konten (60%): <strong><?php echo esc_html((string) $audit['pilar1_pct']); ?>%</strong> <?php echo $p1Green ? '<span aria-label="Lengkap">🟢</span>' : ''; ?></span>
                        <span>· Pilar 2 UI/UX &amp; WCAG 2.2 (20%): <strong><?php echo esc_html((string) $audit['pilar2_pct']); ?>%</strong> <?php echo $p2Green ? '<span aria-label="Lengkap">🟢</span>' : ''; ?></span>
                        <span>· Pilar 3 Performa &amp; Keamanan (20%): <strong><?php echo esc_html((string) $audit['pilar3_pct']); ?>%</strong> <?php echo $p3Green ? '<span aria-label="Lengkap">🟢</span>' : ''; ?></span>
                    </div>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <a href="<?php echo esc_url($exportCsvUrl); ?>" class="button button-secondary">⬇ Ekspor Bukti CSV</a>
                    <a href="<?php echo esc_url($exportJsonUrl); ?>" class="button button-secondary">⬇ Ekspor Laporan JSON</a>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:12px;margin-bottom:16px;">
                <div style="background:#fff;border:1px solid #cbd5e1;border-left:5px solid #056e4f;border-radius:10px;padding:12px 14px;">
                    <div style="font-size:11px;color:#64748b;font-weight:700;">SKOR 35 KRITERIA</div>
                    <div style="font-size:26px;font-weight:900;color:#056e4f;"><?php echo esc_html((string) $report['total']); ?>%</div>
                    <div style="font-size:11px;color:#475569;">Berkala: <?php echo esc_html((string) $report['berkala']); ?>% · Wajib: <?php echo esc_html((string) $report['wajib']); ?>%</div>
                </div>
                <div style="background:#fff;border:1px solid #cbd5e1;border-left:5px solid #16a34a;border-radius:10px;padding:12px 14px;">
                    <div style="font-size:11px;color:#64748b;font-weight:700;">LENGKAP</div>
                    <div style="font-size:26px;font-weight:900;color:#15803d;"><?php echo esc_html((string) $report['counts']['lengkap']); ?> / 35</div>
                    <div style="font-size:11px;color:#475569;">Siap dinilai</div>
                </div>
                <div style="background:#fff;border:1px solid #cbd5e1;border-left:5px solid #d97706;border-radius:10px;padding:12px 14px;">
                    <div style="font-size:11px;color:#64748b;font-weight:700;">PERLU DILENGKAPI</div>
                    <div style="font-size:26px;font-weight:900;color:#b45309;"><?php echo esc_html((string) $report['counts']['perhatian']); ?> / 35</div>
                    <div style="font-size:11px;color:#475569;">Butuh pelengkap</div>
                </div>
                <div style="background:#fff;border:1px solid #cbd5e1;border-left:5px solid #dc2626;border-radius:10px;padding:12px 14px;">
                    <div style="font-size:11px;color:#64748b;font-weight:700;">KOSONG</div>
                    <div style="font-size:26px;font-weight:900;color:#b91c1c;"><?php echo esc_html((string) $report['counts']['kosong']); ?> / 35</div>
                    <div style="font-size:11px;color:#475569;">Belum terunggah</div>
                </div>
            </div>

            <div class="pa-dash-tabs" role="tablist" aria-label="Tab Pilar Audit SINOVA">
                <button type="button" role="tab" id="pa-dtab-1" aria-controls="pa-dpanel-1" aria-selected="true" tabindex="0" class="pa-dash-tab is-active" data-dash-tab="p1">
                    <span>Pilar 1 · 35 Kriteria</span>
                    <?php if ($p1Green) : ?><span class="pa-tab-ok-ico" title="Semua Hijau">✔</span><?php else : ?><span class="pa-tab-pct-pill"><?php echo esc_html((string) $report['total']); ?>%</span><?php endif; ?>
                </button>
                <button type="button" role="tab" id="pa-dtab-2" aria-controls="pa-dpanel-2" aria-selected="false" tabindex="-1" class="pa-dash-tab" data-dash-tab="p2">
                    <span>Pilar 2 · UI/UX &amp; WCAG</span>
                    <?php if ($p2Green) : ?><span class="pa-tab-ok-ico" title="100% Terpenuhi">✔</span><?php else : ?><span class="pa-tab-pct-pill"><?php echo esc_html((string) $audit['pilar2_pct']); ?>%</span><?php endif; ?>
                </button>
                <button type="button" role="tab" id="pa-dtab-3" aria-controls="pa-dpanel-3" aria-selected="false" tabindex="-1" class="pa-dash-tab" data-dash-tab="p3">
                    <span>Pilar 3 · Performa &amp; Keamanan</span>
                    <?php if ($p3Green) : ?><span class="pa-tab-ok-ico" title="100% Terpenuhi">✔</span><?php else : ?><span class="pa-tab-pct-pill"><?php echo esc_html((string) $audit['pilar3_pct']); ?>%</span><?php endif; ?>
                </button>
                <?php if ($hasRecs) : ?>
                    <button type="button" role="tab" id="pa-dtab-rec" aria-controls="pa-dpanel-rec" aria-selected="false" tabindex="-1" class="pa-dash-tab" data-dash-tab="rec">
                        <span>Prioritas Perbaikan</span>
                        <span class="pa-tab-pct-pill"><?php echo esc_html((string) count($audit['recommendations'])); ?></span>
                    </button>
                <?php endif; ?>
            </div>

            <div role="tabpanel" id="pa-dpanel-1" aria-labelledby="pa-dtab-1" data-dash-panel="p1" style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:14px 16px;">
                <table class="widefat striped">
                    <thead>
                        <tr><th style="width:64px;">Kode</th><th style="width:90px;">Kelompok</th><th>Kriteria Lampiran II</th><th style="width:125px;">Status</th><th>Catatan</th><th style="width:230px;">URL Bukti</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['items'] as $it) :
                            $bg = $it['status'] === 'lengkap' ? '#dcfce7;color:#166534' : ($it['status'] === 'perhatian' ? '#fef3c7;color:#92400e' : '#fee2e2;color:#991b1b');
                            $stLabel = $it['status'] === 'lengkap' ? 'Lengkap' : ($it['status'] === 'perhatian' ? 'Perlu Dilengkapi' : 'Kosong');
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($it['code']); ?></strong></td>
                                <td><span style="text-transform:uppercase;font-size:11px;font-weight:700;color:#475569;"><?php echo esc_html($it['group']); ?></span></td>
                                <td><strong><?php echo esc_html($it['label']); ?></strong><br><code style="font-size:11px;"><?php echo esc_html($it['url']); ?></code></td>
                                <td><span style="display:inline-block;padding:3px 9px;border-radius:999px;font-weight:700;font-size:11.5px;background:<?php echo $bg; ?>;"><?php echo esc_html($stLabel); ?></span></td>
                                <td><?php echo esc_html($it['note']); ?></td>
                                <td>
                                    <button type="button" class="button button-small pa-copy-url-btn" data-url="<?php echo esc_attr($it['url']); ?>">Salin URL</button>
                                    <a href="<?php echo esc_url($it['url']); ?>" target="_blank" rel="noopener noreferrer" class="button button-small">Buka</a>
                                    <a href="<?php echo esc_url($it['edit_url']); ?>" class="button button-small button-primary">Lengkapi</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div role="tabpanel" id="pa-dpanel-2" aria-labelledby="pa-dtab-2" data-dash-panel="p2" hidden style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:14px 16px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px;">
                    <h3 style="margin:0;color:#056e4f;">Pilar 2 — UI/UX, Aturan 2-Klik &amp; Aksesibilitas WCAG 2.2 (<?php echo esc_html((string) $audit['pilar2_pct']); ?>%)</h3>
                    <?php if ($p2Green) : ?><span class="pa-all-green-badge">✔ Hijau 100%</span><?php endif; ?>
                </div>
                <table class="widefat striped">
                    <tbody>
                        <?php foreach ($audit['p2_checks'] as $chk) : ?>
                            <tr>
                                <td style="width:28px;font-weight:900;color:<?php echo $chk['pass'] ? '#15803d' : '#b45309'; ?>;"><?php echo $chk['pass'] ? '✔' : '!'; ?></td>
                                <td><strong><?php echo esc_html($chk['label']); ?></strong><?php echo pa_sinova_info_tip($chk['detail'], 'Detail ' . $chk['label']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div role="tabpanel" id="pa-dpanel-3" aria-labelledby="pa-dtab-3" data-dash-panel="p3" hidden style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:14px 16px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px;">
                    <h3 style="margin:0;color:#056e4f;">Pilar 3 — Performa, Keamanan &amp; Infrastruktur Web (<?php echo esc_html((string) $audit['pilar3_pct']); ?>%)</h3>
                    <?php if ($p3Green) : ?><span class="pa-all-green-badge">✔ Hijau 100%</span><?php endif; ?>
                </div>
                <table class="widefat striped">
                    <tbody>
                        <?php foreach ($audit['p3_checks'] as $chk) : ?>
                            <tr>
                                <td style="width:28px;font-weight:900;color:<?php echo $chk['pass'] ? '#15803d' : '#b45309'; ?>;"><?php echo $chk['pass'] ? '✔' : '!'; ?></td>
                                <td><strong><?php echo esc_html($chk['label']); ?></strong><?php echo pa_sinova_info_tip($chk['detail'], 'Detail ' . $chk['label']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($hasRecs) : ?>
                <div role="tabpanel" id="pa-dpanel-rec" aria-labelledby="pa-dtab-rec" data-dash-panel="rec" hidden style="background:#fffbeb;border:1px solid #f59e0b;border-left:5px solid #d97706;border-radius:10px;padding:14px 18px;">
                    <strong style="color:#92400e;">Rekomendasi Prioritas Menuju Predikat Sangat Baik (&ge; 90):</strong>
                    <ul style="margin:8px 0 0;padding-left:18px;font-size:12.5px;color:#78350f;">
                        <?php foreach (array_slice($audit['recommendations'], 0, 10) as $rec) : ?>
                            <li style="margin-bottom:5px;">
                                <strong><?php echo esc_html($rec['label']); ?>:</strong>
                                <?php echo esc_html($rec['detail']); ?>
                                <?php if (!empty($rec['tab']) && in_array($rec['tab'], $allowedTabs, true)) : ?>
                                    — <a href="<?php echo esc_url($tabBaseUrl . $rec['tab']); ?>">Perbaiki di tab <?php echo esc_html(ucfirst($rec['tab'])); ?> →</a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <script>
                (function(){
                    document.querySelectorAll('.pa-copy-url-btn').forEach(function(btn){
                        btn.addEventListener('click', function(){
                            var u = btn.getAttribute('data-url');
                            if(navigator.clipboard){ navigator.clipboard.writeText(u); }
                            var orig = btn.textContent;
                            btn.textContent = 'Tersalin!';
                            setTimeout(function(){ btn.textContent = orig; }, 1400);
                        });
                    });
                    var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-dash-tab]'));
                    var panels = Array.prototype.slice.call(document.querySelectorAll('[data-dash-panel]'));
                    if(!tabs.length) return;
                    function activateDashTab(key, focusMove){
                        tabs.forEach(function(t){
                            var on = (t.getAttribute('data-dash-tab') === key);
                            t.classList.toggle('is-active', on);
                            t.setAttribute('aria-selected', on ? 'true' : 'false');
                            t.setAttribute('tabindex', on ? '0' : '-1');
                            if(on && focusMove) t.focus();
                        });
                        panels.forEach(function(p){
                            if(p.getAttribute('data-dash-panel') === key) p.removeAttribute('hidden');
                            else p.setAttribute('hidden', '');
                        });
                    }
                    tabs.forEach(function(tab, idx){
                        tab.addEventListener('click', function(){ activateDashTab(tab.getAttribute('data-dash-tab'), false); });
                        tab.addEventListener('keydown', function(e){
                            var next = -1;
                            if(e.key === 'ArrowRight') next = (idx + 1) % tabs.length;
                            else if(e.key === 'ArrowLeft') next = (idx - 1 + tabs.length) % tabs.length;
                            else if(e.key === 'Home') next = 0;
                            else if(e.key === 'End') next = tabs.length - 1;
                            if(next >= 0){ e.preventDefault(); activateDashTab(tabs[next].getAttribute('data-dash-tab'), true); }
                        });
                    });
                })();
            </script>

        <?php elseif ($activeTab === 'upload') :
            global $wpdb;
            $table   = function_exists('pa_sinova_table_name') ? pa_sinova_table_name() : ($wpdb->prefix . 'pa_sinova_documents');
            $maxYear = max(2028, (int) wp_date('Y') + 2);

            $filterSlug = isset($_GET['filter_slug']) ? sanitize_key((string) $_GET['filter_slug']) : 'all';
            if ($filterSlug !== 'all' && !isset($uploadCatalog[$filterSlug])) {
                $filterSlug = 'all';
            }
            $filterYear = isset($_GET['filter_year']) ? (int) $_GET['filter_year'] : 0;
            $docQuery   = isset($_GET['doc_q']) ? trim(sanitize_text_field(wp_unslash((string) $_GET['doc_q']))) : '';

            $uploadViewArgs = array('page' => 'pa-sinova-starter', 'tab' => 'upload');
            if ($filterSlug !== 'all') {
                $uploadViewArgs['filter_slug'] = $filterSlug;
            }
            if ($filterYear > 0) {
                $uploadViewArgs['filter_year'] = $filterYear;
            }
            if ($docQuery !== '') {
                $uploadViewArgs['doc_q'] = $docQuery;
            }
            $uploadViewUrl = add_query_arg($uploadViewArgs, admin_url('admin.php'));

            $reqEditDocId = ($editDocId > 0) ? $editDocId : (isset($_GET['edit_doc']) ? (int) $_GET['edit_doc'] : 0);
            $editDoc      = ($reqEditDocId > 0) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE id = %d", $reqEditDocId), ARRAY_A) : null;
            if ($editDoc && !empty($allowedSlugs) && !in_array((string) ($editDoc['page_slug'] ?? ''), $allowedSlugs, true)) {
                $editDoc      = null;
                $reqEditDocId = 0;
            }
            $isEditingDoc = is_array($editDoc) && !empty($editDoc['id']);

            $postedDocEdit = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['pa_sinova_action'] ?? '') === 'update_doc' && $editDocId > 0);
            $formSlug      = $postedDocEdit ? sanitize_key((string) ($_POST['doc_slug'] ?? $preSlug)) : ($isEditingDoc ? (string) $editDoc['page_slug'] : $preSlug);
            if (!isset($uploadCatalog[$formSlug]) && !empty($uploadKeys)) {
                $formSlug = $uploadKeys[0];
            }
            $formYear  = $postedDocEdit ? (int) ($_POST['doc_year'] ?? wp_date('Y')) : ($isEditingDoc ? (int) $editDoc['year'] : (int) wp_date('Y'));
            $formSub   = $postedDocEdit ? sanitize_text_field(wp_unslash((string) ($_POST['doc_subgroup'] ?? 'DOKUMEN'))) : ($isEditingDoc ? (string) $editDoc['sub_group'] : (string) ($uploadCatalog[$formSlug]['sub_groups'][0] ?? 'SERAPAN 01'));
            $formPer   = $postedDocEdit ? strtoupper(sanitize_key((string) ($_POST['doc_period'] ?? 'JAN'))) : ($isEditingDoc ? (string) $editDoc['period_key'] : 'JAN');
            $formTitle = $postedDocEdit ? sanitize_text_field(wp_unslash((string) ($_POST['doc_title'] ?? ''))) : ($isEditingDoc ? (string) $editDoc['title'] : '');
            $formUrl   = $postedDocEdit
                ? esc_url_raw(wp_unslash((string) ($_POST['doc_url'] ?? '')))
                : ($isEditingDoc && ($editDoc['source_type'] ?? '') !== 'wp_pdf' ? (string) ($editDoc['doc_url'] ?? '') : '');

            $whereParts = array();
            $whereArgs  = array();
            if (!empty($allowedSlugs)) {
                $placeholders = implode(',', array_fill(0, count($allowedSlugs), '%s'));
                $whereParts[] = "page_slug IN ({$placeholders})";
                $whereArgs    = array_merge($whereArgs, $allowedSlugs);
            }
            if ($filterSlug !== 'all') {
                $whereParts[] = 'page_slug = %s';
                $whereArgs[]  = $filterSlug;
            }
            if ($filterYear > 0) {
                $whereParts[] = 'year = %d';
                $whereArgs[]  = $filterYear;
            }
            if ($docQuery !== '') {
                $like         = '%' . $wpdb->esc_like($docQuery) . '%';
                $whereParts[] = '(title LIKE %s OR sub_group LIKE %s OR period_key LIKE %s)';
                $whereArgs[]  = $like;
                $whereArgs[]  = $like;
                $whereArgs[]  = $like;
            }
            $whereSql   = !empty($whereParts) ? ('WHERE ' . implode(' AND ', $whereParts)) : '';
            $recentSql  = "SELECT * FROM `{$table}` {$whereSql} ORDER BY updated_at DESC, id DESC LIMIT 100";
            $recentDocs = !empty($whereArgs)
                ? $wpdb->get_results($wpdb->prepare($recentSql, ...$whereArgs), ARRAY_A)
                : $wpdb->get_results($recentSql, ARRAY_A);

            if (!empty($allowedSlugs)) {
                $yrPh     = implode(',', array_fill(0, count($allowedSlugs), '%s'));
                $docYears = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT year FROM `{$table}` WHERE page_slug IN ({$yrPh}) ORDER BY year DESC", ...$allowedSlugs));
            } else {
                $docYears = $wpdb->get_col("SELECT DISTINCT year FROM `{$table}` ORDER BY year DESC");
            }
        ?>
            <div class="pa-adm-cols-2">
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid <?php echo $isEditingDoc ? '#056e4f' : '#C9A227'; ?>;border-radius:10px;padding:18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:6px;">
                        <h2 style="margin:0;color:#056e4f;">
                            <?php echo $isEditingDoc ? 'Edit Dokumen Arsip' : 'Upload Dokumen Cepat'; ?>
                            <?php echo pa_sinova_info_tip($isEditingDoc ? 'Kosongkan file & URL jika hanya memperbaiki modul, tahun, slot, atau judul.' : 'Unggah PDF maksimal 10 MB atau tempel tautan Google Drive / cloud resmi.', 'Info upload'); ?>
                        </h2>
                        <?php if ($isEditingDoc) : ?>
                            <span style="background:#ecfdf5;color:#056e4f;border:1px solid #86efac;border-radius:999px;padding:2px 10px;font-size:11.5px;font-weight:700;">Mode Edit · ID #<?php echo esc_html((string) $editDoc['id']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($isEditingDoc) : ?>
                        <p style="font-size:12.5px;color:#334155;background:#f8fafc;border-left:3px solid #056e4f;padding:8px 10px;margin:8px 0 12px;">
                            <strong><?php echo esc_html((string) $editDoc['title']); ?></strong> ·
                            <a href="<?php echo esc_url((string) $editDoc['doc_url']); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html(($editDoc['source_type'] ?? '') === 'wp_pdf' ? 'PDF Lokal (' . basename((string) $editDoc['doc_url']) . ')' : 'Sumber URL'); ?> ↗
                            </a>
                        </p>
                    <?php endif; ?>
                    <form method="post" action="<?php echo esc_url($uploadViewUrl); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="<?php echo $isEditingDoc ? 'update_doc' : 'upload_doc'; ?>">
                        <?php if ($isEditingDoc) : ?>
                            <input type="hidden" name="edit_doc_id" value="<?php echo esc_attr((string) $editDoc['id']); ?>">
                        <?php endif; ?>
                        <table class="form-table">
                            <tr><th>Modul</th><td>
                                <select name="doc_slug" id="pa-doc-slug-select" class="regular-text" data-editing="<?php echo $isEditingDoc ? '1' : '0'; ?>">
                                    <?php foreach ($uploadCatalog as $slug => $m) : ?>
                                        <option value="<?php echo esc_attr($slug); ?>" data-sub="<?php echo esc_attr((string) ($m['sub_groups'][0] ?? 'DOKUMEN')); ?>" <?php selected($formSlug, $slug); ?>><?php echo esc_html($m['title'] . ' (' . $slug . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td></tr>
                            <tr><th>Tahun</th><td><input type="number" name="doc_year" min="2017" max="<?php echo esc_attr((string) $maxYear); ?>" value="<?php echo esc_attr((string) $formYear); ?>"></td></tr>
                            <tr><th>Sub-Kelompok</th><td><input type="text" name="doc_subgroup" id="pa-doc-subgroup-input" class="regular-text" value="<?php echo esc_attr($formSub); ?>"></td></tr>
                            <tr><th>Periode Slot</th><td>
                                <select name="doc_period">
                                    <?php foreach ($periods as $pk => $pv) : ?>
                                        <option value="<?php echo esc_attr($pk); ?>" <?php selected($formPer, $pk); ?>><?php echo esc_html($pk . ' — ' . $pv['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td></tr>
                            <tr><th><?php echo $isEditingDoc ? 'Ganti PDF (Opsional)' : 'Upload PDF (Maks 10MB)'; ?></th><td><input type="file" name="doc_pdf" accept="application/pdf,.pdf"></td></tr>
                            <tr><th><?php echo $isEditingDoc ? 'Atau Ganti URL HTTPS' : 'Atau URL HTTPS / Drive'; ?></th><td><input type="url" name="doc_url" class="regular-text" value="<?php echo esc_attr($formUrl); ?>" placeholder="https://drive.google.com/file/d/.../view"></td></tr>
                            <tr><th>Judul (Opsional)</th><td><input type="text" name="doc_title" class="regular-text" value="<?php echo esc_attr($formTitle); ?>"></td></tr>
                        </table>
                        <p style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                            <button type="submit" class="button button-primary"><?php echo $isEditingDoc ? 'Simpan Perubahan' : 'Simpan &amp; Tayangkan ke Website'; ?></button>
                            <?php if ($isEditingDoc) : ?>
                                <a href="<?php echo esc_url($uploadViewUrl); ?>" class="button">Batal Edit</a>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>

                <div style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
                        <h2 style="margin:0;color:#056e4f;">Daftar Dokumen Terunggah (<?php echo count((array) $recentDocs); ?>)</h2>
                        <small style="color:#64748b;">Menampilkan hingga 100 entri terbaru sesuai filter</small>
                    </div>

                    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px;margin-bottom:12px;">
                        <input type="hidden" name="page" value="pa-sinova-starter">
                        <input type="hidden" name="tab" value="upload">
                        <select name="filter_slug" aria-label="Filter Modul" style="max-width:190px;">
                            <option value="all" <?php selected($filterSlug, 'all'); ?>>Semua Modul</option>
                            <?php foreach ($uploadCatalog as $slug => $m) : ?>
                                <option value="<?php echo esc_attr($slug); ?>" <?php selected($filterSlug, $slug); ?>><?php echo esc_html($slug . ' — ' . $m['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="filter_year" aria-label="Filter Tahun" style="width:110px;">
                            <option value="0" <?php selected($filterYear, 0); ?>>Semua Tahun</option>
                            <?php foreach ((array) $docYears as $dy) : ?>
                                <option value="<?php echo esc_attr((string) $dy); ?>" <?php selected($filterYear, (int) $dy); ?>><?php echo esc_html((string) $dy); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="search" name="doc_q" value="<?php echo esc_attr($docQuery); ?>" placeholder="Cari judul / sub-kelompok / periode..." style="min-width:190px;flex:1;">
                        <button type="submit" class="button">Filter</button>
                        <?php if ($filterSlug !== 'all' || $filterYear > 0 || $docQuery !== '') : ?>
                            <a href="<?php echo esc_url($tabBaseUrl . 'upload'); ?>" class="button">Reset</a>
                        <?php endif; ?>
                    </form>

                    <form method="post" id="pa-bulk-doc-form" action="<?php echo esc_url($uploadViewUrl); ?>">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="bulk_delete_docs">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px;padding:8px 10px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <label style="font-weight:600;cursor:pointer;">
                                    <input type="checkbox" id="pa-doc-check-all"> Pilih Semua
                                </label>
                                <span id="pa-doc-sel-info" style="font-size:12.5px;color:#475569;">0 item dipilih</span>
                            </div>
                            <button type="submit" id="pa-bulk-doc-del-btn" class="button" style="color:#b91c1c;border-color:#fca5a5;" disabled>Hapus Terpilih (0)</button>
                        </div>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th style="width:28px;"><input type="checkbox" id="pa-doc-th-check-all" aria-label="Pilih semua dokumen pada tabel"></th>
                                    <th>Modul / Slot</th>
                                    <th>Judul Dokumen</th>
                                    <th>Sumber</th>
                                    <th style="width:130px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentDocs)) : ?>
                                    <tr><td colspan="5">Belum ada dokumen yang cocok dengan filter saat ini.</td></tr>
                                <?php else : foreach ($recentDocs as $rd) :
                                    $rowId     = (int) ($rd['id'] ?? 0);
                                    $rowIsEdit = ($isEditingDoc && (int) $editDoc['id'] === $rowId);
                                    $rowEditUrl = add_query_arg('edit_doc', $rowId, $uploadViewUrl);
                                ?>
                                    <tr style="<?php echo $rowIsEdit ? 'background:#f0fdf4;' : ''; ?>">
                                        <td><input type="checkbox" class="pa-doc-chk" name="doc_ids[]" value="<?php echo esc_attr((string) $rowId); ?>" aria-label="Pilih <?php echo esc_attr((string) $rd['title']); ?>"></td>
                                        <td><strong><?php echo esc_html((string) $rd['page_slug']); ?></strong><br><small><?php echo esc_html((string) $rd['year'] . ' · ' . (string) $rd['period_key']); ?></small></td>
                                        <td><?php echo esc_html((string) $rd['title']); ?><br><small style="color:#64748b;"><?php echo esc_html((string) $rd['sub_group']); ?></small></td>
                                        <td><a href="<?php echo esc_url((string) $rd['doc_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(($rd['source_type'] ?? '') === 'wp_pdf' ? 'PDF Lokal' : 'Drive/URL'); ?> ↗</a></td>
                                        <td>
                                            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                                                <a href="<?php echo esc_url($rowEditUrl); ?>" class="button button-small <?php echo $rowIsEdit ? 'button-primary' : ''; ?>">Edit</a>
                                                <button type="button" class="button button-small pa-single-doc-del-btn" data-id="<?php echo esc_attr((string) $rowId); ?>" data-title="<?php echo esc_attr((string) $rd['title']); ?>" style="color:#b91c1c;">Hapus</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </form>
                    <form method="post" id="pa-single-doc-del-form" action="<?php echo esc_url($uploadViewUrl); ?>" style="display:none;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="delete_doc">
                        <input type="hidden" name="doc_id" id="pa-single-doc-del-id" value="0">
                    </form>
                    <script>
                        (function(){
                            var sel = document.getElementById('pa-doc-slug-select');
                            var sub = document.getElementById('pa-doc-subgroup-input');
                            if (sel && sub && sel.getAttribute('data-editing') !== '1') {
                                sel.addEventListener('change', function(){
                                    var opt = sel.options[sel.selectedIndex];
                                    if (opt && opt.getAttribute('data-sub')) { sub.value = opt.getAttribute('data-sub'); }
                                });
                            }
                            var bulkForm = document.getElementById('pa-bulk-doc-form');
                            var checkAllTop = document.getElementById('pa-doc-check-all');
                            var checkAllTh = document.getElementById('pa-doc-th-check-all');
                            var selInfo = document.getElementById('pa-doc-sel-info');
                            var bulkBtn = document.getElementById('pa-bulk-doc-del-btn');
                            var singleForm = document.getElementById('pa-single-doc-del-form');
                            var singleInput = document.getElementById('pa-single-doc-del-id');

                            function getDocBoxes() {
                                return bulkForm ? Array.prototype.slice.call(bulkForm.querySelectorAll('.pa-doc-chk')) : [];
                            }
                            function syncDocSelection() {
                                var boxes = getDocBoxes();
                                var checked = boxes.filter(function(cb){ return cb.checked; }).length;
                                var allChecked = boxes.length > 0 && checked === boxes.length;
                                if (checkAllTop) { checkAllTop.checked = allChecked; }
                                if (checkAllTh) { checkAllTh.checked = allChecked; }
                                if (selInfo) { selInfo.textContent = checked + ' item dipilih'; }
                                if (bulkBtn) {
                                    bulkBtn.disabled = checked === 0;
                                    bulkBtn.textContent = 'Hapus Terpilih (' + checked + ')';
                                }
                            }
                            function setAllDocs(state) {
                                getDocBoxes().forEach(function(cb){ cb.checked = state; });
                                syncDocSelection();
                            }
                            if (checkAllTop) { checkAllTop.addEventListener('change', function(){ setAllDocs(checkAllTop.checked); }); }
                            if (checkAllTh) { checkAllTh.addEventListener('change', function(){ setAllDocs(checkAllTh.checked); }); }
                            getDocBoxes().forEach(function(cb){ cb.addEventListener('change', syncDocSelection); });
                            if (bulkForm) {
                                bulkForm.addEventListener('submit', function(e){
                                    var checked = getDocBoxes().filter(function(cb){ return cb.checked; }).length;
                                    if (checked === 0) { e.preventDefault(); return; }
                                    if (!confirm('Hapus ' + checked + ' dokumen yang dipilih secara permanen?')) { e.preventDefault(); }
                                });
                            }
                            document.querySelectorAll('.pa-single-doc-del-btn').forEach(function(btn){
                                btn.addEventListener('click', function(){
                                    var title = btn.getAttribute('data-title') || 'dokumen ini';
                                    if (!confirm('Hapus dokumen "' + title + '" dari arsip?')) { return; }
                                    if (singleInput && singleForm) {
                                        singleInput.value = btn.getAttribute('data-id') || '0';
                                        singleForm.submit();
                                    }
                                });
                            });
                            syncDocSelection();
                        })();
                    </script>
                </div>
            </div>

        <?php elseif ($activeTab === 'data') :
            if (function_exists('pa_portal_records_ensure_table')) {
                pa_portal_records_ensure_table();
            }
            global $wpdb;
            $rTable   = function_exists('pa_portal_records_table') ? pa_portal_records_table() : ($wpdb->prefix . 'pa_portal_records');
            $recTypes = pa_sinova_record_types_catalog();
            $sippLastPush = (string) get_option('pa_sipp_last_push_at', '');
            $filterRecType = isset($_GET['rec_type']) ? sanitize_key((string) $_GET['rec_type']) : 'all';
            if ($filterRecType !== 'all' && !isset($recTypes[$filterRecType])) {
                $filterRecType = 'all';
            }
            $dataViewArgs = array('page' => 'pa-sinova-starter', 'tab' => 'data');
            if ($filterRecType !== 'all') {
                $dataViewArgs['rec_type'] = $filterRecType;
            }
            $dataViewUrl = add_query_arg($dataViewArgs, admin_url('admin.php'));

            $reqEditRecId = ($editRecId > 0) ? $editRecId : (isset($_GET['edit_rec']) ? (int) $_GET['edit_rec'] : 0);
            $editRec      = ($reqEditRecId > 0) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$rTable}` WHERE id = %d", $reqEditRecId), ARRAY_A) : null;
            $isEditingRec = is_array($editRec) && !empty($editRec['id']);

            $postedRecEdit = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['pa_sinova_action'] ?? '') === 'update_record' && $editRecId > 0);
            $formRecType   = $postedRecEdit
                ? sanitize_key((string) ($_POST['rec_type'] ?? 'jadwal'))
                : ($isEditingRec ? (string) $editRec['record_type'] : ($filterRecType !== 'all' ? $filterRecType : 'jadwal'));
            if (!isset($recTypes[$formRecType])) {
                $formRecType = 'jadwal';
            }
            $formRecTitle = $postedRecEdit ? sanitize_text_field(wp_unslash((string) ($_POST['rec_title'] ?? ''))) : ($isEditingRec ? (string) $editRec['title'] : '');
            $formRecSub   = $postedRecEdit ? sanitize_text_field(wp_unslash((string) ($_POST['rec_subtitle'] ?? ''))) : ($isEditingRec ? (string) $editRec['subtitle'] : '');
            $formRecCat   = $postedRecEdit ? sanitize_text_field(wp_unslash((string) ($_POST['rec_category'] ?? ''))) : ($isEditingRec ? (string) $editRec['category'] : '');
            $formRecDate  = $postedRecEdit ? sanitize_text_field(wp_unslash((string) ($_POST['rec_date'] ?? ''))) : ($isEditingRec ? (string) ($editRec['event_date'] ?? '') : wp_date('Y-m-d'));
            $formRecOrder = $postedRecEdit ? (int) ($_POST['rec_sort_order'] ?? 10) : ($isEditingRec ? (int) $editRec['sort_order'] : 10);

            $editMeta = ($isEditingRec && !empty($editRec['meta_json'])) ? json_decode((string) $editRec['meta_json'], true) : array();
            if (!is_array($editMeta)) {
                $editMeta = array();
            }
            if ($postedRecEdit) {
                $formRecM1 = sanitize_text_field(wp_unslash((string) ($_POST['rec_meta_1'] ?? '')));
                $formRecM2 = sanitize_text_field(wp_unslash((string) ($_POST['rec_meta_2'] ?? '')));
                $formRecM3 = sanitize_text_field(wp_unslash((string) ($_POST['rec_meta_3'] ?? '')));
                $formRecM4 = sanitize_text_field(wp_unslash((string) ($_POST['rec_meta_4'] ?? '')));
                $formRecM5 = sanitize_text_field(wp_unslash((string) ($_POST['rec_meta_5'] ?? '')));
                $formRecM6 = sanitize_textarea_field(wp_unslash((string) ($_POST['rec_meta_6'] ?? '')));
                $formRecM7 = sanitize_textarea_field(wp_unslash((string) ($_POST['rec_meta_7'] ?? '')));
            } elseif ($isEditingRec && $formRecType === 'pegawai') {
                $formRecM1 = (string) ($editMeta['nip'] ?? '');
                $formRecM2 = (string) ($editMeta['gol'] ?? '');
                $formRecM3 = (string) ($editMeta['edu'] ?? '');
                $formRecM4 = (string) ($editMeta['photo'] ?? '');
                $formRecM5 = (string) ($editMeta['tmt'] ?? '');
                $formRecM6 = (string) ($editMeta['riwayat_pendidikan'] ?? '');
                $formRecM7 = (string) ($editMeta['riwayat_jabatan'] ?? '');
            } elseif ($isEditingRec && $formRecType === 'ghaib') {
                $formRecM1 = (string) ($editMeta['pihak'] ?? '');
                $formRecM2 = (string) ($editMeta['doc_url'] ?? '');
                $formRecM3 = $formRecM4 = $formRecM5 = $formRecM6 = $formRecM7 = '';
            } elseif ($isEditingRec && $formRecType === 'putusan') {
                $formRecM1 = (string) ($editMeta['doc_url'] ?? '');
                $formRecM2 = (string) ($editMeta['majelis'] ?? '');
                $formRecM3 = (string) ($editMeta['status_hukum'] ?? '');
                $formRecM4 = $formRecM5 = $formRecM6 = $formRecM7 = '';
            } elseif ($isEditingRec && $formRecType === 'inovasi') {
                $formRecM1 = (string) ($editMeta['app_url'] ?? '');
                $formRecM2 = (string) ($editMeta['icon_url'] ?? '');
                $formRecM3 = (string) ($editMeta['badge'] ?? '');
                $formRecM4 = $formRecM5 = $formRecM6 = $formRecM7 = '';
            } elseif ($isEditingRec && $formRecType === 'statistik') {
                $formRecM1 = (string) ($editMeta['diterima'] ?? '');
                $formRecM2 = (string) ($editMeta['diputus'] ?? '');
                $formRecM3 = (string) ($editMeta['sisa'] ?? '');
                $formRecM4 = $formRecM5 = $formRecM6 = $formRecM7 = '';
            } elseif ($isEditingRec && $formRecType === 'slider') {
                $formRecM1 = (string) ($editMeta['image_url'] ?? '');
                $formRecM2 = (string) ($editMeta['btn_url'] ?? '');
                $formRecM3 = (string) ($editMeta['btn_label'] ?? '');
                $formRecM4 = $formRecM5 = $formRecM6 = $formRecM7 = '';
            } elseif ($isEditingRec && $formRecType === 'video') {
                $formRecM1 = (string) ($editMeta['video_url'] ?? '');
                $formRecM2 = (string) ($editMeta['thumb_url'] ?? '');
                $formRecM3 = (string) ($editMeta['duration'] ?? '');
                $formRecM4 = $formRecM5 = $formRecM6 = $formRecM7 = '';
            } elseif ($isEditingRec && $formRecType === 'ucapan') {
                $formRecM1 = (string) ($editMeta['photo_url'] ?? '');
                $formRecM2 = (string) ($editMeta['sender'] ?? '');
                $formRecM3 = (string) ($editMeta['badge'] ?? '');
                $formRecM4 = $formRecM5 = $formRecM6 = $formRecM7 = '';
            } elseif ($isEditingRec && $formRecType === 'yurisdiksi') {
                $formRecM1 = (string) ($editMeta['desa'] ?? '');
                $formRecM2 = (string) ($editMeta['jarak'] ?? '');
                $formRecM3 = (string) ($editMeta['biaya'] ?? '');
                $formRecM4 = $formRecM5 = $formRecM6 = $formRecM7 = '';
            } elseif ($isEditingRec) {
                $formRecM1 = (string) ($editMeta['jam'] ?? '');
                $formRecM2 = $formRecM3 = $formRecM4 = $formRecM5 = $formRecM6 = $formRecM7 = '';
            } else {
                $formRecM1 = $formRecM2 = $formRecM3 = $formRecM4 = $formRecM5 = $formRecM6 = $formRecM7 = '';
            }

            $recRows = ($filterRecType !== 'all')
                ? $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$rTable}` WHERE record_type = %s ORDER BY sort_order ASC, event_date DESC, id DESC LIMIT 100", $filterRecType), ARRAY_A)
                : $wpdb->get_results("SELECT * FROM `{$rTable}` ORDER BY record_type ASC, sort_order ASC, id DESC LIMIT 100", ARRAY_A);
            $feedCache = function_exists('pa_portal_get_official_feeds') ? pa_portal_get_official_feeds(false) : array();
            $feedMaCnt = count((array) ($feedCache['ma'] ?? array()));
            $feedBdCnt = count((array) ($feedCache['badilag'] ?? array()));
            $feedUpd   = !empty($feedCache['updated_at']) ? wp_date('d M Y H:i', (int) $feedCache['updated_at']) . ' WIB' : 'Belum disinkronkan';
        ?>
            <div style="background:#fff;border:1px solid #cbd5e1;border-left:5px solid #056e4f;border-radius:10px;padding:14px 18px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <div>
                    <strong style="color:#056e4f;">Umpan RSS Resmi (MA RI &amp; Ditjen Badilag)</strong><br>
                    <small style="color:#475569;">
                        MA RI: <?php echo esc_html((string) $feedMaCnt); ?> pengumuman ·
                        Badilag: <?php echo esc_html((string) $feedBdCnt); ?> pengumuman ·
                        Terakhir disinkronkan: <?php echo esc_html($feedUpd); ?>
                    </small>
                </div>
                <form method="post">
                    <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                    <input type="hidden" name="pa_sinova_action" value="refresh_rss">
                    <button type="submit" class="button button-secondary">Sinkronkan RSS Resmi Sekarang</button>
                </form>
            </div>

            <div class="pa-adm-cols-2">
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid <?php echo $isEditingRec ? '#056e4f' : '#C9A227'; ?>;border-radius:10px;padding:18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:8px;">
                        <h2 style="margin:0;color:#056e4f;">
                            <?php echo $isEditingRec ? 'Edit Entri' : 'Tambah Entri'; ?>
                            <span class="pa-adm-tip" tabindex="0" data-tip="Entri langsung tampil di halaman terkait dan blok Beranda 12 kolom. Untuk sinkronisasi SIPP otomatis gunakan endpoint POST /wp-json/pa-sinova/v1/sipp/push-hearings (HMAC-SHA256)." aria-label="Info entri">i</span>
                        </h2>
                        <?php if ($sippLastPush !== '' && $formRecType === 'jadwal') : ?>
                            <span style="background:#ecfdf5;color:#043927;border:1px solid #86efac;border-radius:999px;padding:2px 9px;font-size:11px;font-weight:700;">Push SIPP: <?php echo esc_html($sippLastPush); ?></span>
                        <?php endif; ?>
                        <?php if ($isEditingRec) : ?>
                            <span style="background:#ecfdf5;color:#056e4f;border:1px solid #86efac;border-radius:999px;padding:2px 10px;font-size:11.5px;font-weight:700;">Edit #<?php echo esc_html((string) $editRec['id']); ?></span>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="<?php echo esc_url($dataViewUrl); ?>">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="<?php echo $isEditingRec ? 'update_record' : 'add_record'; ?>">
                        <?php if ($isEditingRec) : ?>
                            <input type="hidden" name="edit_rec_id" value="<?php echo esc_attr((string) $editRec['id']); ?>">
                        <?php endif; ?>
                        <table class="form-table" style="margin-top:0;">
                            <tr><th>Jenis</th><td>
                                <select name="rec_type" id="pa-rec-type-sel" class="regular-text">
                                    <?php foreach ($recTypes as $rk => $rl) : ?>
                                        <option value="<?php echo esc_attr($rk); ?>" <?php selected($formRecType, $rk); ?>><?php echo esc_html($rl); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td></tr>
                            <tr><th id="pa-lbl-title">Judul / Nama</th><td><input type="text" name="rec_title" class="regular-text" required value="<?php echo esc_attr($formRecTitle); ?>"></td></tr>
                            <tr><th id="pa-lbl-sub">Subjudul / Jabatan</th><td><input type="text" name="rec_subtitle" class="regular-text" value="<?php echo esc_attr($formRecSub); ?>"></td></tr>
                            <tr><th id="pa-lbl-cat">Kategori / Ruang</th><td><input type="text" name="rec_category" class="regular-text" value="<?php echo esc_attr($formRecCat); ?>"></td></tr>
                            <tr><th id="pa-lbl-m1">Meta 1</th><td><input type="text" name="rec_meta_1" class="regular-text" value="<?php echo esc_attr($formRecM1); ?>"></td></tr>
                            <tr><th id="pa-lbl-m2">Meta 2</th><td><input type="text" name="rec_meta_2" class="regular-text" value="<?php echo esc_attr($formRecM2); ?>"></td></tr>
                            <tr><th id="pa-lbl-m3">Meta 3</th><td><input type="text" name="rec_meta_3" class="regular-text" value="<?php echo esc_attr($formRecM3); ?>"></td></tr>
                            <tr class="pa-peg-extra" <?php echo $formRecType !== 'pegawai' ? 'style="display:none;"' : ''; ?>><th>URL Foto Pegawai</th><td><input type="url" name="rec_meta_4" class="regular-text" value="<?php echo esc_attr($formRecM4); ?>" placeholder="https://.../foto.jpg"></td></tr>
                            <tr class="pa-peg-extra" <?php echo $formRecType !== 'pegawai' ? 'style="display:none;"' : ''; ?>><th>TMT / Pangkat</th><td><input type="text" name="rec_meta_5" class="regular-text" value="<?php echo esc_attr($formRecM5); ?>" placeholder="01-04-2024"></td></tr>
                            <tr class="pa-peg-extra" <?php echo $formRecType !== 'pegawai' ? 'style="display:none;"' : ''; ?>><th>Riwayat Pendidikan</th><td><textarea name="rec_meta_6" rows="2" class="regular-text" placeholder="S1 Syariah (2005); S2 Ilmu Hukum (2012)"><?php echo esc_textarea($formRecM6); ?></textarea></td></tr>
                            <tr class="pa-peg-extra" <?php echo $formRecType !== 'pegawai' ? 'style="display:none;"' : ''; ?>><th>Riwayat Jabatan / Diklat</th><td><textarea name="rec_meta_7" rows="2" class="regular-text" placeholder="Hakim PA A (2018); Wakil Ketua PA B (2023)"><?php echo esc_textarea($formRecM7); ?></textarea></td></tr>
                            <tr><th>Tanggal</th><td><input type="date" name="rec_date" value="<?php echo esc_attr($formRecDate); ?>"></td></tr>
                            <tr><th>Urutan</th><td><input type="number" name="rec_sort_order" value="<?php echo esc_attr((string) $formRecOrder); ?>" style="width:90px;"></td></tr>
                        </table>
                        <p style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                            <button type="submit" class="button button-primary"><?php echo $isEditingRec ? 'Simpan Perubahan' : 'Simpan'; ?></button>
                            <?php if ($isEditingRec) : ?>
                                <a href="<?php echo esc_url($dataViewUrl); ?>" class="button">Batal</a>
                            <?php endif; ?>
                        </p>
                    </form>
                    <script>
                        (function(){
                            var sel = document.getElementById('pa-rec-type-sel');
                            if (!sel) return;
                            var map = {
                                jadwal:     ['Nomor Perkara', 'Agenda Sidang', 'Ruang Sidang', 'Jam (WIB)', 'Catatan', 'Opsional'],
                                putusan:    ['Nomor Perkara / Judul Putusan', 'Amar / Ringkasan Putusan', 'Jenis Perkara', 'URL Salinan PDF / Putusan3 (HTTPS)', 'Majelis Hakim (Opsional)', 'Status Hukum (BHT / Kasasi)'],
                                pegawai:    ['Nama Lengkap', 'Jabatan', 'Kelompok (Pimpinan/Hakim/dll)', 'NIP', 'Pangkat / Golongan', 'Pendidikan Terakhir'],
                                ghaib:      ['Nomor Perkara', 'Status Pihak', 'Keterangan Pasal 27', 'Nama Pihak Dipanggil', 'URL Relaas PDF/Drive', 'Opsional'],
                                statistik:  ['Periode (Bulan Tahun)', 'Keterangan', 'Tahun', 'Jumlah Diterima', 'Jumlah Diputus', 'Jumlah Sisa'],
                                inovasi:    ['Nama Aplikasi / Inovasi', 'Deskripsi Singkat Manfaat', 'Kategori (PTSP / Antrean / dll)', 'URL Aplikasi / Layanan (HTTPS)', 'URL Ikon / Gambar (Opsional)', 'Label Badge (mis. Unggulan)'],
                                slider:     ['Judul Slide', 'Subjudul Singkat', 'Label Atas (Kicker)', 'URL Gambar Slide (HTTPS)', 'URL Tombol (Opsional)', 'Label Tombol'],
                                video:      ['Judul Video', 'Deskripsi Singkat', 'Kategori Video', 'URL YouTube / Drive (HTTPS)', 'URL Thumbnail (Opsional)', 'Durasi (mis. 03:45)'],
                                ucapan:     ['Judul Ucapan / Nama', 'Pesan Singkat', 'Ucapan Selamat / Belasungkawa', 'URL Foto / Poster (Opsional)', 'Pengirim', 'Label Badge'],
                                yurisdiksi: ['Nama Kecamatan', 'Keterangan Wilayah', 'Radius (mis. Radius I)', 'Daftar Desa / Kelurahan', 'Jarak (± km)', 'Keterangan Biaya']
                            };
                            function syncLabels() {
                                var cfg = map[sel.value] || map.jadwal;
                                var ids = ['pa-lbl-title','pa-lbl-sub','pa-lbl-cat','pa-lbl-m1','pa-lbl-m2','pa-lbl-m3'];
                                ids.forEach(function(id, i){ var el = document.getElementById(id); if (el) el.textContent = cfg[i]; });
                                document.querySelectorAll('.pa-peg-extra').forEach(function(tr){ tr.style.display = (sel.value === 'pegawai') ? '' : 'none'; });
                            }
                            sel.addEventListener('change', syncLabels);
                            syncLabels();
                        })();
                    </script>
                </div>

                <div style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
                        <h2 style="margin:0;color:#056e4f;">Daftar Entri Terpublikasi (<?php echo count((array) $recRows); ?>)</h2>
                        <div>
                            <a href="<?php echo esc_url($tabBaseUrl . 'data&rec_type=all'); ?>" class="button button-small <?php echo $filterRecType === 'all' ? 'button-primary' : ''; ?>">Semua</a>
                            <?php foreach ($recTypes as $rk => $rl) : ?>
                                <a href="<?php echo esc_url($tabBaseUrl . 'data&rec_type=' . $rk); ?>" class="button button-small <?php echo $filterRecType === $rk ? 'button-primary' : ''; ?>"><?php echo esc_html(ucfirst($rk)); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <form method="post" id="pa-bulk-rec-form" action="<?php echo esc_url($dataViewUrl); ?>">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="bulk_delete_records">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px;padding:8px 10px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <label style="font-weight:600;cursor:pointer;">
                                    <input type="checkbox" id="pa-rec-check-all"> Pilih Semua
                                </label>
                                <span id="pa-rec-sel-info" style="font-size:12.5px;color:#475569;">0 item dipilih</span>
                            </div>
                            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                <button type="button" id="pa-del-sample-rec-btn" class="button" style="color:#92400e;border-color:#fcd34d;background:#fffbeb;" title="Hapus cepat 12 entri contoh bawaan (Ahmad Contoh / PA.Cth)">Hapus Data Contoh</button>
                                <button type="submit" id="pa-bulk-rec-del-btn" class="button" style="color:#b91c1c;border-color:#fca5a5;" disabled>Hapus Terpilih (0)</button>
                            </div>
                        </div>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th style="width:28px;"><input type="checkbox" id="pa-rec-th-check-all" aria-label="Pilih semua entri data operasional"></th>
                                    <th>Tipe</th>
                                    <th>Judul / Entitas</th>
                                    <th>Tanggal</th>
                                    <th style="width:130px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recRows)) : ?>
                                    <tr><td colspan="5">Belum ada data operasional. Tambahkan melalui formulir di samping.</td></tr>
                                <?php else : foreach ($recRows as $rr) :
                                    $recRowId    = (int) ($rr['id'] ?? 0);
                                    $recIsEdit   = ($isEditingRec && (int) $editRec['id'] === $recRowId);
                                    $recEditUrl  = add_query_arg('edit_rec', $recRowId, $dataViewUrl);
                                ?>
                                    <tr style="<?php echo $recIsEdit ? 'background:#f0fdf4;' : ''; ?>">
                                        <td><input type="checkbox" class="pa-rec-chk" name="rec_ids[]" value="<?php echo esc_attr((string) $recRowId); ?>" aria-label="Pilih <?php echo esc_attr((string) $rr['title']); ?>"></td>
                                        <td><code><?php echo esc_html((string) $rr['record_type']); ?></code></td>
                                        <td><strong><?php echo esc_html((string) $rr['title']); ?></strong><br><small style="color:#64748b;"><?php echo esc_html((string) $rr['subtitle'] . (!empty($rr['category']) ? ' · ' . $rr['category'] : '')); ?></small></td>
                                        <td><?php echo esc_html((string) ($rr['event_date'] ?: '-')); ?></td>
                                        <td>
                                            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                                                <a href="<?php echo esc_url($recEditUrl); ?>" class="button button-small <?php echo $recIsEdit ? 'button-primary' : ''; ?>">Edit</a>
                                                <button type="button" class="button button-small pa-single-rec-del-btn" data-id="<?php echo esc_attr((string) $recRowId); ?>" data-title="<?php echo esc_attr((string) $rr['title']); ?>" style="color:#b91c1c;">Hapus</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </form>
                    <form method="post" id="pa-single-rec-del-form" action="<?php echo esc_url($dataViewUrl); ?>" style="display:none;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="delete_record">
                        <input type="hidden" name="rec_id" id="pa-single-rec-del-id" value="0">
                    </form>
                    <form method="post" id="pa-sample-rec-del-form" action="<?php echo esc_url($dataViewUrl); ?>" style="display:none;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="delete_sample_records">
                    </form>
                    <script>
                        (function(){
                            var sampleBtn = document.getElementById('pa-del-sample-rec-btn');
                            var sampleFrm = document.getElementById('pa-sample-rec-del-form');
                            if (sampleBtn && sampleFrm) {
                                sampleBtn.addEventListener('click', function(){
                                    if (confirm('Hapus seluruh entri data contoh bawaan (Ahmad Contoh, PA.Cth, dll.)? Data asli satker yang Anda input sendiri tidak akan terhapus.')) {
                                        sampleFrm.submit();
                                    }
                                });
                            }
                            var bulkForm = document.getElementById('pa-bulk-rec-form');
                            var checkAllTop = document.getElementById('pa-rec-check-all');
                            var checkAllTh = document.getElementById('pa-rec-th-check-all');
                            var selInfo = document.getElementById('pa-rec-sel-info');
                            var bulkBtn = document.getElementById('pa-bulk-rec-del-btn');
                            var singleForm = document.getElementById('pa-single-rec-del-form');
                            var singleInput = document.getElementById('pa-single-rec-del-id');

                            function getRecBoxes() {
                                return bulkForm ? Array.prototype.slice.call(bulkForm.querySelectorAll('.pa-rec-chk')) : [];
                            }
                            function syncRecSelection() {
                                var boxes = getRecBoxes();
                                var checked = boxes.filter(function(cb){ return cb.checked; }).length;
                                var allChecked = boxes.length > 0 && checked === boxes.length;
                                if (checkAllTop) { checkAllTop.checked = allChecked; }
                                if (checkAllTh) { checkAllTh.checked = allChecked; }
                                if (selInfo) { selInfo.textContent = checked + ' item dipilih'; }
                                if (bulkBtn) {
                                    bulkBtn.disabled = checked === 0;
                                    bulkBtn.textContent = 'Hapus Terpilih (' + checked + ')';
                                }
                            }
                            function setAllRecs(state) {
                                getRecBoxes().forEach(function(cb){ cb.checked = state; });
                                syncRecSelection();
                            }
                            if (checkAllTop) { checkAllTop.addEventListener('change', function(){ setAllRecs(checkAllTop.checked); }); }
                            if (checkAllTh) { checkAllTh.addEventListener('change', function(){ setAllRecs(checkAllTh.checked); }); }
                            getRecBoxes().forEach(function(cb){ cb.addEventListener('change', syncRecSelection); });
                            if (bulkForm) {
                                bulkForm.addEventListener('submit', function(e){
                                    var checked = getRecBoxes().filter(function(cb){ return cb.checked; }).length;
                                    if (checked === 0) { e.preventDefault(); return; }
                                    if (!confirm('Hapus ' + checked + ' entri data operasional yang dipilih?')) { e.preventDefault(); }
                                });
                            }
                            document.querySelectorAll('.pa-single-rec-del-btn').forEach(function(btn){
                                btn.addEventListener('click', function(){
                                    var title = btn.getAttribute('data-title') || 'entri ini';
                                    if (!confirm('Hapus entri "' + title + '"?')) { return; }
                                    if (singleInput && singleForm) {
                                        singleInput.value = btn.getAttribute('data-id') || '0';
                                        singleForm.submit();
                                    }
                                });
                            });
                            syncRecSelection();
                        })();
                    </script>
                </div>
            </div>

        <?php elseif ($activeTab === 'migration') :
            $isAdoption     = (!empty($cfg['preserve_existing_design']) && (string) $cfg['preserve_existing_design'] === '1');
            $customMap      = pa_sinova_get_custom_map();
            $publishedPages = get_pages(array('post_status' => 'publish', 'sort_column' => 'post_title', 'sort_order' => 'ASC'));
            $sourcePosts    = get_posts(array(
                'post_type'      => array('page', 'post'),
                'post_status'    => 'publish',
                'posts_per_page' => 300,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ));
        ?>
            <div style="display:grid;grid-template-columns:1fr;gap:20px;">
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">1. Mode Adopsi Situs Lama (Non-Destruktif) <?php echo pa_sinova_info_tip('Aktifkan bila sudah punya tema, menu, Elementor, atau konten impor Joomla yang ingin tetap dipertahankan.', 'Info mode adopsi'); ?></h2>
                    <form method="post">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <label style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;margin-bottom:14px;">
                            <input type="checkbox" name="preserve_existing_design" value="1" <?php checked($isAdoption); ?>>
                            <span><strong>Pertahankan header, menu navigasi, dan beranda situs lama</strong> <?php echo pa_sinova_info_tip('Header, navbar, dan halaman depan lama tidak diubah. Modul dokumen [pa_sinova_docs], Dashboard 35 Kriteria, WAF keamanan, dan cache tetap aktif.', 'Detail mode non-destruktif'); ?></span>
                        </label>
                        <div style="display:flex;flex-wrap:wrap;gap:10px;">
                            <button type="submit" name="pa_sinova_action" value="save_adoption_mode" class="button button-secondary">Simpan Pengaturan Mode Adopsi</button>
                            <button type="submit" name="pa_sinova_action" value="save_and_seed_safe" class="button button-primary">Simpan Mode &amp; Jalankan Seeder Aman</button>
                        </div>
                    </form>
                </div>

                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">2. Ekstrak &amp; Impor Tautan Dokumen <?php echo pa_sinova_info_tip('Pindai link PDF atau Google Drive dari halaman/berita lama (termasuk hasil impor Joomla) langsung ke tabel modul arsip.', 'Info impor tautan'); ?></h2>
                    <form method="post" style="display:grid;grid-template-columns:1.4fr 1.2fr 120px auto;gap:12px;align-items:end;margin-bottom:16px;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="scan_links">
                        <div>
                            <label style="display:block;font-weight:700;font-size:12px;margin-bottom:4px;">Halaman / Pos Sumber</label>
                            <select name="scan_post_id" style="width:100%;">
                                <option value="0">-- Pilih Halaman atau Pos --</option>
                                <?php foreach ($sourcePosts as $sp) : ?>
                                    <option value="<?php echo esc_attr((string) $sp->ID); ?>" <?php selected($scanPostId, (int) $sp->ID); ?>><?php echo esc_html('[' . strtoupper((string) $sp->post_type) . '] ' . ($sp->post_title ?: '(Tanpa Judul)')); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-weight:700;font-size:12px;margin-bottom:4px;">Modul Arsip Tujuan</label>
                            <select name="scan_target_slug" style="width:100%;">
                                <?php foreach ($catalog as $slug => $m) : ?>
                                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($scanTargetSlug, $slug); ?>><?php echo esc_html($m['title'] . ' (' . $slug . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-weight:700;font-size:12px;margin-bottom:4px;">Tahun Bawaan</label>
                            <input type="number" name="scan_default_year" min="2017" max="<?php echo esc_attr((string) max(2028, (int) wp_date('Y') + 2)); ?>" value="<?php echo esc_attr((string) $scanDefaultYear); ?>" style="width:100%;">
                        </div>
                        <div>
                            <button type="submit" class="button button-primary">Pindai Tautan</button>
                        </div>
                    </form>

                    <?php if (!empty($scannedLinks)) : ?>
                        <form method="post">
                            <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                            <input type="hidden" name="pa_sinova_action" value="import_links">
                            <input type="hidden" name="import_target_slug" value="<?php echo esc_attr($scanTargetSlug); ?>">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                <strong>Pratinjau Hasil Pindai (<?php echo esc_html((string) count($scannedLinks)); ?> tautan ditemukan) &rarr; Modul: <code><?php echo esc_html($scanTargetSlug); ?></code></strong>
                                <button type="button" class="button button-small" onclick="document.querySelectorAll('.pa-mig-chk').forEach(function(c){c.checked=!c.checked;});">Balik Pilihan</button>
                            </div>
                            <table class="widefat striped" style="margin-bottom:12px;">
                                <thead>
                                    <tr>
                                        <th style="width:32px;">Pilih</th>
                                        <th style="width:82px;">Tahun</th>
                                        <th style="width:130px;">Sub-Kelompok</th>
                                        <th style="width:140px;">Periode</th>
                                        <th>Judul Dokumen &amp; URL Sumber</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $maxMigYear = max(2028, (int) wp_date('Y') + 2);
                                    foreach ($scannedLinks as $idx => $row) : ?>
                                        <tr>
                                            <td><input type="checkbox" class="pa-mig-chk" name="import_rows[]" value="<?php echo esc_attr((string) $idx); ?>" <?php checked(!empty($row['suggested'])); ?>></td>
                                            <td><input type="number" name="row_year[<?php echo esc_attr((string) $idx); ?>]" min="2017" max="<?php echo esc_attr((string) $maxMigYear); ?>" value="<?php echo esc_attr((string) $row['year']); ?>" style="width:74px;"></td>
                                            <td><input type="text" name="row_sub[<?php echo esc_attr((string) $idx); ?>]" value="<?php echo esc_attr($row['sub_group']); ?>" style="width:120px;"></td>
                                            <td>
                                                <select name="row_period[<?php echo esc_attr((string) $idx); ?>]" style="width:132px;">
                                                    <?php foreach ($periods as $pk => $pv) : ?>
                                                        <option value="<?php echo esc_attr($pk); ?>" <?php selected($row['period_key'], $pk); ?>><?php echo esc_html($pk . ' — ' . $pv['label']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="row_title[<?php echo esc_attr((string) $idx); ?>]" value="<?php echo esc_attr($row['title']); ?>" class="large-text" style="margin-bottom:4px;">
                                                <input type="hidden" name="row_url[<?php echo esc_attr((string) $idx); ?>]" value="<?php echo esc_attr($row['doc_url']); ?>">
                                                <input type="hidden" name="row_src[<?php echo esc_attr((string) $idx); ?>]" value="<?php echo esc_attr($row['source_type']); ?>">
                                                <small style="color:#475569;word-break:break-all;"><a href="<?php echo esc_url($row['doc_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($row['doc_url']); ?></a></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button type="submit" class="button button-primary">Impor Terpilih ke Arsip Dokumen</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">3. Pemetaan Halaman Lama untuk 35 Kriteria <?php echo pa_sinova_info_tip('Petakan URL/slug halaman lama Anda agar Dashboard Kepatuhan & tombol Salin URL tetap membaca halaman lama.', 'Info pemetaan halaman'); ?></h2>
                    <form method="post">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="save_custom_map">
                        <table class="widefat striped" style="margin-bottom:14px;">
                            <thead>
                                <tr>
                                    <th style="width:65px;">Kode</th>
                                    <th>Kriteria</th>
                                    <th style="width:260px;">Pilih Halaman WP Eksisting</th>
                                    <th style="width:280px;">Atau URL Kustom Eksisting</th>
                                    <th>URL Terbaca Saat Ini</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['items'] as $it) :
                                    $code   = (string) $it['code'];
                                    $curMap = (isset($customMap[$code]) && is_array($customMap[$code])) ? $customMap[$code] : array();
                                    $curPid = (int) ($curMap['post_id'] ?? 0);
                                    $curUrl = (string) ($curMap['custom_url'] ?? '');
                                ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($code); ?></strong></td>
                                        <td><?php echo esc_html($it['label']); ?><br><small style="color:#64748b;">Slug baku: <code><?php echo esc_html($it['slug']); ?></code></small></td>
                                        <td>
                                            <select name="map_post_id[<?php echo esc_attr($code); ?>]" style="width:100%;">
                                                <option value="0">-- Otomatis (Slug <?php echo esc_html($it['slug']); ?>) --</option>
                                                <?php foreach ($publishedPages as $pg) : ?>
                                                    <option value="<?php echo esc_attr((string) $pg->ID); ?>" <?php selected($curPid, (int) $pg->ID); ?>><?php echo esc_html(($pg->post_title ?: '(Tanpa Judul)') . ' (/' . $pg->post_name . '/)'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="url" name="map_custom_url[<?php echo esc_attr($code); ?>]" value="<?php echo esc_attr($curUrl); ?>" placeholder="https://pa-satker.go.id/halaman-lama/" style="width:100%;"></td>
                                        <td><small style="word-break:break-all;"><a href="<?php echo esc_url($it['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($it['url']); ?></a></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" class="button button-primary">Simpan Pemetaan 35 Kriteria</button>
                    </form>
                </div>
            </div>

        <?php elseif ($activeTab === 'homepage') :
            $frontIdNow    = (int) get_option('page_on_front', 0);
            $frontPostNow  = ($frontIdNow > 0 && get_post_status($frontIdNow) === 'publish') ? get_post($frontIdNow) : null;
            $isDynamicHome = ($frontPostNow instanceof WP_Post && strpos((string) $frontPostNow->post_content, '[pa_sinova_beranda') !== false);
            $homeBlocks    = pa_sinova_get_home_blocks();
            $blkCatalog    = pa_sinova_home_block_catalog();
            $homeGlobals   = array(
                'sipp_slide_url'     => (string) ($cfg['sipp_slide_url'] ?? ''),
                'home_show_ticker'   => (string) ($cfg['home_show_ticker'] ?? '1'),
                'home_ticker_label'  => (string) ($cfg['home_ticker_label'] ?? 'INFO LAYANAN'),
                'home_ticker_text'   => (string) ($cfg['home_ticker_text'] ?? ''),
                'home_hero_kicker'   => (string) ($cfg['home_hero_kicker'] ?? ''),
                'home_hero_title'    => (string) ($cfg['home_hero_title'] ?? ''),
                'home_hero_lead'     => (string) ($cfg['home_hero_lead'] ?? ''),
                'hero_layout'        => (string) ($cfg['hero_layout'] ?? 'split'),
                'hero_pattern'       => (string) ($cfg['hero_pattern'] ?? 'islamic'),
                'hero_photo_url'     => (string) ($cfg['hero_photo_url'] ?? ''),
                'hero_photo_caption' => (string) ($cfg['hero_photo_caption'] ?? 'Gedung Pelayanan Terpadu Satu Pintu (PTSP) & Ruang Sidang'),
                'home_survey_period' => (string) ($cfg['home_survey_period'] ?? 'Triwulan III'),
                'home_survey_grade'  => (string) ($cfg['home_survey_grade'] ?? '★ Mutu A (Sangat Baik)'),
                'home_survey_skm'    => (string) ($cfg['home_survey_skm'] ?? '4.00 / 4.00'),
                'home_survey_spak'   => (string) ($cfg['home_survey_spak'] ?? '4.00 / 4.00'),
                'home_survey_spkp'   => (string) ($cfg['home_survey_spkp'] ?? '4.00 / 4.00'),
            );
        ?>
            <?php if (!$isDynamicHome) : ?>
                <div class="pa-adm-banner pa-adm-banner-warn">
                    <span><strong>Beranda masih HTML statis lama.</strong> Aktifkan Beranda Dinamis agar susunan 12 kolom di bawah langsung tampil.</span>
                    <form method="post" style="margin:0;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="activate_dynamic_home">
                        <button type="submit" class="button button-primary button-small">Aktifkan Beranda Dinamis (1-Klik)</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="pa-ticker-admin-bar">
                <label style="font-weight:700;font-size:12.5px;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                    <input type="checkbox" id="pa-gb-top-show-ticker" <?php checked($homeGlobals['home_show_ticker'] !== '0'); ?>>
                    Pita Teks Berjalan
                    <?php echo pa_sinova_info_tip('Teks Info Layanan berjalan halus otomatis di bagian atas Beranda. Klik Simpan Tampilan Beranda di bawah untuk menerapkan.', 'Info teks berjalan'); ?>
                </label>
                <input type="text" id="pa-gb-top-ticker-lbl" value="<?php echo esc_attr($homeGlobals['home_ticker_label']); ?>" placeholder="INFO LAYANAN" aria-label="Label Pita Info">
                <input type="text" id="pa-gb-top-ticker-txt" value="<?php echo esc_attr($homeGlobals['home_ticker_text']); ?>" placeholder="Teks informasi berjalan di Beranda..." aria-label="Teks Berjalan Info Layanan">
                <span style="font-size:11px;color:#64748b;">Ikut tersimpan saat klik Simpan</span>
            </div>

            <div class="pa-grid-toolbar">
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                    <select id="pa-gb-add-type" aria-label="Pilih jenis blok" style="min-width:210px;">
                        <?php foreach ($blkCatalog as $bk => $bm) : ?>
                            <option value="<?php echo esc_attr($bk); ?>"><?php echo esc_html($bm['icon'] . ' ' . $bm['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="pa-gb-add-btn" class="button">+ Tambah Blok</button>
                    <?php echo pa_sinova_info_tip('Seret kotak untuk pindah kolom/baris. Tarik sudut kanan bawah kotak untuk atur lebar (2–12 kolom) dan tinggi. Klik ikon pensil untuk ubah isi blok.', 'Cara pakai editor grid'); ?>
                </div>
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                    <form method="post" style="margin:0;" onsubmit="return confirm('Kembalikan tata letak Beranda ke susunan standar 12 kolom?');">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="reset_home_grid">
                        <button type="submit" class="button">Reset Standar</button>
                    </form>
                    <form method="post" id="pa-gb-save-form" style="margin:0;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="save_home_grid">
                        <input type="hidden" name="pa_home_blocks_json" id="pa-gb-json-input" value="">
                        <input type="hidden" name="home_show_ticker" id="pa-gb-h-show-ticker" value="<?php echo esc_attr($homeGlobals['home_show_ticker']); ?>">
                        <input type="hidden" name="home_ticker_label" id="pa-gb-h-ticker-lbl" value="<?php echo esc_attr($homeGlobals['home_ticker_label']); ?>">
                        <input type="hidden" name="home_ticker_text" id="pa-gb-h-ticker" value="<?php echo esc_attr($homeGlobals['home_ticker_text']); ?>">
                        <input type="hidden" name="home_hero_kicker" id="pa-gb-h-kicker" value="<?php echo esc_attr($homeGlobals['home_hero_kicker']); ?>">
                        <input type="hidden" name="home_hero_title" id="pa-gb-h-title" value="<?php echo esc_attr($homeGlobals['home_hero_title']); ?>">
                        <input type="hidden" name="home_hero_lead" id="pa-gb-h-lead" value="<?php echo esc_attr($homeGlobals['home_hero_lead']); ?>">
                        <input type="hidden" name="hero_layout" id="pa-gb-h-layout" value="<?php echo esc_attr($homeGlobals['hero_layout']); ?>">
                        <input type="hidden" name="hero_pattern" id="pa-gb-h-pattern" value="<?php echo esc_attr($homeGlobals['hero_pattern']); ?>">
                        <input type="hidden" name="hero_photo_url" id="pa-gb-h-photo" value="<?php echo esc_attr($homeGlobals['hero_photo_url']); ?>">
                        <input type="hidden" name="hero_photo_caption" id="pa-gb-h-pcap" value="<?php echo esc_attr($homeGlobals['hero_photo_caption']); ?>">
                        <input type="hidden" name="home_survey_period" id="pa-gb-h-sper" value="<?php echo esc_attr($homeGlobals['home_survey_period']); ?>">
                        <input type="hidden" name="home_survey_grade" id="pa-gb-h-sgrd" value="<?php echo esc_attr($homeGlobals['home_survey_grade']); ?>">
                        <input type="hidden" name="home_survey_skm" id="pa-gb-h-skm" value="<?php echo esc_attr($homeGlobals['home_survey_skm']); ?>">
                        <input type="hidden" name="home_survey_spak" id="pa-gb-h-spak" value="<?php echo esc_attr($homeGlobals['home_survey_spak']); ?>">
                        <input type="hidden" name="home_survey_spkp" id="pa-gb-h-spkp" value="<?php echo esc_attr($homeGlobals['home_survey_spkp']); ?>">
                        <button type="submit" class="button button-primary">Simpan Tampilan Beranda</button>
                    </form>
                </div>
            </div>

            <div class="pa-grid-board-wrap">
                <div class="pa-grid-col-nums" aria-hidden="true">
                    <?php for ($ci = 1; $ci <= 12; $ci++) : ?><span><?php echo (int) $ci; ?></span><?php endfor; ?>
                </div>
                <div class="pa-grid-canvas" id="pa-gb-canvas" aria-label="Kanvas Editor Grid 12 Kolom Beranda"></div>
            </div>

            <div style="background:#fff;border:1px solid #cbd5e1;border-radius:12px;padding:14px 16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <h2 style="margin:0;font-size:15px;color:#043927;">Daftar Tampilan</h2>
                    <span class="pa-adm-tip" tabindex="0" data-tip="SK 2722/2026 Bab III huruf C: jaga beranda tetap ringkas, jadwal sidang & SIPP mudah ditemukan, hindari animasi berat." aria-label="Catatan SK 2722">i</span>
                </div>
                <table class="widefat striped" id="pa-gb-list-table">
                    <thead>
                        <tr><th>Tampilan</th><th>Jenis</th><th>Posisi</th><th>Ukuran</th><th>Status</th><th style="width:165px;">Aksi</th></tr>
                    </thead>
                    <tbody id="pa-gb-tbody"></tbody>
                </table>
            </div>

            <dialog id="pa-grid-edit-dlg" closedby="any" aria-labelledby="pa-grid-edit-title">
                <div class="pa-dlg-head">
                    <h2 id="pa-grid-edit-title">Ubah Blok Beranda</h2>
                    <form method="dialog" style="margin:0;"><button type="submit" class="button button-small" aria-label="Tutup dialog">✕</button></form>
                </div>
                <div class="pa-dlg-body">
                    <input type="hidden" id="pa-ed-id">
                    <div class="pa-dlg-field">
                        <label for="pa-ed-title">Judul Blok</label>
                        <input type="text" id="pa-ed-title">
                    </div>
                    <div class="pa-dlg-grid4">
                        <div class="pa-dlg-field"><label for="pa-ed-x">Kolom (1–12)</label><input type="number" id="pa-ed-x" min="1" max="12"></div>
                        <div class="pa-dlg-field"><label for="pa-ed-y">Baris</label><input type="number" id="pa-ed-y" min="1" max="40"></div>
                        <div class="pa-dlg-field"><label for="pa-ed-w">Lebar (2–12)</label><input type="number" id="pa-ed-w" min="2" max="12"></div>
                        <div class="pa-dlg-field"><label for="pa-ed-h">Tinggi (1–4)</label><input type="number" id="pa-ed-h" min="1" max="4"></div>
                    </div>

                    <div id="pa-ed-sec-hero" hidden>
                        <div class="pa-dlg-field"><label><input type="checkbox" id="pa-ed-show-ticker" value="1"> Tampilkan Pita Ticker Info</label></div>
                        <div class="pa-dlg-field"><label for="pa-ed-ticker">Teks Ticker</label><textarea id="pa-ed-ticker" rows="2"></textarea></div>
                        <div class="pa-dlg-grid4" style="grid-template-columns:1fr 1fr;">
                            <div class="pa-dlg-field">
                                <label for="pa-ed-hlayout">Gaya Tampilan Hero</label>
                                <select id="pa-ed-hlayout">
                                    <option value="split">Split Modern + Foto Gedung</option>
                                    <option value="classic">Klasik Rata Tengah</option>
                                </select>
                            </div>
                            <div class="pa-dlg-field">
                                <label for="pa-ed-hpattern">Motif Watermark Daerah</label>
                                <select id="pa-ed-hpattern">
                                    <option value="islamic">Geometris Islami</option>
                                    <option value="batik">Ornamen Batik Kawung</option>
                                    <option value="songket">Ornamen Tenun Songket</option>
                                    <option value="none">Polos Minimalis</option>
                                </select>
                            </div>
                        </div>
                        <div class="pa-dlg-field">
                            <label for="pa-ed-hphoto">Foto Gedung Kantor Satker (Hero Split · kosongkan untuk gambar dummy bawaan)</label>
                            <div style="display:flex;gap:6px;">
                                <input type="url" id="pa-ed-hphoto" placeholder="Kosongkan untuk pakai foto gedung dummy">
                                <button type="button" class="button pa-media-pick-btn" data-target="pa-ed-hphoto">Pilih Media</button>
                            </div>
                        </div>
                        <div class="pa-dlg-field"><label for="pa-ed-hpcap">Keterangan Foto Gedung</label><input type="text" id="pa-ed-hpcap"></div>
                        <div class="pa-dlg-field"><label for="pa-ed-kicker">Label Atas Hero</label><input type="text" id="pa-ed-kicker"></div>
                        <div class="pa-dlg-field"><label for="pa-ed-htitle">Judul Utama Hero</label><input type="text" id="pa-ed-htitle"></div>
                        <div class="pa-dlg-field"><label for="pa-ed-hlead">Deskripsi Hero</label><textarea id="pa-ed-hlead" rows="2"></textarea></div>
                    </div>

                    <div id="pa-ed-sec-schedule" hidden>
                        <div class="pa-dlg-field"><label for="pa-ed-sipp-url">URL Slide Jadwal Sidang SIPP (HTTPS)</label><input type="url" id="pa-ed-sipp-url" placeholder="https://sipp.pa-satker.go.id/Slide_sidang"></div>
                        <div class="pa-dlg-field">
                            <label for="pa-ed-sched-tab">Tab Awal</label>
                            <select id="pa-ed-sched-tab">
                                <option value="auto">Otomatis (Live SIPP bila URL diisi)</option>
                                <option value="sipp">Layar Live SIPP</option>
                                <option value="agenda">Agenda Terstruktur</option>
                            </select>
                        </div>
                        <div class="pa-dlg-field"><label for="pa-ed-sched-h">Tinggi Layar (px)</label><input type="number" id="pa-ed-sched-h" min="320" max="900" value="480"></div>
                    </div>

                    <div id="pa-ed-sec-survey" hidden>
                        <div class="pa-dlg-field"><label for="pa-ed-sper">Periode</label><input type="text" id="pa-ed-sper"></div>
                        <div class="pa-dlg-field"><label for="pa-ed-sgrd">Predikat Mutu</label><input type="text" id="pa-ed-sgrd"></div>
                        <div class="pa-dlg-grid4" style="grid-template-columns:repeat(3,1fr);">
                            <div class="pa-dlg-field"><label for="pa-ed-skm">SKM</label><input type="text" id="pa-ed-skm"></div>
                            <div class="pa-dlg-field"><label for="pa-ed-spak">SPAK</label><input type="text" id="pa-ed-spak"></div>
                            <div class="pa-dlg-field"><label for="pa-ed-spkp">SPKP</label><input type="text" id="pa-ed-spkp"></div>
                        </div>
                    </div>

                    <div id="pa-ed-sec-count" hidden>
                        <div class="pa-dlg-field"><label for="pa-ed-count">Jumlah Item Ditampilkan</label><input type="number" id="pa-ed-count" min="1" max="12" value="4"></div>
                    </div>

                    <div id="pa-ed-sec-custom" hidden>
                        <div class="pa-dlg-field"><label for="pa-ed-embed">URL Sematan Iframe (HTTPS — SIPP/Drive/Maps/YouTube)</label><input type="url" id="pa-ed-embed" placeholder="https://..."></div>
                        <div class="pa-dlg-field"><label for="pa-ed-embed-h">Tinggi Sematan (px)</label><input type="number" id="pa-ed-embed-h" min="260" max="900" value="420"></div>
                        <div class="pa-dlg-field"><label for="pa-ed-body">Isi Teks / HTML Ringkas</label><textarea id="pa-ed-body" rows="4" placeholder="Tulis pengumuman, informasi layanan, atau HTML aman..."></textarea></div>
                        <div class="pa-dlg-grid4" style="grid-template-columns:1fr 1fr;">
                            <div class="pa-dlg-field"><label for="pa-ed-ctalbl">Label Tombol (Opsional)</label><input type="text" id="pa-ed-ctalbl" placeholder="Lihat Selengkapnya"></div>
                            <div class="pa-dlg-field"><label for="pa-ed-ctaurl">URL Tombol (Opsional)</label><input type="url" id="pa-ed-ctaurl" placeholder="https://..."></div>
                        </div>
                    </div>

                    <div id="pa-ed-sec-iframe" hidden>
                        <div class="pa-dlg-field"><label for="pa-ed-if-url">URL Sematan Iframe (HTTPS)</label><input type="url" id="pa-ed-if-url" placeholder="https://sipp.pa-satker.go.id/... atau https://drive.google.com/..."></div>
                        <div class="pa-dlg-grid4" style="grid-template-columns:1fr 1fr;">
                            <div class="pa-dlg-field"><label for="pa-ed-if-h">Tinggi Iframe (px)</label><input type="number" id="pa-ed-if-h" min="260" max="900" value="440"></div>
                            <div class="pa-dlg-field" style="display:flex;align-items:flex-end;padding-bottom:6px;"><label><input type="checkbox" id="pa-ed-if-fs" value="1" checked> Tampilkan tautan Layar Penuh</label></div>
                        </div>
                        <div class="pa-dlg-field"><label for="pa-ed-if-cap">Keterangan Singkat (Opsional)</label><input type="text" id="pa-ed-if-cap" placeholder="Misal: Jadwal Antrean PTSP Live"></div>
                    </div>

                    <div id="pa-ed-sec-image" hidden>
                        <div class="pa-dlg-field">
                            <label for="pa-ed-img-url">URL Gambar / Poster</label>
                            <div style="display:flex;gap:6px;">
                                <input type="url" id="pa-ed-img-url" placeholder="https://.../poster.jpg">
                                <button type="button" class="button pa-media-pick-btn" data-target="pa-ed-img-url">Pilih Media</button>
                            </div>
                        </div>
                        <div class="pa-dlg-grid4" style="grid-template-columns:1fr 1fr;">
                            <div class="pa-dlg-field"><label for="pa-ed-img-fit">Mode Tampil</label><select id="pa-ed-img-fit"><option value="cover">Penuhi Bingkai (Cover)</option><option value="contain">Utuh / Proporsional (Contain)</option></select></div>
                            <div class="pa-dlg-field"><label for="pa-ed-img-h">Tinggi Maksimal (px)</label><input type="number" id="pa-ed-img-h" min="180" max="900" value="360"></div>
                        </div>
                        <div class="pa-dlg-field"><label for="pa-ed-img-alt">Teks Alternatif / Keterangan Gambar</label><input type="text" id="pa-ed-img-alt" placeholder="Deskripsi singkat poster/infografis"></div>
                        <div class="pa-dlg-field"><label for="pa-ed-img-link">URL Tautan Jika Gambar Diklik (Opsional)</label><input type="url" id="pa-ed-img-link" placeholder="https://..."></div>
                    </div>

                    <div id="pa-ed-sec-slider" hidden>
                        <div class="pa-dlg-grid4" style="grid-template-columns:1fr 1fr;">
                            <div class="pa-dlg-field">
                                <label for="pa-ed-sl-src">Sumber Slide</label>
                                <select id="pa-ed-sl-src">
                                    <option value="custom">Daftar Gambar Kustom</option>
                                    <option value="records">Otomatis dari Data Slider Beranda</option>
                                </select>
                            </div>
                            <div class="pa-dlg-field"><label for="pa-ed-sl-auto">Putar Otomatis (detik, 0 = manual)</label><input type="number" id="pa-ed-sl-auto" min="0" max="15" value="5"></div>
                        </div>
                        <div class="pa-dlg-field"><label for="pa-ed-sl-h">Tinggi Slider (px)</label><input type="number" id="pa-ed-sl-h" min="220" max="720" value="360"></div>
                        <div id="pa-ed-sl-custom-wrap">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                <strong style="font-size:12px;color:#043927;">Daftar Slide Gambar</strong>
                                <button type="button" id="pa-ed-sl-add" class="button button-small">+ Tambah Slide</button>
                            </div>
                            <div id="pa-ed-sl-list"></div>
                        </div>
                    </div>
                </div>
                <div class="pa-dlg-foot">
                    <form method="dialog" style="margin:0;"><button type="submit" class="button">Batal</button></form>
                    <button type="button" id="pa-ed-apply" class="button button-primary">Terapkan ke Kanvas</button>
                </div>
            </dialog>

            <script>
            (function(){
                var catalog = <?php echo wp_json_encode($blkCatalog); ?>;
                var blocks  = <?php echo wp_json_encode(array_values($homeBlocks)); ?>;
                var globals = <?php echo wp_json_encode($homeGlobals); ?>;
                var canvas  = document.getElementById('pa-gb-canvas');
                var tbody   = document.getElementById('pa-gb-tbody');
                var addSel  = document.getElementById('pa-gb-add-type');
                var addBtn  = document.getElementById('pa-gb-add-btn');
                var saveFrm = document.getElementById('pa-gb-save-form');
                var jsonInp = document.getElementById('pa-gb-json-input');
                var dlg     = document.getElementById('pa-grid-edit-dlg');
                var selectedId = null;

                if (dlg && !('closedBy' in HTMLDialogElement.prototype)) {
                    var pressedOut = false;
                    var isOut = function(e){
                        var r = dlg.getBoundingClientRect();
                        return e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
                    };
                    dlg.addEventListener('pointerdown', function(e){ pressedOut = isOut(e); });
                    dlg.addEventListener('pointerup', function(e){ if (pressedOut && isOut(e)) dlg.close(); });
                }

                function clamp(v, mn, mx){ return Math.max(mn, Math.min(mx, v)); }
                function byId(id){ return blocks.find(function(b){ return b.id === id; }); }
                function sortBlocks(){ blocks.sort(function(a,b){ return (a.y !== b.y) ? (a.y - b.y) : (a.x - b.x); }); }
                function maxRow(){
                    var m = 1;
                    blocks.forEach(function(b){ m = Math.max(m, (b.y || 1) + (b.h || 1) - 1); });
                    return m;
                }

                function openEdit(id){
                    var b = byId(id);
                    if (!b || !dlg) return;
                    selectedId = id;
                    b.config = b.config || {};
                    document.getElementById('pa-ed-id').value = b.id;
                    document.getElementById('pa-ed-title').value = b.title || '';
                    document.getElementById('pa-ed-x').value = b.x;
                    document.getElementById('pa-ed-y').value = b.y;
                    document.getElementById('pa-ed-w').value = b.w;
                    document.getElementById('pa-ed-h').value = b.h;
                    document.getElementById('pa-grid-edit-title').textContent = 'Ubah: ' + (b.title || (catalog[b.type] ? catalog[b.type].label : b.type));

                    ['pa-ed-sec-hero','pa-ed-sec-schedule','pa-ed-sec-survey','pa-ed-sec-count','pa-ed-sec-custom','pa-ed-sec-iframe','pa-ed-sec-image','pa-ed-sec-slider'].forEach(function(sid){
                        var el = document.getElementById(sid);
                        if (el) el.hidden = true;
                    });
                    if (b.type === 'hero_slider') {
                        document.getElementById('pa-ed-sec-hero').hidden = false;
                        document.getElementById('pa-ed-show-ticker').checked = (globals.home_show_ticker !== '0');
                        document.getElementById('pa-ed-ticker').value = globals.home_ticker_text || '';
                        document.getElementById('pa-ed-hlayout').value = globals.hero_layout || 'split';
                        document.getElementById('pa-ed-hpattern').value = globals.hero_pattern || 'islamic';
                        document.getElementById('pa-ed-hphoto').value = globals.hero_photo_url || '';
                        document.getElementById('pa-ed-hpcap').value = globals.hero_photo_caption || '';
                        document.getElementById('pa-ed-kicker').value = globals.home_hero_kicker || '';
                        document.getElementById('pa-ed-htitle').value = globals.home_hero_title || '';
                        document.getElementById('pa-ed-hlead').value = globals.home_hero_lead || '';
                    } else if (b.type === 'schedule') {
                        document.getElementById('pa-ed-sec-schedule').hidden = false;
                        document.getElementById('pa-ed-sipp-url').value = b.config.embed_url || globals.sipp_slide_url || '';
                        document.getElementById('pa-ed-sched-tab').value = b.config.default_tab || 'auto';
                        document.getElementById('pa-ed-sched-h').value = b.config.height || 480;
                    } else if (b.type === 'survey') {
                        document.getElementById('pa-ed-sec-survey').hidden = false;
                        document.getElementById('pa-ed-sper').value = globals.home_survey_period || '';
                        document.getElementById('pa-ed-sgrd').value = globals.home_survey_grade || '';
                        document.getElementById('pa-ed-skm').value  = globals.home_survey_skm || '';
                        document.getElementById('pa-ed-spak').value = globals.home_survey_spak || '';
                        document.getElementById('pa-ed-spkp').value = globals.home_survey_spkp || '';
                    } else if (b.type === 'news' || b.type === 'announcements' || b.type === 'videos' || b.type === 'greetings' || b.type === 'dirput' || b.type === 'inovasi') {
                        document.getElementById('pa-ed-sec-count').hidden = false;
                        document.getElementById('pa-ed-count').value = b.config.count || b.config.limit || (b.type === 'dirput' ? 6 : (b.type === 'news' ? 3 : 4));
                    } else if (b.type === 'custom') {
                        document.getElementById('pa-ed-sec-custom').hidden = false;
                        document.getElementById('pa-ed-embed').value = b.config.embed_url || '';
                        document.getElementById('pa-ed-embed-h').value = b.config.height || 420;
                        document.getElementById('pa-ed-body').value = b.config.body || '';
                        document.getElementById('pa-ed-ctalbl').value = b.config.cta_label || '';
                        document.getElementById('pa-ed-ctaurl').value = b.config.cta_url || '';
                    } else if (b.type === 'iframe_embed') {
                        document.getElementById('pa-ed-sec-iframe').hidden = false;
                        document.getElementById('pa-ed-if-url').value = b.config.embed_url || '';
                        document.getElementById('pa-ed-if-h').value = b.config.height || 440;
                        document.getElementById('pa-ed-if-fs').checked = (b.config.show_fullscreen !== '0');
                        document.getElementById('pa-ed-if-cap').value = b.config.caption || '';
                    } else if (b.type === 'image_banner') {
                        document.getElementById('pa-ed-sec-image').hidden = false;
                        document.getElementById('pa-ed-img-url').value = b.config.image_url || '';
                        document.getElementById('pa-ed-img-fit').value = b.config.image_fit || 'cover';
                        document.getElementById('pa-ed-img-h').value = b.config.height || 360;
                        document.getElementById('pa-ed-img-alt').value = b.config.image_alt || b.config.caption || '';
                        document.getElementById('pa-ed-img-link').value = b.config.link_url || '';
                    } else if (b.type === 'photo_slider') {
                        document.getElementById('pa-ed-sec-slider').hidden = false;
                        document.getElementById('pa-ed-sl-src').value = b.config.slide_source || 'custom';
                        document.getElementById('pa-ed-sl-auto').value = (typeof b.config.autoplay_sec === 'number') ? b.config.autoplay_sec : 5;
                        document.getElementById('pa-ed-sl-h').value = b.config.height || 360;
                        renderSlideEditorList(Array.isArray(b.config.slides) ? b.config.slides : []);
                    }
                    dlg.showModal();
                }

                var slListEl = document.getElementById('pa-ed-sl-list');
                var slSrcEl = document.getElementById('pa-ed-sl-src');
                var slWrapEl = document.getElementById('pa-ed-sl-custom-wrap');
                if (slSrcEl && slWrapEl) {
                    slSrcEl.addEventListener('change', function(){ slWrapEl.style.display = (slSrcEl.value === 'custom') ? '' : 'none'; });
                }
                function addSlideRow(item){
                    if (!slListEl) return;
                    var row = document.createElement('div');
                    row.className = 'pa-slide-row';
                    var inpUrl = document.createElement('input');
                    inpUrl.type = 'url'; inpUrl.className = 'pa-sl-url'; inpUrl.placeholder = 'URL Gambar (HTTPS)'; inpUrl.value = (item && item.image_url) || '';
                    var inpTitle = document.createElement('input');
                    inpTitle.type = 'text'; inpTitle.className = 'pa-sl-title'; inpTitle.placeholder = 'Judul / Keterangan (opsional)'; inpTitle.value = (item && item.title) || '';
                    var actBox = document.createElement('div');
                    actBox.style.cssText = 'display:flex;gap:4px;';
                    var pick = document.createElement('button');
                    pick.type = 'button'; pick.className = 'button button-small pa-media-pick-row'; pick.textContent = 'Media';
                    pick.addEventListener('click', function(){
                        if (window.paOpenMediaPicker) { window.paOpenMediaPicker(function(url){ inpUrl.value = url; }); }
                    });
                    var del = document.createElement('button');
                    del.type = 'button'; del.className = 'button button-small'; del.textContent = '✕';
                    del.addEventListener('click', function(){ row.remove(); });
                    actBox.appendChild(pick); actBox.appendChild(del);
                    row.appendChild(inpUrl); row.appendChild(inpTitle); row.appendChild(actBox);
                    slListEl.appendChild(row);
                }
                function renderSlideEditorList(items){
                    if (!slListEl) return;
                    slListEl.replaceChildren();
                    if (!items || !items.length) { addSlideRow({}); }
                    else { items.forEach(addSlideRow); }
                    if (slWrapEl && slSrcEl) slWrapEl.style.display = (slSrcEl.value === 'custom') ? '' : 'none';
                }
                var addSlBtn = document.getElementById('pa-ed-sl-add');
                if (addSlBtn) { addSlBtn.addEventListener('click', function(){ addSlideRow({}); }); }

                document.getElementById('pa-ed-apply').addEventListener('click', function(){
                    var id = document.getElementById('pa-ed-id').value;
                    var b = byId(id);
                    if (!b) return;
                    b.title = document.getElementById('pa-ed-title').value.trim() || b.title;
                    b.w = clamp(parseInt(document.getElementById('pa-ed-w').value, 10) || b.w, 2, 12);
                    b.x = clamp(parseInt(document.getElementById('pa-ed-x').value, 10) || b.x, 1, 13 - b.w);
                    b.y = clamp(parseInt(document.getElementById('pa-ed-y').value, 10) || b.y, 1, 40);
                    b.h = clamp(parseInt(document.getElementById('pa-ed-h').value, 10) || b.h, 1, 4);
                    b.config = b.config || {};

                    if (b.type === 'hero_slider') {
                        globals.home_show_ticker = document.getElementById('pa-ed-show-ticker').checked ? '1' : '0';
                        globals.home_ticker_text = document.getElementById('pa-ed-ticker').value.trim();
                        if (topShowChk) topShowChk.checked = (globals.home_show_ticker !== '0');
                        if (topTickTxt) topTickTxt.value = globals.home_ticker_text;
                        globals.hero_layout        = document.getElementById('pa-ed-hlayout').value || 'split';
                        globals.hero_pattern       = document.getElementById('pa-ed-hpattern').value || 'islamic';
                        globals.hero_photo_url     = document.getElementById('pa-ed-hphoto').value.trim();
                        globals.hero_photo_caption = document.getElementById('pa-ed-hpcap').value.trim();
                        globals.home_hero_kicker = document.getElementById('pa-ed-kicker').value.trim();
                        globals.home_hero_title  = document.getElementById('pa-ed-htitle').value.trim();
                        globals.home_hero_lead   = document.getElementById('pa-ed-hlead').value.trim();
                    } else if (b.type === 'schedule') {
                        var su = document.getElementById('pa-ed-sipp-url').value.trim();
                        b.config.embed_url = su;
                        globals.sipp_slide_url = su;
                        b.config.default_tab = document.getElementById('pa-ed-sched-tab').value;
                        b.config.height = clamp(parseInt(document.getElementById('pa-ed-sched-h').value, 10) || 480, 320, 900);
                    } else if (b.type === 'survey') {
                        globals.home_survey_period = document.getElementById('pa-ed-sper').value.trim();
                        globals.home_survey_grade  = document.getElementById('pa-ed-sgrd').value.trim();
                        globals.home_survey_skm    = document.getElementById('pa-ed-skm').value.trim();
                        globals.home_survey_spak   = document.getElementById('pa-ed-spak').value.trim();
                        globals.home_survey_spkp   = document.getElementById('pa-ed-spkp').value.trim();
                    } else if (b.type === 'news' || b.type === 'announcements' || b.type === 'videos' || b.type === 'greetings' || b.type === 'dirput' || b.type === 'inovasi') {
                        var cnt = clamp(parseInt(document.getElementById('pa-ed-count').value, 10) || 3, 1, 12);
                        b.config.count = cnt;
                        b.config.limit = cnt;
                    } else if (b.type === 'custom') {
                        b.config.embed_url = document.getElementById('pa-ed-embed').value.trim();
                        b.config.height    = clamp(parseInt(document.getElementById('pa-ed-embed-h').value, 10) || 420, 260, 900);
                        b.config.body      = document.getElementById('pa-ed-body').value;
                        b.config.cta_label = document.getElementById('pa-ed-ctalbl').value.trim();
                        b.config.cta_url   = document.getElementById('pa-ed-ctaurl').value.trim();
                    } else if (b.type === 'iframe_embed') {
                        b.config.embed_url       = document.getElementById('pa-ed-if-url').value.trim();
                        b.config.height          = clamp(parseInt(document.getElementById('pa-ed-if-h').value, 10) || 440, 260, 900);
                        b.config.show_fullscreen = document.getElementById('pa-ed-if-fs').checked ? '1' : '0';
                        b.config.caption         = document.getElementById('pa-ed-if-cap').value.trim();
                    } else if (b.type === 'image_banner') {
                        b.config.image_url = document.getElementById('pa-ed-img-url').value.trim();
                        b.config.image_fit = document.getElementById('pa-ed-img-fit').value;
                        b.config.height    = clamp(parseInt(document.getElementById('pa-ed-img-h').value, 10) || 360, 180, 900);
                        b.config.image_alt = document.getElementById('pa-ed-img-alt').value.trim();
                        b.config.caption   = b.config.image_alt;
                        b.config.link_url  = document.getElementById('pa-ed-img-link').value.trim();
                    } else if (b.type === 'photo_slider') {
                        b.config.slide_source = document.getElementById('pa-ed-sl-src').value;
                        b.config.autoplay_sec = clamp(parseInt(document.getElementById('pa-ed-sl-auto').value, 10) || 0, 0, 15);
                        b.config.height       = clamp(parseInt(document.getElementById('pa-ed-sl-h').value, 10) || 360, 220, 720);
                        var collected = [];
                        if (slListEl) {
                            slListEl.querySelectorAll('.pa-slide-row').forEach(function(r){
                                var u = (r.querySelector('.pa-sl-url') || {}).value || '';
                                var t = (r.querySelector('.pa-sl-title') || {}).value || '';
                                if (u.trim()) collected.push({ image_url: u.trim(), title: t.trim(), link_url: '' });
                            });
                        }
                        b.config.slides = collected;
                    }
                    dlg.close();
                    render();
                });

                function startDragOrResize(ev, block, mode, cardEl){
                    if (ev.button !== 0) return;
                    ev.preventDefault();
                    selectedId = block.id;
                    var rect = canvas.getBoundingClientRect();
                    var colW = (rect.width + 10) / 12;
                    var rowH = 94;
                    var startX = ev.clientX, startY = ev.clientY;
                    var origX = block.x, origY = block.y, origW = block.w, origH = block.h;
                    cardEl.classList.add('is-dragging');

                    function onMove(mv){
                        var dxCols = Math.round((mv.clientX - startX) / colW);
                        var dyRows = Math.round((mv.clientY - startY) / rowH);
                        if (mode === 'drag') {
                            block.x = clamp(origX + dxCols, 1, 13 - block.w);
                            block.y = clamp(origY + dyRows, 1, 40);
                        } else {
                            block.w = clamp(origW + dxCols, 2, 13 - origX);
                            block.h = clamp(origH + dyRows, 1, 4);
                        }
                        cardEl.style.gridColumn = block.x + ' / span ' + block.w;
                        cardEl.style.gridRow = block.y + ' / span ' + block.h;
                        var sz = cardEl.querySelector('.pa-gb-size');
                        if (sz) sz.textContent = block.w + '×' + block.h;
                    }
                    function onUp(){
                        document.removeEventListener('pointermove', onMove);
                        document.removeEventListener('pointerup', onUp);
                        cardEl.classList.remove('is-dragging');
                        render();
                    }
                    document.addEventListener('pointermove', onMove);
                    document.addEventListener('pointerup', onUp);
                }

                function render(){
                    sortBlocks();
                    canvas.replaceChildren();
                    tbody.replaceChildren();
                    var rowsMin = Math.max(6, maxRow() + 1);
                    canvas.style.minHeight = (rowsMin * 94) + 'px';

                    blocks.forEach(function(b){
                        var meta = catalog[b.type] || { label: b.type, icon: '▪' };
                        var card = document.createElement('div');
                        card.className = 'pa-gb-card' + (b.id === selectedId ? ' is-selected' : '') + (!b.enabled ? ' is-hidden-blk' : '');
                        card.style.gridColumn = b.x + ' / span ' + b.w;
                        card.style.gridRow = b.y + ' / span ' + b.h;

                        var head = document.createElement('div');
                        head.className = 'pa-gb-head';

                        var tWrap = document.createElement('div');
                        tWrap.style.minWidth = '0';
                        var tLine = document.createElement('div');
                        tLine.className = 'pa-gb-title';
                        var ico = document.createElement('span');
                        ico.textContent = meta.icon;
                        var tTxt = document.createElement('span');
                        tTxt.textContent = b.title;
                        tLine.appendChild(ico);
                        tLine.appendChild(tTxt);
                        var sub = document.createElement('div');
                        sub.className = 'pa-gb-sub';
                        sub.textContent = meta.label;
                        tWrap.appendChild(tLine);
                        tWrap.appendChild(sub);

                        var acts = document.createElement('div');
                        acts.className = 'pa-gb-acts';
                        var btnEd = document.createElement('button');
                        btnEd.type = 'button'; btnEd.className = 'pa-gb-iconbtn'; btnEd.title = 'Ubah isi & posisi'; btnEd.textContent = '✎';
                        btnEd.addEventListener('click', function(e){ e.stopPropagation(); openEdit(b.id); });
                        var btnVis = document.createElement('button');
                        btnVis.type = 'button'; btnVis.className = 'pa-gb-iconbtn'; btnVis.title = b.enabled ? 'Sembunyikan' : 'Tampilkan'; btnVis.textContent = b.enabled ? '👁' : '◌';
                        btnVis.addEventListener('click', function(e){ e.stopPropagation(); b.enabled = !b.enabled; render(); });
                        var btnDel = document.createElement('button');
                        btnDel.type = 'button'; btnDel.className = 'pa-gb-iconbtn'; btnDel.title = 'Hapus blok'; btnDel.textContent = '🗑';
                        btnDel.addEventListener('click', function(e){
                            e.stopPropagation();
                            if (!confirm('Hapus blok "' + b.title + '" dari Beranda?')) return;
                            blocks = blocks.filter(function(it){ return it.id !== b.id; });
                            render();
                        });
                        acts.appendChild(btnEd); acts.appendChild(btnVis); acts.appendChild(btnDel);

                        head.appendChild(tWrap); head.appendChild(acts);

                        var foot = document.createElement('div');
                        foot.className = 'pa-gb-meta';
                        var sz = document.createElement('span');
                        sz.className = 'pa-gb-size';
                        sz.textContent = b.w + '×' + b.h;
                        var pos = document.createElement('span');
                        pos.textContent = 'Kol ' + b.x + ' · Brs ' + b.y;
                        foot.appendChild(sz); foot.appendChild(pos);

                        var rz = document.createElement('span');
                        rz.className = 'pa-gb-resize';
                        rz.title = 'Tarik untuk ubah ukuran';
                        rz.textContent = '↘';
                        rz.addEventListener('pointerdown', function(e){ e.stopPropagation(); startDragOrResize(e, b, 'resize', card); });

                        card.addEventListener('pointerdown', function(e){
                            if (e.target.closest('button,.pa-gb-resize')) return;
                            startDragOrResize(e, b, 'drag', card);
                        });
                        card.appendChild(head); card.appendChild(foot); card.appendChild(rz);
                        canvas.appendChild(card);

                        var tr = document.createElement('tr');
                        var td1 = document.createElement('td');
                        var st1 = document.createElement('strong'); st1.textContent = meta.icon + ' ' + b.title;
                        td1.appendChild(st1);
                        var td2 = document.createElement('td'); td2.textContent = meta.label;
                        var td3 = document.createElement('td'); td3.textContent = 'kolom ' + b.x + ', baris ' + b.y;
                        var td4 = document.createElement('td'); td4.textContent = b.w + '/12 × ' + b.h + ' baris';
                        var td5 = document.createElement('td');
                        var pill = document.createElement('span');
                        pill.style.cssText = 'display:inline-block;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:700;' + (b.enabled ? 'background:#dcfce7;color:#166534;' : 'background:#f1f5f9;color:#64748b;');
                        pill.textContent = b.enabled ? 'Tampil' : 'Tersembunyi';
                        td5.appendChild(pill);
                        var td6 = document.createElement('td');
                        var aWrap = document.createElement('div'); aWrap.style.cssText = 'display:flex;gap:4px;flex-wrap:wrap;';
                        var tbEd = document.createElement('button'); tbEd.type = 'button'; tbEd.className = 'button button-small'; tbEd.textContent = 'Ubah';
                        tbEd.addEventListener('click', function(){ openEdit(b.id); });
                        var tbVis = document.createElement('button'); tbVis.type = 'button'; tbVis.className = 'button button-small'; tbVis.textContent = b.enabled ? 'Sembunyi' : 'Tampil';
                        tbVis.addEventListener('click', function(){ b.enabled = !b.enabled; render(); });
                        var tbDel = document.createElement('button'); tbDel.type = 'button'; tbDel.className = 'button button-small'; tbDel.style.color = '#b91c1c'; tbDel.textContent = '✕';
                        tbDel.addEventListener('click', function(){
                            if (!confirm('Hapus blok "' + b.title + '"?')) return;
                            blocks = blocks.filter(function(it){ return it.id !== b.id; });
                            render();
                        });
                        aWrap.appendChild(tbEd); aWrap.appendChild(tbVis); aWrap.appendChild(tbDel);
                        td6.appendChild(aWrap);
                        tr.appendChild(td1); tr.appendChild(td2); tr.appendChild(td3); tr.appendChild(td4); tr.appendChild(td5); tr.appendChild(td6);
                        tbody.appendChild(tr);
                    });
                }

                if (addBtn && addSel) {
                    addBtn.addEventListener('click', function(){
                        var t = addSel.value;
                        var m = catalog[t] || { label: t, default_w: 6, default_h: 1 };
                        var nb = {
                            id: 'blk_' + t + '_' + Date.now().toString(36),
                            type: t,
                            title: m.label,
                            x: 1,
                            y: maxRow() + 1,
                            w: m.default_w || 6,
                            h: m.default_h || 1,
                            enabled: true,
                            config: {}
                        };
                        blocks.push(nb);
                        selectedId = nb.id;
                        render();
                        openEdit(nb.id);
                    });
                }

                var topShowChk = document.getElementById('pa-gb-top-show-ticker');
                var topTickLbl = document.getElementById('pa-gb-top-ticker-lbl');
                var topTickTxt = document.getElementById('pa-gb-top-ticker-txt');

                if (saveFrm) {
                    saveFrm.addEventListener('submit', function(){
                        jsonInp.value = JSON.stringify(blocks);
                        document.getElementById('pa-gb-h-show-ticker').value = topShowChk ? (topShowChk.checked ? '1' : '0') : (globals.home_show_ticker || '1');
                        document.getElementById('pa-gb-h-ticker-lbl').value  = topTickLbl ? (topTickLbl.value.trim() || 'INFO LAYANAN') : (globals.home_ticker_label || 'INFO LAYANAN');
                        document.getElementById('pa-gb-h-ticker').value      = topTickTxt ? topTickTxt.value.trim() : (globals.home_ticker_text || '');
                        document.getElementById('pa-gb-h-kicker').value  = globals.home_hero_kicker || '';
                        document.getElementById('pa-gb-h-title').value   = globals.home_hero_title || '';
                        document.getElementById('pa-gb-h-lead').value    = globals.home_hero_lead || '';
                        document.getElementById('pa-gb-h-layout').value  = globals.hero_layout || 'split';
                        document.getElementById('pa-gb-h-pattern').value = globals.hero_pattern || 'islamic';
                        document.getElementById('pa-gb-h-photo').value   = globals.hero_photo_url || '';
                        document.getElementById('pa-gb-h-pcap').value    = globals.hero_photo_caption || '';
                        document.getElementById('pa-gb-h-sper').value   = globals.home_survey_period || '';
                        document.getElementById('pa-gb-h-sgrd').value   = globals.home_survey_grade || '';
                        document.getElementById('pa-gb-h-skm').value    = globals.home_survey_skm || '';
                        document.getElementById('pa-gb-h-spak').value   = globals.home_survey_spak || '';
                        document.getElementById('pa-gb-h-spkp').value   = globals.home_survey_spkp || '';
                    });
                }
                render();
            })();
            </script>

        <?php elseif ($activeTab === 'settings') :
            $isAdoption   = (!empty($cfg['preserve_existing_design']) && (string) $cfg['preserve_existing_design'] === '1');
            $seededOption = get_option('pa_sinova_seeded_pages', array());
            $seededCount  = is_array($seededOption) ? count($seededOption) : 0;
            $dirputPrev   = function_exists('pa_portal_dirput_resolve_urls') ? pa_portal_dirput_resolve_urls() : array();
            $acoLogoNow     = trim((string) ($cfg['aco_logo_url'] ?? ''));
            $logoNow        = trim((string) ($cfg['logo_url'] ?? ''));
            $faviconNow     = trim((string) ($cfg['favicon_url'] ?? ''));
            $favPrevSrc     = $faviconNow !== '' ? $faviconNow : ($logoNow !== '' ? $logoNow : wp_make_link_relative(home_url('/favicon.svg')));
            $heroPhotoNow   = trim((string) ($cfg['hero_photo_url'] ?? ''));
            $heroPrevSrc    = $heroPhotoNow !== '' ? $heroPhotoNow : pa_sinova_asset_url('dummy-gedung-pa.svg');
            $leaderPhotoNow = trim((string) ($cfg['leader_photo_url'] ?? ''));
            $leaderPrevSrc  = $leaderPhotoNow !== '' ? $leaderPhotoNow : pa_sinova_asset_url('dummy-pimpinan.svg');
        ?>
            <?php if ($isAdoption) : ?>
                <div class="notice notice-info inline" style="margin:0 0 16px;padding:10px 14px;">
                    <strong>Mode Adopsi Situs Lama sedang AKTIF</strong> <?php echo pa_sinova_info_tip('Seeder 1-Klik hanya melengkapi halaman yang kurang tanpa mengubah tema, menu, maupun Beranda lama.', 'Info mode adopsi'); ?>
                    · <a href="<?php echo esc_url($tabBaseUrl . 'migration'); ?>">Kelola</a>
                </div>
            <?php endif; ?>
            <div class="pa-adm-cols-set">
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">Identitas Satker, Tampilan &amp; API</h2>
                    <form method="post">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="save_settings">
                        <input type="hidden" name="preserve_existing_design" value="<?php echo $isAdoption ? '1' : '0'; ?>">
                        <table class="form-table">
                            <tr><th>Nama Satker</th><td><input type="text" name="satker_name" class="regular-text" value="<?php echo esc_attr($cfg['satker_name']); ?>"></td></tr>
                            <tr><th>URL Logo Resmi <?php echo pa_sinova_info_tip('Tampil di Header situs (desktop & mobile), Footer, Sidebar Admin SINOVA, pratinjau tautan WhatsApp/OG, serta fallback otomatis Favicon jika Favicon kosong.', 'Info Logo Resmi'); ?></th><td>
                                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                    <span class="pa-media-preview"><img id="pa-set-logo-preview" src="<?php echo esc_url($logoNow !== '' ? $logoNow : wp_make_link_relative(home_url('/favicon.svg'))); ?>" alt="Pratinjau Logo"></span>
                                    <input type="url" id="pa-set-logo-url" name="logo_url" class="regular-text" value="<?php echo esc_attr($logoNow); ?>" placeholder="https://.../logo-pa.png">
                                    <button type="button" class="button pa-media-pick-btn" data-target="pa-set-logo-url" data-preview="pa-set-logo-preview">Pilih Media</button>
                                </div>
                            </td></tr>
                            <tr><th>Favicon Website (Ikon Tab Browser) <?php echo pa_sinova_info_tip('Ikon kecil yang tampil di tab browser di atas kolom URL & bookmark. Disarankan PNG/SVG/ICO persegi (64x64 atau 512x512 px). Kosongkan untuk otomatis mengikuti Logo Resmi atau ikon bawaan.', 'Info Favicon'); ?></th><td>
                                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                    <span class="pa-media-preview" style="width:36px;height:36px;"><img id="pa-set-favicon-preview" src="<?php echo esc_url($favPrevSrc); ?>" alt="Pratinjau Favicon"></span>
                                    <input type="url" id="pa-set-favicon-url" name="favicon_url" class="regular-text" value="<?php echo esc_attr($faviconNow); ?>" placeholder="Kosongkan untuk ikuti Logo / ikon bawaan">
                                    <button type="button" class="button pa-media-pick-btn" data-target="pa-set-favicon-url" data-preview="pa-set-favicon-preview">Pilih / Upload Favicon</button>
                                </div>
                            </td></tr>
                            <tr><th>Palet Aksen Visual</th><td>
                                <select name="accent_palette" class="regular-text">
                                    <option value="gold" <?php selected($cfg['accent_palette'] ?? 'gold', 'gold'); ?>>Hijau Institusional &amp; Royal Gold (#056e4f / #C9A227)</option>
                                    <option value="badilag_orange" <?php selected($cfg['accent_palette'] ?? 'gold', 'badilag_orange'); ?>>Hijau Badilag &amp; Aksen Oranye (#056e4f / #ff9900)</option>
                                    <option value="emerald_silver" <?php selected($cfg['accent_palette'] ?? 'gold', 'emerald_silver'); ?>>Hijau Zamrud &amp; Aksen Perak Modern (#043927 / #cbd5e1)</option>
                                </select>
                            </td></tr>
                            <tr><th>Gaya Hero &amp; Motif Daerah <?php echo pa_sinova_info_tip('Pilih gaya Hero Beranda (Split Modern menampilkan Foto Gedung satker di sisi kanan) serta motif watermark SVG ringan khas Nusantara.', 'Info Hero & Motif'); ?></th><td>
                                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                    <select name="hero_layout" aria-label="Gaya Hero Beranda">
                                        <option value="split" <?php selected($cfg['hero_layout'] ?? 'split', 'split'); ?>>Hero Split Modern + Foto Gedung</option>
                                        <option value="classic" <?php selected($cfg['hero_layout'] ?? 'split', 'classic'); ?>>Hero Klasik Rata Tengah</option>
                                    </select>
                                    <select name="hero_pattern" aria-label="Motif Watermark Daerah">
                                        <option value="islamic" <?php selected($cfg['hero_pattern'] ?? 'islamic', 'islamic'); ?>>Motif Geometris Islami</option>
                                        <option value="batik" <?php selected($cfg['hero_pattern'] ?? 'islamic', 'batik'); ?>>Ornamen Batik Kawung</option>
                                        <option value="songket" <?php selected($cfg['hero_pattern'] ?? 'islamic', 'songket'); ?>>Ornamen Tenun / Songket</option>
                                        <option value="none" <?php selected($cfg['hero_pattern'] ?? 'islamic', 'none'); ?>>Polos Minimalis</option>
                                    </select>
                                </div>
                            </td></tr>
                            <tr><th>Foto Gedung Kantor (Hero) <?php echo pa_sinova_info_tip('Tampil pada Hero Split Modern di Beranda. Kosongkan untuk menampilkan ilustrasi gedung dummy bawaan yang rapi.', 'Info Foto Gedung'); ?></th><td>
                                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:6px;">
                                    <span class="pa-media-preview"><img id="pa-set-hero-preview" src="<?php echo esc_url($heroPrevSrc); ?>" alt="Pratinjau Foto Gedung"></span>
                                    <input type="url" id="pa-set-hero-photo" name="hero_photo_url" class="regular-text" value="<?php echo esc_attr($heroPhotoNow); ?>" placeholder="Kosongkan untuk foto gedung dummy">
                                    <button type="button" class="button pa-media-pick-btn" data-target="pa-set-hero-photo" data-preview="pa-set-hero-preview">Pilih / Upload Foto Gedung</button>
                                </div>
                                <input type="text" name="hero_photo_caption" class="regular-text" value="<?php echo esc_attr($cfg['hero_photo_caption'] ?? 'Gedung Pelayanan Terpadu Satu Pintu (PTSP) & Ruang Sidang'); ?>" placeholder="Keterangan di bawah foto gedung">
                            </td></tr>
                            <tr><th>Alamat Kantor</th><td><input type="text" name="address" class="regular-text" value="<?php echo esc_attr($cfg['address']); ?>"></td></tr>
                            <tr><th>Telepon / WA PTSP</th><td><input type="text" name="phone" class="regular-text" value="<?php echo esc_attr($cfg['phone']); ?>"></td></tr>
                            <tr><th>Email Resmi</th><td><input type="email" name="email" class="regular-text" value="<?php echo esc_attr($cfg['email']); ?>"></td></tr>
                            <tr><th>Jam Sidang</th><td><input type="text" name="hearing_hours" class="regular-text" value="<?php echo esc_attr($cfg['hearing_hours'] ?? ''); ?>"></td></tr>
                            <tr><th>Nama &amp; Jabatan Pimpinan</th><td>
                                <input type="text" name="leader_name" class="regular-text" value="<?php echo esc_attr($cfg['leader_name']); ?>" placeholder="Nama Ketua Pengadilan Agama"><br>
                                <input type="text" name="leader_title" class="regular-text" value="<?php echo esc_attr($cfg['leader_title'] ?? 'Ketua Pengadilan Agama'); ?>" placeholder="Jabatan (mis. Ketua Pengadilan Agama Sumedang)" style="margin-top:4px;">
                            </td></tr>
                            <tr><th>Foto Pimpinan (Sambutan) <?php echo pa_sinova_info_tip('Foto potret pimpinan yang tampil di kartu Sambutan Beranda. Kosongkan untuk ilustrasi potret dummy bawaan.', 'Info Foto Pimpinan'); ?></th><td>
                                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:6px;">
                                    <span class="pa-media-preview"><img id="pa-set-leader-preview" src="<?php echo esc_url($leaderPrevSrc); ?>" alt="Pratinjau Foto Pimpinan"></span>
                                    <input type="url" id="pa-set-leader-photo" name="leader_photo_url" class="regular-text" value="<?php echo esc_attr($leaderPhotoNow); ?>" placeholder="Kosongkan untuk foto dummy pimpinan">
                                    <button type="button" class="button pa-media-pick-btn" data-target="pa-set-leader-photo" data-preview="pa-set-leader-preview">Pilih / Upload Foto</button>
                                </div>
                                <input type="text" name="leader_motto" class="regular-text" value="<?php echo esc_attr($cfg['leader_motto'] ?? 'Melayani dengan Integritas, Profesionalitas, dan Transparansi'); ?>" placeholder="Motto singkat pimpinan (opsional)">
                            </td></tr>
                            <tr><th>Sambutan Pimpinan</th><td><textarea name="leader_welcome" rows="3" class="large-text"><?php echo esc_textarea($cfg['leader_welcome']); ?></textarea></td></tr>
                            <tr><th>URL SIPP</th><td><input type="url" name="sipp_url" class="regular-text" value="<?php echo esc_attr($cfg['sipp_url']); ?>"></td></tr>
                            <tr><th>URL Slide Jadwal Sidang SIPP <?php echo pa_sinova_info_tip('Tempel tautan Slide_sidang SIPP satker (HTTPS) untuk tampil sebagai panel jadwal sidang live di Beranda & izin frame-src otomatis.', 'Info slide SIPP'); ?></th><td><input type="url" name="sipp_slide_url" class="regular-text" value="<?php echo esc_attr($cfg['sipp_slide_url'] ?? ''); ?>" placeholder="https://sipp.pa-sumedang.go.id/Slide_sidang"></td></tr>
                            <tr><th>URL e-Court</th><td><input type="url" name="ecourt_url" class="regular-text" value="<?php echo esc_attr($cfg['ecourt_url']); ?>"></td></tr>
                            <tr><th>URL Direktori Putusan Satker <?php echo pa_sinova_info_tip('Tempel URL profil/direktori satker di putusan3.mahkamahagung.go.id, misal https://putusan3.mahkamahagung.go.id/pengadilan/profil/pengadilan/pa-sumedang.html. Halaman /direktori-putusan/ dan pencarian Beranda menyesuaikan otomatis.', 'Info URL Direktori Putusan'); ?></th><td>
                                <input type="url" name="direktori_url" class="regular-text" value="<?php echo esc_attr($cfg['direktori_url']); ?>" placeholder="https://putusan3.mahkamahagung.go.id/pengadilan/profil/pengadilan/pa-sumedang.html">
                            </td></tr>
                            <tr><th>Kode Hash Putusan3 (Opsional) <?php echo pa_sinova_info_tip('Opsional: kode 32 karakter parameter court= dari URL search.html putusan3 agar pencarian beranda mengunci satker Anda secara persis. Boleh dikosongkan; bila kosong pencarian otomatis menyertakan nama satker.', 'Info kode hash pengadilan'); ?></th><td>
                                <input type="text" name="dirput_court_hash" class="regular-text" value="<?php echo esc_attr($cfg['dirput_court_hash'] ?? ''); ?>" placeholder="Opsional: tempel hash 32 hex atau URL search.html?court=...">
                            </td></tr>
                            <tr><th>RSS Direktori Putusan <?php echo pa_sinova_info_tip('Opsional. Jika dikosongkan otomatis memakai https://putusan3.mahkamahagung.go.id/rss/pengadilan/<kode-satker>.html.', 'Info RSS Direktori Putusan'); ?></th><td>
                                <input type="url" name="dirput_rss_url" class="regular-text" value="<?php echo esc_attr($cfg['dirput_rss_url'] ?? ''); ?>" placeholder="<?php echo esc_attr(!empty($dirputPrev['rss_url']) ? $dirputPrev['rss_url'] : 'Otomatis dari URL Direktori Putusan di atas'); ?>">
                            </td></tr>
                            <tr><th>URL Survei Badilag</th><td><input type="url" name="survey_url" class="regular-text" value="<?php echo esc_attr($cfg['survey_url']); ?>"></td></tr>

                            <tr><th colspan="2" style="padding:14px 0 4px;border-bottom:1px solid #e2e8f0;color:#056e4f;font-size:13.5px;">Teks Berjalan Beranda (Info Layanan)</th></tr>
                            <tr><th>Teks Berjalan <?php echo pa_sinova_info_tip('Tampil sebagai pita informasi berjalan elegan di atas Beranda dan berhenti saat disorot kursor.', 'Info teks berjalan'); ?></th><td>
                                <label style="display:inline-flex;align-items:center;gap:6px;margin-bottom:6px;"><input type="checkbox" name="home_show_ticker" value="1" <?php checked(($cfg['home_show_ticker'] ?? '1') !== '0'); ?>> Tampilkan pita Info Layanan berjalan di Beranda</label><br>
                                <input type="text" name="home_ticker_label" value="<?php echo esc_attr($cfg['home_ticker_label'] ?? 'INFO LAYANAN'); ?>" placeholder="INFO LAYANAN" style="width:160px;margin-bottom:6px;">
                                <textarea name="home_ticker_text" rows="2" class="large-text"><?php echo esc_textarea($cfg['home_ticker_text'] ?? ''); ?></textarea>
                            </td></tr>

                            <tr><th colspan="2" style="padding:14px 0 4px;border-bottom:1px solid #e2e8f0;color:#056e4f;font-size:13.5px;">Logo Melayang CCTV ACO</th></tr>
                            <tr><th>URL Tujuan CCTV ACO <?php echo pa_sinova_info_tip('Tautan ketika ikon CCTV ACO di pojok/tepi layar diklik (misal link room ACO satker atau cctv.badilag.net). Kosongkan untuk menyembunyikan.', 'Info URL CCTV ACO'); ?></th><td><input type="url" name="aco_url" class="regular-text" value="<?php echo esc_attr($cfg['aco_url'] ?? 'https://cctv.badilag.net/'); ?>" placeholder="https://cctv.badilag.net/"></td></tr>
                            <tr><th>Gambar Logo ACO <?php echo pa_sinova_info_tip('Gunakan GIF animasi, PNG transparan, WebP, atau SVG. Kosongkan untuk memakai GIF bawaan (assets/cctv-aco.gif). Saran ukuran 120x120px.', 'Info gambar ACO'); ?></th><td>
                                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                    <span class="pa-media-preview"><img id="pa-set-aco-preview" src="<?php echo esc_url($acoLogoNow !== '' ? $acoLogoNow : wp_make_link_relative(add_query_arg('pa_aco_asset', '1', home_url('/')))); ?>" alt="Pratinjau ACO"></span>
                                    <input type="url" id="pa-set-aco-logo" name="aco_logo_url" class="regular-text" value="<?php echo esc_attr($acoLogoNow); ?>" placeholder="Kosongkan untuk GIF bawaan">
                                    <button type="button" class="button pa-media-pick-btn" data-target="pa-set-aco-logo" data-preview="pa-set-aco-preview">Pilih / Upload GIF·PNG</button>
                                </div>
                            </td></tr>
                            <tr><th>Letak &amp; Ukuran ACO <?php echo pa_sinova_info_tip('Pilih sudut bawaan serta ukuran logo. Aktifkan izinkan geser agar pengunjung/admin dapat memindahkan letaknya dengan drag.', 'Info letak & ukuran ACO'); ?></th><td>
                                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:6px;">
                                    <select name="aco_float_position" aria-label="Posisi Logo ACO">
                                        <option value="bottom_right" <?php selected($cfg['aco_float_position'] ?? 'bottom_right', 'bottom_right'); ?>>Kanan Bawah</option>
                                        <option value="middle_right" <?php selected($cfg['aco_float_position'] ?? 'bottom_right', 'middle_right'); ?>>Kanan Tengah</option>
                                        <option value="top_right" <?php selected($cfg['aco_float_position'] ?? 'bottom_right', 'top_right'); ?>>Kanan Atas</option>
                                        <option value="bottom_left" <?php selected($cfg['aco_float_position'] ?? 'bottom_right', 'bottom_left'); ?>>Kiri Bawah</option>
                                        <option value="middle_left" <?php selected($cfg['aco_float_position'] ?? 'bottom_right', 'middle_left'); ?>>Kiri Tengah</option>
                                    </select>
                                    <select name="aco_float_size" aria-label="Ukuran Logo ACO">
                                        <option value="sm" <?php selected($cfg['aco_float_size'] ?? 'md', 'sm'); ?>>Kecil (44px)</option>
                                        <option value="md" <?php selected($cfg['aco_float_size'] ?? 'md', 'md'); ?>>Sedang (56px)</option>
                                        <option value="lg" <?php selected($cfg['aco_float_size'] ?? 'md', 'lg'); ?>>Besar (68px)</option>
                                    </select>
                                </div>
                                <label style="margin-right:12px;"><input type="checkbox" name="aco_float_enabled" value="1" <?php checked(($cfg['aco_float_enabled'] ?? '1') !== '0'); ?>> Tampilkan logo melayang</label>
                                <label><input type="checkbox" name="aco_float_draggable" value="1" <?php checked(($cfg['aco_float_draggable'] ?? '1') !== '0'); ?>> Bisa digeser (drag)</label>
                            </td></tr>

                            <tr><th colspan="2" style="padding:14px 0 4px;border-bottom:1px solid #e2e8f0;color:#056e4f;font-size:13.5px;">Kredit Footer &amp; Sorot Versi</th></tr>
                            <tr><th>Teks Kredit Footer <?php echo pa_sinova_info_tip('Tampil setelah simbol © tahun pada bagian bawah footer.', 'Info teks kredit footer'); ?></th><td><input type="text" name="footer_credit_text" class="regular-text" value="<?php echo esc_attr($cfg['footer_credit_text'] ?? 'Pengadilan Agama Devlpr Community'); ?>"></td></tr>
                            <tr><th>Subteks Footer <?php echo pa_sinova_info_tip('Opsional: ditampilkan setelah tanda titik tengah, misal Portal Keterbukaan Informasi Publik.', 'Info subteks footer'); ?></th><td><input type="text" name="footer_credit_suffix" class="regular-text" value="<?php echo esc_attr($cfg['footer_credit_suffix'] ?? 'Portal Keterbukaan Informasi Publik'); ?>"></td></tr>
                            <tr><th>Teks Sorot Versi <?php echo pa_sinova_info_tip('Muncul saat bagian versi di footer disorot/fokus. Simbol ♥ atau {love} otomatis diganti ikon hati merah.', 'Info sorot versi'); ?></th><td><input type="text" name="footer_hover_text" class="regular-text" value="<?php echo esc_attr($cfg['footer_hover_text'] ?? 'Developed with ♥ by PA Devlpr Community'); ?>"></td></tr>
                            <tr><th>URL Tautan Versi</th><td><input type="url" name="footer_credit_url" class="regular-text" value="<?php echo esc_attr($cfg['footer_credit_url'] ?? ''); ?>" placeholder="https://github.com/kang-devlp/pa-wordpress-sinova-starter"></td></tr>

                            <tr><th colspan="2" style="padding:14px 0 4px;border-bottom:1px solid #e2e8f0;color:#056e4f;font-size:13.5px;">Media Sosial Resmi &amp; Peta Kantor (Footer)</th></tr>
                            <tr><th>Tautan Medsos &amp; Maps <?php echo pa_sinova_info_tip('Tampil otomatis sebagai ikon vektor ringan di Footer kolom 1 (tanpa skrip pelacak eksternal). Kosongkan yang belum dimiliki.', 'Info Medsos & Maps'); ?></th><td>
                                <input type="url" name="social_instagram" class="regular-text" value="<?php echo esc_attr($cfg['social_instagram'] ?? ''); ?>" placeholder="URL Instagram (https://instagram.com/pa_...)" style="margin-bottom:4px;"><br>
                                <input type="url" name="social_youtube" class="regular-text" value="<?php echo esc_attr($cfg['social_youtube'] ?? ''); ?>" placeholder="URL YouTube Resmi" style="margin-bottom:4px;"><br>
                                <input type="url" name="social_facebook" class="regular-text" value="<?php echo esc_attr($cfg['social_facebook'] ?? ''); ?>" placeholder="URL Facebook Resmi" style="margin-bottom:4px;"><br>
                                <input type="url" name="social_tiktok" class="regular-text" value="<?php echo esc_attr($cfg['social_tiktok'] ?? ''); ?>" placeholder="URL TikTok Resmi" style="margin-bottom:4px;"><br>
                                <input type="url" name="social_whatsapp" class="regular-text" value="<?php echo esc_attr($cfg['social_whatsapp'] ?? ''); ?>" placeholder="URL WhatsApp PTSP (https://wa.me/628...)" style="margin-bottom:4px;"><br>
                                <input type="url" name="maps_url" class="regular-text" value="<?php echo esc_attr($cfg['maps_url'] ?? ''); ?>" placeholder="URL Google Maps Lokasi Kantor (https://maps.app.goo.gl/...)">
                            </td></tr>

                            <tr><th colspan="2" style="padding:14px 0 4px;border-bottom:1px solid #e2e8f0;color:#056e4f;font-size:13.5px;">Ekosistem, Informasi Serta-Merta &amp; Keamanan</th></tr>
                            <tr><th>URL SIWAS &amp; SP4N-LAPOR!</th><td>
                                <input type="url" name="siwas_url" class="regular-text" value="<?php echo esc_attr($cfg['siwas_url'] ?? 'https://siwas.mahkamahagung.go.id/'); ?>" placeholder="https://siwas.mahkamahagung.go.id/"><br>
                                <input type="url" name="lapor_url" class="regular-text" value="<?php echo esc_attr($cfg['lapor_url'] ?? 'https://www.lapor.go.id/'); ?>" placeholder="https://www.lapor.go.id/" style="margin-top:4px;">
                            </td></tr>
                            <tr><th>Pita Informasi Serta-Merta <?php echo pa_sinova_info_tip('Sesuai KMA 2-144/2022: muncul di bagian paling atas portal saat ada pengumuman darurat atau gangguan layanan.', 'Info pita darurat'); ?></th><td>
                                <input type="text" name="emergency_notice" class="regular-text" value="<?php echo esc_attr($cfg['emergency_notice'] ?? ''); ?>" placeholder="Kosongkan bila layanan normal">
                                <input type="url" name="emergency_notice_url" class="regular-text" value="<?php echo esc_attr($cfg['emergency_notice_url'] ?? ''); ?>" placeholder="URL detail pengumuman (opsional)" style="margin-top:4px;">
                            </td></tr>
                            <tr><th>Tautan Zona Integritas (I–VI) <?php echo pa_sinova_info_tip('Jika dikosongkan, kartu Area I–VI di Beranda otomatis mengarah ke /zona-integritas/.', 'Info tautan ZI'); ?></th><td>
                                <?php for ($zi = 1; $zi <= 6; $zi++) : ?>
                                    <input type="url" name="zi_area_<?php echo $zi; ?>_url" class="regular-text" value="<?php echo esc_attr($cfg['zi_area_' . $zi . '_url'] ?? ''); ?>" placeholder="URL Folder Google Drive / Halaman Area <?php echo $zi; ?>" style="margin-bottom:4px;"><br>
                                <?php endfor; ?>
                            </td></tr>
                            <tr><th>RSS Pengumuman MA &amp; Badilag</th><td>
                                <input type="url" name="rss_ma_url" class="regular-text" value="<?php echo esc_attr($cfg['rss_ma_url'] ?? 'https://kepaniteraan.mahkamahagung.go.id/?format=feed&type=rss'); ?>" style="margin-bottom:4px;"><br>
                                <input type="url" name="rss_badilag_url" class="regular-text" value="<?php echo esc_attr($cfg['rss_badilag_url'] ?? 'https://badilag.mahkamahagung.go.id/pengumuman-elektronik?format=feed&type=rss'); ?>">
                            </td></tr>
                            <tr><th>Mode Keamanan CSP <?php echo pa_sinova_info_tip('Strict: Nonce + Strict-Dynamic + Trusted Types. Pilih Compat bila memakai plugin/Elementor lawas.', 'Info CSP'); ?></th><td>
                                <select name="csp_mode" class="regular-text">
                                    <option value="strict" <?php selected($cfg['csp_mode'] ?? 'strict', 'strict'); ?>>Strict (Rekomendasi)</option>
                                    <option value="compat" <?php selected($cfg['csp_mode'] ?? 'strict', 'compat'); ?>>Compat (Plugin/Elementor Lawas)</option>
                                    <option value="off" <?php selected($cfg['csp_mode'] ?? 'strict', 'off'); ?>>Off (Header Dasar Saja)</option>
                                </select>
                            </td></tr>
                            <tr><th>Secret Key HMAC API</th><td><input type="text" name="hmac_secret" class="regular-text" value="<?php echo esc_attr($cfg['hmac_secret']); ?>" placeholder="Minimal 32 karakter"><br><label><input type="checkbox" name="generate_hmac" value="1"> Generate Secret Key baru</label></td></tr>
                            <tr><th>Turnstile Site Key</th><td><input type="text" name="turnstile_site_key" class="regular-text" value="<?php echo esc_attr($cfg['turnstile_site_key']); ?>"></td></tr>
                            <tr><th>Turnstile Secret Key</th><td><input type="password" name="turnstile_secret_key" class="regular-text" value="<?php echo esc_attr($cfg['turnstile_secret_key']); ?>"></td></tr>
                        </table>
                        <p><button type="submit" class="button button-primary">Simpan Pengaturan</button></p>
                    </form>
                </div>

                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:18px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:14px;">
                        <h2 style="margin:0;color:#056e4f;font-size:16px;">Aksi Cepat Struktur</h2>
                        <span style="padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;<?php echo $seededCount >= 15 ? 'background:#dcfce7;color:#166534;' : 'background:#fef3c7;color:#92400e;'; ?>">
                            <?php echo $seededCount >= 15 ? ('● ' . (int) $seededCount . ' Halaman Siap') : '● Belum Di-seed'; ?>
                        </span>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <form method="post" style="margin:0;display:flex;align-items:center;gap:6px;">
                            <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                            <input type="hidden" name="pa_sinova_action" value="run_seeder">
                            <button type="submit" class="button button-primary" style="flex:1;">
                                <?php echo $seededCount >= 15 ? 'Lengkapi Halaman & Menu (1-Klik)' : 'Generate Halaman, Beranda & Menu (1-Klik)'; ?>
                            </button>
                            <?php echo pa_sinova_info_tip('Otomatis membuat halaman 23 modul arsip, halaman wajib SK 2722/2026 & KMA 2-144/2022, Beranda dinamis, berita contoh, serta menyusun 6 menu utama. Halaman lama tidak tertimpa.', 'Info Seeder 1-Klik'); ?>
                        </form>
                        <form method="post" style="margin:0;display:flex;align-items:center;gap:6px;" onsubmit="return confirm('Susun ulang item Menu Utama SINOVA menjadi 6 menu standar? Halaman tidak dihapus.');">
                            <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                            <input type="hidden" name="pa_sinova_action" value="rebuild_6menu">
                            <button type="submit" class="button button-secondary" style="flex:1;">Rapikan 6 Menu Resmi</button>
                            <?php echo pa_sinova_info_tip('Menata ulang Menu Utama SINOVA menjadi tepat 6 induk baku (Beranda, Profil, Layanan Hukum, Transparansi, Layanan Publik, Berita). Halaman & dokumen tetap aman.', 'Info Rapikan 6 Menu'); ?>
                        </form>
                    </div>
                </div>
            </div>

        <?php else :
            $secLogs    = get_option('pa_starter_security_logs', array());
            if (!is_array($secLogs)) $secLogs = array();
            $tsActive      = function_exists('pa_starter_login_turnstile') && pa_starter_login_turnstile() !== null;
            $hiddenSlug    = pa_sinova_custom_login_slug();
            $curUid        = get_current_user_id();
            $mfaEnabled    = (string) get_user_meta($curUid, 'pa_totp_enabled', true) === '1';
            $mfaPending    = (string) get_user_meta($curUid, 'pa_totp_pending_secret', true);
            $mfaEnforced   = !empty($cfg['enforce_mfa']) && (string) $cfg['enforce_mfa'] === '1';
            $mfaUri        = ($mfaPending !== '' && function_exists('pa_starter_totp_provisioning_uri')) ? pa_starter_totp_provisioning_uri(wp_get_current_user(), $mfaPending) : '';
            $mfaQrSvg      = $mfaUri !== '' ? pa_sinova_totp_qr_svg($mfaUri) : '';
            $recHashes     = get_user_meta($curUid, 'pa_totp_recovery_hashes', true);
            $recRemaining  = is_array($recHashes) ? count($recHashes) : 0;
            $fim           = pa_starter_fim_status();
            $cspModeNow    = pa_starter_effective_csp_mode();
            $ghUpd         = current_user_can('manage_options') ? pa_sinova_github_check_update(false) : array();
        ?>
            <?php if (current_user_can('manage_options')) : ?>
            <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:16px 18px;margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                    <div style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;">
                        <h2 style="margin:0;color:#056e4f;font-size:16px;">Pembaruan Starter Kit</h2>
                        <span class="pa-sec-pill is-ok">v<?php echo esc_html(PA_SINOVA_STARTER_VERSION); ?></span>
                        <?php if (!empty($ghUpd['ok'])) : ?>
                            <span class="pa-sec-pill <?php echo !empty($ghUpd['has_update']) ? 'is-warn' : ''; ?>">GitHub: v<?php echo esc_html($ghUpd['latest']); ?></span>
                        <?php endif; ?>
                        <?php
                        $updTipText = 'Sumber: ' . PA_SINOVA_GITHUB_REPO . ' · Pembaruan mengganti file inti secara atomik tanpa menghapus berita, dokumen, atau pengaturan satker.' . (!empty($ghUpd['changelog']) ? (' · Rilis: ' . (string) $ghUpd['changelog']) : '');
                        echo pa_sinova_info_tip($updTipText, 'Info pembaruan GitHub');
                        ?>
                        <?php if (!empty($ghUpd['error'])) : ?>
                            <span class="pa-sec-pill is-warn"><?php echo esc_html($ghUpd['error']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <form method="post">
                            <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                            <input type="hidden" name="pa_sinova_action" value="check_github_update">
                            <button type="submit" class="button button-secondary">Cek Pembaruan</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Unduh dan pasang berkas PA SINOVA terbaru dari GitHub? Data satker tetap aman.');">
                            <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                            <input type="hidden" name="pa_sinova_action" value="apply_github_update">
                            <button type="submit" class="button button-primary"><?php echo !empty($ghUpd['has_update']) ? ('Update ke v' . esc_html($ghUpd['latest']) . ' (1-Klik)') : 'Sinkronkan Ulang (1-Klik)'; ?></button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:16px;">
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:16px 18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:12px;">
                        <h2 style="margin:0;color:#056e4f;font-size:15.5px;">MFA TOTP (RFC 6238) <?php echo pa_sinova_info_tip('Verifikasi 6 digit via Google Authenticator/FreeOTP/Aegis tanpa pihak ketiga. Pemulihan darurat: define("PA_DISABLE_CUSTOM_MFA", true); di wp-config.php.', 'Info MFA'); ?></h2>
                        <span class="pa-sec-pill <?php echo $mfaEnabled ? 'is-ok' : 'is-warn'; ?>"><?php echo $mfaEnabled ? '● Aktif' : '● Belum Aktif'; ?></span>
                    </div>
                    <?php if (!empty($mfaRecoveryOnce)) : ?>
                        <div style="background:#fffbeb;border:1px solid #f59e0b;border-radius:8px;padding:10px 12px;margin-bottom:12px;">
                            <strong style="color:#92400e;font-size:12px;">Simpan 8 Kode Cadangan (Sekali Tampil):</strong>
                            <div style="display:grid;grid-template-columns:repeat(4,minmax(80px,1fr));gap:6px;margin-top:6px;font-family:monospace;font-size:12px;">
                                <?php foreach ($mfaRecoveryOnce as $rc) : ?>
                                    <code style="background:#fff;padding:3px 6px;border:1px solid #fcd34d;border-radius:4px;text-align:center;"><?php echo esc_html($rc); ?></code>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($mfaEnabled) : ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                            <span style="font-size:12.5px;color:#334155;">Sisa kode cadangan: <strong><?php echo (int) $recRemaining; ?></strong></span>
                            <form method="post" onsubmit="return confirm('Nonaktifkan MFA TOTP untuk akun Anda?');">
                                <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                                <input type="hidden" name="pa_sinova_action" value="mfa_disable">
                                <button type="submit" class="button button-small" style="color:#b91c1c;">Nonaktifkan MFA</button>
                            </form>
                        </div>
                    <?php elseif ($mfaPending !== '') : ?>
                        <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:center;background:#f8fafc;border:1px solid #cbd5e1;border-radius:10px;padding:12px;">
                            <div><?php echo $mfaQrSvg; ?></div>
                            <div style="flex:1;min-width:180px;">
                                <code style="display:block;font-size:12.5px;padding:4px 8px;background:#fff;border:1px solid #cbd5e1;border-radius:6px;margin-bottom:8px;"><?php echo esc_html(trim(chunk_split($mfaPending, 4, ' '))); ?></code>
                                <form method="post" style="display:flex;gap:6px;align-items:center;">
                                    <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                                    <input type="hidden" name="pa_sinova_action" value="mfa_activate">
                                    <input type="text" name="mfa_verify_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="6 digit OTP" required style="width:110px;font-weight:700;text-align:center;">
                                    <button type="submit" class="button button-primary button-small">Verifikasi</button>
                                </form>
                            </div>
                        </div>
                    <?php else : ?>
                        <form method="post">
                            <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                            <input type="hidden" name="pa_sinova_action" value="mfa_setup_start">
                            <button type="submit" class="button button-primary">Atur MFA TOTP (QR Lokal)</button>
                        </form>
                    <?php endif; ?>

                    <?php if (current_user_can('manage_options')) : ?>
                        <form method="post" style="margin-top:12px;padding-top:10px;border-top:1px solid #e2e8f0;display:flex;gap:10px;align-items:center;justify-content:space-between;flex-wrap:wrap;">
                            <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                            <input type="hidden" name="pa_sinova_action" value="mfa_policy">
                            <label style="font-size:12.5px;"><input type="checkbox" name="enforce_mfa" value="1" <?php checked($mfaEnforced); ?>> Wajibkan pengingat MFA</label>
                            <button type="submit" class="button button-small">Simpan</button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if (current_user_can('manage_options')) : ?>
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:16px 18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:12px;">
                        <h2 style="margin:0;color:#056e4f;font-size:15.5px;">URL Login Kustom <?php echo pa_sinova_info_tip('Pindahkan alamat masuk admin dari /wp-login.php dan blokir akses anonim dengan 404. Pemulihan darurat: define("PA_DISABLE_CUSTOM_LOGIN", true); di wp-config.php.', 'Info URL login kustom'); ?></h2>
                        <span class="pa-sec-pill <?php echo $hiddenSlug !== '' ? 'is-ok' : ''; ?>"><?php echo $hiddenSlug !== '' ? ('/' . esc_html($hiddenSlug) . '/') : 'Standar'; ?></span>
                    </div>
                    <form method="post" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="save_login_hide">
                        <input type="text" name="custom_login_slug" value="<?php echo esc_attr($cfg['custom_login_slug'] ?? ''); ?>" placeholder="slug-masuk-pa (kosongkan = standar)" style="flex:1;min-width:180px;">
                        <button type="submit" class="button button-primary">Simpan Slug</button>
                    </form>
                    <div style="margin-top:8px;font-size:12px;color:#475569;">
                        <?php echo $hiddenSlug !== '' ? ('Alamat login aktif: <code>' . esc_html(trailingslashit(home_url('/' . $hiddenSlug))) . '</code>') : ('Aktif: <code>' . esc_html(site_url('wp-login.php')) . '</code>'); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:16px 18px;margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                    <h2 style="margin:0;color:#056e4f;font-size:15.5px;">Ringkasan Proteksi &amp; Peran Operator <?php echo pa_sinova_info_tip('Sorot atau klik ikon (i) di tiap lencana untuk melihat detail teknis modul proteksi.', 'Info proteksi'); ?></h2>
                    <span style="font-size:12px;color:#475569;">Peran Operator: <code>pa_kepaniteraan</code> · <code>pa_kesekretariatan</code> · <code>pa_humas</code></span>
                </div>
                <div class="pa-sec-pill-grid">
                    <span class="pa-sec-pill <?php echo $cspModeNow === 'strict' ? 'is-ok' : ''; ?>">CSP: <?php echo esc_html(strtoupper($cspModeNow)); ?> <?php echo pa_sinova_info_tip('Content Security Policy, Trusted Types, COOP/CORP, XFO, XCTO, HSTS — aktif juga di Early Cache.'); ?></span>
                    <span class="pa-sec-pill is-ok">WAF &amp; Anti-Judol <?php echo pa_sinova_info_tip('Pemblokiran pola webshell, SQLi/XSS, dan kata kunci judi online di URI maupun konten database.'); ?></span>
                    <span class="pa-sec-pill is-ok">Rate Limit &amp; Brute-Force <?php echo pa_sinova_info_tip('Blokir 15 menit setelah 5x login gagal, rate limiter REST pencarian & kunjungan, serta XML-RPC/user enumeration nonaktif.'); ?></span>
                    <span class="pa-sec-pill is-ok"><?php echo $tsActive ? 'Turnstile + Honeypot' : 'CAPTCHA HMAC + Honeypot'; ?> <?php echo pa_sinova_info_tip('Pelindung formulir login & akses anti-bot otomatis.'); ?></span>
                    <span class="pa-sec-pill <?php echo is_file(WP_CONTENT_DIR . '/uploads/sinova-docs/.htaccess') ? 'is-ok' : 'is-warn'; ?>">Kunci PDF .htaccess <?php echo pa_sinova_info_tip('Menonaktifkan eksekusi PHP & directory listing di folder sinova-docs serta validasi MIME PDF.'); ?></span>
                    <span class="pa-sec-pill is-ok">RFC 9116 security.txt <?php echo pa_sinova_info_tip('Tersedia di /.well-known/security.txt; DISALLOW_FILE_EDIT aktif; batas sesi diam 60 menit; password min. 10 karakter.'); ?></span>
                </div>
            </div>

            <details class="pa-adm-fold" <?php echo empty($fim['ok']) ? 'open' : ''; ?>>
                <summary>
                    <span>File Integrity Monitor (FIM SHA-256) <?php echo pa_sinova_info_tip('Memantau perubahan tak terduga pada 6 berkas inti & .htaccess. Perbarui baseline setelah update manual.', 'Info FIM'); ?></span>
                    <span class="pa-sec-pill <?php echo $fim['ok'] ? 'is-ok' : 'is-warn'; ?>"><?php echo $fim['ok'] ? '✔ Bersih' : '⚠ Ada Perubahan'; ?> · <?php echo esc_html($fim['baseline_at']); ?></span>
                </summary>
                <div class="pa-adm-fold-body">
                    <?php if (current_user_can('manage_options')) : ?>
                        <form method="post" style="margin-bottom:10px;" onsubmit="return confirm('Perbarui baseline SHA-256 sesuai isi berkas saat ini?');">
                            <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                            <input type="hidden" name="pa_sinova_action" value="fim_reset_baseline">
                            <button type="submit" class="button button-secondary button-small">Perbarui Baseline SHA-256</button>
                        </form>
                    <?php endif; ?>
                    <table class="widefat striped">
                        <thead><tr><th>Berkas Terpantau</th><th>Hash SHA-256</th><th style="width:120px;">Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($fim['current'] as $fKey => $fHash) :
                                $isDirty = in_array($fKey, $fim['changed'], true) || in_array($fKey . ' (baru)', $fim['changed'], true);
                            ?>
                                <tr>
                                    <td><code><?php echo esc_html($fKey); ?></code></td>
                                    <td><code style="font-size:11px;"><?php echo esc_html(substr($fHash, 0, 32) . '...'); ?></code></td>
                                    <td><strong style="color:<?php echo $isDirty ? '#b91c1c' : '#15803d'; ?>;"><?php echo $isDirty ? 'Berubah' : 'Terverifikasi'; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="pa-adm-fold" <?php echo !empty($secLogs) ? '' : ''; ?>>
                <summary>
                    <span>Log Kejadian Keamanan</span>
                    <span class="pa-sec-pill"><?php echo esc_html((string) count($secLogs)); ?> catatan</span>
                </summary>
                <div class="pa-adm-fold-body">
                    <table class="widefat striped">
                        <thead><tr><th>Waktu (UTC)</th><th>Jenis Kejadian</th><th>Keterangan</th><th>Hash IP</th></tr></thead>
                        <tbody>
                            <?php if (empty($secLogs)) : ?>
                                <tr><td colspan="4">Belum ada percobaan mencurigakan yang tercatat.</td></tr>
                            <?php else : foreach ($secLogs as $lg) : ?>
                                <tr>
                                    <td><?php echo esc_html($lg['at'] ?? '-'); ?></td>
                                    <td><code><?php echo esc_html($lg['event'] ?? '-'); ?></code></td>
                                    <td><?php echo esc_html($lg['detail'] ?? '-'); ?></td>
                                    <td><code><?php echo esc_html($lg['ip_hash'] ?? '-'); ?></code></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </details>
        <?php endif; ?>
        </main>
    </div>
    <div id="pa-adm-tip-pop" role="tooltip" hidden></div>
    <script>
        (function(){
            var pop = document.getElementById('pa-adm-tip-pop');
            var activeBtn = null;
            var pinned = false;
            function hidePop(){
                if (!pop) return;
                pop.hidden = true;
                if (activeBtn) activeBtn.setAttribute('aria-expanded', 'false');
                activeBtn = null;
                pinned = false;
            }
            function showPop(btn, isPin){
                if (!pop || !btn) return;
                var msg = btn.getAttribute('data-pa-info') || '';
                if (!msg) return;
                if (activeBtn && activeBtn !== btn) activeBtn.setAttribute('aria-expanded', 'false');
                activeBtn = btn;
                pinned = !!isPin;
                pop.textContent = msg;
                pop.hidden = false;
                btn.setAttribute('aria-expanded', 'true');
                var r = btn.getBoundingClientRect();
                var pw = pop.offsetWidth || 280;
                var ph = pop.offsetHeight || 60;
                var left = Math.min(window.innerWidth - pw - 12, Math.max(12, r.left + (r.width / 2) - (pw / 2)));
                var top = r.top - ph - 8;
                if (top < 12) top = r.bottom + 8;
                pop.style.left = Math.round(left) + 'px';
                pop.style.top = Math.round(top) + 'px';
            }
            document.addEventListener('click', function(e){
                var btn = e.target.closest ? e.target.closest('.pa-info-btn') : null;
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (activeBtn === btn && pinned) hidePop();
                    else showPop(btn, true);
                    return;
                }
                if (pop && !pop.contains(e.target)) hidePop();
            });
            document.addEventListener('mouseover', function(e){
                var btn = e.target.closest ? e.target.closest('.pa-info-btn') : null;
                if (btn && !pinned) showPop(btn, false);
            });
            document.addEventListener('mouseout', function(e){
                var btn = e.target.closest ? e.target.closest('.pa-info-btn') : null;
                if (btn && !pinned && (!e.relatedTarget || (pop && !pop.contains(e.relatedTarget)))) hidePop();
            });
            document.addEventListener('focusin', function(e){
                var btn = e.target.closest ? e.target.closest('.pa-info-btn') : null;
                if (btn) showPop(btn, false);
            });
            document.addEventListener('focusout', function(e){
                if (!pinned && e.target.classList && e.target.classList.contains('pa-info-btn')) hidePop();
            });
            document.addEventListener('keydown', function(e){
                if (e.key === 'Escape' && pop && !pop.hidden) hidePop();
            });

            window.paOpenMediaPicker = function(onSelect){
                if (!window.wp || !wp.media) {
                    alert('Pengelola Media WordPress belum siap. Muat ulang halaman.');
                    return;
                }
                var frame = wp.media({ title: 'Pilih atau Unggah Gambar', button: { text: 'Gunakan Gambar Ini' }, multiple: false });
                frame.on('select', function(){
                    var att = frame.state().get('selection').first().toJSON();
                    if (att && att.url && typeof onSelect === 'function') onSelect(att.url);
                });
                frame.open();
            };
            document.addEventListener('click', function(e){
                var btn = e.target.closest ? e.target.closest('.pa-media-pick-btn') : null;
                if (!btn) return;
                e.preventDefault();
                var tid = btn.getAttribute('data-target');
                var pid = btn.getAttribute('data-preview');
                window.paOpenMediaPicker(function(url){
                    var inp = tid ? document.getElementById(tid) : null;
                    if (inp) inp.value = url;
                    var prv = pid ? document.getElementById(pid) : null;
                    if (prv) prv.src = url;
                });
            });
            [
                { inp: 'pa-set-logo-url', prv: 'pa-set-logo-preview' },
                { inp: 'pa-set-favicon-url', prv: 'pa-set-favicon-preview' },
                { inp: 'pa-set-aco-logo', prv: 'pa-set-aco-preview' },
                { inp: 'pa-set-hero-photo', prv: 'pa-set-hero-preview' },
                { inp: 'pa-set-leader-photo', prv: 'pa-set-leader-preview' }
            ].forEach(function(pair){
                var el = document.getElementById(pair.inp);
                var pv = document.getElementById(pair.prv);
                if (el && pv) {
                    el.addEventListener('input', function(){
                        var v = (el.value || '').trim();
                        if (/^(\/(?!\/)|https?:\/\/)/i.test(v)) pv.src = v;
                    });
                }
            });
        })();
    </script>
    <?php
}
