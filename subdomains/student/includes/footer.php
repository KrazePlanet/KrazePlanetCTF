<script>
// Dark mode
const body       = document.getElementById('body-root');
const savedTheme = localStorage.getItem('eduTheme');
if (savedTheme === 'dark') body.classList.add('dark');

function toggleDark() {
    body.classList.toggle('dark');
    localStorage.setItem('eduTheme', body.classList.contains('dark') ? 'dark' : 'light');
    const btn = document.getElementById('darkBtn');
    if (btn) btn.textContent = body.classList.contains('dark') ? '☀️ Light Mode' : '🌙 Dark Mode';
}

// Hamburger
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    if (sidebar) sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('active');
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
}
</script>
</body>
</html>