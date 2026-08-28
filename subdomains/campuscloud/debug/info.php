<?php

$debug = [

    "Application" =>
        "CampusCloud",

    "Environment" =>
        "production",

    "Framework" =>
        "CampusCloud Framework 4.8",

    "PHP Version" =>
        "8.2.19",

    "Database" =>
        "MySQL 8.0",

    "Database Driver" =>
        "PDO MySQL",

    "Internal Service" =>
        "http://services.internal.campuscloud.test",

    "Cache Service" =>
        "redis://cache.internal.campuscloud.test",

    "Build" =>
        "2026.08.21-1842",

    "Region" =>
        "us-east-1",

    "Debug Mode" =>
        "ENABLED"

];

?>

<!DOCTYPE html>

<html>

<head>

<title>
    CampusCloud Debug Information
</title>

<style>

body {
    margin: 0;

    background: #101318;

    color: #d7dae0;

    font-family: monospace;

    padding: 40px;
}

.container {
    max-width: 900px;

    margin: auto;
}

h1 {
    color: #ffb86b;
}

table {
    width: 100%;

    border-collapse: collapse;

    background: #171a21;
}

td {
    padding: 15px;

    border-bottom:
        1px solid #292d36;
}

td:first-child {
    color: #8be9fd;

    width: 220px;
}

td:last-child {
    color: #f8f8f2;
}

.warning {
    color: #ff6b6b;

    margin-bottom: 25px;
}

</style>

</head>

<body>


<div class="container">


<h1>
    Application Information
</h1>


<div class="warning">

    Production debug endpoint detected.

</div>


<table>


<?php foreach ($debug as $key => $value): ?>

<tr>

<td>
    <?= $key ?>
</td>

<td>
    <?= $value ?>
</td>

</tr>

<?php endforeach; ?>


</table>


</div>


</body>

</html>