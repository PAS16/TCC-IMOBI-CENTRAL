<?php
// glogin.php
// Versão compatível com servidores sem mysqlnd (usa bind_result())

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'conexao.php';

$mensagemErro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = trim($_POST['senha'] ?? '');

    if ($usuario && $senha) {
        $stmt = $conn->prepare("SELECT idUSUARIO, senha, tipo FROM USUARIO WHERE usuario = ?");
        if (!$stmt) {
            $mensagemErro = "Erro ao preparar consulta: " . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($idUSUARIO, $hashSenha, $tipoUsuario);
                $stmt->fetch();

                if ($hashSenha !== null && password_verify($senha, $hashSenha)) {
                    // Login bem sucedido
                    session_regenerate_id(true);
                    $_SESSION['idUSUARIO'] = $idUSUARIO;
                    $_SESSION['usuario'] = $usuario;
                    $_SESSION['tipo'] = $tipoUsuario;

                    // busca nome conforme tipo
                    if ($tipoUsuario === 'GESTOR') {
                        $stmt2 = $conn->prepare("SELECT idGESTOR, nome FROM GESTOR WHERE USUARIO_idUSUARIO = ?");
                        if ($stmt2) {
                            $stmt2->bind_param("i", $idUSUARIO);
                            $stmt2->execute();
                            $stmt2->store_result();
                            if ($stmt2->num_rows > 0) {
                                $stmt2->bind_result($idGESTOR, $nome);
                                $stmt2->fetch();
                                $_SESSION['idGESTOR'] = $idGESTOR;
                                $_SESSION['nome'] = $nome;
                            } else {
                                $_SESSION['nome'] = 'Gestor';
                            }
                            $stmt2->close();
                        }
                    } elseif ($tipoUsuario === 'CORRETOR') {
                        $stmt2 = $conn->prepare("SELECT idCORRETOR, nome FROM CORRETOR WHERE USUARIO_idUSUARIO = ?");
                        if ($stmt2) {
                            $stmt2->bind_param("i", $idUSUARIO);
                            $stmt2->execute();
                            $stmt2->store_result();
                            if ($stmt2->num_rows > 0) {
                                $stmt2->bind_result($idCORRETOR, $nome);
                                $stmt2->fetch();
                                $_SESSION['idCORRETOR'] = $idCORRETOR;
                                $_SESSION['nome'] = $nome;
                            } else {
                                $_SESSION['nome'] = 'Corretor';
                            }
                            $stmt2->close();
                        }
                    } elseif ($tipoUsuario === 'admin') {
                        $_SESSION['nome'] = 'Administrador do Sistema';
                    }

                    // redireciona com animação
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const box = document.getElementById('loginBox');
                            if (box) box.classList.add('animate-fadeOut');
                            setTimeout(() => { window.location.href = 'staffmenu.php'; }, 600);
                        });
                    </script>";
                    $stmt->close();
                    $conn->close();
                    exit;
                } else {
                    $mensagemErro = "Usuário ou senha incorretos.";
                }
            } else {
                $mensagemErro = "Usuário não encontrado.";
            }
            $stmt->close();
        }
    } else {
        $mensagemErro = "Preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Login - Imobi Central</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
/* (mesmo CSS do seu layout anterior) */
@keyframes fadeOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(-20px); } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes gradientMove { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: linear-gradient(270deg, #111827, #1f2937, #1c1c1c, #111827);
    background-size: 400% 400%;
    animation: gradientMove 20s ease infinite;
    z-index: -2;
}
.particle { position: absolute; border-radius: 50%; background: rgba(255,255,255,0.05); pointer-events: none; z-index: -1; animation: floatParticle linear infinite; }
@keyframes floatParticle { 0% { transform: translateY(0) translateX(0) scale(0.5); opacity: 0; } 10% { opacity: 0.3; } 100% { transform: translateY(-800px) translateX(200px) scale(1); opacity: 0; } }
.animate-fadeOut { animation: fadeOut 0.6s forwards; }
.animate-slideUp { animation: slideUp 0.8s ease-out; }
</style>
</head>
<body class="h-screen flex items-center justify-center font-serif text-gray-100 overflow-hidden">
<?php for($i=0;$i<25;$i++): ?>
<div class="particle" style="width:<?=rand(5,15)?>px;height:<?=rand(5,15)?>px;top:<?=rand(0,100)?>%;left:<?=rand(0,100)?>%;animation-duration:<?=rand(20,40)?>s;animation-delay:<?=rand(0,20)?>s;"></div>
<?php endfor; ?>

<div id="loginBox" class="bg-gray-900/95 backdrop-blur-md p-10 rounded-3xl w-full max-w-md mx-auto shadow-2xl animate-slideUp text-center flex flex-col items-center justify-center">
    <div class="flex items-center justify-center mb-6 space-x-3">
        <img src="imagem/logo1.png" alt="Logo Imobi Central" class="w-12 drop-shadow-md">
        <h2 class="text-2xl text-gray-100 font-semibold">Login - Área de Gestão</h2>
    </div>

    <form method="POST" class="space-y-5 w-full">
        <input type="text" name="usuario" placeholder="Usuário" required class="w-full px-4 py-3 rounded-xl bg-gray-800 text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
        <input type="password" name="senha" placeholder="Senha" required class="w-full px-4 py-3 rounded-xl bg-gray-800 text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
        <button type="submit" class="w-full px-4 py-3 rounded-xl font-bold text-gray-100 bg-gradient-to-r from-gray-700 via-gray-800 to-gray-700 hover:from-gray-600 hover:to-gray-800 transition-transform shadow-lg hover:scale-105">Entrar</button>
    </form>

    <div class="text-red-400 mt-4 min-h-[1.25rem]">
        <?php if (!empty($mensagemErro)) echo htmlspecialchars($mensagemErro); ?>
    </div>

    <div onclick="voltarInicio()" class="mt-6 text-gray-400 hover:text-white cursor-pointer text-sm underline"> ← Voltar ao Início</div>
</div>

<script>
function voltarInicio() {
    const box = document.getElementById('loginBox');
    if (box) box.classList.add('animate-fadeOut');
    setTimeout(() => { window.location.href = 'index.php'; }, 500);
}
</script>
</body>
</html>
