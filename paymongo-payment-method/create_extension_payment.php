<?php
// Separate AJAX endpoint for creating extension payments
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
    
    // Check if user is logged in and is a user (renter)
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
        throw new Exception('User not logged in');
    }
    
    // Check if extension ID is provided
    if (!isset($_GET['extension_id'])) {
        throw new Exception('Extension ID not provided');
    }
    
    $extension_id = (int)$_GET['extension_id'];
    $user_id = $_SESSION['user_id'];
    
    // Get customer record
    $customer_query = "SELECT * FROM customers WHERE created_by = $user_id";
    $customer_result = $conn->query($customer_query);
    $customer = $customer_result->fetch_assoc();
    
    if (!$customer) {
        throw new Exception('Customer record not found');
    }
    
    $customer_id = $customer['id'];
    
    // Get extension details
    $extension_query = "SELECT re.*, r.customer_id, r.item_id, r.total_amount as rental_amount,
                               i.name as item_name, i.image as item_image, i.daily_rate,
                               u.name as owner_name, u.email as owner_email
                        FROM rental_extensions re 
                        JOIN rentals r ON re.rental_id = r.id
                        JOIN items i ON r.item_id = i.id
                        JOIN users u ON i.owner_id = u.id
                        WHERE re.id = $extension_id 
                        AND r.customer_id = $customer_id 
                        AND re.status = 'approved'";
    
    $extension_result = $conn->query($extension_query);
    
    if ($extension_result->num_rows == 0) {
        throw new Exception('Extension not found');
    }
    
    $extension = $extension_result->fetch_assoc();
    
    // Create extension_payments table if it doesn't exist
    $create_extension_payments_table = "CREATE TABLE IF NOT EXISTS extension_payments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        extension_id INT NOT NULL,
        rental_id INT NOT NULL,
        customer_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'paymongo',
        payment_status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
        paymongo_payment_id VARCHAR(255),
        paymongo_checkout_url TEXT,
        reference_number VARCHAR(255),
        transaction_id VARCHAR(255),
        payment_date DATETIME,
        verified_by INT,
        verified_at DATETIME,
        verification_notes TEXT,
        payer_name VARCHAR(255),
        payer_email VARCHAR(255),
        payer_phone VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (extension_id) REFERENCES rental_extensions(id) ON DELETE CASCADE,
        FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
        FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if (!$conn->query($create_extension_payments_table)) {
        error_log("Error creating extension_payments table: " . $conn->error);
    }
    
    $payment_method = $_POST['payment_method'] ?? 'paymongo';
    $amount = $extension['estimated_cost'];
    
    $conn->begin_transaction();
    
    // Create extension payment record
    $insert_payment = "INSERT INTO extension_payments (
                          extension_id, rental_id, customer_id, amount, payment_method, payment_status
                      ) VALUES (
                          $extension_id, {$extension['rental_id']}, $customer_id, $amount, '$payment_method', 'pending'
                      )";
    
    if (!$conn->query($insert_payment)) {
        throw new Exception("Failed to create extension payment record: " . $conn->error);
    }
    
    $payment_id = $conn->insert_id;
    
    // Create PayMongo payment link for extension payment
    $description = "Extension Payment for Rental #{$extension['rental_id']} - {$extension['item_name']} ({$extension['extension_days']} days)";
    $reference_id = "extension_{$extension_id}_rental_{$extension['rental_id']}_user_{$user_id}_time_" . time();
    
    // Create payment link via API (same as regular payments)
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
    $update_payment = "UPDATE extension_payments SET 
                      paymongo_payment_id = '" . $conn->real_escape_string($reference_id) . "',
                      paymongo_checkout_url = '" . $conn->real_escape_string($checkout_url) . "',
                      reference_number = '" . $conn->real_escape_string($paymongo_reference) . "'
                      WHERE id = $payment_id";
    
    if (!$conn->query($update_payment)) {
        throw new Exception("Failed to update payment with PayMongo details: " . $conn->error);
    }
    
    $conn->commit();
    
    // Clear any output buffer and return success JSON
    ob_clean();
    echo json_encode(['success' => true, 'checkout_url' => $checkout_url]);
    exit();
    
} catch (Exception $e) {
    // Rollback transaction if it was started
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Log error
    error_log("Extension payment creation failed: " . $e->getMessage());
    
    // Clear any output buffer and return error JSON
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit();
}
?>
