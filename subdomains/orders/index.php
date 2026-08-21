<?php
/**
 * ============================================================================
 * FreeMapTools — Find Place With Name (Real-World SQLi Lab)
 * 
 * Features for SQLi Testing:
 *   1. "Find Place With Name" Search Parameter (UNION-based & Error-based SQLi)
 *   2. Geo Coordinates & Radius Distance Filter (Integer / Float SQLi)
 *   3. Leaflet.js / OpenStreetMap Interactive Visualization
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

// ── Schema Initialization ───────────────────────────────────────────────────
function setup_map_schema($conn) {
    // 1. Map Places Table
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS map_places (
        id INT AUTO_INCREMENT PRIMARY KEY,
        place_name VARCHAR(150) NOT NULL,
        country VARCHAR(100) NOT NULL,
        state_province VARCHAR(100) NOT NULL,
        latitude DECIMAL(10,6) NOT NULL,
        longitude DECIMAL(10,6) NOT NULL,
        population INT NOT NULL,
        elevation_meters INT NOT NULL,
        category VARCHAR(50) DEFAULT 'City',
        timezone VARCHAR(50) DEFAULT 'UTC',
        description TEXT NOT NULL
    )");

    $chk_p = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM map_places");
    if ($chk_p && ($row = mysqli_fetch_assoc($chk_p)) && $row['c'] < 20) {
        $places = [
            ['New York', 'United States', 'New York', 40.712776, -74.005974, 8804190, 10, 'Metropolis', 'America/New_York', 'Most populous city in the United States and global cultural capital.'],
            ['New York', 'United Kingdom', 'Tyne and Wear', 55.021000, -1.488000, 2400, 45, 'Village', 'Europe/London', 'A historic village in North Tyneside, England, founded around 1800.'],
            ['London', 'United Kingdom', 'Greater London', 51.507351, -0.127758, 8982000, 15, 'Capital City', 'Europe/London', 'Capital and largest city of the United Kingdom and England.'],
            ['London', 'Canada', 'Ontario', 42.984924, -81.245277, 422324, 251, 'City', 'America/Toronto', 'Major Canadian city located on the Thames River in Southwestern Ontario.'],
            ['Paris', 'France', 'Île-de-France', 48.856613, 2.352222, 2161000, 35, 'Capital City', 'Europe/Paris', 'Capital and most populous city of France.'],
            ['Paris', 'United States', 'Texas', 33.660938, -95.555511, 24476, 183, 'City', 'America/Chicago', 'City in Lamar County, Texas, featuring an Eiffel Tower replica with a cowboy hat.'],
            ['Delhi', 'India', 'National Capital Territory', 28.613939, 77.209023, 33000000, 216, 'Capital City', 'Asia/Kolkata', 'National Capital Territory of India and historic economic center.'],
            ['Delhi', 'United States', 'California', 37.432160, -120.781870, 10755, 36, 'Town', 'America/Los_Angeles', 'Census-designated place in Merced County, San Joaquin Valley, California.'],
            ['Mumbai', 'India', 'Maharashtra', 19.075983, 72.877655, 20961000, 14, 'Metropolis', 'Asia/Kolkata', 'Financial powerhouse and most populous metropolitan area in India.'],
            ['Tokyo', 'Japan', 'Kantō', 35.676192, 139.650311, 14000000, 40, 'Capital City', 'Asia/Tokyo', 'Capital of Japan and center of the Greater Tokyo Area.'],
            ['Sydney', 'Australia', 'New South Wales', -33.868820, 151.209296, 5312000, 19, 'Metropolis', 'Australia/Sydney', 'Coastal metropolis and capital of New South Wales, Australia.'],
            ['Berlin', 'Germany', 'Berlin', 52.520008, 13.404954, 3850809, 34, 'Capital City', 'Europe/Berlin', 'Capital and largest city of Germany by both area and population.'],
            ['Toronto', 'Canada', 'Ontario', 43.653225, -79.383186, 2794356, 76, 'Metropolis', 'America/Toronto', 'Capital of the province of Ontario and the most populous city in Canada.'],
            ['Singapore', 'Singapore', 'Central Region', 1.352083, 103.819839, 5637000, 15, 'City State', 'Asia/Singapore', 'Sovereign island country and city-state in maritime Southeast Asia.'],
            ['Rome', 'Italy', 'Lazio', 41.902782, 12.496366, 2873000, 21, 'Capital City', 'Europe/Rome', 'Capital city of Italy and historic center of the Roman Empire.'],
            ['Rome', 'United States', 'Georgia', 34.257042, -85.164673, 37713, 187, 'City', 'America/New_York', 'Largest city and the county seat of Floyd County, Georgia.'],
            ['San Francisco', 'United States', 'California', 37.774929, -122.419418, 873965, 16, 'City', 'America/Los_Angeles', 'Commercial, financial, and cultural center of Northern California.'],
            ['Washington', 'United States', 'District of Columbia', 38.907192, -77.036873, 689545, 7, 'Capital City', 'America/New_York', 'Federal capital district of the United States.'],
            ['Washington', 'United Kingdom', 'Tyne and Wear', 54.903000, -1.521000, 67085, 48, 'Town', 'Europe/London', 'Ancestral home of the family of George Washington in North East England.']
        ];

        @mysqli_query($conn, "TRUNCATE TABLE map_places");
        foreach ($places as $p) {
            $pn = mysqli_real_escape_string($conn, $p[0]);
            $c = mysqli_real_escape_string($conn, $p[1]);
            $s = mysqli_real_escape_string($conn, $p[2]);
            $cat = mysqli_real_escape_string($conn, $p[7]);
            $tz = mysqli_real_escape_string($conn, $p[8]);
            $desc = mysqli_real_escape_string($conn, $p[9]);
            @mysqli_query($conn, "INSERT INTO map_places (place_name, country, state_province, latitude, longitude, population, elevation_meters, category, timezone, description) VALUES
                ('$pn', '$c', '$s', {$p[3]}, {$p[4]}, {$p[5]}, {$p[6]}, '$cat', '$tz', '$desc')");
        }
    }

    // 2. Secret Vault Table (CTF Flag & Geo API Secret Keys)
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS map_vault (
        id INT AUTO_INCREMENT PRIMARY KEY,
        secret_name VARCHAR(100) NOT NULL,
        secret_value VARCHAR(255) NOT NULL,
        access_tier VARCHAR(50) NOT NULL
    )");

    $chk_v = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM map_vault");
    if ($chk_v && ($row = mysqli_fetch_assoc($chk_v)) && $row['c'] == 0) {
        @mysqli_query($conn, "INSERT INTO map_vault (secret_name, secret_value, access_tier) VALUES
            ('FLAG_FREEMAPTOOLS_SQLI', 'FLAG{fr33_m4p_t00ls_g30_sqli_2026}', 'TOP_SECRET'),
            ('MAPBOX_PRIVATE_ACCESS_TOKEN', 'sk.eyJ1IjoiZnJlZW1hcHRvb2xzIiwiYSI6ImNtODk', 'CONFIDENTIAL'),
            ('GEO_NAMES_PREMIUM_API_KEY', 'geo_master_sec_88491024aa', 'INTERNAL_ONLY')");
    }
}
setup_map_schema($conn);

// ── Search Handling ─────────────────────────────────────────────────────────
$name_to_find = $_GET['name_to_find'] ?? $_GET['name'] ?? 'new york';
$min_population = $_GET['min_population'] ?? '';
$search_results = [];
$error_msg = '';

if ($name_to_find !== '') {
    /**
     * [VULNERABLE: UNION / Search SQL Injection]
     * Parameter name_to_find directly concatenated into SQL.
     * Payloads to test:
     *   1. UNION Injection: ' UNION SELECT 1,secret_name,secret_value,access_tier,40.71,-74.00,1000,10,'Secret','UTC','Secret Data' FROM map_vault-- -
     *   2. Classic Bypass: ' OR 1=1-- -
     */
    $sql = "SELECT * FROM map_places WHERE place_name LIKE '%$name_to_find%' OR country LIKE '%$name_to_find%'";
    
    if (!empty($min_population) && is_numeric($min_population)) {
        $sql .= " AND population >= $min_population";
    }

    $res = @mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $search_results[] = $row;
        }
    } else {
        $error_msg = "Database Query Error: " . mysqli_error($conn);
    }
}

