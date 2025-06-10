<?php
// Inclua a conexão com o banco de dados
include('../config//config.php');

// Verifica se o usuário está logado
session_start();

// Limpa alertas antigos
if (isset($_SESSION['alertas'])) {
    unset($_SESSION['alertas']);
}

if (!isset($_SESSION['id_cliente'])) {
    header('Location: ../pages/login.php?error=1');
    exit();
}

// Pega o ID do cliente da sessão
$id_cliente = $_SESSION['id_cliente'];

// Buscar dados do cliente no banco
$stmt = $pdo->prepare("SELECT * FROM Clientes WHERE id_cliente = ?");
$stmt->execute([$id_cliente]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar animais do cliente
$stmt_animais = $pdo->prepare("SELECT * FROM Animais WHERE id_cliente = ?");
$stmt_animais->execute([$id_cliente]);
$animais = $stmt_animais->fetchAll(PDO::FETCH_ASSOC);

// Buscar agendamentos do cliente com informações do serviço
$stmt_agendamentos = $pdo->prepare("
    SELECT a.id_agendamento, a.data_hora, a.status, a.valor, a.id_servico, s.nome AS servico_nome
    FROM Agendamentos a
    LEFT JOIN Servicos s ON a.id_servico = s.id_servico
    WHERE a.id_animal IN (SELECT id_animal FROM Animais WHERE id_cliente = ?)
    ORDER BY a.data_hora DESC
");
$stmt_agendamentos->execute([$id_cliente]);
$agendamentos = $stmt_agendamentos->fetchAll(PDO::FETCH_ASSOC);

// Processa troca de senha via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['senha_antiga'], $_POST['nova_senha'], $_POST['confirma_senha'])) {
    $senha_antiga = $_POST['senha_antiga'];
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    // Buscar hash da senha atual
    $stmt = $pdo->prepare("SELECT senha FROM Clientes WHERE id_cliente = ?");
    $stmt->execute([$id_cliente]);
    $clienteSenha = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$clienteSenha || !password_verify($senha_antiga, $clienteSenha['senha'])) {
        $_SESSION['alertas'] = [
            'tipo' => 'danger',
            'mensagem' => "Senha antiga incorreta."
        ];
        header("Location: perfil_cliente.php");
        exit();
    } elseif ($nova_senha !== $confirma_senha) {
        $_SESSION['alertas'] = [
            'tipo' => 'danger',
            'mensagem' => "Nova senha e confirmação não coincidem."
        ];
        header("Location: perfil_cliente.php");
        exit();
    } else {
        // Atualiza a senha
        $nova_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmtUpdate = $pdo->prepare("UPDATE Clientes SET senha = ? WHERE id_cliente = ?");
        if ($stmtUpdate->execute([$nova_hash, $id_cliente])) {
            $_SESSION['alertas'] = [
                'tipo' => 'success',
                'mensagem' => "Senha alterada com sucesso!"
            ];
            header("Location: perfil_cliente.php");
            exit();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['acao'])) {
        // Cadastro de novo pet
        if ($_POST['acao'] == 'cadastrar_pet') {
            $nome_animal = $_POST['nome_animal'];
            $tipo_animal = $_POST['tipo_animal'];
            $raca_animal = $_POST['raca_animal'];
            $idade_animal = $_POST['idade_animal'];
            $peso_animal = $_POST['peso_animal'];
            $foto_animal = ''; // Inicializa a variável para o nome da foto

            // Processar upload da foto
            if (isset($_FILES['foto_animal']) && $_FILES['foto_animal']['error'] === UPLOAD_ERR_OK) {
                $extensao = pathinfo($_FILES['foto_animal']['name'], PATHINFO_EXTENSION);
                $nomeFoto = "pet_" . uniqid() . "." . $extensao;
                $caminhoDestino = "../assets/img/foto_pet/" . $nomeFoto;
                
                if (move_uploaded_file($_FILES['foto_animal']['tmp_name'], $caminhoDestino)) {
                    $foto_animal = $nomeFoto;
                }
            }

            // Inserir no banco de dados (agora incluindo a foto)
            $stmt_pet = $pdo->prepare("INSERT INTO Animais (id_cliente, nome, tipo, raca, idade, peso, foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_pet->execute([$id_cliente, $nome_animal, $tipo_animal, $raca_animal, $idade_animal, $peso_animal, $foto_animal]);

            // Redirecionar após cadastro
            header('Location: perfil_cliente.php');
            exit();
        }
    }
}

// Processar o endereço
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['endereco'])) {
    $novo_endereco = trim($_POST['endereco']);
    
    if (empty($novo_endereco)) {
        $_SESSION['alertas'] = [
            'tipo' => 'danger',
            'mensagem' => "O endereço não pode estar vazio."
        ];
    } else {
        $stmt_update = $pdo->prepare("UPDATE Clientes SET endereco = ? WHERE id_cliente = ?");
        if ($stmt_update->execute([$novo_endereco, $id_cliente])) {
            $_SESSION['alertas'] = [
                'tipo' => 'success',
                'mensagem' => "Endereço atualizado com sucesso!"
            ];
        } else {
            $_SESSION['alertas'] = [
                'tipo' => 'danger',
                'mensagem' => "Erro ao atualizar endereço."
            ];
        }
    }
    header("Location: perfil_cliente.php");
    exit();
}

