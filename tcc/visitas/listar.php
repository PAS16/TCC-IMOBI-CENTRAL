<?php
include '../conexao.php';

// -------------------- Ações AJAX --------------------
if(isset($_POST['acao']) && $_POST['acao']==='cadastrar'){
    $cliente = $_POST['cliente'] ?? 0;
    $imovel = $_POST['imovel'] ?? 0;
    $data = $_POST['data'] ?? '';
    $obs = $_POST['observacoes'] ?? '';

    if($cliente && $imovel && $data){
        $stmt = $conn->prepare("INSERT INTO VISITA (CORRETOR_idCORRETOR, IMOVEL_idIMOVEL, CLIENTE_idCLIENTE, data_visita, observacoes) VALUES (1,?,?,?,?)");
        $stmt->bind_param("iiss",$imovel,$cliente,$data,$obs);
        echo json_encode($stmt->execute()?['status'=>'sucesso']:['status'=>'erro','mensagem'=>$stmt->error]);
    } else {
        echo json_encode(['status'=>'erro','mensagem'=>'Campos obrigatórios faltando']);
    }
    exit;
}

if(isset($_POST['acao']) && $_POST['acao']==='editar'){
    $id = intval($_POST['id']);
    $cliente = $_POST['cliente'] ?? 0;
    $imovel = $_POST['imovel'] ?? 0;
    $data = $_POST['data'] ?? '';
    $obs = $_POST['observacoes'] ?? '';

    if($id && $cliente && $imovel && $data){
        $stmt = $conn->prepare("UPDATE VISITA SET IMOVEL_idIMOVEL=?, CLIENTE_idCLIENTE=?, data_visita=?, observacoes=? WHERE idVISITA=?");
        $stmt->bind_param("iissi",$imovel,$cliente,$data,$obs,$id);
        echo json_encode($stmt->execute()?['status'=>'sucesso']:['status'=>'erro','mensagem'=>$stmt->error]);
    } else {
        echo json_encode(['status'=>'erro','mensagem'=>'Campos obrigatórios faltando']);
    }
    exit;
}

if(isset($_POST['acao']) && $_POST['acao']==='excluir'){
    $id=intval($_POST['id']);
    if($id){
        $stmt=$conn->prepare("DELETE FROM VISITA WHERE idVISITA=?");
        $stmt->bind_param("i",$id);
        echo json_encode($stmt->execute()?['status'=>'sucesso']:['status'=>'erro','mensagem'=>$stmt->error]);
    } else echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']);
    exit;
}

