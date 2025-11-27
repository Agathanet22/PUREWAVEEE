<?php
// --- DEBUGGING ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$db_host = "localhost"; $db_user = "root"; $db_pass = ""; $db_name = "purewave_db"; $db_port = 8111;
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

// 1. Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$review_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($review_id == 0) { echo "ID Review tidak valid."; exit; }

// 2. Ambil Data Review Lama
$sql = "SELECT * FROM reviews WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $review_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) { echo "Akses ditolak. Review tidak ditemukan atau bukan milik Anda."; exit; }

$review_data = $result->fetch_assoc();
$location_id = $review_data['location_id'];
$stmt->close();

// 3. Proses Update
$review_error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_review'])) {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if (empty($rating) || $rating < 1 || $rating > 5) {
        $review_error = "Silakan pilih rating bintang (1-5).";
    } elseif (empty($comment)) {
        $review_error = "Komentar tidak boleh kosong.";
    } else {
        // Update Database + Update Tanggal
        $sql_update = "UPDATE reviews SET rating = ?, comment = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("isii", $rating, $comment, $review_id, $user_id);
        
        if ($stmt_update->execute()) {
            header("Location: lokasi-detail.php?id=" . $location_id);
            exit;
        } else {
            $review_error = "Gagal mengupdate review.";
        }
        $stmt_update->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Review Anda</title>
    
    <link rel="stylesheet" href="style.css?v=2.1">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* ========================================= */
        /* CSS KHUSUS HALAMAN INI (BACKGROUND IMAGE) */
        /* ========================================= */
        body.form-page {
            /* Menggunakan gambar airlaut.jpg sebagai background */
            background-image: url('images/airlaut.jpg'); 
            /* Mengatur agar gambar menutupi seluruh layar */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            /* Menambahkan efek overlay gelap agar teks lebih terbaca */
            background-blend-mode: multiply;
            background-color: rgba(0, 0, 0, 0.5); /* Warna gelap transparan */
            
            min-height: 100vh;
            display: block;
        }

        /* STYLE CONTAINER DI TENGAH (SUDAH BENAR) */
        .edit-container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2); /* Shadow sedikit lebih kuat */
            padding: 2.5rem;
            width: 100%;
            max-width: 600px;
            margin: 4rem auto;
            border: none; /* Border dihapus agar lebih bersih di atas background */
        }

        /* STYLE BINTANG */
        .rating-stars { display: inline-block; direction: rtl; font-size: 0; margin-top: 0.5rem; }
        .rating-stars input[type="radio"] { display: none; }
        .rating-stars label { 
            font-size: 2.5rem; color: #d1d5db; cursor: pointer; padding: 0 0.1em; 
            transition: color 0.2s; display: inline-block; width: auto; 
        }
        .rating-stars:hover label { color: #f59e0b; }
        .rating-stars label:hover ~ label { color: #d1d5db; }
        .rating-stars input[type="radio"]:checked ~ label { color: #f59e0b; }
        .rating-stars label:hover, .rating-stars label:hover ~ label, .rating-stars input[type="radio"]:checked ~ label { color: #f59e0b; }
        
        .review-error { color: red; font-weight: bold; margin-bottom: 1rem; }
        
        /* INPUT AREA */
        textarea { 
            width: 100%; padding: 1rem; border: 1px solid #d1d5db; border-radius: 8px; 
            font-family: 'Nunito', sans-serif; font-size: 1rem; min-height: 150px; margin-top: 0.5rem; 
        }
        textarea:focus { outline: none; border-color: #2563eb; }

        /* TOMBOL */
        .btn-save {
            background-color: #0d6efd; color: white; padding: 0.8rem 1.5rem; 
            border: none; border-radius: 6px; font-weight: bold; cursor: pointer; 
            font-size: 1rem; transition: 0.3s;
        }
        .btn-save:hover { background-color: #0b5ed7; }

        .btn-cancel {
            display: inline-block; margin-top: 1rem; color: #6b7280; 
            text-decoration: none; font-weight: bold;
        }
        .btn-cancel:hover { color: #2563eb; }

        .form-group { margin-bottom: 1.5rem; }
        label { font-weight: 700; color: #374151; }
    </style>
</head>

<body class="form-page">

    <?php include 'navbar.php'; ?>

    <main>
        <div class="edit-container">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="font-size: 1.8rem; color: #111827;">Edit Review Anda</h2>
                <p style="color: #6b7280;">Ubah rating atau pengalaman Anda.</p>
            </div>

            <form method="POST" action="edit-review.php?id=<?php echo $review_id; ?>">
                
                <?php if (!empty($review_error)): ?>
                    <p class="review-error"><?php echo $review_error; ?></p>
                <?php endif; ?>

                <div class="form-group" style="text-align: center;">
                    <label>Rating Anda:</label><br>
                    <div class="rating-stars">
                        <input type="radio" id="star5" name="rating" value="5" <?php if($review_data['rating'] == 5) echo 'checked'; ?>><label for="star5">★</label>
                        <input type="radio" id="star4" name="rating" value="4" <?php if($review_data['rating'] == 4) echo 'checked'; ?>><label for="star4">★</label>
                        <input type="radio" id="star3" name="rating" value="3" <?php if($review_data['rating'] == 3) echo 'checked'; ?>><label for="star3">★</label>
                        <input type="radio" id="star2" name="rating" value="2" <?php if($review_data['rating'] == 2) echo 'checked'; ?>><label for="star2">★</label>
                        <input type="radio" id="star1" name="rating" value="1" <?php if($review_data['rating'] == 1) echo 'checked'; ?>><label for="star1">★</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="comment">Komentar Anda:</label>
                    <textarea id="comment" name="comment"><?php echo htmlspecialchars($review_data['comment']); ?></textarea>
                </div>
                
                <div style="text-align: right; display: flex; align-items: center; justify-content: space-between;">
                    <a href="lokasi-detail.php?id=<?php echo $location_id; ?>" class="btn-cancel">Batal</a>
                    <button type="submit" name="update_review" class="btn-save">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>