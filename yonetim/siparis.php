<?php
require __DIR__ . '/_ust.php';
require __DIR__ . '/../inc/paytr.php';
$s = satir("SELECT * FROM siparisler WHERE id = ?", [(int) ($_GET['id'] ?? 0)]);
if (!$s) {
    mesaj_yaz('Sipariş bulunamadı.', 'hata');
    yonlendir('index.php');
}
$buSayfa = 'siparis.php?id=' . (int) $s['id'];
$odemeler = satirlar("SELECT * FROM odemeler WHERE siparis_id = ? ORDER BY id", [$s['id']]);
$gercekOdeme = false; // PayTR ile gerçekten para alındı mı?
foreach ($odemeler as $o) {
    if ($o['tur'] === 'paytr' && $o['sonuc'] === 'basarili') {
        $gercekOdeme = true;
    }
}

/* Ret ve iptalde iadeyi başlatır; sonucu mesaj olarak yazar. */
function iade_baslat(array $s, bool $gercekOdeme): void
{
    if (!$gercekOdeme) {
        q("UPDATE siparisler SET iade = 'yok' WHERE id = ?", [$s['id']]);
        mesaj_yaz(kapida_mi($s) ? 'Kapıda ödemeli sipariş olduğu için iade gerekmiyor; müşteriden para alınmadı.' : 'Bu sipariş deneme ödemesiyle alındığı için iade gerekmiyor.');
        return;
    }
    $sonuc = paytr_aktif() ? paytr_iade($s) : 'PayTR bilgileri girilmemiş.';
    if ($sonuc === true) {
        q("UPDATE siparisler SET iade = 'yapildi' WHERE id = ?", [$s['id']]);
        q("INSERT INTO odemeler (siparis_id, tur, tutar, sonuc, aciklama, olusturma) VALUES (?, 'iade', ?, 'basarili', 'PayTR iade', ?)", [$s['id'], $s['toplam'], simdi()]);
        mesaj_yaz('Tutar PayTR üzerinden iade edildi.', 'basari');
    } else {
        q("UPDATE siparisler SET iade = 'bekliyor' WHERE id = ?", [$s['id']]);
        q("INSERT INTO odemeler (siparis_id, tur, tutar, sonuc, aciklama, olusturma) VALUES (?, 'iade', ?, 'basarisiz', ?, ?)", [$s['id'], $s['toplam'], kisalt((string) $sonuc, 500), simdi()]);
        mesaj_yaz('Otomatik iade yapılamadı: ' . $sonuc . ' İadeyi PayTR mağaza panelinden elle yapıp bu sayfada "İade yapıldı" olarak işaretleyin.', 'hata');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    $islem = post('islem');
    $d = $s['durum'];

    if ($islem === 'onayla' && $d === 'onay_bekliyor') {
        siparis_durum_yaz((int) $s['id'], 'onaylandi');
        eposta_gonder($s['eposta'], 'Siparişiniz onaylandı (' . $s['no'] . ')',
            "Merhaba " . $s['ad'] . ",\n\nSiparişiniz onaylandı ve hazırlanıyor.\n\n" . siparis_ozeti_metni($s));
        mesaj_yaz('Sipariş onaylandı, müşteriye e-posta gönderildi.', 'basari');
    } elseif (($islem === 'reddet' && $d === 'onay_bekliyor') || ($islem === 'iptal' && in_array($d, ['onaylandi', 'yolda'], true))) {
        $neden = post('neden');
        if (mb_strlen($neden) < 3) {
            mesaj_yaz('Müşteriye iletilecek nedeni yazın.', 'hata');
            yonlendir($buSayfa);
        }
        stok_geri_ekle($s);
        siparis_durum_yaz((int) $s['id'], $islem === 'reddet' ? 'reddedildi' : 'iptal', ['ret_nedeni' => kisalt($neden, 500)]);
        iade_baslat($s, $gercekOdeme);
        eposta_gonder($s['eposta'], ($islem === 'reddet' ? 'Siparişiniz onaylanamadı' : 'Siparişiniz iptal edildi') . ' (' . $s['no'] . ')',
            "Merhaba " . $s['ad'] . ",\n\n" . $s['no'] . " numaralı siparişiniz " . ($islem === 'reddet' ? 'onaylanamadı' : 'iptal edildi') . ".\nNeden: " . $neden
            . (kapida_mi($s) ? "\n\nSizden herhangi bir ödeme alınmamıştır." : "\n\nÖdediğiniz " . tl($s['toplam']) . " kartınıza iade edilecektir. İadenin kartınıza yansıması bankanıza göre birkaç iş günü sürebilir."));
        mesaj_yaz($islem === 'reddet' ? 'Sipariş reddedildi, stok geri eklendi.' : 'Sipariş iptal edildi, stok geri eklendi.');
    } elseif ($islem === 'yolda' && $d === 'onaylandi') {
        siparis_durum_yaz((int) $s['id'], 'yolda');
        eposta_gonder($s['eposta'], 'Siparişiniz yola çıktı (' . $s['no'] . ')', "Merhaba " . $s['ad'] . ",\n\nSiparişiniz yola çıktı. Teslimat: " . str_replace('-', ' – ', $s['teslimat_saati']) . "\n\n" . siparis_adresi($s));
        mesaj_yaz('Sipariş "yolda" olarak işaretlendi.', 'basari');
    } elseif ($islem === 'teslim' && in_array($d, ['onaylandi', 'yolda'], true)) {
        siparis_durum_yaz((int) $s['id'], 'teslim_edildi');
        mesaj_yaz('Sipariş teslim edildi olarak işaretlendi.', 'basari');
    } elseif ($islem === 'iade_dene' && $s['iade'] === 'bekliyor') {
        iade_baslat($s, $gercekOdeme);
    } elseif ($islem === 'iade_tamam' && $s['iade'] === 'bekliyor') {
        q("UPDATE siparisler SET iade = 'yapildi' WHERE id = ?", [$s['id']]);
        mesaj_yaz('İade yapıldı olarak işaretlendi.', 'basari');
    } else {
        mesaj_yaz('Bu işlem siparişin şu anki durumunda yapılamaz.', 'hata');
    }
    yonlendir($buSayfa);
}

$kalemler = siparis_kalemleri((int) $s['id']);
y_ust('Sipariş ' . $s['no'], 'siparisler');
?>
<p style="margin:-8px 0 16px"><a class="baglanti" href="index.php">← Siparişlere dön</a></p>
<div class="y-iki">
  <div style="flex:2 1 520px; display:flex; flex-direction:column; gap:18px">
    <section class="kutu">
      <h2>Durum: <?= durum_rozet($s['durum']) ?><?php if ($s['iade'] === 'bekliyor'): ?> <span class="rozet kotu">İade bekliyor</span><?php elseif ($s['iade'] === 'yapildi'): ?> <span class="rozet notr">İade yapıldı</span><?php endif; ?></h2>
      <?php if ($s['ret_nedeni']): ?><div class="bilgi hata"><b>Neden:</b> <?= e($s['ret_nedeni']) ?></div><?php endif; ?>

      <?php if ($s['durum'] === 'onay_bekliyor'): ?>
        <p class="soluk"><?= kapida_mi($s) ? 'Kapıda ödemeli sipariş: ödeme teslimatta alınacak.' : 'Ödeme alındı.' ?> Siparişi onaylayın ya da reddedin; iki durumda da müşteriye e-posta gider.</p>
        <form method="post" class="satir-form"><?= csrf_alan() ?><input type="hidden" name="islem" value="onayla"><button class="dugme bordo" type="submit">Onayla</button></form>
        <form method="post" class="form" style="border-top:1px solid var(--cizgi); padding-top:14px">
          <?= csrf_alan() ?><input type="hidden" name="islem" value="reddet">
          <div class="alan"><label for="neden">Ret nedeni (müşteriye iletilir)</label><input id="neden" name="neden" type="text" required placeholder="Örn. ürün elimizde kalmadı"></div>
          <button class="dugme cizgi" type="submit" style="align-self:flex-start" data-onay="Sipariş reddedilecek, stok geri eklenecek ve iade başlatılacak. Emin misiniz?"><?= kapida_mi($s) ? 'Reddet' : 'Reddet ve iade et' ?></button>
        </form>
      <?php elseif ($s['durum'] === 'onaylandi' || $s['durum'] === 'yolda'): ?>
        <div class="dugmeler">
          <?php if ($s['durum'] === 'onaylandi'): ?><form method="post" class="satir-form"><?= csrf_alan() ?><input type="hidden" name="islem" value="yolda"><button class="dugme bordo" type="submit">Yola çıktı</button></form><?php endif; ?>
          <form method="post" class="satir-form"><?= csrf_alan() ?><input type="hidden" name="islem" value="teslim"><button class="dugme <?= $s['durum'] === 'yolda' ? 'bordo' : 'cizgi' ?>" type="submit">Teslim edildi</button></form>
        </div>
        <form method="post" class="form" style="border-top:1px solid var(--cizgi); padding-top:14px">
          <?= csrf_alan() ?><input type="hidden" name="islem" value="iptal">
          <div class="alan"><label for="neden">İptal nedeni (müşteriye iletilir)</label><input id="neden" name="neden" type="text" required></div>
          <button class="dugme cizgi" type="submit" style="align-self:flex-start" data-onay="Sipariş iptal edilecek, stok geri eklenecek ve iade başlatılacak. Emin misiniz?"><?= kapida_mi($s) ? 'İptal et' : 'İptal et ve iade et' ?></button>
        </form>
      <?php elseif (in_array($s['durum'], ['odeme_bekliyor', 'odeme_basarisiz'], true)): ?>
        <p class="soluk">Müşteri ödemeyi tamamlamadı. Ödeme gelene kadar yapılacak bir işlem yok; stok düşülmedi.</p>
      <?php endif; ?>

      <?php if ($s['iade'] === 'bekliyor'): ?>
        <div class="uyari">Bu siparişin tutarı (<?= tl($s['toplam']) ?>) henüz iade edilmedi.</div>
        <div class="dugmeler">
          <form method="post" class="satir-form"><?= csrf_alan() ?><input type="hidden" name="islem" value="iade_dene"><button class="dugme cizgi" type="submit">PayTR iadesini tekrar dene</button></form>
          <form method="post" class="satir-form"><?= csrf_alan() ?><input type="hidden" name="islem" value="iade_tamam"><button class="dugme cizgi" type="submit" data-onay="İadeyi PayTR panelinden elle yaptığınızı onaylıyor musunuz?">İade yapıldı olarak işaretle</button></form>
        </div>
      <?php endif; ?>
    </section>

    <section class="kutu">
      <h2>Ürünler</h2>
      <div class="tablo-kap"><table class="tablo">
        <thead><tr><th>Ürün</th><th>Paket</th><th class="sag">Adet</th><th class="sag">Fiyat</th><th class="sag">Tutar</th></tr></thead>
        <tbody>
        <?php foreach ($kalemler as $k): ?>
          <tr><td><?= e($k['urun_adi']) ?></td><td><?= e($k['paket']) ?></td><td class="sag"><?= (int) $k['adet'] ?></td><td class="sag"><?= tl($k['fiyat']) ?></td><td class="sag"><?= tl($k['fiyat'] * $k['adet']) ?></td></tr>
        <?php endforeach; ?>
          <tr><td colspan="4" class="sag">Ara toplam</td><td class="sag"><?= tl($s['ara_toplam']) ?></td></tr>
          <tr><td colspan="4" class="sag">Teslimat</td><td class="sag"><?= $s['teslimat_ucreti'] ? tl($s['teslimat_ucreti']) : 'Ücretsiz' ?></td></tr>
          <tr><td colspan="4" class="sag"><b>Toplam</b></td><td class="sag"><b><?= tl($s['toplam']) ?></b></td></tr>
        </tbody>
      </table></div>
    </section>
  </div>

  <div style="display:flex; flex-direction:column; gap:18px">
    <section class="kutu">
      <h2>Teslimat</h2>
      <div class="satirlar" style="margin-top:0">
        <div class="satir"><b>Zaman</b><span><?= e(tarih_yaz($s['teslimat_tarihi'], false)) ?> <?= e(gun_adi($s['teslimat_tarihi'])) ?>, <?= e(str_replace('-', ' – ', $s['teslimat_saati'])) ?></span></div>
        <div class="satir"><b>Alıcı</b><span><?= e($s['ad']) ?><?= $s['musteri_id'] ? ' (üye)' : ' (misafir)' ?></span></div>
        <div class="satir"><b>Telefon</b><span><?= e($s['telefon']) ?></span></div>
        <div class="satir"><b>E-posta</b><span><?= e($s['eposta']) ?></span></div>
        <div class="satir"><b>Adres</b><span><?= e($s['adres']) ?></span></div>
        <?php if ($s['siparis_notu'] !== ''): ?><div class="satir"><b>Müşteri notu</b><span><?= e($s['siparis_notu']) ?></span></div><?php endif; ?>
        <div class="satir"><b>Sipariş tarihi</b><span><?= e(tarih_yaz($s['olusturma'])) ?></span></div>
      </div>
    </section>
    <?php if (kapida_mi($s)): ?>
    <section class="kutu">
      <h2>Kapıda ödeme</h2>
      <div class="uyari"><?= $s['durum'] === 'teslim_edildi' ? 'Teslimatta tahsil edildi: ' : 'Teslimatta tahsil edilecek tutar: ' ?><b><?= tl($s['toplam']) ?></b></div>
    </section>
    <?php endif; ?>
    <section class="kutu">
      <h2>Müşterinin gördüğü takip</h2>
      <?= siparis_cizelgesi($s) ?>
    </section>
    <section class="kutu">
      <h2>Ödeme kayıtları</h2>
      <?php if (!$odemeler): ?><p class="soluk">Henüz ödeme kaydı yok.</p><?php endif; ?>
      <?php foreach ($odemeler as $o): ?>
        <div class="satir" style="border-bottom:1px solid var(--cizgi)"><b><?= e(tarih_yaz($o['olusturma'])) ?></b><span><?= e(['paytr' => 'PayTR', 'deneme' => 'Deneme', 'iade' => 'İade'][$o['tur']] ?? $o['tur']) ?> · <?= tl($o['tutar']) ?> · <?= $o['sonuc'] === 'basarili' ? 'başarılı' : 'başarısız' ?><?= $o['aciklama'] ? ' · ' . e($o['aciklama']) : '' ?></span></div>
      <?php endforeach; ?>
    </section>
  </div>
</div>
<?php y_alt(); ?>
