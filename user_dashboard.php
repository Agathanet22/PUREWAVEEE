<?php
session_start(); // Mulai session

// Cek apakah user sudah login
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengguna</title>
    
    <link rel="stylesheet" href="style.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <header class="navbar">
        <div class="logo">
            <i class="fas fa-tint"></i> PureWave
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="index.html">Beranda</a></li>
                <li><a href="#">Lokasi</a></li>
                <li><a href="#">Review</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="main-content">
        <div class="form-container" style="text-align: center;">
            
            <div class="form-header">
                <i class="fas fa-tint logo-icon"></i>
                <h2>Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama_depan']); ?>!</h2>
            </div>
            
            <p style="font-size: 1.1rem; color: #555;">Ini adalah halaman dashboard Anda. Fitur-fitur untuk pengguna terdaftar akan segera hadir.</p>
            
            <a href="logout.php" class="submit-button" style="text-decoration: none; margin-top: 1.5rem;">Logout</a>
        
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>