// Processar o telefone
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['telefone'])) {
    $novo_telefone = trim($_POST['telefone']);
    
    // Validar o telefone (formato básico: 11 dígitos)
    if (preg_match("/^[0-9]{10,11}$/", $novo_telefone)) {
        $stmt_update = $pdo->prepare("UPDATE Clientes SET telefone = ? WHERE id_cliente = ?");
        if ($stmt_update->execute([$novo_telefone, $id_cliente])) {
            $_SESSION['alertas'] = [
                'tipo' => 'success',
                'mensagem' => "Telefone atualizado com sucesso!"
            ];
            header("Location: perfil_cliente.php");
            exit();
        }
    } else {
        $_SESSION['alertas'] = [
            'tipo' => 'danger',
            'mensagem' => "Telefone inválido. Use apenas números (10 ou 11 dígitos)."
        ];
        header("Location: perfil_cliente.php");
        exit();
    }
}

// Processar o CPF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cpf'])) {
    $novo_cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    
    // Verifica se o CPF já existe (exceto para o próprio usuário)
    $stmt_check = $pdo->prepare("SELECT id_cliente FROM Clientes WHERE cpf = ? AND id_cliente != ?");
    $stmt_check->execute([$novo_cpf, $id_cliente]);
    
    if ($stmt_check->rowCount() > 0) {
        $_SESSION['alertas'] = [
            'tipo' => 'danger',
            'mensagem' => 'Este CPF já está cadastrado para outro cliente.'
        ];
    } elseif (strlen($novo_cpf) !== 11 || !validarCPF($novo_cpf)) {
        $_SESSION['alertas'] = [
            'tipo' => 'danger',
            'mensagem' => 'CPF inválido. Por favor, verifique o número.'
        ];
    } else {
        $stmt_update = $pdo->prepare("UPDATE Clientes SET cpf = ? WHERE id_cliente = ?");
        if ($stmt_update->execute([$novo_cpf, $id_cliente])) {
            $_SESSION['alertas'] = [
                'tipo' => 'success',
                'mensagem' => 'CPF atualizado com sucesso!'
            ];
        }
    }
    header("Location: perfil_cliente.php");
    exit();
}

// Função para validar CPF no PHP
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

// Processar o upload de foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    $foto = $_FILES['foto'];

    // Verificar se a imagem foi carregada corretamente
    if ($foto['error'] === UPLOAD_ERR_OK) {
        $extensao = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $nomeFoto = "foto_" . $id_cliente . "." . $extensao;
        $caminhoDestino = "../assets/img/foto_cliente/" . $nomeFoto;

        // Mover o arquivo para o diretório
        if (move_uploaded_file($foto['tmp_name'], $caminhoDestino)) {
            // Atualizar o caminho da foto no banco de dados
            $stmt = $pdo->prepare("UPDATE Clientes SET foto = ? WHERE id_cliente = ?");
            if ($stmt->execute([$nomeFoto, $id_cliente])) {
                $_SESSION['alertas'] = [
                    'tipo' => 'success',
                    'mensagem' => "Foto atualizada com sucesso!"
                ];
                header("Location: perfil_cliente.php");
                exit();
            }
        } else {
            $_SESSION['alertas'] = [
                'tipo' => 'danger',
                'mensagem' => "Erro ao carregar a foto. Tente novamente."
            ];
            header("Location: perfil_cliente.php");
            exit();
        }
    } else {
        $_SESSION['alertas'] = [
            'tipo' => 'danger',
            'mensagem' => "Erro no upload da foto. Verifique o formato e tente novamente."
        ];
        header("Location: perfil_cliente.php");
        exit();
    }
}

// Processar alteração de informações do pet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar_pet') {
    $id_animal = $_POST['id_animal'];
    $nome = $_POST['nome_animal'];
    $tipo = $_POST['tipo_animal'];
    $raca = $_POST['raca_animal'];
    $idade = $_POST['idade_animal'];
    $peso = $_POST['peso_animal'];
    $foto_atual = $_POST['foto_atual'] ?? '';
    $foto_animal = $foto_atual;

    // Verificar se o pet pertence ao cliente
    $stmt_verifica = $pdo->prepare("SELECT id_animal, foto FROM Animais WHERE id_animal = ? AND id_cliente = ?");
    $stmt_verifica->execute([$id_animal, $id_cliente]);
    $pet = $stmt_verifica->fetch(PDO::FETCH_ASSOC);
    
    if ($pet) {
        // Processar upload da nova foto
        if (isset($_FILES['foto_animal']) && $_FILES['foto_animal']['error'] === UPLOAD_ERR_OK) {
            // Se já existia uma foto, deleta a antiga
            if (!empty($pet['foto']) && file_exists("../assets/img/foto_pet/" . $pet['foto'])) {
                unlink("../assets/img/foto_pet/" . $pet['foto']);
            }
            
            $extensao = pathinfo($_FILES['foto_animal']['name'], PATHINFO_EXTENSION);
            $nomeFoto = "pet_" . uniqid() . "." . $extensao;
            $caminhoDestino = "../assets/img/foto_pet/" . $nomeFoto;
            
            if (move_uploaded_file($_FILES['foto_animal']['tmp_name'], $caminhoDestino)) {
                $foto_animal = $nomeFoto;
            }
        }

        $stmt_update = $pdo->prepare("UPDATE Animais SET nome = ?, tipo = ?, raca = ?, idade = ?, peso = ?, foto = ? WHERE id_animal = ?");
        if ($stmt_update->execute([$nome, $tipo, $raca, $idade, $peso, $foto_animal, $id_animal])) {
            $_SESSION['alertas'] = [
                'tipo' => 'success',
                'mensagem' => "Informações do pet atualizadas com sucesso!"
            ];
        } else {
            $_SESSION['alertas'] = [
                'tipo' => 'danger',
                'mensagem' => "Erro ao atualizar informações do pet."
            ];
        }
    } else {
        $_SESSION['alertas'] = [
            'tipo' => 'danger',
            'mensagem' => "Pet não encontrado ou não pertence a você."
        ];
    }
    header("Location: perfil_cliente.php");
    exit();
}

