# Starter Kit Website Resmi Pengadilan Agama & Pengelolaan Dokumen Publik (SK Dirjen Badilag 2722/2026)

Paket *starter* **open source** (Lisensi MIT) untuk membantu satuan kerja **Pengadilan Agama** di Indonesia menyiapkan dan menata website resmi berbasis **WordPress** sesuai standar tata kelola informasi **SK Dirjen Badilag Nomor 2722/DJA/SK.TI1/X/2026**.

Seluruh fitur dikemas dalam 5 berkas `mu-plugins` tanpa ketergantungan plugin komersial. Paket ini dapat dipakai pada **website baru**, maupun diterapkan secara aman pada **website WordPress yang sudah berjalan** dan **hasil migrasi dari Joomla** tanpa menghapus berita ataupun arsip lama.

---

## Pratinjau Tampilan

### 1. Beranda Portal (Mode Terang & Akses CCTV ACO)
![Tampilan Beranda Mode Terang dan CCTV ACO](docs/screenshots/beranda-light.png)

### 2. Beranda Portal (Mode Gelap / Dark Mode)
![Tampilan Beranda Mode Gelap](docs/screenshots/beranda-dark.png)

### 3. Halaman Arsip Dokumen Publik & Sidebar Navigasi
![Tampilan Modul Arsip Dokumen Publik LRA](docs/screenshots/modul-sinova.png)

### 4. Ruang Operator: Dashboard Kepatuhan 35 Kriteria
![Tampilan Dashboard Kepatuhan 35 Kriteria di WP Admin](docs/screenshots/admin-dashboard-35.png)

### 5. Ruang Operator: Alat Migrasi & Adopsi Situs Lama
![Tampilan Tab Migrasi dan Adopsi Situs Lama](docs/screenshots/admin-migrasi-adopsi.png)

### 6. Ruang Operator: Keamanan Berlapis & Sembunyikan Login Admin
![Tampilan Pengaturan Keamanan dan Sembunyikan URL Login Admin](docs/screenshots/admin-keamanan-login.png)

---

## Ringkasan Kemampuan

1. **Tampilan Publik Bersih, Modern & Formal:** Seluruh istilah teknis penilaian (seperti nama aplikasi evaluator, nomor SK, maupun kode kriteria `B1.1–W4.1`) hanya muncul di ruang admin operator (**WP Admin → SINOVA Hub**) dan tidak ditampilkan ke masyarakat. Keterangan kartu, kutipan berita, dan tooltip navigasi **tampil ringkas saat sorot/hover atau fokus keyboard** sehingga beranda tetap lega dan rapi, disertai **efek parallax ringan berbasis GPU saat digulir**, **Mode Terang & Mode Gelap**, jam WIB langsung beserta **indikator status jam buka PTSP**, kotak pencarian header, breadcrumb & sidebar halaman dalam, kartu **Access CCTV Online (ACO)** berlogo resmi, indikator progres baca & tombol kembali ke atas, mode cetak rapi, serta **Sarana Disabilitas** (perbesar/perkecil teks, kontras tinggi, dan pembaca suara Bahasa Indonesia).
2. **Dashboard Kepatuhan 35 Kriteria di WP Admin:** Memeriksa otomatis kelengkapan `B1.1–W4.1`, menandai halaman yang masih memuat placeholder `[LENGKAPI]`, serta menyediakan tombol **Salin URL** dan pemetaan URL halaman lama.
3. **23 Modul Arsip Dokumen Terstruktur (Slot s.d. 2027):** Mendukung pengunggahan **PDF langsung ke server** maupun penautan **Google Drive** berdasarkan tahun, kategori (DIPA 01/04, LIPA 7A/7B/7C), bulan, semester, atau triwulan (SKM/SPAK/SPKP) yang tertaut otomatis ke panel indeks survei Beranda.
4. **Dukungan Migrasi & Adopsi Situs Lama (Non-Destruktif):**
   * **Mode Adopsi Situs Lama:** Memasang mesin arsip dokumen, keamanan WAF, cache halaman, dan pemantauan 35 kriteria tanpa menimpa header, menu, ataupun Beranda website WordPress yang sudah aktif.
   * **Alat Pindai & Impor Tautan:** Membaca tautan Google Drive/PDF dari halaman lama WordPress maupun artikel hasil impor Joomla ke dalam arsip terstruktur.
