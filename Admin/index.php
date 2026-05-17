<?php
$pageTitle = 'Dashboard';
require_once 'auth.php';
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$currentSitin  = $pdo->query("SELECT COUNT(*) FROM sit_in_records WHERE status='Active'")->fetchColumn();
$totalSitin    = $pdo->query("SELECT COUNT(*) FROM sit_in_records")->fetchColumn();
$purposes      = $pdo->query("SELECT purpose, COUNT(*) as cnt FROM sit_in_records GROUP BY purpose")->fetchAll();
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 10")->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement'])) {
    $content = trim($_POST['announcement']);
    if ($content) { $pdo->prepare("INSERT INTO announcements (content, posted_by) VALUES (?, 'CCS Admin')")->execute([$content]); header("Location: index.php"); exit(); }
}
if (isset($_GET['del_ann'])) { $pdo->prepare("DELETE FROM announcements WHERE id=?")->execute([$_GET['del_ann']]); header("Location: index.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Admin | Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{--purple:#5B2D8E;--pdark:#3D1A6E;--plight:#7B4BB8;--gold:#F0B429;--gdark:#C88F0A;--bg:#f5f0ff}
        body{font-family:'Inter',sans-serif;background:var(--bg);min-height:100vh}
        .navbar{background:var(--pdark);padding:0 32px;height:58px;display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid var(--gold);box-shadow:0 2px 12px rgba(0,0,0,0.25)}
        .navbar-brand{color:#fff;font-size:14px;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:10px}
        .navbar-brand img{width:36px;height:36px;object-fit:contain;border-radius:50%}
        .nav-links{display:flex;align-items:center;gap:2px;list-style:none}
        .nav-links a{color:rgba(255,255,255,0.9);text-decoration:none;font-size:12.5px;font-weight:500;padding:6px 11px;border-radius:4px;transition:background 0.2s;white-space:nowrap}
        .nav-links a:hover,.nav-links a.active{background:rgba(255,255,255,0.18)}
        .btn-logout-nav{background:var(--gold)!important;color:var(--pdark)!important;font-weight:700!important;border-radius:5px!important}
        .btn-logout-nav:hover{background:var(--gdark)!important;color:#fff!important}
        .page-content{padding:28px 32px;max-width:1300px;margin:0 auto}
        .page-title{font-size:22px;font-weight:700;color:var(--pdark);margin-bottom:20px;border-left:4px solid var(--gold);padding-left:12px}
        .stat-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:28px}
        .stat-card{background:#fff;border-radius:12px;padding:20px 24px;box-shadow:0 2px 12px rgba(91,45,142,0.1);border-top:4px solid var(--purple);transition:transform 0.2s}
        .stat-card:hover{transform:translateY(-3px)}
        .stat-card:nth-child(2){border-color:#28a745}.stat-card:nth-child(3){border-color:var(--gold)}
        .stat-card h3{font-size:13px;color:#777;font-weight:500;margin-bottom:6px}
        .stat-card .num{font-size:32px;font-weight:700;color:var(--pdark)}
        .stat-card:nth-child(2) .num{color:#28a745}.stat-card:nth-child(3) .num{color:var(--gdark)}
        .two-col{display:grid;grid-template-columns:1fr 1fr;gap:24px}
        .table-card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 12px rgba(91,45,142,0.1);margin-bottom:24px}
        .table-card h3{font-size:15px;font-weight:700;color:var(--pdark);margin-bottom:16px;display:flex;align-items:center;gap:8px}
        .announce-textarea{width:100%;padding:10px 14px;border:1.5px solid #ddd;border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;outline:none;resize:vertical;transition:border-color 0.2s}
        .announce-textarea:focus{border-color:var(--purple)}
        .btn-gold{background:var(--gold);color:var(--pdark);font-weight:700;padding:9px 20px;border-radius:7px;border:none;cursor:pointer;font-size:13px;font-family:'Inter',sans-serif;transition:all 0.2s}
        .btn-gold:hover{background:var(--gdark);color:#fff}
        .announce-item{border-bottom:1px solid #f0ecf8;padding:10px 0}
        .announce-item:last-child{border-bottom:none}
        .announce-meta{font-weight:600;color:var(--pdark);font-size:12.5px;margin-bottom:3px;display:flex;align-items:center;justify-content:space-between}
        .announce-content{color:#555;font-size:13px;line-height:1.6}
        .btn-del{background:none;border:none;color:#dc3545;cursor:pointer;font-size:13px;padding:2px 6px}
        .posted-title{font-size:15px;font-weight:700;color:#222;margin:16px 0 8px}
        @media(max-width:900px){.stat-cards{grid-template-columns:1fr}.two-col{grid-template-columns:1fr}}
    </style>
</head>
<body>
<nav class="navbar">
    <a href="index.php" class="navbar-brand"><img src="../logos.png" alt="CCS">College of Computer Studies Admin</a>
    <ul class="nav-links">
        <li><a href="index.php" class="active">Home</a></li>
        <li><a href="search.php">Search</a></li>
        <li><a href="students.php">Students</a></li>
        <li><a href="sitin.php">Sit-in</a></li>
        <li><a href="records.php">View Sit-in Records</a></li>
        <li><a href="reports.php">Sit-in Reports</a></li>
        <li><a href="feedback.php">Feedback Reports</a></li>
        <li><a href="reservation.php">Reservation</a></li>
        <li><a href="../logout.php" class="btn-logout-nav">Log out</a></li>
    </ul>
</nav>
<div class="page-content">
    <div class="page-title">Dashboard</div>
    <div class="stat-cards">
        <div class="stat-card"><h3>Students Registered</h3><div class="num"><?=$totalStudents?></div></div>
        <div class="stat-card"><h3>Currently Sit-in</h3><div class="num"><?=$currentSitin?></div></div>
        <div class="stat-card"><h3>Total Sit-in</h3><div class="num"><?=$totalSitin?></div></div>
    </div>
    <div class="two-col">
        <div class="table-card">
            <h3><i class="fas fa-chart-pie" style="color:var(--purple)"></i> Sit-in by Purpose</h3>
            <canvas id="purposeChart" height="220"></canvas>
        </div>
        <div class="table-card">
            <h3><i class="fas fa-bullhorn" style="color:var(--gold)"></i> Announcement</h3>
            <form method="POST">
                <div style="margin-bottom:10px"><textarea name="announcement" rows="3" class="announce-textarea" placeholder="New Announcement..."></textarea></div>
                <button type="submit" class="btn-gold">Submit</button>
            </form>
            <div class="posted-title">Posted Announcement</div>
            <?php foreach($announcements as $ann): ?>
            <div class="announce-item">
                <div class="announce-meta">
                    <span><?=htmlspecialchars($ann['posted_by'])?> | <?=date('Y-M-d', strtotime($ann['created_at']))?></span>
                    <a href="?del_ann=<?=$ann['id']?>" class="btn-del" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                </div>
                <?php if($ann['content']): ?><div class="announce-content"><?=htmlspecialchars($ann['content'])?></div><?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if(empty($announcements)): ?><p style="color:#999;font-size:13px">No announcements yet.</p><?php endif; ?>
        </div>
    </div>
</div>
<script>
const labels=<?=json_encode(array_column($purposes,'purpose'))?>;
const data=<?=json_encode(array_column($purposes,'cnt'))?>;
const colors=['#5B2D8E','#F0B429','#dc3545','#28a745','#1565c0','#ff9800'];
if(labels.length>0){new Chart(document.getElementById('purposeChart'),{type:'pie',data:{labels,datasets:[{data,backgroundColor:colors.slice(0,labels.length),borderWidth:2}]},options:{plugins:{legend:{position:'bottom',labels:{font:{size:12}}}}}})}
else{document.getElementById('purposeChart').style.display='none';document.getElementById('purposeChart').insertAdjacentHTML('afterend','<p style="color:#999;font-size:13px;text-align:center;padding:20px">No sit-in data yet.</p>')}
</script>
</body></html>
