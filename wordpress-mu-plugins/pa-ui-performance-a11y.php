<?php
/**
 * Plugin Name: PA SINOVA Starter - UI Portal Resmi PA, Pencarian, Statistik Pengunjung, Sidebar Halaman Dalam & Aksesibilitas Lengkap
 * Description: Menyatukan palet Hijau Gelap (#056e4f) & Royal Gold (#C9A227) untuk Astra Free maupun tema blok WordPress, bebas istilah internal di tampilan publik, kotak pencarian header, statistik pengunjung privasi ter-hash, sidebar & breadcrumb halaman dalam, serta dock disabilitas dengan Pembaca Suara Bahasa Indonesia.
 * Version: 1.3.0-starter
 */

defined('ABSPATH') || exit;

// 1. Satukan Webfont menjadi 'Inter' tunggal + Hapus Bloat Emoji/Dashicons/Resource Hints + Optimasi CWV
add_filter('astra_google_fonts', '__return_empty_array', 999);
add_filter('elementor/frontend/print_google_fonts', '__return_false', 999);

add_action('init', static function () {
    if (is_admin()) {
        return;
    }
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    add_filter('emoji_svg_url', '__return_false');
}, 15);

add_filter('wp_resource_hints', static function ($urls, $relationType) {
    if (!is_array($urls)) {
        return $urls;
    }
    return array_values(array_filter($urls, static function ($url) {
        $s = is_array($url) ? (string) ($url['href'] ?? '') : (string) $url;
        return stripos($s, 's.w.org') === false;
    }));
}, 10, 2);

add_action('wp_enqueue_scripts', static function () {
    if (!is_admin() && !is_user_logged_in()) {
        wp_deregister_style('dashicons');
    }
}, 100);

add_filter('script_loader_tag', static function ($tag, $handle) {
    if (is_admin() || (function_exists('is_customize_preview') && is_customize_preview()) || (defined('PA_SERVING_CUSTOM_LOGIN') && PA_SERVING_CUSTOM_LOGIN)) {
        return $tag;
    }
    if (in_array((string) $handle, array('astra-theme-js'), true) && strpos($tag, ' defer') === false && strpos($tag, ' async') === false) {
        return str_replace(' src=', ' defer src=', $tag);
    }
    return $tag;
}, 10, 2);

add_filter('wp_get_attachment_image_attributes', static function ($attr) {
    if (!is_array($attr)) {
        return $attr;
    }
    if (empty($attr['decoding'])) {
        $attr['decoding'] = 'async';
    }
    if (empty($attr['loading']) && (($attr['fetchpriority'] ?? '') !== 'high')) {
        $attr['loading'] = 'lazy';
    }
    return $attr;
}, 10, 1);

function pa_sinova_is_adoption_mode(): bool {
    if (!function_exists('pa_sinova_get_settings')) {
        return false;
    }
    $cfg = pa_sinova_get_settings();
    return !empty($cfg['preserve_existing_design']) && (string) $cfg['preserve_existing_design'] === '1';
}

add_filter('body_class', static function ($classes) {
    if (is_admin() || pa_sinova_is_adoption_mode()) {
        return $classes;
    }
    $classes[] = 'pa-sinova-portal';
    $classes[] = (function_exists('wp_is_block_theme') && wp_is_block_theme()) ? 'pa-sinova-block-shell' : 'pa-sinova-classic-shell';
    return $classes;
});

// 2. Statistik Kunjungan Website Privasi Aman (Hash IP Harian — Mendukung B5.1)
function pa_starter_get_visitor_stats(): array {
    $stats = get_option('pa_sinova_visitor_stats', array());
    if (!is_array($stats)) {
        $stats = array();
    }
    $todayKey = gmdate('Y-m-d', time() + 7 * 3600);
    $monthKey = substr($todayKey, 0, 7);

    return array(
        'today' => (($stats['day_key'] ?? '') === $todayKey) ? (int) ($stats['day_count'] ?? 0) : 0,
        'month' => (($stats['month_key'] ?? '') === $monthKey) ? (int) ($stats['month_count'] ?? 0) : 0,
        'total' => (int) ($stats['total_count'] ?? 0),
    );
}

function pa_starter_record_visitor_hit(): array {
    if (PHP_SAPI === 'cli' || is_admin()) {
        return pa_starter_get_visitor_stats();
    }
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua !== '' && preg_match('/bot|crawl|spider|slurp|curl|wget|headless/i', $ua)) {
        return pa_starter_get_visitor_stats();
    }

    $todayKey = gmdate('Y-m-d', time() + 7 * 3600);
    $monthKey = substr($todayKey, 0, 7);
    $stats    = get_option('pa_sinova_visitor_stats', array());
    if (!is_array($stats)) {
        $stats = array();
    }

    if (($stats['day_key'] ?? '') !== $todayKey) {
        $stats['day_key']    = $todayKey;
        $stats['day_count']  = 0;
        $stats['day_hashes'] = array();
    }
    if (($stats['month_key'] ?? '') !== $monthKey) {
        $stats['month_key']   = $monthKey;
        $stats['month_count'] = 0;
    }
    if (!isset($stats['total_count'])) {
        $stats['total_count'] = 0;
    }

    $ip   = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $salt = function_exists('wp_salt') ? wp_salt('auth') : __FILE__;
    $hash = substr(hash_hmac('sha256', $todayKey . '|' . $ip . '|' . $ua, $salt), 0, 16);

    $seenHashes = isset($stats['day_hashes']) && is_array($stats['day_hashes']) ? $stats['day_hashes'] : array();
    if (!isset($seenHashes[$hash])) {
        if (count($seenHashes) < 2000) {
            $seenHashes[$hash] = 1;
        }
        $stats['day_hashes']  = $seenHashes;
        $stats['day_count']   = (int) $stats['day_count'] + 1;
        $stats['month_count'] = (int) $stats['month_count'] + 1;
        $stats['total_count'] = (int) $stats['total_count'] + 1;
        update_option('pa_sinova_visitor_stats', $stats, false);
    }

    return array(
        'today' => (int) $stats['day_count'],
        'month' => (int) $stats['month_count'],
        'total' => (int) $stats['total_count'],
    );
}

add_action('template_redirect', static function () {
    if (!is_admin() && !is_feed() && !is_404()) {
        pa_starter_record_visitor_hit();
    }
}, 20);

add_action('rest_api_init', static function () {
    register_rest_route('pa-portal/v1', '/visit', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => static function () {
            if (function_exists('pa_starter_rate_limit') && !pa_starter_rate_limit('visit', 30, 60)) {
                return new WP_REST_Response(pa_starter_get_visitor_stats(), 200);
            }
            return new WP_REST_Response(pa_starter_record_visitor_hit(), 200);
        },
    ));
});

add_shortcode('pa_visitor_stats', static function () {
    $st = pa_starter_get_visitor_stats();
    ob_start();
    ?>
    <div class="pa-vstats-grid">
        <div class="pa-vstat-box"><span>Pengunjung Hari Ini</span><strong id="pa-page-vis-today"><?php echo esc_html(number_format($st['today'], 0, ',', '.')); ?></strong></div>
        <div class="pa-vstat-box"><span>Pengunjung Bulan Ini</span><strong id="pa-page-vis-month"><?php echo esc_html(number_format($st['month'], 0, ',', '.')); ?></strong></div>
        <div class="pa-vstat-box"><span>Total Kunjungan</span><strong id="pa-page-vis-total"><?php echo esc_html(number_format($st['total'], 0, ',', '.')); ?></strong></div>
    </div>
    <p style="font-size:12.5px;color:#475569;margin-top:10px;">Penghitung kunjungan menyimpan sidik harian anonim (hash satu arah tanpa menyimpan alamat IP mentah pengunjung).</p>
    <?php
    return (string) ob_get_clean();
});

add_action('wp_head', static function () {
    if (is_admin()) {
        return;
    }
    echo '<meta name="color-scheme" content="light dark">' . "\n";
    echo '<script id="pa-a11y-boot">try{var r=document.documentElement,t=localStorage.getItem("pa_starter_theme"),c=localStorage.getItem("pa_starter_contrast"),z=localStorage.getItem("pa_starter_zoom"),rd=localStorage.getItem("pa_starter_readable"),lk=localStorage.getItem("pa_starter_links"),gs=localStorage.getItem("pa_starter_grayscale"),nm=localStorage.getItem("pa_starter_nomotion");if(!t&&/[?&]theme=dark(?:&|$)/.test(location.search))t="dark";if(t==="dark")r.setAttribute("data-pa-theme","dark");if(c==="high")r.setAttribute("data-pa-contrast","high");if(z)r.style.fontSize=z;if(rd==="1")r.setAttribute("data-pa-readable","1");if(lk==="1")r.setAttribute("data-pa-links","1");if(gs==="1")r.setAttribute("data-pa-grayscale","1");if(nm==="1")r.setAttribute("data-pa-nomotion","1");}catch(e){}</script>' . "\n";
}, 2);

// 3. Shortcode [pa_sinova_welcome_section]: Sambutan Pimpinan + Standar Maklumat Pelayanan PTSP
add_shortcode('pa_sinova_welcome_section', static function () {
    $cfg     = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $satker  = $cfg['satker_name'] ?? 'Pengadilan Agama';
    $leader  = $cfg['leader_name'] ?? 'Ketua Pengadilan Agama';
    $welcome = $cfg['leader_welcome'] ?? 'Selamat datang di portal keterbukaan informasi resmi satuan kerja Pengadilan Agama.';
    $phone   = $cfg['phone'] ?? '0811-0000-0000';
    $email   = $cfg['email'] ?? 'info@pengadilan.go.id';

    ob_start();
    ?>
    <section class="pa-welcome-grid" aria-label="Sambutan Pimpinan dan Maklumat Pelayanan">
        <div class="pa-welcome-card">
            <span class="pa-wc-kicker">Sambutan Pimpinan</span>
            <h3><?php echo esc_html($leader); ?> — <?php echo esc_html($satker); ?></h3>
            <p class="pa-wc-body"><?php echo esc_html($welcome); ?></p>
            <div class="pa-wc-links">
                <a href="./visi-dan-misi/">Visi &amp; Misi</a>
                <a href="./struktur-organisasi/">Struktur Organisasi</a>
                <a href="./profil-pegawai/">Profil Pejabat &amp; ASN</a>
            </div>
        </div>
        <div class="pa-maklumat-card">
            <span class="pa-mk-kicker">Maklumat Pelayanan &amp; PTSP</span>
            <h3>Pelayanan Prima Berintegritas</h3>
            <p class="pa-mk-quote">"Dengan ini kami menyatakan sanggup menyelenggarakan pelayanan sesuai standar pelayanan yang telah ditetapkan dan apabila tidak menepati janji siap menerima sanksi sesuai peraturan perundang-undangan."</p>
            <ul class="pa-mk-hours">
                <li><strong>Senin – Kamis:</strong> 08.00 – 16.30 WIB</li>
                <li><strong>Jumat:</strong> 08.00 – 17.00 WIB</li>
                <li><strong>Layanan PTSP:</strong> <?php echo esc_html($phone . ' · ' . $email); ?></li>
            </ul>
            <a class="pa-mk-btn" href="./standar-maklumat-pelayanan/">Baca Standar &amp; Maklumat Pelayanan →</a>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
});

// 3b. Shortcode [pa_sinova_aco_card]: Logo Resmi & Akses CCTV ACO Ditjen Badilag
add_shortcode('pa_sinova_aco_card', static function () {
    $cfg    = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $acoUrl = trim((string) ($cfg['aco_url'] ?? 'https://cctv.badilag.net/'));
    if ($acoUrl === '') {
        return '';
    }
    ob_start();
    ?>
    <section class="pa-aco-banner" aria-label="Access CCTV Online (ACO) Ditjen Badilag">
        <div class="pa-aco-logo" aria-hidden="true">
            <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="2" y="2" width="60" height="60" rx="14" fill="#022c22" stroke="#E5C158" stroke-width="2.5"/>
                <path d="M15 26L40 17L45 29L20 38L15 26Z" fill="#056e4f" stroke="#E5C158" stroke-width="2" stroke-linejoin="round"/>
                <path d="M45 22L51 20V28L45 26" fill="#C9A227"/>
                <path d="M27 36V44H17" stroke="#E5C158" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="17" cy="44" r="2.5" fill="#E5C158"/>
                <circle cx="49" cy="13" r="3.5" fill="#22c55e"/>
                <text x="39" y="52" text-anchor="middle" fill="#E5C158" font-family="Inter,system-ui,sans-serif" font-size="11" font-weight="900" letter-spacing="0.8">ACO</text>
            </svg>
        </div>
        <div class="pa-aco-body">
            <div class="pa-aco-kicker-row">
                <span class="pa-aco-pill"><span class="pa-aco-dot" aria-hidden="true"></span> LIVE MONITORING · ACO BADILAG</span>
                <span class="pa-aco-std">Mahkamah Agung RI</span>
            </div>
            <h3>Access CCTV Online (ACO) — Transparansi Layanan Publik</h3>
            <p>Pemantauan langsung ruang Pelayanan Terpadu Satu Pintu (PTSP) dan area layanan masyarakat sebagai wujud peradilan yang bersih dan transparan.</p>
        </div>
        <div class="pa-aco-cta">
            <a class="pa-aco-btn" href="<?php echo esc_url($acoUrl); ?>" target="_blank" rel="noopener noreferrer">Pantau CCTV ACO ↗</a>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
});

// 4. Shortcode [pa_sinova_latest_posts count="3"]: Berita & Pengumuman Terkini Dinamis
add_shortcode('pa_sinova_latest_posts', static function ($atts) {
    $cfgAtts = shortcode_atts(array('count' => 3), $atts, 'pa_sinova_latest_posts');
    $count   = max(1, min(6, (int) $cfgAtts['count']));
    $posts   = get_posts(array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $count,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));

    if (empty($posts)) {
        return '';
    }

    ob_start();
    ?>
    <section class="pa-section-block pa-news-section" aria-label="Berita dan Pengumuman Terbaru">
        <div class="pa-news-head">
            <div>
                <h2 class="pa-starter-heading" style="text-align:left;">BERITA &amp; PENGUMUMAN TERKINI <span class="pa-hint-i" aria-hidden="true">i</span></h2>
                <p class="pa-starter-sub" style="text-align:left;margin-bottom:0;">Informasi Kegiatan, Publikasi, dan Pemberitahuan Resmi Satuan Kerja</p>
            </div>
            <a class="pa-news-all" href="<?php echo esc_url(home_url('/?post_type=post')); ?>" data-pa-tip="Lihat seluruh arsip berita dan pengumuman resmi.">Semua Berita →</a>
        </div>
        <div class="pa-news-grid">
            <?php foreach ($posts as $p) :
                $permalink = get_permalink($p);
                $dateStr   = get_the_date('d M Y', $p);
                $excerpt   = wp_trim_words(wp_strip_all_tags((string) ($p->post_excerpt ?: $p->post_content)), 20, '...');
            ?>
                <article class="pa-news-card">
                    <span class="pa-news-date"><?php echo esc_html($dateStr); ?></span>
                    <h3><a href="<?php echo esc_url((string) $permalink); ?>"><?php echo esc_html(get_the_title($p)); ?></a></h3>
                    <p><?php echo esc_html($excerpt); ?></p>
                    <a class="pa-news-more" href="<?php echo esc_url((string) $permalink); ?>">Baca selengkapnya →</a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
});

