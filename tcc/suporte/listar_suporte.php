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

    <h2 class="text-3xl font-bold tracking-wide text-center text-gray-200 title-glow">📩 Mensagens de Suporte</h2>

    <div class="flex flex-wrap gap-4 justify-center">
        <a href="../staffmenu.php" class="py-2 px-5 rounded-xl font-bold btn-glow shadow-md">⬅ Voltar</a>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse mt-4">
        <thead>
          <tr class="bg-[#2a2a3f]">
            <th class="p-3 border border-[#3a3a5a]">ID</th>
            <th class="p-3 border border-[#3a3a5a]">Nome</th>
            <th class="p-3 border border-[#3a3a5a]">Email</th>
            <th class="p-3 border border-[#3a3a5a]">Telefone</th>
            <th class="p-3 border border-[#3a3a5a]">Mensagem</th>
            <th class="p-3 border border-[#3a3a5a]">Status</th>
            <th class="p-3 border border-[#3a3a5a]">Data</th>
            <th class="p-3 border border-[#3a3a5a]">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php if($resultado->num_rows > 0): while($s = $resultado->fetch_assoc()): ?>
          <tr class="bg-[#1f1f2f] hover:bg-[#2c2c44] transition">
            <td class="p-3 border border-[#2a2a3f]"><?= $s['id'] ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($s['nome']) ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($s['email']) ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($s['telefone']) ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= nl2br(htmlspecialchars($s['mensagem'])) ?></td>
            <td class="p-3 border border-[#2a2a3f]">
              <select onchange="atualizarStatus(<?= $s['id'] ?>, this.value)" class="bg-[#2a2a3f] p-1 rounded text-gray-200">
                <option value="Pendente" <?= $s['status']==='Pendente'?'selected':'' ?>>Pendente</option>
                <option value="Atendido" <?= $s['status']==='Atendido'?'selected':'' ?>>Atendido</option>
              </select>
            </td>
            <td class="p-3 border border-[#2a2a3f]"><?= $s['data_criacao'] ?></td>
            <td class="p-3 border border-[#2a2a3f] flex gap-2">
              <button onclick="excluirMensagem(<?= $s['id'] ?>)" class="px-3 py-1 rounded-md font-bold btn-glow shadow-md">Excluir</button>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="8" class="text-center p-3 border">Nenhuma mensagem encontrada.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
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
