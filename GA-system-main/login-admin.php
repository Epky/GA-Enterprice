<?php
// Start session if not already started
session_start();
include 'sql/sql.php';

$message = "";

// Function to handle admin login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_username = $_POST['admin_username'];
    $admin_password = $_POST['admin_password'];

    // Check if the username exists in the database
    $sql = "SELECT * FROM tbl_admin_account WHERE admin_username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch the user row from the database
        $row = $result->fetch_assoc();
        // Get the hashed password from the database
        $hashed_password = $row['admin_password'];

        // Use password_verify to check if the entered password matches the hashed password
        if (password_verify($admin_password, $hashed_password)) {
            // Login successful
            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['admin_username'] = $row['admin_username'];

            // Redirect to admin dashboard
            header("Location: admin/dashboard.php");
            exit();
        } else {
            // Invalid password
            $message = "Invalid username or password. Please try again.";
        }
    } else {
        // Username does not exist
        $message = "Invalid username or password. Please try again.";
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
    <title>Admin Login</title>

    <!-- Favicon and Stylesheets -->
    <link rel="shortcut icon" href="assets/images/gra.png" />
    <link rel="stylesheet" href="assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="assets/vendor/remixicon/fonts/remixicon.css">
</head>

<body class="">

    <!-- Loader Start -->
    <div id="loading">
        <div id="loading-center"></div>
    </div>
    <!-- Loader End -->

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
                                            <h2 class="mb-2">Admin Login</h2>
                                            <p>Sign In to access the admin dashboard.</p>
                                            <?php if ($message != "") { ?>
                                                <div class="alert alert-danger"><?php echo $message; ?></div>
                                            <?php } ?>
                                            <form method="POST" action="login-admin.php">
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="text" name="admin_username" required>
                                                            <label>Username</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-12">
                                                        <div class="floating-label form-group">
                                                            <input class="floating-input form-control" type="password" id="admin_password" name="admin_password" required>
                                                            <label>Password</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-12">
                                                        <div class="form-check">
                                                            <input type="checkbox" class="form-check-input" id="showPassword" onclick="togglePassword()">
                                                            <label class="form-check-label" for="showPassword">Show Password</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Login</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="col-lg-5 content-right">
                                        <img src="assets/images/gra.png" class="img-fluid image-right" alt="Admin Login">
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
            var passwordField = document.getElementById("admin_password");
            if (passwordField.type === "password") {
                passwordField.type = "text";
            } else {
                passwordField.type = "password";
            }
        }
    </script>
</body>

</html>