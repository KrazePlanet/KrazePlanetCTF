<?php

$projects = [
    [
        "title" => "Urban Mobility Systems",
        "description" =>
            "Studying transportation patterns and sustainable mobility across rapidly growing cities."
    ],
    [
        "title" => "Climate Resilience",
        "description" =>
            "Developing practical models for communities adapting to changing environmental conditions."
    ],
    [
        "title" => "Digital Public Services",
        "description" =>
            "Exploring how emerging technologies can improve access to essential public services."
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
    Northbridge Research Institute
</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    color: #243447;

    background: #f7f9fb;
}

header {
    background: #17324d;

    color: white;
}

.header-inner {
    max-width: 1180px;

    margin: auto;

    padding: 20px 25px;

    display: flex;

    justify-content: space-between;

    align-items: center;
}

.logo {
    font-size: 19px;

    font-weight: bold;

    letter-spacing: .3px;
}

.logo small {
    display: block;

    margin-top: 5px;

    color: #a9bac9;

    font-size: 9px;

    letter-spacing: 1.5px;

    font-weight: normal;
}

nav {
    display: flex;

    gap: 25px;
}

nav a {
    color: #d7e1e9;

    text-decoration: none;

    font-size: 12px;
}

nav a:hover {
    color: white;
}

.hero {
    background:
        linear-gradient(
            120deg,
            #17324d,
            #2d637e
        );

    color: white;

    padding:
        70px 25px;
}

.hero-inner {
    max-width: 1180px;

    margin: auto;
}

.hero h1 {
    max-width: 680px;

    margin: 0;

    font-size: 42px;

    line-height: 1.1;
}

.hero p {
    max-width: 650px;

    color: #d8e5ec;

    font-size: 14px;

    line-height: 1.8;

    margin-top: 18px;
}

.container {
    max-width: 1180px;

    margin: auto;

    padding:
        55px 25px;
}

.section-title {
    font-size: 23px;

    margin-bottom: 25px;
}

.projects {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;
}

.project {
    background: white;

    border:
        1px solid #e0e6eb;

    border-radius: 7px;

    padding: 25px;
}

.project h3 {
    margin-top: 0;

    color: #17324d;

    font-size: 16px;
}

.project p {
    color: #697b8b;

    font-size: 12px;

    line-height: 1.7;
}

.publications {
    margin-top: 45px;

    background: white;

    border:
        1px solid #e0e6eb;

    border-radius: 7px;

    padding: 28px;
}

.publications h2 {
    margin-top: 0;

    font-size: 19px;
}

.publications p {
    color: #697b8b;

    font-size: 13px;

    line-height: 1.7;
}

.button {
    display: inline-block;

    margin-top: 10px;

    padding:
        11px 17px;

    background: #17324d;

    color: white;

    text-decoration: none;

    border-radius: 4px;

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: .7px;
}

footer {
    margin-top: 40px;

    background: #17324d;

    color: #a9bac9;

    text-align: center;

    padding: 30px;

    font-size: 10px;
}

@media(max-width: 750px) {

    .header-inner {
        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .projects {
        grid-template-columns: 1fr;
    }

    .hero h1 {
        font-size: 32px;
    }

}

</style>

</head>


<body>


<header>

    <div class="header-inner">


        <div class="logo">

            NORTHBRIDGE RESEARCH INSTITUTE

            <small>
                SCIENCE · POLICY · INNOVATION
            </small>

        </div>


        <nav>

            <a href="/research/">
                Research
            </a>

            <a href="/research/publications/">
                Publications
            </a>

            <a href="#">
                Researchers
            </a>

            <a href="#">
                About
            </a>

        </nav>


    </div>

</header>


<section class="hero">

    <div class="hero-inner">

        <h1>
            Research for a changing world.
        </h1>

        <p>
            Northbridge Research Institute brings
            together scientists, engineers and policy
            researchers to study complex challenges
            facing communities and institutions.
        </p>

    </div>

</section>


<main class="container">


    <h2 class="section-title">
        Current Research
    </h2>


    <div class="projects">


        <?php foreach ($projects as $project): ?>

            <article class="project">

                <h3>
                    <?= $project["title"] ?>
                </h3>

                <p>
                    <?= $project["description"] ?>
                </p>

            </article>

        <?php endforeach; ?>


    </div>


    <section class="publications">

        <h2>
            Research Publications
        </h2>

        <p>
            Browse reports, working papers and
            peer-reviewed research produced by
            Northbridge researchers and partner
            institutions.
        </p>

        <a
            href="/research/publications/"
            class="button"
        >
            View Publications
        </a>

    </section>


</main>


<footer>

    Northbridge Research Institute

    ·

    Research & Innovation Division

    ·

    © 2026

</footer>


</body>

</html>