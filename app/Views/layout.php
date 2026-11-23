<?php
declare(strict_types=1);

function workshopBrand(): string
{
    return '<div class="brand"><span><img src="assets/icons/workshop.svg" width="25" height="25" alt=""></span> Bengkel Eko Motor</div>';
}

function documentStart(string $title): void
{
    $assetRoot = dirname(__DIR__, 2) . '/public/assets/';
    $cssVersion = filemtime($assetRoot . 'css/app.css');
    $jsVersion = filemtime($assetRoot . 'js/app.js');
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . h($title) . ' • Bengkel Eko Motor</title>'
        . '<link rel="icon" type="image/svg+xml" href="assets/icons/workshop.svg?v=1">'
        . '<link rel="stylesheet" href="assets/css/app.css?v=' . h($cssVersion) . '"><script src="assets/js/app.js?v=' . h($jsVersion) . '" defer></script></head><body>';
}

function loginPage(?string $message): void
{
    documentStart('Masuk');
    echo '<main class="login"><section class="login-intro" aria-labelledby="login-title">'
        . '<div class="login-copy">' . workshopBrand()
        . '<p class="login-kicker">Sistem manajemen bengkel</p>'
        . '<h1 id="login-title">Kelola bengkel.<br><em>Lebih cepat.</em></h1>'
        . '<p class="login-description">Kelola servis, stok sparepart, transaksi, dan laporan bengkel dari satu tempat.</p>'
        . '<ul class="login-features">'
        . '<li><span aria-hidden="true">⚙</span><div><strong>Servis</strong><small>Pantau pekerjaan mekanik</small></div></li>'
        . '<li><span aria-hidden="true">▤</span><div><strong>Inventori</strong><small>Cek ketersediaan sparepart</small></div></li>'
        . '<li><span aria-hidden="true">↗</span><div><strong>Transaksi</strong><small>Catat pembayaran dan lihat laporan</small></div></li>'
        . '</ul></div></section><section class="login-access" aria-labelledby="access-title"><div class="login-form">'
        . '<h2 id="access-title">Selamat datang kembali</h2><p>Masuk ke akun Anda untuk melanjutkan.</p>';
    if ($message) {
        echo '<p role="alert" class="error">' . h($message) . '</p>';
    }
    formStart('login');
    echo '<label for="login-username">Username<input id="login-username" name="username" autocomplete="username" maxlength="50" required></label>'
        . '<label for="login-password">Kata sandi</label><div class="password-field">'
        . '<input id="login-password" type="password" name="password" autocomplete="current-password" required>'
        . '<button class="password-toggle" type="button" data-password-toggle aria-controls="login-password" aria-label="Tampilkan kata sandi" aria-pressed="false" hidden>'
        . '<svg aria-hidden="true" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">'
        . '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="2.75"/></svg></button></div>'
        . '<fieldset class="demo-accounts" data-demo-accounts hidden><legend>Pilih akun demo</legend><div>'
        . '<button type="button" data-demo-user="gabriel_owner" aria-pressed="false">Pemilik</button>'
        . '<button type="button" data-demo-user="warid_kasir" aria-pressed="false">Kasir</button>'
        . '<button type="button" data-demo-user="mekanik1" aria-pressed="false">Mekanik</button>'
        . '</div></fieldset>'
        . '<button class="login-submit" type="submit">Masuk ke Sistem <span aria-hidden="true">›</span></button></form>'
        . '</div></section></main></body></html>';
}

function layout(string $title, array $user, string $page, string $content): void
{
    [$role,$menus] = match ($user['peran']) {
        'Pemilik'=>['Owner',['dashboard'=>'Dashboard','pelanggan'=>'Pelanggan','kendaraan'=>'Kendaraan','servis'=>'Order servis','jasa'=>'Katalog jasa',
            'checkout'=>'Transaksi baru','penjualan'=>'Riwayat pembayaran','sparepart'=>'Stok sparepart','pemasok'=>'Pemasok','pembelian'=>'Pembelian & utang','laporan'=>'Laporan','pengguna'=>'Pengguna']],
        'Kasir'=>['Kasir',['dashboard'=>'Dashboard','checkout'=>'Transaksi baru','servis'=>'Order servis','pelanggan'=>'Pelanggan','kendaraan'=>'Kendaraan',
            'penjualan'=>'Riwayat pembayaran','sparepart'=>'Stok sparepart']],
        default=>['Mekanik',['dashboard'=>'Dashboard','servis'=>'Pekerjaan saya','sparepart'=>'Ketersediaan sparepart']],
    };
    $pageMenu = ['pekerjaan'=>'servis','nota'=>'penjualan','detail-beli'=>'pembelian'][$page] ?? $page;
    documentStart($title);
    echo '<div class="app"><aside>' . workshopBrand() . '<nav aria-label="Menu utama">';
    foreach ($menus as $key=>$label) {
        echo '<a href="' . h(url($key)) . '"' . ($pageMenu === $key ? ' aria-current="page"' : '') . '>' . h($label) . '</a>';
    }
    echo '</nav><div class="account"><strong>' . h($user['nama_lengkap']) . '</strong><small>' . h($role) . '</small><form method="post" action="' . h(url('logout')) . '">';
    hidden('csrf',$_SESSION['csrf']);
    button('Keluar');
    echo '</div></aside><main class="workspace"><header><div><p class="role">' . h($role) . '</p><h1>' . h($title) . '</h1></div>'
        . '<span class="date">' . h(date('d/m/Y')) . '</span></header>';
    if (isset($_GET['ok'])) {
        echo '<p role="status" class="success">Perubahan tersimpan.</p>';
    }
    echo $content . '</main></div></body></html>';
}
