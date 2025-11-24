<?php
// Extension Payment Dashboard for Users
require_once '../database.php';
require_once 'paymongo_api_functions.php';

// Start session
session_start();

// Check if user is logged in and is a user (renter)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

// Check if extension ID is provided
if (!isset($_GET['extension_id'])) {
    header("Location: ../user/rentals.php");
    exit();
}

$extension_id = (int)$_GET['extension_id'];
$user_id = $_SESSION['user_id'];
$user = getById('users', $user_id, $conn);

// Get customer record
$customer_query = "SELECT * FROM customers WHERE created_by = $user_id";
$customer_result = $conn->query($customer_query);
$customer = $customer_result->fetch_assoc();

if (!$customer) {
    header("Location: ../user/rentals.php?error=no_customer_record");
    exit();
}

$customer_id = $customer['id'];

// Get extension details with rental and item information
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
    header("Location: ../user/rentals.php?error=extension_not_found");
    exit();
}

$extension = $extension_result->fetch_assoc();

// Check if extension payment already exists
$existing_payment_query = "SELECT * FROM extension_payments 
                          WHERE extension_id = $extension_id 
                          ORDER BY created_at DESC LIMIT 1";
$existing_payment_result = $conn->query($existing_payment_query);
$existing_payment = $existing_payment_result->fetch_assoc();

// Check if payment is already completed
if ($existing_payment && $existing_payment['payment_status'] == 'completed') {
    header("Location: ../user/view_rental.php?id={$extension['rental_id']}&success=extension_already_paid");
    exit();
}

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


