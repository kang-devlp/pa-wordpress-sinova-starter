# PA WordPress SINOVA Starter Kit (SK Dirjen Badilag 2722/2026)

Paket *starter* **gratis & open source** (Lisensi MIT) untuk membangun dan menata website resmi **Pengadilan Agama** berbasis **WordPress** (PHP 8.1+, disarankan 8.2/8.3) sesuai standar **SK Dirjen Badilag 2722/2026** dan **SK KMA 2-144/2022**.

- **Tanpa Elementor & tanpa plugin berbayar** — ringan, cepat, dan ramah operator non-IT.
- **Editor Grid Beranda 12 Kolom (*Drag & Resize*)** — seret kotak, tarik sudut atur lebar (`2–12` kolom), edit isi langsung lewat popup modal (Slider Foto, Jadwal Sidang & Live SIPP, Statistik Perkara, **Direktori Putusan MA RI per Satker via RSS**, Pengumuman, Berita, Galeri Video, Ucapan & Belasungkawa, hingga Konten Bebas/Sematan).
- **Update 1-Klik + Rollback + Notifikasi WP Admin** — perbarui langsung dari GitHub di WP Admin dengan cadangan otomatis (*Batalkan Pembaruan Terakhir*) dan tombol **Bersihkan Cache** 1-klik.
- 👉 **Unduh Plugin Siap Pakai (v2.4.1):** **[Download `pa-sinova-starter.zip`](https://github.com/kang-devlp/pa-wordpress-sinova-starter/raw/main/dist/pa-sinova-starter.zip)**

---

## Panduan Cepat (Pilih Sesuai Kondisi Website Anda)

> **Catatan Penting:** Repositori ini adalah **Plugin / Starter Kit untuk WordPress** (bukan menggantikan file inti WordPress). Jangan langsung mengekstrak repositori GitHub ke `htdocs/` tanpa menginstal WordPress terlebih dahulu.  
> **Catatan Keamanan Data:** Semua cara upgrade di bawah **tidak menghapus** berita, halaman, dokumen (`LRA/DIPA/dll.`), maupun pengaturan lama. Tetap disarankan backup database (`.sql`) sebelum upgrade.

### 1. Baru Pertama Kali Install (WordPress Baru / Hosting / XAMPP)
*(Paling mudah lewat Upload Plugin `.zip` — tidak perlu buka cPanel/FTP)*
1. **Install WordPress terlebih dahulu:** Unduh WordPress resmi dari [wordpress.org/download](https://wordpress.org/download/), ekstrak ke `htdocs/web_pa/` (XAMPP) atau `public_html/` (hosting), buat 1 database kosong di **phpMyAdmin**, lalu selesaikan instalasi WordPress sampai bisa masuk ke **WP Admin** (`localhost/web_pa/wp-admin`).
2. Pasang & aktifkan tema gratis **Astra** (**WP Admin → Appearance → Themes → Add New → `Astra`**).
3. Unduh **[`pa-sinova-starter.zip`](https://github.com/kang-devlp/pa-wordpress-sinova-starter/raw/main/dist/pa-sinova-starter.zip)**, lalu upload lewat **WP Admin → Plugins → Add New → Upload Plugin → Install Now → Activate**.
4. Atur Permalink: **Settings → Permalinks → pilih `Post name` → Save Changes**.
5. Buka **SINOVA Hub → Identitas & Seeder**, lengkapi identitas satker → **Simpan Pengaturan** → klik **`Generate Halaman, Beranda & Menu (1-Klik)`**.
6. *(Alternatif mode `mu-plugins`)* Jika ingin memakai folder `mu-plugins`, salin seluruh isi `wordpress-mu-plugins/` (**6 file `.php` + folder `assets/`**) ke `wp-content/mu-plugins/` lalu lakukan langkah 1, 2, 4, dan 5.

### 2. Sudah Install Versi 1.0 → Upgrade ke v2.4.1
*(Cukup 1 kali pindah ke paket Plugin `.zip`, selanjutnya update v2.4 dst. cukup 1 klik dari WP Admin)*
1. Hapus **5 file `.php` lama** di `wp-content/mu-plugins/` dan hapus isi `wp-content/cache/` (jika ada).
2. Unduh **[`pa-sinova-starter.zip`](https://github.com/kang-devlp/pa-wordpress-sinova-starter/raw/main/dist/pa-sinova-starter.zip)** → upload lewat **WP Admin → Plugins → Add New → Upload Plugin → Activate**.
3. Buka **SINOVA Hub → Tampilan Beranda → klik `Aktifkan Beranda Dinamis (1-Klik)`**.
4. Buka **SINOVA Hub → Identitas & Seeder → klik `Rapikan 6 Menu Resmi Sekarang`** *(lewati bila memakai Mode Adopsi situs lama)*.
5. *(Opsional khusus v1.0)* Jika 5 halaman ini (`profil-pegawai`, `agenda-jadwal-persidangan`, `statistik-perkara`, `panggilan-ghaib`, `wilayah-yurisdiksi`) isinya masih teks `[LENGKAPI]`, hapus permanen (*Trash → Empty Trash*) 5 halaman itu saja lalu klik **`Generate Halaman, Beranda & Menu (1-Klik)`** agar otomatis terisi tabel/kartu dinamis v2.4.1.

### 3. Sudah Install Versi 2.0 / 2.1 → Upgrade ke v2.4.1
*(6 menu & halaman operasional sudah ada — upgrade hanya 4 langkah singkat)*
1. Hapus **6 file `.php` + folder `assets/` lama** di `wp-content/mu-plugins/` dan hapus isi `wp-content/cache/` (jika ada).
2. Unduh **[`pa-sinova-starter.zip`](https://github.com/kang-devlp/pa-wordpress-sinova-starter/raw/main/dist/pa-sinova-starter.zip)** → upload lewat **WP Admin → Plugins → Add New → Upload Plugin → Activate**.
3. Buka **SINOVA Hub → Tampilan Beranda → klik `Aktifkan Beranda Dinamis (1-Klik)`**.
4. *(Opsional)* Klik ikon **✎** pada blok **Jadwal Sidang & Live SIPP** → tempel URL `https://sipp.pa-satker.go.id/Slide_sidang` → **Terapkan** → **Simpan Tampilan Beranda**.

> *Tetap ingin di mode `mu-plugins`?* Cukup timpa isi `wp-content/mu-plugins/` dengan `wordpress-mu-plugins/` terbaru dari GitHub, hapus `wp-content/cache/`, aktifkan Beranda Dinamis, lalu klik **`Perbarui Baseline SHA-256`** di **SINOVA Hub → Keamanan & Update**.

### 4. Sudah Install Versi 2.2.0 ke Atas → Update 1-Klik
- **Mode Plugin `.zip`:** Klik **`Perbarui sekarang`** di menu **WP Admin → Plugins**, atau lewat **SINOVA Hub → Keamanan & Update → `Update 1-Klik dari GitHub`**.
- **Mode `mu-plugins`:** Buka **SINOVA Hub → Keamanan & Update → `Cek Pembaruan Sekarang` → `Update 1-Klik dari GitHub`**. Cache HTML, rollback backup, dan baseline SHA-256 diproses otomatis.

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

1. **Tampilan Publik Modern & Aksesibel (WCAG 2.2):** Header 2 baris + Mega Menu 4 kolom, 6 Menu Utama standar, Pencarian Kilat `Ctrl+K`, Mode Terang/Gelap, Jam WIB + status PTSP, Pita Info Layanan bergerak halus, Dock CCTV ACO fleksibel/dapat digeser, Kredit Footer dengan sorot versi, Panel Aksesibilitas & Pembaca Suara Bahasa Indonesia, tanpa Google Fonts CDN.
2. **Editor Grid Beranda 12 Kolom, Live SIPP & Direktori Putusan MA RI:** Atur seluruh susunan Beranda lewat **SINOVA Hub → Tampilan Beranda** (seret posisi & tarik ukuran 17 jenis blok: termasuk **Sematan Iframe / Aplikasi**, **Gambar / Poster / Banner**, **Slider Gambar / Infografis**, **Direktori Putusan Terbaru (MA RI)**, dan **Konten Bebas**). Cukup tempel URL profil satker `https://putusan3.mahkamahagung.go.id/pengadilan/profil/pengadilan/pa-sumedang.html` di **Identitas & Seeder** — halaman `/direktori-putusan/` terisi otomatis, umpan RSS & pencarian putusan satker aktif seketika! Tempel juga link `https://sipp.pa-satker.go.id/Slide_sidang` pada blok **Jadwal Sidang**, plus **API Push SIPP** (`POST /wp-json/pa-sinova/v1/sipp/push-hearings` + contoh [scripts/sipp-push-example.php](scripts/sipp-push-example.php)).
3. **23 Modul Arsip Dokumen & Publikasi Lengkap:** Kelola LRA, DIPA, LKJiP, LIPA 7, Sisa Panjar, LHKPN, Survei (PDF maks. 10 MB / Google Drive), **Berita & Pengumuman Satker dengan foto otomatis**, **Pengumuman 3 Kolom** (Satker, RSS MA RI, Badilag), **Slider Foto**, **Galeri Video**, **Ucapan & Belasungkawa**, **Wilayah Yurisdiksi**, serta **Profil Pegawai lengkap dengan modal detail**.
4. **Dashboard Audit 3 Pilar Ber-Tab & 35 Kriteria:** Panel admin bersih dengan ikon `(i)` ringkas, tab Pilar 1/2/3 + lencana hijau saat 100%, ekspor bukti **CSV & JSON**, serta pembagian peran operator (`pa_kepaniteraan`, `pa_kesekretariatan`, `pa_humas`).
5. **Keamanan Berlapis Bawaan:** CSP Nonce + Trusted Types, **MFA TOTP** (Google Authenticator/Aegis, QR dibuat lokal), **Sembunyikan URL Login Admin** (404 untuk `/wp-login.php`), Rate Limiter, WAF Anti-Judol, CAPTCHA offline / Turnstile, Rollback Update, dan *File Integrity Monitor (SHA-256)*.

---

## Memasang di Website Lama / Migrasi dari Joomla (Tanpa Hapus Berita)

- **WordPress Lama (Elementor / desain eksisting):** Setelah pasang plugin/`mu-plugins`, **wajib** buka **SINOVA Hub → Migrasi & Adopsi**, centang **"Pertahankan header, menu navigasi, dan beranda situs lama (Mode Non-Destruktif)"**, lalu klik **`Simpan Mode & Jalankan Seeder Aman`**.
- **Migrasi dari Joomla (1.5 s.d. 5.x):** Gunakan plugin gratis **`FG Joomla to WordPress`**, lalu aktifkan **Mode Adopsi** sebelum menjalankan Seeder.  
  📖 Panduan lengkap beserta solusi error umum: **[docs/PANDUAN-MIGRASI-JOOMLA-KE-WORDPRESS.md](docs/PANDUAN-MIGRASI-JOOMLA-KE-WORDPRESS.md)**.

---

## Keamanan Penting & Integrasi Opsional

- **MFA TOTP & Sembunyikan Login Admin:** Aktifkan di **SINOVA Hub → Keamanan & Update**.  
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
├── scripts/sipp-push-example.php          # Contoh skrip push otomatis jadwal sidang dari SIPP lokal
├── docs/                                  # Screenshot & panduan migrasi Joomla
└── laravel-sinova-panel/                  # Opsional: integrasi panel internal Laravel
```
