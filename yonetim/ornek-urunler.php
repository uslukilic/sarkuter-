<?php
/* Temsili örnek ürünleri ekler. Aynı adda ürün varsa atlar; mevcut ürünlere dokunmaz. */
require __DIR__ . '/_ust.php';

// [görsel, ad, kategori, kısa açıklama, paket, fiyat (₺), stok] – hepsi TEMSİLİDİR
$ornekler = [
    ['acili-kangal-sucuk', 'Acılı kangal sucuk', 'Sucuk', 'Dana, acılı', '250 g', 460, 16],
    ['fermente-sucuk', 'Fermente sucuk', 'Sucuk', 'Dana, geleneksel kurutma', '300 g', 560, 10],
    ['kasap-sucuk', 'Kasap sucuk', 'Sucuk', 'Dana-kuzu karışık', '500 g', 780, 8],
    ['kusgomu-pastirma', 'Kuşgömü pastırma', 'Pastırma', 'Çemenli, en yumuşak kısım', '100 g', 720, 9],
    ['sirt-pastirma', 'Sırt pastırma', 'Pastırma', 'Çemenli, az yağlı', '100 g', 650, 11],
    ['macar-salam', 'Macar salam', 'Salam ve Sosis', 'Dana, baharatlı', '200 g', 260, 14],
    ['hindi-salam', 'Hindi salam', 'Salam ve Sosis', 'Dilimlenmiş, hafif', '200 g', 190, 20],
    ['tavuk-sosis', 'Tavuk sosis', 'Salam ve Sosis', 'Kahvaltılık', '250 g', 170, 18],
    ['kokteyl-sosis', 'Kokteyl sosis', 'Salam ve Sosis', 'Dana, küçük boy', '250 g', 250, 12],
    ['taze-kasar', 'Taze kaşar', 'Peynir', 'Tam yağlı', '350 g', 310, 22],
    ['tulum-peyniri', 'Tulum peyniri', 'Peynir', 'Erzincan tipi', '250 g', 340, 9],
    ['lor-peyniri', 'Lor peyniri', 'Peynir', 'Tuzsuz, taze', '500 g', 160, 7],
    ['cecil-peyniri', 'Çeçil peyniri', 'Peynir', 'Tel tel, az tuzlu', '200 g', 240, 13],
    ['orgu-peyniri', 'Örgü peyniri', 'Peynir', 'Diyarbakır tipi', '250 g', 290, 10],
    ['dil-peyniri', 'Dil peyniri', 'Peynir', 'Taze, lifli', '250 g', 270, 15],
    ['biberli-yesil-zeytin', 'Biberli yeşil zeytin', 'Zeytin', 'Biber dolgulu', '400 g', 210, 17],
    ['sele-zeytin', 'Sele zeytin', 'Zeytin', 'Siyah, az tuzlu', '400 g', 230, 19],
    ['zeytin-ezmesi', 'Zeytin ezmesi', 'Zeytin', 'Yeşil zeytinden, sade', '190 g', 150, 12],
    ['kaymak', 'Kaymak', 'Kahvaltılık', 'Manda sütünden', '200 g', 280, 6],
    ['tahin', 'Tahin', 'Kahvaltılık', 'Çifte kavrulmuş susam', '300 g', 190, 14],
    ['pekmez', 'Üzüm pekmezi', 'Kahvaltılık', 'Katkısız', '400 g', 220, 14],
    ['cilek-receli', 'Çilek reçeli', 'Kahvaltılık', 'Ev yapımı tarzı', '380 g', 180, 16],
    ['visne-receli', 'Vişne reçeli', 'Kahvaltılık', 'Ev yapımı tarzı', '380 g', 180, 16],
    ['kayisi-receli', 'Kayısı reçeli', 'Kahvaltılık', 'Ev yapımı tarzı', '380 g', 170, 12],
    ['acuka', 'Acuka', 'Kahvaltılık', 'Cevizli, acılı ezme', '200 g', 160, 10],
    ['yumurta', 'Köy yumurtası', 'Kahvaltılık', '10\'lu paket', '10 adet', 140, 25],
];
$bilgi = [
    'Sucuk' => ['Dana eti, dana yağı, sarımsak, tuz, baharat karışımı', 'Buzdolabında 0–4 °C arasında saklayın.', 'Paketi açtıktan sonra 7 gün içinde tüketin.'],
    'Pastırma' => ['Dana eti, çemen (sarımsak, çemen otu, kırmızı biber), tuz', 'Buzdolabında 0–4 °C arasında saklayın.', 'Paketi açtıktan sonra 5 gün içinde tüketin.'],
    'Salam ve Sosis' => ['Et, su, nişasta, tuz, baharat karışımı', 'Buzdolabında 0–4 °C arasında saklayın.', 'Paketi açtıktan sonra 3 gün içinde tüketin.'],
    'Peynir' => ['Pastörize süt, peynir mayası, tuz', 'Buzdolabında 2–6 °C arasında, kapalı kapta saklayın.', 'Paketi açtıktan sonra 10 gün içinde tüketin.'],
    'Zeytin' => ['Zeytin, su, tuz, limon', 'Serin ve kuru yerde, açtıktan sonra buzdolabında saklayın.', 'Paketi açtıktan sonra 30 gün içinde tüketin.'],
    'Kahvaltılık' => ['Ürün etiketinde belirtilmiştir.', 'Etiketteki saklama koşullarına uyun.', 'Son tüketim tarihi ambalaj üzerindedir.'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    $eklenen = 0;
    $atlanan = 0;
    foreach ($ornekler as $o) {
        [$gorsel, $ad, $kat, $kisa, $paket, $fiyat, $stok] = $o;
        if (deger("SELECT COUNT(*) FROM urunler WHERE ad = ?", [$ad])) {
            $atlanan++;
            continue;
        }
        $katId = deger("SELECT id FROM kategoriler WHERE ad = ? ORDER BY id LIMIT 1", [$kat]);
        if (!$katId) {
            $katId = ekle("INSERT INTO kategoriler (ad, sira, aktif, ust_id) VALUES (?, ?, 1, 0)", [$kat, 1 + (int) deger("SELECT COUNT(*) FROM kategoriler")]);
        }
        q("INSERT INTO urunler (kategori_id, ad, kisa, paket, fiyat, stok, gorsel, icindekiler, saklama, raf, aktif, olusturma) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)",
            [$katId, $ad, $kisa, $paket, $fiyat * 100, $stok, 'assets/img/' . $gorsel . '.svg', $bilgi[$kat][0], $bilgi[$kat][1], $bilgi[$kat][2], simdi()]);
        $eklenen++;
    }
    mesaj_yaz($eklenen . ' örnek ürün eklendi' . ($atlanan ? ', ' . $atlanan . ' ürün zaten vardı.' : '.'), 'basari');
    yonlendir('urunler.php');
}
y_ust('Örnek ürün ekle', 'urunler');
?>
<p style="margin:-8px 0 16px"><a class="baglanti" href="urunler.php">← Ürünlere dön</a></p>
<form class="kutu form" method="post" style="max-width:620px">
  <?= csrf_alan() ?>
  <p><?= count($ornekler) ?> temsili ürün eklenir (sucuk, pastırma, salam, peynir, zeytin ve kahvaltılık çeşitleri). Adı aynı olan ürün varsa atlanır; mevcut ürünlerinize ve fiyatlarınıza dokunulmaz.</p>
  <div class="uyari">Bu ürünlerin adları, fiyatları, gramajları, stokları ve görselleri gerçek değildir. Site yayına girmeden önce kendi ürünlerinizle değiştirin ya da silin.</div>
  <button type="submit" class="dugme bordo" style="align-self:flex-start">Örnek ürünleri ekle</button>
</form>
<?php y_alt(); ?>
