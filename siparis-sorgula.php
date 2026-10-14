<?php
require __DIR__ . '/inc/fonk.php';
$baslik = 'Sipariş Takibi';
$sayfa = 'takip';
$hata = '';
$no = '';
$eposta = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    $no = strtoupper(preg_replace('/\s+/', '', post('no')));
    $eposta = mb_strtolower(post('eposta'));
    if (!deneme_siniri('sorgu')) {
        $hata = 'Çok fazla deneme yapıldı. 10 dakika sonra tekrar dene.';
    } else {
        $s = siparis_getir_no($no);
        // Numara ve e-posta birlikte doğruysa sipariş gösterilir; hangisinin yanlış olduğu söylenmez.
        if ($s && hash_equals(mb_strtolower($s['eposta']), $eposta)) {
            unset($_SESSION['deneme_sorgu']);
            yonlendir('siparis.php?no=' . urlencode($s['no']) . '&k=' . urlencode($s['anahtar']));
        }
        $hata = 'Bu sipariş numarası ve e-posta adresiyle eşleşen bir sipariş bulunamadı. Numarayı sipariş e-postandaki gibi yaz.';
    }
}
$m = musteri();
require __DIR__ . '/inc/ust.php';
?>
<div class="ust">
  <div class="kap ust-baslik">
    <h1 class="baslik h1">Sipariş Takibi</h1>
    <p>Sipariş numaran ve sipariş verirken yazdığın e-posta adresiyle siparişinin hangi aşamada olduğunu gör.</p>
  </div>
</div>
<main>
  <div class="kap iki">
    <form class="kutu form" method="post" style="flex:1 1 380px; min-width:0; max-width:520px">
      <?= csrf_alan() ?>
      <h2>Siparişini sorgula</h2>
      <?php if ($hata): ?><div class="bilgi hata" role="alert"><?= e($hata) ?></div><?php endif; ?>
      <div class="alan"><label for="q-no">Sipariş numarası</label><input id="q-no" name="no" type="text" required autocomplete="off" placeholder="Örn. S26100612345" value="<?= e($no) ?>" aria-describedby="q-no-not"><small id="q-no-not">Sipariş onay sayfasında ve sana gelen e-postada yazar.</small></div>
      <div class="alan"><label for="q-eposta">E-posta</label><input id="q-eposta" name="eposta" type="email" required autocomplete="email" value="<?= e($eposta) ?>"></div>
      <button type="submit" class="dugme bordo">Sorgula</button>
    </form>
    <div class="yan">
      <div class="kutu">
        <h2>Üye misin?</h2>
        <?php if ($m): ?>
          <p class="soluk">Bütün siparişlerin ve durumları hesabında listelenir.</p>
          <a class="dugme cizgi" style="align-self:flex-start" href="hesabim.php">Siparişlerime git</a>
        <?php else: ?>
          <p class="soluk">Giriş yaparsan bütün siparişlerini ve durumlarını numara yazmadan "Hesabım" sayfasında görürsün.</p>
          <a class="dugme cizgi" style="align-self:flex-start" href="giris.php?don=hesabim.php">Giriş yap</a>
        <?php endif; ?>
      </div>
      <div class="kutu">
        <h2>Aşamalar</h2>
        <ol class="takip">
          <li class="tamam"><span class="nokta" aria-hidden="true"></span><div><b>Sipariş alındı</b><span>Siparişin kaydedildi.</span></div></li>
          <li class="tamam"><span class="nokta" aria-hidden="true"></span><div><b>Ödeme alındı, onay bekliyor</b><span>Dükkânın onayı bekleniyor.</span></div></li>
          <li class="simdi"><span class="nokta" aria-hidden="true"></span><div><b>Onaylandı, hazırlanıyor</b><span>Ürünlerin paketleniyor.</span></div></li>
          <li class="bekliyor"><span class="nokta" aria-hidden="true"></span><div><b>Yola çıktı</b><span>Kurye adresine geliyor.</span></div></li>
          <li class="bekliyor"><span class="nokta" aria-hidden="true"></span><div><b>Teslim edildi</b><span>Sipariş tamamlandı.</span></div></li>
        </ol>
      </div>
    </div>
  </div>
</main>
<?php require __DIR__ . '/inc/alt.php'; ?>
