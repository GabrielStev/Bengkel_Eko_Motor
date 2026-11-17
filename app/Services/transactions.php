<?php
declare(strict_types=1);

function token(): string
{
    $token = field('token', 32);
    if (!preg_match('/^[a-f0-9]{32}$/D', $token)) {
        throw new DomainException('Formulir transaksi tidak valid.');
    }
    return $token;
}

function items(string $key = 'qty'): array
{
    $submitted = $_POST[$key] ?? [];
    if (!is_array($submitted)) {
        throw new DomainException('Rincian barang tidak valid.');
    }
    $result = [];
    foreach ($submitted as $part => $qty) {
        if ($qty === '' || $qty === '0') {
            continue;
        }
        if (!filter_var($part, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ||
            !filter_var($qty, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>100000]])) {
            throw new DomainException('Jumlah sparepart harus antara 1 dan 100.000.');
        }
        $result[(int) $part] = (int) $qty;
    }
    ksort($result);
    return $result;
}

function stock(int $part, int $delta, int $actor, string $kind, ?string $ref, ?string $reason = null): void
{
    if ($delta === 0) {
        return;
    }
    $changed = query('UPDATE sparepart SET stok=stok+? WHERE id_sparepart=? AND stok+?>=0', [$delta,$part,$delta]);
    if ($changed->rowCount() !== 1) {
        throw new DomainException('Stok tidak cukup atau sparepart tidak tersedia.');
    }
    query('INSERT INTO mutasi_stok(part_id,pengguna_id,jumlah,jenis,referensi,alasan) VALUES (?,?,?,?,?,?)',
        [$part,$actor,$delta,$kind,$ref,$reason]);
}

function repeatReference(string $table, string $column, string $token, int $actor): ?string
{
    // Identifiers are supplied only by the transaction functions below.
    $record = row("SELECT $column,id_pengguna FROM $table WHERE token=?", [$token]);
    if (!$record) {
        return null;
    }
    if ((int) $record['id_pengguna'] !== $actor) {
        throw new DomainException('Formulir transaksi tidak valid.');
    }
    return $record[$column];
}

function checkout(int $actor): string
{
    $token = token();
    try {
        return atomic(function () use ($actor, $token): string {
            $existing = repeatReference('penjualan', 'no_transaksi', $token, $actor);
            if ($existing !== null) {
                return $existing;
            }
            $method = choice('metode', ['Tunai','Transfer','QRIS']);
            if (field('konfirmasi', 3) !== 'Ya') {
                throw new DomainException('Konfirmasi penerimaan pembayaran.');
            }
            $id = number('servis_id', 0);
            $jasa = false;
            $customer = null;
            $motor = null;
            if ($id) {
                $order = row('SELECT s.*,k.pelanggan_id FROM servis s JOIN kendaraan k ON k.id=s.kendaraan_id WHERE s.id=? FOR UPDATE', [$id]);
                if (!$order || $order['status'] !== 'Selesai' ||
                    row('SELECT no_transaksi FROM penjualan WHERE servis_id=? FOR UPDATE', [$id])) {
                    throw new DomainException('Servis belum selesai atau sudah memiliki nota.');
                }
                $jasa = row('SELECT nama,tarif FROM jasa WHERE id=?', [$order['jasa_id']]);
                $quantities = array_column(rows('SELECT part_id,qty FROM servis_part WHERE servis_id=? ORDER BY part_id', [$id]), 'qty', 'part_id');
                $customer = $order['pelanggan_id'];
                $motor = $order['kendaraan_id'];
            } else {
                $quantities = items();
                $customer = number('pelanggan_id', 0) ?: null;
                if ($customer && !row('SELECT id FROM pelanggan WHERE id=?', [$customer])) {
                    throw new DomainException('Pelanggan tidak tersedia.');
                }
            }
            $total = $jasa ? cents($jasa['tarif']) : 0;
            $lines = [];
            foreach ($quantities as $part => $qty) {
                $record = row('SELECT nama_barang,harga_jual,stok FROM sparepart WHERE id_sparepart=? FOR UPDATE', [$part]);
                if (!$record || $record['stok'] < $qty) {
                    throw new DomainException('Stok tidak cukup untuk ' . ($record['nama_barang'] ?? 'sparepart tersebut') . '.');
                }
                $subtotal = cents($record['harga_jual']) * $qty;
                $total += $subtotal;
                decimal($total);
                $lines[] = [$part,$qty,$record['harga_jual'],decimal($subtotal),$record['nama_barang']];
            }
            if ($total <= 0) {
                throw new DomainException('Pilih item dan pastikan total lebih dari nol.');
            }
            $received = $method === 'Tunai' ? cents(field('diterima', 13)) : $total;
            if ($received < $total) {
                throw new DomainException('Uang tunai yang diterima kurang.');
            }
            $ref = 'TRX-' . strtoupper(bin2hex(random_bytes(8)));
            query('INSERT INTO penjualan(no_transaksi,tanggal_transaksi,id_pengguna,total_bayar,metode_bayar,servis_id,pelanggan_id,kendaraan_id,token,diterima) VALUES (?,NOW(),?,?,?,?,?,?,?,?)',
                [$ref,$actor,decimal($total),$method,$id ?: null,$customer,$motor,$token,decimal($received)]);
            if ($jasa) {
                query('INSERT INTO detail_jasa(no_transaksi,nama,tarif) VALUES (?,?,?)', [$ref,$jasa['nama'],$jasa['tarif']]);
            }
            foreach ($lines as $line) {
                query('INSERT INTO detail_penjualan(no_transaksi,id_sparepart,jumlah,harga_satuan,subtotal,nama_snapshot) VALUES (?,?,?,?,?,?)', [$ref,...$line]);
                stock((int) $line[0], -(int) $line[1], $actor, 'Penjualan', $ref);
            }
            return $ref;
        });
    } catch (PDOException|DomainException $error) {
        $existing = repeatReference('penjualan', 'no_transaksi', $token, $actor);
        if ($existing !== null) {
            return $existing;
        }
        throw $error;
    }
}

