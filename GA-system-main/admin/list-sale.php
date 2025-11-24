<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Function to fetch sales based on the filter
function fetchSales($conn, $filter = 'all')
{
    $dateCondition = '';

    switch ($filter) {
        case 'daily':
            $dateCondition = "AND DATE(o.orders_date) = CURDATE()";
            break;
        case 'weekly':
            $dateCondition = "AND WEEK(o.orders_date) = WEEK(CURDATE()) AND YEAR(o.orders_date) = YEAR(CURDATE())";
            break;
        case 'monthly':
            $dateCondition = "AND MONTH(o.orders_date) = MONTH(CURDATE()) AND YEAR(o.orders_date) = YEAR(CURDATE())";
            break;
        case 'yearly':
            $dateCondition = "AND YEAR(o.orders_date) = YEAR(CURDATE())";
            break;
    }

    $sql = "SELECT o.orders_id, o.orders_date, o.total_amount, o.orders_status, c.customer_name
            FROM tbl_orders o
            JOIN tbl_customer_account c ON o.customerid = c.customerid
            WHERE o.payment_status = 'Paid' $dateCondition
            ORDER BY o.orders_date DESC";

    $result = $conn->query($sql);
    if ($result === false) {
        die("Error fetching sales: " . $conn->error);
    }
    return $result;
}

// Handle AJAX request for filtered sales
if (isset($_POST['filter'])) {
    $filter = $_POST['filter'];
    $sales = fetchSales($conn, $filter);

    $response = [];
    $overall_total = 0;

    while ($sale = $sales->fetch_assoc()) {
        $overall_total += $sale['total_amount'];
        $response['sales'][] = $sale;
    }

    $response['overall_total'] = $overall_total;

    echo json_encode($response);
    exit();
}

$sales = fetchSales($conn);
?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sales List</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/images/gra.png" />
    <link rel="stylesheet" href="../assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="../assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="../assets/vendor/remixicon/fonts/remixicon.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
        .sales-table {
            width: 100%;
            margin-top: 20px;
        }

        #printSection {
            display: none;
        }
    </style>
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
                <h4 class="mb-4">Sales List</h4>

                <!-- Dropdown for selecting the filter -->
                <div class="mb-4">
                    <label for="salesFilter">Select Time Frame:</label>
                    <select id="salesFilter" class="form-control" style="width: 200px;">
                        <option value="all">All</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>

                <!-- Print Button -->
                <button id="printButton" class="btn btn-primary mb-4">Print</button>

                <table id="salesTable" class="sales-table display nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Order Date</th>
                            <th>Customer Name</th>
                            <th>Total Amount</th>
                            <th>Order Status</th>
                        </tr>
                    </thead>
                    <tbody id="salesTableBody">
                        <?php if ($sales->num_rows > 0) { ?>
                            <?php foreach ($sales as $sale) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sale['orders_id']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['orders_date']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                    <td>₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($sale['orders_status']); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="5">No sales found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" style="text-align:right">Overall Total:</th>
                            <th id="overallTotal">₱<?php echo number_format(array_sum(array_column(iterator_to_array($sales), 'total_amount')), 2); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
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

    <!-- JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            var salesTable = $('#salesTable').DataTable({
                "scrollX": true, // Enable horizontal scrolling if necessary
                "paging": true,
                "searching": true,
                "ordering": true,
                "info": true
            });

            // Filter sales based on the dropdown selection
            $('#salesFilter').on('change', function() {
                var filter = $(this).val();

                $.ajax({
                    url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                    method: "POST",
                    data: {
                        filter: filter
                    },
                    dataType: "json",
                    success: function(response) {
                        // Clear the table body
                        salesTable.clear().draw();

                        // Populate the table with the filtered data
                        if (response.sales && response.sales.length > 0) {
                            response.sales.forEach(function(sale) {
                                salesTable.row.add([
                                    sale.orders_id,
                                    sale.orders_date,
                                    sale.customer_name,
                                    '₱' + parseFloat(sale.total_amount).toFixed(2),
                                    sale.orders_status
                                ]).draw(false);
                            });
                        } else {
                            salesTable.row.add([
                                '', '', 'No sales found.', '', ''
                            ]).draw(false);
                        }

                        // Update overall total
                        $('#overallTotal').text('₱' + parseFloat(response.overall_total).toFixed(2));
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching sales:", error);
                    }
                });
            });

            // Print the filtered data
            $('#printButton').on('click', function() {
                // Open a new window for printing
                const printWindow = window.open('', '_blank');

                // HTML content to be printed
                const printContent = `
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Print Sales Data</title>
            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
            <style>
                body {
                    margin: 20px;
                }
                h4 {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .table th, .table td {
                    text-align: center;
                }
                .table tfoot th {
                    text-align: right;
                }
            </style>
        </head>
        <body>
            <h4>Sales List</h4>
            <table class="table table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Order ID</th>
                        <th>Order Date</th>
                        <th>Customer Name</th>
                        <th>Total Amount</th>
                        <th>Order Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${$('#salesTableBody').html()} <!-- Populate table body -->
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">Overall Total:</th>
                        <th>${$('#overallTotal').text()}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </body>
        </html>
    `;

                // Write the content to the print window and trigger print
                printWindow.document.open();
                printWindow.document.write(printContent);
                printWindow.document.close();
                printWindow.onload = function() {
                    printWindow.print();
                    printWindow.close();
                };
            });

        });
    </script>
</body>

</html>