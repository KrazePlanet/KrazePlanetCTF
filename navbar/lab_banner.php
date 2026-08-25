<?php
// lab_banner.php - Floating Control Header for Isolated Sandboxes
require_once __DIR__ . '/../config/domain.php';

$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$hostNoPort = preg_replace('/:\d+$/', '', strtolower($httpHost));
$baseDomain = getKrazeBaseDomain($hostNoPort);

$isInstanceSubdomain = false;
$parsedUser = '';
$parsedLab = '';

// Check if current host is an instance subdomain (e.g. rix4uni-diagnostics.kzlabs.in or rix4uni.diagnostics.localhost)
if (preg_match('/^([a-zA-Z0-9_]+)[-.]([a-zA-Z0-9_\-]+)\.(.+)$/i', $hostNoPort, $m)) {
    $parsedUser = htmlspecialchars($m[1]);
    $parsedLab = htmlspecialchars($m[2]);
    if ($parsedLab !== 'mailpit' && $parsedLab !== 'mail') {
        $isInstanceSubdomain = true;
    }
}

if ($isInstanceSubdomain):
?>
<!-- PortSwigger-Style Persistent Lab Control Bar -->
<div id="krazeLabControlBar" style="position: fixed; top: 0; left: 0; right: 0; height: 44px; background: linear-gradient(90deg, #0b1120 0%, #0f172a 100%); border-bottom: 1px solid rgba(56, 189, 248, 0.35); box-shadow: 0 4px 20px rgba(0,0,0,0.6); z-index: 999999; display: flex; align-items: center; justify-content: space-between; padding: 0 16px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #f8fafc; font-size: 13px;">
  <div style="display: flex; align-items: center; gap: 12px;">
    <a href="//<?php echo htmlspecialchars($baseDomain); ?>" target="_blank" style="display: flex; align-items: center; gap: 6px; text-decoration: none; color: #38bdf8; font-weight: 700; font-size: 14px;">
      <span>🪐 KrazePlanet</span>
    </a>
    <span style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
      Isolated Sandbox
    </span>
    <span style="color: #94a3b8; font-size: 12px; display: inline-block;">
      Target: <strong style="color: #ffffff;"><?php echo $parsedLab; ?></strong> | User: <strong style="color: #38bdf8;"><?php echo $parsedUser; ?></strong>
    </span>
  </div>

  <div style="display: flex; align-items: center; gap: 10px;">
    <!-- Live Timer -->
    <div style="display: flex; align-items: center; gap: 6px; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); padding: 3px 10px; border-radius: 6px; font-family: monospace; font-size: 13px; font-weight: 700; color: #fbbf24;">
      <span>⏳</span>
      <span id="krazeLabTimer">Loading...</span>
    </div>

    <!-- Restart Lab Button -->
    <button onclick="restartKrazeSandbox()" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: all 0.2s;">
      🔄 Restart Lab
    </button>

    <!-- Extend +1h Button -->
    <button onclick="extendKrazeSandbox()" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: all 0.2s;">
      ⏳ Extend +1h
    </button>

    <!-- Main Portal Link -->
    <a href="//<?php echo htmlspecialchars($baseDomain); ?>" style="background: rgba(255,255,255,0.08); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.15); padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px;">
      🏠 Portal
    </a>
  </div>
</div>

<style>
  body {
    padding-top: 44px !important;
  }
</style>

<script>
(function() {
  const portalOrigin = '//<?php echo htmlspecialchars($baseDomain); ?>';
  const labId = '<?php echo $parsedLab; ?>';
  let remainingSeconds = 3600;

  function updateTimerDisplay() {
    const el = document.getElementById('krazeLabTimer');
    if (!el) return;
    if (remainingSeconds <= 0) {
      el.innerText = 'EXPIRED';
      el.style.color = '#ef4444';
      return;
    }
    const m = Math.floor(remainingSeconds / 60).toString().padStart(2, '0');
    const s = (remainingSeconds % 60).toString().padStart(2, '0');
    el.innerText = `${m}:${s}`;
  }

  // Fetch initial remaining time from instance API
  fetch(`${portalOrigin}/api/instance_api.php?action=launch_lab&lab_id=${encodeURIComponent(labId)}`, { credentials: 'include' })
    .then(r => r.json())
    .then(d => {
      if (d.success && d.seconds_left) {
        remainingSeconds = d.seconds_left;
        updateTimerDisplay();
      }
    })
    .catch(() => {});

  setInterval(() => {
    if (remainingSeconds > 0) {
      remainingSeconds--;
      updateTimerDisplay();
    }
  }, 1000);

  window.restartKrazeSandbox = function() {
    if (!confirm('Are you sure you want to restart this lab? All files & state will be restored to original pristine condition.')) return;
    const fd = new FormData();
    fd.append('action', 'restart_lab');
    fd.append('lab_id', labId);
    fetch(`${portalOrigin}/api/instance_api.php`, { method: 'POST', body: fd, credentials: 'include' })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          alert('Lab restarted cleanly! Reloading...');
          window.location.reload();
        } else {
          alert(d.error || 'Failed to restart lab.');
        }
      })
      .catch(() => {
        window.location.reload();
      });
  };

  window.extendKrazeSandbox = function() {
    const fd = new FormData();
    fd.append('action', 'extend_lab');
    fd.append('lab_id', labId);
    fetch(`${portalOrigin}/api/instance_api.php`, { method: 'POST', body: fd, credentials: 'include' })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          remainingSeconds = d.seconds_left || (remainingSeconds + 3600);
          updateTimerDisplay();
          alert('Lab extended by +60 minutes!');
        } else {
          alert(d.error || 'Failed to extend lab.');
        }
      });
  };
})();
</script>
<?php endif; ?>