<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Northwind — <?= ucfirst($tableName) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    body { font-family: 'Inter', sans-serif; }
    .neon-card { background: rgba(26, 26, 26, 0.4); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
    .vs-container { height: calc(100vh - 350px); overflow-y: auto; position: relative; scrollbar-width: thin; scrollbar-color: #333 transparent; }
    .vs-container::-webkit-scrollbar { width: 6px; }
    .vs-container::-webkit-scrollbar-thumb { background-color: #333; border-radius: 10px; }
    .vs-spacer    { position: absolute; top: 0; left: 0; width: 100%; pointer-events: none; }
    .vs-visible   { position: absolute; top: 0; left: 0; width: 100%; }
  </style>
</head>
<body class="bg-[#0a0a0a]">

<?php require __DIR__ . '/layout_nav.php'; ?>

  <div class="flex items-end justify-between mb-10">
    <div>
      <h2 class="text-4xl font-extrabold text-white tracking-tight capitalize"><?= htmlspecialchars($tableName) ?></h2>
      <p class="text-gray-500 mt-2 text-lg font-medium" id="table-count">Loading dataset...</p>
    </div>
    <div class="flex gap-4">
      <a href="<?= $baseUrl ?>/insert" class="px-6 py-3 bg-[#00e599] text-black font-bold rounded-xl hover:bg-[#00cc88] transition-all shadow-[0_0_20px_rgba(0,229,153,0.2)]">
        + Add Record
      </a>
    </div>
  </div>

  <div class="neon-card rounded-2xl overflow-hidden border border-white/5">
    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead class="bg-white/5 border-b border-white/5 text-gray-400 font-bold uppercase tracking-widest text-[10px]">
          <tr id="table-head">
            <!-- Populated dynamically -->
          </tr>
        </thead>
      </table>
    </div>
    <div class="vs-container" id="vs-scroll" data-table="<?= htmlspecialchars($tableName) ?>"></div>
  </div>

  </main>
</div>

<script>
const ROW_H = 56, OVERSCAN = 15;
const cache = { total: 0 };
const PAGE_SIZE = 100;
const TABLE_NAME = '<?= $tableName ?>';
let currentSort = null;
let currentOrder = 'ASC';

function fmtCell(v) {
  if (v === null || v === undefined) return '';
  if (typeof v === 'string' && /^\d{4}-\d{2}-\d{2}/.test(v)) {
    return `<span class="text-blue-400/80 font-mono">${new Date(v).toLocaleDateString()}</span>`;
  }
  if (typeof v === 'number' || (!isNaN(v) && !isNaN(parseFloat(v)))) {
    return `<span class="text-white font-medium">${parseFloat(v).toLocaleString()}</span>`;
  }
  return `<span class="text-gray-400">${String(v)}</span>`;
}

async function fetchPage(page) {
  if (!cache[page]) {
    let url = `${API_BASE}/api/table/${TABLE_NAME}?page=${page}&limit=${PAGE_SIZE}`;
    if (currentSort) {
      url += `&sort=${currentSort}&order=${currentOrder}`;
    }
    const res = await fetch(url);
    if (!res.ok) {
        const text = await res.text();
        throw new Error(`API Error: ${res.status}`);
    }
    const data = await res.json();
    cache.total = data.total;
    cache[page] = data.rows;
  }
}

let renderFn = null;

async function toggleSort(col) {
  if (currentSort === col) {
    currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
  } else {
    currentSort = col;
    currentOrder = 'ASC';
  }
  // Clear cache and re-render
  Object.keys(cache).forEach(k => { if (k !== 'total') delete cache[k]; });
  const el = document.getElementById('vs-scroll');
  el.scrollTop = 0;
  await fetchPage(1);
  updateHeaders();
  if (renderFn) renderFn();
}

function updateHeaders() {
  const head = document.getElementById('table-head');
  const firstRow = cache[1] ? cache[1][0] : null;
  if (!firstRow) return;
  const cols = Object.keys(firstRow);

  const sortIcon = (col) => {
    if (currentSort !== col) return '↕️';
    return currentOrder === 'ASC' ? '🔼' : '🔽';
  };

  head.innerHTML = cols.map(c => 
    `<th class="px-6 py-4">
      <button onclick="toggleSort('${c}')" class="flex items-center gap-2 hover:text-white transition-colors uppercase tracking-widest text-[10px] font-bold whitespace-nowrap">
        ${c.replace('id', ' ID')} <span>${sortIcon(c)}</span>
      </button>
    </th>`
  ).join('');
}

async function init() {
  const el = document.getElementById('vs-scroll');
  const countEl = document.getElementById('table-count');

  try {
    await fetchPage(1);
    const total = cache.total;
    const firstRow = cache[1][0] || {};
    const cols = Object.keys(firstRow);

    countEl.textContent = `${total.toLocaleString()} total records`;
    updateHeaders();

    const spacer = document.createElement('div');
    spacer.className = 'vs-spacer';
    spacer.style.height = (total * ROW_H) + 'px';
    el.appendChild(spacer);

    const wrap = document.createElement('div');
    wrap.className = 'vs-visible';
    el.appendChild(wrap);

    renderFn = async function render() {
      const scrollTop = el.scrollTop;
      const start = Math.max(0, Math.floor(scrollTop / ROW_H) - OVERSCAN);
      const end = Math.min(total, Math.ceil((el.clientHeight + scrollTop) / ROW_H) + OVERSCAN);

      const startPage = Math.floor(start / PAGE_SIZE) + 1;
      const endPage = Math.floor((end - 1) / PAGE_SIZE) + 1;
      
      const pagePromises = [];
      for (let p = startPage; p <= endPage; p++) if (!cache[p]) pagePromises.push(fetchPage(p));
      if (pagePromises.length > 0) await Promise.all(pagePromises);

      wrap.style.top = (start * ROW_H) + 'px';
      const t = document.createElement('table');
      t.className = 'w-full text-sm text-left';
      const tb = document.createElement('tbody');

      for (let i = start; i < end; i++) {
        const pageNum = Math.floor(i / PAGE_SIZE) + 1;
        const row = cache[pageNum] ? cache[pageNum][i % PAGE_SIZE] : null;
        if (!row) continue;
        
        const tr = document.createElement('tr');
        tr.className = 'border-b border-white/[0.02] hover:bg-white/[0.03] transition-colors';
        tr.style.height = ROW_H + 'px';
        tr.innerHTML = cols.map(c => 
          `<td class="px-6 py-2 truncate max-w-xs">${fmtCell(row[c])}</td>`
        ).join('');
        tb.appendChild(tr);
      }
      t.appendChild(tb);
      wrap.innerHTML = '';
      wrap.appendChild(t);
    }

    el.addEventListener('scroll', renderFn);
    renderFn();
  } catch (e) {
    console.error(e);
    countEl.textContent = 'Connection Error';
    countEl.className = 'text-red-500';
  }
}
init();
</script>
</body>
</html>
