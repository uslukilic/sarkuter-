<?php
require __DIR__ . '/inc/fonk.php';
require __DIR__ . '/inc/kart.php';
$baslik = 'Ana Sayfa';
$sayfa = 'ana';
$kategoriler = kategori_agaci();
$secili = (int) ($_GET['kategori'] ?? 0);
// Seçili kategori bir alt kategoriyse, üstündeki ana kategoriyi de bul
$seciliAna = 0;
$altlar = [];
foreach ($kategoriler as $k) {
    if ((int) $k['id'] === $secili) {
        $seciliAna = $secili;
        $altlar = $k['alt'];
    }
    foreach ($k['alt'] as $a) {
        if ((int) $a['id'] === $secili) {
            $seciliAna = (int) $k['id'];
            $altlar = $k['alt'];
        }
    }
}
if (!$seciliAna) {
    $secili = 0;
}
$ara = kisalt(trim((string) ($_GET['ara'] ?? '')), 60);
// Vitrin: süzgeç ve arama yokken gösterilen ana sayfa. Katalog: bütün ürünlerin süzülebilir listesi.
$katalog = $secili || $ara !== '' || isset($_GET['tumu']);

// Ürün, kendi kategorisi ve (varsa) ana kategorisi görünürse listelenir.
$gorunur = "SELECT u.*, k.ust_id AS k_ust FROM urunler u JOIN kategoriler k ON k.id = u.kategori_id LEFT JOIN kategoriler p ON p.id = k.ust_id
    WHERE u.aktif = 1 AND k.aktif = 1 AND (k.ust_id = 0 OR p.aktif = 1)";
$sirala = " ORDER BY COALESCE(p.sira, k.sira), COALESCE(p.ad, k.ad), k.ust_id, k.sira, u.ad";
$hepsi = satirlar($gorunur . $sirala);

