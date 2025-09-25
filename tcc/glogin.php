<?php
session_start();

// --- Conexão com o banco ---
$host = "localhost";
$user = "root";
$pass = "admin";
$db   = "mydb";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Falha na conexão: " . $conn->connect_error);

$mensagemErro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = trim($_POST['senha'] ?? '');

    if ($usuario && $senha) {
        // Consulta segura usando SHA2 para senhas
        $stmt = $conn->prepare("SELECT idGESTOR, nome FROM GESTOR WHERE usuario = ? AND senha = SHA2(?, 256) LIMIT 1");
        $stmt->bind_param("ss", $usuario, $senha);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $gestor = $result->fetch_assoc();
            $_SESSION['usuario'] = $usuario;
            $_SESSION['nome']    = $gestor['nome'];
            $_SESSION['idGESTOR']= $gestor['idGESTOR'];

            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const box = document.getElementById('loginBox');
                        box.classList.add('animate-fadeOut');
                        setTimeout(() => {
                            window.location.href = 'staffmenu.php';
                        }, 600);
                    });
                  </script>";
        } else {
            $mensagemErro = "Usuário ou senha inválidos.";
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
@keyframes fadeOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(-20px); } }
@keyframes fadeInPage { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes gradientMove { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.animate-fadeOut { animation: fadeOut 0.6s forwards; }
.animate-fadeInPage { animation: fadeInPage 0.8s ease-in; }
.animate-slideUp { animation: slideUp 0.8s ease-out; }
.animate-gradientMove { animation: gradientMove 3s linear infinite; background-size: 200% 100%; }
</style>
</head>
<body class="h-screen bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center font-serif">
<div class="text-center animate-fadeInPage">
    <div class="mb-6">
        <img src="imagem/logo.png" alt="Logo Imobi Central" class="w-40 mx-auto drop-shadow-md">
    </div>
    <div id="loginBox" class="bg-gray-800/90 p-9 rounded-2xl max-w-md mx-auto shadow-xl animate-slideUp">
        <h2 class="text-xl text-gray-100 font-semibold mb-6">Login - Área de Gestãof</h2>
        <form method="POST" class="space-y-4">
            <input type="text" name="usuario" placeholder="Usuário" required 
                   class="w-full px-4 py-3 rounded-lg bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
            <input type="password" name="senha" placeholder="Senha" required
                   class="w-full px-4 py-3 rounded-lg bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
            <button type="submit" 
                    class="w-full px-4 py-3 rounded-lg font-bold text-white bg-gradient-to-r from-gray-600 via-gray-500 to-gray-600 animate-gradientMove hover:scale-105 transition-transform">
                Entrar
            </button>
        </form>
        <div class="text-red-500 mt-4 min-h-[1.25rem]">
            <?php if (!empty($mensagemErro)) echo $mensagemErro; ?>
        </div>
        <div onclick="voltarInicio()" 
             class="mt-6 text-gray-400 hover:text-white cursor-pointer text-sm underline">
             ← Voltar ao Início
        </div>
    </div>
</div>

<script>
function voltarInicio() {
    const box = document.getElementById('loginBox');
    box.classList.add('animate-fadeOut');
    setTimeout(() => { window.location.href = 'index.php'; }, 500);
}
</script>
</body>
</html>
