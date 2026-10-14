<?php
/* Veritabanı tabloları ve ilk veriler. İlk açılışta otomatik çalışır. */

function sema_tablolar(): array
{
    return [
        "CREATE TABLE ayarlar (anahtar VARCHAR(60) PRIMARY KEY, deger TEXT)",
        "CREATE TABLE yoneticiler ({PK}, kullanici_adi VARCHAR(60) UNIQUE, sifre VARCHAR(255), olusturma VARCHAR(19))",
        "CREATE TABLE musteriler ({PK}, ad VARCHAR(80), soyad VARCHAR(80), eposta VARCHAR(160) UNIQUE, telefon VARCHAR(30),
            sifre VARCHAR(255), adres TEXT, kampanya_izni INT DEFAULT 0, olusturma VARCHAR(19),
            sifirlama_kodu VARCHAR(64), sifirlama_bitis VARCHAR(19))",
        "CREATE TABLE kategoriler ({PK}, ad VARCHAR(80), sira INT DEFAULT 0, aktif INT DEFAULT 1, ust_id INT DEFAULT 0)",
        "CREATE TABLE urunler ({PK}, kategori_id INT, ad VARCHAR(120), kisa VARCHAR(200), paket VARCHAR(60), fiyat INT,
            stok INT DEFAULT 0, gorsel VARCHAR(255), icindekiler TEXT, saklama TEXT, raf TEXT, aktif INT DEFAULT 1, olusturma VARCHAR(19))",
        "CREATE TABLE teslimat_dilimleri ({PK}, baslangic VARCHAR(5), bitis VARCHAR(5), kapasite INT DEFAULT 5, aktif INT DEFAULT 1)",
        "CREATE TABLE siparisler ({PK}, no VARCHAR(20) UNIQUE, anahtar VARCHAR(40), musteri_id INT,
            ad VARCHAR(160), telefon VARCHAR(30), eposta VARCHAR(160), adres TEXT, siparis_notu TEXT,
            teslimat_tarihi VARCHAR(10), teslimat_saati VARCHAR(13),
            ara_toplam INT, teslimat_ucreti INT, toplam INT,
            durum VARCHAR(20), ret_nedeni TEXT, stok_dusuldu INT DEFAULT 0, iade VARCHAR(12) DEFAULT 'yok', odeme_yontemi VARCHAR(10) DEFAULT 'kart',
            olusturma VARCHAR(19), guncelleme VARCHAR(19))",
        "CREATE TABLE siparis_kalemleri ({PK}, siparis_id INT, urun_id INT, urun_adi VARCHAR(120), paket VARCHAR(60), fiyat INT, adet INT)",
        "CREATE TABLE siparis_gecmisi ({PK}, siparis_id INT, durum VARCHAR(20), olusturma VARCHAR(19))",
        "CREATE TABLE odemeler ({PK}, siparis_id INT, tur VARCHAR(20), tutar INT, sonuc VARCHAR(20), aciklama TEXT, olusturma VARCHAR(19))",
        "CREATE TABLE mesajlar ({PK}, ad VARCHAR(160), telefon VARCHAR(30), eposta VARCHAR(160), konu VARCHAR(80), mesaj TEXT, okundu INT DEFAULT 0, olusturma VARCHAR(19))",
        "CREATE TABLE epostalar ({PK}, kime VARCHAR(160), konu VARCHAR(200), icerik TEXT, gonderildi INT DEFAULT 0, olusturma VARCHAR(19))",
    ];
}

