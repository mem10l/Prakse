<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Northwind — Viewer</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .vs-container { height: 400px; overflow-y: auto; position: relative; }
    .vs-spacer    { position: absolute; top: 0; left: 0; width: 100%; pointer-events: none; }
    .vs-visible   { position: absolute; top: 0; left: 0; width: 100%; }
  </style>
</head>
<body class="bg-gray-50">
<div class="max-w-7xl mx-auto p-8">

  <div class="flex items-center justify-between mb-2">
    <h1 class="text-4xl font-bold text-gray-900">Northwind Database</h1>
    <a href="/insert" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">
      + Insert data
    </a>
  </div>
  <p class="text-gray-500 mb-8">Order management system for a wholesale food distributor</p>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

    <?php
    $tables = [
      'categories'  => ['categoryid',  'categoryname', 'description'],
      'products'    => ['productid',   'productname',  'unitprice'],
      'customers'   => ['customerid',  'companyname',  'city'],
      'orders'      => ['orderid',     'customerid',   'orderdate'],
      'employees'   => ['employeeid',  'firstname',    'lastname'],
      'suppliers'   => ['supplierid',  'companyname',  'country'],
      'shippers'    => ['shipperid',   'companyname',  'phone'],
    ];
    foreach ($tables as $tbl => $cols): ?>
    <div class="bg-white rounded-lg shadow p-6">
      <h2 class="text-xl font-bold text-gray-900 mb-1 capitalize"><?= htmlspecialchars($tbl) ?></h2>
      <p class="text-xs text-gray-400 mb-3" id="<?= $tbl ?>-count">Loading…</p>
      <table class="w-full text-sm">
        <thead class="bg-gray-100">
          <tr>
            <?php foreach ($cols as $col): ?>
            <th class="px-4 py-2 text-left capitalize"><?= htmlspecialchars(str_replace('id', ' ID', $col)) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
      </table>
      <div class="vs-container"
           id="<?= $tbl ?>-scroll"
           data-table="<?= $tbl ?>"
           data-cols="<?= implode(',', $cols) ?>">
      </div>
    </div>
    <?php endforeach; ?>

  </div>
</div>

<script>
const ROW_H = 37, OVERSCAN = 5;
// Cache: tableName -> { total, pages: { pageNum: rows[] } }
const cache = {};
const PAGE_SIZE = 200;

function fmtCell(v) {
  if (v === null || v === undefined) return '';
  if (typeof v === 'string' && /^\d{4}-\d{2}-\d{2}/.test(v)) {
    return new Date(v).toLocaleDateString();
  }
  return String(v);
}

async function fetchPage(table, page) {
  if (!cache[table]) cache[table] = { total: 0, pages: {} };
  if (cache[table].pages[page]) return;

  const res  = await fetch(`/api/table/${table}?page=${page}&limit=${PAGE_SIZE}`);
  const data = await res.json();
  cache[table].total = data.total;
  cache[table].pages[page] = data.rows;
}

function getRow(table, index) {
  const page = Math.floor(index / PAGE_SIZE) + 1;
  const rows = cache[table]?.pages[page];
  if (!rows) return null;
  return rows[index % PAGE_SIZE] ?? null;
}

async function init(containerId) {
  const el    = document.getElementById(containerId);
  const table = el.dataset.table;
  const cols  = el.dataset.cols.split(',');

  // Fetch first page to get total
  await fetchPage(table, 1);
  const total = cache[table].total;
  document.getElementById(table + '-count').textContent = total.toLocaleString() + ' rows';

  const spacer = document.createElement('div');
  spacer.className = 'vs-spacer';
  spacer.style.height = (total * ROW_H) + 'px';
  el.appendChild(spacer);

  const wrap = document.createElement('div');
  wrap.className = 'vs-visible';
  el.appendChild(wrap);

  async function render() {
    const scrollTop = el.scrollTop;
    const height    = el.clientHeight;
    const start     = Math.max(0, Math.floor(scrollTop / ROW_H) - OVERSCAN);
    const end       = Math.min(total, Math.ceil((scrollTop + height) / ROW_H) + OVERSCAN);

    // Pre-fetch any needed pages
    const startPage = Math.floor(start / PAGE_SIZE) + 1;
    const endPage   = Math.floor((end - 1) / PAGE_SIZE) + 1;
    await Promise.all(
      Array.from({ length: endPage - startPage + 1 }, (_, i) => fetchPage(table, startPage + i))
    );

    wrap.style.top = (start * ROW_H) + 'px';
    const t  = document.createElement('table');
    t.className = 'w-full text-sm';
    const tb = document.createElement('tbody');

    for (let i = start; i < end; i++) {
      const row = getRow(table, i);
      const tr  = document.createElement('tr');
      tr.className  = 'border-t hover:bg-gray-50';
      tr.style.height = ROW_H + 'px';
      tr.innerHTML = cols.map(c =>
        `<td class="px-4 py-2 truncate max-w-xs">${fmtCell(row ? row[c] : '…')}</td>`
      ).join('');
      tb.appendChild(tr);
    }
    t.appendChild(tb);
    wrap.innerHTML = '';
    wrap.appendChild(t);
  }

  el.addEventListener('scroll', render);
  render();
}

<?php foreach (array_keys($tables) as $tbl): ?>
init('<?= $tbl ?>-scroll');
<?php endforeach; ?>
</script>
</body>
</html>
