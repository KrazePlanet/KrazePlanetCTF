<?php
// Test database connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Database Connection</h2>";

// Test 1: Basic connection
echo "<h3>Test 1: Basic Connection</h3>";
include("login1.php");

if ($con) {
    echo "✅ Database connection successful!<br>";
    echo "Server: " . mysqli_get_host_info($con) . "<br>";
    echo "Database: " . mysqli_get_dbname($con) . "<br>";
} else {
    echo "❌ Database connection failed!<br>";
    echo "Error: " . mysqli_connect_error() . "<br>";
}

// Test 2: Check if tables exist
echo "<h3>Test 2: Check Tables</h3>";
if ($con) {
    $result = mysqli_query($con, "SHOW TABLES");
    if ($result) {
        echo "✅ Tables found:<br>";
        while ($row = mysqli_fetch_array($result)) {
            echo "- " . $row[0] . "<br>";
        }
    } else {
        echo "❌ Error showing tables: " . mysqli_error($con) . "<br>";
    }
}

// Test 3: Check faculty table structure
echo "<h3>Test 3: Faculty Table Structure</h3>";
if ($con) {
    $result = mysqli_query($con, "DESCRIBE faculty");
    if ($result) {
        echo "✅ Faculty table structure:<br>";
        while ($row = mysqli_fetch_array($result)) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
        }
    } else {
        echo "❌ Error describing faculty table: " . mysqli_error($con) . "<br>";
    }
}

// Test 4: Check XAMPP status
echo "<h3>Test 4: XAMPP Status</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "MySQL Extension: " . (extension_loaded('mysqli') ? '✅ Loaded' : '❌ Not Loaded') . "<br>";
echo "Current Directory: " . getcwd() . "<br>";

// Test 5: Test a simple query
echo "<h3>Test 5: Simple Query Test</h3>";
if ($con) {
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM faculty");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "✅ Faculty count: " . $row['count'] . "<br>";
    } else {
        echo "❌ Error counting faculty: " . mysqli_error($con) . "<br>";
    }
}

echo "<hr>";
echo "<p><strong>If you see any ❌ errors above, those are the issues preventing your project from working.</strong></p>";
echo "<p><strong>Make sure XAMPP is running and MySQL service is started!</strong></p>";
?>
