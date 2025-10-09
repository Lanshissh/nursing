<?php
$page_title = "Medicine Inventory";
require_once __DIR__ . "/../config.php";

$TABLE = 'medicines';

function redirect_with_msg($msg){
  $base = strtok($_SERVER['REQUEST_URI'], '?');
  header("Location: {$base}?msg=" . urlencode($msg));
  exit;
}

// AJAX endpoint to fetch a medicine record
if (isset($_GET['ajax']) && $_GET['ajax'] === 'medicine' && isset($_GET['id'])) {
  header('Content-Type: application/json');
  $id = (int)$_GET['id'];
  $stmt = $pdo->prepare("SELECT id, name, generic_name, category, unit, quantity, reorder_level, expiry_date, supplier, notes FROM {$TABLE} WHERE id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
  echo json_encode($row);
  exit;
}

// Create - FIXED: removed extra parameter in execute()
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__action'] ?? '') === 'create') {
  $stmt = $pdo->prepare("INSERT INTO {$TABLE} (name, generic_name, category, unit, quantity, reorder_level, expiry_date, supplier, notes) VALUES (?,?,?,?,?,?,?,?,?)");
  $stmt->execute([
    trim($_POST['name'] ?? ''),
    trim($_POST['generic_name'] ?? ''),
    trim($_POST['category'] ?? ''),
    trim($_POST['unit'] ?? ''),
    (int)($_POST['quantity'] ?? 0),
    (int)($_POST['reorder_level'] ?? 0),
    $_POST['expiry_date'] !== '' ? $_POST['expiry_date'] : null,
    trim($_POST['supplier'] ?? ''),
    trim($_POST['notes'] ?? '')
  ]);
  redirect_with_msg('Medicine added successfully');
}

// Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__action'] ?? '') === 'update') {
  $id = (int)($_POST['id'] ?? 0);
  $stmt = $pdo->prepare("UPDATE {$TABLE} SET name=?, generic_name=?, category=?, unit=?, quantity=?, reorder_level=?, expiry_date=?, supplier=?, notes=? WHERE id=?");
  $stmt->execute([
    trim($_POST['name'] ?? ''),
    trim($_POST['generic_name'] ?? ''),
    trim($_POST['category'] ?? ''),
    trim($_POST['unit'] ?? ''),
    (int)($_POST['quantity'] ?? 0),
    (int)($_POST['reorder_level'] ?? 0),
    $_POST['expiry_date'] !== '' ? $_POST['expiry_date'] : null,
    trim($_POST['supplier'] ?? ''),
    trim($_POST['notes'] ?? ''),
    $id
  ]);
  redirect_with_msg('Medicine updated successfully');
}

// Delete
$deleteId = null;
if (isset($_GET['delete'])) $deleteId = (int)$_GET['delete'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) $deleteId = (int)$_POST['delete'];
if ($deleteId) {
  $stmt = $pdo->prepare("DELETE FROM {$TABLE} WHERE id=?");
  $stmt->execute([$deleteId]);
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'msg' => 'Medicine deleted']);
    exit;
  }
  redirect_with_msg('Medicine deleted');
}

// Search and filter
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$low_stock = isset($_GET['low_stock']) ? true : false;

$where = [];
$params = [];

if ($q !== '') {
  $where[] = "(name LIKE ? OR generic_name LIKE ? OR supplier LIKE ?)";
  $like = "%$q%";
  array_push($params, $like, $like, $like);
}

if ($category_filter !== '') {
  $where[] = "category = ?";
  $params[] = $category_filter;
}

if ($low_stock) {
  $where[] = "quantity <= reorder_level";
}

