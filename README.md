# Şarküteri – Online Sipariş Sitesi

PHP ile yazılmış, çerçeve (framework) kullanmayan şarküteri e-ticaret sitesi: ürün kataloğu, sepet, teslimat saati seçimi, PayTR ile kartla ödeme, kapıda ödeme, müşteri hesabı, sipariş takibi ve yönetim paneli.

## Gereksinimler

- PHP 7.4 veya üstü (8.x önerilir)
- PHP eklentileri: `pdo_mysql` (ya da `pdo_sqlite`), `mbstring`, `curl`, `openssl`
- Apache + `mod_rewrite` (paylaşımlı hostinglerin çoğunda hazır gelir)
- MySQL 5.7+ / MariaDB 10.3+ (canlı için önerilen)

## Klasörler

| Klasör / dosya | Ne işe yarar |
| --- | --- |
| `inc/` | Ayarlar ve ortak kodlar (tarayıcıdan erişilemez) |
| `inc/ayar.ornek.php` | Ayar dosyası örneği – `inc/ayar.php` olarak kopyalanır |
| `yonetim/` | Yönetim paneli |
| `assets/` | Stil, betik, görseller; `assets/yukleme/` panelden yüklenen ürün fotoğrafları |
| `veri/` | SQLite kullanılırsa veritabanı dosyası (tarayıcıdan erişilemez) |
| `paytr-bildirim.php` | PayTR ödeme sonucu bildirim adresi |
| `router.php`, `baslat.bat` | Yalnızca yerel geliştirme içindir |

## Canlıya kurulum (paylaşımlı hosting / cPanel / Plesk)

1. **Veritabanı oluşturun.** Hosting panelinden bir MySQL veritabanı ve kullanıcısı açın, kullanıcıya veritabanında tüm yetkileri verin. Karakter seti `utf8mb4` olmalı.

2. **Dosyaları yükleyin.** Depodaki dosyaları `public_html` (ya da alan adınızın kök klasörü) içine yükleyin. Git destekli hostingte:
   ```bash
   git clone https://github.com/KULLANICI/DEPO.git .
   ```
   FTP ile yüklüyorsanız `router.php`, `baslat.bat`, `README.md` ve `.git` klasörünü yüklemenize gerek yok.

3. **Ayar dosyasını oluşturun.** `inc/ayar.ornek.php` dosyasını `inc/ayar.php` adıyla kopyalayıp doldurun:

   | Ayar | Değer |
   | --- | --- |
   | `DB_TUR` | `'mysql'` |
   | `DB_SUNUCU`, `DB_AD`, `DB_KULLANICI`, `DB_SIFRE` | 1. adımdaki bilgiler |
   | `YONETICI_ILK_SIFRE` | Panel için güçlü bir şifre (en az 10 karakter) |
   | `PAYTR_MAGAZA_NO`, `PAYTR_ANAHTAR`, `PAYTR_GIZLI_ANAHTAR` | PayTR mağaza panelinden |
   | `PAYTR_TEST_MODU` | Önce `1` ile deneyin, sonra `0` |
   | `DENEME_ODEMESI` | **`false`** |
   | `HATA_GOSTER` | **`false`** |
   | `EPOSTA_GONDEREN` | Alan adınızda bir adres, ör. `siparis@alanadiniz.com` |

4. **Klasör izinleri.** `assets/yukleme/` klasörü PHP tarafından yazılabilir olmalı (genelde `755`; olmazsa `775`). SQLite kullanacaksanız `veri/` için de aynısı geçerli.

5. **İlk açılış.** Siteyi tarayıcıda açın. Tablolar ve örnek ürünler ilk açılışta kendiliğinden oluşur.

6. **Yönetim paneli.** `https://ALANADINIZ/yonetim/` adresinden `admin` kullanıcı adı ve `YONETICI_ILK_SIFRE` ile girin. Sonra:
   - Sağ üstten şifreyi değiştirin ve `inc/ayar.php` içindeki `YONETICI_ILK_SIFRE` değerini silin.
   - **Ayarlar** sayfasından marka adı, telefon, adres, teslimat ücreti ve yasal metinleri (KVKK, mesafeli satış sözleşmesi vb.) gerçekleriyle değiştirin.
   - Örnek ürünleri düzenleyin ya da pasife alın, kendi ürünlerinizi ekleyin.

7. **PayTR.** PayTR mağaza panelinde **Bildirim URL** olarak şunu girin:
   ```
   https://ALANADINIZ/paytr-bildirim.php
   ```
   Test modunda birkaç sipariş deneyin, sonra `PAYTR_TEST_MODU` değerini `0` yapın.
   PayTR bilgileri girilmemişse kartla ödeme kapalı kalır; yalnızca kapıda ödeme çalışır.

8. **HTTPS.** Hosting panelinden SSL sertifikasını (ör. Let's Encrypt) etkinleştirin, ardından kök klasördeki `.htaccess` içinde HTTPS yönlendirme satırlarının başındaki `#` işaretlerini kaldırın.

9. **E-posta.** Site e-postaları PHP `mail()` ile gönderir. Hosting'inizde gönderen adres (`EPOSTA_GONDEREN`) gerçekten var olmalı; SPF/DKIM kayıtlarının açık olması e-postaların spam'e düşmesini önler. Gönderilen tüm e-postalar panelde **E-postalar** sayfasında da görünür.

### Canlıya almadan önce kontrol listesi

- [ ] `inc/ayar.php` dolduruldu, `DENEME_ODEMESI = false`, `HATA_GOSTER = false`
- [ ] Panel şifresi değiştirildi, `YONETICI_ILK_SIFRE` silindi
- [ ] Marka, iletişim, fiyat ve yasal metinler güncellendi
- [ ] SSL aktif, HTTPS yönlendirmesi açıldı
- [ ] PayTR bildirim URL'si girildi, test siparişi başarılı, test modu kapatıldı
- [ ] `https://ALANADINIZ/inc/ayar.php` ve `https://ALANADINIZ/veri/` adresleri **403** veriyor
- [ ] Test e-postası (ör. şifre sıfırlama) geldi

## Güncelleme

```bash
git pull
```
`inc/ayar.php`, veritabanı ve yüklenen fotoğraflar git'te tutulmadığı için güncellemede bozulmaz. Yeni sütunlar/tablolar ilk açılışta mevcut verilere dokunmadan eklenir. Güncellemeden önce veritabanının yedeğini alın.

## Yerelde çalıştırma (Windows)

1. [XAMPP](https://www.apachefriends.org) kurun (`C:\xampp`). Apache/MySQL'i başlatmanız gerekmez; yalnızca içindeki PHP kullanılır.
2. `inc/ayar.ornek.php` dosyasını `inc/ayar.php` olarak kopyalayın ve şunları yapın:
   `DB_TUR = 'sqlite'`, `YONETICI_ILK_SIFRE` = bir şifre, `DENEME_ODEMESI = true`, `HATA_GOSTER = true`.
3. `baslat.bat` dosyasına çift tıklayın. Site `http://localhost:8000`, panel `http://localhost:8000/yonetim/` adresinde açılır.

Yerelde veritabanı `veri/site.sqlite` dosyasındadır; sıfırlamak için site kapalıyken bu dosyayı silin. Yerelde e-posta gönderilemez; e-postalar panelin **E-postalar** sayfasında görünür. `DENEME_ODEMESI` açıkken ödeme sayfasında gerçek para çekmeyen "Ödeme başarılı say" düğmesi çıkar.
