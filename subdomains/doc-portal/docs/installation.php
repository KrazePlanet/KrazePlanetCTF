<!-- Installation Guide Documentation File -->
<div class="doc-content">
  <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-20 pb-3">
    <div>
      <span class="badge bg-warning bg-opacity-20 text-warning font-monospace px-3 py-1 mb-2">Setup Guide</span>
      <h2 class="text-white fw-bold mb-0"><i class="bi bi-download text-warning me-2"></i>Installation &amp; Setup</h2>
    </div>
    <a href="?doc=docs/installation.php&export=pdf" class="btn btn-outline-light btn-sm rounded-pill px-3" onclick="alert('Exporting PDF documentation package...'); return false;">
      <i class="bi bi-file-earmark-pdf text-danger me-1"></i> Export PDF
    </a>
  </div>

  <p class="text-muted">Follow these instructions to deploy the documentation portal viewer on Linux or Windows web servers.</p>

  <h5 class="text-white mt-3">1. Clone &amp; Deploy Filesystem Directory</h5>
  <pre class="p-3 rounded-3 bg-dark border border-secondary border-opacity-20 text-light font-monospace small">git clone https://github.com/docusphere/portal-engine.git /var/www/html/doc-portal</pre>

  <h5 class="text-white mt-3">2. Set Permissions &amp; Enable Module</h5>
  <pre class="p-3 rounded-3 bg-dark border border-secondary border-opacity-20 text-light font-monospace small">chmod -R 755 /var/www/html/doc-portal/docs</pre>
</div>
