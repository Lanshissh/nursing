<?php
$page_title = "In-Patient Admissions";
require_once __DIR__ . "/../config.php";

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$patient = null;
if ($patient_id) {
  $s = $pdo->prepare("SELECT * FROM patients WHERE id=?");
  $s->execute([$patient_id]);
  $patient = $s->fetch();
}

$msg = isset($_GET['msg']) ? trim($_GET['msg']) : '';

$stmt = $pdo->query("SELECT ia.*, p.last_name, p.first_name FROM inpatient_admissions ia JOIN patients p ON p.id = ia.patient_id ORDER BY ia.admit_date DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
      <a
        href="new.php<?php echo $patient_id ? ('?patient_id='.(int)$patient_id) : '';?>"
        class="btn-primary-custom"
        data-bs-toggle="modal"
        data-bs-target="#modalCrud"
        data-title="New Admission"
        data-url="new.php<?php echo $patient_id ? ('?patient_id='.(int)$patient_id) : '';?>">
        <i class="bi bi-plus-circle"></i> New Admission
      </a>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="alert-custom"><?php echo htmlspecialchars($msg); ?></div>
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
                <a
                  href="/public/inpatient/edit.php?id=<?php echo (int)$r['id']; ?>"
                  class="btn-action btn-edit"
                  data-bs-toggle="modal"
                  data-bs-target="#modalCrud"
                  data-title="Edit Admission #<?php echo (int)$r['id']; ?>"
                  data-url="/public/inpatient/edit.php?id=<?php echo (int)$r['id']; ?>">
                  <i class="bi bi-pencil"></i> Edit
                </a>
                <a
                  class="btn-action btn-delete js-delete"
                  href="/public/inpatient/delete.php?id=<?php echo (int)$r['id']; ?>"
                  data-id="<?php echo (int)$r['id']; ?>">
                  <i class="bi bi-trash"></i> Delete
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
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
(function(){
  const modal = document.getElementById('modalCrud');
  if(!modal) return;
  const bodyEl = modal.querySelector('#modalCrudBody');
  const titleEl = modal.querySelector('#modalCrudTitle');

  modal.addEventListener('show.bs.modal', async function (ev) {
    const trigger = ev.relatedTarget;
    if (!trigger) return;
    const url = trigger.getAttribute('data-url');
    const title = trigger.getAttribute('data-title') || 'Form';
    titleEl.textContent = title;
    bodyEl.innerHTML = '<div class="text-center p-5">Loading…</div>';
    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const html = await res.text();
      bodyEl.innerHTML = html;

      const form = bodyEl.querySelector('form');
      if (form) {
        form.addEventListener('submit', async function(e){
          e.preventDefault();
          const fd = new FormData(form);
          const action = form.getAttribute('action') || url;
          const method = (form.getAttribute('method') || 'POST').toUpperCase();
          const submitBtn = form.querySelector('[type="submit"]');
          if (submitBtn) { submitBtn.disabled = true; submitBtn.dataset.originalText = submitBtn.innerHTML; submitBtn.innerHTML = 'Saving…'; }
          try {
            const resp = await fetch(action, {
              method,
              body: fd,
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const ct = resp.headers.get('content-type') || '';
            if (ct.includes('application/json')) {
              const data = await resp.json();
              if (data.ok) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                bsModal && bsModal.hide();
                window.location.reload();
                return;
              }
              if (data.html) { bodyEl.innerHTML = data.html; return; }
            }

            const text = await resp.text();
            if (text && text.trim().length > 0) {
              bodyEl.innerHTML = text;
            } else {
              const bsModal = bootstrap.Modal.getInstance(modal);
              bsModal && bsModal.hide();
              window.location.reload();
            }
          } catch (err) {
            console.error(err);
            alert('Something went wrong while saving.');
          } finally {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = submitBtn.dataset.originalText || 'Save'; }
          }
        }, { once: true });
      }
    } catch (e) {
      console.error(e);
      bodyEl.innerHTML = '<div class="alert alert-danger">Failed to load the form.</div>';
    }
  });

  document.addEventListener('click', async function(e){
    const a = e.target.closest('a.js-delete');
    if (!a) return;
    e.preventDefault();
    if (!confirm('Delete this admission?')) return;
    try {
      const res = await fetch(a.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (res.ok) {
        window.location.reload();
      } else {
        alert('Delete failed.');
      }
    } catch (err) {
      console.error(err);
      alert('Delete failed.');
    }
  });
})();
</script>

<?php include __DIR__ . "/../partials/footer.php"; ?>