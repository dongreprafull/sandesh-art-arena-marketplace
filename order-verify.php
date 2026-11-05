<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/payments.php';

$pdo = db();
$orderId = (int) ($_POST['order_id'] ?? 0);
$paymentId = $_POST['razorpay_payment_id'] ?? '';
$orderRef = $_POST['razorpay_order_id'] ?? '';
$signature = $_POST['razorpay_signature'] ?? '';

if (!$orderId || !$paymentId || !$orderRef || !$signature) {
    http_response_code(400);
    echo 'Missing payment details.';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || $order['razorpay_order_id'] !== $orderRef) {
    http_response_code(404);
    echo 'Order record not found.';
    exit;
}

$s = msettings();
$keySecret = $s['razorpay_key_secret'] ?? '';

if ($keySecret !== '' && razorpay_verify_signature($orderRef, $paymentId, $signature, $keySecret)) {
    $receiptNo = generate_receipt_no($orderId);
    $pdo->prepare("UPDATE orders SET payment_status = 'paid', order_status = 'confirmed', razorpay_payment_id = ?, receipt_no = ? WHERE id = ?")
        ->execute([$paymentId, $receiptNo, $orderId]);
    unset($_SESSION['cart']);
    header('Location: order-success.php?id=' . $orderId);
    exit;
} else {
    $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?")->execute([$orderId]);
    header('Location: order-success.php?id=' . $orderId . '&failed=1');
    exit;
}
