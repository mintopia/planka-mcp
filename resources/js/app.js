(function () {
  'use strict';

  /* ── state ── */
  var currentItem = null; // { type, name, desc, params/uri/isTemplate }

  /* ── refs ── */
  var apiKeyInput  = document.getElementById('api-key-input');
  var jsonSpan     = document.getElementById('json-auth');
  var cliSpan      = document.getElementById('cli-auth');
  var panelEmpty   = document.getElementById('panel-empty');
  var panelItem    = document.getElementById('panel-item');
  var itemIcon     = document.getElementById('item-icon');
  var itemName     = document.getElementById('item-name');
  var itemTag      = document.getElementById('item-tag');
  var itemDesc     = document.getElementById('item-desc');
  var itemParams   = document.getElementById('item-params-section');
  var tryInputs    = document.getElementById('try-inputs');
  var tryActions   = document.getElementById('try-actions');
  var tryRunBtn    = document.getElementById('try-run-btn');
  var tryResp      = document.getElementById('try-response');
  var tryNoKeyNote = document.getElementById('try-no-key-note');

  function hide(el) { if (el) el.classList.add('d-none'); }
  function show(el) { if (el) el.classList.remove('d-none'); }

  function getKey() { return apiKeyInput ? apiKeyInput.value.trim() : ''; }

  function escape(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ── api key live update ── */
  if (apiKeyInput) {
    apiKeyInput.addEventListener('input', function () {
      var key = getKey();
      if (jsonSpan) jsonSpan.innerHTML = key ? '"Bearer ' + escape(key) + '"' : '"Bearer &lt;your-planka-api-key&gt;"';
      if (cliSpan)  cliSpan.innerHTML  = key ? '"Authorization: Bearer ' + escape(key) + '"' : '"Authorization: Bearer &lt;your-planka-api-key&gt;"';
      refreshTryItState();
    });
  }

  function refreshTryItState() {
    if (!currentItem) return;
    var key = getKey();
    if (tryNoKeyNote) { key ? hide(tryNoKeyNote) : show(tryNoKeyNote); }
    if (tryActions)   { key ? show(tryActions)   : hide(tryActions);   }
  }

  /* ── copy buttons ── */
  var copyIcon  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667l0 -8.666" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg>';
  var checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5l10 -10" /></svg>';

  document.querySelectorAll('.copy-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var pre = btn.closest('.code-block').querySelector('pre');
      navigator.clipboard.writeText((pre.textContent || pre.innerText).trim()).then(function () {
        btn.innerHTML = checkIcon; btn.classList.add('copied');
        setTimeout(function () { btn.innerHTML = copyIcon; btn.classList.remove('copied'); }, 1500);
      });
    });
  });

  /* ── param input builder ── */
  function buildParamInput(p) {
    var wrap = document.createElement('div');
    wrap.className = 'try-field';

    var lbl = document.createElement('label');
    lbl.className = 'try-label';
    var ns = document.createElement('span'); ns.textContent = p.name; lbl.appendChild(ns);
    var bd = document.createElement('span');
    bd.className = p.required ? 'param-required' : 'param-optional';
    bd.textContent = p.required ? 'required' : 'optional';
    lbl.appendChild(bd);
    wrap.appendChild(lbl);

    var ctrl;
    if (p.enum && p.enum.length) {
      ctrl = document.createElement('select'); ctrl.className = 'try-select';
      if (!p.required) { var blank = document.createElement('option'); blank.value=''; blank.textContent='— choose —'; ctrl.appendChild(blank); }
      p.enum.forEach(function(v){ var o=document.createElement('option'); o.value=v; o.textContent=v; ctrl.appendChild(o); });
    } else {
      ctrl = document.createElement('input');
      ctrl.className = 'try-input';
      ctrl.type = p.type === 'integer' ? 'number' : 'text';
      ctrl.placeholder = p.desc || p.name;
    }
    ctrl.dataset.paramName = p.name;
    wrap.appendChild(ctrl);
    return wrap;
  }

  function collectArgs(container) {
    var args = {};
    container.querySelectorAll('[data-param-name]').forEach(function (el) {
      var v = el.value.trim();
      if (v !== '') args[el.dataset.paramName] = (el.tagName === 'INPUT' && el.type === 'number') ? Number(v) : v;
    });
    return args;
  }

  function showSpinner(el) {
    show(el); el.className = 'try-response';
    el.innerHTML = '<div class="try-spinner"></div>';
  }

  function showResult(el, data, isError) {
    show(el); el.className = 'try-response' + (isError ? ' error' : '');
    var pre = document.createElement('pre');
    pre.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
    el.innerHTML = ''; el.appendChild(pre);
  }

  /* ── select item ── */
  function selectItem(el) {
    // deactivate previous
    document.querySelectorAll('.nav-item.active').forEach(function(n){ n.classList.remove('active'); });
    el.classList.add('active');

    var type = el.dataset.type;
    var name = el.dataset.name || '';
    var desc = el.dataset.desc || '';

    // reset panel
    tryInputs.innerHTML = '';
    hide(tryResp);
    tryResp.innerHTML = '';

    // populate header
    itemName.textContent = name;
    itemDesc.textContent = desc;
    itemParams.innerHTML = '';

    if (type === 'tool') {
      var icon = el.dataset.icon || '&#128736;';
      itemIcon.innerHTML = '<div class="item-icon-inner">' + icon + '</div>';
      itemTag.textContent = 'MCP Tool';
      itemTag.className = 'item-tag tag-tool';

      var params = [];
      try { params = JSON.parse(el.dataset.params || '[]'); } catch(e){}

      if (params.length > 0) {
        var sec = document.createElement('div'); sec.className = 'params-section';
        var lbl = document.createElement('div'); lbl.className = 'params-section-label'; lbl.textContent = 'Parameters'; sec.appendChild(lbl);
        params.forEach(function(p) {
          var row = document.createElement('div'); row.className = 'param-row';
          var hdr = document.createElement('div'); hdr.className = 'param-row-header';
          var pn = document.createElement('span'); pn.className='param-name'; pn.textContent=p.name; hdr.appendChild(pn);
          var pt = document.createElement('span'); pt.className='param-type'; pt.textContent=p.type; hdr.appendChild(pt);
          var pb = document.createElement('span'); pb.className=p.required?'param-required':'param-optional'; pb.textContent=p.required?'required':'optional'; hdr.appendChild(pb);
          row.appendChild(hdr);
          if (p.desc) { var pd=document.createElement('div'); pd.className='param-desc'; pd.textContent=p.desc; row.appendChild(pd); }
          if (p.enum && p.enum.length) {
            var pe=document.createElement('div'); pe.className='param-enum';
            p.enum.forEach(function(v){ var s=document.createElement('span'); s.className='param-enum-val'; s.textContent=v; pe.appendChild(s); });
            row.appendChild(pe);
          }
          sec.appendChild(row);
          tryInputs.appendChild(buildParamInput(p));
        });
        itemParams.appendChild(sec);
      }

      tryRunBtn.textContent = 'Run';
      currentItem = { type: 'tool', name: name, params: params };

    } else {
      itemIcon.innerHTML = '<div class="item-icon-inner">&#128196;</div>';
      itemTag.textContent = 'MCP Resource';
      itemTag.className = 'item-tag tag-resource';

      var uri = el.dataset.uri || '';
      var isTemplate = el.dataset.isTemplate === 'true';

      var uriDiv = document.createElement('div'); uriDiv.className = 'resource-uri-display';
      var uriLabel = document.createElement('div'); uriLabel.className = 'params-section-label'; uriLabel.textContent = 'URI'; uriDiv.appendChild(uriLabel);
      var uriVal = document.createElement('div'); uriVal.className = 'resource-uri'; uriVal.textContent = uri; uriDiv.appendChild(uriVal);
      itemParams.appendChild(uriDiv);

      if (isTemplate) {
        var varNames = (uri.match(/\{([^}]+)\}/g) || []).map(function(m){ return m.slice(1,-1); });
        varNames.forEach(function(v) {
          tryInputs.appendChild(buildParamInput({ name: v, type: 'string', required: true, desc: '' }));
        });
      }

      tryRunBtn.textContent = 'Read';
      currentItem = { type: 'resource', name: name, isTemplate: isTemplate };
    }

    refreshTryItState();
    hide(panelEmpty);
    show(panelItem);

    // scroll panel to top
    var panel = document.getElementById('main-panel');
    if (panel) panel.scrollTop = 0;
  }

  /* ── nav item events ── */
  document.querySelectorAll('.nav-item').forEach(function (el) {
    el.addEventListener('click', function () { selectItem(el); });
    el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); selectItem(el); }
    });
  });

  /* ── run button ── */
  if (tryRunBtn) {
    tryRunBtn.addEventListener('click', function () {
      var key = getKey();
      if (!key || !currentItem) return;
      var args = collectArgs(tryInputs);
      showSpinner(tryResp);
      var endpoint = currentItem.type === 'tool' ? '/test/tool' : '/test/resource';
      fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + key },
        body: JSON.stringify({ name: currentItem.name, arguments: args }),
      }).then(function(r) {
        return r.json().then(function(d){ return { ok: r.ok, data: d }; });
      }).then(function(res) {
        showResult(tryResp, res.data, !res.ok);
      }).catch(function(err) {
        showResult(tryResp, err.message || 'Network error', true);
      });
    });
  }

}());
