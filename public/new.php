<?php
$page_title = "New Admission";
require_once __DIR__ . "/../config.php";

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $pdo->prepare("INSERT INTO inpatient_admissions (patient_id, admit_date, ward, bed, physician, diagnosis, notes) VALUES (?,?,?,?,?,?,?)");
  $stmt->execute([
    $_POST['patient_id'],
    $_POST['admit_date'],
    $_POST['ward'] ?: NULL,
    $_POST['bed'] ?: NULL,
    $_POST['physician'] ?: NULL,
    $_POST['diagnosis'] ?: NULL,
    $_POST['notes'] ?: NULL,
  ]);
  header("Location: /public/inpatient/index.php?patient_id=".(int)$_POST['patient_id']."&msg=Created");
  exit;
}

// patients for select
$patients = $pdo->query("SELECT id, first_name, last_name FROM patients ORDER BY last_name, first_name")->fetchAll();

?>
<h3>New In-Patient Admission</h3>
<form method="post" class="form-section">
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Patient</label>
      <select class="form-select" name="patient_id" required>
        <option value="">-- choose --</option>
        <?php foreach ($patients as $p): ?>
          <option value="<?php echo (int)$p['id']; ?>" <?php if($patient_id==$p['id']) echo 'selected'; ?>>
            <?php echo htmlspecialchars($p['last_name'].', '.$p['first_name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Admit Date/Time</label>
      <input class="form-control" type="datetime-local" name="admit_date" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
    </div>
    <div class="col-md-4"><label class="form-label">Ward</label><input class="form-control" name="ward"></div>
    <div class="col-md-4"><label class="form-label">Bed</label><input class="form-control" name="bed"></div>
    <div class="col-md-4"><label class="form-label">Physician</label><input class="form-control" name="physician"></div>
    <div class="col-12">
      <label class="form-label">Diagnosis</label>
      <textarea class="form-control" name="diagnosis" rows="2"></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Notes</label>
      <textarea class="form-control" name="notes" rows="3"></textarea>
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary">Save</button>
    <a class="btn btn-secondary" href="index.php<?php if($patient_id) echo '?patient_id='.$patient_id; ?>">Cancel</a>
  </div>
</form>