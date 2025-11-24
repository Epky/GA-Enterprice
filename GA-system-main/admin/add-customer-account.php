<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

$message = "";

// Function to register a new customer
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = $_POST['customer_name'];
    $customer_number = $_POST['customer_number'];
    $customer_email = $_POST['customer_email'];
    $customer_address_1 = $_POST['customer_address_1'];
    $customer_address_2 = $_POST['customer_address_2'];
    $customer_city = $_POST['customer_city'];
    $customer_municipality = $_POST['customer_municipality'];
    $customer_zipcode = $_POST['customer_zipcode'];
    $customer_password = $_POST['customer_password'];

    // Hash the password before storing in the database for security
    $hashed_password = password_hash($customer_password, PASSWORD_BCRYPT);

    // Check if the email already exists
    $sql_check = "SELECT * FROM tbl_customer_account WHERE customer_email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $customer_email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $message = "Email already exists. Please use a different email.";
    } else {
        // Insert new customer into the database
        $sql = "INSERT INTO tbl_customer_account (customer_name, customer_number, customer_email, customer_address_1, customer_address_2, customer_city, customer_municipality, customer_zipcode, customer_password, date_register) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssss", $customer_name, $customer_number, $customer_email, $customer_address_1, $customer_address_2, $customer_city, $customer_municipality, $customer_zipcode, $hashed_password);

        if ($stmt->execute()) {
            $message = "Registration successful! You can now log in.";
            echo "<script>
                    Swal.fire({
                        title: 'Success!',
                        text: '$message',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'dashboard.php';
                    });
                  </script>";
        } else {
            $message = "Error: " . $stmt->error;
            echo "<script>
                    Swal.fire({
                        title: 'Error!',
                        text: '$message',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                  </script>";
        }
        $stmt->close();
    }
    $stmt_check->close();
}

$conn->close();
?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise - Add Customer Account</title>
    <!-- Favicon and Stylesheets -->
    <link rel="shortcut icon" href="../assets/images/gra.png" />
    <link rel="stylesheet" href="../assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="../assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="../assets/vendor/remixicon/fonts/remixicon.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Load SweetAlert before any scripts that use it -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .add-customer-container {
            max-width: 600px;
            margin: 40px auto;
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
                <h4>Add New Customer Account</h4>
                <div class="card-body">
                    <form id="addCustomerForm" method="POST" action="">
                        <div class="form-group">
                            <label for="customer_name">Customer Name</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                        </div>
                        <div class="form-group">
                            <label for="customer_number">Phone Number</label>
                            <input type="text" class="form-control" id="customer_number" name="customer_number" required>
                        </div>
                        <div class="form-group">
                            <label for="customer_email">Email</label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email" required>
                        </div>
                        <div class="form-group">
                            <label for="customer_address_1">Address Line 1</label>
                            <input type="text" class="form-control" id="customer_address_1" name="customer_address_1" required>
                        </div>
                        <div class="form-group">
                            <label for="customer_address_2">Address Line 2</label>
                            <input type="text" class="form-control" id="customer_address_2" name="customer_address_2">
                        </div>
                        <div class="form-group">
                            <label for="customer_city">City</label>
                            <input type="text" class="form-control" id="customer_city" name="customer_city" required>
                        </div>
                        <div class="form-group">
                            <label for="customer_municipality">Municipality</label>
                            <input type="text" class="form-control" id="customer_municipality" name="customer_municipality" required>
                        </div>
                        <div class="form-group">
                            <label for="customer_zipcode">ZIP Code</label>
                            <input type="text" class="form-control" id="customer_zipcode" name="customer_zipcode" required>
                        </div>
                        <div class="form-group">
                            <label for="customer_password">Password</label>
                            <input type="password" class="form-control" id="customer_password" name="customer_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Customer Account</button>
                    </form>
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

    <!-- JavaScript for handling registration messages -->
    <?php if (!empty($message)) : ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: "<?php echo (strpos($message, 'success') !== false) ? 'Success!' : 'Error!'; ?>",
                    text: "<?php echo $message; ?>",
                    icon: "<?php echo (strpos($message, 'success') !== false) ? 'success' : 'error'; ?>",
                    confirmButtonText: 'OK'
                }).then(() => {
                    <?php if (strpos($message, 'success') !== false) : ?>
                        window.location.href = 'dashboard.php';
                    <?php endif; ?>
                });
            });
        </script>
    <?php endif; ?>
</body>

</html>