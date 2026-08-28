<?php

$projects = [

    "Student Portal",
    "Campus Mobile App",
    "Analytics Migration",
    "Digital Library",
    "Admissions Platform"

];

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    CampusCloud — Projects
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
    max-width: 1000px;

    margin: 40px auto;

    padding: 0 20px;
}

h1 {
    font-size: 25px;
}

.project {
    background: white;

    border:
        1px solid #e4e8ef;

    padding: 20px;

    margin-top: 12px;

    border-radius: 7px;
}

.project strong {
    font-size: 14px;
}

.project span {
    display: block;

    color: #8993a3;

    font-size: 11px;

    margin-top: 6px;
}

</style>

</head>

<body>


<header>
    CampusCloud
</header>


<div class="container">

    <h1>
        Projects
    </h1>


    <?php foreach ($projects as $project): ?>

        <div class="project">

            <strong>
                <?= $project ?>
            </strong>

            <span>
                Active workspace project
            </span>

        </div>

    <?php endforeach; ?>


</div>


</body>

</html>