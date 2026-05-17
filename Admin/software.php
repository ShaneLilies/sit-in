<?php
$pageTitle = 'Software Management';
require_once 'auth.php';

$success = $error = '';

// Handle Single Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_single'])) {
    $lab = trim($_POST['lab'] ?? '');
    $software = trim($_POST['software_name'] ?? '');
    if ($lab && $software) {
        $pdo->prepare("INSERT INTO lab_software (lab, software_name) VALUES (?, ?)")->execute([$lab, $software]);
        $success = "Software added successfully.";
    } else {
        $error = "Please fill all fields.";
    }
}

// Handle CSV Import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
                $count = 0;
                $stmt = $pdo->prepare("INSERT INTO lab_software (lab, software_name) VALUES (?, ?)");
                // Skip header if needed (we'll just try to insert everything and ignore empty)
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    if (count($data) >= 2) {
                        $lab = trim($data[0]);
                        $software = trim($data[1]);
                        if (strtolower($lab) !== 'lab' && $lab && $software) { // weak header check
                            $stmt->execute([$lab, $software]);
                            $count++;
                        }
                    }
                }
                fclose($handle);
                $success = "$count software applications imported successfully.";
            } else {
                $error = "Failed to open the uploaded file.";
            }
        } else {
            $error = "Please upload a valid CSV file.";
        }
    } else {
        $error = "Error uploading file.";
    }
}

// Handle Delete
if (isset($_GET['del'])) {
    $pdo->prepare("DELETE FROM lab_software WHERE id = ?")->execute([$_GET['del']]);
    header("Location: software.php"); exit();
}

$software_list = $pdo->query("SELECT * FROM lab_software ORDER BY id DESC")->fetchAll();

include 'header.php';
?>
<div class="page-content">
    <div class="page-title">Software App Management</div>
    
    <?php if($success): ?><div style="background:#d4edda;color:#155724;padding:12px;border-radius:8px;margin-bottom:20px;border:1px solid #c3e6cb;"><i class="fas fa-check-circle"></i> <?=$success?></div><?php endif; ?>
    <?php if($error): ?><div style="background:#f8d7da;color:#721c24;padding:12px;border-radius:8px;margin-bottom:20px;border:1px solid #f5c6cb;"><i class="fas fa-exclamation-circle"></i> <?=$error?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
        <div class="table-card" style="margin-bottom:0;">
            <h3><i class="fas fa-plus-circle"></i> Add Single Software</h3>
            <form method="POST">
                <div style="margin-bottom:14px">
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--pdark);margin-bottom:5px;">Laboratory</label>
                    <select name="lab" required style="width:100%;padding:10px;border:1.5px solid #ddd;border-radius:8px;outline:none;">
                        <option value="">-- Select Lab --</option>
                        <option>524</option><option>526</option><option>528</option><option>530</option><option>542</option>
                    </select>
                </div>
                <div style="margin-bottom:14px">
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--pdark);margin-bottom:5px;">Software Name</label>
                    <input type="text" name="software_name" required placeholder="e.g. Visual Studio 2022" style="width:100%;padding:10px;border:1.5px solid #ddd;border-radius:8px;outline:none;">
                </div>
                <button type="submit" name="add_single" style="background:var(--gold);color:var(--pdark);font-weight:700;padding:10px 20px;border-radius:8px;border:none;cursor:pointer;">Add Software</button>
            </form>
        </div>

        <div class="table-card" style="margin-bottom:0;">
            <h3><i class="fas fa-file-import"></i> Import Software (CSV)</h3>
            <p style="font-size:12px;color:#666;margin-bottom:14px;">Upload a CSV file containing two columns: <b>Lab</b> and <b>Software Name</b>. Example format:<br><br><code>524, Python 3.10<br>526, Node.js</code></p>
            <form method="POST" enctype="multipart/form-data">
                <div style="margin-bottom:14px">
                    <input type="file" name="csv_file" accept=".csv" required style="width:100%;padding:10px;border:1.5px dashed #aaa;border-radius:8px;outline:none;background:#faf8ff;">
                </div>
                <button type="submit" name="import_csv" style="background:var(--purple);color:#fff;font-weight:700;padding:10px 20px;border-radius:8px;border:none;cursor:pointer;"><i class="fas fa-upload"></i> Upload & Import</button>
            </form>
        </div>
    </div>

    <div class="table-card">
        <h3><i class="fas fa-desktop"></i> Installed Software Directory</h3>
        <table id="softwareTable" class="dataTable" style="width:100%;">
            <thead><tr><th>ID</th><th>Lab</th><th>Software Name</th><th>Date Added</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach($software_list as $s): ?>
                <tr>
                    <td><?=$s['id']?></td>
                    <td><b><?=htmlspecialchars($s['lab'])?></b></td>
                    <td><?=htmlspecialchars($s['software_name'])?></td>
                    <td><?=date('Y-m-d', strtotime($s['date_added']))?></td>
                    <td><a href="?del=<?=$s['id']?>" onclick="return confirm('Remove this software?')" style="color:#dc3545;text-decoration:none;"><i class="fas fa-trash"></i></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function(){
    $('#softwareTable').DataTable({
        order: [[0, 'desc']]
    });
});
</script>
</body></html>
