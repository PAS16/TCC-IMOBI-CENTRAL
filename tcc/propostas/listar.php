<?php
include '../conexao.php';

// -------------------- Ações AJAX --------------------
if(isset($_POST['acao']) && $_POST['acao']==='cadastrar'){
    $visita = $_POST['visita'] ?? 0;
    $cliente = $_POST['cliente'] ?? 0;
    $valor = $_POST['valor'] ?? 0;
    $data = $_POST['data'] ?? '';
    $status = $_POST['status'] ?? 'Pendente';

    if($visita && $cliente && $valor && $data){
        $stmt = $conn->prepare("INSERT INTO PROPOSTA (VISITA_idVISITA, VISITA_CLIENTE_idCLIENTE, valor_ofertado, data_proposta, status) VALUES (?,?,?,?,?)");
        $stmt->bind_param("iidsi",$visita,$cliente,$valor,$data,$status);
        echo json_encode($stmt->execute()?['status'=>'sucesso']:['status'=>'erro','mensagem'=>$stmt->error]);
    } else {
        echo json_encode(['status'=>'erro','mensagem'=>'Campos obrigatórios faltando']);
    }
    exit;
}

if(isset($_POST['acao']) && $_POST['acao']==='editar'){
    $id = intval($_POST['id']);
    $visita = $_POST['visita'] ?? 0;
    $cliente = $_POST['cliente'] ?? 0;
    $valor = $_POST['valor'] ?? 0;
    $data = $_POST['data'] ?? '';
    $status = $_POST['status'] ?? 'Pendente';

    if($id && $visita && $cliente && $valor && $data){
        $stmt = $conn->prepare("UPDATE PROPOSTA SET VISITA_idVISITA=?, VISITA_CLIENTE_idCLIENTE=?, valor_ofertado=?, data_proposta=?, status=? WHERE idPROPOSTA=?");
        $stmt->bind_param("iidsis",$visita,$cliente,$valor,$data,$status,$id);
        echo json_encode($stmt->execute()?['status'=>'sucesso']:['status'=>'erro','mensagem'=>$stmt->error]);
    } else {
        echo json_encode(['status'=>'erro','mensagem'=>'Campos obrigatórios faltando']);
    }
    exit;
}

if(isset($_POST['acao']) && $_POST['acao']==='excluir'){
    $id=intval($_POST['id']);
    if($id){
        $stmt=$conn->prepare("DELETE FROM PROPOSTA WHERE idPROPOSTA=?");
        $stmt->bind_param("i",$id);
        echo json_encode($stmt->execute()?['status'=>'sucesso']:['status'=>'erro','mensagem'=>$stmt->error]);
    } else echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']);
    exit;
}

