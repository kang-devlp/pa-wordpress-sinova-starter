<?php
/**
 * Plugin Name: PA Starter - Official RSS Feeds (MA RI & Badilag), Data Operasional, Quick Search & SEO JSON-LD
 * Description: Mengambil dan menayangkan pengumuman resmi Mahkamah Agung RI & Ditjen Badilag via RSS (SWR cache + SSRF guard), tabel & shortcode terstruktur Pegawai/Jadwal Sidang/Panggilan Ghaib/Statistik Perkara, REST API Pencarian Kilat Ctrl+K, serta Open Graph & JSON-LD GovernmentOrganization.
 * Version: 2.5.0-starter
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

function pa_portal_ssrf_safe_get(string $url, array $allowedSuffixes = array('mahkamahagung.go.id', 'badilag.net', 'go.id'), int $timeout = 5): array {
    $validateTarget = static function (string $targetUrl) use ($allowedSuffixes): array {
        $targetUrl = trim($targetUrl);
        $scheme    = strtolower((string) parse_url($targetUrl, PHP_URL_SCHEME));
        $host      = strtolower((string) parse_url($targetUrl, PHP_URL_HOST));

        if (!in_array($scheme, array('http', 'https'), true) || $host === '') {
            return array('ok' => false, 'error' => 'Skema atau host URL tidak valid.');
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
                return array('ok' => false, 'error' => 'Host berada di luar daftar domain resmi yang diizinkan.');
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
            return array('ok' => false, 'error' => 'Host tidak dapat diresolusi.');
        }
        foreach ($ips as $ip) {
            if (!pa_portal_is_public_ip((string) $ip)) {
                return array('ok' => false, 'error' => 'Alamat IP privat/lokal ditolak oleh pelindung SSRF.');
            }
        }
        return array('ok' => true, 'error' => '');
    };

    $currentUrl = trim($url);
    for ($hop = 0; $hop <= 2; $hop++) {
        $chk = $validateTarget($currentUrl);
        if (empty($chk['ok'])) {
            return array('ok' => false, 'body' => '', 'error' => (string) $chk['error']);
        }

        $resp = wp_remote_get($currentUrl, array(
            'timeout'             => $timeout,
            'redirection'         => 0,
            'limit_response_size' => 1572864,
            'user-agent'          => 'PA-Portal-RSS/1.0',
        ));
        if (is_wp_error($resp)) {
            return array('ok' => false, 'body' => '', 'error' => $resp->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($resp);
        if (in_array($code, array(301, 302, 303, 307, 308), true)) {
            $loc = (string) wp_remote_retrieve_header($resp, 'location');
            if ($loc === '' || $hop === 2) {
                return array('ok' => false, 'body' => '', 'error' => 'Redirect tidak valid atau terlalu banyak.');
            }
            if (strpos($loc, '//') === 0) {
                $loc = (parse_url($currentUrl, PHP_URL_SCHEME) ?: 'https') . ':' . $loc;
            } elseif (strpos($loc, '/') === 0) {
                $p   = parse_url($currentUrl);
                $loc = ($p['scheme'] ?? 'https') . '://' . ($p['host'] ?? '') . (!empty($p['port']) ? ':' . $p['port'] : '') . $loc;
            }
            $currentUrl = $loc;
            continue;
        }
        $body = (string) wp_remote_retrieve_body($resp);
        if ($code !== 200 || $body === '') {
            return array('ok' => false, 'body' => '', 'error' => 'HTTP ' . $code);
        }
        return array('ok' => true, 'body' => $body, 'error' => '');
    }

    return array('ok' => false, 'body' => '', 'error' => 'Terlalu banyak pengalihan.');
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

    $allowedSuffixes = array('mahkamahagung.go.id', 'badilag.net', 'go.id');

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

function pa_portal_extract_post_thumb(WP_Post $post, string $size = 'medium_large'): string {
    if (function_exists('get_the_post_thumbnail_url')) {
        $featured = get_the_post_thumbnail_url($post->ID, $size);
        if (is_string($featured) && $featured !== '') {
            return esc_url_raw($featured, array('http', 'https'));
        }
    }
    if (preg_match('/<img\b[^>]*\bsrc=["\']([^"\']+)["\']/i', (string) $post->post_content, $m)) {
        $src = html_entity_decode(trim((string) $m[1]), ENT_QUOTES, 'UTF-8');
        if (strpos($src, '/') === 0 && strpos($src, '//') !== 0) {
            $src = home_url($src);
        }
        $clean = esc_url_raw($src, array('http', 'https'));
        if ($clean !== '' && !preg_match('/^(?:javascript|data):/i', $clean)) {
            return $clean;
        }
    }
    return '';
}

function pa_portal_get_satker_announcements(int $limit = 5): array {
    $limit      = max(1, min(12, $limit));
    $satkerList = array();

    if (function_exists('get_posts')) {
        $annPosts = get_posts(array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'category_name'  => 'pengumuman,pengumuman-satker',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));
        foreach ((array) $annPosts as $ap) {
            if (!$ap instanceof WP_Post) {
                continue;
            }
            $ts    = strtotime((string) ($ap->post_date ?? ''));
            $dtLbl = $ts ? pa_portal_format_id_date($ts) : '';
            $satkerList[] = array(
                'title'      => (string) get_the_title($ap),
                'link'       => (string) get_permalink($ap),
                'url'        => (string) get_permalink($ap),
                'date_label' => $dtLbl,
                'date'       => $dtLbl,
                'thumb'      => pa_portal_extract_post_thumb($ap, 'medium'),
                'kind'       => 'post',
            );
        }
    }

    if (count($satkerList) < $limit && function_exists('pa_sinova_get_settings')) {
        $cfgS   = pa_sinova_get_settings();
        $emgTxt = trim((string) ($cfgS['emergency_notice'] ?? ''));
        if ($emgTxt !== '') {
            $emgHref = trim((string) ($cfgS['emergency_notice_url'] ?? ''));
            if ($emgHref === '') {
                $emgHref = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('informasi-serta-merta') : home_url('/informasi-serta-merta/');
            }
            array_unshift($satkerList, array(
                'title'      => $emgTxt,
                'link'       => $emgHref,
                'url'        => $emgHref,
                'date_label' => 'Serta-Merta',
                'date'       => 'Serta-Merta',
                'thumb'      => '',
                'kind'       => 'emergency',
            ));
        }
    }

    if (count($satkerList) < $limit && function_exists('pa_portal_get_records')) {
        $ghaibUrl  = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('panggilan-ghaib') : home_url('/panggilan-ghaib/');
        $ghaibRecs = pa_portal_get_records('ghaib', $limit);
        foreach ($ghaibRecs as $gr) {
            if (count($satkerList) >= $limit) {
                break;
            }
            $meta = json_decode((string) ($gr['meta_json'] ?? ''), true);
            if (!is_array($meta)) {
                $meta = array();
            }
            $pihak = trim((string) ($meta['pihak'] ?? ''));
            $tsEv  = !empty($gr['event_date']) ? strtotime((string) $gr['event_date']) : 0;
            $dtLbl = $tsEv ? pa_portal_format_id_date($tsEv) : 'Ghaib';
            $href  = !empty($meta['doc_url']) ? (string) $meta['doc_url'] : $ghaibUrl;
            $satkerList[] = array(
                'title'      => 'Panggilan Ghaib ' . (string) ($gr['title'] ?? '') . ($pihak !== '' ? ' — ' . $pihak : ''),
                'link'       => $href,
                'url'        => $href,
                'date_label' => $dtLbl,
                'date'       => $dtLbl,
                'thumb'      => '',
                'kind'       => 'ghaib',
            );
        }
    }

    return array_slice($satkerList, 0, $limit);
}

add_shortcode('pa_official_announcements', static function ($atts) {
    $cfgAtts = shortcode_atts(array('limit' => 4), $atts, 'pa_official_announcements');
    $limit   = max(1, min(10, (int) $cfgAtts['limit']));
    $feeds   = pa_portal_get_official_feeds(false);
    $maList  = array_slice((array) ($feeds['ma'] ?? array()), 0, $limit);
    $bdList  = array_slice((array) ($feeds['badilag'] ?? array()), 0, $limit);
    $needsHydrate = empty($maList) && empty($bdList);

    $satkerList    = pa_portal_get_satker_announcements($limit);
    $satkerPostCnt = 0;
    foreach ($satkerList as $slItem) {
        if (($slItem['kind'] ?? '') === 'post') {
            $satkerPostCnt++;
        }
    }

    $satkerMoreUrl = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('panggilan-ghaib') : home_url('/panggilan-ghaib/');
    if ($satkerPostCnt > 0 && function_exists('get_category_by_slug') && function_exists('get_category_link')) {
        $pCat = get_category_by_slug('pengumuman');
        if ($pCat && !is_wp_error($pCat)) {
            $satkerMoreUrl = (string) get_category_link((int) $pCat->term_id);
        }
    }

    $renderItems = static function (array $list, string $fallbackLabel, string $fallbackUrl, bool $isExternal = true): string {
        if (empty($list)) {
            $attr = $isExternal ? ' target="_blank" rel="noopener noreferrer"' : '';
            $arr  = $isExternal ? '&#8599;' : '&#8594;';
            return '<li class="pa-feed-empty">Belum ada pengumuman terbaru. <a href="' . esc_url($fallbackUrl) . '"' . $attr . '>' . esc_html($fallbackLabel) . ' ' . $arr . '</a></li>';
        }
        $html = '';
        $attr = $isExternal ? ' target="_blank" rel="noopener noreferrer"' : '';
        $arr  = $isExternal ? '&#8599;' : '&#8594;';
        foreach ($list as $it) {
            $dtRaw   = trim((string) ($it['date_label'] ?? ''));
            $dtShort = $dtRaw !== '' ? trim((string) preg_replace('/\s+\d{4}$/', '', $dtRaw)) : '';
            $dt      = $dtShort !== '' ? '<span class="pa-feed-date">' . esc_html($dtShort) . '</span>' : '';
            $title   = (string) ($it['title'] ?? '');
            $thumb   = trim((string) ($it['thumb'] ?? ''));
            $thumbHtml = '';
            if (!$isExternal) {
                $thumbHtml = $thumb !== ''
                    ? '<span class="pa-feed-thumb"><img src="' . esc_url($thumb) . '" alt="" loading="lazy" decoding="async"></span>'
                    : '<span class="pa-feed-thumb pa-feed-thumb-ph" aria-hidden="true">📣</span>';
            }
            $html .= '<li class="pa-feed-item">'
                . '<a href="' . esc_url((string) $it['link']) . '"' . $attr . ' title="' . esc_attr($title) . '">'
                . $thumbHtml
                . '<span class="pa-feed-content">'
                . $dt
                . '<span class="pa-feed-title">' . esc_html($title) . '</span>'
                . '</span>'
                . '<span class="pa-feed-ext" aria-hidden="true">' . $arr . '</span>'
                . '</a>'
                . '</li>';
        }
        return $html;
    };

    ob_start();
    ?>
    <section class="pa-section-block pa-feeds-section" aria-label="Pengumuman Satker, Mahkamah Agung RI dan Ditjen Badilag" data-pa-feeds-hydrate="<?php echo $needsHydrate ? '1' : '0'; ?>" data-pa-feeds-limit="<?php echo esc_attr((string) $limit); ?>">
        <h2 class="pa-starter-heading">PENGUMUMAN SATKER, MA RI &amp; DITJEN BADILAG <span class="pa-hint-i" aria-hidden="true">i</span></h2>
        <p class="pa-starter-sub">Pengumuman Resmi Satuan Kerja serta Sindikasi Langsung Mahkamah Agung RI dan Ditjen Badilag</p>
        <div class="pa-feeds-grid">
            <article class="pa-feed-card">
                <header class="pa-feed-card-head">
                    <div class="pa-feed-head-left">
                        <span class="pa-feed-dot" aria-hidden="true"></span>
                        <h3>Pengumuman Satker</h3>
                        <span class="pa-feed-pill">SATKER</span>
                    </div>
                    <a class="pa-feed-more" href="<?php echo esc_url($satkerMoreUrl); ?>">Semua &#8594;</a>
                </header>
                <ul class="pa-feed-list" id="pa-feed-list-satker">
                    <?php echo $renderItems($satkerList, 'Panggilan Ghaib', $satkerMoreUrl, false); ?>
                </ul>
            </article>

            <article class="pa-feed-card">
                <header class="pa-feed-card-head">
                    <div class="pa-feed-head-left">
                        <span class="pa-feed-dot" aria-hidden="true"></span>
                        <h3>Mahkamah Agung RI</h3>
                        <span class="pa-feed-pill">RSS</span>
                    </div>
                    <a class="pa-feed-more" href="https://www.mahkamahagung.go.id/id/pengumuman" target="_blank" rel="noopener noreferrer">Portal MA &#8599;</a>
                </header>
                <ul class="pa-feed-list" id="pa-feed-list-ma">
                    <?php echo $renderItems($maList, 'Portal MA RI', 'https://www.mahkamahagung.go.id/id/pengumuman', true); ?>
                </ul>
            </article>

            <article class="pa-feed-card">
                <header class="pa-feed-card-head">
                    <div class="pa-feed-head-left">
                        <span class="pa-feed-dot" aria-hidden="true"></span>
                        <h3>Ditjen Badilag MA RI</h3>
                        <span class="pa-feed-pill">RSS</span>
                    </div>
                    <a class="pa-feed-more" href="https://badilag.mahkamahagung.go.id/pengumuman-elektronik" target="_blank" rel="noopener noreferrer">Badilag &#8599;</a>
                </header>
                <ul class="pa-feed-list" id="pa-feed-list-badilag">
                    <?php echo $renderItems($bdList, 'Pengumuman Badilag', 'https://badilag.mahkamahagung.go.id/pengumuman-elektronik', true); ?>
                </ul>
            </article>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
});

// ============================================================================
// 1B. DIREKTORI PUTUSAN SATKER (RSS FEED PUTUSAN3.MAHKAMAHAGUNG.GO.ID)
// ============================================================================

function pa_portal_dirput_resolve_urls(string $rawDirUrl = '', string $rawRssUrl = ''): array {
    $cfg = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $dir = trim($rawDirUrl !== '' ? $rawDirUrl : (string) ($cfg['direktori_url'] ?? ''));
    $rss = trim($rawRssUrl !== '' ? $rawRssUrl : (string) ($cfg['dirput_rss_url'] ?? ''));

    $slug = '';
    foreach (array($dir, $rss) as $candidate) {
        if ($candidate === '') {
            continue;
        }
        if (preg_match('#/(?:pengadilan/profil/pengadilan|direktori/index/pengadilan|rss/pengadilan|rss/index/pengadilan)/([a-z0-9-]+?)(?:\.html|/|(?:\?|$))#i', $candidate, $m)) {
            $slug = strtolower(preg_replace('/[^a-z0-9-]/', '', $m[1]));
            break;
        }
        if (preg_match('/^((?:pa|pta|ms|pn|pt|ptun|dilmil)-[a-z0-9-]+)$/i', $candidate, $m)) {
            $slug = strtolower($m[1]);
            break;
        }
    }

    if ($slug !== '') {
        $profileUrl   = 'https://putusan3.mahkamahagung.go.id/pengadilan/profil/pengadilan/' . $slug . '.html';
        $directoryUrl = 'https://putusan3.mahkamahagung.go.id/direktori/index/pengadilan/' . $slug . '.html';
        $autoRssUrl   = 'https://putusan3.mahkamahagung.go.id/rss/pengadilan/' . $slug . '.html';
        $courtLabel   = strtoupper(str_replace('-', ' ', $slug));
    } else {
        $profileUrl   = ($dir !== '' && preg_match('#^https?://#i', $dir)) ? esc_url_raw($dir, array('http', 'https')) : 'https://putusan3.mahkamahagung.go.id/';
        $directoryUrl = $profileUrl;
        $autoRssUrl   = '';
        $courtLabel   = '';
    }

    $finalRss  = ($rss !== '' && preg_match('#^https?://#i', $rss)) ? esc_url_raw($rss, array('http', 'https')) : $autoRssUrl;
    $courtHash = strtolower(trim((string) ($cfg['dirput_court_hash'] ?? '')));
    if (!preg_match('/^[a-f0-9]{32}$/', $courtHash)) {
        $courtHash = '';
        if (preg_match('/[?&]court=([a-f0-9]{32})/i', $dir, $cm)) {
            $courtHash = strtolower($cm[1]);
        }
    }
    $courtSearchHint = '';
    if ($slug !== '') {
        if (preg_match('/^(pa|pta|ms|pn|pt|ptun|dilmil)-(.+)$/i', $slug, $sm)) {
            $courtSearchHint = strtoupper($sm[1]) . ' ' . ucwords(str_replace('-', ' ', $sm[2]));
        } else {
            $courtSearchHint = $courtLabel;
        }
    } elseif (!empty($cfg['satker_name']) && stripos((string) $cfg['satker_name'], 'contoh') === false) {
        $courtSearchHint = trim((string) preg_replace('/\s+kelas\s+.*/i', '', (string) $cfg['satker_name']));
    }

    return array(
        'slug'              => $slug,
        'court_label'       => $courtLabel,
        'court_hash'        => $courtHash,
        'court_search_hint' => $courtSearchHint,
        'profile_url'       => $profileUrl,
        'directory_url'     => $directoryUrl,
        'rss_url'           => $finalRss,
        'search_url'        => 'https://putusan3.mahkamahagung.go.id/search.html',
    );
}

