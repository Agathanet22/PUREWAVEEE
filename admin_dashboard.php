<?php
session_start();

// Cek apakah user sudah login DAN apakah rolenya admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php"); // Tendang user non-admin ke beranda
    exit;
}

// Koneksi ke database
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "purewave_db";
$db_port = 8111;

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil semua data user
$sql = "SELECT id, nama_depan, nama_belakang, tanggal_daftar FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PureWave</title>
    
    <link rel="stylesheet" href="style.css?v=3.1"> 
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* Style Khusus Dashboard Admin */
        .admin-container {
            max-width: 1000px;
            margin: 3rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .admin-header {
            margin-bottom: 2rem;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 1rem;
        }
        
        .admin-header h1 {
            font-size: 1.8rem;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .admin-header p {
            color: #6b7280;
        }

        /* Table Style */
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .user-table th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: 700;
            text-align: left;
            padding: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .user-table td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
        }
        
        .user-table tr:hover {
            background-color: #f9fafb;
        }
        
        .id-badge {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85rem;
        }
    </style>
</head>
<body class="form-page"> <?php include 'navbar.php'; ?>

    <main class="admin-container">
        <div class="admin-header">
            <h1>Halo, Admin <?php echo htmlspecialchars($_SESSION['nama_depan']); ?>!</h1>
            <p>Ini adalah daftar semua pengguna yang terdaftar di sistem.</p>
        </div>

        <div style="overflow-x: auto;">
            <table class="user-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Nama Depan</th>
                        <th>Nama Belakang</th>
                        <th>Tanggal Daftar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td><span class='id-badge'>" . htmlspecialchars($row["id"]) . "</span></td>";
                            echo "<td>" . htmlspecialchars($row["nama_depan"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["nama_belakang"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["tanggal_daftar"]) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center; padding: 2rem;'>Belum ada pengguna terdaftar.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

    <?php include 'footer.php'; ?>

</body>
</html>
<?php $conn->close(); ?>