<?php
$table='categories';$title='Kategori Barang';$orderBy='id';
$fields=['name'=>['label'=>'Nama kategori','required'=>true],'description'=>['label'=>'Keterangan','type'=>'textarea','full'=>true]];
$columns=['id'=>'ID','name'=>'Nama kategori','description'=>'Keterangan'];require 'includes/master_page.php';