<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Home</title>
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
    <link rel="stylesheet" href="style.css">
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

<section class="hero">
    <div class="hero-content">
        <div class="hero-text">
            <h1>CCS <span>Sit-in</span><br>Monitoring System</h1>
            <p>A centralized platform for the College of Computer Studies to efficiently manage and monitor student sit-in sessions, track attendance, and streamline laboratory usage.</p>
            <div class="hero-buttons">
                <a href="login.php" class="btn btn-primary">Login to Portal</a>
                <a href="Register.php" class="btn btn-outline">Create Account</a>
            </div>
        </div>
        <div class="hero-logo">
            <img src="logos.png" alt="CCS Logo">
            <p class="hero-logo-text">COLLEGE OF COMPUTER STUDIES</p>
        </div>
    </div>
</section>
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