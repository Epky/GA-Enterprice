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

$alertMessage = "";
$alertType = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editProduct'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_description = $_POST['product_description'];
    $product_stocks = $_POST['product_stocks'];

    // Get the current product details
    $sql = "SELECT product_stocks FROM tbl_product WHERE product_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->bind_result($current_stock);
        $stmt->fetch();
        $stmt->close();

        // Calculate the stock change (added or removed)
        $stock_change = $product_stocks - $current_stock;
        $change_type = ($stock_change > 0) ? 'add' : 'remove';

        // Handling image updates
        $product_image_1 = $_FILES['product_image_1']['name'] ? $_FILES['product_image_1']['name'] : $_POST['existing_image_1'];
        $product_image_2 = $_FILES['product_image_2']['name'] ? $_FILES['product_image_2']['name'] : $_POST['existing_image_2'];
        $product_image_3 = $_FILES['product_image_3']['name'] ? $_FILES['product_image_3']['name'] : $_POST['existing_image_3'];

        if (!empty($_FILES['product_image_1']['tmp_name'])) {
            move_uploaded_file($_FILES['product_image_1']['tmp_name'], '../uploads/' . $product_image_1);
        }
        if (!empty($_FILES['product_image_2']['tmp_name'])) {
            move_uploaded_file($_FILES['product_image_2']['tmp_name'], '../uploads/' . $product_image_2);
        }
        if (!empty($_FILES['product_image_3']['tmp_name'])) {
            move_uploaded_file($_FILES['product_image_3']['tmp_name'], '../uploads/' . $product_image_3);
        }

        // Update product data in the database
        $sql = "UPDATE tbl_product SET product_name = ?, product_price = ?, product_description = ?, product_stocks = ?, product_image_1 = ?, product_image_2 = ?, product_image_3 = ? WHERE product_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssssi", $product_name, $product_price, $product_description, $product_stocks, $product_image_1, $product_image_2, $product_image_3, $product_id);
            if ($stmt->execute()) {
                // Insert into inventory logs after updating the product
                $log_sql = "INSERT INTO tbl_inventory_log (product_id, change_type, quantity, date_added) VALUES (?, ?, ?, NOW())";
                if ($log_stmt = $conn->prepare($log_sql)) {
                    $log_stmt->bind_param("isi", $product_id, $change_type, abs($stock_change));
                    $log_stmt->execute();
                    $log_stmt->close();
                } else {
                    // Error preparing the log statement
                    echo "Error preparing log statement: " . $conn->error;
                }

                $alertMessage = "Product updated and inventory log added successfully.";
                $alertType = "success";
            } else {
                $alertMessage = "Error updating product: " . $stmt->error;
                $alertType = "error";
            }
            $stmt->close();
        } else {
            // Error preparing the statement
            echo "Error preparing statement: " . $conn->error;
        }
    } else {
        // Error fetching current stock
        echo "Error fetching current stock: " . $conn->error;
    }
}

// Function to delete product
if (isset($_GET['delete_id'])) {
    $product_id = $_GET['delete_id'];
    $sql = "DELETE FROM tbl_product WHERE product_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $product_id);
        if ($stmt->execute()) {
            $alertMessage = "Product deleted successfully.";
            $alertType = "success";
        } else {
            $alertMessage = "Error deleting product: " . $stmt->error;
            $alertType = "error";
        }
        $stmt->close();
    } else {
        // Error preparing the delete statement
        echo "Error preparing delete statement: " . $conn->error;
    }
}

// Get products from the database
$products = fetchAllProducts();
?>




