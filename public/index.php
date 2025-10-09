<?php
$page_title = "Dashboard";
require_once __DIR__ . "/../config.php";
include __DIR__ . "/../partials/header.php";

// Helper to choose an available date column for ORDER BY
function pick_order_column(PDO $pdo, string $table, array $candidates): string {
  $in = implode("','", array_map('addslashes', $candidates));
  $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
          WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = :table 
            AND COLUMN_NAME IN ('{$in}')";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':table' => $table]);
  $found = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'COLUMN_NAME');
  foreach ($candidates as $c) {
    if (in_array($c, $found, true)) return $c;
  }
  return $found[0] ?? 'id';
}

// Decide order columns
$checkupsOrderCol = pick_order_column($pdo, 'checkups', ['checkup_date','date','visit_date','created_at','created_on','updated_at','recorded_at','createdAt']);
$bpOrderCol       = pick_order_column($pdo, 'bp_readings', ['reading_date','date','created_at','created_on','recorded_at','measured_at','createdAt']);

// Safe date formatter
function fmt_date($val) {
  if ($val === null || $val === '' || $val === '0000-00-00' || $val === '0000-00-00 00:00:00') return '—';
  $ts = @strtotime($val);
  if ($ts === false || $ts <= 0) return '—';
  return date('M d, Y', $ts);
}

// Aliases for rendering
$checkupsDateCol = $checkupsOrderCol;
$bpDateCol       = $bpOrderCol;

// Quick stats
$stats = [
  'patients' => $pdo->query("SELECT COUNT(*) AS c FROM patients")->fetch()['c'],
  'checkups' => $pdo->query("SELECT COUNT(*) AS c FROM checkups")->fetch()['c'],
  'bp'       => $pdo->query("SELECT COUNT(*) AS c FROM bp_readings")->fetch()['c'],
  'inpt'     => $pdo->query("SELECT COUNT(*) AS c FROM inpatient_admissions")->fetch()['c'],
];

// Recent activity (last 5 records)
$recentCheckups = $pdo->query("
  SELECT c.*, p.first_name, p.last_name 
  FROM checkups c 
  JOIN patients p ON c.patient_id = p.id 
  ORDER BY c.`{$checkupsOrderCol}` DESC 
  LIMIT 5
")->fetchAll();

$recentBP = $pdo->query("
  SELECT b.*, p.first_name, p.last_name 
  FROM bp_readings b 
  JOIN patients p ON b.patient_id = p.id 
  ORDER BY b.`{$bpOrderCol}` DESC 
  LIMIT 5
")->fetchAll();
?>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

@keyframes gradient-shift {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}

@keyframes float {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(3deg); }
}

@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(40px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes glow-pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.dashboard-wrapper {
  min-height: 100vh;
  background: linear-gradient(135deg, #ffffffff 0%rgba(255, 255, 255, 1)ee 100%);
  background-size: 100% 100%;
  animation: gradient-shift 15s ease infinite;
  position: relative;
  overflow-x: hidden;
}

.dashboard-content {
  position: relative;
  z-index: 1;
  padding: 3rem 2rem;
  max-width: 1600px;
  margin: 0 auto;
}

.header-section {
  margin-bottom: 3rem;
  animation: fade-in-up 0.8s ease-out;
}

.main-title {
  font-size: 3rem;
  font-weight: 800;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 0.5rem;
  letter-spacing: -1px;
}

.subtitle {
  color: rgba(0, 0, 0, 0.5);
  font-size: 1.1rem;
  font-weight: 400;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2rem;
  margin-bottom: 3rem;
}

.stat-card {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  padding: 2rem;
  border: 1px solid rgba(0, 0, 0, 0.08);
  position: relative;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  animation: fade-in-up 0.8s ease-out backwards;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
  transition: left 0.5s;
}

.stat-card:hover::before {
  left: 100%;
}

.stat-card:hover {
  transform: translateY(-8px) scale(1.02);
  border-color: rgba(0, 0, 0, 0.12);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }

.stat-icon-wrapper {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  margin-bottom: 1.5rem;
  position: relative;
  animation: float 3s ease-in-out infinite;
}

.stat-icon-wrapper::before {
  content: '';
  position: absolute;
  inset: -3px;
  border-radius: 16px;
  background: linear-gradient(45deg, var(--color-1), var(--color-2));
  filter: blur(10px);
  opacity: 0.6;
  animation: glow-pulse 2s ease-in-out infinite;
}

.stat-icon-wrapper::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 16px;
  background: linear-gradient(135deg, var(--color-1), var(--color-2));
  z-index: -1;
}

.stat-label {
  color: rgba(0, 0, 0, 0.4);
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  margin-bottom: 0.75rem;
}

.stat-number {
  font-size: 3rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 1.5rem;
  line-height: 1;
}

.stat-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: rgba(0, 0, 0, 0.04);
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 12px;
  color: #1e293b;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.3s;
  position: relative;
  overflow: hidden;
}

.stat-btn::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, var(--color-1), var(--color-2));
  opacity: 0;
  transition: opacity 0.3s;
}

