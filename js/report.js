(async () => {
  const o = await api('php/options.php');
  const methods = o.notifications || {};
  const email = $('contact_method').querySelector('option[value="email"]');
  const phone = $('contact_method').querySelector('option[value="phone"]');
  const whatsapp = $('contact_method').querySelector('option[value="whatsapp"]');
  email.disabled = !methods.email || !methods.email.available;
  phone.disabled = !methods.sms || !methods.sms.available;
  whatsapp.disabled = !methods.whatsapp || !methods.whatsapp.available;
  $('notificationHint').textContent = methods.email && methods.email.available && methods.email.testMode
    ? 'Email is captured in a local test inbox only. SMS and WhatsApp are unavailable in this demo.'
    : methods.email && methods.email.available
      ? 'Email is available. SMS and WhatsApp are not configured for this installation.'
      : 'Notifications are not configured for this installation. Save the reference code shown after submitting.';
  fillSelect($('category_id'), o.categories || [], 'category_id', 'category_name', 'Select category...');
  fillSelect($('area_id'), o.areas || [], 'area_id', a => a.area_name + ' (' + a.region + ' - ' + a.location + ')', 'Select area...');
})();
$('f').addEventListener('submit', async e => {
  e.preventDefault();
  const err = [], m = $('contact_method').value, c = $('contact_detail').value.trim();
  if (!$('category_id').value) err.push('Choose a category');
  if (!$('area_id').value) err.push('Choose an area');
  if ($('description').value.trim().length < 10) err.push('Description must be at least 10 characters');
  if ((m && !c) || (!m && c)) err.push('Choose a contact method and enter its contact detail, or leave both blank');
  else if (m && (m === 'email' ? !/^\S+@\S+\.\S+$/.test(c) : !/^(\+?254|0)[17]\d{8}$/.test(c.replace(/\s+/g, '')))) err.push('Enter a valid ' + (m === 'email' ? 'email address' : 'Kenyan phone number'));
  if (err.length) return show('msg', err.join('. '), false);
  const d = await api('php/submit_report.php', { method: 'POST', body: new FormData(e.target) });
  show('msg', d.success ? 'Report submitted.' : (d.message || 'Something went wrong'), d.success);
  if (d.success) {
    const r = $('result'); r.hidden = false; r.replaceChildren();
    add(r, 'h3', 'Your reference code: ' + d.code); add(r, 'p', d.message); add(r, 'p', 'Keep this code. You can use it on the Check Status page.');
    e.target.reset();
  }
});