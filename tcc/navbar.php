<!-- navbar.php -->
<nav class="fixed top-0 left-0 w-full bg-gray-900 bg-opacity-90 backdrop-blur-sm z-50 shadow-md transition-all duration-500">
  <div class="max-w-7xl mx-auto px-6 py-3 flex justify-between items-center relative">

    <!-- Logo -->
    <a href="index.php" class="flex items-center space-x-3">
      <img src="imagem/logo1.png" alt="Logo" class="h-10 w-auto">
      <span class="text-white text-xl font-semibold">Imobi Central</span>
    </a>

    <!-- Menu Desktop -->
    <div class="hidden md:flex gap-6">
      <?php if(isset($pagina_atual) && ($pagina_atual === 'suporte' || $pagina_atual === 'buscar_imoveis')): ?>
        <a href="javascript:history.back()" class="nav-link py-2 px-5 bg-gray-800 hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition">
    VOLTAR
</a>

      <?php else: ?>
        <a href="suporte.php" class="nav-link py-2 px-5 bg-gray-800 hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition">
            ANUNCIE SEU IMÓVEL
        </a>
      <?php endif; ?>

      <?php if(isset($pagina_atual) && $pagina_atual !== 'buscar_imoveis'): ?>
        <a href="escolhacd.php" class="nav-link py-2 px-5 bg-gray-800 hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition">
          EXPLORAR IMÓVEIS
        </a>
      <?php endif; ?>
    </div>

    <!-- Botão Mobile -->
    <button onclick="toggleMenu()" class="md:hidden text-white focus:outline-none">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
  </div>

  <!-- Menu Mobile -->
  <div id="mobile-menu" class="hidden md:hidden flex flex-col items-center gap-3 pb-4">
      <?php if(isset($pagina_atual) && ($pagina_atual === 'suporte' || $pagina_atual === 'buscar_imoveis')): ?>
        <a href="index.php" class="w-11/12 text-center py-2 px-4 bg-gray-800 hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition">
            VOLTAR
        </a>
      <?php else: ?>
        <a href="suporte.php" class="w-11/12 text-center py-2 px-4 bg-gray-800 hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition">
            ANUNCIE SEU IMÓVEL
        </a>
      <?php endif; ?>

      <?php if(!isset($pagina_atual) || $pagina_atual !== 'buscar_imoveis'): ?>
        <a href="escolhacd.php" class="w-11/12 text-center py-2 px-4 bg-gray-800 hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition">
          COMPRE UM IMÓVEL
        </a>
      <?php endif; ?>
  </div>
</nav>

<script>
function toggleMenu() {
  const menu = document.getElementById('mobile-menu');
  menu.classList.toggle('hidden');
}
</script>

<!-- Espaço para compensar a navbar fixa -->
<div class="pt-24"></div>

