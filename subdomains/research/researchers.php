<?php

/*
 * Training lab:
 * Deliberately vulnerable open redirect.
 *
 * Example:
 * researchers.php?next=https://example.com
 */

if (isset($_GET['next']) && $_GET['next'] !== '') {
    header('Location: ' . $_GET['next'], true, 302);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Researchers — Riverview Research</title>

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

        header {
            background: #102a43;

            color: white;

            padding: 18px 30px;
        }

        .header-inner {
            max-width: 1100px;

            margin: auto;

            display: flex;

            align-items: center;

            justify-content: space-between;
        }

        .logo {
            font-weight: bold;
            font-size: 18px;
        }

        nav a {
            color: #d9e2ec;

            text-decoration: none;

            margin-left: 24px;

            font-size: 13px;
        }

        .container {
            max-width: 1100px;

            margin: 45px auto;

            padding: 0 20px;
        }

        h1 {
            margin: 0;

            color: #102a43;

            font-size: 30px;
        }

        .intro {
            color: #718096;

            font-size: 14px;

            line-height: 1.6;

            margin-bottom: 25px;
        }

        .researchers {
            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(280px, 1fr)
                );

            gap: 18px;
        }

        .researcher {
            background: white;

            border:
                1px solid #e1e7ee;

            border-radius: 7px;

            padding: 23px;
        }

        .avatar {
            width: 48px;
            height: 48px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #e9f3fb;

            color: #1769aa;

            font-weight: bold;

            margin-bottom: 15px;
        }

        .researcher h2 {
            margin: 0;

            font-size: 17px;
        }

        .role {
            margin-top: 6px;

            color: #1769aa;

            font-size: 12px;
        }

        .bio {
            margin-top: 13px;

            color: #718096;

            font-size: 13px;

            line-height: 1.55;
        }

        .link {
            display: inline-block;

            margin-top: 15px;

            color: #1769aa;

            text-decoration: none;

            font-size: 12px;

            font-weight: bold;
        }

        footer {
            margin-top: 70px;

            padding: 25px;

            background: #102a43;

            color: #9fb3c8;

            text-align: center;

            font-size: 11px;
        }

    </style>

</head>


<body>


<header>

    <div class="header-inner">

        <div class="logo">
            RIVERVIEW RESEARCH
        </div>

        <nav>

            <a href="/">
                Home
            </a>

            <a href="/publications.php">
                Publications
            </a>

            <a href="/researchers.php">
                Researchers
            </a>

        </nav>

    </div>

</header>


<main class="container">


    <h1>
        Our Researchers
    </h1>


    <p class="intro">
        Meet faculty members and researchers working
        across computing, engineering, sustainability
        and emerging technologies.
    </p>


    <div class="researchers">


        <article class="researcher">

            <div class="avatar">
                AM
            </div>

            <h2>
                Dr. Aarav Mehta
            </h2>

            <div class="role">
                Associate Professor · Computer Science
            </div>

            <div class="bio">
                Works on machine learning, intelligent
                transportation systems and applied
                data science.
            </div>

            <a
                class="link"
                href="?next=/"
            >
                Research profile →
            </a>

        </article>


        <article class="researcher">

            <div class="avatar">
                PS
            </div>

            <h2>
                Dr. Priya Sharma
            </h2>

            <div class="role">
                Professor · Electronics Engineering
            </div>

            <div class="bio">
                Research interests include embedded
                systems, sensor networks and
                energy-efficient hardware.
            </div>

            <a
                class="link"
                href="?next=/"
            >
                Research profile →
            </a>

        </article>


        <article class="researcher">

            <div class="avatar">
                KI
            </div>

            <h2>
                Dr. Kenji Ito
            </h2>

            <div class="role">
                Research Fellow · Distributed Systems
            </div>

            <div class="bio">
                Studies resilient distributed computing,
                academic networks and large-scale
                infrastructure.
            </div>

            <a
                class="link"
                href="?next=/"
            >
                Research profile →
            </a>

        </article>


        <article class="researcher">

            <div class="avatar">
                LK
            </div>

            <h2>
                Dr. Leila Khan
            </h2>

            <div class="role">
                Assistant Professor · Sustainability
            </div>

            <div class="bio">
                Focuses on sustainable computing,
                energy optimization and technology
                policy.
            </div>

            <a
                class="link"
                href="?next=/"
            >
                Research profile →
            </a>

        </article>


    </div>


</main>


<footer>

    Riverview Institute of Technology ·
    Research & Innovation

</footer>


</body>

</html>
