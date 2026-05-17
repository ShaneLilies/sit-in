<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php"); exit();
}
require_once '../db.php';
$stmt = $pdo->prepare("SELECT * FROM students WHERE id_number = ?");
$stmt->execute([$_SESSION['user']]);
$student = $stmt->fetch();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve'])) {
    $purpose  = trim($_POST['purpose'] ?? '');
    $lab      = trim($_POST['lab'] ?? '');
    $time_in  = trim($_POST['time_in'] ?? '');
    $date     = trim($_POST['date'] ?? '');
    $name     = $student['fname'].' '.$student['lname'];
    if (empty($purpose) || empty($lab) || empty($date)) {
        $error = "Please fill in all required fields.";
    } else {
        $pdo->prepare("INSERT INTO reservations (id_number, student_name, purpose, lab, reserved_date) VALUES (?,?,?,?,?)")
            ->execute([$_SESSION['user'], $name, $purpose, $lab, $date]);
        $success = "Reservation submitted successfully! Waiting for admin approval.";
    }
}

// Handle Disable/Enable
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    
    if ($action === 'disable') {
        $pdo->prepare("UPDATE reservations SET status='Disabled' WHERE id=? AND id_number=? AND status='Pending'")->execute([$id, $_SESSION['user']]);
        header("Location: reservation.php?msg=disabled"); exit();
    } elseif ($action === 'enable') {
        $pdo->prepare("UPDATE reservations SET status='Pending' WHERE id=? AND id_number=? AND status='Disabled'")->execute([$id, $_SESSION['user']]);
        header("Location: reservation.php?msg=enabled"); exit();
    }
}

// Handle messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'disabled') $success = "Reservation has been disabled.";
    if ($_GET['msg'] === 'enabled') $success = "Reservation has been enabled and is now Pending.";
}


