<?php
/**
 * Plugin Name: PA SINOVA Starter - UI Hijau Gelap–Gold SK 2722/2026, Aksesibilitas & Performa
 * Description: Menyatukan palet Hijau Gelap (#056e4f) & Royal Gold (#C9A227), font Inter terpadu, visibilitas capture-safe SINOVA, Dark Mode, serta widget aksesibilitas WCAG 2.2 AA tanpa dependensi berbayar.
 * Version: 1.0.0-starter
 */

defined('ABSPATH') || exit;

// 1. Satukan Webfont menjadi 'Inter' tunggal + Preconnect untuk memangkas muatan font berlebihan
add_filter('astra_google_fonts', '__return_empty_array', 999);
add_filter('elementor/frontend/print_google_fonts', '__return_false', 999);

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

// 2. Tema SK 2722/2026, Capture-Safe Visibility, Dark Mode, High Contrast & Toolbar Disabilitas Ringan
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
/* Harmonisasi menu navigasi tema standar / Astra ke Hijau Gelap & Royal Gold */
.main-header-bar, .ast-primary-header-bar {
    border-bottom: 3px solid var(--pa-gold);
}
.main-header-menu .sub-menu a:hover,
.ast-nav-menu .sub-menu a:hover {
    background: linear-gradient(135deg, var(--pa-green-dark) 0%, var(--pa-green) 100%) !important;
    color: var(--pa-gold-light) !important;
}
/* Dark Mode */
html[data-pa-theme="dark"] body,
html[data-pa-theme="dark"] .site,
html[data-pa-theme="dark"] .site-content,
html[data-pa-theme="dark"] article.page,
html[data-pa-theme="dark"] article.post {
    background-color: #0f1720 !important;
    color: #e7eef2 !important;
}
html[data-pa-theme="dark"] a {
    color: #E5C158;
}
/* Kontras Tinggi WCAG AAA */
html[data-pa-contrast="high"] body,
html[data-pa-contrast="high"] .site {
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

add_action('wp_footer', static function () {
    if (is_admin()) {
        return;
    }
    ?>
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
