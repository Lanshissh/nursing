<?php
$page_title = "Add Patient";
require_once __DIR__ . "/../../config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $pdo->prepare("INSERT INTO patients (first_name, last_name, sex, birthdate, contact, address) VALUES (?,?,?,?,?,?)");
  $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['sex'] ?: NULL, $_POST['birthdate'] ?: NULL, $_POST['contact'] ?: NULL, $_POST['address'] ?: NULL]);
  header("Location: /public/patients/index.php?msg=Created");
  exit;
}

include __DIR__ . "/../../partials/header.php";
?>
<h3>Add Patient</h3>
<form method="post" class="form-section">
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">First Name</label>
      <input class="form-control" name="first_name" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Last Name</label>
      <input class="form-control" name="last_name" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Sex</label>
      <select class="form-select" name="sex">
        <option value="">--</option>
        <option>Male</option>
        <option>Female</option>
        <option>Other</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Birthdate</label>
      <input class="form-control" type="date" name="birthdate">
    </div>
    <div class="col-md-4">
      <label class="form-label">Contact</label>
      <input class="form-control" name="contact">
    </div>
    <div class="col-12">
      <label class="form-label">Address</label>
      <input class="form-control" name="address">
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary">Save</button>
    <a class="btn btn-secondary" href="/public/patients/index.php">Cancel</a>
  </div>
</form>
<?php include __DIR__ . "/../../partials/footer.php"; ?>
