<?php
// Conexão com o banco de dados mydb
$host = "localhost";
$user = "root"; // seu usuário
$pass = "admin"; // sua senha
$db   = "mydb";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

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
@keyframes fadeIn { 0% { opacity:0; transform:translateY(30px);} 100%{opacity:1; transform:translateY(0);} }
@keyframes floatLogo {0%,100%{transform:translateY(0);}50%{transform:translateY(-8px);}}
.fade-in{animation:fadeIn 1.2s ease-in;}
.float-logo{animation:floatLogo 3s ease-in-out infinite;}
section{scroll-margin-top:80px;}
/* underline animado */
.nav-link {
  position: relative;
  overflow: hidden;
}
.nav-link::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: 0;
  height: 2px;
  width: 0%;
  background: linear-gradient(to right, #9ca3af, #ffffff);
  transition: width 0.3s ease;
}
.nav-link:hover::after {
  width: 100%;
}
/* animação menu mobile */
.menu-enter { opacity: 0; transform: translateY(-10px); }
.menu-enter-active { opacity: 1; transform: translateY(0); transition: all 0.3s ease-out; }
.menu-leave { opacity: 1; transform: translateY(0); }
.menu-leave-active { opacity: 0; transform: translateY(-10px); transition: all 0.2s ease-in; }
</style>
<script>
function toggleMenu() {
  const menu = document.getElementById("mobile-menu");
  const overlay = document.getElementById("menu-overlay");

  if (menu.classList.contains("hidden")) {
    menu.classList.remove("hidden", "menu-leave", "menu-leave-active");
    menu.classList.add("menu-enter");
    overlay.classList.remove("hidden");
    setTimeout(() => {
      menu.classList.add("menu-enter-active");
      overlay.classList.add("bg-opacity-50");
    }, 10);
  } else {
    menu.classList.remove("menu-enter", "menu-enter-active");
    menu.classList.add("menu-leave");
    overlay.classList.remove("bg-opacity-50");
    setTimeout(() => {
      menu.classList.add("menu-leave-active");
      setTimeout(() => {
        menu.classList.add("hidden");
        overlay.classList.add("hidden");
      }, 200);
    }, 10);
  }
}
</script>
</head>
<body class="font-serif text-gray-100 bg-zinc-900">

<!-- Navbar -->
<nav class="fixed top-0 left-0 w-full bg-zinc-900 bg-opacity-80 backdrop-blur-sm z-50 shadow-md">
  <div class="max-w-7xl mx-auto px-6 py-3 flex justify-center items-center relative">
    
    <!-- Links Desktop Centralizados -->
    <div class="hidden md:flex gap-6">
      <a href="formulariocasa.php" class="nav-link py-2 px-5 bg-gradient-to-br from-zinc-800 to-zinc-600 text-white font-semibold rounded-lg shadow hover:scale-105 transition">
        ANUNCIE SUA CASA
      </a>
      <a href="escolhacd.php" class="nav-link py-2 px-5 bg-gradient-to-br from-zinc-800 to-zinc-600 text-white font-semibold rounded-lg shadow hover:scale-105 transition">
        COMPRE UMA CASA
      </a>
      <a href="loginstaff.php" class="nav-link py-2 px-5 bg-gradient-to-br from-zinc-800 to-zinc-600 text-white font-semibold rounded-lg shadow hover:scale-105 transition">
        GESTOR
      </a>
    </div>

    <!-- Botão Hamburguer Mobile -->
    <button onclick="toggleMenu()" class="md:hidden absolute right-6 text-white focus:outline-none">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
  </div>

  <!-- Menu Mobile -->
  <div id="mobile-menu" class="hidden md:hidden flex flex-col items-center gap-3 pb-4 z-50 relative">
    <a href="formulariocasa.php" class="w-11/12 text-center py-2 px-4 bg-gradient-to-br from-zinc-800 to-zinc-600 text-white font-semibold rounded-lg shadow hover:scale-105 transition">
      ANUNCIE SUA CASA
    </a>
    <a href="escolhacd.php" class="w-11/12 text-center py-2 px-4 bg-gradient-to-br from-zinc-800 to-zinc-600 text-white font-semibold rounded-lg shadow hover:scale-105 transition">
      COMPRE UMA CASA
    </a>
    <a href="glogin.php" class="w-11/12 text-center py-2 px-4 bg-gradient-to-br from-zinc-800 to-zinc-600 text-white font-semibold rounded-lg shadow hover:scale-105 transition">
      GESTOR
    </a>
  </div>
</nav>

<!-- Overlay escuro -->
<div id="menu-overlay" onclick="toggleMenu()" class="hidden fixed inset-0 bg-black z-40 transition duration-300"></div>

<!-- Hero Section -->
<section class="relative h-screen w-full bg-cover bg-center pt-20" style="background-image: url('imagem/2.jfif');">
  <div class="absolute inset-0 bg-black bg-opacity-45 backdrop-blur-sm"></div>
  <div class="absolute inset-0" style="background: radial-gradient(circle at center, rgba(0,0,0,0) 70%, rgba(0,0,0,0.5) 100%);"></div>
  <div class="relative z-10 flex flex-col items-center justify-center h-full px-4 text-center fade-in">
    
    <!-- Logo centralizada -->
    <img src="imagem/logo.png" alt="Logo Imobi Central" class="w-40 md:w-52 lg:w-64 mb-6 float-logo">

    <h1 class="text-3xl md:text-4xl lg:text-5xl font-semibold leading-snug drop-shadow-md">
      Vamos te ajudar <br /> a mudar
    </h1>
  </div>
