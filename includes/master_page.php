<?php
// Dipakai oleh halaman CRUD master data. Variabel $table, $title, $fields, $columns, $orderBy disiapkan oleh halaman.
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/functions.php';
verify_csrf();
if (isset($_GET['delete'])) {
    try { $stmt=$pdo->prepare("DELETE FROM `$table` WHERE id=?"); $stmt->execute([(int)$_GET['delete']]); flash('success',"$title berhasil dihapus."); }
    catch(Throwable $ex){ flash('error',"Data tidak dapat dihapus karena sudah digunakan pada transaksi."); }
    redirect(basename($_SERVER['PHP_SELF']));
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $id=(int)($_POST['id']??0); $data=[];
        foreach($fields as $key=>$meta) {
            if (($meta['type']??'text')==='number') $data[$key]=(float)($_POST[$key]??0);
            else $data[$key]=trim($_POST[$key]??'');
            if (($meta['required']??false) && $data[$key]==='') throw new RuntimeException($meta['label'].' wajib diisi.');
        }
        if ($id) {
            $sets=implode(',',array_map(fn($k)=>"`$k`=?",array_keys($data)));
            $stmt=$pdo->prepare("UPDATE `$table` SET $sets WHERE id=?"); $stmt->execute([...array_values($data),$id]);
        } else {
            $keys=array_keys($data); $stmt=$pdo->prepare("INSERT INTO `$table` (`".implode('`,`',$keys)."`) VALUES (".implode(',',array_fill(0,count($keys),'?')).")"); $stmt->execute(array_values($data));
        }
        flash('success',"$title berhasil disimpan."); redirect(basename($_SERVER['PHP_SELF']));
    } catch(Throwable $ex) { $error=$ex->getMessage(); }
}
$edit=null;
if(isset($_GET['edit'])) { $stmt=$pdo->prepare("SELECT * FROM `$table` WHERE id=?");$stmt->execute([(int)$_GET['edit']]);$edit=$stmt->fetch(); }
$rows=$pdo->query("SELECT * FROM `$table` ORDER BY $orderBy DESC")->fetchAll();
$pageTitle=$title; require __DIR__.'/header.php';
?>
<div class="page-head"><div><h1><?= e($title) ?></h1><p>Kelola data <?= strtolower(e($title)) ?> toko Anda.</p></div></div>
<?php if(!empty($error)): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<div class="panel"><h3><?= $edit?'Edit':'Tambah' ?> <?= e($title) ?></h3>
<form method="post"><input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= e($edit['id']??'') ?>">
<div class="form-grid"><?php foreach($fields as $key=>$meta): ?><div class="field <?= ($meta['full']??false)?'full':'' ?>"><label><?= e($meta['label']) ?><?= ($meta['required']??false)?' *':'' ?></label>
<?php if(($meta['type']??'')==='textarea'): ?><textarea name="<?= e($key) ?>"><?= e($edit[$key]??'') ?></textarea><?php else: ?><input type="<?= e($meta['type']??'text') ?>" name="<?= e($key) ?>" value="<?= e($edit[$key]??'') ?>" <?= ($meta['required']??false)?'required':'' ?> <?= isset($meta['step'])?'step="'.e($meta['step']).'"':'' ?>><?php endif; ?></div><?php endforeach; ?></div>
<div class="form-actions"><button class="btn">Simpan Data</button><?php if($edit): ?><a class="btn secondary" href="<?= e(basename($_SERVER['PHP_SELF'])) ?>">Batal</a><?php endif; ?></div></form></div>
<div class="panel"><div class="toolbar"><h3>Daftar <?= e($title) ?></h3><input class="search" placeholder="Cari data..." data-table-search></div><div class="table-wrap"><table><thead><tr><?php foreach($columns as $key=>$label): ?><th><?= e($label) ?></th><?php endforeach; ?><th>Aksi</th></tr></thead><tbody>
<?php foreach($rows as $row): ?><tr><?php foreach($columns as $key=>$label): ?><td><?= e($row[$key]??'-') ?></td><?php endforeach; ?><td><div class="actions"><a class="btn secondary small" href="?edit=<?= (int)$row['id'] ?>">Edit</a><a class="btn danger small" data-confirm="Hapus data ini?" href="?delete=<?= (int)$row['id'] ?>">Hapus</a></div></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="<?= count($columns)+1 ?>" class="empty">Belum ada data.</td></tr><?php endif; ?></tbody></table></div></div>
<?php require __DIR__.'/footer.php'; ?>
