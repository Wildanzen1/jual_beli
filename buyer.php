<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $productId = post_int('product_id', 1);
        $qty = post_int('qty', 1);
        $buyerName = trim($_POST['buyer_name'] ?? '');
        $buyerPhone = trim($_POST['buyer_phone'] ?? '');
        if ($buyerName === '') throw new RuntimeException('Nama pembeli wajib diisi.');

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) throw new RuntimeException('Produk tidak ditemukan.');
        if ((int)$product['stock'] < $qty) {
            throw new RuntimeException('Stok tidak mencukupi. Stok tersedia: ' . (int)$product['stock']);
        }

        $stmt = $pdo->prepare('INSERT INTO customers(name, phone) VALUES(?, ?)');
        $stmt->execute([$buyerName, $buyerPhone !== '' ? $buyerPhone : null]);
        $customerId = (int)$pdo->lastInsertId();

        $invoice = invoice('SHOP-');
        $total = (float)$product['selling_price'] * $qty;
        $stmt = $pdo->prepare("INSERT INTO sales(invoice, customer_id, sale_date, total, payment_method, notes) VALUES(?, ?, CURDATE(), ?, 'Tunai', 'Transaksi dari halaman pembeli')");
        $stmt->execute([$invoice, $customerId, $total]);
        $saleId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO sale_items(sale_id, product_id, qty, price, subtotal) VALUES(?, ?, ?, ?, ?)');
        $stmt->execute([$saleId, $productId, $qty, $product['selling_price'], $total]);
        $stmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
        $stmt->execute([$qty, $productId]);
        $pdo->commit();
        $success = 'Transaksi berhasil! Invoice: ' . $invoice . '. Total: ' . rupiah($total);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    }
}

$products = $pdo->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.name")->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>TokoKu - Katalog Pembeli</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
.shop-wrap{max-width:1120px;margin:30px auto;padding:0 20px}.shop-nav{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:28px}.shop-nav a{color:#fff;text-decoration:none;background:#243b53;padding:10px 16px;border-radius:9px}.shop-title h1{margin:0 0 6px}.shop-title p{color:#718096;margin:0}.shop-alert{padding:13px 16px;border-radius:9px;margin:15px 0}.shop-alert.ok{background:#e6fffa;color:#166534}.shop-alert.bad{background:#fff1f2;color:#9f1239}.shop-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(235px,1fr));gap:18px;margin-top:22px}.shop-card{background:#fff;border:1px solid #e5eaf0;border-radius:14px;padding:20px;box-shadow:0 5px 18px #1f29370a}.shop-card h3{margin:8px 0}.shop-code{font-size:12px;color:#718096}.shop-price{font-size:20px;font-weight:800;color:#2563eb;margin:12px 0}.shop-stock{font-size:13px;color:#64748b;margin-bottom:14px}.shop-card label{display:block;font-size:13px;font-weight:600;margin:9px 0 5px}.shop-card input{width:100%;padding:10px;border:1px solid #d8dee8;border-radius:8px;box-sizing:border-box}.shop-card button{width:100%;margin-top:13px;padding:11px;border:0;border-radius:8px;background:#2563eb;color:#fff;font-weight:700;cursor:pointer}.shop-card button:disabled{background:#94a3b8;cursor:not-allowed}.shop-footer{text-align:center;color:#718096;margin:30px 0}.shop-links{display:flex;gap:10px;flex-wrap:wrap}.shop-links a{color:#2563eb;text-decoration:none}@media(max-width:600px){.shop-nav{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body>
<div class="shop-wrap">
  <div class="shop-nav"><div class="brand"><span class="brand-icon">T</span><div><b>TokoKu</b><small>Halaman Pembeli</small></div></div><div class="shop-links"><a href="index.php">Panel Admin</a></div></div>
  <div class="shop-title"><h1>Katalog Produk</h1><p>Pilih produk, isi data pembeli, lalu tekan Beli Sekarang.</p></div>
  <?php if ($success): ?><div class="shop-alert ok"><?= e($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="shop-alert bad"><?= e($error) ?></div><?php endif; ?>
  <div class="shop-grid">
  <?php foreach ($products as $p): ?>
    <article class="shop-card">
      <div class="shop-code"><?= e($p['code']) ?> · <?= e($p['category_name'] ?? 'Tanpa kategori') ?></div>
      <h3><?= e($p['name']) ?></h3>
      <div class="shop-price"><?= rupiah($p['selling_price']) ?></div>
      <div class="shop-stock">Stok tersedia: <b><?= (int)$p['stock'] ?> <?= e($p['unit']) ?></b></div>
      <form method="post">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
        <label>Nama pembeli</label><input name="buyer_name" required maxlength="150" placeholder="Nama lengkap">
        <label>Nomor telepon (opsional)</label><input name="buyer_phone" maxlength="30" placeholder="08xxxxxxxxxx">
        <label>Jumlah</label><input type="number" name="qty" min="1" max="<?= max(1,(int)$p['stock']) ?>" value="1" required <?= (int)$p['stock'] < 1 ? 'disabled' : '' ?>>
        <button type="submit" <?= (int)$p['stock'] < 1 ? 'disabled' : '' ?>><?= (int)$p['stock'] < 1 ? 'Stok Habis' : 'Beli Sekarang' ?></button>
      </form>
    </article>
  <?php endforeach; ?>
  <?php if (!$products): ?><div class="shop-card">Belum ada produk. Admin perlu menambahkan produk terlebih dahulu.</div><?php endif; ?>
  </div>
  <div class="shop-footer">© <?= date('Y') ?> TokoKu · Transaksi pembelian sederhana</div>
</div>
</body></html>
