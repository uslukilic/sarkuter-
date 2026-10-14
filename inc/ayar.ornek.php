<?php
/*
 * SİTE AYAR DOSYASI – ÖRNEK
 * Bu dosyayı aynı klasöre "ayar.php" adıyla kopyalayıp doldurun.
 * ayar.php gizli bilgiler içerdiği için git'e eklenmez (.gitignore).
 *
 * Marka adı, telefon, teslimat ücreti gibi bilgiler yönetim panelinden (Ayarlar) değişir.
 * Burada yalnızca teknik ayarlar ve gizli anahtarlar bulunur.
 */

// --- Veritabanı ---
// 'sqlite' : kurulum gerektirmez, veri/ klasöründe tek dosyada durur (yerelde denemek için).
// 'mysql'  : hosting'de bunu seçip aşağıdaki bilgileri doldurun. Tablolar ilk açılışta kendiliğinden oluşur.
define('DB_TUR', 'mysql');
define('DB_SUNUCU', 'localhost');
define('DB_AD', 'sarkuteri');
define('DB_KULLANICI', 'KULLANICI');
define('DB_SIFRE', 'SIFRE');

// --- İlk yönetici şifresi ---
// Veritabanı ilk kez kurulurken "admin" kullanıcısı bu şifreyle oluşturulur (en az 10 karakter).
// Kurulumdan sonra panelden şifreyi değiştirip buradaki değeri silebilirsiniz.
define('YONETICI_ILK_SIFRE', '');

// --- PayTR mağaza bilgileri (PayTR mağaza panelinden alınır) ---
define('PAYTR_MAGAZA_NO', '');
define('PAYTR_ANAHTAR', '');
define('PAYTR_GIZLI_ANAHTAR', '');
define('PAYTR_TEST_MODU', 0);   // 1: PayTR test modu, 0: gerçek ödeme

// --- Deneme ödemesi ---
// true iken ve PayTR bilgileri boşken ödeme sayfasında "Ödeme başarılı say" düğmesi çıkar.
// CANLIDA MUTLAKA false kalmalı; aksi hâlde herkes siparişini ödenmiş sayabilir.
define('DENEME_ODEMESI', false);

// --- Hata gösterimi ---
// true: PHP hataları sayfada görünür (yalnızca yerelde). Canlıda false: hatalar sunucu günlüğüne yazılır.
define('HATA_GOSTER', false);

// --- E-posta ---
// Gönderen adresi; kendi alan adınızda bir adres olmalı (ör. siparis@alanadiniz.com).
// Tüm e-postalar ayrıca yönetim panelindeki "E-postalar" sayfasına kaydedilir.
define('EPOSTA_GONDEREN', 'siparis@ornek.com');

date_default_timezone_set('Europe/Istanbul');
