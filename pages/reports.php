<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Public organizational stats
$totalDonations = (int)$pdo->query('SELECT COUNT(*) FROM donations WHERE status = "approved" OR status = "fully_allocated"')->fetchColumn();
$totalTransactions = (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE status = "completed"')->fetchColumn();
$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status = "active"')->fetchColumn();

$pageTitle = 'Organizational Reports & Impact — iCareDonate';
$extraCss = 'public.css';
require_once __DIR__ . '/../includes/header.php';
?>

<?php require_once __DIR__ . '/../includes/nav-public.php'; ?>

<main class="page-container" style="padding: 48px 24px; max-width: 1040px; margin: 0 auto;">
  <div style="text-align: center; margin-bottom: 40px;">
    <h1 style="font-family: var(--font-brand); font-size: 36px; color: var(--color-primary); margin-bottom: 8px;">
      Organizational Reports & Community Impact
    </h1>
    <p style="color: var(--color-text-muted); font-size: 16px; max-width: 600px; margin: 0 auto;">
      We believe in 100% transparency and accountability. Explore our annual reports, financial summaries, and community impact records.
    </p>
  </div>

  <!-- Metric Impact Cards -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 40px;">
    <div class="card" style="padding: 24px; text-align: center; border: 1px solid var(--color-border); border-radius: var(--radius-lg); background: #fff;">
      <div style="font-family: var(--font-brand); font-size: 36px; color: var(--color-primary); line-height: 1;"><?= number_format($totalUsers) ?>+</div>
      <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 6px;">Community Members</div>
    </div>
    <div class="card" style="padding: 24px; text-align: center; border: 1px solid var(--color-border); border-radius: var(--radius-lg); background: #fff;">
      <div style="font-family: var(--font-brand); font-size: 36px; color: var(--color-primary); line-height: 1;"><?= number_format($totalDonations) ?>+</div>
      <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 6px;">Resource Listings</div>
    </div>
    <div class="card" style="padding: 24px; text-align: center; border: 1px solid var(--color-border); border-radius: var(--radius-lg); background: #fff;">
      <div style="font-family: var(--font-brand); font-size: 36px; color: #10B981; line-height: 1;"><?= number_format($totalTransactions) ?>+</div>
      <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 6px;">Completed Deliveries</div>
    </div>
  </div>

  <!-- Published Documents List -->
  <div class="card" style="padding: 32px; border: 1px solid var(--color-border); border-radius: var(--radius-lg); background: #fff; margin-bottom: 32px;">
    <h2 style="font-family: var(--font-brand); font-size: 22px; color: var(--color-text); margin-bottom: 20px;">
      Published Reports & Governance Documents
    </h2>

    <div style="display: flex; flex-direction: column; gap: 16px;">
      
      <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--color-border); border-radius: var(--radius-md); background: #F8FAFC; flex-wrap: wrap; gap: 12px;">
        <div>
          <h3 style="font-size: 15px; font-weight: 700; color: var(--color-text); margin: 0 0 4px;">2025 Annual Transparency & Impact Report</h3>
          <p style="font-size: 12.5px; color: var(--color-text-muted); margin: 0;">Comprehensive overview of donations, community reach, and logistical distribution metrics across Metro Manila.</p>
        </div>
        <div>
          <a href="#" onclick="alert('Downloading 2025 Annual Transparency Report (PDF)...'); return false;" class="btn btn-outline" style="font-size: 12.5px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;">
            <i data-lucide="file-text" style="width: 14px; height: 14px;"></i> Download PDF
          </a>
        </div>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--color-border); border-radius: var(--radius-md); background: #F8FAFC; flex-wrap: wrap; gap: 12px;">
        <div>
          <h3 style="font-size: 15px; font-weight: 700; color: var(--color-text); margin: 0 0 4px;">Community Relief & Disaster Assistance Summary</h3>
          <p style="font-size: 12.5px; color: var(--color-text-muted); margin: 0;">Emergency food package allocations, medical supply transfers, and school supplies assistance logs.</p>
        </div>
        <div>
          <a href="#" onclick="alert('Downloading Relief Summary Report (PDF)...'); return false;" class="btn btn-outline" style="font-size: 12.5px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;">
            <i data-lucide="file-text" style="width: 14px; height: 14px;"></i> Download PDF
          </a>
        </div>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--color-border); border-radius: var(--radius-md); background: #F8FAFC; flex-wrap: wrap; gap: 12px;">
        <div>
          <h3 style="font-size: 15px; font-weight: 700; color: var(--color-text); margin: 0 0 4px;">Audited Financial & Operations Summary (2025)</h3>
          <p style="font-size: 12.5px; color: var(--color-text-muted); margin: 0;">Third-party audited statement of non-profit operations, software infrastructure, and community support funding.</p>
        </div>
        <div>
          <a href="#" onclick="alert('Downloading Financial Summary (PDF)...'); return false;" class="btn btn-outline" style="font-size: 12.5px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;">
            <i data-lucide="file-text" style="width: 14px; height: 14px;"></i> Download PDF
          </a>
        </div>
      </div>

    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
