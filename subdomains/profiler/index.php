<?php

$id = isset($_GET["id"]) ? $_GET["id"] : "1024";

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
    CampusCloud — Researcher Profile
</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;

    background: #f5f7fb;

    color: #172033;

    font-family:
        Arial,
        Helvetica,
        sans-serif;
}

header {
    height: 68px;

    background: white;

    border-bottom:
        1px solid #e5e9ef;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 35px;
}

.logo {
    font-size: 19px;

    font-weight: 800;

    color: #26324a;
}

.logo span {
    color: #5b67d8;
}

nav {
    display: flex;

    gap: 25px;
}

nav a {
    color: #707b8d;

    text-decoration: none;

    font-size: 12px;
}

nav a:hover {
    color: #5b67d8;
}

.container {
    max-width: 900px;

    margin: 55px auto;

    padding: 0 25px;
}

.breadcrumb {
    color: #8993a3;

    font-size: 11px;

    margin-bottom: 20px;
}

.profile-card {
    background: white;

    border:
        1px solid #e2e7ee;

    border-radius: 9px;

    padding: 35px;

    box-shadow:
        0 3px 12px
        rgba(25, 40, 65, .04);
}

.profile-top {
    display: flex;

    align-items: center;

    gap: 22px;

    padding-bottom: 30px;

    border-bottom:
        1px solid #edf0f4;
}

.avatar {
    width: 75px;

    height: 75px;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #6572e5,
            #8a93ed
        );

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

    font-weight: bold;
}

.name {
    margin: 0;

    font-size: 25px;
}

.role {
    color: #7b8798;

    font-size: 12px;

    margin-top: 7px;
}

.details {
    margin-top: 25px;

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;
}

.detail {
    padding: 18px;

    background: #f8f9fb;

    border-radius: 6px;
}

.label {
    color: #8a95a5;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .7px;
}

.value {
    margin-top: 7px;

    font-size: 13px;

    color: #283448;
}

.loading {
    color: #8993a3;

    font-size: 12px;
}

footer {
    text-align: center;

    color: #9aa4b2;

    font-size: 10px;

    padding: 30px;
}

@media(max-width: 650px) {

    .details {
        grid-template-columns: 1fr;
    }

    nav {
        display: none;
    }

}

</style>

</head>


<body>


<header>

    <div class="logo">
        Campus<span>Cloud</span>
    </div>


    <nav>

        <a href="#">
            Dashboard
        </a>

        <a href="#">
            Researchers
        </a>

        <a href="#">
            Publications
        </a>

        <a href="#">
            Contact
        </a>

    </nav>

</header>


<main class="container">


    <div class="breadcrumb">
        Researchers / Profile
    </div>


    <section class="profile-card">


        <div class="profile-top">


            <div class="avatar">
                MC
            </div>


            <div>

                <h1 class="name">
                    <span id="profile-name">
                        Loading...
                    </span>
                </h1>


                <div class="role">
                    Research Faculty
                </div>

            </div>


        </div>


        <div class="details">


            <div class="detail">

                <div class="label">
                    Department
                </div>

                <div
                    class="value"
                    id="profile-department"
                >
                    Loading...
                </div>

            </div>


            <div class="detail">

                <div class="label">
                    University
                </div>

                <div
                    class="value"
                >
                    Northbridge University
                </div>

            </div>


        </div>


    </section>


</main>


<footer>

    CampusCloud Research Network
    ·
    © 2026

</footer>


<script>

const profileId =
    <?= json_encode($id) ?>;


/*
 * The UI intentionally renders only
 * selected fields from the API response.
 */

fetch(
    "/profile/api/profile.php?id="
    + encodeURIComponent(profileId)
)

.then(response => response.json())

.then(data => {

    document.getElementById(
        "profile-name"
    ).textContent = data.name;


    document.getElementById(
        "profile-department"
    ).textContent = data.department;

})

.catch(() => {

    document.getElementById(
        "profile-name"
    ).textContent = "Profile unavailable";


    document.getElementById(
        "profile-department"
    ).textContent = "—";

});

</script>


</body>

</html>