<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$current = basename($_SERVER['PHP_SELF']);

// [file, teks, ikon]
$menu = [
  'MENU UTAMA' => [
    ['dashboard.php',  'Dashboard',   '▦'],
    ['products.php',   'Data Barang', '▣'],
    ['categories.php', 'Kategori',    '▤'],
    ['customers.php',  'Pelanggan',   '♙'],
  ],
  'TRANSAKSI' => [
    ['purchases.php', 'Pembelian', '＋'],
    ['sales.php',     'Penjualan', '↗'],
    ['reports.php',   'Laporan',   '▥'],
  ],
];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | TokoKu</title>
<link rel="stylesheet" href="assets/css/style.css?v=4">
<script>
// pulihkan status sidebar (tersembunyi/tampil) sebelum halaman digambar, supaya tidak berkedip
try { if (localStorage.getItem('sidebar-hidden') === '1') document.documentElement.classList.add('sidebar-hidden'); } catch (e) {}
</script>
</head>
<body>
<div class="layout">

<aside class="sidebar" id="sidebar">
  <button class="sidebar-close" type="button" data-menu-close aria-label="Tutup menu">✕</button>
  <div class="brand">
    <span class="brand-icon">T</span>
    <div><b>WildanToko</b><small>Jual Beli Online</small></div>
  </div>

  <nav aria-label="Menu utama">
    <?php foreach ($menu as $label => $items): ?>
      <span class="nav-label"><?= e($label) ?></span>
      <?php foreach ($items as [$file, $text, $icon]): ?>
        <a class="<?= $current === $file ? 'active' : '' ?>" href="<?= e($file) ?>" title="<?= e($text) ?>"
           <?= $current === $file ? 'aria-current="page"' : '' ?>>
          <i class="ni" aria-hidden="true"><?= $icon ?></i> <span><?= e($text) ?></span>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-foot">TokoGua<br><small>Panel manajemen toko</small></div>
</aside>

<div class="sidebar-overlay" data-menu-close></div>

<main class="main">
<header class="topbar">
  <div class="topbar-left">
    <button class="menu-toggle" type="button" data-menu-toggle
            aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">☰</button>
    <div><span class="muted">Halaman</span><strong><?= e($pageTitle) ?></strong></div>
  </div>
  <div class="admin">●<span>Sing Ndue</span></div>
</header>
<section class="content">
<?php if ($m = flash('success')): ?><div class="alert success"><?= e($m) ?></div><?php endif; ?>
<?php if ($m = flash('error')): ?><div class="alert error"><?= e($m) ?></div><?php endif; ?>

<script>
// Sidebar: tombol ☰ membuka/menutup menu
//  - HP (<= 768px): laci geser dari kiri (class body.sidebar-open)
//  - Desktop/tablet: sembunyikan/tampilkan penuh (class html.sidebar-hidden, diingat di localStorage)
(function () {
  var body = document.body;
  var root = document.documentElement;
  var mq = window.matchMedia('(max-width: 768px)');
  var KEY = 'sidebar-hidden';

  function sync() {
    var btn = document.querySelector('.menu-toggle');
    if (!btn) return;
    var open = mq.matches ? body.classList.contains('sidebar-open')
                          : !root.classList.contains('sidebar-hidden');
    btn.setAttribute('aria-expanded', String(open));
    btn.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
  }

  function setDrawer(open) {
    body.classList.toggle('sidebar-open', open);
    sync();
  }

  function setHidden(hidden) {
    root.classList.toggle('sidebar-hidden', hidden);
    try { localStorage.setItem(KEY, hidden ? '1' : '0'); } catch (e) {}
    sync();
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-menu-toggle]')) {
      if (mq.matches) setDrawer(!body.classList.contains('sidebar-open'));
      else setHidden(!root.classList.contains('sidebar-hidden'));
    } else if (e.target.closest('[data-menu-close]')) {
      setDrawer(false);
    } else if (mq.matches && e.target.closest('.sidebar nav a')) {
      setDrawer(false);
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setDrawer(false);
  });

  mq.addEventListener('change', function () { setDrawer(false); });
  sync();
})();
</script>