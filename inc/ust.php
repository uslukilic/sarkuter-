<?php
/* Sitenin üst bölümü. Sayfalar $baslik ve $sayfa değişkenlerini ayarlayıp bu dosyayı çağırır.
   Yapı: 1) üst bilgi şeridi  2) logo + arama + sepet kutusu  3) kategori menüsü */
$baslik = $baslik ?? '';
$sayfa = $sayfa ?? '';
$__kategoriler = kategori_agaci();
$__musteri = musteri();
$__seciliKat = (int) ($_GET['kategori'] ?? 0);
$__arama = $sayfa === 'ana' ? kisalt(trim((string) ($_GET['ara'] ?? '')), 60) : '';
// Sepet kutusu için adet ve ara toplam (sepeti değiştirmeden hesaplanır)
$__sepetAdet = 0;
$__sepetTutar = 0;
foreach (sepet() as $__id => $__n) {
    $__u = urun_getir((int) $__id);
    if ($__u && $__u['aktif']) {
        $__sepetAdet += (int) $__n;
        $__sepetTutar += (int) $__u['fiyat'] * (int) $__n;
    }
}
function __aktif(string $s, string $sayfa): string
{
    return $s === $sayfa ? ' class="aktif" aria-current="page"' : '';
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($baslik) ?> – <?= e(ayar('marka')) ?></title>
<link rel="icon" href="assets/img/logo.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/site.css">
</head>
<body>
<div class="duyuru"><div class="kap">
  <span><?php if ((int) ayar('ucretsiz_teslimat') > 0): ?><?= tl(ayar('ucretsiz_teslimat')) ?> ve üzeri siparişlerde teslimat ücretsiz · <?php endif; ?><?= e(ayar('teslimat_bolgesi')) ?></span>
  <nav class="duyuru-bag" aria-label="Hesap ve yardım">
    <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) ayar('telefon'))) ?>"><?= e(ayar('telefon')) ?></a>
    <a href="siparis-sorgula.php">Sipariş Takibi</a>
    <?php if (yonetici()): ?><a href="yonetim/index.php">Yönetim paneli</a><?php endif; ?>
    <?php if ($__musteri): ?>
      <a href="hesabim.php">Hesabım</a>
      <form method="post" action="cikis.php"><?= csrf_alan() ?><button type="submit">Çıkış</button></form>
    <?php else: ?>
      <a href="giris.php">Giriş Yap</a>
      <a href="giris.php#kayit">Üye Ol</a>
    <?php endif; ?>
  </nav>
</div></div>
<header class="ust magaza"><div class="kap ust-ic">
  <a class="marka" href="index.php" aria-label="Ana sayfa"><img class="logo" src="assets/img/logo.svg" alt="" width="44" height="44"><span class="marka-ad"><?= e(ayar('marka')) ?></span></a>
  <form class="ust-arama" method="get" action="index.php" role="search">
    <label class="gorunmez" for="ust-ara">Ürün ara</label>
    <input id="ust-ara" name="ara" type="search" placeholder="Ürün ara: kaşar, sucuk, zeytin, reçel…" value="<?= e($__arama) ?>" maxlength="60" required>
    <button type="submit">Ara</button>
  </form>
  <a class="sepet-kutu" href="sepet.php">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 12h11l2-8H6"/></svg>
    <span><b>Sepetim</b><small><?= $__sepetAdet ?> ürün · <?= tl($__sepetTutar) ?></small></span>
  </a>
</div></header>
<nav class="kat-menu" aria-label="Kategoriler"><div class="kap">
  <button type="button" class="kat-menu-dugme" aria-expanded="false" aria-controls="kat-menu-liste">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>Menü</button>
  <ul id="kat-menu-liste">
    <li><a href="index.php"<?= ($sayfa === 'ana' && !$__seciliKat && $__arama === '' && !isset($_GET['tumu'])) ? ' class="aktif" aria-current="page"' : '' ?>>Anasayfa</a></li>
    <?php foreach ($__kategoriler as $__kat):
        $__icinde = $__seciliKat === (int) $__kat['id'];
        foreach ($__kat['alt'] as $__alt) { if ($__seciliKat === (int) $__alt['id']) { $__icinde = true; } } ?>
      <li<?= $__kat['alt'] ? ' class="altli"' : '' ?>>
        <a href="index.php?kategori=<?= (int) $__kat['id'] ?>"<?= ($sayfa === 'ana' && $__icinde) ? ' class="aktif"' : '' ?>><?= e($__kat['ad']) ?><?php if ($__kat['alt']): ?>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg><?php endif; ?></a>
        <?php if ($__kat['alt']): ?>
        <ul class="alt-menu">
          <?php foreach ($__kat['alt'] as $__alt): ?><li><a href="index.php?kategori=<?= (int) $__alt['id'] ?>"><?= e($__alt['ad']) ?></a></li><?php endforeach; ?>
          <li><a class="hepsi" href="index.php?kategori=<?= (int) $__kat['id'] ?>">Tüm <?= e($__kat['ad']) ?> ürünleri</a></li>
        </ul>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
    <li><a href="hakkimizda.php"<?= __aktif('hakkimizda', $sayfa) ?>>Hakkımızda</a></li>
    <li><a href="iletisim.php"<?= __aktif('iletisim', $sayfa) ?>>İletişim</a></li>
  </ul>
</div></nav>
<?php foreach (mesajlari_al() as $__m): ?>
  <div class="kap" style="padding-top:14px"><div class="bilgi <?= e($__m[0]) ?>" role="status"><?= e($__m[1]) ?></div></div>
<?php endforeach; ?>
