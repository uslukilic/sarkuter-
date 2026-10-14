<?php
require __DIR__ . '/_ust.php';
$musteriler = satirlar("SELECT m.*,
    (SELECT COUNT(*) FROM siparisler s WHERE s.musteri_id = m.id AND s.durum IN ('onay_bekliyor','onaylandi','yolda','teslim_edildi')) AS siparis_sayisi,
    (SELECT COALESCE(SUM(s.toplam),0) FROM siparisler s WHERE s.musteri_id = m.id AND s.durum IN ('onay_bekliyor','onaylandi','yolda','teslim_edildi')) AS harcama
    FROM musteriler m ORDER BY m.id DESC LIMIT 500");
y_ust('Müşteriler', 'musteriler');
?>
<div class="dugmeler" style="justify-content:flex-end; margin-bottom:12px"><a class="dugme kucuk cizgi" href="ornek-musteriler.php">Örnek müşteri ekle / sil</a></div>
<section class="kutu">
  <p class="soluk">Kayıtlı üyeler. Misafir siparişleri burada görünmez; onlara Siparişler sayfasından ulaşılır.</p>
  <?php if (!$musteriler): ?><p class="soluk">Henüz kayıtlı müşteri yok.</p><?php else: ?>
  <div class="tablo-kap"><table class="tablo">
    <thead><tr><th>Ad Soyad</th><th>E-posta</th><th>Telefon</th><th>Adres</th><th class="sag">Sipariş</th><th class="sag">Harcama</th><th>Kampanya izni</th><th>Kayıt</th></tr></thead>
    <tbody>
    <?php foreach ($musteriler as $m): ?>
      <tr><td><?= e($m['ad'] . ' ' . $m['soyad']) ?><?= $m['sifre'] === 'ORNEK' ? ' <span class="rozet notr">Örnek</span>' : '' ?></td><td><?= e($m['eposta']) ?></td><td><?= e($m['telefon']) ?></td><td><?= e($m['adres']) ?></td>
        <td class="sag"><?= (int) $m['siparis_sayisi'] ?></td><td class="sag"><?= tl($m['harcama']) ?></td><td><?= $m['kampanya_izni'] ? 'Var' : 'Yok' ?></td><td><?= e(tarih_yaz($m['olusturma'], false)) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php y_alt(); ?>
