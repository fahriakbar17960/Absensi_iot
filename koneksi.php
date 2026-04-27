<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "absensi_iot";

$koneksi = mysqli_connect($host, $user, $pass, $db);

// Tambahkan baris ini di bawahnya supaya variabel $conn juga terdaftar
$conn = $koneksi; 

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>