<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Function to fetch product logs
function fetchProductLogs($product_id)
{
    global $conn;

    $productName = "";

    // Check if the product exists and get the product name
    $productCheckQuery = "SELECT product_name FROM tbl_product WHERE product_id = ?";
    if ($stmt = $conn->prepare($productCheckQuery)) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->bind_result($productName);
        $stmt->fetch();
        $stmt->close();
    } else {
        die("Error preparing the product check query: " . $conn->error);
    }

    // Check if product exists
    if (!empty($productName)) {
        // Fetch the product logs if the product exists
        $sql = "SELECT log_id, product_id, change_type, quantity, date_added FROM tbl_inventory_log WHERE product_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return ['logs' => $result, 'productName' => $productName];
        } else {
            die("Error preparing the logs query: " . $conn->error);
        }
    } else {
        return false; // Product doesn't exist
    }
}

$alertMessage = "";
$alertType = "";

// Get the product_id from the URL to display logs for a specific product
if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    $data = fetchProductLogs($product_id);
    if ($data) {
        $logs = $data['logs'];
        $productName = $data['productName'];
    } else {
        $alertMessage = "Product not found.";
        $alertType = "error";
    }
} else {
    $alertMessage = "No product selected.";
    $alertType = "error";
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise - Product Logs</title>

    <link rel="shortcut icon" href="../assets/images/gra.png" />
    <link rel="stylesheet" href="../assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="../assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="../assets/vendor/remixicon/fonts/remixicon.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
    <style>
        .log-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card-header button {
            text-align: left;
            width: 100%;
            color: #007bff;
            font-size: 16px;
            font-weight: bold;
        }

        .card-header button:hover {
            text-decoration: none;
            color: #0056b3;
        }

        .no-logs {
            font-size: 16px;
            color: #666;
        }
    </style>
</head>

<body>
    <?php include 'topbar.php'; ?>

    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="container-fluid">
                <h4 class="mb-4">Inventory Logs for <?php echo isset($productName) ? htmlspecialchars($productName) : ''; ?> (Product ID: <?php echo isset($product_id) ? htmlspecialchars($product_id) : ''; ?>)</h4>

                <?php if ($alertMessage) { ?>
                    <div class="alert alert-<?php echo $alertType === 'error' ? 'danger' : 'success'; ?>" role="alert">
                        <?php echo $alertMessage; ?>
                    </div>
                <?php } ?>

                <div class="row">
                    <?php
                    if (isset($logs) && $logs->num_rows > 0) {
                        $logCounter = 0;
                        while ($row = $logs->fetch_assoc()) {
                            $logCounter++;
                    ?>
                            <div class="col-12">
                                <div class="accordion" id="logAccordion">
                                    <div class="card log-card">
                                        <div class="card-header" id="heading<?php echo $logCounter; ?>">
                                            <h2 class="mb-0">
                                                <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse<?php echo $logCounter; ?>" aria-expanded="true" aria-controls="collapse<?php echo $logCounter; ?>">
                                                    Log #<?php echo htmlspecialchars($row['log_id']); ?> - <?php echo htmlspecialchars($row['change_type']); ?>
                                                </button>
                                            </h2>
                                        </div>

                                        <div id="collapse<?php echo $logCounter; ?>" class="collapse" aria-labelledby="heading<?php echo $logCounter; ?>" data-parent="#logAccordion">
                                            <div class="card-body">
                                                <div class="log-detail"><strong>Product Name:</strong> <?php echo htmlspecialchars($productName); ?></div>
                                                <div class="log-detail"><strong>Change Type:</strong> <?php echo htmlspecialchars($row['change_type']); ?></div>
                                                <div class="log-detail"><strong>Quantity:</strong> <?php echo htmlspecialchars($row['quantity']); ?></div>
                                                <div class="log-detail"><strong>Date Added:</strong> <?php echo htmlspecialchars($row['date_added']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo "<div class='col-12'><p class='text-center no-logs'>No Logs Found for this Product.</p></div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/backend-bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>