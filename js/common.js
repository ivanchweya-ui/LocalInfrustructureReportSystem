const $ = id => document.getElementById(id);
async function api(url, opt) { const r = await fetch(url, opt); let d = {}; try { d = await r.json(); } catch (e) {} d.status = r.status; return d; }
function show(id, msg, ok) { const e = $(id); e.textContent = msg; e.className = msg ? 'msg ' + (ok ? 'ok' : 'err') : ''; }
function add(parent, tag, text) { const e = document.createElement(tag); e.textContent = text; parent.append(e); return e; }
function fillSelect(sel, items, val, label, first) {
  sel.replaceChildren();
  if (first) { const o = document.createElement('option'); o.value = ''; o.textContent = first; sel.append(o); }
  items.forEach(i => { const o = document.createElement('option'); o.value = i[val]; o.textContent = typeof label === 'function' ? label(i) : i[label]; sel.append(o); });
}
let installPrompt = null;
window.addEventListener('beforeinstallprompt', event => {
  event.preventDefault();
  installPrompt = event;
  const button = $('installApp');
  if (button) button.hidden = false;
});
function setupAppAccess() {
  const qr = $('appQr');
  if (!qr) return;
  const url = new URL('./report.html', document.baseURI).href;
  qr.setAttribute('aria-label', 'QR code for ' + url);
  new QRCode(qr, { text: url, width: 112, height: 112, correctLevel: QRCode.CorrectLevel.M });
  const notice = $('appShareMessage');
  if (['localhost', '127.0.0.1'].includes(location.hostname)) {
    notice.textContent = 'Local demo only: this address will not open on residents\' phones. Publish this app over HTTPS before sharing.';
  } else if (location.protocol !== 'https:') {
    notice.textContent = 'This link can be opened, but app installation requires HTTPS.';
  } else {
    notice.textContent = 'Scan to open this sub-county service. Use your browser menu to install or add it to your home screen.';
  }
  const installButton = $('installApp');
  if (installButton) {
    installButton.addEventListener('click', async () => {
      if (!installPrompt) return;
      installPrompt.prompt();
      await installPrompt.userChoice;
      installPrompt = null;
      installButton.hidden = true;
    });
  }
}
setupAppAccess();
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register(new URL('service-worker.js', document.baseURI))
      .catch(error => console.error('Could not register the app service worker.', error));
  });
}
async function logout() { await api('php/logout.php'); location = 'login.html'; }