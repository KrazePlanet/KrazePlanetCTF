<?php
session_start();
$user_id = $_SESSION['h1_user_id'] ?? null;
session_write_close(); // Release PHP session lock for true concurrency

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($user_id)) {
    http_response_code(401);
    echo json_encode([
        'errors' => [['message' => 'Authentication required. Invalid or expired token.']]
    ], JSON_PRETTY_PRINT);
    exit;
}

$user_id = (int)$user_id;

$raw_input = file_get_contents('php://input');
$body = json_decode($raw_input, true) ?: [];

$query     = $body['query'] ?? '';
$variables = $body['variables'] ?? [];
$team_id   = $variables['input_0']['team_id'] ?? 'dGVhbV8xMjk0';
$client_id = $variables['input_0']['clientMutationId'] ?? '1';

// Check if this is the claim credential mutation
if (strpos($query, 'claimCredential') !== false || strpos($query, 'Claim_credential_mutation') !== false || isset($_POST['claim_credential'])) {
    
    // 1. TOCTOU Race Condition Check: Check if user already claimed credentials for this team
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM user_claims WHERE user_id = ? AND team_id = ?");
    $stmt_check->execute([$user_id, $team_id]);
    $already_claimed_count = (int)$stmt_check->fetchColumn();

    if ($already_claimed_count === 0) {
        // Race window delay: 120ms (simulating async provisioning of test sandbox environment)
        usleep(120000);

        // 2. Perform write with cross-process mutex lock to guarantee concurrency without database crash
        $lock_file = sys_get_temp_dir() . '/h1_cred_mutex.lock';
        $lock_fp = fopen($lock_file, 'w+');
        flock($lock_fp, LOCK_EX);

        $db_conn = new PDO('sqlite:' . __DIR__ . '/hackerone.db');
        $db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Find an unassigned credential from the pool
        $stmt_pool = $db_conn->prepare("SELECT * FROM credential_pool WHERE team_id = ? AND claimed_by_user_id IS NULL ORDER BY id ASC LIMIT 1");
        $stmt_pool->execute([$team_id]);
        $cred = $stmt_pool->fetch(PDO::FETCH_ASSOC);

        if ($cred) {
            // Assign to user
            $stmt_upd = $db_conn->prepare("UPDATE credential_pool SET claimed_by_user_id = ?, claimed_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt_upd->execute([$user_id, $cred['id']]);

            // Record in user_claims
            $stmt_claim = $db_conn->prepare("INSERT INTO user_claims (user_id, team_id, cred_gid) VALUES (?, ?, ?)");
            $stmt_claim->execute([$user_id, $team_id, $cred['cred_gid']]);

            flock($lock_fp, LOCK_UN);
            fclose($lock_fp);

            // 1:1 GraphQL Response format matching HackerOne #488985
            echo json_encode([
                "data" => [
                    "claimCredential" => [
                        "clientMutationId" => (string)$client_id,
                        "team" => [
                            "id" => $team_id,
                            "claimed_credential" => [
                                "id" => $cred['cred_gid'],
                                "credentials" => [
                                    "email" => $cred['email'],
                                    "password" => $cred['password'],
                                    "private_id" => $cred['private_id']
                                ],
                                "account_details" => null
                            ]
                        ],
                        "was_successful" => true,
                        "_errors4fkckF" => [
                            "edges" => [],
                            "pageInfo" => [
                                "hasNextPage" => false,
                                "hasPreviousPage" => false
                            ]
                        ]
                    ]
                ]
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            flock($lock_fp, LOCK_UN);
            fclose($lock_fp);

            echo json_encode([
                "data" => [
                    "claimCredential" => [
                        "clientMutationId" => (string)$client_id,
                        "team" => ["id" => $team_id],
                        "was_successful" => false,
                        "_errors4fkckF" => [
                            "edges" => [
                                ["node" => ["type" => "VALIDATION_ERROR", "field" => "base", "message" => "No available credentials left in the program pool."]]
                            ]
                        ]
                    ]
                ]
            ], JSON_PRETTY_PRINT);
            exit;
        }
    } else {
        // Fetch existing claimed credential to show already claimed response
        $stmt_existing = $pdo->prepare("SELECT c.* FROM credential_pool c JOIN user_claims u ON c.cred_gid = u.cred_gid WHERE u.user_id = ? AND u.team_id = ? LIMIT 1");
        $stmt_existing->execute([$user_id, $team_id]);
        $existing_cred = $stmt_existing->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "data" => [
                "claimCredential" => [
                    "clientMutationId" => (string)$client_id,
                    "team" => [
                        "id" => $team_id,
                        "claimed_credential" => [
                            "id" => $existing_cred['cred_gid'] ?? "gid://hackerone/Credential/0",
                            "credentials" => [
                                "email" => $existing_cred['email'] ?? "",
                                "password" => $existing_cred['password'] ?? "",
                                "private_id" => $existing_cred['private_id'] ?? ""
                            ],
                            "account_details" => null
                        ]
                    ],
                    "was_successful" => false,
                    "_errors4fkckF" => [
                        "edges" => [
                            ["node" => ["type" => "ALREADY_CLAIMED", "field" => "base", "message" => "You have already claimed credentials for this program."]]
                        ],
                        "pageInfo" => [
                            "hasNextPage" => false,
                            "hasPreviousPage" => false
                        ]
                    ]
                ]
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    }
} else {
    echo json_encode(['data' => ['viewer' => ['id' => 'dXNlcl8' . $user_id]]]);
    exit;
}
