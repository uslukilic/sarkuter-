<?php
require __DIR__ . '/_ust.php';
function saat_gecerli(string $s): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $s);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    $id = (int) post('id');
    $b = post('baslangic');
    $s = post('bitis');
    $islem = post('islem');
    if ($islem === 'ekle' || $islem === 'guncelle') {
        if (!saat_gecerli($b) || !saat_gecerli($s) || $b >= $s) {
            mesaj_yaz('Başlangıç ve bitiş saatini doğru girin; bitiş başlangıçtan sonra olmalı.', 'hata');
        } elseif ($islem === 'ekle') {
            q("INSERT INTO teslimat_dilimleri (baslangic, bitis, kapasite, aktif) VALUES (?, ?, ?, 1)", [$b, $s, max(1, (int) post('kapasite'))]);
            mesaj_yaz('Saat aralığı eklendi.', 'basari');
        } else {
            q("UPDATE teslimat_dilimleri SET baslangic = ?, bitis = ?, kapasite = ?, aktif = ? WHERE id = ?", [$b, $s, max(1, (int) post('kapasite')), post('aktif') === '1' ? 1 : 0, $id]);
            mesaj_yaz('Saat aralığı güncellendi.', 'basari');
        }
    } elseif ($islem === 'sil') {
        q("DELETE FROM teslimat_dilimleri WHERE id = ?", [$id]);
        mesaj_yaz('Saat aralığı silindi. Bu aralığa verilmiş eski siparişler etkilenmez.', 'basari');
    } elseif ($islem === 'kurallar') {
        $gunler = array_intersect(['1', '2', '3', '4', '5', '6', '7'], (array) ($_POST['gunler'] ?? []));
        ayar_yaz('teslimat_gunleri', implode(',', $gunler));
        ayar_yaz('ileri_gun', (string) max(0, min(30, (int) post('ileri_gun'))));
        ayar_yaz('son_siparis_dk', (string) max(0, min(1440, (int) post('son_siparis_dk'))));
        mesaj_yaz($gunler ? 'Teslimat kuralları kaydedildi.' : 'Hiç gün seçilmedi: müşteriler şu an teslimat zamanı seçemez ve sipariş veremez.', $gunler ? 'basari' : 'hata');
    }
    yonlendir('dilimler.php');
}
$dilimler = satirlar("SELECT * FROM teslimat_dilimleri ORDER BY baslangic");
$seciliGunler = explode(',', ayar('teslimat_gunleri'));
$gunAdlari = [1 => 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
y_ust('Teslimat saatleri', 'dilimler');
?>
<div class="y-iki">
  <section class="kutu" style="flex:2 1 520px">
    <h2>Saat aralıkları</h2>
    <p class="soluk">Müşteri sepette bu aralıklardan birini seçer. "En fazla sipariş" dolunca o aralık o gün için kapanır.</p>
    <?php if (!$dilimler): ?><div class="bilgi hata">Hiç saat aralığı yok: müşteriler sipariş veremez.</div><?php endif; ?>
    <?php foreach ($dilimler as $d): ?>
      <div class="kalem">
        <form method="post" class="satir-form" style="flex:1 1 440px">
          <?= csrf_alan() ?><input type="hidden" name="islem" value="guncelle"><input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
          <input class="kucuk-girdi" type="time" name="baslangic" value="<?= e($d['baslangic']) ?>" aria-label="Başlangıç" required>
          <span>–</span>
          <input class="kucuk-girdi" type="time" name="bitis" value="<?= e($d['bitis']) ?>" aria-label="Bitiş" required>
          <label class="soluk" style="display:inline-flex; align-items:center; gap:6px">En fazla <input class="kucuk-girdi" style="width:64px" type="number" min="1" name="kapasite" value="<?= (int) $d['kapasite'] ?>"> sipariş</label>
          <label class="onay" style="min-height:40px; align-items:center"><input type="checkbox" name="aktif" value="1"<?= $d['aktif'] ? ' checked' : '' ?>><span>Açık</span></label>
          <button class="dugme kucuk cizgi" type="submit">Kaydet</button>
        </form>
        <form method="post" style="margin:0"><?= csrf_alan() ?><input type="hidden" name="islem" value="sil"><input type="hidden" name="id" value="<?= (int) $d['id'] ?>"><button class="dugme kucuk cizgi" type="submit" data-onay="Bu saat aralığı silinsin mi?">Sil</button></form>
      </div>
    <?php endforeach; ?>
    <form method="post" class="satir-form" style="border-top:1px solid var(--cizgi); padding-top:14px">
      <?= csrf_alan() ?><input type="hidden" name="islem" value="ekle">
      <b>Yeni aralık:</b>
      <input class="kucuk-girdi" type="time" name="baslangic" aria-label="Başlangıç" required>
      <span>–</span>
      <input class="kucuk-girdi" type="time" name="bitis" aria-label="Bitiş" required>
      <label class="soluk" style="display:inline-flex; align-items:center; gap:6px">En fazla <input class="kucuk-girdi" style="width:64px" type="number" min="1" name="kapasite" value="5"> sipariş</label>
      <button class="dugme kucuk bordo" type="submit">Ekle</button>
    </form>
  </section>

  <form class="kutu form" method="post">
    <?= csrf_alan() ?><input type="hidden" name="islem" value="kurallar">
    <h2>Kurallar</h2>
    <fieldset style="border:0; padding:0; margin:0; display:flex; flex-direction:column; gap:2px">
      <legend style="font-weight:600; font-size:14px; padding:0; margin-bottom:4px">Teslimat yapılan günler</legend>
      <?php foreach ($gunAdlari as $n => $ad): ?>
        <label class="onay" style="min-height:34px; align-items:center"><input type="checkbox" name="gunler[]" value="<?= $n ?>"<?= in_array((string) $n, $seciliGunler, true) ? ' checked' : '' ?>><span><?= e($ad) ?></span></label>
      <?php endforeach; ?>
    </fieldset>
    <div class="alan"><label for="d-ileri">En fazla kaç gün sonrasına sipariş alınsın?</label><input id="d-ileri" name="ileri_gun" type="number" min="0" max="30" value="<?= e(ayar('ileri_gun')) ?>"><small>0 yazarsanız yalnızca aynı gün teslimat seçilebilir.</small></div>
    <div class="alan"><label for="d-son">Aralık başlamadan en geç kaç dakika önce sipariş verilebilsin?</label><input id="d-son" name="son_siparis_dk" type="number" min="0" max="1440" value="<?= e(ayar('son_siparis_dk')) ?>"></div>
    <button type="submit" class="dugme bordo">Kaydet</button>
  </form>
</div>
<?php y_alt(); ?>
