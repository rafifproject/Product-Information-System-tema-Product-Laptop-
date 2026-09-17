<?php
/**
 * Processing Layer: functions.php
 * Berisi repositori fungsi kalkulasi logika bisnis murni & fungsi CRUD
 */

function hitungTotalNilaiStok(array $daftarProduk): float|int {
    $totalNilai = 0;
    foreach ($daftarProduk as $item) {
        $totalNilai += ($item['harga'] * $item['stok']);
    }
    return $totalNilai;
}

function hitungTotalStokKritis(array $daftarProduk, int $ambang = 3): int {
    $jumlahKritis = 0;
    foreach ($daftarProduk as $item) {
        if ($item['stok'] < $ambang) {
            $jumlahKritis++;
        }
    }
    return $jumlahKritis;
}

function evaluasiStatusStok(int $stok, int $ambang = 3): array {
    if ($stok === 0) {
        return [
            'status'    => 'Habis',
            'row_class' => 'row-empty',
            'badge'     => 'badge-danger'
        ];
    } elseif ($stok < $ambang) {
        return [
            'status'    => 'Kritis',
            'row_class' => 'row-critical',
            'badge'     => 'badge-warning'
        ];
    } else {
        return [
            'status'    => 'Aman',
            'row_class' => 'row-normal',
            'badge'     => 'badge-success'
        ];
    }
}

function formatRupiah(float|int $nominal): string {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

/**
 * Generate SKU Otomatis (Format: PRD-XXX)
 */
function generateSKU(array $daftarProduk): string {
    $maxNum = 0;
    foreach ($daftarProduk as $item) {
        if (preg_match('/PRD-(\d+)/i', $item['id'], $matches)) {
            $num = (int)$matches[1];
            if ($num > $maxNum) {
                $maxNum = $num;
            }
        }
    }
    return sprintf('PRD-%03d', $maxNum + 1);
}

/**
 * Cari produk berdasarkan ID SKU
 */
function ambilProdukById(array $daftarProduk, string $id): ?array {
    foreach ($daftarProduk as $item) {
        if ($item['id'] === $id) {
            return $item;
        }
    }
    return null;
}

/**
 * Operasi CRUD: Tambah Produk Baru
 */
function tambahProduk(array &$daftarProduk, array $input): bool {
    $id = !empty($input['id']) ? trim($input['id']) : generateSKU($daftarProduk);
    
    // Cek duplikasi SKU
    foreach ($daftarProduk as $item) {
        if ($item['id'] === $id) {
            return false;
        }
    }

    $baru = [
        'id'        => $id,
        'nama'      => trim($input['nama'] ?? ''),
        'kategori'  => trim($input['kategori'] ?? ''),
        'harga'     => (float)($input['harga'] ?? 0),
        'stok'      => (int)($input['stok'] ?? 0),
        'deskripsi' => trim($input['deskripsi'] ?? '')
    ];

    $daftarProduk[] = $baru;
    return saveKatalogProduk($daftarProduk);
}

/**
 * Operasi CRUD: Edit Produk
 */
function editProduk(array &$daftarProduk, string $id, array $input): bool {
    foreach ($daftarProduk as $index => $item) {
        if ($item['id'] === $id) {
            $daftarProduk[$index]['nama']      = trim($input['nama'] ?? $item['nama']);
            $daftarProduk[$index]['kategori']  = trim($input['kategori'] ?? $item['kategori']);
            $daftarProduk[$index]['harga']     = (float)($input['harga'] ?? $item['harga']);
            $daftarProduk[$index]['stok']      = (int)($input['stok'] ?? $item['stok']);
            $daftarProduk[$index]['deskripsi'] = trim($input['deskripsi'] ?? $item['deskripsi']);
            return saveKatalogProduk($daftarProduk);
        }
    }
    return false;
}

/**
 * Operasi CRUD: Hapus Produk
 */
function hapusProduk(array &$daftarProduk, string $id): bool {
    foreach ($daftarProduk as $index => $item) {
        if ($item['id'] === $id) {
            array_splice($daftarProduk, $index, 1);
            return saveKatalogProduk($daftarProduk);
        }
    }
    return false;
}

/**
 * Reset data ke default bawaan awal
 */
function resetKatalogProduk(array &$daftarProduk): bool {
    $daftarProduk = getInitialProducts();
    return saveKatalogProduk($daftarProduk);
}
?>
