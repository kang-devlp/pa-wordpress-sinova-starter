<?php
/**
 * Plugin Name: PA Starter - Official RSS Feeds (MA RI & Badilag), Data Operasional, Quick Search & SEO JSON-LD
 * Description: Mengambil dan menayangkan pengumuman resmi Mahkamah Agung RI & Ditjen Badilag via RSS (SWR cache + SSRF guard), tabel & shortcode terstruktur Pegawai/Jadwal Sidang/Panggilan Ghaib/Statistik Perkara, REST API Pencarian Kilat Ctrl+K, serta Open Graph & JSON-LD GovernmentOrganization.
 * Version: 1.0.0-starter
 */

defined('ABSPATH') || exit;

define('PA_PORTAL_RECORDS_DB_VER', '1.0');

// ============================================================================
// 1. SSRF-SAFE REMOTE FETCHER & RSS FEED RESMI MA RI / DITJEN BADILAG
// ============================================================================

function pa_portal_is_public_ip(string $ip): bool {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return false;
    }
    if (strpos($ip, '169.254.') === 0 || preg_match('/^(?:fc|fd|fe80:)/i', $ip)) {
        return false;
    }
    return true;
}

function pa_portal_ssrf_safe_get(string $url, array $allowedSuffixes = array('mahkamahagung.go.id'), int $timeout = 5): array {
    $url    = trim($url);
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    $host   = strtolower((string) parse_url($url, PHP_URL_HOST));

    if (!in_array($scheme, array('http', 'https'), true) || $host === '') {
        return array('ok' => false, 'body' => '', 'error' => 'Skema atau host URL tidak valid.');
    }

    if (!empty($allowedSuffixes)) {
        $hostOk = false;
        foreach ($allowedSuffixes as $sfx) {
            $sfx = ltrim(strtolower((string) $sfx), '.');
            if ($sfx === '') {
                continue;
            }
            if ($host === $sfx || (strlen($host) > strlen($sfx) && substr($host, -strlen($sfx) - 1) === '.' . $sfx)) {
                $hostOk = true;
                break;
            }
        }
        if (!$hostOk) {
            return array('ok' => false, 'body' => '', 'error' => 'Host berada di luar daftar domain resmi yang diizinkan.');
        }
    }

    $ips = array();
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $ips[] = $host;
    } else {
        $v4 = @gethostbynamel($host);
        if (is_array($v4)) {
            $ips = array_merge($ips, $v4);
        }
        if (function_exists('dns_get_record')) {
            $v6 = @dns_get_record($host, DNS_AAAA);
            if (is_array($v6)) {
                foreach ($v6 as $rec) {
                    if (!empty($rec['ipv6'])) {
                        $ips[] = (string) $rec['ipv6'];
                    }
                }
            }
        }
    }

    $ips = array_values(array_unique($ips));
    if (empty($ips)) {
        return array('ok' => false, 'body' => '', 'error' => 'Host tidak dapat diresolusi.');
    }
    foreach ($ips as $ip) {
        if (!pa_portal_is_public_ip((string) $ip)) {
            return array('ok' => false, 'body' => '', 'error' => 'Alamat IP privat/lokal ditolak oleh pelindung SSRF.');
        }
    }

    $resp = wp_remote_get($url, array(
        'timeout'             => $timeout,
        'redirection'         => 2,
        'limit_response_size' => 1572864,
        'user-agent'          => 'PA-Portal-RSS/1.0',
    ));
    if (is_wp_error($resp)) {
        return array('ok' => false, 'body' => '', 'error' => $resp->get_error_message());
    }
    $code = (int) wp_remote_retrieve_response_code($resp);
    $body = (string) wp_remote_retrieve_body($resp);
    if ($code !== 200 || $body === '') {
        return array('ok' => false, 'body' => '', 'error' => 'HTTP ' . $code);
    }

    return array('ok' => true, 'body' => $body, 'error' => '');
}

function pa_portal_trim_text(string $text, int $max = 140): string {
    $clean = trim((string) preg_replace('/\s+/', ' ', wp_strip_all_tags($text)));
    if ($clean === '') {
        return '';
    }
    $len = function_exists('mb_strlen') ? mb_strlen($clean, 'UTF-8') : strlen($clean);
    if ($len <= $max) {
        return $clean;
    }
    $cut = function_exists('mb_substr') ? mb_substr($clean, 0, $max - 3, 'UTF-8') : substr($clean, 0, $max - 3);
    return rtrim($cut) . '...';
}

function pa_portal_format_id_date(int $ts): string {
    if ($ts <= 0) {
        return '';
    }
    $mons = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');
    $wib  = $ts + 7 * 3600;
    $m    = (int) gmdate('n', $wib);
    return gmdate('d', $wib) . ' ' . ($mons[$m] ?? gmdate('M', $wib)) . ' ' . gmdate('Y', $wib);
}

