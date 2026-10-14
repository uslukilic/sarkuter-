<?php
require __DIR__ . '/_ust.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    $id = (int) post('id');
    $ad = kisalt(post('ad'), 80);
    $ust = (int) post('ust_id');
    // Üst kategori yalnızca bir ana kategori olabilir (iki seviye: ana kategori ve alt kategori).
    if ($ust && !deger("SELECT COUNT(*) FROM kategoriler WHERE id = ? AND ust_id = 0", [$ust])) {
        $ust = 0;
    }
    $islem = post('islem');
    if ($islem === 'ekle' && $ad !== '') {
        q("INSERT INTO kategoriler (ad, sira, aktif, ust_id) VALUES (?, ?, 1, ?)", [$ad, (int) post('sira'), $ust]);
        mesaj_yaz($ust ? 'Alt kategori eklendi.' : 'Kategori eklendi.', 'basari');
    } elseif ($islem === 'guncelle' && $ad !== '') {
        $altSayisi = (int) deger("SELECT COUNT(*) FROM kategoriler WHERE ust_id = ?", [$id]);
        if ($ust === $id) {
            mesaj_yaz('Bir kategori kendisinin alt kategorisi olamaz.', 'hata');
        } elseif ($ust && $altSayisi) {
            mesaj_yaz('Bu kategorinin kendi alt kategorileri var; önce onları başka yere taşıyın, sonra bunu alt kategori yapın.', 'hata');
        } else {
            q("UPDATE kategoriler SET ad = ?, sira = ?, aktif = ?, ust_id = ? WHERE id = ?", [$ad, (int) post('sira'), post('aktif') === '1' ? 1 : 0, $ust, $id]);
            mesaj_yaz('Kategori güncellendi.', 'basari');
        }
    } elseif ($islem === 'sil') {
        if (deger("SELECT COUNT(*) FROM kategoriler WHERE ust_id = ?", [$id])) {
            mesaj_yaz('Bu kategorinin alt kategorileri var. Önce onları silin ya da başka kategoriye taşıyın.', 'hata');
        } elseif (deger("SELECT COUNT(*) FROM urunler WHERE kategori_id = ?", [$id])) {
            mesaj_yaz('Bu kategoride ürün var. Önce ürünleri başka kategoriye taşıyın ya da kategoriyi gizleyin.', 'hata');
        } else {
            q("DELETE FROM kategoriler WHERE id = ?", [$id]);
            mesaj_yaz('Kategori silindi.', 'basari');
        }
    } else {
        mesaj_yaz('Kategori adını yazın.', 'hata');
    }
    yonlendir('kategoriler.php');
}
$agac = kategori_agaci(false);
$sayilar = [];
foreach (satirlar("SELECT kategori_id, COUNT(*) AS n FROM urunler GROUP BY kategori_id") as $r) {
    $sayilar[(int) $r['kategori_id']] = (int) $r['n'];
}
$toplam = (int) deger("SELECT COUNT(*) FROM kategoriler");

/* Bir kategorinin düzenleme satırını yazar. */
function kategori_satiri(array $k, array $agac, array $sayilar, bool $altMi): void
{
    $altVar = !$altMi && !empty($k['alt']);
    ?>
    <div class="kalem<?= $altMi ? ' y-alt-satir' : '' ?>">
      <form method="post" class="satir-form" style="flex:1 1 460px">
        <?= csrf_alan() ?><input type="hidden" name="islem" value="guncelle"><input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
        <input class="kucuk-girdi" style="flex:1 1 150px" type="text" name="ad" value="<?= e($k['ad']) ?>" aria-label="Kategori adı" required>
        <select class="kucuk-girdi" name="ust_id" aria-label="<?= e($k['ad']) ?> hangi kategorinin altında"<?= $altVar ? ' disabled title="Alt kategorileri olan bir kategori başka kategorinin altına alınamaz"' : '' ?>>
          <option value="0">Ana kategori</option>
          <?php foreach ($agac as $a): if ((int) $a['id'] === (int) $k['id']) { continue; } ?>
            <option value="<?= (int) $a['id'] ?>"<?= (int) $k['ust_id'] === (int) $a['id'] ? ' selected' : '' ?>><?= e($a['ad']) ?> altında</option>
          <?php endforeach; ?>
        </select>
        <input class="kucuk-girdi" style="width:64px" type="number" name="sira" value="<?= (int) $k['sira'] ?>" aria-label="Sıra">
        <label class="onay" style="min-height:40px; align-items:center"><input type="checkbox" name="aktif" value="1"<?= $k['aktif'] ? ' checked' : '' ?>><span>Görünür</span></label>
        <button class="dugme kucuk cizgi" type="submit">Kaydet</button>
        <span class="soluk"><?= (int) ($sayilar[(int) $k['id']] ?? 0) ?> ürün</span>
      </form>
      <form method="post" style="margin:0"><?= csrf_alan() ?><input type="hidden" name="islem" value="sil"><input type="hidden" name="id" value="<?= (int) $k['id'] ?>"><button class="dugme kucuk cizgi" type="submit" data-onay="<?= e($k['ad']) ?> kategorisi silinsin mi?">Sil</button></form>
    </div>
    <?php
}
y_ust('Kategoriler', 'kategoriler');
?>
<div class="y-iki">
  <section class="kutu" style="flex:2 1 560px">
    <h2>Kategoriler ve alt kategoriler</h2>
    <p class="soluk">Alt kategoriler, bağlı oldukları ana kategorinin altında içeriden başlar. Müşteri ana kategoriyi seçince alt kategorilerindeki ürünlerin hepsini görür. Sıra numarası küçük olan önce görünür; gizlenen kategorinin (ve alt kategorilerinin) ürünleri sitede görünmez.</p>
    <?php if (!$agac): ?><p class="soluk">Henüz kategori yok.</p><?php endif; ?>
    <?php foreach ($agac as $k): ?>
      <?php kategori_satiri($k, $agac, $sayilar, false); ?>
      <?php foreach ($k['alt'] as $a): ?>
        <?php kategori_satiri($a, $agac, $sayilar, true); ?>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </section>
  <form class="kutu form" method="post">
    <?= csrf_alan() ?><input type="hidden" name="islem" value="ekle">
    <h2>Yeni kategori ya da alt kategori</h2>
    <div class="alan"><label for="k-ad">Ad</label><input id="k-ad" name="ad" type="text" required placeholder="Örn. Peynir"></div>
    <div class="alan"><label for="k-ust">Nereye eklensin?</label>
      <select id="k-ust" name="ust_id">
        <option value="0">Ana kategori olarak</option>
        <?php foreach ($agac as $a): ?><option value="<?= (int) $a['id'] ?>"><?= e($a['ad']) ?> altına (alt kategori)</option><?php endforeach; ?>
      </select>
      <small>Örnek: önce "Kahvaltılık" ana kategorisini ekleyin, sonra "Peynir"i onun altına alt kategori olarak ekleyin.</small></div>
    <div class="alan"><label for="k-sira">Sıra</label><input id="k-sira" name="sira" type="number" value="<?= $toplam + 1 ?>"></div>
    <button type="submit" class="dugme bordo">Ekle</button>
  </form>
</div>
<?php y_alt(); ?>
