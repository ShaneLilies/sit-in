<?php
require_once 'db.php';
$labs = ['524', '526', '528', '530', '542'];
foreach ($labs as $lab) {
    for ($i = 1; $i <= 50; $i++) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO lab_pcs (lab_name, pc_number, status) VALUES (?, ?, 'Available')");
        $stmt->execute([$lab, $i]);
    }
}
echo "Populated lab_pcs successfully.\n";
