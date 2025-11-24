<?php
// Separate AJAX endpoint for creating regular payments
// This file ONLY handles AJAX requests and returns JSON

// Disable all error display and output buffering
ini_set('display_errors', 0);
error_reporting(0);
ob_start();

// Set JSON header immediately
header('Content-Type: application/json');

try {
    // Include required files
    require_once '../database.php';
    require_once 'paymongo_api_functions.php';
    
    // Start session
    session_start();
    
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Check if this is an AJAX request
    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        throw new Exception('Not an AJAX request');
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }
    
    // Get rental ID and user ID from URL parameters
    $rental_id = isset($_GET['rental_id']) ? intval($_GET['rental_id']) : 0;
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    // Validate inputs
    if ($rental_id <= 0 || $user_id <= 0) {
        throw new Exception('Invalid rental ID or user ID');
    }
    
    // Get rental details with item information
    $rental_query = "SELECT r.*, i.daily_rate as item_daily_rate 
                     FROM rentals r 
                     JOIN items i ON r.item_id = i.id 
                     WHERE r.id = $rental_id";
    $rental_result = $conn->query($rental_query);
    
    if (!$rental_result || $rental_result->num_rows == 0) {
        throw new Exception('Rental not found');
    }
    
    $rental = $rental_result->fetch_assoc();
    
    // Get customer info
    $customer_id = $rental['customer_id'] ?? 0;
    $customer_info = getById('customers', $customer_id, $conn);
    $rental_user_id = $customer_info['created_by'] ?? 0;
    
    // Verify ownership
    if ($rental_user_id != $_SESSION['user_id'] || $rental_user_id != $user_id) {
        throw new Exception('Unauthorized access');
    }
    
    // Check if rental is in pending status
    if ($rental['status'] !== 'pending') {
        throw new Exception('Invalid rental status');
    }
    
    // Check for existing completed payment
    $existing_payment = $conn->query("SELECT * FROM payments WHERE rental_id = $rental_id AND payment_status = 'completed' LIMIT 1");
    if ($existing_payment && $existing_payment->num_rows > 0) {
        throw new Exception('Payment already completed');
    }
    
    // Get payment details from POST
    $payment_type = $_POST['payment_type'] ?? 'full_payment';
    $payment_method = $_POST['payment_method'] ?? 'gcash';
    
    // Calculate payment amount
    $rental_total = $rental['total_amount'];
    $paymongo_minimum = 100.00;
    
    if ($payment_type === 'downpayment') {
        $downpayment_amount = $rental_total * 0.25;
        $amount = $downpayment_amount < $paymongo_minimum ? $paymongo_minimum : $downpayment_amount;
    } else {
        $amount = $rental_total;
    }
    
    // Create payment record
    $insert_payment = "INSERT INTO payments (
                          rental_id, amount, payment_method, payment_status, payment_types, payment_date
                      ) VALUES (
                          $rental_id, $amount, '$payment_method', 'pending', '$payment_type', CURDATE()
                      )";
    
    if (!$conn->query($insert_payment)) {
        throw new Exception("Failed to create payment record: " . $conn->error);
    }
    
    $payment_id = $conn->insert_id;
    
    // Create PayMongo payment link
    $description = "Rental Payment for Item #{$rental['item_id']} - Amount: ₱" . number_format($amount, 2);
    $reference_id = "rental_{$rental_id}_payment_{$payment_id}_user_{$user_id}_time_" . time();
    
    // Create payment link via API
    $paymongo_result = createPayMongoLinkViaAPI(
        $amount, 
        $description, 
        $reference_id
    );
    
    if (!$paymongo_result) {
        throw new Exception("Failed to create PayMongo payment link");
    }
    
    // Extract checkout URL and PayMongo reference number
    $checkout_url = $paymongo_result['checkout_url'];
    $paymongo_reference = $paymongo_result['reference_number'];
    
    // Update payment record with PayMongo details
    $update_payment = "UPDATE payments SET 
                      reference_id = '" . $conn->real_escape_string($reference_id) . "',
                      paymongo_link_id = '" . $conn->real_escape_string($checkout_url) . "',
                      transaction_reference = '" . $conn->real_escape_string($paymongo_reference) . "'
                      WHERE id = $payment_id";
    
    if (!$conn->query($update_payment)) {
        throw new Exception("Failed to update payment with PayMongo details: " . $conn->error);
    }
    
    // Clear any output buffer and return success JSON
    ob_clean();
    echo json_encode(['success' => true, 'checkout_url' => $checkout_url]);
    exit();
    
} catch (Exception $e) {
    // Log error
    error_log("Regular payment creation failed: " . $e->getMessage());
    
    // Clear any output buffer and return error JSON
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit();
}
?>
