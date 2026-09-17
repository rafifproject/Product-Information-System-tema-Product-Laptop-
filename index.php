<?php
// Memuat dependensi arsitektur modular
require_once 'config.php';
require_once 'products.php';
require_once 'functions.php';

$message = '';
$messageType = '';

// Handling CRUD Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        if (tambahProduk($katalogProduk, $_POST)) {
            header('Location: index.php?msg=added');
            exit;
        } else {
            $message = 'Gagal menambahkan produk! ID SKU mungkin sudah ada.';
            $messageType = 'danger';
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'] ?? '';
        if (editProduk($katalogProduk, $id, $_POST)) {
            header('Location: index.php?msg=updated');
            exit;
        } else {
            $message = 'Gagal memperbarui data produk!';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if (hapusProduk($katalogProduk, $id)) {
            header('Location: index.php?msg=deleted');
            exit;
        } else {
            $message = 'Gagal menghapus produk!';
            $messageType = 'danger';
        }
    } elseif ($action === 'reset') {
        if (resetKatalogProduk($katalogProduk)) {
            header('Location: index.php?msg=reset');
            exit;
        }
    }
}

// Flash messages check
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $message = 'Produk baru berhasil ditambahkan!';
        $messageType = 'success';
    } elseif ($_GET['msg'] === 'updated') {
        $message = 'Data produk berhasil diperbarui!';
        $messageType = 'success';
    } elseif ($_GET['msg'] === 'deleted') {
        $message = 'Produk berhasil dihapus dari sistem!';
        $messageType = 'success';
    } elseif ($_GET['msg'] === 'reset') {
        $message = 'Dataset produk telah berhasil di-reset ke data bawaan awal!';
        $messageType = 'success';
    }
}

// Search Filter
$searchQuery = trim($_GET['search'] ?? '');
$filteredKatalog = $katalogProduk;
if ($searchQuery !== '') {
    $filteredKatalog = array_filter($katalogProduk, function($item) use ($searchQuery) {
        return (stripos($item['nama'], $searchQuery) !== false) ||
               (stripos($item['id'], $searchQuery) !== false) ||
               (stripos($item['kategori'], $searchQuery) !== false);
    });
}

$totalNilaiAset = hitungTotalNilaiStok($katalogProduk);
$totalProdukKritis = hitungTotalStokKritis($katalogProduk, STOK_KRITIS_THRESHOLD);
$totalRagamProduk = count($katalogProduk);
$nextSKU = generateSKU($katalogProduk);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME); ?> - System Management CRUD</title>
    <link rel="stylesheet" href="Style/style.css">
