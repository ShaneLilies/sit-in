<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Admin | <?= $pageTitle ?? 'Dashboard' ?></title>
    <script>
    // Immediate Theme Applier to prevent flickering
    (function() {
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{
            --purple:#5B2D8E;
            --pdark:#3D1A6E;
            --plight:#7B4BB8;
            --gold:#F0B429;
            --gdark:#C88F0A;
            --bg:#f5f0ff;
            --white:#fff;
            --input-bg:#faf8ff;
            --text-main:#333333;
            --text-muted:#888888;
            --border-color:#ede6f5;
        }
        [data-theme="dark"]{
            --purple:#8e60c2;
            --pdark:#241242;
            --plight:#ac84de;
            --gold:#f5c95d;
            --gdark:#dfa212;
            --bg:#0e0717;
            --white:#1a0f2e;
            --input-bg:#0c0614;
            --text-main:#e0dced;
            --text-muted:#9184a8;
            --border-color:#342054;
        }
        body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text-main);min-height:100vh}

        /* NAVBAR */
        .topnav{
            background:var(--pdark);
            color:#fff;
            padding:0 24px;
            height:56px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            border-bottom:3px solid var(--gold);
            box-shadow:0 2px 12px rgba(0,0,0,0.25);
        }
        .topnav-brand{
            font-size:14px;font-weight:700;color:#fff;
            text-decoration:none;display:flex;align-items:center;gap:10px;
        }
        .topnav-brand img{width:34px;height:34px;object-fit:contain;border-radius:50%}
        .topnav-links{display:flex;align-items:center;gap:2px;list-style:none}
        .topnav-links a{
            color:rgba(255,255,255,0.88);text-decoration:none;
            font-size:12.5px;font-weight:500;padding:6px 11px;
            border-radius:4px;transition:background 0.2s;white-space:nowrap;
        }
        .topnav-links a:hover,.topnav-links a.active{background:rgba(255,255,255,0.18)}
        .btn-logout-nav{background:var(--gold)!important;color:var(--pdark)!important;font-weight:700!important;border-radius:5px!important}
        .btn-logout-nav:hover{background:var(--gdark)!important;color:#fff!important}

        /* ── FEEDBACK NOTIFICATION BADGE (same style as student notif-badge) ── */
        .feedback-nav-wrap {
            position: relative;
        }
        .feedback-nav-btn {
            cursor: pointer;
            color: rgba(255,255,255,0.88);
            font-size: 12.5px;
            font-weight: 500;
            padding: 6px 11px;
            border-radius: 4px;
            transition: background 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .feedback-nav-btn:hover,
        .feedback-nav-btn.active { background: rgba(255,255,255,0.18); }
        .notif-badge {
            background: #dc3545;
            color: #fff;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 2px;
            animation: popIn 0.3s ease;
        }
        .notif-badge.hidden { display: none; }
        @keyframes popIn {
            from { transform: scale(0); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }

        /* PAGE LAYOUT */
        .page-content{padding:28px 32px;max-width:1300px;margin:0 auto}
        .page-title{font-size:22px;font-weight:700;color:var(--pdark);margin-bottom:20px;border-left:4px solid var(--gold);padding-left:12px;}

        /* STAT CARDS */
        .stat-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:28px}
        .stat-card{background:var(--white);color:var(--text-main);border-radius:12px;padding:20px 24px;box-shadow:0 2px 12px rgba(91,45,142,0.10);border-top:4px solid var(--purple);}
        .stat-card.green{border-top-color:#28a745}
        .stat-card.gold{border-top-color:var(--gold)}
        .stat-card h3{font-size:13px;color:var(--text-muted);font-weight:500;margin-bottom:6px}
        .stat-card .num{font-size:32px;font-weight:700;color:var(--pdark)}
        .stat-card.green .num{color:#28a745}
        .stat-card.gold .num{color:var(--gdark)}

        /* TABLE CARDS */
        .table-card{background:var(--white);color:var(--text-main);border-radius:12px;padding:24px;box-shadow:0 2px 12px rgba(91,45,142,0.10);margin-bottom:24px;}
        .table-card h3{font-size:15px;font-weight:700;color:var(--pdark);margin-bottom:16px;display:flex;align-items:center;gap:8px;}
        .table-card h3 i{color:var(--gold)}

        /* DATATABLES */
        table.dataTable{width:100%!important;border-collapse:collapse;color:var(--text-main)}
        table.dataTable thead th{background:linear-gradient(90deg,var(--pdark),var(--purple));color:#fff;padding:10px 14px;font-size:13px;font-weight:600;border:none;}
        table.dataTable tbody tr{background:var(--white)}
        table.dataTable tbody tr:nth-child(even){background:var(--white);opacity:0.95}
        table.dataTable tbody tr:hover{background:var(--bg)!important;color:var(--text-main)}
        table.dataTable tbody td{padding:9px 14px;font-size:13px;border-bottom:1px solid var(--border-color)}
        .dataTables_wrapper{color:var(--text-main)!important}
        .dataTables_wrapper .dataTables_filter input{border:1.5px solid var(--border-color);background:var(--input-bg);color:var(--text-main);border-radius:6px;padding:5px 10px;font-size:13px;outline:none;}
        .dataTables_wrapper .dataTables_filter input:focus{border-color:var(--purple)}
        .dataTables_wrapper .dataTables_paginate .paginate_button{color:var(--text-main)!important}
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover{background:var(--purple)!important;color:#fff!important;border-color:var(--purple)!important;border-radius:5px;}
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover{background:var(--bg)!important;color:var(--pdark)!important;border-color:var(--plight)!important;border-radius:5px;}

        /* BUTTONS */
        .btn-sm{padding:5px 12px;border-radius:5px;font-size:12px;font-weight:600;border:none;cursor:pointer;transition:all 0.2s;text-decoration:none;display:inline-block}
        .btn-primary{background:var(--purple);color:#fff}.btn-primary:hover{background:var(--pdark)}
        .btn-danger{background:#dc3545;color:#fff}.btn-danger:hover{background:#b02a37}
        .btn-success{background:#28a745;color:#fff}.btn-success:hover{background:#1e7e34}
        .btn-warning{background:var(--gold);color:var(--pdark);font-weight:700}.btn-warning:hover{background:var(--gdark);color:#fff}
        .btn-gold{background:var(--gold);color:var(--pdark);font-weight:700;padding:8px 18px;border-radius:6px;border:none;cursor:pointer;font-size:13px;transition:all 0.2s;}
        .btn-gold:hover{background:var(--gdark);color:#fff}

        /* MODAL */
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(61,26,110,0.55);z-index:9999;align-items:center;justify-content:center}
        .modal-overlay.show{display:flex}
        .modal-box{background:var(--white);color:var(--text-main);border-radius:16px;padding:28px 32px;width:100%;max-width:480px;position:relative;box-shadow:0 20px 60px rgba(91,45,142,0.35);}
        .modal-box h3{font-size:17px;font-weight:700;color:var(--pdark);margin-bottom:18px;border-left:3px solid var(--gold);padding-left:10px}
        .modal-close{position:absolute;top:12px;right:16px;background:none;border:none;font-size:22px;cursor:pointer;color:#999}
        .modal-close:hover{color:var(--pdark)}

        /* FORMS */
        .form-group{margin-bottom:14px}
        .form-group label{display:block;font-size:12.5px;font-weight:600;color:var(--pdark);margin-bottom:5px}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:9px 12px;border:1.5px solid var(--border-color);border-radius:7px;font-size:13.5px;font-family:'Inter',sans-serif;outline:none;transition:border-color 0.2s;background:var(--input-bg);color:var(--text-main);}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(91,45,142,0.08);background:var(--white);}
        .modal-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}

        /* ANNOUNCEMENTS */
        .announce-list{margin-top:12px}
        .announce-item{border-bottom:1px solid var(--border-color);padding:10px 0}
        .announce-item .meta{font-weight:600;color:var(--pdark);margin-bottom:3px;font-size:12.5px}
        .announce-item .content{color:var(--text-main);font-size:13px;background:var(--input-bg);border-radius:8px;padding:8px 12px;border-left:3px solid var(--gold)}

        /* BADGES */
        .badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
        .badge-active{background:#d4edda;color:#155724}
        .badge-done{background:#e2e3e5;color:#383d41}
        .badge-pending{background:#fff3cd;color:#856404}
        .badge-approved{background:#d4edda;color:#155724}
        .badge-rejected{background:#f8d7da;color:#721c24}

        /* GRID */
        .two-col{display:grid;grid-template-columns:1fr 1fr;gap:24px}

        /* ALERTS */
        .alert-success{background:#d4edda;color:#155724;padding:10px 16px;border-radius:8px;margin-bottom:14px;font-size:13px;border-left:4px solid #28a745}
        .alert-danger{background:#f8d7da;color:#721c24;padding:10px 16px;border-radius:8px;margin-bottom:14px;font-size:13px;border-left:4px solid #dc3545}

        @media(max-width:900px){
            .stat-cards{grid-template-columns:1fr 1fr}
            .two-col{grid-template-columns:1fr}
            .topnav-links{gap:0}
            .page-content{padding:18px 16px}
        }
    </style>
</head>
<body>
<?php
// ── Count unread feedback for badge ──────────────────────────────────────────
$unreadFeedback = 0;
try {
    $unreadFeedback = (int)$pdo->query("SELECT COUNT(*) FROM feedback WHERE is_read = 0")->fetchColumn();
} catch (Exception $e) {
    $unreadFeedback = 0;
}
?>
<nav class="topnav">
    <a href="index.php" class="topnav-brand">
        <img src="../logos.png" alt="CCS Logo">
        College of Computer Studies Admin
    </a>
    <ul class="topnav-links">
        <li><a href="index.php"       <?=($pageTitle??'')==='Dashboard'  ?'class="active"':''?>>Home</a></li>
        <li><a href="search.php"      <?=($pageTitle??'')==='Search'     ?'class="active"':''?>>Search</a></li>
        <li><a href="students.php"    <?=($pageTitle??'')==='Students'   ?'class="active"':''?>>Students</a></li>
        <li><a href="sitin.php"       <?=($pageTitle??'')==='Sit-in'     ?'class="active"':''?>>Sit-in</a></li>
        <li><a href="records.php"     <?=($pageTitle??'')==='Records'    ?'class="active"':''?>>View Sit-in Records</a></li>
        <li><a href="reports.php"     <?=($pageTitle??'')==='Reports'    ?'class="active"':''?>>Sit-in Reports</a></li>
        <li><a href="software.php"    <?=($pageTitle??'')==='Software Management'?'class="active"':''?>>Software</a></li>
        <li><a href="labs.php"        <?=($pageTitle??'')==='Labs'       ?'class="active"':''?>>Lab PCs</a></li>
        <li class="feedback-nav-wrap">
            <a href="feedback.php"
               class="feedback-nav-btn <?=($pageTitle??'')==='Testimonials' ? 'active' : ''?>">
                <i class="fas fa-bell"></i>
                Testimonials
                <span class="notif-badge <?= $unreadFeedback === 0 ? 'hidden' : '' ?>">
                    <?= $unreadFeedback ?>
                </span>
            </a>
        </li>
        <li><a href="reservation.php" <?=($pageTitle??'')==='Reservation'?'class="active"':''?>>Reservation</a></li>
        <li><a href="#" id="themeToggle" style="cursor:pointer"><i class="fas fa-moon"></i> Theme</a></li>
        <li><a href="../logout.php" class="btn-logout-nav">Log out</a></li>
    </ul>
</nav>
<script>
// Dark Mode Toggle Logic
document.addEventListener("DOMContentLoaded", function() {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    const root = document.documentElement;
    const icon = themeToggle.querySelector('i');

    // Update icon initially
    if(root.getAttribute('data-theme') === 'dark') {
        icon.classList.replace('fa-moon', 'fa-sun');
    }

    themeToggle.addEventListener('click', (e) => {
        e.preventDefault();
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
});
</script>