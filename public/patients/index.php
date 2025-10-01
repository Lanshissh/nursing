<?php
$page_title = "Patients";
require_once __DIR__ . "/../../config.php";

// handle delete
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $stmt = $pdo->prepare("DELETE FROM patients WHERE id=?");
  $stmt->execute([$id]);
  header("Location: /public/patients/index.php?msg=Deleted");
  exit;
}

// search
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q) {
  $stmt = $pdo->prepare("SELECT * FROM patients WHERE last_name LIKE ? OR first_name LIKE ? ORDER BY last_name, first_name LIMIT 200");
  $stmt->execute(["%$q%", "%$q%"]);
} else {
  $stmt = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC LIMIT 200");
}
$rows = $stmt->fetchAll();

include __DIR__ . "/../../partials/header1.php";
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Patients</h3>
  <a class="btn btn-primary" href="new.php">+ Add Patient</a>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-auto">
    <input class="form-control" type="text" name="q" placeholder="Search name..." value="<?php echo htmlspecialchars($q); ?>">
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary">Search</button>
  </div>
</form>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light">
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Sex</th>
      <th>Birthdate</th>
      <th>Contact</th>
      <th>Address</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?php echo (int)$r['id']; ?></td>
      <td><?php echo htmlspecialchars($r['last_name'] . ", " . $r['first_name']); ?></td>
      <td><?php echo htmlspecialchars($r['sex']); ?></td>
      <td><?php echo htmlspecialchars($r['birthdate']); ?></td>
      <td><?php echo htmlspecialchars($r['contact']); ?></td>
      <td><?php echo htmlspecialchars($r['address']); ?></td>
      <td>
        <a class="btn btn-sm btn-outline-primary" href="/public/patients/edit.php?id=<?php echo (int)$r['id']; ?>">Edit</a>
        <a class="btn btn-sm btn-outline-danger" href="/public/patients/index.php?delete=<?php echo (int)$r['id']; ?>" onclick="return confirm('Delete this patient? This will also remove related records.');">Delete</a>
        <a class="btn btn-sm btn-secondary" href="/public/checkups/index.php?patient_id=<?php echo (int)$r['id']; ?>">Check-ups</a>
        <a class="btn btn-sm btn-secondary" href="/public/bp/index.php?patient_id=<?php echo (int)$r['id']; ?>">BP</a>
        <a class="btn btn-sm btn-secondary" href="/public/inpatient/index.php?patient_id=<?php echo (int)$r['id']; ?>">In-Patient</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . "/../../partials/footer.php"; ?>
