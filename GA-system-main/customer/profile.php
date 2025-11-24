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

// Fetch the customer's current details
$qryCustomer = $conn->prepare("SELECT * FROM tbl_customer_account WHERE customerid = ?");
$qryCustomer->bind_param("i", $customer_id);
$qryCustomer->execute();
$customerData = $qryCustomer->get_result()->fetch_assoc();

$success_message = '';
$error_message = '';

// Handle the profile update form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnUpdateProfile'])) {
    $customer_name = $_POST['customer_name'];
    $customer_number = $_POST['customer_number'];
    $customer_email = $_POST['customer_email'];
    $customer_address_1 = $_POST['customer_address_1'];
    $customer_address_2 = $_POST['customer_address_2'];
    $customer_city = $_POST['customer_city'];
    $customer_municipality = $_POST['customer_municipality'];
    $customer_zipcode = $_POST['customer_zipcode'];

    // Update customer details
    $qryUpdate = $conn->prepare("UPDATE tbl_customer_account SET customer_name = ?, customer_number = ?, customer_email = ?, customer_address_1 = ?, customer_address_2 = ?, customer_city = ?, customer_municipality = ?, customer_zipcode = ? WHERE customerid = ?");
    $qryUpdate->bind_param("ssssssssi", $customer_name, $customer_number, $customer_email, $customer_address_1, $customer_address_2, $customer_city, $customer_municipality, $customer_zipcode, $customer_id);
    if ($qryUpdate->execute()) {
        $success_message = "Profile updated successfully.";
    } else {
        $error_message = "Failed to update profile. Please try again.";
    }
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
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise - Profile</title>
    <!-- style -->
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
                    <div class="col-lg-12">
                        <div class="card card-block card-stretch card-height">
                            <div class="card-header d-flex justify-content-between">
                                <h4 class="card-title">Update Profile</h4>
                            </div>
                            <div class="card-body">
                                <?php if ($success_message) : ?>
                                    <div class="alert alert-success">
                                        <?php echo $success_message; ?>
                                    </div>
                                <?php elseif ($error_message) : ?>
                                    <div class="alert alert-danger">
                                        <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <label for="customer_name">Full Name</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($customerData['customer_name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="customer_number">Contact Number</label>
                                        <input type="text" class="form-control" id="customer_number" name="customer_number" value="<?php echo htmlspecialchars($customerData['customer_number']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="customer_email">Email</label>
                                        <input type="email" class="form-control" id="customer_email" name="customer_email" value="<?php echo htmlspecialchars($customerData['customer_email']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="customer_address_1">Address Line 1</label>
                                        <input type="text" class="form-control" id="customer_address_1" name="customer_address_1" value="<?php echo htmlspecialchars($customerData['customer_address_1']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="customer_address_2">Address Line 2</label>
                                        <input type="text" class="form-control" id="customer_address_2" name="customer_address_2" value="<?php echo htmlspecialchars($customerData['customer_address_2']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="customer_city">City</label>
                                        <input type="text" class="form-control" id="customer_city" name="customer_city" value="<?php echo htmlspecialchars($customerData['customer_city']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="customer_municipality">Municipality</label>
                                        <input type="text" class="form-control" id="customer_municipality" name="customer_municipality" value="<?php echo htmlspecialchars($customerData['customer_municipality']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="customer_zipcode">Zipcode</label>
                                        <input type="text" class="form-control" id="customer_zipcode" name="customer_zipcode" value="<?php echo htmlspecialchars($customerData['customer_zipcode']); ?>">
                                    </div>
                                    <button type="submit" name="btnUpdateProfile" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
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
</body>

</html>