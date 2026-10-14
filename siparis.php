<?php
require __DIR__ . '/inc/fonk.php';
$s = siparis_getir_no((string) ($_GET['no'] ?? ''));
$m = musteri();
$yetkili = $s && (hash_equals($s['anahtar'], (string) ($_GET['k'] ?? '')) || ($m && (int) $s['musteri_id'] === (int) $m['id']));
if (!$yetkili) {
    http_response_code(404);
    $baslik = 'Sipariş bulunamadı';
    require __DIR__ . '/inc/ust.php';
    echo '<main><div class="kap bolum"><div class="kutu" style="align-items:flex-start"><h2>Sipariş bulunamadı</h2><p class="soluk">Bağlantıyı sipariş e-postanızdan açmayı deneyin.</p></div></div></main>';
    require __DIR__ . '/inc/alt.php';
    exit;
}
$kalemler = siparis_kalemleri((int) $s['id']);
$baslik = 'Sipariş ' . $s['no'];
$aciklama = [
    'odeme_bekliyor' => 'Ödeme henüz tamamlanmadı.',
    'odeme_basarisiz' => 'Ödeme alınamadı. Tekrar deneyebilirsin.',
    'onay_bekliyor' => kapida_mi($s) ? 'Siparişin alındı ve onay bekliyor; onaylanınca e-posta ile haber vereceğiz. Ödemeyi teslimatta yapacaksın.' : 'Ödemen alındı. Siparişin onay bekliyor; onaylanınca e-posta ile haber vereceğiz.',
    'onaylandi' => 'Siparişin onaylandı ve hazırlanıyor.',
    'yolda' => 'Siparişin yola çıktı.',
    'teslim_edildi' => 'Siparişin teslim edildi. Afiyet olsun.',
    'reddedildi' => kapida_mi($s) ? 'Siparişin onaylanamadı. Senden herhangi bir ödeme alınmadı.' : 'Siparişin onaylanamadı. Ödediğin tutar kartına iade edilecek.',
    'iptal' => 'Sipariş iptal edildi.',
][$s['durum']] ?? '';
require __DIR__ . '/inc/ust.php';
?>
<div class="ust">
  <div class="kap ust-baslik">
    <h1 class="baslik h1">Sipariş <?= e($s['no']) ?></h1>
    <p><?= durum_rozet($s['durum']) ?> <?= e($aciklama) ?></p>
  </div>
</div>
<main>
  <div class="kap iki">
    <div class="genis">
      <?php if (in_array($s['durum'], ['odeme_bekliyor', 'odeme_basarisiz'], true)): ?>
        <section class="kutu yatay"><div><b>Ödeme bekleniyor</b><p class="soluk">Siparişin ödeme tamamlanınca işleme alınır.</p></div>
          <a class="dugme bordo" href="odeme.php?no=<?= e(urlencode($s['no'])) ?>&amp;k=<?= e($s['anahtar']) ?>">Ödemeye geç</a></section>
      <?php endif; ?>
      <?php if ($s['ret_nedeni'] !== null && $s['ret_nedeni'] !== ''): ?>
        <div class="bilgi hata"><b>Açıklama:</b> <?= e($s['ret_nedeni']) ?></div>
      <?php endif; ?>
      <section class="kutu">
        <h2>Sipariş takibi</h2>
        <?= siparis_cizelgesi($s) ?>
      </section>
      <section class="kutu">
        <h2>Ürünler</h2>
        <?php foreach ($kalemler as $k): ?>
          <div class="kalem"><div class="ad"><b><?= e($k['urun_adi']) ?></b><span class="soluk"><?= e($k['paket']) ?> · <?= (int) $k['adet'] ?> adet × <?= tl($k['fiyat']) ?></span></div><div class="tutar"><?= tl($k['fiyat'] * $k['adet']) ?></div></div>
        <?php endforeach; ?>
      </section>
      <section class="kutu">
        <h2>Teslimat</h2>
        <div class="satirlar" style="margin-top:0">
          <div class="satir"><b>Zaman</b><span><?= e(tarih_yaz($s['teslimat_tarihi'], false)) ?> <?= e(gun_adi($s['teslimat_tarihi'])) ?>, <?= e(str_replace('-', ' – ', $s['teslimat_saati'])) ?></span></div>
          <div class="satir"><b>Alıcı</b><span><?= e($s['ad']) ?> · <?= e($s['telefon']) ?></span></div>
          <div class="satir"><b>Adres</b><span><?= e($s['adres']) ?></span></div>
          <?php if ($s['siparis_notu'] !== ''): ?><div class="satir"><b>Not</b><span><?= e($s['siparis_notu']) ?></span></div><?php endif; ?>
        </div>
      </section>
    </div>
    <aside class="dar ozet">
      <h2>Özet</h2>
      <div class="ozet-satir"><span>Sipariş tarihi</span><b><?= e(tarih_yaz($s['olusturma'])) ?></b></div>
      <div class="ozet-satir"><span>Ara toplam</span><b><?= tl($s['ara_toplam']) ?></b></div>
      <div class="ozet-satir"><span>Teslimat</span><b><?= $s['teslimat_ucreti'] ? tl($s['teslimat_ucreti']) : 'Ücretsiz' ?></b></div>
      <div class="ozet-satir ozet-toplam"><b>Toplam</b><b><?= tl($s['toplam']) ?></b></div>
      <div class="ozet-satir"><span>Ödeme</span><b><?= kapida_mi($s) ? 'Kapıda ödeme' : 'Kartla (PayTR)' ?></b></div>
      <small>Sipariş numaran: <b><?= e($s['no']) ?></b>. Bu numara ve e-posta adresinle "Sipariş Takibi" sayfasından durumu her zaman sorgulayabilirsin. Sorun olursa: <?= e(ayar('telefon')) ?></small>
      <a class="baglanti" href="index.php#urunler">Alışverişe devam et</a>
    </aside>
  </div>
</main>
<?php require __DIR__ . '/inc/alt.php'; ?>
