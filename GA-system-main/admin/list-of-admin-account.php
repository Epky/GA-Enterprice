<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Fetch admin accounts from the database
$sql = "SELECT * FROM tbl_admin_account";
$adminAccount = $conn->query($sql);

if (!$adminAccount) {
    die("Error fetching admin accounts: " . $conn->error);
}

// Handle Update Admin Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    // Fetch form data for update
    $admin_id = $_POST['admin_id'];
    $admin_name = $_POST['admin_name'];
    $admin_mname = $_POST['admin_mname'];
    $admin_lname = $_POST['admin_lname'];
    $admin_username = $_POST['admin_username'];
    $admin_password = $_POST['admin_password'];

    // Check if password is provided, if not, do not change the password
    if (!empty($admin_password)) {
        // Hash the password before storing it
        $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);
    } else {
        // If password is not provided, use the current password (no change)
        $sql_current = "SELECT admin_password FROM tbl_admin_account WHERE admin_id = ?";
        $stmt_current = $conn->prepare($sql_current);
        $stmt_current->bind_param("i", $admin_id);
        $stmt_current->execute();
        $result_current = $stmt_current->get_result();
        $current_password = $result_current->fetch_assoc()['admin_password'];
        $hashed_password = $current_password; // Retain the existing password if no new password is provided
        $stmt_current->close();
    }

    // Prepare SQL query to update admin information
    $sql = "UPDATE tbl_admin_account 
            SET admin_name = ?, admin_mname = ?, admin_lname = ?, admin_username = ?, admin_password = ? 
            WHERE admin_id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing update statement: " . $conn->error);
    }

    // Bind parameters to the query
    $stmt->bind_param(
        "sssssi",
        $admin_name,
        $admin_mname,
        $admin_lname,
        $admin_username,
        $hashed_password, // Use the hashed password here
        $admin_id
    );

    // Execute the query
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    // Close statement
    $stmt->close();
    exit();
}

// Handle Delete Admin Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $admin_id = $_POST['admin_id'];

    // Prepare SQL query to delete admin
    $sql = "DELETE FROM tbl_admin_account WHERE admin_id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing delete statement: " . $conn->error);
    }

    // Bind parameter
    $stmt->bind_param("i", $admin_id);

    // Execute the query
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    // Close statement
    $stmt->close();
    exit();
}
?>




<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise - Admin Accounts List</title>
    <!-- Favicon and Stylesheets -->
    <link rel="shortcut icon" href="../assets/images/gra.png" />
    <link rel="stylesheet" href="../assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="../assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="../assets/vendor/remixicon/fonts/remixicon.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .admin-table {
            width: 100%;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <?php include 'topbar.php'; ?>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="container-fluid">
                <h4 class="mb-4">Admin Accounts List</h4>
                <table id="adminTable" class="admin-table display nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Admin ID</th>
                            <th>First Name</th>
                            <th>Middle Name</th>
                            <th>Last Name</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Registered At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($adminAccount->num_rows > 0) { ?>
                            <?php while ($admin = $adminAccount->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin['admin_id']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['admin_name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['admin_mname']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['admin_lname']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['admin_username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['admin_password']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['date_register']); ?></td>
                                    <td>
                                        <button class="btn btn-primary updateBtn" data-id="<?php echo htmlspecialchars($admin['admin_id']); ?>" data-toggle="modal" data-target="#updateAdminModal">Update</button>
                                        <button class="btn btn-danger deleteBtn" data-id="<?php echo htmlspecialchars($admin['admin_id']); ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="8">No admin accounts found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Update Admin Modal -->
    <div class="modal fade" id="updateAdminModal" tabindex="-1" role="dialog" aria-labelledby="updateAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="updateAdminForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateAdminModalLabel">Update Admin Information</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="admin_id" name="admin_id">
                        <div class="form-group">
                            <label for="admin_name">First Name</label>
                            <input type="text" class="form-control" id="admin_name" name="admin_name" required>
                        </div>
                        <div class="form-group">
                            <label for="admin_mname">Middle Name</label>
                            <input type="text" class="form-control" id="admin_mname" name="admin_mname">
                        </div>
                        <div class="form-group">
                            <label for="admin_lname">Last Name</label>
                            <input type="text" class="form-control" id="admin_lname" name="admin_lname" required>
                        </div>
                        <div class="form-group">
                            <label for="admin_username">Username</label>
                            <input type="text" class="form-control" id="admin_username" name="admin_username" required>
                        </div>
                        <div class="form-group">
                            <label for="admin_password">Password</label>
                            <input type="text" class="form-control" id="admin_password" name="admin_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Admin</button>
                    </div>
                </form>
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
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('#adminTable').DataTable({
                "scrollX": true,
                "paging": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "dom": 'Bfrtip',
                "buttons": [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });

            // Handle Delete Button Click
            $('.deleteBtn').on('click', function() {
                const adminId = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'list-of-admin-account.php',
                            method: 'POST',
                            data: {
                                action: 'delete',
                                admin_id: adminId
                            },
                            success: function(response) {
                                Swal.fire('Deleted!', 'The admin account has been deleted.', 'success').then(() => {
                                    location.reload();
                                });
                            }
                        });
                    }
                });
            });

            // Handle Update Button Click - Fill Modal with current data
            $('.updateBtn').on('click', function() {
                const row = $(this).closest('tr');
                $('#admin_id').val($(this).data('id'));
                $('#admin_name').val(row.find('td:nth-child(2)').text());
                $('#admin_mname').val(row.find('td:nth-child(3)').text());
                $('#admin_lname').val(row.find('td:nth-child(4)').text());
                $('#admin_username').val(row.find('td:nth-child(5)').text());
                $('#admin_password').val(row.find('td:nth-child(6)').text());
            });

            // Handle Update Admin Form Submission
            $('#updateAdminForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'list-of-admin-account.php',
                    method: 'POST',
                    data: $(this).serialize() + '&action=update',
                    success: function(response) {
                        Swal.fire('Updated!', 'The admin account has been updated.', 'success').then(() => {
                            location.reload();
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>