// Definir cabeçalhos para evitar cache no navegador
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuário</title>
    <link rel="shortcut icon" href="../assets/img/ícones/logo.png" type="image/x-icon">
    
    <!-- Link para o CSS da página -->
    <link rel="stylesheet" href="../assets/css/paginas/perfil.css?=v2">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Links para o Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    
    <!-- Estilo para os toasts -->
    <style>
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>

<!-- Barra de navegação -->
<nav class="navbar navbar-expand-lg" id="navbar-top">
  <div class="container-fluid">
    <a href="../index.php"><img src="../assets/img/ícones/logo.png" alt="logo" style="border-radius: 50%; width: 100px;"></a>
    <a class="navbar-brand" href="#"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link active" aria-current="page" href="../index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../index.php#sobre">Sobre</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../index.php#servicos">Serviços</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../index.php#depoimentos">Depoimentos</a>
        </li>
        
        <!-- Adicionando o item de Agendamentos se o usuário estiver logado -->
        <?php if (isset($_SESSION['id_cliente'])): ?>
          <li class="nav-item">
            <a class="nav-link" href="../pages/agendamentos.php">Agendamentos</a>
          </li>
        <?php endif; ?>

      </ul>
     <!-- Verifica se o usuário está logado -->
     <div class="login-box">
        <?php if (isset($_SESSION['id_cliente'])): ?>
          <!-- Menu Dropdown para o nome do cliente -->
          <div class="dropdown">
            <button class="btn btn-link dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: white;">
              <?php echo $_SESSION['nome']; ?>
            </button>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
              <li><a class="dropdown-item" href="perfil_cliente.php">Perfil</a></li>
              <li><a class="dropdown-item" href="../actions/logout.php">Sair</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="pages/login.php"><button type="submit">Entrar</button></a>
          <a href="pages/registrar.php"><button type="submit">Registrar</button></a>
        <?php endif; ?>
  </div>
</nav>

<!-- Conteúdo do perfil -->
<main>
    <div class="container py-5">
        <h1>Perfil de Usuário</h1>

        <!-- Toast Notification -->
        <?php if (isset($_SESSION['alertas'])): ?>
        <div class="toast align-items-center text-white bg-<?php echo $_SESSION['alertas']['tipo']; ?>" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
            <div class="d-flex">
                <div class="toast-body">
                    <?php echo $_SESSION['alertas']['mensagem']; ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Seção de Informações do Usuário -->
          <div class="row">
              <div class="col-md-6">
                  <section>
                  <div>
                  <label for="foto"><i class="fas fa-user-circle"></i> Foto de Perfil:</label><br>
    <!-- Foto atual do cliente -->
    <img id="foto" 
     src="<?php echo !empty($cliente['foto']) ? '../assets/img/foto_cliente/' . htmlspecialchars($cliente['foto']) : 'https://via.placeholder.com/150'; ?>" 
     alt="Foto de Perfil" 
     class="foto-perfil" 
     style="cursor: pointer;" 
     onclick="document.getElementById('uploadFoto').click();">
    
    <!-- Formulário de upload de foto -->
    <form method="POST" enctype="multipart/form-data" id="formUploadFoto" style="display: none;">
        <input type="file" name="foto" id="uploadFoto" accept="image/*" onchange="this.form.submit();" style="display: none;">
    </form>
</div>
                      <div>
                      <label for="nome"><i class="fas fa-user"></i> Nome:</label>
                          <p id="nome"><?php echo htmlspecialchars($cliente['nome']); ?></p>
                      </div>
                      <div>
                      <div>
                      <div class="mb-3">
                      <label for="cpf"><i class="fas fa-id-card"></i> CPF:</label>
    <p id="cpfTexto" class="mb-1">
        <?php 
        if (!empty($cliente['cpf'])) {
            $cpf = $cliente['cpf'];
            echo substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9);
        } else {
            echo 'Não cadastrado';
        }
        ?>
    </p>
    <input type="text" name="cpf" id="cpfInput" class="form-control mb-2" 
           style="display: none;"
           pattern="\d{3}\.\d{3}\.\d{3}-\d{2}"
           title="Formato: 000.000.000-00"
           value="<?php echo !empty($cliente['cpf']) ? 
                  substr($cliente['cpf'], 0, 3) . '.' . 
                  substr($cliente['cpf'], 3, 3) . '.' . 
                  substr($cliente['cpf'], 6, 3) . '-' . 
                  substr($cliente['cpf'], 9) : ''; ?>">
    
    <div>
    <button id="editarCpfBtn" class="btn btn-warning btn-sm" <?php echo !empty($cliente['cpf']) ? 'style="display:none;"' : ''; ?>>
    <i class="fas fa-plus"></i> Adicionar CPF
