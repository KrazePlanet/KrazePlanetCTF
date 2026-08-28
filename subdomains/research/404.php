<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Page Not Found — Riverview Research</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            min-height: 100vh;

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
            font-size: 18px;

            font-weight: bold;
        }

        nav a {
            color: #d9e2ec;

            text-decoration: none;

            margin-left: 24px;

            font-size: 13px;
        }

        .container {
            max-width: 900px;

            margin: 90px auto;

            padding: 20px;

            text-align: center;
        }

        .code {
            font-size: 90px;

            line-height: 1;

            font-weight: 700;

            color: #1769aa;
        }

        h1 {
            margin:
                20px 0 10px;

            font-size: 27px;
        }

        p {
            max-width: 570px;

            margin: auto;

            color: #718096;

            line-height: 1.6;

            font-size: 14px;
        }

        .path {
            display: inline-block;

            margin-top: 22px;

            padding: 10px 14px;

            background: white;

            border:
                1px solid #d9e2ec;

            border-radius: 5px;

            color: #52606d;

            font-family: monospace;

            font-size: 12px;
        }

        .button {
            display: inline-block;

            margin-top: 25px;

            padding:
                11px 20px;

            background: #1769aa;

            color: white;

            text-decoration: none;

            border-radius: 5px;

            font-size: 13px;
        }

        footer {
            position: fixed;

            bottom: 0;

            left: 0;

            right: 0;

            padding: 18px;

            text-align: center;

            background: #102a43;

            color: #9fb3c8;

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


<div class="container">

    <div class="code">
        404
    </div>

    <h1>
        Research page not found
    </h1>

    <p>
        The resource you requested could not be
        found in the Riverview Research Portal.
        It may have been moved, archived, or removed.
    </p>

    <div class="path">
        Requested:
        <?php
            echo $_SERVER['REQUEST_URI'];
        ?>
    </div>

    <br>

    <a
        class="button"
        href="/"
    >
        Return to Research Portal
    </a>

</div>


<footer>

    Riverview Institute of Technology ·
    Research & Innovation

</footer>


</body>

</html>
