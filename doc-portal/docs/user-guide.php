<!-- User Guide Documentation File -->
<div class="doc-content">
  <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-20 pb-3">
    <div>
      <span class="badge bg-success bg-opacity-20 text-success font-monospace px-3 py-1 mb-2">Getting Started</span>
      <h2 class="text-white fw-bold mb-0"><i class="bi bi-book text-success me-2"></i>Developer User Guide</h2>
    </div>
    <a href="?doc=docs/user-guide.php&export=pdf" class="btn btn-outline-light btn-sm rounded-pill px-3" onclick="alert('Exporting PDF documentation package...'); return false;">
      <i class="bi bi-file-earmark-pdf text-danger me-1"></i> Export PDF
    </a>
  </div>

  <p class="text-muted leading-relaxed">Welcome to the official developer user guide. This documentation details core system configuration, authentication workflows, and microservice integration patterns.</p>

  <h4 class="text-white mt-4 mb-2"><i class="bi bi-key text-warning me-2"></i>1. Authentication &amp; API Keys</h4>
  <p class="text-muted">All API requests must include a Bearer token in the <code>Authorization</code> header or an API key via <code>X-Doc-Key</code> query parameters.</p>
  <div class="p-3 rounded-3 bg-dark border border-secondary border-opacity-20 font-monospace text-success small mb-3">
    Authorization: Bearer doc_sec_9938219481a8b92d
  </div>

  <h4 class="text-white mt-4 mb-2"><i class="bi bi-diagram-2 text-info me-2"></i>2. Route Mapping &amp; Content Rendering</h4>
  <p class="text-muted">Document viewer modules utilize dynamic filesystem resolution to render markdown and PHP guides seamlessly based on user selection.</p>
</div>
