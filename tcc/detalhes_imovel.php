<?php
// === INÍCIO DO PROCESSAMENTO PHP ===

include 'conexao.php'; // Certifique-se de que o caminho 'conexao.php' está correto

if (!isset($conn) || $conn->connect_error) {
    die("Erro de conexão com o banco de dados: " . ($conn->connect_error ?? "Variável \$conn não definida"));
}

// O ID já está sendo capturado duas vezes no código original. Vou unificar a captura do ID.
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die("ID do imóvel inválido.");

// Busca dados do imóvel
$sql = "SELECT * FROM IMOVEL WHERE idIMOVEL = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$imovel = $stmt->get_result()->fetch_assoc();
if (!$imovel) die("Imóvel não encontrado.");

// Prepara valores para a calculadora JS
$valor_imovel_js = $imovel['valor'];
$valor_entrada_sugerida = number_format($imovel['valor'] * 0.20, 2, '.', ''); // Sugere 20% de entrada

// 2. BUSCA IMAGENS E VÍDEOS
$sqlMedia = "SELECT caminho FROM IMAGEM_IMOVEL WHERE IMOVEL_idIMOVEL = ?";
$stmtMedia = $conn->prepare($sqlMedia);

if ($stmtMedia === false) {
    error_log("Erro ao preparar busca de mídia: " . $conn->error);
    $medias = [];
} else {
    $stmtMedia->bind_param("i", $id);
    $stmtMedia->execute();
    
    $resultado = $stmtMedia->get_result();
    
    if ($resultado) {
        $medias = $resultado->fetch_all(MYSQLI_ASSOC);
    } else {
        error_log("Erro ao obter resultado da busca de mídia: " . $stmtMedia->error);
        $medias = [];
    }
    $stmtMedia->close();
}


// Página anterior
$pagina_anterior = $_SERVER['HTTP_REFERER'] ?? 'buscar_imoveis.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($imovel['titulo'] ?: $imovel['tipo']) ?> - Detalhes</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
/* Slider */
#slider { position: relative; overflow: hidden; border-radius: 1rem; }
.slide { display: none; width: 100%; height: 500px; }
.slide.active { display: block; }
.slide img, .slide video { width: 100%; height: 100%; object-fit: cover; border-radius: 1rem; }
#prev, #next {
    position: absolute; top: 50%; transform: translateY(-50%);
    background: rgba(0,0,0,0.5); color: white; padding: 0.5rem 1rem;
    cursor: pointer; border-radius: 0.5rem; font-weight: bold; z-index:10;
}
#prev { left: 1rem; }
#next { right: 1rem; }
/* Estilo extra para a tabela da calculadora */
#tabelaCompleta table { border-collapse: collapse; width: 100%; }
#tabelaCompleta thead { background-color: #374151; }
#tabelaCompleta th, #tabelaCompleta td { padding: 8px; border-bottom: 1px solid #374151; }
</style>
</head>
<body class="bg-gray-900 text-gray-100 font-sans">

