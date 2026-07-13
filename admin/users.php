<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Users - ' . APP_NAME;

$message = '';
$messageType = 'success';
$action = $_GET['action'] ?? 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'create') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        $status = $_POST['status'];
        if (empty($username) || empty($password)) {
            $message = 'Username and password are required.'; $messageType = 'danger';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $hash, $role, $status]);
                auditLog($_SESSION['user_id'], 'create_user', "Created user: $username");
                $message = "User '$username' created successfully.";
                $action = 'list';
            } catch (PDOException $e) {
                $message = 'Username already exists.'; $messageType = 'danger';
            }
        }
    } elseif ($act === 'edit') {
        $id = (int)$_POST['id'];
        $username = trim($_POST['username']);
        $role = $_POST['role'];
        $status = $_POST['status'];
        $password = $_POST['password'];
        try {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, role=?, status=? WHERE id=?");
                $stmt->execute([$username, $hash, $role, $status, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, role=?, status=? WHERE id=?");
                $stmt->execute([$username, $role, $status, $id]);
            }
            auditLog($_SESSION['user_id'], 'edit_user', "Edited user ID: $id");
            $message = "User updated successfully.";
            $action = 'list';
        } catch (PDOException $e) {
            $message = 'Error updating user.'; $messageType = 'danger';
        }
    } elseif ($act === 'delete') {
        $id = (int)$_POST['id'];
        if ($id === $_SESSION['user_id']) {
            $message = 'Cannot delete your own account.'; $messageType = 'danger';
        } else {
            $pdo->prepare("UPDATE users SET status='inactive' WHERE id=?")->execute([$id]);
            auditLog($_SESSION['user_id'], 'disable_user', "Disabled user ID: $id");
            $message = "User account disabled.";
        }
        $action = 'list';
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY role, username")->fetchAll();

$editUser = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editUser = $stmt->fetch();
}
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
        <h4 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>User Management</h4>
        <a href="?action=add" class="ms-auto btn btn-primary"><i class="bi bi-person-plus me-2"></i>Add User</a>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($action === 'add' || ($action === 'edit' && $editUser)): ?>
    <!-- Add/Edit Form -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-person-<?= $action==='add'?'plus':'gear' ?> me-2"></i><?= $action==='add'?'Create New User':'Edit User' ?></h6>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="act" value="<?= $action==='add'?'create':'edit' ?>">
                <?php if ($editUser): ?><input type="hidden" name="id" value="<?= $editUser['id'] ?>"><?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Username *</label>
                        <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($editUser['username'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Password <?= $action==='edit'?'(leave blank to keep)':' *' ?></label>
                        <input type="password" name="password" class="form-control" <?= $action==='add'?'required':'' ?> placeholder="<?= $action==='edit'?'Leave blank to keep current':'' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Role</label>
                        <select name="role" class="form-select">
                            <option value="cashier" <?= ($editUser['role']??'cashier')==='cashier'?'selected':'' ?>>Cashier</option>
                            <option value="admin" <?= ($editUser['role']??'')==='admin'?'selected':'' ?>>Admin</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= ($editUser['status']??'active')==='active'?'selected':'' ?>>Active</option>
                            <option value="inactive" <?= ($editUser['status']??'')==='inactive'?'selected':'' ?>>Inactive</option>
                        </select>
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

    <!-- Users Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <input type="text" id="tableSearch" class="form-control form-control-sm w-auto d-inline-block" placeholder="Search users...">
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>#</th><th>Username</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><i class="bi bi-person-circle me-2 text-muted"></i><?= htmlspecialchars($user['username']) ?></td>
                    <td><span class="badge <?= $user['role']==='admin'?'bg-danger':'bg-primary' ?>"><?= strtoupper($user['role']) ?></span></td>
                    <td><span class="badge <?= $user['status']==='active'?'status-active':'status-inactive' ?>"><?= ucfirst($user['status']) ?></span></td>
                    <td class="text-muted small"><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <a href="?action=edit&id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Disable this user?')">
                            <input type="hidden" name="act" value="delete">
                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-person-slash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body></html>
