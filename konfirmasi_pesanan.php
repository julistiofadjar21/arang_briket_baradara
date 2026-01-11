<?php
$koneksi = new mysqli("localhost", "root", "", "web_bara_dara");

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

$id = $_GET['id'] ?? '';

if (!empty($id)) {
    $update = $koneksi->query("UPDATE pesanan SET status='Lunas' WHERE id_pesanan='$id'");
    if ($update) {
        echo "<script>alert('Status pesanan berhasil dikonfirmasi menjadi LUNAS!'); window.location.href='pesanan_admin.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui status.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('ID pesanan tidak ditemukan.'); window.history.back();</script>";
}
?>
