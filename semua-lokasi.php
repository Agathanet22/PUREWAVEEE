<?php
session_start(); 
$db_host = "localhost"; $db_user = "root"; $db_pass = ""; $db_name = "purewave_db"; $db_port = 8111;
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) { die("Koneksi gagal."); }

// --- 1. TANGKAP SEMUA FILTER ---
$search_keyword_display = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$filter_kecamatan = isset($_GET['kecamatan']) ? htmlspecialchars($_GET['kecamatan']) : '';
$filter_grade = isset($_GET['grade']) ? htmlspecialchars($_GET['grade']) : '';
$filter_kategori = isset($_GET['kategori']) ? htmlspecialchars($_GET['kategori']) : ''; // <-- BARU

// --- 2. QUERY DASAR ---
$sql = "SELECT l.id, l.nama_lokasi, l.alamat, l.kategori, l.grade, l.foto_url, AVG(r.rating) AS avg_rating
        FROM locations l LEFT JOIN reviews r ON l.id = r.location_id WHERE 1=1";

$params = []; $types = "";

// --- 3. TAMBAHKAN KONDISI FILTER ---

// Filter Keyword
if (!empty($search_keyword_display)) {
    $sql .= " AND (LOWER(l.nama_lokasi) LIKE ? OR LOWER(l.alamat) LIKE ?)";
    $search_param = "%" . strtolower($search_keyword_display) . "%";
    $params[] = $search_param; $params[] = $search_param; $types .= "ss";
}

// Filter Kecamatan
if (!empty($filter_kecamatan)) {
    $sql .= " AND LOWER(l.alamat) LIKE ?";
    $kecamatan_param = "%" . strtolower($filter_kecamatan) . "%";
    $params[] = $kecamatan_param; $types .= "s";
}

// Filter Kategori (BARU)
if (!empty($filter_kategori)) {
    $sql .= " AND l.kategori = ?";
    $params[] = $filter_kategori; $types .= "s";
}

// Filter Grade
if (!empty($filter_grade)) {
    $sql .= " AND l.grade = ?";
    $params[] = $filter_grade; $types .= "s";
}

// Grouping (Tanpa Limit)
$sql .= " GROUP BY l.id, l.nama_lokasi, l.alamat, l.kategori, l.grade, l.foto_url";

// --- 4. EKSEKUSI ---
$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();
$placeholder_image = "https://i.imgur.com/sC3d3g1.jpg"; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Lokasi - PureWave</title>
    <link rel="stylesheet" href="style.css?v=2.0">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body style="background-color: #f0f5ff;">

    <?php include 'navbar.php'; ?>

    <main style="max-width: 1200px; margin: 2rem auto; padding: 2rem 1rem;">
        
        <div style="text-align: center; margin-bottom: 3rem;">
            <h1 style="font-size: 2.5rem; color: #173648; margin-bottom: 1rem;">Semua Lokasi Air Bersih</h1>
            <p style="color: #666; margin-bottom: 2rem;">Jelajahi seluruh titik air bersih yang tersedia di database kami.</p>
            
            <form action="semua-lokasi.php" method="GET" style="background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                
                <div class="search-input-wrapper" style="flex-grow: 1; max-width: 300px;">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Cari lokasi..." value="<?php echo $search_keyword_display; ?>" style="width:100%; padding: 0.8rem 0.8rem 0.8rem 2.5rem; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                
                <div class="search-input-wrapper" style="width: 180px;">
                    <i class="fas fa-map-pin"></i>
                    <select name="kecamatan" style="width:100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; text-indent: 1.5rem;">
                        <option value="">Semua Kecamatan</option>
                        <option value="Poncokusumo" <?php if($filter_kecamatan == 'Poncokusumo') echo 'selected'; ?>>Poncokusumo</option>
                        <option value="Wajak" <?php if($filter_kecamatan == 'Wajak') echo 'selected'; ?>>Wajak</option>
                        <option value="Tumpang" <?php if($filter_kecamatan == 'Tumpang') echo 'selected'; ?>>Tumpang</option>
                    </select>
                </div>

                <div class="search-input-wrapper" style="width: 180px;">
                    <i class="fas fa-tint"></i>
                    <select name="kategori" style="width:100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; text-indent: 1.5rem;">
                        <option value="">Semua Kategori</option>
                        <option value="Mata Air" <?php if($filter_kategori == 'Mata Air') echo 'selected'; ?>>Mata Air</option>
                        <option value="Sumur" <?php if($filter_kategori == 'Sumur') echo 'selected'; ?>>Sumur</option>
                        <option value="PDAM" <?php if($filter_kategori == 'PDAM') echo 'selected'; ?>>PDAM</option>
                        <option value="Water Station" <?php if($filter_kategori == 'Water Station') echo 'selected'; ?>>Water Station</option>
                    </select>
                </div>

                <div class="search-input-wrapper" style="width: 150px;">
                    <i class="fas fa-star"></i>
                    <select name="grade" style="width:100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; text-indent: 1.5rem;">
                        <option value="">Semua Grade</option>
                        <option value="A" <?php if($filter_grade == 'A') echo 'selected'; ?>>Grade A</option>
                        <option value="B" <?php if($filter_grade == 'B') echo 'selected'; ?>>Grade B</option>
                        <option value="C" <?php if($filter_grade == 'C') echo 'selected'; ?>>Grade C</option>
                    </select>
                </div>

                <button type="submit" class="btn-cari" style="background:#0d6efd; color:white; border:none; border-radius:6px; padding:0.8rem 1.5rem; font-weight:bold; cursor:pointer;">Cari</button>
            </form>
        </div>

        <div class="location-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <a href="lokasi-detail.php?id=<?php echo $row['id']; ?>" class="location-card">
                        <img src="<?php echo !empty($row['foto_url']) ? htmlspecialchars($row['foto_url']) : $placeholder_image; ?>" alt="<?php echo htmlspecialchars($row['nama_lokasi']); ?>">
                        <div class="location-content">
                            <div class="location-title">
                                <h3><?php echo htmlspecialchars($row['nama_lokasi']); ?></h3>
                                <span class="rating">
                                    <i class="fas fa-star"></i> 
                                    <?php echo ($row['avg_rating']) ? number_format($row['avg_rating'], 1) : '?'; ?>
                                </span>
                            </div>
                            <p class="location-area"><?php echo htmlspecialchars($row['alamat']); ?></p>
                            <div class="location-tags">
                                <span>
                                    <?php 
                                    $icon_class = 'fa-tint';
                                    if ($row['kategori'] == 'Sumur') $icon_class = 'fa-arrow-down';
                                    if ($row['kategori'] == 'PDAM') $icon_class = 'fa-building';
                                    if ($row['kategori'] == 'Water Station') $icon_class = 'fa-faucet';
                                    ?>
                                    <i class="fas <?php echo $icon_class; ?>"></i> <?php echo htmlspecialchars($row['kategori']); ?>
                                </span>
                                <span><i class="fas fa-road"></i> ? km</span> 
                            </div>
                            <?php 
                                $grade_cls = 'grey'; 
                                if($row['grade'] == 'A') $grade_cls = 'green';
                                if($row['grade'] == 'B') $grade_cls = 'blue';
                            ?>
                            <span class="tag-highlight <?php echo $grade_cls; ?>">Grade <?php echo htmlspecialchars($row['grade']); ?></span>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center; grid-column:1/-1; padding:3rem;">Tidak ada lokasi ditemukan.</p>
            <?php endif; ?>
        </div>

    </main>

    <?php include 'footer.php'; ?>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>