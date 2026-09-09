# PartnerHub — PHP/MySQL Partner Application Portal

A real-world styled partner ecosystem website with a distinctive B2B/partner-program interface.

## Features
- Partner program landing page
- Partner types and program information
- Full partner application
- Company/contact/region/capability/experience/goals fields
- Automatic application number (PH-XXXXXXXX)
- Public application tracking
- Application workflow: Pending → Reviewing → More info → Approved / Declined
- Reviewer notes and activity history
- Admin search/filter dashboard
- Admin application detail and status management
- Automatic MySQL database and tables
- Automatic default admin creation
- CSRF protection, prepared statements, password hashing and escaped output
- Responsive design

## XAMPP
Extract to `C:\xampp\htdocs\partnerhub`, start Apache and MySQL, then open:
http://localhost/partnerhub/

The database `partnerhub` and all tables are created automatically on first request. No SQL import is needed.

## Admin
http://localhost/partnerhub/admin/login.php
Email: admin@partnerhub.local
Password: admin
