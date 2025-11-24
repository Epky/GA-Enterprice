<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Handle Update Customer Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    // Fetch form data for update
    $customerid = $_POST['customerid'];
    $customer_name = $_POST['customer_name'];
    $customer_number = $_POST['customer_number'];
    $customer_email = $_POST['customer_email'];
    $customer_address_1 = $_POST['customer_address_1'];
    $customer_address_2 = $_POST['customer_address_2'];
    $customer_city = $_POST['customer_city'];
    $customer_municipality = $_POST['customer_municipality'];
    $customer_zipcode = $_POST['customer_zipcode'];
    $customer_password = $_POST['customer_password'];
    $online_offline_status = $_POST['online_offline_status'];

    // If a new password is provided, hash it, otherwise retain the current password
    if (!empty($customer_password)) {
        // Hash the password before storing it
        $hashed_password = password_hash($customer_password, PASSWORD_BCRYPT);
    } else {
        // If no new password is provided, retain the existing password (fetch it from the database)
        $sql_current = "SELECT customer_password FROM tbl_customer_account WHERE customerid = ?";
        $stmt_current = $conn->prepare($sql_current);
        $stmt_current->bind_param("i", $customerid);
        $stmt_current->execute();
        $result_current = $stmt_current->get_result();
        $current_password = $result_current->fetch_assoc()['customer_password'];
        $hashed_password = $current_password; // Retain the existing password if no new password is provided
        $stmt_current->close();
    }

    // Prepare SQL query to update customer information
    $sql = "UPDATE tbl_customer_account 
            SET customer_name = ?, customer_number = ?, customer_email = ?, 
                customer_address_1 = ?, customer_address_2 = ?, customer_city = ?, 
                customer_municipality = ?, customer_zipcode = ?, customer_password = ?, 
                online_offline_status = ? 
            WHERE customerid = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing update statement: " . $conn->error);
    }

    // Bind parameters to the query
    $stmt->bind_param(
        "ssssssssssi",
        $customer_name,
        $customer_number,
        $customer_email,
        $customer_address_1,
        $customer_address_2,
        $customer_city,
        $customer_municipality,
        $customer_zipcode,
        $hashed_password, // Use the hashed password here
        $online_offline_status,
        $customerid
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

// Handle Delete Customer Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $customerid = $_POST['customerid'];

    // Prepare SQL query to delete customer
    $sql = "DELETE FROM tbl_customer_account WHERE customerid = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing delete statement: " . $conn->error);
    }

    // Bind parameter
    $stmt->bind_param("i", $customerid);

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

// Function to fetch all customer accounts from tbl_customer_account
function fetchCustomerAccounts($conn)
{
    $sql = "SELECT customerid, customer_name, customer_number, customer_email, customer_address_1, 
                   customer_address_2, customer_city, customer_municipality, customer_zipcode, 
                   customer_password, online_offline_status, date_register
            FROM tbl_customer_account
            ORDER BY date_register DESC";

    $result = $conn->query($sql);
    if ($result === false) {
        die("Error fetching customer accounts: " . $conn->error);
    }
    return $result;
}

$customerAccounts = fetchCustomerAccounts($conn);
?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise - Customer Accounts List</title>
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
        .customer-table {
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
                <h4 class="mb-4">Customer Accounts List</h4>
                <table id="customerTable" class="customer-table display nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Customer Name</th>
                            <th>Phone Number</th>
                            <th>Email</th>
                            <th>Address Line 1</th>
                            <th>Address Line 2</th>
                            <th>City</th>
                            <th>Municipality</th>
                            <th>ZIP Code</th>
                            <th>Password</th>
                            <th>Online/Offline Status</th>
                            <th>Registered At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($customerAccounts->num_rows > 0) { ?>
                            <?php while ($customer = $customerAccounts->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['customerid']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['customer_number']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['customer_email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['customer_address_1']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['customer_address_2']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['customer_city']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['customer_municipality']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['customer_zipcode']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['customer_password']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['online_offline_status']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['date_register']); ?></td>
                                    <td>
                                        <button class="btn btn-primary updateBtn" data-id="<?php echo htmlspecialchars($customer['customerid']); ?>" data-toggle="modal" data-target="#updateCustomerModal">Update</button>
                                        <button class="btn btn-danger deleteBtn" data-id="<?php echo htmlspecialchars($customer['customerid']); ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="13">No customer accounts found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Update Customer Modal -->
    <div class="modal fade" id="updateCustomerModal" tabindex="-1" role="dialog" aria-labelledby="updateCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="updateCustomerForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateCustomerModalLabel">Update Customer Information</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="customerid" name="customerid">
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
                            <label for="customer_address_1">Address 1</label>
                            <input type="text" class="form-control" id="customer_address_1" name="customer_address_1" required>
                        </div>

                        <div class="form-group">
                            <label for="customer_address_2">Address 2</label>
                            <input type="text" class="form-control" id="customer_address_2" name="customer_address_2" required>
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
                            <label for="customer_zipcode">Zip Code</label>
                            <input type="text" class="form-control" id="customer_zipcode" name="customer_zipcode" required>
                        </div>

                        <div class="form-group">
                            <label for="customer_password">Password</label>
                            <input type="text" class="form-control" id="customer_password" name="customer_password" required>
                        </div>
                        <!-- Add other fields similar to Customer Name -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Customer</button>
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
            $('#customerTable').DataTable({
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
                const customerId = $(this).data('id');
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
                            url: 'list-of-customer-account.php',
                            method: 'POST',
                            data: {
                                action: 'delete',
                                customerid: customerId
                            },
                            success: function(response) {
                                Swal.fire('Deleted!', 'The customer account has been deleted.', 'success').then(() => {
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
                $('#customerid').val($(this).data('id'));
                $('#customer_name').val(row.find('td:nth-child(2)').text());
                $('#customer_number').val(row.find('td:nth-child(3)').text());
                $('#customer_email').val(row.find('td:nth-child(4)').text());
                $('#customer_address_1').val(row.find('td:nth-child(5)').text());
                $('#customer_address_2').val(row.find('td:nth-child(6)').text());
                $('#customer_city').val(row.find('td:nth-child(7)').text());
                $('#customer_municipality').val(row.find('td:nth-child(8)').text());
                $('#customer_zipcode').val(row.find('td:nth-child(9)').text());
                $('#customer_password').val(row.find('td:nth-child(10)').text());
                $('#online_offline_status').val(row.find('td:nth-child(11)').text());
            });

            // Handle Update Customer Form Submission
            $('#updateCustomerForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'list-of-customer-account.php',
                    method: 'POST',
                    data: $(this).serialize() + '&action=update',
                    success: function(response) {
                        Swal.fire('Updated!', 'The customer account has been updated.', 'success').then(() => {
                            location.reload();
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>