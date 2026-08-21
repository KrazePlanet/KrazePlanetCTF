<?php
// admin_dashboard.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['adminloggedin']) || !$_SESSION['adminloggedin']) {
    header('Location: ../login.php');
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'restaurant';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Colombo');

// Function to calculate earnings
function calculateEarnings($conn, $dateColumn, $startDate, $endDate)
{
    $query = "SELECT SUM(grand_total) AS total FROM orders WHERE $dateColumn BETWEEN ? AND ? AND payment_status = 'successful'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0; // Return 0 if null
}

// Function to calculate total orders for a given period
function calculateTotalOrders($conn, $startDate, $endDate)
{
    $query = "SELECT COUNT(*) AS total_orders FROM orders WHERE order_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total_orders'] ?? 0; // Return 0 if null
}

// Function to calculate total users
function calculateTotalUsers($conn)
{
    $query = "SELECT COUNT(*) AS total_users FROM users";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total_users'] ?? 0; // Return 0 if null
}

// Function to calculate total reservations
function calculateTotalReservations($conn)
{
    $query = "SELECT COUNT(*) AS total_reservations FROM reservations";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total_reservations'] ?? 0; // Return 0 if null
}

// Function to calculate percentage change
function calculateChange($current, $previous)
{
    // Calculate percentage change
    $change = $previous ? (($current - $previous) / $previous) * 100 : 0;

    // Constrain the value between -100 and 100
    if ($change < -100) {
        return -100;
    } elseif ($change > 100) {
        return 100;
    }

    // Return the formatted change with two decimal points
    return number_format($change, 2);
}


// Get total earnings from the beginning of time to today
$totalEarning = calculateEarnings($conn, 'order_date', '1970-01-01 00:00:00', date('Y-m-d') . ' 23:59:59');

