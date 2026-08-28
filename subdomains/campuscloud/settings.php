<?php

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
    CampusCloud — Settings
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
    max-width: 800px;

    margin: 40px auto;

    padding: 0 20px;
}

.card {
    background: white;

    border:
        1px solid #e4e8ef;

    border-radius: 8px;

    padding: 28px;
}

.setting {
    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 20px 0;

    border-bottom:
        1px solid #edf0f4;
}

.setting strong {
    font-size: 13px;
}

.setting span {
    color: #8993a3;

    font-size: 11px;

    display: block;

    margin-top: 5px;
}

.toggle {
    width: 38px;

    height: 20px;

    background: #5b67d8;

    border-radius: 20px;

    position: relative;
}

.toggle:after {
    content: "";

    position: absolute;

    width: 16px;

    height: 16px;

    right: 2px;

    top: 2px;

    background: white;

    border-radius: 50%;
}

</style>

</head>

<body>


<header>
    CampusCloud
</header>


<div class="container">


    <div class="card">


        <h1>
            Workspace Settings
        </h1>


        <div class="setting">

            <div>

                <strong>
                    Email notifications
                </strong>

                <span>
                    Receive workspace activity updates.
                </span>

            </div>

            <div class="toggle"></div>

        </div>


        <div class="setting">

            <div>

                <strong>
                    Weekly reports
                </strong>

                <span>
                    Receive a weekly workspace summary.
                </span>

            </div>

            <div class="toggle"></div>

        </div>


        <div class="setting">

            <div>

                <strong>
                    Security alerts
                </strong>

                <span>
                    Receive important security notifications.
                </span>

            </div>

            <div class="toggle"></div>

        </div>


    </div>


</div>


</body>

</html>