<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Presensi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="logo">Presensi App</div>
    <div class="menu">
        <span>Halo, <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User') ?></span>
        <a href="../auth/logout.php" class="btn-logout">Logout</a>
    </div>
</nav>
<div class="container">
