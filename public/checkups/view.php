<?php
$page_title = "View Check-up";
require_once __DIR__ . "/../../config.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT c.*, p.first_name, p.last_name FROM checkups c JOIN patients p ON p.id=c.patient_id WHERE c.id=?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { http_response_code(404); echo "Not found"; exit; }

include __DIR__ . "/../../partials/header.php";
?>
<h3>Check-up Detail</h3>
<div class="row g-3">
  <div class="col-md-6">
    <div class="form-section">
      <h5 class="mb-3">Visit Info</h5>
      <dl class="row mb-0">
        <dt class="col-sm-4">Date</dt><dd class="col-sm-8"><?php echo htmlspecialchars($r['visit_date']); ?></dd>
        <dt class="col-sm-4">Patient</dt><dd class="col-sm-8"><?php echo htmlspecialchars($r['last_name'].', '.$r['first_name']); ?></dd>
      </dl>
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-section">
      <h5 class="mb-3">Vitals</h5>
      <dl class="row mb-0">
        <dt class="col-sm-4">Height (cm)</dt><dd class="col-sm-8"><?php echo htmlspecialchars($r['height_cm']); ?></dd>
        <dt class="col-sm-4">Weight (kg)</dt><dd class="col-sm-8"><?php echo htmlspecialchars($r['weight_kg']); ?></dd>
        <dt class="col-sm-4">Temp (°C)</dt><dd class="col-sm-8"><?php echo htmlspecialchars($r['temp_c']); ?></dd>
        <dt class="col-sm-4">Pulse</dt><dd class="col-sm-8"><?php echo htmlspecialchars($r['pulse']); ?></dd>
        <dt class="col-sm-4">Resp Rate</dt><dd class="col-sm-8"><?php echo htmlspecialchars($r['resp_rate']); ?></dd>
        <dt class="col-sm-4">SpO₂</dt><dd class="col-sm-8"><?php echo htmlspecialchars($r['spo2']); ?></dd>
      </dl>
    </div>
  </div>
  <div class="col-12">
    <div class="form-section">
      <h5>Chief Complaint</h5>
      <p><?php echo nl2br(htmlspecialchars($r['complaint'])); ?></p>
    </div>
  </div>
  <div class="col-12">
    <div class="form-section">
      <h5>Diagnosis</h5>
      <p><?php echo nl2br(htmlspecialchars($r['diagnosis'])); ?></p>
    </div>
  </div>
  <div class="col-12">
    <div class="form-section">
      <h5>Treatment / Plan</h5>
      <p><?php echo nl2br(htmlspecialchars($r['treatment'])); ?></p>
    </div>
  </div>
  <div class="col-12">
    <div class="form-section">
      <h5>Notes</h5>
      <p><?php echo nl2br(htmlspecialchars($r['notes'])); ?></p>
    </div>
  </div>
</div>
<div class="mt-3">
  <a class="btn btn-secondary" href="/public/checkups/index.php?patient_id=<?php echo (int)$r['patient_id']; ?>">Back</a>
</div>
<?php include __DIR__ . "/../../partials/footer.php"; ?>
