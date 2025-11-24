<?php
/**
 * PayMongo Integration Functions
 * 
 * This file contains all functions needed to integrate PayMongo payment gateway
 * with the RentMate system.
 */

// Include database connection
require_once dirname(__DIR__) . '/database.php';

/**
 * Get PayMongo API keys from settings
 * 
 * @return array Array containing the public and secret keys
 */
function getPayMongoKeys() {
    global $conn;
    
    $public_key = getSetting('paymongo_public_key', $conn);
    $secret_key = getSetting('paymongo_secret_key', $conn);
    $payment_link = getSetting('paymongo_payment_link', $conn);
    
    // Use provided API payment link as default if not set in settings
    if (empty($payment_link)) {
        $payment_link = "https://pm.link/org-hagodchesspaymentsheshh/test/YYL4GsU";
    }
    
    return [
        'public_key' => $public_key,
        'secret_key' => $secret_key,
        'payment_link' => $payment_link
    ];
}

/**
 * Generate a PayMongo checkout URL with the rental details
 * 
 * @param int $rental_id The rental ID
 * @param float $amount The amount to pay
 * @param string $description Description of the payment
 * @param array $customer Customer details
 * @return string The checkout URL
 */
function generatePaymentLink($rental_id, $amount, $description = '', $customer = [], $payment_type = 'rental') {
    global $conn;
    
    // Create a unique payment link for this rental transaction
    // Format: base_url/rental_id/user_id/timestamp/payment_type
    $timestamp = time();
    $rental = getById('rentals', $rental_id, $conn);
    $customer_id = $rental['customer_id'] ?? 0;
    
    // Get customer info
    $customer_info = getById('customers', $customer_id, $conn);
    $user_id = $customer_info['created_by'] ?? 0;
    
    // Get PayMongo link from settings
    $keys = getPayMongoKeys();
    $base_link = $keys['payment_link'];
    
    // Create a unique reference ID including payment type
    $unique_reference = "rental_{$rental_id}_user_{$user_id}_time_{$timestamp}_type_{$payment_type}";
    
    // Append unique reference ID as a query parameter
    $payment_link = $base_link . "?client_reference_id=" . urlencode($unique_reference);
    
    // Store this unique link in the database for reference
    $payment_data = [
        'rental_id' => $rental_id,
        'payment_link' => $payment_link,
        'created_at' => date('Y-m-d H:i:s'),
        'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
        'status' => 'active'
    ];
    
    // Check if we have a payments_links table, if not create it
    $sql = "SHOW TABLES LIKE 'payment_links'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        // Create the table
        $sql = "CREATE TABLE payment_links (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            rental_id INT(11) NOT NULL,
            payment_link VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active'
        )";
        $conn->query($sql);
    }
    
    // Insert the payment link
    insert('payment_links', $payment_data, $conn);
    
    return $payment_link;
}

/**
 * Create a payment record for PayMongo transaction
 * 
 * @param int $rental_id The rental ID
 * @param float $amount The amount paid
 * @param string $payment_type Type of payment (rental, deposit, etc.)
 * @param int $received_by User ID who received the payment
 * @return int|bool The payment ID if successful, false otherwise
 */