// 5. Tambahkan Logo Satker & Kotak Pencarian Internal ke Baris 1 Header Astra
add_action('astra_render_header_column', static function ($row, $section) {
    if ($row !== 'primary' || $section !== 'left' || is_admin() || pa_sinova_is_adoption_mode()) {
        return;
    }
    $cfg     = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $logoUrl = trim((string) ($cfg['logo_url'] ?? ''));
    ?>
    <form role="search" method="get" class="pa-header-search-form" action="<?php echo esc_url(home_url('/')); ?>" aria-label="Pencarian Informasi Website">
        <input type="search" name="s" class="pa-header-search-input" aria-label="Cari dokumen, berita, dan layanan" placeholder="Cari dokumen, berita, layanan..." value="<?php echo esc_attr(get_search_query()); ?>">
        <button type="submit" class="pa-header-search-btn" aria-label="Cari">Cari</button>
        <button type="button" id="pa-cmdk-open" class="pa-cmdk-trigger" aria-haspopup="dialog" data-pa-tip="Pencarian Instan Lintas Dokumen &amp; Jadwal (Ctrl+K)">⌘ <kbd>Ctrl K</kbd></button>
    </form>
    <?php if ($logoUrl !== '') : ?>
        <img src="<?php echo esc_url($logoUrl); ?>" alt="Logo Pengadilan" class="pa-header-custom-logo" width="44" height="44" fetchpriority="high" decoding="async">
    <?php endif;
}, 20, 2);

// 6. Breadcrumb + Sidebar Navigasi Pilar Otomatis pada Halaman Dalam (!is_front_page() && is_page())
add_filter('the_content', static function ($content) {
    if (is_admin() || !is_page() || is_front_page() || !is_main_query() || !in_the_loop() || pa_sinova_is_adoption_mode()) {
        return $content;
    }
    $postId = (int) get_the_ID();
    $items  = wp_get_nav_menu_items('Menu Utama SINOVA');
    if (empty($items) || !is_array($items)) {
        return $content;
    }

    $byId      = array();
    $children  = array();
    $parentId  = 0;
    foreach ($items as $it) {
        $id = (int) $it->ID;
        $byId[$id] = $it;
        $pid = (int) $it->menu_item_parent;
        if ($pid > 0) {
            $children[$pid][] = $it;
        }
        if ((int) $it->object_id === $postId && $pid > 0) {
            $parentId = $pid;
        }
    }
    if ($parentId <= 0 || empty($byId[$parentId]) || empty($children[$parentId])) {
        return $content;
    }

    $parentTitle = (string) $byId[$parentId]->title;
    $siblings    = $children[$parentId];

    ob_start();
    ?>
    <nav class="pa-breadcrumb" aria-label="Breadcrumb">
        <a href="<?php echo esc_url(home_url('/')); ?>">Beranda</a>
        <span aria-hidden="true">›</span>
        <span><?php echo esc_html($parentTitle); ?></span>
        <span aria-hidden="true">›</span>
        <strong><?php echo esc_html(get_the_title($postId)); ?></strong>
    </nav>
    <div class="pa-inner-layout">
        <aside class="pa-inner-sidebar" aria-label="Navigasi <?php echo esc_attr($parentTitle); ?>">
            <div class="pa-sidebar-head"><?php echo esc_html($parentTitle); ?></div>
            <ul class="pa-sidebar-list">
                <?php foreach ($siblings as $sib) :
                    $isCur = ((int) $sib->object_id === $postId);
                ?>
                    <li>
                        <a href="<?php echo esc_url((string) $sib->url); ?>" class="<?php echo $isCur ? 'is-current' : ''; ?>" <?php echo $isCur ? 'aria-current="page"' : ''; ?>>
                            <?php echo esc_html((string) $sib->title); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <div class="pa-inner-main">
            <?php echo $content; ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}, 30);

