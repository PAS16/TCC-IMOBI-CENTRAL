<?php
include '../conexao.php';

// -------------------- Ações AJAX --------------------
// Cadastrar
if(isset($_POST['acao']) && $_POST['acao'] === 'cadastrar'){
    $tipo = $_POST['tipo'] ?? '';
    $status = $_POST['status'] ?? 'Disponivel';
    $valor = $_POST['valor'] ?? 0;
    $descricao = $_POST['descricao'] ?? '';
    $proprietario = $_POST['proprietario'] ?? 0;

    if($tipo && $descricao && $proprietario){
        $stmt = $conn->prepare("INSERT INTO IMOVEL (tipo,status,valor,descricao,PROPRIETARIO_idPROPRIETARIO) VALUES (?,?,?,?,?)");
        $stmt->bind_param("ssdsi", $tipo, $status, $valor, $descricao, $proprietario);
        $resultado = $stmt->execute();
        if($resultado){
            echo json_encode(['status'=>'sucesso']);
        } else {
            echo json_encode(['status'=>'erro','mensagem'=>$stmt->error]);
        }
    } else {
        echo json_encode(['status'=>'erro','mensagem'=>'Campos obrigatórios faltando']);
    }
    exit;
}

// Editar
if(isset($_POST['acao']) && $_POST['acao']==='editar'){
    $id = intval($_POST['id']);
    $tipo = $_POST['tipo'] ?? '';
    $status = $_POST['status'] ?? 'Disponivel';
    $valor = $_POST['valor'] ?? 0;
    $descricao = $_POST['descricao'] ?? '';
    $proprietario = $_POST['proprietario'] ?? 0;

    if($id && $tipo && $descricao && $proprietario){
        $stmt = $conn->prepare("UPDATE IMOVEL SET tipo=?, status=?, valor=?, descricao=?, PROPRIETARIO_idPROPRIETARIO=? WHERE idIMOVEL=?");
        $stmt->bind_param("ssdiii", $tipo, $status, $valor, $descricao, $proprietario, $id);
        $resultado = $stmt->execute();
        if($resultado){
            echo json_encode(['status'=>'sucesso']);
        } else {
            echo json_encode(['status'=>'erro','mensagem'=>$stmt->error]);
        }
    } else {
        echo json_encode(['status'=>'erro','mensagem'=>'Campos obrigatórios faltando']);
    }
    exit;
}

// Excluir
if(isset($_POST['acao']) && $_POST['acao']==='excluir'){
    $id = intval($_POST['id']);
    if($id){
        $stmt = $conn->prepare("DELETE FROM IMOVEL WHERE idIMOVEL=?");
        $stmt->bind_param("i",$id);
        $resultado = $stmt->execute();
        if($resultado){
            echo json_encode(['status'=>'sucesso']);
        } else {
            echo json_encode(['status'=>'erro','mensagem'=>$stmt->error]);
        }
    } else {
        echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']);
    }
    exit;
}

// -------------------- Buscar / Listar --------------------
$ordem = $_GET['ordem'] ?? 'id_asc';
$filtroStatus = $_GET['status'] ?? '';
$pesquisaId = $_GET['id'] ?? '';

switch($ordem){
    case 'id_desc': $ordenar='i.idIMOVEL DESC'; break;
    case 'valor_asc': $ordenar='i.valor ASC'; break;
    case 'valor_desc': $ordenar='i.valor DESC'; break;
    default: $ordenar='i.idIMOVEL ASC';
}

$filtroSQL='';
if($filtroStatus) $filtroSQL.=" AND i.status='".mysqli_real_escape_string($conn,$filtroStatus)."'";
if($pesquisaId!=='') $filtroSQL.=" AND i.idIMOVEL='".intval($pesquisaId)."'";

$sql="SELECT i.*, c.nome as proprietario FROM IMOVEL i LEFT JOIN CLIENTE c ON i.PROPRIETARIO_idPROPRIETARIO=c.idCLIENTE WHERE 1=1 $filtroSQL ORDER BY $ordenar";
$resultado=$conn->query($sql);