function pa_portal_parse_rss_xml(string $xml, int $limit = 10): array {
    $xml = trim($xml);
    if ($xml === '') {
        return array();
    }

    $prevErr = libxml_use_internal_errors(true);
    $doc     = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
    libxml_clear_errors();
    libxml_use_internal_errors($prevErr);

    if (!$doc instanceof SimpleXMLElement) {
        return array();
    }

    $items = array();
    if (isset($doc->channel->item)) {
        foreach ($doc->channel->item as $node) {
            $title = trim((string) $node->title);
            $link  = esc_url_raw(trim((string) $node->link), array('http', 'https'));
            if ($title === '' || $link === '' || !preg_match('#^https?://#i', $link)) {
                continue;
            }
            $ts      = strtotime((string) $node->pubDate) ?: 0;
            $desc    = (string) ($node->description ?? '');
            $cat     = trim((string) ($node->category ?? ''));
            $items[] = array(
                'title'      => html_entity_decode($title, ENT_QUOTES, 'UTF-8'),
                'link'       => $link,
                'date_ts'    => (int) $ts,
                'date_label' => pa_portal_format_id_date((int) $ts),
                'excerpt'    => pa_portal_trim_text(html_entity_decode($desc, ENT_QUOTES, 'UTF-8'), 140),
                'category'   => sanitize_text_field($cat),
            );
        }
    } elseif (isset($doc->entry)) {
        foreach ($doc->entry as $entry) {
            $title = trim((string) $entry->title);
            $href  = '';
            foreach ($entry->link as $lnk) {
                $attrs = $lnk->attributes();
                if (!empty($attrs['href'])) {
                    $href = (string) $attrs['href'];
                    break;
                }
            }
            $link = esc_url_raw(trim($href), array('http', 'https'));
            if ($title === '' || $link === '' || !preg_match('#^https?://#i', $link)) {
                continue;
            }
            $ts      = strtotime((string) ($entry->updated ?: $entry->published)) ?: 0;
            $desc    = (string) ($entry->summary ?: $entry->content);
            $items[] = array(
                'title'      => html_entity_decode($title, ENT_QUOTES, 'UTF-8'),
                'link'       => $link,
                'date_ts'    => (int) $ts,
                'date_label' => pa_portal_format_id_date((int) $ts),
                'excerpt'    => pa_portal_trim_text(html_entity_decode($desc, ENT_QUOTES, 'UTF-8'), 140),
                'category'   => '',
            );
        }
    }

    usort($items, static fn (array $a, array $b): int => $b['date_ts'] <=> $a['date_ts']);
    return array_slice($items, 0, max(1, $limit));
}

function pa_portal_refresh_official_feeds(): array {
    $cfg        = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $maUrl      = trim((string) ($cfg['rss_ma_url'] ?? 'https://kepaniteraan.mahkamahagung.go.id/?format=feed&type=rss'));
    $badilagUrl = trim((string) ($cfg['rss_badilag_url'] ?? 'https://badilag.mahkamahagung.go.id/pengumuman-elektronik?format=feed&type=rss'));

    $prev = get_option('pa_official_feeds_cache', array());
    if (!is_array($prev)) {
        $prev = array('ma' => array(), 'badilag' => array(), 'updated_at' => 0);
    }

    $allowedSuffixes = array('mahkamahagung.go.id');
    foreach (array($maUrl, $badilagUrl) as $u) {
        $h = strtolower((string) parse_url($u, PHP_URL_HOST));
        if ($h !== '' && !in_array($h, $allowedSuffixes, true)) {
            $allowedSuffixes[] = $h;
        }
    }

    $maItems      = is_array($prev['ma'] ?? null) ? $prev['ma'] : array();
    $badilagItems = is_array($prev['badilag'] ?? null) ? $prev['badilag'] : array();

    if ($maUrl !== '') {
        $rMa = pa_portal_ssrf_safe_get($maUrl, $allowedSuffixes, 6);
        if ($rMa['ok']) {
            $parsed = pa_portal_parse_rss_xml($rMa['body'], 10);
            if (!empty($parsed)) {
                $maItems = $parsed;
            }
        }
    }

    if ($badilagUrl !== '') {
        $rBad = pa_portal_ssrf_safe_get($badilagUrl, $allowedSuffixes, 6);
        if (!$rBad['ok'] && strpos($badilagUrl, 'pengumuman-elektronik') !== false) {
            $rBad = pa_portal_ssrf_safe_get('https://badilag.mahkamahagung.go.id/seputar-ditjen-badilag?format=feed&type=rss', $allowedSuffixes, 6);
        }
        if ($rBad['ok']) {
            $parsed = pa_portal_parse_rss_xml($rBad['body'], 10);
            if (!empty($parsed)) {
                $badilagItems = $parsed;
            }
        }
    }

    $payload = array(
        'ma'         => $maItems,
        'badilag'    => $badilagItems,
        'updated_at' => time(),
    );
    update_option('pa_official_feeds_cache', $payload, false);
    set_transient('pa_official_feeds_fresh', 1, 3600);
    if (function_exists('pa_home_page_cache_mark_stale')) {
        pa_home_page_cache_mark_stale();
    }
    return $payload;
}
add_action('pa_official_feeds_cron_refresh', 'pa_portal_refresh_official_feeds');

function pa_portal_get_official_feeds(bool $allowSyncIfEmpty = false): array {
    $cache = get_option('pa_official_feeds_cache', array());
    if (!is_array($cache)) {
        $cache = array();
    }
    $ma      = is_array($cache['ma'] ?? null) ? $cache['ma'] : array();
    $badilag = is_array($cache['badilag'] ?? null) ? $cache['badilag'] : array();
    $empty   = empty($ma) && empty($badilag);

    if ($allowSyncIfEmpty && $empty) {
        return pa_portal_refresh_official_feeds();
    }

    if (!get_transient('pa_official_feeds_fresh')) {
        if (!wp_next_scheduled('pa_official_feeds_cron_refresh')) {
            wp_schedule_single_event(time() + 1, 'pa_official_feeds_cron_refresh');
        }
    }

    return array(
        'ma'         => $ma,
        'badilag'    => $badilag,
        'updated_at' => (int) ($cache['updated_at'] ?? 0),
    );
}