function pa_portal_parse_dirput_html_fallback(string $html, int $limit = 10): array {
    if ($html === '' || !preg_match_all('/<a\b[^>]*href=["\']([^"\']*\/direktori\/putusan\/[^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
        return array();
    }
    $items = array();
    $seen  = array();
    foreach ($matches as $m) {
        $href = html_entity_decode(trim((string) $m[1]), ENT_QUOTES, 'UTF-8');
        if (strpos($href, '/') === 0) {
            $href = 'https://putusan3.mahkamahagung.go.id' . $href;
        }
        $link = esc_url_raw($href, array('http', 'https'));
        if ($link === '' || isset($seen[$link])) {
            continue;
        }
        $title = pa_portal_trim_text(html_entity_decode((string) $m[2], ENT_QUOTES, 'UTF-8'), 120);
        if ($title === '' || strlen($title) < 6) {
            continue;
        }
        $seen[$link] = true;
        $items[] = array(
            'title'      => $title,
            'link'       => $link,
            'date_ts'    => 0,
            'date_label' => 'Putusan',
            'excerpt'    => '',
            'category'   => 'Direktori Putusan',
        );
        if (count($items) >= $limit) {
            break;
        }
    }
    return $items;
}

function pa_portal_refresh_dirput_feed(string $overrideUrl = ''): array {
    $resolved = pa_portal_dirput_resolve_urls($overrideUrl);
    $rssUrl   = (string) ($resolved['rss_url'] ?? '');
    $dirUrl   = (string) ($resolved['directory_url'] ?? '');
    $cacheKey = md5($rssUrl . '|' . $dirUrl);

    $prev = get_option('pa_dirput_feed_cache', array());
    if (!is_array($prev) || ($prev['source_key'] ?? '') !== $cacheKey) {
        $prev = array('source_key' => $cacheKey, 'items' => array(), 'updated_at' => 0, 'status' => 'empty');
    }

    $items  = is_array($prev['items'] ?? null) ? $prev['items'] : array();
    $status = !empty($items) ? 'ok' : 'unavailable';
    $allowedSuffixes = array('mahkamahagung.go.id', 'go.id');

    if ($rssUrl !== '') {
        $resp = pa_portal_ssrf_safe_get($rssUrl, $allowedSuffixes, 7);
        if ($resp['ok']) {
            $parsed = pa_portal_parse_rss_xml($resp['body'], 12);
            if (!empty($parsed)) {
                $items  = $parsed;
                $status = 'ok';
            }
        }
    }

    if (empty($items) && $dirUrl !== '' && strpos($dirUrl, '/direktori/index/pengadilan/') !== false) {
        $respHtml = pa_portal_ssrf_safe_get($dirUrl, $allowedSuffixes, 7);
        if ($respHtml['ok']) {
            $parsedHtml = pa_portal_parse_dirput_html_fallback($respHtml['body'], 12);
            if (!empty($parsedHtml)) {
                $items  = $parsedHtml;
                $status = 'ok';
            }
        }
    }

    $payload = array(
        'source_key' => $cacheKey,
        'resolved'   => $resolved,
        'items'      => $items,
        'status'     => $status,
        'updated_at' => time(),
    );
    update_option('pa_dirput_feed_cache', $payload, false);
    set_transient('pa_dirput_feed_fresh_' . substr($cacheKey, 0, 12), 1, 3600);
    if (function_exists('pa_home_page_cache_mark_stale')) {
        pa_home_page_cache_mark_stale();
    }
    return $payload;
}
add_action('pa_dirput_feed_cron_refresh', static function () {
    pa_portal_refresh_dirput_feed();
});

function pa_portal_get_dirput_feed(bool $allowSyncIfEmpty = false, string $overrideUrl = ''): array {
    $resolved = pa_portal_dirput_resolve_urls($overrideUrl);
    $cacheKey = md5(((string) ($resolved['rss_url'] ?? '')) . '|' . ((string) ($resolved['directory_url'] ?? '')));
    $cache    = get_option('pa_dirput_feed_cache', array());
    $matched  = is_array($cache) && (($cache['source_key'] ?? '') === $cacheKey);
    $items    = ($matched && is_array($cache['items'] ?? null)) ? $cache['items'] : array();
    $freshKey = 'pa_dirput_feed_fresh_' . substr($cacheKey, 0, 12);

    if ($allowSyncIfEmpty && empty($items) && ($resolved['rss_url'] ?? '') !== '' && !get_transient($freshKey)) {
        return pa_portal_refresh_dirput_feed($overrideUrl);
    }

    if (!get_transient($freshKey) && ($resolved['rss_url'] ?? '') !== '') {
        if (!wp_next_scheduled('pa_dirput_feed_cron_refresh')) {
            wp_schedule_single_event(time() + 2, 'pa_dirput_feed_cron_refresh');
        }
    }

    return array(
        'source_key' => $cacheKey,
        'resolved'   => $resolved,
        'items'      => $items,
        'status'     => !empty($items) ? 'ok' : (get_transient($freshKey) ? 'unavailable' : 'pending'),
        'updated_at' => $matched ? (int) ($cache['updated_at'] ?? 0) : 0,
    );
}

add_action('rest_api_init', static function () {
    register_rest_route('pa-portal/v1', '/dirput', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => static function () {
            $data = pa_portal_get_dirput_feed(true);
            return new WP_REST_Response(array(
                'ok'         => true,
                'status'     => (string) ($data['status'] ?? 'empty'),
                'resolved'   => $data['resolved'] ?? array(),
                'items'      => array_slice((array) ($data['items'] ?? array()), 0, 12),
                'updated_at' => (int) ($data['updated_at'] ?? 0),
            ), 200);
        },
    ));
});

function pa_portal_render_dirput_widget(string $title = '', int $limit = 6, string $overrideUrl = ''): string {
    $limit     = max(2, min(12, $limit));
    $data      = pa_portal_get_dirput_feed(false, $overrideUrl);
    $resolved  = is_array($data['resolved'] ?? null) ? $data['resolved'] : pa_portal_dirput_resolve_urls($overrideUrl);
    $items     = array_slice((array) ($data['items'] ?? array()), 0, $limit);
    $slug      = (string) ($resolved['slug'] ?? '');
    $courtLbl  = (string) ($resolved['court_label'] ?? '');
    $courtHash = (string) ($resolved['court_hash'] ?? '');
    $courtHint = (string) ($resolved['court_search_hint'] ?? '');
    $profUrl   = (string) ($resolved['profile_url'] ?? 'https://putusan3.mahkamahagung.go.id/');
    $dirUrl    = (string) ($resolved['directory_url'] ?? 'https://putusan3.mahkamahagung.go.id/');
    $rssUrl    = (string) ($resolved['rss_url'] ?? '');
    $needsHyd  = (empty($items) && $rssUrl !== '' && ($data['status'] ?? '') === 'pending');
    $heading   = $title !== '' ? $title : 'Direktori Putusan Terbaru';

    // Jalur Hybrid v2.5: jika RSS putusan3.mahkamahagung.go.id belum terisi/terbatas Cloudflare, tampilkan Putusan Pilihan / Push SIPP lokal
    if (empty($items) && function_exists('pa_portal_get_records')) {
        foreach (pa_portal_get_records('putusan', $limit) as $pr) {
            $pm     = json_decode((string) ($pr['meta_json'] ?? ''), true);
            $pm     = is_array($pm) ? $pm : array();
            $pUrl   = esc_url_raw((string) ($pm['doc_url'] ?? ''), array('http', 'https'));
            $pStat  = trim((string) ($pm['status_hukum'] ?? ''));
            $pCat   = trim((string) ($pr['category'] ?? ''));
            $pSub   = trim((string) ($pr['subtitle'] ?? ''));
            $exPart = array_filter(array($pCat, $pSub, $pStat));
            $items[] = array(
                'title'      => (string) ($pr['title'] ?? ''),
                'link'       => ($pUrl !== '' ? $pUrl : $dirUrl),
                'date_label' => !empty($pr['event_date']) ? wp_date('d M Y', strtotime((string) $pr['event_date'])) : 'Putusan',
                'excerpt'    => implode(' · ', $exPart),
                'badge'      => 'PUTUSAN SATKER',
            );
        }
    }

    ob_start();
    ?>
    <section class="pa-dirput-card pa-hg-fullh" aria-label="<?php echo esc_attr($heading); ?>" data-pa-dirput-hydrate="<?php echo $needsHyd ? '1' : '0'; ?>" data-pa-dirput-limit="<?php echo (int) $limit; ?>">
        <header class="pa-dirput-head">
            <div class="pa-dirput-head-title">
                <span class="pa-dirput-kicker">DIREKTORI PUTUSAN MA RI<?php echo $courtLbl !== '' ? ' · ' . esc_html($courtLbl) : ''; ?></span>
                <h3><?php echo esc_html($heading); ?></h3>
            </div>
            <div class="pa-dirput-actions">
                <a class="pa-dirput-btn-ghost" href="<?php echo esc_url($profUrl); ?>" target="_blank" rel="noopener noreferrer">Profil Satker ↗</a>
                <a class="pa-dirput-btn-primary" href="<?php echo esc_url($dirUrl); ?>" target="_blank" rel="noopener noreferrer">Semua Putusan ↗</a>
            </div>
        </header>

        <form class="pa-dirput-search" action="https://putusan3.mahkamahagung.go.id/search.html" method="get" target="_blank" rel="noopener noreferrer" role="search" data-court-hint="<?php echo esc_attr($courtHint); ?>" data-court-hash="<?php echo esc_attr($courtHash); ?>">
            <label class="screen-reader-text" for="pa-dirput-q-<?php echo esc_attr($slug ?: 'ma'); ?>">Cari Nomor Perkara atau Kata Kunci Putusan</label>
            <input id="pa-dirput-q-<?php echo esc_attr($slug ?: 'ma'); ?>" type="search" name="q" placeholder="Cari nomor perkara, jenis perkara, atau kata kunci putusan<?php echo $courtLbl !== '' ? ' (' . esc_attr($courtLbl) . ')' : ''; ?>..." required>
            <?php if ($courtHash !== '') : ?>
                <input type="hidden" name="court" value="<?php echo esc_attr($courtHash); ?>">
            <?php endif; ?>
            <button type="submit">Cari Putusan</button>
            <?php if ($courtHint !== '' && $courtHash === '') : ?>
                <label class="pa-dirput-scope">
                    <input type="checkbox" class="pa-dirput-scope-chk" checked>
                    <span>Fokus pencarian di <strong><?php echo esc_html($courtHint); ?></strong></span>
                </label>
            <?php endif; ?>
        </form>

        <div class="pa-dirput-list" data-pa-dirput-list>
            <?php if (!empty($items)) : ?>
                <?php foreach ($items as $it) :
                    $dtRaw   = trim((string) ($it['date_label'] ?? ''));
                    $dtLabel = $dtRaw !== '' ? $dtRaw : 'Putusan';
                    $itTitle = (string) ($it['title'] ?? '');
                    $itEx    = (string) ($it['excerpt'] ?? '');
                ?>
                    <article class="pa-dirput-item">
                        <a href="<?php echo esc_url((string) ($it['link'] ?? $dirUrl)); ?>" target="_blank" rel="noopener noreferrer">
                            <div class="pa-dirput-item-top">
                                <span class="pa-dirput-badge"><?php echo esc_html((string) ($it['badge'] ?? 'PUTUSAN')); ?></span>
                                <span class="pa-dirput-date"><?php echo esc_html($dtLabel); ?></span>
                            </div>
                            <strong class="pa-dirput-title"><?php echo esc_html($itTitle); ?></strong>
                            <?php if ($itEx !== '') : ?>
                                <p class="pa-dirput-ex"><?php echo esc_html($itEx); ?></p>
                            <?php endif; ?>
                            <span class="pa-dirput-cta">Lihat Salinan &amp; Detail ↗</span>
                        </a>
                    </article>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="pa-dirput-fallback">
                    <div class="pa-dirput-fb-icon" aria-hidden="true">⚖</div>
                    <div class="pa-dirput-fb-body">
                        <strong><?php echo $slug !== '' ? 'Portal Resmi Direktori Putusan ' . esc_html($courtLbl) : 'Portal Direktori Putusan Mahkamah Agung RI'; ?></strong>
                        <p>
                            <?php if ($slug !== '') : ?>
                                Akses langsung arsip salinan putusan perkara satuan kerja pada Direktori Putusan Mahkamah Agung RI.
                            <?php else : ?>
                                Tempelkan URL profil Direktori Putusan satker Anda (contoh: <code>https://putusan3.mahkamahagung.go.id/pengadilan/profil/pengadilan/pa-sumedang.html</code>) pada <strong>WP Admin → SINOVA Hub → Identitas &amp; Seeder</strong>.
                            <?php endif; ?>
                        </p>
                        <div class="pa-dirput-fb-links">
                            <a href="<?php echo esc_url($dirUrl); ?>" target="_blank" rel="noopener noreferrer" class="pa-dirput-chip">Buka Daftar Putusan ↗</a>
                            <a href="<?php echo esc_url($profUrl); ?>" target="_blank" rel="noopener noreferrer" class="pa-dirput-chip">Statistik &amp; Profil Pengadilan ↗</a>
                            <a href="https://putusan3.mahkamahagung.go.id/search.html" target="_blank" rel="noopener noreferrer" class="pa-dirput-chip">Pencarian Lanjutan MA RI ↗</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}

add_shortcode('pa_direktori_putusan', static function ($atts) {
    $a = shortcode_atts(array(
        'title' => 'Direktori Putusan Terbaru',
        'limit' => 6,
        'url'   => '',
    ), $atts, 'pa_direktori_putusan');
    return pa_portal_render_dirput_widget((string) $a['title'], (int) $a['limit'], (string) $a['url']);
});

// ============================================================================
// 2. DATA OPERASIONAL TERSTRUKTUR (PEGAWAI, JADWAL SIDANG, GHAIB, STATISTIK)
// ============================================================================

function pa_portal_records_table(): string {
    global $wpdb;
    return $wpdb->prefix . 'pa_portal_records';
}

function pa_portal_records_ensure_table(): void {
    global $wpdb;
    $table = pa_portal_records_table();
    if (get_option('pa_portal_records_db_ver') === PA_PORTAL_RECORDS_DB_VER) {
        static $verified = false;
        if ($verified || !is_admin()) {
            return;
        }
        $verified = true;
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if ($exists === $table) {
            return;
        }
    }
    $charset = $wpdb->get_charset_collate();
    $sql     = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        record_type varchar(24) NOT NULL,
        title varchar(220) NOT NULL,
        subtitle varchar(220) NOT NULL DEFAULT '',
        category varchar(80) NOT NULL DEFAULT '',
        event_date date DEFAULT NULL,
        meta_json text DEFAULT NULL,
        sort_order int(11) NOT NULL DEFAULT 0,
        status varchar(20) NOT NULL DEFAULT 'published',
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY idx_type_status (record_type, status, sort_order)
    ) {$charset};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    $created = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($created === $table) {
        update_option('pa_portal_records_db_ver', PA_PORTAL_RECORDS_DB_VER, false);
        pa_portal_seed_sample_records();
    }
}

