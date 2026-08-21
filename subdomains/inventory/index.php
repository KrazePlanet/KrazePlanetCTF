<?php
/**
 * ============================================================================
 * DigiLocker — CBSE Class XII Examination 2026 Results Portal (Real-World Lab)
 * 
 * Features for SQLi Testing:
 *   1. Roll Number Search (Time-based Blind SLEEP(), Boolean-based, UNION SQLi)
 *   2. Admit Card ID & School Number Filters
 *   3. Digital Marksheet Verification View
 *   4. Sample Student Directory (/directory.php)
 * ============================================================================
 */

session_start();

// ── Database Configuration ──────────────────────────────────────────────────
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "KrazePlanet_DB";

$conn = @mysqli_connect($db_host, $db_user, $db_pass);
if (!$conn) {
    die("<div style='font-family:sans-serif;padding:30px;background:#f8d7da;color:#721c24;margin:50px auto;max-width:600px;border-radius:8px;'><h3>Database Connection Error</h3><p>Could not connect to MySQL server. Please ensure XAMPP/LAMPP MySQL is running.</p><p><code>" . htmlspecialchars(mysqli_connect_error()) . "</code></p></div>");
}

@mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $db_name");
@mysqli_select_db($conn, $db_name);

// ── Schema Initialization (75 Hardcoded Student Records) ───────────────────
function setup_cbse_schema($conn) {
    // 1. Student Results Table
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cbse_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        roll_no VARCHAR(50) NOT NULL UNIQUE,
        student_name VARCHAR(100) NOT NULL,
        mother_name VARCHAR(100) NOT NULL,
        father_name VARCHAR(100) NOT NULL,
        admit_card_id VARCHAR(50) NOT NULL,
        school_no VARCHAR(50) NOT NULL,
        school_name VARCHAR(200) NOT NULL,
        class_grade VARCHAR(20) DEFAULT 'XII',
        year VARCHAR(10) DEFAULT '2026',
        sub_english INT DEFAULT 95,
        sub_maths INT DEFAULT 98,
        sub_physics INT DEFAULT 96,
        sub_chemistry INT DEFAULT 94,
        sub_cs INT DEFAULT 99,
        total_percent DECIMAL(4,2) DEFAULT 96.40,
        result_status VARCHAR(20) DEFAULT 'PASS',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $chk_r = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM cbse_results");
    if ($chk_r && ($row = mysqli_fetch_assoc($chk_r)) && $row['c'] < 50) {
        $records = [
        ['12345678', 'Aarav Sharma', 'Sunita Sharma', 'Rajesh Sharma', 'AA123456', '12345', 'Delhi Public School, R.K. Puram, New Delhi', 'XII', 96, 98, 98, 95, 99, 97.2, 'PASS'],
        ['26145892', 'Priya Patel', 'Meena Patel', 'Kirit Patel', 'PP261458', '24150', 'The Mother International School, Sri Aurobindo Marg, New Delhi', 'XII', 94, 95, 92, 90, 96, 93.4, 'PASS'],
        ['87349120', 'Rohan Gupta', 'Anju Gupta', 'Sanjay Gupta', 'RG873491', '19820', 'Modern School, Barakhamba Road, New Delhi', 'XII', 98, 99, 99, 97, 100, 98.6, 'PASS'],
        ['44109823', 'Ananya Verma', 'Kavita Verma', 'Alok Verma', 'AV441098', '11024', 'Kendriya Vidyalaya No. 1, Delhi Cantt', 'XII', 91, 88, 89, 87, 94, 89.8, 'PASS'],
        ['19718424', 'Sunita Gupta', 'Kavya Gupta', 'Shaurya Gupta', 'SG197184', '27891', 'Springdales School, Dhaula Kuan, New Delhi', 'XII', 94, 84, 75, 83, 85, 84.2, 'PASS'],
        ['74922444', 'Arjun Malhotra', 'Khushi Malhotra', 'Sai Malhotra', 'AM749224', '19820', 'Modern School, Barakhamba Road, New Delhi', 'XII', 94, 77, 84, 90, 86, 86.2, 'PASS'],
        ['19670552', 'Vivaan Kashyap', 'Aadhya Kashyap', 'Shaurya Kashyap', 'VK196705', '71025', 'Don Bosco High School, Matunga, Mumbai', 'XII', 83, 92, 85, 89, 100, 89.8, 'PASS'],
        ['97071068', 'Meera Yadav', 'Kiara Yadav', 'Ayush Yadav', 'MY970710', '48210', 'Amity International School, Sector 44, Noida', 'XII', 76, 97, 93, 95, 98, 91.8, 'PASS'],
        ['22472904', 'Diya Tiwari', 'Aadhya Tiwari', 'Rudra Tiwari', 'DT224729', '71025', 'Don Bosco High School, Matunga, Mumbai', 'XII', 83, 85, 95, 73, 82, 83.6, 'PASS'],
        ['20764723', 'Aditi Menon', 'Kriti Menon', 'Advik Menon', 'AM207647', '12345', 'Delhi Public School, R.K. Puram, New Delhi', 'XII', 92, 79, 92, 89, 86, 87.6, 'PASS'],
        ['68844284', 'Shreya Das', 'Meera Das', 'Atharva Das', 'SD688442', '19820', 'Modern School, Barakhamba Road, New Delhi', 'XII', 81, 70, 75, 94, 82, 80.4, 'PASS'],
        ['18425498', 'Harsh Kashyap', 'Navya Kashyap', 'Siddharth Kashyap', 'HK184254', '54019', 'The Heritage School, Sector 62, Gurugram', 'XII', 93, 98, 73, 78, 87, 85.8, 'PASS'],
        ['39076640', 'Aditi Singh', 'Kiara Singh', 'Piyush Singh', 'AS390766', '15902', 'Bal Bharati Public School, Ganga Ram Hospital Marg, New Delhi', 'XII', 94, 98, 94, 91, 98, 95.0, 'PASS'],
        ['77429576', 'Varun Ghosh', 'Shreya Ghosh', 'Siddharth Ghosh', 'VG774295', '33419', 'DAV Public School, Sector 14, Gurugram', 'XII', 82, 88, 87, 88, 80, 85.0, 'PASS'],
        ['43329505', 'Pranav Singh', 'Payal Singh', 'Kabir Singh', 'PS433295', '54019', 'The Heritage School, Sector 62, Gurugram', 'XII', 81, 92, 83, 82, 82, 84.0, 'PASS'],
        ['21947868', 'Chirag Pandey', 'Poonam Pandey', 'Ayush Pandey', 'CP219478', '11024', 'Kendriya Vidyalaya No. 1, Delhi Cantt', 'XII', 98, 96, 94, 91, 94, 94.6, 'PASS'],
        ['13991320', 'Kiara Deshmukh', 'Anjali Deshmukh', 'Vihaan Deshmukh', 'KD139913', '39104', 'National Public School, Indiranagar, Bengaluru', 'XII', 83, 71, 97, 72, 91, 82.8, 'PASS'],
        ['69047624', 'Neha Agarwal', 'Saanvi Agarwal', 'Sameer Agarwal', 'NA690476', '39104', 'National Public School, Indiranagar, Bengaluru', 'XII', 95, 70, 95, 82, 89, 86.2, 'PASS'],
        ['49729848', 'Varun Tiwari', 'Meera Tiwari', 'Karan Tiwari', 'VT497298', '19820', 'Modern School, Barakhamba Road, New Delhi', 'XII', 81, 86, 96, 73, 89, 85.0, 'PASS'],
        ['51984479', 'Nikhil Patel', 'Bhavna Patel', 'Yash Patel', 'NP519844', '54019', 'The Heritage School, Sector 62, Gurugram', 'XII', 82, 86, 85, 90, 86, 85.8, 'PASS'],
        ['49674182', 'Dhruv Chopra', 'Riya Chopra', 'Ishaan Chopra', 'DC496741', '19820', 'Modern School, Barakhamba Road, New Delhi', 'XII', 93, 83, 95, 88, 92, 90.2, 'PASS'],
        ['24470873', 'Dhruv Pillai', 'Shreya Pillai', 'Vihaan Pillai', 'DP244708', '11024', 'Kendriya Vidyalaya No. 1, Delhi Cantt', 'XII', 86, 77, 90, 88, 91, 86.4, 'PASS'],
        ['59595105', 'Ankit Tiwari', 'Tanvi Tiwari', 'Ishaan Tiwari', 'AT595951', '42901', 'Bhavan Vidyalaya, Sector 27, Chandigarh', 'XII', 97, 89, 98, 89, 80, 90.6, 'PASS'],
        ['55990292', 'Meera Saxena', 'Geeta Saxena', 'Deepak Saxena', 'MS559902', '11024', 'Kendriya Vidyalaya No. 1, Delhi Cantt', 'XII', 75, 73, 85, 77, 95, 81.0, 'PASS'],
        ['60578960', 'Tanvi Kapoor', 'Pari Kapoor', 'Aditya Kapoor', 'TK605789', '18430', 'Sanskriti School, Chanakyapuri, New Delhi', 'XII', 79, 71, 88, 85, 98, 84.2, 'PASS'],
        ['13140518', 'Reyansh Verma', 'Divya Verma', 'Ayush Verma', 'RV131405', '39104', 'National Public School, Indiranagar, Bengaluru', 'XII', 77, 89, 87, 92, 86, 86.2, 'PASS'],
        ['75187644', 'Deepak Bansal', 'Payal Bansal', 'Pranav Bansal', 'DB751876', '71025', 'Don Bosco High School, Matunga, Mumbai', 'XII', 82, 89, 76, 94, 95, 87.2, 'PASS'],
        ['72945801', 'Reyansh Menon', 'Navya Menon', 'Karan Menon', 'RM729458', '15902', 'Bal Bharati Public School, Ganga Ram Hospital Marg, New Delhi', 'XII', 96, 85, 87, 89, 83, 88.0, 'PASS'],
        ['24284934', 'Muskan Pillai', 'Aadhya Pillai', 'Gaurav Pillai', 'MP242849', '42901', 'Bhavan Vidyalaya, Sector 27, Chandigarh', 'XII', 84, 98, 96, 75, 94, 89.4, 'PASS'],
        ['85006460', 'Pooja Menon', 'Payal Menon', 'Advik Menon', 'PM850064', '65012', 'St. Xavier Senior Secondary School, Civil Lines, Delhi', 'XII', 83, 89, 80, 70, 91, 82.6, 'PASS'],
        ['26084054', 'Sai Joshi', 'Aadhya Joshi', 'Abhishek Joshi', 'SJ260840', '39104', 'National Public School, Indiranagar, Bengaluru', 'XII', 94, 81, 84, 71, 98, 85.6, 'PASS'],
        ['57872415', 'Vikram Kumar', 'Neha Kumar', 'Vivaan Kumar', 'VK578724', '62190', 'Loyola School, Beldih, Jamshedpur', 'XII', 82, 80, 80, 79, 83, 80.8, 'PASS'],
        ['81302603', 'Krishna Ghosh', 'Kavya Ghosh', 'Aditya Ghosh', 'KG813026', '54019', 'The Heritage School, Sector 62, Gurugram', 'XII', 92, 80, 88, 93, 86, 87.8, 'PASS'],
        ['95747738', 'Kartik Kulkarni', 'Meena Kulkarni', 'Aarav Kulkarni', 'KK957477', '15902', 'Bal Bharati Public School, Ganga Ram Hospital Marg, New Delhi', 'XII', 95, 79, 75, 89, 84, 84.4, 'PASS'],
        ['23816587', 'Meera Pandey', 'Aditi Pandey', 'Deepak Pandey', 'MP238165', '39104', 'National Public School, Indiranagar, Bengaluru', 'XII', 83, 95, 75, 75, 84, 82.4, 'PASS'],
        ['66617035', 'Diya Kumar', 'Meera Kumar', 'Pranav Kumar', 'DK666170', '33419', 'DAV Public School, Sector 14, Gurugram', 'XII', 79, 84, 77, 89, 86, 83.0, 'PASS'],
        ['81760490', 'Vikram Sharma', 'Sunita Sharma', 'Nikhil Sharma', 'VS817604', '11024', 'Kendriya Vidyalaya No. 1, Delhi Cantt', 'XII', 75, 96, 79, 86, 85, 84.2, 'PASS'],
        ['87322583', 'Chirag Saxena', 'Payal Saxena', 'Siddharth Saxena', 'CS873225', '39104', 'National Public School, Indiranagar, Bengaluru', 'XII', 93, 84, 88, 76, 86, 85.4, 'PASS'],
        ['68330654', 'Jyoti Patil', 'Saanvi Patil', 'Karan Patil', 'JP683306', '24150', 'The Mother International School, Sri Aurobindo Marg, New Delhi', 'XII', 80, 92, 98, 93, 97, 92.0, 'PASS'],
        ['71954409', 'Riya Mehta', 'Payal Mehta', 'Ayaan Mehta', 'RM719544', '27891', 'Springdales School, Dhaula Kuan, New Delhi', 'XII', 86, 75, 98, 79, 88, 85.2, 'PASS'],
        ['93588361', 'Ankit Patil', 'Aadhya Patil', 'Vivaan Patil', 'AP935883', '15902', 'Bal Bharati Public School, Ganga Ram Hospital Marg, New Delhi', 'XII', 99, 88, 99, 92, 99, 95.4, 'PASS'],
        ['47187436', 'Saanvi Malhotra', 'Simran Malhotra', 'Deepak Malhotra', 'SM471874', '42901', 'Bhavan Vidyalaya, Sector 27, Chandigarh', 'XII', 78, 75, 92, 74, 90, 81.8, 'PASS'],
        ['16902002', 'Navya Dubey', 'Diya Dubey', 'Deepak Dubey', 'ND169020', '71025', 'Don Bosco High School, Matunga, Mumbai', 'XII', 94, 90, 88, 97, 94, 92.6, 'PASS'],
        ['58254505', 'Tanvi Bansal', 'Divya Bansal', 'Manish Bansal', 'TB582545', '33419', 'DAV Public School, Sector 14, Gurugram', 'XII', 76, 83, 89, 77, 84, 81.8, 'PASS'],
        ['21598407', 'Varun Malhotra', 'Ananya Malhotra', 'Pranav Malhotra', 'VM215984', '54019', 'The Heritage School, Sector 62, Gurugram', 'XII', 90, 100, 84, 91, 93, 91.6, 'PASS'],
        ['79742340', 'Gaurav Ghosh', 'Aadhya Ghosh', 'Piyush Ghosh', 'GG797423', '54019', 'The Heritage School, Sector 62, Gurugram', 'XII', 93, 75, 74, 81, 100, 84.6, 'PASS'],
        ['77567960', 'Nikhil Joshi', 'Diya Joshi', 'Aarav Joshi', 'NJ775679', '42901', 'Bhavan Vidyalaya, Sector 27, Chandigarh', 'XII', 79, 84, 98, 73, 89, 84.6, 'PASS'],
        ['54896294', 'Kriti Roy', 'Rashmi Roy', 'Varun Roy', 'KR548962', '39104', 'National Public School, Indiranagar, Bengaluru', 'XII', 85, 92, 80, 71, 94, 84.4, 'PASS'],
        ['73831389', 'Ayush Mukherjee', 'Rashmi Mukherjee', 'Deepak Mukherjee', 'AM738313', '54019', 'The Heritage School, Sector 62, Gurugram', 'XII', 90, 75, 78, 82, 92, 83.4, 'PASS'],
        ['57307871', 'Myra Nair', 'Tanvi Nair', 'Dhruv Nair', 'MN573078', '65012', 'St. Xavier Senior Secondary School, Civil Lines, Delhi', 'XII', 79, 73, 93, 90, 84, 83.8, 'PASS'],
        ['25472274', 'Dhruv Tiwari', 'Meera Tiwari', 'Shivam Tiwari', 'DT254722', '42901', 'Bhavan Vidyalaya, Sector 27, Chandigarh', 'XII', 91, 90, 99, 92, 89, 92.2, 'PASS'],
        ['27268597', 'Kabir Pandey', 'Ananya Pandey', 'Kartik Pandey', 'KP272685', '27891', 'Springdales School, Dhaula Kuan, New Delhi', 'XII', 86, 90, 86, 93, 89, 88.8, 'PASS'],
        ['23153867', 'Kabir Patil', 'Jyoti Patil', 'Vihaan Patil', 'KP231538', '11024', 'Kendriya Vidyalaya No. 1, Delhi Cantt', 'XII', 96, 89, 96, 95, 84, 92.0, 'PASS'],
        ['66796095', 'Vikram Ghosh', 'Neha Ghosh', 'Kabir Ghosh', 'VG667960', '42901', 'Bhavan Vidyalaya, Sector 27, Chandigarh', 'XII', 75, 85, 81, 84, 98, 84.6, 'PASS'],
        ['97092582', 'Pranav Menon', 'Avani Menon', 'Deepak Menon', 'PM970925', '54019', 'The Heritage School, Sector 62, Gurugram', 'XII', 92, 76, 98, 90, 100, 91.2, 'PASS'],
        ['81699667', 'Sneha Mishra', 'Geeta Mishra', 'Dhruv Mishra', 'SM816996', '19820', 'Modern School, Barakhamba Road, New Delhi', 'XII', 81, 72, 73, 82, 96, 80.8, 'PASS'],
        ['71516194', 'Divya Pillai', 'Meena Pillai', 'Arjun Pillai', 'DP715161', '12345', 'Delhi Public School, R.K. Puram, New Delhi', 'XII', 84, 81, 94, 84, 80, 84.6, 'PASS'],
        ['71656081', 'Gaurav Kapoor', 'Bhavna Kapoor', 'Piyush Kapoor', 'GK716560', '18430', 'Sanskriti School, Chanakyapuri, New Delhi', 'XII', 77, 88, 78, 70, 80, 78.6, 'PASS'],
        ['88782830', 'Deepak Kashyap', 'Aditi Kashyap', 'Aarav Kashyap', 'DK887828', '15902', 'Bal Bharati Public School, Ganga Ram Hospital Marg, New Delhi', 'XII', 97, 78, 85, 75, 86, 84.2, 'PASS'],
        ['59798901', 'Isha Shah', 'Kriti Shah', 'Vihaan Shah', 'IS597989', '11024', 'Kendriya Vidyalaya No. 1, Delhi Cantt', 'XII', 86, 91, 82, 78, 88, 85.0, 'PASS'],
        ['16990220', 'Yash Sen', 'Pallavi Sen', 'Pranav Sen', 'YS169902', '12345', 'Delhi Public School, R.K. Puram, New Delhi', 'XII', 79, 89, 92, 74, 86, 84.0, 'PASS'],
        ['78360419', 'Neha Agarwal', 'Pallavi Agarwal', 'Vikram Agarwal', 'NA783604', '39104', 'National Public School, Indiranagar, Bengaluru', 'XII', 97, 85, 72, 82, 93, 85.8, 'PASS'],
        ['71591951', 'Meera Deshmukh', 'Diya Deshmukh', 'Shaurya Deshmukh', 'MD715919', '18430', 'Sanskriti School, Chanakyapuri, New Delhi', 'XII', 75, 96, 83, 80, 84, 83.6, 'PASS'],
        ['74132428', 'Khushi Mishra', 'Aadhya Mishra', 'Aditya Mishra', 'KM741324', '19820', 'Modern School, Barakhamba Road, New Delhi', 'XII', 81, 85, 99, 90, 95, 90.0, 'PASS'],
        ['78693139', 'Chirag Deshmukh', 'Saanvi Deshmukh', 'Vikram Deshmukh', 'CD786931', '62190', 'Loyola School, Beldih, Jamshedpur', 'XII', 88, 91, 98, 86, 89, 90.4, 'PASS'],
        ['71488011', 'Swati Kulkarni', 'Tanvi Kulkarni', 'Gaurav Kulkarni', 'SK714880', '71025', 'Don Bosco High School, Matunga, Mumbai', 'XII', 92, 78, 80, 83, 85, 83.6, 'PASS'],
        ['52532997', 'Siddharth Menon', 'Pari Menon', 'Rudra Menon', 'SM525329', '42901', 'Bhavan Vidyalaya, Sector 27, Chandigarh', 'XII', 86, 96, 79, 70, 93, 84.8, 'PASS'],
        ['90538509', 'Kriti Singh', 'Khushi Singh', 'Sameer Singh', 'KS905385', '33419', 'DAV Public School, Sector 14, Gurugram', 'XII', 87, 95, 85, 82, 85, 86.8, 'PASS'],
        ['67720433', 'Neha Tiwari', 'Isha Tiwari', 'Nikhil Tiwari', 'NT677204', '71025', 'Don Bosco High School, Matunga, Mumbai', 'XII', 78, 82, 96, 88, 83, 85.4, 'PASS'],
        ['16779404', 'Karan Kapoor', 'Meera Kapoor', 'Vihaan Kapoor', 'KK167794', '11024', 'Kendriya Vidyalaya No. 1, Delhi Cantt', 'XII', 98, 85, 92, 91, 96, 92.4, 'PASS'],
        ['77843276', 'Tushar Ghosh', 'Kriti Ghosh', 'Sameer Ghosh', 'TG778432', '54019', 'The Heritage School, Sector 62, Gurugram', 'XII', 98, 78, 86, 81, 99, 88.4, 'PASS'],
        ['93115596', 'Rohan Chopra', 'Sunita Chopra', 'Ankit Chopra', 'RC931155', '48210', 'Amity International School, Sector 44, Noida', 'XII', 92, 78, 77, 71, 98, 83.2, 'PASS'],
        ['32080062', 'Tushar Das', 'Avani Das', 'Krishna Das', 'TD320800', '24150', 'The Mother International School, Sri Aurobindo Marg, New Delhi', 'XII', 79, 80, 82, 95, 87, 84.6, 'PASS'],
        ['73801527', 'Advik Mishra', 'Poonam Mishra', 'Deepak Mishra', 'AM738015', '18430', 'Sanskriti School, Chanakyapuri, New Delhi', 'XII', 82, 81, 82, 87, 84, 83.2, 'PASS'],
        ['75403110', 'Pranav Roy', 'Geeta Roy', 'Krishna Roy', 'PR754031', '19820', 'Modern School, Barakhamba Road, New Delhi', 'XII', 87, 73, 89, 72, 82, 80.6, 'PASS'],
    ];

        @mysqli_query($conn, "TRUNCATE TABLE cbse_results");
        foreach ($records as $rec) {
            $r = mysqli_real_escape_string($conn, $rec[0]);
            $sn = mysqli_real_escape_string($conn, $rec[1]);
            $mn = mysqli_real_escape_string($conn, $rec[2]);
            $fn = mysqli_real_escape_string($conn, $rec[3]);
            $admit = mysqli_real_escape_string($conn, $rec[4]);
            $sno = mysqli_real_escape_string($conn, $rec[5]);
            $sname = mysqli_real_escape_string($conn, $rec[6]);
            $tot = $rec[13];
            $stat = mysqli_real_escape_string($conn, $rec[14]);
            @mysqli_query($conn, "INSERT INTO cbse_results (roll_no, student_name, mother_name, father_name, admit_card_id, school_no, school_name, class_grade, sub_english, sub_maths, sub_physics, sub_chemistry, sub_cs, total_percent, result_status) VALUES
                ('$r', '$sn', '$mn', '$fn', '$admit', '$sno', '$sname', 'XII', {$rec[8]}, {$rec[9]}, {$rec[10]}, {$rec[11]}, {$rec[12]}, $tot, '$stat')");
        }
    }

    // 2. Secret Vault Table (CTF Flag & Government System Keys)
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cbse_vault (
        id INT AUTO_INCREMENT PRIMARY KEY,
        secret_key VARCHAR(100) NOT NULL,
        secret_value VARCHAR(255) NOT NULL,
        access_level VARCHAR(50) NOT NULL
    )");

    $chk_v = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM cbse_vault");
    if ($chk_v && ($row = mysqli_fetch_assoc($chk_v)) && $row['c'] == 0) {
        @mysqli_query($conn, "INSERT INTO cbse_vault (secret_key, secret_value, access_level) VALUES
            ('FLAG_CBSE_SQLI', 'FLAG{d1g1l0ck3r_cbse_r3sul7_t1m3_sqli_2026}', 'TOP_SECRET'),
            ('DIGILOCKER_ISSUER_HMAC_KEY', 'digi_hmac_sec_993410fa883b2', 'RESTRICTED'),
            ('CBSE_EXAM_CONTROLLER_MASTER_KEY', 'cbse_ctrl_2026_988124ff', 'INTERNAL_CONFIDENTIAL')");
    }
}
setup_cbse_schema($conn);

// ── Search Handling (Time-based Blind & UNION SQLi) ──────────────────────────
$student_result = null;
$error_message = '';
$search_time = 0;

$roll_no = $_POST['roll_no'] ?? $_GET['roll_no'] ?? '';
$admit_card = $_POST['admit_card_id'] ?? $_GET['admit_card_id'] ?? '';
$school_no = $_POST['school_no'] ?? $_GET['school_no'] ?? '';
$mother_name = $_POST['mother_name'] ?? $_GET['mother_name'] ?? '';

if (!empty($roll_no)) {
    $start_time = microtime(true);

    /**
     * [VULNERABLE: Time-based Blind / UNION / Boolean SQLi]
     * User roll_no is directly concatenated into SQL query without sanitation.
     */
    $sql = "SELECT * FROM cbse_results WHERE roll_no = '$roll_no'";
    
    $res = @mysqli_query($conn, $sql);
    $search_time = round(microtime(true) - $start_time, 4);

    if ($res && mysqli_num_rows($res) > 0) {
        $student_result = mysqli_fetch_assoc($res);
    } else {
        $error_message = "Result not found. Please verify your Roll Number, Admit Card ID, and School Number as per your Admit Card.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DigiLocker — CBSE Class XII Results 2026</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --gov-blue: #0066cc;
            --gov-blue-dark: #004d99;
            --digi-purple: #5c2d91;
            --bg-page: #f4f6f9;
            --text-dark: #212529;
            --border-color: #d8dee4;
        }

        body {
            font-family: 'Noto Sans', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* ── Header Branding ─────────────────────────────────────────────────── */
        .gov-header {
            text-align: center;
            padding: 24px 20px 8px;
        }
        .header-logos {
            display: inline-flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 6px;
        }
        .digilocker-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.65rem;
            font-weight: 800;
            color: #5c2d91;
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        .digilocker-cloud-icon {
            font-size: 1.8rem;
            color: #5c2d91;
        }
        .digilocker-slogan {
            font-size: 0.72rem;
            color: #64748b;
            font-weight: 600;
            display: block;
            margin-top: -6px;
        }
        .gov-disclaimer {
            font-size: 0.8rem;
            color: #334155;
            font-weight: 500;
            margin-top: 4px;
        }

        /* Directory Quick Banner */
        .directory-pill-banner {
            max-width: 680px;
            margin: 10px auto 14px;
            padding: 0 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* ── Main Form Card (Matching Screenshot) ────────────────────────────── */
        .form-card-container {
            max-width: 680px;
            width: 100%;
            margin: 0 auto 30px;
            padding: 0 16px;
        }
        .result-form-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }
        .card-top-header {
            text-align: center;
            padding: 24px 20px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        .cbse-logo-badge {
            width: 64px;
            height: 64px;
            margin: 0 auto 12px;
            background: #e0f2fe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 2rem;
            border: 2px solid #bae6fd;
        }
        .card-main-title {
            font-size: 1.12rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
            letter-spacing: 0.3px;
        }
        .card-sub-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #334155;
            margin: 0;
        }

        /* Form Inputs */
        .card-form-body {
            padding: 24px 32px 32px;
        }
        .form-group-item {
            margin-bottom: 20px;
        }
        .form-label-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
            display: block;
        }
        .form-input-control {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 0.92rem;
            color: #0f172a;
            background-color: #ffffff;
            transition: all 0.2s;
            outline: none;
        }
        .form-input-control:focus {
            border-color: #0066cc;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.12);
        }
        .form-input-readonly {
            background-color: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
        }

        .checkbox-agreement {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #334155;
            margin-bottom: 24px;
        }
        .checkbox-agreement a {
            color: #0066cc;
            text-decoration: underline;
        }

        .btn-submit-result {
            background-color: #0066cc;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 700;
            padding: 12px;
            border: none;
            border-radius: 6px;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit-result:hover {
            background-color: #004d99;
        }

        /* ── Official Mark Sheet Result Card ─────────────────────────────────── */
        .marksheet-card {
            background: #ffffff;
            border: 2px solid #0066cc;
            border-radius: 12px;
            padding: 30px;
            margin-top: 24px;
            position: relative;
        }
        .marksheet-header {
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .digi-verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 50px;
            margin-bottom: 12px;
        }
        .student-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            font-size: 0.9rem;
            margin-bottom: 24px;
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .student-info-grid strong {
            color: #475569;
        }
        .marks-table {
            width: 100%;
            font-size: 0.88rem;
            margin-bottom: 20px;
        }
        .marks-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            padding: 10px;
            border-bottom: 2px solid #cbd5e1;
        }
        .marks-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        /* Footer */
        .gov-footer {
            text-align: center;
            font-size: 0.8rem;
            color: #64748b;
            padding: 20px;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
        }
        .gov-footer a {
            color: #0066cc;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- ── TOP DIGILOCKER GOV HEADER (Matching Screenshot) ──────────────────── -->
    <header class="gov-header">
        <div class="header-logos">
            <i class="bi bi-bank fs-2 text-dark"></i>
            <div>
                <a href="index.php" class="digilocker-logo">
                    <i class="bi bi-cloud-lock2-fill digilocker-cloud-icon"></i> DigiLocker
                </a>
                <span class="digilocker-slogan">Document Wallet to Empower Citizens</span>
            </div>
        </div>
        <div class="gov-disclaimer">
            DigiLocker Issued Certificates are legally valid as per the IT Act, 2000
        </div>
    </header>

    <!-- Quick Link to Directory for Testing -->
    <div class="directory-pill-banner">
        <span class="badge bg-light text-dark border"><i class="bi bi-database-check me-1 text-primary"></i> 75 Student Records Loaded</span>
        <a href="directory.php" class="btn btn-sm btn-outline-primary fw-semibold">
            <i class="bi bi-table me-1"></i> View Student Directory & Test Data
        </a>
    </div>

    <!-- ── MAIN CONTAINER ───────────────────────────────────────────────────── -->
    <main class="form-card-container">

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ── CBSE RESULTS FORM CARD (Matching Screenshot) ─────────────────── -->
        <div class="result-form-card">
            
            <div class="card-top-header">
                <div class="cbse-logo-badge">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <h1 class="card-main-title">CENTRAL BOARD OF SECONDARY EDUCATION</h1>
                <p class="card-sub-title">Examination 2026- Results</p>
            </div>

            <div class="card-form-body">
                <form method="POST" action="index.php">
                    
                    <!-- Roll Number* -->
                    <div class="form-group-item">
                        <label class="form-label-title">Roll Number*</label>
                        <input type="text" name="roll_no" class="form-input-control" placeholder="12345678" value="<?php echo htmlspecialchars($roll_no); ?>" required>
                    </div>

                    <!-- Class (XII) -->
                    <div class="form-group-item">
                        <label class="form-label-title">Class</label>
                        <input type="text" name="class" class="form-input-control form-input-readonly" value="XII" readonly>
                    </div>

                    <!-- Admit Card ID* -->
                    <div class="form-group-item">
                        <label class="form-label-title">Admit Card ID*</label>
                        <input type="text" name="admit_card_id" class="form-input-control" placeholder="AA123456" value="<?php echo htmlspecialchars($admit_card); ?>">
                    </div>

                    <!-- School Number* -->
                    <div class="form-group-item">
                        <label class="form-label-title">School Number*</label>
                        <input type="text" name="school_no" class="form-input-control" placeholder="12345" value="<?php echo htmlspecialchars($school_no); ?>">
                    </div>

                    <!-- Mother's Name* -->
                    <div class="form-group-item">
                        <label class="form-label-title">Mother's Name*</label>
                        <input type="text" name="mother_name" class="form-input-control" placeholder="Enter mother's name as per admit card" value="<?php echo htmlspecialchars($mother_name); ?>">
                    </div>

                    <!-- Agreement Checkbox -->
                    <div class="checkbox-agreement">
                        <input type="checkbox" id="terms" name="terms" checked required>
                        <label for="terms">I have read and agree to <a href="#" onclick="alert('Terms of Service: Verification of authenticated academic documents under IT Act.'); return false;">terms of use</a></label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit-result">
                        <span>Submit</span>
                    </button>
                </form>
            </div>

        </div>

        <!-- ── MARKSHEET DISPLAY (When Found) ───────────────────────────────── -->
        <?php if ($student_result): ?>
            <div class="marksheet-card" id="resultMarksheet">
                
                <div class="marksheet-header">
                    <div class="digi-verified-badge">
                        <i class="bi bi-patch-check-fill"></i> DigiLocker Digitally Verified Document
                    </div>
                    <h3 class="fw-bold fs-5 text-dark m-0">CENTRAL BOARD OF SECONDARY EDUCATION</h3>
                    <p class="text-muted small m-0">Senior School Certificate Examination (Class XII) 2026</p>
                </div>

                <div class="student-info-grid">
                    <div><strong>Roll No:</strong> <?php echo htmlspecialchars($student_result['roll_no']); ?></div>
                    <div><strong>Candidate Name:</strong> <span class="fw-bold text-primary"><?php echo htmlspecialchars($student_result['student_name']); ?></span></div>
                    <div><strong>Mother's Name:</strong> <?php echo htmlspecialchars($student_result['mother_name']); ?></div>
                    <div><strong>Father's Name:</strong> <?php echo htmlspecialchars($student_result['father_name']); ?></div>
                    <div><strong>School:</strong> <?php echo htmlspecialchars($student_result['school_name'] ?? 'Affiliated CBSE School'); ?></div>
                    <div><strong>Result:</strong> <span class="badge bg-success"><?php echo htmlspecialchars($student_result['result_status']); ?></span> (<?php echo htmlspecialchars($student_result['total_percent']); ?>%)</div>
                </div>

                <!-- Marks Table -->
                <table class="marks-table table table-bordered text-center">
                    <thead>
                        <tr>
                            <th>Sub Code</th>
                            <th class="text-start">Subject Name</th>
                            <th>Theory</th>
                            <th>Practical</th>
                            <th>Total Marks</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>301</td>
                            <td class="text-start">ENGLISH CORE</td>
                            <td>76</td>
                            <td>20</td>
                            <td><strong><?php echo (int)($student_result['sub_english'] ?? 95); ?></strong></td>
                            <td>A1</td>
                        </tr>
                        <tr>
                            <td>041</td>
                            <td class="text-start">MATHEMATICS</td>
                            <td>78</td>
                            <td>20</td>
                            <td><strong><?php echo (int)($student_result['sub_maths'] ?? 98); ?></strong></td>
                            <td>A1</td>
                        </tr>
                        <tr>
                            <td>042</td>
                            <td class="text-start">PHYSICS</td>
                            <td>68</td>
                            <td>30</td>
                            <td><strong><?php echo (int)($student_result['sub_physics'] ?? 96); ?></strong></td>
                            <td>A1</td>
                        </tr>
                        <tr>
                            <td>043</td>
                            <td class="text-start">CHEMISTRY</td>
                            <td>65</td>
                            <td>30</td>
                            <td><strong><?php echo (int)($student_result['sub_chemistry'] ?? 94); ?></strong></td>
                            <td>A1</td>
                        </tr>
                        <tr>
                            <td>083</td>
                            <td class="text-start">COMPUTER SCIENCE</td>
                            <td>69</td>
                            <td>30</td>
                            <td><strong><?php echo (int)($student_result['sub_cs'] ?? 99); ?></strong></td>
                            <td>A1</td>
                        </tr>
                    </tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
                    <div class="small text-muted">
                        <i class="bi bi-qr-code me-1"></i> Digitally Signed by CBSE Controller of Examinations
                    </div>
                    <button class="btn btn-outline-primary btn-sm fw-semibold" onclick="window.print();">
                        <i class="bi bi-printer me-1"></i> Print Result
                    </button>
                </div>

            </div>
        <?php endif; ?>

    </main>

    <!-- ── FOOTER (Matching Screenshot) ─────────────────────────────────────── -->
    <footer class="gov-footer">
        In case of any query/issue, please contact our <a href="#">Support</a> | <a href="directory.php">Student Records Directory</a>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
