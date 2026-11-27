<?php
/**
 * Plugin Name: PA SINOVA Starter - Document Hub & HMAC Sync API
 * Description: Penyimpanan dokumen terstruktur 23 modul SINOVA (Rumah s.d. 2027), Shortcode SSR Hijau Gelap–Gold SK 2722/2026, serta REST API HMAC-SHA256 untuk sinkronisasi dengan portal internal (Portal Internal Laravel).
 * Version: 2.5.0-starter
 */

defined('ABSPATH') || exit;

define('PA_SINOVA_HUB_DB_VER', '2.1');

function pa_sinova_table_name(): string {
    global $wpdb;
    return $wpdb->prefix . 'pa_sinova_documents';
}

function pa_sinova_months_map(): array {
    return array(
        'JAN' => array('label' => 'Januari',   'order' => 1),
        'FEB' => array('label' => 'Februari',  'order' => 2),
        'MAR' => array('label' => 'Maret',     'order' => 3),
        'APR' => array('label' => 'April',     'order' => 4),
        'MEI' => array('label' => 'Mei',       'order' => 5),
        'JUN' => array('label' => 'Juni',      'order' => 6),
        'JUL' => array('label' => 'Juli',      'order' => 7),
        'AGS' => array('label' => 'Agustus',   'order' => 8),
        'SEP' => array('label' => 'September', 'order' => 9),
        'OKT' => array('label' => 'Oktober',   'order' => 10),
        'NOV' => array('label' => 'November',  'order' => 11),
        'DES' => array('label' => 'Desember',  'order' => 12),
    );
}

function pa_sinova_all_periods_map(): array {
    return array_merge(
        pa_sinova_months_map(),
        array(
            'SEM1'    => array('label' => 'Semester I',         'order' => 1),
            'SEM2'    => array('label' => 'Semester II',        'order' => 2),
            'TAHUNAN' => array('label' => 'Dokumen Utama',      'order' => 1),
            'REVISI'  => array('label' => 'Revisi / Perubahan', 'order' => 2),
            'REVIU'   => array('label' => 'Reviu / Evaluasi',   'order' => 3),
            'TW1'     => array('label' => 'Triwulan I',         'order' => 1),
            'TW2'     => array('label' => 'Triwulan II',        'order' => 2),
            'TW3'     => array('label' => 'Triwulan III',       'order' => 3),
            'TW4'     => array('label' => 'Triwulan IV',        'order' => 4),
        )
    );
}

