<?php
/**
 * Module Name: PA SINOVA — Daftar Perkara Live SIPP (Elegan & Responsif)
 * Description: Menampilkan daftar perkara terbaru secara elegan langsung dari {sipp_url}/list_perkara milik masing-masing satuan kerja dengan cache transient 15 menit, pencarian instan, filter klasifikasi, dan tombol detail SIPP.
 * Version: 2.5.3
 */

defined('ABSPATH') || exit;

if (!function_exists('pa_sipp_list_perkara_resolve_url')) {
    function pa_sipp_list_perkara_resolve_url(string $overrideUrl = ''): string {
        $raw = trim($overrideUrl);
        if ($raw === '' && function_exists('pa_sinova_get_settings')) {
            $cfg = pa_sinova_get_settings();
            $raw = trim((string) ($cfg['sipp_url'] ?? ''));
        }
        if ($raw === '' || stripos($raw, 'pa-contoh') !== false) {
            return '';
        }
        $clean = esc_url_raw($raw, array('http', 'https'));
        if ($clean === '') {
            return '';
        }
        $parts = wp_parse_url($clean);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }
        $base = $parts['scheme'] . '://' . $parts['host'] . (!empty($parts['port']) ? ':' . (int) $parts['port'] : '');
        if (stripos($clean, 'list_perkara') !== false) {
            return $clean;
        }
        return rtrim($base, '/') . '/list_perkara';
    }
}

if (!function_exists('pa_sipp_list_perkara_parse_html')) {
    function pa_sipp_list_perkara_parse_html(string $html, string $baseUrl, int $limit = 20): array {
        $items = array();
        if (trim($html) === '') {
            return $items;
        }
        $tblHtml = '';
        if (preg_match('/<table[^>]*id=["\']tablePerkaraAll["\'][^>]*>(.*?)<\/table>/is', $html, $m)) {
            $tblHtml = $m[1];
        } elseif (preg_match('/<table[^>]*>(.*?)<\/table>/is', $html, $m)) {
            $tblHtml = $m[1];
        }
        if ($tblHtml === '') {
            return $items;
        }
        if (!preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tblHtml, $trMatches)) {
            return $items;
        }
        foreach ($trMatches[1] as $idx => $tr) {
            if ($idx === 0 && stripos($tr, '<th') !== false) {
                continue;
            }
            if (!preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $tr, $tdMatches)) {
                continue;
            }
            $cells = $tdMatches[1];
            if (count($cells) < 6) {
                continue;
            }
            $clean = static function (string $s): string {
                $t = wp_strip_all_tags(html_entity_decode($s, ENT_QUOTES, 'UTF-8'));
                return trim((string) preg_replace('/\s+/', ' ', $t));
            };
            $nomor = $clean($cells[1]);
            if ($nomor === '' || stripos($nomor, 'nomor perkara') !== false) {
                continue;
            }
            $detailUrl = '';
            $lastCell = $cells[count($cells) - 1];
            if (preg_match('/href=["\']([^"\']+)["\']/i', $lastCell, $hm)) {
                $href = html_entity_decode($hm[1], ENT_QUOTES, 'UTF-8');
                if (stripos($href, 'http') === 0) {
                    $detailUrl = $href;
                } else {
                    $detailUrl = rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
                }
            }
            $items[] = array(
                'no'          => $clean($cells[0]),
                'nomor'       => $nomor,
                'tanggal'     => $clean($cells[2]),
                'klasifikasi' => $clean($cells[3]),
                'pihak'       => $clean($cells[4]),
                'status'      => $clean($cells[5]),
                'lama'        => isset($cells[6]) ? $clean($cells[6]) : '',
                'detail_url'  => esc_url_raw($detailUrl, array('http', 'https')),
            );
            if (count($items) >= $limit) {
                break;
            }
        }
        return $items;
    }
}

