<?php
include '../conexao.php';

$id = $_GET['id'] ?? 0;
if (!$id) {
    die("Imóvel inválido.");
}

// Busca os dados do imóvel pendente
$stmt = $conn->prepare("SELECT * FROM IMOVEL_PENDENTE WHERE idIMOVEL_PENDENTE = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$imovel = $stmt->get_result()->fetch_assoc();

if (!$imovel) {
    die("Imóvel não encontrado.");
}

// Busca as imagens do imóvel pendente
$stmtImg = $conn->prepare("SELECT * FROM IMAGEM_IMOVEL_PENDENTE WHERE IMOVEL_PENDENTE_id = ?");
$stmtImg->bind_param("i", $id);
$stmtImg->execute();
$imagens = $stmtImg->get_result()->fetch_all(MYSQLI_ASSOC);

// ----------------- REMOVER IMAGEM -----------------
if (isset($_GET['remover_img'])) {
    $idImg = intval($_GET['remover_img']);
    $stmtDelImg = $conn->prepare("DELETE FROM IMAGEM_IMOVEL_PENDENTE WHERE idIMAGEM_PENDENTE = ?");
    $stmtDelImg->bind_param("i", $idImg);
    $stmtDelImg->execute();
    header("Location: conclusaop.php?id=$id");
    exit;
}

// ----------------- APROVAR FORM -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'];
    $rua = $_POST['rua'];
    $numero = $_POST['numero'];
    $bairro = $_POST['bairro'];
    $cidade = $_POST['cidade'];
    $estado = $_POST['estado'];
    $valor = $_POST['valor'];
    $descricao = $_POST['descricao'];
    $qtd_quartos = $_POST['qtd_quartos'];
    $qtd_banheiro = $_POST['qtd_banheiro'];
    $qtd_vagas = $_POST['qtd_vagas'];
    $status = $_POST['status'];

    // Verifica se proprietário já existe (usando telefone como chave)
    $stmtProp = $conn->prepare("SELECT idPROPRIETARIO FROM PROPRIETARIO WHERE telefone = ?");
    $stmtProp->bind_param("s", $imovel['telefone_proprietario']);
    $stmtProp->execute();
    $resProp = $stmtProp->get_result();

    if ($resProp->num_rows > 0) {
        $idProprietario = $resProp->fetch_assoc()['idPROPRIETARIO'];
    } else {
        $stmtInsertProp = $conn->prepare("INSERT INTO PROPRIETARIO (nome, cpf, telefone, email) VALUES (?, ?, ?, ?)");
        $fakeCpf = substr(md5($imovel['telefone_proprietario']), 0, 11);
        $stmtInsertProp->bind_param("ssss", $imovel['nome_proprietario'], $fakeCpf, $imovel['telefone_proprietario'], $imovel['email_proprietario']);
        $stmtInsertProp->execute();
        $idProprietario = $stmtInsertProp->insert_id;
    }

    // Cria imóvel definitivo
    $stmtInsertImovel = $conn->prepare("INSERT INTO IMOVEL 
        (PROPRIETARIO_idPROPRIETARIO, tipo, rua, numero, bairro, cidade, estado, valor, descricao, status, qtd_quartos, qtd_banheiro, qtd_vagas) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtInsertImovel->bind_param("issssssdssiii", 
        $idProprietario, $tipo, $rua, $numero, $bairro, $cidade, $estado, $valor, $descricao, $status, $qtd_quartos, $qtd_banheiro, $qtd_vagas
    );
    $stmtInsertImovel->execute();
    $idImovelNovo = $stmtInsertImovel->insert_id;

    // Salva imagens existentes
    foreach ($imagens as $img) {
        $stmtInsertImg = $conn->prepare("INSERT INTO IMAGEM_IMOVEL (IMOVEL_idIMOVEL, caminho, nome_original) VALUES (?, ?, ?)");
        $stmtInsertImg->bind_param("iss", $idImovelNovo, $img['caminho'], $img['nome_original']);
        $stmtInsertImg->execute();
    }

    // Upload de novas imagens
    if (!empty($_FILES['novas_imagens']['name'][0])) {
        $uploadDir = "uploads/imoveis_pendentes/"; // <-- só caminho relativo para o navegador
        if (!is_dir("../" . $uploadDir)) mkdir("../" . $uploadDir, 0777, true);

        foreach ($_FILES['novas_imagens']['tmp_name'] as $key => $tmp) {
            $nomeOriginal = $_FILES['novas_imagens']['name'][$key];
            $ext = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
            $nomeUnico = uniqid() . "." . $ext;

            $destinoFS = "../" . $uploadDir . $nomeUnico; // caminho físico
            $destinoBanco = $uploadDir . $nomeUnico;     // caminho salvo no banco (sem ../)

            if (move_uploaded_file($tmp, $destinoFS)) {
                $stmtInsertImg = $conn->prepare("INSERT INTO IMAGEM_IMOVEL (IMOVEL_idIMOVEL, caminho, nome_original) VALUES (?, ?, ?)");
                $stmtInsertImg->bind_param("iss", $idImovelNovo, $destinoBanco, $nomeOriginal);
                $stmtInsertImg->execute();
            }
        }
    }

    // Remove da tabela pendente
    $stmtDel = $conn->prepare("DELETE FROM IMOVEL_PENDENTE WHERE idIMOVEL_PENDENTE = ?");
    $stmtDel->bind_param("i", $id);
    $stmtDel->execute();

    header("Location: ../buscar_imoveis.php?msg=Imóvel aprovado com sucesso!");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Conclusão de Imóvel Pendente</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-900 text-gray-100 min-h-screen p-8 font-serif">

<div class="max-w-4xl mx-auto bg-zinc-800 p-6 rounded-2xl shadow-lg">
    <h1 class="text-2xl font-bold mb-4 text-center">Conclusão do Cadastro</h1>
    <form method="post" enctype="multipart/form-data" class="space-y-4">

        <!-- Campos principais -->
        <div>
            <label class="block text-sm">Tipo do Imóvel</label>
            <select name="tipo" class="w-full p-2 rounded bg-zinc-700">
                <option <?= $imovel['tipo']=='Casa'?'selected':'' ?>>Casa</option>
                <option <?= $imovel['tipo']=='Apartamento'?'selected':'' ?>>Apartamento</option>
                <option <?= $imovel['tipo']=='Terreno'?'selected':'' ?>>Terreno</option>
            </select>
        </div>

        <div>
            <label class="block text-sm">Cidade</label>
            <select name="cidade" class="w-full p-2 rounded bg-zinc-700">
                <option <?= $imovel['cidade']=='Mongagua'?'selected':'' ?>>Mongagua</option>
                <option <?= $imovel['cidade']=='Itanhaem'?'selected':'' ?>>Itanhaem</option>
                <option <?= $imovel['cidade']=='Praia Grande'?'selected':'' ?>>Praia Grande</option>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm">Rua</label>
                <input type="text" name="rua" value="<?= htmlspecialchars($imovel['rua']) ?>" class="w-full p-2 rounded bg-zinc-700">
            </div>
            <div>
                <label class="block text-sm">Número</label>
                <input type="text" name="numero" value="<?= htmlspecialchars($imovel['numero']) ?>" class="w-full p-2 rounded bg-zinc-700">
            </div>
        </div>

        <div>
            <label class="block text-sm">Bairro</label>
            <input type="text" name="bairro" value="<?= htmlspecialchars($imovel['bairro']) ?>" class="w-full p-2 rounded bg-zinc-700">
        </div>

        <div>
            <label class="block text-sm">Estado</label>
            <input type="text" name="estado" maxlength="2" value="<?= htmlspecialchars($imovel['estado']) ?>" class="w-full p-2 rounded bg-zinc-700">
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm">Quartos</label>
                <input type="number" name="qtd_quartos" value="<?= $imovel['qtd_quartos'] ?>" class="w-full p-2 rounded bg-zinc-700">
            </div>
            <div>
                <label class="block text-sm">Banheiros</label>
                <input type="number" name="qtd_banheiro" value="<?= $imovel['qtd_banheiro'] ?>" class="w-full p-2 rounded bg-zinc-700">
            </div>
            <div>
                <label class="block text-sm">Vagas</label>
                <input type="number" name="qtd_vagas" value="<?= $imovel['qtd_vagas'] ?>" class="w-full p-2 rounded bg-zinc-700">
            </div>
        </div>

        <div>
            <label class="block text-sm">Valor</label>
            <input type="number" step="0.01" name="valor" value="<?= $imovel['valor'] ?>" class="w-full p-2 rounded bg-zinc-700">
        </div>

        <div>
            <label class="block text-sm">Descrição</label>
            <textarea name="descricao" rows="3" class="w-full p-2 rounded bg-zinc-700"><?= htmlspecialchars($imovel['descricao']) ?></textarea>
        </div>

        <div>
            <label class="block text-sm">Status</label>
            <select name="status" class="w-full p-2 rounded bg-zinc-700">
                <option value="Disponivel" selected>Disponível</option>
                <option value="Vendido">Vendido</option>
            </select>
        </div>

        <!-- Upload de novas imagens -->
        <div>
            <label class="block text-sm">Adicionar Novas Imagens</label>
            <input type="file" name="novas_imagens[]" multiple class="w-full p-2 bg-zinc-700 rounded">
        </div>

        <!-- Pré-visualização e edição de imagens -->
        <h2 class="text-xl font-semibold mt-6 mb-2">Imagens Existentes</h2>
        <div class="grid grid-cols-2 gap-4">
            <?php foreach ($imagens as $img): ?>
                <div class="relative">
                    <img src="<?= htmlspecialchars($img['caminho']) ?>" alt="Imagem do imóvel" class="rounded-lg shadow">
                    <a href="conclusaop.php?id=<?= $id ?>&remover_img=<?= $img['idIMAGEM_PENDENTE'] ?>" 
                       class="absolute top-2 right-2 bg-red-600 text-white px-2 py-1 text-xs rounded-full hover:bg-red-500">✕</a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flex gap-4 justify-center mt-6">
            <button type="submit" class="px-6 py-2 bg-green-600 rounded-lg hover:bg-green-500">Salvar e Aprovar</button>
            <button type="button" onclick="history.back()" class="px-6 py-2 bg-gray-600 rounded-lg hover:bg-gray-500">Cancelar</button>
        </div>
    </form>
</div>

</body>
</html>
