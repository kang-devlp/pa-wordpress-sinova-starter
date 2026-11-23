<?php
/**
 * Plugin Name: PA SINOVA Starter - Konfigurasi, Seeder 1-Klik SK 2722/2026 & Panel Admin
 * Description: Pengaturan dinamis Satker, generator Secret Key HMAC-SHA256, Seeder 1-Klik 35 halaman & Menu Utama SK Dirjen Badilag 2722/2026, serta panel Upload Dokumen & Checklist SINOVA di WP Admin.
 * Version: 1.0.0-starter
 */

defined('ABSPATH') || exit;

function pa_sinova_get_settings(): array {
    $defaults = array(
        'satker_name'          => get_bloginfo('name') ?: 'Pengadilan Agama Contoh Kelas I-A',
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
            'content' => '<h2>Alur &amp; Prosedur Berperkara (Tingkat Pertama, Banding, Kasasi, PK)</h2><p>[LENGKAPI syarat dan alur 6 jenis prosedur berperkara sesuai SK Dirjen Badilag 2722/2026.]</p>',
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
            'content' => '<h2>Statistik Pengunjung Website Resmi</h2><p>[LENGKAPI widget atau grafik kunjungan harian/bulanan/tahunan website.]</p>',
        ),
    );
}

/**
 * Seeder 1-Klik: membuat 23 halaman modul SINOVA + 17 halaman wajib/statis + Menu Utama.
 */
function pa_sinova_run_starter_seeder(): array {
    $seeded = get_option('pa_sinova_seeded_pages', array());
    if (!is_array($seeded)) {
        $seeded = array();
    }

    $createdCount = 0;
    $menuItemsPlan = array(
        'Profil Pengadilan'       => array(),
        'Kepaniteraan & Perkara'   => array(),
        'Kesekretariatan & Anggaran' => array(),
        'Perencanaan & SAKIP'      => array(),
        'Layanan Publik & PPID'    => array(),
        'Publikasi'                => array(),
    );

    $catToParent = array(
        'Keuangan & Anggaran'             => 'Kesekretariatan & Anggaran',
        'Kepaniteraan & Biaya Perkara'    => 'Kepaniteraan & Perkara',
        'Perencanaan & SAKIP'             => 'Perencanaan & SAKIP',
        'Layanan Publik, PPID & Kepatuhan'=> 'Layanan Publik & PPID',
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

    update_option('pa_sinova_seeded_pages', $seeded, false);

    // Susun Menu Utama jika belum ada menu 'Menu Utama SINOVA'
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
                            'menu-item-object-id'   => (int) $ch['post_id'],
                            'menu-item-object'      => 'page',
                            'menu-item-type'        => 'post_type',
                            'menu-item-status'      => 'publish',
                            'menu-item-parent-id'   => (int) $parentItemId,
                            'menu-item-position'    => $pos++,
                        ));
                        $menuCreated++;
                    }
                }
            }
            $locations = get_theme_mod('nav_menu_locations', array());
            if (!is_array($locations)) {
                $locations = array();
            }
            $locations['primary'] = $menuId;
            set_theme_mod('nav_menu_locations', $locations);
        }
    }

    return array('pages_created' => $createdCount, 'total_mapped' => count($seeded), 'menu_items' => $menuCreated);
}

