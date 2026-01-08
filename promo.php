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
        $vip_level = (int)($row["vip_level"] ?? 0);
        $_SESSION["user"]["vip_level"] = $vip_level;
      }
      $stmt->close();
    }
    $koneksi->close();
  }
}

function vip_label(int $vip): string {
  if ($vip === 1) return "VIP 1 (Gold)";
  if ($vip === 2) return "VIP 2 (Silver)";
  if ($vip === 3) return "VIP 3 (Bronze)";
  return "Basic";
}

function promo_desc(int $vip): array {
  if ($vip === 1) {
    return [
      "title" => "VIP 1: Diskon 15% + Bonus 1 Kg + Gratis Ongkir",
      "lines" => [
        "Diskon 15% untuk setiap pembelian.",
        "Bonus 1 Kg untuk pembelian di atas 5 Kg.",
        "Gratis ongkir (sesuai ketentuan toko)."
      ]
    ];
  }
  if ($vip === 2) {
    return [
      "title" => "VIP 2: Diskon 10% + Bonus 1 Kg",
      "lines" => [
        "Diskon 10% untuk setiap pembelian.",
        "Bonus 1 Kg untuk pembelian di atas 5 Kg."
      ]
    ];
  }
  if ($vip === 3) {
    return [
      "title" => "VIP 3: Diskon 5%",
      "lines" => [
        "Diskon 5% untuk setiap pembelian."
      ]
    ];
  }
  return [
    "title" => "Belum Ada Promo VIP",
    "lines" => [
      "Login dan lakukan pembelian untuk naik level member."
    ]
  ];
}

// Kartu promo level (ditampilkan semua, yang aktif hanya sesuai VIP user)
$levels = [
  1 => promo_desc(1),
  2 => promo_desc(2),
  3 => promo_desc(3),
];

