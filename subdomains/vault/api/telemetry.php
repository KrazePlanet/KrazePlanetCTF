<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'service' => 'vaulttech-gateway',
    'status' => 'operational',
    'version' => '4.2.1',
    'region' => 'us-east-1',
    'latency_ms' => 14,
    'timestamp' => gmdate('c'),
], JSON_PRETTY_PRINT);