</button>
<button id="salvarCpfBtn" class="btn btn-success btn-sm" style="display: none;">
    <i class="fas fa-check"></i> Salvar
</button>
<button id="cancelarCpfBtn" class="btn btn-secondary btn-sm" style="display: none;">
    <i class="fas fa-times"></i> Cancelar
</button>
    </div>
</div>

<form method="POST" id="formCpf" style="display: none;">
    <input type="hidden" name="cpf" id="campoCpfFinal">
</form>

<div id="sucessoCpf" class="alert alert-success" style="display: none;">CPF atualizado com sucesso!</div>

                    <div>
                    <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                        <p id="email"><?php echo htmlspecialchars($cliente['email']); ?></p>
                    </div>
                    <div class="mb-3">
                    <label for="endereco"><i class="fas fa-map-marker-alt"></i> Endereço:</label>
    <p id="enderecoTexto" class="mb-1">
        <?php echo !empty($cliente['endereco']) ? htmlspecialchars($cliente['endereco']) : 'Não cadastrado'; ?>
    </p>
    <input type="text" name="endereco" id="enderecoInput" class="form-control mb-2" 
           style="display: none;" 
           value="<?php echo htmlspecialchars($cliente['endereco'] ?? ''); ?>">
    
    <div>
    <button id="editarEnderecoBtn" class="btn btn-warning btn-sm">
    <i class="fas fa-edit"></i> 
    <?php echo empty($cliente['endereco']) ? 'Adicionar endereço' : 'Alterar endereço'; ?>
</button>
<button id="salvarEnderecoBtn" class="btn btn-success btn-sm" style="display: none;">
    <i class="fas fa-check"></i> Salvar
</button>
<button id="cancelarEnderecoBtn" class="btn btn-secondary btn-sm" style="display: none;">
    <i class="fas fa-times"></i> Cancelar
</button>
    </div>
</div>

<form method="POST" id="formEndereco" style="display: none;">
    <input type="hidden" name="endereco" id="campoEnderecoFinal">
</form>

<div class="mb-3">
<label for="telefone"><i class="fas fa-phone"></i> Telefone:</label>
    <p id="telefoneTexto" class="mb-1">
        <?php echo !empty($cliente['telefone']) ? htmlspecialchars($cliente['telefone']) : 'Não cadastrado'; ?>
    </p>
    
    <input type="tel" name="telefone" id="telefoneInput" class="form-control mb-2"
           style="display: none;"
           pattern="[0-9]{10,11}" 
           title="Digite apenas números (10 ou 11 dígitos)"
           value="<?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?>">

    <div>
    <button id="editarTelefoneBtn" class="btn btn-warning btn-sm">
    <i class="fas fa-edit"></i> 
    <?php echo empty($cliente['telefone']) ? 'Adicionar telefone' : 'Alterar telefone'; ?>
</button>
<button id="salvarTelefoneBtn" class="btn btn-success btn-sm" style="display: none;">
    <i class="fas fa-check"></i> Salvar
</button>
<button id="cancelarTelefoneBtn" class="btn btn-secondary btn-sm" style="display: none;">
<i class="fas fa-times"></i> Cancelar
    </div>
</div>

<form method="POST" id="formTelefone" style="display: none;">
    <input type="hidden" name="telefone" id="campoTelefoneFinal">
</form>

<div id="sucessoTelefone" class="alert alert-success" style="display: none;">Telefone atualizado com sucesso!</div>


                    <!-- Botão para alterar senha -->
                    <!-- Botão para abrir modal de alterar senha -->
                    <div class="mt-4 pt-3 border-top">
    <h5>Segurança</h5>
    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#alterarSenhaModal">
    <i class="fas fa-key"></i> Alterar senha
</button>
</div>

<!-- Modal -->
<div class="modal fade" id="alterarSenhaModal" tabindex="-1" aria-labelledby="alterarSenhaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content" id="formSenha">
      <div class="modal-header">
        <h5 class="modal-title" id="alterarSenhaModalLabel">Alterar senha</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="senha_antiga" class="form-label">Senha Antiga</label>
          <input type="password" class="form-control" id="senha_antiga" name="senha_antiga" required>
        </div>
        <div class="mb-3">
          <label for="nova_senha" class="form-label">Nova Senha</label>
          <input type="password" class="form-control" id="nova_senha" name="nova_senha" required>
        </div>
        <div class="mb-3">
          <label for="confirma_senha" class="form-label">Confirmar Nova Senha</label>
          <input type="password" class="form-control" id="confirma_senha" name="confirma_senha" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>
                </section>
            </div>

            <!-- Seção de Meus Pets -->
            <div class="col-md-6">
                <section>
                    <h2>Meus Pets</h2>
                    <!-- Tabela com os Pets cadastrados -->
                    <table class="table">
    <thead>
        <tr>
            <th>Foto</th>
            <th>ID</th>
            <th>Nome</th>
            <th>Espécie</th>
            <th>Idade</th>
            <th>Peso</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($animais as $animal): ?>
        <tr>
        <td class="text-center">
    <?php if (!empty($animal['foto'])): ?>
        <img src="../assets/img/foto_pet/<?= htmlspecialchars($animal['foto']) ?>" alt="Foto do pet"
             class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
    <?php else: ?>
        <img src="https://via.placeholder.com/50" alt="Sem foto"
             class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
    <?php endif; ?>
