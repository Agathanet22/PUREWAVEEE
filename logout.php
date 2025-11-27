<?php
session_start(); // Mulai session

// Hapus semua data session
$_SESSION = array();

// Jika ingin menghapus cookie session juga (untuk keamanan ekstra)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Redirect ke halaman Login
header("Location: login.php");
exit;
?>