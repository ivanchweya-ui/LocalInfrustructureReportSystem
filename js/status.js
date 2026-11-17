$('go').addEventListener('click', async () => {
  const c = $('code').value.trim();
  if (!c) return show('msg', 'Enter your reference code', false);
  const d = await api('php/lookup_status.php?code=' + encodeURIComponent(c)), box = $('result');
  if (!d.success) { box.hidden = true; return show('msg', d.message || 'Not found', false); }
  show('msg', '', true);
  const r = d.report; box.hidden = false; box.replaceChildren();
  add(box, 'h3', 'Reference: ' + r.reference_code + '  -  ' + r.current_status);
  add(box, 'p', 'Category: ' + r.category_name + ' | Area: ' + r.area_name + (r.landmark ? ' | Landmark: ' + r.landmark : ''));
  add(box, 'p', 'Submitted: ' + r.date_submitted);
  const ul = document.createElement('ul');
  r.history.forEach(h => add(ul, 'li', h.updated_at + ' - ' + h.new_status + (h.resolution_note ? ': ' + h.resolution_note : '')));
  box.append(ul);
});