# Starter Kit Website WordPress & Panel Kepatuhan SINOVA (SK Dirjen Badilag 2722/2026)

Paket *starter* **open source** (lisensi MIT) untuk satuan kerja **Pengadilan Agama (Tingkat Pertama)** berbasis **WordPress** yang dioptimasi penuh untuk penilaian **SINOVA – SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026**:

* **Arsitektur Hijau Gelap (`#056e4f`) & Royal Gold (`#C9A227`)** sesuai standar tata letak SK 2722/2026.
* **SINOVA Document Hub — 23 Modul ("Rumah s.d. 2027"):** Slot terstruktur DIPA 01/04, LIPA 7A/B/C, Triwulan SKM/SPAK/SPKP (`TW I–IV`), Semester I/II CALK, dan SAKIP 5 Tahunan — mendukung **Upload PDF langsung ke hosting** maupun tautan Google Drive.
* **1-Click Seeder Struktur Baku & Menu Utama:** Sekali klik langsung membuat **23 halaman dokumen SINOVA + 12 halaman Informasi Wajib (`[LENGKAPI]`) + Menu Utama hierarkis** di WordPress kosong (Astra Free / tema standar).
* **Dashboard Kepatuhan 35 Kriteria Lampiran II (`B1.1–W4.1`) + Salin URL Instan:** Tersedia dalam **2 mode**:
  1. **Mode Mandiri (WordPress Only):** Langsung kelola dokumen, cek skor 35 kriteria, dan salin URL dari menu **WP Admin → SINOVA Hub**.
  2. **Mode Portal Internal Terpisah (Laravel + Vue + REST API HMAC-SHA256):** Hubungkan aplikasi internal kantor (seperti portal manajemen internal satker) agar operator bisa mengunggah dokumen & memantau link *Google Drive* terkunci tanpa perlu membuka `/wp-admin` publik.
* **Performa & Auto-Capture Ready:** Cache HTML statis *early-serve* + *Stale-While-Revalidate* (`Rendering ~0.02–0.05 detik`), penguncian visibilitas seksi (*capture-safe* agar bot SINOVA tidak memotret layar kosong), Dark Mode + Kontras Tinggi WCAG AAA.
* **Keamanan Berlapis:** OWASP headers, pemblokir XML-RPC / enumerasi user, Anti-Judol WAF, serta CAPTCHA login ganda (**Cloudflare Turnstile** + fallback soal matematika offline).

> **Catatan Keterbukaan & Keamanan:** Repositori ini **100% kosongan dan bebas rahasia** — tidak berisi `wp-config.php`, kredensial database, plugin komersial/berbayar, maupun data satker mana pun. Seluruh aset tema/plugin pendukung menggunakan WordPress inti atau tema gratis resmi.

---

## Struktur Folder

```text
sinova-starter-kit/
├── README.md
├── LICENSE
├── .gitignore
├── wordpress-mu-plugins/                  # Salin isi folder ini ke wp-content/mu-plugins/
│   ├── 00-pa-sinova-config.php            # Pengaturan Satker, 1-Click Seeder 35 halaman & Menu, + Panel WP Admin
│   ├── pa-sinova-hub.php                  # Mesin 23 Modul SINOVA, Shortcode [pa_sinova_docs], Widget Survei, & REST HMAC API
│   ├── pa-home-page-cache.php             # Early-Serve HTML Page Cache + [waktu_render] Real-Time
│   ├── pa-ui-performance-a11y.php         # Tema Hijau-Gold, Mega Menu 60 FPS, Dark/High-Contrast, Capture-Safe
│   └── pa-security-hardening.php          # OWASP Headers, Anti-Judol WAF, Dual-Mode CAPTCHA Login
├── wordpress-templates/
│   └── beranda-blocks-starter.html        # Blok HTML mandiri Kartu Survei Indeks Pelayanan Publik & 6 Kartu ZI
└── laravel-sinova-panel/                  # Opsional: Modul Laravel + Inertia Vue untuk portal internal terpisah
    ├── app/Http/Controllers/WebsiteSinovaController.php
    ├── app/Models/SinovaWebsiteAuditLog.php
    ├── app/Models/SinovaWebsiteDocument.php
    ├── app/Services/Sinova/WordPressSinovaSyncService.php
    ├── database/migrations/2026_11_20_090000_create_sinova_website_documents_table.php
    ├── resources/js/Pages/WebsiteSinova/Index.vue
    ├── routes/website_sinova.php
    └── storage/app/sinova-wp-gateway.example.json
```

---

## Cara Pakai Cepat (Mode WordPress Mandiri — 5 Menit)