5. **Pengamanan Berlapis & Performa:**
   * **Sembunyikan Halaman Login Admin (Custom Login URL):** Memindahkan halaman login operator ke slug khusus satker, menghapus tautan login publik di topbar, serta mengembalikan `404 Not Found` pada percobaan akses langsung `/wp-login.php`, `/wp-admin/` anonim, maupun `/admin`.
   * **Proteksi Aktif:** Cache HTML statis halaman publik, penghitung kunjungan aman privasi (hash IP anonim harian), pembatasan login gagal (*Brute-Force Rate Limiter* 5x/15 menit), header keamanan OWASP (`Permissions-Policy`, `HSTS`, `nosniff`, `SAMEORIGIN`), proteksi folder unggahan `.htaccess`, blokir XML-RPC dan enumerasi pengguna, WAF konten/URI terlarang, serta CAPTCHA login ganda (**Cloudflare Turnstile** atau soal hitung offline).

---

## Struktur Berkas

```text
sinova-starter-kit/
├── README.md
├── LICENSE
├── .gitignore
├── wordpress-mu-plugins/                  # Salin ke wp-content/mu-plugins/
│   ├── 00-pa-sinova-config.php            # Pengaturan satker, Seeder 1-Klik, Dashboard 35 Kriteria, & Alat Migrasi
│   ├── pa-sinova-hub.php                  # Mesin 23 modul dokumen, shortcode [pa_sinova_docs], & REST API HMAC
│   ├── pa-home-page-cache.php             # Cache HTML halaman publik & shortcode [waktu_render]
│   ├── pa-ui-performance-a11y.php         # Tema portal Astra/blok, statistik pengunjung, & sarana disabilitas
│   └── pa-security-hardening.php          # Rate limiter, OWASP headers, WAF, proteksi upload, & CAPTCHA login
├── wordpress-templates/
│   └── beranda-blocks-starter.html        # Kerangka HTML halaman Beranda standar Pengadilan Agama
├── docs/
│   └── screenshots/                       # Pratinjau tampilan
└── laravel-sinova-panel/                  # Opsional: integrasi jika kantor memakai aplikasi internal Laravel
```

---

## Skenario A: Pemasangan pada WordPress Baru

Cocok bagi satuan kerja yang membangun portal dari awal di hosting cPanel/DirectAdmin, VPS, maupun Laragon/XAMPP lokal.

### Prasyarat
* WordPress baru terpasang (PHP 8.1+ dan MySQL/MariaDB).
* Pasang dan aktifkan tema gratis **Astra** melalui **WP Admin → Appearance → Themes → Add New → cari "Astra" → Install & Activate**.

### Langkah 1: Pasang Folder `mu-plugins`
1. Buka direktori instalasi WordPress Anda (`public_html/wp-content/` atau folder proyek lokal).
2. Buat folder **`mu-plugins`** jika belum tersedia.
3. Salin seluruh berkas `.php` dari `wordpress-mu-plugins/` ke dalam `wp-content/mu-plugins/`.
   *(Seluruh berkas di `mu-plugins` aktif otomatis tanpa perlu tombol Activate.)*

### Langkah 2: Atur Struktur Permalink
1. Buka **WP Admin → Settings (Pengaturan) → Permalinks**.
2. Pilih **Post name** (`/sample-post/`), lalu klik **Save Changes**.

### Langkah 3: Isi Identitas Satker & Jalankan Seeder 1-Klik
1. Buka **WP Admin → SINOVA Hub → tab Identitas Satker & Seeder 1-Klik**.
2. Lengkapi **Nama Satker**, URL logo (opsional), alamat kantor, telepon/WA PTSP, email, jam sidang, sambutan pimpinan, serta tautan **SIPP**, **e-Court**, **Direktori Putusan**, **Survei Pelayanan**, dan **URL CCTV Online (ACO)** (`https://cctv.badilag.net/` atau tautan ruangan PTSP satker), lalu klik **Simpan Pengaturan**.
3. Klik **"Generate Halaman, Beranda & Menu (1-Klik)"** untuk membuat 23 halaman modul dokumen, 17 halaman profil/wajib bertanda `[LENGKAPI]`, halaman Beranda, statistik pengunjung, 3 artikel awal, serta `Menu Utama` pada Astra.

### Langkah 4: Kelola Dokumen & Lengkapi Halaman Profil
1. **Dokumen Rutin (Bulanan/Triwulanan/Tahunan):** Buka tab **Upload & Kelola Dokumen**, pilih modul dan periode slot (hingga 2027), unggah PDF (maks. 10 MB) atau tempel tautan Google Drive (*Anyone with the link*), lalu simpan.
2. **Halaman Profil Statis:** Buka **WP Admin → Pages (Laman)**, sunting halaman yang bertanda `[LENGKAPI: ...]` (*Visi dan Misi*, *Struktur Organisasi*, *Wilayah Yurisdiksi*, *Prosedur Berperkara*, dll.). Pantau kemajuannya di tab **Dashboard 35 Kriteria**.
3. **Tautan Zona Integritas Beranda:** Sunting halaman **Beranda** dan ganti `href="#ganti-link-area-1"` s.d. `area-6` ke folder Google Drive masing-masing area.

