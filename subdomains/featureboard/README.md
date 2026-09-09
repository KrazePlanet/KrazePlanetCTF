# FeatureBoard

A production-style PHP/MySQL customer feature-request board inspired by the public feedback/roadmap patterns used by products such as Canny and Productboard. It is an original implementation with a distinct editorial board UI.

## XAMPP
1. Extract the `featureboard` folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL in XAMPP.
3. Open `http://localhost/featureboard/`.
4. The MySQL database `featureboard` and all tables are created automatically on first load. No SQL import is required.

## Admin
URL: `http://localhost/featureboard/admin/login.php`
Email: `admin@featureboard.local`
Password: `admin`

## Working features
- Public feature request board
- Search and status filtering
- Submit feature requests
- Unique request slugs
- Community voting with one vote per email/request
- Request detail pages
- Discussion/comments
- Public roadmap: Under review, Planned, In progress, Shipped
- Admin dashboard with metrics
- Admin search/filter
- Admin request review
- Status changes and public team updates
- Delete requests
- Automatic DB/table/admin creation
- CSRF protection, prepared statements, password hashing, output escaping