<div class="max-w-7xl mx-auto px-4 py-8">
    <a href="<?= htmlspecialchars($pagina_anterior) ?>" 
        class="inline-block mb-6 px-5 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg shadow transition">
        ⬅ Voltar
    </a>

    <div id="slider" class="mb-10 shadow-lg">
        <?php foreach($medias as $index => $media): 
            $caminho_completo = $media['caminho'];
            
            // --- AJUSTE CRÍTICO DE CAMINHO ---
            // Se o caminho salvo no BD for 'uploads/imoveis/nome.jpg', e o diretório de arquivos for 'imoveis/uploads/imoveis',
            // precisamos adicionar o prefixo 'imoveis/' se ele não estiver lá.
            // Para simplificar e garantir que funcione, assumiremos que o prefixo necessário é 'imoveis/' se for um caminho local.
            if (!str_contains($caminho_completo, 'http') && !str_starts_with($caminho_completo, 'imoveis/')) {
                 // Concatena com o diretório RAIZ onde a pasta 'imoveis' está.
                 // Ajuste o caminho base conforme a necessidade do seu projeto:
                 // Se o arquivo detalhes.php está na RAIZ e o caminho de imagens é imoveis/uploads/imoveis/...
                $caminho_completo = "imoveis/" . $caminho_completo;
            }

            $ext = pathinfo($caminho_completo, PATHINFO_EXTENSION);
            $active = $index === 0 ? 'active' : '';
        ?>
        <div class="slide <?= $active ?>">
            <?php if(in_array(strtolower($ext), ['mp4','webm','ogg'])): ?>
                <video controls>
                    <source src="<?= htmlspecialchars($caminho_completo) ?>" type="video/<?= $ext ?>">
                    Seu navegador não suporta vídeo.
                </video>
            <?php else: ?>
                <img src="<?= htmlspecialchars($caminho_completo) ?>" alt="Mídia do imóvel">
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <div id="prev">&#10094;</div>
        <div id="next">&#10095;</div>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 bg-gray-800 p-6 rounded-2xl shadow-lg">
            <h2 class="text-2xl font-bold mb-4">
                <?= htmlspecialchars($imovel['titulo'] ?: $imovel['tipo']) ?> - 
                <span class="text-green-400">R$ <?= number_format($imovel['valor'],2,',','.') ?></span>
            </h2>
            <p class="mb-3"><strong>Status:</strong> 
                <span class="<?= $imovel['status']=='Disponivel' ? 'text-green-400' : 'text-red-400' ?>">
                    <?= htmlspecialchars($imovel['status']) ?>
                </span>
            </p>
            <p class="mb-3"><strong>Localização:</strong> 
                <?= htmlspecialchars($imovel['rua']) ?>, <?= htmlspecialchars($imovel['bairro']) ?>, 
                <?= htmlspecialchars($imovel['cidade']) ?>/<?= htmlspecialchars($imovel['estado']) ?>
            </p>
            <p class="mb-3"><strong>Quartos:</strong> <?= $imovel['qtd_quartos'] ?> |
                <strong>Banheiros:</strong> <?= $imovel['qtd_banheiro'] ?> |
                <strong>Vagas:</strong> <?= $imovel['qtd_vagas'] ?></p>
            <p class="mb-3"><strong>Negociável:</strong> <?= htmlspecialchars($imovel['negociavel'] ?? 'Não') ?> |
                <strong>Financiável:</strong> <?= htmlspecialchars($imovel['financiavel'] ?? 'Não') ?></p>
            <p class="mb-3"><strong>Descrição:</strong><br>
                <?= nl2br(htmlspecialchars($imovel['descricao'])) ?></p>
        </div>

        <div class="bg-gray-800 p-6 rounded-2xl shadow-lg">
            
            <h3 class="text-xl font-semibold mb-4">Solicitar Contato</h3>
            <form method="POST" action="enviar_contato.php" class="space-y-3">
                <input type="hidden" name="id_imovel" value="<?= $imovel['idIMOVEL'] ?>">
                <input type="text" name="nome" placeholder="Seu nome" required
                        class="w-full p-3 rounded-lg bg-gray-700 text-white focus:ring-2 focus:ring-green-500 outline-none">
                <input type="tel" name="telefone" placeholder="Telefone" required
                        class="w-full p-3 rounded-lg bg-gray-700 text-white focus:ring-2 focus:ring-green-500 outline-none">
                <input type="email" name="email" placeholder="Email" required
                        class="w-full p-3 rounded-lg bg-gray-700 text-white focus:ring-2 focus:ring-green-500 outline-none">
                <textarea name="mensagem" rows="4"
                                class="w-full p-3 rounded-lg bg-gray-700 text-white focus:ring-2 focus:ring-green-500 outline-none">Tenho interesse neste imóvel.</textarea>
                <button type="submit"
                        class="w-full py-3 bg-green-600 hover:bg-green-500 font-bold rounded-lg shadow-lg transition">
                        Enviar Solicitação
                </button>
            </form>
            
            <div class="mt-8 pt-6 border-t border-gray-700">
                <h3 class="text-xl font-semibold mb-4 text-center">Simulador de Financiamento</h3>
                <div id="calculator-form" class="space-y-4">
                    <div>
                        <label class="block mb-1 text-sm">Valor do Imóvel (R$)</label>
                        <input type="number" id="valorImovel" value="<?= number_format($valor_imovel_js, 2, '.', '') ?>" 
                            min="1" readonly class="w-full p-2 rounded-lg bg-gray-700 border-gray-600 focus:outline-none cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm">Valor da Entrada (R$)</label>
                        <input type="number" id="valorEntrada" value="<?= $valor_entrada_sugerida ?>" 
                            min="0" class="w-full p-2 rounded-lg bg-gray-700 focus:ring-blue-500 focus:ring-2 outline-none">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm">Taxa de Juros Anual (%)</label>
                        <input type="number" id="taxaJurosAnual" value="9.5" step="0.01" min="0.01" max="25"
                            class="w-full p-2 rounded-lg bg-gray-700 focus:ring-blue-500 focus:ring-2 outline-none">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm">Prazo (Meses)</label>
                        <input type="number" id="prazoMeses" value="360" min="12" step="12" max="480"
                            class="w-full p-2 rounded-lg bg-gray-700 focus:ring-blue-500 focus:ring-2 outline-none">
                    </div>

                    <button onclick="calcularFinanciamento()" class="w-full py-2 mt-4 rounded-lg font-bold bg-blue-600 hover:bg-blue-700 transition shadow-lg">
                        Calcular Parcelas
                    </button>
                </div>

                <div id="resultado" class="mt-4 pt-4 border-t border-gray-700 hidden">
                    <h4 class="text-lg font-semibold mb-2">Resumo</h4>
                    <div class="space-y-1 text-sm">
                        <p>Financiado: <span id="resPrincipal" class="font-bold text-green-400"></span></p>
                        <p>Parcela Fixa: <span id="resParcela" class="font-bold text-yellow-400"></span></p>
                    </div>
                    <button onclick="$('#tabelaCompleta').toggleClass('hidden')" class="text-xs text-gray-400 hover:text-white mt-2">
                        Ver Amortização Completa (Tabela Price)
                    </button>

                    <div id="tabelaCompleta" class="overflow-y-auto max-h-48 rounded-lg mt-3 hidden">
                        <table class="min-w-full text-xs text-left">
                            <thead class="bg-gray-700 sticky top-0">
                                <tr>
                                    <th class="p-1">#</th>
                                    <th class="p-1">Juros</th>
                                    <th class="p-1">Amort.</th>
                                    <th class="p-1">Saldo Dev.</th>
                                </tr>
                            </thead>
                            <tbody id="tabelaAmortizacao" class="divide-y divide-gray-700"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            </div>
    </div>
