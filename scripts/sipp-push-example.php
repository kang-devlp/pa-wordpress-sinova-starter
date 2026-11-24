<?php
/**
 * Contoh Skrip Sinkronisasi Push Jadwal Sidang Otomatis dari Server SIPP Lokal -> WordPress PA SINOVA
 *
 * Cara pakai di server SIPP (Linux Cron / Windows Task Scheduler tiap 15-30 menit pada jam kerja):
 *   1. Isi konfigurasi WP_SITE_URL, SINOVA_HMAC_SECRET (samakan dengan WP Admin -> SINOVA Hub -> Identitas Satker),
 *      serta kredensial MySQL SIPP read-only lokal di bawah.
 *   2. Jalankan: php scripts/sipp-push-example.php
 */

declare(strict_types=1);

$wpBaseUrl   = getenv('SINOVA_WP_BASE_URL') ?: 'https://pa-satker.go.id';
$hmacSecret  = getenv('SINOVA_WP_SECRET_KEY') ?: 'ISI_SECRET_KEY_HMAC_MINIMAL_32_KARAKTER';
$sippHost    = getenv('SIPP_DB_HOST') ?: '127.0.0.1';
$sippPort    = (int) (getenv('SIPP_DB_PORT') ?: 3306);
$sippName    = getenv('SIPP_DB_NAME') ?: 'sipp';
$sippUser    = getenv('SIPP_DB_USER') ?: 'sipp_readonly';
$sippPass    = getenv('SIPP_DB_PASS') ?: '';
$targetDate  = $argv[1] ?? date('Y-m-d');

$conn = @mysqli_connect($sippHost, $sippUser, $sippPass, $sippName, $sippPort);
if (!$conn) {
    fwrite(STDERR, "Gagal koneksi database SIPP: " . mysqli_connect_error() . PHP_EOL);
    exit(1);
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT DATE_FORMAT(j.jam_sidang, '%H:%i') AS jam, p.nomor_perkara AS nomor, j.agenda AS agenda, j.ruangan AS ruang
     FROM perkara_jadwal_sidang j
     JOIN perkara p ON p.perkara_id = j.perkara_id
     WHERE j.tanggal_sidang = ?
     ORDER BY j.jam_sidang ASC
     LIMIT 250"
);
mysqli_stmt_bind_param($stmt, 's', $targetDate);
mysqli_stmt_execute($stmt);
$res   = mysqli_stmt_get_result($stmt);
$items = array();
while ($row = mysqli_fetch_assoc($res)) {
    $items[] = array(
        'nomor'  => (string) ($row['nomor'] ?? ''),
        'agenda' => (string) ($row['agenda'] ?? ''),
        'ruang'  => (string) ($row['ruang'] ?? 'Ruang Sidang'),
        'jam'    => trim((string) ($row['jam'] ?? '09:00')) . ' WIB',
    );
}
mysqli_stmt_close($stmt);
mysqli_close($conn);

$route   = '/pa-sinova/v1/sipp/push-hearings';
$url     = rtrim($wpBaseUrl, '/') . '/wp-json' . $route;
$payload = json_encode(array(
    'date'         => $targetDate,
    'replace_date' => true,
    'items'        => $items,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$ts        = (string) time();
$nonce     = bin2hex(random_bytes(16));
$canonical = "POST\n{$route}\n{$ts}\n{$nonce}\n" . hash('sha256', (string) $payload);
$signature = hash_hmac('sha256', $canonical, $hmacSecret);

$ch = curl_init($url);
curl_setopt_array($ch, array(
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => array(
        'Content-Type: application/json',
        'X-PA-Sinova-Timestamp: ' . $ts,
        'X-PA-Sinova-Nonce: ' . $nonce,
        'X-PA-Sinova-Signature: ' . $signature,
    ),
));
$resp = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($resp === false || $code < 200 || $code >= 300) {
    fwrite(STDERR, "Gagal push jadwal sidang (HTTP {$code}): {$err} {$resp}" . PHP_EOL);
    exit(1);
}

echo "Sukses push " . count($items) . " jadwal sidang ({$targetDate}): {$resp}" . PHP_EOL;