// -------------------- Listar --------------------
$resultado = $conn->query("
SELECT v.idVISITA AS id, c.nome AS cliente, i.idIMOVEL AS imovel, v.data_visita, v.observacoes
FROM VISITA v
JOIN CLIENTE c ON v.CLIENTE_idCLIENTE = c.idCLIENTE
JOIN IMOVEL i ON v.IMOVEL_idIMOVEL = i.idIMOVEL
ORDER BY v.data_visita DESC
");

// Buscar clientes e imóveis para selects
$clientes = [];
$res = $conn->query("SELECT idCLIENTE,nome FROM CLIENTE");
while($r=$res->fetch_assoc()) $clientes[$r['idCLIENTE']]=$r['nome'];

$imoveis = [];
$res = $conn->query("SELECT idIMOVEL,descricao FROM IMOVEL");
while($r=$res->fetch_assoc()) $imoveis[$r['idIMOVEL']]=$r['descricao'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerenciar Visitas</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-zinc-900 text-gray-100 min-h-screen p-8 font-serif">

<div class="max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-6 text-center">Gerenciar Visitas</h2>

    <div class="flex gap-2 mb-4 flex-wrap">
        <button onclick="abrirModal('cadastrar')" class="px-4 py-2 bg-green-600 rounded hover:bg-green-500">Cadastrar Novo</button>
        <a href="../staffmenu.php" class="px-4 py-2 bg-gray-600 rounded hover:bg-gray-500">Voltar</a>
    </div>

    <table class="w-full border-collapse text-left">
        <tr class="bg-zinc-700">
            <th class="p-2 border">ID</th>
            <th class="p-2 border">Cliente</th>
            <th class="p-2 border">Imóvel</th>
            <th class="p-2 border">Data</th>
            <th class="p-2 border">Observações</th>
            <th class="p-2 border">Ações</th>
        </tr>
        <?php if($resultado->num_rows>0): while($v=$resultado->fetch_assoc()): ?>
        <tr class="bg-zinc-800 hover:bg-zinc-700">
            <td class="p-2 border"><?= $v['id'] ?></td>
            <td class="p-2 border"><?= htmlspecialchars($v['cliente']) ?></td>
            <td class="p-2 border"><?= htmlspecialchars($v['imovel']) ?></td>
            <td class="p-2 border"><?= $v['data_visita'] ?></td>
            <td class="p-2 border"><?= htmlspecialchars($v['observacoes']) ?></td>
            <td class="p-2 border flex gap-2">
                <button onclick="abrirModal('editar',<?= $v['id'] ?>,<?= $v['cliente'] ?>,<?= $v['imovel'] ?>,'<?= $v['data_visita'] ?>','<?= addslashes($v['observacoes']) ?>')" class="px-2 py-1 bg-yellow-500 rounded hover:bg-yellow-400">Editar</button>
                <button onclick="abrirModal('excluir',<?= $v['id'] ?>)" class="px-2 py-1 bg-red-600 rounded hover:bg-red-500">Excluir</button>
            </td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="6" class="text-center p-2 border">Nenhuma visita encontrada.</td></tr>
        <?php endif; ?>
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
function abrirModal(tipo,id='',cliente='',imovel='',data='',obs=''){
    $('#modal').removeClass('hidden');
    let clientes = `<?php foreach($clientes as $k=>$v){ echo "<option value=\"$k\">$v</option>"; } ?>`;
    let imoveis = `<?php foreach($imoveis as $k=>$v){ echo "<option value=\"$k\">$v</option>"; } ?>`;

    if(tipo==='cadastrar'){
        $('#modal-title').text('Cadastrar Visita');
        $('#modal-body').html(`
            <select id="cliente" class="w-full p-2 mb-2 rounded bg-zinc-700">${clientes}</select>
            <select id="imovel" class="w-full p-2 mb-2 rounded bg-zinc-700">${imoveis}</select>
            <input type="date" id="data" class="w-full p-2 mb-2 rounded bg-zinc-700" value="${data}">
            <textarea id="observacoes" placeholder="Observações" class="w-full p-2 mb-2 rounded bg-zinc-700"></textarea>
        `);
        $('#modal-confirm').off('click').click(function(){
            $.post('', {acao:'cadastrar', cliente:$('#cliente').val(), imovel:$('#imovel').val(), data:$('#data').val(), observacoes:$('#observacoes').val()}, function(res){
                res=JSON.parse(res);
                alert(res.status==='sucesso'?'Cadastro realizado!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    } else if(tipo==='editar'){
        $('#modal-title').text('Editar Visita');
        $('#modal-body').html(`
            <input type="hidden" id="id" value="${id}">
            <select id="cliente" class="w-full p-2 mb-2 rounded bg-zinc-700">${clientes}</select>
            <select id="imovel" class="w-full p-2 mb-2 rounded bg-zinc-700">${imoveis}</select>
            <input type="date" id="data" class="w-full p-2 mb-2 rounded bg-zinc-700" value="${data}">
            <textarea id="observacoes" class="w-full p-2 mb-2 rounded bg-zinc-700">${obs}</textarea>
        `);
        $('#cliente').val(cliente); $('#imovel').val(imovel);
        $('#modal-confirm').off('click').click(function(){
            $.post('', {acao:'editar', id:id, cliente:$('#cliente').val(), imovel:$('#imovel').val(), data:$('#data').val(), observacoes:$('#observacoes').val()}, function(res){
                res=JSON.parse(res);
                alert(res.status==='sucesso'?'Editado com sucesso!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    } else if(tipo==='excluir'){
        $('#modal-title').text('Confirmar Exclusão');
        $('#modal-body').html('<p>Deseja realmente excluir esta visita?</p>');
        $('#modal-confirm').off('click').click(function(){
            $.post('', {acao:'excluir', id:id}, function(res){
                res=JSON.parse(res);
                alert(res.status==='sucesso'?'Excluído com sucesso!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    }
}

function fecharModal(){
    $('#modal').addClass('hidden');
}
</script>

</body>
</html>
