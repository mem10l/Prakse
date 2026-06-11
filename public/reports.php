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
  </style>
</head>
<body class="bg-[#0a0a0a]">

<?php require __DIR__ . '/layout_nav.php'; ?>

  <div class="mb-10">
    <h2 class="text-4xl font-extrabold text-white tracking-tight">Analytics</h2>
    <p class="text-gray-500 mt-2 text-lg">Detailed business performance metrics</p>
  </div>

  <div class="neon-card p-8 rounded-2xl mb-8">
    <div class="flex flex-wrap items-end gap-8">
      <div>
        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em] mb-3">Group By</label>
        <div class="inline-flex p-1 bg-black/40 rounded-xl border border-white/5">
          <button onclick="updateReportType('customer')" id="btn-customer" class="px-6 py-2 text-sm font-bold rounded-lg transition-all">
            Customer
          </button>
          <button onclick="updateReportType('region')" id="btn-region" class="px-6 py-2 text-sm font-bold rounded-lg transition-all">
            Region
          </button>
          <button onclick="updateReportType('employee')" id="btn-employee" class="px-6 py-2 text-sm font-bold rounded-lg transition-all">
            Employee
          </button>
          <button onclick="updateReportType('top_products')" id="btn-top_products" class="px-6 py-2 text-sm font-bold rounded-lg transition-all">
            Top Products
          </button>
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

      <button onclick="resetFilters()" class="px-4 py-2 text-sm font-bold text-[#00e599] hover:text-white transition-colors">
        Reset filters
      </button>
    </div>
  </div>

  <div class="neon-card rounded-2xl overflow-hidden border border-white/5">
    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead id="report-head" class="bg-white/5 border-b border-white/5 text-gray-400 font-bold uppercase tracking-widest text-[10px]">
          <!-- Will be populated dynamically -->
        </thead>
        <tbody id="report-body" class="divide-y divide-white/[0.02]">
          <!-- Will be populated dynamically -->
        </tbody>
      </table>
    </div>
    <div id="loader" class="hidden p-20 text-center">
      <div class="inline-block w-10 h-10 border-2 border-[#00e599] border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-6 text-gray-400 font-bold tracking-widest uppercase text-xs">Fetching report...</p>
    </div>
    <div id="no-data" class="hidden p-20 text-center text-gray-500">
      <div class="text-5xl mb-6">📉</div>
      <p class="font-bold text-xl text-white mb-2">No matching data</p>
      <p class="text-sm">Adjust your filters to see more results.</p>
    </div>
  </div>

  </main>
</div>

<script>
let currentType = 'customer';
let currentSort = 'month';
let currentOrder = 'DESC';

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
  if (currentType === 'top_products') {
    currentSort = 'year';
    currentOrder = 'DESC';
  } else {
    currentSort = 'month';
    currentOrder = 'DESC';
  }
  loadReport();
}

