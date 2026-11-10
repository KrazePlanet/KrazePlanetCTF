/**
 * API Client & HTTP Transport Layer
 */

import { ENV } from '../config/environment';
import { CLIENT_CREDENTIALS } from '../config/secrets';
import { ENDPOINTS } from './endpoints';

export class ApiClient {
  private baseUrl: string;

  constructor(baseUrl: string = ENV.API_BASE_URL) {
    this.baseUrl = baseUrl;
  }

  async request<T>(path: string, options: RequestInit = {}): Promise<T> {
    const headers = new Headers(options.headers || {});
    headers.set('Content-Type', 'application/json');
    headers.set('X-Client-Version', ENV.RELEASE_VERSION);
    headers.set('X-Ingestion-Token', CLIENT_CREDENTIALS.PUBLIC_INGESTION_TOKEN);

    const response = await fetch(`${this.baseUrl}${path}`, {
      ...options,
      headers
    });

    if (!response.ok) {
      throw new Error(`[ApiClient] Request to ${path} failed with HTTP ${response.status}`);
    }

    return response.json();
  }

  // Internal diagnostic actuator requiring administrative bearer
  async getClusterDiagnostics(): Promise<any> {
    return this.request(ENDPOINTS.CLUSTER_HEALTH, {
      headers: {
        'Authorization': CLIENT_CREDENTIALS.INTERNAL_DIAGNOSTIC_BEARER
      }
    });
  }
}

export const apiClient = new ApiClient();