function pa_sinova_module_catalog(): array {
    $satker = function_exists('pa_sinova_satker_name') ? pa_sinova_satker_name() : (get_bloginfo('name') ?: 'Pengadilan Agama');
    return array(
        // === KEUANGAN & ANGGARAN ===
        'lra' => array(
            'category'   => 'Keuangan & Anggaran',
            'title'      => 'Laporan Realisasi Anggaran (LRA)',
            'subtitle'   => 'Transparansi Realisasi Anggaran DIPA 01 & DIPA 04 — ' . $satker,
            'sub_groups' => array('SERAPAN 01', 'SERAPAN 04'),
            'periods'    => 'months',
            'start'      => 2022,
            'end'        => 2027,
        ),
        'pnbp' => array(
            'category'   => 'Keuangan & Anggaran',
            'title'      => 'Realisasi Penerimaan Negara Bukan Pajak (PNBP)',
            'subtitle'   => 'Laporan Bulanan Realisasi PNBP — ' . $satker,
            'sub_groups' => array('DOKUMEN SERAPAN 01', 'DOKUMEN SERAPAN 04'),
            'periods'    => 'months',
            'start'      => 2020,
            'end'        => 2027,
        ),
        'neraca' => array(
            'category'   => 'Keuangan & Anggaran',
            'title'      => 'Neraca Keuangan',
            'subtitle'   => 'Laporan Posisi Keuangan Bulanan DIPA 01 & DIPA 04 — ' . $satker,
            'sub_groups' => array('DIPA 01', 'DIPA 04'),
            'periods'    => 'months',
            'start'      => 2022,
            'end'        => 2027,
        ),
        'calk' => array(
            'category'   => 'Keuangan & Anggaran',
            'title'      => 'Catatan Atas Laporan Keuangan (CALK)',
            'subtitle'   => 'Catatan Atas Laporan Keuangan Semester I & II — DIPA 01 & DIPA 04',
            'sub_groups' => array('DIPA 01', 'DIPA 04'),
            'periods'    => array('SEM1', 'SEM2'),
            'start'      => 2019,
            'end'        => 2027,
        ),
        'dipa' => array(
            'category'   => 'Keuangan & Anggaran',
            'title'      => 'Daftar Isian Pelaksanaan Anggaran (DIPA)',
            'subtitle'   => 'Dokumen DIPA 01 (BUA) & DIPA 04 (Ditjen Badilag) Tahunan beserta Revisinya',
            'sub_groups' => array('DIPA 01 (Badan Urusan Administrasi)', 'DIPA 04 (Ditjen Badilag)'),
            'periods'    => array('TAHUNAN', 'REVISI'),
            'start'      => 2020,
            'end'        => 2027,
        ),
        'aset' => array(
            'category'   => 'Keuangan & Anggaran',
            'title'      => 'Asset dan Inventaris (BMN)',
            'subtitle'   => 'Laporan Pengelolaan Barang Milik Negara (BMN) & Daftar Inventaris Tahunan',
            'sub_groups' => array('LAPORAN ASET (BMN)', 'LAPORAN INVENTARIS'),
            'periods'    => array('TAHUNAN', 'REVISI'),
            'start'      => 2020,
            'end'        => 2027,
        ),
        'pbj' => array(
            'category'   => 'Keuangan & Anggaran',
            'title'      => 'Pengadaan Barang dan Jasa (RUP)',
            'subtitle'   => 'Rencana Umum Pengadaan (RUP) & Daftar Paket DIPA 01 / DIPA 04',
            'sub_groups' => array('DIPA 01', 'DIPA 04', 'RUP TAHUNAN'),
            'periods'    => array('TAHUNAN', 'REVISI'),
            'start'      => 2018,
            'end'        => 2027,
        ),

        // === KEPANITERAAN & BIAYA PERKARA ===
        'biaya-perkara' => array(
            'category'   => 'Kepaniteraan & Biaya Perkara',
            'title'      => 'Laporan Penggunaan Biaya Perkara (LIPA 7)',
            'subtitle'   => 'Transparansi Keuangan Perkara (LIPA 7A), Biaya Proses (LIPA 7B), dan Hak Kepaniteraan (LIPA 7C)',
            'sub_groups' => array('LIPA 7A (Keuangan Perkara)', 'LIPA 7B (Biaya Proses / ATK)', 'LIPA 7C (Hak Kepaniteraan)'),
            'periods'    => 'months',
            'start'      => 2020,
            'end'        => 2027,
        ),
        'sisa-panjar' => array(
            'category'   => 'Kepaniteraan & Biaya Perkara',
            'title'      => 'Laporan Pengembalian Sisa Panjar Biaya Perkara',
            'subtitle'   => 'Laporan Bulanan Transparansi Pengembalian Sisa Panjar (PSP) Perkara',
            'sub_groups' => array('LAPORAN PENGEMBALIAN SISA PANJAR'),
            'periods'    => 'months',
            'start'      => 2021,
            'end'        => 2027,
        ),
        'biaya-prodeo' => array(
            'category'   => 'Kepaniteraan & Biaya Perkara',
            'title'      => 'Rincian Biaya Perkara Prodeo Yang Dibebankan Ke Negara',
            'subtitle'   => 'Laporan Bulanan Realisasi Pembebasan Biaya Perkara (Prodeo)',
            'sub_groups' => array('RINCIAN BIAYA PERKARA PRODEO'),
            'periods'    => 'months',
            'start'      => 2020,
            'end'        => 2027,
        ),
        'hak-kepaniteraan' => array(
            'category'   => 'Kepaniteraan & Biaya Perkara',
            'title'      => 'Biaya Hak-Hak Kepaniteraan (LIPA 17 & LIPA 18)',
            'subtitle'   => 'Laporan Bulanan Penerimaan Hak-Hak Kepaniteraan dan PNBP Lainnya',
            'sub_groups' => array('LIPA 17 (Penerimaan Hak Kepaniteraan)', 'LIPA 18 (Penerimaan PNBP Lainnya)'),
            'periods'    => 'months',
            'start'      => 2020,
            'end'        => 2027,
        ),

        // === PERENCANAAN & SAKIP ===
        'lkjip' => array(
            'category'   => 'Perencanaan & SAKIP',
            'title'      => 'Laporan Akuntabilitas Kinerja Pemerintah (LKJiP)',
            'subtitle'   => 'Laporan Kinerja Instansi Pemerintah ' . $satker,
            'sub_groups' => array('DOKUMEN LKJIP'),
            'periods'    => array('TAHUNAN', 'REVISI'),
            'start'      => 2017,
            'end'        => 2027,
        ),
        'iku' => array(
            'category'   => 'Perencanaan & SAKIP',
            'title'      => 'Indikator Kinerja Utama (IKU) & Reviu IKU',
            'subtitle'   => 'Penetapan & Reviu Indikator Kinerja Utama ' . $satker,
            'sub_groups' => array('DOKUMEN IKU'),
            'periods'    => array('TAHUNAN', 'REVIU', 'REVISI'),
            'start'      => 2020,
            'end'        => 2027,
        ),
        'pk' => array(
            'category'   => 'Perencanaan & SAKIP',
            'title'      => 'Perjanjian Kinerja (PK)',
            'subtitle'   => 'Dokumen Perjanjian Kinerja Tahunan, Revisi, & Reviu ' . $satker,
            'sub_groups' => array('DOKUMEN PERJANJIAN KINERJA'),
            'periods'    => array('TAHUNAN', 'REVISI', 'REVIU'),
            'start'      => 2020,
            'end'        => 2027,
        ),
        'rkt' => array(
            'category'   => 'Perencanaan & SAKIP',
            'title'      => 'Rencana Kinerja Tahunan (RKT)',
            'subtitle'   => 'Dokumen Perencanaan Kinerja Tahunan ' . $satker,
            'sub_groups' => array('DOKUMEN RKT'),
            'periods'    => array('TAHUNAN', 'REVISI'),
            'start'      => 2020,
            'end'        => 2027,
        ),
        'rencana-aksi' => array(
            'category'   => 'Perencanaan & SAKIP',
            'title'      => 'Rencana Aksi Kinerja',
            'subtitle'   => 'Rencana Aksi Pencapaian Target Kinerja Tahunan ' . $satker,
            'sub_groups' => array('DOKUMEN RENCANA AKSI'),
            'periods'    => array('TAHUNAN', 'REVISI'),
            'start'      => 2020,
            'end'        => 2027,
        ),
        'renstra' => array(
            'category'   => 'Perencanaan & SAKIP',
            'title'      => 'Rencana Strategis (Renstra)',
            'subtitle'   => 'Dokumen Rencana Strategis 5 Tahunan & Reviu Renstra ' . $satker,
            'sub_groups' => array('DOKUMEN RENSTRA'),
            'periods'    => array('TAHUNAN', 'REVISI', 'REVIU'),
            'start'      => 2018,
            'end'        => 2027,
        ),

        // === LAYANAN PUBLIK, PPID & KEPATUHAN ===
        'akses-informasi' => array(
            'category'   => 'Layanan Publik, PPID & Kepatuhan',
            'title'      => 'Laporan Akses Informasi Publik',
            'subtitle'   => 'Arsip Resmi Laporan Bulanan Pelayanan Permintaan Informasi Publik (PPID)',
            'sub_groups' => array('LAPORAN AKSES INFORMASI'),
            'periods'    => 'months',
            'start'      => 2022,
            'end'        => 2027,
        ),
        'rekap-pengaduan' => array(
            'category'   => 'Layanan Publik, PPID & Kepatuhan',
            'title'      => 'Laporan Rekapitulasi Pengaduan Masyarakat',
            'subtitle'   => 'Arsip Resmi Laporan Bulanan Rekapitulasi & Tindak Lanjut Pengaduan',
            'sub_groups' => array('REKAPITULASI PENGADUAN'),
            'periods'    => 'months',
            'start'      => 2022,
            'end'        => 2027,
        ),
        'mou' => array(
            'category'   => 'Layanan Publik, PPID & Kepatuhan',
            'title'      => 'Arsip Surat Perjanjian Pihak Ketiga (MoU & PKS)',
            'subtitle'   => 'Slot Terjadwal Nota Kesepakatan & Perjanjian Kerja Sama s.d. 2027',
            'sub_groups' => array('MOU PEMDA & INSTANSI', 'PKS POSBAKUM & MEDIASI', 'PKS LAYANAN & LAINNYA'),
            'periods'    => 'months',
            'start'      => 2021,
            'end'        => 2027,
        ),
        'lhkpn' => array(
            'category'   => 'Layanan Publik, PPID & Kepatuhan',
            'title'      => 'Rekapitulasi LHKPN Pimpinan & Pejabat Wajib Lapor',
            'subtitle'   => 'Rekapitulasi Tahunan & Tanda Terima Pelaporan Harta Kekayaan (KPK) s.d. 2027',
            'sub_groups' => array('REKAP & BUKTI LHKPN'),
            'periods'    => array('TAHUNAN', 'REVISI'),
            'start'      => 2019,
            'end'        => 2027,
        ),
        'lhkasn' => array(
            'category'   => 'Layanan Publik, PPID & Kepatuhan',
            'title'      => 'Rekapitulasi LHKASN / Pelaporan SPT Tahunan ASN',
            'subtitle'   => 'Rekapitulasi Kepatuhan Pelaporan LHKASN & SPT Tahunan Pegawai s.d. 2027',
            'sub_groups' => array('REKAP & SPT LHKASN'),
            'periods'    => array('TAHUNAN', 'REVISI'),
            'start'      => 2022,
            'end'        => 2027,
        ),
        'survei-pelayanan' => array(
            'category'   => 'Layanan Publik, PPID & Kepatuhan',
            'title'      => 'Hasil Survei Pelayanan Publik (SKM, SPAK & SPKP)',
            'subtitle'   => 'Laporan Triwulanan Indeks Kepuasan Masyarakat (SKM), Persepsi Anti Korupsi (SPAK), & Kualitas Pelayanan (SPKP)',
            'sub_groups' => array('SKM (Indeks Kepuasan Masyarakat)', 'SPAK (Survei Persepsi Anti Korupsi)', 'SPKP (Survei Persepsi Kualitas Pelayanan)'),
            'periods'    => array('TW1', 'TW2', 'TW3', 'TW4'),
            'start'      => 2024,
            'end'        => 2027,
        ),
    );
}

function pa_sinova_module_config(string $slug): array {
    $all = pa_sinova_module_catalog();
    return $all[$slug] ?? array(
        'category'   => 'Dokumen Publik',
        'title'      => strtoupper($slug),
        'subtitle'   => function_exists('pa_sinova_satker_name') ? pa_sinova_satker_name() : 'Pengadilan Agama',
        'sub_groups' => array('DOKUMEN'),
        'periods'    => 'months',
        'start'      => 2022,
        'end'        => 2027,
    );
}

function pa_sinova_module_periods(array $cfg): array {
    $periodsCfg = $cfg['periods'] ?? 'months';
    if ($periodsCfg === 'months' || empty($periodsCfg)) {
        return pa_sinova_months_map();
    }
    $all = pa_sinova_all_periods_map();
    $out = array();
    foreach ((array) $periodsCfg as $pk) {
        $k = strtoupper((string) $pk);
        if (isset($all[$k])) {
            $out[$k] = $all[$k];
        }
    }
    return !empty($out) ? $out : pa_sinova_months_map();
}

function pa_sinova_ensure_table(): void {
    global $wpdb;
    $table = pa_sinova_table_name();
    if (get_option('pa_sinova_hub_db_ver') === PA_SINOVA_HUB_DB_VER) {
        // Saat di WP Admin, verifikasi ringan bila tabel hilang akibat restore DB parsial
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
    $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `page_slug` VARCHAR(64) NOT NULL,
        `year` SMALLINT UNSIGNED NOT NULL,
        `sub_group` VARCHAR(64) NOT NULL DEFAULT '',
        `period_key` VARCHAR(32) NOT NULL,
        `period_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        `title` VARCHAR(255) NOT NULL DEFAULT '',
        `source_type` VARCHAR(20) NOT NULL DEFAULT 'external',
        `doc_url` TEXT NOT NULL,
        `file_size_kb` INT UNSIGNED NULL DEFAULT NULL,
        `updated_by` VARCHAR(120) NOT NULL DEFAULT '',
        `status` VARCHAR(20) NOT NULL DEFAULT 'published',
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_slot` (`page_slug`, `year`, `sub_group`, `period_key`),
        KEY `idx_slug_year` (`page_slug`, `year`)
    ) {$charset};";
    $wpdb->query($sql);
    $created = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($created === $table) {
        update_option('pa_sinova_hub_db_ver', PA_SINOVA_HUB_DB_VER, false);
    }
}
add_action('init', 'pa_sinova_ensure_table', 5);

