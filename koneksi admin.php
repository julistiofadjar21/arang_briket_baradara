<?php
// Konfigurasi koneksi ke database
$host = "localhost";      // Server database
$user = "root";           // Username database (default di XAMPP adalah 'root')
$password = "";           // Password database (default di XAMPP adalah kosong)
$database = "web_bara_dara";      // Nama database yang telah Anda buat

// Membuat koneksi
$conn = new mysqli($host, $user, $password, $database);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
echo "Koneksi berhasil!";
?>
