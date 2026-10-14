<?php
require __DIR__ . '/inc/fonk.php';
$baslik = 'Sepet';
$sayfa = 'sepet';
$m = musteri();
$hatalar = [];
$form = [
    'ad' => $m ? trim($m['ad'] . ' ' . $m['soyad']) : '',
    'telefon' => $m['telefon'] ?? '',
    'eposta' => $m['eposta'] ?? '',
    'adres' => $m['adres'] ?? '',
    'not' => '', 'gun' => '', 'saat' => '', 'odeme' => 'kart',
];
$kapidaAcik = ayar('kapida_odeme', '1') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_kontrol();
    $islem = post('islem');

    if ($islem === 'ekle' || $islem === 'guncelle') {
        $u = urun_getir((int) post('urun_id'));
        $adet = max(0, (int) post('adet', '1'));
        if ($u && $u['aktif']) {
            $mevcut = sepet()[$u['id']] ?? 0;
            $yeni = $islem === 'ekle' ? $mevcut + max(1, $adet) : $adet;
            if ($yeni > (int) $u['stok']) {
                $yeni = (int) $u['stok'];
                mesaj_yaz($u['ad'] . ' için stokta ' . $yeni . ' adet var.', 'hata');
            } elseif ($islem === 'ekle') {
                mesaj_yaz($u['ad'] . ' sepete eklendi.', 'basari');
            }
            sepet_adet_ayarla((int) $u['id'], $yeni);
        }
        $don = post('don');
        // yalnızca site içi adrese dön
        yonlendir(($islem === 'ekle' && $don !== '' && !preg_match('~^(https?:)?//|[\r\n]~i', $don)) ? $don : 'sepet.php');
    }

    if ($islem === 'sil') {
        sepet_adet_ayarla((int) post('urun_id'), 0);
        yonlendir('sepet.php');
    }

    if ($islem === 'siparis') {
        foreach (['ad', 'telefon', 'eposta', 'adres', 'not', 'gun', 'saat'] as $a) {
            $form[$a] = post($a);
        }
        $form['odeme'] = ($kapidaAcik && post('odeme') === 'kapida') ? 'kapida' : 'kart';
        $h = sepet_hesap();
        if (!$h['kalemler']) {
            $hatalar[] = 'Sepetin boş.';
        }
        foreach ($h['uyarilar'] as $u) {
            $hatalar[] = $u . ' Lütfen sepetini kontrol edip tekrar onayla.';
        }
        if (mb_strlen($form['ad']) < 3) {
            $hatalar[] = 'Ad soyad yaz.';
        }
        if (strlen(preg_replace('/\D/', '', $form['telefon'])) < 10) {
            $hatalar[] = 'Telefon numaranı başında 0 ile, 11 haneli yaz.';
        }
        if (!filter_var($form['eposta'], FILTER_VALIDATE_EMAIL)) {
            $hatalar[] = 'Geçerli bir e-posta adresi yaz.';
        }
        if (mb_strlen($form['adres']) < 10) {
            $hatalar[] = 'Açık adresini mahalle, sokak ve kapı numarasıyla yaz.';
        }
        if (!dilim_gecerli($form['gun'], $form['saat'])) {
            $hatalar[] = 'Seçtiğin teslimat saati dolmuş ya da artık seçilemiyor. Başka bir saat seç.';
        }
        if (post('onay') !== '1') {
            $hatalar[] = 'Ön Bilgilendirme Formu ve Mesafeli Satış Sözleşmesi onayını işaretle.';
        }

        if (!$hatalar) {
            do {
                $no = 'S' . date('ymd') . random_int(10000, 99999);
            } while (siparis_getir_no($no));
            $anahtar = bin2hex(random_bytes(16));
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $id = ekle("INSERT INTO siparisler (no, anahtar, musteri_id, ad, telefon, eposta, adres, siparis_notu,
                        teslimat_tarihi, teslimat_saati, ara_toplam, teslimat_ucreti, toplam, durum, odeme_yontemi, olusturma, guncelleme)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'odeme_bekliyor', ?, ?, ?)",
                    [$no, $anahtar, $m['id'] ?? null, kisalt($form['ad'], 160), kisalt($form['telefon'], 30), kisalt($form['eposta'], 160),
                        ayar('teslimat_bolgesi') . ' — ' . kisalt($form['adres'], 600), kisalt($form['not'], 500),
                        $form['gun'], $form['saat'], $h['ara'], $h['teslimat'], $h['toplam'], $form['odeme'], simdi(), simdi()]);
                q("INSERT INTO siparis_gecmisi (siparis_id, durum, olusturma) VALUES (?, 'odeme_bekliyor', ?)", [$id, simdi()]);
                foreach ($h['kalemler'] as $k) {
                    q("INSERT INTO siparis_kalemleri (siparis_id, urun_id, urun_adi, paket, fiyat, adet) VALUES (?, ?, ?, ?, ?, ?)",
                        [$id, $k['urun']['id'], $k['urun']['ad'], $k['urun']['paket'], $k['urun']['fiyat'], $k['adet']]);
                }
                if ($m && trim((string) $m['adres']) === '') {
                    q("UPDATE musteriler SET adres = ? WHERE id = ?", [kisalt($form['adres'], 600), $m['id']]);
                }
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
            $_SESSION['sepet'] = [];
            if ($form['odeme'] === 'kapida') {
                // Kapıda ödeme: ödeme adımı yok, sipariş doğrudan dükkânın onayına düşer.
                siparis_onaya_al(siparis_getir_no($no));
                yonlendir('siparis.php?no=' . urlencode($no) . '&k=' . $anahtar);
            }
            yonlendir('odeme.php?no=' . urlencode($no) . '&k=' . $anahtar);
        }
    }
}