function pa_sinova_flush_caches_for_slug(string $slug): void {
    $seeded = get_option('pa_sinova_seeded_pages', array());
    $pid    = (is_array($seeded) && !empty($seeded['mod:' . $slug])) ? (int) $seeded['mod:' . $slug] : 0;
    if ($pid > 0) {
        clean_post_cache($pid);
    }
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    if (function_exists('pa_home_page_cache_mark_stale')) {
        pa_home_page_cache_mark_stale();
    }
    if (class_exists('\LiteSpeed\Purge') && $pid > 0) {
        try {
            \LiteSpeed\Purge::purge_post($pid);
        } catch (\Throwable $e) {
            // Abaikan bila LiteSpeed Cache tidak terpasang
        }
    }
}

function pa_sinova_verify_hmac(WP_REST_Request $req) {
    $key = function_exists('pa_sinova_get_secret_key') ? pa_sinova_get_secret_key() : '';
    if (strlen($key) < 32) {
        return new WP_Error('sinova_key_missing', 'Secret Key HMAC-SHA256 belum diatur pada Pengaturan SINOVA Hub.', array('status' => 503));
    }

    $ts    = (string) $req->get_header('X-PA-Sinova-Timestamp');
    $nonce = (string) $req->get_header('X-PA-Sinova-Nonce');
    $sig   = strtolower(trim((string) $req->get_header('X-PA-Sinova-Signature')));

    if (!ctype_digit($ts) || abs(time() - (int) $ts) > 120) {
        return new WP_Error('sinova_ts_invalid', 'Timestamp permintaan tidak valid atau kedaluwarsa.', array('status' => 401));
    }
    if (!preg_match('/^[A-Za-z0-9_-]{16,64}$/', $nonce)) {
        return new WP_Error('sinova_nonce_invalid', 'Nonce permintaan tidak valid.', array('status' => 401));
    }
    if (!preg_match('/^[a-f0-9]{64}$/', $sig)) {
        return new WP_Error('sinova_sig_invalid', 'Format tanda tangan HMAC tidak valid.', array('status' => 401));
    }

    $replayKey = 'pa_sinova_nonce_' . hash('sha256', $nonce);
    if (get_transient($replayKey) !== false) {
        return new WP_Error('sinova_nonce_replay', 'Nonce sudah pernah digunakan.', array('status' => 401));
    }

    $method    = strtoupper((string) $req->get_method());
    $route     = '/' . ltrim((string) $req->get_route(), '/');
    $rawBody   = $method === 'GET' ? '' : (string) $req->get_body();
    $canonical = $method . "\n" . $route . "\n" . $ts . "\n" . $nonce . "\n" . hash('sha256', $rawBody);
    $expected  = hash_hmac('sha256', $canonical, $key);

    if (!hash_equals($expected, $sig)) {
        return new WP_Error('sinova_sig_mismatch', 'Tanda tangan HMAC tidak cocok.', array('status' => 403));
    }

    set_transient($replayKey, 1, 180);
    return true;
}

function pa_sinova_safe_unlink_local_pdf(string $docUrl): void {
    $baseUrl = trailingslashit(content_url('uploads/sinova-docs'));
    if (strpos($docUrl, $baseUrl) !== 0) {
        return;
    }
    $rel  = ltrim(substr($docUrl, strlen($baseUrl)), '/');
    $root = realpath(WP_CONTENT_DIR . '/uploads/sinova-docs');
    if (!$root || $rel === '') {
        return;
    }
    $target = realpath($root . DIRECTORY_SEPARATOR . $rel);
    if ($target && strpos($target, $root . DIRECTORY_SEPARATOR) === 0 && is_file($target)) {
        @unlink($target);
    }
}