add_action('rest_api_init', static function () {
    register_rest_route('pa-portal/v1', '/official-feeds', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => static function () {
            $data = pa_portal_get_official_feeds(true);
            return new WP_REST_Response($data, 200);
        },
    ));
});

add_shortcode('pa_official_announcements', static function ($atts) {
    $cfgAtts = shortcode_atts(array('limit' => 5), $atts, 'pa_official_announcements');
    $limit   = max(1, min(10, (int) $cfgAtts['limit']));
    $feeds   = pa_portal_get_official_feeds(false);
    $maList  = array_slice((array) ($feeds['ma'] ?? array()), 0, $limit);
    $bdList  = array_slice((array) ($feeds['badilag'] ?? array()), 0, $limit);
    $needsHydrate = empty($maList) && empty($bdList);

    $renderItems = static function (array $list, string $fallbackLabel, string $fallbackUrl): string {
        if (empty($list)) {
            return '<li class="pa-feed-empty">Memuat informasi terbaru dari portal resmi… <a href="' . esc_url($fallbackUrl) . '" target="_blank" rel="noopener noreferrer">' . esc_html($fallbackLabel) . ' ↗</a></li>';
        }
        $html = '';
        foreach ($list as $it) {
            $dt  = !empty($it['date_label']) ? '<span class="pa-feed-date">' . esc_html($it['date_label']) . '</span>' : '';
            $exc = !empty($it['excerpt']) ? '<span class="pa-feed-excerpt">' . esc_html($it['excerpt']) . '</span>' : '';
            $html .= '<li class="pa-feed-item">'
                . '<a href="' . esc_url((string) $it['link']) . '" target="_blank" rel="noopener noreferrer">'
                . $dt
                . '<strong class="pa-feed-title">' . esc_html((string) $it['title']) . '</strong>'
                . '<span class="pa-feed-ext" aria-hidden="true">↗</span>'
                . '</a>'
                . $exc
                . '</li>';
        }
        return $html;
    };

    ob_start();
    ?>
    <section class="pa-section-block pa-feeds-section" aria-label="Pengumuman Resmi Mahkamah Agung RI dan Ditjen Badilag" data-pa-feeds-hydrate="<?php echo $needsHydrate ? '1' : '0'; ?>" data-pa-feeds-limit="<?php echo esc_attr((string) $limit); ?>">
        <h2 class="pa-starter-heading" tabindex="0">PENGUMUMAN RESMI MA RI &amp; DITJEN BADILAG <span class="pa-hint-i" aria-hidden="true">i</span></h2>
        <p class="pa-starter-sub">Sindikasi Langsung Informasi Resmi Mahkamah Agung RI dan Direktorat Jenderal Badan Peradilan Agama</p>
        <div class="pa-feeds-grid">
            <article class="pa-feed-card">
                <header class="pa-feed-card-head">
                    <div class="pa-feed-brand">
                        <span class="pa-feed-crest" aria-hidden="true">
                            <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 3L34 8V19C34 28 27.8 34.4 20 37C12.2 34.4 6 28 6 19V8L20 3Z" fill="#022c22" stroke="#E5C158" stroke-width="2"/>
                                <path d="M20 10V27M13 15H27M13 15L10.5 21H15.5L13 15ZM27 15L24.5 21H29.5L27 15ZM16 27H24" stroke="#E5C158" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <span class="pa-feed-pill">RSS Resmi MA RI</span>
                            <h3>Mahkamah Agung RI</h3>
                        </div>
                    </div>
                </header>
                <ul class="pa-feed-list" id="pa-feed-list-ma">
                    <?php echo $renderItems($maList, 'Buka Portal MA RI', 'https://www.mahkamahagung.go.id/id/pengumuman'); ?>
                </ul>
                <footer class="pa-feed-card-foot">
                    <a href="https://www.mahkamahagung.go.id/id/pengumuman" target="_blank" rel="noopener noreferrer" data-pa-tip="Kunjungi laman pengumuman resmi Mahkamah Agung RI.">Portal Mahkamah Agung RI ↗</a>
                </footer>
            </article>

            <article class="pa-feed-card">
                <header class="pa-feed-card-head">
                    <div class="pa-feed-brand">
                        <span class="pa-feed-crest" aria-hidden="true">
                            <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="3" width="34" height="34" rx="9" fill="#022c22" stroke="#E5C158" stroke-width="2"/>
                                <path d="M12 14H28M12 20H24M12 26H21" stroke="#E5C158" stroke-width="2.2" stroke-linecap="round"/>
                                <circle cx="29" cy="25" r="4" fill="#056e4f" stroke="#E5C158" stroke-width="1.8"/>
                            </svg>
                        </span>
                        <div>
                            <span class="pa-feed-pill">RSS Resmi Badilag</span>
                            <h3>Ditjen Badilag MA RI</h3>
                        </div>
                    </div>
                </header>
                <ul class="pa-feed-list" id="pa-feed-list-badilag">
                    <?php echo $renderItems($bdList, 'Buka Pengumuman Badilag', 'https://badilag.mahkamahagung.go.id/pengumuman-elektronik'); ?>
                </ul>
                <footer class="pa-feed-card-foot">
                    <a href="https://badilag.mahkamahagung.go.id/pengumuman-elektronik" target="_blank" rel="noopener noreferrer" data-pa-tip="Kunjungi kanal Pengumuman Elektronik Ditjen Badilag.">Pengumuman Elektronik Badilag ↗</a>
                </footer>
            </article>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
});

