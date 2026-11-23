<?php
/**
 * Plugin Name: PA SINOVA Starter - UI Portal PA (Astra & Blok), Berita Dinamis, Aksesibilitas & Performa
 * Description: Menyatukan palet Hijau Gelap (#056e4f) & Royal Gold (#C9A227) untuk Astra Free maupun tema blok WordPress, Topbar & Footer 4 Kolom resmi Pengadilan Agama, Shortcode Sambutan & Berita Terkini, Capture-Safe SINOVA, serta Toolbar Aksesibilitas WCAG 2.2 AA.
 * Version: 1.2.0-starter
 */

defined('ABSPATH') || exit;

// 1. Satukan Webfont menjadi 'Inter' tunggal + Preconnect
add_filter('astra_google_fonts', '__return_empty_array', 999);
add_filter('elementor/frontend/print_google_fonts', '__return_false', 999);

add_filter('body_class', static function ($classes) {
    if (is_admin()) {
        return $classes;
    }
    $classes[] = 'pa-sinova-portal';
    $classes[] = (function_exists('wp_is_block_theme') && wp_is_block_theme()) ? 'pa-sinova-block-shell' : 'pa-sinova-classic-shell';
    return $classes;
});

add_action('wp_head', static function () {
    if (is_admin()) {
        return;
    }
    echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    echo '<meta name="color-scheme" content="light dark">' . "\n";
    echo '<script id="pa-a11y-boot">try{var t=localStorage.getItem("pa_starter_theme"),c=localStorage.getItem("pa_starter_contrast"),z=localStorage.getItem("pa_starter_zoom");if(!t&&/[?&]theme=dark(?:&|$)/.test(location.search))t="dark";if(t==="dark")document.documentElement.setAttribute("data-pa-theme","dark");if(c==="high")document.documentElement.setAttribute("data-pa-contrast","high");if(z)document.documentElement.style.fontSize=z;}catch(e){}</script>' . "\n";
}, 2);

add_action('wp_enqueue_scripts', static function () {
    if (is_admin()) {
        return;
    }
    wp_enqueue_style(
        'pa-starter-inter',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
        array(),
        null
    );
}, 999);

// 2. Shortcode [pa_sinova_welcome_section]: Sambutan Pimpinan + Standar Maklumat Pelayanan PTSP
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

// 3. Shortcode [pa_sinova_latest_posts count="3"]: Berita & Pengumuman Terkini Dinamis
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
                <h2 class="pa-starter-heading" style="text-align:left;">BERITA &amp; PENGUMUMAN TERKINI</h2>
                <p class="pa-starter-sub" style="text-align:left;margin-bottom:0;">Informasi Kegiatan, Publikasi, dan Pemberitahuan Resmi Satuan Kerja</p>
            </div>
            <a class="pa-news-all" href="<?php echo esc_url(home_url('/?post_type=post')); ?>">Semua Berita →</a>
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

