<?php
declare(strict_types=1);

function showCheckout(array|false $order): void
{
    formStart('pay');
    hidden('token',bin2hex(random_bytes(16)));
    hidden('servis_id',$order['id'] ?? 0);
    $total = 0;
    if ($order) {
        $parts = serviceParts((int) $order['id']);
        $total = cents($order['tarif']);
        $lines = [[$order['jasa'],1,rupiah($order['tarif']),rupiah($order['tarif'])]];
        foreach ($parts as $part) {
            $subtotal = cents($part['harga_jual']) * (int)$part['qty'];
            $total += $subtotal;
            $lines[] = [$part['nama_barang'],$part['qty'],rupiah($part['harga_jual']),rupiah(decimal($subtotal))];
        }
        echo '<p>' . h($order['pelanggan'] . ' · ' . $order['pelat']) . '</p>';
        table(['Item','Jumlah','Harga','Subtotal'],$lines);
        echo '<p><strong>Estimasi ' . h(rupiah(decimal($total))) . '</strong></p>';
    } else {
        selectField('pelanggan_id','Pelanggan', [0=>'Pelanggan umum'] + options('SELECT id,nama FROM pelanggan ORDER BY nama'),0);
        partInputs();
        echo '<p class="estimate" aria-live="polite"></p>';
    }
    selectField('metode','Metode pembayaran',['Tunai'=>'Tunai','Transfer'=>'Transfer','QRIS'=>'QRIS'],'Tunai');
    hidden('total_estimasi', $order ? $total : 0);
    input('diterima','Nominal pembayaran diterima','text','',true);
    echo '<p class="payment-feedback" role="status" aria-live="polite"></p>'
        . '<p class="payment-help">Isi angka tanpa pemisah ribuan. Tunai boleh lebih dari total dan menghasilkan kembalian. Transfer/QRIS harus sama dengan total.</p>';
    echo '<label class="check"><input type="checkbox" name="konfirmasi" value="Ya" required> Pembayaran sudah diterima</label>';
    button('Simpan pembayaran');
    echo '<dialog class="payment-dialog" aria-labelledby="payment-dialog-title" aria-describedby="payment-dialog-message">'
        . '<h2 id="payment-dialog-title">Periksa pembayaran</h2>'
        . '<p id="payment-dialog-message"></p><button type="button" data-close-payment>Perbaiki nominal</button></dialog>';
}

function showSales(array $user): void
{
    $own = $user['peran'] === 'Kasir';
    $where = $own ? 'WHERE s.id_pengguna=?' : '';
    echo '<p>' . linkTo('Buat transaksi baru','checkout') . '</p>';
    $data = rows("SELECT s.no_transaksi,s.tanggal_transaksi,u.nama_lengkap,s.metode_bayar,s.total_bayar,s.status
        FROM penjualan s JOIN pengguna u ON u.id_pengguna=s.id_pengguna $where ORDER BY s.tanggal_transaksi DESC",
        $own ? [$user['id_pengguna']] : []);
    foreach ($data as &$sale) {
        $sale['total_bayar'] = rupiah($sale['total_bayar']);
    }
    unset($sale);
    table(['Nomor','Tanggal dan jam','Kasir','Metode','Total','Status','Aksi'],$data,
        fn($sale)=>linkTo('Nota','nota',['ref'=>$sale['no_transaksi']]));
}

