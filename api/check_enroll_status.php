<?php
include "koneksi.php";

$query = mysqli_query($conn, "SELECT status FROM sys_config WHERE id=1");
$data = mysqli_fetch_assoc($query);

if($data['status'] == 'success') {
    // Kembalikan status ke 'scan' agar alat tidak mendaftar terus-terusan
    mysqli_query($conn, "UPDATE sys_config SET status='scan' WHERE id=1");
    echo "Pendaftaran Sukses!";
} else if($data['status'] == 'waiting') {
    echo "Menunggu jari ditempelkan...";
} else if($data['status'] == 'enroll') {
    echo "Mode Daftar Aktif: Tempelkan Jari ke Sensor!";
} else {
    echo "Menghubungkan ke alat...";
}
?>