<?php

/*
 * THREADLINE
 * Contemporary clothing & accessories
 */

$category = $_GET['category'] ?? 'all';

$products = [
    [
        'name' => 'Relaxed Oxford Shirt',
        'category' => 'shirts',
        'price' => '$64',
        'color' => 'Cloud White',
        'description' => 'A relaxed everyday shirt crafted from lightweight cotton.'
    ],
    [
        'name' => 'Essential Overshirt',
        'category' => 'jackets',
        'price' => '$118',
        'color' => 'Olive',
        'description' => 'A structured overshirt designed for transitional weather.'
    ],
    [
        'name' => 'Straight Denim',
        'category' => 'denim',
        'price' => '$92',
        'color' => 'Indigo',
        'description' => 'Classic straight-leg denim with a comfortable everyday fit.'
    ],
    [
        'name' => 'Studio Knit',
        'category' => 'knitwear',
        'price' => '$84',
        'color' => 'Stone',
        'description' => 'Soft textured knitwear with a clean minimal silhouette.'
    ],
    [
        'name' => 'Everyday Chino',
        'category' => 'trousers',
        'price' => '$76',
        'color' => 'Sand',
        'description' => 'Versatile cotton chinos made for everyday movement.'
    ],
    [
        'name' => 'Heavyweight Tee',
        'category' => 't-shirts',
        'price' => '$42',
        'color' => 'Washed Black',
        'description' => 'A heavyweight cotton tee with a relaxed modern cut.'
    ]
];

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
    THREADLINE — Modern Everyday Clothing
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {
    box-sizing: border-box;
}

body {
    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #faf9f7;

    color: #1d1d1b;
}

a {
    color: inherit;

    text-decoration: none;
}


/* =========================================================
   TOP BAR
========================================================= */

.topbar {

    background: #1d1d1b;

    color: #fff;

    text-align: center;

    padding: 9px;

    font-size: 11px;

    letter-spacing: .4px;
}


/* =========================================================
   HEADER
========================================================= */

header {

    background: #faf9f7;

    border-bottom:
        1px solid #e3e1dc;

    position: sticky;

    top: 0;

    z-index: 10;
}

.header-inner {

    max-width: 1240px;

    margin: auto;

    padding:
        20px 25px;

    display: flex;

    align-items: center;

    justify-content: space-between;
}

.logo {

    font-size: 23px;

    font-weight: 800;

    letter-spacing: 3px;
}

.logo span {

    display: block;

    margin-top: 3px;

    font-size: 8px;

    font-weight: normal;

    letter-spacing: 2px;

    color: #77736d;
}

nav {

    display: flex;

    gap: 28px;
}

nav a {

    font-size: 12px;

    text-transform: uppercase;

    letter-spacing: .8px;
}

nav a:hover {

    color: #77736d;
}

.header-actions {

    display: flex;

    gap: 18px;

    font-size: 12px;
}


/* =========================================================
   HERO
========================================================= */

.hero {

    min-height: 500px;

    background:
        linear-gradient(
            90deg,
            rgba(20,20,18,.88),
            rgba(20,20,18,.25)
        ),
        linear-gradient(
            135deg,
            #574f45,
            #c8bba8
        );

    display: flex;

    align-items: center;

    color: white;
}

.hero-inner {

    max-width: 1240px;

    width: 100%;

    margin: auto;

    padding: 70px 25px;
}

.hero small {

    text-transform: uppercase;

    letter-spacing: 2px;

    font-size: 11px;
}

.hero h1 {

    max-width: 560px;

    margin:
        14px 0;

    font-size: 54px;

    line-height: 1.02;

    font-weight: 500;
}

.hero p {

    max-width: 480px;

    color: #eeeae4;

    line-height: 1.7;

    font-size: 14px;
}

.hero-button {

    display: inline-block;

    margin-top: 18px;

    background: white;

    color: #1d1d1b;

    padding:
        14px 24px;

    font-size: 11px;

    font-weight: bold;

    text-transform: uppercase;

    letter-spacing: 1px;
}


/* =========================================================
   CONTENT
========================================================= */

.container {

    max-width: 1240px;

    margin: auto;

    padding:
        60px 25px;
}

