<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors','0');
class AccessDenied extends RuntimeException {}

$root = dirname(__DIR__);
foreach (['app/Models/database.php','app/Views/helpers.php','app/Views/layout.php','app/Middleware/security.php',
    'app/Services/transactions.php','app/Controllers/master.php','app/Controllers/workshop.php',
    'app/Controllers/finance.php','routes/web.php'] as $module) {
    require $root . '/' . $module;
}

startSession();
$user = false;
try {
    $user = currentUser();
    route($user);
} catch (Throwable $error) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if ($error instanceof AccessDenied) {
        http_response_code(403);
        $message = $error->getMessage();
    } elseif ($error instanceof DomainException) {
        http_response_code(422);
        $message = $error->getMessage();
    } elseif ($error instanceof PDOException && $error->getCode() === '23000') {
        http_response_code(409);
        $message = 'Data sudah ada atau rujukan tidak tersedia. Periksa isian dan coba lagi.';
    } else {
        http_response_code(500);
        error_log('Bengkel Eko Motor: ' . get_class($error) . ' (' . $error->getCode() . ')');
        $message = 'Sistem tidak dapat memproses permintaan. Periksa konfigurasi dan koneksi database sesuai README.';
    }
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($user) {
        layout('Permintaan tidak berhasil',$user,'','<p role="alert" class="error">' . h($message) . '</p><p>' . linkTo('Kembali ke dashboard','dashboard') . '</p>');
    } else {
        loginPage($message);
    }
}
