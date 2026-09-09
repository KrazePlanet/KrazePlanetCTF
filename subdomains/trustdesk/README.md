# TrustDesk — PHP/MySQL Trust & Safety Portal

A real-world styled Trust & Safety reporting website built for XAMPP.

## Features
- Report content, users, profiles, comments, listings, sellers, reviews, spam and abuse
- Anonymous reporting
- Case number generation and public case tracking
- Severity and workflow statuses
- Customer-facing case updates
- Internal staff notes and case activity history
- Admin review queue with search/filter
- Automatic MySQL database/table creation
- Automatic default admin creation
- CSRF protection, prepared statements, password hashing and escaped output
- Responsive, completely distinct editorial/Trust & Safety UI

## XAMPP
1. Extract this folder into `C:\xampp\htdocs\trustdesk`
2. Start Apache and MySQL in XAMPP.
3. Visit `http://localhost/trustdesk/`
4. The first request creates the `trustdesk` database and all tables automatically.

## Admin
URL: `http://localhost/trustdesk/admin/login.php`
Email: `admin@trustdesk.local`
Password: `admin`

No SQL import is required.
