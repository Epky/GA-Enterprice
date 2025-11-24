<?php
// Start session if not already started
session_start();
include '../sql/sql.php';


// Function to fetch total pending orders from tbl_orders
function fetchTotalPendingOrders()
{
    global $conn;

    $sql = "SELECT COUNT(*) as total_pending FROM tbl_orders WHERE orders_status = 'Pending'";
    $result = $conn->query($sql);
    $data = $result->fetch_assoc();
    return $data['total_pending'];
}

// Function to fetch total number of customer accounts
function fetchTotalCustomerAccounts()
{
    global $conn;

    $sql = "SELECT COUNT(*) as total_customers FROM tbl_customer_account";
    $result = $conn->query($sql);
    $data = $result->fetch_assoc();
    return $data['total_customers'];
}

// Function to fetch total number of products
function fetchTotalProducts()
{
    global $conn;

    $sql = "SELECT COUNT(*) as total_products FROM tbl_product";
    $result = $conn->query($sql);
    $data = $result->fetch_assoc();
    return $data['total_products'];
}

// Function to fetch total number of admin accounts
function fetchTotalAdminAccounts()
{
    global $conn;

    $sql = "SELECT COUNT(*) as total_admins FROM tbl_admin_account";
    $result = $conn->query($sql);
    $data = $result->fetch_assoc();
    return $data['total_admins'];
}

// Function to fetch total sales from tbl_orders where payment_status is "Paid"
function fetchTotalSales()
{
    global $conn;

    $sql = "SELECT SUM(total_amount) as total_sales FROM tbl_orders WHERE payment_status = 'Paid'";
    $result = $conn->query($sql);
    $data = $result->fetch_assoc();
    return $data['total_sales'] ?? 0; // Return 0 if no data is found
}

// Function to fetch available years for the sales chart
function fetchAvailableYears()
{
    global $conn;

    $sql = "SELECT DISTINCT YEAR(orders_date) AS year FROM tbl_orders WHERE payment_status = 'Paid' ORDER BY year DESC";
    $result = $conn->query($sql);

    if (!$result) {
        die("Error in SQL query: " . $conn->error . " - Query: " . $sql);
    }

    $years = [];
    while ($row = $result->fetch_assoc()) {
        $years[] = $row['year'];
    }
    return $years;
}
// Function to fetch total number of delivered orders from tbl_orders
function fetchTotalDeliveredOrders()
{
    global $conn;

    $sql = "SELECT COUNT(*) as total_delivered FROM tbl_orders WHERE orders_status = 'Delivered'";
    $result = $conn->query($sql);
    $data = $result->fetch_assoc();
    return $data['total_delivered'];
}

// Assuming admin details are stored in session
$admin_name = $_SESSION['admin_name'] ?? 'Admin'; // Default to 'Admin' if session data is unavailable

// Fetch the total number of delivered orders
$total_delivered_orders = fetchTotalDeliveredOrders();

// Fetch the totals
$total_pending_orders = fetchTotalPendingOrders();
$total_customer_accounts = fetchTotalCustomerAccounts();
$total_products = fetchTotalProducts();
$total_admin_accounts = fetchTotalAdminAccounts();
$total_sales = fetchTotalSales();
$available_years = fetchAvailableYears();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise </title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/images/gra.png" />
    <link rel="stylesheet" href="../assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="../assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="../assets/vendor/remixicon/fonts/remixicon.css">
</head>

