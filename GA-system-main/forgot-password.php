<?php
// Start session if not already started
session_start();
include 'sql/sql.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_email = $_POST['customer_email'];

    // Check if the email exists in the database
    $sql = "SELECT * FROM tbl_customer_account WHERE customer_email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $customer_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Email found, allow new password entry
        $_SESSION['reset_email'] = $customer_email;
        header("Location: reset-password.php");
        exit();
    } else {
        // Email not found
        $message = "The email address does not exist in our records.";
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
    <title>Forgot Password</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/images/gra.png" />
    <link rel="stylesheet" href="assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="assets/vendor/remixicon/fonts/remixicon.css">
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
                                            <h2 class="mb-2">Forgot Password</h2>
                                            <p>Enter your registered email to reset your password.</p>
                                            <?php if ($message != "") { ?>
                                                <div class="alert alert-danger"><?php echo $message; ?></div>
                                            <?php } ?>
                                            <form method="POST" action="forgot-password.php">
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="email" name="customer_email" required>
                                                            <label>Email</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Submit</button>
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

</body>

</html>