// ============================================================================
// 2. DATA OPERASIONAL TERSTRUKTUR (PEGAWAI, JADWAL SIDANG, GHAIB, STATISTIK)
// ============================================================================

function pa_portal_records_table(): string {
    global $wpdb;
    return $wpdb->prefix . 'pa_portal_records';
}

function pa_portal_records_ensure_table(): void {
    if (get_option('pa_portal_records_db_ver') === PA_PORTAL_RECORDS_DB_VER) {
        return;
    }
    global $wpdb;
    $table   = pa_portal_records_table();
    $charset = $wpdb->get_charset_collate();
    $sql     = "CREATE TABLE IF NOT EXISTS `{$table}` (
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `record_type` varchar(24) NOT NULL,
        `title` varchar(220) NOT NULL,
        `subtitle` varchar(220) NOT NULL DEFAULT '',
        `category` varchar(80) NOT NULL DEFAULT '',
        `event_date` date DEFAULT NULL,
        `meta_json` text DEFAULT NULL,
        `sort_order` int(11) NOT NULL DEFAULT 0,
        `status` varchar(20) NOT NULL DEFAULT 'published',
        `updated_at` datetime NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_type_status` (`record_type`, `status`, `sort_order`)
    ) {$charset};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    update_option('pa_portal_records_db_ver', PA_PORTAL_RECORDS_DB_VER, false);
    pa_portal_seed_sample_records();
}

function pa_portal_seed_sample_records(): void {
    global $wpdb;
    $table = pa_portal_records_table();
    $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");
    if ($count > 0) {
        return;
    }

    $now   = current_time('mysql');
    $today = wp_date('Y-m-d');
    $seeds = array(
        // Pegawai (4 contoh posisi resmi satker)
        array('pegawai', 'Dr. H. Ahmad Contoh, S.H., M.H.', 'Ketua Pengadilan Agama', 'Pimpinan', null, array('nip' => '19700101 199503 1 001', 'gol' => 'Pembina Utama Muda (IV/c)', 'edu' => 'S3 Ilmu Hukum'), 1),
        array('pegawai', 'Dra. Hj. Siti Amanah, M.H.', 'Wakil Ketua Pengadilan Agama', 'Pimpinan', null, array('nip' => '19720512 199803 2 002', 'gol' => 'Pembina Tk. I (IV/b)', 'edu' => 'S2 Hukum Islam'), 2),
        array('pegawai', 'H. Budi Santoso, S.Ag., M.H.', 'Panitera Pengadilan Agama', 'Kepaniteraan', null, array('nip' => '19750817 200003 1 003', 'gol' => 'Pembina Tk. I (IV/b)', 'edu' => 'S2 Ilmu Hukum'), 3),
        array('pegawai', 'Rina Kartika, S.E., M.M.', 'Sekretaris Pengadilan Agama', 'Kesekretariatan', null, array('nip' => '19800321 200604 2 004', 'gol' => 'Pembina (IV/a)', 'edu' => 'S2 Manajemen'), 4),
        // Jadwal Sidang (3 contoh)
        array('jadwal', '0101/Pdt.G/2026/PA.Cth', 'Sidang Pertama / Mediasi', 'Ruang Sidang Utama', $today, array('jam' => '09.00 WIB'), 1),
        array('jadwal', '0118/Pdt.G/2026/PA.Cth', 'Pemeriksaan Saksi', 'Ruang Sidang II', $today, array('jam' => '09.30 WIB'), 2),
        array('jadwal', '0042/Pdt.P/2026/PA.Cth', 'Pembacaan Penetapan', 'Ruang Sidang I', $today, array('jam' => '10.30 WIB'), 3),
        // Panggilan Ghaib (2 contoh)
        array('ghaib', '0214/Pdt.G/2026/PA.Cth', 'Pihak Tergugat (Alamat Tidak Diketahui)', 'Sidang I · Pasal 27 PP 9/1975', wp_date('Y-m-d', time() + 14 * 86400), array('pihak' => 'Sdr. Fulan bin Contoh', 'doc_url' => ''), 1),
        array('ghaib', '0229/Pdt.G/2026/PA.Cth', 'Pihak Termohon (Alamat Tidak Diketahui)', 'Sidang I · Pasal 27 PP 9/1975', wp_date('Y-m-d', time() + 21 * 86400), array('pihak' => 'Sdri. Fulana binti Contoh', 'doc_url' => ''), 2),
        // Statistik Perkara (3 bulan contoh)
        array('statistik', 'Oktober 2026', 'Rekapitulasi Bulanan Perkara', '2026', '2026-10-01', array('diterima' => 145, 'diputus' => 138, 'sisa' => 42), 1),
        array('statistik', 'September 2026', 'Rekapitulasi Bulanan Perkara', '2026', '2026-09-01', array('diterima' => 152, 'diputus' => 149, 'sisa' => 35), 2),
        array('statistik', 'Agustus 2026', 'Rekapitulasi Bulanan Perkara', '2026', '2026-08-01', array('diterima' => 134, 'diputus' => 130, 'sisa' => 32), 3),
    );

    foreach ($seeds as $s) {
        $wpdb->insert($table, array(
            'record_type' => $s[0],
            'title'       => $s[1],
            'subtitle'    => $s[2],
            'category'    => $s[3],
            'event_date'  => $s[4],
            'meta_json'   => wp_json_encode($s[5]),
            'sort_order'  => $s[6],
            'status'      => 'published',
            'updated_at'  => $now,
        ));
    }
}

function pa_portal_get_records(string $type, int $limit = 100): array {
    pa_portal_records_ensure_table();
    global $wpdb;
    $table = pa_portal_records_table();
    $rows  = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM `{$table}` WHERE record_type = %s AND status = 'published' ORDER BY sort_order ASC, id DESC LIMIT %d",
        $type, $limit
    ), ARRAY_A);
    return is_array($rows) ? $rows : array();
}

add_shortcode('pa_pegawai_grid', static function () {
    $rows   = pa_portal_get_records('pegawai', 120);
    $groups = array('Semua', 'Pimpinan', 'Hakim', 'Kepaniteraan', 'Kesekretariatan', 'PPNPN');
    ob_start();
    ?>
    <div class="pa-ops-wrap" id="pa-pegawai-board">
        <div class="pa-ops-filter-tabs" role="tablist" aria-label="Filter Kelompok Pegawai">
            <?php foreach ($groups as $idx => $g) : ?>
                <button type="button" role="tab" class="pa-ops-tab<?php echo $idx === 0 ? ' is-active' : ''; ?>" data-pegawai-filter="<?php echo esc_attr($g); ?>"><?php echo esc_html($g); ?></button>
            <?php endforeach; ?>
        </div>
        <div class="pa-pegawai-grid">
            <?php foreach ($rows as $r) :
                $meta     = json_decode((string) ($r['meta_json'] ?? ''), true);
                if (!is_array($meta)) $meta = array();
                $name     = (string) $r['title'];
                $words    = preg_split('/\s+/', trim(preg_replace('/^(?:Dr\.|Dra\.|H\.|Hj\.|Ir\.)\s*/i', '', $name)));
                $initials = strtoupper(substr((string) ($words[0] ?? 'P'), 0, 1) . substr((string) ($words[1] ?? ''), 0, 1));
                $photo    = esc_url_raw((string) ($meta['photo'] ?? ''), array('http', 'https'));
            ?>
                <article class="pa-pegawai-card" data-group="<?php echo esc_attr((string) $r['category']); ?>" tabindex="0">
                    <div class="pa-pegawai-avatar">
                        <?php if ($photo !== '') : ?>
                            <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($name); ?>" width="56" height="56" loading="lazy">
                        <?php else : ?>
                            <span><?php echo esc_html($initials ?: 'PA'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="pa-pegawai-info">
                        <span class="pa-pegawai-cat"><?php echo esc_html((string) ($r['category'] ?: 'Aparatur')); ?></span>
                        <h3><?php echo esc_html($name); ?></h3>
                        <p class="pa-pegawai-role"><?php echo esc_html((string) $r['subtitle']); ?></p>
                    </div>
                    <div class="pa-pegawai-hover-meta">
                        <?php if (!empty($meta['nip'])) : ?><div><strong>NIP:</strong> <?php echo esc_html((string) $meta['nip']); ?></div><?php endif; ?>
                        <?php if (!empty($meta['gol'])) : ?><div><strong>Pangkat/Gol:</strong> <?php echo esc_html((string) $meta['gol']); ?></div><?php endif; ?>
                        <?php if (!empty($meta['edu'])) : ?><div><strong>Pendidikan:</strong> <?php echo esc_html((string) $meta['edu']); ?></div><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
});

function pa_portal_fetch_sipp_hearings_today(): array {
    if (!defined('PA_SIPP_DB_HOST') || !defined('PA_SIPP_DB_NAME') || !defined('PA_SIPP_DB_USER')) {
        return array();
    }
    $cached = get_transient('pa_sipp_hearings_today');
    if (is_array($cached)) {
        return $cached;
    }
    $out = array();
    try {
        if (!class_exists('mysqli')) {
            return array();
        }
        $conn = @mysqli_init();
        if ($conn) {
            @mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 3);
            $ok = @mysqli_real_connect(
                $conn,
                (string) PA_SIPP_DB_HOST,
                (string) PA_SIPP_DB_USER,
                defined('PA_SIPP_DB_PASS') ? (string) PA_SIPP_DB_PASS : '',
                (string) PA_SIPP_DB_NAME,
                defined('PA_SIPP_DB_PORT') ? (int) PA_SIPP_DB_PORT : 3306
            );
            if ($ok) {
                $today = wp_date('Y-m-d');
                $stmt  = @mysqli_prepare(
                    $conn,
                    "SELECT DATE_FORMAT(j.jam_sidang, '%H:%i') AS jam, p.nomor_perkara AS nomor, j.agenda AS agenda, j.ruangan AS ruang FROM perkara_jadwal_sidang j JOIN perkara p ON p.perkara_id = j.perkara_id WHERE j.tanggal_sidang = ? ORDER BY j.jam_sidang ASC LIMIT 120"
                );
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 's', $today);
                    if (@mysqli_stmt_execute($stmt)) {
                        $res = mysqli_stmt_get_result($stmt);
                        while ($res && ($row = mysqli_fetch_assoc($res))) {
                            $out[] = array(
                                'title'      => (string) ($row['nomor'] ?? ''),
                                'subtitle'   => (string) ($row['agenda'] ?? ''),
                                'category'   => (string) ($row['ruang'] ?? '-'),
                                'event_date' => $today,
                                'jam'        => ((string) ($row['jam'] ?? '')) . ' WIB',
                            );
                        }
                    }
                    mysqli_stmt_close($stmt);
                }
                mysqli_close($conn);
            }
        }
    } catch (\Throwable $e) {
        $out = array();
    }
    set_transient('pa_sipp_hearings_today', $out, 300);
    return $out;
}

add_shortcode('pa_jadwal_sidang', static function () {
    $cfg      = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $sippUrl  = trim((string) ($cfg['sipp_url'] ?? 'https://sipp.pa-contoh.go.id/'));
    $sippRows = pa_portal_fetch_sipp_hearings_today();
    $source   = !empty($sippRows) ? 'Koneksi Read-Only SIPP' : 'Data Jadwal Portal';

    if (empty($sippRows)) {
        foreach (pa_portal_get_records('jadwal', 100) as $r) {
            $meta = json_decode((string) ($r['meta_json'] ?? ''), true);
            $sippRows[] = array(
                'title'      => (string) $r['title'],
                'subtitle'   => (string) $r['subtitle'],
                'category'   => (string) $r['category'],
                'event_date' => (string) ($r['event_date'] ?? wp_date('Y-m-d')),
                'jam'        => (string) ($meta['jam'] ?? '09.00 WIB'),
            );
        }
    }

    ob_start();
    ?>
    <div class="pa-ops-wrap">
        <div class="pa-ops-toolbar">
            <input type="search" class="pa-ops-search" data-ops-table="pa-table-jadwal" placeholder="Cari nomor perkara, agenda, atau ruang sidang...">
            <div class="pa-ops-meta">
                <span class="pa-ops-badge"><?php echo esc_html($source); ?></span>
                <?php if ($sippUrl !== '') : ?>
                    <a class="pa-ops-link-btn" href="<?php echo esc_url($sippUrl); ?>" target="_blank" rel="noopener noreferrer">Buka SIPP Lengkap ↗</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="pa-table-scroll">
            <table class="pa-ops-table" id="pa-table-jadwal">
                <thead>
                    <tr><th>Tanggal</th><th>Jam</th><th>Nomor Perkara</th><th>Agenda Sidang</th><th>Ruang</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($sippRows)) : ?>
                        <tr><td colspan="5">Belum ada jadwal persidangan terjadwal.</td></tr>
                    <?php else : foreach ($sippRows as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row['event_date'] ?: wp_date('Y-m-d')); ?></td>
                            <td><strong><?php echo esc_html($row['jam']); ?></strong></td>
                            <td><strong><?php echo esc_html($row['title']); ?></strong></td>
                            <td><?php echo esc_html($row['subtitle']); ?></td>
                            <td><?php echo esc_html($row['category']); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
});

add_shortcode('pa_panggilan_ghaib', static function () {
    $rows = pa_portal_get_records('ghaib', 100);
    ob_start();
    ?>
    <div class="pa-ops-wrap">
        <div class="pa-ops-toolbar">
            <input type="search" class="pa-ops-search" data-ops-table="pa-table-ghaib" placeholder="Cari nomor perkara atau nama pihak...">
            <span class="pa-ops-badge">Pasal 27 PP No. 9 Tahun 1975</span>
        </div>
        <div class="pa-table-scroll">
            <table class="pa-ops-table" id="pa-table-ghaib">
                <thead>
                    <tr><th>Nomor Perkara</th><th>Nama Pihak Dipanggil</th><th>Tanggal Sidang</th><th>Keterangan</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)) : ?>
                        <tr><td colspan="4">Belum ada pengumuman panggilan ghaib aktif.</td></tr>
                    <?php else : foreach ($rows as $r) :
                        $meta  = json_decode((string) ($r['meta_json'] ?? ''), true);
                        $pihak = is_array($meta) ? (string) ($meta['pihak'] ?? $r['subtitle']) : (string) $r['subtitle'];
                        $doc   = is_array($meta) ? esc_url_raw((string) ($meta['doc_url'] ?? ''), array('http', 'https')) : '';
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html((string) $r['title']); ?></strong></td>
                            <td><?php echo esc_html($pihak); ?></td>
                            <td><?php echo esc_html((string) ($r['event_date'] ?: '-')); ?></td>
                            <td>
                                <?php echo esc_html((string) $r['category']); ?>
                                <?php if ($doc !== '') : ?>
                                    · <a href="<?php echo esc_url($doc); ?>" target="_blank" rel="noopener noreferrer">Relaas ↗</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
});

add_shortcode('pa_statistik_perkara', static function () {
    $rows = pa_portal_get_records('statistik', 36);
    $totIn = 0; $totOut = 0; $lastRem = 0;
    foreach ($rows as $idx => $r) {
        $m = json_decode((string) ($r['meta_json'] ?? ''), true);
        if (!is_array($m)) $m = array();
        $totIn  += (int) ($m['diterima'] ?? 0);
        $totOut += (int) ($m['diputus'] ?? 0);
        if ($idx === 0) {
            $lastRem = (int) ($m['sisa'] ?? 0);
        }
    }
    $ratio = $totIn > 0 ? round(($totOut / $totIn) * 100, 1) : 0;
    ob_start();
    ?>
    <div class="pa-ops-wrap">
        <div class="pa-kpi-grid">
            <div class="pa-kpi-box"><span>Total Perkara Diterima</span><strong><?php echo esc_html(number_format($totIn, 0, ',', '.')); ?></strong></div>
            <div class="pa-kpi-box"><span>Total Perkara Diputus</span><strong><?php echo esc_html(number_format($totOut, 0, ',', '.')); ?></strong></div>
            <div class="pa-kpi-box"><span>Sisa Perkara Terkini</span><strong><?php echo esc_html(number_format($lastRem, 0, ',', '.')); ?></strong></div>
            <div class="pa-kpi-box"><span>Rasio Penyelesaian</span><strong><?php echo esc_html((string) $ratio); ?>%</strong></div>
        </div>
        <div class="pa-table-scroll">
            <table class="pa-ops-table">
                <thead>
                    <tr><th>Periode</th><th>Diterima</th><th>Diputus</th><th>Sisa</th><th>Kinerja Penyelesaian</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)) : ?>
                        <tr><td colspan="5">Belum ada data statistik perkara bulanan.</td></tr>
                    <?php else : foreach ($rows as $r) :
                        $m   = json_decode((string) ($r['meta_json'] ?? ''), true);
                        $in  = (int) ($m['diterima'] ?? 0);
                        $out = (int) ($m['diputus'] ?? 0);
                        $rem = (int) ($m['sisa'] ?? 0);
                        $pct = $in > 0 ? min(100, round(($out / $in) * 100, 1)) : 0;
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html((string) $r['title']); ?></strong></td>
                            <td><?php echo esc_html((string) $in); ?></td>
                            <td><?php echo esc_html((string) $out); ?></td>
                            <td><?php echo esc_html((string) $rem); ?></td>
                            <td>
                                <div class="pa-prog-wrap">
                                    <div class="pa-prog-bar"><span style="width:<?php echo esc_attr((string) $pct); ?>%;"></span></div>
                                    <small><?php echo esc_html((string) $pct); ?>%</small>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
});

// ============================================================================
// 3. COMMAND PALETTE INSTANT SEARCH API (CTRL+K)
// ============================================================================

add_action('rest_api_init', static function () {
    register_rest_route('pa-portal/v1', '/quick-search', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => static function (WP_REST_Request $req) {
            $q     = trim(sanitize_text_field((string) $req->get_param('q')));
            $q     = function_exists('mb_substr') ? mb_substr($q, 0, 60, 'UTF-8') : substr($q, 0, 60);
            $ql    = strtolower($q);
            $items = array();

            $quickServices = array(
                array('Jadwal Sidang & SIPP', 'agenda-jadwal-persidangan', 'Layanan', 'Agenda persidangan harian & integrasi SIPP'),
                array('Pendaftaran e-Court', 'e-court', 'Layanan', 'Pendaftaran perkara, pembayaran, & sidang elektronik'),
                array('Direktori Putusan', 'direktori-putusan', 'Layanan', 'Publikasi putusan Mahkamah Agung RI'),
                array('Panjar & Radius Biaya', 'biaya-proses-berperkara', 'Layanan', 'SK Ketua tentang taksiran panjar & radius panggilan'),
                array('Panggilan Ghaib', 'panggilan-ghaib', 'Pengumuman', 'Panggilan pihak tidak diketahui alamatnya'),
                array('Layanan Kelompok Rentan & Disabilitas', 'disabilitas', 'Inklusif', 'Fasilitas layanan prioritas & aksesibilitas disabilitas'),
                array('Pengumuman MA RI & Badilag', 'pengumuman-resmi', 'Resmi', 'Sindikasi informasi Mahkamah Agung & Ditjen Badilag'),
                array('Survei Pelayanan (SKM/SPAK/SPKP)', 'survei-pelayanan', 'Survei', 'Hasil indeks pelayanan publik per triwulan'),
            );

            foreach ($quickServices as $qs) {
                if ($ql === '' || stripos($qs[0] . ' ' . $qs[3], $ql) !== false) {
                    $url     = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url($qs[1]) : home_url('/' . $qs[1] . '/');
                    $items[] = array('type' => 'layanan', 'badge' => $qs[2], 'title' => $qs[0], 'url' => $url, 'desc' => $qs[3]);
                }
            }

            if ($ql !== '' && function_exists('pa_sinova_module_catalog')) {
                foreach (pa_sinova_module_catalog() as $slug => $cfgMod) {
                    if (stripos($cfgMod['title'] . ' ' . $slug . ' ' . $cfgMod['category'], $ql) !== false) {
                        $url     = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url($slug) : home_url('/' . $slug . '/');
                        $items[] = array('type' => 'modul', 'badge' => 'Arsip', 'title' => $cfgMod['title'], 'url' => $url, 'desc' => $cfgMod['category']);
                    }
                    if (count($items) >= 10) {
                        break;
                    }
                }
            }

            if ($ql !== '') {
                global $wpdb;
                $like    = '%' . $wpdb->esc_like($q) . '%';
                $wpPosts = $wpdb->get_results($wpdb->prepare(
                    "SELECT ID, post_title, post_type FROM `{$wpdb->posts}` WHERE post_status = 'publish' AND post_type IN ('page', 'post') AND (post_title LIKE %s OR post_content LIKE %s) ORDER BY post_date DESC LIMIT 8",
                    $like, $like
                ), ARRAY_A);
                foreach ((array) $wpPosts as $wp) {
                    $url     = (string) get_permalink((int) $wp['ID']);
                    $badge   = ($wp['post_type'] === 'post') ? 'Berita' : 'Halaman';
                    $items[] = array('type' => 'wp', 'badge' => $badge, 'title' => (string) $wp['post_title'], 'url' => $url, 'desc' => $url);
                }

                if (function_exists('pa_sinova_table_name')) {
                    $docTable = pa_sinova_table_name();
                    $docs     = $wpdb->get_results($wpdb->prepare(
                        "SELECT title, page_slug, year, doc_url FROM `{$docTable}` WHERE status = 'published' AND (title LIKE %s OR page_slug LIKE %s OR sub_group LIKE %s) ORDER BY year DESC, id DESC LIMIT 6",
                        $like, $like, $like
                    ), ARRAY_A);
                    foreach ((array) $docs as $d) {
                        $items[] = array(
                            'type'  => 'doc',
                            'badge' => 'Dokumen ' . (string) $d['year'],
                            'title' => (string) $d['title'],
                            'url'   => esc_url_raw((string) $d['doc_url']),
                            'desc'  => 'Modul: ' . (string) $d['page_slug'],
                        );
                    }
                }
            }

            $dedup = array();
            $out   = array();
            foreach ($items as $it) {
                $k = strtolower((string) $it['url'] . '|' . (string) $it['title']);
                if (isset($dedup[$k])) {
                    continue;
                }
                $dedup[$k] = true;
                $out[]     = $it;
                if (count($out) >= 14) {
                    break;
                }
            }

            return new WP_REST_Response(array('items' => $out), 200);
        },
    ));
});

// ============================================================================
// 4. ENTERPRISE SEO: OPEN GRAPH STATIS + SCHEMA.ORG JSON-LD
// ============================================================================

add_action('wp_head', static function () {
    if (is_admin() || (defined('PA_SERVING_CUSTOM_LOGIN') && PA_SERVING_CUSTOM_LOGIN)) {
        return;
    }
    $cfg     = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $satker  = $cfg['satker_name'] ?? (get_bloginfo('name') ?: 'Pengadilan Agama');
    $homeUrl = trailingslashit(home_url('/'));
    $pageUrl = is_singular() ? (string) get_permalink() : $homeUrl;
    $title   = is_front_page() ? $satker : wp_get_document_title();
    $desc    = trim((string) ($cfg['leader_welcome'] ?? 'Portal resmi keterbukaan informasi dan layanan peradilan agama.'));
    $desc    = pa_portal_trim_text($desc, 155);

    $seoPluginActive = defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION');
    if (!$seoPluginActive) {
        $ogImg = '';
        if (is_singular() && has_post_thumbnail()) {
            $thumb = (string) get_the_post_thumbnail_url(null, 'large');
            if ($thumb !== '' && !preg_match('/\.gif(?:$|\?)/i', $thumb)) {
                $ogImg = $thumb;
            }
        }
        if ($ogImg === '' && !empty($cfg['logo_url']) && !preg_match('/\.gif(?:$|\?)/i', (string) $cfg['logo_url'])) {
            $ogImg = (string) $cfg['logo_url'];
        }
        echo '<meta property="og:site_name" content="' . esc_attr($satker) . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($desc) . '">' . "\n";
        echo '<meta property="og:type" content="' . (is_single() ? 'article' : 'website') . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($pageUrl) . '">' . "\n";
        if ($ogImg !== '') {
            echo '<meta property="og:image" content="' . esc_url($ogImg) . '">' . "\n";
        }
    }

    $graph = array(
        array(
            '@type'       => 'GovernmentOrganization',
            '@id'         => $homeUrl . '#organization',
            'name'        => $satker,
            'url'         => $homeUrl,
            'telephone'   => (string) ($cfg['phone'] ?? ''),
            'email'       => (string) ($cfg['email'] ?? ''),
            'address'     => array(
                '@type'         => 'PostalAddress',
                'streetAddress' => (string) ($cfg['address'] ?? ''),
                'addressCountry'=> 'ID',
            ),
            'openingHoursSpecification' => array(
                array(
                    '@type'     => 'OpeningHoursSpecification',
                    'dayOfWeek' => array('Monday', 'Tuesday', 'Wednesday', 'Thursday'),
                    'opens'     => '08:00',
                    'closes'    => '16:30',
                ),
                array(
                    '@type'     => 'OpeningHoursSpecification',
                    'dayOfWeek' => array('Friday'),
                    'opens'     => '08:00',
                    'closes'    => '17:00',
                ),
            ),
        ),
        array(
            '@type'           => 'WebSite',
            '@id'             => $homeUrl . '#website',
            'url'             => $homeUrl,
            'name'            => $satker,
            'publisher'       => array('@id' => $homeUrl . '#organization'),
            'potentialAction' => array(
                '@type'       => 'SearchAction',
                'target'      => $homeUrl . '?s={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ),
        ),
    );

    if (!is_front_page() && is_singular()) {
        $graph[] = array(
            '@type'           => 'BreadcrumbList',
            'itemListElement' => array(
                array('@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => $homeUrl),
                array('@type' => 'ListItem', 'position' => 2, 'name' => wp_strip_all_tags((string) get_the_title()), 'item' => $pageUrl),
            ),
        );
    }

    echo '<script type="application/ld+json">' . wp_json_encode(array('@context' => 'https://schema.org', '@graph' => $graph), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}, 5);
