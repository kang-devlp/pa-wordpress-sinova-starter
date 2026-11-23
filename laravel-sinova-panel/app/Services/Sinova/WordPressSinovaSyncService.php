<?php

namespace App\Services\Sinova;

use App\Models\SinovaWebsiteDocument;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class WordPressSinovaSyncService
{
    public const MONTHS = [
        'JAN' => ['label' => 'Januari',   'order' => 1],
        'FEB' => ['label' => 'Februari',  'order' => 2],
        'MAR' => ['label' => 'Maret',     'order' => 3],
        'APR' => ['label' => 'April',     'order' => 4],
        'MEI' => ['label' => 'Mei',       'order' => 5],
        'JUN' => ['label' => 'Juni',      'order' => 6],
        'JUL' => ['label' => 'Juli',      'order' => 7],
        'AGS' => ['label' => 'Agustus',   'order' => 8],
        'SEP' => ['label' => 'September', 'order' => 9],
        'OKT' => ['label' => 'Oktober',   'order' => 10],
        'NOV' => ['label' => 'November',  'order' => 11],
        'DES' => ['label' => 'Desember',  'order' => 12],
    ];

    public const ALL_PERIODS = [
        'JAN'     => ['label' => 'Januari',            'order' => 1],
        'FEB'     => ['label' => 'Februari',           'order' => 2],
        'MAR'     => ['label' => 'Maret',              'order' => 3],
        'APR'     => ['label' => 'April',              'order' => 4],
        'MEI'     => ['label' => 'Mei',                'order' => 5],
        'JUN'     => ['label' => 'Juni',               'order' => 6],
        'JUL'     => ['label' => 'Juli',               'order' => 7],
        'AGS'     => ['label' => 'Agustus',            'order' => 8],
        'SEP'     => ['label' => 'September',          'order' => 9],
        'OKT'     => ['label' => 'Oktober',            'order' => 10],
        'NOV'     => ['label' => 'November',           'order' => 11],
        'DES'     => ['label' => 'Desember',           'order' => 12],
        'SEM1'    => ['label' => 'Semester I',         'order' => 1],
        'SEM2'    => ['label' => 'Semester II',        'order' => 2],
        'TAHUNAN' => ['label' => 'Dokumen Utama',      'order' => 1],
        'REVISI'  => ['label' => 'Revisi / Perubahan', 'order' => 2],
        'REVIU'   => ['label' => 'Reviu / Evaluasi',   'order' => 3],
        'TW1'     => ['label' => 'Triwulan I',         'order' => 1],
        'TW2'     => ['label' => 'Triwulan II',        'order' => 2],
        'TW3'     => ['label' => 'Triwulan III',       'order' => 3],
        'TW4'     => ['label' => 'Triwulan IV',        'order' => 4],
    ];

    public const CATEGORIES = [
        'Keuangan & Anggaran',
        'Kepaniteraan & Biaya Perkara',
        'Perencanaan & SAKIP',
        'Layanan Publik, PPID & Kepatuhan',
    ];

    public const MODULES = [
        // === KEUANGAN & ANGGARAN ===
        'lra' => [
            'slug' => 'lra',
            'category' => 'Keuangan & Anggaran',
            'title' => 'Laporan Realisasi Anggaran (LRA)',
            'short' => 'LRA',
            'unit' => 'Umum & Keuangan',
            'wp_post_id' => 5356,
            'public_url' => '/lra/',
            'sub_groups' => ['SERAPAN 01', 'SERAPAN 04'],
            'periods' => 'months',
            'start_year' => 2022,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'pnbp' => [
            'slug' => 'pnbp',
            'category' => 'Keuangan & Anggaran',
            'title' => 'Realisasi PNBP',
            'short' => 'PNBP',
            'unit' => 'Umum & Keuangan',
            'wp_post_id' => 6150,
            'public_url' => '/kesekretariatan/realisasi-penerimaan-negara-bukan-pajak-pnbp/',
            'sub_groups' => ['DOKUMEN SERAPAN 01', 'DOKUMEN SERAPAN 04'],
            'periods' => 'months',
            'start_year' => 2020,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'neraca' => [
            'slug' => 'neraca',
            'category' => 'Keuangan & Anggaran',
            'title' => 'Neraca Keuangan',
            'short' => 'Neraca',
            'unit' => 'Umum & Keuangan',
            'wp_post_id' => 6147,
            'public_url' => '/kesekretariatan/neraca-keuangan/',
            'sub_groups' => ['DIPA 01', 'DIPA 04'],
            'periods' => 'months',
            'start_year' => 2022,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'calk' => [
            'slug' => 'calk',
            'category' => 'Keuangan & Anggaran',
            'title' => 'Catatan Atas Laporan Keuangan (CALK)',
            'short' => 'CALK',
            'unit' => 'Umum & Keuangan',
            'wp_post_id' => 6015,
            'public_url' => '/kesekretariatan/catatan-atas-laporan-keuangan-calk/',
            'sub_groups' => ['DIPA 01', 'DIPA 04'],
            'periods' => ['SEM1', 'SEM2'],
            'start_year' => 2019,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'dipa' => [
            'slug' => 'dipa',
            'category' => 'Keuangan & Anggaran',
            'title' => 'Daftar Isian Pelaksanaan Anggaran (DIPA)',
            'short' => 'DIPA',
            'unit' => 'Umum & Keuangan',
            'wp_post_id' => 5959,
            'public_url' => '/daftar-isian-pelaksanaan-anggaran-dipa/',
            'sub_groups' => ['DIPA 01 (Badan Urusan Administrasi)', 'DIPA 04 (Ditjen Badilag)'],
            'periods' => ['TAHUNAN', 'REVISI'],
            'start_year' => 2020,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'aset' => [
            'slug' => 'aset',
            'category' => 'Keuangan & Anggaran',
            'title' => 'Aset & Inventaris (BMN)',
            'short' => 'Aset BMN',
            'unit' => 'Umum & Keuangan',
            'wp_post_id' => 6158,
            'public_url' => '/kesekretariatan/asset-dan-inventaris/',
            'sub_groups' => ['LAPORAN ASET (BMN)', 'LAPORAN INVENTARIS'],
            'periods' => ['TAHUNAN', 'REVISI'],
            'start_year' => 2020,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'pbj' => [
            'slug' => 'pbj',
            'category' => 'Keuangan & Anggaran',
            'title' => 'Pengadaan Barang & Jasa (RUP)',
            'short' => 'PBJ / RUP',
            'unit' => 'Umum & Keuangan',
            'wp_post_id' => 6191,
            'public_url' => '/kesekretariatan/pengadaan-barang-dan-jasa/',
            'sub_groups' => ['DIPA 01', 'DIPA 04', 'RUP TAHUNAN'],
            'periods' => ['TAHUNAN', 'REVISI'],
            'start_year' => 2018,
            'end_year' => 2027,
            'pilot_active' => true,
        ],

        // === KEPANITERAAN & BIAYA PERKARA ===
        'biaya-perkara' => [
            'slug' => 'biaya-perkara',
            'category' => 'Kepaniteraan & Biaya Perkara',
            'title' => 'Laporan Penggunaan Biaya Perkara (LIPA 7)',
            'short' => 'LIPA 7',
            'unit' => 'Kepaniteraan',
            'wp_post_id' => 5282,
            'public_url' => '/kepaniteraan/laporan-penggunaan-biaya-perkara/',
            'sub_groups' => ['LIPA 7A (Keuangan Perkara)', 'LIPA 7B (Biaya Proses / ATK)', 'LIPA 7C (Hak Kepaniteraan)'],
            'periods' => 'months',
            'start_year' => 2020,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'sisa-panjar' => [
            'slug' => 'sisa-panjar',
            'category' => 'Kepaniteraan & Biaya Perkara',
            'title' => 'Pengembalian Sisa Panjar Perkara',
            'short' => 'Sisa Panjar',
            'unit' => 'Kepaniteraan',
            'wp_post_id' => 5292,
            'public_url' => '/kepaniteraan/pengembalian-sisa-panjar/',
            'sub_groups' => ['LAPORAN PENGEMBALIAN SISA PANJAR'],
            'periods' => 'months',
            'start_year' => 2021,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'biaya-prodeo' => [
            'slug' => 'biaya-prodeo',
            'category' => 'Kepaniteraan & Biaya Perkara',
            'title' => 'Rincian Biaya Perkara Prodeo',
            'short' => 'Prodeo',
            'unit' => 'Kepaniteraan',
            'wp_post_id' => 5326,
            'public_url' => '/kepaniteraan/berperkara-tanpa-biaya/rincian-biaya-prodeo/',
            'sub_groups' => ['RINCIAN BIAYA PERKARA PRODEO'],
            'periods' => 'months',
            'start_year' => 2020,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'hak-kepaniteraan' => [
            'slug' => 'hak-kepaniteraan',
            'category' => 'Kepaniteraan & Biaya Perkara',
            'title' => 'Biaya Hak-Hak Kepaniteraan (LIPA 17 & 18)',
            'short' => 'LIPA 17/18',
            'unit' => 'Kepaniteraan',
            'wp_post_id' => 5269,
            'public_url' => '/kepaniteraan/biaya-hak-kepaniteraan/',
            'sub_groups' => ['LIPA 17 (Penerimaan Hak Kepaniteraan)', 'LIPA 18 (Penerimaan PNBP Lainnya)'],
            'periods' => 'months',
            'start_year' => 2020,
            'end_year' => 2027,
            'pilot_active' => true,
        ],

        // === PERENCANAAN & SAKIP ===
        'lkjip' => [
            'slug' => 'lkjip',
            'category' => 'Perencanaan & SAKIP',
            'title' => 'Laporan Kinerja Instansi Pemerintah (LKJiP)',
            'short' => 'LKJiP',
            'unit' => 'PTIP',
            'wp_post_id' => 5983,
            'public_url' => '/kesekretariatan/laporan-akuntabilitas-kinerja-pemerintah-lkjip/',
            'sub_groups' => ['DOKUMEN LKJIP'],
            'periods' => ['TAHUNAN', 'REVISI'],
            'start_year' => 2017,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'iku' => [
            'slug' => 'iku',
            'category' => 'Perencanaan & SAKIP',
            'title' => 'Indikator Kinerja Utama (IKU) & Reviu',
            'short' => 'IKU',
            'unit' => 'PTIP',
            'wp_post_id' => 5975,
            'public_url' => '/kesekretariatan/indikator-kinerja-utama-iku/',
            'sub_groups' => ['DOKUMEN IKU'],
            'periods' => ['TAHUNAN', 'REVIU', 'REVISI'],
            'start_year' => 2020,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'pk' => [
            'slug' => 'pk',
            'category' => 'Perencanaan & SAKIP',
            'title' => 'Perjanjian Kinerja (PK)',
            'short' => 'PK',
            'unit' => 'PTIP',
            'wp_post_id' => 5967,
            'public_url' => '/kesekretariatan/perjanjian-kinerja-pk/',
            'sub_groups' => ['DOKUMEN PERJANJIAN KINERJA'],
            'periods' => ['TAHUNAN', 'REVISI', 'REVIU'],
            'start_year' => 2020,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'rkt' => [
            'slug' => 'rkt',
            'category' => 'Perencanaan & SAKIP',
            'title' => 'Rencana Kinerja Tahunan (RKT)',
            'short' => 'RKT',
            'unit' => 'PTIP',
            'wp_post_id' => 6210,
            'public_url' => '/kesekretariatan/rencana-kinerja-tahunan-rkt/',
            'sub_groups' => ['DOKUMEN RKT'],
            'periods' => ['TAHUNAN', 'REVISI'],
            'start_year' => 2020,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'rencana-aksi' => [
            'slug' => 'rencana-aksi',
            'category' => 'Perencanaan & SAKIP',
            'title' => 'Rencana Aksi Kinerja',
            'short' => 'Rencana Aksi',
            'unit' => 'PTIP',
            'wp_post_id' => 6218,
            'public_url' => '/kesekretariatan/rencana-aksi-kinerja/',
            'sub_groups' => ['DOKUMEN RENCANA AKSI'],
            'periods' => ['TAHUNAN', 'REVISI'],
            'start_year' => 2020,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'renstra' => [
            'slug' => 'renstra',
            'category' => 'Perencanaan & SAKIP',
            'title' => 'Rencana Strategis (Renstra)',
            'short' => 'Renstra',
            'unit' => 'PTIP',
            'wp_post_id' => 6201,
            'public_url' => '/kesekretariatan/rencana-strategis-renstra/',
            'sub_groups' => ['DOKUMEN RENSTRA'],
            'periods' => ['TAHUNAN', 'REVISI', 'REVIU'],
            'start_year' => 2018,
            'end_year' => 2027,
            'pilot_active' => true,
        ],

        // === LAYANAN PUBLIK, PPID & KEPATUHAN ===
        'akses-informasi' => [
            'slug' => 'akses-informasi',
            'category' => 'Layanan Publik, PPID & Kepatuhan',
            'title' => 'Laporan Akses Informasi Publik',
            'short' => 'Akses Informasi',
            'unit' => 'PPID / Hukum',
            'wp_post_id' => 5760,
            'public_url' => '/layanan-publik/laporan-akses-informasi/',
            'sub_groups' => ['LAPORAN AKSES INFORMASI'],
            'periods' => 'months',
            'start_year' => 2022,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'rekap-pengaduan' => [
            'slug' => 'rekap-pengaduan',
            'category' => 'Layanan Publik, PPID & Kepatuhan',
            'title' => 'Laporan Rekapitulasi Pengaduan',
            'short' => 'Pengaduan',
            'unit' => 'Hukum & Pengaduan',
            'wp_post_id' => 5788,
            'public_url' => '/layanan-publik/laporan-rekapitulasi-pengaduan/',
            'sub_groups' => ['REKAPITULASI PENGADUAN'],
            'periods' => 'months',
            'start_year' => 2022,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'mou' => [
            'slug' => 'mou',
            'category' => 'Layanan Publik, PPID & Kepatuhan',
            'title' => 'Arsip Surat Perjanjian (MoU & PKS)',
            'short' => 'MoU / PKS',
            'unit' => 'Hukum & Umum',
            'wp_post_id' => 6722,
            'public_url' => '/publikasi/mou/',
            'sub_groups' => ['MOU PEMDA & INSTANSI', 'PKS POSBAKUM & MEDIASI', 'PKS LAYANAN & LAINNYA'],
            'periods' => 'months',
            'start_year' => 2021,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'lhkpn' => [
            'slug' => 'lhkpn',
            'category' => 'Layanan Publik, PPID & Kepatuhan',
            'title' => 'Rekapitulasi LHKPN Pejabat Wajib Lapor',
            'short' => 'LHKPN',
            'unit' => 'Kepegawaian & Ortala',
            'wp_post_id' => 5380,
            'public_url' => '/kesekretariatan/lhkpn/',
            'sub_groups' => ['REKAP & BUKTI LHKPN'],
            'periods' => ['TAHUNAN', 'REVISI'],
            'start_year' => 2019,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'lhkasn' => [
            'slug' => 'lhkasn',
            'category' => 'Layanan Publik, PPID & Kepatuhan',
            'title' => 'Rekapitulasi LHKASN / SPT Tahunan ASN',
            'short' => 'LHKASN / SPT',
            'unit' => 'Kepegawaian & Ortala',
            'wp_post_id' => 5394,
            'public_url' => '/kesekretariatan/lhkasn/',
            'sub_groups' => ['REKAP & SPT LHKASN'],
            'periods' => ['TAHUNAN', 'REVISI'],
            'start_year' => 2022,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
        'survei-pelayanan' => [
            'slug' => 'survei-pelayanan',
            'category' => 'Layanan Publik, PPID & Kepatuhan',
            'title' => 'Hasil Survei Pelayanan Publik (SKM, SPAK & SPKP)',
            'short' => 'Survei SKM/SPAK/SPKP',
            'unit' => 'Hukum & Pelayanan Publik',
            'wp_post_id' => 7978,
            'public_url' => '/layanan-publik/survei-pelayanan-masyarakat/',
            'sub_groups' => [
                'SKM (Indeks Kepuasan Masyarakat)',
                'SPAK (Survei Persepsi Anti Korupsi)',
                'SPKP (Survei Persepsi Kualitas Pelayanan)',
            ],
            'periods' => ['TW1', 'TW2', 'TW3', 'TW4'],
            'start_year' => 2024,
            'end_year' => 2027,
            'pilot_active' => true,
        ],
    ];

    public static function periodsForModule(string $slug): array
    {
        $mod = self::MODULES[$slug] ?? null;
        if (! $mod) {
            return collect(self::MONTHS)->map(fn ($v, $k) => [
                'key' => $k,
                'label' => $v['label'],
                'order' => $v['order'],
            ])->values()->all();
        }

        if (($mod['periods'] ?? 'months') === 'months') {
            return collect(self::MONTHS)->map(fn ($v, $k) => [
                'key' => $k,
                'label' => $v['label'],
                'order' => $v['order'],
            ])->values()->all();
        }

        $out = [];
        foreach ((array) $mod['periods'] as $idx => $key) {
            $k = strtoupper((string) $key);
            if (isset(self::ALL_PERIODS[$k])) {
                $out[] = [
                    'key' => $k,
                    'label' => self::ALL_PERIODS[$k]['label'],
                    'order' => $idx + 1,
                ];
            }
        }

        return $out;
    }

    public static function roadmapGroups(): array
    {
        $owners = [
            'Keuangan & Anggaran' => 'Subbag Umum & Keuangan',
            'Kepaniteraan & Biaya Perkara' => 'Kepaniteraan',
            'Perencanaan & SAKIP' => 'Subbag PTIP',
            'Layanan Publik, PPID & Kepatuhan' => 'Kepegawaian, Hukum & PPID',
        ];

        $groups = [];
        foreach (self::CATEGORIES as $cat) {
            $items = [];
            foreach (self::MODULES as $mod) {
                if ($mod['category'] !== $cat) {
                    continue;
                }
                $items[] = [
                    'slug' => $mod['slug'],
                    'label' => $mod['title'],
                    'post_id' => $mod['wp_post_id'],
                    'status' => 'Aktif (Live s.d. 2027)',
                ];
            }
            if ($cat === 'Kepaniteraan & Biaya Perkara') {
                $items[] = ['slug' => 'statistik-perkara', 'label' => 'Statistik Perkara', 'post_id' => 5165, 'status' => 'Embed Otomatis'];
                $items[] = ['slug' => 'mediator', 'label' => 'Daftar Mediator & Jadwal Sidang', 'post_id' => 5140, 'status' => 'Terhubung SIPP'];
            }
            if ($cat === 'Perencanaan & SAKIP') {
                $items[] = ['slug' => 'lap-tahunan', 'label' => 'Laporan Tahunan & Program Kerja', 'post_id' => 5008, 'status' => 'Portal Halaman'];
            }
            if ($cat === 'Layanan Publik, PPID & Kepatuhan') {
                $items[] = ['slug' => 'skm-rentan-inovasi', 'label' => 'SKM/SPAK/SPKP, Kelompok Rentan & Inovasi', 'post_id' => 7978, 'status' => 'Portal Khusus'];
            }

            $groups[] = [
                'category' => $cat,
                'owner' => $owners[$cat] ?? 'Admin SINOVA',
                'items' => $items,
            ];
        }

        return $groups;
    }

    /**
     * Definisi resmi 35 Kriteria Lampiran II SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026 (Tingkat Pertama / PA).
     * Mengembalikan array 35 item: [code, group, label, unit, evidence_url, post_id, module_slug, check_mode, default_note].
     */
    public static function complianceCriteriaDefinitions(): array
    {
        return [
            // A.1 INFORMASI BERKALA (24 kriteria, bobot 40%)
            ['B1.1', 'berkala', 'Rencana Aksi Kinerja (5 tahun)', 'PTIP', '/kesekretariatan/rencana-aksi-kinerja/', 6218, 'rencana-aksi', 'sakip_5y', 'Arsip 5 tahunan + slot 2027'],
            ['B1.2', 'berkala', 'Laporan Tahunan / Pelaksanaan Kegiatan', 'PTIP', '/informasi-umum/laporan-tahunan/', 5008, null, 'page_ok', 'Halaman Laporan Tahunan Satker Pengadilan Agama aktif (HTTP 200)'],
            ['B2.1', 'berkala', 'Tautan & Integrasi SIPP Satker', 'Kepaniteraan', 'https://sipp.pa-contoh.go.id/', 3857, null, 'page_ok', 'Tautan SIPP aktif pada Beranda & Mega Menu'],
            ['B2.2', 'berkala', 'Agenda / Jadwal Persidangan Harian', 'Kepaniteraan', '/kepaniteraan/agenda-jadwal-persidangan/', 5135, null, 'page_ok', 'Terhubung langsung ke jadwal sidang SIPP'],
            ['B2.3', 'berkala', 'Statistik Perkara per Bulan', 'Kepaniteraan', '/kepaniteraan/statistik-perkara/', 5165, null, 'page_ok', 'Embed statistik perkara Portal Internal aktif'],
            ['B2.4', 'berkala', 'Daftar Panggilan Ghaib', 'Kepaniteraan', '/tag/ghaib/', 7494, null, 'page_ok', 'Arsip tag pengumuman Panggilan Ghaib aktif'],
            ['B2.5', 'berkala', 'Laporan Penggunaan Biaya Perkara (LIPA 7)', 'Kepaniteraan', '/kepaniteraan/laporan-penggunaan-biaya-perkara/', 5282, 'biaya-perkara', 'monthly', 'Wajib diperbarui bulanan (LIPA 7A/7B/7C)'],
            ['B2.6', 'berkala', 'Laporan Pengembalian Sisa Panjar Perkara', 'Kepaniteraan', '/kepaniteraan/pengembalian-sisa-panjar/', 5292, 'sisa-panjar', 'monthly', 'Wajib diperbarui bulanan'],
            ['B3.1', 'berkala', 'LHKPN & LHKASN Pejabat Wajib Lapor', 'Kepegawaian & Ortala', '/kesekretariatan/lhkpn/', 5380, 'lhkpn', 'hybrid_lhkpn', 'Tabel arsip pegawai aktif; slot Rekap Kolektif 2026â€“2027 di Portal Internal'],
            ['B3.2', 'berkala', 'DIPA, Rencana Penggunaan & Realisasi Anggaran', 'Umum & Keuangan', '/daftar-isian-pelaksanaan-anggaran-dipa/', 5959, 'dipa', 'annual_cur', 'DIPA 01 (BUA) & DIPA 04 (Badilag)'],
            ['B3.3', 'berkala', 'Laporan Realisasi Anggaran (LRA)', 'Umum & Keuangan', '/lra/', 5356, 'lra', 'monthly', 'Wajib diperbarui bulanan (Serapan 01 & 04)'],
            ['B3.4', 'berkala', 'Catatan Atas Laporan Keuangan (CALK)', 'Umum & Keuangan', '/kesekretariatan/catatan-atas-laporan-keuangan-calk/', 6015, 'calk', 'semester_cur', 'Semester I & II DIPA 01/04'],
            ['B3.5', 'berkala', 'Rencana Umum Pengadaan Barang & Jasa (RUP / PBJ)', 'Umum & Keuangan', '/kesekretariatan/pengadaan-barang-dan-jasa/', 6191, 'pbj', 'annual_cur', 'Paket PBJ DIPA 01, DIPA 04, & RUP'],
            ['B3.6', 'berkala', 'Rencana Strategis (Renstra) 5 Tahun', 'PTIP', '/kesekretariatan/rencana-strategis-renstra/', 6201, 'renstra', 'sakip_5y', 'Renstra & Reviu Renstra'],
            ['B3.7', 'berkala', 'Rencana Kinerja Tahunan (RKT) 5 Tahun', 'PTIP', '/kesekretariatan/rencana-kinerja-tahunan-rkt/', 6210, 'rkt', 'sakip_5y', 'RKT hingga slot 2027'],
            ['B3.8', 'berkala', 'Rencana Aksi Kinerja Tahun Berjalan', 'PTIP', '/kesekretariatan/rencana-aksi-kinerja/', 6218, 'rencana-aksi', 'annual_cur', 'Target kinerja tahun 2026'],
            ['B3.9', 'berkala', 'Perjanjian Kinerja (PK) 5 Tahun', 'PTIP', '/kesekretariatan/perjanjian-kinerja-pk/', 5967, 'pk', 'sakip_5y', 'Dokumen PK & Revisi PK'],
            ['B3.10', 'berkala', 'IKU dan Reviu IKU (5 Tahun)', 'PTIP', '/kesekretariatan/indikator-kinerja-utama-iku/', 5975, 'iku', 'sakip_5y', 'Penetapan & Reviu IKU'],
            ['B3.11', 'berkala', 'Laporan Akuntabilitas Kinerja (LKJiP) 5 Tahun', 'PTIP', '/kesekretariatan/laporan-akuntabilitas-kinerja-pemerintah-lkjip/', 5983, 'lkjip', 'lkjip_5y', 'LKJiP min. 5 tahun (terbit s.d. tahun lalu)'],
            ['B4.1', 'berkala', 'Laporan Akses Informasi Publik (PPID)', 'PPID / Hukum', '/layanan-publik/laporan-akses-informasi/', 5760, 'akses-informasi', 'monthly', 'Laporan bulanan layanan PPID'],
            ['B4.2', 'berkala', 'Laporan Rekapitulasi Pengaduan Masyarakat', 'Hukum & Pengaduan', '/layanan-publik/laporan-rekapitulasi-pengaduan/', 5788, 'rekap-pengaduan', 'monthly', 'Rekap bulanan & tindak lanjut pengaduan'],
            ['B4.3', 'berkala', 'Hukuman Disiplin Hasil Pengawasan', 'Kepegawaian & Pengawasan', '/layanan-publik/hukuman-disiplin-hasil-pengawasan/', 5827, null, 'page_ok', 'Halaman publikasi hasil pengawasan aktif (HTTP 200)'],
            ['B4.4', 'berkala', 'Nilai & Laporan Survei Pelayanan (SKM, SPAK, SPKP)', 'Hukum & Pelayanan Publik', '/layanan-publik/survei-pelayanan-masyarakat/', 7978, 'survei-pelayanan', 'quarterly_survey', 'Sinkron ke Halaman Survei & Widget Beranda'],
            ['B5.1', 'berkala', 'Statistik Jumlah Kunjungan Website', 'PTIP / Humas', '/kesekretariatan/statistik-jumlah-pengunjung-website/', 6007, null, 'page_ok', 'Halaman statistik kunjungan website aktif'],

            // A.2 INFORMASI WAJIB (11 kriteria, bobot 20%)
            ['W1.1', 'wajib', 'Visi dan Misi Pengadilan', 'Kesekretariatan', '/profil-pengadilan/visi-dan-misi/', 4715, null, 'page_ok', 'Halaman profil Visi & Misi terpublikasi'],
            ['W1.2', 'wajib', 'Tugas Pokok dan Fungsi Pengadilan', 'Kesekretariatan', '/profil-pengadilan/tugas-pokok-dan-fungsi-pengadilan/', 4854, null, 'page_ok', 'Halaman Tupoksi Pengadilan terpublikasi'],
            ['W1.3', 'wajib', 'Wilayah Yurisdiksi & Peta Wilayah', 'Kepaniteraan', '/profil-pengadilan/wilayah-yurisdiksi/', 4713, null, 'page_ok', 'Peta & cakupan wilayah yurisdiksi Satker Pengadilan Agama'],
            ['W1.4', 'wajib', 'Struktur Organisasi (Bagan, Nama & Jabatan)', 'Kesekretariatan', '/profil-pengadilan/struktur-organisasi/', 4810, null, 'page_ok', 'Bagan struktur organisasi resmi aktif'],
            ['W1.5', 'wajib', 'Alamat Lengkap, Telepon, Email & Foto Gedung', 'Kesekretariatan', '/profil-pengadilan/alamat-dan-kontak/', 4994, null, 'page_ok', 'Identitas kontak resmi tercantum di halaman & footer'],
            ['W2.1', 'wajib', 'Informasi & Tautan e-Court Mahkamah Agung', 'Kepaniteraan', '/kepaniteraan/e-court/', 5069, null, 'page_ok', 'Panduan & tautan resmi e-Court aktif'],
            ['W2.2', 'wajib', 'Tautan Direktori Putusan Satker', 'Kepaniteraan', '/kepaniteraan/direktori-putusan-2/', 5094, null, 'page_ok', 'Tautan ke Direktori Putusan Satker Pengadilan Agama aktif'],
            ['W2.3', 'wajib', 'Prosedur Berperkara (Semua Jenis Perkara)', 'Kepaniteraan', '/kepaniteraan/prosedur-berperkara/', 5086, null, 'page_ok', 'Alur pendaftaran & prosedur berperkara aktif'],
            ['W2.4', 'wajib', 'Rincian Biaya Proses Berperkara & Radius', 'Kepaniteraan', '/kepaniteraan/biaya-proses-berperkara/', 5264, null, 'page_ok', 'Rincian SK Panjar & Radius panggilan terpublikasi'],
            ['W3.1', 'wajib', 'Profil Pejabat, Pegawai, & Pejabat Pengawas', 'Kepegawaian & Ortala', '/kesekretariatan/profil-pegawai/', 4692, null, 'page_ok', 'Profil pegawai & daftar pejabat pengawas (#5820) aktif'],
            ['W4.1', 'wajib', 'Standar dan Maklumat Pelayanan Pengadilan', 'Hukum & Pelayanan Publik', '/layanan-publik/standar-dan-maklumat-pelayanan-pengadilan/', 6320, null, 'page_ok', 'Standar pelayanan & maklumat aktif tanpa dead-link'],
        ];
    }

    private static function evaluateModuleStatus(string $slug, array $idx, string $mode = ''): array
    {
        $mod = self::MODULES[$slug] ?? null;
        if (! $mod) {
            return ['status' => 'kosong', 'points' => 0, 'badge' => 'Tidak Dikenal', 'note' => 'Modul tidak terdaftar.', 'count' => 0, 'years' => []];
        }

        $stats  = $idx[$slug] ?? ['count' => 0, 'years' => [], 'grid' => [], 'latest_year' => 0, 'latest_period_order' => 0, 'latest_period_label' => ''];
        $count  = (int) $stats['count'];
        $years  = array_values(array_keys($stats['years']));
        rsort($years);

        if ($mode === '') {
            if ($slug === 'survei-pelayanan') {
                $mode = 'quarterly_survey';
            } elseif (in_array($slug, ['lhkpn', 'lhkasn'], true)) {
                $mode = 'hybrid_lhkpn';
            } elseif ($slug === 'mou') {
                $mode = 'annual_cur';
            } elseif (($mod['periods'] ?? 'months') === 'months') {
                $mode = 'monthly';
            } elseif ($slug === 'calk') {
                $mode = 'semester_cur';
            } elseif ($slug === 'lkjip') {
                $mode = 'lkjip_5y';
            } elseif (in_array($slug, ['renstra', 'rkt', 'pk', 'iku', 'rencana-aksi'], true)) {
                $mode = 'sakip_5y';
            } else {
                $mode = 'annual_cur';
            }
        }

        $curYear = (int) date('Y');
        $curMonth = (int) date('n');
        $prevTs = mktime(0, 0, 0, max(1, $curMonth - 1), 1, $curYear);
        if ($curMonth === 1) {
            $prevTs = mktime(0, 0, 0, 12, 1, $curYear - 1);
        }
        $prevYear = (int) date('Y', $prevTs);
        $prevMonthNum = (int) date('n', $prevTs);
        $monthKeys = array_keys(self::MONTHS);
        $prevKey = $monthKeys[$prevMonthNum - 1] ?? 'OKT';
        $prevLabel = self::MONTHS[$prevKey]['label'] ?? $prevKey;

        if ($count === 0 && $mode !== 'hybrid_lhkpn') {
            return [
                'status' => 'kosong',
                'points' => 0,
                'badge' => 'Belum Ada Dokumen',
                'note' => 'Belum ada dokumen terpublikasi. Klik "+ Isi" untuk mengunggah.',
                'count' => 0,
                'years' => [],
            ];
        }

        switch ($mode) {
            case 'monthly':
                $missingSubs = [];
                foreach ((array) $mod['sub_groups'] as $sg) {
                    if (empty($stats['grid'][$prevYear][$sg][$prevKey])) {
                        $missingSubs[] = $sg;
                    }
                }
                if (empty($missingSubs)) {
                    return [
                        'status' => 'ok',
                        'points' => 10,
                        'badge' => "Lengkap s.d. {$prevLabel} {$prevYear}",
                        'note' => "{$count} dok tersinkron Â· Laporan {$prevLabel} {$prevYear} sudah lengkap.",
                        'count' => $count,
                        'years' => $years,
                    ];
                }
                $latestInfo = $stats['latest_year'] > 0
                    ? " Terakhir terisi: {$stats['latest_period_label']} {$stats['latest_year']}."
                    : '';

                return [
                    'status' => 'kurang',
                    'points' => 5,
                    'badge' => "Kurang {$prevLabel} {$prevYear}",
                    'note' => "{$count} dok Â· Slot {$prevLabel} {$prevYear} belum lengkap (" . count($missingSubs) . " kelompok).{$latestInfo}",
                    'count' => $count,
                    'years' => $years,
                ];

            case 'quarterly_survey':
                $reqQuarters = [];
                if ($curMonth >= 4) $reqQuarters[] = 'TW1';
                if ($curMonth >= 7) $reqQuarters[] = 'TW2';
                if ($curMonth >= 10) $reqQuarters[] = 'TW3';
                if (empty($reqQuarters)) $reqQuarters = ['TW1'];

                $missingQ = [];
                $filledQ  = [];
                foreach ($reqQuarters as $qk) {
                    $qComplete = true;
                    foreach ((array) $mod['sub_groups'] as $sg) {
                        if (empty($stats['grid'][$curYear][$sg][$qk])) {
                            $qComplete = false;
                            break;
                        }
                    }
                    $qLabel = self::ALL_PERIODS[$qk]['label'] ?? $qk;
                    if ($qComplete) {
                        $filledQ[] = $qLabel;
                    } else {
                        $missingQ[] = $qLabel;
                    }
                }
                if (empty($missingQ)) {
                    return [
                        'status' => 'ok',
                        'points' => 10,
                        'badge' => 'Survei ' . $curYear . ' Lengkap',
                        'note' => "{$count} dokumen Â· Seluruh triwulan berjalan (" . implode(', ', $filledQ) . ") lengkap.",
                        'count' => $count,
                        'years' => $years,
                    ];
                }

                return [
                    'status' => 'kurang',
                    'points' => 5,
                    'badge' => 'Kurang ' . implode(', ', $missingQ),
                    'note' => "{$count} dok Â· Sudah ada: " . ($filledQ ? implode(', ', $filledQ) : 'belum ada') . '. Belum diunggah: ' . implode(', ', $missingQ) . " {$curYear}.",
                    'count' => $count,
                    'years' => $years,
                ];

            case 'hybrid_lhkpn':
                if ($count >= 2) {
                    return [
                        'status' => 'ok',
                        'points' => 10,
                        'badge' => "Rekap {$count} Dok + Arsip Web",
                        'note' => "Rekap kolektif ({$count} dok) & tabel per-pegawai aktif di website.",
                        'count' => $count,
                        'years' => $years,
                    ];
                }

                return [
                    'status' => 'kurang',
                    'points' => 5,
                    'badge' => 'Arsip Web Aktif Â· Slot Rekap Kosong',
                    'note' => 'Tabel per-pegawai aktif di website (#5380/#5394); unggah Rekap Kolektif 2026 di Portal Internal untuk status penuh.',
                    'count' => $count,
                    'years' => $years,
                ];

            case 'lkjip_5y':
                $targetYear = $curYear - 1;
                $recent5 = array_filter($years, static fn ($y) => $y >= ($targetYear - 4) && $y <= $targetYear);
                $hasTarget = in_array($targetYear, $years, true);
                if ($hasTarget && count($recent5) >= 5) {
                    return [
                        'status' => 'ok',
                        'points' => 10,
                        'badge' => count($years) . " Tahun (s.d. {$targetYear})",
                        'note' => "{$count} dok Â· Mencakup 5+ tahun terakhir hingga {$targetYear}.",
                        'count' => $count,
                        'years' => $years,
                    ];
                }

                return [
                    'status' => 'kurang',
                    'points' => 5,
                    'badge' => ! $hasTarget ? "Butuh Tahun {$targetYear}" : count($recent5) . '/5 Tahun',
                    'note' => "{$count} dok (" . implode(', ', array_slice($years, 0, 6)) . ") Â· Membutuhkan 5 tahun hingga {$targetYear}.",
                    'count' => $count,
                    'years' => $years,
                ];

            case 'sakip_5y':
                $recent5 = array_filter($years, static fn ($y) => $y >= ($curYear - 4) && $y <= $curYear);
                $hasCur = in_array($curYear, $years, true);
                if ($hasCur && count($recent5) >= 5) {
                    return [
                        'status' => 'ok',
                        'points' => 10,
                        'badge' => count($years) . " Tahun (s.d. {$curYear})",
                        'note' => "{$count} dok Â· Arsip 5+ tahun lengkap termasuk {$curYear}.",
                        'count' => $count,
                        'years' => $years,
                    ];
                }
                if (! $hasCur) {
                    return [
                        'status' => 'kurang',
                        'points' => 5,
                        'badge' => "Butuh Tahun {$curYear}",
                        'note' => "{$count} dok Â· Dokumen tahun {$curYear} belum terdeteksi di slot.",
                        'count' => $count,
                        'years' => $years,
                    ];
                }

                return [
                    'status' => 'kurang',
                    'points' => 5,
                    'badge' => count($recent5) . '/5 Tahun',
                    'note' => "{$count} dok Â· Tersedia " . count($recent5) . " dari 5 tahun terakhir (" . implode(', ', $years) . ').',
                    'count' => $count,
                    'years' => $years,
                ];

            case 'semester_cur':
                $sem1Ok = true;
                foreach ((array) $mod['sub_groups'] as $sg) {
                    if (empty($stats['grid'][$curYear][$sg]['SEM1'])) {
                        $sem1Ok = false;
                        break;
                    }
                }
                if ($sem1Ok) {
                    return [
                        'status' => 'ok',
                        'points' => 10,
                        'badge' => "Sem I {$curYear} Lengkap",
                        'note' => "{$count} dok Â· Laporan Semester I {$curYear} DIPA 01 & 04 terpublikasi.",
                        'count' => $count,
                        'years' => $years,
                    ];
                }

                return [
                    'status' => 'kurang',
                    'points' => 5,
                    'badge' => "Butuh Sem I {$curYear}",
                    'note' => "{$count} dok Â· Semester I {$curYear} belum lengkap.",
                    'count' => $count,
                    'years' => $years,
                ];

            case 'annual_cur':
            default:
                $hasCur = ! empty($stats['grid'][$curYear]);
                if ($hasCur) {
                    return [
                        'status' => 'ok',
                        'points' => 10,
                        'badge' => "Aktif {$curYear} ({$count} dok)",
                        'note' => "{$count} dokumen terpublikasi Â· Tahun berjalan {$curYear} sudah terisi.",
                        'count' => $count,
                        'years' => $years,
                    ];
                }

                return [
                    'status' => 'kurang',
                    'points' => 5,
                    'badge' => "Butuh Data {$curYear}",
                    'note' => "{$count} dok arsip (" . implode(', ', array_slice($years, 0, 5)) . "), data tahun {$curYear} belum diunggah.",
                    'count' => $count,
                    'years' => $years,
                ];
        }
    }

    public static function complianceReport(iterable $documents): array
    {
        $idx = [];
        foreach ($documents as $doc) {
            $slug   = is_array($doc) ? (string) ($doc['page_slug'] ?? '') : (string) $doc->page_slug;
            $year   = (int) (is_array($doc) ? ($doc['year'] ?? 0) : $doc->year);
            $sg     = is_array($doc) ? (string) ($doc['sub_group'] ?? '') : (string) $doc->sub_group;
            $pk     = strtoupper(is_array($doc) ? (string) ($doc['period_key'] ?? '') : (string) $doc->period_key);
            $pOrder = (int) (is_array($doc) ? ($doc['period_order'] ?? 0) : $doc->period_order);
            if ($slug === '' || $year <= 0) {
                continue;
            }
            if (! isset($idx[$slug])) {
                $idx[$slug] = ['count' => 0, 'years' => [], 'grid' => [], 'latest_year' => 0, 'latest_period_order' => 0, 'latest_period_label' => ''];
            }
            $idx[$slug]['count']++;
            $idx[$slug]['years'][$year] = true;
            $idx[$slug]['grid'][$year][$sg][$pk] = true;

            if ($year > $idx[$slug]['latest_year'] || ($year === $idx[$slug]['latest_year'] && $pOrder >= $idx[$slug]['latest_period_order'])) {
                $idx[$slug]['latest_year'] = $year;
                $idx[$slug]['latest_period_order'] = $pOrder;
                $idx[$slug]['latest_period_label'] = self::ALL_PERIODS[$pk]['label'] ?? $pk;
            }
        }

        $moduleHealth = [];
        foreach (array_keys(self::MODULES) as $slug) {
            $moduleHealth[$slug] = self::evaluateModuleStatus($slug, $idx);
        }

        $items = [];
        $okCount = 0;
        $kurangCount = 0;
        $kosongCount = 0;
        $berkalaPts = 0;
        $berkalaMax = 0;
        $wajibPts = 0;
        $wajibMax = 0;

        foreach (self::complianceCriteriaDefinitions() as [$code, $group, $label, $unit, $url, $postId, $modSlug, $mode, $defaultNote]) {
            if ($modSlug !== null) {
                $eval = self::evaluateModuleStatus($modSlug, $idx, $mode);
                $status = $eval['status'];
                $points = $eval['points'];
                $note   = $eval['note'];
                $yearsCovered = $eval['years'];
                $docCount = $eval['count'];
                // Khusus B3.1: ikut hitung gabungan LHKPN + LHKASN bila ada
                if ($code === 'B3.1' && isset($idx['lhkasn'])) {
                    $lhkasnCount = (int) $idx['lhkasn']['count'];
                    if ($lhkasnCount > 0 && $docCount > 0) {
                        $docCount += $lhkasnCount;
                        $status = 'ok';
                        $points = 10;
                        $note = "Rekap kolektif LHKPN + LHKASN ({$docCount} dok) serta arsip pegawai aktif di website.";
                    }
                }
            } else {
                $status = 'ok';
                $points = 10;
                $note   = $defaultNote;
                $yearsCovered = [];
                $docCount = null;
            }

            if ($status === 'ok') $okCount++;
            elseif ($status === 'kurang') $kurangCount++;
            else $kosongCount++;

            if ($group === 'berkala') {
                $berkalaPts += $points;
                $berkalaMax += 10;
            } else {
                $wajibPts += $points;
                $wajibMax += 10;
            }

            $items[] = [
                'code'          => $code,
                'group'         => $group,
                'group_label'   => $group === 'berkala' ? 'A.1 Informasi Berkala (40%)' : 'A.2 Informasi Wajib (20%)',
                'label'         => $label,
                'unit'          => $unit,
                'evidence_url'  => $url,
                'post_id'       => $postId,
                'module_slug'   => $modSlug,
                'status'        => $status,
                'points'        => $points,
                'doc_count'     => $docCount,
                'years_covered' => $yearsCovered,
                'note'          => $note,
            ];
        }

        $berkalaPct = $berkalaMax > 0 ? round(($berkalaPts / $berkalaMax) * 100, 1) : 0.0;
        $wajibPct   = $wajibMax > 0 ? round(($wajibPts / $wajibMax) * 100, 1) : 0.0;
        $uiUxPct    = 100.0;
        $perfSecPct = 100.0;
        $totalScore = round(($berkalaPct * 0.40) + ($wajibPct * 0.20) + ($uiUxPct * 0.20) + ($perfSecPct * 0.20), 1);

        return [
            'summary' => [
                'total_criteria'        => count($items),
                'ok_count'              => $okCount,
                'kurang_count'          => $kurangCount,
                'kosong_count'          => $kosongCount,
                'berkala_pct'           => $berkalaPct,
                'wajib_pct'             => $wajibPct,
                'ui_ux_pct'             => $uiUxPct,
                'perf_sec_pct'          => $perfSecPct,
                'estimated_total_score' => $totalScore,
            ],
            'items' => $items,
            'module_health' => $moduleHealth,
        ];
    }

    public function gatewayConfig(): array
    {
        $envSecret = trim((string) env('SINOVA_WP_SECRET_KEY', ''));
        $envBase   = rtrim(trim((string) env('SINOVA_WP_BASE_URL', '')), '/');
        if ($envSecret !== '' && $envBase !== '') {
            return [
                'base_url'   => $envBase,
                'secret_key' => $envSecret,
                'timeout'    => (int) env('SINOVA_WP_TIMEOUT', 30),
            ];
        }

        $path = storage_path('app/sinova-wp-gateway.json');
        if (is_readable($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded) && ! empty($decoded['secret_key'])) {
                return $decoded;
            }
        }

        throw new RuntimeException('Konfigurasi gateway SINOVA WordPress belum diatur (.env SINOVA_WP_* atau storage/app/sinova-wp-gateway.json).');
    }

    public function baseUrl(): string
    {
        try {
            $cfg = $this->gatewayConfig();
            return rtrim((string) ($cfg['base_url'] ?? 'https://pa-contoh.go.id'), '/');
        } catch (\Throwable) {
            return rtrim((string) env('SINOVA_WP_BASE_URL', 'https://pa-contoh.go.id'), '/');
        }
    }

    public static function resolveEvidenceUrl(string $pathOrUrl, string $baseUrl): string
    {
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return $pathOrUrl;
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($pathOrUrl, '/');
    }

    private function signedRequest(string $method, string $route, array $body = []): Response
    {
        $cfg     = $this->gatewayConfig();
        $baseUrl = rtrim((string) ($cfg['base_url'] ?? ''), '/');
        $secret  = (string) $cfg['secret_key'];
        $timeout = max(25, (int) ($cfg['timeout'] ?? 30));

        $method    = strtoupper($method);
        $route     = '/' . ltrim($route, '/');
        $rawBody   = $method === 'GET' ? '' : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $timestamp = (string) time();
        $nonce     = Str::random(24);
        $canonical = "{$method}\n{$route}\n{$timestamp}\n{$nonce}\n" . hash('sha256', (string) $rawBody);
        $signature = hash_hmac('sha256', $canonical, $secret);

        $pending = Http::timeout($timeout)->withHeaders([
            'Accept' => 'application/json',
            'User-Agent' => 'SINOVA-Starter-Sync/2.0',
            'X-PA-Sinova-Timestamp' => $timestamp,
            'X-PA-Sinova-Nonce' => $nonce,
            'X-PA-Sinova-Signature' => $signature,
        ]);

        $url = "{$baseUrl}/wp-json{$route}";

        if ($method === 'GET') {
            return $pending->get($url);
        }

        return $pending->withBody((string) $rawBody, 'application/json')->post($url);
    }

    public function ping(): array
    {
        try {
            $resp = $this->signedRequest('GET', '/pa-sinova/v1/ping');
            $data = $resp->json();
            if ($resp->successful() && ! empty($data['ok'])) {
                return [
                    'ok' => true,
                    'site' => $data['site'] ?? '/',
                    'server_time' => $data['server_time'] ?? null,
                    'module_count' => $data['module_count'] ?? count(self::MODULES),
                    'counts' => $data['counts'] ?? [],
                ];
            }

            return [
                'ok' => false,
                'error' => $data['message'] ?? ('HTTP ' . $resp->status()),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function pullDocuments(?string $slug = null): array
    {
        $route = $slug ? '/pa-sinova/v1/documents?slug=' . urlencode($slug) : '/pa-sinova/v1/documents';
        // Sign exact route path without query string (WordPress $req->get_route() excludes query string)
        $cfg     = $this->gatewayConfig();
        $baseUrl = rtrim((string) ($cfg['base_url'] ?? ''), '/');
        $secret  = (string) $cfg['secret_key'];
        $timestamp = (string) time();
        $nonce     = Str::random(24);
        $canonical = "GET\n/pa-sinova/v1/documents\n{$timestamp}\n{$nonce}\n" . hash('sha256', '');
        $signature = hash_hmac('sha256', $canonical, $secret);

        $resp = Http::timeout(max(30, (int) ($cfg['timeout'] ?? 30)))->withHeaders([
            'Accept' => 'application/json',
            'User-Agent' => 'SINOVA-Starter-Sync/2.0',
            'X-PA-Sinova-Timestamp' => $timestamp,
            'X-PA-Sinova-Nonce' => $nonce,
            'X-PA-Sinova-Signature' => $signature,
        ])->get($baseUrl . '/wp-json' . $route);

        if (! $resp->successful() || empty($resp->json('ok'))) {
            throw new RuntimeException('Gagal menarik data dari WordPress: ' . ($resp->json('message') ?? 'HTTP ' . $resp->status()));
        }

        $docs = (array) $resp->json('documents', []);
        $synced = 0;
        $now = now();

        foreach ($docs as $row) {
            $rowSlug = (string) ($row['page_slug'] ?? '');
            if (! isset(self::MODULES[$rowSlug])) {
                continue;
            }
            $periodKey = strtoupper((string) $row['period_key']);
            SinovaWebsiteDocument::updateOrCreate(
                [
                    'page_slug' => $rowSlug,
                    'year' => (int) $row['year'],
                    'sub_group' => (string) ($row['sub_group'] ?? ''),
                    'period_key' => $periodKey,
                ],
                [
                    'period_order' => (int) ($row['period_order'] ?? (self::ALL_PERIODS[$periodKey]['order'] ?? 99)),
                    'title' => (string) ($row['title'] ?? ''),
                    'source_type' => (string) ($row['source_type'] ?? 'external'),
                    'doc_url' => (string) ($row['doc_url'] ?? ''),
                    'file_size_kb' => $row['file_size_kb'] !== null ? (int) $row['file_size_kb'] : null,
                    'sync_status' => 'synced',
                    'last_synced_at' => $now,
                    'last_sync_error' => null,
                    'updated_by_name' => (string) ($row['updated_by'] ?? 'WordPress'),
                ]
            );
            $synced++;
        }

        return ['ok' => true, 'count' => $synced];
    }

    public function upsertDocument(array $payload): array
    {
        $resp = $this->signedRequest('POST', '/pa-sinova/v1/upsert', $payload);
        $data = $resp->json();

        if (! $resp->successful() || empty($data['ok']) || empty($data['document'])) {
            throw new RuntimeException($data['message'] ?? ('Gagal sinkron ke WordPress (HTTP ' . $resp->status() . ')'));
        }

        return (array) $data['document'];
    }

    public function deleteDocument(string $slug, int $year, string $subGroup, string $periodKey): bool
    {
        $resp = $this->signedRequest('POST', '/pa-sinova/v1/delete', [
            'page_slug' => $slug,
            'year' => $year,
            'sub_group' => $subGroup,
            'period_key' => $periodKey,
        ]);
        $data = $resp->json();

        if (! $resp->successful() || empty($data['ok'])) {
            throw new RuntimeException($data['message'] ?? ('Gagal hapus dari WordPress (HTTP ' . $resp->status() . ')'));
        }

        return true;
    }

    public function checkLinkHealth(string $url): array
    {
        try {
            $resp = Http::timeout(10)
                ->withOptions([
                    'allow_redirects' => ['max' => 5, 'track_redirects' => true],
                ])
                ->withHeaders(['User-Agent' => 'SINOVA-HealthCheck/1.0'])
                ->get($url);

            $status = $resp->status();
            $redirects = $resp->header('X-Guzzle-Redirect-History');
            $bodySample = substr((string) $resp->body(), 0, 8192);

            if (stripos((string) $redirects, 'accounts.google.com') !== false
                || stripos($bodySample, 'ServiceLogin') !== false
                || stripos($bodySample, 'Request access') !== false
                || stripos($bodySample, 'You need access') !== false
            ) {
                return ['status' => 403, 'note' => 'Google Drive Terkunci (Butuh Akses Publik)'];
            }

            if ($status >= 200 && $status < 400) {
                return ['status' => $status, 'note' => 'OK (Dapat Diakses Publik)'];
            }

            return ['status' => $status, 'note' => 'Bermasalah (HTTP ' . $status . ')'];
        } catch (\Throwable $e) {
            return ['status' => 0, 'note' => 'Timeout / Gagal Jaringan'];
        }
    }
}
