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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="/assets/style.css" rel="stylesheet">
  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --nav-bg: rgba(255, 255, 255, 0.98);
      --nav-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      --nav-border: rgba(102, 126, 234, 0.1);
      --text-primary: #1e293b;
      --text-secondary: #64748b;
      --hover-bg: rgba(102, 126, 234, 0.08);
      --active-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
      background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
      min-height: 100vh;
    }

    /* Modern Navigation Bar */
    .navbar-modern {
      background: var(--nav-bg);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--nav-border);
      box-shadow: var(--nav-shadow);
      padding: 0.75rem 0;
      position: sticky;
      top: 0;
      z-index: 1030;
      transition: var(--transition);
    }

    .navbar-modern.scrolled {
      padding: 0.5rem 0;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    }

    /* Brand Styling */
    .navbar-brand-modern {
      font-size: 1.5rem;
      font-weight: 800;
      background: var(--primary-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      transition: var(--transition);
      padding: 0.5rem 0;
    }

    .navbar-brand-modern:hover {
      transform: translateY(-2px);
      -webkit-text-fill-color: transparent;
    }

    .brand-icon {
      width: 2.5rem;
      height: 2.5rem;
      background: var(--primary-gradient);
      border-radius: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.25rem;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
      transition: var(--transition);
    }

    .navbar-brand-modern:hover .brand-icon {
      transform: rotate(-5deg) scale(1.05);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    /* Navigation Links */
    .nav-link-modern {
      color: var(--text-primary);
      font-weight: 600;
      font-size: 0.9375rem;
      padding: 0.75rem 1.25rem;
      border-radius: 0.75rem;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 0.5rem;
      position: relative;
      overflow: hidden;
    }

    .nav-link-modern::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      width: 0;
      height: 2px;
      background: var(--primary-gradient);
      transform: translateX(-50%);
      transition: var(--transition);
    }

    .nav-link-modern:hover {
      color: #667eea;
      background: var(--hover-bg);
      transform: translateY(-2px);
    }

    .nav-link-modern:hover::before {
      width: 80%;
    }

    .nav-link-modern.active {
      background: var(--active-bg);
      color: white;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .nav-link-modern.active::before {
      display: none;
    }

    .nav-link-modern i {
      font-size: 1.125rem;
    }

    /* Mobile Toggle Button */
    .navbar-toggler-modern {
      border: 2px solid var(--nav-border);
      border-radius: 0.75rem;
      padding: 0.5rem 0.75rem;
      transition: var(--transition);
      background: transparent;
    }

    .navbar-toggler-modern:hover {
      border-color: #667eea;
      background: var(--hover-bg);
    }

    .navbar-toggler-modern:focus {
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
      outline: none;
    }

    .navbar-toggler-icon-modern {
      width: 1.5rem;
      height: 1.5rem;
      display: flex;
      flex-direction: column;
      justify-content: space-around;
    }

    .navbar-toggler-icon-modern span {
      width: 100%;
      height: 2px;
      background: #667eea;
      border-radius: 2px;
      transition: var(--transition);
    }

    /* Main Container */
    main.container {
      max-width: 1400px;
      animation: fadeInUp 0.6s ease-out;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Responsive Design */
    @media (max-width: 991.98px) {
      .navbar-nav {
        padding: 1rem 0;
        gap: 0.5rem;
      }

      .nav-link-modern {
        padding: 0.875rem 1rem;
      }

      .navbar-collapse {
        background: var(--nav-bg);
        margin-top: 1rem;
        border-radius: 1rem;
        padding: 1rem;
        border: 1px solid var(--nav-border);
      }
    }

    @media (min-width: 992px) {
      .navbar-nav {
        gap: 0.5rem;
      }
    }

    /* Smooth Scrolling */
    html {
      scroll-behavior: smooth;
    }

    /* Selection Color */
    ::selection {
      background: rgba(102, 126, 234, 0.3);
      color: var(--text-primary);
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-modern">
  <div class="container-fluid">
    <a class="navbar-brand navbar-brand-modern" href="../public/index.php">
      <span class="brand-icon">
        <i class="bi bi-heart-pulse-fill"></i>
      </span>
      <span>JDN CLINIC</span>
    </a>
    
    <button class="navbar-toggler navbar-toggler-modern" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample" aria-controls="navbarsExample" aria-expanded="false" aria-label="Toggle navigation">
      <div class="navbar-toggler-icon-modern">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarsExample">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link nav-link-modern <?php echo basename($_SERVER['PHP_SELF']) == 'patients.php' ? 'active' : ''; ?>" href="../public/patients.php">
            <i class="bi bi-people-fill"></i>
            <span>Patients</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link nav-link-modern <?php echo basename($_SERVER['PHP_SELF']) == 'checkups.php' ? 'active' : ''; ?>" href="../public/checkups.php">
            <i class="bi bi-clipboard2-pulse-fill"></i>
            <span>Check-ups</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link nav-link-modern <?php echo basename($_SERVER['PHP_SELF']) == 'bp.php' ? 'active' : ''; ?>" href="../public/bp.php">
            <i class="bi bi-activity"></i>
            <span>BP Monitoring</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link nav-link-modern <?php echo basename($_SERVER['PHP_SELF']) == 'inpatient.php' ? 'active' : ''; ?>" href="../public/inpatient.php">
            <i class="bi bi-hospital-fill"></i>
            <span>In-Patient</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link nav-link-modern <?php echo basename($_SERVER['PHP_SELF']) == 'medicine.php' ? 'active' : ''; ?>" href="../public/medicine.php">
            <i class="bi bi-capsule-pill"></i>
            <span>Medicine</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4">

<script>
// Add scroll effect to navbar
window.addEventListener('scroll', function() {
  const navbar = document.querySelector('.navbar-modern');
  if (window.scrollY > 20) {
    navbar.classList.add('scrolled');
  } else {
    navbar.classList.remove('scrolled');
  }
});

// Auto-close mobile menu when clicking a link
document.querySelectorAll('.nav-link-modern').forEach(link => {
  link.addEventListener('click', () => {
    const navbarCollapse = document.querySelector('.navbar-collapse');
    if (navbarCollapse.classList.contains('show')) {
      const bsCollapse = new bootstrap.Collapse(navbarCollapse);
      bsCollapse.hide();
    }
  });
});
</script>