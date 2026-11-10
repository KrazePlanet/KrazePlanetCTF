<?php
header('Content-Type: application/json; charset=UTF-8');

$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

// Check if developer diagnostic bearer token from source map was provided
$expected_bearer = 'Bearer pm_sec_diag_e829fa7b4011cb548d9042e61a';

if ($auth_header !== $expected_bearer) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'code' => 401,
        'error' => 'Unauthorized',
        'message' => 'Restricted Internal Diagnostic Actuator. Valid internal diagnostic bearer token required in Authorization header.',
        'target_service' => 'PulseMetrics Telemetry Actuator Service (corp-admin.pulsemetrics-internal.net)'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Authorized with leaked internal diagnostic token!
echo json_encode([
    'status' => 'healthy',
    'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
    'cluster' => 'pulsemetrics-prod-us-east-1',
    'environment' => 'production',
    'node_telemetry' => [
        'kafka_cluster' => [
            'status' => 'ONLINE',
            'brokers' => [
                'kafka01.infra.pulsemetrics-internal.net:9092 (Leader)',
                'kafka02.infra.pulsemetrics-internal.net:9092 (Follower)',
                'kafka03.infra.pulsemetrics-internal.net:9092 (Follower)'
            ],
            'under_replicated_partitions' => 0,
            'ingestion_topic' => 'pulse-events-raw-v3'
        ],
        'clickhouse_olap' => [
            'status' => 'ONLINE',
            'host' => 'clickhouse-cluster.infra.pulsemetrics-internal.net:8123',
            'total_rows_ingested' => '1,492,829,104',
            'compression_ratio' => '4.8x'
        ],
        'redis_session_cache' => [
            'status' => 'ONLINE',
            'host' => 'redis-cache-cluster.internal:6379',
            'memory_used_mb' => 2480,
            'hit_rate' => '99.4%'
        ],
        'internal_services' => [
            'ingest_gateway' => 'https://ingest-internal.pulsemetrics-cloud.net:9443/v3/track',
            'corp_admin_rpc' => 'https://corp-admin.pulsemetrics-internal.net/rpc/v1',
            'staging_collector' => 'https://staging-collector.internal.pulsemetrics.io'
        ]
    ]
], JSON_PRETTY_PRINT);

