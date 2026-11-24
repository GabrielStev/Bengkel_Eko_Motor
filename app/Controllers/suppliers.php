<?php
declare(strict_types=1);

function supplierRecord(int $id, bool $lock = false): array
{
    $supplier = row('SELECT id_pemasok,nama_pemasok FROM pemasok WHERE id_pemasok=?' . ($lock ? ' FOR UPDATE' : ''), [$id]);
    if (!$supplier) {
        throw new DomainException('Pemasok tidak ditemukan.');
    }
    return $supplier;
}

function supplierCatalog(int $id): array
{
    return rows("SELECT s.id_sparepart,s.nama_barang,s.stok,s.harga_jual,k.harga_beli
        FROM pemasok_sparepart k JOIN sparepart s ON s.id_sparepart=k.id_sparepart
        WHERE k.id_pemasok=? AND LOWER(TRIM(s.kategori))<>'jasa servis'
        ORDER BY s.nama_barang,s.id_sparepart", [$id]);
}

function saveSupplierCatalog(): int
{
    $supplier = number('pemasok_id');
    $selected = $_POST['tersedia'] ?? [];
    $prices = $_POST['harga'] ?? [];
    if (!is_array($selected) || !is_array($prices)) {
        throw new DomainException('Isian katalog tidak valid.');
    }
    $entries = [];
    foreach ($selected as $key => $checked) {
        $part = filter_var($key, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $value = $prices[$key] ?? '';
        if (!$part || $checked !== '1' || !is_string($value)) {
            throw new DomainException('Barang atau harga katalog tidak valid.');
        }
        $price = cents(trim($value));
        if ($price <= 0) {
            throw new DomainException('Isi harga beli lebih dari nol untuk setiap barang yang dicentang.');
        }
        $entries[$part] = decimal($price);
    }
    ksort($entries);
    atomic(function () use ($supplier, $entries): void {
        supplierRecord($supplier, true);
        foreach ($entries as $part => $price) {
            $item = row('SELECT kategori FROM sparepart WHERE id_sparepart=? FOR UPDATE', [$part]);
            if (!$item || isServiceItem($item['kategori'])) {
                throw new DomainException('Katalog pemasok hanya menerima sparepart fisik yang terdaftar.');
            }
        }
        query('DELETE FROM pemasok_sparepart WHERE id_pemasok=?', [$supplier]);
        foreach ($entries as $part => $price) {
            query('INSERT INTO pemasok_sparepart(id_pemasok,id_sparepart,harga_beli) VALUES (?,?,?)', [$supplier,$part,$price]);
        }
    });
    return $supplier;
}

function showSupplierCatalog(int $id): void
{
    $supplier = supplierRecord($id);
    $saved = array_column(supplierCatalog($id), 'harga_beli', 'id_sparepart');
    echo '<p>' . linkTo('Kembali ke pemasok', 'pemasok') . '</p><h2>' . h($supplier['nama_pemasok']) . '</h2>';
    echo '<p>Centang barang yang dijual pemasok ini, lalu isi harga belinya per unit. Untuk toko serba sparepart, gunakan Pilih semua barang.</p>';
    formStart('save-catalog');
    hidden('pemasok_id', $id);
    echo '<label class="check"><input type="checkbox" data-catalog-all> Pilih semua barang</label>';
    echo '<div class="supplier-catalog-scroll scroll"><table><thead><tr><th>Tersedia</th><th>Sparepart</th><th>Stok bengkel</th><th>Harga jual bengkel</th><th>Harga beli pemasok</th></tr></thead><tbody>';
    foreach (rows("SELECT id_sparepart,nama_barang,stok,harga_jual FROM sparepart WHERE LOWER(TRIM(kategori))<>'jasa servis' ORDER BY nama_barang") as $part) {
        $key = (int) $part['id_sparepart'];
        echo '<tr><td><input type="checkbox" data-catalog-item name="tersedia[' . $key . ']" value="1" aria-label="'
            . h('Dijual oleh pemasok: ' . $part['nama_barang']) . '"' . (isset($saved[$key]) ? ' checked' : '') . '></td>'
            . '<td>' . h($part['nama_barang']) . '</td><td>' . h($part['stok']) . '</td><td>' . h(rupiah($part['harga_jual'])) . '</td>'
            . '<td><input inputmode="decimal" name="harga[' . $key . ']" value="' . h($saved[$key] ?? '')
            . '" aria-label="' . h('Harga beli ' . $part['nama_barang']) . '" placeholder="Contoh 150000"></td></tr>';
    }
    echo '</tbody></table></div><p>Isi angka tanpa pemisah ribuan. Barang baru perlu dicentang di katalog pemasok yang menyediakannya.</p>';
    button('Simpan katalog pemasok');
    echo '<p>' . linkTo('Buat pembelian dari pemasok ini', 'pembelian', ['pemasok' => $id]) . '</p>';
}

function purchaseCatalogTable(array $parts, bool $selected): void
{
    echo '<div class="supplier-catalog-scroll scroll"><table><thead><tr><th>Sparepart</th><th>Stok bengkel</th><th>Harga jual bengkel</th>'
        . ($selected ? '<th>Jumlah beli</th><th>Harga beli pemasok / unit</th>' : '') . '</tr></thead><tbody>';
    if (!$parts) {
        echo '<tr><td colspan="' . ($selected ? 5 : 3) . '">Belum ada barang dalam katalog pemasok ini.</td></tr>';
    }
    foreach ($parts as $part) {
        $key = (int) $part['id_sparepart'];
        echo '<tr><td>' . h($part['nama_barang']) . '</td><td>' . h($part['stok']) . '</td><td>' . h(rupiah($part['harga_jual'])) . '</td>';
        if ($selected) {
            echo '<td><input type="number" name="qty[' . $key . ']" min="0" max="100000" step="1"'
                . ' aria-label="' . h('Jumlah beli ' . $part['nama_barang']) . '"></td>'
                . '<td><input readonly name="harga[' . $key . ']" value="' . h($part['harga_beli'])
                . '" aria-label="' . h('Harga pemasok ' . $part['nama_barang']) . '"></td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
