<?php
session_start();
if (isset($_SESSION['user'])) { header("Location: dashboard.php"); exit(); }
require_once 'db.php';
$error = ""; $success = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_number=trim($_POST['id_number']??'');
    $lname=trim($_POST['lname']??'');
    $fname=trim($_POST['fname']??'');
    $course_lvl=trim($_POST['course_lvl']??'');
    $password=$_POST['password']??'';
    $confirm=$_POST['confirm_password']??'';
    $email=trim($_POST['email']??'');
    $course=trim($_POST['course']??'');
    $mname=''; $address='';

    if(empty($id_number)||empty($lname)||empty($fname)||empty($password)||empty($email)||empty($course)||empty($course_lvl)){$error="Please fill in all required fields.";}
    elseif($password!==$confirm){$error="Passwords do not match.";}
    elseif(strlen($password)<8){$error="Password must be at least 8 characters.";}
    else{
        $stmt=$pdo->prepare("SELECT id FROM students WHERE id_number=? OR email=?");
        $stmt->execute([$id_number,$email]);
        if($stmt->fetch()){$error="ID Number or Email already registered.";}
        else{
            $hash=password_hash($password,PASSWORD_DEFAULT);
            try {
                $pdo->prepare("INSERT INTO students(id_number,lname,fname,mname,course_lvl,password,email,course,address,remaining_session)VALUES(?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$id_number,$lname,$fname,$mname,$course_lvl,$hash,$email,$course,$address,30]);
                $success="Account created successfully!";
            } catch(Exception $e) {
                $error = "DB Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Create Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{--purple:#5B2D8E;--pdark:#3D1A6E;--plight:#7B4BB8;--gold:#F0B429;--gdark:#C88F0A;--bg:#f5f0ff}
        body{font-family:'Inter',sans-serif;min-height:100vh;background:var(--bg);display:flex;flex-direction:column}
        .navbar{background:var(--pdark);padding:0 32px;height:58px;display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid var(--gold);box-shadow:0 2px 12px rgba(0,0,0,0.2)}
        .navbar-brand{color:#fff;font-size:14px;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:10px}
        .navbar-brand img{width:36px;height:36px;object-fit:contain;border-radius:50%}
        .nav-links{display:flex;align-items:center;gap:2px;list-style:none}
        .nav-links a{color:rgba(255,255,255,0.9);text-decoration:none;font-size:13px;font-weight:500;padding:6px 13px;border-radius:4px;transition:background 0.2s}
        .nav-links a:hover{background:rgba(255,255,255,0.15)}
        .btn-nav{background:var(--gold)!important;color:var(--pdark)!important;font-weight:700!important;border-radius:6px!important}
        .dropdown{position:relative}.dropdown-toggle{cursor:pointer}.dropdown-toggle::after{content:' ▾';font-size:10px}
        .dropdown-menu{display:none;position:absolute;top:100%;left:0;background:#fff;border-radius:6px;box-shadow:0 4px 20px rgba(0,0,0,0.2);min-width:160px;z-index:1000;overflow:hidden}
        .dropdown:hover .dropdown-menu{display:block}
        .dropdown-menu a{display:block;color:#333!important;padding:10px 16px;font-size:13px}
        .page-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 20px}
        .register-box{background:#fff;border-radius:20px;padding:48px 52px;width:100%;max-width:560px;box-shadow:0 8px 40px rgba(91,45,142,0.12);animation:fadeUp 0.5s ease}
        @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .reg-title{font-family:'Sora',sans-serif;font-size:28px;font-weight:800;color:#1a1a2e;margin-bottom:6px}
        .reg-subtitle{font-size:14px;color:#888;margin-bottom:32px}
        .section-label{display:flex;align-items:center;gap:12px;margin:24px 0 18px}
        .section-label span{font-size:11.5px;font-weight:700;color:var(--gold);letter-spacing:1.5px;text-transform:uppercase;white-space:nowrap}
        .section-label::after{content:'';flex:1;height:1px;background:#e8e0f0}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .form-grid.single{grid-template-columns:1fr}
        .field{display:flex;flex-direction:column;gap:6px}
        .field label{font-size:11px;font-weight:700;color:#555;letter-spacing:1px;text-transform:uppercase}
        .field input,.field select{padding:12px 16px;border:2px solid #e8e0f0;border-radius:12px;font-size:14px;font-family:'Inter',sans-serif;color:#1a1a2e;background:#faf8ff;outline:none;transition:border-color 0.2s,box-shadow 0.2s}
        .field input:focus,.field select:focus{border-color:var(--purple);box-shadow:0 0 0 4px rgba(91,45,142,0.08);background:#fff}
        .field input::placeholder{color:#bbb}
        .field select{color:#aaa;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23999' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;background-color:#faf8ff}
        .field select.selected{color:#1a1a2e}
        .alert{padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:20px;text-align:center}
        .alert-error{background:#fff0f0;border:1.5px solid #ffcccc;color:#c0392b}
        .alert-success{background:#f0fff4;border:1.5px solid #b2eec8;color:#1a7a3a}
        .btn-create{width:100%;padding:15px;background:linear-gradient(90deg,var(--pdark),var(--purple));color:#fff;font-size:15px;font-weight:700;font-family:'Sora',sans-serif;border:none;border-radius:12px;cursor:pointer;margin-top:28px;letter-spacing:0.5px;transition:all 0.25s;box-shadow:0 6px 20px rgba(61,26,110,0.3)}
        .btn-create:hover{background:linear-gradient(90deg,var(--purple),var(--plight));transform:translateY(-2px);box-shadow:0 10px 28px rgba(91,45,142,0.4)}
        .signin-link{text-align:center;margin-top:16px;font-size:13.5px;color:#888}
        .signin-link a{color:var(--pdark);font-weight:700;text-decoration:underline}
        .signin-link a:hover{color:var(--gold)}
        @media(max-width:600px){.register-box{padding:32px 24px}.form-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<nav class="navbar">
    <a href="home.php" class="navbar-brand"><img src="logos.png" alt="CCS">College of Computer Studies Sit-in Monitoring System</a>
    <ul class="nav-links">
        <li><a href="home.php">Home</a></li>
        <li class="dropdown"><a class="dropdown-toggle">Community</a><div class="dropdown-menu"><a href="#">Announcements</a><a href="#">Resources</a><a href="#">Forum</a></div></li>
        <li><a href="#">About</a></li>
        <li><a href="login.php">Login</a></li>
        <li><a href="Register.php" class="btn-nav">Register</a></li>
    </ul>
</nav>
<div class="page-wrap">
    <div class="register-box">
        <div class="reg-title">Create Account</div>
        <div class="reg-subtitle">Register as a student to request sit-in access to the computer lab.</div>
        <?php if($error): ?><div class="alert alert-error"><?=htmlspecialchars($error)?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><?=$success?> <a href="login.php">Sign in here</a></div><?php endif; ?>
        <form method="POST">
            <div class="section-label"><span>Personal Info</span></div>
            <div class="form-grid">
                <div class="field"><label>First Name *</label><input type="text" name="fname" placeholder="Juan" value="<?=htmlspecialchars($_POST['fname']??'')?>" required></div>
                <div class="field"><label>Last Name *</label><input type="text" name="lname" placeholder="Dela Cruz" value="<?=htmlspecialchars($_POST['lname']??'')?>" required></div>
                <div class="field"><label>Student ID *</label><input type="text" name="id_number" placeholder="2021-00123" value="<?=htmlspecialchars($_POST['id_number']??'')?>" required></div>
                <div class="field"><label>Year Level *</label>
                    <select name="course_lvl" onchange="this.classList.add('selected')" required>
                        <option value="" disabled selected>Select year</option>
                        <option value="1">1st Year</option><option value="2">2nd Year</option>
                        <option value="3">3rd Year</option><option value="4">4th Year</option><option value="5">5th Year</option>
                    </select>
                </div>
            </div>
            <div class="form-grid single" style="margin-top:16px">
                <div class="field"><label>Course / Program *</label>
                    <select name="course" onchange="this.classList.add('selected')" required>
                        <option value="" disabled selected>Select your course</option>
                        <option value="BSIT">BSIT - Bachelor of Science in Information Technology</option>
                        <option value="BSCS">BSCS - Bachelor of Science in Computer Science</option>
                        <option value="BSIS">BSIS - Bachelor of Science in Information Systems</option>
                        <option value="ACT">ACT - Associate in Computer Technology</option>
                    </select>
                </div>
            </div>
            <div class="section-label" style="margin-top:28px"><span>Account Info</span></div>
            <div class="form-grid single">
                <div class="field"><label>Student Email *</label><input type="email" name="email" placeholder="yourname@student.edu" value="<?=htmlspecialchars($_POST['email']??'')?>" required></div>
            </div>
            <div class="form-grid" style="margin-top:16px">
                <div class="field"><label>Password *</label><input type="password" name="password" placeholder="Min. 8 characters" required></div>
                <div class="field"><label>Confirm Password *</label><input type="password" name="confirm_password" placeholder="Repeat password" required></div>
            </div>
            <button type="submit" class="btn-create">Create Account →</button>
        </form>
        <div class="signin-link">Already have an account? <a href="login.php">Sign in here</a></div>
    </div>
</div>
</body>
</html>
