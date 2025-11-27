<?php
// Cek apakah variabel $is_homepage diset (TRUE untuk index.php, FALSE untuk yang lain)
$nav_class = (isset($is_homepage) && $is_homepage) ? 'navbar-beranda' : 'navbar';
$ul_class  = (isset($is_homepage) && $is_homepage) ? 'nav-links-beranda' : 'nav-links';
?>

<header class="<?php echo $nav_class; ?>">
    
    <div class="logo">
        <a href="index.php" style="color: white; text-decoration: none; display: flex; align-items: center;">
            <i class="fas fa-tint"></i> PureWave
        </a>
    </div>
    
    <ul class="<?php echo $ul_class; ?>">
        <li><a href="index.php">Beranda</a></li>
        <li><a href="index.php#lokasi">Lokasi</a></li>
        <li><a href="index.php#kontak">Kontak</a></li>

        <?php
        // Logika Menu Khusus Admin
        if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
            echo '<li><a href="admin_dashboard.php">Lihat Pengguna</a></li>';
            echo '<li><a href="input_lokasi.php">Input Lokasi</a></li>';
        }
        ?>
    </ul>
    
    <div style="display: flex; align-items: center; gap: 1rem;">
        
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true): ?>
            
            <div class="profile-dropdown">
                <a href="javascript:void(0)" class="btn-profil">
                    <i class="fas fa-user-circle"></i>
                </a>
                <div class="dropdown-content">
                    <div class="dropdown-header">
                        Halo, <strong><?php echo htmlspecialchars($_SESSION['nama_depan'] ?? 'User'); ?></strong>
                    </div>
                    <a href="profile.php"><i class="fas fa-user"></i> Profil Saya</a>
                    <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

        <?php else: ?>
            
            <a href="register.php" style="color: white; text-decoration: none; font-weight: 500;">Daftar</a>
            
            <?php if (isset($is_homepage) && $is_homepage): ?>
                <a href="login.php" class="btn-masuk">Masuk</a>
            <?php else: ?>
                <a href="login.php" style="color: white; text-decoration: none; font-weight: 600; border: 1px solid white; padding: 0.4rem 1rem; border-radius: 4px;">Masuk</a>
            <?php endif; ?>

        <?php endif; ?>

    </div>

</header>