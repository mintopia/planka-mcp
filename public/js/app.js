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

  /* ── tool modal ── */
  var overlay    = document.getElementById('modal-overlay');
  var modalClose = document.getElementById('modal-close');
  var mIcon      = document.getElementById('modal-icon');
  var mName      = document.getElementById('modal-name');
  var mDesc      = document.getElementById('modal-desc');
  var mParams    = document.getElementById('modal-params');

  function openModal(tool) {
    var name   = tool.querySelector('.tool-name').textContent;
    var desc   = tool.querySelector('.tool-desc').textContent;
    var icon   = tool.querySelector('.tool-icon').innerHTML;
    var params = [];
    try { params = JSON.parse(tool.dataset.params || '[]'); } catch (e) {}

    mIcon.innerHTML    = icon;
    mName.textContent  = name;
    mDesc.textContent  = desc;
    mParams.innerHTML  = '';

    if (params.length > 0) {
      var label = document.createElement('div');
      label.className   = 'modal-params-label';
      label.textContent = 'Parameters';
      mParams.appendChild(label);

      params.forEach(function (p) {
        var row = document.createElement('div');
        row.className = 'param-row';

        var hdr = document.createElement('div');
        hdr.className = 'param-row-header';

        var pname = document.createElement('span');
        pname.className   = 'param-name';
        pname.textContent = p.name;
        hdr.appendChild(pname);

        var ptype = document.createElement('span');
        ptype.className   = 'param-type';
        ptype.textContent = p.type;
        hdr.appendChild(ptype);

        var badge = document.createElement('span');
        badge.className   = p.required ? 'param-required' : 'param-optional';
        badge.textContent = p.required ? 'required' : 'optional';
        hdr.appendChild(badge);

        row.appendChild(hdr);

        if (p.desc) {
          var pdesc = document.createElement('div');
          pdesc.className   = 'param-desc';
          pdesc.textContent = p.desc;
          row.appendChild(pdesc);
        }

        if (p.enum && p.enum.length) {
          var penum = document.createElement('div');
          penum.className = 'param-enum';
          p.enum.forEach(function (v) {
            var span = document.createElement('span');
            span.className   = 'param-enum-val';
            span.textContent = v;
            penum.appendChild(span);
          });
          row.appendChild(penum);
        }

        mParams.appendChild(row);
      });
    }

    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.tool').forEach(function (tool) {
    tool.addEventListener('click', function () { openModal(tool); });
    tool.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openModal(tool); }
    });
  });

  modalClose.addEventListener('click', closeModal);
  overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
}());
