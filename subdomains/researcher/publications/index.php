<?php

$publications = [

    [
        "title" => "Urban Mobility Trends 2025",
        "type" => "Research Report",
        "date" => "December 2025"
    ],

    [
        "title" => "Climate Adaptation in Coastal Cities",
        "type" => "Working Paper",
        "date" => "October 2025"
    ],

    [
        "title" => "Digital Services and Public Trust",
        "type" => "Policy Brief",
        "date" => "August 2025"
    ],

    [
        "title" => "Infrastructure Investment Outlook",
        "type" => "Research Report",
        "date" => "June 2025"
    ]

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
    Publications — Northbridge Research Institute
</title>

<style>

body {
    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f7f9fb;

    color: #243447;
}

header {
    background: #17324d;

    color: white;

    padding:
        22px 30px;
}

.logo {
    max-width: 1100px;

    margin: auto;

    font-size: 18px;

    font-weight: bold;
}

.container {
    max-width: 1000px;

    margin: auto;

    padding:
        55px 25px;
}

h1 {
    font-size: 30px;

    margin-bottom: 8px;
}

.intro {
    color: #718394;

    font-size: 13px;

    line-height: 1.7;

    margin-bottom: 35px;
}

.publication {
    background: white;

    border:
        1px solid #e0e6eb;

    border-radius: 6px;

    padding: 22px;

    margin-bottom: 12px;

    display: flex;

    justify-content: space-between;

    align-items: center;
}

.publication h3 {
    margin: 0 0 7px;

    color: #17324d;

    font-size: 15px;
}

.publication span {
    color: #7b8b99;

    font-size: 11px;
}

.date {
    color: #8997a3;

    font-size: 11px;
}

.back {
    display: inline-block;

    margin-top: 20px;

    color: #27657e;

    font-size: 12px;

    text-decoration: none;
}

</style>

</head>


<body>


<header>

    <div class="logo">
        NORTHBRIDGE RESEARCH INSTITUTE
    </div>

</header>


<main class="container">


    <h1>
        Publications
    </h1>


    <p class="intro">

        Explore research reports, working papers
        and policy publications from the Northbridge
        Research Institute.

    </p>


    <?php foreach ($publications as $publication): ?>


        <article class="publication">


            <div>

                <h3>
                    <?= $publication["title"] ?>
                </h3>

                <span>
                    <?= $publication["type"] ?>
                </span>

            </div>


            <div class="date">

                <?= $publication["date"] ?>

            </div>


        </article>


    <?php endforeach; ?>


    <a
        href="/research/"
        class="back"
    >
        ← Back to Research
    </a>


</main>


</body>

</html>