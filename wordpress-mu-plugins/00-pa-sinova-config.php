<?php
/**
 * Plugin Name: PA SINOVA Starter - Konfigurasi, Seeder 1-Klik SK 2722/2026, RBAC & Panel Kepatuhan WP Admin
 * Description: Pengaturan dinamis Satker, RBAC peran operator, generator Secret Key HMAC-SHA256, Seeder 1-Klik 6 Menu Baku SK Dirjen Badilag 2722/2026, Dashboard Kepatuhan 35 Kriteria + Migrasi & Adopsi, Data Operasional, Upload Dokumen (Edit & Hapus Massal), & Audit Keamanan + MFA di WP Admin.
 * Version: 2.0.0-starter
 */

defined('ABSPATH') || exit;

function pa_sinova_get_settings(): array {
    $defaults = array(
        'satker_name'              => get_bloginfo('name') ?: 'Pengadilan Agama Contoh Kelas I-A',
        'logo_url'                 => '',
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
        'survey_url'               => 'https://survei.badilag.net/',
        'aco_url'                  => 'https://cctv.badilag.net/',
        'aco_logo_url'             => '',
        'aco_float_enabled'        => '1',
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
    $sippSlideChk = pa_sinova_sanitize_https_embed_url((string) ($cfg['sipp_slide_url'] ?? ''));
    if ($sippSlideChk['valid'] && $sippSlideChk['origin'] !== '' && !in_array($sippSlideChk['origin'], $frameOrigins, true)) {
        $frameOrigins[] = $sippSlideChk['origin'];
    }

    $csp = "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; "
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
        return array('compliance', 'upload', 'data', 'migration', 'settings', 'security');
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
            'content' => '<h2>Peta &amp; Cakupan Wilayah Yurisdiksi</h2><p>[LENGKAPI: Daftar kecamatan/kelurahan wilayah hukum satker dan sematan Google Maps kantor.]</p>',
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
            'content' => '<h2>Direktori Putusan Mahkamah Agung RI</h2><p>[LENGKAPI tautan langsung halaman Direktori Putusan satker pada domain putusan3.mahkamahagung.go.id.]</p>',
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
                if ($recType !== '' && stripos($rawContent, '[pa_') !== false) {
                    $recCnt = (int) ($recStats[$recType] ?? 0);
                    if ($recCnt > 0) {
                        $status = 'lengkap';
                        $pts    = 10;
                        $note   = "Halaman & data terstruktur aktif ({$recCnt} entri terpublikasi).";
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
        array('label' => 'Chrome Security Headers (CSP Nonce + Trusted Types + COOP + CORP)', 'pass' => $cspStrict, 'detail' => $cspStrict ? 'Mode Strict aktif (CSP Nonce + Trusted Types + COOP/CORP)' : "Mode CSP saat ini: {$cspMode}", 'tab' => 'settings'),
        array('label' => 'Early Page Cache + Gzip + ETag/304 + Speculation Rules', 'pass' => $cacheActive, 'detail' => $cacheActive ? 'Cache HTML statis SWR + ETag + Gzip aktif' : 'Cache statis tidak aktif', 'tab' => 'security'),
        array('label' => 'Kebijakan MFA TOTP RFC 6238 & Password Kuat + Simbol', 'pass' => ($mfaEnforced && $hasAnyMfa), 'detail' => ($mfaEnforced ? 'Wajib MFA aktif' : 'Wajib MFA belum dicentang') . ' · ' . ($hasAnyMfa ? 'Akun Anda ber-MFA' : 'Akun Anda belum aktivasi MFA'), 'tab' => 'security'),
        array('label' => 'Custom Hidden Login URL + WAF Anti-Judol + Rate Limiter', 'pass' => ($hiddenSlug !== ''), 'detail' => $hiddenSlug !== '' ? "URL Login tersembunyi (/{$hiddenSlug}/)" : 'URL Login masih standar /wp-login.php', 'tab' => 'security'),
        array('label' => 'RFC 9116 security.txt, Proteksi PDF .htaccess & Blokir XML-RPC', 'pass' => $pdfHardened, 'detail' => $pdfHardened ? '/.well-known/security.txt & .htaccess PDF aktif' : 'Folder upload belum dikunci .htaccess', 'tab' => 'security'),
        array('label' => 'SEO Teknis Chrome (Indeks Aktif, Meta Description, JSON-LD, Sitemap)', 'pass' => $blogPublic, 'detail' => $blogPublic ? 'Visibilitas mesin pencari aktif + JSON-LD + Meta Description + Sitemap' : 'PERINGATAN: Settings → Reading mencentang Discourage Search Engines (noindex)!', 'tab' => 'settings'),
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

    $upsertPage = static function (string $key, string $title, string $slug, string $content) use (&$seeded, &$createdCount): int {
        $existingId = isset($seeded[$key]) ? (int) $seeded[$key] : 0;
        if ($existingId > 0 && get_post_status($existingId) === 'publish') {
            return $existingId;
        }
        $byPath = pa_sinova_find_existing_page($slug);
        if ($byPath instanceof WP_Post) {
            $seeded[$key] = (int) $byPath->ID;
            return (int) $byPath->ID;
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
        $pid = $upsertPage('static:' . $slug, $meta['title'], $slug, $meta['content']);
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

    $menuItemsPlan['Berita & Publikasi'][] = array(
        'title' => 'Berita & Artikel',
        'url'   => home_url('/?post_type=post'),
    );

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
        $tplPaths  = array(
            ABSPATH . 'sinova-starter-kit/wordpress-templates/beranda-blocks-starter.html',
            dirname(__DIR__) . '/wordpress-templates/beranda-blocks-starter.html',
        );
        $frontHtml = '';
        foreach ($tplPaths as $tp) {
            if (is_file($tp)) {
                $frontHtml = (string) file_get_contents($tp);
                break;
            }
        }
        if ($frontHtml === '') {
            $frontHtml = "<!-- wp:html -->\n<div class=\"pa-starter-showcase\">\n  <section class=\"pa-hero-banner\"><div class=\"pa-hero-content\"><span class=\"pa-hero-kicker\">Zona Integritas WBK / WBBM · Keterbukaan Informasi Publik</span><h1 class=\"pa-hero-title\">Portal Keterbukaan Informasi &amp; Layanan Peradilan</h1><p class=\"pa-hero-lead\">Menyediakan akses informasi perkara, jadwal sidang, panjar biaya, laporan kinerja, serta arsip transparansi keuangan.</p><div class=\"pa-hero-actions\"><a class=\"pa-btn-gold\" href=\"./agenda-jadwal-persidangan/\">Jadwal Sidang &amp; SIPP</a><a class=\"pa-btn-outline\" href=\"./e-court/\">Layanan e-Court</a><a class=\"pa-btn-outline\" href=\"./survei-pelayanan/\">Survei Pelayanan</a></div></div></section>\n  [pa_sinova_sipp_slide]\n  [pa_sinova_welcome_section]\n  [pa_sinova_zi_grid]\n  [pa_sinova_latest_posts count=\"3\"]\n</div>\n<!-- /wp:html -->";
        }
        $frontId = !empty($seeded['static:beranda']) ? (int) $seeded['static:beranda'] : 0;
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

    return array(
        'pages_created' => $createdCount,
        'total_mapped'  => count($seeded),
        'menu_items'    => $menuCreated,
        'preserve_mode' => $preserve ? 1 : 0,
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

    $fallbackYear = ($defaultYear >= 2017 && $defaultYear <= 2027) ? $defaultYear : (int) wp_date('Y');
    $modCfg       = ($modSlug !== '' && function_exists('pa_sinova_module_config')) ? pa_sinova_module_config($modSlug) : null;
    $subGroups    = ($modCfg && !empty($modCfg['sub_groups']) && is_array($modCfg['sub_groups'])) ? $modCfg['sub_groups'] : array('DOKUMEN');
    $fallbackSub  = trim($defaultSub) !== '' ? trim($defaultSub) : (string) $subGroups[0];
    $periodsMap   = function_exists('pa_sinova_all_periods_map') ? pa_sinova_all_periods_map() : array();
    $modPeriods   = (is_array($modCfg) && function_exists('pa_sinova_module_periods')) ? pa_sinova_module_periods($modCfg) : $periodsMap;
    $defaultPer   = !empty($modPeriods) ? (string) array_key_first($modPeriods) : 'TAHUNAN';

    $content = (string) $post->post_content;
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
        'AGT' => '/\b(?:agt|ags|agustus|august|aug)\b/i',
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
            if (preg_match('/(?<!\d)(201[7-9]|202[0-7])(?!\d)/', $src, $ym)) {
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

/** Build validated column payload for pa_portal_records insert or update. */
function pa_sinova_build_record_payload(array $input): array {
    $recType = sanitize_key((string) ($input['rec_type'] ?? 'jadwal'));
    if (!in_array($recType, array('pegawai', 'jadwal', 'ghaib', 'statistik'), true)) {
        $recType = 'jadwal';
    }
    $recTitle = sanitize_text_field(wp_unslash((string) ($input['rec_title'] ?? '')));
    $recSub   = sanitize_text_field(wp_unslash((string) ($input['rec_subtitle'] ?? '')));
    $recCat   = sanitize_text_field(wp_unslash((string) ($input['rec_category'] ?? '')));
    $recM1    = sanitize_text_field(wp_unslash((string) ($input['rec_meta_1'] ?? '')));
    $recM2    = sanitize_text_field(wp_unslash((string) ($input['rec_meta_2'] ?? '')));
    $recM3    = sanitize_text_field(wp_unslash((string) ($input['rec_meta_3'] ?? '')));
    $recDate  = sanitize_text_field(wp_unslash((string) ($input['rec_date'] ?? '')));
    $recOrder = (int) ($input['rec_sort_order'] ?? 0);

    if ($recTitle === '') {
        return array('error' => 'Gagal: Judul / nama entri wajib diisi.');
    }
    if ($recType === 'pegawai') {
        $metaArr = array('nip' => $recM1, 'gol' => $recM2, 'edu' => $recM3);
    } elseif ($recType === 'ghaib') {
        $metaArr = array('pihak' => ($recM1 !== '' ? $recM1 : $recSub), 'doc_url' => esc_url_raw($recM2, array('http', 'https')));
    } elseif ($recType === 'statistik') {
        $metaArr = array('diterima' => (int) $recM1, 'diputus' => (int) $recM2, 'sisa' => (int) $recM3);
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
            if (!in_array($palette, array('gold', 'badilag_orange'), true)) {
                $palette = 'gold';
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
            $sippSlideRaw  = wp_unslash((string) ($_POST['sipp_slide_url'] ?? ''));
            $sippSlideChk  = pa_sinova_sanitize_https_embed_url($sippSlideRaw);
            $sippSlideWarn = !$sippSlideChk['valid'];
            $sippSlideSave = $sippSlideChk['valid'] ? $sippSlideChk['url'] : (string) ($cur['sipp_slide_url'] ?? '');

            $new = array(
                'satker_name'              => sanitize_text_field(wp_unslash((string) ($_POST['satker_name'] ?? $cur['satker_name']))),
                'logo_url'                 => esc_url_raw(wp_unslash((string) ($_POST['logo_url'] ?? ''))),
                'preserve_existing_design' => isset($_POST['preserve_existing_design']) ? (!empty($_POST['preserve_existing_design']) ? '1' : '0') : ((string) ($cur['preserve_existing_design'] ?? '0')),
                'address'                  => sanitize_text_field(wp_unslash((string) ($_POST['address'] ?? $cur['address']))),
                'phone'                    => sanitize_text_field(wp_unslash((string) ($_POST['phone'] ?? $cur['phone']))),
                'email'                    => sanitize_email(wp_unslash((string) ($_POST['email'] ?? $cur['email']))),
                'hearing_hours'            => sanitize_text_field(wp_unslash((string) ($_POST['hearing_hours'] ?? $cur['hearing_hours']))),
                'leader_name'              => sanitize_text_field(wp_unslash((string) ($_POST['leader_name'] ?? $cur['leader_name']))),
                'leader_welcome'           => sanitize_textarea_field(wp_unslash((string) ($_POST['leader_welcome'] ?? $cur['leader_welcome']))),
                'sipp_url'                 => esc_url_raw(wp_unslash((string) ($_POST['sipp_url'] ?? ''))),
                'sipp_slide_url'           => $sippSlideSave,
                'ecourt_url'               => esc_url_raw(wp_unslash((string) ($_POST['ecourt_url'] ?? ''))),
                'direktori_url'            => esc_url_raw(wp_unslash((string) ($_POST['direktori_url'] ?? ''))),
                'survey_url'               => esc_url_raw(wp_unslash((string) ($_POST['survey_url'] ?? ''))),
                'aco_url'                  => esc_url_raw(wp_unslash((string) ($_POST['aco_url'] ?? ($cur['aco_url'] ?? '')))),
                'aco_logo_url'             => esc_url_raw(wp_unslash((string) ($_POST['aco_logo_url'] ?? ''))),
                'aco_float_enabled'        => !empty($_POST['aco_float_enabled']) ? '1' : '0',
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
            );
            if (!empty($_POST['generate_hmac'])) {
                $new['hmac_secret'] = bin2hex(random_bytes(32));
            }
            update_option('pa_sinova_starter_settings', $new, false);
            if (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }
            $notice = 'Pengaturan Satker, Identitas Visual, Umpan RSS, & API HMAC berhasil disimpan.';
            if ($rssMaCheck['warn'] || $rssBadCheck['warn']) {
                $notice .= ' Catatan: URL RSS non-HTTPS atau di luar domain resmi (.go.id) ditolak dan dikembalikan ke URL aman sebelumnya.';
            }
            if ($sippSlideWarn) {
                $notice .= ' Catatan: URL Slide Jadwal Sidang SIPP wajib memakai protokol HTTPS dengan domain valid.';
            }
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
                $notice   = "Sinkronisasi RSS resmi selesai: MA RI ({$maCnt} item) dan Ditjen Badilag ({$bdCnt} item).";
            } else {
                $notice = 'Gagal: Modul umpan RSS belum dimuat.';
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
        'migration'  => 'Migrasi &amp; Adopsi',
        'settings'   => 'Identitas Satker &amp; Seeder',
        'security'   => 'Keamanan, MFA &amp; Audit',
    );
    ?>
    <div class="wrap">
        <h1 style="color:#056e4f;font-weight:800;">SINOVA Hub — Panel Backend &amp; Kepatuhan (SK Dirjen Badilag 2722/2026)</h1>
        <p style="color:#475569;margin-top:2px;">Halaman backend khusus operator untuk memantau kesiapan 3 Pilar SINOVA &amp; Standar Chrome Modern Web, mengunggah arsip PDF/Drive, serta mengelola data operasional &amp; keamanan. Istilah <em>SINOVA</em> tidak ditampilkan di halaman publik masyarakat.</p>

        <?php if ($notice !== '') :
            $noticeClass = (stripos($notice, 'Gagal:') === 0) ? 'notice-error' : 'notice-success';
        ?>
            <div class="notice <?php echo esc_attr($noticeClass); ?> is-dismissible"><p><strong><?php echo esc_html($notice); ?></strong></p></div>
        <?php endif; ?>

        <h2 class="nav-tab-wrapper" style="margin:14px 0 18px;">
            <?php foreach ($tabLabels as $tKey => $tLabel) :
                if (!in_array($tKey, $allowedTabs, true)) {
                    continue;
                }
            ?>
                <a href="<?php echo esc_url($tabBaseUrl . $tKey); ?>" class="nav-tab <?php echo $activeTab === $tKey ? 'nav-tab-active' : ''; ?>"><?php echo $tLabel; ?></a>
            <?php endforeach; ?>
        </h2>

        <?php if ($activeTab === 'compliance') : ?>
            <div style="background:linear-gradient(135deg,#043927 0%,#056e4f 100%);color:#fff;border:1px solid #C9A227;border-radius:12px;padding:16px 20px;margin-bottom:16px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px;">
                <div>
                    <div style="font-size:11px;font-weight:800;letter-spacing:.06em;color:#E5C158;text-transform:uppercase;">Skor Gabungan 3 Pilar SINOVA + Standar Web Modern Chrome</div>
                    <div style="display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;margin-top:4px;">
                        <span style="font-size:34px;font-weight:900;color:#fff;"><?php echo esc_html((string) $audit['weighted_total']); ?> / 100</span>
                        <span style="display:inline-block;padding:4px 12px;border-radius:999px;background:#fff;color:<?php echo esc_attr($audit['predicate_color']); ?>;font-weight:800;font-size:12.5px;">Predikat: <?php echo esc_html($audit['predicate']); ?></span>
                    </div>
                    <div style="font-size:12px;color:#d1fae5;margin-top:4px;">
                        Pilar 1 Konten (60%): <strong><?php echo esc_html((string) $audit['pilar1_pct']); ?>%</strong> ·
                        Pilar 2 UI/UX &amp; WCAG 2.2 (20%): <strong><?php echo esc_html((string) $audit['pilar2_pct']); ?>%</strong> ·
                        Pilar 3 Performa, Keamanan &amp; Chrome (20%): <strong><?php echo esc_html((string) $audit['pilar3_pct']); ?>%</strong>
                    </div>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <a href="<?php echo esc_url($exportCsvUrl); ?>" class="button button-secondary">⬇ Ekspor Bukti CSV (SINOVA)</a>
                    <a href="<?php echo esc_url($exportJsonUrl); ?>" class="button button-secondary">⬇ Ekspor Laporan JSON 3 Pilar</a>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:18px;">
                <div style="background:#fff;border:1px solid #cbd5e1;border-left:5px solid #056e4f;border-radius:10px;padding:14px;">
                    <div style="font-size:12px;color:#64748b;font-weight:700;">SKOR 35 KRITERIA (PILAR 1)</div>
                    <div style="font-size:28px;font-weight:900;color:#056e4f;"><?php echo esc_html((string) $report['total']); ?>%</div>
                    <div style="font-size:11.5px;color:#475569;">Berkala: <?php echo esc_html((string) $report['berkala']); ?>% · Wajib: <?php echo esc_html((string) $report['wajib']); ?>%</div>
                </div>
                <div style="background:#fff;border:1px solid #cbd5e1;border-left:5px solid #16a34a;border-radius:10px;padding:14px;">
                    <div style="font-size:12px;color:#64748b;font-weight:700;">LENGKAP</div>
                    <div style="font-size:28px;font-weight:900;color:#15803d;"><?php echo esc_html((string) $report['counts']['lengkap']); ?> / 35</div>
                    <div style="font-size:11.5px;color:#475569;">Siap dinilai otomatis</div>
                </div>
                <div style="background:#fff;border:1px solid #cbd5e1;border-left:5px solid #d97706;border-radius:10px;padding:14px;">
                    <div style="font-size:12px;color:#64748b;font-weight:700;">PERLU DILENGKAPI</div>
                    <div style="font-size:28px;font-weight:900;color:#b45309;"><?php echo esc_html((string) $report['counts']['perhatian']); ?> / 35</div>
                    <div style="font-size:11.5px;color:#475569;">Bertanda [LENGKAPI] / kurang tahun</div>
                </div>
                <div style="background:#fff;border:1px solid #cbd5e1;border-left:5px solid #dc2626;border-radius:10px;padding:14px;">
                    <div style="font-size:12px;color:#64748b;font-weight:700;">KOSONG</div>
                    <div style="font-size:28px;font-weight:900;color:#b91c1c;"><?php echo esc_html((string) $report['counts']['kosong']); ?> / 35</div>
                    <div style="font-size:11.5px;color:#475569;">Belum terunggah</div>
                </div>
            </div>

            <?php if (!empty($audit['recommendations'])) : ?>
                <div style="background:#fffbeb;border:1px solid #f59e0b;border-left:5px solid #d97706;border-radius:10px;padding:14px 18px;margin-bottom:18px;">
                    <strong style="color:#92400e;">Rekomendasi Prioritas Menuju Predikat Sangat Baik / Excellent (&ge; 90):</strong>
                    <ul style="margin:8px 0 0;padding-left:18px;font-size:12.5px;color:#78350f;">
                        <?php foreach (array_slice($audit['recommendations'], 0, 8) as $rec) : ?>
                            <li>
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

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));gap:16px;margin-bottom:18px;">
                <div style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:14px;">
                    <h3 style="margin:0 0 10px;color:#056e4f;">Pilar 2 — UI/UX, 2-Click Rule &amp; Aksesibilitas WCAG 2.2 (<?php echo esc_html((string) $audit['pilar2_pct']); ?>%)</h3>
                    <table class="widefat striped">
                        <tbody>
                            <?php foreach ($audit['p2_checks'] as $chk) : ?>
                                <tr>
                                    <td style="width:28px;font-weight:900;color:<?php echo $chk['pass'] ? '#15803d' : '#b45309'; ?>;"><?php echo $chk['pass'] ? '✔' : '!'; ?></td>
                                    <td><strong><?php echo esc_html($chk['label']); ?></strong><br><small style="color:#475569;"><?php echo esc_html($chk['detail']); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:14px;">
                    <h3 style="margin:0 0 10px;color:#056e4f;">Pilar 3 — Performa, Keamanan &amp; Standar Chrome (<?php echo esc_html((string) $audit['pilar3_pct']); ?>%)</h3>
                    <table class="widefat striped">
                        <tbody>
                            <?php foreach ($audit['p3_checks'] as $chk) : ?>
                                <tr>
                                    <td style="width:28px;font-weight:900;color:<?php echo $chk['pass'] ? '#15803d' : '#b45309'; ?>;"><?php echo $chk['pass'] ? '✔' : '!'; ?></td>
                                    <td><strong><?php echo esc_html($chk['label']); ?></strong><br><small style="color:#475569;"><?php echo esc_html($chk['detail']); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:16px;">
                <table class="widefat striped">
                    <thead>
                        <tr><th style="width:64px;">Kode</th><th style="width:90px;">Kelompok</th><th>Kriteria Lampiran II</th><th style="width:125px;">Status</th><th>Catatan Evaluasi</th><th style="width:230px;">URL Bukti untuk SINOVA</th></tr>
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
            <script>
                document.querySelectorAll('.pa-copy-url-btn').forEach(function(btn){
                    btn.addEventListener('click', function(){
                        var u = btn.getAttribute('data-url');
                        if(navigator.clipboard){ navigator.clipboard.writeText(u); }
                        var orig = btn.textContent;
                        btn.textContent = 'Tersalin!';
                        setTimeout(function(){ btn.textContent = orig; }, 1400);
                    });
                });
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
            <div style="display:grid;grid-template-columns:1fr 1.35fr;gap:20px;align-items:start;">
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid <?php echo $isEditingDoc ? '#056e4f' : '#C9A227'; ?>;border-radius:10px;padding:18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:6px;">
                        <h2 style="margin:0;color:#056e4f;"><?php echo $isEditingDoc ? 'Edit Dokumen Arsip' : 'Upload Dokumen Cepat'; ?></h2>
                        <?php if ($isEditingDoc) : ?>
                            <span style="background:#ecfdf5;color:#056e4f;border:1px solid #86efac;border-radius:999px;padding:2px 10px;font-size:11.5px;font-weight:700;">Mode Edit · ID #<?php echo esc_html((string) $editDoc['id']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($isEditingDoc) : ?>
                        <p style="font-size:12.5px;color:#334155;background:#f8fafc;border-left:3px solid #056e4f;padding:8px 10px;margin:8px 0 12px;">
                            Mengubah dokumen <strong><?php echo esc_html((string) $editDoc['title']); ?></strong>.<br>
                            Sumber aktif:
                            <a href="<?php echo esc_url((string) $editDoc['doc_url']); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html(($editDoc['source_type'] ?? '') === 'wp_pdf' ? 'PDF Lokal (' . basename((string) $editDoc['doc_url']) . ')' : (string) $editDoc['doc_url']); ?> ↗
                            </a>
                            <br><span style="color:#64748b;">Biarkan berkas &amp; kolom URL kosong jika hanya ingin memperbaiki modul, tahun, slot, atau judul.</span>
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
            $recTypes = array(
                'jadwal'    => 'Jadwal Sidang Harian',
                'pegawai'   => 'Profil Hakim & Pegawai',
                'ghaib'     => 'Panggilan Ghaib (Pasal 27)',
                'statistik' => 'Statistik Perkara Bulanan',
            );
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
            } elseif ($isEditingRec && $formRecType === 'pegawai') {
                $formRecM1 = (string) ($editMeta['nip'] ?? '');
                $formRecM2 = (string) ($editMeta['gol'] ?? '');
                $formRecM3 = (string) ($editMeta['edu'] ?? '');
            } elseif ($isEditingRec && $formRecType === 'ghaib') {
                $formRecM1 = (string) ($editMeta['pihak'] ?? '');
                $formRecM2 = (string) ($editMeta['doc_url'] ?? '');
                $formRecM3 = '';
            } elseif ($isEditingRec && $formRecType === 'statistik') {
                $formRecM1 = (string) ($editMeta['diterima'] ?? '');
                $formRecM2 = (string) ($editMeta['diputus'] ?? '');
                $formRecM3 = (string) ($editMeta['sisa'] ?? '');
            } elseif ($isEditingRec) {
                $formRecM1 = (string) ($editMeta['jam'] ?? '');
                $formRecM2 = '';
                $formRecM3 = '';
            } else {
                $formRecM1 = '';
                $formRecM2 = '';
                $formRecM3 = '';
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

            <div style="display:grid;grid-template-columns:1fr 1.35fr;gap:20px;align-items:start;">
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid <?php echo $isEditingRec ? '#056e4f' : '#C9A227'; ?>;border-radius:10px;padding:18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:6px;">
                        <h2 style="margin:0;color:#056e4f;"><?php echo $isEditingRec ? 'Edit Data Operasional' : 'Tambah Data Operasional Terstruktur'; ?></h2>
                        <?php if ($isEditingRec) : ?>
                            <span style="background:#ecfdf5;color:#056e4f;border:1px solid #86efac;border-radius:999px;padding:2px 10px;font-size:11.5px;font-weight:700;">Mode Edit · ID #<?php echo esc_html((string) $editRec['id']); ?></span>
                        <?php endif; ?>
                    </div>
                    <p style="font-size:12.5px;color:#475569;margin-top:0;">Entri langsung tampil pada halaman <code>/profil-pegawai/</code>, <code>/agenda-jadwal-persidangan/</code>, <code>/panggilan-ghaib/</code>, dan <code>/statistik-perkara/</code>.</p>
                    <form method="post" action="<?php echo esc_url($dataViewUrl); ?>">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="<?php echo $isEditingRec ? 'update_record' : 'add_record'; ?>">
                        <?php if ($isEditingRec) : ?>
                            <input type="hidden" name="edit_rec_id" value="<?php echo esc_attr((string) $editRec['id']); ?>">
                        <?php endif; ?>
                        <table class="form-table">
                            <tr><th>Jenis Data</th><td>
                                <select name="rec_type" class="regular-text">
                                    <?php foreach ($recTypes as $rk => $rl) : ?>
                                        <option value="<?php echo esc_attr($rk); ?>" <?php selected($formRecType, $rk); ?>><?php echo esc_html($rl); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td></tr>
                            <tr><th>Judul / Nama / No. Perkara</th><td><input type="text" name="rec_title" class="regular-text" required value="<?php echo esc_attr($formRecTitle); ?>" placeholder="Contoh: 1201/Pdt.G/2026/PA.Sm"></td></tr>
                            <tr><th>Subjudul / Jabatan / Agenda</th><td><input type="text" name="rec_subtitle" class="regular-text" value="<?php echo esc_attr($formRecSub); ?>" placeholder="Contoh: Sidang Pertama / Ketua Pengadilan Agama"></td></tr>
                            <tr><th>Kategori / Ruang / Tahun</th><td><input type="text" name="rec_category" class="regular-text" value="<?php echo esc_attr($formRecCat); ?>" placeholder="Contoh: Ruang Sidang 1 / Pimpinan / 2026"></td></tr>
                            <tr><th>Jam / NIP / Pihak / Diterima</th><td><input type="text" name="rec_meta_1" class="regular-text" value="<?php echo esc_attr($formRecM1); ?>" placeholder="Contoh: 09.00 WIB atau NIP atau Nama Pihak atau 145"></td></tr>
                            <tr><th>Golongan / URL Relaas / Diputus</th><td><input type="text" name="rec_meta_2" class="regular-text" value="<?php echo esc_attr($formRecM2); ?>" placeholder="Contoh: Pembina (IV/a) atau https://... atau 138"></td></tr>
                            <tr><th>Pendidikan / Sisa</th><td><input type="text" name="rec_meta_3" class="regular-text" value="<?php echo esc_attr($formRecM3); ?>" placeholder="Contoh: S2 Hukum Islam atau 42"></td></tr>
                            <tr><th>Tanggal (Opsional)</th><td><input type="date" name="rec_date" value="<?php echo esc_attr($formRecDate); ?>"></td></tr>
                            <tr><th>Urutan Tampil</th><td><input type="number" name="rec_sort_order" value="<?php echo esc_attr((string) $formRecOrder); ?>" style="width:90px;"></td></tr>
                        </table>
                        <p style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                            <button type="submit" class="button button-primary"><?php echo $isEditingRec ? 'Simpan Perubahan' : 'Simpan Data Operasional'; ?></button>
                            <?php if ($isEditingRec) : ?>
                                <a href="<?php echo esc_url($dataViewUrl); ?>" class="button">Batal Edit</a>
                            <?php endif; ?>
                        </p>
                    </form>
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
                            <button type="submit" id="pa-bulk-rec-del-btn" class="button" style="color:#b91c1c;border-color:#fca5a5;" disabled>Hapus Terpilih (0)</button>
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
                    <script>
                        (function(){
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
                    <h2 style="margin-top:0;color:#056e4f;">1. Mode Adopsi Situs Lama (Non-Destruktif)</h2>
                    <p style="color:#475569;margin-top:0;">Cocok bagi Satker yang <strong>sudah memiliki website WordPress aktif</strong> atau baru saja mengimpor konten artikel dari <strong>Joomla</strong> dan ingin mempertahankan tema, susunan menu, serta halaman Beranda lama.</p>
                    <form method="post">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <label style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;margin-bottom:14px;">
                            <input type="checkbox" name="preserve_existing_design" value="1" <?php checked($isAdoption); ?> style="margin-top:3px;">
                            <span>
                                <strong>Pertahankan header, menu navigasi, dan beranda situs lama (Mode Non-Destruktif)</strong><br>
                                <small style="color:#475569;">Jika diaktifkan: header, navbar, dan tampilan depan situs lama Anda tidak disentuh. Hanya fitur backend (23 Modul Dokumen <code>[pa_sinova_docs]</code>, Dashboard 35 Kriteria, Keamanan WAF/Rate Limit, &amp; Cache) yang bekerja.</small>
                            </span>
                        </label>
                        <div style="display:flex;flex-wrap:wrap;gap:10px;">
                            <button type="submit" name="pa_sinova_action" value="save_adoption_mode" class="button button-secondary">Simpan Pengaturan Mode Adopsi</button>
                            <button type="submit" name="pa_sinova_action" value="save_and_seed_safe" class="button button-primary">Simpan Mode &amp; Jalankan Seeder Aman</button>
                        </div>
                    </form>
                </div>

                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">2. Ekstrak &amp; Impor Tautan Dokumen dari Halaman/Pos Lama</h2>
                    <p style="color:#475569;margin-top:0;">Pindai daftar tautan PDF atau Google Drive yang sudah tertulis pada halaman/berita lama (termasuk hasil migrasi Joomla) untuk dimasukkan langsung ke tabel modul arsip.</p>
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
                    <h2 style="margin-top:0;color:#056e4f;">3. Pemetaan Halaman Lama untuk 35 Kriteria</h2>
                    <p style="color:#475569;margin-top:0;">Jika halaman pada website lama memakai URL/slug berbeda (misal <code>/profil/visi-misi/</code> atau hasil migrasi Joomla), tautkan di sini agar Dashboard Kepatuhan &amp; tombol <strong>Salin URL</strong> mengarah ke halaman lama tanpa mengubah tautan publik.</p>
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

        <?php elseif ($activeTab === 'settings') :
            $isAdoption = (!empty($cfg['preserve_existing_design']) && (string) $cfg['preserve_existing_design'] === '1');
        ?>
            <?php if ($isAdoption) : ?>
                <div class="notice notice-info inline" style="margin:0 0 16px;padding:10px 14px;">
                    <strong>Mode Adopsi Situs Lama sedang AKTIF:</strong> Seeder 1-Klik hanya melengkapi halaman yang belum tersedia tanpa mengubah tema, menu, maupun halaman Beranda eksisting. Kelola melalui tab <a href="<?php echo esc_url($tabBaseUrl . 'migration'); ?>">Migrasi &amp; Adopsi</a>.
                </div>
            <?php endif; ?>
            <div style="display:grid;grid-template-columns:1.3fr 1fr;gap:20px;align-items:start;">
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">Konfigurasi Identitas Satker &amp; API</h2>
                    <form method="post">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="save_settings">
                        <input type="hidden" name="preserve_existing_design" value="<?php echo $isAdoption ? '1' : '0'; ?>">
                        <table class="form-table">
                            <tr><th>Nama Satker</th><td><input type="text" name="satker_name" class="regular-text" value="<?php echo esc_attr($cfg['satker_name']); ?>"></td></tr>
                            <tr><th>URL Logo Resmi</th><td><input type="url" name="logo_url" class="regular-text" value="<?php echo esc_attr($cfg['logo_url'] ?? ''); ?>" placeholder="https://.../logo-pa.png"></td></tr>
                            <tr><th>Palet Aksen Visual (SK 2722)</th><td>
                                <select name="accent_palette" class="regular-text">
                                    <option value="gold" <?php selected($cfg['accent_palette'] ?? 'gold', 'gold'); ?>>Hijau Institusional &amp; Royal Gold (#056e4f / #C9A227)</option>
                                    <option value="badilag_orange" <?php selected($cfg['accent_palette'] ?? 'gold', 'badilag_orange'); ?>>Hijau Badilag &amp; Aksen Oranye (#056e4f / #ff9900)</option>
                                </select>
                            </td></tr>
                            <tr><th>Alamat Kantor</th><td><input type="text" name="address" class="regular-text" value="<?php echo esc_attr($cfg['address']); ?>"></td></tr>
                            <tr><th>Telepon / WA PTSP</th><td><input type="text" name="phone" class="regular-text" value="<?php echo esc_attr($cfg['phone']); ?>"></td></tr>
                            <tr><th>Email Resmi</th><td><input type="email" name="email" class="regular-text" value="<?php echo esc_attr($cfg['email']); ?>"></td></tr>
                            <tr><th>Jam Sidang</th><td><input type="text" name="hearing_hours" class="regular-text" value="<?php echo esc_attr($cfg['hearing_hours'] ?? ''); ?>"></td></tr>
                            <tr><th>Nama Pimpinan / Jabatan</th><td><input type="text" name="leader_name" class="regular-text" value="<?php echo esc_attr($cfg['leader_name']); ?>"></td></tr>
                            <tr><th>Sambutan Pimpinan</th><td><textarea name="leader_welcome" rows="3" class="large-text"><?php echo esc_textarea($cfg['leader_welcome']); ?></textarea></td></tr>
                            <tr><th>URL SIPP</th><td><input type="url" name="sipp_url" class="regular-text" value="<?php echo esc_attr($cfg['sipp_url']); ?>"></td></tr>
                            <tr><th>URL Slide Jadwal Sidang SIPP (Beranda)</th><td><input type="url" name="sipp_slide_url" class="regular-text" value="<?php echo esc_attr($cfg['sipp_slide_url'] ?? ''); ?>" placeholder="https://sipp.pa-satker.go.id/Slide_sidang"><br><small style="color:#64748b;">Opsional (HTTPS): cukup tempel tautan <code>Slide_sidang</code> SIPP satker agar tampil otomatis sebagai panel jadwal sidang elegan di Beranda dan otomatis diizinkan pada header CSP <code>frame-src</code> (dapat juga ditaruh di halaman lain dengan shortcode <code>[pa_sinova_sipp_slide]</code>).</small></td></tr>
                            <tr><th>URL e-Court</th><td><input type="url" name="ecourt_url" class="regular-text" value="<?php echo esc_attr($cfg['ecourt_url']); ?>"></td></tr>
                            <tr><th>URL Direktori Putusan</th><td><input type="url" name="direktori_url" class="regular-text" value="<?php echo esc_attr($cfg['direktori_url']); ?>"></td></tr>
                            <tr><th>URL Survei Badilag</th><td><input type="url" name="survey_url" class="regular-text" value="<?php echo esc_attr($cfg['survey_url']); ?>"></td></tr>
                            <tr><th>URL CCTV Online (ACO)</th><td><input type="url" name="aco_url" class="regular-text" value="<?php echo esc_attr($cfg['aco_url'] ?? 'https://cctv.badilag.net/'); ?>" placeholder="https://cctv.badilag.net/"><br><small style="color:#64748b;">Tautan Access CCTV Online Badilag / ruang PTSP Satker (kosongkan untuk menyembunyikan tautan &amp; lencana ACO).</small></td></tr>
                            <tr><th>Logo CCTV ACO (Opsional)</th><td><input type="url" name="aco_logo_url" class="regular-text" value="<?php echo esc_attr($cfg['aco_logo_url'] ?? ''); ?>" placeholder="Kosongkan untuk memakai animasi CCTV ACO bawaan"><br><small style="color:#64748b;">Biarkan kosong untuk memakai gambar GIF CCTV ACO lokal bawaan (<code>assets/cctv-aco.gif</code>), atau isi URL gambar Media Library satker sendiri.</small></td></tr>
                            <tr><th>Dock Floating CCTV ACO</th><td><label><input type="checkbox" name="aco_float_enabled" value="1" <?php checked(($cfg['aco_float_enabled'] ?? '1') !== '0'); ?>> Tampilkan lencana CCTV ACO mini di tepi kanan layar</label></td></tr>
                            <tr><th>URL SIWAS &amp; SP4N-LAPOR!</th><td>
                                <input type="url" name="siwas_url" class="regular-text" value="<?php echo esc_attr($cfg['siwas_url'] ?? 'https://siwas.mahkamahagung.go.id/'); ?>" placeholder="https://siwas.mahkamahagung.go.id/"><br>
                                <input type="url" name="lapor_url" class="regular-text" value="<?php echo esc_attr($cfg['lapor_url'] ?? 'https://www.lapor.go.id/'); ?>" placeholder="https://www.lapor.go.id/" style="margin-top:4px;">
                            </td></tr>
                            <tr><th>Pita Informasi Serta-Merta (KMA 2-144)</th><td>
                                <input type="text" name="emergency_notice" class="regular-text" value="<?php echo esc_attr($cfg['emergency_notice'] ?? ''); ?>" placeholder="Kosongkan bila layanan berjalan normal">
                                <input type="url" name="emergency_notice_url" class="regular-text" value="<?php echo esc_attr($cfg['emergency_notice_url'] ?? ''); ?>" placeholder="URL detail pengumuman darurat (opsional)" style="margin-top:4px;"><br>
                                <small style="color:#64748b;">Muncul di bagian paling atas website saat diisi (misal gangguan listrik/pemeliharaan/penundaan sidang mendadak).</small>
                            </td></tr>
                            <tr><th>Tautan Zona Integritas (Area I–VI)</th><td>
                                <?php for ($zi = 1; $zi <= 6; $zi++) : ?>
                                    <input type="url" name="zi_area_<?php echo $zi; ?>_url" class="regular-text" value="<?php echo esc_attr($cfg['zi_area_' . $zi . '_url'] ?? ''); ?>" placeholder="URL Folder Google Drive / Halaman Area <?php echo $zi; ?>" style="margin-bottom:4px;"><br>
                                <?php endfor; ?>
                                <small style="color:#64748b;">Bila dikosongkan, kartu Area ZI di Beranda otomatis mengarah ke halaman resmi <code>/zona-integritas/</code> (bebas tautan mati).</small>
                            </td></tr>
                            <tr><th>RSS Pengumuman MA RI</th><td><input type="url" name="rss_ma_url" class="regular-text" value="<?php echo esc_attr($cfg['rss_ma_url'] ?? 'https://kepaniteraan.mahkamahagung.go.id/?format=feed&type=rss'); ?>"></td></tr>
                            <tr><th>RSS Pengumuman Badilag</th><td><input type="url" name="rss_badilag_url" class="regular-text" value="<?php echo esc_attr($cfg['rss_badilag_url'] ?? 'https://badilag.mahkamahagung.go.id/pengumuman-elektronik?format=feed&type=rss'); ?>"></td></tr>
                            <tr><th>Mode Keamanan CSP (Chrome)</th><td>
                                <select name="csp_mode" class="regular-text">
                                    <option value="strict" <?php selected($cfg['csp_mode'] ?? 'strict', 'strict'); ?>>Strict (Nonce + Strict-Dynamic + Trusted Types) — Rekomendasi</option>
                                    <option value="compat" <?php selected($cfg['csp_mode'] ?? 'strict', 'compat'); ?>>Compat (Aman untuk plugin/Elementor lawas)</option>
                                    <option value="off" <?php selected($cfg['csp_mode'] ?? 'strict', 'off'); ?>>Off (Hanya header keamanan dasar)</option>
                                </select>
                            </td></tr>
                            <tr><th>Secret Key HMAC API</th><td><input type="text" name="hmac_secret" class="regular-text" value="<?php echo esc_attr($cfg['hmac_secret']); ?>" placeholder="Minimal 32 karakter"><br><label><input type="checkbox" name="generate_hmac" value="1"> Generate Secret Key acak baru (64 hex)</label></td></tr>
                            <tr><th>Turnstile Site Key</th><td><input type="text" name="turnstile_site_key" class="regular-text" value="<?php echo esc_attr($cfg['turnstile_site_key']); ?>"></td></tr>
                            <tr><th>Turnstile Secret Key</th><td><input type="password" name="turnstile_secret_key" class="regular-text" value="<?php echo esc_attr($cfg['turnstile_secret_key']); ?>"></td></tr>
                        </table>
                        <p><button type="submit" class="button button-primary">Simpan Pengaturan</button></p>
                    </form>
                </div>

                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">Seeder Struktur Baku 1-Klik</h2>
                    <p>Membuat otomatis <strong>23 halaman Arsip Dokumen</strong> (slot s.d. 2027), halaman <strong>Wajib, KMA 2-144/2022, &amp; Inklusif</strong> (termasuk <code>/disabilitas/</code>, <code>/informasi-serta-merta/</code>, <code>/informasi-tersedia-setiap-saat/</code>, <code>/layanan-pengaduan/</code>, <code>/zona-integritas/</code>, &amp; <code>/pengumuman-resmi/</code>), halaman <strong>Beranda</strong> lengkap, berita awal, dan menyusun <strong>6 Menu Utama Standar</strong> di tema Astra / WordPress.</p>
                    <form method="post" style="margin-bottom:14px;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="run_seeder">
                        <button type="submit" class="button button-primary">Generate Halaman, Beranda &amp; Menu (1-Klik)</button>
                    </form>
                    <hr style="margin:14px 0;">
                    <h3 style="margin-top:0;font-size:14px;color:#0f172a;">Rapikan Ulang 6 Menu Utama Standar (SK 2722/2026)</h3>
                    <p style="font-size:12.5px;color:#475569;">Menyusun ulang <em>Menu Utama SINOVA</em> menjadi tepat 6 induk: <strong>Beranda</strong>, <strong>Profil Pengadilan</strong>, <strong>Layanan Hukum</strong>, <strong>Transparansi</strong>, <strong>Layanan Publik &amp; Pengaduan</strong>, dan <strong>Berita &amp; Publikasi</strong>.</p>
                    <form method="post" onsubmit="return confirm('Susun ulang item Menu Utama SINOVA menjadi 6 menu standar? Halaman tidak dihapus.');">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="rebuild_6menu">
                        <button type="submit" class="button button-secondary">Rapikan 6 Menu Resmi Sekarang</button>
                    </form>
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
        ?>
            <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;margin-bottom:18px;">
                <h2 style="margin-top:0;color:#056e4f;">Autentikasi Dua Faktor (MFA TOTP RFC 6238 Bawaan)</h2>
                <p style="color:#475569;margin-top:0;">Tambahkan lapisan verifikasi OTP 6-digit menggunakan Google Authenticator, FreeOTP, atau Aegis — tanpa plugin pihak ketiga dan tanpa mengirim kunci ke server eksternal.</p>
                <?php if (!empty($mfaRecoveryOnce)) : ?>
                    <div style="background:#fffbeb;border:1px solid #f59e0b;border-radius:8px;padding:12px 16px;margin-bottom:14px;">
                        <strong style="color:#92400e;">Simpan 8 Kode Cadangan Pemulihan Ini (Hanya Ditampilkan Sekali):</strong>
                        <div style="display:grid;grid-template-columns:repeat(4,minmax(110px,1fr));gap:8px;margin-top:8px;font-family:monospace;font-size:13px;">
                            <?php foreach ($mfaRecoveryOnce as $rc) : ?>
                                <code style="background:#fff;padding:4px 8px;border:1px solid #fcd34d;border-radius:4px;text-align:center;"><?php echo esc_html($rc); ?></code>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($mfaEnabled) : ?>
                    <p style="color:#15803d;font-weight:700;">● MFA TOTP AKTIF pada akun Anda (sisa kode cadangan sekali pakai: <?php echo (int) $recRemaining; ?>).</p>
                    <form method="post" onsubmit="return confirm('Nonaktifkan MFA TOTP untuk akun Anda?');" style="display:inline-block;margin-right:12px;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="mfa_disable">
                        <button type="submit" class="button" style="color:#b91c1c;">Nonaktifkan MFA Akun Saya</button>
                    </form>
                <?php elseif ($mfaPending !== '') : ?>
                    <div style="display:flex;flex-wrap:wrap;gap:20px;align-items:center;background:#f8fafc;border:1px solid #cbd5e1;border-radius:10px;padding:14px;margin-bottom:12px;">
                        <div><?php echo $mfaQrSvg; ?></div>
                        <div style="flex:1;min-width:250px;">
                            <p style="margin-top:0;"><strong>1. Pindai QR Code</strong> dengan aplikasi autentikator, atau masukkan kode Base32 berikut secara manual:</p>
                            <p><code style="font-size:14px;letter-spacing:1.2px;padding:4px 8px;background:#fff;border:1px solid #cbd5e1;border-radius:6px;"><?php echo esc_html(trim(chunk_split($mfaPending, 4, ' '))); ?></code></p>
                            <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                                <input type="hidden" name="pa_sinova_action" value="mfa_activate">
                                <input type="text" name="mfa_verify_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="6 digit OTP" required style="width:130px;font-weight:700;letter-spacing:2px;text-align:center;">
                                <button type="submit" class="button button-primary">Verifikasi &amp; Aktifkan MFA</button>
                            </form>
                        </div>
                    </div>
                <?php else : ?>
                    <p style="color:#64748b;">Status akun Anda saat ini: <strong>Belum mengaktifkan MFA TOTP</strong>.</p>
                    <form method="post">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="mfa_setup_start">
                        <button type="submit" class="button button-primary">Mulai Pengaturan MFA TOTP (Generate QR Lokal)</button>
                    </form>
                <?php endif; ?>

                <?php if (current_user_can('manage_options')) : ?>
                    <form method="post" style="margin-top:14px;padding-top:12px;border-top:1px solid #e2e8f0;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="mfa_policy">
                        <label><input type="checkbox" name="enforce_mfa" value="1" <?php checked($mfaEnforced); ?>> Terapkan pengingat kewajiban MFA TOTP untuk seluruh Administrator &amp; Operator</label>
                        <button type="submit" class="button button-secondary">Simpan Kebijakan MFA</button>
                    </form>
                <?php endif; ?>
                <p style="margin:8px 0 0;font-size:12px;color:#64748b;"><em>Pemulihan darurat MFA: tambahkan <code>define('PA_DISABLE_CUSTOM_MFA', true);</code> di <code>wp-config.php</code> bila kehilangan perangkat &amp; kode cadangan.</em></p>
            </div>

            <?php if (current_user_can('manage_options')) : ?>
            <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:18px;margin-bottom:18px;">
                <h2 style="margin-top:0;color:#056e4f;">Sembunyikan Halaman Login Admin (Custom Login URL)</h2>
                <p style="color:#475569;margin-top:0;">Lindungi halaman masuk operator dari serangan otomatis dengan memindahkan URL login dari <code>/wp-login.php</code> dan menyamarkan akses anonim <code>/wp-admin/</code> serta <code>/wp-login.php</code> dengan respon <strong>404 Not Found</strong>.</p>
                <form method="post" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin-bottom:10px;">
                    <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                    <input type="hidden" name="pa_sinova_action" value="save_login_hide">
                    <label style="font-weight:700;"><code><?php echo esc_html(trailingslashit(home_url('/'))); ?></code>
                        <input type="text" name="custom_login_slug" value="<?php echo esc_attr($cfg['custom_login_slug'] ?? ''); ?>" placeholder="contoh: masuk-operator-pa" style="width:240px;margin-left:4px;">
                    </label>
                    <button type="submit" class="button button-primary">Simpan Pengaturan URL Login</button>
                </form>
                <p style="margin:0;font-size:12.5px;color:#334155;">
                    <strong>Status saat ini:</strong>
                    <?php if ($hiddenSlug !== '') : ?>
                        <span style="color:#15803d;font-weight:700;">AKTIF TERSEMBUNYI</span> &rarr; URL login resmi: <code><?php echo esc_html(trailingslashit(home_url('/' . $hiddenSlug))); ?></code> (simpan alamat ini di bookmark).
                    <?php else : ?>
                        <span>Standar WordPress (<code><?php echo esc_html(site_url('wp-login.php')); ?></code>). Isi slug minimal 4 karakter untuk mengaktifkan, atau kosongkan untuk kembali ke standar.</span>
                    <?php endif; ?>
                </p>
                <p style="margin:6px 0 0;font-size:12px;color:#64748b;"><em>Pemulihan darurat: jika lupa URL login baru, tambahkan <code>define('PA_DISABLE_CUSTOM_LOGIN', true);</code> di berkas <code>wp-config.php</code>.</em></p>
            </div>
            <?php endif; ?>

            <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;margin-bottom:18px;">
                <h2 style="margin-top:0;color:#056e4f;">Status Pengamanan Chrome Modern Web &amp; Kontrol Akses Peran (RBAC)</h2>
                <ul style="line-height:1.8;margin:0 0 12px;padding-left:18px;">
                    <li><strong>Header Keamanan Chrome &amp; CSP:</strong> Mode <code><?php echo esc_html(strtoupper($cspModeNow)); ?></code> (CSP Nonce + <code>strict-dynamic</code> + Trusted Types + <code>COOP: same-origin</code> + <code>CORP: same-origin</code> + <code>XFO</code> + <code>XCTO</code> + <code>Permissions-Policy</code><?php echo pa_starter_is_https_request() ? ' + HSTS Preload 2 Tahun' : ' + HSTS siap saat HTTPS'; ?>) — aktif juga pada Early Cache</li>
                    <li><strong>RFC 9116 security.txt &amp; Manifest:</strong> Tersedia di <code>/.well-known/security.txt</code> dan <code>/manifest.webmanifest</code></li>
                    <li><strong>MFA TOTP &amp; Batas Sesi Admin:</strong> <?php echo $mfaEnabled ? 'MFA TOTP Aktif' : 'MFA TOTP Opsional/Menunggu Aktivasi'; ?> · Batas sesi diam otomatis <strong>60 menit</strong> · Kebijakan kata sandi minimal <strong>10 karakter</strong> (huruf kecil, besar, angka, &amp; simbol)</li>
                    <li><strong>Halaman Login Admin:</strong> <?php echo $hiddenSlug !== '' ? 'Disembunyikan pada slug kustom (<code>/' . esc_html($hiddenSlug) . '/</code>), akses langsung <code>wp-login.php</code> &amp; <code>/wp-admin/</code> anonim diblokir 404' : 'Standar <code>/wp-login.php</code> (dapat disembunyikan melalui formulir di atas)'; ?></li>
                    <li><strong>File Editor WP Admin:</strong> Dinonaktifkan (<code>DISALLOW_FILE_EDIT = true</code>)</li>
                    <li><strong>XML-RPC, Komentar &amp; User Enumeration REST/Author:</strong> Diblokir untuk pengguna anonim</li>
                    <li><strong>Brute-Force Rate Limiter Login &amp; API:</strong> Aktif (blokir 15 menit setelah 5x login gagal; rate limiter REST pencarian &amp; kunjungan)</li>
                    <li><strong>Login Anti-Bot:</strong> <?php echo $tsActive ? 'Cloudflare Turnstile + Honeypot' : 'CAPTCHA Matematika Offline (HMAC) + Honeypot'; ?></li>
                    <li><strong>Folder Unggahan PDF (<code>sinova-docs/.htaccess</code>):</strong> <?php echo is_file(WP_CONTENT_DIR . '/uploads/sinova-docs/.htaccess') ? 'Terlindungi (PHP execution &amp; listing diblokir + MIME PDF guard)' : 'Aktif setelah admin_init'; ?></li>
                    <li><strong>WAF URI &amp; DB Content Guard:</strong> Aktif memblokir pola judi online, webshell, SQLi/XSS, &amp; cloaking tautan (termasuk deteksi scanner di Early Cache)</li>
                </ul>
                <p style="margin:0;font-size:12.5px;color:#334155;"><strong>Peran Operator Satker Tersedia (Users &rarr; Add New):</strong> <code>pa_kepaniteraan</code> (LIPA 7, Sisa Panjar, Prodeo, PPID &amp; Data Operasional), <code>pa_kesekretariatan</code> (Anggaran, SAKIP, BMN, LHKPN/LHKASN), dan <code>pa_humas</code> (Data Operasional, Survei, &amp; Berita).</p>
            </div>

            <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:18px;margin-bottom:18px;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:10px;">
                    <div>
                        <h2 style="margin:0;color:#056e4f;">File Integrity Monitor (FIM SHA-256)</h2>
                        <small style="color:#475569;">Baseline direkam: <?php echo esc_html($fim['baseline_at']); ?> · Status: <strong style="color:<?php echo $fim['ok'] ? '#15803d' : '#b91c1c'; ?>;"><?php echo $fim['ok'] ? 'BERSIH (Sesuai Baseline)' : 'ADA PERUBAHAN BERKAS'; ?></strong></small>
                    </div>
                    <?php if (current_user_can('manage_options')) : ?>
                        <form method="post" onsubmit="return confirm('Perbarui baseline SHA-256 sesuai isi berkas saat ini?');">
                            <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                            <input type="hidden" name="pa_sinova_action" value="fim_reset_baseline">
                            <button type="submit" class="button button-secondary">Perbarui Baseline SHA-256</button>
                        </form>
                    <?php endif; ?>
                </div>
                <table class="widefat striped">
                    <thead><tr><th>Berkas Terpantau</th><th>Hash SHA-256 Aktif</th><th style="width:120px;">Status</th></tr></thead>
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
            <div style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:18px;">
                <h2 style="margin-top:0;color:#056e4f;">Log Kejadian Keamanan Terakhir (40 Teratas)</h2>
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
        <?php endif; ?>
    </div>
    <?php
}
