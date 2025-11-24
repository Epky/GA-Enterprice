<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_items'])) {
    $selected_items = json_decode($_POST['selected_items'], true);

    if (is_array($selected_items) && count($selected_items) > 0) {
        // Save the selected items to the session
        $_SESSION['selected_items'] = $selected_items;

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid items selected']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
