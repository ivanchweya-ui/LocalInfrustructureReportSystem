const $ = id => document.getElementById(id);
async function api(url, opt) { const r = await fetch(url, opt); let d = {}; try { d = await r.json(); } catch (e) {} d.status = r.status; return d; }
function show(id, msg, ok) { const e = $(id); e.textContent = msg; e.className = msg ? 'msg ' + (ok ? 'ok' : 'err') : ''; }
function add(parent, tag, text) { const e = document.createElement(tag); e.textContent = text; parent.append(e); return e; }
function fillSelect(sel, items, val, label, first) {
  sel.replaceChildren();
  if (first) { const o = document.createElement('option'); o.value = ''; o.textContent = first; sel.append(o); }
  items.forEach(i => { const o = document.createElement('option'); o.value = i[val]; o.textContent = typeof label === 'function' ? label(i) : i[label]; sel.append(o); });
}
async function logout() { await api('php/logout.php'); location = 'login.html'; }