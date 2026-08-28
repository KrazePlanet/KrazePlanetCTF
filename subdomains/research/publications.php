<?php

/*
 * Training lab:
 * Deliberately vulnerable open redirect.
 *
 * Example:
 * publications.php?next=https://example.com
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

    <title>Publications — Riverview Research</title>

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

        .heading h1 {
            margin: 0;

            font-size: 30px;

            color: #102a43;
        }

        .heading p {
            color: #718096;

            font-size: 14px;

            line-height: 1.6;
        }

        .publication {
            background: white;

            border:
                1px solid #e1e7ee;

            border-radius: 7px;

            padding: 22px;

            margin-top: 16px;
        }

        .publication h2 {
            margin: 0;

            font-size: 17px;
        }

        .authors {
            margin-top: 8px;

            color: #1769aa;

            font-size: 12px;
        }

        .description {
            margin-top: 12px;

            color: #718096;

            font-size: 13px;

            line-height: 1.6;
        }

        .meta {
            margin-top: 15px;

            color: #9aa5b1;

            font-size: 11px;
        }

        .button {
            display: inline-block;

            margin-top: 14px;

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


    <div class="heading">

        <h1>
            Research Publications
        </h1>

        <p>
            Explore recent publications from researchers
            across Riverview Institute of Technology.
        </p>

    </div>


    <article class="publication">

        <h2>
            Applied Machine Learning for Urban Mobility
        </h2>

        <div class="authors">
            A. Mehta · S. Rao · D. Wilson
        </div>

        <div class="description">
            A study examining machine-learning approaches
            for predicting transportation demand in
            rapidly growing metropolitan areas.
        </div>

        <div class="meta">
            Journal of Applied Computing · 2026
        </div>

        <a
            class="button"
            href="?next=/"
        >
            Return to Research Portal →
        </a>

    </article>


    <article class="publication">

        <h2>
            Sustainable Computing in Higher Education
        </h2>

        <div class="authors">
            P. Sharma · L. Chen · M. Khan
        </div>

        <div class="description">
            An analysis of energy-efficient computing
            infrastructure and sustainable technology
            practices across university campuses.
        </div>

        <div class="meta">
            International Computing Review · 2026
        </div>

        <a
            class="button"
            href="?next=/"
        >
            Return to Research Portal →
        </a>

    </article>


    <article class="publication">

        <h2>
            Distributed Systems for Academic Networks
        </h2>

        <div class="authors">
            R. Patel · N. Williams · K. Ito
        </div>

        <div class="description">
            Research into resilient distributed systems
            designed for large-scale academic networks
            and research institutions.
        </div>

        <div class="meta">
            Systems Engineering Quarterly · 2026
        </div>

        <a
            class="button"
            href="?next=/"
        >
            Return to Research Portal →
        </a>

    </article>


</main>


<footer>

    Riverview Institute of Technology ·
    Research & Innovation

</footer>


</body>

</html>
