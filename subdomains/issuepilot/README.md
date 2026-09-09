# IssuePilot — PHP + MySQL Bug Reporting Website

IssuePilot is a polished bug-report intake website with a real working MySQL backend and administrator dashboard.

## Features
- Modern public landing page
- Dedicated **Submit bug** workflow
- Structured fields: title, description, reproduction steps, expected/actual behavior, severity, browser/device, URL and reporter contact
- Optional PNG/JPG/WEBP screenshot upload (5 MB max)
- Automatic MySQL database and table creation — no SQL import required
- Automatic default admin creation
- Admin login and dashboard
- Search and filter by status/severity
- Report detail view
- Admin status/severity updates and internal notes
- Delete reports
- CSRF protection, password hashing, prepared statements and escaped output
- Responsive UI

## XAMPP setup
1. Put the project folder in `C:\xampp\htdocs\issuepilot`
2. Start **Apache** and **MySQL** in XAMPP.
3. Open `http://localhost/issuepilot/`
4. The first request automatically creates the `issuepilot` database, tables and admin account.

## Default admin
- URL: `http://localhost/issuepilot/admin/login.php`
- Email: `admin@issuepilot.local`
- Password: `admin`

Change the credentials in `config.php` before using the site outside a local development environment.

## Design note
The interface is an original SaaS/issue-tracking design, using common modern patterns such as a focused intake form, status workflow, search/filtering and a dense admin table. It is not a copy of a specific third-party website.
