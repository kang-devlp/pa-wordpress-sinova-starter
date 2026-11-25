<?php
/**
 * Plugin Name: PA SINOVA Starter Kit (Portal Resmi Pengadilan Agama)
 * Plugin URI: https://github.com/kang-devlp/pa-wordpress-sinova-starter
 * Description: Paket starter open source website resmi Pengadilan Agama (SK Dirjen Badilag 2722/2026): Editor Grid Beranda 12 Kolom Drag & Resize tanpa Elementor, Dashboard Jadwal Sidang & Live SIPP, API Push SIPP, 23 Modul Dokumen Publik, Audit 3 Pilar, MFA TOTP, dan Update 1-Klik + Rollback dari GitHub.
 * Version: 2.3.2
 * Requires at least: 6.2
 * Requires PHP: 8.1
 * Author: M. Agus Hayyudin
 * License: MIT
 * Text Domain: pa-sinova-starter
 */

defined('ABSPATH') || exit;

// Penjaga anti-bentrok: jika versi mu-plugins sudah terpasang di wp-content/mu-plugins/, jangan muat ulang
if (function_exists('pa_sinova_get_settings')) {
    add_action('admin_notices', static function () {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="notice notice-info"><p><strong>PA SINOVA Starter Kit:</strong> Berkas versi <code>mu-plugins</code> sudah aktif di <code>wp-content/mu-plugins/</code>, sehingga paket Plugin standar ini otomatis dilewati untuk mencegah bentrok. Anda boleh menonaktifkan salah satunya.</p></div>';
    });
    return;
}

defined('PA_SINOVA_RUNTIME_MODE') || define('PA_SINOVA_RUNTIME_MODE', 'plugin');
defined('PA_SINOVA_PLUGIN_FILE') || define('PA_SINOVA_PLUGIN_FILE', __FILE__);
defined('PA_SINOVA_ASSETS_DIR') || define('PA_SINOVA_ASSETS_DIR', __DIR__ . '/wordpress-mu-plugins/assets');
defined('PA_SINOVA_ASSETS_URL') || define('PA_SINOVA_ASSETS_URL', plugin_dir_url(__FILE__) . 'wordpress-mu-plugins/assets');

$paSinovaCoreFiles = array(
    '00-pa-sinova-config.php',
    'pa-home-page-cache.php',
    'pa-sinova-hub.php',
    'pa-official-feeds-and-data.php',
    'pa-ui-performance-a11y.php',
    'pa-security-hardening.php',
);

foreach ($paSinovaCoreFiles as $paFile) {
    $fullPath = __DIR__ . '/wordpress-mu-plugins/' . $paFile;
    if (is_file($fullPath)) {
        require_once $fullPath;
    }
}

register_activation_hook(__FILE__, static function () {
    // Deteksi otomatis website WordPress lama agar desain/menu/Elementor eksisting langsung terlindungi (Mode Adopsi)
    if (get_option('pa_sinova_starter_settings', null) === null && function_exists('pa_sinova_get_settings')) {
        $postCnt = wp_count_posts('post');
        $pageCnt = wp_count_posts('page');
        $pubPosts = isset($postCnt->publish) ? (int) $postCnt->publish : 0;
        $pubPages = isset($pageCnt->publish) ? (int) $pageCnt->publish : 0;
        $hasBuilder = defined('ELEMENTOR_VERSION') || class_exists('\Elementor\Plugin') || defined('ET_BUILDER_VERSION') || defined('WPB_VC_VERSION');
        if ($pubPosts > 2 || $pubPages > 3 || $hasBuilder) {
            $initCfg = pa_sinova_get_settings();
            $initCfg['preserve_existing_design'] = '1';
            $initCfg['csp_mode'] = 'compat';
            update_option('pa_sinova_starter_settings', $initCfg, false);
            set_transient('pa_sinova_auto_adoption_notice', array('posts' => $pubPosts, 'pages' => $pubPages), 7 * DAY_IN_SECONDS);
        }
    }
    if (function_exists('pa_sinova_register_roles')) {
        pa_sinova_register_roles();
    }
    if (function_exists('pa_sinova_ensure_table')) {
        pa_sinova_ensure_table();
    }
    if (function_exists('pa_portal_records_ensure_table')) {
        pa_portal_records_ensure_table();
    }
    flush_rewrite_rules();
});

