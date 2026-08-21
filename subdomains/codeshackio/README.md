# Basic Login & Registration System

This project implements a simple login and registration system with basic security using PHP and MySQL. It is designed to be beginner-friendly, providing a step-by-step guide to creating robust forms, handling user authentication, managing sessions, validating inputs, and interacting securely with a MySQL database.

This system is based on the tutorials "[Secure Login System with PHP and MySQL](https://codeshack.io/secure-login-system-php-mysql/)" and "[Secure Registration System with PHP and MySQL](https://codeshack.io/secure-registration-system-php-mysql/)".

## Screenshot

![Screenshot of Basic Login & Registration System](assets/screenshot.png)

## Features

* **User Registration:** Allows new users to create an account.
* **User Login:** Securely authenticates users against database records.
* **Password Hashing:** Uses `password_hash()` and `password_verify()` for secure password management.
* **Session Management:** Initializes sessions upon login and manages user state.
* **Page Protection:** Restricts access to certain pages (e.g., home, profile) to logged-in users only.
* **User Profile Page:** Displays basic account details for the logged-in user.
* **Logout Functionality:** Allows users to securely end their session.
* **Form Design:** Clean login (and registration) forms designed with HTML5 and CSS3.
* **Prepared SQL Queries:** Utilizes prepared statements to prevent SQL injection vulnerabilities.
* **Input Validation:** Basic server-side validation for form data.

## Requirements

* A web server environment (e.g., XAMPP, WAMP, MAMP, or a live server).
* PHP
* MySQL

XAMPP is recommended for local development as it includes PHP, MySQL, Apache, and phpMyAdmin.

## Database Setup

1.  **Create a Database:**
    * Open phpMyAdmin or your preferred MySQL management tool.
    * Create a new database. The article uses the name `codeshackio`.
    * Choose `utf8mb4_unicode_ci` as the collation.

2.  **Create `accounts` Table:**
    Execute the following SQL query in your `codeshackio` database:

    ```sql
    CREATE TABLE IF NOT EXISTS `accounts` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(50) NOT NULL,
      `password` varchar(255) NOT NULL,
      `email` varchar(100) NOT NULL,
      `registered` datetime NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ```

    The article also includes a test account. If you wish to add it:
    ```sql
    INSERT INTO `accounts` (`id`, `username`, `password`, `email`, `registered`) VALUES (1, 'test', '$2y$10$SfhYIDtn.iOuCW7zfoFLuuZHX6lja4lF4XA4JqNmpiH/.P3zB8JCa', 'test@example.com', '2025-01-01 00:00:00');
    -- Note: The password 'test' is hashed. The registration form should handle hashing for new users.
    ```

## Installation and Setup

1.  **Clone or Download:**
    Place the project files in your web server's document root (e.g., `htdocs/codeshackio` if using XAMPP).

2.  **Configure Database Connection:**
    Open the following PHP files and update the database connection variables to match your MySQL setup:
    * `authenticate.php`
    * `profile.php`
    * `register.php`


    ```php
    <?php
    // In authenticate.php, profile.php, etc.
    $DATABASE_HOST = 'localhost';
   $DATABASE_NAME = 'KrazePlanet'; // The database name you created
    $DATABASE_USER = 'your_mysql_username'; // e.g., 'root'
    $DATABASE_PASS = 'your_mysql_password'; // e.g., '' or your root password


    $con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
    if (mysqli_connect_errno()) {
        exit('Failed to connect to MySQL: ' . mysqli_connect_error());
    }
    // ... rest of the code
    ?>
    ```

3.  **Start Your Web Server:**
    Ensure Apache and MySQL services are running from your XAMPP control panel (or equivalent).

4.  **Access the Application:**
    Open your web browser and navigate to `http://localhost/codeshackio/` (or the appropriate path if you named the folder differently).

## Usage

* **Register:** Navigate to `register.php` (or click the "Register" link on the login page) to create a new account.
* **Login:** Go to `index.php` to log in with your username and password.
* **Home Page:** After successful login, you will be redirected to `home.php`.
* **Profile Page:** View your account details on `profile.php`.
* **Logout:** Click the "Logout" link to end your session.

## Security Considerations from the Article

The original article highlights several important security practices:

* Always use `htmlspecialchars()` when outputting user-provided data to prevent XSS.
* Use prepared statements for all SQL queries to prevent SQL injection.
* Hash passwords securely using `password_hash()` and verify them with `password_verify()`.
* Regenerate session IDs using `session_regenerate_id()` after login to help prevent session fixation.
* Consider secure session INI settings.
* Use HTTPS in a production environment.
* Configure error reporting appropriately for development (`error_reporting(E_ALL)`) versus production (`error_reporting(0)` and log errors to a file).
* Implement CSRF (Cross-Site Request Forgery) protection for forms.

## Credits

* This project is based on the tutorials "[Secure Login System with PHP and MySQL](https://codeshack.io/secure-login-system-php-mysql/)" and "[Secure Registration System with PHP and MySQL](https://codeshack.io/secure-registration-system-php-mysql/)" by David Adams at CodeShack.io.
* Icons used in the forms are from Font Awesome and Material Design Icons.

## Contact

David Adams - [info@codeshack.io](mailto:info@codeshack.io)

GitHub: [https://github.com/codeshackio/basic-php-login-registration-system](https://github.com/codeshackio/basic-php-login-registration-system)

X (Twitter): [https://twitter.com/codeshackio](https://twitter.com/codeshackio)
