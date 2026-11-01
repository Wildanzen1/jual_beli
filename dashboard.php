<?php
// Dashboard: hanya ringkasan. Form transaksi ada di sales.php / purchases.php.
require 'config/database.php';
require 'includes/functions.php';

$lowStockLimit = 5; // barang dengan stok <= angka ini dianggap menipis

// ---- Statistik ----
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalStock    = (int)$pdo->query("SELECT COALESCE(SUM(stock), 0) FROM products")->fetchColumn();

$salesMonth = (float)$pdo->query(
    "SELECT COALESCE(SUM(total), 0) FROM sales WHERE sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
)->fetchColumn();

$purchasesMonth = (float)$pdo->query(
    "SELECT COALESCE(SUM(total), 0) FROM purchases WHERE purchase_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
)->fetchColumn();

// ---- Stok menipis ----
$stmt = $pdo->prepare("SELECT code, name, stock FROM products WHERE stock <= ? ORDER BY stock ASC, name LIMIT 8");
$stmt->execute([$lowStockLimit]);
$lowStock = $stmt->fetchAll();

// ---- Penjualan terbaru ----
$recentSales = $pdo->query(
    "SELECT s.invoice, s.sale_date, s.total, COALESCE(c.name, 'Pelanggan umum') AS customer
     FROM sales s LEFT JOIN customers c ON c.id = s.customer_id
     ORDER BY s.id DESC LIMIT 6"
)->fetchAll();

$pageTitle = 'Dashboard';
require 'includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p>Ringkasan toko Anda hari ini, <?= date('d M Y') ?>.</p>
    </div>
    <div class="actions">
        <a class="btn" href="sales.php">+ Penjualan</a>
        <a class="btn secondary" href="purchases.php">+ Pembelian</a>
    </div>
</div>

<div class="cards">
    <div class="stat">
        <div class="label">Jenis barang</div>
        <div class="value"><?= number_format($totalProducts, 0, ',', '.') ?></div>
        <div class="hint">Terdaftar di sistem</div>
    </div>
    <div class="stat">
        <div class="label">Total stok</div>
        <div class="value"><?= number_format($totalStock, 0, ',', '.') ?></div>
        <div class="hint">Seluruh unit barang</div>
    </div>
    <div class="stat">
        <div class="label">Penjualan bulan ini</div>
        <div class="value"><?= rupiah($salesMonth) ?></div>
        <div class="hint">Sejak tanggal 1</div>
    </div>
    <div class="stat">
        <div class="label">Pembelian bulan ini</div>
        <div class="value"><?= rupiah($purchasesMonth) ?></div>
        <div class="hint">Sejak tanggal 1</div>
    </div>
</div>

<div class="grid-2">
    <div class="panel">
        <div class="toolbar">
            <h3>Penjualan terbaru</h3>
            <a class="btn secondary small" href="sales.php">Lihat semua</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentSales as $r): ?>
                        <tr>
                            <td><?= e($r['invoice']) ?></td>
                            <td><?= e($r['sale_date']) ?></td>
                            <td><?= e($r['customer']) ?></td>
                            <td class="text-right"><?= rupiah($r['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentSales): ?>
                        <tr><td colspan="4" class="empty">Belum ada penjualan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="toolbar">
            <h3>Stok menipis</h3>
            <a class="btn secondary small" href="products.php">Data barang</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th class="text-right">Stok</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStock as $p): ?>
                        <tr>
                            <td>
                                <?= e($p['name']) ?><br>
                                <small class="muted-text"><?= e($p['code']) ?></small>
                            </td>
                            <td class="text-right">
                                <span class="badge warn"><?= (int)$p['stock'] ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$lowStock): ?>
                        <tr><td colspan="2" class="empty">Semua stok aman.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>