<?php
$pageTitle = 'My Products';
require_once __DIR__ . '/includes/vendor_header.php';
$pdo = db();
$vid = (int) $vendor['id'];

$fields = [
    'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
    'category' => ['label' => 'Category', 'type' => 'text', 'required' => true],
    'description' => ['label' => 'Description', 'type' => 'textarea'],
    'price' => ['label' => 'Price (₹)', 'type' => 'number', 'required' => true],
    'stock_qty' => ['label' => 'Stock Quantity', 'type' => 'number'],
    'image' => ['label' => 'Product Image', 'type' => 'image'],
    'is_active' => ['label' => 'Active (visible in shop)', 'type' => 'select', 'options' => ['1' => 'Yes', '0' => 'No']],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM products WHERE id = ? AND vendor_id = ?")->execute([$id, $vid]);
    header('Location: products.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $id = (int) ($_POST['id'] ?? 0);
    $existingRow = null;
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
        $stmt->execute([$id, $vid]);
        $existingRow = $stmt->fetch();
        if (!$existingRow) {
            header('Location: products.php');
            exit;
        }
    }
    $cols = [];
    $vals = [];
    foreach ($fields as $key => $field) {
        if ($field['type'] === 'image') {
            $cols[] = $key;
            $vals[] = handle_upload($key, $existingRow[$key] ?? null, 'uploads/products');
            continue;
        }
        $val = trim($_POST[$key] ?? '');
        if ($field['type'] === 'number') $val = $val === '' ? 0 : $val;
        $cols[] = $key;
        $vals[] = $val === '' ? null : $val;
    }
    if ($id) {
        $sets = implode(', ', array_map(fn($c) => "`$c` = ?", $cols));
        $pdo->prepare("UPDATE products SET $sets WHERE id = ? AND vendor_id = ?")->execute([...$vals, $id, $vid]);
    } else {
        $cols[] = 'vendor_id';
        $vals[] = $vid;
        $colList = implode(', ', array_map(fn($c) => "`$c`", $cols));
        $ph = implode(', ', array_fill(0, count($cols), '?'));
        $pdo->prepare("INSERT INTO products ($colList) VALUES ($ph)")->execute($vals);
    }
    header('Location: products.php');
    exit;
}

$action = $_GET['action'] ?? 'list';
$editRow = null;
if (in_array($action, ['add', 'edit'], true)) {
    if ($action === 'edit') {
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
        $stmt->execute([$id, $vid]);
        $editRow = $stmt->fetch();
    }
    ?>
    <h2><?= $action === 'edit' ? 'Edit' : 'Add' ?> Product</h2>
    <?php if ($vendor['status'] !== 'approved'): ?>
      <div class="flash err" style="background:#fff3da;color:#8a5a00;border-color:#ffe3a3">You can prepare products now, but they'll only appear in the shop once your vendor account is approved.</div>
    <?php endif; ?>
    <form class="admin-form" method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int) ($editRow['id'] ?? 0) ?>">
      <?php foreach ($fields as $key => $field): ?>
        <div>
          <label for="<?= e($key) ?>"><?= e($field['label']) ?></label>
          <?php $val = $editRow[$key] ?? ($key === 'is_active' ? '1' : ''); ?>
          <?php if ($field['type'] === 'image'): ?>
            <?php if (!empty($val)): ?><div style="margin:.3rem 0"><img src="<?= e(img_url($val)) ?>" alt="" style="max-width:140px;border-radius:6px;display:block"></div><?php endif; ?>
            <input id="<?= e($key) ?>" name="<?= e($key) ?>" type="file" accept="image/*">
            <p class="muted" style="margin-top:.3rem"><?= $val ? 'Choose a new file to replace the current image, or leave blank to keep it.' : 'Upload an image file.' ?></p>
          <?php elseif ($field['type'] === 'textarea'): ?>
            <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" <?= !empty($field['required']) ? 'required' : '' ?>><?= e($val) ?></textarea>
          <?php elseif ($field['type'] === 'select'): ?>
            <select id="<?= e($key) ?>" name="<?= e($key) ?>">
              <?php foreach ($field['options'] as $optVal => $optLabel): ?>
                <option value="<?= e($optVal) ?>" <?= (string) $val === (string) $optVal ? 'selected' : '' ?>><?= e($optLabel) ?></option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input id="<?= e($key) ?>" name="<?= e($key) ?>" type="<?= $field['type'] === 'number' ? 'number' : 'text' ?>" <?= $field['type'] === 'number' ? 'step="0.01"' : '' ?>
              <?= !empty($field['required']) ? 'required' : '' ?> value="<?= e((string) $val) ?>">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <button class="btn primary" type="submit">Save</button>
      <a class="btn ghost" href="products.php">Cancel</a>
    </form>
    <?php
} else {
    $rows = $pdo->prepare("SELECT * FROM products WHERE vendor_id = ? ORDER BY id DESC");
    $rows->execute([$vid]);
    $rows = $rows->fetchAll();
    ?>
    <div class="admin-topbar-actions" style="margin-bottom:1rem">
      <a class="btn primary" href="products.php?action=add">+ Add Product</a>
    </div>
    <table class="admin-table">
      <thead>
        <tr><th>Image</th><th>Title</th><th>Category</th><th>Price</th><th>Stock</th><th>Active</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?php if ($row['image']): ?><img src="<?= e(img_url($row['image'])) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:5px"><?php endif; ?></td>
            <td><?= e($row['title']) ?></td>
            <td><?= e($row['category']) ?></td>
            <td>₹<?= number_format((float) $row['price'], 2) ?></td>
            <td><?= (int) $row['stock_qty'] ?></td>
            <td><?= $row['is_active'] ? 'Yes' : 'No' ?></td>
            <td>
              <a class="btn ghost" href="products.php?action=edit&id=<?= (int) $row['id'] ?>">Edit</a>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete this product?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <button class="btn danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7">No products yet. Add your first one!</td></tr><?php endif; ?>
      </tbody>
    </table>
    <?php
}
require_once __DIR__ . '/includes/vendor_footer.php';