if (!function_exists('pa_sipp_list_perkara_fetch')) {
    function pa_sipp_list_perkara_fetch(string $overrideUrl = '', int $limit = 20, bool $force = false): array {
        $targetUrl = pa_sipp_list_perkara_resolve_url($overrideUrl);
        if ($targetUrl === '') {
            return array('ok' => false, 'url' => '', 'items' => array(), 'fetched_at' => '', 'error' => 'URL SIPP belum diatur pada Identitas Satker.');
        }
        $cacheKey = 'pa_sipp_lp_v1_' . md5($targetUrl . '|' . $limit);
        if (!$force) {
            $cached = get_transient($cacheKey);
            if (is_array($cached) && isset($cached['items'])) {
                return $cached;
            }
        }
        $parts = wp_parse_url($targetUrl);
        $baseUrl = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
        $resp = wp_remote_get($targetUrl, array(
            'timeout'     => 12,
            'redirection' => 3,
            'sslverify'   => false,
            'headers'     => array('User-Agent' => 'Mozilla/5.0 (compatible; PA-SINOVA-SIPP/2.5.3)'),
        ));
        if (is_wp_error($resp)) {
            $res = array('ok' => false, 'url' => $targetUrl, 'items' => array(), 'fetched_at' => wp_date('d M Y H:i') . ' WIB', 'error' => 'SIPP Web tidak dapat dijangkau sementara.');
            set_transient($cacheKey, $res, 3 * MINUTE_IN_SECONDS);
            return $res;
        }
        $code = (int) wp_remote_retrieve_response_code($resp);
        $body = (string) wp_remote_retrieve_body($resp);
        $items = ($code >= 200 && $code < 400) ? pa_sipp_list_perkara_parse_html($body, $baseUrl, $limit) : array();
        $res = array(
            'ok'         => !empty($items),
            'url'        => $targetUrl,
            'items'      => $items,
            'fetched_at' => wp_date('d M Y H:i') . ' WIB',
            'error'      => empty($items) ? 'Belum ada data perkara publik yang terbaca dari SIPP.' : '',
        );
        set_transient($cacheKey, $res, !empty($items) ? 15 * MINUTE_IN_SECONDS : 4 * MINUTE_IN_SECONDS);
        return $res;
    }
}