function sema_ilk_veriler(): void
{
    $simdi = date('Y-m-d H:i:s');

    // TEMSİLİ ayarlar: yönetim panelinden değiştirilir.
    $ayarlar = [
        'marka' => 'Örnek Şarküteri',
        'telefon' => '0 (354) 000 00 00',
        'eposta' => 'bilgi@ornek.com',
        'adres' => 'Örnek Mah. Örnek Cad. No: 1, Merkez / Yozgat',
        'calisma_saatleri' => 'Her gün 08.00 – 20.00',
        'teslimat_bolgesi' => 'Yozgat şehir merkezi',
        'teslimat_ucreti' => '5000',        // kuruş (50,00 ₺) – temsili
        'ucretsiz_teslimat' => '100000',    // kuruş (1.000,00 ₺) – temsili
        'bildirim_eposta' => 'bilgi@ornek.com',
        'teslimat_gunleri' => '1,2,3,4,5,6,7',  // 1 = Pazartesi … 7 = Pazar
        'ileri_gun' => '3',                 // en fazla kaç gün sonrasına sipariş
        'son_siparis_dk' => '60',           // dilim başlamadan en geç kaç dakika önce
        'stok_uyari' => '5',
        'kapida_odeme' => '1',              // 1: kapıda ödeme seçeneği açık
        'odeme_suresi_dk' => '60',          // ödenmeyen sipariş kaç dakika sonra kendiliğinden kapansın
    ];
    foreach ($ayarlar as $k => $v) {
        q("INSERT INTO ayarlar (anahtar, deger) VALUES (?, ?)", [$k, $v]);
    }

    // İlk yönetici: kullanıcı adı "admin", şifre inc/ayar.php içindeki YONETICI_ILK_SIFRE.
    q("INSERT INTO yoneticiler (kullanici_adi, sifre, olusturma) VALUES (?, ?, ?)",
        ['admin', password_hash(YONETICI_ILK_SIFRE, PASSWORD_DEFAULT), $simdi]);

    foreach (['10:00-12:00', '12:00-14:00', '14:00-16:00', '16:00-18:00', '18:00-20:00'] as $d) {
        [$b, $s] = explode('-', $d);
        q("INSERT INTO teslimat_dilimleri (baslangic, bitis, kapasite, aktif) VALUES (?, ?, 5, 1)", [$b, $s]);
    }

    // TEMSİLİ kategori bilgileri ve ürünler (fiyat kuruş cinsinden).
    $bilgi = [
        'Sucuk' => ['Dana eti, dana yağı, sarımsak, tuz, baharat karışımı', 'Buzdolabında 0–4 °C arasında saklayın.', 'Paketi açtıktan sonra 7 gün içinde tüketin.'],
        'Pastırma' => ['Dana eti, çemen (sarımsak, çemen otu, kırmızı biber), tuz', 'Buzdolabında 0–4 °C arasında saklayın.', 'Paketi açtıktan sonra 5 gün içinde tüketin.'],
        'Salam ve Sosis' => ['Dana eti, su, nişasta, tuz, baharat karışımı', 'Buzdolabında 0–4 °C arasında saklayın.', 'Paketi açtıktan sonra 3 gün içinde tüketin.'],
        'Peynir' => ['Pastörize süt, peynir mayası, tuz', 'Buzdolabında 2–6 °C arasında, kapalı kapta saklayın.', 'Paketi açtıktan sonra 10 gün içinde tüketin.'],
        'Zeytin' => ['Zeytin, su, tuz, limon', 'Serin ve kuru yerde, açtıktan sonra buzdolabında saklayın.', 'Paketi açtıktan sonra 30 gün içinde tüketin.'],
        'Kahvaltılık' => ['Ürün etiketinde belirtilmiştir.', 'Etiketteki saklama koşullarına uyun.', 'Son tüketim tarihi ambalaj üzerindedir.'],
    ];
    $katId = [];
    $sira = 1;
    foreach ($bilgi as $ad => $_) {
        $katId[$ad] = ekle("INSERT INTO kategoriler (ad, sira, aktif) VALUES (?, ?, 1)", [$ad, $sira++]);
    }
    $urunler = [
        ['kangal-sucuk', 'Kangal sucuk', 'Sucuk', 'Dana, orta acılı', '250 g', 45000, 20],
        ['parmak-sucuk', 'Parmak sucuk', 'Sucuk', 'Dana, acısız', '250 g', 42000, 15],
        ['pastirma', 'Pastırma', 'Pastırma', 'Çemenli, ince dilim', '100 g', 60000, 12],
        ['dana-salam', 'Dana salam', 'Salam ve Sosis', 'Dilimlenmiş', '200 g', 22000, 18],
        ['dana-sosis', 'Dana sosis', 'Salam ve Sosis', 'Kahvaltılık', '250 g', 24000, 4],
        ['eski-kasar', 'Eski kaşar', 'Peynir', 'Olgunlaştırılmış', '300 g', 38000, 10],
        ['ezine-peyniri', 'Ezine peyniri', 'Peynir', 'Tam yağlı', '250 g', 32000, 14],
        ['yesil-zeytin', 'Yeşil zeytin', 'Zeytin', 'Kırma, limonlu', '400 g', 18000, 25],
        ['siyah-zeytin', 'Siyah zeytin', 'Zeytin', 'Gemlik tipi', '400 g', 20000, 0],
        ['tereyagi', 'Tereyağı', 'Kahvaltılık', 'Köy tipi', '250 g', 35000, 8],
        ['bal', 'Süzme bal', 'Kahvaltılık', 'Çiçek balı', '450 g', 40000, 9],
    ];
    foreach ($urunler as $u) {
        [$gorsel, $ad, $kat, $kisa, $paket, $fiyat, $stok] = $u;
        q("INSERT INTO urunler (kategori_id, ad, kisa, paket, fiyat, stok, gorsel, icindekiler, saklama, raf, aktif, olusturma)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)",
            [$katId[$kat], $ad, $kisa, $paket, $fiyat, $stok, 'assets/img/' . $gorsel . '.svg', $bilgi[$kat][0], $bilgi[$kat][1], $bilgi[$kat][2], $simdi]);
    }
}
