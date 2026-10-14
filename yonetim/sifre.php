<?php
require __DIR__ . '/_ust.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    $ad = post('kullanici');
    $yeni = (string) ($_POST['yeni'] ?? '');
    if (!password_verify((string) ($_POST['eski'] ?? ''), $__y['sifre'])) {
        mesaj_yaz('Mevcut şifre hatalı.', 'hata');
    } elseif (!preg_match('/^[A-Za-z0-9._@-]{3,60}$/', $ad)) {
        mesaj_yaz('Kullanıcı adı 3–60 karakter olmalı; harf, rakam, nokta, tire, alt çizgi ve @ kullanılabilir.', 'hata');
    } elseif ($ad !== $__y['kullanici_adi'] && deger("SELECT COUNT(*) FROM yoneticiler WHERE kullanici_adi = ?", [$ad])) {
        mesaj_yaz('Bu kullanıcı adı kullanılıyor.', 'hata');
    } elseif ($yeni !== '' && (strlen($yeni) < 10 || $yeni !== (string) ($_POST['yeni2'] ?? ''))) {
        mesaj_yaz('Yeni şifre en az 10 karakter olmalı ve iki alanda aynı yazılmalı.', 'hata');
    } else {
        q("UPDATE yoneticiler SET kullanici_adi = ? WHERE id = ?", [$ad, $__y['id']]);
        if ($yeni !== '') {
            q("UPDATE yoneticiler SET sifre = ? WHERE id = ?", [password_hash($yeni, PASSWORD_DEFAULT), $__y['id']]);
        }
        mesaj_yaz('Giriş bilgileri güncellendi.', 'basari');
    }
    yonlendir('sifre.php');
}
y_ust('Giriş bilgilerim', '');
?>
<form class="kutu form" method="post" style="max-width:460px">
  <?= csrf_alan() ?>
  <div class="alan"><label for="s-kullanici">Kullanıcı adı</label><input id="s-kullanici" name="kullanici" type="text" autocomplete="username" required value="<?= e($__y['kullanici_adi']) ?>"></div>
  <div class="alan"><label for="s-eski">Mevcut şifre</label><input id="s-eski" name="eski" type="password" autocomplete="current-password" required></div>
  <div class="alan"><label for="s-yeni">Yeni şifre</label><input id="s-yeni" name="yeni" type="password" autocomplete="new-password" minlength="10"><small>En az 10 karakter. Değiştirmek istemiyorsanız boş bırakın.</small></div>
  <div class="alan"><label for="s-yeni2">Yeni şifre (tekrar)</label><input id="s-yeni2" name="yeni2" type="password" autocomplete="new-password"></div>
  <button type="submit" class="dugme bordo">Kaydet</button>
</form>
<?php y_alt(); ?>
