<?php
/**
 * PayMongo Webhook Handler - COMPREHENSIVE FIX VERSION
 * 
 * This file handles webhook notifications from PayMongo
 * COMPREHENSIVE FIX: Enhanced debugging, better error handling, and ngrok compatibility
 */

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once dirname(__DIR__) . '/database.php';
require_once 'paymongo_api_functions.php';

// Log the webhook request
$logFile = 'paymongo_webhook_log.txt';
$timestamp = date('Y-m-d H:i:s');

// Create a comprehensive log entry
$log_entry = "\n[{$timestamp}] ===== NEW WEBHOOK RECEIVED =====\n";
$log_entry .= "[{$timestamp}] Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$log_entry .= "[{$timestamp}] Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
$log_entry .= "[{$timestamp}] Remote Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
$log_entry .= "[{$timestamp}] User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "\n";

// Get the webhook payload
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Log headers and payload
$log_entry .= "[{$timestamp}] Headers: " . json_encode($headers) . "\n";
$log_entry .= "[{$timestamp}] Payload Length: " . strlen($payload) . " bytes\n";
$log_entry .= "[{$timestamp}] Payload: {$payload}\n";

// Write initial log entry
file_put_contents($logFile, $log_entry, FILE_APPEND);

// Verify the webhook signature (optional but recommended)
$webhookSecret = getSetting('paymongo_webhook_secret', $conn) ?? '';

if (!empty($webhookSecret)) {
    // PayMongo sends the signature in the Paymongo-Signature header
    $signature = null;
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'paymongo-signature') {
            $signature = $value;
            break;
        }
    }
    
    if (empty($signature)) {
        file_put_contents($logFile, "[{$timestamp}] WARNING: Missing Paymongo-Signature header\n", FILE_APPEND);
    } else {
        // Verify signature (implementation depends on PayMongo's signature verification method)
        $computedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        
        file_put_contents($logFile, "[{$timestamp}] Computed signature: {$computedSignature}\n", FILE_APPEND);
        file_put_contents($logFile, "[{$timestamp}] Received signature: {$signature}\n", FILE_APPEND);
        
        if ($signature !== $computedSignature) {
            file_put_contents($logFile, "[{$timestamp}] WARNING: Invalid signature - proceeding anyway for testing\n", FILE_APPEND);
        } else {
            file_put_contents($logFile, "[{$timestamp}] Signature verification successful\n", FILE_APPEND);
        }
    }
} else {
    file_put_contents($logFile, "[{$timestamp}] INFO: No webhook secret configured - skipping signature verification\n", FILE_APPEND);
}