function pa_portal_delete_sample_records(): int {
    pa_portal_records_ensure_table();
    global $wpdb;
    $table = pa_portal_records_table();
    $sampleTitles = array(
        'Dr. H. Ahmad Contoh, S.H., M.H.',
        'Dra. Hj. Siti Amanah, M.H.',
        'H. Budi Santoso, S.Ag., M.H.',
        'Rina Kartika, S.E., M.M.',
        '0101/Pdt.G/2026/PA.Cth',
        '0118/Pdt.G/2026/PA.Cth',
        '0042/Pdt.P/2026/PA.Cth',
        '0214/Pdt.G/2026/PA.Cth',
        '0229/Pdt.G/2026/PA.Cth',
        '0156/Pdt.G/2026/PA.Cth',
        '0089/Pdt.P/2026/PA.Cth',
        '0204/Pdt.G/2026/PA.Cth',
        'SIPANTAU — Pantau Antrean Sidang & PTSP',
        'SIAKTA Keliling — Pengambilan Akta Cerai Terjadwal',
        'E-PANJAR — Simulasi & Taksiran Panjar Biaya',
        'SI-RENTAN — Layanan Prioritas Disabilitas & Lansia',
    );
    $placeholders = implode(',', array_fill(0, count($sampleTitles), '%s'));
    $deleted = (int) $wpdb->query($wpdb->prepare(
        "DELETE FROM `{$table}` WHERE title IN ({$placeholders}) OR (record_type = 'statistik' AND subtitle = 'Rekapitulasi Bulanan Perkara' AND title IN ('Oktober 2026','September 2026','Agustus 2026'))",
        ...$sampleTitles
    ));
    if ($deleted > 0 && function_exists('pa_home_page_cache_mark_stale')) {
        pa_home_page_cache_mark_stale();
    }
    return $deleted;
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
    pa_portal_seed_v25_samples();
}

function pa_portal_seed_v25_samples(): void {
    pa_portal_records_ensure_table();
    global $wpdb;
    $table = pa_portal_records_table();
    $now   = current_time('mysql');

    $putCount = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}` WHERE record_type = 'putusan'");
    if ($putCount === 0) {
        $putSeeds = array(
            array('0156/Pdt.G/2026/PA.Cth', 'Dikabulkan Sebagian — Cerai Gugat & Hak Asuh Anak', 'Cerai Gugat', wp_date('Y-m-d', time() - 3 * 86400), array('doc_url' => '', 'majelis' => 'Majelis Hakim I', 'status_hukum' => 'Berkekuatan Hukum Tetap'), 1),
            array('0089/Pdt.P/2026/PA.Cth', 'Dikabulkan — Penetapan Ahli Waris & Dispensasi', 'Permohonan (Voluntair)', wp_date('Y-m-d', time() - 7 * 86400), array('doc_url' => '', 'majelis' => 'Hakim Tunggal', 'status_hukum' => 'Berkekuatan Hukum Tetap'), 2),
            array('0204/Pdt.G/2026/PA.Cth', 'Dikabulkan — Cerai Talak (Putusan e-Litigasi)', 'Cerai Talak', wp_date('Y-m-d', time() - 12 * 86400), array('doc_url' => '', 'majelis' => 'Majelis Hakim II', 'status_hukum' => 'Berkekuatan Hukum Tetap'), 3),
        );
        foreach ($putSeeds as $ps) {
            $wpdb->insert($table, array(
                'record_type' => 'putusan',
                'title'       => $ps[0],
                'subtitle'    => $ps[1],
                'category'    => $ps[2],
                'event_date'  => $ps[3],
                'meta_json'   => wp_json_encode($ps[4]),
                'sort_order'  => $ps[5],
                'status'      => 'published',
                'updated_at'  => $now,
            ));
        }
    }

    $inoCount = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}` WHERE record_type = 'inovasi'");
    if ($inoCount === 0) {
        $inoSeeds = array(
            array('SIPANTAU — Pantau Antrean Sidang & PTSP', 'Pantauan antrean ruang sidang dan progres layanan PTSP secara real-time dari ponsel para pihak.', 'PTSP Digital', null, array('app_url' => home_url('/agenda-jadwal-persidangan/'), 'icon_url' => '', 'badge' => 'Real-Time'), 1),
            array('SIAKTA Keliling — Pengambilan Akta Cerai Terjadwal', 'Layanan penjadwalan pengambilan atau pengantaran akta cerai & salinan putusan tanpa antre.', 'Layanan Keliling', null, array('app_url' => home_url('/prosedur-berperkara/'), 'icon_url' => '', 'badge' => 'PTSP Cepat'), 2),
            array('E-PANJAR — Simulasi & Taksiran Panjar Biaya', 'Transparansi rincian radius wilayah serta estimasi panjar biaya perkara sesuai SK Ketua.', 'Transparansi Biaya', null, array('app_url' => home_url('/biaya-proses-berperkara/'), 'icon_url' => '', 'badge' => 'Akuntabel'), 3),
            array('SI-RENTAN — Layanan Prioritas Disabilitas & Lansia', 'Pendampingan khusus, antrean prioritas, serta akses ramah difabel di seluruh area layanan.', 'Layanan Inklusif', null, array('app_url' => home_url('/disabilitas/'), 'icon_url' => '', 'badge' => 'Inklusif'), 4),
        );
        foreach ($inoSeeds as $is) {
            $wpdb->insert($table, array(
                'record_type' => 'inovasi',
                'title'       => $is[0],
                'subtitle'    => $is[1],
                'category'    => $is[2],
                'event_date'  => $is[3],
                'meta_json'   => wp_json_encode($is[4]),
                'sort_order'  => $is[5],
                'status'      => 'published',
                'updated_at'  => $now,
            ));
        }
    }
    update_option('pa_portal_v25_samples_seeded', '1', false);
}

add_action('init', static function () {
    if (get_option('pa_portal_v25_samples_seeded') !== '1') {
        pa_portal_seed_v25_samples();
    }
}, 25);

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

// ============================================================================
// 2A. SINKRONISASI PEJABAT & PEGAWAI (SIMTEPA / SIKEP: API/URL + CSV + PUSH)
// ============================================================================

function pa_portal_simtepa_infer_group(string $jabatan, string $rawGroup = ''): string {
    $g = strtolower(trim($rawGroup));
    if ($g !== '') {
        if (strpos($g, 'pimpin') !== false || strpos($g, 'ketua') !== false) {
            return 'Pimpinan';
        }
        if (strpos($g, 'hakim') !== false) {
            return 'Hakim';
        }
        if (strpos($g, 'panit') !== false || strpos($g, 'jurusita') !== false) {
            return 'Kepaniteraan';
        }
        if (strpos($g, 'sekret') !== false || strpos($g, 'kasub') !== false || strpos($g, 'kesek') !== false || strpos($g, 'umum') !== false || strpos($g, 'ptip') !== false || strpos($g, 'kepeg') !== false) {
            return 'Kesekretariatan';
        }
        if (strpos($g, 'ppnpn') !== false || strpos($g, 'pppk') !== false || strpos($g, 'honorer') !== false || strpos($g, 'non') !== false) {
            return 'PPNPN';
        }
    }
    $j = strtolower(trim($jabatan));
    if (preg_match('/^(?:ketua|wakil\s+ketua)\b/i', $j) || strpos($j, 'ketua pengadilan') !== false) {
        return 'Pimpinan';
    }
    if (strpos($j, 'hakim') !== false) {
        return 'Hakim';
    }
    if (preg_match('/panitera|jurusita|juru\s*sita|pp\b|kepaniteraan/i', $j)) {
        return 'Kepaniteraan';
    }
    if (preg_match('/ppnpn|pramubakti|satpam|pengemudi|supir|tenaga\s+kontrak|cleaning|security/i', $j)) {
        return 'PPNPN';
    }
    return 'Kesekretariatan';
}

function pa_portal_simtepa_normalize_row(array $raw, string $baseUrl = ''): ?array {
    $lower = array();
    foreach ($raw as $k => $v) {
        $normKey = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim((string) $k)));
        $normKey = trim((string) $normKey, '_');
        if ($normKey !== '' && !isset($lower[$normKey])) {
            $lower[$normKey] = $v;
        }
    }
    $pick = static function (array $keys) use ($lower): string {
        foreach ($keys as $k) {
            if (!isset($lower[$k])) {
                continue;
            }
            $val = $lower[$k];
            if (is_array($val)) {
                $flat = array();
                foreach ($val as $sub) {
                    if (is_scalar($sub) && trim((string) $sub) !== '') {
                        $flat[] = trim((string) $sub);
                    }
                }
                if (!empty($flat)) {
                    return implode('; ', $flat);
                }
                continue;
            }
            $s = trim((string) $val);
            if ($s !== '' && $s !== '-' && strtolower($s) !== 'null') {
                return $s;
            }
        }
        return '';
    };

    $name = $pick(array('nama_lengkap', 'namalengkap', 'nama_pegawai', 'nama', 'name', 'full_name'));
    if ($name !== '') {
        $gd = $pick(array('gelar_depan', 'gelardepan'));
        $gb = $pick(array('gelar_belakang', 'gelarbelakang'));
        if ($gd !== '' && stripos($name, $gd) !== 0) {
            $name = rtrim($gd) . ' ' . $name;
        }
        if ($gb !== '' && stripos($name, $gb) === false) {
            $name = rtrim($name, ', ') . ', ' . ltrim($gb, ', ');
        }
    }
    $name = sanitize_text_field($name);
    if ($name === '' || strlen($name) < 2) {
        return null;
    }

    $jabatan = sanitize_text_field($pick(array('nama_jabatan_lengkap', 'namajabatanlengkap', 'nama_jabatan', 'jabatan', 'role', 'position', 'subtitle')));
    $rawGrp  = $pick(array('kelompok', 'group', 'unit', 'unit_kerja', 'bagian', 'category', 'kategori'));
    $group   = pa_portal_simtepa_infer_group($jabatan, $rawGrp);

    $nip     = sanitize_text_field($pick(array('nip_baru', 'nipbaru', 'nip', 'nrp', 'nik')));
    $gol     = sanitize_text_field($pick(array('pangkat_golongan', 'pangkatgolongan', 'pangkat_gol', 'gol_ruang', 'golongan', 'gol')));
    $pangkat = sanitize_text_field($pick(array('pangkat', 'nama_pangkat')));
    if ($pangkat !== '' && $gol !== '' && stripos($gol, $pangkat) === false) {
        $gol = $pangkat . ' (' . $gol . ')';
    } elseif ($gol === '' && $pangkat !== '') {
        $gol = $pangkat;
    }

    $edu  = sanitize_text_field($pick(array('pendidikan_terakhir', 'pendidikanterakhir', 'pendidikan', 'tingkat_pendidikan', 'edu')));
    $tmt  = sanitize_text_field($pick(array('tmt_jabatan', 'tmtjabatan', 'tmt_pangkat', 'tmt')));
    $rEdu = sanitize_textarea_field($pick(array('riwayat_pendidikan', 'riwayatpendidikan')));
    $rJab = sanitize_textarea_field($pick(array('riwayat_jabatan', 'riwayatjabatan', 'riwayat_diklat')));

    $photoRaw = $pick(array('foto_url', 'url_foto', 'foto', 'photo', 'photo_url', 'avatar', 'gambar'));
    $photo    = '';
    if ($photoRaw !== '') {
        if (preg_match('#^https?://#i', $photoRaw)) {
            $photo = esc_url_raw($photoRaw, array('http', 'https'));
        } elseif ($baseUrl !== '') {
            $bp = parse_url($baseUrl);
            if (!empty($bp['scheme']) && !empty($bp['host'])) {
                $org   = $bp['scheme'] . '://' . $bp['host'] . (!empty($bp['port']) ? ':' . $bp['port'] : '');
                $photo = (strpos($photoRaw, '/') === 0)
                    ? esc_url_raw($org . $photoRaw, array('http', 'https'))
                    : esc_url_raw(rtrim($baseUrl, '/') . '/' . ltrim($photoRaw, '/'), array('http', 'https'));
            }
        }
    }

    return array(
        'title'    => $name,
        'subtitle' => $jabatan !== '' ? $jabatan : 'Aparatur Pengadilan',
        'category' => $group,
        'meta'     => array(
            'nip'                => $nip,
            'gol'                => $gol,
            'edu'                => $edu,
            'photo'              => $photo,
            'tmt'                => $tmt,
            'riwayat_pendidikan' => $rEdu,
            'riwayat_jabatan'    => $rJab,
        ),
    );
}

function pa_portal_parse_simtepa_payload(string $body, string $baseUrl = '', int $limit = 200): array {
    $raw = trim($body);
    if ($raw === '') {
        return array('status' => 'error', 'items' => array(), 'message' => 'Respons dari endpoint SIMTEPA kosong.');
    }

    // 1. Coba parse JSON (Web Service SIKEP/SIMTEPA MA RI atau bridge JSON satker).
    $firstCh = substr($raw, 0, 1);
    if ($firstCh === '{' || $firstCh === '[') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $rows = array();
            if (array_keys($decoded) === range(0, count($decoded) - 1)) {
                $rows = $decoded;
            } else {
                foreach (array('data', 'pegawai', 'items', 'results', 'rows', 'list') as $listKey) {
                    if (isset($decoded[$listKey]) && is_array($decoded[$listKey])) {
                        $rows = $decoded[$listKey];
                        break;
                    }
                }
            }
            $items = array();
            $seen  = array();
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $norm = pa_portal_simtepa_normalize_row($row, $baseUrl);
                if ($norm === null) {
                    continue;
                }
                $dk = strtolower(($norm['meta']['nip'] !== '' ? $norm['meta']['nip'] : $norm['title']));
                if (isset($seen[$dk])) {
                    continue;
                }
                $seen[$dk] = true;
                $items[]   = $norm;
                if (count($items) >= $limit) {
                    break;
                }
            }
            if (!empty($items)) {
                return array(
                    'status'  => 'ok',
                    'items'   => $items,
                    'message' => 'Berhasil membaca ' . count($items) . ' profil pegawai (JSON SIMTEPA/SIKEP).',
                );
            }
            return array(
                'status'  => 'empty',
                'items'   => array(),
                'message' => 'Endpoint JSON SIMTEPA terhubung tetapi daftar pegawai kosong atau struktur kolom tidak dikenali.',
            );
        }
    }

    // 2. Deteksi halaman login SSO Badilag / Cloudflare tanpa mencoba melewati autentikasi.
    if (
        stripos($raw, 'cf-browser-verification') !== false ||
        stripos($raw, 'challenges.cloudflare.com') !== false ||
        (stripos($raw, '<title>Just a moment') !== false && stripos($raw, 'nip') === false)
    ) {
        return array(
            'status'  => 'blocked',
            'items'   => array(),
            'message' => 'URL SIMTEPA diblokir verifikasi Cloudflare/WAF. Gunakan Impor CSV ekspor SIMTEPA atau Push API HMAC.',
        );
    }
    if (
        preg_match('/<input[^>]+type=["\']password["\']/i', $raw) &&
        stripos($raw, '<table') === false
    ) {
        return array(
            'status'  => 'auth_required',
            'items'   => array(),
            'message' => 'URL SIMTEPA memerlukan login SSO Badilag. Gunakan endpoint JSON/Token satker, Push API HMAC, atau Impor CSV dari ekspor SIMTEPA.',
        );
    }

    // 3. Fallback parse tabel HTML publik (halaman profil pegawai satker / bridge HTML SIMTEPA).
    if (!class_exists('DOMDocument')) {
        return array('status' => 'error', 'items' => array(), 'message' => 'Ekstensi PHP DOMDocument tidak aktif.');
    }
    $prevErr = libxml_use_internal_errors(true);
    $dom     = new DOMDocument();
    $loaded  = @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $raw, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    libxml_use_internal_errors($prevErr);
    if (!$loaded) {
        return array('status' => 'error', 'items' => array(), 'message' => 'Gagal membaca dokumen HTML SIMTEPA.');
    }

    $xpath  = new DOMXPath($dom);
    $tables = $xpath->query('//table');
    $items  = array();
    $seen   = array();
    if ($tables && $tables->length > 0) {
        foreach ($tables as $tbl) {
            $trs = $xpath->query('.//tr', $tbl);
            if (!$trs || $trs->length < 2) {
                continue;
            }
            $colMap    = array();
            $headerIdx = -1;
            for ($ri = 0; $ri < min(3, $trs->length); $ri++) {
                $cells = $xpath->query('./th|./td', $trs->item($ri));
                if (!$cells || $cells->length < 2) {
                    continue;
                }
                $tmp = array();
                for ($ci = 0; $ci < $cells->length; $ci++) {
                    $ht = strtolower(trim(preg_replace('/\s+/', ' ', (string) $cells->item($ci)->textContent)));
                    if (preg_match('/nama/', $ht)) {
                        $tmp['nama'] = $ci;
                    } elseif (preg_match('/jabatan/', $ht)) {
                        $tmp['jabatan'] = $ci;
                    } elseif (preg_match('/\bnip\b/', $ht)) {
                        $tmp['nip'] = $ci;
                    } elseif (preg_match('/pangkat|gol/', $ht)) {
                        $tmp['pangkat_gol'] = $ci;
                    } elseif (preg_match('/pendidikan/', $ht)) {
                        $tmp['pendidikan'] = $ci;
                    } elseif (preg_match('/kelompok|unit|bagian/', $ht)) {
                        $tmp['kelompok'] = $ci;
                    } elseif (preg_match('/tmt/', $ht)) {
                        $tmp['tmt'] = $ci;
                    } elseif (preg_match('/foto|photo/', $ht)) {
                        $tmp['foto'] = $ci;
                    }
                }
                if (isset($tmp['nama']) && (isset($tmp['jabatan']) || isset($tmp['nip']))) {
                    $colMap    = $tmp;
                    $headerIdx = $ri;
                    break;
                }
            }
            if (empty($colMap)) {
                continue;
            }
            for ($ri = $headerIdx + 1; $ri < $trs->length; $ri++) {
                $tr    = $trs->item($ri);
                $cells = $xpath->query('./td', $tr);
                if (!$cells || $cells->length < 2) {
                    continue;
                }
                $rowArr = array();
                foreach ($colMap as $field => $ci) {
                    if ($ci >= $cells->length) {
                        continue;
                    }
                    if ($field === 'foto') {
                        $imgs = $xpath->query('.//img[@src]', $cells->item($ci));
                        if ($imgs && $imgs->length > 0) {
                            $rowArr['foto'] = trim((string) $imgs->item(0)->getAttribute('src'));
                        }
                    } else {
                        $rowArr[$field] = trim(preg_replace('/\s+/', ' ', (string) $cells->item($ci)->textContent));
                    }
                }
                if (empty($rowArr['foto'])) {
                    $imgs = $xpath->query('.//img[@src]', $tr);
                    if ($imgs && $imgs->length > 0) {
                        $rowArr['foto'] = trim((string) $imgs->item(0)->getAttribute('src'));
                    }
                }
                $norm = pa_portal_simtepa_normalize_row($rowArr, $baseUrl);
                if ($norm === null) {
                    continue;
                }
                $dk = strtolower(($norm['meta']['nip'] !== '' ? $norm['meta']['nip'] : $norm['title']));
                if (isset($seen[$dk])) {
                    continue;
                }
                $seen[$dk] = true;
                $items[]   = $norm;
                if (count($items) >= $limit) {
                    break 2;
                }
            }
        }
    }

    if (!empty($items)) {
        return array(
            'status'  => 'ok',
            'items'   => $items,
            'message' => 'Berhasil membaca ' . count($items) . ' profil pegawai dari tabel HTML SIMTEPA.',
        );
    }

    return array(
        'status'  => 'error',
        'items'   => array(),
        'message' => 'Format data pegawai SIMTEPA tidak dikenali (bukan JSON/tabel profil standar). Gunakan Impor CSV ekspor SIMTEPA atau Input Manual.',
    );
}

