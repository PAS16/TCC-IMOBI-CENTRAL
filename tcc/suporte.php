<?php
include('conexao.php'); // conexão com $conn
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
    $texto = trim($_POST['mensagem'] ?? '');

    if (empty($nome)) {
        $mensagem = "O nome é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "E-mail inválido.";
    } elseif (empty($texto)) {
        $mensagem = "A mensagem não pode estar vazia.";
    } else {
        $stmt = $conn->prepare("INSERT INTO mensagens_suporte (nome, email, telefone, mensagem) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome, $email, $telefone, $texto);

        if ($stmt->execute()) {
            $mensagemWhatsApp = "Nova mensagem de suporte:\n"
                . "Nome: $nome\n"
                . "Email: $email\n"
                . "Telefone: $telefone\n"
                . "Mensagem: $texto";

            $mensagemCodificada = urlencode($mensagemWhatsApp);
            $numero = "5513988393768";

            header("Location: https://api.whatsapp.com/send?phone=$numero&text=$mensagemCodificada");
            exit;
        } else {
            $mensagem = "Erro ao enviar mensagem: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Suporte - Imobi Central</title>
<script src="https://cdn.tailwindcss.com"></script>

<style>
@keyframes fadeUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
.animate-fadeUp { animation: fadeUp 0.8s ease forwards; }

/* Nav link underline animado */
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
.nav-link:hover::after { width: 100%; }
</style>
</head>

<body class="font-sans text-gray-100 bg-gray-900">

<!-- Inclui navbar padronizada -->
<?php
$pagina_atual = 'suporte';
include 'navbar.php';
?>


<!-- Formulário de suporte -->
<div class="flex-grow flex justify-center items-start p-6">
  <?php if($mensagem): ?>
    <div class="bg-red-700 text-white p-6 rounded-2xl shadow-2xl text-center w-full max-w-xl animate-fadeUp">
      <p class="text-lg font-semibold"><?php echo $mensagem; ?></p>
      <button onclick="history.back()" class="mt-4 bg-gray-700 hover:bg-gray-600 px-6 py-3 rounded-lg font-semibold">Voltar</button>
    </div>
  <?php else: ?>
    <form action="" method="POST" class="bg-gray-800 rounded-2xl shadow-2xl p-8 w-full max-w-2xl space-y-6 animate-fadeUp">
      <h2 class="text-2xl font-bold text-center mb-4">Entre em Contato</h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block mb-2 font-semibold">Seu Nome:</label>
          <input type="text" name="nome" class="w-full p-2 rounded-lg bg-gray-700 text-white" required>
        </div>
        <div>
          <label class="block mb-2 font-semibold">Seu Email:</label>
          <input type="email" name="email" class="w-full p-2 rounded-lg bg-gray-700 text-white" required>
        </div>
      </div>

      <div>
        <label class="block mb-2 font-semibold">Telefone:</label>
        <input type="text" name="telefone" pattern="[0-9]{8,}" title="Digite apenas números (mínimo 8 dígitos)" class="w-full p-2 rounded-lg bg-gray-700 text-white">
      </div>

      <div>
        <label class="block mb-2 font-semibold">Mensagem:</label>
        <textarea name="mensagem" rows="5" class="w-full p-2 rounded-lg bg-gray-700 text-white" required></textarea>
      </div>

      <div class="flex justify-between mt-6 gap-4">
        <button type="button" onclick="history.back()" class="flex-1 bg-gray-700 hover:bg-gray-600 py-3 rounded-lg font-semibold">VOLTAR</button>
        <button type="submit" class="flex-1 bg-green-600 hover:bg-green-500 py-3 rounded-lg font-semibold">ENVIAR</button>
      </div>
    </form>
  <?php endif; ?>
</div>

</body>
</html>