Cocok untuk satker dengan hosting **cPanel / DirectAdmin / VPS** yang sudah memasang WordPress fresh (disarankan tema **Astra** versi gratis + **Elementor** versi gratis atau editor blok bawaan WordPress):

1. **Salin seluruh berkas PHP** dari `wordpress-mu-plugins/` ke folder WordPress Anda di:
   `wp-content/mu-plugins/`
   *(Buat folder `mu-plugins` bila belum ada — WordPress otomatis mengaktifkannya tanpa perlu klik Activate.)*
2. **Buka WP Admin → Pengaturan → Permalinks**, pilih struktur **Post name** (`/sample-post/`), lalu klik **Save Changes**.
3. **Buka WP Admin → SINOVA Starter Kit:**
   * Isi **Nama Satker** (contoh: `Pengadilan Agama Contoh Kelas I-A`), alamat kabupaten/kota, URL **SIPP**, **e-Court**, **Direktori Putusan**, dan tautan **Survei Badilag**.
   * Klik **Generate Secret Key HMAC** bila ingin mengaktifkan sinkronisasi API.
   * Klik **Simpan Pengaturan**.
4. **Klik tombol `"Generate Halaman & Menu Utama SK 2722/2026 (1-Klik)"`:**
   * Sistem otomatis membuat **23 halaman Arsip Dokumen** (sudah terisi `[pa_sinova_docs slug="..."]`), **12 halaman Informasi Wajib** (berisi kerangka `[LENGKAPI]`), serta menyusun **Menu Utama** yang rapi dan tertaut ke semua halaman.
5. **Pasang Blok Beranda (Opsional):**
   * Buka halaman Beranda → tambahkan widget **HTML Khusus** (*Custom HTML*) lalu tempel isi berkas `wordpress-templates/beranda-blocks-starter.html` untuk menampilkan **6 Kartu Zona Integritas sama tinggi** dan panel **Indeks Pelayanan Publik (SKM/SPAK/SPKP Triwulan I–IV)** yang tersinkron otomatis dari modul `survei-pelayanan`.
6. **Isi Dokumen & Cek Kesiapan:**
   * Buka **WP Admin → SINOVA Starter Kit → tab Checklist 35 Kriteria / Kelola Dokumen**, unggah PDF resmi atau tempel URL Google Drive (pastikan akses *Anyone with the link*), dan gunakan tombol **Salin URL** saat mengisi aplikasi penilaian **SINOVA Badilag**.

---

## Cara Menghubungkan ke Portal Laravel Internal (Opsional)

Jika satker Anda ingin staf mengelola publikasi dari aplikasi internal (seperti Laravel + Inertia Vue 3) tanpa perlu login ke `/wp-admin`:

1. Salin seluruh folder di dalam `laravel-sinova-panel/` ke direktori aplikasi Laravel Anda (controller, models, service, migration, Vue page, routes).
2. Jalankan migrasi database Laravel:
   ```bash
   php artisan migrate
   ```
3. Tambahkan variabel berikut di `.env` Laravel (atau buat `storage/app/sinova-wp-gateway.json` dari contoh yang tersedia):
   ```env
   SINOVA_WP_BASE_URL=https://www.pa-contoh.go.id
   SINOVA_WP_SECRET_KEY=tempel_kunci_hmac_dari_wp_admin_disini
   SINOVA_WP_TIMEOUT=30
   ```
4. Pastikan kunci rahasia (`SINOVA_WP_SECRET_KEY`) **persis sama** dengan Secret Key yang tersimpan di **WP Admin → SINOVA Starter Kit**.
5. Jalankan `npm run build` di Laravel, buka route `/update-website`, lalu klik **"Tarik Semua 23 Modul"**.

---

## Checklist Keamanan Sebelum Go-Live

- [ ] Aktifkan HTTPS + SSL valid (`https://`) dan aktifkan HSTS di server / Cloudflare.
- [ ] Ubah URL login `/wp-login.php` bila perlu dan wajibkan **2FA / Authenticator** untuk seluruh akun Administrator.
- [ ] Jika memakai **Cloudflare Turnstile**, masukkan *Site Key* & *Secret Key* pada menu **Pengaturan → SINOVA Starter Kit**.
- [ ] Pastikan database MariaDB/MySQL tidak terekspos ke publik (`bind-address = 127.0.0.1`) serta backup berkala tersimpan *offsite*.
- [ ] Jangan pernah meng-commit berkas `.env`, `*.key`, atau `sinova-wp-gateway.json` ke repositori Git publik.
