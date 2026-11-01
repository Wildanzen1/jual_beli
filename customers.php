<?php
$table='customers';$title='Data Pelanggan';$orderBy='id';
$fields=['name'=>['label'=>'Nama pelanggan','required'=>true],
'phone'=>['label'=>'Nomor telepon'],'email'=>['label'=>'Email','type'=>'email']
,'address'=>['label'=>'Alamat','type'=>'textarea','full'=>true]];
$columns=['id'=>'ID','name'=>'Nama','phone'=>'Telepon','email'=>'Email','address'=>'Alamat'];require 'includes/master_page.php';