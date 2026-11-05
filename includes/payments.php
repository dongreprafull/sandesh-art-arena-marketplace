<?php
/** Razorpay helpers (server-side order creation + signature verification). */

function razorpay_create_order(float $amountInr, string $keyId, string $keySecret, string $receipt): ?array {
    if ($keyId === '' || $keySecret === '') return null;
    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => $keyId . ':' . $keySecret,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'amount' => (int) round($amountInr * 100),
            'currency' => 'INR',
            'receipt' => $receipt,
        ]),
        CURLOPT_TIMEOUT => 15,
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($res === false || $code >= 300) return null;
    $data = json_decode($res, true);
    return is_array($data) ? $data : null;
}

function razorpay_verify_signature(string $orderId, string $paymentId, string $signature, string $keySecret): bool {
    $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);
    return hash_equals($expected, $signature);
}

function generate_receipt_no(int $orderId): string {
    return 'SM-' . date('Y') . '-' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);
}
