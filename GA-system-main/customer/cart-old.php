<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

// Get the customer ID from the session
$customer_id = $_SESSION['customer_id'];

// Function to fetch cart items
function fetchCartItems($customer_id, $conn)
{
    $sql = "SELECT o.order_id, o.item_quantity, o.order_status, p.product_id, p.product_name, p.product_price, p.product_description, p.product_stocks, p.product_image_1, p.date_added
            FROM tbl_order o
            JOIN tbl_product p ON o.product_id = p.product_id
            WHERE o.customerid = ? AND o.order_status = 'Wait for Confirmation'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch the cart items
$cart_items = fetchCartItems($customer_id, $conn);

// Handle AJAX requests for removing an item or updating item quantity
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $response = ['status' => 'error', 'message' => 'Invalid action'];

    if ($_POST['action'] == 'remove_item') {
        $order_id = $_POST['order_id'];
        $product_id = $_POST['product_id'];
        $item_quantity = $_POST['item_quantity'];

        $conn->begin_transaction();
        try {
            $delete_sql = "DELETE FROM tbl_order WHERE order_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $order_id);
            if (!$delete_stmt->execute()) {
                throw new Exception("Error deleting order.");
            }

            $update_sql = "UPDATE tbl_product SET product_stocks = product_stocks + ? WHERE product_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $item_quantity, $product_id);
            if (!$update_stmt->execute()) {
                throw new Exception("Error updating product stock.");
            }

            $conn->commit();
            $response = ['status' => 'success', 'message' => 'Item removed successfully'];
        } catch (Exception $e) {
            $conn->rollback();
            $response = ['status' => 'error', 'message' => $e->getMessage()];
        }

        echo json_encode($response);
        exit();
    }

    if ($_POST['action'] == 'update_quantity') {
        $order_id = $_POST['order_id'];
        $product_id = $_POST['product_id'];
        $new_quantity = $_POST['new_quantity'];

        $conn->begin_transaction();
        try {
            $select_sql = "SELECT item_quantity FROM tbl_order WHERE order_id = ?";
            $select_stmt = $conn->prepare($select_sql);
            $select_stmt->bind_param("i", $order_id);
            $select_stmt->execute();
            $select_result = $select_stmt->get_result();

            if ($select_result->num_rows > 0) {
                $row = $select_result->fetch_assoc();
                $current_quantity = $row['item_quantity'];
                $quantity_difference = $new_quantity - $current_quantity;

                $update_order_sql = "UPDATE tbl_order SET item_quantity = ? WHERE order_id = ?";
                $update_order_stmt = $conn->prepare($update_order_sql);
                $update_order_stmt->bind_param("ii", $new_quantity, $order_id);
                if (!$update_order_stmt->execute()) {
                    throw new Exception("Error updating order quantity.");
                }

                $update_stock_sql = "UPDATE tbl_product SET product_stocks = product_stocks - ? WHERE product_id = ?";
                $update_stock_stmt = $conn->prepare($update_stock_sql);
                $update_stock_stmt->bind_param("ii", $quantity_difference, $product_id);
                if (!$update_stock_stmt->execute()) {
                    throw new Exception("Error updating product stock.");
                }

                $conn->commit();
                $response = ['status' => 'success', 'new_quantity' => $new_quantity];
            } else {
                throw new Exception("Order not found.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $response = ['status' => 'error', 'message' => $e->getMessage()];
        }

        echo json_encode($response);
        exit();
    }
}
?>



<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/images/gra.png" />
    <link rel="stylesheet" href="../assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="../assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="../assets/vendor/remixicon/fonts/remixicon.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .card {
            margin-bottom: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2);
        }

        .card img {
            height: 100px;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <!-- loader Start -->
    <div id="loading">
        <div id="loading-center"></div>
    </div>
    <!-- loader END -->

    <?php include 'topbar.php'; ?>

    <!-- Wrapper Start -->
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="container-fluid">
                <h4 class="mb-4">Shopping Cart</h4>
                <div class="row">
                    <?php
                    $total_price = 0; // Initialize the total price variable
                    if ($cart_items->num_rows > 0) {
                        while ($row = $cart_items->fetch_assoc()) {
                            $subtotal = $row['product_price'] * $row['item_quantity'];
                            $total_price += $subtotal;
                    ?>
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body d-flex">
                                        <div class="product-image">
                                            <img src="../uploads/<?php echo htmlspecialchars($row['product_image_1']); ?>" class="img-thumbnail" alt="Product Image">
                                        </div>
                                        <div class="product-details ml-4">
                                            <h5 class="card-title"><?php echo htmlspecialchars($row['product_name']); ?></h5>
                                            <p class="card-text"><strong>Price:</strong> $<?php echo htmlspecialchars($row['product_price']); ?></p>
                                            <p class="card-text"><strong>Description:</strong> <?php echo htmlspecialchars($row['product_description']); ?></p>
                                            <p class="card-text"><strong>Available Stocks:</strong> <?php echo htmlspecialchars($row['product_stocks']); ?></p>
                                            <p class="card-text"><strong>Date Added:</strong> <?php echo htmlspecialchars($row['date_added']); ?></p>
                                            <div class="form-group">
                                                <label for="quantity">Quantity:</label>
                                                <input type="number" name="quantity" class="form-control quantity" data-order-id="<?php echo htmlspecialchars($row['order_id']); ?>" data-product-id="<?php echo htmlspecialchars($row['product_id']); ?>" value="<?php echo htmlspecialchars($row['item_quantity']); ?>" min="1" required style="width: 80px;">
                                            </div>
                                            <p class="card-text"><strong>Subtotal:</strong> $<span class="subtotal"><?php echo number_format($subtotal, 2); ?></span></p>
                                            <button class="btn btn-danger btn-sm remove-item" data-order-id="<?php echo htmlspecialchars($row['order_id']); ?>" data-product-id="<?php echo htmlspecialchars($row['product_id']); ?>" data-item-quantity="<?php echo htmlspecialchars($row['item_quantity']); ?>">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo "<div class='col-12'><p class='text-center'>Your cart is empty.</p></div>";
                    }
                    ?>
                </div>
                <div class="text-right mt-4">
                    <h5><strong>Total:</strong> $<span id="total-price"><?php echo number_format($total_price, 2); ?></span></h5>
                    <a href="checkout.php" class="btn btn-success mt-2">Proceed to Checkout</a>
                    <br>
                    <a href="order-now.php" class="btn btn-warning mt-2">Continue Shopping</a>
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
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(window).on('load', function() {
            $('#loading').fadeOut('slow');
        });

        $(document).ready(function() {
            // Remove item from cart
            $('.remove-item').click(function() {
                var orderId = $(this).data('order-id');
                var productId = $(this).data('product-id');
                var itemQuantity = $(this).data('item-quantity');

                $.post('cart.php', {
                    action: 'remove_item',
                    order_id: orderId,
                    product_id: productId,
                    item_quantity: itemQuantity
                }, function(response) {
                    var data = JSON.parse(response);
                    if (data.status === 'success') {
                        // Remove the item card from the DOM
                        $(`.remove-item[data-order-id="${orderId}"]`).closest('.col-md-12').remove();
                        // Recalculate the total price after an item is removed
                        recalculateTotalPrice();
                    } else {
                        alert(data.message);
                    }
                });
            });

            // Update item quantity in cart
            $('.quantity').on('change', function() {
                var $quantityInput = $(this);
                var orderId = $quantityInput.data('order-id');
                var productId = $quantityInput.data('product-id');
                var newQuantity = parseInt($quantityInput.val());

                // Ensure the quantity is a valid number and greater than zero
                if (isNaN(newQuantity) || newQuantity <= 0) {
                    alert("Please enter a valid quantity.");
                    return;
                }

                var $productDetails = $quantityInput.closest('.product-details');
                var $subtotalElement = $productDetails.find('.subtotal');
                var priceText = $productDetails.find('.card-text strong:contains("Price")').parent().text();

                // Extract the price from the text, assuming the format "Price: $<value>"
                var productPrice = parseFloat(priceText.replace(/[^0-9\.]/g, ''));

                // Ensure the product price is a valid number
                if (isNaN(productPrice)) {
                    alert("Error reading product price. Please try again.");
                    return;
                }

                // Send AJAX request to update quantity
                $.post('cart.php', {
                    action: 'update_quantity',
                    order_id: orderId,
                    product_id: productId,
                    new_quantity: newQuantity
                }, function(response) {
                    var data = JSON.parse(response);
                    if (data.status === 'success') {
                        // Update the subtotal for the current item
                        var newSubtotal = productPrice * newQuantity;
                        $subtotalElement.text(newSubtotal.toFixed(2));

                        // Recalculate the total price for all items
                        recalculateTotalPrice();
                    } else {
                        alert(data.message);
                    }
                });
            });

            // Function to recalculate the total price of all items
            function recalculateTotalPrice() {
                var totalPrice = 0;
                $('.subtotal').each(function() {
                    var subtotalValue = parseFloat($(this).text());
                    if (!isNaN(subtotalValue)) {
                        totalPrice += subtotalValue;
                    }
                });
                $('#total-price').text(totalPrice.toFixed(2));
            }
        });
    </script>

</body>

</html>