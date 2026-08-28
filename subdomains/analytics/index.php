<?php

/*
 * INSIGHTLYTICS
 * Business Analytics Platform
 */

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
    Insightly — Business Analytics
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

    background: #f6f8fb;

    color: #172033;
}

header {
    background: white;

    border-bottom:
        1px solid #e5e9f0;
}

.header-inner {
    max-width: 1180px;

    margin: auto;

    padding:
        18px 25px;

    display: flex;

    align-items: center;

    justify-content: space-between;
}

.logo {
    color: #172033;

    font-size: 20px;

    font-weight: 800;

    letter-spacing: -.5px;
}

.logo span {
    color: #5b67d8;
}

nav {
    display: flex;

    gap: 28px;
}

nav a {
    color: #657083;

    text-decoration: none;

    font-size: 13px;
}

nav a:hover {
    color: #5b67d8;
}

.account {
    display: flex;

    align-items: center;

    gap: 10px;

    font-size: 12px;

    color: #657083;
}

.avatar {
    width: 32px;

    height: 32px;

    border-radius: 50%;

    background: #5b67d8;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: bold;
}


/* HERO */

.hero {
    background: white;

    padding:
        60px 25px 50px;
}

.hero-inner {
    max-width: 1180px;

    margin: auto;
}

.badge {
    display: inline-block;

    background: #eef0ff;

    color: #4d59c7;

    padding:
        6px 10px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: bold;

    text-transform: uppercase;

    letter-spacing: .7px;
}

.hero h1 {
    max-width: 650px;

    margin:
        15px 0 10px;

    font-size: 40px;

    line-height: 1.1;

    letter-spacing: -1px;
}

.hero p {
    max-width: 600px;

    color: #697586;

    font-size: 14px;

    line-height: 1.7;
}


/* DASHBOARD */

.container {
    max-width: 1180px;

    margin: auto;

    padding:
        35px 25px 70px;
}

.dashboard-header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 20px;
}

.dashboard-header h2 {
    margin: 0;

    font-size: 18px;
}

.date {
    color: #7b8494;

    font-size: 11px;
}


/* STAT CARDS */

.stats {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 16px;
}

.stat {
    background: white;

    border:
        1px solid #e4e8ef;

    border-radius: 8px;

    padding: 20px;

    box-shadow:
        0 2px 8px
        rgba(20,35,60,.03);
}

.stat-label {
    color: #788396;

    font-size: 11px;
}

.stat-value {
    margin-top: 8px;

    color: #172033;

    font-size: 25px;

    font-weight: bold;
}

.positive {
    margin-top: 5px;

    color: #159570;

    font-size: 11px;
}


/* CHART */

.panel {
    margin-top: 20px;

    background: white;

    border:
        1px solid #e4e8ef;

    border-radius: 8px;

    padding: 25px;
}

.panel h3 {
    margin: 0;

    font-size: 15px;
}

.panel-subtitle {
    margin-top: 5px;

    color: #8791a1;

    font-size: 11px;
}

.chart {
    height: 220px;

    margin-top: 25px;

    position: relative;

    overflow: hidden;

    background:
        repeating-linear-gradient(
            to bottom,
            transparent 0,
            transparent 54px,
            #edf0f5 55px
        );
}

.chart-line {
    position: absolute;

    left: 3%;

    right: 3%;

    top: 35%;

    height: 3px;

    background: #5b67d8;

    transform:
        rotate(-5deg);

    box-shadow:
        55px 35px 0 #5b67d8,
        115px 15px 0 #5b67d8,
        175px 50px 0 #5b67d8,
        235px 5px 0 #5b67d8,
        295px 30px 0 #5b67d8,
        355px -5px 0 #5b67d8,
        415px 20px 0 #5b67d8;
}


/* TABLE */

.table {
    width: 100%;

    margin-top: 25px;

    border-collapse: collapse;

    font-size: 12px;
}

.table th {
    padding:
        12px;

    text-align: left;

    color: #8791a1;

    border-bottom:
        1px solid #e8ebf0;

    font-weight: normal;
}

.table td {
    padding:
        15px 12px;

    border-bottom:
        1px solid #eef0f4;

    color: #3b4657;
}

