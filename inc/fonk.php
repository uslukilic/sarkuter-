<?php
/* Ortak fonksiyonlar: oturum, güvenlik, sepet, sipariş, teslimat dilimleri, e-posta. */
require_once __DIR__ . '/db.php';
if (!extension_loaded('mbstring')) {
    exit('PHP kurulumunda mbstring eklentisi kapalı. php.ini dosyasında "extension=mbstring" satırını açıp yeniden deneyin.');
}

// ---------- Oturum ----------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_name('sarkuteri');
    session_start();
}
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

// ---------- Genel yardımcılar ----------
function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
/* Kuruş cinsinden tutarı "1.234,50 ₺" biçiminde yazar. */
function tl($kurus): string
{
    return number_format(((int) $kurus) / 100, 2, ',', '.') . ' ₺';
}
/* "12,50" ya da "12.50" yazılan tutarı kuruşa çevirir. */
function kurusa($yazi): int
{
    $y = trim((string) $yazi);
    if (strpos($y, ',') !== false) {
        $y = str_replace('.', '', $y);
        $y = str_replace(',', '.', $y);
    }
    return (int) round(((float) $y) * 100);
}
function simdi(): string
{
    return date('Y-m-d H:i:s');
}
function tarih_yaz(?string $t, bool $saat = true): string
{
    if (!$t) {
        return '';
    }
    $z = strtotime($t);
    return $z ? date($saat ? 'd.m.Y H:i' : 'd.m.Y', $z) : '';
}
function gun_adi(string $tarih): string
{
    $adlar = [1 => 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
    return $adlar[(int) date('N', strtotime($tarih))];
}
function yonlendir(string $adres): void
{
    header('Location: ' . $adres);
    exit;
}
function post(string $ad, string $varsayilan = ''): string
{
    return isset($_POST[$ad]) && is_string($_POST[$ad]) ? trim($_POST[$ad]) : $varsayilan;
}
function kisalt(string $s, int $n): string
{
    return mb_substr($s, 0, $n);
}
/* Sitenin kök adresi (ör. http://localhost:8000). */
function site_adresi(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $kok = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if (substr($kok, -8) === '/yonetim') {
        $kok = substr($kok, 0, -8);
    }
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $kok;
}

// ---------- Bildirim mesajları (bir sonraki sayfada bir kez görünür) ----------
function mesaj_yaz(string $metin, string $tur = 'bilgi'): void
{
    $_SESSION['mesajlar'][] = [$tur, $metin];
}
function mesajlari_al(): array
{
    $m = $_SESSION['mesajlar'] ?? [];
    unset($_SESSION['mesajlar']);
    return $m;
}

// ---------- Form güvenliği (CSRF) ----------
function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(20));
    }
    return $_SESSION['csrf'];
}
function csrf_alan(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">';
}
function csrf_kontrol(): void
{
    if (!hash_equals(csrf(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Oturum süresi dolmuş olabilir. Lütfen sayfayı yenileyip tekrar deneyin.');
    }
}
/* Aynı oturumdan çok sık giriş denemesini yavaşlatır. */
function deneme_siniri(string $ad): bool
{
    $k = 'deneme_' . $ad;
    $d = $_SESSION[$k] ?? ['sayi' => 0, 'zaman' => time()];
    if (time() - $d['zaman'] > 600) {
        $d = ['sayi' => 0, 'zaman' => time()];
    }
    $d['sayi']++;
    $_SESSION[$k] = $d;
    return $d['sayi'] <= 8;
}

// ---------- Ayarlar ----------
function ayar(string $anahtar, string $varsayilan = ''): string
{
    static $hepsi = null;
    if ($hepsi === null || $anahtar === '__yenile') {
        $hepsi = [];
        foreach (satirlar("SELECT anahtar, deger FROM ayarlar") as $r) {
            $hepsi[$r['anahtar']] = (string) $r['deger'];
        }
    }
    return $hepsi[$anahtar] ?? $varsayilan;
}
function ayar_yaz(string $anahtar, string $deger): void
{
    if (deger("SELECT COUNT(*) FROM ayarlar WHERE anahtar = ?", [$anahtar])) {
        q("UPDATE ayarlar SET deger = ? WHERE anahtar = ?", [$deger, $anahtar]);
    } else {
        q("INSERT INTO ayarlar (anahtar, deger) VALUES (?, ?)", [$anahtar, $deger]);
    }
    ayar('__yenile');
}

// ---------- Giriş yapan müşteri / yönetici ----------
function musteri(): ?array
{
    static $m = false;
    if ($m === false) {
        $m = empty($_SESSION['musteri_id']) ? null : satir("SELECT * FROM musteriler WHERE id = ?", [$_SESSION['musteri_id']]);
    }
    return $m;
}
function yonetici(): ?array
{
    return empty($_SESSION['yonetici_id']) ? null : satir("SELECT * FROM yoneticiler WHERE id = ?", [$_SESSION['yonetici_id']]);
}

// ---------- Kategoriler (ana kategori ve alt kategori) ----------
/* Ana kategorileri, her birinin 'alt' anahtarında alt kategorileriyle döndürür. */
function kategori_agaci(bool $sadeceGorunur = true): array
{
    $hepsi = satirlar("SELECT * FROM kategoriler" . ($sadeceGorunur ? " WHERE aktif = 1" : "") . " ORDER BY sira, ad");
    $agac = [];
    foreach ($hepsi as $k) {
        if ((int) $k['ust_id'] === 0) {
            $k['alt'] = [];
            $agac[(int) $k['id']] = $k;
        }
    }
    foreach ($hepsi as $k) {
        if ((int) $k['ust_id'] !== 0 && isset($agac[(int) $k['ust_id']])) {
            $agac[(int) $k['ust_id']]['alt'][] = $k;
        }
    }
    return array_values($agac);
}

// ---------- Ürünler ----------
function urun_getir(int $id): ?array
{
    return satir("SELECT u.*, k.ad AS kategori, k.ust_id AS ust_kategori_id, p.ad AS ust_kategori
        FROM urunler u LEFT JOIN kategoriler k ON k.id = u.kategori_id LEFT JOIN kategoriler p ON p.id = k.ust_id WHERE u.id = ?", [$id]);
}

// ---------- Sepet (oturumda saklanır: [urun_id => adet]) ----------
function sepet(): array
{
    return $_SESSION['sepet'] ?? [];
}
function sepet_adet_ayarla(int $urunId, int $adet): void
{
    if ($adet <= 0) {
        unset($_SESSION['sepet'][$urunId]);
    } else {
        $_SESSION['sepet'][$urunId] = min(99, $adet);
    }
}
/* Sepeti veritabanındaki güncel fiyat ve stokla hesaplar. Satışta olmayan ürünleri çıkarır. */
function sepet_hesap(): array
{
    $kalemler = [];
    $ara = 0;
    $adet = 0;
    $uyarilar = [];
    foreach (sepet() as $id => $n) {
        $u = urun_getir((int) $id);
        if (!$u || !$u['aktif'] || (int) $u['stok'] <= 0) {
            unset($_SESSION['sepet'][$id]);
            if ($u) {
                $uyarilar[] = $u['ad'] . ' tükendiği için sepetten çıkarıldı.';
            }
            continue;
        }
        if ($n > (int) $u['stok']) {
            $n = (int) $u['stok'];
            $_SESSION['sepet'][$id] = $n;
            $uyarilar[] = $u['ad'] . ' için stokta ' . $n . ' adet kaldı; adet güncellendi.';
        }
        $tutar = (int) $u['fiyat'] * $n;
        $kalemler[] = ['urun' => $u, 'adet' => $n, 'tutar' => $tutar];
        $ara += $tutar;
        $adet += $n;
    }
    $esik = (int) ayar('ucretsiz_teslimat');
    $teslimat = ($ara === 0 || ($esik > 0 && $ara >= $esik)) ? 0 : (int) ayar('teslimat_ucreti');
    return ['kalemler' => $kalemler, 'adet' => $adet, 'ara' => $ara, 'teslimat' => $teslimat,
        'toplam' => $ara + $teslimat, 'esik' => $esik, 'uyarilar' => $uyarilar];
}
function sepet_adedi(): int
{
    return array_sum(sepet());
}

// ---------- Teslimat dilimleri ----------
/* Seçilebilir gün ve saat aralıklarını döndürür: [['tarih'=>..., 'etiket'=>..., 'dilimler'=>[['saat'=>'10:00-12:00','dolu'=>bool]]]] */
function teslimat_secenekleri(): array
{
    $gunler = array_filter(explode(',', ayar('teslimat_gunleri', '1,2,3,4,5,6,7')));
    $ileri = max(0, (int) ayar('ileri_gun', '3'));
    $sonDk = max(0, (int) ayar('son_siparis_dk', '60'));
    $dilimler = satirlar("SELECT * FROM teslimat_dilimleri WHERE aktif = 1 ORDER BY baslangic");
    $sonuc = [];
    for ($i = 0; $i <= $ileri; $i++) {
        $tarih = date('Y-m-d', strtotime("+$i day"));
        if (!in_array(date('N', strtotime($tarih)), $gunler)) {
            continue;
        }
        $liste = [];
        foreach ($dilimler as $d) {
            $baslangic = strtotime($tarih . ' ' . $d['baslangic']);
            if ($baslangic - $sonDk * 60 < time()) {
                continue; // bu dilim için geç kalındı
            }
            $saat = $d['baslangic'] . '-' . $d['bitis'];
            $liste[] = ['saat' => $saat, 'dolu' => dilim_dolu($tarih, $saat, (int) $d['kapasite'])];
        }
        if ($liste) {
            $etiket = $i === 0 ? 'Bugün' : ($i === 1 ? 'Yarın' : gun_adi($tarih));
            $sonuc[] = ['tarih' => $tarih, 'etiket' => $etiket . ' · ' . date('d.m', strtotime($tarih)), 'dilimler' => $liste];
        }
    }
    return $sonuc;
}
function dilim_dolu(string $tarih, string $saat, int $kapasite): bool
{
    $n = (int) deger("SELECT COUNT(*) FROM siparisler WHERE teslimat_tarihi = ? AND teslimat_saati = ?
        AND durum IN ('onay_bekliyor','onaylandi','yolda','teslim_edildi')", [$tarih, $saat]);
    return $n >= $kapasite;
}
/* Müşterinin seçtiği gün ve saat hâlâ geçerli mi? */
function dilim_gecerli(string $tarih, string $saat): bool
{
    foreach (teslimat_secenekleri() as $g) {
        if ($g['tarih'] !== $tarih) {
            continue;
        }
        foreach ($g['dilimler'] as $d) {
            if ($d['saat'] === $saat && !$d['dolu']) {
                return true;
            }
        }
    }
    return false;
}

// ---------- Siparişler ----------
function durumlar(): array
{
    return [
        'odeme_bekliyor' => 'Ödeme bekliyor',
        'odeme_basarisiz' => 'Ödeme başarısız',
        'onay_bekliyor' => 'Onay bekliyor',
        'onaylandi' => 'Onaylandı, hazırlanıyor',
        'yolda' => 'Yolda',
        'teslim_edildi' => 'Teslim edildi',
        'reddedildi' => 'Reddedildi',
        'iptal' => 'İptal edildi',
    ];
}
function durum_adi(string $d): string
{
    return durumlar()[$d] ?? $d;
}
function durum_rozet(string $d): string
{
    $sinif = ['onay_bekliyor' => 'uyari', 'onaylandi' => 'iyi', 'yolda' => 'iyi', 'teslim_edildi' => 'iyi',
        'reddedildi' => 'kotu', 'iptal' => 'kotu', 'odeme_basarisiz' => 'kotu'][$d] ?? 'notr';
    return '<span class="rozet ' . $sinif . '">' . e(durum_adi($d)) . '</span>';
}
function siparis_getir_no(string $no): ?array
{
    return satir("SELECT * FROM siparisler WHERE no = ?", [$no]);
}
function siparis_kalemleri(int $siparisId): array
{
    return satirlar("SELECT * FROM siparis_kalemleri WHERE siparis_id = ? ORDER BY id", [$siparisId]);
}
function siparis_adresi(array $s): string
{
    return site_adresi() . '/siparis.php?no=' . urlencode($s['no']) . '&k=' . urlencode($s['anahtar']);
}
function siparis_durum_yaz(int $id, string $durum, array $ek = []): void
{
    $alanlar = array_merge(['durum' => $durum, 'guncelleme' => simdi()], $ek);
    $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($alanlar)));
    q("UPDATE siparisler SET $set WHERE id = ?", array_merge(array_values($alanlar), [$id]));
    q("INSERT INTO siparis_gecmisi (siparis_id, durum, olusturma) VALUES (?, ?, ?)", [$id, $durum, simdi()]);
}
/*
 * Müşterinin gördüğü takip çizelgesi: sırasıyla aşamalar ve her birinin durumu.
 * Dönen her eleman: ['ad' => ..., 'durum' => 'tamam' | 'simdi' | 'bekliyor' | 'kotu', 'zaman' => 'Y-m-d H:i:s' | null]
 */
function siparis_asamalari(array $s): array
{
    $sira = ['odeme_bekliyor', 'onay_bekliyor', 'onaylandi', 'yolda', 'teslim_edildi'];
    $adlar = ['Sipariş alındı', kapida_mi($s) ? 'Onay bekliyor (kapıda ödeme)' : 'Ödeme alındı, onay bekliyor', 'Onaylandı, hazırlanıyor', 'Yola çıktı', 'Teslim edildi'];
    $zamanlar = [];
    foreach (satirlar("SELECT durum, olusturma FROM siparis_gecmisi WHERE siparis_id = ? ORDER BY id", [$s['id']]) as $g) {
        $zamanlar[$g['durum']] = $g['olusturma'];
    }
    $zamanlar['odeme_bekliyor'] = $zamanlar['odeme_bekliyor'] ?? $s['olusturma'];
    $olumsuz = ['reddedildi' => 'Reddedildi', 'iptal' => 'İptal edildi', 'odeme_basarisiz' => 'Ödeme alınamadı'];
    $kotu = isset($olumsuz[$s['durum']]);
    if ($kotu) {
        // en son ulaşılan olağan aşamayı bul
        $ulasilan = 0;
        foreach ($sira as $i => $d) {
            if (isset($zamanlar[$d])) {
                $ulasilan = $i;
            }
        }
        if ($s['durum'] === 'reddedildi') {
            $ulasilan = max($ulasilan, 1);
        }
    } else {
        $ulasilan = (int) array_search($s['durum'], $sira, true);
    }
    $liste = [];
    foreach ($sira as $i => $d) {
        if ($kotu && $i > $ulasilan) {
            break;
        }
        $bitti = $i < $ulasilan || $kotu || $s['durum'] === 'teslim_edildi';
        $liste[] = ['ad' => $adlar[$i], 'durum' => $bitti ? 'tamam' : ($i === $ulasilan ? 'simdi' : 'bekliyor'),
            'zaman' => $i <= $ulasilan ? ($zamanlar[$d] ?? null) : null];
    }
    if ($kotu) {
        $liste[] = ['ad' => $olumsuz[$s['durum']], 'durum' => 'kotu', 'zaman' => $zamanlar[$s['durum']] ?? $s['guncelleme']];
    }
    return $liste;
}
/* Takip çizelgesini HTML olarak yazar. */
function siparis_cizelgesi(array $s): string
{
    $h = '<ol class="takip">';
    foreach (siparis_asamalari($s) as $a) {
        $h .= '<li class="' . $a['durum'] . '"' . ($a['durum'] === 'simdi' ? ' aria-current="step"' : '') . '><span class="nokta" aria-hidden="true"></span>'
            . '<div><b>' . e($a['ad']) . '</b>'
            . '<span>' . ($a['zaman'] ? e(tarih_yaz($a['zaman'])) : ($a['durum'] === 'simdi' ? 'Şu an bu aşamada' : 'Bekleniyor')) . '</span></div></li>';
    }
    return $h . '</ol>';
}
/* Ödeme alınınca stoktan düşer (bir sipariş için yalnızca bir kez). */
function stok_dus(array $s): void
{
    if ((int) $s['stok_dusuldu'] === 1) {
        return;
    }
    foreach (siparis_kalemleri((int) $s['id']) as $k) {
        q("UPDATE urunler SET stok = CASE WHEN stok >= ? THEN stok - ? ELSE 0 END WHERE id = ?", [$k['adet'], $k['adet'], $k['urun_id']]);
    }
    q("UPDATE siparisler SET stok_dusuldu = 1 WHERE id = ?", [$s['id']]);
}
/* Ret ya da iptalde stoku geri ekler. */
function stok_geri_ekle(array $s): void
{
    if ((int) $s['stok_dusuldu'] !== 1) {
        return;
    }
    foreach (siparis_kalemleri((int) $s['id']) as $k) {
        q("UPDATE urunler SET stok = stok + ? WHERE id = ?", [$k['adet'], $k['urun_id']]);
    }
    q("UPDATE siparisler SET stok_dusuldu = 0 WHERE id = ?", [$s['id']]);
}
/* Ödeme süresi dolduğu için kendiliğinden kapatılmış sipariş mi? (Geç gelen ödeme bildirimi bunu yeniden açabilir.) */
function sure_dolmus_mu(array $s): bool
{
    return $s['durum'] === 'iptal' && (int) $s['stok_dusuldu'] === 0 && strpos((string) $s['ret_nedeni'], 'Ödeme süresi doldu') === 0;
}
function kapida_mi(array $s): bool
{
    return ($s['odeme_yontemi'] ?? 'kart') === 'kapida';
}
/* Ödeme sonucu geldiğinde çağrılır (PayTR bildirimi ya da deneme ödemesi). Aynı sipariş için tekrar çağrılırsa bir şey yapmaz. */
function odeme_sonucu_isle(array $s, bool $basarili, string $tur, string $aciklama = ''): void
{
    $gecOdeme = $basarili && sure_dolmus_mu($s);
    if ($s['durum'] !== 'odeme_bekliyor' && $s['durum'] !== 'odeme_basarisiz' && !$gecOdeme) {
        return; // daha önce işlenmiş
    }
    q("INSERT INTO odemeler (siparis_id, tur, tutar, sonuc, aciklama, olusturma) VALUES (?, ?, ?, ?, ?, ?)",
        [$s['id'], $tur, $s['toplam'], $basarili ? 'basarili' : 'basarisiz', kisalt($aciklama, 500), simdi()]);
    if (!$basarili) {
        siparis_durum_yaz((int) $s['id'], 'odeme_basarisiz');
        return;
    }
    if ($gecOdeme) {
        q("UPDATE siparisler SET ret_nedeni = NULL WHERE id = ?", [$s['id']]);
    }
    siparis_onaya_al($s);
}
/* Siparişi "onay bekliyor" yapar: stoktan düşer, müşteriye ve dükkâna e-posta gönderir. Kartla ödemede ödeme alınınca, kapıda ödemede sipariş verilince çağrılır. */
function siparis_onaya_al(array $s): void
{
    stok_dus($s);
    siparis_durum_yaz((int) $s['id'], 'onay_bekliyor');
    $s = satir("SELECT * FROM siparisler WHERE id = ?", [$s['id']]);
    $giris = kapida_mi($s)
        ? "Siparişiniz alındı ve onay bekliyor. Ödemeyi teslimatta yapacaksınız. Onaylandığında size haber vereceğiz."
        : "Ödemeniz alındı, siparişiniz onay bekliyor. Onaylandığında size haber vereceğiz.";
    eposta_gonder($s['eposta'], 'Siparişiniz alındı (' . $s['no'] . ')', "Merhaba " . $s['ad'] . ",\n\n" . $giris . "\n\n" . siparis_ozeti_metni($s));
    $bildirim = ayar('bildirim_eposta');
    if ($bildirim !== '') {
        eposta_gonder($bildirim, 'Yeni sipariş: ' . $s['no'] . ' (' . tl($s['toplam']) . (kapida_mi($s) ? ', kapıda ödeme' : '') . ')',
            "Yeni bir sipariş onayınızı bekliyor.\n\n" . siparis_ozeti_metni($s) . "\nYönetim paneli: " . site_adresi() . '/yonetim/siparis.php?id=' . $s['id']);
    }
}
/* Ödemesi belirlenen süre içinde tamamlanmayan siparişleri kendiliğinden kapatır. Stok düşülmediği için stoka dokunmaz. */
function odenmeyenleri_kapat(): void
{
    $dk = (int) ayar('odeme_suresi_dk', '60');
    if ($dk <= 0) {
        return;
    }
    $sinir = date('Y-m-d H:i:s', time() - $dk * 60);
    foreach (satirlar("SELECT id FROM siparisler WHERE durum IN ('odeme_bekliyor','odeme_basarisiz') AND olusturma < ?", [$sinir]) as $r) {
        siparis_durum_yaz((int) $r['id'], 'iptal', ['ret_nedeni' => 'Ödeme süresi doldu: ' . $dk . ' dakika içinde ödeme tamamlanmadı.']);
    }
}
/* Türkçe harflere duyarlı küçük harfe çevirme (arama için). */
function kucuk_harf(string $s): string
{
    return mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], $s), 'UTF-8');
}
function siparis_ozeti_metni(array $s): string
{
    $m = "Sipariş no: " . $s['no'] . "\n";
    foreach (siparis_kalemleri((int) $s['id']) as $k) {
        $m .= "- " . $k['adet'] . " x " . $k['urun_adi'] . " (" . $k['paket'] . "): " . tl($k['fiyat'] * $k['adet']) . "\n";
    }
    $m .= "Teslimat ücreti: " . ($s['teslimat_ucreti'] ? tl($s['teslimat_ucreti']) : 'Ücretsiz') . "\n";
    $m .= "Toplam: " . tl($s['toplam']) . "\n";
    $m .= "Ödeme: " . (kapida_mi($s) ? "Kapıda ödeme (teslimatta ödenecek)" : "Kartla, PayTR üzerinden") . "\n";
    $m .= "Teslimat: " . tarih_yaz($s['teslimat_tarihi'], false) . " " . gun_adi($s['teslimat_tarihi']) . ", " . str_replace('-', ' – ', $s['teslimat_saati']) . "\n";
    $m .= "Adres: " . $s['adres'] . "\n";
    $m .= "Sipariş durumu: " . siparis_adresi($s) . "\n";
    return $m;
}

// ---------- E-posta ----------
/* E-postayı göndermeyi dener ve her durumda yönetim panelindeki "E-postalar" sayfasına kaydeder. */
function eposta_gonder(string $kime, string $konu, string $icerik): void
{
    $marka = ayar('marka');
    $icerik .= "\n\n" . $marka;
    $gitti = 0;
    if (filter_var($kime, FILTER_VALIDATE_EMAIL)) {
        $baslik = "From: " . mb_encode_mimeheader($marka, 'UTF-8') . " <" . EPOSTA_GONDEREN . ">\r\n"
            . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        $gitti = @mail($kime, mb_encode_mimeheader($konu, 'UTF-8'), $icerik, $baslik) ? 1 : 0;
    }
    q("INSERT INTO epostalar (kime, konu, icerik, gonderildi, olusturma) VALUES (?, ?, ?, ?, ?)",
        [kisalt($kime, 160), kisalt($konu, 200), $icerik, $gitti, simdi()]);
}

odenmeyenleri_kapat();
