<?php
declare(strict_types=1);

function route(array|false $user): void
{
    $page = $_GET['page'] ?? 'dashboard';
    $post = $_SERVER['REQUEST_METHOD'] === 'POST';
    if (!$post && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        fail(405,'Metode permintaan tidak tersedia.');
    }
    if ($page === 'login') {
        if ($user) {
            redirect('dashboard');
        }
        $message = null;
        if ($post) {
            verifyCsrf();
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            if (is_string($username) && is_string($password) && strlen($username) <= 50 &&
                strlen($password) <= 72 && !str_contains($password, chr(0)) && authenticate(trim($username),$password)) {
                redirect('dashboard');
            }
            $message = 'Login gagal. Periksa username dan password atau coba lagi nanti.';
        }
        loginPage($message);
        return;
    }
    if (!$user) {
        redirect('login');
    }
    if ($page === 'logout') {
        if (!$post) {
            fail(405,'Gunakan tombol Keluar.');
        }
        verifyCsrf();
        logout();
        redirect('login');
    }

    $staff = ['Pemilik','Kasir'];
    $owner = ['Pemilik'];
    $all = ['Pemilik','Kasir','Mekanik'];
    $routes = [
        'dashboard'=>['Dashboard',$all,[]],
        'pelanggan'=>['Pelanggan',$staff,['save'=>$staff]],
        'kendaraan'=>['Kendaraan',$staff,['save'=>$staff]],
        'sparepart'=>['Sparepart',$all,['save'=>$owner,'adjust'=>$owner]],
        'jasa'=>['Katalog jasa',$owner,['save'=>$owner]],
        'pemasok'=>['Pemasok',$owner,['save'=>$owner]],
        'katalog-pemasok'=>['Katalog pemasok',$owner,['save-catalog'=>$owner]],
        'pengguna'=>['Pengguna',$owner,['save'=>$owner]],
        'servis'=>['Order servis',$all,['create'=>$staff]],
        'pekerjaan'=>['Detail pekerjaan',$all,['start'=>['Pemilik','Mekanik'],'progress'=>['Pemilik','Mekanik'],'assign'=>$owner]],
        'checkout'=>['Pembayaran baru',$staff,['pay'=>$staff]],
        'penjualan'=>['Riwayat pembayaran',$staff,[]],
        'nota'=>['Nota penjualan',$staff,['cancel'=>$owner]],
        'pembelian'=>['Pembelian dan utang',$owner,['purchase'=>$owner]],
        'detail-beli'=>['Rincian pembelian',$owner,['settle'=>$owner]],
        'laporan'=>['Laporan',$owner,[]],
    ];
    if (!is_string($page) || !isset($routes[$page])) {
        fail(404,'Halaman tidak ditemukan.');
    }
    [$title,$roles,$actions] = $routes[$page];
    if (!in_array($user['peran'],$roles,true)) {
        throw new AccessDenied('Akun ini tidak memiliki akses ke halaman tersebut.');
    }
    if ($post) {
        verifyCsrf();
        $action = field('action',20);
        if (!in_array($user['peran'],$actions[$action] ?? [],true)) {
            throw new AccessDenied('Akun ini tidak memiliki akses ke tindakan tersebut.');
        }
        dispatchAction($page,$action,$user);
    }

    $id = (int) filter_var($_GET['id'] ?? 0,FILTER_VALIDATE_INT);
    $reference = is_string($_GET['ref'] ?? null) ? $_GET['ref'] : '';
    ob_start();
    match ($page) {
        'dashboard'=>showDashboard($user),
        'servis'=>showServices($user),
        'pekerjaan'=>showService(serviceRecord($id,$user),$user),
        'checkout'=>showCheckout(checkoutOrder($user)),
        'penjualan'=>showSales($user),
        'nota'=>showReceipt($reference,$user),
        'pembelian'=>showPurchases(),
        'katalog-pemasok'=>showSupplierCatalog($id),
        'detail-beli'=>showPurchase($reference),
        'laporan'=>showReports(),
        default=>showMaster($page,$page !== 'sparepart' || $user['peran'] === 'Pemilik'),
    };
    layout($title,$user,$page,(string) ob_get_clean());
}

function checkoutOrder(array $user): array|false
{
    if (!isset($_GET['servis'])) {
        return false;
    }
    $id = (int) filter_var($_GET['servis'], FILTER_VALIDATE_INT);
    $record = serviceRecord($id,$user);
    if ($record['status'] !== 'Selesai' || row('SELECT no_transaksi FROM penjualan WHERE servis_id=?',[$id])) {
        throw new DomainException('Servis belum selesai atau sudah memiliki nota.');
    }
    return $record;
}

function dispatchAction(string $page, string $action, array $user): never
{
    $actor = (int) $user['id_pengguna'];
    switch ($action) {
        case 'save-catalog':
            redirect('katalog-pemasok', ['id' => saveSupplierCatalog(), 'ok' => 1]);
        case 'save':
            saveMaster($page,$actor);
            break;
        case 'adjust':
            $part = number('part_id');
            $target = number('stok',0);
            $reason = field('alasan',500);
            atomic(function () use ($part,$target,$reason,$actor): void {
                $record = row('SELECT stok,kategori FROM sparepart WHERE id_sparepart=? FOR UPDATE',[$part]);
                if (!$record) {
                    throw new DomainException('Sparepart tidak ditemukan.');
                }
                if (isServiceItem($record['kategori'])) {
                    throw new DomainException('Jasa tidak memiliki persediaan barang.');
                }
                stock($part,$target-(int)$record['stok'],$actor,'Penyesuaian',null,$reason);
            });
            break;
        case 'create':
            saveOrder();
            break;
        case 'start':
        case 'progress':
        case 'assign':
            $status = $action === 'progress' && isset($_POST['finish']) ? 'finish' : $action;
            $id = updateService($user,$status);
            redirect('pekerjaan',['id'=>$id,'ok'=>1]);
        case 'pay':
            $reference = checkout($actor);
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['redirect' => url('nota', ['ref' => $reference, 'ok' => 1])], JSON_UNESCAPED_UNICODE);
                exit;
            }
            redirect('nota',['ref'=>$reference,'ok'=>1]);
        case 'cancel':
            redirect('nota',['ref'=>cancelSale($actor),'ok'=>1]);
        case 'purchase':
            redirect('detail-beli',['ref'=>purchase($actor),'ok'=>1]);
        case 'settle':
            $ref = field('ref',20);
            $result = query("UPDATE pembelian SET status_bayar='Lunas',lunas_pada=NOW(),lunas_oleh=? WHERE no_pembelian=? AND status_bayar='Utang'",[$actor,$ref]);
            if ($result->rowCount() !== 1) {
                throw new DomainException('Pembelian tidak tersedia atau sudah lunas.');
            }
            redirect('detail-beli',['ref'=>$ref,'ok'=>1]);
    }
    redirect($page,['ok'=>1]);
}
