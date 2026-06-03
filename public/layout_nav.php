<?php
$rawBase = str_replace("\\", "/", dirname($_SERVER['SCRIPT_NAME']));
$baseUrl = ($rawBase === DIRECTORY_SEPARATOR || $rawBase === '/') ? '' : rtrim($rawBase, '/');
// Ensure baseUrl for JS is always starting with / if not empty
$jsBaseUrl = ($baseUrl === '') ? '' : $baseUrl;
?>

<script>
  window.API_BASE = '<?= $jsBaseUrl ?>';
</script>

<style>
  /* Global Neon Scrollbar */
  ::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }
  ::-webkit-scrollbar-track {
    background: #0a0a0a;
  }
  ::-webkit-scrollbar-thumb {
    background: #1a1a1a;
    border-radius: 10px;
    border: 2px solid #0a0a0a;
  }
  ::-webkit-scrollbar-thumb:hover {
    background: #2a2a2a;
  }
  
  /* Firefox */
  * {
    scrollbar-width: thin;
    scrollbar-color: #1a1a1a #0a0a0a;
  }
</style>

<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($baseUrl !== '' && strpos($currentPath, $baseUrl) === 0) {
    $currentPath = substr($currentPath, strlen($baseUrl));
}
$currentPath = '/' . ltrim($currentPath, '/');

$navItems = [
    ['label' => 'Dashboard', 'path' => '/view'],
    ['label' => 'Reports', 'path' => '/reports'],
    ['label' => 'Insert Data', 'path' => '/insert'],
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

<div class="min-h-screen bg-[#0a0a0a] text-gray-300 font-sans">
  <!-- Top Navigation Bar -->
  <header class="sticky top-0 z-50 bg-black/80 backdrop-blur-xl border-b border-white/5">
    <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
      <!-- Logo -->
      <a href="<?= $baseUrl ?>/view" class="flex items-center gap-3 group">
        <div class="w-8 h-8 bg-[#00e599] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(0,229,153,0.3)] group-hover:shadow-[0_0_20px_rgba(0,229,153,0.5)] transition-all">
          <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        </div>
        <span class="text-xl font-bold text-white tracking-tight">Northwind</span>
      </a>

      <!-- Main Nav -->
      <nav class="hidden md:flex items-center gap-1">
        <?php foreach ($navItems as $item): ?>
        <a href="<?= $baseUrl . $item['path'] ?>" 
           class="px-4 py-2 rounded-lg text-sm font-semibold transition-all <?= isActive($item['path'], $currentPath) ? 'text-[#00e599] bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5' ?>">
          <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>

        <!-- Dropdown -->
        <div class="relative group ml-2 h-full flex items-center">
          <button class="flex items-center gap-1 px-4 py-2 rounded-lg text-sm font-semibold text-gray-400 group-hover:text-white group-hover:bg-white/5 transition-all <?= strpos($currentPath, '/table/') === 0 ? 'text-[#00e599]' : '' ?>">
            <span>Tables</span>
            <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
          </button>
          
          <!-- Dropdown Menu with a 'bridge' to prevent flickering -->
          <div class="absolute left-0 top-[80%] pt-4 w-56 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200 z-[60]">
            <div class="p-2 bg-[#111] border border-white/10 rounded-xl shadow-2xl grid grid-cols-1 gap-1">
              <?php foreach ($tableItems as $tbl): ?>
              <a href="<?= $baseUrl ?>/table/<?= $tbl ?>" 
                 class="px-3 py-2 text-xs font-medium rounded-lg capitalize transition-colors <?= isActive('/table/'.$tbl, $currentPath) ? 'text-[#00e599] bg-[#00e599]/10' : 'text-gray-500 hover:text-white hover:bg-white/5' ?>">
                <?= htmlspecialchars($tbl) ?>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </nav>

      <!-- Connection Status -->
      <div class="flex items-center gap-4">
        <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#111] border border-white/5">
          <div class="w-1.5 h-1.5 rounded-full bg-[#00e599] animate-pulse"></div>
          <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Postgres Online</span>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="relative py-10 px-6 max-w-7xl mx-auto">
    <!-- Glows -->
    <div class="fixed top-0 right-0 w-[600px] h-[600px] bg-[#00e599]/5 blur-[120px] pointer-events-none -z-10"></div>
    <div class="fixed bottom-0 left-0 w-[500px] h-[500px] bg-purple-500/5 blur-[120px] pointer-events-none -z-10"></div>
