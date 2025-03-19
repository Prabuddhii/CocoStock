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
    $buyer_name = mysqli_real_escape_string($conn, $_POST['buyer_name']);

    // Insert the new buyer into the database
    $sql = "INSERT INTO buyers (name) VALUES ('$buyer_name')";
    if (mysqli_query($conn, $sql)) {
        // Redirect back to add.php with a success message
        header("Location: add.php?success=Buyer added successfully");
    } else {
        $error = "Error adding buyer: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Buyer - CocoStock</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container">
        <h2>Add New Buyer</h2>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="POST" action="">
            <input type="text" name="buyer_name" placeholder="Buyer Name" required>
            <button type="submit" class="btn">Add Buyer</button>
        </form>
        <a href="add.php" class="btn" style="background-color: #ccc; margin-top: 10px;">Back to Add Stock</a>
    </div>
</body>
</html>