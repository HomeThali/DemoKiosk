<?php
/**************************************************
 * save_order.php
 * -----------------------------------------------
 * Saves FULL order after successful payment
 * SINGLE FILE ONLY
 **************************************************/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

/* ================= DB CONFIG ================= */
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
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );

    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);
    exit;
}

/* ================= READ INPUT ================= */
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON"
    ]);
    exit;
}

/* ================= VALIDATION ================= */
if (
    empty($data['order_id']) ||
    empty($data['customer']['name']) ||
    empty($data['customer']['phone']) ||
    empty($data['customer']['room']) ||
    empty($data['payment_method']) ||
    empty($data['cart']) ||
    empty($data['total_amount'])
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields"
    ]);
    exit;
}

/* ================= TRANSACTION ================= */
try {
    $pdo->beginTransaction();

    /* ---------- INSERT ORDER ---------- */
    $stmtOrder = $pdo->prepare("
        INSERT INTO orders
        (order_id, customer_name, customer_phone, room_number, payment_method, total_amount)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmtOrder->execute([
        $data['order_id'],
        $data['customer']['name'],
        $data['customer']['phone'],
        $data['customer']['room'],
        $data['payment_method'],
        $data['total_amount']
    ]);

    /* ---------- INSERT ORDER ITEMS ---------- */
    $stmtItem = $pdo->prepare("
        INSERT INTO order_items
        (order_id, item_name, price, qty, item_total)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($data['cart'] as $item) {
        $stmtItem->execute([
            $data['order_id'],
            $item['name'],
            $item['price'],
            $item['qty'],
            $item['price'] * $item['qty']
        ]);
    }

    /* ---------- COMMIT ---------- */
    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Order saved successfully",
        "order_id" => $data['order_id']
    ]);

} catch (Exception $e) {
    $pdo->rollBack();

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to save order"
    ]);
}
