<?php
session_start();
if (!isset($_SESSION['idUSUARIO']) || !isset($_SESSION['tipo'])) {
    header("Location: glogin.php");
    exit();
}

include __DIR__ . '/../conexao.php';
if (!$conn) { die("Erro: Conexão não estabelecida"); }

// --- Ações AJAX ---
if(isset($_POST['acao'])){
    $acao = $_POST['acao'];

    if($acao==='listar'){
        $res = $conn->query("
        SELECT p.idPROPOSTA AS id, c.nome AS cliente, c.cpf, p.valor_ofertado, p.data_proposta, p.status, i.idIMOVEL AS imovel
        FROM PROPOSTA p
        JOIN VISITA v ON p.VISITA_idVISITA = v.idVISITA AND p.VISITA_CLIENTE_idCLIENTE = v.CLIENTE_idCLIENTE
        JOIN CLIENTE c ON v.CLIENTE_idCLIENTE = c.idCLIENTE
        JOIN IMOVEL i ON v.IMOVEL_idIMOVEL = i.idIMOVEL
        ORDER BY p.data_proposta DESC
        ");
        $rows = [];
        while($p=$res->fetch_assoc()) $rows[] = $p;
        echo json_encode($rows);
        exit;
    }

    if($acao==='cadastrar'){
        $visita = $_POST['visita'] ?? '';
        $cliente = $_POST['cliente'] ?? '';
        $valor = $_POST['valor'] ?? 0;
        $data = $_POST['data'] ?? '';
        $status = $_POST['status'] ?? 'Pendente';
        if($visita && $cliente && $valor && $data){
            $stmt = $conn->prepare("INSERT INTO PROPOSTA (VISITA_idVISITA, VISITA_CLIENTE_idCLIENTE, valor_ofertado, data_proposta, status) VALUES (?,?,?,?,?)");
            $stmt->bind_param("iidss",$visita,$cliente,$valor,$data,$status);
            echo json_encode($stmt->execute()?['status'=>'sucesso']:['status'=>'erro','mensagem'=>$stmt->error]);
        } else echo json_encode(['status'=>'erro','mensagem'=>'Campos obrigatórios faltando']);
        exit;
    }

    if($acao==='editar'){
        $id = intval($_POST['id']);
        $visita = $_POST['visita'] ?? '';
        $cliente = $_POST['cliente'] ?? '';
        $valor = $_POST['valor'] ?? 0;
        $data = $_POST['data'] ?? '';
        $status = $_POST['status'] ?? 'Pendente';
        if($id && $visita && $cliente && $valor && $data){
            $stmt = $conn->prepare("UPDATE PROPOSTA SET VISITA_idVISITA=?, VISITA_CLIENTE_idCLIENTE=?, valor_ofertado=?, data_proposta=?, status=? WHERE idPROPOSTA=?");
            $stmt->bind_param("iidssi",$visita,$cliente,$valor,$data,$status,$id);
            echo json_encode($stmt->execute()?['status'=>'sucesso']:['status'=>'erro','mensagem'=>$stmt->error]);
        } else echo json_encode(['status'=>'erro','mensagem'=>'Campos obrigatórios faltando']);
        exit;
    }

    if($acao==='excluir'){
        $id = intval($_POST['id']);
        if($id){
            $stmt = $conn->prepare("DELETE FROM PROPOSTA WHERE idPROPOSTA=?");
            $stmt->bind_param("i",$id);
            echo json_encode($stmt->execute()?['status'=>'sucesso']:['status'=>'erro','mensagem'=>$stmt->error]);
        } else echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']);
        exit;
    }
}

// Buscar clientes
$clientes = [];
$res = $conn->query("SELECT idCLIENTE,nome FROM CLIENTE");
while($r=$res->fetch_assoc()) $clientes[$r['idCLIENTE']]=$r['nome'];

// Buscar visitas
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
body::before{content:"";position:fixed;top:0;left:0;right:0;bottom:0;background:linear-gradient(135deg,#111,#1a1a1a,#222233,#1a1a1a);background-size:400% 400%;animation:gradientMove 20s ease infinite;z-index:-2;}
@keyframes gradientMove{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}}
.particle{position:absolute;border-radius:50%;background:rgba(255,255,255,0.03);pointer-events:none;z-index:-1;animation:floatParticle linear infinite;}
@keyframes floatParticle{0%{transform:translateY(0) translateX(0) scale(0.5);opacity:0;}10%{opacity:0.2;}100%{transform:translateY(-800px) translateX(200px) scale(1);opacity:0;}}
.btn-glow{position:relative;transition:all 0.3s ease;background:#1f1f2f;color:#e0e0e0;}
.btn-glow::before{content:'';position:absolute;top:-2px;left:-2px;right:-2px;bottom:-2px;background:linear-gradient(45deg,#2a2a3f,#3a3a5a,#2a2a3f,#3a3a5a);border-radius:inherit;filter:blur(6px);opacity:0;transition:opacity 0.3s ease;z-index:-1;}
.btn-glow:hover{background:#2c2c44;}
.btn-glow:hover::before{opacity:1;}
.card-dynamic{box-shadow:0 10px 25px rgba(0,0,0,0.6);transition:transform 0.3s ease, box-shadow 0.3s ease;}
.card-dynamic:hover{transform:translateY(-5px);box-shadow:0 20px 40px rgba(0,0,0,0.8);}
.title-glow{text-shadow:0 0 6px rgba(255,255,255,0.3);}
</style>
</head>
<body class="font-serif text-gray-100 relative min-h-screen overflow-hidden flex flex-col items-center">

<?php for($i=0;$i<25;$i++): ?>
<div class="particle" style="width:<?=rand(5,15)?>px;height:<?=rand(5,15)?>px;top:<?=rand(0,100)?>%;left:<?=rand(0,100)?>%;animation-duration:<?=rand(20,40)?>s;animation-delay:<?=rand(0,20)?>s;"></div>
<?php endfor; ?>

<div class="w-full px-4 py-10 flex flex-col items-center">
  <div class="bg-[#1f1f2f]/90 backdrop-blur-md p-8 rounded-3xl w-full max-w-7xl shadow-2xl space-y-6 card-dynamic border border-[#2a2a3f]/50">
    <h2 class="text-3xl font-bold tracking-wide text-center text-gray-200 title-glow">Gerenciar Propostas</h2>
    <div class="flex flex-wrap gap-4 justify-center mb-4">
        <button onclick="abrirModal('cadastrar')" class="py-2 px-5 rounded-xl font-bold btn-glow shadow-md">Cadastrar Novo</button>
        <a href="../staffmenu.php" class="py-2 px-5 rounded-xl font-bold btn-glow shadow-md">Voltar</a>
    </div>
    <div class="overflow-x-auto mt-4">
      <table class="w-full text-left border-collapse">
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
        <tbody id="tabela-propostas">
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL -->
<div id="modal" class="fixed inset-0 bg-black/70 flex items-center justify-center hidden z-50">
  <div class="bg-[#1f1f2f] p-6 rounded-2xl w-full max-w-md">
    <h3 id="modal-title" class="text-xl font-bold mb-4"></h3>
    <div id="modal-body" class="space-y-3"></div>
    <div class="flex justify-end gap-3 mt-4">
        <button onclick="fecharModal()" class="px-4 py-2 rounded btn-glow">Cancelar</button>
        <button id="modal-action" class="px-4 py-2 rounded btn-glow">Salvar</button>
    </div>
  </div>
</div>

<script>
let clientes = <?php echo json_encode($clientes); ?>;
let visitas = <?php echo json_encode($visitas); ?>;

function carregarTabela(){
    $.post('', {acao:'listar'}, function(res){
        let html='';
        if(res.length>0){
            res.forEach(p=>{
                html+=`<tr class="bg-[#1f1f2f] hover:bg-[#2c2c44] transition">
                    <td class="p-3 border border-[#2a2a3f]">${p.id}</td>
                    <td class="p-3 border border-[#2a2a3f]">${p.cliente}</td>
                    <td class="p-3 border border-[#2a2a3f]">${p.cpf}</td>
                    <td class="p-3 border border-[#2a2a3f]">R$ ${p.valor_ofertado}</td>
                    <td class="p-3 border border-[#2a2a3f]">${p.data_proposta}</td>
                    <td class="p-3 border border-[#2a2a3f]">${p.status}</td>
                    <td class="p-3 border border-[#2a2a3f]">${p.imovel}</td>
                    <td class="p-3 border border-[#2a2a3f] flex gap-2">
                        <button onclick="abrirModal('editar',${p.id},${p.VISITA_CLIENTE_idCLIENTE},${p.imovel},${p.valor_ofertado},'${p.data_proposta}','${p.status}')" class="px-3 py-1 rounded-md font-bold btn-glow shadow-md">Editar</button>
                        <button onclick="abrirModal('excluir',${p.id})" class="px-3 py-1 rounded-md font-bold btn-glow shadow-md">Excluir</button>
                    </td>
                </tr>`;
            });
        } else html='<tr><td colspan="8" class="text-center p-3 border">Nenhuma proposta encontrada.</td></tr>';
        $("#tabela-propostas").html(html);
    },'json');
}

$(document).ready(function(){
    carregarTabela();
});

function abrirModal(acao,id='',cliente='',imovel='',valor='',data='',status=''){
    $("#modal").removeClass('hidden');
    $("#modal-body").html('');
    $("#modal-action").off('click').text('Salvar');

    if(acao==='cadastrar'){
        $("#modal-title").text('Cadastrar Proposta');
        let html='<label>Cliente:</label><select id="cliente" class="w-full rounded p-2 bg-[#2a2a3f] text-gray-200">';
        for(let k in clientes) html+=`<option value="${k}">${clientes[k]}</option>`;
        html+='</select><label>Visita:</label><select id="visita" class="w-full rounded p-2 bg-[#2a2a3f] text-gray-200">';
        for(let k in visitas) html+=`<option value="${k}">${visitas[k]}</option>`;
        html+='</select><label>Valor Ofertado:</label><input id="valor" type="number" class="w-full rounded p-2 bg-[#2a2a3f] text-gray-200"/><label>Data:</label><input id="data" type="date" class="w-full rounded p-2 bg-[#2a2a3f] text-gray-200"/><label>Status:</label><select id="status" class="w-full rounded p-2 bg-[#2a2a3f] text-gray-200"><option>Pendente</option><option>Aceita</option><option>Recusada</option></select>';
        $("#modal-body").html(html);
        $("#modal-action").click(function(){
            $.post('',{
                acao:'cadastrar',
                cliente:$("#cliente").val(),
                visita:$("#visita").val(),
                valor:$("#valor").val(),
                data:$("#data").val(),
                status:$("#status").val()
            },function(r){if(r.status==='sucesso'){fecharModal(); carregarTabela();} else alert(r.mensagem);},'json');
        });
    }

    if(acao==='editar'){
        $("#modal-title").text('Editar Proposta');
        let html='<label>Cliente:</label><select id="cliente" class="w-full rounded p-2 bg-[#2a2a3f] text-gray-200">';
        for(let k in clientes) html+=`<option value="${k}" ${cliente==k?'selected':''}>${clientes[k]}</option>`;
        html+='</select><label>Visita:</label><select id="visita" class="w-full rounded p-2 bg-[#2a2a3f] text-gray-200">';
        for(let k in visitas) html+=`<option value="${k}" ${imovel==k?'selected':''}>${visitas[k]}</option>`;
        html+='</select><label>Valor Ofertado:</label><input id="valor" type="number" value="'+valor+'" class="w-full rounded p-2 bg-[#2a2a3f] text-gray-200"/><label>Data:</label><input id="data" type="date" value="'+data+'" class="w-full rounded p-2 bg-[#2a2a3f] text-gray-200"/><label>Status:</label><select id="status" class="w-full rounded p-2 bg-[#2a2a3f] text-gray-200"><option '+(status==='Pendente'?'selected':'')+'>Pendente</option><option '+(status==='Aceita'?'selected':'')+'>Aceita</option><option '+(status==='Recusada'?'selected':'')+'>Recusada</option></select>';
        $("#modal-body").html(html);
        $("#modal-action").click(function(){
            $.post('',{
                acao:'editar',id:id,
                cliente:$("#cliente").val(),
                visita:$("#visita").val(),
                valor:$("#valor").val(),
                data:$("#data").val(),
                status:$("#status").val()
            },function(r){if(r.status==='sucesso'){fecharModal(); carregarTabela();} else alert(r.mensagem);},'json');
        });
    }

    if(acao==='excluir'){
        $("#modal-title").text('Excluir Proposta');
        $("#modal-body").html('<p>Tem certeza que deseja excluir esta proposta?</p>');
        $("#modal-action").text('Excluir').click(function(){
            $.post('',{acao:'excluir',id:id},function(r){if(r.status==='sucesso'){fecharModal(); carregarTabela();} else alert(r.mensagem);},'json');
        });
    }
}

function fecharModal(){
    $("#modal").addClass('hidden');
    $("#modal-action").text('Salvar');
}
</script>
</body>
</html>
