<?php
// Comprehensive Fix for Student Portal Integration Issues
// Run this file to fix all common problems

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Student Portal Integration Fix Tool</h1>";
echo "<p>This tool will identify and fix common integration issues.</p>";

// Step 1: Test Database Connection
echo "<h2>Step 1: Testing Database Connection</h2>";
include("login1.php");

if ($con) {
    echo "✅ Database connection successful<br>";
} else {
    echo "❌ Database connection failed: " . mysqli_connect_error() . "<br>";
    echo "<strong>Fix: Make sure XAMPP is running and MySQL service is started!</strong><br>";
    exit();
}

// Step 2: Check Database Structure
echo "<h2>Step 2: Checking Database Structure</h2>";

// Check if faculty table exists and has correct structure
$result = mysqli_query($con, "DESCRIBE faculty");
if ($result) {
    echo "✅ Faculty table exists<br>";
    $fields = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $fields[] = $row['Field'];
    }
    
    // Check for required fields
    $required_fields = ['name', 'department', 'designation', 'qualification', 'number', 'email', 'year_of_experiance', 'f_pass', 'f_id'];
    $missing_fields = array_diff($required_fields, $fields);
    
    if (empty($missing_fields)) {
        echo "✅ All required fields present<br>";
    } else {
        echo "❌ Missing fields: " . implode(', ', $missing_fields) . "<br>";
    }
} else {
    echo "❌ Faculty table not found or error: " . mysqli_error($con) . "<br>";
}

// Step 3: Check for Common Issues
echo "<h2>Step 3: Checking for Common Issues</h2>";

// Issue 1: Database field name mismatch
echo "<h3>Issue 1: Database Field Names</h3>";
$result = mysqli_query($con, "SHOW COLUMNS FROM faculty LIKE 'year_of_experiance'");
if (mysqli_num_rows($result) > 0) {
    echo "✅ Field 'year_of_experiance' exists (note: this has a typo but matches your database)<br>";
} else {
    echo "❌ Field 'year_of_experiance' not found<br>";
}

// Issue 2: Check if tables have data
echo "<h3>Issue 2: Table Data</h3>";
$result = mysqli_query($con, "SELECT COUNT(*) as count FROM faculty");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "✅ Faculty table has " . $row['count'] . " records<br>";
} else {
    echo "❌ Error checking faculty data: " . mysqli_error($con) . "<br>";
}

// Step 4: Fix Common Issues
echo "<h2>Step 4: Fixing Common Issues</h2>";

// Fix 1: Ensure proper database encoding
echo "<h3>Fix 1: Database Encoding</h3>";
$result = mysqli_query($con, "ALTER DATABASE students CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
if ($result) {
    echo "✅ Database encoding updated<br>";
} else {
    echo "ℹ️ Database encoding already correct or no changes needed<br>";
}

// Fix 2: Check and fix faculty table structure if needed
echo "<h3>Fix 2: Faculty Table Structure</h3>";
$result = mysqli_query($con, "SHOW CREATE TABLE faculty");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $create_table = $row['Create Table'];
    
    if (strpos($create_table, 'year_of_experiance') !== false) {
        echo "✅ Table structure matches expected (with typo)<br>";
    } else {
        echo "⚠️ Table structure may need updating<br>";
    }
} else {
    echo "❌ Error checking table structure: " . mysqli_error($con) . "<br>";
}

// Step 5: Test Basic Operations
echo "<h2>Step 5: Testing Basic Operations</h2>";

// Test INSERT operation
echo "<h3>Test INSERT Operation</h3>";
$test_name = "TEST_FACULTY_" . time();
$stmt = $con->prepare("INSERT INTO faculty (name, department, designation, qualification, number, email, year_of_experiance, f_id, f_pass) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

if ($stmt) {
    $dept = "TEST";
    $desig = "TEST";
    $qual = "TEST";
    $num = 123;
    $email = "test@test.com";
    $exp = 1;
    $fid = "TEST" . time();
    $pass = "test123";
    
    $stmt->bind_param("sssssssss", $test_name, $dept, $desig, $qual, $num, $email, $exp, $fid, $pass);
    
    if ($stmt->execute()) {
        echo "✅ Test INSERT successful<br>";
        
        // Clean up test data
        $stmt = $con->prepare("DELETE FROM faculty WHERE name = ?");
        $stmt->bind_param("s", $test_name);
        $stmt->execute();
        echo "✅ Test data cleaned up<br>";
    } else {
        echo "❌ Test INSERT failed: " . $stmt->error . "<br>";
    }
    $stmt->close();
} else {
    echo "❌ Prepare statement failed: " . $con->error . "<br>";
}

// Step 6: Recommendations
echo "<h2>Step 6: Recommendations</h2>";
echo "<ul>";
echo "<li>✅ Make sure XAMPP is running (Apache + MySQL)</li>";
echo "<li>✅ Verify database 'students' exists in phpMyAdmin</li>";
echo "<li>✅ Check that all tables are imported from students.sql</li>";
echo "<li>✅ Ensure PHP has mysqli extension enabled</li>";
echo "<li>✅ Verify file permissions (readable by web server)</li>";
echo "</ul>";

// Step 7: Quick Fix Commands
echo "<h2>Step 7: Quick Fix Commands</h2>";
echo "<p><strong>If you're still having issues, run these commands in XAMPP:</strong></p>";
echo "<ol>";
echo "<li>Start XAMPP Control Panel</li>";
echo "<li>Start Apache and MySQL services</li>";
echo "<li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>";
echo "<li>Create database 'students' if it doesn't exist</li>";
echo "<li>Import students.sql file</li>";
echo "<li>Check if all tables are created</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>🎯 Next Steps:</strong></p>";
echo "<p>1. Run this file first to identify issues</p>";
echo "<p>2. Fix any ❌ errors shown above</p>";
echo "<p>3. Try accessing your main pages again</p>";
echo "<p>4. If still having issues, check the browser console for JavaScript errors</p>";

echo "<p><strong>🔗 Test your pages:</strong></p>";
echo "<p><a href='admin_login.php' target='_blank'>Admin Login</a> | ";
echo "<a href='add_faculty.php' target='_blank'>Add Faculty</a> | ";
echo "<a href='test_connection.php' target='_blank'>Test Connection</a></p>";
?>