async function loadReport() {
  const type = currentType;
  const from = document.getElementById('from').value;
  const to = document.getElementById('to').value;

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

  body.innerHTML = '';
  loader.classList.remove('hidden');
  noData.classList.add('hidden');

  if (type === 'top_products') {
    head.innerHTML = `
      <tr>
        <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest">Reģions</th>
        <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest">Gads</th>
        <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-center">Top 5 vieta</th>
        <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest">Preces nosaukums</th>
        <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-right">Skaits</th>
        <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-right">Summa</th>
      </tr>
    `;
  } else {
    let firstColLabel = 'Customer / Company';
    let firstColKey = 'client';
    if (type === 'region') { firstColLabel = 'Region Name'; firstColKey = 'region'; }
    if (type === 'employee') { firstColLabel = 'Employee Name'; firstColKey = 'employee'; }

    const sortIcon = (col) => {
      const isActive = currentSort === col;
      const isAsc = currentOrder === 'ASC';
      
      return `
        <div class="flex flex-col ml-1.5 opacity-${isActive ? '100' : '20'} group-hover:opacity-100 transition-opacity">
          <svg class="w-2 h-2 ${isActive && isAsc ? 'text-[#00e599]' : 'text-gray-400'}" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-8 8h16z"/></svg>
          <svg class="w-2 h-2 ${isActive && !isAsc ? 'text-[#00e599]' : 'text-gray-400'} -mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 20l8-8H4z"/></svg>
        </div>
      `;
    };

    head.innerHTML = `
      <tr>
        <th class="px-8 py-5">
          <button onclick="toggleSort('${firstColKey}')" class="group flex items-center hover:text-white transition-colors uppercase tracking-widest text-[10px] font-bold">
            ${firstColLabel} ${sortIcon(firstColKey)}
          </button>
        </th>
        <th class="px-8 py-5">
          <button onclick="toggleSort('month')" class="group flex items-center hover:text-white transition-colors uppercase tracking-widest text-[10px] font-bold">
            Accounting Month ${sortIcon('month')}
          </button>
        </th>
        <th class="px-8 py-5 text-right">
          <button onclick="toggleSort('order_count')" class="group flex items-center justify-end ml-auto hover:text-white transition-colors uppercase tracking-widest text-[10px] font-bold">
            Orders ${sortIcon('order_count')}
          </button>
        </th>
        <th class="px-8 py-5 text-right">
          <button onclick="toggleSort('total_sum')" class="group flex items-center justify-end ml-auto hover:text-white transition-colors uppercase tracking-widest text-[10px] font-bold">
            Revenue ${sortIcon('total_sum')}
          </button>
        </th>
      </tr>
    `;
  }

  try {
    let url = `${window.API_BASE}/api/reports/sales?by=${type}&sort=${currentSort}&order=${currentOrder}`;
    if (type === 'top_products') {
      url = `${window.API_BASE}/api/reports/top-products`;
    }
    
    if (from) url += (url.includes('?') ? '&' : '?') + `from=${from}`;
    if (to) url += (url.includes('?') ? '&' : '?') + `to=${to}`;

    const res = await fetch(url);
    const data = await res.json();
    loader.classList.add('hidden');

    if (!data.rows || data.rows.length === 0) {
      noData.classList.remove('hidden');
      return;
    }

    data.rows.forEach(row => {
      const tr = document.createElement('tr');
      tr.className = 'hover:bg-white/[0.03] transition-colors';
      
      if (type === 'top_products') {
        tr.innerHTML = `
          <td class="px-8 py-5 text-white font-bold">${row.region}</td>
          <td class="px-8 py-5 text-gray-400 font-mono text-xs">${row.year}</td>
          <td class="px-8 py-5 text-center"><span class="px-2 py-1 rounded bg-white/5 text-xs font-bold ${row.rank == 1 ? 'text-yellow-400' : 'text-gray-400'}">${row.rank}</span></td>
          <td class="px-8 py-5 text-gray-300 font-medium">${row.productname}</td>
          <td class="px-8 py-5 text-right text-gray-300">${parseInt(row.total_quantity).toLocaleString()}</td>
          <td class="px-8 py-5 text-right text-[#00e599] font-extrabold">$${parseFloat(row.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
        `;
      } else {
        let firstCol = row.client;
        if (type === 'region') firstCol = row.region;
        if (type === 'employee') firstCol = row.employee;
        
        tr.innerHTML = `
          <td class="px-8 py-5 text-white font-bold">${firstCol}</td>
          <td class="px-8 py-5 text-gray-400 font-mono text-xs uppercase tracking-wider">${row.month}</td>
          <td class="px-8 py-5 text-right text-gray-300 font-medium">${parseInt(row.order_count).toLocaleString()}</td>
          <td class="px-8 py-5 text-right text-[#00e599] font-extrabold">$${parseFloat(row.total_sum).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
        `;
      }
      body.appendChild(tr);
    });
  } catch (e) {
    loader.classList.add('hidden');
    console.error(e);
  }
}

loadReport();
</script>
</body>
</html>