.section-heading {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 28px;
}

.section-heading h2 {

    margin: 0;

    font-size: 24px;

    font-weight: 500;
}

.section-heading a {

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: 1px;

    color: #77736d;
}


/* =========================================================
   PRODUCTS
========================================================= */

.products {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 22px;
}

.product {

    background: white;

    border:
        1px solid #e5e2dd;
}

.product-image {

    height: 330px;

    display: flex;

    align-items: flex-end;

    padding: 18px;

    color: white;

    font-size: 10px;

    letter-spacing: 1px;

    text-transform: uppercase;
}

.product:nth-child(1) .product-image {
    background:
        linear-gradient(
            135deg,
            #d9d5cd,
            #918b82
        );
}

.product:nth-child(2) .product-image {
    background:
        linear-gradient(
            135deg,
            #7d806d,
            #37392f
        );
}

.product:nth-child(3) .product-image {
    background:
        linear-gradient(
            135deg,
            #343a4a,
            #11151e
        );
}

.product:nth-child(4) .product-image {
    background:
        linear-gradient(
            135deg,
            #c5b8a5,
            #756a5c
        );
}

.product:nth-child(5) .product-image {
    background:
        linear-gradient(
            135deg,
            #d1b88f,
            #817157
        );
}

.product:nth-child(6) .product-image {
    background:
        linear-gradient(
            135deg,
            #4b4a47,
            #161615
        );
}

.product-info {

    padding: 19px;
}

.product-info h3 {

    margin:
        0 0 7px;

    font-size: 14px;

    font-weight: 500;
}

.product-meta {

    display: flex;

    justify-content: space-between;

    color: #77736d;

    font-size: 11px;
}

.product-description {

    margin-top: 12px;

    color: #77736d;

    line-height: 1.5;

    font-size: 11px;
}


/* =========================================================
   FEATURE STRIP
========================================================= */

.features {

    margin-top: 55px;

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    border-top:
        1px solid #ddd9d3;

    border-bottom:
        1px solid #ddd9d3;
}

.feature {

    padding: 25px;

    text-align: center;

    border-right:
        1px solid #ddd9d3;
}

.feature:last-child {

    border-right: 0;
}

.feature strong {

    display: block;

    font-size: 12px;

    margin-bottom: 7px;
}

.feature span {

    color: #77736d;

    font-size: 11px;
}


/* =========================================================
   NEWSLETTER
========================================================= */

.newsletter {

    margin-top: 65px;

    background: #e9e5de;

    padding: 55px 30px;

    text-align: center;
}

.newsletter h2 {

    margin:
        0 0 10px;

    font-size: 25px;

    font-weight: 500;
}

.newsletter p {

    color: #68645e;

    font-size: 12px;
}

.newsletter form {

    max-width: 460px;

    margin:
        22px auto 0;

    display: flex;
}

.newsletter input {

    flex: 1;

    border: 1px solid #c9c4bc;

    background: white;

    padding: 13px;

    outline: none;
}

.newsletter button {

    border: 0;

    background: #1d1d1b;

    color: white;

    padding:
        0 22px;

    cursor: pointer;
}


/* =========================================================
   FOOTER
========================================================= */

footer {

    background: #1d1d1b;

    color: #aaa69f;

    margin-top: 70px;

    padding:
        45px 25px;
}

.footer-inner {

    max-width: 1240px;

    margin: auto;

    display: grid;

    grid-template-columns:
        2fr 1fr 1fr 1fr;

    gap: 30px;
}

.footer-brand {

    color: white;

    font-size: 18px;

    letter-spacing: 2px;

    font-weight: bold;
}

footer h4 {

    margin-top: 0;

    color: white;

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: 1px;
}

footer a {

    display: block;

    margin-top: 10px;

    font-size: 11px;
}

.copyright {

    max-width: 1240px;

    margin:
        35px auto 0;

    padding-top: 20px;

    border-top:
        1px solid #393836;

    font-size: 10px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

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

    .hero h1 {

        font-size: 40px;
    }

    .products {

        grid-template-columns:
            repeat(2, 1fr);
    }

    .features {

        grid-template-columns: 1fr;
    }

    .feature {

        border-right: 0;

        border-bottom:
            1px solid #ddd9d3;
    }

    .footer-inner {

        grid-template-columns:
            1fr 1fr;
    }

}

