<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Northwind — Insert Data</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php require __DIR__ . '/layout_sidebar.php'; ?>

  <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-900">Insert Data</h2>
    <p class="text-gray-500 mt-1">Bulk upload records via JSON or CSV format</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-6">
      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
        <div class="mb-4">
          <label class="block text-sm font-bold text-gray-700 mb-2">Target Table</label>
          <select id="table-select" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
            <?php require_once __DIR__ . '/../src/schema.php';
            foreach (array_keys(TABLES) as $t): ?>
              <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-bold text-gray-700 mb-2">Data Format</label>
          <div class="flex space-x-4">
            <label class="flex items-center">
              <input type="radio" name="format" value="json" checked class="mr-2"> JSON
            </label>
            <label class="flex items-center">
              <input type="radio" name="format" value="csv" class="mr-2"> CSV
            </label>
          </div>
        </div>

        <div class="mb-6">
          <label class="block text-sm font-bold text-gray-700 mb-2">Payload</label>
          <textarea id="payload" rows="12" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none font-mono text-sm" placeholder='[{"productname": "New Product", "unitprice": 19.99}]'></textarea>
        </div>

        <button onclick="handleInsert()" id="submit-btn" class="w-full py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition shadow-sm">
          Execute Import
        </button>
      </div>

      <div id="results" class="hidden bg-white p-6 rounded-xl shadow-sm border border-gray-200">
        <h3 class="font-bold text-lg mb-4">Import Results</h3>
        <div id="results-content" class="space-y-2 text-sm"></div>
      </div>
    </div>

    <div class="space-y-6">
      <div class="bg-blue-50 p-6 rounded-xl border border-blue-100">
        <h3 class="font-bold text-blue-900 mb-2">Import Tips</h3>
        <ul class="text-sm text-blue-800 space-y-2 list-disc pl-4">
          <li>Ensure column names match the database schema.</li>
          <li>For CSV, include a header row.</li>
          <li>Dates should be in <code class="bg-blue-100 px-1 rounded">YYYY-MM-DD</code> format.</li>
          <li>Duplicates will be ignored (ON CONFLICT DO NOTHING).</li>
        </ul>
      </div>
    </div>
  </div>

    </div> <!-- End py-6 -->
  </main>
</div>

<script>
async function handleInsert() {
  const table = document.getElementById('table-select').value;
  const format = document.querySelector('input[name="format"]:checked').value;
  const data = document.getElementById('payload').value;
  const btn = document.getElementById('submit-btn');
  const resultsDiv = document.getElementById('results');
  const resultsContent = document.getElementById('results-content');

  if (!data.trim()) return alert('Please enter data to import');

  btn.disabled = true;
  btn.textContent = 'Processing...';

  try {
    const res = await fetch(`<?= $baseUrl ?>/api/insert/${table}`, {
      method: 'POST',
      body: JSON.stringify({ format, data })
    });
    const result = await res.json();

    resultsDiv.classList.remove('hidden');
    resultsContent.innerHTML = `
      <div class="p-3 bg-green-50 text-green-700 rounded-lg border border-green-100">
        <strong>Success:</strong> ${result.inserted} rows inserted.
      </div>
    `;

    if (result.errors && result.errors.length > 0) {
      resultsContent.innerHTML += `
        <div class="mt-4 p-3 bg-red-50 text-red-700 rounded-lg border border-red-100">
          <strong>Errors (${result.errors.length}):</strong>
          <ul class="list-disc pl-4 mt-2 max-h-40 overflow-y-auto">
            ${result.errors.map(e => `<li>${e}</li>`).join('')}
          </ul>
        </div>
      `;
    }
  } catch (e) {
    alert('Failed to execute import: ' + e.message);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Execute Import';
  }
}
</script>
</body>
</html>
