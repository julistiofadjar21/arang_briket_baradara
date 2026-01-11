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

    // Ambil input
    $username = $conn->real_escape_string(trim($_POST["username"] ?? ""));
    $email    = $conn->real_escape_string(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $alamat   = $conn->real_escape_string(trim($_POST["alamat"] ?? ""));

    $role = "user";         // signup: user
    $table = "user";        // tabel kamu: user

    // Validasi dasar
    if ($username === "" || $email === "" || $password === "" || $alamat === "") {
        echo "<script>alert('Semua field wajib diisi.'); window.location.href='signup.html';</script>";
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Format email tidak valid.'); window.location.href='signup.html';</script>";
        exit();
    }

    // Cek duplikat email/username
    $stmt = $conn->prepare("SELECT id FROM `$table` WHERE email = ? OR username = ? LIMIT 1");
    if (!$stmt) {
        die("Persiapan query gagal: " . $conn->error);
    }
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $stmt->close();
        echo "<script>alert('Email/Username sudah terdaftar.'); window.location.href='signup.html';</script>";
        exit();
    }
    $stmt->close();

    // Handle upload foto (opsional)
    $fotoPath = null;

    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {
            echo "<script>alert('Upload foto gagal.'); window.location.href='signup.html';</script>";
            exit();
        }

        // Validasi ukuran max 2MB
        if ($_FILES["foto"]["size"] > 2 * 1024 * 1024) {
            echo "<script>alert('Ukuran foto maksimal 2MB.'); window.location.href='signup.html';</script>";
            exit();
        }

        // Validasi MIME
        $tmpName = $_FILES["foto"]["tmp_name"];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpName);
        finfo_close($finfo);

        $allowed = [
            "image/jpeg" => "jpg",
            "image/png"  => "png",
            "image/webp" => "webp"
        ];

        if (!isset($allowed[$mime])) {
            echo "<script>alert('Format foto harus JPG/PNG/WEBP.'); window.location.href='signup.html';</script>";
            exit();
        }

        // Pastikan folder upload ada
        $uploadDir = __DIR__ . "/uploads/profil/";
        if (!is_dir($uploadDir)) {
            // coba buat otomatis
            if (!mkdir($uploadDir, 0777, true)) {
                echo "<script>alert('Folder uploads/profil belum ada dan tidak bisa dibuat. Buat manual folder uploads/profil.'); window.location.href='signup.html';</script>";
                exit();
            }
        }

        // Nama file unik
        $ext = $allowed[$mime];
        $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($username));
        $newName = $safeBase . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;

        $dest = $uploadDir . $newName;

        if (!move_uploaded_file($tmpName, $dest)) {
            echo "<script>alert('Gagal menyimpan file upload.'); window.location.href='signup.html';</script>";
            exit();
        }

        // Simpan path relatif supaya bisa ditampilkan di HTML
        $fotoPath = "uploads/profil/" . $newName;
    }

    // Hash password
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Insert user (alamat + foto ikut masuk)
    $stmt = $conn->prepare("INSERT INTO `$table` (username, email, password, role, alamat, foto) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Persiapan query gagal: " . $conn->error);
    }

    $stmt->bind_param("ssssss", $username, $email, $hashed, $role, $alamat, $fotoPath);

    if ($stmt->execute()) {

        // Set session agar langsung tampil di halamanberanda.php
        $_SESSION["user"] = [
            "username" => $username,
            "email"    => $email,
            "alamat"   => $alamat,
            "role"     => $role,
            "foto"     => $fotoPath
        ];

        $stmt->close();
        $conn->close();

        header("Location: halamanberanda.php");
        exit();

    } else {
        $stmt->close();
        echo "<script>alert('Signup gagal.'); window.location.href='signup.html';</script>";
        exit();
    }
}

$conn->close();
?>