add_action('rest_api_init', static function () {
    register_rest_route('pa-sinova/v1', '/ping', array(
        'methods'             => 'GET',
        'permission_callback' => 'pa_sinova_verify_hmac',
        'callback'            => static function () {
            pa_sinova_ensure_table();
            global $wpdb;
            $table  = pa_sinova_table_name();
            $counts = $wpdb->get_results("SELECT page_slug, COUNT(*) AS total FROM `{$table}` WHERE status='published' GROUP BY page_slug", ARRAY_A);
            return new WP_REST_Response(array(
                'ok'           => true,
                'site'         => home_url('/'),
                'server_time'  => gmdate('c'),
                'module_count' => count(pa_sinova_module_catalog()),
                'counts'       => $counts ?: array(),
            ), 200);
        },
    ));

    register_rest_route('pa-sinova/v1', '/catalog', array(
        'methods'             => 'GET',
        'permission_callback' => 'pa_sinova_verify_hmac',
        'callback'            => static function () {
            $catalog = pa_sinova_module_catalog();
            $out = array();
            foreach ($catalog as $slug => $cfg) {
                $cfg['slug'] = $slug;
                $cfg['resolved_periods'] = pa_sinova_module_periods($cfg);
                if (function_exists('pa_sinova_resolve_page_url')) {
                    $cfg['public_url'] = pa_sinova_resolve_page_url($slug);
                }
                $out[$slug] = $cfg;
            }
            return new WP_REST_Response(array('ok' => true, 'modules' => $out), 200);
        },
    ));

    // Endpoint Push Jadwal Sidang otomatis dari server SIPP lokal (terautentikasi HMAC-SHA256)
    register_rest_route('pa-sinova/v1', '/sipp/push-hearings', array(
        'methods'             => 'POST',
        'permission_callback' => 'pa_sinova_verify_hmac',
        'callback'            => static function (WP_REST_Request $req) {
            if (!function_exists('pa_portal_records_ensure_table') || !function_exists('pa_portal_records_table')) {
                return new WP_Error('sinova_records_unavailable', 'Modul data operasional belum aktif.', array('status' => 503));
            }
            pa_portal_records_ensure_table();
            $body        = json_decode((string) $req->get_body(), true);
            if (!is_array($body)) {
                return new WP_Error('sinova_bad_json', 'Body JSON tidak valid.', array('status' => 400));
            }
            $targetDate  = sanitize_text_field((string) ($body['date'] ?? wp_date('Y-m-d')));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate)) {
                $targetDate = wp_date('Y-m-d');
            }
            $replaceDate = !isset($body['replace_date']) || !empty($body['replace_date']);
            $rawItems    = isset($body['items']) && is_array($body['items']) ? array_slice($body['items'], 0, 300) : array();

            global $wpdb;
            $rTable = pa_portal_records_table();
            if ($replaceDate) {
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM `{$rTable}` WHERE record_type = 'jadwal' AND event_date = %s",
                    $targetDate
                ));
            }
            $now      = current_time('mysql');
            $inserted = 0;
            foreach ($rawItems as $idx => $it) {
                if (!is_array($it)) {
                    continue;
                }
                $nomor = sanitize_text_field((string) ($it['nomor'] ?? ($it['title'] ?? '')));
                if ($nomor === '') {
                    continue;
                }
                $agenda = sanitize_text_field((string) ($it['agenda'] ?? ($it['subtitle'] ?? 'Persidangan')));
                $ruang  = sanitize_text_field((string) ($it['ruang'] ?? ($it['category'] ?? 'Ruang Sidang')));
                $jam    = sanitize_text_field((string) ($it['jam'] ?? '09.00 WIB'));
                $ok     = $wpdb->insert($rTable, array(
                    'record_type' => 'jadwal',
                    'title'       => $nomor,
                    'subtitle'    => $agenda,
                    'category'    => $ruang,
                    'event_date'  => $targetDate,
                    'meta_json'   => wp_json_encode(array('jam' => $jam, 'source' => 'sipp_push')) ?: '{}',
                    'sort_order'  => $idx + 1,
                    'status'      => 'published',
                    'updated_at'  => $now,
                ));
                if ($ok) {
                    $inserted++;
                }
            }
            $pushedAt = wp_date('d-m-Y H:i') . ' WIB';
            update_option('pa_sipp_last_push_at', $pushedAt, false);
            delete_transient('pa_sipp_hearings_today');
            if (function_exists('pa_sinova_purge_html_cache_files')) {
                pa_sinova_purge_html_cache_files();
            } elseif (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }

            return new WP_REST_Response(array(
                'ok'        => true,
                'date'      => $targetDate,
                'inserted'  => $inserted,
                'pushed_at' => $pushedAt,
            ), 200);
        },
    ));

    // Endpoint Push Putusan Pilihan / Salinan dari SIPP lokal (HMAC-SHA256)
    register_rest_route('pa-sinova/v1', '/sipp/push-decisions', array(
        'methods'             => 'POST',
        'permission_callback' => 'pa_sinova_verify_hmac',
        'callback'            => static function (WP_REST_Request $req) {
            if (!function_exists('pa_portal_records_ensure_table') || !function_exists('pa_portal_records_table')) {
                return new WP_Error('sinova_records_unavailable', 'Modul data operasional belum aktif.', array('status' => 503));
            }
            pa_portal_records_ensure_table();
            $body = json_decode((string) $req->get_body(), true);
            if (!is_array($body)) {
                return new WP_Error('sinova_bad_json', 'Body JSON tidak valid.', array('status' => 400));
            }
            $replaceAll = !empty($body['replace_all']);
            $rawItems   = isset($body['items']) && is_array($body['items']) ? array_slice($body['items'], 0, 100) : array();

            global $wpdb;
            $rTable = pa_portal_records_table();
            if ($replaceAll) {
                $wpdb->query("DELETE FROM `{$rTable}` WHERE record_type = 'putusan'");
            }
            $now      = current_time('mysql');
            $inserted = 0;
            foreach ($rawItems as $idx => $it) {
                if (!is_array($it)) {
                    continue;
                }
                $nomor = sanitize_text_field((string) ($it['nomor'] ?? ($it['title'] ?? '')));
                if ($nomor === '') {
                    continue;
                }
                $amar   = sanitize_text_field((string) ($it['amar'] ?? ($it['subtitle'] ?? 'Putusan Terpublikasi')));
                $jenis  = sanitize_text_field((string) ($it['jenis'] ?? ($it['category'] ?? 'Perkara Perdata Agama')));
                $dtRaw  = sanitize_text_field((string) ($it['tanggal'] ?? ($it['date'] ?? wp_date('Y-m-d'))));
                $dt     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dtRaw) ? $dtRaw : wp_date('Y-m-d');
                $docUrl = esc_url_raw((string) ($it['doc_url'] ?? ($it['url'] ?? '')), array('http', 'https'));
                $majelis = sanitize_text_field((string) ($it['majelis'] ?? ''));
                $statusH = sanitize_text_field((string) ($it['status'] ?? 'Berkekuatan Hukum Tetap'));

                $existingId = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM `{$rTable}` WHERE record_type = 'putusan' AND title = %s LIMIT 1",
                    $nomor
                ));
                $rowPayload = array(
                    'record_type' => 'putusan',
                    'title'       => $nomor,
                    'subtitle'    => $amar,
                    'category'    => $jenis,
                    'event_date'  => $dt,
                    'meta_json'   => wp_json_encode(array(
                        'doc_url'      => $docUrl,
                        'majelis'      => $majelis,
                        'status_hukum' => $statusH,
                        'source'       => 'sipp_push',
                    )) ?: '{}',
                    'sort_order'  => $idx + 1,
                    'status'      => 'published',
                    'updated_at'  => $now,
                );
                if ($existingId > 0) {
                    $wpdb->update($rTable, $rowPayload, array('id' => $existingId));
                    $inserted++;
                } elseif ($wpdb->insert($rTable, $rowPayload)) {
                    $inserted++;
                }
            }
            $pushedAt = wp_date('d-m-Y H:i') . ' WIB';
            update_option('pa_sipp_last_decision_push_at', $pushedAt, false);
            if (function_exists('pa_sinova_purge_html_cache_files')) {
                pa_sinova_purge_html_cache_files();
            } elseif (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }

            return new WP_REST_Response(array(
                'ok'        => true,
                'inserted'  => $inserted,
                'pushed_at' => $pushedAt,
            ), 200);
        },
    ));

    register_rest_route('pa-sinova/v1', '/simtepa/push-staff', array(
        'methods'             => 'POST',
        'permission_callback' => 'pa_sinova_verify_hmac',
        'callback'            => static function (WP_REST_Request $req) {
            if (!function_exists('pa_portal_simtepa_normalize_row') || !function_exists('pa_portal_upsert_simtepa_records')) {
                return new WP_Error('pa_records_missing', 'Modul pegawai belum aktif.', array('status' => 500));
            }
            $body    = $req->get_json_params();
            $rawList = is_array($body['items'] ?? null) ? $body['items'] : (is_array($body['data'] ?? null) ? $body['data'] : array());
            $replace = !empty($body['replace_all']);
            $items   = array();
            foreach ($rawList as $rawRow) {
                if (!is_array($rawRow)) {
                    continue;
                }
                $norm = pa_portal_simtepa_normalize_row($rawRow, '');
                if ($norm !== null) {
                    $items[] = $norm;
                }
            }
            if (empty($items)) {
                return new WP_Error('pa_empty_staff', 'Daftar items/data pegawai kosong atau tidak valid.', array('status' => 422));
            }
            $saved    = pa_portal_upsert_simtepa_records($items, 'simtepa_push', $replace);
            $pushedAt = wp_date('d-m-Y H:i') . ' WIB';
            update_option('pa_simtepa_sync_status', array(
                'status'     => 'ok',
                'count'      => $saved,
                'source_url' => 'HMAC Push API (/simtepa/push-staff)',
                'synced_at'  => $pushedAt,
                'timestamp'  => time(),
                'message'    => "Diterima via Push API SIMTEPA ({$saved} pegawai).",
            ), false);
            return new WP_REST_Response(array(
                'ok'        => true,
                'saved'     => $saved,
                'pushed_at' => $pushedAt,
            ), 200);
        },
    ));

    register_rest_route('pa-sinova/v1', '/documents', array(
        'methods'             => 'GET',
        'permission_callback' => 'pa_sinova_verify_hmac',
        'callback'            => static function (WP_REST_Request $req) {
            pa_sinova_ensure_table();
            global $wpdb;
            $table = pa_sinova_table_name();
            $slug  = sanitize_key((string) $req->get_param('slug'));
            if ($slug !== '') {
                $rows = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM `{$table}` WHERE page_slug = %s ORDER BY year DESC, sub_group ASC, period_order ASC, id ASC",
                    $slug
                ), ARRAY_A);
            } else {
                $rows = $wpdb->get_results(
                    "SELECT * FROM `{$table}` ORDER BY page_slug ASC, year DESC, sub_group ASC, period_order ASC, id ASC",
                    ARRAY_A
                );
            }
            return new WP_REST_Response(array('ok' => true, 'documents' => $rows ?: array()), 200);
        },
    ));

    register_rest_route('pa-sinova/v1', '/upsert', array(
        'methods'             => 'POST',
        'permission_callback' => 'pa_sinova_verify_hmac',
        'callback'            => static function (WP_REST_Request $req) {
            pa_sinova_ensure_table();
            global $wpdb;
            $table   = pa_sinova_table_name();
            $periods = pa_sinova_all_periods_map();
            $params  = json_decode((string) $req->get_body(), true);
            if (!is_array($params)) {
                return new WP_Error('sinova_bad_json', 'Payload JSON tidak valid.', array('status' => 400));
            }

            $slug      = strtolower(trim((string) ($params['page_slug'] ?? '')));
            $year      = (int) ($params['year'] ?? 0);
            $subGroup  = trim(sanitize_text_field((string) ($params['sub_group'] ?? '')));
            $periodKey = strtoupper(trim((string) ($params['period_key'] ?? '')));
            $title     = trim(sanitize_text_field((string) ($params['title'] ?? '')));
            $srcType   = strtolower(trim((string) ($params['source_type'] ?? 'external')));
            $docUrl    = trim((string) ($params['doc_url'] ?? ''));
            $updatedBy = trim(sanitize_text_field((string) ($params['updated_by'] ?? 'Portal Internal')));

            if (!preg_match('/^[a-z0-9_-]{2,64}$/', $slug)) {
                return new WP_Error('sinova_bad_slug', 'Slug modul tidak valid.', array('status' => 422));
            }
            if ($year < 2017 || $year > 2035) {
                return new WP_Error('sinova_bad_year', 'Tahun harus di antara 2017–2035.', array('status' => 422));
            }
            if (!preg_match('/^[A-Z0-9_-]{1,32}$/', $periodKey)) {
                return new WP_Error('sinova_bad_period', 'Periode tidak valid.', array('status' => 422));
            }
            if (!in_array($srcType, array('wp_pdf', 'gdrive', 'external'), true)) {
                return new WP_Error('sinova_bad_source', 'Tipe sumber dokumen tidak valid.', array('status' => 422));
            }

            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM `{$table}` WHERE page_slug = %s AND year = %d AND sub_group = %s AND period_key = %s",
                $slug, $year, $subGroup, $periodKey
            ), ARRAY_A);

            $fileSizeKb = null;
            if ($srcType === 'wp_pdf') {
                $b64 = (string) ($params['pdf_base64'] ?? '');
                $bin = $b64 !== '' ? base64_decode($b64, true) : '';
                if (!is_string($bin) || $bin === '') {
                    return new WP_Error('sinova_pdf_decode', 'Berkas PDF wajib disertakan & valid.', array('status' => 422));
                }
                $len = strlen($bin);
                if ($len > 10 * 1024 * 1024) {
                    return new WP_Error('sinova_pdf_size', 'Ukuran PDF melebihi 10 MB.', array('status' => 422));
                }
                if (substr($bin, 0, 5) !== '%PDF-') {
                    return new WP_Error('sinova_pdf_magic', 'Berkas bukan PDF asli.', array('status' => 422));
                }
                if (preg_match('/(<\?php|<\?=|<script\b)/i', substr($bin, 0, 8192))) {
                    return new WP_Error('sinova_pdf_unsafe', 'Konten ditolak filter keamanan.', array('status' => 422));
                }

                $destDir = WP_CONTENT_DIR . "/uploads/sinova-docs/{$year}/{$slug}";
                if (!is_dir($destDir) && !wp_mkdir_p($destDir)) {
                    return new WP_Error('sinova_mkdir_fail', 'Gagal membuat direktori unggahan.', array('status' => 500));
                }
                $safeSub  = preg_replace('/[^a-z0-9]+/', '-', strtolower($subGroup ?: 'dok')) ?: 'dok';
                $safePer  = strtolower(preg_replace('/[^a-z0-9]+/i', '', $periodKey));
                $hash     = substr(hash('sha256', $bin . microtime(true)), 0, 8);
                $safeFile = "{$slug}-{$year}-{$safeSub}-{$safePer}-{$hash}.pdf";
                $destPath = "{$destDir}/{$safeFile}";

                if (file_put_contents($destPath, $bin, LOCK_EX) === false) {
                    return new WP_Error('sinova_write_fail', 'Gagal menulis PDF ke server.', array('status' => 500));
                }
                @chmod($destPath, 0644);
                if ($existing && ($existing['source_type'] ?? '') === 'wp_pdf' && !empty($existing['doc_url'])) {
                    pa_sinova_safe_unlink_local_pdf((string) $existing['doc_url']);
                }
                $docUrl     = content_url("uploads/sinova-docs/{$year}/{$slug}/{$safeFile}");
                $fileSizeKb = (int) ceil($len / 1024);
            } else {
                $docUrl = esc_url_raw($docUrl, array('https'));
                if ($docUrl === '' || !filter_var($docUrl, FILTER_VALIDATE_URL) || stripos($docUrl, 'https://') !== 0) {
                    return new WP_Error('sinova_bad_url', 'URL eksternal wajib diawali https://.', array('status' => 422));
                }
                if ($existing && ($existing['source_type'] ?? '') === 'wp_pdf' && !empty($existing['doc_url'])) {
                    pa_sinova_safe_unlink_local_pdf((string) $existing['doc_url']);
                }
            }

            $periodOrder = (int) ($periods[$periodKey]['order'] ?? 99);
            if ($title === '') {
                $periodLabel = $periods[$periodKey]['label'] ?? $periodKey;
                $title = trim("{$subGroup} {$periodLabel} {$year}");
            }

            $now  = current_time('mysql');
            $data = array(
                'page_slug'    => $slug,
                'year'         => $year,
                'sub_group'    => $subGroup,
                'period_key'   => $periodKey,
                'period_order' => $periodOrder,
                'title'        => $title,
                'source_type'  => $srcType,
                'doc_url'      => $docUrl,
                'file_size_kb' => $fileSizeKb,
                'updated_by'   => $updatedBy,
                'status'       => 'published',
                'updated_at'   => $now,
            );
            if ($existing) {
                $wpdb->update($table, $data, array('id' => (int) $existing['id']));
                $rowId = (int) $existing['id'];
            } else {
                $data['created_at'] = $now;
                $wpdb->insert($table, $data);
                $rowId = (int) $wpdb->insert_id;
            }

            $saved = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE id = %d", $rowId), ARRAY_A);
            pa_sinova_flush_caches_for_slug($slug);

            return new WP_REST_Response(array('ok' => true, 'document' => $saved), 200);
        },
    ));

    register_rest_route('pa-sinova/v1', '/delete', array(
        'methods'             => 'POST',
        'permission_callback' => 'pa_sinova_verify_hmac',
        'callback'            => static function (WP_REST_Request $req) {
            pa_sinova_ensure_table();
            global $wpdb;
            $table  = pa_sinova_table_name();
            $params = json_decode((string) $req->get_body(), true);
            if (!is_array($params)) {
                return new WP_Error('sinova_bad_json', 'Payload JSON tidak valid.', array('status' => 400));
            }
            $slug      = strtolower(trim((string) ($params['page_slug'] ?? '')));
            $year      = (int) ($params['year'] ?? 0);
            $subGroup  = trim(sanitize_text_field((string) ($params['sub_group'] ?? '')));
            $periodKey = strtoupper(trim((string) ($params['period_key'] ?? '')));

            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM `{$table}` WHERE page_slug = %s AND year = %d AND sub_group = %s AND period_key = %s",
                $slug, $year, $subGroup, $periodKey
            ), ARRAY_A);

            if ($existing) {
                if (($existing['source_type'] ?? '') === 'wp_pdf' && !empty($existing['doc_url'])) {
                    pa_sinova_safe_unlink_local_pdf((string) $existing['doc_url']);
                }
                $wpdb->delete($table, array('id' => (int) $existing['id']));
                pa_sinova_flush_caches_for_slug($slug);
            }
            return new WP_REST_Response(array('ok' => true, 'deleted' => (bool) $existing), 200);
        },
    ));
});

