<?php
/* Yalnızca yerelde "php -S" ile çalıştırırken kullanılır: gizli klasörleri kapatır. Hosting'de .htaccess aynı işi yapar. */
$yol = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('~^/(inc|veri)(/|$)~', $yol) || preg_match('~^/assets/yukleme/.*\.(php|phtml|phar)$~i', $yol) || $yol === '/router.php') {
    http_response_code(403);
    exit('Erişim yok.');
}
// Klasör adresi sonunda / olmadan yazılırsa (ör. /yonetim) sonuna / ekle; yoksa sayfa içi bağlantılar yanlış yere gider.
if (substr($yol, -1) !== '/' && is_dir(__DIR__ . $yol)) {
    $sorgu = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    header('Location: ' . $yol . '/' . ($sorgu ? '?' . $sorgu : ''), true, 301);
    exit;
}
return false; // dosyayı normal biçimde sun