<body class="">
    <!-- loader Start -->
    <div id="loading">
        <div id="loading-center"></div>
    </div>
    <?php include 'topbar.php'; ?>
    <!-- loader END -->
    <!-- Wrapper Start -->
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card card-transparent card-block card-stretch card-height border-none">
                            <div class="card-body p-0 mt-lg-2 mt-0">
                                <h3 class="mb-3">Hi <?php echo htmlspecialchars($admin_name); ?>,</h3>
                                <p class="mb-0 mr-4">
                                    <span id="greeting">Good Morning</span> <br>
                                    <span id="currentDateTime"></span> <br>
                                    Your dashboard gives you views of key performance or business processes.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="row">
                            <!-- Total Sales -->
                            <div class="col-lg-4 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-4 card-total-sale">
                                            <div class="icon iq-icon-box-2 bg-info-light">
                                                <img src="../assets/images/user/total-sale.png" class="img-fluid" alt="image">
                                            </div>
                                            <div>
                                                <p class="mb-2">Total Sales</p>
                                                <h4><?php echo number_format($total_sales, 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Total Pending Orders -->
                            <div class="col-lg-4 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-4 card-total-sale">
                                            <div class="icon iq-icon-box-2 bg-info-light">
                                                <img src="../assets/images/user/pending.png" class="img-fluid" alt="image">
                                            </div>
                                            <div>
                                                <p class="mb-2">Total Pending</p>
                                                <h4><?php echo $total_pending_orders; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Total Customer Accounts -->
                            <div class="col-lg-4 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-4 card-total-sale">
                                            <div class="icon iq-icon-box-2 bg-warning-light">
                                                <img src="../assets/images/user/total-account.png" class="img-fluid" alt="image">
                                            </div>
                                            <div>
                                                <p class="mb-2"> Customer Accounts</p>
                                                <h4><?php echo $total_customer_accounts; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Total -->
                            <div class="col-lg-4 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-4 card-total-sale">
                                            <div class="icon iq-icon-box-2 bg-info-light">
                                                <img src="../assets/images/user/total-order.png" class="img-fluid" alt="image">
                                            </div>
                                            <div>
                                                <p class="mb-2">Total Product</p>
                                                <h4><?php echo $total_products; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Total Admin Accounts -->
                            <div class="col-lg-4 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-4 card-total-sale">
                                            <div class="icon iq-icon-box-2 bg-success-light">
                                                <img src="../assets/images/user/total-account.png" class="img-fluid" alt="image">
                                            </div>
                                            <div>
                                                <p class="mb-2"> Admin Accounts</p>
                                                <h4><?php echo $total_admin_accounts; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-4 card-total-sale">
                                            <div class="icon iq-icon-box-2 bg-success-light">
                                                <img src="../assets/images/user/cargo.png" class="img-fluid" alt="image">
                                            </div>
                                            <div>
                                                <p class="mb-2">Total Sold Items</p>
                                                <h4><?php echo $total_delivered_orders; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Per Year Chart -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card card-block card-stretch card-height">
                            <div class="card-header d-flex justify-content-between">
                                <div class="header-title">
                                    <h4 class="card-title">Sales Per Year</h4>
                                </div>
                                <div class="card-header-toolbar d-flex align-items-center">
                                    <div class="dropdown">
                                        <select id="yearDropdown" class="form-control">
                                            <?php
                                            // Populate the dropdown with available years
                                            foreach ($available_years as $year) {
                                                echo "<option value='$year'>$year</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="yearlySalesChart" style="height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Page end  -->
            </div>
        </div>
    </div>

    <!-- Backend Bundle JavaScript -->
    <script src="../assets/js/backend-bundle.min.js"></script>

    <!-- Table Treeview JavaScript -->
    <script src="../assets/js/table-treeview.js"></script>

    <!-- Chart Custom JavaScript -->
    <script src="../assets/js/customizer.js"></script>

    <!-- Chart Custom JavaScript -->
    <script async src="../assets/js/chart-custom.js"></script>

    <!-- app JavaScript -->
    <script src="../assets/js/app.js"></script>

    <!-- Backend Bundle JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            const yearDropdown = document.getElementById("yearDropdown");
            const yearlySalesChartCtx = document.getElementById("yearlySalesChart").getContext("2d");
            let yearlySalesChart;

            // Fetch sales data based on selected year
            function fetchSalesData(year) {
                fetch(`fetch_sales_data.php?year=${year}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0 || data.every(value => value === 0)) {
                            // If there's no data or all values are zero, clear the chart
                            updateYearlySalesChart([]);
                        } else {
                            if (yearlySalesChart) {
                                updateYearlySalesChart(data);
                            } else {
                                initializeYearlySalesChart(data);
                            }
                        }
                    })
                    .catch(error => console.error("Error fetching sales data:", error));
            }

            // Initialize the chart
            function initializeYearlySalesChart(data) {
                yearlySalesChart = new Chart(yearlySalesChartCtx, {
                    type: 'bar',
                    data: {
                        labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                        datasets: [{
                            label: 'Total Sales',
                            data: data,
                            backgroundColor: 'rgba(75, 192, 192, 0.6)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Update the chart data
            function updateYearlySalesChart(data) {
                if (data.length === 0) {
                    // Clear data if no valid values are provided
                    yearlySalesChart.data.datasets[0].data = new Array(12).fill(0);
                } else {
                    yearlySalesChart.data.datasets[0].data = data;
                }
                yearlySalesChart.update();
            }

            // Event listener for year dropdown change
            yearDropdown.addEventListener("change", function() {
                const selectedYear = yearDropdown.value;
                fetchSalesData(selectedYear);
            });

            // Fetch and initialize chart with data for the first available year
            if (yearDropdown.options.length > 0) {
                const initialYear = yearDropdown.value;
                fetchSalesData(initialYear);
            }
        });

        function updateDateTime() {
            const currentDate = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('currentDateTime').innerHTML = currentDate.toLocaleString('en-US', options);

            // Update greeting based on the time
            const hours = currentDate.getHours();
            let greeting = "Good Morning";
            if (hours >= 12 && hours < 18) {
                greeting = "Good Afternoon";
            } else if (hours >= 18 || hours < 6) {
                greeting = "Good Evening";
            }
            document.getElementById('greeting').innerHTML = greeting;
        }

        // Update the date and time every second
        setInterval(updateDateTime, 1000);
        // Initialize immediately
        updateDateTime();
    </script>
</body>

</html>