---

## Keamanan Lanjutan: Menyembunyikan Halaman Login Admin

Secara bawaan, starter kit **tidak menampilkan tautan Login Admin** pada topbar publik website agar halaman masuk tidak terekspos secara terbuka. Untuk pengamanan tambahan dari serangan otomatis terhadap `/wp-login.php` maupun `/wp-admin/`, operator dapat memindahkan halaman masuk ke alamat rahasia milik satker:

1. Buka **WP Admin → SINOVA Hub → tab Keamanan & Audit Log**.
2. Pada panel **Sembunyikan Halaman Login Admin (Custom Login URL)**, ketikkan slug rahasia (minimal 4 karakter huruf kecil/angka/tanda hubung, misalnya `gerbang-operator-pa`), lalu klik **Simpan Pengaturan URL Login**.
3. **Simpan (bookmark) URL baru tersebut** di peramban operator (misalnya `https://pa-satker.go.id/gerbang-operator-pa/`).
4. Setelah aktif:
   * Seluruh tautan masuk, keluar (*logout*), dan lupa kata sandi WordPress otomatis menggunakan slug rahasia tersebut.
   * Permintaan langsung ke `/wp-login.php`, akses anonim ke `/wp-admin/`, serta pengalihan bawaan WordPress (`/admin`, `/login`) akan mengembalikan halaman **`404 Tidak Ditemukan`** sehingga tidak membocorkan letak halaman login.
   * Rate limiter (5x gagal / 15 menit) dan CAPTCHA (Turnstile / soal matematika offline) tetap aktif pada URL login tersembunyi.
5. **Cara Pemulihan Darurat (jika lupa slug login):**
   * Masuk melalui *File Manager* cPanel, FTP, atau SSH, lalu buka berkas `wp-config.php`.
   * Tambahkan baris berikut tepat di atas komentar `/* That's all, stop editing! */`:
     ```php
     define('PA_DISABLE_CUSTOM_LOGIN', true);
     ```
   * Login melalui `/wp-login.php`, buka kembali tab **Keamanan & Audit Log** untuk memperbarui atau mengosongkan slug, lalu hapus baris tersebut dari `wp-config.php`.

---

## Skenario B: Penerapan Aman pada WordPress Lama (Tanpa Kehilangan Data)

Jika satuan kerja sudah memiliki website WordPress berisi berita, galeri, serta tata letak Elementor/Astra yang tidak ingin diubah tampilannya, Anda tetap dapat memakai fitur arsip dokumen, dashboard kepatuhan, dan keamanannya secara aman:

1. **Cadangkan Website Terlebih Dahulu:** Ekspor database melalui phpMyAdmin (`.sql`) dan simpan salinan folder `wp-content/` sebelum menambahkan berkas baru.
2. **Pasang Folder `mu-plugins`:** Salin 5 berkas `.php` dari `wordpress-mu-plugins/` ke `wp-content/mu-plugins/`.
3. **Aktifkan Mode Adopsi Situs Lama (Wajib sebelum menjalankan Seeder):**
   * Buka **WP Admin → SINOVA Hub → tab Migrasi & Adopsi**.
   * Centang opsi **"Pertahankan header, menu navigasi, dan beranda situs lama (Mode Non-Destruktif)"**, lalu klik **Simpan Mode & Jalankan Seeder Aman**.
   * Dengan mode ini aktif: berita lama tetap utuh, header dan Beranda lama tidak diubah, dan sistem hanya menambahkan halaman modul yang belum ada (bila slug yang sama sudah tersedia—baik di root maupun sebagai sub-halaman—sistem langsung mengenalinya tanpa menduplikasi).
4. **Petakan URL Halaman Lama ke Dashboard 35 Kriteria:**
   * Jika halaman lama memakai alamat berbeda (misalnya `/profil/visi-misi/` atau `/keuangan/laporan-lra/`), pilih halaman tersebut pada kolom **Pemetaan Halaman Lama** di tab **Migrasi & Adopsi**, lalu simpan agar **Dashboard 35 Kriteria** menilai dan menyalin URL yang benar.
5. **Pindahkan atau Gabungkan Arsip Dokumen Lama:**
   * **Cara Bertahap:** Tempelkan shortcode `[pa_sinova_docs slug="lra"]` (atau slug modul lain) di bagian atas konten halaman lama untuk dokumen tahun berjalan ke atas, sehingga tabel arsip lama di bagian bawah tetap tampil apa adanya.
   * **Cara Terstruktur (Impor Otomatis):** Pada tab **Migrasi & Adopsi**, pilih halaman lama di formulir **Ekstrak & Impor Tautan Dokumen**, klik **Pindai Tautan**, periksa hasil deteksi tahun & bulan pada pratinjau, lalu klik **Impor Terpilih** agar seluruh tautan masuk rapi ke database arsip.

