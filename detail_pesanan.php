<?php
session_start();

// wajib login
if (!isset($_SESSION['username'])) {
    header("Location: tampilanloging.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("ID pesanan tidak valid");
}

$id_pesanan = (int)$_GET['id'];
$username   = $_SESSION['username'];

$koneksi = new mysqli("localhost","root","","web_bara_dara");
$koneksi->set_charset("utf8mb4");

// ambil pesanan milik user & harus LUNAS
$stmt = $koneksi->prepare("
    SELECT *
    FROM pesanan
    WHERE id_pesanan = ?
      AND nama_pembeli = ?
      AND status_pesanan = 'Lunas'
    LIMIT 1
");
$stmt->bind_param("is", $id_pesanan, $username);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Struk belum tersedia atau pesanan tidak valid.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Struk Pesanan</title>
<style>
body{background:#f4f4f4;font-family:Arial;}
.struk{
    max-width:420px;
    margin:40px auto;
    background:#fff;
    padding:25px;
    border-radius:10px;
    box-shadow:0 4px 10px rgba(0,0,0,.15);
}
h2{text-align:center;color:#c62828;}
.row{display:flex;justify-content:space-between;margin:6px 0;}
hr{margin:15px 0;}
.total{font-weight:bold;font-size:18px;}
.footer{text-align:center;margin-top:20px;font-size:12px;color:#555;}
</style>
</head>
<body>

<div class="struk">
    <h2>BARA DARA</h2>
    <p style="text-align:center">Struk Pembelian</p>

    <div class="row"><span>Kode</span><span><?= $data['kode_pesanan'] ?></span></div>
    <div class="row"><span>Tanggal</span><span><?= date('d-m-Y', strtotime($data['tanggal'])) ?></span></div>
    <div class="row"><span>Pembeli</span><span><?= htmlspecialchars($data['nama_pembeli']) ?></span></div>

    <hr>

    <div class="row"><span>Bahan</span><span><?= $data['bahan_baku'] ?></span></div>
    <div class="row"><span>Jumlah</span><span><?= $data['jumlah_kg'] ?> Kg</span></div>

    <?php if ($data['bonus_kg'] > 0): ?>
    <div class="row"><span>Bonus</span><span><?= $data['bonus_kg'] ?> Kg</span></div>
    <?php endif; ?>

    <hr>

    <div class="row total">
        <span>Total</span>
        <span>Rp <?= number_format($data['total_harga'],0,',','.') ?></span>
    </div>

    <p style="text-align:center;color:green;font-weight:bold;margin-top:10px;">
        ✔ LUNAS
    </p>

    <div class="footer">
        Terima kasih telah berbelanja<br>
        Arang Briket Bara Dara
    </div>
</div>

</body>
</html>