</td>
            <td><?= $animal['id_animal'] ?></td>
            <td><?= htmlspecialchars($animal['nome']) ?></td>
            <td><?= htmlspecialchars($animal['tipo']) ?></td>
            <td><?= htmlspecialchars($animal['idade']) ?></td>
            <td><?= htmlspecialchars($animal['peso']) ?></td>
            <td>
            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalEditarPet<?= $animal['id_animal'] ?>">
    <i class="fas fa-edit"></i> Alterar Informações
</button>
            </td>
        </tr>
<!-- Modal de Edição para cada pet -->
<div class="modal fade" id="modalEditarPet<?= $animal['id_animal'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar <?= htmlspecialchars($animal['nome']) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="acao" value="editar_pet">
          <input type="hidden" name="id_animal" value="<?= $animal['id_animal'] ?>">
          <input type="hidden" name="foto_atual" value="<?= htmlspecialchars($animal['foto'] ?? '') ?>">

          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa-solid fa-image"></i></span>
            <?php if (!empty($animal['foto'])): ?>
              <img src="../assets/img/foto_pet/<?= htmlspecialchars($animal['foto']) ?>" alt="Foto do pet" style="max-width: 100px; display: block; margin-bottom: 10px;">
            <?php endif; ?>
            <input type="file" class="form-control" name="foto_animal">
          </div>

          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa-solid fa-dog"></i></span>
            <input type="text" class="form-control" name="nome_animal" value="<?= htmlspecialchars($animal['nome']) ?>" required>
          </div>

          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa-solid fa-paw"></i></span>
            <select class="form-select" name="tipo_animal" required>
              <option value="Cachorro" <?= $animal['tipo'] === 'Cachorro' ? 'selected' : '' ?>>Cachorro</option>
              <option value="Gato" <?= $animal['tipo'] === 'Gato' ? 'selected' : '' ?>>Gato</option>
            </select>
          </div>

          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa-solid fa-dna"></i></span>
            <input type="text" class="form-control" name="raca_animal" value="<?= htmlspecialchars($animal['raca']) ?>" required>
          </div>

          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa-solid fa-calendar-days"></i></span>
            <input type="number" class="form-control" name="idade_animal" value="<?= htmlspecialchars($animal['idade']) ?>" required>
          </div>

          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa-solid fa-weight-scale"></i></span>
            <input type="number" step="0.1" class="form-control" name="peso_animal" value="<?= htmlspecialchars($animal['peso']) ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fa-solid fa-xmark"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> Salvar Alterações
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
        <?php endforeach; ?>
    </tbody>
</table>
<button id="btn-cadastrar-pet" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCadastrarPet">
    <i class="fas fa-plus"></i> Cadastrar Pet
</button>
  <!-- Modal de Cadastrar Pet -->
<div class="modal fade" id="modalCadastrarPet" tabindex="-1" aria-labelledby="modalCadastrarPetLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCadastrarPetLabel">Cadastrar Novo Pet</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="acao" value="cadastrar_pet">

          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa-solid fa-dog"></i></span>
            <input type="text" class="form-control" name="nome_animal" placeholder="Nome do Animal" required>
          </div>

          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa-solid fa-paw"></i></span>
            <select name="tipo_animal" class="form-select" required>
              <option value="Cachorro">Cachorro</option>
              <option value="Gato">Gato</option>
            </select>
          </div>

          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa-solid fa-dna"></i></span>
            <input type="text" class="form-control" name="raca_animal" placeholder="Raça" required>
          </div>

          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa-solid fa-calendar-days"></i></span>
            <input type="number" class="form-control" name="idade_animal" placeholder="Idade" required>
          </div>

          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa-solid fa-weight-scale"></i></span>
            <input type="number" step="0.1" class="form-control" name="peso_animal" placeholder="Peso (kg)" required>
          </div>

          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa-solid fa-image"></i></span>
            <input type="file" class="form-control" name="foto_animal" accept="image/*">
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-check"></i> Cadastrar
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
                </section>
            </div>
        </div>
<!-- Seção de Meus Agendamentos -->
<div class="row">
    <div class="col-md-12">
        <section>
            <h2>Meus Agendamentos</h2>
            <!-- Tabela com os Agendamentos -->
            <table class="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Serviço</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Valor</th>
                        <th>Nota</th>
                    </tr>
                </thead>
                <tbody>
    <?php foreach ($agendamentos as $agendamento): ?>
    <tr>
        <td><?= $agendamento['id_agendamento'] ?></td>
        <td><?= htmlspecialchars($agendamento['servico_nome'] ?? 'Desconhecido') ?></td>
        <td><?= date('d/m/Y H:i', strtotime($agendamento['data_hora'])) ?></td>
        <td>
    <?php 
        $status = $agendamento['status'];
        switch ($status) {
            case 'concluído':
                $badgeClass = 'success';
                $statusTexto = 'Finalizado';
                break;
            case 'cancelado':
                $badgeClass = 'danger';
                $statusTexto = 'Cancelado';
                break;
            case 'confirmado':
                $badgeClass = 'info';
                $statusTexto = 'Confirmado';
                break;
            case 'pendente':
            default:
                $badgeClass = 'warning';
                $statusTexto = 'Pendente';
                break;
        }
    ?>
    <span class="badge bg-<?= $badgeClass ?>">
        <?= $statusTexto ?>
    </span>
