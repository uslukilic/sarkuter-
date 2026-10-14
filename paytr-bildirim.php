<?php
/*
 * PayTR ödeme sonucunu bu adrese bildirir (PayTR mağaza panelinde "Bildirim URL" olarak girilir).
 * Yanıt olarak yalnızca "OK" yazılmalıdır.
 */
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/paytr.php';
// Bu sayfada oturum açılmaz; fonk.php'deki sipariş fonksiyonlarına ihtiyaç var.
require __DIR__ . '/inc/fonk.php';
header_remove('Set-Cookie');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !paytr_aktif() || !paytr_bildirim_gecerli($_POST)) {
    http_response_code(400);
    exit('PAYTR notification failed: bad hash');
}
$s = siparis_getir_no((string) $_POST['merchant_oid']);
if ($s) {
    $basarili = ($_POST['status'] ?? '') === 'success';
    $aciklama = $basarili ? 'PayTR: ödeme alındı' : 'PayTR: ' . ($_POST['failed_reason_code'] ?? '') . ' ' . ($_POST['failed_reason_msg'] ?? '');
    // Tutar siparişle uyuşmuyorsa ödemeyi onaylama
    if ($basarili && (int) ($_POST['payment_amount'] ?? $_POST['total_amount'] ?? 0) < (int) $s['toplam']) {
        $basarili = false;
        $aciklama = 'PayTR tutarı siparişle uyuşmuyor';
    }
    odeme_sonucu_isle($s, $basarili, 'paytr', $aciklama);
}
echo 'OK';
