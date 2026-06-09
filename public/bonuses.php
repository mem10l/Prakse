<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Northwind — Employee Bonuses</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    body { font-family: 'Inter', sans-serif; }
    .neon-card { background: rgba(26, 26, 26, 0.4); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
  </style>
</head>
<body class="bg-[#0a0a0a]">

<?php require __DIR__ . '/layout_nav.php'; ?>

  <div class="mb-10">
    <h2 class="text-4xl font-extrabold text-white tracking-tight">Employee Bonuses</h2>
    <p class="text-gray-500 mt-2 text-lg">Quarterly performance-based rewards (0.9% of sales)</p>
  </div>

  <div class="neon-card rounded-2xl overflow-hidden border border-white/5">
    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead class="bg-white/5 border-b border-white/5 text-gray-400 font-bold uppercase tracking-widest text-[10px]">
          <tr>
            <th class="px-8 py-5 text-left">Employee</th>
            <th class="px-8 py-5 text-left">Year</th>
            <th class="px-8 py-5 text-left">Quarter</th>
            <th class="px-8 py-5 text-right">Orders</th>
            <th class="px-8 py-5 text-right">Total Revenue</th>
            <th class="px-8 py-5 text-right">Calculated Bonus</th>
          </tr>
        </thead>
        <tbody id="bonus-body" class="divide-y divide-white/[0.02]">
          <!-- Will be populated dynamically -->
        </tbody>
      </table>
    </div>
    <div id="loader" class="p-20 text-center">
      <div class="inline-block w-10 h-10 border-2 border-[#00e599] border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-6 text-gray-400 font-bold tracking-widest uppercase text-xs">Calculating bonuses...</p>
    </div>
    <div id="no-data" class="hidden p-20 text-center text-gray-500">
      <div class="text-5xl mb-6">📉</div>
      <p class="font-bold text-xl text-white mb-2">No bonus data found</p>
      <p class="text-sm">Check back later or adjust the calculation period.</p>
    </div>
  </div>

  </main>
</div>

<script>
async function loadBonuses() {
  const body = document.getElementById('bonus-body');
  const loader = document.getElementById('loader');
  const noData = document.getElementById('no-data');

  try {
    const res = await fetch(`${window.API_BASE}/api/reports/bonuses`);
    const data = await res.json();
    loader.classList.add('hidden');

    if (!data.rows || data.rows.length === 0) {
      noData.classList.remove('hidden');
      return;
    }

    data.rows.forEach(row => {
      const tr = document.createElement('tr');
      tr.className = 'hover:bg-white/[0.03] transition-colors';
      
      tr.innerHTML = `
        <td class="px-8 py-5 text-white font-bold">${row.employee}</td>
        <td class="px-8 py-5 text-gray-400 font-mono text-xs">${row.year}</td>
        <td class="px-8 py-5 text-gray-400 font-mono text-xs">Q${row.quarter}</td>
        <td class="px-8 py-5 text-right text-gray-300 font-medium">${parseInt(row.order_count).toLocaleString()}</td>
        <td class="px-8 py-5 text-right text-gray-300">$${parseFloat(row.total_sum).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
        <td class="px-8 py-5 text-right text-[#00e599] font-extrabold">$${parseFloat(row.bonus).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
      `;
      body.appendChild(tr);
    });
  } catch (e) {
    loader.classList.add('hidden');
    console.error(e);
  }
}

// Initial load
loadBonuses();
</script>
</body>
</html>
