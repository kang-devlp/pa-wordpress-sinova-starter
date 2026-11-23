<?php
/**
 * Plugin Name: PA SINOVA Starter - Konfigurasi, Seeder 1-Klik SK 2722/2026, & Panel Kepatuhan WP Admin
 * Description: Pengaturan dinamis Satker, generator Secret Key HMAC-SHA256, Seeder 1-Klik halaman & Menu Utama SK Dirjen Badilag 2722/2026, konfigurasi Astra Free, Dashboard Kepatuhan 35 Kriteria Lampiran II + Salin URL, Upload Dokumen, & Audit Keamanan di WP Admin.
 * Version: 1.3.0-starter
 */

defined('ABSPATH') || exit;

function pa_sinova_get_settings(): array {
    $defaults = array(
        'satker_name'          => get_bloginfo('name') ?: 'Pengadilan Agama Contoh Kelas I-A',
        'logo_url'             => '',
        'address'              => 'Jl. Keadilan Raya No. 1, Kota Contoh',
        'phone'                => '(000) 123-4567 · WA PTSP 0811-0000-0000',
        'email'                => 'info@pa-contoh.go.id',
        'hearing_hours'        => 'Senin–Kamis 09.00 WIB s.d. selesai',
        'leader_name'          => 'Ketua Pengadilan Agama',
        'leader_welcome'       => 'Selamat datang di portal resmi Pengadilan Agama. Melalui website ini kami menyajikan informasi layanan peradilan, transparansi keuangan, laporan kinerja, dan dokumen pembangunan Zona Integritas secara terbuka kepada seluruh masyarakat pencari keadilan.',
        'sipp_url'             => 'https://sipp.pa-contoh.go.id/',
        'ecourt_url'           => 'https://ecourt.mahkamahagung.go.id/',
        'direktori_url'        => 'https://putusan3.mahkamahagung.go.id/',
        'survey_url'           => 'https://survei.badilag.net/',
        'hmac_secret'          => '',
        'turnstile_site_key'   => '',
        'turnstile_secret_key' => '',
    );
    $saved = get_option('pa_sinova_starter_settings', array());
    if (!is_array($saved)) {
        $saved = array();
    }
    $merged = array_merge($defaults, $saved);

    if (defined('PA_SINOVA_SATKER_NAME') && PA_SINOVA_SATKER_NAME !== '') {
        $merged['satker_name'] = (string) PA_SINOVA_SATKER_NAME;
    }
    if (defined('PA_SINOVA_HMAC_SECRET') && PA_SINOVA_HMAC_SECRET !== '') {
        $merged['hmac_secret'] = (string) PA_SINOVA_HMAC_SECRET;
    }
    if (defined('PA_TURNSTILE_SITE_KEY') && PA_TURNSTILE_SITE_KEY !== '') {
        $merged['turnstile_site_key'] = (string) PA_TURNSTILE_SITE_KEY;
    }
    if (defined('PA_TURNSTILE_SECRET_KEY') && PA_TURNSTILE_SECRET_KEY !== '') {
        $merged['turnstile_secret_key'] = (string) PA_TURNSTILE_SECRET_KEY;
    }

    return $merged;
}

function pa_sinova_satker_name(): string {
    $cfg = pa_sinova_get_settings();
    return trim((string) ($cfg['satker_name'] ?? 'Pengadilan Agama')) ?: 'Pengadilan Agama';
}

function pa_sinova_get_secret_key(): string {
    $cfg = pa_sinova_get_settings();
    return trim((string) ($cfg['hmac_secret'] ?? ''));
}

/**
 * Daftar halaman non-modul (Informasi Wajib & Halaman Informasi Berkala statis) untuk Seeder 1-Klik.
 */
