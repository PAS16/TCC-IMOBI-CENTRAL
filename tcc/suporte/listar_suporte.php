<?php
include '../conexao.php';

// -------------------- Atualizar status --------------------
if (isset($_POST['acao']) && $_POST['acao'] === 'atualizar_status') {
    $id = intval($_POST['id']);
    $status = $_POST['status'] ?? 'Pendente';

    $stmt = $conn->prepare("UPDATE mensagens_suporte SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    echo json_encode($stmt->execute() ? ['status' => 'sucesso'] : ['status' => 'erro', 'mensagem' => $stmt->error]);
    exit;
}

// -------------------- Excluir mensagem --------------------
if (isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
    $id = intval($_POST['id']);

    $stmt = $conn->prepare("DELETE FROM mensagens_suporte WHERE id=?");
    $stmt->bind_param("i", $id);
    echo json_encode($stmt->execute() ? ['status' => 'sucesso'] : ['status' => 'erro', 'mensagem' => $stmt->error]);
    exit;
}

// -------------------- Listar mensagens --------------------
$resultado = $conn->query("
SELECT id, nome, email, telefone, mensagem, status, data_criacao
FROM mensagens_suporte
ORDER BY data_criacao DESC
");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerenciar Suporte</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-zinc-900 text-gray-100 min-h-screen p-8 font-serif">

<div class="max-w-6xl mx-auto">
    <h2 class="text-3xl font-bold mb-6 text-center">📩 Mensagens de Suporte</h2>

    <div class="flex gap-2 mb-6 flex-wrap justify-center">
        <a href="../staffmenu.php" class="px-4 py-2 bg-gray-600 rounded hover:bg-gray-500">⬅ Voltar</a>
    </div>

    <table class="w-full border-collapse text-left shadow-lg rounded-lg overflow-hidden">
        <thead class="bg-zinc-700">
            <tr>
                <th class="p-3 border">ID</th>
                <th class="p-3 border">Nome</th>
                <th class="p-3 border">Email</th>
                <th class="p-3 border">Telefone</th>
                <th class="p-3 border">Mensagem</th>
                <th class="p-3 border">Status</th>
                <th class="p-3 border">Data</th>
                <th class="p-3 border">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if($resultado->num_rows > 0): while($s = $resultado->fetch_assoc()): ?>
            <tr class="bg-zinc-800 hover:bg-zinc-700">
                <td class="p-3 border"><?= $s['id'] ?></td>
                <td class="p-3 border"><?= htmlspecialchars($s['nome']) ?></td>
                <td class="p-3 border"><?= htmlspecialchars($s['email']) ?></td>
                <td class="p-3 border"><?= htmlspecialchars($s['telefone']) ?></td>
                <td class="p-3 border"><?= nl2br(htmlspecialchars($s['mensagem'])) ?></td>
                <td class="p-3 border">
                    <select onchange="atualizarStatus(<?= $s['id'] ?>, this.value)" class="bg-zinc-700 p-1 rounded">
                        <option value="Pendente" <?= $s['status']==='Pendente'?'selected':'' ?>>Pendente</option>
                        <option value="Atendido" <?= $s['status']==='Atendido'?'selected':'' ?>>Atendido</option>
                    </select>
                </td>
                <td class="p-3 border"><?= $s['data_criacao'] ?></td>
                <td class="p-3 border flex gap-2">
                    <button onclick="excluirMensagem(<?= $s['id'] ?>)" class="px-3 py-1 bg-red-600 rounded hover:bg-red-500">Excluir</button>
                </td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="8" class="text-center p-4 border">Nenhuma mensagem encontrada.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function atualizarStatus(id, status){
    $.post('', {acao:'atualizar_status', id:id, status:status}, function(res){
        res = JSON.parse(res);
        if(res.status !== 'sucesso'){
            alert(res.mensagem || 'Erro ao atualizar status.');
        }
    });
}

function excluirMensagem(id){
    if(confirm('Deseja realmente excluir esta mensagem?')){
        $.post('', {acao:'excluir', id:id}, function(res){
            res = JSON.parse(res);
            if(res.status==='sucesso'){
                location.reload();
            } else {
                alert(res.mensagem || 'Erro ao excluir mensagem.');
            }
        });
    }
}
</script>

</body>
</html>
