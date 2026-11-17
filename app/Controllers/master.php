<?php
declare(strict_types=1);

function masters(): array
{
    return [
        'pelanggan'=>['pelanggan','id','Pelanggan',['nama'=>'Nama','telepon'=>'Telepon']],
        'kendaraan'=>['kendaraan','id','Kendaraan',['pelanggan_id'=>'Pelanggan','pelat'=>'Pelat nomor','model'=>'Model motor']],
        'sparepart'=>['sparepart','id_sparepart','Sparepart',['nama_barang'=>'Nama barang','kategori'=>'Kategori','harga_jual'=>'Harga jual','stok_minimum'=>'Stok minimum']],
        'pemasok'=>['pemasok','id_pemasok','Pemasok',['nama_pemasok'=>'Nama pemasok','no_hp'=>'Nomor HP','alamat'=>'Alamat']],
        'jasa'=>['jasa','id','Katalog jasa',['nama'=>'Nama jasa','tarif'=>'Tarif']],
        'pengguna'=>['pengguna','id_pengguna','Pengguna',['nama_lengkap'=>'Nama lengkap','username'=>'Username','peran'=>'Peran','status_aktif'=>'Status']],
    ];
}

function saveMaster(string $page, int $actor): void
{
    [$table,$pk,,$fields] = masters()[$page];
    $id = number('id', 0);
    $values = [];
    foreach ($fields as $key => $label) {
        $values[$key] = match ($key) {
            'pelanggan_id' => number($key),
            'stok_minimum' => number($key,0),
            'harga_jual','tarif' => decimal(cents(field($key,13))),
            'peran' => choice($key,['Pemilik','Kasir','Mekanik']),
            'status_aktif' => choice($key,['Aktif','Nonaktif']),
            'telepon','pelat','no_hp' => field($key,20),
            'username' => field($key,50),
            'kategori' => field($key,50),
            'alamat' => field($key,500),
            default => field($key),
        };
    }
    if ($page === 'jasa' && cents($values['tarif']) === 0) {
        throw new DomainException('Tarif harus lebih dari nol.');
    }
    if ($page === 'pengguna') {
        if ($id === $actor && ($values['peran'] !== 'Pemilik' || $values['status_aktif'] !== 'Aktif')) {
            throw new DomainException('Akun sendiri harus tetap aktif sebagai Owner.');
        }
        $password = $_POST['password'] ?? '';
        if (!$id || $password !== '') {
            if (!is_string($password) || strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0")) {
                throw new DomainException('Gunakan password sepanjang 12 sampai 72 byte.');
            }
            $values['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
    }
    if ($page === 'sparepart' && !$id) {
        $values['stok'] = 0;
    }
    if ($id) {
        $set = implode(',', array_map(fn($key) => "$key=?", array_keys($values)));
        query("UPDATE $table SET $set WHERE $pk=?", [...array_values($values),$id]);
    } else {
        $keys = implode(',', array_keys($values));
        $placeholders = implode(',', array_fill(0,count($values),'?'));
        query("INSERT INTO $table ($keys) VALUES ($placeholders)", array_values($values));
    }
}

function options(string $sql): array
{
    $choices = [];
    foreach (query($sql)->fetchAll(PDO::FETCH_NUM) as [$key,$label]) {
        $choices[$key] = $label;
    }
    return $choices;
}

function showMaster(string $page, bool $editable): void
{
    [$table,$pk,,$fields] = masters()[$page];
    $fieldsList = implode(',', array_keys($fields));
    $term = is_string($_GET['cari'] ?? null) ? $_GET['cari'] : '';
    $searchColumn = $page === 'kendaraan' ? 'pelat' : array_key_first($fields);
    $extra = $page === 'sparepart' ? ',stok' : '';
    $data = rows("SELECT $pk,$fieldsList$extra FROM $table WHERE CAST($searchColumn AS CHAR) LIKE ? ORDER BY $pk DESC", ['%'.$term.'%']);
    if ($page === 'kendaraan') {
        $customers = options('SELECT id,nama FROM pelanggan ORDER BY nama');
        foreach ($data as &$record) {
            $record['pelanggan_id'] = $customers[$record['pelanggan_id']] ?? '—';
        }
        unset($record);
    }
    echo '<form method="get" class="filters">';
    hidden('page',$page);
    input('cari','Cari','text',$term,false);
    button('Cari');
    $headers = ['ID',...array_values($fields)];
    if ($page === 'sparepart') {
        $headers[] = 'Stok';
        $headers[] = 'Kondisi';
        foreach ($data as &$record) {
            $record['kondisi'] = (int) $record['stok'] < 10 ? 'Stok rendah' :
                ($record['stok'] <= $record['stok_minimum'] ? 'Stok minimum' : 'Tersedia');
        }
        unset($record);
    }
    if ($editable) {
        $headers[] = 'Aksi';
    }
    table($headers,$data,$editable ? fn($r) => linkTo('Edit',$page,['edit'=>$r[$pk]]) : null);
    if (!$editable) {
        return;
    }
    $edit = filter_var($_GET['edit'] ?? 0, FILTER_VALIDATE_INT);
    $record = $edit ? row("SELECT $fieldsList FROM $table WHERE $pk=?", [$edit]) : false;
    echo '<section class="panel"><h2>' . ($record ? 'Edit data' : 'Tambah data') . '</h2>';
    formStart('save');
    hidden('id',$record ? $edit : 0);
    foreach ($fields as $key => $label) {
        $value = $record[$key] ?? '';
        if ($key === 'pelanggan_id') {
            selectField($key,$label,$customers,$value);
        } elseif ($key === 'peran') {
            selectField($key,$label,['Pemilik'=>'Owner','Kasir'=>'Kasir','Mekanik'=>'Mekanik'],$value);
        } elseif ($key === 'status_aktif') {
            selectField($key,$label,['Aktif'=>'Aktif','Nonaktif'=>'Nonaktif'],$value ?: 'Aktif');
        } else {
            input($key,$label,$key === 'stok_minimum' ? 'number' : 'text',$value);
        }
    }
    if ($page === 'pengguna') {
        input('password',$record ? 'Password baru (opsional)' : 'Password minimal 12 karakter','password','',!$record);
    }
    button('Simpan');
    echo '</section>';
    if ($page === 'sparepart') {
        echo '<section class="panel"><h2>Penyesuaian stok</h2>';
        formStart('adjust');
        selectField('part_id','Sparepart',options('SELECT id_sparepart,nama_barang FROM sparepart ORDER BY nama_barang'));
        input('stok','Stok aktual','number');
        input('alasan','Alasan penyesuaian');
        button('Simpan penyesuaian');
        echo '</section>';
        table(['Waktu','Sparepart','Perubahan','Jenis','Referensi','Alasan','Petugas'],rows('SELECT m.dibuat,s.nama_barang,m.jumlah,m.jenis,m.referensi,m.alasan,u.nama_lengkap FROM mutasi_stok m JOIN sparepart s ON s.id_sparepart=m.part_id JOIN pengguna u ON u.id_pengguna=m.pengguna_id ORDER BY m.id DESC LIMIT 100'));
    }
}