if ($katalog) {
    $urunler = $hepsi;
    if ($secili) { // ana kategori seçiliyse alt kategorilerinin ürünleri de gelir
        $urunler = array_values(array_filter($urunler, fn($u) => (int) $u['kategori_id'] === $secili || (int) $u['k_ust'] === $secili));
    }
    // Arama: yazılan her kelime ürün adında, açıklamasında ya da kategorisinde geçmeli (Türkçe büyük-küçük harfe duyarsız).
    if ($ara !== '') {
        $kelimeler = preg_split('/\s+/', kucuk_harf($ara));
        $katAdlari = [];
        foreach (satirlar("SELECT id, ad FROM kategoriler") as $k) {
            $katAdlari[(int) $k['id']] = $k['ad'];
        }
        $urunler = array_values(array_filter($urunler, function ($u) use ($kelimeler, $katAdlari) {
            $metin = kucuk_harf($u['ad'] . ' ' . $u['kisa'] . ' ' . $u['paket'] . ' ' . ($katAdlari[(int) $u['kategori_id']] ?? ''));
            foreach ($kelimeler as $k) {
                if ($k !== '' && mb_strpos($metin, $k) === false) {
                    return false;
                }
            }
            return true;
        }));
    }
} else {
    // Her ana kategorinin (alt kategorileri dahil) ürünleri
    $kategoriUrunleri = [];
    foreach ($kategoriler as $k) {
        $kategoriUrunleri[(int) $k['id']] = array_values(array_filter($hepsi, fn($u) => (int) $u['kategori_id'] === (int) $k['id'] || (int) $u['k_ust'] === (int) $k['id']));
    }
    // Çok satanlar: onaylanmış siparişlerdeki adetlere göre; yeterli satış yoksa en yeni ürünlerle tamamlanır.
    $stokta = [];
    foreach ($hepsi as $u) {
        if ((int) $u['stok'] > 0) {
            $stokta[(int) $u['id']] = $u;
        }
    }
    $one = [];
    foreach (satirlar("SELECT k.urun_id, SUM(k.adet) AS n FROM siparis_kalemleri k JOIN siparisler s ON s.id = k.siparis_id
        WHERE s.durum IN ('onay_bekliyor','onaylandi','yolda','teslim_edildi') GROUP BY k.urun_id ORDER BY n DESC, k.urun_id LIMIT 12") as $r) {
        if (isset($stokta[(int) $r['urun_id']]) && count($one) < 4) {
            $one[(int) $r['urun_id']] = $stokta[(int) $r['urun_id']];
        }
    }
    $oneBaslik = count($one) >= 4 ? 'Çok satanlar' : 'Öne çıkanlar';
    if (count($one) < 4) {
        $yeniler = $stokta;
        krsort($yeniler);
        foreach ($yeniler as $id => $u) {
            if (count($one) >= 4) {
                break;
            }
            $one[$id] = $one[$id] ?? $u;
        }
    }
    // En erken seçilebilir teslimat zamanı
    $ilkTeslimat = null;
    foreach (teslimat_secenekleri() as $g) {
        foreach ($g['dilimler'] as $d) {
            if (!$d['dolu']) {
                $ilkTeslimat = explode(' · ', $g['etiket'])[0] . ', ' . str_replace('-', ' – ', $d['saat']);
                break 2;
            }
        }
    }
}
$esik = (int) ayar('ucretsiz_teslimat');
require __DIR__ . '/inc/ust.php';
?>
<?php if (!$katalog):
  // Kampanya alanı: ilk üç dolu kategori birer slayt olarak da gösterilir
  $slaytlar = [[
      'ust' => ayar('teslimat_bolgesi') . ' içi teslimat',
      'baslik' => 'Kahvaltı sofran, seçtiğin saatte kapında',
      'yazi' => 'Sucuk, pastırma, peynir, zeytin ve kahvaltılıklar hazır paketlerde. Gününü ve saat aralığını sen seç.',
      'dugme' => 'Alışverişe başla', 'adres' => 'index.php?tumu=1', 'gorsel' => 'assets/img/ana.svg',
  ]];
  foreach ($kategoriler as $k) {
      $liste = $kategoriUrunleri[(int) $k['id']];
      if ($liste && count($slaytlar) < 3) {
          $slaytlar[] = [
              'ust' => count($liste) . ' ürün',
              'baslik' => $k['ad'] . ' çeşitleri',
              'yazi' => implode(', ', array_map(fn($u) => $u['ad'], array_slice($liste, 0, 3))) . ' ve daha fazlası.',
              'dugme' => $k['ad'] . ' ürünlerini gör', 'adres' => 'index.php?kategori=' . (int) $k['id'], 'gorsel' => $liste[0]['gorsel'],
          ];
      }
  }
?>
<main>
  <section class="slayt-alan" aria-roledescription="carousel" aria-label="Kampanyalar">
    <div class="kap">
      <div class="slaytlar">
        <?php foreach ($slaytlar as $i => $sl): ?>
        <div class="slayt<?= $i === 0 ? ' acik' : '' ?>" id="slayt-<?= $i ?>" role="group" aria-roledescription="slide" aria-label="<?= $i + 1 ?> / <?= count($slaytlar) ?>">
          <div class="slayt-yazi">
            <span class="etiket"><?= e($sl['ust']) ?></span>
            <<?= $i === 0 ? 'h1' : 'h2' ?> class="baslik slayt-baslik"><?= e($sl['baslik']) ?></<?= $i === 0 ? 'h1' : 'h2' ?>>
            <p><?= e($sl['yazi']) ?></p>
            <div class="dugmeler">
              <a class="dugme bordo" href="<?= e($sl['adres']) ?>"><?= e($sl['dugme']) ?></a>
              <?php if ($i === 0): ?><a class="dugme cizgi" href="siparis-sorgula.php">Siparişimi sorgula</a><?php endif; ?>
            </div>
          </div>
          <div class="slayt-gorsel"><img src="<?= e($sl['gorsel']) ?>" alt="" width="800" height="600"<?= $i ? ' loading="lazy"' : '' ?>></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if (count($slaytlar) > 1): ?>
      <div class="slayt-dugmeler">
        <?php foreach ($slaytlar as $i => $sl): ?><button type="button" data-slayt="<?= $i ?>" aria-controls="slayt-<?= $i ?>" aria-label="<?= $i + 1 ?>. kampanya: <?= e($sl['baslik']) ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>></button><?php endforeach; ?>
        <button type="button" class="slayt-dur" aria-pressed="false">Durdur</button>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="bilgi-serit" aria-label="Teslimat ve ödeme bilgileri"><div class="kap bilgi-serit-ic">
    <?php if ($ilkTeslimat): ?>
    <div><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span><b>Hızlı teslimat</b>En erken: <?= e($ilkTeslimat) ?></span></div>
    <?php endif; ?>
    <div><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="12" height="10" rx="1"/><path d="M14 10h4l3 3v4h-7"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg><span><b><?= $esik > 0 ? 'Ücretsiz teslimat' : 'Teslimat ücreti' ?></b><?= $esik > 0 ? tl($esik) . ' ve üzeri siparişlerde' : tl(ayar('teslimat_ucreti')) ?></span></div>
    <div><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="3"/><path d="M3 10h18"/></svg><span><b>Güvenli ödeme</b><?= ayar('kapida_odeme', '1') === '1' ? 'Kartla ya da kapıda' : 'Kartla, PayTR ile' ?></span></div>
    <div><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-5.5-7-11a7 7 0 0114 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg><span><b>Sipariş takibi</b>Sipariş numaranla anlık durum</span></div>
  </div></section>

  <section class="kap bolum hizli" aria-labelledby="b-hizli">
    <h2 class="baslik h2" id="b-hizli">Taze şarküteri ve kahvaltılık çeşitleri</h2>
    <p class="soluk">Aradığın ürüne kategorisinden hızlıca ulaş.</p>
    <div class="hizli-dizi">
      <?php foreach ($kategoriler as $k): $liste = $kategoriUrunleri[(int) $k['id']]; if (!$liste) { continue; } ?>
        <a href="#kat-<?= (int) $k['id'] ?>"><img src="<?= e($liste[0]['gorsel']) ?>" alt="" loading="lazy" width="120" height="120"><span><?= e($k['ad']) ?></span></a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="zemin-bugday" aria-labelledby="b-neden">
    <div class="kap bolum neden">
      <div class="yazi">
        <h2 class="baslik h2" id="b-neden">Neden <?= e(ayar('marka')) ?>?</h2>
        <p>Ürünlerimizi güvendiğimiz üreticilerden alıyor, günlük olarak hazır paketlere ayırıyoruz. Ne aldığını gramıyla, fiyatıyla baştan bilirsin.</p>
        <p>Siparişini <?= e(ayar('teslimat_bolgesi')) ?> içinde, senin seçtiğin gün ve saat aralığında kendi kuryemizle kapına getiriyoruz.</p>
        <div><a class="dugme koyu" href="hakkimizda.php">Hikayemizi incele</a></div>
      </div>
      <img src="assets/img/dukkan.svg" alt="Dükkân (temsili çizim)" loading="lazy" width="800" height="600">
    </div>
  </section>

  <?php if ($one): ?>
  <section aria-labelledby="b-one">
    <div class="kap bolum">
      <div class="bolum-ust"><h2 class="baslik h2" id="b-one"><?= e($oneBaslik) ?></h2><a class="baglanti" href="index.php?tumu=1">Tüm ürünler (<?= count($hepsi) ?>)</a></div>
      <div class="urunler dortlu"><?php foreach ($one as $u) { echo urun_karti($u); } ?></div>
    </div>
  </section>
  <?php endif; ?>

  <section class="kap bolum kat-bloklar" id="urunler" aria-labelledby="b-kategoriler">
    <h2 class="baslik h2 bolum-baslik" id="b-kategoriler">Kategorilerimiz</h2>
    <?php foreach ($kategoriler as $k): $liste = $kategoriUrunleri[(int) $k['id']]; if (!$liste) { continue; } ?>
    <article class="kat-blok" id="kat-<?= (int) $k['id'] ?>">
      <a class="kat-blok-ust" href="index.php?kategori=<?= (int) $k['id'] ?>">
        <img src="<?= e($liste[0]['gorsel']) ?>" alt="" loading="lazy" width="600" height="450">
        <div><h3 class="baslik"><?= e($k['ad']) ?></h3><small><?= count($liste) ?> ürün</small><b>Tümünü gör</b></div>
      </a>
      <div class="kat-blok-urunler">
        <?php foreach (array_slice($liste, 0, 8) as $u): ?>
        <a class="mini-urun<?= (int) $u['stok'] > 0 ? '' : ' yok' ?>" href="urun.php?id=<?= (int) $u['id'] ?>">
          <img src="<?= e($u['gorsel']) ?>" alt="" loading="lazy" width="300" height="225">
          <span class="ad"><?= e($u['ad']) ?></span>
          <span class="alt-satir"><b><?= tl($u['fiyat']) ?></b><small><?= (int) $u['stok'] > 0 ? e($u['paket']) : 'Tükendi' ?></small></span>
        </a>
        <?php endforeach; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </section>

  <section class="kap bolum" style="padding-top:0">
    <div class="takip-serit">
      <div>
        <h2 class="baslik h2">Siparişin nerede?</h2>
        <p>Sipariş numaran ve e-posta adresinle hazırlanıyor mu, yola çıktı mı hemen öğren.</p>
      </div>
      <div class="dugmeler">
        <a class="dugme bordo" href="siparis-sorgula.php">Siparişimi sorgula</a>
        <a class="dugme cizgi" href="iletisim.php">Bize ulaş</a>
      </div>
    </div>
  </section>
</main>

<?php else: ?>
<main>
  <section id="urunler">
    <div class="kap bolum">
      <div class="bolum-ust">
        <h1 class="baslik h2"><?= $seciliAna ? e(array_values(array_filter($kategoriler, fn($k) => (int) $k['id'] === $seciliAna))[0]['ad']) : 'Tüm ürünler' ?></h1>
        <nav class="cipler" aria-label="Kategoriler">
          <a class="cip" style="display:inline-flex;align-items:center" href="index.php?tumu=1#urunler"<?= $secili ? '' : ' aria-current="true"' ?>>Tümü</a>
          <?php foreach ($kategoriler as $k): ?>
            <a class="cip" style="display:inline-flex;align-items:center" href="index.php?kategori=<?= (int) $k['id'] ?>#urunler"<?= $seciliAna === (int) $k['id'] ? ' aria-current="true"' : '' ?>><?= e($k['ad']) ?></a>
          <?php endforeach; ?>
        </nav>
      </div>
      <?php if ($altlar): ?>
        <nav class="cipler alt-cipler" aria-label="Alt kategoriler">
          <a class="cip kucuk" style="display:inline-flex;align-items:center" href="index.php?kategori=<?= $seciliAna ?>#urunler"<?= $secili === $seciliAna ? ' aria-current="true"' : '' ?>>Hepsi</a>
          <?php foreach ($altlar as $a): ?>
            <a class="cip kucuk" style="display:inline-flex;align-items:center" href="index.php?kategori=<?= (int) $a['id'] ?>#urunler"<?= $secili === (int) $a['id'] ? ' aria-current="true"' : '' ?>><?= e($a['ad']) ?></a>
          <?php endforeach; ?>
        </nav>
      <?php endif; ?>
      <?php if ($ara !== ''): ?><p class="soluk" style="margin-bottom:14px" role="status">"<?= e($ara) ?>" için <?= count($urunler) ?> ürün bulundu<?= $secili ? ' (seçili kategoride)' : '' ?>. <a class="baglanti" style="min-height:0" href="index.php?<?= $secili ? 'kategori=' . $secili : 'tumu=1' ?>">Aramayı temizle</a></p><?php endif; ?>
      <div class="urunler">
        <?php if (!$urunler): ?><div class="bos"><?= $ara !== '' ? 'Aramana uyan ürün bulunamadı. Başka bir kelime dene ya da kategorilere göz at.' : 'Bu kategoride henüz ürün yok.' ?></div><?php endif; ?>
        <?php foreach ($urunler as $u) { echo urun_karti($u); } ?>
      </div>
    </div>
  </section>
</main>
<?php endif; ?>
<?php require __DIR__ . '/inc/alt.php'; ?>
