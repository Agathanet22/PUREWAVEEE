<?php
session_start(); // Selalu mulai session

// --- 1. Koneksi ke Database ---
$db_host = "localhost";   
$db_user = "root";        
$db_pass = "";            
$db_name = "purewave_db"; 
$db_port = 8111; // <-- PASTIKAN PORT ANDA BENAR
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// --- 2. Siapkan Filter dan Kueri SQL ---
$search_keyword_display = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$filter_kecamatan = isset($_GET['kecamatan']) ? htmlspecialchars($_GET['kecamatan']) : '';
$filter_grade = isset($_GET['grade']) ? htmlspecialchars($_GET['grade']) : '';
$filter_kategori = isset($_GET['kategori']) ? htmlspecialchars($_GET['kategori']) : '';

// Query Utama dengan JOIN untuk Rating
$sql = "
    SELECT 
        l.id, l.nama_lokasi, l.alamat, l.kategori, l.grade, l.foto_url,
        AVG(r.rating) AS avg_rating
    FROM 
        locations l
    LEFT JOIN 
        reviews r ON l.id = r.location_id
    WHERE 1=1
";

$params = [];
$types = "";

// Filter Keyword
if (!empty($search_keyword_display)) {
    $sql .= " AND (LOWER(l.nama_lokasi) LIKE ? OR LOWER(l.alamat) LIKE ?)";
    $search_param = "%" . strtolower($search_keyword_display) . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// Filter Kecamatan
if (!empty($filter_kecamatan)) {
    $sql .= " AND LOWER(l.alamat) LIKE ?";
    $kecamatan_param = "%" . strtolower($filter_kecamatan) . "%";
    $params[] = $kecamatan_param;
    $types .= "s";
}

// Filter Kategori
if (!empty($filter_kategori)) {
    $sql .= " AND l.kategori = ?";
    $params[] = $filter_kategori;
    $types .= "s";
}

// Filter Grade
if (!empty($filter_grade)) {
    $sql .= " AND l.grade = ?";
    $params[] = $filter_grade;
    $types .= "s";
}

// Grouping
$sql .= " GROUP BY l.id, l.nama_lokasi, l.alamat, l.kategori, l.grade, l.foto_url";

$sql .= " LIMIT 6";

// Eksekusi
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params); 
}
$stmt->execute();
$result = $stmt->get_result();

