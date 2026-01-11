<?php
// Sertakan konfigurasi database
require 'config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Validasi data
    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
        $error = "Semua bidang wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Cek apakah email atau username sudah terdaftar
        $check_query = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $check_query->bind_param("ss", $email, $username);
        $check_query->execute();
        $result = $check_query->get_result();

        if ($result->num_rows > 0) {
            $error = "Email atau username sudah digunakan!";
        } else {
            // Insert data ke database
            $query = $conn->prepare("INSERT INTO users (full_name, email, username, password_hash, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
            $query->bind_param("ssssss", $full_name, $email, $username, $password_hash, $phone, $address);

            if ($query->execute()) {
                header("Location: signin.html");
                exit;
            } else {
                $error = "Terjadi kesalahan saat menyimpan data!";
            }
        }
    }
}
?>