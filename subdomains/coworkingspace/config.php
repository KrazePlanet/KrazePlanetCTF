<?php 
 
// Product Details  
// Minimum amount is $0.50 US  
$productName = "Codex Demo Product";  
$productID = "DP12345";  
$productPrice = 55; 
$currency = "usd"; 
  
/* 
 * Stripe API configuration 
 * Remember to switch to your live publishable and secret key in production! 
 * See your keys here: https://dashboard.stripe.com/account/apikeys 
 */ 
define('STRIPE_API_KEY', 'sk_test_51NS32vJ2FpacagSqqCdqiIJhLT6QrSP8YX3scgyck4jnFw6WcyCV90rKZPZyRWRQQbIEmq23L5uKNsEDcx98eQyI00Sm6jypQw'); 
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51NS32vJ2FpacagSqh3WT7jQHwO6jCopHYgmDrjHm8YNfp0FbsvwYu6QsY462QQbiYiaHSycri8NzzodAMZo9cihQ00Tlxm9qmu'); 
define('STRIPE_SUCCESS_URL', 'https://localhost/cowork-website/payment-success.php'); //Payment success URL 
define('STRIPE_CANCEL_URL', 'https://localhost/cowork-website/payment-cancel.php'); //Payment cancel URL 
    
// Database configuration    
define('DB_HOST', 'localhost');   
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', '');   
define('DB_NAME', 'cowork_db'); 
 
?>