// 7. CSS Terpadu Portal Resmi Pengadilan Agama (Astra Free + Blok Theme)
add_action('wp_head', static function () {
    if (is_admin()) {
        return;
    }
    $cfg       = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $isOrange  = (($cfg['accent_palette'] ?? 'gold') === 'badilag_orange');
    $goldHex   = $isOrange ? '#ff9900' : '#C9A227';
    $goldLight = $isOrange ? '#ffb84d' : '#E5C158';
    ?>
<style id="pa-sinova-starter-ui-css">
:root {
    --pa-green: #056e4f;
    --pa-green-dark: #043927;
    --pa-green-deep: #022c22;
    --pa-gold: <?php echo esc_html($goldHex); ?>;
    --pa-gold-light: <?php echo esc_html($goldLight); ?>;
    --e-global-typography-primary-font-family: 'Inter', system-ui, -apple-system, sans-serif;
    --e-global-typography-secondary-font-family: 'Inter', system-ui, -apple-system, sans-serif;
}
body, button, input, select, textarea {
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}
.screen-reader-text {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
a:focus-visible,
button:focus-visible,
input:focus-visible,
select:focus-visible,
textarea:focus-visible,
summary:focus-visible,
[tabindex]:focus-visible {
    outline: 3px solid #056e4f !important;
    outline-offset: 2px !important;
}
.pa-shell-topbar :focus-visible,
.pa-shell-footer :focus-visible,
.pa-hero-banner :focus-visible,
.main-header-bar :focus-visible,
html[data-pa-theme="dark"] :focus-visible {
    outline-color: #E5C158 !important;
}
.pa-emergency-bar {
    background: #991b1b;
    color: #ffffff;
    border-bottom: 2px solid #fca5a5;
    padding: 9px 16px;
    font-size: 13px;
    font-weight: 700;
}
.pa-emergency-bar a { color: #fef08a; text-decoration: underline; margin-left: 8px; }
.pa-scroll-reveal, .elementor-invisible {
    opacity: 1 !important;
    visibility: visible !important;
    animation: none !important;
}

<?php if (!pa_sinova_is_adoption_mode()) : ?>
body.pa-sinova-block-shell .wp-site-blocks > header.wp-block-template-part,
body.pa-sinova-block-shell .wp-site-blocks > footer.wp-block-template-part,
body.pa-sinova-block-shell header:has(.wp-block-page-list),
body.home.pa-sinova-block-shell .wp-block-post-title,
body.pa-sinova-classic-shell #colophon.site-footer,
body.home.pa-sinova-classic-shell .entry-header {
    display: none !important;
}
body.pa-sinova-block-shell .wp-site-blocks .is-layout-constrained > :where(:not(.alignfull)),
body.pa-sinova-classic-shell .ast-container {
    max-width: 1200px !important;
}
body.pa-sinova-classic-shell.home .site-content .ast-container {
    padding-top: 12px;
    padding-bottom: 24px;
}
body.pa-sinova-block-shell .wp-block-post-title,
body.pa-sinova-classic-shell:not(.home) .entry-title {
    color: var(--pa-green-dark);
    font-weight: 800;
    font-size: clamp(1.35rem, 2.3vw, 1.95rem);
    border-bottom: 3px solid var(--pa-gold);
    padding-bottom: 8px;
    margin-bottom: 16px;
}

/* Styling Header Native Tema Astra menjadi 2 Baris Rapi */
body.pa-sinova-classic-shell .main-header-bar {
    background: linear-gradient(135deg, #043927 0%, #056e4f 100%) !important;
    border-bottom: 3px solid #C9A227 !important;
    color: #fff !important;
    padding: 0 !important;
    line-height: 1.4 !important;
}
body.pa-sinova-classic-shell #ast-desktop-header .site-primary-header-wrap {
    max-width: 1200px !important;
    padding: 0 !important;
}
body.pa-sinova-classic-shell #ast-desktop-header .ast-builder-grid-row {
    display: flex !important;
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 0 !important;
}
body.pa-sinova-classic-shell #ast-desktop-header .site-header-primary-section-left {
    width: 100% !important;
    max-width: 100% !important;
    padding: 13px 16px !important;
    border-bottom: 1px solid rgba(201, 162, 39, 0.35);
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 12px !important;
}
body.pa-sinova-classic-shell #ast-desktop-header .site-header-primary-section-left .ast-builder-layout-element[data-section="title_tagline"] {
    order: 1;
}
body.pa-sinova-classic-shell #ast-desktop-header .pa-header-custom-logo {
    order: 0;
    border-radius: 8px;
    background: #fff;
    padding: 3px;
    object-fit: contain;
}
body.pa-sinova-classic-shell #ast-desktop-header .pa-header-search-form {
    order: 2;
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 6px;
}
.pa-header-search-input {
    width: 265px;
    min-width: 190px;
    padding: 8px 14px !important;
    border-radius: 999px !important;
    border: 1px solid rgba(229, 193, 88, 0.55) !important;
    background: rgba(255, 255, 255, 0.12) !important;
    color: #ffffff !important;
    font-size: 12.5px !important;
    line-height: 1.3 !important;
    outline: none;
}
.pa-header-search-input::placeholder { color: #d1fae5; }
.pa-header-search-input:focus { background: rgba(2, 44, 34, 0.75) !important; border-color: #E5C158 !important; }
.pa-header-search-btn {
    white-space: nowrap !important;
    flex-shrink: 0 !important;
    padding: 8px 16px !important;
    border-radius: 999px !important;
    border: none !important;
    background: #C9A227 !important;
    color: #022c22 !important;
    font-size: 12.5px !important;
    line-height: 1.2 !important;
    font-weight: 800 !important;
    cursor: pointer;
}
.ast-mobile-header-wrap .pa-header-search-form,
.ast-mobile-header-wrap .pa-header-custom-logo { display: none !important; }

body.pa-sinova-classic-shell #ast-desktop-header .site-header-primary-section-right {
    width: 100% !important;
    max-width: 100% !important;
    background: rgba(2, 44, 34, 0.55);
    padding: 0 8px !important;
    justify-content: flex-start !important;
}
body.pa-sinova-classic-shell #ast-desktop-header .ast-builder-menu-1,
body.pa-sinova-classic-shell #ast-desktop-header .ast-main-header-bar-alignment,
body.pa-sinova-classic-shell #ast-desktop-header .main-header-bar-navigation,
body.pa-sinova-classic-shell #ast-desktop-header .main-navigation {
    width: 100% !important;
    line-height: 1.3 !important;
}
body.pa-sinova-classic-shell .site-title {
    font-size: clamp(1.1rem, 2vw, 1.42rem) !important;
    font-weight: 800 !important;
    line-height: 1.25 !important;
}
body.pa-sinova-classic-shell .site-title a,
body.pa-sinova-classic-shell .site-description { color: #ffffff !important; }
body.pa-sinova-classic-shell .site-description { color: #d1fae5 !important; font-size: 12px !important; }
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .main-header-menu {
    display: flex !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    justify-content: flex-start !important;
    gap: 2px !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .main-header-menu > .menu-item {
    flex: 0 0 auto !important;
    line-height: 1.3 !important;
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item > .menu-link,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .ast-builder-menu-1 .main-header-menu > .menu-item > .menu-link {
    color: #ffffff !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    line-height: 1.3 !important;
    height: auto !important;
    padding: 11px 11px !important;
    white-space: nowrap !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 4px !important;
    border-bottom: 2px solid transparent !important;
    transition: background-color 0.15s ease, color 0.15s ease;
}
@media (max-width: 1180px) {
    body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item > .menu-link,
    body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .ast-builder-menu-1 .main-header-menu > .menu-item > .menu-link {
        padding: 10px 8px !important;
        font-size: 12px !important;
    }
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item > .menu-link:hover,
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .current-menu-item > .menu-link,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .ast-builder-menu-1 .main-header-menu > .menu-item > .menu-link:hover,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .ast-builder-menu-1 .main-header-menu > .current-menu-item > .menu-link {
    color: #E5C158 !important;
    background: rgba(201, 162, 39, 0.16) !important;
    border-bottom-color: #E5C158 !important;
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item > .menu-link .ast-header-navigation-arrow,
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item > .menu-link svg {
    color: #E5C158 !important;
    fill: currentColor !important;
    width: 11px;
    height: 11px;
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 .sub-menu,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .main-header-menu .sub-menu {
    background: #ffffff !important;
    border: 1px solid #d5e6dc !important;
    border-top: 3px solid #C9A227 !important;
    border-radius: 0 0 10px 10px !important;
    box-shadow: 0 14px 32px rgba(4, 57, 39, 0.18) !important;
    width: 280px !important;
    max-height: 70vh;
    overflow-y: auto;
    line-height: 1.35 !important;
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item:nth-last-child(-n+2) > .sub-menu,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .main-header-menu > .menu-item:nth-last-child(-n+2) > .sub-menu {
    left: auto !important;
    right: 0 !important;
}
body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu .menu-link {
    color: #043927 !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    line-height: 1.4 !important;
    padding: 9px 14px !important;
    white-space: normal !important;
}
body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu .menu-link:hover {
    background: linear-gradient(135deg, #043927 0%, #056e4f 100%) !important;
    color: #E5C158 !important;
}
body.pa-sinova-classic-shell .ast-mobile-header-wrap .ast-primary-header-bar { padding: 10px 14px !important; }
body.pa-sinova-classic-shell .ast-mobile-menu-buttons .menu-toggle { background: rgba(201, 162, 39, 0.2) !important; border-color: #C9A227 !important; color: #E5C158 !important; }
body.pa-sinova-classic-shell .ast-mobile-popup-drawer .ast-mobile-popup-inner { background: #043927 !important; }
body.pa-sinova-classic-shell .ast-mobile-popup-drawer .menu-link { color: #ffffff !important; border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important; }
<?php endif; ?>

/* Breadcrumb & Sidebar Halaman Dalam */
.pa-breadcrumb {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    border-radius: 10px;
    background: #f4faf7;
    border: 1px solid #d5e6dc;
    font-size: 12.5px;
    color: #475569;
    margin-bottom: 18px;
}
.pa-breadcrumb a { color: #056e4f; font-weight: 700; text-decoration: none; }
.pa-breadcrumb a:hover { text-decoration: underline; }
.pa-inner-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 22px;
    align-items: start;
}
@media (max-width: 900px) { .pa-inner-layout { grid-template-columns: 1fr; } }
.pa-inner-sidebar {
    background: #ffffff;
    border: 1px solid #d5e6dc;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 6px 18px rgba(4, 57, 39, 0.05);
}
.pa-sidebar-head {
    padding: 13px 16px;
    background: linear-gradient(135deg, #043927 0%, #056e4f 100%);
    border-bottom: 3px solid #C9A227;
    color: #ffffff;
    font-weight: 800;
    font-size: 13.5px;
}
.pa-sidebar-list {
    list-style: none;
    margin: 0;
    padding: 8px;
}
.pa-sidebar-list li { margin-bottom: 4px; }
.pa-sidebar-list a {
    display: block;
    padding: 9px 12px;
    border-radius: 8px;
    color: #1e293b;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none !important;
    transition: background-color 0.14s ease, color 0.14s ease;
}
.pa-sidebar-list a:hover { background: #ecfdf5; color: #056e4f; }
.pa-sidebar-list a.is-current {
    background: #056e4f;
    color: #E5C158 !important;
    font-weight: 700;
}
.pa-inner-main { min-width: 0; }
.pa-inner-main .pa-sinova-hub { margin-top: 0 !important; }

/* Statistik Pengunjung Grid */
.pa-vstats-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-top: 14px;
}
@media (max-width: 600px) { .pa-vstats-grid { grid-template-columns: 1fr; } }
.pa-vstat-box {
    padding: 18px;
    border-radius: 12px;
    background: #f4faf7;
    border: 1.5px solid #d5e6dc;
    border-top: 4px solid #056e4f;
}
.pa-vstat-box span { display: block; font-size: 12px; font-weight: 700; color: #475569; }
.pa-vstat-box strong { display: block; margin-top: 6px; font-size: 26px; font-weight: 900; color: #043927; }

/* Jam Real-Time & Tombol Light/Dark Mode di Topbar */
.pa-live-clock {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
    color: #E5C158;
    font-variant-numeric: tabular-nums;
}
.pa-topbar-theme-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 999px;
    border: 1px solid rgba(229, 193, 88, 0.6);
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
    font: 700 11.5px/1.3 'Inter', system-ui, sans-serif;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
}
.pa-topbar-theme-btn:hover,
.pa-topbar-theme-btn[aria-pressed="true"] { background: #E5C158; color: #022c22; }

/* Topbar Institusional */
.pa-shell-topbar {
    background: #022c22;
    color: #d1fae5;
    font-size: 12px;
    padding: 7px 16px;
    border-bottom: 1px solid rgba(201, 162, 39, 0.35);
}
.pa-shell-inner {
    max-width: 1180px;
    margin: 0 auto;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px 18px;
}
.pa-shell-topbar-links { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; }
.pa-shell-topbar a { color: #E5C158; text-decoration: none; font-weight: 600; }
.pa-shell-topbar a:hover { text-decoration: underline; }

/* Fallback Header & Navbar saat Block Theme aktif */
.pa-shell-masthead { background: linear-gradient(135deg, #043927 0%, #056e4f 100%); color: #fff; padding: 16px; border-bottom: 3px solid #C9A227; }
.pa-shell-brand { display: flex; align-items: center; gap: 14px; text-decoration: none; color: #fff; }
.pa-shell-emblem { width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #C9A227 0%, #E5C158 100%); color: #022c22; font-weight: 900; font-size: 17px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.pa-shell-brand-title { margin: 0; font-size: clamp(1.05rem, 2vw, 1.4rem); font-weight: 800; color: #fff; line-height: 1.2; }
.pa-shell-brand-sub { margin: 2px 0 0; font-size: 12.5px; color: #d1fae5; }
.pa-shell-badge { padding: 6px 12px; border-radius: 999px; background: rgba(201, 162, 39, 0.16); border: 1px solid #C9A227; color: #E5C158; font-size: 12px; font-weight: 700; }
.pa-shell-navwrap { background: #ffffff; border-bottom: 1px solid #d5e6dc; box-shadow: 0 4px 14px rgba(4, 57, 39, 0.06); position: sticky; top: 0; z-index: 9990; }
.pa-shell-nav-row { max-width: 1180px; margin: 0 auto; padding: 0 16px; display: flex; align-items: center; justify-content: space-between; }
.pa-shell-menu-btn { display: none; margin: 8px 0; padding: 8px 14px; border-radius: 8px; border: 1.5px solid #056e4f; background: #f4faf7; color: #043927; font-weight: 700; cursor: pointer; }
.pa-shell-menu { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; align-items: center; gap: 2px; }
.pa-shell-menu > li { position: relative; }
.pa-shell-menu > li > a { display: flex; align-items: center; gap: 5px; padding: 13px 12px; color: #043927; text-decoration: none; font-size: 13.5px; font-weight: 700; border-bottom: 2px solid transparent; }
.pa-shell-menu > li > a:hover, .pa-shell-menu > li:focus-within > a { color: #056e4f; background: #f4faf7; border-bottom-color: #C9A227; }
.pa-shell-submenu { list-style: none; margin: 0; padding: 6px; position: absolute; left: 0; top: 100%; min-width: 270px; max-height: 70vh; overflow-y: auto; background: #ffffff; border: 1px solid #d5e6dc; border-top: 3px solid #C9A227; border-radius: 0 0 12px 12px; box-shadow: 0 14px 32px rgba(4, 57, 39, 0.16); display: none; z-index: 9995; }
.pa-shell-menu > li:hover > .pa-shell-submenu, .pa-shell-menu > li:focus-within > .pa-shell-submenu { display: block; }
.pa-shell-submenu a { display: block; padding: 8px 12px; border-radius: 8px; color: #1e293b; text-decoration: none; font-size: 13px; font-weight: 600; }
.pa-shell-submenu a:hover { background: linear-gradient(135deg, #043927 0%, #056e4f 100%); color: #E5C158; }
@media (max-width: 960px) {
    .pa-shell-menu-btn { display: inline-flex; }
    .pa-shell-nav-row { flex-direction: column; align-items: stretch; }
    .pa-shell-menu { display: none; flex-direction: column; align-items: stretch; padding-bottom: 10px; }
    .pa-shell-menu.is-open { display: flex; }
    .pa-shell-submenu { position: static; box-shadow: none; border-radius: 8px; margin: 0 0 6px 12px; }
}

/* CSS Beranda Portal Resmi Pengadilan Agama (Clean Hover-First + GPU Parallax) */
.pa-starter-showcase { max-width: 1180px; margin: 6px auto 28px; font-family: 'Inter', system-ui, sans-serif; overflow: visible !important; }
.pa-section-block, .pa-services-grid, .pa-zi-grid, .pa-news-grid, .pa-split-section { overflow: visible !important; }
.pa-ticker-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 14px; border-radius: 10px; background: #f4faf7; border: 1px solid #d5e6dc; border-left: 4px solid #C9A227; margin-bottom: 18px; font-size: 12.5px; color: #043927; }
.pa-ticker-tag { padding: 3px 9px; border-radius: 6px; background: #056e4f; color: #fff; font-size: 11px; font-weight: 800; letter-spacing: 0.04em; }
.pa-ticker-text { flex: 1; min-width: 220px; font-weight: 600; }
.pa-ticker-link { color: #056e4f !important; font-weight: 800; text-decoration: none !important; white-space: nowrap; }
.pa-ticker-link:hover { text-decoration: underline !important; }
.pa-hero-banner {
    position: relative;
    overflow: hidden;
    padding: clamp(30px, 4.8vw, 52px) clamp(20px, 4vw, 42px);
    border-radius: 18px;
    background: linear-gradient(135deg, #022c22 0%, #044e3a 52%, #056e4f 100%);
    border: 1.5px solid #C9A227;
    color: #fff;
    text-align: center;
    box-shadow: 0 14px 34px rgba(4, 57, 39, 0.18);
    margin-bottom: 30px;
}
.pa-hero-banner::before,
.pa-hero-banner::after {
    content: "";
    position: absolute;
    pointer-events: none;
    will-change: transform;
    transition: transform 0.08s linear;
}
.pa-hero-banner::before {
    inset: -36% -12%;
    background:
        radial-gradient(circle at 82% 24%, rgba(229, 193, 88, 0.24), transparent 45%),
        radial-gradient(circle at 14% 78%, rgba(16, 185, 129, 0.22), transparent 42%),
        repeating-linear-gradient(135deg, rgba(229, 193, 88, 0.04) 0 1px, transparent 1px 28px);
    transform: translate3d(0, var(--pa-py, 0px), 0);
}
.pa-hero-banner::after {
    width: 280px;
    height: 280px;
    right: -60px;
    bottom: -90px;
    border-radius: 50%;
    border: 1.5px solid rgba(229, 193, 88, 0.22);
    background: radial-gradient(circle, rgba(201, 162, 39, 0.14), transparent 70%);
    transform: translate3d(0, var(--pa-py-rev, 0px), 0);
}
.pa-hero-content { position: relative; z-index: 2; transform: translate3d(0, var(--pa-py-fg, 0px), 0); will-change: transform; }
.pa-hero-kicker { display: inline-block; padding: 5px 14px; border-radius: 999px; background: rgba(201, 162, 39, 0.18); border: 1px solid #C9A227; color: #E5C158; font-size: 11.5px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 12px; }
.pa-hero-title { margin: 0 0 12px; color: #fff; font-size: clamp(1.5rem, 3.2vw, 2.35rem); font-weight: 800; line-height: 1.2; }
.pa-hero-lead {
    margin: 0 auto 14px;
    max-width: 720px;
    color: #d1fae5;
    font-size: 0.94rem;
    line-height: 1.5;
}
.pa-hero-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 8px; }
.pa-btn-gold { display: inline-flex; align-items: center; padding: 11px 20px; border-radius: 10px; background: linear-gradient(135deg, #C9A227 0%, #E5C158 100%); color: #022c22 !important; font-weight: 800; font-size: 13.5px; text-decoration: none !important; box-shadow: 0 6px 16px rgba(0, 0, 0, 0.18); transition: transform 0.16s ease, box-shadow 0.16s ease; }
.pa-btn-gold:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(0, 0, 0, 0.25); }
.pa-btn-outline { display: inline-flex; align-items: center; padding: 11px 18px; border-radius: 10px; background: rgba(255, 255, 255, 0.1); border: 1.5px solid rgba(229, 193, 88, 0.55); color: #fff !important; font-weight: 700; font-size: 13.5px; text-decoration: none !important; transition: transform 0.16s ease, background-color 0.16s ease; }
.pa-btn-outline:hover { background: rgba(255, 255, 255, 0.2); transform: translateY(-2px); }
.pa-section-block { margin-bottom: 34px; text-align: center; position: relative; }
.pa-starter-heading { margin: 0 0 18px; color: #056e4f; font-weight: 800; font-size: 1.35rem; display: inline-flex; align-items: center; gap: 8px; cursor: default; }
.pa-hint-i {
    display: inline-flex; align-items: center; justify-content: center;
    width: 18px; height: 18px; border-radius: 50%;
    border: 1px solid #C9A227; color: #6e5204;
    font-size: 11px; font-weight: 800; font-style: italic; font-family: Georgia, serif;
    opacity: 0.78; transition: background-color 0.16s ease, color 0.16s ease;
}
.pa-starter-heading:hover .pa-hint-i,
.pa-starter-heading:focus-visible .pa-hint-i { background: #056e4f; color: #E5C158; border-color: #056e4f; opacity: 1; }
.pa-services-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; text-align: left; }
@media (max-width: 960px) { .pa-services-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 520px) { .pa-services-grid { grid-template-columns: 1fr; } }
.pa-service-card {
    position: relative; z-index: 1;
    display: flex; flex-direction: column; justify-content: space-between; gap: 10px;
    padding: 16px 16px 15px; border-radius: 14px;
    background: #ffffff; border: 1.5px solid #d5e6dc; border-top: 4px solid #056e4f;
    color: #1f2937 !important; text-decoration: none !important;
    box-shadow: 0 5px 14px rgba(4, 57, 39, 0.05);
    transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}
.pa-service-card:hover,
.pa-service-card:focus-visible { transform: translateY(-4px); border-color: #C9A227; box-shadow: 0 12px 26px rgba(4, 57, 39, 0.14); z-index: 40; }
.pa-svc-top { display: flex; align-items: center; justify-content: space-between; }
.pa-svc-icon { width: 34px; height: 34px; border-radius: 9px; background: #ecfdf5; color: #056e4f; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.18s ease, color 0.18s ease; }
.pa-service-card:hover .pa-svc-icon { background: #056e4f; color: #E5C158; }
.pa-svc-icon svg { width: 18px; height: 18px; }
.pa-svc-badge { padding: 2px 8px; border-radius: 999px; background: #f8fafc; border: 1px solid #cbd5e1; color: #043927; font-size: 10.5px; font-weight: 800; }
.pa-service-card strong { font-size: 14.5px; color: #043927; display: flex; align-items: center; justify-content: space-between; gap: 6px; }
.pa-service-card strong::after { content: "→"; font-size: 13px; color: #C9A227; opacity: 0.5; transition: transform 0.18s ease, opacity 0.18s ease; }
.pa-service-card:hover strong::after { transform: translateX(3px); opacity: 1; }
.pa-service-card > span:last-child { font-size: 12.2px; line-height: 1.45; color: #4b5563; }
.pa-welcome-grid { display: grid; grid-template-columns: 1.15fr 1fr; gap: 18px; margin-bottom: 32px; text-align: left; }
@media (max-width: 860px) { .pa-welcome-grid { grid-template-columns: 1fr; } }

/* Banner Resmi Access CCTV Online (ACO) Badilag */
.pa-aco-banner {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 18px;
    padding: 15px 22px;
    margin-bottom: 32px;
    border-radius: 16px;
    background: linear-gradient(135deg, #f4faf7 0%, #ffffff 55%, #fefce8 100%);
    border: 1.5px solid #C9A227;
    box-shadow: 0 8px 22px rgba(4, 57, 39, 0.08);
    text-align: left;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}
.pa-aco-banner:hover,
.pa-aco-banner:focus-within { transform: translateY(-2px); box-shadow: 0 14px 28px rgba(4, 57, 39, 0.14); z-index: 35; }
.pa-aco-logo { flex: 0 0 58px; width: 58px; height: 58px; }
.pa-aco-logo svg { width: 58px; height: 58px; display: block; }
.pa-aco-body { flex: 1 1 320px; min-width: 0; }
.pa-aco-kicker-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 5px; }
.pa-aco-pill { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; background: #043927; color: #E5C158; font-size: 11px; font-weight: 800; letter-spacing: 0.03em; }
.pa-aco-dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.25); }
.pa-aco-std { font-size: 11.5px; font-weight: 700; color: #056e4f; }
.pa-aco-body h3 { margin: 0; font-size: 1.06rem; font-weight: 800; color: #043927; }
.pa-aco-body p { margin: 4px 0 0; font-size: 0.87rem; line-height: 1.5; color: #475569; }
.pa-aco-cta { flex: 0 0 auto; }
.pa-aco-btn { display: inline-flex; align-items: center; padding: 11px 18px; border-radius: 10px; background: linear-gradient(135deg, #043927 0%, #056e4f 100%); border: 1.5px solid #C9A227; color: #E5C158 !important; font-size: 13px; font-weight: 800; text-decoration: none !important; box-shadow: 0 6px 14px rgba(4, 57, 39, 0.16); transition: transform 0.16s ease; }
.pa-aco-btn:hover { transform: translateY(-2px); background: #022c22; }
html[data-pa-theme="dark"] .pa-aco-banner { background: linear-gradient(135deg, #13221a 0%, #0f1c15 100%); border-color: #C9A227; }
html[data-pa-theme="dark"] .pa-aco-body h3 { color: #E5C158; }
html[data-pa-theme="dark"] .pa-aco-body p,
html[data-pa-theme="dark"] .pa-aco-std { color: #cbd5e1; }
.pa-welcome-card { padding: 22px; border-radius: 16px; background: #f4faf7; border: 1.5px solid #d5e6dc; border-left: 5px solid #056e4f; transition: transform 0.18s ease, border-color 0.18s ease; }
.pa-welcome-card:hover { transform: translateY(-2px); border-color: #C9A227; }
.pa-wc-kicker { display: inline-block; padding: 3px 10px; border-radius: 999px; background: #056e4f; color: #fff; font-size: 11px; font-weight: 800; margin-bottom: 8px; }
.pa-welcome-card h3 { margin: 0 0 10px; color: #043927; font-size: 1.15rem; font-weight: 800; }
.pa-wc-body { margin: 0 0 14px; color: #334155; font-size: 0.92rem; line-height: 1.6; }
.pa-wc-links { display: flex; flex-wrap: wrap; gap: 8px; }
.pa-wc-links a { padding: 6px 12px; border-radius: 8px; background: #ffffff; border: 1px solid #056e4f; color: #043927 !important; font-size: 12px; font-weight: 700; text-decoration: none !important; transition: background-color 0.15s ease, color 0.15s ease; }
.pa-wc-links a:hover { background: #056e4f; color: #ffffff !important; }
.pa-maklumat-card { padding: 22px; border-radius: 16px; background: linear-gradient(155deg, #043927 0%, #056e4f 100%); border: 1.5px solid #C9A227; color: #ffffff; transition: transform 0.18s ease; }
.pa-maklumat-card:hover { transform: translateY(-2px); }
.pa-mk-kicker { display: inline-block; padding: 3px 10px; border-radius: 999px; background: rgba(229, 193, 88, 0.18); border: 1px solid #C9A227; color: #E5C158; font-size: 11px; font-weight: 800; margin-bottom: 8px; }
.pa-maklumat-card h3 { margin: 0 0 8px; color: #fff; font-size: 1.15rem; font-weight: 800; }
.pa-mk-quote { margin: 0 0 12px; color: #e6f4ee; font-size: 12.5px; line-height: 1.55; font-style: italic; }
.pa-mk-hours { list-style: none; margin: 0 0 12px; padding: 10px 12px; border-radius: 10px; background: rgba(0, 0, 0, 0.18); font-size: 12.5px; line-height: 1.65; }
.pa-mk-btn { color: #E5C158 !important; font-size: 12.5px; font-weight: 800; text-decoration: none !important; }
.pa-mk-btn:hover { text-decoration: underline !important; }
.pa-zi-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px; align-items: stretch; }
@media (max-width: 960px) { .pa-zi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 520px) { .pa-zi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.pa-zi-card { position: relative; z-index: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 18px 12px; border-radius: 12px; background: linear-gradient(135deg, #056e4f 0%, #043927 100%); border: 1.5px solid #C9A227; color: #fff !important; text-decoration: none !important; box-shadow: 0 6px 14px rgba(4, 57, 39, 0.18); transition: transform 0.2s ease, border-color 0.2s ease; }
.pa-zi-card:hover,
.pa-zi-card:focus-visible { transform: translateY(-4px); border-color: #E5C158; z-index: 40; }
.pa-zi-icon { width: 36px; height: 36px; border-radius: 999px; background: rgba(255, 255, 255, 0.12); color: #E5C158; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 8px; }
.pa-zi-icon svg { width: 18px; height: 18px; }
.pa-zi-area { font-size: 15px; font-weight: 800; color: #E5C158; margin-bottom: 0; }
.pa-zi-desc { font-size: 11.5px; line-height: 1.32; font-weight: 700; display: flex; align-items: center; justify-content: center; text-align: center; }
.pa-news-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 16px; }
.pa-news-head > div { position: relative; }
.pa-news-all { color: #056e4f !important; font-weight: 800; font-size: 13px; text-decoration: none !important; }
.pa-news-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; text-align: left; }
@media (max-width: 860px) { .pa-news-grid { grid-template-columns: 1fr; } }
.pa-news-card { position: relative; z-index: 1; display: flex; flex-direction: column; justify-content: space-between; gap: 8px; padding: 18px; border-radius: 14px; background: #ffffff; border: 1px solid #d5e6dc; box-shadow: 0 4px 14px rgba(4, 57, 39, 0.05); transition: transform 0.18s ease, border-color 0.18s ease; }
.pa-news-card:hover,
.pa-news-card:focus-within { transform: translateY(-3px); border-color: #C9A227; z-index: 35; }
.pa-news-date { font-size: 11.5px; font-weight: 700; color: #6e5204; }
.pa-news-card h3 { margin: 0; font-size: 15px; line-height: 1.4; }
.pa-news-card h3 a { color: #043927 !important; text-decoration: none !important; }
.pa-news-card h3 a:hover { color: #056e4f !important; }
.pa-news-card p { margin: 0; font-size: 12.5px; line-height: 1.5; color: #4b5563; }
.pa-news-more { color: #056e4f !important; font-size: 12px; font-weight: 800; text-decoration: none !important; }
.pa-split-section { display: grid; grid-template-columns: 360px 1fr; gap: 20px; align-items: stretch; }
@media (max-width: 880px) { .pa-split-section { grid-template-columns: 1fr; } }
.pa-starter-survey { padding: 18px; border-radius: 16px; background: linear-gradient(155deg, #065f46 0%, #044e3a 52%, #022c22 100%); border: 1.5px solid #C9A227; color: #fff; text-align: left; display: flex; flex-direction: column; justify-content: space-between; }
.pa-starter-survey .survey-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 10.5px; font-weight: 800; }
.pa-starter-survey .survey-pill { padding: 3px 9px; border-radius: 999px; background: rgba(229, 193, 88, 0.18); border: 1px solid #C9A227; color: #E5C158; }
.pa-starter-survey .survey-period-badge { padding: 3px 9px; border-radius: 999px; background: #d1fae5; color: #022c22; }
.pa-starter-survey h3 { margin: 0 0 10px; color: #fff; font-size: 16.5px; font-weight: 800; }
.pa-starter-survey .survey-slide { display: none; }
.pa-starter-survey .survey-slide.is-active { display: block; }
.pa-starter-survey .survey-cards { display: flex; flex-direction: column; gap: 8px; }
.pa-starter-survey .survey-card { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 10px 12px; border-radius: 10px; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.18); color: #fff !important; text-decoration: none !important; font-size: 12px; }
.pa-starter-survey a.survey-card:hover { background: rgba(201, 162, 39, 0.22); border-color: #E5C158; }
.pa-starter-survey .survey-dots { display: flex; justify-content: center; gap: 6px; margin-top: 14px; }
.pa-starter-survey .survey-dot { padding: 5px 11px; border-radius: 999px; border: 1px solid rgba(255, 255, 255, 0.28); background: transparent; color: #fff; font-size: 11px; font-weight: 700; cursor: pointer; }
.pa-starter-survey .survey-dot.is-active { background: #E5C158; color: #022c22; border-color: #E5C158; }
.pa-hub-shortcuts { background: #f4faf7; border: 1.5px solid #d5e6dc; border-radius: 16px; padding: 20px; text-align: left; display: flex; flex-direction: column; justify-content: space-between; }
.pa-hub-shortcuts-head { position: relative; }
.pa-hub-shortcuts-head h3 { margin: 0 0 12px; color: #043927; font-size: 1.15rem; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; cursor: default; }
.pa-hub-shortcuts-head p { margin: 0; color: #4b5563; font-size: 0.88rem; }
.pa-shortcut-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
@media (max-width: 520px) { .pa-shortcut-grid { grid-template-columns: 1fr; } }
.pa-shortcut-item { position: relative; display: flex; flex-direction: column; gap: 2px; padding: 12px 14px; border-radius: 10px; background: #ffffff; border: 1px solid #d5e6dc; text-decoration: none !important; transition: border-color 0.15s ease, transform 0.15s ease; }
.pa-shortcut-item:hover { border-color: #C9A227; transform: translateY(-2px); z-index: 30; }
.pa-shortcut-item span { font-size: 11px; font-weight: 800; color: #6e5204; text-transform: uppercase; }
.pa-shortcut-item strong { font-size: 13.5px; color: #043927; }

/* Hover-First Clean UX: Keterangan Muncul Elegan Hanya Saat Hover/Fokus (CLS = 0) */
@media (hover: hover) and (pointer: fine) {
    html:not([data-pa-contrast="high"]) .pa-hero-lead {
        position: absolute;
        left: 50%;
        top: 6px;
        transform: translate(-50%, -6px);
        width: min(680px, 92%);
        margin: 0;
        padding: 9px 16px;
        border-radius: 10px;
        background: rgba(2, 44, 34, 0.94);
        border: 1px solid rgba(229, 193, 88, 0.6);
        color: #f8fafc;
        font-size: 0.85rem;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.28);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s ease;
        z-index: 30;
    }
    html:not([data-pa-contrast="high"]) .pa-hero-title:hover + .pa-hero-lead,
    html:not([data-pa-contrast="high"]) .pa-hero-kicker:hover ~ .pa-hero-lead {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, 0);
    }

    html:not([data-pa-contrast="high"]) .pa-starter-sub,
    html:not([data-pa-contrast="high"]) .pa-hub-shortcuts-head p {
        position: absolute;
        left: 50%;
        top: 34px;
        transform: translate(-50%, -4px);
        margin: 0;
        padding: 7px 14px;
        border-radius: 9px;
        background: #022c22;
        border: 1px solid #C9A227;
        color: #f8fafc !important;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        max-width: 92vw;
        overflow: hidden;
        text-overflow: ellipsis;
        box-shadow: 0 10px 24px rgba(2, 44, 34, 0.24);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        z-index: 45;
        transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s ease;
    }
    html:not([data-pa-contrast="high"]) .pa-news-head .pa-starter-sub,
    html:not([data-pa-contrast="high"]) .pa-hub-shortcuts-head p {
        left: 0;
        transform: translate(0, -4px);
    }
    html:not([data-pa-contrast="high"]) .pa-starter-heading:hover + .pa-starter-sub,
    html:not([data-pa-contrast="high"]) .pa-starter-heading:focus-visible + .pa-starter-sub {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, 0);
    }
    html:not([data-pa-contrast="high"]) .pa-news-head .pa-starter-heading:hover + .pa-starter-sub,
    html:not([data-pa-contrast="high"]) .pa-news-head .pa-starter-heading:focus-visible + .pa-starter-sub,
    html:not([data-pa-contrast="high"]) .pa-hub-shortcuts-head h3:hover + p,
    html:not([data-pa-contrast="high"]) .pa-hub-shortcuts-head:focus-within p {
        opacity: 1;
        visibility: visible;
        transform: translate(0, 0);
    }

    html:not([data-pa-contrast="high"]) .pa-service-card > span:last-child {
        position: absolute;
        left: -1.5px;
        right: -1.5px;
        top: calc(100% - 6px);
        z-index: 45;
        padding: 10px 14px 12px;
        border-radius: 0 0 14px 14px;
        background: #022c22;
        border: 1.5px solid #C9A227;
        border-top: 1px solid rgba(229, 193, 88, 0.3);
        color: #f1f5f9 !important;
        box-shadow: 0 14px 28px rgba(2, 44, 34, 0.28);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-4px);
        pointer-events: none;
        transition: opacity 0.16s ease, transform 0.16s ease, visibility 0.16s ease;
    }
    html:not([data-pa-contrast="high"]) .pa-service-card:hover,
    html:not([data-pa-contrast="high"]) .pa-service-card:focus-visible {
        border-bottom-left-radius: 4px;
        border-bottom-right-radius: 4px;
    }
    html:not([data-pa-contrast="high"]) .pa-service-card:hover > span:last-child,
    html:not([data-pa-contrast="high"]) .pa-service-card:focus-visible > span:last-child {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    html:not([data-pa-contrast="high"]) .pa-zi-card .pa-zi-desc {
        position: absolute;
        left: 50%;
        top: calc(100% + 7px);
        transform: translate(-50%, -4px);
        min-width: 175px;
        max-width: 220px;
        padding: 7px 10px;
        border-radius: 9px;
        background: #022c22;
        border: 1px solid #E5C158;
        color: #ffffff;
        box-shadow: 0 10px 22px rgba(0, 0, 0, 0.25);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        z-index: 45;
        transition: opacity 0.16s ease, transform 0.16s ease, visibility 0.16s ease;
    }
    html:not([data-pa-contrast="high"]) .pa-zi-card:hover .pa-zi-desc,
    html:not([data-pa-contrast="high"]) .pa-zi-card:focus-visible .pa-zi-desc {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, 0);
    }

    html:not([data-pa-contrast="high"]) .pa-aco-body p,
    html:not([data-pa-contrast="high"]) .pa-news-card p {
        position: absolute;
        left: -1px;
        right: -1px;
        top: calc(100% - 5px);
        margin: 0;
        padding: 10px 14px;
        border-radius: 0 0 12px 12px;
        background: #022c22;
        border: 1.5px solid #C9A227;
        border-top: 1px solid rgba(229, 193, 88, 0.35);
        color: #f1f5f9 !important;
        box-shadow: 0 14px 28px rgba(2, 44, 34, 0.26);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-4px);
        pointer-events: none;
        z-index: 45;
        transition: opacity 0.16s ease, transform 0.16s ease, visibility 0.16s ease;
    }
    html:not([data-pa-contrast="high"]) .pa-aco-banner:hover .pa-aco-body p,
    html:not([data-pa-contrast="high"]) .pa-aco-banner:focus-within .pa-aco-body p,
    html:not([data-pa-contrast="high"]) .pa-news-card:hover p,
    html:not([data-pa-contrast="high"]) .pa-news-card:focus-within p {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
}

/* Tooltip Ringan pada Button, Topbar, & Menu Saat Disorot ([data-pa-tip]) */
#ast-desktop-header,
#ast-desktop-header .main-header-bar,
#ast-desktop-header .site-header-primary-section-right,
#ast-desktop-header .ast-builder-menu-1,
#ast-desktop-header .main-header-menu { overflow: visible !important; }
[data-pa-tip] { position: relative; }
@media (hover: hover) and (pointer: fine) {
    [data-pa-tip]::after {
        content: attr(data-pa-tip);
        position: absolute;
        left: 50%;
        bottom: calc(100% + 9px);
        transform: translate(-50%, 4px);
        width: max-content;
        max-width: 250px;
        padding: 6px 10px;
        border-radius: 8px;
        background: #022c22;
        border: 1px solid #C9A227;
        color: #ffffff;
        font-size: 11.5px;
        font-weight: 600;
        line-height: 1.35;
        text-align: center;
        white-space: normal;
        box-shadow: 0 10px 22px rgba(0, 0, 0, 0.26);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        z-index: 9998;
        transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s ease;
    }
    .pa-shell-topbar [data-pa-tip]::after {
        bottom: auto;
        top: calc(100% + 7px);
        transform: translate(-50%, -4px);
    }
    #pa-topbar-theme-btn[data-pa-tip]::after { left: auto; right: 0; transform: translate(0, -4px); }
    #pa-btn-top[data-pa-tip]::after,
    .pa-news-all[data-pa-tip]::after { left: auto; right: 0; transform: translate(0, 4px); }
    #pa-a11y-dock button:first-child[data-pa-tip]::after { left: 0; transform: translate(0, 4px); }
    [data-pa-tip]:hover::after,
    [data-pa-tip]:focus-visible::after {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, 0);
    }
    #pa-topbar-theme-btn[data-pa-tip]:hover::after,
    #pa-topbar-theme-btn[data-pa-tip]:focus-visible::after,
    #pa-btn-top[data-pa-tip]:hover::after,
    #pa-btn-top[data-pa-tip]:focus-visible::after,
    .pa-news-all[data-pa-tip]:hover::after,
    .pa-news-all[data-pa-tip]:focus-visible::after,
    #pa-a11y-dock button:first-child[data-pa-tip]:hover::after,
    #pa-a11y-dock button:first-child[data-pa-tip]:focus-visible::after {
        transform: translate(0, 0);
    }
}

/* Indikator Progres Gulir, Status PTSP Real-Time, & Tombol Ke Atas */
#pa-scroll-progress {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #C9A227 0%, #E5C158 55%, #10b981 100%);
    transform-origin: 0 50%;
    transform: scaleX(0);
    z-index: 99999;
    pointer-events: none;
}
.pa-ptsp-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    border: 1px solid rgba(255, 255, 255, 0.22);
    background: rgba(255, 255, 255, 0.08);
    color: #d1fae5;
}
.pa-ptsp-pill.is-open { border-color: rgba(34, 197, 94, 0.55); color: #86efac; }
.pa-ptsp-pill.is-closed { border-color: rgba(229, 193, 88, 0.45); color: #fde68a; }
html.pa-is-scrolled #ast-desktop-header .main-header-bar,
html.pa-is-scrolled .pa-shell-navwrap { box-shadow: 0 10px 26px rgba(2, 44, 34, 0.18) !important; }
#pa-btn-top {
    position: fixed;
    right: 16px;
    bottom: 16px;
    z-index: 99989;
    width: 42px;
    height: 42px;
    border-radius: 999px;
    border: 1.5px solid #C9A227;
    background: rgba(4, 57, 39, 0.94);
    color: #E5C158;
    font: 800 16px/1 'Inter', system-ui, sans-serif;
    cursor: pointer;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.24);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
}
#pa-btn-top.is-visible { opacity: 1; visibility: visible; transform: translateY(0); }
#pa-btn-top:hover { background: #022c22; color: #ffffff; }
@media (prefers-reduced-motion: reduce) {
    .pa-hero-banner::before, .pa-hero-banner::after, .pa-hero-content { transform: none !important; transition: none !important; }
    * { scroll-behavior: auto !important; }
}
@media print {
    #pa-a11y-dock, #pa-btn-top, #pa-scroll-progress, .pa-emergency-bar, .pa-shell-topbar, .pa-shell-navwrap, #ast-desktop-header, .pa-inner-sidebar { display: none !important; }
}

/* Footer 4 Kolom Resmi + Statistik Pengunjung */
.pa-shell-footer { background: #022c22; color: #d1fae5; border-top: 4px solid #C9A227; padding: 34px 16px 24px; font-size: 13px; }
.pa-footer-grid { max-width: 1180px; margin: 0 auto 22px; display: grid; grid-template-columns: 1.35fr 1fr 1fr 1.1fr; gap: 24px; }
@media (max-width: 900px) { .pa-footer-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 540px) { .pa-footer-grid { grid-template-columns: 1fr; } }
.pa-footer-col h4 { margin: 0 0 10px; color: #E5C158; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.03em; }
.pa-footer-col p { margin: 0 0 8px; line-height: 1.55; color: #e2e8f0; font-size: 12.5px; }
.pa-footer-col ul { list-style: none; margin: 0; padding: 0; }
.pa-footer-col li { margin-bottom: 6px; }
.pa-footer-col a { color: #d1fae5; text-decoration: none; font-size: 12.5px; }
.pa-footer-col a:hover { color: #E5C158; text-decoration: underline; }
.pa-footer-vis-box { margin-top: 10px; padding: 10px 12px; border-radius: 10px; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(229, 193, 88, 0.25); font-size: 12px; }
.pa-footer-vis-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
.pa-footer-vis-row:last-child { margin-bottom: 0; }
.pa-footer-vis-row strong { color: #E5C158; font-variant-numeric: tabular-nums; }
.pa-shell-footer-bottom { max-width: 1180px; margin: 0 auto; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.14); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px; font-size: 12px; }
.pa-shell-render-pill { display: inline-block; padding: 4px 12px; border-radius: 999px; background: rgba(229, 193, 88, 0.16); border: 1px solid rgba(229, 193, 88, 0.45); color: #E5C158; font-size: 12px; font-weight: 700; }

/* Dark Mode & Kontras Tinggi (Astra + Block Theme) */
html[data-pa-theme="dark"] body,
html[data-pa-theme="dark"] #page,
html[data-pa-theme="dark"] .site,
html[data-pa-theme="dark"] .wp-site-blocks,
html[data-pa-theme="dark"] .site-content,
html[data-pa-theme="dark"] #primary,
html[data-pa-theme="dark"] .ast-separate-container,
html[data-pa-theme="dark"] .ast-separate-container .ast-article-single,
html[data-pa-theme="dark"] article.page,
html[data-pa-theme="dark"] article.post {
    background-color: #0b141a !important;
    color: #e7eef2 !important;
}
html[data-pa-theme="dark"] .pa-shell-navwrap,
html[data-pa-theme="dark"] .pa-shell-submenu,
html[data-pa-theme="dark"] .pa-ticker-bar,
html[data-pa-theme="dark"] .pa-breadcrumb,
html[data-pa-theme="dark"] .pa-inner-sidebar,
html[data-pa-theme="dark"] body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu {
    background: #13221a !important;
    border-color: #2c4637 !important;
    color: #e6f4ee !important;
}
html[data-pa-theme="dark"] .pa-shell-menu > li > a,
html[data-pa-theme="dark"] .pa-shell-submenu a,
html[data-pa-theme="dark"] .pa-ticker-link,
html[data-pa-theme="dark"] .pa-sidebar-list a,
html[data-pa-theme="dark"] .pa-breadcrumb a,
html[data-pa-theme="dark"] body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu .menu-link {
    color: #e6f4ee !important;
}
html[data-pa-theme="dark"] .pa-sidebar-list a:hover { background: #1e3729; color: #E5C158 !important; }
html[data-pa-theme="dark"] body.pa-sinova-classic-shell:not(.home) .entry-title,
html[data-pa-theme="dark"] body.pa-sinova-block-shell .wp-block-post-title { color: #E5C158 !important; }
html[data-pa-theme="dark"] .pa-service-card,
html[data-pa-theme="dark"] .pa-welcome-card,
html[data-pa-theme="dark"] .pa-news-card,
html[data-pa-theme="dark"] .pa-hub-shortcuts,
html[data-pa-theme="dark"] .pa-shortcut-item,
html[data-pa-theme="dark"] .pa-vstat-box {
    background: #182a20;
    border-color: #355241;
    color: #e6f4ee !important;
}
html[data-pa-theme="dark"] .pa-service-card strong,
html[data-pa-theme="dark"] .pa-welcome-card h3,
html[data-pa-theme="dark"] .pa-news-card h3 a,
html[data-pa-theme="dark"] .pa-hub-shortcuts-head h3,
html[data-pa-theme="dark"] .pa-shortcut-item strong,
html[data-pa-theme="dark"] .pa-vstat-box strong,
html[data-pa-theme="dark"] .pa-starter-heading { color: #E5C158 !important; }
html[data-pa-theme="dark"] .pa-svc-badge { background: rgba(255, 255, 255, 0.08); border-color: #4b6e58; color: #E5C158; }
html[data-pa-theme="dark"] .pa-service-card > span:last-child,
html[data-pa-theme="dark"] .pa-wc-body,
html[data-pa-theme="dark"] .pa-news-card p,
html[data-pa-theme="dark"] .pa-starter-sub,
html[data-pa-theme="dark"] .pa-vstat-box span,
html[data-pa-theme="dark"] .pa-hub-shortcuts-head p { color: #cbd5e1; }
html[data-pa-theme="dark"] a { color: #E5C158; }

html[data-pa-contrast="high"] body,
html[data-pa-contrast="high"] .site,
html[data-pa-contrast="high"] .wp-site-blocks { background: #ffffff !important; color: #000000 !important; }
html[data-pa-contrast="high"] a { color: #003d29 !important; text-decoration: underline !important; }
html[data-pa-readable="1"] body,
html[data-pa-readable="1"] p,
html[data-pa-readable="1"] li,
html[data-pa-readable="1"] td {
    letter-spacing: 0.04em !important;
    word-spacing: 0.12em !important;
    line-height: 1.75 !important;
}
html[data-pa-links="1"] a {
    text-decoration: underline !important;
    text-underline-offset: 3px !important;
}
html[data-pa-grayscale="1"] { filter: grayscale(100%) !important; }
html[data-pa-nomotion="1"] *,
html[data-pa-nomotion="1"] *::before,
html[data-pa-nomotion="1"] *::after {
    animation: none !important;
    transition: none !important;
    scroll-behavior: auto !important;
}
.pa-feeds-section, .pa-news-section, .pa-split-section, .pa-shell-footer {
    content-visibility: auto;
    contain-intrinsic-size: auto 360px;
}

/* Drawer Sarana Aksesibilitas & Pembaca Suara WCAG 2.2 AA */
#pa-a11y-dock {
    position: fixed;
    left: 14px;
    bottom: 14px;
    z-index: 99990;
    display: flex;
    flex-direction: column-reverse;
    align-items: flex-start;
    gap: 8px;
}
#pa-a11y-toggle {
    min-height: 44px;
    padding: 8px 14px;
    border-radius: 999px;
    border: 1.5px solid var(--pa-gold, #C9A227);
    background: rgba(4, 57, 39, 0.96);
    color: #ffffff;
    font: 800 12.5px/1.2 'Inter', system-ui, sans-serif;
    cursor: pointer;
    box-shadow: 0 10px 26px rgba(0, 0, 0, 0.28);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
#pa-a11y-toggle:hover,
#pa-a11y-toggle[aria-expanded="true"] {
    background: var(--pa-gold, #C9A227);
    color: #022c22;
}
#pa-a11y-panel {
    width: min(320px, calc(100vw - 28px));
    padding: 12px;
    border-radius: 14px;
    background: rgba(4, 57, 39, 0.97);
    border: 1.5px solid var(--pa-gold, #C9A227);
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.36);
    color: #ffffff;
}
#pa-a11y-panel[hidden] { display: none !important; }
.pa-a11y-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 7px;
    border-bottom: 1px solid rgba(229, 193, 88, 0.35);
    font-size: 12px;
    font-weight: 800;
    color: #E5C158;
}
.pa-a11y-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 6px;
}
#pa-a11y-panel button {
    min-height: 38px;
    padding: 6px 9px;
    border: 1px solid rgba(229, 193, 88, 0.35);
    border-radius: 9px;
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
    font: 700 11.5px/1.25 'Inter', system-ui, sans-serif;
    cursor: pointer;
    text-align: left;
}
#pa-a11y-panel button:hover,
#pa-a11y-panel button[aria-pressed="true"] {
    background: var(--pa-gold, #C9A227);
    color: #022c22;
    border-color: #E5C158;
}
#pa-a11y-close-btn {
    min-height: 28px !important;
    padding: 3px 8px !important;
    font-size: 11px !important;
    text-align: center !important;
}

/* Command Palette Ctrl+K */
.pa-cmdk-trigger {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 9px;
    border: 1px solid rgba(201, 162, 39, 0.5);
    background: rgba(255, 255, 255, 0.14);
    color: #ffffff;
    font: 700 11.5px/1.2 var(--pa-font, system-ui, sans-serif);
    cursor: pointer;
    white-space: nowrap;
}
.pa-cmdk-trigger kbd {
    background: rgba(2, 44, 34, 0.6);
    color: #E5C158;
    border: 1px solid rgba(229, 193, 88, 0.35);
    border-radius: 5px;
    padding: 1px 5px;
    font: 700 10.5px/1.2 monospace;
}
.pa-cmdk-trigger:hover { background: #C9A227; color: #022c22; }
#pa-cmdk-dialog {
    width: min(640px, 94vw);
    max-width: 94vw;
    padding: 0;
    border: 1.5px solid #C9A227;
    border-radius: 16px;
    background: var(--pa-surface, #ffffff);
    color: var(--pa-text, #0f172a);
    box-shadow: 0 28px 65px rgba(2, 44, 34, 0.35);
    overflow: hidden;
}
#pa-cmdk-dialog::backdrop { background: rgba(2, 44, 34, 0.58); backdrop-filter: blur(4px); }
.pa-cmdk-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: #022c22;
    color: #ffffff;
    border-bottom: 1px solid rgba(201, 162, 39, 0.35);
}
#pa-cmdk-input {
    flex: 1;
    border: 0;
    background: transparent;
    color: #ffffff;
    font-size: 15px;
    font-weight: 600;
    outline: none;
}
#pa-cmdk-input::placeholder { color: #94a3b8; }
#pa-cmdk-close {
    border: 1px solid rgba(255, 255, 255, 0.25);
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    border-radius: 8px;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}
.pa-cmdk-results { max-height: 360px; overflow-y: auto; padding: 10px; }
.pa-cmdk-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    border: 1px solid transparent;
}
.pa-cmdk-item:hover, .pa-cmdk-item:focus-visible {
    background: rgba(5, 110, 79, 0.08);
    border-color: #C9A227;
}
.pa-cmdk-item strong { display: block; font-size: 14px; color: var(--pa-primary, #056e4f); }
.pa-cmdk-item small { display: block; font-size: 12px; color: var(--pa-muted, #475569); margin-top: 2px; }
.pa-cmdk-badge {
    flex-shrink: 0;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 3px 8px;
    border-radius: 999px;
    background: rgba(201, 162, 39, 0.16);
    color: #92400e;
}
.pa-cmdk-empty { padding: 18px; text-align: center; color: var(--pa-muted, #64748b); font-size: 13.5px; }

/* Pengumuman Resmi RSS MA RI & Ditjen Badilag */
.pa-feeds-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(310px, 1fr));
    gap: 20px;
    margin-top: 16px;
}
.pa-feed-card {
    background: var(--pa-surface, #ffffff);
    border: 1px solid var(--pa-border, #e2e8f0);
    border-top: 4px solid #C9A227;
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}
.pa-feed-card-head {
    background: linear-gradient(135deg, #022c22 0%, #056e4f 100%);
    color: #ffffff;
    padding: 14px 16px;
}
.pa-feed-brand { display: flex; align-items: center; gap: 12px; }
.pa-feed-crest {
    width: 40px;
    height: 40px;
    border-radius: 11px;
    background: rgba(201, 162, 39, 0.14);
    border: 1px solid rgba(229, 193, 88, 0.45);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.pa-feed-pill {
    display: inline-block;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #E5C158;
}
.pa-feed-brand h3 { margin: 2px 0 0; font-size: 15.5px; color: #ffffff !important; font-weight: 800; }
.pa-feed-list { list-style: none; margin: 0; padding: 8px 14px; flex: 1; }
.pa-feed-item { border-bottom: 1px solid var(--pa-border, #e2e8f0); }
.pa-feed-item:last-child { border-bottom: 0; }
.pa-feed-item > a {
    display: grid;
    grid-template-columns: 1fr auto;
    justify-items: start;
    align-items: center;
    text-align: left;
    gap: 4px 10px;
    padding: 10px 4px;
    text-decoration: none;
    color: inherit;
    position: relative;
}
.pa-feed-date { grid-column: 1 / -1; font-size: 11px; font-weight: 700; color: #b45309; text-align: left; }
.pa-feed-title { font-size: 13.5px; line-height: 1.4; color: var(--pa-text, #0f172a); transition: color 0.15s ease; text-align: left; }
.pa-feed-item > a:hover .pa-feed-title { color: #056e4f; }
.pa-feed-ext { font-weight: 800; color: #C9A227; align-self: center; justify-self: end; }
.pa-feed-excerpt {
    grid-column: 1 / -1;
    display: block;
    text-align: left;
    font-size: 12px;
    line-height: 1.45;
    color: var(--pa-muted, #475569);
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.2s ease, opacity 0.2s ease;
}
.pa-feed-item:hover .pa-feed-excerpt,
.pa-feed-item:focus-within .pa-feed-excerpt { max-height: 80px; opacity: 1; padding: 0 4px 8px; }
.pa-feed-empty { padding: 14px 4px; font-size: 13px; color: var(--pa-muted, #64748b); }
.pa-feed-card-foot {
    padding: 11px 16px;
    background: var(--pa-surface-soft, #f8fafc);
    border-top: 1px solid var(--pa-border, #e2e8f0);
    text-align: right;
}
.pa-feed-card-foot a { font-size: 12.5px; font-weight: 700; color: var(--pa-primary, #056e4f); text-decoration: none; }

/* Modul Data Operasional Terstruktur (Pegawai, Sidang, Ghaib, Statistik) */
.pa-ops-wrap { margin: 18px 0; }
.pa-ops-filter-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.pa-ops-tab {
    padding: 7px 14px;
    border-radius: 999px;
    border: 1px solid var(--pa-border, #cbd5e1);
    background: var(--pa-surface, #ffffff);
    color: var(--pa-text, #0f172a);
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
}
.pa-ops-tab.is-active, .pa-ops-tab:hover { background: #056e4f; color: #ffffff; border-color: #056e4f; }
.pa-pegawai-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(245px, 1fr)); gap: 14px; }
.pa-pegawai-card {
    background: var(--pa-surface, #ffffff);
    border: 1px solid var(--pa-border, #e2e8f0);
    border-top: 3px solid #C9A227;
    border-radius: 14px;
    padding: 14px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
    position: relative;
}
.pa-pegawai-card.is-hidden { display: none; }
.pa-pegawai-avatar {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #022c22, #056e4f);
    color: #E5C158;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.pa-pegawai-cat { font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #b45309; letter-spacing: 0.04em; }
.pa-pegawai-info h3 { margin: 3px 0 4px; font-size: 14.5px; line-height: 1.3; }
.pa-pegawai-role { margin: 0; font-size: 12.5px; color: var(--pa-muted, #475569); }
.pa-pegawai-hover-meta {
    margin-top: 6px;
    font-size: 11.5px;
    color: var(--pa-muted, #64748b);
    opacity: 0.85;
}
.pa-pegawai-hover-meta small { display: block; line-height: 1.45; }
.pa-ops-toolbar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 12px; }
.pa-ops-search {
    min-width: 240px;
    flex: 1;
    max-width: 380px;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid var(--pa-border, #cbd5e1);
    background: var(--pa-surface, #ffffff);
    color: var(--pa-text, #0f172a);
}
.pa-ops-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.pa-ops-badge {
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(5, 110, 79, 0.12);
    color: #056e4f;
}
.pa-ops-link-btn {
    padding: 5px 12px;
    border-radius: 8px;
    background: #056e4f;
    color: #ffffff !important;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}
.pa-table-scroll { overflow-x: auto; border: 1px solid var(--pa-border, #e2e8f0); border-radius: 12px; }
.pa-ops-table { width: 100%; border-collapse: collapse; background: var(--pa-surface, #ffffff); font-size: 13.5px; }
.pa-ops-table th { background: #022c22; color: #ffffff; text-align: left; padding: 10px 12px; font-weight: 700; }
.pa-ops-table td { padding: 10px 12px; border-bottom: 1px solid var(--pa-border, #e2e8f0); vertical-align: top; }
.pa-ops-table tr:last-child td { border-bottom: 0; }
.pa-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(165px, 1fr)); gap: 12px; margin-bottom: 16px; }
.pa-kpi-box {
    background: var(--pa-surface, #ffffff);
    border: 1px solid var(--pa-border, #e2e8f0);
    border-left: 4px solid #C9A227;
    border-radius: 12px;
    padding: 12px 14px;
}
.pa-kpi-box span { display: block; font-size: 11.5px; color: var(--pa-muted, #64748b); font-weight: 700; }
.pa-kpi-box strong { display: block; font-size: 22px; color: var(--pa-primary, #056e4f); margin-top: 2px; }
.pa-prog-wrap { display: flex; align-items: center; gap: 8px; }
.pa-prog-bar { flex: 1; height: 8px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
.pa-prog-bar span { display: block; height: 100%; background: linear-gradient(90deg, #056e4f, #C9A227); }

html[data-pa-theme="dark"] .pa-feed-card,
html[data-pa-theme="dark"] .pa-pegawai-card,
html[data-pa-theme="dark"] .pa-kpi-box,
html[data-pa-theme="dark"] .pa-ops-table,
html[data-pa-theme="dark"] .pa-ops-tab,
html[data-pa-theme="dark"] .pa-ops-search,
html[data-pa-theme="dark"] #pa-cmdk-dialog {
    background: #182a20;
    border-color: #355241;
    color: #e6f4ee;
}
html[data-pa-theme="dark"] .pa-feed-item,
html[data-pa-theme="dark"] .pa-ops-table td,
html[data-pa-theme="dark"] .pa-table-scroll { border-color: #2c4637; }
html[data-pa-theme="dark"] .pa-feed-title { color: #e6f4ee; }
html[data-pa-theme="dark"] .pa-feed-date { color: #E5C158; }
html[data-pa-theme="dark"] .pa-feed-excerpt,
html[data-pa-theme="dark"] .pa-pegawai-role,
html[data-pa-theme="dark"] .pa-pegawai-hover-meta,
html[data-pa-theme="dark"] .pa-cmdk-item small,
html[data-pa-theme="dark"] .pa-kpi-box span { color: #cbd5e1; }
html[data-pa-theme="dark"] .pa-kpi-box strong,
html[data-pa-theme="dark"] .pa-cmdk-item strong,
html[data-pa-theme="dark"] .pa-feed-card-foot a { color: #E5C158; }
html[data-pa-theme="dark"] .pa-feed-card-foot { background: #13221a; border-color: #2c4637; }
html[data-pa-theme="dark"] .pa-feed-item > a:hover .pa-feed-title { color: #6ee7b7; }
html[data-pa-theme="dark"] .pa-ops-badge { background: rgba(110, 231, 183, 0.14); color: #6ee7b7; }
html[data-pa-theme="dark"] .pa-cmdk-badge { background: rgba(229, 193, 88, 0.2); color: #fcd34d; }
</style>
    <?php
}, 90);

// 8. Topbar Institusional (Semua Tema) + Shell Header/Menu Saat Memakai Tema Blok WordPress
add_action('wp_body_open', static function () {
    if (is_admin() || pa_sinova_is_adoption_mode()) {
        return;
    }
    $cfg     = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $satker  = $cfg['satker_name'] ?? (get_bloginfo('name') ?: 'Pengadilan Agama');
    $tagline = get_bloginfo('description') ?: 'Portal Resmi Keterbukaan Informasi Publik';
    $phone   = $cfg['phone'] ?? '';

    $wibNow  = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
    $daysId  = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
    $monsId  = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $clockInit = $daysId[(int) $wibNow->format('w')] . ', '
        . $wibNow->format('j') . ' '
        . ($monsId[(int) $wibNow->format('n')] ?? $wibNow->format('F')) . ' '
        . $wibNow->format('Y') . ' · ' . $wibNow->format('H:i:s') . ' WIB';
    $emg    = trim((string) ($cfg['emergency_notice'] ?? ''));
    $emgUrl = trim((string) ($cfg['emergency_notice_url'] ?? ''));
    ?>
<div id="pa-scroll-progress" aria-hidden="true"></div>
<?php if ($emg !== '') : ?>
<div class="pa-emergency-bar" role="alert">
    <div class="pa-shell-inner">
        <span>⚠ <strong>INFORMASI SERTA-MERTA:</strong> <?php echo esc_html($emg); ?>
        <?php if ($emgUrl !== '') : ?>
            <a href="<?php echo esc_url($emgUrl); ?>">Lihat Detail →</a>
        <?php endif; ?>
        </span>
    </div>
</div>
<?php endif; ?>
<div class="pa-shell-topbar">
    <div class="pa-shell-inner">
        <span id="pa-live-clock" class="pa-live-clock" title="Waktu Indonesia Barat"><?php echo esc_html($clockInit); ?></span>
        <div class="pa-shell-topbar-links">
            <span id="pa-ptsp-live-pill" class="pa-ptsp-pill" data-pa-tip="Jam layanan PTSP: Senin–Kamis 08.00–16.30 WIB · Jumat 08.00–17.00 WIB">● PTSP</span>
            <span>PTSP: <?php echo esc_html($phone ?: 'Senin–Jumat'); ?></span>
            <a href="<?php echo esc_url($cfg['sipp_url'] ?? 'https://sipp.pa-contoh.go.id/'); ?>" target="_blank" rel="noopener noreferrer" data-pa-tip="Sistem Informasi Penelusuran Perkara">SIPP</a>
            <a href="<?php echo esc_url($cfg['ecourt_url'] ?? 'https://ecourt.mahkamahagung.go.id/'); ?>" target="_blank" rel="noopener noreferrer" data-pa-tip="Layanan Perkara Elektronik MA RI">e-Court</a>
            <a href="<?php echo esc_url($cfg['aco_url'] ?? 'https://cctv.badilag.net/'); ?>" target="_blank" rel="noopener noreferrer" data-pa-tip="Pemantauan Access CCTV Online Ditjen Badilag" style="display:inline-flex;align-items:center;gap:4px;"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>CCTV ACO</a>
            <a href="<?php echo esc_url($cfg['survey_url'] ?? 'https://survei.badilag.net/'); ?>" target="_blank" rel="noopener noreferrer" data-pa-tip="Survei Persepsi Kepuasan &amp; Anti Korupsi">Survei Pelayanan</a>
            <button type="button" id="pa-topbar-theme-btn" class="pa-topbar-theme-btn" aria-pressed="false" data-pa-tip="Ubah tampilan antara Mode Terang dan Mode Gelap">🌙 Mode Gelap</button>
        </div>
    </div>
</div>
    <?php
    if (!function_exists('wp_is_block_theme') || !wp_is_block_theme()) {
        return;
    }

    $menuItems = wp_get_nav_menu_items('Menu Utama SINOVA');
    if (empty($menuItems)) {
        $locs = get_nav_menu_locations();
        if (!empty($locs['primary'])) {
            $menuItems = wp_get_nav_menu_items((int) $locs['primary']);
        }
    }
    $topItems = array();
    $children = array();
    if (is_array($menuItems)) {
        foreach ($menuItems as $it) {
            $pid = (int) $it->menu_item_parent;
            if ($pid === 0) {
                $topItems[] = $it;
            } else {
                $children[$pid][] = $it;
            }
        }
    }
    ?>
<header class="pa-shell-masthead" role="banner">
    <div class="pa-shell-inner">
        <a class="pa-shell-brand" href="<?php echo esc_url(home_url('/')); ?>">
            <span class="pa-shell-emblem">PA</span>
            <div>
                <p class="pa-shell-brand-title"><?php echo esc_html($satker); ?></p>
                <p class="pa-shell-brand-sub"><?php echo esc_html($tagline); ?></p>
            </div>
        </a>
        <form role="search" method="get" class="pa-header-search-form" action="<?php echo esc_url(home_url('/')); ?>" aria-label="Pencarian Informasi Website">
            <input type="search" name="s" class="pa-header-search-input" aria-label="Cari informasi dan berita" placeholder="Cari informasi, berita..." value="<?php echo esc_attr(get_search_query()); ?>">
            <button type="submit" class="pa-header-search-btn" aria-label="Cari">Cari</button>
            <button type="button" id="pa-cmdk-open" class="pa-cmdk-trigger" aria-haspopup="dialog" data-pa-tip="Pencarian Instan Lintas Dokumen &amp; Jadwal (Ctrl+K)">⌘ <kbd>Ctrl K</kbd></button>
        </form>
    </div>
</header>
<?php if (!empty($topItems)) : ?>
<nav class="pa-shell-navwrap" aria-label="Navigasi Utama Website">
    <div class="pa-shell-nav-row">
        <button type="button" class="pa-shell-menu-btn" id="pa-shell-menu-toggle" aria-expanded="false" aria-controls="pa-shell-menu-list">☰ Menu Navigasi</button>
        <ul class="pa-shell-menu" id="pa-shell-menu-list">
            <?php foreach ($topItems as $top) :
                $kids = $children[(int) $top->ID] ?? array();
                $url  = !empty($kids) ? '#' : (string) $top->url;
            ?>
                <li>
                    <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($top->title); ?><?php echo !empty($kids) ? ' ▾' : ''; ?></a>
                    <?php if (!empty($kids)) : ?>
                        <ul class="pa-shell-submenu">
                            <?php foreach ($kids as $kid) : ?>
                                <li><a href="<?php echo esc_url((string) $kid->url); ?>"><?php echo esc_html($kid->title); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
<?php endif;
}, 5);

// 9. Footer 4 Kolom Resmi + Statistik Pengunjung + Drawer Aksesibilitas WCAG 2.2 & Pembaca Suara Web Speech
add_action('wp_footer', static function () {
    if (is_admin()) {
        return;
    }
    $cfg    = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $satker = $cfg['satker_name'] ?? (get_bloginfo('name') ?: 'Pengadilan Agama');
    $vStats = pa_starter_get_visitor_stats();
    if (!pa_sinova_is_adoption_mode()) :
    ?>
<footer class="pa-shell-footer" role="contentinfo">
    <div class="pa-footer-grid">
        <div class="pa-footer-col">
            <h4><?php echo esc_html($satker); ?></h4>
            <p><?php echo esc_html($cfg['address'] ?? 'Jl. Keadilan Raya No. 1'); ?></p>
            <p><strong>Telp / PTSP:</strong> <?php echo esc_html($cfg['phone'] ?? '-'); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($cfg['email'] ?? '-'); ?></p>
            <?php if (!empty($cfg['hearing_hours'])) : ?>
                <p><strong>Jam Sidang:</strong> <?php echo esc_html($cfg['hearing_hours']); ?></p>
            <?php endif; ?>
        </div>
        <div class="pa-footer-col">
            <h4>Transparansi Publik</h4>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/lra/')); ?>">Laporan Realisasi Anggaran</a></li>
                <li><a href="<?php echo esc_url(home_url('/dipa/')); ?>">Dokumen DIPA 01 &amp; 04</a></li>
                <li><a href="<?php echo esc_url(home_url('/biaya-perkara/')); ?>">Biaya Perkara (LIPA 7)</a></li>
                <li><a href="<?php echo esc_url(home_url('/lkjip/')); ?>">Laporan Kinerja (LKJiP)</a></li>
                <li><a href="<?php echo esc_url(home_url('/survei-pelayanan/')); ?>">Hasil Survei SKM/SPAK/SPKP</a></li>
            </ul>
        </div>
        <div class="pa-footer-col">
            <h4>Layanan &amp; Profil</h4>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/visi-dan-misi/')); ?>">Visi dan Misi</a></li>
                <li><a href="<?php echo esc_url(home_url('/struktur-organisasi/')); ?>">Struktur Organisasi</a></li>
                <li><a href="<?php echo esc_url(home_url('/prosedur-berperkara/')); ?>">Prosedur Berperkara</a></li>
                <li><a href="<?php echo esc_url(home_url('/standar-maklumat-pelayanan/')); ?>">Standar &amp; Maklumat Pelayanan</a></li>
                <li><a href="<?php echo esc_url(home_url('/disabilitas/')); ?>">Layanan Kelompok Rentan &amp; Disabilitas</a></li>
                <li><a href="<?php echo esc_url(home_url('/statistik-kunjungan/')); ?>">Statistik Kunjungan Website</a></li>
            </ul>
        </div>
        <div class="pa-footer-col">
            <h4>Ekosistem &amp; Statistik</h4>
            <ul>
                <li><a href="https://www.mahkamahagung.go.id/" target="_blank" rel="noopener noreferrer">Mahkamah Agung RI</a></li>
                <li><a href="https://badilag.mahkamahagung.go.id/" target="_blank" rel="noopener noreferrer">Ditjen Badilag MA RI</a></li>
                <li><a href="<?php echo esc_url($cfg['aco_url'] ?? 'https://cctv.badilag.net/'); ?>" target="_blank" rel="noopener noreferrer">Access CCTV Online (ACO)</a></li>
                <li><a href="<?php echo esc_url($cfg['siwas_url'] ?? 'https://siwas.mahkamahagung.go.id/'); ?>" target="_blank" rel="noopener noreferrer">SIWAS Mahkamah Agung</a></li>
            </ul>
            <div class="pa-footer-vis-box">
                <div class="pa-footer-vis-row"><span>Hari Ini:</span><strong id="pa-vis-today"><?php echo esc_html(number_format($vStats['today'], 0, ',', '.')); ?></strong></div>
                <div class="pa-footer-vis-row"><span>Bulan Ini:</span><strong id="pa-vis-month"><?php echo esc_html(number_format($vStats['month'], 0, ',', '.')); ?></strong></div>
                <div class="pa-footer-vis-row"><span>Total Kunjungan:</span><strong id="pa-vis-total"><?php echo esc_html(number_format($vStats['total'], 0, ',', '.')); ?></strong></div>
            </div>
        </div>
    </div>
    <div class="pa-shell-footer-bottom">
        <span>&copy; <?php echo esc_html((string) wp_date('Y')); ?> <?php echo esc_html($satker); ?> · Portal Resmi Keterbukaan Informasi Publik</span>
        <span class="pa-shell-render-pill">Rendering : <?php echo esc_html(timer_stop(0, 3)); ?> detik.</span>
    </div>
</footer>
    <?php endif; ?>
<button type="button" id="pa-btn-top" aria-label="Kembali ke atas" data-pa-tip="Kembali ke bagian atas halaman">↑</button>
<div id="pa-a11y-dock" role="region" aria-label="Sarana Aksesibilitas Website">
    <button type="button" id="pa-a11y-toggle" aria-expanded="false" aria-controls="pa-a11y-panel" data-pa-tip="Buka panel pengaturan aksesibilitas WCAG 2.2">♿ Aksesibilitas</button>
    <div id="pa-a11y-panel" hidden>
        <div class="pa-a11y-panel-head">
            <span>Aksesibilitas WCAG 2.2</span>
            <button type="button" id="pa-a11y-close-btn" aria-label="Tutup panel aksesibilitas">✕</button>
        </div>
        <div class="pa-a11y-grid">
            <button type="button" id="pa-btn-dark" aria-pressed="false">🌙 Mode Gelap</button>
            <button type="button" id="pa-btn-contrast" aria-pressed="false">◐ Kontras Tinggi</button>
            <button type="button" id="pa-btn-font-up">A+ Perbesar</button>
            <button type="button" id="pa-btn-font-down">A- Perkecil</button>
            <button type="button" id="pa-btn-readable" aria-pressed="false">📖 Spasi Disleksia</button>
            <button type="button" id="pa-btn-links" aria-pressed="false">🔗 Garis Tautan</button>
            <button type="button" id="pa-btn-grayscale" aria-pressed="false">◑ Monokrom</button>
            <button type="button" id="pa-btn-nomotion" aria-pressed="false">⏸ Stop Animasi</button>
            <button type="button" id="pa-btn-speak" aria-pressed="false">🔊 Bacakan</button>
            <button type="button" id="pa-btn-font-reset">↺ Reset</button>
        </div>
    </div>
</div>
<dialog id="pa-cmdk-dialog" aria-labelledby="pa-cmdk-label">
    <div class="pa-cmdk-head">
        <span id="pa-cmdk-label" style="font-weight:800;color:#E5C158;font-size:13px;">PENCARIAN INSTAN</span>
        <input type="search" id="pa-cmdk-input" aria-label="Ketik kata kunci pencarian instan" placeholder="Ketik kata kunci: LRA, jadwal sidang, DIPA, disabilitas..." autocomplete="off">
        <button type="button" id="pa-cmdk-close" aria-label="Tutup pencarian">ESC</button>
    </div>
    <div class="pa-cmdk-results" id="pa-cmdk-results" role="region" aria-live="polite">
        <div class="pa-cmdk-empty">Ketik minimal 2 karakter atau jelajahi pintasan: DIPA, LRA, Jadwal Sidang, e-Court, Disabilitas.</div>
    </div>
</dialog>
<?php if (!function_exists('wp_print_speculation_rules') && !pa_sinova_is_adoption_mode()) : ?>
<script type="speculationrules">
<?php echo wp_json_encode(array(
    'prefetch' => array(
        array(
            'where' => array(
                'and' => array(
                    array('href_matches' => '/*'),
                    array('not' => array('href_matches' => '/wp-admin/*')),
                    array('not' => array('href_matches' => '/wp-login.php*')),
                    array('not' => array('selector_matches' => '[download], [target="_blank"]')),
                ),
            ),
            'eagerness' => 'moderate',
        ),
    ),
), JSON_UNESCAPED_SLASHES); ?>
</script>
<?php endif; ?>
<script id="pa-a11y-js">
(function(){
    var root = document.documentElement;
    var a11yToggle = document.getElementById('pa-a11y-toggle');
    var a11yPanel = document.getElementById('pa-a11y-panel');
    var a11yClose = document.getElementById('pa-a11y-close-btn');
    var btnDark = document.getElementById('pa-btn-dark');
    var btnTopTheme = document.getElementById('pa-topbar-theme-btn');
    var btnContrast = document.getElementById('pa-btn-contrast');
    var btnUp = document.getElementById('pa-btn-font-up');
    var btnDown = document.getElementById('pa-btn-font-down');
    var btnReadable = document.getElementById('pa-btn-readable');
    var btnLinks = document.getElementById('pa-btn-links');
    var btnGray = document.getElementById('pa-btn-grayscale');
    var btnNoMotion = document.getElementById('pa-btn-nomotion');
    var btnSpeak = document.getElementById('pa-btn-speak');
    var btnReset = document.getElementById('pa-btn-font-reset');
    var btnTop = document.getElementById('pa-btn-top');
    var scrollBar = document.getElementById('pa-scroll-progress');
    var heroEl = document.querySelector('.pa-hero-banner');
    var ptspPill = document.getElementById('pa-ptsp-live-pill');
    var menuToggle = document.getElementById('pa-shell-menu-toggle');
    var menuList = document.getElementById('pa-shell-menu-list');
    var clockEl = document.getElementById('pa-live-clock');

    function setA11yOpen(open){
        if(!a11yPanel || !a11yToggle) return;
        a11yToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if(open) a11yPanel.removeAttribute('hidden');
        else a11yPanel.setAttribute('hidden', 'hidden');
    }
    if(a11yToggle && a11yPanel){
        a11yToggle.addEventListener('click', function(){
            setA11yOpen(a11yPanel.hasAttribute('hidden'));
        });
    }
    if(a11yClose){
        a11yClose.addEventListener('click', function(){
            setA11yOpen(false);
            if(a11yToggle) a11yToggle.focus();
        });
    }

    if(clockEl){
        var hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        var bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        var pad = function(n){ return n < 10 ? '0' + n : '' + n; };
        var lastPtspOpen = null;
        var tickClock = function(){
            if(document.hidden) return;
            var d = new Date(Date.now() + 7 * 3600000);
            clockEl.textContent = hari[d.getUTCDay()] + ', ' + d.getUTCDate() + ' ' + bulan[d.getUTCMonth()] + ' ' + d.getUTCFullYear() + ' · ' + pad(d.getUTCHours()) + ':' + pad(d.getUTCMinutes()) + ':' + pad(d.getUTCSeconds()) + ' WIB';
            if(ptspPill){
                var dow = d.getUTCDay(), mins = d.getUTCHours() * 60 + d.getUTCMinutes();
                var open = (dow >= 1 && dow <= 4 && mins >= 480 && mins <= 990) || (dow === 5 && mins >= 480 && mins <= 1020);
                if(open !== lastPtspOpen){
                    lastPtspOpen = open;
                    ptspPill.classList.toggle('is-open', open);
                    ptspPill.classList.toggle('is-closed', !open);
                    ptspPill.textContent = open ? '● PTSP Buka' : '● PTSP Tutup';
                }
            }
        };
        tickClock();
        setInterval(tickClock, 1000);
        document.addEventListener('visibilitychange', function(){ if(!document.hidden) tickClock(); });
    }

    var menuTips = {
        'beranda': 'Halaman utama portal keterbukaan informasi',
        'profil pengadilan': 'Visi, misi, tupoksi, yurisdiksi, struktur, & profil pegawai',
        'layanan hukum': 'Jadwal sidang, e-Court, putusan, panjar, LIPA 7, & panggilan ghaib',
        'transparansi': 'LRA, DIPA 01/04, PNBP, SAKIP, LKJiP, & LHKPN',
        'layanan publik & pengaduan': 'Survei SKM/SPAK/SPKP, posbakum, disabilitas, & pengaduan',
        'berita & publikasi': 'Berita satker, pengumuman MA RI & Badilag, serta MoU'
    };
    document.querySelectorAll('#ast-hf-menu-1 > .menu-item > .menu-link, .ast-builder-menu-1 .main-header-menu > .menu-item > .menu-link, .pa-shell-menu > li > a').forEach(function(a){
        if(!a.hasAttribute('data-pa-tip')){
            var key = (a.textContent || '').replace(/[▾\s]+/g, ' ').trim().toLowerCase();
            if(menuTips[key]) a.setAttribute('data-pa-tip', menuTips[key]);
        }
    });

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var rafLock = false;
    var heroHeight = heroEl ? Math.max(360, heroEl.offsetHeight + 180) : 0;
    var docH = 1;
    function updateDocHeight(){
        docH = Math.max(1, root.scrollHeight - window.innerHeight);
    }
    updateDocHeight();
    window.addEventListener('resize', updateDocHeight, { passive: true });
    window.addEventListener('load', updateDocHeight, { passive: true });
    if('ResizeObserver' in window && document.body){
        try{ new ResizeObserver(updateDocHeight).observe(document.body); }catch(e){}
    }

    function applyScrollEffects(){
        rafLock = false;
        var y = window.scrollY || window.pageYOffset || 0;
        if(scrollBar) scrollBar.style.transform = 'scaleX(' + Math.min(1, Math.max(0, y / docH)).toFixed(4) + ')';
        root.classList.toggle('pa-is-scrolled', y > 50);
        if(btnTop) btnTop.classList.toggle('is-visible', y > 360);
        if(heroEl && !reduceMotion && root.getAttribute('data-pa-nomotion') !== '1' && y <= heroHeight){
            heroEl.style.setProperty('--pa-py', (y * 0.22).toFixed(1) + 'px');
            heroEl.style.setProperty('--pa-py-rev', (-y * 0.14).toFixed(1) + 'px');
            heroEl.style.setProperty('--pa-py-fg', (y * 0.06).toFixed(1) + 'px');
        }
    }
    window.addEventListener('scroll', function(){
        if(!rafLock){
            rafLock = true;
            window.requestAnimationFrame(applyScrollEffects);
        }
    }, { passive: true });
    applyScrollEffects();
    if(btnTop){
        btnTop.addEventListener('click', function(){
            window.scrollTo({ top: 0, behavior: (reduceMotion || root.getAttribute('data-pa-nomotion') === '1') ? 'auto' : 'smooth' });
        });
    }

    function pingVisit(){
        if(!window.fetch) return;
        fetch(<?php echo wp_json_encode(rest_url('pa-portal/v1/visit')); ?>, { method: 'POST', credentials: 'same-origin' })
            .then(function(r){ return r.ok ? r.json() : null; })
            .then(function(d){
                if(!d || typeof d.total === 'undefined') return;
                var fmt = function(n){ return Number(n).toLocaleString('id-ID'); };
                ['today','month','total'].forEach(function(k){
                    var f = document.getElementById('pa-vis-' + k);
                    var p = document.getElementById('pa-page-vis-' + k);
                    if(f) f.textContent = fmt(d[k]);
                    if(p) p.textContent = fmt(d[k]);
                });
            }).catch(function(){});
    }
    if('requestIdleCallback' in window){
        window.requestIdleCallback(pingVisit, { timeout: 2500 });
    } else {
        setTimeout(pingVisit, 1200);
    }

    if(menuToggle && menuList){
        menuToggle.addEventListener('click', function(){
            var open = menuList.classList.toggle('is-open');
            menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    document.querySelectorAll('.pa-starter-survey').forEach(function(panel){
        panel.querySelectorAll('.survey-dot').forEach(function(btn){
            btn.addEventListener('click', function(){
                var idx = btn.getAttribute('data-target');
                panel.querySelectorAll('.survey-dot').forEach(function(b){
                    var on = b === btn;
                    b.classList.toggle('is-active', on);
                    b.setAttribute('aria-pressed', on ? 'true' : 'false');
                });
                panel.querySelectorAll('.survey-slide').forEach(function(s){
                    s.classList.toggle('is-active', s.getAttribute('data-slide') === idx);
                });
            });
        });
    });

    function syncState(){
        var dark = root.getAttribute('data-pa-theme') === 'dark';
        var hi = root.getAttribute('data-pa-contrast') === 'high';
        if(btnDark){ btnDark.setAttribute('aria-pressed', dark ? 'true' : 'false'); btnDark.textContent = dark ? '☀️ Mode Terang' : '🌙 Mode Gelap'; }
        if(btnTopTheme){ btnTopTheme.setAttribute('aria-pressed', dark ? 'true' : 'false'); btnTopTheme.textContent = dark ? '☀️ Mode Terang' : '🌙 Mode Gelap'; }
        if(btnContrast){ btnContrast.setAttribute('aria-pressed', hi ? 'true' : 'false'); }
        if(btnReadable){ btnReadable.setAttribute('aria-pressed', root.getAttribute('data-pa-readable') === '1' ? 'true' : 'false'); }
        if(btnLinks){ btnLinks.setAttribute('aria-pressed', root.getAttribute('data-pa-links') === '1' ? 'true' : 'false'); }
        if(btnGray){ btnGray.setAttribute('aria-pressed', root.getAttribute('data-pa-grayscale') === '1' ? 'true' : 'false'); }
        if(btnNoMotion){ btnNoMotion.setAttribute('aria-pressed', root.getAttribute('data-pa-nomotion') === '1' ? 'true' : 'false'); }
    }
    function toggleDarkMode(){
        var next = root.getAttribute('data-pa-theme') === 'dark' ? '' : 'dark';
        if(next) root.setAttribute('data-pa-theme', next); else root.removeAttribute('data-pa-theme');
        try{ localStorage.setItem('pa_starter_theme', next); }catch(e){}
        syncState();
    }
    function bindFlagToggle(btn, attr, storeKey){
        if(!btn) return;
        btn.addEventListener('click', function(){
            var next = root.getAttribute(attr) === '1' ? '' : '1';
            if(next) root.setAttribute(attr, '1'); else root.removeAttribute(attr);
            try{ localStorage.setItem(storeKey, next); }catch(e){}
            syncState();
        });
    }
    if(btnDark){ btnDark.addEventListener('click', toggleDarkMode); }
    if(btnTopTheme){ btnTopTheme.addEventListener('click', toggleDarkMode); }
    if(btnContrast){
        btnContrast.addEventListener('click', function(){
            var next = root.getAttribute('data-pa-contrast') === 'high' ? '' : 'high';
            if(next) root.setAttribute('data-pa-contrast', next); else root.removeAttribute('data-pa-contrast');
            try{ localStorage.setItem('pa_starter_contrast', next); }catch(e){}
            syncState();
        });
    }
    bindFlagToggle(btnReadable, 'data-pa-readable', 'pa_starter_readable');
    bindFlagToggle(btnLinks, 'data-pa-links', 'pa_starter_links');
    bindFlagToggle(btnGray, 'data-pa-grayscale', 'pa_starter_grayscale');
    bindFlagToggle(btnNoMotion, 'data-pa-nomotion', 'pa_starter_nomotion');

    if(btnUp){
        btnUp.addEventListener('click', function(){
            var cur = parseFloat(getComputedStyle(root).fontSize) || 16;
            var nxt = Math.min(22, cur + 2) + 'px';
            root.style.fontSize = nxt;
            try{ localStorage.setItem('pa_starter_zoom', nxt); }catch(e){}
            updateDocHeight();
        });
    }
    if(btnDown){
        btnDown.addEventListener('click', function(){
            var cur = parseFloat(getComputedStyle(root).fontSize) || 16;
            var nxt = Math.max(12, cur - 2) + 'px';
            root.style.fontSize = nxt;
            try{ localStorage.setItem('pa_starter_zoom', nxt); }catch(e){}
            updateDocHeight();
        });
    }
    if(btnSpeak && 'speechSynthesis' in window){
        btnSpeak.addEventListener('click', function(){
            if(window.speechSynthesis.speaking){
                window.speechSynthesis.cancel();
                btnSpeak.setAttribute('aria-pressed', 'false');
                btnSpeak.textContent = '🔊 Bacakan';
                return;
            }
            var selText = window.getSelection ? String(window.getSelection()).trim() : '';
            var mainEl = document.querySelector('main, .entry-content, .pa-starter-showcase');
            var raw = selText || (mainEl ? (mainEl.innerText || mainEl.textContent || '') : document.title);
            var txt = raw.replace(/\s+/g, ' ').trim().slice(0, 1800);
            if(!txt) return;
            var utter = new SpeechSynthesisUtterance(txt);
            utter.lang = 'id-ID';
            utter.rate = 0.98;
            utter.onend = function(){ btnSpeak.setAttribute('aria-pressed', 'false'); btnSpeak.textContent = '🔊 Bacakan'; };
            btnSpeak.setAttribute('aria-pressed', 'true');
            btnSpeak.textContent = '⏹ Stop Suara';
            window.speechSynthesis.speak(utter);
        });
    }
    if(btnReset){
        btnReset.addEventListener('click', function(){
            root.style.fontSize = '';
            ['data-pa-theme','data-pa-contrast','data-pa-readable','data-pa-links','data-pa-grayscale','data-pa-nomotion'].forEach(function(a){ root.removeAttribute(a); });
            if('speechSynthesis' in window) window.speechSynthesis.cancel();
            if(btnSpeak){ btnSpeak.setAttribute('aria-pressed', 'false'); btnSpeak.textContent = '🔊 Bacakan'; }
            try{
                ['pa_starter_zoom','pa_starter_theme','pa_starter_contrast','pa_starter_readable','pa_starter_links','pa_starter_grayscale','pa_starter_nomotion'].forEach(function(k){ localStorage.removeItem(k); });
            }catch(e){}
            syncState();
            updateDocHeight();
        });
    }
    syncState();

    // Filter Tab Pegawai (ARIA + Keyboard) & Tabel Data Operasional
    var pegBoard = document.getElementById('pa-pegawai-board');
    if(pegBoard){
        var pegTabs = Array.prototype.slice.call(pegBoard.querySelectorAll('.pa-ops-tab'));
        var pegStatus = document.getElementById('pa-pegawai-status');
        function activatePegTab(tab){
            var f = (tab.getAttribute('data-pegawai-filter') || 'Semua').toLowerCase();
            pegTabs.forEach(function(t){
                var on = t === tab;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.setAttribute('tabindex', on ? '0' : '-1');
            });
            var shown = 0;
            pegBoard.querySelectorAll('.pa-pegawai-card').forEach(function(c){
                var g = (c.getAttribute('data-group') || '').toLowerCase();
                var hide = f !== 'semua' && g.indexOf(f) === -1;
                c.classList.toggle('is-hidden', hide);
                if(!hide) shown++;
            });
            if(pegStatus){ pegStatus.textContent = 'Menampilkan ' + shown + ' data kelompok ' + (tab.textContent || ''); }
        }
        pegTabs.forEach(function(tab, idx){
            tab.addEventListener('click', function(){ activatePegTab(tab); });
            tab.addEventListener('keydown', function(e){
                var next = -1;
                if(e.key === 'ArrowRight') next = (idx + 1) % pegTabs.length;
                else if(e.key === 'ArrowLeft') next = (idx - 1 + pegTabs.length) % pegTabs.length;
                else if(e.key === 'Home') next = 0;
                else if(e.key === 'End') next = pegTabs.length - 1;
                if(next !== -1){
                    e.preventDefault();
                    activatePegTab(pegTabs[next]);
                    pegTabs[next].focus();
                }
            });
        });
    }
    document.querySelectorAll('.pa-ops-search[data-ops-table]').forEach(function(inp){
        inp.addEventListener('input', function(){
            var tbl = document.getElementById(inp.getAttribute('data-ops-table'));
            if(!tbl) return;
            var q = (inp.value || '').trim().toLowerCase();
            tbl.querySelectorAll('tbody tr').forEach(function(tr){
                tr.style.display = (!q || (tr.textContent || '').toLowerCase().indexOf(q) !== -1) ? '' : 'none';
            });
        });
    });

    function safeHref(u){
        var s = String(u || '').trim();
        return /^(\/(?!\/)|https?:\/\/)/i.test(s) ? s : '#';
    }

    // Hydrate Official RSS Feeds via DOM API (Trusted Types Safe)
    var feedSec = document.querySelector('.pa-feeds-section[data-pa-feeds-hydrate="1"]');
    if(feedSec && window.fetch){
        var fLimit = parseInt(feedSec.getAttribute('data-pa-feeds-limit') || '5', 10) || 5;
        fetch(<?php echo wp_json_encode(rest_url('pa-portal/v1/official-feeds')); ?>, { credentials: 'same-origin' })
            .then(function(r){ return r.ok ? r.json() : null; })
            .then(function(d){
                if(!d) return;
                function paintList(elId, arr){
                    var ul = document.getElementById(elId);
                    if(!ul || !Array.isArray(arr) || !arr.length) return;
                    var frag = document.createDocumentFragment();
                    arr.slice(0, fLimit).forEach(function(it){
                        var li = document.createElement('li');
                        li.className = 'pa-feed-item';
                        var a = document.createElement('a');
                        a.setAttribute('href', safeHref(it.link));
                        a.setAttribute('target', '_blank');
                        a.setAttribute('rel', 'noopener noreferrer');
                        if(it.date_label){
                            var dt = document.createElement('span');
                            dt.className = 'pa-feed-date';
                            dt.textContent = it.date_label;
                            a.appendChild(dt);
                        }
                        var ttl = document.createElement('strong');
                        ttl.className = 'pa-feed-title';
                        ttl.textContent = it.title || '';
                        a.appendChild(ttl);
                        var ext = document.createElement('span');
                        ext.className = 'pa-feed-ext';
                        ext.setAttribute('aria-hidden', 'true');
                        ext.textContent = '↗';
                        a.appendChild(ext);
                        li.appendChild(a);
                        if(it.excerpt){
                            var ex = document.createElement('span');
                            ex.className = 'pa-feed-excerpt';
                            ex.textContent = it.excerpt;
                            li.appendChild(ex);
                        }
                        frag.appendChild(li);
                    });
                    ul.replaceChildren(frag);
                }
                paintList('pa-feed-list-ma', d.ma);
                paintList('pa-feed-list-badilag', d.badilag);
            }).catch(function(){});
    }

    // Command Palette Ctrl+K (Trusted Types Safe)
    var cmdDlg = document.getElementById('pa-cmdk-dialog');
    var cmdInput = document.getElementById('pa-cmdk-input');
    var cmdRes = document.getElementById('pa-cmdk-results');
    var cmdClose = document.getElementById('pa-cmdk-close');
    var cmdTimer = null;
    var quickUrl = <?php echo wp_json_encode(rest_url('pa-portal/v1/quick-search')); ?>;

    function renderCmdItems(list){
        if(!cmdRes) return;
        if(!list || !list.length){
            var empty = document.createElement('div');
            empty.className = 'pa-cmdk-empty';
            empty.textContent = 'Tidak ditemukan hasil yang cocok. Coba kata kunci lain atau tekan Enter pada pencarian utama.';
            cmdRes.replaceChildren(empty);
            return;
        }
        var frag = document.createDocumentFragment();
        list.forEach(function(it){
            var a = document.createElement('a');
            a.className = 'pa-cmdk-item';
            var href = safeHref(it.url);
            a.setAttribute('href', href);
            if(it.type === 'doc' && /^https?:\/\//i.test(href)){
                a.setAttribute('target', '_blank');
                a.setAttribute('rel', 'noopener noreferrer');
            }
            var box = document.createElement('div');
            var strong = document.createElement('strong');
            strong.textContent = it.title || '';
            var small = document.createElement('small');
            small.textContent = it.desc || '';
            box.appendChild(strong);
            box.appendChild(small);
            var badge = document.createElement('span');
            badge.className = 'pa-cmdk-badge';
            badge.textContent = it.badge || it.type || 'Halaman';
            a.appendChild(box);
            a.appendChild(badge);
            frag.appendChild(a);
        });
        cmdRes.replaceChildren(frag);
    }
    function fetchCmd(q){
        if(!window.fetch) return;
        fetch(quickUrl + '?q=' + encodeURIComponent(q || ''), { credentials: 'same-origin' })
            .then(function(r){ return r.ok ? r.json() : null; })
            .then(function(d){ if(d && Array.isArray(d.items)) renderCmdItems(d.items); })
            .catch(function(){});
    }
    function openCmd(){
        if(!cmdDlg || typeof cmdDlg.showModal !== 'function') return;
        if(!cmdDlg.open) cmdDlg.showModal();
        if(cmdInput){
            cmdInput.focus();
            fetchCmd(cmdInput.value || '');
        }
    }
    document.querySelectorAll('#pa-cmdk-open').forEach(function(b){ b.addEventListener('click', openCmd); });
    if(cmdClose && cmdDlg){ cmdClose.addEventListener('click', function(){ cmdDlg.close(); }); }
    if(cmdDlg){
        cmdDlg.addEventListener('click', function(e){ if(e.target === cmdDlg) cmdDlg.close(); });
    }
    if(cmdInput){
        cmdInput.addEventListener('input', function(){
            clearTimeout(cmdTimer);
            cmdTimer = setTimeout(function(){ fetchCmd(cmdInput.value || ''); }, 180);
        });
    }
    document.addEventListener('keydown', function(e){
        if((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')){
            e.preventDefault();
            if(cmdDlg && cmdDlg.open) cmdDlg.close(); else openCmd();
        } else if(e.key === 'Escape' && a11yPanel && !a11yPanel.hasAttribute('hidden')){
            setA11yOpen(false);
            if(a11yToggle) a11yToggle.focus();
        }
    });
})();
</script>
    <?php
}, 95);