function pa_sinova_render_year_card(int $year, array $subGroups, array $grid, string $modTitle, array $periods, bool $featured = false): string {
    $filledCount = 0;
    $totalSlots  = count($subGroups) * count($periods);
    $isMonthly   = isset($periods['JAN']);
    $unitWord    = $isMonthly ? 'Bulan' : 'Dokumen';

    foreach ($subGroups as $sg) {
        foreach ($periods as $pKey => $_pInfo) {
            if (!empty($grid[$year][$sg][$pKey])) {
                $filledCount++;
            }
        }
    }

    $curY      = (int) wp_date('Y');
    $cardClass = $featured ? 'pa-sinova-year-card is-featured' : 'pa-sinova-year-card';
    $badgeText = $year > $curY ? 'Arsip Terjadwal ' . $year : ($year === $curY ? 'Tahun Berjalan' : 'Arsip Tahun ' . $year);

    ob_start();
    ?>
    <section class="<?php echo esc_attr($cardClass); ?>" aria-label="<?php echo esc_attr($modTitle . ' Tahun ' . $year); ?>">
        <header class="pa-sinova-year-head">
            <div class="pa-sinova-year-title">
                <span class="pa-sinova-year-num"><?php echo esc_html((string) $year); ?></span>
                <span class="pa-sinova-year-pill"><?php echo esc_html($badgeText); ?></span>
            </div>
            <span class="pa-sinova-year-stat"><?php echo esc_html("{$filledCount} / {$totalSlots} {$unitWord} Terpublikasi"); ?></span>
        </header>
        <div class="pa-sinova-year-body">
            <?php foreach ($subGroups as $sg) : ?>
                <div class="pa-sinova-subgroup">
                    <h3 class="pa-sinova-subgroup-title"><?php echo esc_html($sg); ?></h3>
                    <div class="pa-sinova-month-grid">
                        <?php foreach ($periods as $pKey => $pInfo) :
                            $doc = $grid[$year][$sg][$pKey] ?? null;
                            if ($doc && !empty($doc['doc_url'])) :
                                $url   = (string) $doc['doc_url'];
                                $isPdf = (($doc['source_type'] ?? '') === 'wp_pdf');
                                $badge = $isPdf ? 'PDF Resmi' : (stripos($url, 'drive.google.com') !== false ? 'Drive' : 'Tautan');
                                $displayTitle = !empty($doc['title']) && !$isMonthly ? (string) $doc['title'] : $pInfo['label'];
                                $previewUrl   = '';
                                if (preg_match('/\.pdf(?:$|\?)/i', $url) || $isPdf) {
                                    $previewUrl = $url;
                                } elseif (preg_match('#drive\.google\.com/file/d/([A-Za-z0-9_-]+)#i', $url, $dm) || preg_match('#drive\.google\.com/open\?id=([A-Za-z0-9_-]+)#i', $url, $dm)) {
                                    $previewUrl = 'https://drive.google.com/file/d/' . $dm[1] . '/preview';
                                }
                        ?>
                            <div class="pa-sinova-slot-wrap">
                                <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" class="pa-sinova-btn is-filled">
                                    <span class="pa-sinova-btn-month"><?php echo esc_html($isMonthly ? $pInfo['label'] : $displayTitle); ?></span>
                                    <span class="pa-sinova-btn-meta"><?php echo esc_html(($isMonthly ? '' : $pInfo['label'] . ' · ') . $badge); ?> ↗</span>
                                </a>
                                <?php if ($previewUrl !== '') : ?>
                                    <button type="button" class="pa-doc-preview-trigger" data-preview-url="<?php echo esc_url($previewUrl); ?>" data-open-url="<?php echo esc_url($url); ?>" data-preview-title="<?php echo esc_attr($modTitle . ' — ' . ($isMonthly ? $pInfo['label'] : $displayTitle) . ' ' . $year); ?>" aria-label="<?php echo esc_attr('Pratinjau ' . ($isMonthly ? $pInfo['label'] : $displayTitle) . ' ' . $year); ?>">Pratinjau</button>
                                <?php endif; ?>
                            </div>
                        <?php else : ?>
                            <span class="pa-sinova-btn is-empty" aria-disabled="true">
                                <span class="pa-sinova-btn-month"><?php echo esc_html($pInfo['label']); ?></span>
                                <span class="pa-sinova-btn-meta">Menunggu Publikasi</span>
                            </span>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}