function pa_portal_parse_simtepa_csv(string $csvContent, int $limit = 250): array {
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent);
    $raw = str_replace(array("\r\n", "\r"), "\n", (string) $raw);
    $lines = array_values(array_filter(explode("\n", $raw), static function ($l) {
        return trim($l) !== '';
    }));
    if (count($lines) < 2) {
        return array('status' => 'error', 'items' => array(), 'message' => 'Berkas CSV kosong atau hanya berisi header tanpa baris pegawai.');
    }

    $firstLine = $lines[0];
    $delim     = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
    $headers   = array_map('trim', str_getcsv($firstLine, $delim));
    if (count($headers) < 2) {
        return array('status' => 'error', 'items' => array(), 'message' => 'Kolom CSV tidak terbaca. Pastikan pemisah koma (,) atau titik koma (;).');
    }

    $items = array();
    $seen  = array();
    for ($i = 1; $i < count($lines); $i++) {
        $cols = str_getcsv($lines[$i], $delim);
        if (empty($cols)) {
            continue;
        }
        $assoc = array();
        foreach ($headers as $idx => $hName) {
            if ($hName !== '' && array_key_exists($idx, $cols)) {
                $assoc[$hName] = trim((string) $cols[$idx]);
            }
        }
        $norm = pa_portal_simtepa_normalize_row($assoc, '');
        if ($norm === null) {
            continue;
        }
        $dk = strtolower(($norm['meta']['nip'] !== '' ? $norm['meta']['nip'] : $norm['title']));
        if (isset($seen[$dk])) {
            continue;
        }
        $seen[$dk] = true;
        $items[]   = $norm;
        if (count($items) >= $limit) {
            break;
        }
    }

    if (empty($items)) {
        return array('status' => 'error', 'items' => array(), 'message' => 'Tidak ada baris pegawai valid di CSV. Pastikan terdapat kolom "Nama" dan "Jabatan".');
    }

    return array(
        'status'  => 'ok',
        'items'   => $items,
        'message' => 'Berhasil membaca ' . count($items) . ' data pegawai dari berkas CSV SIMTEPA.',
    );
}

function pa_portal_upsert_simtepa_records(array $items, string $source = 'simtepa', bool $replaceOldSync = true): int {
    if (empty($items)) {
        return 0;
    }
    pa_portal_records_ensure_table();
    global $wpdb;
    $rTable = pa_portal_records_table();
    $nowSql = current_time('mysql');

    // Bersihkan data pegawai contoh bawaan (Ahmad Contoh / Siti Amanah / Budi Santoso) saat sinkronisasi berhasil.
    $wpdb->query(
        "DELETE FROM `{$rTable}` WHERE record_type = 'pegawai' AND (title LIKE '%Ahmad Contoh%' OR title LIKE '%Siti Amanah%' OR meta_json LIKE '%19700101 199503 1 001%' OR meta_json LIKE '%19720512 199803 2 002%' OR meta_json LIKE '%19750817 200003 1 003%')"
    );
    if ($replaceOldSync) {
        $wpdb->query($wpdb->prepare(
            "DELETE FROM `{$rTable}` WHERE record_type = 'pegawai' AND meta_json LIKE %s",
            '%"source":"' . $wpdb->esc_like($source) . '"%'
        ));
    }

    $groupRank = array(
        'Pimpinan'        => 10,
        'Hakim'           => 20,
        'Kepaniteraan'    => 30,
        'Kesekretariatan' => 40,
        'PPNPN'           => 50,
    );

    $saved = 0;
    foreach ($items as $idx => $it) {
        $meta           = is_array($it['meta'] ?? null) ? $it['meta'] : array();
        $meta['source'] = $source;
        $grp            = (string) ($it['category'] ?? 'Kesekretariatan');
        $sort           = (int) ($groupRank[$grp] ?? 60) * 100 + ($idx + 1);
        $title          = (string) ($it['title'] ?? '');
        $nip            = (string) ($meta['nip'] ?? '');

        $existingId = 0;
        if (!$replaceOldSync) {
            if ($nip !== '') {
                $existingId = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM `{$rTable}` WHERE record_type = 'pegawai' AND meta_json LIKE %s LIMIT 1",
                    '%"nip":"' . $wpdb->esc_like($nip) . '"%'
                ));
            }
            if ($existingId <= 0) {
                $existingId = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM `{$rTable}` WHERE record_type = 'pegawai' AND title = %s LIMIT 1",
                    $title
                ));
            }
        }

        $rowPayload = array(
            'record_type' => 'pegawai',
            'title'       => $title,
            'subtitle'    => (string) ($it['subtitle'] ?? 'Aparatur Pengadilan'),
            'category'    => $grp,
            'event_date'  => wp_date('Y-m-d'),
            'meta_json'   => wp_json_encode($meta) ?: '{}',
            'sort_order'  => $sort,
            'status'      => 'published',
            'updated_at'  => $nowSql,
        );

        if ($existingId > 0) {
            $wpdb->update($rTable, $rowPayload, array('id' => $existingId));
            $saved++;
        } elseif ($wpdb->insert($rTable, $rowPayload)) {
            $saved++;
        }
    }

    if (function_exists('pa_sinova_purge_html_cache_files')) {
        pa_sinova_purge_html_cache_files();
    } elseif (function_exists('pa_home_page_cache_mark_stale')) {
        pa_home_page_cache_mark_stale();
    }
    return $saved;
}

function pa_portal_get_simtepa_sync_status(): array {
    $st = get_option('pa_simtepa_sync_status', array());
    return is_array($st) ? $st : array();
}

function pa_portal_sync_simtepa_remote(bool $manual = false): array {
    $cfg      = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $url      = trim((string) ($cfg['simtepa_url'] ?? ''));
    $token    = trim((string) ($cfg['simtepa_token'] ?? ''));
    $nowLabel = wp_date('d-m-Y H:i') . ' WIB';

    if ($url === '') {
        $payload = array(
            'status'     => 'unconfigured',
            'count'      => 0,
            'source_url' => '',
            'synced_at'  => $nowLabel,
            'timestamp'  => time(),
            'message'    => 'URL endpoint SIMTEPA/SIKEP belum diisi. Anda dapat mengisi URL API/JSON SIMTEPA, memakai Impor CSV ekspor SIMTEPA, atau mengisi secara manual.',
        );
        update_option('pa_simtepa_sync_status', $payload, false);
        return $payload;
    }

    $parts  = parse_url($url);
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host   = strtolower((string) ($parts['host'] ?? ''));
    $allowed = array('mahkamahagung.go.id', 'badilag.net', 'go.id');
    $hostOk  = false;
    foreach ($allowed as $sfx) {
        if ($host === $sfx || (strlen($host) > strlen($sfx) && substr($host, -strlen($sfx) - 1) === '.' . $sfx)) {
            $hostOk = true;
            break;
        }
    }
    if (!in_array($scheme, array('http', 'https'), true) || !$hostOk) {
        $payload = array(
            'status'     => 'error',
            'count'      => 0,
            'source_url' => $url,
            'synced_at'  => $nowLabel,
            'timestamp'  => time(),
            'message'    => 'URL SIMTEPA ditolak pelindung SSRF: wajib memakai domain resmi .go.id atau badilag.net.',
        );
        update_option('pa_simtepa_sync_status', $payload, false);
        return $payload;
    }

    $ips = @gethostbynamel($host);
    if (!is_array($ips) || empty($ips)) {
        $payload = array(
            'status'     => 'error',
            'count'      => 0,
            'source_url' => $url,
            'synced_at'  => $nowLabel,
            'timestamp'  => time(),
            'message'    => 'Host SIMTEPA tidak dapat diresolusi dari server hosting.',
        );
        update_option('pa_simtepa_sync_status', $payload, false);
        return $payload;
    }
    foreach ($ips as $ip) {
        if (!pa_portal_is_public_ip((string) $ip)) {
            $payload = array(
                'status'     => 'error',
                'count'      => 0,
                'source_url' => $url,
                'synced_at'  => $nowLabel,
                'timestamp'  => time(),
                'message'    => 'IP privat/lokal ditolak pelindung SSRF.',
            );
            update_option('pa_simtepa_sync_status', $payload, false);
            return $payload;
        }
    }

    $headers = array(
        'Accept' => 'application/json, text/html;q=0.9',
    );
    if ($token !== '') {
        $headers['Authorization'] = 'Bearer ' . $token;
        $headers['token']         = $token;
    }

    $resp = wp_remote_get($url, array(
        'timeout'             => 12,
        'redirection'         => 2,
        'limit_response_size' => 2097152,
        'user-agent'          => 'PA-SINOVA-SIMTEPA/2.5',
        'headers'             => $headers,
    ));
    if (is_wp_error($resp)) {
        $payload = array(
            'status'     => 'error',
            'count'      => 0,
            'source_url' => $url,
            'synced_at'  => $nowLabel,
            'timestamp'  => time(),
            'message'    => 'Gagal menghubungi endpoint SIMTEPA: ' . $resp->get_error_message(),
        );
        update_option('pa_simtepa_sync_status', $payload, false);
        return $payload;
    }

    $code = (int) wp_remote_retrieve_response_code($resp);
    $body = (string) wp_remote_retrieve_body($resp);
    if ($code === 401 || $code === 403) {
        $payload = array(
            'status'     => 'auth_required',
            'count'      => 0,
            'source_url' => $url,
            'synced_at'  => $nowLabel,
            'timestamp'  => time(),
            'message'    => "Endpoint SIMTEPA mengembalikan HTTP {$code} (butuh token otorisasi / IP whitelist). Gunakan Impor CSV ekspor SIMTEPA atau Push API HMAC.",
        );
        update_option('pa_simtepa_sync_status', $payload, false);
        return $payload;
    }
    if ($code !== 200 || trim($body) === '') {
        $payload = array(
            'status'     => 'error',
            'count'      => 0,
            'source_url' => $url,
            'synced_at'  => $nowLabel,
            'timestamp'  => time(),
            'message'    => "Endpoint SIMTEPA mengembalikan HTTP {$code}.",
        );
        update_option('pa_simtepa_sync_status', $payload, false);
        return $payload;
    }

    $parsed = pa_portal_parse_simtepa_payload($body, $url, 200);
    if (($parsed['status'] ?? '') === 'ok' && !empty($parsed['items'])) {
        $saved   = pa_portal_upsert_simtepa_records($parsed['items'], 'simtepa', true);
        $payload = array(
            'status'     => 'ok',
            'count'      => $saved,
            'source_url' => $url,
            'synced_at'  => $nowLabel,
            'timestamp'  => time(),
            'message'    => "Berhasil menyinkronkan {$saved} profil pejabat & pegawai dari SIMTEPA.",
        );
        update_option('pa_simtepa_sync_status', $payload, false);
        set_transient('pa_simtepa_sync_fresh', 1, DAY_IN_SECONDS);
        return $payload;
    }

    $payload = array(
        'status'     => (string) ($parsed['status'] ?? 'error'),
        'count'      => 0,
        'source_url' => $url,
        'synced_at'  => $nowLabel,
        'timestamp'  => time(),
        'message'    => (string) ($parsed['message'] ?? 'Gagal memproses data SIMTEPA.'),
    );
    update_option('pa_simtepa_sync_status', $payload, false);
    set_transient('pa_simtepa_sync_fresh', 1, $manual ? 300 : HOUR_IN_SECONDS * 6);
    return $payload;
}

add_action('pa_simtepa_cron_sync', static function () {
    $cfg = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    if (($cfg['simtepa_autosync'] ?? '1') === '0' || trim((string) ($cfg['simtepa_url'] ?? '')) === '') {
        return;
    }
    pa_portal_sync_simtepa_remote(false);
});

function pa_portal_maybe_schedule_simtepa_sync(): void {
    $cfg = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $on  = (($cfg['simtepa_autosync'] ?? '1') !== '0') && trim((string) ($cfg['simtepa_url'] ?? '')) !== '';
    $nxt = wp_next_scheduled('pa_simtepa_cron_sync');
    if (!$on) {
        if ($nxt) {
            wp_unschedule_event($nxt, 'pa_simtepa_cron_sync');
        }
        return;
    }
    if (!$nxt) {
        wp_schedule_event(time() + 300, 'daily', 'pa_simtepa_cron_sync');
    }
}
add_action('init', static function () {
    if (!wp_doing_ajax()) {
        pa_portal_maybe_schedule_simtepa_sync();
    }
}, 31);

