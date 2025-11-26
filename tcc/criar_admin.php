<?php
// criar_admin.php
// Executar UMA vez. Depois remover/renomear o arquivo.
// Usa conexao.php que deve expor $conn (mysqli)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexao.php';

if (!isset($conn)) {
    die("Erro: conexao.php não definiu \$conn corretamente.");
}

$usuario = 'admin';
$email   = 'admin@imobi.com';
$senha_plana = 'G3st0r!Imobi2025'; // senha gerada
$hash = password_hash($senha_plana, PASSWORD_DEFAULT);
$tipo = 'admin';

// Verifica se já existe usuário admin
$stmt = $conn->prepare("SELECT idUSUARIO FROM USUARIO WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Atualiza
    $stmt->bind_result($idExistente);
    $stmt->fetch();
    $upd = $conn->prepare("UPDATE USUARIO SET senha = ?, tipo = ?, email = ? WHERE idUSUARIO = ?");
    $upd->bind_param("sssi", $hash, $tipo, $email, $idExistente);
    if ($upd->execute()) {
        echo "Usuário 'admin' atualizado com sucesso.<br>";
        echo "Login: <strong>admin</strong><br>Senha: <strong>$senha_plana</strong><br>";
    } else {
        echo "Erro ao atualizar admin: " . htmlspecialchars($conn->error);
    }
} else {
    // Insere novo usuário admin
    $ins = $conn->prepare("INSERT INTO USUARIO (usuario, senha, tipo, email) VALUES (?, ?, ?, ?)");
    $ins->bind_param("ssss", $usuario, $hash, $tipo, $email);
    if ($ins->execute()) {
        $newId = $conn->insert_id;
        // cria entrada em GESTOR opcional para referenciar nome (só se quiser)
        $insG = $conn->prepare("INSERT INTO GESTOR (USUARIO_idUSUARIO, nome) VALUES (?, ?)");
        $nomeGestor = 'Administrador do Sistema';
        $insG->bind_param("is", $newId, $nomeGestor);
        $insG->execute();
        echo "Usuário 'admin' criado com sucesso.<br>";
        echo "Login: <strong>admin</strong><br>Senha: <strong>$senha_plana</strong><br>";
    } else {
        echo "Erro ao criar admin: " . htmlspecialchars($conn->error);
    }
}

$stmt->close();
$conn->close();
?>