$sql = "SELECT * FROM {$TABLE}";
if ($where) {
  $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categories = [];
try {
  $catStmt = $pdo->query("SELECT DISTINCT category FROM {$TABLE} WHERE category IS NOT NULL AND category != '' ORDER BY category");
  $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}

// Calculate statistics
$total_items = count($rows);
$low_stock_count = 0;
$expired_count = 0;

foreach ($rows as $r) {
  if ($r['quantity'] <= $r['reorder_level']) $low_stock_count++;
  if ($r['expiry_date'] && strtotime($r['expiry_date']) < time()) $expired_count++;
}

include __DIR__ . "/../partials/header.php";
?>
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root {
  --bg-light: #f8fafc;
  --surface-light: #ffffff;
  --border-light: #e2e8f0;
  --text-light: #0f172a;
  --muted-light: #64748b;
  --accent: #10b981;
  --accent-hover: #059669;
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #3b82f6;
  --shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
}

body {
  background: var(--bg-light);
  color: var(--text-light);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-title {
  font-size: 1.875rem;
  font-weight: 700;
  color: var(--text-light);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.title-icon {
  width: 2.5rem;
  height: 2.5rem;
  background: linear-gradient(135deg, var(--accent) 0%, var(--accent-hover) 100%);
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: var(--surface-light);
  border: 1px solid var(--border-light);
  border-radius: 1rem;
  padding: 1.25rem;
  box-shadow: var(--shadow);
  transition: all 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--muted-light);
  margin-bottom: 0.5rem;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-light);
}

.stat-icon {
  float: right;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.stat-success {
  background: #d1fae5;
  color: #065f46;
}

.stat-warning {
  background: #fef3c7;
  color: #92400e;
}

.stat-danger {
  background: #fee2e2;
  color: #991b1b;
}

.toolbar-card {
  background: var(--surface-light);
  border: 1px solid var(--border-light);
  border-radius: 1rem;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
  box-shadow: var(--shadow);
}

.toolbar-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.filter-group {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  flex-wrap: wrap;
}

.search-input, .filter-select {
  border: 1px solid var(--border-light);
  border-radius: 0.5rem;
  padding: 0.5rem 0.875rem;
  font-size: 0.875rem;
  min-width: 200px;
}

.search-input:focus, .filter-select:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  outline: none;
}

.btn-filter {
  padding: 0.5rem 1rem;
  border: 1px solid var(--border-light);
  background: white;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  transition: all 0.2s;
  cursor: pointer;
}

.btn-filter:hover {
  background: var(--bg-light);
}

.btn-filter.active {
  background: var(--accent);
  color: white;
  border-color: var(--accent);
}

.btn-primary-custom {
  background: linear-gradient(135deg, var(--accent) 0%, var(--accent-hover) 100%);
  color: white;
  border: none;
  padding: 0.625rem 1.25rem;
  border-radius: 0.75rem;
  font-weight: 600;
  font-size: 0.875rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
  cursor: pointer;
}

.btn-primary-custom:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(16, 185, 129, 0.4);
}

.alert-custom {
  border-radius: 0.75rem;
  border: none;
  padding: 1rem 1.25rem;
  margin-bottom: 1.5rem;
  box-shadow: var(--shadow);
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  color: #065f46;
}

.table-card {
  background: var(--surface-light);
  border: 1px solid var(--border-light);
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: var(--shadow-lg);
}

.table-modern {
  margin: 0;
  width: 100%;
}

.table-modern thead {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.table-modern thead th {
  border: none;
  padding: 1rem 1.25rem;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--muted-light);
}

.table-modern tbody tr {
  border-bottom: 1px solid var(--border-light);
  transition: all 0.15s ease;
}

.table-modern tbody tr:hover {
  background: linear-gradient(90deg, rgba(16, 185, 129, 0.03) 0%, rgba(16, 185, 129, 0.06) 100%);
}

.table-modern tbody td {
  padding: 1rem 1.25rem;
  vertical-align: middle;
  font-size: 0.875rem;
}

.medicine-name {
  font-weight: 600;
  color: var(--text-light);
}

.generic-name {
  font-size: 0.8125rem;
  color: var(--muted-light);
}

.category-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
  background: #e0e7ff;
  color: #3730a3;
  display: inline-block;
}

.stock-indicator {
  padding: 0.25rem 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
  display: inline-block;
}

.stock-normal {
  background: #d1fae5;
  color: #065f46;
}

.stock-low {
  background: #fef3c7;
  color: #92400e;
}

.stock-out {
  background: #fee2e2;
  color: #991b1b;
}

.expiry-warning {
  color: var(--danger);
  font-weight: 600;
}

.expiry-soon {
  color: var(--warning);
}

.btn-action {
  padding: 0.375rem 0.875rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
  transition: all 0.2s;
  border: 1px solid transparent;
  cursor: pointer;
}

.btn-edit-custom {
  background: white;
  color: var(--accent);
  border-color: #d1fae5;
}

.btn-edit-custom:hover {
  background: var(--accent);
  color: white;
  border-color: var(--accent);
  transform: translateY(-1px);
}

.btn-delete-custom {
  background: white;
  color: var(--danger);
  border-color: #fee2e2;
}

.btn-delete-custom:hover {
  background: var(--danger);
  color: white;
  border-color: var(--danger);
  transform: translateY(-1px);
}

.modal-content {
  border: none;
  border-radius: 1rem;
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
}

.modal-header {
  border-bottom: 1px solid var(--border-light);
  padding: 1.5rem;
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
}

.modal-title {
  font-weight: 700;
  font-size: 1.25rem;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  border-top: 1px solid var(--border-light);
  padding: 1rem 1.5rem;
  background: var(--bg-light);
}

