<?php
$pageTitle = 'Sit-in';
require_once 'auth.php';

// Handle timeout (end sit-in session)
if (isset($_GET['timeout'])) {
    $id = (int)$_GET['timeout'];
    $pdo->prepare("UPDATE sit_in_records SET status='Done', time_out=NOW() WHERE id=?")->execute([$id]);
    header("Location: sitin.php?msg=timeout"); exit();
}

// Fetch pending sit-in requests
$pendingCount = $pdo->query("SELECT COUNT(*) FROM sit_in_records WHERE status='Pending'")->fetchColumn();
$pending      = $pdo->query("SELECT * FROM sit_in_records WHERE status='Pending' ORDER BY time_in DESC")->fetchAll();

// Fetch active sit-in sessions
$activeCount  = $pdo->query("SELECT COUNT(*) FROM sit_in_records WHERE status='Active'")->fetchColumn();
$active       = $pdo->query("SELECT * FROM sit_in_records WHERE status='Active' ORDER BY time_in DESC")->fetchAll();

include 'header.php';
?>

<style>
    .sitin-table { width:100%; border-collapse:collapse; font-size:13px; }
    .sitin-table thead th {
        background: linear-gradient(90deg, var(--pdark), var(--purple));
        color: #fff; padding: 10px 14px; text-align: left; font-weight: 600;
    }
    .sitin-table tbody tr:nth-child(even) { background: #f8f4ff; }
    .sitin-table tbody tr:hover { background: #f0e8ff; }
    .sitin-table tbody td { padding: 9px 14px; border-bottom: 1px solid #ede6f5; vertical-align: middle; }
    .sitin-table tbody td form { display: inline; margin: 0; }
    .section-badge {
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--gold); color: var(--pdark);
        font-size: 12px; font-weight: 700;
        width: 24px; height: 24px; border-radius: 50%;
        margin-left: 8px; vertical-align: middle;
    }
    .section-badge.green { background: #28a745; color: #fff; }
    .empty-row { text-align: center; color: #999; padding: 20px !important; }
</style>

<div class="page-content">
    <div class="page-title">Sit-in Management</div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'timeout'): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> Session ended successfully.</div>
    <?php endif; ?>

    <!-- Pending Requests -->
    <div class="table-card" style="margin-bottom:28px">
        <h3>
            <i class="fas fa-clock" style="color:var(--gold)"></i>
            Pending Sit-in Requests
            <span class="section-badge"><?= $pendingCount ?></span>
        </h3>
        <table class="sitin-table">
            <thead>
                <tr>
                    <th>Sit ID</th>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Purpose</th>
                    <th>Lab</th>
                    <th>Time In</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pending)): ?>
                <tr><td colspan="7" class="empty-row">No pending requests.</td></tr>
            <?php else: ?>
                <?php foreach ($pending as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['id_number']) ?></td>
                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                    <td><?= htmlspecialchars($row['lab']) ?></td>
                    <td><?= htmlspecialchars($row['time_in']) ?></td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                        <form method="POST" action="sitin_action.php">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" name="action" value="approve" class="btn-sm btn-success">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        </form>
                        <form method="POST" action="sitin_action.php">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" name="action" value="disapprove" class="btn-sm btn-danger"
                                onclick="return confirm('Disapprove this sit-in request?')">
                                <i class="fas fa-times"></i> Disapprove
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Active Sessions -->
    <div class="table-card">
        <h3>
            <i class="fas fa-door-open" style="color:#28a745"></i>
            Currently Active Sit-in Sessions
            <span class="section-badge green"><?= $activeCount ?></span>
        </h3>
        <table class="sitin-table">
            <thead>
                <tr>
                    <th>Sit ID</th>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Purpose</th>
                    <th>Lab</th>
                    <th>Time In</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($active)): ?>
                <tr><td colspan="7" class="empty-row">No active sit-in sessions.</td></tr>
            <?php else: ?>
                <?php foreach ($active as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['id_number']) ?></td>
                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                    <td><?= htmlspecialchars($row['lab']) ?></td>
                    <td><?= htmlspecialchars($row['time_in']) ?></td>
                    <td>
                        <a href="?timeout=<?= $row['id'] ?>" class="btn-sm btn-warning"
                            onclick="return confirm('End this sit-in session?')">
                            <i class="fas fa-sign-out-alt"></i> Time Out
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>