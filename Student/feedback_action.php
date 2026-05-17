<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php"); exit();
}
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sit_in_id  = (int)($_POST['sit_in_id'] ?? 0);
    $rating     = (int)($_POST['rating'] ?? 3);
    $message    = trim($_POST['message'] ?? '');
    $id_number  = $_SESSION['user'];

    if ($sit_in_id <= 0 || empty($message) || $rating < 1 || $rating > 5) {
        header("Location: history.php?feedback_error=1"); exit();
    }

    $check = $pdo->prepare("SELECT id, student_name FROM sit_in_records WHERE id = ? AND id_number = ? AND status = 'Done'");
    $check->execute([$sit_in_id, $id_number]);
    $record = $check->fetch();

    if (!$record) {
        header("Location: history.php?feedback_error=invalid"); exit();
    }

    $dup = $pdo->prepare("SELECT id FROM feedback WHERE sit_in_id = ? AND id_number = ?");
    $dup->execute([$sit_in_id, $id_number]);
    if ($dup->fetch()) {
        header("Location: history.php?feedback_error=duplicate"); exit();
    }

    // is_read = 0 so the admin badge lights up
    $pdo->prepare("INSERT INTO feedback (sit_in_id, id_number, student_name, rating, message, is_read)
                   VALUES (?, ?, ?, ?, ?, 0)")
        ->execute([$sit_in_id, $id_number, $record['student_name'], $rating, $message]);

    header("Location: history.php?feedback_sent=1"); exit();
}

header("Location: history.php"); exit();
?>