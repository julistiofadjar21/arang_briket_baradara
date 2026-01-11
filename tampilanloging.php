<?php
// Start session
session_start();

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "web_bara_dara";
// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize error message
$error_message = "";

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query to check user credentials
    $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Redirect to home page if login is successful
        header("Location: halamanberanda.php");
        exit();
    } else {
        $error_message = "Invalid email or password";
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In</title>
  <style>
    :root{
      --red: #c62828;
      --red-dark: #b71c1c;
      --soft: #fff5f5;
      --text: #222;
      --muted: #666;
      --border: #e6e6e6;
    }

    *{ box-sizing: border-box; }

    body {
      margin: 0;
      padding: 18px;
      font-family: 'Poppins', Arial, sans-serif;
      background: linear-gradient(135deg, var(--soft), #ffffff);
      min-height: 100vh;

      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 18px;
    }

    /* ===== Logo Center (tanpa garis, tanpa teks, tanpa tombol kanan) ===== */
    .top-logo {
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      margin-top: 6px;
    }

    .top-logo img {
      width: 180px;
      height: 180px;
      border-radius: 50%;
      object-fit: cover;
      background: #fff;
      border: 2px solid rgba(198,40,40,0.25);
      box-shadow: 0 8px 22px rgba(0,0,0,0.12);
    }

    .container {
      width: 90%;
      max-width: 880px;
      display: flex;
      background: #fff;
      box-shadow: 0 8px 28px rgba(0, 0, 0, 0.12);
      border-radius: 14px;
      overflow: hidden;
      border: 1px solid var(--border);
    }

    .left, .right {
      width: 50%;
      padding: 34px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
    }

    .left { background-color: #fff; }

    .right {
      background: linear-gradient(135deg, var(--red), var(--red-dark));
      color: #fff;
      text-align: center;
    }

    h1 {
      font-size: 2.1em;
      margin: 0 0 12px 0;
      color: var(--text);
      text-align: center;
    }

    .right h1 { color: #fff; }

    .social-icons {
      display: flex;
      gap: 14px;
      margin-bottom: 14px;
    }

    .social-icons i {
      font-size: 1.3em;
      color: #444;
      transition: color 0.2s ease;
    }

    .social-icons i:hover {
      color: var(--red);
    }

    p {
      margin: 6px 0 12px 0;
      color: var(--muted);
      text-align: center;
    }

    .right p { color: rgba(255,255,255,0.92); }

    input, select {
      width: 100%;
      padding: 11px 12px;
      margin: 10px 0;
      border: 1px solid #ddd;
      border-radius: 10px;
      box-sizing: border-box;
      font-size: 14px;
      outline: none;
      background: #fff;
    }

    input:focus, select:focus {
      border-color: rgba(198,40,40,0.55);
      box-shadow: 0 0 0 4px rgba(198,40,40,0.10);
    }

    button {
      width: 100%;
      padding: 11px 14px;
      background-color: var(--red);
      border: none;
      border-radius: 10px;
      color: #fff;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      transition: 0.2s ease;
    }

    button:hover {
      background-color: var(--red-dark);
      transform: translateY(-1px);
    }

    button:active { transform: translateY(0); }

    .right button {
      background-color: #fff;
      color: var(--red);
      font-weight: 700;
    }

    .right button:hover {
      background-color: #fff;
      color: var(--red-dark);
      transform: translateY(-1px);
    }

    form {
      width: 100%;
      max-width: 400px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .error-message {
      width: 100%;
      max-width: 360px;
      background: #ffe3e6;
      border: 1px solid #ffb3bd;
      color: #b71c1c;
      padding: 10px 12px;
      border-radius: 10px;
      margin: 10px 0 0 0;
      font-size: 14px;
      text-align: center;
      font-weight: 700;
    }

    /* Responsive */
    @media(max-width: 768px){
      body { padding: 14px; }
      .container { flex-direction: column; width: 100%; max-width: 560px; }
      .left, .right { width: 100%; padding: 26px; }
      h1 { font-size: 1.8em; }
      .top-logo img { width: 84px; height: 84px; }
    }

    @media(max-width: 414px){
      h1 { font-size: 1.6em; }
      input, select { font-size: 13.5px; }
      button { font-size: 14px; }
      .top-logo img { width: 78px; height: 78px; }
    }
  </style>
</head>
<body>

  <!-- Logo di tengah (tanpa header, tanpa teks, tanpa tombol kanan) -->
  <div class="top-logo">
    <img src="LOGO.OKEMI.png" alt="Logo Toko">
  </div>

  <div class="container">
    <div class="left">
      <h1>Masuk</h1>
      <div class="social-icons">
        <i class="fab fa-facebook-f"></i>
        <i class="fas fa-envelope"></i>
        <i class="fab fa-linkedin-in"></i>
      </div>
      <p>Masukkan Akun Kamu</p>
      <?php if (!empty($error_message)): ?>
        <p class="error-message"><?php echo $error_message; ?></p>
      <?php endif; ?>
      <form action="signin.php" method="post">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="role" required>
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>
        <button type="submit">Masukkan Akun</button>
      </form>
    </div>
    <div class="right">
      <h1>Hallo, Guys!</h1>
      <p>Buat Akun, Dan Selamat Berbelanja</p>
      <a href="signup.html"><button>Buat Akun</button></a>
    </div>
  </div>

</body>
</html>