// Process the webhook
try {
    // Parse the payload
    $data = json_decode($payload, true);
    
    if (!$data) {
        http_response_code(400);
        $error_msg = "[{$timestamp}] ERROR: Invalid payload format\n";
        file_put_contents($logFile, $error_msg, FILE_APPEND);
        echo json_encode(['error' => 'Invalid payload format', 'timestamp' => $timestamp]);
        exit;
    }
    
    // Extract relevant information - PayMongo sends nested structure
    $event_type = $data['data']['attributes']['type'] ?? '';
    $event_data = $data['data'] ?? [];
    
    file_put_contents($logFile, "[{$timestamp}] Event type: {$event_type}\n", FILE_APPEND);
    file_put_contents($logFile, "[{$timestamp}] Event data structure: " . json_encode(array_keys($event_data)) . "\n", FILE_APPEND);
    
    // Enhanced event handling - support more PayMongo event types
    $supported_events = [
        'payment.paid', 'payment.succeeded', 'source.chargeable', 'payment.awaiting_action',
        'link.payment.paid', 'checkout_session.payment.paid', 'payment.failed'
    ];
    
    if (in_array($event_type, $supported_events)) {
        // CRITICAL FIX: Extract reference ID from the correct nested structure
        // For link.payment.paid events, the reference_id is in the payment data, not the link data
        $reference_id = '';
        $transaction_id = '';
        $payment_amount = 0;
        $payment_currency = 'PHP';
        $payment_description = '';
        $paymongo_reference_number = '';
        
        // Check if this is a link payment event (link.payment.paid)
        if ($event_type == 'link.payment.paid' && isset($event_data['attributes']['payments'][0]['data']['attributes'])) {
            $payment_attrs = $event_data['attributes']['payments'][0]['data']['attributes'];
            $reference_id = $payment_attrs['external_reference_number'] ?? '';
            $transaction_id = $event_data['attributes']['payments'][0]['data']['id'] ?? '';
            $payment_amount = $payment_attrs['amount'] ?? 0;
            $payment_currency = $payment_attrs['currency'] ?? 'PHP';
            $payment_description = $payment_attrs['description'] ?? '';
            
            // CRITICAL FIX: Get PayMongo's reference number from the link data
            $paymongo_reference_number = $event_data['attributes']['reference_number'] ?? '';
        } else {
            // For other event types, use the original structure
            $reference_id = $event_data['attributes']['data']['attributes']['external_reference_number'] ?? '';
            $transaction_id = $event_data['attributes']['data']['id'] ?? '';
            $payment_amount = $event_data['attributes']['data']['attributes']['amount'] ?? 0;
            $payment_currency = $event_data['attributes']['data']['attributes']['currency'] ?? 'PHP';
            $payment_description = $event_data['attributes']['data']['attributes']['description'] ?? '';
            $paymongo_reference_number = $reference_id; // For non-link events
        }
        
        // CRITICAL FIX: Extract PayMongo link ID from the correct structure
        $paymongo_link_id = '';
        
        if ($event_type == 'link.payment.paid') {
            // For link payments, the link ID is in the main event data
            $paymongo_link_id = $event_data['id'] ?? '';
        } else {
            // For other event types, check source data
            $source_data = $event_data['attributes']['data']['attributes']['source'] ?? [];
            if (!empty($source_data['id'])) {
                $paymongo_link_id = $source_data['id'];
            }
        }
        
        // CRITICAL FIX: The reference_number should be our internal RentMate reference, not PayMongo's reference
        // We need to find the actual internal reference from our database
        $payment_reference = $reference_id; // Will be updated below when we find the payment
        
        // CRITICAL FIX: Extract rental ID from description if reference doesn't contain it
        $rental_id_from_description = null;
        if (preg_match('/Rental #(\d+)/', $payment_description, $matches)) {
            $rental_id_from_description = $matches[1];
            file_put_contents($logFile, "[{$timestamp}] Extracted rental ID from description: {$rental_id_from_description}\n", FILE_APPEND);
        }
        
        // Enhanced logging for debugging
        file_put_contents($logFile, "[{$timestamp}] Processing event: {$event_type}\n", FILE_APPEND);
        file_put_contents($logFile, "[{$timestamp}] Reference ID (internal): {$reference_id}\n", FILE_APPEND);
        file_put_contents($logFile, "[{$timestamp}] Transaction ID (PayMongo): {$transaction_id}\n", FILE_APPEND);
        file_put_contents($logFile, "[{$timestamp}] PayMongo Link ID: {$paymongo_link_id}\n", FILE_APPEND);
        file_put_contents($logFile, "[{$timestamp}] Amount: {$payment_amount} {$payment_currency}\n", FILE_APPEND);
        
        // Log the complete event data for debugging
        file_put_contents($logFile, "[{$timestamp}] Complete event data: " . json_encode($event_data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        
        // CRITICAL FIX: Extract payment source information for verification from correct structure
        $payment_source = [];
        $billing_info = [];
        
        if ($event_type == 'link.payment.paid' && isset($event_data['attributes']['payments'][0]['data']['attributes'])) {
            $payment_attrs = $event_data['attributes']['payments'][0]['data']['attributes'];
            $payment_source = $payment_attrs['source'] ?? [];
            $billing_info = $payment_attrs['billing'] ?? [];
        } else {
            $payment_source = $event_data['attributes']['data']['attributes']['source'] ?? [];
            $billing_info = $event_data['attributes']['data']['attributes']['billing'] ?? [];
        }
        
        $sender_name = $billing_info['name'] ?? '';
        $sender_contact = $billing_info['phone'] ?? $billing_info['email'] ?? '';
        
        // Extract sender information from payment source if available
        if (!empty($payment_source)) {
            // For GCash payments
            if (isset($payment_source['type']) && $payment_source['type'] == 'gcash') {
                $sender_name = $billing_info['name'] ?? '';
                $sender_contact = $billing_info['phone'] ?? '';
                file_put_contents($logFile, "[{$timestamp}] GCash sender: {$sender_name}, Phone: {$sender_contact}\n", FILE_APPEND);
            }
            // For card payments
            else if (isset($payment_source['type']) && $payment_source['type'] == 'card') {
                $sender_name = $billing_info['name'] ?? '';
                $sender_contact = $billing_info['email'] ?? '';
                file_put_contents($logFile, "[{$timestamp}] Card sender: {$sender_name}, Email: {$sender_contact}\n", FILE_APPEND);
            }
            // For other payment types
            else {
                $sender_name = $billing_info['name'] ?? '';
                $sender_contact = $billing_info['email'] ?? $billing_info['phone'] ?? '';
                file_put_contents($logFile, "[{$timestamp}] Other sender: {$sender_name}, Contact: {$sender_contact}\n", FILE_APPEND);
            }
        }
        
        // Log more details for debugging
        file_put_contents($logFile, "[{$timestamp}] Payment details: " . json_encode($event_data['attributes']) . "\n", FILE_APPEND);
        
        // Convert payment amount from cents to actual amount if needed
        if ($payment_amount > 0 && $payment_currency == 'PHP') {
            $payment_amount = $payment_amount / 100; // Convert from cents to PHP
        }
        
        file_put_contents($logFile, "[{$timestamp}] Reference ID (internal): {$reference_id}\n", FILE_APPEND);
        file_put_contents($logFile, "[{$timestamp}] Transaction ID (PayMongo): {$transaction_id}\n", FILE_APPEND);
        file_put_contents($logFile, "[{$timestamp}] PayMongo Link ID: {$paymongo_link_id}\n", FILE_APPEND);
        file_put_contents($logFile, "[{$timestamp}] Amount: {$payment_amount} {$payment_currency}\n", FILE_APPEND);
        file_put_contents($logFile, "[{$timestamp}] Reference Number (internal): {$payment_reference}\n", FILE_APPEND);
        
        // CRITICAL FIX: Always use PayMongo's transaction ID as the primary transaction ID
        $final_transaction_id = $transaction_id;
        
        $payment = null;
        $rental_id = null;
        
        // CRITICAL FIX: Try multiple methods to find the rental ID and payment type
        $payment_type = 'rental'; // Default to rental payment
        $extension_payment = null;
        
        // CRITICAL: When reference_id is a PayMongo reference (not our format), find payment by PayMongo reference
        if (!empty($reference_id)) {
            // First, check if this is our internal reference format
            if (preg_match('/rental_(\d+)/', $reference_id, $matches)) {
                // This is our internal reference - use existing logic
                $rental_id = $matches[1];
                file_put_contents($logFile, "[{$timestamp}] Found internal reference format - Rental ID: {$rental_id} from reference: {$reference_id}\n", FILE_APPEND);
                
                // Try to find payment by client reference ID
                $payment = getPaymentByClientReferenceId($reference_id);
            } else {
                // This is a PayMongo reference number - find payment by PayMongo reference or description
                file_put_contents($logFile, "[{$timestamp}] PayMongo reference detected: {$reference_id} - searching by PayMongo reference number\n", FILE_APPEND);
                
                // CRITICAL FIX: Search for payment by PayMongo reference number in the correct fields
                // The PayMongo reference number should be stored in a field that we can search
                $paymongo_ref_query = "SELECT * FROM payments WHERE 
                                      (reference_number LIKE '%{$reference_id}%' OR 
                                       paymongo_link_id LIKE '%{$reference_id}%' OR
                                       transaction_reference LIKE '%{$reference_id}%') 
                                      AND payment_status = 'pending' 
                                      ORDER BY id DESC LIMIT 1";
                $paymongo_result = $conn->query($paymongo_ref_query);
                
                if ($paymongo_result && $paymongo_result->num_rows > 0) {
                    $payment = $paymongo_result->fetch_assoc();
                    $rental_id = $payment['rental_id'];
                    file_put_contents($logFile, "[{$timestamp}] Found payment by PayMongo reference - Payment ID: {$payment['id']}, Rental ID: {$rental_id}\n", FILE_APPEND);
                } else {
                    // CRITICAL FIX: Also search for extension payments by PayMongo reference
                    $extension_paymongo_query = "SELECT * FROM extension_payments WHERE 
                                               (paymongo_payment_id LIKE '%{$reference_id}%' OR 
                                                paymongo_checkout_url LIKE '%{$reference_id}%') 
                                               AND payment_status = 'pending' 
                                               ORDER BY id DESC LIMIT 1";
                    $extension_paymongo_result = $conn->query($extension_paymongo_query);
                    
                    if ($extension_paymongo_result && $extension_paymongo_result->num_rows > 0) {
                        $extension_payment = $extension_paymongo_result->fetch_assoc();
                        $rental_id = $extension_payment['rental_id'];
                        $payment_type = 'extension';
                        file_put_contents($logFile, "[{$timestamp}] Found extension payment by PayMongo reference - Extension Payment ID: {$extension_payment['id']}, Rental ID: {$rental_id}\n", FILE_APPEND);
                    } else {
                        // Fallback: try to extract rental ID from description
                        if (preg_match('/Rental #(\d+)/', $payment_description, $desc_matches)) {
                            $rental_id = $desc_matches[1];
                            file_put_contents($logFile, "[{$timestamp}] Extracted rental ID from description: {$rental_id}\n", FILE_APPEND);
                        }
                    }
                }
            }
        }
        
        // CRITICAL: Check for extension payment by reference ID format
        if (!empty($reference_id) && preg_match('/extension_(\d+)_rental_(\d+)_user_(\d+)_time_(\d+)/', $reference_id, $extension_matches)) {
            $payment_type = 'extension';
            $extension_id = $extension_matches[1];
            $rental_id = $extension_matches[2];
            $user_id = $extension_matches[3];
            
            file_put_contents($logFile, "[{$timestamp}] Detected extension payment by reference ID - Extension ID: {$extension_id}, Rental ID: {$rental_id}, User ID: {$user_id}\n", FILE_APPEND);
            
            // Find extension payment record
            $extension_payment_query = "SELECT * FROM extension_payments WHERE extension_id = $extension_id AND payment_status = 'pending' ORDER BY id DESC LIMIT 1";
            $extension_result = $conn->query($extension_payment_query);
            if ($extension_result && $extension_result->num_rows > 0) {
                $extension_payment = $extension_result->fetch_assoc();
                file_put_contents($logFile, "[{$timestamp}] Found extension payment ID: {$extension_payment['id']}\n", FILE_APPEND);
            }
        }
        
        // CRITICAL FIX: If we still don't have a rental ID, use the one from description
        if (!$rental_id && $rental_id_from_description) {
            $rental_id = $rental_id_from_description;
            file_put_contents($logFile, "[{$timestamp}] Using rental ID from description: {$rental_id}\n", FILE_APPEND);
        }
        
        // CRITICAL FIX: Find pending PayMongo payments for this rental - Updated to match new payment methods
        if ($rental_id && !$payment) {
            // First try to find by reference_id (our internal reference)
            if (!empty($reference_id) && preg_match('/rental_(\d+)/', $reference_id)) {
                $ref_payments_query = "SELECT * FROM payments WHERE rental_id = $rental_id AND reference_id = '" . $conn->real_escape_string($reference_id) . "' AND payment_status = 'pending' ORDER BY id DESC LIMIT 1";
                $ref_result = $conn->query($ref_payments_query);
                
                if ($ref_result && $ref_result->num_rows > 0) {
                    $payment = $ref_result->fetch_assoc();
                    file_put_contents($logFile, "[{$timestamp}] Found payment by reference_id: {$payment['id']} for rental: {$rental_id}\n", FILE_APPEND);
                }
            }
            
            // If not found by reference_id, try by PayMongo-related payment methods and rental_id
            if (!$payment) {
                $paymongo_methods = ['gcash', 'grabpay', 'maya', 'bpi_online', 'card', 'unionbank_online', 'paymongo'];
                $methods_sql = "'" . implode("','", $paymongo_methods) . "'";
                
                // First try to find by PayMongo reference in link fields
                $paymongo_link_query = "SELECT * FROM payments WHERE rental_id = $rental_id AND payment_status = 'pending' AND 
                                       (paymongo_link_id LIKE '%{$paymongo_reference_number}%' OR 
                                        transaction_reference LIKE '%{$paymongo_reference_number}%' OR
                                        paymongo_link_id LIKE '%{$reference_id}%' OR 
                                        transaction_reference LIKE '%{$reference_id}%')
                                       ORDER BY id DESC LIMIT 1";
                $paymongo_link_result = $conn->query($paymongo_link_query);
                
                if ($paymongo_link_result && $paymongo_link_result->num_rows > 0) {
                    $payment = $paymongo_link_result->fetch_assoc();
                    file_put_contents($logFile, "[{$timestamp}] Found payment by PayMongo link reference - Payment ID: {$payment['id']} for rental: {$rental_id}\n", FILE_APPEND);
                } else {
                    // Fallback to any pending PayMongo payment for this rental
                    $pending_payments_query = "SELECT * FROM payments WHERE rental_id = $rental_id AND payment_method IN ($methods_sql) AND payment_status = 'pending' ORDER BY id DESC LIMIT 1";
                    $pending_result = $conn->query($pending_payments_query);
                    
                    if ($pending_result && $pending_result->num_rows > 0) {
                        $payment = $pending_result->fetch_assoc();
                        file_put_contents($logFile, "[{$timestamp}] Found pending PayMongo payment ID: {$payment['id']} for rental: {$rental_id} (method: {$payment['payment_method']})\n", FILE_APPEND);
                    } else {
                        file_put_contents($logFile, "[{$timestamp}] No pending PayMongo payments found for rental: {$rental_id}\n", FILE_APPEND);
                    }
                }
            }
        }
        
        // CRITICAL FIX: Check for extension payments if this is an extension payment webhook
        if (!$payment && !$extension_payment && $rental_id && strpos($payment_description, 'Extension Payment') !== false) {
            file_put_contents($logFile, "[{$timestamp}] Detected extension payment webhook - searching extension_payments table\n", FILE_APPEND);
            
            // Search for extension payment by PayMongo reference (including completed ones to check if already processed)
            $extension_paymongo_query = "SELECT * FROM extension_payments WHERE rental_id = $rental_id AND 
                                       (paymongo_payment_id LIKE '%{$paymongo_reference_number}%' OR 
                                        paymongo_checkout_url LIKE '%{$paymongo_reference_number}%' OR
                                        paymongo_payment_id LIKE '%{$reference_id}%' OR 
                                        paymongo_checkout_url LIKE '%{$reference_id}%')
                                       ORDER BY id DESC LIMIT 1";
            $extension_paymongo_result = $conn->query($extension_paymongo_query);
            
            if ($extension_paymongo_result && $extension_paymongo_result->num_rows > 0) {
                $extension_payment = $extension_paymongo_result->fetch_assoc();
                
                if ($extension_payment['payment_status'] == 'pending') {
                    $payment_type = 'extension';
                    file_put_contents($logFile, "[{$timestamp}] Found pending extension payment by PayMongo reference - Extension Payment ID: {$extension_payment['id']} for rental: {$rental_id}\n", FILE_APPEND);
                } else {
                    file_put_contents($logFile, "[{$timestamp}] Found extension payment but already processed (status: {$extension_payment['payment_status']}) - Extension Payment ID: {$extension_payment['id']} for rental: {$rental_id}\n", FILE_APPEND);
                    // Mark as already processed to prevent 422 error
                    $payment_type = 'extension';
                    $extension_payment = null; // Don't process again
                }
            } else {
                // Fallback: search for any pending extension payment for this rental
                $extension_fallback_query = "SELECT * FROM extension_payments WHERE rental_id = $rental_id AND payment_status = 'pending' ORDER BY id DESC LIMIT 1";
                $extension_fallback_result = $conn->query($extension_fallback_query);
                
                if ($extension_fallback_result && $extension_fallback_result->num_rows > 0) {
                    $extension_payment = $extension_fallback_result->fetch_assoc();
                    $payment_type = 'extension';
                    file_put_contents($logFile, "[{$timestamp}] Found pending extension payment ID: {$extension_payment['id']} for rental: {$rental_id}\n", FILE_APPEND);
                } else {
                    file_put_contents($logFile, "[{$timestamp}] No pending extension payments found for rental: {$rental_id}\n", FILE_APPEND);
                }
            }
        }
        
        // Handle extension payments first
        if ($payment_type == 'extension' && $extension_payment) {
            // Process extension payment
            file_put_contents($logFile, "[{$timestamp}] Processing extension payment ID: {$extension_payment['id']}\n", FILE_APPEND);
            
            // Get PAYER details from PayMongo billing data (person who actually paid)
            $payer_name = $billing_info['name'] ?? $sender_name ?? '';
            $payer_email = $billing_info['email'] ?? '';
            $payer_contact = $billing_info['phone'] ?? $sender_contact ?? '';
            
            // Extract actual payment method from PayMongo source information
            $actual_payment_method = 'paymongo'; // Default fallback
            if (!empty($payment_source)) {
                $source_type = $payment_source['type'] ?? '';
                $source_id = $payment_source['id'] ?? '';
                
                // Map PayMongo source types to our payment methods
                if (strpos($source_type, 'gcash') !== false || strpos($source_id, 'gcash') !== false) {
                    $actual_payment_method = 'gcash';
                } elseif (strpos($source_type, 'grabpay') !== false || strpos($source_id, 'grabpay') !== false) {
                    $actual_payment_method = 'grabpay';
                } elseif (strpos($source_type, 'maya') !== false || strpos($source_id, 'maya') !== false) {
                    $actual_payment_method = 'maya';
                } elseif (strpos($source_type, 'bpi') !== false || strpos($source_id, 'bpi') !== false) {
                    $actual_payment_method = 'bpi_online';
                } elseif (strpos($source_type, 'card') !== false || strpos($source_id, 'card') !== false) {
                    $actual_payment_method = 'card';
                } elseif (strpos($source_type, 'unionbank') !== false || strpos($source_id, 'unionbank') !== false) {
                    $actual_payment_method = 'unionbank_online';
                }
            }
            
            // Update extension payment status with billing information
            $update_extension_sql = "UPDATE extension_payments SET 
                                    payment_status = 'completed',
                                    amount = " . floatval($payment_amount) . ",
                                    payment_method = '" . $conn->real_escape_string($actual_payment_method) . "',
                                    paymongo_payment_id = '" . $conn->real_escape_string($final_transaction_id) . "',
                                    reference_number = '" . $conn->real_escape_string($extension_payment['paymongo_payment_id']) . "',
                                    transaction_id = '" . $conn->real_escape_string($final_transaction_id) . "',
                                    payer_name = '" . $conn->real_escape_string($payer_name) . "',
                                    payer_email = '" . $conn->real_escape_string($payer_email) . "',
                                    payer_phone = '" . $conn->real_escape_string($payer_contact) . "',
                                    payment_date = NOW(),
                                    updated_at = NOW()
                                    WHERE id = " . $extension_payment['id'];
            
            if ($conn->query($update_extension_sql)) {
                file_put_contents($logFile, "[{$timestamp}] Extension payment updated successfully\n", FILE_APPEND);
                file_put_contents($logFile, "[{$timestamp}] Extension payment data stored:\n", FILE_APPEND);
                file_put_contents($logFile, "[{$timestamp}] - Payment Status: completed\n", FILE_APPEND);
                file_put_contents($logFile, "[{$timestamp}] - Amount: {$payment_amount}\n", FILE_APPEND);
                file_put_contents($logFile, "[{$timestamp}] - Payment Method: {$actual_payment_method}\n", FILE_APPEND);
                file_put_contents($logFile, "[{$timestamp}] - PayMongo Payment ID: {$final_transaction_id}\n", FILE_APPEND);
                file_put_contents($logFile, "[{$timestamp}] - Reference Number: {$extension_payment['paymongo_payment_id']}\n", FILE_APPEND);
                file_put_contents($logFile, "[{$timestamp}] - Transaction ID: {$final_transaction_id}\n", FILE_APPEND);
                file_put_contents($logFile, "[{$timestamp}] - Payer Name: {$payer_name}\n", FILE_APPEND);
                file_put_contents($logFile, "[{$timestamp}] - Payer Email: {$payer_email}\n", FILE_APPEND);
                file_put_contents($logFile, "[{$timestamp}] - Payer Phone: {$payer_contact}\n", FILE_APPEND);
                
                // Update extension status to payment_confirmed
                $update_extension_status = "UPDATE rental_extensions SET 
                                          status = 'payment_confirmed',
                                          payment_confirmed_at = NOW()
                                          WHERE id = " . $extension_payment['extension_id'];
                $conn->query($update_extension_status);
                
                // CRITICAL: Automatically update rental overdue date when extension payment is confirmed
                $rental_id = $extension_payment['rental_id'];
                
                // Get rental activation time and calculate new overdue date
                $rental_info_query = "SELECT activation_time, start_date, end_date FROM rentals WHERE id = $rental_id";
                $rental_info_result = $conn->query($rental_info_query);
                $rental_info = $rental_info_result->fetch_assoc();
                
                if ($rental_info && !empty($rental_info['activation_time'])) {
                    // Get total extension days including this newly paid extension
                    $total_extensions_query = "SELECT SUM(extension_days) as total_extensions 
                                             FROM rental_extensions 
                                             WHERE rental_id = $rental_id AND status = 'payment_confirmed'";
                    $total_ext_result = $conn->query($total_extensions_query);
                    $total_ext_data = $total_ext_result->fetch_assoc();
                    $total_extension_days = (int)($total_ext_data['total_extensions'] ?? 0);
                    
                    // Calculate original duration and new end date
                    $activation_time = new DateTime($rental_info['activation_time']);
                    $start_date = new DateTime($rental_info['start_date']);
                    $original_end_date = new DateTime($rental_info['end_date']);
                    $original_duration = $start_date->diff($original_end_date)->days;
                    
                    // Calculate new end date: start_date + original_duration + total_extension_days
                    $new_end_date = clone $start_date;
                    $new_end_date->add(new DateInterval("P" . ($original_duration + $total_extension_days) . "D"));
                    
                    // Update rental end_date to reflect the new extended period
                    $update_rental_end_date = "UPDATE rentals SET end_date = '" . $new_end_date->format('Y-m-d') . "' WHERE id = $rental_id";
                    $conn->query($update_rental_end_date);
                    
                    file_put_contents($logFile, "[{$timestamp}] Auto-updated rental end_date to: " . $new_end_date->format('Y-m-d') . " (original: {$original_duration} days + extensions: {$total_extension_days} days)\n", FILE_APPEND);
                }
                
                // Get extension and rental details for notifications
                $extension_details_query = "SELECT re.*, r.customer_id, i.name as item_name, u.name as customer_name
                                          FROM rental_extensions re
                                          JOIN rentals r ON re.rental_id = r.id
                                          JOIN items i ON r.item_id = i.id
                                          JOIN customers c ON r.customer_id = c.id
                                          JOIN users u ON c.created_by = u.id
                                          WHERE re.id = " . $extension_payment['extension_id'];
                $extension_details_result = $conn->query($extension_details_query);
                $extension_details = $extension_details_result->fetch_assoc();
                
                if ($extension_details) {
                    // Notify customer about successful extension payment
                    $customer_user_query = "SELECT created_by FROM customers WHERE id = " . $extension_details['customer_id'];
                    $customer_user_result = $conn->query($customer_user_query);
                    $customer_user = $customer_user_result->fetch_assoc();
                    
                    if ($customer_user) {
                        $notification_data = [
                            'user_id' => $customer_user['created_by'],
                            'title' => '✅ Extension Payment Confirmed!',
                            'message' => "Your extension payment of ₱" . number_format($extension_payment['amount'], 2) . " for {$extension_details['item_name']} (Rental #{$extension_details['rental_id']}) has been confirmed. Your rental period has been extended by {$extension_details['extension_days']} days.",
                            'type' => 'success',
                            'related_to' => 'rental',
                            'related_id' => $extension_details['rental_id'],
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        insert('notifications', $notification_data, $conn);
                    }
                    
                    // Notify staff about extension payment confirmation
                    $staff_users = getWhere('users', "role IN ('admin', 'staff') AND is_active = 1", $conn);
                    foreach ($staff_users as $staff) {
                        $notification_data = [
                            'user_id' => $staff['id'],
                            'title' => 'Extension Payment Received',
                            'message' => "{$extension_details['customer_name']} has paid ₱" . number_format($extension_payment['amount'], 2) . " for rental extension (Rental #{$extension_details['rental_id']}) - {$extension_details['extension_days']} days extension confirmed.",
                            'type' => 'info',
                            'related_to' => 'rental',
                            'related_id' => $extension_details['rental_id'],
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        insert('notifications', $notification_data, $conn);
                    }
                }
                
                file_put_contents($logFile, "[{$timestamp}] Extension payment webhook processed successfully\n", FILE_APPEND);
                echo json_encode(['status' => 'success', 'payment_type' => 'extension', 'extension_id' => $extension_payment['extension_id']]);
                exit;
            }
        }
        
        if ($payment) {
            // CRITICAL FIX: Update payment status with the actual PayMongo transaction ID
            file_put_contents($logFile, "[{$timestamp}] Updating payment ID {$payment['id']} with transaction_id: {$final_transaction_id}\n", FILE_APPEND);
            
            // Update the payment directly to ensure transaction_id is saved
            // CRITICAL: Don't overwrite reference_number if it already exists (it contains our internal reference)
            // CRITICAL: Don't auto-verify payments - keep status as 'pending' until manual verification
            $update_payment_sql = "UPDATE payments SET 
                                   transaction_id = '" . $conn->real_escape_string($final_transaction_id) . "',
                                   paymongo_link_id = '" . $conn->real_escape_string($paymongo_link_id) . "',
                                   notes = CONCAT(IFNULL(notes, ''), ' | PayMongo webhook received - Event: {$event_type} - Manual verification required')
                                   WHERE id = {$payment['id']}";
            
            if ($conn->query($update_payment_sql)) {
                file_put_contents($logFile, "[{$timestamp}] SUCCESS: Updated payment with transaction_id: {$final_transaction_id}\n", FILE_APPEND);
                
                // CRITICAL FIX: Update reference_number with the actual internal reference from the payment record
                if (empty($payment['reference_number']) && !empty($payment['reference_id'])) {
                    $update_ref_sql = "UPDATE payments SET reference_number = '" . $conn->real_escape_string($payment['reference_id']) . "' WHERE id = {$payment['id']}";
                    $conn->query($update_ref_sql);
                    file_put_contents($logFile, "[{$timestamp}] Updated reference_number with internal reference from payment record: {$payment['reference_id']}\n", FILE_APPEND);
                } else if (!empty($payment['reference_id'])) {
                    // Always ensure reference_number matches reference_id for consistency
                    $update_ref_sql = "UPDATE payments SET reference_number = '" . $conn->real_escape_string($payment['reference_id']) . "' WHERE id = {$payment['id']}";
                    $conn->query($update_ref_sql);
                    file_put_contents($logFile, "[{$timestamp}] Ensured reference_number matches reference_id: {$payment['reference_id']}\n", FILE_APPEND);
                }
            } else {
                file_put_contents($logFile, "[{$timestamp}] ERROR updating payment: " . $conn->error . "\n", FILE_APPEND);
            }

            // Save payment form details from PayMongo - Use the billing_info and payment_source we already extracted
            $source_info = $payment_source;
            
            // Get PAYER details from PayMongo billing data (person who actually paid)
            $payer_name = $billing_info['name'] ?? $sender_name ?? '';
            $payer_email = $billing_info['email'] ?? '';
            $payer_contact = $billing_info['phone'] ?? $sender_contact ?? '';
            
            // Extract actual payment method from PayMongo source information
            $actual_payment_method = 'paymongo'; // Default fallback
            if (!empty($source_info)) {
                $source_type = $source_info['type'] ?? '';
                $source_id = $source_info['id'] ?? '';
                
                // Map PayMongo source types to our payment methods
                if (strpos($source_type, 'gcash') !== false || strpos($source_id, 'gcash') !== false) {
                    $actual_payment_method = 'gcash';
                } elseif (strpos($source_type, 'grabpay') !== false || strpos($source_id, 'grabpay') !== false) {
                    $actual_payment_method = 'grabpay';
                } elseif (strpos($source_type, 'maya') !== false || strpos($source_id, 'maya') !== false) {
                    $actual_payment_method = 'maya';
                } elseif (strpos($source_type, 'bpi') !== false || strpos($source_id, 'bpi') !== false) {
                    $actual_payment_method = 'bpi_online';
                } elseif (strpos($source_type, 'card') !== false || strpos($source_id, 'card') !== false) {
                    $actual_payment_method = 'card';
                } elseif (strpos($source_type, 'unionbank') !== false || strpos($source_id, 'unionbank') !== false) {
                    $actual_payment_method = 'unionbank_online';
                }
            }
            
            // Get CUSTOMER details from rental data (user who made the rental)
            $rental = getById('rentals', $rental_id, $conn);
            $customer_id = $rental['customer_id'] ?? 0;
            $customer = getById('customers', $customer_id, $conn);
            $customer_name = $customer['name'] ?? '';

            // Prepare payment form data - Store both CUSTOMER and PAYER information
            $payment_form_data = [
                'payment_id' => $payment['id'],
                'customer_name' => $customer_name,  // RENTAL CUSTOMER (user who made the rental)
                'name' => $payer_name,              // PAYER NAME (person who paid via PayMongo)
                'email' => $payer_email,            // PAYER EMAIL (person who paid via PayMongo)
                'contact_number' => $payer_contact, // PAYER CONTACT (person who paid via PayMongo)
                'payment_method' => $actual_payment_method, // ACTUAL PAYMENT METHOD (gcash, grabpay, etc.)
                'created_at' => date('Y-m-d H:i:s')
            ];

            // Log the payment form data
            file_put_contents($logFile, "[{$timestamp}] Payment form data to be inserted: " . json_encode($payment_form_data) . "\n", FILE_APPEND);

            // Check if payment form record already exists
            $existing_payment_form_query = "SELECT * FROM payment_forms WHERE payment_id = {$payment['id']} LIMIT 1";
            $existing_payment_form_result = $conn->query($existing_payment_form_query);
            
            if ($existing_payment_form_result && $existing_payment_form_result->num_rows > 0) {
                // Update existing record - Store both CUSTOMER and PAYER information
                $update_payment_form = "UPDATE payment_forms SET 
                                       customer_name = '" . $conn->real_escape_string($customer_name) . "', 
                                       name = '" . $conn->real_escape_string($payer_name) . "', 
                                       email = '" . $conn->real_escape_string($payer_email) . "', 
                                       contact_number = '" . $conn->real_escape_string($payer_contact) . "',
                                       payment_method = '" . $conn->real_escape_string($actual_payment_method) . "'
                                       WHERE payment_id = {$payment['id']}";
                $conn->query($update_payment_form);
                file_put_contents($logFile, "[{$timestamp}] Successfully updated existing payment form data\n", FILE_APPEND);
            } else {
                // Insert new record
                try {
                    insert('payment_forms', $payment_form_data, $conn);
                    file_put_contents($logFile, "[{$timestamp}] Successfully saved payment form data\n", FILE_APPEND);
                } catch (Exception $e) {
                    file_put_contents($logFile, "[{$timestamp}] Error saving payment form data: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
            
            // Get rental information for notifications (DO NOT auto-activate rental)
            $rental_id = $payment['rental_id'];
            $rental = getById('rentals', $rental_id, $conn);
            if ($rental) {
                // Get customer and user information for notifications
                $customer_id = $rental['customer_id'] ?? 0;
                $customer = getById('customers', $customer_id, $conn);
                $customer_name = $customer['name'] ?? 'Unknown Customer';
                $user_id = $customer['created_by'] ?? 0;
                
                // Get item information
                $item_id = $rental['item_id'] ?? 0;
                $item = getById('items', $item_id, $conn);
                $item_name = $item['name'] ?? 'Unknown Item';
                
                // Create notification for staff/owner - Payment needs verification
                $staff_users = getWhere('users', "role IN ('admin', 'staff') AND is_active = 1", $conn);
                foreach ($staff_users as $staff) {
                    $notification_data = [
                        'user_id' => $staff['id'],
                        'title' => 'Payment Received - Verification Required',
                        'message' => "$customer_name has completed payment for $item_name (Rental #$rental_id) via PayMongo - ₱" . number_format($payment_amount, 2) . ". Please verify the payment to activate the rental.",
                        'type' => 'warning',
                        'related_to' => 'rental',
                        'related_id' => $rental_id,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    insert('notifications', $notification_data, $conn);
                }
                
                // Create comprehensive notification for customer
                if ($user_id > 0) {
                    // Get customer name for personalized notification
                    $customer_name = $customer['name'] ?? 'Customer';
                    
                    // Create main payment confirmation notification
                    $payment_title = "💳 Payment Successfully Processed!";
                    $payment_message = "Hi $customer_name! Your payment of ₱" . number_format($payment_amount, 2) . " for $item_name (Rental #$rental_id) has been successfully processed via PayMongo.";
                    
                    // Add payment method and reference info
                    $payment_message .= " Payment Method: PayMongo";
                    if (!empty($payment_reference)) {
                        $payment_message .= " | Reference: $payment_reference";
                    }
                    
                    // Add next steps
                    $payment_message .= " Your payment is now being verified by the owner. You'll receive another notification once your payment is verified and your rental is approved.";
                    
                    $notification_data = [
                        'user_id' => $user_id,
                        'title' => $payment_title,
                        'message' => $payment_message,
                        'type' => 'success',
                        'related_to' => 'rental',
                        'related_id' => $rental_id,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    insert('notifications', $notification_data, $conn);
                    
                    // Create a second notification about verification process
                    $verification_title = "⏳ Payment Verification in Progress";
                    $verification_message = "Your payment has been received and is now being verified by the owner. This usually takes a few minutes to a few hours. You'll be notified once verification is complete and your rental is ready for approval.";
                    
                    $verification_notification_data = [
                        'user_id' => $user_id,
                        'title' => $verification_title,
                        'message' => $verification_message,
                        'type' => 'info',
                        'related_to' => 'rental',
                        'related_id' => $rental_id,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    insert('notifications', $verification_notification_data, $conn);
                }
            }
            
            http_response_code(200);
            file_put_contents($logFile, "[{$timestamp}] SUCCESS: Payment processed successfully with transaction_id: {$final_transaction_id}\n", FILE_APPEND);
            echo json_encode(['status' => 'success', 'transaction_id' => $final_transaction_id, 'timestamp' => $timestamp]);
            exit;
        } else {
            // No exact payment found, fallback to enhanced logic
            // CRITICAL FIX: Use rental ID from description if available
            if (!$rental_id && $rental_id_from_description) {
                $rental_id = $rental_id_from_description;
                file_put_contents($logFile, "[{$timestamp}] Using rental ID from description in fallback: {$rental_id}\n", FILE_APPEND);
            }
            
            // Try the standard format first for internal references
            if (!$rental_id && preg_match('/rental_(\d+)(?:_user_\d+_time_\d+)?/', $reference_id, $matches)) {
                $rental_id = $matches[1];
                file_put_contents($logFile, "[{$timestamp}] Extracted rental ID from internal reference: {$rental_id}\n", FILE_APPEND);
            } 
            // Try a more generic approach if standard format fails
            else if (!$rental_id && preg_match('/rental_(\d+)/', $reference_id, $matches)) {
                $rental_id = $matches[1];
                file_put_contents($logFile, "[{$timestamp}] Extracted rental ID from generic pattern: {$rental_id}\n", FILE_APPEND);
            }
            
            if ($rental_id) {
                file_put_contents($logFile, "[{$timestamp}] Processing fallback with rental ID: {$rental_id}\n", FILE_APPEND);
                
                // CRITICAL FIX: Find the pending payment for this rental using enhanced search
                $paymongo_methods = ['gcash', 'grabpay', 'maya', 'bpi_online', 'card', 'unionbank_online', 'paymongo'];
                $methods_condition = "payment_method IN ('" . implode("','", $paymongo_methods) . "')";
                
                // First try to find by PayMongo reference in link fields
                $paymongo_link_query = "SELECT * FROM payments WHERE rental_id = $rental_id AND payment_status = 'pending' AND 
                                       (paymongo_link_id LIKE '%{$paymongo_reference_number}%' OR 
                                        transaction_reference LIKE '%{$paymongo_reference_number}%' OR
                                        paymongo_link_id LIKE '%{$reference_id}%' OR 
                                        transaction_reference LIKE '%{$reference_id}%')
                                       ORDER BY id DESC LIMIT 1";
                $paymongo_link_result = $conn->query($paymongo_link_query);
                
                if ($paymongo_link_result && $paymongo_link_result->num_rows > 0) {
                    $payments = [$paymongo_link_result->fetch_assoc()];
                    file_put_contents($logFile, "[{$timestamp}] Found payment by PayMongo reference in fallback\n", FILE_APPEND);
                } else {
                    // Fallback to standard PayMongo method search
                    $payments = getWhere('payments', "rental_id = $rental_id AND payment_status = 'pending' AND $methods_condition", $conn);
                    
                    // If not found, look for PayMongo-related payments with broader search
                    if (empty($payments)) {
                        $payments = getWhere('payments', "rental_id = $rental_id AND payment_status = 'pending' AND ($methods_condition OR notes LIKE '%PayMongo API%' OR notes LIKE '%via PayMongo%')", $conn);
                    }
                }
                
                if (!empty($payments)) {
                    $payment = $payments[0];
                    
                    // CRITICAL FIX: Always use PayMongo's transaction ID
                    updatePaymentStatus($payment['id'], $final_transaction_id, 'completed', $payment_reference, $sender_name, $sender_contact, $paymongo_link_id);
                    
                    // Save payment form details from PayMongo - Use the billing_info and payment_source we already extracted
                    $source_info = $payment_source;
                    
                    // Get PAYER details from PayMongo billing data (person who actually paid)
                    $payer_name = $billing_info['name'] ?? $sender_name ?? '';
                    $payer_email = $billing_info['email'] ?? '';
                    $payer_contact = $billing_info['phone'] ?? $sender_contact ?? '';
                    
                    // Get CUSTOMER details from rental data (user who made the rental)
                    $rental = getById('rentals', $rental_id, $conn);
                    $customer_id = $rental['customer_id'] ?? 0;
                    $customer = getById('customers', $customer_id, $conn);
                    $customer_name = $customer['name'] ?? '';

                    // Get the original payment method from the existing payment_forms record
                    $original_method_query = "SELECT payment_method FROM payment_forms WHERE payment_id = {$payment['id']} LIMIT 1";
                    $original_method_result = $conn->query($original_method_query);
                    $original_payment_method = '';

                    if ($original_method_result && $original_method_result->num_rows > 0) {
                        $original_method_data = $original_method_result->fetch_assoc();
                        $original_payment_method = $original_method_data['payment_method'] ?? '';
                    }
                    
                    // Prepare payment form data - Store both CUSTOMER and PAYER information
                    $payment_form_data = [
                        'payment_id' => $payment['id'],
                        'customer_name' => $customer_name,  // RENTAL CUSTOMER (user who made the rental)
                        'name' => $payer_name,              // PAYER NAME (person who paid via PayMongo)
                        'email' => $payer_email,            // PAYER EMAIL (person who paid via PayMongo)
                        'contact_number' => $payer_contact, // PAYER CONTACT (person who paid via PayMongo)
                        'payment_method' => $original_payment_method, // Preserve the original method
                        'created_at' => date('Y-m-d H:i:s')
                    ];

                    // Log the payment form data
                    file_put_contents($logFile, "[{$timestamp}] Payment form data to be inserted: " . json_encode($payment_form_data) . "\n", FILE_APPEND);

                    // Insert or update payment_forms table to prevent duplicates
                    try {
                        if (!empty($original_payment_method)) {
                            // Use INSERT ... ON DUPLICATE KEY UPDATE to prevent duplicates
                            $columns = implode(", ", array_keys($payment_form_data));
                            $values = implode(", ", array_map(function($value) use ($conn) {
                                return "'" . $conn->real_escape_string($value) . "'";
                            }, array_values($payment_form_data)));
                            
                            $update_parts = [];
                            foreach (array_keys($payment_form_data) as $column) {
                                if ($column !== 'payment_id') { // Don't update the primary key
                                    $update_parts[] = "$column = VALUES($column)";
                                }
                            }
                            $update_string = implode(", ", $update_parts);
                            
                            $sql = "INSERT INTO payment_forms ($columns) VALUES ($values) ON DUPLICATE KEY UPDATE $update_string";
                            $conn->query($sql);
                        } else {
                            // Fallback to regular insert if no original method
                            insert('payment_forms', $payment_form_data, $conn);
                        }
                        file_put_contents($logFile, "[{$timestamp}] Successfully saved payment form data\n", FILE_APPEND);
                    } catch (Exception $e) {
                        file_put_contents($logFile, "[{$timestamp}] Error saving payment form data: " . $e->getMessage() . "\n", FILE_APPEND);
                    }
                    
                    // Get rental information for notifications (DO NOT auto-activate rental)
                    $rental = getById('rentals', $rental_id, $conn);
                    if ($rental) {
                        // Get customer and user information for notifications
                        $customer_id = $rental['customer_id'] ?? 0;
                        $customer = getById('customers', $customer_id, $conn);
                        $customer_name = $customer['name'] ?? 'Unknown Customer';
                        $user_id = $customer['created_by'] ?? 0;
                        
                        // Get item information
                        $item_id = $rental['item_id'] ?? 0;
                        $item = getById('items', $item_id, $conn);
                        $item_name = $item['name'] ?? 'Unknown Item';
                        
                        // Create notification for staff/owner - Payment needs verification
                        $staff_users = getWhere('users', "role IN ('admin', 'staff') AND is_active = 1", $conn);
                        foreach ($staff_users as $staff) {
                            $notification_data = [
                                'user_id' => $staff['id'],
                                'title' => 'Payment Received - Verification Required',
                                'message' => "$customer_name has completed payment for $item_name (Rental #$rental_id) via PayMongo - ₱" . number_format($payment_amount, 2) . ". Please verify the payment to activate the rental.",
                                'type' => 'warning',
                                'related_to' => 'rental',
                                'related_id' => $rental_id,
                                'created_at' => date('Y-m-d H:i:s')
                            ];
                            insert('notifications', $notification_data, $conn);
                        }
                        
                        // Create comprehensive notification for customer
                        if ($user_id > 0) {
                            // Get customer name for personalized notification
                            $customer_name = $customer['name'] ?? 'Customer';
                            
                            // Create main payment confirmation notification
                            $payment_title = "💳 Payment Successfully Processed!";
                            $payment_message = "Hi $customer_name! Your payment of ₱" . number_format($payment_amount, 2) . " for $item_name (Rental #$rental_id) has been successfully processed via PayMongo.";
                            
                            // Add payment method and reference info
                            $payment_message .= " Payment Method: PayMongo";
                            if (!empty($payment_reference)) {
                                $payment_message .= " | Reference: $payment_reference";
                            }
                            
                            // Add next steps
                            $payment_message .= " Your payment is now being verified by the owner. You'll receive another notification once your payment is verified and your rental is approved.";
                            
                            $notification_data = [
                                'user_id' => $user_id,
                                'title' => $payment_title,
                                'message' => $payment_message,
                                'type' => 'success',
                                'related_to' => 'rental',
                                'related_id' => $rental_id,
                                'created_at' => date('Y-m-d H:i:s')
                            ];
                            insert('notifications', $notification_data, $conn);
                            
                            // Create a second notification about verification process
                            $verification_title = "⏳ Payment Verification in Progress";
                            $verification_message = "Your payment has been received and is now being verified by the owner. This usually takes a few minutes to a few hours. You'll be notified once verification is complete and your rental is ready for approval.";
                            
                            $verification_notification_data = [
                                'user_id' => $user_id,
                                'title' => $verification_title,
                                'message' => $verification_message,
                                'type' => 'info',
                                'related_to' => 'rental',
                                'related_id' => $rental_id,
                                'created_at' => date('Y-m-d H:i:s')
                            ];
                            insert('notifications', $verification_notification_data, $conn);
                        }
                    }
                    
                    http_response_code(200);
                    file_put_contents($logFile, "[{$timestamp}] SUCCESS: Payment processed successfully with transaction_id: {$final_transaction_id}\n", FILE_APPEND);
                    echo json_encode(['status' => 'success', 'transaction_id' => $final_transaction_id, 'timestamp' => $timestamp]);
                    exit;
                } else {
                    file_put_contents($logFile, "[{$timestamp}] ERROR: No pending payment found for rental ID {$rental_id}\n", FILE_APPEND);
                }
            } else {
                file_put_contents($logFile, "[{$timestamp}] ERROR: Could not extract rental ID from reference {$reference_id}\n", FILE_APPEND);
            }
        }
    } else if ($event_type == 'payment.failed') {
        // Handle failed payments
        file_put_contents($logFile, "[{$timestamp}] Processing failed payment event\n", FILE_APPEND);
        
        // Extract reference ID (should contain rental_id) - PayMongo nested structure
        $reference_id = $event_data['attributes']['data']['attributes']['external_reference_number'] ?? '';
        $transaction_id = $event_data['attributes']['data']['id'] ?? '';
        $failure_code = $event_data['attributes']['data']['attributes']['failure_code'] ?? '';
        $failure_message = $event_data['attributes']['data']['attributes']['failure_message'] ?? 'Payment failed';
        
        file_put_contents($logFile, "[{$timestamp}] Failed payment - Reference ID: {$reference_id}\n", FILE_APPEND);
        file_put_contents($logFile, "[{$timestamp}] Failed payment - Reason: {$failure_code} - {$failure_message}\n", FILE_APPEND);
        
        // Extract rental ID from reference - support multiple formats
        $rental_id = null;
        
        // Try the standard format first
        if (preg_match('/rental_(\d+)(?:_user_\d+_time_\d+)?/', $reference_id, $matches)) {
            $rental_id = $matches[1];
        } 
        // Try a more generic approach if standard format fails
        else if (preg_match('/rental_(\d+)/', $reference_id, $matches)) {
            $rental_id = $matches[1];
        }
        
        if ($rental_id) {
            // Find the pending payment for this rental
            $payments = getWhere('payments', "rental_id = $rental_id AND payment_status = 'pending' AND payment_method = 'paymongo'", $conn);
            
            if (!empty($payments)) {
                $payment = $payments[0];
                
                // Update payment status
                updatePaymentStatus($payment['id'], $transaction_id, 'failed', null, $sender_name, $sender_contact);
                
                // Add note about failure reason
                $notes = $payment['notes'] . " | Failed: " . $failure_message;
                update('payments', ['notes' => $notes], $payment['id'], $conn);
                
                // Notify customer about failed payment
                $rental = getById('rentals', $rental_id, $conn);
                if ($rental) {
                    $customer_id = $rental['customer_id'] ?? 0;
                    $customer = getById('customers', $customer_id, $conn);
                    $customer_name = $customer['name'] ?? 'Unknown Customer';
                    $user_id = $customer['created_by'] ?? 0;
                    
                    // Get item information
                    $item_id = $rental['item_id'] ?? 0;
                    $item = getById('items', $item_id, $conn);
                    $item_name = $item['name'] ?? 'Unknown Item';
                    
                    // Notify staff about failed payment
                    $staff_users = getWhere('users', "role IN ('admin', 'staff') AND is_active = 1", $conn);
                    foreach ($staff_users as $staff) {
                        $notification_data = [
                            'user_id' => $staff['id'],
                            'title' => 'Payment Failed',
                            'message' => "$customer_name's payment for $item_name (Rental #$rental_id) failed: $failure_message",
                            'type' => 'danger',
                            'related_to' => 'rental',
                            'related_id' => $rental_id,
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        insert('notifications', $notification_data, $conn);
                    }
                    
                    // Notify customer about failed payment with helpful information
                    if ($user_id > 0) {
                        // Get customer name for personalized notification
                        $customer_name = $customer['name'] ?? 'Customer';
                        
                        // Create comprehensive failed payment notification
                        $failed_title = "❌ Payment Failed - Action Required";
                        $failed_message = "Hi $customer_name! Unfortunately, your payment for $item_name (Rental #$rental_id) could not be processed.";
                        
                        // Add failure reason
                        $failed_message .= " Reason: $failure_message";
                        
                        // Add helpful next steps
                        $failed_message .= " Please try again or contact support if the problem persists. You can attempt payment again from your rental details page.";
                        
                        $notification_data = [
                            'user_id' => $user_id,
                            'title' => $failed_title,
                            'message' => $failed_message,
                            'type' => 'danger',
                            'related_to' => 'rental',
                            'related_id' => $rental_id,
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        insert('notifications', $notification_data, $conn);
                        
                        // Create a second notification with retry instructions
                        $retry_title = "🔄 How to Retry Payment";
                        $retry_message = "To retry your payment, please visit your rental details page and click the 'Pay with PayMongo' button again. Make sure your payment method has sufficient funds and is properly configured.";
                        
                        $retry_notification_data = [
                            'user_id' => $user_id,
                            'title' => $retry_title,
                            'message' => $retry_message,
                            'type' => 'info',
                            'related_to' => 'rental',
                            'related_id' => $rental_id,
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        insert('notifications', $retry_notification_data, $conn);
                    }
                }
                
                http_response_code(200);
                file_put_contents($logFile, "[{$timestamp}] SUCCESS: Failed payment recorded\n", FILE_APPEND);
                echo json_encode(['status' => 'success', 'timestamp' => $timestamp]);
                exit;
            } else {
                file_put_contents($logFile, "[{$timestamp}] ERROR: No pending payment found for rental ID {$rental_id} to mark as failed\n", FILE_APPEND);
            }
        } else {
            file_put_contents($logFile, "[{$timestamp}] ERROR: Could not extract rental ID from reference {$reference_id}\n", FILE_APPEND);
        }
    } else {
        // Unrecognized event type
        file_put_contents($logFile, "[{$timestamp}] INFO: Unrecognized event type: {$event_type}\n", FILE_APPEND);
        file_put_contents($logFile, "[{$timestamp}] Event data: " . json_encode($event_data) . "\n", FILE_APPEND);
        
        // Return 200 OK to acknowledge receipt even for unrecognized events
        http_response_code(200);
        echo json_encode(['status' => 'acknowledged', 'message' => 'Unrecognized event type', 'timestamp' => $timestamp]);
        exit;
    }
    
    // If we reach here, we couldn't process the webhook properly
    // This could be because the payment was already processed or doesn't exist
    // Return 200 OK to acknowledge receipt and prevent PayMongo from retrying
    http_response_code(200);
    file_put_contents($logFile, "[{$timestamp}] INFO: Webhook received but no pending payment found - likely already processed\n", FILE_APPEND);
    echo json_encode(['status' => 'acknowledged', 'message' => 'No pending payment found - likely already processed', 'timestamp' => $timestamp]);
    
} catch (Exception $e) {
    http_response_code(500);
    $error_msg = "[{$timestamp}] EXCEPTION: " . $e->getMessage() . "\n";
    $error_msg .= "[{$timestamp}] Stack trace: " . $e->getTraceAsString() . "\n";
    file_put_contents($logFile, $error_msg, FILE_APPEND);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage(), 'timestamp' => $timestamp]);
}
?>
