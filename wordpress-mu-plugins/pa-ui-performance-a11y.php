<?php
/**
 * Plugin Name: PA SINOVA Starter - UI Portal Resmi PA, Pencarian, Statistik Pengunjung, Sidebar Halaman Dalam & Aksesibilitas Lengkap
 * Description: Menyatukan palet Hijau Gelap (#056e4f) & Royal Gold (#C9A227) untuk Astra Free maupun tema blok WordPress, bebas istilah internal di tampilan publik, kotak pencarian header, statistik pengunjung privasi ter-hash, sidebar & breadcrumb halaman dalam, serta dock disabilitas dengan Pembaca Suara Bahasa Indonesia.
 * Version: 2.5.0-starter
 */

defined('ABSPATH') || exit;

// 1. Satukan Webfont menjadi 'Inter' tunggal + Hapus Bloat Emoji/Dashicons/Resource Hints + Optimasi CWV
// (Pada Mode Adopsi situs lama, font & Dashicons tema/Elementor eksisting tetap dipertahankan utuh)
add_filter('astra_google_fonts', static function ($fonts) {
    return (function_exists('pa_sinova_is_adoption_mode') && pa_sinova_is_adoption_mode()) ? $fonts : array();
}, 999);
add_filter('elementor/frontend/print_google_fonts', static function ($print) {
    return (function_exists('pa_sinova_is_adoption_mode') && pa_sinova_is_adoption_mode()) ? $print : false;
}, 999);

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
    if (!is_admin() && !is_user_logged_in() && (!function_exists('pa_sinova_is_adoption_mode') || !pa_sinova_is_adoption_mode())) {
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
    $cfg         = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $satker      = $cfg['satker_name'] ?? 'Pengadilan Agama';
    $leader      = $cfg['leader_name'] ?? 'Ketua Pengadilan Agama';
    $leaderTitle = trim((string) ($cfg['leader_title'] ?? '')) ?: 'Ketua Pengadilan Agama';
    $leaderPhoto = trim((string) ($cfg['leader_photo_url'] ?? ''));
    if ($leaderPhoto === '' && function_exists('pa_sinova_asset_url')) {
        $leaderPhoto = pa_sinova_asset_url('dummy-pimpinan.svg');
    }
    $leaderMotto = trim((string) ($cfg['leader_motto'] ?? ''));
    $welcome     = $cfg['leader_welcome'] ?? 'Selamat datang di portal keterbukaan informasi resmi satuan kerja Pengadilan Agama.';
    $phone       = $cfg['phone'] ?? '0811-0000-0000';
    $email       = $cfg['email'] ?? 'info@pengadilan.go.id';

    ob_start();
    ?>
    <section class="pa-welcome-grid" aria-label="Sambutan Pimpinan dan Maklumat Pelayanan">
        <div class="pa-welcome-card">
            <span class="pa-wc-kicker">Sambutan Pimpinan</span>
            <div class="pa-wc-profile">
                <?php if ($leaderPhoto !== '') : ?>
                    <img class="pa-wc-photo" src="<?php echo esc_url($leaderPhoto); ?>" alt="<?php echo esc_attr($leader); ?>" loading="lazy" decoding="async">
                <?php endif; ?>
                <div class="pa-wc-headtext">
                    <h3><?php echo esc_html($leader); ?></h3>
                    <span class="pa-wc-role"><?php echo esc_html($leaderTitle . ' · ' . $satker); ?></span>
                    <?php if ($leaderMotto !== '') : ?>
                        <p class="pa-wc-motto">"<?php echo esc_html($leaderMotto); ?>"</p>
                    <?php endif; ?>
                </div>
            </div>
            <p class="pa-wc-body"><?php echo esc_html($welcome); ?></p>
            <div class="pa-wc-links">
                <a href="./visi-dan-misi/">Visi &amp; Misi</a>
                <a href="./struktur-organisasi/">Struktur Organisasi</a>
                <a href="./profil-pegawai/">Profil Pejabat &amp; ASN</a>
            </div>
        </div>
        <div class="pa-maklumat-card">
            <?php
            $tzInfo = function_exists('pa_sinova_timezone_info') ? pa_sinova_timezone_info() : array('zone' => 'WIB');
            $tzZone = $tzInfo['zone'] ?? 'WIB';
            ?>
            <span class="pa-mk-kicker">Maklumat Pelayanan &amp; PTSP</span>
            <h3>Pelayanan Prima Berintegritas</h3>
            <p class="pa-mk-quote">"Dengan ini kami menyatakan sanggup menyelenggarakan pelayanan sesuai standar pelayanan yang telah ditetapkan dan apabila tidak menepati janji siap menerima sanksi sesuai peraturan perundang-undangan."</p>
            <ul class="pa-mk-hours">
                <li><strong>Senin – Kamis:</strong> 08.00 – 16.30 <?php echo esc_html($tzZone); ?></li>
                <li><strong>Jumat:</strong> 08.00 – 17.00 <?php echo esc_html($tzZone); ?></li>
                <li><strong>Layanan PTSP:</strong> <?php echo esc_html($phone . ' · ' . $email); ?></li>
            </ul>
            <a class="pa-mk-btn" href="./standar-maklumat-pelayanan/">Baca Standar &amp; Maklumat Pelayanan →</a>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
});

// 3b. Shortcode [pa_sinova_aco_card]: Disembunyikan dari beranda bawaan karena tautan CCTV ACO sudah tersedia di Topbar & Footer
add_shortcode('pa_sinova_aco_card', static function ($atts = array()) {
    $cfgAtts = shortcode_atts(array('show' => '0'), $atts, 'pa_sinova_aco_card');
    if ($cfgAtts['show'] !== '1') {
        return '';
    }
    $cfg    = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $acoUrl = trim((string) ($cfg['aco_url'] ?? 'https://cctv.badilag.net/'));
    if ($acoUrl === '') {
        return '';
    }
    ob_start();
    ?>
    <aside class="pa-aco-banner" aria-label="Access CCTV Online (ACO) Ditjen Badilag">
        <div class="pa-aco-main">
            <span class="pa-aco-dot" aria-hidden="true"></span>
            <svg class="pa-aco-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
            <span class="pa-aco-text"><strong>Access CCTV Online (ACO) Badilag</strong> <span class="pa-aco-sep" aria-hidden="true">&middot;</span> <span class="pa-aco-desc">Pemantauan terbuka ruang pelayanan publik (PTSP)</span></span>
        </div>
        <a class="pa-aco-btn" href="<?php echo esc_url($acoUrl); ?>" target="_blank" rel="noopener noreferrer">Pantau CCTV ACO &#8599;</a>
    </aside>
    <?php
    return (string) ob_get_clean();
});

