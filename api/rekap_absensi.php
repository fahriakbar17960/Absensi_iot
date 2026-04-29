<?php
include "koneksi.php";

if(isset($_POST['simpan_izin'])){
    $nis_input  = mysqli_real_escape_string($conn, $_POST['nis']);
    $tanggal    = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $cari = mysqli_query($conn, "SELECT finger_id FROM users WHERE nis='$nis_input'");
    if(mysqli_num_rows($cari) > 0){
        $ds = mysqli_fetch_assoc($cari);
        $id_finger = $ds['finger_id'];
        $cek = mysqli_query($conn, "SELECT * FROM absensi WHERE id_finger='$id_finger' AND tanggal='$tanggal'");
        if(mysqli_num_rows($cek) > 0)
            mysqli_query($conn, "UPDATE absensi SET keterangan='$keterangan', waktu='00:00:00' WHERE id_finger='$id_finger' AND tanggal='$tanggal'");
        else
            mysqli_query($conn, "INSERT INTO absensi (id_finger, tanggal, waktu, status, keterangan) VALUES ('$id_finger','$tanggal','00:00:00','0','$keterangan')");
        echo "<script>alert('Data $keterangan untuk NIS $nis_input berhasil dicatat!'); window.location='rekap_absensi.php';</script>";
    } else {
        echo "<script>alert('Siswa dengan NIS $nis_input tidak ditemukan.'); window.location='rekap_absensi.php';</script>";
    }
}
if(isset($_GET['hapus'])){
    $id_hapus = mysqli_real_escape_string($conn, $_GET['hapus']);
    if(mysqli_query($conn, "DELETE FROM absensi WHERE id='$id_hapus'"))
        echo "<script>alert('Data berhasil dihapus'); window.location='rekap_absensi.php';</script>";
}
if(isset($_GET['hapus_semua'])){
    if(mysqli_query($conn, "DELETE FROM absensi"))
        echo "<script>alert('Semua data berhasil dikosongkan'); window.location='rekap_absensi.php';</script>";
}
if(isset($_GET['proses_alfa'])){
    date_default_timezone_set('Asia/Jakarta');
    $hari_ini = date('Y-m-d');
    $q = mysqli_query($conn, "SELECT finger_id FROM users WHERE finger_id NOT IN (SELECT id_finger FROM absensi WHERE tanggal='$hari_ini')");
    $n = 0;
    while($r = mysqli_fetch_array($q)){
        mysqli_query($conn, "INSERT INTO absensi (id_finger,tanggal,waktu,status,keterangan) VALUES ('{$r['finger_id']}','$hari_ini','00:00:00','0','Alfa')");
        $n++;
    }
    echo "<script>alert('Berhasil memproses $n siswa ALFA.'); window.location='rekap_absensi.php';</script>";
}

$conditions = [];
if(!empty($_GET['tanggal']))    $conditions[] = "absensi.tanggal = '".mysqli_real_escape_string($conn,$_GET['tanggal'])."'";
if(!empty($_GET['search_nis'])) $conditions[] = "users.nis LIKE '%".mysqli_real_escape_string($conn,$_GET['search_nis'])."%'";
$where = $conditions ? "WHERE ".implode(" AND ",$conditions) : "";

if(isset($_GET['export'])){
    header("Content-type: application/vnd-ms-excel");
    header("Content-Disposition: attachment; filename=rekap_absensi.xls");
    echo "<table border='1'><tr><th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Waktu</th><th>Status</th><th>Keterangan</th></tr>";
    $q = mysqli_query($conn,"SELECT absensi.*,users.nama,users.kelas,users.nis FROM absensi JOIN users ON absensi.id_finger=users.finger_id $where ORDER BY tanggal DESC,waktu DESC");
    while($d=mysqli_fetch_array($q)) echo "<tr><td>{$d['tanggal']}</td><td>{$d['nis']}</td><td>{$d['nama']}</td><td>{$d['kelas']}</td><td>{$d['waktu']}</td><td>{$d['status']}</td><td>{$d['keterangan']}</td></tr>";
    echo "</table>"; exit;
}