// -------------------- Listar --------------------
$resultado = $conn->query("
SELECT p.idPROPOSTA AS id, c.nome AS cliente, c.cpf, p.valor_ofertado, p.data_proposta, p.status, i.idIMOVEL AS imovel
FROM PROPOSTA p
JOIN VISITA v ON p.VISITA_idVISITA = v.idVISITA AND p.VISITA_CLIENTE_idCLIENTE = v.CLIENTE_idCLIENTE
JOIN CLIENTE c ON v.CLIENTE_idCLIENTE = c.idCLIENTE
JOIN IMOVEL i ON v.IMOVEL_idIMOVEL = i.idIMOVEL
ORDER BY p.data_proposta DESC
");

// Buscar clientes e visitas para selects
$clientes = [];
$res = $conn->query("SELECT idCLIENTE,nome FROM CLIENTE");
while($r=$res->fetch_assoc()) $clientes[$r['idCLIENTE']]=$r['nome'];

$visitas = [];
$res = $conn->query("SELECT idVISITA,CONCAT('Visita ',idVISITA,' - Imóvel ',IMOVEL_idIMOVEL) AS descricao FROM VISITA");
while($r=$res->fetch_assoc()) $visitas[$r['idVISITA']]=$r['descricao'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerenciar Propostas</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-zinc-900 text-gray-100 min-h-screen p-8 font-serif">

<div class="max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-6 text-center">Gerenciar Propostas</h2>

    <div class="flex gap-2 mb-4 flex-wrap">
        <button onclick="abrirModal('cadastrar')" class="px-4 py-2 bg-green-600 rounded hover:bg-green-500">Cadastrar Novo</button>
        <a href="../staffmenu.php" class="px-4 py-2 bg-gray-600 rounded hover:bg-gray-500">Voltar</a>
    </div>

    <table class="w-full border-collapse text-left">
        <tr class="bg-zinc-700">
            <th class="p-2 border">ID</th>
            <th class="p-2 border">Cliente</th>
            <th class="p-2 border">CPF</th>
            <th class="p-2 border">Valor Ofertado</th>
            <th class="p-2 border">Data</th>
            <th class="p-2 border">Status</th>
            <th class="p-2 border">Imóvel</th>
            <th class="p-2 border">Ações</th>
        </tr>
        <?php if($resultado->num_rows>0): while($p=$resultado->fetch_assoc()): ?>
        <tr class="bg-zinc-800 hover:bg-zinc-700">
            <td class="p-2 border"><?= $p['id'] ?></td>
            <td class="p-2 border"><?= htmlspecialchars($p['cliente']) ?></td>
            <td class="p-2 border"><?= htmlspecialchars($p['cpf']) ?></td>
            <td class="p-2 border">R$ <?= $p['valor_ofertado'] ?></td>
            <td class="p-2 border"><?= $p['data_proposta'] ?></td>
            <td class="p-2 border"><?= $p['status'] ?></td>
            <td class="p-2 border"><?= $p['imovel'] ?></td>
            <td class="p-2 border flex gap-2">
                <button onclick="abrirModal('editar',<?= $p['id'] ?>,<?= $p['cliente'] ?>,<?= $p['imovel'] ?>,<?= $p['valor_ofertado'] ?>,'<?= $p['data_proposta'] ?>','<?= $p['status'] ?>')" class="px-2 py-1 bg-yellow-500 rounded hover:bg-yellow-400">Editar</button>
                <button onclick="abrirModal('excluir',<?= $p['id'] ?>)" class="px-2 py-1 bg-red-600 rounded hover:bg-red-500">Excluir</button>
            </td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="8" class="text-center p-2 border">Nenhuma proposta encontrada.</td></tr>
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
function abrirModal(tipo,id='',cliente='',visita='',valor=0,data='',status='Pendente'){
    $('#modal').removeClass('hidden');
    let clientes = `<?php foreach($clientes as $k=>$v){ echo "<option value=\"$k\">$v</option>"; } ?>`;
    let visitas = `<?php foreach($visitas as $k=>$v){ echo "<option value=\"$k\">$v</option>"; } ?>`;

    if(tipo==='cadastrar'){
        $('#modal-title').text('Cadastrar Proposta');
        $('#modal-body').html(`
            <select id="cliente" class="w-full p-2 mb-2 rounded bg-zinc-700">${clientes}</select>
            <select id="visita" class="w-full p-2 mb-2 rounded bg-zinc-700">${visitas}</select>
            <input type="number" id="valor" placeholder="Valor" class="w-full p-2 mb-2 rounded bg-zinc-700">
            <input type="date" id="data" class="w-full p-2 mb-2 rounded bg-zinc-700">
            <select id="status" class="w-full p-2 mb-2 rounded bg-zinc-700">
                <option value="Pendente">Pendente</option>
                <option value="Aceita">Aceita</option>
                <option value="Recusada">Recusada</option>
            </select>
        `);
        $('#modal-confirm').off('click').click(function(){
            $.post('', {acao:'cadastrar', cliente:$('#cliente').val(), visita:$('#visita').val(), valor:$('#valor').val(), data:$('#data').val(), status:$('#status').val()}, function(res){
                res=JSON.parse(res);
                alert(res.status==='sucesso'?'Cadastro realizado!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    } else if(tipo==='editar'){
        $('#modal-title').text('Editar Proposta');
        $('#modal-body').html(`
            <input type="hidden" id="id" value="${id}">
            <select id="cliente" class="w-full p-2 mb-2 rounded bg-zinc-700">${clientes}</select>
            <select id="visita" class="w-full p-2 mb-2 rounded bg-zinc-700">${visitas}</select>
            <input type="number" id="valor" class="w-full p-2 mb-2 rounded bg-zinc-700" value="${valor}">
            <input type="date" id="data" class="w-full p-2 mb-2 rounded bg-zinc-700" value="${data}">
            <select id="status" class="w-full p-2 mb-2 rounded bg-zinc-700">
                <option value="Pendente" ${status==='Pendente'?'selected':''}>Pendente</option>
                <option value="Aceita" ${status==='Aceita'?'selected':''}>Aceita</option>
                <option value="Recusada" ${status==='Recusada'?'selected':''}>Recusada</option>
            </select>
        `);
        $('#cliente').val(cliente); $('#visita').val(visita);
        $('#modal-confirm').off('click').click(function(){
            $.post('', {acao:'editar', id:id, cliente:$('#cliente').val(), visita:$('#visita').val(), valor:$('#valor').val(), data:$('#data').val(), status:$('#status').val()}, function(res){
                res=JSON.parse(res);
                alert(res.status==='sucesso'?'Editado com sucesso!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    } else if(tipo==='excluir'){
        $('#modal-title').text('Confirmar Exclusão');
        $('#modal-body').html('<p>Deseja realmente excluir esta proposta?</p>');
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
