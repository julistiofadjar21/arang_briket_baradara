<?php
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'user') {
  header("Location: tampilanloging.php?err=login");
  exit();
}

$user = $_SESSION['user'];
$username = (string)($user['username'] ?? '');

// ================= PROMO VIP (UNTUK TAMPILAN/ESTIMASI) =================
// Perhitungan final tetap dilakukan di proses_pesanan.php (server-side).
function promo_rule(int $vip_level): array {
  // "di atas pembelian 5 kg" => qty > 5
  if ($vip_level === 1) { // VIP 1 Bronze
    return ["diskon_persen" => 5, "bonus_kg" => 0, "bonus_min_kg" => 0, "gratis_ongkir" => true];
  }
  if ($vip_level === 2) { // VIP 2 Silver
    return ["diskon_persen" => 10, "bonus_kg" => 1, "bonus_min_kg" => 5, "gratis_ongkir" => false];
  }
  if ($vip_level === 3) { // VIP 3 Gold
    return ["diskon_persen" => 15, "bonus_kg" => 1, "bonus_min_kg" => 5, "gratis_ongkir" => false];
  }
  return ["diskon_persen" => 0, "bonus_kg" => 0, "bonus_min_kg" => 0, "gratis_ongkir" => false];
}

function level_name(int $vip): string {
  if ($vip === 1) return "VIP 1 (Bronze)";
  if ($vip === 2) return "VIP 2 (Silver)";
  if ($vip === 3) return "VIP 3 (Gold)";
  return "Basic";
}

// ================= AMBIL vip_level + total_kg + list bahan dari DB =================
$vip_level = 0;
$total_kg = 0.0;
$bahanList = [];

$koneksi = new mysqli("localhost", "root", "", "web_bara_dara");
if ($koneksi->connect_error) {
  die("Koneksi gagal: " . $koneksi->connect_error);
}
$koneksi->set_charset("utf8mb4");

// vip user
$stmtU = $koneksi->prepare("SELECT vip_level, total_kg FROM `user` WHERE username = ? LIMIT 1");
$stmtU->bind_param("s", $username);
$stmtU->execute();
$resU = $stmtU->get_result();
if ($resU && $resU->num_rows === 1) {
  $urow = $resU->fetch_assoc();
  $vip_level = (int)($urow['vip_level'] ?? 0);
  $total_kg  = (float)($urow['total_kg'] ?? 0);
  $_SESSION['user']['vip_level'] = $vip_level;
  $_SESSION['user']['total_kg']  = $total_kg;
}
$stmtU->close();

// list bahan baku
$resB = $koneksi->query("SELECT nama_bahan, harga_jual, stok FROM bahan_baku ORDER BY nama_bahan ASC");
if ($resB) {
  while ($r = $resB->fetch_assoc()) {
    $bahanList[] = $r;
  }
}
$koneksi->close();

