<?php
// Configure session settings for subdirectory access
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');

// Start session
session_start();

// Include database connection
require_once '../database.php';
require_once 'paymongo_api_functions.php';

// Validate session and user access

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get rental ID from URL
$rental_id = isset($_GET['rental_id']) ? intval($_GET['rental_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Validate inputs
if ($rental_id <= 0 || $user_id <= 0) {
    header("Location: ../user/view_rental.php?id=$rental_id&error=invalid_input");
    exit();
}

// Get rental details with item information
$rental_query = "SELECT r.*, i.daily_rate as item_daily_rate 
                 FROM rentals r 
                 JOIN items i ON r.item_id = i.id 
                 WHERE r.id = $rental_id";
$rental_result = $conn->query($rental_query);

if (!$rental_result || $rental_result->num_rows == 0) {
    header("Location: ../user/view_rental.php?id=$rental_id&error=rental_not_found");
    exit();
}

$rental = $rental_result->fetch_assoc();

// Get customer info
$customer_id = $rental['customer_id'] ?? 0;
$customer_info = getById('customers', $customer_id, $conn);
$rental_user_id = $customer_info['created_by'] ?? 0;

// Verify ownership
if ($rental_user_id != $_SESSION['user_id'] || $rental_user_id != $user_id) {
    header("Location: ../user/view_rental.php?id=$rental_id&error=unauthorized");
    exit();
}

// Check if rental is in pending status
if ($rental['status'] !== 'pending') {
    header("Location: ../user/view_rental.php?id=$rental_id&error=invalid_status");
    exit();
}

// Check for existing completed payment
$existing_payment = $conn->query("SELECT * FROM payments WHERE rental_id = $rental_id AND payment_status = 'completed' LIMIT 1");
if ($existing_payment && $existing_payment->num_rows > 0) {
    header("Location: ../user/view_rental.php?id=$rental_id&payment=verified");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>PayMongo Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #ffffff;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .payment-container {
            max-width: 480px;
            width: 100%;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        h2 {
            font-weight: 600;
            color: #222;
            margin-bottom: 20px;
            text-align: center;
        }
        p.lead {
            color: #555;
            margin-bottom: 30px;
            text-align: center;
        }
        .payment-option, .method-option {
            cursor: pointer;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            transition: border-color 0.3s ease;
            user-select: none;
        }
        .payment-option.selected, .method-option.selected {
            border-color: #198754;
            background-color: #e9f7ef;
        }
        .payment-option:hover, .method-option:hover {
            border-color: #198754;
        }
        .payment-type-label, .method-label {
            font-weight: 600;
            cursor: pointer;
        }
        .amount-display {
            font-size: 1.25rem;
            font-weight: 700;
            color: #198754;
            margin-top: 5px;
        }
        .small-text {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .btn-primary {
            width: 100%;
            padding: 12px 0;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(25, 135, 84, 0.4);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #157347;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(21, 115, 71, 0.6);
            outline: none;
        }
        .summary-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            font-weight: 600;
            color: #212529;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <?php if (isset($_GET['rental_id']) && is_numeric($_GET['rental_id'])): ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php
                    $error_message = '';
                    switch ($_GET['error']) {
                        case 'invalid_input':
                            $error_message = 'Invalid input parameters. Please try again.';
                            break;
                        case 'rental_not_found':
                            $error_message = 'Rental not found. Please check your rental ID.';
                            break;
                        case 'unauthorized':
                            $error_message = 'You are not authorized to pay for this rental.';
                            break;
                        case 'payment_record_failed':
                            $error_message = 'Failed to create payment record. Please try again.';
                            break;
                        case 'payment_link_failed':
                            $error_message = 'Failed to create payment link. Please try again.';
                            break;
                        case 'amount_below_minimum':
                            $minimum = isset($_GET['minimum']) ? floatval($_GET['minimum']) : 100;
                            $error_message = "Payment amount must be at least ₱" . number_format($minimum, 2) . ". Please select a different payment option or contact support for assistance.";
                            break;
                        default:
                            $error_message = 'An error occurred. Please try again.';
                    }
                    echo htmlspecialchars($error_message);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <h2>Proceed to Payment</h2>
            <p class="lead">Select your payment type and payment method below.</p>
            <form id="paymentForm" action="payment_processor.php" method="GET" novalidate>
                <input type="hidden" name="rental_id" value="<?php echo (int)$_GET['rental_id']; ?>" />
                <?php
                // Use total rental amount as the base for downpayment calculation
                $item_daily_rate = $rental['item_daily_rate']; // Original item price per day
                $rental_total = $rental['total_amount']; // Total rental amount (price × days)
                $paymongo_minimum = 100.00;
                
                // Downpayment is 25% of the total rental amount
                $downpayment_amount = $rental_total * 0.25;
                $downpayment_below_minimum = $downpayment_amount < $paymongo_minimum;
                
                // If downpayment is below minimum, adjust it to the minimum
                if ($downpayment_below_minimum) {
                    $adjusted_downpayment = $paymongo_minimum;
                } else {
                    $adjusted_downpayment = $downpayment_amount;
                }
                
                // Calculate remaining balance (total rental amount - downpayment)
                $remaining_balance = $rental_total - $adjusted_downpayment;
                ?>
                <input type="hidden" id="rental_total" value="<?php echo $rental_total; ?>" />
                <input type="hidden" id="item_daily_rate" value="<?php echo $item_daily_rate; ?>" />
                <input type="hidden" name="amount" id="amount" value="<?php echo $rental_total; ?>" />

                <!-- Payment Type Selection -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Select Payment Type:</label>
                    <?php if ($downpayment_below_minimum): ?>
                    <div class="alert alert-info mt-2 mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> The downpayment amount has been adjusted to meet the minimum payment requirement of ₱<?php echo number_format($paymongo_minimum, 2); ?>. 
                        Your remaining balance will be ₱<?php echo number_format($remaining_balance, 2); ?>.
                    </div>
                    <?php endif; ?>
                    <div class="d-flex gap-3">
                        <div class="payment-option selected flex-fill text-center" data-type="full_payment" tabindex="0" role="radio" aria-checked="true">
                            <input type="radio" name="payment_type" id="type_full" value="full_payment" checked hidden />
                            <label for="type_full" class="payment-type-label d-block mb-1">Full Payment</label>
                            <div class="amount-display">₱<?php echo number_format($rental_total, 2); ?></div>
                            <div class="small-text">Pay the full amount</div>
                        </div>
                        <div class="payment-option flex-fill text-center" data-type="downpayment" tabindex="0" role="radio" aria-checked="false">
                            <input type="radio" name="payment_type" id="type_down" value="downpayment" hidden />
                            <label for="type_down" class="payment-type-label d-block mb-1">Down Payment</label>
                            <div class="amount-display text-primary">₱<?php echo number_format($adjusted_downpayment, 2); ?></div>
                            <div class="small-text">
                                <?php if ($downpayment_below_minimum): ?>
                                    Minimum payment (₱<?php echo number_format($paymongo_minimum, 2); ?>)
                                <?php else: ?>
                                    25% of total amount (₱<?php echo number_format($rental_total, 2); ?>)
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Selection -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Select Payment Method:</label>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="method-option selected flex-fill text-center" data-method="gcash" tabindex="0" role="radio" aria-checked="true">
                            <input type="radio" name="payment_method" id="method_gcash" value="gcash" checked hidden />
                            <label for="method_gcash" class="method-label d-block">GCash</label>
                        </div>
                        <div class="method-option flex-fill text-center" data-method="grabpay" tabindex="0" role="radio" aria-checked="false">
                            <input type="radio" name="payment_method" id="method_grabpay" value="grabpay" hidden />
                            <label for="method_grabpay" class="method-label d-block">GrabPay</label>
                        </div>
                        <div class="method-option flex-fill text-center" data-method="maya" tabindex="0" role="radio" aria-checked="false">
                            <input type="radio" name="payment_method" id="method_maya" value="maya" hidden />
                            <label for="method_maya" class="method-label d-block">Maya</label>
                        </div>
                        <div class="method-option flex-fill text-center" data-method="bpi_online" tabindex="0" role="radio" aria-checked="false">
                            <input type="radio" name="payment_method" id="method_bpi" value="bpi_online" hidden />
                            <label for="method_bpi" class="method-label d-block">BPI Online</label>
                        </div>
                        <div class="method-option flex-fill text-center" data-method="card" tabindex="0" role="radio" aria-checked="false">
                            <input type="radio" name="payment_method" id="method_card" value="card" hidden />
                            <label for="method_card" class="method-label d-block">Credit/Debit Card</label>
                        </div>
                        <div class="method-option flex-fill text-center" data-method="unionbank_online" tabindex="0" role="radio" aria-checked="false">
                            <input type="radio" name="payment_method" id="method_unionbank" value="unionbank_online" hidden />
                            <label for="method_unionbank" class="method-label d-block">Unionbank Online</label>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="summary-box">
                    <div class="summary-row">
                        <span>Amount to Pay:</span>
                        <span id="display_amount">₱<?php echo number_format($rental_total, 2); ?></span>
                    </div>
                    <div class="summary-row" id="remaining_balance_row" style="display: none;">
                        <span>Remaining Balance:</span>
                        <span id="display_balance">₱<?php echo number_format($remaining_balance, 2); ?></span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" onclick="openPaymentInNewTab(event)">Proceed to Payment</button>
            </form>
        <?php else: ?>
            <h2 class="text-center text-danger">Invalid Access</h2>
            <p class="text-center text-muted">No rental selected. Please go back and try again.</p>
        <?php endif; ?>
    </div>

    <script>
        const amountInput = document.getElementById('amount');
        const displayAmount = document.getElementById('display_amount');
        const displayBalance = document.getElementById('display_balance');
        const remainingBalanceRow = document.getElementById('remaining_balance_row');
        const rentalTotal = parseFloat(document.getElementById('rental_total').value);
        const itemDailyRate = parseFloat(document.getElementById('item_daily_rate').value);
        const paymongoMinimum = 100.00;
        
        // Calculate downpayment as 25% of total rental amount
        const downpaymentAmount = rentalTotal * 0.25;
        const adjustedDownpayment = downpaymentAmount < paymongoMinimum ? paymongoMinimum : downpaymentAmount;

        const paymentOptions = document.querySelectorAll('.payment-option');
        const methodOptions = document.querySelectorAll('.method-option');

        paymentOptions.forEach(option => {
            option.addEventListener('click', function() {
                paymentOptions.forEach(opt => {
                    opt.classList.remove('selected');
                    opt.setAttribute('aria-checked', 'false');
                });
                this.classList.add('selected');
                this.setAttribute('aria-checked', 'true');
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;

                const paymentType = this.getAttribute('data-type');
                if (paymentType === 'downpayment') {
                    amountInput.value = adjustedDownpayment.toFixed(2);
                    displayAmount.textContent = '₱' + adjustedDownpayment.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    const remainingBalance = rentalTotal - adjustedDownpayment;
                    displayBalance.textContent = '₱' + remainingBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    remainingBalanceRow.style.display = 'flex';
                } else {
                    amountInput.value = rentalTotal.toFixed(2);
                    displayAmount.textContent = '₱' + rentalTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    remainingBalanceRow.style.display = 'none';
                }
            });
        });

        methodOptions.forEach(option => {
            option.addEventListener('click', function() {
                methodOptions.forEach(opt => {
                    opt.classList.remove('selected');
                    opt.setAttribute('aria-checked', 'false');
                });
                this.classList.add('selected');
                this.setAttribute('aria-checked', 'true');
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
            });
        });

        // Function to open payment in new tab
        function openPaymentInNewTab(event) {
            event.preventDefault();
            
            if (!amountInput.value || amountInput.value <= 0) {
                alert('Please select a payment type');
                return false;
            }
            
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!selectedMethod) {
                alert('Please select a payment method');
                return false;
            }
            
            // Show loading message
            const submitBtn = event.target;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating Payment...';
            submitBtn.disabled = true;
            
            // Create payment via AJAX using separate endpoint
            const form = document.getElementById('paymentForm');
            const formData = new FormData(form);
            
            // Add rental_id and user_id to form data
            const urlParams = new URLSearchParams(window.location.search);
            const rentalId = urlParams.get('rental_id');
            const userId = urlParams.get('user_id');
            formData.append('rental_id', rentalId);
            formData.append('user_id', userId);
            
            fetch('create_regular_payment.php?rental_id=' + rentalId + '&user_id=' + userId, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    return response.text().then(text => {
                        console.log('Response text:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            console.error('Response was not JSON:', text);
                            throw new Error('Server returned invalid JSON: ' + text.substring(0, 100));
                        }
                    });
                }
                throw new Error('Network response was not ok');
            })
            .then(data => {
                if (data.success && data.checkout_url) {
                    // Open PayMongo URL in new tab
                    window.open(data.checkout_url, '_blank');
                    
                    // Show success message
                    setTimeout(() => {
                        alert('Payment window opened in new tab. You can continue browsing while completing your payment.');
                    }, 500);
                } else if (data.success === false && data.error) {
                    throw new Error(data.error);
                } else {
                    throw new Error('Invalid response from server');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error.message);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }

        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            // This will be handled by the openPaymentInNewTab function
            e.preventDefault();
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
