<?php
session_start(); // Start session

// --- 1. Security Check ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php"); // Redirect if not admin
    exit;
}

// --- 2. Database Connection ---
$db_host = "localhost";   
$db_user = "root";        
$db_pass = "";            
$db_name = "purewave_db"; 
$db_port = 8111; 
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// --- 3. Process Form Submission ---
$errors = []; 
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize data
    $nama_lokasi = trim($_POST['nama_lokasi']);
    $alamat = trim($_POST['alamat']);
    $latitude = trim($_POST['latitude']);
    $longitude = trim($_POST['longitude']);
    $kategori = $_POST['kategori'];
    $grade = $_POST['grade'];
    $deskripsi = trim($_POST['deskripsi']);


    // Validation
    if (empty($nama_lokasi)) { $errors['nama_lokasi'] = "Nama Lokasi wajib diisi."; }
    if (empty($alamat)) { $errors['alamat'] = "Alamat Lengkap wajib diisi."; }
    if (empty($deskripsi)) { $errors['deskripsi'] = "Deskripsi wajib diisi."; }

    if (empty($latitude) || !filter_var($latitude, FILTER_VALIDATE_FLOAT)) { 
        $errors['latitude'] = "Latitude tidak valid."; 
    }
    if (empty($longitude) || !filter_var($longitude, FILTER_VALIDATE_FLOAT)) { 
        $errors['longitude'] = "Longitude tidak valid."; 
    }
    
    $foto_path = NULL;

    if (isset($_FILES['foto_lokasi']) && $_FILES['foto_lokasi']['error'] == 0) {
        $target_dir = "uploads/"; // Folder tujuan
        $file_ext = strtolower(pathinfo($_FILES["foto_lokasi"]["name"], PATHINFO_EXTENSION));
        
        // Generate nama unik agar tidak bentrok (contoh: 65a1b2c3.jpg)
        $new_file_name = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $new_file_name;

        // Validasi Tipe File
        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($file_ext, $allowed_types)) {
            $errors['foto_lokasi'] = "Hanya file JPG, JPEG, PNG, dan WEBP yang diperbolehkan.";
        } 
        // Validasi Ukuran (Maks 5MB)
        elseif ($_FILES["foto_lokasi"]["size"] > 5000000) {
            $errors['foto_lokasi'] = "Ukuran file terlalu besar (Maks 5MB).";
        }
        // Jika aman, pindahkan file
        else {
            if (move_uploaded_file($_FILES["foto_lokasi"]["tmp_name"], $target_file)) {
                $foto_path = $target_file; // Simpan path ini ke database (cth: uploads/abc.jpg)
            } else {
                $errors['foto_lokasi'] = "Gagal mengupload gambar ke server.";
            }
        }
    }

    // simpan ke database
    if (empty($errors)) {
        $sql = "INSERT INTO locations (nama_lokasi, alamat, latitude, longitude, kategori, grade, deskripsi, foto_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssddssss", $nama_lokasi, $alamat, $latitude, $longitude, $kategori, $grade, $deskripsi, $foto_url); 
        
        if ($stmt->execute()) {
            $success_message = "Lokasi baru berhasil disimpan!";
        } else {
            $errors['db'] = "Gagal menyimpan ke database: " . $conn->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Lokasi Air Bersih Baru</title>
    <link rel="stylesheet" href="style.css?v=1.4">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* CSS KHUSUS DRAG & DROP */
        .drop-zone {
            width: 100%;
            height: 200px;
            padding: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-family: 'Nunito', sans-serif;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            color: #666;
            border: 2px dashed #2563eb; /* Garis putus-putus biru */
            border-radius: 10px;
            background-color: #f8faff;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        /* Saat file di-drag di atasnya */
        .drop-zone--over {
            border-style: solid;
            background-color: #e0e7ff;
        }

        /* Input file asli disembunyikan */
        .drop-zone__input {
            display: none;
        }

        /* Preview Gambar di dalam kotak */
        .drop-zone__thumb {
            width: 100%;
            height: 100%;
            border-radius: 10px;
            overflow: hidden;
            background-color: #cccccc;
            background-size: cover;
            background-position: center;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        /* Teks di dalam preview (nama file) */
        .drop-zone__thumb::after {
            content: attr(data-label);
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 5px 0;
            color: #ffffff;
            background: rgba(0, 0, 0, 0.75);
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>

<body class="form-page"> 

    <?php include 'navbar.php'; ?>

    <div class="card">
        <h2>Tambah Lokasi Air Bersih Baru</h2>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (isset($errors['db'])): ?>
            <p class="error-message"><?php echo $errors['db']; ?></p>
        <?php endif; ?>

        <form action="input_lokasi.php" method="POST">

            <label for="nama_lokasi">Nama Lokasi</label>
            <input type="text" id="nama_lokasi" name="nama_lokasi" placeholder="Masukkan nama lokasi" 
                   class="<?php echo isset($errors['nama_lokasi']) ? 'input-error' : ''; ?>">
            <?php if (isset($errors['nama_lokasi'])): ?><p class="error-message"><?php echo $errors['nama_lokasi']; ?></p><?php endif; ?>

            <label for="alamat">Alamat Lengkap</label>
            <textarea id="alamat" name="alamat" rows="2" placeholder="Masukkan alamat lengkap lokasi" 
                      class="<?php echo isset($errors['alamat']) ? 'input-error' : ''; ?>"></textarea>
            <?php if (isset($errors['alamat'])): ?><p class="error-message"><?php echo $errors['alamat']; ?></p><?php endif; ?>

            <label>Koordinat</label>
            <div class="coords">
                <input type="text" name="latitude" placeholder="Latitude" 
                       class="<?php echo isset($errors['latitude']) ? 'input-error' : ''; ?>">
                <input type="text" name="longitude" placeholder="Longitude" 
                       class="<?php echo isset($errors['longitude']) ? 'input-error' : ''; ?>">
            </div>
            <?php if (isset($errors['latitude'])): ?><p class="error-message"><?php echo $errors['latitude']; ?></p><?php endif; ?>
            <?php if (isset($errors['longitude'])): ?><p class="error-message"><?php echo $errors['longitude']; ?></p><?php endif; ?>

            <label for="kategori">Kategori Sumber Air</label>
            <select id="kategori" name="kategori">
                <option value="Mata Air">Mata Air</option>
                <option value="Sumur">Sumur</option>
                <option value="PDAM">PDAM</option>
                <option value="Water Station">Water Station/Filter</option>
            </select>

            <label for="grade">Grade Kualitas Air</label>
            <select id="grade" name="grade">
                <option value="A">Grade A (Bisa diminum, dll)</option>
                <option value="B">Grade B (Bisa dipakai, tidak diminum)</option>
                <option value="C">Grade C (Perlu pengolahan)</option>
            </select>

            <label for="deskripsi">Deskripsi Singkat</label>
            <textarea id="deskripsi" name="deskripsi" rows="2" placeholder="Tulis deskripsi atau kondisi air di lokasi ini" 
                      class="<?php echo isset($errors['deskripsi']) ? 'input-error' : ''; ?>"></textarea>
            <?php if (isset($errors['deskripsi'])): ?><p class="error-message"><?php echo $errors['deskripsi']; ?></p><?php endif; ?>

            <label>Upload Foto Lokasi</label>
            <div class="drop-zone">
                <span class="drop-zone__prompt">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; margin-bottom: 10px;"></i><br>
                    Seret foto ke sini atau klik untuk upload
                </span>
                <input type="file" name="foto_lokasi" class="drop-zone__input" accept="image/*">
            </div>
            <?php if (isset($errors['foto_lokasi'])): ?><p class="error-message"><?php echo $errors['foto_lokasi']; ?></p><?php endif; ?>

            <button type="submit" class="btn btn-primary">Simpan Lokasi</button>
            <a href="index.php" class="btn btn-secondary" style="text-align:center; text-decoration:none; margin-top: 10px;">Batal</a>
        </form>
    </div>

    <?php include 'footer.php'; ?>
    
    <script>
        document.querySelectorAll(".drop-zone__input").forEach((inputElement) => {
            const dropZoneElement = inputElement.closest(".drop-zone");

            dropZoneElement.addEventListener("click", (e) => {
                inputElement.click();
            });
            inputElement.addEventListener("change", (e) => {
                if (inputElement.files.length) {
                    updateThumbnail(dropZoneElement, inputElement.files[0]);
                }
            });
            dropZoneElement.addEventListener("dragover", (e) => {
                e.preventDefault();
                dropZoneElement.classList.add("drop-zone--over");
            });
            ["dragleave", "dragend"].forEach((type) => {
                dropZoneElement.addEventListener(type, (e) => {
                    dropZoneElement.classList.remove("drop-zone--over");
                });
            });
            dropZoneElement.addEventListener("drop", (e) => {
                e.preventDefault();

                if (e.dataTransfer.files.length) {
                    inputElement.files = e.dataTransfer.files; // Masukkan file ke input
                    updateThumbnail(dropZoneElement, e.dataTransfer.files[0]); // Tampilkan preview
                }

                dropZoneElement.classList.remove("drop-zone--over");
            });
        });

        // Fungsi untuk menampilkan preview gambar
        function updateThumbnail(dropZoneElement, file) {
            let thumbnailElement = dropZoneElement.querySelector(".drop-zone__thumb");

            if (dropZoneElement.querySelector(".drop-zone__prompt")) {
                dropZoneElement.querySelector(".drop-zone__prompt").remove();
            }

            if (!thumbnailElement) {
                thumbnailElement = document.createElement("div");
                thumbnailElement.classList.add("drop-zone__thumb");
                dropZoneElement.appendChild(thumbnailElement);
            }

            thumbnailElement.dataset.label = file.name;

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