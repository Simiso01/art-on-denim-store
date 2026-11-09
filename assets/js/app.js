/**
 * Art on Denim — Global frontend interactions
 */
(function () {
  'use strict';

  var loader = document.getElementById('page-loader');
  var level = loader ? loader.querySelector('.level') : null;
  var levelTimer = null;

  if (level) {
    var value = 18;
    levelTimer = window.setInterval(function () {
      value = value >= 96 ? 18 : value + 6;
      level.value = String(value);
    }, 90);
  }

  var splashStarted = Date.now();

  function hideLoader() {
    if (!loader) return;
    window.clearInterval(levelTimer);
    if (level) level.value = '100';
    var wait = loader.classList.contains('aod-splash')
      ? Math.max(0, 1700 - (Date.now() - splashStarted))
      : 180;
    window.setTimeout(function () {
      loader.classList.add('is-done');
      document.body.classList.remove('splash-open');
    }, wait);
  }

  if (document.readyState === 'complete') {
    hideLoader();
  } else {
    window.addEventListener('load', hideLoader);
  }

  var toggle = document.getElementById('nav-toggle');
  var nav = document.getElementById('primary-nav');

  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.classList.contains('is-open')) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open menu');
        toggle.focus();
      }
    });
  }

  document.querySelectorAll('.alert__close').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var alert = btn.closest('.alert');
      if (alert) {
        alert.remove();
      }
    });
  });

  document.querySelectorAll('[data-qty]').forEach(function (wrap) {
    var input = wrap.querySelector('input[type="number"]');
    var minus = wrap.querySelector('[data-qty-minus]');
    var plus = wrap.querySelector('[data-qty-plus]');
    if (!input) return;

    function clamp() {
      var min = parseInt(input.min || '1', 10);
      var max = parseInt(input.max || '99', 10);
      var val = parseInt(input.value || String(min), 10);
      if (isNaN(val)) val = min;
      input.value = String(Math.max(min, Math.min(max, val)));
    }

    if (minus) {
      minus.addEventListener('click', function () {
        input.value = String((parseInt(input.value, 10) || 1) - 1);
        clamp();
      });
    }
    if (plus) {
      plus.addEventListener('click', function () {
        input.value = String((parseInt(input.value, 10) || 1) + 1);
        clamp();
      });
    }
    input.addEventListener('change', clamp);
  });

  document.querySelectorAll('.slider-row').forEach(function (row) {
    var slider = row.querySelector('.custom-slider');
    var value = row.querySelector('.slider-value');
    if (!slider || !value) return;
    function paint() {
      value.textContent = 'R' + Number(slider.value).toLocaleString('en-ZA');
    }
    slider.addEventListener('input', paint);
    paint();
  });

  var mainImage = document.getElementById('product-main-image');
  if (mainImage) {
    document.querySelectorAll('.product-thumb').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var full = btn.getAttribute('data-full');
        if (!full) return;
        mainImage.src = full;
        document.querySelectorAll('.product-thumb').forEach(function (el) {
          el.classList.remove('is-active');
        });
        btn.classList.add('is-active');
      });
    });
  }
})();