.stat-btn:hover {
  border-color: transparent;
  transform: translateX(5px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
  color: #fff;
}

.stat-btn:hover::before {
  opacity: 1;
}

.stat-btn span {
  position: relative;
  z-index: 1;
}

.activity-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
  gap: 2rem;
  animation: fade-in-up 1s ease-out 0.5s backwards;
}

.activity-card {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  padding: 2rem;
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.activity-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.activity-icon {
  font-size: 1.5rem;
}

.activity-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.activity-item {
  background: rgba(255, 255, 255, 0.5);
  border: 1px solid rgba(0, 0, 0, 0.06);
  border-radius: 16px;
  padding: 1.25rem;
  transition: all 0.3s;
  position: relative;
  overflow: hidden;
}

.activity-item::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: linear-gradient(180deg, var(--accent-1), var(--accent-2));
  opacity: 0;
  transition: opacity 0.3s;
}

.activity-item:hover {
  background: rgba(255, 255, 255, 0.9);
  border-color: rgba(0, 0, 0, 0.12);
  transform: translateX(8px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.activity-item:hover::before {
  opacity: 1;
}

.activity-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.activity-info strong {
  color: #1e293b;
  font-size: 1rem;
  font-weight: 600;
  display: block;
  margin-bottom: 0.5rem;
}

.activity-meta {
  color: rgba(0, 0, 0, 0.4);
  font-size: 0.875rem;
}

.activity-badge {
  padding: 0.5rem 1rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-checkup {
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(99, 102, 241, 0.15));
  color: #7c3aed;
  border: 1px solid rgba(139, 92, 246, 0.3);
}

.badge-bp {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(14, 165, 233, 0.15));
  color: #2563eb;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.empty-state {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  padding: 4rem 2rem;
  border: 1px solid rgba(0, 0, 0, 0.08);
  text-align: center;
  animation: fade-in-up 1s ease-out 0.5s backwards;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1.5rem;
  animation: float 3s ease-in-out infinite;
}

.empty-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.75rem;
}

.empty-text {
  color: rgba(0, 0, 0, 0.5);
  margin-bottom: 2rem;
  font-size: 1.1rem;
}

.cta-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 2rem;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  border-radius: 16px;
  color: #fff;
  text-decoration: none;
  font-weight: 700;
  font-size: 1rem;
  transition: all 0.3s;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.cta-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
  color: #fff;
}

