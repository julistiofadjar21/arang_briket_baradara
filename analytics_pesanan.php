<?php
header('Content-Type: application/json; charset=utf-8');

// Hubungkan ke database
include 'koneksi.php';

// Timezone WITA/Makassar
date_default_timezone_set('Asia/Makassar');

function bulan_indo($bulan_angka) {
    $map = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $b = (int)$bulan_angka;
    return $map[$b] ?? (string)$bulan_angka;
}

function respond($success, $message = null, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================
   Validasi input
   ym: YYYY-MM
   metric: kg | transaksi | omzet
   status: optional (contoh: Lunas / Belum Lunas)
   date_from: YYYY-MM-DD (optional)
   date_to  : YYYY-MM-DD (optional)
========================= */
$ym = isset($_GET['ym']) ? trim($_GET['ym']) : date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) $ym = date('Y-m');

$metric = isset($_GET['metric']) ? trim($_GET['metric']) : 'kg';
$metric_allowed = ['kg', 'transaksi', 'omzet'];
if (!in_array($metric, $metric_allowed, true)) $metric = 'kg';

// status optional; kosong = semua status
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
// keamanan sederhana: batasi panjang & karakter
if ($status !== '' && !preg_match('/^[\w\s\-]{1,20}$/u', $status)) {
    $status = '';
}

$metric_label = 'Total Kg Dibeli';
if ($metric === 'transaksi') $metric_label = 'Jumlah Transaksi';
if ($metric === 'omzet') $metric_label = 'Omzet (Rp)';

/* =========================
   RANGE TANGGAL (PERUBAHAN):
   - Jika date_from & date_to valid -> pakai range tsb
   - Jika tidak -> pakai range bulan dari ym (default lama)
   Catatan:
   - date_to dibuat eksklusif dengan +1 hari supaya inklusif pada query
========================= */
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to   = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

$hasDateRange = false;
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    // validasi kronologis
    if ($date_from <= $date_to) {
        $hasDateRange = true;
    }
}

// Range tanggal bulan terpilih (tanggal bertipe DATE)
$start_month = $ym . '-01';
$end_month = date('Y-m-d', strtotime($start_month . ' +1 month')); // eksklusif

// Override jika user pakai date range
if ($hasDateRange) {
    $start_month = $date_from; // inklusif
    $end_month = date('Y-m-d', strtotime($date_to . ' +1 day')); // eksklusif (agar date_to inklusif)
}

if (!$koneksi) {
    respond(false, 'Koneksi database gagal!');
}

// cek tabel pesanan
$check = mysqli_query($koneksi, "SHOW TABLES LIKE 'pesanan'");
if (!$check || mysqli_num_rows($check) === 0) {
    respond(false, "Tabel 'pesanan' tidak ditemukan. Analisis membutuhkan tabel pesanan.");
}

/* =========================
   Frekuensi Analisis Bulanan (GROUP BY bahan)
   Perbaikan duplikasi:
   "Briket 1 Kg Tempurung ..." -> "Tempurung Kelapa"
========================= */
$labels_bulan = [];
$data_metric  = [];
$data_kg = [];
$data_transaksi = [];
$data_omzet = [];

$freq_rows = [];

$top_info = [
    'label' => null,
    'total_kg' => 0,
    'total_transaksi' => 0,
    'total_omzet' => 0
];

// SQL dengan optional filter status_pesanan
$whereStatus = '';
if ($status !== '') {
    $whereStatus = " AND (p.status_pesanan = ?) ";
}

/**
 * PERUBAHAN UTAMA:
 * Standarkan label_bahan berbasis keyword agar variasi penulisan tidak membuat duplikat.
 * Contoh:
 * - "Briket 1 Kg Tempurung" -> Tempurung Kelapa
 */
$sql_freq = "
    SELECT
        CASE
            WHEN LOWER(p.bahan_baku) LIKE '%kemiri%' THEN 'Kulit Kemiri'
            WHEN LOWER(p.bahan_baku) LIKE '%tempurung%' OR LOWER(p.bahan_baku) LIKE '%batok%' OR LOWER(p.bahan_baku) LIKE '%kelapa%' THEN 'Tempurung Kelapa'
            WHEN LOWER(p.bahan_baku) LIKE '%serbuk%' OR LOWER(p.bahan_baku) LIKE '%kayu%' THEN 'Serbuk Kayu'
            WHEN LOWER(p.bahan_baku) LIKE '%sekam%' OR LOWER(p.bahan_baku) LIKE '%padi%' THEN 'Sekam Padi'
            ELSE p.bahan_baku
        END AS label_bahan,
        SUM(p.jumlah_kg) AS total_kg,
        COUNT(*) AS total_transaksi,
        SUM(p.total_harga) AS total_omzet
    FROM pesanan p
    WHERE p.tanggal >= ? AND p.tanggal < ?
    $whereStatus
    GROUP BY label_bahan
    ORDER BY total_kg DESC
";

$stmt = mysqli_prepare($koneksi, $sql_freq);
if (!$stmt) {
    respond(false, "Query frekuensi tidak bisa dipersiapkan: " . mysqli_error($koneksi));
}

if ($status !== '') {
    mysqli_stmt_bind_param($stmt, "sss", $start_month, $end_month, $status);
} else {
    mysqli_stmt_bind_param($stmt, "ss", $start_month, $end_month);
}

if (!mysqli_stmt_execute($stmt)) {
    respond(false, "Gagal menjalankan query frekuensi: " . mysqli_stmt_error($stmt));
}

// ambil hasil (mysqlnd tersedia)
$result = mysqli_stmt_get_result($stmt);

