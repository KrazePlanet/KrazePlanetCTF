# HireFlow — PHP/MySQL Recruiting Website

A production-style recruiting/careers website inspired by modern ATS patterns seen in products such as Greenhouse, Lever and Workable, but implemented as an original UI.

## Features
- Careers landing page
- Searchable/filterable jobs
- Job detail pages
- Candidate application form
- Resume upload (PDF/DOC/DOCX, max 5 MB)
- Unique application number
- Public candidate application tracking
- Candidate timeline
- Admin login/dashboard
- Candidate pipeline and status management
- Recruiter notes
- Job management and job creation
- Automatic MySQL database/table creation
- Seeded demo jobs
- CSRF protection, prepared statements, password hashing, escaped output

## XAMPP
1. Extract the `hireflow` folder to `C:\xampp\htdocs\hireflow`
2. Start Apache and MySQL.
3. Visit `http://localhost/hireflow/`
4. Database `hireflow` and all tables are created automatically.

## Admin
URL: `http://localhost/hireflow/admin/login.php`
Email: `admin@hireflow.local`
Password: `admin`

No database.sql is required.