@media (max-width: 768px) {
  .dashboard-content {
    padding: 2rem 1rem;
  }
  
  .main-title {
    font-size: 2rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .activity-section {
    grid-template-columns: 1fr;
  }
  
}
</style>

<div class="dashboard-wrapper">
  <div class="dashboard-content">
    
    <div class="header-section">
      <h1 class="main-title">JDN CLINIC Analytics</h1>
      <p class="subtitle">Real-time insights and monitoring for better care</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card" style="--color-1: #667eea; --color-2: #764ba2;">
        <div class="stat-icon-wrapper">
          👥
        </div>
        <div class="stat-label">Total Patients</div>
        <div class="stat-number"><?php echo number_format((int)$stats['patients']); ?></div>
        <a href="patients.php" class="stat-btn">
          <span>View All</span>
          <span>→</span>
        </a>
      </div>

      <div class="stat-card" style="--color-1: #f093fb; --color-2: #f5576c;">
        <div class="stat-icon-wrapper">
          🩺
        </div>
        <div class="stat-label">Check-ups</div>
        <div class="stat-number"><?php echo number_format((int)$stats['checkups']); ?></div>
        <a href="checkups.php" class="stat-btn">
          <span>Manage</span>
          <span>→</span>
        </a>
      </div>

      <div class="stat-card" style="--color-1: #4facfe; --color-2: #00f2fe;">
        <div class="stat-icon-wrapper">
          💓
        </div>
        <div class="stat-label">BP Readings</div>
        <div class="stat-number"><?php echo number_format((int)$stats['bp']); ?></div>
        <a href="bp.php" class="stat-btn">
          <span>Monitor</span>
          <span>→</span>
        </a>
      </div>

      <div class="stat-card" style="--color-1: #43e97b; --color-2: #38f9d7;">
        <div class="stat-icon-wrapper">
          🏥
        </div>
        <div class="stat-label">In-Patient</div>
        <div class="stat-number"><?php echo number_format((int)$stats['inpt']); ?></div>
        <a href="inpatient.php" class="stat-btn">
          <span>View All</span>
          <span>→</span>
        </a>
      </div>
    </div>

    <?php if (!empty($recentCheckups) || !empty($recentBP)): ?>
    <div class="activity-section">
      <?php if (!empty($recentCheckups)): ?>
      <div class="activity-card">
        <div class="activity-header">
          <span class="activity-icon">📋</span>
          <h3 class="activity-title">Recent Check-ups</h3>
        </div>
        <div class="activity-list">
          <?php foreach ($recentCheckups as $checkup): ?>
          <div class="activity-item" style="--accent-1: #8b5cf6; --accent-2: #6366f1;">
            <div class="activity-content">
              <div class="activity-info">
                <strong><?php echo htmlspecialchars($checkup['first_name'] . ' ' . $checkup['last_name']); ?></strong>
                <div class="activity-meta">
                  <?php echo fmt_date($checkup[$checkupsDateCol] ?? null); ?>
                </div>
              </div>
              <div class="activity-badge badge-checkup">Check-up</div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($recentBP)): ?>
      <div class="activity-card">
        <div class="activity-header">
          <span class="activity-icon">📊</span>
          <h3 class="activity-title">Recent BP Readings</h3>
        </div>
        <div class="activity-list">
          <?php foreach ($recentBP as $bp): ?>
          <div class="activity-item" style="--accent-1: #3b82f6; --accent-2: #0ea5e9;">
            <div class="activity-content">
              <div class="activity-info">
                <strong><?php echo htmlspecialchars($bp['first_name'] . ' ' . $bp['last_name']); ?></strong>
                <div class="activity-meta">
                  <?php echo fmt_date($bp[$bpDateCol] ?? null); ?> • 
                  <?php echo htmlspecialchars($bp['systolic'] . '/' . $bp['diastolic']); ?> mmHg
                </div>
              </div>
              <div class="activity-badge badge-bp">BP Reading</div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon">🚀</div>
      <h2 class="empty-title">Ready to Get Started?</h2>
      <p class="empty-text">Add your first patient to begin tracking healthcare data</p>
      <a href="patients.php" class="cta-btn">
        <span>Add Patient Now</span>
        <span>→</span>
      </a>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . "/../partials/footer.php"; ?>