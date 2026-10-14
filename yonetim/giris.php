<?php
/* Ayrı yönetim giriş sayfası yok: yönetici de sitedeki "Giriş Yap" sayfasından girer. */
require_once __DIR__ . '/../inc/fonk.php';
header('X-Robots-Tag: noindex, nofollow');
yonlendir(yonetici() ? 'index.php' : '../giris.php');
