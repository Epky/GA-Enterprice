<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

$message = "";

// Function to register a new admin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_name = $_POST['admin_name'];
    $admin_mname = $_POST['admin_mname'];
    $admin_lname = $_POST['admin_lname'];
    $admin_username = $_POST['admin_username'];
    $admin_password = $_POST['admin_password'];

    // Check if the username already exists
    $sql_check = "SELECT * FROM tbl_admin_account WHERE admin_username = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $admin_username);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $message = "Username already exists. Please use a different username.";
    } else {
        // Hash the password before storing it
        $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);

        // Insert new admin into the database
        $sql = "INSERT INTO tbl_admin_account (admin_name, admin_mname, admin_lname, admin_username, admin_password, date_register) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $admin_name, $admin_mname, $admin_lname, $admin_username, $hashed_password);

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
    <title>G.A. Ruiz Enterprise - Add Admin Account</title>
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
        .add-admin-container {
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
                <h4>Add New Admin Account</h4>
                <div class="card-body">
                    <form id="addAdminForm" method="POST" action="">
                        <div class="form-group">
                            <label for="admin_name">Admin First Name</label>
                            <input type="text" class="form-control" id="admin_name" name="admin_name" required>
                        </div>
                        <div class="form-group">
                            <label for="admin_mname">Admin Middle Name</label>
                            <input type="text" class="form-control" id="admin_mname" name="admin_mname">
                        </div>
                        <div class="form-group">
                            <label for="admin_lname">Admin Last Name</label>
                            <input type="text" class="form-control" id="admin_lname" name="admin_lname" required>
                        </div>
                        <div class="form-group">
                            <label for="admin_username">Username</label>
                            <input type="text" class="form-control" id="admin_username" name="admin_username" required>
                        </div>
                        <div class="form-group">
                            <label for="admin_password">Password</label>
                            <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Admin Account</button>
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