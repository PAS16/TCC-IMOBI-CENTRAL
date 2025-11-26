<?php
session_start();
if (!isset($_SESSION['idUSUARIO']) || !isset($_SESSION['tipo'])) {
    header("Location: glogin.php");
    exit();
}

include 'conexao.php'; // Certifique-se de que o caminho 'conexao.php' está correto

// Captura termo de busca via GET
$termo = trim($_GET['termo'] ?? '');
$resultados = [];

if($termo){
    // Utiliza mysqli_real_escape_string para segurança
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerenciamento Imobiliária</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body::before{content:"";position:fixed;top:0;left:0;right:0;bottom:0;background:linear-gradient(135deg,#111,#1a1a1a,#222233,#1a1a1a);background-size:400% 400%;animation:gradientMove 20s ease infinite;z-index:-2;}
@keyframes gradientMove{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}}
.particle{position:absolute;border-radius:50%;background:rgba(255,255,255,0.03);pointer-events:none;z-index:-1;animation:floatParticle linear infinite;}
@keyframes floatParticle{0%{transform:translateY(0) translateX(0) scale(0.5);opacity:0;}10%{opacity:0.2;}100%{transform:translateY(-800px) translateX(200px) scale(1);opacity:0;}}

.btn-glow{position:relative;transition:all 0.3s ease;background:#1f1f2f;color:#e0e0e0;}
.btn-glow::before{content:'';position:absolute;top:-2px;left:-2px;right:-2px;bottom:-2px;background:linear-gradient(45deg,#2a2a3f,#3a3a5a,#2a2a3f,#3a3a5a);border-radius:inherit;filter:blur(6px);opacity:0;transition:opacity 0.3s ease;z-index:-1;}
.btn-glow:hover{background:#2c2c44;}
.btn-glow:hover::before{opacity:1;}

.btn-logout{background:#dc2626; transition:background 0.3s ease;} /* Estilo para o botão de logout */
.btn-logout:hover{background:#b91c1c;}

.card-dynamic{box-shadow:0 10px 25px rgba(0,0,0,0.6);transition:transform 0.3s ease, box-shadow 0.3s ease;}
.card-dynamic:hover{transform:translateY(-5px);box-shadow:0 20px 40px rgba(0,0,0,0.8);}
.title-glow{text-shadow:0 0 6px rgba(255,255,255,0.3);}
.highlight{background-color:rgba(255,255,0,0.3);border-radius:3px;padding:0 2px;}
</style>
</head>
<body class="font-serif text-gray-100 relative min-h-screen overflow-hidden flex items-center justify-center">

<?php for($i=0;$i<25;$i++): ?>
<div class="particle" style="width:<?=rand(5,15)?>px;height:<?=rand(5,15)?>px;top:<?=rand(0,100)?>%;left:<?=rand(0,100)?>%;animation-duration:<?=rand(20,40)?>s;animation-delay:<?=rand(0,20)?>s;"></div>
<?php endfor; ?>

<div class="fade-in-page w-full px-4 flex flex-col items-center">
    <div class="bg-[#1f1f2f]/90 backdrop-blur-md p-10 rounded-3xl text-center w-full max-w-2xl shadow-2xl space-y-6 card-dynamic border border-[#2a2a3f]/50">
        <h2 class="text-3xl font-bold mb-6 tracking-wide text-gray-200 title-glow">Gerenciamento Imobiliária</h2>

        <div class="flex gap-2 mb-4">
            <input type="text" id="campo-busca" placeholder="Buscar por ID, nome, telefone, endereço..." 
                   class="flex-1 p-3 rounded-xl bg-[#2a2a3f] text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#3a3a5a] shadow-inner"/>
            <button id="btn-busca" class="px-6 rounded-xl font-bold btn-glow shadow-md">Buscar</button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php if ($_SESSION['tipo'] === 'admin'): ?>
                <a href="corretor/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Corretores</a>
                <a href="clientes/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Clientes</a>
                <a href="proprietario/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Proprietários</a>
                <a href="imoveis/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Imóveis</a>
                <a href="visitas/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Visitas</a>
                <a href="propostas/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Propostas</a>
                <a href="logins.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Histórico/Logins</a>
            
            <?php elseif ($_SESSION['tipo'] === 'GESTOR'): ?>
                <a href="corretor/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Corretores</a>
                <a href="clientes/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Clientes</a>
                <a href="proprietario/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Proprietários</a>
                <a href="imoveis/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Imóveis</a>
                <a href="visitas/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Visitas</a>
                <a href="propostas/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Propostas</a>
            
            <?php elseif ($_SESSION['tipo'] === 'CORRETOR'): ?>
                <a href="visitas/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Minhas Visitas</a>
                <a href="propostas/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Minhas Propostas</a>
                <a href="imoveis/listar.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center">Buscar Imóveis</a>
            <?php endif; ?>
            
            <a href="logout.php" class="py-3 px-5 rounded-xl font-bold btn-logout shadow-md text-center">Sair (Logout)</a>

            <a href="index.php" class="py-3 px-5 rounded-xl font-bold btn-glow shadow-md text-center inline-block">Voltar ao Início</a>
        </div>
    </div>
</div>

<div id="modal-resultados" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50">
    <div class="bg-[#1f1f2f] p-6 rounded-2xl w-2/3 max-w-3xl max-h-[80vh] overflow-y-auto relative card-dynamic border border-[#2a2a3f]/50">
        <h3 class="text-xl font-bold mb-4 title-glow">Resultados da Busca</h3>
        <ul id="lista-resultados" class="space-y-2 mb-4">
            <?php if($resultados): ?>
                <?php foreach($resultados as $r):
                    $url='';
                    switch($r['tipo_item']){
                        case 'cliente': $url='clientes/listar.php?id='.$r['id']; break;
                        case 'proprietario': $url='proprietario/listar.php?id='.$r['id']; break;
                        case 'corretor': $url='corretor/listar.php?id='.$r['id']; break;
                        case 'imovel': $url='imoveis/listar.php?id='.$r['id']; break;
                    }
                    $regex = "/(".preg_quote($termo, '/').")/i";
                    $nomeDestacado = preg_replace($regex,'<span class="highlight">$1</span>',$r['nome_exibir']);
                ?>
                    <li onclick="window.location='<?=$url?>'" class="p-2 bg-[#2a2a3f] rounded-xl cursor-pointer hover:bg-[#3a3a5a]"><?=$r['tipo_item']?>: <?=$nomeDestacado?></li>
                <?php endforeach; ?>
            <?php else: ?>
                <?php if($termo): ?>
                    <li class="p-2 bg-[#2a2a3f] rounded-xl">Nenhum resultado encontrado.</li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
        <div class="flex flex-wrap gap-2 justify-center mb-4">
            <a href="clientes/listar.php" class="px-4 py-2 rounded-lg bg-[#2a2a3f] hover:bg-[#3a3a5a] text-gray-200 font-bold">Ver Todos Clientes</a>
            <a href="proprietario/listar.php" class="px-4 py-2 rounded-lg bg-[#2a2a3f] hover:bg-[#3a3a5a] text-gray-200 font-bold">Ver Todos Proprietários</a>
            <a href="corretor/listar.php" class="px-4 py-2 rounded-lg bg-[#2a2a3f] hover:bg-[#3a3a5a] text-gray-200 font-bold">Ver Todos Corretores</a>
            <a href="imoveis/listar.php" class="px-4 py-2 rounded-lg bg-[#2a2a3f] hover:bg-[#3a3a5a] text-gray-200 font-bold">Ver Todos Imóveis</a>
        </div>
        <button onclick="$('#modal-resultados').addClass('hidden')" class="absolute top-2 right-2 text-gray-400 hover:text-white">&times;</button>
    </div>
</div>

<script>
$('#btn-busca').on('click', function(){
    let termo = $('#campo-busca').val().trim();
    if(termo) window.location.href = "?termo="+encodeURIComponent(termo);
});

$('#campo-busca').on('keypress', function(e){
    if(e.key==='Enter'){
        $('#btn-busca').click();
    }
});

<?php if($resultados): ?>
$('#modal-resultados').removeClass('hidden');
<?php endif; ?>
</script>

</body>
</html>