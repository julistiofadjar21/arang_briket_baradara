<?php
// Hubungkan ke database
include 'koneksi.php';

// Timezone (sesuai WITA / Makassar)
date_default_timezone_set('Asia/Makassar');

/* =========================
   Helper: nama bulan Indonesia
========================= */
function bulan_indo($bulan_angka) {
    $map = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $b = (int)$bulan_angka;
    return isset($map[$b]) ? $map[$b] : (string)$bulan_angka;
}

/* =========================
   Filter Analitik
   ym: YYYY-MM (default: bulan berjalan)
   (Filter tanggal DIHAPUS)
   (Filter metrik DIHAPUS)
========================= */
$ym = isset($_GET['ym']) ? trim($_GET['ym']) : date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
    $ym = date('Y-m');
}

// Rentang tanggal bulan terpilih
$start_month = $ym . '-01';
$end_month = date('Y-m-d', strtotime($start_month . ' +1 month')); // eksklusif

// Opsi 12 bulan terakhir (dari bulan berjalan server)
$month_options = [];
for ($i = 0; $i < 12; $i++) {
    $t = strtotime(date('Y-m-01') . " -$i months");
    $key = date('Y-m', $t);
    $label = bulan_indo(date('n', $t)) . ' ' . date('Y', $t);
    $month_options[] = ['key' => $key, 'label' => $label];
}

// Label metrik fixed (karena filter metrik dihapus)
$metric_label = 'Total Kg Dibeli';

/* =========================
   Ambil data frekuensi bulanan dari tabel pesanan
   Kolom pesanan:
   - bahan_baku, jumlah_kg, total_harga, tanggal

   Perbaikan duplikasi:
   Standarkan label berdasarkan keyword supaya
   "Briket 1 Kg Tempurung ..." masuk ke "Tempurung Kelapa"
   dan hanya 4 produk yang tampil.
========================= */
$freq_error = null;
$freq_rows = [];

$labels_bulan = [];
$data_kg = [];
$data_transaksi = [];
$data_omzet = [];

$top_info = [
    'label' => null,
    'total_kg' => 0,
    'total_transaksi' => 0,
    'total_omzet' => 0
];

$has_pesanan = false;

if (!$koneksi) {
    $freq_error = "Koneksi database gagal!";
} else {
    $check = mysqli_query($koneksi, "SHOW TABLES LIKE 'pesanan'");
    if ($check && mysqli_num_rows($check) > 0) {
        $has_pesanan = true;
    } else {
        $freq_error = "Tabel 'pesanan' tidak ditemukan. Analisis bulanan membutuhkan tabel pesanan.";
    }
}

