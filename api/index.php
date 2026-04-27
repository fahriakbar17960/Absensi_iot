<?php
session_start();
include "koneksi.php";

$admins = [
    "fahri.akbar17960@smk.belajar.id" => "admin123",
    "admin123" => "admin123",
];

$pesan_error = "";
$show_modal = false;

if(isset($_POST['login'])){
    $identifier = $_POST['identifier'];
    $password = $_POST['password'];
    if(array_key_exists($identifier, $admins) && $admins[$identifier] === $password){
        $_SESSION['admin'] = $identifier;
        header("Location: dashboard.php");
        exit;
    } else {
        $pesan_error = "Email/Username atau Password tidak valid!";
        $show_modal = true;
    }
}

if(isset($_GET['logout'])){
    session_destroy();
    header("Location: index.php");
    exit;
}

$tgl_hari_ini = date("Y-m-d");

$query_live = mysqli_query($koneksi, "
    SELECT a.waktu, u.nama, u.kelas, a.keterangan 
    FROM absensi a 
    JOIN users u ON a.id_finger = u.finger_id 
    WHERE a.tanggal = '$tgl_hari_ini' 
    ORDER BY a.waktu DESC LIMIT 5
");

$query_absen = mysqli_query($koneksi, "
    SELECT u.nama, u.kelas 
    FROM users u 
    WHERE u.finger_id NOT IN (
        SELECT id_finger FROM absensi WHERE tanggal = '$tgl_hari_ini'
    )
    ORDER BY u.kelas ASC, u.nama ASC LIMIT 5
");

$statistik = mysqli_query($koneksi,"
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN keterangan='Tepat Waktu' THEN 1 ELSE 0 END) as tepat,
        SUM(CASE WHEN keterangan='Terlambat' THEN 1 ELSE 0 END) as terlambat,
        SUM(CASE WHEN keterangan='Sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN keterangan='Izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN keterangan='Alfa' THEN 1 ELSE 0 END) as alfa
    FROM absensi 
    WHERE tanggal = '$tgl_hari_ini'
");

$stat = mysqli_fetch_array($statistik);
$total_absen = $stat['total'] ?? 0;
$jml_tepat   = $stat['tepat'] ?? 0;
$jml_telat   = $stat['terlambat'] ?? 0;
$jml_sakit   = $stat['sakit'] ?? 0;
$jml_izin    = $stat['izin'] ?? 0;
$jml_alfa    = $stat['alfa'] ?? 0;

// Hitung total siswa untuk persentase
$total_siswa_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users");
$total_siswa_row = mysqli_fetch_array($total_siswa_q);
$total_siswa = $total_siswa_row['total'] ?? 0;
$persen_hadir = $total_siswa > 0 ? round(($jml_tepat / $total_siswa) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="60">
    <title>Sistem Absensi Fingerprint</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --green: #10b981;
            --red: #ef4444;
            --sky: #0ea5e9;
            --orange: #f59e0b;
            --purple: #a855f7;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            background: linear-gradient(rgba(15,23,42,0.78), rgba(15,23,42,0.88)), url('sekolah.jpg');
            background-size: cover; background-position: center; background-attachment: fixed;
            min-height: 100vh; display: flex; flex-direction: column; color: #ffffff;
        }

        /* ===== NAVBAR ===== */
        .header {
            background: rgba(15,23,42,0.5);
            backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 48px; position: fixed; top: 0; width: 100%; z-index: 1000;
            transition: background 0.3s;
        }
        .header.scrolled { background: rgba(10,18,35,0.92); }

        .logo-area { display: flex; align-items: center; gap: 12px; }
        .logo-area img { width: 38px; height: 38px; border-radius: 8px; }
        .logo { color: #fff; font-size: 18px; font-weight: 700; }
        .logo-sub { font-size: 10px; color: #64748b; font-weight: 400; display: block; }

        .btn-nav-group { display: flex; gap: 10px; align-items: center; }
        .login-btn {
            background: var(--primary); color: white; padding: 9px 22px;
            border-radius: 50px; text-decoration: none; font-weight: 500;
            font-size: 13px; transition: all 0.25s; border: none; cursor: pointer;
            display: inline-flex; align-items: center; gap: 7px;
        }
        .login-btn:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 6px 16px rgba(59,130,246,0.45); }
        .logout-btn { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.35); color: #f87171; }
        .logout-btn:hover { background: rgba(239,68,68,0.3); box-shadow: none; }
        .dashboard-btn { background: var(--green); }
        .dashboard-btn:hover { background: #059669; box-shadow: 0 6px 16px rgba(16,185,129,0.4); }

        /* ===== MAIN ===== */
        .main-container {
            flex: 1; padding: 100px 20px 60px;
            display: flex; flex-direction: column; align-items: center;
        }

        /* ===== WELCOME BOX ===== */
        .welcome-box {
            background: rgba(30,41,59,0.82);
            backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
            border: 1px solid rgba(255,255,255,0.09);
            padding: 40px; border-radius: 24px;
            width: 100%; max-width: 950px; text-align: center;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
            position: relative;
        }

        .status-badge {
            position: absolute; top: -14px; right: 28px;
            background: var(--green); color: white; padding: 5px 14px;
            border-radius: 20px; font-size: 11px; font-weight: 600;
            display: flex; align-items: center; gap: 6px;
            box-shadow: 0 4px 12px rgba(16,185,129,0.4);
        }
        .status-dot { width: 7px; height: 7px; background: white; border-radius: 50%; animation: pulse 1.5s infinite; }

        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 rgba(255,255,255,0.7); }
            70%  { box-shadow: 0 0 0 6px rgba(255,255,255,0); }
            100% { box-shadow: 0 0 0 0 rgba(255,255,255,0); }
        }

        .icon-fingerprint {
            width: 70px; height: 70px; fill: var(--primary);
            margin-bottom: 14px; background: rgba(59,130,246,0.12);
            padding: 15px; border-radius: 50%; animation: floatingGlow 3s ease-in-out infinite;
        }
        @keyframes floatingGlow {
            0%,100% { transform: translateY(0); box-shadow: 0 0 0 rgba(59,130,246,0); }
            50%      { transform: translateY(-8px); box-shadow: 0 15px 28px rgba(59,130,246,0.35); }
        }

        .clock-display { color: var(--primary); font-size: 40px; font-weight: 700; line-height: 1; font-variant-numeric: tabular-nums; }
        .date-display  { color: #94a3b8; font-size: 13px; margin-bottom: 18px; font-weight: 400; margin-top: 4px; }
        .welcome-box h1 { color: #fff; font-size: 26px; font-weight: 700; margin-bottom: 22px; }

        .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; text-align: left; }
        .highlight-container { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
        .highlight-badge {
            background: rgba(59,130,246,0.18); color: #60a5fa;
            border: 1px solid rgba(59,130,246,0.28);
            padding: 5px 13px; border-radius: 50px; font-size: 11px; font-weight: 600;
        }
        .desc-text { color: #cbd5e1; line-height: 1.65; font-size: 13.5px; margin-bottom: 16px; }

        .info-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px; padding: 18px; height: 100%;
        }
        .info-box h4 { color: #fff; font-size: 13px; margin-bottom: 11px; display: flex; align-items: center; gap: 7px; }
        .info-box h4 svg { width: 16px; height: 16px; fill: var(--primary); }
        .instruction-list { list-style: none; }
        .instruction-list li {
            font-size: 12.5px; color: #94a3b8; margin-bottom: 9px;
            display: flex; align-items: flex-start; gap: 9px; line-height: 1.5;
        }
        .instruction-list li::before { content: "✓"; color: var(--green); font-weight: 700; font-size: 13px; }

        /* ===== ATTENDANCE RATE BAR (NEW) ===== */
        .rate-bar-wrap {
            width: 100%; max-width: 950px; margin-top: 20px;
            background: rgba(30,41,59,0.7);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px; padding: 18px 24px;
            display: flex; align-items: center; gap: 20px;
        }
        .rate-label { font-size: 12px; color: #94a3b8; white-space: nowrap; }
        .rate-label strong { display: block; font-size: 22px; font-weight: 700; color: var(--green); line-height: 1; }
        .rate-track {
            flex: 1; height: 8px; background: rgba(255,255,255,0.08);
            border-radius: 10px; overflow: hidden;
        }
        .rate-fill {
            height: 100%; border-radius: 10px;
            background: linear-gradient(90deg, var(--green), #34d399);
            width: 0; transition: width 1.4s cubic-bezier(0.4,0,0.2,1);
        }
        .rate-segments { display: flex; gap: 10px; flex-wrap: wrap; }
        .rate-seg { display: flex; align-items: center; gap: 5px; font-size: 11px; color: #94a3b8; }
        .rate-dot { width: 8px; height: 8px; border-radius: 50%; }

        /* ===== STATS ===== */
        .stats-grid {
            display: grid; grid-template-columns: repeat(4,1fr); gap: 14px;
            width: 100%; max-width: 950px; margin-top: 14px;
        }
        .stat-card {
            background: rgba(30,41,59,0.65);
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 14px; padding: 18px 16px;
            position: relative; overflow: hidden; cursor: default;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,0.3); }
        .stat-card::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 3px; }
        .card-total::before { background: var(--primary); }
        .card-hadir::before { background: var(--green); }
        .card-izin::before  { background: var(--sky); }
        .card-alfa::before  { background: var(--red); }
        .stat-card h3 { font-size: 11px; color: #94a3b8; font-weight: 500; margin-bottom: 7px; text-transform: uppercase; letter-spacing: 0.4px; }
        .stat-card h2 { font-size: 24px; font-weight: 700; color: #fff; margin: 0; }
        .stat-card .stat-icon { position: absolute; right: 14px; top: 14px; font-size: 20px; opacity: 0.35; }

        /* Ripple */
        .stat-card { position: relative; overflow: hidden; }
        .ripple {
            position: absolute; border-radius: 50%;
            background: rgba(255,255,255,0.15);
            transform: scale(0); animation: rippleAnim 0.55s linear;
            pointer-events: none;
        }
        @keyframes rippleAnim { to { transform: scale(4); opacity: 0; } }

        /* ===== PANELS ===== */
        .dashboard-panels {
            display: grid; grid-template-columns: 1.2fr 1fr; gap: 18px;
            width: 100%; max-width: 950px; margin-top: 14px; text-align: left;
        }
        .panel-box {
            background: rgba(30,41,59,0.65);
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 18px; padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .panel-head {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 14px; padding-bottom: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .panel-head h3 { color: #fff; font-size: 14px; font-weight: 600; }
        .live-indicator {
            font-size: 10px; background: rgba(239,68,68,0.18); color: #f87171;
            padding: 3px 9px; border-radius: 10px; display: flex; align-items: center; gap: 5px;
        }
        .live-dot { width: 5px; height: 5px; background: var(--red); border-radius: 50%; animation: pulse 1s infinite; }

        .table-dark { width: 100%; border-collapse: collapse; }
        .table-dark th { color: #64748b; font-size: 10px; font-weight: 600; padding: 7px 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .table-dark td { color: #cbd5e1; font-size: 12.5px; padding: 10px 8px; border-top: 1px solid rgba(255,255,255,0.04); vertical-align: middle; }
        .table-dark tr:hover td { background: rgba(255,255,255,0.025); }
        .text-bold { color: #fff; font-weight: 500; }

        .badge-tepat { background: rgba(16,185,129,0.18); color: #34d399; padding: 3px 8px; border-radius: 5px; font-size: 10.5px; font-weight: 600; }
        .badge-telat { background: rgba(245,158,11,0.18); color: #fbbf24; padding: 3px 8px; border-radius: 5px; font-size: 10.5px; font-weight: 600; }
        .badge-sakit { background: rgba(56,189,248,0.18); color: #38bdf8; padding: 3px 8px; border-radius: 5px; font-size: 10.5px; font-weight: 600; }
        .badge-izin  { background: rgba(168,85,247,0.18); color: #c084fc; padding: 3px 8px; border-radius: 5px; font-size: 10.5px; font-weight: 600; }
        .badge-alfa  { background: rgba(239,68,68,0.18); color: #f87171; padding: 3px 8px; border-radius: 5px; font-size: 10.5px; font-weight: 600; }

        .empty-state { text-align: center; color: #475569; font-size: 12.5px; padding: 24px 0; }

        /* PANEL TABS (mobile) */
        .panel-tabs { display: none; margin-bottom: 10px; }
        .tab-btn {
            flex: 1; padding: 8px; border: none; border-radius: 8px;
            font-family: inherit; font-size: 12px; font-weight: 600; cursor: pointer;
            background: rgba(255,255,255,0.05); color: #94a3b8; transition: all 0.2s;
        }
        .tab-btn.active { background: var(--primary); color: #fff; }

        /* ===== MINI CHART (NEW) ===== */
        .chart-section {
            width: 100%; max-width: 950px; margin-top: 14px;
            background: rgba(30,41,59,0.65);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 18px; padding: 20px 24px;
        }
        .chart-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .chart-header h3 { color: #fff; font-size: 14px; font-weight: 600; }
        .chart-meta { font-size: 11px; color: #64748b; }
        .bar-chart { display: flex; align-items: flex-end; gap: 12px; height: 80px; }
        .bar-item { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .bar-fill {
            width: 100%; border-radius: 5px 5px 0 0;
            transition: height 1.2s cubic-bezier(0.4,0,0.2,1);
            min-height: 3px; position: relative; cursor: default;
        }
        .bar-fill:hover { filter: brightness(1.2); }
        .bar-fill .bar-tip {
            position: absolute; top: -22px; left: 50%; transform: translateX(-50%);
            font-size: 10px; font-weight: 600; color: #fff;
            background: rgba(0,0,0,0.5); padding: 2px 6px; border-radius: 4px;
            white-space: nowrap; opacity: 0; transition: opacity 0.2s;
        }
        .bar-fill:hover .bar-tip { opacity: 1; }
        .bar-label { font-size: 10px; color: #64748b; text-align: center; }
        .bar-val   { font-size: 11px; font-weight: 600; color: #94a3b8; }

        /* ===== TICKER (NEW) ===== */
        .ticker-wrap {
            width: 100%; max-width: 950px; margin-top: 14px;
            background: rgba(15,23,42,0.6);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 10px; padding: 0; overflow: hidden;
            display: flex; align-items: stretch;
        }
        .ticker-label {
            background: var(--primary); color: #fff; font-size: 10px; font-weight: 700;
            padding: 0 14px; display: flex; align-items: center;
            white-space: nowrap; letter-spacing: 0.5px; flex-shrink: 0;
            border-radius: 10px 0 0 10px;
        }
        .ticker-track { overflow: hidden; flex: 1; height: 36px; display: flex; align-items: center; }
        .ticker-inner { display: flex; gap: 0; animation: ticker 18s linear infinite; white-space: nowrap; }
        .ticker-inner:hover { animation-play-state: paused; }
        .ticker-item { padding: 0 30px; font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 8px; }
        .ticker-item strong { color: #fff; }
        .ticker-sep { color: #334155; }
        @keyframes ticker { from { transform: translateX(0); } to { transform: translateX(-50%); } }

        /* ===== FEATURES ===== */
        .features {
            display: grid; grid-template-columns: repeat(3,1fr);
            gap: 14px; margin-top: 14px;
            width: 100%; max-width: 950px;
        }
        .feature-card {
            background: rgba(15,23,42,0.55);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.08);
            padding: 22px 18px; border-radius: 16px;
            display: flex; align-items: flex-start; gap: 14px;
            transition: transform 0.2s, border-color 0.2s, background 0.2s;
        }
        .feature-card:hover { transform: translateY(-4px); border-color: rgba(59,130,246,0.3); background: rgba(30,41,59,0.75); }
        .feat-icon { font-size: 26px; flex-shrink: 0; }
        .feature-card h3 { font-size: 13.5px; margin-bottom: 5px; color: #fff; }
        .feature-card p  { font-size: 12px; color: #94a3b8; line-height: 1.55; }

        /* ===== MODAL ===== */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.55); backdrop-filter: blur(6px);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; visibility: hidden; transition: all 0.28s ease; z-index: 2000;
            padding: 20px;
        }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .modal-box {
            background: #ffffff; width: 100%; max-width: 400px;
            padding: 34px 30px; border-radius: 20px; text-align: center;
            position: relative; transform: translateY(20px) scale(0.97);
            transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
            box-shadow: 0 20px 60px rgba(0,0,0,0.35); color: var(--text-dark);
        }
        .modal-overlay.active .modal-box { transform: translateY(0) scale(1); }
        .close-btn {
            position: absolute; top: 14px; right: 16px; font-size: 20px;
            color: #94a3b8; cursor: pointer; background: none; border: none;
            width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;
            border-radius: 6px; transition: all 0.18s;
        }
        .close-btn:hover { background: #f1f5f9; color: var(--red); }
        .modal-box h2 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .modal-box p.subtitle { color: var(--text-light); font-size: 13px; margin-bottom: 22px; }
        .input-group { margin-bottom: 14px; text-align: left; }
        .input-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px; color: #475569; }
        .input-group input {
            width: 100%; padding: 11px 14px; border: 1.5px solid #e2e8f0;
            border-radius: 10px; font-size: 14px; font-family: inherit;
            outline: none; transition: all 0.2s;
        }
        .input-group input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .btn-submit {
            width: 100%; padding: 12px; border: none; background: var(--primary);
            color: white; border-radius: 10px; cursor: pointer; font-weight: 600;
            font-size: 14px; margin-top: 8px; transition: all 0.2s; font-family: inherit;
        }
        .btn-submit:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .alert { background: #fef2f2; color: var(--red); border: 1px solid #fecaca; padding: 10px 14px; border-radius: 8px; font-size: 12.5px; margin-bottom: 14px; text-align: left; display: flex; align-items: center; gap: 8px; }
        .divider { display: flex; align-items: center; color: var(--text-light); font-size: 11px; margin: 18px 0; }
        .divider::before,.divider::after { content:''; flex:1; border-bottom:1px solid #e2e8f0; }
        .divider::before { margin-right:10px; } .divider::after { margin-left:10px; }
        .btn-google {
            width:100%; padding:10px; background:#fff; border:1.5px solid #e2e8f0;
            color:var(--text-dark); border-radius:10px; font-weight:500; font-size:13px;
            cursor:pointer; display:flex; align-items:center; justify-content:center; gap:9px;
            transition:all 0.2s; font-family:inherit;
        }
        .btn-google:hover { background:#f8fafc; border-color:#cbd5e1; }
        .btn-google img { width:17px; height:17px; }

        /* TOAST */
        .toast {
            position: fixed; bottom: 28px; left: 50%;
            transform: translateX(-50%) translateY(10px);
            background: rgba(15,23,42,0.95); color: #fff; border: 1px solid rgba(255,255,255,0.1);
            padding: 11px 20px; border-radius: 10px; font-size: 13px;
            z-index: 3000; opacity: 0; pointer-events: none;
            transition: all 0.3s; white-space: nowrap;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

        /* SCROLL TO TOP */
        .scroll-top {
            position: fixed; bottom: 24px; right: 20px; z-index: 500;
            width: 40px; height: 40px; border-radius: 10px;
            background: rgba(59,130,246,0.2); border: 1px solid rgba(59,130,246,0.35);
            color: var(--primary); font-size: 18px; cursor: pointer;
            display: none; align-items: center; justify-content: center;
            transition: all 0.2s; backdrop-filter: blur(10px);
        }
        .scroll-top.visible { display: flex; }
        .scroll-top:hover { background: rgba(59,130,246,0.35); }

        /* FAB mobile */
        .fab-login {
            display: none; position: fixed; bottom: 20px; right: 20px; z-index: 500;
            width: 52px; height: 52px; border-radius: 50%;
            background: var(--primary); color: #fff; border: none;
            font-size: 20px; cursor: pointer;
            box-shadow: 0 4px 18px rgba(59,130,246,0.45);
            align-items: center; justify-content: center; transition: all 0.2s;
        }
        .fab-login:hover { transform: scale(1.08); }

        /* FOOTER */
        .footer {
            text-align: center; padding: 18px 20px;
            color: #475569; font-size: 12px;
            border-top: 1px solid rgba(255,255,255,0.06);
            margin-top: auto;
        }

        /* ===== FADE-IN ON SCROLL ===== */
        .fade-in { opacity: 0; transform: translateY(22px); transition: opacity 0.55s ease, transform 0.55s ease; }
        .fade-in.visible { opacity: 1; transform: translateY(0); }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .header { padding: 12px 16px; }
            .logo-sub { display: none; }
            .login-btn span { display: none; }
            .main-container { padding: 80px 12px 60px; }
            .welcome-box { padding: 28px 18px; }
            .content-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stat-card h2 { font-size: 20px; }
            .rate-bar-wrap { flex-direction: column; align-items: flex-start; gap: 10px; }
            .rate-segments { flex-direction: row; }
            .dashboard-panels { grid-template-columns: 1fr; }
            .panel-tabs { display: flex; gap: 8px; }
            .panel-box.hidden-mobile { display: none; }
            .chart-section { padding: 16px; }
            .bar-chart { gap: 8px; }
            .features { grid-template-columns: 1fr; }
            .feature-card { padding: 16px 14px; }
            .fab-login { display: flex; }
            .scroll-top { bottom: 80px; }
            .ticker-item { padding: 0 20px; }
        }

        @media (max-width: 400px) {
            .clock-display { font-size: 32px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }

        /* ===== SIDE DECORATIONS ===== */
        .side-deco {
            position: fixed; top: 0; bottom: 0; width: calc((100vw - 990px) / 2);
            pointer-events: none; z-index: 0; overflow: hidden;
        }
        .side-left  { left: 0; }
        .side-right { right: 0; }

        /* Hide on narrow screens */
        @media (max-width: 1200px) { .side-deco { display: none; } }

        /* Floating rings (fingerprint-like circles) */
        .ring {
            position: absolute; border-radius: 50%;
            border: 1px solid rgba(59,130,246,0.18);
            animation: ringFloat linear infinite;
        }
        @keyframes ringFloat {
            0%   { transform: translateY(0) scale(1); opacity: 0; }
            15%  { opacity: 1; }
            85%  { opacity: 1; }
            100% { transform: translateY(-100vh) scale(1.1); opacity: 0; }
        }

        /* Vertical scan line */
        .scan-line {
            position: absolute; left: 50%; width: 1px;
            background: linear-gradient(to bottom, transparent, rgba(59,130,246,0.5), rgba(16,185,129,0.3), transparent);
            animation: scanMove 4s ease-in-out infinite;
            transform: translateX(-50%);
        }
        @keyframes scanMove {
            0%,100% { top: 10%; height: 25%; opacity: 0.4; }
            50%      { top: 60%; height: 30%; opacity: 0.9; }
        }

        /* Floating dots */
        .fdot {
            position: absolute; border-radius: 50%;
            background: rgba(59,130,246,0.35);
            animation: dotBlink ease-in-out infinite;
        }
        @keyframes dotBlink {
            0%,100% { opacity: 0.15; transform: scale(1); }
            50%      { opacity: 0.7; transform: scale(1.4); }
        }

        /* Vertical label */
        .side-label {
            position: absolute; bottom: 30%; left: 50%;
            transform: translateX(-50%) rotate(-90deg);
            font-size: 10px; font-weight: 700; letter-spacing: 3px;
            color: rgba(255,255,255,0.1); white-space: nowrap;
            text-transform: uppercase;
        }

        /* Grid lines */
        .side-grid {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(59,130,246,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59,130,246,0.04) 1px, transparent 1px);
            background-size: 30px 30px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 0%, transparent 100%);
        }

        /* Status pill */
        .side-status {
            position: absolute; top: 30%; left: 50%; transform: translateX(-50%);
            display: flex; flex-direction: column; align-items: center; gap: 12px;
        }
        .side-status-item {
            display: flex; flex-direction: column; align-items: center; gap: 4px;
        }
        .side-status-dot {
            width: 8px; height: 8px; border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }
        .side-status-label {
            font-size: 9px; font-weight: 600; letter-spacing: 1px;
            text-transform: uppercase; writing-mode: vertical-rl;
            color: rgba(255,255,255,0.25);
        }

        /* Glowing orb */
        .side-orb {
            position: absolute; border-radius: 50%;
            filter: blur(40px);
            animation: orbDrift ease-in-out infinite;
        }
        @keyframes orbDrift {
            0%,100% { transform: translateY(0) scale(1); }
            50%      { transform: translateY(-30px) scale(1.08); }
        }

        /* Corner accent */
        .corner-tl, .corner-br {
            position: absolute; width: 30px; height: 30px;
        }
        .corner-tl { top: 80px; left: 12px; border-top: 1px solid rgba(59,130,246,0.3); border-left: 1px solid rgba(59,130,246,0.3); }
        .corner-br { bottom: 60px; right: 12px; border-bottom: 1px solid rgba(59,130,246,0.3); border-right: 1px solid rgba(59,130,246,0.3); }

        /* Animated data stream (right side) */
        .data-stream {
            position: absolute; right: 18px; top: 80px; bottom: 60px;
            width: 1px; background: linear-gradient(to bottom, transparent, rgba(59,130,246,0.12), transparent);
        }
        .stream-particle {
            position: absolute; width: 3px; height: 3px; border-radius: 50%;
            background: var(--primary); right: -1px;
            animation: streamFall linear infinite;
        }
        @keyframes streamFall {
            from { top: 0; opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            to   { top: 100%; opacity: 0; }
        }
    </style>
</head>
<body>

    <!-- ===== LEFT SIDE DECORATION ===== -->
    <div class="side-deco side-left">
        <div class="side-grid"></div>
        <div class="side-orb" style="width:180px;height:180px;background:rgba(59,130,246,0.07);top:20%;left:-40px;animation-duration:8s;"></div>
        <div class="side-orb" style="width:120px;height:120px;background:rgba(16,185,129,0.05);top:60%;left:10px;animation-duration:12s;animation-delay:-4s;"></div>
        <div class="ring" style="width:60px;height:60px;left:15%;bottom:-60px;animation-duration:14s;"></div>
        <div class="ring" style="width:40px;height:40px;left:55%;bottom:-60px;animation-duration:18s;animation-delay:-5s;border-color:rgba(16,185,129,0.2)"></div>
        <div class="ring" style="width:80px;height:80px;left:30%;bottom:-80px;animation-duration:20s;animation-delay:-10s;border-color:rgba(59,130,246,0.12)"></div>
        <div class="ring" style="width:30px;height:30px;left:70%;bottom:-30px;animation-duration:16s;animation-delay:-7s;border-color:rgba(168,85,247,0.2)"></div>
        <div class="fdot" style="width:5px;height:5px;top:25%;left:20%;animation-duration:3s;"></div>
        <div class="fdot" style="width:4px;height:4px;top:45%;left:70%;animation-duration:4s;animation-delay:-1s;background:rgba(16,185,129,0.5)"></div>
        <div class="fdot" style="width:6px;height:6px;top:65%;left:35%;animation-duration:3.5s;animation-delay:-2s;"></div>
        <div class="fdot" style="width:3px;height:3px;top:80%;left:60%;animation-duration:5s;animation-delay:-3s;background:rgba(168,85,247,0.5)"></div>
        <div class="scan-line" style="left:40%;animation-delay:-1s;"></div>
        <div class="corner-tl"></div>
        <div class="corner-br"></div>
        <div class="side-label">Sistem Absensi • SMKN 2 Yogyakarta</div>
    </div>

    <!-- ===== RIGHT SIDE DECORATION ===== -->
    <div class="side-deco side-right">
        <div class="side-grid"></div>
        <div class="side-orb" style="width:160px;height:160px;background:rgba(16,185,129,0.06);top:30%;right:-30px;animation-duration:10s;animation-delay:-3s;"></div>
        <div class="side-orb" style="width:100px;height:100px;background:rgba(59,130,246,0.06);top:65%;right:20px;animation-duration:14s;"></div>
        <div class="data-stream">
            <div class="stream-particle" style="animation-duration:2.5s;"></div>
            <div class="stream-particle" style="animation-duration:3.2s;animation-delay:-1s;background:#10b981"></div>
            <div class="stream-particle" style="animation-duration:4s;animation-delay:-2.2s;"></div>
        </div>
        <div class="ring" style="width:50px;height:50px;right:20%;bottom:-50px;animation-duration:15s;animation-delay:-3s;border-color:rgba(16,185,129,0.2)"></div>
        <div class="ring" style="width:70px;height:70px;right:50%;bottom:-70px;animation-duration:19s;animation-delay:-8s;"></div>
        <div class="ring" style="width:35px;height:35px;right:10%;bottom:-35px;animation-duration:12s;animation-delay:-4s;border-color:rgba(168,85,247,0.18)"></div>
        <div class="fdot" style="width:5px;height:5px;top:30%;left:25%;animation-duration:3.5s;animation-delay:-0.5s;background:rgba(16,185,129,0.5)"></div>
        <div class="fdot" style="width:4px;height:4px;top:50%;left:60%;animation-duration:4.5s;animation-delay:-1.5s;"></div>
        <div class="fdot" style="width:6px;height:6px;top:70%;left:40%;animation-duration:3s;animation-delay:-2.5s;background:rgba(168,85,247,0.4)"></div>
        <div class="fdot" style="width:3px;height:3px;top:85%;left:20%;animation-duration:5.5s;animation-delay:-3.5s;"></div>
        <div class="scan-line" style="left:60%;animation-delay:-2s;animation-duration:5s;"></div>
        <div class="side-status">
            <div class="side-status-item">
                <div class="side-status-dot" style="background:#10b981;box-shadow:0 0 6px rgba(16,185,129,0.6);"></div>
                <div class="side-status-label">Online</div>
            </div>
            <div class="side-status-item">
                <div class="side-status-dot" style="background:#3b82f6;box-shadow:0 0 6px rgba(59,130,246,0.6);animation-delay:-0.8s;"></div>
                <div class="side-status-label">IoT</div>
            </div>
            <div class="side-status-item">
                <div class="side-status-dot" style="background:#a855f7;box-shadow:0 0 6px rgba(168,85,247,0.6);animation-delay:-1.6s;"></div>
                <div class="side-status-label">Sync</div>
            </div>
        </div>
        <div style="position:absolute;top:80px;right:12px;width:30px;height:30px;border-top:1px solid rgba(59,130,246,0.3);border-right:1px solid rgba(59,130,246,0.3);"></div>
        <div style="position:absolute;bottom:60px;left:12px;width:30px;height:30px;border-bottom:1px solid rgba(59,130,246,0.3);border-left:1px solid rgba(59,130,246,0.3);"></div>
        <div class="side-label">IoT Fingerprint Hub • Real-Time</div>
    </div>

    <!-- NAVBAR -->
    <header class="header" id="navbar">
        <div class="logo-area">
            <img src="Gambar1.png" alt="Logo" onerror="this.style.display='none'">
            <div class="logo">
                Sistem Absensi
                <span class="logo-sub">SMKN 2 Yogyakarta</span>
            </div>
        </div>
        <div class="btn-nav-group">
            <?php if(!isset($_SESSION['admin'])): ?>
                <button class="login-btn" onclick="openModal()">🔐 <span>Login Admin</span></button>
            <?php else: ?>
                <a href="dashboard.php" class="login-btn dashboard-btn">📊 <span>Dashboard</span></a>
                <a href="index.php?logout=true" class="login-btn logout-btn">↩ <span>Logout</span></a>
            <?php endif; ?>
        </div>
    </header>

    <main class="main-container">

        <!-- WELCOME BOX (original, unchanged) -->
        <div class="welcome-box fade-in">
            <div class="status-badge">
                <div class="status-dot"></div>
                Sensor Aktif
            </div>

            <svg class="icon-fingerprint" viewBox="0 0 24 24">
                <path d="M17.81 4.47c-.08 0-.16-.02-.23-.06C15.66 3.42 14 3 12.01 3c-1.98 0-3.86.47-5.57 1.41-.24.13-.54.04-.68-.2-.13-.24-.04-.55.2-.68C7.82 2.52 9.86 2 12.01 2c2.13 0 3.99.47 6.03 1.52.25.13.34.43.21.67-.09.18-.26.28-.44.28zM3.5 9.72c-.1 0-.2-.03-.29-.09-.23-.16-.28-.47-.12-.7.99-1.4 2.25-2.5 3.75-3.27C9.98 4.04 10.99 3.9 12 3.9c1.02 0 2.03.14 3.16.42.27.07.43.34.36.61-.07.27-.34.43-.61.36-1.03-.26-1.95-.39-2.91-.39-2.6 0-5.18 1.15-6.9 3.19-.15.18-.42.22-.6.07zM7.5 21c-.13 0-.26-.05-.35-.15-.2-.2-.2-.51 0-.71l3.5-3.5c.2-.2.51-.2.71 0s.2.51 0 .71l-3.5 3.5c-.1.1-.23.15-.36.15z"/>
                <path d="M11 22c-.28 0-.5-.22-.5-.5v-4c0-.28.22-.5.5-.5s.5.22.5.5v4c0 .28-.22.5-.5.5zM12 16c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5c0 1.25-.45 2.4-1.2 3.3-.17.21-.48.24-.69.07-.21-.17-.24-.48-.07-.69C15.6 13.06 16 12.07 16 11c0-2.21-1.79-4-4-4s-4 1.79-4 4 1.79 4 4 4c.28 0 .5.22.5.5s-.22.5-.5.5z"/>
            </svg>

            <div class="clock-display" id="jam">00:00:00</div>
            <div class="date-display" id="tanggal">Memuat tanggal...</div>

            <h1>Fingerprint IoT Hub</h1>

            <div class="content-grid">
                <div class="left-col">
                    <div class="highlight-container">
                        <span class="highlight-badge">⚡ Instan</span>
                        <span class="highlight-badge">🎯 Akurat</span>
                        <span class="highlight-badge">🔒 Aman</span>
                    </div>
                    <p class="desc-text">Portal absensi cerdas berbasis Internet of Things. Sistem ini terintegrasi langsung dengan database sekolah untuk merekam kehadiran secara real-time dan mencegah kecurangan.</p>
                    <p class="desc-text">Gunakan perangkat pemindai yang tersedia di lobi sekolah. Pastikan koneksi WiFi aktif agar data Anda langsung sinkron dengan sistem manajemen absensi.</p>
                </div>
                <div class="right-col">
                    <div class="info-box">
                        <h4>
                            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                            Panduan Presensi
                        </h4>
                        <ul class="instruction-list">
                            <li>Pastikan jari bersih dan tidak basah.</li>
                            <li>Letakkan jari terdaftar di tengah sensor.</li>
                            <li>Tahan 1-2 detik hingga terdengar "Beep".</li>
                            <li>Pastikan nama muncul di layar alat.</li>
                            <li>Cek riwayat kehadiran di tabel Live Scan di bawah.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ATTENDANCE RATE BAR (NEW) -->
        <div class="rate-bar-wrap fade-in">
            <div class="rate-label">
                <strong><?= $persen_hadir ?>%</strong>
                Tingkat Kehadiran
            </div>
            <div style="flex:1;">
                <div style="display:flex; justify-content:space-between; font-size:10px; color:#64748b; margin-bottom:6px;">
                    <span>Hari ini</span>
                    <span><?= $jml_tepat ?> / <?= $total_siswa ?> siswa</span>
                </div>
                <div class="rate-track"><div class="rate-fill" id="rateFill" data-pct="<?= $persen_hadir ?>"></div></div>
            </div>
            <div class="rate-segments">
                <div class="rate-seg"><div class="rate-dot" style="background:#10b981"></div><?= $jml_tepat ?> Tepat</div>
                <div class="rate-seg"><div class="rate-dot" style="background:#f59e0b"></div><?= $jml_telat ?> Telat</div>
                <div class="rate-seg"><div class="rate-dot" style="background:#0ea5e9"></div><?= $jml_sakit ?> Sakit</div>
                <div class="rate-seg"><div class="rate-dot" style="background:#a855f7"></div><?= $jml_izin ?> Izin</div>
                <div class="rate-seg"><div class="rate-dot" style="background:#ef4444"></div><?= $jml_alfa ?> Alfa</div>
            </div>
        </div>

        <!-- STATS (original, with enhancements) -->
        <div class="stats-grid fade-in">
            <div class="stat-card card-total" onclick="showToast('📋 Total <?= $total_absen ?> record absensi tercatat hari ini')">
                <div class="stat-icon">📋</div>
                <h3>Total Record</h3>
                <h2 class="count-up" data-target="<?= $total_absen ?>"><?= $total_absen ?></h2>
            </div>
            <div class="stat-card card-hadir" onclick="showToast('✅ <?= $jml_tepat ?> tepat waktu · ⚠️ <?= $jml_telat ?> terlambat')">
                <div class="stat-icon">✅</div>
                <h3>Hadir / Telat</h3>
                <h2 class="count-up" data-target="<?= $jml_tepat ?>"><?= $jml_tepat ?></h2>
                <span style="font-size:13px;color:#ef4444;">/ <?= $jml_telat ?></span>
            </div>
            <div class="stat-card card-izin" onclick="showToast('🏥 <?= $jml_sakit ?> sakit · 📋 <?= $jml_izin ?> izin')">
                <div class="stat-icon">🏥</div>
                <h3>Sakit / Izin</h3>
                <h2><?= $jml_sakit ?> <span style="font-size:14px;color:#38bdf8;">/ <?= $jml_izin ?></span></h2>
            </div>
            <div class="stat-card card-alfa" onclick="showToast('⚠️ <?= $jml_alfa ?> siswa tidak hadir tanpa keterangan')">
                <div class="stat-icon">⚠️</div>
                <h3>Total Alfa</h3>
                <h2 class="count-up" style="color:#ef4444;" data-target="<?= $jml_alfa ?>"><?= $jml_alfa ?></h2>
            </div>
        </div>

        <!-- TICKER (NEW) -->
        <div class="ticker-wrap fade-in">
            <div class="ticker-label">🔴 LIVE</div>
            <div class="ticker-track">
                <div class="ticker-inner" id="tickerInner">
                    <?php
                    // Fetch ulang untuk ticker
                    $tk = mysqli_query($koneksi,"SELECT u.nama, u.kelas, a.keterangan, a.waktu FROM absensi a JOIN users u ON a.id_finger=u.finger_id WHERE a.tanggal='$tgl_hari_ini' ORDER BY a.waktu DESC LIMIT 8");
                    $ticker_items = "";
                    if(mysqli_num_rows($tk) > 0){
                        while($t=mysqli_fetch_array($tk)){
                            $ticker_items .= "<span class='ticker-item'><strong>{$t['nama']}</strong> ({$t['kelas']}) — {$t['keterangan']} <span class='ticker-sep'>•</span> {$t['waktu']}</span>";
                        }
                    } else {
                        $ticker_items = "<span class='ticker-item'>Belum ada aktivitas scan hari ini. Silakan gunakan perangkat fingerprint di lobi.</span>";
                    }
                    // Duplikasi untuk loop seamless
                    echo $ticker_items . $ticker_items;
                    ?>
                </div>
            </div>
        </div>

        <!-- PANELS (original + tab switching on mobile) -->
        <div style="width:100%;max-width:950px;margin-top:14px;">
            <div class="panel-tabs">
                <button class="tab-btn active" onclick="switchTab('live', this)">📡 Riwayat Scan</button>
                <button class="tab-btn" onclick="switchTab('absen', this)">👥 Belum Hadir</button>
            </div>
            <div class="dashboard-panels">
                <div class="panel-box fade-in" id="panel-live">
                    <div class="panel-head">
                        <h3>Riwayat Scan Terakhir</h3>
                        <span class="live-indicator"><div class="live-dot"></div> Live</span>
                    </div>
                    <table class="table-dark">
                        <thead><tr><th>Waktu</th><th>Nama Siswa</th><th>Kelas</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if(mysqli_num_rows($query_live) > 0):
                            while($row = mysqli_fetch_array($query_live)): ?>
                            <tr>
                                <td style="font-size:11px;color:#64748b"><?= $row['waktu'] ?></td>
                                <td class="text-bold"><?= $row['nama'] ?></td>
                                <td><?= $row['kelas'] ?></td>
                                <td><?php
                                    $k = $row['keterangan'];
                                    $map = ['Tepat Waktu'=>'badge-tepat','Terlambat'=>'badge-telat','Sakit'=>'badge-sakit','Izin'=>'badge-izin'];
                                    echo '<span class="'.($map[$k]??'badge-alfa').'">'.$k.'</span>';
                                ?></td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="4" class="empty-state">Belum ada data scan hari ini.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="panel-box fade-in hidden-mobile" id="panel-absen">
                    <div class="panel-head">
                        <h3>Monitor Belum Hadir</h3>
                        <span style="font-size:11px;color:#64748b"><?= date('d/m/Y') ?></span>
                    </div>
                    <table class="table-dark">
                        <thead><tr><th>Nama Siswa</th><th>Kelas</th></tr></thead>
                        <tbody>
                        <?php if(mysqli_num_rows($query_absen) > 0):
                            while($row2 = mysqli_fetch_array($query_absen)): ?>
                            <tr>
                                <td class="text-bold"><?= $row2['nama'] ?></td>
                                <td><?= $row2['kelas'] ?></td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="2" class="empty-state">Semua siswa sudah presensi. 🎉</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MINI BAR CHART (NEW) -->
        <div class="chart-section fade-in">
            <div class="chart-header">
                <h3>📊 Komposisi Kehadiran Hari Ini</h3>
                <span class="chart-meta"><?= date('d M Y') ?></span>
            </div>
            <?php
            $max = max($jml_tepat, $jml_telat, $jml_sakit, $jml_izin, $jml_alfa, 1);
            $bars = [
                ['label'=>'Tepat','val'=>$jml_tepat,'color'=>'#10b981'],
                ['label'=>'Terlambat','val'=>$jml_telat,'color'=>'#f59e0b'],
                ['label'=>'Sakit','val'=>$jml_sakit,'color'=>'#0ea5e9'],
                ['label'=>'Izin','val'=>$jml_izin,'color'=>'#a855f7'],
                ['label'=>'Alfa','val'=>$jml_alfa,'color'=>'#ef4444'],
            ];
            ?>
            <div class="bar-chart">
                <?php foreach($bars as $b):
                    $pct = $max > 0 ? round(($b['val']/$max)*72) : 3;
                ?>
                <div class="bar-item">
                    <div class="bar-val"><?= $b['val'] ?></div>
                    <div class="bar-fill" style="background:<?= $b['color'] ?>;height:<?= max($pct,3) ?>px"
                         data-h="<?= max($pct,3) ?>" data-init="0">
                        <div class="bar-tip"><?= $b['label'] ?>: <?= $b['val'] ?></div>
                    </div>
                    <div class="bar-label"><?= $b['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- FEATURES (original, grid layout) -->
        <div class="features fade-in">
            <div class="feature-card">
                <div class="feat-icon">⚡</div>
                <div><h3>Real-Time Data</h3><p>Data absensi masuk ke server hitungan detik setelah jari dipindai.</p></div>
            </div>
            <div class="feature-card">
                <div class="feat-icon">🎯</div>
                <div><h3>Akurasi Tinggi</h3><p>Mencegah titip absen menggunakan validasi biometrik sidik jari.</p></div>
            </div>
            <div class="feature-card">
                <div class="feat-icon">🌐</div>
                <div><h3>Terintegrasi IoT</h3><p>Mesin pemindai langsung terhubung dengan jaringan internet sekolah.</p></div>
            </div>
        </div>

    </main>

    <!-- MODAL (original + improved) -->
    <div class="modal-overlay <?= $show_modal ? 'active' : '' ?>" id="loginModal">
        <div class="modal-box">
            <button class="close-btn" onclick="closeModal()">✕</button>
            <h2>Akses Keamanan</h2>
            <p class="subtitle">Hanya administrator terdaftar yang dapat masuk.</p>
            <?php if($pesan_error): ?>
                <div class="alert">⚠️ <?= $pesan_error ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="input-group">
                    <label>Email atau Username</label>
                    <input type="text" name="identifier" placeholder="Masukkan Email atau Username" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                </div>
                <button type="submit" name="login" class="btn-submit">Login Sekarang →</button>
            </form>
            <div class="divider">ATAU</div>
            <button class="btn-google" onclick="showToast('🚧 Fitur Google OAuth sedang dikembangkan')">
                <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google">
                Lanjutkan dengan Google
            </button>
        </div>
    </div>

    <footer class="footer">
        &copy; <?= date("Y") ?> SMK Negeri 2 Yogyakarta. Semua Hak Cipta Dilindungi.
    </footer>

    <!-- FAB mobile -->
    <?php if(!isset($_SESSION['admin'])): ?>
    <button class="fab-login" onclick="openModal()">🔐</button>
    <?php endif; ?>

    <!-- Scroll top -->
    <button class="scroll-top" id="scrollTopBtn" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script>
    // ===== CLOCK =====
    function tick() {
        const n = new Date();
        document.getElementById('jam').textContent =
            n.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false}).replace(/\./g,':');
        document.getElementById('tanggal').textContent =
            n.toLocaleDateString('id-ID',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
    }
    setInterval(tick, 1000); tick();

    // ===== NAVBAR SCROLL =====
    window.addEventListener('scroll', () => {
        document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 30);
        document.getElementById('scrollTopBtn').classList.toggle('visible', window.scrollY > 300);
    });

    // ===== MODAL =====
    const modal = document.getElementById('loginModal');
    function openModal()  { modal.classList.add('active'); document.body.style.overflow='hidden'; }
    function closeModal() { modal.classList.remove('active'); document.body.style.overflow=''; }
    modal.addEventListener('click', e => { if(e.target===modal) closeModal(); });
    document.addEventListener('keydown', e => { if(e.key==='Escape') closeModal(); });

    // ===== TOAST =====
    let toastTimer;
    function showToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.remove('show'), 2800);
    }

    // ===== RIPPLE ON STAT CARDS =====
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('click', function(e) {
            const r = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            r.className = 'ripple';
            r.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px`;
            this.appendChild(r);
            setTimeout(() => r.remove(), 600);
        });
    });

    // ===== FADE IN ON SCROLL =====
    const obs = new IntersectionObserver(entries => {
        entries.forEach((e, i) => {
            if(e.isIntersecting) {
                setTimeout(() => e.target.classList.add('visible'), i * 80);
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.fade-in').forEach(el => obs.observe(el));

    // ===== COUNT UP =====
    const countObs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if(!e.isIntersecting) return;
            const el = e.target, target = +el.dataset.target;
            if(!target) { el.textContent='0'; return; }
            let start, dur=900;
            const run = ts => {
                if(!start) start=ts;
                const p = Math.min((ts-start)/dur, 1);
                el.textContent = Math.round(p*target);
                p < 1 ? requestAnimationFrame(run) : el.textContent=target;
            };
            requestAnimationFrame(run);
            countObs.unobserve(el);
        });
    }, {threshold:0.2});
    document.querySelectorAll('.count-up').forEach(c => countObs.observe(c));

    // ===== RATE BAR ANIMATE =====
    const barObs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if(!e.isIntersecting) return;
            const fill = e.target;
            setTimeout(() => fill.style.width = fill.dataset.pct + '%', 200);
            barObs.unobserve(fill);
        });
    }, {threshold:0.3});
    const rateFill = document.getElementById('rateFill');
    if(rateFill) barObs.observe(rateFill);

    // ===== BAR CHART ANIMATE =====
    const chartObs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if(!e.isIntersecting) return;
            e.target.querySelectorAll('.bar-fill').forEach((bar, i) => {
                bar.style.height = '3px';
                setTimeout(() => { bar.style.height = bar.dataset.h + 'px'; }, i * 80);
            });
            chartObs.unobserve(e.target);
        });
    }, {threshold:0.3});
    const chartSection = document.querySelector('.chart-section');
    if(chartSection) chartObs.observe(chartSection);

    // ===== MOBILE PANEL TABS =====
    function switchTab(tab, btn) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        if(tab === 'live') {
            document.getElementById('panel-live').classList.remove('hidden-mobile');
            document.getElementById('panel-absen').classList.add('hidden-mobile');
        } else {
            document.getElementById('panel-absen').classList.remove('hidden-mobile');
            document.getElementById('panel-live').classList.add('hidden-mobile');
        }
    }
    </script>

</body>
</html>