<?php
$page_title = "Edit Patient";
require_once __DIR__ . "/../../config.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id=?");
$stmt->execute([$id]);
$patient = $stmt->fetch();
if (!$patient) { http_response_code(404); echo "Not found"; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $pdo->prepare("UPDATE patients SET first_name=?, last_name=?, sex=?, birthdate=?, contact=?, address=? WHERE id=?");
  $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['sex'] ?: NULL, $_POST['birthdate'] ?: NULL, $_POST['contact'] ?: NULL, $_POST['address'] ?: NULL, $id]);
  header("Location: /public/patients/index.php?msg=Updated");
  exit;
}

include __DIR__ . "/../../partials/header.php";
?>
<h3>Edit Patient</h3>
<form method="post" class="form-section">
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">First Name</label>
      <input class="form-control" name="first_name" value="<?php echo htmlspecialchars($patient['first_name']); ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Last Name</label>
      <input class="form-control" name="last_name" value="<?php echo htmlspecialchars($patient['last_name']); ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Sex</label>
      <select class="form-select" name="sex">
        <option value="">--</option>
        <option <?php if($patient['sex']==='Male') echo 'selected'; ?>>Male</option>
        <option <?php if($patient['sex']==='Female') echo 'selected'; ?>>Female</option>
        <option <?php if($patient['sex']==='Other') echo 'selected'; ?>>Other</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Birthdate</label>
      <input class="form-control" type="date" name="birthdate" value="<?php echo htmlspecialchars($patient['birthdate']); ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Contact</label>
      <input class="form-control" name="contact" value="<?php echo htmlspecialchars($patient['contact']); ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Address</label>
      <input class="form-control" name="address" value="<?php echo htmlspecialchars($patient['address']); ?>">
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary">Save</button>
    <a class="btn btn-secondary" href="/public/patients/index.php">Cancel</a>
  </div>
</form>
<?php include __DIR__ . "/../../partials/footer.php"; ?>
