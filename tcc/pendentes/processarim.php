<?php
include '../conexao.php';

$msg = ''; // Mensagem de feedback

// ----------------- Rejeitar imóvel via POST -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rejeitar_id'])) {
    $id = intval($_POST['rejeitar_id']);
    if ($id) {
        // Deleta imagens relacionadas
        $stmtImg = $conn->prepare("DELETE FROM IMAGEM_IMOVEL_PENDENTE WHERE IMOVEL_PENDENTE_id = ?");
        $stmtImg->bind_param("i", $id);
        $stmtImg->execute();

        // Deleta o imóvel pendente
        $stmt = $conn->prepare("DELETE FROM IMOVEL_PENDENTE WHERE idIMOVEL_PENDENTE = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $msg = "Imóvel rejeitado com sucesso!";
    }
}

// ----------------- Buscar imóveis -----------------
$busca = $_GET['busca'] ?? '';
$where = '';
if (!empty($busca)) {
    $busca_esc = mysqli_real_escape_string($conn, $busca);
    $where = "WHERE tipo LIKE '%$busca_esc%' OR cidade LIKE '%$busca_esc%'";
}

$sql = "SELECT * FROM IMOVEL_PENDENTE $where ORDER BY data_envio DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Imóveis Pendentes</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-900 text-gray-100 min-h-screen p-8 font-serif">

<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold mb-6 text-center">Imóveis Pendentes</h1>

    <!-- Mensagem de feedback -->
    <?php if($msg): ?>
        <div class="bg-green-600 text-white p-3 rounded mb-4 text-center">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <!-- Barra de ações -->
    <div class="flex flex-wrap justify-between mb-6 gap-4">
        <a href="../staffmenu.php" class="px-4 py-2 bg-gray-700 rounded-lg hover:bg-gray-600 transition">Voltar</a>
        <form method="get" class="flex gap-2 flex-1 sm:flex-auto">
            <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar por tipo ou cidade..." class="flex-1 p-2 rounded-lg bg-zinc-800 text-white focus:outline-none">
            <button type="submit" class="px-4 py-2 bg-blue-600 rounded-lg hover:bg-blue-500 transition">Pesquisar</button>
        </form>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while($imovel = $result->fetch_assoc()):
                $idImovel = $imovel['idIMOVEL_PENDENTE'];
                $stmtImg = $conn->prepare("SELECT caminho FROM IMAGEM_IMOVEL_PENDENTE WHERE IMOVEL_PENDENTE_id = ?");
                $stmtImg->bind_param("i", $idImovel);
                $stmtImg->execute();
                $imgs = $stmtImg->get_result()->fetch_all(MYSQLI_ASSOC);
                if (empty($imgs)) {
                    $imgs = [["caminho" => "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80"]];
                }
            ?>
            <div class="bg-zinc-800 rounded-xl overflow-hidden shadow-lg hover:scale-105 transition relative">
                <img src="<?= htmlspecialchars($imgs[0]['caminho']) ?>" alt="Imagem do imóvel" class="w-full h-52 object-cover hover:brightness-90 transition">
                <div class="p-4">
                    <h2 class="text-xl font-bold mb-1"><?= htmlspecialchars($imovel['tipo']) ?> - R$ <?= number_format($imovel['valor'],2,',','.') ?></h2>
                    <p class="text-gray-300 text-sm mb-2"><?= htmlspecialchars($imovel['rua']) ?>, <?= htmlspecialchars($imovel['numero']) ?> - <?= htmlspecialchars($imovel['bairro']) ?>, <?= htmlspecialchars($imovel['cidade']) ?></p>
                    <p class="text-gray-300 text-sm mb-3">Quartos: <?= $imovel['qtd_quartos'] ?> | Banheiros: <?= $imovel['qtd_banheiro'] ?> | Vagas: <?= $imovel['qtd_vagas'] ?></p>

                    <div class="text-sm text-gray-400 mb-3">
                        <p><strong>Enviado por:</strong> <?= htmlspecialchars($imovel['nome_proprietario']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($imovel['email_proprietario']) ?></p>
                        <p><strong>Telefone:</strong> <?= htmlspecialchars($imovel['telefone_proprietario']) ?></p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <!-- Aprovar -->
                        <a href="conclusaop.php?id=<?= $imovel['idIMOVEL_PENDENTE'] ?>" class="px-3 py-1 bg-green-600 rounded-lg hover:bg-green-500 transition text-sm">Aprovar</a>

                        <!-- Rejeitar via modal -->
                        <button onclick="document.getElementById('modal-<?= $idImovel ?>').classList.remove('hidden')" class="px-3 py-1 bg-red-600 rounded-lg hover:bg-red-500 transition text-sm">
                            Rejeitar
                        </button>
                    </div>
                </div>

                <!-- Modal Rejeitar -->
                <div id="modal-<?= $idImovel ?>" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-zinc-800 p-6 rounded-2xl max-w-sm w-full shadow-lg relative">
                        <h2 class="text-xl font-bold mb-4">Confirmação de Rejeição</h2>
                        <p class="mb-4 text-gray-300">Deseja realmente rejeitar este imóvel? Todos os dados e imagens serão apagados.</p>
                        <div class="flex justify-end gap-4">
                            <button onclick="document.getElementById('modal-<?= $idImovel ?>').classList.add('hidden')" class="px-4 py-2 bg-gray-600 rounded hover:bg-gray-500">Cancelar</button>
                            <form method="post" class="inline">
                                <input type="hidden" name="rejeitar_id" value="<?= $imovel['idIMOVEL_PENDENTE'] ?>">
                                <button type="submit" class="px-4 py-2 bg-red-600 rounded hover:bg-red-500 text-white">Rejeitar</button>
                            </form>
                        </div>
                        <button onclick="document.getElementById('modal-<?= $idImovel ?>').classList.add('hidden')" class="absolute top-2 right-2 text-gray-400 hover:text-white">&times;</button>
                    </div>
                </div>

            </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="text-center text-gray-400 mt-8">Nenhum imóvel pendente encontrado.</p>
    <?php endif; ?>
</div>

</body>
</html>
