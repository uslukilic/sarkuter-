<?php
require __DIR__ . '/_ust.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    $id = (int) post('id');
    if (post('islem') === 'stok') {
        q("UPDATE urunler SET stok = ? WHERE id = ?", [max(0, (int) post('stok')), $id]);
        mesaj_yaz('Stok güncellendi.', 'basari');
    } elseif (post('islem') === 'durum') {
        q("UPDATE urunler SET aktif = CASE WHEN aktif = 1 THEN 0 ELSE 1 END WHERE id = ?", [$id]);
        mesaj_yaz('Ürünün satış durumu değiştirildi.', 'basari');
    } elseif (post('islem') === 'sil') {
        if (deger("SELECT COUNT(*) FROM siparis_kalemleri WHERE urun_id = ?", [$id])) {
            q("UPDATE urunler SET aktif = 0 WHERE id = ?", [$id]);
            mesaj_yaz('Bu ürün geçmiş siparişlerde yer aldığı için silinmedi; satıştan kaldırıldı.');
        } else {
            q("DELETE FROM urunler WHERE id = ?", [$id]);
            mesaj_yaz('Ürün silindi.', 'basari');
        }
    }
    yonlendir('urunler.php' . (isset($_GET['azalan']) ? '?azalan=1' : ''));
}
$esik = (int) ayar('stok_uyari', '5');
$azalan = isset($_GET['azalan']);
$urunler = satirlar("SELECT u.*, k.ad AS kategori, p.ad AS ust_kategori FROM urunler u LEFT JOIN kategoriler k ON k.id = u.kategori_id LEFT JOIN kategoriler p ON p.id = k.ust_id"
    . ($azalan ? " WHERE u.aktif = 1 AND u.stok <= " . $esik : "") . " ORDER BY COALESCE(p.sira, k.sira), k.ust_id, k.sira, u.ad");
y_ust('Ürünler ve stok', 'urunler');
?>
<div class="bolum-ust" style="margin-bottom:16px">
  <nav class="cipler">
    <a class="cip" style="display:inline-flex;align-items:center" href="urunler.php"<?= $azalan ? '' : ' aria-current="true"' ?>>Tüm ürünler</a>
    <a class="cip" style="display:inline-flex;align-items:center" href="urunler.php?azalan=1"<?= $azalan ? ' aria-current="true"' : '' ?>>Stoku azalanlar (<?= $esik ?> ve altı)</a>
  </nav>
  <div class="dugmeler"><a class="dugme cizgi" href="ornek-urunler.php">Örnek ürün ekle</a><a class="dugme bordo" href="urun.php">+ Yeni ürün</a></div>
</div>
<section class="kutu">
  <?php if (!$urunler): ?><p class="soluk">Gösterilecek ürün yok.</p><?php else: ?>
  <div class="tablo-kap"><table class="tablo">
    <thead><tr><th></th><th>Ürün</th><th>Kategori</th><th class="sag">Fiyat</th><th>Stok</th><th>Durum</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($urunler as $u): ?>
      <tr>
        <td><img class="y-gorsel" src="../<?= e($u['gorsel']) ?>" alt=""></td>
        <td><a class="baglanti" style="min-height:0" href="urun.php?id=<?= (int) $u['id'] ?>"><?= e($u['ad']) ?></a><br><span class="soluk"><?= e($u['paket']) ?></span></td>
        <td><?= $u['ust_kategori'] ? e($u['ust_kategori']) . ' › ' : '' ?><?= e($u['kategori'] ?? '—') ?></td>
        <td class="sag"><?= tl($u['fiyat']) ?></td>
        <td>
          <form method="post" class="satir-form"><?= csrf_alan() ?><input type="hidden" name="islem" value="stok"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
            <input class="kucuk-girdi" style="width:76px" type="number" name="stok" min="0" value="<?= (int) $u['stok'] ?>" aria-label="<?= e($u['ad']) ?> stok adedi">
            <button class="dugme kucuk cizgi" type="submit">Kaydet</button>
            <?php if ((int) $u['stok'] === 0): ?><span class="rozet kotu">Tükendi</span><?php elseif ((int) $u['stok'] <= $esik): ?><span class="rozet uyari">Azaldı</span><?php endif; ?>
          </form>
        </td>
        <td><?= $u['aktif'] ? '<span class="rozet iyi">Satışta</span>' : '<span class="rozet notr">Kapalı</span>' ?></td>
        <td>
          <div class="satir-form">
            <form method="post" style="margin:0"><?= csrf_alan() ?><input type="hidden" name="islem" value="durum"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><button class="dugme kucuk cizgi" type="submit"><?= $u['aktif'] ? 'Satıştan kaldır' : 'Satışa aç' ?></button></form>
            <form method="post" style="margin:0"><?= csrf_alan() ?><input type="hidden" name="islem" value="sil"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><button class="dugme kucuk cizgi" type="submit" data-onay="<?= e($u['ad']) ?> silinsin mi?">Sil</button></form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php y_alt(); ?>
