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
    $redirect   = $_POST['redirect'] ?? 'history';

    if (empty($message) || $rating < 1 || $rating > 5) {
        if ($redirect === 'dashboard') {
            header("Location: ../dashboard.php?feedback_error=1");
        } else {
            header("Location: history.php?feedback_error=1");
        }
        exit();
    }

    $student_name = '';

    if ($sit_in_id > 0) {
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
        $student_name = $record['student_name'];
    } else {
        // General testimonial from dashboard
        $stmt = $pdo->prepare("SELECT fname, lname FROM students WHERE id_number = ?");
        $stmt->execute([$id_number]);
        $stud = $stmt->fetch();
        $student_name = $stud ? ($stud['fname'] . ' ' . $stud['lname']) : 'Student';
    }

    $sitInIdValue = ($sit_in_id > 0) ? $sit_in_id : null;

    // is_read = 0 so the admin badge lights up
    $pdo->prepare("INSERT INTO feedback (sit_in_id, id_number, student_name, rating, message, is_read)
                   VALUES (?, ?, ?, ?, ?, 0)")
        ->execute([$sitInIdValue, $id_number, $student_name, $rating, $message]);

    if ($redirect === 'dashboard') {
        header("Location: ../dashboard.php?feedback_sent=1");
    } else {
        header("Location: history.php?feedback_sent=1");
    }
    exit();
}

header("Location: history.php"); exit();
?>