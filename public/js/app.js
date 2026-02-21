(function () {
  /* ── api key input ── */
  var input    = document.getElementById('api-key-input');
  var jsonSpan = document.getElementById('json-auth');
  var cliSpan  = document.getElementById('cli-auth');

  function escape(str) {
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  input.addEventListener('input', function () {
    var key = this.value.trim();
    if (key) {
      var safe = escape(key);
      jsonSpan.innerHTML = '"Bearer ' + safe + '"';
      cliSpan.innerHTML  = '"Authorization: Bearer ' + safe + '"';
    } else {
      jsonSpan.innerHTML = '"Bearer &lt;your-planka-api-key&gt;"';
      cliSpan.innerHTML  = '"Authorization: Bearer &lt;your-planka-api-key&gt;"';
    }
  });

  /* ── copy buttons ── */
  var copyIcon  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667l0 -8.666" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg>';
  var checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5l10 -10" /></svg>';

  document.querySelectorAll('.copy-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var pre  = btn.closest('.code-block').querySelector('pre');
      var text = (pre.textContent || pre.innerText).trim();
      navigator.clipboard.writeText(text).then(function () {
        btn.innerHTML = checkIcon;
        btn.classList.add('copied');
        setTimeout(function () {
          btn.innerHTML = copyIcon;
          btn.classList.remove('copied');
        }, 1500);
      });
    });
  });
}());