/**
 * Resolve permalink untuk halaman modul SINOVA maupun statis.
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
        'SINOVA Starter Kit',
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pa_sinova_action'])) {
        check_admin_referer('pa_sinova_starter_nonce');
        $action = sanitize_key((string) $_POST['pa_sinova_action']);

        if ($action === 'save_settings') {
            $cur = pa_sinova_get_settings();
            $new = array(
                'satker_name'          => sanitize_text_field(wp_unslash((string) ($_POST['satker_name'] ?? $cur['satker_name']))),
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
            $notice = 'Pengaturan Satker & API HMAC berhasil disimpan.';
        } elseif ($action === 'run_seeder') {
            $res = pa_sinova_run_starter_seeder();
            $notice = sprintf(
                'Seeder selesai: %d halaman baru dibuat (%d total halaman terpetakan), %d item menu ditambahkan.',
                $res['pages_created'],
                $res['total_mapped'],
                $res['menu_items']
            );
        } elseif ($action === 'upload_doc' && function_exists('pa_sinova_ensure_table')) {
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

            if ($extUrl !== '' && stripos($extUrl, 'https://') === 0) {
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
    }

    $cfg     = pa_sinova_get_settings();
    $catalog = function_exists('pa_sinova_module_catalog') ? pa_sinova_module_catalog() : array();
    $periods = function_exists('pa_sinova_all_periods_map') ? pa_sinova_all_periods_map() : array();
    ?>
    <div class="wrap">
        <h1 style="color:#056e4f;font-weight:800;">PA SINOVA Starter Kit (SK Dirjen Badilag 2722/2026)</h1>
        <?php if ($notice !== '') : ?>
            <div class="notice notice-success is-dismissible"><p><strong><?php echo esc_html($notice); ?></strong></p></div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));gap:20px;margin-top:16px;">
            <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #056e4f;border-radius:10px;padding:18px;">
                <h2 style="margin-top:0;color:#056e4f;">1. Konfigurasi Satker &amp; REST API HMAC</h2>
                <form method="post">
                    <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                    <input type="hidden" name="pa_sinova_action" value="save_settings">
                    <table class="form-table">
                        <tr><th>Nama Satker</th><td><input type="text" name="satker_name" class="regular-text" value="<?php echo esc_attr($cfg['satker_name']); ?>"></td></tr>
                        <tr><th>URL SIPP</th><td><input type="url" name="sipp_url" class="regular-text" value="<?php echo esc_attr($cfg['sipp_url']); ?>"></td></tr>
                        <tr><th>URL e-Court</th><td><input type="url" name="ecourt_url" class="regular-text" value="<?php echo esc_attr($cfg['ecourt_url']); ?>"></td></tr>
                        <tr><th>URL Direktori Putusan</th><td><input type="url" name="direktori_url" class="regular-text" value="<?php echo esc_attr($cfg['direktori_url']); ?>"></td></tr>
                        <tr><th>URL Survei Pelayanan</th><td><input type="url" name="survey_url" class="regular-text" value="<?php echo esc_attr($cfg['survey_url']); ?>"></td></tr>
                        <tr><th>Secret Key HMAC API</th><td><input type="text" name="hmac_secret" class="regular-text" value="<?php echo esc_attr($cfg['hmac_secret']); ?>" placeholder="Minimal 32 karakter"><br><label><input type="checkbox" name="generate_hmac" value="1"> Generate Secret Key acak baru (64 hex)</label></td></tr>
                        <tr><th>Turnstile Site Key</th><td><input type="text" name="turnstile_site_key" class="regular-text" value="<?php echo esc_attr($cfg['turnstile_site_key']); ?>"></td></tr>
                        <tr><th>Turnstile Secret Key</th><td><input type="password" name="turnstile_secret_key" class="regular-text" value="<?php echo esc_attr($cfg['turnstile_secret_key']); ?>"></td></tr>
                    </table>
                    <p><button type="submit" class="button button-primary">Simpan Pengaturan</button></p>
                </form>

                <hr>
                <h2 style="color:#056e4f;">2. Seeder Halaman &amp; Menu SK 2722/2026</h2>
                <p>Membuat otomatis <strong>23 halaman Arsip SINOVA</strong> (berisi <code>[pa_sinova_docs]</code> slot s.d. 2027), <strong>17 halaman wajib/statis</strong> bertanda <code>[LENGKAPI]</code>, dan menyusun Menu Utama.</p>
                <form method="post">
                    <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                    <input type="hidden" name="pa_sinova_action" value="run_seeder">
                    <button type="submit" class="button button-secondary" style="border-color:#056e4f;color:#056e4f;font-weight:700;">Generate 40 Halaman &amp; Menu Utama (1-Klik)</button>
                </form>
            </div>

            <div style="background:#fff;border:1px solid #cbd5e1;border-top:4px solid #C9A227;border-radius:10px;padding:18px;">
                <h2 style="margin-top:0;color:#056e4f;">3. Upload Dokumen Cepat (Mode WordPress Mandiri)</h2>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('pa_sinova_starter_nonce'); ?>
                    <input type="hidden" name="pa_sinova_action" value="upload_doc">
                    <table class="form-table">
                        <tr><th>Modul SINOVA</th><td>
                            <select name="doc_slug" class="regular-text">
                                <?php foreach ($catalog as $slug => $m) : ?>
                                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($m['title'] . ' (' . $slug . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td></tr>
                        <tr><th>Tahun</th><td><input type="number" name="doc_year" min="2017" max="2027" value="<?php echo esc_attr((string) date('Y')); ?>"></td></tr>
                        <tr><th>Sub-Kelompok</th><td><input type="text" name="doc_subgroup" class="regular-text" value="SERAPAN 01" placeholder="Misal: SERAPAN 01 / DIPA 01"></td></tr>
                        <tr><th>Periode Slot</th><td>
                            <select name="doc_period">
                                <?php foreach ($periods as $pk => $pv) : ?>
                                    <option value="<?php echo esc_attr($pk); ?>"><?php echo esc_html($pk . ' — ' . $pv['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td></tr>
                        <tr><th>Upload PDF (Maks 10MB)</th><td><input type="file" name="doc_pdf" accept="application/pdf,.pdf"></td></tr>
                        <tr><th>Atau URL HTTPS / Drive</th><td><input type="url" name="doc_url" class="regular-text" placeholder="https://drive.google.com/file/d/.../view"></td></tr>
                        <tr><th>Judul Dokumen (Opsional)</th><td><input type="text" name="doc_title" class="regular-text"></td></tr>
                    </table>
                    <p><button type="submit" class="button button-primary">Simpan &amp; Tayangkan ke Halaman Web</button></p>
                </form>
            </div>
        </div>

        <div style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:18px;margin-top:20px;">
            <h2 style="margin-top:0;color:#056e4f;">4. Daftar Halaman &amp; Shortcode Siap Salin</h2>
            <table class="widefat striped">
                <thead><tr><th>Slug Modul</th><th>Judul Modul</th><th>Shortcode</th><th>URL Halaman Web</th></tr></thead>
                <tbody>
                    <?php foreach ($catalog as $slug => $m) :
                        $u = pa_sinova_resolve_page_url($slug);
                    ?>
                        <tr>
                            <td><code><?php echo esc_html($slug); ?></code></td>
                            <td><strong><?php echo esc_html($m['title']); ?></strong></td>
                            <td><code>[pa_sinova_docs slug="<?php echo esc_attr($slug); ?>"]</code></td>
                            <td><a href="<?php echo esc_url($u); ?>" target="_blank"><?php echo esc_html($u); ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
