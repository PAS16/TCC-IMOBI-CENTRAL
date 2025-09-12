<?php
include '../conexao.php';

// -------------------- Ações AJAX --------------------
// Cadastrar
if (isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
    $nome = $_POST['nome'] ?? '';
    $creci = $_POST['creci'] ?? '';
    $telefone = $_POST['telefone'] ?? '';

    if ($nome && $creci) {
        $stmt = $conn->prepare("INSERT INTO CORRETOR (nome, creci, telefone) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nome, $creci, $telefone);
        echo json_encode($stmt->execute() ? ['status'=>'sucesso'] : ['status'=>'erro','mensagem'=>$stmt->error]);
    } else {
        echo json_encode(['status'=>'erro','mensagem'=>'Nome e CRECI obrigatórios']);
    }
    exit;
}

// Editar
if (isset($_POST['acao']) && $_POST['acao'] === 'editar') {
    $id = intval($_POST['id']);
    $nome = $_POST['nome'] ?? '';
    $creci = $_POST['creci'] ?? '';
    $telefone = $_POST['telefone'] ?? '';

    if ($id && $nome && $creci) {
        $stmt = $conn->prepare("UPDATE CORRETOR SET nome=?, creci=?, telefone=? WHERE idCORRETOR=?");
        $stmt->bind_param("sssi", $nome, $creci, $telefone, $id);
        echo json_encode($stmt->execute() ? ['status'=>'sucesso'] : ['status'=>'erro','mensagem'=>$stmt->error]);
    } else {
        echo json_encode(['status'=>'erro','mensagem'=>'Todos os campos obrigatórios']);
    }
    exit;
}

// Excluir
if (isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
    $id = intval($_POST['id']);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM CORRETOR WHERE idCORRETOR=?");
        $stmt->bind_param("i", $id);
        echo json_encode($stmt->execute() ? ['status'=>'sucesso'] : ['status'=>'erro','mensagem'=>$stmt->error]);
    } else {
        echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']);
    }
    exit;
}

// -------------------- Buscar / Listar --------------------
$busca = $_GET['busca'] ?? '';
$where = '';
if (!empty($busca)) {
    $busca_esc = mysqli_real_escape_string($conn, $busca);
    $where = "WHERE nome LIKE '%$busca_esc%'";
}
$sql = "SELECT * FROM CORRETOR $where ORDER BY idCORRETOR ASC";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Corretores</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-zinc-900 text-gray-100 min-h-screen p-8 font-serif">

<div class="max-w-5xl mx-auto">
    <h2 class="text-3xl font-bold mb-6 text-center">Corretores</h2>

    <div class="flex gap-2 mb-4">
        <button onclick="abrirModal('cadastrar')" class="px-4 py-2 bg-green-600 rounded hover:bg-green-500">Cadastrar Novo</button>
        <a href="../staffmenu.php" class="px-4 py-2 bg-gray-600 rounded hover:bg-gray-500">Voltar</a>
    </div>

    <form method="get" class="flex gap-2 mb-4">
        <input type="text" name="busca" placeholder="Buscar por nome..." value="<?= htmlspecialchars($busca) ?>" class="flex-1 p-2 rounded bg-zinc-800 text-white">
        <button type="submit" class="px-4 py-2 bg-blue-600 rounded hover:bg-blue-500">Pesquisar</button>
    </form>

    <table class="w-full text-left border-collapse">
        <tr class="bg-zinc-700">
            <th class="p-2 border">ID</th>
            <th class="p-2 border">Nome</th>
            <th class="p-2 border">CRECI</th>
            <th class="p-2 border">Telefone</th>
            <th class="p-2 border">Ações</th>
        </tr>
        <?php while($linha = $resultado->fetch_assoc()): ?>
        <tr class="bg-zinc-800 hover:bg-zinc-700">
            <td class="p-2 border"><?= $linha['idCORRETOR'] ?></td>
            <td class="p-2 border"><?= htmlspecialchars($linha['nome']) ?></td>
            <td class="p-2 border"><?= htmlspecialchars($linha['creci']) ?></td>
            <td class="p-2 border"><?= htmlspecialchars($linha['telefone']) ?></td>
            <td class="p-2 border flex gap-2">
                <button onclick="abrirModal('editar', <?= $linha['idCORRETOR'] ?>, '<?= addslashes($linha['nome']) ?>', '<?= addslashes($linha['creci']) ?>', '<?= addslashes($linha['telefone']) ?>')" class="px-2 py-1 bg-yellow-500 rounded hover:bg-yellow-400">Editar</button>
                <button onclick="abrirModal('excluir', <?= $linha['idCORRETOR'] ?>)" class="px-2 py-1 bg-red-600 rounded hover:bg-red-500">Excluir</button>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- Modal -->
<div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-zinc-800 p-6 rounded-2xl w-96 relative">
        <h2 id="modal-title" class="text-xl font-bold mb-4">Título</h2>
        <div id="modal-body"></div>
        <div class="flex justify-end gap-2 mt-4">
            <button onclick="fecharModal()" class="px-4 py-2 bg-gray-600 rounded hover:bg-gray-500">Cancelar</button>
            <button id="modal-confirm" class="px-4 py-2 bg-green-600 rounded hover:bg-green-500">Confirmar</button>
        </div>
        <button onclick="fecharModal()" class="absolute top-2 right-2 text-gray-400 hover:text-white">&times;</button>
    </div>
</div>

<script>
function abrirModal(tipo, id='', nome='', creci='', telefone='') {
    $('#modal').removeClass('hidden');
    if(tipo==='cadastrar') {
        $('#modal-title').text('Cadastrar Corretor');
        $('#modal-body').html(`
            <input type="text" id="nome" placeholder="Nome" class="w-full p-2 mb-2 rounded bg-zinc-700">
            <input type="text" id="creci" placeholder="CRECI" class="w-full p-2 mb-2 rounded bg-zinc-700">
            <input type="text" id="telefone" placeholder="Telefone" class="w-full p-2 mb-2 rounded bg-zinc-700">
        `);
        $('#modal-confirm').off('click').click(function(){
            $.post('', {acao:'cadastrar', nome:$('#nome').val(), creci:$('#creci').val(), telefone:$('#telefone').val()}, function(res){
                res = JSON.parse(res);
                alert(res.status==='sucesso'?'Cadastro realizado!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    } else if(tipo==='editar') {
        $('#modal-title').text('Editar Corretor');
        $('#modal-body').html(`
            <input type="hidden" id="id" value="${id}">
            <input type="text" id="nome" value="${nome}" class="w-full p-2 mb-2 rounded bg-zinc-700">
            <input type="text" id="creci" value="${creci}" class="w-full p-2 mb-2 rounded bg-zinc-700">
            <input type="text" id="telefone" value="${telefone}" class="w-full p-2 mb-2 rounded bg-zinc-700">
        `);
        $('#modal-confirm').off('click').click(function(){
            $.post('', {acao:'editar', id:id, nome:$('#nome').val(), creci:$('#creci').val(), telefone:$('#telefone').val()}, function(res){
                res = JSON.parse(res);
                alert(res.status==='sucesso'?'Editado com sucesso!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    } else if(tipo==='excluir') {
        $('#modal-title').text('Confirmar Exclusão');
        $('#modal-body').html('<p>Deseja realmente excluir este corretor?</p>');
        $('#modal-confirm').off('click').click(function(){
            $.post('', {acao:'excluir', id:id}, function(res){
                res = JSON.parse(res);
                alert(res.status==='sucesso'?'Excluído com sucesso!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    }
}

function fecharModal() {
    $('#modal').addClass('hidden');
}
</script>

</body>
</html>