// Get student's reservations
$myReservations = $pdo->prepare("SELECT * FROM reservations WHERE id_number = ? ORDER BY created_at DESC");
$myReservations->execute([$_SESSION['user']]);
$reservations = $myReservations->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Reservation</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{--purple:#5B2D8E;--pdark:#3D1A6E;--plight:#7B4BB8;--gold:#F0B429;--gdark:#C88F0A;--bg:#f5f0ff}
        body{font-family:'Inter',sans-serif;background:var(--bg);min-height:100vh}

        .navbar{background:var(--pdark);padding:0 32px;height:58px;display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid var(--gold);box-shadow:0 2px 12px rgba(0,0,0,0.25)}
        .navbar-brand{color:#fff;font-size:14px;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:10px}
        .navbar-brand img{width:36px;height:36px;object-fit:contain;border-radius:50%}
        .nav-links{display:flex;align-items:center;gap:2px;list-style:none}
        .nav-links a{color:rgba(255,255,255,0.9);text-decoration:none;font-size:13px;font-weight:500;padding:6px 13px;border-radius:4px;transition:background 0.2s}
        .nav-links a:hover,.nav-links a.active{background:rgba(255,255,255,0.18)}
        .btn-logout-nav{background:var(--gold)!important;color:var(--pdark)!important;font-weight:700!important;border-radius:5px!important}
        .btn-logout-nav:hover{background:var(--gdark)!important;color:#fff!important}
        .dropdown{position:relative}.dropdown-toggle{cursor:pointer}
        .dropdown-toggle::after{content:' ▾';font-size:10px}
        .dropdown-menu{display:none;position:absolute;top:100%;left:0;background:#fff;border-radius:8px;box-shadow:0 4px 20px rgba(91,45,142,0.2);min-width:160px;z-index:1000;overflow:hidden}
        .dropdown:hover .dropdown-menu{display:block}
        .dropdown-menu a{display:block;color:#333!important;padding:10px 16px;font-size:13px}
        .dropdown-menu a:hover{background:var(--bg);color:var(--purple)!important}

        .page-content{padding:28px 32px;max-width:1100px;margin:0 auto}
        .page-title{font-size:22px;font-weight:700;color:var(--pdark);margin-bottom:20px;border-left:4px solid var(--gold);padding-left:12px}

        .two-col{display:grid;grid-template-columns:420px 1fr;gap:24px;align-items:start}
        .card{background:#fff;border-radius:16px;padding:28px;box-shadow:0 4px 20px rgba(91,45,142,0.10);margin-bottom:24px}
        .card-header{font-size:15px;font-weight:700;color:var(--pdark);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:2px solid var(--bg);padding-bottom:12px}
        .card-header i{color:var(--gold)}

        .field-group{margin-bottom:16px}
        .field-group label{display:block;font-size:11.5px;font-weight:700;color:var(--pdark);letter-spacing:0.8px;text-transform:uppercase;margin-bottom:5px}
        .field-group input,.field-group select{width:100%;padding:10px 12px;border:1.5px solid #ddd;border-radius:10px;font-size:13.5px;font-family:'Inter',sans-serif;color:#333;outline:none;transition:border-color 0.2s,box-shadow 0.2s;background:#faf8ff}
        .field-group input:focus,.field-group select:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(91,45,142,0.08);background:#fff}
        .field-group input[readonly]{background:#f0ecf8;color:#777;cursor:not-allowed}

        .btn-reserve{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(90deg,var(--pdark),var(--purple));color:#fff;font-size:14px;font-weight:700;padding:11px 28px;border-radius:10px;border:none;cursor:pointer;font-family:'Inter',sans-serif;margin-top:4px;transition:all 0.2s;box-shadow:0 4px 14px rgba(91,45,142,0.3)}
        .btn-reserve:hover{background:linear-gradient(90deg,var(--purple),var(--plight));transform:translateY(-1px)}

        .alert-success{background:#f0fff4;border:1.5px solid #b2eec8;color:#1a7a3a;padding:10px 16px;border-radius:10px;margin-bottom:16px;font-size:13px}
        .alert-error{background:#fff0f0;border:1.5px solid #ffcccc;color:#c0392b;padding:10px 16px;border-radius:10px;margin-bottom:16px;font-size:13px}

        /* Reservations table */
        .res-table{width:100%;border-collapse:collapse}
        .res-table thead th{background:linear-gradient(90deg,var(--pdark),var(--purple));color:#fff;padding:10px 14px;font-size:12.5px;font-weight:600;text-align:left}
        .res-table tbody tr:nth-child(even){background:#f8f4ff}
        .res-table tbody tr:hover{background:#f0e8ff}
        .res-table tbody td{padding:9px 14px;font-size:13px;border-bottom:1px solid #ede6f5}
        .badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
        .badge-pending{background:#fff3cd;color:#856404}
        .badge-approved{background:#d4edda;color:#155724}
        .badge-rejected{background:#f8d7da;color:#721c24}

        @media(max-width:900px){.two-col{grid-template-columns:1fr}}
    </style>
</head>
<body>
<nav class="navbar">
    <a href="../dashboard.php" class="navbar-brand">
        <img src="../logos.png" alt="CCS">
        College of Computer Studies
    </a>
    <ul class="nav-links">
        <li class="dropdown"><a class="dropdown-toggle"><i class="fas fa-bell"></i> Notification</a>
            <div class="dropdown-menu"><a href="#">No new notifications</a></div>
        </li>
        <li><a href="../dashboard.php">Home</a></li>
        <li><a href="edit_profile.php">Edit Profile</a></li>
        <li><a href="history.php">History</a></li>
        <li><a href="reservation.php" class="active">Reservation</a></li>
        <li><a href="../logout.php" class="btn-logout-nav">Log out</a></li>
    </ul>
</nav>

<div class="page-content">
    <div class="page-title">Reservation</div>
    <div class="two-col">
        <!-- Form -->
        <div class="card">
            <div class="card-header"><i class="fas fa-calendar-plus"></i> New Reservation</div>
            <?php if($success): ?><div class="alert-success"><i class="fas fa-check-circle"></i> <?=$success?></div><?php endif; ?>
            <?php if($error): ?><div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?=$error?></div><?php endif; ?>
            <form method="POST">
                <div class="field-group">
                    <label>ID Number</label>
                    <input type="text" value="<?=htmlspecialchars($student['id_number'])?>" readonly>
                </div>
                <div class="field-group">
                    <label>Student Name</label>
                    <input type="text" value="<?=htmlspecialchars($student['fname'].' '.$student['lname'])?>" readonly>
                </div>
                <div class="field-group">
                    <label>Purpose</label>
                    <select name="purpose">
                        <option>C Programming</option>
                        <option>Java</option>
                        <option>PHP</option>
                        <option>Python</option>
                        <option>ASP.Net</option>
                        <option>C#</option>
                        <option>Database</option>
                        <option>Others</option>
                    </select>
                </div>
                <div class="field-group">
                    <label>Laboratory</label>
                    <select name="lab">
                        <option>524</option>
                        <option>526</option>
                        <option>528</option>
                        <option>530</option>
                        <option>542</option>
                    </select>
                </div>
                <div class="field-group">
                    <label>Preferred Time</label>
                    <input type="time" name="time_in">
                </div>
                <div class="field-group">
                    <label>Date</label>
                    <input type="date" name="date" min="<?=date('Y-m-d')?>">
                </div>
                <div class="field-group">
                    <label>Remaining Sessions</label>
                    <input type="text" value="<?=htmlspecialchars($student['remaining_session'])?> sessions" readonly>
                </div>
                <button type="submit" name="reserve" class="btn-reserve"><i class="fas fa-calendar-check"></i> Submit Reservation</button>
            </form>
        </div>

        <!-- My Reservations -->
        <div class="card">
            <div class="card-header"><i class="fas fa-list"></i> My Reservations</div>
            <?php if(empty($reservations)): ?>
                <p style="color:#999;font-size:13px;text-align:center;padding:20px">No reservations yet.</p>
            <?php else: ?>
            <table class="res-table">
                <thead>
                    <tr>
                        <th>Purpose</th>
                        <th>Lab</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($reservations as $r): ?>
                <tr>
                    <td><?=htmlspecialchars($r['purpose'])?></td>
                    <td><?=htmlspecialchars($r['lab'])?></td>
                    <td><?=htmlspecialchars($r['reserved_date'])?></td>
                    <td>
                        <span class="badge badge-<?=strtolower($r['status'])?>" <?= $r['status']==='Disabled' ? 'style="background:#6c757d;color:#fff"' : '' ?>>
                            <?=$r['status']?>
                        </span>
                    </td>
                    <td>
                        <?php if($r['status'] === 'Pending'): ?>
                            <a href="?action=disable&id=<?=$r['id']?>" class="btn-reserve" style="padding:5px 10px;font-size:11px;background:#dc3545;margin:0" onclick="return confirm('Disable this reservation?')">Disable</a>
                        <?php elseif($r['status'] === 'Disabled'): ?>
                            <a href="?action=enable&id=<?=$r['id']?>" class="btn-reserve" style="padding:5px 10px;font-size:11px;background:#28a745;margin:0" onclick="return confirm('Enable this reservation?')">Enable</a>
                        <?php else: ?>
                            <span style="color:#999;font-size:11px">--</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>