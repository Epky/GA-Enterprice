<?php
session_start();
include '../sql/sql.php';

$alertMessage = "";
$alertType = "";

// Fetch all products for the dropdown
$productQuery = "SELECT product_id, product_name, product_price, product_stocks FROM tbl_product";
$productResult = $conn->query($productQuery);
$products = [];
if ($productResult && $productResult->num_rows > 0) {
    while ($row = $productResult->fetch_assoc()) {
        $products[] = $row;
    }
}

// Handle form submission for walk-in order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = $_POST['customer_name'] ?? '';
    $orderItems = $_POST['order_items'] ?? [];
    $orderItems = $_POST['deqty'] ?? [];

    if ($customerName && !empty($orderItems)) {
        $conn->begin_transaction(); // Start transaction

        try {
            foreach ($orderItems as $item) {
                $productId = $item['product_id'] ?? 0;
                $itemQuantity = (int)($item['item_quantity'] ?? 0);

                // Get current stock and product price
                $checkProductQuery = "SELECT product_stocks, product_price FROM tbl_product WHERE product_id = ?";

                if ($stmt = $conn->prepare($checkProductQuery)) {
                    // Bind the product ID parameter
                    $stmt->bind_param("i", $productId);

                    // Execute the query
                    $stmt->execute();

                    // Bind the results to variables
                    $stmt->bind_result($currentStock, $productPrice);

                    // Fetch the result and validate product ID
                    if ($stmt->fetch()) {
                        $stmt->close();
                    } else {
                        $stmt->close();
                        throw new Exception("Invalid product ID: $productId");
                    }
                } else {
                    throw new Exception("Error validating product ID: " . $conn->error);
                }
                
                //problema
                // Validate stock
                if (empty($itemQuantity) || !is_numeric($itemQuantity) || $itemQuantity <= 0) {
                    throw new Exception("Invalid quantity for Product ID: $productId. Quantity must be greater than zero.");
                }



                if ($itemQuantity > $currentStock) {
                    throw new Exception("Insufficient stock for Product ID: $productId. Available: $currentStock, Requested: $itemQuantity");
                }

                // If everything is valid, proceed
                echo "Validation passed. Product ID: $productId, Current Stock: $currentStock, Requested Quantity: $itemQuantity, Price: $productPrice";


                $totalPrice = $itemQuantity * $productPrice;

                // Insert into tbl_walk_in_order
                $insertWalkInOrderQuery = "INSERT INTO tbl_walk_in_order (customer_name, product_id, quantity, total_price) 
                                           VALUES (?, ?, ?, ?)";
                if ($stmt = $conn->prepare($insertWalkInOrderQuery)) {
                    $stmt->bind_param("siid", $customerName, $productId, $itemQuantity, $totalPrice);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    throw new Exception("Error inserting walk-in order: " . $conn->error);
                }

                // Update product stock
                $updateStockQuery = "UPDATE tbl_product SET product_stocks = product_stocks - ? WHERE product_id = ?";
                if ($stmt = $conn->prepare($updateStockQuery)) {
                    $stmt->bind_param("ii", $itemQuantity, $productId);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    throw new Exception("Error updating product stock: " . $conn->error);
                }
            }

            // Commit transaction if all queries succeed
            $conn->commit();
            $alertMessage = "Walk-in order successfully processed!";
            $alertType = "success";
        } catch (Exception $e) {
            $conn->rollback(); // Rollback if any error occurs
            $alertMessage = "Error: " . $e->getMessage();
            $alertType = "error";
        }
    } else {
        $alertMessage = "All fields are required.";
        $alertType = "error";
    }
}

