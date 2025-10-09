<?php
$page_title = "BP Monitoring";
require_once __DIR__ . "/../config.php";

// Handle CSV Export
if (isset($_GET['export_csv'])) {
  $export_patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
  $date_from = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? $_GET['date_from'] : null;
  $date_to = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? $_GET['date_to'] : null;

  try {
    $sql = "
      SELECT 
        COALESCE(r.reading_time, r.created_at) as reading_datetime,
        p.last_name,
        p.first_name,
        r.systolic,
        r.diastolic,
        r.pulse,
        r.notes
      FROM bp_readings r
      JOIN patients p ON p.id = r.patient_id
      WHERE 1=1
    ";
    $params = [];

    if ($export_patient_id > 0) {
      $sql .= " AND r.patient_id = :pid";
      $params[':pid'] = $export_patient_id;
    }

    if ($date_from) {
      $sql .= " AND DATE(COALESCE(r.reading_time, r.created_at)) >= :date_from";
      $params[':date_from'] = $date_from;
    }

    if ($date_to) {
      $sql .= " AND DATE(COALESCE(r.reading_time, r.created_at)) <= :date_to";
      $params[':date_to'] = $date_to;
    }

    $sql .= " ORDER BY COALESCE(r.reading_time, r.created_at) DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $export_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate filename
    $filename = "bp_readings_" . date('Y-m-d_His') . ".csv";
    
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
    fputcsv($output, ['Date & Time', 'Last Name', 'First Name', 'Systolic (mmHg)', 'Diastolic (mmHg)', 'Pulse (bpm)', 'Notes']);

    // Write data rows
    foreach ($export_rows as $row) {
      fputcsv($output, [
        $row['reading_datetime'] ? date('Y-m-d H:i:s', strtotime($row['reading_datetime'])) : '',
        $row['last_name'],
        $row['first_name'],
        $row['systolic'],
        $row['diastolic'],
        $row['pulse'] ?? '',
        $row['notes'] ?? ''
      ]);
    }

    fclose($output);
    exit;
  } catch (PDOException $e) {
    die("Export failed: " . $e->getMessage());
  }
}

if (!isset($TABLE) || !$TABLE) { $TABLE = "bp_readings"; }
if (!isset($total)) {
  try {
    if (!empty($patient_id)) {
      $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM {$TABLE} WHERE patient_id = :pid");
      $stmtCount->execute([':pid' => $patient_id]);
      $total = (int)$stmtCount->fetchColumn();
    } else {
      $total = (int)$pdo->query("SELECT COUNT(*) FROM {$TABLE}")->fetchColumn();
    }
  } catch (Throwable $e) { $total = 0; }
}

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$patient = null;
if ($patient_id) {
  $s = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
  $s->execute([$patient_id]);
  $patient = $s->fetch();
}

$errors = [];
$flash_success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_bp'])) {
  $pid       = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
  $systolic  = isset($_POST['systolic']) ? (int)$_POST['systolic'] : null;
  $diastolic = isset($_POST['diastolic']) ? (int)$_POST['diastolic'] : null;
  $pulse     = isset($_POST['pulse']) && $_POST['pulse'] !== '' ? (int)$_POST['pulse'] : null;
  $notes     = isset($_POST['notes']) && $_POST['notes'] !== '' ? trim($_POST['notes']) : null;
  $reading   = isset($_POST['reading_time']) && $_POST['reading_time'] !== '' ? $_POST['reading_time'] : null;

  if ($pid <= 0)            $errors[] = "Patient is required.";
  if ($systolic === null)   $errors[] = "Systolic is required.";
  if ($diastolic === null)  $errors[] = "Diastolic is required.";

  $reading_dt = $reading ? str_replace('T', ' ', $reading) : date('Y-m-d H:i:s');
  if ($reading && strlen($reading_dt) === 16) {
    $reading_dt .= ":00";
  }

  if (!$errors) {
    try {
      $inserted = false;
      try {
        $stmt = $pdo->prepare("
          INSERT INTO bp_readings (patient_id, reading_time, systolic, diastolic, pulse, notes)
          VALUES (:pid, :rt, :sys, :dia, :pulse, :notes)
        ");
        $stmt->execute([
          ':pid'   => $pid,
          ':rt'    => $reading_dt,
          ':sys'   => $systolic,
          ':dia'   => $diastolic,
          ':pulse' => $pulse,
          ':notes' => $notes
        ]);
        $inserted = true;
      } catch (PDOException $e) {
        $stmt = $pdo->prepare("
          INSERT INTO bp_readings (patient_id, created_at, systolic, diastolic, pulse, notes)
          VALUES (:pid, :rt, :sys, :dia, :pulse, :notes)
        ");
        $stmt->execute([
          ':pid'   => $pid,
          ':rt'    => $reading_dt,
          ':sys'   => $systolic,
          ':dia'   => $diastolic,
          ':pulse' => $pulse,
          ':notes' => $notes
        ]);
        $inserted = true;
      }

      if ($inserted) {
        $flash_success = "BP reading added successfully.";
      }
    } catch (PDOException $e) {
      $errors[] = "Insert failed: " . $e->getMessage();
    }
  }
}

if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  try {
    $stmt = $pdo->prepare("DELETE FROM bp_readings WHERE id = ?");
    $stmt->execute([$id]);
    $base = strtok($_SERVER["REQUEST_URI"], '?');
    $qs   = [];
    if ($patient_id) $qs['patient_id'] = $patient_id;
    $to   = $base . (count($qs) ? ('?' . http_build_query($qs)) : '');
    header("Location: " . $to);
    exit;
  } catch (PDOException $e) {
    $errors[] = "Delete failed: " . $e->getMessage();
  }
}