// 3c. Dashboard Jadwal Sidang Modern ([pa_sinova_schedule_dashboard] & alias [pa_sinova_sipp_slide])
function pa_sinova_render_schedule_dashboard(array $atts = array()): string {
    $cfg     = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $cfgAtts = shortcode_atts(array(
        'url'         => (string) ($cfg['sipp_slide_url'] ?? ''),
        'title'       => 'Jadwal Persidangan & Monitor SIPP',
        'height'      => '540',
        'default_tab' => (string) ($cfg['home_schedule_default_tab'] ?? 'auto'),
        'position'    => (string) ($cfg['home_schedule_position'] ?? 'after_hero'),
    ), $atts, 'pa_sinova_schedule_dashboard');

    $rawUrl   = trim((string) $cfgAtts['url']);
    $embedUrl = '';
    if ($rawUrl !== '') {
        if (function_exists('pa_sinova_sanitize_https_embed_url')) {
            $chk = pa_sinova_sanitize_https_embed_url($rawUrl);
            if ($chk['valid'] && $chk['url'] !== '') {
                $embedUrl = $chk['url'];
            }
        } else {
            $tmp = esc_url_raw($rawUrl, array('https'));
            if ($tmp !== '' && stripos($tmp, 'https://') === 0) {
                $embedUrl = $tmp;
            }
        }
    }

    $hasSipp   = ($embedUrl !== '');
    $defTabReq = sanitize_key((string) $cfgAtts['default_tab']);
    if ($defTabReq === 'sipp' && $hasSipp) {
        $activeTab = 'sipp';
    } elseif ($defTabReq === 'agenda') {
        $activeTab = 'agenda';
    } else {
        $activeTab = $hasSipp ? 'sipp' : 'agenda';
    }

    if (function_exists('pa_portal_maybe_schedule_sipp_web_sync')) {
        pa_portal_maybe_schedule_sipp_web_sync();
    }
    $sippRows = function_exists('pa_portal_fetch_sipp_hearings_today') ? pa_portal_fetch_sipp_hearings_today() : array();
    $isLiveDb = !empty($sippRows);
    $hearings = $isLiveDb ? $sippRows : (function_exists('pa_portal_get_records') ? pa_portal_get_records('jadwal', 6) : array());
    $totalH   = count($hearings);

    $satker      = function_exists('pa_sinova_satker_name') ? pa_sinova_satker_name() : (get_bloginfo('name') ?: 'Pengadilan Agama');
    $sippPortal  = trim((string) ($cfg['sipp_url'] ?? ''));
    $tzInfoSched = function_exists('pa_sinova_timezone_info') ? pa_sinova_timezone_info() : array('zone' => 'WIB');
    $tzZoneSched = $tzInfoSched['zone'] ?? 'WIB';
    $hearingHrs  = trim((string) ($cfg['hearing_hours'] ?? ('Senin–Kamis 09.00 ' . $tzZoneSched . ' s.d. selesai')));
    $agendaUrl   = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('agenda-jadwal-persidangan') : home_url('/agenda-jadwal-persidangan/');
    $ghaibUrl    = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('panggilan-ghaib') : home_url('/panggilan-ghaib/');
    $heading     = trim((string) $cfgAtts['title']) ?: 'Jadwal Persidangan & Monitor SIPP';
    $height      = max(360, min(900, (int) $cfgAtts['height']));
    $todayLabel  = wp_date('l, j F Y');
    $uid         = 'pa-sched-' . substr(md5($embedUrl . $heading . (string) $totalH), 0, 6);
    $isBelowFold = in_array((string) $cfgAtts['position'], array('after_services', 'after_welcome'), true);

    ob_start();
    ?>
    <section class="pa-section-block pa-sched-section<?php echo $isBelowFold ? ' pa-sched-below-fold' : ''; ?>" aria-label="Jadwal Persidangan Hari Ini">
        <div class="pa-sched-hub" data-pa-sched-hub>
            <div class="pa-sched-head">
                <div class="pa-sched-head-left">
                    <div class="pa-sched-badges">
                        <span class="pa-sipp-live-badge"><span class="pa-sipp-live-dot" aria-hidden="true"></span><?php echo $hasSipp ? 'LIVE SIPP' : 'AGENDA SIDANG'; ?></span>
                        <span class="pa-sched-date-pill"><?php echo esc_html($todayLabel); ?></span>
                    </div>
                    <h2 class="pa-sched-title"><?php echo esc_html($heading); ?></h2>
                </div>
                <div class="pa-sched-head-right">
                    <?php if ($hasSipp) : ?>
                        <div class="pa-sched-tabs" role="tablist" aria-label="Mode Tampilan Jadwal Sidang">
                            <button type="button" role="tab" id="<?php echo esc_attr($uid . '-tab-sipp'); ?>" aria-controls="<?php echo esc_attr($uid . '-panel-sipp'); ?>" aria-selected="<?php echo $activeTab === 'sipp' ? 'true' : 'false'; ?>" tabindex="<?php echo $activeTab === 'sipp' ? '0' : '-1'; ?>" class="pa-sched-tab<?php echo $activeTab === 'sipp' ? ' is-active' : ''; ?>" data-sched-tab="sipp">Layar Live SIPP</button>
                            <button type="button" role="tab" id="<?php echo esc_attr($uid . '-tab-agenda'); ?>" aria-controls="<?php echo esc_attr($uid . '-panel-agenda'); ?>" aria-selected="<?php echo $activeTab === 'agenda' ? 'true' : 'false'; ?>" tabindex="<?php echo $activeTab === 'agenda' ? '0' : '-1'; ?>" class="pa-sched-tab<?php echo $activeTab === 'agenda' ? ' is-active' : ''; ?>" data-sched-tab="agenda">Agenda Terstruktur (<?php echo (int) $totalH; ?>)</button>
                        </div>
                        <a class="pa-sipp-btn-open" href="<?php echo esc_url($embedUrl); ?>" target="_blank" rel="noopener noreferrer">Layar Penuh &#8599;</a>
                    <?php else : ?>
                        <a class="pa-sipp-btn-sec" href="<?php echo esc_url($ghaibUrl); ?>">Panggilan Ghaib</a>
                        <a class="pa-sipp-btn-open" href="<?php echo esc_url($agendaUrl); ?>">Lihat Semua Jadwal →</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($hasSipp) : ?>
            <div class="pa-sched-panel" role="tabpanel" id="<?php echo esc_attr($uid . '-panel-sipp'); ?>" aria-labelledby="<?php echo esc_attr($uid . '-tab-sipp'); ?>" data-sched-panel="sipp" <?php echo $activeTab !== 'sipp' ? 'hidden' : ''; ?>>
                <div class="pa-sipp-frame-wrap">
                    <iframe
                        class="pa-sipp-frame"
                        src="<?php echo esc_url($embedUrl); ?>"
                        title="<?php echo esc_attr($heading . ' — ' . $satker); ?>"
                        loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allow="fullscreen"
                        allowfullscreen
                        style="height:clamp(420px, 64vh, <?php echo (int) $height; ?>px)"></iframe>
                </div>
            </div>
            <?php endif; ?>

            <div class="pa-sched-panel pa-sched-agenda-panel" role="tabpanel" id="<?php echo esc_attr($uid . '-panel-agenda'); ?>" aria-labelledby="<?php echo esc_attr($hasSipp ? ($uid . '-tab-agenda') : $uid); ?>" data-sched-panel="agenda" <?php echo ($hasSipp && $activeTab !== 'agenda') ? 'hidden' : ''; ?>>
                <div class="pa-sched-kpi-strip">
                    <div class="pa-sched-kpi">
                        <span>Perkara Terjadwal</span>
                        <strong><?php echo (int) $totalH; ?> Perkara</strong>
                    </div>
                    <div class="pa-sched-kpi">
                        <span>Jam Persidangan</span>
                        <strong><?php echo esc_html($hearingHrs); ?></strong>
                    </div>
                    <div class="pa-sched-kpi">
                        <span>Sumber Informasi</span>
                        <strong><?php echo $isLiveDb ? 'Tersambung SIPP' : 'Data Operasional Portal'; ?></strong>
                    </div>
                </div>

                <?php if (!empty($hearings)) : ?>
                    <div class="pa-sched-cards">
                        <?php foreach (array_slice($hearings, 0, 6) as $hr) :
                            $meta    = isset($hr['meta_json']) ? json_decode((string) $hr['meta_json'], true) : array();
                            $jam     = (string) ($hr['jam'] ?? (is_array($meta) ? ($meta['jam'] ?? ('09.00 ' . $tzZoneSched)) : ('09.00 ' . $tzZoneSched)));
                            $ruang   = (string) ($hr['category'] ?? 'Ruang Sidang');
                            $nomor   = (string) ($hr['title'] ?? '-');
                            $agendaT = (string) ($hr['subtitle'] ?? 'Persidangan');
                        ?>
                            <article class="pa-sched-item">
                                <div class="pa-sched-item-top">
                                    <span class="pa-sched-time"><?php echo esc_html($jam); ?></span>
                                    <span class="pa-sched-room"><?php echo esc_html($ruang); ?></span>
                                </div>
                                <strong class="pa-sched-case"><?php echo esc_html($nomor); ?></strong>
                                <span class="pa-sched-agenda"><?php echo esc_html($agendaT); ?></span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="pa-sched-empty">Belum ada jadwal persidangan terjadwal pada hari ini. Cek agenda lengkap melalui halaman Jadwal Sidang atau portal SIPP.</p>
                <?php endif; ?>
            </div>

            <div class="pa-sipp-card-foot">
                <span>Sumber resmi: <strong><?php echo esc_html($satker); ?></strong> · Persidangan terbuka untuk umum sesuai ketentuan peradilan.</span>
                <div class="pa-sched-foot-links">
                    <a href="<?php echo esc_url($agendaUrl); ?>">Halaman Jadwal Sidang</a>
                    <span aria-hidden="true">·</span>
                    <a href="<?php echo esc_url($ghaibUrl); ?>">Panggilan Ghaib</a>
                    <?php if ($sippPortal !== '' && stripos($sippPortal, 'pa-contoh') === false) : ?>
                        <span aria-hidden="true">·</span>
                        <a href="<?php echo esc_url($sippPortal); ?>" target="_blank" rel="noopener noreferrer">Portal SIPP &#8599;</a>
                    <?php elseif ($hasSipp) : ?>
                        <span aria-hidden="true">·</span>
                        <a href="<?php echo esc_url($embedUrl); ?>" target="_blank" rel="noopener noreferrer">Buka Tab Baru &#8599;</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('pa_sinova_schedule_dashboard', 'pa_sinova_render_schedule_dashboard');

function pa_sinova_render_sipp_slide_section(array $atts = array()): string {
    return pa_sinova_render_schedule_dashboard($atts);
}
add_shortcode('pa_sinova_sipp_slide', 'pa_sinova_render_sipp_slide_section');

// 3d. Shortcode [pa_sinova_beranda]: Beranda Dinamis Grid 12 Kolom (Drag & Resize di SINOVA Hub)
function pa_sinova_render_home_hero_or_slider(array $cfg, array $blk): string {
    $slides    = function_exists('pa_portal_get_records') ? pa_portal_get_records('slider', 5) : array();
    $kicker    = trim((string) ($cfg['home_hero_kicker'] ?? 'Zona Integritas WBK / WBBM · Keterbukaan Informasi Publik'));
    $heroTitle = trim((string) ($cfg['home_hero_title'] ?? 'Portal Keterbukaan Informasi & Layanan Peradilan'));
    $heroLead  = trim((string) ($cfg['home_hero_lead'] ?? ''));
    $u = static function (string $slug): string {
        return function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url($slug) : home_url('/' . $slug . '/');
    };

    if (!empty($slides)) {
        ob_start();
        ?>
        <section class="pa-hero-slider" data-pa-hero-slider aria-label="Slider Utama Portal">
            <div class="pa-hs-track">
                <?php foreach ($slides as $sIdx => $sl) :
                    $m      = json_decode((string) ($sl['meta_json'] ?? ''), true);
                    $img    = is_array($m) ? esc_url_raw((string) ($m['image_url'] ?? ''), array('http', 'https')) : '';
                    $btnUrl = is_array($m) ? esc_url_raw((string) ($m['btn_url'] ?? ''), array('http', 'https')) : '';
                    $btnLbl = is_array($m) ? trim((string) ($m['btn_label'] ?? 'Selengkapnya')) : 'Selengkapnya';
                    $sKick  = trim((string) ($sl['category'] ?: $kicker));
                    $tagH   = ($sIdx === 0) ? 'h1' : 'h2';
                ?>
                    <article class="pa-hs-slide<?php echo $sIdx === 0 ? ' is-active' : ''; ?>" data-hs-slide="<?php echo (int) $sIdx; ?>" <?php echo $sIdx > 0 ? 'hidden' : ''; ?>>
                        <?php if ($img !== '') : ?>
                            <img class="pa-hs-bg" src="<?php echo esc_url($img); ?>" alt="" <?php echo $sIdx === 0 ? 'fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async">
                        <?php endif; ?>
                        <div class="pa-hs-overlay">
                            <?php if ($sKick !== '') : ?><span class="pa-hero-kicker"><?php echo esc_html($sKick); ?></span><?php endif; ?>
                            <<?php echo $tagH; ?> class="pa-hero-title"><?php echo esc_html((string) $sl['title']); ?></<?php echo $tagH; ?>>
                            <?php if (!empty($sl['subtitle'])) : ?><p class="pa-hero-lead"><?php echo esc_html((string) $sl['subtitle']); ?></p><?php endif; ?>
                            <div class="pa-hero-actions">
                                <?php if ($btnUrl !== '') : ?>
                                    <a class="pa-btn-gold" href="<?php echo esc_url($btnUrl); ?>"><?php echo esc_html($btnLbl); ?></a>
                                <?php endif; ?>
                                <a class="pa-btn-outline" href="<?php echo esc_url($u('agenda-jadwal-persidangan')); ?>">Jadwal Sidang &amp; SIPP</a>
                                <a class="pa-btn-outline" href="<?php echo esc_url($u('e-court')); ?>">e-Court</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (count($slides) > 1) : ?>
                <div class="pa-hs-controls">
                    <button type="button" class="pa-hs-nav" data-hs-dir="-1" aria-label="Slide sebelumnya">‹</button>
                    <div class="pa-hs-dots" role="group" aria-label="Pilih slide">
                        <?php foreach ($slides as $sIdx => $_s) : ?>
                            <button type="button" class="pa-hs-dot<?php echo $sIdx === 0 ? ' is-active' : ''; ?>" data-hs-to="<?php echo (int) $sIdx; ?>" aria-label="Slide <?php echo (int) ($sIdx + 1); ?>" aria-pressed="<?php echo $sIdx === 0 ? 'true' : 'false'; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="pa-hs-nav" data-hs-dir="1" aria-label="Slide berikutnya">›</button>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    $heroLayout  = sanitize_key((string) ($cfg['hero_layout'] ?? 'split'));
    if (!in_array($heroLayout, array('split', 'classic'), true)) {
        $heroLayout = 'split';
    }
    $heroPattern = sanitize_key((string) ($cfg['hero_pattern'] ?? 'islamic'));
    if (!in_array($heroPattern, array('none', 'islamic', 'batik', 'songket'), true)) {
        $heroPattern = 'islamic';
    }
    $heroPhoto = trim((string) ($cfg['hero_photo_url'] ?? ''));
    if ($heroPhoto === '' && function_exists('pa_sinova_asset_url')) {
        $heroPhoto = pa_sinova_asset_url('dummy-gedung-pa.svg');
    }
    $heroCap = trim((string) ($cfg['hero_photo_caption'] ?? ''));
    $satker  = trim((string) ($cfg['satker_name'] ?? '')) ?: (get_bloginfo('name') ?: 'Pengadilan Agama');

    ob_start();
    ?>
    <section class="pa-hero-banner<?php echo $heroLayout === 'split' ? ' is-split' : ''; ?>" data-pa-pattern="<?php echo esc_attr($heroPattern); ?>" aria-label="Portal Resmi Pengadilan Agama">
        <?php if ($heroLayout === 'split') : ?>
            <div class="pa-hero-split">
                <div class="pa-hero-content">
                    <?php if ($kicker !== '') : ?><span class="pa-hero-kicker"><?php echo esc_html($kicker); ?></span><?php endif; ?>
                    <h1 class="pa-hero-title"><?php echo esc_html($heroTitle); ?></h1>
                    <?php if ($heroLead !== '') : ?><p class="pa-hero-lead"><?php echo esc_html($heroLead); ?></p><?php endif; ?>
                    <div class="pa-hero-actions">
                        <a class="pa-btn-gold" href="<?php echo esc_url($u('agenda-jadwal-persidangan')); ?>" data-pa-tip="Agenda sidang harian &amp; SIPP">Jadwal Sidang &amp; SIPP</a>
                        <a class="pa-btn-outline" href="<?php echo esc_url($u('e-court')); ?>" data-pa-tip="Perkara elektronik">Layanan e-Court</a>
                        <a class="pa-btn-outline" href="<?php echo esc_url($u('direktori-putusan')); ?>" data-pa-tip="Putusan resmi MA RI">Direktori Putusan</a>
                        <a class="pa-btn-outline" href="<?php echo esc_url($u('survei-pelayanan')); ?>" data-pa-tip="SKM, SPAK, SPKP">Survei Pelayanan</a>
                    </div>
                </div>
                <?php if ($heroPhoto !== '') : ?>
                    <figure class="pa-hero-split-media">
                        <div class="pa-hero-media-frame">
                            <img src="<?php echo esc_url($heroPhoto); ?>" alt="<?php echo esc_attr('Gedung ' . $satker); ?>" fetchpriority="high" decoding="async">
                            <span class="pa-hero-media-badge">WBK / WBBM · KIP</span>
                        </div>
                        <?php if ($heroCap !== '') : ?>
                            <figcaption><?php echo esc_html($heroCap); ?></figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="pa-hero-content">
                <?php if ($kicker !== '') : ?><span class="pa-hero-kicker"><?php echo esc_html($kicker); ?></span><?php endif; ?>
                <h1 class="pa-hero-title"><?php echo esc_html($heroTitle); ?></h1>
                <?php if ($heroLead !== '') : ?><p class="pa-hero-lead"><?php echo esc_html($heroLead); ?></p><?php endif; ?>
                <div class="pa-hero-actions">
                    <a class="pa-btn-gold" href="<?php echo esc_url($u('agenda-jadwal-persidangan')); ?>" data-pa-tip="Agenda sidang harian &amp; SIPP">Jadwal Sidang &amp; SIPP</a>
                    <a class="pa-btn-outline" href="<?php echo esc_url($u('e-court')); ?>" data-pa-tip="Perkara elektronik">Layanan e-Court</a>
                    <a class="pa-btn-outline" href="<?php echo esc_url($u('direktori-putusan')); ?>" data-pa-tip="Putusan resmi MA RI">Direktori Putusan</a>
                    <a class="pa-btn-outline" href="<?php echo esc_url($u('survei-pelayanan')); ?>" data-pa-tip="SKM, SPAK, SPKP">Survei Pelayanan</a>
                </div>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return (string) ob_get_clean();
}

function pa_sinova_render_home_block_item(array $blk, array $cfg): string {
    $type  = (string) ($blk['type'] ?? '');
    $title = trim((string) ($blk['title'] ?? ''));
    $w     = max(2, min(12, (int) ($blk['w'] ?? 12)));
    $y     = max(1, (int) ($blk['y'] ?? 1));
    $bCfg  = isset($blk['config']) && is_array($blk['config']) ? $blk['config'] : array();
    $u     = static function (string $slug): string {
        return function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url($slug) : home_url('/' . $slug . '/');
    };

    if ($type === 'hero_slider') {
        return pa_sinova_render_home_hero_or_slider($cfg, $blk);
    }

    if ($type === 'kpi_bar') {
        return function_exists('pa_portal_render_kpi_bar') ? pa_portal_render_kpi_bar($title) : '';
    }

    if ($type === 'sipp_tracker') {
        return function_exists('pa_portal_render_sipp_tracker') ? pa_portal_render_sipp_tracker($title ?: 'Penelusuran Perkara Cepat (SIPP)', false) : '';
    }

    if ($type === 'inovasi') {
        $cnt = !empty($bCfg['count']) ? (int) $bCfg['count'] : (!empty($bCfg['limit']) ? (int) $bCfg['limit'] : 4);
        return function_exists('pa_portal_render_inovasi_grid') ? pa_portal_render_inovasi_grid($title ?: 'Inovasi Layanan Unggulan', $cnt) : '';
    }

    if ($type === 'services') {
        ob_start();
        ?>
        <section class="pa-section-block pa-hg-flush" aria-label="<?php echo esc_attr($title ?: 'Layanan Utama'); ?>">
            <h2 class="pa-starter-heading"><?php echo esc_html(strtoupper($title ?: 'LAYANAN PERADILAN & INFORMASI PERKARA')); ?> <span class="pa-hint-i" aria-hidden="true">i</span></h2>
            <p class="pa-starter-sub">Akses langsung layanan elektronik, informasi berperkara, dan transparansi</p>
            <div class="pa-services-grid <?php echo $w <= 8 ? 'pa-services-grid-compact' : 'pa-services-grid-8'; ?>">
                <a class="pa-service-card" href="<?php echo esc_url($u('agenda-jadwal-persidangan')); ?>" data-pa-tip="Status perkara &amp; jadwal sidang harian">
                    <div class="pa-svc-top"><span class="pa-svc-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span><span class="pa-svc-badge">Persidangan</span></div>
                    <strong>Jadwal Sidang &amp; SIPP</strong>
                </a>
                <a class="pa-service-card" href="<?php echo esc_url($u('e-court')); ?>" data-pa-tip="Pendaftaran, panjar, &amp; sidang elektronik">
                    <div class="pa-svc-top"><span class="pa-svc-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg></span><span class="pa-svc-badge">Perkara Online</span></div>
                    <strong>Pendaftaran e-Court</strong>
                </a>
                <a class="pa-service-card" href="<?php echo esc_url($u('direktori-putusan')); ?>" data-pa-tip="Salinan putusan resmi MA RI">
                    <div class="pa-svc-top"><span class="pa-svc-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span><span class="pa-svc-badge">Putusan Resmi</span></div>
                    <strong>Direktori Putusan</strong>
                </a>
                <a class="pa-service-card" href="<?php echo esc_url($u('prosedur-berperkara')); ?>" data-pa-tip="Syarat &amp; alur gugatan/permohonan">
                    <div class="pa-svc-top"><span class="pa-svc-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span><span class="pa-svc-badge">Panduan</span></div>
                    <strong>Prosedur Berperkara</strong>
                </a>
                <a class="pa-service-card" href="<?php echo esc_url($u('biaya-proses-berperkara')); ?>" data-pa-tip="SK panjar biaya &amp; radius panggilan">
                    <div class="pa-svc-top"><span class="pa-svc-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M8 10h8M8 14h8"/></svg></span><span class="pa-svc-badge">Biaya Perkara</span></div>
                    <strong>Panjar &amp; Radius Biaya</strong>
                </a>
                <a class="pa-service-card" href="<?php echo esc_url($u('panggilan-ghaib')); ?>" data-pa-tip="Panggilan pihak tidak diketahui alamatnya">
                    <div class="pa-svc-top"><span class="pa-svc-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span><span class="pa-svc-badge">Pengumuman</span></div>
                    <strong>Panggilan Ghaib</strong>
                </a>
                <a class="pa-service-card" href="<?php echo esc_url($u('biaya-prodeo')); ?>" data-pa-tip="Pembebasan biaya &amp; bantuan hukum">
                    <div class="pa-svc-top"><span class="pa-svc-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></span><span class="pa-svc-badge">Bantuan Hukum</span></div>
                    <strong>Prodeo &amp; Posbakum</strong>
                </a>
                <a class="pa-service-card" href="<?php echo esc_url($u('akses-informasi')); ?>" data-pa-tip="Layanan informasi publik &amp; pengaduan">
                    <div class="pa-svc-top"><span class="pa-svc-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span><span class="pa-svc-badge">Layanan Publik</span></div>
                    <strong>PPID &amp; Pengaduan</strong>
                </a>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    if ($type === 'schedule') {
        $embedU = !empty($bCfg['embed_url']) ? (string) $bCfg['embed_url'] : (string) ($cfg['sipp_slide_url'] ?? '');
        $defT   = !empty($bCfg['default_tab']) ? (string) $bCfg['default_tab'] : (string) ($cfg['home_schedule_default_tab'] ?? 'auto');
        $hPx    = !empty($bCfg['height']) ? (int) $bCfg['height'] : 480;
        return pa_sinova_render_schedule_dashboard(array(
            'url'         => $embedU,
            'title'       => $title ?: 'Jadwal Persidangan & Monitor SIPP',
            'height'      => (string) $hPx,
            'default_tab' => $defT,
            'position'    => ($y <= 2) ? 'after_hero' : 'after_services',
        ));
    }

    if ($type === 'stats') {
        return function_exists('pa_portal_render_stats_widget') ? pa_portal_render_stats_widget($title ?: 'Statistik Perkara') : '';
    }

    if ($type === 'announcements') {
        $lim = !empty($bCfg['limit']) ? (int) $bCfg['limit'] : 5;
        if ($w <= 6 && function_exists('pa_portal_render_announcements_compact')) {
            return pa_portal_render_announcements_compact($title ?: 'Pengumuman & Panggilan Ghaib', $lim);
        }
        return do_shortcode('[pa_official_announcements limit="' . $lim . '"]');
    }

    if ($type === 'news') {
        $cnt = !empty($bCfg['count']) ? (int) $bCfg['count'] : 3;
        return do_shortcode('[pa_sinova_latest_posts count="' . $cnt . '"]');
    }

    if ($type === 'welcome') {
        return do_shortcode('[pa_sinova_welcome_section]');
    }

    if ($type === 'zi') {
        return do_shortcode('[pa_sinova_zi_grid]');
    }

    if ($type === 'survey') {
        $survPer   = trim((string) ($cfg['home_survey_period'] ?? 'Triwulan III')) ?: 'Triwulan III';
        $survGrade = trim((string) ($cfg['home_survey_grade'] ?? '★ Mutu A (Sangat Baik)')) ?: '★ Mutu A (Sangat Baik)';
        $survSkm   = trim((string) ($cfg['home_survey_skm'] ?? '4.00 / 4.00')) ?: '4.00 / 4.00';
        $survSpak  = trim((string) ($cfg['home_survey_spak'] ?? '4.00 / 4.00')) ?: '4.00 / 4.00';
        $survSpkp  = trim((string) ($cfg['home_survey_spkp'] ?? '4.00 / 4.00')) ?: '4.00 / 4.00';
        ob_start();
        ?>
        <section class="survey-panel pa-starter-survey pa-hg-fullh" aria-label="<?php echo esc_attr($title ?: 'Indeks Pelayanan Publik'); ?>">
            <div class="survey-slider" aria-live="polite">
                <article class="survey-slide is-active" data-slide="0">
                    <div class="survey-head">
                        <span class="survey-pill"><?php echo esc_html($survGrade); ?></span>
                        <span class="survey-period-badge"><?php echo esc_html($survPer); ?></span>
                    </div>
                    <h3><?php echo esc_html($title ?: 'Indeks Pelayanan Publik'); ?></h3>
                    <div class="survey-cards">
                        <div class="survey-card"><strong>SKM</strong> <span>Kepuasan Masyarakat</span> <b><?php echo esc_html($survSkm); ?></b></div>
                        <div class="survey-card"><strong>SPAK</strong> <span>Persepsi Anti Korupsi</span> <b><?php echo esc_html($survSpak); ?></b></div>
                        <div class="survey-card"><strong>SPKP</strong> <span>Kualitas Pelayanan</span> <b><?php echo esc_html($survSpkp); ?></b></div>
                    </div>
                </article>
            </div>
            <div style="margin-top:14px;">
                <a class="pa-mk-btn" href="<?php echo esc_url($u('survei-pelayanan')); ?>">Laporan Survei →</a>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    if ($type === 'directory') {
        ob_start();
        ?>
        <div class="pa-hub-shortcuts pa-hg-fullh">
            <div class="pa-hub-shortcuts-head">
                <h3><?php echo esc_html($title ?: 'Direktori Transparansi & Dokumen Publik'); ?> <span class="pa-hint-i" aria-hidden="true">i</span></h3>
                <p>Laporan keuangan, kinerja, biaya perkara, dan survei pelayanan.</p>
            </div>
            <div class="pa-shortcut-grid">
                <a class="pa-shortcut-item" href="<?php echo esc_url($u('lra')); ?>" data-pa-tip="Realisasi anggaran bulanan DIPA 01 &amp; 04"><span>Keuangan</span><strong>Realisasi Anggaran (LRA)</strong></a>
                <a class="pa-shortcut-item" href="<?php echo esc_url($u('dipa')); ?>" data-pa-tip="Dokumen DIPA &amp; realisasi tahunan"><span>Anggaran</span><strong>Dokumen DIPA 01 &amp; 04</strong></a>
                <a class="pa-shortcut-item" href="<?php echo esc_url($u('biaya-perkara')); ?>" data-pa-tip="LIPA 7A, 7B, &amp; 7C"><span>Kepaniteraan</span><strong>Biaya Perkara (LIPA 7)</strong></a>
                <a class="pa-shortcut-item" href="<?php echo esc_url($u('lkjip')); ?>" data-pa-tip="Akuntabilitas kinerja instansi"><span>Kinerja</span><strong>Laporan Kinerja (LKJiP)</strong></a>
                <a class="pa-shortcut-item" href="<?php echo esc_url($u('lhkpn')); ?>" data-pa-tip="Kepatuhan LHKPN pejabat"><span>Kepatuhan</span><strong>Pelaporan LHKPN &amp; ASN</strong></a>
                <a class="pa-shortcut-item" href="<?php echo esc_url($u('survei-pelayanan')); ?>" data-pa-tip="SKM, SPAK, &amp; SPKP per triwulan"><span>Survei</span><strong>Survei SKM/SPAK/SPKP</strong></a>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    if ($type === 'videos') {
        $cnt = !empty($bCfg['count']) ? (int) $bCfg['count'] : 4;
        return do_shortcode('[pa_video_gallery limit="' . $cnt . '" title="' . esc_attr($title ?: 'Galeri Video') . '"]');
    }

    if ($type === 'greetings') {
        $cnt = !empty($bCfg['count']) ? (int) $bCfg['count'] : 4;
        return do_shortcode('[pa_ucapan_board limit="' . $cnt . '" title="' . esc_attr($title ?: 'Ucapan & Belasungkawa') . '"]');
    }

    if ($type === 'dirput') {
        $cnt   = !empty($bCfg['count']) ? (int) $bCfg['count'] : (!empty($bCfg['limit']) ? (int) $bCfg['limit'] : 6);
        $ovUrl = !empty($bCfg['embed_url']) ? (string) $bCfg['embed_url'] : '';
        return function_exists('pa_portal_render_dirput_widget')
            ? pa_portal_render_dirput_widget($title ?: 'Direktori Putusan Terbaru', $cnt, $ovUrl)
            : '';
    }

    if ($type === 'iframe_embed') {
        $embedUrl = '';
        if (!empty($bCfg['embed_url']) && function_exists('pa_sinova_sanitize_https_embed_url')) {
            $ec = pa_sinova_sanitize_https_embed_url((string) $bCfg['embed_url']);
            if ($ec['valid'] && $ec['url'] !== '') {
                $embedUrl = $ec['url'];
            }
        }
        $eHeight = max(260, min(900, (int) ($bCfg['height'] ?? 440)));
        $caption = trim((string) ($bCfg['caption'] ?? ''));
        $showFs  = (string) ($bCfg['show_fullscreen'] ?? '1') !== '0';
        ob_start();
        ?>
        <section class="pa-grid-panel pa-iframe-panel" aria-label="<?php echo esc_attr($title ?: 'Sematan Aplikasi'); ?>">
            <div class="pa-gp-head">
                <h2 class="pa-gp-title"><?php echo esc_html($title ?: 'Sematan Aplikasi'); ?></h2>
                <?php if ($embedUrl !== '' && $showFs) : ?>
                    <a class="pa-gp-more" href="<?php echo esc_url($embedUrl); ?>" target="_blank" rel="noopener noreferrer">Layar Penuh ↗</a>
                <?php endif; ?>
            </div>
            <?php if ($caption !== '') : ?>
                <p class="pa-iframe-cap"><?php echo esc_html($caption); ?></p>
            <?php endif; ?>
            <?php if ($embedUrl !== '') : ?>
                <div class="pa-sipp-frame-wrap" style="border-radius:10px;overflow:hidden;border:1px solid #d5e6dc;">
                    <iframe class="pa-sipp-frame" src="<?php echo esc_url($embedUrl); ?>" title="<?php echo esc_attr($title ?: 'Sematan Iframe'); ?>" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="fullscreen" allowfullscreen style="height:clamp(260px, 55vh, <?php echo (int) $eHeight; ?>px)"></iframe>
                </div>
            <?php else : ?>
                <p class="pa-sched-empty">Tempel URL sematan HTTPS pada pengaturan blok <strong>Sematan Iframe / Aplikasi</strong>.</p>
            <?php endif; ?>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    if ($type === 'image_banner') {
        $imgUrl  = esc_url_raw((string) ($bCfg['image_url'] ?? ''), array('http', 'https'));
        $imgAlt  = trim((string) ($bCfg['image_alt'] ?? ($bCfg['caption'] ?? $title)));
        $fit     = (($bCfg['image_fit'] ?? 'cover') === 'contain') ? 'contain' : 'cover';
        $maxH    = max(180, min(900, (int) ($bCfg['height'] ?? 360)));
        $linkUrl = esc_url_raw((string) ($bCfg['link_url'] ?? ''), array('http', 'https'));
        ob_start();
        ?>
        <section class="pa-grid-panel pa-image-panel" aria-label="<?php echo esc_attr($title ?: 'Gambar / Poster'); ?>">
            <?php if ($title !== '') : ?>
                <div class="pa-gp-head">
                    <h2 class="pa-gp-title"><?php echo esc_html($title); ?></h2>
                    <?php if ($linkUrl !== '') : ?>
                        <a class="pa-gp-more" href="<?php echo esc_url($linkUrl); ?>" target="_blank" rel="noopener noreferrer">Buka ↗</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($imgUrl !== '') : ?>
                <figure class="pa-image-banner-box">
                    <?php if ($linkUrl !== '') : ?><a href="<?php echo esc_url($linkUrl); ?>" target="_blank" rel="noopener noreferrer"><?php endif; ?>
                    <img src="<?php echo esc_url($imgUrl); ?>" alt="<?php echo esc_attr($imgAlt); ?>" style="max-height:<?php echo (int) $maxH; ?>px;object-fit:<?php echo esc_attr($fit); ?>;" loading="lazy" decoding="async">
                    <?php if ($linkUrl !== '') : ?></a><?php endif; ?>
                    <?php if (!empty($bCfg['caption'])) : ?>
                        <figcaption><?php echo esc_html((string) $bCfg['caption']); ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php else : ?>
                <p class="pa-sched-empty">Pilih gambar melalui Media Library atau isi URL gambar pada pengaturan blok.</p>
            <?php endif; ?>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    if ($type === 'photo_slider') {
        $srcMode = (($bCfg['slide_source'] ?? 'custom') === 'records') ? 'records' : 'custom';
        $slides  = array();
        if ($srcMode === 'custom' && !empty($bCfg['slides']) && is_array($bCfg['slides'])) {
            foreach ($bCfg['slides'] as $sl) {
                $su = esc_url_raw((string) ($sl['image_url'] ?? ''), array('http', 'https'));
                if ($su === '') {
                    continue;
                }
                $slides[] = array(
                    'image_url' => $su,
                    'title'     => (string) ($sl['title'] ?? ''),
                    'link_url'  => esc_url_raw((string) ($sl['link_url'] ?? ''), array('http', 'https')),
                );
            }
        } elseif ($srcMode === 'records' && function_exists('pa_portal_get_records')) {
            foreach (pa_portal_get_records('slider', 8) as $sr) {
                $sm = json_decode((string) ($sr['meta_json'] ?? ''), true);
                $su = is_array($sm) ? esc_url_raw((string) ($sm['image_url'] ?? ''), array('http', 'https')) : '';
                if ($su === '') {
                    continue;
                }
                $slides[] = array(
                    'image_url' => $su,
                    'title'     => (string) ($sr['title'] ?? ''),
                    'link_url'  => is_array($sm) ? esc_url_raw((string) ($sm['btn_url'] ?? ''), array('http', 'https')) : '',
                );
            }
        }
        $slH   = max(220, min(720, (int) ($bCfg['height'] ?? 360)));
        $autoS = max(0, min(15, (int) ($bCfg['autoplay_sec'] ?? 5)));
        ob_start();
        ?>
        <section class="pa-grid-panel pa-photo-slider-panel" aria-label="<?php echo esc_attr($title ?: 'Slider Gambar'); ?>">
            <?php if ($title !== '') : ?>
                <div class="pa-gp-head"><h2 class="pa-gp-title"><?php echo esc_html($title); ?></h2></div>
            <?php endif; ?>
            <?php if (!empty($slides)) : ?>
                <div class="pa-photo-slider" data-pa-hero-slider data-autoplay="<?php echo (int) $autoS; ?>" style="--pa-ps-h:<?php echo (int) $slH; ?>px;">
                    <div class="pa-ps-track">
                        <?php foreach ($slides as $sIdx => $sl) : ?>
                            <article class="pa-ps-slide<?php echo $sIdx === 0 ? ' is-active' : ''; ?>" data-hs-slide="<?php echo (int) $sIdx; ?>" <?php echo $sIdx > 0 ? 'hidden' : ''; ?>>
                                <?php if ($sl['link_url'] !== '') : ?><a href="<?php echo esc_url($sl['link_url']); ?>" target="_blank" rel="noopener noreferrer"><?php endif; ?>
                                <img src="<?php echo esc_url($sl['image_url']); ?>" alt="<?php echo esc_attr($sl['title']); ?>" <?php echo $sIdx === 0 ? 'decoding="async"' : 'loading="lazy" decoding="async"'; ?>>
                                <?php if ($sl['title'] !== '') : ?>
                                    <div class="pa-ps-caption"><?php echo esc_html($sl['title']); ?></div>
                                <?php endif; ?>
                                <?php if ($sl['link_url'] !== '') : ?></a><?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($slides) > 1) : ?>
                        <div class="pa-hs-controls">
                            <button type="button" class="pa-hs-nav" data-hs-dir="-1" aria-label="Slide sebelumnya">‹</button>
                            <div class="pa-hs-dots" role="group" aria-label="Pilih slide">
                                <?php foreach ($slides as $sIdx => $_s) : ?>
                                    <button type="button" class="pa-hs-dot<?php echo $sIdx === 0 ? ' is-active' : ''; ?>" data-hs-to="<?php echo (int) $sIdx; ?>" aria-label="Slide <?php echo (int) ($sIdx + 1); ?>" aria-pressed="<?php echo $sIdx === 0 ? 'true' : 'false'; ?>"></button>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="pa-hs-nav" data-hs-dir="1" aria-label="Slide berikutnya">›</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <p class="pa-sched-empty">Tambahkan gambar slide pada pengaturan blok <strong>Slider Gambar / Infografis</strong>.</p>
            <?php endif; ?>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    if ($type === 'custom') {
        $embedUrl = '';
        if (!empty($bCfg['embed_url']) && function_exists('pa_sinova_sanitize_https_embed_url')) {
            $ec = pa_sinova_sanitize_https_embed_url((string) $bCfg['embed_url']);
            if ($ec['valid'] && $ec['url'] !== '') {
                $embedUrl = $ec['url'];
            }
        }
        $eHeight = max(260, min(900, (int) ($bCfg['height'] ?? 420)));
        $body    = (string) ($bCfg['body'] ?? '');
        $ctaLbl  = trim((string) ($bCfg['cta_label'] ?? ''));
        $ctaUrl  = esc_url_raw((string) ($bCfg['cta_url'] ?? ''), array('http', 'https'));
        ob_start();
        ?>
        <section class="pa-grid-panel pa-custom-panel" aria-label="<?php echo esc_attr($title ?: 'Informasi Tambahan'); ?>">
            <div class="pa-gp-head">
                <h2 class="pa-gp-title"><?php echo esc_html($title ?: 'Informasi'); ?></h2>
                <?php if ($ctaUrl !== '' && $ctaLbl !== '') : ?>
                    <a class="pa-gp-more" href="<?php echo esc_url($ctaUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($ctaLbl); ?> ↗</a>
                <?php elseif ($embedUrl !== '') : ?>
                    <a class="pa-gp-more" href="<?php echo esc_url($embedUrl); ?>" target="_blank" rel="noopener noreferrer">Layar Penuh ↗</a>
                <?php endif; ?>
            </div>
            <?php if ($body !== '') : ?>
                <div class="pa-custom-body"><?php echo wp_kses_post(wpautop($body)); ?></div>
            <?php endif; ?>
            <?php if ($embedUrl !== '') : ?>
                <div class="pa-sipp-frame-wrap" style="border-radius:10px;overflow:hidden;border:1px solid #d5e6dc;">
                    <iframe class="pa-sipp-frame" src="<?php echo esc_url($embedUrl); ?>" title="<?php echo esc_attr($title ?: 'Sematan Konten'); ?>" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="fullscreen" allowfullscreen style="height:clamp(280px, 55vh, <?php echo (int) $eHeight; ?>px)"></iframe>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    return '';
}

function pa_sinova_render_beranda(): string {
    $cfg       = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $blocks    = function_exists('pa_sinova_get_home_blocks') ? pa_sinova_get_home_blocks() : array();
    $showTick  = (string) ($cfg['home_show_ticker'] ?? '1') !== '0';
    $tickLabel = trim((string) ($cfg['home_ticker_label'] ?? 'INFO LAYANAN')) ?: 'INFO LAYANAN';
    $tickerTxt = trim((string) ($cfg['home_ticker_text'] ?? ''));
    $tickLen   = function_exists('mb_strlen') ? mb_strlen($tickerTxt, 'UTF-8') : strlen($tickerTxt);
    $tickDur   = max(18, min(65, (int) round(max(40, $tickLen) * 0.24)));
    $ghaibUrl  = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('panggilan-ghaib') : home_url('/panggilan-ghaib/');

    ob_start();
    ?>
    <div class="pa-starter-showcase">
        <?php if ($showTick && $tickerTxt !== '') : ?>
        <div class="pa-ticker-bar" role="region" aria-label="Informasi Terkini Layanan">
            <span class="pa-ticker-tag"><?php echo esc_html($tickLabel); ?></span>
            <div class="pa-ticker-viewport">
                <div class="pa-ticker-track" style="--pa-ticker-dur:<?php echo (int) $tickDur; ?>s;">
                    <span class="pa-ticker-text"><?php echo esc_html($tickerTxt); ?></span>
                    <span class="pa-ticker-text pa-ticker-dup" aria-hidden="true"><?php echo esc_html($tickerTxt); ?></span>
                </div>
            </div>
            <a class="pa-ticker-link" href="<?php echo esc_url($ghaibUrl); ?>">Panggilan Ghaib →</a>
        </div>
        <?php endif; ?>

        <div class="pa-home-grid-12">
            <?php foreach ($blocks as $blk) :
                if (empty($blk['enabled'])) {
                    continue;
                }
                $html = pa_sinova_render_home_block_item($blk, $cfg);
                if ($html === '') {
                    continue;
                }
                $x = max(1, min(12, (int) ($blk['x'] ?? 1)));
                $y = max(1, min(40, (int) ($blk['y'] ?? 1)));
                $w = max(2, min(12, (int) ($blk['w'] ?? 12)));
                $h = max(1, min(4, (int) ($blk['h'] ?? 1)));
                $narrowCls = ($w <= 6) ? ' pa-hg-narrow' : (($w <= 8) ? ' pa-hg-medium' : ' pa-hg-wide');
            ?>
                <div class="pa-hg-slot<?php echo esc_attr($narrowCls); ?>" style="--pa-gx:<?php echo (int) $x; ?>;--pa-gy:<?php echo (int) $y; ?>;--pa-gw:<?php echo (int) $w; ?>;--pa-gh:<?php echo (int) $h; ?>;">
                    <?php echo $html; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('pa_sinova_beranda', 'pa_sinova_render_beranda');

// Kompatibilitas Beranda statis lama: sisipkan otomatis Dashboard Jadwal Sidang bila URL Slide SIPP diisi namun Beranda belum memakai [pa_sinova_beranda]
add_filter('the_content', static function ($content) {
    if (is_admin() || !is_front_page() || !is_main_query() || !in_the_loop() || pa_sinova_is_adoption_mode()) {
        return $content;
    }
    if (!is_string($content) || strpos($content, 'pa-sched-hub') !== false || strpos($content, 'pa-sipp-slide-section') !== false) {
        return $content;
    }
    $cfg = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    if ((string) ($cfg['home_schedule_position'] ?? 'after_hero') === 'hidden' || trim((string) ($cfg['sipp_slide_url'] ?? '')) === '') {
        return $content;
    }
    $html = pa_sinova_render_schedule_dashboard(array('position' => 'after_services'));
    if ($html === '') {
        return $content;
    }
    if (strpos($content, '<section class="pa-welcome-grid"') !== false) {
        return preg_replace('/<section class="pa-welcome-grid"/', $html . "\n" . '<section class="pa-welcome-grid"', $content, 1) ?: ($content . $html);
    }
    if (strpos($content, '<div class="pa-starter-showcase">') !== false) {
        return preg_replace('/<\/div>\s*$/', "\n" . $html . "\n</div>", $content, 1) ?: ($content . $html);
    }
    return $content . $html;
}, 25);

// Pengaman tampilan publik halaman /direktori-putusan/ bila isi DB masih memuat placeholder [LENGKAPI...] lama
add_filter('the_content', static function ($content) {
    if (is_admin() || !is_page('direktori-putusan') || !is_main_query() || !in_the_loop()) {
        return $content;
    }
    if (!is_string($content) || stripos($content, '[LENGKAPI') === false) {
        return $content;
    }
    $cfg  = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $dir  = trim((string) ($cfg['direktori_url'] ?? ''));
    $norm = rtrim(strtolower($dir), '/');
    if ($dir === '' || $norm === 'https://putusan3.mahkamahagung.go.id' || $norm === 'http://putusan3.mahkamahagung.go.id') {
        return $content;
    }
    return do_shortcode('[pa_direktori_putusan limit="10"]');
}, 15);

// 4. Shortcode [pa_sinova_latest_posts count="3"]: Berita Terkini Satuan Kerja (Memisahkan Kategori Pengumuman)
add_shortcode('pa_sinova_latest_posts', static function ($atts) {
    $cfgAtts = shortcode_atts(array('count' => 3), $atts, 'pa_sinova_latest_posts');
    $count   = max(1, min(6, (int) $cfgAtts['count']));
    $qArgs   = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $count,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    if (function_exists('get_category_by_slug')) {
        $annCat = get_category_by_slug('pengumuman');
        if ($annCat && !is_wp_error($annCat)) {
            $qArgs['category__not_in'] = array((int) $annCat->term_id);
        }
    }
    $posts = get_posts($qArgs);
    if (empty($posts) && isset($qArgs['category__not_in'])) {
        unset($qArgs['category__not_in']);
        $posts = get_posts($qArgs);
    }

    if (empty($posts)) {
        return '';
    }

    ob_start();
    ?>
    <section class="pa-section-block pa-news-section" aria-label="Berita Terkini Satuan Kerja">
        <div class="pa-news-head">
            <div>
                <h2 class="pa-starter-heading" style="text-align:left;">BERITA TERKINI SATKER <span class="pa-hint-i" aria-hidden="true">i</span></h2>
                <p class="pa-starter-sub" style="text-align:left;margin-bottom:0;">Liputan Kegiatan, Inovasi, dan Publikasi Berita Satuan Kerja</p>
            </div>
            <a class="pa-news-all" href="<?php echo esc_url(function_exists('pa_sinova_news_archive_url') ? pa_sinova_news_archive_url() : home_url('/?post_type=post')); ?>" data-pa-tip="Lihat seluruh arsip berita satuan kerja.">Semua Berita →</a>
        </div>
        <div class="pa-news-grid">
            <?php foreach ($posts as $pIdx => $p) :
                $permalink = get_permalink($p);
                $dateStr   = get_the_date('d M Y', $p);
                $excerpt   = wp_trim_words(wp_strip_all_tags((string) ($p->post_excerpt ?: $p->post_content)), 20, '...');
                $thumbUrl  = function_exists('pa_portal_extract_post_thumb') ? pa_portal_extract_post_thumb($p, 'medium_large') : (string) get_the_post_thumbnail_url($p->ID, 'medium_large');
                if ($thumbUrl === '' && function_exists('pa_sinova_asset_url')) {
                    $thumbUrl = pa_sinova_asset_url('dummy-berita-' . (($pIdx % 3) + 1) . '.svg');
                }
            ?>
                <article class="pa-news-card">
                    <a class="pa-news-thumb" href="<?php echo esc_url((string) $permalink); ?>" aria-hidden="true" tabindex="-1">
                        <?php if ($thumbUrl !== '') : ?>
                            <img src="<?php echo esc_url($thumbUrl); ?>" alt="" loading="lazy" decoding="async">
                        <?php else : ?>
                            <span class="pa-news-thumb-ph">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            </span>
                        <?php endif; ?>
                    </a>
                    <div class="pa-news-body">
                        <span class="pa-news-date"><?php echo esc_html($dateStr); ?></span>
                        <h3><a href="<?php echo esc_url((string) $permalink); ?>"><?php echo esc_html(get_the_title($p)); ?></a></h3>
                        <p><?php echo esc_html($excerpt); ?></p>
                        <a class="pa-news-more" href="<?php echo esc_url((string) $permalink); ?>">Baca selengkapnya →</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
});

// 5. Tambahkan Logo Satker & Satu Kotak Pencarian Terpadu ke Baris 1 Header Astra
add_action('astra_render_header_column', static function ($row, $section) {
    if ($row !== 'primary' || $section !== 'left' || is_admin() || pa_sinova_is_adoption_mode()) {
        return;
    }
    $cfg     = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $satker  = trim((string) ($cfg['satker_name'] ?? '')) ?: (get_bloginfo('name') ?: 'Pengadilan Agama');
    $logoUrl = trim((string) ($cfg['logo_url'] ?? ''));
    ?>
    <button type="button" id="pa-cmdk-open" class="pa-unified-search-btn" aria-haspopup="dialog" aria-label="Cari layanan, dokumen, dan berita">
        <span class="pa-us-left">
            <svg class="pa-us-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
            <span class="pa-us-placeholder">Cari layanan, dokumen, berita...</span>
        </span>
        <kbd class="pa-us-kbd">Ctrl K</kbd>
    </button>
    <?php if ($logoUrl !== '') : ?>
        <a class="pa-header-custom-logo-link" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr($satker); ?>">
            <img src="<?php echo esc_url($logoUrl); ?>" alt="Logo <?php echo esc_attr($satker); ?>" class="pa-header-custom-logo" height="46" fetchpriority="high" decoding="async">
        </a>
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
    $palette   = sanitize_key((string) ($cfg['accent_palette'] ?? 'gold'));
    $greenHex  = ($palette === 'emerald_silver') ? '#043927' : '#056e4f';
    $greenDk   = ($palette === 'emerald_silver') ? '#022c22' : '#043927';
    $greenDeep = ($palette === 'emerald_silver') ? '#011e17' : '#022c22';
    $goldHex   = ($palette === 'badilag_orange') ? '#ff9900' : (($palette === 'emerald_silver') ? '#94a3b8' : '#C9A227');
    $goldLight = ($palette === 'badilag_orange') ? '#ffb84d' : (($palette === 'emerald_silver') ? '#e2e8f0' : '#E5C158');
    ?>
<style id="pa-sinova-starter-ui-css">
:root {
    --pa-green: <?php echo esc_html($greenHex); ?>;
    --pa-green-dark: <?php echo esc_html($greenDk); ?>;
    --pa-green-deep: <?php echo esc_html($greenDeep); ?>;
    --pa-gold: <?php echo esc_html($goldHex); ?>;
    --pa-gold-light: <?php echo esc_html($goldLight); ?>;
<?php if (!pa_sinova_is_adoption_mode()) : ?>
    --e-global-typography-primary-font-family: 'Inter', system-ui, -apple-system, sans-serif;
    --e-global-typography-secondary-font-family: 'Inter', system-ui, -apple-system, sans-serif;
<?php endif; ?>
}
<?php if (!pa_sinova_is_adoption_mode()) : ?>
body, button, input, select, textarea {
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}
<?php endif; ?>
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
<?php if (!pa_sinova_is_adoption_mode()) : ?>
.pa-scroll-reveal, .elementor-invisible {
    opacity: 1 !important;
    visibility: visible !important;
    animation: none !important;
}
<?php endif; ?>

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

/* Styling Header Native Tema Astra menjadi 2 Baris Elegan & Proporsional */
body.pa-sinova-classic-shell .main-header-bar {
    background: linear-gradient(125deg, #033022 0%, #05583f 54%, #066c4e 100%) !important;
    border-bottom: 1px solid rgba(229, 193, 88, 0.30) !important;
    box-shadow: 0 12px 30px -12px rgba(2, 44, 34, 0.28) !important;
    color: #fff !important;
    padding: 0 !important;
    line-height: 1.4 !important;
    box-sizing: border-box !important;
}
body.pa-sinova-classic-shell .main-header-bar::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: -1px;
    height: 2px;
    background: linear-gradient(90deg, rgba(201, 162, 39, 0.16) 0%, rgba(229, 193, 88, 0.88) 50%, rgba(201, 162, 39, 0.16) 100%);
    pointer-events: none;
    z-index: 2;
}
html {
    scroll-padding-top: var(--pa-sticky-offset, 72px);
}
html:has(> body.admin-bar) {
    scroll-padding-top: calc(var(--pa-sticky-offset, 72px) + 32px);
}
body.pa-sinova-portal {
    overflow-x: clip !important;
}
body.pa-sinova-classic-shell #page,
body.pa-sinova-classic-shell .site {
    overflow: visible !important;
}
body.pa-sinova-portal.admin-bar {
    --pa-wpbar-h: 32px;
}
@media (max-width: 782px) {
    body.pa-sinova-portal.admin-bar {
        --pa-wpbar-h: 46px;
    }
}
body.pa-sinova-classic-shell #masthead {
    position: sticky !important;
    top: calc(var(--pa-wpbar-h, 0px) - var(--pa-brand-h, 0px)) !important;
    z-index: 9990 !important;
    overflow: visible !important;
    container-type: scroll-state;
    container-name: pa-sticky-nav;
}
@media (max-width: 921px) {
    body.pa-sinova-classic-shell #masthead {
        top: var(--pa-wpbar-h, 0px) !important;
    }
}
body.pa-sinova-classic-shell #ast-desktop-header,
body.pa-sinova-classic-shell #ast-desktop-header .main-header-bar-wrap,
body.pa-sinova-classic-shell #ast-desktop-header .main-header-bar {
    position: relative !important;
    z-index: 9990 !important;
    overflow: visible !important;
}
@container pa-sticky-nav scroll-state(stuck: top) {
    .main-header-bar {
        box-shadow: 0 12px 30px rgba(2, 44, 34, 0.34) !important;
    }
    .site-header-primary-section-right {
        background: rgba(2, 38, 29, 0.96) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    .pa-shell-nav-row {
        background: rgba(2, 36, 28, 0.96);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
}
html.pa-nav-stuck body.pa-sinova-classic-shell #ast-desktop-header .main-header-bar {
    box-shadow: 0 12px 30px rgba(2, 44, 34, 0.34) !important;
}
html.pa-nav-stuck body.pa-sinova-classic-shell #ast-desktop-header .site-header-primary-section-right {
    background: rgba(2, 38, 29, 0.96) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}
html.pa-nav-stuck .pa-shell-navwrap {
    background: rgba(2, 36, 28, 0.96) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    box-shadow: 0 12px 30px rgba(2, 44, 34, 0.34) !important;
}
body.pa-sinova-classic-shell #ast-desktop-header .site-primary-header-wrap {
    position: relative !important;
    max-width: 100% !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    box-sizing: border-box !important;
    overflow: visible !important;
}
body.pa-sinova-classic-shell #ast-desktop-header .ast-builder-grid-row {
    display: flex !important;
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 0 !important;
    position: static !important;
    overflow: visible !important;
}
body.pa-sinova-classic-shell #ast-desktop-header .site-header-primary-section-left {
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    padding: 15px max(20px, calc((100% - 1180px) / 2)) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.09) !important;
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 12px !important;
}
body.pa-sinova-classic-shell #ast-desktop-header .site-header-primary-section-left .ast-builder-layout-element[data-section="title_tagline"] {
    order: 1;
}
body.pa-sinova-classic-shell #ast-desktop-header .pa-header-custom-logo-link {
    order: 0;
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
    text-decoration: none !important;
}
body.pa-sinova-classic-shell #ast-desktop-header .pa-header-custom-logo,
.pa-header-custom-logo {
    display: block;
    height: 46px;
    width: auto;
    max-width: 180px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.96);
    padding: 4px 6px;
    box-shadow: 0 4px 12px rgba(2, 44, 34, 0.18);
    object-fit: contain;
}
body.pa-sinova-classic-shell #ast-desktop-header .pa-unified-search-btn,
.pa-unified-search-btn {
    order: 2;
    margin-left: auto;
    width: 315px;
    max-width: 100%;
    height: 40px;
    padding: 0 8px 0 14px !important;
    border-radius: 999px !important;
    border: 1px solid rgba(255, 255, 255, 0.22) !important;
    background: rgba(2, 36, 27, 0.30) !important;
    backdrop-filter: blur(8px);
    color: #e6f4ee !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 10px !important;
    font: 600 12.5px/1.2 var(--pa-font, system-ui, sans-serif) !important;
    cursor: pointer !important;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
    transition: background-color 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease !important;
}
body.pa-sinova-classic-shell #ast-desktop-header .pa-unified-search-btn:hover,
body.pa-sinova-classic-shell #ast-desktop-header .pa-unified-search-btn:focus,
body.pa-sinova-classic-shell #ast-desktop-header .pa-unified-search-btn:focus-visible,
.pa-unified-search-btn:hover,
.pa-unified-search-btn:focus,
.pa-unified-search-btn:focus-visible {
    background: rgba(2, 36, 27, 0.62) !important;
    border-color: rgba(229, 193, 88, 0.78) !important;
    color: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(229, 193, 88, 0.18) !important;
    outline: none !important;
}
.pa-us-left {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    overflow: hidden;
}
.pa-us-ico { width: 15px; height: 15px; color: #E5C158; flex-shrink: 0; }
.pa-us-placeholder { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #d1fae5; }
.pa-us-kbd {
    flex-shrink: 0;
    padding: 4px 9px;
    border-radius: 999px;
    background: rgba(2, 36, 27, 0.72);
    border: 1px solid rgba(229, 193, 88, 0.35);
    color: #E5C158;
    font: 700 11px/1 monospace;
}
.ast-mobile-header-wrap .pa-unified-search-btn { display: none !important; }
.ast-mobile-header-wrap .pa-header-custom-logo-link { display: inline-flex; align-items: center; flex-shrink: 0; margin-right: 8px; }
.ast-mobile-header-wrap .pa-header-custom-logo { height: 34px; max-width: 120px; padding: 3px; border-radius: 8px; }

body.pa-sinova-classic-shell #ast-desktop-header .site-header-primary-section-right {
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    background: linear-gradient(180deg, rgba(2, 36, 27, 0.42) 0%, rgba(2, 36, 27, 0.56) 100%) !important;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
    padding: 8px max(20px, calc((100% - 1180px) / 2)) !important;
    justify-content: stretch !important;
    position: static !important;
    overflow: visible !important;
}
body.pa-sinova-classic-shell #ast-desktop-header .ast-builder-menu-1,
body.pa-sinova-classic-shell #ast-desktop-header .ast-main-header-bar-alignment,
body.pa-sinova-classic-shell #ast-desktop-header .main-header-bar-navigation,
body.pa-sinova-classic-shell #ast-desktop-header #primary-site-navigation-desktop,
body.pa-sinova-classic-shell #ast-desktop-header .main-navigation {
    width: 100% !important;
    line-height: 1.3 !important;
    position: static !important;
    overflow: visible !important;
}
body.pa-sinova-classic-shell .site-title {
    font-size: clamp(1.1rem, 2vw, 1.42rem) !important;
    font-weight: 800 !important;
    line-height: 1.25 !important;
    letter-spacing: -0.01em;
}
body.pa-sinova-classic-shell .site-title a,
body.pa-sinova-classic-shell .site-description { color: #ffffff !important; }
body.pa-sinova-classic-shell .site-description { color: #d1fae5 !important; font-size: 12px !important; }
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .main-header-menu {
    display: flex !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 6px !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    position: static !important;
    overflow: visible !important;
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .main-header-menu > .menu-item {
    flex: 0 1 auto !important;
    line-height: 1.3 !important;
    position: static !important;
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item > .ast-menu-toggle {
    display: none !important;
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item > .menu-link,
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item:last-child > .menu-link,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .ast-builder-menu-1 .main-header-menu > .menu-item > .menu-link {
    color: #ffffff !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    line-height: 1.3 !important;
    height: auto !important;
    margin: 0 !important;
    padding: 8px 16px !important;
    border-radius: 999px !important;
    border: 1px solid transparent !important;
    border-bottom: 1px solid transparent !important;
    white-space: nowrap !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    box-sizing: border-box !important;
    transition: background-color 0.16s ease, color 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease !important;
}
@media (max-width: 1180px) {
    body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item > .menu-link,
    body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .ast-builder-menu-1 .main-header-menu > .menu-item > .menu-link {
        padding: 7px 11px !important;
        font-size: 12.2px !important;
        gap: 5px !important;
    }
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .current-menu-item > .menu-link,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .ast-builder-menu-1 .main-header-menu > .current-menu-item > .menu-link {
    color: #E5C158 !important;
    background: rgba(255, 255, 255, 0.10) !important;
    border-color: rgba(229, 193, 88, 0.26) !important;
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item > .menu-link:hover,
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item.is-mega-open > .menu-link,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .ast-builder-menu-1 .main-header-menu > .menu-item > .menu-link:hover,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .ast-builder-menu-1 .main-header-menu > .menu-item.is-mega-open > .menu-link {
    color: #E5C158 !important;
    background: rgba(255, 255, 255, 0.14) !important;
    border-color: rgba(229, 193, 88, 0.38) !important;
    box-shadow: 0 4px 14px rgba(2, 44, 34, 0.18) !important;
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item > .menu-link .ast-header-navigation-arrow,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .main-header-menu > .menu-item > .menu-link .ast-header-navigation-arrow {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    position: static !important;
    width: auto !important;
    height: auto !important;
    min-width: 0 !important;
    min-height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    line-height: 1 !important;
    flex-shrink: 0 !important;
    color: rgba(229, 193, 88, 0.85) !important;
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item > .menu-link .ast-icon.icon-arrow,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .main-header-menu > .menu-item > .menu-link .ast-icon.icon-arrow {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 !important;
    padding: 0 !important;
    line-height: 1 !important;
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item > .menu-link svg.ast-arrow-svg,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .main-header-menu > .menu-item > .menu-link svg.ast-arrow-svg {
    position: static !important;
    top: 0 !important;
    left: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    width: 10px !important;
    height: 10px !important;
    display: block !important;
    color: currentColor !important;
    fill: currentColor !important;
    transform-origin: center center !important;
    transition: transform 0.2s cubic-bezier(0.22, 1, 0.36, 1) !important;
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item.is-mega-open > .menu-link .ast-header-navigation-arrow,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .main-header-menu > .menu-item.is-mega-open > .menu-link .ast-header-navigation-arrow {
    color: #E5C158 !important;
}
body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item.is-mega-open > .menu-link svg.ast-arrow-svg,
body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .main-header-menu > .menu-item.is-mega-open > .menu-link svg.ast-arrow-svg {
    transform: rotate(180deg) !important;
}
@media (min-width: 961px) {
    body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item > .sub-menu,
    body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .main-header-menu > .menu-item > .sub-menu,
    .pa-shell-navwrap .pa-shell-menu > li > .pa-shell-submenu {
        position: absolute !important;
        left: 50% !important;
        right: auto !important;
        top: calc(100% + 10px) !important;
        width: min(1180px, calc(100vw - 32px)) !important;
        max-width: 1180px !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        border: 1px solid rgba(4, 57, 39, 0.11) !important;
        border-top: 2px solid rgba(201, 162, 39, 0.72) !important;
        border-radius: 18px !important;
        box-shadow: 0 24px 56px -12px rgba(2, 44, 34, 0.22), 0 6px 16px -4px rgba(2, 44, 34, 0.08) !important;
        max-height: none !important;
        overflow: visible !important;
        display: block !important;
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
        transform: translate(-50%, 6px) !important;
        transition: opacity 0.16s ease, transform 0.16s ease, visibility 0.16s ease !important;
        z-index: 9999 !important;
    }
    body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item > .sub-menu::before,
    .pa-shell-navwrap .pa-shell-menu > li > .pa-shell-submenu::before {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        top: -16px;
        height: 16px;
    }
    body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item.is-mega-open > .sub-menu,
    body.pa-sinova-classic-shell.ast-desktop #ast-desktop-header .main-header-menu > .menu-item.is-mega-open > .sub-menu,
    body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item:not(.is-mega-closed):hover > .sub-menu,
    body.pa-sinova-classic-shell #ast-desktop-header #ast-hf-menu-1 > .menu-item:not(.is-mega-closed):focus-within > .sub-menu,
    .pa-shell-navwrap .pa-shell-menu > li.is-mega-open > .pa-shell-submenu,
    .pa-shell-navwrap .pa-shell-menu > li:not(.is-mega-closed):hover > .pa-shell-submenu,
    .pa-shell-navwrap .pa-shell-menu > li:not(.is-mega-closed):focus-within > .pa-shell-submenu {
        opacity: 1 !important;
        visibility: visible !important;
        pointer-events: auto !important;
        transform: translate(-50%, 0) !important;
    }
}
body.pa-sinova-classic-shell .ast-mobile-header-wrap .ast-primary-header-bar { padding: 10px 14px !important; }
body.pa-sinova-classic-shell .ast-mobile-menu-buttons .menu-toggle { background: rgba(201, 162, 39, 0.2) !important; border-color: #C9A227 !important; color: #E5C158 !important; }
body.pa-sinova-classic-shell .ast-mobile-popup-drawer .ast-mobile-popup-inner { background: #043927 !important; }
body.pa-sinova-classic-shell .ast-mobile-popup-drawer .menu-link { color: #ffffff !important; border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important; }

/* Mega Menu 4-Kolom Mengambang Elegan & Simetris */
.pa-mega-panel {
    list-style: none !important;
    box-sizing: border-box !important;
}
.pa-mega-panel > li {
    list-style: none !important;
    margin: 0 !important;
    width: 100% !important;
    box-sizing: border-box !important;
}
.pa-mega-panel > li.pa-mega-head {
    padding: 16px 24px 14px !important;
    border-bottom: 1px solid rgba(4, 57, 39, 0.07);
    border-radius: 16px 16px 0 0;
    display: flex !important;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #ffffff;
}
.pa-mega-head-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.pa-mega-head-title {
    margin: 0;
    font-size: 15.5px;
    font-weight: 800;
    color: #043927;
    letter-spacing: -0.01em;
}
.pa-mega-count-pill {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 999px;
    background: #ecfdf5;
    border: 1px solid #bbf7d0;
    color: #056e4f;
    font-size: 12px;
    font-weight: 700;
}
.pa-mega-head-hint {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
}
.pa-mega-panel > li.pa-mega-body {
    padding: 18px 24px 20px !important;
    max-height: 58vh;
    overflow-y: auto;
    background: #ffffff;
}
.pa-mega-grid {
    list-style: none !important;
    margin: 0 !important;
    padding: 0 !important;
    display: grid !important;
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    align-items: stretch !important;
    gap: 12px !important;
}
.pa-mega-grid[data-mega-cols="2"] {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
}
.pa-mega-grid[data-mega-cols="3"] {
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
}
.pa-mega-grid > li {
    list-style: none !important;
    margin: 0 !important;
    padding: 0 !important;
    display: flex !important;
}
.pa-mega-grid .ast-icon { display: none !important; }
.pa-mega-card-link,
body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu .pa-mega-card-link {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 10px !important;
    width: 100% !important;
    height: 100% !important;
    padding: 13px 16px !important;
    min-height: 52px !important;
    border-radius: 12px !important;
    background: #fbfdfc !important;
    border: 1px solid rgba(4, 57, 39, 0.10) !important;
    color: #0f172a !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    line-height: 1.38 !important;
    text-decoration: none !important;
    white-space: normal !important;
    box-sizing: border-box !important;
    box-shadow: 0 1px 2px rgba(2, 44, 34, 0.03) !important;
    transition: border-color 0.15s ease, background-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease, color 0.15s ease !important;
}
.pa-mega-card-link::after,
body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu .pa-mega-card-link::after {
    content: "→";
    font-weight: 800;
    color: #056e4f;
    opacity: 0;
    transform: translateX(-2px);
    transition: opacity 0.15s ease, transform 0.15s ease;
    flex-shrink: 0;
}
.pa-mega-card-link:hover,
.pa-mega-card-link:focus-visible,
body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu .pa-mega-card-link:hover,
body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu .pa-mega-card-link:focus-visible {
    background: #effaf4 !important;
    border-color: rgba(5, 110, 79, 0.38) !important;
    color: #043927 !important;
    transform: translateY(-2px);
    box-shadow: 0 10px 22px -6px rgba(4, 57, 39, 0.14), inset 3px 0 0 #C9A227 !important;
}
.pa-mega-card-link:hover::after,
.pa-mega-card-link:focus-visible::after,
body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu .pa-mega-card-link:hover::after,
body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu .pa-mega-card-link:focus-visible::after {
    opacity: 0.78;
    transform: translateX(2px);
}
.pa-mega-card-label {
    flex: 1;
    min-width: 0;
    overflow-wrap: break-word;
}
.pa-mega-panel > li.pa-mega-foot {
    padding: 12px 24px 13px !important;
    background: #f8fbf9;
    border-top: 1px solid rgba(4, 57, 39, 0.07);
    border-radius: 0 0 16px 16px;
    display: flex !important;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    font-size: 12.2px;
    color: #475569;
}
.pa-mega-crumb-wrap {
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: 0;
    flex: 1;
}
.pa-mega-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: var(--pa-gold);
    flex-shrink: 0;
}
.pa-mega-crumb-cat { color: #056e4f; font-weight: 800; white-space: nowrap; }
.pa-mega-crumb-sep { color: #94a3b8; }
.pa-mega-crumb-txt {
    color: #475569;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pa-mega-avail {
    padding: 3px 11px;
    border-radius: 999px;
    background: #fef9e7;
    border: 1px solid #f3dfa0;
    color: #854d0e;
    font-size: 11.8px;
    font-weight: 700;
    white-space: nowrap;
}
@media (max-width: 1120px) {
    .pa-mega-grid { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
}
@media (max-width: 960px) {
    .pa-mega-head { padding: 12px 14px !important; }
    .pa-mega-body { padding: 12px 14px !important; max-height: none; }
    .pa-mega-grid,
    .pa-mega-grid[data-mega-cols="2"],
    .pa-mega-grid[data-mega-cols="3"] { grid-template-columns: 1fr !important; gap: 8px !important; }
    .pa-mega-foot { padding: 10px 14px !important; }
}
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
    background: #02241c;
    color: #d1fae5;
    font-size: 12px;
    padding: 7px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
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
.pa-shell-masthead { background: linear-gradient(125deg, #033022 0%, #05583f 54%, #066c4e 100%); color: #fff; padding: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.09); }
.pa-shell-brand { display: flex; align-items: center; gap: 14px; text-decoration: none; color: #fff; }
.pa-shell-emblem { width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #C9A227 0%, #E5C158 100%); color: #022c22; font-weight: 900; font-size: 17px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.pa-shell-logo-img { height: 48px; width: auto; max-width: 160px; border-radius: 10px; background: rgba(255, 255, 255, 0.96); padding: 4px 6px; object-fit: contain; flex-shrink: 0; box-shadow: 0 4px 12px rgba(2, 44, 34, 0.2); }
.pa-shell-brand-title { margin: 0; font-size: clamp(1.05rem, 2vw, 1.4rem); font-weight: 800; color: #fff; line-height: 1.2; }
.pa-shell-brand-sub { margin: 2px 0 0; font-size: 12.5px; color: #d1fae5; }
.pa-shell-badge { padding: 6px 12px; border-radius: 999px; background: rgba(201, 162, 39, 0.16); border: 1px solid #C9A227; color: #E5C158; font-size: 12px; font-weight: 700; }
.pa-shell-navwrap { background: linear-gradient(180deg, #033124 0%, #02241c 100%); border-bottom: 1px solid rgba(229, 193, 88, 0.32); box-shadow: 0 12px 28px -12px rgba(2, 44, 34, 0.24); position: sticky; top: var(--pa-wpbar-h, 0px); z-index: 9990; container-type: scroll-state; container-name: pa-sticky-nav; transition: box-shadow 0.2s ease, background-color 0.2s ease; }
.pa-shell-nav-row { max-width: 1180px; margin: 0 auto; padding: 8px 16px; display: flex; align-items: center; justify-content: space-between; position: relative; }
.pa-shell-menu-btn { display: none; margin: 4px 0; padding: 8px 14px; border-radius: 8px; border: 1.5px solid #C9A227; background: rgba(255, 255, 255, 0.12); color: #E5C158; font-weight: 700; cursor: pointer; }
.pa-shell-menu { list-style: none; margin: 0; padding: 0; width: 100%; display: flex; flex-wrap: nowrap; align-items: center; justify-content: space-between; gap: 6px; position: static; }
.pa-shell-menu > li { position: static; flex: 0 1 auto; }
.pa-shell-menu > li > a { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 999px; border: 1px solid transparent; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 700; white-space: nowrap; transition: background-color 0.16s ease, color 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease; }
.pa-shell-menu > li > a:hover,
.pa-shell-menu > li.is-mega-open > a,
.pa-shell-menu > li:focus-within > a { color: #E5C158; background: rgba(255, 255, 255, 0.14); border-color: rgba(229, 193, 88, 0.38); box-shadow: 0 4px 12px rgba(2, 44, 34, 0.16); }
.pa-shell-submenu { list-style: none; margin: 0; padding: 0; background: #ffffff; border: 1px solid rgba(4, 57, 39, 0.11); border-top: 2px solid rgba(201, 162, 39, 0.72); border-radius: 18px; box-shadow: 0 24px 56px -12px rgba(2, 44, 34, 0.22); z-index: 9999; }
@media (max-width: 960px) {
    .pa-shell-menu-btn { display: inline-flex; }
    .pa-shell-nav-row { flex-direction: column; align-items: stretch; }
    .pa-shell-menu { display: none; flex-direction: column; align-items: stretch; padding-bottom: 10px; gap: 4px; }
    .pa-shell-menu.is-open { display: flex; }
    .pa-shell-menu > li > a { width: 100%; }
    .pa-shell-submenu { display: none; position: static; box-shadow: none; border-radius: 10px; margin: 4px 0 8px 0; }
    .pa-shell-menu > li.is-mega-open > .pa-shell-submenu,
    .pa-shell-menu > li:focus-within > .pa-shell-submenu { display: block; }
}

/* CSS Beranda Portal Resmi Pengadilan Agama (Clean Hover-First + GPU Parallax) */
.pa-starter-showcase { max-width: 1180px; margin: 6px auto 28px; font-family: 'Inter', system-ui, sans-serif; overflow: visible !important; }
.pa-section-block, .pa-services-grid, .pa-zi-grid, .pa-news-grid, .pa-split-section { overflow: visible !important; }
.pa-ticker-bar { display: flex; flex-wrap: nowrap; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 14px; border-radius: 10px; background: #f4faf7; border: 1px solid #d5e6dc; border-left: 4px solid #C9A227; margin-bottom: 18px; font-size: 12.5px; color: #043927; overflow: hidden; }
.pa-ticker-tag { padding: 3px 9px; border-radius: 6px; background: #056e4f; color: #fff; font-size: 11px; font-weight: 800; letter-spacing: 0.04em; flex-shrink: 0; white-space: nowrap; }
.pa-ticker-viewport { flex: 1 1 auto; min-width: 0; overflow: hidden; position: relative; mask-image: linear-gradient(90deg, transparent 0%, #000 3%, #000 97%, transparent 100%); -webkit-mask-image: linear-gradient(90deg, transparent 0%, #000 3%, #000 97%, transparent 100%); }
.pa-ticker-track { display: inline-flex; align-items: center; gap: 48px; width: max-content; will-change: transform; animation: paTickerMarquee var(--pa-ticker-dur, 28s) linear infinite; }
.pa-ticker-bar:hover .pa-ticker-track,
.pa-ticker-bar:focus-within .pa-ticker-track { animation-play-state: paused; }
.pa-ticker-text { font-weight: 600; white-space: nowrap; }
.pa-ticker-link { color: #056e4f !important; font-weight: 800; text-decoration: none !important; white-space: nowrap; flex-shrink: 0; }
.pa-ticker-link:hover { text-decoration: underline !important; }
@keyframes paTickerMarquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
html[data-pa-nomotion="1"] .pa-ticker-track { animation: none !important; width: auto; }
html[data-pa-nomotion="1"] .pa-ticker-dup { display: none; }
html[data-pa-nomotion="1"] .pa-ticker-text { white-space: normal; }
@media (prefers-reduced-motion: reduce) {
    .pa-ticker-track { animation: none; width: auto; }
    .pa-ticker-dup { display: none; }
    .pa-ticker-text { white-space: normal; }
}
@media (max-width: 640px) {
    .pa-ticker-bar { flex-wrap: wrap; }
    .pa-ticker-viewport { order: 3; width: 100%; flex-basis: 100%; }
}
html[data-pa-theme="dark"] .pa-ticker-bar { background: #13221a; border-color: #2c4c3b; color: #e6f4ee; }
html[data-pa-theme="dark"] .pa-ticker-link { color: #E5C158 !important; }
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
.pa-btn-gold { display: inline-flex; align-items: center; padding: 11px 20px; border-radius: 10px; background: linear-gradient(135deg, var(--pa-gold, #C9A227) 0%, var(--pa-gold-light, #E5C158) 100%); color: #022c22 !important; font-weight: 800; font-size: 13.5px; text-decoration: none !important; box-shadow: 0 6px 16px rgba(0, 0, 0, 0.18); transition: transform 0.16s ease, box-shadow 0.16s ease; }
.pa-hero-banner[data-pa-pattern="islamic"]::before {
    background-image:
        radial-gradient(circle at 82% 24%, rgba(229, 193, 88, 0.22), transparent 45%),
        radial-gradient(circle at 14% 78%, rgba(16, 185, 129, 0.20), transparent 42%),
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='64' height='64' viewBox='0 0 64 64'%3E%3Cpath d='M32 6 L39 25 L58 32 L39 39 L32 58 L25 39 L6 32 L25 25 Z' fill='none' stroke='%23E5C158' stroke-opacity='0.11' stroke-width='1.2'/%3E%3Ccircle cx='32' cy='32' r='16' fill='none' stroke='%23ffffff' stroke-opacity='0.06'/%3E%3C/svg%3E");
    background-size: auto, auto, 64px 64px;
}
.pa-hero-banner[data-pa-pattern="batik"]::before {
    background-image:
        radial-gradient(circle at 82% 24%, rgba(229, 193, 88, 0.22), transparent 45%),
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='56' viewBox='0 0 56 56'%3E%3Ccircle cx='28' cy='14' r='12' fill='none' stroke='%23E5C158' stroke-opacity='0.12'/%3E%3Ccircle cx='28' cy='42' r='12' fill='none' stroke='%23E5C158' stroke-opacity='0.12'/%3E%3Ccircle cx='14' cy='28' r='12' fill='none' stroke='%23ffffff' stroke-opacity='0.07'/%3E%3Ccircle cx='42' cy='28' r='12' fill='none' stroke='%23ffffff' stroke-opacity='0.07'/%3E%3Ccircle cx='28' cy='28' r='3' fill='%23E5C158' fill-opacity='0.16'/%3E%3C/svg%3E");
    background-size: auto, 56px 56px;
}
.pa-hero-banner[data-pa-pattern="songket"]::before {
    background-image:
        radial-gradient(circle at 82% 24%, rgba(229, 193, 88, 0.22), transparent 45%),
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 48 48'%3E%3Cpolygon points='24,3 45,24 24,45 3,24' fill='none' stroke='%23E5C158' stroke-opacity='0.12' stroke-width='1.2'/%3E%3Cpolygon points='24,12 36,24 24,36 12,24' fill='none' stroke='%23ffffff' stroke-opacity='0.07'/%3E%3C/svg%3E");
    background-size: auto, 48px 48px;
}
.pa-hero-banner.is-split { text-align: left; padding: clamp(24px, 3.8vw, 40px) clamp(20px, 3.5vw, 36px); }
.pa-hero-split { position: relative; z-index: 2; display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr); gap: 24px; align-items: center; }
.pa-hero-banner.is-split .pa-hero-content { text-align: left; }
.pa-hero-banner.is-split .pa-hero-lead { margin: 0 0 16px; max-width: 100%; }
.pa-hero-banner.is-split .pa-hero-actions { justify-content: flex-start; }
.pa-hero-split-media { margin: 0; display: flex; flex-direction: column; gap: 6px; }
.pa-hero-media-frame { position: relative; border-radius: 14px; overflow: hidden; border: 2px solid rgba(229, 193, 88, 0.65); background: #012119; box-shadow: 0 16px 32px rgba(0, 0, 0, 0.3); aspect-ratio: 16 / 10; }
.pa-hero-media-frame img { width: 100%; height: 100%; object-fit: cover; display: block; }
.pa-hero-media-badge { position: absolute; top: 10px; right: 10px; padding: 4px 10px; border-radius: 999px; background: rgba(2, 44, 34, 0.88); border: 1px solid #E5C158; color: #E5C158; font-size: 10.5px; font-weight: 800; letter-spacing: 0.04em; }
.pa-hero-split-media figcaption { font-size: 11.5px; color: #a7f3d0; text-align: center; }
@media (max-width: 860px) {
    .pa-hero-split { grid-template-columns: 1fr; text-align: center; }
    .pa-hero-banner.is-split .pa-hero-content { text-align: center; }
    .pa-hero-banner.is-split .pa-hero-actions { justify-content: center; }
}
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

/* Grid Bebas 12 Kolom Beranda + Slider Foto + Panel Statistik/Video/Ucapan + Modal Detail Pegawai */
.pa-home-grid-12 {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 20px;
    align-items: stretch;
}
.pa-hg-slot {
    grid-column: var(--pa-gx, 1) / span var(--pa-gw, 12);
    grid-row: var(--pa-gy, auto) / span var(--pa-gh, 1);
    min-width: 0;
    display: flex;
    flex-direction: column;
}
.pa-hg-slot > .pa-section-block,
.pa-hg-slot > .pa-welcome-grid,
.pa-hg-slot > .pa-grid-panel,
.pa-hg-slot > .pa-hg-fullh {
    margin-bottom: 0 !important;
    height: 100%;
    box-sizing: border-box;
}
.pa-hg-slot .pa-sched-hub { height: 100%; display: flex; flex-direction: column; }
.pa-hg-slot .pa-sched-panel { flex: 1; }
.pa-hg-narrow .pa-sched-cards,
.pa-hg-medium .pa-sched-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.pa-hg-narrow .pa-sched-kpi-strip { grid-template-columns: 1fr; }
.pa-hg-medium .pa-news-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.pa-hg-narrow .pa-news-grid { grid-template-columns: 1fr; }
.pa-hg-narrow .pa-welcome-grid,
.pa-hg-medium .pa-welcome-grid { grid-template-columns: 1fr; }
.pa-services-grid-compact { grid-template-columns: repeat(2, minmax(0, 1fr)); }
@media (max-width: 960px) {
    .pa-home-grid-12 { grid-template-columns: 1fr; gap: 18px; }
    .pa-hg-slot { grid-column: 1 / -1 !important; grid-row: auto !important; }
    .pa-hg-medium .pa-sched-cards,
    .pa-hg-medium .pa-news-grid { grid-template-columns: 1fr; }
}

.pa-hero-slider { position: relative; border-radius: 20px; overflow: hidden; background: linear-gradient(125deg, #033022 0%, #05583f 60%, #066c4e 100%); color: #fff; min-height: 300px; box-shadow: 0 18px 40px -18px rgba(2, 44, 34, 0.35); border-bottom: 4px solid var(--pa-gold, #C9A227); }
.pa-hs-slide { position: relative; min-height: 300px; display: flex; align-items: center; }
.pa-hs-slide[hidden] { display: none !important; }
.pa-hs-bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.35; }
.pa-hs-overlay { position: relative; z-index: 2; padding: 38px 34px 52px; max-width: 820px; margin: 0 auto; text-align: center; }
.pa-hs-controls { position: absolute; left: 0; right: 0; bottom: 14px; z-index: 3; display: flex; align-items: center; justify-content: center; gap: 12px; }
.pa-hs-nav { width: 32px; height: 32px; border-radius: 50%; border: 1px solid rgba(255,255,255,.35); background: rgba(2,44,34,.62); color: #fff; font-size: 18px; line-height: 1; cursor: pointer; }
.pa-hs-nav:hover { background: #C9A227; color: #022c22; }
.pa-hs-dots { display: inline-flex; gap: 6px; }
.pa-hs-dot { width: 9px; height: 9px; border-radius: 50%; border: 0; padding: 0; background: rgba(255,255,255,.45); cursor: pointer; transition: width .16s, background-color .16s; }
.pa-hs-dot.is-active { width: 22px; border-radius: 999px; background: #E5C158; }

.pa-grid-panel { background: var(--pa-surface, #ffffff); border: 1.5px solid #d5e6dc; border-top: 4px solid var(--pa-gold, #C9A227); border-radius: 18px; padding: 18px 20px; box-shadow: 0 10px 26px -14px rgba(2, 44, 34, 0.16); text-align: left; display: flex; flex-direction: column; gap: 14px; }
.pa-gp-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; border-bottom: 1px solid #e6f0eb; padding-bottom: 10px; }
.pa-gp-title { margin: 0 !important; font-size: 16px !important; font-weight: 800 !important; color: #043927 !important; min-width: 0; overflow-wrap: anywhere; }
.pa-gp-more { font-size: 12px; font-weight: 700; color: #056e4f !important; text-decoration: none; white-space: nowrap; }
.pa-gp-more:hover { text-decoration: underline; }
.pa-stats-mini-kpis { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.pa-smk { padding: 10px 12px; border-radius: 12px; background: #f4faf7; border: 1px solid #d5e6dc; }
.pa-smk span { display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; }
.pa-smk strong { display: block; font-size: 20px; font-weight: 900; color: #043927; margin-top: 2px; }
.pa-smk-accent { background: linear-gradient(135deg, #043927, #056e4f); border-color: #043927; }
.pa-smk-accent span { color: #a7f3d0; }
.pa-smk-accent strong { color: #E5C158; }
.pa-stats-mini-bars { display: flex; flex-direction: column; gap: 10px; }
.pa-smb-top { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px; color: #334155; }
.pa-ann-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
.pa-ann-tab { padding: 5px 12px; border-radius: 999px; border: 1px solid #cbd5e1; background: #f8fafc; color: #334155; font-size: 12px; font-weight: 700; cursor: pointer; }
.pa-ann-tab.is-active { background: #056e4f; border-color: #056e4f; color: #fff; }
.pa-media-grid, .pa-ucapan-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
.pa-video-card, .pa-ucapan-card { border: 1px solid #d5e6dc; border-radius: 12px; overflow: hidden; background: #f8fbf9; display: flex; flex-direction: column; }
.pa-video-embed { position: relative; aspect-ratio: 16 / 9; background: #022c22; }
.pa-video-embed iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
.pa-video-link-box { display: flex; align-items: center; justify-content: center; min-height: 120px; background: #043927; color: #E5C158 !important; font-weight: 800; text-decoration: none; }
.pa-video-body { padding: 10px 12px; }
.pa-video-body strong { display: block; font-size: 13.5px; color: #043927; }
.pa-video-body p { margin: 4px 0 0; font-size: 12px; color: #475569; }
.pa-ucapan-card { padding: 14px; gap: 8px; border-left: 4px solid #056e4f; }
.pa-ucapan-card.is-duka { border-left-color: #334155; background: #f1f5f9; }
.pa-ucapan-badge { align-self: flex-start; padding: 2px 9px; border-radius: 999px; background: #ecfdf5; color: #043927; font-size: 11px; font-weight: 800; }
.pa-ucapan-card.is-duka .pa-ucapan-badge { background: #e2e8f0; color: #1e293b; }
.pa-ucapan-img { width: 100%; max-height: 150px; object-fit: cover; border-radius: 8px; }
.pa-ucapan-card strong { font-size: 14px; color: #0f172a; }
.pa-ucapan-card p { margin: 0; font-size: 12.5px; color: #475569; line-height: 1.45; }
.pa-ucapan-card small { font-size: 11.5px; color: #64748b; font-weight: 600; }
.pa-custom-body { font-size: 13.5px; line-height: 1.6; color: #334155; }
.pa-peg-detail-btn {
    inline-size: 100%;
    padding: 9px 14px;
    border-radius: 10px;
    border: 1px solid rgba(5, 110, 79, 0.28);
    background: #f3faf6;
    color: #043927;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease;
}
.pa-peg-detail-btn:hover,
.pa-peg-detail-btn:focus-visible { background: #056e4f; color: #ffffff; border-color: #056e4f; }
dialog.pa-peg-dlg { border: 1px solid #cbd5e1; border-block-start: 4px solid var(--pa-gold, #C9A227); border-radius: 18px; padding: 0; inline-size: min(540px, 94vw); max-block-size: 86dvh; box-shadow: 0 24px 64px rgba(2, 44, 34, 0.34); background: #fff; color: #0f172a; }
dialog.pa-peg-dlg[open] { display: flex; flex-direction: column; }
dialog.pa-peg-dlg:modal { overscroll-behavior: contain; }
dialog.pa-peg-dlg::backdrop { background: rgba(2, 44, 34, 0.56); backdrop-filter: blur(3px); overflow: hidden; overscroll-behavior: contain; }
.pa-peg-dlg-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 20px; background: linear-gradient(135deg, #022c22, #056e4f); color: #fff; }
.pa-peg-dlg-head h1 { margin: 0 !important; font-size: 16.5px !important; font-weight: 800 !important; color: #fff !important; border: 0 !important; padding: 0 !important; line-height: 1.35 !important; }
.pa-peg-dlg-close { inline-size: 32px; block-size: 32px; border-radius: 9px; border: 1px solid rgba(255,255,255,.3); background: rgba(255,255,255,.12); color: #fff; cursor: pointer; font-weight: 800; flex-shrink: 0; }
.pa-peg-dlg-body { padding: 20px; overflow-y: auto; scrollbar-gutter: stable; display: flex; flex-direction: column; gap: 14px; font-size: 13.5px; }
.pa-peg-dlg-row { display: flex; align-items: center; gap: 16px; }
.pa-peg-dlg-avatar { inline-size: 74px; block-size: 74px; aspect-ratio: 1 / 1; border-radius: 16px; object-fit: cover; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; background: linear-gradient(135deg, #022c22, #056e4f); color: #E5C158; font-weight: 900; font-size: 24px; border: 2px solid rgba(201, 162, 39, 0.45); box-shadow: 0 6px 16px rgba(2, 44, 34, 0.16); }
.pa-peg-dlg-kv { display: grid; grid-template-columns: minmax(115px, 140px) minmax(0, 1fr); gap: 8px 14px; font-size: 13px; border-block-start: 1px solid #e2e8f0; padding-block-start: 14px; margin: 0; }
.pa-peg-dlg-kv dt { font-weight: 700; color: #475569; }
.pa-peg-dlg-kv dd { margin: 0; color: #0f172a; font-variant-numeric: tabular-nums; overflow-wrap: break-word; }
html[data-pa-theme="dark"] .pa-peg-detail-btn { background: rgba(16, 185, 129, 0.12); border-color: rgba(110, 231, 183, 0.3); color: #a7f3d0; }
html[data-pa-theme="dark"] .pa-peg-detail-btn:hover,
html[data-pa-theme="dark"] .pa-peg-detail-btn:focus-visible { background: #056e4f; color: #ffffff; border-color: #10b981; }
html[data-pa-theme="dark"] .pa-peg-dlg-kv { border-block-start-color: #2c4637; }
html[data-pa-theme="dark"] .pa-grid-panel,
html[data-pa-theme="dark"] dialog.pa-peg-dlg { background: #14241c; border-color: #355241; color: #e6f4ee; }
html[data-pa-theme="dark"] .pa-gp-head { border-color: #2c4637; }
html[data-pa-theme="dark"] .pa-gp-title,
html[data-pa-theme="dark"] .pa-smk strong,
html[data-pa-theme="dark"] .pa-video-body strong,
html[data-pa-theme="dark"] .pa-ucapan-card strong { color: #E5C158 !important; }
html[data-pa-theme="dark"] .pa-smk,
html[data-pa-theme="dark"] .pa-video-card,
html[data-pa-theme="dark"] .pa-ucapan-card { background: #182a20; border-color: #355241; }
html[data-pa-theme="dark"] .pa-smk span,
html[data-pa-theme="dark"] .pa-smb-top,
html[data-pa-theme="dark"] .pa-video-body p,
html[data-pa-theme="dark"] .pa-ucapan-card p,
html[data-pa-theme="dark"] .pa-custom-body,
html[data-pa-theme="dark"] .pa-peg-dlg-kv dt,
html[data-pa-theme="dark"] .pa-peg-dlg-kv dd { color: #cbd5e1; }
html[data-pa-theme="dark"] .pa-ann-tab { background: #182a20; border-color: #355241; color: #cbd5e1; }
html[data-pa-theme="dark"] .pa-ann-tab.is-active { background: #C9A227; border-color: #C9A227; color: #022c22; }

/* Dashboard Jadwal Sidang Modern + Live Slide SIPP di Beranda */
.pa-sched-below-fold,
.pa-sipp-slide-section {
    content-visibility: auto;
    contain-intrinsic-size: auto none auto 560px;
}
.pa-sched-hub,
.pa-sipp-card {
    background: var(--pa-surface, #ffffff);
    border: 1.5px solid #d5e6dc;
    border-top: 4px solid var(--pa-gold, #C9A227);
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 14px 34px -14px rgba(2, 44, 34, 0.22);
    text-align: left;
}
.pa-sched-head,
.pa-sipp-card-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px 16px;
    padding: 15px 20px;
    background: linear-gradient(125deg, #033022 0%, #05583f 58%, #066c4e 100%);
    color: #ffffff;
    border-bottom: 1px solid rgba(229, 193, 88, 0.35);
}
.pa-sched-head-left {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}
.pa-sched-badges {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}
.pa-sched-date-pill {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.22);
    color: #e6f4ee;
    font-size: 11.5px;
    font-weight: 700;
}
.pa-sched-title {
    margin: 0 !important;
    font-size: clamp(1.05rem, 1.8vw, 1.25rem) !important;
    font-weight: 800 !important;
    color: #ffffff !important;
    line-height: 1.25 !important;
}
.pa-sched-head-right {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}
.pa-sched-tabs {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px;
    border-radius: 999px;
    background: rgba(2, 36, 27, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.18);
}
.pa-sched-tab {
    appearance: none;
    border: 0;
    background: transparent;
    color: #d1fae5;
    padding: 6px 13px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.16s ease, color 0.16s ease;
}
.pa-sched-tab:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.1);
}
.pa-sched-tab.is-active,
.pa-sched-tab[aria-selected="true"] {
    background: #ffffff;
    color: #043927;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
}
.pa-sched-agenda-panel {
    padding: 18px 20px 20px;
}
.pa-sched-kpi-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}
@media (max-width: 720px) {
    .pa-sched-kpi-strip { grid-template-columns: 1fr; }
}
.pa-sched-kpi {
    padding: 11px 14px;
    border-radius: 12px;
    background: #f4faf7;
    border: 1px solid #d5e6dc;
    border-left: 3.5px solid var(--pa-gold, #C9A227);
}
.pa-sched-kpi span {
    display: block;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #475569;
}
.pa-sched-kpi strong {
    display: block;
    margin-top: 3px;
    font-size: 14px;
    font-weight: 800;
    color: #043927;
}
.pa-sched-cards {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}
@media (max-width: 920px) {
    .pa-sched-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 580px) {
    .pa-sched-cards { grid-template-columns: 1fr; }
}
.pa-sched-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 13px 14px;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid #d5e6dc;
    box-shadow: 0 3px 10px rgba(4, 57, 39, 0.04);
}
.pa-sched-item-top {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.pa-sched-time {
    display: inline-flex;
    align-items: center;
    padding: 2px 9px;
    border-radius: 999px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #043927;
    font-size: 11.5px;
    font-weight: 800;
}
.pa-sched-room {
    font-size: 11.5px;
    font-weight: 700;
    color: #6e5204;
    background: #fef9c3;
    border: 1px solid #fde047;
    padding: 2px 8px;
    border-radius: 999px;
}
.pa-sched-case {
    font-size: 14px;
    font-weight: 800;
    color: #043927;
    word-break: break-word;
}
.pa-sched-agenda {
    font-size: 12.5px;
    color: #475569;
    line-height: 1.4;
}
.pa-sched-empty {
    margin: 0;
    padding: 14px 16px;
    border-radius: 10px;
    background: #f8fbf9;
    border: 1px dashed #cbd5e1;
    color: #475569;
    font-size: 13px;
}
.pa-sched-foot-links {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}
html[data-pa-theme="dark"] .pa-sched-hub {
    background: #14241c;
    border-color: #355241;
}
html[data-pa-theme="dark"] .pa-sched-kpi,
html[data-pa-theme="dark"] .pa-sched-item {
    background: #182a20;
    border-color: #355241;
}
html[data-pa-theme="dark"] .pa-sched-kpi span,
html[data-pa-theme="dark"] .pa-sched-agenda,
html[data-pa-theme="dark"] .pa-sched-empty {
    color: #cbd5e1;
}
html[data-pa-theme="dark"] .pa-sched-kpi strong,
html[data-pa-theme="dark"] .pa-sched-case {
    color: #E5C158;
}
html[data-pa-theme="dark"] .pa-sched-time {
    background: #0d2a1f;
    border-color: #1f5c44;
    color: #6ee7b7;
}
html[data-pa-theme="dark"] .pa-sched-room {
    background: #2b2310;
    border-color: #5c4818;
    color: #fde68a;
}
html[data-pa-theme="dark"] .pa-sched-empty {
    background: #101e17;
    border-color: #355241;
}
.pa-sipp-head-left {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.pa-sipp-live-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(201, 162, 39, 0.18);
    border: 1px solid rgba(229, 193, 88, 0.62);
    color: #E5C158;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    white-space: nowrap;
}
.pa-sipp-live-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #4ade80;
    box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.28);
    flex-shrink: 0;
}
.pa-sipp-head-title {
    margin: 0 !important;
    font-size: 15px !important;
    font-weight: 800 !important;
    color: #ffffff !important;
    line-height: 1.3 !important;
}
.pa-sipp-head-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.pa-sipp-btn-sec {
    display: inline-flex;
    align-items: center;
    padding: 7px 13px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.28);
    color: #ffffff !important;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none !important;
    transition: background-color 0.16s ease, border-color 0.16s ease;
}
.pa-sipp-btn-sec:hover,
.pa-sipp-btn-sec:focus-visible {
    background: rgba(255, 255, 255, 0.22);
    border-color: rgba(229, 193, 88, 0.72);
}
.pa-sipp-btn-open {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 14px;
    border-radius: 999px;
    background: linear-gradient(135deg, #C9A227 0%, #E5C158 100%);
    color: #022c22 !important;
    font-size: 12.5px;
    font-weight: 800;
    text-decoration: none !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
    transition: transform 0.16s ease, filter 0.16s ease;
}
.pa-sipp-btn-open:hover,
.pa-sipp-btn-open:focus-visible {
    transform: translateY(-1px);
    filter: brightness(1.06);
}
.pa-sipp-frame-wrap {
    position: relative;
    width: 100%;
    background: #ffffff;
    color-scheme: only light;
}
.pa-sipp-frame {
    display: block;
    width: 100%;
    min-height: 400px;
    border: 0;
    background: #ffffff;
    color-scheme: only light;
}
.pa-sipp-card-foot {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 10px 20px;
    background: #f8fbf9;
    border-top: 1px solid #d5e6dc;
    font-size: 12px;
    color: #475569;
}
.pa-sipp-card-foot a {
    color: #056e4f !important;
    font-weight: 700;
    text-decoration: none;
}
.pa-sipp-card-foot a:hover,
.pa-sipp-card-foot a:focus-visible {
    text-decoration: underline;
}
html[data-pa-theme="dark"] .pa-sipp-card {
    background: #14241c;
    border-color: #355241;
}
html[data-pa-theme="dark"] .pa-sipp-card-foot {
    background: #101e17;
    border-top-color: #2c4637;
    color: #cbd5e1;
}
html[data-pa-theme="dark"] .pa-sipp-card-foot strong {
    color: #e6f4ee;
}
html[data-pa-theme="dark"] .pa-sipp-card-foot a {
    color: #E5C158 !important;
}
@media (max-width: 640px) {
    .pa-sipp-card-head { padding: 12px 14px; }
    .pa-sipp-card-foot { padding: 10px 14px; }
}

/* Strip Tenang Access CCTV Online (ACO) Badilag di Bagian Bawah Halaman */
.pa-aco-banner {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px 16px;
    padding: 10px 18px;
    margin: 22px 0 0;
    border-radius: 12px;
    background: #f8fbf9;
    border: 1px solid #d5e6dc;
    box-shadow: none;
    text-align: left;
    transition: border-color 0.16s ease, background-color 0.16s ease;
}
.pa-aco-banner:hover,
.pa-aco-banner:focus-within { border-color: #9ec5b3; background: #f2f9f5; }
.pa-aco-main {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    min-width: 0;
    flex: 1 1 280px;
}
.pa-aco-dot { width: 7px; height: 7px; border-radius: 50%; background: #16a34a; flex-shrink: 0; }
.pa-aco-ico { width: 15px; height: 15px; color: #056e4f; flex-shrink: 0; }
.pa-aco-text { font-size: 12.5px; line-height: 1.45; color: #334155; }
.pa-aco-text strong { color: #043927; font-weight: 700; }
.pa-aco-sep { color: #94a3b8; margin: 0 2px; }
.pa-aco-desc { color: #64748b; }
.pa-aco-btn {
    display: inline-flex;
    align-items: center;
    padding: 5px 13px;
    border-radius: 999px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #056e4f !important;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none !important;
    white-space: nowrap;
    transition: border-color 0.15s ease, background-color 0.15s ease, color 0.15s ease;
}
.pa-aco-btn:hover,
.pa-aco-btn:focus-visible { background: #ecfdf5; border-color: #056e4f; color: #043927 !important; }
html[data-pa-theme="dark"] .pa-aco-banner { background: #111e18; border-color: #264234; }
html[data-pa-theme="dark"] .pa-aco-banner:hover,
html[data-pa-theme="dark"] .pa-aco-banner:focus-within { background: #15251e; border-color: #3a6651; }
html[data-pa-theme="dark"] .pa-aco-ico { color: #E5C158; }
html[data-pa-theme="dark"] .pa-aco-text { color: #cbd5e1; }
html[data-pa-theme="dark"] .pa-aco-text strong { color: #e6f4ee; }
html[data-pa-theme="dark"] .pa-aco-desc { color: #94a3b8; }
html[data-pa-theme="dark"] .pa-aco-btn { background: #162a21; border-color: #2f5241; color: #E5C158 !important; }
html[data-pa-theme="dark"] .pa-aco-btn:hover,
html[data-pa-theme="dark"] .pa-aco-btn:focus-visible { background: #1e382b; border-color: #E5C158; }
.pa-welcome-card { padding: 22px; border-radius: 16px; background: #f4faf7; border: 1.5px solid #d5e6dc; border-left: 5px solid #056e4f; transition: transform 0.18s ease, border-color 0.18s ease; }
.pa-welcome-card:hover { transform: translateY(-2px); border-color: #C9A227; }
.pa-wc-kicker { display: inline-block; padding: 3px 10px; border-radius: 999px; background: #056e4f; color: #fff; font-size: 11px; font-weight: 800; margin-bottom: 10px; }
.pa-wc-profile { display: flex; align-items: center; gap: 14px; margin-bottom: 12px; }
.pa-wc-photo { width: 72px; height: 84px; border-radius: 12px; object-fit: cover; border: 2px solid var(--pa-gold, #C9A227); background: #043927; flex-shrink: 0; box-shadow: 0 6px 16px rgba(2, 44, 34, 0.15); }
.pa-wc-headtext { min-width: 0; flex: 1 1 0%; }
.pa-welcome-card h3 { margin: 0 0 3px; color: #043927; font-size: 1.12rem; font-weight: 800; }
.pa-wc-role { display: block; font-size: 12px; font-weight: 700; color: #056e4f; }
.pa-wc-motto { margin: 4px 0 0; font-size: 12px; font-style: italic; color: #6e5204; }
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
.pa-news-card { position: relative; z-index: 1; display: flex; flex-direction: column; padding: 0; border-radius: 14px; background: #ffffff; border: 1px solid #d5e6dc; box-shadow: 0 4px 14px rgba(4, 57, 39, 0.05); transition: transform 0.18s ease, border-color 0.18s ease; }
.pa-news-card:hover,
.pa-news-card:focus-within { transform: translateY(-3px); border-color: #C9A227; z-index: 35; }
.pa-news-thumb { display: block; aspect-ratio: 16 / 10; border-radius: 13px 13px 0 0; overflow: hidden; background: linear-gradient(135deg, #e7f4ee 0%, #fef9e7 100%); border-bottom: 1px solid #e2ece6; }
.pa-news-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.28s ease; }
.pa-news-card:hover .pa-news-thumb img { transform: scale(1.04); }
.pa-news-thumb-ph { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #056e4f; opacity: 0.65; }
.pa-news-body { padding: 14px 16px 16px; display: flex; flex-direction: column; flex: 1; justify-content: space-between; gap: 7px; }
.pa-news-date { font-size: 11.5px; font-weight: 700; color: #6e5204; }
.pa-news-card h3 { margin: 0; font-size: 15px; line-height: 1.4; }
.pa-news-card h3 a { color: #043927 !important; text-decoration: none !important; }
.pa-news-card h3 a:hover { color: #056e4f !important; }
.pa-news-card p { margin: 0; font-size: 12.5px; line-height: 1.5; color: #4b5563; }
.pa-news-more { color: #056e4f !important; font-size: 12px; font-weight: 800; text-decoration: none !important; }
.pa-feed-thumb { width: 38px; height: 38px; border-radius: 8px; overflow: hidden; flex-shrink: 0; background: #ecfdf5; border: 1px solid #cce6da; display: inline-flex; align-items: center; justify-content: center; }
.pa-feed-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.pa-feed-thumb-ph { font-size: 15px; color: #056e4f; }
.pa-image-banner-box { margin: 0; display: flex; flex-direction: column; gap: 8px; }
.pa-image-banner-box img { width: 100%; border-radius: 12px; display: block; border: 1px solid #d5e6dc; }
.pa-image-banner-box figcaption { font-size: 12.5px; color: #475569; text-align: center; }
.pa-photo-slider { position: relative; border-radius: 14px; overflow: hidden; background: #022c22; }
.pa-ps-slide { position: relative; height: var(--pa-ps-h, 360px); margin: 0; }
.pa-ps-slide[hidden] { display: none !important; }
.pa-ps-slide img { width: 100%; height: 100%; object-fit: cover; display: block; }
.pa-ps-caption { position: absolute; left: 0; right: 0; bottom: 0; padding: 22px 18px 44px; background: linear-gradient(transparent, rgba(2, 44, 34, 0.86)); color: #fff; font-weight: 700; font-size: 14px; }
.pa-dirput-scope { flex-basis: 100%; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #475569; cursor: pointer; }
html[data-pa-theme="dark"] .pa-feed-thumb { background: #182a20; border-color: #2c4c3b; }
html[data-pa-theme="dark"] .pa-news-thumb { background: #111e18; border-color: #2c4637; }
html[data-pa-theme="dark"] .pa-news-thumb-ph { color: #E5C158; }
html[data-pa-theme="dark"] .pa-image-banner-box figcaption,
html[data-pa-theme="dark"] .pa-dirput-scope { color: #cbd5e1; }
.pa-split-section { display: grid; grid-template-columns: 360px 1fr; gap: 20px; align-items: stretch; }
@media (max-width: 880px) { .pa-split-section { grid-template-columns: 1fr; } }
.pa-starter-survey { padding: 18px; border-radius: 16px; background: linear-gradient(155deg, #065f46 0%, #044e3a 52%, #022c22 100%); border: 1.5px solid #C9A227; color: #fff; text-align: left; display: flex; flex-direction: column; justify-content: space-between; }
.pa-starter-survey .survey-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 6px; margin-bottom: 8px; font-size: 10.5px; font-weight: 800; }
.pa-starter-survey .survey-pill { padding: 3px 9px; border-radius: 999px; background: rgba(229, 193, 88, 0.18); border: 1px solid #C9A227; color: #E5C158; }
.pa-starter-survey .survey-period-badge { padding: 3px 9px; border-radius: 999px; background: #d1fae5; color: #022c22; }
.pa-starter-survey h3 { margin: 0 0 10px; color: #fff; font-size: 16.5px; font-weight: 800; }
.pa-starter-survey .survey-slide { display: none; }
.pa-starter-survey .survey-slide.is-active { display: block; }
.pa-starter-survey .survey-cards { display: flex; flex-direction: column; gap: 8px; }
.pa-starter-survey .survey-card { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 8px; padding: 10px 12px; border-radius: 10px; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.18); color: #fff !important; text-decoration: none !important; font-size: 12px; }
.pa-starter-survey .survey-card b { white-space: nowrap; }
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
    html:not([data-pa-contrast="high"]) .pa-hero-banner:not(.is-split) .pa-hero-lead {
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
    html:not([data-pa-contrast="high"]) .pa-hero-banner:not(.is-split) .pa-hero-title:hover + .pa-hero-lead,
    html:not([data-pa-contrast="high"]) .pa-hero-banner:not(.is-split) .pa-hero-kicker:hover ~ .pa-hero-lead {
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
    #pa-aco-float[data-pa-tip]::after { left: auto; right: 8px; transform: translate(0, 4px); }
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
    #pa-aco-float[data-pa-tip]:hover::after,
    #pa-aco-float[data-pa-tip]:focus-visible::after,
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
    top: var(--pa-wpbar-h, 0px);
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
#ast-scroll-top,
.ast-scroll-top-icon { display: none !important; }
#pa-btn-top {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 99989;
    width: 44px !important;
    height: 44px !important;
    min-width: 44px !important;
    padding: 0 !important;
    margin: 0 !important;
    box-sizing: border-box !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 50% !important;
    border: 1.5px solid #C9A227 !important;
    background: #043927 !important;
    color: #E5C158 !important;
    font: 800 17px/1 'Inter', system-ui, sans-serif !important;
    cursor: pointer;
    box-shadow: 0 8px 22px rgba(2, 44, 34, 0.28);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease, background-color 0.15s ease;
}
#pa-btn-top.is-visible { opacity: 1; visibility: visible; transform: translateY(0); }
#pa-btn-top:hover { background: #022c22 !important; color: #ffffff !important; }
#pa-aco-float {
    position: fixed;
    right: 0;
    bottom: 76px;
    z-index: 99988;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 4px 5px 8px;
    border-radius: 12px 0 0 12px;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(5, 110, 79, 0.16);
    border-right: 0;
    box-shadow: -3px 6px 18px rgba(2, 44, 34, 0.14);
    text-decoration: none !important;
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}
#pa-aco-float.pa-aco-pos-middle_right { top: 50%; bottom: auto; transform: translateY(-50%); }
#pa-aco-float.pa-aco-pos-top_right { top: 112px; bottom: auto; }
#pa-aco-float.pa-aco-pos-bottom_left { left: 0; right: auto; bottom: 76px; padding: 5px 8px 5px 4px; border-radius: 0 12px 12px 0; border-right: 1px solid rgba(5, 110, 79, 0.16); border-left: 0; box-shadow: 3px 6px 18px rgba(2, 44, 34, 0.14); }
#pa-aco-float.pa-aco-pos-middle_left { left: 0; right: auto; top: 50%; bottom: auto; transform: translateY(-50%); padding: 5px 8px 5px 4px; border-radius: 0 12px 12px 0; border-right: 1px solid rgba(5, 110, 79, 0.16); border-left: 0; box-shadow: 3px 6px 18px rgba(2, 44, 34, 0.14); }
#pa-aco-float.is-custom-pos { border-radius: 12px; border: 1px solid rgba(5, 110, 79, 0.22); padding: 6px 8px; box-shadow: 0 8px 22px rgba(2, 44, 34, 0.18); transform: none !important; }
#pa-aco-float.is-draggable { cursor: grab; }
#pa-aco-float.is-dragging { cursor: grabbing; opacity: 0.9; transition: none !important; }
#pa-aco-float:hover,
#pa-aco-float:focus-visible {
    border-color: rgba(201, 162, 39, 0.72);
    box-shadow: 0 10px 24px rgba(2, 44, 34, 0.22);
}
.pa-aco-float-img {
    display: block;
    width: 56px;
    height: 50px;
    object-fit: contain;
    pointer-events: none;
}
#pa-aco-float.pa-aco-sz-sm .pa-aco-float-img { width: 44px; height: 40px; }
#pa-aco-float.pa-aco-sz-lg .pa-aco-float-img { width: 68px; height: 60px; }
.pa-aco-float-img[hidden] { display: none !important; }
.pa-aco-float-static {
    display: none;
    align-items: center;
    gap: 4px;
    padding: 5px 8px 5px 4px;
    color: #043927;
    font: 800 11px/1 'Inter', system-ui, sans-serif;
    letter-spacing: 0.03em;
}
.pa-aco-float-static.is-fallback { display: inline-flex; }
@media (max-width: 640px) {
    #pa-aco-float.pa-aco-pos-bottom_right,
    #pa-aco-float.pa-aco-pos-bottom_left { bottom: 70px; padding: 4px 4px 4px 6px; }
    .pa-aco-float-img { width: 46px; height: 41px; }
}
html[data-pa-theme="dark"] #pa-aco-float {
    background: rgba(248, 252, 250, 0.95);
    border-color: rgba(229, 193, 88, 0.45);
    box-shadow: -4px 8px 22px rgba(0, 0, 0, 0.38);
}
@media (prefers-reduced-motion: reduce) {
    .pa-hero-banner::before, .pa-hero-banner::after, .pa-hero-content { transform: none !important; transition: none !important; }
    * { scroll-behavior: auto !important; }
}
@media print {
    #pa-a11y-dock, #pa-btn-top, #pa-aco-float, #pa-scroll-progress, .pa-emergency-bar, .pa-shell-topbar, .pa-shell-navwrap, #ast-desktop-header, .pa-inner-sidebar { display: none !important; }
}

/* Footer 4 Kolom Resmi + Statistik Pengunjung */
.pa-shell-footer { background: #022c22; color: #d1fae5; border-top: 4px solid #C9A227; padding: 34px 16px 24px; font-size: 13px; }
.pa-footer-grid { max-width: 1180px; margin: 0 auto 22px; display: grid; grid-template-columns: 1.35fr 1fr 1fr 1.1fr; gap: 24px; }
@media (max-width: 900px) { .pa-footer-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 540px) { .pa-footer-grid { grid-template-columns: 1fr; } }
.pa-footer-col h4 { margin: 0 0 10px; color: #E5C158; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.03em; }
.pa-footer-brand-head { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.pa-footer-brand-head h4 { margin: 0; }
.pa-footer-logo-img { height: 38px; width: auto; max-width: 110px; border-radius: 8px; background: rgba(255, 255, 255, 0.96); padding: 3px 5px; object-fit: contain; flex-shrink: 0; }
.pa-footer-col p { margin: 0 0 8px; line-height: 1.55; color: #e2e8f0; font-size: 12.5px; }
.pa-footer-col ul { list-style: none; margin: 0; padding: 0; }
.pa-footer-col li { margin-bottom: 6px; }
.pa-footer-col a { color: #d1fae5; text-decoration: none; font-size: 12.5px; }
.pa-footer-col a:hover { color: #E5C158; text-decoration: underline; }
.pa-footer-vis-box { margin-top: 10px; padding: 10px 12px; border-radius: 10px; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(229, 193, 88, 0.25); font-size: 12px; }
.pa-footer-vis-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
.pa-footer-vis-row:last-child { margin-bottom: 0; }
.pa-footer-vis-row strong { color: #E5C158; font-variant-numeric: tabular-nums; }
.pa-shell-footer-bottom { max-width: 1180px; margin: 0 auto; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.14); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px 16px; font-size: 12px; }
.pa-footer-credit-line { display: inline-flex; flex-wrap: wrap; align-items: center; gap: 8px; max-width: 100%; color: #d1fae5; line-height: 1.5; }
.pa-footer-ver-wrap { position: relative; display: inline-flex; align-items: center; }
.pa-shell-footer .pa-footer-ver-trigger { display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 999px; background: rgba(229, 193, 88, 0.14); border: 1px solid rgba(229, 193, 88, 0.45); color: #E5C158; font-size: 11px; font-weight: 800; letter-spacing: 0.02em; font-variant-numeric: tabular-nums; text-decoration: none; transition: background-color 0.16s ease, color 0.16s ease, border-color 0.16s ease; }
.pa-shell-footer .pa-footer-ver-trigger:hover,
.pa-shell-footer .pa-footer-ver-trigger:focus-visible { background: #E5C158; border-color: #E5C158; color: #022c22; text-decoration: none; }
.pa-shell-footer .pa-footer-ver-trigger:focus-visible { outline: 2px solid #ffffff; outline-offset: 2px; }
.pa-footer-credit-popover { position: absolute; left: 0; bottom: 100%; padding-bottom: 10px; width: max-content; max-width: min(350px, calc(100vw - 32px)); opacity: 0; visibility: hidden; pointer-events: none; transform: translateY(6px); transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s ease; z-index: 9999; }
.pa-footer-ver-wrap:hover .pa-footer-credit-popover,
.pa-footer-ver-wrap:focus-within .pa-footer-credit-popover { opacity: 1; visibility: visible; pointer-events: auto; transform: translateY(0); }
.pa-footer-ver-wrap.is-dismissed .pa-footer-credit-popover { opacity: 0 !important; visibility: hidden !important; pointer-events: none !important; }
.pa-footer-credit-card { position: relative; background: #012119; color: #ecfdf5; border: 1px solid #C9A227; border-radius: 12px; padding: 10px 14px; box-shadow: 0 14px 30px rgba(0, 0, 0, 0.36); text-align: left; line-height: 1.45; }
.pa-footer-credit-card::after { content: ''; position: absolute; left: 18px; top: 100%; border-width: 6px 6px 0; border-style: solid; border-color: #C9A227 transparent transparent; }
.pa-footer-credit-main { display: flex; flex-wrap: wrap; align-items: center; gap: 5px; font-size: 12.5px; color: #ffffff; font-weight: 700; }
.pa-heart-ico { color: #fb7185; flex-shrink: 0; display: inline-block; vertical-align: middle; filter: drop-shadow(0 0 6px rgba(251, 113, 133, 0.45)); animation: paHeartBeat 1.8s ease-in-out infinite; }
@keyframes paHeartBeat { 0%, 100% { transform: scale(1); } 18% { transform: scale(1.18); } 34% { transform: scale(0.98); } 50% { transform: scale(1.14); } }
.pa-footer-credit-meta { margin-top: 6px; padding-top: 6px; border-top: 1px solid rgba(255, 255, 255, 0.12); display: flex; flex-wrap: wrap; align-items: center; gap: 6px; font-size: 11px; color: #cbd5e1; }
.pa-footer-pop-ver { display: inline-block; padding: 1px 7px; border-radius: 999px; background: rgba(201, 162, 39, 0.22); border: 1px solid rgba(229, 193, 88, 0.45); color: #fde68a; font-weight: 700; }
html[data-pa-nomotion="1"] .pa-heart-ico,
html[data-pa-nomotion="1"] .pa-footer-credit-popover { animation: none !important; transition: none !important; }
@media (prefers-reduced-motion: reduce) { .pa-heart-ico { animation: none; } .pa-footer-credit-popover { transition: none; } }
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
html[data-pa-theme="dark"] .pa-mega-panel,
html[data-pa-theme="dark"] .pa-mega-head,
html[data-pa-theme="dark"] .pa-mega-body,
html[data-pa-theme="dark"] .pa-ticker-bar,
html[data-pa-theme="dark"] .pa-breadcrumb,
html[data-pa-theme="dark"] .pa-inner-sidebar,
html[data-pa-theme="dark"] body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu {
    background: #102019 !important;
    border-color: #2c4637 !important;
    color: #e6f4ee !important;
}
html[data-pa-theme="dark"] .pa-mega-foot {
    background: #0b1611 !important;
    border-color: #243f31 !important;
    color: #cbd5e1 !important;
}
html[data-pa-theme="dark"] .pa-mega-head-title,
html[data-pa-theme="dark"] .pa-mega-crumb-cat { color: #E5C158 !important; }
html[data-pa-theme="dark"] .pa-mega-head-hint,
html[data-pa-theme="dark"] .pa-mega-crumb-txt { color: #94a3b8; }
html[data-pa-theme="dark"] .pa-mega-count-pill { background: #162a21; border-color: #2c4637; color: #86efac; }
html[data-pa-theme="dark"] .pa-mega-avail { background: #262010; border-color: #5c4818; color: #fde68a; }
html[data-pa-theme="dark"] .pa-mega-card-link,
html[data-pa-theme="dark"] body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu .pa-mega-card-link {
    background: #162a21 !important;
    border-color: rgba(229, 193, 88, 0.16) !important;
    color: #e6f4ee !important;
}
html[data-pa-theme="dark"] .pa-mega-card-link::after,
html[data-pa-theme="dark"] body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu .pa-mega-card-link::after {
    color: #E5C158;
}
html[data-pa-theme="dark"] .pa-mega-card-link:hover,
html[data-pa-theme="dark"] .pa-mega-card-link:focus-visible,
html[data-pa-theme="dark"] body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu .pa-mega-card-link:hover,
html[data-pa-theme="dark"] body.pa-sinova-classic-shell #ast-desktop-header .main-header-menu .sub-menu .pa-mega-card-link:focus-visible {
    background: #1d382b !important;
    border-color: rgba(229, 193, 88, 0.58) !important;
    color: #E5C158 !important;
    box-shadow: 0 10px 22px -6px rgba(0, 0, 0, 0.45), inset 3px 0 0 #E5C158 !important;
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
html[data-pa-nomotion="1"] #pa-aco-float.pa-aco-is-gif .pa-aco-float-img { display: none !important; }
html[data-pa-nomotion="1"] #pa-aco-float.pa-aco-is-gif .pa-aco-float-static { display: inline-flex; }
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

/* KPI Bar, Inovasi Satker, Penelusuran Perkara SIPP, & Medsos Footer (v2.5) */
.pa-kpi-bar { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
@media (max-width: 860px) { .pa-kpi-bar { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 480px) { .pa-kpi-bar { grid-template-columns: 1fr; } }
.pa-kpi-bar-item { display: flex; flex-direction: column; gap: 3px; padding: 14px 16px; border-radius: 14px; background: var(--pa-surface, #ffffff); border: 1.5px solid #d5e6dc; border-top: 3px solid var(--pa-gold, #C9A227); text-decoration: none !important; text-align: left; box-shadow: 0 4px 12px rgba(2, 44, 34, 0.05); transition: transform 0.16s ease, border-color 0.16s ease; }
.pa-kpi-bar-item:hover { transform: translateY(-2px); border-color: #056e4f; }
.pa-kb-lbl { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: #056e4f; }
.pa-kb-val { font-size: clamp(1.3rem, 2.1vw, 1.65rem); font-weight: 900; color: #043927; line-height: 1.15; font-variant-numeric: tabular-nums; }
.pa-kb-sub { font-size: 11.5px; color: #64748b; }
.pa-kpi-bar-item.is-highlight { background: linear-gradient(135deg, #043927, #056e4f); border-color: #C9A227; }
.pa-kpi-bar-item.is-highlight .pa-kb-lbl { color: #a7f3d0; }
.pa-kpi-bar-item.is-highlight .pa-kb-val { color: #E5C158; }
.pa-kpi-bar-item.is-highlight .pa-kb-sub { color: #d1fae5; }

.pa-inovasi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(235px, 1fr)); gap: 14px; text-align: left; }
.pa-ino-card { display: flex; flex-direction: column; gap: 8px; padding: 16px; border-radius: 14px; background: var(--pa-surface, #ffffff); border: 1.5px solid #d5e6dc; border-bottom: 3px solid var(--pa-gold, #C9A227); box-shadow: 0 6px 16px rgba(2, 44, 34, 0.05); transition: transform 0.16s ease, border-color 0.16s ease; }
.pa-ino-card:hover { transform: translateY(-3px); border-color: #056e4f; }
.pa-ino-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
.pa-ino-ico { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #043927, #056e4f); color: #E5C158; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.pa-ino-ico svg { width: 19px; height: 19px; }
.pa-ino-img { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid #d5e6dc; flex-shrink: 0; }
.pa-ino-badge { padding: 3px 9px; border-radius: 999px; background: #fef9e7; border: 1px solid #f3dfa0; color: #92400e; font-size: 10.5px; font-weight: 800; }
.pa-ino-card h3 { margin: 2px 0 0; font-size: 14.5px; font-weight: 800; color: #043927; line-height: 1.35; overflow-wrap: anywhere; }
.pa-ino-card p { margin: 0; font-size: 12.5px; line-height: 1.5; color: #475569; flex: 1; }
.pa-ino-link { font-size: 12px; font-weight: 800; color: #056e4f !important; text-decoration: none !important; margin-top: 2px; }
.pa-ino-link:hover { text-decoration: underline !important; }

.pa-sipp-tracker { padding: 18px 20px; border-radius: 16px; background: linear-gradient(135deg, #f4faf7 0%, #ffffff 100%); border: 1.5px solid #d5e6dc; border-left: 5px solid var(--pa-gold, #C9A227); text-align: left; display: flex; flex-direction: column; gap: 12px; margin-bottom: 18px; }
.pa-st-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
.pa-st-kicker { display: block; font-size: 10.5px; font-weight: 800; letter-spacing: 0.05em; color: #056e4f; text-transform: uppercase; }
.pa-st-title { margin: 2px 0 0 !important; font-size: 16.5px !important; font-weight: 800 !important; color: #043927 !important; border: 0 !important; padding: 0 !important; }
.pa-st-links { display: flex; flex-wrap: wrap; gap: 6px; }
.pa-st-pill { padding: 5px 11px; border-radius: 999px; background: #ffffff; border: 1px solid #cbd5e1; color: #043927 !important; font-size: 11.5px; font-weight: 700; text-decoration: none !important; transition: background-color 0.15s, border-color 0.15s; }
.pa-st-pill:hover { background: #056e4f; border-color: #056e4f; color: #ffffff !important; }
.pa-st-form { display: flex; flex-wrap: wrap; gap: 8px; }
.pa-st-input { flex: 1 1 260px; min-width: 0; padding: 9px 13px; border-radius: 10px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #0f172a; font-size: 13.5px; }
.pa-st-btn { padding: 9px 18px; border-radius: 10px; border: 0; background: #043927; color: #E5C158; font-weight: 800; font-size: 13px; cursor: pointer; }
.pa-st-btn:hover { background: #056e4f; color: #ffffff; }
.pa-st-results { display: flex; flex-direction: column; gap: 8px; }
.pa-st-results[hidden] { display: none !important; }
.pa-st-res-item { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 12px; border-radius: 10px; background: #ffffff; border: 1px solid #d5e6dc; text-decoration: none !important; color: inherit; }
.pa-st-res-item:hover { border-color: #C9A227; }
.pa-st-res-meta { min-width: 0; flex: 1 1 220px; }
.pa-st-res-meta strong { display: block; font-size: 13.5px; color: #043927; }
.pa-st-res-meta span { font-size: 12px; color: #475569; }
.pa-st-res-tag { padding: 2px 9px; border-radius: 999px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #043927; font-size: 11px; font-weight: 800; white-space: nowrap; }
.pa-st-empty { padding: 10px 12px; border-radius: 10px; background: #fffbeb; border: 1px dashed #fcd34d; font-size: 12.5px; color: #78350f; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; }

.pa-footer-socials { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 12px; }
.pa-footer-soc-btn { display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-width: 32px; height: 32px; padding: 0 8px; border-radius: 8px; background: rgba(255, 255, 255, 0.09); border: 1px solid rgba(229, 193, 88, 0.32); color: #e2e8f0 !important; text-decoration: none !important; font-size: 11.5px; font-weight: 700; transition: background-color 0.15s, border-color 0.15s, color 0.15s; }
.pa-footer-soc-btn:hover { background: #E5C158; border-color: #E5C158; color: #022c22 !important; }
.pa-footer-soc-btn svg { width: 15px; height: 15px; flex-shrink: 0; }

html[data-pa-theme="dark"] .pa-kpi-bar-item:not(.is-highlight),
html[data-pa-theme="dark"] .pa-ino-card,
html[data-pa-theme="dark"] .pa-sipp-tracker,
html[data-pa-theme="dark"] .pa-st-res-item,
html[data-pa-theme="dark"] .pa-st-pill { background: #14241c; border-color: #2c4c3b; color: #e6f4ee !important; }
html[data-pa-theme="dark"] .pa-kb-val,
html[data-pa-theme="dark"] .pa-ino-card h3,
html[data-pa-theme="dark"] .pa-st-title,
html[data-pa-theme="dark"] .pa-st-res-meta strong { color: #E5C158 !important; }
html[data-pa-theme="dark"] .pa-kb-sub,
html[data-pa-theme="dark"] .pa-ino-card p,
html[data-pa-theme="dark"] .pa-st-res-meta span,
html[data-pa-theme="dark"] .pa-wc-role { color: #cbd5e1; }
html[data-pa-theme="dark"] .pa-wc-motto { color: #fde68a; }
html[data-pa-theme="dark"] .pa-st-input { background: #0f1d16; border-color: #355241; color: #f8fafc; }

/* Pencarian Terpadu (Modal Ctrl+K + Full-Site Submit) */
.pa-cmdk-head-ico { width: 17px; height: 17px; color: #E5C158; flex-shrink: 0; }
.pa-cmdk-submit {
    padding: 6px 14px !important;
    border-radius: 999px !important;
    border: none !important;
    background: #C9A227 !important;
    color: #022c22 !important;
    font-size: 12px !important;
    font-weight: 800 !important;
    line-height: 1.2 !important;
    cursor: pointer !important;
    flex-shrink: 0;
}
.pa-cmdk-submit:hover,
.pa-cmdk-submit:focus-visible { background: #E5C158 !important; color: #022c22 !important; }
#pa-cmdk-dialog {
    width: min(660px, 94vw);
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
    margin: 0;
    background: #022c22;
    color: #ffffff;
    border-bottom: 1px solid rgba(201, 162, 39, 0.35);
}
#pa-cmdk-input {
    flex: 1;
    min-width: 0;
    border: 0 !important;
    background: transparent !important;
    color: #ffffff !important;
    font-size: 14.5px;
    font-weight: 600;
    outline: none !important;
    padding: 4px 0 !important;
    box-shadow: none !important;
}
#pa-cmdk-input::placeholder { color: #a7f3d0; opacity: 0.72; }
#pa-cmdk-close {
    border: 1px solid rgba(255, 255, 255, 0.28) !important;
    background: rgba(255, 255, 255, 0.1) !important;
    color: #ffffff !important;
    border-radius: 8px !important;
    padding: 5px 10px !important;
    font-size: 11.5px !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    flex-shrink: 0;
}
#pa-cmdk-close:hover,
#pa-cmdk-close:focus-visible {
    background: rgba(255, 255, 255, 0.22) !important;
    color: #E5C158 !important;
    border-color: #E5C158 !important;
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
.pa-cmdk-foot {
    padding: 9px 16px;
    background: #f8fbf9;
    border-top: 1px solid #e2ece7;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 11.5px;
    color: #64748b;
}
.pa-cmdk-foot kbd {
    padding: 1px 6px;
    border-radius: 4px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #043927;
    font: 700 10.5px/1.3 monospace;
}
html[data-pa-theme="dark"] .pa-cmdk-foot { background: #13221a; border-color: #2c4637; color: #cbd5e1; }
html[data-pa-theme="dark"] .pa-cmdk-foot kbd { background: #182a20; border-color: #355241; color: #E5C158; }

/* Pengumuman 3 Kolom Sejajar (Satker | MA RI | Ditjen Badilag) */
.pa-feeds-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-top: 12px;
}
@media (max-width: 960px) { .pa-feeds-grid { grid-template-columns: 1fr; } }
.pa-feed-card {
    background: var(--pa-surface, #ffffff);
    border: 1px solid #d5e6dc;
    border-top: 3px solid #C9A227;
    border-radius: 13px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 14px rgba(4, 57, 39, 0.05);
}
.pa-feed-card-head {
    background: linear-gradient(135deg, #022c22 0%, #056e4f 100%);
    color: #ffffff;
    padding: 9px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.pa-feed-head-left {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-width: 0;
}
.pa-feed-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #E5C158;
    flex-shrink: 0;
}
.pa-feed-head-left h3 {
    margin: 0;
    font-size: 13px;
    font-weight: 800;
    color: #ffffff !important;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pa-feed-pill {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    color: #E5C158;
    font-size: 9.5px;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    flex-shrink: 0;
}
.pa-feed-more {
    font-size: 11.2px;
    font-weight: 700;
    color: #E5C158 !important;
    text-decoration: none !important;
    white-space: nowrap;
    flex-shrink: 0;
}
.pa-feed-more:hover { text-decoration: underline !important; color: #ffffff !important; }
.pa-feed-list {
    list-style: none;
    margin: 0;
    padding: 4px 11px;
    max-height: 276px;
    overflow-y: auto;
    flex: 1;
}
.pa-feed-item { border-bottom: 1px solid var(--pa-border, #e6f0eb); }
.pa-feed-item:last-child { border-bottom: 0; }
.pa-feed-item > a {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 9px 2px;
    text-decoration: none !important;
    color: inherit;
    text-align: left;
}
.pa-feed-content {
    flex: 1 1 0%;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 3px;
}
.pa-feed-date {
    display: inline-block;
    font-size: 10.5px;
    font-weight: 700;
    line-height: 1.25;
    color: #92400e;
    background: #fef9e7;
    border: 1px solid #f3dfa0;
    border-radius: 6px;
    padding: 2px 7px;
    white-space: nowrap;
    flex-shrink: 0;
}
.pa-feed-title {
    width: 100%;
    font-size: 12.5px;
    font-weight: 600;
    line-height: 1.38;
    color: var(--pa-text, #0f172a);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    overflow-wrap: anywhere;
    text-align: left;
    transition: color 0.15s ease;
}
.pa-feed-item > a:hover .pa-feed-title { color: #056e4f; text-decoration: underline; }
.pa-feed-ext { font-size: 12px; font-weight: 800; color: #C9A227; flex-shrink: 0; align-self: center; margin-left: auto; }
.pa-ann-compact .pa-feed-list { max-height: none; overflow-y: visible; padding: 4px 2px; }
.pa-feed-empty { padding: 12px 4px; font-size: 12.5px; color: var(--pa-muted, #64748b); }

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
.pa-peg-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px 16px;
    margin-block-end: 18px;
}
.pa-peg-tabs {
    display: flex;
    flex-wrap: wrap;
    flex-wrap: balance;
    gap: 8px;
    margin: 0 !important;
}
.pa-peg-tabs .pa-ops-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 13px;
    font-size: 13px;
    transition: background-color 0.16s ease, color 0.16s ease, border-color 0.16s ease;
}
.pa-peg-tab-cnt {
    font-size: 11px;
    font-weight: 800;
    padding: 1px 7px;
    border-radius: 999px;
    background: rgba(5, 110, 79, 0.1);
    color: inherit;
    font-variant-numeric: tabular-nums;
}
.pa-peg-tabs .pa-ops-tab.is-active .pa-peg-tab-cnt,
.pa-peg-tabs .pa-ops-tab:hover .pa-peg-tab-cnt {
    background: rgba(255, 255, 255, 0.22);
}
.pa-peg-tools-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    flex: 1 1 240px;
    justify-content: flex-end;
}
.pa-peg-search {
    min-inline-size: 220px;
    max-inline-size: 320px;
    inline-size: 100%;
    padding: 8px 13px;
    font-size: 13px;
}
.pa-pegawai-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
    gap: 18px;
}
.pa-pegawai-card {
    container: pegcard / inline-size;
    background: var(--pa-surface, #ffffff);
    border: 1px solid var(--pa-border, #dce6df);
    border-block-start: 3px solid var(--pa-gold, #C9A227);
    border-radius: 16px;
    padding: 18px;
    grid-row: span 3;
    display: grid;
    grid-template-rows: auto 1fr auto;
    grid-template-rows: subgrid;
    row-gap: 14px;
    box-shadow: 0 4px 16px rgba(2, 44, 34, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    position: relative;
}
.pa-pegawai-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(2, 44, 34, 0.11);
    border-color: rgba(5, 110, 79, 0.35);
}
.pa-pegawai-card.is-hidden { display: none; }
.pa-peg-head {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 14px;
    align-items: center;
}
.pa-pegawai-avatar {
    inline-size: 64px;
    block-size: 64px;
    border-radius: 16px;
    background: linear-gradient(135deg, #022c22, #056e4f);
    color: #E5C158;
    font-weight: 900;
    font-size: 20px;
    letter-spacing: 0.03em;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: clip;
    border: 2px solid rgba(201, 162, 39, 0.42);
    box-shadow: 0 6px 14px rgba(2, 44, 34, 0.14);
}
.pa-pegawai-avatar img {
    display: block;
    inline-size: 100%;
    block-size: 100%;
    aspect-ratio: 1 / 1;
    object-fit: cover;
    object-position: center top;
}
.pa-pegawai-info {
    min-inline-size: 0;
}
.pa-pegawai-cat {
    display: inline-block;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 2px 9px;
    border-radius: 999px;
    background: #fef3c7;
    color: #92400e;
    margin-block-end: 6px;
}
.pa-pegawai-cat.is-grp-hakim { background: #ecfdf5; color: #065f46; }
.pa-pegawai-cat.is-grp-kepaniteraan { background: #e0f2fe; color: #075985; }
.pa-pegawai-cat.is-grp-kesekretariatan { background: #f1f5f9; color: #334155; }
.pa-pegawai-cat.is-grp-ppnpn { background: #f3e8ff; color: #6b21a8; }
.pa-pegawai-info h3 {
    margin: 0 0 4px !important;
    font-size: 15.5px !important;
    font-weight: 800 !important;
    line-height: 1.35 !important;
    color: var(--pa-ink, #0f172a) !important;
    overflow-wrap: break-word;
    word-break: normal;
}
.pa-pegawai-role {
    margin: 0 !important;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.42;
    color: #056e4f;
    overflow-wrap: break-word;
}
.pa-peg-meta {
    margin: 0;
    padding: 12px 0 0;
    border-block-start: 1px dashed var(--pa-border, #e2e8f0);
    display: flex;
    flex-direction: column;
    gap: 7px;
    font-size: 12.5px;
}
.pa-peg-meta-row {
    display: grid;
    grid-template-columns: 98px minmax(0, 1fr);
    gap: 8px;
    align-items: baseline;
}
.pa-peg-meta-row dt {
    margin: 0;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--pa-muted, #64748b);
}
.pa-peg-meta-row dd {
    margin: 0;
    color: var(--pa-ink, #1e293b);
    font-weight: 500;
    line-height: 1.42;
    overflow-wrap: break-word;
}
.pa-peg-nip {
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.01em;
}
.pa-peg-foot {
    align-self: end;
    padding-block-start: 4px;
}
.pa-peg-empty {
    margin: 18px 0 0;
    padding: 20px;
    text-align: center;
    border-radius: 12px;
    background: var(--pa-surface, #f8fafc);
    border: 1px dashed var(--pa-border, #cbd5e1);
    color: var(--pa-muted, #475569);
    font-size: 13.5px;
}
@container pegcard (inline-size < 275px) {
    .pa-peg-head {
        grid-template-columns: 1fr;
        justify-items: center;
        text-align: center;
    }
    .pa-peg-meta-row {
        grid-template-columns: 1fr;
        gap: 2px;
    }
}
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
html[data-pa-theme="dark"] .pa-feed-card {
    background: #13221a;
    border-color: #284738;
    color: #e6f4ee;
}
html[data-pa-theme="dark"] .pa-feed-item,
html[data-pa-theme="dark"] .pa-ops-table td,
html[data-pa-theme="dark"] .pa-table-scroll { border-color: #2c4637; }
html[data-pa-theme="dark"] .pa-feed-title { color: #e6f4ee; }
html[data-pa-theme="dark"] .pa-feed-date { background: #262010; border-color: #5c4818; color: #fde68a; }
html[data-pa-theme="dark"] .pa-pegawai-info h3 { color: #f0fdf4 !important; }
html[data-pa-theme="dark"] .pa-pegawai-role { color: #6ee7b7; }
html[data-pa-theme="dark"] .pa-peg-meta { border-block-start-color: #2c4637; }
html[data-pa-theme="dark"] .pa-peg-meta-row dt { color: #94a3b8; }
html[data-pa-theme="dark"] .pa-peg-meta-row dd { color: #e2e8f0; }
html[data-pa-theme="dark"] .pa-pegawai-cat { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
html[data-pa-theme="dark"] .pa-pegawai-cat.is-grp-hakim { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
html[data-pa-theme="dark"] .pa-pegawai-cat.is-grp-kepaniteraan { background: rgba(14, 165, 233, 0.2); color: #7dd3fc; }
html[data-pa-theme="dark"] .pa-pegawai-cat.is-grp-kesekretariatan { background: rgba(148, 163, 184, 0.2); color: #cbd5e1; }
html[data-pa-theme="dark"] .pa-pegawai-cat.is-grp-ppnpn { background: rgba(168, 85, 247, 0.22); color: #d8b4fe; }
html[data-pa-theme="dark"] .pa-peg-empty { background: #14241c; border-color: #355241; color: #cbd5e1; }
html[data-pa-theme="dark"] .pa-cmdk-item small,
html[data-pa-theme="dark"] .pa-kpi-box span { color: #cbd5e1; }
html[data-pa-theme="dark"] .pa-kpi-box strong,
html[data-pa-theme="dark"] .pa-cmdk-item strong { color: #E5C158; }
html[data-pa-theme="dark"] .pa-feed-item > a:hover .pa-feed-title { color: #6ee7b7; }
html[data-pa-theme="dark"] .pa-ops-badge { background: rgba(110, 231, 183, 0.14); color: #6ee7b7; }
html[data-pa-theme="dark"] .pa-cmdk-badge { background: rgba(229, 193, 88, 0.2); color: #fcd34d; }

/* Direktori Putusan Satker (MA RI) */
.pa-dirput-card {
    background: var(--pa-surface, #ffffff);
    border: 1px solid #d5e6dc;
    border-top: 4px solid var(--pa-green, #056e4f);
    border-radius: 16px;
    padding: 20px 22px;
    box-shadow: 0 8px 24px rgba(2, 44, 34, 0.06);
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.pa-dirput-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e6efe9;
}
.pa-dirput-kicker {
    display: inline-block;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.08em;
    color: var(--pa-green, #056e4f);
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-radius: 999px;
    padding: 3px 10px;
    margin-bottom: 6px;
}
.pa-dirput-head h3 {
    margin: 0;
    font-size: 20px;
    line-height: 1.25;
    color: #022c22;
    font-weight: 800;
}
.pa-dirput-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.pa-dirput-btn-ghost,
.pa-dirput-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12.5px;
    font-weight: 700;
    padding: 7px 13px;
    border-radius: 999px;
    text-decoration: none;
    transition: transform .15s ease, background .15s ease;
}
.pa-dirput-btn-ghost {
    color: var(--pa-green-dark, #043927);
    background: #f0f9f4;
    border: 1px solid #b7dfd0;
}
.pa-dirput-btn-ghost:hover { background: #dff4ea; transform: translateY(-1px); }
.pa-dirput-btn-primary {
    color: #ffffff;
    background: linear-gradient(135deg, #056e4f, #043927);
    border: 1px solid #043927;
}
.pa-dirput-btn-primary:hover { background: #022c22; color: #E5C158; transform: translateY(-1px); }
.pa-dirput-search {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.pa-dirput-search input[type="search"] {
    flex: 1 1 220px;
    padding: 9px 14px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-size: 13.5px;
    background: #f8fafc;
    color: #0f172a;
}
.pa-dirput-search input[type="search"]:focus {
    background: #ffffff;
    border-color: var(--pa-green, #056e4f);
}
.pa-dirput-search button {
    padding: 9px 18px;
    border-radius: 10px;
    border: 1px solid var(--pa-gold, #C9A227);
    background: var(--pa-gold, #C9A227);
    color: #022c22;
    font-weight: 800;
    font-size: 13px;
    cursor: pointer;
}
.pa-dirput-search button:hover { filter: brightness(0.95); }
.pa-dirput-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 12px;
}
.pa-hg-narrow .pa-dirput-list { grid-template-columns: 1fr; }
.pa-dirput-item {
    border: 1px solid #e2ece6;
    border-radius: 12px;
    background: #f8fcfa;
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}
.pa-dirput-item:hover {
    border-color: var(--pa-green, #056e4f);
    box-shadow: 0 6px 16px rgba(5, 110, 79, 0.10);
    transform: translateY(-2px);
}
.pa-dirput-item > a {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 14px;
    height: 100%;
    text-decoration: none;
    color: inherit;
}
.pa-dirput-item-top {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.pa-dirput-badge {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.06em;
    padding: 2px 8px;
    border-radius: 999px;
    background: #043927;
    color: #E5C158;
}
.pa-dirput-date {
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
}
.pa-dirput-title {
    font-size: 14px;
    line-height: 1.4;
    color: #0f172a;
    font-weight: 700;
    overflow-wrap: anywhere;
}
.pa-dirput-ex {
    margin: 0;
    font-size: 12.5px;
    line-height: 1.5;
    color: #475569;
}
.pa-dirput-cta {
    margin-top: auto;
    padding-top: 6px;
    font-size: 12px;
    font-weight: 700;
    color: var(--pa-green, #056e4f);
}
.pa-dirput-fallback {
    grid-column: 1 / -1;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 18px;
    border-radius: 12px;
    background: linear-gradient(135deg, #f0f9f4 0%, #fefce8 100%);
    border: 1px dashed #93c5ab;
}
.pa-dirput-fb-icon {
    flex: 0 0 42px;
    height: 42px;
    border-radius: 11px;
    background: #043927;
    color: #E5C158;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.pa-dirput-fb-body strong { display: block; font-size: 15px; color: #022c22; margin-bottom: 4px; }
.pa-dirput-fb-body p { margin: 0 0 10px; font-size: 13px; color: #334155; line-height: 1.55; }
.pa-dirput-fb-links { display: flex; flex-wrap: wrap; gap: 8px; }
.pa-dirput-chip {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 999px;
    background: #ffffff;
    border: 1px solid #a7d7c5;
    color: #043927;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}
.pa-dirput-chip:hover { background: #056e4f; color: #ffffff; border-color: #056e4f; }
html[data-pa-theme="dark"] .pa-dirput-card { background: #13221a; border-color: #284738; color: #e6f4ee; }
html[data-pa-theme="dark"] .pa-dirput-head { border-color: #254032; }
html[data-pa-theme="dark"] .pa-dirput-head h3,
html[data-pa-theme="dark"] .pa-dirput-title,
html[data-pa-theme="dark"] .pa-dirput-fb-body strong { color: #f0fdf4; }
html[data-pa-theme="dark"] .pa-dirput-kicker { background: rgba(110, 231, 183, 0.14); border-color: #2e5b47; color: #6ee7b7; }
html[data-pa-theme="dark"] .pa-dirput-item { background: #182a20; border-color: #2c4c3b; }
html[data-pa-theme="dark"] .pa-dirput-ex,
html[data-pa-theme="dark"] .pa-dirput-date,
html[data-pa-theme="dark"] .pa-dirput-fb-body p { color: #cbd5e1; }
html[data-pa-theme="dark"] .pa-dirput-cta { color: #E5C158; }
html[data-pa-theme="dark"] .pa-dirput-search input[type="search"] { background: #0f1c15; border-color: #2e523f; color: #f0fdf4; }
html[data-pa-theme="dark"] .pa-dirput-fallback { background: #182a20; border-color: #355a47; }
html[data-pa-theme="dark"] .pa-dirput-chip { background: #0f1c15; border-color: #355a47; color: #6ee7b7; }
html[data-pa-theme="dark"] .pa-dirput-btn-ghost { background: #182a20; border-color: #355a47; color: #e6f4ee; }
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

    $tzInfo  = function_exists('pa_sinova_timezone_info') ? pa_sinova_timezone_info() : array('zone' => 'WIB', 'tz' => 'Asia/Jakarta', 'offset' => 7, 'label' => 'WIB (UTC+7)');
    $tzZone  = $tzInfo['zone'];
    $wibNow  = new DateTimeImmutable('now', new DateTimeZone($tzInfo['tz']));
    $daysId  = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
    $monsId  = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $clockInit = $daysId[(int) $wibNow->format('w')] . ', '
        . $wibNow->format('j') . ' '
        . ($monsId[(int) $wibNow->format('n')] ?? $wibNow->format('F')) . ' '
        . $wibNow->format('Y') . ' · ' . $wibNow->format('H:i:s') . ' ' . $tzZone;
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
        <span id="pa-live-clock" class="pa-live-clock" title="<?php echo esc_attr($tzInfo['label']); ?>"><?php echo esc_html($clockInit); ?></span>
        <div class="pa-shell-topbar-links">
            <span id="pa-ptsp-live-pill" class="pa-ptsp-pill" data-pa-tip="Jam layanan PTSP: Senin–Kamis 08.00–16.30 <?php echo esc_attr($tzZone); ?> · Jumat 08.00–17.00 <?php echo esc_attr($tzZone); ?>">● PTSP</span>
            <span>PTSP: <?php echo esc_html($phone ?: 'Senin–Jumat'); ?></span>
            <a href="<?php echo esc_url($cfg['sipp_url'] ?? 'https://sipp.pa-contoh.go.id/'); ?>" target="_blank" rel="noopener noreferrer" data-pa-tip="Sistem Informasi Penelusuran Perkara">SIPP</a>
            <a href="<?php echo esc_url($cfg['ecourt_url'] ?? 'https://ecourt.mahkamahagung.go.id/'); ?>" target="_blank" rel="noopener noreferrer" data-pa-tip="Layanan Perkara Elektronik MA RI">e-Court</a>
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
    $logoUrl = trim((string) ($cfg['logo_url'] ?? ''));
    ?>
<header class="pa-shell-masthead" role="banner">
    <div class="pa-shell-inner">
        <a class="pa-shell-brand" href="<?php echo esc_url(home_url('/')); ?>">
            <?php if ($logoUrl !== '') : ?>
                <img class="pa-shell-logo-img" src="<?php echo esc_url($logoUrl); ?>" alt="Logo <?php echo esc_attr($satker); ?>" fetchpriority="high" decoding="async">
            <?php else : ?>
                <span class="pa-shell-emblem">PA</span>
            <?php endif; ?>
            <div>
                <p class="pa-shell-brand-title"><?php echo esc_html($satker); ?></p>
                <p class="pa-shell-brand-sub"><?php echo esc_html($tagline); ?></p>
            </div>
        </a>
        <button type="button" id="pa-cmdk-open" class="pa-unified-search-btn" aria-haspopup="dialog" aria-label="Cari layanan, dokumen, dan berita">
            <span class="pa-us-left">
                <svg class="pa-us-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
                <span class="pa-us-placeholder">Cari layanan, dokumen, berita...</span>
            </span>
            <kbd class="pa-us-kbd">Ctrl K</kbd>
        </button>
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
    $cfg     = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $satker  = $cfg['satker_name'] ?? (get_bloginfo('name') ?: 'Pengadilan Agama');
    $logoUrl = trim((string) ($cfg['logo_url'] ?? ''));
    $tzInfo = function_exists('pa_sinova_timezone_info') ? pa_sinova_timezone_info() : array('zone' => 'WIB', 'offset' => 7);
    $vStats  = pa_starter_get_visitor_stats();
    if (!pa_sinova_is_adoption_mode()) :
    ?>
<footer class="pa-shell-footer" role="contentinfo">
    <div class="pa-footer-grid">
        <div class="pa-footer-col">
            <?php if ($logoUrl !== '') : ?>
                <div class="pa-footer-brand-head">
                    <img class="pa-footer-logo-img" src="<?php echo esc_url($logoUrl); ?>" alt="Logo <?php echo esc_attr($satker); ?>" loading="lazy" decoding="async">
                    <h4><?php echo esc_html($satker); ?></h4>
                </div>
            <?php else : ?>
                <h4><?php echo esc_html($satker); ?></h4>
            <?php endif; ?>
            <p><?php echo esc_html($cfg['address'] ?? 'Jl. Keadilan Raya No. 1'); ?></p>
            <p><strong>Telp / PTSP:</strong> <?php echo esc_html($cfg['phone'] ?? '-'); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($cfg['email'] ?? '-'); ?></p>
            <?php if (!empty($cfg['hearing_hours'])) : ?>
                <p><strong>Jam Sidang:</strong> <?php echo esc_html($cfg['hearing_hours']); ?></p>
            <?php endif; ?>
            <?php
            $socLinks = array(
                'instagram' => array('url' => trim((string) ($cfg['social_instagram'] ?? '')), 'label' => 'Instagram', 'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>'),
                'youtube'   => array('url' => trim((string) ($cfg['social_youtube'] ?? '')),   'label' => 'YouTube',   'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>'),
                'facebook'  => array('url' => trim((string) ($cfg['social_facebook'] ?? '')),  'label' => 'Facebook',  'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>'),
                'tiktok'    => array('url' => trim((string) ($cfg['social_tiktok'] ?? '')),    'label' => 'TikTok',    'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>'),
                'whatsapp'  => array('url' => trim((string) ($cfg['social_whatsapp'] ?? '')),  'label' => 'WhatsApp PTSP', 'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>'),
            );
            $mapsUrl = trim((string) ($cfg['maps_url'] ?? ''));
            $hasAnySoc = ($mapsUrl !== '');
            foreach ($socLinks as $sl) {
                if ($sl['url'] !== '') { $hasAnySoc = true; break; }
            }
            if ($hasAnySoc) : ?>
                <div class="pa-footer-socials" aria-label="Kanal Resmi &amp; Lokasi Kantor">
                    <?php foreach ($socLinks as $sl) : if ($sl['url'] === '') continue; ?>
                        <a class="pa-footer-soc-btn" href="<?php echo esc_url($sl['url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($sl['label']); ?>" title="<?php echo esc_attr($sl['label']); ?>"><?php echo $sl['svg']; ?></a>
                    <?php endforeach; ?>
                    <?php if ($mapsUrl !== '') : ?>
                        <a class="pa-footer-soc-btn" href="<?php echo esc_url($mapsUrl); ?>" target="_blank" rel="noopener noreferrer">📍 Peta Kantor ↗</a>
                    <?php endif; ?>
                </div>
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
<?php
    $starterVer    = defined('PA_SINOVA_STARTER_VERSION') ? (string) PA_SINOVA_STARTER_VERSION : '2.5.0';
    $starterRepo   = defined('PA_SINOVA_GITHUB_REPO') ? ('https://github.com/' . ltrim((string) PA_SINOVA_GITHUB_REPO, '/')) : 'https://github.com/kang-devlp/pa-wordpress-sinova-starter';
    $creditMain    = trim((string) ($cfg['footer_credit_text'] ?? 'Pengadilan Agama Devlpr Community')) ?: 'Pengadilan Agama Devlpr Community';
    $creditSuffix  = trim((string) ($cfg['footer_credit_suffix'] ?? 'Portal Keterbukaan Informasi Publik'));
    $creditHover   = trim((string) ($cfg['footer_hover_text'] ?? 'Developed with ♥ by PA Devlpr Community')) ?: 'Developed with ♥ by PA Devlpr Community';
    $creditUrl     = esc_url_raw(trim((string) ($cfg['footer_credit_url'] ?? '')), array('http', 'https'));
    if ($creditUrl === '') {
        $creditUrl = $starterRepo;
    }
    $heartSvg = '<svg class="pa-heart-ico" width="14" height="14" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
    $hoverParts = preg_split('/(♥|\{love\})/', $creditHover, -1, PREG_SPLIT_DELIM_CAPTURE);
    $hoverRendered = '';
    if (is_array($hoverParts)) {
        foreach ($hoverParts as $hp) {
            $hoverRendered .= ($hp === '♥' || $hp === '{love}') ? $heartSvg : esc_html($hp);
        }
    } else {
        $hoverRendered = esc_html($creditHover);
    }
    ?>
    <div class="pa-shell-footer-bottom">
        <div class="pa-footer-credit-line">
            <span>&copy; <?php echo esc_html((string) wp_date('Y')); ?> <?php echo esc_html($creditMain); ?><?php echo $creditSuffix !== '' ? ' &middot; ' . esc_html($creditSuffix) : ''; ?></span>
            <div class="pa-footer-ver-wrap">
                <a class="pa-footer-ver-trigger" href="<?php echo esc_url($creditUrl); ?>" target="_blank" rel="noopener noreferrer" aria-describedby="pa-footer-ver-card">v<?php echo esc_html($starterVer); ?></a>
                <div class="pa-footer-credit-popover" id="pa-footer-ver-card" role="tooltip">
                    <div class="pa-footer-credit-card">
                        <div class="pa-footer-credit-main"><?php echo $hoverRendered; ?></div>
                        <div class="pa-footer-credit-meta">
                            <span class="pa-footer-pop-ver">Versi <?php echo esc_html($starterVer); ?></span>
                            <span><?php echo esc_html($satker); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <span class="pa-shell-render-pill">Rendering : <?php echo esc_html(timer_stop(0, 3)); ?> detik.</span>
    </div>
</footer>
    <?php endif; ?>
<?php
    $acoUrlRaw  = trim((string) ($cfg['aco_url'] ?? 'https://cctv.badilag.net/'));
    $acoFloatOn = (string) ($cfg['aco_float_enabled'] ?? '1') !== '0';
    if (!pa_sinova_is_adoption_mode() && $acoUrlRaw !== '' && $acoFloatOn) :
        $acoImgUrl     = trim((string) ($cfg['aco_logo_url'] ?? ''));
        $acoFallback   = wp_make_link_relative(add_query_arg('pa_aco_asset', '1', home_url('/')));
        $acoPos        = (string) ($cfg['aco_float_position'] ?? 'bottom_right');
        if (!in_array($acoPos, array('bottom_right', 'middle_right', 'bottom_left', 'middle_left', 'top_right'), true)) {
            $acoPos = 'bottom_right';
        }
        $acoSize       = (string) ($cfg['aco_float_size'] ?? 'md');
        if (!in_array($acoSize, array('sm', 'md', 'lg'), true)) {
            $acoSize = 'md';
        }
        $acoDrag       = (string) ($cfg['aco_float_draggable'] ?? '1') !== '0';
        $acoIsGif      = ($acoImgUrl === '' || (bool) preg_match('/\.gif(?:$|\?)/i', $acoImgUrl));
        if ($acoImgUrl === '') {
            if (defined('PA_SINOVA_ASSETS_DIR') && defined('PA_SINOVA_ASSETS_URL') && file_exists(PA_SINOVA_ASSETS_DIR . '/cctv-aco.gif')) {
                $acoImgUrl = rtrim((string) PA_SINOVA_ASSETS_URL, '/') . '/cctv-aco.gif';
            } elseif (file_exists(__DIR__ . '/assets/cctv-aco.gif')) {
                if (defined('WPMU_PLUGIN_DIR') && defined('WPMU_PLUGIN_URL') && realpath(__DIR__) === realpath(WPMU_PLUGIN_DIR)) {
                    $acoImgUrl = rtrim((string) WPMU_PLUGIN_URL, '/') . '/assets/cctv-aco.gif';
                } elseif (defined('WP_PLUGIN_DIR') && defined('WP_PLUGIN_URL') && strpos((string) wp_normalize_path(__DIR__), (string) wp_normalize_path(WP_PLUGIN_DIR)) === 0) {
                    $relDir    = ltrim(substr((string) wp_normalize_path(__DIR__), strlen((string) wp_normalize_path(WP_PLUGIN_DIR))), '/');
                    $acoImgUrl = rtrim((string) WP_PLUGIN_URL, '/') . '/' . $relDir . '/assets/cctv-aco.gif';
                } else {
                    $acoImgUrl = content_url('/mu-plugins/assets/cctv-aco.gif');
                }
            } elseif (defined('WPMU_PLUGIN_DIR') && defined('WPMU_PLUGIN_URL') && file_exists(WPMU_PLUGIN_DIR . '/assets/cctv-aco.gif')) {
                $acoImgUrl = rtrim((string) WPMU_PLUGIN_URL, '/') . '/assets/cctv-aco.gif';
            } elseif (defined('WP_PLUGIN_DIR') && defined('WP_PLUGIN_URL') && file_exists(WP_PLUGIN_DIR . '/pa-sinova-starter/wordpress-mu-plugins/assets/cctv-aco.gif')) {
                $acoImgUrl = rtrim((string) WP_PLUGIN_URL, '/') . '/pa-sinova-starter/wordpress-mu-plugins/assets/cctv-aco.gif';
            } else {
                $acoImgUrl = $acoFallback;
            }
            $homeHost = strtolower((string) parse_url(home_url('/'), PHP_URL_HOST));
            $imgHost  = strtolower((string) parse_url($acoImgUrl, PHP_URL_HOST));
            if ($imgHost === '' || ($homeHost !== '' && $imgHost === $homeHost)) {
                $acoImgUrl = wp_make_link_relative($acoImgUrl);
            }
        }
        $acoClasses = 'pa-aco-float pa-aco-pos-' . $acoPos . ' pa-aco-sz-' . $acoSize . ($acoIsGif ? ' pa-aco-is-gif' : '') . ($acoDrag ? ' is-draggable' : '');
        $acoTip     = $acoDrag ? 'Access CCTV Online (ACO) · Klik untuk buka atau seret untuk pindah posisi' : 'Access CCTV Online (ACO) · Klik untuk memantau';
?>
<a id="pa-aco-float" class="<?php echo esc_attr($acoClasses); ?>" data-aco-draggable="<?php echo $acoDrag ? '1' : '0'; ?>" href="<?php echo esc_url($acoUrlRaw); ?>" target="_blank" rel="noopener noreferrer" aria-label="Buka Access CCTV Online (ACO) Ditjen Badilag" data-pa-tip="<?php echo esc_attr($acoTip); ?>">
    <img class="pa-aco-float-img" src="<?php echo esc_url($acoImgUrl); ?>" data-aco-fallback="<?php echo esc_attr($acoFallback); ?>" alt="ACO Access CCTV Online" width="56" height="50" decoding="async">
    <span class="pa-aco-float-static" aria-hidden="true">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
        <span>ACO</span>
    </span>
</a>
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
    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" id="pa-cmdk-form" class="pa-cmdk-head">
        <svg class="pa-cmdk-head-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
        <span id="pa-cmdk-label" class="screen-reader-text">Pencarian Terpadu Website</span>
        <input type="search" name="s" id="pa-cmdk-input" aria-label="Cari layanan, menu, dokumen, atau berita" placeholder="Cari layanan, menu, dokumen (LRA/DIPA), atau berita..." autocomplete="off">
        <button type="submit" class="pa-cmdk-submit">Cari</button>
        <button type="button" id="pa-cmdk-close" aria-label="Tutup pencarian">ESC</button>
    </form>
    <div class="pa-cmdk-results" id="pa-cmdk-results" role="region" aria-live="polite">
        <div class="pa-cmdk-empty">Ketik minimal 2 karakter atau jelajahi pintasan: DIPA, LRA, Jadwal Sidang, e-Court, Disabilitas.</div>
    </div>
    <div class="pa-cmdk-foot">
        <span>Pencarian terpadu: Menu · 23 Arsip Dokumen · Layanan · Berita</span>
        <span>Tekan <kbd>Enter</kbd> untuk pencarian penuh</span>
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
        var tzOffset = <?php echo (int) ($tzInfo['offset'] ?? 7); ?>;
        var tzLabel = <?php echo json_encode($tzInfo['zone'] ?? 'WIB'); ?>;
        var tickClock = function(){
            if(document.hidden) return;
            var d = new Date(Date.now() + tzOffset * 3600000);
            clockEl.textContent = hari[d.getUTCDay()] + ', ' + d.getUTCDate() + ' ' + bulan[d.getUTCMonth()] + ' ' + d.getUTCFullYear() + ' · ' + pad(d.getUTCHours()) + ':' + pad(d.getUTCMinutes()) + ':' + pad(d.getUTCSeconds()) + ' ' + tzLabel;
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
        'beranda': 'Halaman utama portal keterbukaan informasi'
    };
    document.querySelectorAll('#ast-hf-menu-1 > .menu-item > .menu-link, .ast-builder-menu-1 .main-header-menu > .menu-item > .menu-link, .pa-shell-menu > li > a').forEach(function(a){
        var parentLi = a.parentElement;
        if(parentLi && parentLi.querySelector(':scope > ul.sub-menu, :scope > ul.pa-shell-submenu')) return;
        if(!a.hasAttribute('data-pa-tip')){
            var key = (a.textContent || '').replace(/[▾\s]+/g, ' ').trim().toLowerCase();
            if(menuTips[key]) a.setAttribute('data-pa-tip', menuTips[key]);
        }
    });

    (function initPaMegaMenu(){
        var canEnhance = document.body.classList.contains('pa-sinova-classic-shell') || document.querySelector('.pa-shell-menu');
        if(!canEnhance) return;
        var navLists = document.querySelectorAll('#ast-hf-menu-1, .pa-shell-menu');
        if(!navLists.length) return;

        function extractLabel(el){
            var clone = el.cloneNode(true);
            clone.querySelectorAll('.ast-icon,.dropdown-menu-toggle,svg').forEach(function(rm){ rm.remove(); });
            return (clone.textContent || '').replace(/[▾\s]+/g, ' ').trim();
        }

        var megaEntries = [];
        var defaultCrumb = 'Arahkan kursor pada kartu untuk pratinjau jalur menu';

        function closeAllExcept(activeEntry, withGuard){
            megaEntries.forEach(function(en){
                if(en === activeEntry) return;
                clearTimeout(en.timer);
                en.li.classList.remove('is-mega-open');
                if(withGuard) en.li.classList.add('is-mega-closed');
                en.trigger.setAttribute('aria-expanded', 'false');
            });
        }

        function resetEntry(en){
            if(en.crumbTxt) en.crumbTxt.textContent = defaultCrumb;
        }

        function shortUrlPath(rawHref){
            if(!rawHref) return '';
            try{
                var u = new URL(rawHref, window.location.origin);
                return u.origin === window.location.origin ? (u.pathname || '/') : u.hostname;
            }catch(err){
                return rawHref;
            }
        }

        navLists.forEach(function(navUl){
            var topItems = Array.prototype.slice.call(navUl.children);
            topItems.forEach(function(li){
                var trigger = li.querySelector(':scope > a');
                if(!trigger) return;
                var topTitle = extractLabel(trigger);
                var sub = li.querySelector(':scope > ul.sub-menu, :scope > ul.pa-shell-submenu');
                if(!sub) return;

                var rawLinks = Array.prototype.slice.call(sub.querySelectorAll('a'));
                var items = [];
                var seen = {};
                rawLinks.forEach(function(a){
                    var label = extractLabel(a);
                    var href = a.getAttribute('href') || '#';
                    if(!label || seen[label + '|' + href]) return;
                    seen[label + '|' + href] = true;
                    items.push({
                        label: label,
                        href: href,
                        target: a.getAttribute('target') || '',
                        rel: a.getAttribute('rel') || ''
                    });
                });
                if(!items.length) return;

                while(sub.firstChild){ sub.removeChild(sub.firstChild); }
                sub.classList.add('pa-mega-panel');

                var totalCount = items.length;
                var cols = (totalCount <= 2) ? '2' : ((totalCount === 3 || (totalCount > 4 && totalCount <= 6)) ? '3' : '4');

                var headLi = document.createElement('li');
                headLi.className = 'pa-mega-head';
                headLi.setAttribute('role', 'presentation');

                var headLeft = document.createElement('div');
                headLeft.className = 'pa-mega-head-left';
                var headTitle = document.createElement('p');
                headTitle.className = 'pa-mega-head-title';
                headTitle.textContent = topTitle;
                var countPill = document.createElement('span');
                countPill.className = 'pa-mega-count-pill';
                countPill.textContent = totalCount + ' Sub-Menu';
                headLeft.appendChild(headTitle);
                headLeft.appendChild(countPill);

                var headHint = document.createElement('span');
                headHint.className = 'pa-mega-head-hint';
                headHint.textContent = 'Klik kartu untuk membuka layanan';

                headLi.appendChild(headLeft);
                headLi.appendChild(headHint);

                var bodyLi = document.createElement('li');
                bodyLi.className = 'pa-mega-body';
                bodyLi.setAttribute('role', 'presentation');

                var grid = document.createElement('ul');
                grid.className = 'pa-mega-grid';
                grid.setAttribute('data-mega-cols', cols);

                var footLi = document.createElement('li');
                footLi.className = 'pa-mega-foot';
                footLi.setAttribute('role', 'presentation');
                var crumbWrap = document.createElement('div');
                crumbWrap.className = 'pa-mega-crumb-wrap';
                var dot = document.createElement('span');
                dot.className = 'pa-mega-dot';
                var crumbCat = document.createElement('span');
                crumbCat.className = 'pa-mega-crumb-cat';
                crumbCat.textContent = topTitle;
                var crumbSep = document.createElement('span');
                crumbSep.className = 'pa-mega-crumb-sep';
                crumbSep.textContent = '›';
                var crumbTxt = document.createElement('span');
                crumbTxt.className = 'pa-mega-crumb-txt';
                crumbTxt.textContent = defaultCrumb;
                crumbWrap.appendChild(dot);
                crumbWrap.appendChild(crumbCat);
                crumbWrap.appendChild(crumbSep);
                crumbWrap.appendChild(crumbTxt);

                var availEl = document.createElement('span');
                availEl.className = 'pa-mega-avail';
                availEl.textContent = totalCount + ' layanan tersedia';

                footLi.appendChild(crumbWrap);
                footLi.appendChild(availEl);

                items.forEach(function(it){
                    var cardLi = document.createElement('li');
                    var a = document.createElement('a');
                    a.className = 'pa-mega-card-link';
                    a.setAttribute('href', it.href);
                    if(it.target) a.setAttribute('target', it.target);
                    if(it.rel) a.setAttribute('rel', it.rel);
                    var lbl = document.createElement('span');
                    lbl.className = 'pa-mega-card-label';
                    lbl.textContent = it.label;
                    a.appendChild(lbl);
                    cardLi.appendChild(a);
                    grid.appendChild(cardLi);

                    function preview(){
                        crumbTxt.textContent = it.label + '  —  ' + shortUrlPath(it.href);
                    }
                    a.addEventListener('mouseenter', preview);
                    a.addEventListener('focus', preview);
                });

                grid.addEventListener('mouseleave', function(){
                    crumbTxt.textContent = defaultCrumb;
                });

                bodyLi.appendChild(grid);

                sub.appendChild(headLi);
                sub.appendChild(bodyLi);
                sub.appendChild(footLi);

                var entry = {
                    li: li,
                    trigger: trigger,
                    sub: sub,
                    crumbTxt: crumbTxt,
                    totalCount: totalCount,
                    timer: null
                };
                megaEntries.push(entry);

                function openThis(){
                    clearTimeout(entry.timer);
                    closeAllExcept(entry, false);
                    li.classList.remove('is-mega-closed');
                    li.classList.add('is-mega-open');
                    trigger.setAttribute('aria-expanded', 'true');
                }
                function closeThis(withGuard){
                    clearTimeout(entry.timer);
                    li.classList.remove('is-mega-open');
                    if(withGuard) li.classList.add('is-mega-closed');
                    trigger.setAttribute('aria-expanded', 'false');
                }

                li.addEventListener('mouseenter', function(){
                    if(window.innerWidth <= 960) return;
                    openThis();
                });
                li.addEventListener('mouseleave', function(){
                    if(window.innerWidth <= 960) return;
                    clearTimeout(entry.timer);
                    entry.timer = setTimeout(function(){
                        closeThis(false);
                        li.classList.remove('is-mega-closed');
                        resetEntry(entry);
                    }, 140);
                });
                trigger.addEventListener('click', function(e){
                    var rawHref = trigger.getAttribute('href') || '';
                    var isMobile = window.innerWidth <= 960;
                    var isHash = rawHref === '#' || rawHref.indexOf('#') === 0;
                    if(!isHash && !isMobile) return;
                    e.preventDefault();
                    if(isMobile && li.classList.contains('is-mega-open')){
                        closeThis(true);
                        resetEntry(entry);
                    } else {
                        openThis();
                    }
                });
                sub.addEventListener('keydown', function(e){
                    if(e.key === 'Escape'){
                        e.stopPropagation();
                        closeThis(true);
                        resetEntry(entry);
                        trigger.focus();
                    }
                });
            });
        });

        document.addEventListener('click', function(e){
            if(!e.target.closest('#ast-hf-menu-1, .pa-shell-menu')){
                megaEntries.forEach(function(en){
                    clearTimeout(en.timer);
                    en.li.classList.remove('is-mega-open');
                    en.trigger.setAttribute('aria-expanded', 'false');
                    resetEntry(en);
                });
            }
        });
        document.addEventListener('keydown', function(e){
            if(e.key === 'Escape'){
                megaEntries.forEach(function(en){
                    if(en.li.classList.contains('is-mega-open')){
                        clearTimeout(en.timer);
                        en.li.classList.remove('is-mega-open');
                        en.li.classList.add('is-mega-closed');
                        en.trigger.setAttribute('aria-expanded', 'false');
                        resetEntry(en);
                        en.trigger.focus();
                    }
                });
            }
        });
    })();

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

    var mastheadEl = document.getElementById('masthead');
    var brandRowEl = mastheadEl ? mastheadEl.querySelector('#ast-desktop-header .site-header-primary-section-left') : null;
    var menuRowEl  = mastheadEl ? mastheadEl.querySelector('#ast-desktop-header .site-header-primary-section-right') : document.querySelector('.pa-shell-navwrap');
    var navStickThreshold = 110;
    function syncStickyNavMetrics(){
        var wpBar = document.getElementById('wpadminbar');
        var wpBarH = (wpBar && wpBar.offsetHeight) ? wpBar.offsetHeight : 0;
        root.style.setProperty('--pa-wpbar-h', wpBarH + 'px');
        if(mastheadEl && brandRowEl && window.innerWidth > 921){
            var bh = Math.round(brandRowEl.getBoundingClientRect().height || brandRowEl.offsetHeight || 0);
            mastheadEl.style.setProperty('--pa-brand-h', bh + 'px');
            var topbarEl = document.querySelector('.pa-shell-topbar');
            var tbH = topbarEl ? (topbarEl.offsetHeight || 0) : 0;
            navStickThreshold = Math.max(40, tbH + bh - 2);
        } else {
            if(mastheadEl) mastheadEl.style.setProperty('--pa-brand-h', '0px');
            var tbOnly = document.querySelector('.pa-shell-topbar');
            navStickThreshold = Math.max(30, tbOnly ? (tbOnly.offsetHeight || 36) : 36);
        }
        var mh = menuRowEl ? Math.round(menuRowEl.getBoundingClientRect().height || menuRowEl.offsetHeight || 56) : 56;
        root.style.setProperty('--pa-sticky-offset', (mh + 16) + 'px');
    }
    syncStickyNavMetrics();
    window.addEventListener('resize', syncStickyNavMetrics, { passive: true });
    window.addEventListener('load', syncStickyNavMetrics, { passive: true });
    if('ResizeObserver' in window && brandRowEl){
        try{ new ResizeObserver(syncStickyNavMetrics).observe(brandRowEl); }catch(e){}
    }

    function applyScrollEffects(){
        rafLock = false;
        var y = window.scrollY || window.pageYOffset || 0;
        if(scrollBar) scrollBar.style.transform = 'scaleX(' + Math.min(1, Math.max(0, y / docH)).toFixed(4) + ')';
        root.classList.toggle('pa-is-scrolled', y > 50);
        root.classList.toggle('pa-nav-stuck', y >= navStickThreshold);
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

    // Filter Tab Pegawai (ARIA + Keyboard) + Pencarian Langsung & Tabel Data Operasional
    var pegBoard = document.getElementById('pa-pegawai-board');
    if(pegBoard){
        var pegTabs = Array.prototype.slice.call(pegBoard.querySelectorAll('.pa-ops-tab'));
        var pegStatus = document.getElementById('pa-pegawai-status');
        var pegSearch = document.getElementById('pa-pegawai-search');
        var pegEmpty = document.getElementById('pa-pegawai-empty');
        var activePegGroup = 'semua';
        var activePegLabel = 'Semua';

        function applyPegFilter(){
            var q = pegSearch ? (pegSearch.value || '').trim().toLowerCase() : '';
            var shown = 0;
            pegBoard.querySelectorAll('.pa-pegawai-card').forEach(function(c){
                var g = (c.getAttribute('data-group') || '').toLowerCase();
                var hay = (c.getAttribute('data-search') || c.textContent || '').toLowerCase();
                var matchGrp = (activePegGroup === 'semua') || (g.indexOf(activePegGroup) !== -1);
                var matchQ = !q || (hay.indexOf(q) !== -1);
                var hide = !(matchGrp && matchQ);
                c.classList.toggle('is-hidden', hide);
                if(!hide) shown++;
            });
            if(pegEmpty){ pegEmpty.hidden = (shown > 0); }
            if(pegStatus){ pegStatus.textContent = 'Menampilkan ' + shown + ' profil pegawai pada kelompok ' + activePegLabel; }
        }
        function activatePegTab(tab){
            activePegGroup = (tab.getAttribute('data-pegawai-filter') || 'Semua').toLowerCase();
            activePegLabel = (tab.getAttribute('data-pegawai-filter') || 'Semua');
            pegTabs.forEach(function(t){
                var on = t === tab;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.setAttribute('tabindex', on ? '0' : '-1');
            });
            applyPegFilter();
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
        if(pegSearch){
            pegSearch.addEventListener('input', applyPegFilter);
        }
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
        var fLimit = parseInt(feedSec.getAttribute('data-pa-feeds-limit') || '4', 10) || 4;
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
                        if(it.title) a.setAttribute('title', it.title);
                        var contentSpan = document.createElement('span');
                        contentSpan.className = 'pa-feed-content';
                        if(it.date_label){
                            var dt = document.createElement('span');
                            dt.className = 'pa-feed-date';
                            dt.textContent = String(it.date_label).replace(/\s+\d{4}$/, '').trim();
                            contentSpan.appendChild(dt);
                        }
                        var ttl = document.createElement('span');
                        ttl.className = 'pa-feed-title';
                        ttl.textContent = it.title || '';
                        contentSpan.appendChild(ttl);
                        a.appendChild(contentSpan);
                        var ext = document.createElement('span');
                        ext.className = 'pa-feed-ext';
                        ext.setAttribute('aria-hidden', 'true');
                        ext.textContent = '↗';
                        a.appendChild(ext);
                        li.appendChild(a);
                        frag.appendChild(li);
                    });
                    ul.replaceChildren(frag);
                }
                paintList('pa-feed-list-ma', d.ma);
                paintList('pa-feed-list-badilag', d.badilag);
            }).catch(function(){});
    }

    // Hydrate Direktori Putusan Satker RSS via DOM API (Trusted Types Safe)
    var dirputSecs = document.querySelectorAll('.pa-dirput-card[data-pa-dirput-hydrate="1"]');
    if(dirputSecs.length && window.fetch){
        fetch(<?php echo wp_json_encode(rest_url('pa-portal/v1/dirput')); ?>, { credentials: 'same-origin' })
            .then(function(r){ return r.ok ? r.json() : null; })
            .then(function(d){
                if(!d || !Array.isArray(d.items) || !d.items.length) return;
                dirputSecs.forEach(function(sec){
                    var box = sec.querySelector('[data-pa-dirput-list]');
                    if(!box) return;
                    var lim = parseInt(sec.getAttribute('data-pa-dirput-limit') || '6', 10) || 6;
                    var frag = document.createDocumentFragment();
                    d.items.slice(0, lim).forEach(function(it){
                        var art = document.createElement('article');
                        art.className = 'pa-dirput-item';
                        var a = document.createElement('a');
                        a.setAttribute('href', safeHref(it.link));
                        a.setAttribute('target', '_blank');
                        a.setAttribute('rel', 'noopener noreferrer');
                        var top = document.createElement('div');
                        top.className = 'pa-dirput-item-top';
                        var bdg = document.createElement('span');
                        bdg.className = 'pa-dirput-badge';
                        bdg.textContent = 'PUTUSAN';
                        var dt = document.createElement('span');
                        dt.className = 'pa-dirput-date';
                        dt.textContent = it.date_label || 'Putusan';
                        top.appendChild(bdg);
                        top.appendChild(dt);
                        var ttl = document.createElement('strong');
                        ttl.className = 'pa-dirput-title';
                        ttl.textContent = it.title || '';
                        a.appendChild(top);
                        a.appendChild(ttl);
                        if(it.excerpt){
                            var ex = document.createElement('p');
                            ex.className = 'pa-dirput-ex';
                            ex.textContent = it.excerpt;
                            a.appendChild(ex);
                        }
                        var cta = document.createElement('span');
                        cta.className = 'pa-dirput-cta';
                        cta.textContent = 'Lihat Salinan & Detail ↗';
                        a.appendChild(cta);
                        art.appendChild(a);
                        frag.appendChild(art);
                    });
                    box.replaceChildren(frag);
                });
            }).catch(function(){});
    }

    // Command Palette Ctrl+K (Trusted Types Safe)
    var cmdDlg = document.getElementById('pa-cmdk-dialog');
    var cmdForm = document.getElementById('pa-cmdk-form');
    var cmdInput = document.getElementById('pa-cmdk-input');
    var cmdRes = document.getElementById('pa-cmdk-results');
    var cmdClose = document.getElementById('pa-cmdk-close');
    var cmdTimer = null;
    var quickUrl = <?php echo wp_json_encode(rest_url('pa-portal/v1/quick-search')); ?>;

    function getMenuMatches(q){
        var needle = (q || '').trim().toLowerCase();
        if(!needle) return [];
        var matches = [];
        var seen = {};
        document.querySelectorAll('#ast-hf-menu-1 a, .pa-shell-menu a').forEach(function(a){
            var href = a.getAttribute('href') || '';
            if(!href || href === '#' || href.indexOf('#') === 0) return;
            var lbl = a.querySelector('.pa-mega-card-label');
            var text = (lbl ? lbl.textContent : a.textContent || '').replace(/[▾→\s]+/g, ' ').trim();
            if(!text || text.toLowerCase().indexOf(needle) === -1) return;
            var k = text.toLowerCase() + '|' + href;
            if(seen[k]) return;
            seen[k] = true;
            matches.push({ type: 'menu', badge: 'Menu', title: text, url: href, desc: 'Navigasi Portal' });
        });
        return matches.slice(0, 6);
    }

    function renderCmdItems(list){
        if(!cmdRes) return;
        if(!list || !list.length){
            var empty = document.createElement('div');
            empty.className = 'pa-cmdk-empty';
            empty.textContent = 'Tidak ditemukan hasil instan. Tekan Enter untuk mencari seluruh artikel & halaman.';
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
        var menuHits = getMenuMatches(q);
        if(menuHits.length){
            renderCmdItems(menuHits);
        }
        if(!window.fetch) return;
        fetch(quickUrl + '?q=' + encodeURIComponent(q || ''), { credentials: 'same-origin' })
            .then(function(r){ return r.ok ? r.json() : null; })
            .then(function(d){
                var apiItems = (d && Array.isArray(d.items)) ? d.items : [];
                var merged = [];
                var seen = {};
                menuHits.concat(apiItems).forEach(function(it){
                    var k = String(it.title || '').toLowerCase() + '|' + String(it.url || '');
                    if(seen[k]) return;
                    seen[k] = true;
                    merged.push(it);
                });
                renderCmdItems(merged.slice(0, 14));
            })
            .catch(function(){ if(menuHits.length) renderCmdItems(menuHits); });
    }
    function openCmd(){
        if(!cmdDlg || typeof cmdDlg.showModal !== 'function') return;
        if(!cmdDlg.open) cmdDlg.showModal();
        if(cmdInput){
            cmdInput.focus();
            fetchCmd(cmdInput.value || '');
        }
    }
    if(cmdForm && cmdInput){
        cmdForm.addEventListener('submit', function(e){
            if(!(cmdInput.value || '').trim()){
                e.preventDefault();
                cmdInput.focus();
            }
        });
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
    document.querySelectorAll('[data-pa-hero-slider]').forEach(function(slider){
        var slides = Array.prototype.slice.call(slider.querySelectorAll('[data-hs-slide]'));
        var dots = Array.prototype.slice.call(slider.querySelectorAll('[data-hs-to]'));
        if(slides.length < 2) return;
        var cur = 0;
        function showSlide(i){
            cur = (i + slides.length) % slides.length;
            slides.forEach(function(s, idx){
                var on = (idx === cur);
                s.classList.toggle('is-active', on);
                if(on) s.removeAttribute('hidden'); else s.setAttribute('hidden', '');
            });
            dots.forEach(function(d, idx){
                var on = (idx === cur);
                d.classList.toggle('is-active', on);
                d.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
        }
        slider.querySelectorAll('[data-hs-dir]').forEach(function(btn){
            btn.addEventListener('click', function(){ showSlide(cur + (parseInt(btn.getAttribute('data-hs-dir'), 10) || 1)); });
        });
        dots.forEach(function(d){
            d.addEventListener('click', function(){ showSlide(parseInt(d.getAttribute('data-hs-to'), 10) || 0); });
        });
        var autoSec = parseInt(slider.getAttribute('data-autoplay') || '0', 10) || 0;
        var reduceM = (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) || document.documentElement.getAttribute('data-pa-nomotion') === '1';
        if(autoSec > 0 && !reduceM){
            var slPaused = false;
            slider.addEventListener('mouseenter', function(){ slPaused = true; });
            slider.addEventListener('mouseleave', function(){ slPaused = false; });
            slider.addEventListener('focusin', function(){ slPaused = true; });
            slider.addEventListener('focusout', function(){ slPaused = false; });
            setInterval(function(){ if(!slPaused) showSlide(cur + 1); }, autoSec * 1000);
        }
    });

    var pegDlg = document.getElementById('pa-pegawai-dlg');
    if(pegDlg && typeof pegDlg.showModal === 'function'){
        if(!('closedBy' in HTMLDialogElement.prototype)){
            var pOut = false;
            var isPOut = function(e){
                var r = pegDlg.getBoundingClientRect();
                return e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
            };
            pegDlg.addEventListener('pointerdown', function(e){ pOut = isPOut(e); });
            pegDlg.addEventListener('pointerup', function(e){ if(pOut && isPOut(e)) pegDlg.close(); });
        }
        document.querySelectorAll('[data-peg-detail]').forEach(function(btn){
            btn.addEventListener('click', function(){
                var raw = btn.getAttribute('data-peg-detail') || '{}';
                var info;
                try { info = JSON.parse(raw); } catch(err) { return; }
                var titleEl = document.getElementById('pa-peg-dlg-name');
                var bodyEl = document.getElementById('pa-peg-dlg-body');
                if(!titleEl || !bodyEl) return;
                titleEl.textContent = info.name || 'Profil Pegawai';
                bodyEl.replaceChildren();

                var top = document.createElement('div');
                top.className = 'pa-peg-dlg-row';
                if(info.photo){
                    var img = document.createElement('img');
                    img.className = 'pa-peg-dlg-avatar';
                    img.src = info.photo;
                    img.alt = info.name || '';
                    top.appendChild(img);
                } else {
                    var av = document.createElement('span');
                    av.className = 'pa-peg-dlg-avatar';
                    av.textContent = info.init || 'PA';
                    top.appendChild(av);
                }
                var sum = document.createElement('div');
                var rEl = document.createElement('strong');
                rEl.textContent = info.role || '-';
                var gEl = document.createElement('div');
                gEl.style.fontSize = '12px';
                gEl.style.opacity = '0.8';
                gEl.textContent = info.group || 'Aparatur';
                sum.appendChild(rEl); sum.appendChild(gEl);
                top.appendChild(sum);
                bodyEl.appendChild(top);

                var dl = document.createElement('dl');
                dl.className = 'pa-peg-dlg-kv';
                [
                    ['NIP', info.nip],
                    ['Pangkat / Gol', info.gol],
                    ['TMT', info.tmt],
                    ['Pendidikan', info.edu],
                    ['Riwayat Pendidikan', info.r_edu],
                    ['Riwayat Jabatan', info.r_jab]
                ].forEach(function(pair){
                    if(!pair[1]) return;
                    var dt = document.createElement('dt'); dt.textContent = pair[0];
                    var dd = document.createElement('dd'); dd.textContent = pair[1];
                    dl.appendChild(dt); dl.appendChild(dd);
                });
                bodyEl.appendChild(dl);
                pegDlg.showModal();
            });
        });
    }

    document.querySelectorAll('[data-pa-sched-hub]').forEach(function(hub){
        var tabs = Array.prototype.slice.call(hub.querySelectorAll('[data-sched-tab]'));
        var panels = Array.prototype.slice.call(hub.querySelectorAll('[data-sched-panel]'));
        if(tabs.length < 2) return;
        function activateSchedTab(targetKey, moveFocus){
            tabs.forEach(function(t){
                var on = (t.getAttribute('data-sched-tab') === targetKey);
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.setAttribute('tabindex', on ? '0' : '-1');
                if(on && moveFocus) t.focus();
            });
            panels.forEach(function(p){
                var on = (p.getAttribute('data-sched-panel') === targetKey);
                if(on) p.removeAttribute('hidden'); else p.setAttribute('hidden', '');
            });
        }
        tabs.forEach(function(tab, idx){
            tab.addEventListener('click', function(){
                activateSchedTab(tab.getAttribute('data-sched-tab'), false);
            });
            tab.addEventListener('keydown', function(e){
                var nextIdx = -1;
                if(e.key === 'ArrowRight') nextIdx = (idx + 1) % tabs.length;
                else if(e.key === 'ArrowLeft') nextIdx = (idx - 1 + tabs.length) % tabs.length;
                else if(e.key === 'Home') nextIdx = 0;
                else if(e.key === 'End') nextIdx = tabs.length - 1;
                if(nextIdx >= 0){
                    e.preventDefault();
                    activateSchedTab(tabs[nextIdx].getAttribute('data-sched-tab'), true);
                }
            });
        });
    });

    var acoFloat = document.getElementById('pa-aco-float');
    var acoImg = document.querySelector('#pa-aco-float .pa-aco-float-img');
    var acoStatic = document.querySelector('#pa-aco-float .pa-aco-float-static');
    if(acoImg){
        var acoTriedFallback = false;
        var handleAcoImgErr = function(){
            var fb = acoImg.getAttribute('data-aco-fallback') || '';
            if(!acoTriedFallback && fb && acoImg.getAttribute('src') !== fb){
                acoTriedFallback = true;
                acoImg.setAttribute('src', fb);
                return;
            }
            acoImg.setAttribute('hidden', '');
            if(acoStatic) acoStatic.classList.add('is-fallback');
        };
        acoImg.addEventListener('error', handleAcoImgErr);
        if(acoImg.complete && acoImg.naturalWidth === 0){
            handleAcoImgErr();
        }
    }

    if(acoFloat && acoFloat.getAttribute('data-aco-draggable') === '1'){
        var acoStoreKey = 'pa_aco_pos_v1';
        try {
            var savedPos = JSON.parse(localStorage.getItem(acoStoreKey) || 'null');
            if(savedPos && typeof savedPos.left === 'number' && typeof savedPos.top === 'number'){
                var maxL = Math.max(8, window.innerWidth - (acoFloat.offsetWidth || 64) - 8);
                var maxT = Math.max(8, window.innerHeight - (acoFloat.offsetHeight || 56) - 8);
                acoFloat.classList.add('is-custom-pos');
                acoFloat.style.left = Math.min(maxL, Math.max(8, savedPos.left)) + 'px';
                acoFloat.style.top = Math.min(maxT, Math.max(8, savedPos.top)) + 'px';
                acoFloat.style.right = 'auto';
                acoFloat.style.bottom = 'auto';
            }
        } catch(e){}
        var acoDragging = false, acoMoved = false, sx = 0, sy = 0, ox = 0, oy = 0;
        acoFloat.addEventListener('pointerdown', function(e){
            if(e.button !== 0) return;
            var rect = acoFloat.getBoundingClientRect();
            acoDragging = true; acoMoved = false;
            sx = e.clientX; sy = e.clientY;
            ox = rect.left; oy = rect.top;
            if(acoFloat.setPointerCapture) acoFloat.setPointerCapture(e.pointerId);
        });
        acoFloat.addEventListener('pointermove', function(e){
            if(!acoDragging) return;
            var dx = e.clientX - sx, dy = e.clientY - sy;
            if(!acoMoved && (Math.abs(dx) > 6 || Math.abs(dy) > 6)){
                acoMoved = true;
                acoFloat.classList.add('is-dragging', 'is-custom-pos');
                acoFloat.style.right = 'auto';
                acoFloat.style.bottom = 'auto';
            }
            if(acoMoved){
                var maxL = Math.max(8, window.innerWidth - acoFloat.offsetWidth - 8);
                var maxT = Math.max(8, window.innerHeight - acoFloat.offsetHeight - 8);
                acoFloat.style.left = Math.min(maxL, Math.max(8, ox + dx)) + 'px';
                acoFloat.style.top = Math.min(maxT, Math.max(8, oy + dy)) + 'px';
            }
        });
        var endAcoDrag = function(){
            if(!acoDragging) return;
            acoDragging = false;
            acoFloat.classList.remove('is-dragging');
            if(acoMoved){
                var rect = acoFloat.getBoundingClientRect();
                try { localStorage.setItem(acoStoreKey, JSON.stringify({ left: Math.round(rect.left), top: Math.round(rect.top) })); } catch(e){}
                setTimeout(function(){ acoMoved = false; }, 80);
            }
        };
        acoFloat.addEventListener('pointerup', endAcoDrag);
        acoFloat.addEventListener('pointercancel', endAcoDrag);
        acoFloat.addEventListener('click', function(e){ if(acoMoved) e.preventDefault(); });
    }

    document.querySelectorAll('.pa-dirput-search').forEach(function(form){
        form.addEventListener('submit', function(e){
            var qInp = form.querySelector('input[type="search"]');
            if(!qInp) return;
            var rawQ = (qInp.value || '').trim();
            if(!rawQ) return;
            var hint = (form.getAttribute('data-court-hint') || '').trim();
            var hash = (form.getAttribute('data-court-hash') || '').trim();
            var scopeChk = form.querySelector('.pa-dirput-scope-chk');
            var useScope = (!scopeChk || scopeChk.checked);
            var finalQ = rawQ;
            if(useScope && hint && !hash){
                var lowQ = rawQ.toLowerCase();
                var lowH = hint.toLowerCase();
                if(lowQ.indexOf(lowH) === -1 && !/pdt\.|pa\.|ms\.|pta\./i.test(rawQ)){
                    finalQ = rawQ + ' ' + hint;
                }
            }
            e.preventDefault();
            var targetUrl = 'https://putusan3.mahkamahagung.go.id/search.html?q=' + encodeURIComponent(finalQ);
            if(hash){ targetUrl += '&court=' + encodeURIComponent(hash); }
            window.open(targetUrl, '_blank', 'noopener,noreferrer');
        });
    });

    document.querySelectorAll('.pa-st-form[data-pa-case-tracker="1"]').forEach(function(form){
        form.addEventListener('submit', function(e){
            e.preventDefault();
            var qInp = form.querySelector('.pa-st-input');
            var resId = form.getAttribute('data-results-id');
            var resBox = resId ? document.getElementById(resId) : null;
            if(!qInp || !resBox) return;
            var q = (qInp.value || '').trim();
            if(q.length < 2) return;
            var sec = form.closest('.pa-sipp-tracker');
            var sippBase = (sec ? (sec.getAttribute('data-sipp-url') || '') : '').replace(/\/+$/, '');
            resBox.hidden = false;
            var loading = document.createElement('div');
            loading.className = 'pa-st-empty';
            loading.textContent = 'Menelusuri perkara "' + q + '"...';
            resBox.replaceChildren(loading);
            fetch(<?php echo wp_json_encode(rest_url('pa-portal/v1/case-lookup')); ?> + '?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
                .then(function(r){ return r.ok ? r.json() : null; })
                .then(function(d){
                    var frag = document.createDocumentFragment();
                    if(d && Array.isArray(d.items) && d.items.length){
                        d.items.forEach(function(it){
                            var a = document.createElement('a');
                            a.className = 'pa-st-res-item';
                            a.setAttribute('href', safeHref(it.url));
                            var left = document.createElement('div');
                            left.className = 'pa-st-res-meta';
                            var st = document.createElement('strong');
                            st.textContent = it.title || '';
                            var sp = document.createElement('span');
                            var parts = [it.subtitle, it.category, it.date].filter(Boolean);
                            sp.textContent = parts.join(' · ');
                            left.appendChild(st);
                            left.appendChild(sp);
                            var tag = document.createElement('span');
                            tag.className = 'pa-st-res-tag';
                            tag.textContent = it.type_label || 'Perkara';
                            a.appendChild(left);
                            a.appendChild(tag);
                            frag.appendChild(a);
                        });
                    } else {
                        var emp = document.createElement('div');
                        emp.className = 'pa-st-empty';
                        var msg = document.createElement('span');
                        msg.textContent = 'Belum ditemukan di agenda/putusan lokal portal. Lanjutkan pencarian langsung di SIPP Satker.';
                        emp.appendChild(msg);
                        if(sippBase){
                            var lk = document.createElement('a');
                            lk.className = 'pa-st-pill';
                            lk.setAttribute('href', safeHref(sippBase + '/list_perkara'));
                            lk.setAttribute('target', '_blank');
                            lk.setAttribute('rel', 'noopener noreferrer');
                            lk.textContent = 'Buka SIPP Satker ↗';
                            emp.appendChild(lk);
                        }
                        frag.appendChild(emp);
                    }
                    resBox.replaceChildren(frag);
                })
                .catch(function(){
                    var err = document.createElement('div');
                    err.className = 'pa-st-empty';
                    err.textContent = 'Gagal menghubungi layanan pencarian portal.';
                    resBox.replaceChildren(err);
                });
        });
    });

    var footerCredit = document.querySelector('.pa-footer-ver-wrap');
    if(footerCredit){
        footerCredit.addEventListener('mouseleave', function(){ footerCredit.classList.remove('is-dismissed'); });
        footerCredit.addEventListener('focusout', function(e){
            if(!footerCredit.contains(e.relatedTarget)) footerCredit.classList.remove('is-dismissed');
        });
    }

    document.addEventListener('keydown', function(e){
        if((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')){
            e.preventDefault();
            if(cmdDlg && cmdDlg.open) cmdDlg.close(); else openCmd();
        } else if(e.key === 'Escape'){
            if(a11yPanel && !a11yPanel.hasAttribute('hidden')){
                setA11yOpen(false);
                if(a11yToggle) a11yToggle.focus();
            } else if(footerCredit && (footerCredit.matches(':hover') || footerCredit.contains(document.activeElement))){
                footerCredit.classList.add('is-dismissed');
            }
        }
    });
})();
</script>
    <?php
}, 95);
