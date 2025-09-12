<?php
$page_title = "Add Check-up";
require_once __DIR__ . "/../../config.php";

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $pdo->prepare("INSERT INTO checkups (patient_id, visit_date, complaint, diagnosis, treatment, height_cm, weight_kg, temp_c, pulse, resp_rate, spo2, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
  $stmt->execute([
    $_POST['patient_id'],
    $_POST['visit_date'],
    $_POST['complaint'] ?: NULL,
    $_POST['diagnosis'] ?: NULL,
    $_POST['treatment'] ?: NULL,
    $_POST['height_cm'] ?: NULL,
    $_POST['weight_kg'] ?: NULL,
    $_POST['temp_c'] ?: NULL,
    $_POST['pulse'] ?: NULL,
    $_POST['resp_rate'] ?: NULL,
    $_POST['spo2'] ?: NULL,
    $_POST['notes'] ?: NULL,
  ]);
  header("Location: /public/checkups/index.php?patient_id=".(int)$_POST['patient_id']."&msg=Created");
  exit;
}

// patients for select
$patients = $pdo->query("SELECT id, first_name, last_name FROM patients ORDER BY last_name, first_name")->fetchAll();

include __DIR__ . "/../../partials/header.php";
?>
<h3>Add Check-up</h3>
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
      <label class="form-label">Visit Date</label>
      <input class="form-control" type="date" name="visit_date" value="<?php echo date('Y-m-d'); ?>" required>
    </div>
    <div class="col-12">
      <label class="form-label">Chief Complaint</label>
      <textarea class="form-control" name="complaint" rows="2"></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Diagnosis</label>
      <textarea class="form-control" name="diagnosis" rows="2"></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Treatment / Plan</label>
      <textarea class="form-control" name="treatment" rows="2"></textarea>
    </div>
    <div class="col-md-2"><label class="form-label">Height (cm)</label><input class="form-control" type="number" step="0.01" name="height_cm"></div>
    <div class="col-md-2"><label class="form-label">Weight (kg)</label><input class="form-control" type="number" step="0.01" name="weight_kg"></div>
    <div class="col-md-2"><label class="form-label">Temp (°C)</label><input class="form-control" type="number" step="0.1" name="temp_c"></div>
    <div class="col-md-2"><label class="form-label">Pulse</label><input class="form-control" type="number" name="pulse"></div>
    <div class="col-md-2"><label class="form-label">Resp Rate</label><input class="form-control" type="number" name="resp_rate"></div>
    <div class="col-md-2"><label class="form-label">SpO₂ (%)</label><input class="form-control" type="number" name="spo2" min="0" max="100"></div>
    <div class="col-12">
      <label class="form-label">Notes</label>
      <textarea class="form-control" name="notes" rows="3"></textarea>
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary">Save</button>
    <a class="btn btn-secondary" href="/public/checkups/index.php<?php if($patient_id) echo '?patient_id='.$patient_id; ?>">Cancel</a>
  </div>
</form>
<?php include __DIR__ . "/../../partials/footer.php"; ?>
