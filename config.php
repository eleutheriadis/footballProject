<?php
// =============================================
// Ρυθμίσεις Σύνδεσης Βάσης Δεδομένων
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // Άλλαξε με τα δικά σου στοιχεία
define('DB_PASS', '');             // Άλλαξε με τα δικά σου στοιχεία
define('DB_NAME', 'football_championship');

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            die("Σφάλμα σύνδεσης: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Βοηθητική: Ασφαλής μεταφόρτωση αρχείου εικόνας
function uploadImage($file, $subfolder) {
    $targetDir = UPLOAD_DIR . $subfolder . '/';
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) return ['error' => 'Μη επιτρεπτός τύπος αρχείου.'];
    if ($file['size'] > 2 * 1024 * 1024) return ['error' => 'Το αρχείο υπερβαίνει τα 2MB.'];

    $filename = uniqid() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
        return ['path' => UPLOAD_URL . $subfolder . '/' . $filename];
    }
    return ['error' => 'Αποτυχία μεταφόρτωσης.'];
}
