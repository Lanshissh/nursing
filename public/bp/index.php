<?php
$page_title = "BP Monitoring";
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
  $stmt = $pdo->prepare("DELETE FROM bp_readings WHERE id=?");
  $stmt->execute([$id]);
  $redir = "/clinic-system/public/bp/index.php";
  if ($patient_id) $redir .= "?patient_id=$patient_id";
  header("Location: ".$redir."&msg=Deleted");
  exit;
}

if ($patient_id) {
  $stmt = $pdo->prepare("SELECT b.*, p.first_name, p.last_name FROM bp_readings b JOIN patients p ON p.id=b.patient_id WHERE b.patient_id=? ORDER BY b.reading_time DESC, b.id DESC");
  $stmt->execute([$patient_id]);
} else {
  $stmt = $pdo->query("SELECT b.*, p.first_name, p.last_name FROM bp_readings b JOIN patients p ON p.id=b.patient_id ORDER BY b.reading_time DESC, b.id DESC LIMIT 200");
}
$rows = $stmt->fetchAll();

include __DIR__ . "/../../partials/header.php";
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="mb-0">BP Readings <?php if($patient){ echo "for ".htmlspecialchars($patient['last_name'].', '.$patient['first_name']); } ?></h3>
    <div class="text-muted small">Latest 200 shown.</div>
  </div>
  <a class="btn btn-primary" href="new.php<?php if($patient_id) echo '?patient_id='.$patient_id; ?>">+ Add Reading</a>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light">
    <tr>
      <th>Date & Time</th>
      <th>Patient</th>
      <th>BP (mmHg)</th>
      <th>Pulse</th>
      <th>Position</th>
      <th>Notes</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?php echo htmlspecialchars($r['reading_time']); ?></td>
      <td><?php echo htmlspecialchars($r['last_name'].', '.$r['first_name']); ?></td>
      <td><strong><?php echo (int)$r['systolic']; ?></strong>/<strong><?php echo (int)$r['diastolic']; ?></strong></td>
      <td><?php echo htmlspecialchars($r['pulse']); ?></td>
      <td><?php echo htmlspecialchars($r['position']); ?></td>
      <td><?php echo htmlspecialchars(mb_strimwidth($r['notes'],0,30,'…')); ?></td>
      <td>
        <a class="btn btn-sm btn-outline-danger" href="/public/bp/index.php?delete=<?php echo (int)$r['id']; ?><?php if($patient_id) echo '&patient_id='.$patient_id; ?>" onclick="return confirm('Delete this reading?');">Delete</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . "/../../partials/footer.php"; ?>
