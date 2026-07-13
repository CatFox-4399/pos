<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Products - ' . APP_NAME;

$message = ''; $messageType = 'success';
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'create' || $act === 'edit') {
        $barcode = trim($_POST['barcode']);
        $sku = trim($_POST['sku']);
        $name = trim($_POST['product_name']);
        $category = trim($_POST['category']);
        $brand = trim($_POST['brand']);
        $sell = (float)$_POST['selling_price'];
        $cost = (float)$_POST['cost_price'];
        $unit = trim($_POST['unit']);
        $status = $_POST['status'];

        if (empty($barcode)) $barcode = generateBarcode();
        if (empty($name)) { $message = 'Product name required.'; $messageType = 'danger'; goto end; }

        // Handle image upload
        $imageName = $_POST['existing_image'] ?? null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                $imageName = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../uploads/products/' . $imageName);
            }
        }

        try {
            if ($act === 'create') {
                $stmt = $pdo->prepare("INSERT INTO products (barcode,sku,product_name,category,brand,selling_price,cost_price,unit,image,status) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$barcode,$sku,$name,$category,$brand,$sell,$cost,$unit,$imageName,$status]);
                auditLog($_SESSION['user_id'], 'create_product', "Created product: $name");
                $message = "Product '$name' created.";
            } else {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE products SET barcode=?,sku=?,product_name=?,category=?,brand=?,selling_price=?,cost_price=?,unit=?,image=?,status=? WHERE id=?");
                $stmt->execute([$barcode,$sku,$name,$category,$brand,$sell,$cost,$unit,$imageName,$status,$id]);
                auditLog($_SESSION['user_id'], 'edit_product', "Edited product ID: $id");
                $message = "Product updated.";
            }
            $action = 'list';
        } catch (PDOException $e) {
            $message = 'Barcode already exists.'; $messageType = 'danger';
        }
    } elseif ($act === 'delete') {
        $id = (int)$_POST['id'];
        // Check if used in transactions
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaction_items WHERE product_id=?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $pdo->prepare("UPDATE products SET status='inactive' WHERE id=?")->execute([$id]);
            $message = "Product has transactions — set to Inactive instead of deleted."; $messageType = 'warning';
        } else {
            $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
            $message = "Product deleted.";
        }
        auditLog($_SESSION['user_id'], 'delete_product', "Deleted/deactivated product ID: $id");
        $action = 'list';
    }
}
end:

$search = $_GET['s'] ?? '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_name LIKE ? OR barcode LIKE ? OR sku LIKE ? ORDER BY product_name");
    $stmt->execute(["%$search%","%$search%","%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY product_name");
}
$products = $stmt->fetchAll();

$editProduct = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editProduct = $stmt->fetch();
}
$categories = $pdo->query("SELECT DISTINCT category FROM products WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>Product Management</h4>
        <a href="?action=add" class="ms-auto btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Add Product</a>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($action === 'add' || ($action === 'edit' && $editProduct)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-box me-2"></i><?= $action==='add'?'Add New Product':'Edit Product' ?></h6>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="act" value="<?= $action==='add'?'create':'edit' ?>">
                <?php if ($editProduct): ?><input type="hidden" name="id" value="<?= $editProduct['id'] ?>"><input type="hidden" name="existing_image" value="<?= $editProduct['image'] ?>"><?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Barcode</label>
                        <div class="input-group">
                            <input type="text" name="barcode" class="form-control" id="barcodeField" value="<?= htmlspecialchars($editProduct['barcode'] ?? '') ?>" placeholder="Auto-generate if empty">
                            <button type="button" class="btn btn-outline-secondary" onclick="genBarcode()"><i class="bi bi-upc-scan"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">SKU</label>
                        <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($editProduct['sku'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Product Name *</label>
                        <input type="text" name="product_name" class="form-control" required value="<?= htmlspecialchars($editProduct['product_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Category</label>
                        <input type="text" name="category" class="form-control" list="categoryList" value="<?= htmlspecialchars($editProduct['category'] ?? '') ?>">
                        <datalist id="categoryList"><?php foreach($categories as $c): ?><option value="<?= htmlspecialchars($c) ?>"><?php endforeach; ?></datalist>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Brand</label>
                        <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($editProduct['brand'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Selling Price (RM) *</label>
                        <input type="number" name="selling_price" step="0.01" min="0" class="form-control" required value="<?= $editProduct['selling_price'] ?? '0.00' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Cost Price (RM)</label>
                        <input type="number" name="cost_price" step="0.01" min="0" class="form-control" value="<?= $editProduct['cost_price'] ?? '0.00' ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label fw-semibold">Unit</label>
                        <input type="text" name="unit" class="form-control" value="<?= htmlspecialchars($editProduct['unit'] ?? 'pcs') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= ($editProduct['status']??'active')==='active'?'selected':'' ?>>Active</option>
                            <option value="inactive" <?= ($editProduct['status']??'')==='inactive'?'selected':'' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Product Image</label>
                        <?php if (!empty($editProduct['image'])): ?>
                        <div class="mb-2"><img src="<?= APP_URL ?>/uploads/products/<?= $editProduct['image'] ?>" style="height:50px;border-radius:6px;" onerror="this.style.display='none'"></div>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2"><i class="bi bi-save me-2"></i>Save</button>
                        <a href="?" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Search -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="s" class="form-control" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                <?php if ($search): ?><a href="?" class="btn btn-outline-secondary">Clear</a><?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-2 d-flex align-items-center">
            <span class="text-muted small"><?= count($products) ?> products</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>Image</th><th>Barcode</th><th>Name</th><th>Category</th><th>Sell Price</th><th>Cost</th><th>Unit</th><th>Status</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <?php if ($p['image']): ?>
                        <img src="<?= APP_URL ?>/uploads/products/<?= $p['image'] ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px;" onerror="this.src='<?= APP_URL ?>/assets/img/no-image.png'">
                        <?php else: ?><span class="text-muted"><i class="bi bi-image" style="font-size:1.5rem"></i></span><?php endif; ?>
                    </td>
                    <td><code class="small"><?= htmlspecialchars($p['barcode']) ?></code></td>
                    <td><span class="fw-semibold"><?= htmlspecialchars($p['product_name']) ?></span><br><span class="text-muted small"><?= htmlspecialchars($p['brand']) ?></span></td>
                    <td class="text-muted small"><?= htmlspecialchars($p['category']) ?></td>
                    <td class="text-success fw-bold">RM <?= number_format($p['selling_price'],2) ?></td>
                    <td class="text-muted">RM <?= number_format($p['cost_price'],2) ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($p['unit']) ?></td>
                    <td><span class="badge <?= $p['status']==='active'?'status-active':'status-inactive' ?>"><?= ucfirst($p['status']) ?></span></td>
                    <td>
                        <a href="?action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete/deactivate this product?')">
                            <input type="hidden" name="act" value="delete">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                <tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>No products found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function genBarcode() {
    const ts = Date.now().toString().slice(-10);
    document.getElementById('barcodeField').value = '8' + ts;
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body></html>
