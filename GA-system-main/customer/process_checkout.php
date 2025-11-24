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

// Fetch form data from POST request
$customer_name = $_POST['customer_name'];
$customer_number = $_POST['customer_number'];
$customer_email = $_POST['customer_email'];
$customer_address_1 = $_POST['customer_address_1'];
$customer_address_2 = $_POST['customer_address_2'];
$customer_city = $_POST['customer_city'];
$customer_municipality = $_POST['customer_municipality'];
$customer_zipcode = $_POST['customer_zipcode'];
$delivery_id = $_POST['delivery_type'];

// Construct the shipping address
$shipping_address = $customer_address_1;
if (!empty($customer_address_2)) {
    $shipping_address .= ', ' . $customer_address_2;
}
$shipping_address .= ', ' . $customer_city . ', ' . $customer_municipality . ', ' . $customer_zipcode;

// Retrieve selected order details from tbl_order for the current customer
$sql = "SELECT order_id, product_id, item_quantity FROM tbl_order WHERE customerid = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement for fetching order details: " . $conn->error);
}
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$order_details = [];

// Fetch product price for each order and calculate subtotal
while ($row = $result->fetch_assoc()) {
    $product_id = $row['product_id'];
    $item_quantity = $row['item_quantity'];

    $sql_price = "SELECT product_price FROM tbl_product WHERE product_id = ?";
    $stmt_price = $conn->prepare($sql_price);
    if ($stmt_price === false) {
        die("Error preparing statement for fetching product price: " . $conn->error);
    }
    $stmt_price->bind_param("i", $product_id);
    $stmt_price->execute();
    $result_price = $stmt_price->get_result();

    if ($result_price->num_rows > 0) {
        $row_price = $result_price->fetch_assoc();
        $product_price = $row_price['product_price'];

        // Calculate the subtotal for each item
        $subtotal = $item_quantity * $product_price;

        // Store each order item along with its subtotal
        $row['subtotal'] = $subtotal;
        $order_details[] = $row;
    } else {
        echo "<script>alert('Invalid product selected. Please try again.'); window.history.back();</script>";
        exit();
    }
}

// Generate a tracking number (10-character alphanumeric)
function generateTrackingNumber($length = 10)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $trackingNumber = '';
    for ($i = 0; $i < $length; $i++) {
        $trackingNumber .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $trackingNumber;
}
$tracking_number = generateTrackingNumber();

// Insert data into tbl_orders
$conn->begin_transaction();
try {
    foreach ($order_details as $order) {
        $order_id = $order['order_id'];
        $product_id = $order['product_id'];
        $item_quantity = $order['item_quantity'];
        $subtotal = $order['subtotal'];

        // Insert the main order details including each product into tbl_orders
        $sql = "INSERT INTO `tbl_orders` (`customerid`, `orders_date`, `shipping_address`, `total_amount`, `orders_status`, `delivery_id`, `payment_status`, `tracking_number`, `created_at`, `updated_at`, `order_id`, `item_quantity`, `product_id`) 
                VALUES (?, NOW(), ?, ?, 'Pending', ?, 'Unpaid', ?, NOW(), NOW(), ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Error preparing order insert statement: " . $conn->error);
        }
        // Binding parameters: customerid (i), shipping_address (s), total_amount (d), delivery_id (s), tracking_number (s), order_id (i), item_quantity (i), product_id (i)
        $stmt->bind_param("isdssiii", $customer_id, $shipping_address, $subtotal, $delivery_id, $tracking_number, $order_id, $item_quantity, $product_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to place the order. Error: " . $stmt->error);
        }
    }

    // Delete selected items from tbl_order after successful insertion
    $delete_sql = "DELETE FROM `tbl_order` WHERE `customerid` = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    if ($delete_stmt === false) {
        throw new Exception("Error preparing deletion of cart items statement: " . $conn->error);
    }
    $delete_stmt->bind_param("i", $customer_id);
    if ($delete_stmt->execute()) {
        // Commit the transaction
        $conn->commit();
        echo "<script>alert('Order placed successfully! Tracking Number: {$tracking_number}'); window.location.href='order-confirmation.php';</script>";
    } else {
        throw new Exception("Order placed but failed to remove items from the cart. Error: " . $delete_stmt->error);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('Failed to place order. " . $e->getMessage() . "'); window.history.back();</script>";
}

$conn->close();
