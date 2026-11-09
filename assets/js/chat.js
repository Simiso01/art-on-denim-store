(function () {
  var root = document.querySelector('.atelier-chat');
  if (!root) return;

  var toggle = root.querySelector('.atelier-chat__toggle');
  var panel = root.querySelector('.atelier-chat__panel');
  var closeBtn = root.querySelector('.atelier-chat__close');
  var form = root.querySelector('.atelier-chat__form');
  var input = root.querySelector('#atelier-chat-input');
  var log = root.querySelector('.atelier-chat__log');
  var endpoint = root.getAttribute('data-chat-endpoint');

  function setOpen(open) {
    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) input.focus();
  }

  function addBubble(text, kind) {
    var p = document.createElement('p');
    p.className = 'atelier-chat__bubble' + (kind ? ' atelier-chat__bubble--' + kind : '');
    p.textContent = text;
    log.appendChild(p);
    log.scrollTop = log.scrollHeight;
  }

  toggle.addEventListener('click', function () {
    setOpen(panel.hidden);
  });
  closeBtn.addEventListener('click', function () {
    setOpen(false);
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    var message = input.value.trim();
    if (!message) return;
    addBubble(message, 'user');
    input.value = '';

    fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ message: message })
    }).then(function (response) {
      return response.json();
    }).then(function (payload) {
      var data = payload.data || {};
      addBubble(data.reply || payload.message || 'No answer came back.', 'studio');
      (data.products || []).forEach(function (product) {
        var link = document.createElement('a');
        link.className = 'atelier-chat__product';
        link.href = product.url;
        link.textContent = product.name + ' · ' + product.price;
        log.appendChild(link);
      });
      log.scrollTop = log.scrollHeight;
    }).catch(function () {
      addBubble('The desk could not be reached. Check that the site is running.', 'studio');
    });
  });
})();
