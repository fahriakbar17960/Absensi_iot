<?php
include "koneksi.php";

if(isset($_GET['status'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    mysqli_query($conn, "UPDATE sys_config SET status='$status' WHERE id=1");
    echo "OK";
}
?>