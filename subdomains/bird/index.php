<?php

/*
 * RIVERVIEW INSTITUTE
 * Office of Admissions
 *
 * Normal public admissions portal.
 */

$page = $_GET['page'] ?? 'overview';

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
    Admissions | Riverview Institute
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

    background: #f5f7fa;

    color: #243b53;
}


/* HEADER */

header {
    background: #102a43;

    color: white;
}

.header-inner {
    max-width: 1150px;

    margin: auto;

    padding: 20px 25px;

    display: flex;

    align-items: center;

    justify-content: space-between;
}

.logo {
    font-size: 19px;

    font-weight: bold;
}

.logo span {
    display: block;

    margin-top: 4px;

    font-size: 10px;

    font-weight: normal;

    color: #9fb3c8;

    letter-spacing: .5px;
}

nav a {
    color: #d9e2ec;

    text-decoration: none;

    margin-left: 25px;

    font-size: 13px;
}

nav a:hover {
    color: white;
}


/* HERO */

.hero {
    background:
        linear-gradient(
            135deg,
            #102a43,
            #1769aa
        );

    color: white;

    padding: 65px 25px;
}

.hero-inner {
    max-width: 1150px;

    margin: auto;
}

.hero h1 {
    margin: 0;

    font-size: 38px;
}

.hero p {
    max-width: 650px;

    margin-top: 15px;

    color: #d9e2ec;

    line-height: 1.7;

    font-size: 15px;
}

.apply-button {
    display: inline-block;

    margin-top: 12px;

    padding: 12px 20px;

    background: #18b77e;

    color: white;

    border-radius: 5px;

    text-decoration: none;

    font-size: 13px;

    font-weight: bold;
}


/* CONTENT */

.container {
    max-width: 1150px;

    margin: 40px auto;

    padding: 0 25px;
}

.section-title {
    margin-bottom: 22px;

    color: #102a43;

    font-size: 22px;
}


/* CARDS */

.cards {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;
}

.card {
    background: white;

    padding: 25px;

    border:
        1px solid #dce3ea;

    border-radius: 8px;

    box-shadow:
        0 3px 12px
        rgba(16,42,67,.04);
}

.card h3 {
    margin-top: 0;

    color: #102a43;

    font-size: 17px;
}

.card p {
    color: #627d98;

    line-height: 1.6;

    font-size: 13px;
}


/* DEADLINE */

.deadline {
    margin-top: 35px;

    background: #e6f7f1;

    border-left:
        4px solid #18b77e;

    padding: 20px;

    border-radius: 5px;
}

.deadline strong {
    color: #087f5b;
}


/* FOOTER */

footer {
    margin-top: 70px;

    background: #102a43;

    color: #9fb3c8;

    text-align: center;

    padding: 28px;

    font-size: 11px;
}


/* MOBILE */

@media(max-width: 750px) {

    .header-inner {
        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    nav a {
        margin-left: 0;

        margin-right: 18px;
    }

    .cards {
        grid-template-columns: 1fr;
    }

    .hero h1 {
        font-size: 30px;
    }

}

</style>

</head>


<body>


<header>

    <div class="header-inner">

        <div class="logo">

            RIVERVIEW INSTITUTE

            <span>
                OFFICE OF ADMISSIONS
            </span>

        </div>


        <nav>

            <a href="/">
                Home
            </a>

            <a href="?page=programs">
                Programs
            </a>

            <a href="?page=requirements">
                Requirements
            </a>

            <a href="?page=contact">
                Contact
            </a>

        </nav>

    </div>

</header>


<section class="hero">

    <div class="hero-inner">

        <h1>
            Begin Your Journey
        </h1>

        <p>
            Discover undergraduate and postgraduate
            programs designed to prepare students for
            a changing world. Explore our admissions
            requirements and application process.
        </p>

        <a
            href="?page=apply"
            class="apply-button"
        >
            Start Your Application
        </a>

    </div>

</section>


<main class="container">


    <h2 class="section-title">
        Admissions Information
    </h2>


    <div class="cards">


        <div class="card">

            <h3>
                Undergraduate Programs
            </h3>

            <p>
                Explore bachelor's degree programs
                across engineering, science, business,
                humanities and design.
            </p>

        </div>


        <div class="card">

            <h3>
                Application Requirements
            </h3>

            <p>
                Review academic requirements,
                supporting documents and important
                application deadlines.
            </p>

        </div>


        <div class="card">

            <h3>
                International Students
            </h3>

            <p>
                Information about international
                admissions, visas, housing and
                orientation programs.
            </p>

        </div>


    </div>


    <div class="deadline">

        <strong>
            Application deadline:
        </strong>

        &nbsp;

        November 30, 2026

        <br>

        <span>
            Applications submitted after the deadline
            may be considered for the following intake.
        </span>

    </div>


</main>


<footer>

    Riverview Institute of Technology

    ·

    Office of Admissions

    ·

    © 2026

</footer>


</body>

</html>