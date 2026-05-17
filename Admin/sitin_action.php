<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit();
}

// Helper to free a PC
function freePC($pdo, $record_id) {
    $stmt = $pdo->prepare("SELECT lab, pc_no FROM sit_in_records WHERE id = ?");
    $stmt->execute([$record_id]);
    $rec = $stmt->fetch();
    if ($rec && $rec['pc_no'] !== 'N/A') {
        $pdo->prepare("UPDATE lab_pcs SET status = 'Available' WHERE lab_name = ? AND pc_number = ?")
            ->execute([$rec['lab'], $rec['pc_no']]);
    }
}

// ── INSERT NEW SIT-IN (from search.php modal) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_number']) && !isset($_POST['action'])) {
    $id_number    = trim($_POST['id_number'] ?? '');
    $student_name = trim($_POST['student_name'] ?? '');
    $purpose      = trim($_POST['purpose'] ?? '');
    $lab          = trim($_POST['lab'] ?? '');

    if ($id_number && $student_name && $purpose && $lab) {
        $chk = $pdo->prepare("SELECT remaining_session FROM students WHERE id_number = ?");
        $chk->execute([$id_number]);
        $student = $chk->fetch();

        if ($student && $student['remaining_session'] > 0) {
            // Find an available PC automatically for admin-initiated sit-in
            $pc_stmt = $pdo->prepare("SELECT pc_number FROM lab_pcs WHERE lab_name = ? AND status = 'Available' LIMIT 1");
            $pc_stmt->execute([$lab]);
            $avail_pc = $pc_stmt->fetchColumn();
            $pc_no = $avail_pc ? $avail_pc : 'N/A';

            if ($avail_pc) {
                $pdo->prepare("UPDATE lab_pcs SET status = 'Occupied' WHERE lab_name = ? AND pc_number = ?")->execute([$lab, $pc_no]);
            }

            $pdo->prepare("INSERT INTO sit_in_records (id_number, student_name, purpose, lab, status, time_in, pc_no)
                           VALUES (?, ?, ?, ?, 'Active', NOW(), ?)")
                ->execute([$id_number, $student_name, $purpose, $lab, $pc_no]);

            $pdo->prepare("UPDATE students SET remaining_session = GREATEST(remaining_session - 1, 0) WHERE id_number = ?")
                ->execute([$id_number]);

            header("Location: sitin.php?msg=sitin_ok"); exit();
        } else {
            header("Location: search.php?error=no_sessions"); exit();
        }
    }

    header("Location: search.php"); exit();
}

// ── APPROVE / DISAPPROVE / TIMEOUT (from sitin.php buttons) ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $id     = (int)$_POST['id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $pdo->prepare("SELECT id_number FROM sit_in_records WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();

        if ($record) {
            $id_number = $record['id_number'];
            $pdo->prepare("UPDATE sit_in_records SET status = 'Active' WHERE id = ?")->execute([$id]);
            $pdo->prepare("UPDATE students SET remaining_session = GREATEST(remaining_session - 1, 0) WHERE id_number = ?")->execute([$id_number]);
        }

    } elseif ($action === 'disapprove') {
        freePC($pdo, $id);
        $pdo->prepare("UPDATE sit_in_records SET status = 'Disapproved' WHERE id = ?")->execute([$id]);

    } elseif ($action === 'timeout') {
        freePC($pdo, $id);
        $pdo->prepare("UPDATE sit_in_records SET status = 'Done', time_out = NOW() WHERE id = ?")->execute([$id]);
    }

    header("Location: sitin.php"); exit();
}

// ── TIMEOUT via GET (from sitin.php Time Out link) ─────────────────────────
if (isset($_GET['timeout'])) {
    $id = (int)$_GET['timeout'];
    freePC($pdo, $id);
    $pdo->prepare("UPDATE sit_in_records SET status = 'Done', time_out = NOW() WHERE id = ?")->execute([$id]);
    header("Location: sitin.php?msg=timeout"); exit();
}

header("Location: sitin.php"); exit();
?>