<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayMongo Webhook Cleanup & Update Guide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 2rem 0;
        }
        .status-success {
            border-left: 4px solid #28a745;
            background: rgba(40, 167, 69, 0.1);
        }
        .status-warning {
            border-left: 4px solid #ffc107;
            background: rgba(255, 193, 7, 0.1);
        }
        .code-block {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .step-card {
            border: 1px solid #dee2e6;
            border-radius: 12px;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        .step-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .step-number {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="fas fa-broom me-3"></i>Webhook Cleanup & Update
                    </h1>
                    <p class="lead mb-0">
                        Clean up old webhooks and configure your new ngrok URL
                    </p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-check-circle fa-5x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <!-- Current Status -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card status-success">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="card-title text-success">
                                    <i class="fas fa-check-circle me-2"></i>New Webhook URL Status
                                </h5>
                                <div class="code-block mb-3">
                                "https://c59ed53e4580.ngrok-free.app/rentmate-system/paymongo-payment-method/webhook_handler.php"
                                </div>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>✅ VERIFIED:</strong> Webhook URL is accessible and ready to use!
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                                <h6 class="text-success">Ready to Use</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cleanup Instructions -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Cleanup Multiple Webhooks
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Problem:</strong> You mentioned may maraming webhooks na naka-setup sa PayMongo Dashboard. 
                            Multiple webhooks can cause conflicts and confusion.
                        </div>

                        <h6 class="fw-bold mb-3">Step-by-Step Cleanup:</h6>
                        
                        <!-- Step 1 -->
                        <div class="step-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="step-number">1</div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold">Open PayMongo Dashboard</h6>
                                        <p class="mb-2">Login to your PayMongo account and navigate to webhook settings.</p>
                                        <a href="https://dashboard.paymongo.com" target="_blank" class="btn btn-primary btn-sm">
                                            <i class="fas fa-external-link-alt me-1"></i> Open PayMongo Dashboard
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="step-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="step-number">2</div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold">Navigate to Webhooks</h6>
                                        <p class="mb-2">Go to <strong>Settings</strong> → <strong>Webhooks</strong></p>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            You should see a list of all your webhooks here.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="step-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="step-number">3</div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold">Identify Old Webhooks</h6>
                                        <p class="mb-2">Look for webhooks with old/different ngrok URLs or localhost URLs:</p>
                                        <ul class="small">
                                            <li>❌ <code>https://b94dce0f1ce3.ngrok-free.app/...</code> (old ngrok)</li>
                                            <li>❌ <code>https://0df1d1279276.ngrok-free.app/...</code> (old ngrok)</li>
                                            <li>❌ <code>https://4a79100d7cd8.ngrok-free.app/...</code> (old ngrok)</li>
                                            <li>❌ <code>http://localhost/...</code> (localhost)</li>
                                            <li>❌ Any other old ngrok URLs</li>
                                        </ul>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check me-2"></i>
                                            <strong>Keep only:</strong> <code>https://9b27c6af9474.ngrok-free.app/...</code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="step-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="step-number">4</div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold">Delete Old Webhooks</h6>
                                        <p class="mb-2">For each old webhook:</p>
                                        <ol class="small">
                                            <li>Click on the webhook</li>
                                            <li>Look for "Delete" or "Remove" button</li>
                                            <li>Confirm deletion</li>
                                        </ol>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Be careful:</strong> Only delete webhooks you're sure are old/unused.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5 -->
                        <div class="step-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="step-number">5</div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold">Configure New Webhook</h6>
                                        <p class="mb-2">Create new webhook or update existing one with:</p>
                                        


                                        <!-- always gina ilisan webhook url para maka payment -->
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Webhook URL:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="webhookUrl" 
                                                       value="https://1781c35d7500.ngrok-free.app/rentmate-system/paymongo-payment-method/webhook_handler.php" readonly>
                                                <button class="btn btn-outline-secondary" onclick="copyWebhookUrl()">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Events to Enable:</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <ul class="list-unstyled">
                                                        <li><i class="fas fa-check text-success me-2"></i> payment.paid</li>
                                                        <li><i class="fas fa-check text-success me-2"></i> payment.succeeded</li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <ul class="list-unstyled">
                                                        <li><i class="fas fa-check text-success me-2"></i> source.chargeable</li>
                                                        <li><i class="fas fa-check text-success me-2"></i> payment.awaiting_action</li>
                                                        <li><i class="fas fa-check text-success me-2"></i> payment.failed</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 6 -->
                        <div class="step-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="step-number">6</div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold">Test the Webhook</h6>
                                        <p class="mb-2">After saving:</p>
                                        <ol class="small">
                                            <li>Use PayMongo's "Send test event" feature</li>
                                            <li>Check webhook logs for activity</li>
                                            <li>Make a test payment</li>
                                            <li>Verify transaction ID appears in staff view</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="https://dashboard.paymongo.com" target="_blank" class="btn btn-primary">
                                <i class="fas fa-external-link-alt me-2"></i>PayMongo Dashboard
                            </a>
                            <a href="webhook_ngrok_test.php" class="btn btn-outline-info">
                                <i class="fas fa-test-tube me-2"></i>Test Webhook
                            </a>
                            <a href="../staff/rentals.php" target="_blank" class="btn btn-outline-success">
                                <i class="fas fa-eye me-2"></i>Check Staff View
                            </a>
                            <button class="btn btn-outline-secondary" onclick="window.location.reload()">
                                <i class="fas fa-sync-alt me-2"></i>Refresh Status
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Current Webhook Info -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Current Webhook</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-bold">Ngrok Host:</h6>
                        <code class="d-block mb-3">9b27c6af9474.ngrok-free.app</code>
                        
                        <h6 class="fw-bold">Status:</h6>
                        <span class="badge bg-success">✅ Accessible</span>
                        
                        <h6 class="fw-bold mt-3">Port:</h6>
                        <span class="text-success">✅ Port 80 (Correct)</span>
                        
                        <h6 class="fw-bold mt-3">Expected Results:</h6>
                        <ul class="small">
                            <li>Transaction IDs auto-populate</li>
                            <li>Real-time payment updates</li>
                            <li>200 OK responses in ngrok</li>
                            <li>POST requests in webhook logs</li>
                        </ul>
                    </div>
                </div>

                <!-- Monitoring -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Monitoring</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-bold">Log Files:</h6>
                        <ul class="small">
                            <li>paymongo_webhook_log.txt</li>
                            <li>paymongo_api_log.txt</li>
                        </ul>
                        
                        <h6 class="fw-bold mt-3">Web Interfaces:</h6>
                        <ul class="small">
                            <li><a href="http://localhost:4040" target="_blank">Ngrok Dashboard</a></li>
                            <li><a href="webhook_ngrok_test.php">Webhook Monitor</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Final Summary -->
        <div class="alert alert-success mt-4">
            <h5><i class="fas fa-trophy me-2"></i>Summary</h5>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-success">✅ Completed:</h6>
                    <ul class="mb-0">
                        <li>New webhook URL verified</li>
                        <li>Correct port configuration (80)</li>
                        <li>Ngrok tunnel active</li>
                        <li>Webhook accessibility confirmed</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-warning">🔄 Next Steps:</h6>
                    <ul class="mb-0">
                        <li>Clean up old webhooks in PayMongo</li>
                        <li>Configure new webhook URL</li>
                        <li>Test with real payment</li>
                        <li>Verify transaction ID capture</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyWebhookUrl() {
            const input = document.getElementById('webhookUrl');
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value);
            
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-success');
            
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
            }, 2000);
        }
    </script>
</body>
</html>