</head>
<body>
    <div class="container">
        <header class="app-header">
            <div class="header-content">
                <div>
                    <h1><?= htmlspecialchars(APP_NAME); ?></h1>
                    <p>Sistem Pemantauan Aset Inventori Gudang & Management Data Produk (CRUD Native)</p>
                </div>
                <div class="header-actions">
                    <button type="button" class="btn btn-primary" onclick="openModal('createModal')">
                        + Tambah Produk Baru
                    </button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin me-reset katalog ke data awal?');">
                        <input type="hidden" name="action" value="reset">
                        <button type="submit" class="btn btn-outline-danger">Reset Data Awal</button>
                    </form>
                </div>
            </div>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType; ?>">
                <span><?= htmlspecialchars($message); ?></span>
                <button class="alert-close" onclick="this.parentElement.style.display='none';">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="metrics-grid">
            <div class="card">
                <span class="card-label">Total Ragam Produk</span>
                <strong class="card-value"><?= $totalRagamProduk; ?> Item</strong>
            </div>
            <div class="card">
                <span class="card-label">Total Valuasi Aset Stok</span>
                <strong class="card-value text-primary"><?= formatRupiah($totalNilaiAset); ?></strong>
            </div>
            <div class="card">
                <span class="card-label">Produk Perlu Restok (&lt; <?= STOK_KRITIS_THRESHOLD; ?>)</span>
                <strong class="card-value text-danger"><?= $totalProdukKritis; ?> Item</strong>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="toolbar">
            <form method="GET" class="search-form">
                <input type="text" name="search" class="form-control" placeholder="Cari nama, SKU, atau kategori produk..." value="<?= htmlspecialchars($searchQuery); ?>">
                <button type="submit" class="btn btn-secondary">Cari</button>
                <?php if ($searchQuery !== ''): ?>
                    <a href="index.php" class="btn btn-outline">Reset Filter</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabel Data Produk -->
        <div class="table-responsive">
            <table class="product-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID SKU</th>
                        <th>Nama Komoditas Produk</th>
                        <th>Kategori</th>
                        <th>Harga Satuan</th>
                        <th>Stok</th>
                        <th>Subtotal Valuasi</th>
                        <th>Status Stok</th>
                        <th>Deskripsi</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filteredKatalog)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 24px;" class="text-muted">
                                Tidak ada data produk yang ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($filteredKatalog as $item): 
                            $status = evaluasiStatusStok($item['stok'], STOK_KRITIS_THRESHOLD);
                            $subtotal = $item['harga'] * $item['stok'];
                            $jsonItem = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr class="<?= $status['row_class']; ?>">
                                <td><?= $no++; ?></td>
                                <td><code><?= htmlspecialchars($item['id']); ?></code></td>
                                <td><strong><?= htmlspecialchars($item['nama']); ?></strong></td>
                                <td><?= htmlspecialchars($item['kategori']); ?></td>
                                <td><?= formatRupiah($item['harga']); ?></td>
                                <td><strong><?= $item['stok']; ?></strong></td>
                                <td><?= formatRupiah($subtotal); ?></td>
                                <td><span class="badge <?= $status['badge']; ?>"><?= $status['status']; ?></span></td>
                                <td><span class="text-muted"><?= htmlspecialchars($item['deskripsi']); ?></span></td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <button type="button" class="btn btn-sm btn-warning" onclick='openEditModal(<?= $jsonItem; ?>)' title="Edit Produk">
                                        ✏️ Edit
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk <?= htmlspecialchars($item['nama']); ?>?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus Produk">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="app-footer">
            <p>Pemrograman Web Pertemuan 2 - Product Information System (CRUD Native PHP)</p>
        </footer>
    </div>

    <!-- Modal Tambah Produk -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Produk Baru</h3>
                <span class="close-btn" onclick="closeModal('createModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">ID SKU (Otomatis / Kustom)</label>
                        <input type="text" name="id" class="form-control" value="<?= htmlspecialchars($nextSKU); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Komoditas Produk</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Laptop Asus ROG" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kategori</label>
                            <input type="text" name="kategori" class="form-control" placeholder="Contoh: Komputer & Laptop" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Harga Satuan (Rp)</label>
                            <input type="number" name="harga" class="form-control" placeholder="0" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah Stok</label>
                        <input type="number" name="stok" class="form-control" placeholder="0" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deskripsi Produk</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Penjelasan spesifikasi teknis singkat..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('createModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Produk -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Data Produk</h3>
                <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">ID SKU</label>
                        <input type="text" id="edit_id_display" class="form-control" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Komoditas Produk</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kategori</label>
                            <input type="text" name="kategori" id="edit_kategori" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Harga Satuan (Rp)</label>
                            <input type="number" name="harga" id="edit_harga" class="form-control" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah Stok</label>
                        <input type="number" name="stok" id="edit_stok" class="form-control" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deskripsi Produk</label>
                        <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn btn-warning">Update Produk</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function openEditModal(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_id_display').value = item.id;
            document.getElementById('edit_nama').value = item.nama;
            document.getElementById('edit_kategori').value = item.kategori;
            document.getElementById('edit_harga').value = item.harga;
            document.getElementById('edit_stok').value = item.stok;
            document.getElementById('edit_deskripsi').value = item.deskripsi;
            openModal('editModal');
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        };
    </script>
</body>
</html>
