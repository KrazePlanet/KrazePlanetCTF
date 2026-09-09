# SupportHub — PHP + MySQL

A complete customer-support website with a modern help-center experience, support request workflow, ticket tracking, and administrator dashboard.

## Features
- Customer support/help-center homepage
- Submit support request
- Ticket number generated automatically
- Track request by ticket number or email
- Status workflow: Open, Pending, Resolved, Closed
- Priority: Low, Normal, High, Urgent
- Categories
- Customer-visible support update
- Admin search and filters
- Admin ticket detail/update screen
- Automatic MySQL database and table creation
- Automatic default admin creation
- CSRF protection, password hashing, prepared statements and escaped output

## XAMPP
1. Extract into `C:\xampp\htdocs\supporthub`
2. Start Apache and MySQL.
3. Open `http://localhost/supporthub/`
4. The database `supporthub` and required tables are created automatically.

## Admin
URL: `http://localhost/supporthub/admin/login.php`
Email: `admin@supporthub.local`
Password: `admin`

No SQL import or setup page is required.
