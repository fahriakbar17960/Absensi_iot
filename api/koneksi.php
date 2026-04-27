<?php
$host = "bcgqnmv1lwnzerqzgdft-mysql.services.clever-cloud.com";
$user = "umqkft2nhxc4nnyo";
$pass = "z8KBUIqMUD7sxNEtZyfJ";
$db   = "bcgqnmv1lwnzerqzgdft";

// Gunakan @ untuk meredam error sementara jika koneksi penuh
$koneksi = @mysqli_connect($host, $user, $pass, $db);
$conn = $koneksi; 

if (!$koneksi) {
    // Jika penuh, tampilkan pesan ramah daripada error fatal
    die("Server lagi ramai, coba refresh 5 detik lagi ya! (Error: " . mysqli_connect_error() . ")");
}

// Tambahkan ini supaya koneksi otomatis ditutup saat script selesai running
register_shutdown_function(function() use ($koneksi) {
    mysqli_close($koneksi);
});
?>
