<?php
require __DIR__ . '/inc/fonk.php';
require __DIR__ . '/inc/paytr.php';
$s = siparis_getir_no((string) ($_GET['no'] ?? ''));
if (!$s || !hash_equals($s['anahtar'], (string) ($_GET['k'] ?? ''))) {
    http_response_code(404);
    exit('Sipariş bulunamadı.');
}
$adres = 'siparis.php?no=' . urlencode($s['no']) . '&k=' . urlencode($s['anahtar']);
if (!in_array($s['durum'], ['odeme_bekliyor', 'odeme_basarisiz'], true) || kapida_mi($s)) {
    yonlendir($adres);
}
$kalemler = siparis_kalemleri((int) $s['id']);

// Ödemeden hemen önce stok hâlâ yeterli mi?
$stokSorunu = '';
foreach ($kalemler as $k) {
    $u = urun_getir((int) $k['urun_id']);
    if (!$u || !$u['aktif'] || (int) $u['stok'] < (int) $k['adet']) {
        $stokSorunu = $k['urun_adi'];
        break;
    }
}
if ($stokSorunu !== '') {
    siparis_durum_yaz((int) $s['id'], 'iptal', ['ret_nedeni' => 'Ödeme öncesinde stok tükendi: ' . $stokSorunu]);
    mesaj_yaz($stokSorunu . ' bu arada tükendi; sipariş iptal edildi ve ödeme alınmadı. Sepetini yeniden oluşturabilirsin.', 'hata');
    yonlendir($adres);
}

// Deneme ödemesi yalnızca ayar.php'de DENEME_ODEMESI açıkken (yerelde) kullanılabilir.
$deneme = !paytr_aktif() && DENEME_ODEMESI;
if ($deneme && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    odeme_sonucu_isle($s, post('sonuc') === 'basarili', 'deneme', 'Deneme ödemesi (gerçek para çekilmedi)');
    yonlendir($adres);
}

$token = null;
$hata = '';
if (!paytr_aktif() && !$deneme) {
    $hata = 'Kartla ödeme şu an kullanılamıyor.';
} elseif (!$deneme) {
    if ($s['durum'] === 'odeme_basarisiz') {
        siparis_durum_yaz((int) $s['id'], 'odeme_bekliyor');
    }
    $yanit = paytr_token_al($s, $kalemler);
    $token = $yanit['token'] ?? null;
    $hata = $yanit['hata'] ?? '';
}
$baslik = 'Ödeme';
require __DIR__ . '/inc/ust.php';
?>
<div class="ust">
  <div class="kap ust-baslik">
    <h1 class="baslik h1">Ödeme</h1>
    <ol class="adimlar">
      <li>1 · Sepet ve teslimat</li>
      <li class="aktif">2 · Ödeme</li>
      <li>3 · Sipariş onayı</li>
    </ol>
  </div>
</div>
<main>
  <div class="kap iki">
    <div class="genis">
      <?php if ($deneme): ?>
        <section class="kutu">
          <h2>Deneme ödemesi</h2>
          <div class="uyari">PayTR mağaza bilgileri henüz girilmediği için site deneme modunda. Bu sayfada kart bilgisi istenmez ve gerçek para çekilmez.</div>
          <p class="soluk">Sipariş akışını denemek için ödeme sonucunu aşağıdan seç. PayTR bilgileri girildiğinde bu kutunun yerinde PayTR'nin kart formu açılır.</p>
          <form method="post" class="dugmeler">
            <?= csrf_alan() ?>
            <button class="dugme bordo" type="submit" name="sonuc" value="basarili">Ödeme başarılı say</button>
            <button class="dugme cizgi" type="submit" name="sonuc" value="basarisiz">Ödeme başarısız say</button>
          </form>
        </section>
      <?php elseif ($token): ?>
        <section class="kutu" style="padding:8px">
          <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
          <iframe src="https://www.paytr.com/odeme/guvenli/<?= e($token) ?>" id="paytriframe" title="PayTR güvenli ödeme formu" frameborder="0" scrolling="no" style="width:100%"></iframe>
          <script>iFrameResize({}, '#paytriframe');</script>
        </section>
      <?php else: ?>
        <div class="bilgi hata" role="alert">Ödeme sayfası açılamadı: <?= e($hata) ?> Lütfen biraz sonra tekrar dene ya da bizi ara: <?= e(ayar('telefon')) ?></div>
        <a class="dugme cizgi" href="odeme.php?no=<?= e(urlencode($s['no'])) ?>&amp;k=<?= e($s['anahtar']) ?>">Tekrar dene</a>
      <?php endif; ?>
    </div>
    <aside class="dar ozet">
      <h2>Sipariş <?= e($s['no']) ?></h2>
      <?php foreach ($kalemler as $k): ?>
        <div class="ozet-satir"><span><?= (int) $k['adet'] ?> × <?= e($k['urun_adi']) ?></span><b><?= tl($k['fiyat'] * $k['adet']) ?></b></div>
      <?php endforeach; ?>
      <div class="ozet-satir"><span>Teslimat</span><b><?= $s['teslimat_ucreti'] ? tl($s['teslimat_ucreti']) : 'Ücretsiz' ?></b></div>
      <div class="ozet-satir ozet-toplam"><b>Toplam</b><b><?= tl($s['toplam']) ?></b></div>
      <small>Teslimat: <?= e(tarih_yaz($s['teslimat_tarihi'], false)) ?> <?= e(gun_adi($s['teslimat_tarihi'])) ?>, <?= e(str_replace('-', ' – ', $s['teslimat_saati'])) ?></small>
    </aside>
  </div>
</main>
<?php require __DIR__ . '/inc/alt.php'; ?>