function createPayMongoPayment($rental_id, $amount, $payment_type = 'full_payment', $received_by = null, $payment_method = 'paymongo') {
    global $conn;
    
    // Log the function call with detailed parameters
    error_log("createPayMongoPayment called with parameters:");
    error_log("  - Rental ID: $rental_id");
    error_log("  - Amount: $amount");
    error_log("  - Payment Type: $payment_type");
    error_log("  - Payment Method: $payment_method");
    
    try {
        // Generate a unique client reference ID
        $timestamp = time();
        $rental = getById('rentals', $rental_id, $conn);
        if (!$rental) {
            error_log("Rental not found: $rental_id");
            return false;
        }
        
        $customer_id = $rental['customer_id'] ?? 0;
        $customer_info = getById('customers', $customer_id, $conn);
        $user_id = $customer_info['created_by'] ?? 0;
        
        // Standardize payment type and method
        $payment_type_lower = strtolower($payment_type);
        if ($payment_type_lower === 'downpayment' || $payment_type_lower === 'down payment' || 
            $payment_type_lower === 'down_payment' || $payment_type_lower === 'dp') {
            $payment_types = 'downpayment';
            $amount = $rental['total_amount'] * 0.25; // Set amount to 25% for downpayment
        } else {
            $payment_types = 'full_payment';
            $amount = $rental['total_amount']; // Full amount for full payment
        }
        
        // Create a unique reference including both payment type and method
        $unique_reference = "rental_{$rental_id}_user_{$user_id}_time_{$timestamp}_type_{$payment_types}_method_{$payment_method}";
        
        // Log what we're creating
        error_log("Creating PayMongo payment:");
        error_log("  - Rental ID: $rental_id");
        error_log("  - Amount: $amount");
        error_log("  - Payment Type: $payment_types");
        error_log("  - Payment Method: $payment_method");
        error_log("  - Reference: $unique_reference");
        
        // Create a payment link via PayMongo API
        $description = "Payment for Rental #$rental_id";
        $payment_link = createPayMongoLinkViaAPI($amount, $description, $unique_reference);
        
        if (!$payment_link) {
            error_log("Failed to create payment link for rental $rental_id");
            return false;
        }
        
        // Use direct SQL to insert the payment record
        $payment_date = date('Y-m-d');
        $notes = "Payment via $payment_method";
        $received_by_value = $received_by ? $received_by : "NULL";
        
        // Escape strings
        $payment_method_escaped = $conn->real_escape_string($payment_method);
        $payment_types_escaped = $conn->real_escape_string($payment_types);
        $notes_escaped = $conn->real_escape_string($notes);
        $payment_link_escaped = $conn->real_escape_string($payment_link);
        
        // Escape the unique reference for database storage
        $unique_reference_escaped = $conn->real_escape_string($unique_reference);
        
        $sql = "INSERT INTO payments 
                (rental_id, amount, payment_date, payment_method, payment_type, payment_types, payment_status, notes, received_by, reference_number, transaction_reference) 
                VALUES 
                ($rental_id, $amount, '$payment_date', '$payment_method_escaped', 'rental', '$payment_types_escaped', 'pending', '$notes_escaped', $received_by_value, '$unique_reference_escaped', '$payment_link_escaped')";
        
        error_log("SQL query: $sql");
        
        if ($conn->query($sql)) {
            $payment_id = $conn->insert_id;
            error_log("Successfully created payment record - ID: $payment_id, Link: $payment_link");
            
            // Create payment_forms record with the specific payment method
            $payment_form_data = [
                'payment_id' => $payment_id,
                'customer_name' => '', // Will be populated when payment is verified
                'email' => '', // Will be populated when payment is verified
                'contact_number' => '', // Will be populated when payment is verified
                'payment_method' => $payment_method // Store the actual payment method (gcash, grabpay, etc.)
            ];
            
            // Insert payment form record
            $payment_form_id = insert('payment_forms', $payment_form_data, $conn);
            error_log("Created payment_forms record - ID: $payment_form_id, Method: $payment_method");
            
            return $payment_id;
        } else {
            error_log("Failed to insert payment record: " . $conn->error);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error in createPayMongoPayment: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        return false;
    }
}

/**
 * Update payment status after PayMongo webhook notification
 * 
 * @param int $payment_id Payment ID to update
 * @param string $transaction_id PayMongo transaction ID
 * @param string $status New payment status
 * @param string $reference_number Internal reference number (rental_xxx format)
 * @param string $sender_name Name of the sender (optional)
 * @param string $sender_contact Contact of the sender (optional)
 * @param string $paymongo_link_id PayMongo link ID (optional)
 * @return bool True if update successful, false otherwise
 */
function updatePaymentStatus($payment_id, $transaction_id, $status, $reference_number = null, $sender_name = null, $sender_contact = null, $paymongo_link_id = null) {
    global $conn;
    
    $payment_data = [
        'transaction_id' => $transaction_id,
        'payment_status' => $status,
        'reference_number' => $reference_number
    ];
    
    // Add PayMongo link ID if provided
    if (!empty($paymongo_link_id)) {
        $payment_data['paymongo_link_id'] = $paymongo_link_id;
    }
    
    // REMOVED: Automatic verification - payments now require manual verification
    // For PayMongo webhooks, only update transaction details but keep status as 'pending'
    if ($status == 'completed' && !empty($transaction_id)) {
        // Don't auto-verify - keep payment as 'pending' until manual verification
        $payment_data['payment_status'] = 'pending'; // Override to keep as pending
        $payment_data['notes'] = 'PayMongo webhook received - Manual verification required';
    }
    
    // Add sender information if provided
    if (!empty($sender_name)) {
        $payment_data['sender_name'] = $sender_name;
    }
    
    if (!empty($sender_contact)) {
        $payment_data['sender_contact'] = $sender_contact;
    }
    
    // Add note about sender verification information
    if (!empty($sender_name) || !empty($sender_contact)) {
        $payment = getById('payments', $payment_id, $conn);
        $current_notes = $payment['notes'] ?? '';
        
        $verification_note = "Payment verified with ";
        if (!empty($sender_name)) $verification_note .= "Name: " . $sender_name;
        if (!empty($sender_name) && !empty($sender_contact)) $verification_note .= ", ";
        if (!empty($sender_contact)) $verification_note .= "Contact: " . $sender_contact;
        
        $payment_data['notes'] = $current_notes . " | " . $verification_note;
        
        // Save to payment_forms table if this is a completed payment
        if ($status == 'completed') {
            // Check if payment form entry already exists
            $existing_form = getWhere('payment_forms', "payment_id = $payment_id", $conn);
            
            if (empty($existing_form)) {
                $payment_form_data = [
                    'payment_id' => $payment_id,
                    'customer_name' => $sender_name,
                    'email' => '',  // Can be updated later if available
                    'contact_number' => $sender_contact ?? '',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                try {
                    insert('payment_forms', $payment_form_data, $conn);
                } catch (Exception $e) {
                    error_log("Error saving payment form data: " . $e->getMessage());
                }
            }
        }
    }
    
    return update('payments', $payment_data, $payment_id, $conn);
}

/**
 * Get payment by client_reference_id or transaction_reference
 * 
 * @param string $client_reference_id The client reference ID from PayMongo
 * @return array|null Payment record if found, null otherwise
 */
function getPaymentByClientReferenceId($client_reference_id) {
    global $conn;
    
    // CRITICAL FIX: Look in reference_id column, not transaction_reference, and support multiple PayMongo payment methods
    $paymongo_methods = ['gcash', 'grabpay', 'maya', 'bpi_online', 'card', 'unionbank_online', 'paymongo'];
    $methods_sql = "'" . implode("','", $paymongo_methods) . "'";
    
    // First try exact match in reference_id (for internal references)
    $sql = "SELECT * FROM payments WHERE reference_id = '" . $conn->real_escape_string($client_reference_id) . "' AND payment_method IN ($methods_sql) LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // CRITICAL FIX: If not found and this looks like a PayMongo reference, search in PayMongo link fields
    if (!preg_match('/rental_(\d+)/', $client_reference_id)) {
        // This is likely a PayMongo reference - search in paymongo_link_id and transaction_reference
        $paymongo_search_sql = "SELECT * FROM payments WHERE 
                               (paymongo_link_id LIKE '%" . $conn->real_escape_string($client_reference_id) . "%' OR 
                                transaction_reference LIKE '%" . $conn->real_escape_string($client_reference_id) . "%') 
                               AND payment_method IN ($methods_sql) 
                               AND payment_status = 'pending' 
                               ORDER BY id DESC LIMIT 1";
        $paymongo_result = $conn->query($paymongo_search_sql);
        
        if ($paymongo_result && $paymongo_result->num_rows > 0) {
            return $paymongo_result->fetch_assoc();
        }
    }
    
    return null;
}

/**
 * Process PayMongo webhook
 * 
 * @param string $payload The webhook payload
 * @return bool True if processed successfully, false otherwise
 */
function processPayMongoWebhook($payload) {
    global $conn;
    
    // Decode the payload
    $data = json_decode($payload, true);
    
    if (!$data) {
        error_log('Invalid PayMongo webhook payload');
        return false;
    }
    
    // Extract relevant information
    $event_type = $data['type'] ?? '';
    $data = $data['data'] ?? [];
    
    if (empty($event_type) || empty($data)) {
        error_log('Missing event type or data in PayMongo webhook');
        return false;
    }
    
    // Process based on event type
    switch ($event_type) {
        case 'payment.paid':
            // Payment successful
            $transaction_id = $data['id'] ?? '';
            $reference_id = $data['attributes']['client_reference_id'] ?? '';
            
            // Try to find payment by client_reference_id
            $payment = getPaymentByClientReferenceId($reference_id);
            
            if ($payment) {
                // Update payment status
                updatePaymentStatus($payment['id'], $transaction_id, 'completed', $data['attributes']['reference_number'] ?? null);
                
                // Get rental information for notifications (DO NOT auto-activate rental)
                $rental_id = $payment['rental_id'];
                $rental = getById('rentals', $rental_id, $conn);
                if ($rental) {
                    // Create notification for staff that payment needs verification
                    $staff_users = getWhere('users', "role IN ('admin', 'staff') AND is_active = 1", $conn);
                    foreach ($staff_users as $staff) {
                        $notification_data = [
                            'user_id' => $staff['id'],
                            'title' => 'Payment Received - Verification Required',
                            'message' => "Payment completed for Rental #$rental_id via PayMongo. Please verify the payment to activate the rental.",
                            'type' => 'warning',
                            'related_to' => 'rental',
                            'related_id' => $rental_id,
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        insert('notifications', $notification_data, $conn);
                    }
                }
                
                return true;
            } else {
                // Fallback: extract rental ID and update first pending payment
                if (preg_match('/rental_(\d+)/', $reference_id, $matches)) {
                    $rental_id = $matches[1];
                    
                    $payments = getWhere('payments', "rental_id = $rental_id AND payment_status = 'pending' AND payment_method = 'paymongo'", $conn);
                    
                    if (!empty($payments)) {
                        $payment = $payments[0];
                        
                        updatePaymentStatus($payment['id'], $transaction_id, 'completed', $data['attributes']['reference_number'] ?? null);
                        
                        $rental = getById('rentals', $rental_id, $conn);
                        if ($rental && $rental['status'] == 'pending') {
                            update('rentals', ['status' => 'active'], $rental_id, $conn);
                        }
                        
                        return true;
                    }
                }
            }
            break;
            
        case 'payment.failed':
            // Payment failed
            $transaction_id = $data['id'] ?? '';
            $reference_id = $data['attributes']['client_reference_id'] ?? '';
            
            // Try to find payment by client_reference_id
            $payment = getPaymentByClientReferenceId($reference_id);
            
            if ($payment) {
                updatePaymentStatus($payment['id'], $transaction_id, 'failed');
                return true;
            } else {
                // Fallback: extract rental ID and update first pending payment
                if (preg_match('/rental_(\d+)/', $reference_id, $matches)) {
                    $rental_id = $matches[1];
                    
                    $payments = getWhere('payments', "rental_id = $rental_id AND payment_status = 'pending' AND payment_method = 'paymongo'", $conn);
                    
                    if (!empty($payments)) {
                        $payment = $payments[0];
                        updatePaymentStatus($payment['id'], $transaction_id, 'failed');
                        return true;
                    }
                }
            }
            break;
    }
    
    return false;
}

/**
 * Check if a rental has been paid via PayMongo
 * 
 * @param int $rental_id The rental ID
 * @return bool True if paid, false otherwise
 */
function isRentalPaidViaPayMongo($rental_id) {
    global $conn;
    
    $payments = getWhere('payments', "rental_id = $rental_id AND payment_method = 'paymongo' AND payment_status = 'completed'", $conn);
    
    return !empty($payments);
}

/**
 * Get the payment link for a specific rental
 * 
 * @param int $rental_id The rental ID
 * @return string|null The payment link if found, null otherwise
 */
function getPaymentLinkForRental($rental_id, $payment_type = 'rental') {
    global $conn;
    
    // First check if we have a stored payment link
    $sql = "SHOW TABLES LIKE 'payment_links'";
    $result = $conn->query($sql);
    
    // Check if the rental is already paid for this payment type
    $payment_check = "SELECT * FROM payments WHERE rental_id = $rental_id AND payment_method = 'paymongo' AND payment_type = '$payment_type' AND payment_status = 'completed'";
    $payment_result = $conn->query($payment_check);
    if ($payment_result && $payment_result->num_rows > 0) {
        // Payment already completed, no need for a payment link
        return null;
    }
    
    if ($result->num_rows > 0) {
        // Table exists, check for active payment link for this payment type
        $links = getWhere('payment_links', "rental_id = $rental_id AND status = 'active' AND expires_at > NOW()", $conn);
        
        if (!empty($links)) {
            // Return all active links for this rental (ignoring payment_type filter)
            // You may modify this to return all links or the most recent one as needed
            return $links[0]['payment_link'];
        }
    }
    
    // Check if there's a transaction reference in the payments table for this payment type
    $payments = getWhere('payments', "rental_id = $rental_id AND payment_status = 'pending' AND payment_method = 'paymongo' AND payment_type = '$payment_type'", $conn);
    
    if (!empty($payments) && !empty($payments[0]['transaction_reference'])) {
        return $payments[0]['transaction_reference'];
    }
    
    // No existing link found, generate a new one
    $rental = getById('rentals', $rental_id, $conn);
    if ($rental) {
        return generatePaymentLink($rental_id, $rental['total_amount'], "Payment for Rental #$rental_id", [], $payment_type);
    }
    
    return null;
}

/**
 * Check payment status for a rental
 * 
 * @param int $rental_id The rental ID
 * @return array Payment status information
 */
function checkRentalPaymentStatus($rental_id) {
    global $conn;
    
    $result = [
        'is_paid' => false,
        'payment_method' => null,
        'payment_date' => null,
        'payment_status' => 'not_found',
        'reference_number' => null
    ];
    
    // Check for completed payments first
    $sql = "SELECT * FROM payments 
            WHERE rental_id = $rental_id 
            AND payment_status = 'completed' 
            ORDER BY payment_date DESC 
            LIMIT 1";
    $query = $conn->query($sql);
    
    if ($query && $query->num_rows > 0) {
        $payment = $query->fetch_assoc();
        $result['is_paid'] = true;
        $result['payment_method'] = $payment['payment_method'];
        $result['payment_date'] = $payment['payment_date'];
        $result['payment_status'] = 'completed';
        $result['reference_number'] = $payment['reference_number'];
        return $result;
    }
    
    // Check for pending payments
    $sql = "SELECT * FROM payments 
            WHERE rental_id = $rental_id 
            AND payment_status = 'pending' 
            ORDER BY payment_date DESC 
            LIMIT 1";
    $query = $conn->query($sql);
    
    if ($query && $query->num_rows > 0) {
        $payment = $query->fetch_assoc();
        $result['payment_method'] = $payment['payment_method'];
        $result['payment_date'] = $payment['payment_date'];
        $result['payment_status'] = 'pending';
        $result['reference_number'] = $payment['reference_number'];
    }
    
    return $result;
}

/**
 * Create a payment link directly via PayMongo API
 * 
 * @param float $amount The amount to pay (in PHP)
 * @param string $description Payment description
 * @param string $reference_id Client reference ID (e.g. rental_123)
 * @param array $customer_data Optional customer data (name, email, phone)
 * @return string|bool The checkout URL if successful, false otherwise
 */
function createPayMongoLinkViaAPI($amount, $description = '', $reference_id = '', $customer_data = []) {
    global $conn;
    
    // Get API keys
    $keys = getPayMongoKeys();
    $secretKey = $keys['secret_key'];
    
    if (empty($secretKey)) {
        error_log('PayMongo secret key not configured');
        return false;
    }
    
    // Convert to centavos (smallest currency unit)
    $amount = round($amount * 100);
    
    // Define the data payload
    $data = [
        "data" => [
            "attributes" => [
                "amount" => $amount,
                "currency" => "PHP",
                "description" => $description,
                "remarks" => "Payment via RentMate",
            ]
        ]
    ];
    
    // Add reference ID if provided - PayMongo uses external_reference_number for webhooks
    if (!empty($reference_id)) {
        $data["data"]["attributes"]["external_reference_number"] = $reference_id;
    }
    
    // Add customer billing details if provided
    if (!empty($customer_data)) {
        $billing = [];
        
        if (!empty($customer_data['name'])) {
            $billing['name'] = $customer_data['name'];
        }
        
        if (!empty($customer_data['email'])) {
            $billing['email'] = $customer_data['email'];
        }
        
        if (!empty($customer_data['phone'])) {
            $billing['phone'] = $customer_data['phone'];
        }
        
        if (!empty($billing)) {
            $data["data"]["attributes"]["billing"] = $billing;
        }
    }
    
    // Log the API request
    $logFile = __DIR__ . '/paymongo_api_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "\n[{$timestamp}] Creating payment link...\n", FILE_APPEND);
    file_put_contents($logFile, "[{$timestamp}] Amount: ₱" . ($amount/100) . "\n", FILE_APPEND);
    file_put_contents($logFile, "[{$timestamp}] Reference ID: {$reference_id}\n", FILE_APPEND);
    file_put_contents($logFile, "[{$timestamp}] Request data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    
    try {
        // Initialize cURL
        $ch = curl_init();
        if ($ch === false) {
            throw new Exception("Failed to initialize cURL");
        }
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, "https://api.paymongo.com/v1/links");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Basic " . base64_encode($secretKey . ":")
        ]);
        
        // Execute the request
        $result = curl_exec($ch);
        if ($result === false) {
            throw new Exception("cURL Error: " . curl_error($ch));
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log the API response
        file_put_contents($logFile, "[{$timestamp}] API Response Code: {$httpCode}\n", FILE_APPEND);
        file_put_contents($logFile, "[{$timestamp}] API Response: {$result}\n", FILE_APPEND);
        
        // Decode the response
        $response = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to decode API response: " . json_last_error_msg());
        }
        
        // Check for API error response
        if (isset($response['errors'])) {
            $error_msg = isset($response['errors'][0]['detail']) ? 
                        $response['errors'][0]['detail'] : 
                        'Unknown API error';
            throw new Exception("API Error: " . $error_msg);
        }
        
        // Check if the Payment Link was created successfully
        if ($httpCode == 200 && isset($response['data']['attributes']['checkout_url'])) {
            $checkout_url = $response['data']['attributes']['checkout_url'];
            $paymongo_reference = $response['data']['attributes']['reference_number'] ?? '';
            
            // Store the payment link in database if needed
            if (!empty($reference_id) && preg_match('/rental_(\d+)/', $reference_id, $matches)) {
                $rental_id = $matches[1];
                
                $payment_data = [
                    'rental_id' => $rental_id,
                    'payment_link' => $checkout_url,
                    'created_at' => date('Y-m-d H:i:s'),
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                    'status' => 'active'
                ];
                
                // Check if payment_links table exists
                $sql = "SHOW TABLES LIKE 'payment_links'";
                $result = $conn->query($sql);
                
                if ($result->num_rows == 0) {
                    // Create the table
                    $sql = "CREATE TABLE payment_links (
                        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        rental_id INT(11) NOT NULL,
                        payment_link VARCHAR(255) NOT NULL,
                        created_at DATETIME NOT NULL,
                        expires_at DATETIME NOT NULL,
                        status VARCHAR(20) NOT NULL DEFAULT 'active'
                    )";
                    $conn->query($sql);
                }
                
                // Insert the payment link
                $insert_result = insert('payment_links', $payment_data, $conn);
                
                // Log the insertion result
                if ($insert_result) {
                    file_put_contents($logFile, "[{$timestamp}] Successfully saved payment link to database - ID: $insert_result\n", FILE_APPEND);
                } else {
                    file_put_contents($logFile, "[{$timestamp}] ERROR: Failed to save payment link to database\n", FILE_APPEND);
                    error_log("Failed to insert payment link: " . json_encode($payment_data));
                }
            }
            
            file_put_contents($logFile, "[{$timestamp}] Successfully created payment link: {$checkout_url}\n", FILE_APPEND);
            
            // Return both checkout URL and PayMongo reference number
            return [
                'checkout_url' => $checkout_url,
                'reference_number' => $paymongo_reference
            ];
        } else {
            throw new Exception("Unexpected response: HTTP {$httpCode}");
        }
        
    } catch (Exception $e) {
        file_put_contents($logFile, "[{$timestamp}] Error creating payment link: " . $e->getMessage() . "\n", FILE_APPEND);
        error_log("PayMongo API Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify if a payment exists in PayMongo's system using their API
 * 
 * @param string $reference_number The reference number to verify
 * @return array|bool Returns payment details if found, false otherwise
 */
function verifyPaymentWithPayMongo($reference_number) {
    global $conn;
    
    // First check if we have the details in our payment_forms table
    $sql = "SELECT pf.*, p.payment_method, p.reference_number 
            FROM payments p 
            LEFT JOIN payment_forms pf ON p.id = pf.payment_id 
            WHERE p.reference_number = '" . $conn->real_escape_string($reference_number) . "'";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $local_data = $result->fetch_assoc();
        // If we have local data, return it in a format similar to PayMongo response
        return [
            'attributes' => [
                'billing' => [
                    'name' => $local_data['customer_name'],
                    'email' => $local_data['email'],
                    'phone' => $local_data['contact_number']
                ],
                'source' => [
                    'type' => $local_data['payment_method']
                ]
            ]
        ];
    }
    
    // If no local data, try PayMongo API
    $keys = getPayMongoKeys();
    $secret_key = $keys['secret_key'];
    
    if (empty($secret_key)) {
        error_log('PayMongo secret key not configured');
        return false;
    }
    
    // Clean the reference number
    $clean_reference = preg_replace('/^(staff-confirm-|manual-confirm-)/', '', $reference_number);
    
    // Log the verification attempt
    error_log("Attempting to verify PayMongo payment with reference: $reference_number (cleaned: $clean_reference)");
    
    $ch = curl_init();
    $url = "https://api.paymongo.com/v1/payments/$clean_reference";  // Changed to direct payment fetch
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($secret_key . ':'),
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log the response
    error_log("PayMongo API response code: $http_code");
    error_log("PayMongo API response: $response");
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        if (!empty($data['data'])) {
            // Log the successful API response for debugging
            error_log("PayMongo API verification successful for reference: $reference_number");
            error_log("API Response data: " . json_encode($data['data']));
            
            // Extract and log the transaction ID
            $transaction_id = $data['data']['id'] ?? '';
            if (!empty($transaction_id)) {
                error_log("Extracted transaction_id: $transaction_id from PayMongo API");
            }
            
            return $data['data'];
        }
    } else {
        error_log("PayMongo API verification failed for reference: $reference_number - HTTP Code: $http_code");
        error_log("API Response: $response");
    }
    
    return false;
}

/**
 * Check if a payment reference is valid
 * 
 * @param string $reference_number Reference number to check
 * @param string $payment_method Payment method used
 * @return bool True if valid, false otherwise
 */
function isValidPaymentReference($reference_number, $payment_method) {
    // If this is a staff-confirmed payment, it's automatically valid
    if (strpos($reference_number, 'staff-confirm-') === 0) {
        return true;
    }
    
    // If this is a manual confirmation, it's automatically valid
    if (strpos($reference_number, 'manual-confirm-') === 0) {
        return true;
    }
    
    // For cash payments, any reference is valid
    if ($payment_method == 'cash') {
        return true;
    }
    
    // For bank transfers, reference should be at least 6 characters
    if ($payment_method == 'bank_transfer') {
        return strlen($reference_number) >= 6;
    }
    
    // For GCash, reference should be at least 10 characters
    if ($payment_method == 'gcash') {
        return strlen($reference_number) >= 10;
    }
    
    // For PayMongo, verify with API
    if ($payment_method == 'paymongo') {
        // Try to verify with PayMongo API
        $result = verifyPaymentWithPayMongo($reference_number);
        return $result !== false;
    }
    
    // For other payment methods, any non-empty reference is valid
    return !empty($reference_number);
}
?> 