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
$foto = (!empty($user["foto"])) ? $user["foto"] : "user.log.png";
$vipLevel = $user["vip_level"] ?? null;

/* =====================
   KONEKSI DATABASE
===================== */
$koneksi = new mysqli("localhost", "root", "", "web_bara_dara");
if ($koneksi->connect_error) {
    die("Koneksi gagal");
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
        bonus_kg,
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

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

<style>
/* ===== CSS HEADER & NAV (ASLI) ===== */
header {
    background-color: #c62828;
    color: #fff;
    padding: 10px 0;
    text-align: center;
    position: relative;
}
header h1 {
    margin: 0;
    font-size: 32px;
    line-height: 1.2;
}
nav {
    background-color: #c6282c;
    padding: 10px 0;
}
nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    text-align: center;
}
nav li {
    display: inline-block;
    margin: 0 10px;
}
nav a {
    color: #fff;
    text-decoration: none;
    padding: 10px;
    border-radius: 5px;
}
nav a:hover {
    background-color: #d32f2f;
}
.logo-container img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
        }


/* ===== CSS PESANAN ===== */
body {
    background: #f8f9fa;
    padding: 40px;
    font-family: Poppins, sans-serif;
}
.card {
    max-width: 900px;
    margin: auto;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,.1);
}
h2 {
    color: #c6282c; 
    
    }
.badge-status {
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
}
.belum { background: #ffe0b2; color: #e65100; }
.lunas { background: #c8e6c9; color: #1b5e20; }
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
    <li><a href="bahanbaku.php">Bahan Baku</a></li>
    <li><a href="promo.php">Promo</a></li>
    <li><a href="tes_pesanansaya.php">Pesanan saya</a></li>
</ul>
</nav>

<div class="card p-4">
<h2 class="text-center mb-4">📦 Pesanan Saya</h2>

<table class="table table-bordered text-center">
<thead class="thead-light">
<tr>
<th>Kode</th><th>Tanggal</th><th>Bahan</th><th>Jumlah</th>
<th>Total</th><th>Status</th><th>Aksi</th>
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
<a href="detail_pesanan.php?id=<?= $row['id_pesanan'] ?>" class="btn btn-sm btn-primary">Detail</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="7">Belum ada pesanan</td></tr>
<?php endif; ?>

</tbody>
</table>
</div>

</body>
</html>
