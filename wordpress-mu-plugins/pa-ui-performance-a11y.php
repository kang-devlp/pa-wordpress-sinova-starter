<?php
/**
 * Plugin Name: PA SINOVA Starter - UI Hijau Gelap–Gold SK 2722/2026, Navbar Shell, Aksesibilitas & Performa
 * Description: Menyatukan palet Hijau Gelap (#056e4f) & Royal Gold (#C9A227), Topbar + Menu Dropdown Hierarkis otomatis pada tema blok WP standar, CSS/JS Beranda anti-strip KSES, Dark Mode, Capture-Safe SINOVA, serta dock aksesibilitas WCAG 2.2 AA.
 * Version: 1.1.0-starter
 */

defined('ABSPATH') || exit;

// 1. Satukan Webfont menjadi 'Inter' tunggal + Preconnect untuk memangkas muatan font berlebihan
add_filter('astra_google_fonts', '__return_empty_array', 999);
add_filter('elementor/frontend/print_google_fonts', '__return_false', 999);

add_filter('body_class', static function ($classes) {
    if (!is_admin() && function_exists('wp_is_block_theme') && wp_is_block_theme()) {
        $classes[] = 'pa-sinova-block-shell';
    }
    return $classes;
});

add_action('wp_head', static function () {
    if (is_admin()) {
        return;
    }
    echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    echo '<meta name="color-scheme" content="light dark">' . "\n";
    echo '<script id="pa-a11y-boot">try{var t=localStorage.getItem("pa_starter_theme"),c=localStorage.getItem("pa_starter_contrast"),z=localStorage.getItem("pa_starter_zoom");if(t==="dark")document.documentElement.setAttribute("data-pa-theme","dark");if(c==="high")document.documentElement.setAttribute("data-pa-contrast","high");if(z)document.documentElement.style.fontSize=z;}catch(e){}</script>' . "\n";
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

// 2. Tema SK 2722/2026, Shell Header/Navbar & Footer, CSS Beranda, Capture-Safe Visibility, Dark Mode, High Contrast
add_action('wp_head', static function () {
    if (is_admin()) {
        return;
    }
    ?>
<style id="pa-sinova-starter-ui-css">
:root {
    --pa-green: #056e4f;
    --pa-green-dark: #043927;
    --pa-gold: #C9A227;
    --pa-gold-light: #E5C158;
    --e-global-typography-primary-font-family: 'Inter', system-ui, -apple-system, sans-serif;
    --e-global-typography-secondary-font-family: 'Inter', system-ui, -apple-system, sans-serif;
}
body, button, input, select, textarea {
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}
/* Capture-Safe: cegah section transparan/blank saat di-capture otomatis oleh bot SINOVA */
.pa-scroll-reveal, .elementor-invisible {
    opacity: 1 !important;
    visibility: visible !important;
    animation: none !important;
}
/* Sembunyikan header daftar halaman bawaan TwentyTwentyFive & lebarkan area konten */
body.pa-sinova-block-shell .wp-site-blocks > header.wp-block-template-part,
body.pa-sinova-block-shell .wp-site-blocks > footer.wp-block-template-part,
body.pa-sinova-block-shell header:has(.wp-block-page-list),
body.home.pa-sinova-block-shell .wp-block-post-title {
    display: none !important;
}
body.pa-sinova-block-shell .wp-site-blocks .is-layout-constrained > :where(:not(.alignfull)) {
    max-width: 1180px !important;
    margin-left: auto !important;
    margin-right: auto !important;
}
body.pa-sinova-block-shell main.wp-block-group {
    padding-top: 12px !important;
    padding-bottom: 36px !important;
}
body.pa-sinova-block-shell .wp-block-post-title {
    color: var(--pa-green-dark);
    font-weight: 800;
    font-size: clamp(1.5rem, 2.5vw, 2.1rem);
    border-bottom: 3px solid var(--pa-gold);
    padding-bottom: 10px;
    margin-bottom: 20px;
}

/* Topbar & Header Institusional SINOVA */
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
    gap: 10px 18px;
}
.pa-shell-topbar-links {
    display: flex;
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
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.22);
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
    white-space: nowrap;
}

/* Navbar Dropdown Hierarkis */
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
.pa-shell-menu > li {
    position: relative;
}
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
    transition: background-color 0.15s ease, color 0.15s ease;
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
.pa-shell-menu > li:focus-within > .pa-shell-submenu {
    display: block;
}
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

/* Harmonisasi menu navigasi tema Astra / klasik */
.main-header-bar, .ast-primary-header-bar {
    border-bottom: 3px solid var(--pa-gold);
}
.main-header-menu .sub-menu a:hover,
.ast-nav-menu .sub-menu a:hover {
    background: linear-gradient(135deg, var(--pa-green-dark) 0%, var(--pa-green) 100%) !important;
    color: var(--pa-gold-light) !important;
}

/* Template Beranda Showcase CSS (Aman dari strip KSES WordPress) */
.pa-starter-showcase {
    max-width: 1180px;
    margin: 8px auto 28px;
    font-family: 'Inter', system-ui, sans-serif;
}
.pa-hero-banner {
    padding: clamp(26px, 4vw, 44px) clamp(20px, 4vw, 40px);
    border-radius: 18px;
    background: radial-gradient(circle at top right, rgba(201, 162, 39, 0.22), transparent 52%),
                linear-gradient(135deg, #022c22 0%, #044e3a 52%, #056e4f 100%);
    border: 1.5px solid #C9A227;
    color: #fff;
    text-align: center;
    box-shadow: 0 14px 34px rgba(4, 57, 39, 0.18);
    margin-bottom: 28px;
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
    font-size: clamp(1.5rem, 3.2vw, 2.35rem);
    font-weight: 800;
    line-height: 1.2;
}
.pa-hero-lead {
    margin: 0 auto 20px;
    max-width: 720px;
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
.pa-btn-outline:hover {
    background: rgba(255, 255, 255, 0.2);
}

.pa-section-block {
    margin-bottom: 30px;
    text-align: center;
}
.pa-starter-heading {
    margin: 0 0 4px;
    color: #056e4f;
    font-weight: 800;
    font-size: 1.4rem;
}
.pa-starter-sub {
    margin: 0 0 18px;
    color: #4b5563;
    font-weight: 600;
    font-size: 0.93rem;
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
    gap: 6px;
    padding: 18px 16px;
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
.pa-svc-badge {
    align-self: flex-start;
    padding: 2px 8px;
    border-radius: 999px;
    background: #ecfdf5;
    border: 1px solid #056e4f;
    color: #043927;
    font-size: 11px;
    font-weight: 800;
}
.pa-service-card strong {
    font-size: 15px;
    color: #043927;
}
.pa-service-card span:last-child {
    font-size: 12.5px;
    line-height: 1.45;
    color: #4b5563;
}

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
    padding: 20px 12px;
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
.pa-zi-area {
    font-size: 15px;
    font-weight: 800;
    color: #E5C158;
    margin-bottom: 6px;
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
.pa-starter-survey .survey-cards {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
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
    transition: background-color 0.15s ease, border-color 0.15s ease;
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
.pa-shortcut-item:hover {
    border-color: #056e4f;
    transform: translateY(-2px);
}
.pa-shortcut-item span {
    font-size: 11px;
    font-weight: 800;
    color: #8A6808;
    text-transform: uppercase;
}
.pa-shortcut-item strong {
    font-size: 13.5px;
    color: #043927;
}

/* Footer Institusional */
.pa-shell-footer {
    background: #022c22;
    color: #d1fae5;
    border-top: 4px solid #C9A227;
    padding: 22px 16px 26px;
    font-size: 13px;
}
.pa-shell-footer-meta {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    max-width: 1180px;
    margin: 0 auto;
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

/* Dark Mode */
html[data-pa-theme="dark"] body,
html[data-pa-theme="dark"] .site,
html[data-pa-theme="dark"] .wp-site-blocks,
html[data-pa-theme="dark"] .site-content,
html[data-pa-theme="dark"] article.page,
html[data-pa-theme="dark"] article.post {
    background-color: #0f1720 !important;
    color: #e7eef2 !important;
}
html[data-pa-theme="dark"] .pa-shell-navwrap,
html[data-pa-theme="dark"] .pa-shell-submenu {
    background: #13221a;
    border-color: #2c4637;
}
html[data-pa-theme="dark"] .pa-shell-menu > li > a,
html[data-pa-theme="dark"] .pa-shell-submenu a {
    color: #e6f4ee;
}
html[data-pa-theme="dark"] .pa-service-card,
html[data-pa-theme="dark"] .pa-hub-shortcuts,
html[data-pa-theme="dark"] .pa-shortcut-item {
    background: #182a20;
    border-color: #355241;
    color: #e6f4ee !important;
}
html[data-pa-theme="dark"] .pa-service-card strong,
html[data-pa-theme="dark"] .pa-hub-shortcuts-head h3,
html[data-pa-theme="dark"] .pa-shortcut-item strong,
html[data-pa-theme="dark"] .pa-starter-heading {
    color: #E5C158;
}
html[data-pa-theme="dark"] .pa-service-card span:last-child,
html[data-pa-theme="dark"] .pa-starter-sub,
html[data-pa-theme="dark"] .pa-hub-shortcuts-head p {
    color: #cbd5e1;
}
html[data-pa-theme="dark"] a {
    color: #E5C158;
}
/* Kontras Tinggi WCAG AAA */
html[data-pa-contrast="high"] body,
html[data-pa-contrast="high"] .site,
html[data-pa-contrast="high"] .wp-site-blocks {
    background: #ffffff !important;
    color: #000000 !important;
}
html[data-pa-contrast="high"] a {
    color: #003d29 !important;
    text-decoration: underline !important;
}
/* Floating Aksesibilitas */
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
#pa-a11y-dock button[aria-pressed="true"] {
    background: #C9A227;
    color: #043927;
}
</style>
    <?php
}, 90);

// 3. Render Header & Menu Hierarkis Otomatis saat Tema Blok Standar WordPress (seperti Twenty Twenty-Five) Aktif
add_action('wp_body_open', static function () {
    if (is_admin() || !function_exists('wp_is_block_theme') || !wp_is_block_theme()) {
        return;
    }

    $satker  = function_exists('pa_sinova_satker_name') ? pa_sinova_satker_name() : (get_bloginfo('name') ?: 'Pengadilan Agama');
    $tagline = get_bloginfo('description') ?: 'Portal Resmi Keterbukaan Informasi Publik (SK Dirjen Badilag 2722/2026)';
    $cfg     = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();

    // Ambil menu hierarkis 'Menu Utama SINOVA'
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
<div class="pa-shell-topbar">
    <div class="pa-shell-inner">
        <span>Standar Keterbukaan Informasi Publik · SK Dirjen Badilag 2722/2026</span>
        <div class="pa-shell-topbar-links">
            <a href="<?php echo esc_url($cfg['survey_url'] ?? 'https://survei.badilag.net/'); ?>" target="_blank" rel="noopener noreferrer">Survei Badilag</a>
            <a href="<?php echo esc_url(wp_login_url()); ?>">Login Admin</a>
        </div>
    </div>
</div>
<header class="pa-shell-masthead" role="banner">
    <div class="pa-shell-inner">
        <a class="pa-shell-brand" href="<?php echo esc_url(home_url('/')); ?>">
            <span class="pa-shell-emblem">PA</span>
            <div>
                <p class="pa-shell-brand-title"><?php echo esc_html($satker); ?></p>
                <p class="pa-shell-brand-sub"><?php echo esc_html($tagline); ?></p>
            </div>
        </a>
        <span class="pa-shell-badge">SINOVA Hub · Rumah s.d. 2027</span>
    </div>
</header>
<?php if (!empty($topItems)) : ?>
<nav class="pa-shell-navwrap" aria-label="Menu Utama SINOVA">
    <div class="pa-shell-nav-row">
        <button type="button" class="pa-shell-menu-btn" id="pa-shell-menu-toggle" aria-expanded="false">☰ Menu Navigasi SINOVA</button>
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
<?php endif; ?>
    <?php
}, 5);

// 4. Footer Institusional, Toolbar Aksesibilitas & Interaksi Slider Survei Beranda
add_action('wp_footer', static function () {
    if (is_admin()) {
        return;
    }
    if (function_exists('wp_is_block_theme') && wp_is_block_theme()) :
        $satker = function_exists('pa_sinova_satker_name') ? pa_sinova_satker_name() : (get_bloginfo('name') ?: 'Pengadilan Agama');
    ?>
<footer class="pa-shell-footer">
    <div class="pa-shell-footer-meta">
        <div>
            <strong><?php echo esc_html($satker); ?></strong> — Starter Kit WordPress SINOVA SK Dirjen Badilag 2722/2026
        </div>
        <span class="pa-shell-render-pill">Rendering : <?php echo esc_html(timer_stop(0, 3)); ?> detik.</span>
    </div>
</footer>
    <?php endif; ?>
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
    var btnContrast = document.getElementById('pa-btn-contrast');
    var btnUp = document.getElementById('pa-btn-font-up');
    var btnReset = document.getElementById('pa-btn-font-reset');
    var menuToggle = document.getElementById('pa-shell-menu-toggle');
    var menuList = document.getElementById('pa-shell-menu-list');

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
        if(btnContrast){ btnContrast.setAttribute('aria-pressed', hi ? 'true' : 'false'); }
    }
    if(btnDark){
        btnDark.addEventListener('click', function(){
            var next = root.getAttribute('data-pa-theme') === 'dark' ? '' : 'dark';
            if(next) root.setAttribute('data-pa-theme', next); else root.removeAttribute('data-pa-theme');
            try{ localStorage.setItem('pa_starter_theme', next); }catch(e){}
            syncState();
        });
    }
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
