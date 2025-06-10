<?php
session_start();
require_once '../config/config.php';

$erro = "";
$sucesso = "";

// Botão "Recomeçar" - limpa a sessão e recarrega a página
if (isset($_POST['reset'])) {
    session_unset();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Passo 1: recebe email e armazena na sessão (sem validar no banco)
if (isset($_POST['email']) && !isset($_SESSION['email_validado'])) {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $erro = "Informe um e-mail válido.";
    } else {
        $_SESSION['email_para_codigo'] = $email;
        $sucesso = "Código enviado para o seu e-mail (demonstrativo).";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
// Passo 1: recebe email e armazena na sessão (validando no banco)
if (isset($_POST['email']) && !isset($_SESSION['email_validado'])) {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $erro = "Informe um e-mail válido.";
    } else {
        // Verifica no banco se o e-mail existe
        $stmt = $pdo->prepare("SELECT id_cliente FROM clientes WHERE email = :email LIMIT 1");
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            // E-mail não encontrado
            $erro = "E-mail não cadastrado.";
        } else {
            $_SESSION['email_para_codigo'] = $email;
            $sucesso = "Código enviado para o seu e-mail (demonstrativo).";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Passo 2: usuário envia o código para validar
if (isset($_POST['codigo']) && isset($_SESSION['email_para_codigo']) && !isset($_SESSION['email_validado'])) {
    $codigo_digitado = trim($_POST['codigo']);

    // Busca o código fixo na tabela 'code'
    $stmt = $pdo->query("SELECT code FROM code LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && intval($codigo_digitado) === intval($row['code'])) {
        $_SESSION['email_validado'] = $_SESSION['email_para_codigo'];
        unset($_SESSION['email_para_codigo']);
        $sucesso = "Código confirmado! Agora você pode trocar sua senha.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $erro = "Código inválido.";
    }
}

// Passo 3: troca de senha
if (isset($_POST['senha'], $_POST['senha_confirm']) && isset($_SESSION['email_validado'])) {
    $senha = $_POST['senha'];
    $senha_confirm = $_POST['senha_confirm'];

    if (empty($senha) || empty($senha_confirm)) {
        $erro = "Preencha todos os campos de senha.";
    } elseif ($senha !== $senha_confirm) {
        $erro = "As senhas não coincidem.";
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $email = $_SESSION['email_validado'];

        $stmt = $pdo->prepare("UPDATE clientes SET senha = :senha WHERE email = :email");
        $stmt->bindValue(':senha', $hash);
        $stmt->bindValue(':email', $email);

        if ($stmt->execute()) {
            $sucesso = "Senha alterada com sucesso! Você já pode fazer login.";
            session_unset();
            // Não redireciona aqui para exibir o modal
        } else {
            $erro = "Erro ao atualizar a senha.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Recuperar Senha</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/paginas/esquecisenha.css">
<link rel="shortcut icon" href="../assets/img/ícones/logo.png" type="image/x-icon">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

<div class="card p-4 shadow" style="width: 360px;">
    <h3 class="mb-4 text-center">Recuperar Senha</h3>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <?php if ($sucesso && $sucesso !== "Senha alterada com sucesso! Você já pode fazer login."): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <?php if (!isset($_SESSION['email_para_codigo']) && !isset($_SESSION['email_validado'])): ?>
        <form method="POST" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail cadastrado</label>
                <input type="email" class="form-control" name="email" id="email" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary w-100">Enviar Código</button>
            <p>Um código será enviado ao seu e-mail</p>
        </form>

    <?php elseif (isset($_SESSION['email_para_codigo']) && !isset($_SESSION['email_validado'])): ?>
        <form method="POST" novalidate>
            <div class="mb-3">
                <label for="codigo" class="form-label">Digite o código enviado</label>
                <input type="text" class="form-control" name="codigo" id="codigo" required maxlength="10" autofocus>
            </div>
            <button type="submit" class="btn btn-primary w-100">Confirmar Código</button>
        </form>

    <?php else: ?>
        <form method="POST" novalidate>
            <div class="mb-3">
                <label for="senha" class="form-label">Nova senha</label>
                <input type="password" class="form-control" name="senha" id="senha" required minlength="6" autofocus>
            </div>
            <div class="mb-3">
                <label for="senha_confirm" class="form-label">Confirme a nova senha</label>
                <input type="password" class="form-control" name="senha_confirm" id="senha_confirm" required minlength="6">
            </div>
            <button type="submit" class="btn btn-success w-100">Alterar Senha</button>
        </form>
    <?php endif; ?>

    <!-- Botão Recomeçar -->
    <form method="POST" class="mt-3 text-center">
        <button type="submit" name="reset" class="btn btn-secondary btn-sm">Recomeçar</button>
    </form>
</div>

<!-- Modal de Sucesso -->
<div class="modal fade" id="sucessoModal" tabindex="-1" aria-labelledby="sucessoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="sucessoModalLabel">Sucesso</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <?= htmlspecialchars($sucesso) ?>
      </div>
    </div>
  </div>
</div>

<?php if ($sucesso === "Senha alterada com sucesso! Você já pode fazer login."): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var sucessoModal = new bootstrap.Modal(document.getElementById('sucessoModal'));
    sucessoModal.show();

    setTimeout(function () {
      sucessoModal.hide();
      window.location.href = 'login.php';  // redireciona após fechar modal
    }, 3000);
  });
</script>
<?php endif; ?>

</body>
</html>
