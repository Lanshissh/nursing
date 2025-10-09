<?php
$page_title = "Check-ups";
require_once __DIR__ . "/../config.php";

$TABLE = 'checkups';

// Handle CSV Export
if (isset($_GET['export_csv'])) {
  $export_patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
  $date_from = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? $_GET['date_from'] : null;
  $date_to = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? $_GET['date_to'] : null;

  try {
    $sql = "
      SELECT 
        c.id,
        c.visit_date,
        p.last_name,
        p.first_name,
        c.complaint,
        c.diagnosis,
        c.treatment,
        c.notes
      FROM {$TABLE} c
      LEFT JOIN patients p ON p.id = c.patient_id
      WHERE 1=1
    ";
    $params = [];

    if ($export_patient_id > 0) {
      $sql .= " AND c.patient_id = :pid";
      $params[':pid'] = $export_patient_id;
    }

    if ($date_from) {
      $sql .= " AND DATE(c.visit_date) >= :date_from";
      $params[':date_from'] = $date_from;
    }

    if ($date_to) {
      $sql .= " AND DATE(c.visit_date) <= :date_to";
      $params[':date_to'] = $date_to;
    }

    $sql .= " ORDER BY c.visit_date DESC, c.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $export_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate filename
    $filename = "checkups_" . date('Y-m-d_His') . ".csv";
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Write CSV header
    fputcsv($output, ['ID', 'Visit Date', 'Last Name', 'First Name', 'Chief Complaint', 'Diagnosis', 'Treatment/Plan', 'Notes']);

    // Write data rows
    foreach ($export_rows as $row) {
      fputcsv($output, [
        $row['id'],
        $row['visit_date'] ?? '',
        $row['last_name'] ?? '',
        $row['first_name'] ?? '',
        $row['complaint'] ?? '',
        $row['diagnosis'] ?? '',
        $row['treatment'] ?? '',
        $row['notes'] ?? ''
      ]);
    }

    fclose($output);
    exit;
  } catch (PDOException $e) {
    die("Export failed: " . $e->getMessage());
  }
}

function redirect_with_msg($msg){
  $base = strtok($_SERVER['REQUEST_URI'], '?');
  header("Location: {$base}?msg=" . urlencode($msg));
  exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'checkup' && isset($_GET['id'])) {
  header('Content-Type: application/json');
  $id = (int)$_GET['id'];
  $stmt = $pdo->prepare("SELECT id, patient_id, visit_date, complaint, diagnosis, treatment, notes FROM {$TABLE} WHERE id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
  echo json_encode($row);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__action'] ?? '') === 'create') {
  $stmt = $pdo->prepare("INSERT INTO {$TABLE} (patient_id, visit_date, complaint, diagnosis, treatment, notes) VALUES (?,?,?,?,?,?)");
  $stmt->execute([
    (int)($_POST['patient_id'] ?? 0),
    $_POST['visit_date'] !== '' ? $_POST['visit_date'] : null,
    trim($_POST['complaint'] ?? ''),
    trim($_POST['diagnosis'] ?? ''),
    trim($_POST['treatment'] ?? ''),
    trim($_POST['notes'] ?? ''),
  ]);
  redirect_with_msg('Check-up created');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__action'] ?? '') === 'update') {
  $id = (int)($_POST['id'] ?? 0);
  $stmt = $pdo->prepare("UPDATE {$TABLE} SET patient_id=?, visit_date=?, complaint=?, diagnosis=?, treatment=?, notes=? WHERE id=?");
  $stmt->execute([
    (int)($_POST['patient_id'] ?? 0),
    $_POST['visit_date'] !== '' ? $_POST['visit_date'] : null,
    trim($_POST['complaint'] ?? ''),
    trim($_POST['diagnosis'] ?? ''),
    trim($_POST['treatment'] ?? ''),
    trim($_POST['notes'] ?? ''),
    $id
  ]);
  redirect_with_msg('Check-up updated');
}

$deleteId = null;
if (isset($_GET['delete'])) $deleteId = (int)$_GET['delete'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) $deleteId = (int)$_POST['delete'];
if ($deleteId) {
  $stmt = $pdo->prepare("DELETE FROM {$TABLE} WHERE id=?");
  $stmt->execute([$deleteId]);
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'msg' => 'Check-up deleted']);
    exit;
  }
  redirect_with_msg('Check-up deleted');
}

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$where = [];$params = [];
if ($patient_id) { $where[] = 'c.patient_id = ?'; $params[] = $patient_id; }
if ($q !== '') { $where[] = "(c.complaint LIKE ? OR c.diagnosis LIKE ? OR c.treatment LIKE ? OR c.notes LIKE ?)"; $like = "%$q%"; array_push($params,$like,$like,$like,$like); }

$sql = "SELECT c.id, c.patient_id, c.visit_date, c.complaint, c.diagnosis, c.treatment, c.notes,
               p.first_name, p.last_name
        FROM {$TABLE} c
        LEFT JOIN patients p ON p.id = c.patient_id";
if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY c.visit_date DESC, c.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all patients for the export modal
$patients = [];
try {
  $q = "SELECT id, last_name, first_name FROM patients ORDER BY last_name, first_name";
  $patients = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

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
  --accent: #8b5cf6;
  --accent-hover: #7c3aed;
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
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
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
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
  box-shadow: 0 2px 4px rgba(139, 92, 246, 0.3);
}

.btn-primary-custom:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(139, 92, 246, 0.4);
}

.alert-custom {
  border-radius: 0.75rem;
  border: none;
  padding: 1rem 1.25rem;
  margin-bottom: 1.5rem;
  box-shadow: var(--shadow);
}

