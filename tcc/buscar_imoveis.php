<?php
include('conexao.php'); // Conexão já criada em $conn

// Recebe os filtros via GET
$cidade = isset($_GET['cidade']) ? $conn->real_escape_string($_GET['cidade']) : '';
$tipo = isset($_GET['tipo']) ? $conn->real_escape_string($_GET['tipo']) : '';
$preco_min = isset($_GET['preco_min']) ? floatval($_GET['preco_min']) : '';
$preco_max = isset($_GET['preco_max']) ? floatval($_GET['preco_max']) : '';
$quartos = isset($_GET['quartos']) ? intval($_GET['quartos']) : '';
$banheiros = isset($_GET['banheiros']) ? intval($_GET['banheiros']) : '';
$vagas = isset($_GET['vagas']) ? intval($_GET['vagas']) : '';

// Consulta SQL básica
$query = "SELECT I.*, IMG.caminho 
          FROM IMOVEL I 
          LEFT JOIN IMAGEM_IMOVEL IMG ON I.idIMOVEL = IMG.IMOVEL_idIMOVEL 
          WHERE I.status = 'Disponivel'";

// Adiciona filtros somente se o valor estiver definido
if (!empty($cidade)) $query .= " AND I.cidade LIKE '%$cidade%'";
if (!empty($tipo)) $query .= " AND I.tipo = '$tipo'";
if ($preco_min !== '') $query .= " AND I.valor >= $preco_min";
if ($preco_max !== '') $query .= " AND I.valor <= $preco_max";
if ($quartos !== '') $query .= " AND I.qtd_quartos >= $quartos";
if ($banheiros !== '') $query .= " AND I.qtd_banheiro >= $banheiros";
if ($vagas !== '') $query .= " AND I.qtd_vagas >= $vagas";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Imóveis Disponíveis</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
/* animação fadeUp */
@keyframes fadeUp {
  from { transform: translateY(40px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
.animate-fadeUp { animation: fadeUp 0.8s ease forwards; }

/* modal overlay sem JS */
#painelFiltro {
  display: none;
}
#abrirFiltro:checked ~ #painelFiltro {
  display: flex;
}
</style>
</head>
<body class="bg-gray-900 text-gray-100 font-sans p-6">

<!-- Título -->
<h1 class="text-4xl sm:text-5xl font-semibold mb-10 text-center">Imóveis Disponíveis</h1>

<!-- Checkbox invisível para abrir/fechar modal -->
<input type="checkbox" id="abrirFiltro" class="hidden">

<!-- Botão para abrir filtro -->
<label for="abrirFiltro" class="fixed top-6 right-6 z-50 bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg cursor-pointer">
  Filtrar
</label>

<!-- Painel de filtros (modal) -->
<div id="painelFiltro" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 flex justify-center items-start pt-20">
  <div class="bg-gray-900 p-6 rounded-xl w-11/12 max-w-md shadow-2xl">
    <h2 class="text-xl font-bold mb-4 text-center">BUSCA FILTRADA</h2>
    
    <form method="GET" action="buscar_imoveis.php">
      <!-- Cidade -->
      <div class="mb-4">
        <label class="block mb-1">Localização</label>
        <input type="text" name="cidade" placeholder="Digite uma cidade" value="<?= htmlspecialchars($cidade) ?>"
               class="w-full p-2 rounded-lg bg-gray-700 text-white placeholder-gray-400">
      </div>
      
      <!-- Tipo de imóvel -->
      <div class="mb-4 flex gap-2">
        <?php 
        $tipos = ['Casa','Apartamento','Alugar'];
        foreach($tipos as $t): 
            $checked = ($tipo == $t) ? 'checked' : '';
            $active = ($tipo == $t) ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-100 hover:bg-gray-600';
        ?>
          <label class="flex-1 cursor-pointer p-3 rounded-lg text-center <?= $active ?>">
            <input type="radio" name="tipo" value="<?= $t ?>" class="hidden" <?= $checked ?>>
            <?= $t ?>
          </label>
        <?php endforeach; ?>
      </div>
      
      <!-- Preço -->
      <div class="mb-4 flex gap-2">
        <input type="number" name="preco_min" placeholder="Preço mínimo" value="<?= htmlspecialchars($preco_min) ?>"
               class="w-1/2 p-2 rounded-lg bg-gray-700 text-white">
        <input type="number" name="preco_max" placeholder="Preço máximo" value="<?= htmlspecialchars($preco_max) ?>"
               class="w-1/2 p-2 rounded-lg bg-gray-700 text-white">
      </div>
      
      <!-- Quartos, Banheiros e Vagas -->
      <div class="mb-4 grid grid-cols-1 sm:grid-cols-3 gap-2">
        <input type="number" name="quartos" placeholder="Quartos" value="<?= htmlspecialchars($quartos) ?>"
               class="p-2 rounded-lg bg-gray-700 text-white">
        <input type="number" name="banheiros" placeholder="Banheiros" value="<?= htmlspecialchars($banheiros) ?>"
               class="p-2 rounded-lg bg-gray-700 text-white">
        <input type="number" name="vagas" placeholder="Vagas" value="<?= htmlspecialchars($vagas) ?>"
               class="p-2 rounded-lg bg-gray-700 text-white">
      </div>
      
      <!-- Botões -->
      <div class="flex gap-2 mt-4">
        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 rounded-lg hover:bg-blue-500 font-semibold">Pesquisar</button>
        <a href="buscar_imoveis.php" class="flex-1 px-4 py-2 bg-gray-600 rounded-lg hover:bg-gray-500 text-center">Resetar</a>
        <label for="abrirFiltro" class="flex-1 px-4 py-2 bg-gray-700 rounded-lg hover:bg-gray-600 text-center cursor-pointer">Fechar</label>
      </div>
    </form>
  </div>
</div>

<!-- Grid de imóveis -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 mt-10">
<?php if ($result && $result->num_rows > 0): ?>
  <?php $delay = 0; ?>
  <?php while ($row = $result->fetch_assoc()): ?>
    <a href="detalhes_imovel.php?id=<?= $row['idIMOVEL'] ?>" 
       class="bg-gray-800 rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-300 hover:-translate-y-2 hover:shadow-3xl hover:bg-gray-700 animate-fadeUp" 
       style="animation-delay:<?= $delay ?>s;">
      <img src="<?= $row['caminho'] ? htmlspecialchars($row['caminho']) : 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80' ?>" 
           alt="Imagem do Imóvel"
           class="w-full h-44 object-cover transition-transform duration-300 hover:scale-105 border-b-2 border-gray-600">
      <div class="p-5">
        <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($row['tipo']) ?> - R$ <?= number_format($row['valor'],2,',','.') ?></h3>
        <p class="text-gray-300 text-sm mb-1"><strong>Endereço:</strong> <?= htmlspecialchars($row['rua']) ?>, <?= htmlspecialchars($row['numero']) ?> - <?= htmlspecialchars($row['bairro']) ?>, <?= htmlspecialchars($row['cidade']) ?></p>
        <p class="text-gray-300 text-sm"><strong>Descrição:</strong> <?= htmlspecialchars($row['descricao']) ?></p>
        <p class="text-gray-300 text-sm mt-1"><strong>Quartos:</strong> <?= $row['qtd_quartos'] ?> | <strong>Banheiros:</strong> <?= $row['qtd_banheiro'] ?> | <strong>Vagas:</strong> <?= $row['qtd_vagas'] ?></p>
      </div>
    </a>
    <?php $delay += 0.1; ?>
  <?php endwhile; ?>
<?php else: ?>
  <p class="col-span-full text-center text-gray-400">Nenhum imóvel encontrado.</p>
<?php endif; ?>
</div>

</body>
</html>
