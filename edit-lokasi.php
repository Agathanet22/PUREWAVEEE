<?php
session_start();

// 1. Security Check
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php"); exit;
}

// 2. Koneksi Database
$db_host = "localhost"; $db_user = "root"; $db_pass = ""; $db_name = "purewave_db"; $db_port = 8111;
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

// 3. Ambil ID Lokasi
$location_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($location_id == 0) { echo "ID tidak valid."; exit; }

// 4. PROSES UPDATE DATA (POST)
$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lokasi = trim($_POST['nama_lokasi']);
    $alamat = trim($_POST['alamat']);
    $latitude = trim($_POST['latitude']);
    $longitude = trim($_POST['longitude']);
    $kategori = $_POST['kategori'];
    $grade = $_POST['grade'];
    $deskripsi = trim($_POST['deskripsi']);

    // Validasi
    if (empty($nama_lokasi)) $errors['nama_lokasi'] = "Nama wajib diisi.";
    if (empty($alamat)) $errors['alamat'] = "Alamat wajib diisi.";
    
    // --- LOGIKA UPLOAD FOTO (EDIT MODE) ---
    $new_foto_path = null;
    
    // Cek apakah ada file baru yang diupload
    if (isset($_FILES['foto_lokasi']) && $_FILES['foto_lokasi']['error'] == 0) {
        $target_dir = "uploads/";
        $file_ext = strtolower(pathinfo($_FILES["foto_lokasi"]["name"], PATHINFO_EXTENSION));
        $new_file_name = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $new_file_name;
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($file_ext, $allowed_types)) {
            $errors['foto_lokasi'] = "Format file harus JPG, PNG, atau WEBP.";
        } elseif ($_FILES["foto_lokasi"]["size"] > 5000000) {
            $errors['foto_lokasi'] = "Ukuran file terlalu besar (Max 5MB).";
        } else {
            if (move_uploaded_file($_FILES["foto_lokasi"]["tmp_name"], $target_file)) {
                $new_foto_path = $target_file; // File baru berhasil diupload
            } else {
                $errors['foto_lokasi'] = "Gagal mengupload file.";
            }
        }
    }
    // ---------------------------------------

    if (empty($errors)) {
        // Jika ada foto baru, update kolom foto_url. Jika tidak, jangan sentuh kolom itu.
        if ($new_foto_path) {
            $sql = "UPDATE locations SET nama_lokasi=?, alamat=?, latitude=?, longitude=?, kategori=?, grade=?, deskripsi=?, foto_url=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssddssssi", $nama_lokasi, $alamat, $latitude, $longitude, $kategori, $grade, $deskripsi, $new_foto_path, $location_id);
        } else {
            // Query TANPA update foto (foto lama tetap aman)
            $sql = "UPDATE locations SET nama_lokasi=?, alamat=?, latitude=?, longitude=?, kategori=?, grade=?, deskripsi=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssddsssi", $nama_lokasi, $alamat, $latitude, $longitude, $kategori, $grade, $deskripsi, $location_id);
        }
        
        if ($stmt->execute()) {
            header("Location: lokasi-detail.php?id=" . $location_id);
            exit;
        } else {
            $errors['db'] = "Gagal update: " . $conn->error;
        }
        $stmt->close();
    }
}

