<?php
session_start();


/* ================= ADMIN AUTH ================= */
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

/* ================= DATABASE CONFIG ================= */
$host = "localhost";
$dbname = "homethali";
$user = "root";
$pass = "";

/* ================= CONNECT ================= */
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed");
}

/* ================= DASHBOARD DATA ================= */
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

$totalRevenue = $pdo->query(
    "SELECT IFNULL(SUM(total_amount),0) FROM orders"
)->fetchColumn();

$todayOrders = $pdo->query("
    SELECT COUNT(*) FROM orders 
    WHERE DATE(created_at) = CURDATE()
")->fetchColumn();

$latestOrders = $pdo->query("
    SELECT * FROM orders
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

/* Fetch ALL items at once (important for performance) */
$orderItemsStmt = $pdo->query("
    SELECT order_id, item_name, qty, price
    FROM order_items
");
$orderItemsRaw = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);

/* Group items by order_id */
$orderItems = [];
foreach ($orderItemsRaw as $item) {
    $orderItems[$item['order_id']][] = $item;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HomeThali Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f8;
}

/* HEADER */
.header {
    background: #4CAF50;
    color: white;
    padding: 18px 30px;
    font-size: 24px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logout {
    background: white;
    color: #4CAF50;
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
}

/* CONTAINER */
.container {
    padding: 25px;
}

/* CARDS */
.cards {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    width: 220px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.card h3 {
    margin: 0;
    color: #777;
    font-size: 16px;
}

.card p {
    font-size: 28px;
    font-weight: bold;
    margin-top: 10px;
    color: #4CAF50;
}

/* TABLE */
.table-box {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #4CAF50;
    color: white;
    padding: 12px;
    text-align: left;
}

td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    color: white;
    font-weight: bold;
    display: inline-block;
}

/* Payment colors */
.upi {
    background: #2196F3;
}

.cash {
    background: #9C27B0;
}

.credit_card {
    background: #FF9800;
}

.debit_card {
    background: #009688;
}

.payment_on_delivery {
    background: #607D8B;
}
    
.cod,
.payment_on_delivery {
    background: #607D8B; /* Grey-blue */
}
    
.view-btn {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
}

.items-box {
    background: #f9f9f9;
    padding: 12px 16px;
    border-radius: 8px;
}

.items-box ul {
    margin: 8px 0 0;
    padding-left: 18px;
}

.items-box li {
    margin-bottom: 4px;
    font-size: 14px;
}

</style>
</head>

<body>

<div class="header">
    HomeThali – Admin Dashboard
    <a href="logout.php" class="logout">Home</a>
</div>

<div class="container">

    <!-- SUMMARY CARDS -->
    <div class="cards">
        <div class="card">
            <h3>Total Orders</h3>
            <p><?= $totalOrders ?></p>
        </div>

        <div class="card">
            <h3>Today's Orders</h3>
            <p><?= $todayOrders ?></p>
        </div>

        <div class="card">
            <h3>Total Revenue</h3>
            <p>₹ <?= $totalRevenue ?></p>
        </div>
    </div>

    <!-- ORDERS TABLE -->
    <div class="table-box">
        <h2>Latest Orders</h2>

        <table>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Room</th>
                <th>Amount</th>
                <th>Payment</th>
                <th>Time</th>
            </tr>

           <?php foreach ($latestOrders as $order): ?>
<tr>
    <td><?= $order['order_id'] ?></td>
    <td><?= htmlspecialchars($order['customer_name']) ?></td>
    <td><?= htmlspecialchars($order['customer_phone']) ?></td>
    <td><?= htmlspecialchars($order['room_number']) ?></td>
    <td>₹<?= $order['total_amount'] ?></td>

    <td>
        <?php
            $method = strtolower(str_replace(' ', '_', $order['payment_method']));
        ?>
        <span class="badge <?= $method ?>">
            <?= strtoupper($order['payment_method']) ?>
        </span>
    </td>

<td>
<?php
    echo date(
        "d M Y, h:i A",
        strtotime($order['created_at'])
    );
?>
</td>




    <td>
        <button class="view-btn" onclick="toggleItems('<?= $order['order_id'] ?>')">
            View Items
        </button>
    </td>
</tr>

<!-- 🔽 Hidden items row -->
<tr id="items-<?= $order['order_id'] ?>" class="items-row" style="display:none;">
    <td colspan="8">
        <div class="items-box">
            <strong>Order Items:</strong>
            <ul>
                <?php foreach ($orderItems[$order['order_id']] ?? [] as $item): ?>
                    <li>
                        <?= htmlspecialchars($item['item_name']) ?>
                        — <?= $item['qty'] ?> × ₹<?= $item['price'] ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </td>
</tr>
<?php endforeach; ?>

        </table>
    </div>

</div>

    <script>
function toggleItems(orderId) {
    const row = document.getElementById("items-" + orderId);
    row.style.display = row.style.display === "none" ? "table-row" : "none";
}
</script>

</body>
</html>
