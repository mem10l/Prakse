<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Northwind — Reports</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    body { font-family: 'Inter', sans-serif; }
    .neon-card { background: rgba(26, 26, 26, 0.4); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
    /* Prevent table flickering */
    #report-table { min-height: 400px; }
  </style>
</head>
<body class="bg-[#0a0a0a]">

<?php require __DIR__ . '/layout_nav.php'; ?>

<main class="max-w-7xl mx-auto px-6 py-12">
  <div class="mb-10">
    <h2 class="text-4xl font-extrabold text-white tracking-tight">Analytics</h2>
    <p class="text-gray-500 mt-2 text-lg">Detailed business performance metrics</p>
  </div>

  <div class="neon-card p-8 rounded-2xl mb-8">
    <div class="flex flex-wrap items-end gap-8">
      <div>
        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em] mb-3">Group By</label>
        <div class="inline-flex p-1 bg-black/40 rounded-xl border border-white/5">
          <button onclick="updateReportType('customer')" id="btn-customer" class="px-6 py-2 text-sm font-bold rounded-lg transition-all">Customer</button>
          <button onclick="updateReportType('region')" id="btn-region" class="px-6 py-2 text-sm font-bold rounded-lg transition-all">Region</button>
          <button onclick="updateReportType('employee')" id="btn-employee" class="px-6 py-2 text-sm font-bold rounded-lg transition-all">Employee</button>
          <button onclick="updateReportType('top_products')" id="btn-top_products" class="px-6 py-2 text-sm font-bold rounded-lg transition-all">Top Products</button>
        </div>
      </div>
      
      <div>
        <label for="from" class="block text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em] mb-3">Start Date</label>
        <input type="date" id="from" onchange="loadReport()" class="px-4 py-2 bg-black/40 border border-white/10 rounded-xl focus:ring-2 focus:ring-[#00e599] outline-none text-sm text-white">
      </div>

      <div>
        <label for="to" class="block text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em] mb-3">End Date</label>
        <input type="date" id="to" onchange="loadReport()" class="px-4 py-2 bg-black/40 border border-white/10 rounded-xl focus:ring-2 focus:ring-[#00e599] outline-none text-sm text-white">
      </div>

      <button onclick="resetFilters()" class="px-4 py-2 text-sm font-bold text-[#00e599] hover:text-white transition-colors">Reset filters</button>
    </div>
  </div>

  <div class="neon-card rounded-2xl overflow-hidden border border-white/5" id="report-table">
    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead id="report-head" class="bg-white/5 border-b border-white/5 text-gray-400 font-bold uppercase tracking-widest text-[10px]">
          <!-- Populated only after data arrives -->
        </thead>
        <tbody id="report-body" class="divide-y divide-white/[0.02]">
          <!-- Populated only after data arrives -->
        </tbody>
      </table>
    </div>
    <div id="loader" class="hidden p-20 text-center">
      <div class="inline-block w-10 h-10 border-2 border-[#00e599] border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-6 text-gray-400 font-bold tracking-widest uppercase text-xs">Fetching report data...</p>
    </div>
    <div id="no-data" class="hidden p-20 text-center text-gray-500">
      <div class="text-5xl mb-6">📉</div>
      <p class="font-bold text-xl text-white mb-2">No matching data</p>
      <p class="text-sm">Adjust your filters or check for errors.</p>
    </div>
  </div>
</main>

<script>
let currentType = 'top_products'; // Make Top Products default for easier testing
let currentSort = 'year';
let currentOrder = 'DESC';
let currentRequestId = 0; // To prevent race conditions

function updateReportType(type) {
  currentType = type;
  if (type === 'top_products') {
    currentSort = 'year';
    currentOrder = 'DESC';
  } else {
    currentSort = type === 'customer' ? 'client' : (type === 'region' ? 'region' : 'employee');
    currentOrder = 'ASC';
  }
  loadReport();
}

function toggleSort(col) {
  if (currentType === 'top_products') return;
  if (currentSort === col) {
    currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
  } else {
    currentSort = col;
    currentOrder = 'ASC';
  }
  loadReport();
}

function resetFilters() {
  document.getElementById('from').value = '';
  document.getElementById('to').value = '';
  updateReportType(currentType);
}

// Robust data access helper
function getVal(obj, ...keys) {
  for (const k of keys) {
    const lowKey = k.toLowerCase();
    for (const actualKey in obj) {
      if (actualKey.toLowerCase() === lowKey) return obj[actualKey];
    }
  }
  return null;
}

