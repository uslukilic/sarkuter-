<?php
/* Temsili örnek siparişler ekler ya da siler. Gerçek siparişlere dokunmaz. */
require __DIR__ . '/_ust.php';
const ORNEK_NOTU = 'ÖRNEK SİPARİŞ (temsili, gerçek müşteri değil)';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    if (post('islem') === 'sil') {
        $idler = array_column(satirlar("SELECT id FROM siparisler WHERE siparis_notu = ?", [ORNEK_NOTU]), 'id');
        foreach ($idler as $id) {
            q("DELETE FROM siparis_kalemleri WHERE siparis_id = ?", [$id]);
            q("DELETE FROM siparis_gecmisi WHERE siparis_id = ?", [$id]);
            q("DELETE FROM odemeler WHERE siparis_id = ?", [$id]);
            q("DELETE FROM siparisler WHERE id = ?", [$id]);
        }
        mesaj_yaz(count($idler) . ' örnek sipariş silindi.', 'basari');
        yonlendir('index.php?durum=hepsi');
    }

    $urunler = satirlar("SELECT * FROM urunler WHERE aktif = 1 ORDER BY id");
    $dilimler = satirlar("SELECT * FROM teslimat_dilimleri WHERE aktif = 1 ORDER BY baslangic");
    if (count($urunler) < 3 || !$dilimler) {
        mesaj_yaz('Örnek sipariş için en az 3 satıştaki ürün ve 1 teslimat saati gerekir.', 'hata');
        yonlendir('index.php');
    }
    // [ad, telefon, e-posta, adres, durum, kaç saat önce verildi, teslimat günü (bugünden kaç gün sonra)]
    $ornekler = [
        ['Ayşe Yılmaz', '0500 000 00 01', 'ayse@ornek.com', 'Örnek Mah. Lale Sok. No: 4 D: 2', 'onay_bekliyor', 0.3, 0],
        ['Mehmet Demir', '0500 000 00 02', 'mehmet@ornek.com', 'Örnek Mah. Gül Cad. No: 18 D: 5', 'onay_bekliyor', 1, 1],
        ['Fatma Kaya', '0500 000 00 03', 'fatma@ornek.com', 'Deneme Mah. Çınar Sok. No: 7', 'onaylandi', 2, 0],
        ['Ali Çelik', '0500 000 00 04', 'ali@ornek.com', 'Deneme Mah. Okul Cad. No: 22 D: 1', 'onaylandi', 3, 1],
        ['Zeynep Şahin', '0500 000 00 05', 'zeynep@ornek.com', 'Örnek Mah. Park Sok. No: 9 D: 3', 'yolda', 4, 0],
        ['Mustafa Aydın', '0500 000 00 06', 'mustafa@ornek.com', 'Deneme Mah. Çarşı Cad. No: 31', 'yolda', 5, 0],
        ['Elif Arslan', '0500 000 00 07', 'elif@ornek.com', 'Örnek Mah. Bahar Sok. No: 12 D: 4', 'teslim_edildi', 26, -1],
        ['Hasan Koç', '0500 000 00 08', 'hasan@ornek.com', 'Deneme Mah. İstasyon Cad. No: 5', 'teslim_edildi', 50, -2],
        ['Emine Kurt', '0500 000 00 09', 'emine@ornek.com', 'Örnek Mah. Menekşe Sok. No: 3', 'reddedildi', 6, 0],
        ['İbrahim Öztürk', '0500 000 00 10', 'ibrahim@ornek.com', 'Deneme Mah. Sanayi Cad. No: 40', 'odeme_bekliyor', 0.1, 1],
    ];
    $akis = ['odeme_bekliyor', 'onay_bekliyor', 'onaylandi', 'yolda', 'teslim_edildi'];
    $esik = (int) ayar('ucretsiz_teslimat');
    $eklenen = 0;
    foreach ($ornekler as $i => $o) {
        [$ad, $tel, $eposta, $adres, $durum, $saatOnce, $gun] = $o;
        do {
            $no = 'S' . date('ymd') . random_int(10000, 99999);
        } while (siparis_getir_no($no));
        // her siparişe 1–3 farklı ürün
        $kalemler = [];
        $ara = 0;
        for ($j = 0; $j < 1 + ($i % 3); $j++) {
            $u = $urunler[($i * 3 + $j * 5) % count($urunler)];
            $adet = 1 + (($i + $j) % 2);
            $kalemler[] = [$u, $adet];
            $ara += (int) $u['fiyat'] * $adet;
        }
        $teslimat = ($esik > 0 && $ara >= $esik) ? 0 : (int) ayar('teslimat_ucreti');
        $verildi = time() - (int) ($saatOnce * 3600);
        $d = $dilimler[$i % count($dilimler)];
        $id = ekle("INSERT INTO siparisler (no, anahtar, musteri_id, ad, telefon, eposta, adres, siparis_notu, teslimat_tarihi, teslimat_saati,
                ara_toplam, teslimat_ucreti, toplam, durum, ret_nedeni, stok_dusuldu, iade, olusturma, guncelleme)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'yok', ?, ?)",
            [$no, bin2hex(random_bytes(16)), deger("SELECT id FROM musteriler WHERE eposta = ? AND sifre = 'ORNEK'", [$eposta]), $ad, $tel, $eposta, ayar('teslimat_bolgesi') . ' — ' . $adres, ORNEK_NOTU,
                date('Y-m-d', strtotime(($gun >= 0 ? '+' : '') . $gun . ' day')), $d['baslangic'] . '-' . $d['bitis'],
                $ara, $teslimat, $ara + $teslimat, $durum, $durum === 'reddedildi' ? 'Ürün elimizde kalmadı (örnek)' : null,
                date('Y-m-d H:i:s', $verildi), simdi()]);
        foreach ($kalemler as [$u, $adet]) {
            q("INSERT INTO siparis_kalemleri (siparis_id, urun_id, urun_adi, paket, fiyat, adet) VALUES (?, ?, ?, ?, ?, ?)",
                [$id, $u['id'], $u['ad'], $u['paket'], $u['fiyat'], $adet]);
        }
        // aşama saatleri: sipariş anından bugüne eşit aralıklarla
        $adimlar = $durum === 'reddedildi' ? ['odeme_bekliyor', 'onay_bekliyor', 'reddedildi'] : array_slice($akis, 0, array_search($durum, $akis, true) + 1);
        $aralik = (time() - $verildi) / max(1, count($adimlar));
        foreach ($adimlar as $n => $adim) {
            q("INSERT INTO siparis_gecmisi (siparis_id, durum, olusturma) VALUES (?, ?, ?)", [$id, $adim, date('Y-m-d H:i:s', (int) ($verildi + $n * $aralik))]);
        }
        if ($durum !== 'odeme_bekliyor') {
            q("INSERT INTO odemeler (siparis_id, tur, tutar, sonuc, aciklama, olusturma) VALUES (?, 'deneme', ?, 'basarili', 'Örnek sipariş (gerçek ödeme değil)', ?)",
                [$id, $ara + $teslimat, date('Y-m-d H:i:s', $verildi + 60)]);
        }
        $eklenen++;
    }
    mesaj_yaz($eklenen . ' örnek sipariş eklendi.', 'basari');
    yonlendir('index.php?durum=hepsi');
}
$mevcut = (int) deger("SELECT COUNT(*) FROM siparisler WHERE siparis_notu = ?", [ORNEK_NOTU]);
y_ust('Örnek siparişler', 'siparisler');
?>
<p style="margin:-8px 0 16px"><a class="baglanti" href="index.php">← Siparişlere dön</a></p>
<div class="y-iki">
  <form class="kutu form" method="post">
    <?= csrf_alan() ?><input type="hidden" name="islem" value="ekle">
    <h2>Örnek sipariş ekle</h2>
    <p>Farklı aşamalarda 10 temsili sipariş eklenir: 2 onay bekleyen, 2 hazırlanan, 2 yolda, 2 teslim edilmiş, 1 reddedilmiş ve 1 ödemesi tamamlanmamış.</p>
    <div class="uyari">Müşteri adları, telefonlar ve adresler uydurmadır. Bu siparişler stoktan düşmez, e-posta göndermez ve müşteri notunda "ÖRNEK SİPARİŞ" yazar.</div>
    <button type="submit" class="dugme bordo" style="align-self:flex-start">Örnek siparişleri ekle</button>
  </form>
  <form class="kutu form" method="post">
    <?= csrf_alan() ?><input type="hidden" name="islem" value="sil">
    <h2>Örnek siparişleri sil</h2>
    <p>Şu an <b><?= $mevcut ?></b> örnek sipariş var. Silme yalnızca örnek siparişleri kaldırır; gerçek siparişlere dokunmaz.</p>
    <button type="submit" class="dugme cizgi" style="align-self:flex-start"<?= $mevcut ? '' : ' disabled' ?> data-onay="Bütün örnek siparişler silinsin mi?">Örnek siparişleri sil</button>
  </form>
</div>
<?php y_alt(); ?>
