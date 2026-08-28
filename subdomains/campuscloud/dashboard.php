<?php

$page = "Dashboard";

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
    CampusCloud — Dashboard
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

    background: #f5f7fb;

    color: #172033;
}

.sidebar {
    position: fixed;

    left: 0;
    top: 0;
    bottom: 0;

    width: 230px;

    background: #111827;

    color: white;

    padding: 25px 18px;
}

.logo {
    font-size: 21px;

    font-weight: 800;

    margin-bottom: 40px;
}

.logo span {
    color: #6d7cff;
}

.workspace {
    color: #8d98aa;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: 1px;

    margin-bottom: 12px;
}

.sidebar a {
    display: block;

    padding: 12px 14px;

    margin-bottom: 5px;

    border-radius: 6px;

    color: #aeb7c6;

    text-decoration: none;

    font-size: 13px;
}

.sidebar a:hover,
.sidebar a.active {
    background: #202938;

    color: white;
}

.main {
    margin-left: 230px;
}

.topbar {
    height: 68px;

    background: white;

    border-bottom:
        1px solid #e5e9f0;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        0 35px;
}

.search {
    color: #9aa4b2;

    font-size: 12px;
}

.user {
    display: flex;

    align-items: center;

    gap: 10px;

    font-size: 12px;
}

.avatar {
    width: 32px;

    height: 32px;

    border-radius: 50%;

    background: #6673e8;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: bold;
}

.content {
    padding: 35px;
}

.welcome {
    margin-bottom: 28px;
}

.welcome h1 {
    margin: 0;

    font-size: 25px;
}

.welcome p {
    color: #7c8798;

    font-size: 13px;
}

.stats {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 18px;
}

.card {
    background: white;

    border:
        1px solid #e4e8ef;

    border-radius: 8px;

    padding: 22px;
}

.label {
    color: #7c8798;

    font-size: 11px;
}

.value {
    margin-top: 9px;

    font-size: 25px;

    font-weight: bold;
}

.change {
    margin-top: 6px;

    color: #159570;

    font-size: 10px;
}

.panel {
    margin-top: 22px;

    background: white;

    border:
        1px solid #e4e8ef;

    border-radius: 8px;

    padding: 25px;
}

.panel h2 {
    margin: 0;

    font-size: 16px;
}

table {
    width: 100%;

    border-collapse: collapse;

    margin-top: 20px;

    font-size: 12px;
}

th {
    color: #8993a3;

    font-weight: normal;

    text-align: left;

    padding: 12px;

    border-bottom:
        1px solid #edf0f4;
}

td {
    padding: 15px 12px;

    border-bottom:
        1px solid #edf0f4;
}

.status {
    background: #e7f7f1;

    color: #15805f;

    padding: 5px 8px;

    border-radius: 12px;

    font-size: 10px;
}

@media(max-width: 800px) {

    .sidebar {
        width: 190px;
    }

    .main {
        margin-left: 190px;
    }

    .stats {
        grid-template-columns:
            repeat(2, 1fr);
    }

}

</style>

</head>


<body>


<aside class="sidebar">

    <div class="logo">
        Campus<span>Cloud</span>
    </div>


    <div class="workspace">
        Workspace
    </div>


    <a
        href="dashboard.php"
        class="active"
    >
        Dashboard
    </a>


    <a href="profile.php">
        Profile
    </a>


    <a href="projects.php">
        Projects
    </a>


    <a href="settings.php">
        Settings
    </a>

</aside>


<div class="main">


    <header class="topbar">

        <div class="search">
            Search your workspace...
        </div>


        <div class="user">

            Campus Operations

            <div class="avatar">
                CO
            </div>

        </div>

    </header>


    <main class="content">


        <section class="welcome">

            <h1>
                Good morning, Alex.
            </h1>

            <p>
                Here's what's happening across
                your workspace today.
            </p>

        </section>


        <section class="stats">


            <div class="card">

                <div class="label">
                    Active Projects
                </div>

                <div class="value">
                    24
                </div>

                <div class="change">
                    ↑ 8.2% this month
                </div>

            </div>


            <div class="card">

                <div class="label">
                    Team Members
                </div>

                <div class="value">
                    148
                </div>

                <div class="change">
                    ↑ 12 new members
                </div>

            </div>


            <div class="card">

                <div class="label">
                    Tasks Completed
                </div>

                <div class="value">
                    1,284
                </div>

                <div class="change">
                    ↑ 18.6% this month
                </div>

            </div>


            <div class="card">

                <div class="label">
                    Storage Used
                </div>

                <div class="value">
                    64%
                </div>

                <div class="change">
                    128 GB of 200 GB
                </div>

            </div>


        </section>


        <section class="panel">

            <h2>
                Recent projects
            </h2>


            <table>

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
                            Student Portal
                        </td>

                        <td>
                            Product Team
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
                            Campus Mobile App
                        </td>

                        <td>
                            Engineering
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
                            Analytics Migration
                        </td>

                        <td>
                            Data Team
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

        </section>


    </main>

</div>


</body>

</html>