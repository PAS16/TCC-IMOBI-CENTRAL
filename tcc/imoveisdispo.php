<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mydb";

// Criando conexão
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// ----- FILTROS -----
$where = "WHERE 1=1"; 

if (!empty($_GET['cidade'])) {
    $cidade = $conn->real_escape_string($_GET['cidade']);
    $where .= " AND cidade = '$cidade'";
}

if (!empty($_GET['preco_min'])) {
    $preco_min = (int) $_GET['preco_min'];
    $where .= " AND preco >= $preco_min";
}

if (!empty($_GET['preco_max'])) {
    $preco_max = (int) $_GET['preco_max'];
    $where .= " AND preco <= $preco_max";
}

if (!empty($_GET['quartos'])) {
    $quartos = (int) $_GET['quartos'];
    $where .= " AND quartos >= $quartos";
}

if (!empty($_GET['banheiros'])) {
    $banheiros = (int) $_GET['banheiros'];
    $where .= " AND banheiros >= $banheiros";
}

if (!empty($_GET['vagas'])) {
    $vagas = (int) $_GET['vagas'];
    $where .= " AND vagas >= $vagas";
}

// Consulta imóveis
$sql = "SELECT id, titulo, descricao, cidade, preco, quartos, banheiros, vagas 
        FROM imovel $where ORDER BY preco ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Imóveis</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-800 text-white">

<div class="p-6">

    <!-- Botão para abrir filtros -->
    <button onclick="document.getElementById('overlayFiltro').style.display='flex'" 
    class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg">
        Filtros
    </button>

    <h1 class="text-2xl font-bold my-4">Lista de Imóveis</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "
                <div class='bg-gray-900 p-4 rounded-xl shadow-lg'>
                    <h2 class='text-xl font-semibold mb-2'>".$row['titulo']."</h2>
                    <p class='text-sm text-gray-300 mb-2'>".$row['descricao']."</p>
                    <p><strong>Cidade:</strong> ".$row['cidade']."</p>
                    <p><strong>Preço:</strong> R$ ".number_format($row['preco'], 2, ',', '.')."</p>
                    <p><strong>Quartos:</strong> ".$row['quartos']."</p>
                    <p><strong>Banheiros:</strong> ".$row['banheiros']."</p>
                    <p><strong>Vagas:</strong> ".$row['vagas']."</p>
                </div>
                ";
            }
        } else {
            echo "<p>Nenhum imóvel encontrado.</p>";
        }
        ?>
    </div>
</div>

<!-- Overlay de Filtros -->
<div id="overlayFiltro" class="fixed inset-0 bg-black bg-opacity-70 hidden justify-center items-center z-50">
    <div class="bg-gray-900 p-8 rounded-2xl w-[400px] shadow-lg relative text-white">

        <!-- Botão fechar -->
        <button onclick="document.getElementById('overlayFiltro').style.display='none'" 
        class="absolute top-3 right-3 text-white text-xl">&times;</button>

        <h2 class="text-xl font-bold mb-6 text-center">Filtrar Imóveis</h2>

        <form method="GET" action="buscar_imoveis.php" class="space-y-4">

            <!-- Cidade -->
            <label class="block">Cidade</label>
            <select name="cidade" class="w-full p-2 rounded bg-gray-800 text-white">
                <option value="">Todos os imóveis</option>
                <?php
                $sqlCidades = "SELECT DISTINCT cidade FROM imovel ORDER BY cidade";
                $resultCidades = $conn->query($sqlCidades);
                while ($rowCidade = $resultCidades->fetch_assoc()) {
                    $selected = (isset($_GET['cidade']) && $_GET['cidade'] == $rowCidade['cidade']) ? "selected" : "";
                    echo "<option value='".$rowCidade['cidade']."' $selected>".$rowCidade['cidade']."</option>";
                }
                ?>
            </select>

            <!-- Preço -->
            <div class="flex gap-2">
                <input type="number" name="preco_min" value="<?= $_GET['preco_min'] ?? '' ?>" placeholder="Preço mín" class="w-1/2 p-2 rounded bg-gray-800 text-white">
                <input type="number" name="preco_max" value="<?= $_GET['preco_max'] ?? '' ?>" placeholder="Preço máx" class="w-1/2 p-2 rounded bg-gray-800 text-white">
            </div>

            <!-- Quartos -->
            <input type="number" name="quartos" value="<?= $_GET['quartos'] ?? '' ?>" placeholder="Quantidade de quartos" class="w-full p-2 rounded bg-gray-800 text-white">

            <!-- Banheiros -->
            <input type="number" name="banheiros" value="<?= $_GET['banheiros'] ?? '' ?>" placeholder="Quantidade de banheiros" class="w-full p-2 rounded bg-gray-800 text-white">

            <!-- Vagas -->
            <input type="number" name="vagas" value="<?= $_GET['vagas'] ?? '' ?>" placeholder="Quantidade de vagas" class="w-full p-2 rounded bg-gray-800 text-white">

            <!-- Botão aplicar -->
            <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-500 rounded-lg font-semibold">
                Aplicar Filtros
            </button>
        </form>
    </div>
</div>

</body>
</html>
