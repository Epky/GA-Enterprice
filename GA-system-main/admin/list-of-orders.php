<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Fetch all orders for the customer that are not "Delivered"
function fetchAllOrders($conn)
{
    $sql = "SELECT o.*, c.customer_name 
            FROM tbl_orders o
            JOIN tbl_customer_account c ON o.customerid = c.customerid
            WHERE o.orders_status != 'Delivered'
            ORDER BY o.orders_date DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement for fetching all orders: " . $conn->error);
    }

    $stmt->execute();
    return $stmt->get_result();
}

// Fetch ordered products based on the order_id
function fetchOrderedProducts($order_id, $conn)
{
    $sql = "SELECT p.product_name, p.product_image_1, o.item_quantity, o.product_id, o.orders_id
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

// Handle AJAX request for updating order status
if (isset($_POST['action']) && $_POST['action'] == 'update_order_status') {
    $order_id = $_POST['order_id'];
    $order_status = $_POST['order_status'];

    // Update based on selected status
    if ($order_status === 'Completed') {
        $sql = "UPDATE tbl_orders SET orders_status = 'Delivered', payment_status = 'Paid' WHERE orders_id = ?";
    } elseif ($order_status === 'Out for Delivery') {
        $sql = "UPDATE tbl_orders SET orders_status = 'Out for Delivery' WHERE orders_id = ?";
    } elseif ($order_status === 'Preparing') {
        $sql = "UPDATE tbl_orders SET orders_status = 'Preparing' WHERE orders_id = ?";
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement for updating order status: " . $conn->error);
    }

    $stmt->bind_param("i", $order_id);
    if ($stmt->execute() === false) {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    } else {
        echo json_encode(['status' => 'success']);
    }
    exit();
}


$orders = fetchAllOrders($conn);
?>





<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise - List of Orders</title>
    <!-- Favicon and Stylesheets -->
    <link rel="shortcut icon" href="../assets/images/gra.png" />
    <link rel="stylesheet" href="../assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="../assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="../assets/vendor/remixicon/fonts/remixicon.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
                <div id="orderContainer">
                    <?php if ($orders->num_rows > 0) { ?>
                        <?php while ($order = $orders->fetch_assoc()) { ?>
                            <?php
                            // Fetch ordered products for the current order
                            $ordered_products = fetchOrderedProducts($order['orders_id'], $conn);
                            ?>
                            <div class="combined-card">
                                <div class="image-section">
                                    <?php while ($product = $ordered_products->fetch_assoc()) { ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($product['product_image_1']); ?>" alt="Product Image" class="product-image">
                                    <?php } ?>
                                </div>
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
                                        $ordered_products->data_seek(0);
                                        while ($product = $ordered_products->fetch_assoc()) { ?>
                                            <p><strong>Product Name:</strong> <?php echo htmlspecialchars($product['product_name']); ?></p>
                                            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($product['item_quantity']); ?></p>
                                            <hr>
                                        <?php } ?>
                                    </div>

                                    <!-- Update Order Status Form -->
                                    <div class="order-actions mt-4">
                                        <div class="form-group">
                                            <label for="order_status">Select Status:</label>
                                            <select name="order_status" id="order_status_<?php echo htmlspecialchars($order['orders_id']); ?>" class="form-control">
                                                <option value="Pending">Pending</option>
                                                <option value="Preparing">Preparing</option>
                                                <option value="Completed">Completed</option>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-primary update-order-btn" data-order-id="<?php echo htmlspecialchars($order['orders_id']); ?>">Update Status</button>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="no-orders-message">No product information available for this order.</div>
                    <?php } ?>
                </div>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- AJAX for updating order status -->
    <script>
        $(document).ready(function() {
            $(".update-order-btn").on("click", function() {
                const orderId = $(this).data("order-id");
                const orderStatus = $("#order_status_" + orderId).val();

                $.ajax({
                    url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                    method: "POST",
                    data: {
                        action: "update_order_status",
                        order_id: orderId,
                        order_status: orderStatus
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.status === "success") {
                            Swal.fire({
                                title: 'Order Updated!',
                                text: 'The order status has been updated successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'list-of-orders.php';
                                }
                            });
                        } else if (data.status === "no_order") {
                            window.location.href = 'list-of-orders.php';
                        } else {
                            Swal.fire('Error', 'Failed to update order: ' + data.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error updating order status:", error);
                    }
                });
            });
        });
    </script>
</body>

</html>