function pa_sinova_static_seed_pages(): array {
    return array(
        'visi-dan-misi' => array(
            'title'   => 'Visi dan Misi',
            'parent'  => 'Profil Pengadilan',
            'code'    => 'W1.1',
            'content' => '<h2>Visi Pengadilan</h2><p>[LENGKAPI: Masukkan Visi resmi Mahkamah Agung RI / Satker]</p><h2>Misi Pengadilan</h2><ol><li>[LENGKAPI: Misi 1]</li><li>[LENGKAPI: Misi 2]</li><li>[LENGKAPI: Misi 3]</li></ol>',
        ),
        'tugas-pokok-dan-fungsi' => array(
            'title'   => 'Tugas Pokok dan Fungsi',
            'parent'  => 'Profil Pengadilan',
            'code'    => 'W1.2',
            'content' => '<h2>Tugas Pokok</h2><p>[LENGKAPI: Berdasarkan UU Peradilan Agama, tugas pokok Pengadilan Agama adalah menerima, memeriksa, memutus, dan menyelesaikan perkara tertentu bagi rakyat pencari keadilan yang beragama Islam.]</p><h2>Fungsi Pengadilan</h2><p>[LENGKAPI daftar fungsi peradilan, administrasi, dan pengawasan.]</p>',
        ),
        'wilayah-yurisdiksi' => array(
            'title'   => 'Wilayah Yurisdiksi',
            'parent'  => 'Profil Pengadilan',
            'code'    => 'W1.3',
            'content' => '<h2>Peta &amp; Cakupan Wilayah Yurisdiksi</h2><p>[LENGKAPI: Daftar kecamatan/kelurahan wilayah hukum satker dan sematan Google Maps kantor.]</p>',
        ),
        'struktur-organisasi' => array(
            'title'   => 'Struktur Organisasi',
            'parent'  => 'Profil Pengadilan',
            'code'    => 'W1.4',
            'content' => '<h2>Bagan Struktur Organisasi</h2><p>[LENGKAPI: Unggah bagan struktur organisasi resmi lengkap dengan nama pejabat &amp; jabatan.]</p>',
        ),
        'profil-pegawai' => array(
            'title'   => 'Profil Pejabat dan Pegawai',
            'parent'  => 'Profil Pengadilan',
            'code'    => 'W3.1',
            'content' => '<h2>Daftar Pejabat Struktural, Fungsional, Hakim &amp; Pegawai</h2><p>[LENGKAPI: Tabel/kartu profil pimpinan, hakim, panitera, sekretaris, dan ASN.]</p>',
        ),
        'alamat-dan-kontak' => array(
            'title'   => 'Alamat dan Kontak Resmi',
            'parent'  => 'Profil Pengadilan',
            'code'    => 'W1.5',
            'content' => '<h2>Identitas &amp; Alamat Kantor</h2><p>[LENGKAPI: Jalan, Kelurahan, Kecamatan, Kota/Kabupaten, Kode Pos, Telepon, Email resmi, &amp; Foto Gedung tampak depan.]</p>',
        ),
        'e-court' => array(
            'title'   => 'Informasi e-Court',
            'parent'  => 'Kepaniteraan & Perkara',
            'code'    => 'W2.1',
            'content' => '<h2>Pendaftaran Perkara Elektronik (e-Court)</h2><p>[LENGKAPI panduan pendaftaran e-Filing, e-Payment, e-Summons, &amp; e-Litigation beserta tautan ke ecourt.mahkamahagung.go.id.]</p>',
        ),
        'direktori-putusan' => array(
            'title'   => 'Direktori Putusan Satker',
            'parent'  => 'Kepaniteraan & Perkara',
            'code'    => 'W2.2',
            'content' => '<h2>Direktori Putusan Mahkamah Agung RI</h2><p>[LENGKAPI tautan langsung halaman Direktori Putusan satker pada domain putusan3.mahkamahagung.go.id.]</p>',
        ),
        'prosedur-berperkara' => array(
            'title'   => 'Prosedur Berperkara',
            'parent'  => 'Kepaniteraan & Perkara',
            'code'    => 'W2.3',
            'content' => '<h2>Alur &amp; Prosedur Berperkara (Tingkat Pertama, Banding, Kasasi, PK)</h2><p>[LENGKAPI syarat dan alur 6 jenis prosedur berperkara.]</p>',
        ),
        'biaya-proses-berperkara' => array(
            'title'   => 'Biaya Proses Berperkara & Radius',
            'parent'  => 'Kepaniteraan & Perkara',
            'code'    => 'W2.4',
            'content' => '<h2>SK Panjar Biaya Perkara &amp; Radius Panggilan</h2><p>[LENGKAPI tabel SK Ketua tentang Panjar Biaya Perkara &amp; Radius Panggilan Jurusita.]</p>',
        ),
        'agenda-jadwal-persidangan' => array(
            'title'   => 'Agenda dan Jadwal Persidangan',
            'parent'  => 'Kepaniteraan & Perkara',
            'code'    => 'B2.2',
            'content' => '<h2>Jadwal Sidang Harian (SIPP)</h2><p>[LENGKAPI tautan atau sematan jadwal persidangan dari SIPP.]</p>',
        ),
        'statistik-perkara' => array(
            'title'   => 'Statistik Perkara',
            'parent'  => 'Kepaniteraan & Perkara',
            'code'    => 'B2.3',
            'content' => '<h2>Laporan Statistik Perkara Bulanan</h2><p>[LENGKAPI tabel/grafik perkara diterima, diputus, sisa per bulan.]</p>',
        ),
        'panggilan-ghaib' => array(
            'title'   => 'Daftar Panggilan Ghaib',
            'parent'  => 'Kepaniteraan & Perkara',
            'code'    => 'B2.4',
            'content' => '<h2>Pengumuman Panggilan Ghaib (Pasal 27 PP 9/1975)</h2><p>[LENGKAPI daftar atau kategori pengumuman panggilan pihak yang tidak diketahui tempat tinggalnya.]</p>',
        ),
        'laporan-tahunan' => array(
            'title'   => 'Laporan Tahunan & Program Kerja',
            'parent'  => 'Perencanaan & SAKIP',
            'code'    => 'B1.2',
            'content' => '<h2>Arsip Laporan Tahunan &amp; Program Kerja</h2><p>[LENGKAPI tautan dokumen Laporan Tahunan 5 tahun terakhir.]</p>',
        ),
        'standar-maklumat-pelayanan' => array(
            'title'   => 'Standar dan Maklumat Pelayanan',
            'parent'  => 'Layanan Publik & PPID',
            'code'    => 'W4.1',
            'content' => '<h2>Standar &amp; Maklumat Pelayanan Publik</h2><p>[LENGKAPI SK Standar Pelayanan, Maklumat Pelayanan PTSP, SOP PPID, dan Layanan Kelompok Rentan/Disabilitas.]</p>',
        ),
        'hukuman-disiplin' => array(
            'title'   => 'Hukuman Disiplin Hasil Pengawasan',
            'parent'  => 'Layanan Publik & PPID',
            'code'    => 'B4.3',
            'content' => '<h2>Publikasi Hasil Pengawasan &amp; Hukuman Disiplin</h2><p>[LENGKAPI rekapitulasi hasil pengawasan atau keterangan Nihil pada periode berjalan.]</p>',
        ),
        'statistik-kunjungan' => array(
            'title'   => 'Statistik Kunjungan Website',
            'parent'  => 'Publikasi',
            'code'    => 'B5.1',
            'content' => '<h2>Statistik Pengunjung Website Resmi</h2>[pa_visitor_stats]',
        ),
    );
}

/**
 * Definisi & Evaluasi Real-Time 35 Kriteria Lampiran II (SK Dirjen Badilag 2722/2026) langsung di WordPress.
 */
