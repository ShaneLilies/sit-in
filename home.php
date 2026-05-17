<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS | Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
</body>
</html>