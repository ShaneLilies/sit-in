<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php"); exit();
}
require_once '../db.php';
$stmt = $pdo->prepare("SELECT * FROM students WHERE id_number = ?");
$stmt->execute([$_SESSION['user']]);
$student = $stmt->fetch();

$records = $pdo->prepare("SELECT * FROM sit_in_records WHERE id_number = ? ORDER BY time_in DESC");
$records->execute([$_SESSION['user']]);
$history = $records->fetchAll();

// Get all feedbacks already submitted by this student
$fbStmt = $pdo->prepare("SELECT sit_in_id FROM feedback WHERE id_number = ?");
$fbStmt = $pdo->prepare("SELECT sit_in_id FROM feedback WHERE id_number = ?");
$submittedFeedbacks = array_column($fbStmt->fetchAll(), 'sit_in_id');

// Get all feedbacks to display in table
$myFeedbacks = $pdo->prepare("
    SELECT f.*, s.purpose, s.lab, s.time_in
    FROM feedback f
    LEFT JOIN sit_in_records s ON s.id = f.sit_in_id
    WHERE f.id_number = ?
    ORDER BY f.created_at DESC
");
$myFeedbacks->execute([$_SESSION['user']]);
$feedbackList = $myFeedbacks->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | History</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
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

        .page-content{padding:28px 32px;max-width:1200px;margin:0 auto}
        .page-title{font-size:22px;font-weight:700;color:var(--pdark);margin-bottom:20px;border-left:4px solid var(--gold);padding-left:12px}
        .table-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 4px 20px rgba(91,45,142,0.10);margin-bottom:24px}
        .table-card h3{font-size:15px;font-weight:700;color:var(--pdark);margin-bottom:16px;display:flex;align-items:center;gap:8px}
        .table-card h3 i{color:var(--gold)}

        table.dataTable{width:100%!important;border-collapse:collapse}
        table.dataTable thead th{background:linear-gradient(90deg,var(--pdark),var(--purple));color:#fff;padding:10px 14px;font-size:13px;font-weight:600;border:none}
        table.dataTable tbody tr:nth-child(even){background:#f8f4ff}
        table.dataTable tbody tr:hover{background:#f0e8ff}
        table.dataTable tbody td{padding:9px 14px;font-size:13px;border-bottom:1px solid #ede6f5}
        .dataTables_wrapper .dataTables_filter input{border:1.5px solid #ccc;border-radius:6px;padding:5px 10px;font-size:13px;outline:none}
        .dataTables_wrapper .dataTables_filter input:focus{border-color:var(--purple)}
        .dataTables_wrapper .dataTables_paginate .paginate_button.current{background:var(--purple)!important;color:#fff!important;border-color:var(--purple)!important;border-radius:5px}
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover{background:var(--bg)!important;color:var(--pdark)!important;border-radius:5px}

        .badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
        .badge-active{background:#d4edda;color:#155724}
        .badge-done{background:#e2e3e5;color:#383d41}

        /* Feedback button */
        .btn-feedback{
            display:inline-flex;align-items:center;gap:5px;
            background:linear-gradient(90deg,var(--gold),var(--gdark));
            color:var(--pdark);font-size:11.5px;font-weight:700;
            padding:5px 12px;border-radius:6px;border:none;cursor:pointer;
            font-family:'Inter',sans-serif;transition:all 0.2s;
            box-shadow:0 2px 8px rgba(240,180,41,0.3);
        }
        .btn-feedback:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(240,180,41,0.4)}
        .btn-feedback-done{
            display:inline-flex;align-items:center;gap:5px;
            background:#e2e3e5;color:#555;font-size:11.5px;font-weight:600;
            padding:5px 12px;border-radius:6px;border:none;cursor:not-allowed;
            font-family:'Inter',sans-serif;
        }

        /* Modal */
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(61,26,110,0.55);z-index:9999;align-items:center;justify-content:center}
        .modal-overlay.show{display:flex}
        .modal-box{background:#fff;border-radius:16px;padding:28px 32px;width:100%;max-width:460px;position:relative;box-shadow:0 20px 60px rgba(91,45,142,0.35);animation:slideUp 0.3s ease}
        @keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .modal-box h3{font-size:17px;font-weight:700;color:var(--pdark);margin-bottom:6px;border-left:3px solid var(--gold);padding-left:10px}
        .modal-subtitle{font-size:12.5px;color:#999;margin-bottom:20px;padding-left:13px}
        .modal-close{position:absolute;top:12px;right:16px;background:none;border:none;font-size:22px;cursor:pointer;color:#999}
        .modal-close:hover{color:var(--pdark)}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-size:12px;font-weight:700;color:var(--pdark);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:6px}
        .form-group textarea{width:100%;padding:10px 12px;border:1.5px solid #ddd;border-radius:8px;font-size:13.5px;font-family:'Inter',sans-serif;outline:none;transition:border-color 0.2s;background:#faf8ff;resize:vertical;min-height:100px}
        .form-group textarea:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(91,45,142,0.08);background:#fff}

        /* Star rating */
        .star-rating{display:flex;gap:6px;flex-direction:row-reverse;justify-content:flex-end}
        .star-rating input{display:none}
        .star-rating label{font-size:28px;color:#ddd;cursor:pointer;transition:color 0.15s}
        .star-rating label:hover,.star-rating label:hover~label,.star-rating input:checked~label{color:var(--gold)}

        .modal-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
        .btn-cancel{padding:9px 20px;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:#eee;color:#555;font-family:'Inter',sans-serif}
        .btn-submit{padding:9px 22px;border-radius:8px;font-size:13px;font-weight:700;border:none;cursor:pointer;background:linear-gradient(90deg,var(--pdark),var(--purple));color:#fff;font-family:'Inter',sans-serif}
        .btn-submit:hover{background:linear-gradient(90deg,var(--purple),var(--plight))}

        /* Feedback table stars */
        .star-display{color:var(--gold);font-size:13px;letter-spacing:1px}

        .alert-success{background:#f0fff4;border:1.5px solid #b2eec8;color:#1a7a3a;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:13px}
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
        <li><a href="history.php" class="active">History</a></li>
        <li><a href="reservation.php">Reservation</a></li>
        <li><a href="../logout.php" class="btn-logout-nav">Log out</a></li>
    </ul>
</nav>

<div class="page-content">
    <div class="page-title">History</div>

    <?php if(isset($_GET['feedback_sent'])): ?>
    <div class="alert-success"><i class="fas fa-check-circle"></i> Feedback submitted successfully! Thank you.</div>
    <?php endif; ?>

    <!-- Sit-in History Table -->
    <div class="table-card">
        <h3><i class="fas fa-history"></i> Sit-in History</h3>
        <table id="historyTable" class="dataTable" style="width:100%">
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Purpose</th>
                    <th>Laboratory</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Feedback</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($history as $r): ?>
            <tr>
                <td><?=htmlspecialchars($r['id_number'])?></td>
                <td><?=htmlspecialchars($r['student_name'])?></td>
                <td><?=htmlspecialchars($r['purpose'])?></td>
                <td><?=htmlspecialchars($r['lab'])?></td>
                <td><?=$r['time_in'] ? date('h:i A', strtotime($r['time_in'])) : '--'?></td>
                <td><?=$r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '--'?></td>
                <td><?=$r['time_in'] ? date('Y-m-d', strtotime($r['time_in'])) : '--'?></td>
                <td><span class="badge <?=$r['status']==='Active'?'badge-active':'badge-done'?>"><?=$r['status']?></span></td>
                <td>
                    <?php if($r['status'] === 'Done'): ?>
                        <?php if(in_array($r['id'], $submittedFeedbacks)): ?>
                            <button class="btn-feedback-done" disabled>
                                <i class="fas fa-check"></i> Submitted
                            </button>
                        <?php else: ?>
                            <button class="btn-feedback"
                                onclick="openFeedback(<?=$r['id']?>, '<?=htmlspecialchars($r['purpose'])?>', '<?=htmlspecialchars($r['lab'])?>')">
                                <i class="fas fa-comment-alt"></i> Give Feedback
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:#bbb;font-size:12px">Session active</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($history)): ?>
            <tr><td colspan="9" style="text-align:center;color:#999;padding:20px">No history yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- My Feedback Table -->
    <div class="table-card">
        <h3><i class="fas fa-star"></i> My Submitted Feedback</h3>
        <table id="feedbackTable" class="dataTable" style="width:100%">
            <thead>
                <tr>
                    <th>Purpose</th>
                    <th>Laboratory</th>
                    <th>Session Date</th>
                    <th>Rating</th>
                    <th>Message</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($feedbackList as $f): ?>
            <tr>
                <td><?=htmlspecialchars($f['purpose'] ?? '--')?></td>
                <td><?=htmlspecialchars($f['lab'] ?? '--')?></td>
                <td><?=$f['time_in'] ? date('Y-m-d', strtotime($f['time_in'])) : '--'?></td>
                <td>
                    <span class="star-display">
                        <?= str_repeat('★', (int)$f['rating']) . str_repeat('☆', 5 - (int)$f['rating']) ?>
                    </span>
                    <span style="font-size:11px;color:#888;margin-left:4px">(<?=$f['rating']?>/5)</span>
                </td>
                <td><?=htmlspecialchars($f['message'])?></td>
                <td><?=date('M d, Y', strtotime($f['created_at']))?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($feedbackList)): ?>
            <tr><td colspan="6" style="text-align:center;color:#999;padding:20px">No feedback submitted yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Feedback Modal -->
<div class="modal-overlay" id="feedbackModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal()">×</button>
        <h3><i class="fas fa-comment-alt" style="color:var(--gold);margin-right:6px"></i> Give Feedback</h3>
        <p class="modal-subtitle" id="feedbackSubtitle">Session feedback</p>
        <form method="POST" action="feedback_action.php">
            <input type="hidden" name="sit_in_id" id="feedbackSitinId">
            <div class="form-group">
                <label>Rate your experience</label>
                <div class="star-rating">
                    <input type="radio" name="rating" id="s5" value="5"><label for="s5" title="Excellent">★</label>
                    <input type="radio" name="rating" id="s4" value="4"><label for="s4" title="Good">★</label>
                    <input type="radio" name="rating" id="s3" value="3" checked><label for="s3" title="Okay">★</label>
                    <input type="radio" name="rating" id="s2" value="2"><label for="s2" title="Poor">★</label>
                    <input type="radio" name="rating" id="s1" value="1"><label for="s1" title="Terrible">★</label>
                </div>
            </div>
            <div class="form-group">
                <label>Your Message</label>
                <textarea name="message" placeholder="Share your experience about this sit-in session..." required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit Feedback</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function(){
    $('#historyTable').DataTable({order:[[6,'desc']], columnDefs:[{orderable:false, targets:8}]});  // ← NEW LINE
    $('#feedbackTable').DataTable({order:[[5,'desc']]});
});
function openFeedback(sitinId, purpose, lab) {
    document.getElementById('feedbackSitinId').value = sitinId;
    document.getElementById('feedbackSubtitle').textContent = 'Purpose: ' + purpose + ' | Lab: ' + lab;
    document.getElementById('feedbackModal').classList.add('show');
}
function closeModal() {
    document.getElementById('feedbackModal').classList.remove('show');
}
// Close modal on overlay click
document.getElementById('feedbackModal').addEventListener('click', function(e){
    if(e.target === this) closeModal();
});
</script>
</body>
</html>