function pa_sinova_wp_compliance_report(): array {
    if (function_exists('pa_sinova_ensure_table')) {
        pa_sinova_ensure_table();
    }
    global $wpdb;
    $cfg   = pa_sinova_get_settings();
    $table = function_exists('pa_sinova_table_name') ? pa_sinova_table_name() : ($wpdb->prefix . 'pa_sinova_documents');
    $rows  = $wpdb->get_results("SELECT page_slug, year, period_key FROM `{$table}` WHERE status = 'published'", ARRAY_A);

    $docStats = array();
    foreach ((array) $rows as $r) {
        $s = (string) $r['page_slug'];
        $y = (int) $r['year'];
        $docStats[$s]['total']      = ($docStats[$s]['total'] ?? 0) + 1;
        $docStats[$s]['years'][$y]  = ($docStats[$s]['years'][$y] ?? 0) + 1;
    }

    $curY  = (int) date('Y');
    $specs = array(
        // A.1 INFORMASI BERKALA (24 kriteria)
        array('B1.1', 'berkala', 'Rencana Aksi Kinerja (5 tahun)', 'rencana-aksi', 'mod:sakip_5y'),
        array('B1.2', 'berkala', 'Laporan Tahunan / Pelaksanaan Kegiatan', 'laporan-tahunan', 'page'),
        array('B2.1', 'berkala', 'Tautan & Integrasi SIPP Satker', 'agenda-jadwal-persidangan', 'setting:sipp_url'),
        array('B2.2', 'berkala', 'Agenda / Jadwal Persidangan Harian', 'agenda-jadwal-persidangan', 'page'),
        array('B2.3', 'berkala', 'Statistik Perkara per Bulan', 'statistik-perkara', 'page'),
        array('B2.4', 'berkala', 'Daftar Panggilan Ghaib', 'panggilan-ghaib', 'page'),
        array('B2.5', 'berkala', 'Laporan Penggunaan Biaya Perkara (LIPA 7)', 'biaya-perkara', 'mod:monthly'),
        array('B2.6', 'berkala', 'Laporan Pengembalian Sisa Panjar', 'sisa-panjar', 'mod:monthly'),
        array('B3.1', 'berkala', 'LHKPN Pimpinan & Pejabat Wajib Lapor', 'lhkpn', 'mod:annual'),
        array('B3.2', 'berkala', 'DIPA, Rencana Penggunaan & Realisasi', 'dipa', 'mod:annual'),
        array('B3.3', 'berkala', 'Laporan Realisasi Anggaran (LRA)', 'lra', 'mod:monthly'),
        array('B3.4', 'berkala', 'Catatan Atas Laporan Keuangan (CALK)', 'calk', 'mod:annual'),
        array('B3.5', 'berkala', 'Pengadaan Barang & Jasa (RUP / PBJ)', 'pbj', 'mod:annual'),
        array('B3.6', 'berkala', 'Rencana Strategis (Renstra) 5 Tahun', 'renstra', 'mod:sakip_5y'),
        array('B3.7', 'berkala', 'Rencana Kinerja Tahunan (RKT) 5 Tahun', 'rkt', 'mod:sakip_5y'),
        array('B3.8', 'berkala', 'Rencana Aksi Kinerja Tahun Berjalan', 'rencana-aksi', 'mod:annual'),
        array('B3.9', 'berkala', 'Perjanjian Kinerja (PK) 5 Tahun', 'pk', 'mod:sakip_5y'),
        array('B3.10', 'berkala', 'IKU dan Reviu IKU (5 Tahun)', 'iku', 'mod:sakip_5y'),
        array('B3.11', 'berkala', 'Laporan Akuntabilitas Kinerja (LKJiP) 5 Tahun', 'lkjip', 'mod:sakip_5y'),
        array('B4.1', 'berkala', 'Laporan Akses Informasi Publik (PPID)', 'akses-informasi', 'mod:monthly'),
        array('B4.2', 'berkala', 'Laporan Rekapitulasi Pengaduan Masyarakat', 'rekap-pengaduan', 'mod:monthly'),
        array('B4.3', 'berkala', 'Hukuman Disiplin Hasil Pengawasan', 'hukuman-disiplin', 'page'),
        array('B4.4', 'berkala', 'Nilai & Laporan Survei (SKM, SPAK, SPKP)', 'survei-pelayanan', 'mod:quarterly'),
        array('B5.1', 'berkala', 'Statistik Kunjungan Website', 'statistik-kunjungan', 'page'),
        // A.2 INFORMASI WAJIB (11 kriteria)
        array('W1.1', 'wajib', 'Visi dan Misi Pengadilan', 'visi-dan-misi', 'page'),
        array('W1.2', 'wajib', 'Tugas Pokok dan Fungsi Pengadilan', 'tugas-pokok-dan-fungsi', 'page'),
        array('W1.3', 'wajib', 'Wilayah Yurisdiksi & Peta Wilayah', 'wilayah-yurisdiksi', 'page'),
        array('W1.4', 'wajib', 'Struktur Organisasi (Bagan, Nama & Jabatan)', 'struktur-organisasi', 'page'),
        array('W1.5', 'wajib', 'Alamat Lengkap, Telepon, Email & Foto Gedung', 'alamat-dan-kontak', 'page'),
        array('W2.1', 'wajib', 'Informasi & Tautan e-Court', 'e-court', 'page'),
        array('W2.2', 'wajib', 'Tautan Direktori Putusan Satker', 'direktori-putusan', 'page'),
        array('W2.3', 'wajib', 'Prosedur Berperkara', 'prosedur-berperkara', 'page'),
        array('W2.4', 'wajib', 'Rincian Biaya Proses Berperkara & Radius', 'biaya-proses-berperkara', 'page'),
        array('W3.1', 'wajib', 'Profil Pejabat, Pegawai, & Pegawai Non-ASN', 'profil-pegawai', 'page'),
        array('W4.1', 'wajib', 'Standar dan Maklumat Pelayanan', 'standar-maklumat-pelayanan', 'page'),
    );

    $items   = array();
    $counts  = array('lengkap' => 0, 'perhatian' => 0, 'kosong' => 0);
    $bPts    = 0;
    $wPts    = 0;

    foreach ($specs as $sp) {
        list($code, $group, $label, $slug, $mode) = $sp;
        $pageObj = get_page_by_path($slug);
        $url     = $pageObj instanceof WP_Post ? get_permalink($pageObj->ID) : pa_sinova_resolve_page_url($slug);
        $editUrl = $pageObj instanceof WP_Post ? admin_url('post.php?post=' . $pageObj->ID . '&action=edit') : admin_url('admin.php?page=pa-sinova-starter&tab=upload');

        $status = 'kosong';
        $pts    = 0;
        $note   = 'Belum ada halaman atau dokumen.';

        if (strpos($mode, 'mod:') === 0) {
            $st      = $docStats[$slug] ?? array('total' => 0, 'years' => array());
            $tot     = (int) ($st['total'] ?? 0);
            $yrCount = count((array) ($st['years'] ?? array()));
            $hasCur  = !empty($st['years'][$curY]);
            $editUrl = admin_url('admin.php?page=pa-sinova-starter&tab=upload&doc_slug=' . rawurlencode($slug));

            if ($tot === 0) {
                $status = 'kosong';
                $pts    = 0;
                $note   = 'Belum ada dokumen terunggah di modul.';
            } elseif ($mode === 'mod:sakip_5y') {
                if ($yrCount >= 4) {
                    $status = 'lengkap';
                    $pts    = 10;
                    $note   = "{$tot} dokumen ({$yrCount} tahun tersedia).";
                } else {
                    $status = 'perhatian';
                    $pts    = 5;
                    $note   = "Baru {$yrCount} tahun terisi (anjuran 5 tahun).";
                }
            } elseif ($mode === 'mod:monthly' || $mode === 'mod:quarterly') {
                $curDocs = (int) ($st['years'][$curY] ?? 0);
                if ($curDocs >= 3) {
                    $status = 'lengkap';
                    $pts    = 10;
                    $note   = "{$curDocs} dokumen periode {$curY} (total {$tot}).";
                } else {
                    $status = 'perhatian';
                    $pts    = 5;
                    $note   = "Baru {$curDocs} dokumen pada tahun {$curY} (total {$tot}).";
                }
            } else {
                if ($hasCur) {
                    $status = 'lengkap';
                    $pts    = 10;
                    $note   = "{$tot} dokumen (tahun {$curY} terpublikasi).";
                } else {
                    $status = 'perhatian';
                    $pts    = 5;
                    $note   = "Ada {$tot} dokumen arsip, namun tahun {$curY} belum diunggah.";
                }
            }
        } elseif (strpos($mode, 'setting:') === 0) {
            $sk  = substr($mode, 8);
            $val = trim((string) ($cfg[$sk] ?? ''));
            if ($val !== '' && stripos($val, 'pa-contoh') === false) {
                $status = 'lengkap';
                $pts    = 10;
                $note   = 'Tautan aktif pada pengaturan Satker.';
            } elseif ($val !== '') {
                $status = 'perhatian';
                $pts    = 5;
                $note   = 'Masih memakai URL contoh; perbarui di Pengaturan Satker.';
            }
            $editUrl = admin_url('admin.php?page=pa-sinova-starter&tab=settings');
        } else {
            if ($pageObj instanceof WP_Post && $pageObj->post_status === 'publish') {
                $rawContent = (string) $pageObj->post_content;
                if (stripos($rawContent, '[LENGKAPI') !== false) {
                    $status = 'perhatian';
                    $pts    = 5;
                    $note   = 'Halaman sudah ada, masih berisi penanda [LENGKAPI].';
                } elseif (strlen(trim(wp_strip_all_tags($rawContent))) >= 20 || stripos($rawContent, '[pa_') !== false) {
                    $status = 'lengkap';
                    $pts    = 10;
                    $note   = 'Halaman resmi terpublikasi (HTTP 200).';
                } else {
                    $status = 'perhatian';
                    $pts    = 5;
                    $note   = 'Konten halaman masih terlalu singkat.';
                }
            }
        }

        $counts[$status]++;
        if ($group === 'berkala') $bPts += $pts;
        else $wPts += $pts;

        $items[] = array(
            'code'     => $code,
            'group'    => $group,
            'label'    => $label,
            'slug'     => $slug,
            'status'   => $status,
            'points'   => $pts,
            'note'     => $note,
            'url'      => (string) $url,
            'edit_url' => $editUrl,
        );
    }

    $bPct = round(($bPts / 240) * 100, 1);
    $wPct = round(($wPts / 110) * 100, 1);
    $tPct = round((($bPts + $wPts) / 350) * 100, 1);

    return array(
        'items'   => $items,
        'counts'  => $counts,
        'berkala' => $bPct,
        'wajib'   => $wPct,
        'total'   => $tPct,
    );
}

