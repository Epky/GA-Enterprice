<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Get the customer ID from the session
$customer_id = $_SESSION['customer_id'];

// Fetch user information
function fetchCustomerInfo($customer_id, $conn)
{
    $sql = "SELECT * FROM tbl_customer_account WHERE customerid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$customer_info = fetchCustomerInfo($customer_id, $conn);

// Retrieve selected cart items from session
$selected_items = isset($_SESSION['selected_items']) ? $_SESSION['selected_items'] : [];

// Fetch selected cart items from the database
$cart_items = !empty($selected_items) ? fetchSelectedCartItems($selected_items, $conn) : null;

// If there are no selected items, redirect back to the cart
if (!$cart_items || $cart_items->num_rows === 0) {
    echo "<div class='col-12'><p class='text-center'>No items selected for checkout. Please select items first.</p></div>";
    exit();
}

// Fetch selected cart items from the database
function fetchSelectedCartItems($selected_items, $conn)
{
    if (empty($selected_items)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($selected_items), '?'));
    $query = "SELECT o.order_id, o.item_quantity, p.product_name, p.product_price, p.product_image_1
              FROM tbl_order o
              JOIN tbl_product p ON o.product_id = p.product_id
              WHERE o.order_id IN ($placeholders)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat('i', count($selected_items)), ...array_column($selected_items, 'order_id'));
    $stmt->execute();
    return $stmt->get_result();
}


//problema
// Function to fetch delivery types
function fetchDeliveryTypes($conn)
{
    $sql = "SELECT * FROM tbl_type_delivery";
    $result = $conn->query($sql);
    return $result;
}

$delivery_types = fetchDeliveryTypes($conn);

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise - Checkout</title>

    <!-- Stylesheets -->
    <link rel="shortcut icon" href="../assets/images/gra.png" />
    <link rel="stylesheet" href="../assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="../assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="../assets/vendor/remixicon/fonts/remixicon.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <?php include 'topbar.php'; ?>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="container-fluid">
                <h4 class="mb-4">Checkout</h4>
                <form method="POST" action="process_checkout.php" class="mt-4">
                    <div class="row">
                        <div class="col-md-8">
                            <h5>Shipping Details</h5>
                            <!-- Customer Information Fields -->
                            <div class="form-group">
                                <label for="customer_name">Your Name:</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($customer_info['customer_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="customer_number">Your Number:</label>
                                <input type="text" class="form-control" id="customer_number" name="customer_number" value="<?php echo htmlspecialchars($customer_info['customer_number']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="customer_email">Your Email:</label>
                                <input type="email" class="form-control" id="customer_email" name="customer_email" value="<?php echo htmlspecialchars($customer_info['customer_email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="customer_address_1">Address Line 01:</label>
                                <input type="text" class="form-control" id="customer_address_1" name="customer_address_1" value="<?php echo htmlspecialchars($customer_info['customer_address_1']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="customer_address_2">Address Line 02 (Optional):</label>
                                <input type="text" class="form-control" id="customer_address_2" name="customer_address_2" value="<?php echo htmlspecialchars($customer_info['customer_address_2']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="customer_city">City:</label>
                                <input type="text" class="form-control" id="customer_city" name="customer_city" value="<?php echo htmlspecialchars($customer_info['customer_city']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="customer_municipality">Municipality:</label>
                                <input type="text" class="form-control" id="customer_municipality" name="customer_municipality" value="<?php echo htmlspecialchars($customer_info['customer_municipality']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="customer_zipcode">ZIP CODE:</label>
                                <input type="text" class="form-control" id="customer_zipcode" name="customer_zipcode" value="<?php echo htmlspecialchars($customer_info['customer_zipcode']); ?>" required>
                            </div>


                            <!-- problema -->
                            <!-- Delivery Type Dropdown -->
                            <div class="form-group">
                                <label for="delivery_type">Delivery Type:</label>
                                <select class="form-control" id="delivery_type" name="delivery_type" required>
                                    <option value="">Select Delivery Type</option>
                                        <option value="Cash On Delivery">Cash On Delivery</option>
                                        
                                </select>
                            </div>




                            <!-- Payment Method (Hidden Input) -->
                            <input type="hidden" id="payment_method" name="payment_method" value="">
                        </div>
                        <div class="col-md-4">
                            <h5>Order Summary</h5>
                            <!-- Order Summary Details -->
                            <?php
                            $total_price = 0;
                            while ($row = $cart_items->fetch_assoc()) {
                                $subtotal = $row['product_price'] * $row['item_quantity'];
                                $total_price += $subtotal;
                            ?>
                                <div class="card mb-3">
                                    <div class="card-body d-flex">
                                        <div class="product-image">
                                            <img src="../uploads/<?php echo htmlspecialchars($row['product_image_1']); ?>" class="img-thumbnail" alt="Product Image">
                                        </div>
                                        <div class="product-details ml-4">
                                            <h5 class="card-title"><?php echo htmlspecialchars($row['product_name']); ?></h5>
                                            <p class="card-text"><strong>Price:</strong> $<?php echo htmlspecialchars($row['product_price']); ?></p>
                                            <p class="card-text"><strong>Quantity:</strong> <?php echo htmlspecialchars($row['item_quantity']); ?></p>
                                            <p class="card-text"><strong>Subtotal:</strong> $<?php echo number_format($subtotal, 2); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hidden Inputs for order_id, item_quantity, product_id -->
                                <input type="hidden" name="order_ids[]" value="<?php echo $row['order_id']; ?>">
                                <input type="hidden" name="item_quantities[]" value="<?php echo $row['item_quantity']; ?>">
                                <input type="hidden" name="product_ids[]" value="<?php echo $row['product_id']; ?>">
                            <?php } ?>
                            <h5><strong>Total:</strong> $<span id="total-price"><?php echo number_format($total_price, 2); ?></span></h5>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-4">Place Order</button>
                </form>
            </div>
        </div>
    </div>
    <!-- JavaScript Dependencies -->
    <!-- JavaScript Dependencies -->
    <script src="../assets/js/backend-bundle.min.js"></script>
    <script src="../assets/js/table-treeview.js"></script>
    <script src="../assets/js/customizer.js"></script>
    <script async src="../assets/js/chart-custom.js"></script>
    <script src="../assets/js/app.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Update the payment method based on the selected delivery type
            $('#delivery_type').on('change', function() {
                var selectedDelivery = $(this).val();
                $('#payment_method').val(selectedDelivery);
            });
        });
    </script>
</body>

</html>