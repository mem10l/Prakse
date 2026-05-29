<?php
declare(strict_types=1);
if (!defined('TABLES_LOADED')) {
    require_once __DIR__ . '/../src/schema.php';
    define('TABLES_LOADED', true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Northwind — Insert</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
<div class="max-w-4xl mx-auto p-8">

  <div class="flex items-center justify-between mb-2">
    <h1 class="text-3xl font-bold text-gray-900">Northwind Data Inserter</h1>
    <a href="/view" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
      ← View data
    </a>
  </div>
  <p class="text-gray-500 mb-8">Paste JSON or CSV to bulk-insert rows into any table</p>

  <!-- Controls -->
  <div class="bg-white rounded-xl shadow p-6 mb-6 flex flex-wrap gap-4 items-end">
    <div class="flex-1 min-w-48">
      <label class="block text-sm font-medium text-gray-700 mb-1">Table</label>
      <select id="tableSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <?php foreach (array_keys(TABLES) as $t): ?>
        <option value="<?= $t ?>"><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex-1 min-w-48">
      <label class="block text-sm font-medium text-gray-700 mb-1">Format</label>
      <select id="formatSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="json">JSON</option>
        <option value="csv">CSV</option>
      </select>
    </div>
    <button id="exampleBtn" class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
      Load example
    </button>
  </div>

  <!-- Schema hint -->
  <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
    <p class="text-xs font-semibold text-blue-700 mb-1">Columns for <span id="schemaTableName"></span></p>
    <p id="schemaHint" class="text-xs text-blue-600 font-mono break-all"></p>
    <p id="requiredHint" class="text-xs text-blue-500 mt-1"></p>
  </div>

  <!-- Editor -->
  <div class="bg-white rounded-xl shadow p-6 mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">Data</label>
    <textarea id="dataInput" rows="14" placeholder="Paste JSON array or CSV here…"
      class="w-full font-mono text-sm border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y"></textarea>
  </div>

  <!-- Actions -->
  <div class="flex gap-3 mb-6">
    <button id="insertBtn" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
      Insert rows
    </button>
    <button id="clearBtn" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
      Clear
    </button>
  </div>

  <!-- Result -->
  <div id="result" class="hidden rounded-xl p-5 text-sm font-mono whitespace-pre-wrap"></div>

</div>

<script>
const TABLES = <?= json_encode(TABLES) ?>;

const EXAMPLES = {
  categories:   { json: JSON.stringify([{ categoryname: "Beverages", description: "Soft drinks and teas" }], null, 2),
                  csv:  "categoryname,description\nBeverages,Soft drinks and teas" },
  products:     { json: JSON.stringify([{ productname: "Chai", categoryid: 1, unitprice: 18.00, unitsinstock: 39, discontinued: false }], null, 2),
                  csv:  "productname,categoryid,unitprice,unitsinstock,discontinued\nChai,1,18.00,39,false" },
  customers:    { json: JSON.stringify([{ customerid: "ALFKI", companyname: "Alfreds Futterkiste", city: "Berlin", country: "Germany" }], null, 2),
                  csv:  "customerid,companyname,city,country\nALFKI,Alfreds Futterkiste,Berlin,Germany" },
  orders:       { json: JSON.stringify([{ customerid: "ALFKI", orderdate: "2024-01-15", freight: 12.50, shipcountry: "Germany" }], null, 2),
                  csv:  "customerid,orderdate,freight,shipcountry\nALFKI,2024-01-15,12.50,Germany" },
  order_details:{ json: JSON.stringify([{ orderid: 10248, productid: 11, unitprice: 14.00, quantity: 12, discount: 0 }], null, 2),
                  csv:  "orderid,productid,unitprice,quantity,discount\n10248,11,14.00,12,0" },
  employees:    { json: JSON.stringify([{ firstname: "Nancy", lastname: "Davolio", title: "Sales Representative", city: "Seattle" }], null, 2),
                  csv:  "firstname,lastname,title,city\nNancy,Davolio,Sales Representative,Seattle" },
  suppliers:    { json: JSON.stringify([{ companyname: "Exotic Liquids", contactname: "Charlotte Cooper", city: "London", country: "UK" }], null, 2),
                  csv:  "companyname,contactname,city,country\nExotic Liquids,Charlotte Cooper,London,UK" },
};

const tableSelect  = document.getElementById('tableSelect');
const formatSelect = document.getElementById('formatSelect');
const dataInput    = document.getElementById('dataInput');
const insertBtn    = document.getElementById('insertBtn');
const clearBtn     = document.getElementById('clearBtn');
const exampleBtn   = document.getElementById('exampleBtn');
const result       = document.getElementById('result');

function updateSchema() {
  const t = tableSelect.value;
  document.getElementById('schemaTableName').textContent = t;
  document.getElementById('schemaHint').textContent = TABLES[t].columns.join(', ');
  document.getElementById('requiredHint').textContent = 'Required: ' + TABLES[t].required.join(', ');
}
tableSelect.addEventListener('change', updateSchema);
updateSchema();

exampleBtn.addEventListener('click', () => {
  const t = tableSelect.value, f = formatSelect.value;
  dataInput.value = EXAMPLES[t]?.[f] ?? '';
});

clearBtn.addEventListener('click', () => {
  dataInput.value = '';
  result.className = 'hidden rounded-xl p-5 text-sm font-mono whitespace-pre-wrap';
  result.textContent = '';
});

insertBtn.addEventListener('click', async () => {
  const table  = tableSelect.value;
  const format = formatSelect.value;
  const data   = dataInput.value.trim();
  if (!data) return;

  insertBtn.disabled    = true;
  insertBtn.textContent = 'Inserting…';
  result.className      = 'rounded-xl p-5 text-sm font-mono whitespace-pre-wrap bg-gray-100 text-gray-600';
  result.textContent    = 'Working…';

  try {
    const res  = await fetch('/api/insert/' + table, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ format, data }),
    });
    const json = await res.json();

    if (json.error) {
      result.className  = 'rounded-xl p-5 text-sm font-mono whitespace-pre-wrap bg-red-50 text-red-700 border border-red-200';
      result.textContent = 'Error: ' + json.error;
    } else {
      const ok = json.inserted > 0;
      result.className = 'rounded-xl p-5 text-sm font-mono whitespace-pre-wrap border ' +
        (ok ? 'bg-green-50 text-green-800 border-green-200' : 'bg-yellow-50 text-yellow-800 border-yellow-200');
      let msg = '✓ Inserted: ' + json.inserted + ' row(s)';
      if (json.errors?.length) msg += '\n\nWarnings / skipped:\n' + json.errors.join('\n');
      result.textContent = msg;
    }
  } catch (e) {
    result.className  = 'rounded-xl p-5 text-sm font-mono whitespace-pre-wrap bg-red-50 text-red-700 border border-red-200';
    result.textContent = 'Network error: ' + e.message;
  } finally {
    insertBtn.disabled    = false;
    insertBtn.textContent = 'Insert rows';
  }
});
</script>
</body>
</html>
