<?php
// Start session if not already started
session_start();
include '../sql/sql.php';

// Check if the customer is logged in
if (isset($_SESSION['customerid'])) {
    // Get the customer ID from the session
    $customer_id = $_SESSION['customerid'];

    // Update the online_offline_status column to 0 (offline)
    $qryUpdateStatus = $conn->prepare("UPDATE tbl_customer_account SET online_offline_status = 0 WHERE customerid = ?");
    $qryUpdateStatus->bind_param("i", $customer_id);
    $qryUpdateStatus->execute();

    // Destroy the session
    session_unset();
    session_destroy();
}

// Redirect to login page after logout
header("Location: login.php");
exit();
