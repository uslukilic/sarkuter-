<?php
require __DIR__ . '/_ust.php';
$id = (int) ($_GET['id'] ?? 0);
$u = $id ? satir("SELECT * FROM urunler WHERE id = ?", [$id]) : null;
if ($id && !$u) {
    mesaj_yaz('Ürün bulunamadı.', 'hata');
    yonlendir('urunler.php');
}
$kategoriler = kategori_agaci(false);
$f = $u ?: ['ad' => '', 'kategori_id' => $kategoriler[0]['id'] ?? 0, 'kisa' => '', 'paket' => '', 'fiyat' => 0, 'stok' => 0,
    'gorsel' => 'assets/img/logo.svg', 'icindekiler' => '', 'saklama' => '', 'raf' => '', 'aktif' => 1];
$hatalar = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    foreach (['ad', 'kisa', 'paket', 'icindekiler', 'saklama', 'raf'] as $a) {
        $f[$a] = post($a);
    }
    $f['kategori_id'] = (int) post('kategori_id');
    $f['fiyat'] = kurusa(post('fiyat'));
    $f['stok'] = max(0, (int) post('stok'));
    $f['aktif'] = post('aktif') === '1' ? 1 : 0;
    if (mb_strlen($f['ad']) < 2) {
        $hatalar[] = 'Ürün adını yazın.';
    }
    if ($f['fiyat'] <= 0) {
        $hatalar[] = 'Fiyatı sıfırdan büyük girin (örn. 450 ya da 449,90).';
    }
    if (!deger("SELECT COUNT(*) FROM kategoriler WHERE id = ?", [$f['kategori_id']])) {
        $hatalar[] = 'Bir kategori seçin.';
    }
    // Görsel yükleme (isteğe bağlı)
    if (!empty($_FILES['gorsel']['name']) && !$hatalar) {
        $d = $_FILES['gorsel'];
        $tur = $d['error'] === UPLOAD_ERR_OK ? @getimagesize($d['tmp_name']) : false;
        $uzantilar = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
        if (!$tur || !isset($uzantilar[$tur[2]])) {
            $hatalar[] = 'Görsel JPG, PNG ya da WEBP olmalı.';
        } elseif ($d['size'] > 4 * 1024 * 1024) {
            $hatalar[] = 'Görsel en fazla 4 MB olabilir.';
        } else {
            $klasor = __DIR__ . '/../assets/yukleme';
            if (!is_dir($klasor)) {
                mkdir($klasor, 0775, true);
            }
            $ad = bin2hex(random_bytes(8)) . '.' . $uzantilar[$tur[2]];
            if (move_uploaded_file($d['tmp_name'], $klasor . '/' . $ad)) {
                $f['gorsel'] = 'assets/yukleme/' . $ad;
            } else {
                $hatalar[] = 'Görsel kaydedilemedi.';
            }
        }
    }
    if (!$hatalar) {
        $p = [$f['kategori_id'], kisalt($f['ad'], 120), kisalt($f['kisa'], 200), kisalt($f['paket'], 60), $f['fiyat'], $f['stok'], $f['gorsel'],
            kisalt($f['icindekiler'], 1000), kisalt($f['saklama'], 1000), kisalt($f['raf'], 1000), $f['aktif']];
        if ($u) {
            q("UPDATE urunler SET kategori_id = ?, ad = ?, kisa = ?, paket = ?, fiyat = ?, stok = ?, gorsel = ?, icindekiler = ?, saklama = ?, raf = ?, aktif = ? WHERE id = ?", array_merge($p, [$u['id']]));
        } else {
            q("INSERT INTO urunler (kategori_id, ad, kisa, paket, fiyat, stok, gorsel, icindekiler, saklama, raf, aktif, olusturma) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array_merge($p, [simdi()]));
        }
        mesaj_yaz('Ürün kaydedildi.', 'basari');
        yonlendir('urunler.php');
    }
}
y_ust($u ? 'Ürünü düzenle' : 'Yeni ürün', 'urunler');
?>
<p style="margin:-8px 0 16px"><a class="baglanti" href="urunler.php">← Ürünlere dön</a></p>
<form class="kutu form" method="post" enctype="multipart/form-data" style="max-width:760px">
  <?= csrf_alan() ?>
  <?php if ($hatalar): ?><div class="bilgi hata" role="alert"><ul style="margin:0; padding-left:18px"><?php foreach ($hatalar as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <div class="alanlar">
    <div class="alan" style="flex:2 1 260px"><label for="u-ad">Ürün adı</label><input id="u-ad" name="ad" type="text" required value="<?= e($f['ad']) ?>"></div>
    <div class="alan"><label for="u-kat">Kategori</label>
      <select id="u-kat" name="kategori_id"><?php foreach ($kategoriler as $k): ?><option value="<?= (int) $k['id'] ?>"<?= (int) $f['kategori_id'] === (int) $k['id'] ? ' selected' : '' ?>><?= e($k['ad']) ?></option><?php foreach ($k['alt'] as $a): ?><option value="<?= (int) $a['id'] ?>"<?= (int) $f['kategori_id'] === (int) $a['id'] ? ' selected' : '' ?>><?= e($k['ad']) ?> › <?= e($a['ad']) ?></option><?php endforeach; ?><?php endforeach; ?></select></div>
  </div>
  <div class="alan"><label for="u-kisa">Kısa açıklama</label><input id="u-kisa" name="kisa" type="text" value="<?= e($f['kisa']) ?>" placeholder="Örn. Dana, orta acılı"></div>
  <div class="alanlar">
    <div class="alan"><label for="u-paket">Paket</label><input id="u-paket" name="paket" type="text" value="<?= e($f['paket']) ?>" placeholder="Örn. 250 g"></div>
    <div class="alan"><label for="u-fiyat">Fiyat (₺)</label><input id="u-fiyat" name="fiyat" type="text" inputmode="decimal" required value="<?= e(number_format($f['fiyat'] / 100, 2, ',', '')) ?>"></div>
    <div class="alan"><label for="u-stok">Stok adedi</label><input id="u-stok" name="stok" type="number" min="0" required value="<?= (int) $f['stok'] ?>"></div>
  </div>
  <div class="alan"><label for="u-gorsel">Görsel (JPG, PNG ya da WEBP, en fazla 4 MB)</label>
    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:12px"><img class="y-gorsel" style="width:96px; height:72px" src="../<?= e($f['gorsel']) ?>" alt="Mevcut görsel"><input id="u-gorsel" name="gorsel" type="file" accept="image/jpeg,image/png,image/webp" style="flex:1 1 220px; padding:8px 12px"></div>
    <small>Yatay (4:3) fotoğraflar en iyi görünür. Yeni görsel seçmezseniz mevcut görsel kalır.</small></div>
  <div class="alan"><label for="u-icindekiler">İçindekiler</label><textarea id="u-icindekiler" name="icindekiler" rows="2"><?= e($f['icindekiler']) ?></textarea></div>
  <div class="alan"><label for="u-saklama">Saklama koşulları</label><textarea id="u-saklama" name="saklama" rows="2"><?= e($f['saklama']) ?></textarea></div>
  <div class="alan"><label for="u-raf">Son tüketim</label><textarea id="u-raf" name="raf" rows="2"><?= e($f['raf']) ?></textarea></div>
  <label class="onay" for="u-aktif"><input id="u-aktif" name="aktif" value="1" type="checkbox"<?= $f['aktif'] ? ' checked' : '' ?>><span>Satışta (işaretli değilse sitede görünmez)</span></label>
  <div class="dugmeler"><button type="submit" class="dugme bordo">Kaydet</button><a class="dugme cizgi" href="urunler.php">Vazgeç</a></div>
</form>
<?php y_alt(); ?>
