<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/app/Models/database.php';

$accounts = [
    ['gabriel_owner', 'Owner Bengkel', 'Pemilik'],
    ['warid_kasir', 'Kasir Bengkel', 'Kasir'],
    ['mekanik1', 'Mekanik Bengkel', 'Mekanik'],
];
$passwords = [];
foreach ($accounts as [$username]) {
    fwrite(STDOUT, "Password baru untuk $username (12–72 byte): ");
    $password = rtrim((string) fgets(STDIN), "\r\n");
    if (strlen($password) < 12 || strlen($password) > 72 || str_contains($password, chr(0)) || in_array($password, $passwords, true)) {
        fwrite(STDERR, "Password harus berbeda untuk tiap akun dan panjangnya 12–72 byte. Belum ada data yang diubah.\n");
        exit(1);
    }
    $passwords[] = $password;
}
try {
    atomic(function () use ($accounts,$passwords): void {
        foreach ($accounts as $index=>[$username,$name,$role]) {
            $hash = password_hash($passwords[$index], PASSWORD_DEFAULT);
            query("INSERT INTO pengguna(nama_lengkap,username,password_hash,peran,status_aktif) VALUES (?,?,?,?,'Aktif')
                ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),peran=VALUES(peran),status_aktif='Aktif'",
                [$name,$username,$hash,$role]);
        }
    });
    fwrite(STDOUT, "Tiga akun demo aktif. Gunakan password yang baru kamu isi.\n");
} catch (Throwable $error) {
    fwrite(STDERR, "Gagal menyiapkan akun. Periksa konfigurasi dan pastikan baseline serta migrasi sudah diimpor.\n");
    exit(1);
}
