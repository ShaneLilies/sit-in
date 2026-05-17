<?php
$pageTitle = 'Students';
require_once 'auth.php';

// Reset all sessions
if (isset($_GET['reset_all'])) {
    $pdo->query("UPDATE students SET remaining_session = 30");
    header("Location: students.php?msg=reset"); exit();
}
// Delete student
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM students WHERE id_number=?")->execute([$_GET['delete']]);
    header("Location: students.php"); exit();
}
// Edit student (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $pdo->prepare("UPDATE students SET fname=?,lname=?,course=?,course_lvl=?,email=?,remaining_session=? WHERE id_number=?")
        ->execute([$_POST['fname'],$_POST['lname'],$_POST['course'],$_POST['course_lvl'],$_POST['email'],$_POST['remaining_session'],$_POST['edit_id']]);
    header("Location: students.php"); exit();
}
// Add student (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_id'])) {
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    try {
        $pdo->prepare("INSERT INTO students (id_number,fname,lname,mname,course,course_lvl,email,address,password) VALUES(?,?,?,?,?,?,?,?,?)")
            ->execute([$_POST['add_id'],$_POST['add_fname'],$_POST['add_lname'],$_POST['add_mname'],$_POST['add_course'],$_POST['add_lvl'],$_POST['add_email'],$_POST['add_address'],$hash]);
    } catch(Exception $e) {}
    header("Location: students.php"); exit();
}

$students = $pdo->query("SELECT * FROM students ORDER BY id_number")->fetchAll();
include 'header.php';
?>
<div class="page-content">
    <div class="page-title">Students Information</div>
    <?php if(isset($_GET['msg'])): ?><div style="background:#d4edda;color:#155724;padding:10px 16px;border-radius:7px;margin-bottom:14px;font-size:13px">All sessions have been reset to 30.</div><?php endif; ?>

    <div style="display:flex;gap:10px;margin-bottom:18px">
        <button class="btn-sm btn-primary" onclick="document.getElementById('addModal').classList.add('show')"><i class="fas fa-plus"></i> Add Students</button>
        <a href="?reset_all=1" class="btn-sm btn-danger" onclick="return confirm('Reset ALL student sessions to 30?')"><i class="fas fa-refresh"></i> Reset All Session</a>
    </div>

    <div class="table-card">
        <table id="studentsTable" class="dataTable">
            <thead><tr><th>ID Number</th><th>Name</th><th>Year Level</th><th>Course</th><th>Remaining Session</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach($students as $s): ?>
            <tr>
                <td><?=htmlspecialchars($s['id_number'])?></td>
                <td><?=htmlspecialchars($s['fname'].' '.$s['lname'])?></td>
                <td><?=htmlspecialchars($s['course_lvl'])?></td>
                <td><?=htmlspecialchars($s['course'])?></td>
                <td><?=htmlspecialchars($s['remaining_session'])?></td>
                <td>
                    <button class="btn-sm btn-primary" onclick="openEdit('<?=htmlspecialchars($s['id_number'])?>','<?=htmlspecialchars($s['fname'])?>','<?=htmlspecialchars($s['lname'])?>','<?=htmlspecialchars($s['course'])?>','<?=$s['course_lvl']?>','<?=htmlspecialchars($s['email'])?>','<?=$s['remaining_session']?>')">Edit</button>
                    <a href="?delete=<?=urlencode($s['id_number'])?>" class="btn-sm btn-danger" onclick="return confirm('Delete this student?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('show')">×</button>
        <h3>Edit Student</h3>
        <form method="POST">
            <input type="hidden" name="edit_id" id="edit_id">
            <div class="form-group"><label>First Name</label><input type="text" name="fname" id="edit_fname" required></div>
            <div class="form-group"><label>Last Name</label><input type="text" name="lname" id="edit_lname" required></div>
            <div class="form-group"><label>Course</label><input type="text" name="course" id="edit_course"></div>
            <div class="form-group"><label>Year Level</label><input type="number" name="course_lvl" id="edit_lvl" min="1" max="5"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email"></div>
            <div class="form-group"><label>Remaining Session</label><input type="number" name="remaining_session" id="edit_session" min="0" max="30"></div>
            <div class="modal-footer">
                <button type="button" class="btn-sm btn-danger" onclick="document.getElementById('editModal').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn-sm btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('show')">×</button>
        <h3>Add Student</h3>
        <form method="POST">
            <div class="form-group"><label>ID Number</label><input type="text" name="add_id" required></div>
            <div class="form-group"><label>First Name</label><input type="text" name="add_fname" required></div>
            <div class="form-group"><label>Last Name</label><input type="text" name="add_lname" required></div>
            <div class="form-group"><label>Middle Name</label><input type="text" name="add_mname"></div>
            <div class="form-group"><label>Course</label><input type="text" name="add_course" value="BSIT"></div>
            <div class="form-group"><label>Year Level</label><input type="number" name="add_lvl" value="1" min="1" max="5"></div>
            <div class="form-group"><label>Email</label><input type="email" name="add_email" required></div>
            <div class="form-group"><label>Address</label><input type="text" name="add_address"></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <div class="modal-footer">
                <button type="button" class="btn-sm btn-danger" onclick="document.getElementById('addModal').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn-sm btn-primary">Add Student</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function(){ $('#studentsTable').DataTable(); });
function openEdit(id,fname,lname,course,lvl,email,session) {
    document.getElementById('edit_id').value=id;
    document.getElementById('edit_fname').value=fname;
    document.getElementById('edit_lname').value=lname;
    document.getElementById('edit_course').value=course;
    document.getElementById('edit_lvl').value=lvl;
    document.getElementById('edit_email').value=email;
    document.getElementById('edit_session').value=session;
    document.getElementById('editModal').classList.add('show');
}
</script>
</body></html>
