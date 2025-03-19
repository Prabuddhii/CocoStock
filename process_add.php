<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and retrieve form data
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $supplier_id = ($type == 'supply') ? intval($_POST['supplier']) : NULL;
    $buyer_id = ($type == 'buyer') ? intval($_POST['buyer']) : NULL;
    $item = mysqli_real_escape_string($conn, $_POST['item']);
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $amount = floatval($_POST['amount']); // From client-side calculation
    $date = mysqli_real_escape_string($conn, $_POST['date']);

    // Server-side validation: Recalculate amount to prevent tampering
    $calculated_amount = $quantity * $price;
    if (abs($calculated_amount - $amount) > 0.01) {
        // If the client-side amount doesn't match the server-side calculation, reject the request
        die("Error: Amount mismatch detected.");
    }

    // Insert into the database
    $query = "INSERT INTO stock_entries (type, supplier_id, buyer_id, item, quantity, price, amount, date)
              VALUES ('$type', " . ($supplier_id ? $supplier_id : 'NULL') . ", " . ($buyer_id ? $buyer_id : 'NULL') . ", '$item', $quantity, $price, $amount, '$date')";

    if (mysqli_query($conn, $query)) {
        header("Location: add.php?success=Stock entry added successfully");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>