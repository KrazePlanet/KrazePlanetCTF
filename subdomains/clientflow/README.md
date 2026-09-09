# ClientFlow — PHP + MySQL

A polished account-management website with a feedback workflow and administrator dashboard.

## Included
- Create account, sign in and sign out
- Deactivate account
- Permanently delete account
- Automatic redirect to the feedback page after either action
- Feedback submission and MySQL storage
- Admin dashboard with search/filtering
- Account list and feedback details
- Automatic database/table creation
- CSRF protection, password hashing, prepared statements and output encoding
- Responsive SaaS-style UI

## Setup
1. Put the folder in `C:\xampp\htdocs\clientflow`
2. Start Apache and MySQL.
3. Open `http://localhost/clientflow/`

No SQL import or setup page is required.

## Admin
`http://localhost/clientflow/admin/login.php`

Email: `admin@clientflow.local`
Password: `admin`

Change the credentials in `config.php` before production deployment.
