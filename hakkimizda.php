<?php
require __DIR__ . '/inc/fonk.php';
$baslik = 'Hakkımızda';
$sayfa = 'hakkimizda';
require __DIR__ . '/inc/ust.php';
?>
<div class="ust">
  <div class="kap ust-baslik">
    <h1 class="baslik h1">Hakkımızda</h1>
    <p>Yozgat şehir merkezinde kahvaltılık ve şarküteri ürünleri satan bir aile dükkânıyız.</p>
  </div>
</div>
<main>
  <!-- Bu sayfadaki metinler temsilidir; kendi hikâyenizle değiştirin. -->
  <section>
    <div class="kap bolum yan-yana">
      <img src="assets/img/dukkan.svg" alt="Dükkân (temsili çizim)" width="800" height="600">
      <div class="yazi">
        <h2 class="baslik h2">Hikâyemiz</h2>
        <p>Dükkânımız Yozgat şehir merkezinde, mahallemizin kahvaltı sofralarına yıllardır eşlik ediyor. Tezgâhımızda sucuk, pastırma, salam, peynir ve zeytin çeşitlerini tek tek seçip sizin için paketliyoruz.</p>
        <p>Ürünlerimizi güvendiğimiz üreticilerden alıyoruz. Her ürün hazır paketler hâlinde, etiketinde gramajı ve son tüketim tarihi yazılı olarak satılır.</p>
        <p>Artık siparişinizi internetten verebilir, ödemenizi kartla yapabilir ve ürünlerinizi şehir merkezinde kapınızda teslim alabilirsiniz.</p>
      </div>
    </div>
  </section>
  <section class="zemin-bugday">
    <div class="kap bolum">
      <h2 class="baslik h2 bolum-baslik">Neye önem veriyoruz</h2>
      <div class="dizi">
        <div class="kart"><h3>Seçilmiş ürün</h3><p>Tezgâha koymadan önce her ürünü kendimiz tadıyor, beğenmediğimizi satmıyoruz.</p></div>
        <div class="kart"><h3>Tazelik</h3><p>Ürünleri soğukta saklıyor, siparişinizi teslimattan hemen önce hazırlıyoruz.</p></div>
        <div class="kart"><h3>Kapıya teslimat</h3><p>Yozgat şehir merkezindeki adresinize, seçtiğiniz saat aralığında getiriyoruz.</p></div>
      </div>
    </div>
  </section>
  <section>
    <div class="kap bolum kapanis">
      <div><h2 class="baslik h2">Ürünlerimize göz atmak ister misin?</h2><p class="soluk">Sucuktan zeytine tüm çeşitler tek sayfada.</p></div>
      <a class="dugme bordo" href="index.php#urunler">Ürünleri gör</a>
    </div>
  </section>
</main>
<?php require __DIR__ . '/inc/alt.php'; ?>
