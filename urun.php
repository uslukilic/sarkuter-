<?php
require __DIR__ . '/inc/fonk.php';
require __DIR__ . '/inc/kart.php';
$u = urun_getir((int) ($_GET['id'] ?? 0));
if (!$u || !$u['aktif']) {
    http_response_code(404);
    $baslik = 'Ürün bulunamadı';
    require __DIR__ . '/inc/ust.php';
    echo '<main><div class="kap bolum"><div class="kutu" style="align-items:flex-start"><h2>Ürün bulunamadı</h2><p class="soluk">Bu ürün satıştan kaldırılmış olabilir.</p><a class="dugme bordo" href="index.php#urunler">Ürünleri gör</a></div></div></main>';
    require __DIR__ . '/inc/alt.php';
    exit;
}
$baslik = $u['ad'];
$sayfa = 'urun';
$stok = (int) $u['stok'];
$diger = satirlar("SELECT * FROM urunler WHERE aktif = 1 AND id <> ? AND kategori_id <> ? AND stok > 0 ORDER BY id LIMIT 3", [$u['id'], $u['kategori_id']]);
require __DIR__ . '/inc/ust.php';
?>
<main>
  <nav class="kap ekmek" aria-label="Sayfa yolu">
    <a href="index.php">Ana Sayfa</a><span>/</span>
    <a href="index.php#urunler">Ürünler</a><span>/</span>
    <?php if ($u['ust_kategori']): ?><a href="index.php?kategori=<?= (int) $u['ust_kategori_id'] ?>#urunler"><?= e($u['ust_kategori']) ?></a><span>/</span><?php endif; ?>
    <a href="index.php?kategori=<?= (int) $u['kategori_id'] ?>#urunler"><?= e($u['kategori']) ?></a><span>/</span>
    <span style="color:var(--murekkep)"><?= e($u['ad']) ?></span>
  </nav>

  <div class="kap detay">
    <div>
      <img class="detay-gorsel" src="<?= e($u['gorsel']) ?>" alt="<?= e($u['ad']) ?>" width="600" height="450">
    </div>
    <div>
      <span class="etiket" style="align-self:flex-start"><?= e($u['kategori']) ?></span>
      <h1 class="baslik h1"><?= e($u['ad']) ?></h1>
      <p class="soluk"><?= e($u['kisa']) ?></p>
      <div class="fiyat buyuk"><?= tl($u['fiyat']) ?></div>
      <?php if ($stok > 0): ?>
        <form class="satin-al" method="post" action="sepet.php">
          <?= csrf_alan() ?>
          <input type="hidden" name="islem" value="ekle">
          <input type="hidden" name="urun_id" value="<?= (int) $u['id'] ?>">
          <input type="hidden" name="don" value="urun.php?id=<?= (int) $u['id'] ?>">
          <div class="adet">
            <button type="button" data-adim="-1" aria-label="Adedi azalt">−</button>
            <input type="number" name="adet" value="1" min="1" max="<?= min(99, $stok) ?>" aria-label="Adet">
            <button type="button" data-adim="1" aria-label="Adedi artır">+</button>
          </div>
          <button type="submit" class="dugme bordo">Sepete ekle</button>
        </form>
        <?php if ($stok <= (int) ayar('stok_uyari', '5')): ?><p class="soluk">Son <?= $stok ?> adet.</p><?php endif; ?>
      <?php else: ?>
        <div class="uyari">Bu ürün şu an tükendi.</div>
      <?php endif; ?>
      <div class="satirlar">
        <div class="satir"><b>Paket</b><span><?= e($u['paket']) ?></span></div>
        <?php if ($u['icindekiler'] !== ''): ?><div class="satir"><b>İçindekiler</b><span><?= e($u['icindekiler']) ?></span></div><?php endif; ?>
        <?php if ($u['saklama'] !== ''): ?><div class="satir"><b>Saklama koşulları</b><span><?= e($u['saklama']) ?></span></div><?php endif; ?>
        <?php if ($u['raf'] !== ''): ?><div class="satir"><b>Son tüketim</b><span><?= e($u['raf']) ?></span></div><?php endif; ?>
        <div class="satir"><b>Teslimat</b><span>Yalnızca <?= e(ayar('teslimat_bolgesi')) ?>.<?php if ((int) ayar('ucretsiz_teslimat') > 0): ?> <?= tl(ayar('ucretsiz_teslimat')) ?> ve üzeri siparişlerde ücretsiz.<?php endif; ?></span></div>
      </div>
    </div>
  </div>

  <?php if ($diger): ?>
  <section class="zemin-bugday">
    <div class="kap bolum">
      <h2 class="baslik h2 bolum-baslik">Yanına yakışır</h2>
      <div class="urunler"><?php foreach ($diger as $d) { echo urun_karti($d); } ?></div>
    </div>
  </section>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/inc/alt.php'; ?>
