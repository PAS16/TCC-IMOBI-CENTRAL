<?php
session_start();
if (!isset($_SESSION['idUSUARIO']) || !isset($_SESSION['tipo'])) {
    header("Location: glogin.php");
    exit();
}

include 'conexao.php'; // Certifique-se de que o caminho está correto

// NOVO: Função para determinar o tipo de dado para bind_param (crucial para a reversão)
// Se o valor for um número (int ou float), usa 'i' ou 'd'. Caso contrário, usa 's' (string).
function get_db_type($value) {
    if (is_int($value)) return 'i';
    if (is_float($value)) return 'd';
    return 's';
}

// Função auxiliar para referências em bind_param (necessário para call_user_func_array)
function ref_values($arr){
    $refs = array();
    foreach($arr as $key => $value) $refs[$key] = &$arr[$key];
    return $refs;
}

// Receber filtros
$filtroUsuario = $_GET['usuario'] ?? '';
$filtroTabela  = $_GET['tabela'] ?? '';

// Reverter alteração (Ação POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reverter_id'])) {
    $id = intval($_POST['reverter_id']);
    $stmt = $conn->prepare("SELECT tabela, registro_id, dados_anteriores FROM HISTORICO WHERE idHISTORICO=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    
    if ($res) {
        $tabela = $res['tabela'];
        // Garante que a coluna primária é o padrão idTABELA
        $coluna_id = "id" . strtoupper($tabela); 
        $id_registro = intval($res['registro_id']);
        $dados_anteriores = json_decode($res['dados_anteriores'], true) ?? [];

        $campos = [];
        $valores = [];
        $types_array = []; 

        foreach($dados_anteriores as $k=>$v){
            $campos[] = "$k=?";
            $valores[] = $v;
            $types_array[] = get_db_type($v);
        }

        if($campos){
            $types = implode('', $types_array);
            
            $sql = "UPDATE $tabela SET ".implode(', ',$campos)." WHERE $coluna_id=?";
            
            $valores[] = $id_registro; // Adiciona o ID do registro no final dos valores
            $types .= 'i'; // Adiciona 'i' para o tipo do ID do registro
            
            $stmtU = $conn->prepare($sql);
            
            // Passa os parâmetros com a função auxiliar
            $bind_params = array_merge([$types], $valores);
            call_user_func_array([$stmtU, 'bind_param'], ref_values($bind_params));

            $stmtU->execute();
            if ($stmtU->affected_rows > 0) {
                 echo "<script>alert('Reversão bem-sucedida! A página será recarregada.'); window.location.href = window.location.href;</script>";
            } else {
                 echo "<script>alert('Reversão concluída, mas nenhum registro foi alterado (pode ser erro ou dados já revertidos).'); window.location.href = window.location.href;</script>";
            }
        }
    }
}

// Buscar histórico com filtros
$where = [];
$params = [];
$types = '';

if($filtroUsuario){
    $where[] = "u.usuario LIKE ?";
    $params[] = "%$filtroUsuario%";
    $types .= 's';
}
if($filtroTabela){
    $where[] = "h.tabela LIKE ?";
    $params[] = "%$filtroTabela%";
    $types .= 's';
}

$sql = "SELECT h.*, u.usuario FROM HISTORICO h 
        LEFT JOIN USUARIO u ON h.usuario_idUSUARIO = u.idUSUARIO";
if($where){
    $sql .= " WHERE ".implode(" AND ", $where);
}
$sql .= " ORDER BY h.idHISTORICO DESC";

$stmt = $conn->prepare($sql);
if($params){
    // Usa a função auxiliar para passar os parâmetros
    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], ref_values($bind_params));
}
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Histórico de Alterações</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
/* Estilos mantidos para consistência visual */
@keyframes fadeInPage { from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);} }
.fade-in-page{animation:fadeInPage 0.6s ease-in-out;}
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
<body class="font-serif text-gray-100 min-h-screen relative overflow-hidden">

<?php for($i=0;$i<25;$i++): ?>
  <div class="particle" style="width:<?=rand(5,15)?>px;height:<?=rand(5,15)?>px;top:<?=rand(0,100)?>%;left:<?=rand(0,100)?>%;animation-duration:<?=rand(20,40)?>s;animation-delay:<?=rand(0,20)?>s;"></div>
