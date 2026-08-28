<?php

header("Content-Type: application/json");

$id = isset($_GET["id"])
    ? $_GET["id"]
    : "1024";


$profiles = [

    "1024" => [

        "id" =>
            1024,

        "name" =>
            "Maya Chen",

        "department" =>
            "Physics",

        "university" =>
            "Northbridge University",

        "email" =>
            "maya.chen@example.test",

        "account_status" =>
            "active",

        "internal_user_id" =>
            "USR-48291",

        "created_at" =>
            "2026-02-14"

    ]

];


if (isset($profiles[$id])) {

    echo json_encode(
        $profiles[$id],
        JSON_PRETTY_PRINT
    );

    exit;

}


http_response_code(404);

echo json_encode([

    "error" =>
        "Profile not found"

]);

?>