<?php
require __DIR__ . '/inc/fonk.php';
$baslik = 'İletişim';
$sayfa = 'iletisim';
$hata = '';
$f = ['ad' => '', 'telefon' => '', 'eposta' => '', 'konu' => '', 'mesaj' => ''];
$konular = ['Siparişim hakkında', 'Ürün sorusu', 'İade ve değişim', 'Diğer'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    foreach ($f as $a => $_) {
        $f[$a] = post($a);
    }
    if (post('web_sitesi') !== '') {
        yonlendir('iletisim.php'); // gizli alanı dolduran otomatik gönderimleri yok say
    }
    if (mb_strlen($f['ad']) < 3 || !filter_var($f['eposta'], FILTER_VALIDATE_EMAIL) || mb_strlen($f['mesaj']) < 5) {
        $hata = 'Ad soyad, geçerli bir e-posta ve mesajını yaz.';
    } elseif (!deneme_siniri('mesaj')) {
        $hata = 'Kısa sürede çok fazla mesaj gönderildi. Biraz sonra tekrar dene.';
    } else {
        $konu = in_array($f['konu'], $konular, true) ? $f['konu'] : 'Diğer';
        q("INSERT INTO mesajlar (ad, telefon, eposta, konu, mesaj, okundu, olusturma) VALUES (?, ?, ?, ?, ?, 0, ?)",
            [kisalt($f['ad'], 160), kisalt($f['telefon'], 30), kisalt($f['eposta'], 160), $konu, kisalt($f['mesaj'], 3000), simdi()]);
        if (ayar('bildirim_eposta') !== '') {
            eposta_gonder(ayar('bildirim_eposta'), 'Yeni mesaj: ' . $konu, $f['ad'] . ' (' . $f['eposta'] . ' ' . $f['telefon'] . ")\n\n" . $f['mesaj']);
        }
        mesaj_yaz('Mesajın bize ulaştı. En kısa sürede dönüş yapacağız.', 'basari');
        yonlendir('iletisim.php');
    }
}
require __DIR__ . '/inc/ust.php';
?>
<div class="ust">
  <div class="kap ust-baslik">
    <h1 class="baslik h1">İletişim</h1>
    <p>Sorun ya da siparişinle ilgili bir isteğin mi var? Bize yaz ya da ara.</p>
  </div>
</div>
<main>
  <div class="kap iki">
    <div class="yan">
      <div class="ilet"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1C10.7 20 4 13.3 4 5a1 1 0 0 1 1-1z"/></svg><div><b>Telefon</b><span><?= e(ayar('telefon')) ?></span></div></div>
      <div class="ilet"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg><div><b>E-posta</b><span><?= e(ayar('eposta')) ?></span></div></div>
      <div class="ilet"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg><div><b>Adres</b><span><?= e(ayar('adres')) ?></span></div></div>
      <div class="ilet"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><div><b>Çalışma saatleri</b><span><?= e(ayar('calisma_saatleri')) ?></span></div></div>
      <img class="harita" src="assets/img/harita.svg" alt="Dükkânın konumu (temsili harita)" width="800" height="400">
    </div>
    <form class="kutu form" method="post" style="flex:1.3 1 420px; min-width:0">
      <?= csrf_alan() ?>
      <h2>Bize yaz</h2>
      <?php if ($hata): ?><div class="bilgi hata" role="alert"><?= e($hata) ?></div><?php endif; ?>
      <div class="alanlar">
        <div class="alan"><label for="i-ad">Ad Soyad</label><input id="i-ad" name="ad" type="text" autocomplete="name" required value="<?= e($f['ad']) ?>"></div>
        <div class="alan"><label for="i-tel">Telefon</label><input id="i-tel" name="telefon" type="tel" autocomplete="tel" placeholder="05__ ___ __ __" value="<?= e($f['telefon']) ?>"></div>
      </div>
      <div class="alan"><label for="i-eposta">E-posta</label><input id="i-eposta" name="eposta" type="email" autocomplete="email" required value="<?= e($f['eposta']) ?>"></div>
      <div class="alan"><label for="i-konu">Konu</label>
        <select id="i-konu" name="konu"><?php foreach ($konular as $x): ?><option<?= $f['konu'] === $x ? ' selected' : '' ?>><?= e($x) ?></option><?php endforeach; ?></select>
      </div>
      <div class="alan"><label for="i-mesaj">Mesajın</label><textarea id="i-mesaj" name="mesaj" rows="6" required><?= e($f['mesaj']) ?></textarea></div>
      <div style="position:absolute; left:-9999px" aria-hidden="true"><label>Bu alanı boş bırakın <input type="text" name="web_sitesi" tabindex="-1" autocomplete="off"></label></div>
      <button type="submit" class="dugme bordo">Gönder</button>
    </form>
  </div>
</main>
<?php require __DIR__ . '/inc/alt.php'; ?>