async function loadReport() {
  const requestId = ++currentRequestId;
  const type = currentType;
  const from = document.getElementById('from').value;
  const to = document.getElementById('to').value;

  // UI State Cleanup
  const activeBtn = 'bg-[#00e599] text-black shadow-[0_0_15px_rgba(0,229,153,0.3)]';
  const inactiveBtn = 'text-gray-500 hover:text-gray-300';
  document.getElementById('btn-customer').className = `px-6 py-2 text-sm font-bold rounded-lg transition-all ${type === 'customer' ? activeBtn : inactiveBtn}`;
  document.getElementById('btn-region').className = `px-6 py-2 text-sm font-bold rounded-lg transition-all ${type === 'region' ? activeBtn : inactiveBtn}`;
  document.getElementById('btn-employee').className = `px-6 py-2 text-sm font-bold rounded-lg transition-all ${type === 'employee' ? activeBtn : inactiveBtn}`;
  document.getElementById('btn-top_products').className = `px-6 py-2 text-sm font-bold rounded-lg transition-all ${type === 'top_products' ? activeBtn : inactiveBtn}`;

  const head = document.getElementById('report-head');
  const body = document.getElementById('report-body');
  const loader = document.getElementById('loader');
  const noData = document.getElementById('no-data');

  // Clear table immediately to prevent misalignment from previous reports
  head.innerHTML = '';
  body.innerHTML = '';
  loader.classList.remove('hidden');
  noData.classList.add('hidden');

  try {
    let url = type === 'top_products' ? `${window.API_BASE}/api/reports/top-products` : `${window.API_BASE}/api/reports/sales?by=${type}&sort=${currentSort}&order=${currentOrder}`;
    if (from) url += (url.includes('?') ? '&' : '?') + `from=${from}`;
    if (to) url += (url.includes('?') ? '&' : '?') + `to=${to}`;

    const res = await fetch(url);
    const data = await res.json();

    // Ignore if a newer request has been started
    if (requestId !== currentRequestId) return;

    loader.classList.add('hidden');

    if (data.error) {
      noData.classList.remove('hidden');
      noData.querySelector('p.text-sm').textContent = 'Error: ' + data.error;
      return;
    }

    if (!data.rows || data.rows.length === 0) {
      noData.classList.remove('hidden');
      noData.querySelector('p.text-sm').textContent = 'No data found for this selection.';
      return;
    }

    // Render Headers AND Rows together to ensure sync
    if (type === 'top_products') {
      head.innerHTML = `
        <tr>
          <th class="px-8 py-5 text-left">Region</th>
          <th class="px-8 py-5 text-left">Year</th>
          <th class="px-8 py-5 text-center">Rank</th>
          <th class="px-8 py-5 text-left">Product Name</th>
          <th class="px-8 py-5 text-right">Quantity</th>
          <th class="px-8 py-5 text-right">Revenue</th>
        </tr>
      `;
      data.rows.forEach(row => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-white/[0.03] transition-colors';
        const rank = getVal(row, 'rank') || '-';
        tr.innerHTML = `
          <td class="px-8 py-5 text-white font-bold">${getVal(row, 'region') || 'Unknown'}</td>
          <td class="px-8 py-5 text-gray-400 font-mono text-xs">${getVal(row, 'year') || 'N/A'}</td>
          <td class="px-8 py-5 text-center">
            <span class="px-2 py-1 rounded bg-white/5 text-xs font-bold ${rank == 1 ? 'text-yellow-400' : 'text-gray-400'}">${rank}</span>
          </td>
          <td class="px-8 py-5 text-gray-300 font-medium">${getVal(row, 'productname', 'ProductName') || 'Unknown Product'}</td>
          <td class="px-8 py-5 text-right text-gray-400 font-mono">${parseInt(getVal(row, 'total_quantity', 'quantity') || 0).toLocaleString()}</td>
          <td class="px-8 py-5 text-right text-[#00e599] font-bold">$${parseFloat(getVal(row, 'total_amount', 'amount') || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
        `;
        body.appendChild(tr);
      });
    } else {
      let firstColLabel = type === 'customer' ? 'Customer' : (type === 'region' ? 'Region' : 'Employee');
      let firstColKey = type === 'customer' ? 'client' : (type === 'region' ? 'region' : 'employee');
      
      const sortIcon = (col) => {
        const isActive = currentSort === col;
        const isAsc = currentOrder === 'ASC';
        return `<span class="ml-1.5 ${isActive ? 'text-[#00e599]' : 'opacity-20'}">${isAsc ? '▲' : '▼'}</span>`;
      };

      head.innerHTML = `
        <tr>
          <th class="px-8 py-5"><button onclick="toggleSort('${firstColKey}')" class="uppercase">${firstColLabel} ${sortIcon(firstColKey)}</button></th>
          <th class="px-8 py-5"><button onclick="toggleSort('month')" class="uppercase">Month ${sortIcon('month')}</button></th>
          <th class="px-8 py-5 text-right"><button onclick="toggleSort('order_count')" class="ml-auto uppercase">Orders ${sortIcon('order_count')}</button></th>
          <th class="px-8 py-5 text-right"><button onclick="toggleSort('total_sum')" class="ml-auto uppercase">Revenue ${sortIcon('total_sum')}</button></th>
        </tr>
      `;
      data.rows.forEach(row => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-white/[0.03] transition-colors';
        tr.innerHTML = `
          <td class="px-8 py-5 text-white font-bold">${getVal(row, 'client', 'region', 'employee') || 'Unknown'}</td>
          <td class="px-8 py-5 text-gray-400 font-mono text-xs uppercase tracking-wider">${getVal(row, 'month') || 'N/A'}</td>
          <td class="px-8 py-5 text-right text-gray-300 font-medium">${parseInt(getVal(row, 'order_count') || 0).toLocaleString()}</td>
          <td class="px-8 py-5 text-right text-[#00e599] font-extrabold">$${parseFloat(getVal(row, 'total_sum') || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
        `;
        body.appendChild(tr);
      });
    }
  } catch (e) {
    if (requestId === currentRequestId) {
      loader.classList.add('hidden');
      console.error(e);
    }
  }
}

// Initial load
loadReport();
</script>
</body>
</html>
