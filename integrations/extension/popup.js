const $ = id => document.getElementById(id);

function load() {
  chrome.storage.sync.get(['base', 'key'], (cfg) => {
    if (!cfg.base || !cfg.key) { showSettings(); }
    $('base').value = cfg.base || '';
    $('key').value = cfg.key || '';
  });
  // prefill current tab URL
  chrome.tabs && chrome.tabs.query({ active: true, currentWindow: true }, (tabs) => {
    if (tabs && tabs[0]) $('url').value = tabs[0].url;
  });
}

function showSettings() { $('main').style.display = 'none'; $('settingsPane').style.display = 'block'; }
function showMain() { $('settingsPane').style.display = 'none'; $('main').style.display = 'block'; }

$('openSettings').onclick = (e) => { e.preventDefault(); showSettings(); };
$('backMain').onclick = (e) => { e.preventDefault(); showMain(); };
$('save').onclick = () => {
  chrome.storage.sync.set({ base: $('base').value.trim().replace(/\/$/, ''), key: $('key').value.trim() }, showMain);
};

$('go').onclick = () => {
  $('err').textContent = '';
  chrome.storage.sync.get(['base', 'key'], async (cfg) => {
    if (!cfg.base || !cfg.key) { showSettings(); return; }
    const url = $('url').value.trim();
    if (!url) return;
    $('go').textContent = '…';
    try {
      const res = await fetch(cfg.base + '/api/v1/links', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + cfg.key, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ destination: url }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed');
      $('short').value = data.data.short_url;
      $('result').style.display = 'block';
    } catch (e) {
      $('err').textContent = e.message;
    } finally {
      $('go').textContent = 'Shorten';
    }
  });
};

$('copy').onclick = () => {
  $('short').select();
  navigator.clipboard.writeText($('short').value);
  $('copy').textContent = '✓';
  setTimeout(() => $('copy').textContent = 'Copy', 1200);
};

load();
