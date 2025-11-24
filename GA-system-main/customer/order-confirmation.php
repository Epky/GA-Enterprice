<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Get the customer ID from the session
$customer_id = $_SESSION['customer_id'];

// Fetch all orders information for the customer
function fetchAllOrders($customer_id, $conn)
{
    $sql = "SELECT o.*, c.customer_name 
            FROM tbl_orders o
            JOIN tbl_customer_account c ON o.customerid = c.customerid
            WHERE o.customerid = ? 
            ORDER BY o.orders_date DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement for fetching orders: " . $conn->error);
    }

    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    return $stmt->get_result();
}

$orders = fetchAllOrders($customer_id, $conn);

// Fetch ordered products for a specific order_id
function fetchOrderedProducts($order_id, $conn)
{
    $sql = "SELECT p.product_name, p.product_image_1, o.item_quantity, o.product_id
            FROM tbl_orders o
            JOIN tbl_product p ON o.product_id = p.product_id
            WHERE o.orders_id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    return $stmt->get_result();
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise </title>
    <!-- Stylesheets -->
    <link rel="shortcut icon" href="../assets/images/gra.png" />
    <link rel="stylesheet" href="../assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="../assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="../assets/vendor/remixicon/fonts/remixicon.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .combined-card {
            display: flex;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 300px;
            max-height: 300px;
            object-fit: cover;
        }

        .details-section {
            padding: 20px;
            flex: 1;
        }

        .details-section h5 {
            margin-bottom: 20px;
            font-weight: bold;
        }

        .details-section p {
            margin-bottom: 10px;
            color: #333;
        }

        .product-details p.strong-text {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php include 'topbar.php'; ?>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="container-fluid">
                <h4 class="mb-4">Order Confirmation</h4>
                <?php if ($orders->num_rows > 0) { ?>
                    <?php while ($order = $orders->fetch_assoc()) { ?>
                        <?php
                        // Fetch ordered products for the current order
                        $ordered_products = fetchOrderedProducts($order['orders_id'], $conn);
                        ?>
                        <?php if ($ordered_products->num_rows > 0) { ?>
                            <div class="combined-card">
                                <!-- Image on the Left Side -->
                                <div class="image-section">
                                    <?php while ($product = $ordered_products->fetch_assoc()) { ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($product['product_image_1']); ?>" alt="Product Image" class="product-image">
                                    <?php } ?>
                                </div>
                                <!-- Details Section on the Right Side -->
                                <div class="details-section">
                                    <div class="order-details">
                                        <h5>Order Details</h5>
                                        <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['orders_id']); ?></p>
                                        <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['orders_date']); ?></p>
                                        <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                        <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                        <p><strong>Total Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?></p>
                                        <p><strong>Order Status:</strong> <?php echo htmlspecialchars($order['orders_status']); ?></p>
                                        <p><strong>Delivery Type:</strong> <?php echo htmlspecialchars($order['delivery_id']); ?></p>
                                        <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($order['payment_status']); ?></p>
                                        <p><strong>Tracking Number:</strong> <?php echo htmlspecialchars($order['tracking_number']); ?></p>
                                        <p><strong>Products Ordered:</strong></p>
                                        <?php
                                        // Reset pointer to display product details for the same order
                                        $ordered_products->data_seek(0);
                                        while ($product = $ordered_products->fetch_assoc()) { ?>
                                            <p><strong>Product Name:</strong> <?php echo htmlspecialchars($product['product_name']); ?></p>
                                            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($product['item_quantity']); ?></p>
                                            <hr>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                <?php } else { ?>
                    <p>No orders found. Please place an order first.</p>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- JavaScript Dependencies -->
    <script src="../assets/js/backend-bundle.min.js"></script>
    <script src="../assets/js/table-treeview.js"></script>
    <script src="../assets/js/customizer.js"></script>
    <script async src="../assets/js/chart-custom.js"></script>
    <script src="../assets/js/app.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>