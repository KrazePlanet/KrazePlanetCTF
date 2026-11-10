/**
 * PulseMetrics API Route Registry & Parameter Mapping
 */

export const ENDPOINTS = {
  // Public Telemetry Endpoints
  METRICS: '/api/v2/metrics',
  EVENTS: '/api/v2/events',
  TELEMETRY_STREAM: '/api/v2/telemetry/stream',

  /**
   * Export Telemetry Report
   * @endpoint /reports/export.php
   * @param title Title of the exported dataset (rendered in export summary header)
   * @param format Export format (csv | json | parquet)
   */
  REPORTS_EXPORT: '/reports/export.php',

  /**
   * Event Filtering & Search
   * @endpoint /
   * @param filter Ingestion query filter expression
   */
  EVENT_FILTER: '/?filter=',

  // Restricted Internal Diagnostic Routes (Requires INTERNAL_DIAGNOSTIC_BEARER)
  CLUSTER_HEALTH: '/api/v2/internal/cluster/health',
  TENANT_OVERRIDE: '/api/v2/internal/tenants/debug-export',
  CACHE_FLUSH: '/api/v2/internal/system/cache/flush'
};

