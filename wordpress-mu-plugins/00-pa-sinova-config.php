<?php
/**
 * Plugin Name: PA SINOVA Starter - Konfigurasi, Seeder 1-Klik SK 2722/2026, RBAC & Panel Kepatuhan WP Admin
 * Description: Pengaturan dinamis Satker, RBAC peran operator, generator Secret Key HMAC-SHA256, Seeder 1-Klik 6 Menu Baku SK Dirjen Badilag 2722/2026, Dashboard Kepatuhan 35 Kriteria + Migrasi & Adopsi, Data Operasional, Upload Dokumen, & Audit Keamanan + MFA di WP Admin.
 * Version: 1.5.0-starter
 */

defined('ABSPATH') || exit;

function pa_sinova_get_settings(): array {
    $defaults = array(
        'satker_name'              => get_bloginfo('name') ?: 'Pengadilan Agama Contoh Kelas I-A',
        'logo_url'                 => '',
        'preserve_existing_design' => '0',
        'address'                  => 'Jl. Keadilan Raya No. 1, Kota Contoh',
        'phone'                    => '(000) 123-4567 · WA PTSP 0811-0000-0000',
        'email'                    => 'info@pa-contoh.go.id',
        'hearing_hours'            => 'Senin–Kamis 09.00 WIB s.d. selesai',
        'leader_name'              => 'Ketua Pengadilan Agama',
        'leader_welcome'           => 'Selamat datang di portal resmi Pengadilan Agama. Melalui website ini kami menyajikan informasi layanan peradilan, transparansi keuangan, laporan kinerja, dan dokumen pembangunan Zona Integritas secara terbuka kepada seluruh masyarakat pencari keadilan.',
        'sipp_url'                 => 'https://sipp.pa-contoh.go.id/',
        'ecourt_url'               => 'https://ecourt.mahkamahagung.go.id/',
        'direktori_url'            => 'https://putusan3.mahkamahagung.go.id/',
        'survey_url'               => 'https://survei.badilag.net/',
        'aco_url'                  => 'https://cctv.badilag.net/',
        'rss_ma_url'               => 'https://kepaniteraan.mahkamahagung.go.id/?format=feed&type=rss',
        'rss_badilag_url'          => 'https://badilag.mahkamahagung.go.id/pengumuman-elektronik?format=feed&type=rss',
        'custom_login_slug'        => '',
        'enforce_mfa'              => '0',
        'hmac_secret'              => '',
        'turnstile_site_key'       => '',
        'turnstile_secret_key'     => '',
    );
    $saved = get_option('pa_sinova_starter_settings', array());
    if (!is_array($saved)) {
        $saved = array();
    }
    $merged = array_merge($defaults, $saved);

    if (defined('PA_SINOVA_SATKER_NAME') && PA_SINOVA_SATKER_NAME !== '') {
        $merged['satker_name'] = (string) PA_SINOVA_SATKER_NAME;
    }
    if (defined('PA_ACO_URL') && PA_ACO_URL !== '') {
        $merged['aco_url'] = (string) PA_ACO_URL;
    }
    if (defined('PA_CUSTOM_LOGIN_SLUG') && PA_CUSTOM_LOGIN_SLUG !== '') {
        $merged['custom_login_slug'] = (string) PA_CUSTOM_LOGIN_SLUG;
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

function pa_sinova_register_roles(): void {
    $adminRole = get_role('administrator');
    if ($adminRole && !$adminRole->has_cap('pa_access_sinova_hub')) {
        $adminRole->add_cap('pa_access_sinova_hub');
    }

    $ver = '1.0';
    if (get_option('pa_sinova_roles_ver') === $ver) {
        return;
    }

    $defs = array(
        'pa_kepaniteraan' => array(
            'label' => 'Operator Kepaniteraan',
            'caps'  => array('read' => true, 'upload_files' => true, 'edit_pages' => true, 'edit_others_pages' => true, 'publish_pages' => true, 'pa_access_sinova_hub' => true, 'pa_manage_kepaniteraan' => true),
        ),
        'pa_kesekretariatan' => array(
            'label' => 'Operator Kesekretariatan',
            'caps'  => array('read' => true, 'upload_files' => true, 'edit_pages' => true, 'edit_others_pages' => true, 'publish_pages' => true, 'pa_access_sinova_hub' => true, 'pa_manage_kesekretariatan' => true),
        ),
        'pa_humas' => array(
            'label' => 'Operator Humas & Berita',
            'caps'  => array('read' => true, 'upload_files' => true, 'edit_posts' => true, 'edit_others_posts' => true, 'publish_posts' => true, 'delete_posts' => true, 'pa_access_sinova_hub' => true, 'pa_manage_humas' => true),
        ),
    );

    foreach ($defs as $slug => $cfg) {
        $roleObj = get_role($slug);
        if (!$roleObj) {
            add_role($slug, $cfg['label'], $cfg['caps']);
        } else {
            foreach ($cfg['caps'] as $cap => $grant) {
                if ($grant && !$roleObj->has_cap($cap)) {
                    $roleObj->add_cap($cap);
                }
            }
        }
    }
    update_option('pa_sinova_roles_ver', $ver, false);
}
add_action('init', 'pa_sinova_register_roles', 5);

function pa_sinova_user_allowed_tabs(): array {
    if (current_user_can('manage_options')) {
        return array('compliance', 'upload', 'data', 'migration', 'settings', 'security');
    }
    if (current_user_can('pa_manage_kepaniteraan') || current_user_can('pa_manage_kesekretariatan')) {
        return array('compliance', 'upload', 'data', 'security');
    }
    if (current_user_can('pa_manage_humas')) {
        return array('compliance', 'data', 'security');
    }
    return array();
}

function pa_sinova_user_allowed_module_slugs(): array {
    if (current_user_can('manage_options')) {
        return array();
    }
    $slugs = array();
    if (current_user_can('pa_manage_kepaniteraan')) {
        $slugs = array_merge($slugs, array('biaya-perkara', 'sisa-panjar', 'biaya-prodeo', 'hak-kepaniteraan', 'akses-informasi', 'rekap-pengaduan'));
    }
    if (current_user_can('pa_manage_kesekretariatan')) {
        $slugs = array_merge($slugs, array('lra', 'pnbp', 'neraca', 'calk', 'dipa', 'aset', 'pbj', 'lkjip', 'iku', 'pk', 'rkt', 'rencana-aksi', 'renstra', 'mou', 'lhkpn', 'lhkasn', 'survei-pelayanan'));
    }
    if (current_user_can('pa_manage_humas')) {
        $slugs = array_merge($slugs, array('survei-pelayanan', 'mou'));
    }
    return array_values(array_unique($slugs));
}

function pa_sinova_sanitize_login_slug(string $raw): array {
    $clean = strtolower(sanitize_title(trim($raw, " /\t\n\r")));
    if ($clean === '') {
        return array('valid' => true, 'slug' => '', 'error' => '');
    }
    $reserved = array(
        'wp-admin', 'wp-login', 'wp-login-php', 'wp-content', 'wp-includes',
        'wp-json', 'wp-cron', 'xmlrpc', 'admin', 'login', 'dashboard',
        'beranda', 'feed', 'author', 'search', 'robots', 'sitemap', 'lra', 'dipa',
    );
    if (!preg_match('/^[a-z0-9][a-z0-9-]{2,38}[a-z0-9]$/', $clean)) {
        return array('valid' => false, 'slug' => '', 'error' => 'Slug login minimal 4 karakter, hanya huruf kecil, angka, dan tanda hubung (-).');
    }
    if (in_array($clean, $reserved, true)) {
        return array('valid' => false, 'slug' => '', 'error' => 'Slug tersebut menggunakan nama tercadang sistem; pilih kata yang lebih unik.');
    }
    if (function_exists('get_page_by_path') && did_action('init')) {
        $existingPage = get_page_by_path($clean);
        if ($existingPage instanceof WP_Post && $existingPage->post_status === 'publish') {
            return array('valid' => false, 'slug' => '', 'error' => 'Slug tersebut sudah dipakai halaman publik yang terbit; gunakan slug lain.');
        }
    }
    return array('valid' => true, 'slug' => $clean, 'error' => '');
}

function pa_sinova_custom_login_slug(): string {
    if (defined('PA_DISABLE_CUSTOM_LOGIN') && PA_DISABLE_CUSTOM_LOGIN) {
        return '';
    }
    $cfg = pa_sinova_get_settings();
    $res = pa_sinova_sanitize_login_slug((string) ($cfg['custom_login_slug'] ?? ''));
    return $res['valid'] ? (string) $res['slug'] : '';
}

function pa_sinova_satker_name(): string {
    $cfg = pa_sinova_get_settings();
    return trim((string) ($cfg['satker_name'] ?? 'Pengadilan Agama')) ?: 'Pengadilan Agama';
}

function pa_sinova_get_secret_key(): string {
    $cfg = pa_sinova_get_settings();
    return trim((string) ($cfg['hmac_secret'] ?? ''));
}

function pa_sinova_get_custom_map(): array {
    $map = get_option('pa_sinova_custom_page_map', array());
    return is_array($map) ? $map : array();
}

/**
 * Cari halaman terbit baik di root maupun sebagai sub-halaman (nested slug) agar tidak menduplikasi halaman WP lama.
 */
function pa_sinova_find_existing_page(string $slug): ?WP_Post {
    $direct = get_page_by_path($slug);
    if ($direct instanceof WP_Post && $direct->post_status === 'publish') {
        return $direct;
    }
    global $wpdb;
    $id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM `{$wpdb->posts}` WHERE post_type = 'page' AND post_status = 'publish' AND post_name = %s ORDER BY ID ASC LIMIT 1",
        $slug
    ));
    if ($id > 0) {
        $p = get_post($id);
        if ($p instanceof WP_Post) {
            return $p;
        }
    }
    return null;
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
            'content' => '<h2>Daftar Pejabat Struktural, Fungsional, Hakim &amp; Pegawai</h2><p>Berikut direktori resmi pimpinan, hakim, pejabat kepaniteraan, pejabat kesekretariatan, dan aparatur pengadilan.</p>[pa_pegawai_grid]',
        ),
        'alamat-dan-kontak' => array(
            'title'   => 'Alamat dan Kontak Resmi',
            'parent'  => 'Profil Pengadilan',
            'code'    => 'W1.5',
            'content' => '<h2>Identitas &amp; Alamat Kantor</h2><p>[LENGKAPI: Jalan, Kelurahan, Kecamatan, Kota/Kabupaten, Kode Pos, Telepon, Email resmi, &amp; Foto Gedung tampak depan.]</p>',
        ),
        'e-court' => array(
            'title'   => 'Informasi e-Court',
            'parent'  => 'Layanan Hukum',
            'code'    => 'W2.1',
            'content' => '<h2>Pendaftaran Perkara Elektronik (e-Court)</h2><p>[LENGKAPI panduan pendaftaran e-Filing, e-Payment, e-Summons, &amp; e-Litigation beserta tautan ke ecourt.mahkamahagung.go.id.]</p>',
        ),
        'direktori-putusan' => array(
            'title'   => 'Direktori Putusan Satker',
            'parent'  => 'Layanan Hukum',
            'code'    => 'W2.2',
            'content' => '<h2>Direktori Putusan Mahkamah Agung RI</h2><p>[LENGKAPI tautan langsung halaman Direktori Putusan satker pada domain putusan3.mahkamahagung.go.id.]</p>',
        ),
        'prosedur-berperkara' => array(
            'title'   => 'Prosedur Berperkara',
            'parent'  => 'Layanan Hukum',
            'code'    => 'W2.3',
            'content' => '<h2>Alur &amp; Prosedur Berperkara (Tingkat Pertama, Banding, Kasasi, PK)</h2><p>[LENGKAPI syarat dan alur 6 jenis prosedur berperkara.]</p>',
        ),
        'biaya-proses-berperkara' => array(
            'title'   => 'Biaya Proses Berperkara & Radius',
            'parent'  => 'Layanan Hukum',
            'code'    => 'W2.4',
            'content' => '<h2>SK Panjar Biaya Perkara &amp; Radius Panggilan</h2><p>[LENGKAPI tabel SK Ketua tentang Panjar Biaya Perkara &amp; Radius Panggilan Jurusita.]</p>',
        ),
        'agenda-jadwal-persidangan' => array(
            'title'   => 'Agenda dan Jadwal Persidangan',
            'parent'  => 'Layanan Hukum',
            'code'    => 'B2.2',
            'content' => '<h2>Jadwal Sidang Harian</h2><p>Informasi agenda persidangan terbuka untuk umum pada satuan kerja.</p>[pa_jadwal_sidang]',
        ),
        'statistik-perkara' => array(
            'title'   => 'Statistik Perkara',
            'parent'  => 'Layanan Hukum',
            'code'    => 'B2.3',
            'content' => '<h2>Laporan Statistik Perkara Bulanan</h2><p>Rekapitulasi perkara diterima, diputus, dicabut, dan sisa pada satuan kerja.</p>[pa_statistik_perkara]',
        ),
        'panggilan-ghaib' => array(
            'title'   => 'Daftar Panggilan Ghaib',
            'parent'  => 'Layanan Hukum',
            'code'    => 'B2.4',
            'content' => '<h2>Pengumuman Panggilan Ghaib (Pasal 27 PP 9/1975)</h2><p>Daftar resmi pengumuman panggilan sidang bagi pihak berperkara yang tidak diketahui tempat tinggalnya.</p>[pa_panggilan_ghaib]',
        ),
        'laporan-tahunan' => array(
            'title'   => 'Laporan Tahunan & Program Kerja',
            'parent'  => 'Transparansi',
            'code'    => 'B1.2',
            'content' => '<h2>Arsip Laporan Tahunan &amp; Program Kerja</h2><p>[LENGKAPI tautan dokumen Laporan Tahunan 5 tahun terakhir.]</p>',
        ),
        'standar-maklumat-pelayanan' => array(
            'title'   => 'Standar dan Maklumat Pelayanan',
            'parent'  => 'Layanan Publik & Pengaduan',
            'code'    => 'W4.1',
            'content' => '<h2>Standar &amp; Maklumat Pelayanan Publik</h2><p>[LENGKAPI SK Standar Pelayanan, Maklumat Pelayanan PTSP, SOP PPID, dan Layanan Kelompok Rentan/Disabilitas.]</p>',
        ),
        'disabilitas' => array(
            'title'   => 'Layanan Kelompok Rentan & Disabilitas',
            'parent'  => 'Layanan Publik & Pengaduan',
            'code'    => 'W4.1',
            'content' => '<h2>Layanan Inklusif Bagi Penyandang Disabilitas &amp; Kelompok Rentan</h2><p>Satuan kerja menyediakan sarana ramah disabilitas di area Pelayanan Terpadu Satu Pintu (PTSP) dan ruang sidang: jalur landai kursi roda (ramp), parkir prioritas, toilet aksesibel, kursi tunggu khusus, loket prioritas, pendampingan petugas PTSP, formulir informasi Braille/cetak besar, serta layanan bantuan juru bahasa isyarat atas permintaan.</p><h2>Aksesibilitas Digital Portal Website</h2><p>Portal ini dilengkapi bilah alat aksesibilitas pada bagian kanan bawah (tombol Pembesar/Pengecil Teks, Mode Kontras Tinggi, Mode Gelap, serta Pembaca Suara <em>Text-to-Speech</em> berbahasa Indonesia) dan navigasi papan ketik penuh untuk mendukung pembaca layar.</p>',
        ),
        'hukuman-disiplin' => array(
            'title'   => 'Hukuman Disiplin Hasil Pengawasan',
            'parent'  => 'Layanan Publik & Pengaduan',
            'code'    => 'B4.3',
            'content' => '<h2>Publikasi Hasil Pengawasan &amp; Hukuman Disiplin</h2><p>[LENGKAPI rekapitulasi hasil pengawasan atau keterangan Nihil pada periode berjalan.]</p>',
        ),
        'statistik-kunjungan' => array(
            'title'   => 'Statistik Kunjungan Website',
            'parent'  => 'Layanan Publik & Pengaduan',
            'code'    => 'B5.1',
            'content' => '<h2>Statistik Pengunjung Website Resmi</h2>[pa_visitor_stats]',
        ),
        'pengumuman-resmi' => array(
            'title'   => 'Pengumuman MA RI & Badilag',
            'parent'  => 'Berita & Publikasi',
            'code'    => 'B5.1',
            'content' => '<h2>Siaran Resmi Mahkamah Agung RI &amp; Ditjen Badilag</h2><p>Pembaruan pengumuman, surat edaran, dan informasi kelembagaan langsung dari umpan RSS resmi Mahkamah Agung RI dan Direktorat Jenderal Badan Peradilan Agama.</p>[pa_official_announcements limit="10"]',
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

    $recStats = array();
    if (function_exists('pa_portal_records_table') && function_exists('pa_portal_records_ensure_table')) {
        pa_portal_records_ensure_table();
        $rTable = pa_portal_records_table();
        $rRows  = $wpdb->get_results("SELECT record_type, COUNT(*) AS c FROM `{$rTable}` WHERE status = 'published' GROUP BY record_type", ARRAY_A);
        foreach ((array) $rRows as $rr) {
            $recStats[(string) $rr['record_type']] = (int) $rr['c'];
        }
    }
    $slugToRecType = array(
        'profil-pegawai'            => 'pegawai',
        'agenda-jadwal-persidangan' => 'jadwal',
        'statistik-perkara'         => 'statistik',
        'panggilan-ghaib'           => 'ghaib',
    );

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

    $items     = array();
    $counts    = array('lengkap' => 0, 'perhatian' => 0, 'kosong' => 0);
    $bPts      = 0;
    $wPts      = 0;
    $customMap = pa_sinova_get_custom_map();

    foreach ($specs as $sp) {
        list($code, $group, $label, $slug, $mode) = $sp;
        $mapped = (isset($customMap[$code]) && is_array($customMap[$code])) ? $customMap[$code] : array();
        $mapPid = !empty($mapped['post_id']) ? (int) $mapped['post_id'] : 0;
        $mapUrl = !empty($mapped['custom_url']) ? trim((string) $mapped['custom_url']) : '';

        $pageObj = null;
        if ($mapPid > 0) {
            $candidate = get_post($mapPid);
            if ($candidate instanceof WP_Post && $candidate->post_status === 'publish') {
                $pageObj = $candidate;
            }
        }
        if (!$pageObj instanceof WP_Post) {
            $pageObj = pa_sinova_find_existing_page($slug);
        }

        if ($mapUrl !== '') {
            $url = $mapUrl;
        } elseif ($pageObj instanceof WP_Post) {
            $url = (string) get_permalink($pageObj->ID);
        } else {
            $url = pa_sinova_resolve_page_url($slug);
        }
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
                $recType    = $slugToRecType[$slug] ?? '';
                if ($recType !== '' && stripos($rawContent, '[pa_') !== false) {
                    $recCnt = (int) ($recStats[$recType] ?? 0);
                    if ($recCnt > 0) {
                        $status = 'lengkap';
                        $pts    = 10;
                        $note   = "Halaman & data terstruktur aktif ({$recCnt} entri terpublikasi).";
                    } else {
                        $status = 'perhatian';
                        $pts    = 5;
                        $note   = 'Halaman siap dengan shortcode dinamis, namun entri Data Operasional masih 0.';
                    }
                    $editUrl = admin_url('admin.php?page=pa-sinova-starter&tab=data&rec_type=' . rawurlencode($recType));
                } elseif (stripos($rawContent, '[LENGKAPI') !== false) {
                    $status = 'perhatian';
                    $pts    = 5;
                    $note   = 'Halaman sudah ada, masih berisi penanda [LENGKAPI].';
                } elseif (strlen(trim(wp_strip_all_tags($rawContent))) >= 20 || stripos($rawContent, '[pa_') !== false) {
                    $status = 'lengkap';
                    $pts    = 10;
                    $note   = $mapPid > 0 ? 'Tertaut ke halaman situs lama (pemetaan kustom).' : 'Halaman resmi terpublikasi (HTTP 200).';
                } else {
                    $status = 'perhatian';
                    $pts    = 5;
                    $note   = 'Konten halaman masih terlalu singkat.';
                }
            } elseif ($mapUrl !== '') {
                $status = 'lengkap';
                $pts    = 10;
                $note   = 'Tertaut ke URL kustom pemetaan situs lama.';
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

    $disabPage = pa_sinova_find_existing_page('disabilitas');

    return array(
        'items'              => $items,
        'counts'             => $counts,
        'berkala'            => $bPct,
        'wajib'              => $wPct,
        'total'              => $tPct,
        'disabilitas_active' => ($disabPage instanceof WP_Post && $disabPage->post_status === 'publish'),
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
 * Seeder 1-Klik: membuat halaman modul & wajib yang belum ada.
 * Jika mode adopsi situs lama aktif ($forcePreserve = true), tema, menu eksisting, dan Beranda lama tidak diubah.
 */
function pa_sinova_run_starter_seeder(?bool $forcePreserve = null, bool $rebuildMenu = false): array {
    $cfg      = pa_sinova_get_settings();
    $preserve = $forcePreserve ?? (!empty($cfg['preserve_existing_design']) && (string) $cfg['preserve_existing_design'] === '1');
    $seeded   = get_option('pa_sinova_seeded_pages', array());
    if (!is_array($seeded)) {
        $seeded = array();
    }

    $createdCount  = 0;
    $menuItemsPlan = array(
        'Profil Pengadilan'         => array(),
        'Layanan Hukum'              => array(),
        'Transparansi'               => array(),
        'Layanan Publik & Pengaduan' => array(),
        'Berita & Publikasi'         => array(),
    );

    $catToParent = array(
        'Keuangan & Anggaran'              => 'Transparansi',
        'Perencanaan & SAKIP'              => 'Transparansi',
        'Kepaniteraan & Biaya Perkara'     => 'Layanan Hukum',
        'Layanan Publik, PPID & Kepatuhan' => 'Layanan Publik & Pengaduan',
    );

    $upsertPage = static function (string $key, string $title, string $slug, string $content) use (&$seeded, &$createdCount): int {
        $existingId = isset($seeded[$key]) ? (int) $seeded[$key] : 0;
        if ($existingId > 0 && get_post_status($existingId)) {
            return $existingId;
        }
        $byPath = pa_sinova_find_existing_page($slug);
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
            if (!isset($menuItemsPlan[$parentName])) {
                $parentName = 'Layanan Publik & Pengaduan';
            }
            $menuItemsPlan[$parentName][] = array('title' => $meta['title'], 'post_id' => $pid);
        }
    }

    if (function_exists('pa_sinova_module_catalog')) {
        foreach (pa_sinova_module_catalog() as $slug => $cfgMod) {
            $shortcode = '[pa_sinova_docs slug="' . esc_attr($slug) . '"]';
            $pid = $upsertPage('mod:' . $slug, $cfgMod['title'], $slug, $shortcode);
            if ($pid > 0) {
                if ($slug === 'mou') {
                    $parentName = 'Berita & Publikasi';
                } elseif (in_array($slug, array('lhkpn', 'lhkasn'), true)) {
                    $parentName = 'Transparansi';
                } else {
                    $parentName = $catToParent[$cfgMod['category']] ?? 'Layanan Publik & Pengaduan';
                }
                $menuItemsPlan[$parentName][] = array('title' => $cfgMod['title'], 'post_id' => $pid);
            }
        }
    }

    $menuItemsPlan['Berita & Publikasi'][] = array(
        'title' => 'Berita & Artikel',
        'url'   => home_url('/?post_type=post'),
    );

    $menuCreated = 0;

    if (!$preserve || $rebuildMenu) {
        $menuName = 'Menu Utama SINOVA';
        $menuObj  = wp_get_nav_menu_object($menuName);
        $menuId   = $menuObj ? (int) $menuObj->term_id : (int) wp_create_nav_menu($menuName);

        if ($menuId > 0) {
            $existingItems = wp_get_nav_menu_items($menuId);
            if ($rebuildMenu && is_array($existingItems) && !empty($existingItems)) {
                foreach ($existingItems as $oldItem) {
                    wp_delete_post((int) $oldItem->ID, true);
                }
                $existingItems = array();
            }
            if (empty($existingItems)) {
                $pos = 1;
                wp_update_nav_menu_item($menuId, 0, array(
                    'menu-item-title'    => 'Beranda',
                    'menu-item-url'      => home_url('/'),
                    'menu-item-type'     => 'custom',
                    'menu-item-status'   => 'publish',
                    'menu-item-position' => $pos++,
                ));
                $menuCreated++;
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
                            if (!empty($ch['post_id'])) {
                                wp_update_nav_menu_item($menuId, 0, array(
                                    'menu-item-object-id' => (int) $ch['post_id'],
                                    'menu-item-object'    => 'page',
                                    'menu-item-type'      => 'post_type',
                                    'menu-item-status'    => 'publish',
                                    'menu-item-parent-id' => (int) $parentItemId,
                                    'menu-item-position'  => $pos++,
                                ));
                            } else {
                                wp_update_nav_menu_item($menuId, 0, array(
                                    'menu-item-title'     => (string) ($ch['title'] ?? 'Tautan'),
                                    'menu-item-url'       => (string) ($ch['url'] ?? home_url('/')),
                                    'menu-item-type'      => 'custom',
                                    'menu-item-status'    => 'publish',
                                    'menu-item-parent-id' => (int) $parentItemId,
                                    'menu-item-position'  => $pos++,
                                ));
                            }
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
    }

    if (!$preserve) {

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

        if ($frontId > 0) {
            if (get_option('show_on_front') !== 'page') {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $frontId);
            }
            pa_sinova_apply_astra_defaults($frontId);
        }
    }

    update_option('pa_sinova_seeded_pages', $seeded, false);

    return array(
        'pages_created' => $createdCount,
        'total_mapped'  => count($seeded),
        'menu_items'    => $menuCreated,
        'preserve_mode' => $preserve ? 1 : 0,
    );
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
    $page = pa_sinova_find_existing_page($slug);
    if ($page instanceof WP_Post) {
        $u = get_permalink($page->ID);
        if ($u) return $u;
    }
    return home_url('/' . ltrim($fallbackPath ?: $slug . '/', '/'));
}

/**
 * Ekstrak tautan dokumen (PDF / Google Drive / URL arsip) dari konten halaman atau pos lama.
 */
function pa_sinova_extract_links_from_post(int $postId, string $modSlug = '', int $defaultYear = 0, string $defaultSub = ''): array {
    $post = get_post($postId);
    if (!$post instanceof WP_Post) {
        return array();
    }

    $fallbackYear = ($defaultYear >= 2017 && $defaultYear <= 2027) ? $defaultYear : (int) wp_date('Y');
    $modCfg       = ($modSlug !== '' && function_exists('pa_sinova_module_config')) ? pa_sinova_module_config($modSlug) : null;
    $subGroups    = ($modCfg && !empty($modCfg['sub_groups']) && is_array($modCfg['sub_groups'])) ? $modCfg['sub_groups'] : array('DOKUMEN');
    $fallbackSub  = trim($defaultSub) !== '' ? trim($defaultSub) : (string) $subGroups[0];
    $periodsMap   = function_exists('pa_sinova_all_periods_map') ? pa_sinova_all_periods_map() : array();
    $modPeriods   = (is_array($modCfg) && function_exists('pa_sinova_module_periods')) ? pa_sinova_module_periods($modCfg) : $periodsMap;
    $defaultPer   = !empty($modPeriods) ? (string) array_key_first($modPeriods) : 'TAHUNAN';

    $content = (string) $post->post_content;
    if (!preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is', $content, $matches, PREG_OFFSET_CAPTURE)) {
        return array();
    }

    $monthPatterns = array(
        'JAN' => '/\bjan(?:uari|uary)?\b/i',
        'FEB' => '/\b(?:feb|peb)(?:ruari|ruary)?\b/i',
        'MAR' => '/\bmar(?:et|ch)?\b/i',
        'APR' => '/\bapr(?:il)?\b/i',
        'MEI' => '/\b(?:mei|may)\b/i',
        'JUN' => '/\bjun(?:i|e)?\b/i',
        'JUL' => '/\bjul(?:i|y)?\b/i',
        'AGT' => '/\b(?:agt|ags|agustus|august|aug)\b/i',
        'SEP' => '/\bsep(?:t(?:ember)?)?\b/i',
        'OKT' => '/\b(?:okt|oct)(?:ober)?\b/i',
        'NOV' => '/\b(?:nov|nop)(?:ember)?\b/i',
        'DES' => '/\b(?:des|dec)(?:ember)?\b/i',
    );
    $quarterPatterns = array(
        'TW4' => '/\b(?:triwulan|tw)\s*(?:iv|4|empat)\b/i',
        'TW3' => '/\b(?:triwulan|tw)\s*(?:iii|3|tiga)\b/i',
        'TW2' => '/\b(?:triwulan|tw)\s*(?:ii|2|dua)\b/i',
        'TW1' => '/\b(?:triwulan|tw)\s*(?:i|1|satu)\b/i',
        'SEM2' => '/\b(?:semester|sem)\s*(?:ii|2|genap)\b/i',
        'SEM1' => '/\b(?:semester|sem)\s*(?:i|1|ganjil)\b/i',
        'REVISI' => '/\brevisi\b/i',
        'REVIU'  => '/\b(?:reviu|review)\b/i',
        'TAHUNAN' => '/\btahunan\b/i',
    );

    $results = array();
    $seenUrl = array();

    foreach ($matches[2] as $idx => $hrefMatch) {
        $rawHref = html_entity_decode(trim((string) $hrefMatch[0]), ENT_QUOTES, 'UTF-8');
        if ($rawHref === '' || strpos($rawHref, '#') === 0 || preg_match('#^(?:javascript|mailto|tel|data):#i', $rawHref)) {
            continue;
        }
        if (strpos($rawHref, '/') === 0 && strpos($rawHref, '//') !== 0) {
            $rawHref = home_url($rawHref);
        } elseif (!preg_match('#^https?://#i', $rawHref) && strpos($rawHref, '//') !== 0) {
            $rawHref = home_url('/' . ltrim($rawHref, '/'));
        }
        $cleanUrl = esc_url_raw($rawHref, array('http', 'https'));
        if ($cleanUrl === '' || !preg_match('#^https?://#i', $cleanUrl)) {
            continue;
        }
        $dedupKey = strtolower($cleanUrl);
        if (isset($seenUrl[$dedupKey])) {
            continue;
        }
        $seenUrl[$dedupKey] = true;

        $anchorRaw  = (string) ($matches[3][$idx][0] ?? '');
        $anchorText = trim((string) preg_replace('/\s+/', ' ', wp_strip_all_tags(html_entity_decode($anchorRaw, ENT_QUOTES, 'UTF-8'))));
        $offset     = (int) ($matches[0][$idx][1] ?? 0);
        $beforeHtml = substr($content, max(0, $offset - 350), min(350, $offset));
        $beforeClean = (string) preg_replace('/<a\b[^>]*>.*?<\/a>/is', ' ', $beforeHtml);
        $nearText   = trim((string) preg_replace('/\s+/', ' ', wp_strip_all_tags(html_entity_decode($beforeClean, ENT_QUOTES, 'UTF-8'))));
        $urlPath    = (string) wp_parse_url($cleanUrl, PHP_URL_PATH);

        $yr = $fallbackYear;
        foreach (array($anchorText, $urlPath, substr($nearText, -160)) as $src) {
            if (preg_match('/(?<!\d)(201[7-9]|202[0-7])(?!\d)/', $src, $ym)) {
                $yr = (int) $ym[1];
                break;
            }
        }

        $linkOwnText = trim($anchorText . ' ' . urldecode($urlPath));
        $nearTail    = substr($nearText, -140);
        $perKey      = '';
        foreach (array($linkOwnText, $nearTail) as $scopeText) {
            if ($scopeText === '') {
                continue;
            }
            foreach ($quarterPatterns as $pk => $rx) {
                if (preg_match($rx, $scopeText)) {
                    $perKey = $pk;
                    break 2;
                }
            }
            foreach ($monthPatterns as $pk => $rx) {
                if (preg_match($rx, $scopeText)) {
                    $perKey = $pk;
                    break 2;
                }
            }
        }
        if ($perKey === '' || !isset($periodsMap[$perKey])) {
            $perKey = $defaultPer;
        }
        $perOrder = (int) ($periodsMap[$perKey]['order'] ?? 1);

        $detectedSub = '';
        foreach (array($linkOwnText, $nearTail) as $scopeText) {
            if ($scopeText === '') {
                continue;
            }
            foreach ($subGroups as $sg) {
                $sgNorm = preg_quote((string) $sg, '/');
                $sgFlex = str_replace('\ ', '\s*', $sgNorm);
                if (preg_match('/' . $sgFlex . '/i', $scopeText)) {
                    $detectedSub = (string) $sg;
                    break 2;
                }
            }
            if (preg_match('/(?:dipa\s*0?1|\bbua\b)/i', $scopeText)) {
                foreach ($subGroups as $sg) {
                    if (strpos((string) $sg, '01') !== false) {
                        $detectedSub = (string) $sg;
                        break 2;
                    }
                }
            } elseif (preg_match('/(?:dipa\s*0?4|badilag)/i', $scopeText)) {
                foreach ($subGroups as $sg) {
                    if (strpos((string) $sg, '04') !== false) {
                        $detectedSub = (string) $sg;
                        break 2;
                    }
                }
            }
        }
        if ($detectedSub === '') {
            $detectedSub = $fallbackSub;
        }

        $isLocalPdf = (stripos($cleanUrl, home_url()) === 0 && preg_match('/\.pdf(?:$|\?)/i', $cleanUrl));
        $sourceType = $isLocalPdf ? 'wp_pdf' : 'external';

        $isGeneric  = ($anchorText === '' || preg_match('/^(?:download|unduh|klik(?:\s+di\s*sini)?|lihat|buka|pdf|file|dokumen)$/i', $anchorText));
        $trimmedTxt = function_exists('mb_substr') ? mb_substr($anchorText, 0, 180) : substr($anchorText, 0, 180);
        $title      = $isGeneric
            ? trim($detectedSub . ' ' . ($periodsMap[$perKey]['label'] ?? $perKey) . ' ' . $yr)
            : $trimmedTxt;

        $isDocHost  = (bool) preg_match('/(?:\.(?:pdf|docx?|xlsx?|zip)(?:$|\?)|drive\.google\.com|docs\.google\.com|\/uploads\/|\/images\/|download)/i', $cleanUrl);
        $suggested  = $isDocHost || $perKey !== $defaultPer;

        $results[] = array(
            'year'         => $yr,
            'sub_group'    => $detectedSub,
            'period_key'   => $perKey,
            'period_order' => $perOrder,
            'title'        => $title,
            'source_type'  => $sourceType,
            'doc_url'      => $cleanUrl,
            'anchor_text'  => $anchorText,
            'suggested'    => $suggested,
        );
    }

    return $results;
}

add_action('admin_menu', static function () {
    add_menu_page(
        'SINOVA Hub (Kepatuhan SK 2722/2026)',
        'SINOVA Hub',
        'pa_access_sinova_hub',
        'pa-sinova-starter',
        'pa_sinova_render_admin_page',
        'dashicons-awards',
        3
    );
});

/**
 * Menghasilkan matriks & SVG QR Code ISO/IEC 18004 (Version 5-L, <=106 byte, 1 blok RS) secara lokal.
 */
function pa_sinova_totp_qr_svg(string $payload): string {
    $bytes = array_values(unpack('C*', $payload));
    $len   = count($bytes);
    if ($len === 0 || $len > 106) {
        return '';
    }
    $size = 37; // Version 5

    // 1. Bitstream: Mode 0100 (Byte), 8-bit count, data, terminator, padding 0xEC/0x11 hingga 108 byte
    $bits = array(0, 1, 0, 0);
    for ($i = 7; $i >= 0; $i--) {
        $bits[] = ($len >> $i) & 1;
    }
    foreach ($bytes as $b) {
        for ($i = 7; $i >= 0; $i--) {
            $bits[] = ($b >> $i) & 1;
        }
    }
    $maxBits = 108 * 8;
    for ($i = 0; $i < 4 && count($bits) < $maxBits; $i++) {
        $bits[] = 0;
    }
    while (count($bits) % 8 !== 0) {
        $bits[] = 0;
    }
    $padBytes = array(0xEC, 0x11);
    $pIdx     = 0;
    while (count($bits) < $maxBits) {
        $pb = $padBytes[$pIdx % 2];
        $pIdx++;
        for ($i = 7; $i >= 0; $i--) {
            $bits[] = ($pb >> $i) & 1;
        }
    }
    $dataCw = array();
    for ($i = 0; $i < 108; $i++) {
        $v = 0;
        for ($j = 0; $j < 8; $j++) {
            $v = ($v << 1) | $bits[$i * 8 + $j];
        }
        $dataCw[] = $v;
    }

    // 2. GF(256) poly 0x11D Reed-Solomon (26 EC codewords)
    $exp = array_fill(0, 512, 0);
    $log = array_fill(0, 256, 0);
    $x   = 1;
    for ($i = 0; $i < 255; $i++) {
        $exp[$i]     = $x;
        $exp[$i + 255] = $x;
        $log[$x]     = $i;
        $x <<= 1;
        if ($x & 0x100) {
            $x ^= 0x11D;
        }
    }
    $gfMul = static function (int $a, int $b) use ($exp, $log): int {
        return ($a === 0 || $b === 0) ? 0 : $exp[$log[$a] + $log[$b]];
    };
    $ecLen = 26;
    $gen   = array(1);
    for ($i = 0; $i < $ecLen; $i++) {
        $next = array_fill(0, count($gen) + 1, 0);
        $a    = $exp[$i];
        foreach ($gen as $idx => $coef) {
            $next[$idx]     ^= $coef;
            $next[$idx + 1] ^= $gfMul($coef, $a);
        }
        $gen = $next;
    }
    $msg = array_merge($dataCw, array_fill(0, $ecLen, 0));
    for ($i = 0; $i < 108; $i++) {
        $coef = $msg[$i];
        if ($coef !== 0) {
            for ($j = 1; $j <= $ecLen; $j++) {
                $msg[$i + $j] ^= $gfMul($gen[$j], $coef);
            }
        }
    }
    $allCw = array_merge($dataCw, array_slice($msg, 108));
    $stream = array();
    foreach ($allCw as $cw) {
        for ($i = 7; $i >= 0; $i--) {
            $stream[] = ($cw >> $i) & 1;
        }
    }
    for ($i = 0; $i < 7; $i++) {
        $stream[] = 0; // Version 5 remainder bits
    }

    // 3. Matriks modul & pola fungsi
    $grid = array_fill(0, $size, array_fill(0, $size, null));
    $func = array_fill(0, $size, array_fill(0, $size, false));
    $setFunc = static function (int $r, int $c, bool $dark) use (&$grid, &$func, $size): void {
        if ($r >= 0 && $c >= 0 && $r < $size && $c < $size) {
            $grid[$r][$c] = $dark;
            $func[$r][$c] = true;
        }
    };
    $drawFinder = static function (int $top, int $left) use ($setFunc): void {
        for ($dr = -1; $dr <= 7; $dr++) {
            for ($dc = -1; $dc <= 7; $dc++) {
                $dark = ($dr >= 0 && $dr <= 6 && $dc >= 0 && $dc <= 6)
                    && ($dr === 0 || $dr === 6 || $dc === 0 || $dc === 6 || ($dr >= 2 && $dr <= 4 && $dc >= 2 && $dc <= 4));
                $setFunc($top + $dr, $left + $dc, $dark);
            }
        }
    };
    $drawFinder(0, 0);
    $drawFinder(0, $size - 7);
    $drawFinder($size - 7, 0);
    // Alignment (30, 30)
    for ($dr = -2; $dr <= 2; $dr++) {
        for ($dc = -2; $dc <= 2; $dc++) {
            $setFunc(30 + $dr, 30 + $dc, max(abs($dr), abs($dc)) !== 1);
        }
    }
    // Timing
    for ($i = 8; $i < $size - 8; $i++) {
        $setFunc(6, $i, $i % 2 === 0);
        $setFunc($i, 6, $i % 2 === 0);
    }
    // Dark module & reserve format info
    $setFunc($size - 8, 8, true);
    for ($i = 0; $i <= 8; $i++) {
        if ($i !== 6) {
            $setFunc(8, $i, false);
            $setFunc($i, 8, false);
        }
    }
    for ($i = $size - 8; $i < $size; $i++) {
        $setFunc(8, $i, false);
        if ($i > $size - 8) {
            $setFunc($i, 8, false);
        }
    }

    // 4. Penempatan data zig-zag
    $bitIdx = 0;
    $up     = true;
    for ($right = $size - 1; $right >= 1; $right -= 2) {
        if ($right === 6) {
            $right = 5;
        }
        for ($step = 0; $step < $size; $step++) {
            $r = $up ? ($size - 1 - $step) : $step;
            for ($dx = 0; $dx < 2; $dx++) {
                $c = $right - $dx;
                if (!$func[$r][$c]) {
                    $grid[$r][$c] = (bool) ($stream[$bitIdx++] ?? 0);
                }
            }
        }
        $up = !$up;
    }

    // 5. Terapkan mask 0 ((r+c)%2===0) + format info ECC Level L (01)
    for ($r = 0; $r < $size; $r++) {
        for ($c = 0; $c < $size; $c++) {
            if (!$func[$r][$c] && (($r + $c) % 2 === 0)) {
                $grid[$r][$c] = !$grid[$r][$c];
            }
        }
    }
    $fmtData = (0b01 << 3) | 0; // ECC L, Mask 0
    $rem     = $fmtData << 10;
    for ($i = 4; $i >= 0; $i--) {
        if (($rem >> ($i + 10)) & 1) {
            $rem ^= (0x537 << $i);
        }
    }
    $fmt = (($fmtData << 10) | $rem) ^ 0x5412;
    for ($i = 0; $i < 15; $i++) {
        $b = (bool) (($fmt >> $i) & 1);
        if ($i < 6) $grid[8][$i] = $b;
        elseif ($i < 8) $grid[8][$i + 1] = $b;
        else $grid[8][$size - 15 + $i] = $b;

        if ($i < 8) $grid[$size - 1 - $i][8] = $b;
        elseif ($i < 9) $grid[7][8] = $b;
        else $grid[14 - $i][8] = $b;
    }

    $paths = '';
    for ($r = 0; $r < $size; $r++) {
        for ($c = 0; $c < $size; $c++) {
            if (!empty($grid[$r][$c])) {
                $paths .= 'M' . ($c + 2) . ',' . ($r + 2) . 'h1v1h-1z';
            }
        }
    }
    $vb = $size + 4;
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $vb . ' ' . $vb . '" width="180" height="180" shape-rendering="crispEdges" role="img" aria-label="Kode QR Autentikator TOTP"><rect width="' . $vb . '" height="' . $vb . '" fill="#ffffff"/><path d="' . $paths . '" fill="#0f172a"/></svg>';
}

function pa_sinova_render_admin_page(): void {
    if (!current_user_can('pa_access_sinova_hub')) {
        return;
    }

    $allowedTabs = pa_sinova_user_allowed_tabs();
    if (empty($allowedTabs)) {
        $allowedTabs = array('compliance');
    }

    $notice          = '';
    $mfaRecoveryOnce = array();
    $scannedLinks    = array();
    $scanPostId      = 0;
    $scanTargetSlug  = 'lra';
    $scanDefaultYear = (int) wp_date('Y');
    $activeTab       = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : $allowedTabs[0];
    if (!in_array($activeTab, $allowedTabs, true)) {
        $activeTab = $allowedTabs[0];
    }

    $requireAdmin = static function () use (&$notice): bool {
        if (!current_user_can('manage_options')) {
            $notice = 'Gagal: Aksi ini hanya diizinkan untuk Administrator Satker.';
            return false;
        }
        return true;
    };

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pa_sinova_action'])) {
        check_admin_referer('pa_sinova_starter_nonce');
        $action = sanitize_key((string) $_POST['pa_sinova_action']);

        if ($action === 'save_settings' && $requireAdmin()) {
            $activeTab = 'settings';
            $cur = pa_sinova_get_settings();
            $new = array(
                'satker_name'              => sanitize_text_field(wp_unslash((string) ($_POST['satker_name'] ?? $cur['satker_name']))),
                'logo_url'                 => esc_url_raw(wp_unslash((string) ($_POST['logo_url'] ?? ''))),
                'preserve_existing_design' => isset($_POST['preserve_existing_design']) ? (!empty($_POST['preserve_existing_design']) ? '1' : '0') : ((string) ($cur['preserve_existing_design'] ?? '0')),
                'address'                  => sanitize_text_field(wp_unslash((string) ($_POST['address'] ?? $cur['address']))),
                'phone'                => sanitize_text_field(wp_unslash((string) ($_POST['phone'] ?? $cur['phone']))),
                'email'                => sanitize_email(wp_unslash((string) ($_POST['email'] ?? $cur['email']))),
                'hearing_hours'        => sanitize_text_field(wp_unslash((string) ($_POST['hearing_hours'] ?? $cur['hearing_hours']))),
                'leader_name'          => sanitize_text_field(wp_unslash((string) ($_POST['leader_name'] ?? $cur['leader_name']))),
                'leader_welcome'       => sanitize_textarea_field(wp_unslash((string) ($_POST['leader_welcome'] ?? $cur['leader_welcome']))),
                'sipp_url'             => esc_url_raw(wp_unslash((string) ($_POST['sipp_url'] ?? ''))),
                'ecourt_url'           => esc_url_raw(wp_unslash((string) ($_POST['ecourt_url'] ?? ''))),
                'direktori_url'        => esc_url_raw(wp_unslash((string) ($_POST['direktori_url'] ?? ''))),
                'survey_url'           => esc_url_raw(wp_unslash((string) ($_POST['survey_url'] ?? ''))),
                'aco_url'              => esc_url_raw(wp_unslash((string) ($_POST['aco_url'] ?? ($cur['aco_url'] ?? '')))),
                'rss_ma_url'           => esc_url_raw(wp_unslash((string) ($_POST['rss_ma_url'] ?? ($cur['rss_ma_url'] ?? '')))),
                'rss_badilag_url'      => esc_url_raw(wp_unslash((string) ($_POST['rss_badilag_url'] ?? ($cur['rss_badilag_url'] ?? '')))),
                'enforce_mfa'          => (string) ($cur['enforce_mfa'] ?? '0'),
                'custom_login_slug'    => (string) ($cur['custom_login_slug'] ?? ''),
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
            $notice = 'Pengaturan Satker, Umpan RSS, & API HMAC berhasil disimpan.';
        } elseif ($action === 'run_seeder' && $requireAdmin()) {
            $activeTab = 'settings';
            $res = pa_sinova_run_starter_seeder();
            if (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }
            $notice = !empty($res['preserve_mode'])
                ? sprintf(
                    'Seeder aman selesai: %d halaman kurang dibuat (%d total halaman terpetakan). Tema, menu, & beranda lama tetap dipertahankan.',
                    $res['pages_created'],
                    $res['total_mapped']
                )
                : sprintf(
                    'Seeder selesai: %d halaman baru dibuat (%d total halaman terpetakan), %d item menu ditambahkan.',
                    $res['pages_created'],
                    $res['total_mapped'],
                    $res['menu_items']
                );
        } elseif ($action === 'rebuild_6menu' && $requireAdmin()) {
            $activeTab = 'settings';
            $res = pa_sinova_run_starter_seeder(false, true);
            if (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }
            $notice = sprintf('Struktur 6 menu utama standar berhasil disusun ulang (%d item menu terbit).', $res['menu_items']);
        } elseif ($action === 'refresh_rss' && in_array('data', $allowedTabs, true)) {
            $activeTab = 'data';
            if (function_exists('pa_portal_refresh_official_feeds')) {
                $feedData = pa_portal_refresh_official_feeds();
                $maCnt    = count((array) ($feedData['ma'] ?? array()));
                $bdCnt    = count((array) ($feedData['badilag'] ?? array()));
                $notice   = "Sinkronisasi RSS resmi selesai: MA RI ({$maCnt} item) dan Ditjen Badilag ({$bdCnt} item).";
            } else {
                $notice = 'Gagal: Modul umpan RSS belum dimuat.';
            }
        } elseif ($action === 'add_record' && in_array('data', $allowedTabs, true)) {
            $activeTab = 'data';
            if (function_exists('pa_portal_records_ensure_table') && function_exists('pa_portal_records_table')) {
                pa_portal_records_ensure_table();
                global $wpdb;
                $recType = sanitize_key((string) ($_POST['rec_type'] ?? 'jadwal'));
                if (!in_array($recType, array('pegawai', 'jadwal', 'ghaib', 'statistik'), true)) {
                    $recType = 'jadwal';
                }
                $recTitle = sanitize_text_field(wp_unslash((string) ($_POST['rec_title'] ?? '')));
                $recSub   = sanitize_text_field(wp_unslash((string) ($_POST['rec_subtitle'] ?? '')));
                $recCat   = sanitize_text_field(wp_unslash((string) ($_POST['rec_category'] ?? '')));
                $recM1    = sanitize_text_field(wp_unslash((string) ($_POST['rec_meta_1'] ?? '')));
                $recM2    = sanitize_text_field(wp_unslash((string) ($_POST['rec_meta_2'] ?? '')));
                $recM3    = sanitize_text_field(wp_unslash((string) ($_POST['rec_meta_3'] ?? '')));
                $recDate  = sanitize_text_field(wp_unslash((string) ($_POST['rec_date'] ?? '')));
                $recOrder = (int) ($_POST['rec_sort_order'] ?? 0);
                if ($recTitle === '') {
                    $notice = 'Gagal: Judul / nama entri wajib diisi.';
                } else {
                    if ($recType === 'pegawai') {
                        $metaArr = array('nip' => $recM1, 'gol' => $recM2, 'edu' => $recM3);
                    } elseif ($recType === 'ghaib') {
                        $metaArr = array('pihak' => ($recM1 !== '' ? $recM1 : $recSub), 'doc_url' => esc_url_raw($recM2, array('http', 'https')));
                    } elseif ($recType === 'statistik') {
                        $metaArr = array('diterima' => (int) $recM1, 'diputus' => (int) $recM2, 'sisa' => (int) $recM3);
                    } else {
                        $metaArr = array('jam' => ($recM1 !== '' ? $recM1 : '09.00 WIB'));
                    }
                    $metaJson = wp_json_encode($metaArr);
                    $now      = current_time('mysql');
                    $wpdb->insert(pa_portal_records_table(), array(
                        'record_type' => $recType,
                        'title'       => $recTitle,
                        'subtitle'    => $recSub,
                        'category'    => $recCat,
                        'event_date'  => preg_match('/^\d{4}-\d{2}-\d{2}$/', $recDate) ? $recDate : null,
                        'meta_json'   => $metaJson ?: '{}',
                        'sort_order'  => $recOrder,
                        'status'      => 'published',
                        'updated_at'  => $now,
                    ));
                    if (function_exists('pa_home_page_cache_mark_stale')) {
                        pa_home_page_cache_mark_stale();
                    }
                    $notice = "Entri Data Operasional ({$recType}) berhasil ditambahkan.";
                }
            }
        } elseif ($action === 'delete_record' && in_array('data', $allowedTabs, true)) {
            $activeTab = 'data';
            if (function_exists('pa_portal_records_table')) {
                global $wpdb;
                $rid = (int) ($_POST['rec_id'] ?? 0);
                if ($rid > 0) {
                    $wpdb->delete(pa_portal_records_table(), array('id' => $rid));
                    $notice = 'Entri data operasional berhasil dihapus.';
                }
            }
        } elseif ($action === 'mfa_setup_start' && in_array('security', $allowedTabs, true)) {
            $activeTab = 'security';
            if (function_exists('pa_starter_totp_generate_secret')) {
                $uid = get_current_user_id();
                $pending = pa_starter_totp_generate_secret(20);
                update_user_meta($uid, 'pa_totp_pending_secret', $pending);
                $notice = 'Kunci TOTP baru dibuat. Pindai kode QR pada aplikasi autentikator lalu masukkan kode 6 digit untuk mengaktifkan.';
            }
        } elseif ($action === 'mfa_activate' && in_array('security', $allowedTabs, true)) {
            $activeTab = 'security';
            if (function_exists('pa_starter_totp_verify') && function_exists('pa_starter_totp_generate_recovery_codes')) {
                $uid     = get_current_user_id();
                $pending = (string) get_user_meta($uid, 'pa_totp_pending_secret', true);
                $otp     = trim((string) ($_POST['mfa_verify_code'] ?? ''));
                if ($pending === '' || !pa_starter_totp_verify($pending, $otp, 1, $uid)) {
                    $notice = 'Gagal: Kode verifikasi 6 digit tidak sesuai atau kedaluwarsa. Pastikan jam perangkat akurat.';
                } else {
                    $rec = pa_starter_totp_generate_recovery_codes(8);
                    update_user_meta($uid, 'pa_totp_secret', $pending);
                    update_user_meta($uid, 'pa_totp_enabled', '1');
                    update_user_meta($uid, 'pa_totp_recovery_hashes', $rec['hashes']);
                    delete_user_meta($uid, 'pa_totp_pending_secret');
                    $mfaRecoveryOnce = $rec['plain'];
                    $notice = 'Autentikasi Dua Faktor (MFA TOTP) berhasil diaktifkan pada akun Anda. Simpan 8 kode cadangan di tempat aman.';
                }
            }
        } elseif ($action === 'mfa_disable' && in_array('security', $allowedTabs, true)) {
            $activeTab = 'security';
            $uid = get_current_user_id();
            delete_user_meta($uid, 'pa_totp_secret');
            delete_user_meta($uid, 'pa_totp_enabled');
            delete_user_meta($uid, 'pa_totp_recovery_hashes');
            delete_user_meta($uid, 'pa_totp_pending_secret');
            $notice = 'Autentikasi Dua Faktor (MFA TOTP) pada akun Anda telah dinonaktifkan.';
        } elseif ($action === 'mfa_policy' && $requireAdmin()) {
            $activeTab = 'security';
            $stored = get_option('pa_sinova_starter_settings', array());
            if (!is_array($stored)) {
                $stored = array();
            }
            $stored['enforce_mfa'] = !empty($_POST['enforce_mfa']) ? '1' : '0';
            update_option('pa_sinova_starter_settings', $stored, false);
            $notice = $stored['enforce_mfa'] === '1'
                ? 'Kebijakan kewajiban MFA TOTP diaktifkan (menampilkan pengingat wajib kepada operator yang belum mengaktifkan 2FA).'
                : 'Kebijakan pengingat wajib MFA TOTP dinonaktifkan.';
        } elseif (($action === 'save_adoption_mode' || $action === 'save_and_seed_safe') && $requireAdmin()) {
            $activeTab = 'migration';
            $stored    = get_option('pa_sinova_starter_settings', array());
            if (!is_array($stored)) {
                $stored = array();
            }
            $stored['preserve_existing_design'] = !empty($_POST['preserve_existing_design']) ? '1' : '0';
            update_option('pa_sinova_starter_settings', $stored, false);
            if ($action === 'save_and_seed_safe') {
                $res = pa_sinova_run_starter_seeder(true);
                $notice = sprintf(
                    'Mode Adopsi Non-Destruktif disimpan (%s). Seeder aman selesai: %d halaman kurang dibuat (%d total terpetakan). Tema, menu, & beranda lama tetap dipertahankan.',
                    $stored['preserve_existing_design'] === '1' ? 'AKTIF' : 'NONAKTIF',
                    $res['pages_created'],
                    $res['total_mapped']
                );
            } else {
                $notice = $stored['preserve_existing_design'] === '1'
                    ? 'Mode Adopsi Situs Lama DIAKTIFKAN: tema, menu utama, & halaman beranda eksisting tetap dipertahankan.'
                    : 'Mode Adopsi Situs Lama DINONAKTIFKAN: tata letak header dan beranda standar paket aktif kembali.';
            }
            if (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }
        } elseif ($action === 'save_custom_map' && $requireAdmin()) {
            $activeTab = 'migration';
            $postIds   = isset($_POST['map_post_id']) && is_array($_POST['map_post_id']) ? $_POST['map_post_id'] : array();
            $customUrl = isset($_POST['map_custom_url']) && is_array($_POST['map_custom_url']) ? $_POST['map_custom_url'] : array();
            $newMap    = array();
            foreach (array_unique(array_merge(array_keys($postIds), array_keys($customUrl))) as $rawCode) {
                $code = strtoupper(preg_replace('/[^A-Za-z0-9.]/', '', (string) $rawCode));
                if ($code === '') {
                    continue;
                }
                $pid = isset($postIds[$rawCode]) ? max(0, (int) $postIds[$rawCode]) : 0;
                $url = isset($customUrl[$rawCode]) ? esc_url_raw(trim(wp_unslash((string) $customUrl[$rawCode])), array('http', 'https')) : '';
                if ($pid > 0 || $url !== '') {
                    $newMap[$code] = array(
                        'post_id'    => $pid,
                        'custom_url' => $url,
                    );
                }
            }
            update_option('pa_sinova_custom_page_map', $newMap, false);
            if (function_exists('pa_home_page_cache_mark_stale')) {
                pa_home_page_cache_mark_stale();
            }
            $notice = sprintf('Pemetaan kustom %d kriteria situs lama berhasil disimpan.', count($newMap));
        } elseif ($action === 'scan_links' && $requireAdmin()) {
            $activeTab       = 'migration';
            $scanPostId      = max(0, (int) ($_POST['scan_post_id'] ?? 0));
            $scanTargetSlug  = sanitize_key((string) ($_POST['scan_target_slug'] ?? 'lra'));
            $scanDefaultYear = max(2017, min(2027, (int) ($_POST['scan_default_year'] ?? wp_date('Y'))));
            if ($scanPostId > 0) {
                $scannedLinks = pa_sinova_extract_links_from_post($scanPostId, $scanTargetSlug, $scanDefaultYear);
                $notice = sprintf('Pemindaian selesai: ditemukan %d tautan unik pada halaman/pos sumber.', count($scannedLinks));
            } else {
                $notice = 'Pilih halaman atau pos sumber terlebih dahulu untuk dipindai.';
            }
        } elseif ($action === 'import_links' && $requireAdmin() && function_exists('pa_sinova_ensure_table')) {
            $activeTab = 'migration';
            pa_sinova_ensure_table();
            global $wpdb;
            $table      = pa_sinova_table_name();
            $periodsMap = pa_sinova_all_periods_map();
            $catalogMap = function_exists('pa_sinova_module_catalog') ? pa_sinova_module_catalog() : array();
            $targetSlug = sanitize_key((string) ($_POST['import_target_slug'] ?? 'lra'));
            if (!isset($catalogMap[$targetSlug])) {
                $targetSlug = 'lra';
            }
            $selectedIdx = isset($_POST['import_rows']) && is_array($_POST['import_rows']) ? $_POST['import_rows'] : array();
            $years       = isset($_POST['row_year']) && is_array($_POST['row_year']) ? $_POST['row_year'] : array();
            $subs        = isset($_POST['row_sub']) && is_array($_POST['row_sub']) ? $_POST['row_sub'] : array();
            $pers        = isset($_POST['row_period']) && is_array($_POST['row_period']) ? $_POST['row_period'] : array();
            $titles      = isset($_POST['row_title']) && is_array($_POST['row_title']) ? $_POST['row_title'] : array();
            $urls        = isset($_POST['row_url']) && is_array($_POST['row_url']) ? $_POST['row_url'] : array();
            $srcs        = isset($_POST['row_src']) && is_array($_POST['row_src']) ? $_POST['row_src'] : array();
            $importedCnt = 0;
            $now         = current_time('mysql');
            $author      = wp_get_current_user()->display_name ?: 'WP Admin (Migrasi)';

            foreach ($selectedIdx as $rawIdx) {
                $idx      = (int) $rawIdx;
                $rawUrl   = isset($urls[$idx]) ? trim(wp_unslash((string) $urls[$idx])) : '';
                $cleanUrl = esc_url_raw($rawUrl, array('http', 'https'));
                if ($cleanUrl === '' || !preg_match('#^https?://#i', $cleanUrl)) {
                    continue;
                }
                $yr  = max(2017, min(2035, (int) ($years[$idx] ?? wp_date('Y'))));
                $sub = sanitize_text_field(wp_unslash((string) ($subs[$idx] ?? 'DOKUMEN')));
                if ($sub === '') {
                    $sub = 'DOKUMEN';
                }
                $per = strtoupper(sanitize_key((string) ($pers[$idx] ?? 'TAHUNAN')));
                if (!isset($periodsMap[$per])) {
                    $per = 'TAHUNAN';
                }
                $ord = (int) ($periodsMap[$per]['order'] ?? 1);
                $ttl = sanitize_text_field(wp_unslash((string) ($titles[$idx] ?? '')));
                if ($ttl === '') {
                    $ttl = trim("{$sub} " . ($periodsMap[$per]['label'] ?? $per) . " {$yr}");
                }
                $src = (isset($srcs[$idx]) && $srcs[$idx] === 'wp_pdf') ? 'wp_pdf' : 'external';

                $existingId = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM `{$table}` WHERE page_slug = %s AND year = %d AND sub_group = %s AND period_key = %s",
                    $targetSlug, $yr, $sub, $per
                ));
                $data = array(
                    'page_slug'    => $targetSlug,
                    'year'         => $yr,
                    'sub_group'    => $sub,
                    'period_key'   => $per,
                    'period_order' => $ord,
                    'title'        => $ttl,
                    'source_type'  => $src,
                    'doc_url'      => $cleanUrl,
                    'file_size_kb' => null,
                    'updated_by'   => $author,
                    'status'       => 'published',
                    'updated_at'   => $now,
                );
                if ($existingId > 0) {
                    $wpdb->update($table, $data, array('id' => $existingId));
                } else {
                    $data['created_at'] = $now;
                    $wpdb->insert($table, $data);
                }
                $importedCnt++;
            }
            if ($importedCnt > 0) {
                pa_sinova_flush_caches_for_slug($targetSlug);
                $notice = sprintf('%d dokumen berhasil diimpor ke modul %s dan langsung tersinkron ke Dashboard 35 Kriteria.', $importedCnt, $targetSlug);
            } else {
                $notice = 'Tidak ada baris tautan valid yang dipilih untuk diimpor.';
            }
        } elseif ($action === 'upload_doc' && in_array('upload', $allowedTabs, true) && function_exists('pa_sinova_ensure_table')) {
            $activeTab = 'upload';
            pa_sinova_ensure_table();
            global $wpdb;
            $table        = pa_sinova_table_name();
            $periods      = pa_sinova_all_periods_map();
            $slug         = sanitize_key((string) ($_POST['doc_slug'] ?? 'lra'));
            $allowedSlugs = pa_sinova_user_allowed_module_slugs();
            if (!empty($allowedSlugs) && !in_array($slug, $allowedSlugs, true)) {
                $notice = 'Gagal: Peran akun Anda tidak berwenang mengunggah ke modul "' . esc_html($slug) . '".';
            } else {
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
            }
        } elseif ($action === 'delete_doc' && in_array('upload', $allowedTabs, true) && !empty($_POST['doc_id'])) {
            $activeTab = 'upload';
            global $wpdb;
            $table        = pa_sinova_table_name();
            $did          = (int) $_POST['doc_id'];
            $row          = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE id = %d", $did), ARRAY_A);
            $allowedSlugs = pa_sinova_user_allowed_module_slugs();
            if ($row && (!empty($allowedSlugs) && !in_array((string) $row['page_slug'], $allowedSlugs, true))) {
                $notice = 'Gagal: Peran akun Anda tidak berwenang menghapus dokumen modul tersebut.';
            } elseif ($row) {
                if (($row['source_type'] ?? '') === 'wp_pdf' && function_exists('pa_sinova_safe_unlink_local_pdf')) {
                    pa_sinova_safe_unlink_local_pdf((string) $row['doc_url']);
                }
                $wpdb->delete($table, array('id' => $did));
                pa_sinova_flush_caches_for_slug((string) $row['page_slug']);
                $notice = 'Dokumen berhasil dihapus dari arsip.';
            }
        } elseif ($action === 'save_login_hide' && $requireAdmin()) {
            $activeTab = 'security';
            $rawSlug   = wp_unslash((string) ($_POST['custom_login_slug'] ?? ''));
            $check     = pa_sinova_sanitize_login_slug($rawSlug);
            if (!$check['valid']) {
                $notice = 'Gagal: ' . $check['error'];
            } else {
                $stored = get_option('pa_sinova_starter_settings', array());
                if (!is_array($stored)) {
                    $stored = array();
                }
                $stored['custom_login_slug'] = $check['slug'];
                update_option('pa_sinova_starter_settings', $stored, false);
                if (function_exists('pa_home_page_cache_mark_stale')) {
                    pa_home_page_cache_mark_stale();
                }
                if ($check['slug'] !== '') {
                    $notice = 'Halaman login berhasil disembunyikan. URL masuk baru: ' . trailingslashit(home_url('/' . $check['slug']));
                } else {
                    $notice = 'Penyembunyian URL login dinonaktifkan; WordPress kembali memakai /wp-login.php standar.';
                }
            }
        }
    }

    $cfg          = pa_sinova_get_settings();
    $catalog      = function_exists('pa_sinova_module_catalog') ? pa_sinova_module_catalog() : array();
    $allowedSlugs = pa_sinova_user_allowed_module_slugs();
    $uploadCatalog = empty($allowedSlugs)
        ? $catalog
        : array_intersect_key($catalog, array_flip($allowedSlugs));
    if (empty($uploadCatalog)) {
        $uploadCatalog = $catalog;
    }
    $periods    = function_exists('pa_sinova_all_periods_map') ? pa_sinova_all_periods_map() : array();
    $report     = pa_sinova_wp_compliance_report();
    $uploadKeys = array_keys($uploadCatalog);
    $preSlug    = isset($_GET['doc_slug']) ? sanitize_key((string) $_GET['doc_slug']) : ($uploadKeys[0] ?? 'lra');
    if (!isset($uploadCatalog[$preSlug]) && !empty($uploadKeys)) {
        $preSlug = $uploadKeys[0];
    }
    $tabBaseUrl = admin_url('admin.php?page=pa-sinova-starter&tab=');
    $tabLabels  = array(
        'compliance' => 'Dashboard 35 Kriteria (' . $report['total'] . '%)',
        'upload'     => 'Upload &amp; Kelola Dokumen',
        'data'       => 'Data Operasional &amp; RSS',
        'migration'  => 'Migrasi &amp; Adopsi',
        'settings'   => 'Identitas Satker &amp; Seeder',
        'security'   => 'Keamanan, MFA &amp; Audit',
    );
    ?>
    <div class="wrap">
        <h1 style="color:#056e4f;font-weight:800;">SINOVA Hub — Panel Backend &amp; Kepatuhan (SK Dirjen Badilag 2722/2026)</h1>
        <p style="color:#475569;margin-top:2px;">Halaman backend khusus operator untuk memantau kesiapan 35 kriteria, mengunggah arsip PDF/Drive, serta mengelola data operasional &amp; keamanan. Istilah <em>SINOVA</em> tidak ditampilkan di halaman publik masyarakat.</p>

        <?php if ($notice !== '') :
            $noticeClass = (stripos($notice, 'Gagal:') === 0) ? 'notice-error' : 'notice-success';
        ?>
            <div class="notice <?php echo esc_attr($noticeClass); ?> is-dismissible"><p><strong><?php echo esc_html($notice); ?></strong></p></div>
        <?php endif; ?>

        <h2 class="nav-tab-wrapper" style="margin:14px 0 18px;">
            <?php foreach ($tabLabels as $tKey => $tLabel) :
                if (!in_array($tKey, $allowedTabs, true)) {
                    continue;
                }
            ?>
                <a href="<?php echo esc_url($tabBaseUrl . $tKey); ?>" class="nav-tab <?php echo $activeTab === $tKey ? 'nav-tab-active' : ''; ?>"><?php echo $tLabel; ?></a>
            <?php endforeach; ?>
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
            if (!empty($allowedSlugs)) {
                $placeholders = implode(',', array_fill(0, count($allowedSlugs), '%s'));
                $recentDocs   = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$table}` WHERE page_slug IN ({$placeholders}) ORDER BY updated_at DESC, id DESC LIMIT 25", ...$allowedSlugs), ARRAY_A);
            } else {
                $recentDocs = $wpdb->get_results("SELECT * FROM `{$table}` ORDER BY updated_at DESC, id DESC LIMIT 25", ARRAY_A);
            }
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
                                    <?php foreach ($uploadCatalog as $slug => $m) : ?>
                                        <option value="<?php echo esc_attr($slug); ?>" data-sub="<?php echo esc_attr((string) ($m['sub_groups'][0] ?? 'DOKUMEN')); ?>" <?php selected($preSlug, $slug); ?>><?php echo esc_html($m['title'] . ' (' . $slug . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td></tr>
                            <tr><th>Tahun</th><td><input type="number" name="doc_year" min="2017" max="2027" value="<?php echo esc_attr((string) date('Y')); ?>"></td></tr>
                            <tr><th>Sub-Kelompok</th><td><input type="text" name="doc_subgroup" id="pa-doc-subgroup-input" class="regular-text" value="<?php echo esc_attr((string) ($uploadCatalog[$preSlug]['sub_groups'][0] ?? 'SERAPAN 01')); ?>"></td></tr>
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

        <?php elseif ($activeTab === 'data') :
            if (function_exists('pa_portal_records_ensure_table')) {
                pa_portal_records_ensure_table();
            }
            global $wpdb;
            $rTable   = function_exists('pa_portal_records_table') ? pa_portal_records_table() : ($wpdb->prefix . 'pa_portal_records');
            $recTypes = array(
                'jadwal'    => 'Jadwal Sidang Harian',
                'pegawai'   => 'Profil Hakim & Pegawai',
                'ghaib'     => 'Panggilan Ghaib (Pasal 27)',
                'statistik' => 'Statistik Perkara Bulanan',
            );
            $filterRecType = isset($_GET['rec_type']) ? sanitize_key((string) $_GET['rec_type']) : 'all';
            if ($filterRecType !== 'all' && !isset($recTypes[$filterRecType])) {
                $filterRecType = 'all';
            }
            $recRows = ($filterRecType !== 'all')
                ? $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$rTable}` WHERE record_type = %s ORDER BY sort_order ASC, event_date DESC, id DESC LIMIT 60", $filterRecType), ARRAY_A)
                : $wpdb->get_results("SELECT * FROM `{$rTable}` ORDER BY record_type ASC, sort_order ASC, id DESC LIMIT 80", ARRAY_A);
            $feedCache = function_exists('pa_portal_get_official_feeds') ? pa_portal_get_official_feeds(false) : array();
            $feedMaCnt = count((array) ($feedCache['ma'] ?? array()));
            $feedBdCnt = count((array) ($feedCache['badilag'] ?? array()));
            $feedUpd   = !empty($feedCache['updated_at']) ? wp_date('d M Y H:i', (int) $feedCache['updated_at']) . ' WIB' : 'Belum disinkronkan';
        ?>
            <div style="background:#fff;border:1px solid #cbd5e1;border-left:5px solid #056e4f;border-radius:10px;padding:14px 18px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <div>
                    <strong style="color:#056e4f;">Umpan RSS Resmi (MA RI &amp; Ditjen Badilag)</strong><br>
                    <small style="color:#475569;">
                        MA RI: <?php echo esc_html((string) $feedMaCnt); ?> pengumuman ·
                        Badilag: <?php echo esc_html((string) $feedBdCnt); ?> pengumuman ·
                        Terakhir disinkronkan: <?php echo esc_html($feedUpd); ?>
                    </small>
                </div>
                <form method="post">
                    <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                    <input type="hidden" name="pa_sinova_action" value="refresh_rss">
                    <button type="submit" class="button button-secondary">Sinkronkan RSS Resmi Sekarang</button>
                </form>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1.35fr;gap:20px;align-items:start;">
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">Tambah Data Operasional Terstruktur</h2>
                    <p style="font-size:12.5px;color:#475569;margin-top:0;">Entri langsung tampil pada halaman <code>/profil-pegawai/</code>, <code>/agenda-jadwal-persidangan/</code>, <code>/panggilan-ghaib/</code>, dan <code>/statistik-perkara/</code>.</p>
                    <form method="post">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="add_record">
                        <table class="form-table">
                            <tr><th>Jenis Data</th><td>
                                <select name="rec_type" class="regular-text">
                                    <?php foreach ($recTypes as $rk => $rl) : ?>
                                        <option value="<?php echo esc_attr($rk); ?>" <?php selected($filterRecType !== 'all' ? $filterRecType : 'jadwal', $rk); ?>><?php echo esc_html($rl); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td></tr>
                            <tr><th>Judul / Nama / No. Perkara</th><td><input type="text" name="rec_title" class="regular-text" required placeholder="Contoh: 1201/Pdt.G/2026/PA.Sm"></td></tr>
                            <tr><th>Subjudul / Jabatan / Agenda</th><td><input type="text" name="rec_subtitle" class="regular-text" placeholder="Contoh: Sidang Pertama / Ketua Pengadilan Agama"></td></tr>
                            <tr><th>Kategori / Ruang / Tahun</th><td><input type="text" name="rec_category" class="regular-text" placeholder="Contoh: Ruang Sidang 1 / Pimpinan / 2026"></td></tr>
                            <tr><th>Jam / NIP / Pihak / Diterima</th><td><input type="text" name="rec_meta_1" class="regular-text" placeholder="Contoh: 09.00 WIB atau NIP atau Nama Pihak atau 145"></td></tr>
                            <tr><th>Golongan / URL Relaas / Diputus</th><td><input type="text" name="rec_meta_2" class="regular-text" placeholder="Contoh: Pembina (IV/a) atau https://... atau 138"></td></tr>
                            <tr><th>Pendidikan / Sisa</th><td><input type="text" name="rec_meta_3" class="regular-text" placeholder="Contoh: S2 Hukum Islam atau 42"></td></tr>
                            <tr><th>Tanggal (Opsional)</th><td><input type="date" name="rec_date" value="<?php echo esc_attr(wp_date('Y-m-d')); ?>"></td></tr>
                            <tr><th>Urutan Tampil</th><td><input type="number" name="rec_sort_order" value="10" style="width:90px;"></td></tr>
                        </table>
                        <p><button type="submit" class="button button-primary">Simpan Data Operasional</button></p>
                    </form>
                </div>

                <div style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
                        <h2 style="margin:0;color:#056e4f;">Daftar Entri Terpublikasi (<?php echo count((array) $recRows); ?>)</h2>
                        <div>
                            <a href="<?php echo esc_url($tabBaseUrl . 'data&rec_type=all'); ?>" class="button button-small <?php echo $filterRecType === 'all' ? 'button-primary' : ''; ?>">Semua</a>
                            <?php foreach ($recTypes as $rk => $rl) : ?>
                                <a href="<?php echo esc_url($tabBaseUrl . 'data&rec_type=' . $rk); ?>" class="button button-small <?php echo $filterRecType === $rk ? 'button-primary' : ''; ?>"><?php echo esc_html(ucfirst($rk)); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <table class="widefat striped">
                        <thead><tr><th>Tipe</th><th>Judul / Entitas</th><th>Tanggal</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php if (empty($recRows)) : ?>
                                <tr><td colspan="4">Belum ada data operasional. Tambahkan melalui formulir di samping.</td></tr>
                            <?php else : foreach ($recRows as $rr) : ?>
                                <tr>
                                    <td><code><?php echo esc_html((string) $rr['record_type']); ?></code></td>
                                    <td><strong><?php echo esc_html((string) $rr['title']); ?></strong><br><small style="color:#64748b;"><?php echo esc_html((string) $rr['subtitle'] . (!empty($rr['category']) ? ' · ' . $rr['category'] : '')); ?></small></td>
                                    <td><?php echo esc_html((string) ($rr['event_date'] ?: '-')); ?></td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Hapus entri data ini?');">
                                            <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                                            <input type="hidden" name="pa_sinova_action" value="delete_record">
                                            <input type="hidden" name="rec_id" value="<?php echo esc_attr((string) $rr['id']); ?>">
                                            <button type="submit" class="button button-small" style="color:#b91c1c;">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($activeTab === 'migration') :
            $isAdoption     = (!empty($cfg['preserve_existing_design']) && (string) $cfg['preserve_existing_design'] === '1');
            $customMap      = pa_sinova_get_custom_map();
            $publishedPages = get_pages(array('post_status' => 'publish', 'sort_column' => 'post_title', 'sort_order' => 'ASC'));
            $sourcePosts    = get_posts(array(
                'post_type'      => array('page', 'post'),
                'post_status'    => 'publish',
                'posts_per_page' => 300,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ));
        ?>
            <div style="display:grid;grid-template-columns:1fr;gap:20px;">
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">1. Mode Adopsi Situs Lama (Non-Destruktif)</h2>
                    <p style="color:#475569;margin-top:0;">Cocok bagi Satker yang <strong>sudah memiliki website WordPress aktif</strong> atau baru saja mengimpor konten artikel dari <strong>Joomla</strong> dan ingin mempertahankan tema, susunan menu, serta halaman Beranda lama.</p>
                    <form method="post">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <label style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;margin-bottom:14px;">
                            <input type="checkbox" name="preserve_existing_design" value="1" <?php checked($isAdoption); ?> style="margin-top:3px;">
                            <span>
                                <strong>Pertahankan header, menu navigasi, dan beranda situs lama (Mode Non-Destruktif)</strong><br>
                                <small style="color:#475569;">Jika diaktifkan: header, navbar, dan tampilan depan situs lama Anda tidak disentuh. Hanya fitur backend (23 Modul Dokumen <code>[pa_sinova_docs]</code>, Dashboard 35 Kriteria, Keamanan WAF/Rate Limit, &amp; Cache) yang bekerja.</small>
                            </span>
                        </label>
                        <div style="display:flex;flex-wrap:wrap;gap:10px;">
                            <button type="submit" name="pa_sinova_action" value="save_adoption_mode" class="button button-secondary">Simpan Pengaturan Mode Adopsi</button>
                            <button type="submit" name="pa_sinova_action" value="save_and_seed_safe" class="button button-primary">Simpan Mode &amp; Jalankan Seeder Aman</button>
                        </div>
                    </form>
                </div>

                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">2. Ekstrak &amp; Impor Tautan Dokumen dari Halaman/Pos Lama</h2>
                    <p style="color:#475569;margin-top:0;">Pindai daftar tautan PDF atau Google Drive yang sudah tertulis pada halaman/berita lama (termasuk hasil migrasi Joomla) untuk dimasukkan langsung ke tabel modul arsip.</p>
                    <form method="post" style="display:grid;grid-template-columns:1.4fr 1.2fr 120px auto;gap:12px;align-items:end;margin-bottom:16px;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="scan_links">
                        <div>
                            <label style="display:block;font-weight:700;font-size:12px;margin-bottom:4px;">Halaman / Pos Sumber</label>
                            <select name="scan_post_id" style="width:100%;">
                                <option value="0">-- Pilih Halaman atau Pos --</option>
                                <?php foreach ($sourcePosts as $sp) : ?>
                                    <option value="<?php echo esc_attr((string) $sp->ID); ?>" <?php selected($scanPostId, (int) $sp->ID); ?>><?php echo esc_html('[' . strtoupper((string) $sp->post_type) . '] ' . ($sp->post_title ?: '(Tanpa Judul)')); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-weight:700;font-size:12px;margin-bottom:4px;">Modul Arsip Tujuan</label>
                            <select name="scan_target_slug" style="width:100%;">
                                <?php foreach ($catalog as $slug => $m) : ?>
                                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($scanTargetSlug, $slug); ?>><?php echo esc_html($m['title'] . ' (' . $slug . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-weight:700;font-size:12px;margin-bottom:4px;">Tahun Bawaan</label>
                            <input type="number" name="scan_default_year" min="2017" max="2027" value="<?php echo esc_attr((string) $scanDefaultYear); ?>" style="width:100%;">
                        </div>
                        <div>
                            <button type="submit" class="button button-primary">Pindai Tautan</button>
                        </div>
                    </form>

                    <?php if (!empty($scannedLinks)) : ?>
                        <form method="post">
                            <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                            <input type="hidden" name="pa_sinova_action" value="import_links">
                            <input type="hidden" name="import_target_slug" value="<?php echo esc_attr($scanTargetSlug); ?>">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                <strong>Pratinjau Hasil Pindai (<?php echo esc_html((string) count($scannedLinks)); ?> tautan ditemukan) &rarr; Modul: <code><?php echo esc_html($scanTargetSlug); ?></code></strong>
                                <button type="button" class="button button-small" onclick="document.querySelectorAll('.pa-mig-chk').forEach(function(c){c.checked=!c.checked;});">Balik Pilihan</button>
                            </div>
                            <table class="widefat striped" style="margin-bottom:12px;">
                                <thead>
                                    <tr>
                                        <th style="width:32px;">Pilih</th>
                                        <th style="width:82px;">Tahun</th>
                                        <th style="width:130px;">Sub-Kelompok</th>
                                        <th style="width:140px;">Periode</th>
                                        <th>Judul Dokumen &amp; URL Sumber</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($scannedLinks as $idx => $row) : ?>
                                        <tr>
                                            <td><input type="checkbox" class="pa-mig-chk" name="import_rows[]" value="<?php echo esc_attr((string) $idx); ?>" <?php checked(!empty($row['suggested'])); ?>></td>
                                            <td><input type="number" name="row_year[<?php echo esc_attr((string) $idx); ?>]" min="2017" max="2027" value="<?php echo esc_attr((string) $row['year']); ?>" style="width:74px;"></td>
                                            <td><input type="text" name="row_sub[<?php echo esc_attr((string) $idx); ?>]" value="<?php echo esc_attr($row['sub_group']); ?>" style="width:120px;"></td>
                                            <td>
                                                <select name="row_period[<?php echo esc_attr((string) $idx); ?>]" style="width:132px;">
                                                    <?php foreach ($periods as $pk => $pv) : ?>
                                                        <option value="<?php echo esc_attr($pk); ?>" <?php selected($row['period_key'], $pk); ?>><?php echo esc_html($pk . ' — ' . $pv['label']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="row_title[<?php echo esc_attr((string) $idx); ?>]" value="<?php echo esc_attr($row['title']); ?>" class="large-text" style="margin-bottom:4px;">
                                                <input type="hidden" name="row_url[<?php echo esc_attr((string) $idx); ?>]" value="<?php echo esc_attr($row['doc_url']); ?>">
                                                <input type="hidden" name="row_src[<?php echo esc_attr((string) $idx); ?>]" value="<?php echo esc_attr($row['source_type']); ?>">
                                                <small style="color:#475569;word-break:break-all;"><a href="<?php echo esc_url($row['doc_url']); ?>" target="_blank"><?php echo esc_html($row['doc_url']); ?></a></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button type="submit" class="button button-primary">Impor Terpilih ke Arsip Dokumen</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">3. Pemetaan Halaman Lama untuk 35 Kriteria</h2>
                    <p style="color:#475569;margin-top:0;">Jika halaman pada website lama memakai URL/slug berbeda (misal <code>/profil/visi-misi/</code> atau hasil migrasi Joomla), tautkan di sini agar Dashboard Kepatuhan &amp; tombol <strong>Salin URL</strong> mengarah ke halaman lama tanpa mengubah tautan publik.</p>
                    <form method="post">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="save_custom_map">
                        <table class="widefat striped" style="margin-bottom:14px;">
                            <thead>
                                <tr>
                                    <th style="width:65px;">Kode</th>
                                    <th>Kriteria</th>
                                    <th style="width:260px;">Pilih Halaman WP Eksisting</th>
                                    <th style="width:280px;">Atau URL Kustom Eksisting</th>
                                    <th>URL Terbaca Saat Ini</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['items'] as $it) :
                                    $code   = (string) $it['code'];
                                    $curMap = (isset($customMap[$code]) && is_array($customMap[$code])) ? $customMap[$code] : array();
                                    $curPid = (int) ($curMap['post_id'] ?? 0);
                                    $curUrl = (string) ($curMap['custom_url'] ?? '');
                                ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($code); ?></strong></td>
                                        <td><?php echo esc_html($it['label']); ?><br><small style="color:#64748b;">Slug baku: <code><?php echo esc_html($it['slug']); ?></code></small></td>
                                        <td>
                                            <select name="map_post_id[<?php echo esc_attr($code); ?>]" style="width:100%;">
                                                <option value="0">-- Otomatis (Slug <?php echo esc_html($it['slug']); ?>) --</option>
                                                <?php foreach ($publishedPages as $pg) : ?>
                                                    <option value="<?php echo esc_attr((string) $pg->ID); ?>" <?php selected($curPid, (int) $pg->ID); ?>><?php echo esc_html(($pg->post_title ?: '(Tanpa Judul)') . ' (/' . $pg->post_name . '/)'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="url" name="map_custom_url[<?php echo esc_attr($code); ?>]" value="<?php echo esc_attr($curUrl); ?>" placeholder="https://pa-satker.go.id/halaman-lama/" style="width:100%;"></td>
                                        <td><small style="word-break:break-all;"><a href="<?php echo esc_url($it['url']); ?>" target="_blank"><?php echo esc_html($it['url']); ?></a></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" class="button button-primary">Simpan Pemetaan 35 Kriteria</button>
                    </form>
                </div>
            </div>

        <?php elseif ($activeTab === 'settings') :
            $isAdoption = (!empty($cfg['preserve_existing_design']) && (string) $cfg['preserve_existing_design'] === '1');
        ?>
            <?php if ($isAdoption) : ?>
                <div class="notice notice-info inline" style="margin:0 0 16px;padding:10px 14px;">
                    <strong>Mode Adopsi Situs Lama sedang AKTIF:</strong> Seeder 1-Klik hanya melengkapi halaman yang belum tersedia tanpa mengubah tema, menu, maupun halaman Beranda eksisting. Kelola melalui tab <a href="<?php echo esc_url($tabBaseUrl . 'migration'); ?>">Migrasi &amp; Adopsi</a>.
                </div>
            <?php endif; ?>
            <div style="display:grid;grid-template-columns:1.3fr 1fr;gap:20px;align-items:start;">
                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">Konfigurasi Identitas Satker &amp; API</h2>
                    <form method="post">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="save_settings">
                        <input type="hidden" name="preserve_existing_design" value="<?php echo $isAdoption ? '1' : '0'; ?>">
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
                            <tr><th>URL CCTV Online (ACO)</th><td><input type="url" name="aco_url" class="regular-text" value="<?php echo esc_attr($cfg['aco_url'] ?? 'https://cctv.badilag.net/'); ?>" placeholder="https://cctv.badilag.net/"><br><small style="color:#64748b;">Tautan Access CCTV Online Badilag / ruang PTSP Satker (kosongkan untuk menyembunyikan kartu ACO).</small></td></tr>
                            <tr><th>RSS Pengumuman MA RI</th><td><input type="url" name="rss_ma_url" class="regular-text" value="<?php echo esc_attr($cfg['rss_ma_url'] ?? 'https://kepaniteraan.mahkamahagung.go.id/?format=feed&type=rss'); ?>"></td></tr>
                            <tr><th>RSS Pengumuman Badilag</th><td><input type="url" name="rss_badilag_url" class="regular-text" value="<?php echo esc_attr($cfg['rss_badilag_url'] ?? 'https://badilag.mahkamahagung.go.id/pengumuman-elektronik?format=feed&type=rss'); ?>"></td></tr>
                            <tr><th>Secret Key HMAC API</th><td><input type="text" name="hmac_secret" class="regular-text" value="<?php echo esc_attr($cfg['hmac_secret']); ?>" placeholder="Minimal 32 karakter"><br><label><input type="checkbox" name="generate_hmac" value="1"> Generate Secret Key acak baru (64 hex)</label></td></tr>
                            <tr><th>Turnstile Site Key</th><td><input type="text" name="turnstile_site_key" class="regular-text" value="<?php echo esc_attr($cfg['turnstile_site_key']); ?>"></td></tr>
                            <tr><th>Turnstile Secret Key</th><td><input type="password" name="turnstile_secret_key" class="regular-text" value="<?php echo esc_attr($cfg['turnstile_secret_key']); ?>"></td></tr>
                        </table>
                        <p><button type="submit" class="button button-primary">Simpan Pengaturan</button></p>
                    </form>
                </div>

                <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:18px;">
                    <h2 style="margin-top:0;color:#056e4f;">Seeder Struktur Baku 1-Klik</h2>
                    <p>Membuat otomatis <strong>23 halaman Arsip Dokumen</strong> (slot s.d. 2027), halaman <strong>Wajib &amp; Inklusif (termasuk /disabilitas/ &amp; /pengumuman-resmi/)</strong>, halaman <strong>Beranda</strong> lengkap, berita awal, dan menyusun <strong>6 Menu Utama Standar</strong> di tema Astra / WordPress.</p>
                    <form method="post" style="margin-bottom:14px;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="run_seeder">
                        <button type="submit" class="button button-primary">Generate Halaman, Beranda &amp; Menu (1-Klik)</button>
                    </form>
                    <hr style="margin:14px 0;">
                    <h3 style="margin-top:0;font-size:14px;color:#0f172a;">Rapikan Ulang 6 Menu Utama Standar (SK 2722/2026)</h3>
                    <p style="font-size:12.5px;color:#475569;">Menyusun ulang <em>Menu Utama SINOVA</em> menjadi tepat 6 induk: <strong>Beranda</strong>, <strong>Profil Pengadilan</strong>, <strong>Layanan Hukum</strong>, <strong>Transparansi</strong>, <strong>Layanan Publik &amp; Pengaduan</strong>, dan <strong>Berita &amp; Publikasi</strong>.</p>
                    <form method="post" onsubmit="return confirm('Susun ulang item Menu Utama SINOVA menjadi 6 menu standar? Halaman tidak dihapus.');">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="rebuild_6menu">
                        <button type="submit" class="button button-secondary">Rapikan 6 Menu Resmi Sekarang</button>
                    </form>
                </div>
            </div>

        <?php else :
            $secLogs    = get_option('pa_starter_security_logs', array());
            if (!is_array($secLogs)) $secLogs = array();
            $tsActive      = function_exists('pa_starter_login_turnstile') && pa_starter_login_turnstile() !== null;
            $hiddenSlug    = pa_sinova_custom_login_slug();
            $curUid        = get_current_user_id();
            $mfaEnabled    = (string) get_user_meta($curUid, 'pa_totp_enabled', true) === '1';
            $mfaPending    = (string) get_user_meta($curUid, 'pa_totp_pending_secret', true);
            $mfaEnforced   = !empty($cfg['enforce_mfa']) && (string) $cfg['enforce_mfa'] === '1';
            $mfaUri        = ($mfaPending !== '' && function_exists('pa_starter_totp_provisioning_uri')) ? pa_starter_totp_provisioning_uri(wp_get_current_user(), $mfaPending) : '';
            $mfaQrSvg      = $mfaUri !== '' ? pa_sinova_totp_qr_svg($mfaUri) : '';
            $recHashes     = get_user_meta($curUid, 'pa_totp_recovery_hashes', true);
            $recRemaining  = is_array($recHashes) ? count($recHashes) : 0;
        ?>
            <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;margin-bottom:18px;">
                <h2 style="margin-top:0;color:#056e4f;">Autentikasi Dua Faktor (MFA TOTP RFC 6238 Bawaan)</h2>
                <p style="color:#475569;margin-top:0;">Tambahkan lapisan verifikasi OTP 6-digit menggunakan Google Authenticator, FreeOTP, atau Aegis — tanpa plugin pihak ketiga dan tanpa mengirim kunci ke server eksternal.</p>
                <?php if (!empty($mfaRecoveryOnce)) : ?>
                    <div style="background:#fffbeb;border:1px solid #f59e0b;border-radius:8px;padding:12px 16px;margin-bottom:14px;">
                        <strong style="color:#92400e;">Simpan 8 Kode Cadangan Pemulihan Ini (Hanya Ditampilkan Sekali):</strong>
                        <div style="display:grid;grid-template-columns:repeat(4,minmax(110px,1fr));gap:8px;margin-top:8px;font-family:monospace;font-size:13px;">
                            <?php foreach ($mfaRecoveryOnce as $rc) : ?>
                                <code style="background:#fff;padding:4px 8px;border:1px solid #fcd34d;border-radius:4px;text-align:center;"><?php echo esc_html($rc); ?></code>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($mfaEnabled) : ?>
                    <p style="color:#15803d;font-weight:700;">● MFA TOTP AKTIF pada akun Anda (sisa kode cadangan sekali pakai: <?php echo (int) $recRemaining; ?>).</p>
                    <form method="post" onsubmit="return confirm('Nonaktifkan MFA TOTP untuk akun Anda?');" style="display:inline-block;margin-right:12px;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="mfa_disable">
                        <button type="submit" class="button" style="color:#b91c1c;">Nonaktifkan MFA Akun Saya</button>
                    </form>
                <?php elseif ($mfaPending !== '') : ?>
                    <div style="display:flex;flex-wrap:wrap;gap:20px;align-items:center;background:#f8fafc;border:1px solid #cbd5e1;border-radius:10px;padding:14px;margin-bottom:12px;">
                        <div><?php echo $mfaQrSvg; ?></div>
                        <div style="flex:1;min-width:250px;">
                            <p style="margin-top:0;"><strong>1. Pindai QR Code</strong> dengan aplikasi autentikator, atau masukkan kode Base32 berikut secara manual:</p>
                            <p><code style="font-size:14px;letter-spacing:1.2px;padding:4px 8px;background:#fff;border:1px solid #cbd5e1;border-radius:6px;"><?php echo esc_html(trim(chunk_split($mfaPending, 4, ' '))); ?></code></p>
                            <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                                <input type="hidden" name="pa_sinova_action" value="mfa_activate">
                                <input type="text" name="mfa_verify_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="6 digit OTP" required style="width:130px;font-weight:700;letter-spacing:2px;text-align:center;">
                                <button type="submit" class="button button-primary">Verifikasi &amp; Aktifkan MFA</button>
                            </form>
                        </div>
                    </div>
                <?php else : ?>
                    <p style="color:#64748b;">Status akun Anda saat ini: <strong>Belum mengaktifkan MFA TOTP</strong>.</p>
                    <form method="post">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="mfa_setup_start">
                        <button type="submit" class="button button-primary">Mulai Pengaturan MFA TOTP (Generate QR Lokal)</button>
                    </form>
                <?php endif; ?>

                <?php if (current_user_can('manage_options')) : ?>
                    <form method="post" style="margin-top:14px;padding-top:12px;border-top:1px solid #e2e8f0;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                        <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                        <input type="hidden" name="pa_sinova_action" value="mfa_policy">
                        <label><input type="checkbox" name="enforce_mfa" value="1" <?php checked($mfaEnforced); ?>> Terapkan pengingat kewajiban MFA TOTP untuk seluruh Administrator &amp; Operator</label>
                        <button type="submit" class="button button-secondary">Simpan Kebijakan MFA</button>
                    </form>
                <?php endif; ?>
                <p style="margin:8px 0 0;font-size:12px;color:#64748b;"><em>Pemulihan darurat MFA: tambahkan <code>define('PA_DISABLE_CUSTOM_MFA', true);</code> di <code>wp-config.php</code> bila kehilangan perangkat &amp; kode cadangan.</em></p>
            </div>

            <?php if (current_user_can('manage_options')) : ?>
            <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:18px;margin-bottom:18px;">
                <h2 style="margin-top:0;color:#056e4f;">Sembunyikan Halaman Login Admin (Custom Login URL)</h2>
                <p style="color:#475569;margin-top:0;">Lindungi halaman masuk operator dari serangan otomatis dengan memindahkan URL login dari <code>/wp-login.php</code> dan menyamarkan akses anonim <code>/wp-admin/</code> serta <code>/wp-login.php</code> dengan respon <strong>404 Not Found</strong>.</p>
                <form method="post" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin-bottom:10px;">
                    <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                    <input type="hidden" name="pa_sinova_action" value="save_login_hide">
                    <label style="font-weight:700;"><code><?php echo esc_html(trailingslashit(home_url('/'))); ?></code>
                        <input type="text" name="custom_login_slug" value="<?php echo esc_attr($cfg['custom_login_slug'] ?? ''); ?>" placeholder="contoh: masuk-operator-pa" style="width:240px;margin-left:4px;">
                    </label>
                    <button type="submit" class="button button-primary">Simpan Pengaturan URL Login</button>
                </form>
                <p style="margin:0;font-size:12.5px;color:#334155;">
                    <strong>Status saat ini:</strong>
                    <?php if ($hiddenSlug !== '') : ?>
                        <span style="color:#15803d;font-weight:700;">AKTIF TERSEMBUNYI</span> &rarr; URL login resmi: <code><?php echo esc_html(trailingslashit(home_url('/' . $hiddenSlug))); ?></code> (simpan alamat ini di bookmark).
                    <?php else : ?>
                        <span>Standar WordPress (<code><?php echo esc_html(site_url('wp-login.php')); ?></code>). Isi slug minimal 4 karakter untuk mengaktifkan, atau kosongkan untuk kembali ke standar.</span>
                    <?php endif; ?>
                </p>
                <p style="margin:6px 0 0;font-size:12px;color:#64748b;"><em>Pemulihan darurat: jika lupa URL login baru, tambahkan <code>define('PA_DISABLE_CUSTOM_LOGIN', true);</code> di berkas <code>wp-config.php</code>.</em></p>
            </div>
            <?php endif; ?>

            <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;margin-bottom:18px;">
                <h2 style="margin-top:0;color:#056e4f;">Status Pengamanan &amp; Kontrol Akses Peran (RBAC)</h2>
                <ul style="line-height:1.8;margin:0 0 12px;padding-left:18px;">
                    <li><strong>MFA TOTP &amp; Batas Sesi Admin:</strong> <?php echo $mfaEnabled ? 'MFA TOTP Aktif' : 'MFA TOTP Opsional/Menunggu Aktivasi'; ?> · Batas sesi diam otomatis <strong>60 menit</strong> · Kebijakan kata sandi minimal <strong>10 karakter</strong></li>
                    <li><strong>Halaman Login Admin:</strong> <?php echo $hiddenSlug !== '' ? 'Disembunyikan pada slug kustom (<code>/' . esc_html($hiddenSlug) . '/</code>), akses langsung <code>wp-login.php</code> &amp; <code>/wp-admin/</code> anonim diblokir 404' : 'Standar <code>/wp-login.php</code> (dapat disembunyikan melalui formulir di atas)'; ?></li>
                    <li><strong>File Editor WP Admin:</strong> Dinonaktifkan (<code>DISALLOW_FILE_EDIT = true</code>)</li>
                    <li><strong>XML-RPC &amp; User Enumeration REST/Author:</strong> Diblokir untuk pengguna anonim</li>
                    <li><strong>Brute-Force Rate Limiter Login:</strong> Aktif (blokir 15 menit setelah 5x gagal per IP ter-hash)</li>
                    <li><strong>Login Anti-Bot:</strong> <?php echo $tsActive ? 'Cloudflare Turnstile + Honeypot' : 'CAPTCHA Matematika Offline (HMAC) + Honeypot'; ?></li>
                    <li><strong>Folder Unggahan PDF (<code>sinova-docs/.htaccess</code>):</strong> <?php echo is_file(WP_CONTENT_DIR . '/uploads/sinova-docs/.htaccess') ? 'Terlindungi (PHP execution &amp; listing diblokir)' : 'Aktif setelah admin_init'; ?></li>
                    <li><strong>WAF URI &amp; DB Content Guard:</strong> Aktif memblokir pola judi online, webshell, SQLi/XSS, &amp; cloaking tautan</li>
                </ul>
                <p style="margin:0;font-size:12.5px;color:#334155;"><strong>Peran Operator Satker Tersedia (Users &rarr; Add New):</strong> <code>pa_kepaniteraan</code> (LIPA 7, Sisa Panjar, Prodeo, PPID &amp; Data Operasional), <code>pa_kesekretariatan</code> (Anggaran, SAKIP, BMN, LHKPN/LHKASN), dan <code>pa_humas</code> (Data Operasional, Survei, &amp; Berita).</p>
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
