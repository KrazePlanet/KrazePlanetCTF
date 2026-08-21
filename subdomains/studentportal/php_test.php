<?php
// Simple PHP test file
echo "<h1>PHP Test - If you see this, PHP is working!</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Test database connection
echo "<h2>Testing Database Connection:</h2>";
include("login1.php");

if ($con) {
    echo "✅ Database connection successful!<br>";
    echo "Server: " . mysqli_get_host_info($con) . "<br>";
} else {
    echo "❌ Database connection failed: " . mysqli_connect_error() . "<br>";
}

echo "<hr>";
echo "<h2>If you see PHP source code above, PHP is NOT working!</h2>";
echo "<p>You should see formatted output, not raw PHP code.</p>";
?>
