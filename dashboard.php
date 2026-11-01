<?php
// Mesin transaksi pembelian/penjualan bersama.
require 'config/database.php';
require 'includes/functions.php';

$type = $type ?? 'sale';
$isPurchase = $type === 'purchase';
$table = $isPurchase ? 'purchases' : 'sales';
$itemsTable = $isPurchase ? 'purchase_items' : 'sale_items';
$title = $isPurchase ? 'Transaksi Pembelian' : 'Transaksi Penjualan';
$prefix = $isPurchase ? 'BELI-' : 'JUAL-';

verify_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $productIds = $_POST['product_id'] ?? [];
        $qtys = $_POST['qty'] ?? [];

        if (!is_array($productIds) || count($productIds) === 0) {
            throw new RuntimeException('Tambahkan minimal satu barang.');
        }

        $pdo->beginTransaction();
        $invoiceNo = invoice($prefix);
        $date = $_POST['date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '');

        if ($isPurchase) {
            $supplier = trim($_POST['supplier'] ?? '');
            $stmt = $pdo->prepare("INSERT INTO purchases (invoice, supplier, purchase_date, total, notes) VALUES (?, ?, ?, 0, ?)");
            $stmt->execute([$invoiceNo, $supplier, $date, $notes]);
            $transactionId = (int)$pdo->lastInsertId();
        } else {
            $customer = (int)($_POST['customer_id'] ?? 0) ?: null;
            $method = $_POST['payment_method'] ?? 'Tunai';
            $stmt = $pdo->prepare("INSERT INTO sales (invoice, customer_id, sale_date, total, payment_method, notes) VALUES (?, ?, ?, 0, ?, ?)");
            $stmt->execute([$invoiceNo, $customer, $date, $method, $notes]);
            $transactionId = (int)$pdo->lastInsertId();
        }

        $total = 0;
        foreach ($productIds as $i => $pid) {
            $pid = (int)$pid;
            $qty = (int)($qtys[$i] ?? 0);

            if ($pid < 1 || $qty < 1) {
                throw new RuntimeException('Barang dan jumlah harus valid.');
            }

            $q = $pdo->prepare("SELECT * FROM products WHERE id = ? FOR UPDATE");
            $q->execute([$pid]);
            $product = $q->fetch();

            if (!$product) {
                throw new RuntimeException('Barang tidak ditemukan.');
            }

            if (!$isPurchase && $product['stock'] < $qty) {
                throw new RuntimeException('Stok ' . $product['name'] . ' tidak mencukupi. Stok tersedia: ' . $product['stock']);
            }

            $price = $isPurchase ? (float)$product['purchase_price'] : (float)$product['selling_price'];
            $subtotal = $price * $qty;
            $total += $subtotal;

            $fkColumn = $isPurchase ? 'purchase_id' : 'sale_id';
            $ins = $pdo->prepare("INSERT INTO `$itemsTable` ($fkColumn, product_id, qty, price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$transactionId, $pid, $qty, $price, $subtotal]);

            $delta = $isPurchase ? $qty : -$qty;
            $upd = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $upd->execute([$delta, $pid]);
        }

        $upd = $pdo->prepare("UPDATE `$table` SET total = ? WHERE id = ?");
        $upd->execute([$total, $transactionId]);
        
        $pdo->commit();
        
        flash('success', "$title berhasil disimpan. Nomor invoice: $invoiceNo");
        redirect($isPurchase ? 'purchases.php' : 'sales.php');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

$products = $pdo->query("SELECT id, code, name, stock, purchase_price, selling_price FROM products ORDER BY name")->fetchAll();
$customers = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();

if ($isPurchase) {
    $rows = $pdo->query("SELECT p.*, (SELECT COUNT(*) FROM purchase_items i WHERE i.purchase_id = p.id) AS item_count FROM purchases p ORDER BY p.id DESC LIMIT 50")->fetchAll();
} else {
    $rows = $pdo->query("SELECT s.*, COALESCE(c.name, 'Pelanggan umum') AS customer, (SELECT COUNT(*) FROM sale_items i WHERE i.sale_id = s.id) AS item_count FROM sales s LEFT JOIN customers c ON c.id = s.customer_id ORDER BY s.id DESC LIMIT 50")->fetchAll();
}

$pageTitle = $title;
require 'includes/header.php';
?>

<div class="page-head">
    <div>
        <h1><?= e($title) ?></h1>
        <p><?= $isPurchase ? 'Pembelian menambah stok barang.' : 'Penjualan mengurangi stok barang secara otomatis.' ?></p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<div class="panel">
    <h3>Input <?= e($title) ?></h3>
    <form method="post" id="transaction-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        
        <div class="form-grid">
            <?php if ($isPurchase): ?>
                <div class="field">
                    <label>Nama pemasok</label>
                    <input name="supplier" placeholder="Nama pemasok">
                </div>
            <?php else: ?>
                <div class="field">
                    <label>Pelanggan</label>
                    <select name="customer_id">
                        <option value="">Pelanggan umum</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="field">
                <label>Tanggal transaksi</label>
                <input type="date" name="date" required value="<?= date('Y-m-d') ?>">
            </div>

            <?php if (!$isPurchase): ?>
                <div class="field">
                    <label>Metode pembayaran</label>
                    <select name="payment_method">
                        <option>Tunai</option>
                        <option>Transfer</option>
                        <option>QRIS</option>
                        <option>Kartu debit</option>
                    </select>
                </div>
            <?php endif; ?>

            <div class="field full">
                <label>Catatan</label>
                <input name="notes" placeholder="Catatan transaksi (opsional)">
            </div>
        </div>

        <div class="toolbar" style="margin-top: 22px">
            <h3>Daftar barang</h3>
            <button type="button" class="btn secondary" id="add-item">+ Tambah barang</button>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Stok</th>
                        <th>Jumlah</th>
                        <th>Harga satuan</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="items-body"></tbody>
            </table>
        </div>

        <div class="summary">
            <b>Total transaksi</b>
            <strong id="grand-total">Rp 0</strong>
        </div>

        <div class="form-actions">
            <button class="btn" <?= !$products ? 'disabled' : '' ?>>Simpan Transaksi</button>
        </div>
        
        <?php if (!$products): ?>
            <p class="muted-text">Tambahkan barang terlebih dahulu sebelum membuat transaksi.</p>
        <?php endif; ?>
    </form>
</div>

<div class="panel">
    <h3>Riwayat <?= e($title) ?></h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Tanggal</th>
                    <th><?= $isPurchase ? 'Pemasok' : 'Pelanggan' ?></th>
                    <th>Item</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e($r['invoice']) ?></td>
                        <td><?= e($isPurchase ? $r['purchase_date'] : $r['sale_date']) ?></td>
                        <td><?= e($isPurchase ? ($r['supplier'] ?: '-') : $r['customer']) ?></td>
                        <td><?= (int)$r['item_count'] ?> jenis</td>
                        <td><?= rupiah($r['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="5" class="empty">Belum ada transaksi.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const products = <?= json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const buying = <?= $isPurchase ? 'true' : 'false' ?>;
const body = document.getElementById('items-body');
const money = n => 'Rp ' + Number(n).toLocaleString('id-ID');

function addRow() {
    const tr = document.createElement('tr');
    const options = products.map(p => `
        <option value="${p.id}" data-stock="${p.stock}" data-price="${buying ? p.purchase_price : p.selling_price}">
            ${p.code} - ${p.name}
        </option>
    `).join('');

    tr.innerHTML = `
        <td>
            <select name="product_id[]" class="product-select" required>
                <option value="">Pilih barang</option>
                ${options}
            </select>
        </td>
        <td class="stock-cell">-</td>
        <td>
            <input type="number" name="qty[]" class="qty" min="1" value="1" required style="width: 75px; padding: 8px">
        </td>
        <td class="price-cell">-</td>
        <td class="subtotal">Rp 0</td>
        <td>
            <button type="button" class="btn danger small remove">Hapus</button>
        </td>
    `;

    body.appendChild(tr);

    tr.querySelectorAll('select, input').forEach(el => el.addEventListener('change', calculate));
