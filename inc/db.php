<?php
/* Veritabanı bağlantısı ve kısa yardımcılar. */
if (!is_file(__DIR__ . '/ayar.php')) {
    http_response_code(500);
    exit('Ayar dosyası yok: inc/ayar.ornek.php dosyasını inc/ayar.php adıyla kopyalayıp doldurun.');
}
require_once __DIR__ . '/ayar.php';
// Eski ayar dosyalarında olmayan değerler için güvenli varsayılanlar
defined('YONETICI_ILK_SIFRE') || define('YONETICI_ILK_SIFRE', '');
defined('DENEME_ODEMESI') || define('DENEME_ODEMESI', false);
defined('HATA_GOSTER') || define('HATA_GOSTER', false);
ini_set('display_errors', HATA_GOSTER ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/sema.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    if (DB_TUR === 'mysql') {
        $pdo = new PDO('mysql:host=' . DB_SUNUCU . ';dbname=' . DB_AD . ';charset=utf8mb4', DB_KULLANICI, DB_SIFRE);
    } else {
        if (!extension_loaded('pdo_sqlite')) {
            exit('PHP kurulumunda pdo_sqlite eklentisi kapalı. php.ini dosyasında "extension=pdo_sqlite" satırının başındaki noktalı virgülü kaldırıp yeniden deneyin.');
        }
        $klasor = __DIR__ . '/../veri';
        if (!is_dir($klasor)) {
            mkdir($klasor, 0775, true);
        }
        $pdo = new PDO('sqlite:' . $klasor . '/site.sqlite');
        $pdo->exec('PRAGMA journal_mode = DELETE');
        $pdo->exec('PRAGMA busy_timeout = 5000');
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    db_kurulum($pdo);
    return $pdo;
}

/* Tablolar yoksa oluşturur ve ilk verileri ekler. */
function db_kurulum(PDO $pdo): void
{
    try {
        $pdo->query('SELECT 1 FROM ayarlar LIMIT 1');
        db_guncelle($pdo);
        return; // kurulu
    } catch (PDOException $e) {
        // tablo yok: kur
    }
    if (strlen(YONETICI_ILK_SIFRE) < 10) {
        http_response_code(500);
        exit('Kurulum için inc/ayar.php dosyasında YONETICI_ILK_SIFRE değerini (en az 10 karakter) yazın.');
    }
    $pk = DB_TUR === 'mysql' ? 'id INT AUTO_INCREMENT PRIMARY KEY' : 'id INTEGER PRIMARY KEY AUTOINCREMENT';
    $ek = DB_TUR === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
    foreach (sema_tablolar() as $sql) {
        $pdo->exec(str_replace('{PK}', $pk, $sql) . $ek);
    }
    $GLOBALS['__kurulum_pdo'] = $pdo;
    sema_ilk_veriler();
    unset($GLOBALS['__kurulum_pdo']);
}

/* Sonradan eklenen sütunları, mevcut verilere dokunmadan ekler. */
function db_guncelle(PDO $pdo): void
{
    try {
        $pdo->query('SELECT ust_id FROM kategoriler LIMIT 1');
    } catch (PDOException $e) {
        // alt kategori desteği: 0 = ana kategori, değilse bağlı olduğu ana kategorinin numarası
        $pdo->exec('ALTER TABLE kategoriler ADD COLUMN ust_id INT DEFAULT 0');
        $pdo->exec('UPDATE kategoriler SET ust_id = 0 WHERE ust_id IS NULL');
    }
    // tek tek eklenen sütunlar: [tablo, sütun, tanım]
    foreach ([
        ['musteriler', 'sifirlama_kodu', 'VARCHAR(64)'],
        ['musteriler', 'sifirlama_bitis', 'VARCHAR(19)'],
        ['siparisler', 'odeme_yontemi', "VARCHAR(10) DEFAULT 'kart'"],
    ] as [$tablo, $sutun, $tanim]) {
        try {
            $pdo->query("SELECT $sutun FROM $tablo LIMIT 1");
        } catch (PDOException $e) {
            $pdo->exec("ALTER TABLE $tablo ADD COLUMN $sutun $tanim");
            if ($sutun === 'odeme_yontemi') {
                $pdo->exec("UPDATE siparisler SET odeme_yontemi = 'kart' WHERE odeme_yontemi IS NULL");
            }
        }
    }
    try {
        $pdo->query('SELECT 1 FROM siparis_gecmisi LIMIT 1');
    } catch (PDOException $e) {
        // sipariş aşamalarının saatleri (müşterinin gördüğü takip çizelgesi için)
        $pk = DB_TUR === 'mysql' ? 'id INT AUTO_INCREMENT PRIMARY KEY' : 'id INTEGER PRIMARY KEY AUTOINCREMENT';
        $pdo->exec('CREATE TABLE siparis_gecmisi (' . $pk . ', siparis_id INT, durum VARCHAR(20), olusturma VARCHAR(19))'
            . (DB_TUR === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : ''));
    }
}

function q(string $sql, array $p = []): PDOStatement
{
    $pdo = $GLOBALS['__kurulum_pdo'] ?? db();
    $s = $pdo->prepare($sql);
    $s->execute($p);
    return $s;
}
function satir(string $sql, array $p = []): ?array
{
    $r = q($sql, $p)->fetch();
    return $r === false ? null : $r;
}
function satirlar(string $sql, array $p = []): array
{
    return q($sql, $p)->fetchAll();
}
function deger(string $sql, array $p = [])
{
    $r = q($sql, $p)->fetch(PDO::FETCH_NUM);
    return $r === false ? null : $r[0];
}
function ekle(string $sql, array $p = []): int
{
    q($sql, $p);
    $pdo = $GLOBALS['__kurulum_pdo'] ?? db();
    return (int) $pdo->lastInsertId();
}