$levelName = level_name($vip_level);
$promo = promo_rule($vip_level);
$diskon_persen = (int)$promo["diskon_persen"];
$bonus_kg = (int)$promo["bonus_kg"];
$bonus_min_kg = (int)$promo["bonus_min_kg"];
$gratis_ongkir = (bool)$promo["gratis_ongkir"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesan Briket Bara Dara</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<style>
body { background: #f8f9fa; padding: 40px; }
.card { max-width: 600px; margin: auto; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
button { background: #c62828; color: #fff; border: none; padding: 10px; width: 100%; border-radius: 8px; font-weight: 800; }
button:hover { background: #b71c1c; }

.member-box{
  background: #fff3f3;
  border: 1px solid rgba(198,40,40,0.20);
  border-radius: 12px;
  padding: 12px 14px;
  margin-bottom: 16px;
}
.badge-vip{
  display: inline-block;
  padding: 5px 10px;
  border-radius: 999px;
  font-weight: 900;
  font-size: 12px;
  border: 1px solid rgba(0,0,0,0.08);
}
.vip-gold{ background:#f6d365; color:#4a3600; }
.vip-silver{ background:#e0e0e0; color:#333; }
.vip-bronze{ background:#cd7f32; color:#fff; }
.vip-basic{ background:#f1f1f1; color:#333; }

.small-note{ font-size: 12px; color: #444; margin-top: 6px; }
.warn-note{ font-size: 12px; color: #b71c1c; margin-top: 6px; font-weight: 700; }
</style>
</head>
<body>

<div class="card">
  <h2 class="text-center mb-3">Pesan Briket Bara Dara</h2>

  <div class="member-box">
    <div>
      Status Member:
      <?php
        $cls = "vip-basic";
        if ($vip_level === 1) $cls = "vip-Bronze";
        elseif ($vip_level === 2) $cls = "vip-Silver";
        elseif ($vip_level === 3) $cls = "vip-Gold";
      ?>
      <span class="badge-vip <?= $cls ?>"><?= htmlspecialchars($levelName) ?></span>
    </div>

    <div class="small-note">
      Total belanja terverifikasi (Lunas): <strong><?= number_format($total_kg, 2, ',', '.') ?> Kg</strong>.
    </div>

    <div class="small-note">
      <?php if ($diskon_persen > 0): ?>
        Promo VIP aktif: <strong>diskon <?= $diskon_persen ?>%</strong>.
        <?php if ($bonus_kg > 0): ?>
          Bonus <strong><?= $bonus_kg ?> Kg</strong> jika pembelian <strong>di atas <?= $bonus_min_kg ?> Kg</strong>.
        <?php endif; ?>
        <?php if ($gratis_ongkir): ?>
          + <strong>Gratis ongkir</strong>.
        <?php endif; ?>
      <?php else: ?>
        Belum ada promo VIP untuk level Anda.
      <?php endif; ?>
    </div>

    <div class="small-note">
      Catatan: total final & promo dihitung di server saat pesanan dibuat.
    </div>
  </div>

  <form method="POST" action="/arang_briket_baradara/proses_pesanan.php">
    <div class="form-group">
      <label>Nama Pembeli</label>
      <input type="text" class="form-control" name="nama_pembeli" value="<?= htmlspecialchars($username) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Bahan Baku</label>
      <select class="form-control" name="bahan_baku" id="bahanBaku" required>
        <?php if (count($bahanList) === 0): ?>
          <option value="">(Bahan belum tersedia)</option>
        <?php else: ?>
          <?php foreach ($bahanList as $b): ?>
            <?php
              $nm = (string)$b['nama_bahan'];
              $hj = (int)($b['harga_jual'] ?? 0);
              $st = (int)($b['stok'] ?? 0);
            ?>
            <option value="<?= htmlspecialchars($nm) ?>" data-harga="<?= $hj ?>" data-stok="<?= $st ?>">
              <?= htmlspecialchars($nm) ?> — Rp<?= number_format($hj, 0, ',', '.') ?> /Kg (Stok: <?= $st ?> Kg)
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
      <div class="small-note" id="stokInfo"></div>
    </div>

    <div class="form-group">
      <label>Jumlah (Kg)</label>
      <input type="number" class="form-control" name="jumlah_kg" id="jumlah" min="1" required>
      <div class="warn-note" id="stokWarning" style="display:none;"></div>
    </div>

    <div class="form-group">
      <label>Total Harga (Estimasi)</label>
      <input type="text" class="form-control" id="totalHarga" readonly>
      <div class="small-note" id="promoInfo"></div>
    </div>

    <div class="form-group">
      <label>Tanggal Pemesanan</label>
      <input type="date" class="form-control" name="tanggal" id="tanggalPesan" required readonly>
    </div>

    <button type="submit">Pesan Sekarang</button>
  </form>
</div>

<script>
const DISKON_PERSEN = <?= (int)$diskon_persen ?>;
const BONUS_KG = <?= (int)$bonus_kg ?>;
const BONUS_MIN_KG = <?= (int)$bonus_min_kg ?>;
const GRATIS_ONGKIR = <?= $gratis_ongkir ? 'true' : 'false' ?>;

const bahan = document.getElementById("bahanBaku");
const jumlah = document.getElementById("jumlah");
const totalHargaEl = document.getElementById("totalHarga");
const promoInfoEl = document.getElementById("promoInfo");
const stokInfoEl = document.getElementById("stokInfo");
const stokWarningEl = document.getElementById("stokWarning");

function formatRupiah(n){
  return "Rp " + (n || 0).toLocaleString('id-ID');
}

function getSelected() {
  const opt = bahan.options[bahan.selectedIndex];
  const harga = parseInt(opt?.dataset.harga || "0", 10);
  const stok = parseInt(opt?.dataset.stok || "0", 10);
  return { harga, stok };
}

function updateStokInfo(){
  const { stok } = getSelected();
  stokInfoEl.textContent = stok > 0 ? `Stok tersedia: ${stok} Kg` : "";
}

function updateTotal(){
  const { harga, stok } = getSelected();
  const qty = parseInt(jumlah.value || "0", 10);

  stokWarningEl.style.display = "none";
  stokWarningEl.textContent = "";

  if (!qty || qty <= 0) {
    totalHargaEl.value = "";
    promoInfoEl.textContent = "";
    return;
  }

  // Validasi stok (estimasi). Validasi final tetap di server.
  // Bonus kg mempengaruhi stok kirim.
  const qualifiesBonus = (BONUS_KG > 0 && qty > BONUS_MIN_KG);
  const qtyKirim = qty + (qualifiesBonus ? BONUS_KG : 0);

  if (stok > 0 && qtyKirim > stok) {
    totalHargaEl.value = "";
    promoInfoEl.textContent = "";
    stokWarningEl.style.display = "block";
    stokWarningEl.textContent = `Stok tidak cukup untuk pesanan + bonus. Butuh ${qtyKirim} Kg, stok ${stok} Kg.`;
    return;
  }

  const subtotal = harga * qty;
  const diskonRp = Math.round(subtotal * (DISKON_PERSEN / 100));
  const total = Math.max(0, subtotal - diskonRp);

  let infoParts = [];
  if (DISKON_PERSEN > 0) infoParts.push(`Diskon ${DISKON_PERSEN}% (estimasi).`);
  else infoParts.push("Tidak ada diskon VIP.");

  if (BONUS_KG > 0) {
    if (qualifiesBonus) infoParts.push(`Bonus ${BONUS_KG} Kg aktif (dikirim total ${qtyKirim} Kg).`);
    else infoParts.push(`Bonus ${BONUS_KG} Kg berlaku jika pembelian di atas ${BONUS_MIN_KG} Kg.`);
  }

  if (GRATIS_ONGKIR) infoParts.push("Gratis ongkir (sesuai ketentuan).");

  totalHargaEl.value = formatRupiah(total);
  promoInfoEl.textContent = infoParts.join(" ");
}

bahan.addEventListener("change", () => { updateStokInfo(); updateTotal(); });
jumlah.addEventListener("input", updateTotal);

(function init(){
  // set tanggal hari ini
  const el = document.getElementById("tanggalPesan");
  const now = new Date();
  const yyyy = now.getFullYear();
  const mm = String(now.getMonth() + 1).padStart(2, '0');
  const dd = String(now.getDate()).padStart(2, '0');
  el.value = `${yyyy}-${mm}-${dd}`;

  updateStokInfo();
})();
</script>

</body>
</html>
