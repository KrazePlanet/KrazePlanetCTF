/**
 * Telemetry Service
 */

import { apiClient } from '../api/client';
import { ENDPOINTS } from '../api/endpoints';

export async function fetchLiveMetrics() {
  return apiClient.request(ENDPOINTS.METRICS);
}

export async function fetchLiveEvents() {
  return apiClient.request(ENDPOINTS.EVENTS);
}

export function exportTelemetryReport(title: string, format: string = 'csv') {
  const url = `${ENDPOINTS.REPORTS_EXPORT}?title=${encodeURIComponent(title)}&format=${encodeURIComponent(format)}`;
  window.open(url, '_blank');
}

