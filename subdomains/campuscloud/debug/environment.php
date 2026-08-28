<?php

$environment = [

    "APPLICATION_ENV" =>
        "production",

    "APPLICATION_VERSION" =>
        "4.8.3",

    "APPLICATION_BUILD" =>
        "2026.08.21-1842",

    "SERVER_REGION" =>
        "us-east-1",

    "SERVER_ROLE" =>
        "web",

    "API_VERSION" =>
        "v2",

    "API_INTERNAL_HOST" =>
        "api.internal.campuscloud.test",

    "QUEUE_SERVICE" =>
        "queue.internal.campuscloud.test",

    "CACHE_SERVICE" =>
        "cache.internal.campuscloud.test",

    "LOG_LEVEL" =>
        "debug",

    "DEBUG_MODE" =>
        "true"

];

?>

<!DOCTYPE html>

<html>

<head>

<title>
    CampusCloud Environment
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

pre {
    background: #171a21;

    border:
        1px solid #292d36;

    padding: 25px;

    color: #8be9fd;

    line-height: 1.8;

    overflow-x: auto;
}

.warning {
    color: #ff6b6b;

    margin-bottom: 20px;
}

</style>

</head>

<body>


<div class="container">


<h1>
    Environment Configuration
</h1>


<div class="warning">

    This information should not be publicly accessible.

</div>


<pre><?php

foreach ($environment as $key => $value) {

    echo $key
        . "="
        . $value
        . "\n";

}

?></pre>


</div>


</body>

</html>