if (!function_exists('pa_sipp_list_perkara_render')) {
    function pa_sipp_list_perkara_render(array $atts = array()): string {
        $a = shortcode_atts(array(
            'limit' => 10,
            'title' => 'Daftar Perkara Terbaru (Live SIPP)',
            'url'   => '',
        ), $atts, 'pa_sipp_list_perkara');

        $limit = max(3, min(25, (int) $a['limit']));
        $data  = pa_sipp_list_perkara_fetch((string) $a['url'], $limit);
        $items = $data['items'];
        $sippUrl = (string) $data['url'];
        $uid   = 'pa-sipp-lp-' . wp_rand(1000, 9999);

        $klasSet = array();
        foreach ($items as $it) {
            if ($it['klasifikasi'] !== '') {
                $klasSet[$it['klasifikasi']] = ($klasSet[$it['klasifikasi']] ?? 0) + 1;
            }
        }
        arsort($klasSet);
        $topKlas = array_slice(array_keys($klasSet), 0, 5);

        static $cssEmitted = false;
        ob_start();
        if (!$cssEmitted) :
            $cssEmitted = true;
            ?>
            <style id="pa-sipp-lp-css">
                .pa-slp-wrap{background:#fff;border:1px solid #d5e6dc;border-top:4px solid #056e4f;border-radius:14px;padding:18px 20px;box-shadow:0 10px 26px -18px rgba(2,44,34,.22)}
                .pa-slp-head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px}
                .pa-slp-title-grp{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
                .pa-slp-title{margin:0;font-size:17px;font-weight:800;color:#043927}
                .pa-slp-live{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;background:#ecfdf5;border:1px solid #86efac;color:#065f46;font-size:11.5px;font-weight:700}
                .pa-slp-ext{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:8px;background:#056e4f;color:#fff !important;font-size:12.5px;font-weight:700;text-decoration:none !important}
                .pa-slp-ext:hover{background:#04593f}
                .pa-slp-bar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px}
                .pa-slp-search{flex:1;min-width:220px;max-width:360px;padding:8px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;background:#f8fafc;color:#0f172a}
                .pa-slp-search:focus{outline:none;border-color:#056e4f;background:#fff}
                .pa-slp-pills{display:flex;flex-wrap:wrap;gap:6px}
                .pa-slp-pill{padding:5px 11px;border-radius:999px;border:1px solid #cbd5e1;background:#fff;color:#334155;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s}
                .pa-slp-pill.is-active,.pa-slp-pill:hover{background:#056e4f;border-color:#056e4f;color:#fff}
                .pa-slp-tbl-wrap{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid #e2e8f0;border-radius:10px}
                .pa-slp-tbl{width:100%;border-collapse:collapse;font-size:13px;min-width:660px}
                .pa-slp-tbl th{background:#043927;color:#fff;font-weight:700;text-align:left;padding:10px 12px;font-size:12px;letter-spacing:.02em;white-space:nowrap}
                .pa-slp-tbl td{padding:10px 12px;border-bottom:1px solid #edf2f7;vertical-align:middle;color:#1e293b}
                .pa-slp-tbl tbody tr:nth-child(even){background:#f8fbf9}
                .pa-slp-tbl tbody tr:hover{background:#ecfdf5}
                .pa-slp-no{font-weight:800;color:#056e4f;white-space:nowrap}
                .pa-slp-date{font-size:11.5px;color:#64748b;display:block;margin-top:2px}
                .pa-slp-klas{display:inline-block;padding:3px 9px;border-radius:6px;background:#fef9e7;border:1px solid #f3dfa0;color:#854d0e;font-size:11.5px;font-weight:700}
                .pa-slp-status{display:inline-block;padding:3px 9px;border-radius:999px;background:#e0f2fe;color:#075985;font-size:11.5px;font-weight:700}
                .pa-slp-status.is-done{background:#dcfce7;color:#166534}
                .pa-slp-lama{font-weight:700;color:#475569;white-space:nowrap;font-size:12px}
                .pa-slp-detil{display:inline-flex;align-items:center;padding:5px 10px;border-radius:6px;border:1px solid #056e4f;color:#056e4f !important;font-size:12px;font-weight:700;text-decoration:none !important;white-space:nowrap}
                .pa-slp-detil:hover{background:#056e4f;color:#fff !important}
                .pa-slp-foot{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:8px;margin-top:10px;font-size:11.5px;color:#64748b}
                [data-pa-theme="dark"] .pa-slp-wrap{background:#0f1f1a;border-color:#1f3a31}
                [data-pa-theme="dark"] .pa-slp-title{color:#a7f3d0}
                [data-pa-theme="dark"] .pa-slp-search,[data-pa-theme="dark"] .pa-slp-pill{background:#132822;border-color:#1f3a31;color:#e2e8f0}
                [data-pa-theme="dark"] .pa-slp-tbl td{color:#e2e8f0;border-color:#1f3a31}
                [data-pa-theme="dark"] .pa-slp-tbl tbody tr:nth-child(even){background:#132822}
            </style>
        <?php endif; ?>

        <section class="pa-slp-wrap" id="<?php echo esc_attr($uid); ?>" aria-label="<?php echo esc_attr((string) $a['title']); ?>">
            <div class="pa-slp-head">
                <div class="pa-slp-title-grp">
                    <h2 class="pa-slp-title"><?php echo esc_html((string) $a['title']); ?></h2>
                    <span class="pa-slp-live">● Live SIPP</span>
                </div>
                <?php if ($sippUrl !== '') : ?>
                    <a class="pa-slp-ext" href="<?php echo esc_url($sippUrl); ?>" target="_blank" rel="noopener noreferrer">Buka SIPP Lengkap ↗</a>
                <?php endif; ?>
            </div>

            <?php if (!empty($items)) : ?>
                <div class="pa-slp-bar">
                    <input type="search" class="pa-slp-search" placeholder="Cari nomor perkara, jenis perkara, atau status..." aria-label="Cari perkara">
                    <div class="pa-slp-pills">
                        <button type="button" class="pa-slp-pill is-active" data-klas="">Semua (<?php echo count($items); ?>)</button>
                        <?php foreach ($topKlas as $kl) : ?>
                            <button type="button" class="pa-slp-pill" data-klas="<?php echo esc_attr(strtolower($kl)); ?>"><?php echo esc_html($kl); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pa-slp-tbl-wrap">
                    <table class="pa-slp-tbl">
                        <thead>
                            <tr>
                                <th>Nomor Perkara</th>
                                <th>Klasifikasi</th>
                                <th>Para Pihak</th>
                                <th>Status Perkara</th>
                                <th>Lama</th>
                                <th style="text-align:right;">Tautan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it) :
                                $isDone = (bool) preg_match('/(minutasi|putus|selesai|cabut)/i', $it['status']);
                                $searchHay = strtolower($it['nomor'] . ' ' . $it['klasifikasi'] . ' ' . $it['pihak'] . ' ' . $it['status'] . ' ' . $it['tanggal']);
                            ?>
                                <tr data-search="<?php echo esc_attr($searchHay); ?>" data-klas="<?php echo esc_attr(strtolower($it['klasifikasi'])); ?>">
                                    <td>
                                        <span class="pa-slp-no"><?php echo esc_html($it['nomor']); ?></span>
                                        <span class="pa-slp-date"><?php echo esc_html($it['tanggal']); ?></span>
                                    </td>
                                    <td><span class="pa-slp-klas"><?php echo esc_html($it['klasifikasi']); ?></span></td>
                                    <td style="max-width:240px;font-size:12.5px;color:#475569;"><?php echo esc_html($it['pihak']); ?></td>
                                    <td><span class="pa-slp-status<?php echo $isDone ? ' is-done' : ''; ?>"><?php echo esc_html($it['status']); ?></span></td>
                                    <td><span class="pa-slp-lama"><?php echo esc_html($it['lama'] ?: '—'); ?></span></td>
                                    <td style="text-align:right;">
                                        <?php if ($it['detail_url'] !== '') : ?>
                                            <a class="pa-slp-detil" href="<?php echo esc_url($it['detail_url']); ?>" target="_blank" rel="noopener noreferrer">Detil ↗</a>
                                        <?php else : ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pa-slp-foot">
                    <span>Sumber: Sistem Informasi Penelusuran Perkara (SIPP) · Diperbarui <?php echo esc_html((string) $data['fetched_at']); ?></span>
                    <span class="pa-slp-count">Menampilkan <?php echo count($items); ?> perkara terbaru</span>
                </div>

                <script>
                (function(){
                    var root = document.getElementById(<?php echo wp_json_encode($uid); ?>);
                    if (!root) return;
                    var inp = root.querySelector('.pa-slp-search');
                    var pills = root.querySelectorAll('.pa-slp-pill');
                    var rows = root.querySelectorAll('tbody tr');
                    var cntEl = root.querySelector('.pa-slp-count');
                    var curKlas = '';
                    function filter() {
                        var q = (inp ? inp.value : '').trim().toLowerCase();
                        var vis = 0;
                        rows.forEach(function(tr){
                            var okQ = !q || (tr.getAttribute('data-search') || '').indexOf(q) !== -1;
                            var okK = !curKlas || (tr.getAttribute('data-klas') || '') === curKlas;
                            var show = okQ && okK;
                            tr.style.display = show ? '' : 'none';
                            if (show) vis++;
                        });
                        if (cntEl) cntEl.textContent = 'Menampilkan ' + vis + ' perkara';
                    }
                    if (inp) inp.addEventListener('input', filter);
                    pills.forEach(function(btn){
                        btn.addEventListener('click', function(){
                            pills.forEach(function(b){ b.classList.remove('is-active'); });
                            btn.classList.add('is-active');
                            curKlas = btn.getAttribute('data-klas') || '';
                            filter();
                        });
                    });
                })();
                </script>
            <?php else : ?>
                <p style="margin:0;padding:12px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#475569;">
                    <?php echo esc_html((string) ($data['error'] ?: 'Data perkara belum tersedia.')); ?>
                    <?php if ($sippUrl !== '') : ?>
                        <a href="<?php echo esc_url($sippUrl); ?>" target="_blank" rel="noopener noreferrer" style="font-weight:700;color:#056e4f;">Lihat langsung di SIPP ↗</a>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </section>
        <?php
        return (string) ob_get_clean();
    }
}
add_shortcode('pa_sipp_list_perkara', 'pa_sipp_list_perkara_render');
