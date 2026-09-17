<?php
/**
 * Data Layer: products.php
 * Mengelola dataset produk menggunakan JSON File Storage di folder Database/
 */

define('DATA_FILE', __DIR__ . '/Database/products.json');

/**
 * Data awal (Default Seed Data)
 */
function getInitialProducts(): array {
    return [
        [
            'id'        => 'PRD-001',
            'nama'      => 'Laptop Asus ROG Zephyrus',
            'kategori'  => 'Komputer & Laptop',
            'harga'     => 24500000,
            'stok'      => 5,
            'deskripsi' => 'Laptop gaming ultra-slim dengan AMD Ryzen 9 dan RTX 4070.'
        ],
        [
            'id'        => 'PRD-002',
            'nama'      => 'Keyboard Mekanikal Wireless',
            'kategori'  => 'Aksesoris Komputer',
            'harga'     => 850000,
            'stok'      => 2,
            'deskripsi' => 'Keyboard 75% layout dengan switch linear dan koneksi Bluetooth.'
        ],
        [
            'id'        => 'PRD-003',
            'nama'      => 'Monitor Ultrawide 34 Inch',
            'kategori'  => 'Komputer & Laptop',
            'harga'     => 6200000,
            'stok'      => 4,
            'deskripsi' => 'Panel IPS 144Hz WQHD ideal untuk multitasking dan editing.'
        ],
        [
            'id'        => 'PRD-004',
            'nama'      => 'Mouse Ergonomis Vertikal',
            'kategori'  => 'Aksesoris Komputer',
            'harga'     => 450000,
            'stok'      => 1,
            'deskripsi' => 'Desain ramah pergelangan tangan untuk mencegah RSI cedera.'
        ],
        [
            'id'        => 'PRD-005',
            'nama'      => 'Headset Noise Cancelling Pro',
            'kategori'  => 'Audio',
            'harga'     => 1750000,
            'stok'      => 0,
            'deskripsi' => 'Peredam bising aktif premium dengan mikrofon studio clarity.'
        ],
        [
            'id'        => 'PRD-006',
            'nama'      => 'RAM DDR5 32GB Kit (2x16GB)',
            'kategori'  => 'Komponen',
            'harga'     => 1950000,
            'stok'      => 8,
            'deskripsi' => 'Kecepatan 6000MHz CL30 dengan heatsink aluminium solid.'
        ],
        [
            'id'        => 'PRD-007',
            'nama'      => 'SSD NVMe M.2 2TB PCIe 4.0',
            'kategori'  => 'Penyimpanan Data',
            'harga'     => 2100000,
            'stok'      => 2,
            'deskripsi' => 'Kecepatan baca hingga 7000 MB/s untuk performa tanpa kompromi.'
        ]
    ];
}

/**
 * Membaca data produk dari file JSON
 */
function loadKatalogProduk(): array {
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    if (!file_exists(DATA_FILE)) {
        $initial = getInitialProducts();
        saveKatalogProduk($initial);
        return $initial;
    }
    $json = file_get_contents(DATA_FILE);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/**
 * Menyimpan data produk ke file JSON
 */
function saveKatalogProduk(array $katalog): bool {
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $json = json_encode(array_values($katalog), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(DATA_FILE, $json) !== false;
}

$katalogProduk = loadKatalogProduk();
?>
