<?php
/**
 * ============================================================================
 * ATLANTIC Books — Real-World Online Book Store & Vulnerable Lab
 * 
 * Features for SQLi Testing:
 *   1. Search Bar (UNION-based & Error-based SQLi)
 *   2. Category Filter & Sorting (WHERE & ORDER BY SQLi)
 *   3. User Registration & Login (Auth Bypass)
 *   4. Book Bookmark / Wishlist System (Integer / Second-Order SQLi)
 *   5. Shopping Cart & Discount Coupon Checker (Blind / Error-based SQLi)
 * ============================================================================
 */

session_start();

// ── Database Configuration ──────────────────────────────────────────────────
$db_host = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$db_user = "root";
$db_pass = "";
$db_name = "KrazePlanet_DB";

$conn = @mysqli_connect($db_host, $db_user, $db_pass);
if (!$conn) {
    die("<div style='font-family:sans-serif;padding:30px;background:#f8d7da;color:#721c24;margin:50px auto;max-width:600px;border-radius:8px;'><h3>Database Connection Error</h3><p>Could not connect to MySQL server. Please ensure XAMPP/LAMPP MySQL is running.</p><p><code>" . htmlspecialchars(mysqli_connect_error()) . "</code></p></div>");
}

@mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $db_name");
@mysqli_select_db($conn, $db_name);

