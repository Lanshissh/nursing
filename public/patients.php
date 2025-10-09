<?php
$page_title = "Patients";
require_once __DIR__ . "/../config.php";

if (isset($_GET['ajax']) && $_GET['ajax'] === 'patient' && isset($_GET['id'])) {
  header('Content-Type: application/json');
  $id = (int)$_GET['id'];
  $stmt = $pdo->prepare("SELECT id, first_name, last_name, sex, birthdate, contact, address FROM patients WHERE id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  echo json_encode($row ?: []);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__action']) && $_POST['__action'] === 'create') {
  $stmt = $pdo->prepare("INSERT INTO patients (first_name, last_name, sex, birthdate, contact, address) VALUES (?,?,?,?,?,?)");
  $stmt->execute([
    trim($_POST['first_name'] ?? ''),
    trim($_POST['last_name'] ?? ''),
    $_POST['sex'] !== '' ? $_POST['sex'] : null,
    $_POST['birthdate'] !== '' ? $_POST['birthdate'] : null,
    trim($_POST['contact'] ?? ''),
    trim($_POST['address'] ?? '')
  ]);
  header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?msg=" . urlencode('Patient created'));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__action']) && $_POST['__action'] === 'update') {
  $id = (int)($_POST['id'] ?? 0);
  $stmt = $pdo->prepare("UPDATE patients SET first_name=?, last_name=?, sex=?, birthdate=?, contact=?, address=? WHERE id=?");
  $stmt->execute([
    trim($_POST['first_name'] ?? ''),
    trim($_POST['last_name'] ?? ''),
    $_POST['sex'] !== '' ? $_POST['sex'] : null,
    $_POST['birthdate'] !== '' ? $_POST['birthdate'] : null,
    trim($_POST['contact'] ?? ''),
    trim($_POST['address'] ?? ''),
    $id
  ]);
  header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?msg=" . urlencode('Patient updated'));
  exit;
}

$deleteId = null;
if (isset($_GET['delete'])) $deleteId = (int)$_GET['delete'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) $deleteId = (int)$_POST['delete'];
if ($deleteId) {
  $stmt = $pdo->prepare("DELETE FROM patients WHERE id=?");
  $stmt->execute([$deleteId]);
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'msg' => 'Patient deleted']);
    exit;
  }
  header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?msg=" . urlencode('Patient deleted'));
  exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$params = [];
$sql = "SELECT id, first_name, last_name, sex, birthdate, contact, address FROM patients";
if ($q !== '') {
  $sql .= " WHERE (first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name,' ',last_name) LIKE ? OR contact LIKE ? OR address LIKE ?)";
  $like = "%$q%";
  $params = [$like, $like, $like, $like, $like];
}
$sql .= " ORDER BY last_name, first_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . "/../partials/header.php";
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root {
  --bg-light: #f8fafc;
  --surface-light: #ffffff;
  --border-light: #e2e8f0;
  --text-light: #0f172a;
  --muted-light: #64748b;
  --accent: #ec4899;
  --accent-hover: #db2777;
  --success: #10b981;
  --danger: #ef4444;
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
}

.header-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.search-bar {
  display: flex;
  gap: 0.5rem;
}

.search-input {
  min-width: 250px;
  border: 1px solid var(--border-light);
  border-radius: 0.5rem;
  padding: 0.5rem 0.875rem;
  font-size: 0.875rem;
}

.search-input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
  outline: none;
}

.btn-search {
  padding: 0.5rem 1rem;
  border: 1px solid var(--border-light);
  background: white;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  transition: all 0.2s;
}

.btn-search:hover {
  background: var(--bg-light);
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
  box-shadow: 0 2px 4px rgba(236, 72, 153, 0.3);
  cursor: pointer;
}

.btn-primary-custom:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(236, 72, 153, 0.4);
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
  background: linear-gradient(90deg, rgba(236, 72, 153, 0.03) 0%, rgba(236, 72, 153, 0.06) 100%);
}

.table-modern tbody td {
  padding: 1rem 1.25rem;
  vertical-align: middle;
  font-size: 0.875rem;
}

.patient-name {
  font-weight: 600;
  color: var(--text-light);
}

.gender-badge {
  padding: 0.25rem 0.625rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
  display: inline-block;
}

.gender-male {
  background: #dbeafe;
  color: #1e40af;
}

