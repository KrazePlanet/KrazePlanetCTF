<?php

$app = "CampusCloud";

?>

<!DOCTYPE html>

<html>

<head>

<title>
    Debug Information
</title>

<style>

body {
    margin: 0;

    background: #101318;

    color: #d7dae0;

    font-family:
        monospace;

    padding: 40px;
}

.container {
    max-width: 900px;

    margin: auto;
}

h1 {
    color: #ffb86b;
}

.warning {
    border:
        1px solid #704d25;

    background: #211a10;

    padding: 18px;

    color: #ffcf8a;

    margin-bottom: 25px;
}

a {
    color: #8be9fd;

    display: block;

    margin: 12px 0;
}

</style>

</head>

<body>

<div class="container">


<h1>
    CampusCloud Debug Console
</h1>


<div class="warning">

    WARNING: Debug interface is enabled
    in the current deployment.

</div>


<a href="info.php">
    /debug/info
</a>


<a href="environment.php">
    /debug/environment
</a>


</div>

</body>

</html>