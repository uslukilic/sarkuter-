<?php
/* Yönetim paneli ortak üst bölümü. Sayfalar $baslik ve $aktif değişkenlerini ayarlar. */
require_once __DIR__ . '/../inc/fonk.php';
header('X-Robots-Tag: noindex, nofollow');
$__y = yonetici();
if (!$__y) {
    yonlendir('../giris.php');
}
function y_post_kontrol(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_kontrol();
    }
}
function y_ust(string $baslik, string $aktif): void
{
    global $__y;
    $bekleyen = (int) deger("SELECT COUNT(*) FROM siparisler WHERE durum = 'onay_bekliyor'");
    $okunmamis = (int) deger("SELECT COUNT(*) FROM mesajlar WHERE okundu = 0");
    $menu = [
        'siparisler' => ['index.php', 'Siparişler', $bekleyen],
        'urunler' => ['urunler.php', 'Ürünler ve stok', 0],
        'kategoriler' => ['kategoriler.php', 'Kategoriler', 0],
        'dilimler' => ['dilimler.php', 'Teslimat saatleri', 0],
        'musteriler' => ['musteriler.php', 'Müşteriler', 0],
        'mesajlar' => ['mesajlar.php', 'Mesajlar', $okunmamis],
        'epostalar' => ['epostalar.php', 'E-postalar', 0],
        'ayarlar' => ['ayarlar.php', 'Ayarlar', 0],
    ];
    ?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($baslik) ?> – Yönetim</title>
<link rel="icon" href="../assets/img/logo.svg">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/site.css">
</head>
<body class="yonetim">
<header class="ust"><div class="kap ust-ic">
  <a class="marka" href="index.php"><img class="logo" src="../assets/img/logo.svg" alt="" width="38" height="38"><span class="marka-ad"><?= e(ayar('marka')) ?> · Yönetim</span></a>
  <div class="ust-sag">
    <a class="baglanti" style="text-decoration:none" href="../index.php">Siteyi gör</a>
    <a class="baglanti" style="text-decoration:none" href="sifre.php"><?= e($__y['kullanici_adi']) ?></a>
    <form method="post" action="cikis.php" style="margin:0"><?= csrf_alan() ?><button class="dugme kucuk cizgi" type="submit">Çıkış</button></form>
  </div>
</div>
<nav class="kap y-menu" aria-label="Yönetim menüsü">
  <?php foreach ($menu as $k => $m): ?>
    <a href="<?= e($m[0]) ?>"<?= $k === $aktif ? ' class="aktif" aria-current="page"' : '' ?>><?= e($m[1]) ?><?php if ($m[2]): ?> <span class="sepet-adet"><?= (int) $m[2] ?></span><?php endif; ?></a>
  <?php endforeach; ?>
</nav></header>
<main class="kap y-govde">
<h1 class="baslik h2" style="margin-bottom:16px"><?= e($baslik) ?></h1>
<?php foreach (mesajlari_al() as $x): ?><div class="bilgi <?= e($x[0]) ?>" role="status" style="margin-bottom:14px"><?= e($x[1]) ?></div><?php endforeach; ?>
    <?php
}
function y_alt(): void
{
    echo '</main><script src="../assets/site.js"></script></body></html>';
}
