<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php"); exit();
}
require_once 'db.php';

$stmt = $pdo->prepare("SELECT * FROM students WHERE id_number = ?");
$stmt->execute([$_SESSION['user']]);
$student = $stmt->fetch();

$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll();

// Auto-insert notifications
foreach ($announcements as $ann) {
    $check = $pdo->prepare("SELECT id FROM student_notifications WHERE student_id=? AND announcement_id=?");
    $check->execute([$_SESSION['user'], $ann['id']]);
    if (!$check->fetch()) {
        $pdo->prepare("INSERT INTO student_notifications (student_id, announcement_id, is_read) VALUES (?,?,0)")
            ->execute([$_SESSION['user'], $ann['id']]);
    }
}

// Mark all as read
if (isset($_GET['mark_read'])) {
    $pdo->prepare("UPDATE student_notifications SET is_read=1 WHERE student_id=?")
        ->execute([$_SESSION['user']]);
    header("Location: dashboard.php"); exit();
}

// Count unread notifications
$unread = $pdo->prepare("SELECT COUNT(*) FROM student_notifications WHERE student_id=? AND is_read=0");
$unread->execute([$_SESSION['user']]);
$unread_count = $unread->fetchColumn();

// Get notifications
$notifs = $pdo->prepare("
    SELECT a.content, a.posted_by, a.created_at, n.is_read, n.id as notif_id
    FROM student_notifications n
    JOIN announcements a ON a.id = n.announcement_id
    WHERE n.student_id = ?
    ORDER BY a.created_at DESC LIMIT 10
");
$notifs->execute([$_SESSION['user']]);
$notifications = $notifs->fetchAll();

// Get reservation settings
$setting = $pdo->query("SELECT setting_value FROM settings WHERE setting_name='reservations_enabled'");
if ($setting) {
    $val = $setting->fetchColumn();
    $reservations_enabled = ($val === false) ? true : (bool)$val;
} else {
    $reservations_enabled = true; // Default
}

$success = $error = '';

// Check for existing pending session
$pending_check = $pdo->prepare("SELECT id, pc_no, lab FROM sit_in_records WHERE id_number = ? AND status = 'Pending'");
$pending_check->execute([$_SESSION['user']]);
$pending_session = $pending_check->fetch();

// ==================== HANDLE SIT-IN REQUEST ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_sitin'])) {
    if ($pending_session) {
        // Free the PC
        if ($pending_session['pc_no'] !== 'N/A') {
            $pdo->prepare("UPDATE lab_pcs SET status='Available' WHERE lab_name=? AND pc_number=?")->execute([$pending_session['lab'], $pending_session['pc_no']]);
        }
        
        // Delete the pending record
        $pdo->prepare("DELETE FROM sit_in_records WHERE id=?")->execute([$pending_session['id']]);
        
        $success = "Your pending sit-in request has been cancelled.";
        $pending_session = false; // Reset so the UI shows the request button again
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_sitin'])) {
    if (!$reservations_enabled) {
        $error = "Reservations are currently disabled by the administrator.";
    } else {
        $purpose = trim($_POST['purpose'] ?? '');
        $lab     = trim($_POST['lab'] ?? '');
        $pc_no   = trim($_POST['pc_no'] ?? '');

        if (empty($purpose) || empty($lab) || empty($pc_no)) {
            $error = "Please fill in all fields and select a PC.";
        } else {
            // Check if student already has pending or active session
            $check = $pdo->prepare("SELECT id FROM sit_in_records WHERE id_number = ? AND status IN ('Pending', 'Active')");
            $check->execute([$_SESSION['user']]);
            
            if ($check->rowCount() > 0) {
                $error = "You already have a pending or active sit-in session.";
            } elseif ($student['remaining_session'] <= 0) {
                $error = "You have no remaining sessions.";
            } else {
                // Verify PC is still available
                $pc_check = $pdo->prepare("SELECT status FROM lab_pcs WHERE lab_name=? AND pc_number=?");
                $pc_check->execute([$lab, $pc_no]);
                $pc_status = $pc_check->fetchColumn();

                if ($pc_status !== 'Available') {
                    $error = "Sorry, PC $pc_no is currently $pc_status. Please select another.";
                } else {
                    $name = $student['fname'] . ' ' . $student['lname'];
                    
                    // Mark PC as Occupied so others can't take it
                    $pdo->prepare("UPDATE lab_pcs SET status='Occupied' WHERE lab_name=? AND pc_number=?")->execute([$lab, $pc_no]);

                    // INSERT AS PENDING
                    $pdo->prepare("INSERT INTO sit_in_records (id_number, student_name, purpose, lab, time_in, status, pc_no) 
                                   VALUES (?,?,?,?, NOW(), 'Pending', ?)")
                        ->execute([$_SESSION['user'], $name, $purpose, $lab, $pc_no]);
                    
                    $success = "Sit-in request submitted successfully for PC $pc_no! Waiting for admin approval.";
                    
                    // Refresh student data & pending check
                    $stmt = $pdo->prepare("SELECT * FROM students WHERE id_number = ?");
                    $stmt->execute([$_SESSION['user']]);
                    $student = $stmt->fetch();
                    
                    $pending_check->execute([$_SESSION['user']]);
                    $pending_session = $pending_check->fetch();
                }
            }
        }
    }
}
// ===============================================================

// User Sit-in Summary stats
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_sessions,
        SUM(TIMESTAMPDIFF(MINUTE, time_in, time_out)) as total_minutes,
        AVG(TIMESTAMPDIFF(MINUTE, time_in, time_out)) as avg_minutes,
        MAX(TIMESTAMPDIFF(MINUTE, time_in, time_out)) as max_minutes
    FROM sit_in_records 
    WHERE id_number = ? AND status = 'Done'
");
$stats->execute([$_SESSION['user']]);
$user_stats = $stats->fetch();

$total_sessions = $user_stats['total_sessions'] ?? 0;
$total_hours = floor(($user_stats['total_minutes'] ?? 0) / 60) . 'h ' . (($user_stats['total_minutes'] ?? 0) % 60) . 'm';
$avg_duration = floor(($user_stats['avg_minutes'] ?? 0) / 60) . 'h ' . round(($user_stats['avg_minutes'] ?? 0) % 60) . 'm';
$longest_session = floor(($user_stats['max_minutes'] ?? 0) / 60) . 'h ' . (($user_stats['max_minutes'] ?? 0) % 60) . 'm';

// Sessions Table
$sessions_stmt = $pdo->prepare("SELECT * FROM sit_in_records WHERE id_number = ? ORDER BY time_in DESC LIMIT 20");
$sessions_stmt->execute([$_SESSION['user']]);
$recent_sessions = $sessions_stmt->fetchAll();

// Testimonials
$testimonials_stmt = $pdo->query("
    SELECT student_name, rating, message, created_at 
    FROM feedback 
    WHERE rating >= 4 
    ORDER BY created_at DESC LIMIT 5
");
$testimonials = $testimonials_stmt->fetchAll();

$avatar = 'logos.png';
if (!empty($student['photo']) && file_exists('uploads/photos/' . $student['photo'])) {
    $avatar = 'uploads/photos/' . $student['photo'];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Student Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{
            --purple:#5B2D8E;
            --pdark:#3D1A6E;
            --plight:#7B4BB8;
            --gold:#F0B429;
            --gdark:#C88F0A;
            --bg:#f5f0ff;
            --card-bg:#ffffff;
            --text-main:#333333;
            --text-muted:#888888;
            --border-color:#ede6f5;
            --modal-bg:#ffffff;
        }
        [data-theme="dark"]{
            --purple:#8e60c2;
            --pdark:#241242;
            --plight:#ac84de;
            --gold:#f5c95d;
            --gdark:#dfa212;
            --bg:#0e0717;
            --card-bg:#1a0f2e;
            --text-main:#e0dced;
            --text-muted:#9184a8;
            --border-color:#342054;
            --modal-bg:#1a0f2e;
        }
        body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text-main);min-height:100vh;transition:background 0.3s, color 0.3s;}

        .navbar{background:var(--pdark);padding:0 32px;height:58px;display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid var(--gold);box-shadow:0 2px 12px rgba(0,0,0,0.25)}
        .navbar-brand{color:#fff;font-size:14px;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:10px}
        .navbar-brand img{width:36px;height:36px;object-fit:contain;border-radius:0;}
        .nav-links{display:flex;align-items:center;gap:6px;list-style:none}
        .nav-links a, .nav-links button.btn-theme{color:rgba(255,255,255,0.9);text-decoration:none;font-size:13px;font-weight:500;padding:6px 13px;border-radius:4px;transition:background 0.2s;background:transparent;border:none;cursor:pointer;}
        .nav-links a:hover,.nav-links a.active, .nav-links button.btn-theme:hover{background:rgba(255,255,255,0.18)}
        .btn-logout-nav{background:var(--gold)!important;color:var(--pdark)!important;font-weight:700!important;border-radius:5px!important}
        .btn-logout-nav:hover{background:var(--gdark)!important;color:#fff!important}

        .dropdown{position:relative}
        .dropdown-toggle{cursor:pointer;color:rgba(255,255,255,0.9);font-size:13px;font-weight:500;padding:6px 13px;border-radius:4px;transition:background 0.2s;display:flex;align-items:center;gap:6px}
        .dropdown-toggle:hover{background:rgba(255,255,255,0.18)}
        .notif-badge{background:#dc3545;color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;margin-left:2px;}
        .notif-badge.hidden{display:none}
        .dropdown-menu{display:none;position:absolute;top:100%;left:0;background:var(--card-bg);border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,0.3);min-width:320px;z-index:1000;overflow:hidden;border:1px solid var(--border-color);}
        .dropdown:hover .dropdown-menu{display:block}
        .notif-header{background:linear-gradient(90deg,var(--pdark),var(--purple));color:#fff;padding:12px 16px;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:space-between;}
        .notif-header a{color:var(--gold);font-size:11px;font-weight:600;text-decoration:none}
        .notif-item{padding:12px 16px;border-bottom:1px solid var(--border-color);font-size:12.5px;color:var(--text-main);transition:background 0.2s;}
        .notif-item:hover{background:var(--bg)}
        .notif-item.unread{border-left:3px solid var(--gold);background:var(--bg);}
        .notif-item .notif-by{font-weight:700;color:var(--purple);font-size:12px;margin-bottom:3px}
        .notif-item .notif-msg{color:var(--text-muted);font-size:12px;line-height:1.5;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px}
        .notif-item .notif-time{font-size:10.5px;color:var(--text-muted);margin-top:3px}
        .notif-empty{padding:20px;text-align:center;color:var(--text-muted);font-size:13px}

        .main-content{padding:28px 32px;display:grid;grid-template-columns:300px 1fr;gap:24px;max-width:1300px;margin:0 auto}
        .left-col{display:flex;flex-direction:column;gap:24px}
        .right-col{display:flex;flex-direction:column;gap:24px}

        .card{background:var(--card-bg);border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);overflow:hidden;border:1px solid var(--border-color)}
        .card-header{background:linear-gradient(90deg,var(--pdark),var(--purple));color:#fff;padding:14px 20px;font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px}
        .card-header i{color:var(--gold)}
        .card-body{padding:18px 20px}

        .student-card-top{background:linear-gradient(135deg,var(--pdark),var(--purple));padding:28px 20px;text-align:center}
        .avatar-wrap{width:95px;height:95px;border-radius:50%;background:#fff;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;border:4px solid var(--gold);overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.2);position:relative;cursor:pointer}
        .avatar-wrap img{width:100%;height:100%;object-fit:cover}
        .avatar-overlay{position:absolute;inset:0;background:rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity 0.2s;border-radius:50%}
        .avatar-wrap:hover .avatar-overlay{opacity:1}
        .avatar-overlay i{color:#fff;font-size:20px}
        .student-name{color:#fff;font-size:15px;font-weight:700;margin-bottom:2px}
        .student-id{color:rgba(255,255,255,0.7);font-size:12px}
        .info-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border-color);font-size:13px}
        .info-item:last-child{border-bottom:none}
        .info-icon{width:28px;height:28px;border-radius:8px;background:var(--bg);display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .info-icon i{color:var(--purple);font-size:12px}
        .info-label{color:var(--text-muted);font-size:11px;display:block}
        .info-value{font-weight:600;font-size:13px}
        .session-badge{background:linear-gradient(90deg,var(--pdark),var(--purple));color:#fff;border-radius:20px;padding:4px 14px;font-size:12px;font-weight:700;display:inline-block;margin-top:4px}
        
        .btn-sitin{display:block;width:calc(100% - 40px);margin:0 20px 18px;padding:11px;background:linear-gradient(90deg,var(--gold),var(--gdark));color:var(--pdark);font-size:13px;font-weight:700;border:none;border-radius:10px;cursor:pointer;font-family:'Inter',sans-serif;transition:all 0.2s;box-shadow:0 4px 12px rgba(240,180,41,0.3)}
        .btn-sitin:hover:not(:disabled){transform:translateY(-1px);box-shadow:0 6px 18px rgba(240,180,41,0.4)}
        .btn-sitin:disabled{background:#555;color:#aaa;box-shadow:none;}

        .stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .stat-box{background:var(--bg);padding:14px;border-radius:10px;text-align:center;border:1px solid var(--border-color)}
        .stat-val{font-size:18px;font-weight:800;color:var(--purple);margin-bottom:2px}
        .stat-lbl{font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase}

        .lab-list{list-style:none;font-size:13px;line-height:1.8}
        .lab-list li{margin-bottom:8px;padding-bottom:8px;border-bottom:1px dashed var(--border-color)}
        .lab-list li:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
        .lab-list strong{color:var(--purple)}

        .announce-item{border-bottom:1px solid var(--border-color);padding:12px 0}
        .announce-item:last-child{border-bottom:none}
        .announce-meta{font-weight:600;color:var(--purple);font-size:12.5px;margin-bottom:5px}
        .announce-content{color:var(--text-main);font-size:13px;line-height:1.6;background:var(--bg);border-radius:8px;padding:10px 12px;border-left:3px solid var(--gold)}

        .testi-scroll{display:grid;grid-template-columns:1fr;gap:12px;}
        .testi-item{background:var(--bg);border:1px solid var(--border-color);padding:14px;border-radius:10px;border-left:3px solid var(--purple)}
        .testi-stars{color:var(--gold);font-size:14px;margin-bottom:6px}
        .testi-msg{font-size:13px;font-style:italic;margin-bottom:8px;line-height:1.5}
        .testi-author{font-size:11.5px;font-weight:700;color:var(--text-muted);text-align:right}

        table.dataTable{width:100%!important;border-collapse:collapse;color:var(--text-main)}
        table.dataTable thead th{background:linear-gradient(90deg,var(--pdark),var(--purple));color:#fff;padding:10px 14px;font-size:13px;font-weight:600;border:none}
        table.dataTable tbody tr{background:var(--card-bg)}
        table.dataTable tbody tr:nth-child(even){background:var(--bg)}
        table.dataTable tbody tr:hover{background:var(--border-color)}
        table.dataTable tbody td{padding:9px 14px;font-size:13px;border-bottom:1px solid var(--border-color)}
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate {
            color: var(--text-main) !important;
            font-size:12px; margin-top:10px; margin-bottom:10px;
        }
        .dataTables_wrapper .dataTables_filter input { background: var(--bg); color: var(--text-main); border: 1px solid var(--border-color); border-radius: 6px; padding: 4px 8px; outline: none; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { color: var(--text-main)!important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--purple)!important; color: #fff!important; border:none!important; border-radius:5px;}

        .badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
        .badge-active{background:#d4edda;color:#155724}
        .badge-done{background:#e2e3e5;color:#383d41}
        .badge-pending{background:#fff3cd;color:#856404}

        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center}
        .modal-overlay.show{display:flex}
        .modal-box{background:var(--modal-bg);border-radius:16px;padding:28px 32px;width:100%;max-width:500px;position:relative;box-shadow:0 20px 60px rgba(0,0,0,0.4)}
        .modal-box h3{font-size:17px;font-weight:700;margin-bottom:18px;border-left:3px solid var(--gold);padding-left:10px;color:var(--text-main)}
        .modal-close{position:absolute;top:12px;right:16px;background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted)}
        .modal-close:hover{color:var(--purple)}
        .form-group{margin-bottom:14px}
        .form-group label{display:block;font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:5px}
        .form-group select,.form-group input{width:100%;padding:10px 12px;border:1.5px solid var(--border-color);border-radius:8px;font-size:13.5px;font-family:'Inter',sans-serif;outline:none;background:var(--bg);color:var(--text-main)}
        .form-group select:focus,.form-group input:focus{border-color:var(--purple);}
        .modal-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}
        .btn-cancel{padding:9px 20px;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:var(--border-color);color:var(--text-main);font-family:'Inter',sans-serif}
        .btn-submit{padding:9px 20px;border-radius:8px;font-size:13px;font-weight:700;border:none;cursor:pointer;background:linear-gradient(90deg,var(--pdark),var(--purple));color:#fff;font-family:'Inter',sans-serif}
        
        .alert-success{background:#d4edda;border:1.5px solid #c3e6cb;color:#155724;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px}
        .alert-error{background:#f8d7da;border:1.5px solid #f5c6cb;color:#721c24;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px}

        /* PC GRID CSS */
        .pc-grid { display: grid; grid-template-columns: repeat(10, 1fr); gap: 6px; margin-top: 8px; max-height: 200px; overflow-y: auto; padding: 5px; background:var(--bg); border:1px solid var(--border-color); border-radius: 8px; }
        .pc-box { aspect-ratio: 1; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; color: #fff; cursor: pointer; transition: transform 0.1s, opacity 0.1s; }
        .pc-box.Available { background: #28a745; }
        .pc-box.Occupied { background: #dc3545; cursor: not-allowed; opacity: 0.6; }
        .pc-box.Maintenance { background: #fd7e14; cursor: not-allowed; opacity: 0.6; }
        .pc-box.selected { transform: scale(1.15); box-shadow: 0 0 0 3px var(--purple); z-index: 2; }
        .pc-legend { display: flex; gap: 15px; font-size: 11px; color: var(--text-muted); margin-top: 8px; justify-content:center;}
        .pc-legend span { display: flex; align-items: center; gap: 4px; }
        .pc-legend .dot { width: 10px; height: 10px; border-radius: 2px; }
        .pc-legend .dot.g { background: #28a745; }
        .pc-legend .dot.r { background: #dc3545; }
        .pc-legend .dot.o { background: #fd7e14; }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="dashboard.php" class="navbar-brand">
        <img src="logos.png" alt="CCS">
        College of Computer Studies
    </a>
    <ul class="nav-links">
        <li>
            <button id="themeToggle" class="btn-theme" title="Toggle Dark Mode">
                <i class="fas fa-moon"></i>
            </button>
        </li>
        <li class="dropdown">
            <a class="dropdown-toggle">
                <i class="fas fa-bell"></i> Notification
                <span class="notif-badge <?= $unread_count == 0 ? 'hidden' : '' ?>"><?= $unread_count ?></span>
            </a>
            <div class="dropdown-menu">
                <div class="notif-header">
                    <span><i class="fas fa-bell"></i> Notifications</span>
                    <?php if($unread_count > 0): ?>
                    <a href="?mark_read=1">Mark all as read</a>
                    <?php endif; ?>
                </div>
                <?php if(empty($notifications)): ?>
                <div class="notif-empty"><i class="fas fa-bell-slash"></i><br>No notifications yet</div>
                <?php else: ?>
                <?php foreach($notifications as $n): ?>
                <div class="notif-item <?= $n['is_read'] == 0 ? 'unread' : '' ?>">
                    <div class="notif-by"><i class="fas fa-bullhorn" style="color:var(--gold)"></i> <?= htmlspecialchars($n['posted_by']) ?></div>
                    <div class="notif-msg"><?= htmlspecialchars($n['content']) ?></div>
                    <div class="notif-time"><i class="fas fa-clock"></i> <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </li>
        <li><a href="dashboard.php" class="active">Home</a></li>
        <li><a href="student/edit_profile.php">Edit Profile</a></li>
        <li><a href="student/history.php">History</a></li>
        <li><a href="student/reservation.php">Reservation</a></li>
        <li><a href="logout.php" class="btn-logout-nav">Log out</a></li>
    </ul>
</nav>

<div class="main-content">
    <div class="left-col">
        <div class="card student-card">
            <div class="student-card-top">
                <div class="avatar-wrap" onclick="document.getElementById('photoModal').classList.add('show')" title="Change photo">
                    <img src="<?=htmlspecialchars($avatar)?>" alt="Avatar" id="avatarImg">
                    <div class="avatar-overlay"><i class="fas fa-camera"></i></div>
                </div>
                <div class="student-name"><?=htmlspecialchars($student['fname'].' '.$student['lname'])?></div>
                <div class="student-id"><?=htmlspecialchars($student['id_number'])?></div>
            </div>
            <div class="card-body">
                <div class="info-item">
                    <div class="info-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div><span class="info-label">Course</span><span class="info-value"><?=htmlspecialchars($student['course'])?></span></div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="fas fa-layer-group"></i></div>
                    <div><span class="info-label">Year Level</span><span class="info-value"><?=htmlspecialchars($student['course_lvl'])?></span></div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="fas fa-envelope"></i></div>
                    <div><span class="info-label">Email</span><span class="info-value" style="font-size:12px"><?=htmlspecialchars($student['email'])?></span></div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div><span class="info-label">Address</span><span class="info-value"><?=htmlspecialchars($student['address'] ?: 'N/A')?></span></div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="fas fa-clock"></i></div>
                    <div><span class="info-label">Remaining Sessions</span><span class="session-badge"><?=htmlspecialchars($student['remaining_session'])?> sessions</span></div>
                </div>
            </div>
            <?php if($pending_session): ?>
            <form method="POST" style="margin:0 20px 18px;">
                <button type="submit" name="cancel_sitin" class="btn-sitin" style="background:#dc3545;color:#fff;width:100%;margin:0;box-shadow:0 4px 12px rgba(220,53,69,0.2)" onclick="return confirm('Are you sure you want to cancel your pending request for PC <?=htmlspecialchars($pending_session['pc_no'])?> in Lab <?=htmlspecialchars($pending_session['lab'])?>?')">
                    <i class="fas fa-times-circle"></i> Cancel Pending Request
                </button>
            </form>
            <?php elseif($reservations_enabled): ?>
            <button class="btn-sitin" onclick="document.getElementById('sitinModal').classList.add('show')">
                <i class="fas fa-desktop"></i> Request Sit-in
            </button>
            <?php else: ?>
            <button class="btn-sitin" disabled title="Reservations are currently disabled by the administrator.">
                <i class="fas fa-ban"></i> Reservations Disabled
            </button>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-chart-pie"></i> User Sit-in Summary</div>
            <div class="card-body stat-grid">
                <div class="stat-box">
                    <div class="stat-val"><?= $total_sessions ?></div>
                    <div class="stat-lbl">Total Sessions</div>
                </div>
                <div class="stat-box">
                    <div class="stat-val"><?= $total_hours ?></div>
                    <div class="stat-lbl">Total Hours</div>
                </div>
                <div class="stat-box">
                    <div class="stat-val"><?= $avg_duration ?></div>
                    <div class="stat-lbl">Avg Duration</div>
                </div>
                <div class="stat-box">
                    <div class="stat-val"><?= $longest_session ?></div>
                    <div class="stat-lbl">Longest Session</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-desktop"></i> Software Availability / Lab</div>
            <div class="card-body">
                <ul class="lab-list">
                    <li><strong>Lab 524:</strong> Python, Java, C++, MySQL</li>
                    <li><strong>Lab 526:</strong> PHP, Apache, MySQL, Node.js</li>
                    <li><strong>Lab 528:</strong> Visual Studio, C#, ASP.NET</li>
                    <li><strong>Lab 530:</strong> Adobe CC, Figma, Blender</li>
                    <li><strong>Lab 542:</strong> Android Studio, Flutter, iOS</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="right-col">
        <div class="card">
            <div class="card-header"><i class="fas fa-bullhorn"></i> Announcement</div>
            <div class="card-body">
                <?php if($success): ?><div class="alert-success"><i class="fas fa-check-circle"></i> <?=$success?></div><?php endif; ?>
                <?php if($error): ?><div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?=$error?></div><?php endif; ?>
                
                <?php if(empty($announcements)): ?>
                    <p style="color:var(--text-muted);font-size:13px">No announcements yet.</p>
                <?php endif; ?>
                <?php foreach($announcements as $ann): ?>
                <div class="announce-item">
                    <div class="announce-meta"><?=htmlspecialchars($ann['posted_by'])?> | <?=date('Y-M-d', strtotime($ann['created_at']))?></div>
                    <?php if($ann['content']): ?><div class="announce-content"><?=htmlspecialchars($ann['content'])?></div><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-list"></i> Sessions Table</div>
            <div class="card-body">
                <table id="sessionsTable" class="dataTable">
                    <thead>
                        <tr>
                            <th>Date</th><th>Time In</th><th>Time Out</th><th>Duration</th><th>PC No.</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_sessions as $s): 
                            $duration = '--';
                            if ($s['time_in'] && $s['time_out']) {
                                $diff = strtotime($s['time_out']) - strtotime($s['time_in']);
                                $duration = floor($diff/60) . 'm';
                            }
                        ?>
                        <tr>
                            <td><?= date('Y-m-d', strtotime($s['time_in'])) ?></td>
                            <td><?= date('h:i A', strtotime($s['time_in'])) ?></td>
                            <td><?= $s['time_out'] ? date('h:i A', strtotime($s['time_out'])) : '--' ?></td>
                            <td><?= $duration ?></td>
                            <td><?= htmlspecialchars($s['pc_no']) ?></td>
                            <td><span class="badge badge-<?=strtolower($s['status'])?>"><?= $s['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recent_sessions)): ?>
                        <tr><td colspan="6" style="text-align:center;color:var(--text-muted)">No sessions recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-star"></i> Students Testimonials</div>
            <div class="card-body">
                <div class="testi-scroll">
                    <?php foreach($testimonials as $t): ?>
                    <div class="testi-item">
                        <div class="testi-stars"><?= str_repeat('★', $t['rating']) . str_repeat('☆', 5 - $t['rating']) ?></div>
                        <div class="testi-msg">"<?= htmlspecialchars($t['message']) ?>"</div>
                        <div class="testi-author">- <?= htmlspecialchars($t['student_name']) ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($testimonials)): ?>
                        <p style="color:var(--text-muted);font-size:13px">No testimonials available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sit-in Modal -->
<div class="modal-overlay" id="sitinModal">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('sitinModal').classList.remove('show')">×</button>
        <h3><i class="fas fa-desktop"></i> Request Sit-in</h3>
        
        <form method="POST">
            <div class="form-group" style="display:none;">
                <label>ID Number</label>
                <input type="text" value="<?=htmlspecialchars($student['id_number'])?>" readonly>
            </div>
            <div class="form-group" style="display:none;">
                <label>Student Name</label>
                <input type="text" value="<?=htmlspecialchars($student['fname'].' '.$student['lname'])?>" readonly>
            </div>
            <div class="form-group">
                <label>Purpose</label>
                <select name="purpose" required>
                    <option value="">-- Select Purpose --</option>
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
            <div class="form-group">
                <label>Laboratory</label>
                <select name="lab" id="labSelect" required onchange="loadPCs()">
                    <option value="">-- Select Lab --</option>
                    <option>524</option>
                    <option>526</option>
                    <option>528</option>
                    <option>530</option>
                    <option>542</option>
                </select>
            </div>
            
            <div class="form-group" id="pcSelectionGroup" style="display:none;">
                <label>Select PC</label>
                <div id="pcGrid" class="pc-grid"></div>
                <input type="hidden" name="pc_no" id="selectedPcInput" required>
                <div class="pc-legend">
                    <span><div class="dot g"></div> Available</span>
                    <span><div class="dot r"></div> Occupied</span>
                    <span><div class="dot o"></div> Maintenance</span>
                </div>
                <p id="pcSelectionText" style="font-size:12px;color:var(--text-muted);margin-top:8px;text-align:center;">Please click an available green PC.</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('sitinModal').classList.remove('show')">Cancel</button>
                <button type="submit" name="submit_sitin" class="btn-submit" id="submitBtn" disabled>Submit Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Photo Modal -->
<div class="modal-overlay" id="photoModal">
    <div class="modal-box photo-modal-box">
        <button class="modal-close" onclick="document.getElementById('photoModal').classList.remove('show')">×</button>
        <h3><i class="fas fa-camera"></i> Update Photo</h3>
        <div style="text-align:center;margin-bottom:15px">
            <img src="<?=htmlspecialchars($avatar)?>" alt="Preview" class="photo-preview" id="photoPreview" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--gold)">
        </div>
        <form method="POST" action="upload_photo.php" enctype="multipart/form-data">
            <div class="form-group">
                <label>Choose Photo</label>
                <input type="file" name="photo" accept="image/*" onchange="previewPhoto(this)">
            </div>
            <p style="font-size:11.5px;color:var(--text-muted);text-align:center;margin-top:8px">JPG, PNG or GIF — Max 2MB</p>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('photoModal').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Photo</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function(){
    $('#sessionsTable').DataTable({
        order: [[0, 'desc'], [1, 'desc']],
        pageLength: 5,
        lengthMenu: [5, 10, 20]
    });
});

function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('photoPreview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}

// PC Loading Logic
function loadPCs() {
    const lab = document.getElementById('labSelect').value;
    const group = document.getElementById('pcSelectionGroup');
    const grid = document.getElementById('pcGrid');
    const input = document.getElementById('selectedPcInput');
    const btn = document.getElementById('submitBtn');
    const text = document.getElementById('pcSelectionText');
    
    if(!lab) {
        group.style.display = 'none';
        input.value = '';
        btn.disabled = true;
        return;
    }
    
    group.style.display = 'block';
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px;font-size:12px;color:var(--text-muted)">Loading PCs...</div>';
    input.value = '';
    btn.disabled = true;
    text.innerHTML = 'Please click an available green PC.';

    fetch('get_pcs.php?lab=' + encodeURIComponent(lab))
        .then(response => response.json())
        .then(data => {
            grid.innerHTML = '';
            data.forEach(pc => {
                const box = document.createElement('div');
                box.className = 'pc-box ' + pc.status;
                box.innerText = pc.pc_number;
                
                if(pc.status === 'Available') {
                    box.onclick = function() {
                        // clear previous selection
                        document.querySelectorAll('.pc-box').forEach(b => b.classList.remove('selected'));
                        box.classList.add('selected');
                        input.value = pc.pc_number;
                        btn.disabled = false;
                        text.innerHTML = 'Selected <b>PC ' + pc.pc_number + '</b>';
                    };
                } else {
                    box.onclick = function() {
                        alert('PC ' + pc.pc_number + ' is currently ' + pc.status + '.');
                    };
                }
                grid.appendChild(box);
            });
        });
}

// Dark Mode Toggle Logic
const themeToggle = document.getElementById('themeToggle');
const root = document.documentElement;
const icon = themeToggle.querySelector('i');

if(localStorage.getItem('theme') === 'dark') {
    root.setAttribute('data-theme', 'dark');
    icon.classList.replace('fa-moon', 'fa-sun');
}

themeToggle.addEventListener('click', () => {
    if(root.getAttribute('data-theme') === 'dark') {
        root.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
        icon.classList.replace('fa-sun', 'fa-moon');
    } else {
        root.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
        icon.classList.replace('fa-moon', 'fa-sun');
    }
});
</script>
</body>
</html>