</td>
        <td>R$ <?= number_format($agendamento['valor'], 2, ',', '.') ?></td>
        <td>
        <a href="nota.php?id_agendamento=<?= $agendamento['id_agendamento'] ?>" class="btn btn-info btn-sm">
            <i class="fas fa-file-invoice"></i> Ver Nota
        </a>
        <?php if ($agendamento['status'] != 'concluído' && $agendamento['status'] != 'cancelado'): ?>
        <form method="POST" action="../actions/cancelar_agendamento.php" class="d-inline" 
              onsubmit="return confirm('Tem certeza que deseja cancelar este agendamento?');">
            <input type="hidden" name="id_agendamento" value="<?= $agendamento['id_agendamento'] ?>">
            <button type="submit" class="btn btn-danger btn-sm ms-2">
                <i class="fas fa-times-circle"></i> Cancelar
            </button>
        </form>
    <?php elseif ($agendamento['status'] == 'cancelado'): ?>
        
    <?php endif; ?>
    <?php if ($agendamento['status'] == 'concluído'): ?>     
        <?php 
        $stmt_feedback = $pdo->prepare("SELECT id_feedback FROM feedbacks WHERE id_agendamento = ?");
        $stmt_feedback->execute([$agendamento['id_agendamento']]);
        $ja_avaliou = $stmt_feedback->rowCount() > 0;
        ?>
        
        <button class="btn btn-<?= $ja_avaliou ? 'secondary' : 'success' ?> btn-sm ms-2" 
                data-bs-toggle="modal" 
                data-bs-target="#feedbackModal"
                data-agendamento="<?= $agendamento['id_agendamento'] ?>"
                <?= $ja_avaliou ? 'disabled' : '' ?>>
            <i class="fas fa-comment"></i> 
            <?= $ja_avaliou ? 'Avaliado' : 'Avaliar' ?>
        </button>
    <?php else: ?>
        <span class="text-muted">Disponível após finalização</span>
    <?php endif; ?>

    <!-- Botão de Cancelar -->
</td>
    </tr>
    <?php endforeach; ?>
</tbody>
            </table>
        </section>
    </div>
</div>
<!-- Modal de Feedback -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="feedbackModalLabel">Avaliar Atendimento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="../actions/salvar_feedback.php">
        <div class="modal-body">
          <input type="hidden" name="id_agendamento" id="feedbackAgendamentoId">
          
          <div class="mb-3">
            <label for="avaliacao" class="form-label">Avaliação (1-5 estrelas)</label>
            <div class="rating-stars">
              <i class="fas fa-star" data-rating="1"></i>
              <i class="fas fa-star" data-rating="2"></i>
              <i class="fas fa-star" data-rating="3"></i>
              <i class="fas fa-star" data-rating="4"></i>
              <i class="fas fa-star" data-rating="5"></i>
            </div>
            <input type="hidden" name="avaliacao" id="avaliacaoInput" required>
          </div>
          
          <div class="mb-3">
            <label for="comentarios" class="form-label">Comentários (opcional)</label>
            <textarea class="form-control" name="comentarios" id="comentarios" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Enviar Feedback</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Seção de Feedbacks -->
<div class="row mt-4">
    <div class="col-md-12">
        <section>
            <h2>Meus Feedbacks</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Serviço</th>
                            <th>Avaliação</th>
                            <th>Comentário</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt_feedbacks = $pdo->prepare("
                            SELECT f.*, s.nome as servico_nome, a.data_hora
                            FROM feedbacks f
                            JOIN agendamentos a ON f.id_agendamento = a.id_agendamento
                            LEFT JOIN servicos s ON a.id_servico = s.id_servico
                            WHERE f.id_cliente = ?
                            ORDER BY f.created DESC
                        ");
                        $stmt_feedbacks->execute([$id_cliente]);
                        $feedbacks = $stmt_feedbacks->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($feedbacks as $feedback):
                        ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($feedback['data_hora'])) ?></td>
                            <td><?= htmlspecialchars($feedback['servico_nome']) ?></td>
                            <td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?= $i <= $feedback['avaliacao'] ? 'text-warning' : 'text-secondary' ?>"></i>
                                <?php endfor; ?>
                            </td>
                            <td><?= htmlspecialchars($feedback['comentarios'] ?? 'Sem comentários') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($feedbacks)): ?>
                        <tr>
                            <td colspan="4" class="text-center">Você ainda não enviou nenhum feedback.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
</main>


