<?php
$koneksi = new mysqli("localhost", "root", "", "web_bara_dara");
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
$koneksi->set_charset("utf8mb4");

function safe_filename_from_path($path, $fallback = "bukti_transfer") {
    $name = basename((string)$path);
    $name = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $name);
    if (!$name || $name === '.' || $name === '..') {
        $name = $fallback;
    }
    return $name;
}

function is_valid_date_ymd($d) {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$d);
}

/* >>> PERUBAHAN: validasi bulan (YYYY-MM) */
function is_valid_ym($ym) {
    return (bool)preg_match('/^\d{4}-\d{2}$/', (string)$ym);
}

function rupiah($n) {
    return "Rp " . number_format((int)$n, 0, ',', '.');
}

/* =========================
   FALLBACK: kalau kode_pesanan NULL, tampilkan BRD000001 dst
========================= */
function kode_pesanan_fallback($id_int) {
    $id_int = (int)$id_int;
    return "BRD" . str_pad((string)$id_int, 2, "0", STR_PAD_LEFT);
}

/* =========================
   AMBIL KODE dari DB jika ada (kode_pesanan), kalau kosong fallback
========================= */
function kode_pesanan_db_or_fallback($row) {
    if (is_array($row) && isset($row['kode_pesanan'])) {
        $k = trim((string)$row['kode_pesanan']);
        if ($k !== '') return $k;
    }
    $idNum = isset($row['id_pesanan']) ? (int)$row['id_pesanan'] : 0;
    return kode_pesanan_fallback($idNum);
}

/* =========================
   tampilkan bahan saja
   - jika data lama "Briket 1 Kg - Kulit Kemiri" => "Kulit Kemiri"
========================= */
function tampil_bahan_saja($bahan) {
    $bahan = trim((string)$bahan);
    if (strpos($bahan, '-') !== false) {
        $parts = explode('-', $bahan);
        $last = trim(end($parts));
        if ($last !== '') return $last;
    }
    return $bahan;
}

/* =========================
   format jumlah jadi "X Kg"
========================= */
function tampil_kg($n) {
    return (int)$n . " Kg";
}

/* =========================
   Handler: Lihat Bukti
   - klik 👁 => pesanan_admin.php?lihat_bukti=1&id_pesanan=ID
========================= */
if (isset($_GET['lihat_bukti']) && $_GET['lihat_bukti'] === '1' && isset($_GET['id_pesanan'])) {
    $id = (int)$_GET['id_pesanan'];

    if ($id > 0) {
        $stmt = $koneksi->prepare("SELECT bukti_transfer FROM pesanan WHERE id_pesanan = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();

            if ($row && !empty($row['bukti_transfer'])) {
                $bukti = trim((string)$row['bukti_transfer']);
                $bukti = str_replace(["\r", "\n"], "", $bukti);

                // Block URL berbahaya
                if (preg_match('#^\s*https?://#i', $bukti) || preg_match('#^\s*javascript:#i', $bukti)) {
                    http_response_code(400);
                    die("Path bukti tidak valid.");
                }

                header("Location: " . $bukti);
                exit;
            }
        }
    }

    http_response_code(404);
    die("Bukti transfer tidak ditemukan.");
}

