async function loadOptions() {
  const o = await api('php/options.php');
  fillSelect($('fc'), o.categories || [], 'category_id', 'category_name', 'All categories');
  fillSelect($('fa'), o.areas || [], 'area_id', a => a.area_name + ' (' + a.region + ' - ' + a.location + ')', 'All areas');
}
async function load() {
  const q = new URLSearchParams({ category: $('fc').value, area: $('fa').value, status: $('fs').value });
  const d = await api('php/dashboard.php?' + q);
  if (d.status === 401) return location = 'login.html';
  $('who').textContent = d.staff;
  $('instance').textContent = d.instance ? d.instance.subcounty_name + ', ' + d.instance.county_name : '';
  $('approvalPanel').hidden = d.role !== 'system_admin';
  if (d.role === 'system_admin') loadStaffRequests(d.csrf_token);
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
async function loadStaffRequests(csrfToken) {
  const panel = $('staffRequests'), message = $('requestMessage');
  panel.replaceChildren();
  const d = await api('php/staff_requests.php');
  if (!d.success) { message.textContent = d.message || 'Could not load account requests.'; return; }
  message.textContent = d.requests.length ? '' : 'There are no pending requests.';
  d.requests.forEach(request => {
    const item = document.createElement('div'); item.className = 'card';
    add(item, 'strong', request.full_name);
    add(item, 'p', request.username + ' - ' + request.email + ' - ' + request.created_at);
    ['approve', 'reject'].forEach(decision => {
      const button = add(item, 'button', decision === 'approve' ? 'Approve' : 'Reject');
      if (decision === 'reject') button.className = 'alt';
      button.type = 'button';
      button.addEventListener('click', async () => {
        const body = new FormData();
        body.set('staff_id', request.staff_id);
        body.set('decision', decision);
        body.set('csrf_token', csrfToken);
        const result = await api('php/approve_staff.php', { method: 'POST', body });
        message.textContent = result.message || 'Could not update the request.';
        if (result.success) loadStaffRequests(csrfToken);
      });
    });
    panel.append(item);
  });
}
$('apply').onclick = load;
$('reset').onclick = () => { $('fc').value = ''; $('fa').value = ''; $('fs').value = ''; load(); };
loadOptions().then(load);