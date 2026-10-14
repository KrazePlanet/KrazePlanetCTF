<?php
// auth_modals.php - Sign-in now happens on the central KrazePlanet login server
// (shared by every lab site), so the old login / signup / forgot dialogs are
// replaced by redirects. Existing callers (openLoginModal(), ?modal=login,
// ?redirect=..., the pending-lab redirect) keep working.
?>
<script>
function krazeGoToLogin(screen, returnTo) {
  const pending = sessionStorage.getItem('kraze_pending_lab_url');
  sessionStorage.removeItem('kraze_pending_lab_url');
  const target = returnTo || pending || (location.pathname + location.search);
  const q = new URLSearchParams({ return: target });
  if (screen) q.set('screen', screen);
  location.href = '/sso/login.php?' + q.toString();
}
function openLoginModal(e)  { if (e && e.preventDefault) e.preventDefault(); krazeGoToLogin(''); }
function openSignupModal(e) { if (e && e.preventDefault) e.preventDefault(); krazeGoToLogin('signup'); }
function openForgotModal(e) { if (e && e.preventDefault) e.preventDefault(); krazeGoToLogin('forgot'); }

(function () {
  const p = new URLSearchParams(location.search);
  const modal = (p.get('modal') || '').toLowerCase();
  const screens = { login: '', signin: '', signup: 'signup', register: 'signup', forgot: 'forgot', reset: 'forgot' };
  if (modal in screens) krazeGoToLogin(screens[modal], p.get('redirect') || '/');
})();
</script>
