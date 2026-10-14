<?php
require __DIR__ . '/inc/fonk.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    unset($_SESSION['musteri_id']);
    session_regenerate_id(true);
    mesaj_yaz('Çıkış yapıldı.');
}
yonlendir('index.php');
