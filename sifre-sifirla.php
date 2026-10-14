<?php
require __DIR__ . '/inc/fonk.php';
$baslik = 'Şifre sıfırlama';
$kod = (string) ($_GET['kod'] ?? $_POST['kod'] ?? '');
$hata = '';
$gonderildi = false;

/* Koda karşılık gelen, süresi dolmamış müşteriyi bulur. Veritabanında kodun kendisi değil özeti saklanır. */
function sifirlama_musterisi(string $kod): ?array
{
    if (!preg_match('/^[a-f0-9]{48}$/', $kod)) {
        return null;
    }
    return satir("SELECT * FROM musteriler WHERE sifirlama_kodu = ? AND sifirlama_bitis > ?", [hash('sha256', $kod), simdi()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    if (post('islem') === 'iste') {
        if (!deneme_siniri('sifirla')) {
            $hata = 'Çok fazla istek yapıldı. 10 dakika sonra tekrar dene.';
        } else {
            $m = satir("SELECT * FROM musteriler WHERE eposta = ?", [mb_strtolower(post('eposta'))]);
            if ($m) {
                $yeniKod = bin2hex(random_bytes(24));
                q("UPDATE musteriler SET sifirlama_kodu = ?, sifirlama_bitis = ? WHERE id = ?",
                    [hash('sha256', $yeniKod), date('Y-m-d H:i:s', time() + 3600), $m['id']]);
                eposta_gonder($m['eposta'], 'Şifre sıfırlama',
                    "Merhaba " . $m['ad'] . ",\n\nŞifrenizi yenilemek için aşağıdaki bağlantıyı açın. Bağlantı 1 saat geçerlidir.\n\n"
                    . site_adresi() . '/sifre-sifirla.php?kod=' . $yeniKod
                    . "\n\nBu isteği siz yapmadıysanız bu e-postayı dikkate almayın; şifreniz değişmez.");
            }
            // Hesap olsa da olmasa da aynı mesaj gösterilir; böylece kimin üye olduğu anlaşılmaz.
            $gonderildi = true;
        }
    }
    if (post('islem') === 'yenile') {
        $m = sifirlama_musterisi($kod);
        $yeni = (string) ($_POST['yeni'] ?? '');
        if (!$m) {
            $hata = 'Bu bağlantının süresi dolmuş ya da daha önce kullanılmış. Yeni bir bağlantı iste.';
            $kod = '';
        } elseif (strlen($yeni) < 8 || $yeni !== (string) ($_POST['yeni2'] ?? '')) {
            $hata = 'Şifre en az 8 karakter olmalı ve iki alanda aynı yazılmalı.';
        } else {
            q("UPDATE musteriler SET sifre = ?, sifirlama_kodu = NULL, sifirlama_bitis = NULL WHERE id = ?", [password_hash($yeni, PASSWORD_DEFAULT), $m['id']]);
            mesaj_yaz('Şifren değiştirildi. Yeni şifrenle giriş yapabilirsin.', 'basari');
            yonlendir('giris.php');
        }
    }
} elseif ($kod !== '' && !sifirlama_musterisi($kod)) {
    $hata = 'Bu bağlantının süresi dolmuş ya da daha önce kullanılmış. Yeni bir bağlantı iste.';
    $kod = '';
}
require __DIR__ . '/inc/ust.php';
?>
<div class="ust">
  <div class="kap ust-baslik">
    <h1 class="baslik h1">Şifre sıfırlama</h1>
    <p>E-posta adresine göndereceğimiz bağlantıyla yeni bir şifre belirle.</p>
  </div>
</div>
<main>
  <div class="kap iki">
    <?php if ($kod !== ''): ?>
      <form class="kutu form" method="post" action="sifre-sifirla.php" style="flex:0 1 460px">
        <?= csrf_alan() ?><input type="hidden" name="islem" value="yenile"><input type="hidden" name="kod" value="<?= e($kod) ?>">
        <h2>Yeni şifreni belirle</h2>
        <?php if ($hata): ?><div class="bilgi hata" role="alert"><?= e($hata) ?></div><?php endif; ?>
        <div class="alan"><label for="y-yeni">Yeni şifre</label><input id="y-yeni" name="yeni" type="password" autocomplete="new-password" minlength="8" required aria-describedby="y-not"><small id="y-not">En az 8 karakter.</small></div>
        <div class="alan"><label for="y-yeni2">Yeni şifre (tekrar)</label><input id="y-yeni2" name="yeni2" type="password" autocomplete="new-password" minlength="8" required></div>
        <button type="submit" class="dugme bordo">Şifreyi değiştir</button>
      </form>
    <?php elseif ($gonderildi): ?>
      <div class="kutu" style="flex:0 1 520px">
        <h2>E-postanı kontrol et</h2>
        <p>Bu adresle kayıtlı bir hesap varsa şifre sıfırlama bağlantısını gönderdik. Bağlantı 1 saat geçerli.</p>
        <p class="soluk">E-posta gelmediyse gereksiz (spam) klasörüne bak ya da bizi ara: <?= e(ayar('telefon')) ?></p>
        <a class="dugme cizgi" style="align-self:flex-start" href="giris.php">Girişe dön</a>
      </div>
    <?php else: ?>
      <form class="kutu form" method="post" action="sifre-sifirla.php" style="flex:0 1 460px">
        <?= csrf_alan() ?><input type="hidden" name="islem" value="iste">
        <h2>Şifremi unuttum</h2>
        <?php if ($hata): ?><div class="bilgi hata" role="alert"><?= e($hata) ?></div><?php endif; ?>
        <div class="alan"><label for="y-eposta">Kayıtlı e-posta adresin</label><input id="y-eposta" name="eposta" type="email" autocomplete="email" required></div>
        <button type="submit" class="dugme bordo">Sıfırlama bağlantısı gönder</button>
        <a class="baglanti" href="giris.php" style="align-self:flex-start">Girişe dön</a>
      </form>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/inc/alt.php'; ?>
