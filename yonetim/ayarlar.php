<?php
require __DIR__ . '/_ust.php';
require __DIR__ . '/../inc/paytr.php';
$metinAlanlari = ['marka', 'telefon', 'eposta', 'adres', 'calisma_saatleri', 'teslimat_bolgesi', 'bildirim_eposta', 'sozlesme_metni', 'iade_metni', 'kvkk_metni'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    if (mb_strlen(post('marka')) < 2) {
        mesaj_yaz('Marka adını yazın.', 'hata');
    } elseif (post('bildirim_eposta') !== '' && !filter_var(post('bildirim_eposta'), FILTER_VALIDATE_EMAIL)) {
        mesaj_yaz('Bildirim e-postası geçerli bir adres olmalı.', 'hata');
    } else {
        foreach ($metinAlanlari as $a) {
            ayar_yaz($a, kisalt(post($a), 60000));
        }
        ayar_yaz('teslimat_ucreti', (string) max(0, kurusa(post('teslimat_ucreti'))));
        ayar_yaz('ucretsiz_teslimat', (string) max(0, kurusa(post('ucretsiz_teslimat'))));
        ayar_yaz('stok_uyari', (string) max(0, (int) post('stok_uyari')));
        ayar_yaz('kapida_odeme', post('kapida_odeme') === '1' ? '1' : '0');
        ayar_yaz('odeme_suresi_dk', (string) max(0, min(1440, (int) post('odeme_suresi_dk'))));
        mesaj_yaz('Ayarlar kaydedildi.', 'basari');
    }
    yonlendir('ayarlar.php');
}
$para = fn(string $a) => number_format(((int) ayar($a)) / 100, 2, ',', '');
y_ust('Ayarlar', 'ayarlar');
?>
<form method="post" class="y-iki">
  <?= csrf_alan() ?>
  <section class="kutu form">
    <h2>İşletme bilgileri</h2>
    <div class="alan"><label for="a-marka">Marka adı</label><input id="a-marka" name="marka" type="text" required value="<?= e(ayar('marka')) ?>"></div>
    <div class="alan"><label for="a-telefon">Telefon</label><input id="a-telefon" name="telefon" type="text" value="<?= e(ayar('telefon')) ?>"></div>
    <div class="alan"><label for="a-eposta">Sitede görünen e-posta</label><input id="a-eposta" name="eposta" type="text" value="<?= e(ayar('eposta')) ?>"></div>
    <div class="alan"><label for="a-adres">Adres</label><textarea id="a-adres" name="adres" rows="2"><?= e(ayar('adres')) ?></textarea></div>
    <div class="alan"><label for="a-saat">Çalışma saatleri</label><input id="a-saat" name="calisma_saatleri" type="text" value="<?= e(ayar('calisma_saatleri')) ?>"></div>
    <div class="alan"><label for="a-bildirim">Yeni sipariş ve mesajların bildirileceği e-posta</label><input id="a-bildirim" name="bildirim_eposta" type="email" value="<?= e(ayar('bildirim_eposta')) ?>"><small>Boş bırakılırsa bildirim e-postası gönderilmez.</small></div>
  </section>

  <div style="display:flex; flex-direction:column; gap:18px">
    <section class="kutu form">
      <h2>Teslimat ve stok</h2>
      <div class="alan"><label for="a-bolge">Teslimat bölgesi</label><input id="a-bolge" name="teslimat_bolgesi" type="text" value="<?= e(ayar('teslimat_bolgesi')) ?>"></div>
      <div class="alanlar">
        <div class="alan"><label for="a-ucret">Teslimat ücreti (₺)</label><input id="a-ucret" name="teslimat_ucreti" type="text" inputmode="decimal" value="<?= e($para('teslimat_ucreti')) ?>"></div>
        <div class="alan"><label for="a-esik">Ücretsiz teslimat tutarı (₺)</label><input id="a-esik" name="ucretsiz_teslimat" type="text" inputmode="decimal" value="<?= e($para('ucretsiz_teslimat')) ?>"><small>0 yazarsanız ücretsiz teslimat uygulanmaz.</small></div>
      </div>
      <div class="alan"><label for="a-stok">Stok uyarı sınırı (adet)</label><input id="a-stok" name="stok_uyari" type="number" min="0" value="<?= e(ayar('stok_uyari')) ?>"><small>Stoku bu sayıya inen ürünler "azaldı" olarak işaretlenir.</small></div>
    </section>
    <section class="kutu form">
      <h2>Ödeme</h2>
      <label class="onay" for="a-kapida"><input id="a-kapida" name="kapida_odeme" value="1" type="checkbox"<?= ayar('kapida_odeme', '1') === '1' ? ' checked' : '' ?>><span>Kapıda ödeme seçeneği açık olsun</span></label>
      <div class="alan"><label for="a-sure">Ödenmeyen sipariş kaç dakika sonra kendiliğinden kapansın?</label><input id="a-sure" name="odeme_suresi_dk" type="number" min="0" max="1440" value="<?= e(ayar('odeme_suresi_dk', '60')) ?>"><small>Kartla ödemeyi yarıda bırakan siparişler için. 0 yazarsanız kendiliğinden kapanmaz.</small></div>
      <?php if (paytr_aktif()): ?>
        <div class="bilgi basari">PayTR bağlı (<?= PAYTR_TEST_MODU ? 'test modu' : 'gerçek ödeme' ?>).</div>
        <p class="soluk">PayTR mağaza panelinde Bildirim URL olarak şu adresi girin:<br><b><?= e(site_adresi()) ?>/paytr-bildirim.php</b></p>
      <?php else: ?>
        <div class="uyari">Deneme ödemesi modu: PayTR bilgileri girilmemiş, gerçek para çekilmiyor.</div>
        <p class="soluk">PayTR mağaza no, anahtar ve gizli anahtar güvenlik nedeniyle panelden değil, <b>inc/ayar.php</b> dosyasından girilir.</p>
      <?php endif; ?>
    </section>
  </div>

  <section class="kutu form" style="flex-basis:100%">
    <h2>Yasal metinler</h2>
    <p class="soluk">Sitenin altındaki bağlantılarda gösterilir. Boş bırakılan metnin sayfasında "henüz yazılmadı" notu çıkar. Paragrafları boş satırla ayırın.</p>
    <div class="alan"><label for="a-sozlesme">Mesafeli Satış Sözleşmesi ve Ön Bilgilendirme</label><textarea id="a-sozlesme" name="sozlesme_metni" rows="6"><?= e(ayar('sozlesme_metni')) ?></textarea></div>
    <div class="alan"><label for="a-iade">İade ve Teslimat Koşulları</label><textarea id="a-iade" name="iade_metni" rows="6"><?= e(ayar('iade_metni')) ?></textarea></div>
    <div class="alan"><label for="a-kvkk">Gizlilik ve KVKK Aydınlatma Metni</label><textarea id="a-kvkk" name="kvkk_metni" rows="6"><?= e(ayar('kvkk_metni')) ?></textarea></div>
    <button type="submit" class="dugme bordo" style="align-self:flex-start">Tüm ayarları kaydet</button>
  </section>
</form>
<?php y_alt(); ?>