if ($has_pesanan) {
    // === PERUBAHAN UTAMA DI SINI: CASE mapping agar "Briket 1 Kg Tempurung" -> "Tempurung Kelapa"
    $sql_freq = "
        SELECT
            CASE
                WHEN LOWER(p.bahan_baku) LIKE '%kemiri%' THEN 'Kulit Kemiri'
                WHEN LOWER(p.bahan_baku) LIKE '%tempurung%' OR LOWER(p.bahan_baku) LIKE '%batok%' OR LOWER(p.bahan_baku) LIKE '%kelapa%' THEN 'Tempurung Kelapa'
                WHEN LOWER(p.bahan_baku) LIKE '%serbuk%' OR LOWER(p.bahan_baku) LIKE '%kayu%' THEN 'Serbuk Kayu'
                WHEN LOWER(p.bahan_baku) LIKE '%sekam%' OR LOWER(p.bahan_baku) LIKE '%padi%' THEN 'Sekam Padi'
                ELSE 'Lainnya'
            END AS label_bahan,
            SUM(p.jumlah_kg) AS total_kg,
            COUNT(*) AS total_transaksi,
            SUM(p.total_harga) AS total_omzet
        FROM pesanan p
        WHERE p.tanggal >= ? AND p.tanggal < ?
        GROUP BY label_bahan
        HAVING label_bahan <> 'Lainnya'
        ORDER BY total_kg DESC
    ";

    $stmt = mysqli_prepare($koneksi, $sql_freq);
    if (!$stmt) {
        $freq_error = "Query frekuensi tidak bisa dipersiapkan: " . mysqli_error($koneksi);
    } else {
        mysqli_stmt_bind_param($stmt, "ss", $start_month, $end_month);

        if (!mysqli_stmt_execute($stmt)) {
            $freq_error = "Gagal menjalankan query frekuensi: " . mysqli_stmt_error($stmt);
        } else {
            // Fallback aman jika mysqlnd/get_result tidak tersedia
            $result = mysqli_stmt_get_result($stmt);

            if ($result) {
                $idx = 0;
                while ($r = mysqli_fetch_assoc($result)) {
                    $freq_rows[] = $r;

                    $label = (string)$r['label_bahan'];
                    $totalKg = (float)$r['total_kg'];
                    $totalTrx = (int)$r['total_transaksi'];
                    $totalOmz = (float)$r['total_omzet'];

                    $labels_bulan[] = $label;
                    $data_kg[] = $totalKg;
                    $data_transaksi[] = $totalTrx;
                    $data_omzet[] = $totalOmz;

                    if ($idx === 0) {
                        $top_info['label'] = $label;
                        $top_info['total_kg'] = $totalKg;
                        $top_info['total_transaksi'] = $totalTrx;
                        $top_info['total_omzet'] = $totalOmz;
                    }
                    $idx++;
                }
            } else {
                // Manual bind_result fallback
                mysqli_stmt_store_result($stmt);
                mysqli_stmt_bind_result($stmt, $label_bahan, $total_kg, $total_transaksi, $total_omzet);

                $idx = 0;
                while (mysqli_stmt_fetch($stmt)) {
                    $r = [
                        'label_bahan' => $label_bahan,
                        'total_kg' => $total_kg,
                        'total_transaksi' => $total_transaksi,
                        'total_omzet' => $total_omzet
                    ];
                    $freq_rows[] = $r;

                    $label = (string)$label_bahan;
                    $totalKg = (float)$total_kg;
                    $totalTrx = (int)$total_transaksi;
                    $totalOmz = (float)$total_omzet;

                    $labels_bulan[] = $label;
                    $data_kg[] = $totalKg;
                    $data_transaksi[] = $totalTrx;
                    $data_omzet[] = $totalOmz;

                    if ($idx === 0) {
                        $top_info['label'] = $label;
                        $top_info['total_kg'] = $totalKg;
                        $top_info['total_transaksi'] = $totalTrx;
                        $top_info['total_omzet'] = $totalOmz;
                    }
                    $idx++;
                }
            }
        }
        mysqli_stmt_close($stmt);
    }
}

/* =========================
   Tren 12 bulan terakhir (total KG)
   Untuk perbandingan antar bulan
========================= */
$trend_error = null;
$trend_labels = [];
$trend_values = [];

