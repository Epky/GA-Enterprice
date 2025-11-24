<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get the admin ID from the session
$admin_id = $_SESSION['admin_id'];

// Fetch the admin's current details
$qryAdmin = $conn->prepare("SELECT * FROM tbl_admin_account WHERE admin_id = ?");
$qryAdmin->bind_param("i", $admin_id);
$qryAdmin->execute();
$adminData = $qryAdmin->get_result()->fetch_assoc();

// DEBUG: Check if the $adminData array contains data
// Uncomment the line below to verify that the database query is fetching data correctly
// print_r($adminData); exit;

if (!$adminData) {
    echo "No data found for admin_id = " . $admin_id;
    exit();
}

$success_message = '';
$error_message = '';

// Handle the profile update form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnUpdateProfile'])) {
    $admin_name = $_POST['admin_name'];
    $admin_mname = $_POST['admin_mname'];
    $admin_lname = $_POST['admin_lname'];
    $admin_username = $_POST['admin_username'];
    $admin_password = $_POST['admin_password'];  // You might want to hash this for security

    // Update admin details
    $qryUpdate = $conn->prepare("UPDATE tbl_admin_account SET admin_name = ?, admin_mname = ?, admin_lname = ?, admin_username = ?, admin_password = ? WHERE admin_id = ?");
    $qryUpdate->bind_param("sssssi", $admin_name, $admin_mname, $admin_lname, $admin_username, $admin_password, $admin_id);

    if ($qryUpdate->execute()) {
        // Update session variables if needed
        $_SESSION['admin_username'] = $admin_username;
        $success_message = "Profile updated successfully.";
    } else {
        $error_message = "Failed to update profile. Please try again.";
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Profile</title>
    <!-- Favicon -->
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
                                        <label for="admin_name">First Name</label>
                                        <input type="text" class="form-control" id="admin_name" name="admin_name" value="<?php echo htmlspecialchars($adminData['admin_name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="admin_mname">Middle Name</label>
                                        <input type="text" class="form-control" id="admin_mname" name="admin_mname" value="<?php echo htmlspecialchars($adminData['admin_mname']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="admin_lname">Last Name</label>
                                        <input type="text" class="form-control" id="admin_lname" name="admin_lname" value="<?php echo htmlspecialchars($adminData['admin_lname']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="admin_username">Username</label>
                                        <input type="text" class="form-control" id="admin_username" name="admin_username" value="<?php echo isset($adminData['admin_username']) ? htmlspecialchars($adminData['admin_username']) : ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="admin_password">Password</label>
                                        <input type="password" class="form-control" id="admin_password" name="admin_password" value="<?php echo isset($adminData['admin_password']) ? htmlspecialchars($adminData['admin_password']) : ''; ?>" required>
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