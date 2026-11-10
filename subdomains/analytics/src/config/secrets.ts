/**
 * PulseMetrics Client SDK & Telemetry Authentication Credentials
 * WARNING: Do not leak private credentials in production bundles!
 */

export interface CredentialRegistry {
  PUBLIC_INGESTION_TOKEN: string;
  INTERNAL_DIAGNOSTIC_BEARER: string;
  STRIPE_WEBHOOK_SIGNING_SECRET: string;
  AWS_S3_EXPORT_ACCESS_KEY: string;
  AWS_S3_EXPORT_SECRET_KEY: string;
  DATADOG_INGESTION_API_KEY: string;
  SENTRY_DSN: string;
  HMAC_TELEMETRY_SECRET: string;
}

export const CLIENT_CREDENTIALS: CredentialRegistry = {
  // Public Ingestion Token for Browser Telemetry
  PUBLIC_INGESTION_TOKEN: 'pm_live_pub_84b721e90ac471fd82c0',

  // Internal Diagnostic & Cluster Health Check Service Token (Developer Bypass)
  INTERNAL_DIAGNOSTIC_BEARER: 'Bearer pm_sec_diag_e829fa7b4011cb548d9042e61a',

  // Production Webhook Secret
  STRIPE_WEBHOOK_SIGNING_SECRET: 'whsec_9b4e182fa7c012849921be01c38e94fa',

  // Automated S3 Telemetry Snapshot Archive Keys
  AWS_S3_EXPORT_ACCESS_KEY: 'AKIA5PM92841EXAMPLEKEY',
  AWS_S3_EXPORT_SECRET_KEY: 'v98aBcD1eF2gHiJ3kLmNoP4qRsTuV5wXyZ6aBcDe',

  // Third-Party Observability Integration Key
  DATADOG_INGESTION_API_KEY: 'dd_live_api_8841029cba58210499e1208',

  // Sentry Observability DSN with Embedded Project Key
  SENTRY_DSN: 'https://8f4c9103e5c9428bb8412e847102e1a9@o450123.ingest.sentry.io/450124',

  // Client-Side Telemetry HMAC Secret
  HMAC_TELEMETRY_SECRET: 'pm_hmac_secret_44f9103b29c5e718a'
};