@media(max-width: 520px) {

    .products {

        grid-template-columns: 1fr;
    }

    .product-image {

        height: 280px;
    }

    .newsletter form {

        flex-direction: column;

        gap: 8px;
    }

    .newsletter button {

        padding: 13px;
    }

}

</style>

</head>


<body>


<div class="topbar">

    Complimentary shipping on orders over $100

</div>


<header>

    <div class="header-inner">


        <a href="/" class="logo">

            THREADLINE

            <span>
                MODERN EVERYDAY CLOTHING
            </span>

        </a>


        <nav>

            <a href="?category=all">
                New Arrivals
            </a>

            <a href="?category=shirts">
                Shirts
            </a>

            <a href="?category=denim">
                Denim
            </a>

            <a href="?category=knitwear">
                Knitwear
            </a>

            <a href="?category=jackets">
                Outerwear
            </a>

        </nav>


        <div class="header-actions">

            <span>
                Search
            </span>

            <span>
                Bag (0)
            </span>

        </div>


    </div>

</header>


<section class="hero">

    <div class="hero-inner">

        <small>
            Autumn / Winter 2026
        </small>

        <h1>
            Designed for every day.
        </h1>

        <p>
            Thoughtful essentials made from
            considered materials, with relaxed
            silhouettes designed to move with you.
        </p>

        <a
            href="#collection"
            class="hero-button"
        >
            Shop the collection
        </a>

    </div>

</section>


<main class="container" id="collection">


    <div class="section-heading">

        <h2>
            New arrivals
        </h2>

        <a href="?category=all">
            View all
        </a>

    </div>


    <div class="products">


        <?php foreach ($products as $product): ?>

            <?php

            if (
                $category !== 'all'
                &&
                $product['category'] !== $category
            ) {
                continue;
            }

            ?>


            <article class="product">


                <div class="product-image">

                    THREADLINE

                </div>


                <div class="product-info">


                    <h3>
                        <?= $product['name'] ?>
                    </h3>


                    <div class="product-meta">

                        <span>
                            <?= $product['color'] ?>
                        </span>

                        <strong>
                            <?= $product['price'] ?>
                        </strong>

                    </div>


                    <div class="product-description">

                        <?= $product['description'] ?>

                    </div>


                </div>


            </article>


        <?php endforeach; ?>


    </div>


    <div class="features">


        <div class="feature">

            <strong>
                FREE SHIPPING
            </strong>

            <span>
                On orders over $100
            </span>

        </div>


        <div class="feature">

            <strong>
                EASY RETURNS
            </strong>

            <span>
                30-day returns on all orders
            </span>

        </div>


        <div class="feature">

            <strong>
                CONSIDERED MATERIALS
            </strong>

            <span>
                Designed to last beyond the season
            </span>

        </div>


    </div>


    <section class="newsletter">


        <h2>
            Stay in the loop
        </h2>


        <p>
            New collections, early access and
            occasional notes from the studio.
        </p>


        <form>

            <input
                type="email"
                placeholder="Your email address"
            >

            <button type="submit">
                Subscribe
            </button>

        </form>


    </section>


</main>


<footer>


    <div class="footer-inner">


        <div>

            <div class="footer-brand">
                THREADLINE
            </div>

            <p>
                Modern everyday clothing designed
                in Copenhagen and made with care.
            </p>

        </div>


        <div>

            <h4>
                Shop
            </h4>

            <a href="#">
                New Arrivals
            </a>

            <a href="#">
                Clothing
            </a>

            <a href="#">
                Accessories
            </a>

        </div>


        <div>

            <h4>
                Help
            </h4>

            <a href="#">
                Contact
            </a>

            <a href="#">
                Shipping
            </a>

            <a href="#">
                Returns
            </a>

        </div>


        <div>

            <h4>
                Company
            </h4>

            <a href="#">
                About
            </a>

            <a href="#">
                Journal
            </a>

            <a href="#">
                Careers
            </a>

        </div>


    </div>


    <div class="copyright">

        © 2026 THREADLINE. All rights reserved.

    </div>


</footer>


</body>

</html>