?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise - Walk-in Order</title>

    <link rel="shortcut icon" href="../assets/images/gra.png" />
    <link rel="stylesheet" href="../assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="../assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
    <style>
        .form-container {
            margin-top: 30px;
        }

        .form-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
        }

        .alert {
            margin-top: 20px;
        }

        .alert-success {
            background-color: #28a745;
            color: #fff;
        }

        .alert-error {
            background-color: #dc3545;
            color: #fff;
        }

        .remove-btn {
            display: inline-block;
            margin-left: 10px;
            padding: 5px 10px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <?php include 'topbar.php'; ?>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="container-fluid">
                <h4 class="mb-4">Walk-in Order Process</h4>

                <?php if ($alertMessage) { ?>
                    <div class="alert alert-<?php echo $alertType === 'error' ? 'error' : 'success'; ?>">
                        <?php echo $alertMessage; ?>
                    </div>
                <?php } ?>

                <div class="form-container">
                    <div class="form-card">
                        <form method="POST" action="" id="walkinForm">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="customer_name">Customer Name</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                                </div>
                            </div>

                            <div id="productContainer">
                                <div class="product-entry">
                                    <div class="row align-items-center">
                                        <div class="col-md-5 form-group">
                                            <label for="product_id">Product Name</label>
                                            <select class="form-control" name="order_items[][product_id]" onchange="openModal(this)">
                                                <option value="">Select Product</option>
                                                <?php foreach ($products as $product) { ?>
                                                    <option value="<?php echo $product['product_id']; ?>"
                                                        data-price="<?php echo $product['product_price']; ?>"
                                                        data-stock="<?php echo $product['product_stocks']; ?>">
                                                        <?php echo $product['product_name']; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="col-md-1 d-flex align-items-center">
                                            <button type="button" class="remove-btn btn btn-danger" onclick="removeProduct(this)">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div>



                            <button type="button" class="btn btn-success" id="addProductButton">Add Another Product</button>
                            <button type="submit" class="btn btn-primary">Add Walk-in Order</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
<div class="modal fade" id="quantityModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <form method="POST" action="walk-in-process.php"> <!-- Form to handle POST submission -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Product Details</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <!-- Product Name -->
                    <div class="form-group">
                        <label for="product_name">Product Name</label>
                        <input type="text" class="form-control" id="modal_product_name" name="product_name" readonly>
                    </div>
                    <!-- Available Stock -->
                    <div class="form-group">
                        <label for="available_stock">Available Stock</label>
                        <input type="text" class="form-control" id="modal_available_stock" name="available_stock" readonly>
                    </div>
                    <!-- Item Quantity -->
                    <div class="form-group">
                        <label for="item_quantity">Item Quantity</label>
                        <input type="number" class="form-control" name="item_quantity" id="modal_item_quantity" required>
                    </div>
                    <!-- Price per Item -->
                    <div class="form-group">
                        <label for="product_price">Price per Item</label>
                        <input type="text" class="form-control" id="modal_product_price" name="product_price" readonly>
                    </div>
                    <!-- Total Price -->
                    <div class="form-group">
                        <label for="total_price">Total Price</label>
                        <input type="text" class="form-control" id="modal_total_price" name="total_price" readonly>
                    </div>
                    <!-- Order Status -->
                    <div class="form-group">
                        <label for="order_status">Status</label>
                        <input type="text" class="form-control" id="modal_order_status" name="order_status" value="Walk-in" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary" id="confirmOrderDetails">Confirm</button>
                </div>
            </div>
        </form>
    </div>
</div>




    <script src="../assets/js/backend-bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Open modal and set the price
        function openModal(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0; // Get stock value

            // Set the product name, price, and stock in the modal
            document.getElementById('modal_product_name').value = selectedOption.text;
            document.getElementById('modal_product_price').value = price.toFixed(2);
            document.getElementById('modal_available_stock').value = stock;

            // Reset quantity and total price
            document.getElementById('modal_item_quantity').value = 1;
            document.getElementById('modal_total_price').value = (price * 1).toFixed(2);

            $('#quantityModal').modal('show');
        }

        // Update total price when quantity changes
        document.getElementById('modal_item_quantity').addEventListener('input', function() {
            const quantity = parseInt(this.value) || 1;
            const price = parseFloat(document.getElementById('modal_product_price').value) || 0;
            const total = quantity * price;

            // Update the total price display
            document.getElementById('modal_total_price').value = total.toFixed(2);
        });

        // Add another product entry
        document.getElementById('addProductButton').addEventListener('click', function() {
            const productEntry = document.querySelector('.product-entry').cloneNode(true);
            document.getElementById('productContainer').appendChild(productEntry);
        });

        // Remove product entry
        function removeProduct(button) {
            const productEntry = button.closest('.product-entry');
            productEntry.remove();
        }

        // Confirm order details and populate the form
        document.getElementById('confirmOrderDetails').addEventListener('click', function() {
            const quantity = document.getElementById('modal_item_quantity').value;
            const price = document.getElementById('modal_product_price').value;
            const total = quantity * price;

            console.log('Quantity:', quantity, 'Price:', price, 'Total:', total);

            $('#quantityModal').modal('hide');
        });
    </script>
</body>

</html>