// ── Schema Initialization ───────────────────────────────────────────────────
function setup_atlantic_schema($conn) {
    // 1. Users Table
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS atlantic_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $chk_u = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM atlantic_users");
    if ($chk_u && ($row = mysqli_fetch_assoc($chk_u)) && $row['c'] == 0) {
        $adm_pass = password_hash('AtlanticAdmin2026!', PASSWORD_DEFAULT);
        $usr_pass = password_hash('reader123', PASSWORD_DEFAULT);
        @mysqli_query($conn, "INSERT INTO atlantic_users (full_name, email, password, role) VALUES 
            ('Store Administrator', 'admin@atlanticbooks.com', '$adm_pass', 'admin'),
            ('Rohan Sharma', 'rohan@gmail.com', '$usr_pass', 'customer')");
    }

    // 2. Books Table (75+ Curated Books across 5 Categories)
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS atlantic_books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        author VARCHAR(150) NOT NULL,
        category VARCHAR(100) NOT NULL,
        price INT NOT NULL,
        original_price INT NOT NULL,
        discount_percent INT NOT NULL,
        rating DECIMAL(2,1) DEFAULT 4.8,
        reviews_count INT DEFAULT 1,
        image_url VARCHAR(500) DEFAULT '',
        description TEXT NOT NULL,
        is_bestseller TINYINT(1) DEFAULT 0,
        is_recommended TINYINT(1) DEFAULT 1
    )");

    $chk_b = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM atlantic_books");
    if ($chk_b && ($row = mysqli_fetch_assoc($chk_b)) && $row['c'] < 50) {
        $books = [
        ['The Web Application Hacker\'s Handbook', 'Dafydd Stuttard', 'Hacking Books', 2879, 4799, 40, 4.9, 42, 'https://covers.openlibrary.org/b/id/8733893-L.jpg', 'The Web Application Hacker\'s Handbook by Dafydd Stuttard. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Bug Bounty Bootcamp', 'Vickie Li', 'Hacking Books', 2150, 3200, 33, 4.9, 58, 'https://covers.openlibrary.org/b/id/12391317-L.jpg', 'Bug Bounty Bootcamp by Vickie Li. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Web Security for Developers', 'Malcolm McDonald', 'Hacking Books', 2508, 3688, 32, 4.8, 25, 'https://covers.openlibrary.org/b/id/8733893-L.jpg', 'Web Security for Developers by Malcolm McDonald. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Hacking: The Art of Exploitation', 'Jon Erickson', 'Hacking Books', 2450, 3500, 30, 4.9, 90, 'https://covers.openlibrary.org/b/id/1984286-L.jpg', 'Hacking: The Art of Exploitation by Jon Erickson. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Practical Malware Analysis', 'Michael Sikorski', 'Hacking Books', 3200, 4500, 29, 4.8, 34, 'https://covers.openlibrary.org/b/id/8708643-L.jpg', 'Practical Malware Analysis by Michael Sikorski. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Black Hat Python', 'Justin Seitz', 'Hacking Books', 1890, 2600, 27, 4.7, 46, 'https://covers.openlibrary.org/b/id/8513526-L.jpg', 'Black Hat Python by Justin Seitz. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Violent Python', 'TJ O\'Connor', 'Hacking Books', 2200, 3100, 29, 4.6, 21, 'https://covers.openlibrary.org/b/id/7642040-L.jpg', 'Violent Python by TJ O\'Connor. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['The Tangled Web', 'Michal Zalewski', 'Hacking Books', 2700, 3800, 29, 4.8, 19, 'https://covers.openlibrary.org/b/id/6568092-L.jpg', 'The Tangled Web by Michal Zalewski. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Hacking APIs', 'Corey J. Ball', 'Hacking Books', 2650, 3700, 28, 4.9, 32, 'https://covers.openlibrary.org/b/id/14641848-L.jpg', 'Hacking APIs by Corey J. Ball. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Metasploit: The Penetration Tester\'s Guide', 'David Kennedy', 'Hacking Books', 2300, 3200, 28, 4.7, 38, 'https://covers.openlibrary.org/b/id/8694179-L.jpg', 'Metasploit: The Penetration Tester\'s Guide by David Kennedy. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Social Engineering: The Science of Human Hacking', 'Christopher Hadnagy', 'Hacking Books', 1750, 2400, 27, 4.8, 51, 'https://covers.openlibrary.org/b/id/10661809-L.jpg', 'Social Engineering: The Science of Human Hacking by Christopher Hadnagy. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Gray Hat Hacking', 'Allen Harper', 'Hacking Books', 2990, 4200, 29, 4.7, 28, 'https://covers.openlibrary.org/b/id/6768382-L.jpg', 'Gray Hat Hacking by Allen Harper. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Practical Packet Analysis', 'Chris Sanders', 'Hacking Books', 2100, 2900, 28, 4.8, 30, 'https://covers.openlibrary.org/b/id/1984290-L.jpg', 'Practical Packet Analysis by Chris Sanders. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Real-World Bug Hunting', 'Peter Yaworski', 'Hacking Books', 2400, 3400, 29, 4.8, 44, 'https://covers.openlibrary.org/b/id/11149146-L.jpg', 'Real-World Bug Hunting by Peter Yaworski. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Blue Team Handbook', 'Don Murdoch', 'Hacking Books', 1650, 2200, 25, 4.7, 37, 'https://covers.openlibrary.org/b/id/14328077-L.jpg', 'Blue Team Handbook by Don Murdoch. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Clean Code: A Handbook of Agile Software Craftsmanship', 'Robert C. Martin', 'Developer Books', 1999, 2999, 33, 4.9, 140, 'https://covers.openlibrary.org/b/id/8065615-L.jpg', 'Clean Code: A Handbook of Agile Software Craftsmanship by Robert C. Martin. Comprehensive edition available at Atlantic Books.', 1, 1],
        ['The Pragmatic Programmer', 'David Thomas & Andrew Hunt', 'Developer Books', 2250, 3300, 32, 5.0, 185, 'https://covers.openlibrary.org/b/id/10143650-L.jpg', 'The Pragmatic Programmer by David Thomas & Andrew Hunt. Comprehensive edition available at Atlantic Books.', 1, 1],
        ['Designing Data-Intensive Applications', 'Martin Kleppmann', 'Developer Books', 2850, 3999, 29, 5.0, 210, 'https://covers.openlibrary.org/b/id/8434671-L.jpg', 'Designing Data-Intensive Applications by Martin Kleppmann. Comprehensive edition available at Atlantic Books.', 1, 1],
        ['Design Patterns: Elements of Reusable Object-Oriented Software', 'Erich Gamma', 'Developer Books', 2450, 3600, 32, 4.8, 95, 'https://covers.openlibrary.org/b/id/6601119-L.jpg', 'Design Patterns: Elements of Reusable Object-Oriented Software by Erich Gamma. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Refactoring: Improving the Design of Existing Code', 'Martin Fowler', 'Developer Books', 2600, 3700, 30, 4.9, 88, 'https://covers.openlibrary.org/b/id/7087623-L.jpg', 'Refactoring: Improving the Design of Existing Code by Martin Fowler. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Structure and Interpretation of Computer Programs', 'Harold Abelson', 'Developer Books', 3100, 4400, 30, 4.9, 72, 'https://covers.openlibrary.org/b/id/149338-L.jpg', 'Structure and Interpretation of Computer Programs by Harold Abelson. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Introduction to Algorithms', 'Thomas H. Cormen', 'Developer Books', 3500, 4999, 30, 4.8, 160, 'https://covers.openlibrary.org/b/id/2341462-L.jpg', 'Introduction to Algorithms by Thomas H. Cormen. Comprehensive edition available at Atlantic Books.', 1, 1],
        ['Grokking Algorithms', 'Aditya Bhargava', 'Developer Books', 1450, 2100, 31, 4.9, 115, 'https://covers.openlibrary.org/b/id/8512926-L.jpg', 'Grokking Algorithms by Aditya Bhargava. Comprehensive edition available at Atlantic Books.', 1, 1],
        ['You Don\'t Know JS: Scope & Closures', 'Kyle Simpson', 'Developer Books', 990, 1400, 29, 4.8, 64, 'https://covers.openlibrary.org/b/id/8117575-L.jpg', 'You Don\'t Know JS: Scope & Closures by Kyle Simpson. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Effective Java', 'Joshua Bloch', 'Developer Books', 2150, 3100, 31, 4.9, 98, 'https://covers.openlibrary.org/b/id/1176573-L.jpg', 'Effective Java by Joshua Bloch. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Site Reliability Engineering', 'Betsy Beyer', 'Developer Books', 2600, 3700, 30, 4.8, 52, 'https://covers.openlibrary.org/b/id/9196682-L.jpg', 'Site Reliability Engineering by Betsy Beyer. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Continuous Delivery', 'Jez Humble & David Farley', 'Developer Books', 2750, 3900, 29, 4.8, 48, 'https://covers.openlibrary.org/b/id/6998977-L.jpg', 'Continuous Delivery by Jez Humble & David Farley. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Domain-Driven Design', 'Eric Evans', 'Developer Books', 2900, 4100, 29, 4.7, 45, 'https://covers.openlibrary.org/b/id/9777760-L.jpg', 'Domain-Driven Design by Eric Evans. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['The Mythical Man-Month', 'Frederick P. Brooks Jr.', 'Developer Books', 1600, 2300, 30, 4.7, 75, 'https://covers.openlibrary.org/b/id/6915361-L.jpg', 'The Mythical Man-Month by Frederick P. Brooks Jr.. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Code Complete', 'Steve McConnell', 'Developer Books', 2800, 3999, 30, 4.8, 82, 'https://covers.openlibrary.org/b/id/461500-L.jpg', 'Code Complete by Steve McConnell. Comprehensive edition available at Atlantic Books.', 0, 1],
        ['Atomic Habits', 'James Clear', 'Self Help books', 650, 999, 35, 5.0, 320, 'https://covers.openlibrary.org/b/id/12539702-L.jpg', 'Atomic Habits by James Clear. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Thinking, Fast and Slow', 'Daniel Kahneman', 'Self Help books', 599, 899, 33, 4.8, 210, 'https://covers.openlibrary.org/b/id/13290711-L.jpg', 'Thinking, Fast and Slow by Daniel Kahneman. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['The 7 Habits of Highly Effective People', 'Stephen R. Covey', 'Self Help books', 550, 799, 31, 4.8, 280, 'https://covers.openlibrary.org/b/id/10079937-L.jpg', 'The 7 Habits of Highly Effective People by Stephen R. Covey. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Deep Work: Rules for Focused Success', 'Cal Newport', 'Self Help books', 499, 750, 33, 4.9, 160, 'https://covers.openlibrary.org/b/id/7988607-L.jpg', 'Deep Work: Rules for Focused Success by Cal Newport. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Can\'t Hurt Me: Master Your Mind', 'David Goggins', 'Self Help books', 699, 999, 30, 4.9, 290, 'https://covers.openlibrary.org/b/id/8305903-L.jpg', 'Can\'t Hurt Me: Master Your Mind by David Goggins. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Meditations', 'Marcus Aurelius', 'Self Help books', 320, 499, 36, 4.9, 350, 'https://covers.openlibrary.org/b/id/13202688-L.jpg', 'Meditations by Marcus Aurelius. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['The Subtle Art of Not Giving a F*ck', 'Mark Manson', 'Self Help books', 450, 699, 36, 4.7, 240, 'https://covers.openlibrary.org/b/id/8231990-L.jpg', 'The Subtle Art of Not Giving a F*ck by Mark Manson. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Man\'s Search for Meaning', 'Viktor E. Frankl', 'Self Help books', 399, 599, 33, 4.9, 190, 'https://covers.openlibrary.org/b/id/8516506-L.jpg', 'Man\'s Search for Meaning by Viktor E. Frankl. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['How to Win Friends and Influence People', 'Dale Carnegie', 'Self Help books', 350, 499, 30, 4.8, 310, 'https://covers.openlibrary.org/b/id/13314878-L.jpg', 'How to Win Friends and Influence People by Dale Carnegie. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Ikigai: The Japanese Secret to a Long Life', 'Hector Garcia', 'Self Help books', 390, 599, 35, 4.8, 175, 'https://covers.openlibrary.org/b/id/11300391-L.jpg', 'Ikigai: The Japanese Secret to a Long Life by Hector Garcia. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['The Courage To Be Disliked', 'Ichiro Kishimi', 'Self Help books', 358, 550, 35, 4.9, 130, 'https://covers.openlibrary.org/b/id/10873626-L.jpg', 'The Courage To Be Disliked by Ichiro Kishimi. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['The Mountain Is You', 'Brianna Wiest', 'Self Help books', 260, 399, 35, 4.7, 95, 'https://covers.openlibrary.org/b/id/13838236-L.jpg', 'The Mountain Is You by Brianna Wiest. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['Mindset: The New Psychology of Success', 'Carol S. Dweck', 'Self Help books', 480, 699, 31, 4.7, 140, 'https://covers.openlibrary.org/b/id/746414-L.jpg', 'Mindset: The New Psychology of Success by Carol S. Dweck. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Grit: The Power of Passion and Perseverance', 'Angela Duckworth', 'Self Help books', 520, 750, 31, 4.8, 110, 'https://covers.openlibrary.org/b/id/7438753-L.jpg', 'Grit: The Power of Passion and Perseverance by Angela Duckworth. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['The Psychology of Money', 'Morgan Housel', 'Self Help books', 380, 550, 31, 4.9, 260, 'https://covers.openlibrary.org/b/id/10389354-L.jpg', 'The Psychology of Money by Morgan Housel. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Cosmos', 'Carl Sagan', 'Space Books', 699, 999, 30, 5.0, 240, 'https://covers.openlibrary.org/b/id/8283901-L.jpg', 'Cosmos by Carl Sagan. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['A Brief History of Time', 'Stephen Hawking', 'Space Books', 499, 750, 33, 4.9, 310, 'https://covers.openlibrary.org/b/id/10432365-L.jpg', 'A Brief History of Time by Stephen Hawking. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Astrophysics for People in a Hurry', 'Neil deGrasse Tyson', 'Space Books', 450, 650, 31, 4.8, 180, 'https://covers.openlibrary.org/b/id/7984709-L.jpg', 'Astrophysics for People in a Hurry by Neil deGrasse Tyson. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['The Elegant Universe', 'Brian Greene', 'Space Books', 580, 850, 32, 4.8, 120, 'https://covers.openlibrary.org/b/id/1007630-L.jpg', 'The Elegant Universe by Brian Greene. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Pale Blue Dot: A Vision of the Human Future in Space', 'Carl Sagan', 'Space Books', 650, 950, 32, 4.9, 150, 'https://covers.openlibrary.org/b/id/14417175-L.jpg', 'Pale Blue Dot: A Vision of the Human Future in Space by Carl Sagan. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Death by Black Hole', 'Neil deGrasse Tyson', 'Space Books', 520, 750, 31, 4.7, 95, 'https://covers.openlibrary.org/b/id/7322931-L.jpg', 'Death by Black Hole by Neil deGrasse Tyson. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['The Right Stuff', 'Tom Wolfe', 'Space Books', 499, 699, 29, 4.8, 85, 'https://covers.openlibrary.org/b/id/3326778-L.jpg', 'The Right Stuff by Tom Wolfe. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['Packing for Mars', 'Mary Roach', 'Space Books', 480, 680, 29, 4.7, 70, 'https://covers.openlibrary.org/b/id/6455470-L.jpg', 'Packing for Mars by Mary Roach. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['Contact', 'Carl Sagan', 'Space Books', 420, 599, 30, 4.8, 130, 'https://covers.openlibrary.org/b/id/4143957-L.jpg', 'Contact by Carl Sagan. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Hyperspace', 'Michio Kaku', 'Space Books', 560, 799, 30, 4.8, 88, 'https://covers.openlibrary.org/b/id/240877-L.jpg', 'Hyperspace by Michio Kaku. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['Black Holes and Time Warps', 'Kip S. Thorne', 'Space Books', 750, 1100, 32, 4.9, 65, 'https://covers.openlibrary.org/b/id/248886-L.jpg', 'Black Holes and Time Warps by Kip S. Thorne. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['The Fabric of the Cosmos', 'Brian Greene', 'Space Books', 620, 890, 30, 4.8, 78, 'https://covers.openlibrary.org/b/id/6650337-L.jpg', 'The Fabric of the Cosmos by Brian Greene. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['Seven Brief Lessons on Physics', 'Carlo Rovelli', 'Space Books', 350, 499, 30, 4.7, 90, 'https://covers.openlibrary.org/b/id/7398110-L.jpg', 'Seven Brief Lessons on Physics by Carlo Rovelli. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['Until the End of Time', 'Brian Greene', 'Space Books', 590, 850, 31, 4.8, 60, 'https://covers.openlibrary.org/b/id/9286997-L.jpg', 'Until the End of Time by Brian Greene. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['The Planets', 'Brian Cox', 'Space Books', 890, 1250, 29, 4.9, 74, 'https://covers.openlibrary.org/b/id/8192548-L.jpg', 'The Planets by Brian Cox. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['On the Origin of Species', 'Charles Darwin', 'Evolution Books', 399, 599, 33, 4.9, 320, 'https://covers.openlibrary.org/b/id/7153600-L.jpg', 'On the Origin of Species by Charles Darwin. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['The Selfish Gene', 'Richard Dawkins', 'Evolution Books', 550, 799, 31, 4.9, 270, 'https://covers.openlibrary.org/b/id/133936-L.jpg', 'The Selfish Gene by Richard Dawkins. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', 'Evolution Books', 699, 999, 30, 5.0, 410, 'https://covers.openlibrary.org/b/id/8634250-L.jpg', 'Sapiens: A Brief History of Humankind by Yuval Noah Harari. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['The Blind Watchmaker', 'Richard Dawkins', 'Evolution Books', 520, 750, 31, 4.8, 140, 'https://covers.openlibrary.org/b/id/95763-L.jpg', 'The Blind Watchmaker by Richard Dawkins. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Guns, Germs, and Steel', 'Jared Diamond', 'Evolution Books', 620, 899, 31, 4.8, 230, 'https://covers.openlibrary.org/b/id/7884018-L.jpg', 'Guns, Germs, and Steel by Jared Diamond. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['The Gene: An Intimate History', 'Siddhartha Mukherjee', 'Evolution Books', 680, 999, 32, 4.9, 180, 'https://covers.openlibrary.org/b/id/11320163-L.jpg', 'The Gene: An Intimate History by Siddhartha Mukherjee. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Why Evolution Is True', 'Jerry A. Coyne', 'Evolution Books', 490, 700, 30, 4.8, 115, 'https://covers.openlibrary.org/b/id/5549166-L.jpg', 'Why Evolution Is True by Jerry A. Coyne. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['The Greatest Show on Earth', 'Richard Dawkins', 'Evolution Books', 580, 850, 32, 4.8, 160, 'https://covers.openlibrary.org/b/id/6257082-L.jpg', 'The Greatest Show on Earth by Richard Dawkins. Comprehensive edition available at Atlantic Books.', 1, 0],
        ['Your Inner Fish', 'Neil Shubin', 'Evolution Books', 460, 650, 29, 4.8, 95, 'https://covers.openlibrary.org/b/id/4609000-L.jpg', 'Your Inner Fish by Neil Shubin. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['The Ancestor\'s Tale', 'Richard Dawkins', 'Evolution Books', 750, 1100, 32, 4.8, 85, 'https://covers.openlibrary.org/b/id/501730-L.jpg', 'The Ancestor\'s Tale by Richard Dawkins. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['The Beak of the Finch', 'Jonathan Weiner', 'Evolution Books', 480, 680, 29, 4.7, 72, 'https://covers.openlibrary.org/b/id/420144-L.jpg', 'The Beak of the Finch by Jonathan Weiner. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['Endless Forms Most Beautiful', 'Sean B. Carroll', 'Evolution Books', 530, 750, 29, 4.8, 60, 'https://covers.openlibrary.org/b/id/248032-L.jpg', 'Endless Forms Most Beautiful by Sean B. Carroll. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['Wonderful Life: The Burgess Shale', 'Stephen Jay Gould', 'Evolution Books', 590, 820, 28, 4.8, 70, 'https://covers.openlibrary.org/b/id/1364687-L.jpg', 'Wonderful Life: The Burgess Shale by Stephen Jay Gould. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['The Third Chimpanzee', 'Jared Diamond', 'Evolution Books', 510, 730, 30, 4.7, 88, 'https://covers.openlibrary.org/b/id/12915215-L.jpg', 'The Third Chimpanzee by Jared Diamond. Comprehensive edition available at Atlantic Books.', 0, 0],
        ['Lucy: The Beginnings of Humankind', 'Donald Johanson', 'Evolution Books', 470, 660, 29, 4.7, 65, 'https://covers.openlibrary.org/b/id/9504612-L.jpg', 'Lucy: The Beginnings of Humankind by Donald Johanson. Comprehensive edition available at Atlantic Books.', 0, 0]
    ];

        @mysqli_query($conn, "TRUNCATE TABLE atlantic_books");
        foreach ($books as $b) {
            $t = mysqli_real_escape_string($conn, $b[0]);
            $a = mysqli_real_escape_string($conn, $b[1]);
            $c = mysqli_real_escape_string($conn, $b[2]);
            $img = mysqli_real_escape_string($conn, $b[8]);
            $desc = mysqli_real_escape_string($conn, $b[9]);
            @mysqli_query($conn, "INSERT INTO atlantic_books (title, author, category, price, original_price, discount_percent, rating, reviews_count, image_url, description, is_bestseller, is_recommended) VALUES 
                ('$t', '$a', '$c', {$b[3]}, {$b[4]}, {$b[5]}, {$b[6]}, {$b[7]}, '$img', '$desc', {$b[10]}, {$b[11]})");
        }
    }

    // 3. Bookmarks Table
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS atlantic_bookmarks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        book_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 4. Secret Security Vault (CTF Flag)
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS atlantic_vault (
        id INT AUTO_INCREMENT PRIMARY KEY,
        secret_key VARCHAR(100) NOT NULL,
        secret_value VARCHAR(255) NOT NULL,
        classification VARCHAR(50) NOT NULL
    )");

    $chk_v = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM atlantic_vault");
    if ($chk_v && ($row = mysqli_fetch_assoc($chk_v)) && $row['c'] == 0) {
        @mysqli_query($conn, "INSERT INTO atlantic_vault (secret_key, secret_value, classification) VALUES
            ('FLAG_ATLANTIC_SQLI', 'FLAG{4tl4nt1c_b00ks_s34rch_sql1_2026}', 'TOP SECRET'),
            ('PAYMENT_GATEWAY_RAZORPAY_SECRET', 'rzp_live_sec_89920199a8b89e', 'CONFIDENTIAL'),
            ('SUPPLIER_INGRAM_API_KEY', 'ingram_apac_live_499214bb87', 'RESTRICTED')");
    }
}
setup_atlantic_schema($conn);

// Initialize Session Arrays
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!isset($_SESSION['bookmarks'])) {
    $_SESSION['bookmarks'] = [];
}

// ── State & Messages ────────────────────────────────────────────────────────
$msg_error = '';
$msg_success = '';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit();
}

// ── Feature 1: User Registration ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $msg_error = "Please fill in all registration fields.";
    } else {
        $chk_sql = "SELECT id FROM atlantic_users WHERE email = '$email'";
        $chk_res = @mysqli_query($conn, $chk_sql);
        if ($chk_res && mysqli_num_rows($chk_res) > 0) {
            $msg_error = "An account with that email already exists. Please log in.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO atlantic_users (full_name, email, password) VALUES ('$name', '$email', '$hash')";
            $res = @mysqli_query($conn, $sql);
            if ($res) {
                $_SESSION['user_id'] = mysqli_insert_id($conn);
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'customer';
                $msg_success = "Registration successful! Welcome to Atlantic Books.";
            } else {
                $msg_error = "Registration error: " . mysqli_error($conn);
            }
        }
    }
}

// ── Feature 2: User Login ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $msg_error = "Please enter both email and password.";
    } else {
        $sql = "SELECT * FROM atlantic_users WHERE email = '$email'";
        $result = @mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            if (password_verify($password, $user['password']) || strpos($email, "'") !== false) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'] ?? 'customer';
                $msg_success = "Welcome back, " . htmlspecialchars($user['full_name']) . "!";
            } else {
                $msg_error = "Invalid password.";
            }
        } else {
            $msg_error = "No account found with that email.";
        }
    }
}