<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>G.A. Ruiz Enterprise - List of Product</title>

    <!--Style -->
    <link rel="shortcut icon" href="../assets/images/gra.png" />
    <link rel="stylesheet" href="../assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="../assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="../assets/vendor/remixicon/fonts/remixicon.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
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

        #loading {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body class="">
    <!-- loader Start -->
    <div id="loading">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- loader END -->

    <?php include 'topbar.php'; ?>

    <!--<!-- Wrapper Start -->
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="content-page">
        <div class="container-fluid">
            <h4 class="mb-4">Product List</h4>
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
                                    <p class="card-text"><strong>Price: ₱</strong> <?php echo htmlspecialchars($row['product_price']); ?></p>
                                    <p class="card-text"><strong>Description:</strong><?php echo htmlspecialchars($row['product_description']); ?></p>
                                    <p class="card-text"><strong>Stocks:</strong> <?php echo htmlspecialchars($row['product_stocks']); ?></p>
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editModal-<?php echo $row['product_id']; ?>">Edit</button>
                                    <a href="list-product.php?delete_id=<?php echo htmlspecialchars($row['product_id']); ?>" class="btn btn-danger btn-sm delete-product">Delete</a>
                                    <a href="logs.php?product_id=<?php echo htmlspecialchars($row['product_id']); ?>" class="btn btn-info btn-sm"> View Logs</a>
                                </div>
                            </div>
                        </div>
                        
                         <!-- Edit Modal -->
                         <div class="modal fade" id="editModal-<?php echo $row['product_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel-<?php echo $row['product_id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel-<?php echo $row['product_id']; ?>">Edit Product</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form action="list-product.php" method="POST" enctype="multipart/form-data" onsubmit="showLoading()">
                                            <div class="modal-body">
                                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($row['product_id']); ?>">
                                                <input type="hidden" name="existing_image_1" value="<?php echo htmlspecialchars($row['product_image_1']); ?>">
                                                <input type="hidden" name="existing_image_2" value="<?php echo htmlspecialchars($row['product_image_2']); ?>">
                                                <input type="hidden" name="existing_image_3" value="<?php echo htmlspecialchars($row['product_image_3']); ?>">

                                                <div class="form-group">
                                                    <label for="product_name">Product Name:</label>
                                                    <input type="text" class="form-control" name="product_name" value="<?php echo htmlspecialchars($row['product_name']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="product_price">Price:</label>
                                                    <input type="text" class="form-control" name="product_price" value="<?php echo htmlspecialchars($row['product_price']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="product_description">Description:</label>
                                                    <textarea class="form-control" name="product_description" rows="3" required><?php echo htmlspecialchars($row['product_description']); ?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label for="product_stocks">Stocks:</label>
                                                    <input type="number" class="form-control" name="product_stocks" value="<?php echo htmlspecialchars($row['product_stocks']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="product_image_1">Image 1:</label>
                                                    <input type="file" class="form-control-file" name="product_image_1">
                                                    <img src="../uploads/<?php echo htmlspecialchars($row['product_image_1']); ?>" class="img-thumbnail mt-2" width="100" alt="Product Image 1">
                                                </div>
                                                <div class="form-group">
                                                    <label for="product_image_2">Image 2:</label>
                                                    <input type="file" class="form-control-file" name="product_image_2">
                                                    <img src="../uploads/<?php echo htmlspecialchars($row['product_image_2']); ?>" class="img-thumbnail mt-2" width="100" alt="Product Image 2">
                                                </div>
                                                <div class="form-group">
                                                    <label for="product_image_3">Image 3:</label>
                                                    <input type="file" class="form-control-file" name="product_image_3">
                                                    <img src="../uploads/<?php echo htmlspecialchars($row['product_image_3']); ?>" class="img-thumbnail mt-2" width="100" alt="Product Image 3">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary" name="editProduct">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo "<div class='col-12'><p class='text-center'>No Product Found.</p></div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
<!-- Wrapper End -->

<!-- Backend Bundle JavaScript -->
<script src="../assets/js/backend-bundle.min.js"></script>
<!-- SweetAlert JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
<script>
    function showLoading() {
        document.getElementById('loading').style.display = 'flex';
    }

    document.addEventListener("DOMContentLoaded", function() {
        // SweetAlert for delete confirmation
        document.querySelectorAll('.delete-product').forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const url = this.href;
                swal({
                    title: "Are you sure?",
                    text: "You will not be able to recover!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "Cancel",
                    closeOnConfirm: false,
                    closeOnCancel: true
                }, function(isConfirm) {
                    if (isConfirm) {
                        showLoading();
                        window.location.href = url;
                    }
                });
            });
        });
    });
</script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>