$query = mysqli_query($conn,"
    SELECT absensi.*, users.nama, users.kelas, users.nis,
    (SELECT COUNT(*) FROM absensi a WHERE a.id_finger=absensi.id_finger AND a.keterangan='Terlambat'
     AND MONTH(a.tanggal)=MONTH(CURRENT_DATE()) AND YEAR(a.tanggal)=YEAR(CURRENT_DATE())) as total_telat_bulan_ini
    FROM absensi JOIN users ON absensi.id_finger=users.finger_id
    $where ORDER BY tanggal DESC, waktu DESC
");

$stat_q = mysqli_query($conn,"
    SELECT COUNT(*) as total,
    SUM(CASE WHEN absensi.keterangan='Tepat Waktu' THEN 1 ELSE 0 END) as tepat,
    SUM(CASE WHEN absensi.keterangan='Terlambat' THEN 1 ELSE 0 END) as terlambat,
    SUM(CASE WHEN absensi.keterangan='Sakit' THEN 1 ELSE 0 END) as sakit,
    SUM(CASE WHEN absensi.keterangan='Izin' THEN 1 ELSE 0 END) as izin,
    SUM(CASE WHEN absensi.keterangan='Alfa' THEN 1 ELSE 0 END) as alfa
    FROM absensi JOIN users ON absensi.id_finger=users.finger_id $where
");
$stat = mysqli_fetch_array($stat_q);
$total_absen=$stat['total']??0; $jml_tepat=$stat['tepat']??0; $jml_telat=$stat['terlambat']??0;
$jml_sakit=$stat['sakit']??0;  $jml_izin=$stat['izin']??0;   $jml_alfa=$stat['alfa']??0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rekap Absensi — Sistem Absensi</title>
<link rel="icon" type="image/png" href="Gambar1.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
  --primary:   #3b82f6;
  --primary-h: #2563eb;
  --green:     #10b981;
  --red:       #ef4444;
  --orange:    #f59e0b;
  --sky:       #0ea5e9;
  --purple:    #a855f7;
  --border:    rgba(255,255,255,0.09);
  --glass:     rgba(30,41,59,0.78);
  --glass2:    rgba(15,23,42,0.6);
  --radius:    16px;
  --radius-sm: 10px;
}
*,*::before,*::after { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }

body {
  background: linear-gradient(rgba(15,23,42,0.82), rgba(10,18,35,0.9)), url('sekolah.jpg');
  background-size:cover; background-position:center; background-attachment:fixed;
  min-height:100vh; color:#fff;
}

/* ===== SIDE DECORATIONS ===== */
.side-deco {
  position:fixed; top:0; bottom:0;
  width:calc((100vw - 1180px) / 2);
  pointer-events:none; z-index:0; overflow:hidden;
}
.side-left  { left:0; }
.side-right { right:0; }
@media (max-width:1300px) { .side-deco { display:none; } }

.side-grid {
  position:absolute; inset:0;
  background-image:
    linear-gradient(rgba(59,130,246,0.04) 1px,transparent 1px),
    linear-gradient(90deg,rgba(59,130,246,0.04) 1px,transparent 1px);
  background-size:30px 30px;
  mask-image:radial-gradient(ellipse 80% 80% at 50% 50%,black 0%,transparent 100%);
}
.side-orb {
  position:absolute; border-radius:50%; filter:blur(50px);
  animation:orbDrift ease-in-out infinite;
}
@keyframes orbDrift { 0%,100%{transform:translateY(0) scale(1)} 50%{transform:translateY(-25px) scale(1.06)} }

.ring {
  position:absolute; border-radius:50%;
  border:1px solid rgba(59,130,246,0.15);
  animation:ringFloat linear infinite;
}
@keyframes ringFloat {
  0%   { transform:translateY(0) scale(1); opacity:0; }
  15%  { opacity:1; }
  85%  { opacity:1; }
  100% { transform:translateY(-100vh) scale(1.1); opacity:0; }
}
.fdot {
  position:absolute; border-radius:50%;
  background:rgba(59,130,246,0.4);
  animation:dotBlink ease-in-out infinite;
}
@keyframes dotBlink { 0%,100%{opacity:.15;transform:scale(1)} 50%{opacity:.7;transform:scale(1.4)} }

.scan-line {
  position:absolute; width:1px;
  background:linear-gradient(to bottom,transparent,rgba(59,130,246,0.5),rgba(16,185,129,0.3),transparent);
  animation:scanMove 4s ease-in-out infinite;
}
@keyframes scanMove { 0%,100%{top:10%;height:25%;opacity:.4} 50%{top:58%;height:32%;opacity:.9} }

.side-label {
  position:absolute; bottom:28%; left:50%;
  transform:translateX(-50%) rotate(-90deg);
  font-size:9px; font-weight:700; letter-spacing:3px;
  color:rgba(255,255,255,0.08); white-space:nowrap; text-transform:uppercase;
}
.side-status {
  position:absolute; top:32%; left:50%; transform:translateX(-50%);
  display:flex; flex-direction:column; align-items:center; gap:14px;
}
.ss-dot {
  width:8px; height:8px; border-radius:50%;
  animation:pulse 2s ease-in-out infinite;
}
@keyframes pulse { 0%,100%{box-shadow:0 0 0 0 currentColor} 50%{box-shadow:0 0 0 5px transparent} }
.ss-label {
  font-size:8px; font-weight:700; letter-spacing:1.5px;
  text-transform:uppercase; writing-mode:vertical-rl;
  color:rgba(255,255,255,0.2); margin-top:3px;
}

.data-stream {
  position:absolute; right:20px; top:80px; bottom:60px;
  width:1px; background:linear-gradient(to bottom,transparent,rgba(59,130,246,0.1),transparent);
}
.stream-p {
  position:absolute; width:3px; height:3px; border-radius:50%;
  background:var(--primary); right:-1px;
  animation:streamFall linear infinite;
}
@keyframes streamFall { from{top:0;opacity:0} 10%{opacity:1} 90%{opacity:1} to{top:100%;opacity:0} }

.corner { position:absolute; width:28px; height:28px; }
.corner-tl { top:72px; left:12px; border-top:1px solid rgba(59,130,246,0.28); border-left:1px solid rgba(59,130,246,0.28); }
.corner-br { bottom:55px; right:12px; border-bottom:1px solid rgba(59,130,246,0.28); border-right:1px solid rgba(59,130,246,0.28); }

/* ===== NAVBAR ===== */
.navbar {
  background:rgba(15,23,42,0.55);
  backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px);
  border-bottom:1px solid var(--border);
  display:flex; align-items:center; justify-content:space-between;
  padding:0 32px; height:62px;
  position:sticky; top:0; z-index:100;
  transition:background .3s;
}
.navbar.scrolled { background:rgba(8,15,28,0.95); }

