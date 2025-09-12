<?php
include 'conexao.php';

if (!isset($conn) || $conn->connect_error) {
    die("Erro de conexão com o banco de dados: " . ($conn->connect_error ?? "Variável \$conn não definida"));
}

// Cidade e pesquisa
$cidade = isset($_GET['cidade']) ? $_GET['cidade'] : '';
$pesquisa = isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '';

$sql = "SELECT i.idIMOVEL, i.descricao, i.cidade, i.qtd_quartos, i.qtd_banheiro, i.qtd_vagas, i.valor, img.caminho 
        FROM IMOVEL i
        LEFT JOIN IMAGEM_IMOVEL img ON i.idIMOVEL = img.IMOVEL_idIMOVEL
        WHERE 1=1";

$params = [];
$types = "";

// Filtro cidade
if (!empty($cidade)) {
    $sql .= " AND i.cidade = ?";
    $params[] = $cidade;
    $types .= "s";
}

// Pesquisa na descrição ou endereço
if (!empty($pesquisa)) {
    $sql .= " AND i.descricao LIKE ?";
    $params[] = "%" . $pesquisa . "%";
    $types .= "s";
}

$stmt = $conn->prepare($sql);
if (!$stmt) die("Erro na preparação da query: " . $conn->error);

// Bind dinâmico
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Imagens de exemplo caso não haja no banco
$imagensExemplo = [
    "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80",
    "https://images.unsplash.com/photo-1599423300746-b62533397364?auto=format&fit=crop&w=800&q=80",
    "https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=800&q=80"
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Imóveis Disponíveis</title>
<style>
body { margin:0; font-family:Arial,sans-serif; background:#2f2f2f; color:#fff; }
header { background:#1f1f1f; padding:20px; display:flex; justify-content: space-between; align-items:center; }
header h1 { margin:0; font-size:1.8em; }
header form { display:flex; gap:10px; }
header input[type="text"], header select { padding:10px; border-radius:8px; border:none; font-size:1em; }
header button { padding:10px 15px; border-radius:8px; border:none; background:#00bcd4; color:white; cursor:pointer; }
header button:hover { background:#0097a7; }

.container { display:flex; flex-wrap:wrap; justify-content:center; gap:30px; padding:20px; }

.card { background:#3a3a3a; border-radius:15px; overflow:hidden; width:350px; transition: transform 0.3s; }
.card:hover { transform:translateY(-8px); }
.card img { width:100%; height:220px; object-fit:cover; }
.card-content { background:#262626; padding:15px; text-align:left; }
.card-content h3 { margin:0 0 10px 0; font-size:1.2em; }
.card-content p { margin:5px 0; font-size:0.95em; color:#ccc; }
.card-icons { display:flex; gap:15px; margin-top:10px; color:#fff; font-size:0.9em; }
.card-icons span { display:flex; align-items:center; gap:5px; }
.card-content .valor { margin-top:10px; font-weight:bold; font-size:1.1em; }
.card-content button { margin-top:10px; width:100%; padding:10px; background:#00bcd4; border:none; border-radius:8px; color:white; cursor:pointer; }
.card-content button:hover { background:#0097a7; }
</style>
</head>
<body>

<header>
    <h1>IMÓVEIS DISPONÍVEIS</h1>
    <form method="GET">
        <input type="text" name="pesquisa" placeholder="Pesquisar..." value="<?php echo htmlspecialchars($pesquisa); ?>">
        <select name="cidade">
            <option value="">Todas as cidades</option>
            <option value="Praia Grande" <?php if($cidade=='Praia Grande') echo 'selected'; ?>>Praia Grande</option>
            <option value="Mongaguá" <?php if($cidade=='Mongaguá') echo 'selected'; ?>>Mongaguá</option>
            <option value="Itanhaém" <?php if($cidade=='Itanhaém') echo 'selected'; ?>>Itanhaém</option>
        </select>
        <button type="submit">Pesquisar</button>
    </form>
</header>

<div class="container">
<?php 
if ($result && $result->num_rows > 0) {
    $i = 0;
    while ($row = $result->fetch_assoc()) { 
        $imgSrc = !empty($row['caminho']) ? $row['caminho'] : $imagensExemplo[$i % count($imagensExemplo)];
        $i++;
?>
    <div class="card">
        <img src="<?php echo $imgSrc; ?>" alt="Imagem do imóvel">
        <div class="card-content">
            <h3><?php echo htmlspecialchars($row['descricao']); ?></h3>
            <p><?php echo strtoupper(htmlspecialchars($row['cidade'])); ?></p>
            <div class="card-icons">
                <span>🛏 <?php echo $row['qtd_quartos'] ?? 0; ?> Quartos</span>
                <span>🛁 <?php echo $row['qtd_banheiro'] ?? 0; ?> Banheiros</span>
                <span>🚗 <?php echo $row['qtd_vagas'] ?? 0; ?> Vagas</span>
            </div>
            <div class="valor">R$ <?php echo number_format($row['valor'] ?? 0,2,",","."); ?></div>
            <button>EXIBIR</button>
        </div>
    </div>
<?php 
    }
} else {
    echo "<p style='color:#ccc;'>Nenhum imóvel encontrado.</p>";
}
?>
</div>

</body>
</html>
