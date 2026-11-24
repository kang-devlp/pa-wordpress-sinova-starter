# Starter Kit Website Resmi Pengadilan Agama (SK Dirjen Badilag 2722/2026)

Paket *starter* **gratis & open source** (Lisensi MIT) untuk membangun dan merapikan website resmi **Pengadilan Agama** berbasis **WordPress** sesuai standar **SK Dirjen Badilag 2722/2026** dan **SK KMA 2-144/2022**.

- **Tanpa Elementor & tanpa plugin berbayar** — ringan, cepat, dan ramah operator non-IT.
- **Editor Grid Beranda 12 Kolom (*Drag & Resize*)** — seret kotak, tarik sudut atur lebar (`1–12` kolom), edit isi langsung lewat popup modal, termasuk **Slider Foto**, **Jadwal Sidang & Live SIPP**, **Statistik Perkara**, **Galeri Video**, **Ucapan & Belasungkawa**, hingga **Konten Bebas / Sematan**.
- **Update 1-Klik + Rollback** — perbarui langsung dari GitHub di WP Admin dengan cadangan otomatis (*Batalkan Pembaruan Terakhir*) dan tombol **Bersihkan Cache** 1-klik.
- **2 Pilihan Pemasangan:** via **Upload Plugin `.zip`** langsung dari WP Admin *(paling mudah, tanpa buka cPanel)* **atau** salin ke folder `wp-content/mu-plugins/`.

---

## Pratinjau Tampilan

| Beranda Mode Terang & CCTV ACO | Beranda Mode Gelap (Dark Mode) |
|---|---|
| ![Beranda Mode Terang](docs/screenshots/beranda-light.png) | ![Beranda Mode Gelap](docs/screenshots/beranda-dark.png) |

| Arsip Dokumen Publik (LRA, DIPA, dll.) | Dashboard Kepatuhan 35 Kriteria |
|---|---|
| ![Arsip Dokumen Publik](docs/screenshots/modul-sinova.png) | ![Dashboard 35 Kriteria](docs/screenshots/admin-dashboard-35.png) |

| Migrasi & Adopsi Situs Lama | Keamanan MFA & Sembunyikan Login |
|---|---|
| ![Migrasi Situs Lama](docs/screenshots/admin-migrasi-adopsi.png) | ![Keamanan & Login](docs/screenshots/admin-keamanan-login.png) |

---

## Fitur Utama

1. **Tampilan Publik Modern & Aksesibel (WCAG 2.2):** Header 2 baris + Mega Menu 4 kolom, 6 Menu Utama standar, Pencarian Kilat `Ctrl+K`, Mode Terang/Gelap, Jam WIB + status PTSP, Dock CCTV ACO lokal, Panel Aksesibilitas & Pembaca Suara Bahasa Indonesia, tanpa ketergantungan Google Fonts CDN.
2. **Editor Grid Beranda 12 Kolom & Live SIPP:** Atur seluruh susunan Beranda lewat **WP Admin → SINOVA Hub → Tampilan Beranda** (seret posisi & tarik ukuran 13 jenis blok, termasuk **Konten Bebas/Sematan**). Tempel link `https://sipp.pa-satker.go.id/Slide_sidang` pada blok **Jadwal Sidang** — otomatis tampil bersama tab *Agenda Terstruktur* dan otomatis diizinkan oleh CSP `frame-src`. Tersedia pula endpoint **API Push SIPP** (`POST /wp-json/pa-sinova/v1/sipp/push-hearings` + contoh [scripts/sipp-push-example.php](scripts/sipp-push-example.php)).
3. **23 Modul Arsip Dokumen & Publikasi Lengkap:** Kelola LRA, DIPA, LKJiP, LIPA 7, Sisa Panjar, LHKPN, Survei (PDF maks. 10 MB / Google Drive), **Pengumuman 3 Kolom** (Satker, RSS MA RI, Badilag), **Slider Foto**, **Galeri Video**, **Ucapan & Belasungkawa**, **Wilayah Yurisdiksi**, serta **Profil Pegawai lengkap dengan modal detail**.
4. **Dashboard Audit 3 Pilar & 35 Kriteria:** Menghitung skor kepatuhan otomatis (target **Sangat Baik ≥ 90**) lengkap dengan tombol ekspor **CSV & JSON** dan pembagian peran operator (`pa_kepaniteraan`, `pa_kesekretariatan`, `pa_humas`).
5. **Keamanan Berlapis Bawaan:** CSP Nonce + Trusted Types, **MFA TOTP** (Google Authenticator/Aegis, QR dibuat lokal), **Sembunyikan URL Login Admin** (404 untuk `/wp-login.php`), Rate Limiter, WAF Anti-Judol, CAPTCHA offline / Turnstile, dan *File Integrity Monitor (SHA-256)*.