<footer class="roda-pe" id="roda-pe">
  <div class="roda-pe-container">
    <div class="roda-pe-localizacao">
      <h5>Localização</h5>
      <p>Rua Exemplo, 123 - Bairro Feliz, Cidade - Estado</p>
      <p><strong>Telefone:</strong> (11) 12345-6789</p>
    </div>
    <div class="roda-pe-descricao">
      <h5>Sobre AnimalSave</h5>
      <p>AnimalSave oferece serviços de banho e tosa para pets com todo o carinho, cuidado e qualidade. Garantimos uma experiência segura e confortável para o seu melhor amigo!</p>
    </div>
    <div class="roda-pe-horario">
      <h5>Horário de Funcionamento</h5>
      <p>Segunda a Sexta: 08h - 18h</p>
      <p>Sábado: 09h - 15h</p>
      <p>Domingo: Fechado</p>
    </div>
    <div class="roda-pe-maps">
      <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d403.43218990107175!2d-48.1191905315842!3d-15.850528612151736!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1spt-PT!2sbr!4v1747960968258!5m2!1spt-PT!2sbr" width="280" height="180" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
  </div>
  <div class="roda-pe-footer">
    <p>&copy; 2025 AnimalSave - Todos os direitos reservados</p>
  </div>
</footer>

<!-- Scripts do Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script>// Máscara para o CPF
function formatarCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

// Controle do campo de CPF
document.getElementById('editarCpfBtn').addEventListener('click', function() {
    document.getElementById('cpfTexto').style.display = 'none';
    document.getElementById('cpfInput').style.display = 'block';
    this.style.display = 'none';
    document.getElementById('salvarCpfBtn').style.display = 'inline-block';
    document.getElementById('cancelarCpfBtn').style.display = 'inline-block';
    document.getElementById('cpfInput').focus();
});

document.getElementById('salvarCpfBtn').addEventListener('click', function() {
    let cpf = document.getElementById('cpfInput').value.replace(/\D/g, '');
    
    // Validação básica do CPF
    if (cpf.length !== 11 || !validarCPF(cpf)) {
        alert('CPF inválido. Por favor, verifique o número.');
        return;
    }
    
    document.getElementById('campoCpfFinal').value = cpf;
    document.getElementById('formCpf').submit();
});

document.getElementById('cancelarCpfBtn').addEventListener('click', function() {
    document.getElementById('cpfTexto').style.display = 'block';
    document.getElementById('cpfInput').style.display = 'none';
    document.getElementById('editarCpfBtn').style.display = 'inline-block';
    document.getElementById('salvarCpfBtn').style.display = 'none';
    document.getElementById('cancelarCpfBtn').style.display = 'none';
});

// Função para validar CPF
function validarCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    
    // Verifica se tem 11 dígitos e não é uma sequência repetida
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
        return false;
    }
    
    // Validação dos dígitos verificadores
    for (let t = 9; t < 11; t++) {
        let d = 0;
        for (let c = 0; c < t; c++) {
            d += cpf.charAt(c) * ((t + 1) - c);
        }
        d = (10 * d) % 11;
        if (d === 10) d = 0;
        if (d !== parseInt(cpf.charAt(t))) {
            return false;
        }
    }
    return true;
}

// Aplicar máscara enquanto digita
document.getElementById('cpfInput').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 11) {
        value = value.substring(0, 11);
    }
    e.target.value = formatarCPF(value);
});</script>
<script>
// Mostrar toast quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    const toastEl = document.querySelector('.toast');
    if (toastEl) {
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }
});

document.getElementById('editarEnderecoBtn').addEventListener('click', function () {
    document.getElementById('enderecoTexto').style.display = 'none';
    document.getElementById('enderecoInput').style.display = 'block';
    this.style.display = 'none';
    document.getElementById('salvarEnderecoBtn').style.display = 'inline-block';
});

document.getElementById('salvarEnderecoBtn').addEventListener('click', function () {
    const novoEndereco = document.getElementById('enderecoInput').value.trim();
    document.getElementById('campoEnderecoFinal').value = novoEndereco;
    document.getElementById('formEndereco').submit();
});

document.getElementById('salvarCpfBtn').addEventListener('click', function () {
    const novoCpf = document.getElementById('cpfInput').value.trim();
    const erroCpf = document.getElementById('erroCpf');

    // Verificar se o CPF tem 11 dígitos
    if (novoCpf.length === 11 && /^[0-9]{11}$/.test(novoCpf)) {
        // Submete o formulário
        document.getElementById('campoCpfFinal').value = novoCpf;
        document.getElementById('formCpf').submit();
    } else {
        erroCpf.textContent = 'CPF inválido. Certifique-se de que está no formato correto.';
        erroCpf.style.display = 'block';
        document.getElementById('cpfInput').classList.add('is-invalid');
    }
});

// Exibir e ocultar os campos de CPF
document.getElementById('editarCpfBtn').addEventListener('click', function () {
    document.getElementById('cpfTexto').style.display = 'none';
    document.getElementById('cpfInput').style.display = 'block';
    this.style.display = 'none';
    document.getElementById('salvarCpfBtn').style.display = 'inline-block';
});

// Navbar scroll
let lastScrollTop = 0;
window.addEventListener("scroll", function() {
    let currentScroll = window.pageYOffset || document.documentElement.scrollTop;

    if (currentScroll > lastScrollTop) {
        document.getElementById("navbar-top").classList.add("navbar-hidden");
    } else {
        document.getElementById("navbar-top").classList.remove("navbar-hidden");
    }

    lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
});

