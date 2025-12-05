<?php
$pagina_atual = 'buscar_imoveis';
include 'navbar.php';
include('conexao.php');

// --- FILTROS ---
$cidade     = $_GET['cidade']     ?? '';
$tipo       = $_GET['tipo']       ?? '';
$preco_min  = isset($_GET['preco_min'])  ? floatval($_GET['preco_min']) : null;
$preco_max  = isset($_GET['preco_max'])  ? floatval($_GET['preco_max']) : null;
$quartos    = isset($_GET['quartos'])    ? intval($_GET['quartos']) : null;
$banheiros  = isset($_GET['banheiros'])  ? intval($_GET['banheiros']) : null;
$vagas      = isset($_GET['vagas'])      ? intval($_GET['vagas']) : null;

// --- QUERY PRINCIPAL ---
$query = "SELECT I.*, 
                 CONCAT(MIN(IMG.caminho), '/', MIN(IMG.nome_original)) AS caminho
          FROM IMOVEL I
          LEFT JOIN IMAGEM_IMOVEL IMG 
            ON IMG.IMOVEL_idIMOVEL = I.idIMOVEL
          WHERE I.status = 'Disponivel'";

$params = [];
$types = "";

// --- FILTROS ---
if (!empty($cidade)) {
    $query .= " AND I.cidade = ?";
    $params[] = $cidade;
    $types .= "s";
}
if (!empty($tipo)) {
    $query .= " AND I.tipo = ?";
    $params[] = $tipo;
    $types .= "s";
}
if ($preco_min !== null) {
    $query .= " AND I.valor >= ?";
    $params[] = $preco_min;
    $types .= "d";
}
if ($preco_max !== null) {
    $query .= " AND I.valor <= ?";
    $params[] = $preco_max;
    $types .= "d";
}
if ($quartos !== null) {
    $query .= " AND I.qtd_quartos >= ?";
    $params[] = $quartos;
    $types .= "i";
}
if ($banheiros !== null) {
    $query .= " AND I.qtd_banheiro >= ?";
    $params[] = $banheiros;
    $types .= "i";
}
if ($vagas !== null) {
    $query .= " AND I.qtd_vagas >= ?";
    $params[] = $vagas;
    $types .= "i";
}

$query .= " GROUP BY I.idIMOVEL ORDER BY I.valor ASC";

// EXECUÇÃO
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Erro ao preparar query: " . $conn->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// --- FUNDO POR CIDADE ---
$bg_map = [
    'Praia Grande' => 'imagem/planodefundopraiagrande.jpg',
    'Mongaguá'     => 'imagem/planodefundomongagua.jpg',
    'Itanhaém'     => 'imagem/planodefundoitanhaem.jpg',
];
$bg_img = 'imagem/FUNDO.jpeg';

if (!empty($cidade)) {
    foreach ($bg_map as $nome_cidade => $img) {
        if (stripos($cidade, $nome_cidade) !== false) {
            $bg_img = $img;
            break;
        }
    }
}

// --- IMG PADRÃO (fallback) ---
$fallback_url = "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSOAyKpCW4nx0jZoJEVRtlLiC8sYEdO5gOF0g&s";

