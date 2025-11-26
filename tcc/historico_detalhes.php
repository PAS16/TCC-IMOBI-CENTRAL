<?php
// historico_detalhes.php - Arquivo chamado via AJAX para exibir os dados no modal

session_start();
if (!isset($_SESSION['idUSUARIO'])) {
    http_response_code(403);
    echo "Acesso Negado.";
    exit();
}

include 'conexao.php'; // Certifique-se de que o caminho está correto
header('Content-Type: text/html; charset=utf-8');

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    echo "<p class='text-red-500'>ID de histórico inválido.</p>";
    exit();
}

$sql = "SELECT h.*, u.usuario 
        FROM HISTORICO h 
        LEFT JOIN USUARIO u ON h.usuario_idUSUARIO = u.idUSUARIO
        WHERE h.idHISTORICO = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$historico = $stmt->get_result()->fetch_assoc();

if (!$historico) {
    echo "<p>Registro de histórico não encontrado.</p>";
    exit();
}

// Decodificar JSON
$dados_anteriores = json_decode($historico['dados_anteriores'], true) ?? [];
$dados_atuais = json_decode($historico['dados_atuais'], true) ?? [];

// Início da formatação HTML
echo "<div class='space-y-4 text-gray-200'>";
echo "<p><strong>Usuário:</strong> " . htmlspecialchars($historico['usuario'] ?? 'N/A') . "</p>";
echo "<p><strong>Tabela:</strong> " . htmlspecialchars($historico['tabela']) . " | <strong>ID do Registro:</strong> " . htmlspecialchars($historico['registro_id']) . "</p>";
echo "<p><strong>Ação:</strong> <span class='font-bold text-lg'>" . htmlspecialchars($historico['acao']) . "</span> | <strong>Data/Hora:</strong> " . htmlspecialchars($historico['data_hora']) . "</p>";

// Se a ação for DELETE
if ($historico['acao'] === 'DELETE' && $dados_anteriores) {
    echo "<h3 class='text-lg font-semibold mt-4 text-yellow-400'>Dados Deletados (Conteúdo original)</h3>";
    echo "<div class='bg-[#2a2a3f] p-3 rounded-lg'>";
    foreach ($dados_anteriores as $campo => $valor) {
        echo "<p><strong>" . htmlspecialchars($campo) . ":</strong> " . htmlspecialchars($valor ?? 'NULO') . "</p>";
    }
    echo "</div>";
    
} 
// Se a ação for INSERT
else if ($historico['acao'] === 'INSERT' && $dados_atuais) {
    echo "<h3 class='text-lg font-semibold mt-4 text-green-400'>Dados Inseridos</h3>";
    echo "<div class='bg-[#2a2a3f] p-3 rounded-lg'>";
    foreach ($dados_atuais as $campo => $valor) {
        echo "<p><strong>" . htmlspecialchars($campo) . ":</strong> " . htmlspecialchars($valor ?? 'NULO') . "</p>";
    }
    echo "</div>";
}
// Se a ação for UPDATE
else if ($historico['acao'] === 'UPDATE' && $dados_anteriores) {
    echo "<h3 class='text-lg font-semibold mt-4'>Detalhes da Alteração</h3>";
    echo "<div class='overflow-x-auto'><table class='min-w-full divide-y divide-[#3a3a5a] text-sm'>";
    echo "<thead class='bg-[#3a3a5a]'>";
    echo "<tr><th class='p-2 text-left'>Campo</th><th class='p-2 text-left'>Valor Anterior</th><th class='p-2 text-left'>Valor Atual</th></tr>";
    echo "</thead>";
    echo "<tbody class='divide-y divide-[#2a2a3f]'>";
    
    // Obtém todos os campos que foram alterados (baseado em dados_anteriores)
    foreach ($dados_anteriores as $campo => $valor_anterior) {
        $valor_atual = $dados_atuais[$campo] ?? 'NULO'; 

        // Adiciona a formatação de cor se houve mudança (Valor Anterior != Valor Atual)
        $cor_atual = ($valor_anterior != $valor_atual) ? 'text-yellow-300' : 'text-gray-400';

        echo "<tr class='hover:bg-[#2c2c44]'>";
        echo "<td class='p-2 font-bold'>" . htmlspecialchars($campo) . "</td>";
        echo "<td class='p-2 text-red-400'>" . htmlspecialchars($valor_anterior) . "</td>";
        echo "<td class='p-2 " . $cor_atual . "'>" . htmlspecialchars($valor_atual) . "</td>";
        echo "</tr>";
    }

    echo "</tbody></table></div>";
}

echo "</div>";
?>