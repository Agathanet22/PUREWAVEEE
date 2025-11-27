<?php
session_start();

// Jika user sudah login, redirect ke index
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: index.php");
    exit;
}

// Inisialisasi pesan
$register_error = "";
$register_success = "";

// --- LOGIKA PENDAFTARAN ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Koneksi Database
    $db_host = "localhost";   
    $db_user = "root";        
    $db_pass = "";            
    $db_name = "purewave_db"; 
    $db_port = 8111; 
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // 2. Ambil Data dari Form
    $nama_depan = trim($_POST['nama_depan']);
    $nama_belakang = trim($_POST['nama_belakang']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // 3. Validasi Input
    if (empty($nama_depan) || empty($nama_belakang) || empty($password) || empty($confirm_password)) {
        $register_error = "Mohon lengkapi semua kolom.";
    } elseif ($password !== $confirm_password) {
        $register_error = "Konfirmasi kata sandi tidak cocok.";
    } elseif (strlen($password) < 6) {
        $register_error = "Kata sandi minimal 6 karakter.";
    } else {
        // 4. Cek apakah kombinasi Nama Depan & Belakang sudah ada
        $sql_check = "SELECT id FROM users WHERE nama_depan = ? AND nama_belakang = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $nama_depan, $nama_belakang);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $register_error = "Pengguna dengan nama tersebut sudah terdaftar.";
        } else {
            // 5. Hash Password & Simpan Data
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'member'; // Role default untuk pendaftar baru

            $sql_insert = "INSERT INTO users (nama_depan, nama_belakang, password, role) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            // Bind parameter: ssss (string, string, string, string)
            $stmt_insert->bind_param("ssss", $nama_depan, $nama_belakang, $password_hash, $role);

            if ($stmt_insert->execute()) {
                $register_success = "Pendaftaran berhasil! Silakan login.";
            } else {
                $register_error = "Terjadi kesalahan sistem: " . $conn->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - PureWave</title>
    
    <link rel="stylesheet" href="style.css?v=3.2">
    
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body {
            /* 1. Set Gambar Background (Pastikan file images/airlaut.jpg ADA) */
            background-image: url('images/airlaut.jpg') !important;
            background-size: cover;     /* Gambar memenuhi layar */
            background-position: center; /* Gambar di tengah */
            background-repeat: no-repeat; /* Jangan diulang */
            background-attachment: fixed; /* Background diam saat discroll */
            
            /* 2. Tambahkan Lapisan Gelap (Overlay) agar tulisan navbar terbaca */
            background-color: rgba(15, 23, 42, 0.6); /* Warna biru gelap transparan */
            background-blend-mode: multiply; /* Campurkan warna dengan gambar */
            
            /* 3. Atur agar konten di tengah secara vertikal & horizontal */
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0; /* Hapus margin bawaan body */
        }

        /* Style untuk Kartu Putih di Tengah */
        .form-container {
            background-color: #ffffff !important; /* Warna putih bersih */
            border-radius: 16px;       /* Sudut membulat */
            box-shadow: 0 10px 25px rgba(0,0,0,0.2); /* Bayangan agar timbul */
            padding: 2.5rem;           /* Jarak isi dari pinggir kartu */
            width: 100%;
            max-width: 450px;          /* Lebar maksimum kartu */
            margin-top: 60px;          /* Jarak dari navbar atas */
        }

        /* Penyesuaian Header Kartu */
        .form-header h2 {
            color: #1e293b; /* Warna teks judul */
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
            <li><a href="login.php" class="btn-masuk">Masuk</a></li>
        </ul>
    </header>

    <div class="form-container">
        <div class="form-header">
            <div class="logo-icon" style="color: #2563eb;"><i class="fas fa-tint"></i></div>
            <h2>Daftar Akun Baru</h2>
            <p style="color: #64748b; margin-top: 0.5rem;">Lengkapi data diri Anda untuk bergabung.</p>
        </div>

        <?php if (!empty($register_error)): ?>
            <div class="error-message" style="background-color: #fee2e2; color: #ef4444; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-weight: bold; border: 1px solid #fecaca;">
                <?php echo $register_error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($register_success)): ?>
            <div class="success-message" style="background-color: #d1fae5; color: #065f46; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-weight: bold; border: 1px solid #a7f3d0;">
                <?php echo $register_success; ?>
                <br><a href="login.php" style="color: #059669; text-decoration: underline; margin-top: 10px; display: inline-block; font-weight: 700;">Klik di sini untuk Masuk</a>
            </div>
        <?php else: ?>

        <form action="register.php" method="POST">
            
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label for="nama_depan">Nama Depan</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="nama_depan" name="nama_depan" placeholder="Budi" required value="<?php echo isset($nama_depan) ? htmlspecialchars($nama_depan) : ''; ?>">
                    </div>
                </div>

                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label for="nama_belakang">Nama Belakang</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="nama_belakang" name="nama_belakang" placeholder="Santoso" required value="<?php echo isset($nama_belakang) ? htmlspecialchars($nama_belakang) : ''; ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Kata Sandi</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Konfirmasi Kata Sandi</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock-open"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi kata sandi" required>
                </div>
            </div>

            <button type="submit" class="submit-button" style="margin-top: 1rem;">Daftar Sekarang</button>

            <div class="bottom-link" style="margin-top: 1.5rem;">
                Sudah punya akun? <a href="login.php" style="color: #2563eb; font-weight: 700;">Masuk di sini</a>
            </div>
        </form>
        <?php endif; ?>
    </div>

</body>
</html>