/**
 * Live Event Stream Component
 */

export function renderEventFeed(events: any[]) {
  const tbody = document.getElementById('events-tbody');
  if (!tbody || !events.length) return;

  tbody.innerHTML = events.map(evt => `
    <tr>
      <td><code class="evt-code">${evt.name}</code></td>
      <td>${evt.tenant_id}</td>
      <td><span class="badge-geo">${evt.geo}</span> ${evt.ip_address}</td>
      <td>${evt.latency_ms}ms</td>
      <td><span class="badge-status status-success">${evt.status}</span></td>
      <td class="text-muted">${evt.timestamp.split('T')[1].replace('Z','')} UTC</td>
    </tr>
  `).join('');
}

