<!-- API Reference Documentation File -->
<div class="doc-content">
  <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-20 pb-3">
    <div>
      <span class="badge bg-info bg-opacity-20 text-info font-monospace px-3 py-1 mb-2">API v2.4</span>
      <h2 class="text-white fw-bold mb-0"><i class="bi bi-code-slash text-info me-2"></i>REST API Reference</h2>
    </div>
    <a href="?doc=docs/api-reference.php&export=pdf" class="btn btn-outline-light btn-sm rounded-pill px-3" onclick="alert('Exporting PDF documentation package...'); return false;">
      <i class="bi bi-file-earmark-pdf text-danger me-1"></i> Export PDF
    </a>
  </div>

  <p class="text-muted">The REST API provides programmatic access to documentation trees, filesystem metadata, and automated pdf compilation services.</p>

  <div class="card bg-dark border-secondary border-opacity-20 mb-3">
    <div class="card-header bg-secondary bg-opacity-10 text-white font-monospace">
      <span class="badge bg-success me-2">GET</span> /api/v2/documents/resolve
    </div>
    <div class="card-body">
      <h6 class="text-white">Query Parameters:</h6>
      <ul class="text-muted small mb-0 font-monospace">
        <li><code>doc</code> (string) - Path to document resource file</li>
        <li><code>version</code> (string) - Document revision version</li>
      </ul>
    </div>
  </div>
</div>
