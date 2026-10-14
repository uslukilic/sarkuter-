<?php
require __DIR__ . '/inc/fonk.php';
$sayfalar = [
    'sozlesme' => ['Mesafeli Satış Sözleşmesi ve Ön Bilgilendirme', 'sozlesme_metni'],
    'iade' => ['İade ve Teslimat Koşulları', 'iade_metni'],
    'kvkk' => ['Gizlilik ve KVKK Aydınlatma Metni', 'kvkk_metni'],
];
$s = (string) ($_GET['s'] ?? '');
if (!isset($sayfalar[$s])) {
    yonlendir('index.php');
}
[$baslik, $anahtar] = $sayfalar[$s];
$metin = ayar($anahtar);
require __DIR__ . '/inc/ust.php';
?>
<div class="ust"><div class="kap ust-baslik"><h1 class="baslik h1"><?= e($baslik) ?></h1></div></div>
<main>
  <div class="kap bolum">
    <div class="yazi-sayfa">
      <?php if ($metin === ''): ?>
        <div class="uyari">Bu metin henüz yazılmadı. Yönetim panelinde Ayarlar &gt; Yasal metinler bölümünden eklenir.</div>
        <p class="soluk">Bu sayfanın metni işletmenize özeldir (ünvan, adres, vergi bilgileri, iade koşulları) ve hukuki sonuç doğurur. Hazır bir şablonu olduğu gibi kullanmak yerine bir avukata ya da mali müşavirinize danışmanız önerilir.</p>
      <?php else: ?>
        <?php foreach (preg_split('/\R{2,}/', $metin) as $par): ?><p><?= nl2br(e($par)) ?></p><?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php require __DIR__ . '/inc/alt.php'; ?>
