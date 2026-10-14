<?php
require __DIR__ . '/inc/fonk.php';
$baslik = 'Giriş Yap / Kayıt Ol';
$sayfa = 'giris';
$don = (string) ($_GET['don'] ?? $_POST['don'] ?? '');
$don = preg_match('~^[a-z0-9_-]+\.php$~i', $don) ? $don : 'hesabim.php';
if (musteri()) {
    yonlendir($don);
}
$girisHata = '';
$kayitHatalar = [];
$k = ['ad' => '', 'soyad' => '', 'eposta' => '', 'telefon' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    if (post('islem') === 'giris') {
        if (!deneme_siniri('giris')) {
            $girisHata = 'Çok fazla deneme yapıldı. 10 dakika sonra tekrar dene.';
        } else {
            $sifreGirilen = (string) ($_POST['sifre'] ?? '');
            // Yönetici de aynı formdan girer: kullanıcı adı yöneticiye aitse yönetim paneline gider.
            $y = satir("SELECT * FROM yoneticiler WHERE kullanici_adi = ?", [post('eposta')]);
            if ($y && password_verify($sifreGirilen, $y['sifre'])) {
                session_regenerate_id(true);
                $_SESSION['yonetici_id'] = $y['id'];
                unset($_SESSION['deneme_giris']);
                yonlendir('yonetim/index.php');
            }
            $m = satir("SELECT * FROM musteriler WHERE eposta = ?", [mb_strtolower(post('eposta'))]);
            if ($m && password_verify($sifreGirilen, $m['sifre'])) {
                session_regenerate_id(true);
                $_SESSION['musteri_id'] = $m['id'];
                unset($_SESSION['deneme_giris']);
                yonlendir($don);
            }
            usleep(400000);
            $girisHata = 'Giriş bilgileri hatalı.';
        }
    }
    if (post('islem') === 'kayit') {
        foreach ($k as $a => $_) {
            $k[$a] = post($a);
        }
        $k['eposta'] = mb_strtolower($k['eposta']);
        $sifre = (string) ($_POST['sifre'] ?? '');
        if (mb_strlen($k['ad']) < 2 || mb_strlen($k['soyad']) < 2) {
            $kayitHatalar[] = 'Ad ve soyad yaz.';
        }
        if (!filter_var($k['eposta'], FILTER_VALIDATE_EMAIL)) {
            $kayitHatalar[] = 'Geçerli bir e-posta adresi yaz.';
        } elseif (deger("SELECT COUNT(*) FROM musteriler WHERE eposta = ?", [$k['eposta']])) {
            $kayitHatalar[] = 'Bu e-posta ile kayıtlı bir hesap var. Giriş yapmayı dene.';
        }
        if (strlen(preg_replace('/\D/', '', $k['telefon'])) < 10) {
            $kayitHatalar[] = 'Telefon numaranı başında 0 ile, 11 haneli yaz.';
        }
        if (strlen($sifre) < 8) {
            $kayitHatalar[] = 'Şifre en az 8 karakter olmalı.';
        } elseif ($sifre !== (string) ($_POST['sifre2'] ?? '')) {
            $kayitHatalar[] = 'Şifreler aynı değil.';
        }
        if (post('kvkk') !== '1') {
            $kayitHatalar[] = 'KVKK Aydınlatma Metni onayını işaretle.';
        }
        if (!$kayitHatalar) {
            $id = ekle("INSERT INTO musteriler (ad, soyad, eposta, telefon, sifre, adres, kampanya_izni, olusturma) VALUES (?, ?, ?, ?, ?, '', ?, ?)",
                [kisalt($k['ad'], 80), kisalt($k['soyad'], 80), kisalt($k['eposta'], 160), kisalt($k['telefon'], 30),
                    password_hash($sifre, PASSWORD_DEFAULT), post('ileti') === '1' ? 1 : 0, simdi()]);
            session_regenerate_id(true);
            $_SESSION['musteri_id'] = $id;
            eposta_gonder($k['eposta'], 'Hoş geldiniz', "Merhaba " . $k['ad'] . ",\n\nHesabınız oluşturuldu. Siparişlerinizi \"Hesabım\" sayfasından takip edebilirsiniz.");
            mesaj_yaz('Hesabın oluşturuldu, hoş geldin.', 'basari');
            yonlendir($don);
        }
    }
}
require __DIR__ . '/inc/ust.php';
?>
<div class="ust">
  <div class="kap ust-baslik">
    <h1 class="baslik h1">Hesabın</h1>
    <p>Giriş yap ya da birkaç bilgiyle yeni hesap oluştur.</p>
  </div>