// Tautan cepat langsung ke SINOVA Hub di halaman Daftar Plugin WP Admin
add_filter('plugin_action_links_' . plugin_basename(__FILE__), static function ($links) {
    if (!is_array($links)) {
        $links = array();
    }
    $hubLink  = '<a href="' . esc_url(admin_url('admin.php?page=pa-sinova-starter&tab=settings')) . '" style="font-weight:700;color:#056e4f;">Buka SINOVA Hub</a>';
    $homeLink = '<a href="' . esc_url(admin_url('admin.php?page=pa-sinova-starter&tab=homepage')) . '">Desain Beranda</a>';
    array_unshift($links, $hubLink, $homeLink);
    return $links;
});

// Integrasi notifikasi Update bawaan WordPress (WP Admin -> Plugins) langsung dari GitHub resmi
add_filter('pre_set_site_transient_update_plugins', static function ($transient) {
    if (!is_object($transient) || empty($transient->checked) || !function_exists('pa_sinova_github_check_update')) {
        return $transient;
    }
    $upd = pa_sinova_github_check_update(false);
    if (empty($upd['ok']) || empty($upd['has_update']) || empty($upd['download_url'])) {
        return $transient;
    }
    $pluginBasename = plugin_basename(__FILE__);
    $transient->response[$pluginBasename] = (object) array(
        'slug'        => 'pa-sinova-starter',
        'plugin'      => $pluginBasename,
        'new_version' => (string) $upd['latest'],
        'url'         => 'https://github.com/' . (defined('PA_SINOVA_GITHUB_REPO') ? PA_SINOVA_GITHUB_REPO : 'kang-devlp/pa-wordpress-sinova-starter'),
        'package'     => (string) $upd['download_url'],
        'requires'    => '6.2',
        'requires_php'=> (string) ($upd['requires_php'] ?? '8.1'),
    );
    return $transient;
});

add_filter('plugins_api', static function ($result, $action, $args) {
    if ($action !== 'plugin_information' || !is_object($args) || ($args->slug ?? '') !== 'pa-sinova-starter') {
        return $result;
    }
    $upd = function_exists('pa_sinova_github_check_update') ? pa_sinova_github_check_update(false) : array();
    return (object) array(
        'name'          => 'PA SINOVA Starter Kit (Portal Resmi Pengadilan Agama)',
        'slug'          => 'pa-sinova-starter',
        'version'       => (string) ($upd['latest'] ?? (defined('PA_SINOVA_STARTER_VERSION') ? PA_SINOVA_STARTER_VERSION : '2.2.0')),
        'author'        => '<a href="https://github.com/kang-devlp">M. Agus Hayyudin</a>',
        'homepage'      => 'https://github.com/' . (defined('PA_SINOVA_GITHUB_REPO') ? PA_SINOVA_GITHUB_REPO : 'kang-devlp/pa-wordpress-sinova-starter'),
        'requires'      => '6.2',
        'requires_php'  => (string) ($upd['requires_php'] ?? '8.1'),
        'last_updated'  => (string) ($upd['released_at'] ?? ''),
        'download_link' => (string) ($upd['download_url'] ?? ''),
        'sections'      => array(
            'description' => '<p>Starter kit open source website resmi Pengadilan Agama sesuai standar SK Dirjen Badilag 2722/2026: Beranda Dinamis tanpa Elementor, Dashboard Jadwal Sidang &amp; Live SIPP, 23 modul arsip dokumen, Audit 3 Pilar, MFA TOTP, dan keamanan Chrome Modern Web.</p>',
            'changelog'   => '<p>' . esc_html((string) ($upd['changelog'] ?? 'Pembaruan terbaru dari repositori resmi GitHub.')) . '</p>',
        ),
    );
}, 20, 3);
