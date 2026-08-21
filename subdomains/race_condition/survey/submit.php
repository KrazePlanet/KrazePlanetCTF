<?php
session_start();

$state_file = __DIR__ . '/slack_team.json';
if (!file_exists($state_file)) {
    $init = [
        'name' => 'Acme Corp',
        'domain' => 'acmecorp',
        'credits' => 25,
        'survey_completed' => 0,
        'history' => []
    ];
    file_put_contents($state_file, json_encode($init, JSON_PRETTY_PRINT));
}

// Reset Action
if (isset($_POST['action']) && $_POST['action'] === 'reset_credits') {
    $init = [
        'name' => 'Acme Corp',
        'domain' => 'acmecorp',
        'credits' => 25,
        'survey_completed' => 0,
        'history' => []
    ];
    file_put_contents($state_file, json_encode($init, JSON_PRETTY_PRINT));
    header("Location: billing.php");
    exit;
}

$is_json = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false) ||
           isset($_GET['api']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Read state without lock (VULNERABLE TO RACE CONDITION)
    $raw = @file_get_contents($state_file);
    $data = json_decode($raw, true) ?: ['credits' => 25, 'survey_completed' => 0, 'history' => []];

    if ($data['survey_completed'] == 0) {
        // Race condition window: 120ms delay
        usleep(120000);

        // 2. Perform write
        // Read latest data to atomically append the credit (simulating independent ledger workers)
        $fp = fopen($state_file, 'c+');
        if (flock($fp, LOCK_EX)) {
            $current_raw = stream_get_contents($fp);
            $current_data = json_decode($current_raw, true) ?: $data;
            
            $current_data['credits'] = ($current_data['credits'] ?? 25) + 100;
            $current_data['survey_completed'] = 1;
            $current_data['history'][] = [
                'date_str' => '2016-09-03',
                'item' => 'Survey completed',
                'amount' => 100,
                'created_at' => date('Y-m-d H:i:s')
            ];

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($current_data, JSON_PRETTY_PRINT));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            if ($is_json) {
                http_response_code(200);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 200,
                    'success' => true,
                    'message' => 'Survey completed successfully. $100 credited.',
                    'credits' => $current_data['credits']
                ], JSON_PRETTY_PRINT);
                exit;
            } else {
                header("Location: billing.php");
                exit;
            }
        }
    } else {
        if ($is_json) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 400,
                'success' => false,
                'error' => 'Survey has already been completed for this team.'
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            header("Location: billing.php");
            exit;
        }
    }
} else {
    header("Location: index.php");
    exit;
}