add_shortcode('pa_pegawai_grid', static function () {
    $rows        = pa_portal_get_records('pegawai', 150);
    $groups      = array('Semua', 'Pimpinan', 'Hakim', 'Kepaniteraan', 'Kesekretariatan', 'PPNPN');
    $groupCounts = array_fill_keys($groups, 0);
    $hasSimtepa  = false;
    foreach ($rows as $r) {
        $groupCounts['Semua']++;
        $gLow = strtolower((string) ($r['category'] ?? ''));
        foreach ($groups as $g) {
            if ($g !== 'Semua' && strpos($gLow, strtolower($g)) !== false) {
                $groupCounts[$g]++;
            }
        }
        $m = json_decode((string) ($r['meta_json'] ?? ''), true);
        if (is_array($m) && in_array((string) ($m['source'] ?? ''), array('simtepa', 'simtepa_csv', 'simtepa_push'), true)) {
            $hasSimtepa = true;
        }
    }
    ob_start();
    ?>
    <div class="pa-ops-wrap pa-pegawai-dir" id="pa-pegawai-board">
        <div class="pa-peg-toolbar">
            <div class="pa-ops-filter-tabs pa-peg-tabs" role="tablist" aria-label="Filter Kelompok Pegawai">
                <?php foreach ($groups as $idx => $g) :
                    $tid = 'pa-peg-tab-' . sanitize_key($g);
                    $cnt = (int) ($groupCounts[$g] ?? 0);
                ?>
                    <button type="button" role="tab" id="<?php echo esc_attr($tid); ?>" aria-selected="<?php echo $idx === 0 ? 'true' : 'false'; ?>" tabindex="<?php echo $idx === 0 ? '0' : '-1'; ?>" class="pa-ops-tab<?php echo $idx === 0 ? ' is-active' : ''; ?>" data-pegawai-filter="<?php echo esc_attr($g); ?>">
                        <span><?php echo esc_html($g); ?></span>
                        <span class="pa-peg-tab-cnt"><?php echo $cnt; ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="pa-peg-tools-right">
                <input type="search" id="pa-pegawai-search" class="pa-ops-search pa-peg-search" aria-label="Cari nama, jabatan, NIP, atau pangkat pegawai" placeholder="Cari nama, jabatan, atau NIP...">
                <?php if ($hasSimtepa) : ?>
                    <span class="pa-ops-badge pa-peg-sync-badge">Tersinkron SIMTEPA</span>
                <?php endif; ?>
            </div>
        </div>
        <p id="pa-pegawai-status" class="screen-reader-text" aria-live="polite"></p>
        <div class="pa-pegawai-grid" role="tabpanel">
            <?php foreach ($rows as $r) :
                $meta = json_decode((string) ($r['meta_json'] ?? ''), true);
                if (!is_array($meta)) {
                    $meta = array();
                }
                $name     = trim((string) ($r['title'] ?? ''));
                $role     = trim((string) ($r['subtitle'] ?? ''));
                $group    = trim((string) ($r['category'] ?? '')) ?: 'Aparatur';
                $grpSlug  = sanitize_key($group);
                $words    = preg_split('/\s+/', trim((string) preg_replace('/^(?:Prof\.|Dr\.|Dra\.|Drs\.|H\.|Hj\.|Ir\.|K\.H\.)\s*/i', '', $name)));
                $initials = strtoupper(substr((string) ($words[0] ?? 'P'), 0, 1) . substr((string) ($words[1] ?? ''), 0, 1));
                $photo    = esc_url_raw((string) ($meta['photo'] ?? ''), array('http', 'https'));
                $nip      = trim((string) ($meta['nip'] ?? ''));
                $gol      = trim((string) ($meta['gol'] ?? ''));
                $edu      = trim((string) ($meta['edu'] ?? ''));
                $tmt      = trim((string) ($meta['tmt'] ?? ''));
                $searchHay = strtolower($name . ' ' . $role . ' ' . $group . ' ' . $nip . ' ' . $gol . ' ' . $edu);
            ?>
                <article class="pa-pegawai-card" data-group="<?php echo esc_attr($group); ?>" data-grp-key="<?php echo esc_attr($grpSlug); ?>" data-search="<?php echo esc_attr($searchHay); ?>">
                    <header class="pa-peg-head">
                        <div class="pa-pegawai-avatar">
                            <?php if ($photo !== '') : ?>
                                <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($name); ?>" width="64" height="64" loading="lazy" decoding="async">
                            <?php else : ?>
                                <span aria-hidden="true"><?php echo esc_html($initials ?: 'PA'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="pa-pegawai-info">
                            <span class="pa-pegawai-cat is-grp-<?php echo esc_attr($grpSlug); ?>"><?php echo esc_html($group); ?></span>
                            <h3><?php echo esc_html($name); ?></h3>
                            <p class="pa-pegawai-role"><?php echo esc_html($role ?: 'Aparatur Pengadilan'); ?></p>
                        </div>
                    </header>
                    <dl class="pa-peg-meta">
                        <?php if ($nip !== '') : ?>
                            <div class="pa-peg-meta-row"><dt>NIP</dt><dd class="pa-peg-nip"><?php echo esc_html($nip); ?></dd></div>
                        <?php endif; ?>
                        <?php if ($gol !== '') : ?>
                            <div class="pa-peg-meta-row"><dt>Pangkat / Gol.</dt><dd><?php echo esc_html($gol); ?></dd></div>
                        <?php endif; ?>
                        <?php if ($edu !== '') : ?>
                            <div class="pa-peg-meta-row"><dt>Pendidikan</dt><dd><?php echo esc_html($edu); ?></dd></div>
                        <?php endif; ?>
                        <?php if ($nip === '' && $gol === '' && $edu === '') : ?>
                            <div class="pa-peg-meta-row"><dt>Unit Kerja</dt><dd><?php echo esc_html($group); ?></dd></div>
                        <?php endif; ?>
                    </dl>
                    <footer class="pa-peg-foot">
                        <button type="button" class="pa-peg-detail-btn" data-peg-detail="<?php echo esc_attr(wp_json_encode(array(
                            'name'  => $name,
                            'role'  => $role,
                            'group' => $group,
                            'photo' => $photo,
                            'init'  => $initials ?: 'PA',
                            'nip'   => $nip,
                            'gol'   => $gol,
                            'tmt'   => $tmt,
                            'edu'   => $edu,
                            'r_edu' => (string) ($meta['riwayat_pendidikan'] ?? ''),
                            'r_jab' => (string) ($meta['riwayat_jabatan'] ?? ''),
                        )) ?: '{}'); ?>">Lihat Profil &amp; Riwayat<span class="screen-reader-text"> <?php echo esc_html($name); ?></span> <span aria-hidden="true">→</span></button>
                    </footer>
                </article>
            <?php endforeach; ?>
        </div>
        <p id="pa-pegawai-empty" class="pa-peg-empty" hidden>Tidak ada profil pejabat atau pegawai yang sesuai dengan filter atau pencarian Anda.</p>
        <dialog id="pa-pegawai-dlg" class="pa-peg-dlg" closedby="any" aria-labelledby="pa-peg-dlg-name">
            <div class="pa-peg-dlg-head">
                <h1 id="pa-peg-dlg-name">Profil Pejabat / Pegawai</h1>
                <form method="dialog"><button type="submit" class="pa-peg-dlg-close" aria-label="Tutup profil">✕</button></form>
            </div>
            <div class="pa-peg-dlg-body" id="pa-peg-dlg-body"></div>
        </dialog>
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

// ============================================================================
// 2B. AUTO-SYNC JADWAL SIDANG DARI URL PUBLIK SIPP WEB (list_jadwal_sidang)
// ============================================================================

function pa_portal_sipp_resolve_schedule_urls(string $overrideUrl = ''): array {
    $cfg    = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $rawUrl = trim($overrideUrl !== '' ? $overrideUrl : (string) ($cfg['sipp_url'] ?? ''));
    if ($rawUrl === '') {
        return array(
            'valid'       => false,
            'base_url'    => '',
            'primary_url' => '',
            'candidates'  => array(),
            'reason'      => 'URL SIPP Satker belum diisi di menu Identitas & Seeder.',
        );
    }

    $parts  = parse_url($rawUrl);
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host   = strtolower((string) ($parts['host'] ?? ''));
    if (!in_array($scheme, array('http', 'https'), true) || $host === '') {
        return array(
            'valid'       => false,
            'base_url'    => '',
            'primary_url' => '',
            'candidates'  => array(),
            'reason'      => 'Format URL SIPP tidak valid. Gunakan format https://sipp.pa-satker.go.id/.',
        );
    }
    if (stripos($host, 'pa-contoh') !== false || stripos($host, 'example.') !== false) {
        return array(
            'valid'       => false,
            'base_url'    => $scheme . '://' . $host,
            'primary_url' => $scheme . '://' . $host . '/list_jadwal_sidang',
            'candidates'  => array(),
            'reason'      => 'URL SIPP masih memakai contoh (pa-contoh.go.id). Lengkapi URL SIPP resmi satker di Identitas & Seeder.',
        );
    }

    $port     = !empty($parts['port']) ? ':' . (int) $parts['port'] : '';
    $path     = trim((string) ($parts['path'] ?? ''), '/');
    $basePath = '';
    if ($path !== '') {
        $cleanPath = preg_replace('#/(?:index\.php/)?(?:list_jadwal_sidang|jadwal_sidang|list_perkara|Slide_sidang|slide_sidang)(?:/.*)?$#i', '', '/' . $path);
        $cleanPath = preg_replace('#^/(?:index\.php/)?(?:list_jadwal_sidang|jadwal_sidang|list_perkara|Slide_sidang|slide_sidang)(?:/.*)?$#i', '', (string) $cleanPath);
        $basePath  = rtrim((string) $cleanPath, '/');
    }
    $origin = $scheme . '://' . $host . $port . $basePath;

    $candidates = array();
    if (preg_match('#(?:list_jadwal_sidang|jadwal_sidang)#i', $rawUrl)) {
        $candidates[] = $rawUrl;
    }
    $candidates[] = $origin . '/list_jadwal_sidang';
    $candidates[] = $origin . '/jadwal_sidang';
    $candidates[] = $origin . '/index.php/list_jadwal_sidang';
    $candidates   = array_values(array_unique($candidates));

    return array(
        'valid'       => true,
        'base_url'    => $origin,
        'primary_url' => $candidates[0],
        'candidates'  => $candidates,
        'reason'      => '',
    );
}

function pa_portal_parse_id_schedule_date(string $raw): string {
    $clean = trim(preg_replace('/\s+/', ' ', $raw));
    if ($clean === '') {
        return wp_date('Y-m-d');
    }
    if (preg_match('/\b(20\d{2})[-\/.](\d{1,2})[-\/.](\d{1,2})\b/', $clean, $m)) {
        return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
    }
    if (preg_match('/\b(\d{1,2})[-\/.](\d{1,2})[-\/.](20\d{2})\b/', $clean, $m)) {
        return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
    }
    $months = array(
        'jan' => 1, 'januari' => 1, 'january' => 1,
        'feb' => 2, 'februari' => 2, 'february' => 2,
        'mar' => 3, 'maret' => 3, 'march' => 3,
        'apr' => 4, 'april' => 4,
        'mei' => 5, 'may' => 5,
        'jun' => 6, 'juni' => 6, 'june' => 6,
        'jul' => 7, 'juli' => 7, 'july' => 7,
        'agu' => 8, 'agt' => 8, 'agustus' => 8, 'aug' => 8, 'august' => 8,
        'sep' => 9, 'sept' => 9, 'september' => 9,
        'okt' => 10, 'oktober' => 10, 'oct' => 10, 'october' => 10,
        'nov' => 11, 'nop' => 11, 'november' => 11,
        'des' => 12, 'desember' => 12, 'dec' => 12, 'december' => 12,
    );
    if (preg_match('/\b(\d{1,2})\s+([A-Za-z]{3,10})\.?\s+(20\d{2})\b/', $clean, $m)) {
        $monKey = strtolower($m[2]);
        if (isset($months[$monKey])) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], $months[$monKey], (int) $m[1]);
        }
    }
    return wp_date('Y-m-d');
}

function pa_portal_parse_sipp_web_html(string $html, string $baseUrl = '', int $limit = 120): array {
    $raw = trim($html);
    if ($raw === '') {
        return array('status' => 'error', 'items' => array(), 'message' => 'Respons HTML dari SIPP Web kosong.');
    }

    // Deteksi halus halaman tantangan Cloudflare / WAF (tidak mencoba melewati proteksi).
    $isChallenge = (
        stripos($raw, 'cf-browser-verification') !== false ||
        stripos($raw, 'challenges.cloudflare.com') !== false ||
        stripos($raw, '__cf_chl_') !== false ||
        (stripos($raw, '<title>Just a moment') !== false && stripos($raw, 'nomor perkara') === false) ||
        (stripos($raw, 'Attention Required! | Cloudflare') !== false && stripos($raw, 'nomor perkara') === false)
    );
    if ($isChallenge) {
        return array(
            'status'  => 'blocked',
            'items'   => array(),
            'message' => 'SIPP Web dilindungi verifikasi Cloudflare/WAF terhadap akses server hosting. Gunakan Push API SIPP atau Input Manual (fallback).',
        );
    }

    if (!class_exists('DOMDocument')) {
        return array('status' => 'error', 'items' => array(), 'message' => 'Ekstensi PHP DOMDocument tidak aktif di server.');
    }

    $prevErr = libxml_use_internal_errors(true);
    $dom     = new DOMDocument();
    $loaded  = @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $raw, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    libxml_use_internal_errors($prevErr);

    if (!$loaded) {
        return array('status' => 'error', 'items' => array(), 'message' => 'Gagal membaca struktur HTML SIPP Web.');
    }

    $xpath  = new DOMXPath($dom);
    $tables = $xpath->query('//table');
    if (!$tables || $tables->length === 0) {
        return array(
            'status'  => 'error',
            'items'   => array(),
            'message' => 'Tabel jadwal sidang tidak ditemukan pada halaman SIPP Web (kemungkinan dirender via JavaScript/AJAX atau halaman berbeda).',
        );
    }

    $items          = array();
    $seen           = array();
    $foundSchedule  = false;
    $emptyConfirmed = false;
    $baseParts      = $baseUrl !== '' ? parse_url($baseUrl) : array();
    $origin         = (!empty($baseParts['scheme']) && !empty($baseParts['host']))
        ? $baseParts['scheme'] . '://' . $baseParts['host'] . (!empty($baseParts['port']) ? ':' . $baseParts['port'] : '')
        : '';

    foreach ($tables as $tbl) {
        $rows = $xpath->query('.//tr', $tbl);
        if (!$rows || $rows->length === 0) {
            continue;
        }

        $colMap    = array();
        $headerIdx = -1;
        for ($ri = 0; $ri < min(3, $rows->length); $ri++) {
            $cells = $xpath->query('./th|./td', $rows->item($ri));
            if (!$cells || $cells->length < 3) {
                continue;
            }
            $tmpMap = array();
            for ($ci = 0; $ci < $cells->length; $ci++) {
                $hTxt = strtolower(trim(preg_replace('/\s+/', ' ', (string) $cells->item($ci)->textContent)));
                if (preg_match('/nomor\s*perkara|no\.?\s*perkara/', $hTxt)) {
                    $tmpMap['nomor'] = $ci;
                } elseif (preg_match('/tanggal|tgl\.?\s*sidang/', $hTxt)) {
                    $tmpMap['tanggal'] = $ci;
                } elseif (preg_match('/\bjam\b|waktu|pukul/', $hTxt)) {
                    $tmpMap['jam'] = $ci;
                } elseif (preg_match('/ruang|ruangan/', $hTxt)) {
                    $tmpMap['ruang'] = $ci;
                } elseif (preg_match('/agenda|acara|tahapan/', $hTxt)) {
                    $tmpMap['agenda'] = $ci;
                } elseif (preg_match('/keliling/', $hTxt)) {
                    $tmpMap['keliling'] = $ci;
                }
            }
            if (isset($tmpMap['nomor']) && (isset($tmpMap['agenda']) || isset($tmpMap['ruang']) || isset($tmpMap['tanggal']))) {
                $colMap    = $tmpMap;
                $headerIdx = $ri;
                $foundSchedule = true;
                break;
            }
        }

        $startRow = ($headerIdx >= 0) ? ($headerIdx + 1) : 0;
        for ($ri = $startRow; $ri < $rows->length; $ri++) {
            $tr    = $rows->item($ri);
            $cells = $xpath->query('./td', $tr);
            if (!$cells || $cells->length === 0) {
                continue;
            }
            if ($cells->length === 1) {
                $onlyTxt = strtolower(trim(preg_replace('/\s+/', ' ', (string) $cells->item(0)->textContent)));
                if ($foundSchedule && preg_match('/tidak ada|kosong|belum ada|tidak ditemukan|no data/', $onlyTxt)) {
                    $emptyConfirmed = true;
                }
                continue;
            }

            $vals = array();
            for ($ci = 0; $ci < $cells->length; $ci++) {
                $vals[$ci] = trim(preg_replace('/\s+/', ' ', (string) $cells->item($ci)->textContent));
            }

            $nomor   = '';
            $tanggal = '';
            $jam     = '';
            $ruang   = '';
            $agenda  = '';
            $kelil   = '';

            if (!empty($colMap)) {
                $nomor   = (string) ($vals[$colMap['nomor'] ?? -1] ?? '');
                $tanggal = (string) ($vals[$colMap['tanggal'] ?? -1] ?? '');
                $jam     = (string) ($vals[$colMap['jam'] ?? -1] ?? '');
                $ruang   = (string) ($vals[$colMap['ruang'] ?? -1] ?? '');
                $agenda  = (string) ($vals[$colMap['agenda'] ?? -1] ?? '');
                $kelil   = (string) ($vals[$colMap['keliling'] ?? -1] ?? '');
            } else {
                // Fallback deteksi pola nomor perkara SIPP standar (mis. 123/Pdt.G/2026/PA.Smdg).
                $nIdx = -1;
                foreach ($vals as $ci => $v) {
                    if (preg_match('/\b\d{1,5}\/[A-Za-z.]+\/20\d{2}\/[A-Za-z0-9._-]+\b/', $v)) {
                        $nIdx  = $ci;
                        $nomor = $v;
                        break;
                    }
                }
                if ($nIdx < 0) {
                    continue;
                }
                $foundSchedule = true;
                if ($nIdx > 0) {
                    $tanggal = (string) ($vals[$nIdx - 1] ?? '');
                }
                $after = array_slice($vals, $nIdx + 1);
                foreach ($after as $av) {
                    $up = strtoupper($av);
                    if ($up === 'YA' || $up === 'TIDAK') {
                        $kelil = $up;
                    } elseif ($jam === '' && preg_match('/^\d{1,2}[:.]\d{2}(?:\s*WIB|WITA|WIT)?$/i', $av)) {
                        $jam = $av;
                    } elseif ($ruang === '' && preg_match('/ruang|sidang\s*\d|utama|mediasi|aula/i', $av)) {
                        $ruang = $av;
                    } elseif ($agenda === '' && $av !== '' && !preg_match('/^(?:detil|detail|lihat)$/i', $av)) {
                        $agenda = $av;
                    }
                }
            }

            $nomor = sanitize_text_field($nomor);
            if ($nomor === '' || strlen($nomor) < 5) {
                continue;
            }
            if (preg_match('/^(?:no\.?|nomor\s*perkara)$/i', $nomor)) {
                continue;
            }

            $eventDate = pa_portal_parse_id_schedule_date($tanggal);
            if ($jam === '' && preg_match('/\b(\d{1,2}[:.]\d{2})\b/', $tanggal . ' ' . $agenda, $jm)) {
                $jam = $jm[1] . ' WIB';
            }
            $jam = sanitize_text_field($jam !== '' ? $jam : '09.00 WIB');
            if ($jam !== '' && stripos($jam, 'WI') === false && preg_match('/\d{1,2}[:.]\d{2}/', $jam)) {
                $jam .= ' WIB';
            }

            $agenda = sanitize_text_field($agenda !== '' ? $agenda : 'Persidangan');
            if (strtoupper(trim($kelil)) === 'YA' && stripos($agenda, 'keliling') === false) {
                $agenda .= ' · Sidang Keliling';
            }
            $ruang = sanitize_text_field($ruang !== '' ? $ruang : 'Ruang Sidang');

            // Ambil tautan detil perkara bila tersedia pada baris.
            $detailUrl = '';
            $links     = $xpath->query('.//a[@href]', $tr);
            if ($links && $links->length > 0) {
                $href = trim((string) $links->item($links->length - 1)->getAttribute('href'));
                if ($href !== '' && stripos($href, 'javascript:') !== 0 && $href !== '#') {
                    if (preg_match('#^https?://#i', $href)) {
                        $detailUrl = esc_url_raw($href);
                    } elseif (strpos($href, '/') === 0 && $origin !== '') {
                        $detailUrl = esc_url_raw($origin . $href);
                    } elseif ($baseUrl !== '') {
                        $detailUrl = esc_url_raw(rtrim($baseUrl, '/') . '/' . ltrim($href, '/'));
                    }
                }
            }

            $dedupKey = strtolower($nomor . '|' . $eventDate . '|' . $agenda);
            if (isset($seen[$dedupKey])) {
                continue;
            }
            $seen[$dedupKey] = true;

            $items[] = array(
                'title'      => $nomor,
                'subtitle'   => $agenda,
                'category'   => $ruang,
                'event_date' => $eventDate,
                'jam'        => $jam,
                'detail_url' => $detailUrl,
            );
            if (count($items) >= $limit) {
                break 2;
            }
        }
    }

    if (!empty($items)) {
        return array(
            'status'  => 'ok',
            'items'   => $items,
            'message' => 'Berhasil membaca ' . count($items) . ' agenda sidang dari SIPP Web.',
        );
    }

    if ($foundSchedule || $emptyConfirmed) {
        return array(
            'status'  => 'empty',
            'items'   => array(),
            'message' => 'SIPP Web terhubung, namun saat ini belum ada agenda sidang terjadwal pada tabel publik.',
        );
    }

    return array(
        'status'  => 'error',
        'items'   => array(),
        'message' => 'Halaman SIPP Web terbuka tetapi tabel jadwal sidang tidak dikenali (kemungkinan dimuat via AJAX atau tema kustom). Gunakan Push API SIPP atau Input Manual.',
    );
}

