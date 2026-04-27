<?php
include "koneksi.php";

date_default_timezone_set("Asia/Jakarta");

// 1. Ambil ID dari alat
$id = isset($_GET['id']) ? $_GET['id'] : '';

if ($id != '') {
    $tanggal = date("Y-m-d");
    $waktu = date("H:i:s");

    // Tentukan keterangan waktu
    if($waktu <= "06:45:00"){
        $keterangan = "Tepat Waktu";
    } else {
        $keterangan = "Terlambat";
    }

    // 2. Cari data siswa berdasarkan finger_id
    $data = mysqli_query($koneksi, "SELECT * FROM users WHERE finger_id='$id'");
    $siswa = mysqli_fetch_array($data);

    if ($siswa) {
        $nis = $siswa['nis'];
        $nama = $siswa['nama']; // Nama asli dari database (bisa 3-4 kata)
        $kelas = $siswa['kelas'];

        // ==========================================
        // LOGIKA POTONG NAMA MAKSIMAL 2 KATA
        // ==========================================
        $pecah_nama = explode(" ", $nama); // Pecah nama berdasarkan spasi
        
        if (count($pecah_nama) >= 2) {
            // Jika nama lebih dari atau sama dengan 2 kata, ambil kata ke-1 dan ke-2
            $nama_tampil = $pecah_nama[0] . " " . $pecah_nama[1];
        } else {
            // Jika nama cuma 1 kata, tampilkan apa adanya
            $nama_tampil = $nama; 
        }
        // ==========================================

        // ==========================================
        // CEK ANTI ABSEN DOBEL
        // ==========================================
        $cek_absen = mysqli_query($koneksi, "SELECT * FROM absensi WHERE id_finger='$id' AND tanggal='$tanggal'");
        
        if (mysqli_num_rows($cek_absen) > 0) {
            // Jika hari ini siswa sudah absen, jangan simpan data lagi
            echo "SUDAH ABSEN";
        } else {
            // 3. Masukkan data ke tabel absensi (Tetap simpan NAMA ASLI LENGKAP ke database)
            mysqli_query($koneksi, "INSERT INTO absensi 
                (nis, id_finger, nama, kelas, tanggal, waktu, status, keterangan) 
                VALUES 
                ('$nis', '$id', '$nama', '$kelas', '$tanggal', '$waktu', 'Hadir', '$keterangan')");

            // 4. Kirim NAMA YANG SUDAH DIPOTONG ke alat LCD
            echo strtoupper($nama_tampil); 
        }
        // ==========================================

    } else {
        echo "ID TDK DIKENAL";
    }
} else {
    echo "ID KOSONG";
}
?>