/**
 * Konfigurasi Astra Free secara otomatis (full-width tanpa sidebar mengganggu).
 */
function pa_sinova_apply_astra_defaults(int $frontPageId = 0): void {
    $astraSettings = get_option('astra-settings', array());
    if (!is_array($astraSettings)) {
        $astraSettings = array();
    }
    $defaults = array(
        'site-content-width'         => 1200,
        'site-sidebar-layout'        => 'no-sidebar',
        'single-page-sidebar-layout' => 'no-sidebar',
        'single-page-content-layout' => 'plain-container',
    );
    update_option('astra-settings', array_merge($astraSettings, $defaults), false);

    if ($frontPageId > 0) {
        update_post_meta($frontPageId, 'site-post-title', 'disabled');
        update_post_meta($frontPageId, 'site-sidebar-layout', 'no-sidebar');
        update_post_meta($frontPageId, 'site-content-layout', 'plain-container');
        update_post_meta($frontPageId, 'ast-breadcrumbs-content', 'disabled');
    }
}

/**
 * Seeder 1-Klik: membuat 23 halaman modul + 17 halaman wajib/statis + Beranda PA + Berita contoh + Menu Utama.
 */
function pa_sinova_run_starter_seeder(): array {
    $seeded = get_option('pa_sinova_seeded_pages', array());
    if (!is_array($seeded)) {
        $seeded = array();
    }

    $createdCount = 0;
    $menuItemsPlan = array(
        'Profil Pengadilan'         => array(),
        'Kepaniteraan & Perkara'     => array(),
        'Kesekretariatan & Anggaran' => array(),
        'Perencanaan & SAKIP'        => array(),
        'Layanan Publik & PPID'      => array(),
        'Publikasi'                  => array(),
    );

    $catToParent = array(
        'Keuangan & Anggaran'              => 'Kesekretariatan & Anggaran',
        'Kepaniteraan & Biaya Perkara'     => 'Kepaniteraan & Perkara',
        'Perencanaan & SAKIP'              => 'Perencanaan & SAKIP',
        'Layanan Publik, PPID & Kepatuhan' => 'Layanan Publik & PPID',
    );

    $upsertPage = static function (string $key, string $title, string $slug, string $content) use (&$seeded, &$createdCount): int {
        $existingId = isset($seeded[$key]) ? (int) $seeded[$key] : 0;
        if ($existingId > 0 && get_post_status($existingId)) {
            return $existingId;
        }
        $byPath = get_page_by_path($slug);
        if ($byPath instanceof WP_Post) {
            $seeded[$key] = (int) $byPath->ID;
            return (int) $byPath->ID;
        }
        $newId = wp_insert_post(array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $content,
        ));
        if (!is_wp_error($newId) && $newId > 0) {
            $seeded[$key] = (int) $newId;
            $createdCount++;
            return (int) $newId;
        }
        return 0;
    };

    foreach (pa_sinova_static_seed_pages() as $slug => $meta) {
        $pid = $upsertPage('static:' . $slug, $meta['title'], $slug, $meta['content']);
        if ($pid > 0) {
            $parentName = $meta['parent'];
            $menuItemsPlan[$parentName][] = array('title' => $meta['title'], 'post_id' => $pid);
        }
    }

    if (function_exists('pa_sinova_module_catalog')) {
        foreach (pa_sinova_module_catalog() as $slug => $cfg) {
            $shortcode = '[pa_sinova_docs slug="' . esc_attr($slug) . '"]';
            $pid = $upsertPage('mod:' . $slug, $cfg['title'], $slug, $shortcode);
            if ($pid > 0) {
                $parentName = ($slug === 'mou') ? 'Publikasi' : ($catToParent[$cfg['category']] ?? 'Layanan Publik & PPID');
                $menuItemsPlan[$parentName][] = array('title' => $cfg['title'], 'post_id' => $pid);
            }
        }
    }

    $menuName = 'Menu Utama SINOVA';
    $menuObj  = wp_get_nav_menu_object($menuName);
    $menuId   = $menuObj ? (int) $menuObj->term_id : (int) wp_create_nav_menu($menuName);
    $menuCreated = 0;

    if ($menuId > 0) {
        $existingItems = wp_get_nav_menu_items($menuId);
        if (empty($existingItems)) {
            $pos = 1;
            wp_update_nav_menu_item($menuId, 0, array(
                'menu-item-title'    => 'Beranda',
                'menu-item-url'      => home_url('/'),
                'menu-item-type'     => 'custom',
                'menu-item-status'   => 'publish',
                'menu-item-position' => $pos++,
            ));
            foreach ($menuItemsPlan as $parentTitle => $children) {
                $parentItemId = wp_update_nav_menu_item($menuId, 0, array(
                    'menu-item-title'    => $parentTitle,
                    'menu-item-url'      => '#top-' . sanitize_title($parentTitle),
                    'menu-item-type'     => 'custom',
                    'menu-item-status'   => 'publish',
                    'menu-item-position' => $pos++,
                ));
                if (!is_wp_error($parentItemId) && $parentItemId > 0) {
                    $menuCreated++;
                    foreach ($children as $ch) {
                        wp_update_nav_menu_item($menuId, 0, array(
                            'menu-item-object-id' => (int) $ch['post_id'],
                            'menu-item-object'    => 'page',
                            'menu-item-type'      => 'post_type',
                            'menu-item-status'    => 'publish',
                            'menu-item-parent-id' => (int) $parentItemId,
                            'menu-item-position'  => $pos++,
                        ));
                        $menuCreated++;
                    }
                }
            }
        }
        $locations = get_theme_mod('nav_menu_locations', array());
        if (!is_array($locations)) {
            $locations = array();
        }
        foreach (array('primary', 'secondary_menu', 'mobile_menu') as $locKey) {
            $locations[$locKey] = $menuId;
        }
        set_theme_mod('nav_menu_locations', $locations);
    }

    $frontSlug = 'beranda';
    $tplPaths  = array(
        ABSPATH . 'sinova-starter-kit/wordpress-templates/beranda-blocks-starter.html',
        dirname(__DIR__) . '/wordpress-templates/beranda-blocks-starter.html',
    );
    $frontHtml = '';
    foreach ($tplPaths as $tp) {
        if (is_file($tp)) {
            $frontHtml = (string) file_get_contents($tp);
            break;
        }
    }
    if ($frontHtml === '') {
        $frontHtml = "<!-- wp:html -->\n<div class=\"pa-starter-showcase\">\n  <section class=\"pa-hero-banner\"><div class=\"pa-hero-content\"><span class=\"pa-hero-kicker\">Zona Integritas WBK / WBBM · Keterbukaan Informasi Publik</span><h2 class=\"pa-hero-title\">Portal Keterbukaan Informasi &amp; Layanan Peradilan</h2><p class=\"pa-hero-lead\">Menyediakan akses informasi perkara, jadwal sidang, panjar biaya, laporan kinerja, serta arsip transparansi keuangan.</p><div class=\"pa-hero-actions\"><a class=\"pa-btn-gold\" href=\"./agenda-jadwal-persidangan/\">Jadwal Sidang &amp; SIPP</a><a class=\"pa-btn-outline\" href=\"./e-court/\">Layanan e-Court</a><a class=\"pa-btn-outline\" href=\"./survei-pelayanan/\">Survei Pelayanan</a></div></div></section>\n  [pa_sinova_welcome_section]\n  [pa_sinova_latest_posts count=\"3\"]\n</div>\n<!-- /wp:html -->";
    }
    $frontId = !empty($seeded['static:beranda']) ? (int) $seeded['static:beranda'] : 0;
    if ($frontId <= 0 || !get_post_status($frontId)) {
        $existingFront = get_page_by_path($frontSlug);
        $wasKses = has_filter('content_save_pre', 'wp_filter_post_kses');
        if ($wasKses && function_exists('kses_remove_filters')) {
            kses_remove_filters();
        }
        if ($existingFront instanceof WP_Post) {
            $frontId = (int) $existingFront->ID;
        } else {
            $frontId = (int) wp_insert_post(array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => 'Beranda',
                'post_name'    => $frontSlug,
                'post_content' => $frontHtml,
            ));
            if ($frontId > 0) {
                $createdCount++;
            }
        }
        if ($wasKses && function_exists('kses_init_filters')) {
            kses_init_filters();
        }
        if ($frontId > 0) {
            $seeded['static:beranda'] = $frontId;
        }
    }

    $postCounts = wp_count_posts('post');
    $pubPosts   = isset($postCounts->publish) ? (int) $postCounts->publish : 0;
    if ($pubPosts <= 1) {
        $samplePosts = array(
            array(
                'slug'    => 'peningkatan-kualitas-layanan-ptsp-pengadilan-agama',
                'title'   => 'Peningkatan Kualitas Layanan PTSP dan Keterbukaan Informasi Publik',
                'content' => '<p>Pengadilan Agama terus meningkatkan standar pelayanan terpadu satu pintu (PTSP) guna memberikan pelayanan yang cepat, transparan, dan bebas biaya di luar ketentuan resmi kepada masyarakat pencari keadilan.</p>',
            ),
            array(
                'slug'    => 'publikasi-laporan-realisasi-anggaran-dan-kinerja-sakip',
                'title'   => 'Publikasi Berkala Laporan Realisasi Anggaran dan Kinerja SAKIP',
                'content' => '<p>Seluruh dokumen laporan realisasi anggaran DIPA 01 dan DIPA 04 serta capaian indikator kinerja utama dapat diakses langsung oleh publik melalui menu Kesekretariatan dan SAKIP sesuai pedoman transparansi Badilag.</p>',
            ),
            array(
                'slug'    => 'komitmen-bersama-pembangunan-zona-integritas-wbk-wbbm',
                'title'   => 'Komitmen Bersama Pembangunan Zona Integritas Menuju WBK dan WBBM',
                'content' => '<p>Pimpinan dan seluruh aparatur pengadilan menyatakan komitmen bersama menjaga integritas, menolak segala bentuk gratifikasi, serta mendukung penuh pengawasan publik melalui layanan pengaduan terpadu.</p>',
            ),
        );
        foreach ($samplePosts as $sp) {
            if (!get_page_by_path($sp['slug'], OBJECT, 'post')) {
                wp_insert_post(array(
                    'post_type'    => 'post',
                    'post_status'  => 'publish',
                    'post_title'   => $sp['title'],
                    'post_name'    => $sp['slug'],
                    'post_content' => $sp['content'],
                ));
            }
        }
    }

    update_option('pa_sinova_seeded_pages', $seeded, false);

    if ($frontId > 0) {
        if (get_option('show_on_front') !== 'page') {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $frontId);
        }
        pa_sinova_apply_astra_defaults($frontId);
    }

    return array('pages_created' => $createdCount, 'total_mapped' => count($seeded), 'menu_items' => $menuCreated);
}