// 4. CSS Terpadu Portal Pengadilan Agama (Astra Free + Blok Theme)
add_action('wp_head', static function () {
    if (is_admin()) {
        return;
    }
    ?>
<style id="pa-sinova-starter-ui-css">
:root {
    --pa-green: #056e4f;
    --pa-green-dark: #043927;
    --pa-green-deep: #022c22;
    --pa-gold: #C9A227;
    --pa-gold-light: #E5C158;
    --e-global-typography-primary-font-family: 'Inter', system-ui, -apple-system, sans-serif;
    --e-global-typography-secondary-font-family: 'Inter', system-ui, -apple-system, sans-serif;
}
body, button, input, select, textarea {
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}
.pa-scroll-reveal, .elementor-invisible {
    opacity: 1 !important;
    visibility: visible !important;
    animation: none !important;
}

/* Sembunyikan shell bawaan Block Theme & Footer Astra standar agar konsisten memakai Footer 4 Kolom PA */
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
    font-size: clamp(1.4rem, 2.4vw, 2rem);
    border-bottom: 3px solid var(--pa-gold);
    padding-bottom: 8px;
    margin-bottom: 18px;
}

/* Styling Header Native Tema Astra menjadi 2 Baris Rapi (Brand + Navbar 1-Garis Hijau Gelap–Gold) */
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
    justify-content: flex-start !important;
}
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
body.pa-sinova-classic-shell .site-description {
    color: #ffffff !important;
}
body.pa-sinova-classic-shell .site-description {
    color: #d1fae5 !important;
    font-size: 12px !important;
}
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
body.pa-sinova-classic-shell .ast-mobile-header-wrap .ast-primary-header-bar {
    padding: 10px 14px !important;
}
body.pa-sinova-classic-shell .ast-mobile-menu-buttons .menu-toggle {
    background: rgba(201, 162, 39, 0.2) !important;
    border-color: #C9A227 !important;
    color: #E5C158 !important;
}
body.pa-sinova-classic-shell .ast-mobile-popup-drawer .ast-mobile-popup-inner {
    background: #043927 !important;
}
body.pa-sinova-classic-shell .ast-mobile-popup-drawer .menu-link {
    color: #ffffff !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}

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
.pa-topbar-theme-btn[aria-pressed="true"] {
    background: #E5C158;
    color: #022c22;
}

/* Topbar Institusional (Tampil di Astra maupun Block Theme) */
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
.pa-shell-topbar-links {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    align-items: center;
}
.pa-shell-topbar a {
    color: #E5C158;
    text-decoration: none;
    font-weight: 600;
}
.pa-shell-topbar a:hover {
    text-decoration: underline;
}

/* Fallback Header & Navbar saat Block Theme aktif (jika user belum memasang Astra) */
.pa-shell-masthead {
    background: linear-gradient(135deg, #043927 0%, #056e4f 100%);
    color: #fff;
    padding: 16px;
    border-bottom: 3px solid #C9A227;
}
.pa-shell-brand {
    display: flex;
    align-items: center;
    gap: 14px;
    text-decoration: none;
    color: #fff;
}
.pa-shell-emblem {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #C9A227 0%, #E5C158 100%);
    color: #022c22;
    font-weight: 900;
    font-size: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.pa-shell-brand-title {
    margin: 0;
    font-size: clamp(1.05rem, 2vw, 1.4rem);
    font-weight: 800;
    color: #fff;
    line-height: 1.2;
}
.pa-shell-brand-sub {
    margin: 2px 0 0;
    font-size: 12.5px;
    color: #d1fae5;
}
.pa-shell-badge {
    padding: 6px 12px;
    border-radius: 999px;
    background: rgba(201, 162, 39, 0.16);
    border: 1px solid #C9A227;
    color: #E5C158;
    font-size: 12px;
    font-weight: 700;
}
.pa-shell-navwrap {
    background: #ffffff;
    border-bottom: 1px solid #d5e6dc;
    box-shadow: 0 4px 14px rgba(4, 57, 39, 0.06);
    position: sticky;
    top: 0;
    z-index: 9990;
}
.pa-shell-nav-row {
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.pa-shell-menu-btn {
    display: none;
    margin: 8px 0;
    padding: 8px 14px;
    border-radius: 8px;
    border: 1.5px solid #056e4f;
    background: #f4faf7;
    color: #043927;
    font-weight: 700;
    cursor: pointer;
}
.pa-shell-menu {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 2px;
}
.pa-shell-menu > li { position: relative; }
.pa-shell-menu > li > a {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 13px 12px;
    color: #043927;
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 700;
    border-bottom: 2px solid transparent;
}
.pa-shell-menu > li > a:hover,
.pa-shell-menu > li:focus-within > a {
    color: #056e4f;
    background: #f4faf7;
    border-bottom-color: #C9A227;
}
.pa-shell-submenu {
    list-style: none;
    margin: 0;
    padding: 6px;
    position: absolute;
    left: 0;
    top: 100%;
    min-width: 270px;
    max-height: 70vh;
    overflow-y: auto;
    background: #ffffff;
    border: 1px solid #d5e6dc;
    border-top: 3px solid #C9A227;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 14px 32px rgba(4, 57, 39, 0.16);
    display: none;
    z-index: 9995;
}
.pa-shell-menu > li:hover > .pa-shell-submenu,
.pa-shell-menu > li:focus-within > .pa-shell-submenu { display: block; }
.pa-shell-submenu a {
    display: block;
    padding: 8px 12px;
    border-radius: 8px;
    color: #1e293b;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}
.pa-shell-submenu a:hover {
    background: linear-gradient(135deg, #043927 0%, #056e4f 100%);
    color: #E5C158;
}
@media (max-width: 960px) {
    .pa-shell-menu-btn { display: inline-flex; }
    .pa-shell-nav-row { flex-direction: column; align-items: stretch; }
    .pa-shell-menu { display: none; flex-direction: column; align-items: stretch; padding-bottom: 10px; }
    .pa-shell-menu.is-open { display: flex; }
    .pa-shell-submenu { position: static; box-shadow: none; border-radius: 8px; margin: 0 0 6px 12px; }
}

/* CSS Beranda Portal Resmi Pengadilan Agama */
.pa-starter-showcase {
    max-width: 1180px;
    margin: 6px auto 28px;
    font-family: 'Inter', system-ui, sans-serif;
}
.pa-ticker-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 10px;
    background: #f4faf7;
    border: 1px solid #d5e6dc;
    border-left: 4px solid #C9A227;
    margin-bottom: 18px;
    font-size: 12.5px;
    color: #043927;
}
.pa-ticker-tag {
    padding: 3px 9px;
    border-radius: 6px;
    background: #056e4f;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.04em;
}
.pa-ticker-text {
    flex: 1;
    min-width: 220px;
    font-weight: 600;
}
.pa-ticker-link {
    color: #056e4f !important;
    font-weight: 800;
    text-decoration: none !important;
    white-space: nowrap;
}
.pa-ticker-link:hover { text-decoration: underline !important; }

.pa-hero-banner {
    padding: clamp(28px, 4.5vw, 48px) clamp(20px, 4vw, 42px);
    border-radius: 18px;
    background: radial-gradient(circle at top right, rgba(201, 162, 39, 0.24), transparent 52%),
                linear-gradient(135deg, #022c22 0%, #044e3a 52%, #056e4f 100%);
    border: 1.5px solid #C9A227;
    color: #fff;
    text-align: center;
    box-shadow: 0 14px 34px rgba(4, 57, 39, 0.18);
    margin-bottom: 30px;
}
.pa-hero-kicker {
    display: inline-block;
    padding: 5px 14px;
    border-radius: 999px;
    background: rgba(201, 162, 39, 0.18);
    border: 1px solid #C9A227;
    color: #E5C158;
    font-size: 11.5px;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    margin-bottom: 12px;
}
.pa-hero-title {
    margin: 0 0 10px;
    color: #fff;
    font-size: clamp(1.5rem, 3.2vw, 2.3rem);
    font-weight: 800;
    line-height: 1.2;
}
.pa-hero-lead {
    margin: 0 auto 22px;
    max-width: 760px;
    color: #d1fae5;
    font-size: 0.98rem;
    line-height: 1.55;
}
.pa-hero-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}
.pa-btn-gold {
    display: inline-flex;
    align-items: center;
    padding: 11px 20px;
    border-radius: 10px;
    background: linear-gradient(135deg, #C9A227 0%, #E5C158 100%);
    color: #022c22 !important;
    font-weight: 800;
    font-size: 13.5px;
    text-decoration: none !important;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.18);
}
.pa-btn-outline {
    display: inline-flex;
    align-items: center;
    padding: 11px 18px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.1);
    border: 1.5px solid rgba(229, 193, 88, 0.55);
    color: #fff !important;
    font-weight: 700;
    font-size: 13.5px;
    text-decoration: none !important;
}
.pa-btn-outline:hover { background: rgba(255, 255, 255, 0.2); }

.pa-section-block {
    margin-bottom: 32px;
    text-align: center;
}
.pa-starter-heading {
    margin: 0 0 4px;
    color: #056e4f;
    font-weight: 800;
    font-size: 1.38rem;
}
.pa-starter-sub {
    margin: 0 0 18px;
    color: #4b5563;
    font-weight: 600;
    font-size: 0.92rem;
}
.pa-services-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    text-align: left;
}
@media (max-width: 960px) { .pa-services-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 520px) { .pa-services-grid { grid-template-columns: 1fr; } }
.pa-service-card {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 16px;
    border-radius: 14px;
    background: #ffffff;
    border: 1.5px solid #d5e6dc;
    border-top: 4px solid #056e4f;
    color: #1f2937 !important;
    text-decoration: none !important;
    box-shadow: 0 6px 16px rgba(4, 57, 39, 0.06);
    transition: transform 0.18s ease, border-color 0.18s ease;
}
.pa-service-card:hover {
    transform: translateY(-3px);
    border-color: #C9A227;
}
.pa-svc-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.pa-svc-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    background: #ecfdf5;
    color: #056e4f;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.pa-svc-icon svg { width: 18px; height: 18px; }
.pa-svc-badge {
    padding: 2px 8px;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    color: #043927;
    font-size: 10.5px;
    font-weight: 800;
}
.pa-service-card strong {
    font-size: 14.5px;
    color: #043927;
}
.pa-service-card span:last-child {
    font-size: 12.5px;
    line-height: 1.45;
    color: #4b5563;
}

/* Sambutan Pimpinan & Maklumat Pelayanan */
.pa-welcome-grid {
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    gap: 18px;
    margin-bottom: 32px;
    text-align: left;
}
@media (max-width: 860px) { .pa-welcome-grid { grid-template-columns: 1fr; } }
.pa-welcome-card {
    padding: 22px;
    border-radius: 16px;
    background: #f4faf7;
    border: 1.5px solid #d5e6dc;
    border-left: 5px solid #056e4f;
}
.pa-wc-kicker {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    background: #056e4f;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    margin-bottom: 8px;
}
.pa-welcome-card h3 {
    margin: 0 0 10px;
    color: #043927;
    font-size: 1.15rem;
    font-weight: 800;
}
.pa-wc-body {
    margin: 0 0 14px;
    color: #334155;
    font-size: 0.92rem;
    line-height: 1.6;
}
.pa-wc-links {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.pa-wc-links a {
    padding: 6px 12px;
    border-radius: 8px;
    background: #ffffff;
    border: 1px solid #056e4f;
    color: #043927 !important;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none !important;
}
.pa-wc-links a:hover {
    background: #056e4f;
    color: #ffffff !important;
}
.pa-maklumat-card {
    padding: 22px;
    border-radius: 16px;
    background: linear-gradient(155deg, #043927 0%, #056e4f 100%);
    border: 1.5px solid #C9A227;
    color: #ffffff;
}
.pa-mk-kicker {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    background: rgba(229, 193, 88, 0.18);
    border: 1px solid #C9A227;
    color: #E5C158;
    font-size: 11px;
    font-weight: 800;
    margin-bottom: 8px;
}
.pa-maklumat-card h3 {
    margin: 0 0 8px;
    color: #fff;
    font-size: 1.15rem;
    font-weight: 800;
}
.pa-mk-quote {
    margin: 0 0 12px;
    color: #e6f4ee;
    font-size: 12.5px;
    line-height: 1.55;
    font-style: italic;
}
.pa-mk-hours {
    list-style: none;
    margin: 0 0 12px;
    padding: 10px 12px;
    border-radius: 10px;
    background: rgba(0, 0, 0, 0.18);
    font-size: 12.5px;
    line-height: 1.65;
}
.pa-mk-btn {
    color: #E5C158 !important;
    font-size: 12.5px;
    font-weight: 800;
    text-decoration: none !important;
}
.pa-mk-btn:hover { text-decoration: underline !important; }

/* Zona Integritas 6 Kartu Sejajar */
.pa-zi-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 12px;
    align-items: stretch;
}
@media (max-width: 960px) { .pa-zi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 520px) { .pa-zi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.pa-zi-card {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 18px 12px;
    border-radius: 12px;
    background: linear-gradient(135deg, #056e4f 0%, #043927 100%);
    border: 1.5px solid #C9A227;
    color: #fff !important;
    text-decoration: none !important;
    box-shadow: 0 6px 14px rgba(4, 57, 39, 0.18);
    transition: transform 0.2s ease;
}
.pa-zi-card:hover {
    transform: translateY(-3px);
    border-color: #E5C158;
}
.pa-zi-icon {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    color: #E5C158;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
}
.pa-zi-icon svg { width: 18px; height: 18px; }
.pa-zi-area {
    font-size: 15px;
    font-weight: 800;
    color: #E5C158;
    margin-bottom: 4px;
}
.pa-zi-desc {
    font-size: 11.5px;
    line-height: 1.32;
    font-weight: 600;
    min-height: 2.64em;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

/* Berita & Pengumuman Terkini */
.pa-news-head {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: flex-end;
    gap: 10px;
    margin-bottom: 16px;
}
.pa-news-all {
    color: #056e4f !important;
    font-weight: 800;
    font-size: 13px;
    text-decoration: none !important;
}
.pa-news-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    text-align: left;
}
@media (max-width: 860px) { .pa-news-grid { grid-template-columns: 1fr; } }
.pa-news-card {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 8px;
    padding: 18px;
    border-radius: 14px;
    background: #ffffff;
    border: 1px solid #d5e6dc;
    box-shadow: 0 4px 14px rgba(4, 57, 39, 0.05);
}
.pa-news-date {
    font-size: 11.5px;
    font-weight: 700;
    color: #8A6808;
}
.pa-news-card h3 {
    margin: 0;
    font-size: 15px;
    line-height: 1.4;
}
.pa-news-card h3 a {
    color: #043927 !important;
    text-decoration: none !important;
}
.pa-news-card h3 a:hover { color: #056e4f !important; }
.pa-news-card p {
    margin: 0;
    font-size: 12.5px;
    line-height: 1.5;
    color: #4b5563;
}
.pa-news-more {
    color: #056e4f !important;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none !important;
}

/* Split Survei + Pintasan SINOVA */
.pa-split-section {
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 20px;
    align-items: stretch;
}
@media (max-width: 880px) { .pa-split-section { grid-template-columns: 1fr; } }
.pa-starter-survey {
    padding: 18px;
    border-radius: 16px;
    background: linear-gradient(155deg, #065f46 0%, #044e3a 52%, #022c22 100%);
    border: 1.5px solid #C9A227;
    color: #fff;
    text-align: left;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.pa-starter-survey .survey-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 10.5px;
    font-weight: 800;
}
.pa-starter-survey .survey-pill {
    padding: 3px 9px;
    border-radius: 999px;
    background: rgba(229, 193, 88, 0.18);
    border: 1px solid #C9A227;
    color: #E5C158;
}
.pa-starter-survey .survey-period-badge {
    padding: 3px 9px;
    border-radius: 999px;
    background: #d1fae5;
    color: #022c22;
}
.pa-starter-survey h3 {
    margin: 0 0 10px;
    color: #fff;
    font-size: 16.5px;
    font-weight: 800;
}
.pa-starter-survey .survey-slide { display: none; }
.pa-starter-survey .survey-slide.is-active { display: block; }
.pa-starter-survey .survey-cards { display: flex; flex-direction: column; gap: 8px; }
.pa-starter-survey .survey-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.18);
    color: #fff !important;
    text-decoration: none !important;
    font-size: 12px;
}
.pa-starter-survey a.survey-card:hover {
    background: rgba(201, 162, 39, 0.22);
    border-color: #E5C158;
}
.pa-starter-survey .survey-dots {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-top: 14px;
}
.pa-starter-survey .survey-dot {
    padding: 5px 11px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.28);
    background: transparent;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}
.pa-starter-survey .survey-dot.is-active {
    background: #E5C158;
    color: #022c22;
    border-color: #E5C158;
}
.pa-hub-shortcuts {
    background: #f4faf7;
    border: 1.5px solid #d5e6dc;
    border-radius: 16px;
    padding: 20px;
    text-align: left;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.pa-hub-shortcuts-head h3 {
    margin: 0 0 4px;
    color: #043927;
    font-size: 1.15rem;
    font-weight: 800;
}
.pa-hub-shortcuts-head p {
    margin: 0 0 14px;
    color: #4b5563;
    font-size: 0.88rem;
}
.pa-shortcut-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}
@media (max-width: 520px) { .pa-shortcut-grid { grid-template-columns: 1fr; } }
.pa-shortcut-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 12px 14px;
    border-radius: 10px;
    background: #ffffff;
    border: 1px solid #d5e6dc;
    text-decoration: none !important;
    transition: border-color 0.15s ease, transform 0.15s ease;
}
.pa-shortcut-item:hover { border-color: #056e4f; transform: translateY(-2px); }
.pa-shortcut-item span { font-size: 11px; font-weight: 800; color: #8A6808; text-transform: uppercase; }
.pa-shortcut-item strong { font-size: 13.5px; color: #043927; }

/* Footer 4 Kolom Resmi Pengadilan Agama */
.pa-shell-footer {
    background: #022c22;
    color: #d1fae5;
    border-top: 4px solid #C9A227;
    padding: 34px 16px 24px;
    font-size: 13px;
}
.pa-footer-grid {
    max-width: 1180px;
    margin: 0 auto 22px;
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr 1fr;
    gap: 24px;
}
@media (max-width: 900px) { .pa-footer-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 540px) { .pa-footer-grid { grid-template-columns: 1fr; } }
.pa-footer-col h4 {
    margin: 0 0 10px;
    color: #E5C158;
    font-size: 14px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.pa-footer-col p {
    margin: 0 0 8px;
    line-height: 1.55;
    color: #e2e8f0;
    font-size: 12.5px;
}
.pa-footer-col ul {
    list-style: none;
    margin: 0;
    padding: 0;
}
.pa-footer-col li { margin-bottom: 6px; }
.pa-footer-col a {
    color: #d1fae5;
    text-decoration: none;
    font-size: 12.5px;
}
.pa-footer-col a:hover {
    color: #E5C158;
    text-decoration: underline;
}
.pa-shell-footer-bottom {
    max-width: 1180px;
    margin: 0 auto;
    padding-top: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.14);
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    font-size: 12px;
}
.pa-shell-render-pill {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 999px;
    background: rgba(229, 193, 88, 0.16);
    border: 1px solid rgba(229, 193, 88, 0.45);
    color: #E5C158;
    font-size: 12px;
    font-weight: 700;
}

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
html[data-pa-theme="dark"] body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu {
    background: #13221a !important;
    border-color: #2c4637 !important;
    color: #e6f4ee !important;
}
html[data-pa-theme="dark"] .pa-shell-menu > li > a,
html[data-pa-theme="dark"] .pa-shell-submenu a,
html[data-pa-theme="dark"] .pa-ticker-link,
html[data-pa-theme="dark"] body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu .menu-link {
    color: #e6f4ee !important;
}
html[data-pa-theme="dark"] body.pa-sinova-classic-shell:not(.home) .entry-title,
html[data-pa-theme="dark"] body.pa-sinova-block-shell .wp-block-post-title {
    color: #E5C158 !important;
}
html[data-pa-theme="dark"] .pa-service-card,
html[data-pa-theme="dark"] .pa-welcome-card,
html[data-pa-theme="dark"] .pa-news-card,
html[data-pa-theme="dark"] .pa-hub-shortcuts,
html[data-pa-theme="dark"] .pa-shortcut-item {
    background: #182a20;
    border-color: #355241;
    color: #e6f4ee !important;
}
html[data-pa-theme="dark"] .pa-service-card strong,
html[data-pa-theme="dark"] .pa-welcome-card h3,
html[data-pa-theme="dark"] .pa-news-card h3 a,
html[data-pa-theme="dark"] .pa-hub-shortcuts-head h3,
html[data-pa-theme="dark"] .pa-shortcut-item strong,
html[data-pa-theme="dark"] .pa-starter-heading { color: #E5C158 !important; }
html[data-pa-theme="dark"] .pa-service-card span:last-child,
html[data-pa-theme="dark"] .pa-wc-body,
html[data-pa-theme="dark"] .pa-news-card p,
html[data-pa-theme="dark"] .pa-starter-sub,
html[data-pa-theme="dark"] .pa-hub-shortcuts-head p { color: #cbd5e1; }
html[data-pa-theme="dark"] a { color: #E5C158; }
html[data-pa-contrast="high"] body,
html[data-pa-contrast="high"] .site,
html[data-pa-contrast="high"] .wp-site-blocks { background: #ffffff !important; color: #000000 !important; }
html[data-pa-contrast="high"] a { color: #003d29 !important; text-decoration: underline !important; }

#pa-a11y-dock {
    position: fixed;
    left: 14px;
    bottom: 14px;
    z-index: 99990;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 6px;
    border-radius: 14px;
    background: rgba(4, 57, 39, 0.94);
    border: 1.5px solid #C9A227;
    box-shadow: 0 10px 26px rgba(0, 0, 0, 0.25);
}
#pa-a11y-dock button {
    min-height: 36px;
    padding: 5px 10px;
    border: 1px solid rgba(229, 193, 88, 0.35);
    border-radius: 9px;
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
    font: 700 12px/1.2 'Inter', system-ui, sans-serif;
    cursor: pointer;
}
#pa-a11y-dock button:hover,
#pa-a11y-dock button[aria-pressed="true"] { background: #C9A227; color: #043927; }
</style>
    <?php
}, 90);

// 5. Topbar Institusional (Semua Tema) + Shell Header/Menu Saat Memakai Tema Blok WordPress
add_action('wp_body_open', static function () {
    if (is_admin()) {
        return;
    }
    $cfg     = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $satker  = $cfg['satker_name'] ?? (get_bloginfo('name') ?: 'Pengadilan Agama');
    $tagline = get_bloginfo('description') ?: 'Portal Keterbukaan Informasi Publik (SK Dirjen Badilag 2722/2026)';
    $phone   = $cfg['phone'] ?? '';
    $email   = $cfg['email'] ?? '';

    $wibNow  = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
    $daysId  = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
    $monsId  = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $clockInit = $daysId[(int) $wibNow->format('w')] . ', '
        . $wibNow->format('j') . ' '
        . ($monsId[(int) $wibNow->format('n')] ?? $wibNow->format('F')) . ' '
        . $wibNow->format('Y') . ' · ' . $wibNow->format('H:i:s') . ' WIB';
    ?>
<div class="pa-shell-topbar">
    <div class="pa-shell-inner">
        <span id="pa-live-clock" class="pa-live-clock" title="Waktu Indonesia Barat"><?php echo esc_html($clockInit); ?></span>
        <div class="pa-shell-topbar-links">
            <span>PTSP: <?php echo esc_html($phone ?: 'Senin–Jumat'); ?></span>
            <a href="<?php echo esc_url($cfg['sipp_url'] ?? 'https://sipp.pa-contoh.go.id/'); ?>" target="_blank" rel="noopener noreferrer">SIPP</a>
            <a href="<?php echo esc_url($cfg['ecourt_url'] ?? 'https://ecourt.mahkamahagung.go.id/'); ?>" target="_blank" rel="noopener noreferrer">e-Court</a>
            <a href="<?php echo esc_url($cfg['survey_url'] ?? 'https://survei.badilag.net/'); ?>" target="_blank" rel="noopener noreferrer">Survei Badilag</a>
            <a href="<?php echo esc_url(wp_login_url()); ?>">Login Admin</a>
            <button type="button" id="pa-topbar-theme-btn" class="pa-topbar-theme-btn" aria-pressed="false" title="Ubah Mode Terang/Gelap">🌙 Mode Gelap</button>
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
        <span class="pa-shell-badge">SK Dirjen Badilag 2722/2026</span>
    </div>
</header>
<?php if (!empty($topItems)) : ?>
<nav class="pa-shell-navwrap" aria-label="Menu Utama SINOVA">
    <div class="pa-shell-nav-row">
        <button type="button" class="pa-shell-menu-btn" id="pa-shell-menu-toggle" aria-expanded="false">☰ Menu Navigasi</button>
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

// 6. Footer 4 Kolom Resmi Pengadilan Agama + Toolbar Aksesibilitas & Slider JS
add_action('wp_footer', static function () {
    if (is_admin()) {
        return;
    }
    $cfg    = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $satker = $cfg['satker_name'] ?? (get_bloginfo('name') ?: 'Pengadilan Agama');
    ?>
<footer class="pa-shell-footer" role="contentinfo">
    <div class="pa-footer-grid">
        <div class="pa-footer-col">
            <h4><?php echo esc_html($satker); ?></h4>
            <p><?php echo esc_html($cfg['address'] ?? 'Jl. Keadilan Raya No. 1'); ?></p>
            <p><strong>Telp / PTSP:</strong> <?php echo esc_html($cfg['phone'] ?? '-'); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($cfg['email'] ?? '-'); ?></p>
        </div>
        <div class="pa-footer-col">
            <h4>Transparansi SINOVA</h4>
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
                <li><a href="<?php echo esc_url(home_url('/statistik-kunjungan/')); ?>">Statistik Kunjungan Website</a></li>
            </ul>
        </div>
        <div class="pa-footer-col">
            <h4>Tautan Lembaga</h4>
            <ul>
                <li><a href="https://www.mahkamahagung.go.id/" target="_blank" rel="noopener noreferrer">Mahkamah Agung RI</a></li>
                <li><a href="https://badilag.mahkamahagung.go.id/" target="_blank" rel="noopener noreferrer">Ditjen Badilag MA RI</a></li>
                <li><a href="https://siwas.mahkamahagung.go.id/" target="_blank" rel="noopener noreferrer">SIWAS Mahkamah Agung</a></li>
                <li><a href="https://putusan3.mahkamahagung.go.id/" target="_blank" rel="noopener noreferrer">Direktori Putusan</a></li>
                <li><a href="https://www.lapor.go.id/" target="_blank" rel="noopener noreferrer">SP4N-LAPOR! Nasional</a></li>
            </ul>
        </div>
    </div>
    <div class="pa-shell-footer-bottom">
        <span>&copy; <?php echo esc_html((string) date('Y')); ?> <?php echo esc_html($satker); ?> · Keterbukaan Informasi Publik SK Dirjen Badilag 2722/2026</span>
        <span class="pa-shell-render-pill">Rendering : <?php echo esc_html(timer_stop(0, 3)); ?> detik.</span>
    </div>
</footer>
<div id="pa-a11y-dock" role="region" aria-label="Menu Aksesibilitas Website">
    <button type="button" id="pa-btn-dark" aria-pressed="false" title="Ubah Mode Terang/Gelap">Mode Gelap</button>
    <button type="button" id="pa-btn-contrast" aria-pressed="false" title="Kontras Tinggi WCAG">Kontras</button>
    <button type="button" id="pa-btn-font-up" title="Perbesar Teks">A+</button>
    <button type="button" id="pa-btn-font-reset" title="Reset Ukuran Teks">Reset</button>
</div>
<script id="pa-a11y-js">
(function(){
    var root = document.documentElement;
    var btnDark = document.getElementById('pa-btn-dark');
    var btnTopTheme = document.getElementById('pa-topbar-theme-btn');
    var btnContrast = document.getElementById('pa-btn-contrast');
    var btnUp = document.getElementById('pa-btn-font-up');
    var btnReset = document.getElementById('pa-btn-font-reset');
    var menuToggle = document.getElementById('pa-shell-menu-toggle');
    var menuList = document.getElementById('pa-shell-menu-list');
    var clockEl = document.getElementById('pa-live-clock');

    if(clockEl){
        var hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        var bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        var pad = function(n){ return n < 10 ? '0' + n : '' + n; };
        var tickClock = function(){
            var d = new Date();
            clockEl.textContent = hari[d.getDay()] + ', ' + d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear() + ' · ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds()) + ' WIB';
        };
        tickClock();
        setInterval(tickClock, 1000);
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
                panel.querySelectorAll('.survey-dot').forEach(function(b){ b.classList.toggle('is-active', b === btn); });
                panel.querySelectorAll('.survey-slide').forEach(function(s){ s.classList.toggle('is-active', s.getAttribute('data-slide') === idx); });
            });
        });
    });

    function syncState(){
        var dark = root.getAttribute('data-pa-theme') === 'dark';
        var hi = root.getAttribute('data-pa-contrast') === 'high';
        if(btnDark){ btnDark.setAttribute('aria-pressed', dark ? 'true' : 'false'); btnDark.textContent = dark ? 'Mode Terang' : 'Mode Gelap'; }
        if(btnTopTheme){ btnTopTheme.setAttribute('aria-pressed', dark ? 'true' : 'false'); btnTopTheme.textContent = dark ? '☀️ Mode Terang' : '🌙 Mode Gelap'; }
        if(btnContrast){ btnContrast.setAttribute('aria-pressed', hi ? 'true' : 'false'); }
    }
    function toggleDarkMode(){
        var next = root.getAttribute('data-pa-theme') === 'dark' ? '' : 'dark';
        if(next) root.setAttribute('data-pa-theme', next); else root.removeAttribute('data-pa-theme');
        try{ localStorage.setItem('pa_starter_theme', next); }catch(e){}
        syncState();
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
    if(btnUp){
        btnUp.addEventListener('click', function(){
            var cur = parseFloat(getComputedStyle(root).fontSize) || 16;
            var nxt = Math.min(22, cur + 2) + 'px';
            root.style.fontSize = nxt;
            try{ localStorage.setItem('pa_starter_zoom', nxt); }catch(e){}
        });
    }
    if(btnReset){
        btnReset.addEventListener('click', function(){
            root.style.fontSize = '';
            root.removeAttribute('data-pa-theme');
            root.removeAttribute('data-pa-contrast');
            try{ localStorage.removeItem('pa_starter_zoom'); localStorage.removeItem('pa_starter_theme'); localStorage.removeItem('pa_starter_contrast'); }catch(e){}
            syncState();
        });
    }
    syncState();
})();
</script>
    <?php
}, 95);
