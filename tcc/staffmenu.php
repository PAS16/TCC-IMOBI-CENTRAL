<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Gerenciamento Imobiliária</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Animação de entrada */
    @keyframes fadeInPage {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .fade-in-page {
      animation: fadeInPage 0.6s ease-in-out;
    }

    /* Gradiente animado monocromático de fundo */
    body::before {
      content: "";
      position: fixed;
      top:0; left:0; right:0; bottom:0;
      background: linear-gradient(135deg, #111111, #1a1a1a, #2a2a2a, #1a1a1a);
      background-size: 400% 400%;
      animation: gradientMove 20s ease infinite;
      z-index: -1;
    }
    @keyframes gradientMove {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    /* Efeito glow monocromático nos botões */
    .btn-glow {
      position: relative;
      transition: all 0.3s ease;
    }
    .btn-glow::before {
      content: '';
      position: absolute;
      top: -2px; left: -2px; right: -2px; bottom: -2px;
      background: linear-gradient(45deg, #444, #666, #888, #aaa);
      border-radius: inherit;
      filter: blur(6px);
      opacity: 0;
      transition: opacity 0.3s ease;
      z-index: -1;
    }
    .btn-glow:hover::before {
      opacity: 1;
    }

    /* Sombra dinâmica no card */
    .card-dynamic {
      box-shadow: 0 10px 25px rgba(0,0,0,0.6);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card-dynamic:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.8);
    }

    /* Brilho suave nos títulos */
    .title-glow {
      text-shadow: 0 0 6px rgba(255,255,255,0.5);
    }
  </style>
</head>
<body class="font-serif text-gray-100">

  <div class="min-h-screen flex flex-col items-center pt-16 fade-in-page">

    <!-- Logo -->
    <div class="mb-10">
      <img src="imagem/logo.png" alt="Logo Imobiliária" class="w-48 h-auto mx-auto drop-shadow-2xl">
    </div>

    <!-- Menu Box -->
    <div class="bg-gray-900/80 backdrop-blur-md p-10 rounded-3xl text-center w-full max-w-2xl shadow-2xl space-y-6 card-dynamic border border-gray-700/50">

      <h2 class="text-3xl font-bold mb-6 tracking-wide text-gray-100 title-glow">Gerenciamento Imobiliária</h2>

      <!-- 🔍 Campo de busca global -->
      <form class="flex mb-6" action="buscar_geral.php" method="GET">
        <input type="text" name="termo" placeholder="Buscar por ID, nome, telefone, endereço..." required
               class="flex-1 p-3 rounded-l-xl focus:outline-none text-gray-900 shadow-inner"/>
        <button type="submit"
                class="bg-gray-700 text-gray-100 px-6 rounded-r-xl font-bold hover:bg-gray-600 transition shadow-md btn-glow">
          🔍
        </button>
      </form>

      <!-- 🔘 Botões de Gerenciamento -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="corretor/listar.php" class="py-3 px-5 bg-gray-700 text-gray-100 rounded-xl font-bold hover:bg-gray-600 transition shadow-md btn-glow">👔 Corretores</a>
        <a href="clientes/listar.php" class="py-3 px-5 bg-gray-700 text-gray-100 rounded-xl font-bold hover:bg-gray-600 transition shadow-md btn-glow">👥 Clientes</a>
        <a href="proprietario/listar.php" class="py-3 px-5 bg-gray-700 text-gray-100 rounded-xl font-bold hover:bg-gray-600 transition shadow-md btn-glow">🏠 Proprietários</a>
        <a href="imoveis/listar.php" class="py-3 px-5 bg-gray-700 text-gray-100 rounded-xl font-bold hover:bg-gray-600 transition shadow-md btn-glow">🏡 Imóveis</a>
        <a href="visitas/listar.php" class="py-3 px-5 bg-gray-700 text-gray-100 rounded-xl font-bold hover:bg-gray-600 transition shadow-md btn-glow">📅 Visitas</a>
        <a href="propostas/listar.php" class="py-3 px-5 bg-gray-700 text-gray-100 rounded-xl font-bold hover:bg-gray-600 transition shadow-md btn-glow">💰 Propostas</a>
        <a href="pendentes/processarim.php" class="py-3 px-5 bg-gray-700 text-gray-100 rounded-xl font-bold hover:bg-gray-600 transition shadow-md btn-glow">📋 Processar Imóvel</a>
        <a href="suporte/listar_suporte.php" class="py-3 px-5 bg-gray-700 text-gray-100 rounded-xl font-bold hover:bg-gray-600 transition shadow-md btn-glow">💬 Suporte</a>
      </div>

    </div>
  </div>

</body>
</html>
