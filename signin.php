<?php
session_start();

$servername = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "web_bara_dara";

$conn = new mysqli($servername, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $passwordInput = $_POST["password"] ?? "";
    $role = trim($_POST["role"] ?? "user");

    if ($email === "" || $passwordInput === "" || $role === "") {
        header("Location: tampilanloging.php?err=1");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: tampilanloging.php?err=1");
        exit();
    }

    $table = ($role === "admin") ? "admin" : "user";

    $stmt = $conn->prepare("SELECT * FROM `$table` WHERE email = ? LIMIT 1");
    if (!$stmt) {
        die("Query gagal dipersiapkan: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $u = $res->fetch_assoc();
        $dbPassword = $u["password"] ?? "";

        if (password_verify($passwordInput, $dbPassword)) {

            $vip_level = 0;
            $total_kg  = 0.0;
            if ($role !== "admin") {
                $vip_level = (int)($u["vip_level"] ?? 0);
                $total_kg  = (float)($u["total_kg"] ?? 0);
            }

            $_SESSION["user"] = [
                "email" => $u["email"] ?? $email,
                "username" => $u["username"] ?? "",
                "alamat" => $u["alamat"] ?? "",
                "role" => $role,
                "foto" => $u["foto"] ?? null,
                "vip_level" => $vip_level,
                "total_kg"  => $total_kg
            ];

            $_SESSION["username"] = $u["username"] ?? "";
            $_SESSION["role"] = $role;

            if ($role === "admin") {
                header("Location: admin.html");
            } else {
                header("Location: halamanberanda.php");
            }
            exit();
        }
    }

    header("Location: tampilanloging.php?err=1");
    exit();
}

$conn->close();
?>