// Buscar proprietários para select
$propResult = $conn->query("SELECT idCLIENTE,nome FROM CLIENTE");
$proprietarios=[];
while($p=$propResult->fetch_assoc()) $proprietarios[$p['idCLIENTE']]=$p['nome'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerenciar Imóveis</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-zinc-900 text-gray-100 min-h-screen p-8 font-serif">

<div class="max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-6 text-center">Gerenciar Imóveis</h2>

    <div class="flex gap-2 mb-4 flex-wrap">
        <button onclick="abrirModal('cadastrar')" class="px-4 py-2 bg-green-600 rounded hover:bg-green-500">Cadastrar Novo</button>
        <a href="../staffmenu.php" class="px-4 py-2 bg-gray-600 rounded hover:bg-gray-500">Voltar</a>
        <form method="get" class="flex gap-2 items-center">
            <input type="text" name="id" placeholder="Buscar por ID" value="<?= htmlspecialchars($pesquisaId) ?>" class="p-2 rounded bg-zinc-800 text-white">
            <button type="submit" class="px-4 py-2 bg-blue-600 rounded hover:bg-blue-500">Buscar</button>
        </form>
    </div>

    <div class="mb-4">
        <strong>Ordenar por:</strong>
        <a href="?ordem=id_asc">ID ↑</a> | <a href="?ordem=id_desc">ID ↓</a> | 
        <a href="?ordem=valor_asc">Valor ↑</a> | <a href="?ordem=valor_desc">Valor ↓</a>
    </div>

    <div class="mb-4">
        <strong>Filtrar por status:</strong>
        <a href="?status=Disponivel">Disponível</a> | 
        <a href="?status=Vendido">Vendido</a> | 
        <a href="?status=Alugado">Alugado</a> | 
        <a href="listar.php">Limpar Filtro</a>
    </div>

    <table class="w-full border-collapse text-left">
        <tr class="bg-zinc-700">
            <th class="p-2 border">ID</th>
            <th class="p-2 border">Tipo</th>
            <th class="p-2 border">Status</th>
            <th class="p-2 border">Valor</th>
            <th class="p-2 border">Descrição</th>
            <th class="p-2 border">Proprietário</th>
            <th class="p-2 border">Ações</th>
        </tr>
        <?php if($resultado->num_rows>0): while($linha=$resultado->fetch_assoc()): ?>
        <tr class="bg-zinc-800 hover:bg-zinc-700">
            <td class="p-2 border"><?= $linha['idIMOVEL'] ?></td>
            <td class="p-2 border"><?= htmlspecialchars($linha['tipo']) ?></td>
            <td class="p-2 border"><?= $linha['status'] ?></td>
            <td class="p-2 border">R$ <?= $linha['valor'] ?></td>
            <td class="p-2 border"><?= htmlspecialchars($linha['descricao']) ?></td>
            <td class="p-2 border"><?= htmlspecialchars($linha['proprietario']) ?></td>
            <td class="p-2 border flex gap-2">
                <button onclick="abrirModal('editar', <?= $linha['idIMOVEL'] ?>,'<?= addslashes($linha['tipo']) ?>','<?= $linha['status'] ?>',<?= $linha['valor'] ?>,'<?= addslashes($linha['descricao']) ?>',<?= $linha['PROPRIETARIO_idPROPRIETARIO'] ?>)" class="px-2 py-1 bg-yellow-500 rounded hover:bg-yellow-400">Editar</button>
                <button onclick="abrirModal('excluir', <?= $linha['idIMOVEL'] ?>)" class="px-2 py-1 bg-red-600 rounded hover:bg-red-500">Excluir</button>
            </td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="7" class="text-center p-2 border">Nenhum imóvel encontrado.</td></tr>
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
function abrirModal(tipo,id='',tipoImovel='',status='Disponivel',valor=0,descricao='',proprietario=0){
    $('#modal').removeClass('hidden');

    let options = `<?php
        foreach($proprietarios as $idp=>$nome){
            echo "<option value=\"$idp\">$nome</option>";
        }
    ?>`;

    if(tipo==='cadastrar'){
        $('#modal-title').text('Cadastrar Imóvel');
        $('#modal-body').html(`
            <input type="text" id="tipo" placeholder="Tipo" class="w-full p-2 mb-2 rounded bg-zinc-700">
            <select id="status" class="w-full p-2 mb-2 rounded bg-zinc-700">
                <option value="Disponivel">Disponível</option>
                <option value="Vendido">Vendido</option>
                <option value="Alugado">Alugado</option>
            </select>
            <input type="number" id="valor" placeholder="Valor" class="w-full p-2 mb-2 rounded bg-zinc-700">
            <textarea id="descricao" placeholder="Descrição" class="w-full p-2 mb-2 rounded bg-zinc-700"></textarea>
            <select id="proprietario" class="w-full p-2 mb-2 rounded bg-zinc-700">${options}</select>
        `);
        $('#modal-confirm').off('click').click(function(){
            $.post('', {
                acao:'cadastrar', 
                tipo:$('#tipo').val(), 
                status:$('#status').val(), 
                valor:$('#valor').val(), 
                descricao:$('#descricao').val(), 
                proprietario:$('#proprietario').val()
            }, function(res){
                res=JSON.parse(res);
                alert(res.status==='sucesso'?'Cadastro realizado!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    } else if(tipo==='editar'){
        $('#modal-title').text('Editar Imóvel');
        $('#modal-body').html(`
            <input type="hidden" id="id" value="${id}">
            <input type="text" id="tipo" value="${tipoImovel}" class="w-full p-2 mb-2 rounded bg-zinc-700">
            <select id="status" class="w-full p-2 mb-2 rounded bg-zinc-700">
                <option value="Disponivel">Disponível</option>
                <option value="Vendido">Vendido</option>
                <option value="Alugado">Alugado</option>
            </select>
            <input type="number" id="valor" value="${valor}" class="w-full p-2 mb-2 rounded bg-zinc-700">
            <textarea id="descricao" class="w-full p-2 mb-2 rounded bg-zinc-700">${descricao}</textarea>
            <select id="proprietario" class="w-full p-2 mb-2 rounded bg-zinc-700">${options}</select>
        `);
        // Seleciona status e proprietário corretos
        $('#status').val(status);
        $('#proprietario').val(proprietario);

        $('#modal-confirm').off('click').click(function(){
            $.post('', {
                acao:'editar', 
                id:id, 
                tipo:$('#tipo').val(), 
                status:$('#status').val(), 
                valor:$('#valor').val(), 
                descricao:$('#descricao').val(), 
                proprietario:$('#proprietario').val()
            }, function(res){
                res=JSON.parse(res);
                alert(res.status==='sucesso'?'Editado com sucesso!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    } else if(tipo==='excluir'){
        $('#modal-title').text('Confirmar Exclusão');
        $('#modal-body').html('<p>Deseja realmente excluir este imóvel?</p>');
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
