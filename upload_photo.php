<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php"); exit();
}
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowed)) {
        header("Location: dashboard.php?photo_error=invalid_type"); exit();
    }
    if ($file['size'] > $maxSize) {
        header("Location: dashboard.php?photo_error=too_large"); exit();
    }

    // Create upload directory if not exists
    $uploadDir = 'uploads/photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Delete old photo
    $stmt = $pdo->prepare("SELECT photo FROM students WHERE id_number = ?");
    $stmt->execute([$_SESSION['user']]);
    $old = $stmt->fetchColumn();
    if ($old && file_exists($uploadDir . $old)) {
        unlink($uploadDir . $old);
    }

    // Save new photo
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $_SESSION['user'] . '_' . time() . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $uploadDir . $filename);

    // Update DB
    $pdo->prepare("UPDATE students SET photo = ? WHERE id_number = ?")
        ->execute([$filename, $_SESSION['user']]);
}

header("Location: dashboard.php?photo_success=1");
exit();
?>