// Get earnings for today
$todaysEarning = calculateEarnings($conn, 'order_date', date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59');

// Get total orders for today and yesterday
$todaysOrders = calculateTotalOrders($conn, date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59');
$yesterdaysOrders = calculateTotalOrders($conn, date('Y-m-d', strtotime('-1 day')) . ' 00:00:00', date('Y-m-d', strtotime('-1 day')) . ' 23:59:59');

// Get total orders for the current period
$currentStartDate = '1970-01-01 00:00:00'; // Or any appropriate start date
$currentEndDate = date('Y-m-d') . ' 23:59:59';
$totalOrders = calculateTotalOrders($conn, $currentStartDate, $currentEndDate);

// Get total orders for the previous period
$previousStartDate = date('Y-m-d', strtotime('-1 month')) . ' 00:00:00';
$previousEndDate = date('Y-m-d', strtotime('-1 day')) . ' 23:59:59';
$previousTotalOrders = calculateTotalOrders($conn, $previousStartDate, $previousEndDate);

// Get total users
$totalUsers = calculateTotalUsers($conn);
$previousTotalUsers = calculateTotalUsers($conn) - 100; // Example: 50 users ago; adjust as needed

// Get total reservations
$totalReservations = calculateTotalReservations($conn);
$previousTotalReservations = calculateTotalReservations($conn) - 100;




// Calculate percentage changes
$totalEarningChange = calculateChange($totalEarning, calculateEarnings($conn, 'order_date', '1970-01-01 00:00:00', date('Y-m-d', strtotime('-1 month')) . ' 23:59:59')); // Previous month earnings
$todaysEarningChange = calculateChange($todaysEarning, calculateEarnings($conn, 'order_date', date('Y-m-d', strtotime('-1 day')) . ' 00:00:00', date('Y-m-d', strtotime('-1 day')) . ' 23:59:59')); // Previous day earnings
$totalOrdersChange = calculateChange($totalOrders, $previousTotalOrders);
$totalUsersChange = calculateChange($totalUsers, $previousTotalUsers);
$totalReservationsChange = calculateChange($totalReservations, $previousTotalReservations);
$todaysOrdersChange = calculateChange($todaysOrders, $yesterdaysOrders);

// Ensure variables are defined
$todaysOrders = isset($todaysOrders) ? $todaysOrders : 0;
$todaysOrdersChange = isset($todaysOrdersChange) ? $todaysOrdersChange : 0;
$totalEarningChange = isset($totalEarningChange) ? $totalEarningChange : 0;
$todaysEarningChange = isset($todaysEarningChange) ? $todaysEarningChange : 0;
$totalOrdersChange = isset($totalOrdersChange) ? $totalOrdersChange : 0;
$totalUsersChange = isset($totalUsersChange) ? $totalUsersChange : 0;
$totalReservationsChange = isset($totalReservationsChange) ? $totalReservationsChange : 0;

// Function to get the count of orders by status
function getOrderStatusCounts($conn)
{
    $statuses = ['Pending', 'Processing', 'On the Way', 'Completed', 'Cancelled'];
    $statusCounts = [];
    foreach ($statuses as $status) {
        $query = "SELECT COUNT(*) AS count FROM orders WHERE order_status = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $statusCounts[$status] = $row['count'] ?? 0; // Return 0 if null
    }
    return $statusCounts;
}

// Get the status counts
$statusCounts = getOrderStatusCounts($conn);

// Close connection
$conn->close();
?>
<?php
include 'sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!--poppins-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="index.css">
    <style>
        .content {
            margin-bottom: 40px;
        }
    </style>
</head>

<body>

    <!-- sidebar -->
    <div class="sidebar">
        <button class="close-sidebar" id="closeSidebar">&times;</button>

        <!-- Profile Section -->
        <div class="profile-section">
            <img src="../uploads/<?php echo htmlspecialchars($admin_info['profile_image']); ?>" alt="Profile Picture">
            <div class="info">
                <h3>Welcome Back!</h3>
                <p><?php echo htmlspecialchars($admin_info['firstName']) . ' ' . htmlspecialchars($admin_info['lastName']); ?></p>
            </div>
        </div>

        <!-- Navigation Items -->

        <ul>
            <li><a href="index.php" class="active"><i class="fas fa-chart-line"></i> Overview</a></li>
            <li><a href="admin_menu.php"><i class="fas fa-utensils"></i> Menu Management</a></li>
            <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="reservations.php"><i class="fas fa-calendar-alt"></i> Reservations</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="reviews.php"><i class="fas fa-star"></i> Reviews</a></li>
            <li><a href="staffs.php"><i class="fas fa-users"></i> Staffs</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile Setting</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="header">
            <button id="toggleSidebar" class="toggle-button">
                <i class="fas fa-bars"></i>
            </button>
            <h2><i class="fas fa-chart-line"></i> Overview</h2>
        </div>
        <div class="container">
            <!-- Total Earning Card -->
            <div class="card" data-color="purple">

                <div class="card-content">
                    <h4>Total Earning</h4>
                    <h3>Rs <?php echo number_format($totalEarning); ?></h3>
                    <p class="<?php echo $totalEarningChange > 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $totalEarningChange > 0 ? '▲' : '▼'; ?> <?php echo abs($totalEarningChange); ?>%
                    </p>
                </div>
                <i class="icon-top-right icon fas fa-dollar-sign"></i>
                <canvas id="chart1"></canvas>
            </div>

            <!-- Today's Earning Card -->
            <div class="card" data-color="orange">
                <div class="card-content">
                    <h4>Today's Earning</h4>
                    <h3>Rs <?php echo number_format($todaysEarning); ?></h3>
                    <p class="<?php echo $todaysEarningChange > 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $todaysEarningChange > 0 ? '▲' : '▼'; ?> <?php echo abs($todaysEarningChange); ?>%
                    </p>
                </div>
                <i class="icon-top-right icon fas fa-calendar-day"></i>
                <canvas id="chart2"></canvas>
            </div>

            <!-- Total Orders Card -->
            <div class="card" data-color="l-blue">
                <div class="card-content">
                    <h4>Total Orders</h4>
                    <h3><?php echo number_format($totalOrders); ?></h3> <!-- Display total orders -->
                    <p class="<?php echo $totalOrdersChange > 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $totalOrdersChange > 0 ? '▲' : '▼'; ?> <?php echo abs($totalOrdersChange); ?>%
                    </p>
                </div>
                <i class="icon-top-right icon fas fa-shopping-cart"></i>
                <canvas id="chart5"></canvas>
            </div>

            <!-- Today's Orders Card -->
            <div class="card" data-color="pink">
                <div class="card-content">
                    <h4>Today's Orders</h4>
                    <h3><?php echo number_format($todaysOrders); ?></h3> <!-- Display today's orders -->
                    <p class="<?php echo $todaysOrdersChange > 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $todaysOrdersChange > 0 ? '▲' : '▼'; ?> <?php echo abs($todaysOrdersChange); ?>%
                    </p>
                </div>
                <i class="icon-top-right icon fas fa-calendar-day"></i>
                <canvas id="chart6"></canvas>
            </div>

            <!-- Total User Card -->
            <div class="card" data-color="blue">
                <div class="card-content">
                    <h4>Total Users</h4>
                    <h3><?php echo number_format($totalUsers); ?></h3> <!-- Display total users -->
                    <p class="<?php echo $totalUsersChange > 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $totalUsersChange > 0 ? '▲' : '▼'; ?> <?php echo abs($totalUsersChange); ?>%
                    </p>
                </div>
                <i class="icon-top-right icon fas fa-users"></i>
                <canvas id="chart3"></canvas>
            </div>

            <!-- Reservations Card -->
            <div class="card" data-color="green">
                <div class="card-content">
                    <h4>Total Reservations</h4>
                    <h3><?php echo number_format($totalReservations); ?></h3> <!-- Display total reservations -->
                    <p class="<?php echo $totalReservationsChange > 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $totalReservationsChange > 0 ? '▲' : '▼'; ?> <?php echo abs($totalReservationsChange); ?>%
                    </p>
                </div>
                <i class="icon-top-right icon fas fa-calendar-check"></i>
                <canvas id="chart4"></canvas>
            </div>

        </div>

        <div class="table-chart">
            <div class="table">
                <?php
                // Database connection (update with your actual connection details)
                $servername = "localhost";
                $username = "root";
                $password = "";
                $dbname = "restaurant";

                $conn = new mysqli($servername, $username, $password, $dbname);

                // Check connection
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                // SQL query to get the latest 5 orders
                $sql = "SELECT order_id, firstName, lastName, order_status, grand_total 
        FROM orders 
        ORDER BY order_date DESC 
        LIMIT 6";
                $result = $conn->query($sql);

                // Check if there are results
                if ($result->num_rows > 0) {
                    echo '<div class="latest-orders">';
                    echo '<h2>Latest Orders</h2>';
                    echo '<table>';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>Order ID</th>';
                    echo '<th>Customer Name</th>';
                    echo '<th>Status</th>';
                    echo '<th>Total Amount</th>';
                    echo '<th>Actions</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';

                    // Output data for each row
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row["order_id"]) . '</td>';
                        echo '<td>' . htmlspecialchars($row["firstName"] . " " . $row["lastName"]) . '</td>';
                        echo '<td>' . htmlspecialchars($row["order_status"]) . '</td>';
                        echo '<td>Rs ' . htmlspecialchars($row["grand_total"]) . '</td>';
                        echo '<td>';
                        echo '<button onclick=\'viewDetails(' . $row['order_id'] . ')\'>View Details</button>';
                        echo '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';
                } else {
                    echo "No orders found.";
                }

                $conn->close();
                ?>

            </div>
            <div class="bar-chart">

                <canvas id="orderStatusChart"></canvas>
            </div>
        </div>
        <div class="review-container">
            <div id="chartContainer">
                <h2>Earnings</h2>
                <canvas id="earningsChart"></canvas>
            </div>
            <div class="review-chart-container">
                <h2>Ratings</h2>
                <canvas id="ratingsLineChart"></canvas>
            </div>
        </div>

    </div>
    <?php
    include_once('footer.html');
    ?>
    <script src="sidebar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('fetch_earnings.php')
                .then(response => response.json())
                .then(data => {
                    const ctx = document.getElementById('earningsChart').getContext('2d');

                    const dates = data.dates;
                    const categories = data.categories;
                    const earnings = data.earnings;

                    const datasets = categories.map((category, index) => ({
                        label: category,
                        data: earnings[category],
                        fill: true,
                        borderColor: `hsl(${index * 360 / categories.length}, 70%, 50%)`,
                        backgroundColor: `hsla(${index * 360 / categories.length}, 70%, 70%, 0.5)`,
                        tension: 0.1
                    }));

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: dates,
                            datasets: datasets
                        },
                        options: {
                            scales: {
                                x: {

                                    stacked: true,
                                    title: {
                                        display: true,
                                        text: 'Date'
                                    }
                                },
                                y: {
                                    color: '#fff',
                                    stacked: true,
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Earnings'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error fetching data:', error));
        });
    </script>
    <script>
        function viewDetails(orderId) {
            window.location.href = 'view_order.php?orderId=' + orderId;
        }
        const chartOptions = {
            maintainAspectRatio: false,
            legend: {
                display: false,
            },
            tooltips: {
                enabled: false,
            },
            elements: {
                point: {
                    radius: 0
                },
            },
            scales: {
                xAxes: [{
                    gridLines: false,
                    scaleLabel: false,
                    ticks: {
                        display: false
                    }
                }],
                yAxes: [{
                    gridLines: false,
                    scaleLabel: false,
                    ticks: {
                        display: false,
                        suggestedMin: 0,
                        suggestedMax: 10
                    }
                }]
            }
        };

        // Total Earning Chart
        var ctx1 = document.getElementById('chart1').getContext('2d');
        new Chart(ctx1, {
            type: "line",
            data: {
                labels: [1, 2, 1, 3, 5, 4, 7],
                datasets: [{
                    backgroundColor: "rgba(101, 116, 205, 0.1)",
                    borderColor: "rgba(101, 116, 205, 0.8)",
                    borderWidth: 2,
                    data: [1, 2, 1, 3, 5, 4, 7],
                }],
            },
            options: chartOptions
        });

        // Monthly Earning Chart
        var ctx2 = document.getElementById('chart2').getContext('2d');
        new Chart(ctx2, {
            type: "line",
            data: {
                labels: [2, 3, 2, 7, 6, 4, 5],
                datasets: [{
                    backgroundColor: "rgba(253, 108, 77, 0.1)",
                    borderColor: "rgba(253, 108, 77, 0.8)",
                    borderWidth: 2,
                    data: [2, 3, 2, 7, 6, 4, 5],
                }],
            },
            options: chartOptions
        });

        // Weekly Earning Chart
        var ctx3 = document.getElementById('chart3').getContext('2d');
        new Chart(ctx3, {
            type: "line",
            data: {
                labels: [3, 5, 7, 8, 6, 3, 5],
                datasets: [{
                    backgroundColor: "rgba(60, 142, 245, 0.1)",
                    borderColor: "rgba(60, 142, 245, 0.8)",
                    borderWidth: 2,
                    data: [3, 5, 7, 8, 6, 3, 5],
                }],
            },
            options: chartOptions
        });

        // Today's Earning Chart
        var ctx4 = document.getElementById('chart4').getContext('2d');
        new Chart(ctx4, {
            type: "line",
            data: {
                labels: [3, 5, 2, 8, 7, 3, 5],
                datasets: [{
                    backgroundColor: "rgba(80, 198, 168, 0.1)",
                    borderColor: "rgba(80, 198, 168, 0.8)",
                    borderWidth: 2,
                    data: [3, 5, 2, 8, 7, 3, 5],
                }],
            },
            options: chartOptions
        });

        // Total orders Chart
        var ctx4 = document.getElementById('chart5').getContext('2d');
        new Chart(ctx4, {
            type: "line",
            data: {
                labels: [3, 5, 2, 8, 7, 3, 5],
                datasets: [{
                    backgroundColor: "rgba(54, 162, 235, 0.1)", // Soft teal
                    borderColor: "rgba(54, 162, 235, 0.8)", // Darker teal
                    borderWidth: 2,
                    data: [3, 5, 2, 8, 7, 3, 5],
                }],
            },
            options: chartOptions
        });

        // Today's order Chart
        var ctx4 = document.getElementById('chart6').getContext('2d');
        new Chart(ctx4, {
            type: "line",
            data: {
                labels: [3, 5, 2, 8, 7, 3, 5],
                datasets: [{
                    backgroundColor: "rgba(255, 99, 132, 0.1)", // Soft pink
                    borderColor: "rgba(255, 99, 132, 0.8)", // Darker pink
                    borderWidth: 2,
                    data: [3, 5, 2, 8, 7, 3, 5],
                }],
            },
            options: chartOptions
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Data from PHP
            const statusCounts = <?php echo json_encode($statusCounts); ?>;

            // Prepare data
            const labels = Object.keys(statusCounts);
            const data = Object.values(statusCounts);

            // Create the bar chart
            const ctx = document.getElementById('orderStatusChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Order Statuses',
                        data: data,
                        backgroundColor: [
                            'rgba(60, 142, 245, 0.4)', // Blue
                            'rgba(101, 116, 205, 0.4)', // Purple
                            'rgba(253, 108, 77, 0.4)', // orange
                            'rgba(80, 198, 168, 0.4)', // Green
                            'rgba(255, 0, 0, 0.4)' // Light Blue
                        ],
                        borderColor: [
                            'rgba(60, 142, 245, 0.8)',
                            'rgba(101, 116, 205, 0.8)',
                            'rgba(253, 108, 77, 0.8)',
                            'rgba(80, 198, 168, 0.8)',
                            'rgbargba(255, 0, 0, 0.8)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
    <script>
        // Fetch review data from the PHP script
        fetch('fetch_reviews.php')
            .then(response => response.json())
            .then(data => {
                // Process data for Chart.js
                const labels = [...new Set(data.map(item => item.review_date))]; // Extract unique dates
                const ratings = [1, 2, 3, 4, 5]; // Rating categories
                const datasets = ratings.map(rating => {
                    return {
                        label: `${rating} Star`,
                        data: labels.map(date => {
                            const entry = data.find(item => item.review_date === date && item.rating == rating);
                            return entry ? entry.count : 0;
                        }),
                        borderColor: getColorForRating(rating),
                        backgroundColor: getColorForRating(rating, 0.2),
                        fill: false,
                        tension: 0.1
                    };
                });

                // Create the line chart
                const ctx = document.getElementById('ratingsLineChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        scales: {
                            x: {
                                beginAtZero: true
                            },
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        return tooltipItem.dataset.label + ': ' + tooltipItem.raw;
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching review data:', error));

        // Function to get color for each rating
        function getColorForRating(rating, opacity = 1) {
            const colors = {
                1: 'rgba(255, 99, 132, ',
                2: 'rgba(54, 162, 235, ',
                3: 'rgba(75, 192, 192, ',
                4: 'rgba(255, 206, 86, ',
                5: 'rgba(153, 102, 255, '
            };
            return colors[rating] + opacity + ')';
        }
    </script>

</body>

</html>