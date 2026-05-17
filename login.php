
<?php
session_start();

if (isset($_SESSION['user']) && !isset($_SESSION['role'])) {
    session_destroy();
}

if (isset($_SESSION['user']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: Admin/index.php"); exit();
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: dashboard.php"); exit();
    } else {
        session_destroy();
    }
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['user'] = 'admin';
        $_SESSION['name'] = 'Admin';
        $_SESSION['role'] = 'admin';
        header("Location: Admin/index.php"); exit();
    }

    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=sit_in_db;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT * FROM students WHERE id_number = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $student = $stmt->fetch();

        if ($student && password_verify($password, $student['password'])) {
            $_SESSION['user'] = $student['id_number'];
            $_SESSION['name'] = $student['fname'] . ' ' . $student['lname'];
            $_SESSION['role'] = 'student';
            header("Location: dashboard.php"); exit();
        }
    } catch (PDOException $e) {
        $error = "DB Error: " . $e->getMessage();
    }

    if (empty($error)) {
        $error = "Invalid ID Number / Email or Password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Login</title>
    <script>
    // Immediate Theme Applier to prevent flickering
    (function() {
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{
            --purple:#5B2D8E;
            --pdark:#3D1A6E;
            --plight:#7B4BB8;
            --gold:#F0B429;
            --gdark:#C88F0A;
            --bg: linear-gradient(160deg, #f5f0ff 0%, #ede5f8 55%, #f9f3e0 100%);
            --white:#fff;
            --input-bg:#faf8ff;
            --text-main:#333333;
            --text-muted:#888888;
            --border-color:#ede6f5;
        }
        [data-theme="dark"]{
            --purple:#8e60c2;
            --pdark:#241242;
            --plight:#ac84de;
            --gold:#f5c95d;
            --gdark:#dfa212;
            --bg: linear-gradient(160deg, #0e0717 0%, #1c0e35 55%, #2a1b0a 100%);
            --white:#1a0f2e;
            --input-bg:#0c0614;
            --text-main:#e0dced;
            --text-muted:#9184a8;
            --border-color:#342054;
        }
        body{
            font-family:'Inter',sans-serif;
            min-height:100vh;
            background: var(--bg);
            color: var(--text-main);
            display:flex;
            flex-direction:column;
        }
        /* NAVBAR */
        .navbar{
            background:var(--pdark);
            padding:0 40px;
            display:flex;align-items:center;justify-content:space-between;
            height:58px;
            border-bottom:3px solid var(--gold);
            box-shadow:0 2px 12px rgba(0,0,0,0.25);
        }
        .navbar-brand{color:#fff;font-size:14.5px;font-weight:600;display:flex;align-items:center;gap:10px;text-decoration:none}
        .navbar-brand img{width:38px;height:38px;object-fit:contain;border-radius:0;}
        .nav-links{display:flex;align-items:center;gap:4px;list-style:none}
        .nav-links a{color:rgba(255,255,255,0.9);text-decoration:none;font-size:13.5px;font-weight:500;padding:7px 16px;border-radius:5px;transition:background 0.2s}
        .nav-links a:hover{background:rgba(255,255,255,0.15)}
        .nav-links a.btn-nav{background:var(--gold);color:var(--pdark);font-weight:700;border-radius:6px}
        .nav-links a.btn-nav:hover{background:var(--gdark);color:#fff}
        .dropdown{position:relative}.dropdown-toggle{cursor:pointer}
        .dropdown-toggle::after{content:' ▾';font-size:10px}
        .dropdown-menu{display:none;position:absolute;top:100%;left:0;background:var(--white);border-radius:6px;box-shadow:0 4px 20px rgba(0,0,0,0.2);min-width:160px;z-index:1000;overflow:hidden}
        .dropdown:hover .dropdown-menu{display:block}
        .dropdown-menu a{display:block;color:var(--text-main)!important;padding:10px 16px;font-size:13px}
        .dropdown-menu a:hover{background:var(--bg);color:var(--purple)!important}

        /* PAGE */
        .page-wrapper{
            flex:1;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:40px 16px;
        }
        .login-card{
            background:var(--white);
            color:var(--text-main);
            border-radius:20px;
            padding:44px 44px 40px;
            width:100%;max-width:420px;
            box-shadow:0 20px 60px rgba(0,0,0,0.15);
            animation:slideUp 0.5s cubic-bezier(0.22,1,0.36,1);
        }
        @keyframes slideUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}

        /* LOGO */
        .logo-wrap{display:flex;flex-direction:column;align-items:center;margin-bottom:24px}
        .logo-wrap img{
            width:90px;height:90px;
            object-fit:contain;
            margin-bottom:10px;
            border-radius:50%;
            border:3px solid var(--gold);
            padding:4px;
            box-shadow:0 0 0 4px rgba(91,45,142,0.15);
        }
        .school-name{font-size:11px;color:var(--text-muted);letter-spacing:1.2px;text-transform:uppercase;text-align:center}
        .gold-bar{width:48px;height:4px;background:linear-gradient(90deg,var(--pdark),var(--gold));border-radius:4px;margin:0 auto 6px}
        h2{font-size:24px;font-weight:700;color:var(--pdark);text-align:center;margin-bottom:26px}

        /* ALERTS */
        .alert-error{background:#fff0f0;border:1px solid #ffcccc;color:#c0392b;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:16px;text-align:center}

        /* FIELDS */
        .field{margin-bottom:18px}
        .field label{display:block;font-size:12.5px;font-weight:600;color:var(--pdark);margin-bottom:6px}
        .input-wrap{position:relative}
        .input-wrap i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:14px}
        .field input{width:100%;padding:11px 12px 11px 38px;border:1.5px solid var(--border-color);border-radius:10px;font-size:14px;font-family:'Inter',sans-serif;color:var(--text-main);background:var(--input-bg);outline:none;transition:border-color 0.2s,box-shadow 0.2s}
        .field input:focus{border-color:var(--purple);box-shadow:0 0 0 4px rgba(91,45,142,0.1);background:var(--white)}
        .forgot{text-align:right;margin-top:-10px;margin-bottom:20px}
        .forgot a{font-size:12px;color:var(--text-muted);text-decoration:none}
        .forgot a:hover{color:var(--purple)}

        /* BUTTON */
        .btn-login{width:100%;padding:13px;background:linear-gradient(90deg,var(--pdark),var(--purple),var(--plight));background-size:200% 100%;color:#fff;font-size:14px;font-weight:700;font-family:'Inter',sans-serif;border:none;border-radius:10px;cursor:pointer;letter-spacing:1px;transition:background-position 0.4s,transform 0.15s,box-shadow 0.2s;box-shadow:0 6px 20px rgba(91,45,142,0.4)}
        .btn-login:hover{background-position:right center;transform:translateY(-2px);box-shadow:0 10px 28px rgba(91,45,142,0.5)}
        .divider-line{height:1px;background:linear-gradient(90deg,transparent,var(--gold),transparent);margin:22px 0}
        .register-link{text-align:center;font-size:13px;color:var(--text-muted)}
        .register-link a{color:var(--purple);font-weight:600;text-decoration:none}
        .back-link{text-align:center;margin-top:12px}
        .back-link a{font-size:12.5px;color:var(--text-muted);text-decoration:none}
        .back-link a:hover{color:var(--purple)}
    </style>
</head>
<body>
<nav class="navbar">
    <a href="home.php" class="navbar-brand">
        <img src="logos.png" alt="CCS Logo">
        College of Computer Studies Sit-in Monitoring System
    </a>
    <ul class="nav-links">
        <li><a href="home.php">Home</a></li>
        <li class="dropdown">
            <a class="dropdown-toggle">Community</a>
            <div class="dropdown-menu">
                <a href="#">Announcements</a>
                <a href="#">Resources</a>
                <a href="#">Forum</a>
            </div>
        </li>
        <li><a href="#">About</a></li>
        <li><a href="login.php">Login</a></li>
        <li><a href="Register.php" class="btn-nav">Register</a></li>
        <li><a href="#" id="themeToggle" style="cursor:pointer"><i class="fas fa-moon"></i> Theme</a></li>
    </ul>
</nav>
<div class="page-wrapper">
    <div class="login-card">
        <div class="logo-wrap">
            <img src="logos.png" alt="CCS Logo">
            <p class="school-name">UC College of Computer Studies</p>
        </div>
        <div class="gold-bar"></div>
        <h2>Login</h2>
        <?php if($error): ?>
            <div class="alert-error"><?=htmlspecialchars($error)?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="field">
                <label>ID Number or Email</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username"
                        placeholder="Enter your ID Number or Email"
                        value="<?=htmlspecialchars($_POST['username']??'')?>" required>
                </div>
            </div>
            <div class="field">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password"
                        placeholder="Enter your password" required>
                </div>
            </div>
            <div class="forgot"><a href="#">Forgot password?</a></div>
            <button type="submit" class="btn-login">LOGIN</button>
        </form>
        <div class="divider-line"></div>
        <div class="register-link">Don't have an account? <a href="Register.php">Register here</a></div>
        <div class="back-link"><a href="home.php">← Back to Home</a></div>
    </div>
</div>
<script>
// Dark Mode Toggle Logic
document.addEventListener("DOMContentLoaded", function() {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    const root = document.documentElement;
    const icon = themeToggle.querySelector('i');

    // Update icon initially
    if(root.getAttribute('data-theme') === 'dark') {
        icon.classList.replace('fa-moon', 'fa-sun');
    }

    themeToggle.addEventListener('click', (e) => {
        e.preventDefault();
        if(root.getAttribute('data-theme') === 'dark') {
            root.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
            icon.classList.replace('fa-sun', 'fa-moon');
        } else {
            root.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            icon.classList.replace('fa-moon', 'fa-sun');
        }
    });
});
</script>
</body>
</html>
