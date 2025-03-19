<?php
session_start();
include 'db_connect.php';

// Restrict access to logged-in users
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplier_name = mysqli_real_escape_string($conn, $_POST['supplier_name']);

    // Insert the new supplier into the database
    $sql = "INSERT INTO suppliers (name) VALUES ('$supplier_name')";
    if (mysqli_query($conn, $sql)) {
        // Redirect back to add.php with a success message
        header("Location: add.php?success=Supplier added successfully");
    } else {
        $error = "Error adding supplier: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Supplier - CocoStock</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container">
        <h2>Add New Supplier</h2>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="POST" action="">
            <input type="text" name="supplier_name" placeholder="Supplier Name" required>
            <button type="submit" class="btn">Add Supplier</button>
        </form>
        <a href="add.php" class="btn" style="background-color: #ccc; margin-top: 10px;">Back to Add Stock</a>
    </div>
</body>
</html>