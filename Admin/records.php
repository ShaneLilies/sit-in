<?php
$pageTitle = 'Records';
require_once 'auth.php';

$records = $pdo->query("SELECT * FROM sit_in_records ORDER BY time_in DESC")->fetchAll();
include 'header.php';
?>
<style>
.btn-export {
    background: #5B2D8E !important;
    color: #fff !important;
    border: none !important;
    border-radius: 5px !important;
    padding: 6px 12px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    margin-bottom: 10px !important;
}
.btn-export:hover {
    background: #3D1A6E !important;
}
</style>
<div class="page-content">
    <div class="page-title">View Sit-in Records</div>
    <div class="table-card">
        <h3 style="display:flex;justify-content:space-between;align-items:center;">
            <span><i class="fas fa-list"></i> All Sit-in Records</span>
        </h3>
        <table id="recordsTable" class="dataTable">
            <thead><tr><th>Sit ID</th><th>ID Number</th><th>Name</th><th>Purpose</th><th>Lab</th><th>Time In</th><th>Time Out</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach($records as $r): ?>
            <tr>
                <td><?=$r['id']?></td>
                <td><?=htmlspecialchars($r['id_number'])?></td>
                <td><?=htmlspecialchars($r['student_name'])?></td>
                <td><?=htmlspecialchars($r['purpose'])?></td>
                <td><?=htmlspecialchars($r['lab'])?></td>
                <td><?=htmlspecialchars($r['time_in'])?></td>
                <td><?=$r['time_out'] ? htmlspecialchars($r['time_out']) : '—'?></td>
                <td><span class="badge <?=$r['status']==='Active'?'badge-active':'badge-done'?>"><?=$r['status']?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<!-- DataTables Buttons CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function(){ 
    $('#recordsTable').DataTable({
        order: [[5,'desc']],
        dom: '<"top"Bf>rt<"bottom"lip><"clear">',
        buttons: [
            { extend: 'csv', text: '<i class="fas fa-file-csv"></i> Export CSV', className: 'btn-export' },
            { extend: 'pdf', text: '<i class="fas fa-file-pdf"></i> Export PDF', className: 'btn-export' }
        ]
    }); 
});
</script>
</body></html>
