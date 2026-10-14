<?php
// portal.php - Lab progress API (solved / bookmarked), stored on the central
// login server so it follows the student across every KrazePlanet site.
// Responses keep the shape the lab pages' JavaScript expects.
require_once __DIR__ . '/../config/sso.php';
startKrazeSession();
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$guest = ['loggedIn' => false, 'username' => null, 'solvedLabs' => [], 'bookmarkedLabs' => []];

// Easy = 20 pts, Medium = 50 pts, Hard = 100 pts (the central server maps difficulty to points).
function labDifficulty($labId, $rawDiff) {
    $rawDiff = strtolower(trim($rawDiff));
    foreach (['hard', 'medium', 'easy'] as $d) {
        if (strpos($rawDiff, $d) !== false) return $d;
    }
    $catFile = __DIR__ . '/lab_catalog_points.json';
    $catalog = file_exists($catFile) ? json_decode(file_get_contents($catFile), true) : [];
    if (!empty($catalog[$labId]['difficulty'])) return strtolower($catalog[$labId]['difficulty']);
    if (preg_match('/(hard|rce|ssti|xxe|blind|deserialization)/i', $labId)) return 'hard';
    if (preg_match('/(medium|sqli|ssrf|auth|jwt|lfi|csrf)/i', $labId)) return 'medium';
    return 'easy';
}

switch ($action) {
    case 'get_state':
        if (!krazeLoggedIn()) {
            echo json_encode(['success' => true, 'data' => $guest]);
            exit;
        }
        $res = krazeSsoCall('state');
        echo json_encode(['success' => true, 'data' => !empty($res['success']) ? $res['data'] : $guest]);
        exit;

    case 'toggle_solved':
    case 'toggle_bookmark':
        if (!krazeLoggedIn()) {
            echo json_encode(['success' => false, 'requireLogin' => true, 'message' => 'Please login to track lab progress.']);
            exit;
        }
        $labId = trim($_POST['lab_id'] ?? '');
        if ($labId === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid lab ID.']);
            exit;
        }
        $payload = ['lab_id' => $labId];
        if ($action === 'toggle_solved') {
            $payload['difficulty'] = labDifficulty($labId, $_POST['difficulty'] ?? '');
        }
        $res = krazeSsoCall($action, $payload);
        if (empty($res['success'])) {
            echo json_encode(['success' => false, 'requireLogin' => !krazeLoggedIn(), 'message' => $res['error'] ?? 'Could not save progress.']);
            exit;
        }
        echo json_encode($res);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action requested.']);
        exit;
}
