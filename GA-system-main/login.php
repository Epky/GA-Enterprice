<?php
// Start session if not already started
session_start();
include 'sql/sql.php';

$message = "";

// Function to handle customer login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_email = $_POST['customer_email'];
    $customer_password = $_POST['password'];

    // Check if the email exists
    $sql = "SELECT * FROM tbl_customer_account WHERE customer_email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $customer_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Email exists, now check the password
        $row = $result->fetch_assoc();

        // Verify the password using password_verify()
        if (password_verify($customer_password, $row['customer_password'])) {
            // Login successful, update online_offline_status to 1
            $customer_id = $row['customerid'];

            $update_status_sql = "UPDATE tbl_customer_account SET online_offline_status = 1 WHERE customerid = ?";
            $stmt_update = $conn->prepare($update_status_sql);
            $stmt_update->bind_param("i", $customer_id);
            $stmt_update->execute();

            // Store customer information in the session
            $_SESSION['customer_id'] = $customer_id;
            $_SESSION['customer_name'] = $row['customer_name'];

            // Redirect to customer dashboard
            header("Location: customer/dashboard.php");
            exit();
        } else {
            // Invalid password
            $message = "Invalid email or password. Please try again.";
        }
    } else {
        // Invalid email
        $message = "Invalid email or password. Please try again.";
    }

    $stmt->close();
}

$conn->close();

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login</title>

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
                                            <h2 class="mb-2">Log In</h2>
                                            <p>Sign In to stay connected.</p>
                                            <?php if ($message != "") { ?>
                                                <div class="alert alert-danger"><?php echo $message; ?></div>
                                            <?php } ?>
                                            <form method="POST" action="login.php">
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="email" name="customer_email" required>
                                                            <label>Email</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-12">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="password" id="password" name="password" required>
                                                            <label>Password</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-12">
                                                        <div class="form-check">
                                                            <input type="checkbox" class="form-check-input" id="showPassword" onclick="togglePassword()">
                                                            <label class="form-check-label" for="showPassword">Show Password</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="custom-control custom-checkbox mb-3">
                                                            <input type="checkbox" class="custom-control-input" id="customCheck1">
                                                            <label class="custom-control-label control-label-1" for="customCheck1">Remember Me</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <a href="forgot-password.php" class="text-primary float-right">Forgot Password?</a>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Sign In</button>
                                                <p class="mt-3">
                                                    Create an Account <a href="register.php" class="text-primary">Sign Up</a>
                                                </p>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="col-lg- 5 content-right">
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

    <!-- Show Password Script -->
    <script>
        function togglePassword() {
            var passwordField = document.getElementById("password");
            if (passwordField.type === "password") {
                passwordField.type = "text";
            } else {
                passwordField.type = "password";
            }
        }
    </script>

</body>

</html>