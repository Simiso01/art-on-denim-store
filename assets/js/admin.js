/**
 * Admin panel interactions
 */
(function () {
  'use strict';

  var toggle = document.getElementById('admin-nav-toggle');
  var nav = document.getElementById('admin-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      var message = el.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(message)) {
        e.preventDefault();
      }
    });
  });

  var addSizeBtn = document.getElementById('add-size-row');
  var sizeWrap = document.getElementById('inventory-editor');
  if (addSizeBtn && sizeWrap) {
    addSizeBtn.addEventListener('click', function () {
      var row = document.createElement('div');
      row.className = 'inventory-row';
      row.innerHTML =
        '<div class="admin-field"><label>Size</label><input type="text" name="sizes[]" required></div>' +
        '<div class="admin-field"><label>Qty</label><input type="number" name="quantities[]" min="0" value="0" required></div>' +
        '<button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-remove-row>Remove</button>';
      sizeWrap.appendChild(row);
    });

    sizeWrap.addEventListener('click', function (e) {
      var target = e.target;
      if (target && target.hasAttribute('data-remove-row')) {
        var row = target.closest('.inventory-row');
        if (row) row.remove();
      }
    });
  }
})();
