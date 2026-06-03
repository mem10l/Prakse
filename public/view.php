<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Northwind — Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    body { font-family: 'Inter', sans-serif; }
    .neon-card { background: rgba(26, 26, 26, 0.4); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
    .neon-card:hover { border-color: rgba(0, 229, 153, 0.3); background: rgba(26, 26, 26, 0.6); }
  </style>
</head>
<body class="bg-[#0a0a0a]">

<?php require __DIR__ . '/layout_nav.php'; ?>

  <div class="mb-12">
    <h2 class="text-4xl font-extrabold text-white tracking-tight">Dashboard</h2>
    <p class="text-gray-500 mt-2 text-lg">System-wide overview and data insights</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php
    $stats = [
      ['label' => 'Products', 'tbl' => 'products', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
      ['label' => 'Total Orders', 'tbl' => 'orders', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
      ['label' => 'Customers', 'tbl' => 'customers', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
      ['label' => 'Employees', 'tbl' => 'employees', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
      ['label' => 'Suppliers', 'tbl' => 'suppliers', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
      ['label' => 'Categories', 'tbl' => 'categories', 'icon' => 'M7 7h.01M7 11h.01M7 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01'],
    ];

    foreach ($stats as $s): ?>
    <a href="<?= $baseUrl ?>/table/<?= $s['tbl'] ?>" class="neon-card p-8 rounded-2xl group transition-all duration-300">
      <div class="flex items-center justify-between mb-6">
        <div class="w-14 h-14 bg-gray-900 border border-gray-800 rounded-xl flex items-center justify-center group-hover:border-[#00e599]/50 transition-colors">
          <svg class="w-7 h-7 text-gray-400 group-hover:text-[#00e599] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?= $s['icon'] ?>"></path></svg>
        </div>
        <div class="text-[#00e599] opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
        </div>
      </div>
      <h3 class="text-gray-400 text-sm font-bold uppercase tracking-[0.1em]"><?= $s['label'] ?></h3>
      <p class="text-4xl font-extrabold text-white mt-2" id="count-<?= $s['tbl'] ?>">...</p>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="mt-12 grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div class="neon-card p-8 rounded-2xl">
      <h3 class="text-xl font-bold text-white mb-6">Management Actions</h3>
      <div class="grid grid-cols-1 gap-4">
        <a href="<?= $baseUrl ?>/reports" class="flex items-center p-4 rounded-xl bg-white/5 border border-white/5 hover:bg-white/10 hover:border-white/10 transition-all text-gray-300">
          <div class="w-10 h-10 bg-blue-500/10 rounded-lg flex items-center justify-center mr-4 text-blue-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
          </div>
          <span class="font-medium">Business Performance Reports</span>
        </a>
        <a href="<?= $baseUrl ?>/insert" class="flex items-center p-4 rounded-xl bg-white/5 border border-white/5 hover:bg-white/10 hover:border-white/10 transition-all text-gray-300">
          <div class="w-10 h-10 bg-[#00e599]/10 rounded-lg flex items-center justify-center mr-4 text-[#00e599]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
          </div>
          <span class="font-medium">Bulk Data Import Utility</span>
        </a>
      </div>
    </div>
    
    <div class="neon-card p-8 rounded-2xl flex flex-col justify-center">
      <div class="flex items-center gap-4 mb-6">
        <div class="w-16 h-16 rounded-full bg-[#00e599]/5 flex items-center justify-center">
          <div class="w-4 h-4 rounded-full bg-[#00e599] animate-ping opacity-75"></div>
          <div class="absolute w-4 h-4 rounded-full bg-[#00e599] shadow-[0_0_15px_#00e599]"></div>
        </div>
        <div>
          <h3 class="text-xl font-bold text-white">Database Status</h3>
          <p class="text-[#00e599] font-semibold text-sm tracking-widest uppercase mt-1">Operational</p>
        </div>
      </div>
      <p class="text-gray-500 leading-relaxed">
        Server connected to <span class="text-gray-300 font-mono">Neon PostgreSQL</span>. 
        All indexes verified and optimized for high-speed retrieval. Batch inserts enabled for bulk operations.
      </p>
    </div>
  </div>

  </main>
</div>

<script>
async function loadStats() {
  const tables = <?= json_encode(array_column($stats, 'tbl')) ?>;
  for (const tbl of tables) {
    try {
      const res = await fetch(`${window.API_BASE}/api/table/${tbl}?limit=1`);
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
