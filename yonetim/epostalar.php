<?php
require __DIR__ . '/_ust.php';
$epostalar = satirlar("SELECT * FROM epostalar ORDER BY id DESC LIMIT 200");
y_ust('E-postalar', 'epostalar');
?>
<section class="kutu">
  <p class="soluk">Sitenin gönderdiği son 200 e-posta. "Gönderilemedi" yazanlar sunucudan çıkamamıştır; yerel bilgisayarda e-posta gönderilemediği için bu normaldir, içerikleri buradan okuyabilirsiniz.</p>
  <?php if (!$epostalar): ?><p class="soluk">Henüz e-posta yok.</p><?php endif; ?>
  <?php foreach ($epostalar as $x): ?>
    <details class="kayit">
      <summary><b><?= e($x['konu']) ?></b><span><?= e($x['kime']) ?></span><span class="soluk"><?= e(tarih_yaz($x['olusturma'])) ?></span><?= $x['gonderildi'] ? '<span class="rozet iyi">Gönderildi</span>' : '<span class="rozet notr">Gönderilemedi</span>' ?></summary>
      <pre><?= e($x['icerik']) ?></pre>
    </details>
  <?php endforeach; ?>
</section>
<?php y_alt(); ?>
