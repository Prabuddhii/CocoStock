<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

// Fetch suppliers from database
$suppliers_query = "SELECT * FROM suppliers";
$suppliers_result = mysqli_query($conn, $suppliers_query);

// Fetch buyers from database
$buyers_query = "SELECT * FROM buyers";
$buyers_result = mysqli_query($conn, $buyers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Stock - CocoStock</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Add Stock Entry</h2>
        <?php if (isset($_GET['success'])) echo "<p style='color:green;'>".$_GET['success']."</p>"; ?>
        <form method="POST" action="process_add.php">
            <!-- Supply/Buyer Selection -->
            <select name="type" id="type" required onchange="toggleFields()">
                <option value="">Select Type</option>
                <option value="supply">Supply</option>
                <option value="buyer">Buyer</option>
            </select>

            <!-- Supplier Selection -->
            <div id="supplier-field" class="hidden">
                <select name="supplier" id="supplier">
                    <option value="">Select Supplier</option>
                    <?php while ($row = mysqli_fetch_assoc($suppliers_result)) { ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                    <?php } ?>
                </select>
                <a href="add_supplier.php" style="display:block; margin: 10px 0; color: #4CAF50;">Add New Supplier</a>
            </div>

            <!-- Buyer Selection -->
            <div id="buyer-field" class="hidden">
                <select name="buyer" id="buyer">
                    <option value="">Select Buyer</option>
                    <?php while ($row = mysqli_fetch_assoc($buyers_result)) { ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                    <?php } ?>
                </select>
                <a href="add_buyer.php" style="display:block; margin: 10px 0; color: #4CAF50;">Add New Buyer</a>
            </div>

            <!-- Item Selection -->
            <select name="item" id="item" required>
                <option value="">Select Item</option>
                <option value="DC">Coconut</option>
                <option value="DC">DC</option>
                <option value="Rejected">Rejected Nut</option>
                <option value="Husks">Husks</option>
            </select>

            <!-- Quantity -->
            <input type="number" name="quantity" id="quantity" placeholder="Quantity" min="1" step="1" required oninput="calculateAmount()">

            <!-- Price -->
            <input type="number" name="price" id="price" placeholder="Price per Unit" min="0" step="0.01" required oninput="calculateAmount()">

            <!-- Amount (Auto-calculated) -->
            <input type="number" name="amount" id="amount" placeholder="Amount" step="0.01" readonly required>

            <!-- Current Date -->
            <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" readonly>

            <button type="submit" class="btn">Add Entry</button>
        </form>
    </div>

    <script>
        // Function to toggle Supplier/Buyer fields
        function toggleFields() {
            const type = document.getElementById('type').value;
            const supplierField = document.getElementById('supplier-field');
            const buyerField = document.getElementById('buyer-field');
            const supplierSelect = document.getElementById('supplier');
            const buyerSelect = document.getElementById('buyer');

            if (type === 'supply') {
                supplierField.classList.remove('hidden');
                buyerField.classList.add('hidden');
                supplierSelect.required = true;
                buyerSelect.required = false;
            } else if (type === 'buyer') {
                supplierField.classList.add('hidden');
                buyerField.classList.remove('hidden');
                supplierSelect.required = false;
                buyerSelect.required = true;
            } else {
                supplierField.classList.add('hidden');
                buyerField.classList.add('hidden');
                supplierSelect.required = false;
                buyerSelect.required = false;
            }
        }

        // Function to calculate Amount (Quantity × Price)
        function calculateAmount() {
            const quantity = parseFloat(document.getElementById('quantity').value) || 0;
            const price = parseFloat(document.getElementById('price').value) || 0;
            const amount = quantity * price;
            document.getElementById('amount').value = amount.toFixed(2); // Round to 2 decimal places
        }
    </script>
</body>
</html>