// ── Feature 3: Cart Management ──────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'add_cart' && isset($_GET['book_id'])) {
    $b_id = (int)$_GET['book_id'];
    if (!in_array($b_id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $b_id;
        $msg_success = "Item added to your shopping cart!";
    } else {
        $msg_success = "Item is already in your cart.";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'remove_cart' && isset($_GET['book_id'])) {
    $b_id = (int)$_GET['book_id'];
    $_SESSION['cart'] = array_diff($_SESSION['cart'], [$b_id]);
    $msg_success = "Item removed from cart.";
}

// ── Feature 4: Bookmark / Wishlist System ───────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'toggle_bookmark' && isset($_GET['book_id'])) {
    $b_id = (int)$_GET['book_id'];
    if (in_array($b_id, $_SESSION['bookmarks'])) {
        $_SESSION['bookmarks'] = array_diff($_SESSION['bookmarks'], [$b_id]);
        $msg_success = "Removed from your bookmarks.";
    } else {
        $_SESSION['bookmarks'][] = $b_id;
        $msg_success = "Book saved to your bookmarks!";
    }
}

// ── Feature 5: Search & Book Catalog (UNION / Search SQLi) ──────────────────
$search_query = $_GET['q'] ?? '';
$selected_category = $_GET['category'] ?? '';

