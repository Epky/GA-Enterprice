<?php
include '../sql/sql.php';

// Get the year from the query string
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Query to fetch monthly sales for the selected year
$sql = "SELECT MONTH(orders_date) AS month, SUM(total_amount) AS total_sales 
        FROM tbl_orders 
        WHERE payment_status = 'Paid' AND YEAR(orders_date) = ?
        GROUP BY month
        ORDER BY month ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $year);
$stmt->execute();
$result = $stmt->get_result();

$sales_data = array_fill(0, 12, 0); // Initialize array for 12 months

// Populate sales data array with actual values from the query
while ($row = $result->fetch_assoc()) {
    $month_index = $row['month'] - 1;
    $sales_data[$month_index] = $row['total_sales'];
}

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($sales_data);