$patients = [];
try {
  $q = "SELECT id, last_name, first_name FROM patients ORDER BY last_name, first_name";
  $patients = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

try {
  if ($patient_id) {
    $sql = "
      SELECT r.*, p.last_name, p.first_name
      FROM bp_readings r
      JOIN patients p ON p.id = r.patient_id
      WHERE r.patient_id = ?
      ORDER BY COALESCE(r.reading_time, r.created_at, r.id) DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$patient_id]);
  } else {
    $sql = "
      SELECT r.*, p.last_name, p.first_name
      FROM bp_readings r
      JOIN patients p ON p.id = r.patient_id
      ORDER BY COALESCE(r.reading_time, r.created_at, r.id) DESC
    ";
    $stmt = $pdo->query($sql);
  }
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $rows = [];
}

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
  --accent: #3b82f6;
  --accent-hover: #2563eb;
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
  margin-bottom: 2rem;
}

.page-title {
  font-size: 1.875rem;
  font-weight: 700;
  color: var(--text-light);
  margin: 0;
}

.toolbar-card {
  background: var(--surface-light);
  border: 1px solid var(--border-light);
  border-radius: 1rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: var(--shadow);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.toolbar-title {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
}

.stat-badge {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 2rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.toolbar-right {
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
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
}

.btn-primary-custom:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(59, 130, 246, 0.4);
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

.alert-danger-custom {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #991b1b;
}

.filter-section {
  background: var(--surface-light);
  border: 1px solid var(--border-light);
  border-radius: 0.75rem;
  padding: 1rem;
  margin-bottom: 1.5rem;
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
  position: sticky;
  top: 0;
  z-index: 10;
}

.table-modern tbody tr {
  border-bottom: 1px solid var(--border-light);
  transition: all 0.15s ease;
}

.table-modern tbody tr:hover {
  background: linear-gradient(90deg, rgba(59, 130, 246, 0.03) 0%, rgba(59, 130, 246, 0.06) 100%);
}

.table-modern tbody td {
  padding: 1rem 1.25rem;
  vertical-align: middle;
  font-size: 0.875rem;
}

.bp-value {
  font-weight: 700;
  font-size: 1rem;
  color: var(--text-light);
}

.btn-action {
  padding: 0.375rem 0.875rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
  transition: all 0.2s;
  border: 1px solid transparent;
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
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  outline: none;
}

.text-danger {
  color: var(--danger);
}

@media (max-width: 768px) {
  .toolbar-card {
    flex-direction: column;
    align-items: stretch;
  }
  
  .toolbar-right {
    justify-content: stretch;
    flex-direction: column;
  }
}
</style>

<div class="page-container">
  <div class="page-header">
    <h1 class="page-title">
      <?php if ($patient): ?>
        BP Monitoring — <?php echo htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']); ?>
      <?php else: ?>
        BP Monitoring
      <?php endif; ?>
    </h1>
  </div>

  <div class="toolbar-card">
    <div class="toolbar-left">
      <h3 class="toolbar-title">Blood Pressure Readings</h3>
      <span class="stat-badge">Total: <?php echo (int)$total; ?></span>
    </div>
    <div class="toolbar-right">
      <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#reportModal">
        <i class="bi bi-file-earmark-text"></i> Generate Report
      </button>
      <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-circle"></i> New Reading
      </button>
    </div>
  </div>

  <?php if (!empty($flash_success)): ?>
    <div class="alert alert-success-custom alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($flash_success); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger-custom" role="alert">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?php echo htmlspecialchars($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!$patient_id && count($patients) > 0): ?>
    <div class="filter-section">
      <form class="row g-2" method="get">
        <div class="col-auto">
          <select name="patient_id" class="form-select">
            <option value="">All patients</option>
            <?php foreach ($patients as $p): ?>
              <option value="<?php echo (int)$p['id']; ?>" <?php echo $patient_id == (int)$p['id'] ? 'selected' : '' ?>>
                <?php echo htmlspecialchars($p['last_name'] . ', ' . $p['first_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-auto">
          <button class="btn btn-outline-secondary">Filter</button>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <div class="table-card">
    <div class="table-responsive">
      <table class="table table-modern">
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Patient</th>
            <th>Blood Pressure</th>
            <th>Pulse (BPM)</th>
            <th>Notes</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <?php
            $dt = $r['reading_time'] ?? ($r['created_at'] ?? null);
            $when = $dt ? date('Y-m-d H:i', strtotime($dt)) : '';
          ?>
          <tr>
            <td><?php echo htmlspecialchars($when); ?></td>
            <td><?php echo htmlspecialchars($r['last_name'] . ', ' . $r['first_name']); ?></td>
            <td>
              <span class="bp-value"><?php echo (int)$r['systolic']; ?></span>
              <span class="text-muted">/</span>
              <span class="bp-value"><?php echo (int)$r['diastolic']; ?></span>
              <span class="text-muted ms-1">mmHg</span>
            </td>
            <td><?php echo $r['pulse'] !== null && $r['pulse'] !== '' ? (int)$r['pulse'] : '—'; ?></td>
            <td><?php echo htmlspecialchars(mb_strimwidth((string)($r['notes'] ?? ''), 0, 40, '…')); ?></td>
            <td>
              <a class="btn btn-action btn-delete-custom"
                 href="?<?php echo http_build_query(array_merge($_GET, ['delete' => (int)$r['id']])); ?>"
                 onclick="return confirm('Delete this reading?');">
                Delete
              </a>
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
        <h5 class="modal-title">Export BP Report (CSV)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="export_csv" value="1">
        
        <?php if ($patient_id): ?>
          <input type="hidden" name="patient_id" value="<?php echo (int)$patient_id; ?>">
          <div class="mb-3">
            <label class="form-label">Patient</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']); ?>" disabled>
          </div>
        <?php else: ?>
          <div class="mb-3">
            <label for="report_patient_id" class="form-label">Patient</label>
            <select name="patient_id" id="report_patient_id" class="form-select">
              <option value="">All Patients</option>
              <?php foreach ($patients as $p): ?>
                <option value="<?php echo (int)$p['id']; ?>">
                  <?php echo htmlspecialchars($p['last_name'] . ', ' . $p['first_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

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

<div class="modal fade" id="createModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">New BP Reading</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="create_bp" value="1">

        <?php if ($patient_id): ?>
          <input type="hidden" name="patient_id" value="<?php echo (int)$patient_id; ?>">
          <div class="mb-3">
            <label class="form-label">Patient</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']); ?>" disabled>
          </div>
        <?php else: ?>
          <div class="mb-3">
            <label for="patient_id_input" class="form-label">Patient <span class="text-danger">*</span></label>
            <select name="patient_id" id="patient_id_input" class="form-select" required>
              <option value="" selected disabled>Select patient</option>
              <?php foreach ($patients as $p): ?>
                <option value="<?php echo (int)$p['id']; ?>">
                  <?php echo htmlspecialchars($p['last_name'] . ', ' . $p['first_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <div class="mb-3">
          <label for="reading_time" class="form-label">Date & Time</label>
          <input type="datetime-local" name="reading_time" id="reading_time" class="form-control"
                 value="<?php echo htmlspecialchars(date('Y-m-d\TH:i')); ?>">
        </div>

        <div class="row g-3">
          <div class="col-6">
            <label for="systolic" class="form-label">Systolic (mmHg) <span class="text-danger">*</span></label>
            <input type="number" name="systolic" id="systolic" class="form-control" required min="50" max="300">
          </div>
          <div class="col-6">
            <label for="diastolic" class="form-label">Diastolic (mmHg) <span class="text-danger">*</span></label>
            <input type="number" name="diastolic" id="diastolic" class="form-control" required min="30" max="200">
          </div>
        </div>

        <div class="mt-3">
          <label for="pulse" class="form-label">Pulse (bpm)</label>
          <input type="number" name="pulse" id="pulse" class="form-control" min="20" max="250">
        </div>

        <div class="mt-3">
          <label for="notes" class="form-label">Notes</label>
          <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Optional notes..."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Reading</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . "/../partials/footer.php"; ?>