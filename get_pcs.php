<?php
session_start();
require_once 'db.php';

if (isset($_GET['lab'])) {
    $lab = trim($_GET['lab']);
    $stmt = $pdo->prepare("SELECT pc_number, status FROM lab_pcs WHERE lab_name = ? ORDER BY pc_number ASC");
    $stmt->execute([$lab]);
    $pcs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($pcs);
}
?>
