<?php
header('Content-Type: application/json; charset=utf-8');
$plid = isset($_GET['plid']) ? (int) $_GET['plid'] : 73;
echo json_encode(['recorded' => true, 'plid' => $plid]);

