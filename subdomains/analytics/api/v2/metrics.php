<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$response = [
    'status' => 'success',
    'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
    'environment' => 'production',
    'cluster_region' => 'us-east-1',
    'metrics' => [
        'events_ingested_24h' => 42819200,
        'events_per_second_peak' => 18450,
        'daily_active_users' => 184920,
        'monthly_active_users' => 1420500,
        'ingestion_p95_latency_ms' => 14.2,
        'ingestion_p99_latency_ms' => 22.8,
        'error_rate_percentage' => 0.0012,
        'api_gateway_requests_per_hr' => 1294000,
        'active_workspaces' => 3840
    ],
    'trend_series' => [
        ['hour' => '00:00', 'events' => 1240000, 'latency' => 13.8],
        ['hour' => '02:00', 'events' => 980000, 'latency' => 12.5],
        ['hour' => '04:00', 'events' => 840000, 'latency' => 12.1],
        ['hour' => '06:00', 'events' => 1120000, 'latency' => 13.0],
        ['hour' => '08:00', 'events' => 1850000, 'latency' => 15.2],
        ['hour' => '10:00', 'events' => 2410000, 'latency' => 16.8],
        ['hour' => '12:00', 'events' => 2950000, 'latency' => 17.4],
        ['hour' => '14:00', 'events' => 3120000, 'latency' => 16.9],
        ['hour' => '16:00', 'events' => 2840000, 'latency' => 15.8],
        ['hour' => '18:00', 'events' => 2340000, 'latency' => 14.5],
        ['hour' => '20:00', 'events' => 1980000, 'latency' => 14.1],
        ['hour' => '22:00', 'events' => 1560000, 'latency' => 13.9]
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);

