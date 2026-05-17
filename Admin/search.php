<?php
$pageTitle = 'Search';
require_once 'auth.php';

$student = null;
$searched = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $q = trim($_POST['search']);
    $searched = true;
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id_number LIKE ? OR fname LIKE ? OR lname LIKE ? OR email LIKE ?");
    $like = "%$q%";
    $stmt->execute([$like,$like,$like,$like]);
    $results = $stmt->fetchAll();
}

include 'header.php';
?>
<div class="page-content">
    <div class="page-title">Search Student</div>
    <div class="table-card" style="max-width:500px">
        <form method="POST">
            <div class="form-group">
                <input type="text" name="search" placeholder="Search by ID, Name, or Email..." value="<?=htmlspecialchars($_POST['search']??'')?>">
            </div>
            <button type="submit" class="btn-gold"><i class="fas fa-search"></i> Search</button>
        </form>
    </div>

    <?php if ($searched): ?>
    <div class="table-card">
        <h3><i class="fas fa-users"></i> Search Results</h3>
        <?php if (!empty($results)): ?>
        <table class="dataTable" id="searchTable">
            <thead><tr><th>ID Number</th><th>Name</th><th>Course</th><th>Year</th><th>Email</th><th>Sessions Left</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($results as $s): ?>
            <tr>
                <td><?=htmlspecialchars($s['id_number'])?></td>
                <td><?=htmlspecialchars($s['lname'].', '.$s['fname'])?></td>
                <td><?=htmlspecialchars($s['course'])?></td>
                <td><?=htmlspecialchars($s['course_lvl'])?></td>
                <td><?=htmlspecialchars($s['email'])?></td>
                <td><?=htmlspecialchars($s['remaining_session'])?></td>
                <td>
                    <button class="btn-sm btn-primary" onclick="openSitin('<?=htmlspecialchars($s['id_number'])?>','<?=htmlspecialchars($s['fname'].' '.$s['lname'])?>','<?=htmlspecialchars($s['remaining_session'])?>')">
                        <i class="fas fa-door-open"></i> Sit-in
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:#999;font-size:13px">No students found.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Sit-in Modal -->
<div class="modal-overlay" id="sitinModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal()">×</button>
        <h3>Sit In Form</h3>
        <form method="POST" action="sitin_action.php">
            <div class="form-group"><label>ID Number</label><input type="text" name="id_number" id="modal_id" readonly></div>
            <div class="form-group"><label>Student Name</label><input type="text" name="student_name" id="modal_name" readonly></div>
            <div class="form-group">
                <label>Purpose</label>
                <select name="purpose">
                    <option>C Programming</option><option>Java</option><option>PHP</option>
                    <option>Python</option><option>ASP.Net</option><option>C#</option>
                    <option>Database</option><option>Others</option>
                </select>
            </div>
            <div class="form-group"><label>Lab</label>
                <select name="lab"><option>524</option><option>526</option><option>528</option><option>530</option><option>542</option></select>
            </div>
            <div class="form-group"><label>Remaining Session</label><input type="text" id="modal_session" readonly></div>
            <div class="modal-footer">
                <button type="button" class="btn-sm btn-danger" onclick="closeModal()">Close</button>
                <button type="submit" class="btn-sm btn-primary">Sit In</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function(){ if($('#searchTable').length) $('#searchTable').DataTable({paging:false}); });
function openSitin(id, name, session) {
    document.getElementById('modal_id').value = id;
    document.getElementById('modal_name').value = name;
    document.getElementById('modal_session').value = session;
    document.getElementById('sitinModal').classList.add('show');
}
function closeModal() { document.getElementById('sitinModal').classList.remove('show'); }
</script>
</body></html>
