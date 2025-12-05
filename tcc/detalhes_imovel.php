<?php
session_start();
include 'conexao.php'; // ajuste se necessário

if (!isset($conn) || $conn->connect_error) {
    echo "Erro de conexão com o banco de dados.";
    exit;
}

// ID do imóvel
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "ID do imóvel inválido.";
    exit;
}

// Busca o imóvel
$sql = "SELECT * FROM IMOVEL WHERE idIMOVEL = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log("Erro prepare IMOVEL: " . $conn->error);
    echo "Erro interno.";
    exit;
}
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$imovel = $res->fetch_assoc();
$stmt->close();

if (!$imovel) {
    echo "Imóvel não encontrado.";
    exit;
}

// Fundo por cidade (mesma lógica do buscar_imoveis)
$bg_map = [
    'Praia Grande' => 'imagem/planodefundopraiagrande.jpg',
    'Mongaguá'     => 'imagem/planodefundomongagua.jpg',
    'Itanhaém'     => 'imagem/planodefundoitanhaem.jpg',
];
$bg_img = 'imagem/FUNDO.jpeg';
if (!empty($imovel['cidade'])) {
    foreach ($bg_map as $nome_cidade => $img) {
        if (stripos($imovel['cidade'], $nome_cidade) !== false) {
            $bg_img = $img;
            break;
        }
    }
}

// Buscar mídias
$sqlMedia = "SELECT caminho FROM IMAGEM_IMOVEL WHERE IMOVEL_idIMOVEL = ?";
$stmtMedia = $conn->prepare($sqlMedia);
$medias = [];
if ($stmtMedia !== false) {
    $stmtMedia->bind_param("i", $id);
    $stmtMedia->execute();
    $resultMedia = $stmtMedia->get_result();
    if ($resultMedia) {
        while ($m = $resultMedia->fetch_assoc()) {
            $medias[] = $m['caminho'];
        }
    }
    $stmtMedia->close();
} else {
    error_log("Erro prepare IMAGEM_IMOVEL: " . $conn->error);
}

// FALLBACKS (3 imagens + 1 vídeo)
$fallback_images = [
    "https://images.pexels.com/photos/259588/pexels-photo-259588.jpeg",
    "https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg",
    "https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg"
];
$fallback_video = "https://www.w3schools.com/html/mov_bbb.mp4";

// Monta slides
$slides = [];
if (!empty($medias)) {
    foreach ($medias as $m) {
        // normaliza caminhos locais: se não começa com http, usamos relativo direto
        $slides[] = ['tipo' => 'img', 'src' => $m];
    }
} else {
    foreach ($fallback_images as $img) $slides[] = ['tipo' => 'img', 'src' => $img];
    $slides[] = ['tipo' => 'video', 'src' => $fallback_video];
}

// Página anterior
$pagina_anterior = filter_var($_SERVER['HTTP_REFERER'] ?? 'buscar_imoveis.php', FILTER_SANITIZE_URL);

// Formata valores para JS
$valor_imovel_js = floatval($imovel['valor'] ?? 0);
$valor_entrada_sugerida = number_format($valor_imovel_js * 0.20, 2, '.', '');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($imovel['titulo'] ?: $imovel['tipo'] ?: "Imóvel #{$imovel['idIMOVEL']}") ?> - Detalhes</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
@keyframes fadeUp { from { transform: translateY(24px); opacity: 0 } to { transform: translateY(0); opacity: 1 } }
.animate-fadeUp { animation: fadeUp 0.6s ease forwards; }
.slider-btn { background: rgba(255,255,255,0.06); padding: 0.5rem; border-radius: 999px; backdrop-filter: blur(6px); }
.slider-btn:hover { background: rgba(255,255,255,0.12); transform: scale(1.03); }
.dot { width:10px; height:10px; border-radius:999px; background: rgba(255,255,255,0.12); }
.dot.active { background: white; }
</style>
</head>
<body class="text-gray-100 font-sans min-h-screen"
      style="background-image: url('<?= $bg_img ?>'); background-size: cover; background-position: center; background-attachment: fixed;">

