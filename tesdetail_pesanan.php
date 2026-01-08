<?php
session_start();
$koneksi = new mysqli("localhost", "root", "", "web_bara_dara");
if ($koneksi->connect_error) {
    die("Koneksi gagal");
}

// ambil id pesanan
$id_pesanan = 0;

// prioritas: GET → SESSION
if (isset($_GET['id_pesanan'])) {
    $id_pesanan = intval($_GET['id_pesanan']);
} elseif (isset($_SESSION['id_pesanan'])) {
    $id_pesanan = intval($_SESSION['id_pesanan']);
}

if ($id_pesanan <= 0) {
    die("Pesanan tidak ditemukan.");
}

// ambil data pesanan
$stmt = $koneksi->prepare("SELECT * FROM pesanan WHERE id_pesanan = ?");
$stmt->bind_param("i", $id_pesanan);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Data pesanan tidak tersedia.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Pesanan Saya</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
    background:#f8f9fa;
    font-family: 'Poppins', sans-serif;
}
.card {
    max-width: 600px;
    margin: 50px auto;
    background: #fff;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
h2 {
    color: #c62828;
    text-align: center;
    margin-bottom: 25px;
}
.row {
    margin-bottom: 12px;
}
.label {
    font-weight: 600;
    color: #555;
}
.value {
    font-weight: 500;
}
.status {
    padding: 10px;
    border-radius: 8px;
    text-align: center;
    font-weight: 700;
}
.lunas { background: #2e7d32; color: #fff; }
.menunggu { background: #f9a825; color: #000; }
.btn {
    display: block;
    text-align: center;
    margin-top: 25px;
    padding: 12px;
    background: #c62828;
    color: white;
    text-decoration: none;
    border-radius: 8px;
}
.btn:hover {
    background: #b71c1c;
}
</style>
</head>
<body>

<div class="card">
    <h2>📦 Pesanan Saya</h2>

    <div class="row">
        <span class="label">ID Pesanan:</span>
        <span class="value"><?= $data['id_pesanan'] ?></span>
    </div>

    <div class="row">
        <span class="label">Nama:</span>
        <span class="value"><?= htmlspecialchars($data['nama_pembeli']) ?></span>
    </div>

    <div class="row">
        <span class="label">Produk:</span>
        <span class="value"><?= htmlspecialchars($data['bahan_baku']) ?></span>
    </div>

    <div class="row">
        <span class="label">Jumlah:</span>
        <span class="value"><?= $data['jumlah_kg'] ?> Kg</span>
    </div>

    <div class="row">
        <span class="label">Total Harga:</span>
        <span class="value">Rp <?= number_format($data['total_harga'],0,',','.') ?></span>
    </div>

    <div class="row">
        <span class="label">Tanggal:</span>
        <span class="value"><?= $data['tanggal'] ?></span>
    </div>

    <div class="row">
        <span class="label">Status Pesanan:</span>
    </div>

    <?php if ($data['status_pesanan'] === 'Lunas'): ?>
        <div class="status lunas">✔ LUNAS</div>
    <?php else: ?>
        <div class="status menunggu">⏳ <?= htmlspecialchars($data['status_pesanan']) ?></div>
    <?php endif; ?>

    <a href="bahanbaku.php" class="btn">⬅️ Kembali ke Produk</a>
</div>

</body>
</html>