/* =========================
   Verifikasi Admin (DIPERBAIKI)
   - ✅ approve => POTONG STOK bahan_baku + status_pesanan = 'Lunas' + stok_dipotong=1
   - ❌ reject  => status_pesanan = 'Menolak' (stok tidak dipotong)
   - hanya berlaku jika status sekarang 'Menunggu Konfirmasi' dan bukti_transfer ada
========================= */
if (isset($_GET['verif']) && isset($_GET['id_pesanan'])) {
    $aksi = strtolower(trim((string)$_GET['verif']));
    $idv  = (int)$_GET['id_pesanan'];

    if ($idv > 0 && ($aksi === 'approve' || $aksi === 'reject')) {

        $koneksi->begin_transaction();

        try {
            // 1) Lock pesanan
            $stmtP = $koneksi->prepare("
                SELECT id_pesanan, nama_pembeli, bahan_baku, jumlah_kg, status_pesanan, bukti_transfer, stok_dipotong
                FROM pesanan
                WHERE id_pesanan = ?
                  AND bukti_transfer <> ''
                  AND LOWER(TRIM(status_pesanan)) = 'menunggu konfirmasi'
                LIMIT 1
                FOR UPDATE
            ");
            $stmtP->bind_param("i", $idv);
            $stmtP->execute();
            $resP = $stmtP->get_result();
            $pes = $resP ? $resP->fetch_assoc() : null;
            $stmtP->close();

            if (!$pes) {
                $koneksi->rollback();
            } else {

                $username  = trim((string)($pes['nama_pembeli'] ?? '')); // ini harus username (karena sudah kita kunci dari session)
                $bahanNama = (string)$pes['bahan_baku'];
                $qty       = (int)$pes['jumlah_kg'];
                $dipotong  = (int)($pes['stok_dipotong'] ?? 0);

                if ($aksi === 'approve') {

                    // idempotent: kalau sudah dipotong, jangan hitung lagi
                    if ($dipotong === 1) {
                        $stmtU = $koneksi->prepare("
                            UPDATE pesanan
                            SET status_pesanan = 'Lunas'
                            WHERE id_pesanan = ?
                            LIMIT 1
                        ");
                        $stmtU->bind_param("i", $idv);
                        $stmtU->execute();
                        $stmtU->close();
                        $koneksi->commit();

                    } else {

                        // 2) Lock bahan_baku (exact match dulu)
                        $bb = null;

                        $stmtB1 = $koneksi->prepare("
                            SELECT id, stok, jumlah_terjual
                            FROM bahan_baku
                            WHERE nama_bahan = ?
                            LIMIT 1
                            FOR UPDATE
                        ");
                        $stmtB1->bind_param("s", $bahanNama);
                        $stmtB1->execute();
                        $resB1 = $stmtB1->get_result();
                        if ($resB1 && $resB1->num_rows === 1) {
                            $bb = $resB1->fetch_assoc();
                        }
                        $stmtB1->close();

                        if (!$bb) {
                            $koneksi->rollback();
                        } else {
                            $bb_id = (int)$bb['id'];
                            $stok  = (int)($bb['stok'] ?? 0);

                            if ($qty <= 0 || $stok < $qty) {
                                $koneksi->rollback();
                            } else {

                                // 3) Potong stok + naikkan terjual
                                $stmtS = $koneksi->prepare("
                                    UPDATE bahan_baku
                                    SET
                                        stok = IFNULL(stok, 0) - ?,
                                        jumlah_terjual = IFNULL(jumlah_terjual, 0) + ?
                                    WHERE id = ?
                                      AND IFNULL(stok, 0) >= ?
                                    LIMIT 1
                                ");
                                $stmtS->bind_param("iiii", $qty, $qty, $bb_id, $qty);
                                $stmtS->execute();

                                if ($stmtS->affected_rows !== 1) {
                                    $stmtS->close();
                                    $koneksi->rollback();
                                } else {
                                    $stmtS->close();

                                    // 4) Set Lunas + tandai stok dipotong
                                    $stmtV = $koneksi->prepare("
                                        UPDATE pesanan
                                        SET status_pesanan = 'Lunas',
                                            stok_dipotong = 1
                                        WHERE id_pesanan = ?
                                          AND bukti_transfer <> ''
                                          AND LOWER(TRIM(status_pesanan)) = 'menunggu konfirmasi'
                                          AND stok_dipotong = 0
                                        LIMIT 1
                                    ");
                                    $stmtV->bind_param("i", $idv);
                                    $stmtV->execute();

                                    if ($stmtV->affected_rows !== 1) {
                                        $stmtV->close();
                                        $koneksi->rollback();
                                    } else {
                                        $stmtV->close();

                                        // 5) Update total_kg + vip_level user (berdasarkan total setelah ditambah)
                                        if ($username === '' || $qty <= 0) {
                                            $koneksi->rollback();
                                        } else {
                                            $qtyF = (float)$qty;

                                            $stmtLvl = $koneksi->prepare("
                                                UPDATE user
                                                SET
                                                    total_kg = total_kg + ?,
                                                    vip_level = CASE
                                                        WHEN total_kg + ? >= 12 THEN 1
                                                        WHEN total_kg + ? >= 6  THEN 2
                                                        WHEN total_kg + ? >= 3  THEN 3
                                                        ELSE 0
                                                    END
                                                WHERE username = ?
                                                LIMIT 1
                                            ");
                                            $stmtLvl->bind_param("dddds", $qtyF, $qtyF, $qtyF, $qtyF, $username);
                                            $stmtLvl->execute();

                                            if ($stmtLvl->affected_rows !== 1) {
                                                $stmtLvl->close();
                                                $koneksi->rollback();
                                            } else {
                                                $stmtLvl->close();
                                                $koneksi->commit();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                } else {
                    // reject: set Menolak
                    $stmtR = $koneksi->prepare("
                        UPDATE pesanan
                        SET status_pesanan = 'Menolak',
                            stok_dipotong = 0
                        WHERE id_pesanan = ?
                          AND bukti_transfer <> ''
                          AND LOWER(TRIM(status_pesanan)) = 'menunggu konfirmasi'
                        LIMIT 1
                    ");
                    $stmtR->bind_param("i", $idv);
                    $stmtR->execute();
                    $stmtR->close();
                    $koneksi->commit();
                }
            }

        } catch (Throwable $e) {
            $koneksi->rollback();
        }
    }

    /* >>> PERUBAHAN: redirect back tetap bawa filter (tanggal / bulan) */
    $back = "pesanan_admin.php";
    $qs = [];

    $fb = isset($_GET['filter_by']) ? strtolower(trim((string)$_GET['filter_by'])) : '';
    if ($fb !== 'tanggal' && $fb !== 'bulan') $fb = '';

    if ($fb === 'tanggal') {
        if (isset($_GET['tanggal']) && is_valid_date_ymd($_GET['tanggal'])) {
            $qs['filter_by'] = 'tanggal';
            $qs['tanggal'] = $_GET['tanggal'];
        }
    } elseif ($fb === 'bulan') {
        if (isset($_GET['ym']) && is_valid_ym($_GET['ym'])) {
            $qs['filter_by'] = 'bulan';
            $qs['ym'] = $_GET['ym'];
        }
    }

    if (!empty($qs)) {
        $back .= "?" . http_build_query($qs);
    }

    header("Location: " . $back);
    exit;
}


/* =========================
   PDF generator (tanpa library)
========================= */
function pdf_escape($text) {
    $text = (string)$text;
    $text = str_replace("\\", "\\\\", $text);
    $text = str_replace("(", "\\(", $text);
    $text = str_replace(")", "\\)", $text);
    $text = preg_replace("/\r\n|\r|\n/", " ", $text);
    return $text;
}

function build_simple_pdf($title, $lines) {
    $page_w = 595;
    $page_h = 842;

    $font_obj_id = 3;
    $catalog_id = 1;
    $pages_id = 2;

    $margin_x = 40;
    $start_y = 800;
    $line_h = 14;
    $max_lines_per_page = (int)(($start_y - 60) / $line_h);

    $pages_lines = [];
    $current = [];
    foreach ($lines as $ln) {
        $current[] = $ln;
        if (count($current) >= $max_lines_per_page) {
            $pages_lines[] = $current;
            $current = [];
        }
    }
    if (count($current) > 0) $pages_lines[] = $current;
    if (count($pages_lines) === 0) $pages_lines[] = ["(Tidak ada data)"];

    $num_pages = count($pages_lines);

    $offsets = [];
    $pdf = "%PDF-1.4\n";
    $objCount = 3 + $num_pages * 2;

    $addObj = function($id, $content) use (&$pdf, &$offsets) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $content . "\nendobj\n";
    };

    $addObj($font_obj_id, "<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>");

    $kids = [];
    for ($k = 1; $k <= $num_pages; $k++) {
        $page_obj_id = 4 + ($k - 1) * 2;
        $content_obj_id = 5 + ($k - 1) * 2;
        $kids[] = $page_obj_id . " 0 R";

        $content = "BT\n/F1 11 Tf\n";
        $y = $start_y;
        $content .= $margin_x . " " . $y . " Td\n";
        $content .= "(" . pdf_escape($title) . ") Tj\n";
        $content .= "0 -" . $line_h . " Td\n";
        $content .= "(" . pdf_escape(str_repeat("-", 90)) . ") Tj\n";

        foreach ($pages_lines[$k - 1] as $ln) {
            $content .= "\n0 -" . $line_h . " Td\n";
            $content .= "(" . pdf_escape($ln) . ") Tj";
        }
        $content .= "\nET";

        $stream = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        $addObj($content_obj_id, $stream);

        $page = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $page_w $page_h] "
              . "/Resources << /Font << /F1 3 0 R >> >> "
              . "/Contents " . $content_obj_id . " 0 R >>";
        $addObj($page_obj_id, $page);
    }

    $pages_obj = "<< /Type /Pages /Kids [" . implode(" ", $kids) . "] /Count " . $num_pages . " >>";
    $addObj($pages_id, $pages_obj);

    $catalog_obj = "<< /Type /Catalog /Pages 2 0 R >>";
    $addObj($catalog_id, $catalog_obj);

    $xref_pos = strlen($pdf);
    $pdf .= "xref\n";
    $pdf .= "0 " . ($objCount + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    for ($i = 1; $i <= $objCount; $i++) {
        $off = isset($offsets[$i]) ? $offsets[$i] : 0;
        $pdf .= str_pad((string)$off, 10, "0", STR_PAD_LEFT) . " 00000 n \n";
    }

    $pdf .= "trailer\n";
    $pdf .= "<< /Size " . ($objCount + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n";
    $pdf .= $xref_pos . "\n";
    $pdf .= "%%EOF";

    return $pdf;
}

/* =========================
   Filter Tanggal / Bulan (dropdown)
========================= */
$filter_by = isset($_GET['filter_by']) ? strtolower(trim((string)$_GET['filter_by'])) : '';
if ($filter_by !== 'tanggal' && $filter_by !== 'bulan') {
    $filter_by = '';
}

$tanggal = isset($_GET['tanggal']) ? trim($_GET['tanggal']) : "";
if ($tanggal !== "" && !is_valid_date_ymd($tanggal)) {
    $tanggal = "";
}

$ym = isset($_GET['ym']) ? trim($_GET['ym']) : "";
if ($ym !== "" && !is_valid_ym($ym)) {
    $ym = "";
}

if ($filter_by === '') {
    // auto pilih berdasarkan parameter yang ada
    if ($ym !== "") $filter_by = 'bulan';
    else $filter_by = 'tanggal';
}

$where = "";
$types = "";
$params = [];

/* >>> PERUBAHAN: apply filter sesuai dropdown */
if ($filter_by === 'tanggal' && $tanggal !== "") {
    $where = "WHERE tanggal = ?";
    $types = "s";
    $params[] = $tanggal;
} elseif ($filter_by === 'bulan' && $ym !== "") {
    $start_month = $ym . "-01";
    $end_month = date('Y-m-d', strtotime($start_month . ' +1 month')); // eksklusif
    $where = "WHERE tanggal >= ? AND tanggal < ?";
    $types = "ss";
    $params[] = $start_month;
    $params[] = $end_month;
}

/* >>> PERUBAHAN: querystring filter untuk dipakai di link-link */
$filterQS = "";
if ($filter_by === 'tanggal' && $tanggal !== "") {
    $filterQS = "filter_by=tanggal&tanggal=" . urlencode($tanggal);
} elseif ($filter_by === 'bulan' && $ym !== "") {
    $filterQS = "filter_by=bulan&ym=" . urlencode($ym);
}

/* =========================
   Export PDF sesuai filter
========================= */
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $sql = "SELECT * FROM pesanan $where ORDER BY id_pesanan DESC";
    $stmt = $koneksi->prepare($sql);
    if (!$stmt) die("Prepare gagal: " . $koneksi->error);

    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $lines = [];
    $judul = "Daftar Pesanan";

    /* >>> PERUBAHAN: judul PDF sesuai mode filter */
    if ($filter_by === 'tanggal' && $tanggal !== "") {
        $judul .= " - Tanggal: $tanggal";
    } elseif ($filter_by === 'bulan' && $ym !== "") {
        $judul .= " - Bulan: $ym";
    } else {
        $judul .= " - Semua Tanggal";
    }

    $lines[] = "Kolom: Kode | Nama | Bahan | Jumlah | Total | Status";
    $lines[] = "";

    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $kode  = kode_pesanan_db_or_fallback($row);
            $nama  = mb_strimwidth($row['nama_pembeli'], 0, 24, "...");
            $bahan = mb_strimwidth(tampil_bahan_saja($row['bahan_baku']), 0, 28, "...");
            $kg    = tampil_kg($row['jumlah_kg']);
            $total = rupiah($row['total_harga']);
            $status = $row['status_pesanan'];

            $lines[] = "{$kode} | {$nama} | {$bahan} | {$kg} | {$total} | {$status}";
        }
    } else {
        $lines[] = "Tidak ada data pesanan untuk filter tersebut.";
    }

    $pdf = build_simple_pdf($judul, $lines);

    $fname = "pesanan";
    /* >>> PERUBAHAN: nama file sesuai mode filter */
    if ($filter_by === 'tanggal' && $tanggal !== "") $fname .= "_" . $tanggal;
    if ($filter_by === 'bulan' && $ym !== "") $fname .= "_" . $ym;
    $fname .= ".pdf";

    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=\"$fname\"");
    header("Content-Length: " . strlen($pdf));
    echo $pdf;
    exit;
}

/* =========================
   Query tampilan tabel utama (Semua Pesanan)
========================= */
$sqlView = "SELECT * FROM pesanan $where ORDER BY id_pesanan DESC";
$stmtView = $koneksi->prepare($sqlView);
if (!$stmtView) die("Prepare gagal: " . $koneksi->error);

if ($types !== "") {
    $stmtView->bind_param($types, ...$params);
}
$stmtView->execute();
$result = $stmtView->get_result();

/* =========================
   Query tabel verifikasi (Menunggu Konfirmasi + ada bukti)
========================= */
$whereVerif = "";
$typesVerif = "";
$paramsVerif = [];

/* >>> PERUBAHAN: filter verifikasi ikut dropdown (tanggal/bulan) */
if ($filter_by === 'tanggal' && $tanggal !== "") {
    $whereVerif = "WHERE tanggal = ? AND bukti_transfer <> '' AND LOWER(TRIM(status_pesanan)) = 'menunggu konfirmasi'";
    $typesVerif = "s";
    $paramsVerif[] = $tanggal;
} elseif ($filter_by === 'bulan' && $ym !== "") {
    $start_month_v = $ym . "-01";
    $end_month_v = date('Y-m-d', strtotime($start_month_v . ' +1 month')); // eksklusif
    $whereVerif = "WHERE tanggal >= ? AND tanggal < ? AND bukti_transfer <> '' AND LOWER(TRIM(status_pesanan)) = 'menunggu konfirmasi'";
    $typesVerif = "ss";
    $paramsVerif[] = $start_month_v;
    $paramsVerif[] = $end_month_v;
} else {
    $whereVerif = "WHERE bukti_transfer <> '' AND LOWER(TRIM(status_pesanan)) = 'menunggu konfirmasi'";
}

$sqlVerif = "SELECT * FROM pesanan $whereVerif ORDER BY id_pesanan DESC";
$stmtVerif = $koneksi->prepare($sqlVerif);
if (!$stmtVerif) die("Prepare gagal: " . $koneksi->error);

if ($typesVerif !== "") {
    $stmtVerif->bind_param($typesVerif, ...$paramsVerif);
}
$stmtVerif->execute();
$resultVerif = $stmtVerif->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Daftar Pesanan</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f8;
    padding: 20px;
}
h2 {
    text-align: center;
    color: #c62828;
    margin-bottom: 14px;
}

/* Arrow back kiri atas */
.back-btn {
    position: fixed;
    top: 14px;
    left: 14px;
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
    color: #c62828;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.2s ease;
}
.back-btn:hover { background: #dcdcdc; color: #333; transform: translateY(-1px); }
.back-btn:active { transform: translateY(0); }
.back-btn span { font-size: 22px; line-height: 1; margin-left: -1px; }

.page-title { padding-top: 6px; }

/* Bar kontrol kanan atas */
.control-bar {
    display: flex;
    justify-content: flex-end;
    margin: 0 0 12px 0;
}
.filter-wrap {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 10px 12px;
    display: inline-flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}
.filter-wrap label {
    font-size: 13px;
    color: #333;
    margin: 0;
}
.filter-wrap input[type="date"] {
    padding: 7px 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

/* >>> PERUBAHAN: style dropdown & input month */
.filter-wrap select {
    padding: 7px 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fff;
}
.filter-wrap input[type="month"]{
    padding: 7px 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

.btn {
    border: none;
    border-radius: 8px;
    padding: 8px 12px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
}
.btn-reset {
    background: #e0e0e0;
    color: #333;
}
.btn-reset:hover { background: #d5d5d5; }

.btn-pdf {
    background: #455a64;
    color: #fff;
}
.btn-pdf:hover { background: #263238; }

/* Tabel */
table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-bottom: 18px;
}
th {
    background: #c62828;
    color: #fff;
    padding: 12px;
    text-transform: uppercase;
}
td {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

/* Status */
.status {
    padding: 5px 10px;
    border-radius: 6px;
    color: #fff;
    font-weight: bold;
}
.status.lunas { background: #2e7d32; }
.status.belum { background: #f57c00; }
.status.menunggu { background: #1565c0; }
.status.menolak { background: #d32f2f; }

/* Ikon bukti */
.bukti-actions {
    display: inline-flex;
    gap: 8px;
    align-items: center;
    justify-content: center;
}
.icon-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #fff;
    font-size: 16px;
    line-height: 1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: 0.2s;
}
.icon-btn.view { background: #1565c0; }
.icon-btn.view:hover { background: #0d47a1; transform: translateY(-1px); }
.icon-btn.dl { background: #455a64; }
.icon-btn.dl:hover { background: #263238; transform: translateY(-1px); }

/* Tombol verifikasi */
.verify-actions{
    display: inline-flex;
    gap: 8px;
    align-items: center;
    justify-content: center;
}
.vbtn{
    width: 36px;
    height: 36px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #fff;
    font-size: 16px;
    line-height: 1;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: 0.2s;
}
.vbtn.approve{ background: #2e7d32; }
.vbtn.approve:hover{ background: #1b5e20; transform: translateY(-1px); }
.vbtn.reject{ background: #df2828ff; }
.vbtn.reject:hover{ background: #b71c1c; transform: translateY(-1px); }

.section-title{
    margin: 18px 0 10px 0;
    font-size: 16px;
    font-weight: 800;
    color: #333;
    text-align: left;
}
</style>
</head>
<body>

<a href="admin.html" class="back-btn" aria-label="Kembali ke halaman admin" title="Kembali">
    <span>&larr;</span>
</a>

<h2 class="page-title">📦 Daftar Pesanan</h2>

<?php
    $pdfLink = "pesanan_admin.php?export=pdf";
    /* >>> PERUBAHAN: PDF link ikut filter aktif */
    if ($filterQS !== "") $pdfLink .= "&" . $filterQS;
?>

<!-- Kontrol di kanan atas -->
<div class="control-bar">
    <form method="get" class="filter-wrap" id="filterForm">
        <!-- >>> PERUBAHAN: dropdown pilih filter (tanggal/bulan) -->
        <label for="filter_by"><strong>Filter:</strong></label>
        <select id="filter_by" name="filter_by">
            <option value="tanggal" <?= ($filter_by === 'tanggal') ? 'selected' : '' ?>>Tanggal</option>
            <option value="bulan" <?= ($filter_by === 'bulan') ? 'selected' : '' ?>>Bulan</option>
        </select>

        <!-- Mode TANGGAL -->
        <span id="wrapTanggal" style="display:inline-flex; gap:8px; align-items:center;">
            <label for="tanggal"><strong>Tanggal:</strong></label>
            <input type="date" id="tanggal" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>">
        </span>

        <!-- Mode BULAN -->
        <span id="wrapBulan" style="display:inline-flex; gap:8px; align-items:center;">
            <label for="ym"><strong>Bulan:</strong></label>
            <input type="month" id="ym" name="ym" value="<?= htmlspecialchars($ym) ?>">
        </span>

        <a class="btn btn-reset" href="pesanan_admin.php">Reset</a>
        <a class="btn btn-pdf" href="<?= htmlspecialchars($pdfLink) ?>" title="Download PDF sesuai filter">Download PDF</a>
    </form>
</div>

<!-- TABEL BARU: Verifikasi Pembayaran -->
<div class="section-title">Verifikasi Pembayaran (Menunggu Konfirmasi)</div>
<table>
<tr>
<th>ID Pesanan</th>
<th>Nama</th>
<th>Total</th>
<th>Tanggal</th>
<th>Bukti</th>
<th>Verifikasi</th>
</tr>

<?php if ($resultVerif && $resultVerif->num_rows > 0): ?>
    <?php while($rv = $resultVerif->fetch_assoc()): ?>
        <?php
            $idNumV = (int)$rv['id_pesanan'];
            $kodeV  = kode_pesanan_db_or_fallback($rv);

            $buktiV = $rv['bukti_transfer'];
            $downloadNameV = "bukti_pesanan_" . $idNumV . "_" . safe_filename_from_path($buktiV, "bukti_transfer");

            $lihatLinkV = "pesanan_admin.php?lihat_bukti=1&id_pesanan=" . $idNumV;
            if ($filterQS !== "") $lihatLinkV .= "&" . $filterQS;

            $approveLink = "pesanan_admin.php?verif=approve&id_pesanan=" . $idNumV;
            $rejectLink  = "pesanan_admin.php?verif=reject&id_pesanan=" . $idNumV;
            if ($filterQS !== "") {
                $approveLink .= "&" . $filterQS;
                $rejectLink  .= "&" . $filterQS;
            }
        ?>
        <tr>
            <td title="ID asli: <?= $idNumV ?>"><?= htmlspecialchars($kodeV) ?></td>
            <td><?= htmlspecialchars($rv['nama_pembeli']) ?></td>
            <td><?= rupiah($rv['total_harga']) ?></td>
            <td><?= htmlspecialchars($rv['tanggal']) ?></td>
            <td>
                <div class="bukti-actions">
                    <a href="<?= htmlspecialchars($lihatLinkV) ?>" target="_blank" class="icon-btn view" title="Lihat Bukti" aria-label="Lihat Bukti">👁</a>
                    <a href="<?= htmlspecialchars($buktiV) ?>" class="icon-btn dl" download="<?= htmlspecialchars($downloadNameV) ?>" title="Download Bukti" aria-label="Download Bukti">⬇</a>
                </div>
            </td>
            <td>
                <div class="verify-actions">
                    <a class="vbtn approve"
                       href="<?= htmlspecialchars($approveLink) ?>"
                       title="ACC (Alamat transfer benar) - Set Lunas"
                       aria-label="Set Lunas"
                       onclick="return confirm('ACC pembayaran ini? Status akan menjadi Lunas.');">✅</a>

                    <a class="vbtn reject"
                       href="<?= htmlspecialchars($rejectLink) ?>"
                       title="Tolak (Bukti/Alamat salah) - Set Menolak"
                       aria-label="Tolak"
                       onclick="return confirm('Tolak pembayaran ini? Status akan menjadi Menolak.');">❌</a>
                </div>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="6" style="color:#777;font-style:italic;">Tidak ada pembayaran yang menunggu konfirmasi.</td>
    </tr>
<?php endif; ?>
</table>

<!-- TABEL UTAMA: Semua Pesanan -->
<div class="section-title">Semua Pesanan</div>
<table>
<tr>
<th>ID Pesanan</th>
<th>Nama</th>
<th>Bahan</th>
<th>Jumlah</th>
<th>Total Harga</th>
<th>Tanggal</th>
<th>Status</th>
<th>Bukti</th>
</tr>

<?php if ($result && $result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
    <?php
        $st = strtolower(trim((string)$row['status_pesanan']));
        if ($st === 'lunas') $stClass = 'lunas';
        elseif ($st === 'menunggu konfirmasi') $stClass = 'menunggu';
        elseif ($st === 'menolak') $stClass = 'menolak';
        else $stClass = 'belum';

        $idNum = (int)$row['id_pesanan'];
        $kode  = kode_pesanan_db_or_fallback($row);
    ?>
    <tr>
        <td title="ID asli: <?= $idNum ?>"><?= htmlspecialchars($kode) ?></td>
        <td><?= htmlspecialchars($row['nama_pembeli']) ?></td>
        <td><?= htmlspecialchars(tampil_bahan_saja($row['bahan_baku'])) ?></td>
        <td><?= htmlspecialchars(tampil_kg($row['jumlah_kg'])) ?></td>
        <td><?= rupiah($row['total_harga']) ?></td>
        <td><?= htmlspecialchars($row['tanggal']) ?></td>
        <td>
            <span class="status <?= $stClass ?>">
                <?= htmlspecialchars($row['status_pesanan']) ?>
            </span>
        </td>
        <td>
            <?php if(!empty($row['bukti_transfer'])): ?>
                <?php
                    $bukti = $row['bukti_transfer'];
                    $downloadName = "bukti_pesanan_" . $idNum . "_" . safe_filename_from_path($bukti, "bukti_transfer");

                    $lihatLink = "pesanan_admin.php?lihat_bukti=1&id_pesanan=" . $idNum;
                    if ($filterQS !== "") $lihatLink .= "&" . $filterQS;
                ?>
                <div class="bukti-actions">
                    <a href="<?= htmlspecialchars($lihatLink) ?>"
                       target="_blank"
                       class="icon-btn view"
                       title="Lihat Bukti"
                       aria-label="Lihat Bukti">👁</a>

                    <a href="<?= htmlspecialchars($bukti) ?>"
                       class="icon-btn dl"
                       download="<?= htmlspecialchars($downloadName) ?>"
                       title="Download Bukti"
                       aria-label="Download Bukti">⬇</a>
                </div>
            <?php else: ?>
                <span style="color:#999;font-style:italic;">Belum ada</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="8" style="color:#777;font-style:italic;">Tidak ada data pesanan untuk filter tersebut.</td>
    </tr>
<?php endif; ?>
</table>

<script>
/* >>> PERUBAHAN: dropdown pilih filter tanggal/bulan + auto submit */
(function () {
    const form = document.getElementById('filterForm');
    const filterBy = document.getElementById('filter_by');
    const wrapTanggal = document.getElementById('wrapTanggal');
    const wrapBulan = document.getElementById('wrapBulan');
    const inputTanggal = document.getElementById('tanggal');
    const inputYm = document.getElementById('ym');

    function syncUI() {
        const v = (filterBy && filterBy.value) ? filterBy.value : 'tanggal';

        if (v === 'bulan') {
            if (wrapTanggal) wrapTanggal.style.display = 'none';
            if (inputTanggal) inputTanggal.disabled = true;

            if (wrapBulan) wrapBulan.style.display = 'inline-flex';
            if (inputYm) inputYm.disabled = false;
        } else {
            if (wrapBulan) wrapBulan.style.display = 'none';
            if (inputYm) inputYm.disabled = true;

            if (wrapTanggal) wrapTanggal.style.display = 'inline-flex';
            if (inputTanggal) inputTanggal.disabled = false;
        }
    }

    if (filterBy) {
        filterBy.addEventListener('change', function () {
            syncUI();
            form.submit();
        });
    }

    if (inputTanggal) {
        inputTanggal.addEventListener('change', function () {
            form.submit();
        });
    }

    if (inputYm) {
        inputYm.addEventListener('change', function () {
            form.submit();
        });
    }

    syncUI();
})();
</script>

</body>
</html>