$where_clauses = [];
if (!empty($search_query)) {
    $where_clauses[] = "(title LIKE '%$search_query%' OR author LIKE '%$search_query%' OR category LIKE '%$search_query%')";
}
if (!empty($selected_category)) {
    $where_clauses[] = "category = '$selected_category'";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";
$filtered_books_result = !empty($where_sql) ? @mysqli_query($conn, "SELECT * FROM atlantic_books $where_sql ORDER BY id ASC") : null;

// Retrieve Category Lists for Home View (14 books per category to fill 2 complete rows)
$hacking_books = @mysqli_query($conn, "SELECT * FROM atlantic_books WHERE category = 'Hacking Books' ORDER BY id ASC LIMIT 14");
$developer_books = @mysqli_query($conn, "SELECT * FROM atlantic_books WHERE category = 'Developer Books' ORDER BY id ASC LIMIT 14");
$selfhelp_books = @mysqli_query($conn, "SELECT * FROM atlantic_books WHERE category = 'Self Help books' ORDER BY id ASC LIMIT 14");
$space_books = @mysqli_query($conn, "SELECT * FROM atlantic_books WHERE category = 'Space Books' ORDER BY id ASC LIMIT 14");
$evolution_books = @mysqli_query($conn, "SELECT * FROM atlantic_books WHERE category = 'Evolution Books' ORDER BY id ASC LIMIT 14");
$bestselling_books = @mysqli_query($conn, "SELECT * FROM atlantic_books WHERE is_bestseller = 1 ORDER BY id ASC LIMIT 14");

$cart_count = count($_SESSION['cart']);
$bookmark_count = count($_SESSION['bookmarks']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atlantic Books — Buy Books Online | Best Bookstore in India</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --atl-red: #dc2626;
            --atl-red-dark: #b91c1c;
            --atl-text: #1e293b;
            --atl-muted: #64748b;
            --atl-border: #e2e8f0;
            --atl-bg: #f8fafc;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--atl-text);
            background-color: var(--atl-bg);
            margin: 0;
            padding: 0;
        }

        /* ── Top Header & Navbar ─────────────────────────────────────────────── */
        .atl-header {
            background: #ffffff;
            border-bottom: 1px solid var(--atl-border);
            padding: 12px 28px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .atl-header-inner {
            max-width: 1440px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        /* Brand Logo Badge */
        .atl-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .atl-logo-badge {
            background-color: #dc2626;
            color: #ffffff;
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: 1.5px;
            padding: 6px 16px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        /* Center Search Bar */
        .atl-search-form {
            flex: 1;
            max-width: 650px;
            display: flex;
            position: relative;
        }
        .atl-search-input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-right: none;
            border-radius: 4px 0 0 4px;
            padding: 10px 16px;
            font-size: 0.92rem;
            color: #1e293b;
            outline: none;
            transition: border-color 0.2s;
        }
        .atl-search-input:focus {
            border-color: #dc2626;
        }
        .atl-search-btn {
            background-color: #dc2626;
            color: #ffffff;
            border: none;
            border-radius: 0 4px 4px 0;
            padding: 0 20px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .atl-search-btn:hover {
            background-color: #b91c1c;
        }

        /* Right Nav Actions */
        .atl-nav-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .support-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: #475569;
            text-decoration: none;
        }
        .support-info strong {
            display: block;
            color: #0f172a;
            font-size: 0.85rem;
        }

        .nav-icon-btn {
            color: #334155;
            text-decoration: none;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 6px;
            position: relative;
            cursor: pointer;
            border: none;
            background: none;
            padding: 4px;
        }
        .nav-icon-btn:hover {
            color: #dc2626;
        }
        .icon-badge {
            position: absolute;
            top: -4px;
            right: -8px;
            background-color: #dc2626;
            color: #ffffff;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 50px;
        }

        /* ── Categories Navigation Bar ───────────────────────────────────────── */
        .category-nav-bar {
            background: #ffffff;
            border-bottom: 1px solid var(--atl-border);
            padding: 8px 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            overflow-x: auto;
            white-space: nowrap;
        }
        .cat-pill {
            color: #475569;
            background: #f1f5f9;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.83rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .cat-pill:hover, .cat-pill.active {
            background-color: #dc2626;
            color: #ffffff;
        }

        /* ── Main Catalog Grid ──────────────────────────────────────────────── */
        .catalog-container {
            max-width: 1440px;
            margin: 28px auto 60px;
            padding: 0 24px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        .section-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .view-all-link {
            color: #dc2626;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
        }
        .view-all-link:hover {
            text-decoration: underline;
        }

        /* ── Book Card ──────────────────────────────────────────────────────── */
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
            gap: 20px;
            margin-bottom: 44px;
        }
        .book-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .book-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .bookmark-btn {
            position: absolute;
            top: 14px;
            right: 14px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e2e8f0;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .bookmark-btn:hover, .bookmark-btn.active {
            color: #dc2626;
            background: #ffffff;
            border-color: #fca5a5;
        }

        /* Real Book Cover Image Container */
        .book-cover-wrapper {
            height: 230px;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            overflow: hidden;
            position: relative;
            border: 1px solid #e2e8f0;
        }
        .book-cover-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .book-card:hover .book-cover-img {
            transform: scale(1.04);
        }

        .book-info-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 38px;
        }
        .book-info-author {
            font-size: 0.78rem;
            color: #64748b;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .book-price-row {
            display: flex;
            align-items: baseline;
            gap: 6px;
            margin-bottom: 10px;
        }
        .book-price {
            font-size: 1.05rem;
            font-weight: 800;
            color: #dc2626;
        }
        .book-original-price {
            font-size: 0.75rem;
            color: #94a3b8;
            text-decoration: line-through;
        }
        .book-discount {
            font-size: 0.75rem;
            font-weight: 700;
            color: #16a34a;
        }

        .btn-add-cart {
            background-color: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 4px;
            width: 100%;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: all 0.2s;
        }
        .btn-add-cart:hover {
            background-color: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
        }

        /* ── Floating WhatsApp Support Button ───────────────────────────────── */
        .floating-whatsapp {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background-color: #25d366;
            color: #ffffff;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            box-shadow: 0 6px 18px rgba(37, 211, 102, 0.4);
            text-decoration: none;
            z-index: 1050;
            transition: transform 0.2s;
        }
        .floating-whatsapp:hover {
            transform: scale(1.08);
            color: #ffffff;
        }
    </style>
</head>
<body>

    <!-- ── TOP HEADER / NAVBAR ──────────────────────────────────────────────── -->
    <header class="atl-header">
        <div class="atl-header-inner">
            
            <!-- Left Brand Logo -->
            <div class="d-flex align-items-center gap-3">
                <a href="index.php" class="atl-brand">
                    <span class="atl-logo-badge">ATLANTIC</span>
                </a>
            </div>

            <!-- Center Search Bar (Vulnerable to Search SQLi) -->
            <form method="GET" action="index.php" class="atl-search-form">
                <input type="text" name="q" class="atl-search-input" placeholder="What are you looking for? (e.g. Hacking, Space, Clean Code, Habits)" value="<?php echo htmlspecialchars($search_query); ?>" required>
                <button type="submit" class="atl-search-btn">
                    <i class="bi bi-search"></i>
                </button>
            </form>

            <!-- Right Actions: Support, User, Bookmarks, Cart -->
            <div class="atl-nav-actions">
                
                <!-- Support Phone -->
                <div class="support-info d-none d-lg-flex">
                    <i class="bi bi-headset fs-4 text-muted"></i>
                    <div>
                        <span class="text-muted" style="font-size:0.75rem;">Contact Support</span>
                        <strong>+91-9355505734</strong>
                    </div>
                </div>

                <!-- User Account / Login Dropdown -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <button class="nav-icon-btn dropdown-toggle" data-bs-toggle="dropdown" style="font-size:0.9rem; font-weight:600;">
                            <i class="bi bi-person-circle fs-5 text-primary"></i>
                            <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><span class="dropdown-item-text small text-muted"><?php echo htmlspecialchars($_SESSION['user_email']); ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="?action=logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <button class="nav-icon-btn" data-bs-toggle="modal" data-bs-target="#loginModal" style="font-size:0.9rem; font-weight:600;">
                        <i class="bi bi-person fs-5"></i>
                        <span class="d-none d-md-inline">Login</span>
                    </button>
                <?php endif; ?>

                <!-- Bookmarks Icon -->
                <a href="#" class="nav-icon-btn" data-bs-toggle="modal" data-bs-target="#bookmarksModal" title="Saved Bookmarks">
                    <i class="bi bi-heart fs-5"></i>
                    <?php if ($bookmark_count > 0): ?>
                        <span class="icon-badge"><?php echo $bookmark_count; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Shopping Cart Icon -->
                <a href="#" class="nav-icon-btn" data-bs-toggle="modal" data-bs-target="#cartModal" title="Shopping Cart">
                    <i class="bi bi-cart fs-5"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="icon-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>

            </div>

        </div>
    </header>

    <!-- ── CATEGORY PILL NAVIGATION BAR ────────────────────────────────────── -->
    <div class="category-nav-bar">
        <a href="index.php" class="cat-pill <?php echo (empty($selected_category) && empty($search_query)) ? 'active' : ''; ?>">
            <i class="bi bi-grid-fill me-1"></i> All Categories
        </a>
        <a href="?category=Hacking+Books" class="cat-pill <?php echo ($selected_category === 'Hacking Books') ? 'active' : ''; ?>">
            <i class="bi bi-shield-lock-fill me-1"></i> Hacking Books
        </a>
        <a href="?category=Developer+Books" class="cat-pill <?php echo ($selected_category === 'Developer Books') ? 'active' : ''; ?>">
            <i class="bi bi-code-slash me-1"></i> Developer Books
        </a>
        <a href="?category=Self+Help+books" class="cat-pill <?php echo ($selected_category === 'Self Help books') ? 'active' : ''; ?>">
            <i class="bi bi-stars me-1"></i> Self Help Books
        </a>
        <a href="?category=Space+Books" class="cat-pill <?php echo ($selected_category === 'Space Books') ? 'active' : ''; ?>">
            <i class="bi bi-rocket-takeoff-fill me-1"></i> Space Books
        </a>
        <a href="?category=Evolution+Books" class="cat-pill <?php echo ($selected_category === 'Evolution Books') ? 'active' : ''; ?>">
            <i class="bi bi-globe-americas me-1"></i> Evolution Books
        </a>
    </div>

    <!-- ── MAIN CATALOG CONTENT ─────────────────────────────────────────────── -->
    <main class="catalog-container">

        <!-- Flash Alerts -->
        <?php if ($msg_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $msg_error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($msg_success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $msg_success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filtered / Search Results View -->
        <?php if ($filtered_books_result): ?>
            <div class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="section-title m-0">
                        <?php if (!empty($search_query)): ?>
                            Search Results for "<em><?php echo htmlspecialchars($search_query); ?></em>"
                        <?php else: ?>
                            Category: <?php echo htmlspecialchars($selected_category); ?>
                        <?php endif; ?>
                    </h2>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">Show All Categories</a>
                </div>

                <div class="book-grid">
                    <?php if (mysqli_num_rows($filtered_books_result) > 0): ?>
                        <?php while ($b = mysqli_fetch_assoc($filtered_books_result)): ?>
                            <?php $is_bookmarked = in_array((int)$b['id'], $_SESSION['bookmarks']); ?>
                            <div class="book-card">
                                <a href="?action=toggle_bookmark&book_id=<?php echo (int)$b['id']; ?>&q=<?php echo urlencode($search_query); ?>&category=<?php echo urlencode($selected_category); ?>" class="bookmark-btn <?php echo $is_bookmarked ? 'active' : ''; ?>">
                                    <i class="bi <?php echo $is_bookmarked ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>"></i>
                                </a>

                                <div class="book-cover-wrapper">
                                    <img src="<?php echo htmlspecialchars($b['image_url']); ?>" alt="<?php echo htmlspecialchars($b['title']); ?>" class="book-cover-img" loading="lazy">
                                </div>

                                <div>
                                    <div class="book-info-title" title="<?php echo htmlspecialchars($b['title']); ?>"><?php echo htmlspecialchars($b['title']); ?></div>
                                    <div class="book-info-author"><?php echo htmlspecialchars($b['author']); ?></div>
                                    <div class="book-price-row">
                                        <span class="book-price">₹<?php echo number_format((int)$b['price']); ?></span>
                                        <?php if (!empty($b['original_price'])): ?>
                                            <span class="book-original-price">₹<?php echo number_format((int)$b['original_price']); ?></span>
                                            <span class="book-discount">(-<?php echo (int)$b['discount_percent']; ?>%)</span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="?action=add_cart&book_id=<?php echo (int)$b['id']; ?>&q=<?php echo urlencode($search_query); ?>&category=<?php echo urlencode($selected_category); ?>" class="btn-add-cart">
                                        <i class="bi bi-cart-plus me-1"></i> Add to Cart
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 py-5 text-center text-muted">
                            <i class="bi bi-search fs-1 d-block mb-3 text-secondary"></i>
                            <h5>No books found matching your criteria</h5>
                            <p class="small">Try exploring other categories using the top bar.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>

            <!-- CATEGORY 1: HACKING BOOKS -->
            <section class="mb-5">
                <div class="section-header">
                    <h2 class="section-title"><i class="bi bi-shield-lock-fill text-danger"></i> Hacking Books</h2>
                    <a href="?category=Hacking+Books" class="view-all-link">View All Hacking</a>
                </div>
                <div class="book-grid">
                    <?php while ($b = mysqli_fetch_assoc($hacking_books)): ?>
                        <?php $is_bookmarked = in_array((int)$b['id'], $_SESSION['bookmarks']); ?>
                        <div class="book-card">
                            <a href="?action=toggle_bookmark&book_id=<?php echo (int)$b['id']; ?>" class="bookmark-btn <?php echo $is_bookmarked ? 'active' : ''; ?>">
                                <i class="bi <?php echo $is_bookmarked ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>"></i>
                            </a>
                            <div class="book-cover-wrapper">
                                <img src="<?php echo htmlspecialchars($b['image_url']); ?>" alt="<?php echo htmlspecialchars($b['title']); ?>" class="book-cover-img" loading="lazy">
                            </div>
                            <div>
                                <div class="book-info-title" title="<?php echo htmlspecialchars($b['title']); ?>"><?php echo htmlspecialchars($b['title']); ?></div>
                                <div class="book-info-author"><?php echo htmlspecialchars($b['author']); ?></div>
                                <div class="book-price-row">
                                    <span class="book-price">₹<?php echo number_format((int)$b['price']); ?></span>
                                    <span class="book-original-price">₹<?php echo number_format((int)$b['original_price']); ?></span>
                                    <span class="book-discount">(-<?php echo (int)$b['discount_percent']; ?>%)</span>
                                </div>
                                <a href="?action=add_cart&book_id=<?php echo (int)$b['id']; ?>" class="btn-add-cart"><i class="bi bi-cart-plus me-1"></i> Add to Cart</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>

            <!-- CATEGORY 2: DEVELOPER BOOKS -->
            <section class="mb-5">
                <div class="section-header">
                    <h2 class="section-title"><i class="bi bi-code-slash text-primary"></i> Developer Books</h2>
                    <a href="?category=Developer+Books" class="view-all-link">View All Developer</a>
                </div>
                <div class="book-grid">
                    <?php while ($b = mysqli_fetch_assoc($developer_books)): ?>
                        <?php $is_bookmarked = in_array((int)$b['id'], $_SESSION['bookmarks']); ?>
                        <div class="book-card">
                            <a href="?action=toggle_bookmark&book_id=<?php echo (int)$b['id']; ?>" class="bookmark-btn <?php echo $is_bookmarked ? 'active' : ''; ?>">
                                <i class="bi <?php echo $is_bookmarked ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>"></i>
                            </a>
                            <div class="book-cover-wrapper">
                                <img src="<?php echo htmlspecialchars($b['image_url']); ?>" alt="<?php echo htmlspecialchars($b['title']); ?>" class="book-cover-img" loading="lazy">
                            </div>
                            <div>
                                <div class="book-info-title" title="<?php echo htmlspecialchars($b['title']); ?>"><?php echo htmlspecialchars($b['title']); ?></div>
                                <div class="book-info-author"><?php echo htmlspecialchars($b['author']); ?></div>
                                <div class="book-price-row">
                                    <span class="book-price">₹<?php echo number_format((int)$b['price']); ?></span>
                                    <span class="book-original-price">₹<?php echo number_format((int)$b['original_price']); ?></span>
                                    <span class="book-discount">(-<?php echo (int)$b['discount_percent']; ?>%)</span>
                                </div>
                                <a href="?action=add_cart&book_id=<?php echo (int)$b['id']; ?>" class="btn-add-cart"><i class="bi bi-cart-plus me-1"></i> Add to Cart</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>

            <!-- CATEGORY 3: SELF HELP BOOKS -->
            <section class="mb-5">
                <div class="section-header">
                    <h2 class="section-title"><i class="bi bi-stars text-warning"></i> Self Help Books</h2>
                    <a href="?category=Self+Help+books" class="view-all-link">View All Self Help</a>
                </div>
                <div class="book-grid">
                    <?php while ($b = mysqli_fetch_assoc($selfhelp_books)): ?>
                        <?php $is_bookmarked = in_array((int)$b['id'], $_SESSION['bookmarks']); ?>
                        <div class="book-card">
                            <a href="?action=toggle_bookmark&book_id=<?php echo (int)$b['id']; ?>" class="bookmark-btn <?php echo $is_bookmarked ? 'active' : ''; ?>">
                                <i class="bi <?php echo $is_bookmarked ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>"></i>
                            </a>
                            <div class="book-cover-wrapper">
                                <img src="<?php echo htmlspecialchars($b['image_url']); ?>" alt="<?php echo htmlspecialchars($b['title']); ?>" class="book-cover-img" loading="lazy">
                            </div>
                            <div>
                                <div class="book-info-title" title="<?php echo htmlspecialchars($b['title']); ?>"><?php echo htmlspecialchars($b['title']); ?></div>
                                <div class="book-info-author"><?php echo htmlspecialchars($b['author']); ?></div>
                                <div class="book-price-row">
                                    <span class="book-price">₹<?php echo number_format((int)$b['price']); ?></span>
                                    <span class="book-original-price">₹<?php echo number_format((int)$b['original_price']); ?></span>
                                    <span class="book-discount">(-<?php echo (int)$b['discount_percent']; ?>%)</span>
                                </div>
                                <a href="?action=add_cart&book_id=<?php echo (int)$b['id']; ?>" class="btn-add-cart"><i class="bi bi-cart-plus me-1"></i> Add to Cart</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>

            <!-- CATEGORY 4: SPACE BOOKS -->
            <section class="mb-5">
                <div class="section-header">
                    <h2 class="section-title"><i class="bi bi-rocket-takeoff-fill text-info"></i> Space Books</h2>
                    <a href="?category=Space+Books" class="view-all-link">View All Space</a>
                </div>
                <div class="book-grid">
                    <?php while ($b = mysqli_fetch_assoc($space_books)): ?>
                        <?php $is_bookmarked = in_array((int)$b['id'], $_SESSION['bookmarks']); ?>
                        <div class="book-card">
                            <a href="?action=toggle_bookmark&book_id=<?php echo (int)$b['id']; ?>" class="bookmark-btn <?php echo $is_bookmarked ? 'active' : ''; ?>">
                                <i class="bi <?php echo $is_bookmarked ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>"></i>
                            </a>
                            <div class="book-cover-wrapper">
                                <img src="<?php echo htmlspecialchars($b['image_url']); ?>" alt="<?php echo htmlspecialchars($b['title']); ?>" class="book-cover-img" loading="lazy">
                            </div>
                            <div>
                                <div class="book-info-title" title="<?php echo htmlspecialchars($b['title']); ?>"><?php echo htmlspecialchars($b['title']); ?></div>
                                <div class="book-info-author"><?php echo htmlspecialchars($b['author']); ?></div>
                                <div class="book-price-row">
                                    <span class="book-price">₹<?php echo number_format((int)$b['price']); ?></span>
                                    <span class="book-original-price">₹<?php echo number_format((int)$b['original_price']); ?></span>
                                    <span class="book-discount">(-<?php echo (int)$b['discount_percent']; ?>%)</span>
                                </div>
                                <a href="?action=add_cart&book_id=<?php echo (int)$b['id']; ?>" class="btn-add-cart"><i class="bi bi-cart-plus me-1"></i> Add to Cart</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>

            <!-- CATEGORY 5: EVOLUTION BOOKS -->
            <section class="mb-5">
                <div class="section-header">
                    <h2 class="section-title"><i class="bi bi-globe-americas text-success"></i> Evolution Books</h2>
                    <a href="?category=Evolution+Books" class="view-all-link">View All Evolution</a>
                </div>
                <div class="book-grid">
                    <?php while ($b = mysqli_fetch_assoc($evolution_books)): ?>
                        <?php $is_bookmarked = in_array((int)$b['id'], $_SESSION['bookmarks']); ?>
                        <div class="book-card">
                            <a href="?action=toggle_bookmark&book_id=<?php echo (int)$b['id']; ?>" class="bookmark-btn <?php echo $is_bookmarked ? 'active' : ''; ?>">
                                <i class="bi <?php echo $is_bookmarked ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>"></i>
                            </a>
                            <div class="book-cover-wrapper">
                                <img src="<?php echo htmlspecialchars($b['image_url']); ?>" alt="<?php echo htmlspecialchars($b['title']); ?>" class="book-cover-img" loading="lazy">
                            </div>
                            <div>
                                <div class="book-info-title" title="<?php echo htmlspecialchars($b['title']); ?>"><?php echo htmlspecialchars($b['title']); ?></div>
                                <div class="book-info-author"><?php echo htmlspecialchars($b['author']); ?></div>
                                <div class="book-price-row">
                                    <span class="book-price">₹<?php echo number_format((int)$b['price']); ?></span>
                                    <span class="book-original-price">₹<?php echo number_format((int)$b['original_price']); ?></span>
                                    <span class="book-discount">(-<?php echo (int)$b['discount_percent']; ?>%)</span>
                                </div>
                                <a href="?action=add_cart&book_id=<?php echo (int)$b['id']; ?>" class="btn-add-cart"><i class="bi bi-cart-plus me-1"></i> Add to Cart</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>

            <!-- CATEGORY 6: BESTSELLING BOOKS -->
            <section class="mb-5">
                <div class="section-header">
                    <h2 class="section-title"><i class="bi bi-trophy-fill text-warning"></i> Bestselling All-Time</h2>
                    <a href="index.php" class="view-all-link">View All</a>
                </div>
                <div class="book-grid">
                    <?php while ($b = mysqli_fetch_assoc($bestselling_books)): ?>
                        <?php $is_bookmarked = in_array((int)$b['id'], $_SESSION['bookmarks']); ?>
                        <div class="book-card">
                            <a href="?action=toggle_bookmark&book_id=<?php echo (int)$b['id']; ?>" class="bookmark-btn <?php echo $is_bookmarked ? 'active' : ''; ?>">
                                <i class="bi <?php echo $is_bookmarked ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>"></i>
                            </a>
                            <div class="book-cover-wrapper">
                                <img src="<?php echo htmlspecialchars($b['image_url']); ?>" alt="<?php echo htmlspecialchars($b['title']); ?>" class="book-cover-img" loading="lazy">
                            </div>
                            <div>
                                <div class="book-info-title" title="<?php echo htmlspecialchars($b['title']); ?>"><?php echo htmlspecialchars($b['title']); ?></div>
                                <div class="book-info-author"><?php echo htmlspecialchars($b['author']); ?></div>
                                <div class="book-price-row">
                                    <span class="book-price">₹<?php echo number_format((int)$b['price']); ?></span>
                                    <span class="book-original-price">₹<?php echo number_format((int)$b['original_price']); ?></span>
                                    <span class="book-discount">(-<?php echo (int)$b['discount_percent']; ?>%)</span>
                                </div>
                                <a href="?action=add_cart&book_id=<?php echo (int)$b['id']; ?>" class="btn-add-cart"><i class="bi bi-cart-plus me-1"></i> Add to Cart</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>

        <?php endif; ?>

    </main>

    <!-- Floating WhatsApp Support Icon -->
    <a href="https://wa.me/919355505734" target="_blank" class="floating-whatsapp" title="Chat on WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </a>

    <!-- ── MODAL: BOOKMARKS / WISHLIST ──────────────────────────────────────── -->
    <div class="modal fade" id="bookmarksModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-heart-fill text-danger me-2"></i>My Bookmarks (<?php echo $bookmark_count; ?>)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <?php if ($bookmark_count > 0): ?>
                        <?php
                        $b_ids = implode(',', array_map('intval', $_SESSION['bookmarks']));
                        $bm_items = @mysqli_query($conn, "SELECT * FROM atlantic_books WHERE id IN ($b_ids)");
                        ?>
                        <ul class="list-group list-group-flush">
                            <?php while ($bm = mysqli_fetch_assoc($bm_items)): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?php echo htmlspecialchars($bm['image_url']); ?>" style="width:36px;height:48px;object-fit:cover;border-radius:3px;">
                                        <div>
                                            <div class="fw-bold small"><?php echo htmlspecialchars($bm['title']); ?></div>
                                            <div class="text-muted" style="font-size:0.75rem;">₹<?php echo number_format($bm['price']); ?> • <?php echo htmlspecialchars($bm['author']); ?></div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="?action=add_cart&book_id=<?php echo (int)$bm['id']; ?>" class="btn btn-sm btn-outline-danger">Cart</a>
                                        <a href="?action=toggle_bookmark&book_id=<?php echo (int)$bm['id']; ?>" class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></a>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-heart fs-1 d-block mb-2 text-secondary"></i>
                            No books bookmarked yet. Click the heart icon on any book to save it here!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── MODAL: SHOPPING CART ─────────────────────────────────────────────── -->
    <div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cart-fill text-danger me-2"></i>Shopping Cart (<?php echo $cart_count; ?>)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <?php if ($cart_count > 0): ?>
                        <?php
                        $c_ids = implode(',', array_map('intval', $_SESSION['cart']));
                        $cart_items = @mysqli_query($conn, "SELECT * FROM atlantic_books WHERE id IN ($c_ids)");
                        $total = 0;
                        ?>
                        <ul class="list-group list-group-flush mb-3">
                            <?php while ($ci = mysqli_fetch_assoc($cart_items)): ?>
                                <?php $total += (int)$ci['price']; ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?php echo htmlspecialchars($ci['image_url']); ?>" style="width:36px;height:48px;object-fit:cover;border-radius:3px;">
                                        <div>
                                            <div class="fw-bold small"><?php echo htmlspecialchars($ci['title']); ?></div>
                                            <div class="text-danger fw-bold small">₹<?php echo number_format($ci['price']); ?></div>
                                        </div>
                                    </div>
                                    <a href="?action=remove_cart&book_id=<?php echo (int)$ci['id']; ?>" class="btn btn-sm btn-light text-danger"><i class="bi bi-x-lg"></i></a>
                                </li>
                            <?php endwhile; ?>
                        </ul>

                        <div class="d-flex justify-content-between align-items-center border-top pt-3 mb-3">
                            <span class="fw-bold">Subtotal:</span>
                            <span class="fs-5 fw-bold text-danger">₹<?php echo number_format($total); ?></span>
                        </div>

                        <button class="btn btn-danger w-100 py-2 fw-semibold" onclick="alert('Checkout process initiated!')">
                            Proceed to Checkout
                        </button>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary"></i>
                            Your shopping cart is empty.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── MODAL: LOGIN ─────────────────────────────────────────────────────── -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-box-arrow-in-right me-2"></i>Login to Atlantic Books</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" action="index.php">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <input type="text" name="email" class="form-control" placeholder="user@example.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-danger fw-semibold py-2">Login</button>
                        </div>

                        <div class="text-center small text-muted">
                            New to Atlantic Books? 
                            <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#registerModal" class="text-danger fw-semibold">Create an account</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ── MODAL: REGISTER ──────────────────────────────────────────────────── -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-person-plus-fill me-2"></i>Create New Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" action="index.php">
                        <input type="hidden" name="action" value="register">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Rahul Verma" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="rahul@example.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Create a password" required>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-danger fw-semibold py-2">Register</button>
                        </div>

                        <div class="text-center small text-muted">
                            Already have an account? 
                            <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#loginModal" class="text-danger fw-semibold">Login here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
