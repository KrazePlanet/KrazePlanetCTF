<?php
session_start();

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($username === 'admin' && $password === 'admin') {

        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = 'admin';

        header('Location: dashboard.php');
        exit;

    } else {

        $error = 'Invalid username or password.';

    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #102a43,
                    #1769aa
                );

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        .login-box {
            width: 380px;

            background: #ffffff;

            border-radius: 10px;

            padding: 35px;

            box-shadow:
                0 15px 45px
                rgba(0, 0, 0, .2);
        }

        .logo {
            width: 48px;
            height: 48px;

            margin: 0 auto 18px;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #1769aa;

            color: white;

            font-weight: bold;
        }

        h1 {
            margin: 0;

            text-align: center;

            color: #243b53;

            font-size: 24px;
        }

        .subtitle {
            text-align: center;

            color: #718096;

            font-size: 13px;

            margin:
                8px 0 27px;
        }

        label {
            display: block;

            margin-bottom: 7px;

            color: #34495e;

            font-size: 13px;

            font-weight: bold;
        }

        input {
            width: 100%;

            padding: 13px;

            margin-bottom: 18px;

            border:
                1px solid #d9e2ec;

            border-radius: 6px;

            outline: none;

            font-size: 14px;
        }

        input:focus {
            border-color: #1769aa;
        }

        button {
            width: 100%;

            border: 0;

            padding: 13px;

            border-radius: 6px;

            background: #1769aa;

            color: white;

            font-weight: bold;

            cursor: pointer;
        }

        button:hover {
            background: #145b91;
        }

        .error {
            margin-bottom: 18px;

            padding: 11px;

            border-radius: 6px;

            background: #fff1f2;

            border: 1px solid #fecdd3;

            color: #be123c;

            font-size: 13px;
        }

        .footer {
            margin-top: 22px;

            text-align: center;

            color: #9aa5b1;

            font-size: 11px;
        }

    </style>

</head>


<body>


<div class="login-box">


    <div class="logo">
        R
    </div>


    <h1>
        Administration Portal
    </h1>


    <div class="subtitle">
        Riverview Institute of Technology
    </div>


    <?php if ($error): ?>

        <div class="error">
            <?= $error ?>
        </div>

    <?php endif; ?>


    <form method="POST">


        <label for="username">
            Username
        </label>

        <input
            type="text"
            id="username"
            name="username"
            placeholder="Enter username"
            required
        >


        <label for="password">
            Password
        </label>

        <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter password"
            required
        >


        <button type="submit">
            Sign in
        </button>


    </form>


    <div class="footer">
        Authorized personnel only
    </div>


</div>


</body>

</html>
