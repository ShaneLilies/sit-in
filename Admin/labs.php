<?php
$pageTitle = 'Labs';
require_once 'auth.php';

// Handle Maintenance Toggle via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_pc'])) {
    $pc_id = (int)$_POST['pc_id'];
    $current_status = $_POST['current_status'];
    
    // Toggle Logic: Available <-> Maintenance
    $new_status = ($current_status === 'Available') ? 'Maintenance' : 'Available';
    
    // Do not toggle if Occupied (though UI should prevent this, good to be safe)
    if ($current_status !== 'Occupied') {
        $pdo->prepare("UPDATE lab_pcs SET status = ? WHERE id = ?")->execute([$new_status, $pc_id]);
    }
    exit; // Stop execution for AJAX
}

$labs = ['524', '526', '528', '530', '542'];

// Fetch all PCs grouped by lab
$all_pcs = [];
foreach ($labs as $lab) {
    $stmt = $pdo->prepare("SELECT id, pc_number, status FROM lab_pcs WHERE lab_name = ? ORDER BY pc_number ASC");
    $stmt->execute([$lab]);
    $all_pcs[$lab] = $stmt->fetchAll();
}

include 'header.php';
?>
<div class="page-content">
    <div class="page-title">Laboratory PC Management</div>
    
    <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(91,45,142,0.1);margin-bottom:24px;">
        <h3 style="color:var(--pdark);margin-bottom:15px;"><i class="fas fa-info-circle"></i> Legend & Instructions</h3>
        <div style="display:flex;gap:20px;font-size:13px;color:#555;align-items:center;">
            <span style="display:flex;align-items:center;gap:6px;"><div style="width:14px;height:14px;background:#28a745;border-radius:3px;"></div> Available</span>
            <span style="display:flex;align-items:center;gap:6px;"><div style="width:14px;height:14px;background:#dc3545;border-radius:3px;"></div> Occupied (In Use)</span>
            <span style="display:flex;align-items:center;gap:6px;"><div style="width:14px;height:14px;background:#fd7e14;border-radius:3px;"></div> Maintenance</span>
        </div>
        <p style="margin-top:12px;font-size:13px;color:#666;">Click on an <b>Available</b> or <b>Maintenance</b> PC to toggle its state. You cannot modify <b>Occupied</b> PCs until the student times out.</p>
    </div>

    <?php foreach($labs as $lab): ?>
    <div class="table-card">
        <h3><i class="fas fa-desktop"></i> Lab <?= htmlspecialchars($lab) ?></h3>
        <div class="pc-grid" style="display:grid;grid-template-columns:repeat(10,1fr);gap:10px;margin-top:15px;">
            <?php foreach($all_pcs[$lab] as $pc): ?>
            <div class="pc-box <?= $pc['status'] ?>" 
                 data-id="<?= $pc['id'] ?>" 
                 data-status="<?= $pc['status'] ?>"
                 style="aspect-ratio:1;border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:bold;color:#fff;cursor:<?= $pc['status']==='Occupied'?'not-allowed':'pointer' ?>;transition:0.2s;"
                 onclick="togglePC(this)">
                <?= $pc['pc_number'] ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
.pc-box.Available { background: #28a745; }
.pc-box.Occupied { background: #dc3545; opacity:0.8; }
.pc-box.Maintenance { background: #fd7e14; }
.pc-box:hover:not(.Occupied) { filter: brightness(1.2); transform: scale(1.05); }
</style>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
function togglePC(element) {
    const pcId = $(element).data('id');
    const currentStatus = $(element).data('status');
    
    if (currentStatus === 'Occupied') {
        alert("This PC is currently occupied by a student and cannot be modified.");
        return;
    }
    
    // Optimistic UI update
    const newStatus = (currentStatus === 'Available') ? 'Maintenance' : 'Available';
    $(element).removeClass(currentStatus).addClass(newStatus);
    $(element).data('status', newStatus);
    
    // AJAX Request
    $.post('labs.php', {
        toggle_pc: true,
        pc_id: pcId,
        current_status: currentStatus
    }).fail(function() {
        alert("Failed to update PC status.");
        // Revert UI on failure
        $(element).removeClass(newStatus).addClass(currentStatus);
        $(element).data('status', currentStatus);
    });
}
</script>
</body></html>