// Convert results to JSON for Leaflet Map Rendering
$markers_json = json_encode($search_results);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Place With Name — FreeMapTools</title>
    
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Leaflet OpenStreetMap CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --fmt-blue: #0072bc;
            --fmt-blue-hover: #005a96;
            --fmt-dark: #222222;
            --fmt-sidebar-bg: #f8f9fa;
            --fmt-border: #e2e8f0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--fmt-dark);
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* ── Top Header (Matching Screenshot) ────────────────────────────────── */
        .fmt-header {
            padding: 16px 28px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--fmt-border);
        }
        .fmt-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .fmt-logo-icon {
            font-size: 2.2rem;
            color: var(--fmt-blue);
        }
        .fmt-brand-text {
            display: flex;
            flex-direction: column;
        }
        .fmt-brand-title {
            font-size: 1.85rem;
            font-weight: 700;
            color: #4a5568;
            line-height: 1.1;
            letter-spacing: -0.5px;
        }
        .fmt-brand-title span {
            color: var(--fmt-blue);
        }
        .fmt-brand-tagline {
            font-size: 0.85rem;
            color: #718096;
            font-style: italic;
        }

        .fmt-header-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .btn-ad-free {
            background-color: #d9534f;
            color: #ffffff;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 4px;
            text-decoration: none;
            border: none;
        }
        .btn-fmt-login {
            background-color: var(--fmt-blue);
            color: #ffffff;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 4px;
            text-decoration: none;
            border: none;
        }

        /* ── Layout Grid ─────────────────────────────────────────────────────── */
        .fmt-container {
            display: flex;
            min-height: calc(100vh - 85px);
        }

        /* Left Sidebar (Matching Screenshot) */
        .fmt-sidebar {
            width: 250px;
            background-color: var(--fmt-sidebar-bg);
            border-right: 1px solid var(--fmt-border);
            padding: 20px 18px;
            flex-shrink: 0;
            font-size: 0.85rem;
        }
        .sidebar-section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--fmt-blue);
            margin-bottom: 8px;
            margin-top: 16px;
        }
        .sidebar-section-title:first-child {
            margin-top: 0;
        }
        .sidebar-menu {
            list-style: none;
            padding-left: 0;
            margin-bottom: 12px;
        }
        .sidebar-menu li {
            margin-bottom: 6px;
        }
        .sidebar-menu a {
            color: #4a5568;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            color: var(--fmt-blue);
            font-weight: 600;
        }
        .sidebar-submenu {
            list-style: none;
            padding-left: 14px;
            margin-top: 4px;
        }
        .sidebar-submenu li {
            margin-bottom: 4px;
        }
        .sidebar-submenu a {
            color: #718096;
            font-size: 0.82rem;
        }

        /* Google Site Search Box */
        .site-search-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 4px 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            margin-top: 6px;
            margin-bottom: 16px;
        }
        .site-search-input {
            border: none;
            outline: none;
            font-size: 0.8rem;
            width: 100%;
        }

        /* Main Content Area */
        .fmt-main {
            flex: 1;
            padding: 24px 32px 60px;
        }
        .main-page-title {
            color: var(--fmt-blue);
            font-size: 1.95rem;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .main-page-subtitle {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 24px;
        }
        .section-subheading {
            color: var(--fmt-blue);
            font-size: 1.35rem;
            font-weight: 600;
            margin-bottom: 14px;
        }

        /* Search Form Input */
        .find-place-form {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px 20px;
            border-radius: 6px;
        }
        .find-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: #334155;
        }
        .find-input {
            width: 320px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 0.92rem;
            outline: none;
        }
        .find-input:focus {
            border-color: var(--fmt-blue);
            box-shadow: 0 0 0 2px rgba(0, 114, 188, 0.15);
        }
        .btn-find-search {
            background-color: var(--fmt-blue);
            color: #ffffff;
            border: none;
            border-radius: 4px;
            padding: 7px 22px;
            font-weight: 600;
            font-size: 0.92rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-find-search:hover {
            background-color: var(--fmt-blue-hover);
        }

        /* ── Leaflet OpenStreetMap Container (Matching Screenshot) ───────────── */
        #map {
            width: 100%;
            height: 480px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 28px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            z-index: 1;
        }

        /* Results Table */
        .results-table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        .results-table {
            margin-bottom: 0;
            font-size: 0.88rem;
        }
        .results-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
        }
    </style>
