<?php
// Data dari Clever Cloud
$host = "bcgqnmv1lwnzerqzgdft-mysql.services.clever-cloud.com";
$user = "umqkft2nhxc4nnyo";
$pass = "z8KBUIqMUD7sxNEtZyfJ";
$db   = "bcgqnmv1lwnzerqzgdft";

// Membuat koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Menyamakan variabel agar tidak error di file lain
$conn = $koneksi; 

// Cek koneksi
if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Set timezone agar waktu sesuai
date_default_timezone_set('Asia/Jakarta');
?>
