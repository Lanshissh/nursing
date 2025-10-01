<?php
$page_title = "In-Patient Admissions";
require_once __DIR__ . "/../../config.php";

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$patient = null;
if ($patient_id) {
  $s = $pdo->prepare("SELECT * FROM patients WHERE id=?");
  $s->execute([$patient_id]);
  $patient = $s->fetch();
}

if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $stmt = $pdo->prepare("DELETE FROM inpatient_admissions WHERE id=?");
  $stmt->execute([$id]);
  $redir = "/public/inpatient/index.php";
  if ($patient_id) $redir .= "?patient_id=$patient_id";
  header("Location: ".$redir."&msg=Deleted");
  exit;
}

if ($patient_id) {
  $stmt = $pdo->prepare("SELECT i.*, p.first_name, p.last_name FROM inpatient_admissions i JOIN patients p ON p.id=i.patient_id WHERE i.patient_id=? ORDER BY i.admit_date DESC, i.id DESC");
  $stmt->execute([$patient_id]);
} else {
  $stmt = $pdo->query("SELECT i.*, p.first_name, p.last_name FROM inpatient_admissions i JOIN patients p ON p.id=i.patient_id ORDER BY i.admit_date DESC, i.id DESC LIMIT 200");
}
$rows = $stmt->fetchAll();

include __DIR__ . "/../../partials/header1.php";
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="mb-0">In-Patient Admissions <?php if($patient){ echo "for ".htmlspecialchars($patient['last_name'].', '.$patient['first_name']); } ?></h3>
    <div class="text-muted small">Latest 200 shown.</div>
  </div>
  <a class="btn btn-primary" href="new.php<?php if($patient_id) echo '?patient_id='.$patient_id; ?>">+ New Admission</a>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light">
    <tr>
      <th>Admit Date</th>
      <th>Patient</th>
      <th>Ward/Bed</th>
      <th>Physician</th>
      <th>Diagnosis</th>
      <th>Discharge</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?php echo htmlspecialchars($r['admit_date']); ?></td>
      <td><?php echo htmlspecialchars($r['last_name'].', '.$r['first_name']); ?></td>
      <td><?php echo htmlspecialchars(trim(($r['ward']?:'')." ".$r['bed'])); ?></td>
      <td><?php echo htmlspecialchars($r['physician']); ?></td>
      <td><?php echo htmlspecialchars(mb_strimwidth($r['diagnosis'],0,40,'…')); ?></td>
      <td><?php echo htmlspecialchars($r['discharge_date']); ?></td>
      <td>
        <a class="btn btn-sm btn-outline-primary" href="/public/inpatient/view.php?id=<?php echo (int)$r['id']; ?>">View</a>
        <a class="btn btn-sm btn-outline-danger" href="/public/inpatient/index.php?delete=<?php echo (int)$r['id']; ?><?php if($patient_id) echo '&patient_id='.$patient_id; ?>" onclick="return confirm('Delete this admission?');">Delete</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . "/../../partials/footer.php"; ?>