.alert-success-custom {
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
  background: linear-gradient(90deg, rgba(139, 92, 246, 0.03) 0%, rgba(139, 92, 246, 0.06) 100%);
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
  border-color: #ede9fe;
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
  background: linear-gradient(135deg, #faf5ff 0%, #f5f3ff 100%);
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
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
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
    <h1 class="page-title">Check-ups</h1>
    <div class="header-actions">
      <form class="search-bar" method="get" action="">
        <?php if ($patient_id): ?>
          <input type="hidden" name="patient_id" value="<?php echo (int)$patient_id; ?>">
        <?php endif; ?>
        <input type="text" class="search-input" name="q" placeholder="Search check-ups..." value="<?php echo htmlspecialchars($q); ?>">
        <button type="submit" class="btn-search">Search</button>
      </form>
      <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#reportModal">
        <i class="bi bi-file-earmark-text"></i> Generate Report
      </button>
      <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-circle"></i> New Check-up
      </button>
    </div>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success-custom alert-dismissible fade show" role="alert">
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
            <th>Patient</th>
            <th>Visit Date</th>
            <th>Complaint</th>
            <th>Diagnosis</th>
            <th>Treatment</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr data-id="<?php echo (int)$r['id']; ?>">
              <td><?php echo (int)$r['id']; ?></td>
              <td class="patient-name"><?php echo htmlspecialchars(trim(($r['last_name'] ?? '') . ', ' . ($r['first_name'] ?? ''))) ?: ('#'.(int)$r['patient_id']); ?></td>
              <td><?php echo htmlspecialchars($r['visit_date'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r['complaint'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r['diagnosis'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r['treatment'] ?? ''); ?></td>
              <td>
                <button type="button" class="btn btn-action btn-edit-custom" data-id="<?php echo (int)$r['id']; ?>" data-bs-toggle="modal" data-bs-target="#editModal">
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

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="get" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Export Check-ups Report (CSV)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="export_csv" value="1">
        
        <div class="mb-3">
          <label for="report_patient_id" class="form-label">Patient</label>
          <select name="patient_id" id="report_patient_id" class="form-select">
            <option value="">All Patients</option>
            <?php foreach ($patients as $p): ?>
              <option value="<?php echo (int)$p['id']; ?>" <?php echo $patient_id == (int)$p['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($p['last_name'] . ', ' . $p['first_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label for="date_from" class="form-label">From Date</label>
          <input type="date" name="date_from" id="date_from" class="form-control" 
                 value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
        </div>

        <div class="mb-3">
          <label for="date_to" class="form-label">To Date</label>
          <input type="date" name="date_to" id="date_to" class="form-control" 
                 value="<?php echo date('Y-m-d'); ?>">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-file-earmark-spreadsheet"></i> Download CSV
        </button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">New Check-up</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="">
        <input type="hidden" name="__action" value="create">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Patient ID</label>
              <input type="number" name="patient_id" class="form-control" value="<?php echo (int)$patient_id; ?>" required>
            </div>
            <div class="col-md-8">
              <label class="form-label">Visit Date</label>
              <input type="date" name="visit_date" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Chief Complaint</label>
              <input type="text" name="complaint" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Diagnosis</label>
              <input type="text" name="diagnosis" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Treatment / Plan</label>
              <textarea name="treatment" class="form-control" rows="3"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Notes</label>
              <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Check-up</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Check-up</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="" id="editForm">
        <input type="hidden" name="__action" value="update">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Patient ID</label>
              <input type="number" name="patient_id" id="edit_patient_id" class="form-control" required>
            </div>
            <div class="col-md-8">
              <label class="form-label">Visit Date</label>
              <input type="date" name="visit_date" id="edit_visit_date" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Chief Complaint</label>
              <input type="text" name="complaint" id="edit_complaint" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Diagnosis</label>
              <input type="text" name="diagnosis" id="edit_diagnosis" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Treatment / Plan</label>
              <textarea name="treatment" id="edit_treatment" class="form-control" rows="3"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Notes</label>
              <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
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
    if (!confirm('Delete this check-up?')) return;
    try {
      const res = await fetch(window.location.pathname, {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({delete: id}).toString()
      });
      if (res.ok) {
        const tr = document.querySelector('tr[data-id="'+id+'"]');
        if (tr) tr.remove();
        toast('Check-up deleted');
      } else {
        location.href = window.location.pathname + '?msg=' + encodeURIComponent('Check-up deleted');
      }
    } catch(err){
      console.error(err);
      location.href = window.location.pathname + '?msg=' + encodeURIComponent('Check-up deleted');
    }
  });

  const editModal = document.getElementById('editModal');
  editModal.addEventListener('show.bs.modal', async function(e){
    const btn = e.relatedTarget; if (!btn) return;
    const id = btn.getAttribute('data-id');
    try {
      const res = await fetch(window.location.pathname + '?ajax=checkup&id=' + encodeURIComponent(id));
      const c = await res.json();
      document.getElementById('edit_id').value = c.id || '';
      document.getElementById('edit_patient_id').value = c.patient_id || '';
      document.getElementById('edit_visit_date').value = c.visit_date || '';
      document.getElementById('edit_complaint').value = c.complaint || '';
      document.getElementById('edit_diagnosis').value = c.diagnosis || '';
      document.getElementById('edit_treatment').value = c.treatment || '';
      document.getElementById('edit_notes').value = c.notes || '';
    } catch(err){ console.error(err); }
  });

  function toast(msg){
    const el = document.createElement('div');
    el.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3 shadow';
    el.style.zIndex = 1060;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(()=>{ el.classList.add('show'); }, 10);
    setTimeout(()=>{ el.remove(); }, 2500);
  }
})();
</script>

<?php include __DIR__ . "/../partials/footer.php"; ?>