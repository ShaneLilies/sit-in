<?php
$pageTitle = 'Reservation';
require_once 'auth.php';


if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $pdo->prepare("UPDATE reservations SET status='Approved' WHERE id=? AND status='Pending'")->execute([$id]);
    header("Location: reservation.php?msg=approved"); exit();
}
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $pdo->prepare("UPDATE reservations SET status='Rejected' WHERE id=? AND status='Pending'")->execute([$id]);
    header("Location: reservation.php?msg=rejected"); exit();
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM reservations WHERE id=?")->execute([$id]);
    header("Location: reservation.php?msg=deleted"); exit();
}

// Get global reservation status
$setting = $pdo->query("SELECT setting_value FROM settings WHERE setting_name='reservations_enabled'");
if ($setting) {
    $val = $setting->fetchColumn();
    $reservations_enabled = ($val === false) ? true : (bool)$val;
} else {
    $reservations_enabled = true; // Default
}

$counts = $pdo->query("SELECT status, COUNT(*) as cnt FROM reservations GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$pending  = $counts['Pending']  ?? 0;
$approved = $counts['Approved'] ?? 0;
$rejected = $counts['Rejected'] ?? 0;

$reservations = $pdo->query("SELECT * FROM reservations WHERE status != 'Disabled' ORDER BY created_at DESC")->fetchAll();
include 'header.php';
?>
<div class="page-content">
    <div class="page-title">Reservation</div>

    <?php if(isset($_GET['msg'])): ?>
        <?php if($_GET['msg']==='approved'): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> Reservation approved.</div>
        <?php elseif($_GET['msg']==='rejected'): ?>
        <div class="alert-danger"><i class="fas fa-times-circle"></i> Reservation rejected.</div>
        <?php elseif($_GET['msg']==='deleted'): ?>
        <div class="alert-danger"><i class="fas fa-trash"></i> Reservation deleted.</div>
        <?php elseif($_GET['msg']==='settings_updated'): ?>
        <div class="alert-success"><i class="fas fa-info-circle"></i> Global reservation settings updated.</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="stat-cards" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
        <div class="stat-card gold"><h3>Pending</h3><div class="num"><?= $pending ?></div></div>
        <div class="stat-card green"><h3>Approved</h3><div class="num"><?= $approved ?></div></div>
        <div class="stat-card"><h3>Rejected</h3><div class="num" style="color:#dc3545"><?= $rejected ?></div></div>
    </div>

    <div class="table-card">
        <h3><i class="fas fa-calendar-check"></i> Reservation Requests</h3>
        <table id="resTable" class="dataTable">
            <thead>
                <tr>
                    <th>ID</th><th>ID Number</th><th>Student Name</th>
                    <th>Purpose</th><th>Lab</th><th>PC No.</th><th>Date</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($reservations as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['id_number']) ?></td>
                <td><?= htmlspecialchars($r['student_name']) ?></td>
                <td><?= htmlspecialchars($r['purpose']) ?></td>
                <td><?= htmlspecialchars($r['lab']) ?></td>
                <td><?= htmlspecialchars($r['pc_no'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($r['reserved_date']) ?></td>
                <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                <td style="display:flex;gap:6px;flex-wrap:wrap">
                    <?php if($r['status']==='Pending'): ?>
                    <a href="?approve=<?= $r['id'] ?>" class="btn-sm btn-success"
                        onclick="return confirm('Approve this reservation?')">Approve</a>
                    <a href="?reject=<?= $r['id'] ?>" class="btn-sm btn-danger"
                        onclick="return confirm('Reject this reservation?')">Reject</a>
                    <?php else: ?>
                    <span style="color:#999;font-size:12px"><?= $r['status'] ?></span>
                    <?php endif; ?>

                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($reservations)): ?>
            <tr><td colspan="9" style="text-align:center;color:#999;padding:20px">No reservation requests yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>$(document).ready(function(){ $('#resTable').DataTable({ order: [[6,'desc']] }); });</script>
</body>
</html>