.gender-female {
  background: #fce7f3;
  color: #9f1239;
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
  border-color: #fce7f3;
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
  background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
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
  box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
  outline: none;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .header-actions {
    flex-direction: column;
  }
  
  .search-input {
    width: 100%;
  }
}
</style>

<div class="page-container">
  <div class="page-header">
    <h1 class="page-title">Patients</h1>
    <div class="header-actions">
      <form class="search-bar" method="get" action="">
        <input type="text" class="search-input" name="q" placeholder="Search patients..." value="<?php echo htmlspecialchars($q); ?>">
        <button type="submit" class="btn-search">Search</button>
      </form>
      <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-circle"></i> New Patient
      </button>
    </div>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert-custom alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($_GET['msg']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="table-card">
    <div class="table-responsive">
      <table class="table-modern">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Gender</th>
            <th>Birthdate</th>
            <th>Contact</th>
            <th>Address</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr data-id="<?php echo (int)$r['id']; ?>">
            <td><?php echo (int)$r['id']; ?></td>
            <td class="patient-name"><?php echo htmlspecialchars(trim(($r['last_name'] ?? '') . ', ' . ($r['first_name'] ?? ''))); ?></td>
            <td>
              <?php if ($r['sex']): ?>
                <span class="gender-badge <?php echo strtolower($r['sex']) === 'male' ? 'gender-male' : 'gender-female'; ?>">
                  <?php echo htmlspecialchars($r['sex']); ?>
                </span>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($r['birthdate'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['contact'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['address'] ?? ''); ?></td>
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

<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">New Patient</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="">
        <input type="hidden" name="__action" value="create">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Gender</label>
              <select name="sex" class="form-select">
                <option value="">--</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Birthdate</label>
              <input type="date" name="birthdate" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Contact</label>
              <input type="text" name="contact" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Address</label>
              <textarea name="address" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Patient</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Patient</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="" id="editForm">
        <input type="hidden" name="__action" value="update">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Gender</label>
              <select name="sex" id="edit_sex" class="form-select">
                <option value="">--</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Birthdate</label>
              <input type="date" name="birthdate" id="edit_birthdate" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Contact</label>
              <input type="text" name="contact" id="edit_contact" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Address</label>
              <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function(){
  document.addEventListener('click', async function(e){
    const btn = e.target.closest('.btn-delete');
    if (!btn) return;
    const id = btn.getAttribute('data-id');
    const ok = confirm('Delete this patient? This will also remove related records.');
    if (!ok) return;
    try {
      const res = await fetch(window.location.pathname, {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({delete: id}).toString()
      });
      if (res.ok) {
        const tr = document.querySelector('tr[data-id="'+id+'"]');
        if (tr) tr.remove();
        showToast('Patient deleted successfully');
      } else {
        location.href = window.location.pathname + '?msg=' + encodeURIComponent('Patient deleted');
      }
    } catch(err){
      console.error(err);
      location.href = window.location.pathname + '?msg=' + encodeURIComponent('Patient deleted');
    }
  });

  const editModal = document.getElementById('editModal');
  editModal.addEventListener('show.bs.modal', async function(e){
    const btn = e.relatedTarget;
    if (!btn) return;
    const id = btn.getAttribute('data-id');
    try {
      const res = await fetch(window.location.pathname + '?ajax=patient&id=' + encodeURIComponent(id));
      const p = await res.json();
      document.getElementById('edit_id').value = p.id || '';
      document.getElementById('edit_first_name').value = p.first_name || '';
      document.getElementById('edit_last_name').value = p.last_name || '';
      document.getElementById('edit_sex').value = p.sex || '';
      document.getElementById('edit_birthdate').value = p.birthdate || '';
      document.getElementById('edit_contact').value = p.contact || '';
      document.getElementById('edit_address').value = p.address || '';
    } catch(err) {
      console.error(err);
    }
  });

  function showToast(msg){
    const el = document.createElement('div');
    el.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3 shadow';
    el.style.cssText = 'z-index: 1060; border-radius: 0.75rem; border: none; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);';
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(()=>{ el.classList.add('show'); }, 10);
    setTimeout(()=>{ el.remove(); }, 2500);
  }
})();
</script>

<?php include __DIR__ . "/../partials/footer.php"; ?>