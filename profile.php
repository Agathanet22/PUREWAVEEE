<?php
session_start(); 

// --- DEBUGGING ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Cek Login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true || !isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. Koneksi Database
$db_host = "localhost"; $db_user = "root"; $db_pass = ""; $db_name = "purewave_db"; $db_port = 8111;
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) { die("Koneksi gagal."); }

// 3. Proses Update Profil
$name_success = ""; $name_error = ""; $pass_success = ""; $pass_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update Nama
    if (isset($_POST['update_name'])) {
        $nama_depan = trim($_POST['nama_depan']);
        $nama_belakang = trim($_POST['nama_belakang']);
        if (empty($nama_depan) || empty($nama_belakang)) { $name_error = "Nama tidak boleh kosong."; }
        else {
            $stmt = $conn->prepare("UPDATE users SET nama_depan=?, nama_belakang=? WHERE id=?");
            $stmt->bind_param("ssi", $nama_depan, $nama_belakang, $user_id);
            if ($stmt->execute()) { $_SESSION['nama_depan'] = $nama_depan; $name_success = "Nama berhasil diupdate!"; }
            else { $name_error = "Gagal update nama."; }
            $stmt->close();
        }
    }
    // Update Password
    if (isset($_POST['update_password'])) {
        $p_lama = $_POST['password_lama']; $p_baru = $_POST['password_baru']; $p_konfirm = $_POST['konfirmasi_password'];
        if (empty($p_lama) || empty($p_baru)) { $pass_error = "Isi semua kolom password."; }
        elseif ($p_baru != $p_konfirm) { $pass_error = "Password baru tidak cocok."; }
        else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
            $stmt->bind_param("i", $user_id); $stmt->execute();
            $res = $stmt->get_result(); $row = $res->fetch_assoc();
            
            if (password_verify($p_lama, $row['password'])) {
                $new_hash = password_hash($p_baru, PASSWORD_DEFAULT);
                $stmt_upd = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                $stmt_upd->bind_param("si", $new_hash, $user_id);
                if ($stmt_upd->execute()) { $pass_success = "Password berhasil diubah!"; }
                else { $pass_error = "Gagal ubah password."; }
            } else { $pass_error = "Password lama salah."; }
        }
    }
}

// 4. Ambil Data User
$stmt = $conn->prepare("SELECT nama_depan, nama_belakang FROM users WHERE id=?");
$stmt->bind_param("i", $user_id); $stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Anda - PureWave</title>
    
    <link rel="stylesheet" href="style.css?v=2.0">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="form-page"> <?php include 'navbar.php'; ?>

    <div class="card" style="margin-top: 4rem; margin-bottom: 4rem; max-width: 600px;">
        <h2>Edit Profil Anda</h2>
        <p style="color: #666; margin-bottom: 2rem;">Kelola informasi akun Anda di sini.</p>
        
        <form action="profile.php" method="POST" style="margin-bottom: 2rem;">
            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem; color:#333;">Ubah Nama</h3>
            
            <?php if ($name_success) echo "<div class='success-message'>$name_success</div>"; ?>
            <?php if ($name_error) echo "<div class='error-message'>$name_error</div>"; ?>

            <div class="form-group">
                <label>Nama Depan</label>
                <input type="text" name="nama_depan" value="<?php echo htmlspecialchars($user_data['nama_depan']); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <div class="form-group">
                <label>Nama Belakang</label>
                <input type="text" name="nama_belakang" value="<?php echo htmlspecialchars($user_data['nama_belakang']); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <button type="submit" name="update_name" class="btn btn-primary">Simpan Nama</button>
        </form>

        <form action="profile.php" method="POST">
            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem; color:#333;">Ubah Password</h3>
            
            <?php if ($pass_success) echo "<div class='success-message'>$pass_success</div>"; ?>
            <?php if ($pass_error) echo "<div class='error-message'>$pass_error</div>"; ?>

            <div class="form-group">
                <label>Password Lama</label>
                <input type="password" name="password_lama" placeholder="Masukkan password saat ini" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="password_baru" placeholder="Masukkan password baru" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="konfirmasi_password" placeholder="Ulangi password baru" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <button type="submit" name="update_password" class="btn btn-secondary">Ubah Password</button>
        </form>
    </div>

    <?php include 'footer.php'; ?>

</body>
</html>