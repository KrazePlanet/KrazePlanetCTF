<?php
    require_once '../vendor/autoload.php';
    $stripeSecretKey='sk_test_51NS32vJ2FpacagSqqCdqiIJhLT6QrSP8YX3scgyck4jnFw6WcyCV90rKZPZyRWRQQbIEmq23L5uKNsEDcx98eQyI00Sm6jypQw';
    \Stripe\Stripe::setApiKey($stripeSecretKey);

    
    // Create a Stripe Checkout Session
    $checkout_session = \Stripe\Checkout\Session::create([
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Chosen Plan: ' . $selectedPlan,
                ],
                'unit_amount' => $unitAmount,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://localhost/cowork-website/success.php',
        'cancel_url' => 'http://localhost/cowork-website/cancel.php',
    ]);
    
    header("HTTP/1.1 303 See Other");
    header("Location: " . $checkout_session->url);
    ?>
    


<html>
    <head>
        <title>Buy cool new product</title>
        <script src="https://js.stripe.com/v3/">
        </script>
    </head>
    <body>
        <button type="submit" id="checkout-button">Checkout</button>
        <script>
            var stripe =Stripe('pk_test_51NS32vJ2FpacagSqh3WT7jQHwO6jCopHYgmDrjHm8YNfp0FbsvwYu6QsY462QQbiYiaHSycri8NzzodAMZo9cihQ00Tlxm9qmu');
            document.getElementById("checkout-button")
            btn.addEventListener('click', function(e) {
                e.preventDefault(); // avoid to execute the actual submit of the form.
            
            stripe.redirectToCheckout({
                sessionId: '<?=$checkout_session['id']?>'
            });
        });
        </script>
        </form>
    </body>
</html>