<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Northwind — Reports</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php require __DIR__ . '/layout_sidebar.php'; ?>

  <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-900">Sales Reports</h2>
    <p class="text-gray-500 mt-1">Analyze monthly revenue and order volume</p>
  </div>

  <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
    <div class="flex flex-wrap items-end gap-6">
      <div>
        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 tracking-wider">Group Data By</label>
        <div class="inline-flex p-1 bg-gray-100 rounded-lg">
          <button onclick="updateReportType('customer')" id="btn-customer" class="px-4 py-2 text-sm font-semibold rounded-md transition-all">
            Customer
          </button>
          <button onclick="updateReportType('region')" id="btn-region" class="px-4 py-2 text-sm font-semibold rounded-md transition-all">
            Region
          </button>
        </div>
      </div>
      
      <div>
        <label for="from" class="block text-xs font-bold text-gray-500 uppercase mb-2 tracking-wider">Start Date</label>
        <input type="date" id="from" onchange="loadReport()" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
      </div>

      <div>
        <label for="to" class="block text-xs font-bold text-gray-500 uppercase mb-2 tracking-wider">End Date</label>
        <input type="date" id="to" onchange="loadReport()" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
      </div>

      <button onclick="resetFilters()" class="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors">
        Reset Filters
      </button>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead id="report-head" class="bg-gray-50 border-b border-gray-200">
          <!-- Will be populated dynamically -->
        </thead>
        <tbody id="report-body" class="divide-y divide-gray-100">
          <!-- Will be populated dynamically -->
        </tbody>
      </table>
    </div>
    <div id="loader" class="hidden p-12 text-center">
      <div class="inline-block w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-gray-500 font-medium">Loading report data...</p>
    </div>
    <div id="no-data" class="hidden p-12 text-center text-gray-500">
      <span class="text-4xl block mb-4">🔍</span>
      <p class="font-medium text-lg">No results found</p>
      <p class="text-sm">Try adjusting your date range or grouping.</p>
    </div>
  </div>

    </div> <!-- End py-6 -->
  </main>
</div> <!-- End flex layout -->

<script>
const API_BASE = '<?= $baseUrl ?>';
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
  const activeClass = 'bg-white text-blue-600 shadow-sm';
  const inactiveClass = 'text-gray-500 hover:text-gray-700';
  
  document.getElementById('btn-customer').className = `px-4 py-2 text-sm font-semibold rounded-md transition-all ${type === 'customer' ? activeClass : inactiveClass}`;
  document.getElementById('btn-region').className = `px-4 py-2 text-sm font-semibold rounded-md transition-all ${type === 'region' ? activeClass : inactiveClass}`;

  const head = document.getElementById('report-head');
  const body = document.getElementById('report-body');
  const loader = document.getElementById('loader');
  const noData = document.getElementById('no-data');

  body.innerHTML = '';
  loader.classList.remove('hidden');
  noData.classList.add('hidden');

  const firstColLabel = type === 'customer' ? 'Client / Company' : 'Region';
  head.innerHTML = `
    <tr>
      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase tracking-wider">${firstColLabel}</th>
      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase tracking-wider">Month</th>
      <th class="px-6 py-4 text-right font-bold text-gray-600 uppercase tracking-wider">Order Volume</th>
      <th class="px-6 py-4 text-right font-bold text-gray-600 uppercase tracking-wider">Total Revenue</th>
    </tr>
  `;

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
      tr.className = 'hover:bg-blue-50/30 transition-colors';
      
      const firstCol = type === 'customer' ? row.client : row.region;
      
      tr.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-semibold">${firstCol}</td>
        <td class="px-6 py-4 whitespace-nowrap text-gray-600 font-medium">${row.month}</td>
        <td class="px-6 py-4 whitespace-nowrap text-right text-gray-600">${parseInt(row.order_count).toLocaleString()}</td>
        <td class="px-6 py-4 whitespace-nowrap text-right text-blue-600 font-bold">$${parseFloat(row.total_sum).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
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