function showReceipt(string $ref, array $user): void
{
    $own = $user['peran'] === 'Kasir';
    $where = $own ? ' AND s.id_pengguna=?' : '';
    $sale = row("SELECT s.*,u.nama_lengkap,p.nama AS pelanggan,k.pelat,b.nama_lengkap AS pembatal
        FROM penjualan s JOIN pengguna u ON u.id_pengguna=s.id_pengguna
        LEFT JOIN pelanggan p ON p.id=s.pelanggan_id LEFT JOIN kendaraan k ON k.id=s.kendaraan_id
        LEFT JOIN pengguna b ON b.id_pengguna=s.batal_oleh WHERE s.no_transaksi=?$where",
        $own ? [$ref,$user['id_pengguna']] : [$ref]);
    if (!$sale) {
        throw new AccessDenied('Nota tidak tersedia untuk akun ini.');
    }
    echo '<section class="panel receipt"><h2>' . h($ref) . ' · ' . h($sale['status']) . '</h2><dl>';
    $metadata = ['Tanggal'=>$sale['tanggal_transaksi'],'Kasir'=>$sale['nama_lengkap'],
        'Pelanggan'=>$sale['pelanggan'] ?? 'Tidak tercatat','Pelat'=>$sale['pelat'] ?? 'Tidak tercatat',
        'Metode'=>$sale['metode_bayar'],'Total'=>rupiah($sale['total_bayar'])];
    if ($sale['metode_bayar'] === 'Tunai' && $sale['diterima'] !== null) {
        $metadata['Diterima'] = rupiah($sale['diterima']);
        $metadata['Kembalian'] = rupiah(decimal(cents($sale['diterima'])-cents($sale['total_bayar'])));
    }
    if ($sale['status'] === 'Batal') {
        $metadata['Dibatalkan'] = $sale['batal_pada'] . ' · ' . $sale['pembatal'];
        $metadata['Alasan'] = $sale['alasan_batal'];
    }
    foreach ($metadata as $label=>$value) {
        echo '<dt>' . h($label) . '</dt><dd>' . h($value) . '</dd>';
    }
    echo '</dl>';
    $parts = rows('SELECT d.id_sparepart,COALESCE(d.nama_snapshot,p.nama_barang) AS nama,d.jumlah,d.harga_satuan,d.subtotal
        FROM detail_penjualan d JOIN sparepart p ON p.id_sparepart=d.id_sparepart WHERE d.no_transaksi=? ORDER BY d.id_detail',[$ref]);
    $jasa = row('SELECT nama,qty,tarif FROM detail_jasa WHERE no_transaksi=?',[$ref]);
    $lines = array_map(fn($p)=>[$p['nama'],$p['jumlah'],rupiah($p['harga_satuan']),rupiah($p['subtotal'])],$parts);
    if ($jasa) {
        $lines[] = [$jasa['nama'],$jasa['qty'],rupiah($jasa['tarif']),rupiah($jasa['tarif'])];
    }
    table(['Item','Jumlah','Harga','Subtotal'],$lines);
    echo '<button type="button" data-print>Cetak nota</button></section>';
    if (!$own && $sale['status'] === 'Lunas') {
        echo '<section class="panel no-print"><h2>Batalkan penjualan</h2><p>Masukkan hanya jumlah yang kembali secara fisik.</p>';
        formStart('cancel');
        hidden('ref',$ref);
        input('alasan','Alasan pembatalan');
        $returns = [];
        foreach ($parts as $part) {
            $id = $part['id_sparepart'];
            $returns[$id] ??= ['nama'=>$part['nama'],'qty'=>0];
            $returns[$id]['qty'] += (int)$part['jumlah'];
        }
        foreach ($returns as $id=>$part) {
            input('kembali[' . $id . ']', $part['nama'] . ' (terjual ' . $part['qty'] . ')','number',0);
        }
        button('Batalkan transaksi');
        echo '</section>';
    }
}

function showPurchases(): void
{
    table(['Nomor','Tanggal','Pemasok','Petugas','Total','Status','Pelunasan','Aksi'],
        array_map(function ($record) {
            $record['total_pembelian'] = rupiah($record['total_pembelian']);
            return $record;
        },rows('SELECT b.no_pembelian,b.tanggal_pembelian,p.nama_pemasok,u.nama_lengkap,b.total_pembelian,b.status_bayar,b.lunas_pada
            FROM pembelian b JOIN pemasok p ON p.id_pemasok=b.id_pemasok JOIN pengguna u ON u.id_pengguna=b.id_pengguna ORDER BY b.tanggal_pembelian DESC')),
        fn($r)=>linkTo('Rincian','detail-beli',['ref'=>$r['no_pembelian']]));
    echo '<section class="panel"><h2>Catat barang diterima</h2>';
    $id = filter_var($_GET['pemasok'] ?? '0', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if ($id === false) {
        throw new DomainException('Pilihan pemasok tidak valid.');
    }
    $supplier = $id ? supplierRecord($id) : false;
    echo '<form method="get" data-supplier-picker>';
    hidden('page', 'pembelian');
    selectField('pemasok', 'Pilih pemasok', options('SELECT id_pemasok,nama_pemasok FROM pemasok ORDER BY nama_pemasok'), $id);
    echo '</form>';
    if (!$supplier) {
        echo '<p>Pilih pemasok untuk menampilkan barang yang tersedia beserta jumlah dan harga belinya.</p>';
        purchaseCatalogTable(rows("SELECT id_sparepart,nama_barang,stok,harga_jual FROM sparepart WHERE LOWER(TRIM(kategori))<>'jasa servis' ORDER BY nama_barang"), false);
    } else {
        $parts = supplierCatalog($id);
        echo '<h3>' . h($supplier['nama_pemasok']) . '</h3>';
        if (!$parts) {
            purchaseCatalogTable([], true);
            echo '<p>' . linkTo('Isi katalog dan harga pemasok', 'katalog-pemasok', ['id' => $id]) . '</p>';
        } else {
            formStart('purchase');
            hidden('token', bin2hex(random_bytes(16)));
            hidden('pemasok_id', $id);
            selectField('status_bayar', 'Status pembayaran', ['Lunas' => 'Lunas', 'Utang' => 'Utang']);
            purchaseCatalogTable($parts, true);
            echo '<p class="purchase-total" aria-live="polite"><strong>Total pembelian: <output data-purchase-total>Rp0,00</output></strong></p>';
            button('Simpan pembelian');
        }
    }
    echo '</section>';
}

function showPurchase(string $ref): void
{
    $record = row('SELECT b.*,p.nama_pemasok,u.nama_lengkap AS pelunas FROM pembelian b
        JOIN pemasok p ON p.id_pemasok=b.id_pemasok LEFT JOIN pengguna u ON u.id_pengguna=b.lunas_oleh WHERE b.no_pembelian=?',[$ref]);
    if (!$record) {
        throw new DomainException('Pembelian tidak ditemukan.');
    }
    echo '<h2>' . h($ref) . ' · ' . h($record['nama_pemasok']) . '</h2><p>'
        . h(rupiah($record['total_pembelian']) . ' · ' . $record['status_bayar']) . '</p>';
    $lines = rows('SELECT p.nama_barang,d.qty,d.harga,d.qty*d.harga AS subtotal FROM detail_pembelian d
        JOIN sparepart p ON p.id_sparepart=d.part_id WHERE d.no_pembelian=?',[$ref]);
    table(['Sparepart','Jumlah','Harga beli','Subtotal'],array_map(fn($r)=>[$r['nama_barang'],$r['qty'],rupiah($r['harga']),rupiah($r['subtotal'])],$lines));
    if ($record['status_bayar'] === 'Utang') {
        formStart('settle');
        hidden('ref',$ref);
        button('Catat pelunasan penuh');
    } elseif ($record['lunas_pada']) {
        echo '<p>Pelunasan ' . h($record['lunas_pada'] . ' · ' . $record['pelunas']) . '</p>';
    }
}

function lowStock(): array
{
    return rows('SELECT nama_barang,stok,stok_minimum FROM sparepart WHERE LOWER(TRIM(kategori))<>\'jasa servis\' AND stok<=stok_minimum ORDER BY stok,nama_barang');
}

function reportDate(string $key, string $default): string
{
    $value = $_GET[$key] ?? $default;
    if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D',$value,$parts) ||
        !checkdate((int)$parts[2],(int)$parts[3],(int)$parts[1])) {
        throw new DomainException('Tanggal laporan tidak valid.');
    }
    return $value;
}

function showReports(): void
{
    $start = reportDate('awal',date('Y-m-01'));
    $end = reportDate('akhir',date('Y-m-d'));
    if ($end < $start) {
        throw new DomainException('Tanggal akhir harus sama atau sesudah awal.');
    }
    $dates = [$start,$end];
    echo '<form method="get" class="filters">';
    hidden('page','laporan');
    input('awal','Dari','date',$start);
    input('akhir','Sampai','date',$end);
    button('Tampilkan');
    $sales = rows("SELECT DATE(tanggal_transaksi) AS hari,COUNT(*) AS jumlah,SUM(total_bayar) AS total FROM penjualan
        WHERE status='Lunas' AND tanggal_transaksi>=? AND tanggal_transaksi<DATE_ADD(?,INTERVAL 1 DAY) GROUP BY DATE(tanggal_transaksi) ORDER BY hari",$dates);
    echo '<h2>Penjualan per hari</h2>';
    table(['Hari','Transaksi','Penjualan'],array_map(fn($r)=>[$r['hari'],$r['jumlah'],rupiah($r['total'])],$sales));
    $methods = rows("SELECT metode_bayar,COUNT(*) AS jumlah,SUM(total_bayar) AS total FROM penjualan
        WHERE status='Lunas' AND tanggal_transaksi>=? AND tanggal_transaksi<DATE_ADD(?,INTERVAL 1 DAY) GROUP BY metode_bayar",$dates);
    echo '<h2>Penerimaan per metode</h2><p>Penjualan berstatus Lunas dalam periode terpilih.</p>';
    table(['Metode','Transaksi','Penerimaan'],array_map(fn($r)=>[$r['metode_bayar'],$r['jumlah'],rupiah($r['total'])],$methods));
    $purchases = rows('SELECT p.nama_pemasok,COUNT(*) AS jumlah,SUM(b.total_pembelian) AS total FROM pembelian b JOIN pemasok p ON p.id_pemasok=b.id_pemasok
        WHERE b.tanggal_pembelian>=? AND b.tanggal_pembelian<DATE_ADD(?,INTERVAL 1 DAY) GROUP BY p.id_pemasok,p.nama_pemasok',$dates);
    echo '<h2>Pembelian per pemasok</h2><p>Nilai barang diterima dalam periode terpilih, termasuk pembelian utang.</p>';
    table(['Pemasok','Pembelian','Total'],array_map(fn($r)=>[$r['nama_pemasok'],$r['jumlah'],rupiah($r['total'])],$purchases));
    $debts = rows("SELECT p.nama_pemasok,SUM(b.total_pembelian) AS total FROM pembelian b JOIN pemasok p ON p.id_pemasok=b.id_pemasok
        WHERE b.status_bayar='Utang' GROUP BY p.id_pemasok,p.nama_pemasok");
    echo '<h2>Utang terbuka saat ini</h2>';
    table(['Pemasok','Sisa utang'],array_map(fn($r)=>[$r['nama_pemasok'],rupiah($r['total'])],$debts));
    echo '<h2>Sparepart pada batas minimum</h2>';
    table(['Sparepart','Stok','Minimum'],lowStock());
}

function showDashboard(array $user): void
{
    $role = $user['peran'];
    $actor = $user['id_pengguna'];
    $stockAlerts = rows('SELECT nama_barang,stok FROM sparepart WHERE LOWER(TRIM(kategori))<>\'jasa servis\' AND stok<=15 ORDER BY stok,nama_barang');
    $lowCount = count(array_filter($stockAlerts, fn($part) => (int) $part['stok'] < 10));
    $cards = [];
    if ($role === 'Mekanik') {
        $counts = array_column(rows('SELECT status,COUNT(*) AS total FROM servis WHERE mekanik_id=? GROUP BY status',[$actor]),'total','status');
        $cards = [
            ['Menunggu', $counts['Menunggu'] ?? 0, 'amber', 'servis'],
            ['Dikerjakan', $counts['Dikerjakan'] ?? 0, 'blue', 'servis'],
            ['Selesai', $counts['Selesai'] ?? 0, 'green', 'servis'],
        ];
    } elseif ($role === 'Kasir') {
        $today = row("SELECT COUNT(*) AS total FROM penjualan WHERE id_pengguna=? AND status='Lunas' AND tanggal_transaksi>=CURDATE() AND tanggal_transaksi<CURDATE()+INTERVAL 1 DAY",[$actor])['total'];
        $ready = row("SELECT COUNT(*) AS total FROM servis s LEFT JOIN penjualan p ON p.servis_id=s.id WHERE s.status='Selesai' AND p.no_transaksi IS NULL")['total'];
        $cards = [['Transaksi saya hari ini',$today,'green','penjualan'],['Servis siap bayar',$ready,'blue','servis']];
    } else {
        $sales = row("SELECT COALESCE(SUM(total_bayar),0) AS total FROM penjualan WHERE status='Lunas' AND tanggal_transaksi>=CURDATE() AND tanggal_transaksi<CURDATE()+INTERVAL 1 DAY")['total'];
        $purchases = row('SELECT COALESCE(SUM(total_pembelian),0) AS total FROM pembelian WHERE tanggal_pembelian>=CURDATE() AND tanggal_pembelian<CURDATE()+INTERVAL 1 DAY')['total'];
        $debts = row("SELECT COALESCE(SUM(total_pembelian),0) AS total FROM pembelian WHERE status_bayar='Utang'")['total'];
        $queue = row("SELECT COUNT(*) AS total FROM servis WHERE status IN ('Menunggu','Dikerjakan')")['total'];
        $cards = [
            ['Omzet hari ini',rupiah($sales),'green','laporan'],
            ['Pembelian hari ini',rupiah($purchases),'amber','pembelian'],
            ['Utang terbuka',rupiah($debts),'red','pembelian'],
            ['Antrean servis',$queue,'blue','servis'],
        ];
    }
    $cards[] = ['Sparepart di bawah 10', $lowCount, $lowCount ? 'red' : 'green', 'sparepart'];
    echo '<div class="cards dashboard-cards">';
    foreach ($cards as [$label,$value,$tone,$page]) {
        echo '<a class="card tone-' . h($tone) . '" href="' . h(url($page)) . '"><small>' . h($label)
            . '</small><strong>' . h($value) . '</strong></a>';
    }
    echo '</div><div class="dashboard-grid"><section class="panel dashboard-main">';
    if ($role === 'Kasir') {
        echo '<div class="dashboard-heading"><h2>Pembayaran terbaru</h2>' . linkTo('Transaksi baru','checkout') . '</div>';
        $recent = rows('SELECT no_transaksi,tanggal_transaksi,total_bayar,status FROM penjualan WHERE id_pengguna=? ORDER BY tanggal_transaksi DESC,no_transaksi DESC LIMIT 5',[$actor]);
        foreach ($recent as &$sale) {
            $sale['total_bayar'] = rupiah($sale['total_bayar']);
        }
        unset($sale);
        table(['Nomor','Tanggal','Total','Status','Aksi'],$recent,fn($sale)=>linkTo('Nota','nota',['ref'=>$sale['no_transaksi']]));
    } else {
        $mechanic = $role === 'Mekanik';
        echo '<div class="dashboard-heading"><h2>' . ($mechanic ? 'Pekerjaan saya' : 'Aktivitas servis')
            . '</h2>' . linkTo('Lihat semua','servis') . '</div>';
        $where = $mechanic ? 'WHERE s.mekanik_id=?' : '';
        $recent = rows("SELECT s.id,k.pelat,j.nama,s.status,s.diperbarui FROM servis s
            JOIN kendaraan k ON k.id=s.kendaraan_id JOIN jasa j ON j.id=s.jasa_id
            $where ORDER BY (s.status='Selesai'),s.diperbarui DESC,s.id DESC LIMIT 6",$mechanic ? [$actor] : []);
        table(['Order','Motor','Jasa','Status','Diperbarui','Aksi'],$recent,fn($job)=>linkTo('Buka','pekerjaan',['id'=>$job['id']]));
    }
    echo '</section><section class="panel dashboard-stock"><div class="dashboard-heading"><h2>Peringatan stok</h2>'
        . linkTo('Lihat stok','sparepart') . '</div>';
    if (!$stockAlerts) {
        echo '<p class="stock-clear">Semua sparepart memiliki lebih dari 15 unit.</p>';
    } else {
        echo '<ul class="stock-list">';
        foreach ($stockAlerts as $part) {
            echo '<li><span>' . h($part['nama_barang']) . '</span>'
                . '<span class="stock-alert-detail"><span class="stock-units">' . h($part['stok']) . ' unit</span>'
                . stockBadge((int) $part['stok'], stockCondition((int) $part['stok'])) . '</span></li>';
        }
        echo '</ul>';
    }
    echo '</section></div>';
    showMonthlyBestSellers();
}

function showMonthlyBestSellers(): void
{
    $start = new DateTimeImmutable('first day of this month midnight');
    $end = $start->modify('+1 month');
    $parts = rows("SELECT s.nama_barang, SUM(d.jumlah) AS unit_terjual
        FROM penjualan p
        JOIN detail_penjualan d ON d.no_transaksi = p.no_transaksi
        JOIN sparepart s ON s.id_sparepart = d.id_sparepart
        WHERE LOWER(TRIM(s.kategori)) <> 'jasa servis' AND p.status = 'Lunas' AND p.tanggal_transaksi >= ? AND p.tanggal_transaksi < ?
        GROUP BY s.id_sparepart, s.nama_barang
        ORDER BY unit_terjual DESC, s.nama_barang, s.id_sparepart
        LIMIT 5", [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);

    $months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $period = $months[(int) $start->format('n') - 1] . ' ' . $start->format('Y');
    echo '<section class="panel best-sellers"><div class="dashboard-heading"><h2>Sparepart terlaris</h2>'
        . '<span class="chart-period">' . h($period) . '</span></div>';
    if (!$parts) {
        echo '<p>Belum ada penjualan sparepart yang lunas bulan ini.</p></section>';
        return;
    }

    $max = (int) $parts[0]['unit_terjual'];
    echo '<p>Jumlah unit terjual dari transaksi lunas.</p><ol class="sales-ranking">';
    foreach ($parts as $part) {
        $quantity = (int) $part['unit_terjual'];
        echo '<li><div class="ranking-label"><span>' . h($part['nama_barang']) . '</span><strong>'
            . h(number_format($quantity, 0, ',', '.')) . ' unit</strong></div>'
            . '<meter min="0" max="' . $max . '" value="' . $quantity . '" aria-hidden="true"></meter></li>';
    }
    echo '</ol></section>';
}