$statusName = vip_label($vip_level);
$current = promo_desc($vip_level);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BRIKET BARA DARA - Promo</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
    header { background-color: #c62828; color: white; padding: 10px 0; text-align: center; position: relative; }
    header h1 { margin: 0; font-size: 32px; line-height: 1.2; text-align: center; }
    .logo-container { position: absolute; top: 3px; left: 50px; }
    .logo-container img { width: 120px; height: 120px; border-radius: 50%; }

    nav { background-color: #c6282c; padding: 10px 0; text-align: center; }
    nav ul { list-style-type: none; margin: 0; padding: 0; }
    nav ul li { display: inline-block; margin: 0 10px; }
    nav ul li a { color: white; text-decoration: none; padding: 10px 10px; border-radius: 5px; }
    nav ul li a:hover { background-color: #d32f2f; }

    .promo-container { padding: 30px; text-align: center; }
    h2 { color: #c6282c; }

    .status-box{
      max-width: 980px;
      margin: 0 auto 18px;
      background:#fff;
      border-radius: 14px;
      padding: 14px 18px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.10);
      text-align:left;
    }

    .promo-item {
      background-color: white;
      padding: 10px;
      margin: 10px;
      border: 1px solid #ccc;
      border-radius: 10px;
      box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.1);
      display: inline-block;
      width: 27%;
      vertical-align: top;
      position: relative;
      text-align: left;
    }
    .promo-item img { max-width: 100%; height: 180px; object-fit: cover; border-radius: 10px; }
    .promo-item p, .promo-item li { color: #555; font-size: 14px; line-height: 1.5; }
    .promo-item ul { padding-left: 18px; margin: 8px 0 0; }

    .promo-item button {
      background-color: #c6282c;
      color: white;
      border: none;
      padding: 10px 10px;
      cursor: pointer;
      border-radius: 10px;
      margin-top: 10px;
      width: 100%;
      font-weight: 900;
    }
    .promo-item button:hover { background-color: #b22222; }

    .locked { opacity: .55; filter: grayscale(70%); }
    .active { border: 2px solid #c6282c; box-shadow: 0 8px 18px rgba(198,40,44,0.20); }

    .badge {
      position:absolute; top:12px; left:12px;
      background:#c6282c; color:#fff;
      padding:6px 10px; border-radius:999px;
      font-weight:900; font-size:12px;
    }
    .tag { display:inline-block; padding:6px 10px; border-radius:999px; font-weight:900; font-size:12px; background:#f1f1f1; margin-top:6px; }

    @media (max-width: 920px){ .promo-item{ width: 42%; } }
    @media (max-width: 560px){
      .promo-item{ width: 92%; }
      .logo-container{ left:14px; }
      .logo-container img{ width:84px; height:84px; }
    }
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

<div class="promo-container">
  <h2>Promo VIP</h2>

  <div class="status-box">
    <div>Status Anda: <strong><?= htmlspecialchars($statusName) ?></strong></div>
    <div style="margin-top:6px;color:#444;">
      <strong>Promo aktif Anda:</strong> <?= htmlspecialchars($current["title"]) ?>
    </div>
    <div style="margin-top:10px;">
      <?php if (!$user): ?>
        <a href="tampilanloging.php" style="text-decoration:none;">
          <button style="width:auto;padding:10px 14px;">Login</button>
        </a>
      <?php else: ?>
        <a href="pesansekarang.php" style="text-decoration:none;">
          <button style="width:auto;padding:10px 14px;">Pesan Sekarang</button>
        </a>
      <?php endif; ?>
    </div>
    <div style="margin-top:10px;color:#666;">
      Catatan: promo dihitung otomatis di server saat membuat pesanan.
    </div>
  </div>

  <?php foreach ($levels as $lvl => $info): ?>
    <?php
      $isActive = ($vip_level === $lvl);
      $class = $isActive ? 'active' : 'locked';
      $label = ($lvl === 1 ? 'VIP 1 (Gold)' : ($lvl === 2 ? 'VIP 2 (Silver)' : 'VIP 3 (Bronze)'));
      $btnText = !$user ? "Login" : ($isActive ? "Gunakan Promo" : "Tidak berlaku untuk level Anda");
      $btnLink = !$user ? "tampilanloging.php" : ($isActive ? "pesansekarang.php" : "promo.php");
    ?>
    <div class="promo-item <?= $class ?>">
      <div class="badge"><?= htmlspecialchars($label) ?></div>
      <img src="briket.jpeg" alt="Promo <?= htmlspecialchars($label) ?>">
      <h2 style="margin:10px 0 6px;"><?= htmlspecialchars($info["title"]) ?></h2>
      <ul>
        <?php foreach ($info["lines"] as $ln): ?>
          <li><?= htmlspecialchars($ln) ?></li>
        <?php endforeach; ?>
      </ul>
      <div class="tag"><?= $isActive ? 'Promo aktif untuk level Anda' : 'Terkunci (beda level)' ?></div>
      <button onclick="location.href='<?= htmlspecialchars($btnLink) ?>'"><?= htmlspecialchars($btnText) ?></button>
    </div>
  <?php endforeach; ?>

  <div style="max-width:980px;margin:10px auto 0;">
    <div class="promo-item">
      <img src="briket2.jpg" alt="Info Level">
      <h2>Naik Level Member</h2>
      <p>Bronze (VIP 3): 3 Kg, Silver (VIP 2): 6 Kg, Gold (VIP 1): 12 Kg (berdasarkan pesanan Lunas).</p>
      <div class="tag">Auto naik saat Lunas</div>
      <button onclick="location.href='halamanberanda.php'">Lihat Status</button>
    </div>

    <div class="promo-item">
      <img src="pexels-pixabay-280229.jpg" alt="Belanja">
      <h2>Belanja Briket</h2>
      <p>Lakukan pemesanan untuk menambah total Kg dan naik level.</p>
      <div class="tag">Mulai dari 1 Kg</div>
      <button onclick="location.href='pesansekarang.php'">Beli Sekarang</button>
    </div>
  </div>

</div>
</body>
</html>
