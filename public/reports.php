<?php declare(strict_types=1); ?>
<?php
$baseUrl = rtrim(str_replace("\\", "/", dirname($_SERVER['SCRIPT_NAME'])), "/.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Northwind — Reports</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
<div class="max-w-7xl mx-auto p-8">

  <div class="flex items-center justify-between mb-2">
    <h1 class="text-4xl font-bold text-gray-900">Sales Reports</h1>
    <div class="space-x-4">
      <a href="<?= $baseUrl ?>/view" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-semibold rounded-lg hover:bg-gray-300 transition">
        Back to Viewer
      </a>
    </div>
  </div>
  <p class="text-gray-500 mb-8">Monthly order summaries and performance metrics</p>

  <div class="mb-8 flex flex-wrap items-end gap-4">
    <div>
      <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Group By</label>
      <div class="flex space-x-2">
        <button onclick="updateReportType('customer')" id="btn-customer" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow hover:bg-blue-700 transition">
          Customer
        </button>
        <button onclick="updateReportType('region')" id="btn-region" class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow hover:bg-gray-300 transition">
          Region
        </button>
      </div>
    </div>
    
    <div>
      <label for="from" class="block text-xs font-bold text-gray-500 uppercase mb-1">From</label>
      <input type="date" id="from" onchange="loadReport()" class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
    </div>

    <div>
      <label for="to" class="block text-xs font-bold text-gray-500 uppercase mb-1">To</label>
      <input type="date" id="to" onchange="loadReport()" class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
    </div>

    <button onclick="resetFilters()" class="px-4 py-2 text-sm text-blue-600 hover:underline">
      Clear Filters
    </button>
  </div>

  <div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
      <thead id="report-head" class="bg-gray-100 border-b">
        <!-- Will be populated dynamically -->
      </thead>
      <tbody id="report-body" class="divide-y divide-gray-200">
        <!-- Will be populated dynamically -->
      </tbody>
    </table>
    <div id="loader" class="hidden p-8 text-center text-gray-500">
      Loading report data...
    </div>
    <div id="no-data" class="hidden p-8 text-center text-gray-500">
      No data found for this report.
    </div>
  </div>
</div>

<script>
const API_BASE = '<?= rtrim(str_replace("\\", "/", dirname($_SERVER['SCRIPT_NAME'])), "/.") ?>';
let currentType = 'customer';

function updateReportType(type) {
  currentType = type;
  loadReport();
}

function resetFilters() {
  document.getElementById('from').value = '';
  document.getElementById('to').value = '';
  loadReport();
}

async function loadReport() {
  const type = currentType;
  const from = document.getElementById('from').value;
  const to = document.getElementById('to').value;

  // Update UI buttons
  document.getElementById('btn-customer').className = type === 'customer' ? 'px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow hover:bg-blue-700 transition' : 'px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow hover:bg-gray-300 transition';
  document.getElementById('btn-region').className = type === 'region' ? 'px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow hover:bg-blue-700 transition' : 'px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow hover:bg-gray-300 transition';

  const head = document.getElementById('report-head');
  const body = document.getElementById('report-body');
  const loader = document.getElementById('loader');
  const noData = document.getElementById('no-data');

  body.innerHTML = '';
  loader.classList.remove('hidden');
  noData.classList.add('hidden');

  if (type === 'customer') {
    head.innerHTML = `
      <tr>
        <th class="px-6 py-3 text-left font-bold text-gray-700 uppercase tracking-wider">Client</th>
        <th class="px-6 py-3 text-left font-bold text-gray-700 uppercase tracking-wider">Month</th>
        <th class="px-6 py-3 text-right font-bold text-gray-700 uppercase tracking-wider">Orders</th>
        <th class="px-6 py-3 text-right font-bold text-gray-700 uppercase tracking-wider">Total Sum</th>
      </tr>
    `;
  } else {
    head.innerHTML = `
      <tr>
        <th class="px-6 py-3 text-left font-bold text-gray-700 uppercase tracking-wider">Region</th>
        <th class="px-6 py-3 text-left font-bold text-gray-700 uppercase tracking-wider">Month</th>
        <th class="px-6 py-3 text-right font-bold text-gray-700 uppercase tracking-wider">Orders</th>
        <th class="px-6 py-3 text-right font-bold text-gray-700 uppercase tracking-wider">Total Sum</th>
      </tr>
    `;
  }

  try {
    let url = `${API_BASE}/api/reports/sales?by=${type}`;
    if (from) url += `&from=${from}`;
    if (to) url += `&to=${to}`;

    const res = await fetch(url);
    const data = await res.json();
    loader.classList.add('hidden');

    if (!data.rows || data.rows.length === 0) {
      noData.classList.remove('hidden');
      return;
    }

    data.rows.forEach(row => {
      const tr = document.createElement('tr');
      tr.className = 'hover:bg-gray-50 transition-colors';
      
      const firstCol = type === 'customer' ? row.client : row.region;
      
      tr.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-medium">${firstCol}</td>
        <td class="px-6 py-4 whitespace-nowrap text-gray-600">${row.month}</td>
        <td class="px-6 py-4 whitespace-nowrap text-right text-gray-600">${row.order_count}</td>
        <td class="px-6 py-4 whitespace-nowrap text-right text-gray-900 font-semibold">$${parseFloat(row.total_sum).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
      `;
      body.appendChild(tr);
    });
  } catch (e) {
    loader.classList.add('hidden');
    console.error(e);
    alert('Failed to load report data');
  }
}

// Initial load
loadReport();
</script>
</body>
</html>