$placeholder_image = "https://i.imgur.com/sC3d3g1.jpg"; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PureWave - Temukan Sumber Air Bersih</title>
    
    <link rel="stylesheet" href="style.css?v=1.2"> <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <?php 
    $is_homepage = true; // <-- Ini kuncinya agar navbar jadi transparan
    include 'navbar.php'; 
    ?>

    <section class="hero-section" id="beranda">
        <div class="hero-content">
            <h1>Temukan Sumber Air Bersih Terdekat</h1>
            <p>Akses mudah ke informasi lokasi sumber air bersih di seluruh Indonesia. Bantu masyarakat mendapatkan air layak konsumsi untuk kehidupan yang lebih sehat.</p>
            
            <form class="search-form-hero" action="index.php" method="GET">
                <div class="search-input-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Cari nama lokasi..." value="<?php echo $search_keyword_display; ?>">
                </div>
                <div class="search-input-wrapper">
                    <i class="fas fa-map-pin"></i>
                    <select name="kecamatan">
                        <option value="">Semua Kecamatan</option>
                        <option value="Poncokusumo" <?php if($filter_kecamatan == 'Poncokusumo') echo 'selected'; ?>>Poncokusumo</option>
                        <option value="Wajak" <?php if($filter_kecamatan == 'Wajak') echo 'selected'; ?>>Wajak</option>
                        <option value="Tumpang" <?php if($filter_kecamatan == 'Tumpang') echo 'selected'; ?>>Tumpang</option>
                    </select>
                </div>
                <div class="search-input-wrapper">
                    <i class="fas fa-tint"></i> <select name="kategori">
                        <option value="">Semua Kategori</option>
                        <option value="Mata Air" <?php if($filter_kategori == 'Mata Air') echo 'selected'; ?>>Mata Air</option>
                        <option value="Sumur" <?php if($filter_kategori == 'Sumur') echo 'selected'; ?>>Sumur</option>
                        <option value="PDAM" <?php if($filter_kategori == 'PDAM') echo 'selected'; ?>>PDAM</option>
                        <option value="Water Station" <?php if($filter_kategori == 'Water Station') echo 'selected'; ?>>Water Station</option>
                    </select>
                </div>
                <div class="search-input-wrapper">
                    <i class="fas fa-tags"></i>
                    <select name="grade">
                        <option value="">Semua Grade</option>
                        <option value="A" <?php if($filter_grade == 'A') echo 'selected'; ?>>Grade A</option>
                        <option value="B" <?php if($filter_grade == 'B') echo 'selected'; ?>>Grade B</option>
                        <option value="C" <?php if($filter_grade == 'C') echo 'selected'; ?>>Grade C</option>
                    </select>
                </div>
                <button type="submit" class="btn-cari"><i class="fas fa-search"></i> Cari</button>
            </form>
        </div>
    </section>

    <main class="container-beranda">
        <section class="location-list" id="lokasi">
            <h2>
                <?php 
                if (!empty($search_keyword_display) || !empty($filter_kecamatan) || !empty($filter_grade)) {
                    echo "Hasil Filter Lokasi";
                } else {
                    echo "Semua Lokasi Air Bersih";
                }
                ?>
            </h2>
            <p class="subtitle">Temukan sumber air bersih berkualitas di sekitar Anda</p>
            
            <div class="location-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <a href="lokasi-detail.php?id=<?php echo $row['id']; ?>" class="location-card">
                            <img src="<?php echo !empty($row['foto_url']) ? htmlspecialchars($row['foto_url']) : $placeholder_image; ?>" 
                                 alt="<?php echo htmlspecialchars($row['nama_lokasi']); ?>">
                            <div class="location-content">
                                <div class="location-title">
                                    <h3><?php echo htmlspecialchars($row['nama_lokasi']); ?></h3>
                                    <span class="rating">
                                        <i class="fas fa-star"></i> 
                                        <?php 
                                        if ($row['avg_rating']) { echo number_format($row['avg_rating'], 1); } 
                                        else { echo '?'; }
                                        ?>
                                    </span>
                                </div>
                                <p class="location-area"><?php echo htmlspecialchars($row['alamat']); ?></p>
                                <div class="location-tags">
                                    <span>
                                        <?php 
                                        $icon_class = 'fa-tint'; // Default (Mata Air)
                                        if ($row['kategori'] == 'Sumur') $icon_class = 'fa-arrow-down';
                                        if ($row['kategori'] == 'PDAM') $icon_class = 'fa-building';
                                        if ($row['kategori'] == 'Water Station') $icon_class = 'fa-faucet'; // Ikon Keran untuk Water Station
                                        ?>
                                        <i class="fas <?php echo $icon_class; ?>"></i> <?php echo htmlspecialchars($row['kategori']); ?>
                                    </span>
                                    <span><i class="fas fa-road"></i> ? km</span> 
                                </div>
                                <?php 
                                    $grade_class = 'grey'; 
                                    if($row['grade'] == 'A') $grade_class = 'green';
                                    if($row['grade'] == 'B') $grade_class = 'blue';
                                ?>
                                <span class="tag-highlight <?php echo $grade_class; ?>">Grade <?php echo htmlspecialchars($row['grade']); ?></span>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; grid-column: 1 / -1; padding: 3rem 0;">
                        <strong>Maaf, tidak ada lokasi yang ditemukan.</strong><br>
                        Coba ubah kata kunci pencarian atau filter Anda.
                    </p>
                <?php endif; ?>
            </div>

            <div style="text-align: center; margin-top: 3rem;">
                <a href="semua-lokasi.php" class="btn-lihat-semua">Lihat Semua Lokasi <i class="fas fa-arrow-right"></i></a>
            </div>
        </section>
    </main>
    
    <section class="cta-section" id="kontak">
        <div class="cta-wrapper"> 
            <div class="cta-card">
                <i class="fas fa-headset cta-icon"></i>
                <h3>Hubungi Kami</h3>
                <p>Ada pertanyaan atau butuh bantuan? Tim kami siap membantu Anda 24/7.</p>
                <div class="kontak-info">
                    <span><i class="fas fa-phone"></i> +62 813-8154-4216</span>
                    <span><i class="fas fa-envelope"></i> purewave@gmail</span>
                </div>
                <a href="https://api.whatsapp.com/send/?phone=6282152287490&text=Hi%2C+aku+ingin+bertanya+terkait+air+bersih.&type=phone_number&app_absent=0" target="_blank" class="btn-hubungi">Hubungi Sekarang</a>
            </div>
            <div class="cta-card light">
                <i class="fas fa-exclamation-triangle cta-icon green"></i>
                <h3>Laporkan Masalah</h3>
                <p>Temukan masalah dengan kualitas sumber air, atau ingin menambahkan lokasi baru? Laporkan kepada kami</p>
                <div class="lapor-links">
                    <a href="https://forms.gle/rwj5TFNjov9sQXxt9" target="_blank">
                        <i class="fas fa-plus-circle"></i> Laporkan Kualitas Air
                    </a>
                    <a href="https://forms.gle/rwj5TFNjov9sQXxt9" target="_blank">
                        <i class="fas fa-plus-circle"></i> Tambah Lokasi Baru
                    </a>
                </div>
                <a href="https://forms.gle/rwj5TFNjov9sQXxt9" target="_blank" class="btn-lapor">
                    Buat Laporan
                </a>
            </div>
        </div> 
    </section>

    <?php include 'footer.php'; ?>

    <script>
        const navbar = document.querySelector('.navbar-beranda');
        const navLinks = document.querySelectorAll(".nav-links-beranda a");
        const sections = document.querySelectorAll("section[id]");

        window.addEventListener('scroll', () => {
            // 1. Sticky Navbar Effect
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            // 2. Scroll Spy (Highlight Menu)
            let current = "";
            sections.forEach((section) => {
                const sectionTop = section.offsetTop;
                if (pageYOffset >= (sectionTop - 150)) {
                    current = section.getAttribute("id");
                }
            });

            navLinks.forEach((link) => {
                link.classList.remove("active");
                // Cek href, misal "index.php#lokasi" mengandung "lokasi"
                if (link.getAttribute("href").includes("#" + current)) {
                    link.classList.add("active");
                }
            });
        });
    </script>

</body>
</html>
<?php $stmt->close(); $conn->close(); ?>