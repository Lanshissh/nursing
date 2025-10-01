<?php
$page_title = "Add BP Reading";
require_once __DIR__ . "/../../config.php";

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $pdo->prepare("INSERT INTO bp_readings (patient_id, reading_time, systolic, diastolic, pulse, position, notes) VALUES (?,?,?,?,?,?,?)");
  $stmt->execute([
    $_POST['patient_id'],
    $_POST['reading_time'],
    $_POST['systolic'],
    $_POST['diastolic'],
    $_POST['pulse'] ?: NULL,
    $_POST['position'] ?: NULL,
    $_POST['notes'] ?: NULL,
  ]);
  header("Location: /public/bp/index.php?patient_id=".(int)$_POST['patient_id']."&msg=Created");
  exit;
}

// patients for select
$patients = $pdo->query("SELECT id, first_name, last_name FROM patients ORDER BY last_name, first_name")->fetchAll();

include __DIR__ . "/../../partials/header1.php";
?>
<h3>Add BP Reading</h3>
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
      <label class="form-label">Reading Time</label>
      <input class="form-control" type="datetime-local" name="reading_time" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
    </div>
    <div class="col-md-3"><label class="form-label">Systolic</label><input class="form-control" type="number" name="systolic" required></div>
    <div class="col-md-3"><label class="form-label">Diastolic</label><input class="form-control" type="number" name="diastolic" required></div>
    <div class="col-md-3"><label class="form-label">Pulse</label><input class="form-control" type="number" name="pulse"></div>
    <div class="col-md-3"><label class="form-label">Position</label><input class="form-control" name="position" placeholder="Sitting / Standing / Lying"></div>
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
<?php include __DIR__ . "/../../partials/footer.php"; ?>