.form-label {
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--text-light);
  margin-bottom: 0.5rem;
}

.form-control, .form-select {
  border: 1px solid var(--border-light);
  border-radius: 0.5rem;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.form-control:focus, .form-select:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  outline: none;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .toolbar-content {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filter-group {
    flex-direction: column;
  }
  
  .search-input, .filter-select {
    width: 100%;
  }
}
</style>

<div class="page-container">
  <div class="page-header">
    <h1 class="page-title">
      <span class="title-icon"><i class="bi bi-capsule"></i></span>
      Medicine Inventory
    </h1>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon stat-success">
        <i class="bi bi-box-seam"></i>
      </div>
      <div class="stat-label">Total Items</div>
      <div class="stat-value"><?php echo number_format($total_items); ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-warning">
        <i class="bi bi-exclamation-triangle"></i>
      </div>
      <div class="stat-label">Low Stock</div>
      <div class="stat-value"><?php echo number_format($low_stock_count); ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-danger">
        <i class="bi bi-calendar-x"></i>
      </div>
      <div class="stat-label">Expired</div>
      <div class="stat-value"><?php echo number_format($expired_count); ?></div>
    </div>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert-custom alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($_GET['msg']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="toolbar-card">
    <div class="toolbar-content">
      <div class="filter-group">
        <form method="get" style="display: contents;">
          <input type="text" class="search-input" name="q" placeholder="Search medicines..." value="<?php echo htmlspecialchars($q); ?>">
          <select name="category" class="filter-select">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn-filter">Filter</button>
        </form>
        <a href="?low_stock=1" class="btn-filter <?php echo $low_stock ? 'active' : ''; ?>">
          <i class="bi bi-exclamation-circle"></i> Low Stock Only
        </a>
      </div>
      <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-circle"></i> Add Medicine
      </button>
    </div>
  </div>

  <div class="table-card">
    <div class="table-responsive">
      <table class="table-modern">
        <thead>
          <tr>
            <th>ID</th>
            <th>Medicine Name</th>
            <th>Category</th>
            <th>Quantity</th>
            <th>Unit</th>
            <th>Expiry Date</th>
            <th>Supplier</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <?php
              $quantity = (int)$r['quantity'];
              $reorder = (int)$r['reorder_level'];
              $stock_class = 'stock-normal';
              if ($quantity == 0) $stock_class = 'stock-out';
              elseif ($quantity <= $reorder) $stock_class = 'stock-low';
              
              $expiry_class = '';
              if ($r['expiry_date']) {
                $expiry_time = strtotime($r['expiry_date']);
                $days_until = ($expiry_time - time()) / (60 * 60 * 24);
                if ($days_until < 0) $expiry_class = 'expiry-warning';
                elseif ($days_until < 90) $expiry_class = 'expiry-soon';
              }
            ?>
            <tr data-id="<?php echo (int)$r['id']; ?>">
              <td><?php echo (int)$r['id']; ?></td>
              <td>
                <div class="medicine-name"><?php echo htmlspecialchars($r['name']); ?></div>
                <?php if ($r['generic_name']): ?>
                  <div class="generic-name"><?php echo htmlspecialchars($r['generic_name']); ?></div>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($r['category']): ?>
                  <span class="category-badge"><?php echo htmlspecialchars($r['category']); ?></span>
                <?php endif; ?>
              </td>
              <td>
                <span class="stock-indicator <?php echo $stock_class; ?>">
                  <?php echo number_format($quantity); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($r['unit']); ?></td>
              <td class="<?php echo $expiry_class; ?>">
                <?php echo $r['expiry_date'] ? date('Y-m-d', strtotime($r['expiry_date'])) : '—'; ?>
              </td>
              <td><?php echo htmlspecialchars($r['supplier']); ?></td>
              <td>
                <button type="button" class="btn btn-action btn-edit-custom btn-edit" data-id="<?php echo (int)$r['id']; ?>" data-bs-toggle="modal" data-bs-target="#editModal">
                  <i class="bi bi-pencil"></i> Edit
                </button>
                <button type="button" class="btn btn-action btn-delete-custom btn-delete" data-id="<?php echo (int)$r['id']; ?>">
                  <i class="bi bi-trash"></i> Delete
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Medicine</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="">
        <input type="hidden" name="__action" value="create">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Medicine Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Generic Name</label>
              <input type="text" name="generic_name" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Category</label>
              <input type="text" name="category" class="form-control" placeholder="e.g., Antibiotic, Analgesic">
            </div>
            <div class="col-md-6">
              <label class="form-label">Unit</label>
              <input type="text" name="unit" class="form-control" placeholder="e.g., Tablet, Bottle, Box">
            </div>
            <div class="col-md-4">
              <label class="form-label">Quantity <span class="text-danger">*</span></label>
              <input type="number" name="quantity" class="form-control" required min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Reorder Level</label>
              <input type="number" name="reorder_level" class="form-control" min="0" value="10">
            </div>
            <div class="col-md-4">
              <label class="form-label">Expiry Date</label>
              <input type="date" name="expiry_date" class="form-control">
            </div>
            <div class="col-md-12">
              <label class="form-label">Supplier</label>
              <input type="text" name="supplier" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Notes</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Additional information..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Medicine</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Medicine</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="" id="editForm">
        <input type="hidden" name="__action" value="update">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Medicine Name <span class="text-danger">*</span></label>
              <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Generic Name</label>
              <input type="text" name="generic_name" id="edit_generic_name" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Category</label>
              <input type="text" name="category" id="edit_category" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Unit</label>
              <input type="text" name="unit" id="edit_unit" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Quantity <span class="text-danger">*</span></label>
              <input type="number" name="quantity" id="edit_quantity" class="form-control" required min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Reorder Level</label>
              <input type="number" name="reorder_level" id="edit_reorder_level" class="form-control" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Expiry Date</label>
              <input type="date" name="expiry_date" id="edit_expiry_date" class="form-control">
            </div>
            <div class="col-md-12">
              <label class="form-label">Supplier</label>
              <input type="text" name="supplier" id="edit_supplier" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Notes</label>
              <textarea name="notes" id="edit_notes" class="form-control" rows="2" placeholder="Additional information..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Medicine</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this medicine record? This action cannot be undone.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <form method="post" action="" id="deleteForm" style="display: inline;">
          <input type="hidden" name="delete" id="delete_id">
          <button type="submit" class="btn btn-danger">Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Edit Modal Handling
  const editModal = document.getElementById('editModal');
  const editForm = document.getElementById('editForm');
  
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      
      // Fetch medicine details via AJAX
      fetch(`?ajax=medicine&id=${id}`)
        .then(response => response.json())
        .then(data => {
          // Populate edit form
          document.getElementById('edit_id').value = data.id || '';
          document.getElementById('edit_name').value = data.name || '';
          document.getElementById('edit_generic_name').value = data.generic_name || '';
          document.getElementById('edit_category').value = data.category || '';
          document.getElementById('edit_unit').value = data.unit || '';
          document.getElementById('edit_quantity').value = data.quantity || 0;
          document.getElementById('edit_reorder_level').value = data.reorder_level || 0;
          document.getElementById('edit_expiry_date').value = data.expiry_date || '';
          document.getElementById('edit_supplier').value = data.supplier || '';
          document.getElementById('edit_notes').value = data.notes || '';
        })
        .catch(error => {
          console.error('Error fetching medicine details:', error);
          alert('Failed to load medicine details');
        });
    });
  });

  // Delete Modal Handling
  const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
  
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      document.getElementById('delete_id').value = id;
      deleteModal.show();
    });
  });

  // Auto-dismiss alerts after 5 seconds
  const alerts = document.querySelectorAll('.alert-custom');
  alerts.forEach(alert => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      bsAlert.close();
    }, 5000);
  });

  // Form validation feedback
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    form.addEventListener('submit', function(e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  });

  // Clear form when create modal is closed
  const createModal = document.getElementById('createModal');
  createModal.addEventListener('hidden.bs.modal', function() {
    const form = createModal.querySelector('form');
    form.reset();
    form.classList.remove('was-validated');
  });

  // Highlight expired and low stock items on page load
  highlightWarnings();
});

function highlightWarnings() {
  const rows = document.querySelectorAll('tbody tr');
  rows.forEach(row => {
    const expiryCell = row.querySelector('td:nth-child(6)');
    const stockCell = row.querySelector('.stock-indicator');
    
    // Add visual indicators
    if (expiryCell && expiryCell.classList.contains('expiry-warning')) {
      row.style.borderLeft = '4px solid #ef4444';
    } else if (expiryCell && expiryCell.classList.contains('expiry-soon')) {
      row.style.borderLeft = '4px solid #f59e0b';
    } else if (stockCell && (stockCell.classList.contains('stock-low') || stockCell.classList.contains('stock-out'))) {
      row.style.borderLeft = '4px solid #f59e0b';
    }
  });
}

// Optional: Add search functionality with debounce
let searchTimeout;
const searchInput = document.querySelector('.search-input');
if (searchInput) {
  searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      this.form.submit();
    }, 500);
  });
}
</script>

<?php include __DIR__ . "/../partials/footer.php"; ?>