// Formulário de senha com AJAX
document.getElementById('formSenha').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});
</script>
<script>// Manipulação do campo de telefone
document.getElementById('editarTelefoneBtn').addEventListener('click', function() {
    document.getElementById('telefoneTexto').style.display = 'none';
    document.getElementById('telefoneInput').style.display = 'block';
    this.style.display = 'none';
    document.getElementById('salvarTelefoneBtn').style.display = 'inline-block';
});

document.getElementById('salvarTelefoneBtn').addEventListener('click', function() {
    const novoTelefone = document.getElementById('telefoneInput').value.trim();
    
    // Validação básica
    if (novoTelefone.length >= 10 && /^[0-9]+$/.test(novoTelefone)) {
        document.getElementById('campoTelefoneFinal').value = novoTelefone;
        document.getElementById('formTelefone').submit();
    } else {
        alert('Telefone inválido. Digite apenas números (10 ou 11 dígitos).');
    }
});</script>
<script>// Adicione este script se estiver usando jQuery
$(document).ready(function() {
    $('#telefoneInput').mask('(00) 00000-0000');
});</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>// Controle do campo de endereço
document.getElementById('editarEnderecoBtn').addEventListener('click', function() {
    document.getElementById('enderecoTexto').style.display = 'none';
    document.getElementById('enderecoInput').style.display = 'block';
    this.style.display = 'none';
    document.getElementById('salvarEnderecoBtn').style.display = 'inline-block';
    document.getElementById('cancelarEnderecoBtn').style.display = 'inline-block';
    document.getElementById('enderecoInput').focus();
});

document.getElementById('salvarEnderecoBtn').addEventListener('click', function() {
    const novoEndereco = document.getElementById('enderecoInput').value.trim();
    if (novoEndereco.length === 0) {
        alert('Por favor, insira um endereço válido.');
        return;
    }
    document.getElementById('campoEnderecoFinal').value = novoEndereco;
    document.getElementById('formEndereco').submit();
});

document.getElementById('cancelarEnderecoBtn').addEventListener('click', function() {
    document.getElementById('enderecoTexto').style.display = 'block';
    document.getElementById('enderecoInput').style.display = 'none';
    document.getElementById('editarEnderecoBtn').style.display = 'inline-block';
    document.getElementById('salvarEnderecoBtn').style.display = 'none';
    document.getElementById('cancelarEnderecoBtn').style.display = 'none';
    // Restaura o valor original
    document.getElementById('enderecoInput').value = document.getElementById('campoEnderecoFinal').value || '';
});</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const telefoneTexto = document.getElementById('telefoneTexto');
    const telefoneInput = document.getElementById('telefoneInput');
    const editarTelefoneBtn = document.getElementById('editarTelefoneBtn');
    const salvarTelefoneBtn = document.getElementById('salvarTelefoneBtn');
    const cancelarTelefoneBtn = document.getElementById('cancelarTelefoneBtn');
    const campoTelefoneFinal = document.getElementById('campoTelefoneFinal');
    const formTelefone = document.getElementById('formTelefone');

    editarTelefoneBtn.addEventListener('click', function () {
        telefoneTexto.style.display = 'none';
        telefoneInput.style.display = 'block';
        editarTelefoneBtn.style.display = 'none';
        salvarTelefoneBtn.style.display = 'inline-block';
        cancelarTelefoneBtn.style.display = 'inline-block';
    });

    cancelarTelefoneBtn.addEventListener('click', function () {
        telefoneInput.style.display = 'none';
        telefoneTexto.style.display = 'block';
        editarTelefoneBtn.style.display = 'inline-block';
        salvarTelefoneBtn.style.display = 'none';
        cancelarTelefoneBtn.style.display = 'none';
    });

    salvarTelefoneBtn.addEventListener('click', function () {
        campoTelefoneFinal.value = telefoneInput.value;
        formTelefone.submit();
    });
});
</script>
<script>// Sistema de avaliação por estrelas
document.querySelectorAll('.rating-stars i').forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.getAttribute('data-rating');
        const stars = document.querySelectorAll('.rating-stars i');
        
        stars.forEach((s, index) => {
            if (index < rating) {
                s.classList.add('text-warning');
            } else {
                s.classList.remove('text-warning');
            }
        });
        
        document.getElementById('avaliacaoInput').value = rating;
    });
});

// Configurar o modal de feedback
document.getElementById('feedbackModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const idAgendamento = button.getAttribute('data-agendamento');
    const modal = this;
    
    modal.querySelector('#feedbackAgendamentoId').value = idAgendamento;
    
    // Resetar estrelas
    document.querySelectorAll('.rating-stars i').forEach(star => {
        star.classList.remove('text-warning');
    });
    document.getElementById('avaliacaoInput').value = '';
    document.getElementById('comentarios').value = '';
});

// Estilo CSS adicional para as estrelas
const style = document.createElement('style');
style.textContent = `
    .rating-stars {
        font-size: 2rem;
        cursor: pointer;
    }
    .rating-stars i {
        color: #ddd;
        transition: color 0.2s;
    }
    .rating-stars i.text-warning {
        color: #ffc107;
    }
    .rating-stars i:hover {
        color: #ffc107;
    }
`;
document.head.appendChild(style);</script>
</body>
</html>