/* Küçük arayüz davranışları. Sepet ve sipariş işlemleri sunucuda (PHP) yapılır. */
(function () {
  'use strict';
  // Kategori menüsü (dar ekranda "Menü" düğmesiyle açılır)
  var katMenu = document.querySelector('.kat-menu');
  if (katMenu) {
    var menuDugme = katMenu.querySelector('.kat-menu-dugme');
    menuDugme.addEventListener('click', function () {
      var acik = katMenu.classList.toggle('acik');
      menuDugme.setAttribute('aria-expanded', acik ? 'true' : 'false');
    });
    // Esc ile açık alt menüden çık
    katMenu.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && document.activeElement) document.activeElement.blur();
    });
  }
  // Kampanya alanı: noktalarla geçiş, kendiliğinden döner (üzerine gelince ya da "Durdur" ile bekler)
  var slaytAlan = document.querySelector('.slayt-alan');
  if (slaytAlan) {
    var slaytlar = slaytAlan.querySelectorAll('.slayt');
    var noktalar = slaytAlan.querySelectorAll('button[data-slayt]');
    var durDugme = slaytAlan.querySelector('.slayt-dur');
    var simdiki = 0, bekle = false;
    var durdu = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var goster = function (i) {
      simdiki = (i + slaytlar.length) % slaytlar.length;
      Array.prototype.forEach.call(slaytlar, function (s, n) { s.classList.toggle('acik', n === simdiki); });
      Array.prototype.forEach.call(noktalar, function (b, n) {
        if (n === simdiki) b.setAttribute('aria-current', 'true'); else b.removeAttribute('aria-current');
      });
    };
    var durYaz = function () {
      if (!durDugme) return;
      durDugme.textContent = durdu ? 'Oynat' : 'Durdur';
      durDugme.setAttribute('aria-pressed', durdu ? 'true' : 'false');
    };
    durYaz();
    Array.prototype.forEach.call(noktalar, function (b) {
      b.addEventListener('click', function () { durdu = true; durYaz(); goster(parseInt(b.getAttribute('data-slayt'), 10)); });
    });
    if (durDugme) durDugme.addEventListener('click', function () { durdu = !durdu; durYaz(); });
    slaytAlan.addEventListener('mouseenter', function () { bekle = true; });
    slaytAlan.addEventListener('mouseleave', function () { bekle = false; });
    slaytAlan.addEventListener('focusin', function () { bekle = true; });
    slaytAlan.addEventListener('focusout', function () { bekle = false; });
    if (slaytlar.length > 1) window.setInterval(function () { if (!durdu && !bekle) goster(simdiki + 1); }, 6000);
  }
  // Adet kutuları: − ve + düğmeleri sayıyı değiştirir; data-gonder varsa formu gönderir
  Array.prototype.forEach.call(document.querySelectorAll('.adet'), function (kutu) {
    var girdi = kutu.querySelector('input');
    if (!girdi) return;
    Array.prototype.forEach.call(kutu.querySelectorAll('button[data-adim]'), function (b) {
      b.addEventListener('click', function () {
        var en = parseInt(girdi.getAttribute('max') || '99', 10);
        var az = parseInt(girdi.getAttribute('min') || '1', 10);
        var yeni = Math.max(az, Math.min(en, (parseInt(girdi.value, 10) || 0) + parseInt(b.getAttribute('data-adim'), 10)));
        girdi.value = yeni;
        if (kutu.hasAttribute('data-gonder')) girdi.form.submit();
      });
    });
    if (kutu.hasAttribute('data-gonder')) girdi.addEventListener('change', function () { girdi.form.submit(); });
  });
  // Teslimat günü seçilince o günün saatlerini göster
  var gunSec = document.getElementById('t-gun');
  if (gunSec) {
    var saatSec = document.getElementById('t-saat');
    var doldur = function () {
      var secenekler = JSON.parse(gunSec.selectedOptions[0].getAttribute('data-saatler') || '[]');
      var eski = saatSec.getAttribute('data-secili') || '';
      saatSec.innerHTML = '';
      secenekler.forEach(function (s) {
        var o = document.createElement('option');
        o.value = s.saat; o.textContent = s.saat.replace('-', ' – ') + (s.dolu ? ' (dolu)' : '');
        o.disabled = s.dolu; if (s.saat === eski && !s.dolu) o.selected = true;
        saatSec.appendChild(o);
      });
    };
    gunSec.addEventListener('change', function () { saatSec.setAttribute('data-secili', ''); doldur(); });
    doldur();
  }
  // Onay isteyen düğmeler
  Array.prototype.forEach.call(document.querySelectorAll('[data-onay]'), function (b) {
    b.addEventListener('click', function (e) { if (!window.confirm(b.getAttribute('data-onay'))) e.preventDefault(); });
  });
})();
