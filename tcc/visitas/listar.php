<?php
include '../conexao.php';

// Garantir que conexão exista
if (!$conn) {
    die(json_encode(['status' => 'erro', 'mensagem' => 'Falha na conexão com o banco.']));
}

// -------------------- Ações AJAX --------------------
if (isset($_POST['acao'])) {

    function limpar($v) {
        return trim($v ?? '');
    }

    $acao = $_POST['acao'];

    if ($acao === 'cadastrar') {
        $cliente = intval($_POST['cliente'] ?? 0);
        $imovel  = intval($_POST['imovel'] ?? 0);
        $data    = limpar($_POST['data'] ?? '');
        $obs     = limpar($_POST['observacoes'] ?? '');

        if ($cliente && $imovel && $data) {
            $stmt = $conn->prepare("
                INSERT INTO VISITA (CORRETOR_idCORRETOR, IMOVEL_idIMOVEL, CLIENTE_idCLIENTE, data_visita, observacoes)
                VALUES (1, ?, ?, ?, ?)
            ");

            $stmt->bind_param("iiss", $imovel, $cliente, $data, $obs);

            echo json_encode($stmt->execute()
                ? ['status' => 'sucesso']
                : ['status' => 'erro', 'mensagem' => $stmt->error]
            );
        } else {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha todos os campos obrigatórios.']);
        }

        exit;
    }

    if ($acao === 'editar') {
        $id      = intval($_POST['id']);
        $cliente = intval($_POST['cliente']);
        $imovel  = intval($_POST['imovel']);
        $data    = limpar($_POST['data']);
        $obs     = limpar($_POST['observacoes']);

        if ($id && $cliente && $imovel && $data) {
            $stmt = $conn->prepare("
                UPDATE VISITA
                SET IMOVEL_idIMOVEL=?, CLIENTE_idCLIENTE=?, data_visita=?, observacoes=?
                WHERE idVISITA=?
            ");

            $stmt->bind_param("iissi", $imovel, $cliente, $data, $obs, $id);

            echo json_encode($stmt->execute()
                ? ['status' => 'sucesso']
                : ['status' => 'erro', 'mensagem' => $stmt->error]
            );
        } else {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Campos faltando.']);
        }

        exit;
    }

    if ($acao === 'excluir') {
        $id = intval($_POST['id']);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM VISITA WHERE idVISITA=?");
            $stmt->bind_param("i", $id);
            echo json_encode($stmt->execute()
                ? ['status' => 'sucesso']
                : ['status' => 'erro', 'mensagem' => $stmt->error]
            );
        } else {
            echo json_encode(['status' => 'erro', 'mensagem' => 'ID inválido']);
        }
        exit;
    }
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
while ($r = $res->fetch_assoc()) {
    $clientes[$r['idCLIENTE']] = $r['nome'];
}

$imoveis = [];
$res = $conn->query("SELECT idIMOVEL,descricao FROM IMOVEL");
while ($r = $res->fetch_assoc()) {
    $imoveis[$r['idIMOVEL']] = $r['descricao'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerenciar Visitas</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
/* Suave entrada */
.modal-open { animation: modalIn .25s ease-out forwards; }
@keyframes modalIn {
    from {opacity: 0; transform: scale(.95);}
    to {opacity: 1; transform: scale(1);}
}
</style>
</head>

<body class="font-serif text-gray-100 min-h-screen relative overflow-hidden">

<!-- Conteúdo -->
<div class="fade-in-page w-full px-4 py-10 flex flex-col items-center">
    <div class="bg-[#1f1f2f]/90 backdrop-blur-md p-8 rounded-3xl w-full max-w-7xl shadow-2xl space-y-6 card-dynamic border border-[#2a2a3f]/50">

        <h2 class="text-3xl font-bold tracking-wide text-center text-gray-200 title-glow">
            Gerenciar Visitas
        </h2>

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
                    <th class="p-3 border border-[#3a3a5a]">Imóvel</th>
                    <th class="p-3 border border-[#3a3a5a]">Data</th>
                    <th class="p-3 border border-[#3a3a5a]">Observações</th>
                    <th class="p-3 border border-[#3a3a5a]">Ações</th>
                </tr>
                </thead>
                <tbody>

                <?php if ($resultado->num_rows > 0): ?>
                    <?php while ($v = $resultado->fetch_assoc()): ?>
                        <tr class="bg-[#1f1f2f] hover:bg-[#2c2c44] transition">
                            <td class="p-3 border border-[#2a2a3f]"><?= $v['id'] ?></td>
                            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($v['cliente']) ?></td>
                            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($v['imovel']) ?></td>
                            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($v['data_visita']) ?></td>
                            <td class="p-3 border border-[#2a2a3f]"><?= nl2br(htmlspecialchars($v['observacoes'])) ?></td>

                            <td class="p-3 border border-[#2a2a3f] flex gap-2">
                                <button
                                    onclick="abrirModal(
                                        'editar',
                                        <?= $v['id'] ?>,
                                        <?= $v['imovel'] ?>,
                                        <?= $v['imovel'] ?>,
                                        '<?= $v['data_visita'] ?>',
                                        '<?= addslashes(htmlspecialchars($v['observacoes'])) ?>'
                                    )"
                                    class="px-3 py-1 rounded-md font-bold btn-glow shadow-md">
                                    Editar
                                </button>

                                <button
                                    onclick="abrirModal('excluir', <?= $v['id'] ?>)"
                                    class="px-3 py-1 rounded-md font-bold btn-glow shadow-md">
                                    Excluir
                                </button>
                            </td>
                        </tr>
                    <?php endwhile ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center p-3">Nenhuma visita encontrada.</td>
                    </tr>
                <?php endif ?>

                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Modal -->
<div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50">
    <div id="modal-box" class="bg-[#1f1f2f] p-6 rounded-2xl w-96 relative border border-[#2a2a3f]/50">
        <h2 id="modal-title" class="text-xl font-bold mb-4 title-glow">Título</h2>
        <div id="modal-body" class="space-y-2"></div>

        <div class="flex justify-end gap-2 mt-4">
            <button onclick="fecharModal()" class="py-2 px-4 rounded-xl font-bold btn-glow shadow-md">Cancelar</button>
            <button id="modal-confirm" class="py-2 px-4 rounded-xl font-bold btn-glow shadow-md">Confirmar</button>
        </div>

        <button onclick="fecharModal()" class="absolute top-2 right-2 text-gray-400 hover:text-white text-2xl">&times;</button>
    </div>
</div>

<script>
function abrirModal(tipo, id='', cliente='', imovel='', data='', obs='') {

    $('#modal').removeClass('hidden');
    $('#modal-box').addClass('modal-open');

    let clientes = `<?php foreach($clientes as $k=>$v){ echo "<option value='$k'>".htmlspecialchars($v)."</option>"; } ?>`;
    let imoveis = `<?php foreach($imoveis as $k=>$v){ echo "<option value='$k'>".htmlspecialchars($v)."</option>"; } ?>`;

    if (tipo === 'cadastrar') {
        $('#modal-title').text('Cadastrar Visita');
        $('#modal-body').html(`
            <select id="cliente" class="w-full p-2 rounded bg-[#2a2a3f] text-gray-200">${clientes}</select>
            <select id="imovel" class="w-full p-2 rounded bg-[#2a2a3f] text-gray-200">${imoveis}</select>
            <input type="date" id="data" class="w-full p-2 rounded bg-[#2a2a3f] text-gray-200">
            <textarea id="observacoes" placeholder="Observações" class="w-full p-2 rounded bg-[#2a2a3f] text-gray-200"></textarea>
        `);

        $('#modal-confirm').off('click').click(function () {
            $.post('', {
                acao: 'cadastrar',
                cliente: $('#cliente').val(),
                imovel: $('#imovel').val(),
                data: $('#data').val(),
                observacoes: $('#observacoes').val()
            }, respostaAjax);
        });

    } else if (tipo === 'editar') {

        $('#modal-title').text('Editar Visita');
        $('#modal-body').html(`
            <input type="hidden" id="id" value="${id}">
            <select id="cliente" class="w-full p-2 rounded bg-[#2a2a3f] text-gray-200">${clientes}</select>
            <select id="imovel" class="w-full p-2 rounded bg-[#2a2a3f] text-gray-200">${imoveis}</select>
            <input type="date" id="data" class="w-full p-2 rounded bg-[#2a2a3f] text-gray-200" value="${data}">
            <textarea id="observacoes" class="w-full p-2 rounded bg-[#2a2a3f] text-gray-200">${obs}</textarea>
        `);

        $('#cliente').val(cliente);
        $('#imovel').val(imovel);

        $('#modal-confirm').off('click').click(function () {
            $.post('', {
                acao: 'editar',
                id: id,
                cliente: $('#cliente').val(),
                imovel: $('#imovel').val(),
                data: $('#data').val(),
                observacoes: $('#observacoes').val()
            }, respostaAjax);
        });

    } else if (tipo === 'excluir') {
        $('#modal-title').text('Excluir Visita');
        $('#modal-body').html(`<p class="text-gray-300">Tem certeza que deseja excluir esta visita?</p>`);

        $('#modal-confirm').off('click').click(function () {
            $.post('', { acao: 'excluir', id: id }, respostaAjax);
        });
    }
}

function respostaAjax(res) {
    res = JSON.parse(res);
    alert(res.status === 'sucesso'
        ? 'Operação realizada com sucesso!'
        : 'Erro: ' + res.mensagem
    );
    if (res.status === 'sucesso') location.reload();
}

function fecharModal() {
    $('#modal').addClass('hidden');
    $('#modal-box').removeClass('modal-open');
}
</script>

</body>
</html>
