<?php
// 1. Hubungkan ke database
include "koneksi.php";

// Set timezone agar waktu sesuai dengan lokasi kita (WIB)
date_default_timezone_set('Asia/Jakarta');

// 2. Cek apakah ada data 'finger_id' yang dikirim (dari Arduino)
if (isset($_GET['finger_id'])) {
    
    $finger_id = mysqli_real_escape_string($conn, $_GET['finger_id']);
    $tgl_sekarang = date("Y-m-d"); // Format: 2026-04-02
    $jam_sekarang = date("H:i:s"); // Format: 11:30:00
    
    // 3. LOGIKA STATUS (Tentukan batas jam masuk sekolah/kantor)
    $batas_masuk = "07:30:00";
    
    if ($jam_sekarang <= $batas_masuk) {
        $keterangan_absensi = "Tepat Waktu"; // Menggunakan nama variabel yang lebih spesifik untuk keterangan absensi
    } else {
        $keterangan_absensi = "Terlambat";
    }

    // Periksa apakah finger_id sudah ada di tabel users
    $check_user_query = mysqli_query($conn, "SELECT * FROM users WHERE finger_id = '$finger_id'");
    
    if (mysqli_num_rows($check_user_query) > 0) {
        // Jika finger_id sudah ada, berarti ini adalah absensi siswa yang sudah terdaftar
        $siswa = mysqli_fetch_array($check_user_query);
        $nis = $siswa['nis'];
        $nama = $siswa['nama'];
        $kelas = $siswa['kelas'];

        // Masukkan data absensi ke tabel 'absensi'
        $insert_absensi_sql = "INSERT INTO absensi
                               (nis, id_finger, nama, kelas, tanggal, waktu, status, keterangan)
                               VALUES
                               ('$nis', '$finger_id', '$nama', '$kelas', '$tgl_sekarang', '$jam_sekarang', 'Hadir', '$keterangan_absensi')";

        if (mysqli_query($conn, $insert_absensi_sql)) {
            echo "Berhasil absen! Status: Hadir, Keterangan: " . $keterangan_absensi;
        } else {
            echo "Gagal absen: " . mysqli_error($conn);
        }
    } else {
        echo "Finger ID $finger_id tidak terdaftar di database!";
    }
} else {
    // Jika file dipanggil tanpa parameter finger_id
    echo "Menunggu data dari Fingerprint...";
}
?>