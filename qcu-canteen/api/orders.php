<?php
// ============================================================
// api/orders.php (FINAL FIXED VERSION)
// ============================================================

require_once 'config.php';

// ⚠️ MOVE THESE TO config.php LATER (for security)
define('TELEGRAM_BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('TELEGRAM_CHAT_ID',   'YOUR_CHAT_ID_HERE');

// ============================================================
// TELEGRAM NOTIFICATION
// ============================================================

function notifyTelegram(array $order): void {

    if (!TELEGRAM_BOT_TOKEN || !TELEGRAM_CHAT_ID) return;

    $itemLines = '';

    foreach ($order['items'] as $item) {
        $name  = $item['name'] ?? 'Unknown';
        $qty   = $item['qty'] ?? $item['quantity'] ?? 1;
        $price = isset($item['price']) ? '₱' . number_format((float)$item['price'], 2) : '';

        $itemLines .= "• {$name} x{$qty} {$price}\n";
    }

    $message = "🛎️ *New Order*\n\n"
        . "📋 *Code:* `{$order['orderCode']}`\n"
        . "👤 {$order['customer']}\n"
        . "🎓 {$order['studentId']}\n"
        . "🏪 {$order['store']}\n\n"
        . "🍽️ *Items:*\n{$itemLines}\n"
        . "💰 ₱" . number_format($order['total'], 2);

    $payload = json_encode([
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ]);

    $ch = curl_init("https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage");

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);

    curl_exec($ch);
    curl_close($ch);
}

// ============================================================

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

switch ($method) {

    // ========================================================
    // GET ORDERS
    // ========================================================
    case 'GET':

        $where = [];
        $params = [];
        $types = '';

        if (!empty($_GET['store'])) {
            $where[] = 'store = ?';
            $params[] = $_GET['store'];
            $types .= 's';
        }

        if (!empty($_GET['status'])) {
            $where[] = 'status = ?';
            $params[] = $_GET['status'];
            $types .= 's';
        }

        if (!empty($_GET['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = (int)$_GET['user_id'];
            $types .= 'i';
        }

        if (!empty($_GET['search'])) {
            $s = '%' . $_GET['search'] . '%';
            $where[] = '(customer LIKE ? OR order_code LIKE ? OR student_id LIKE ?)';
            $params[] = $s; $params[] = $s; $params[] = $s;
            $types .= 'sss';
        }

        $sql = 'SELECT * FROM orders';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY created_at DESC';

        $stmt = $db->prepare($sql);

        if ($params) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
        $db->close();

        respond([
            'success' => true,
            'orders' => array_map(function ($r) {
                return [
                    'id'        => (int)$r['id'],
                    'orderCode' => $r['order_code'],
                    'customer'  => $r['customer'],
                    'role'      => $r['role'],
                    'studentId' => $r['student_id'],
                    'store'     => $r['store'],
                    'items'     => json_decode($r['items'], true) ?? [],
                    'total'     => (float)$r['total'],
                    'status'    => $r['status'],
                    'createdAt' => $r['created_at'],
                ];
            }, $rows)
        ]);
        break;

    // ========================================================
    // PLACE ORDER
    // ========================================================
    case 'POST':

        $body = getBody();

        $userId    = isset($body['userId']) ? (int)$body['userId'] : null;
        $customer  = trim($body['customer'] ?? '');
        $role      = $body['role'] ?? 'Student';
        $studentId = trim($body['studentId'] ?? '');
        $store     = trim($body['store'] ?? '');
        $items     = $body['items'] ?? [];
        $total     = (float)($body['total'] ?? 0);

        if (!$customer || !$studentId || !$store || !is_array($items) || count($items) === 0 || $total <= 0) {
            respond(['error' => 'Invalid order data'], 400);
        }

        $orderCode = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        $itemsJson = json_encode($items);

        $stmt = $db->prepare(
            "INSERT INTO orders (order_code, user_id, customer, role, student_id, store, items, total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        // handle nullable user_id
        if ($userId === null) {
            $stmt->bind_param('sisssssd', $orderCode, $userId, $customer, $role, $studentId, $store, $itemsJson, $total);
        } else {
            $stmt->bind_param('sisssssd', $orderCode, $userId, $customer, $role, $studentId, $store, $itemsJson, $total);
        }

        if ($stmt->execute()) {

            $newId = $db->insert_id;

            $stmt->close();
            $db->close();

            notifyTelegram([
                'orderCode' => $orderCode,
                'customer'  => $customer,
                'studentId' => $studentId,
                'store'     => $store,
                'items'     => $items,
                'total'     => $total
            ]);

            respond([
                'success' => true,
                'id' => $newId,
                'orderCode' => $orderCode
            ]);
        }

        respond(['error' => 'Failed to place order'], 500);
        break;

    // ========================================================
    // UPDATE STATUS (ADMIN ONLY)
    // ========================================================
    case 'PUT':

        if (!isAdmin()) {
            respond(['error' => 'Unauthorized'], 403);
        }

        $id = (int)($_GET['id'] ?? 0);
        $body = getBody();
        $status = $body['status'] ?? '';

        if ($id <= 0) respond(['error' => 'Invalid ID'], 400);

        if (!in_array($status, ['Pending','Preparing','Ready','Completed'])) {
            respond(['error' => 'Invalid status'], 400);
        }

        $stmt = $db->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);

        if ($stmt->execute()) {
            respond(['success' => true]);
        }

        respond(['error' => 'Update failed'], 500);
        break;

    // ========================================================
    // DELETE (ADMIN ONLY)
// ========================================================
case 'DELETE':

    if (!isAdmin()) {
        respond(['error' => 'Unauthorized'], 403);
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id > 0) {
        $stmt = $db->prepare("DELETE FROM orders WHERE id=?");
        $stmt->bind_param("i", $id);
    } else {
        $stmt = $db->prepare("DELETE FROM orders");
    }

    if ($stmt->execute()) {
        respond(['success' => true]);
    }

    respond(['error' => 'Delete failed'], 500);
    break;

    default:
        respond(['error' => 'Method not allowed'], 405);
}