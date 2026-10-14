<?php
/*
 * PayTR bağlantısı (iFrame API).
 * Kaynak: https://dev.paytr.com/iframe-api  ve  https://dev.paytr.com/iade-api
 * Mağaza bilgileri inc/ayar.php dosyasına yazılır. Bilgiler boşken site deneme ödemesi modunda çalışır.
 */

function paytr_aktif(): bool
{
    return PAYTR_MAGAZA_NO !== '' && PAYTR_ANAHTAR !== '' && PAYTR_GIZLI_ANAHTAR !== '';
}

function paytr_istek(string $adres, array $alanlar): ?array
{
    $ch = curl_init($adres);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $alanlar,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $yanit = curl_exec($ch);
    curl_close($ch);
    $veri = $yanit === false ? null : json_decode($yanit, true);
    return is_array($veri) ? $veri : null;
}

/* Sipariş için PayTR'den ödeme formu anahtarı alır. Başarısızsa ['hata' => '...'] döner. */
function paytr_token_al(array $siparis, array $kalemler): array
{
    $sepet = [];
    foreach ($kalemler as $k) {
        $sepet[] = [$k['urun_adi'], number_format($k['fiyat'] / 100, 2, '.', ''), (int) $k['adet']];
    }
    if ((int) $siparis['teslimat_ucreti'] > 0) {
        $sepet[] = ['Teslimat', number_format($siparis['teslimat_ucreti'] / 100, 2, '.', ''), 1];
    }
    $kullaniciSepeti = base64_encode(json_encode($sepet, JSON_UNESCAPED_UNICODE));
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $tutar = (int) $siparis['toplam'];      // kuruş
    $taksitYok = 1;
    $azamiTaksit = 0;
    $paraBirimi = 'TL';
    $test = (int) PAYTR_TEST_MODU;

    $ham = PAYTR_MAGAZA_NO . $ip . $siparis['no'] . $siparis['eposta'] . $tutar . $kullaniciSepeti
        . $taksitYok . $azamiTaksit . $paraBirimi . $test;
    $imza = base64_encode(hash_hmac('sha256', $ham . PAYTR_GIZLI_ANAHTAR, PAYTR_ANAHTAR, true));

    $yanit = paytr_istek('https://www.paytr.com/odeme/api/get-token', [
        'merchant_id' => PAYTR_MAGAZA_NO,
        'user_ip' => $ip,
        'merchant_oid' => $siparis['no'],
        'email' => $siparis['eposta'],
        'payment_amount' => $tutar,
        'paytr_token' => $imza,
        'user_basket' => $kullaniciSepeti,
        'debug_on' => $test,
        'no_installment' => $taksitYok,
        'max_installment' => $azamiTaksit,
        'user_name' => $siparis['ad'],
        'user_address' => $siparis['adres'],
        'user_phone' => $siparis['telefon'],
        'merchant_ok_url' => siparis_adresi($siparis),
        'merchant_fail_url' => siparis_adresi($siparis),
        'timeout_limit' => 30,
        'currency' => $paraBirimi,
        'test_mode' => $test,
        'lang' => 'tr',
    ]);
    if ($yanit && ($yanit['status'] ?? '') === 'success') {
        return ['token' => $yanit['token']];
    }
    return ['hata' => $yanit['reason'] ?? 'PayTR ile bağlantı kurulamadı.'];
}

/* PayTR bildiriminin gerçekten PayTR'den geldiğini doğrular. */
function paytr_bildirim_gecerli(array $p): bool
{
    $beklenen = base64_encode(hash_hmac('sha256',
        ($p['merchant_oid'] ?? '') . PAYTR_GIZLI_ANAHTAR . ($p['status'] ?? '') . ($p['total_amount'] ?? ''),
        PAYTR_ANAHTAR, true));
    return hash_equals($beklenen, (string) ($p['hash'] ?? ''));
}

/* Siparişin tamamını iade eder. Başarılıysa true, değilse hata metni döner. */
function paytr_iade(array $siparis)
{
    $tutar = number_format($siparis['toplam'] / 100, 2, '.', '');
    $imza = base64_encode(hash_hmac('sha256',
        PAYTR_MAGAZA_NO . $siparis['no'] . $tutar . PAYTR_GIZLI_ANAHTAR, PAYTR_ANAHTAR, true));
    $yanit = paytr_istek('https://www.paytr.com/odeme/iade', [
        'merchant_id' => PAYTR_MAGAZA_NO,
        'merchant_oid' => $siparis['no'],
        'return_amount' => $tutar,
        'paytr_token' => $imza,
    ]);
    if ($yanit && ($yanit['status'] ?? '') === 'success') {
        return true;
    }
    return $yanit['err_msg'] ?? 'PayTR iade isteğine yanıt vermedi.';
}
