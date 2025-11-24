<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Validate if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Get logged-in customer's ID
$customer_id = $_SESSION['customer_id'];

// Function to fetch orders
function fetchOrders($customer_id)
{
    global $conn;

    // SQL query to fetch orders of the customer
    $sql = "SELECT * FROM tbl_orders WHERE customerid = ? ORDER BY orders_date DESC";

    // Prepare the SQL statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('MySQL prepare error: ' . $conn->error);
    }

    // Bind the customer_id to the SQL statement
    $stmt->bind_param('i', $customer_id);

    // Execute the statement
    if (!$stmt->execute()) {
        die('Execution error: ' . $stmt->error);
    }

    // Return the result
    return $stmt->get_result();
}

// Fetch the orders for the logged-in customer
$orders = fetchOrders($customer_id);

// Handle AJAX request for updating order status
if (isset($_POST['action']) && $_POST['action'] == 'update_order_status') {
    $order_id = $_POST['order_id'] ?? null;
    $order_status = $_POST['order_status'] ?? null;

    if (!$order_id || !$order_status) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
        exit();
    }

    // Avoid unnecessary updates if the status hasn't changed
    $sql = "SELECT orders_status FROM tbl_orders WHERE orders_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if ($order['orders_status'] === $order_status) {
        echo json_encode(['status' => 'info', 'message' => 'Order status is already ' . $order_status]);
        exit();
    }

    // Update the order status
    $sql = "UPDATE tbl_orders SET orders_status = ? WHERE orders_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $order_status, $order_id);
    if ($stmt->execute() === false) {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        exit();
    }

    // Optionally update the customer account status
    $updateCustomerSql = "UPDATE tbl_customer_account SET account_status = ? WHERE customerid = (SELECT customerid FROM tbl_orders WHERE orders_id = ?)";
    $updateStmt = $conn->prepare($updateCustomerSql);
    $updateStmt->bind_param("si", $order_status, $order_id);
    if ($updateStmt->execute() === false) {
        echo json_encode(['status' => 'error', 'message' => $updateStmt->error]);
        exit();
    }

    echo json_encode(['status' => 'success']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../assets/images/gra.png" />
    <link rel="stylesheet" href="../assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="../assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="../assets/vendor/remixicon/fonts/remixicon.css">
</head>
<body>
    <!-- Topbar -->
    <?php include 'topbar.php'; ?>

    <!-- Wrapper Start -->
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="content-page">
            <div class="container-fluid">
                <h1 class="text-center">Your Orders</h1>
                <hr>
                <div class="mt-4">
                    <?php if ($orders->num_rows > 0): ?>
                        <table class="table table-bordered">
                            <thead class="table-primary">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Shipping Address</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['orders_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['shipping_address']); ?></td>
                                        <td><?php echo htmlspecialchars($row['total_amount']); ?></td>
                                        <td>
                                            <?php 
                                            if ($row['orders_status'] === 'Pending') {
                                                echo '<span class="badge bg-warning text-dark">Pending</span>';
                                            } elseif ($row['orders_status'] === 'Out for Delivery') {
                                                echo '<span class="badge bg-info text-light">Out for Delivery</span>';
                                            } elseif ($row['orders_status'] === 'Delivered') {
                                                echo '<span class="badge bg-success">Delivered</span>';
                                            } elseif ($row['orders_status'] === 'Preparing') {
                                                echo '<span class="badge bg-primary">Preparing</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date("Y-m-d H:i", strtotime($row['updated_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">You have no orders yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Backend Bundle JavaScript -->
    <script src="../assets/js/backend-bundle.min.js"></script>
    <!-- app JavaScript -->
    <script src="../assets/js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script for handling order status update -->
    <script>
        document.getElementById('updateStatusForm').addEventListener('submit', function (e) {
            e.preventDefault();

            var orderId = document.getElementById('order_id').value;
            var orderStatus = document.getElementById('order_status').value;

            var formData = new FormData();
            formData.append('action', 'update_order_status');
            formData.append('order_id', orderId);
            formData.append('order_status', orderStatus);

            fetch('order_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log('Server response:', data);
                try {
                    const jsonData = JSON.parse(data);
                    if (jsonData.status === 'success') {
                        alert('Order status updated successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + jsonData.message);
                    }
                } catch (error) {
                    console.error('Failed to parse JSON:', data);
                    alert('Unexpected server response.');
                }
            })
            .catch(error => console.error('Fetch error:', error));
        });
    </script>
</body>
</html>