// --- FUNÇÃO AUXILIAR ---
function caminho_valido_para_exibir($caminho_relativo) {
    $caminho_relativo = trim(str_replace('\\', '/', $caminho_relativo));
    if ($caminho_relativo === '') return false;

    if (preg_match('#^https?://#i', $caminho_relativo)) {
        return $caminho_relativo;
    }

    $caminho_relativo_sem_barra = ltrim($caminho_relativo, '/');
    $physical = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/' . $caminho_relativo_sem_barra;

    if (file_exists($physical)) {
        return $caminho_relativo_sem_barra;
    }

    return false;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Imóveis Disponíveis</title>
<script src="https://cdn.tailwindcss.com"></script>

<style>
@keyframes fadeUp { 
    from { transform: translateY(40px); opacity: 0; } 
    to   { transform: translateY(0);   opacity: 1; }
}
.animate-fadeUp { animation: fadeUp 0.8s ease forwards; }
#painelFiltro { display: none; }
#abrirFiltro:checked ~ #painelFiltro { display: flex; }
.close-btn {
    position: absolute; top: 12px; right: 14px;
    background-color: rgba(255,255,255,0.15);
    border-radius: 50%;
    width: 36px; height: 36px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
}
.close-btn:hover {
    background-color: rgba(255,255,255,0.3);
    transform: scale(1.1);
}
</style>
</head>

<body class="text-gray-100 font-sans p-6 min-h-screen"
      style="background-image: url('<?= $bg_img ?>'); background-size: cover; background-position: center; background-attachment: fixed;">

<div class="bg-black/70 min-h-screen p-6">
    <h1 class="text-4xl sm:text-5xl font-semibold mb-6 text-center">Imóveis Disponíveis</h1>

    <input type="checkbox" id="abrirFiltro" class="hidden">

    <div class="flex justify-center mb-10">
        <label for="abrirFiltro" class="bg-blue-500/40 hover:bg-blue-500/60 text-white backdrop-blur-md px-8 py-3 rounded-xl shadow-md text-lg font-medium transition-all duration-300 cursor-pointer">
            🔍 Filtrar Imóveis
        </label>
    </div>

    <!-- FILTRO -->
    <div id="painelFiltro" class="fixed inset-0 bg-black/60 backdrop-blur-md z-40 flex justify-center items-start pt-20 animate-fadeUp">
        <div class="relative bg-gray-800/80 backdrop-blur-2xl p-6 rounded-2xl w-11/12 max-w-md shadow-2xl border border-gray-700">
            <label for="abrirFiltro" class="close-btn">✖</label>

            <h2 class="text-xl font-bold mb-4 text-center text-white/90">Busca Filtrada</h2>

            <form method="GET" action="buscar_imoveis.php">

                <div class="mb-4">
                    <label class="block mb-1 text-gray-300">Localização</label>
                    <input type="text" name="cidade" placeholder="Digite uma cidade"
                           value="<?= htmlspecialchars($cidade) ?>"
                           class="w-full p-2 rounded-lg bg-gray-700/60 text-white">
                </div>

                <div class="mb-4 flex gap-2">
                    <?php 
                    $tipos = ['Casa','Apartamento','Alugar'];
                    foreach($tipos as $t): 
                        $checked = ($tipo == $t) ? 'checked' : '';
                        $active = ($tipo == $t) ? 'bg-blue-500/60 text-white' : 'bg-gray-700/60 text-white hover:bg-gray-600/60';
                    ?>
                        <label class="flex-1 cursor-pointer p-3 rounded-lg text-center transition <?= $active ?>">
                            <input type="radio" name="tipo" value="<?= $t ?>" class="hidden" <?= $checked ?>>
                            <?= $t ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="mb-4 flex gap-2">
                    <input type="number" name="preco_min" placeholder="Preço mínimo"
                        value="<?= htmlspecialchars($preco_min) ?>"
                        class="w-1/2 p-2 rounded-lg bg-gray-700/60 text-white">

                    <input type="number" name="preco_max" placeholder="Preço máximo"
                        value="<?= htmlspecialchars($preco_max) ?>"
                        class="w-1/2 p-2 rounded-lg bg-gray-700/60 text-white">
                </div>

                <div class="mb-4 grid grid-cols-3 gap-2">
                    <input type="number" name="quartos" placeholder="Quartos" value="<?= htmlspecialchars($quartos) ?>" class="p-2 rounded-lg bg-gray-700/60 text-white">
                    <input type="number" name="banheiros" placeholder="Banheiros" value="<?= htmlspecialchars($banheiros) ?>" class="p-2 rounded-lg bg-gray-700/60 text-white">
                    <input type="number" name="vagas" placeholder="Vagas" value="<?= htmlspecialchars($vagas) ?>" class="p-2 rounded-lg bg-gray-700/60 text-white">
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-500/60 hover:bg-blue-500/80 rounded-lg font-semibold">Pesquisar</button>
                    <a href="buscar_imoveis.php" class="flex-1 px-4 py-2 bg-gray-600/60 hover:bg-gray-600/80 rounded-lg text-center">Resetar</a>
                </div>

            </form>
        </div>
    </div>

    <!-- LISTAGEM -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 mt-10">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php $delay = 0; ?>
        <?php while ($row = $result->fetch_assoc()): ?>

            <?php
            // --------------- IMAGEM FINAL ----------------
            $attempts = [];

            if (!empty($row['caminho'])) {
                $attempts[] = $row['caminho'];
            }

            $attempts[] = 'uploads/imoveis/' . $row['idIMOVEL'] . '.jpg';

            $attempts[] = $fallback_url;

            $caminho_final = $fallback_url;

            foreach ($attempts as $c) {
                $valid = caminho_valido_para_exibir($c);
                if ($valid !== false) {
                    $caminho_final = $valid;
                    break;
                }
            }

            if (!preg_match('#^https?://#i', $caminho_final)) {
                $caminho_final = '/' . ltrim($caminho_final, '/');
            }

            // --------------- DESCRIÇÃO CURTA ----------------
            $descricao_curta = strlen($row['descricao']) > 120 
                ? substr($row['descricao'], 0, 120) . "..." 
                : $row['descricao'];
            ?>

            <a href="detalhes_imovel.php?id=<?= $row['idIMOVEL'] ?>" 
               class="bg-gray-800/90 rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-300 hover:-translate-y-2 hover:shadow-3xl hover:bg-gray-700 animate-fadeUp" 
               style="animation-delay:<?= $delay ?>s;">

                <img src="<?= htmlspecialchars($caminho_final) ?>" 
                     alt="Imagem do Imóvel"
                     class="w-full h-44 object-cover transition-transform duration-300 hover:scale-105 border-b-2 border-gray-600">

                <div class="p-5">
                    <h3 class="text-xl font-bold text-white mb-1">
                        <?= htmlspecialchars($row['titulo'] ?: "Imóvel #{$row['idIMOVEL']}") ?>
                    </h3>

                    <p class="text-blue-300 text-lg font-semibold mb-2">
                        R$ <?= number_format($row['valor'],2,',','.') ?>
                    </p>

                    <p class="text-gray-300 text-sm mb-1">
                        <strong>Endereço:</strong> 
                        <?= htmlspecialchars($row['rua']) ?>, 
                        <?= htmlspecialchars($row['bairro']) ?>, 
                        <?= htmlspecialchars($row['cidade']) ?>
                    </p>

                    <p class="text-gray-300 text-sm mb-1">
                        <strong>Descrição:</strong> <?= htmlspecialchars($descricao_curta) ?>
                    </p>

                    <p class="text-gray-300 text-sm mt-1">
                        <strong>Quartos:</strong> <?= $row['qtd_quartos'] ?> |
                        <strong>Banheiros:</strong> <?= $row['qtd_banheiro'] ?> |
                        <strong>Vagas:</strong> <?= $row['qtd_vagas'] ?>
                    </p>
                </div>
            </a>

            <?php $delay += 0.1; ?>
        <?php endwhile; ?>

    <?php else: ?>
        <p class="col-span-full text-center text-gray-300">Nenhum imóvel encontrado.</p>
    <?php endif; ?>
    </div>
</div>

</body>
</html>
