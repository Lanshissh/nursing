<?php
$page_title = "Admission Details";
require_once __DIR__ . "/../config.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT i.*, p.first_name, p.last_name FROM inpatient_admissions i JOIN patients p ON p.id=i.patient_id WHERE i.id=?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { http_response_code(404); echo "Not found"; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discharge_date'])) {
  $stmt = $pdo->prepare("UPDATE inpatient_admissions SET discharge_date=? WHERE id=?");
  $stmt->execute([$_POST['discharge_date'] ?: NULL, $id]);
  header("Location: /public/inpatient/view.php?id=".$id."&msg=Updated");
  exit;
}

include __DIR__ . "/../partials/header.php";
?>
<h3>Admission Details</h3>
<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>
<div class="row g-3">
  <div class="col-md-6">
    <div class="form-section">
      <h5 class="mb-3">Admission</h5>
      <dl class="row mb-0">
        <dt class="col-sm-4">Patient</dt><dd class="col-sm-8"><?php echo htmlspecialchars($r['last_name'].', '.$r['first_name']); ?></dd>
        <dt class="col-sm-4">Admit Date</dt><dd class="col-sm-8"><?php echo htmlspecialchars($r['admit_date']); ?></dd>
        <dt class="col-sm-4">Ward/Bed</dt><dd class="col-sm-8"><?php echo htmlspecialchars(trim(($r['ward']?:'')." ".$r['bed'])); ?></dd>
        <dt class="col-sm-4">Physician</dt><dd class="col-sm-8"><?php echo htmlspecialchars($r['physician']); ?></dd>
      </dl>
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-section">
      <h5 class="mb-3">Status</h5>
      <form method="post" class="row g-2">
        <div class="col-12">
          <label class="form-label">Discharge Date/Time</label>
          <input class="form-control" type="datetime-local" name="discharge_date" value="<?php echo $r['discharge_date'] ? date('Y-m-d\TH:i', strtotime($r['discharge_date'])) : ''; ?>">
        </div>
        <div class="col-12">
          <button class="btn btn-primary">Update</button>
          <a class="btn btn-secondary" href="/public/inpatient/index.php?patient_id=<?php echo (int)$r['patient_id']; ?>">Back</a>
        </div>
      </form>
    </div>
  </div>
  <div class="col-12">
    <div class="form-section">
      <h5>Diagnosis</h5>
      <p><?php echo nl2br(htmlspecialchars($r['diagnosis'])); ?></p>
      <h5 class="mt-3">Notes</h5>
      <p><?php echo nl2br(htmlspecialchars($r['notes'])); ?></p>
    </div>
  </div>
</div>
<?php include __DIR__ . "/../partials/footer.php"; ?>