<?php endfor; ?>

<div class="fade-in-page w-full px-4 py-10 flex flex-col items-center">
  <div class="bg-[#1f1f2f]/90 backdrop-blur-md p-8 rounded-3xl w-full max-w-6xl shadow-2xl space-y-6 card-dynamic border border-[#2a2a3f]/50">
    <h2 class="text-3xl font-bold tracking-wide text-center text-gray-200 title-glow">Histórico de Alterações</h2>

    <form method="GET" class="flex flex-wrap gap-4 justify-center mb-6">
        <input type="text" name="usuario" placeholder="Filtrar por usuário" value="<?=htmlspecialchars($filtroUsuario)?>"
            class="px-4 py-2 rounded-xl bg-[#2a2a3f]/50 text-gray-200 border border-[#3a3a5a] focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <input type="text" name="tabela" placeholder="Filtrar por tabela" value="<?=htmlspecialchars($filtroTabela)?>"
            class="px-4 py-2 rounded-xl bg-[#2a2a3f]/50 text-gray-200 border border-[#3a3a5a] focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <button type="submit" class="px-4 py-2 rounded-xl btn-glow">Filtrar</button>
        <a href="staffmenu.php" class="px-4 py-2 rounded-xl btn-glow">Voltar</a>
    </form>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-[#2a2a3f]">
            <th class="p-3 border border-[#3a3a5a]">ID</th>
            <th class="p-3 border border-[#3a3a5a]">Usuário</th>
            <th class="p-3 border border-[#3a3a5a]">Tabela</th>
            <th class="p-3 border border-[#3a3a5a]">ID Registro</th>
            <th class="p-3 border border-[#3a3a5a]">Ação</th>
            <th class="p-3 border border-[#3a3a5a]">Data</th>
            <th class="p-3 border border-[#3a3a5a]">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php while($linha = $resultado->fetch_assoc()): ?>
          <tr class="bg-[#1f1f2f] hover:bg-[#2c2c44] transition">
            <td class="p-3 border border-[#2a2a3f]"><?= $linha['idHISTORICO'] ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['usuario'] ?? 'N/A') ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['tabela']) ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= $linha['registro_id'] ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['acao']) ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= $linha['data_hora'] ?></td>
            <td class="p-3 border border-[#2a2a3f] flex gap-2">
              <button onclick="abrirModal(<?= $linha['idHISTORICO'] ?>)" class="px-3 py-1 rounded-md btn-glow">Ver</button>
              <?php if ($linha['acao'] === 'UPDATE'): ?>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja reverter esta alteração?');">
                <input type="hidden" name="reverter_id" value="<?= $linha['idHISTORICO'] ?>">
                <button type="submit" class="px-3 py-1 rounded-md btn-glow bg-red-600 hover:bg-red-700">Reverter</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50">
  <div class="bg-[#1f1f2f] p-6 rounded-2xl w-11/12 max-w-4xl max-h-[90vh] overflow-y-auto relative card-dynamic border border-[#2a2a3f]/50">
    <h2 id="modal-title" class="text-xl font-bold mb-4 title-glow">Detalhes</h2>
    <div id="modal-body" class="space-y-2"></div>
    <button onclick="fecharModal()" class="absolute top-2 right-2 text-gray-400 hover:text-white">&times;</button>
  </div>
</div>

<script>
function abrirModal(id){
    $('#modal').removeClass('hidden');
    $('#modal-title').text('Histórico #' + id + ' (Detalhes)');
    $('#modal-body').html('<div class="text-center py-4">Carregando detalhes...</div>');

    // Chama o arquivo auxiliar via AJAX
    $.get('historico_detalhes.php', {id:id}, function(res){
        $('#modal-body').html(res);
    }).fail(function(){
        $('#modal-body').html('<p class="text-red-500">Erro ao carregar detalhes. Verifique o arquivo historico_detalhes.php</p>');
    });
}

function fecharModal(){
    $('#modal').addClass('hidden');
    $('#modal-body').empty();
}
</script>
</body>
</html>