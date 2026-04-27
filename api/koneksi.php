<?php
// Data dari Clever Cloud
// Tips: Pastikan tidak ada spasi tambahan pada string koneksi
$host = trim("bcgqnmv1lwnzerqzgdft-mysql.services.clever-cloud.com");
$user = trim("umqkft2nhxc4nnyo");
$pass = trim("z8KBUIqMUD7sxNEtZyfJ");
$db   = trim("bcgqnmv1lwnzerqzgdft");

// Membuat koneksi
try {
    $koneksi = mysqli_connect($host, $user, $pass, $db);
    $conn = $koneksi; // Menyamakan variabel agar kompatibel dengan semua file
} catch (mysqli_sql_exception $e) {
    die("Koneksi ke database gagal: " . $e->getMessage());
}

// Set timezone agar waktu sesuai
date_default_timezone_set('Asia/Jakarta');
?>