$idx = 0;
if ($result) {
    while ($r = mysqli_fetch_assoc($result)) {
        $label = (string)$r['label_bahan'];
        $totalKg = (float)$r['total_kg'];
        $totalTrx = (int)$r['total_transaksi'];
        $totalOmz = (float)$r['total_omzet'];


        $freq_rows[] = [
            'label_bahan' => $label,
            'total_kg' => $totalKg,
            'total_transaksi' => $totalTrx,
            'total_omzet' => $totalOmz
        ];

        $labels_bulan[] = $label;
        $data_kg[] = $totalKg;
        $data_transaksi[] = $totalTrx;
        $data_omzet[] = $totalOmz;

        if ($metric === 'kg') $data_metric[] = $totalKg;
        if ($metric === 'transaksi') $data_metric[] = $totalTrx;
        if ($metric === 'omzet') $data_metric[] = $totalOmz;

        if ($idx === 0) {
            $top_info['label'] = $label;
            $top_info['total_kg'] = $totalKg;
            $top_info['total_transaksi'] = $totalTrx;
            $top_info['total_omzet'] = $totalOmz;
        }
        $idx++;
    }
} else {
    // fallback bind_result jika mysqlnd tidak ada
    mysqli_stmt_store_result($stmt);
    mysqli_stmt_bind_result($stmt, $label_bahan, $total_kg, $total_transaksi, $total_omzet);

    while (mysqli_stmt_fetch($stmt)) {
        $label = (string)$label_bahan;
        $totalKg = (float)$total_kg;
        $totalTrx = (int)$total_transaksi;
        $totalOmz = (float)$total_omzet;

        $freq_rows[] = [
            'label_bahan' => $label,
            'total_kg' => $totalKg,
            'total_transaksi' => $totalTrx,
            'total_omzet' => $totalOmz
        ];

        $labels_bulan[] = $label;
        $data_kg[] = $totalKg;
        $data_transaksi[] = $totalTrx;
        $data_omzet[] = $totalOmz;

        if ($metric === 'kg') $data_metric[] = $totalKg;
        if ($metric === 'transaksi') $data_metric[] = $totalTrx;
        if ($metric === 'omzet') $data_metric[] = $totalOmz;

        if ($idx === 0) {
            $top_info['label'] = $label;
            $top_info['total_kg'] = $totalKg;
            $top_info['total_transaksi'] = $totalTrx;
            $top_info['total_omzet'] = $totalOmz;
        }
        $idx++;
    }
}
mysqli_stmt_close($stmt);

/* =========================
   Tren 12 bulan terakhir (SUM jumlah_kg)
   Optional filter status sama
========================= */
$trend_labels = [];
$trend_values = [];

$start_12 = date('Y-m-01', strtotime(date('Y-m-01') . ' -11 months'));
$end_12   = date('Y-m-01', strtotime(date('Y-m-01') . ' +1 month')); // eksklusif

$whereStatus2 = '';
if ($status !== '') {
    $whereStatus2 = " AND (status_pesanan = ?) ";
}

$sql_trend = "
    SELECT
        DATE_FORMAT(tanggal, '%Y-%m') AS ym,
        SUM(jumlah_kg) AS total_kg
    FROM pesanan
    WHERE tanggal >= ? AND tanggal < ?
    $whereStatus2
    GROUP BY ym
    ORDER BY ym ASC
";

$stmt2 = mysqli_prepare($koneksi, $sql_trend);
if (!$stmt2) {
    respond(false, "Query tren tidak bisa dipersiapkan: " . mysqli_error($koneksi));
}

if ($status !== '') {
    mysqli_stmt_bind_param($stmt2, "sss", $start_12, $end_12, $status);
} else {
    mysqli_stmt_bind_param($stmt2, "ss", $start_12, $end_12);
}

if (!mysqli_stmt_execute($stmt2)) {
    respond(false, "Gagal menjalankan query tren: " . mysqli_stmt_error($stmt2));
}

$res2 = mysqli_stmt_get_result($stmt2);
$map = [];

if ($res2) {
    while ($rr = mysqli_fetch_assoc($res2)) {
        $map[$rr['ym']] = (float)$rr['total_kg'];
    }
} else {
    mysqli_stmt_store_result($stmt2);
    mysqli_stmt_bind_result($stmt2, $ym_key, $totalKgMonth);
    while (mysqli_stmt_fetch($stmt2)) {
        $map[$ym_key] = (float)$totalKgMonth;
    }
}
mysqli_stmt_close($stmt2);

// label fixed 12 bulan (bulan tanpa transaksi = 0)
for ($i = 11; $i >= 0; $i--) {
    $t = strtotime(date('Y-m-01') . " -$i months");
    $key = date('Y-m', $t);
    $label = bulan_indo(date('n', $t)) . ' ' . date('Y', $t);
    $trend_labels[] = $label;
    $trend_values[] = $map[$key] ?? 0;
}

$month_label = bulan_indo((int)substr($ym, 5, 2)) . ' ' . substr($ym, 0, 4);

// output final
respond(true, null, [
    'meta' => [
        'ym' => $ym,
        'metric' => $metric,
        'metric_label' => $metric_label,
        'month_label' => $month_label,
        'status' => $status,
        // PERUBAHAN: ikutkan info range tanggal yang dipakai (opsional)
        'date_from' => $hasDateRange ? $date_from : $start_month,
        'date_to' => $hasDateRange ? $date_to : date('Y-m-d', strtotime($end_month . ' -1 day'))
    ],
    'freq' => [
        'labels' => $labels_bulan,
        'data_metric' => $data_metric,
        'data_kg' => $data_kg,
        'data_transaksi' => $data_transaksi,
        'data_omzet' => $data_omzet,
        'top_info' => $top_info,
        'rows' => $freq_rows
    ],
    'trend' => [
        'labels' => $trend_labels,
        'values' => $trend_values
    ]
]);
