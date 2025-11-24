# PayMongo Payment Method - File Guide

## 📁 **CLEANED UP FOLDER STRUCTURE**

After cleanup, your `paymongo-payment-method` folder now contains **10 essential files** (down from 22+ files).

---

## 🔧 **CORE PRODUCTION FILES**

### 1. `webhook_handler.php` 
**🚨 CRITICAL - Main Webhook Processor**
- **Purpose:** Main webhook handler that processes PayMongo webhooks and updates database
- **What it does:** 
  - Receives webhook notifications from PayMongo
  - Verifies webhook signatures for security
  - Extracts transaction IDs and payment details
  - Updates payment and rental statuses in database
  - Sends notifications to customers
- **When it's used:** Automatically called by PayMongo when payments occur
- **URL:** `https://your-domain.com/rentmate-system/paymongo-payment-method/webhook_handler.php`

### 2. `paymongo_api_functions.php`
**🚨 CRITICAL - Core API Functions**
- **Purpose:** Contains all PayMongo API functions and utilities
- **What it does:**
  - Payment creation and processing functions
  - API communication with PayMongo servers
  - Payment verification and status checking
  - Utility functions for payment handling
- **When it's used:** Included by other files that need PayMongo functionality
- **Important:** Required by almost all other PayMongo files

### 3. `payment_processor.php`
**🚨 CRITICAL - Payment Creation**
- **Purpose:** Creates PayMongo payments and handles payment processing
- **What it does:**
  - Generates PayMongo payment links
  - Processes different payment methods (GCash, GrabPay, Cards, etc.)
  - Handles payment form submissions
  - Redirects users to PayMongo payment pages
- **When it's used:** When customers click "Pay Now" buttons
- **URL:** Called from rental forms and payment pages

### 4. `payment_dashboard.php`
**⚠️ IMPORTANT - Management Interface**
- **Purpose:** Payment management dashboard interface
- **What it does:**
  - Displays payment statistics and reports
  - Shows recent transactions
  - Provides payment management tools
  - Admin interface for payment oversight
- **When it's used:** Accessed by staff/admin for payment monitoring
- **URL:** `http://localhost/rentmate-system/paymongo-payment-method/payment_dashboard.php`

### 5. `payment_callback.php`
**⚠️ IMPORTANT - Payment Flow Handler**
- **Purpose:** Handles payment callbacks and redirects
- **What it does:**
  - Processes return URLs from PayMongo
  - Handles success/failure redirects
  - Updates payment status on callback
  - Provides user feedback after payment
- **When it's used:** When PayMongo redirects users back after payment
- **URL:** Used as redirect URL in PayMongo payment creation

---

## 🛠️ **UTILITY & MONITORING FILES**

### 6. `payment_status_checker.php`
**✅ USEFUL - Status Monitoring Tool**
- **Purpose:** Checks and updates payment statuses from PayMongo API
- **What it does:**
  - Manually checks payment statuses with PayMongo
  - Updates database with latest payment information
  - Syncs transaction IDs and payment details
  - Useful for troubleshooting payment issues
- **When it's used:** Run manually or via cron job for status updates
- **URL:** `http://localhost/rentmate-system/paymongo-payment-method/payment_status_checker.php`

### 7. `setup_guide.php`
**✅ USEFUL - Documentation & Setup**
- **Purpose:** Comprehensive setup and cleanup guide for webhooks
- **What it does:**
  - Provides step-by-step webhook setup instructions
  - Shows how to configure PayMongo dashboard
  - Includes troubleshooting guides
  - Contains cleanup instructions for old webhooks
- **When it's used:** When setting up or troubleshooting webhooks
- **URL:** `http://localhost/rentmate-system/paymongo-payment-method/setup_guide.php`

### 8. `README.md`
**✅ USEFUL - Documentation**
- **Purpose:** Basic documentation file
- **What it does:**
  - Contains basic setup and usage information
  - Provides quick reference for developers
  - Documents file structure and purposes
- **When it's used:** For reference and documentation

---

## 📊 **LOG FILES**

### 9. `paymongo_webhook_log.txt`
**⚠️ IMPORTANT - Webhook Activity Logs**
- **Purpose:** Records all webhook activity and debugging information
- **What it contains:**
  - All incoming webhook requests
  - Webhook processing results
  - Error messages and debugging info
  - Transaction details and status updates
- **When it's updated:** Automatically by `webhook_handler.php`
- **Size:** Currently ~64KB (grows over time)

### 10. `paymongo_api_log.txt`
**⚠️ IMPORTANT - API Call Logs**
- **Purpose:** Records all PayMongo API calls and responses
- **What it contains:**
  - API request/response logs
  - Payment creation attempts
  - Status check results
  - API error messages
- **When it's updated:** Automatically by API functions
- **Size:** Currently ~20KB (grows over time)

---

## 🗑️ **DELETED FILES (12 files removed)**

The following temporary and test files have been **permanently deleted**:
- ❌ `test_current_webhook.php` - Temporary test file
- ❌ `update_current_webhook.php` - Temporary update script
- ❌ `webhook_port_fix_guide.php` - Temporary troubleshooting guide
- ❌ `fix_ngrok_port.bat` - Temporary batch script
- ❌ `fix_502_error.php` - Temporary diagnostic script
- ❌ `update_webhook_url.php` - Temporary URL update script
- ❌ `test_new_webhook.php` - Temporary webhook test
- ❌ `start_ngrok.sh` - Temporary Linux script
- ❌ `start_ngrok.bat` - Temporary Windows script
- ❌ `ngrok_setup_guide.php` - Redundant guide
- ❌ `webhook_ngrok_test.php` - Test interface
- ❌ `webhook_setup_guide.php` - Redundant guide

---

## 🔄 **FILE DEPENDENCIES**

### Critical Dependencies:
1. **`webhook_handler.php`** requires `paymongo_api_functions.php`
2. **`payment_processor.php`** requires `paymongo_api_functions.php`
3. **`payment_dashboard.php`** requires `paymongo_api_functions.php`
4. **`payment_status_checker.php`** requires `paymongo_api_functions.php`

### Important Notes:
- **Never delete** `paymongo_api_functions.php` - it's required by all core files
- **Never delete** `webhook_handler.php` - it's your main webhook endpoint
- **Log files** grow over time - consider rotating them periodically
- **Setup guide** contains current webhook URL and configuration

---

## 🚀 **QUICK ACCESS URLS**

When your XAMPP is running:
- **Payment Dashboard:** `http://localhost/rentmate-system/paymongo-payment-method/payment_dashboard.php`
- **Setup Guide:** `http://localhost/rentmate-system/paymongo-payment-method/setup_guide.php`
- **Status Checker:** `http://localhost/rentmate-system/paymongo-payment-method/payment_status_checker.php`
- **Webhook Endpoint:** `https://your-ngrok-url.ngrok-free.app/rentmate-system/paymongo-payment-method/webhook_handler.php`

---

## 📈 **CLEANUP RESULTS**

- **Files before cleanup:** 22+ files
- **Files after cleanup:** 10 files
- **Space saved:** Removed 12 temporary/test files
- **Organization improvement:** All files now have descriptive names
- **Maintenance:** Much easier to understand and maintain

---

## ⚠️ **IMPORTANT REMINDERS**

1. **Current Webhook URL:** Update PayMongo dashboard with current ngrok URL
2. **File Names:** All files now have descriptive names - update any references
3. **Logs:** Monitor log files for debugging and troubleshooting
4. **Backup:** Consider backing up your cleaned folder structure

---

*File guide created: September 17, 2025*
*Folder cleaned and organized successfully* ✨
