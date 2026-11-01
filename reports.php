<?php

require 'config/database.php';
require 'includes/functions.php';

// Mengambil dan memvalidasi tanggal periode
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $from = date('Y-m-01');
    $to = date('Y-m-d');
}

// Mengambil data rekap penjualan
$s = $pdo->prepare("SELECT COUNT(*) AS transactions, COALESCE(SUM(total), 0) AS total FROM sales WHERE sale_date BETWEEN ? AND ?");
$s->execute([$from, $to]);
$sales = $s->fetch();

// Mengambil data rekap pembelian
$p = $pdo->prepare("SELECT COUNT(*) AS transactions, COALESCE(SUM(total), 0) AS total FROM purchases WHERE purchase_date BETWEEN ? AND ?");
$p->execute([$from, $to]);
$purchases = $p->fetch();

// Mengambil rincian daftar penjualan
$rows = $pdo->prepare("
    SELECT 
        s.invoice,
        s.sale_date,
        COALESCE(c.name, 'Pelanggan umum') AS customer,
        s.payment_method,
        s.total 
    FROM sales s 
    LEFT JOIN customers c ON c.id = s.customer_id 
    WHERE s.sale_date BETWEEN ? AND ? 
    ORDER BY s.sale_date DESC, s.id DESC
");
$rows->execute([$from, $to]);
$list = $rows->fetchAll();

$pageTitle = 'Laporan';
require 'includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Laporan</h1>
        <p>Rekap pembelian dan penjualan berdasarkan periode.</p>
    </div>
    <button class="btn secondary" onclick="window.print()">Cetak Laporan</button>
</div>

<div class="panel">
    <form method="get" class="filters">
        <label>Dari</label>
        <input type="date" name="from" value="<?= e($from) ?>">
        
        <label>Sampai</label>
        <input type="date" name="to" value="<?= e($to) ?>">
        
        <button class="btn">Tampilkan</button>
    </form>
</div>

<div class="cards">
    <div class="stat">
        <div class="label">Jumlah transaksi penjualan</div>
        <div class="value"><?= (int)$sales['transactions'] ?></div>
    </div>
    <div class="stat">
        <div class="label">Total penjualan</div>
        <div class="value"><?= rupiah($sales['total']) ?></div>
    </div>
    <div class="stat">
        <div class="label">Jumlah transaksi pembelian</div>
        <div class="value"><?= (int)$purchases['transactions'] ?></div>
    </div>
    <div class="stat">
        <div class="label">Total pembelian</div>
        <div class="value"><?= rupiah($purchases['total']) ?></div>
    </div>
</div>

<div class="panel">
    <h3>Rincian Penjualan</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Pembayaran</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $r): ?>
                    <tr>
                        <td><?= e($r['invoice']) ?></td>
                        <td><?= e($r['sale_date']) ?></td>
                        <td><?= e($r['customer']) ?></td>
                        <td><?= e($r['payment_method']) ?></td>
                        <td><?= rupiah($r['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (!$list): ?>
                    <tr>
                        <td colspan="5" class="empty">Tidak ada penjualan pada periode ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="summary">
        <span>Jumlah penjualan periode ini</span>
        <strong><?= rupiah($sales['total']) ?></strong>
    </div>
</div>


<?php require 'includes/footer.php'; ?>
