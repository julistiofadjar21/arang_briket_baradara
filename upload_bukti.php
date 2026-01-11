<?php
session_start();

// --- Koneksi ke database ---
$koneksi = new mysqli("localhost", "root", "", "web_bara_dara");

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
$koneksi->set_charset("utf8mb4");

// Pastikan data dikirim via POST
$message = '';
$success = false;

// pastikan $id selalu ada untuk dipakai di HTML
$id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = isset($_POST['id_pesanan']) ? intval($_POST['id_pesanan']) : 0;
    $file = isset($_FILES['bukti_transfer']) ? $_FILES['bukti_transfer'] : null;

    /* =========================
       Validasi batas waktu 5 menit (server-side)
       - Deadline diset di pembayaran.php: $_SESSION['upload_deadline'][$id] = time()+300
       - Jika tidak ada deadline / sudah lewat => tolak upload
    ========================= */
    $deadlineOk = false;
    if ($id > 0 && isset($_SESSION['upload_deadline']) && is_array($_SESSION['upload_deadline']) && isset($_SESSION['upload_deadline'][$id])) {
        $deadline = (int)$_SESSION['upload_deadline'][$id];
        if (time() <= $deadline) {
            $deadlineOk = true;
        }
    }

    if (!$deadlineOk) {
        $message = "❌ Waktu upload bukti sudah habis (maksimal 5 menit) atau sesi tidak valid. Silakan ulangi proses pembayaran dari halaman pembayaran.";
    } else {

        if ($id && $file && $file['error'] === 0) {

            // Buat folder 'bukti' jika belum ada
            $folder = 'bukti';
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            // Validasi ekstensi file
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($ext, $allowed, true)) {
                $message = "❌ Format file tidak didukung. Gunakan JPG/PNG/PDF.";
            } else {

                // Buat nama file unik
                $namaFile = time() . '_' . preg_replace("/[^a-zA-Z0-9]/", "_", basename($file['name']));
                $target = $folder . '/' . $namaFile;

                // Pindahkan file
                if (move_uploaded_file($file['tmp_name'], $target)) {

                    // Update data pesanan di database:
                    // - simpan bukti_transfer
                    // - ubah status menjadi "Menunggu Konfirmasi" (kecuali kalau sudah Lunas, biarkan Lunas)
                    $stmt = $koneksi->prepare("
                        UPDATE pesanan
                        SET bukti_transfer = ?,
                            status_pesanan = IF(LOWER(status_pesanan)='lunas','Lunas','Menunggu Konfirmasi')
                        WHERE id_pesanan = ?
                    ");
                    if (!$stmt) {
                        $message = "❌ Gagal prepare query.";
                    } else {
                        $stmt->bind_param("si", $target, $id);

                        if ($stmt->execute()) {
                            $message = "✅ Bukti berhasil diunggah. Status pembayaran: MENUNGGU KONFIRMASI ADMIN.";
                            $success = true;

                            // Setelah sukses, hapus deadline agar tidak bisa upload ulang seenaknya
                            if (isset($_SESSION['upload_deadline'][$id])) {
                                unset($_SESSION['upload_deadline'][$id]);
                            }
                        } else {
                            $message = "❌ Gagal memperbarui data pesanan.";
                        }

                        $stmt->close();
                    }
                } else {
                    $message = "❌ Gagal mengunggah file.";
                }
            }
        } else {
            $message = "❌ Data pesanan atau file tidak valid.";
        }
    }

} else {
    $message = "❌ Akses tidak diperbolehkan.";
}

// Link cek status (kalau id valid)
$statusUrl = ($id > 0) ? "pembayaran.php?id_pesanan=" . urlencode((string)$id) : "";
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unggah Bukti Pembayaran</title>
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 500px;
        background: #fff;
        margin: 60px auto;
        padding: 30px 40px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        text-align: center;
    }
    h2 {
        color: #c62828;
        margin-bottom: 20px;
    }
    .message {
        padding: 15px;
        margin-bottom: 16px;
        border-radius: 10px;
        font-weight: 600;
        color: white;
    }
    .success { background-color: #2e7d32; }
    .error { background-color: #d32f2f; }

    form { text-align: left; }
    label {
        font-weight: 600;
        color: #333;
        display: block;
        margin-bottom: 8px;
    }
    input[type="file"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 8px;
        background-color: #fdfdfd;
        cursor: pointer;
    }
    input[type="file"]:hover { border-color: #c62828; }

    button, .btn-link {
        display: block;
        width: 100%;
        margin-top: 14px;
        background-color: #c62828;
        color: white;
        border: none;
        padding: 12px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s ease;
        text-decoration: none;
        text-align: center;
        box-sizing: border-box;
    }
    button:hover, .btn-link:hover { background-color: #b71c1c; }

    .btn-secondary{
        background:#455a64;
        margin-top:10px;
    }
    .btn-secondary:hover{ background:#263238; }

    .hint{
        margin-top: 10px;
        font-size: 12px;
        color: #555;
        line-height: 1.4;
    }

    /* Arrow back kiri atas */
    .back-btn {
        position: fixed;
        top: 38px;
        left: 38px;
        z-index: 9999;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        background: #e6e6e6;
        border: 1px solid #d0d0d0;
        color: #c62828;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: all 0.2s ease;
    }
    .back-btn:hover { background: #dcdcdc; color: #333; transform: translateY(-1px); }
    .back-btn:active { transform: translateY(0); }
    .back-btn span { font-size: 22px; line-height: 1; margin-left: -1px; }

    .page-title { padding-top: 6px; }
</style>
</head>
<body>

<a href="bahanbaku.php" class="back-btn" aria-label="Kembali">
    <span>←</span>
</a>

<div class="container">
    <h2 class="page-title">💰 Unggah Bukti Pembayaran</h2>

    <?php if ($message): ?>
        <div class="message <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <?php if ($statusUrl): ?>
            <a class="btn-link btn-secondary" href="<?php echo htmlspecialchars($statusUrl); ?>">
                ✅ Cek Status Pembayaran
            </a>
            <div class="hint">
                Klik tombol di atas untuk melihat status terbaru:
                <strong>Menunggu Konfirmasi</strong>, <strong>Lunas</strong>, atau <strong>Menolak</strong>.
            </div>
        <?php endif; ?>

    <?php else: ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_pesanan" value="<?php echo htmlspecialchars((string)$id); ?>">

            <label>Unggah Bukti Pembayaran (JPG/PNG/PDF):</label>
            <input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf" required>

            <button type="submit">📤 Kirim Bukti Pembayaran</button>

            <?php if ($statusUrl): ?>
                <a class="btn-link btn-secondary" href="<?php echo htmlspecialchars($statusUrl); ?>">
                    🔎 Kembali ke Halaman Pembayaran / Status
                </a>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
