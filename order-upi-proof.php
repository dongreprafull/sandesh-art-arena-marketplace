<?php
require_once __DIR__ . '/config.php';

$pdo = db();
$orderId = (int) ($_POST['order_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND payment_method = 'upi'");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo 'Order record not found.';
    exit;
}

if (empty($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
    header('Location: checkout.php?err=upload');
    exit;
}

$allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
$origName = $_FILES['proof']['name'];
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    header('Location: checkout.php?err=filetype');
    exit;
}

$uploadDir = __DIR__ . '/uploads/payment_proofs';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$fileName = 'proof_' . $orderId . '_' . time() . '.' . $ext;
$destPath = $uploadDir . '/' . $fileName;

if (!move_uploaded_file($_FILES['proof']['tmp_name'], $destPath)) {
    header('Location: checkout.php?err=savefail');
    exit;
}

$publicUrl = 'uploads/payment_proofs/' . $fileName;
$pdo->prepare("UPDATE orders SET upi_proof_url = ? WHERE id = ?")->execute([$publicUrl, $orderId]);
unset($_SESSION['cart']);

header('Location: order-success.php?id=' . $orderId . '&pending=1');
exit;
