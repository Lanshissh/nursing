<?php
$page_title = "Dashboard";
require_once __DIR__ . "/../config.php";
include __DIR__ . "/../partials/header.php";

// Quick stats
$stats = [
  'patients' => $pdo->query("SELECT COUNT(*) AS c FROM patients")->fetch()['c'],
  'checkups' => $pdo->query("SELECT COUNT(*) AS c FROM checkups")->fetch()['c'],
  'bp'       => $pdo->query("SELECT COUNT(*) AS c FROM bp_readings")->fetch()['c'],
  'inpt'     => $pdo->query("SELECT COUNT(*) AS c FROM inpatient_admissions")->fetch()['c'],
];
?>
<div class="row g-3">
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="text-muted">Patients</h6>
        <h2 class="fw-bold"><?php echo (int)$stats['patients']; ?></h2>
        <a class="btn btn-sm btn-outline-primary" href="/public/patients/index.php">Manage</a>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="text-muted">Check-ups</h6>
        <h2 class="fw-bold"><?php echo (int)$stats['checkups']; ?></h2>
        <a class="btn btn-sm btn-outline-primary" href="/public/checkups/index.php">Manage</a>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="text-muted">BP Readings</h6>
        <h2 class="fw-bold"><?php echo (int)$stats['bp']; ?></h2>
        <a class="btn btn-sm btn-outline-primary" href="/public/bp/index.php">Manage</a>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="text-muted">In-Patient</h6>
        <h2 class="fw-bold"><?php echo (int)$stats['inpt']; ?></h2>
        <a class="btn btn-sm btn-outline-primary" href="/public/inpatient/index.php">Manage</a>
      </div>
    </div>
  </div>
</div>

<div class="mt-4">
  <div class="alert alert-info">
    <strong>Tip:</strong> To get started, add a patient first, then log check-ups, BP readings, or an in-patient admission.
  </div>
</div>

<?php include __DIR__ . "/../partials/footer.php"; ?>
