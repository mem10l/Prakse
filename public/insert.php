<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Northwind — Insert Data</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    body { font-family: 'Inter', sans-serif; }
    .neon-card { background: rgba(26, 26, 26, 0.4); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
    .neon-input { background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.1); color: white; }
    .neon-input:focus { border-color: #00e599; ring: 2px; ring-color: rgba(0, 229, 153, 0.2); }
  </style>
</head>
<body class="bg-[#0a0a0a]">

<?php require __DIR__ . '/layout_nav.php'; ?>

  <div class="mb-10">
    <h2 class="text-4xl font-extrabold text-white tracking-tight">Data Ingestion</h2>
    <p class="text-gray-500 mt-2 text-lg font-medium">Bulk upload records to Neon PostgreSQL</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-8">
      <div class="neon-card p-8 rounded-2xl border border-white/5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
          <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em] mb-3">Target Dataset</label>
            <select id="table-select" class="w-full px-4 py-3 neon-input rounded-xl focus:outline-none transition-all text-sm font-medium">
              <?php require_once __DIR__ . '/../src/schema.php';
              foreach (array_keys(TABLES) as $t): ?>
                <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em] mb-3">Schema Format</label>
            <div class="inline-flex p-1 bg-black/40 rounded-xl border border-white/5 w-full">
              <label class="flex-1 text-center py-2 text-sm font-bold rounded-lg cursor-pointer transition-all peer-checked:bg-[#00e599] peer-checked:text-black has-[:checked]:bg-[#00e599] has-[:checked]:text-black text-gray-500">
                <input type="radio" name="format" value="json" checked class="hidden"> JSON
              </label>
              <label class="flex-1 text-center py-2 text-sm font-bold rounded-lg cursor-pointer transition-all has-[:checked]:bg-[#00e599] has-[:checked]:text-black text-gray-500">
                <input type="radio" name="format" value="csv" class="hidden"> CSV
              </label>
            </div>
          </div>
        </div>

        <div class="mb-8">
          <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em] mb-3">Payload Payload</label>
          <textarea id="payload" rows="12" class="w-full px-5 py-4 neon-input rounded-xl focus:outline-none transition-all font-mono text-xs leading-relaxed" placeholder='[{"productname": "Alpha Prototype", "unitprice": 299.99}]'></textarea>
        </div>

        <button onclick="handleInsert()" id="submit-btn" class="w-full py-4 bg-[#00e599] text-black font-extrabold rounded-xl hover:bg-[#00cc88] transition-all shadow-[0_0_25px_rgba(0,229,153,0.2)] uppercase tracking-widest text-xs">
          Execute Upload Sequence
        </button>
      </div>

      <div id="results" class="hidden neon-card p-8 rounded-2xl border border-white/5">
        <h3 class="font-bold text-lg text-white mb-6 flex items-center">
          <span class="w-2 h-2 rounded-full bg-[#00e599] mr-3 animate-pulse"></span>
          Ingestion Results
        </h3>
        <div id="results-content" class="space-y-4"></div>
      </div>
    </div>

    <div class="space-y-8">
      <div class="neon-card p-8 rounded-2xl border border-[#00e599]/10 bg-[#00e599]/[0.02]">
        <h3 class="font-bold text-[#00e599] text-sm uppercase tracking-widest mb-6">Ingestion Protocol</h3>
        <ul class="text-sm text-gray-400 space-y-4">
          <li class="flex items-start">
            <span class="text-[#00e599] mr-3">▹</span>
            <span>Column headers must align with PostgreSQL schema exactly.</span>
          </li>
          <li class="flex items-start">
            <span class="text-[#00e599] mr-3">▹</span>
            <span>CSV uploads require an initial header row for mapping.</span>
          </li>
          <li class="flex items-start">
            <span class="text-[#00e599] mr-3">▹</span>
            <span>Temporal data (dates) should follow <code class="bg-white/5 px-2 py-0.5 rounded text-[#00e599] font-mono">ISO 8601</code>.</span>
          </li>
          <li class="flex items-start">
            <span class="text-[#00e599] mr-3">▹</span>
            <span>Conflicts are handled via <code class="bg-white/5 px-2 py-0.5 rounded text-[#00e599] font-mono">NOTHING</code> bypass.</span>
          </li>
        </ul>
      </div>
      
      <div class="neon-card p-8 rounded-2xl border border-white/5">
        <h3 class="font-bold text-white text-sm uppercase tracking-widest mb-4">System Identity</h3>
        <div class="p-4 rounded-xl bg-black/40 border border-white/5 space-y-3">
          <div class="flex justify-between text-xs">
            <span class="text-gray-500">Node Status</span>
            <span class="text-[#00e599] font-bold">Active</span>
          </div>
          <div class="flex justify-between text-xs">
            <span class="text-gray-500">Batch Limit</span>
            <span class="text-gray-300 font-mono">500 Records</span>
          </div>
        </div>
      </div>
    </div>
  </div>

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

  if (!data.trim()) return;

  btn.disabled = true;
  btn.textContent = 'Processing Stream...';
  btn.classList.add('opacity-50', 'cursor-not-allowed');

  try {
    const res = await fetch(`${window.API_BASE}/api/insert/${table}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ format, data })
    });
    const result = await res.json();

    resultsDiv.classList.remove('hidden');
    resultsContent.innerHTML = `
      <div class="p-5 bg-[#00e599]/10 text-[#00e599] rounded-xl border border-[#00e599]/20 font-medium flex items-center">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        <span>Successfully committed ${result.inserted} records to ${table}.</span>
      </div>
    `;

    if (result.errors && result.errors.length > 0) {
      resultsContent.innerHTML += `
        <div class="p-6 bg-red-500/5 text-red-400 rounded-xl border border-red-500/10">
          <strong class="block mb-3 text-red-500 uppercase tracking-widest text-[10px]">Ingestion Warnings (${result.errors.length})</strong>
          <ul class="space-y-2 font-mono text-[11px] max-h-60 overflow-y-auto pr-4 custom-scrollbar">
            ${result.errors.map(e => `<li class="p-2 bg-black/20 rounded border border-white/5">${e}</li>`).join('')}
          </ul>
        </div>
      `;
    }
  } catch (e) {
    console.error(e);
    alert('Ingestion failure: ' + e.message);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Execute Upload Sequence';
    btn.classList.remove('opacity-50', 'cursor-not-allowed');
  }
}
</script>
</body>
</html>
