<?php
$baseUrl = rtrim(str_replace("\\", "/", dirname($_SERVER['SCRIPT_NAME'])), "/.");
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($baseUrl !== '' && strpos($currentPath, $baseUrl) === 0) {
    $currentPath = substr($currentPath, strlen($baseUrl));
}
$currentPath = '/' . ltrim($currentPath, '/');

$navItems = [
    ['label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'path' => '/view'],
    ['label' => 'Reports', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'path' => '/reports'],
    ['label' => 'Bonuses', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.407 2.67 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.407-2.67-1M12 16v1m0-9v9', 'path' => '/bonuses'],
    ['label' => 'Insert Data', 'icon' => 'M12 4v16m8-8H4', 'path' => '/insert'],
];

$tableItems = [
    'categories', 'customers', 'employees', 'orders', 'products', 
    'shippers', 'suppliers', 'territories', 'region', 
    'customerdemographics', 'customercustomerdemo', 'employeeterritories'
];

function isActive($path, $current) {
    if ($path === '/view' && ($current === '/' || $current === '/view')) return true;
    return $path === $current || strpos($current, $path . '/') === 0;
}
?>

<div class="flex h-screen bg-[#0a0a0a] text-gray-300 overflow-hidden font-sans">
  <!-- Sidebar -->
  <aside class="w-72 bg-[#0a0a0a] border-r border-[#1a1a1a] flex-shrink-0 flex flex-col z-20">
    <div class="p-8">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-[#00e599] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(0,229,153,0.4)]">
          <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        </div>
        <h1 class="text-xl font-bold tracking-tight text-white">Northwind</h1>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 pb-8 space-y-1">
      <div class="text-[10px] font-bold text-gray-600 uppercase tracking-[0.2em] px-4 py-3">Navigation</div>
      <?php foreach ($navItems as $item): ?>
      <a href="<?= $baseUrl . $item['path'] ?>" 
         class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= isActive($item['path'], $currentPath) ? 'bg-[#1a1a1a] text-[#00e599] shadow-[inset_0_0_0_1px_rgba(0,229,153,0.1)]' : 'text-gray-400 hover:text-white hover:bg-[#111]' ?>">
        <svg class="w-5 h-5 mr-3 transition-colors <?= isActive($item['path'], $currentPath) ? 'text-[#00e599]' : 'text-gray-500 group-hover:text-gray-300' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>"></path>
        </svg>
        <span class="font-medium"><?= $item['label'] ?></span>
      </a>
      <?php endforeach; ?>

      <div class="text-[10px] font-bold text-gray-600 uppercase tracking-[0.2em] px-4 py-3 mt-8">Database Tables</div>
      <div class="grid grid-cols-1 gap-0.5">
        <?php foreach ($tableItems as $tbl): ?>
        <a href="<?= $baseUrl ?>/table/<?= $tbl ?>" 
           class="flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200 <?= isActive('/table/'.$tbl, $currentPath) ? 'text-[#00e599] bg-[#1a1a1a]/50' : 'text-gray-500 hover:text-gray-300 hover:bg-[#111]' ?>">
          <div class="w-1 h-1 rounded-full mr-3 <?= isActive('/table/'.$tbl, $currentPath) ? 'bg-[#00e599] shadow-[0_0_5px_#00e599]' : 'bg-gray-700' ?>"></div>
          <span class="capitalize"><?= htmlspecialchars($tbl) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </nav>

    <div class="p-6 border-t border-[#1a1a1a]">
      <div class="flex items-center gap-3 p-3 rounded-xl bg-[#111] border border-[#1a1a1a]">
        <div class="w-2 h-2 rounded-full bg-[#00e599] animate-pulse shadow-[0_0_5px_#00e599]"></div>
        <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Postgres Online</span>
      </div>
    </div>
  </aside>

  <!-- Main Content Area -->
  <main class="flex-1 overflow-y-auto relative bg-[#0a0a0a]">
    <!-- Background Glows -->
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-[#00e599]/5 blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-purple-500/5 blur-[120px] pointer-events-none"></div>

    <div class="relative py-10 px-12 max-w-7xl mx-auto">
