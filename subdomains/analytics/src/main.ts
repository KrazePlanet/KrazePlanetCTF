/**
 * Main Application Entry Point
 */

import { ENV } from './config/environment';
import { fetchLiveMetrics, fetchLiveEvents, exportTelemetryReport } from './services/telemetryService';
import { updateMetricDisplay } from './dashboard/metricsController';
import { renderEventFeed } from './dashboard/eventStream';

console.log(`[PulseMetrics] Initialized Enterprise Console v${ENV.RELEASE_VERSION} (${ENV.ENVIRONMENT})`);

async function bootstrap() {
  try {
    const metricsData = await fetchLiveMetrics();
    if (metricsData && metricsData.metrics) {
      updateMetricDisplay(metricsData.metrics);
    }
    const eventsData = await fetchLiveEvents();
    if (eventsData && eventsData.events) {
      renderEventFeed(eventsData.events);
    }
  } catch (err) {
    console.warn('[PulseMetrics] Telemetry polling error:', err);
  }
}

// Expose SDK helpers on window object for developer debugging
(window as any).PulseMetrics = {
  version: ENV.RELEASE_VERSION,
  environment: ENV.ENVIRONMENT,
  exportReport: exportTelemetryReport,
  refresh: bootstrap
};

document.addEventListener('DOMContentLoaded', bootstrap);

