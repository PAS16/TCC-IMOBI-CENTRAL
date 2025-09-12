<?php
include 'conexao.php';

if (!isset($conn) || $conn->connect_error) {
    die("Erro de conexão com o banco de dados: " . ($conn->connect_error ?? "Variável \$conn não definida"));
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die("ID do imóvel inválido.");

// Busca dados do imóvel (sem informações do proprietário)
$sql = "SELECT * FROM IMOVEL WHERE idIMOVEL = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$imovel = $stmt->get_result()->fetch_assoc();

if (!$imovel) die("Imóvel não encontrado.");

// Busca imagens
$sqlImg = "SELECT caminho FROM IMAGEM_IMOVEL WHERE IMOVEL_idIMOVEL = ?";
$stmtImg = $conn->prepare($sqlImg);
$stmtImg->bind_param("i", $id);
$stmtImg->execute();
$imgs = $stmtImg->get_result()->fetch_all(MYSQLI_ASSOC);

// Imagens padrão se não houver
if (empty($imgs)) {
    $imgs = [["caminho" => "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80"]];
}

// Página anterior
$pagina_anterior = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'buscar_imoveis.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($imovel['tipo']) ?> - Detalhes</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
body { background:#1f1f1f; color:#fff; font-family:'Inter',sans-serif; }
.container { max-width:1200px; margin:30px auto; padding:20px; }
.galeria { display:grid; grid-template-columns: repeat(auto-fit,minmax(300px,1fr)); gap:15px; margin-bottom:30px; }
.galeria img { width:100%; height:250px; object-fit:cover; border-radius:12px; transition: transform 0.3s; }
.galeria img:hover { transform: scale(1.05); }

.info { display:flex; flex-wrap:wrap; gap:30px; }
.info-main { flex:1; min-width:300px; background:#2a2a2a; padding:20px; border-radius:12px; }
.info-main h2 { font-size:1.8rem; margin-bottom:15px; }
.info-main p { margin-bottom:8px; line-height:1.5; }

.sidebar { flex:0 0 350px; background:#2a2a2a; padding:20px; border-radius:12px; }
.sidebar h3 { font-size:1.5rem; margin-bottom:15px; }
.sidebar form input, .sidebar form textarea, .sidebar form button { width:100%; margin-bottom:12px; padding:12px; border-radius:8px; border:none; font-size:1rem; }
.sidebar form input, .sidebar form textarea { background:#1f1f1f; color:#fff; }
.sidebar form button { background:#3b82f6; color:#fff; font-weight:600; cursor:pointer; transition: background 0.3s; }
.sidebar form button:hover { background:#2563eb; }

.voltar { display:inline-block; margin-top:20px; padding:10px 20px; background:#444; color:#fff; text-decoration:none; border-radius:8px; }
.voltar:hover { background:#666; }
</style>
</head>
<body>

<div class="container">
    <!-- Botão de voltar para página anterior -->
    <a href="<?= htmlspecialchars($pagina_anterior) ?>" class="voltar">⬅ Voltar</a>

    <div class="galeria">
        <?php foreach($imgs as $img): ?>
            <img src="<?= htmlspecialchars($img['caminho']) ?>" alt="Foto do imóvel">
        <?php endforeach; ?>
    </div>

    <div class="info">
        <div class="info-main">
            <h2><?= htmlspecialchars($imovel['tipo']) ?> - R$ <?= number_format($imovel['valor'],2,',','.') ?></h2>
            <p><strong>Endereço:</strong> <?= htmlspecialchars($imovel['rua']) ?>, <?= htmlspecialchars($imovel['numero']) ?> - <?= htmlspecialchars($imovel['bairro']) ?>, <?= htmlspecialchars($imovel['cidade']) ?>, <?= htmlspecialchars($imovel['estado']) ?></p>
            <p><strong>Quartos:</strong> <?= $imovel['qtd_quartos'] ?> | <strong>Banheiros:</strong> <?= $imovel['qtd_banheiro'] ?> | <strong>Vagas:</strong> <?= $imovel['qtd_vagas'] ?></p>
            <p><strong>Descrição:</strong><br><?= nl2br(htmlspecialchars($imovel['descricao'])) ?></p>
        </div>

        <div class="sidebar">
            <h3>Solicitar Contato</h3>
            <form method="POST" action="enviar_contato.php">
                <input type="hidden" name="id_imovel" value="<?= $imovel['idIMOVEL'] ?>">
                <input type="text" name="nome" placeholder="Seu nome" required>
                <input type="tel" name="telefone" placeholder="Telefone" required>
                <input type="email" name="email" placeholder="Email" required>
                <textarea name="mensagem" placeholder="Mensagem" rows="4">Tenho interesse neste imóvel.</textarea>
                <button type="submit">Enviar Solicitação</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
