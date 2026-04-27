<?php
include "koneksi.php";

// ambil filter tanggal kalau ada
$where = "";

if(isset($_GET['tanggal']) && $_GET['tanggal'] != ""){
    $tgl = $_GET['tanggal'];
    $where = "WHERE tanggal = '$tgl'";
}

// header excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=rekap_absensi.xls");

?>

<table border="1">
<tr>
<th>Tanggal</th>
<th>NIS</th>
<th>Nama</th>
<th>Kelas</th>
<th>Waktu</th>
<th>Status</th>
<th>Keterangan</th>
</tr>

<?php

$query = mysqli_query($conn,"
SELECT absensi.*, users.nama, users.kelas, users.nis
FROM absensi
JOIN users ON absensi.id_finger = users.finger_id
$where
ORDER BY tanggal DESC, waktu DESC
");

while($data=mysqli_fetch_array($query)){
?>

<tr>
<td><?php echo $data['tanggal']; ?></td>
<td><?php echo $data['nis']; ?></td>
<td><?php echo $data['nama']; ?></td>
<td><?php echo $data['kelas']; ?></td>
<td><?php echo $data['waktu']; ?></td>
<td><?php echo $data['status']; ?></td>
<td><?php echo $data['keterangan']; ?></td>
</tr>

<?php } ?>

</table>