</div>

<script>
// --- SLIDER JS ---
let current = 0;
const slides = $('.slide');
const total = slides.length;

function showSlide(index){
    slides.removeClass('active');
    slides.eq(index).addClass('active');
}
$('#next').click(()=>{ current = (current+1)%total; showSlide(current); });
$('#prev').click(()=>{ current = (current-1+total)%total; showSlide(current); });

// Garante que o slider comece no primeiro item ao carregar
if(total > 0) showSlide(0);

// --- CALCULATOR JS (Lógica da Tabela Price) ---

// ... (Restante do JavaScript da calculadora é o mesmo)
const formatarMoeda = (valor) => {
    if (isNaN(valor)) return 'R$ 0,00';
    return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

function calcularFinanciamento() {
    // 1. Coleta e conversão de Inputs
    const valorImovel = parseFloat($('#valorImovel').val());
    const valorEntrada = parseFloat($('#valorEntrada').val());
    const taxaJurosAnual = parseFloat($('#taxaJurosAnual').val());
    const prazoMeses = parseInt($('#prazoMeses').val());

    if (isNaN(valorImovel) || isNaN(valorEntrada) || isNaN(taxaJurosAnual) || isNaN(prazoMeses) || prazoMeses <= 0) {
        // Substituído alert() por log no console, conforme diretrizes
        console.error("Erro: Preencha todos os campos da calculadora com valores válidos.");
        return;
    }
    
    if (valorEntrada >= valorImovel) {
        console.error("Erro: O valor da entrada não pode ser maior ou igual ao valor do imóvel.");
        $('#resultado').addClass('hidden');
        return;
    }

    // 2. Cálculos de Base
    const principal = valorImovel - valorEntrada;
    const taxaMensal = (taxaJurosAnual / 100) / 12; // Taxa i
    const n = prazoMeses; // Número de períodos

    // 3. Fórmula da Tabela Price (Parcela Constante)
    let parcelaFixa;
    
    if (taxaMensal === 0) {
        // Se a taxa for zero, a parcela é apenas a amortização
        parcelaFixa = principal / n;
    } else {
        const fator = Math.pow(1 + taxaMensal, n);
        parcelaFixa = principal * (taxaMensal * fator) / (fator - 1); 
    }
    
    // Arredondamento da parcela para duas casas decimais
    parcelaFixa = Math.round(parcelaFixa * 100) / 100;

    // 4. Montar a Tabela de Amortização
    let saldoDevedor = principal;
    let tabelaHTML = '';

    for (let i = 1; i <= n; i++) {
        const juros = saldoDevedor * taxaMensal;
        let amortizacao = parcelaFixa - juros;
        
        // Aplica o ajuste de arredondamento na última parcela para zerar o saldo
        // Usamos um pequeno epsilon (0.01, 0.1, etc.) ou ajuste direto
        if (i === n) {
            // Garante que a última amortização zere o saldo
            amortizacao = saldoDevedor; 
            parcelaFixa = juros + amortizacao; // Recalcula a última parcela
            saldoDevedor = 0;
        } else {
            saldoDevedor -= amortizacao;
        }
        
        // Garante que o saldo devedor não fique negativo devido a erros de ponto flutuante
        if (saldoDevedor < 0.01 && saldoDevedor > -0.01) {
            saldoDevedor = 0;
        }

        tabelaHTML += `
            <tr class="hover:bg-gray-700">
                <td class="p-1">${i}</td>
                <td class="p-1 text-red-300">${formatarMoeda(juros)}</td>
                <td class="p-1 text-green-300">${formatarMoeda(amortizacao)}</td>
                <td class="p-1">${formatarMoeda(saldoDevedor)}</td>
            </tr>
        `;
    }

    // 5. Exibir Resultados
    $('#resPrincipal').text(formatarMoeda(principal));
    $('#resParcela').text(formatarMoeda(parcelaFixa));
    $('#tabelaAmortizacao').html(tabelaHTML);
    
    $('#resultado').removeClass('hidden');
}
</script>
</body>
</html>