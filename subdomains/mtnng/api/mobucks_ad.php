<?php
header('Content-Type: application/json; charset=utf-8');
$plid = isset($_GET['plid']) ? (int) $_GET['plid'] : 73;

echo json_encode([
    'plid' => $plid,
    'carrier' => 'MTN-NG',
    'banner_title' => 'MTN XtraByte & Pulse Nightlife Bundles — Tap to Explore',
    'banner_html' => true,
    'status' => 'active',
]);

