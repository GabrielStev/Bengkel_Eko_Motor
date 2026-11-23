<?php
declare(strict_types=1);

function availableMechanics(): array
{
    return options("SELECT id_pengguna,nama_lengkap FROM pengguna WHERE peran='Mekanik' AND status_aktif='Aktif' ORDER BY nama_lengkap");
}

function saveOrder(): void
{
    $vehicle = number('kendaraan_id');
    $mechanic = number('mekanik_id');
    $service = number('jasa_id');
    $complaint = field('keluhan',500);
    if (!isset(availableMechanics()[$mechanic]) || !row('SELECT id FROM kendaraan WHERE id=?',[$vehicle]) ||
        !row('SELECT id FROM jasa WHERE id=?',[$service])) {
        throw new DomainException('Pilih motor, mekanik aktif, dan jasa yang tersedia.');
    }
    query('INSERT INTO servis(kendaraan_id,mekanik_id,jasa_id,keluhan) VALUES (?,?,?,?)',[$vehicle,$mechanic,$service,$complaint]);
}

function serviceRecord(int $id, array $user, bool $lock = false): array
{
    $own = $user['peran'] === 'Mekanik';
    $where = $own ? ' AND s.mekanik_id=?' : '';
    $record = row("SELECT s.*,k.pelat,k.model,p.nama AS pelanggan,j.nama AS jasa,j.tarif,u.nama_lengkap AS mekanik
        FROM servis s JOIN kendaraan k ON k.id=s.kendaraan_id JOIN pelanggan p ON p.id=k.pelanggan_id
        JOIN jasa j ON j.id=s.jasa_id JOIN pengguna u ON u.id_pengguna=s.mekanik_id
        WHERE s.id=?$where" . ($lock ? ' FOR UPDATE' : ''),$own ? [$id,$user['id_pengguna']] : [$id]);
    if (!$record) {
        throw new AccessDenied('Pekerjaan servis tidak tersedia untuk akun ini.');
    }
    return $record;
}

function updateService(array $user, string $action): int
{
    $id = number('id');
    atomic(function () use ($id,$user,$action): void {
        $record = serviceRecord($id,$user,true);
        if ($action === 'assign') {
            $mechanic = number('mekanik_id');
            if ($record['status'] === 'Selesai' || !isset(availableMechanics()[$mechanic])) {
                throw new DomainException('Penugasan hanya tersedia untuk servis aktif dan mekanik aktif.');
            }
            query('UPDATE servis SET mekanik_id=? WHERE id=?',[$mechanic,$id]);
            return;
        }
        if ($action === 'start' && $record['status'] === 'Menunggu') {
            query("UPDATE servis SET status='Dikerjakan' WHERE id=?",[$id]);
            return;
        }
        if ($record['status'] !== 'Dikerjakan' || !in_array($action,['progress','finish'],true)) {
            throw new DomainException('Status pekerjaan sudah berubah. Muat ulang halaman.');
        }
        $notes = field('catatan',1000,$action === 'finish');
        $quantities = items();
        query('DELETE FROM servis_part WHERE servis_id=?',[$id]);
        foreach ($quantities as $part=>$qty) {
            if (!row('SELECT id_sparepart FROM sparepart WHERE id_sparepart=?',[$part])) {
                throw new DomainException('Sparepart tidak tersedia.');
            }
            query('INSERT INTO servis_part VALUES (?,?,?)',[$id,$part,$qty]);
        }
        query('UPDATE servis SET catatan=?,status=? WHERE id=?',[$notes,$action === 'finish' ? 'Selesai' : 'Dikerjakan',$id]);
    });
    return $id;
}

function partInputs(array $values = [], string $mode = 'qty'): void
{
    echo '<div class="scroll"><table><thead><tr><th>Sparepart</th><th>Stok</th><th>Harga jual</th><th>Jumlah</th>'
        . ($mode === 'purchase' ? '<th>Harga beli / unit</th>' : '') . '</tr></thead><tbody>';
    $where = $mode === 'purchase' ? " WHERE LOWER(TRIM(kategori)) <> 'jasa servis'" : '';
    foreach (rows('SELECT id_sparepart,nama_barang,stok,harga_jual,kategori FROM sparepart' . $where . ' ORDER BY nama_barang') as $part) {
        $id = (int) $part['id_sparepart'];
        echo '<tr><td>' . h($part['nama_barang']) . '</td><td>' . h(isServiceItem($part['kategori']) ? '0 (Jasa)' : $part['stok']) . '</td><td>' . h(rupiah($part['harga_jual']))
            . '</td><td><input aria-label="' . h('Jumlah ' . $part['nama_barang']) . '" type="number" min="0" max="100000" data-unit="' . h($part['harga_jual']) . '" name="qty[' . $id . ']" value="' . h($values[$id] ?? '') . '"></td>';
        if ($mode === 'purchase') {
            echo '<td><input aria-label="' . h('Harga beli ' . $part['nama_barang']) . '" inputmode="decimal" name="harga[' . $id . ']"></td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function showServices(array $user): void
{
    $own = $user['peran'] === 'Mekanik';
    $where = $own ? 'WHERE s.mekanik_id=?' : '';
    table(['ID','Masuk','Pelanggan','Motor','Jasa','Mekanik','Status','Aksi'],
        rows("SELECT s.id,s.dibuat,p.nama,k.pelat,j.nama AS jasa,u.nama_lengkap,s.status FROM servis s
            JOIN kendaraan k ON k.id=s.kendaraan_id JOIN pelanggan p ON p.id=k.pelanggan_id
            JOIN jasa j ON j.id=s.jasa_id JOIN pengguna u ON u.id_pengguna=s.mekanik_id $where ORDER BY s.id DESC",
            $own ? [$user['id_pengguna']] : []),
        fn($record)=>linkTo('Buka','pekerjaan',['id'=>$record['id']]));
    if (!$own) {
        echo '<section class="panel"><h2>Buat order servis</h2>';
        formStart('create');
        selectField('kendaraan_id','Motor',options("SELECT k.id,CONCAT(k.pelat,' · ',k.model,' · ',p.nama) FROM kendaraan k JOIN pelanggan p ON p.id=k.pelanggan_id ORDER BY k.pelat"));
        selectField('mekanik_id','Mekanik',availableMechanics());
        selectField('jasa_id','Jasa',options('SELECT id,nama FROM jasa ORDER BY nama'));
        input('keluhan','Keluhan pelanggan');
        button('Buat order');
        echo '</section>';
    }
}

function serviceParts(int $id): array
{
    return rows('SELECT p.id_sparepart,p.nama_barang,s.qty,p.harga_jual,p.stok FROM servis_part s
        JOIN sparepart p ON p.id_sparepart=s.part_id WHERE s.servis_id=? ORDER BY p.nama_barang',[$id]);
}

function showService(array $record, array $user): void
{
    $id = (int) $record['id'];
    $parts = serviceParts($id);
    $canWork = $user['peran'] !== 'Kasir';
    echo '<div class="panel"><h2>Order #' . $id . ' · ' . h($record['status']) . '</h2><dl>';
    foreach (['pelanggan'=>'Pelanggan','pelat'=>'Pelat','model'=>'Motor','jasa'=>'Jasa','mekanik'=>'Mekanik','keluhan'=>'Keluhan','catatan'=>'Catatan','diperbarui'=>'Diperbarui'] as $key=>$label) {
        echo '<dt>' . h($label) . '</dt><dd>' . h($record[$key] ?? '—') . '</dd>';
    }
    echo '</dl></div>';
    table(['Sparepart','Jumlah tercatat'],array_map(fn($p)=>[$p['nama_barang'],$p['qty']],$parts));
    if ($user['peran'] === 'Pemilik' && $record['status'] !== 'Selesai') {
        formStart('assign');
        hidden('id',$id);
        selectField('mekanik_id','Penugasan mekanik',availableMechanics(),$record['mekanik_id']);
        button('Ubah penugasan');
    }
    if ($canWork && $record['status'] === 'Menunggu') {
        formStart('start');
        hidden('id',$id);
        button('Mulai servis');
    }
    if ($canWork && $record['status'] === 'Dikerjakan') {
        echo '<section class="panel"><h2>Progres dan pemakaian sparepart</h2>';
        formStart('progress');
        hidden('id',$id);
        echo '<label>Tindakan dan progres<textarea name="catatan" maxlength="1000">' . h($record['catatan']) . '</textarea></label>';
        partInputs(array_column($parts,'qty','id_sparepart'));
        echo '<button>Simpan progres</button> <button name="finish" value="1">Selesaikan servis</button></form></section>';
    }
    if ($user['peran'] !== 'Mekanik' && $record['status'] === 'Selesai') {
        $sale = row('SELECT no_transaksi,id_pengguna,status FROM penjualan WHERE servis_id=?',[$id]);
        if (!$sale) {
            echo linkTo('Tagihkan servis','checkout',['servis'=>$id]);
        } elseif ($user['peran'] === 'Pemilik' || (int)$sale['id_pengguna'] === (int)$user['id_pengguna']) {
            echo linkTo('Buka nota · ' . $sale['status'],'nota',['ref'=>$sale['no_transaksi']]);
        } else {
            echo '<p>Servis sudah memiliki nota.</p>';
        }
    }
}
