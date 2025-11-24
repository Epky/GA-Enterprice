<?php
// Start session if not already started
session_start();
include 'sql/sql.php';

$message = "";
$alert_type = ""; // For SweetAlert type (success/error)

// Check if the email session exists
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $customer_email = $_SESSION['reset_email'];

    if ($new_password == $confirm_password) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password in the database
        $sql = "UPDATE tbl_customer_account SET customer_password = ? WHERE customer_email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hashed_password, $customer_email);

        if ($stmt->execute()) {
            // Clear session and redirect to login with a success SweetAlert
            unset($_SESSION['reset_email']);
            $message = "Password updated successfully.";
            $alert_type = "success";
        } else {
            // Handle database update error
            $message = "An error occurred while updating the password. Please try again.";
            $alert_type = "error";
        }

        $stmt->close();
    } else {
        // Passwords do not match, show error SweetAlert
        $message = "Passwords do not match. Please try again.";
        $alert_type = "error";
    }
}

$conn->close();
?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Reset Password</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico" />
    <link rel="stylesheet" href="assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="assets/vendor/remixicon/fonts/remixicon.css">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body>

    <div class="wrapper">
        <section class="login-content">
            <div class="container">
                <div class="row align-items-center justify-content-center height-self-center">
                    <div class="col-lg-8">
                        <div class="card auth-card">
                            <div class="card-body p-0">
                                <div class="d-flex align-items-center auth-content">
                                    <div class="col-lg-12 align-self-center">
                                        <div class="p-3">
                                            <h2 class="mb-2">Reset Password</h2>
                                            <p>Enter your new password below.</p>
                                            <form method="POST" action="reset-password.php">
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="password" name="new_password" required>
                                                            <label>New Password</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-12">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="password" name="confirm_password" required>
                                                            <label>Confirm Password</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Reset Password</button>
                                                <p class="mt-3">
                                                    Remembered your password? <a href="login.php" class="text-primary">Sign In</a>
                                                </p>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Display SweetAlert based on the message -->
    <script>
        <?php if (!empty($message)) { ?>
            Swal.fire({
                icon: '<?php echo $alert_type; ?>',
                title: '<?php echo $alert_type == "success" ? "Success" : "Error"; ?>',
                text: '<?php echo $message; ?>',
                confirmButtonText: '<?php echo $alert_type == "success" ? "Proceed to Login" : "Try Again"; ?>'
            }).then((result) => {
                <?php if ($alert_type == 'success') { ?>
                    window.location.href = "login.php";
                <?php } ?>
            });
        <?php } ?>
    </script>

</body>

</html>