.nav-brand { display:flex; align-items:center; gap:12px; }
.nav-logo  { width:36px; height:36px; border-radius:8px; overflow:hidden; flex-shrink:0; }
.nav-logo img { width:100%; height:100%; object-fit:cover; }
.nav-title { font-size:16px; font-weight:700; color:#fff; line-height:1.2; }
.nav-sub   { font-size:10px; color:#64748b; display:block; }

.nav-center { display:flex; gap:4px; }
.nav-link { color:#94a3b8; text-decoration:none; font-size:13px; font-weight:500; padding:7px 16px; border-radius:50px; transition:all .2s; display:inline-flex; align-items:center; gap:6px; }
.nav-link:hover { background:rgba(59,130,246,0.15); color:#93c5fd; }
.nav-link.active { background:rgba(59,130,246,0.22); color:var(--primary); }

.nav-right { display:flex; align-items:center; gap:10px; }
.nav-clock { font-size:12px; color:#64748b; background:rgba(0,0,0,0.25); padding:7px 14px; border-radius:50px; white-space:nowrap; }
.btn-nav { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:50px; font-size:13px; font-weight:600; border:none; cursor:pointer; text-decoration:none; transition:all .2s; }
.btn-nav-home { background:rgba(255,255,255,0.07); color:#cbd5e1; border:1px solid rgba(255,255,255,0.1); }
.btn-nav-home:hover { background:rgba(255,255,255,0.12); color:#fff; }

/* ===== HAMBURGER ===== */
.hamburger {
  display: none;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  width: 38px; height: 38px;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  cursor: pointer;
  padding: 8px;
  flex-shrink: 0;
}
.hamburger span {
  display: block;
  width: 100%; height: 2px;
  background: #94a3b8;
  border-radius: 2px;
  transition: all .3s;
}
.hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.hamburger.open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
.hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* ===== MOBILE MENU ===== */
.mobile-menu {
  display: none;
  position: fixed;
  top: 62px; left: 0; right: 0;
  background: rgba(8,15,28,0.97);
  backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
  border-bottom: 1px solid rgba(255,255,255,0.08);
  z-index: 99;
  padding: 10px 16px 16px;
  flex-direction: column;
  gap: 4px;
  transform: translateY(-8px);
  opacity: 0;
  transition: all .25s ease;
}
.mobile-menu.open {
  display: flex;
  transform: translateY(0);
  opacity: 1;
}
.mobile-menu .nav-link {
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 14px;
  color: #94a3b8;
  display: block;
}
.mobile-menu .nav-link.active {
  background: rgba(59,130,246,0.2);
  color: var(--primary);
}
.mobile-menu .nav-link:hover {
  background: rgba(59,130,246,0.12);
  color: #93c5fd;
}
.mobile-menu-divider {
  height: 1px;
  background: rgba(255,255,255,0.07);
  margin: 6px 0;
}

/* ===== MAIN ===== */
main { max-width:1160px; margin:0 auto; padding:28px 20px 60px; position:relative; z-index:1; }

/* ===== PAGE HEADER ===== */
.page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:22px; gap:16px; flex-wrap:wrap; }
.page-title h1 { font-size:22px; font-weight:700; color:#fff; }
.page-title p  { font-size:13px; color:#64748b; margin-top:2px; }

/* ===== STAT CARDS ROW ===== */
.stats-row { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:18px; }
.stat-card {
  background:var(--glass);
  backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px);
  border:1px solid var(--border); border-radius:var(--radius);
  padding:16px 14px; position:relative; overflow:hidden;
  transition:transform .22s, box-shadow .22s;
  cursor:default;
}
.stat-card:hover { transform:translateY(-3px); box-shadow:0 14px 36px rgba(0,0,0,.3); }
.stat-top { position:absolute; top:0; left:0; right:0; height:2px; border-radius:var(--radius) var(--radius) 0 0; }
.stat-icon  { font-size:20px; margin-bottom:8px; display:block; }
.stat-label { font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.stat-val   { font-size:24px; font-weight:700; color:#fff; line-height:1.1; margin-top:4px; }
.stat-val .sub { font-size:13px; margin-left:2px; }

/* ===== MIDDLE SECTION ===== */
.mid-section { display:grid; grid-template-columns:1fr 340px; gap:16px; margin-bottom:18px; }

.chart-card {
  background:var(--glass);
  backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px);
  border:1px solid var(--border); border-radius:var(--radius);
  padding:22px 24px; display:flex; flex-direction:column;
}
.section-title { font-size:14px; font-weight:600; color:#fff; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.section-title .dot { width:8px; height:8px; border-radius:50%; }
.chart-inner { flex:1; position:relative; min-height:180px; }

.right-panel { display:flex; flex-direction:column; gap:14px; }

.filter-card {
  background:var(--glass);
  backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px);
  border:1px solid var(--border); border-radius:var(--radius);
  padding:18px 20px;
}
.filter-form { display:flex; flex-direction:column; gap:10px; }
.filter-input {
  background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.12);
  color:#fff; padding:9px 14px; border-radius:var(--radius-sm);
  font-size:13px; font-family:inherit; outline:none; width:100%; transition:all .2s;
}
.filter-input::placeholder { color:#475569; }
.filter-input:focus { border-color:rgba(59,130,246,.5); background:rgba(59,130,246,.08); }
.filter-input[type=date]::-webkit-calendar-picker-indicator { filter:invert(1) opacity(.5); cursor:pointer; }
.filter-btns { display:grid; grid-template-columns:1fr 1fr; gap:8px; }

.action-card {
  background:var(--glass);
  backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px);
  border:1px solid var(--border); border-radius:var(--radius);
  padding:18px 20px;
}
.action-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }

/* Buttons */
.btn {
  display:inline-flex; align-items:center; justify-content:center; gap:6px;
  padding:9px 14px; border-radius:var(--radius-sm);
  font-size:12.5px; font-weight:600; border:none; cursor:pointer;
  text-decoration:none; transition:all .2s; white-space:nowrap;
  font-family:inherit; color:#fff;
}
.btn-primary { background:var(--primary); }
.btn-primary:hover { background:var(--primary-h); box-shadow:0 4px 14px rgba(59,130,246,.35); }
.btn-ghost { background:rgba(255,255,255,0.07); color:#94a3b8; border:1px solid rgba(255,255,255,.1); }
.btn-ghost:hover { background:rgba(255,255,255,0.12); color:#fff; }
.btn-info    { background:var(--sky); }
.btn-info:hover    { background:#0284c7; box-shadow:0 4px 14px rgba(14,165,233,.3); }
.btn-warning { background:var(--orange); }
.btn-warning:hover { background:#d97706; box-shadow:0 4px 14px rgba(245,158,11,.3); }
.btn-success { background:var(--green); }
.btn-success:hover { background:#059669; box-shadow:0 4px 14px rgba(16,185,129,.3); }
.btn-danger  { background:rgba(239,68,68,.18); color:#f87171; border:1px solid rgba(239,68,68,.3); }
.btn-danger:hover  { background:rgba(239,68,68,.3); }
.btn-sm      { padding:5px 10px; font-size:11px; border-radius:6px; }
.btn-full    { width:100%; }

/* ===== TABLE CARD ===== */
.table-card {
  background:var(--glass);
  backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px);
  border:1px solid var(--border); border-radius:var(--radius);
  overflow:hidden;
}
.table-header {
  display:flex; align-items:center; justify-content:space-between;
  padding:16px 22px; border-bottom:1px solid rgba(255,255,255,.06);
  flex-wrap:wrap; gap:10px;
}
.table-header h3 { font-size:14px; font-weight:600; color:#fff; }
.row-count { font-size:12px; color:#475569; }

.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; min-width:680px; }
thead th {
  padding:11px 18px; text-align:left;
  font-size:10px; font-weight:700; color:#475569;
  text-transform:uppercase; letter-spacing:.7px;
  background:rgba(255,255,255,0.02);
  border-bottom:1px solid rgba(255,255,255,.06);
}
tbody td { padding:13px 18px; font-size:13px; color:#cbd5e1; border-bottom:1px solid rgba(255,255,255,.04); vertical-align:middle; }
tbody tr:last-child td { border-bottom:none; }
tbody tr { transition:background .15s; }
tbody tr:hover td { background:rgba(255,255,255,.025); }

.name-bold { color:#fff; font-weight:600; }
.telat-tag  { display:inline-flex; align-items:center; gap:4px; background:rgba(239,68,68,.14); color:#f87171; border:1px solid rgba(239,68,68,.22); padding:2px 7px; border-radius:4px; font-size:10px; font-weight:600; margin-top:4px; }
.time-mono  { font-family:monospace; font-size:13px; color:#94a3b8; }
.date-cell  { font-size:12px; color:#64748b; }

.badge { padding:4px 10px; border-radius:6px; font-size:11px; font-weight:700; display:inline-block; }
.b-hadir { background:rgba(16,185,129,.18); color:#34d399; border:1px solid rgba(16,185,129,.25); }
.b-telat  { background:rgba(239,68,68,.18); color:#f87171; border:1px solid rgba(239,68,68,.25); }
.b-alfa   { background:rgba(153,27,27,.28); color:#fca5a5; border:1px solid rgba(239,68,68,.3); }
.b-sakit  { background:rgba(245,158,11,.18); color:#fbbf24; border:1px solid rgba(245,158,11,.25); }
.b-izin   { background:rgba(14,165,233,.18); color:#38bdf8; border:1px solid rgba(14,165,233,.25); }

.empty-td { text-align:center; padding:52px 0; color:#475569; }
.empty-td span { font-size:32px; display:block; margin-bottom:10px; }

/* ===== MODAL ===== */
.modal-overlay {
  position:fixed; inset:0; background:rgba(0,0,0,.6);
  backdrop-filter:blur(6px); z-index:300;
  display:none; align-items:center; justify-content:center; padding:20px;
}
.modal-overlay.open { display:flex; }
.modal-box {
  background:linear-gradient(145deg,#0f1e35,#0a1525);
  border:1px solid rgba(59,130,246,.25); border-radius:20px;
  padding:32px 28px; width:100%; max-width:400px;
  box-shadow:0 24px 60px rgba(0,0,0,.6);
  animation:modalIn .28s cubic-bezier(.34,1.56,.64,1);
  position:relative;
}
@keyframes modalIn { from{transform:translateY(20px) scale(.97);opacity:0} to{transform:none;opacity:1} }
.modal-top { position:absolute; top:-1px; left:15%; right:15%; height:1px; background:linear-gradient(90deg,transparent,var(--primary),transparent); }
.modal-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; }
.modal-head h2 { font-size:17px; font-weight:700; color:#fff; }
.modal-close { background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.1); color:#94a3b8; width:30px; height:30px; border-radius:8px; font-size:16px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:.2s; }
.modal-close:hover { background:rgba(239,68,68,.15); color:#f87171; }
.form-group { margin-bottom:14px; }
.form-group label { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px; }
.form-control {
  width:100%; padding:10px 14px;
  background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.12);
  color:#fff; border-radius:var(--radius-sm); font-size:13px; font-family:inherit; outline:none; transition:all .2s;
}
.form-control::placeholder { color:#475569; }
.form-control:focus { border-color:rgba(59,130,246,.5); background:rgba(59,130,246,.08); }
.form-control option { background:#1e2d42; }
.form-control[type=date]::-webkit-calendar-picker-indicator { filter:invert(1) opacity(.5); }
.modal-foot { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }
.btn-cancel-modal { background:rgba(255,255,255,.07); color:#94a3b8; border:1px solid rgba(255,255,255,.1); }
.btn-cancel-modal:hover { background:rgba(255,255,255,.12); color:#fff; }

/* TOAST */
.toast {
  position:fixed; bottom:28px; left:50%;
  transform:translateX(-50%) translateY(12px);
  background:rgba(15,23,42,.95); border:1px solid rgba(255,255,255,.1);
  color:#fff; padding:11px 20px; border-radius:12px; font-size:13px;
  z-index:500; opacity:0; pointer-events:none;
  transition:all .3s; white-space:nowrap;
  box-shadow:0 10px 30px rgba(0,0,0,.4);
}
.toast.show { opacity:1; transform:translateX(-50%) translateY(0); }

footer { text-align:center; padding:20px; color:#334155; font-size:12px; margin-top:12px; position:relative; z-index:1; }

/* ===== RESPONSIVE ===== */
@media (max-width:1024px) {
  .mid-section { grid-template-columns:1fr; }
  .right-panel { display:grid; grid-template-columns:1fr 1fr; }
  .filter-form { display:grid; grid-template-columns:1fr 1fr; }
  .filter-btns { grid-template-columns:1fr 1fr; }
}
@media (max-width:900px) {
  .navbar { padding:0 18px; height:56px; }
  .nav-center { display:none; }
  .hamburger { display:flex; }
  .mobile-menu { top:56px; }
  main { padding:20px 14px 50px; }
  .stats-row { grid-template-columns:repeat(3,1fr); }
}
@media (max-width:640px) {
  .nav-clock,.nav-sub { display:none; }
  main { padding:16px 12px 50px; }
  .stats-row { grid-template-columns:1fr 1fr; }
  .stat-val { font-size:20px; }
  .mid-section { grid-template-columns:1fr; }
  .right-panel { display:flex; flex-direction:column; }
  .filter-form { display:flex; flex-direction:column; }
  .action-grid { grid-template-columns:1fr; }
  .page-header { flex-direction:column; }
  .table-header { flex-direction:column; align-items:flex-start; }
  .modal-box { padding:24px 18px; }
}
@media (max-width:380px) {
  .stats-row { grid-template-columns:1fr 1fr; }
}
</style>
</head>
<body>

<!-- LEFT SIDE DECO -->
<div class="side-deco side-left">
  <div class="side-grid"></div>
  <div class="side-orb" style="width:180px;height:180px;background:rgba(59,130,246,0.07);top:18%;left:-40px;animation-duration:9s;"></div>
  <div class="side-orb" style="width:110px;height:110px;background:rgba(16,185,129,0.05);top:62%;left:8px;animation-duration:13s;animation-delay:-5s;"></div>
  <div class="ring" style="width:55px;height:55px;left:18%;bottom:-55px;animation-duration:15s;"></div>
  <div class="ring" style="width:38px;height:38px;left:58%;bottom:-38px;animation-duration:19s;animation-delay:-6s;border-color:rgba(16,185,129,0.2)"></div>
  <div class="ring" style="width:72px;height:72px;left:32%;bottom:-72px;animation-duration:22s;animation-delay:-11s;border-color:rgba(59,130,246,0.1)"></div>
  <div class="fdot" style="width:5px;height:5px;top:22%;left:22%;animation-duration:3.2s;"></div>
  <div class="fdot" style="width:4px;height:4px;top:44%;left:68%;animation-duration:4.2s;animation-delay:-1s;background:rgba(16,185,129,0.5)"></div>
  <div class="fdot" style="width:6px;height:6px;top:68%;left:38%;animation-duration:3.8s;animation-delay:-2s;"></div>
  <div class="fdot" style="width:3px;height:3px;top:82%;left:58%;animation-duration:5.5s;animation-delay:-3s;background:rgba(168,85,247,0.5)"></div>
  <div class="scan-line" style="left:42%;animation-delay:-1s;"></div>
  <div class="corner corner-tl"></div>
  <div class="corner corner-br"></div>
  <div class="side-label">Rekap Absensi • SMKN 2 Yogyakarta</div>
</div>

<!-- RIGHT SIDE DECO -->
<div class="side-deco side-right">
  <div class="side-grid"></div>
  <div class="side-orb" style="width:150px;height:150px;background:rgba(16,185,129,0.06);top:28%;right:-25px;animation-duration:11s;animation-delay:-4s;"></div>
  <div class="side-orb" style="width:100px;height:100px;background:rgba(59,130,246,0.05);top:64%;right:15px;animation-duration:15s;"></div>
  <div class="data-stream">
    <div class="stream-p" style="animation-duration:2.6s;"></div>
    <div class="stream-p" style="animation-duration:3.4s;animation-delay:-1.1s;background:#10b981"></div>
    <div class="stream-p" style="animation-duration:4.2s;animation-delay:-2.3s;background:#a855f7"></div>
  </div>
  <div class="ring" style="width:48px;height:48px;left:22%;bottom:-48px;animation-duration:16s;animation-delay:-4s;border-color:rgba(16,185,129,0.2)"></div>
  <div class="ring" style="width:65px;height:65px;left:52%;bottom:-65px;animation-duration:20s;animation-delay:-9s;"></div>
  <div class="fdot" style="width:5px;height:5px;top:28%;left:28%;animation-duration:3.8s;animation-delay:-0.5s;background:rgba(16,185,129,0.5)"></div>
  <div class="fdot" style="width:4px;height:4px;top:52%;left:62%;animation-duration:4.8s;animation-delay:-1.8s;"></div>
  <div class="fdot" style="width:6px;height:6px;top:72%;left:42%;animation-duration:3.2s;animation-delay:-2.8s;background:rgba(168,85,247,0.4)"></div>
  <div class="scan-line" style="left:58%;animation-delay:-2.5s;animation-duration:5.5s;"></div>
  <div class="side-status">
    <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
      <div class="ss-dot" style="background:#10b981;box-shadow:0 0 0 0 rgba(16,185,129,0.6);color:rgba(16,185,129,0.6);"></div>
      <div class="ss-label">Live</div>
    </div>
    <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
      <div class="ss-dot" style="background:#3b82f6;color:rgba(59,130,246,0.6);animation-delay:-.7s;"></div>
      <div class="ss-label">Data</div>
    </div>
    <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
      <div class="ss-dot" style="background:#a855f7;color:rgba(168,85,247,0.6);animation-delay:-1.4s;"></div>
      <div class="ss-label">Sync</div>
    </div>
  </div>
  <div style="position:absolute;top:72px;right:12px;width:28px;height:28px;border-top:1px solid rgba(59,130,246,0.28);border-right:1px solid rgba(59,130,246,0.28);"></div>
  <div style="position:absolute;bottom:55px;left:12px;width:28px;height:28px;border-bottom:1px solid rgba(59,130,246,0.28);border-left:1px solid rgba(59,130,246,0.28);"></div>
  <div class="side-label">Visualisasi Kehadiran • Real-Time</div>
</div>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
  <div class="nav-brand">
    <div class="nav-logo"><img src="Gambar1.png" alt="" onerror="this.style.display='none'"></div>
    <div>
      <div class="nav-title">Rekap Absensi</div>
      <span class="nav-sub">SMKN 2 Yogyakarta</span>
    </div>
  </div>
  <div class="nav-center">
    <a href="index.php"          class="nav-link">🏠 Home</a>
    <a href="dashboard.php"      class="nav-link">👥 Data Siswa</a>
    <a href="rekap_absensi.php"  class="nav-link active">📊 Rekap</a>
  </div>
  <div class="nav-right">
    <div class="nav-clock" id="clock">—</div>
    <a href="index.php" class="btn-nav btn-nav-home">← Kembali</a>
    <button class="hamburger" id="hamburger" onclick="toggleMenu()" aria-label="Menu">
      <span></span>
      <span></span>
      <span></span>
    </button>
  </div>
</nav>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
  <a href="index.php"         class="nav-link">🏠 Home</a>
  <a href="dashboard.php"     class="nav-link">👥 Data Siswa</a>
  <a href="rekap_absensi.php" class="nav-link active">📊 Rekap</a>
  <div class="mobile-menu-divider"></div>
  <a href="index.php"         class="nav-link">← Kembali ke Beranda</a>
</div>

<main>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <h1>Rekap Absensi</h1>
      <p>Data kehadiran dan kedisiplinan seluruh siswa</p>
    </div>
  </div>

  <!-- STAT CARDS -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-top" style="background:linear-gradient(90deg,var(--primary),#60a5fa)"></div>
      <span class="stat-icon">📋</span>
      <div class="stat-label">Total Record</div>
      <div class="stat-val"><?= $total_absen ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-top" style="background:linear-gradient(90deg,var(--green),#34d399)"></div>
      <span class="stat-icon">✅</span>
      <div class="stat-label">Tepat Waktu</div>
      <div class="stat-val" style="color:var(--green)"><?= $jml_tepat ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-top" style="background:linear-gradient(90deg,var(--orange),#fcd34d)"></div>
      <span class="stat-icon">⏰</span>
      <div class="stat-label">Terlambat</div>
      <div class="stat-val" style="color:var(--orange)"><?= $jml_telat ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-top" style="background:linear-gradient(90deg,var(--sky),#7dd3fc)"></div>
      <span class="stat-icon">🏥</span>
      <div class="stat-label">Sakit / Izin</div>
      <div class="stat-val"><?= $jml_sakit ?><span class="sub" style="color:#38bdf8"> / <?= $jml_izin ?></span></div>
    </div>
    <div class="stat-card">
      <div class="stat-top" style="background:linear-gradient(90deg,var(--red),#fca5a5)"></div>
      <span class="stat-icon">⚠️</span>
      <div class="stat-label">Alfa</div>
      <div class="stat-val" style="color:var(--red)"><?= $jml_alfa ?></div>
    </div>
  </div>

  <!-- MIDDLE: Chart + Filter + Action -->
  <div class="mid-section">

    <div class="chart-card">
      <div class="section-title">
        <div class="dot" style="background:var(--primary)"></div>
        Visualisasi Kedisiplinan
      </div>
      <div class="chart-inner">
        <canvas id="disiplinChart"></canvas>
      </div>
    </div>

    <div class="right-panel">

      <div class="filter-card">
        <div class="section-title" style="margin-bottom:14px">
          <div class="dot" style="background:var(--sky)"></div>
          Filter Data
        </div>
        <form method="GET" class="filter-form">
          <div>
            <div style="font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;">Tanggal</div>
            <input type="date" name="tanggal" class="filter-input" value="<?= $_GET['tanggal'] ?? '' ?>">
          </div>
          <div>
            <div style="font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;">Cari NIS</div>
            <input type="text" name="search_nis" class="filter-input" placeholder="Ketik NIS siswa…" value="<?= $_GET['search_nis'] ?? '' ?>">
          </div>
          <div class="filter-btns">
            <button type="submit" class="btn btn-primary">🔍 Filter</button>
            <a href="rekap_absensi.php" class="btn btn-ghost">✕ Reset</a>
          </div>
        </form>
      </div>

      <div class="action-card">
        <div class="section-title" style="margin-bottom:14px">
          <div class="dot" style="background:var(--orange)"></div>
          Aksi Cepat
        </div>
        <div class="action-grid">
          <button onclick="openModal()" class="btn btn-info">📝 Input Sakit/Izin</button>
          <a href="?proses_alfa=1&tanggal=<?= $_GET['tanggal']??'' ?>&search_nis=<?= $_GET['search_nis']??'' ?>"
             class="btn btn-warning"
             onclick="return confirm('Proses ALFA siswa yang belum absen hari ini?')">❌ Generate Alfa</a>
          <a href="?export=1&tanggal=<?= $_GET['tanggal']??'' ?>&search_nis=<?= $_GET['search_nis']??'' ?>"
             class="btn btn-success">📄 Export Excel</a>
          <a href="?hapus_semua=1"
             class="btn btn-danger"
             onclick="return confirm('Kosongkan semua data absensi? Tidak bisa dibatalkan!')">🗑 Kosongkan</a>
        </div>
      </div>

    </div>
  </div>

  <!-- TABLE -->
  <div class="table-card">
    <div class="table-header">
      <h3>Data Absensi <?= !empty($_GET['tanggal']) ? '— '.date('d M Y', strtotime($_GET['tanggal'])) : 'Seluruh Periode' ?></h3>
      <span class="row-count"><?= mysqli_num_rows($query) ?> baris data</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Waktu</th>
            <th>NIS</th>
            <th>Nama Siswa</th>
            <th>Kelas</th>
            <th>Keterangan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if(mysqli_num_rows($query) > 0):
          while($data = mysqli_fetch_array($query)): ?>
          <tr>
            <td class="date-cell"><?= $data['tanggal'] ?></td>
            <td class="time-mono"><?= $data['waktu']=='00:00:00' ? '—' : $data['waktu'] ?></td>
            <td style="font-family:monospace;font-size:12px;color:#64748b"><?= $data['nis'] ?></td>
            <td>
              <div class="name-bold"><?= $data['nama'] ?></div>
              <?php if($data['total_telat_bulan_ini'] > 0): ?>
                <div class="telat-tag">⚠️ Telat <?= $data['total_telat_bulan_ini'] ?>× bulan ini</div>
              <?php endif; ?>
            </td>
            <td><?= $data['kelas'] ?></td>
            <td>
              <?php
                $k   = $data['keterangan'];
                $map = ['Tepat Waktu'=>['b-hadir','Tepat Waktu'],'Terlambat'=>['b-telat','Terlambat'],'Sakit'=>['b-sakit','Sakit'],'Izin'=>['b-izin','Izin'],'Alfa'=>['b-alfa','Alfa']];
                [$cls,$lbl] = $map[$k] ?? ['b-alfa',$k];
                echo "<span class='badge $cls'>$lbl</span>";
              ?>
            </td>
            <td>
              <a href="?hapus=<?= $data['id'] ?>&tanggal=<?= $_GET['tanggal']??'' ?>&search_nis=<?= $_GET['search_nis']??'' ?>"
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('Hapus riwayat ini?')">🗑 Hapus</a>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="7" class="empty-td">
            <span>📭</span>
            Belum ada data absensi<?= !empty($_GET['tanggal']) ? ' untuk tanggal ini' : '' ?>.
          </td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<footer>&copy; <?= date('Y') ?> SMK Negeri 2 Yogyakarta. Semua Hak Cipta Dilindungi.</footer>

<!-- Modal Input Sakit/Izin -->
<div class="modal-overlay" id="izinModal">
  <div class="modal-box">
    <div class="modal-top"></div>
    <div class="modal-head">
      <h2>📝 Input Sakit / Izin</h2>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <form method="POST">
      <div class="form-group">
        <label>NIS Siswa</label>
        <input list="data_siswa" name="nis" class="form-control" placeholder="Ketik NIS atau nama…" required autocomplete="off">
        <datalist id="data_siswa">
          <?php
          $qs = mysqli_query($conn, "SELECT nis,nama,kelas FROM users ORDER BY nis ASC");
          while($s = mysqli_fetch_array($qs))
            echo "<option value='{$s['nis']}'>{$s['nama']} ({$s['kelas']})</option>";
          ?>
        </datalist>
      </div>
      <div class="form-group">
        <label>Tanggal</label>
        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="form-group">
        <label>Keterangan</label>
        <select name="keterangan" class="form-control" required>
          <option value="Sakit">🤒 Sakit</option>
          <option value="Izin">✉️ Izin</option>
        </select>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-cancel-modal" onclick="closeModal()">Batal</button>
        <button type="submit" name="simpan_izin" class="btn btn-primary">Simpan Data</button>
      </div>
    </form>
  </div>
</div>

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

// Hamburger menu
function toggleMenu() {
  const btn  = document.getElementById('hamburger');
  const menu = document.getElementById('mobileMenu');
  btn.classList.toggle('open');
  menu.classList.toggle('open');
}

// Tutup menu kalau klik di luar
document.addEventListener('click', function(e) {
  const btn  = document.getElementById('hamburger');
  const menu = document.getElementById('mobileMenu');
  if (!btn.contains(e.target) && !menu.contains(e.target)) {
    btn.classList.remove('open');
    menu.classList.remove('open');
  }
});

// Modal
const modal = document.getElementById('izinModal');
function openModal() { modal.classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeModal() { modal.classList.remove('open'); document.body.style.overflow = ''; }
modal.addEventListener('click', e => { if(e.target === modal) closeModal(); });
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeModal(); });

// Toast
let tt;
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.classList.add('show');
  clearTimeout(tt); tt = setTimeout(() => t.classList.remove('show'), 2800);
}

// Chart
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('disiplinChart').getContext('2d');
  const vals = [<?=$jml_tepat?>,<?=$jml_telat?>,<?=$jml_sakit?>,<?=$jml_izin?>,<?=$jml_alfa?>];
  const tot  = vals.reduce((a,b) => a+b, 0);
  const data   = tot === 0 ? [1] : vals;
  const colors = tot === 0 ? ['rgba(255,255,255,0.06)'] : ['#10b981','#ef4444','#f59e0b','#0ea5e9','#991b1b'];
  const labels = tot === 0 ? ['Belum ada data'] : ['Tepat Waktu','Terlambat','Sakit','Izin','Alfa'];

  new Chart(ctx, {
    type: 'doughnut',
    data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 0, hoverOffset: 8 }] },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '68%',
      plugins: {
        legend: { position:'right', labels:{ color:'#94a3b8', boxWidth:11, font:{size:12}, padding:14 } },
        tooltip: { enabled: tot !== 0 }
      }
    }
  });
});
</script>
</body>
</html>
