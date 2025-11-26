<?php
// Inclui a conexão com o banco de dados
include 'conexao.php';

// Formulário de suporte
$sucesso = false;
$erro = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $mensagem = $_POST['mensagem'] ?? '';

    if ($nome && $email && $mensagem) {
        $stmt = $conn->prepare("INSERT INTO mensagens_suporte (nome, email, telefone, mensagem) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome, $email, $telefone, $mensagem);

        if ($stmt->execute()) {
            $sucesso = true;
        } else {
            $erro = "Erro ao enviar mensagem. Tente novamente.";
        }
        $stmt->close();
    } else {
        $erro = "Preencha todos os campos obrigatórios!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<title>Imobi Central</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<script src="https://cdn.tailwindcss.com"></script>
<style>
@keyframes fadeIn {0%{opacity:0;transform:translateY(30px);}100%{opacity:1;transform:translateY(0);} }
@keyframes floatLogo {0%,100%{transform:translateY(0);}50%{transform:translateY(-8px);} }
.fade-in{animation:fadeIn 1.2s ease-in;}
.float-logo{animation:floatLogo 3s ease-in-out infinite;}
section{scroll-margin-top:80px;}
.nav-link { position: relative; overflow: hidden; }
.nav-link::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: 0;
  height: 2px;
  width: 0%;
  background: linear-gradient(to right, #6b7280, #ffffff);
  transition: width 0.3s ease;
}
.nav-link:hover::after {width:100%;}
</style>
<script>
function toggleMenu() {
  const menu = document.getElementById("mobile-menu");
  menu.classList.toggle("hidden");
}
</script>
</head>

<body class="font-sans text-gray-100 bg-gray-900">

<!-- Navbar fixa sempre ativa -->
<nav class="fixed top-0 left-0 w-full bg-gray-900 bg-opacity-90 backdrop-blur-sm z-50 shadow-md transition-all duration-500">
  <div class="max-w-7xl mx-auto px-6 py-3 flex justify-between items-center relative">
    <!-- Logo -->
    <a href="glogin.php" class="flex items-center space-x-3">
      <img src="imagem/logo1.png" alt="Logo" class="h-10 w-auto">
      <span class="text-white text-xl font-semibold">Imobi Central</span>
    </a>

    <!-- Menu Desktop -->
    <div class="hidden md:flex gap-6">
      <a href="suporte.php" class="nav-link py-2 px-5 bg-gray-800 hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition">
        ANUNCIE SEU IMÓVEL
      </a>
      <a href="escolhacd.php" class="nav-link py-2 px-5 bg-gray-800 hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition">
        EXPLORAR IMÓVEIS
      </a>
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
    <a href="suporte.php" class="w-11/12 text-center py-2 px-4 bg-gray-800 hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition">
      ANUNCIE SEU IMÓVEL
    </a>
    <a href="escolhacd.php" class="w-11/12 text-center py-2 px-4 bg-gray-800 hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition">
      COMPRE UM IMÓVEL
    </a>
  </div>
</nav>

<!-- Hero -->
<section class="relative h-screen w-full bg-cover bg-center pt-20" style="background-image: url('imagem/2.jfif');">
  <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  <div class="relative z-10 flex flex-col items-center justify-center h-full px-4 text-center fade-in">
    <img src="imagem/logo.png" alt="Logo Imobi Central" class="w-40 md:w-52 lg:w-64 mb-6 float-logo">
    <h1 class="text-3xl md:text-5xl font-bold drop-shadow-lg">Vamos te ajudar a mudar</h1>
  </div>
</section>

<!-- Sobre -->
<section class="py-20 px-6 bg-gray-800">
  <div class="max-w-5xl mx-auto text-center fade-in">
    <h2 class="text-3xl md:text-4xl font-bold mb-6">Sobre Nós</h2>
    <p class="text-gray-300 text-lg md:text-xl leading-relaxed">
      A Imobi Central conecta pessoas com seus lares dos sonhos. Com anos de experiência no mercado imobiliário, oferecemos soluções personalizadas para compradores e vendedores.
    </p>
  </div>
</section>

<!-- Destaques -->
<section class="py-20 px-6 bg-gray-900">
  <div class="max-w-6xl mx-auto fade-in">
    <h2 class="text-3xl md:text-4xl font-bold mb-12 text-center">Destaques de Imóveis</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php
    // Busca imóveis em destaque
    $res_high = $conn->query("SELECT * FROM IMOVEL ORDER BY valor DESC LIMIT 3");
    $res_low  = $conn->query("SELECT * FROM IMOVEL ORDER BY valor ASC LIMIT 3");
    $imoveis = [];
    while($row = $res_high->fetch_assoc()) $imoveis[] = $row;
    while($row = $res_low->fetch_assoc())  $imoveis[] = $row;

    foreach($imoveis as $imovel){
        // Imagem padrão apenas
        $img = 'uploads/imoveis/1.jpg';

        echo '<div class="relative bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:scale-105 transition cursor-pointer" onclick="location.href=\'detalhes_imovel.php?id='.$imovel['idIMOVEL'].'\'">
          <img src="'.htmlspecialchars($img).'" alt="'.htmlspecialchars($imovel['tipo']).'" class="w-full h-56 object-cover"/>
          <span class="absolute top-2 left-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-full">Imagem Padrão</span>
          <div class="p-5">
            <h3 class="text-xl font-bold mb-2">'.$imovel['tipo'].' - R$ '.number_format($imovel['valor'],2,",",".").'</h3>
            <p class="text-gray-300">'.$imovel['qtd_quartos'].' quartos · '.$imovel['qtd_banheiro'].' banheiros · '.$imovel['qtd_vagas'].' vagas</p>
          </div>
        </div>';
    }
    ?>
    </div>
  </div>
</section>

<!-- Depoimentos -->
<section class="py-20 px-6 bg-gray-800">
  <div class="max-w-4xl mx-auto fade-in text-center">
    <h2 class="text-3xl md:text-4xl font-bold mb-12">O que nossos clientes dizem</h2>
    <div class="grid md:grid-cols-2 gap-8">
      <div class="bg-gray-900 p-6 rounded-2xl shadow-lg">
        <p class="text-gray-300 italic">"A Imobi Central nos ajudou a encontrar nossa casa perfeita de forma rápida e segura!"</p>
        <span class="block mt-4 font-bold">– João S.</span>
      </div>
      <div class="bg-gray-900 p-6 rounded-2xl shadow-lg">
        <p class="text-gray-300 italic">"Vender meu imóvel nunca foi tão fácil. Excelente atendimento e profissionalismo."</p>
        <span class="block mt-4 font-bold">– Maria L.</span>
      </div>
    </div>
  </div>
</section>

<!-- Contato -->
<section class="py-20 px-6 bg-gray-900">
  <div class="max-w-3xl mx-auto fade-in text-center">
    <h2 class="text-3xl md:text-4xl font-bold mb-6">Entre em Contato</h2>
    <p class="text-gray-300 mb-8">Preencha o formulário abaixo e nossa equipe entrará em contato rapidamente.</p>
    <?php
    if($sucesso) echo '<p class="text-green-400 font-semibold mb-4">Mensagem enviada com sucesso!</p>';
    elseif($erro) echo '<p class="text-red-400 font-semibold mb-4">'.$erro.'</p>';
    ?>
    <form class="flex flex-col gap-4" action="" method="POST">
      <input type="text" name="nome" placeholder="Nome" class="p-3 rounded-lg bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-gray-600" required/>
      <input type="email" name="email" placeholder="Email" class="p-3 rounded-lg bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-gray-600" required/>
      <input type="text" name="telefone" placeholder="Telefone" class="p-3 rounded-lg bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-gray-600"/>
      <textarea name="mensagem" placeholder="Mensagem" class="p-3 rounded-lg bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-gray-600" required></textarea>
      <div class="flex gap-4 mt-4">
        <button type="submit" class="flex-1 py-3 bg-green-600 hover:bg-green-500 font-bold rounded-lg shadow-lg transition">Enviar Mensagem</button>
        <button type="reset" class="flex-1 py-3 bg-gray-700 hover:bg-gray-600 font-bold rounded-lg shadow-lg transition">Limpar</button>
      </div>
    </form>
  </div>
</section>

<footer class="py-6 bg-gray-800 text-center text-gray-400">
  © 2025 Imobi Central. Todos os direitos reservados.
</footer>

</body>
</html>
