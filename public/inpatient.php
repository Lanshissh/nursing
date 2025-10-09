<?php
$page_title = "In-Patient Admissions";
require_once __DIR__ . "/../config.php";

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
  try {
    $stmt = $pdo->prepare("
      INSERT INTO inpatient_admissions 
      (patient_id, admit_date, discharge_date, ward, bed, physician, diagnosis, treatment, notes) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
      (int)$_POST['patient_id'],
      $_POST['admit_date'] ?: null,
      !empty($_POST['discharge_date']) ? $_POST['discharge_date'] : null,
      trim($_POST['ward']),
      trim($_POST['bed']),
      trim($_POST['physician']),
      trim($_POST['diagnosis']),
      trim($_POST['treatment']),
      trim($_POST['notes'])
    ]);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?msg=" . urlencode("Admission created successfully"));
    exit;
  } catch (PDOException $e) {
    $error = "Failed to create admission: " . $e->getMessage();
  }
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
  try {
    $stmt = $pdo->prepare("
      UPDATE inpatient_admissions 
      SET patient_id=?, admit_date=?, discharge_date=?, ward=?, bed=?, physician=?, diagnosis=?, treatment=?, notes=?
      WHERE id=?
    ");
    $stmt->execute([
      (int)$_POST['patient_id'],
      $_POST['admit_date'] ?: null,
      !empty($_POST['discharge_date']) ? $_POST['discharge_date'] : null,
      trim($_POST['ward']),
      trim($_POST['bed']),
      trim($_POST['physician']),
      trim($_POST['diagnosis']),
      trim($_POST['treatment']),
      trim($_POST['notes']),
      (int)$_POST['id']
    ]);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?msg=" . urlencode("Admission updated successfully"));
    exit;
  } catch (PDOException $e) {
    $error = "Failed to update admission: " . $e->getMessage();
  }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
  try {
    $stmt = $pdo->prepare("DELETE FROM inpatient_admissions WHERE id = ?");
    $stmt->execute([(int)$_POST['delete']]);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?msg=" . urlencode("Admission deleted successfully"));
    exit;
  } catch (PDOException $e) {
    $error = "Failed to delete admission: " . $e->getMessage();
  }
}

// Handle CSV Export
if (isset($_GET['export_csv'])) {
  $export_patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
  $date_from = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? $_GET['date_from'] : null;
  $date_to = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? $_GET['date_to'] : null;
  $status = isset($_GET['status']) ? trim($_GET['status']) : '';

  try {
    $sql = "
      SELECT 
        ia.id,
        ia.admit_date,
        ia.discharge_date,
        p.last_name,
        p.first_name,
        ia.ward,
        ia.bed,
        ia.physician,
        ia.diagnosis,
        ia.treatment,
        ia.notes
      FROM inpatient_admissions ia
      JOIN patients p ON p.id = ia.patient_id
      WHERE 1=1
    ";
    $params = [];

    if ($export_patient_id > 0) {
      $sql .= " AND ia.patient_id = :pid";
      $params[':pid'] = $export_patient_id;
    }

    if ($date_from) {
      $sql .= " AND DATE(ia.admit_date) >= :date_from";
      $params[':date_from'] = $date_from;
    }

    if ($date_to) {
      $sql .= " AND DATE(ia.admit_date) <= :date_to";
      $params[':date_to'] = $date_to;
    }

    if ($status === 'active') {
      $sql .= " AND ia.discharge_date IS NULL";
    } elseif ($status === 'discharged') {
      $sql .= " AND ia.discharge_date IS NOT NULL";
    }

    $sql .= " ORDER BY ia.admit_date DESC, ia.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $export_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate filename
    $filename = "inpatient_admissions_" . date('Y-m-d_His') . ".csv";
    
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
    fputcsv($output, ['ID', 'Admit Date', 'Discharge Date', 'Last Name', 'First Name', 'Ward', 'Bed', 'Physician', 'Diagnosis', 'Treatment', 'Notes', 'Status']);

    // Write data rows
    foreach ($export_rows as $row) {
      $status = $row['discharge_date'] ? 'Discharged' : 'Active';
      fputcsv($output, [
        $row['id'],
        $row['admit_date'] ?? '',
        $row['discharge_date'] ?? '',
        $row['last_name'] ?? '',
        $row['first_name'] ?? '',
        $row['ward'] ?? '',
        $row['bed'] ?? '',
        $row['physician'] ?? '',
        $row['diagnosis'] ?? '',
        $row['treatment'] ?? '',
        $row['notes'] ?? '',
        $status
      ]);
    }

    fclose($output);
    exit;
  } catch (PDOException $e) {
    die("Export failed: " . $e->getMessage());
  }
}

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$patient = null;
if ($patient_id) {
  $s = $pdo->prepare("SELECT * FROM patients WHERE id=?");
  $s->execute([$patient_id]);
  $patient = $s->fetch();
}

