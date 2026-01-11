<?php
session_start();
$user = $_SESSION["user"] ?? null;
$foto = ($user && !empty($user["foto"])) ? $user["foto"] : "user.log.png";

/* ✅ Tambahan VIP (tanpa mengubah bagian lain)
   Pastikan data vip level disimpan di session user, misalnya: $_SESSION["user"]["vip_level"]
*/
$vipLevel = $user["vip_level"] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRIKET BARA DARA</title>

    <style>
        header {
            background-color: #c62828;
            color: #fff;
            padding: 10px 0;
            text-align: center;
            position: relative;
        }

        header h1 {
            margin: 0;
            font-size: 32px;
            line-height: 1.2;
            text-align: center;
        }

        nav {
            background-color: #c6282c;
            padding: 10px 0;
        }

        nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        nav li {
            display: inline-block;
            margin: 0 10px;
        }

        nav a {
            color: #fff;
            text-decoration: none;
            padding: 10px 10px;
            border-radius: 5px;
        }

        nav a:hover {
            background-color: #d32f2f;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #c6282c;
        }

        .container {
            padding: 20px;
            background-color: #f0f4f6;
            color: #a71d2a;
        }

        /* 🌟 Tampilan Sejarah & Pemilik seperti Sambutan Rektor */
        .history-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            max-width: 1000px;
            margin: 60px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            overflow: hidden;
        }

        .history-content {
            flex: 1;
            padding: 40px 50px;
            text-align: justify;
        }

        .history-content h2 {
            font-size: 28px;
            color: #a71d2a;
            margin-bottom: 15px;
            text-align: left;
        }

        .history-content p {
            line-height: 1.8;
            font-size: 15px;
            color: #333;
        }

        .owner-photo {
            flex: 0 0 320px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f8f9fa;
            padding: 30px;
        }

        .owner-photo img {
            width: 90%;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.2);
        }

        .owner-photo p {
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
            color: #a71d2a;
        }

        @media (max-width: 768px) {
            .history-section {
                flex-direction: column;
                text-align: center;
            }
            .owner-photo {
                width: 100%;
                padding: 20px 0;
            }
            .owner-photo img {
                width: 70%;
            }
            .history-content {
                padding: 25px;
            }
        }

        /* === Galeri Produk sejajar header === */
        .gallery {
            margin: 0;
            padding: 0;
            width: 100%;
            text-align: center;
            border: none;
        }

        .gallery-images {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 400px;
            overflow: hidden;
            border: none;
            line-height: 0;
            position: relative;
        }

        .gallery-images img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }

        .gallery-images img.active {
            opacity: 1;
        }

        .cta {
            text-align: center;
            margin-top: 30px;
        }

        .cta button {
            background-color: #a71d2a;
            color: #fff;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .footer {
            background-color: #a71d2a;
            padding: 10px 0;
            text-align: center;
            margin-top: 20px;
        }

        .footer p {
            color: #fff;
            margin: 0;
        }

        .logo-container {
            position: absolute;
            top: 3px;
            left: 50px;
        }

        .logo-container img {
            width: 131px;
            height: 131px;
            border-radius: 100%;
        }

        .logout-button {
            position: absolute;
            top: 30px;
            right: 30px;
        }

        .logout-button a {
            color: white;
            text-decoration: none;
            background-color: #c6282c;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
        }

        .logout-button img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* === Tombol & Popup Bantuan === */
        .help-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background-color: #c6282c;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 55px;
            height: 55px;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            z-index: 1000;
        }

        .help-popup {
            display: none;
            position: fixed;
            bottom: 90px;
            right: 25px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            width: 280px;
            z-index: 1000;
        }

        .help-popup-header {
            background-color: #c6282c;
            color: #fff;
            padding: 10px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .help-popup-content {
            padding: 15px;
            color: #333;
            font-size: 14px;
        }

        .help-popup-content a {
            display: block;
            background-color: #c6282c;
            color: #fff;
            text-decoration: none;
            text-align: center;
            padding: 8px;
            border-radius: 6px;
            margin-top: 10px;
        }

        .help-popup-content a:hover {
            background-color: #a71d2a;
        }

        .close-popup {
            background: none;
            border: none;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
        }

        /* Profil dropdown */
        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: #c6282c;
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 10px 14px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.18);
        }

        .user-pill img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-pill .user-name {
            font-weight: bold;
            font-size: 14px;
            white-space: nowrap;
            max-width: 160px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-dropdown {
            display: none;
            position: absolute;
            top: 70px;
            right: 0;
            width: 260px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            overflow: hidden;
            z-index: 1200;
        }

        .user-dropdown-header {
            padding: 14px;
            background: #f5f5f5;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .user-dropdown-header img {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: #fff;
            object-fit: cover;
        }

        .user-meta-name {
            font-weight: bold;
            color: #a71d2a;
            font-size: 15px;
            line-height: 1.2;
        }

        .user-meta-email {
            font-size: 12px;
            color: #666;
            word-break: break-word;
        }

        .user-dropdown-body {
            padding: 14px;
            font-size: 13px;
            color: #333;
        }

        .user-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 6px 0;
            border-bottom: 1px solid #eee;
        }

        .user-row:last-child {
            border-bottom: none;
        }

        /* ==============================
           UPDATE: Logout kecil (terbaru)
           ============================== */
        .logout-action{
            display: inline-flex;
            align-items: center;
            justify-content: center;

            width: 92px;
            height: 36px;
            margin: 12px auto 14px;

            padding: 0;
            border: none;
            border-radius: 999px;

            background: #c6282c;
            color: #fff;

            font-weight: 700;
            font-size: 12px;

            cursor: pointer;

            text-align: center;
            text-decoration: none;
        }

        .logout-action:hover{
            background: #a71d2a;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <img src="LOGO.OKEMI.png" alt="Logo Toko">
        </div>
        <h1>ARANG BRIKET</h1>
        <h1>BARA DARA</h1>

        <div class="logout-button" id="userArea">
            <?php if (!$user): ?>
                <a href="tampilanloging.php" title="Login">
                    <img src="user.log.png" alt="Login">
                </a>
            <?php else: ?>
                <button type="button" class="user-pill" id="profileBtn" title="Profil">
                    <img src="<?php echo htmlspecialchars($foto); ?>" alt="User">
                    <span class="user-name"><?php echo htmlspecialchars($user["username"] ?? "User"); ?></span>
                </button>

                <div class="user-dropdown" id="userDropdown">
                    <div class="user-dropdown-header">
                        <img src="<?php echo htmlspecialchars($foto); ?>" alt="User">
                        <div>
                            <div class="user-meta-name"><?php echo htmlspecialchars($user["username"] ?? "-"); ?></div>
                            <div class="user-meta-email"><?php echo htmlspecialchars($user["email"] ?? "-"); ?></div>
                        </div>
                    </div>
                    <div class="user-dropdown-body">
                        <div class="user-row">
                            <span><strong>Alamat</strong></span>
                            <span><?php echo htmlspecialchars($user["alamat"] ?? "-"); ?></span>
                        </div>
                        <div class="user-row">
                            <span><strong>Role</strong></span>
                            <span><?php echo htmlspecialchars($user["role"] ?? "-"); ?></span>
                        </div>

                        <!-- ✅ Tambahan VIP Level (tepat di bawah Role) -->
                        <div class="user-row">
                            <span><strong>VIP Level</strong></span>
                            <span><?php echo htmlspecialchars($vipLevel ?? "-"); ?></span>
                        </div>
                    </div>

                    <a href="logout.php" class="logout-action"
                       onclick="return confirm('Apakah anda yakin ingin keluar?')">Logout</a>
                </div>
            <?php endif; ?>
        </div>~
    </header>

    <nav>
        <ul>
            <li><a href="halamanberanda.php">Beranda</a></li>
            <li><a href="tentangkami.html">Tentang Kami</a></li>
            <li><a href="kontak.html">Kontak</a></li>
            <li><a href="bahanbaku.php">Bahan Baku</a></li>
            <li><a href="promo.php">Promo</a></li>
            <li><a href="tes_pesanansaya.php">Pesanan saya</a></li>
        </ul>
    </nav>

    <div class="gallery">
        <div class="gallery-images">
            <img src="1.jpeg" alt="Produk 1" class="active">
            <img src="vip 1.jpeg" alt="Produk 2">
            <img src="vip 2.jpeg" alt="Produk 3">
            <img src="vip 3.jpeg" alt="Produk 4">
        </div>
    </div>

    <div class="container">
        <div class="history-section">
            <div class="history-content">
                <h2>Sejarah Terbentuknya Arang Briket Bara Dara</h2>
                <p>
                    Arang Briket Bara Dara berdiri dari semangat untuk menciptakan energi alternatif yang ramah lingkungan
                    dan berdaya guna tinggi. Usaha ini berawal dari keprihatinan pemiliknya terhadap limbah tempurung kelapa
                    yang belum dimanfaatkan secara optimal. Dengan inovasi dan tekad yang kuat, Bara Dara berhasil
                    mengubah limbah tersebut menjadi produk arang briket berkualitas tinggi yang efisien, bersih,
                    dan mendukung keberlanjutan lingkungan.
                </p>
                <p>
                    Seiring berjalannya waktu, Bara Dara terus berkembang dan dipercaya oleh banyak pelanggan baik lokal
                    maupun luar daerah. Kini, Bara Dara tidak hanya dikenal sebagai produsen briket, tetapi juga sebagai
                    simbol inovasi dalam energi hijau dan pemberdayaan masyarakat sekitar.
                     <p><strong>Julistio Fadjar Anugrah Sao</strong><br>Pemilik Bisnis</p>
                </p>
            </div>

            <div class="owner-photo">
                <div>
                    <img src="arang.jpeg" alt="Foto Pemilik Bisnis">
                </div>
            </div>
        </div>

        <div class="cta">
   
            </a>
        </div>

        <div class="footer">
            <p>&copy; ARANG BRIKET BARA DARA.</p>
        </div>
    </div>

    <button class="help-btn" id="helpBtn">💬</button>
    <div class="help-popup" id="helpPopup">
        <div class="help-popup-header">
            <span>Pusat Bantuan</span>
            <button class="close-popup" id="closePopup">&times;</button>
        </div>
        <div class="help-popup-content">
            <p>Halo! Ada yang bisa kami bantu?</p>
            <a href="https://wa.me/6281234567890" target="_blank">Chat via WhatsApp</a>
            <a href="mailto:info@baradara.com">Kirim Email</a>
        </div>
    </div>

    <script>
        const helpBtn = document.getElementById("helpBtn");
        const helpPopup = document.getElementById("helpPopup");
        const closePopup = document.getElementById("closePopup");

        helpBtn.onclick = () => {
            helpPopup.style.display = helpPopup.style.display === "block" ? "none" : "block";
        };
        closePopup.onclick = () => helpPopup.style.display = "none";
        window.onclick = (e) => {
            if (!helpPopup.contains(e.target) && e.target !== helpBtn) helpPopup.style.display = "none";
        };

        const slides = document.querySelectorAll('.gallery-images img');
        let index = 0;
        function showNextImage() {
            slides[index].classList.remove('active');
            index = (index + 1) % slides.length;
            slides[index].classList.add('active');
        }
        setInterval(showNextImage, 10000);

        // Dropdown profil
        const profileBtn = document.getElementById("profileBtn");
        const userDropdown = document.getElementById("userDropdown");
        if (profileBtn && userDropdown) {
            profileBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                userDropdown.style.display = (userDropdown.style.display === "block") ? "none" : "block";
            });
            document.addEventListener("click", (e) => {
                if (!userDropdown.contains(e.target) && e.target !== profileBtn) {
                    userDropdown.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>
