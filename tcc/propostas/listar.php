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
<style>
/* ===== Estilo painel unificado ===== */
@keyframes fadeInPage { from {opacity:0;transform:translateY(20px);} to {opacity:1;transform:translateY(0);} }
.fade-in-page { animation: fadeInPage 0.6s ease-in-out; }

body::before {
    content:"";position:fixed;top:0;left:0;right:0;bottom:0;
    background:linear-gradient(135deg,#111111,#1a1a1a,#222233,#1a1a1a);
    background-size:400% 400%;
    animation:gradientMove 20s ease infinite;
    z-index:-2;
}
@keyframes gradientMove {0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%;}}

.particle {position:absolute;border-radius:50%;background:rgba(255,255,255,0.03);pointer-events:none;z-index:-1;animation:floatParticle linear infinite;}
@keyframes floatParticle {0%{transform:translateY(0) translateX(0) scale(0.5);opacity:0;}10%{opacity:0.2;}100%{transform:translateY(-800px) translateX(200px) scale(1);opacity:0;}}

.btn-glow {position:relative;transition:all 0.3s ease;background:#1f1f2f;color:#e0e0e0;font-bold;}
.btn-glow::before {content:'';position:absolute;top:-2px;left:-2px;right:-2px;bottom:-2px;background:linear-gradient(45deg,#2a2a3f,#3a3a5a,#2a2a3f,#3a3a5a);border-radius:inherit;filter:blur(6px);opacity:0;transition:opacity 0.3s ease;z-index:-1;}
.btn-glow:hover {background:#2c2c44;}
.btn-glow:hover::before {opacity:1;}

.card-dynamic {box-shadow:0 10px 25px rgba(0,0,0,0.6);transition:transform 0.3s ease, box-shadow 0.3s ease;}
.card-dynamic:hover {transform:translateY(-5px);box-shadow:0 20px 40px rgba(0,0,0,0.8);}

.title-glow {text-shadow:0 0 6px rgba(255,255,255,0.3);}
</style>
</head>
<body class="font-serif text-gray-100 min-h-screen relative overflow-hidden">

<?php for($i=0;$i<25;$i++): ?>
<div class="particle" style="width:<?=rand(5,15)?>px;height:<?=rand(5,15)?>px;top:<?=rand(0,100)?>%;left:<?=rand(0,100)?>%;animation-duration:<?=rand(20,40)?>s;animation-delay:<?=rand(0,20)?>s;"></div>
<?php endfor; ?>

<div class="fade-in-page w-full px-4 py-10 flex flex-col items-center">
  <div class="bg-[#1f1f2f]/90 backdrop-blur-md p-8 rounded-3xl w-full max-w-7xl shadow-2xl space-y-6 card-dynamic border border-[#2a2a3f]/50">
    
    <h2 class="text-3xl font-bold tracking-wide text-center text-gray-200 title-glow">Gerenciar Propostas</h2>

    <div class="flex flex-wrap gap-4 justify-center">
        <button onclick="abrirModal('cadastrar')" class="py-2 px-5 rounded-xl font-bold btn-glow shadow-md">Cadastrar Novo</button>
        <a href="../staffmenu.php" class="py-2 px-5 rounded-xl font-bold btn-glow shadow-md">Voltar</a>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse mt-4">
        <thead>
          <tr class="bg-[#2a2a3f]">
            <th class="p-3 border border-[#3a3a5a]">ID</th>
            <th class="p-3 border border-[#3a3a5a]">Cliente</th>
            <th class="p-3 border border-[#3a3a5a]">CPF</th>
            <th class="p-3 border border-[#3a3a5a]">Valor Ofertado</th>
            <th class="p-3 border border-[#3a3a5a]">Data</th>
            <th class="p-3 border border-[#3a3a5a]">Status</th>
            <th class="p-3 border border-[#3a3a5a]">Imóvel</th>
            <th class="p-3 border border-[#3a3a5a]">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php if($resultado->num_rows>0): while($p=$resultado->fetch_assoc()): ?>
          <tr class="bg-[#1f1f2f] hover:bg-[#2c2c44] transition">
            <td class="p-3 border border-[#2a2a3f]"><?= $p['id'] ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($p['cliente']) ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($p['cpf']) ?></td>
            <td class="p-3 border border-[#2a2a3f]">R$ <?= $p['valor_ofertado'] ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= $p['data_proposta'] ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= $p['status'] ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= $p['imovel'] ?></td>
            <td class="p-3 border border-[#2a2a3f] flex gap-2">
              <button onclick="abrirModal('editar',<?= $p['id'] ?>,<?= $p['cliente'] ?>,<?= $p['imovel'] ?>,<?= $p['valor_ofertado'] ?>,'<?= $p['data_proposta'] ?>','<?= $p['status'] ?>')" class="px-3 py-1 rounded-md font-bold btn-glow shadow-md">Editar</button>
              <button onclick="abrirModal('excluir',<?= $p['id'] ?>)" class="px-3 py-1 rounded-md font-bold btn-glow shadow-md">Excluir</button>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="8" class="text-center p-3 border">Nenhuma proposta encontrada.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- Modal -->
<div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50">
  <div class="bg-[#1f1f2f] p-6 rounded-2xl w-96 relative card-dynamic border border-[#2a2a3f]/50">
    <h2 id="modal-title" class="text-xl font-bold mb-4 title-glow">Título</h2>
    <div id="modal-body" class="space-y-2"></div>
    <div class="flex justify-end gap-2 mt-4">
        <button onclick="fecharModal()" class="py-2 px-4 rounded-xl font-bold btn-glow shadow-md">Cancelar</button>
        <button id="modal-confirm" class="py-2 px-4 rounded-xl font-bold btn-glow shadow-md">Confirmar</button>
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
            <select id="cliente" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">${clientes}</select>
            <select id="visita" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">${visitas}</select>
            <input type="number" id="valor" placeholder="Valor" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">
            <input type="date" id="data" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">
            <select id="status" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">
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
            <select id="cliente" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">${clientes}</select>
            <select id="visita" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">${visitas}</select>
            <input type="number" id="valor" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200" value="${valor}">
            <input type="date" id="data" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200" value="${data}">
            <select id="status" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">
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

function fecharModal(){ $('#modal').addClass('hidden'); }
</script>

</body>
</html>
