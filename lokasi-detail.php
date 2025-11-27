<?php
session_start(); 

// --- 1. KONEKSI DATABASE ---
$db_host = "localhost"; $db_user = "root"; $db_pass = ""; $db_name = "purewave_db"; $db_port = 8111;
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

// --- 2. AMBIL ID LOKASI ---
$location_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($location_id == 0) { echo "ID Lokasi tidak valid."; exit; }

// --- 3. CEK SESSION USER ---
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$current_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

// --- 4. AMBIL DATA LOKASI ---
$sql_loc = "SELECT * FROM locations WHERE id = ?";
$stmt_loc = $conn->prepare($sql_loc);
$stmt_loc->bind_param("i", $location_id);
$stmt_loc->execute();
$result_loc = $stmt_loc->get_result();

if ($result_loc->num_rows == 1) { 
    $location = $result_loc->fetch_assoc(); 
} else { 
    echo "Lokasi tidak ditemukan!"; 
    exit; 
}
$stmt_loc->close();

// --- 5. PROSES FORM (POST) ---
$review_error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // A. TAMBAH REVIEW
    if (isset($_POST['submit_review'])) {
        if ($current_role == 'admin') { $review_error = "Admin tidak diperbolehkan memberi review."; } 
        elseif (!isset($_SESSION['loggedin'])) { $review_error = "Anda harus login."; } 
        else {
            $rating = (int)$_POST['rating'];
            $comment = trim($_POST['comment']);
            if (empty($rating) || $rating < 1 || $rating > 5) { $review_error = "Pilih bintang 1-5."; }
            elseif (empty($comment)) { $review_error = "Komentar kosong."; }
            else {
                $sql_insert = "INSERT INTO reviews (location_id, user_id, rating, comment) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql_insert);
                $stmt->bind_param("iiis", $location_id, $current_user_id, $rating, $comment);
                if ($stmt->execute()) { header("Location: lokasi-detail.php?id=" . $location_id); exit; }
                else { $review_error = "Gagal menyimpan (Mungkin duplikat)."; }
                $stmt->close();
            }
        }
    }
    // B. HAPUS REVIEW
    if (isset($_POST['delete_review']) && isset($_SESSION['loggedin'])) {
        $review_id_to_delete = (int)$_POST['review_id_to_delete'];
        if ($current_role == 'admin') {
            $sql_delete = "DELETE FROM reviews WHERE id = ?";
            $stmt = $conn->prepare($sql_delete);
            $stmt->bind_param("i", $review_id_to_delete);
        } else {
            $sql_delete = "DELETE FROM reviews WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql_delete);
            $stmt->bind_param("ii", $review_id_to_delete, $current_user_id);
        }
        $stmt->execute();
        $stmt->close();
        header("Location: lokasi-detail.php?id=" . $location_id); exit;
    }

    // 3. HAPUS LOKASI (KHUSUS ADMIN)
    if (isset($_POST['delete_location']) && $current_role == 'admin') {
        $sql_del_loc = "DELETE FROM locations WHERE id = ?";
        $stmt_del = $conn->prepare($sql_del_loc);
        $stmt_del->bind_param("i", $location_id);
        if ($stmt_del->execute()) { header("Location: index.php"); exit; }
        $stmt_del->close();
    }
}

// --- 6. AMBIL DAFTAR REVIEW ---
$reviews_list = [];
$sql_reviews = "SELECT r.id, r.user_id, r.rating, r.comment, r.created_at, r.updated_at, u.nama_depan, u.nama_belakang 
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.location_id = ?
                ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql_reviews);
$stmt->bind_param("i", $location_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $reviews_list[] = $row; }
$stmt->close();
$conn->close(); 

