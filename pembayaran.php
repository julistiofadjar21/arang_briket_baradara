<?php
session_start();

$koneksi = new mysqli("localhost", "root", "", "web_bara_dara");
$koneksi->set_charset("utf8mb4");

$id = isset($_GET['id_pesanan']) ? (int)$_GET['id_pesanan'] : 0;
$data = null;

if ($id > 0) {
    $stmt = $koneksi->prepare("SELECT * FROM pesanan WHERE id_pesanan = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res ? $res->fetch_assoc() : null;
    $stmt->close();
}

/* =========================
   Normalisasi status + bukti
========================= */
$status_raw  = $data ? (string)($data['status_pesanan'] ?? '') : '';
$status_norm = strtolower(trim($status_raw));
$bukti_path  = $data ? trim((string)($data['bukti_transfer'] ?? '')) : '';
$hasBukti    = ($bukti_path !== '');

/* =========================
   Mapping tampilan status (UI)
   - DB: Belum Lunas / Menunggu Konfirmasi / Lunas / Menolak
   - UI: Belum Lunas / Menunggu Konfirmasi / Diterima / Ditolak
========================= */
function ui_status_label($status_norm, $hasBukti) {
    if ($status_norm === 'lunas') return 'Diterima';
    if ($status_norm === 'menolak') return 'Ditolak';
    if ($status_norm === 'menunggu konfirmasi') return 'Menunggu Konfirmasi';

    // Fallback penting: kalau bukti sudah ada tapi status belum kebaca benar,
    // tetap anggap Menunggu Konfirmasi agar tidak muncul upload lagi.
    if ($hasBukti) return 'Menunggu Konfirmasi';

    return 'Belum Lunas';
}

function ui_status_class($status_norm, $hasBukti) {
    if ($status_norm === 'lunas') return 'diterima';
    if ($status_norm === 'menolak') return 'ditolak';
    if ($status_norm === 'menunggu konfirmasi') return 'menunggu';
    if ($hasBukti) return 'menunggu';
    return 'belum';
}

$uiLabel = $data ? ui_status_label($status_norm, $hasBukti) : '';
$uiClass = $data ? ui_status_class($status_norm, $hasBukti) : '';

/* =========================
   Aturan upload:
   - HANYA boleh upload jika:
     status = "Belum Lunas" DAN belum ada bukti
========================= */
$canUpload = false;
if ($data) {
    $canUpload = ($status_norm === 'belum lunas' && !$hasBukti);
}

/* =========================
   Batas waktu upload 5 menit (session-based)
   - dibuat hanya saat canUpload = true
   - dibersihkan kalau sudah tidak canUpload (misal bukti sudah ada)
========================= */
$remainingSeconds = 0;
$isExpired = true;

if ($data && $canUpload) {
    if (!isset($_SESSION['upload_deadline']) || !is_array($_SESSION['upload_deadline'])) {
        $_SESSION['upload_deadline'] = [];
    }

    if (!isset($_SESSION['upload_deadline'][$id])) {
        $_SESSION['upload_deadline'][$id] = time() + 300; // 5 menit
    }

    $deadline = (int)$_SESSION['upload_deadline'][$id];
    $remainingSeconds = $deadline - time();
    if ($remainingSeconds < 0) $remainingSeconds = 0;

    $isExpired = ($remainingSeconds <= 0);
} else {
    if (isset($_SESSION['upload_deadline'][$id])) {
        unset($_SESSION['upload_deadline'][$id]);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pembayaran</title>
<style>
body { font-family: 'Poppins', sans-serif; background: #f8f9fa; padding: 50px; text-align: center; }
.card { background: #fff; padding: 30px; border-radius: 15px; max-width: 450px; margin:auto; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }

button { background:#c62828; color:#fff; border:none; padding:12px; border-radius:8px; width:100%; margin-top:15px; cursor:pointer; }
button:hover { background:#b71c1c; }
input[type="file"] { width:100%; padding:10px; margin-top:10px; border-radius:8px; border:1px solid #ccc; }

.status-badge{
  display:inline-block;
  padding:7px 14px;
  border-radius:12px;
  font-weight:900;
  font-size:12px;
  margin: 10px 0 16px 0;
  color:#fff;
}
.status-badge.menunggu{ background:#0d47a1; }  /* biru tua */
.status-badge.diterima{ background:#2e7d32; }  /* hijau */
.status-badge.ditolak{ background:#d32f2f; }   /* merah */
.status-badge.belum{ background:#f57c00; }     /* oranye */

.note{
  margin: 12px 0;
  padding: 10px 12px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 13px;
}
.note.info{ background:#e3f2fd; border:1px solid #bbdefb; color:#0d47a1; }
.note.ok{ background:#e8f5e9; border:1px solid #c8e6c9; color:#1b5e20; }
.note.bad{ background:#ffebee; border:1px solid #ffcdd2; color:#b71c1c; }

/* =========================
   Timer: dibuat lebih kecil (mirip badge status)
========================= */
.timer-wrap{
  display:flex;
  justify-content:center;
  align-items:center;
  margin: 6px 0 12px 0;
}
.timer-float{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:7px 14px;                 /* kecil seperti status badge */
  border-radius:12px;
  box-shadow:0 2px 8px rgba(0,0,0,0.08);
  font-weight:900;
  font-size:12px;
  user-select:none;
}
.timer-float.ok{ background:#d7f4ed; border:1px solid #00c030; color:#05aa2b; }
.timer-float.expired{ background:#ffebee; border:1px solid #ffcdd2; color:#b71c1c; }

.timer-badge{
  width:12px;
  height:12px;
  border-radius:10px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  font-size:14px;
  line-height:1;
  background:rgba(255,255,255,0.65);
  border:1px solid rgba(0,0,0,0.05);
}

.timer-text{
  display:inline-flex;
  flex-direction:column;            /* tetap 2 baris, tapi lebih kecil */
  align-items:flex-start;
  gap:1px;
}
.timer-text small{
  font-weight:800;
  opacity:0.9;
  font-size:9px;
}
.timer-text strong{
  font-weight:900;
  font-size:13px;
  letter-spacing:0.3px;
}

/* =========================
   Tombol Refresh Status: rapat, tidak full, font lebih besar
   (tanpa ubah HTML tombol)
========================= */
button[onclick*="location.reload"]{
  width:auto !important;
  display:inline-block;
  padding:10px 14px;
  border-radius:10px;
  font-size:15px;
  font-weight:900;
  margin-top:10px;
}

a.link-btn{
  display:inline-block;
  margin-top:10px;
  text-decoration:none;
  font-weight:800;
  color:#c62828;
}
</style>
</head>
<body>

<div class="card">
  <h2>💰 Pembayaran Pesanan</h2>

  <?php if($data): ?>
    <!-- Status (selalu tampil) -->
    <div class="status-badge <?= htmlspecialchars($uiClass) ?>">
      Status: <?= htmlspecialchars($uiLabel) ?>
    </div>

    <p><strong>Nama:</strong> <?= htmlspecialchars($data['nama_pembeli']) ?></p>
    <p><strong>Bahan Baku:</strong> <?= htmlspecialchars($data['bahan_baku']) ?></p>
    <p><strong>Total Bayar:</strong> Rp <?= number_format((int)$data['total_harga'],0,',','.') ?></p>

    <?php if ($uiLabel === 'Menunggu Konfirmasi'): ?>
      <div class="note info">
        Bukti sudah dikirim. Silakan klik Refresh untuk melihat keputusan admin.
      </div>
      <button type="button" onclick="location.reload()">🔄 Refresh Status</button>
      <a class="link-btn" href="bahanbaku.php">Kembali ke Bahan Baku</a>

    <?php elseif ($uiLabel === 'Diterima'): ?>
      <div class="note ok">
        Pembayaran diterima. Terima kasih!
      </div>
      <a class="link-btn" href="bahanbaku.php">Kembali ke Bahan Baku</a>

    <?php elseif ($uiLabel === 'Ditolak'): ?>
      <div class="note bad">
        Pembayaran ditolak oleh admin. Jika ingin melanjutkan, silakan lakukan pemesanan ulang.
      </div>
      <a class="link-btn" href="pesansekarang.php">Pesan Ulang</a>

    <?php else: ?>
      <!-- Belum Lunas (baru tampil QR + upload) -->
      <img src="qris bisnis.jpg" alt="QRIS" width="200"><br><br>

      <?php if ($canUpload): ?>
        <div class="timer-wrap">
          <div id="timerFloat" class="timer-float <?= $isExpired ? 'expired' : 'ok' ?>">
            <span class="timer-badge">⏱</span>
            <span class="timer-text">
              <small>Batas upload bukti</small>
              <strong id="countdown"><?= $isExpired ? 'HABIS' : '--:--' ?></strong>
            </span>
          </div>
        </div>

        <form action="upload_bukti.php" method="POST" enctype="multipart/form-data" id="formUpload">
          <input type="hidden" name="id_pesanan" value="<?= (int)$data['id_pesanan'] ?>">
          <label>Unggah Bukti Pembayaran:</label>
          <input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf" required id="fileInput">
          <button type="submit" id="btnSubmit">Kirim Bukti</button>
        </form>

        <script>
        (function(){
          var remaining = <?= (int)$remainingSeconds ?>;

          var countdownEl = document.getElementById('countdown');
          var timerFloat  = document.getElementById('timerFloat');
          var fileInput   = document.getElementById('fileInput');
          var btnSubmit   = document.getElementById('btnSubmit');
          var formUpload  = document.getElementById('formUpload');

          function pad(n){ return (n < 10 ? '0' : '') + n; }

          function setExpiredUI(){
            if (timerFloat){
              timerFloat.classList.remove('ok');
              timerFloat.classList.add('expired');
            }
            if (countdownEl) countdownEl.textContent = "HABIS";
            if (fileInput) fileInput.disabled = true;
            if (btnSubmit) btnSubmit.disabled = true;

            if (formUpload) {
              formUpload.addEventListener('submit', function(e){
                e.preventDefault();
                alert('Waktu upload bukti sudah habis (5 menit).');
                return false;
              }, { once: true });
            }
          }

          function tick(){
            if (remaining <= 0){
              setExpiredUI();
              return;
            }
            var m = Math.floor(remaining / 60);
            var s = remaining % 60;
            if (countdownEl) countdownEl.textContent = pad(m) + ":" + pad(s);
            remaining--;
            setTimeout(tick, 1000);
          }

          if (remaining <= 0) setExpiredUI();
          else tick();
        })();
        </script>
      <?php endif; ?>
    <?php endif; ?>

  <?php else: ?>
    <p style="color:red;">⚠️ Data pesanan tidak ditemukan.</p>
  <?php endif; ?>
</div>

</body>
</html>
