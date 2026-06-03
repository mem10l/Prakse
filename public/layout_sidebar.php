<?php
$baseUrl = rtrim(str_replace("\\", "/", dirname($_SERVER['SCRIPT_NAME'])), "/.");
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Normalize current path for comparison
if ($baseUrl !== '' && strpos($currentPath, $baseUrl) === 0) {
    $currentPath = substr($currentPath, strlen($baseUrl));
}
$currentPath = '/' . ltrim($currentPath, '/');

$navItems = [
    ['label' => 'Dashboard', 'icon' => '🏠', 'path' => '/view'],
    ['label' => 'Reports', 'icon' => '📊', 'path' => '/reports'],
    ['label' => 'Insert Data', 'icon' => '➕', 'path' => '/insert'],
];

$tableItems = [
    'categories', 'customers', 'employees', 'orders', 'products', 
    'shippers', 'suppliers', 'territories', 'region', 
    'customerdemographics', 'customercustomerdemo', 'employeeterritories'
];

function isActive($path, $current) {
    if ($path === '/view' && $current === '/') return true;
    return $path === $current || strpos($current, $path . '/') === 0;
}
?>

<div class="flex h-screen bg-gray-100 overflow-hidden">
  <!-- Sidebar -->
  <aside class="w-64 bg-slate-900 text-white flex-shrink-0 flex flex-col">
    <div class="p-6">
      <h1 class="text-2xl font-bold tracking-tight text-blue-400">Northwind</h1>
      <p class="text-xs text-slate-400 mt-1 uppercase tracking-widest font-semibold">ERP Management</p>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 space-y-1">
      <div class="text-xs font-bold text-slate-500 uppercase px-2 py-2">Main</div>
      <?php foreach ($navItems as $item): ?>
      <a href="<?= $baseUrl . $item['path'] ?>" 
         class="flex items-center px-4 py-3 rounded-lg transition-colors <?= isActive($item['path'], $currentPath) ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
        <span class="mr-3"><?= $item['icon'] ?></span>
        <span class="font-medium"><?= $item['label'] ?></span>
      </a>
      <?php endforeach; ?>

      <div class="text-xs font-bold text-slate-500 uppercase px-2 py-2 mt-6">Tables</div>
      <?php foreach ($tableItems as $tbl): ?>
      <a href="<?= $baseUrl ?>/table/<?= $tbl ?>" 
         class="flex items-center px-4 py-2 text-sm rounded-lg transition-colors <?= isActive('/table/'.$tbl, $currentPath) ? 'bg-slate-800 text-blue-400 border-l-4 border-blue-500 pl-3' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
        <span class="capitalize"><?= htmlspecialchars($tbl) ?></span>
      </a>
      <?php endforeach; ?>
    </nav>

    <div class="p-4 border-t border-slate-800 text-xs text-slate-500 text-center">
      &copy; 2026 Northwind PHP
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto relative focus:outline-none">
    <div class="py-6 px-8">