<div class="bg-black/70 min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
        <a href="<?= htmlspecialchars($pagina_anterior) ?>" class="inline-block mb-6 px-5 py-2 bg-gray-800/70 hover:bg-gray-800 rounded-xl shadow text-white">⬅ Voltar</a>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- SLIDER -->
            <div class="lg:col-span-2 bg-gray-800/80 p-4 rounded-2xl shadow-2xl animate-fadeUp">
                <div class="relative rounded-xl overflow-hidden">
                    <div id="slider" class="w-full h-80 md:h-96 bg-black">
                        <?php foreach ($slides as $i => $s): 
                            $ext = pathinfo(parse_url($s['src'], PHP_URL_PATH), PATHINFO_EXTENSION);
                            $isVideo = in_array(strtolower($ext), ['mp4','webm','ogg']) || $s['tipo'] === 'video';
                        ?>
                            <div class="slide <?= $i===0 ? 'block' : 'hidden' ?> w-full h-full" data-index="<?= $i ?>">
                                <?php if ($isVideo): ?>
                                    <video controls playsinline preload="metadata" class="w-full h-full object-cover">
                                        <source src="<?= htmlspecialchars($s['src']) ?>">
                                        Seu navegador não suporta vídeo.
                                    </video>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($s['src']) ?>" alt="Mídia <?= $i+1 ?>" class="w-full h-full object-cover">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- controls -->
                    <div class="absolute inset-y-0 left-4 flex items-center">
                        <button id="prev" class="slider-btn text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg></button>
                    </div>
                    <div class="absolute inset-y-0 right-4 flex items-center">
                        <button id="next" class="slider-btn text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg></button>
                    </div>

                    <!-- dots -->
                    <div class="absolute left-0 right-0 bottom-4 flex justify-center gap-3">
                        <?php foreach ($slides as $i => $_): ?>
                            <div class="dot <?= $i===0 ? 'active' : '' ?>" data-dot="<?= $i ?>"></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Detalhes principais -->
                <div class="p-5 mt-6 bg-gray-900/50 rounded-xl border border-gray-800">
                    <h1 class="text-2xl font-bold mb-2"><?= htmlspecialchars($imovel['titulo'] ?: $imovel['tipo'] ?: "Imóvel #{$imovel['idIMOVEL']}") ?></h1>
                    <div class="text-blue-300 text-xl font-semibold mb-3">R$ <?= number_format(floatval($imovel['valor']),2,',','.') ?></div>

                    <div class="text-gray-300 mb-3">
                        <strong>Endereço:</strong> <?= htmlspecialchars($imovel['rua']) ?>, <?= htmlspecialchars($imovel['bairro']) ?>, <?= htmlspecialchars($imovel['cidade']) ?> / <?= htmlspecialchars($imovel['estado']) ?>
                    </div>

                    <div class="flex gap-4 text-gray-300 mb-4">
                        <div><strong>Quartos:</strong> <?= intval($imovel['qtd_quartos']) ?></div>
                        <div><strong>Banheiros:</strong> <?= intval($imovel['qtd_banheiro']) ?></div>
                        <div><strong>Vagas:</strong> <?= intval($imovel['qtd_vagas']) ?></div>
                    </div>

                    <div class="text-gray-300 leading-relaxed">
                        <strong>Descrição:</strong>
                        <div class="mt-2 bg-gray-800/40 p-4 rounded"><?= nl2br(htmlspecialchars($imovel['descricao'])) ?></div>
                    </div>
                </div>
            </div>

            <!-- LATERAL: contato + simulador -->
            <div class="bg-gray-800/80 p-6 rounded-2xl shadow-2xl animate-fadeUp">
                <h2 class="text-xl font-semibold mb-4">Solicitar Contato</h2>
                <form method="POST" action="enviar_contato.php" class="space-y-3">
                    <input type="hidden" name="id_imovel" value="<?= intval($imovel['idIMOVEL']) ?>">
                    <input name="nome" placeholder="Seu nome" required class="w-full p-3 rounded bg-gray-700/60 text-white outline-none">
                    <input name="telefone" placeholder="Telefone" required class="w-full p-3 rounded bg-gray-700/60 text-white outline-none">
                    <input name="email" type="email" placeholder="Email" required class="w-full p-3 rounded bg-gray-700/60 text-white outline-none">
                    <textarea name="mensagem" rows="3" class="w-full p-3 rounded bg-gray-700/60 text-white outline-none">Tenho interesse neste imóvel.</textarea>
                    <button type="submit" class="w-full py-3 bg-blue-500/60 hover:bg-blue-500 rounded-md font-semibold">Enviar Solicitação</button>
                </form>

                <hr class="my-5 border-gray-700">

                <h3 class="text-lg font-semibold mb-3">Simulador de Financiamento</h3>

                <label class="text-sm text-gray-300">Valor do Imóvel (R$)</label>
                <input id="valorImovel" readonly value="<?= number_format($valor_imovel_js,2,'.','') ?>" class="w-full p-2 rounded bg-gray-700/60 text-white mb-3">

                <label class="text-sm text-gray-300">Valor da Entrada (R$)</label>
                <input id="valorEntrada" value="<?= $valor_entrada_sugerida ?>" class="w-full p-2 rounded bg-gray-700/60 text-white mb-3">

                <label class="text-sm text-gray-300">Taxa Anual (%)</label>
                <input id="taxaJurosAnual" value="9.5" step="0.01" class="w-full p-2 rounded bg-gray-700/60 text-white mb-3">

                <label class="text-sm text-gray-300">Prazo (meses)</label>
                <input id="prazoMeses" value="360" class="w-full p-2 rounded bg-gray-700/60 text-white mb-3">

                <button onclick="calcularFinanciamento()" type="button" class="w-full py-2 bg-green-600 hover:bg-green-500 rounded-md font-semibold">Calcular Parcelas</button>

                <div id="resultado" class="mt-4 hidden">
                    <h4 class="font-semibold">Resumo</h4>
                    <p>Financiado: <span id="resPrincipal" class="font-bold text-green-300"></span></p>
                    <p>Parcela Fixa: <span id="resParcela" class="font-bold text-yellow-300"></span></p>
                    <button onclick="document.getElementById('tabelaCompleta').classList.toggle('hidden')" class="text-sm text-gray-400 mt-2">Ver Amortização</button>

                    <div id="tabelaCompleta" class="hidden mt-3 max-h-48 overflow-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-800 sticky top-0"><tr><th class="p-2">#</th><th class="p-2">Juros</th><th class="p-2">Amort.</th><th class="p-2">Saldo</th></tr></thead>
                            <tbody id="tabelaAmortizacao" class="divide-y divide-gray-700"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// Slider logic
