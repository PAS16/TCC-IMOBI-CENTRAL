<?php
session_start();
if (!isset($_SESSION['idUSUARIO']) || !isset($_SESSION['tipo'])) {
    header("Location: glogin.php");
    exit();
}

include '../conexao.php'; // ajuste o caminho se necessário

// --- Endpoint AJAX interno para busca unificada (clientes, proprietarios, corretores, imoveis) ---
if (isset($_GET['ajax_search']) && $_GET['ajax_search'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    $termo = trim($_GET['termo'] ?? '');
    $out = [];
    if ($termo !== '') {
        // Correção: Uso de prepared statement para SELECT com UNION é complexo, mantendo mysqli_real_escape_string
        $t = mysqli_real_escape_string($conn, $termo);
        $sql = "
            SELECT 'cliente' AS tipo_item, idCLIENTE AS id, nome AS nome_exibir
            FROM CLIENTE
            WHERE nome LIKE '%$t%' OR cpf LIKE '%$t%' OR telefone LIKE '%$t%'
            UNION
            SELECT 'proprietario', idPROPRIETARIO, nome
            FROM PROPRIETARIO
            WHERE nome LIKE '%$t%' OR cpf LIKE '%$t%' OR telefone LIKE '%$t%'
            UNION
            SELECT 'corretor', idCORRETOR, nome
            FROM CORRETOR
            WHERE nome LIKE '%$t%' OR creci LIKE '%$t%' OR telefone LIKE '%$t%'
            UNION
            SELECT 'imovel', idIMOVEL, IFNULL(titulo, CONCAT(tipo, ' - ', cidade)) as nome_exibir
            FROM IMOVEL
            WHERE (titulo IS NOT NULL AND titulo LIKE '%$t%') OR cidade LIKE '%$t%' OR bairro LIKE '%$t%' OR rua LIKE '%$t%'
            LIMIT 40
        ";
        $res = $conn->query($sql);
        if ($res) $out = $res->fetch_all(MYSQLI_ASSOC);
    }
    echo json_encode($out);
    exit;
}

// -------------------- Ações AJAX (cadastrar / editar / excluir / visualizar) --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    
    // funções utilitárias internas
    function resposta($arr) {
        // Garante que não haverá mais nada na saída.
        ob_clean(); 
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($arr);
        exit;
    }

    if ($acao === 'cadastrar' || $acao === 'editar') {
        
        // 1. Coleta e Sanitização de Inputs
        $id = intval($_POST['id'] ?? 0); // 0 se cadastrar, >0 se editar
        $titulo = trim($_POST['titulo'] ?? '');
        $proprietario = intval($_POST['proprietario'] ?? 0);
        $tipo = trim($_POST['tipo'] ?? '');
        $status = trim($_POST['status'] ?? 'Disponivel');
        $rua = trim($_POST['rua'] ?? '');
        $numero = trim($_POST['numero'] ?? '');
        $bairro = trim($_POST['bairro'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $valor = floatval($_POST['valor'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $qtd_quartos = intval($_POST['qtd_quartos'] ?? 0);
        $qtd_banheiro = intval($_POST['qtd_banheiro'] ?? 0);
        $qtd_vagas = intval($_POST['qtd_vagas'] ?? 0);
        
        $negociavel = in_array($_POST['negociavel'] ?? 'Não', ['Sim','Não']) ? $_POST['negociavel'] : 'Não';
        $financiavel = in_array($_POST['financiavel'] ?? 'Não', ['Sim','Não']) ? $_POST['financiavel'] : 'Não';

        
        if (!$proprietario || !$tipo || !$status) {
            resposta(['status'=>'erro','mensagem'=>'Proprietário, tipo e status são obrigatórios']);
        }

        // montar types dinamicamente para bind_param
        $types_base = 'i' . 's' .
            str_repeat('s', 7) . 'd' . 's' . str_repeat('i', 3) . 'ss';
        
        $sucesso = false;
        $id_imovel_processado = 0;

        // 2. Inserção / Atualização Principal do Imóvel
        if ($acao === 'cadastrar') {
            $sql = "INSERT INTO IMOVEL (PROPRIETARIO_idPROPRIETARIO, titulo, tipo,status,rua,numero,bairro,cidade,estado,valor,descricao,qtd_quartos,qtd_banheiro,qtd_vagas,negociavel,financiavel)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) resposta(['status'=>'erro','mensagem'=>'Erro ao preparar INSERÇÃO: ' . $conn->error]);

            $types = $types_base;
            $stmt->bind_param($types,
                $proprietario, $titulo, $tipo, $status, $rua, $numero, $bairro, $cidade, $estado,
                $valor, $descricao, $qtd_quartos, $qtd_banheiro, $qtd_vagas, $negociavel, $financiavel
            );
            $sucesso = $stmt->execute();
            
            // 🔑 CAPTURA DO ID PARA O UPLOAD (Correto para CADASTRO)
            $id_imovel_processado = $stmt->insert_id; 
            
            $stmt->close();
            
        } else {
            // editar
            $sql = "UPDATE IMOVEL SET PROPRIETARIO_idPROPRIETARIO=?, titulo=?, tipo=?, status=?, rua=?, numero=?, bairro=?, cidade=?, estado=?, valor=?, descricao=?, qtd_quartos=?, qtd_banheiro=?, qtd_vagas=?, negociavel=?, financiavel=? WHERE idIMOVEL=?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) resposta(['status'=>'erro','mensagem'=>'Erro ao preparar ATUALIZAÇÃO: ' . $conn->error]);

            $types = $types_base . 'i'; // + id (i)
            $stmt->bind_param($types,
                $proprietario, $titulo, $tipo, $status, $rua, $numero, $bairro, $cidade, $estado,
                $valor, $descricao, $qtd_quartos, $qtd_banheiro, $qtd_vagas, $negociavel, $financiavel, $id
            );
            $sucesso = $stmt->execute();
            
            // 🔑 CAPTURA DO ID PARA O UPLOAD (Correto para EDIÇÃO)
            $id_imovel_processado = $id; 
            
            $stmt->close();
        }

        if (!$sucesso) {
            // Se a inserção/atualização falhar, encerra aqui
            resposta(['status'=>'erro','mensagem'=>'Erro de banco de dados na atualização do imóvel: ' . $conn->error]);
        }
        
        // -------------------------------------------------------------------------------------------------
        // 3. BLOCO DE UPLOAD DE MÍDIAS (CORRIGIDO CAMINHOS, CHECAGEM E CRIAÇÃO DE PASTA)
        // -------------------------------------------------------------------------------------------------

        // Upload de mídias (imagens/vídeos)
        $warning_message = '';
        $files_uploaded_count = 0; // Contador de uploads bem-sucedidos
        
        if ($id_imovel_processado > 0 && !empty($_FILES['imagens']) && !empty($_FILES['imagens']['name'][0])) {
            
            $allowed_img = ['jpg','jpeg','png','gif','webp'];
            $allowed_vid = ['mp4','webm','ogg'];
            
            // 1. Definição do Caminho Físico (Um nível acima do script, na pasta 'uploads/imoveis')
            $scriptDir = __DIR__; // Diretório onde 'listar.php' está (ex: /var/www/html/tcc)
            $uploadPathBase = $scriptDir . '/../uploads/imoveis/'; // Caminho absoluto (ex: /var/www/html/uploads/imoveis/)
            
            // ** CHECA CRÍTICA DE DIRETÓRIO E CRIAÇÃO AUTOMÁTICA **
            // Tenta criar o diretório se não existir
            if (!is_dir($uploadPathBase)) {
                // Tenta criar o diretório recursivamente (true) com permissão 0777
                if (!mkdir($uploadPathBase, 0777, true)) {
                    // MENSAGEM DE ERRO CRÍTICA: FALHA AO CRIAR
                    $errorMessage = "⚠️ ERRO CRÍTICO DE PASTA: O diretório de upload ($uploadPathBase) não existe e **NÃO PÔDE SER CRIADO** pelo PHP. Por favor, verifique se a pasta 'uploads/' (o nível acima de 'tcc/') existe e tem permissões de escrita (CHMOD 777).";
                    resposta(['status'=>'erro','mensagem'=>$errorMessage]);
                }
            }
            
            // Após garantir que existe (ou foi criado), obtém o caminho real e checa permissão
            $uploadDir = realpath($uploadPathBase);

            if ($uploadDir === false || !is_writable($uploadDir)) {
                 // MENSAGEM DE ERRO CRÍTICA: PASTA EXISTE, MAS NÃO É GRAVÁVEL
                 $errorMessage = "🚫 ERRO DE PERMISSÃO: O diretório de upload (`$uploadPathBase`) existe (ou foi criado), mas **NÃO É GRAVÁVEL** pelo servidor web. Por favor, defina as permissões da pasta `$uploadPathBase` para **CHMOD 777** (Escrita total).";
                 resposta(['status'=>'erro','mensagem'=>$errorMessage]);
            }
            
            $uploadDir .= DIRECTORY_SEPARATOR; // Garante a barra no final
            
            // 2. Definição do Caminho Web (CORRIGIDO: O que o navegador usa)
            $webPathBase = '/TCC-IMOBI-CENTRAL/uploads/imoveis'; 

            
            // --- Lógica de Limites ---
            $max_total = 7;
            $max_videos = 2;
            
            // 1. Contar mídias existentes
            $existing_count = 0;
            $existing_videos = 0;
            
            $stmt_count = $conn->prepare("SELECT caminho FROM IMAGEM_IMOVEL WHERE IMOVEL_idIMOVEL=?");
            $stmt_count->bind_param("i", $id_imovel_processado);
            $stmt_count->execute();
            $result_count = $stmt_count->get_result();
            // Ignora o placeholder ao contar
            while($row = $result_count->fetch_assoc()) {
                if (strpos($row['caminho'], 'placeholder.jpg') !== false) continue;
                $existing_count++;
                $ext = strtolower(pathinfo($row['caminho'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed_vid)) {
                    $existing_videos++;
                }
            }
            $stmt_count->close();

            $valid_files_meta = [];
            $new_videos_count = 0;
            
            // 2. Pré-validação e contagem de novos arquivos válidos
            foreach ($_FILES['imagens']['tmp_name'] as $k => $tmp) {
                // Checa se o arquivo foi enviado corretamente e sem erro de upload
                if (!is_uploaded_file($tmp) || $_FILES['imagens']['error'][$k] !== UPLOAD_ERR_OK) {
                    if ($_FILES['imagens']['error'][$k] !== UPLOAD_ERR_NO_FILE) {
                         $warning_message .= " Erro: Falha de upload interno (Código: " . $_FILES['imagens']['error'][$k] . ") para o arquivo " . ($_FILES['imagens']['name'][$k] ?? 'indefinido') . ". (Verifique `php.ini` para limites de tamanho.)";
                    }
                    continue;
                }
                
                $origName = $_FILES['imagens']['name'][$k];
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $is_video = in_array($ext, $allowed_vid);
                
                // Ignora tipos não permitidos
                if (!in_array($ext, array_merge($allowed_img,$allowed_vid))) {
                    $warning_message .= " Erro: O arquivo '$origName' tem um tipo não permitido e foi ignorado.";
                    continue; 
                } 

                // Checa limite total (7 mídias)
                if (($existing_count + count($valid_files_meta)) >= $max_total) {
                     $warning_message .= " Aviso: O limite de $max_total mídias por imóvel foi atingido. As mídias restantes foram ignoradas.";
                     break; // Para de processar novos arquivos
                }
                
                // Checa limite de vídeos (2 vídeos)
                if ($is_video) {
                    if (($existing_videos + $new_videos_count) >= $max_videos) {
                        $warning_message .= " Aviso: O limite de $max_videos vídeos por imóvel foi atingido. O vídeo '$origName' foi ignorado.";
                        continue; // Ignora apenas este vídeo
                    }
                    $new_videos_count++;
                }
                
                $valid_files_meta[] = ['tmp' => $tmp, 'origName' => $origName, 'ext' => $ext, 'is_video' => $is_video];
            }
            
            // 3. Processamento de Upload e Registro
            foreach ($valid_files_meta as $meta) {
                $novoNome = uniqid('m_') . '.' . $meta['ext'];
                $dest = $uploadDir . $novoNome;
                
                if (move_uploaded_file($meta['tmp'], $dest)) {
                    // O caminho salvo no banco (coluna `caminho`) DEVE ser o caminho WEB/relativo
                    $caminhoBd = $webPathBase . '/' . $novoNome; 
                    
                    $stmtImg = $conn->prepare("INSERT INTO IMAGEM_IMOVEL (IMOVEL_idIMOVEL, caminho, nome_original) VALUES (?,?,?)");
                    
                    if ($stmtImg) {
                        $stmtImg->bind_param("iss", $id_imovel_processado, $caminhoBd, $meta['origName']); 
                        
                        if ($stmtImg->execute()) {
                             $files_uploaded_count++;
                        } else {
                            // Erro de INSERT no banco
                            $warning_message .= " Erro DB: Falha ao registrar '$meta[origName]' no banco de dados. Motivo: " . $stmtImg->error . ".";
                        }
                        $stmtImg->close();
                    } else {
                        // Erro ao preparar statement (geralmente sintaxe SQL)
                        $warning_message .= " Erro DB: Falha ao preparar INSERT para '$meta[origName]'. Motivo: " . $conn->error . ".";
                    }
                } else {
                    // Erro de I/O (movimentação) - O mais provável é permissão
                    $warning_message .= " Erro I/O: Falha ao mover arquivo '$meta[origName]'. Causa provável: **Permissão de escrita** na pasta de destino ($uploadDir).";
                }
            }
        }
        
        // 4. Resposta de Sucesso
        $msg = ($acao === 'cadastrar') ? 'Imóvel cadastrado com sucesso!' : 'Imóvel atualizado com sucesso!';
        
        if (isset($files_uploaded_count) && $files_uploaded_count > 0) {
            $msg .= " ($files_uploaded_count arquivo(s) salvo(s) com sucesso.)";
        }
        
        if (!empty($warning_message)) {
             // Destaca o aviso de falha.
             $msg .= " \n\n⚠️ **ATENÇÃO / ERROS ENCONTRADOS:** " . $warning_message;
        }
        
        resposta(['status'=>'sucesso','mensagem'=>$msg, 'id'=>$id_imovel_processado]);
    } // Fim do if (cadastrar || editar)

    if ($acao === 'excluir') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) resposta(['status'=>'erro','mensagem'=>'ID inválido']);
        
        // Antes de excluir o imóvel, vamos tentar excluir as mídias associadas
        $stmt_select = $conn->prepare("SELECT caminho FROM IMAGEM_IMOVEL WHERE IMOVEL_idIMOVEL=?");
        $stmt_select->bind_param("i", $id);
        $stmt_select->execute();
        $result_medias = $stmt_select->get_result();
        
        // Excluir as imagens fisicamente (opcional, mas recomendado)
        while($row = $result_medias->fetch_assoc()) {
             // Ignora o placeholder e URLs externas
            if (strpos($row['caminho'], 'https://') !== 0 && strpos($row['caminho'], 'placeholder.jpg') === false) {
                // Remove o prefixo web para obter o caminho relativo ao script
                $relativePath = str_replace('/TCC-IMOBI-CENTRAL/', '../', $row['caminho']);
                $fullPath = __DIR__ . '/' . $relativePath;
                if (file_exists($fullPath)) {
                    @unlink($fullPath); // @ para suprimir erros
                }
            }
        }
        $stmt_select->close();

        $stmt = $conn->prepare("DELETE FROM IMOVEL WHERE idIMOVEL=?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        // A exclusão de IMAGEM_IMOVEL é em cascata, por isso não precisamos de código adicional para o DB.
        resposta($ok?['status'=>'sucesso','mensagem'=>'Imóvel excluído com sucesso.']:['status'=>'erro','mensagem'=>$stmt->error]);
    }

    if ($acao === 'visualizar') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) resposta(['status'=>'erro','mensagem'=>'ID inválido']);
        
        $stmt = $conn->prepare("SELECT i.*, p.nome as proprietario FROM IMOVEL i LEFT JOIN PROPRIETARIO p ON i.PROPRIETARIO_idPROPRIETARIO=p.idPROPRIETARIO WHERE i.idIMOVEL=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $imovel = $stmt->get_result()->fetch_assoc();

        // buscar mídias
        $stmtM = $conn->prepare("SELECT caminho, nome_original FROM IMAGEM_IMOVEL WHERE IMOVEL_idIMOVEL=?");
        $stmtM->bind_param("i", $id);
        $stmtM->execute();
        $medias = $stmtM->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($imovel)) {
             resposta(['status'=>'erro','mensagem'=>'Imóvel não encontrado.']);
        }

        // AQUI: Checa se há APENAS o placeholder ou se não há nada no DB.
        $has_real_media = false;
        foreach($medias as $m) {
            if (strpos($m['caminho'], 'placeholder.jpg') === false) {
                $has_real_media = true;
                break;
            }
        }

        if (!$has_real_media) {
             // Se não encontrou nenhuma mídia real, garante que o placeholder está lá (apenas para visualização se não tiver nada)
            $medias = [['caminho'=>'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80','nome_original'=>'placeholder.jpg']];
        }

        resposta(['status'=>'sucesso','imovel'=>$imovel,'medias'=>$medias]);
    }

    // fim POST actions
}

// -------------------- Buscar / Listar (página normal) --------------------
$busca = $_GET['busca'] ?? '';
$where = '';
if (!empty($busca)) {
    $b = mysqli_real_escape_string($conn, $busca);
    $where = "WHERE i.cidade LIKE '%$b%' OR p.nome LIKE '%$b%' OR i.titulo LIKE '%$b%'";
}

$sql = "SELECT i.*, p.nome as proprietario FROM IMOVEL i LEFT JOIN PROPRIETARIO p ON i.PROPRIETARIO_idPROPRIETARIO=p.idPROPRIETARIO $where ORDER BY i.idIMOVEL DESC";
$resultado = $conn->query($sql);

$propResult = $conn->query("SELECT idPROPRIETARIO,nome FROM PROPRIETARIO ORDER BY nome ASC");
$proprietarios = [];
while ($r = $propResult->fetch_assoc()) $proprietarios[$r['idPROPRIETARIO']] = $r['nome'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Gerenciamento de Imóveis</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
/* Mantive o visual similar que você usa */
@keyframes fadeInPage { from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);} }
.fade-in-page{animation:fadeInPage 0.6s ease-in-out;}
body::before{content:"";position:fixed;top:0;left:0;right:0;bottom:0;background:linear-gradient(135deg,#111,#1a1a1a,#222233,#1a1a1a);background-size:400% 400%;animation:gradientMove 20s ease infinite;z-index:-2;}
@keyframes gradientMove{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}}
.particle{position:absolute;border-radius:50%;background:rgba(255,255,255,0.03);pointer-events:none;z-index:-1;animation:floatParticle linear infinite;}
@keyframes floatParticle{0%{transform:translateY(0) translateX(0) scale(0.5);opacity:0;}10%{opacity:0.2;}100%{transform:translateY(-800px) translateX(200px) scale(1);opacity:0;} }
.btn-glow{position:relative;transition:all 0.3s ease;background:#1f1f2f;color:#e0e0e0;}
.btn-glow::before{content:'';position:absolute;top:-2px;left:-2px;right:-2px;bottom:-2px;background:linear-gradient(45deg,#2a2a3f,#3a3a5a,#2a2a3f,#3a3a5a);border-radius:inherit;filter:blur(6px);opacity:0;transition:opacity 0.3s ease;z-index:-1;}
.btn-glow:hover{background:#2c2c44;}
.btn-glow:hover::before{opacity:1;}
.card-dynamic{box-shadow:0 10px 25px rgba(0,0,0,0.6);transition:transform 0.3s ease, box-shadow 0.3s ease;}
.card-dynamic:hover{transform:translateY(-5px);box-shadow:0 20px 40px rgba(0,0,0,0.8);}
.title-glow{text-shadow:0 0 6px rgba(255,255,255,0.3);}
/* Estilo para campos desabilitados no modal 'Ver' */
:disabled { background-color: #2a2a3f !important; opacity: 0.7; cursor: not-allowed; }
</style>
</head>
<body class="font-serif text-gray-100 min-h-screen relative overflow-hidden">
<?php for($i=0;$i<25;$i++): ?>
  <div class="particle" style="width:<?=rand(5,15)?>px;height:<?=rand(5,15)?>px;top:<?=rand(0,100)?>%;left:<?=rand(0,100)?>%;animation-duration:<?=rand(20,40)?>s;animation-delay:<?=rand(0,20)?>s;"></div>
<?php endfor; ?>

<div class="fade-in-page w-full px-4 py-10 flex flex-col items-center">
  <div class="bg-[#1f1f2f]/90 backdrop-blur-md p-8 rounded-3xl w-full max-w-6xl shadow-2xl space-y-6 card-dynamic border border-[#2a2a3f]/50">
    <h2 class="text-3xl font-bold tracking-wide text-center text-gray-200 title-glow">Gerenciamento de Imóveis</h2>

    <div class="flex flex-wrap gap-4 justify-center mb-3">
        <button onclick="abrirModal('cadastrar')" class="py-2 px-5 rounded-xl btn-glow shadow-md">Cadastrar Novo</button>
        <a href="../staffmenu.php" class="py-2 px-5 rounded-xl btn-glow shadow-md">Voltar</a>
    </div>

    <input type="text" id="search" placeholder="Procurar por título, tipo, proprietário ou cidade..." class="w-full mb-4 p-3 rounded-xl 
  bg-[#2a2a3f] text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#3a3a5a] shadow-inner"/>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-[#2a2a3f]">
            <th class="p-3 border border-[#3a3a5a]">ID</th>
            <th class="p-3 border border-[#3a3a5a]">Título</th> 
            <th class="p-3 border border-[#3a3a5a]">Tipo</th>
            <th class="p-3 border border-[#3a3a5a]">Status</th>
            <th class="p-3 border border-[#3a3a5a]">Valor</th>
            <th class="p-3 border border-[#3a3a5a]">Negociável</th>
            <th class="p-3 border border-[#3a3a5a]">Financiável</th>
            <th class="p-3 border border-[#3a3a5a]">Proprietário</th>
            <th class="p-3 border border-[#3a3a5a]">Cidade</th>
            <th class="p-3 border border-[#3a3a5a]">Ações</th>
          </tr>
        </thead>
        
        <tbody>
        <?php while($linha = $resultado->fetch_assoc()): ?>
          <tr class="bg-[#1f1f2f] hover:bg-[#2c2c44] transition">
            <td class="p-3 border border-[#2a2a3f]"><?= $linha['idIMOVEL'] ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['titulo'] ?? 'Sem Título') ?></td> 
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['tipo']) ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['status']) ?></td>
            <td class="p-3 border border-[#2a2a3f]">R$ <?= number_format($linha['valor'],2,',','.') ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['negociavel'] ?? 'Não') ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['financiavel'] ?? 'Não') ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['proprietario']) ?></td>
            <td class="p-3 border border-[#2a2a3f]"><?= htmlspecialchars($linha['cidade']) ?></td>
            <td class="p-3 border border-[#2a2a3f] flex gap-2">
              <button onclick="abrirModal('visualizar', <?= $linha['idIMOVEL'] ?>)" class="px-3 py-1 rounded-md btn-glow">Ver</button>
              <button onclick="abrirModal('editar', <?= $linha['idIMOVEL'] ?>)" class="px-3 py-1 rounded-md btn-glow">Editar</button>
              <button onclick="abrirModal('excluir', <?= $linha['idIMOVEL'] ?>)" class="px-3 py-1 rounded-md btn-glow">Excluir</button>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50">
  <div class="bg-[#1f1f2f] p-6 rounded-2xl w-11/12 max-w-4xl max-h-[90vh] overflow-y-auto relative card-dynamic border border-[#2a2a3f]/50">
    <h2 id="modal-title" class="text-xl font-bold mb-4 title-glow">Título</h2>
    <div id="modal-body" class="space-y-2"></div>
    <div class="flex justify-end gap-2 mt-4">
        <button onclick="fecharModal()" class="py-2 px-4 rounded-xl btn-glow">Cancelar</button>
        <button id="modal-confirm" class="py-2 px-4 rounded-xl btn-glow">Confirmar</button>
    </div>
    <button onclick="fecharModal()" class="absolute top-2 right-2 text-gray-400 hover:text-white">&times;</button>
  </div>
</div>

<script>
// Busca local atualizada para novos índices de coluna
$('#search').on('input', function(){
    let val = $(this).val().toLowerCase();
    $('table tbody tr').each(function(){
        // Índices atualizados: Título(2), Tipo(3), Proprietário(8), Cidade(9)
        const title = $(this).find('td:nth-child(2)').text().toLowerCase();
        const type = $(this).find('td:nth-child(3)').text().toLowerCase();
        const owner = $(this).find('td:nth-child(8)').text().toLowerCase();
        const city = $(this).find('td:nth-child(9)').text().toLowerCase();
        
        const textMatch = owner.indexOf(val) > -1 || city.indexOf(val) > -1 || type.indexOf(val) > -1 || title.indexOf(val) > -1;
        $(this).toggle(textMatch);
    });
});

// variáveis de estado para upload
let imagensSelecionadas = [];

// Constrói o formulário para 'cadastrar', 'editar' e 'visualizar'
const buildForm = (data = {}, readOnly = false, medias = []) => {
    // Usamos o operador de coalescência nula (??) para fornecer um valor padrão seguro
    const pSel = data.PROPRIETARIO_idPROPRIETARIO ?? '';
    const titulo = data.titulo ?? '';
    const tipo = data.tipo ?? '';
    const status = data.status ?? 'Disponivel';
    const rua = data.rua ?? '';
    const numero = data.numero ?? '';
    const bairro = data.bairro ?? '';
    const cidade = data.cidade ?? '';
    const estado = data.estado ?? '';
    const valor = data.valor ?? '';
    const descricao = data.descricao ?? '';
    const q_quartos = data.qtd_quartos ?? 0;
    const q_banheiro = data.qtd_banheiro ?? 0;
    const q_vagas = data.qtd_vagas ?? 0;
    const negociavel = data.negociavel ?? 'Não';
    const financiavel = data.financiavel ?? 'Não';

    // Flag para desabilitar campos no modo 'visualizar'
    const isDisabled = readOnly ? 'disabled' : '';

    let form = `<form id="form-imovel" enctype="multipart/form-data" class="space-y-3">
        <input type="hidden" name="id" value="${data.idIMOVEL ?? 0}">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">`;
    
    // Proprietário
    form += `<div>
        <label class="block mb-1">Proprietário</label>
        <select name="proprietario" class="w-full p-2 rounded-xl bg-[#2a2a3f] text-gray-200" ${isDisabled}>`;
    <?php foreach($proprietarios as $pid=>$pname): ?>
    form += `<option value="<?= $pid ?>" ${pSel == '<?= $pid ?>' ? 'selected' : ''}><?= htmlspecialchars($pname) ?></option>`;
    <?php endforeach; ?>
    form += `</select></div>`;

    // Título do imóvel
    form += `<div>
        <label class="block mb-1">Título</label>
        <input type="text" name="titulo" value="${escapeHtml(titulo)}" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}>
    </div>`;

    // Tipo
    form += `<div><label class="block mb-1">Tipo</label><input type="text" name="tipo" value="${escapeHtml(tipo)}" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}></div>`;

    // Status
    form += `<div><label class="block mb-1">Status</label>
                <select name="status" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}>
                    <option value="Disponivel" ${status==='Disponivel'?'selected':''}>Disponível</option>
                    <option value="Vendido" ${status==='Vendido'?'selected':''}>Vendido</option>
                </select></div>`;
    // Valor
    form += `<div><label class="block mb-1">Valor</label><input type="number" step="0.01" name="valor" value="${valor}" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}></div>`;
    // Rua
    form += `<div><label class="block mb-1">Rua</label><input type="text" name="rua" value="${escapeHtml(rua)}" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}></div>`;
    // Numero
    form += `<div><label class="block mb-1">Número</label><input type="text" name="numero" value="${escapeHtml(numero)}" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}></div>`;
    // Bairro
    form += `<div><label class="block mb-1">Bairro</label><input type="text" name="bairro" value="${escapeHtml(bairro)}" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}></div>`;
    // Cidade
    form += `<div><label class="block mb-1">Cidade</label><input type="text" name="cidade" value="${escapeHtml(cidade)}" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}></div>`;
    // Estado
    form += `<div><label class="block mb-1">Estado</label><input type="text" name="estado" maxlength="50" value="${escapeHtml(estado)}" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}></div>`;
    
    // Negociável
    form += `<div><label class="block mb-1">Negociável</label>
                <select name="negociavel" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}>
                  <option value="Sim" ${negociavel==='Sim'?'selected':''}>Sim</option>
                  <option value="Não" ${negociavel==='Não'?'selected':''}>Não</option>
                </select></div>`;
    // Financiável
    form += `<div><label class="block mb-1">Financiável</label>
                <select name="financiavel" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}>
                  <option value="Sim" ${financiavel==='Sim'?'selected':''}>Sim</option>
                  <option value="Não" ${financiavel==='Não'?'selected':''}>Não</option>
                </select></div>`;
    
    // Quartos
    form += `<div><label class="block mb-1">Quartos</label><input type="number" name="qtd_quartos" value="${q_quartos}" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}></div>`;
    // Banheiros
    form += `<div><label class="block mb-1">Banheiros</label><input type="number" name="qtd_banheiro" value="${q_banheiro}" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}></div>`;
    // Vagas
    form += `<div><label class="block mb-1">Vagas</label><input type="number" name="qtd_vagas" value="${q_vagas}" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}></div>`;

    form += `</div>`; // fim grid

    // Descrição
    form += `<div class="md:col-span-2">
                <label class="block mb-1">Descrição</label>
                <textarea name="descricao" rows="3" class="w-full p-2 rounded-xl bg-[#2a2a3f]" ${isDisabled}>${escapeHtml(descricao)}</textarea>
            </div>`;
    
    // Mídias (Upload e Preview) - ADICIONADO CONTADOR
    form += `<div>
                <label class="block mb-1">Mídias (${readOnly ? medias.length : `Máx. 7 total, 2 vídeos`})</label>
                <input type="file" id="input-imagens" name="imagens[]" multiple accept="image/*,video/*" class="w-full p-2 rounded-xl bg-[#2a2a3f]">
                <div id="media-count" class="text-sm text-gray-400 mt-1"></div>
                <div id="preview-imagens" class="flex flex-wrap gap-2 mt-2"></div>
            </div>`;
            
    form += `</form>`;
    $('#modal-body').html(form);

    // Lógica de preview de mídias e 'readOnly'
    if (readOnly) {
        $('#input-imagens').hide();
    } else {
         $('#input-imagens').show();
    }
    
    // Mostra as mídias já existentes (para 'editar' e 'visualizar')
    let preview = $('#preview-imagens');
    preview.html(''); // Limpa preview para evitar duplicidade
    
    // Filtra o placeholder se houver mídias reais (melhoria)
    let realMedias = medias.filter(m => strpos(m.caminho, 'placeholder.jpg') === -1);

    if (realMedias.length > 0) {
        realMedias.forEach(m => {
            let ext = m.caminho.split('.').pop().toLowerCase();
            // Adiciona classe para identificar que é um item EXISTENTE
            const container = $(`<div class="relative preview-existente"></div>`);
            if (['mp4','webm','ogg'].includes(ext)) {
                // CORREÇÃO DE URL: Garantir que a URL da mídia do DB seja usada corretamente
                container.append(`<video class="w-32 h-20 object-cover rounded-md" controls src="${m.caminho}"></video>`);
            } else {
                container.append(`<img src="${m.caminho}" class="w-32 h-20 object-cover rounded-md">`);
            }
            preview.append(container);
        });
    } else if (medias.length === 1 && strpos(medias[0].caminho, 'placeholder.jpg') !== -1) {
        // Se só tem o placeholder, mostra ele
        const container = $(`<div class="relative preview-existente"></div>`);
        container.append(`<img src="${medias[0].caminho}" class="w-32 h-20 object-cover rounded-md opacity-50" title="Placeholder: Nenhuma imagem cadastrada">`);
        preview.append(container);
    }

    // Liga o input de arquivos
    $('#input-imagens').off('change').on('change', function(){
        // Previne limite: Se já passou, não adiciona mais arquivos
        const existingCount = preview.children('.preview-existente').length;
        const currentNewCount = imagensSelecionadas.length;
        const max_total = 7;
        
        for (let i=0;i<this.files.length;i++) {
            if ((existingCount + currentNewCount + (i + 1)) <= max_total) {
                imagensSelecionadas.push(this.files[i]);
            }
        }
        this.value = null; 
        atualizarPreview(existingCount); 
    });
    
    // Chama o preview inicial para configurar o contador
    atualizarPreview(realMedias.length);
};


// abre modal para as ações
function abrirModal(acao, id=0){
    $('#modal').removeClass('hidden');
    $('#modal-title').text(acao.charAt(0).toUpperCase()+acao.slice(1) + (id ? ` #${id}` : ''));
    $('#modal-body').html('<p class="text-center text-gray-400">Carregando...</p>');
    $('#modal-confirm').show();
    imagensSelecionadas = []; // Zera a lista de novas imagens

    if (acao === 'cadastrar') {
        $('#modal-title').text('Cadastrar Novo Imóvel');
        buildForm({}, false, []); // Chama form vazio, editável, sem mídias
        
        // Ação do botão confirmar para CADASTRAR
        $('#modal-confirm').off('click').on('click', function(){
            enviarFormulario(acao);
        });

    } else if (acao === 'editar' || acao === 'visualizar') {
        // buscar dados do imóvel para popular o form
        $.post('', {acao:'visualizar', id: id}, function(res){
            let resp;
            try { 
                resp = typeof res === 'string' ? JSON.parse(res) : res; 
            } catch(e){
                alert('Erro: A resposta do servidor não é um JSON válido! Verifique o console (F12) para a resposta bruta.');
                console.log("Resposta com erro recebida (não JSON):", res);
                fecharModal();
                return;
            }
            
            if (resp.status === 'sucesso') {
                const readOnly = acao === 'visualizar';
                const medias = resp.medias || [];
                buildForm(resp.imovel, readOnly, medias); 
                
                if (readOnly) {
                     $('#modal-confirm').hide(); // Esconde o botão de confirmar para visualizar
                } else {
                    // Ação do botão confirmar para EDITAR
                    $('#modal-confirm').show().off('click').on('click', function(){
                        enviarFormulario(acao, id);
                    });
                }
            } else {
                alert(resp.mensagem || 'Erro ao carregar imóvel');
                fecharModal();
            }
        });

    } else if (acao === 'excluir') {
        $('#modal-body').html(`<p>Deseja realmente excluir o imóvel #${id}?</p>`);
        
        // Ação do botão confirmar para EXCLUIR
        $('#modal-confirm').off('click').on('click', function(){
            $.post('', {acao:'excluir', id: id}, function(resp){
                let res;
                try { res = typeof resp === 'string' ? JSON.parse(resp) : resp; } catch(e){}
                if (res.status === 'sucesso') location.reload();
                else alert(res.mensagem || 'Erro ao excluir');
            });
        });
    }
}

// Função centralizada para enviar formulário (Cadastrar/Editar)
function enviarFormulario(acao, id = 0) {
    const fd = new FormData($('#form-imovel')[0]);
    
    // Adiciona as imagens SELECIONADAS (apenas as novas) ao FormData
    imagensSelecionadas.forEach(f => fd.append('imagens[]', f));
    
    fd.append('acao', acao);
    if (acao === 'editar') {
        fd.append('id', id); 
    }

    $.ajax({
        url: '',
        method: 'POST',
        data: fd,
        contentType: false,
        processData: false,
        success: function(resp){
            let res;
            try { 
                res = typeof resp === 'string' ? JSON.parse(resp) : resp; 
            } catch(e){
                alert('Erro inesperado: A resposta do servidor não é um JSON válido. Verifique se há Warnings/Notices no PHP.');
                console.log("Resposta bruta do servidor:", resp);
                return;
            }
            
            // Se houver status de erro, mostra a mensagem detalhada
            if (res.status === 'erro') {
                 alert(res.mensagem); 
            } else if (res.status === 'sucesso') { 
                 // Se houver aviso de falha de I/O, mostra no alert, senão só recarrega.
                 if (res.mensagem.includes('ATENÇÃO / ERROS')) {
                      alert(res.mensagem);
                 }
                 location.reload();
            } else {
                 alert('Erro desconhecido ao salvar.');
            }
        },
        error: function(xhr, status, error){ 
            alert('Erro na requisição AJAX: ' + error + '. Verifique a conexão e o console (F12).'); 
        }
    });
}


// Atualiza preview e o contador de mídias (NOVAS e EXISTENTES)
function atualizarPreview(existingCount = 0){
    const preview = $('#preview-imagens');
    
    // Se não foi passado o valor, tenta contar os elementos existentes no DOM
    if (existingCount === 0) {
        existingCount = preview.children('.preview-existente').length;
    }
    
    // Remove APENAS os previews das imagens recém-selecionadas (os que têm o botão de remover)
    preview.find('.preview-novo').remove();

    imagensSelecionadas.forEach((file, idx) => {
        // Checa tipo do arquivo
        const isVideo = file.type.startsWith('video/');
        
        const reader = new FileReader();
        reader.onload = function(e){
            const container = $(`<div class="relative preview-novo"></div>`);
            
            if (isVideo) {
                container.append(`<video class="w-32 h-20 object-cover rounded-md" src="${e.target.result}" controls></video>`);
            } else {
                container.append(`<img src="${e.target.result}" class="w-32 h-20 object-cover rounded-md">`);
            }
            
            // Botão de remoção
            const btn = $(`<button type="button" class="absolute top-0 right-0 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">×</button>`);
            
            btn.on('click', function(){ 
                // Remove o arquivo do array pelo índice (IMPORTANTE!)
                imagensSelecionadas.splice(idx, 1);
                // Chama a função de novo para RE-RENDERIZAR (isso corrige o problema de índice)
                atualizarPreview(existingCount);
            });
            
            container.append(btn);
            preview.append(container);
        };
        reader.readAsDataURL(file);
    });
    
    // --- ATUALIZAÇÃO DO CONTADOR ---
    const newCount = imagensSelecionadas.length;
    const total = existingCount + newCount;
    $('#media-count').text(`Imagens existentes: ${existingCount} | Novas selecionadas: ${newCount} | Total: ${total} (Máx: 7)`);

    // Desabilita input se atingir o limite
    if (total >= 7) {
        // Limpa o input file para que não tente enviar arquivos extras
        $('#input-imagens').prop('disabled', true).val(''); 
        $('#media-count').append(' <span class="text-red-400">Limite total de mídias (7) atingido!</span>');
    } else {
         $('#input-imagens').prop('disabled', false);
    }
}

function fecharModal(){
    $('#modal').addClass('hidden');
    $('#modal-body').empty();
    $('#modal-confirm').off('click').show();
}

// util helpers JS
function escapeHtml(str){
    if(!str && str !== 0) return '';
    return String(str).replace(/[&<>"'`=\/]/g, function(s){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;','`':'&#x60;','=':'&#x3D;','/':'&#x2F;'}[s]; });
}
// strpos para JS (simulação)
function strpos(haystack, needle, offset) {
    var i = (haystack + '').indexOf(needle, (offset || 0));
    return i === -1 ? false : i;
}
</script>
</body>
</html>
