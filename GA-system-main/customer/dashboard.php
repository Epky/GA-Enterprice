<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Get the customer ID from the session
$customer_id = $_SESSION['customer_id'];



// Assuming the first name, middle name, and last name are stored in the session after login
$firstName = $_SESSION['customer_name'] ?? '';

// Create a full name variable
$fullName = trim("$firstName");

// Get current time in Asia/Manila timezone and determine the appropriate greeting
date_default_timezone_set('Asia/Manila');
$current_hour = date('H');

if ($current_hour >= 5 && $current_hour < 12) {
    $greeting = "Good Morning";
} elseif ($current_hour >= 12 && $current_hour < 18) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}

//problema karon
// Fetch total pending orders from tbl_orders
function fetchTotalPendingOrders($customer_id)
{
    global $conn;

    $sql = "SELECT COUNT(*) as total_pending FROM tbl_orders WHERE customerid = ? AND orders_status = 'Pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return $data['total_pending'];
}

// Fetch total items in cart for the logged-in user from tbl_order
function fetchTotalItemsInCart($customer_id)
{
    global $conn;

    $sql = "SELECT COUNT(*) as total_in_cart FROM tbl_order WHERE customerid = ? AND order_status = 'Wait for Confirmation'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return $data['total_in_cart'];
}

/// and problemax
// Fetch total number of delivered orders for the logged-in user
function fetchTotalDeliveredOrders($customer_id)
{
    global $conn;

    $sql = "SELECT COUNT(*) as total_delivered FROM tbl_orders WHERE customerid = ? AND orders_status = 'Delivered'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return $data['total_delivered'];
}


// Fetch cart items for the logged-in user
function fetchCartItems($customer_id)
{
    global $conn;

    $sql = "SELECT o.order_id, o.item_quantity, o.order_status, p.product_id, p.product_name, p.product_price, p.product_image_1 
            FROM tbl_order o
            JOIN tbl_product p ON o.product_id = p.product_id
            WHERE o.customerid = ? AND o.order_status = 'Wait for Confirmation'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result;
}

// Fetch the cart items
$cart_items = fetchCartItems($customer_id);
// Fetch the total delivered orders count
$total_delivered_orders = fetchTotalDeliveredOrders($customer_id);

// Fetch the totals
$total_pending_orders = fetchTotalPendingOrders($customer_id);
$total_items_in_cart = fetchTotalItemsInCart($customer_id);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise </title>
    <!-- Style -->
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
                                <h3 class="mb-3">
                                    Hi <?php echo htmlspecialchars($fullName); ?>,
                                </h3>
                                <h4 class="text-secondary">
                                    <?php echo $greeting; ?>
                                </h4>
                                <p class="mb-0 mr-4" id="currentTime">Current time: </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="row">
                            <div class="col-lg-4 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-4 card-total-sale">
                                            <div class="icon iq-icon-box-2 bg-info-light">
                                                <img src="../assets/images/user/cargo.png" class="img-fluid" alt="image">
                                            </div>
                                            <div>
                                                <p class="mb-2">Total Orders</p>
                                                <h4><?php echo $total_delivered_orders; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-4 card-total-sale">
                                            <div class="icon iq-icon-box-2 bg-info-light">
                                                <img src="../assets/images/user/pending.png" class="img-fluid" alt="image">
                                            </div>
                                            <div>
                                                <p class="mb-2">Total Pending Orders</p>
                                                <h4><?php echo $total_pending_orders; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-4 card-total-sale">
                                            <div class="icon iq-icon-box-2 bg-danger-light">
                                                <img src="../assets/images/user/total-item.png" class="img-fluid" alt="image">
                                            </div>
                                            <div>
                                                <p class="mb-2">Total Items in Cart</p>
                                                <h4><?php echo $total_items_in_cart; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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

    <!-- JavaScript for Real-Time Clock -->
    <script type="text/javascript">
        function updateTime() {
            const now = new Date();
            const options = {
                timeZone: 'Asia/Manila',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            const formattedTime = now.toLocaleTimeString('en-US', options);

            const dayOptions = {
                timeZone: 'Asia/Manila',
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const formattedDate = now.toLocaleDateString('en-US', dayOptions);

            document.getElementById('currentTime').innerHTML = `${formattedTime}<br>${formattedDate}`;
        }

        // Update time every second
        setInterval(updateTime, 1000);
        updateTime(); // Call it once immediately to show time without delay
    </script>
</body>

</html>