$msg = isset($_GET['msg']) ? trim($_GET['msg']) : '';
$error = $error ?? '';

$stmt = $pdo->query("SELECT ia.*, p.last_name, p.first_name FROM inpatient_admissions ia JOIN patients p ON p.id = ia.patient_id ORDER BY ia.admit_date DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all patients for the export modal
$patients = [];
try {
  $q = "SELECT id, last_name, first_name FROM patients ORDER BY last_name, first_name";
  $patients = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<?php include __DIR__ . "/../partials/header.php"; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root {
  --bg-light: #f8fafc;
  --surface-light: #ffffff;
  --border-light: #e2e8f0;
  --text-light: #0f172a;
  --muted-light: #64748b;
  --accent: #06b6d4;
  --accent-hover: #0891b2;
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
  box-shadow: 0 2px 4px rgba(6, 182, 212, 0.3);
  text-decoration: none;
  cursor: pointer;
}

.btn-primary-custom:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(6, 182, 212, 0.4);
  color: white;
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
  background: white;
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
  background: linear-gradient(90deg, rgba(6, 182, 212, 0.03) 0%, rgba(6, 182, 212, 0.06) 100%);
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

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  display: inline-block;
}

.status-active {
  background: #d1fae5;
  color: #065f46;
}

.status-discharged {
  background: #e0e7ff;
  color: #3730a3;
}

.btn-action {
  padding: 0.375rem 0.875rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
  transition: all 0.2s;
  border: 1px solid transparent;
  text-decoration: none;
  display: inline-block;
}

.btn-view {
  background: white;
  color: var(--accent);
  border-color: #cffafe;
}

.btn-view:hover {
  background: var(--accent);
  color: white;
  border-color: var(--accent);
  transform: translateY(-1px);
  text-decoration: none;
}

.btn-edit {
  background: white;
  color: #8b5cf6;
  border-color: #ede9fe;
}

.btn-edit:hover {
  background: #8b5cf6;
  color: white;
  border-color: #8b5cf6;
  transform: translateY(-1px);
  text-decoration: none;
}

.btn-delete {
  background: white;
  color: var(--danger);
  border-color: #fee2e2;
}

.btn-delete:hover {
  background: var(--danger);
  color: white;
  border-color: var(--danger);
  transform: translateY(-1px);
  text-decoration: none;
}

.modal-content {
  border: none;
  border-radius: 1rem;
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
}

.modal-header {
  border-bottom: 1px solid var(--border-light);
  padding: 1.5rem;
  background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
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
  box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
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
}
</style>

<div class="page-container">
  <div class="page-header">
    <h1 class="page-title">In-Patient Admissions</h1>
    <div class="header-actions">
      <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#reportModal">
        <i class="bi bi-file-earmark-text"></i> Generate Report
      </button>
      <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#admissionModal" onclick="resetAdmissionForm()">
        <i class="bi bi-plus-circle"></i> New Admission
      </button>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="alert-custom alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($msg); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert-custom" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #991b1b;">
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endif; ?>

  <div class="table-card">
    <div class="table-responsive">
      <table class="table-modern">
        <thead>
          <tr>
            <th>Admit Date</th>
            <th>Patient</th>
            <th>Ward/Bed</th>
            <th>Physician</th>
            <th>Diagnosis</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($r['admit_date']))); ?></td>
              <td class="patient-name"><?php echo htmlspecialchars($r['last_name'] . ', ' . $r['first_name']); ?></td>
              <td><?php echo htmlspecialchars(trim(($r['ward']?:'')." ".$r['bed'])); ?></td>
              <td><?php echo htmlspecialchars($r['physician']); ?></td>
              <td><?php echo htmlspecialchars(mb_strimwidth($r['diagnosis'], 0, 40, '…')); ?></td>
              <td>
                <?php if ($r['discharge_date']): ?>
                  <span class="status-badge status-discharged">Discharged</span>
                <?php else: ?>
                  <span class="status-badge status-active">Active</span>
                <?php endif; ?>
              </td>
              <td class="text-nowrap">
                <a class="btn-action btn-view" href="/public/inpatient/view.php?id=<?php echo (int)$r['id']; ?>">
                  <i class="bi bi-eye"></i> View
                </a>
                <button type="button" class="btn-action btn-edit" onclick="editAdmission(<?php echo (int)$r['id']; ?>)" data-bs-toggle="modal" data-bs-target="#admissionModal">
                  <i class="bi bi-pencil"></i> Edit
                </button>
                <button type="button" class="btn-action btn-delete js-delete" data-id="<?php echo (int)$r['id']; ?>">
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