---

## Skenario C: Migrasi Bertahap dari Joomla ke WordPress

Untuk satuan kerja yang ingin beralih dari **Joomla (3.x / 4.x)** ke WordPress agar konten berita dan arsip pengumuman bertahun-tahun tetap terbawa utuh:

1. **Kerjakan di Lingkungan Uji (*Staging* / Lokal):**
   * Jangan mematikan website Joomla yang sedang online. Siapkan WordPress baru beserta tema gratis **Astra** di server lokal (*Laragon/XAMPP*) atau subdomain sementara.
2. **Impor Artikel, Kategori, dan Media Joomla:**
   * Pasang plugin gratis resmi **`FG Joomla to WordPress`** dari menu **WP Admin → Plugins → Add New**.
   * Masukkan kredensial koneksi *read-only* database MySQL Joomla lama (prefix tabel umumnya `jos_` atau sesuai konfigurasi Joomla Anda) serta URL website Joomla asal untuk menarik seluruh berita, halaman profil, kategori, dan gambar ke WordPress beserta tanggal terbit aslinya.
   * Setelah selesai memindahkan konten, nonaktifkan dan hapus plugin importer agar WordPress tetap ringan.
3. **Salin Berkas PDF Lokal & Jaga Tautan Lama (*301 Redirect*):**
   * Salin folder dokumen lama dari server Joomla (umumnya di direktori `images/` atau `files/`) ke path yang sama pada WordPress agar tautan PDF di dalam berita lama tetap dapat diunduh.
   * Bila alamat halaman utama berubah (misalnya dari `/index.php/profil/visi-misi` ke `/visi-dan-misi/`), pasang aturan *Redirect 301* melalui `.htaccess` atau plugin *Redirection* supaya tautan dari mesin pencari tidak menghasilkan halaman tidak ditemukan (`404`).
4. **Pasang Starter Kit & Rapikan Arsip:**
   * Salin 5 berkas `wordpress-mu-plugins/` ke `wp-content/mu-plugins/`.
   * Jalankan **Seeder 1-Klik** (atau gunakan **Tab Migrasi & Adopsi** untuk memindai tautan Google Drive/PDF yang ikut terbawa di artikel hasil impor Joomla agar masuk ke 23 modul dokumen terstruktur).
   * Berita lama hasil migrasi Joomla secara otomatis muncul pada blok **Berita Terkini** di Beranda dan terindeks oleh fitur pencarian website.
5. **Pengujian Sebelum Pengalihan Domain (*Cut-Over*):**
   * Periksa seluruh tautan melalui **WP Admin → SINOVA Hub → Dashboard 35 Kriteria**, pastikan SSL/HTTPS aktif pada domain produksi, barulah pindahkan WordPress hasil migrasi ke domain utama.

---

## Opsional: Menghubungkan Aplikasi Internal Berbasis Laravel

Jika satuan kerja memiliki aplikasi manajemen internal terpisah berbasis Laravel + Inertia Vue 3 untuk mengelola unggahan dokumen dari jaringan internal kantor:
1. Salin isi folder `laravel-sinova-panel/` ke proyek Laravel Anda (`app/`, `database/migrations/`, `resources/js/Pages/`, dan `routes/`).
2. Jalankan migrasi tabel:
   ```bash
   php artisan migrate
   ```
3. Tambahkan konfigurasi di `.env` Laravel (samakan `SINOVA_WP_SECRET_KEY` dengan kunci pada **WP Admin → SINOVA Hub**):
   ```env
   SINOVA_WP_BASE_URL=https://domain-satker-anda.go.id
   SINOVA_WP_SECRET_KEY=isi_secret_key_hmac_minimal_32_karakter
   SINOVA_WP_TIMEOUT=30
   ```
4. Jalankan `npm run build`, buka route `/update-website`, lalu lakukan sinkronisasi modul.

---

## Daftar Periksa Sebelum Publikasi Online
- Pastikan sertifikat **HTTPS/SSL** aktif dan valid di domain produksi.
- Wajibkan **Autentikasi Dua Faktor (2FA)** pada seluruh akun Administrator WordPress.
- Bila memakai **Cloudflare Turnstile**, isikan *Site Key* dan *Secret Key* pada **WP Admin → SINOVA Hub** (bila dikosongkan, sistem otomatis memakai CAPTCHA hitung matematika offline + honeypot).
- Pastikan akses database MariaDB/MySQL hanya mendengarkan koneksi lokal (`bind-address = 127.0.0.1`) dan jadwalkan pencadangan database serta `wp-content/uploads/` secara berkala.