add_shortcode('pa_sinova_docs', static function ($atts) {
    pa_sinova_ensure_table();
    $cfgAtts = shortcode_atts(array('slug' => 'lra', 'start' => '', 'end' => ''), $atts, 'pa_sinova_docs');
    $slug    = sanitize_key($cfgAtts['slug']);
    $cfg     = pa_sinova_module_config($slug);
    $start   = $cfgAtts['start'] !== '' ? (int) $cfgAtts['start'] : (int) $cfg['start'];
    $end     = $cfgAtts['end'] !== '' ? (int) $cfgAtts['end'] : (int) $cfg['end'];
    $periods = pa_sinova_module_periods($cfg);

    global $wpdb;
    $table = pa_sinova_table_name();
    $rows  = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM `{$table}` WHERE page_slug = %s AND status = 'published' ORDER BY year DESC, sub_group ASC, period_order ASC",
        $slug
    ), ARRAY_A);

    $grid = array();
    $totalPublished = 0;
    foreach ((array) $rows as $r) {
        $grid[(int) $r['year']][(string) $r['sub_group']][strtoupper((string) $r['period_key'])] = $r;
        $totalPublished++;
    }

    $featuredYears = array();
    $archiveYears  = array();
    $curY = (int) wp_date('Y');
    for ($y = $end; $y >= $start; $y--) {
        if ($y >= $curY) $featuredYears[] = $y;
        else $archiveYears[] = $y;
    }

    $resolveSubGroups = static function (int $year) use ($cfg, $grid, $curY): array {
        $configured = (array) $cfg['sub_groups'];
        if ($year >= $curY) return $configured;
        $active = array_values(array_filter($configured, static fn ($sg) => !empty($grid[$year][$sg])));
        return !empty($active) ? $active : $configured;
    };

    $uid = 'pa-sinova-' . esc_attr($slug);
    ob_start();
    ?>
    <div id="pa-sinova-hub" class="pa-sinova-hub" data-slug="<?php echo esc_attr($slug); ?>">
        <header class="pa-sinova-hero">
            <span class="pa-sinova-kicker">Keterbukaan Informasi Publik · Arsip Dokumen Resmi</span>
            <h2><?php echo esc_html($cfg['title']); ?></h2>
            <p class="pa-sinova-sub"><?php echo esc_html($cfg['subtitle']); ?></p>
            <div class="pa-sinova-summary">
                <span>Total Terpublikasi: <strong><?php echo esc_html((string) $totalPublished); ?></strong> Dokumen</span>
                <span>Cakupan Slot: <strong><?php echo esc_html("{$start} – {$end}"); ?></strong></span>
            </div>
        </header>

        <div class="pa-sinova-featured-stack">
            <?php foreach ($featuredYears as $fy) : ?>
                <?php echo pa_sinova_render_year_card($fy, $resolveSubGroups($fy), $grid, $cfg['title'], $periods, true); ?>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($archiveYears)) : ?>
            <div class="pa-sinova-archive-wrap" id="<?php echo $uid; ?>-archive">
                <h3 class="pa-sinova-archive-heading">Arsip Tahun Sebelumnya (<?php echo esc_html(min($archiveYears) . ' – ' . max($archiveYears)); ?>)</h3>
                <div class="pa-sinova-tabs" role="tablist" aria-label="Pilih Tahun Arsip">
                    <?php foreach ($archiveYears as $idx => $ay) :
                        $tabId  = $uid . '-tab-' . (int) $ay;
                        $paneId = $uid . '-pane-' . (int) $ay;
                    ?>
                        <button type="button" role="tab" id="<?php echo esc_attr($tabId); ?>" aria-selected="<?php echo $idx === 0 ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr($paneId); ?>" tabindex="<?php echo $idx === 0 ? '0' : '-1'; ?>" class="pa-sinova-tab-btn<?php echo $idx === 0 ? ' is-active' : ''; ?>" data-pa-year-tab="<?php echo esc_attr((string) $ay); ?>">
                            Tahun <?php echo esc_html((string) $ay); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="pa-sinova-tab-panels">
                    <?php foreach ($archiveYears as $idx => $ay) :
                        $tabId  = $uid . '-tab-' . (int) $ay;
                        $paneId = $uid . '-pane-' . (int) $ay;
                    ?>
                        <div id="<?php echo esc_attr($paneId); ?>" class="pa-sinova-tab-pane<?php echo $idx === 0 ? ' is-active' : ''; ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr($tabId); ?>" data-pa-year-pane="<?php echo esc_attr((string) $ay); ?>"<?php echo $idx === 0 ? '' : ' hidden'; ?>>
                            <?php echo pa_sinova_render_year_card($ay, $resolveSubGroups($ay), $grid, $cfg['title'], $periods, false); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <style id="pa-sinova-hub-css">
        .pa-sinova-hub{max-width:1080px;margin:16px auto 28px;font-family:'Inter',system-ui,sans-serif;color:#111827}
        .pa-sinova-hero{padding:28px 24px;border-radius:16px;background:linear-gradient(135deg,#043927 0%,#056e4f 100%);color:#fff;border-bottom:5px solid #C9A227;box-shadow:0 12px 28px rgba(4,57,39,.18);text-align:center;margin-bottom:22px}
        .pa-sinova-kicker{display:inline-block;padding:4px 12px;border-radius:999px;background:rgba(201,162,39,.18);border:1px solid #C9A227;color:#E5C158;font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;margin-bottom:10px}
        .pa-sinova-hero h2{margin:0 0 8px;color:#fff;font-size:clamp(1.35rem,2.6vw,1.95rem);font-weight:800}
        .pa-sinova-sub{margin:0 0 14px;color:#e6f4ee;font-size:.95rem}
        .pa-sinova-summary{display:flex;flex-wrap:wrap;justify-content:center;gap:10px 18px;font-size:12.5px;color:#e6f4ee}
        .pa-sinova-summary strong{color:#E5C158}
        .pa-sinova-year-card{background:#fff;border:1px solid #d5e6dc;border-radius:14px;overflow:hidden;box-shadow:0 6px 20px rgba(5,110,79,.06);margin-bottom:20px}
        .pa-sinova-year-card.is-featured{border-color:#C9A227}
        .pa-sinova-year-head{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:10px;padding:14px 20px;background:linear-gradient(135deg,#056e4f 0%,#044a35 100%);border-bottom:3px solid #C9A227;color:#fff}
        .pa-sinova-year-title{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .pa-sinova-year-num{font-size:1.35rem;font-weight:800;color:#E5C158}
        .pa-sinova-year-pill{padding:3px 10px;border-radius:999px;background:rgba(255,255,255,.14);border:1px solid rgba(229,193,88,.45);color:#fff;font-size:11.5px;font-weight:700}
        .pa-sinova-year-stat{font-size:12.5px;font-weight:600;color:#e6f4ee}
        .pa-sinova-year-body{padding:18px 20px}
        .pa-sinova-subgroup{margin-bottom:18px}
        .pa-sinova-subgroup:last-child{margin-bottom:0}
        .pa-sinova-subgroup-title{margin:0 0 10px;padding-bottom:6px;border-bottom:2px solid #edf5f0;color:#056e4f;font-size:.95rem;font-weight:800;text-transform:uppercase}
        .pa-sinova-month-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px}
        .pa-sinova-btn{display:flex;flex-direction:column;justify-content:center;align-items:flex-start;min-height:56px;padding:9px 12px;border-radius:10px;text-decoration:none!important;transition:.16s ease}
        .pa-sinova-slot-wrap:has(.pa-doc-preview-trigger) .pa-sinova-btn{padding-right:84px}
        .pa-sinova-btn.is-filled{background:#f4faf7;border:1.5px solid #056e4f;color:#043927}
        .pa-sinova-btn.is-filled:hover{background:#056e4f;border-color:#C9A227;color:#fff;transform:translateY(-2px)}
        .pa-sinova-btn.is-filled .pa-sinova-btn-month{font-weight:800;font-size:13.5px;overflow-wrap:anywhere;max-width:100%}
        .pa-sinova-btn.is-filled .pa-sinova-btn-meta{font-size:11px;font-weight:700;color:#6e5204;margin-top:2px;overflow-wrap:anywhere;max-width:100%}
        .pa-sinova-btn.is-filled:hover .pa-sinova-btn-meta{color:#E5C158}
        .pa-sinova-btn.is-empty{background:#f8fafc;border:1px dashed #cbd5e1;color:#475569}
        .pa-sinova-btn.is-empty .pa-sinova-btn-month{font-weight:700;font-size:13px;overflow-wrap:anywhere;max-width:100%}
        .pa-sinova-btn.is-empty .pa-sinova-btn-meta{font-size:10.5px;color:#475569;margin-top:2px;overflow-wrap:anywhere;max-width:100%}
        .pa-sinova-archive-wrap{margin-top:24px;padding:18px 20px;border-radius:14px;background:#f7fbf8;border:1px solid #d5e6dc}
        .pa-sinova-archive-heading{margin:0 0 12px;color:#056e4f;font-size:1.05rem;font-weight:800}
        .pa-sinova-tabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
        .pa-sinova-tab-btn{min-height:40px;padding:7px 14px;border-radius:9px;border:1.5px solid #056e4f;background:#fff;color:#056e4f;font-weight:700;font-size:13px;cursor:pointer}
        .pa-sinova-tab-btn.is-active{background:#056e4f;border-color:#C9A227;color:#fff}
        .pa-sinova-tab-pane{display:none}
        .pa-sinova-tab-pane.is-active{display:block}
        html[data-pa-theme="dark"] .pa-sinova-hub{color:#e6f4ee}
        html[data-pa-theme="dark"] .pa-sinova-year-card,html[data-pa-theme="dark"] .pa-sinova-archive-wrap{background:#182a20;border-color:#355241}
        html[data-pa-theme="dark"] .pa-sinova-subgroup-title,html[data-pa-theme="dark"] .pa-sinova-archive-heading{color:#E5C158;border-bottom-color:#2c4637}
        html[data-pa-theme="dark"] .pa-sinova-btn.is-filled{background:#21392b;border-color:#C9A227;color:#fff}
        html[data-pa-theme="dark"] .pa-sinova-btn.is-filled .pa-sinova-btn-meta{color:#E5C158}
        html[data-pa-theme="dark"] .pa-sinova-btn.is-empty{background:#122018;border-color:#355241;color:#cbd5e1}
        .pa-sinova-slot-wrap{position:relative;display:flex;flex-direction:column}
        .pa-sinova-slot-wrap .pa-sinova-btn{flex:1}
        .pa-doc-preview-trigger{position:absolute;top:7px;right:7px;min-height:28px;min-width:66px;padding:4px 10px;border-radius:999px;border:1px solid #056e4f;background:#ffffff;color:#056e4f;font-size:11px;font-weight:800;cursor:pointer;z-index:2;transition:background-color .15s ease,color .15s ease}
        .pa-doc-preview-trigger:hover{background:#056e4f;color:#E5C158;border-color:#C9A227}
        html[data-pa-theme="dark"] .pa-doc-preview-trigger{background:#022c22;color:#E5C158;border-color:#C9A227}
        .pa-preview-dialog{width:min(960px,95vw);max-width:95vw;padding:0;border:2px solid #C9A227;border-radius:16px;background:#ffffff;box-shadow:0 24px 60px rgba(0,0,0,.38);overflow:hidden}
        .pa-preview-dialog::backdrop{background:rgba(2,44,34,.72);backdrop-filter:blur(4px)}
        .pa-preview-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 18px;background:linear-gradient(135deg,#043927,#056e4f);border-bottom:3px solid #C9A227;color:#ffffff}
        .pa-preview-head strong{font-size:14px;font-weight:800;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .pa-preview-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}
        .pa-preview-ext{padding:7px 12px;border-radius:8px;background:#E5C158;color:#022c22!important;font-size:12px;font-weight:800;text-decoration:none!important}
        .pa-preview-close{min-height:32px;padding:5px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.4);background:rgba(255,255,255,.12);color:#ffffff;font-weight:800;cursor:pointer}
        .pa-preview-body{height:min(76vh,720px);background:#f1f5f9}
        .pa-preview-body iframe{width:100%;height:100%;border:0;display:block}
        html[data-pa-theme="dark"] .pa-preview-dialog{background:#13221a}
        html[data-pa-theme="dark"] .pa-preview-body{background:#0b141a}
    </style>
    <dialog id="pa-doc-preview-modal" class="pa-preview-dialog" aria-labelledby="pa-preview-modal-title">
        <div class="pa-preview-head">
            <strong id="pa-preview-modal-title">Pratinjau Dokumen</strong>
            <div class="pa-preview-actions">
                <a id="pa-preview-modal-ext" class="pa-preview-ext" href="#" target="_blank" rel="noopener noreferrer">Buka Tab Baru ↗</a>
                <button type="button" id="pa-preview-modal-close" class="pa-preview-close" aria-label="Tutup pratinjau">✕ Tutup</button>
            </div>
        </div>
        <div class="pa-preview-body"><iframe id="pa-preview-modal-frame" title="Pratinjau Dokumen PDF" src="about:blank" loading="lazy"></iframe></div>
    </dialog>
    <script id="pa-sinova-hub-js">
        (function(){
            document.querySelectorAll('.pa-sinova-archive-wrap').forEach(function(wrap){
                var btns = Array.prototype.slice.call(wrap.querySelectorAll('[data-pa-year-tab]'));
                var panes = wrap.querySelectorAll('[data-pa-year-pane]');
                function activateTab(btn){
                    var yr = btn.getAttribute('data-pa-year-tab');
                    btns.forEach(function(b){
                        var on = b === btn;
                        b.classList.toggle('is-active', on);
                        b.setAttribute('aria-selected', on ? 'true' : 'false');
                        b.setAttribute('tabindex', on ? '0' : '-1');
                    });
                    panes.forEach(function(p){
                        var on = p.getAttribute('data-pa-year-pane') === yr;
                        p.classList.toggle('is-active', on);
                        if(on) p.removeAttribute('hidden');
                        else p.setAttribute('hidden', 'hidden');
                    });
                }
                btns.forEach(function(btn, idx){
                    btn.addEventListener('click', function(){ activateTab(btn); });
                    btn.addEventListener('keydown', function(e){
                        var next = -1;
                        if(e.key === 'ArrowRight') next = (idx + 1) % btns.length;
                        else if(e.key === 'ArrowLeft') next = (idx - 1 + btns.length) % btns.length;
                        else if(e.key === 'Home') next = 0;
                        else if(e.key === 'End') next = btns.length - 1;
                        if(next !== -1){
                            e.preventDefault();
                            activateTab(btns[next]);
                            btns[next].focus();
                        }
                    });
                });
            });
            var dlg = document.getElementById('pa-doc-preview-modal');
            var frm = document.getElementById('pa-preview-modal-frame');
            var ttl = document.getElementById('pa-preview-modal-title');
            var ext = document.getElementById('pa-preview-modal-ext');
            var cls = document.getElementById('pa-preview-modal-close');
            if(dlg && frm){
                function closeModal(){
                    if(typeof dlg.close === 'function' && dlg.open) dlg.close();
                    else dlg.removeAttribute('open');
                    frm.src = 'about:blank';
                }
                document.querySelectorAll('.pa-doc-preview-trigger').forEach(function(btn){
                    btn.addEventListener('click', function(){
                        var pv = btn.getAttribute('data-preview-url') || '';
                        var op = btn.getAttribute('data-open-url') || pv;
                        if(!pv) return;
                        if(ttl) ttl.textContent = btn.getAttribute('data-preview-title') || 'Pratinjau Dokumen';
                        if(ext) ext.href = op;
                        frm.src = pv;
                        if(typeof dlg.showModal === 'function') dlg.showModal();
                        else dlg.setAttribute('open', 'open');
                    });
                });
                if(cls) cls.addEventListener('click', closeModal);
                dlg.addEventListener('close', function(){ frm.src = 'about:blank'; });
                dlg.addEventListener('click', function(e){ if(e.target === dlg) closeModal(); });
            }
        })();
    </script>
    <?php
    return (string) ob_get_clean();
});

function pa_sinova_update_survey_card_tag(string $slideHtml, array $docsByCode, string $periodLabel): string {
    $codes = array(
        'SKM'  => 'SKM (Indeks Kepuasan Masyarakat)',
        'SPAK' => 'SPAK (Survei Persepsi Anti Korupsi)',
        'SPKP' => 'SPKP (Survei Persepsi Kualitas Pelayanan)',
    );
    $offset = 0;
    while (preg_match('/<(a|div)\b([^>]*\bclass=(["\'])[^"\']*\bsurvey-card\b[^"\']*\3[^>]*)>/is', $slideHtml, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $fullOpen = $m[0][0];
        $tag      = strtolower($m[1][0]);
        $attrs    = $m[2][0];
        $startPos = (int) $m[0][1];
        $bodyPos  = $startPos + strlen($fullOpen);

        $endTagPos = false;
        if ($tag === 'a') {
            $close = stripos($slideHtml, '</a>', $bodyPos);
            if ($close !== false) $endTagPos = $close;
        } else {
            $depth = 1;
            $scan  = $bodyPos;
            while ($depth > 0 && preg_match('/<(\/?)div\b[^>]*>/i', $slideHtml, $dm, PREG_OFFSET_CAPTURE, $scan)) {
                if ($dm[1][0] === '/') {
                    $depth--;
                    if ($depth === 0) { $endTagPos = (int) $dm[0][1]; break; }
                } else {
                    $depth++;
                }
                $scan = (int) $dm[0][1] + strlen($dm[0][0]);
            }
        }
        if ($endTagPos === false) { $offset = $bodyPos; continue; }

        $closeLen    = strlen("</{$tag}>");
        $inner       = substr($slideHtml, $bodyPos, $endTagPos - $bodyPos);
        $matchedCode = preg_match('/<strong>\s*(SKM|SPAK|SPKP)\s*<\/strong>/i', $inner, $cm) ? strtoupper($cm[1]) : '';
        $docUrl      = ($matchedCode !== '' && isset($codes[$matchedCode]) && !empty($docsByCode[$codes[$matchedCode]])) ? (string) $docsByCode[$codes[$matchedCode]] : '';

        if ($docUrl !== '') {
            $safeUrl  = esc_url($docUrl);
            $newAttrs = preg_match('/\bhref=(["\'])[^"\']*\1/i', $attrs)
                ? preg_replace('/\bhref=(["\'])[^"\']*\1/i', 'href="' . $safeUrl . '"', $attrs)
                : $attrs . ' href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer"';
            if (!preg_match('/\btitle=/i', $newAttrs)) {
                $newAttrs .= ' title="' . esc_attr("Buka Laporan {$matchedCode} {$periodLabel}") . '"';
            }
            $replacement = '<a' . $newAttrs . '>' . $inner . '</a>';
            $slideHtml   = substr_replace($slideHtml, $replacement, $startPos, ($endTagPos + $closeLen) - $startPos);
            $offset      = $startPos + strlen($replacement);
        } else {
            $offset = $endTagPos + $closeLen;
        }
    }
    return $slideHtml;
}

add_filter('the_content', static function ($content) {
    if (!is_string($content) || strpos($content, 'survey-slider') === false || !is_front_page()) {
        return $content;
    }
    pa_sinova_ensure_table();
    global $wpdb;
    $table = pa_sinova_table_name();
    $rows  = $wpdb->get_results($wpdb->prepare(
        "SELECT year, sub_group, period_key, doc_url FROM `{$table}` WHERE page_slug = %s AND status = 'published' AND year = %d",
        'survei-pelayanan',
        (int) wp_date('Y')
    ), ARRAY_A);
    if (empty($rows)) return $content;

    $byPeriod = array();
    foreach ($rows as $r) {
        if (!empty($r['doc_url'])) {
            $byPeriod[strtoupper((string) $r['period_key'])][(string) $r['sub_group']] = (string) $r['doc_url'];
        }
    }
    $slideMap = array(
        '0' => array('key' => 'TW3', 'label' => 'Triwulan III'),
        '1' => array('key' => 'TW2', 'label' => 'Triwulan II'),
        '2' => array('key' => 'TW1', 'label' => 'Triwulan I'),
    );
    $updated = preg_replace_callback(
        '/<article\b[^>]*\bdata-slide=(["\'])(0|1|2)\1[^>]*>.*?<\/article>/is',
        static function ($m) use ($byPeriod, $slideMap) {
            $info = $slideMap[$m[2]] ?? null;
            return (!$info || empty($byPeriod[$info['key']])) ? $m[0] : pa_sinova_update_survey_card_tag($m[0], $byPeriod[$info['key']], $info['label']);
        },
        $content
    );
    return is_string($updated) ? $updated : $content;
}, 20);

add_shortcode('pa_sinova_zi_grid', static function () {
    $cfg      = function_exists('pa_sinova_get_settings') ? pa_sinova_get_settings() : array();
    $fallback = function_exists('pa_sinova_resolve_page_url') ? pa_sinova_resolve_page_url('zona-integritas') : home_url('/zona-integritas/');
    $areas    = array(
        1 => array('Area I', 'MANAJEMEN PERUBAHAN', '<path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>'),
        2 => array('Area II', 'PENATAAN TATA LAKSANA', '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'),
        3 => array('Area III', 'PENATAAN MANAJEMEN SDM', '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'),
        4 => array('Area IV', 'PENGUATAN AKUNTABILITAS', '<path d="M18 20V10M12 20V4M6 20v-6"/>'),
        5 => array('Area V', 'PENGUATAN PENGAWASAN', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'),
        6 => array('Area VI', 'KUALITAS PELAYANAN PUBLIK', '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'),
    );

    ob_start();
    ?>
    <section class="pa-section-block" aria-label="Dokumen Pembangunan Zona Integritas">
        <h2 class="pa-starter-heading">DOKUMEN PEMBANGUNAN ZONA INTEGRITAS <span class="pa-hint-i" aria-hidden="true">i</span></h2>
        <p class="pa-starter-sub">Transparansi 6 Area Perubahan Menuju Wilayah Bebas dari Korupsi (WBK) &amp; WBBM</p>
        <div class="pa-zi-grid">
            <?php foreach ($areas as $n => $meta) :
                $customUrl = trim((string) ($cfg['zi_area_' . $n . '_url'] ?? ''));
                $isExt     = ($customUrl !== '' && preg_match('#^https?://#i', $customUrl));
                $href      = $isExt ? $customUrl : ($fallback . '#area-' . $n);
                $extAttr   = $isExt ? ' target="_blank" rel="noopener noreferrer"' : '';
            ?>
                <a class="pa-zi-card" id="area-<?php echo (int) $n; ?>" href="<?php echo esc_url($href); ?>"<?php echo $extAttr; ?>>
                    <span class="pa-zi-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?php echo $meta[2]; ?></svg></span>
                    <span class="pa-zi-area"><?php echo esc_html($meta[0]); ?></span>
                    <span class="pa-zi-desc"><?php echo esc_html($meta[1]); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
});