$h = sepet_hesap();
foreach ($h['uyarilar'] as $u) {
    mesaj_yaz($u, 'hata');
}
$gunler = teslimat_secenekleri();
require __DIR__ . '/inc/ust.php';
?>
<div class="ust">
  <div class="kap ust-baslik">
    <h1 class="baslik h1">Sepetin</h1>
    <ol class="adimlar">
      <li class="aktif">1 · Sepet ve teslimat</li>
      <li>2 · Ödeme</li>
      <li>3 · Sipariş onayı</li>
    </ol>
  </div>
</div>

<main>
<?php if (!$h['kalemler']): ?>
  <div class="kap" style="padding-top:40px; padding-bottom:72px">
    <div class="kutu" style="align-items:flex-start">
      <h2>Sepetin boş</h2>
      <p class="soluk">Ürünlere göz at, beğendiklerini sepete ekle.</p>
      <a class="dugme bordo" href="index.php#urunler">Ürünleri gör</a>
    </div>
  </div>
<?php else: ?>
  <div class="kap iki">
    <div class="genis">
      <?php if ($hatalar): ?>
        <div class="bilgi hata" role="alert"><b>Sipariş tamamlanamadı:</b>
          <ul style="margin:6px 0 0; padding-left:18px"><?php foreach ($hatalar as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <?php if (!$m): ?>
      <section class="kutu yatay">
        <div>
          <b>Misafir olarak devam ediyorsun</b>
          <p class="soluk">Üye olmadan sipariş verebilirsin. Giriş yaparsan adresin ve siparişlerin kayıtlı kalır.</p>
        </div>
        <div class="dugmeler">
          <a class="dugme kucuk koyu" href="giris.php?don=sepet.php">Giriş Yap</a>
          <a class="dugme kucuk cizgi" href="giris.php?don=sepet.php#kayit">Kayıt Ol</a>
        </div>
      </section>
      <?php endif; ?>

      <section class="kutu">
        <h2>Ürünler</h2>
        <?php foreach ($h['kalemler'] as $k): $u = $k['urun']; ?>
          <div class="kalem">
            <img src="<?= e($u['gorsel']) ?>" alt="" width="72" height="54">
            <div class="ad"><b><a href="urun.php?id=<?= (int) $u['id'] ?>"><?= e($u['ad']) ?></a></b><span class="soluk"><?= e($u['paket']) ?> · <?= tl($u['fiyat']) ?></span></div>
            <form method="post" action="sepet.php" style="margin:0">
              <?= csrf_alan() ?><input type="hidden" name="islem" value="guncelle"><input type="hidden" name="urun_id" value="<?= (int) $u['id'] ?>">
              <div class="adet" data-gonder>
                <button type="button" data-adim="-1" aria-label="<?= e($u['ad']) ?> adedini azalt">−</button>
                <input type="number" name="adet" value="<?= (int) $k['adet'] ?>" min="1" max="<?= min(99, (int) $u['stok']) ?>" aria-label="<?= e($u['ad']) ?> adedi">
                <button type="button" data-adim="1" aria-label="<?= e($u['ad']) ?> adedini artır">+</button>
              </div>
            </form>
            <div class="tutar"><?= tl($k['tutar']) ?></div>
            <form method="post" action="sepet.php" style="margin:0">
              <?= csrf_alan() ?><input type="hidden" name="islem" value="sil"><input type="hidden" name="urun_id" value="<?= (int) $u['id'] ?>">
              <button type="submit" class="sil" aria-label="<?= e($u['ad']) ?> ürününü sepetten çıkar"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16"/><path d="M9 7V4h6v3"/><path d="M6 7l1 13h10l1-13"/></svg></button>
            </form>
          </div>
        <?php endforeach; ?>
      </section>

      <section class="kutu">
        <h2>Teslimat zamanı</h2>
        <?php if (!$gunler): ?>
          <div class="bilgi hata">Şu an seçilebilir teslimat saati yok. Lütfen daha sonra tekrar dene ya da bizi ara: <?= e(ayar('telefon')) ?></div>
        <?php else: ?>
        <div class="alanlar">
          <div class="alan"><label for="t-gun">Gün</label>
            <select id="t-gun" name="gun" form="odeme-form" required>
              <?php foreach ($gunler as $g): ?>
                <option value="<?= e($g['tarih']) ?>" data-saatler="<?= e(json_encode($g['dilimler'])) ?>"<?= $form['gun'] === $g['tarih'] ? ' selected' : '' ?>><?= e($g['etiket']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="alan"><label for="t-saat">Saat aralığı</label>
            <select id="t-saat" name="saat" form="odeme-form" required data-secili="<?= e($form['saat']) ?>">
              <?php foreach ($gunler[0]['dilimler'] as $d): ?>
                <option value="<?= e($d['saat']) ?>"<?= $d['dolu'] ? ' disabled' : '' ?>><?= e(str_replace('-', ' – ', $d['saat'])) ?><?= $d['dolu'] ? ' (dolu)' : '' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <?php endif; ?>
      </section>

      <section class="kutu">
        <h2>Teslimat adresi</h2>
        <div class="uyari" id="t-not">Şu an yalnızca şu bölgeye teslimat yapıyoruz: <?= e(ayar('teslimat_bolgesi')) ?>.</div>
        <div class="alanlar">
          <div class="alan"><label for="t-ad">Ad Soyad</label><input id="t-ad" name="ad" type="text" autocomplete="name" required value="<?= e($form['ad']) ?>" form="odeme-form"></div>
          <div class="alan"><label for="t-tel">Telefon</label><input id="t-tel" name="telefon" type="tel" autocomplete="tel" placeholder="05__ ___ __ __" required value="<?= e($form['telefon']) ?>" form="odeme-form"></div>
        </div>
        <div class="alan"><label for="t-eposta">E-posta</label><input id="t-eposta" name="eposta" type="email" autocomplete="email" required aria-describedby="t-eposta-not" value="<?= e($form['eposta']) ?>" form="odeme-form"><small id="t-eposta-not">Sipariş onayı bu adrese gönderilir.</small></div>
        <div class="alanlar">
          <div class="alan"><label for="t-il">İl</label><input id="t-il" type="text" value="Yozgat" readonly aria-describedby="t-not"></div>
          <div class="alan"><label for="t-ilce">İlçe</label><input id="t-ilce" type="text" value="Merkez" readonly aria-describedby="t-not"></div>
        </div>
        <div class="alan"><label for="t-adres">Açık adres (mahalle, sokak, bina ve daire no)</label><textarea id="t-adres" name="adres" rows="3" autocomplete="street-address" required form="odeme-form"><?= e($form['adres']) ?></textarea></div>
        <div class="alan"><label for="t-not2">Sipariş notu (isteğe bağlı)</label><input id="t-not2" name="not" type="text" value="<?= e($form['not']) ?>" form="odeme-form"></div>
      </section>
    </div>

    <aside class="dar ozet">
      <h2>Sipariş özeti</h2>
      <div class="ozet-satir"><span>Ara toplam</span><b><?= tl($h['ara']) ?></b></div>
      <div class="ozet-satir"><span>Teslimat</span><b><?= $h['teslimat'] ? tl($h['teslimat']) : 'Ücretsiz' ?></b></div>
      <?php if ($h['esik'] > 0): ?>
        <div class="uyari"><?= $h['ara'] < $h['esik'] ? 'Ücretsiz teslimat için ' . tl($h['esik'] - $h['ara']) . ' daha ekle.' : 'Teslimatın ücretsiz.' ?></div>
      <?php endif; ?>
      <div class="ozet-satir ozet-toplam"><b>Toplam</b><b><?= tl($h['toplam']) ?></b></div>
      <label class="onay" for="s-onay"><input id="s-onay" name="onay" value="1" type="checkbox" required form="odeme-form"><span><a class="baglanti" style="min-height:0;display:inline" href="sayfa.php?s=sozlesme" target="_blank" rel="noopener">Ön Bilgilendirme Formu'nu ve Mesafeli Satış Sözleşmesi'ni</a> okudum, onaylıyorum.</span></label>
      <fieldset class="odeme-secim">
        <legend>Ödeme yöntemi</legend>
        <label class="onay" for="o-kart"><input id="o-kart" type="radio" name="odeme" value="kart" form="odeme-form"<?= $form['odeme'] === 'kart' ? ' checked' : '' ?>><span><b>Kartla şimdi öde</b><br><small>PayTR güvenli ödeme sayfasında.</small></span></label>
        <?php if ($kapidaAcik): ?>
        <label class="onay" for="o-kapida"><input id="o-kapida" type="radio" name="odeme" value="kapida" form="odeme-form"<?= $form['odeme'] === 'kapida' ? ' checked' : '' ?>><span><b>Kapıda öde</b><br><small>Tutarı teslimatta kuryeye ödersin.</small></span></label>
        <?php endif; ?>
      </fieldset>
      <button type="submit" class="dugme bordo tam" form="odeme-form"<?= $gunler ? '' : ' disabled' ?>>Siparişi tamamla</button>
      <a class="baglanti" href="index.php#urunler">Alışverişe devam et</a>
    </aside>
  </div>
  <form id="odeme-form" method="post" action="sepet.php"><?= csrf_alan() ?><input type="hidden" name="islem" value="siparis"></form>
<?php endif; ?>
</main>
<?php require __DIR__ . '/inc/alt.php'; ?>
