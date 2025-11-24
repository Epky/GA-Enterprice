<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Function to fetch all products
function fetchAllProducts()
{
    global $conn;

    $sql = "SELECT product_id, product_name, product_price, product_description, product_stocks, product_image_1, product_image_2, product_image_3, date_added FROM tbl_product";
    $result = $conn->query($sql);

    return $result;
}

// Handle placing an order within the same file
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    if (isset($_SESSION['customer_id'])) {
        $customer_id = $_SESSION['customer_id'];
    } else {
        // Redirect to login page if customer is not logged in
        header("Location: login.php");
        exit();
    }

    // Retrieve form data
    $product_id = $_POST['product_id'];
    $item_quantity = $_POST['quantity'];
    $order_status = "Wait for Confirmation"; // Default status

    // Start a transaction to ensure data integrity
    $conn->begin_transaction();

    try {
        // Step 1: Check if there is enough stock for the product
        $check_stock_sql = "SELECT product_stocks FROM tbl_product WHERE product_id = ?";
        $check_stock_stmt = $conn->prepare($check_stock_sql);
        $check_stock_stmt->bind_param("i", $product_id);
        $check_stock_stmt->execute();
        $check_stock_stmt->bind_result($current_stock);
        $check_stock_stmt->fetch();
        $check_stock_stmt->close();

        if ($current_stock >= $item_quantity) {
            // Step 2: Insert order into tbl_order
            $sql = "INSERT INTO tbl_order (customerid, item_quantity, order_status, product_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisi", $customer_id, $item_quantity, $order_status, $product_id);

            if (!$stmt->execute()) {
                throw new Exception("Error placing order.");
            }

            // Step 3: Update the product stock in tbl_product
            $update_sql = "UPDATE tbl_product SET product_stocks = product_stocks - ? WHERE product_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $item_quantity, $product_id);

            if (!$update_stmt->execute()) {
                throw new Exception("Error updating product stock.");
            }

            // Step 4: Commit the transaction
            $conn->commit();

            // Success alert
            echo "<script>alert('Order placed successfully.');</script>";

            $stmt->close();
            $update_stmt->close();
        } else {
            // If not enough stock
            throw new Exception("Not enough stock available for this product.");
        }

    } catch (Exception $e) {
        // Rollback the transaction if any query fails
        $conn->rollback();

        // Error alert
        echo "<script>alert('" . $e->getMessage() . "');</script>";
    }
}

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Get the customer ID from the session
$customer_id = $_SESSION['customer_id'];

// Fetch cart items for the logged-in user
function fetchCartItems($customer_id)
{
    global $conn;

    $sql = "SELECT o.order_id, o.item_quantity, o.order_status, p.product_id, p.product_name, p.product_price, p.product_image_1 
            FROM tbl_order o
            JOIN tbl_product p ON o.product_id = p.product_id
            WHERE o.customerid = ? AND o.order_status = 'Wait for Confirmation'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result;
}

// Fetch the cart items
$cart_items = fetchCartItems($customer_id);
// Get products from the database
$products = fetchAllProducts();
?>



<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise </title>

    <!-- Style -->
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
            height: 200px;
            object-fit: cover;
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
                <h4 class="mb-4">Order Now</h4>
                <div class="row">
                    <?php
                    if ($products->num_rows > 0) {
                        while ($row = $products->fetch_assoc()) {
                    ?>
                            <div class="col-md-4">
                                <div class="card">
                                    <div id="carousel-<?php echo $row['product_id']; ?>" class="carousel slide" data-ride="carousel">
                                        <div class="carousel-inner">
                                            <?php
                                            $images = ['product_image_1', 'product_image_2', 'product_image_3'];
                                            $active = true;
                                            foreach ($images as $image) {
                                                if (!empty($row[$image])) {
                                            ?>
                                                    <div class="carousel-item <?php echo $active ? 'active' : ''; ?>">
                                                        <img src="../uploads/<?php echo htmlspecialchars($row[$image]); ?>" class="d-block w-100" alt="Product Image">
                                                    </div>
                                            <?php
                                                    $active = false;
                                                }
                                            }
                                            ?>
                                        </div>
                                        <a class="carousel-control-prev" href="#carousel-<?php echo $row['product_id']; ?>" role="button" data-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="sr-only">Previous</span>
                                        </a>
                                        <a class="carousel-control-next" href="#carousel-<?php echo $row['product_id']; ?>" role="button" data-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="sr-only">Next</span>
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($row['product_name']); ?></h5>
                                        <p class="card-text"><strong>Price:</strong> <?php echo htmlspecialchars($row['product_price']); ?></p>
                                        <p class="card-text"><strong>Description:</strong> <?php echo htmlspecialchars($row['product_description']); ?></p>
                                        <p class="card-text"><strong>Stocks Available:</strong> <?php echo htmlspecialchars($row['product_stocks']); ?></p>
                                        <p class="card-text"><small class="text-muted">Added on: <?php echo htmlspecialchars($row['date_added']); ?></small></p>
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#orderModal-<?php echo $row['product_id']; ?>">Order Now</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Modal -->
                            <div class="modal fade" id="orderModal-<?php echo $row['product_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel-<?php echo $row['product_id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="orderModalLabel-<?php echo $row['product_id']; ?>">Order Product</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="text-center mb-3">
                                                <img src="../uploads/<?php echo htmlspecialchars($row['product_image_1']); ?>" class="img-thumbnail" alt="Product Image" style="width: 200px; height: auto;">
                                            </div>
                                            <form method="POST" action="">
                                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($row['product_id']); ?>">
                                                <div class="form-group">
                                                    <label for="product_name">Product Name</label>
                                                    <input type="text" class="form-control" id="product_name" value="<?php echo htmlspecialchars($row['product_name']); ?>" readonly>
                                                </div>
                                                <div class="form-group">
                                                    <label for="product_price">Price</label>
                                                    <input type="text" class="form-control" id="product_price" value="<?php echo htmlspecialchars($row['product_price']); ?>" readonly>
                                                </div>
                                                <div class="form-group">
                                                    <label for="product_description">Description</label>
                                                    <textarea class="form-control" id="product_description" rows="3" readonly><?php echo htmlspecialchars($row['product_description']); ?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label for="product_stocks">Available Stocks</label>
                                                    <input type="text" class="form-control" id="product_stocks" value="<?php echo htmlspecialchars($row['product_stocks']); ?>" readonly>
                                                </div>
                                                <div class="form-group">
                                                    <label for="quantity">Quantity to Order</label>
                                                    <input type="number" class="form-control" name="quantity" id="quantity" min="1" max="<?php echo htmlspecialchars($row['product_stocks']); ?>" required>
                                                </div>
                                                <button type="submit" name="place_order" class="btn btn-primary">Add to Cart</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo "<div class='col-12'><p class='text-center'>No products available.</p></div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Backend Bundle JavaScript -->
    <script src="../assets/js/backend-bundle.min.js"></script>
    <!-- app JavaScript -->
    <script src="../assets/js/app.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>