<?php
include "koneksi.php";

if (isset($_GET['waiting_enroll'])) {
    $id_target = $_GET['waiting_enroll'];
    mysqli_query($conn, "INSERT INTO sys_config (id, status, enroll_id) VALUES (1, 'enroll', '$id_target') 
                        ON DUPLICATE KEY UPDATE status='enroll', enroll_id='$id_target'");
}

if (isset($_POST['update'])) {
    $finger_id = mysqli_real_escape_string($conn, $_POST['finger_id']);
    $nis       = mysqli_real_escape_string($conn, $_POST['nis']);
    $nama      = mysqli_real_escape_string($conn, $_POST['nama']);
    $kelas     = mysqli_real_escape_string($conn, $_POST['kelas']);
    $update = mysqli_query($conn, "UPDATE users SET nis='$nis', nama='$nama', kelas='$kelas' WHERE finger_id='$finger_id'");
    if ($update) echo "<script>alert('Data berhasil diperbarui'); window.location='dashboard.php';</script>";
    else echo "<script>alert('Gagal memperbarui data');</script>";
}

if (isset($_POST['tambah'])) {
    $finger_id = mysqli_real_escape_string($conn, $_POST['finger_id']);
    $nis       = mysqli_real_escape_string($conn, $_POST['nis']);
    $nama      = mysqli_real_escape_string($conn, $_POST['nama']);
    $kelas     = mysqli_real_escape_string($conn, $_POST['kelas']);
    $cek = mysqli_query($conn, "SELECT * FROM users WHERE finger_id='$finger_id' OR nis='$nis'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Gagal! Finger ID atau NIS sudah terdaftar.'); window.location='dashboard.php?tambah=1';</script>";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO users (finger_id, nis, nama, kelas) VALUES ('$finger_id', '$nis', '$nama', '$kelas')");
        if ($insert) {
            mysqli_query($conn, "INSERT INTO sys_config (id, status, enroll_id) VALUES (1, 'enroll', '$finger_id') 
                                ON DUPLICATE KEY UPDATE status='enroll', enroll_id='$finger_id'");
            echo "<script>alert('Data $nama berhasil disimpan. Silakan tempelkan jari ke alat.'); window.location='dashboard.php?waiting_enroll=$finger_id';</script>";
        } else {
            $error = mysqli_error($conn);
            echo "<script>alert('Gagal simpan: $error'); window.location='dashboard.php';</script>";
        }
    }
}

if (isset($_GET['hapus'])) {
    $finger_id = $_GET['hapus'];
    $hapus = mysqli_query($conn, "DELETE FROM users WHERE finger_id='$finger_id'");
    if ($hapus) echo "<script>alert('Data berhasil dihapus'); window.location='dashboard.php';</script>";
}

$res_next_id  = mysqli_query($conn, "SELECT MAX(finger_id) as max_id FROM users");
$row_next_id  = mysqli_fetch_assoc($res_next_id);
$next_id      = (int)($row_next_id['max_id'] ?? 0) + 1;

$query = mysqli_query($conn, "SELECT * FROM users ORDER BY finger_id ASC");
$total = mysqli_num_rows($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Sistem Absensi</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --primary:      #3b82f6;
  --primary-h:    #2563eb;
  --green:        #10b981;
  --red:          #ef4444;
  --orange:       #f59e0b;
  --sky:          #0ea5e9;
  --purple:       #a855f7;
  --text-dark:    #1e293b;
  --text-light:   #64748b;
  --border:       rgba(255,255,255,0.09);
  --glass:        rgba(30,41,59,0.75);
  --glass2:       rgba(15,23,42,0.55);
  --radius:       16px;
  --radius-sm:    10px;
}

*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }

body {
  background: linear-gradient(rgba(15,23,42,0.82), rgba(10,18,35,0.9)), url('sekolah.jpg');
  background-size:cover; background-position:center; background-attachment:fixed;
  min-height:100vh; color:#fff;
}

/* ===== NAVBAR ===== */
.navbar {
  background: rgba(15,23,42,0.55);
  backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border);
  display:flex; align-items:center; justify-content:space-between;
  padding: 0 32px; height: 62px;
  position: sticky; top:0; z-index:200;
  transition: background .3s;
}
.navbar.scrolled { background: rgba(8,15,28,0.95); }

