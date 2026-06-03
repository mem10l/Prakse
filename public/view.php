<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Northwind — Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php require __DIR__ . '/layout_sidebar.php'; ?>

  <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-900">Dashboard</h2>
    <p class="text-gray-500 mt-1">Overview of your Northwind ERP data</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php
    $stats = [
      ['label' => 'Products', 'tbl' => 'products', 'color' => 'blue', 'icon' => '📦'],
      ['label' => 'Total Orders', 'tbl' => 'orders', 'color' => 'green', 'icon' => '🛒'],
      ['label' => 'Customers', 'tbl' => 'customers', 'color' => 'purple', 'icon' => '👥'],
      ['label' => 'Employees', 'tbl' => 'employees', 'color' => 'orange', 'icon' => '👔'],
      ['label' => 'Suppliers', 'tbl' => 'suppliers', 'color' => 'red', 'icon' => '🏭'],
      ['label' => 'Categories', 'tbl' => 'categories', 'color' => 'teal', 'icon' => '🏷️'],
    ];

    foreach ($stats as $s): ?>
    <a href="<?= $baseUrl ?>/table/<?= $s['tbl'] ?>" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:shadow-md hover:border-blue-300 transition-all group">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 bg-<?= $s['color'] ?>-100 text-<?= $s['color'] ?>-600 rounded-lg flex items-center justify-center text-2xl">
          <?= $s['icon'] ?>
        </div>
        <span class="text-gray-400 group-hover:text-blue-500 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </span>
      </div>
      <h3 class="text-gray-500 text-sm font-medium uppercase tracking-wider"><?= $s['label'] ?></h3>
      <p class="text-2xl font-bold text-gray-900 mt-1" id="count-<?= $s['tbl'] ?>">...</p>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Recent Activity / Info -->
  <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
      <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Links</h3>
      <div class="space-y-3">
        <a href="<?= $baseUrl ?>/reports" class="flex items-center p-3 rounded-lg border border-gray-100 hover:bg-blue-50 transition-colors text-gray-700">
          <span class="mr-3">📊</span>
          <span>View Detailed Sales Reports</span>
        </a>
        <a href="<?= $baseUrl ?>/insert" class="flex items-center p-3 rounded-lg border border-gray-100 hover:bg-blue-50 transition-colors text-gray-700">
          <span class="mr-3">➕</span>
          <span>Bulk Insert New Records</span>
        </a>
      </div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
      <h3 class="text-lg font-bold text-gray-900 mb-4">System Status</h3>
      <div class="flex items-center space-x-2 text-green-600 mb-2">
        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
        <span class="text-sm font-medium">Database Connected</span>
      </div>
      <p class="text-sm text-gray-500">PostgreSQL is running and indexed for optimal performance.</p>
    </div>
  </div>

    </div> <!-- End py-6 -->
  </main>
</div> <!-- End flex layout -->

<script>
async function loadStats() {
  const tables = <?= json_encode(array_column($stats, 'tbl')) ?>;
  for (const tbl of tables) {
    try {
      const res = await fetch(`<?= $baseUrl ?>/api/table/${tbl}?limit=1`);
      const data = await res.json();
      document.getElementById(`count-${tbl}`).textContent = data.total.toLocaleString();
    } catch (e) {
      document.getElementById(`count-${tbl}`).textContent = 'Error';
    }
  }
}
loadStats();
</script>
</body>
</html>
