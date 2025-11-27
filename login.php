<?php
session_start();

// Jika sudah login, langsung ke halaman utama
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$login_error = "";

// --- LOGIKA LOGIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Koneksi Database
    $db_host = "localhost";   
    $db_user = "root";        
    $db_pass = "";            
    $db_name = "purewave_db"; 
    $db_port = 8111;
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

    if ($conn->connect_error) { die("Koneksi gagal."); }

    // 2. Ambil Data Input
    $nama_depan = trim($_POST['nama_depan']);
    $nama_belakang = trim($_POST['nama_belakang']);
    $password = trim($_POST['password']);

    // 3. Validasi Input Kosong
    if (empty($nama_depan) || empty($nama_belakang) || empty($password)) {
        $login_error = "Mohon lengkapi Nama Depan, Nama Belakang, dan Kata Sandi.";
    } else {
        // 4. Cek User
        $sql = "SELECT id, nama_depan, role, password FROM users WHERE nama_depan = ? AND nama_belakang = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nama_depan, $nama_belakang);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // 5. Verifikasi Password
            if (password_verify($password, $user['password'])) {
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama_depan'] = $user['nama_depan'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $login_error = "Kata sandi salah.";
            }
        } else {
            $login_error = "Akun tidak ditemukan. Cek nama Anda.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PureWave</title>
    
    <link rel="stylesheet" href="style.css?v=3.2">
    
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* --- CSS KHUSUS HALAMAN INI (BACKGROUND LAUT) --- */
        body {
            /* Gambar Laut */
            background-image: url('images/airlaut.jpg') !important;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            
            /* Efek Gelap (Overlay) agar tulisan putih di navbar terbaca */
            background-color: rgba(0, 0, 0, 0.4);
            background-blend-mode: multiply;
            
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Pastikan kartu tetap putih bersih */
        .form-container {
            background-color: #ffffff !important;
            margin-top: 80px; /* Jarak dari navbar */
        }
    </style>
</head>
<body>

    <header class="navbar-beranda">
        <div class="logo">
            <a href="index.php" style="color: white; text-decoration: none; display: flex; align-items: center;">
                <i class="fas fa-tint"></i> PureWave
            </a>
        </div>
        <ul class="nav-links-beranda">
            <li><a href="index.php">Beranda</a></li>
            <li><a href="index.php#lokasi">Lokasi</a></li>
            <li><a href="index.php#kontak">Kontak</a></li>
            <li><a href="register.php" class="btn-masuk">Sign Up</a></li>
        </ul>
    </header>

    <div class="form-container">
        <div class="form-header">
            <div class="logo-icon"><i class="fas fa-tint"></i></div>
            <h2>Login</h2>
            <p style="color:#666; font-size:0.9rem;">Masuk untuk mengakses fitur lengkap</p>
        </div>

        <?php if (!empty($login_error)): ?>
            <div class="error-message" style="background-color: #fee2e2; color: #ef4444; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-weight: bold; border: 1px solid #fecaca;">
                <?php echo $login_error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            
            <div class="form-group">
                <label for="nama_depan">Nama Depan</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="nama_depan" placeholder="Masukkan nama depan" required>
                </div>
            </div>

            <div class="form-group">
                <label for="nama_belakang">Nama Belakang</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="nama_belakang" placeholder="Masukkan nama belakang" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Kata Sandi</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Masukkan kata sandi" required>
                    <i class="fas fa-eye" id="togglePassword" style="cursor: pointer; left: auto; right: 10px;"></i>
                </div>
            </div>

            <button type="submit" class="submit-button">Masuk</button>

            <div class="bottom-link">
                Belum punya akun? <a href="register.php">Daftar di sini</a>
            </div>
        </form>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>

</body>
</html>