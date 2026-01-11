<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

// ====== WAJIB LOGIN USER ======
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'user') {
    header("Location: tampilanloging.php?err=login");
    exit();
}

$koneksi = new mysqli("localhost", "root", "", "web_bara_dara");
$koneksi->set_charset("utf8mb4");

// Ambil data dari form (nama TIDAK dipakai dari POST demi keamanan)
$nama    = trim((string)($_SESSION['user']['username'] ?? ''));
$bahan   = trim((string)($_POST['bahan_baku'] ?? ''));
$jumlah  = (int)($_POST['jumlah_kg'] ?? 0);
$tanggal = trim((string)($_POST['tanggal'] ?? ''));

if ($nama === '' || $bahan === '' || $jumlah <= 0 || $tanggal === '') {
    die("Input tidak valid. Pastikan semua field terisi.");
}

// Validasi format tanggal (YYYY-MM-DD)
$dt = DateTime::createFromFormat('Y-m-d', $tanggal);
if (!$dt || $dt->format('Y-m-d') !== $tanggal) {
    die("Format tanggal tidak valid.");
}

// ====== RULE PROMO VIP (OPS A) ======
function promo_by_vip(int $vip_level, int $qty): array {
    // "di atas pembelian 5 kg" => qty > 5
    if ($vip_level === 1) { // VIP 1 (Gold)
        return [
            "diskon_persen" => 15,
            "bonus_kg" => ($qty > 5 ? 1 : 0),
            "gratis_ongkir" => 1
        ];
    }
    if ($vip_level === 2) { // VIP 2 (Silver)
        return [
            "diskon_persen" => 10,
            "bonus_kg" => ($qty > 5 ? 1 : 0),
            "gratis_ongkir" => 0
        ];
    }
    if ($vip_level === 3) { // VIP 3 (Bronze)
        return [
            "diskon_persen" => 5,
            "bonus_kg" => 0,
            "gratis_ongkir" => 0
        ];
    }
    return [
        "diskon_persen" => 0,
        "bonus_kg" => 0,
        "gratis_ongkir" => 0
    ];
}

try {
    $koneksi->begin_transaction();

    // 1) Ambil vip_level user (LOCK row user saat transaksi)
    $vip_level = 0;
    $stmtVip = $koneksi->prepare("SELECT vip_level FROM `user` WHERE username = ? LIMIT 1 FOR UPDATE");
    $stmtVip->bind_param("s", $nama);
    $stmtVip->execute();
    $resVip = $stmtVip->get_result();

    if (!$resVip || $resVip->num_rows !== 1) {
        throw new Exception("User tidak ditemukan.");
    }

    $u = $resVip->fetch_assoc();
    $vip_level = (int)($u['vip_level'] ?? 0);
    $stmtVip->close();

    // 2) Ambil bahan baku + lock row (exact match)
    $stmt = $koneksi->prepare("
        SELECT id, nama_bahan, harga_jual, stok
        FROM bahan_baku
        WHERE nama_bahan = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->bind_param("s", $bahan);
    $stmt->execute();
    $res = $stmt->get_result();

    if (!$res || $res->num_rows !== 1) {
        throw new Exception("Bahan baku tidak ditemukan di tabel bahan_baku.");
    }

    $bb = $res->fetch_assoc();
    $stmt->close();

    $hargaKg  = (int)($bb['harga_jual'] ?? 0);
    $stok_now = (int)($bb['stok'] ?? 0);
    $bahan_simpan = (string)$bb['nama_bahan'];

    // 3) Hitung promo (SERVER-SIDE) sesuai VIP
    $promo = promo_by_vip($vip_level, $jumlah);
    $diskon_persen = (int)$promo["diskon_persen"];
    $bonus_kg = (int)$promo["bonus_kg"];
    $gratis_ongkir = (int)$promo["gratis_ongkir"];

    // Stok harus cukup untuk jumlah kirim (jumlah + bonus)
    $qty_kirim = $jumlah + $bonus_kg;
    if ($qty_kirim > $stok_now) {
        throw new Exception("Stok tidak cukup untuk pesanan + bonus. Sisa stok: {$stok_now} Kg.");
    }

    // Hitung harga
    $subtotal = $hargaKg * $jumlah; // bayar berdasarkan jumlah yg dipesan (bukan bonus)
    $diskon_rp = (int)round($subtotal * ($diskon_persen / 100));
    $total = max(0, $subtotal - $diskon_rp);

    // 4) Simpan pesanan (stok baru dipotong saat admin approve)
    // Pastikan kolom ops A sudah ada: subtotal, diskon_persen, diskon_rp, bonus_kg, gratis_ongkir
    $stmt3 = $koneksi->prepare("
        INSERT INTO pesanan
          (nama_pembeli, bahan_baku, jumlah_kg, subtotal, diskon_persen, diskon_rp, bonus_kg, gratis_ongkir,
           total_harga, tanggal, status_pesanan, stok_dipotong)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Belum Lunas', 0)
    ");

    // 2 string + 7 int + 1 string = ssiiiiiiis
    $stmt3->bind_param(
        "ssiiiiiiis",
        $nama,
        $bahan_simpan,
        $jumlah,
        $subtotal,
        $diskon_persen,
        $diskon_rp,
        $bonus_kg,
        $gratis_ongkir,
        $total,
        $tanggal
    );

    $stmt3->execute();
    $id = (int)$koneksi->insert_id;
    $stmt3->close();

    // 5) Isi kode_pesanan (BRD01, BRD02, dst)
    $kode = 'BRD' . str_pad((string)$id, 2, '0', STR_PAD_LEFT);

    $stmtK = $koneksi->prepare("
        UPDATE pesanan
        SET kode_pesanan = ?
        WHERE id_pesanan = ?
        LIMIT 1
    ");
    $stmtK->bind_param("si", $kode, $id);
    $stmtK->execute();

    if ($stmtK->affected_rows !== 1) {
        throw new Exception("Gagal mengisi kode_pesanan. Pastikan file proses_pesanan.php yang dipanggil benar.");
    }
    $stmtK->close();

    $koneksi->commit();

    header("Location: pembayaran.php?id_pesanan=$id");
    exit();

} catch (Throwable $e) {
    $koneksi->rollback();
    echo "Gagal memproses pesanan: " . htmlspecialchars($e->getMessage());
}