const slides = Array.from(document.querySelectorAll('#slider .slide'));
const dots  = Array.from(document.querySelectorAll('[data-dot]'));
let current = 0;

function showSlide(i) {
    if (!slides.length) return;
    slides.forEach(s => s.classList.add('hidden'));
    dots.forEach(d => d.classList.remove('active'));
    current = (i + slides.length) % slides.length;
    slides[current].classList.remove('hidden');
    dots[current].classList.add('active');
}
document.getElementById('next').addEventListener('click', ()=> showSlide(current+1));
document.getElementById('prev').addEventListener('click', ()=> showSlide(current-1));
dots.forEach(d => d.addEventListener('click', (e)=> showSlide(parseInt(e.currentTarget.getAttribute('data-dot')))));

// init
showSlide(0);

// Finance calculator
const formatarMoeda = (v) => {
    return v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

function calcularFinanciamento(){
    const valorImovel = parseFloat(document.getElementById('valorImovel').value) || 0;
    const valorEntrada = parseFloat(document.getElementById('valorEntrada').value) || 0;
    const taxaAnual = parseFloat(document.getElementById('taxaJurosAnual').value) || 0;
    const prazo = parseInt(document.getElementById('prazoMeses').value) || 0;

    if (valorImovel <= 0 || prazo <= 0) return console.error("Valores inválidos");
    if (valorEntrada >= valorImovel) {
        console.error("Entrada >= valor");
        document.getElementById('resultado').classList.add('hidden');
        return;
    }

    const principal = valorImovel - valorEntrada;
    const i = (taxaAnual/100)/12;
    const n = prazo;
    let parcela;
    if (i === 0) parcela = principal / n;
    else {
        const fator = Math.pow(1+i, n);
        parcela = principal * (i * fator) / (fator - 1);
    }
    parcela = Math.round(parcela*100)/100;

    // tabela
    let saldo = principal;
    let tbody = '';
    for (let k=1;k<=n;k++){
        const juros = saldo * i;
        let amort = parcela - juros;
        if (k === n) {
            amort = saldo;
            parcela = juros + amort;
            saldo = 0;
        } else {
            saldo -= amort;
            if (saldo < 0.01) saldo = 0;
        }
        tbody += `<tr class="hover:bg-gray-800"><td class="p-2">${k}</td><td class="p-2 text-red-300">${formatarMoeda(juros)}</td><td class="p-2 text-green-300">${formatarMoeda(amort)}</td><td class="p-2">${formatarMoeda(saldo)}</td></tr>`;
        if (k>500) break;
    }

    document.getElementById('resPrincipal').textContent = formatarMoeda(principal);
    document.getElementById('resParcela').textContent = formatarMoeda(parcela);
    document.getElementById('tabelaAmortizacao').innerHTML = tbody;
    document.getElementById('resultado').classList.remove('hidden');
}
</script>
</body>
</html>
