<?php
include '../conexao.php';

// -------------------- Ações AJAX --------------------
if (isset($_POST['acao'])) {

    // -------- CADASTRAR --------
    if ($_POST['acao'] === 'cadastrar') {
        $nome = $_POST['nome'] ?? '';
        $creci = $_POST['creci'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $usuario = $_POST['usuario'] ?? '';
        $senha = $_POST['senha'] ?? '';

        if ($nome && $creci && $usuario && $senha) {
            $hashSenha = password_hash($senha, PASSWORD_DEFAULT);

            $stmt1 = $conn->prepare("INSERT INTO USUARIO (usuario, senha, tipo) VALUES (?, ?, 'CORRETOR')");
            $stmt1->bind_param("ss", $usuario, $hashSenha);
            if ($stmt1->execute()) {
                $idUsuario = $stmt1->insert_id;

                $stmt2 = $conn->prepare("INSERT INTO CORRETOR (USUARIO_idUSUARIO, nome, creci, telefone) VALUES (?, ?, ?, ?)");
                $stmt2->bind_param("isss", $idUsuario, $nome, $creci, $telefone);
                echo json_encode($stmt2->execute() ? ['status'=>'sucesso'] : ['status'=>'erro','mensagem'=>$stmt2->error]);
            } else {
                echo json_encode(['status'=>'erro','mensagem'=>$stmt1->error]);
            }
        } else {
            echo json_encode(['status'=>'erro','mensagem'=>'Todos os campos obrigatórios']);
        }
        exit;
    }

    // -------- EDITAR --------
    if ($_POST['acao'] === 'editar') {
        $id = intval($_POST['id']);
        $nome = $_POST['nome'] ?? '';
        $creci = $_POST['creci'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $usuario = $_POST['usuario'] ?? '';

        if ($id && $nome && $creci && $usuario) {
            $res = $conn->query("SELECT USUARIO_idUSUARIO FROM CORRETOR WHERE idCORRETOR=$id");
            $linha = $res->fetch_assoc();
            $idUsuario = $linha['USUARIO_idUSUARIO'];

            $stmt1 = $conn->prepare("UPDATE USUARIO SET usuario=? WHERE idUSUARIO=?");
            $stmt1->bind_param("si", $usuario, $idUsuario);
            $stmt1->execute();

            $stmt2 = $conn->prepare("UPDATE CORRETOR SET nome=?, creci=?, telefone=? WHERE idCORRETOR=?");
            $stmt2->bind_param("sssi", $nome, $creci, $telefone, $id);
            echo json_encode($stmt2->execute() ? ['status'=>'sucesso'] : ['status'=>'erro','mensagem'=>$stmt2->error]);
        } else {
            echo json_encode(['status'=>'erro','mensagem'=>'Todos os campos obrigatórios']);
        }
        exit;
    }

    // -------- EXCLUIR --------
    if ($_POST['acao'] === 'excluir') {
        $id = intval($_POST['id']);
        if ($id) {
            $res = $conn->query("SELECT USUARIO_idUSUARIO FROM CORRETOR WHERE idCORRETOR=$id");
            $linha = $res->fetch_assoc();
            $idUsuario = $linha['USUARIO_idUSUARIO'];

            $stmt = $conn->prepare("DELETE FROM USUARIO WHERE idUSUARIO=?");
            $stmt->bind_param("i", $idUsuario);
            echo json_encode($stmt->execute() ? ['status'=>'sucesso'] : ['status'=>'erro','mensagem'=>$stmt->error]);
        } else {
            echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']);
        }
        exit;
    }
}

// -------------------- Buscar / Listar --------------------
$busca = $_GET['busca'] ?? '';
$where = '';
if (!empty($busca)) {
    $busca_esc = mysqli_real_escape_string($conn, $busca);
    $where = "WHERE CORRETOR.nome LIKE '%$busca_esc%'";
}

$sql = "SELECT CORRETOR.*, USUARIO.usuario FROM CORRETOR 
        INNER JOIN USUARIO ON CORRETOR.USUARIO_idUSUARIO = USUARIO.idUSUARIO
        $where ORDER BY idCORRETOR ASC";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Corretores</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
/* ===================== Background animado ===================== */
@keyframes gradientMove {0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
body::before{content:"";position:fixed;top:0;left:0;right:0;bottom:0;background:linear-gradient(135deg,#111111,#1a1a1a,#222233,#1a1a1a);background-size:400% 400%;animation:gradientMove 20s ease infinite;z-index:-2;}

/* ===================== Partículas ===================== */
.particle{position:absolute;border-radius:50%;background:rgba(255,255,255,0.03);pointer-events:none;z-index:-1;animation:floatParticle linear infinite;}
@keyframes floatParticle {0%{transform:translateY(0) translateX(0) scale(0.5);opacity:0;}10%{opacity:0.2;}100%{transform:translateY(-800px) translateX(200px) scale(1);opacity:0;}}

/* ===================== Botões ===================== */
.btn-glow{position:relative;transition:all 0.3s ease;background:#1f1f2f;color:#e0e0e0;}
.btn-glow::before{content:'';position:absolute;top:-2px;left:-2px;right:-2px;bottom:-2px;background:linear-gradient(45deg,#2a2a3f,#3a3a5a,#2a2a3f,#3a3a5a);border-radius:inherit;filter:blur(6px);opacity:0;transition:opacity 0.3s ease;z-index:-1;}
.btn-glow:hover{background:#2c2c44;}
.btn-glow:hover::before{opacity:1;}

/* ===================== Cards ===================== */
.card-dynamic{box-shadow:0 10px 25px rgba(0,0,0,0.6);transition:transform 0.3s ease, box-shadow 0.3s ease;}
.card-dynamic:hover{transform:translateY(-5px);box-shadow:0 20px 40px rgba(0,0,0,0.8);}

/* ===================== Títulos ===================== */
.title-glow{text-shadow:0 0 6px rgba(255,255,255,0.3);}
</style>
</head>
<body class="font-serif text-gray-100 min-h-screen relative overflow-hidden">

<!-- Partículas -->
<?php for($i=0;$i<25;$i++): ?>
  <div class="particle" style="width:<?=rand(5,15)?>px;height:<?=rand(5,15)?>px;top:<?=rand(0,100)?>%;left:<?=rand(0,100)?>%;animation-duration:<?=rand(20,40)?>s;animation-delay:<?=rand(0,20)?>s;"></div>
<?php endfor; ?>

<div class="bg-[#1f1f2f]/90 backdrop-blur-md p-8 rounded-3xl w-full max-w-6xl shadow-2xl space-y-6 card-dynamic border border-[#2a2a3f]/50 mx-auto mt-12">

<h2 class="text-3xl font-bold tracking-wide text-center text-gray-200 title-glow">Gerenciamento de Corretores</h2>

<div class="flex flex-wrap gap-4 justify-center mb-6">
    <button onclick="abrirModal('cadastrar')" class="py-2 px-5 rounded-xl font-bold btn-glow shadow-md">Cadastrar Novo</button>
    <a href="../staffmenu.php" class="py-2 px-5 rounded-xl font-bold btn-glow shadow-md">Voltar</a>
</div>

<form method="get" class="flex mb-6">
    <input type="text" name="busca" placeholder="Buscar por nome..." value="<?= htmlspecialchars($busca) ?>"
        class="flex-1 p-3 rounded-l-xl bg-[#2a2a3f] text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#3a3a5a] shadow-inner"/>
    <button type="submit" class="px-6 rounded-r-xl font-bold btn-glow shadow-md">Pesquisar</button>
</form>

<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-[#2a2a3f]">
<th class="p-3 border border-[#3a3a5a]">ID</th>
<th class="p-3 border border-[#3a3a5a]">Nome</th>
<th class="p-3 border border-[#3a3a5a]">Usuário</th>
<th class="p-3 border border-[#3a3a5a]">CRECI</th>
<th class="p-3 border border-[#3a3a5a]">Telefone</th>
<th class="p-3 border border-[#3a3a5a]">Ações</th>
</tr>
</thead>
<tbody>
<?php while($linha = $resultado->fetch_assoc()): ?>
<tr class="bg-[#1f1f2f] hover:bg-[#2c2c44] transition">
<td class="p-3 border border-[#2a2a3f]"><?= $linha['idCORRETOR'] ?></td>
<td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['nome']) ?></td>
<td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['usuario']) ?></td>
<td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['creci']) ?></td>
<td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['telefone']) ?></td>
<td class="p-3 border border-[#2a2a3f] flex gap-2">
<button onclick="abrirModal('editar', <?= $linha['idCORRETOR'] ?>, '<?= addslashes($linha['nome']) ?>', '<?= addslashes($linha['creci']) ?>', '<?= addslashes($linha['telefone']) ?>', '<?= addslashes($linha['usuario']) ?>')" class="py-2 px-4 rounded-xl font-bold btn-glow shadow-md">Editar</button>
<button onclick="abrirModal('excluir', <?= $linha['idCORRETOR'] ?>)" class="py-2 px-4 rounded-xl font-bold btn-glow shadow-md">Excluir</button>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<!-- Modal -->
<div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50">
  <div class="bg-[#1f1f2f] p-6 rounded-2xl w-96 relative card-dynamic border border-[#2a2a3f]/50">
    <h2 id="modal-title" class="text-xl font-bold mb-4 title-glow"></h2>
    <div id="modal-body"></div>
    <div class="flex justify-end gap-2 mt-4">
        <button onclick="fecharModal()" class="py-2 px-4 rounded-xl font-bold btn-glow shadow-md">Cancelar</button>
        <button id="modal-confirm" class="py-2 px-4 rounded-xl font-bold btn-glow shadow-md">Confirmar</button>
    </div>
    <button onclick="fecharModal()" class="absolute top-2 right-2 text-gray-400 hover:text-white">&times;</button>
  </div>
</div>

<script>
function abrirModal(tipo, id='', nome='', creci='', telefone='', usuario='') {
    $('#modal').removeClass('hidden');
    if(tipo==='cadastrar') {
        $('#modal-title').text('Cadastrar Corretor');
        $('#modal-body').html(`
            <input type="text" id="nome" placeholder="Nome" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
            <input type="text" id="usuario" placeholder="Usuário" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
            <input type="text" id="senha" placeholder="Senha" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
            <input type="text" id="creci" placeholder="CRECI" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
            <input type="text" id="telefone" placeholder="Telefone" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
        `);
        $('#modal-confirm').off('click').click(function(){
            $.post(window.location.href, {
                acao:'cadastrar',
                nome:$('#nome').val(),
                usuario:$('#usuario').val(),
                senha:$('#senha').val(),
                creci:$('#creci').val(),
                telefone:$('#telefone').val()
            }, function(res){
                res = JSON.parse(res);
                alert(res.status==='sucesso'?'Cadastro realizado!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    } else if(tipo==='editar') {
        $('#modal-title').text('Editar Corretor');
        $('#modal-body').html(`
            <input type="hidden" id="id" value="${id}">
            <input type="text" id="nome" value="${nome}" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
            <input type="text" id="usuario" value="${usuario}" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
            <input type="text" id="creci" value="${creci}" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
            <input type="text" id="telefone" value="${telefone}" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
        `);
        $('#modal-confirm').off('click').click(function(){
            $.post(window.location.href, {
                acao:'editar',
                id:id,
                nome:$('#nome').val(),
                usuario:$('#usuario').val(),
                creci:$('#creci').val(),
                telefone:$('#telefone').val()
            }, function(res){
                res = JSON.parse(res);
                alert(res.status==='sucesso'?'Editado com sucesso!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    } else if(tipo==='excluir') {
        $('#modal-title').text('Confirmar Exclusão');
        $('#modal-body').html('<p>Deseja realmente excluir este corretor?</p>');
        $('#modal-confirm').off('click').click(function(){
            $.post(window.location.href, {acao:'excluir', id:id}, function(res){
                res = JSON.parse(res);
                alert(res.status==='sucesso'?'Excluído com sucesso!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    }
}
function fecharModal() { $('#modal').addClass('hidden'); }
</script>
</body>
</html>
