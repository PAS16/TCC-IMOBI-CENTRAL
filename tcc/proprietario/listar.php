<?php
include '../conexao.php';

// -------------------- Ações AJAX --------------------
// Cadastrar
if (isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
    $nome = $_POST['nome'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';

    if ($nome && $cpf && $email) {
        $stmt = $conn->prepare("INSERT INTO PROPRIETARIO (nome, cpf, telefone, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome, $cpf, $telefone, $email);
        echo json_encode($stmt->execute() ? ['status'=>'sucesso'] : ['status'=>'erro','mensagem'=>$stmt->error]);
    } else {
        echo json_encode(['status'=>'erro','mensagem'=>'Nome, CPF e Email são obrigatórios']);
    }
    exit;
}

// Editar
if (isset($_POST['acao']) && $_POST['acao'] === 'editar') {
    $id = intval($_POST['id']);
    $nome = $_POST['nome'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';

    if ($id && $nome && $cpf && $email) {
        $stmt = $conn->prepare("UPDATE PROPRIETARIO SET nome=?, cpf=?, telefone=?, email=? WHERE idPROPRIETARIO=?");
        $stmt->bind_param("ssssi", $nome, $cpf, $telefone, $email, $id);
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
        $stmt = $conn->prepare("DELETE FROM PROPRIETARIO WHERE idPROPRIETARIO=?");
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

$sql = "SELECT idPROPRIETARIO AS id, nome, cpf, telefone, email FROM PROPRIETARIO $where ORDER BY id ASC";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerenciar Proprietários</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
/* ===== Estilo igual ao painel de Clientes ===== */
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
  <div class="bg-[#1f1f2f]/90 backdrop-blur-md p-8 rounded-3xl w-full max-w-5xl shadow-2xl space-y-6 card-dynamic border border-[#2a2a3f]/50">
    
    <h2 class="text-3xl font-bold tracking-wide text-center text-gray-200 title-glow">Gerenciar Proprietários</h2>

    <div class="flex flex-wrap gap-4 justify-center">
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
            <th class="p-3 border border-[#3a3a5a]">CPF</th>
            <th class="p-3 border border-[#3a3a5a]">Telefone</th>
            <th class="p-3 border border-[#3a3a5a]">Email</th>
            <th class="p-3 border border-[#3a3a5a]">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php while($linha = $resultado->fetch_assoc()): ?>
          <tr class="bg-[#1f1f2f] hover:bg-[#2c2c44] transition">
            <td class="p-3 border border-[#2a2a3f]"><?= $linha['id'] ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['nome']) ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['cpf']) ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['telefone']) ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['email']) ?></td>
            <td class="p-3 border border-[#2a2a3f] flex gap-2">
              <button onclick="abrirModal('editar', <?= $linha['id'] ?>, '<?= addslashes($linha['nome']) ?>', '<?= addslashes($linha['cpf']) ?>', '<?= addslashes($linha['telefone']) ?>', '<?= addslashes($linha['email']) ?>')" class="px-3 py-1 rounded-md font-bold btn-glow shadow-md">Editar</button>
              <button onclick="abrirModal('excluir', <?= $linha['id'] ?>)" class="px-3 py-1 rounded-md font-bold btn-glow shadow-md">Excluir</button>
            </td>
          </tr>
        <?php endwhile; ?>
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
function abrirModal(tipo, id='', nome='', cpf='', telefone='', email='') {
    $('#modal').removeClass('hidden');
    if(tipo==='cadastrar') {
        $('#modal-title').text('Cadastrar Proprietário');
        $('#modal-body').html(`
            <input type="text" id="nome" placeholder="Nome" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">
            <input type="text" id="cpf" placeholder="CPF" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">
            <input type="text" id="telefone" placeholder="Telefone" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">
            <input type="email" id="email" placeholder="Email" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">
        `);
        $('#modal-confirm').off('click').click(function(){
            $.post('', {acao:'cadastrar', nome:$('#nome').val(), cpf:$('#cpf').val(), telefone:$('#telefone').val(), email:$('#email').val()}, function(res){
                res = JSON.parse(res);
                alert(res.status==='sucesso'?'Cadastro realizado!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    } else if(tipo==='editar') {
        $('#modal-title').text('Editar Proprietário');
        $('#modal-body').html(`
            <input type="hidden" id="id" value="${id}">
            <input type="text" id="nome" value="${nome}" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">
            <input type="text" id="cpf" value="${cpf}" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">
            <input type="text" id="telefone" value="${telefone}" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">
            <input type="email" id="email" value="${email}" class="w-full p-2 mb-2 rounded bg-[#2a2a3f] text-gray-200">
        `);
        $('#modal-confirm').off('click').click(function(){
            $.post('', {acao:'editar', id:id, nome:$('#nome').val(), cpf:$('#cpf').val(), telefone:$('#telefone').val(), email:$('#email').val()}, function(res){
                res = JSON.parse(res);
                alert(res.status==='sucesso'?'Editado com sucesso!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    } else if(tipo==='excluir') {
        $('#modal-title').text('Confirmar Exclusão');
        $('#modal-body').html('<p>Deseja realmente excluir este proprietário?</p>');
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
