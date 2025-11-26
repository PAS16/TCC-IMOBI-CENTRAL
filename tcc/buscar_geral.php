<?php
header('Content-Type: application/json');
include 'conexao.php';

$termo = trim($_GET['termo'] ?? '');
$resultados = [];

if($termo){
    $termo_esc = mysqli_real_escape_string($conn, $termo);

    $sql = "
        SELECT 'cliente' AS tipo_item, idCLIENTE AS id, nome AS nome_exibir
        FROM CLIENTE
        WHERE nome LIKE '%$termo_esc%' OR cpf LIKE '%$termo_esc%' OR telefone LIKE '%$termo_esc%'

        UNION ALL

        SELECT 'proprietario', idPROPRIETARIO, nome
        FROM PROPRIETARIO
        WHERE nome LIKE '%$termo_esc%' OR cpf LIKE '%$termo_esc%' OR telefone LIKE '%$termo_esc%'

        UNION ALL

        SELECT 'corretor', idCORRETOR, nome
        FROM CORRETOR
        WHERE nome LIKE '%$termo_esc%' OR creci LIKE '%$termo_esc%' OR telefone LIKE '%$termo_esc%'

        UNION ALL

        SELECT 'imovel', idIMOVEL, titulo
        FROM IMOVEL
        WHERE titulo LIKE '%$termo_esc%' OR cidade LIKE '%$termo_esc%' OR bairro LIKE '%$termo_esc%' OR rua LIKE '%$termo_esc%'
        LIMIT 20
    ";

    $res = $conn->query($sql);
    if($res){
        $resultados = $res->fetch_all(MYSQLI_ASSOC);
    }
}

echo json_encode($resultados);
