$('f').addEventListener('submit', async e => {
  e.preventDefault();
  const d = await api('php/login.php', { method: 'POST', body: new FormData(e.target) });
  if (d.success) location = 'dashboard.html'; else show('msg', d.message || 'Login failed', false);
});