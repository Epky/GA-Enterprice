<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Initialize an empty message to pass to JavaScript for SweetAlert
$message = '';

// Function to add a product
function addProduct($product_name, $product_price, $product_description, $product_stocks, $product_image_1, $product_image_2, $product_image_3)
{
    global $conn, $message;

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO tbl_product (product_name, product_price, product_description, product_stocks, product_image_1, product_image_2, product_image_3, date_added) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssssss", $product_name, $product_price, $product_description, $product_stocks, $product_image_1, $product_image_2, $product_image_3);

    // Execute the query
    if ($stmt->execute()) {
        $message = "success";
    } else {
        $message = "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}

// Example usage
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_description = $_POST['product_description'];
    $product_stocks = $_POST['product_stocks'];
    $product_image_1 = $_FILES['product_image_1']['name'];
    $product_image_2 = $_FILES['product_image_2']['name'];
    $product_image_3 = $_FILES['product_image_3']['name'];

    // Upload images to a specific directory
    move_uploaded_file($_FILES['product_image_1']['tmp_name'], '../uploads/' . $product_image_1);
    move_uploaded_file($_FILES['product_image_2']['tmp_name'], '../uploads/' . $product_image_2);
    move_uploaded_file($_FILES['product_image_3']['tmp_name'], '../uploads/' . $product_image_3);

    addProduct($product_name, $product_price, $product_description, $product_stocks, $product_image_1, $product_image_2, $product_image_3);
}

$conn->close();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
    <style>
        .img-preview {
            max-width: 100%;
            height: auto;
            margin-top: 10px;
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
                <h4>Add New Product</h4>
                <div class="card-body">
                    <form action="add-product.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="product_name">Product Name:</label>
                            <input type="text" class="form-control" name="product_name" required>
                        </div>
                        <div class="form-group">
                            <label for="product_price">Product Price:</label>
                            <input type="text" class="form-control" name="product_price" required>
                        </div>
                        <div class="form-group">
                            <label for="product_description">Product Description:</label>
                            <textarea class="form-control" name="product_description" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="product_stocks">Product Stocks:</label>
                            <input type="number" class="form-control" name="product_stocks" required>
                        </div>
                        <div class="form-group">
                            <label for="product_image_1">Product Image 1:</label>
                            <input type="file" class="form-control-file" name="product_image_1" id="product_image_1" required onchange="previewImage(event, 'preview_image_1')">
                            <img id="preview_image_1" class="img-preview" />
                        </div>
                        <div class="form-group">
                            <label for="product_image_2">Product Image 2:</label>
                            <input type="file" class="form-control-file" name="product_image_2" id="product_image_2" onchange="previewImage(event, 'preview_image_2')">
                            <img id="preview_image_2" class="img-preview" />
                        </div>
                        <div class="form-group">
                            <label for="product_image_3">Product Image 3:</label>
                            <input type="file" class="form-control-file" name="product_image_3" id="product_image_3" onchange="previewImage(event, 'preview_image_3')">
                            <img id="preview_image_3" class="img-preview" />
                        </div>
                        <button type="submit" class="btn btn-success">Add Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Backend Bundle JavaScript -->
    <script src="../assets/js/backend-bundle.min.js"></script>
    <!-- SweetAlert JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
    <script>
        // SweetAlert logic
        document.addEventListener("DOMContentLoaded", function() {
            <?php if ($message == "success") { ?>
                swal("Success!", "New product added successfully!", "success");
            <?php } elseif (strpos($message, 'Error:') !== false) { ?>
                swal("Error!", "<?php echo $message; ?>", "error");
            <?php } ?>
        });

        // Preview Image Function
        function previewImage(event, previewId) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById(previewId);
                output.src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>

    <!-- Table Treeview JavaScript -->
    <script src="../assets/js/table-treeview.js"></script>

    <!-- Chart Custom JavaScript -->
    <script src="../assets/js/customizer.js"></script>

    <!-- Chart Custom JavaScript -->
    <script async src="../assets/js/chart-custom.js"></script>

    <!-- app JavaScript -->
    <script src="../assets/js/app.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>