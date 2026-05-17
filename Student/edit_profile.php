<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php"); exit();
}
require_once '../db.php';
$stmt = $pdo->prepare("SELECT * FROM students WHERE id_number = ?");
$stmt->execute([$_SESSION['user']]);
$student = $stmt->fetch();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024;
        if (!in_array($_FILES['photo']['type'], $allowed)) {
            $error = "Invalid file type. Please upload JPG, PNG, or GIF.";
        } elseif ($_FILES['photo']['size'] > $maxSize) {
            $error = "File too large. Max 2MB allowed.";
        } else {
            $uploadDir = '../uploads/photos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (!empty($student['photo']) && file_exists($uploadDir . $student['photo'])) {
                unlink($uploadDir . $student['photo']);
            }
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = $_SESSION['user'] . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename);
            $pdo->prepare("UPDATE students SET photo = ? WHERE id_number = ?")
                ->execute([$filename, $_SESSION['user']]);
            $student['photo'] = $filename;
        }
    }

    if (empty($error)) {
        $lname      = trim($_POST['lname'] ?? '');
        $fname      = trim($_POST['fname'] ?? '');
        $mname      = trim($_POST['mname'] ?? '');
        $course_lvl = trim($_POST['course_lvl'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $course     = trim($_POST['course'] ?? '');
        $address    = trim($_POST['address'] ?? '');
        $pdo->prepare("UPDATE students SET lname=?,fname=?,mname=?,course_lvl=?,email=?,course=?,address=? WHERE id_number=?")
            ->execute([$lname,$fname,$mname,$course_lvl,$email,$course,$address,$_SESSION['user']]);
        $_SESSION['name'] = $fname.' '.$lname;
        $success = "Profile updated successfully!";
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id_number = ?");
        $stmt->execute([$_SESSION['user']]);
        $student = $stmt->fetch();
    }
}

$avatar = '../logos.png';
if (!empty($student['photo']) && file_exists('../uploads/photos/' . $student['photo'])) {
    $avatar = '../uploads/photos/' . $student['photo'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Edit Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{--purple:#5B2D8E;--pdark:#3D1A6E;--plight:#7B4BB8;--gold:#F0B429;--gdark:#C88F0A;--bg:#f5f0ff}
        body{font-family:'Inter',sans-serif;background:var(--bg);min-height:100vh}
        .navbar{background:var(--pdark);padding:0 32px;height:58px;display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid var(--gold);box-shadow:0 2px 12px rgba(0,0,0,0.25)}
        .navbar-brand{color:#fff;font-size:14px;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:10px}
        .navbar-brand img{width:36px;height:36px;object-fit:contain;border-radius:0;}
        .nav-links{display:flex;align-items:center;gap:2px;list-style:none}
        .nav-links a{color:rgba(255,255,255,0.9);text-decoration:none;font-size:13px;font-weight:500;padding:6px 13px;border-radius:4px;transition:background 0.2s}
        .nav-links a:hover,.nav-links a.active{background:rgba(255,255,255,0.18)}
        .btn-logout-nav{background:var(--gold)!important;color:var(--pdark)!important;font-weight:700!important;border-radius:5px!important}
        .btn-logout-nav:hover{background:var(--gdark)!important;color:#fff!important}
        .dropdown{position:relative}.dropdown-toggle{cursor:pointer}
        .dropdown-toggle::after{content:' ▾';font-size:10px}
        .dropdown-menu{display:none;position:absolute;top:100%;left:0;background:#fff;border-radius:8px;box-shadow:0 4px 20px rgba(91,45,142,0.2);min-width:160px;z-index:1000;overflow:hidden}
        .dropdown:hover .dropdown-menu{display:block}
        .dropdown-menu a{display:block;color:#333!important;padding:10px 16px;font-size:13px}
        .dropdown-menu a:hover{background:var(--bg);color:var(--purple)!important}
        .page-content{display:flex;justify-content:center;padding:36px 20px}
        .edit-card{background:#fff;border-radius:16px;padding:36px 44px;width:100%;max-width:860px;box-shadow:0 4px 20px rgba(91,45,142,0.12);display:grid;grid-template-columns:1fr 300px;gap:40px;align-items:start}
        .form-title{font-size:22px;font-weight:700;color:var(--pdark);margin-bottom:4px;border-left:4px solid var(--gold);padding-left:12px}
        .form-subtitle{font-size:13px;color:#999;margin-bottom:22px;padding-left:16px}
        .alert-success{background:#f0fff4;border:1.5px solid #b2eec8;color:#1a7a3a;padding:10px 14px;border-radius:10px;margin-bottom:16px;font-size:13px}
        .alert-error{background:#fff0f0;border:1.5px solid #ffcccc;color:#c0392b;padding:10px 14px;border-radius:10px;margin-bottom:16px;font-size:13px}
        .field-group{margin-bottom:15px}
        .field-group label{display:block;font-size:11px;font-weight:700;color:var(--pdark);letter-spacing:0.8px;text-transform:uppercase;margin-bottom:5px}
        .input-wrap{position:relative}
        .input-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#bbb;font-size:13px}
        .field-group input{width:100%;padding:10px 12px 10px 36px;border:1.5px solid #ddd;border-radius:10px;font-size:13.5px;font-family:'Inter',sans-serif;color:#333;outline:none;transition:border-color 0.2s,box-shadow 0.2s;background:#faf8ff}
        .field-group input:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(91,45,142,0.08);background:#fff}
        .field-group input[readonly]{background:#f0ecf8;color:#777;cursor:not-allowed}
        .btn-save{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(90deg,var(--pdark),var(--purple));color:#fff;font-size:14px;font-weight:700;padding:11px 32px;border-radius:10px;border:none;cursor:pointer;font-family:'Inter',sans-serif;margin-top:6px;transition:all 0.2s;box-shadow:0 4px 14px rgba(91,45,142,0.3)}
        .btn-save:hover{background:linear-gradient(90deg,var(--purple),var(--plight));transform:translateY(-1px)}
        .right-panel{display:flex;flex-direction:column;align-items:center;gap:16px}
        .photo-section{background:linear-gradient(135deg,var(--pdark),var(--purple));border-radius:16px;padding:24px 20px;text-align:center;width:100%}
        .photo-wrap{position:relative;width:110px;height:110px;margin:0 auto 14px;cursor:pointer}
        .photo-wrap img{width:110px;height:110px;border-radius:50%;object-fit:cover;border:4px solid var(--gold);box-shadow:0 4px 16px rgba(0,0,0,0.3)}
        .photo-overlay{position:absolute;inset:0;border-radius:50%;background:rgba(0,0,0,0.5);display:flex;flex-direction:column;align-items:center;justify-content:center;opacity:0;transition:opacity 0.2s}
        .photo-wrap:hover .photo-overlay{opacity:1}
        .photo-overlay i{color:#fff;font-size:22px;margin-bottom:4px}
        .photo-overlay span{color:#fff;font-size:10px;font-weight:600}
        .photo-name{color:#fff;font-size:15px;font-weight:700;margin-bottom:2px}
        .photo-id{color:rgba(255,255,255,0.7);font-size:12px;margin-bottom:8px}
        .photo-badge{background:var(--gold);color:var(--pdark);border-radius:20px;padding:4px 14px;font-size:11px;font-weight:700;display:inline-block}
        #photoInput{display:none}
        .file-label{display:flex;align-items:center;justify-content:center;gap:8px;background:rgba(255,255,255,0.15);border:2px dashed rgba(255,255,255,0.5);border-radius:10px;padding:10px;font-size:12px;color:#fff;font-weight:600;cursor:pointer;transition:background 0.2s;margin-top:12px}
        .file-label:hover{background:rgba(255,255,255,0.25)}
        .file-hint{color:rgba(255,255,255,0.5);font-size:10.5px;margin-top:5px}
        .file-chosen{color:var(--gold);font-size:11px;margin-top:4px;font-weight:600}
        .info-box{background:var(--bg);border-radius:12px;padding:16px 18px;width:100%}
        .info-box-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #e8e0f0;font-size:13px}
        .info-box-item:last-child{border-bottom:none}
        .info-box-item i{color:var(--purple);width:16px;font-size:13px}
        .info-box-item .lbl{color:#888;font-size:12px;flex:1}
        .info-box-item .val{color:var(--pdark);font-weight:700;font-size:12px}
        @media(max-width:750px){.edit-card{grid-template-columns:1fr}}
    </style>
</head>
<body>
<nav class="navbar">
    <a href="../dashboard.php" class="navbar-brand">
        <img src="../logos.png" alt="CCS">
        College of Computer Studies
    </a>
    <ul class="nav-links">
        <li class="dropdown"><a class="dropdown-toggle"><i class="fas fa-bell"></i> Notification</a>
            <div class="dropdown-menu"><a href="#">No new notifications</a></div>
        </li>
        <li><a href="../dashboard.php">Home</a></li>
        <li><a href="edit_profile.php" class="active">Edit Profile</a></li>
        <li><a href="history.php">History</a></li>
        <li><a href="reservation.php">Reservation</a></li>
        <li><a href="../logout.php" class="btn-logout-nav">Log out</a></li>
    </ul>
</nav>
<div class="page-content">
    <div class="edit-card">
        <!-- LEFT: FORM -->
        <div>
            <div class="form-title">Edit Profile</div>
            <div class="form-subtitle">Update your personal information below</div>
            <?php if($success): ?><div class="alert-success"><i class="fas fa-check-circle"></i> <?=$success?></div><?php endif; ?>
            <?php if($error): ?><div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?=$error?></div><?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" id="photoInput" name="photo" accept="image/*" onchange="previewPhoto(this)">
                <div class="field-group">
                    <label>ID Number</label>
                    <div class="input-wrap"><i class="fas fa-id-card"></i>
                        <input type="text" value="<?=htmlspecialchars($student['id_number'])?>" readonly>
                    </div>
                </div>
                <div class="field-group">
                    <label>Last Name</label>
                    <div class="input-wrap"><i class="fas fa-user"></i>
                        <input type="text" name="lname" value="<?=htmlspecialchars($student['lname'])?>">
                    </div>
                </div>
                <div class="field-group">
                    <label>First Name</label>
                    <div class="input-wrap"><i class="fas fa-user"></i>
                        <input type="text" name="fname" value="<?=htmlspecialchars($student['fname'])?>">
                    </div>
                </div>
                <div class="field-group">
                    <label>Middle Name</label>
                    <div class="input-wrap"><i class="fas fa-user"></i>
                        <input type="text" name="mname" value="<?=htmlspecialchars($student['mname'])?>">
                    </div>
                </div>
                <div class="field-group">
                    <label>Year Level</label>
                    <div class="input-wrap"><i class="fas fa-layer-group"></i>
                        <input type="number" name="course_lvl" value="<?=htmlspecialchars($student['course_lvl'])?>" min="1" max="5">
                    </div>
                </div>
                <div class="field-group">
                    <label>Email</label>
                    <div class="input-wrap"><i class="fas fa-envelope"></i>
                        <input type="email" name="email" value="<?=htmlspecialchars($student['email'])?>">
                    </div>
                </div>
                <div class="field-group">
                    <label>Course</label>
                    <div class="input-wrap"><i class="fas fa-graduation-cap"></i>
                        <input type="text" name="course" value="<?=htmlspecialchars($student['course'])?>">
                    </div>
                </div>
                <div class="field-group">
                    <label>Address</label>
                    <div class="input-wrap"><i class="fas fa-map-marker-alt"></i>
                        <input type="text" name="address" value="<?=htmlspecialchars($student['address'])?>">
                    </div>
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>

        <!-- RIGHT: PHOTO + INFO -->
        <div class="right-panel">
            <div class="photo-section">
                <div class="photo-wrap" onclick="document.getElementById('photoInput').click()" title="Click to change photo">
                    <img src="<?=htmlspecialchars($avatar)?>" alt="Profile" id="photoPreview">
                    <div class="photo-overlay">
                        <i class="fas fa-camera"></i>
                        <span>Change Photo</span>
                    </div>
                </div>
                <div class="photo-name"><?=htmlspecialchars($student['fname'].' '.$student['lname'])?></div>
                <div class="photo-id"><?=htmlspecialchars($student['id_number'])?></div>
                <div class="photo-badge"><?=htmlspecialchars($student['course'])?></div>
                <label class="file-label" for="photoInput">
                    <i class="fas fa-upload"></i> Upload Photo
                </label>
                <div class="file-hint">JPG, PNG or GIF · Max 2MB</div>
                <div class="file-chosen" id="fileName"></div>
            </div>
            <div class="info-box">
                <div class="info-box-item"><i class="fas fa-layer-group"></i><span class="lbl">Year Level</span><span class="val"><?=htmlspecialchars($student['course_lvl'])?></span></div>
                <div class="info-box-item"><i class="fas fa-envelope"></i><span class="lbl">Email</span><span class="val" style="font-size:11px"><?=htmlspecialchars($student['email'])?></span></div>
                <div class="info-box-item"><i class="fas fa-clock"></i><span class="lbl">Sessions Left</span><span class="val"><?=htmlspecialchars($student['remaining_session'])?></span></div>
                <div class="info-box-item"><i class="fas fa-map-marker-alt"></i><span class="lbl">Address</span><span class="val"><?=htmlspecialchars($student['address'] ?: 'N/A')?></span></div>
            </div>
        </div>
    </div>
</div>
<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('photoPreview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
        document.getElementById('fileName').textContent = '📎 ' + input.files[0].name + ' — click Save Changes to upload';
    }
}
</script>
</body>
</html>