// --- 7. DATA TAMPILAN ---
$placeholder = "https://i.imgur.com/sC3d3g1.jpg";
$foto = !empty($location['foto_url']) ? htmlspecialchars($location['foto_url']) : $placeholder;
$grade_text = "Grade " . $location['grade'];
$grade_cls = "grade-c"; $grade_dsc = "";
if ($location['grade'] == 'A') { $grade_cls='grade-a'; $grade_dsc="Kualitas air terjamin, bisa diminum/masak."; }
elseif ($location['grade'] == 'B') { $grade_cls='grade-b'; $grade_dsc="Bisa untuk mandi/cuci, jangan diminum."; }
else { $grade_cls='grade-c'; $grade_dsc="Perlu pengolahan lanjut."; }
$gmaps_link = "https://www.google.com/maps?q=" . htmlspecialchars($location['latitude']) . "," . htmlspecialchars($location['longitude']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($location['nama_lokasi']); ?> - PureWave</title>
    
    <link rel="stylesheet" href="style.css?v=9.9"> <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        .btn-del-loc { 
            background-color: #fee2e2; 
            border: 1px solid #ef4444; 
            color: #ef4444; 
            padding: 0.5rem 1rem; 
            border-radius: 20px; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 0.9rem; 
            cursor: pointer; 
            margin-left: 1rem;
            font-family: 'Nunito', sans-serif;
        }
        .btn-del-loc:hover { background-color: #fecaca; }
        body { background-color: #f0f5ff; font-family: 'Nunito', sans-serif; }
        
        /* Layout Container */
        .detail-container { max-width: 900px; margin: 2rem auto; padding: 2rem; }
        
        /* Kartu Putih */
        .detail-card, .review-section, .tambah-review-section {
            background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 2.5rem; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;
        }

        /* Header Lokasi */
        .detail-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem; }
        .detail-header h2 { font-size: 2.2rem; color: #333; margin: 0; font-weight: 800; }
        
        /* Badges */
        .grade-a { background-color: #e8f5e9; color: #388e3c; padding: 0.5rem 1rem; border-radius: 20px; font-weight: bold; font-size: 0.9rem; }
        .grade-b { background-color: #e7f0ff; color: #0d6efd; padding: 0.5rem 1rem; border-radius: 20px; font-weight: bold; font-size: 0.9rem; }
        .grade-c { background-color: #f1f1f1; color: #555; padding: 0.5rem 1rem; border-radius: 20px; font-weight: bold; font-size: 0.9rem; }
        .tag-highlight { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 6px; font-size: 0.9rem; font-weight: bold; margin-top: 1rem; }
        .tag-highlight.blue { background: #e7f0ff; color: #0d6efd; }

        /* Foto & Maps */
        .detail-main-photo { width: 100%; height: 400px; object-fit: cover; border-radius: 12px; margin: 1.5rem 0; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .gmaps-link { display: inline-block; background-color: #fff; border: 1px solid #0d6efd; color: #0d6efd; padding: 0.5rem 1rem; border-radius: 50px; text-decoration: none; font-weight: bold; font-size: 0.9rem; transition: 0.2s; }
        .gmaps-link:hover { background-color: #0d6efd; color: #fff; }

        /* Deskripsi */
        .deskripsi-title { font-size: 1.3rem; color: #111; margin-bottom: 0.5rem; font-weight: 700; border-bottom: 2px solid #f3f4f6; padding-bottom: 0.5rem; display: inline-block; margin-top: 1.5rem; }
        .deskripsi-text { color: #4b5563; line-height: 1.7; font-size: 1.05rem; margin-bottom: 0; }

        /* Reviews */
        .review-card { border: 1px solid #f3f4f6; padding: 1.5rem; margin-bottom: 1rem; border-radius: 8px; background: #f9fafb; box-shadow: none; }
        .review-author { display: flex; align-items: center; margin-bottom: 0.5rem; }
        .avatar { width: 40px; height: 40px; background: #2563eb; color: white; border-radius: 50%; text-align: center; line-height: 40px; font-weight: bold; margin-right: 1rem; font-size: 1.1rem; }
        .review-rating { color: #f59e0b; margin-bottom: 0.5rem; }
        
        /* Stars Input */
        .rating-stars { display: inline-block; direction: rtl; }
        .rating-stars input { display: none; }
        .rating-stars label { font-size: 2rem; color: #d1d5db; cursor: pointer; padding: 0 0.1em; transition: 0.2s; }
        .rating-stars label:hover, .rating-stars label:hover ~ label, .rating-stars input:checked ~ label { color: #f59e0b; }

        /* Form Elements */
        textarea { width: 100%; padding: 1rem; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'Nunito'; font-size: 1rem; min-height: 120px; margin-top: 0.5rem; }
        .btn-submit-review { background: #2563eb; color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 1rem; font-size: 1rem; }
        .btn-submit-review:hover { background: #1d4ed8; }
        
        /* Tombol Balik */
        .back-link { display: inline-block; margin-bottom: 1rem; color: #6b7280; text-decoration: none; font-weight: bold; }
        .back-link:hover { color: #2563eb; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="detail-container">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Lokasi</a>

        <div class="detail-card">
            <div class="detail-header">
                <div style="flex-grow:1;">
                    <h2><?php echo htmlspecialchars($location['nama_lokasi']); ?></h2>
                    <p class="address" style="color:#6b7280; margin-top:0.2rem; font-weight:500;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($location['alamat']); ?></p>
                </div>
                <span class="<?php echo $grade_cls; ?>"><?php echo $grade_text; ?></span>
            </div>
            
            <div style="display:flex; align-items:center; gap:1rem; margin-top:0.5rem;">
                <a href="<?php echo $gmaps_link; ?>" target="_blank" class="gmaps-link"><i class="fas fa-map"></i> Lihat di Google Maps</a>

                <span class="tag-highlight blue" style="margin:0;"><?php echo htmlspecialchars($location['kategori']); ?></span>
                
                <?php if ($current_role == 'admin'): ?>
                    <div style="margin-left: auto; display: flex; gap: 0.5rem;">
                        <a href="edit-lokasi.php?id=<?php echo $location_id; ?>" style="background-color:#e5e7eb; color:#374151; padding:0.5rem 1rem; border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9rem; display:inline-flex; align-items:center;">
                            <i class="fas fa-pen" style="margin-right:5px;"></i> Edit
                        </a>

                        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus LOKASI ini secara permanen?');" style="margin:0;">
                            <button type="submit" name="delete_location" style="background-color:#c53030; color:white; border:none; padding:0.5rem 1rem; border-radius:6px; cursor:pointer; font-weight:bold; font-size:0.9rem; font-family:'Nunito';">
                                <i class="fas fa-trash-alt"></i> Hapus
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
            
            <img src="<?php echo $foto; ?>" alt="Foto Lokasi" class="detail-main-photo">
            
            <h3 class="deskripsi-title">Deskripsi</h3>
            <p class="deskripsi-text"><?php echo htmlspecialchars($location['deskripsi']); ?></p>
            
            <h3 class="deskripsi-title">Kualitas Air</h3>
            <p class="deskripsi-text"><?php echo $grade_dsc; ?></p>
        </div>

        <div class="review-section">
            <h3 style="margin-bottom:1.5rem; font-size:1.5rem; color:#333;">Ulasan Pengguna (<?php echo count($reviews_list); ?>)</h3>
            
            <?php if (empty($reviews_list)): ?>
                <p style="color:#6b7280;">Belum ada ulasan. Jadilah yang pertama!</p>
            <?php else: ?>
                <?php foreach ($reviews_list as $r): ?>
                    <div class="review-card">
                        <div class="review-author">
                            <span class="avatar"><?php echo strtoupper(substr($r['nama_depan'], 0, 1)); ?></span>
                            <div style="flex-grow:1;">
                                <strong style="color:#111; font-size:1.05rem;"><?php echo htmlspecialchars($r['nama_depan'] . ' ' . $r['nama_belakang']); ?></strong>
                                <div style="font-size:0.85rem; color:#9ca3af;">
                                    <?php 
                                    if ($r['updated_at']) { echo date('d M Y', strtotime($r['updated_at'])) . ' <span style="font-style:normal; color:#6b7280;">(Diedit)</span>'; } 
                                    else { echo date('d M Y', strtotime($r['created_at'])); }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="review-rating">
                            <?php for($i=0; $i<5; $i++) echo ($i < $r['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star" style="color:#d1d5db"></i>'; ?>
                        </div>
                        <p style="color:#4b5563; line-height:1.6; margin-top:0.5rem;"><?php echo htmlspecialchars($r['comment']); ?></p>

                        <?php if (($current_user_id == $r['user_id']) || $current_role == 'admin'): ?>
                            <div style="display:flex; align-items: center; gap: 1rem; margin-top:1rem; padding-top:1rem; border-top:1px solid #eee;">
                                
                                <?php if ($current_user_id == $r['user_id']): ?>
                                    <a href="edit-review.php?id=<?php echo $r['id']; ?>" style="font-size:0.9rem; font-weight:bold; text-decoration:none; color:#6b7280; padding: 4px 8px; border-radius: 4px; background-color: #f3f4f6;">Edit</a>
                                <?php endif; ?>
                                
                                <form method="POST" onsubmit="return confirm('Hapus review?');" style="margin: 0;">
                                    <input type="hidden" name="review_id_to_delete" value="<?php echo $r['id']; ?>">
                                    <button type="submit" name="delete_review" style="background:none; border:none; color:#ef4444; font-weight:bold; font-size:0.9rem; cursor:pointer; padding: 4px 8px; border-radius: 4px; background-color: #fee2e2;">Hapus</button>
                                </form>
                                
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($current_role != 'admin'): ?>
            <div class="tambah-review-section">
                <h3 style="margin-bottom:1rem; font-size:1.3rem;">Tulis Ulasan Anda</h3>
                <?php if (isset($_SESSION['loggedin'])): ?>
                    <form method="POST">
                        <?php if ($review_error) echo "<p style='color:red; font-weight:bold;'>$review_error</p>"; ?>
                        <div style="margin-bottom:1rem;">
                            <label style="font-weight:bold; color:#374151;">Rating:</label><br>
                            <div class="rating-stars">
                                <input type="radio" id="s5" name="rating" value="5"><label for="s5">★</label>
                                <input type="radio" id="s4" name="rating" value="4"><label for="s4">★</label>
                                <input type="radio" id="s3" name="rating" value="3"><label for="s3">★</label>
                                <input type="radio" id="s2" name="rating" value="2"><label for="s2">★</label>
                                <input type="radio" id="s1" name="rating" value="1"><label for="s1">★</label>
                            </div>
                        </div>
                        <textarea name="comment" placeholder="Bagikan pengalaman Anda..."></textarea>
                        <button type="submit" name="submit_review" class="btn-submit-review">Kirim Ulasan</button>
                    </form>
                <?php else: ?>
                    <p style="text-align:center; color:#6b7280;">Silakan <a href="login.html" style="color:#2563eb; font-weight:bold;">Login</a> untuk menulis ulasan.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>
    <?php include 'footer.php'; ?>
</body>
</html>