</section>

<!-- Sobre Nós -->
<section class="py-20 px-6 bg-zinc-800">
<div class="max-w-5xl mx-auto text-center fade-in">
<h2 class="text-3xl md:text-4xl font-semibold mb-6">Sobre Nós</h2>
<p class="text-gray-300 text-lg md:text-xl leading-relaxed">
A Imobi Central conecta pessoas com seus lares dos sonhos. Com anos de experiência no mercado imobiliário, oferecemos soluções personalizadas para compradores e vendedores.
</p>
</div>
</section>

<!-- Destaques de Imóveis -->
<section class="py-20 px-6 bg-zinc-900">
<div class="max-w-6xl mx-auto fade-in">
<h2 class="text-3xl md:text-4xl font-semibold mb-12 text-center">Destaques de Imóveis</h2>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
<?php
// Buscar 3 maiores valores
$res_high = $conn->query("SELECT * FROM IMOVEL ORDER BY valor DESC LIMIT 3");
// Buscar 3 menores valores
$res_low = $conn->query("SELECT * FROM IMOVEL ORDER BY valor ASC LIMIT 3");

$imoveis = [];
while($row = $res_high->fetch_assoc()) $imoveis[] = $row;
while($row = $res_low->fetch_assoc()) $imoveis[] = $row;

foreach($imoveis as $imovel){
    $img = "imagem/imovel_padrao.jpg";
    // Verificar se tem imagem no IMAGEM_IMOVEL
    $res_img = $conn->query("SELECT caminho FROM IMAGEM_IMOVEL WHERE IMOVEL_idIMOVEL=".$imovel['idIMOVEL']." LIMIT 1");
    if($res_img && $res_img->num_rows>0){
        $img_row = $res_img->fetch_assoc();
        $img = $img_row['caminho'];
    }

    echo '<div class="bg-zinc-800 rounded-xl overflow-hidden shadow-lg hover:scale-105 transition cursor-pointer" onclick="location.href=\'detalhes_imovel.php?id='.$imovel['idIMOVEL'].'\'">
    <img src="'.$img.'" alt="'.$imovel['tipo'].'" class="w-full h-52 object-cover"/>
    <div class="p-4">
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
<section class="py-20 px-6 bg-zinc-800">
<div class="max-w-4xl mx-auto fade-in text-center">
<h2 class="text-3xl md:text-4xl font-semibold mb-12">O que nossos clientes dizem</h2>
<div class="space-y-8">
<div class="bg-zinc-900 p-6 rounded-xl shadow-lg">
<p class="text-gray-300 italic">"A Imobi Central nos ajudou a encontrar nossa casa perfeita de forma rápida e segura!"</p>
<span class="block mt-4 font-bold">– João S.</span>
</div>
<div class="bg-zinc-900 p-6 rounded-xl shadow-lg">
<p class="text-gray-300 italic">"Vender meu imóvel nunca foi tão fácil. Excelente atendimento e profissionalismo."</p>
<span class="block mt-4 font-bold">– Maria L.</span>
</div>
</div>
</div>
</section>

<!-- Contato -->
<section class="py-20 px-6 bg-zinc-900">
<div class="max-w-3xl mx-auto fade-in text-center">
<h2 class="text-3xl md:text-4xl font-semibold mb-6">Entre em Contato</h2>
<p class="text-gray-300 mb-8">Preencha o formulário abaixo e nossa equipe entrará em contato rapidamente.</p>

<?php
if($sucesso) echo '<p class="text-green-400 font-semibold mb-4">Mensagem enviada com sucesso!</p>';
elseif($erro) echo '<p class="text-red-400 font-semibold mb-4">'.$erro.'</p>';
?>

<form class="flex flex-col gap-4" action="" method="POST">
<input type="text" name="nome" placeholder="Nome" class="p-3 rounded-lg bg-zinc-800 text-white focus:outline-none focus:ring-2 focus:ring-zinc-600" required/>
<input type="email" name="email" placeholder="Email" class="p-3 rounded-lg bg-zinc-800 text-white focus:outline-none focus:ring-2 focus:ring-zinc-600" required/>
<input type="text" name="telefone" placeholder="Telefone" class="p-3 rounded-lg bg-zinc-800 text-white focus:outline-none focus:ring-2 focus:ring-zinc-600"/>
<textarea name="mensagem" placeholder="Mensagem" class="p-3 rounded-lg bg-zinc-800 text-white focus:outline-none focus:ring-2 focus:ring-zinc-600" required></textarea>
<button type="submit" class="py-3 px-5 bg-gradient-to-br from-zinc-800 to-zinc-600 font-bold rounded-lg shadow-lg hover:scale-105 hover:shadow-xl transition">
Enviar Mensagem
</button>
</form>
</div>
</section>

<!-- Footer -->
<footer class="py-6 bg-zinc-800 text-center text-gray-400">
© 2025 Imobi Central. Todos os direitos reservados.
</footer>

</body>
</html>
