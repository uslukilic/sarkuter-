<?php $__altKat = kategori_agaci(); ?>
<footer class="alt">
  <div class="kap alt-sutunlar">
    <div class="alt-sutun alt-marka">
      <a class="marka" href="index.php"><img class="logo" src="assets/img/logo.svg" alt="" width="40" height="40"><span class="marka-ad"><?= e(ayar('marka')) ?></span></a>
      <p><?= e(ayar('adres')) ?></p>
      <p><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) ayar('telefon'))) ?>"><?= e(ayar('telefon')) ?></a><?php if (ayar('eposta')): ?><br><a href="mailto:<?= e(ayar('eposta')) ?>"><?= e(ayar('eposta')) ?></a><?php endif; ?></p>
      <?php if (ayar('calisma_saatleri')): ?><p><?= e(ayar('calisma_saatleri')) ?></p><?php endif; ?>
    </div>
    <div class="alt-sutun">
      <h2>Kategoriler</h2>
      <?php foreach ($__altKat as $__ak): ?><a href="index.php?kategori=<?= (int) $__ak['id'] ?>"><?= e($__ak['ad']) ?></a><?php endforeach; ?>
      <a href="index.php?tumu=1">Tüm ürünler</a>
    </div>
    <div class="alt-sutun">
      <h2>Kurumsal</h2>
      <a href="hakkimizda.php">Hakkımızda</a>
      <a href="iletisim.php">İletişim</a>
      <a href="sayfa.php?s=sozlesme">Mesafeli Satış Sözleşmesi</a>
      <a href="sayfa.php?s=iade">İade ve Teslimat</a>
      <a href="sayfa.php?s=kvkk">Gizlilik ve KVKK</a>
    </div>
    <div class="alt-sutun">
      <h2>Müşteri</h2>
      <a href="siparis-sorgula.php">Sipariş Takibi</a>
      <a href="sepet.php">Sepetim</a>
      <?php if (musteri()): ?><a href="hesabim.php">Hesabım</a><?php else: ?><a href="giris.php">Giriş Yap</a><a href="giris.php#kayit">Üye Ol</a><?php endif; ?>
    </div>
  </div>
  <div class="kap alt-son"><small>Ödeme altyapısı: PayTR · Teslimat: yalnızca <?= e(ayar('teslimat_bolgesi')) ?> · Bu sitedeki marka, metin, fiyat ve görseller temsilidir.</small></div>
</footer>
<script src="assets/site.js"></script>
</body>
</html>
