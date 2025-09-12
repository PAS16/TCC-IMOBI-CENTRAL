<?php
include 'conexao.php';

$termo = $_GET['termo'] ?? '';
$termo = trim($termo);

if (empty($termo)) {
    die("<p style='color:red;'>Digite algo para buscar.</p>");
}

// Se for número puro, verifica seguindo a prioridade
if (ctype_digit($termo)) {
    $id = intval($termo);

    // 1️⃣ Imóvel
    $res = $conn->query("SELECT idIMOVEL FROM IMOVEL WHERE idIMOVEL = $id LIMIT 1");
    if ($res && $res->num_rows) header("Location: imoveis/detalhes.php?id=$id");

    // 2️⃣ Cliente
    $res = $conn->query("SELECT idCLIENTE FROM CLIENTE WHERE idCLIENTE = $id LIMIT 1");
    if ($res && $res->num_rows) header("Location: clientes/editar.php?id=$id");

    // 3️⃣ Proprietário
    $res = $conn->query("SELECT idPROPRIETARIO FROM PROPRIETARIO WHERE idPROPRIETARIO = $id LIMIT 1");
    if ($res && $res->num_rows) header("Location: proprietario/editar.php?id=$id");

    // 4️⃣ Corretor
    $res = $conn->query("SELECT idCORRETOR FROM CORRETOR WHERE idCORRETOR = $id LIMIT 1");
    if ($res && $res->num_rows) header("Location: corretor/editar.php?id=$id");
}

// Busca textual normal
$like = "%" . $conn->real_escape_string($termo) . "%";

$sql = "
(
  SELECT 'Cliente' AS origem, idCLIENTE AS id, nome, telefone, email, cpf AS info_extra
  FROM CLIENTE
  WHERE nome LIKE ? OR telefone LIKE ? OR email LIKE ? OR cpf LIKE ?
)
UNION
(
  SELECT 'Proprietário' AS origem, idPROPRIETARIO AS id, nome, telefone, email, cpf AS info_extra
  FROM PROPRIETARIO
  WHERE nome LIKE ? OR telefone LIKE ? OR email LIKE ? OR cpf LIKE ?
)
UNION
(
  SELECT 'Corretor' AS origem, idCORRETOR AS id, nome, telefone, NULL AS email, creci AS info_extra
  FROM CORRETOR
  WHERE nome LIKE ? OR telefone LIKE ? OR creci LIKE ?
)
UNION
(
  SELECT 'Imóvel' AS origem, idIMOVEL AS id, tipo AS nome, NULL AS telefone, NULL AS email, cidade AS info_extra
  FROM IMOVEL
  WHERE tipo LIKE ? OR cidade LIKE ? OR bairro LIKE ? OR rua LIKE ?
)
LIMIT 50
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssssssssssss",
    $like,$like,$like,$like,
    $like,$like,$like,$like,
    $like,$like,$like,
    $like,$like,$like,$like
);
$stmt->execute();
$result = $stmt->get_result();

// Define cores para cada tipo
$cores = [
    'Imóvel' => 'bg-blue-600 hover:bg-blue-700',
    'Cliente' => 'bg-green-600 hover:bg-green-700',
    'Proprietário' => 'bg-yellow-600 hover:bg-yellow-700',
    'Corretor' => 'bg-purple-600 hover:bg-purple-700'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Resultados da Busca</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 p-6 font-sans">

  <h1 class="text-3xl font-bold mb-6 text-center">
    Resultados para: <span class="text-blue-400"><?= htmlspecialchars($termo) ?></span>
  </h1>

  <?php if ($result->num_rows > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php while ($row = $result->fetch_assoc()): ?>
        <?php
          $url = '#';
          $cor = $cores[$row['origem']] ?? 'bg-gray-600';
          switch($row['origem']){
              case 'Imóvel': $url="imoveis/detalhes.php?id=".$row['id']; break;
              case 'Cliente': $url="clientes/editar.php?id=".$row['id']; break;
              case 'Proprietário': $url="proprietario/editar.php?id=".$row['id']; break;
              case 'Corretor': $url="corretor/editar.php?id=".$row['id']; break;
          }
        ?>
        <a href="<?= $url ?>" class="block <?= $cor ?> rounded-xl shadow-lg p-6 transition transform hover:scale-105">
          <h2 class="text-xl font-semibold mb-2"><?= htmlspecialchars($row['origem']) ?> #<?= $row['id'] ?></h2>
          <p><strong>Nome:</strong> <?= htmlspecialchars($row['nome']) ?></p>
          <?php if ($row['telefone']): ?><p><strong>Telefone:</strong> <?= htmlspecialchars($row['telefone']) ?></p><?php endif; ?>
          <?php if ($row['email']): ?><p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p><?php endif; ?>
          <?php if ($row['info_extra']): ?><p><strong>Extra:</strong> <?= htmlspecialchars($row['info_extra']) ?></p><?php endif; ?>
        </a>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <p class="text-center text-red-400 text-lg">Nenhum resultado encontrado.</p>
  <?php endif; ?>

  <div class="mt-8 text-center">
    <a href="index.php"
       class="inline-block px-6 py-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition">
       ⬅ Voltar
    </a>
  </div>
</body>
</html>
