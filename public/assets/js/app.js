// PartsDesk shared behaviour: theme switch, phone menu, confirm prompts, dismissable messages.
(function () {
  'use strict';

  var root = document.documentElement;
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  // ---- Theme: Light / Dark / System ----
  function applyTheme(theme) {
    if (theme === 'light' || theme === 'dark') {
      root.setAttribute('data-theme', theme);
    } else {
      root.removeAttribute('data-theme');
    }
    document.querySelectorAll('[data-theme-choice]').forEach(function (btn) {
      btn.setAttribute('aria-pressed', btn.getAttribute('data-theme-choice') === theme ? 'true' : 'false');
    });
  }

  var saved = root.getAttribute('data-theme-pref') || 'system';
  try { saved = localStorage.getItem('partsdesk-theme') || saved; } catch (e) { /* storage blocked */ }
  applyTheme(saved);

  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-theme-choice]');
    if (!btn) { return; }
    var theme = btn.getAttribute('data-theme-choice');
    applyTheme(theme);
    try { localStorage.setItem('partsdesk-theme', theme); } catch (e) { /* storage blocked */ }
    // Signed-in users: remember the choice on their account too
    if (root.hasAttribute('data-signed-in')) {
      fetch(root.getAttribute('data-base') + '/profile/theme', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ theme: theme })
      }).catch(function () { /* offline: the local choice still applies */ });
    }
  });

  // ---- Phone menu ----
  document.addEventListener('click', function (event) {
    var toggle = event.target.closest('[data-menu-toggle]');
    if (!toggle) { return; }
    var sidebar = document.querySelector('.sidebar');
    var open = sidebar.classList.toggle('open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  // ---- Confirm before destructive actions: <form data-confirm="Archive this product?"> ----
  document.addEventListener('submit', function (event) {
    var message = event.target.getAttribute('data-confirm');
    if (message && !window.confirm(message)) {
      event.preventDefault();
    }
  });

  // ---- Dismiss flash messages ----
  document.addEventListener('click', function (event) {
    var close = event.target.closest('[data-dismiss]');
    if (close) { close.closest('.flash').remove(); }
  });

  // ---- Filters that submit as soon as they change: <select data-autosubmit> ----
  document.addEventListener('change', function (event) {
    if (event.target.matches('[data-autosubmit]')) { event.target.form.submit(); }
  });
})();
