<?php
require __DIR__ . '/_ust.php';
$filtre = (string) ($_GET['durum'] ?? 'acik');
$gruplar = [
    'acik' => ['Açık siparişler', "durum IN ('onay_bekliyor','onaylandi','yolda')"],
    'onay_bekliyor' => ['Onay bekleyen', "durum = 'onay_bekliyor'"],
    'onaylandi' => ['Hazırlanan', "durum = 'onaylandi'"],
    'yolda' => ['Yolda', "durum = 'yolda'"],
    'teslim_edildi' => ['Teslim edilen', "durum = 'teslim_edildi'"],
    'kapali' => ['Reddedilen / iptal', "durum IN ('reddedildi','iptal')"],
    'odeme' => ['Ödemesi tamamlanmayan', "durum IN ('odeme_bekliyor','odeme_basarisiz')"],
    'hepsi' => ['Tümü', "1 = 1"],
];
if (!isset($gruplar[$filtre])) {
    $filtre = 'acik';
}
$siparisler = satirlar("SELECT * FROM siparisler WHERE " . $gruplar[$filtre][1] . "
    ORDER BY CASE durum WHEN 'onay_bekliyor' THEN 0 ELSE 1 END, teslimat_tarihi, teslimat_saati, id DESC LIMIT 300");
$bugun = date('Y-m-d');
$ozet = [
    'Onay bekleyen' => (int) deger("SELECT COUNT(*) FROM siparisler WHERE durum = 'onay_bekliyor'"),
    'Bugün teslim edilecek' => (int) deger("SELECT COUNT(*) FROM siparisler WHERE teslimat_tarihi = ? AND durum IN ('onay_bekliyor','onaylandi','yolda')", [$bugun]),
    'Bugünkü ciro' => tl((int) deger("SELECT COALESCE(SUM(toplam),0) FROM siparisler WHERE olusturma >= ? AND durum IN ('onay_bekliyor','onaylandi','yolda','teslim_edildi')", [$bugun . ' 00:00:00'])),
    'İade bekleyen' => (int) deger("SELECT COUNT(*) FROM siparisler WHERE iade = 'bekliyor'"),
    'Stoku azalan ürün' => (int) deger("SELECT COUNT(*) FROM urunler WHERE aktif = 1 AND stok <= ?", [(int) ayar('stok_uyari', '5')]),
];
y_ust('Siparişler', 'siparisler');
?>
<div class="y-ozet">
  <?php foreach ($ozet as $ad => $d): ?><div class="kart"><span class="soluk"><?= e($ad) ?></span><b><?= e($d) ?></b></div><?php endforeach; ?>
</div>
<div class="dugmeler" style="margin-top:14px; justify-content:flex-end"><a class="dugme kucuk cizgi" href="ornek-siparisler.php">Örnek sipariş ekle / sil</a></div>
<nav class="cipler" aria-label="Sipariş süzgeci" style="margin:10px 0 18px">
  <?php foreach ($gruplar as $k => $g): ?>
    <a class="cip" style="display:inline-flex;align-items:center" href="index.php?durum=<?= e($k) ?>"<?= $k === $filtre ? ' aria-current="true"' : '' ?>><?= e($g[0]) ?></a>
  <?php endforeach; ?>
</nav>
<section class="kutu">
  <?php if (!$siparisler): ?><p class="soluk">Bu grupta sipariş yok.</p><?php else: ?>
  <div class="tablo-kap"><table class="tablo">
    <thead><tr><th>Sipariş</th><th>Müşteri</th><th>Teslimat</th><th>Durum</th><th class="sag">Toplam</th><th>Sipariş tarihi</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($siparisler as $s): ?>
      <tr>
        <td><a class="baglanti" style="min-height:0" href="siparis.php?id=<?= (int) $s['id'] ?>"><?= e($s['no']) ?></a></td>
        <td><?= e($s['ad']) ?><br><span class="soluk"><?= e($s['telefon']) ?><?= $s['musteri_id'] ? '' : ' · misafir' ?></span></td>
        <td><?= e(tarih_yaz($s['teslimat_tarihi'], false)) ?><?= $s['teslimat_tarihi'] === $bugun ? ' (bugün)' : '' ?><br><span class="soluk"><?= e(str_replace('-', ' – ', $s['teslimat_saati'])) ?></span></td>
        <td><?= durum_rozet($s['durum']) ?><?php if ($s['iade'] === 'bekliyor'): ?> <span class="rozet kotu">İade bekliyor</span><?php endif; ?></td>
        <td class="sag"><?= tl($s['toplam']) ?><?php if (kapida_mi($s)): ?><br><span class="rozet notr">Kapıda</span><?php endif; ?></td>
        <td><?= e(tarih_yaz($s['olusturma'])) ?></td>
        <td><a class="dugme kucuk cizgi" href="siparis.php?id=<?= (int) $s['id'] ?>">Aç</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php y_alt(); ?>
