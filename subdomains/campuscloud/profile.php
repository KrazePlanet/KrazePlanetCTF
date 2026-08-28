<?php

$page = "Profile";

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
    CampusCloud — Profile
</title>

<style>

body {
    margin: 0;

    background: #f5f7fb;

    font-family: Arial, sans-serif;

    color: #172033;
}

header {
    background: #111827;

    color: white;

    padding: 20px 35px;

    font-size: 20px;

    font-weight: bold;
}

.container {
    max-width: 850px;

    margin: 45px auto;

    padding: 0 20px;
}

.card {
    background: white;

    border:
        1px solid #e4e8ef;

    border-radius: 8px;

    padding: 30px;
}

h1 {
    margin-top: 0;

    font-size: 24px;
}

.row {
    padding:
        18px 0;

    border-bottom:
        1px solid #edf0f4;
}

.label {
    color: #8993a3;

    font-size: 11px;

    margin-bottom: 6px;
}

.value {
    font-size: 14px;
}

.back {
    display: inline-block;

    margin-top: 25px;

    color: #5b67d8;

    text-decoration: none;

    font-size: 12px;
}

</style>

</head>

<body>


<header>
    CampusCloud
</header>


<div class="container">

    <div class="card">

        <h1>
            Your Profile
        </h1>


        <div class="row">

            <div class="label">
                Name
            </div>

            <div class="value">
                Alex Morgan
            </div>

        </div>


        <div class="row">

            <div class="label">
                Role
            </div>

            <div class="value">
                Workspace Administrator
            </div>

        </div>


        <div class="row">

            <div class="label">
                Organization
            </div>

            <div class="value">
                Campus Operations
            </div>

        </div>


        <a
            href="dashboard.php"
            class="back"
        >
            ← Back to dashboard
        </a>

    </div>

</div>


</body>

</html>