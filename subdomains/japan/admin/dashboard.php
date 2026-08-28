<?php

session_start();

/*
 * Authentication check
 */

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /japan/admin/login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'admin';

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Riverview Institute — Administration</title>


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

            background: #f4f6f8;

            color: #263238;
        }


        /* =========================
           SIDEBAR
        ========================== */

        .sidebar {
            position: fixed;

            left: 0;
            top: 0;
            bottom: 0;

            width: 245px;

            background: #102a43;

            color: white;

            padding: 24px 0;
        }

        .brand {
            padding:
                0 24px 25px;

            border-bottom:
                1px solid
                rgba(255,255,255,.1);
        }

        .brand-title {
            font-size: 19px;

            font-weight: 700;
        }

        .brand-subtitle {
            margin-top: 5px;

            color: #9fb3c8;

            font-size: 11px;
        }


        .menu {
            margin-top: 22px;
        }

        .menu-title {
            padding:
                0 24px 9px;

            color: #829ab1;

            font-size: 10px;

            text-transform:
                uppercase;

            letter-spacing: 1px;
        }

        .menu a {
            display: block;

            padding:
                12px 24px;

            color: #d9e2ec;

            text-decoration: none;

            font-size: 13px;
        }

        .menu a:hover,
        .menu a.active {
            background: #1769aa;

            color: white;
        }


        /* =========================
           MAIN
        ========================== */

        .main {
            margin-left: 245px;

            min-height: 100vh;
        }


        /* =========================
           TOPBAR
        ========================== */

        .topbar {
            height: 68px;

            background: white;

            border-bottom:
                1px solid #e1e7ee;

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            padding:
                0 30px;
        }

        .page-name {
            font-size: 17px;

            font-weight: 600;

            color: #243b53;
        }

        .profile {
            display: flex;

            align-items: center;

            gap: 12px;
        }

        .avatar {
            width: 34px;
            height: 34px;

            border-radius: 50%;

            background: #1769aa;

            color: white;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 13px;

            font-weight: bold;
        }

        .profile-name {
            font-size: 13px;

            color: #34495e;
        }

        .logout {
            margin-left: 15px;

            color: #d64545;

            text-decoration: none;

            font-size: 12px;
        }


        /* =========================
           CONTENT
        ========================== */

        .content {
            padding: 32px;
        }

        .welcome {
            margin-bottom: 28px;
        }

        .welcome h1 {
            margin: 0;

            color: #102a43;

            font-size: 27px;
        }

        .welcome p {
            margin-top: 7px;

            color: #718096;

            font-size: 14px;
        }


        /* =========================
           STAT CARDS
        ========================== */

        .stats {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 28px;
        }

        .stat {
            background: white;

            border:
                1px solid #e1e7ee;

            border-radius: 7px;

            padding: 20px;
        }

        .stat-label {
            color: #718096;

            font-size: 12px;
        }

        .stat-value {
            margin-top: 8px;

            color: #243b53;

            font-size: 25px;

            font-weight: 700;
        }

        .stat-change {
            margin-top: 7px;

            font-size: 11px;

            color: #2f855a;
        }


        /* =========================
           GRID
        ========================== */

        .dashboard-grid {
            display: grid;

            grid-template-columns:
                1.5fr 1fr;

            gap: 22px;
        }

        .panel {
            background: white;

            border:
                1px solid #e1e7ee;

            border-radius: 7px;

            overflow: hidden;
        }

        .panel-header {
            padding: 17px 20px;

            border-bottom:
                1px solid #e8edf2;

            display: flex;

            justify-content:
                space-between;

            align-items: center;
        }

        .panel-title {
            color: #243b53;

            font-size: 14px;

            font-weight: 700;
        }

        .panel-link {
            color: #1769aa;

            font-size: 11px;

            text-decoration: none;
        }


        /* =========================
           TABLE
        ========================== */

        table {
            width: 100%;

            border-collapse:
                collapse;
        }

        th {
            text-align: left;

            padding:
                12px 20px;

            background: #f8fafc;

            color: #718096;

            font-size: 10px;

            text-transform:
                uppercase;

            letter-spacing: .4px;
        }

        td {
            padding:
                14px 20px;

            border-top:
                1px solid #edf0f3;

            font-size: 12px;

            color: #4a5568;
        }

        .status {
            display: inline-block;

            padding:
                4px 8px;

            border-radius: 20px;

            background: #e6fffa;

            color: #276749;

            font-size: 10px;
        }


        /* =========================
           ACTIVITY
        ========================== */

        .activity {
            padding: 0 20px;
        }

        .activity-item {
            padding: 16px 0;

            border-bottom:
                1px solid #edf0f3;
        }

        .activity-item:last-child {
            border-bottom: 0;
        }

        .activity-time {
            color: #1769aa;

            font-size: 10px;
        }

        .activity-text {
            margin-top: 5px;

            color: #4a5568;

            font-size: 12px;

            line-height: 1.5;
        }


        /* =========================
           QUICK LINKS
        ========================== */

        .quick-links {
            margin-top: 22px;

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 15px;
        }

        .quick-link {
            background: white;

            border:
                1px solid #e1e7ee;

            border-radius: 7px;

            padding: 17px;

            text-decoration: none;

            color: #243b53;
        }

        .quick-link:hover {
            border-color: #1769aa;
        }

        .quick-icon {
            color: #1769aa;

            font-size: 20px;

            margin-bottom: 10px;
        }

        .quick-title {
            font-weight: 700;

            font-size: 13px;
        }

        .quick-description {
            margin-top: 5px;

            color: #718096;

            font-size: 11px;

            line-height: 1.4;
        }


        /* =========================
           RESPONSIVE
        ========================== */

        @media(max-width: 1000px) {

            .stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

        }


        @media(max-width: 700px) {

            .sidebar {
                width: 65px;
            }

            .brand-title,
            .brand-subtitle,
            .menu-title,
            .menu a span {
                display: none;
            }

            .brand {
                padding:
                    0 17px 25px;
            }

            .menu a {
                text-align: center;

                padding:
                    15px 5px;
            }

            .main {
                margin-left: 65px;
            }

            .content {
                padding: 20px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .quick-links {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================== -->

<aside class="sidebar">


    <div class="brand">

        <div class="brand-title">
            RIVERVIEW
        </div>

        <div class="brand-subtitle">
            ADMINISTRATION PORTAL
        </div>

    </div>


    <div class="menu">


        <div class="menu-title">
            Administration
        </div>


        <a
            href="#"
            class="active"
        >
            ▦
            <span>Dashboard</span>
        </a>


        <a href="#">
            ▣
            <span>Students</span>
        </a>


        <a href="#">
            ◫
            <span>Faculty</span>
        </a>


        <a href="#">
            ▤
            <span>Courses</span>
        </a>


        <a href="#">
            ▥
            <span>Examinations</span>
        </a>


        <a href="#">
            ◈
            <span>Admissions</span>
        </a>


        <div
            class="menu-title"
            style="margin-top:25px;"
        >
            System
        </div>


        <a href="#">
            ⚙
            <span>Settings</span>
        </a>


        <a href="#">
            ?
            <span>Help & Support</span>
        </a>


    </div>

</aside>


<!-- =========================
     MAIN
========================== -->

<div class="main">


    <div class="topbar">


        <div class="page-name">
            Administration Dashboard
        </div>


        <div class="profile">


            <div class="avatar">
                A
            </div>


            <div class="profile-name">
                <?= $username ?>
            </div>


            <a
                href="logout.php"
                class="logout"
            >
                Sign out
            </a>


        </div>


    </div>


    <div class="content">


        <!-- =========================
             WELCOME
        ========================== -->

        <div class="welcome">

            <h1>
                Good morning, <?= $username ?>
            </h1>

            <p>
                Here's an overview of activity
                across the institute.
            </p>

        </div>


        <!-- =========================
             STATISTICS
        ========================== -->

        <div class="stats">


            <div class="stat">

                <div class="stat-label">
                    Total Students
                </div>

                <div class="stat-value">
                    8,426
                </div>

                <div class="stat-change">
                    ↑ 4.8% this semester
                </div>

            </div>


            <div class="stat">

                <div class="stat-label">
                    Faculty Members
                </div>

                <div class="stat-value">
                    384
                </div>

                <div class="stat-change">
                    ↑ 2 new this month
                </div>

            </div>


            <div class="stat">

                <div class="stat-label">
                    Active Courses
                </div>

                <div class="stat-value">
                    217
                </div>

                <div class="stat-change">
                    2026–27 academic year
                </div>

            </div>


            <div class="stat">

                <div class="stat-label">
                    Applications
                </div>

                <div class="stat-value">
                    1,284
                </div>

                <div class="stat-change">
                    ↑ 12.4% from last year
                </div>

            </div>


        </div>


        <!-- =========================
             DASHBOARD PANELS
        ========================== -->

        <div class="dashboard-grid">


            <!-- Recent applications -->

            <section class="panel">


                <div class="panel-header">

                    <div class="panel-title">
                        Recent Applications
                    </div>

                    <a
                        href="#"
                        class="panel-link"
                    >
                        View all
                    </a>

                </div>


                <table>

                    <thead>

                        <tr>

                            <th>
                                Applicant
                            </th>

                            <th>
                                Program
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <tr>

                            <td>
                                Aarav Mehta
                            </td>

                            <td>
                                B.Tech Computer Science
                            </td>

                            <td>
                                Aug 27
                            </td>

                            <td>
                                <span class="status">
                                    Submitted
                                </span>
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Priya Sharma
                            </td>

                            <td>
                                B.Tech Electronics
                            </td>

                            <td>
                                Aug 27
                            </td>

                            <td>
                                <span class="status">
                                    Verified
                                </span>
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Daniel Wilson
                            </td>

                            <td>
                                M.Tech Computing
                            </td>

                            <td>
                                Aug 26
                            </td>

                            <td>
                                <span class="status">
                                    Submitted
                                </span>
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Sara Khan
                            </td>

                            <td>
                                BBA Management
                            </td>

                            <td>
                                Aug 26
                            </td>

                            <td>
                                <span class="status">
                                    Verified
                                </span>
                            </td>

                        </tr>


                    </tbody>

                </table>


            </section>


            <!-- Activity -->

            <section class="panel">


                <div class="panel-header">

                    <div class="panel-title">
                        Recent Activity
                    </div>

                </div>


                <div class="activity">


                    <div class="activity-item">

                        <div class="activity-time">
                            14 minutes ago
                        </div>

                        <div class="activity-text">
                            Course catalog updated by
                            Academic Affairs.
                        </div>

                    </div>


                    <div class="activity-item">

                        <div class="activity-time">
                            47 minutes ago
                        </div>

                        <div class="activity-text">
                            New admission application
                            received.
                        </div>

                    </div>


                    <div class="activity-item">

                        <div class="activity-time">
                            2 hours ago
                        </div>

                        <div class="activity-text">
                            Examination timetable
                            published.
                        </div>

                    </div>


                    <div class="activity-item">

                        <div class="activity-time">
                            Yesterday
                        </div>

                        <div class="activity-text">
                            Semester registration
                            window opened.
                        </div>

                    </div>


                </div>


            </section>


        </div>


        <!-- =========================
             QUICK LINKS
        ========================== -->

        <div class="quick-links">


            <a
                href="#"
                class="quick-link"
            >

                <div class="quick-icon">
                    +
                </div>

                <div class="quick-title">
                    Add Student
                </div>

                <div class="quick-description">
                    Create a new student record
                    in the institute system.
                </div>

            </a>


            <a
                href="#"
                class="quick-link"
            >

                <div class="quick-icon">
                    ▤
                </div>

                <div class="quick-title">
                    Publish Notice
                </div>

                <div class="quick-description">
                    Publish an announcement
                    to students and faculty.
                </div>

            </a>


            <a
                href="#"
                class="quick-link"
            >

                <div class="quick-icon">
                    ↓
                </div>

                <div class="quick-title">
                    Reports
                </div>

                <div class="quick-description">
                    View academic and
                    administrative reports.
                </div>

            </a>


        </div>


    </div>

</div>


</body>

</html>
