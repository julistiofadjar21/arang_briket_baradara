<?php
session_start();

/* =====================
   LOGIN & SESSION
===================== */
$user = $_SESSION["user"] ?? null;

if (!$user || !isset($_SESSION['username'])) {
    header("Location: tampilanloging.php");
    exit;
}

$username = $_SESSION['username'];

/* =====================
   KONEKSI DATABASE
===================== */
$koneksi = new mysqli("localhost", "root", "", "web_bara_dara");
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
$koneksi->set_charset("utf8mb4");

/* =====================
   QUERY PESANAN
===================== */
$stmt = $koneksi->prepare("
    SELECT 
        id_pesanan,
        kode_pesanan,
        bahan_baku,
        jumlah_kg,
        total_harga,
        status_pesanan,
        tanggal
    FROM pesanan
    WHERE nama_pembeli = ?
    ORDER BY id_pesanan DESC
");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Pesanan Saya</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

<style>
/* =====================
   GLOBAL
===================== */
body {
    margin: 0;
    background: #f4f4f4;
    font-family: Arial, sans-serif;
}

/* =====================
   HEADER + NAV (SATU BANNER)
===================== */
.header {
    background-color: #c62828;
    color: #fff;
    padding: 20px 0 10px;
    position: relative;
    text-align: center;
}

/* LOGO */
.header .logo {
    position: absolute;
    left: 40px;
    top: 18px;
}

.header .logo img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
}

/* JUDUL */
.header h1 {
    margin: 0;
    font-size: 34px;
    font-weight: bold;
    letter-spacing: 1px;
}

.header h2 {
    margin: 0;
    font-size: 30px;
    font-weight: bold;
    letter-spacing: 1px;
}

/* =====================
   NAVBAR
===================== */
.navbar-custom {
    margin-top: 10px;
}

.navbar-custom ul {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: center;
}

.navbar-custom li {
    display: inline-block;
    margin: 0 18px;
}

.navbar-custom a {
    color: #ffffff;
    text-decoration: none;
    font-size: 16px;
    font-weight: normal;
}

.navbar-custom a:hover {
    text-decoration: underline;
}

/* =====================
   CARD
===================== */
.card {
    max-width: 1000px;
    margin: 40px auto;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,.1);
}

/* =====================
   STATUS
===================== */
.badge-status {
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: bold;
}

.belum {
    background: #ffe0b2;
    color: #e65100;
}

.lunas {
    background: #c8e6c9;
    color: #1b5e20;
}
</style>
</head>

<body>

<!-- ===== HEADER & NAVBAR ===== -->
<div class="header">
    <div class="logo">
        <img src="LOGO.OKEMI.png" alt="Logo Bara Dara">
    </div>

    <h1>ARANG BRIKET</h1>
    <h2>BARA DARA</h2>

    <div class="navbar-custom">
        <ul>
            <li><a href="halamanberanda.php">Beranda</a></li>
            <li><a href="tentangkami.html">Tentang Kami</a></li>
            <li><a href="kontak.html">Kontak</a></li>
            <li><a href="bahanbaku.php">Bahan Baku</a></li>
            <li><a href="promo.php">Promo</a></li>
            <li><a href="pesanan_saya.php">Pesanan saya</a></li>
        </ul>
    </div>
</div>

<!-- ===== CONTENT ===== -->
<div class="card p-4">
    <h3 class="text-center mb-4 text-danger font-weight-bold">
        Pesanan Saya
    </h3>

    <table class="table table-bordered text-center">
        <thead class="thead-light">
            <tr>
                <th>Kode</th>
                <th>Tanggal</th>
                <th>Bahan</th>
                <th>Jumlah</th>
                <th>Total</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['kode_pesanan'] ?></td>
                <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                <td><?= $row['bahan_baku'] ?></td>
                <td><?= $row['jumlah_kg'] ?> Kg</td>
                <td>Rp <?= number_format($row['total_harga'],0,',','.') ?></td>
                <td>
                    <span class="badge-status <?= $row['status_pesanan']=='Lunas'?'lunas':'belum' ?>">
                        <?= $row['status_pesanan'] ?>
                    </span>
                </td>
                <td>
                    <a href="detail_pesanan.php?id=<?= $row['id_pesanan'] ?>"
                       class="btn btn-sm btn-primary">
                        Detail
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Belum ada pesanan</td>
            </tr>
        <?php endif; ?>

        </tbody>
    </table>
</div>

</body>
</html>
