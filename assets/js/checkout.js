/**
 * Checkout page interactions
 */
(function () {
  'use strict';

  var form = document.getElementById('checkout-form');
  var btn = document.getElementById('place-order-btn');

  if (form && btn) {
    form.addEventListener('submit', function () {
      btn.disabled = true;
      btn.textContent = 'Placing order…';
    });
  }

  function syncPaymentSelection() {
    document.querySelectorAll('.payment-option').forEach(function (label) {
      var input = label.querySelector('input[type="radio"]');
      label.classList.toggle('is-selected', !!(input && input.checked));
    });
  }

  document.querySelectorAll('.payment-option input[type="radio"]').forEach(function (input) {
    input.addEventListener('change', syncPaymentSelection);
  });
  syncPaymentSelection();
})();
