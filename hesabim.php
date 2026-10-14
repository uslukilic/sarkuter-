<?php
require __DIR__ . '/inc/fonk.php';
$m = musteri();
if (!$m) {
    yonlendir('giris.php?don=hesabim.php');
}
$baslik = 'Hesabım';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    if (post('islem') === 'bilgi') {
        if (mb_strlen(post('ad')) < 2 || mb_strlen(post('soyad')) < 2 || strlen(preg_replace('/\D/', '', post('telefon'))) < 10) {
            mesaj_yaz('Ad, soyad ve telefonu eksiksiz yaz.', 'hata');
        } else {
            q("UPDATE musteriler SET ad = ?, soyad = ?, telefon = ?, adres = ?, kampanya_izni = ? WHERE id = ?",
                [kisalt(post('ad'), 80), kisalt(post('soyad'), 80), kisalt(post('telefon'), 30), kisalt(post('adres'), 600), post('ileti') === '1' ? 1 : 0, $m['id']]);
            mesaj_yaz('Bilgilerin güncellendi.', 'basari');
        }
    }
    if (post('islem') === 'sifre') {
        $yeni = (string) ($_POST['yeni'] ?? '');
        if (!password_verify((string) ($_POST['eski'] ?? ''), $m['sifre'])) {
            mesaj_yaz('Mevcut şifre hatalı.', 'hata');
        } elseif (strlen($yeni) < 8 || $yeni !== (string) ($_POST['yeni2'] ?? '')) {
            mesaj_yaz('Yeni şifre en az 8 karakter olmalı ve iki alanda aynı yazılmalı.', 'hata');
        } else {
            q("UPDATE musteriler SET sifre = ? WHERE id = ?", [password_hash($yeni, PASSWORD_DEFAULT), $m['id']]);
            mesaj_yaz('Şifren değiştirildi.', 'basari');
        }
    }
    yonlendir('hesabim.php');
}
$siparisler = satirlar("SELECT * FROM siparisler WHERE musteri_id = ? ORDER BY id DESC", [$m['id']]);
require __DIR__ . '/inc/ust.php';
?>
<div class="ust">
  <div class="kap ust-baslik">
    <h1 class="baslik h1">Hesabım</h1>
    <p>Merhaba <?= e($m['ad']) ?>. Siparişlerini ve bilgilerini buradan yönetebilirsin.</p>
  </div>
</div>
<main>
  <div class="kap iki">
    <div class="genis">
      <section class="kutu">
        <h2>Siparişlerim</h2>
        <?php if (!$siparisler): ?>
          <p class="soluk">Henüz siparişin yok.</p>
          <a class="dugme bordo" style="align-self:flex-start" href="index.php#urunler">Ürünleri gör</a>
        <?php else: ?>
        <div class="tablo-kap"><table class="tablo">
          <thead><tr><th>Sipariş</th><th>Tarih</th><th>Teslimat</th><th>Durum</th><th class="sag">Toplam</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($siparisler as $s): ?>
            <tr>
              <td><a class="baglanti" style="min-height:0" href="siparis.php?no=<?= e(urlencode($s['no'])) ?>"><?= e($s['no']) ?></a></td>
              <td><?= e(tarih_yaz($s['olusturma'])) ?></td>
              <td><?= e(tarih_yaz($s['teslimat_tarihi'], false)) ?> <?= e(str_replace('-', '–', $s['teslimat_saati'])) ?></td>
              <td><?= durum_rozet($s['durum']) ?></td>
              <td class="sag"><?= tl($s['toplam']) ?></td>
              <td><a class="dugme kucuk cizgi" href="siparis.php?no=<?= e(urlencode($s['no'])) ?>">Takip et</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
        <?php endif; ?>
      </section>
    </div>
    <div class="yan">
      <form class="kutu form" method="post">
        <?= csrf_alan() ?><input type="hidden" name="islem" value="bilgi">
        <h2>Bilgilerim</h2>
        <div class="alanlar">
          <div class="alan"><label for="h-ad">Ad</label><input id="h-ad" name="ad" type="text" required value="<?= e($m['ad']) ?>"></div>
          <div class="alan"><label for="h-soyad">Soyad</label><input id="h-soyad" name="soyad" type="text" required value="<?= e($m['soyad']) ?>"></div>
        </div>
        <div class="alan"><label for="h-eposta">E-posta</label><input id="h-eposta" type="email" value="<?= e($m['eposta']) ?>" readonly></div>
        <div class="alan"><label for="h-tel">Telefon</label><input id="h-tel" name="telefon" type="tel" required value="<?= e($m['telefon']) ?>"></div>
        <div class="alan"><label for="h-adres">Teslimat adresi (Yozgat şehir merkezi)</label><textarea id="h-adres" name="adres" rows="3"><?= e($m['adres']) ?></textarea></div>
        <label class="onay" for="h-ileti"><input id="h-ileti" name="ileti" value="1" type="checkbox"<?= $m['kampanya_izni'] ? ' checked' : '' ?>><span>Kampanyalardan haberdar olmak istiyorum.</span></label>
        <button type="submit" class="dugme bordo">Kaydet</button>
      </form>
      <form class="kutu form" method="post">
        <?= csrf_alan() ?><input type="hidden" name="islem" value="sifre">
        <h2>Şifre değiştir</h2>
        <div class="alan"><label for="s-eski">Mevcut şifre</label><input id="s-eski" name="eski" type="password" autocomplete="current-password" required></div>
        <div class="alan"><label for="s-yeni">Yeni şifre</label><input id="s-yeni" name="yeni" type="password" autocomplete="new-password" minlength="8" required></div>
        <div class="alan"><label for="s-yeni2">Yeni şifre (tekrar)</label><input id="s-yeni2" name="yeni2" type="password" autocomplete="new-password" minlength="8" required></div>
        <button type="submit" class="dugme cizgi">Şifreyi değiştir</button>
      </form>
    </div>
  </div>
</main>
<?php require __DIR__ . '/inc/alt.php'; ?>
