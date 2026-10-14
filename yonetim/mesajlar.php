<?php
require __DIR__ . '/_ust.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    $id = (int) post('id');
    if (post('islem') === 'okundu') {
        q("UPDATE mesajlar SET okundu = CASE WHEN okundu = 1 THEN 0 ELSE 1 END WHERE id = ?", [$id]);
    } elseif (post('islem') === 'sil') {
        q("DELETE FROM mesajlar WHERE id = ?", [$id]);
        mesaj_yaz('Mesaj silindi.', 'basari');
    }
    yonlendir('mesajlar.php');
}
$mesajlar = satirlar("SELECT * FROM mesajlar ORDER BY okundu, id DESC LIMIT 300");
y_ust('Mesajlar', 'mesajlar');
?>
<section class="kutu">
  <p class="soluk">İletişim formundan gelen mesajlar.</p>
  <?php if (!$mesajlar): ?><p class="soluk">Henüz mesaj yok.</p><?php endif; ?>
  <?php foreach ($mesajlar as $m): ?>
    <details class="kayit"<?= $m['okundu'] ? '' : ' open' ?>>
      <summary><b><?= e($m['ad']) ?></b><span><?= e($m['konu']) ?></span><span class="soluk"><?= e(tarih_yaz($m['olusturma'])) ?></span><?= $m['okundu'] ? '' : '<span class="rozet uyari">Yeni</span>' ?></summary>
      <pre><?= e($m['mesaj']) ?></pre>
      <p class="soluk" style="margin-top:8px"><?= e($m['eposta']) ?><?= $m['telefon'] ? ' · ' . e($m['telefon']) : '' ?></p>
      <div class="satir-form" style="margin-top:8px">
        <form method="post" style="margin:0"><?= csrf_alan() ?><input type="hidden" name="islem" value="okundu"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="dugme kucuk cizgi" type="submit"><?= $m['okundu'] ? 'Okunmadı yap' : 'Okundu yap' ?></button></form>
        <form method="post" style="margin:0"><?= csrf_alan() ?><input type="hidden" name="islem" value="sil"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="dugme kucuk cizgi" type="submit" data-onay="Mesaj silinsin mi?">Sil</button></form>
      </div>
    </details>
  <?php endforeach; ?>
</section>
<?php y_alt(); ?>
