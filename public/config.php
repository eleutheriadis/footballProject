<?php
declare(strict_types=1);

// =============================================
// Bootstrap: autoload + .env + session
// =============================================
require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
    ]);
}

// =============================================
// Database connection (PDO via Unix socket)
// =============================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:unix_socket=%s;dbname=%s;charset=%s',
                $_ENV['DB_SOCKET'],
                $_ENV['DB_DATABASE'],
                $_ENV['DB_CHARSET']
            );
            $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            ]);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            die('Σφάλμα σύνδεσης με τη βάση δεδομένων.');
        }
    }
    return $pdo;
}

// =============================================
// Helpers
// =============================================

/** Output-encoded HTML escaping */
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Per-session CSRF token */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function csrfValid(): bool {
    return !empty($_POST['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token']);
}

/** Flash messages survive one redirect */
function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashRender(): string {
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $cls = match ($f['type']) {
            'success' => 'alert-success',
            'danger'  => 'alert-danger',
            'warning' => 'alert-warning',
            default   => 'alert-info',
        };
        $html .= '<div class="alert ' . $cls . '">' . e($f['message']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

/** Map ENUM position code → Greek label */
function positionLabel(string $code): string {
    return [
        'GK'  => 'Τερματοφύλακας',
        'DEF' => 'Αμυντικός',
        'MID' => 'Μέσος',
        'FWD' => 'Επιθετικός',
    ][$code] ?? $code;
}

/**
 * Secure image upload with real MIME sniffing and random filenames.
 * Returns ['path' => string] on success or ['error' => string] on failure.
 */
function uploadImage(array $file, string $subfolder): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['error' => 'Σφάλμα στα δεδομένα αρχείου.'];
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['error' => 'Δεν επιλέχθηκε αρχείο.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Σφάλμα μεταφόρτωσης (κωδικός ' . $file['error'] . ').'];
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['error' => 'Το αρχείο υπερβαίνει τα 2MB.'];
    }

    // Real MIME via fileinfo (don't trust client-provided type)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        return ['error' => 'Μη επιτρεπτός τύπος αρχείου (' . e($mime) . ').'];
    }

    $ext = $allowed[$mime];
    $targetDir = __DIR__ . '/uploads/' . $subfolder . '/';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        return ['error' => 'Δεν δημιουργήθηκε ο φάκελος αποθήκευσης.'];
    }

    // Random unpredictable filename
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $target   = $targetDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['error' => 'Αποτυχία μεταφόρτωσης.'];
    }

    return ['path' => 'uploads/' . $subfolder . '/' . $filename];
}