.status {
    color: #159570;

    background: #e7f7f2;

    padding:
        5px 8px;

    border-radius: 12px;

    font-size: 10px;
}


/* FOOTER */

footer {
    background: #172033;

    color: #8993a4;

    padding:
        30px 25px;

    text-align: center;

    font-size: 10px;
}


@media(max-width: 800px) {

    .header-inner {
        flex-wrap: wrap;

        gap: 15px;
    }

    nav {
        order: 3;

        width: 100%;

        overflow-x: auto;
    }

    .stats {
        grid-template-columns:
            repeat(2, 1fr);
    }

}


@media(max-width: 500px) {

    .stats {
        grid-template-columns: 1fr;
    }

    .hero h1 {
        font-size: 31px;
    }

}

</style>

</head>


<body>


<header>

    <div class="header-inner">


        <div class="logo">

            Insight<span>ly</span>

        </div>


        <nav>

            <a href="#">
                Overview
            </a>

            <a href="#">
                Reports
            </a>

            <a href="#">
                Customers
            </a>

            <a href="#">
                Integrations
            </a>

        </nav>


        <div class="account">

            Acme Corporation

            <div class="avatar">
                AC
            </div>

        </div>


    </div>

</header>


<section class="hero">

    <div class="hero-inner">


        <span class="badge">
            Business Intelligence
        </span>


        <h1>
            Understand your business at a glance.
        </h1>


        <p>
            Insightly brings your sales, customer and
            operational data together in one place so
            your team can make better decisions.
        </p>


    </div>

</section>


<main class="container">


    <div class="dashboard-header">

        <h2>
            Performance overview
        </h2>

        <span class="date">
            Last updated today at 09:42 UTC
        </span>

    </div>


    <div class="stats">


        <div class="stat">

            <div class="stat-label">
                Monthly Revenue
            </div>

            <div class="stat-value">
                $284,920
            </div>

            <div class="positive">
                ↑ 12.8% this month
            </div>

        </div>


        <div class="stat">

            <div class="stat-label">
                Active Customers
            </div>

            <div class="stat-value">
                8,421
            </div>

            <div class="positive">
                ↑ 6.4% this month
            </div>

        </div>


        <div class="stat">

            <div class="stat-label">
                Conversion Rate
            </div>

            <div class="stat-value">
                7.82%
            </div>

            <div class="positive">
                ↑ 1.2% this month
            </div>

        </div>


        <div class="stat">

            <div class="stat-label">
                Active Projects
            </div>

            <div class="stat-value">
                146
            </div>

            <div class="positive">
                ↑ 9 this month
            </div>

        </div>


    </div>


    <div class="panel">


        <h3>
            Revenue overview
        </h3>


        <div class="panel-subtitle">
            Monthly revenue across all accounts
        </div>


        <div class="chart">

            <div class="chart-line"></div>

        </div>


    </div>


    <div class="panel">


        <h3>
            Recent projects
        </h3>


        <table class="table">

            <thead>

                <tr>

                    <th>
                        Project
                    </th>

                    <th>
                        Owner
                    </th>

                    <th>
                        Updated
                    </th>

                    <th>
                        Status
                    </th>

                </tr>

            </thead>


            <tbody>


                <tr>

                    <td>
                        Northstar Campaign
                    </td>

                    <td>
                        Marketing
                    </td>

                    <td>
                        Today
                    </td>

                    <td>
                        <span class="status">
                            Active
                        </span>
                    </td>

                </tr>


                <tr>

                    <td>
                        Customer Insights
                    </td>

                    <td>
                        Analytics
                    </td>

                    <td>
                        Yesterday
                    </td>

                    <td>
                        <span class="status">
                            Active
                        </span>
                    </td>

                </tr>


                <tr>

                    <td>
                        Q4 Forecast
                    </td>

                    <td>
                        Finance
                    </td>

                    <td>
                        2 days ago
                    </td>

                    <td>
                        <span class="status">
                            Active
                        </span>
                    </td>

                </tr>


            </tbody>

        </table>


    </div>


</main>


<footer>

    Insightly Analytics

    ·

    © 2026 Insightly Technologies

</footer>


<!-- IMPORTANT:
     The application loads a production JavaScript bundle.
-->

<script src="/static/js/app.min.js"></script>

</body>

</html>