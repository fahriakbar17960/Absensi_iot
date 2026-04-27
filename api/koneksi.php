<?php
$host = "bcgqnmv1lwnzerqzgdft-mysql.services.clever-cloud.com";
$user = "umqkft2nhxc4nnyo";
$pass = "z8KBUIqMUD7sxNEtZyfJ";
$db   = "bcgqnmv1lwnzerqzgdft";

$koneksi = mysqli_connect($host, $user, $pass, $db);
$conn = $koneksi; 

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