if ($has_pesanan) {
    $start_12 = date('Y-m-01', strtotime(date('Y-m-01') . ' -11 months'));
    $end_12 = date('Y-m-01', strtotime(date('Y-m-01') . ' +1 month')); // eksklusif

    $sql_trend = "
        SELECT
            DATE_FORMAT(tanggal, '%Y-%m') AS ym,
            SUM(jumlah_kg) AS total_kg
        FROM pesanan
        WHERE tanggal >= ? AND tanggal < ?
        GROUP BY ym
        ORDER BY ym ASC
    ";

    $stmt2 = mysqli_prepare($koneksi, $sql_trend);
    if (!$stmt2) {
        $trend_error = "Query tren tidak bisa dipersiapkan: " . mysqli_error($koneksi);
    } else {
        mysqli_stmt_bind_param($stmt2, "ss", $start_12, $end_12);

        if (!mysqli_stmt_execute($stmt2)) {
            $trend_error = "Gagal menjalankan query tren: " . mysqli_stmt_error($stmt2);
        } else {
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

            // Buat label fixed 12 bulan (bulan tanpa transaksi tetap muncul nol)
            for ($i = 11; $i >= 0; $i--) {
                $t = strtotime(date('Y-m-01') . " -$i months");
                $key = date('Y-m', $t);
                $label = bulan_indo(date('n', $t)) . ' ' . date('Y', $t);
                $trend_labels[] = $label;
                $trend_values[] = isset($map[$key]) ? $map[$key] : 0;
            }
        }

        mysqli_stmt_close($stmt2);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRIKET BARA DARA</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }

        header {
            background-color: #c62828;
            color: #fff;
            padding: 10px 0;
            text-align: center;
            position: relative;
        }

        .logo-container { position: absolute; top: 3px; left: 50px; }
        .logo-container img { width: 131px; height: 131px; border-radius: 100%; }

        header h1 {
            margin: 0;
            font-size: 32px;
            line-height: 1.2;
            text-align: center;
        }

        nav { background-color: #c6282c; padding: 10px 10px; }
        nav ul { list-style: none; margin: 0; padding: 0; text-align: center; }
        nav li { display: inline-block; margin: 0 10px; }
        nav a { color: #fff; text-decoration: none; padding: 6px 6px; }
        nav a:hover { background-color: #d32f2f; }

        .container {
            text-align: center;
            padding: 20px;
            background-color: #fff;
            color: #a71d2a;
        }

        .image-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            padding: 20px;
        }

        .image-box { width: 180px; margin-bottom: 20px; text-align: center; }
        .image-box img { width: 100%; height: auto; border-radius: 10px; }
        .image-box p { margin: 5px 0; }

        .price { color: #555; font-size: 14px; }

        .cta { text-align: center; margin-top: 20px; margin-bottom: 40px; }
        .cta button {
            background-color: #a71d2a;
            color: #fff;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .cta button:hover { background-color: #d32f2f; }

        /* ===== Tambahan style untuk Frekuensi Analisis ===== */
        .analytics-wrap { max-width: 1000px; margin: 0 auto 60px auto; padding: 0 16px; }

        .analytics-card {
            background: #fff;
            border: 1px solid #f0d6d6;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .analytics-title {
            margin: 0 0 8px 0;
            color: #a71d2a;
            font-size: 20px;
            text-align: center;
        }

        .analytics-subtitle {
            margin: 0 0 16px 0;
            color: #555;
            text-align: center;
            font-size: 14px;
        }

        /* subtitle jadi merah & bold */
        .analytics-subtitle--headline{
            margin: 0 0 16px 0;
            color: #a71d2a;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            align-items: center;
            margin: 10px 0 16px 0;
        }

        .filter-row label { font-size: 14px; color: #333; }

        .filter-row select {
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #ddd;
            min-width: 210px;
        }

        .filter-row button {
            background-color: #a71d2a;
            color: #fff;
            padding: 9px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .filter-row button:hover { background-color: #d32f2f; }

        .chart-area { margin-top: 12px; }

        .note-error {
            color: #b71c1c;
            background: #ffebee;
            border: 1px solid #ffcdd2;
            padding: 10px 12px;
            border-radius: 8px;
            margin: 10px 0;
            font-size: 13px;
        }

        .note-empty {
            color: #333;
            background: #fff8e1;
            border: 1px solid #ffe0b2;
            padding: 10px 12px;
            border-radius: 8px;
            margin: 10px 0;
            font-size: 13px;
            text-align: center;
        }

        @media (max-width: 600px) {
            .filter-row select { min-width: 100%; }
        }
        /* ===== End tambahan style ===== */
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="LOGO.OKEMI.png" alt="Logo Toko">
        </div>
        <h1>ARANG BRIKET</h1>
        <h1>BARA DARA</h1>
    </header>

    <nav>
        <ul>
            <li><a href="halamanberanda.php">Beranda</a></li>
            <li><a href="tentangkami.html">Tentang Kami</a></li>
            <li><a href="kontak.html">Kontak</a></li>
            <li><a href="#">Bahan Baku</a></li>
            <li><a href="promo.php">Promo</a></li>
            <li><a href="tes_pesanansaya.php">Pesanan saya</a></li>
        </ul>
    </nav>

    <div class="container">
        <h2>Bahan Baku</h2>
        <h3>Bahan baku yang kami sediakan</h3>
    </div>

    <div class="image-container">
        <?php
        // pastikan koneksi ke database tersedia
        if (!$koneksi) {
            die("<p style='color:red;'>Koneksi database gagal!</p>");
        }

        // ambil data bahan baku dari tabel yang benar
        $query = mysqli_query($koneksi, "SELECT * FROM bahan_baku");
        if (!$query) {
            echo "<p style='color:red;'>Query gagal: " . mysqli_error($koneksi) . "</p>";
        } else {
            while ($row = mysqli_fetch_assoc($query)) {
                $gambar = htmlspecialchars($row['gambar'] ?? '');
                $nama = htmlspecialchars($row['nama_bahan'] ?? '');
                $harga = number_format((float)($row['harga_jual'] ?? 0), 0, ',', '.');
                $stok = htmlspecialchars($row['stok'] ?? 0);

                echo "
                <div class='image-box'>
                    <img src='{$gambar}' alt='{$nama}'>
                    <p><strong>{$nama}</strong></p>
                    <p class='price'>Rp {$harga} / Kg</p>
                    <p><strong>Stok: {$stok} Kg</strong></p>
                </div>
                ";
            }
        }
        ?>
    </div>

    <div class="cta">
        <a href="pesansekarang.php">
            <button>Pesan Sekarang</button>
        </a>
    </div>

    <!-- ====== Frekuensi Analisis (tambahan fitur) ====== -->
    <div class="analytics-wrap">
        <div class="analytics-card">
            <?php
                $bulan_label = bulan_indo((int)substr($ym, 5, 2)) . ' ' . substr($ym, 0, 4);
            ?>

            <p class="analytics-subtitle--headline">
                Grafik menampilkan bahan baku/produk paling diminati pada <strong><?php echo htmlspecialchars($bulan_label); ?></strong>
                dari tabel <strong>pesanan</strong>.
            </p>

            <form method="GET" class="filter-row">
                <label for="ym">Pilih Bulan (12 bulan terakhir):</label>
                <select name="ym" id="ym">
                    <?php foreach ($month_options as $opt): ?>
                        <option value="<?php echo htmlspecialchars($opt['key']); ?>" <?php echo ($opt['key'] === $ym) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($opt['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Tampilkan</button>
            </form>

            <?php if ($freq_error): ?>
                <div class="note-error"><?php echo htmlspecialchars($freq_error); ?></div>
            <?php endif; ?>

            <?php if (!$freq_error && count($freq_rows) === 0): ?>
                <div class="note-empty">Belum ada data pesanan pada bulan ini, sehingga grafik belum bisa ditampilkan.</div>
            <?php endif; ?>

            <?php if (!$freq_error && count($freq_rows) > 0): ?>
                <div class="chart-area">
                    <canvas id="freqChart" height="120"></canvas>
                </div>
            <?php endif; ?>

            <hr style="border:none;border-top:1px solid #eee;margin:18px 0;">

            <h3 class="analytics-title" style="font-size:18px;margin-top:0;">Perbandingan 12 Bulan Terakhir</h3>
            <p class="analytics-subtitle" style="margin-bottom:12px;">
                Tren total pembelian (Kg) selama 12 bulan terakhir untuk membandingkan antar bulan.
            </p>

            <?php if ($trend_error): ?>
                <div class="note-error"><?php echo htmlspecialchars($trend_error); ?></div>
            <?php else: ?>
                <div class="chart-area">
                    <canvas id="trendChart" height="110"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- ====== End Frekuensi Analisis ====== -->

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    (function(){
        const labelsBulan = <?php echo json_encode($labels_bulan, JSON_UNESCAPED_UNICODE); ?>;
        const dataKg      = <?php echo json_encode($data_kg, JSON_UNESCAPED_UNICODE); ?>;

        const metricLabel = <?php echo json_encode($metric_label, JSON_UNESCAPED_UNICODE); ?>;
        const selectedYM  = <?php echo json_encode($ym, JSON_UNESCAPED_UNICODE); ?>;

        const canvas1 = document.getElementById('freqChart');
        if (canvas1 && labelsBulan.length > 0) {
            new Chart(canvas1, {
                type: 'bar',
                data: {
                    labels: labelsBulan,
                    datasets: [{
                        label: metricLabel + ' (' + selectedYM + ')',
                        data: dataKg,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                title: function(ctx){
                                    return ctx[0].label || '';
                                },
                                label: function(ctx){
                                    const v = ctx.parsed.y ?? 0;
                                    return 'Total Kg Dibeli: ' + Number(v).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' Kg';
                                }
                            }
                        },
                        legend: { display: true }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value){
                                    return Number(value).toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        }

        const trendLabels = <?php echo json_encode($trend_labels, JSON_UNESCAPED_UNICODE); ?>;
        const trendValues = <?php echo json_encode($trend_values, JSON_UNESCAPED_UNICODE); ?>;

        const canvas2 = document.getElementById('trendChart');
        if (canvas2 && trendLabels.length > 0) {
            new Chart(canvas2, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Total Pembelian (Kg) - 12 Bulan Terakhir',
                        data: trendValues,
                        borderWidth: 2,
                        tension: 0.25,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: true } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value){
                                    return Number(value).toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        }
    })();
    </script>
</body>
</html>
