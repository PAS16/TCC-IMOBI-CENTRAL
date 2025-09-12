<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Encontre uma Casa</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  /* Animations */
  @keyframes fadeUp {
    from { transform: translateY(40px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }
  .animate-fadeUp { animation: fadeUp 0.8s ease forwards; }

  @keyframes float {
    0% { transform: translateY(100vh) rotate(0deg); }
    100% { transform: translateY(-200px) rotate(360deg); }
  }
  .float { animation: float linear infinite; }
</style>
</head>
<body class="relative bg-gray-900 text-gray-100 font-poppins overflow-x-hidden p-6">

  <!-- Floating House Background -->
  <div class="fixed top-0 left-0 w-screen h-screen overflow-hidden pointer-events-none z-0" id="logoBackground"></div>

  <!-- Page Title -->
  <h1 class="text-4xl sm:text-5xl font-semibold mb-12 text-center z-10 relative">Encontre uma Casa</h1>

  <!-- Cards Container -->
  <div class="flex flex-wrap justify-center gap-8 z-10 relative">
    <!-- Praia Grande -->
    <a href="buscar_imoveis.php?cidade=Praia+Grande" 
       class="bg-gray-800 rounded-2xl shadow-2xl overflow-hidden w-72 sm:w-80 cursor-pointer transform transition-all duration-300 hover:-translate-y-2 hover:shadow-3xl hover:bg-gray-700 animate-fadeUp">
      <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80" 
           alt="Praia Grande" 
           class="w-full h-44 object-cover transition-transform duration-300 hover:scale-105 border-b-2 border-gray-600">
      <div class="p-5 text-xl font-semibold text-gray-100 text-center drop-shadow-md">PRAIA GRANDE</div>
    </a>

    <!-- Mongaguá -->
    <a href="buscar_imoveis.php?cidade=Mongaguá" 
       class="bg-gray-800 rounded-2xl shadow-2xl overflow-hidden w-72 sm:w-80 cursor-pointer transform transition-all duration-300 hover:-translate-y-2 hover:shadow-3xl hover:bg-gray-700 animate-fadeUp" 
       style="animation-delay:0.2s;">
      <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80" 
           alt="Mongaguá" 
           class="w-full h-44 object-cover transition-transform duration-300 hover:scale-105 border-b-2 border-gray-600">
      <div class="p-5 text-xl font-semibold text-gray-100 text-center drop-shadow-md">MONGAGUÁ</div>
    </a>

    <!-- Itanhaém -->
    <a href="buscar_imoveis.php?cidade=Itanhaém" 
       class="bg-gray-800 rounded-2xl shadow-2xl overflow-hidden w-72 sm:w-80 cursor-pointer transform transition-all duration-300 hover:-translate-y-2 hover:shadow-3xl hover:bg-gray-700 animate-fadeUp" 
       style="animation-delay:0.4s;">
      <img src="https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=800&q=80" 
           alt="Itanhaém" 
           class="w-full h-44 object-cover transition-transform duration-300 hover:scale-105 border-b-2 border-gray-600">
      <div class="p-5 text-xl font-semibold text-gray-100 text-center drop-shadow-md">ITANHAÉM</div>
    </a>
  </div>

  <!-- Floating House Script -->
  <script>
    const container = document.getElementById('logoBackground');
    const houseSVG = `<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                        <path d="M32 12l22 22h-6v18h-12v-12h-8v12h-12v-18h-6z" fill="currentColor"/>
                      </svg>`;
    const total = 25;

    for(let i = 0; i < total; i++) {
      const div = document.createElement('div');
      div.innerHTML = houseSVG;
      const svg = div.firstChild;
      svg.classList.add('absolute', 'float', 'text-gray-100');
      svg.style.left = `${Math.random()*100}vw`;
      svg.style.animationDuration = `${15 + Math.random()*10}s`;
      svg.style.width = `${20 + Math.random()*30}px`;
      svg.style.height = svg.style.width;
      svg.style.opacity = 0.08;
      container.appendChild(svg);
    }
  </script>

</body>
</html>
