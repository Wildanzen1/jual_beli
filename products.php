
<?php

require 'config/database.php';
require 'includes/functions.php';

verify_csrf();

// ==========================================
// PROSES HAPUS BARANG
// ==========================================
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare(
            "DELETE FROM products WHERE id = ?"
        );

        $stmt->execute([(int) $_GET['delete']]);

        flash('success', 'Barang berhasil dihapus.');
    } catch (Throwable $e) {
        flash(
            'error',
            'Barang tidak bisa dihapus karena sudah tercatat dalam transaksi.'
        );
    }

    redirect('products.php');
}

// ==========================================
// PROSES TAMBAH DAN EDIT BARANG
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = (int) ($_POST['id'] ?? 0);

        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $categoryId = $categoryId ?: null;

        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $unit = trim($_POST['unit'] ?? 'pcs');

        $purchasePrice = post_money('purchase_price');
        $sellingPrice = post_money('selling_price');
        $stock = post_int('stock');

        // Validasi data
        if ($code === '' || $name === '') {
            throw new RuntimeException(
                'Kode dan nama barang wajib diisi.'
            );
        }

        if ($purchasePrice < 0 || $sellingPrice < 0 || $stock < 0) {
            throw new RuntimeException(
                'Harga dan stok tidak boleh bernilai negatif.'
            );
        }

        // Jika ID tersedia, lakukan UPDATE
        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE products
                SET
                    category_id = ?,
                    code = ?,
                    name = ?,
                    purchase_price = ?,
                    selling_price = ?,
                    stock = ?,
                    unit = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $categoryId,
                $code,
                $name,
                $purchasePrice,
                $sellingPrice,
                $stock,
                $unit,
                $id
            ]);

        } else {
            // Jika ID tidak tersedia, lakukan INSERT
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    category_id,
                    code,
                    name,
                    purchase_price,
                    selling_price,
                    stock,
                    unit
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $categoryId,
                $code,
                $name,
                $purchasePrice,
                $sellingPrice,
                $stock,
                $unit
            ]);
        }

        flash('success', 'Data barang berhasil disimpan.');

        redirect('products.php');

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// ==========================================
// AMBIL DATA BARANG UNTUK DIEDIT
// ==========================================
$edit = null;

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM products WHERE id = ?"
    );

    $stmt->execute([(int) $_GET['edit']]);

    $edit = $stmt->fetch();
}

// ==========================================
// AMBIL DATA KATEGORI
// ==========================================
$cats = $pdo->query("
    SELECT *
    FROM categories
    ORDER BY name
")->fetchAll();

// ==========================================
// AMBIL SELURUH DATA BARANG
// ==========================================
$rows = $pdo->query("
    SELECT
        p.*,
        c.name AS category
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY p.id DESC
")->fetchAll();

$pageTitle = 'Data Barang';

require 'includes/header.php';

?>

<!-- ======================================
     JUDUL HALAMAN
======================================= -->
<div class="page-head">
    <div>
        <h1>Data Barang</h1>
        <p>Kelola katalog, harga, dan persediaan barang.</p>
    </div>
</div>

<!-- ======================================
     PESAN ERROR
======================================= -->
<?php if (!empty($error)): ?>
    <div class="alert error">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<!-- ======================================
     FORM TAMBAH DAN EDIT BARANG
======================================= -->
<div class="panel">

    <h3>
        <?= $edit ? 'Edit' : 'Tambah' ?> Barang
    </h3>

    <form method="post">

        <input
            type="hidden"
            name="_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="id"
            value="<?= e($edit['id'] ?? '') ?>"
        >

        <div class="form-grid">

            <div class="field">
                <label>Kode Barang *</label>
                <input
                    type="text"
                    name="code"
                    required
                    value="<?= e($edit['code'] ?? '') ?>"
                >
            </div>

            <div class="field">
                <label>Nama Barang *</label>
                <input
                    type="text"
                    name="name"
                    required
                    value="<?= e($edit['name'] ?? '') ?>"
                >
            </div>

            <div class="field">
                <label>Kategori</label>

                <select name="category_id">
                    <option value="">Tanpa kategori</option>

                    <?php foreach ($cats as $category): ?>
                        <option
                            value="<?= $category['id'] ?>"
                            <?= ($edit['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>
                        >
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="field">
                <label>Satuan</label>
                <input
                    type="text"
                    name="unit"
                    value="<?= e($edit['unit'] ?? 'pcs') ?>"
                >
            </div>

            <div class="field">
                <label>Harga Beli</label>
                <input
                    type="number"
                    name="purchase_price"
                    min="0"
                    step="0.01"
                    required
                    value="<?= e($edit['purchase_price'] ?? 0) ?>"
                >
            </div>

            <div class="field">
                <label>Harga Jual</label>
                <input
                    type="number"
                    name="selling_price"
                    min="0"
                    step="0.01"
                    required
                    value="<?= e($edit['selling_price'] ?? 0) ?>"
                >
            </div>

            <div class="field">
                <label>Stok Awal / Stok Saat Ini</label>
                <input
                    type="number"
                    name="stock"
                    min="0"
                    required
                    value="<?= e($edit['stock'] ?? 0) ?>"
                >
            </div>

        </div>

        <div class="form-actions">

            <button type="submit" class="btn">
                Simpan Barang
            </button>

            <?php if ($edit): ?>
                <a href="products.php" class="btn secondary">
                    Batal
                </a>
            <?php endif; ?>

        </div>

    </form>

</div>

<!-- ======================================
     TABEL DAFTAR BARANG
======================================= -->
<div class="panel">

    <div class="toolbar">
        <h3>Daftar Barang</h3>

        <input
            type="text"
            class="search"
            placeholder="Cari barang..."
            data-table-search
        >
    </div>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Harga Beli</th>
                    <th>Harga Jual</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($rows as $row): ?>

                    <tr>

                        <td>
                            <?= e($row['code']) ?>
                        </td>

                        <td>
                            <?= e($row['name']) ?>
                        </td>

                        <td>
                            <?= e($row['category'] ?? '-') ?>
                        </td>

                        <td>
                            <?= rupiah($row['purchase_price']) ?>
                        </td>

                        <td>
                            <?= rupiah($row['selling_price']) ?>
                        </td>

                        <td>
                            <span class="badge <?= $row['stock'] < 5 ? 'warn' : '' ?>">
                                <?= (int) $row['stock'] ?>
                                <?= e($row['unit']) ?>
                            </span>
                        </td>

                        <td>
                            <div class="actions">

                                <a
                                    href="?edit=<?= $row['id'] ?>"
                                    class="btn secondary small"
                                >
                                    Edit
                                </a>

                                <a
                                    href="?delete=<?= $row['id'] ?>"
                                    class="btn danger small"
                                    data-confirm="Hapus barang ini?"
                                >
                                    Hapus
                                </a>

                            </div>
                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" class="empty">
                            Belum ada barang.
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php require 'includes/footer.php'; ?>