.nav-brand { display:flex; align-items:center; gap:12px; }
.nav-logo  { width:36px; height:36px; border-radius:8px; overflow:hidden; flex-shrink:0; }
.nav-logo img { width:100%; height:100%; object-fit:cover; }
.nav-title { font-size:16px; font-weight:700; color:#fff; line-height:1.2; }
.nav-sub   { font-size:10px; color:#64748b; display:block; }

.nav-center { display:flex; gap:4px; }
.nav-link {
  color:#94a3b8; text-decoration:none; font-size:13px; font-weight:500;
  padding:7px 16px; border-radius:50px; transition:all .2s;
}
.nav-link:hover { background:rgba(59,130,246,0.15); color:#93c5fd; }
.nav-link.active { background:rgba(59,130,246,0.2); color:var(--primary); }

.nav-right { display:flex; align-items:center; gap:10px; }
.nav-clock { font-size:12px; color:#64748b; background:rgba(0,0,0,0.25); padding:7px 14px; border-radius:50px; white-space:nowrap; }

.btn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:50px; font-size:13px; font-weight:600; border:none; cursor:pointer; text-decoration:none; transition:all .2s; }
.btn-home { background:rgba(255,255,255,0.07); color:#cbd5e1; border:1px solid rgba(255,255,255,0.1); }
.btn-home:hover { background:rgba(255,255,255,0.12); color:#fff; }

/* ===== MAIN ===== */
main { max-width:1160px; margin:0 auto; padding:28px 20px 60px; }

/* ===== PAGE HEADER ===== */
.page-header {
  display:flex; align-items:center; justify-content:space-between;
  margin-bottom:24px; gap:16px; flex-wrap:wrap;
}
.page-title h1 { font-size:22px; font-weight:700; color:#fff; }
.page-title p  { font-size:13px; color:#64748b; margin-top:2px; }
.page-actions  { display:flex; gap:10px; flex-wrap:wrap; }

.btn-primary { background:var(--primary); color:#fff; }
.btn-primary:hover { background:var(--primary-h); box-shadow:0 6px 16px rgba(59,130,246,0.35); transform:translateY(-1px); }
.btn-success { background:var(--green); color:#fff; }
.btn-success:hover { background:#059669; box-shadow:0 6px 16px rgba(16,185,129,0.3); }
.btn-warning-outline { background:transparent; color:var(--orange); border:1px solid rgba(245,158,11,0.4); }
.btn-warning-outline:hover { background:rgba(245,158,11,0.1); }
.btn-danger-sm { background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3); padding:5px 11px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; text-decoration:none; transition:all .2s; display:inline-flex; align-items:center; gap:5px; }
.btn-danger-sm:hover { background:rgba(239,68,68,0.25); }
.btn-edit-sm { background:rgba(245,158,11,0.15); color:#fbbf24; border:1px solid rgba(245,158,11,0.3); padding:5px 11px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; text-decoration:none; transition:all .2s; display:inline-flex; align-items:center; gap:5px; }
.btn-edit-sm:hover { background:rgba(245,158,11,0.25); }

/* ===== STAT CARDS ===== */
.stats-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:20px; }
.stat-card {
  background:var(--glass);
  backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px);
  border:1px solid var(--border); border-radius:var(--radius);
  padding:20px 22px; position:relative; overflow:hidden;
  transition:transform .2s, box-shadow .2s;
}
.stat-card:hover { transform:translateY(-3px); box-shadow:0 14px 36px rgba(0,0,0,0.3); }
.stat-bar { position:absolute; top:0; left:0; right:0; height:2px; }
.stat-icon { font-size:28px; margin-bottom:10px; display:block; }
.stat-label { font-size:11px; color:#64748b; font-weight:500; text-transform:uppercase; letter-spacing:.5px; }
.stat-val   { font-size:30px; font-weight:700; color:#fff; line-height:1.1; margin-top:4px; }
.stat-sub   { font-size:12px; color:#64748b; margin-top:4px; }

/* ===== ENROLL ALERT ===== */
.enroll-alert {
  background: rgba(245,158,11,0.1);
  border: 1px solid rgba(245,158,11,0.35);
  border-radius: var(--radius); padding:22px 26px; margin-bottom:20px;
  display:flex; align-items:center; gap:20px; flex-wrap:wrap;
}
.enroll-icon { font-size:36px; flex-shrink:0; }
.enroll-text h3 { font-size:16px; font-weight:700; color:#fbbf24; margin-bottom:4px; }
.enroll-text p  { font-size:13px; color:#fde68a; }
.enroll-badge {
  display:inline-flex; align-items:center; gap:7px;
  background:rgba(245,158,11,0.2); border:1px solid rgba(245,158,11,0.4);
  color:#fbbf24; padding:6px 14px; border-radius:50px; font-size:12px; font-weight:700;
  margin-top:8px; animation:badgePulse 2s ease-in-out infinite;
}
@keyframes badgePulse { 0%,100%{opacity:1} 50%{opacity:.7} }
.enroll-dot { width:7px; height:7px; border-radius:50%; background:#fbbf24; animation:dotBlink 1s infinite; }
@keyframes dotBlink { 0%,100%{opacity:1} 50%{opacity:.2} }

/* ===== MAIN CARD ===== */
.main-card {
  background:var(--glass);
  backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px);
  border:1px solid var(--border); border-radius:var(--radius);
  overflow:hidden;
}

.card-header {
  display:flex; align-items:center; justify-content:space-between;
  padding:18px 24px; border-bottom:1px solid rgba(255,255,255,0.06);
  flex-wrap:wrap; gap:14px;
}
.card-header h2 { font-size:16px; font-weight:600; color:#fff; }

.card-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }

.search-wrap { position:relative; }
.search-wrap svg { position:absolute; left:12px; top:50%; transform:translateY(-50%); width:15px; height:15px; fill:#64748b; }
.search-input {
  background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1);
  color:#fff; padding:9px 14px 9px 36px; border-radius:50px; font-size:13px;
  font-family:inherit; outline:none; width:220px; transition:all .2s;
}
.search-input::placeholder { color:#475569; }
.search-input:focus { border-color:rgba(59,130,246,0.5); background:rgba(59,130,246,0.08); width:260px; }

/* ===== FORM ===== */
.form-section {
  padding:20px 24px;
  border-bottom:1px solid rgba(255,255,255,0.06);
  background:rgba(255,255,255,0.02);
}
.form-title { font-size:14px; font-weight:600; color:#fff; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.form-title .dot { width:8px; height:8px; border-radius:50%; }
.form-grid { display:grid; grid-template-columns:repeat(4,1fr) auto auto; gap:10px; align-items:end; }
.form-field { display:flex; flex-direction:column; gap:5px; }
.form-field label { font-size:11px; color:#64748b; font-weight:600; letter-spacing:.3px; text-transform:uppercase; }
.form-input {
  background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.12);
  color:#fff; padding:10px 14px; border-radius:var(--radius-sm);
  font-size:13px; font-family:inherit; outline:none; transition:all .2s; width:100%;
}
.form-input::placeholder { color:#475569; }
.form-input:focus { border-color:rgba(59,130,246,0.5); background:rgba(59,130,246,0.08); }
.form-input:read-only { opacity:.6; cursor:not-allowed; }
.btn-save { background:var(--primary); color:#fff; border:none; padding:10px 20px; border-radius:var(--radius-sm); font-size:13px; font-weight:600; cursor:pointer; font-family:inherit; white-space:nowrap; transition:all .2s; align-self:end; }
.btn-save:hover { background:var(--primary-h); }
.btn-save.edit { background:var(--orange); }
.btn-save.edit:hover { background:#d97706; }
.btn-cancel { color:#64748b; font-size:13px; text-decoration:none; padding:10px 14px; border-radius:var(--radius-sm); transition:all .2s; white-space:nowrap; align-self:end; display:inline-flex; align-items:center; }
.btn-cancel:hover { color:var(--red); background:rgba(239,68,68,0.08); }

/* ===== TABLE ===== */
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; min-width:560px; }
thead th {
  padding:11px 18px; text-align:left;
  font-size:10.5px; font-weight:700; color:#475569;
  text-transform:uppercase; letter-spacing:.6px;
  background:rgba(255,255,255,0.02);
  border-bottom:1px solid rgba(255,255,255,0.06);
}
tbody td { padding:13px 18px; font-size:13px; color:#cbd5e1; border-bottom:1px solid rgba(255,255,255,0.04); vertical-align:middle; }
tbody tr:last-child td { border-bottom:none; }
tbody tr { transition:background .15s; }
tbody tr:hover td { background:rgba(255,255,255,0.025); }
tbody tr.row-new td { background:rgba(245,158,11,0.06); }
tbody tr.row-new:hover td { background:rgba(245,158,11,0.1); }

.id-badge { font-weight:700; color:#fff; background:rgba(59,130,246,0.2); border:1px solid rgba(59,130,246,0.3); padding:3px 10px; border-radius:6px; font-size:12px; font-family:monospace; }
.name-cell { color:#fff; font-weight:500; }
.new-tag { background:rgba(245,158,11,0.2); color:#fbbf24; font-size:10px; font-weight:700; padding:2px 7px; border-radius:4px; margin-left:6px; border:1px solid rgba(245,158,11,0.3); }

.actions { display:flex; gap:6px; align-items:center; }

/* Empty state */
.empty-row td { text-align:center; padding:48px 0; color:#475569; font-size:13px; }
.empty-row .empty-icon { font-size:36px; display:block; margin-bottom:10px; }

/* ===== TOAST ===== */
.toast {
  position:fixed; bottom:28px; left:50%;
  transform:translateX(-50%) translateY(12px);
  background:rgba(15,23,42,0.95); border:1px solid rgba(255,255,255,0.1);
  color:#fff; padding:11px 20px; border-radius:12px; font-size:13px;
  z-index:500; opacity:0; pointer-events:none;
  transition:all .3s; white-space:nowrap;
  box-shadow:0 10px 30px rgba(0,0,0,0.4);
}
.toast.show { opacity:1; transform:translateX(-50%) translateY(0); }

/* FOOTER */
.footer { text-align:center; padding:20px; color:#334155; font-size:12px; margin-top:12px; }

/* ===== RESPONSIVE ===== */
@media (max-width:900px) {
  .navbar { padding:0 18px; }
  .nav-center { display:none; }
  main { padding:20px 14px 50px; }
  .stats-grid { grid-template-columns:1fr 1fr; }
  .form-grid { grid-template-columns:1fr 1fr; }
  .search-input { width:180px; }
  .search-input:focus { width:200px; }
}

@media (max-width:600px) {
  .navbar { height:56px; }
  .nav-clock { display:none; }
  .nav-title { font-size:14px; }
  .nav-sub { display:none; }
  main { padding:16px 12px 50px; }
  .page-header { flex-direction:column; align-items:flex-start; gap:12px; }
  .stats-grid { grid-template-columns:1fr 1fr; gap:10px; }
  .stat-val { font-size:24px; }
  .card-header { flex-direction:column; align-items:flex-start; }
  .card-actions { width:100%; }
  .search-input,.search-input:focus { width:100%; }
  .search-wrap { flex:1; }
  .form-grid { grid-template-columns:1fr; }
  .form-section { padding:16px; }
  .card-header { padding:14px 16px; }
  thead th, tbody td { padding:11px 14px; }
  .enroll-alert { flex-direction:column; gap:12px; padding:18px; }
}

@media (max-width:380px) {
  .stats-grid { grid-template-columns:1fr; }
  .btn { padding:8px 14px; font-size:12px; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
  <div class="nav-brand">
    <div class="nav-logo">
      <img src="Gambar1.png" alt="Logo" onerror="this.style.display='none'">
    </div>
    <div>
      <div class="nav-title">Dashboard Admin</div>
      <span class="nav-sub">SMKN 2 Yogyakarta</span>
    </div>
  </div>

  <div class="nav-center">
    <a href="index.php" class="nav-link">🏠 Home</a>
    <a href="dashboard.php" class="nav-link active">👥 Data Siswa</a>
    <a href="rekap_absensi.php" class="nav-link">📊 Rekap</a>
  </div>

  <div class="nav-right">
    <div class="nav-clock" id="clock">—</div>
    <a href="index.php" class="btn btn-home">← Kembali</a>
  </div>
</nav>

<!-- MAIN -->
<main>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <h1>Manajemen Data Siswa</h1>
      <p>Kelola data siswa dan pendaftaran sidik jari</p>
    </div>
    <div class="page-actions">
      <button onclick="exportTableToCSV('data_siswa.csv')" class="btn btn-success">📥 Export CSV</button>
      <a href="?tambah=1" class="btn btn-primary">＋ Tambah Siswa</a>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-bar" style="background:linear-gradient(90deg,var(--primary),#60a5fa)"></div>
      <span class="stat-icon">👥</span>
      <div class="stat-label">Total Siswa Terdaftar</div>
      <div class="stat-val"><?= $total ?></div>
      <div class="stat-sub">Finger ID teregistrasi</div>
    </div>
    <div class="stat-card">
      <div class="stat-bar" style="background:linear-gradient(90deg,var(--green),#34d399)"></div>
      <span class="stat-icon">📡</span>
      <div class="stat-label">Status Modul IoT</div>
      <div class="stat-val" style="font-size:18px;color:var(--green);margin-top:8px;">● Online & Aktif</div>
      <div class="stat-sub">Sensor siap digunakan</div>
    </div>
    <div class="stat-card">
      <div class="stat-bar" style="background:linear-gradient(90deg,var(--orange),#fcd34d)"></div>
      <span class="stat-icon">🆔</span>
      <div class="stat-label">Finger ID Berikutnya</div>
      <div class="stat-val">#<?= $next_id ?></div>
      <div class="stat-sub">Auto-increment</div>
    </div>
  </div>

  <!-- Enroll Alert -->
  <?php if(isset($_GET['waiting_enroll'])): ?>
  <div class="enroll-alert">
    <div class="enroll-icon">🖐️</div>
    <div class="enroll-text">
      <h3>Mode Pendaftaran Biometrik Aktif</h3>
      <p>Tempelkan jari pada mesin pemindai untuk merekam sidik jari <strong>ID: <?= $_GET['waiting_enroll'] ?></strong></p>
      <div class="enroll-badge">
        <div class="enroll-dot"></div>
        <span id="status-text">Menghubungkan ke alat...</span>
      </div>
    </div>
  </div>
  <script>
    setInterval(function(){
      fetch('check_enroll_status.php')
      .then(r => r.text())
      .then(data => {
        document.getElementById('status-text').textContent = data;
        if(data === 'Pendaftaran Sukses!') {
          document.getElementById('status-text').closest('.enroll-badge').style.background = 'rgba(16,185,129,0.2)';
          document.getElementById('status-text').closest('.enroll-badge').style.color = '#34d399';
          setTimeout(() => window.location.href='dashboard.php', 2000);
        }
      });
    }, 1000);
  </script>
  <?php endif; ?>

  <!-- Main Card -->
  <div class="main-card">

    <!-- Card Header -->
    <div class="card-header">
      <h2>Daftar Siswa <span style="color:#475569;font-weight:400;font-size:13px;">(<?= $total ?> data)</span></h2>
      <div class="card-actions">
        <div class="search-wrap">
          <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
          <input type="text" class="search-input" id="searchInput" onkeyup="searchTable()" placeholder="Cari nama atau NIS...">
        </div>
      </div>
    </div>

    <!-- Form Tambah -->
    <?php if(isset($_GET['tambah'])): ?>
    <div class="form-section">
      <div class="form-title">
        <div class="dot" style="background:var(--primary)"></div>
        Tambah Siswa Baru
      </div>
      <form method="POST">
        <div class="form-grid">
          <div class="form-field">
            <label>Finger ID</label>
            <input type="number" class="form-input" name="finger_id" value="<?= $next_id ?>" required>
          </div>
          <div class="form-field">
            <label>NIS</label>
            <input type="text" class="form-input" name="nis" placeholder="Nomor Induk Siswa" required>
          </div>
          <div class="form-field">
            <label>Nama Lengkap</label>
            <input type="text" class="form-input" name="nama" placeholder="Nama Siswa" required autocomplete="off">
          </div>
          <div class="form-field">
            <label>Kelas</label>
            <input type="text" class="form-input" name="kelas" placeholder="contoh: XI RPL 1" required>
          </div>
          <button type="submit" name="tambah" class="btn-save">💾 Simpan & Scan</button>
          <a href="dashboard.php" class="btn-cancel">Batal</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <!-- Form Edit -->
    <?php if(isset($_GET['edit'])):
      $id_edit   = $_GET['edit'];
      $data_edit = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM users WHERE finger_id='$id_edit'"));
    ?>
    <div class="form-section">
      <div class="form-title">
        <div class="dot" style="background:var(--orange)"></div>
        Edit Data Siswa — ID #<?= $id_edit ?>
      </div>
      <form method="POST">
        <div class="form-grid">
          <input type="hidden" name="finger_id" value="<?= $data_edit['finger_id'] ?>">
          <div class="form-field">
            <label>Finger ID</label>
            <input type="text" class="form-input" value="<?= $data_edit['finger_id'] ?>" readonly>
          </div>
          <div class="form-field">
            <label>NIS</label>
            <input type="text" class="form-input" name="nis" value="<?= $data_edit['nis'] ?>" required>
          </div>
          <div class="form-field">
            <label>Nama Lengkap</label>
            <input type="text" class="form-input" name="nama" value="<?= $data_edit['nama'] ?>" required>
          </div>
          <div class="form-field">
            <label>Kelas</label>
            <input type="text" class="form-input" name="kelas" value="<?= $data_edit['kelas'] ?>" required>
          </div>
          <button type="submit" name="update" class="btn-save edit">✏️ Update</button>
          <a href="dashboard.php" class="btn-cancel">Batal</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="table-wrap">
      <table id="dataTable">
        <thead>
          <tr>
            <th>Finger ID</th>
            <th>NIS</th>
            <th>Nama Siswa</th>
            <th>Kelas</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if($total > 0):
          while($data = mysqli_fetch_array($query)):
            $is_new = ($data['nama'] == 'New Student');
        ?>
          <tr class="<?= $is_new ? 'row-new' : '' ?>">
            <td><span class="id-badge">#<?= $data['finger_id'] ?></span></td>
            <td><?= $data['nis'] ?: '<span style="color:#334155">—</span>' ?></td>
            <td class="name-cell">
              <?= $data['nama'] ?>
              <?php if($is_new): ?><span class="new-tag">Belum Lengkap</span><?php endif; ?>
            </td>
            <td><?= $data['kelas'] ?: '<span style="color:#334155">—</span>' ?></td>
            <td>
              <div class="actions">
                <a href="?edit=<?= $data['finger_id'] ?>" class="btn-edit-sm">✏️ Edit</a>
                <a href="?hapus=<?= $data['finger_id'] ?>" class="btn-danger-sm"
                   onclick="return confirm('Yakin ingin menghapus <?= $data['nama'] ?>?')">🗑 Hapus</a>
              </div>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr class="empty-row">
            <td colspan="5">
              <span class="empty-icon">📭</span>
              Belum ada data siswa terdaftar.
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div><!-- /main-card -->

</main>

<div class="footer">&copy; <?= date('Y') ?> SMK Negeri 2 Yogyakarta. Semua Hak Cipta Dilindungi.</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
// Clock
function tick() {
  const n = new Date();
  document.getElementById('clock').textContent =
    n.toLocaleDateString('id-ID',{weekday:'short',day:'numeric',month:'short'}) + ' · ' +
    n.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false}).replace(/\./g,':');
}
setInterval(tick, 1000); tick();

// Navbar scroll
window.addEventListener('scroll', () => {
  document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 20);
});

// Search
function searchTable() {
  const f = document.getElementById('searchInput').value.toUpperCase();
  const rows = document.querySelectorAll('#dataTable tbody tr');
  rows.forEach(tr => {
    const cells = tr.querySelectorAll('td');
    let found = false;
    cells.forEach((td, i) => { if(i < 4 && td.textContent.toUpperCase().includes(f)) found = true; });
    tr.style.display = found ? '' : 'none';
  });
}

// Export CSV
function exportTableToCSV(filename) {
  const csv = [];
  document.querySelectorAll('#dataTable tr').forEach(row => {
    const cols = row.querySelectorAll('td,th');
    const rowData = [];
    for(let i = 0; i < cols.length - 1; i++) {
      rowData.push('"' + cols[i].innerText.replace(/"/g,'""') + '"');
    }
    csv.push(rowData.join(','));
  });
  const blob = new Blob([csv.join('\n')], {type:'text/csv'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  a.click();
  showToast('✅ File CSV berhasil diunduh!');
}

// Toast
let tt;
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(tt);
  tt = setTimeout(() => t.classList.remove('show'), 2800);
}
</script>
</body>
</html>