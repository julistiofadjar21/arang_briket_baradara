<?php
session_start();

$user = $_SESSION["user"] ?? null;
$vip_level = 0;

if ($user && ($user["role"] ?? "") === "user" && !empty($user["username"])) {
  $koneksi = new mysqli("localhost", "root", "", "web_bara_dara");
  if (!$koneksi->connect_error) {
    $koneksi->set_charset("utf8mb4");
    $uname = (string)$user["username"];
    if ($stmt = $koneksi->prepare("SELECT vip_level FROM `user` WHERE username = ? LIMIT 1")) {
      $stmt->bind_param("s", $uname);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res && $res->num_rows === 1) {
        $row = $res->fetch_assoc();
        $vip_level = (int)$row["vip_level"];
        $_SESSION["user"]["vip_level"] = $vip_level;
      }
      $stmt->close();
    }
    $koneksi->close();
  }
}

function vip_label(int $vip): string {
  if ($vip === 1) return "VIP 1 (Bronze)";
  if ($vip === 2) return "VIP 2 (Silver)";
  if ($vip === 3) return "VIP 3 (Gold)";
  return "Basic";
}

function promo_desc(int $vip): array {
  if ($vip === 1) {
    return ["title"=>"VIP 1: Diskon 5%","lines"=>["Diskon 5% untuk setiap pembelian."]];
  }
  if ($vip === 2) {
    return ["title"=>"VIP 2: Diskon 10% + Bonus 1 Kg","lines"=>[
      "Diskon 10% untuk setiap pembelian.",
      "Bonus 1 Kg untuk pembelian di atas 5 Kg."
    ]];
  }
  if ($vip === 3) {
    return ["title"=>"VIP 3: Diskon 15% + Bonus 1 Kg + Gratis Ongkir","lines"=>[
      "Diskon 5% untuk setiap pembelian.",
      "Bonus 1 Kg untuk pembelian di atas 5 Kg.",
      "Gratis ongkir (sesuai ketentuan toko)."
    ]];
  }
  return ["title"=>"Belum Ada Promo VIP","lines"=>["Login untuk mendapatkan promo."]];
}

$levels = [1=>promo_desc(1),2=>promo_desc(2),3=>promo_desc(3)];
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BRIKET BARA DARA - Promo</title>

<style>
/* ===== CSS ASLI (TIDAK DIUBAH) ===== */
body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:0; }
header { background:#c62828; color:#fff; padding:10px 0; text-align:center; position:relative; }
header h1 { margin:0; font-size:32px; }
.logo-container { position:absolute; top:3px; left:50px; }
.logo-container img { width:120px; height:120px; border-radius:50%; }

nav { background:#c6282c; padding:10px 0; text-align:center; }
nav ul { list-style:none; margin:0; padding:0; }
nav ul li { display:inline-block; margin:0 10px; }
nav ul li a { color:#fff; text-decoration:none; padding:10px; border-radius:5px; }
nav ul li a:hover { background:#d32f2f; }

.promo-container { padding:30px; text-align:center; }
h2 { color:#c6282c; }

.promo-item {
  background:#fff;
  padding:10px;
  margin:10px;
  border:1px solid #ccc;
  border-radius:10px;
  box-shadow:5px 5px 5px rgba(0,0,0,.1);
  display:inline-block;
  width:27%;
  position:relative;
  text-align:left;
}

.promo-item img {
  width:100%;
  height:180px;
  object-fit:cover;
  border-radius:10px;
}

.promo-item button {
  background:#c6282c;
  color:#fff;
  border:none;
  padding:10px;
  border-radius:10px;
  width:100%;
  font-weight:900;
  cursor:pointer;
}

.locked { opacity:.55; filter:grayscale(70%); }
.active { border:2px solid #c6282c; }

.badge {
  position:absolute;
  top:12px;
  left:12px;
  background:#c6282c;
  color:#fff;
  padding:6px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:900;
}

.tag {
  display:inline-block;
  margin-top:6px;
  padding:6px 10px;
  border-radius:999px;
  font-size:12px;
  background:#f1f1f1;
  font-weight:900;
}

/* ===== CSS TAMBAHAN (KHUSUS PROMO VIP) ===== */
.vip-wrapper {
  max-width:1100px;
  margin:0 auto;
  display:flex;
  justify-content:center;
  gap:24px;
  flex-wrap:wrap;
}

.vip-wrapper .promo-item {
  width:320px;
  display:flex;
  flex-direction:column;
}

.vip-wrapper .promo-item ul {
  flex-grow:1;
}

.vip-wrapper .promo-item button {
  margin-top:auto;
}
</style>
</head>

<body>

<header>
  <div class="logo-container">
    <img src="LOGO.OKEMI.png">
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

<div class="promo-container">
<h2>Promo VIP</h2>

<div class="vip-wrapper">
<?php foreach ($levels as $lvl => $info): 
  $isActive = ($vip_level === $lvl);
?>
<div class="promo-item <?= $isActive?'active':'locked' ?>">
  <div class="badge"><?= vip_label($lvl) ?></div>
  <img src="briket.jpeg">
  <h2><?= $info["title"] ?></h2>
  <ul>
    <?php foreach ($info["lines"] as $ln): ?>
      <li><?= $ln ?></li>
    <?php endforeach; ?>
  </ul>
  <div class="tag"><?= $isActive?'Promo aktif untuk level Anda':'Terkunci (beda level)' ?></div>

  <?php if ($isActive): ?>
    <button onclick="location.href='pesansekarang.php'">Gunakan Promo</button>
  <?php else: ?>
    <button disabled>Tidak berlaku</button>
  <?php endif; ?>
</div>
<?php endforeach; ?>
</div>

</div>
</body>
</html>
