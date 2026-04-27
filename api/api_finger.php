<?php
include 'koneksi.php';
ob_clean(); // Bersihkan output tersembunyi
$sql = mysqli_query($conn, "SELECT * FROM sys_config WHERE id=1");
$data = mysqli_fetch_assoc($sql);

if ($data && $data['status'] == 'enroll') {
    echo "ENROLL:" . trim($data['enroll_id']);
} else {
    echo "SCAN";
}