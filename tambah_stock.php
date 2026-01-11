<?php
// Koneksi ke database
$koneksi = new mysqli("localhost", "root", "", "web_bara_dara");

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Jika tombol update ditekan
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $tambah = $_POST['tambah_stok'];

    // Update stok
    $update = $koneksi->query("UPDATE bahan_baku SET stok = stok + $tambah WHERE id = $id");

    if ($update) {
        $pesan = "<div class='alert success'>✅ Stok berhasil ditambahkan!</div>";
    } else {
        $pesan = "<div class='alert error'>❌ Gagal menambahkan stok!</div>";
    }
}

// Ambil data bahan baku
$result = $koneksi->query("SELECT * FROM bahan_baku");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Stok - Admin Bara Dara</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #fdfdfd;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #a71d2a;
            color: white;
            padding: 20px 0;
            text-align: center;
            letter-spacing: 1px;
            font-size: 24px;
        }

        h2 {
            text-align: center;
            color: #a71d2a;
            margin-top: 30px;
        }

        .container {
            width: 90%;
            margin: 20px auto;
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        th {
            background-color: #c62828;
            color: white;
        }

        tr:hover {
            background-color: #fff5f5;
        }

        input[type="number"] {
            width: 80px;
            padding: 5px;
            text-align: center;
        }

        button {
            background-color: #a71d2a;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #d32f2f;
        }

        .alert {
            text-align: center;
            padding: 10px;
            border-radius: 6px;
            margin: 10px auto;
            width: 80%;
        }

        .success {
            background-color: #c8e6c9;
            color: #256029;
            border: 1px solid #81c784;
        }

        .error {
            background-color: #ffcdd2;
            color: #b71c1c;
            border: 1px solid #ef9a9a;
        }

        footer {
            background-color: #a71d2a;
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: 40px;
            font-size: 14px;
        }

        @media screen and (max-width: 600px) {
            table, tr, td, th {
                font-size: 12px;
            }
        }

        /* ===== Arrow back kiri (diturunkan ke bawah header) ===== */
        .back-btn-fixed {
            position: fixed;
            top: 105px;          /* <-- ini yang mengatur turun/naik tombol */
            left: 80px;
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
            color: #eb1111ff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.2s ease;
        }
        .back-btn-fixed:hover {
            background: #dcdcdc;
            color: #333;
            transform: translateY(-1px);
        }
        .back-btn-fixed:active {
            transform: translateY(0);
        }
        .back-btn-fixed span {
            font-size: 22px;
            line-height: 1;
            margin-left: -1px;
        }
        /* ============================================= */
    </style>
</head>
<body>

<!-- Arrow back fixed kiri atas (di bawah header) -->
<a href="admin.html" class="back-btn-fixed" aria-label="Kembali ke halaman admin" title="Kembali">
    <span>&larr;</span>
</a>

<header>
    🧱 ADMIN PANEL - BRIKET BARA DARA
</header>

<?php if (isset($pesan)) echo $pesan; ?>

<div class="container">
    <h2>📦 Tambah Stok Bahan Baku</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Nama Bahan</th>
            <th>Harga Jual (Rp)</th>
            <th>Stok Sekarang (Kg)</th>
            <th>Tambah Stok (Kg)</th>
            <th>Aksi</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['nama_bahan']); ?></td>
            <td><?php echo number_format($row['harga_jual']); ?></td>
            <td><?php echo $row['stok']; ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <input type="number" name="tambah_stok" min="1" required>
            </td>
            <td>
                    <button type="submit" name="update">Tambah</button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>

    <!-- Tombol back bawah tengah DIHAPUS sesuai permintaan -->
</div>

<footer>
    © 2025 Bara Dara Charcoal — Admin Dashboard
</footer>

</body>
</html>
