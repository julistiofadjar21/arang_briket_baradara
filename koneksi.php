<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'web_bara_dara';

// Buat koneksi
$koneksi = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
// Jangan ditutup di sini agar bisa dipakai file lain
?>
