# Freelance PHP + MySQL Website — Automatic Setup

A premium freelance developer website with a MySQL-backed project inquiry form and protected admin CRM.

## Requirements

- PHP 8.2+
- MySQL 8+
- Apache/XAMPP
- PDO MySQL extension

## XAMPP setup

1. Copy this folder into `C:/xampp/htdocs/`.
2. Start Apache and MySQL in XAMPP.
3. Make sure `config/database.php` has the correct MySQL username/password.
4. Open `http://localhost/freelance-php-mysql/`.
5. On the first database connection, the application automatically creates the `freelance_portfolio` database, `admins` table and `inquiries` table.
6. The first admin account is automatically created as:

   Username: `admin`
   Password: `admin`

7. Admin login: `http://localhost/freelance-php-mysql/admin/login.php`

**There is no database.sql file and no admin setup page.**

## Customize

Edit `config/config.php` for your name, email, phone, location, social links, services, projects, technologies, testimonials and FAQs.

Edit `config/database.php` if your MySQL server uses a password or a different host/user.

## Important security note

The requested default credentials are intentionally `admin` / `admin`. For any public/production deployment, change the admin password and use a dedicated MySQL user instead of root. The password is stored in MySQL using `password_hash()`, not plaintext.

## Inquiry management

Client submissions are validated server-side and stored using PDO prepared statements. Admins can search, filter, view, update status and delete inquiries.

Statuses: new, read, contacted, closed.

## Production checklist

- Use HTTPS.
- Change the default admin password.
- Use a dedicated MySQL database user.
- Move secrets into environment/server configuration.
- Configure email notifications.
- Configure database backups.
- Keep Apache/PHP/MySQL updated.


### Change admin credentials
Edit `config/config.php` and change `ADMIN_EMAIL` and `ADMIN_PASSWORD` before deploying publicly. The default credentials are `admin@yourdomain.com` / `admin`.