</div>
<main>
  <div class="kap iki">
    <form class="kutu form" method="post" style="flex:1 1 320px; min-width:0">
      <?= csrf_alan() ?><input type="hidden" name="islem" value="giris"><input type="hidden" name="don" value="<?= e($don) ?>">
      <h2>Giriş Yap</h2>
      <p class="soluk">Daha önce kayıt olduysan e-posta ve şifrenle gir.</p>
      <?php if ($girisHata): ?><div class="bilgi hata" role="alert"><?= e($girisHata) ?></div><?php endif; ?>
      <div class="alan"><label for="g-eposta">E-posta ya da kullanıcı adı</label><input id="g-eposta" name="eposta" type="text" inputmode="email" autocapitalize="none" spellcheck="false" autocomplete="username" maxlength="160" required value="<?= e(post('islem') === 'giris' ? post('eposta') : '') ?>"></div>
      <div class="alan"><label for="g-sifre">Şifre</label><input id="g-sifre" name="sifre" type="password" autocomplete="current-password" required></div>
      <button type="submit" class="dugme koyu">Giriş Yap</button>
      <a class="baglanti" href="sifre-sifirla.php" style="align-self:flex-start">Şifremi unuttum</a>
      <div class="ayrac">
        <b>Üye olmak istemiyor musun?</b>
        <a class="dugme cizgi" href="sepet.php">Misafir olarak devam et</a>
      </div>
    </form>

    <form class="kutu form" method="post" action="giris.php#kayit" style="flex:1.4 1 420px; min-width:0; scroll-margin-top:24px">
      <?= csrf_alan() ?><input type="hidden" name="islem" value="kayit"><input type="hidden" name="don" value="<?= e($don) ?>">
      <h2 id="kayit">Kayıt Ol</h2>
      <p class="soluk">Yeni müşteriysen birkaç bilgiyle hesabını oluştur.</p>
      <?php if ($kayitHatalar): ?><div class="bilgi hata" role="alert"><ul style="margin:0; padding-left:18px"><?php foreach ($kayitHatalar as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <div class="alanlar">
        <div class="alan"><label for="k-ad">Ad</label><input id="k-ad" name="ad" type="text" autocomplete="given-name" required value="<?= e($k['ad']) ?>"></div>
        <div class="alan"><label for="k-soyad">Soyad</label><input id="k-soyad" name="soyad" type="text" autocomplete="family-name" required value="<?= e($k['soyad']) ?>"></div>
      </div>
      <div class="alanlar">
        <div class="alan"><label for="k-eposta">E-posta</label><input id="k-eposta" name="eposta" type="email" autocomplete="email" required value="<?= e($k['eposta']) ?>"></div>
        <div class="alan"><label for="k-telefon">Telefon</label><input id="k-telefon" name="telefon" type="tel" autocomplete="tel" placeholder="05__ ___ __ __" required value="<?= e($k['telefon']) ?>"></div>
      </div>
      <div class="alanlar">
        <div class="alan"><label for="k-sifre">Şifre</label><input id="k-sifre" name="sifre" type="password" autocomplete="new-password" minlength="8" required aria-describedby="k-sifre-not"><small id="k-sifre-not">En az 8 karakter.</small></div>
        <div class="alan"><label for="k-sifre2">Şifre (tekrar)</label><input id="k-sifre2" name="sifre2" type="password" autocomplete="new-password" minlength="8" required></div>
      </div>
      <label class="onay" for="k-kvkk"><input id="k-kvkk" name="kvkk" value="1" type="checkbox" required><span><a class="baglanti" style="min-height:0;display:inline" href="sayfa.php?s=kvkk" target="_blank" rel="noopener">KVKK Aydınlatma Metni'ni</a> okudum, üyelik koşullarını kabul ediyorum.</span></label>
      <label class="onay" for="k-ileti"><input id="k-ileti" name="ileti" value="1" type="checkbox"><span>Kampanyalardan e-posta ve SMS ile haberdar olmak istiyorum. (isteğe bağlı)</span></label>
      <button type="submit" class="dugme bordo">Kayıt Ol</button>
    </form>
  </div>
</main>
<?php require __DIR__ . '/inc/alt.php'; ?>