</head>
<body>

    <!-- ── TOP HEADER (Matching Screenshot) ────────────────────────────────── -->
    <header class="fmt-header">
        <a href="index.php" class="fmt-brand">
            <i class="bi bi-geo-alt-fill fmt-logo-icon"></i>
            <div class="fmt-brand-text">
                <div class="fmt-brand-title">Free<span>MapTools</span></div>
                <div class="fmt-brand-tagline">Maps you can make use of...</div>
            </div>
        </a>

        <div class="fmt-header-actions">
            <span class="text-muted small me-2 d-none d-md-inline">Ad Free Experience</span>
            <a href="#" class="btn-ad-free" onclick="alert('Ad-free premium feature active for lab.'); return false;">Go Ad Free</a>
            <a href="#" class="btn-fmt-login" onclick="alert('User account portal.'); return false;">Login</a>
        </div>
    </header>

    <!-- ── CONTAINER WITH SIDEBAR & MAP ────────────────────────────────────── -->
    <div class="fmt-container">
        
        <!-- ── LEFT SIDEBAR (Matching Screenshot) ──────────────────────────── -->
        <aside class="fmt-sidebar">
            <div class="sidebar-section-title">Map Tools</div>
            <ul class="sidebar-menu">
                <li><a href="index.php">Full List of Map Tools</a></li>
                <li>
                    <a href="index.php" class="active">▼ Popular Map Tools</a>
                    <ul class="sidebar-submenu">
                        <li><a href="#">Radius Around Point</a></li>
                        <li><a href="#">Elevation Where I Am</a></li>
                        <li><a href="#">Find Population on Map</a></li>
                        <li><a href="#">Measure Distance</a></li>
                        <li><a href="#">How Far is it Between</a></li>
                        <li><a href="#">Area Calculator</a></li>
                    </ul>
                </li>
                <li><a href="#">▶ USA Map Tools</a></li>
                <li><a href="#">▶ UK Map Tools</a></li>
            </ul>

            <div class="sidebar-section-title">Site Search</div>
            <div class="site-search-box">
                <i class="bi bi-search text-muted small"></i>
                <input type="text" class="site-search-input" placeholder="ENHANCED BY Google">
            </div>

            <div class="sidebar-section-title">Other</div>
            <ul class="sidebar-menu">
                <li>
                    <a href="#">▼ User Menu</a>
                    <ul class="sidebar-submenu">
                        <li><a href="#">Import Data</a></li>
                        <li><a href="#">Remove All User Data</a></li>
                        <li><a href="#">Settings</a></li>
                        <li><a href="#">About User Menu</a></li>
                    </ul>
                </li>
                <li><a href="#">FAQs</a></li>
                <li><a href="#">About</a></li>
            </ul>
        </aside>

        <!-- ── MAIN MAP & SEARCH AREA (Matching Screenshot) ────────────────── -->
        <main class="fmt-main">

            <h1 class="main-page-title">Find Place With Your Name</h1>
            <p class="main-page-subtitle">Search for a town, city or place with your name.</p>

            <h2 class="section-subheading">Find Place With Name</h2>

            <!-- Search Form -->
            <form method="GET" action="index.php" class="find-place-form">
                <label for="name_to_find" class="find-label">Name to find</label>
                <input type="text" id="name_to_find" name="name_to_find" class="find-input" placeholder="e.g. New York, London, Paris, Rome, Delhi" value="<?php echo htmlspecialchars($name_to_find); ?>" required>
                <button type="submit" class="btn-find-search">Search</button>
            </form>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <!-- ── INTERACTIVE LEAFLET / OPENSTREETMAP ──────────────────────── -->
            <div id="map"></div>

            <!-- ── FOUND PLACES RESULTS TABLE ──────────────────────────────── -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="fw-bold m-0 text-dark"><i class="bi bi-pin-map-fill text-danger me-2"></i>Places Found (<?php echo count($search_results); ?>)</h5>
                <span class="text-muted small">Powered by OpenStreetMap & FreeMapTools Geo Engine</span>
            </div>

            <div class="results-table-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle results-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Place Name</th>
                                <th>Country</th>
                                <th>State / Province</th>
                                <th>Coordinates (Lat, Lng)</th>
                                <th>Population</th>
                                <th>Elevation</th>
                                <th>Timezone</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($search_results) > 0): ?>
                                <?php $i = 1; foreach ($search_results as $row): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><strong class="text-primary"><?php echo htmlspecialchars($row['place_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['country']); ?></td>
                                        <td><?php echo htmlspecialchars($row['state_province']); ?></td>
                                        <td><code><?php echo number_format((float)$row['latitude'], 4); ?>, <?php echo number_format((float)$row['longitude'], 4); ?></code></td>
                                        <td><?php echo number_format((int)$row['population']); ?></td>
                                        <td><?php echo (int)$row['elevation_meters']; ?> m</td>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['timezone']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        No locations found matching "<strong><?php echo htmlspecialchars($name_to_find); ?></strong>". Try searching for another city or town name.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>

    </div>

    <!-- Leaflet JS (Free OpenStreetMap library, no API key needed) -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Map Initialization Script -->
    <script>
        const placesData = <?php echo $markers_json; ?>;

        // Default to New York coordinates if no results
        let defaultLat = 40.7128;
        let defaultLng = -74.0060;
        let defaultZoom = 4;

        if (placesData.length > 0) {
            defaultLat = parseFloat(placesData[0].latitude) || 40.7128;
            defaultLng = parseFloat(placesData[0].longitude) || -74.0060;
            defaultZoom = placesData.length === 1 ? 9 : 3;
        }

        // Initialize Leaflet Map with OpenStreetMap free tile layer
        const map = L.map('map').setView([defaultLat, defaultLng], defaultZoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Add pins/markers for all search results
        const markerGroup = L.featureGroup();

        placesData.forEach(function(place) {
            const lat = parseFloat(place.latitude);
            const lng = parseFloat(place.longitude);

            if (!isNaN(lat) && !isNaN(lng)) {
                const marker = L.marker([lat, lng]).addTo(markerGroup);
                marker.bindPopup(`
                    <div style="font-family:sans-serif; min-width:180px;">
                        <strong style="font-size:1.05rem; color:#0072bc;">${place.place_name}</strong><br>
                        <span style="color:#64748b; font-size:0.82rem;">${place.state_province}, ${place.country}</span>
                        <hr style="margin:6px 0;">
                        <div style="font-size:0.8rem; line-height:1.4;">
                            <strong>Population:</strong> ${parseInt(place.population).toLocaleString()}<br>
                            <strong>Elevation:</strong> ${place.elevation_meters}m<br>
                            <strong>Timezone:</strong> ${place.timezone}
                        </div>
                    </div>
                `);
            }
        });

        markerGroup.addTo(map);

        if (placesData.length > 1) {
            map.fitBounds(markerGroup.getBounds().pad(0.2));
        }
    </script>
</body>
</html>