// Handle payment status updates from URL parameters
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'success') {
        $success_message = "Payment submitted successfully! Your extension will be activated automatically once payment is confirmed by PayMongo.";
    } elseif ($status == 'cancelled') {
        $error_message = "Payment was cancelled. You can try again when ready.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Extension Payment - RentMate</title>
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
        .method-option {
            cursor: pointer;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            transition: border-color 0.3s ease;
            user-select: none;
        }
        .method-option.selected {
            border-color: #198754;
            background-color: #e9f7ef;
        }
        .method-option:hover {
            border-color: #198754;
        }
        .method-label {
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
        .alert-success {
            background-color: #d1e7dd;
            border-color: #badbcc;
            color: #0f5132;
        }
        .extension-details {
            background: linear-gradient(135deg, #e3f2fd 0%, #f8f9ff 100%);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #198754;
        }
        .item-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #6c757d;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-link:hover {
            color: #198754;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <!-- Back Link -->
        <a href="../user/view_rental.php?id=<?php echo $extension['rental_id']; ?>" class="back-link">
            ← Back to Rental
        </a>
        
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <h2>Extension Payment</h2>
        <p class="lead">Pay for your rental extension to activate the new dates.</p>
        
        <!-- Extension Details -->
        <div class="extension-details">
            <div class="item-info">
                <?php if (!empty($extension['item_image'])): ?>
                <img src="../uploads/items/<?php echo htmlspecialchars($extension['item_image']); ?>" 
                     alt="<?php echo htmlspecialchars($extension['item_name']); ?>" 
                     class="item-image">
                <?php else: ?>
                <div class="bg-secondary d-flex align-items-center justify-content-center item-image">
                    <i class="fas fa-box fa-lg text-white"></i>
                </div>
                <?php endif; ?>
                
                <div>
                    <h6 class="mb-1"><?php echo htmlspecialchars($extension['item_name']); ?></h6>
                    <p class="text-muted mb-0 small">Rental #<?php echo $extension['rental_id']; ?> • Owner: <?php echo htmlspecialchars($extension['owner_name']); ?></p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-6">
                    <small class="text-muted">Extension Period:</small>
                    <div class="fw-bold"><?php echo $extension['extension_days']; ?> days</div>
                </div>
                <div class="col-6">
                    <small class="text-muted">Extension Fee:</small>
                    <div class="fw-bold text-success">₱<?php echo number_format($extension['estimated_cost'], 2); ?></div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-6">
                    <small class="text-muted">Current End Date:</small>
                    <div class="fw-bold"><?php echo date('M j, Y', strtotime($extension['current_end_date'])); ?></div>
                </div>
                <div class="col-6">
                    <small class="text-muted">New End Date:</small>
                    <div class="fw-bold text-primary"><?php echo date('M j, Y', strtotime($extension['proposed_end_date'])); ?></div>
                </div>
            </div>
        </div>
        
        <form id="paymentForm" method="POST" action="" novalidate>
            <input type="hidden" name="payment_method" value="paymongo">
            
            <!-- Payment Method Selection -->
            <div class="mb-4">
                <label class="form-label fw-bold">Select Payment Method:</label>
                <div class="d-flex flex-wrap gap-3">
                    <div class="method-option selected flex-fill text-center" data-method="gcash" tabindex="0" role="radio" aria-checked="true">
                        <input type="radio" name="payment_method_type" id="method_gcash" value="gcash" checked hidden />
                        <label for="method_gcash" class="method-label d-block">GCash</label>
                    </div>
                    <div class="method-option flex-fill text-center" data-method="grabpay" tabindex="0" role="radio" aria-checked="false">
                        <input type="radio" name="payment_method_type" id="method_grabpay" value="grabpay" hidden />
                        <label for="method_grabpay" class="method-label d-block">GrabPay</label>
                    </div>
                    <div class="method-option flex-fill text-center" data-method="maya" tabindex="0" role="radio" aria-checked="false">
                        <input type="radio" name="payment_method_type" id="method_maya" value="maya" hidden />
                        <label for="method_maya" class="method-label d-block">Maya</label>
                    </div>
                    <div class="method-option flex-fill text-center" data-method="bpi_online" tabindex="0" role="radio" aria-checked="false">
                        <input type="radio" name="payment_method_type" id="method_bpi" value="bpi_online" hidden />
                        <label for="method_bpi" class="method-label d-block">BPI Online</label>
                    </div>
                    <div class="method-option flex-fill text-center" data-method="card" tabindex="0" role="radio" aria-checked="false">
                        <input type="radio" name="payment_method_type" id="method_card" value="card" hidden />
                        <label for="method_card" class="method-label d-block">Credit/Debit Card</label>
                    </div>
                    <div class="method-option flex-fill text-center" data-method="unionbank_online" tabindex="0" role="radio" aria-checked="false">
                        <input type="radio" name="payment_method_type" id="method_unionbank" value="unionbank_online" hidden />
                        <label for="method_unionbank" class="method-label d-block">Unionbank Online</label>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="summary-box">
                <div class="summary-row">
                    <span>Extension Fee:</span>
                    <span class="amount-display">₱<?php echo number_format($extension['estimated_cost'], 2); ?></span>
                </div>
            </div>

            <button type="submit" name="create_payment" class="btn btn-primary" onclick="openPaymentInNewTab(event)">Proceed to Payment</button>
        </form>
        
        <?php if ($existing_payment): ?>
        <div class="alert alert-info mt-4">
            <h6><i class="fas fa-info-circle me-2"></i>Payment Status</h6>
            <p class="mb-2">A payment for this extension is currently being processed.</p>
            <p class="mb-0">Status: <strong><?php echo ucfirst($existing_payment['payment_status']); ?></strong></p>
            <?php if (!empty($existing_payment['paymongo_payment_id'])): ?>
            <p class="mb-0">Payment ID: <code><?php echo $existing_payment['paymongo_payment_id']; ?></code></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        const methodOptions = document.querySelectorAll('.method-option');

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
            
            const selectedMethod = document.querySelector('input[name="payment_method_type"]:checked');
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
            
            // Add extension_id to form data
            const urlParams = new URLSearchParams(window.location.search);
            const extensionId = urlParams.get('extension_id');
            formData.append('extension_id', extensionId);
            
            fetch('create_extension_payment.php?extension_id=' + extensionId, {
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
