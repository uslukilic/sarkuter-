<?php
/* Ürün kartı: ana sayfa ve ürün detayındaki listelerde kullanılır. */
function urun_karti(array $u): string
{
    $var = (int) $u['stok'] > 0;
    $adres = 'urun.php?id=' . (int) $u['id'];
    $h = '<article class="urun' . ($var ? '' : ' yok') . '">'
        . '<a class="foto" href="' . $adres . '" aria-label="' . e($u['ad']) . ' detayı"><img src="' . e($u['gorsel']) . '" alt="" loading="lazy" width="600" height="450"></a>'
        . '<div class="urun-ic"><h3><a href="' . $adres . '">' . e($u['ad']) . '</a></h3>'
        . '<p class="soluk">' . e($u['kisa']) . ' · ' . e($u['paket']) . '</p>'
        . '<div class="urun-alt"><div class="fiyat">' . tl($u['fiyat']) . '</div>';
    if ($var) {
        $h .= '<form method="post" action="sepet.php" style="margin:0">' . csrf_alan()
            . '<input type="hidden" name="islem" value="ekle"><input type="hidden" name="urun_id" value="' . (int) $u['id'] . '">'
            . '<input type="hidden" name="adet" value="1"><input type="hidden" name="don" value="' . e($_SERVER['REQUEST_URI'] ?? 'index.php') . '">'
            . '<button type="submit" class="dugme kucuk bordo" aria-label="' . e($u['ad']) . ' sepete ekle">+ Ekle</button></form>';
    } else {
        $h .= '<span class="tukendi">Tükendi</span>';
    }
    return $h . '</div></div></article>';
}
