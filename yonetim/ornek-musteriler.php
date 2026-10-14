<?php
/* Temsili örnek üye müşteriler ekler ya da siler. Gerçek üyelere dokunmaz. */
require __DIR__ . '/_ust.php';
// Örnek üyelerin şifre alanına geçerli bir şifre yerine bu işaret yazılır: bu hesaplarla giriş yapılamaz.
const ORNEK_ISARETI = 'ORNEK';
const ORNEK_SIPARIS_NOTU = 'ÖRNEK SİPARİŞ (temsili, gerçek müşteri değil)';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    if (post('islem') === 'sil') {
        $idler = array_column(satirlar("SELECT id FROM musteriler WHERE sifre = ?", [ORNEK_ISARETI]), 'id');
        foreach ($idler as $id) {
            q("UPDATE siparisler SET musteri_id = NULL WHERE musteri_id = ?", [$id]); // siparişler misafir siparişi olarak kalır
            q("DELETE FROM musteriler WHERE id = ?", [$id]);
        }
        mesaj_yaz(count($idler) . ' örnek müşteri silindi.', 'basari');
        yonlendir('musteriler.php');
    }
    // [ad, soyad, e-posta, telefon, adres, kampanya izni, kaç gün önce kayıt oldu] – hepsi TEMSİLİDİR
    $ornekler = [
        ['Ayşe', 'Yılmaz', 'ayse@ornek.com', '0500 000 00 01', 'Örnek Mah. Lale Sok. No: 4 D: 2', 1, 64],
        ['Mehmet', 'Demir', 'mehmet@ornek.com', '0500 000 00 02', 'Örnek Mah. Gül Cad. No: 18 D: 5', 0, 58],
        ['Fatma', 'Kaya', 'fatma@ornek.com', '0500 000 00 03', 'Deneme Mah. Çınar Sok. No: 7', 1, 51],
        ['Ali', 'Çelik', 'ali@ornek.com', '0500 000 00 04', 'Deneme Mah. Okul Cad. No: 22 D: 1', 1, 47],
        ['Zeynep', 'Şahin', 'zeynep@ornek.com', '0500 000 00 05', 'Örnek Mah. Park Sok. No: 9 D: 3', 0, 40],
        ['Mustafa', 'Aydın', 'mustafa@ornek.com', '0500 000 00 06', 'Deneme Mah. Çarşı Cad. No: 31', 1, 33],
        ['Elif', 'Arslan', 'elif@ornek.com', '0500 000 00 07', 'Örnek Mah. Bahar Sok. No: 12 D: 4', 1, 29],
        ['Hasan', 'Koç', 'hasan@ornek.com', '0500 000 00 08', 'Deneme Mah. İstasyon Cad. No: 5', 0, 21],
        ['Emine', 'Kurt', 'emine@ornek.com', '0500 000 00 09', 'Örnek Mah. Menekşe Sok. No: 3', 0, 16],
        ['İbrahim', 'Öztürk', 'ibrahim@ornek.com', '0500 000 00 10', 'Deneme Mah. Sanayi Cad. No: 40', 1, 12],
        ['Hatice', 'Polat', 'hatice@ornek.com', '0500 000 00 11', 'Örnek Mah. Yıldız Sok. No: 6 D: 2', 1, 8],
        ['Hüseyin', 'Aksoy', 'huseyin@ornek.com', '0500 000 00 12', '', 0, 5],
        ['Merve', 'Erdoğan', 'merve@ornek.com', '0500 000 00 13', 'Deneme Mah. Pınar Sok. No: 14', 1, 3],
        ['Osman', 'Güneş', 'osman@ornek.com', '0500 000 00 14', '', 0, 2],
        ['Sultan', 'Bulut', 'sultan@ornek.com', '0500 000 00 15', 'Örnek Mah. Ihlamur Cad. No: 27 D: 6', 1, 0],
    ];
    $eklenen = 0;
    $atlanan = 0;
    foreach ($ornekler as $o) {
        [$ad, $soyad, $eposta, $tel, $adres, $izin, $gun] = $o;
        if (deger("SELECT COUNT(*) FROM musteriler WHERE eposta = ?", [$eposta])) {
            $atlanan++;
            continue;
        }
        $id = ekle("INSERT INTO musteriler (ad, soyad, eposta, telefon, sifre, adres, kampanya_izni, olusturma) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$ad, $soyad, $eposta, $tel, ORNEK_ISARETI, $adres, $izin, date('Y-m-d H:i:s', time() - $gun * 86400 - 3600)]);
        // aynı e-postayla verilmiş örnek siparişler varsa bu üyeye bağla
        q("UPDATE siparisler SET musteri_id = ? WHERE eposta = ? AND siparis_notu = ?", [$id, $eposta, ORNEK_SIPARIS_NOTU]);
        $eklenen++;
    }
    mesaj_yaz($eklenen . ' örnek müşteri eklendi' . ($atlanan ? ', ' . $atlanan . ' e-posta zaten kayıtlıydı.' : '.'), 'basari');
    yonlendir('musteriler.php');
}
$mevcut = (int) deger("SELECT COUNT(*) FROM musteriler WHERE sifre = ?", [ORNEK_ISARETI]);
y_ust('Örnek müşteriler', 'musteriler');
?>
<p style="margin:-8px 0 16px"><a class="baglanti" href="musteriler.php">← Müşterilere dön</a></p>
<div class="y-iki">
  <form class="kutu form" method="post">
    <?= csrf_alan() ?><input type="hidden" name="islem" value="ekle">
    <h2>Örnek müşteri ekle</h2>
    <p>15 temsili üye eklenir. Örnek siparişlerle aynı e-postayı taşıyanlar o siparişlere bağlanır; böylece sipariş sayısı ve harcama sütunları dolu görünür.</p>
    <div class="uyari">Adlar, telefonlar, e-postalar ve adresler uydurmadır. Bu hesapların şifresi yoktur, yani kimse bu hesaplarla giriş yapamaz.</div>
    <button type="submit" class="dugme bordo" style="align-self:flex-start">Örnek müşterileri ekle</button>
  </form>
  <form class="kutu form" method="post">
    <?= csrf_alan() ?><input type="hidden" name="islem" value="sil">
    <h2>Örnek müşterileri sil</h2>
    <p>Şu an <b><?= $mevcut ?></b> örnek müşteri var. Silme yalnızca örnek müşterileri kaldırır; gerçek üyelere dokunmaz. Bağlı örnek siparişler silinmez, misafir siparişi olarak kalır.</p>
    <button type="submit" class="dugme cizgi" style="align-self:flex-start"<?= $mevcut ? '' : ' disabled' ?> data-onay="Bütün örnek müşteriler silinsin mi?">Örnek müşterileri sil</button>
  </form>
</div>
<?php y_alt(); ?>