function pa_portal_get_sipp_web_sync_status(): array {
    $st = get_option('pa_sipp_web_sync_status', array());
    return is_array($st) ? $st : array();
}

function pa_portal_sync_sipp_web_schedule(bool $manual = false, string $overrideUrl = ''): array {
    $resolved = pa_portal_sipp_resolve_schedule_urls($overrideUrl);
    $nowLabel = wp_date('d-m-Y H:i') . ' WIB';

    if (empty($resolved['valid'])) {
        $payload = array(
            'status'     => 'unconfigured',
            'count'      => 0,
            'source_url' => (string) ($resolved['primary_url'] ?? ''),
            'synced_at'  => $nowLabel,
            'timestamp'  => time(),
            'message'    => (string) ($resolved['reason'] ?? 'URL SIPP Satker belum diatur.'),
        );
        update_option('pa_sipp_web_sync_status', $payload, false);
        set_transient('pa_sipp_web_sync_fresh', 1, 1800);
        return $payload;
    }

    $lastError  = '';
    $lastUrl    = (string) $resolved['primary_url'];
    $parsedBest = null;

    foreach ((array) $resolved['candidates'] as $candUrl) {
        $lastUrl = (string) $candUrl;
        $resp    = pa_portal_ssrf_safe_get($lastUrl, array('go.id', 'mahkamahagung.go.id'), 10);
        if (empty($resp['ok'])) {
            $err = (string) ($resp['error'] ?? 'Gagal menghubungi SIPP Web.');
            if (preg_match('/HTTP\s*(403|503|429)/', $err)) {
                $lastError = "Akses ke {$lastUrl} ditolak ({$err} — kemungkinan proteksi Cloudflare atau firewall IP server SIPP).";
            } else {
                $lastError = "Tidak dapat mengakses {$lastUrl} ({$err}).";
            }
            continue;
        }

        $parsed = pa_portal_parse_sipp_web_html((string) $resp['body'], (string) $resolved['base_url'], 120);
        if (($parsed['status'] ?? '') === 'ok') {
            $parsedBest = $parsed;
            break;
        }
        if (($parsed['status'] ?? '') === 'blocked') {
            $parsedBest = $parsed;
            break;
        }
        if (($parsed['status'] ?? '') === 'empty' && $parsedBest === null) {
            $parsedBest = $parsed;
        } else {
            $lastError = (string) ($parsed['message'] ?? $lastError);
        }
    }

    if ($parsedBest !== null && ($parsedBest['status'] ?? '') === 'ok') {
        pa_portal_records_ensure_table();
        global $wpdb;
        $rTable = pa_portal_records_table();
        $nowSql = current_time('mysql');

        // Ganti hanya entri hasil Auto-Sync SIPP Web & data contoh bawaan (PA.Cth); entri manual/Push SIPP tetap aman.
        $wpdb->query(
            "DELETE FROM `{$rTable}` WHERE record_type = 'jadwal' AND (meta_json LIKE '%\"source\":\"sipp_web\"%' OR title LIKE '%/PA.Cth%')"
        );

        $inserted = 0;
        $sort     = 1;
        foreach ((array) $parsedBest['items'] as $it) {
            $meta = array(
                'jam'    => (string) ($it['jam'] ?? '09.00 WIB'),
                'source' => 'sipp_web',
            );
            if (!empty($it['detail_url'])) {
                $meta['detail_url'] = (string) $it['detail_url'];
            }
            $ok = $wpdb->insert($rTable, array(
                'record_type' => 'jadwal',
                'title'       => (string) $it['title'],
                'subtitle'    => (string) $it['subtitle'],
                'category'    => (string) $it['category'],
                'event_date'  => (string) $it['event_date'],
                'meta_json'   => wp_json_encode($meta) ?: '{}',
                'sort_order'  => $sort++,
                'status'      => 'published',
                'updated_at'  => $nowSql,
            ));
            if ($ok) {
                $inserted++;
            }
        }

        delete_transient('pa_sipp_hearings_today');
        if (function_exists('pa_home_page_cache_mark_stale')) {
            pa_home_page_cache_mark_stale();
        }

        $payload = array(
            'status'     => 'ok',
            'count'      => $inserted,
            'source_url' => $lastUrl,
            'synced_at'  => $nowLabel,
            'timestamp'  => time(),
            'message'    => "Berhasil menyinkronkan {$inserted} jadwal sidang otomatis dari SIPP Web.",
        );
        update_option('pa_sipp_web_sync_status', $payload, false);
        set_transient('pa_sipp_web_sync_fresh', 1, 1800);
        return $payload;
    }

    if ($parsedBest !== null && in_array((string) ($parsedBest['status'] ?? ''), array('empty', 'blocked'), true)) {
        $payload = array(
            'status'     => (string) $parsedBest['status'],
            'count'      => 0,
            'source_url' => $lastUrl,
            'synced_at'  => $nowLabel,
            'timestamp'  => time(),
            'message'    => (string) $parsedBest['message'],
        );
        update_option('pa_sipp_web_sync_status', $payload, false);
        set_transient('pa_sipp_web_sync_fresh', 1, 1800);
        return $payload;
    }

    $payload = array(
        'status'     => 'error',
        'count'      => 0,
        'source_url' => $lastUrl,
        'synced_at'  => $nowLabel,
        'timestamp'  => time(),
        'message'    => $lastError !== '' ? $lastError : 'Gagal menarik jadwal sidang dari SIPP Web. Pastikan URL SIPP dapat diakses server hosting atau gunakan Input Manual (fallback).',
    );
    update_option('pa_sipp_web_sync_status', $payload, false);
    set_transient('pa_sipp_web_sync_fresh', 1, $manual ? 300 : 1800);
    return $payload;
}

add_filter('cron_schedules', static function ($schedules) {
    if (!is_array($schedules)) {
        $schedules = array();
    }
    if (!isset($schedules['pa_every_30min'])) {
        $schedules['pa_every_30min'] = array(
            'interval' => 1800,
            'display'  => 'Setiap 30 Menit (PA SINOVA SIPP Auto-Sync)',
        );
    }
    return $schedules;
});

add_action('pa_sipp_web_cron_sync', static function () {
    $cfg = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    if (($cfg['sipp_web_autosync'] ?? '1') === '0') {
        return;
    }
    pa_portal_sync_sipp_web_schedule(false);
});

function pa_portal_maybe_schedule_sipp_web_sync(): void {
    $cfg = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    if (($cfg['sipp_web_autosync'] ?? '1') === '0') {
        $next = wp_next_scheduled('pa_sipp_web_cron_sync');
        if ($next) {
            wp_unschedule_event($next, 'pa_sipp_web_cron_sync');
        }
        return;
    }
    $resolved = pa_portal_sipp_resolve_schedule_urls();
    if (empty($resolved['valid'])) {
        return;
    }
    if (!wp_next_scheduled('pa_sipp_web_cron_sync')) {
        wp_schedule_event(time() + 120, 'pa_every_30min', 'pa_sipp_web_cron_sync');
    }
    if (!get_transient('pa_sipp_web_sync_fresh') && !wp_next_scheduled('pa_sipp_web_single_sync')) {
        wp_schedule_single_event(time() + 5, 'pa_sipp_web_single_sync');
    }
}
add_action('pa_sipp_web_single_sync', static function () {
    $cfg = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    if (($cfg['sipp_web_autosync'] ?? '1') !== '0') {
        pa_portal_sync_sipp_web_schedule(false);
    }
});
add_action('init', static function () {
    if (!wp_doing_ajax()) {
        pa_portal_maybe_schedule_sipp_web_sync();
    }
}, 30);