---

## Cara Pasang Paling Mudah (Upload Plugin `.zip` — Tanpa cPanel)

Cocok untuk **website WordPress baru** maupun **XAMPP lokal** (syarat: **PHP 8.1+**, disarankan 8.2/8.3).

1. **Unduh file plugin siap pakai:**  
   👉 **[Download `pa-sinova-starter.zip`](https://github.com/kang-devlp/pa-wordpress-sinova-starter/raw/main/dist/pa-sinova-starter.zip)**
2. **Pasang tema gratis Astra:**  
   Buka **WP Admin → Appearance → Themes → Add New → cari `Astra` → Install & Activate**.
3. **Upload & aktifkan plugin:**  
   Buka **WP Admin → Plugins → Add New → Upload Plugin → pilih `pa-sinova-starter.zip` → Install Now → Activate**.
4. **Atur Permalink & Jalankan Seeder 1-Klik:**
   - Buka **Settings → Permalinks → pilih `Post name` → Save Changes**.
   - Buka **SINOVA Hub → tab Identitas Satker & Seeder** → isi nama satker, alamat, WA PTSP, tautan SIPP/e-Court/ACO → klik **Simpan Pengaturan**.
   - Klik **`Generate Halaman, Beranda & Menu (1-Klik)`**. Selesai!

---

## Alternatif: Pasang via Folder `mu-plugins` (cPanel / VPS / XAMPP)

Jika lebih suka mode `mu-plugins` (aktif otomatis & *Early Page Cache* berjalan paling awal):
1. Salin **seluruh 6 file `.php` beserta folder `assets/`** dari `wordpress-mu-plugins/` ke folder `wp-content/mu-plugins/`.
2. Aktifkan tema **Astra**, atur Permalink **`Post name`**, lalu jalankan **Seeder 1-Klik** di **WP Admin → SINOVA Hub**.

---

## Cara Atur Beranda & Pasang Jadwal Sidang Live SIPP (Tanpa Edit Kode)

1. Buka **WP Admin → SINOVA Hub → Tampilan Beranda**.
2. *(Khusus upgrade dari versi lama)* Jika muncul peringatan kuning *"Beranda masih HTML statis lama"*, klik **`Aktifkan Beranda Dinamis (1-Klik)`** *(berita, dokumen, dan halaman lain tetap aman)*.
3. **Seret kotak** untuk memindah posisi baris/kolom, **tarik sudut kanan bawah (`↘`)** untuk mengubah lebar `2–12` kolom, atau klik **`+ Tambah Blok`** untuk menambah **Galeri Video**, **Ucapan & Belasungkawa**, maupun **Konten Bebas / Sematan**.
4. Klik ikon pensil (**✎**) pada blok **Jadwal Sidang & Live SIPP**, tempel link `https://sipp.pa-satker.go.id/Slide_sidang` → **Terapkan ke Kanvas** → klik **`Simpan Tampilan Beranda`**.

---

## Cara Update ke Versi Terbaru (Tanpa Kehilangan Data)

### Untuk pengguna v2.2.0 ke atas (Otomatis 1-Klik)
- **Mode Plugin `.zip`:** Klik **"Perbarui sekarang"** di menu **WP Admin → Plugins**, atau lewat **SINOVA Hub → tab Keamanan, Update & Audit → `Update 1-Klik dari GitHub`**.
- **Mode `mu-plugins`:** Buka **SINOVA Hub → tab Keamanan, Update & Audit → klik `Cek Pembaruan Sekarang` → `Update 1-Klik dari GitHub`**. Cache HTML & baseline SHA-256 diperbarui otomatis.

### Untuk pengguna versi lama (v1.0 / v2.0 / v2.1)
1. Cadangkan database (`.sql`) terlebih dahulu.
2. Timpa seluruh isi `wp-content/mu-plugins/` dengan **6 file `.php` + folder `assets/`** terbaru dari `wordpress-mu-plugins/` *(1 kali terakhir — update berikutnya sudah 1-klik dari WP Admin)*.
3. Hapus isi `wp-content/cache/` jika ada.
4. Buka **SINOVA Hub → tab Desain & Tata Letak Beranda → klik `Aktifkan Beranda Dinamis (1-Klik)`**, lalu di tab **Identitas Satker & Seeder** klik **`Rapikan 6 Menu Resmi Sekarang`** *(lewati jika memakai Mode Adopsi situs lama)*.
5. Klik **`Perbarui Baseline SHA-256`** di tab **Keamanan, Update & Audit**.

---

## Memasang di Website Lama / Migrasi dari Joomla (Aman Tanpa Hapus Berita)

- **WordPress Lama (Elementor / desain eksisting):** Setelah pasang plugin/`mu-plugins`, **wajib** buka **SINOVA Hub → tab Migrasi & Adopsi**, centang **"Pertahankan header, menu navigasi, dan beranda situs lama (Mode Non-Destruktif)"**, lalu klik **`Simpan Mode & Jalankan Seeder Aman`**.
- **Migrasi dari Joomla (1.5 s.d. 5.x):** Gunakan plugin gratis **`FG Joomla to WordPress`**, lalu aktifkan **Mode Adopsi** sebelum menjalankan Seeder.  
  📖 Baca panduan lengkap beserta solusi error umum di: **[docs/PANDUAN-MIGRASI-JOOMLA-KE-WORDPRESS.md](docs/PANDUAN-MIGRASI-JOOMLA-KE-WORDPRESS.md)**.

---

## Keamanan Penting & Integrasi Opsional

- **MFA TOTP & Sembunyikan Login Admin:** Aktifkan di **WP Admin → SINOVA Hub → tab Keamanan, Update & Audit**.  
  *Pemulihan darurat bila lupa URL login / kehilangan perangkat MFA:* tambahkan `define('PA_DISABLE_CUSTOM_LOGIN', true);` atau `define('PA_DISABLE_CUSTOM_MFA', true);` di `wp-config.php`.
- **Hardening Apache (`.htaccess`):** Contoh aturan kompresi & keamanan produksi tersedia di [.htaccess.hardened.example](.htaccess.hardened.example).
- **Integrasi Aplikasi Internal Laravel (Opsional):** Folder `laravel-sinova-panel/` menyediakan modul Laravel + Inertia Vue 3 untuk unggah dokumen dari jaringan internal kantor (samakan `SINOVA_WP_SECRET_KEY` di `.env` Laravel dengan Secret Key HMAC di **SINOVA Hub**).
- **Daftar Periksa Sebelum Online:** Pastikan domain `.go.id` sudah memakai **HTTPS/SSL**, **Settings → Reading → Search Engine Visibility** tidak dicentang, dan skor **Dashboard 3 Pilar ≥ 90**.

---

## Struktur Repositori

```text
├── dist/pa-sinova-starter.zip             # Paket plugin WordPress siap upload (1-klik)
├── version.json                           # Manifest versi untuk Update 1-Klik dari GitHub
├── wordpress-mu-plugins/                  # 6 file inti PHP + assets/cctv-aco.gif
├── wordpress-plugin-wrapper/              # Pembungkus plugin standar WP + anti-bentrok
├── wordpress-templates/                   # Referensi template HTML Beranda
├── scripts/build-plugin-zip.sh            # Skrip pembuat ulang dist/pa-sinova-starter.zip
├── docs/                                  # Screenshot & panduan migrasi Joomla
└── laravel-sinova-panel/                  # Opsional: integrasi panel internal Laravel
```
