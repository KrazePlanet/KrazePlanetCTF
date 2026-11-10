/**
 * Metrics Controller
 */

export function updateMetricDisplay(metrics: any) {
  const elEvents = document.getElementById('metric-events');
  const elUsers = document.getElementById('metric-users');
  const elLatency = document.getElementById('metric-latency');
  const elRequests = document.getElementById('metric-requests');

  if (elEvents && metrics.events_ingested_24h) {
    elEvents.innerText = (metrics.events_ingested_24h / 1000000).toFixed(1) + 'M';
  }
  if (elUsers && metrics.daily_active_users) {
    elUsers.innerText = Number(metrics.daily_active_users).toLocaleString();
  }
  if (elLatency && metrics.ingestion_p95_latency_ms) {
    elLatency.innerText = metrics.ingestion_p95_latency_ms + 'ms';
  }
  if (elRequests && metrics.api_gateway_requests_per_hr) {
    elRequests.innerText = (metrics.api_gateway_requests_per_hr / 1000000).toFixed(2) + 'M/hr';
  }
}

