<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$events = [
    [
        'event_id' => 'evt_' . bin2hex(random_bytes(6)),
        'name' => 'checkout.payment_intent_succeeded',
        'tenant_id' => 'org_acme_corp',
        'user_id' => 'usr_94821',
        'ip_address' => '198.51.100.44',
        'geo' => 'US-VA',
        'latency_ms' => 12.4,
        'status' => '200 OK',
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z', time() - 4)
    ],
    [
        'event_id' => 'evt_' . bin2hex(random_bytes(6)),
        'name' => 'workspace.retention_job_dispatched',
        'tenant_id' => 'org_nordic_media',
        'user_id' => 'sys_daemon_worker',
        'ip_address' => '10.244.3.118',
        'geo' => 'EU-DE',
        'latency_ms' => 18.2,
        'status' => '200 OK',
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z', time() - 12)
    ],
    [
        'event_id' => 'evt_' . bin2hex(random_bytes(6)),
        'name' => 'auth.sso_saml_asserted',
        'tenant_id' => 'org_apex_fintech',
        'user_id' => 'usr_88204',
        'ip_address' => '203.0.113.89',
        'geo' => 'US-CA',
        'latency_ms' => 9.7,
        'status' => '200 OK',
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z', time() - 25)
    ],
    [
        'event_id' => 'evt_' . bin2hex(random_bytes(6)),
        'name' => 'pipeline.kafka_batch_flushed',
        'tenant_id' => 'system_telemetry_bus',
        'user_id' => 'sys_kafka_consumer_04',
        'ip_address' => '10.244.7.42',
        'geo' => 'US-OH',
        'latency_ms' => 15.6,
        'status' => '200 OK',
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z', time() - 39)
    ],
    [
        'event_id' => 'evt_' . bin2hex(random_bytes(6)),
        'name' => 'tenant.api_token_generated',
        'tenant_id' => 'org_vanguard_security',
        'user_id' => 'usr_12048',
        'ip_address' => '192.0.2.71',
        'geo' => 'EU-IE',
        'latency_ms' => 14.1,
        'status' => '201 Created',
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z', time() - 58)
    ]
];

echo json_encode([
    'status' => 'success',
    'total_in_batch' => count($events),
    'stream_status' => 'streaming',
    'events' => $events
], JSON_PRETTY_PRINT);

