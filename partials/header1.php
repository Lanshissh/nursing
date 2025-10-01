<?php
if (!isset($page_title)) { $page_title = 'Clinic System'; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="../../public/index.php">Clinic System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarsExample">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="../../public/patients/index.php">Patients</a></li>
        <li class="nav-item"><a class="nav-link" href="../../public/checkups/index.php">Check-ups</a></li>
        <li class="nav-item"><a class="nav-link" href="../../public/bp/index.php">BP Monitoring</a></li>
        <li class="nav-item"><a class="nav-link" href="../../public/inpatient/index.php">In-Patient</a></li>
      </ul>
    </div>
  </div>
</nav>
<main class="container py-4">