function purchase(int $actor): string
{
    $token = token();
    try {
        return atomic(function () use ($actor, $token): string {
            $existing = repeatReference('pembelian', 'no_pembelian', $token, $actor);
            if ($existing !== null) {
                return $existing;
            }
            $supplier = number('pemasok_id');
            $status = choice('status_bayar', ['Lunas','Utang']);
            $quantities = items();
            if (!$quantities || !row('SELECT id_pemasok FROM pemasok WHERE id_pemasok=?', [$supplier])) {
                throw new DomainException('Pilih pemasok dan isi rincian barang.');
            }
            $total = 0;
            $lines = [];
            foreach ($quantities as $part => $qty) {
                $price = $_POST['harga'][$part] ?? '';
                $unit = cents(is_string($price) ? $price : '');
                if (!$unit) {
                    throw new DomainException('Harga beli harus lebih dari nol.');
                }
                $total += $unit * $qty;
                decimal($total);
                $lines[] = [$part,$qty,decimal($unit)];
            }
            $ref = 'BEL-' . strtoupper(bin2hex(random_bytes(8)));
            query('INSERT INTO pembelian(no_pembelian,tanggal_pembelian,id_pemasok,id_pengguna,total_pembelian,status_bayar,token,lunas_pada,lunas_oleh) VALUES (?,NOW(),?,?,?,?,?,?,?)',
                [$ref,$supplier,$actor,decimal($total),$status,$token,$status === 'Lunas' ? date('Y-m-d H:i:s') : null,$status === 'Lunas' ? $actor : null]);
            foreach ($lines as $line) {
                stock($line[0], $line[1], $actor, 'Pembelian', $ref);
                query('INSERT INTO detail_pembelian VALUES (?,?,?,?)', [$ref,...$line]);
            }
            return $ref;
        });
    } catch (PDOException $error) {
        return repeatReference('pembelian', 'no_pembelian', $token, $actor) ?? throw $error;
    }
}

function cancelSale(int $actor): string
{
    $ref = field('ref', 20);
    $reason = field('alasan', 500);
    $returns = items('kembali');
    atomic(function () use ($actor, $ref, $reason, $returns): void {
        $sale = row('SELECT status FROM penjualan WHERE no_transaksi=? FOR UPDATE', [$ref]);
        if (!$sale || $sale['status'] !== 'Lunas') {
            throw new DomainException('Transaksi tidak tersedia atau sudah dibatalkan.');
        }
        $sold = array_column(rows('SELECT id_sparepart,SUM(jumlah) AS qty FROM detail_penjualan WHERE no_transaksi=? GROUP BY id_sparepart', [$ref]), 'qty', 'id_sparepart');
        foreach ($returns as $part => $qty) {
            if ($qty > ($sold[$part] ?? 0)) {
                throw new DomainException('Jumlah yang kembali melebihi jumlah terjual.');
            }
            stock($part, $qty, $actor, 'Pengembalian', $ref, $reason);
        }
        query("UPDATE penjualan SET status='Batal',alasan_batal=?,batal_pada=NOW(),batal_oleh=? WHERE no_transaksi=?", [$reason,$actor,$ref]);
    });
    return $ref;
}
