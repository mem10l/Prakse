<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Northwind — <?= ucfirst($tableName) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .vs-container { height: calc(100vh - 250px); overflow-y: auto; position: relative; }
    .vs-spacer    { position: absolute; top: 0; left: 0; width: 100%; pointer-events: none; }
    .vs-visible   { position: absolute; top: 0; left: 0; width: 100%; }
  </style>
</head>
<body class="bg-gray-100">

<?php require __DIR__ . '/layout_sidebar.php'; ?>

  <div class="flex items-center justify-between mb-8">
    <div>
      <h2 class="text-3xl font-bold text-gray-900 capitalize"><?= htmlspecialchars($tableName) ?></h2>
      <p class="text-gray-500 mt-1" id="table-count">Loading rows...</p>
    </div>
    <a href="<?= $baseUrl ?>/insert" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 shadow-sm transition">
      + Add Data
    </a>
  </div>

  <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-200">
        <tr id="table-head">
          <!-- Populated dynamically -->
        </tr>
      </thead>
    </table>
    <div class="vs-container" id="vs-scroll" data-table="<?= htmlspecialchars($tableName) ?>"></div>
  </div>

    </div> <!-- End py-6 -->
  </main>
</div> <!-- End flex layout -->

<script>
const API_BASE = '<?= $baseUrl ?>';
const TABLE_NAME = '<?= $tableName ?>';
const ROW_H = 48, OVERSCAN = 10;
const cache = {};
const PAGE_SIZE = 100;

function fmtCell(v) {
  if (v === null || v === undefined) return '';
  if (typeof v === 'string' && /^\d{4}-\d{2}-\d{2}/.test(v)) {
    return new Date(v).toLocaleDateString();
  }
  return String(v);
}

async function fetchPage(page) {
  if (!cache[page]) {
    const res = await fetch(`${API_BASE}/api/table/${TABLE_NAME}?page=${page}&limit=${PAGE_SIZE}`);
    const data = await res.json();
    cache.total = data.total;
    cache[page] = data.rows;
  }
}

async function init() {
  const el = document.getElementById('vs-scroll');
  const countEl = document.getElementById('table-count');
  const head = document.getElementById('table-head');

  try {
    await fetchPage(1);
    const total = cache.total;
    const firstRow = cache[1][0] || {};
    const cols = Object.keys(firstRow);

    countEl.textContent = total.toLocaleString() + ' records found';
    head.innerHTML = cols.map(c => 
      `<th class="px-6 py-4 text-left font-semibold text-gray-600 uppercase tracking-wider">${c.replace('id', ' ID')}</th>`
    ).join('');

    const spacer = document.createElement('div');
    spacer.className = 'vs-spacer';
    spacer.style.height = (total * ROW_H) + 'px';
    el.appendChild(spacer);

    const wrap = document.createElement('div');
    wrap.className = 'vs-visible';
    el.appendChild(wrap);

    async function render() {
      const scrollTop = el.scrollTop;
      const start = Math.max(0, Math.floor(scrollTop / ROW_H) - OVERSCAN);
      const end = Math.min(total, Math.ceil((el.clientHeight + scrollTop) / ROW_H) + OVERSCAN);

      const startPage = Math.floor(start / PAGE_SIZE) + 1;
      const endPage = Math.floor((end - 1) / PAGE_SIZE) + 1;
      for (let p = startPage; p <= endPage; p++) await fetchPage(p);

      wrap.style.top = (start * ROW_H) + 'px';
      const t = document.createElement('table');
      t.className = 'w-full text-sm';
      const tb = document.createElement('tbody');

      for (let i = start; i < end; i++) {
        const page = Math.floor(i / PAGE_SIZE) + 1;
        const row = cache[page][i % PAGE_SIZE];
        const tr = document.createElement('tr');
        tr.className = 'border-b border-gray-100 hover:bg-blue-50/50 transition-colors';
        tr.style.height = ROW_H + 'px';
        tr.innerHTML = cols.map(c => 
          `<td class="px-6 py-3 truncate max-w-xs text-gray-700">${fmtCell(row[c])}</td>`
        ).join('');
        tb.appendChild(tr);
      }
      t.appendChild(tb);
      wrap.innerHTML = '';
      wrap.appendChild(t);
    }

    el.addEventListener('scroll', render);
    render();
  } catch (e) {
    console.error(e);
    countEl.textContent = 'Error loading data';
    countEl.className = 'text-red-500';
  }
}
init();
</script>
</body>
</html>
