<?php
include 'conexao.php'; // Caminho correto do seu arquivo de conexão
session_start();

// Apenas ADMIN pode acessar
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas ADMINS podem acessar esta página.');window.location='staffmenu.php';</script>";
    exit;
}

// -------------------- Ações AJAX --------------------
if (isset($_POST['acao'])) {
    if ($_POST['acao'] === 'cadastrar') {
        $usuario = trim($_POST['usuario'] ?? '');
        $senha = trim($_POST['senha'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $tipos_validos = ['admin', 'GESTOR', 'CORRETOR'];

        if ($usuario && $senha && in_array($tipo, $tipos_validos)) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO USUARIO (usuario, senha, tipo, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $usuario, $senha_hash, $tipo, $email);
            echo json_encode($stmt->execute() ? ['status'=>'sucesso'] : ['status'=>'erro','mensagem'=>$stmt->error]);
        } else {
            echo json_encode(['status'=>'erro','mensagem'=>'Usuário, senha e tipo válidos são obrigatórios']);
        }
        exit;
    }

    if ($_POST['acao'] === 'editar') {
        $id = intval($_POST['id']);
        $usuario = trim($_POST['usuario'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $tipos_validos = ['admin', 'GESTOR', 'CORRETOR'];

        if ($id && $usuario && in_array($tipo, $tipos_validos)) {
            $stmt = $conn->prepare("UPDATE USUARIO SET usuario=?, email=?, tipo=? WHERE idUSUARIO=?");
            $stmt->bind_param("sssi", $usuario, $email, $tipo, $id);
            echo json_encode($stmt->execute() ? ['status'=>'sucesso'] : ['status'=>'erro','mensagem'=>$stmt->error]);
        } else {
            echo json_encode(['status'=>'erro','mensagem'=>'Campos obrigatórios ausentes ou tipo inválido']);
        }
        exit;
    }

    if ($_POST['acao'] === 'alterar_senha') {
        $id = intval($_POST['id']);
        $nova_senha = $_POST['senha'] ?? '';
        if ($id && $nova_senha) {
            $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE USUARIO SET senha=? WHERE idUSUARIO=?");
            $stmt->bind_param("si", $hash, $id);
            echo json_encode($stmt->execute() ? ['status'=>'sucesso'] : ['status'=>'erro','mensagem'=>$stmt->error]);
        } else {
            echo json_encode(['status'=>'erro','mensagem'=>'Senha inválida']);
        }
        exit;
    }

    if ($_POST['acao'] === 'excluir') {
        $id = intval($_POST['id']);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM USUARIO WHERE idUSUARIO=?");
            $stmt->bind_param("i", $id);
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
    $where = "WHERE usuario LIKE '%$busca_esc%'";
}
$sql = "SELECT * FROM USUARIO $where ORDER BY idUSUARIO ASC";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerenciamento de Logins</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
  /* Animações e partículas */
  @keyframes fadeInPage { from {opacity:0;transform:translateY(20px);} to {opacity:1;transform:translateY(0);} }
  .fade-in-page { animation: fadeInPage 0.6s ease-in-out; }

  body::before {
    content:"";position:fixed;top:0;left:0;right:0;bottom:0;
    background:linear-gradient(135deg,#111,#1a1a1a,#222233,#1a1a1a);
    background-size:400% 400%;
    animation:gradientMove 20s ease infinite;
    z-index:-2;
  }
  @keyframes gradientMove {0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}

  .particle {position:absolute;border-radius:50%;background:rgba(255,255,255,0.03);pointer-events:none;z-index:-1;animation:floatParticle linear infinite;}
  @keyframes floatParticle {
    0%{transform:translateY(0) translateX(0) scale(0.5);opacity:0;}
    10%{opacity:0.2;}
    100%{transform:translateY(-800px) translateX(200px) scale(1);opacity:0;}
  }

  .btn-glow {position:relative;transition:all 0.3s ease;background:#1f1f2f;color:#e0e0e0;}
  .btn-glow::before {
    content:'';position:absolute;top:-2px;left:-2px;right:-2px;bottom:-2px;
    background:linear-gradient(45deg,#2a2a3f,#3a3a5a,#2a2a3f,#3a3a5a);
    border-radius:inherit;filter:blur(6px);opacity:0;transition:opacity 0.3s ease;z-index:-1;
  }
  .btn-glow:hover {background:#2c2c44;}
  .btn-glow:hover::before {opacity:1;}
  .card-dynamic {box-shadow:0 10px 25px rgba(0,0,0,0.6);transition:transform 0.3s ease, box-shadow 0.3s ease;}
  .card-dynamic:hover {transform:translateY(-5px);box-shadow:0 20px 40px rgba(0,0,0,0.8);}
  .title-glow {text-shadow:0 0 6px rgba(255,255,255,0.3);}
</style>
</head>
<body class="font-serif text-gray-100 min-h-screen relative overflow-hidden">

<!-- Partículas -->
<?php for($i=0;$i<25;$i++): ?>
  <div class="particle" style="width:<?=rand(5,15)?>px;height:<?=rand(5,15)?>px;top:<?=rand(0,100)?>%;left:<?=rand(0,100)?>%;animation-duration:<?=rand(20,40)?>s;animation-delay:<?=rand(0,20)?>s;"></div>
<?php endfor; ?>

<div class="fade-in-page w-full px-4 py-10 flex flex-col items-center">
  <div class="bg-[#1f1f2f]/90 backdrop-blur-md p-8 rounded-3xl w-full max-w-6xl shadow-2xl space-y-6 card-dynamic border border-[#2a2a3f]/50">
    
    <h2 class="text-3xl font-bold tracking-wide text-center text-gray-200 title-glow">Gerenciamento de Logins</h2>

    <div class="flex flex-wrap gap-4 justify-center">
        <button onclick="abrirModal('cadastrar')" class="py-2 px-5 rounded-xl font-bold btn-glow shadow-md">Novo Login</button>
        <a href="staffmenu.php" class="py-2 px-5 rounded-xl font-bold btn-glow shadow-md">Voltar</a>
    </div>

    <form method="get" class="flex mb-6">
        <input type="text" name="busca" placeholder="Buscar usuário..." value="<?= htmlspecialchars($busca) ?>"
            class="flex-1 p-3 rounded-l-xl bg-[#2a2a3f] text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#3a3a5a] shadow-inner"/>
        <button type="submit" class="px-6 rounded-r-xl font-bold btn-glow shadow-md">Pesquisar</button>
    </form>

    <!-- Tabela com scroll vertical -->
    <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-[#2a2a3f]">
            <th class="p-3 border border-[#3a3a5a]">ID</th>
            <th class="p-3 border border-[#3a3a5a]">Usuário</th>
            <th class="p-3 border border-[#3a3a5a]">Email</th>
            <th class="p-3 border border-[#3a3a5a]">Tipo</th>
            <th class="p-3 border border-[#3a3a5a]">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php while($linha = $resultado->fetch_assoc()): ?>
          <tr class="bg-[#1f1f2f] hover:bg-[#2c2c44] transition">
            <td class="p-3 border border-[#2a2a3f]"><?= $linha['idUSUARIO'] ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['usuario']) ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['email']) ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['tipo']) ?></td>
            <td class="p-3 border border-[#2a2a3f] flex gap-2">
              <button onclick="abrirModal('editar', <?= $linha['idUSUARIO'] ?>, '<?= addslashes($linha['usuario']) ?>', '<?= addslashes($linha['email']) ?>', '<?= addslashes($linha['tipo']) ?>')" class="px-3 py-1 rounded-md font-bold btn-glow shadow-md">Editar</button>
              <button onclick="abrirModal('alterar_senha', <?= $linha['idUSUARIO'] ?>)" class="px-3 py-1 rounded-md font-bold btn-glow shadow-md">Senha</button>
              <button onclick="abrirModal('excluir', <?= $linha['idUSUARIO'] ?>)" class="px-3 py-1 rounded-md font-bold btn-glow shadow-md">Excluir</button>
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
    <div id="modal-body"></div>
    <div class="flex justify-end gap-2 mt-4">
        <button onclick="fecharModal()" class="py-2 px-4 rounded-xl font-bold btn-glow shadow-md">Cancelar</button>
        <button id="modal-confirm" class="py-2 px-4 rounded-xl font-bold btn-glow shadow-md">Confirmar</button>
    </div>
    <button onclick="fecharModal()" class="absolute top-2 right-2 text-gray-400 hover:text-white">&times;</button>
  </div>
</div>

<script>
function abrirModal(tipo, id='', usuario='', email='', tipoUser='') {
    $('#modal').removeClass('hidden');

    if(tipo==='cadastrar') {
        $('#modal-title').text('Cadastrar Login');
        $('#modal-body').html(`
            <input type="text" id="usuario" placeholder="Usuário" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
            <input type="password" id="senha" placeholder="Senha" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
            <input type="email" id="email" placeholder="Email" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
            <select id="tipo" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
                <option value="">Selecione o tipo</option>
                <option value="admin">Administrador</option>
                <option value="GESTOR">Gestor</option>
                <option value="CORRETOR">Corretor</option>
            </select>
        `);
        $('#modal-confirm').off('click').click(function(){
            $.post('', {
                acao:'cadastrar',
                usuario:$('#usuario').val(),
                senha:$('#senha').val(),
                email:$('#email').val(),
                tipo:$('#tipo').val()
            }, function(res){
                res = JSON.parse(res);
                alert(res.status==='sucesso'?'Cadastro realizado!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });

    } else if(tipo==='editar') {
        $('#modal-title').text('Editar Login');
        $('#modal-body').html(`
            <input type="hidden" id="id" value="${id}">
            <input type="text" id="usuario" value="${usuario}" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
            <input type="email" id="email" value="${email}" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
            <select id="tipo" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">
                <option value="admin" ${tipoUser==='admin'?'selected':''}>Administrador</option>
                <option value="GESTOR" ${tipoUser==='GESTOR'?'selected':''}>Gestor</option>
                <option value="CORRETOR" ${tipoUser==='CORRETOR'?'selected':''}>Corretor</option>
            </select>
        `);
        $('#modal-confirm').off('click').click(function(){
            $.post('', {
                acao:'editar',
                id:id,
                usuario:$('#usuario').val(),
                email:$('#email').val(),
                tipo:$('#tipo').val()
            }, function(res){
                res = JSON.parse(res);
                alert(res.status==='sucesso'?'Editado com sucesso!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });

    } else if(tipo==='alterar_senha') {
        $('#modal-title').text('Alterar Senha');
        $('#modal-body').html(`<input type="password" id="senha" placeholder="Nova senha" class="w-full p-2 mb-2 rounded bg-[#2a2a3f]">`);
        $('#modal-confirm').off('click').click(function(){
            $.post('', {acao:'alterar_senha', id:id, senha:$('#senha').val()}, function(res){
                res = JSON.parse(res);
                alert(res.status==='sucesso'?'Senha alterada!':res.mensagem);
                if(res.status==='sucesso') location.reload();
            });
        });
    } else if(tipo==='excluir') {
        $('#modal-title').text('Confirmar Exclusão');
        $('#modal-body').html('<p>Deseja realmente excluir este login?</p>');
        $('#modal-confirm').off('click').click(function(){
            $.post('', {acao:'excluir', id:id}, function(res){
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
