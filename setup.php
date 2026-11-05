<?php
/** One-time: create Marketplace tables + seed starter products. Delete after running. */
require __DIR__ . '/config.php';
header('Content-Type: text/plain');
$pdo = db();

try {
    $sql = file_get_contents(__DIR__ . '/schema_marketplace.sql');
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        $pdo->exec($stmt);
    }
    echo "Tables created/verified.\n";

    $pdo->exec("INSERT IGNORE INTO marketplace_settings (id, about_text, shipping_note, cod_enabled, upi_id) VALUES (
      1,
      'Original paintings, prints and handmade art pieces from Sandesh Art Arena''s studio and partner artisans. Every purchase supports our art programmes. Edit this from Admin > Settings.',
      'Orders are shipped within 3-5 working days across India. Cash on Delivery available on orders under Rs 5000.',
      1,
      'sandeshartarena@upi'
    )");
    echo "Settings row seeded (edit UPI ID / Razorpay keys / COD toggle from Admin > Settings).\n";

    $img1 = 'https://artarenastudio.com/assets/three-sectors-artwork.png';
    $img2 = 'https://artarenastudio.com/assets/three-sectors-artwork-v2.png';
    $img3 = 'https://artarenastudio.com/assets/three-sectors-artwork-v3.png';

    $count = $pdo->query("SELECT COUNT(*) c FROM products")->fetch()['c'];
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO products (title, description, category, price, image, stock_qty, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['Sunset Over the Ghats', 'Original acrylic on canvas, 24x18 inches, framed and ready to hang.', 'Paintings', 4500.00, $img1, 3, 1]);
        $stmt->execute(['Monsoon Miniature', 'Hand-painted miniature artwork inspired by traditional Indian monsoon motifs.', 'Paintings', 2200.00, $img2, 5, 2]);
        $stmt->execute(['Urban Sketch Print — A3', 'High-quality giclee print of an original urban sketch, A3 size.', 'Prints', 850.00, $img3, 20, 1]);
        $stmt->execute(['Festival Colors Print — A4', 'Vibrant festival-themed print on archival paper, A4 size.', 'Prints', 450.00, $img1, 25, 2]);
        $stmt->execute(['Hand Block-Printed Tote Bag', 'Cotton tote bag block-printed by women artisans trained through Sandesh Foundation.', 'Handmade Crafts', 650.00, $img2, 15, 1]);
        $stmt->execute(['Terracotta Wall Hanging', 'Hand-crafted terracotta wall art, 12 inch diameter.', 'Handmade Crafts', 1200.00, $img3, 8, 2]);
        $stmt->execute(['Artful Ceramic Mug', 'Hand-painted ceramic mug with original art arena designs.', 'Accessories', 399.00, $img1, 30, 1]);
        $stmt->execute(['Canvas Tote — Studio Edition', 'Durable canvas tote bag printed with studio artwork.', 'Accessories', 549.00, $img2, 18, 2]);
        echo "products: 8 rows added.\n";
    } else {
        echo "products: already has data, skipped.\n";
    }

    echo "\nSetup complete. Delete setup.php and schema_marketplace.sql now.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Setup failed: " . $e->getMessage() . "\n";
}
