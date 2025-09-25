<?php
// processa_imovel_pendente.php
include('conexao.php'); // Conexão com $conn
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Dados do proprietário ---
    $nome_proprietario = trim($_POST['nome_proprietario'] ?? '');
    $email_proprietario = trim($_POST['email_proprietario'] ?? '');
    $telefone_proprietario = preg_replace('/\D/', '', $_POST['telefone_proprietario'] ?? ''); // só números

    // --- Validações servidor ---
    if (empty($nome_proprietario)) {
        $mensagem = "O nome é obrigatório.";
    } elseif (!filter_var($email_proprietario, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "E-mail inválido.";
    } elseif (strlen($telefone_proprietario) < 8) {
        $mensagem = "O telefone deve ter pelo menos 8 dígitos.";
    } else {
        // --- Dados do imóvel ---
        $tipo = $_POST['tipo'] ?? '';
        $finalidade = $_POST['finalidade'] ?? '';
        $rua = $_POST['rua'] ?? '';
        $numero = $_POST['numero'] ?? '';
        $bairro = $_POST['bairro'] ?? '';
        $cidade = $_POST['cidade'] ?? '';
        $estado = $_POST['estado'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $quartos = intval($_POST['quartos'] ?? 0);
        $banheiros = intval($_POST['banheiros'] ?? 0);
        $vagas = intval($_POST['vagas'] ?? 0);
        $area = !empty($_POST['area']) ? floatval($_POST['area']) : NULL;
        $valor = floatval($_POST['valor'] ?? 0);
        $negociavel = $_POST['negociavel'] ?? 'nao';

        // --- Insert no banco ---
        $stmt = $conn->prepare("
            INSERT INTO IMOVEL_PENDENTE 
            (nome_proprietario, email_proprietario, telefone_proprietario, rua, numero, bairro, cidade, estado, tipo, finalidade, descricao, qtd_quartos, qtd_banheiro, qtd_vagas, area, valor, negociavel) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssssssssssiiiids",
            $nome_proprietario,
            $email_proprietario,
            $telefone_proprietario,
            $rua,
            $numero,
            $bairro,
            $cidade,
            $estado,
            $tipo,
            $finalidade,
            $descricao,
            $quartos,
            $banheiros,
            $vagas,
            $area,
            $valor,
            $negociavel
        );

        if($stmt->execute()){
            $imovel_id = $stmt->insert_id;

            // --- Processa imagens ---
            if (!empty($_FILES['imagens']['name'][0])) {
                $uploadDir = "uploads/imoveis_pendentes/";
                if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                foreach ($_FILES['imagens']['tmp_name'] as $key => $tmp_name) {
                    $originalName = $_FILES['imagens']['name'][$key];
                    $fileTmp = $_FILES['imagens']['tmp_name'][$key];
                    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                    $newName = uniqid() . '.' . $ext;
                    $destino = $uploadDir . $newName;

                    if(move_uploaded_file($fileTmp, $destino)) {
                        $stmtImg = $conn->prepare("
                            INSERT INTO IMAGEM_IMOVEL_PENDENTE 
                            (IMOVEL_PENDENTE_id, caminho, nome_original) 
                            VALUES (?, ?, ?)
                        ");
                        $stmtImg->bind_param("iss", $imovel_id, $destino, $originalName);
                        $stmtImg->execute();
                        $stmtImg->close();
                    }
                }
            }

            $stmt->close();
            $mensagem = "Assim que seu imóvel for aprovado, entraremos em contato com você!";
        } else {
            $mensagem = "Erro ao cadastrar imóvel: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro de Imóvel</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 font-sans min-h-screen flex flex-col">

<!-- Navbar (ajustada, sem "Meus Imóveis") -->
<nav class="bg-gray-800 p-4 flex justify-center gap-4 sticky top-0 z-50">
  <button onclick="location.href='index.php'" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg font-semibold">INÍCIO</button>
  <button onclick="location.href='suporte.php'" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg font-semibold">SUPORTE</button>
</nav>

<div class="flex-grow flex justify-center items-start p-6">
<?php if($mensagem): ?>
  <div class="bg-green-700 text-white p-6 rounded-2xl shadow-2xl text-center w-full max-w-xl animate-fadeUp">
    <p class="text-lg font-semibold"><?php echo $mensagem; ?></p>
    <button onclick="location.href='index.php'" class="mt-4 bg-gray-700 hover:bg-gray-600 px-6 py-3 rounded-lg font-semibold">Voltar ao Início</button>
  </div>
<?php else: ?>
  <form action="" method="POST" enctype="multipart/form-data" class="bg-gray-800 rounded-2xl shadow-2xl p-8 w-full max-w-3xl space-y-6 animate-fadeUp">
    <h2 class="text-2xl font-bold text-center mb-4">Cadastro de Imóvel</h2>

    <!-- Dados do Proprietário -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div>
        <label class="block mb-2 font-semibold">Seu Nome:</label>
        <input type="text" name="nome_proprietario" class="w-full p-2 rounded-lg bg-gray-700 text-white" required>
      </div>
      <div>
        <label class="block mb-2 font-semibold">Seu Email:</label>
        <input type="email" name="email_proprietario" class="w-full p-2 rounded-lg bg-gray-700 text-white" required>
      </div>
      <div>
        <label class="block mb-2 font-semibold">Telefone:</label>
        <input type="text" name="telefone_proprietario" pattern="[0-9]{8,}" title="Digite apenas números (mínimo 8 dígitos)" class="w-full p-2 rounded-lg bg-gray-700 text-white" required>
      </div>
    </div>

    <!-- Tipo de Imóvel -->
    <div>
      <label class="block mb-2 font-semibold">Tipo de Imóvel:</label>
      <div class="flex flex-wrap gap-4">
        <label class="flex items-center gap-2"><input type="radio" name="tipo" value="casa" required class="accent-blue-500"> Casa</label>
        <label class="flex items-center gap-2"><input type="radio" name="tipo" value="apartamento" class="accent-blue-500"> Apartamento</label>
        <label class="flex items-center gap-2"><input type="radio" name="tipo" value="terreno" class="accent-blue-500"> Terreno</label>
        <label class="flex items-center gap-2"><input type="radio" name="tipo" value="outro" class="accent-blue-500"> Outro</label>
      </div>
    </div>

    <!-- Finalidade -->
    <div>
      <label class="block mb-2 font-semibold">Finalidade:</label>
      <div class="flex flex-wrap gap-4">
        <label class="flex items-center gap-2"><input type="radio" name="finalidade" value="alugar" required class="accent-blue-500"> Alugar</label>
        <label class="flex items-center gap-2"><input type="radio" name="finalidade" value="temporada" class="accent-blue-500"> Temporada</label>
        <label class="flex items-center gap-2"><input type="radio" name="finalidade" value="vender" class="accent-blue-500"> Vender</label>
      </div>
    </div>

    <!-- Endereço completo -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block mb-2 font-semibold">Rua:</label>
        <input type="text" name="rua" class="w-full p-2 rounded-lg bg-gray-700 text-white" required>
      </div>
      <div>
        <label class="block mb-2 font-semibold">Número:</label>
        <input type="text" name="numero" class="w-full p-2 rounded-lg bg-gray-700 text-white" required>
      </div>
      <div>
        <label class="block mb-2 font-semibold">Bairro:</label>
        <input type="text" name="bairro" class="w-full p-2 rounded-lg bg-gray-700 text-white" required>
      </div>
      <div>
        <label class="block mb-2 font-semibold">Cidade:</label>
        <input type="text" name="cidade" class="w-full p-2 rounded-lg bg-gray-700 text-white" required>
      </div>
      <div>
        <label class="block mb-2 font-semibold">Estado (UF):</label>
        <input type="text" name="estado" maxlength="2" class="w-full p-2 rounded-lg bg-gray-700 text-white" required>
      </div>
    </div>

    <!-- Descrição -->
    <div>
      <label class="block mb-2 font-semibold">Descrição:</label>
      <textarea name="descricao" rows="3" class="w-full p-2 rounded-lg bg-gray-700 text-white" required></textarea>
    </div>

    <!-- Quartos, Banheiros, Vagas e Área -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div>
        <label class="block mb-2 font-semibold">Quartos:</label>
        <input type="number" name="quartos" min="0" class="w-full p-2 rounded-lg bg-gray-700 text-white" required>
      </div>
      <div>
        <label class="block mb-2 font-semibold">Banheiros:</label>
        <input type="number" name="banheiros" min="0" class="w-full p-2 rounded-lg bg-gray-700 text-white" required>
      </div>
      <div>
        <label class="block mb-2 font-semibold">Vagas:</label>
        <input type="number" name="vagas" min="0" class="w-full p-2 rounded-lg bg-gray-700 text-white">
      </div>
      <div>
        <label class="block mb-2 font-semibold">Área (m²):</label>
        <input type="number" name="area" min="0" class="w-full p-2 rounded-lg bg-gray-700 text-white">
      </div>
    </div>

    <!-- Valor e Negociável -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
      <div>
        <label class="block mb-2 font-semibold">Valor (R$):</label>
        <input type="number" name="valor" min="0" step="0.01" class="w-full p-2 rounded-lg bg-gray-700 text-white" required>
      </div>
      <div>
        <label class="block mb-2 font-semibold">Negociável:</label>
        <div class="flex gap-4">
          <label class="flex items-center gap-2"><input type="radio" name="negociavel" value="sim" required class="accent-blue-500"> Sim</label>
          <label class="flex items-center gap-2"><input type="radio" name="negociavel" value="nao" class="accent-blue-500"> Não</label>
        </div>
      </div>
    </div>

    <!-- Upload de Imagens com preview -->
    <div>
      <label class="block mb-2 font-semibold">Anexar Imagens:</label>
      <input type="file" name="imagens[]" accept="image/*" multiple class="w-full p-2 rounded-lg bg-gray-700 text-white" id="imagensInput">
      <div id="preview" class="flex flex-wrap gap-4 mt-4"></div>
    </div>

    <!-- Botões -->
    <div class="flex justify-between mt-6 gap-4">
      <button type="button" onclick="history.back()" class="flex-1 bg-gray-700 hover:bg-gray-600 py-3 rounded-lg font-semibold">VOLTAR</button>
      <button type="submit" class="flex-1 bg-green-600 hover:bg-green-500 py-3 rounded-lg font-semibold">CONCLUIR</button>
    </div>
  </form>
<?php endif; ?>
</div>

<script>
const input = document.getElementById('imagensInput');
const preview = document.getElementById('preview');

input.addEventListener('change', () => {
  preview.innerHTML = '';
  const files = input.files;
  if(files){
    Array.from(files).forEach(file => {
      const reader = new FileReader();
      reader.onload = e => {
        const img = document.createElement('img');
        img.src = e.target.result;
        img.className = "w-24 h-24 object-cover rounded-lg border border-gray-500";
        preview.appendChild(img);
      };
      reader.readAsDataURL(file);
    });
  }
});
</script>

<style>
@keyframes fadeUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
.animate-fadeUp { animation: fadeUp 0.8s ease forwards; }
</style>
</body>
</html>