add_shortcode('pa_jadwal_sidang', static function () {
    pa_portal_maybe_schedule_sipp_web_sync();
    $cfg      = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $sippUrl  = trim((string) ($cfg['sipp_url'] ?? 'https://sipp.pa-contoh.go.id/'));
    $sippRows = pa_portal_fetch_sipp_hearings_today();
    $source   = !empty($sippRows) ? 'Koneksi Read-Only SIPP' : 'Data Jadwal Portal';

    if (empty($sippRows)) {
        $hasSippWeb  = false;
        $hasSippPush = false;
        foreach (pa_portal_get_records('jadwal', 100) as $r) {
            $meta = json_decode((string) ($r['meta_json'] ?? ''), true);
            if (!is_array($meta)) {
                $meta = array();
            }
            $src = (string) ($meta['source'] ?? '');
            if ($src === 'sipp_web') {
                $hasSippWeb = true;
            } elseif ($src === 'sipp_push') {
                $hasSippPush = true;
            }
            $sippRows[] = array(
                'title'      => (string) $r['title'],
                'subtitle'   => (string) $r['subtitle'],
                'category'   => (string) $r['category'],
                'event_date' => (string) ($r['event_date'] ?? wp_date('Y-m-d')),
                'jam'        => (string) ($meta['jam'] ?? '09.00 WIB'),
                'detail_url' => (string) ($meta['detail_url'] ?? ''),
            );
        }
        if ($hasSippWeb) {
            $source = 'Auto-Sync SIPP Web';
        } elseif ($hasSippPush) {
            $source = 'Push Otomatis SIPP';
        }
    }

    ob_start();
    if (function_exists('pa_portal_render_sipp_tracker')) {
        echo pa_portal_render_sipp_tracker('Penelusuran Perkara Cepat', true);
    }
    ?>
    <div class="pa-ops-wrap">
        <div class="pa-ops-toolbar">
            <input type="search" class="pa-ops-search" data-ops-table="pa-table-jadwal" aria-label="Saring jadwal sidang berdasarkan nomor perkara, agenda, atau ruang" placeholder="Cari nomor perkara, agenda, atau ruang sidang...">
            <div class="pa-ops-meta">
                <span class="pa-ops-badge"><?php echo esc_html($source); ?></span>
                <?php if ($sippUrl !== '') : ?>
                    <a class="pa-ops-link-btn" href="<?php echo esc_url($sippUrl); ?>" target="_blank" rel="noopener noreferrer">Buka SIPP Lengkap ↗</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="pa-table-scroll">
            <table class="pa-ops-table" id="pa-table-jadwal">
                <caption class="screen-reader-text">Jadwal Persidangan Harian Pengadilan Agama</caption>
                <thead>
                    <tr><th scope="col">Tanggal</th><th scope="col">Jam</th><th scope="col">Nomor Perkara</th><th scope="col">Agenda Sidang</th><th scope="col">Ruang</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($sippRows)) : ?>
                        <tr><td colspan="5">Belum ada jadwal persidangan terjadwal.</td></tr>
                    <?php else : foreach ($sippRows as $row) :
                        $detU = esc_url_raw((string) ($row['detail_url'] ?? ''), array('http', 'https'));
                    ?>
                        <tr>
                            <td><?php echo esc_html($row['event_date'] ?: wp_date('Y-m-d')); ?></td>
                            <td><strong><?php echo esc_html($row['jam']); ?></strong></td>
                            <td>
                                <?php if ($detU !== '') : ?>
                                    <a href="<?php echo esc_url($detU); ?>" target="_blank" rel="noopener noreferrer"><strong><?php echo esc_html($row['title']); ?></strong> ↗</a>
                                <?php else : ?>
                                    <strong><?php echo esc_html($row['title']); ?></strong>
                                <?php endif; ?>
                            </td>
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
            <input type="search" class="pa-ops-search" data-ops-table="pa-table-ghaib" aria-label="Saring panggilan ghaib berdasarkan nomor perkara atau nama pihak" placeholder="Cari nomor perkara atau nama pihak...">
            <span class="pa-ops-badge">Pasal 27 PP No. 9 Tahun 1975</span>
        </div>
        <div class="pa-table-scroll">
            <table class="pa-ops-table" id="pa-table-ghaib">
                <caption class="screen-reader-text">Daftar Pengumuman Panggilan Ghaib</caption>
                <thead>
                    <tr><th scope="col">Nomor Perkara</th><th scope="col">Nama Pihak Dipanggil</th><th scope="col">Tanggal Sidang</th><th scope="col">Keterangan</th></tr>
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
                <caption class="screen-reader-text">Rekapitulasi Statistik Perkara Bulanan</caption>
                <thead>
                    <tr><th scope="col">Periode</th><th scope="col">Diterima</th><th scope="col">Diputus</th><th scope="col">Sisa</th><th scope="col">Kinerja Penyelesaian</th></tr>
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
                                    <div class="pa-prog-bar" role="progressbar" aria-valuenow="<?php echo esc_attr((string) $pct); ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?php echo esc_attr('Kinerja penyelesaian ' . (string) $r['title']); ?>"><span style="width:<?php echo esc_attr((string) $pct); ?>%;"></span></div>
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

// Strip Ringkasan Kinerja (KPI Bar) 4 Indikator Utama
function pa_portal_render_kpi_bar(string $title = ''): string {
    $cfg      = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $rows     = pa_portal_get_records('statistik', 12);
    $totIn    = 0;
    $totOut   = 0;
    foreach ($rows as $r) {
        $m = json_decode((string) ($r['meta_json'] ?? ''), true);
        if (!is_array($m)) {
            $m = array();
        }
        $totIn  += (int) ($m['diterima'] ?? 0);
        $totOut += (int) ($m['diputus'] ?? 0);
    }
    $ratio    = $totIn > 0 ? round(($totOut / $totIn) * 100, 1) : 0;
    $skm      = trim((string) ($cfg['home_survey_skm'] ?? '4.00 / 4.00')) ?: '4.00 / 4.00';
    $skmPer   = trim((string) ($cfg['home_survey_period'] ?? 'Triwulan III')) ?: 'Triwulan III';
    $statUrl  = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('statistik-perkara') : home_url('/statistik-perkara/');
    $survUrl  = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('survei-pelayanan') : home_url('/survei-pelayanan/');

    ob_start();
    ?>
    <section class="pa-kpi-bar pa-hg-flush" aria-label="<?php echo esc_attr($title ?: 'Ringkasan Kinerja Pelayanan'); ?>">
        <a class="pa-kpi-bar-item" href="<?php echo esc_url($statUrl); ?>">
            <span class="pa-kb-lbl">Perkara Diterima</span>
            <strong class="pa-kb-val"><?php echo esc_html(number_format($totIn, 0, ',', '.')); ?></strong>
            <span class="pa-kb-sub">Akumulasi rekapitulasi</span>
        </a>
        <a class="pa-kpi-bar-item" href="<?php echo esc_url($statUrl); ?>">
            <span class="pa-kb-lbl">Perkara Diputus</span>
            <strong class="pa-kb-val"><?php echo esc_html(number_format($totOut, 0, ',', '.')); ?></strong>
            <span class="pa-kb-sub">Putusan &amp; penetapan</span>
        </a>
        <a class="pa-kpi-bar-item is-highlight" href="<?php echo esc_url($statUrl); ?>">
            <span class="pa-kb-lbl">Rasio Penyelesaian</span>
            <strong class="pa-kb-val"><?php echo esc_html((string) $ratio); ?>%</strong>
            <span class="pa-kb-sub">Kinerja penyelesaian perkara</span>
        </a>
        <a class="pa-kpi-bar-item" href="<?php echo esc_url($survUrl); ?>">
            <span class="pa-kb-lbl">Indeks Kepuasan (SKM)</span>
            <strong class="pa-kb-val"><?php echo esc_html($skm); ?></strong>
            <span class="pa-kb-sub"><?php echo esc_html($skmPer); ?> · Mutu Pelayanan</span>
        </a>
    </section>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('pa_kpi_bar', static function ($atts = array()) {
    $a = shortcode_atts(array('title' => 'Ringkasan Kinerja Pelayanan'), $atts, 'pa_kpi_bar');
    return pa_portal_render_kpi_bar((string) $a['title']);
});

// Blok Khusus Inovasi Layanan Satker
function pa_portal_render_inovasi_grid(string $title = 'Inovasi Layanan Satker', int $limit = 6): string {
    $limit = max(2, min(12, $limit));
    $rows  = pa_portal_get_records('inovasi', $limit);
    ob_start();
    ?>
    <section class="pa-section-block pa-inovasi-sec pa-hg-flush" aria-label="<?php echo esc_attr($title ?: 'Inovasi Layanan Satker'); ?>">
        <h2 class="pa-starter-heading"><?php echo esc_html(strtoupper($title ?: 'INOVASI LAYANAN SATKER')); ?> <span class="pa-hint-i" aria-hidden="true">i</span></h2>
        <p class="pa-starter-sub">Inovasi Layanan Digital, Prioritas Rentan, &amp; Ciri Khas Pelayanan Satuan Kerja</p>
        <?php if (empty($rows)) : ?>
            <p class="pa-sched-empty">Belum ada daftar inovasi layanan. Tambahkan lewat WP Admin → SINOVA Hub → Publikasi → Inovasi Layanan.</p>
        <?php else : ?>
            <div class="pa-inovasi-grid">
                <?php foreach ($rows as $r) :
                    $m      = json_decode((string) ($r['meta_json'] ?? ''), true);
                    $m      = is_array($m) ? $m : array();
                    $appUrl = esc_url_raw((string) ($m['app_url'] ?? ''), array('http', 'https'));
                    $icoUrl = esc_url_raw((string) ($m['icon_url'] ?? ''), array('http', 'https'));
                    $badge  = trim((string) ($m['badge'] ?? ($r['category'] ?: 'Inovasi Satker')));
                    $isExt  = ($appUrl !== '' && stripos($appUrl, home_url('/')) !== 0);
                ?>
                    <article class="pa-ino-card">
                        <div class="pa-ino-top">
                            <?php if ($icoUrl !== '') : ?>
                                <img class="pa-ino-img" src="<?php echo esc_url($icoUrl); ?>" alt="" loading="lazy" decoding="async">
                            <?php else : ?>
                                <span class="pa-ino-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.6 5.3L20.5 8l-4.2 4.1L17.3 18 12 15.2 6.7 18l1-5.9L3.5 8l5.9-.7z"/></svg></span>
                            <?php endif; ?>
                            <span class="pa-ino-badge"><?php echo esc_html($badge); ?></span>
                        </div>
                        <h3><?php echo esc_html((string) $r['title']); ?></h3>
                        <?php if (!empty($r['subtitle'])) : ?>
                            <p><?php echo esc_html((string) $r['subtitle']); ?></p>
                        <?php endif; ?>
                        <?php if ($appUrl !== '') : ?>
                            <a class="pa-ino-link" href="<?php echo esc_url($appUrl); ?>"<?php echo $isExt ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>Buka Layanan <?php echo $isExt ? '↗' : '→'; ?></a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('pa_inovasi_satker', static function ($atts = array()) {
    $a = shortcode_atts(array('title' => 'Inovasi Layanan Satker', 'limit' => 6), $atts, 'pa_inovasi_satker');
    return pa_portal_render_inovasi_grid((string) $a['title'], (int) $a['limit']);
});

// Widget Penelusuran Perkara Cepat (SIPP + Data Portal Lokal + Pintasan)
function pa_portal_render_sipp_tracker(string $title = 'Penelusuran Perkara Cepat (SIPP)', bool $compact = false): string {
    $cfg     = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $sippUrl = trim((string) ($cfg['sipp_url'] ?? 'https://sipp.pa-contoh.go.id/'));
    $sippBase = $sippUrl !== '' ? rtrim($sippUrl, '/') : '';
    $ecourtU = trim((string) ($cfg['ecourt_url'] ?? 'https://ecourt.mahkamahagung.go.id/'));
    $dirputU = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('direktori-putusan') : home_url('/direktori-putusan/');
    $schedU  = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('agenda-jadwal-persidangan') : home_url('/agenda-jadwal-persidangan/');
    $uid     = 'pa-sipp-tr-' . substr(md5($title . ($compact ? 'c' : 'f')), 0, 6);

    ob_start();
    ?>
    <section class="pa-sipp-tracker<?php echo $compact ? ' is-compact' : ''; ?> pa-hg-fullh" aria-label="<?php echo esc_attr($title); ?>" data-sipp-url="<?php echo esc_attr($sippUrl); ?>">
        <div class="pa-st-head">
            <div>
                <span class="pa-st-kicker">LAYANAN INFORMASI PERKARA</span>
                <h2 class="pa-st-title"><?php echo esc_html($title); ?></h2>
            </div>
            <div class="pa-st-links">
                <?php if ($sippBase !== '') : ?>
                    <a class="pa-st-pill" href="<?php echo esc_url($sippBase . '/list_perkara'); ?>" target="_blank" rel="noopener noreferrer">Daftar Perkara SIPP ↗</a>
                    <a class="pa-st-pill" href="<?php echo esc_url($sippBase . '/list_jadwal_sidang'); ?>" target="_blank" rel="noopener noreferrer">Jadwal Sidang SIPP ↗</a>
                <?php endif; ?>
                <a class="pa-st-pill" href="<?php echo esc_url($ecourtU); ?>" target="_blank" rel="noopener noreferrer">e-Court ↗</a>
                <?php if (!$compact) : ?>
                    <a class="pa-st-pill" href="<?php echo esc_url($dirputU); ?>">Direktori Putusan →</a>
                <?php else : ?>
                    <a class="pa-st-pill" href="<?php echo esc_url($schedU); ?>">Refresh Jadwal</a>
                <?php endif; ?>
            </div>
        </div>
        <form class="pa-st-form" data-pa-case-tracker="1" data-results-id="<?php echo esc_attr($uid . '-res'); ?>" role="search">
            <label class="screen-reader-text" for="<?php echo esc_attr($uid . '-q'); ?>">Cari nomor perkara, pihak, ruang sidang, atau amar putusan</label>
            <input id="<?php echo esc_attr($uid . '-q'); ?>" type="search" class="pa-st-input" placeholder="Ketik nomor perkara (mis. 0101/Pdt.G), agenda sidang, atau nama pihak ghaib..." required minlength="2">
            <button type="submit" class="pa-st-btn">Telusuri Perkara</button>
        </form>
        <div id="<?php echo esc_attr($uid . '-res'); ?>" class="pa-st-results" aria-live="polite" hidden></div>
    </section>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('pa_penelusuran_perkara', static function ($atts = array()) {
    $a = shortcode_atts(array('title' => 'Penelusuran Perkara Cepat (SIPP)'), $atts, 'pa_penelusuran_perkara');
    return pa_portal_render_sipp_tracker((string) $a['title'], false);
});

add_action('rest_api_init', static function () {
    register_rest_route('pa-portal/v1', '/case-lookup', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => static function (WP_REST_Request $req) {
            if (function_exists('pa_starter_rate_limit') && !pa_starter_rate_limit('caselookup', 45, 60)) {
                return new WP_Error('rate_limited', 'Terlalu banyak permintaan penelusuran perkara.', array('status' => 429));
            }
            pa_portal_records_ensure_table();
            $q = trim(sanitize_text_field((string) $req->get_param('q')));
            $q = function_exists('mb_substr') ? mb_substr($q, 0, 60, 'UTF-8') : substr($q, 0, 60);
            if (strlen($q) < 2) {
                return new WP_REST_Response(array('ok' => true, 'query' => $q, 'count' => 0, 'items' => array()), 200);
            }

            global $wpdb;
            $rTable = pa_portal_records_table();
            $like   = '%' . $wpdb->esc_like($q) . '%';
            $rows   = $wpdb->get_results($wpdb->prepare(
                "SELECT record_type, title, subtitle, category, event_date, meta_json FROM `{$rTable}` WHERE status = 'published' AND record_type IN ('jadwal', 'ghaib', 'putusan') AND (title LIKE %s OR subtitle LIKE %s OR category LIKE %s OR meta_json LIKE %s) ORDER BY event_date DESC, id DESC LIMIT 12",
                $like, $like, $like, $like
            ), ARRAY_A);

            $typeLblMap = array(
                'jadwal'  => 'Jadwal Sidang',
                'ghaib'   => 'Panggilan Ghaib',
                'putusan' => 'Putusan Satker',
            );
            $urlMap = array(
                'jadwal'  => function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('agenda-jadwal-persidangan') : home_url('/agenda-jadwal-persidangan/'),
                'ghaib'   => function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('panggilan-ghaib') : home_url('/panggilan-ghaib/'),
                'putusan' => function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('direktori-putusan') : home_url('/direktori-putusan/'),
            );

            $items = array();
            foreach ((array) $rows as $r) {
                $rt = (string) ($r['record_type'] ?? 'jadwal');
                $m  = json_decode((string) ($r['meta_json'] ?? ''), true);
                $m  = is_array($m) ? $m : array();
                $u  = (string) ($urlMap[$rt] ?? home_url('/'));
                if (!empty($m['doc_url'])) {
                    $u = esc_url_raw((string) $m['doc_url'], array('http', 'https')) ?: $u;
                }
                $extra = array_filter(array(
                    (string) ($r['category'] ?? ''),
                    (string) ($m['jam'] ?? ''),
                    (string) ($m['pihak'] ?? ''),
                    (string) ($m['status_hukum'] ?? ''),
                ));
                $items[] = array(
                    'type'       => $rt,
                    'type_label' => (string) ($typeLblMap[$rt] ?? ucfirst($rt)),
                    'title'      => (string) ($r['title'] ?? ''),
                    'subtitle'   => (string) ($r['subtitle'] ?? ''),
                    'category'   => implode(' · ', $extra),
                    'date'       => !empty($r['event_date']) ? wp_date('d M Y', strtotime((string) $r['event_date'])) : '',
                    'url'        => $u,
                );
            }

            return new WP_REST_Response(array(
                'ok'    => true,
                'query' => $q,
                'count' => count($items),
                'items' => $items,
            ), 200);
        },
    ));
});

// Helper ringkas Statistik Perkara untuk blok Beranda 12 kolom (5/12 atau 12/12)
function pa_portal_render_stats_widget(string $heading = 'Statistik Perkara'): string {
    $rows = pa_portal_get_records('statistik', 12);
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
    $ratio    = $totIn > 0 ? round(($totOut / $totIn) * 100, 1) : 0;
    $statUrl  = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('statistik-perkara') : home_url('/statistik-perkara/');
    ob_start();
    ?>
    <section class="pa-grid-panel pa-stats-panel" aria-label="<?php echo esc_attr($heading); ?>">
        <div class="pa-gp-head">
            <h2 class="pa-gp-title"><?php echo esc_html($heading); ?></h2>
            <a class="pa-gp-more" href="<?php echo esc_url($statUrl); ?>">Detail →</a>
        </div>
        <div class="pa-stats-mini-kpis">
            <div class="pa-smk"><span>Diterima</span><strong><?php echo esc_html(number_format($totIn, 0, ',', '.')); ?></strong></div>
            <div class="pa-smk"><span>Diputus</span><strong><?php echo esc_html(number_format($totOut, 0, ',', '.')); ?></strong></div>
            <div class="pa-smk"><span>Sisa</span><strong><?php echo esc_html(number_format($lastRem, 0, ',', '.')); ?></strong></div>
            <div class="pa-smk pa-smk-accent"><span>Penyelesaian</span><strong><?php echo esc_html((string) $ratio); ?>%</strong></div>
        </div>
        <?php if (!empty($rows)) : ?>
            <div class="pa-stats-mini-bars">
                <?php foreach (array_slice($rows, 0, 4) as $r) :
                    $m   = json_decode((string) ($r['meta_json'] ?? ''), true);
                    $in  = (int) ($m['diterima'] ?? 0);
                    $out = (int) ($m['diputus'] ?? 0);
                    $pct = $in > 0 ? min(100, round(($out / $in) * 100, 1)) : 0;
                ?>
                    <div class="pa-smb-row">
                        <div class="pa-smb-top">
                            <strong><?php echo esc_html((string) $r['title']); ?></strong>
                            <span><?php echo esc_html("{$out}/{$in} ({$pct}%)"); ?></span>
                        </div>
                        <div class="pa-prog-bar" role="progressbar" aria-valuenow="<?php echo esc_attr((string) $pct); ?>" aria-valuemin="0" aria-valuemax="100"><span style="width:<?php echo esc_attr((string) $pct); ?>%;"></span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p class="pa-sched-empty">Belum ada data statistik perkara bulanan.</p>
        <?php endif; ?>
    </section>
    <?php
    return (string) ob_get_clean();
}

// Helper Pengumuman & Panggilan Ghaib ringkas (tab Satker / MA RI / Badilag) untuk blok sempit (w <= 6)
function pa_portal_render_announcements_compact(string $heading = 'Pengumuman & Panggilan Ghaib', int $limit = 5): string {
    $limit    = max(2, min(10, $limit));
    $satkerIt = function_exists('pa_portal_get_satker_announcements') ? pa_portal_get_satker_announcements($limit) : array();
    $feeds    = function_exists('pa_portal_get_official_feeds') ? pa_portal_get_official_feeds(false) : array('ma' => array(), 'badilag' => array());
    $maIt     = array_slice((array) ($feeds['ma'] ?? array()), 0, $limit);
    $bdIt     = array_slice((array) ($feeds['badilag'] ?? array()), 0, $limit);
    $allUrl   = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('pengumuman-resmi') : home_url('/pengumuman-resmi/');
    $uid      = 'pa-ann-' . substr(md5($heading . (string) $limit), 0, 5);
    ob_start();
    ?>
    <section class="pa-grid-panel pa-ann-compact" data-pa-sched-hub aria-label="<?php echo esc_attr($heading); ?>">
        <div class="pa-gp-head">
            <h2 class="pa-gp-title"><?php echo esc_html($heading); ?></h2>
            <a class="pa-gp-more" href="<?php echo esc_url($allUrl); ?>">Semua →</a>
        </div>
        <div class="pa-ann-tabs" role="tablist" aria-label="Sumber Pengumuman">
            <button type="button" role="tab" id="<?php echo esc_attr($uid . '-t1'); ?>" aria-controls="<?php echo esc_attr($uid . '-p1'); ?>" aria-selected="true" tabindex="0" class="pa-ann-tab is-active" data-sched-tab="satker">Satker</button>
            <button type="button" role="tab" id="<?php echo esc_attr($uid . '-t2'); ?>" aria-controls="<?php echo esc_attr($uid . '-p2'); ?>" aria-selected="false" tabindex="-1" class="pa-ann-tab" data-sched-tab="ma">MA RI</button>
            <button type="button" role="tab" id="<?php echo esc_attr($uid . '-t3'); ?>" aria-controls="<?php echo esc_attr($uid . '-p3'); ?>" aria-selected="false" tabindex="-1" class="pa-ann-tab" data-sched-tab="badilag">Badilag</button>
        </div>
        <?php
        $groups = array(
            array('key' => 'satker', 'tid' => $uid . '-t1', 'pid' => $uid . '-p1', 'items' => $satkerIt, 'ext' => false),
            array('key' => 'ma',     'tid' => $uid . '-t2', 'pid' => $uid . '-p2', 'items' => $maIt,     'ext' => true),
            array('key' => 'badilag','tid' => $uid . '-t3', 'pid' => $uid . '-p3', 'items' => $bdIt,     'ext' => true),
        );
        foreach ($groups as $gIdx => $g) :
        ?>
            <div role="tabpanel" id="<?php echo esc_attr($g['pid']); ?>" aria-labelledby="<?php echo esc_attr($g['tid']); ?>" data-sched-panel="<?php echo esc_attr($g['key']); ?>" <?php echo $gIdx > 0 ? 'hidden' : ''; ?>>
                <ul class="pa-feed-list">
                    <?php if (empty($g['items'])) : ?>
                        <li class="pa-feed-empty">Belum ada pengumuman terbaru.</li>
                    <?php else : foreach ($g['items'] as $it) :
                        $title = (string) ($it['title'] ?? '');
                        $url   = (string) ($it['url'] ?? ($it['link'] ?? '#'));
                        $date  = (string) ($it['date'] ?? '');
                        $thumb = trim((string) ($it['thumb'] ?? ''));
                    ?>
                        <li class="pa-feed-item">
                            <a href="<?php echo esc_url($url); ?>" <?php echo $g['ext'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                                <?php if (!$g['ext']) : ?>
                                    <?php if ($thumb !== '') : ?>
                                        <span class="pa-feed-thumb"><img src="<?php echo esc_url($thumb); ?>" alt="" loading="lazy" decoding="async"></span>
                                    <?php else : ?>
                                        <span class="pa-feed-thumb pa-feed-thumb-ph" aria-hidden="true">📣</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <span class="pa-feed-content">
                                    <?php if ($date !== '') : ?><span class="pa-feed-date"><?php echo esc_html($date); ?></span><?php endif; ?>
                                    <span class="pa-feed-title"><?php echo esc_html($title); ?></span>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </section>
    <?php
    return (string) ob_get_clean();
}

// Shortcode [pa_yurisdiksi_grid]: Daftar Wilayah Yurisdiksi & Radius
add_shortcode('pa_yurisdiksi_grid', static function () {
    $rows = pa_portal_get_records('yurisdiksi', 150);
    ob_start();
    ?>
    <div class="pa-ops-wrap">
        <div class="pa-ops-toolbar">
            <input type="search" class="pa-ops-search" data-ops-table="pa-table-yurisdiksi" aria-label="Cari kecamatan, desa, atau radius" placeholder="Cari kecamatan, kelurahan/desa, atau radius...">
            <span class="pa-ops-badge"><?php echo (int) count($rows); ?> Kecamatan</span>
        </div>
        <div class="pa-table-scroll">
            <table class="pa-ops-table" id="pa-table-yurisdiksi">
                <caption class="screen-reader-text">Wilayah Yurisdiksi Pengadilan Agama</caption>
                <thead><tr><th scope="col">Kecamatan</th><th scope="col">Radius</th><th scope="col">Desa / Kelurahan</th><th scope="col">Jarak / Ket.</th></tr></thead>
                <tbody>
                    <?php if (empty($rows)) : ?>
                        <tr><td colspan="4">Belum ada data wilayah yurisdiksi. Isi melalui WP Admin → SINOVA Hub → Wilayah Yurisdiksi.</td></tr>
                    <?php else : foreach ($rows as $r) :
                        $m = json_decode((string) ($r['meta_json'] ?? ''), true);
                        if (!is_array($m)) $m = array();
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html((string) $r['title']); ?></strong><?php if (!empty($r['subtitle'])) : ?><br><small><?php echo esc_html((string) $r['subtitle']); ?></small><?php endif; ?></td>
                            <td><span class="pa-ops-badge"><?php echo esc_html((string) ($r['category'] ?: 'Radius')); ?></span></td>
                            <td><?php echo esc_html((string) ($m['desa'] ?? '-')); ?></td>
                            <td><?php echo esc_html(trim(((string) ($m['jarak'] ?? '')) . (!empty($m['biaya']) ? ' · ' . (string) $m['biaya'] : '')) ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
});

// Helper ekstrak YouTube ID aman untuk Galeri Video (youtube-nocookie.com)
function pa_portal_extract_youtube_id(string $url): string {
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
        return $m[1];
    }
    return '';
}

// Shortcode [pa_video_gallery limit="4"]: Galeri Video Satker
add_shortcode('pa_video_gallery', static function ($atts = array()) {
    $a    = shortcode_atts(array('limit' => 4, 'title' => 'Galeri Video'), $atts, 'pa_video_gallery');
    $rows = pa_portal_get_records('video', max(1, min(12, (int) $a['limit'])));
    ob_start();
    ?>
    <section class="pa-grid-panel pa-video-panel" aria-label="<?php echo esc_attr((string) $a['title']); ?>">
        <div class="pa-gp-head">
            <h2 class="pa-gp-title"><?php echo esc_html((string) $a['title']); ?></h2>
        </div>
        <?php if (empty($rows)) : ?>
            <p class="pa-sched-empty">Belum ada video terpublikasi.</p>
        <?php else : ?>
            <div class="pa-media-grid">
                <?php foreach ($rows as $r) :
                    $m    = json_decode((string) ($r['meta_json'] ?? ''), true);
                    $vUrl = is_array($m) ? esc_url_raw((string) ($m['video_url'] ?? ''), array('https')) : '';
                    $ytId = pa_portal_extract_youtube_id($vUrl);
                ?>
                    <article class="pa-video-card">
                        <?php if ($ytId !== '') : ?>
                            <div class="pa-video-embed">
                                <iframe src="<?php echo esc_url('https://www.youtube-nocookie.com/embed/' . $ytId); ?>" title="<?php echo esc_attr((string) $r['title']); ?>" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="fullscreen" allowfullscreen></iframe>
                            </div>
                        <?php elseif ($vUrl !== '') : ?>
                            <a class="pa-video-link-box" href="<?php echo esc_url($vUrl); ?>" target="_blank" rel="noopener noreferrer">▶ Putar Video ↗</a>
                        <?php endif; ?>
                        <div class="pa-video-body">
                            <strong><?php echo esc_html((string) $r['title']); ?></strong>
                            <?php if (!empty($r['subtitle'])) : ?><p><?php echo esc_html((string) $r['subtitle']); ?></p><?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return (string) ob_get_clean();
});

// Shortcode [pa_ucapan_board limit="4"]: Papan Ucapan & Belasungkawa Satker
add_shortcode('pa_ucapan_board', static function ($atts = array()) {
    $a    = shortcode_atts(array('limit' => 4, 'title' => 'Ucapan & Belasungkawa'), $atts, 'pa_ucapan_board');
    $rows = pa_portal_get_records('ucapan', max(1, min(12, (int) $a['limit'])));
    ob_start();
    ?>
    <section class="pa-grid-panel pa-ucapan-panel" aria-label="<?php echo esc_attr((string) $a['title']); ?>">
        <div class="pa-gp-head">
            <h2 class="pa-gp-title"><?php echo esc_html((string) $a['title']); ?></h2>
        </div>
        <?php if (empty($rows)) : ?>
            <p class="pa-sched-empty">Belum ada papan ucapan atau belasungkawa.</p>
        <?php else : ?>
            <div class="pa-ucapan-grid">
                <?php foreach ($rows as $r) :
                    $m       = json_decode((string) ($r['meta_json'] ?? ''), true);
                    $badge   = is_array($m) ? (string) ($m['badge'] ?? ($r['category'] ?: 'Ucapan Selamat')) : (string) ($r['category'] ?: 'Ucapan Selamat');
                    $sender  = is_array($m) ? (string) ($m['sender'] ?? 'Keluarga Besar Pengadilan Agama') : '';
                    $photo   = is_array($m) ? esc_url_raw((string) ($m['photo_url'] ?? ''), array('http', 'https')) : '';
                    $isDuka  = (stripos($badge . ' ' . (string) $r['category'], 'duka') !== false || stripos($badge . ' ' . (string) $r['category'], 'belasungkawa') !== false);
                ?>
                    <article class="pa-ucapan-card<?php echo $isDuka ? ' is-duka' : ''; ?>">
                        <span class="pa-ucapan-badge"><?php echo esc_html($badge); ?></span>
                        <?php if ($photo !== '') : ?>
                            <img class="pa-ucapan-img" src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr((string) $r['title']); ?>" loading="lazy" decoding="async">
                        <?php endif; ?>
                        <strong><?php echo esc_html((string) $r['title']); ?></strong>
                        <?php if (!empty($r['subtitle'])) : ?><p><?php echo esc_html((string) $r['subtitle']); ?></p><?php endif; ?>
                        <?php if ($sender !== '') : ?><small><?php echo esc_html($sender); ?></small><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
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
            if (function_exists('pa_starter_rate_limit') && !pa_starter_rate_limit('qsearch', 45, 60)) {
                return new WP_Error('rate_limited', 'Terlalu banyak permintaan pencarian.', array('status' => 429));
            }
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
                array('Informasi Serta-Merta', 'informasi-serta-merta', 'KMA 2-144', 'Pemberitahuan segera kondisi layanan & utilitas'),
                array('Layanan Pengaduan & SIWAS', 'layanan-pengaduan', 'Pengaduan', 'Saluran pengaduan masyarakat & Whistleblowing'),
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
// 4. ENTERPRISE SEO: META DESCRIPTION, MANIFEST, FAVICON, OPEN GRAPH + JSON-LD
// ============================================================================

function pa_sinova_guess_icon_mime(string $url): string {
    $path = strtolower((string) parse_url($url, PHP_URL_PATH));
    if (preg_match('/\.svg$/', $path)) {
        return 'image/svg+xml';
    }
    if (preg_match('/\.ico$/', $path)) {
        return 'image/x-icon';
    }
    if (preg_match('/\.webp$/', $path)) {
        return 'image/webp';
    }
    if (preg_match('/\.jpe?g$/', $path)) {
        return 'image/jpeg';
    }
    if (preg_match('/\.gif$/', $path)) {
        return 'image/gif';
    }
    return 'image/png';
}

function pa_sinova_resolve_favicon_meta(): array {
    $cfg     = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $favUrl  = trim((string) ($cfg['favicon_url'] ?? ''));
    $logoUrl = trim((string) ($cfg['logo_url'] ?? ''));
    if ($favUrl !== '') {
        return array('url' => $favUrl, 'mime' => pa_sinova_guess_icon_mime($favUrl), 'is_custom' => true);
    }
    if (function_exists('has_site_icon') && has_site_icon()) {
        return array('url' => '', 'mime' => '', 'is_custom' => false);
    }
    if ($logoUrl !== '') {
        return array('url' => $logoUrl, 'mime' => pa_sinova_guess_icon_mime($logoUrl), 'is_custom' => false);
    }
    return array('url' => home_url('/favicon.svg'), 'mime' => 'image/svg+xml', 'is_custom' => false);
}

function pa_sinova_emit_favicon_tags(array $fav): void {
    if (empty($fav['url'])) {
        return;
    }
    $url  = esc_url((string) $fav['url']);
    $mime = esc_attr((string) ($fav['mime'] ?: 'image/png'));
    echo '<link rel="icon" href="' . $url . '" type="' . $mime . '">' . "\n";
    echo '<link rel="shortcut icon" href="' . $url . '" type="' . $mime . '">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . $url . '">' . "\n";
}

add_action('wp_head', static function () {
    $fav = pa_sinova_resolve_favicon_meta();
    if (!empty($fav['is_custom'])) {
        remove_action('wp_head', 'wp_site_icon', 99);
    }
}, 1);

add_action('admin_head', static function () {
    $fav = pa_sinova_resolve_favicon_meta();
    if (!empty($fav['is_custom'])) {
        remove_action('admin_head', 'wp_site_icon', 99);
    }
    if (!empty($fav['url'])) {
        pa_sinova_emit_favicon_tags($fav);
    }
}, 20);

add_action('wp_head', static function () {
    if (is_admin() || (defined('PA_SERVING_CUSTOM_LOGIN') && PA_SERVING_CUSTOM_LOGIN)) {
        return;
    }
    $cfg     = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $satker  = $cfg['satker_name'] ?? (get_bloginfo('name') ?: 'Pengadilan Agama');
    $homeUrl = trailingslashit(home_url('/'));
    $pageUrl = is_singular() ? (string) get_permalink() : $homeUrl;
    $title   = is_front_page() ? $satker : wp_get_document_title();

    $rawDesc = '';
    if (is_singular() && !is_front_page()) {
        $postObj = get_queried_object();
        if ($postObj instanceof WP_Post) {
            if (has_excerpt($postObj)) {
                $rawDesc = (string) $postObj->post_excerpt;
            } elseif (
                preg_match('/\[pa_sinova_docs\b[^\]]*slug=["\']([a-z0-9_-]+)["\']/i', (string) $postObj->post_content, $dm)
                && function_exists('pa_sinova_module_config')
            ) {
                $modCfg  = pa_sinova_module_config(sanitize_key($dm[1]));
                $rawDesc = trim((string) ($modCfg['title'] ?? '') . ' — ' . (string) ($modCfg['subtitle'] ?? ''));
            } else {
                $rawDesc = trim(wp_strip_all_tags(strip_shortcodes((string) $postObj->post_content)));
            }
            if ($rawDesc === '') {
                $rawDesc = get_the_title($postObj) . ' — Portal resmi informasi layanan dan transparansi ' . $satker . '.';
            }
        }
    }
    if ($rawDesc === '') {
        $rawDesc = trim((string) ($cfg['leader_welcome'] ?? 'Portal resmi keterbukaan informasi dan layanan peradilan agama.'));
    }
    $desc = pa_portal_trim_text($rawDesc, 155);

    echo '<meta name="theme-color" content="#043927" media="(prefers-color-scheme: light)">' . "\n";
    echo '<meta name="theme-color" content="#0b141a" media="(prefers-color-scheme: dark)">' . "\n";
    echo '<link rel="manifest" href="' . esc_url(home_url('/manifest.webmanifest')) . '">' . "\n";
    pa_sinova_emit_favicon_tags(pa_sinova_resolve_favicon_meta());

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
        echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
        echo '<meta property="og:locale" content="id_ID">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($satker) . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($desc) . '">' . "\n";
        echo '<meta property="og:type" content="' . (is_single() ? 'article' : 'website') . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($pageUrl) . '">' . "\n";
        if ($ogImg !== '') {
            echo '<meta property="og:image" content="' . esc_url($ogImg) . '">' . "\n";
        }
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($desc) . '">' . "\n";
        if ($ogImg !== '') {
            echo '<meta name="twitter:image" content="' . esc_url($ogImg) . '">' . "\n";
        }
    }

    $orgNode = array(
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
    );
    if (!empty($cfg['logo_url'])) {
        $orgNode['logo'] = (string) $cfg['logo_url'];
    }

    $graph = array(
        $orgNode,
        array(
            '@type'           => 'WebSite',
            '@id'             => $homeUrl . '#website',
            'url'             => $homeUrl,
            'name'            => $satker,
            'inLanguage'      => 'id-ID',
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
        if (is_single()) {
            $postObj = get_queried_object();
            if ($postObj instanceof WP_Post) {
                $graph[] = array(
                    '@type'            => 'NewsArticle',
                    'headline'         => wp_strip_all_tags((string) get_the_title($postObj)),
                    'description'      => $desc,
                    'datePublished'    => get_the_date('c', $postObj),
                    'dateModified'     => get_the_modified_date('c', $postObj),
                    'mainEntityOfPage' => $pageUrl,
                    'author'           => array('@id' => $homeUrl . '#organization'),
                    'publisher'        => array('@id' => $homeUrl . '#organization'),
                );
            }
        }
    }

    echo '<script type="application/ld+json">' . wp_json_encode(array('@context' => 'https://schema.org', '@graph' => $graph), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}, 5);