<!-- Admission Modal (Create/Edit) -->
<div class="modal fade" id="admissionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="admissionModalTitle">New Admission</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" id="admissionForm">
        <input type="hidden" name="action" id="formAction" value="create">
        <input type="hidden" name="id" id="admissionId">
        
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
              <select name="patient_id" id="patient_id" class="form-select" required>
                <option value="">Select Patient</option>
                <?php foreach ($patients as $p): ?>
                  <option value="<?php echo (int)$p['id']; ?>">
                    <?php echo htmlspecialchars($p['last_name'] . ', ' . $p['first_name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="col-md-6">
              <label for="admit_date" class="form-label">Admit Date <span class="text-danger">*</span></label>
              <input type="date" name="admit_date" id="admit_date" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label for="discharge_date" class="form-label">Discharge Date</label>
              <input type="date" name="discharge_date" id="discharge_date" class="form-control">
            </div>

            <div class="col-md-3">
              <label for="ward" class="form-label">Ward</label>
              <input type="text" name="ward" id="ward" class="form-control">
            </div>

            <div class="col-md-3">
              <label for="bed" class="form-label">Bed</label>
              <input type="text" name="bed" id="bed" class="form-control">
            </div>

            <div class="col-12">
              <label for="physician" class="form-label">Physician</label>
              <input type="text" name="physician" id="physician" class="form-control">
            </div>

            <div class="col-12">
              <label for="diagnosis" class="form-label">Diagnosis</label>
              <textarea name="diagnosis" id="diagnosis" class="form-control" rows="2"></textarea>
            </div>

            <div class="col-12">
              <label for="treatment" class="form-label">Treatment Plan</label>
              <textarea name="treatment" id="treatment" class="form-control" rows="3"></textarea>
            </div>

            <div class="col-12">
              <label for="notes" class="form-label">Notes</label>
              <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Admission</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="get" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Export Admissions Report (CSV)</h5>
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
          <label for="report_status" class="form-label">Status</label>
          <select name="status" id="report_status" class="form-select">
            <option value="">All Statuses</option>
            <option value="active">Active Only</option>
            <option value="discharged">Discharged Only</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="date_from" class="form-label">Admit Date From</label>
          <input type="date" name="date_from" id="date_from" class="form-control" 
                 value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
        </div>

        <div class="mb-3">
          <label for="date_to" class="form-label">Admit Date To</label>
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

  </div>
</div>

<div class="modal fade" id="modalCrud" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCrudTitle">Loading…</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalCrudBody">
        <div class="text-center p-5">Loading form…</div>
      </div>
    </div>
  </div>
</div>

<script>
// Store all admissions data for editing
const admissionsData = <?php echo json_encode($rows); ?>;

function resetAdmissionForm() {
  document.getElementById('admissionModalTitle').textContent = 'New Admission';
  document.getElementById('formAction').value = 'create';
  document.getElementById('admissionForm').reset();
  document.getElementById('admissionId').value = '';
  document.getElementById('admit_date').value = new Date().toISOString().split('T')[0];
}

function editAdmission(id) {
  const admission = admissionsData.find(a => a.id == id);
  if (!admission) return;

  document.getElementById('admissionModalTitle').textContent = 'Edit Admission #' + id;
  document.getElementById('formAction').value = 'update';
  document.getElementById('admissionId').value = admission.id;
  document.getElementById('patient_id').value = admission.patient_id;
  document.getElementById('admit_date').value = admission.admit_date || '';
  document.getElementById('discharge_date').value = admission.discharge_date || '';
  document.getElementById('ward').value = admission.ward || '';
  document.getElementById('bed').value = admission.bed || '';
  document.getElementById('physician').value = admission.physician || '';
  document.getElementById('diagnosis').value = admission.diagnosis || '';
  document.getElementById('treatment').value = admission.treatment || '';
  document.getElementById('notes').value = admission.notes || '';
}

// Delete handler
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.js-delete');
  if (!btn) return;
  e.preventDefault();
  
  const id = btn.getAttribute('data-id');
  if (!confirm('Delete this admission?')) return;
  
  const form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = '<input type="hidden" name="delete" value="' + id + '">';
  document.body.appendChild(form);
  form.submit();
});
})();
</script>

<?php include __DIR__ . "/../partials/footer.php"; ?>