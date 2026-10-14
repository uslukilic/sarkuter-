<?php
require_once __DIR__ . '/../inc/fonk.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    unset($_SESSION['yonetici_id']);
    session_regenerate_id(true);
}
yonlendir('../giris.php');
