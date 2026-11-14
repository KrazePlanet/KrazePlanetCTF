/*! MediCore patient & clinician portal — v2.4.1 */
(function () {
  'use strict';

  var cfg = document.currentScript ? document.currentScript.dataset : {};
  var BASE = cfg.base || '';

  // Portal routes (shared by the patient and clinician menus).
  var ROUTES = {
    patient: {
      overview:     '/portal',
      reports:      '/portal/reports',
      report:       '/portal/reports/view?id=:id',
      reportPdf:    '/portal/reports/print?id=:id',
      bookTest:     '/portal/book-test',
      appointments: '/portal/appointments',
      profile:      '/portal/profile'
    },
    clinician: {
      home:         '/portal/clinician',
      directory:    '/portal/clinician/patients',
      search:       '/portal/clinician/patients?q=:q',
      chart:        '/portal/clinician/patients/view?id=:id'
    }
  };

  function route(group, name, params) {
    var p = ROUTES[group] && ROUTES[group][name];
    if (!p) return null;
    Object.keys(params || {}).forEach(function (k) {
      p = p.replace(':' + k, encodeURIComponent(params[k]));
    });
    return BASE + p;
  }
  window.MediCore = { route: route };

  // Keyboard shortcut: "/" focuses the first search box on the page.
  document.addEventListener('keydown', function (e) {
    if (e.key !== '/' || /input|textarea|select/i.test(e.target.tagName)) return;
    var s = document.querySelector('input[name="q"]');
    if (s) { e.preventDefault(); s.focus(); }
  });

  // Idle sign-out warning (sessions expire after 30 minutes of inactivity).
  var IDLE_MS = 28 * 60 * 1000, timer;
  function warn() {
    if (document.getElementById('mc-idle')) return;
    var d = document.createElement('div');
    d.id = 'mc-idle';
    d.className = 'flash flash-info';
    d.style.cssText = 'position:fixed;right:20px;bottom:20px;z-index:50;max-width:340px;box-shadow:0 6px 24px rgba(0,0,0,.12)';
    d.textContent = 'For your security, you will be signed out soon due to inactivity. Move the mouse or press a key to stay signed in.';
    document.body.appendChild(d);
  }
  function reset() {
    clearTimeout(timer);
    var d = document.getElementById('mc-idle');
    if (d) d.remove();
    timer = setTimeout(warn, IDLE_MS);
  }
  ['mousemove', 'keydown', 'click', 'scroll'].forEach(function (ev) {
    document.addEventListener(ev, reset, { passive: true });
  });
  reset();
})();