// 5. AMBIL DATA LAMA (Untuk ditampilkan di form)
$sql_get = "SELECT * FROM locations WHERE id = ?";
$stmt_get = $conn->prepare($sql_get);
$stmt_get->bind_param("i", $location_id);
$stmt_get->execute();
$result = $stmt_get->get_result();
if ($result->num_rows == 0) { echo "Lokasi tidak ditemukan."; exit; }
$data = $result->fetch_assoc();
$stmt_get->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lokasi - PureWave</title>
    <link rel="stylesheet" href="style.css?v=3.0">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* CSS Drag & Drop (Sama dengan input_lokasi.php) */
        .drop-zone {
            width: 100%; height: 200px; padding: 25px;
            display: flex; align-items: center; justify-content: center; text-align: center;
            border: 2px dashed #2563eb; border-radius: 10px; background-color: #f8faff;
            cursor: pointer; position: relative; overflow: hidden; margin-bottom: 16px;
        }
        .drop-zone--over { background-color: #e0e7ff; border-style: solid; }
        .drop-zone__input { display: none; }
        .drop-zone__thumb {
            width: 100%; height: 100%; border-radius: 10px; overflow: hidden;
            background-color: #cccccc; background-size: cover; background-position: center;
            position: absolute; top: 0; left: 0;
        }
        .drop-zone__thumb::after {
            content: attr(data-label); position: absolute; bottom: 0; left: 0; width: 100%;
            padding: 5px 0; color: white; background: rgba(0,0,0,0.75); font-size: 14px;
        }
        .drop-zone__prompt { color: #666; font-family: 'Nunito'; font-weight: 500; }
    </style>
</head>
<body class="form-page">

    <?php include 'navbar.php'; ?>

    <div class="card">
        <h2>Edit Lokasi: <?php echo htmlspecialchars($data['nama_lokasi']); ?></h2>
        
        <?php if (isset($errors['db'])): ?><p class="error-message"><?php echo $errors['db']; ?></p><?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            
            <label>Nama Lokasi</label>
            <input type="text" name="nama_lokasi" value="<?php echo htmlspecialchars($data['nama_lokasi']); ?>">

            <label>Alamat Lengkap</label>
            <textarea name="alamat" rows="2"><?php echo htmlspecialchars($data['alamat']); ?></textarea>

            <label>Koordinat</label>
            <div class="coords">
                <input type="text" name="latitude" placeholder="Lat" value="<?php echo htmlspecialchars($data['latitude']); ?>">
                <input type="text" name="longitude" placeholder="Long" value="<?php echo htmlspecialchars($data['longitude']); ?>">
            </div>

            <label>Kategori</label>
            <select name="kategori">
                <option value="Mata Air" <?php if($data['kategori']=='Mata Air') echo 'selected'; ?>>Mata Air</option>
                <option value="Sumur" <?php if($data['kategori']=='Sumur') echo 'selected'; ?>>Sumur</option>
                <option value="PDAM" <?php if($data['kategori']=='PDAM') echo 'selected'; ?>>PDAM</option>
                <option value="Water Station" <?php if($data['kategori']=='Water Station') echo 'selected'; ?>>Water Station / Filter</option>
            </select>

            <label>Grade</label>
            <select name="grade">
                <option value="A" <?php if($data['grade']=='A') echo 'selected'; ?>>Grade A</option>
                <option value="B" <?php if($data['grade']=='B') echo 'selected'; ?>>Grade B</option>
                <option value="C" <?php if($data['grade']=='C') echo 'selected'; ?>>Grade C</option>
            </select>

            <label>Deskripsi</label>
            <textarea name="deskripsi" rows="4"><?php echo htmlspecialchars($data['deskripsi']); ?></textarea>

            <label>Foto Lokasi (Klik/Drop untuk ganti)</label>
            <div class="drop-zone" id="dropZoneFoto">
                
                <?php if (!empty($data['foto_url'])): ?>
                    <div class="drop-zone__thumb" data-label="Foto Saat Ini" style="background-image: url('<?php echo $data['foto_url']; ?>');"></div>
                <?php else: ?>
                    <span class="drop-zone__prompt">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; margin-bottom: 10px;"></i><br>
                        Seret foto baru ke sini atau klik untuk mengganti
                    </span>
                <?php endif; ?>

                <input type="file" name="foto_lokasi" class="drop-zone__input" accept="image/*">
            </div>
            <?php if (isset($errors['foto_lokasi'])): ?><p class="error-message"><?php echo $errors['foto_lokasi']; ?></p><?php endif; ?>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="lokasi-detail.php?id=<?php echo $location_id; ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        document.querySelectorAll(".drop-zone__input").forEach((inputElement) => {
            const dropZoneElement = inputElement.closest(".drop-zone");

            dropZoneElement.addEventListener("click", (e) => { inputElement.click(); });

            inputElement.addEventListener("change", (e) => {
                if (inputElement.files.length) {
                    updateThumbnail(dropZoneElement, inputElement.files[0]);
                }
            });

            dropZoneElement.addEventListener("dragover", (e) => {
                e.preventDefault(); dropZoneElement.classList.add("drop-zone--over");
            });

            ["dragleave", "dragend"].forEach((type) => {
                dropZoneElement.addEventListener(type, (e) => {
                    dropZoneElement.classList.remove("drop-zone--over");
                });
            });

            dropZoneElement.addEventListener("drop", (e) => {
                e.preventDefault();
                if (e.dataTransfer.files.length) {
                    inputElement.files = e.dataTransfer.files;
                    updateThumbnail(dropZoneElement, e.dataTransfer.files[0]);
                }
                dropZoneElement.classList.remove("drop-zone--over");
            });
        });

        function updateThumbnail(dropZoneElement, file) {
            let thumbnailElement = dropZoneElement.querySelector(".drop-zone__thumb");

            // Hapus prompt lama jika ada
            if (dropZoneElement.querySelector(".drop-zone__prompt")) {
                dropZoneElement.querySelector(".drop-zone__prompt").remove();
            }

            // Jika belum ada elemen thumbnail, buat baru
            if (!thumbnailElement) {
                thumbnailElement = document.createElement("div");
                thumbnailElement.classList.add("drop-zone__thumb");
                dropZoneElement.appendChild(thumbnailElement);
            }

            thumbnailElement.dataset.label = file.name; // Update nama file

            if (file.type.startsWith("image/")) {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = () => {
                    thumbnailElement.style.backgroundImage = `url('${reader.result}')`;
                };
            } else {
                thumbnailElement.style.backgroundImage = null;
            }
        }
    </script>
</body>
</html>