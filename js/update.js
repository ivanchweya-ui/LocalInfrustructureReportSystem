const id = new URLSearchParams(location.search).get('id');
let csrfToken = '';
const order = ['Submitted', 'Under Review', 'In Progress', 'Resolved'];
async function load() {
  const d = await api('php/get_report.php?id=' + encodeURIComponent(id));
  if (d.status === 401) return location = 'login.html';
  if (!d.success) return show('msg', d.message || 'Not found', false);
  csrfToken = d.csrf_token;
  const r = d.report; $('ref').textContent = r.reference_code;
  const box = $('details'); box.replaceChildren();
  add(box, 'h3', 'Report details - ' + r.current_status);
  add(box, 'p', 'Category: ' + r.category_name + ' | Area: ' + r.area_name + (r.landmark ? ' | Landmark: ' + r.landmark : ''));
  add(box, 'p', 'Submitted: ' + r.date_submitted); add(box, 'p', 'Description: ' + r.description);
  if (r.photo_path) { const im = document.createElement('img'); im.className = 'photo'; im.src = r.photo_path; box.append(im); }
  const next = order[order.indexOf(r.current_status) + 1];
  if (next) fillSelect($('new_status'), [{ v: next }], 'v', 'v'); else { $('f').hidden = true; show('msg', 'This report is resolved.', true); }
  const ul = $('hist'); ul.replaceChildren();
  r.history.forEach(h => add(ul, 'li', h.updated_at + ' - ' + h.new_status + (h.full_name ? ' (' + h.full_name + ')' : '') + (h.resolution_note ? ': ' + h.resolution_note : '')));
}
$('f').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(e.target); fd.append('report_id', id); fd.append('csrf_token', csrfToken);
  const d = await api('php/update_status.php', { method: 'POST', body: fd });
  show('msg', d.message || 'Failed', d.success);
  if (d.success) load();
});
load();