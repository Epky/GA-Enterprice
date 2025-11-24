<?php
// Start session if not already started
session_start();
include 'sql/sql.php';

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
    $customer_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if the password and confirm password match
    if ($customer_password !== $confirm_password) {
        $message = "Passwords do not match. Please try again.";
    } else {
        // Hash password before storing in the database (using BCRYPT)
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

            // Fix the bind_param by including the correct number of parameters and types
            $stmt->bind_param("sssssssss", $customer_name, $customer_number, $customer_email, $customer_address_1, $customer_address_2, $customer_city, $customer_municipality, $customer_zipcode, $hashed_password);

            if ($stmt->execute()) {
                $message = "Registration successful! You can now log in.";
                header("Location: login.php");
                exit();
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

$conn->close();
?>



<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sign Up</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/images/gra.png" />
    <link rel="stylesheet" href="assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="assets/vendor/remixicon/fonts/remixicon.css">
</head>

<body class=" ">

    <!-- loader Start -->
    <div id="loading">
        <div id="loading-center"></div>
    </div>
    <!-- loader END -->

    <div class="wrapper">
        <section class="login-content">
            <div class="container">
                <div class="row align-items-center justify-content-center height-self-center">
                    <div class="col-lg-8">
                        <div class="card auth-card">
                            <div class="card-body p-0">
                                <div class="d-flex align-items-center auth-content">
                                    <div class="col-lg-7 align-self-center">
                                        <div class="p-3">
                                            <h2 class="mb-2">Sign Up</h2>
                                            <p>Create your account.</p>
                                            <?php if ($message != "") { ?>
                                                <div class="alert alert-warning"><?php echo $message; ?></div>
                                            <?php } ?>
                                            <form method="POST" action="register.php">
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="text" name="customer_name" required>
                                                            <label>Full Name</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="email" name="customer_email" required>
                                                            <label>Email</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="text" name="customer_number" required>
                                                            <label>Phone No.</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="text" name="customer_address_1" required>
                                                            <label>Address Line 1</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="text" name="customer_address_2">
                                                            <label>Address Line 2</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="text" name="customer_city" required>
                                                            <label>City</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="text" name="customer_municipality" required>
                                                            <label>Municipality</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="text" name="customer_zipcode" required>
                                                            <label>Zipcode</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="password" name="password" required>
                                                            <label>Password</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="password" name="confirm_password" required>
                                                            <label>Confirm Password</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-12">
                                                        <div class="custom-control custom-checkbox mb-3">
                                                            <input type="checkbox" class="custom-control-input" id="customCheck1" required>
                                                            <label class="custom-control-label" for="customCheck1">I agree with the terms of use</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Sign Up</button>
                                                <p class="mt-3">
                                                    Already have an Account? <a href="login.php" class="text-primary">Sign In</a>
                                                </p>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="col-lg-5 content-right">
                                        <img src="assets/images/gra.png" class="img-fluid image-right" alt="">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Backend Bundle JavaScript -->
    <script src="assets/js/backend-bundle.min.js"></script>
    <!-- Table Treeview JavaScript -->
    <script src="assets/js/table-treeview.js"></script>
    <!-- Chart Custom JavaScript -->
    <script src="assets/js/customizer.js"></script>
    <!-- Chart Custom JavaScript -->
    <script async src="assets/js/chart-custom.js"></script>
    <!-- app JavaScript -->
    <script src="assets/js/app.js"></script>
</body>

</html>