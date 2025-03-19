<?php
session_start();

// Database configuration (using PDO)
$host = 'localhost';
$db = 'cocostock';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    $filter_type = $_GET['filter_type'] ?? 'all';
    $daily_date = $_GET['daily_date'] ?? '';
    $week_start = $_GET['week_start'] ?? '';
    $week_end = $_GET['week_end'] ?? '';
    $month = $_GET['month'] ?? '';
    $year = $_GET['year'] ?? date('Y');
    $section = $_GET['section'] ?? 'supplier';

    $whereClause = '';
    $params = [];

    if ($filter_type === 'daily' && $daily_date) {
        $whereClause = " AND se.date = :daily_date";
        $params[':daily_date'] = $daily_date;
    } elseif ($filter_type === 'weekly' && $week_start && $week_end) {
        $whereClause = " AND se.date BETWEEN :week_start AND :week_end";
        $params[':week_start'] = $week_start;
        $params[':week_end'] = $week_end;
    } elseif ($filter_type === 'monthly' && $month && $year) {
        $whereClause = " AND YEAR(se.date) = :year AND MONTH(se.date) = :month";
        $params[':year'] = $year;
        $params[':month'] = $month;
    } elseif ($filter_type === 'yearly' && $year) {
        $whereClause = " AND YEAR(se.date) = :year";
        $params[':year'] = $year;
    }

    if ($section === 'supplier') {
        // Supplier stock details
        $supplier_stock_query = "SELECT 
            s.name as supplier_name, 
            se.item, 
            se.quantity,
            se.price,
            se.amount,
            se.date
            FROM stock_entries se
            LEFT JOIN suppliers s ON se.supplier_id = s.id
            WHERE se.type = 'supply'$whereClause";
        $stmt = $pdo->prepare($supplier_stock_query);
        $stmt->execute($params);
        $supplier_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Supplier coconut stock
        $supplier_coconut_query = "SELECT 
            s.name as supplier_name,
            SUM(se.quantity) as total_coconut_quantity
            FROM stock_entries se
            LEFT JOIN suppliers s ON se.supplier_id = s.id
            WHERE se.type = 'supply' AND se.item = 'Coconut with Husks'$whereClause
            GROUP BY se.supplier_id, s.name";
        $stmt = $pdo->prepare($supplier_coconut_query);
        $stmt->execute($params);
        $supplier_coconut_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            'supplier_stock' => $supplier_stock,
            'supplier_coconut_stock' => $supplier_coconut_stock
        ];
    } elseif ($section === 'buyer') {
        // Buyer purchase details
        $buyer_purchases_query = "SELECT 
            b.name as buyer_name, 
            se.item, 
            se.quantity,
            se.price,
            se.amount,
            se.date
            FROM stock_entries se
            LEFT JOIN buyers b ON se.buyer_id = b.id
            WHERE se.type = 'buyer'$whereClause";
        $stmt = $pdo->prepare($buyer_purchases_query);
        $stmt->execute($params);
        $buyer_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            'buyer_purchases' => $buyer_purchases
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Initial page load (non-AJAX)
$total_supplier_stock = $pdo->query("SELECT 
    SUM(CASE WHEN item = 'Coconut with Husks' THEN quantity ELSE 0 END) as total_coconut_quantity,
    SUM(CASE WHEN item = 'DC' THEN quantity ELSE 0 END) as total_dc_quantity,
    SUM(CASE WHEN item = 'Husks' THEN quantity ELSE 0 END) as total_husks_quantity
    FROM stock_entries
    WHERE type = 'supply'")->fetch(PDO::FETCH_ASSOC);

$total_buyer_purchases = $pdo->query("SELECT 
    SUM(CASE WHEN item = 'Coconut with Husks' THEN quantity ELSE 0 END) as total_coconut_purchases_quantity,
    SUM(CASE WHEN item = 'DC' THEN quantity ELSE 0 END) as total_dc_purchases_quantity,
    SUM(CASE WHEN item = 'Husks' THEN quantity ELSE 0 END) as total_husks_purchases_quantity
    FROM stock_entries
    WHERE type = 'buyer'")->fetch(PDO::FETCH_ASSOC);

$net_stock = [
    'net_coconut' => max(0, ($total_supplier_stock['total_coconut_quantity'] ?? 0) - ($total_buyer_purchases['total_dc_purchases_quantity'] ?? 0)),
    'net_dc' => max(0, ($total_supplier_stock['total_dc_quantity'] ?? 0) - ($total_buyer_purchases['total_dc_purchases_quantity'] ?? 0)),
    'net_husks' => $total_buyer_purchases['total_dc_purchases_quantity'] ?? 0
];

// Initial data for supplier and buyer sections (default: all time)
$supplier_stock = $pdo->query("SELECT 
    s.name as supplier_name, 
    se.item, 
    se.quantity,
    se.price,
    se.amount,
    se.date
    FROM stock_entries se
    LEFT JOIN suppliers s ON se.supplier_id = s.id
    WHERE se.type = 'supply'")->fetchAll(PDO::FETCH_ASSOC);

$supplier_coconut_stock = $pdo->query("SELECT 
    s.name as supplier_name,
    SUM(se.quantity) as total_coconut_quantity
    FROM stock_entries se
    LEFT JOIN suppliers s ON se.supplier_id = s.id
    WHERE se.type = 'supply' AND se.item = 'Coconut with Husks'
    GROUP BY se.supplier_id, s.name")->fetchAll(PDO::FETCH_ASSOC);

$buyer_purchases = $pdo->query("SELECT 
    b.name as buyer_name, 
    se.item, 
    se.quantity,
    se.price,
    se.amount,
    se.date
    FROM stock_entries se
    LEFT JOIN buyers b ON se.buyer_id = b.id
    WHERE se.type = 'buyer'")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CocoStock - Summary Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f0f2f5;
            color: #333;
            line-height: 1.6;
            margin: 0;
            overflow-x: hidden;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 1rem;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: width 0.3s ease, transform 0.3s ease;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar:hover {
            width: 260px;
        }

        .logo {
            font-size: clamp(1.5rem, 4vw, 1.8rem);
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: center;
            color: #28a745;
            letter-spacing: 1px;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
        }

        .sidebar-nav a {
            display: block;
            padding: 0.8rem;
            color: white;
            text-decoration: none;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            transition: background 0.3s ease, padding-left 0.3s ease;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }

        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: #28a745;
            padding-left: 1.2rem;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 1rem;
            position: relative;
            width: calc(100% - 250px);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            flex-wrap: wrap;
        }

        header h1 {
            font-size: clamp(1.5rem, 3vw, 1.8rem);
            color: #2c3e50;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            gap: 0.5rem;
            font-size: clamp(0.8rem, 1.5vw, 0.9rem);
            color: #666;
        }

        .user-info .username {
            font-weight: 600;
        }

        .user-info .user-role {
            opacity: 0.8;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            overflow-x: auto;
        }

        .tab-button {
            padding: 0.8rem 1.5rem;
            background: #f8f9fa;
            color: #2c3e50;
            border: none;
            border-radius: 5px;
            font-size: clamp(0.9rem, 2vw, 1rem);
            cursor: pointer;
            transition: background 0.3s ease, transform 0.3s ease;
        }

        .tab-button:hover, .tab-button.active {
            background: #28a745;
            color: white;
            transform: translateY(-2px);
        }

        .content-section {
            display: none;
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .content-section.active {
            display: block;
        }

        .content-section h2 {
            margin-bottom: 1rem;
            color: #2c3e50;
            font-size: clamp(1.2rem, 2.5vw, 1.5rem);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            padding: 1rem;
            background: #28a745;
            color: white;
            border-radius: 15px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .stat-card h3 {
            margin-bottom: 0.5rem;
            font-size: clamp(0.9rem, 2vw, 1.1rem);
            font-weight: 400;
        }

        .stat-card p {
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 600;
        }

        .summary-table {
            overflow-x: auto;
            margin-top: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: clamp(0.8rem, 1.5vw, 0.9rem);
        }

        th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            text-transform: uppercase;
        }

        td {
            color: #555;
        }

        tr:hover {
            background: #f8f9fa;
            transition: background 0.2s ease;
        }

        .filter-container {
            margin-bottom: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }

        .filter-container label {
            font-weight: 600;
            color: #2c3e50;
        }

        .filter-container select, .filter-container input[type="date"] {
            padding: 0.5rem;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: clamp(0.9rem, 2vw, 1rem);
            cursor: pointer;
        }

        .filter-container button {
            padding: 0.5rem 1rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .filter-container button:hover {
            background: #218838;
        }

        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            color: #2c3e50;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                transform: translateY(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateY(0);
            }

            .sidebar:hover {
                width: 100%;
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
                width: 100%;
            }

            .menu-toggle {
                display: block;
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 1100;
            }

            header {
                padding: 0.8rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .tabs {
                flex-direction: column;
                gap: 0.5rem;
            }

            .tab-button {
                width: 100%;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            .content-section {
                padding: 0.5rem;
            }

            .stat-card p {
                font-size: clamp(1.2rem, 2.5vw, 1.5rem);
            }

            th, td {
                padding: 0.5rem;
            }

            .filter-container {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <aside class="sidebar">
            <div class="logo">CocoStock</div>
            <nav class="sidebar-nav">
                <a href="#" class="active">Summary Dashboard</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header>
                <h1>Summary Dashboard</h1>
                <div class="user-info">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="user-role">(Admin)</span>
                </div>
            </header>
            <div class="tabs">
                <button class="tab-button active" onclick="showSlide('net')">Net Stock Summary</button>
                <button class="tab-button" onclick="showSlide('supplier')">Supplier Stock Summary</button>
                <button class="tab-button" onclick="showSlide('buyer')">Buyer Purchase Summary</button>
            </div>
            <section id="net" class="content-section active">
                <h2>Total Net Stock Summary</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Net Coconut Stock</h3>
                        <p><?php echo $net_stock['net_coconut']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Net DC Stock</h3>
                        <p><?php echo $net_stock['net_dc']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Net Husks Stock</h3>
                        <p><?php echo $net_stock['net_husks']; ?></p>
                    </div>
                </div>
            </section>
            <section id="supplier" class="content-section">
                <h2>Supplier Stock Summary</h2>
                <div class="filter-container">
                    <label for="filter-type">Filter Type:</label>
                    <select id="filter-type" onchange="toggleFilterInputs()">
                        <option value="all">All Time</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                    <div id="daily-filter" style="display: none;">
                        <input type="date" id="daily-date">
                    </div>
                    <div id="weekly-filter" style="display: none; gap: 1rem;" class="flex">
                        <input type="date" id="week-start" placeholder="Start Date (e.g., Monday)">
                        <input type="date" id="week-end" placeholder="End Date (e.g., Sunday)">
                    </div>
                    <div id="monthly-filter" style="display: none; gap: 1rem;" class="flex">
                        <select id="month">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo date('F', mktime(0, 0, 0, $i, 1)); ?></option>
                            <?php endfor; ?>
                        </select>
                        <select id="month-year">
                            <?php for ($i = date('Y') - 5; $i <= date('Y') + 5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == date('Y') ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div id="yearly-filter" style="display: none;">
                        <select id="year">
                            <?php for ($i = date('Y') - 5; $i <= date('Y') + 5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == date('Y') ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button onclick="applyFilter('supplier')">Apply Filter</button>
                </div>
                <div class="stats-grid">
                    <!-- Add stats here if desired -->
                </div>
                <h2>Total Coconut with Husks Quantity per Supplier</h2>
                <div class="summary-table" id="supplier-coconut-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Total Coconut with Husks Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($supplier_coconut_stock as $stock): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stock['supplier_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($stock['total_coconut_quantity'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <h2>Supplier Stock Details</h2>
                <div class="summary-table" id="supplier-stock-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($supplier_stock as $stock): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stock['date']); ?></td>
                                    <td><?php echo htmlspecialchars($stock['supplier_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($stock['item']); ?></td>
                                    <td><?php echo htmlspecialchars($stock['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($stock['price'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($stock['amount'], 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <section id="buyer" class="content-section">
                <h2>Buyer Purchase Summary</h2>
                <div class="filter-container">
                    <label for="filter-type-buyer">Filter Type:</label>
                    <select id="filter-type-buyer" onchange="toggleFilterInputs('buyer')">
                        <option value="all">All Time</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                    <div id="daily-filter-buyer" style="display: none;">
                        <input type="date" id="daily-date-buyer">
                    </div>
                    <div id="weekly-filter-buyer" style="display: none; gap: 1rem;" class="flex">
                        <input type="date" id="week-start-buyer" placeholder="Start Date (e.g., Monday)">
                        <input type="date" id="week-end-buyer" placeholder="End Date (e.g., Sunday)">
                    </div>
                    <div id="monthly-filter-buyer" style="display: none; gap: 1rem;" class="flex">
                        <select id="month-buyer">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo date('F', mktime(0, 0, 0, $i, 1)); ?></option>
                            <?php endfor; ?>
                        </select>
                        <select id="month-year-buyer">
                            <?php for ($i = date('Y') - 5; $i <= date('Y') + 5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == date('Y') ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div id="yearly-filter-buyer" style="display: none;">
                        <select id="year-buyer">
                            <?php for ($i = date('Y') - 5; $i <= date('Y') + 5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == date('Y') ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button onclick="applyFilter('buyer')">Apply Filter</button>
                </div>
                <div class="stats-grid">
                    <!-- Add stats here if desired -->
                </div>
                <h2>Buyer Purchase Details</h2>
                <div class="summary-table" id="buyer-purchases-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Buyer</th>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($buyer_purchases as $purchase): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($purchase['date']); ?></td>
                                    <td><?php echo htmlspecialchars($purchase['buyer_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($purchase['item']); ?></td>
                                    <td><?php echo htmlspecialchars($purchase['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($purchase['price'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($purchase['amount'], 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }

        function showSlide(slideId) {
            const slides = document.querySelectorAll('.content-section');
            const tabs = document.querySelectorAll('.tab-button');
            
            slides.forEach(slide => slide.classList.remove('active'));
            tabs.forEach(tab => tab.classList.remove('active'));
            
            document.getElementById(slideId).classList.add('active');
            document.querySelector(`.tab-button[onclick="showSlide('${slideId}')"]`).classList.add('active');
        }

        function toggleFilterInputs(section = 'supplier') {
            const prefix = section === 'buyer' ? '-buyer' : '';
            const filterType = document.getElementById(`filter-type${prefix}`).value;
            document.getElementById(`daily-filter${prefix}`).style.display = filterType === 'daily' ? 'block' : 'none';
            document.getElementById(`weekly-filter${prefix}`).style.display = filterType === 'weekly' ? 'flex' : 'none';
            document.getElementById(`monthly-filter${prefix}`).style.display = filterType === 'monthly' ? 'flex' : 'none';
            document.getElementById(`yearly-filter${prefix}`).style.display = filterType === 'yearly' ? 'block' : 'none';
        }

        function applyFilter(section = 'supplier') {
            const prefix = section === 'buyer' ? '-buyer' : '';
            const filterType = document.getElementById(`filter-type${prefix}`).value;
            const params = new URLSearchParams({
                ajax: 'true',
                section: section,
                filter_type: filterType
            });

            if (filterType === 'daily') {
                const dailyDate = document.getElementById(`daily-date${prefix}`).value;
                if (dailyDate) params.append('daily_date', dailyDate);
            } else if (filterType === 'weekly') {
                const weekStart = document.getElementById(`week-start${prefix}`).value;
                const weekEnd = document.getElementById(`week-end${prefix}`).value;
                if (weekStart && weekEnd) {
                    params.append('week_start', weekStart);
                    params.append('week_end', weekEnd);
                }
            } else if (filterType === 'monthly') {
                const month = document.getElementById(`month${prefix}`).value;
                const year = document.getElementById(`month-year${prefix}`).value;
                params.append('month', month);
                params.append('year', year);
            } else if (filterType === 'yearly') {
                const year = document.getElementById(`year${prefix}`).value;
                params.append('year', year);
            }

            fetch(`?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (section === 'supplier') {
                    // Update Supplier Coconut Stock Table
                    const coconutTbody = document.querySelector('#supplier-coconut-table tbody');
                    coconutTbody.innerHTML = '';
                    data.supplier_coconut_stock.forEach(stock => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${stock.supplier_name || 'Unknown'}</td>
                            <td>${stock.total_coconut_quantity || 0}</td>
                        `;
                        coconutTbody.appendChild(row);
                    });

                    // Update Supplier Stock Details Table
                    const stockTbody = document.querySelector('#supplier-stock-table tbody');
                    stockTbody.innerHTML = '';
                    data.supplier_stock.forEach(stock => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${stock.date}</td>
                            <td>${stock.supplier_name || 'Unknown'}</td>
                            <td>${stock.item}</td>
                            <td>${stock.quantity}</td>
                            <td>${Number(stock.price).toFixed(2)}</td>
                            <td>${Number(stock.amount).toFixed(2)}</td>
                        `;
                        stockTbody.appendChild(row);
                    });
                } else if (section === 'buyer') {
                    // Update Buyer Purchases Table
                    const purchasesTbody = document.querySelector('#buyer-purchases-table tbody');
                    purchasesTbody.innerHTML = '';
                    data.buyer_purchases.forEach(purchase => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${purchase.date}</td>
                            <td>${purchase.buyer_name || 'Unknown'}</td>
                            <td>${purchase.item}</td>
                            <td>${purchase.quantity}</td>
                            <td>${Number(purchase.price).toFixed(2)}</td>
                            <td>${Number(purchase.amount).toFixed(2)}</td>
                        `;
                        purchasesTbody.appendChild(row);
                    });
                }
            })
            .catch(error => console.error('Error fetching data:', error));
        }

        document.addEventListener('DOMContentLoaded', () => {
            showSlide('net');
            toggleFilterInputs('supplier');
            toggleFilterInputs('buyer');
        });
    </script>
</body>
</html>