/**
 * Resolve permalink untuk halaman modul maupun statis.
 */
function pa_sinova_resolve_page_url(string $slug, string $fallbackPath = ''): string {
    $seeded = get_option('pa_sinova_seeded_pages', array());
    if (is_array($seeded)) {
        if (!empty($seeded['mod:' . $slug])) {
            $u = get_permalink((int) $seeded['mod:' . $slug]);
            if ($u) return $u;
        }
        if (!empty($seeded['static:' . $slug])) {
            $u = get_permalink((int) $seeded['static:' . $slug]);
            if ($u) return $u;
        }
    }
    $page = get_page_by_path($slug);
    if ($page instanceof WP_Post) {
        $u = get_permalink($page->ID);
        if ($u) return $u;
    }
    return home_url('/' . ltrim($fallbackPath ?: $slug . '/', '/'));
}

add_action('admin_menu', static function () {
    add_menu_page(
        'SINOVA Hub (Kepatuhan SK 2722/2026)',
        'SINOVA Hub',
        'manage_options',
        'pa-sinova-starter',
        'pa_sinova_render_admin_page',
        'dashicons-awards',
        3
    );
});

function pa_sinova_render_admin_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }

    $notice = '';
    $activeTab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'compliance';
    if (!in_array($activeTab, array('compliance', 'settings', 'upload', 'security'), true)) {
        $activeTab = 'compliance';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pa_sinova_action'])) {
        check_admin_referer('pa_sinova_starter_nonce');
        $action = sanitize_key((string) $_POST['pa_sinova_action']);

        if ($action === 'save_settings') {
            $activeTab = 'settings';
            $cur = pa_sinova_get_settings();
            $new = array(
                'satker_name'          => sanitize_text_field(wp_unslash((string) ($_POST['satker_name'] ?? $cur['satker_name']))),
                'logo_url'             => esc_url_raw(wp_unslash((string) ($_POST['logo_url'] ?? ''))),
                'address'              => sanitize_text_field(wp_unslash((string) ($_POST['address'] ?? $cur['address']))),
                'phone'                => sanitize_text_field(wp_unslash((string) ($_POST['phone'] ?? $cur['phone']))),
                'email'                => sanitize_email(wp_unslash((string) ($_POST['email'] ?? $cur['email']))),
                'hearing_hours'        => sanitize_text_field(wp_unslash((string) ($_POST['hearing_hours'] ?? $cur['hearing_hours']))),
                'leader_name'          => sanitize_text_field(wp_unslash((string) ($_POST['leader_name'] ?? $cur['leader_name']))),
                'leader_welcome'       => sanitize_textarea_field(wp_unslash((string) ($_POST['leader_welcome'] ?? $cur['leader_welcome']))),
                'sipp_url'             => esc_url_raw(wp_unslash((string) ($_POST['sipp_url'] ?? ''))),
                'ecourt_url'           => esc_url_raw(wp_unslash((string) ($_POST['ecourt_url'] ?? ''))),
                'direktori_url'        => esc_url_raw(wp_unslash((string) ($_POST['direktori_url'] ?? ''))),
                'survey_url'           => esc_url_raw(wp_unslash((string) ($_POST['survey_url'] ?? ''))),
                'hmac_secret'          => trim(sanitize_text_field(wp_unslash((string) ($_POST['hmac_secret'] ?? '')))),
                'turnstile_site_key'   => trim(sanitize_text_field(wp_unslash((string) ($_POST['turnstile_site_key'] ?? '')))),
                'turnstile_secret_key' => trim(sanitize_text_field(wp_unslash((string) ($_POST['turnstile_secret_key'] ?? '')))),
            );
            if (!empty($_POST['generate_hmac'])) {
                $new['hmac_secret'] = bin2hex(random_bytes(32));
            }
            update_option('pa_sinova_starter_settings', $new, false);
            if (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }
            $notice = 'Pengaturan Satker & API HMAC berhasil disimpan.';
        } elseif ($action === 'run_seeder') {
            $activeTab = 'settings';
            $res = pa_sinova_run_starter_seeder();
            if (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }
            $notice = sprintf(
                'Seeder selesai: %d halaman baru dibuat (%d total halaman terpetakan), %d item menu ditambahkan.',
                $res['pages_created'],
                $res['total_mapped'],
                $res['menu_items']
            );
        } elseif ($action === 'upload_doc' && function_exists('pa_sinova_ensure_table')) {
            $activeTab = 'upload';
            pa_sinova_ensure_table();
            global $wpdb;
            $table   = pa_sinova_table_name();
            $periods = pa_sinova_all_periods_map();
            $slug    = sanitize_key((string) ($_POST['doc_slug'] ?? 'lra'));
            $year    = max(2017, min(2035, (int) ($_POST['doc_year'] ?? date('Y'))));
            $sub     = sanitize_text_field(wp_unslash((string) ($_POST['doc_subgroup'] ?? 'DOKUMEN')));
            $per     = strtoupper(sanitize_key((string) ($_POST['doc_period'] ?? 'JAN')));
            $title   = sanitize_text_field(wp_unslash((string) ($_POST['doc_title'] ?? '')));
            $extUrl  = esc_url_raw(wp_unslash((string) ($_POST['doc_url'] ?? '')));
            $srcType = 'external';
            $fileKb  = null;

            if (!empty($_FILES['doc_pdf']['tmp_name']) && is_uploaded_file($_FILES['doc_pdf']['tmp_name'])) {
                $raw = file_get_contents($_FILES['doc_pdf']['tmp_name']);
                if (is_string($raw) && strlen($raw) <= 10 * 1024 * 1024 && substr($raw, 0, 5) === '%PDF-') {
                    $destDir = WP_CONTENT_DIR . "/uploads/sinova-docs/{$year}/{$slug}";
                    wp_mkdir_p($destDir);
                    $fname = "{$slug}-{$year}-" . strtolower($per) . '-' . substr(hash('sha256', $raw), 0, 8) . '.pdf';
                    if (file_put_contents("{$destDir}/{$fname}", $raw, LOCK_EX) !== false) {
                        $extUrl  = content_url("uploads/sinova-docs/{$year}/{$slug}/{$fname}");
                        $srcType = 'wp_pdf';
                        $fileKb  = (int) ceil(strlen($raw) / 1024);
                    }
                } else {
                    $notice = 'Gagal: Berkas unggahan harus PDF asli maksimal 10 MB.';
                }
            }

            if ($extUrl !== '' && (stripos($extUrl, 'https://') === 0 || stripos($extUrl, home_url()) === 0)) {
                $order = (int) ($periods[$per]['order'] ?? 1);
                if ($title === '') {
                    $title = trim("{$sub} " . ($periods[$per]['label'] ?? $per) . " {$year}");
                }
                $now = current_time('mysql');
                $existingId = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM `{$table}` WHERE page_slug = %s AND year = %d AND sub_group = %s AND period_key = %s",
                    $slug, $year, $sub, $per
                ));
                $data = array(
                    'page_slug'    => $slug,
                    'year'         => $year,
                    'sub_group'    => $sub,
                    'period_key'   => $per,
                    'period_order' => $order,
                    'title'        => $title,
                    'source_type'  => $srcType,
                    'doc_url'      => $extUrl,
                    'file_size_kb' => $fileKb,
                    'updated_by'   => wp_get_current_user()->display_name ?: 'WP Admin',
                    'status'       => 'published',
                    'updated_at'   => $now,
                );
                if ($existingId > 0) {
                    $wpdb->update($table, $data, array('id' => $existingId));
                } else {
                    $data['created_at'] = $now;
                    $wpdb->insert($table, $data);
                }
                pa_sinova_flush_caches_for_slug($slug);
                $notice = "Dokumen {$slug} ({$per} {$year}) berhasil disimpan & dipublikasikan.";
            } elseif ($notice === '') {
                $notice = 'Silakan unggah berkas PDF atau masukkan tautan HTTPS yang valid.';
            }
        } elseif ($action === 'delete_doc' && !empty($_POST['doc_id'])) {
            $activeTab = 'upload';
            global $wpdb;
            $table = pa_sinova_table_name();
            $did   = (int) $_POST['doc_id'];
            $row   = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE id = %d", $did), ARRAY_A);
            if ($row) {
                if (($row['source_type'] ?? '') === 'wp_pdf' && function_exists('pa_sinova_safe_unlink_local_pdf')) {
                    pa_sinova_safe_unlink_local_pdf((string) $row['doc_url']);
                }
                $wpdb->delete($table, array('id' => $did));
                pa_sinova_flush_caches_for_slug((string) $row['page_slug']);
                $notice = 'Dokumen berhasil dihapus dari arsip.';
            }
        }
    }

    $cfg        = pa_sinova_get_settings();
    $catalog    = function_exists('pa_sinova_module_catalog') ? pa_sinova_module_catalog() : array();
    $periods    = function_exists('pa_sinova_all_periods_map') ? pa_sinova_all_periods_map() : array();
    $report     = pa_sinova_wp_compliance_report();
    $preSlug    = isset($_GET['doc_slug']) ? sanitize_key((string) $_GET['doc_slug']) : 'lra';
    $tabBaseUrl = admin_url('admin.php?page=pa-sinova-starter&tab=');
    ?>
    <div class="wrap">
        <h1 style="color:#056e4f;font-weight:800;">SINOVA Hub — Panel Backend &amp; Kepatuhan (SK Dirjen Badilag 2722/2026)</h1>
        <p style="color:#475569;margin-top:2px;">Halaman backend khusus operator untuk memantau kesiapan 35 kriteria, mengunggah arsip PDF/Drive, serta menyalin URL bukti ke SINOVA. Istilah <em>SINOVA</em> tidak ditampilkan di halaman publik masyarakat.</p>

        <?php if ($notice !== '') : ?>
            <div class="notice notice-success is-dismissible"><p><strong><?php echo esc_html($notice); ?></strong></p></div>
        <?php endif; ?>

        <h2 class="nav-tab-wrapper" style="margin:14px 0 18px;">
            <a href="<?php echo esc_url($tabBaseUrl . 'compliance'); ?>" class="nav-tab <?php echo $activeTab === 'compliance' ? 'nav-tab-active' : ''; ?>">Dashboard 35 Kriteria (<?php echo esc_html((string) $report['total']); ?>%)</a>
            <a href="<?php echo esc_url($tabBaseUrl . 'upload'); ?>" class="nav-tab <?php echo $activeTab === 'upload' ? 'nav-tab-active' : ''; ?>">Upload &amp; Kelola Dokumen</a>
            <a href="<?php echo esc_url($tabBaseUrl . 'settings'); ?>" class="nav-tab <?php echo $activeTab === 'settings' ? 'nav-tab-active' : ''; ?>">Identitas Satker &amp; Seeder 1-Klik</a>
            <a href="<?php echo esc_url($tabBaseUrl . 'security'); ?>" class="nav-tab <?php echo $activeTab === 'security' ? 'nav-tab-active' : ''; ?>">Keamanan &amp; Audit Log</a>
        </h2>

        <?php if ($activeTab === 'compliance') : ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:18px;">
                <div style="background:#fff;border:1px solid #cbd5e1;border-left:5px solid #056e4f;border-radius:10px;padding:14px;">
                    <div style="font-size:12px;color:#64748b;font-weight:700;">SKOR KESIAPAN KONTEN</div>
                    <div style="font-size:28px;font-weight:900;color:#056e4f;"><?php echo esc_html((string) $report['total']); ?>%</div>
                    <div style="font-size:11.5px;color:#475569;">Berkala: <?php echo esc_html((string) $report['berkala']); ?>% · Wajib: <?php echo esc_html((string) $report['wajib']); ?>%</div>
                </div>
                <div style="background:#fff;border:1px solid #cbd5e1;border-left:5px solid #16a34a;border-radius:10px;padding:14px;">
                    <div style="font-size:12px;color:#64748b;font-weight:700;">LENGKAP</div>
                    <div style="font-size:28px;font-weight:900;color:#15803d;"><?php echo esc_html((string) $report['counts']['lengkap']); ?> / 35</div>
                    <div style="font-size:11.5px;color:#475569;">Siap dinilai otomatis</div>
                </div>
                <div style="background:#fff;border:1px solid #cbd5e1;border-left:5px solid #d97706;border-radius:10px;padding:14px;">
                    <div style="font-size:12px;color:#64748b;font-weight:700;">PERLU DILENGKAPI</div>
                    <div style="font-size:28px;font-weight:900;color:#b45309;"><?php echo esc_html((string) $report['counts']['perhatian']); ?> / 35</div>
                    <div style="font-size:11.5px;color:#475569;">Bertanda [LENGKAPI] / kurang tahun</div>
                </div>
                <div style="background:#fff;border:1px solid #cbd5e1;border-left:5px solid #dc2626;border-radius:10px;padding:14px;">
                    <div style="font-size:12px;color:#64748b;font-weight:700;">KOSONG</div>
                    <div style="font-size:28px;font-weight:900;color:#b91c1c;"><?php echo esc_html((string) $report['counts']['kosong']); ?> / 35</div>
                    <div style="font-size:11.5px;color:#475569;">Belum terunggah</div>
                </div>
            </div>

            <div style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:16px;">
                <table class="widefat striped">
                    <thead>
                        <tr><th style="width:64px;">Kode</th><th style="width:90px;">Kelompok</th><th>Kriteria Lampiran II</th><th style="width:125px;">Status</th><th>Catatan Evaluasi</th><th style="width:230px;">URL Bukti untuk SINOVA</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['items'] as $it) :
                            $bg = $it['status'] === 'lengkap' ? '#dcfce7;color:#166534' : ($it['status'] === 'perhatian' ? '#fef3c7;color:#92400e' : '#fee2e2;color:#991b1b');
                            $stLabel = $it['status'] === 'lengkap' ? 'Lengkap' : ($it['status'] === 'perhatian' ? 'Perlu Dilengkapi' : 'Kosong');
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($it['code']); ?></strong></td>
                                <td><span style="text-transform:uppercase;font-size:11px;font-weight:700;color:#475569;"><?php echo esc_html($it['group']); ?></span></td>
                                <td><strong><?php echo esc_html($it['label']); ?></strong><br><code style="font-size:11px;"><?php echo esc_html($it['url']); ?></code></td>
                                <td><span style="display:inline-block;padding:3px 9px;border-radius:999px;font-weight:700;font-size:11.5px;background:<?php echo $bg; ?>;"><?php echo esc_html($stLabel); ?></span></td>
                                <td><?php echo esc_html($it['note']); ?></td>
                                <td>
                                    <button type="button" class="button button-small pa-copy-url-btn" data-url="<?php echo esc_attr($it['url']); ?>">Salin URL</button>
                                    <a href="<?php echo esc_url($it['url']); ?>" target="_blank" class="button button-small">Buka</a>
                                    <a href="<?php echo esc_url($it['edit_url']); ?>" class="button button-small button-primary">Lengkapi</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <script>
                document.querySelectorAll('.pa-copy-url-btn').forEach(function(btn){
                    btn.addEventListener('click', function(){
                        var u = btn.getAttribute('data-url');
                        if(navigator.clipboard){ navigator.clipboard.writeText(u); }
                        var orig = btn.textContent;
                        btn.textContent = 'Tersalin!';
                        setTimeout(function(){ btn.textContent = orig; }, 1400);
                    });
                });
            </script>

        <?php elseif ($activeTab === 'upload') :
            global $wpdb;
            $table = function_exists('pa_sinova_table_name') ? pa_sinova_table_name() : ($wpdb->prefix . 'pa_sinova_documents');
            $recentDocs = $wpdb->get_results("SELECT * FROM `{$table}` ORDER BY updated_at DESC, id DESC LIMIT 25", ARRAY_A);
        ?>
            <div style="display:grid;grid-template-columns:1fr 1.2fr;gap:20px;align-items:start;">
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">Upload Dokumen Cepat</h2>
                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="upload_doc">
                        <table class="form-table">
                            <tr><th>Modul</th><td>
                                <select name="doc_slug" id="pa-doc-slug-select" class="regular-text">
                                    <?php foreach ($catalog as $slug => $m) : ?>
                                        <option value="<?php echo esc_attr($slug); ?>" data-sub="<?php echo esc_attr((string) ($m['sub_groups'][0] ?? 'DOKUMEN')); ?>" <?php selected($preSlug, $slug); ?>><?php echo esc_html($m['title'] . ' (' . $slug . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td></tr>
                            <tr><th>Tahun</th><td><input type="number" name="doc_year" min="2017" max="2027" value="<?php echo esc_attr((string) date('Y')); ?>"></td></tr>
                            <tr><th>Sub-Kelompok</th><td><input type="text" name="doc_subgroup" id="pa-doc-subgroup-input" class="regular-text" value="<?php echo esc_attr((string) ($catalog[$preSlug]['sub_groups'][0] ?? 'SERAPAN 01')); ?>"></td></tr>
                            <tr><th>Periode Slot</th><td>
                                <select name="doc_period">
                                    <?php foreach ($periods as $pk => $pv) : ?>
                                        <option value="<?php echo esc_attr($pk); ?>"><?php echo esc_html($pk . ' — ' . $pv['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td></tr>
                            <tr><th>Upload PDF (Maks 10MB)</th><td><input type="file" name="doc_pdf" accept="application/pdf,.pdf"></td></tr>
                            <tr><th>Atau URL HTTPS / Drive</th><td><input type="url" name="doc_url" class="regular-text" placeholder="https://drive.google.com/file/d/.../view"></td></tr>
                            <tr><th>Judul (Opsional)</th><td><input type="text" name="doc_title" class="regular-text"></td></tr>
                        </table>
                        <p><button type="submit" class="button button-primary">Simpan &amp; Tayangkan ke Website</button></p>
                    </form>
                    <script>
                        var sel = document.getElementById('pa-doc-slug-select');
                        var sub = document.getElementById('pa-doc-subgroup-input');
                        if(sel && sub){
                            sel.addEventListener('change', function(){
                                var opt = sel.options[sel.selectedIndex];
                                if(opt && opt.getAttribute('data-sub')){ sub.value = opt.getAttribute('data-sub'); }
                            });
                        }
                    </script>
                </div>

                <div style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">25 Dokumen Terakhir Diunggah</h2>
                    <table class="widefat striped">
                        <thead><tr><th>Modul / Slot</th><th>Judul Dokumen</th><th>Sumber</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php if (empty($recentDocs)) : ?>
                                <tr><td colspan="4">Belum ada dokumen diunggah.</td></tr>
                            <?php else : foreach ($recentDocs as $rd) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html($rd['page_slug']); ?></strong><br><small><?php echo esc_html($rd['year'] . ' · ' . $rd['period_key']); ?></small></td>
                                    <td><?php echo esc_html($rd['title']); ?><br><small style="color:#64748b;"><?php echo esc_html($rd['sub_group']); ?></small></td>
                                    <td><a href="<?php echo esc_url($rd['doc_url']); ?>" target="_blank"><?php echo esc_html($rd['source_type'] === 'wp_pdf' ? 'PDF Lokal' : 'Drive/URL'); ?> ↗</a></td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Hapus dokumen ini?');">
                                            <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                                            <input type="hidden" name="pa_sinova_action" value="delete_doc">
                                            <input type="hidden" name="doc_id" value="<?php echo esc_attr((string) $rd['id']); ?>">
                                            <button type="submit" class="button button-small" style="color:#b91c1c;">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($activeTab === 'settings') : ?>
            <div style="display:grid;grid-template-columns:1.3fr 1fr;gap:20px;align-items:start;">
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">Konfigurasi Identitas Satker &amp; API</h2>
                    <form method="post">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="save_settings">
                        <table class="form-table">
                            <tr><th>Nama Satker</th><td><input type="text" name="satker_name" class="regular-text" value="<?php echo esc_attr($cfg['satker_name']); ?>"></td></tr>
                            <tr><th>URL Logo Resmi</th><td><input type="url" name="logo_url" class="regular-text" value="<?php echo esc_attr($cfg['logo_url'] ?? ''); ?>" placeholder="https://.../logo-pa.png"></td></tr>
                            <tr><th>Alamat Kantor</th><td><input type="text" name="address" class="regular-text" value="<?php echo esc_attr($cfg['address']); ?>"></td></tr>
                            <tr><th>Telepon / WA PTSP</th><td><input type="text" name="phone" class="regular-text" value="<?php echo esc_attr($cfg['phone']); ?>"></td></tr>
                            <tr><th>Email Resmi</th><td><input type="email" name="email" class="regular-text" value="<?php echo esc_attr($cfg['email']); ?>"></td></tr>
                            <tr><th>Jam Sidang</th><td><input type="text" name="hearing_hours" class="regular-text" value="<?php echo esc_attr($cfg['hearing_hours'] ?? ''); ?>"></td></tr>
                            <tr><th>Nama Pimpinan / Jabatan</th><td><input type="text" name="leader_name" class="regular-text" value="<?php echo esc_attr($cfg['leader_name']); ?>"></td></tr>
                            <tr><th>Sambutan Pimpinan</th><td><textarea name="leader_welcome" rows="3" class="large-text"><?php echo esc_textarea($cfg['leader_welcome']); ?></textarea></td></tr>
                            <tr><th>URL SIPP</th><td><input type="url" name="sipp_url" class="regular-text" value="<?php echo esc_attr($cfg['sipp_url']); ?>"></td></tr>
                            <tr><th>URL e-Court</th><td><input type="url" name="ecourt_url" class="regular-text" value="<?php echo esc_attr($cfg['ecourt_url']); ?>"></td></tr>
                            <tr><th>URL Direktori Putusan</th><td><input type="url" name="direktori_url" class="regular-text" value="<?php echo esc_attr($cfg['direktori_url']); ?>"></td></tr>
                            <tr><th>URL Survei Badilag</th><td><input type="url" name="survey_url" class="regular-text" value="<?php echo esc_attr($cfg['survey_url']); ?>"></td></tr>
                            <tr><th>Secret Key HMAC API</th><td><input type="text" name="hmac_secret" class="regular-text" value="<?php echo esc_attr($cfg['hmac_secret']); ?>" placeholder="Minimal 32 karakter"><br><label><input type="checkbox" name="generate_hmac" value="1"> Generate Secret Key acak baru (64 hex)</label></td></tr>
                            <tr><th>Turnstile Site Key</th><td><input type="text" name="turnstile_site_key" class="regular-text" value="<?php echo esc_attr($cfg['turnstile_site_key']); ?>"></td></tr>
                            <tr><th>Turnstile Secret Key</th><td><input type="password" name="turnstile_secret_key" class="regular-text" value="<?php echo esc_attr($cfg['turnstile_secret_key']); ?>"></td></tr>
                        </table>
                        <p><button type="submit" class="button button-primary">Simpan Pengaturan</button></p>
                    </form>
                </div>

                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">Seeder Struktur Baku 1-Klik</h2>
                    <p>Membuat otomatis <strong>23 halaman Arsip Dokumen</strong> (slot s.d. 2027), <strong>17 halaman wajib/statis</strong> bertanda <code>[LENGKAPI]</code>, halaman <strong>Beranda</strong> lengkap, berita awal, dan menyusun <strong>Menu Utama</strong> di tema Astra / WordPress.</p>
                    <form method="post">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="run_seeder">
                        <button type="submit" class="button button-primary">Generate Halaman, Beranda &amp; Menu (1-Klik)</button>
                    </form>
                </div>
            </div>

        <?php else :
            $secLogs = get_option('pa_starter_security_logs', array());
            if (!is_array($secLogs)) $secLogs = array();
            $tsActive = function_exists('pa_starter_login_turnstile') && pa_starter_login_turnstile() !== null;
        ?>
            <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;margin-bottom:18px;">
                <h2 style="margin-top:0;color:#056e4f;">Status Pengamanan Aktif</h2>
                <ul style="line-height:1.8;margin:0;padding-left:18px;">
                    <li><strong>File Editor WP Admin:</strong> Dinonaktifkan (<code>DISALLOW_FILE_EDIT = true</code>)</li>
                    <li><strong>XML-RPC &amp; User Enumeration REST/Author:</strong> Diblokir untuk pengguna anonim</li>
                    <li><strong>Brute-Force Rate Limiter Login:</strong> Aktif (blokir 15 menit setelah 5x gagal per IP ter-hash)</li>
                    <li><strong>Login Anti-Bot:</strong> <?php echo $tsActive ? 'Cloudflare Turnstile + Honeypot' : 'CAPTCHA Matematika Offline (HMAC) + Honeypot'; ?></li>
                    <li><strong>Folder Unggahan PDF (<code>sinova-docs/.htaccess</code>):</strong> <?php echo is_file(WP_CONTENT_DIR . '/uploads/sinova-docs/.htaccess') ? 'Terlindungi (PHP execution &amp; listing diblokir)' : 'Aktif setelah admin_init'; ?></li>
                    <li><strong>WAF URI &amp; DB Content Guard:</strong> Aktif memblokir pola judi online, webshell, SQLi/XSS, &amp; cloaking tautan</li>
                </ul>
            </div>
            <div style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:18px;">
                <h2 style="margin-top:0;color:#056e4f;">Log Kejadian Keamanan Terakhir (40 Teratas)</h2>
                <table class="widefat striped">
                    <thead><tr><th>Waktu (UTC)</th><th>Jenis Kejadian</th><th>Keterangan</th><th>Hash IP</th></tr></thead>
                    <tbody>
                        <?php if (empty($secLogs)) : ?>
                            <tr><td colspan="4">Belum ada percobaan mencurigakan yang tercatat.</td></tr>
                        <?php else : foreach ($secLogs as $lg) : ?>
                            <tr>
                                <td><?php echo esc_html($lg['at'] ?? '-'); ?></td>
                                <td><code><?php echo esc_html($lg['event'] ?? '-'); ?></code></td>
                                <td><?php echo esc_html($lg['detail'] ?? '-'); ?></td>
                                <td><code><?php echo esc_html($lg['ip_hash'] ?? '-'); ?></code></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
