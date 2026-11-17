async function loadOptions() {
  const o = await api('php/options.php');
  fillSelect($('fc'), o.categories || [], 'category_id', 'category_name', 'All categories');
  fillSelect($('fa'), o.areas || [], 'area_id', a => a.area_name + ' (' + a.region + ')', 'All areas');
}
async function load() {
  const q = new URLSearchParams({ category: $('fc').value, area: $('fa').value, status: $('fs').value });
  const d = await api('php/dashboard.php?' + q);
  if (d.status === 401) return location = 'login.html';
  $('who').textContent = d.staff;
  const s = d.summary, st = $('stats'); st.replaceChildren();
  [['Total', s.total], ['Submitted', s.submitted], ['Under Review', s.review], ['In Progress', s.progress], ['Resolved', s.resolved]].forEach(([l, v]) => {
    const c = document.createElement('div'); c.className = 'card stat'; add(c, 'strong', v || 0); add(c, 'span', l); st.append(c);
  });
  const tb = $('rows'); tb.replaceChildren();
  if (!d.reports.length) { const tr = document.createElement('tr'); add(tr, 'td', 'No reports found').colSpan = 6; tb.append(tr); }
  d.reports.forEach(r => {
    const tr = document.createElement('tr');
    [r.reference_code, r.category_name, r.area_name, r.current_status, r.date_submitted].forEach(v => add(tr, 'td', v));
    const td = document.createElement('td'), a = add(td, 'a', r.current_status === 'Resolved' ? 'View' : 'Update'); a.href = 'update.html?id=' + r.report_id;
    tr.append(td); tb.append(tr);
  });
}
$('apply').onclick = load;
$('reset').onclick = () => { $('fc').value = ''; $('fa').value = ''; $('fs').value = ''; load(); };
$('af').addEventListener('submit', async e => {
  e.preventDefault();
  const d = await api('php/add_area.php', { method: 'POST', body: new FormData(e.target) });
  show('amsg', d.message || 'Failed', d.success);
  if (d.success) { e.target.reset(); loadOptions(); }
});
loadOptions().then(load);