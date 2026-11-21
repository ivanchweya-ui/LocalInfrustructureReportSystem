$('f').addEventListener('submit', async e => {
  e.preventDefault();
  const d = await api('php/signup.php', { method: 'POST', body: new FormData(e.target) });
  show('msg', d.message || (d.success ? 'Account request submitted.' : 'Could not submit account request.'), d.success);
  if (d.success) e.target.reset();
});
