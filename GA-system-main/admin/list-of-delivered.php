<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Function to fetch all delivered orders
function fetchDeliveredOrders($conn)
{
    $sql = "SELECT o.orders_id, o.orders_date, o.total_amount, o.delivery_id, c.customer_name
            FROM tbl_orders o
            JOIN tbl_customer_account c ON o.customerid = c.customerid
            WHERE o.orders_status = 'Delivered'
            ORDER BY o.orders_date DESC";

    $result = $conn->query($sql);
    if ($result === false) {
        die("Error fetching delivered orders: " . $conn->error);
    }
    return $result;
}

$deliveredOrders = fetchDeliveredOrders($conn);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise - Delivered Orders List</title>
    <!-- Favicon and Stylesheets -->
    <link rel="shortcut icon" href="../assets/images/gra.png" />
    <link rel="stylesheet" href="../assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="../assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="../assets/vendor/remixicon/fonts/remixicon.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
        .delivered-table {
            width: 100%;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <?php include 'topbar.php'; ?>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="container-fluid">
                <h4 class="mb-4">Delivered Orders List</h4>
                <table id="deliveredTable" class="delivered-table display nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Order Date</th>
                            <th>Customer Name</th>
                            <th>Total Amount</th>
                            <th>Delivery Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($deliveredOrders->num_rows > 0) { ?>
                            <?php while ($order = $deliveredOrders->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['orders_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['orders_date']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($order['delivery_id']); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="5">No delivered orders found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
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
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#deliveredTable').DataTable({
                "scrollX": true, // Enable horizontal scrolling if necessary
                "paging": true,
                "searching": true,
                "ordering": true,
                "info": true
            });
        });
    </script>
</body>

</html>