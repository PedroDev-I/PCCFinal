<?php
// Inicia a sessão para verificar se o parâmetro 'cadastro' foi passado
session_start();

// Verifica se o parâmetro 'cadastro' está na URL, indicando que o cadastro foi feito com sucesso
if (isset($_GET['cadastro']) && $_GET['cadastro'] == 'sucesso') {
    $_SESSION['mensagem'] = 'Cadastro realizado com sucesso! Agora, faça login.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Página de Login</title>
  <link rel="stylesheet" href="../assets/css/paginas/login.css?=v1">
  <!-- Link do Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

  <link rel="shortcut icon" href="../assets/img/ícones/logo.png" type="image/x-icon">
  <style>
    /* Adiciona uma borda e um fundo claro para o alerta, sem afetar o fundo da página */
    .custom-alert {
      background-color: #d4edda;  /* Fundo claro */
      border-color: #c3e6cb;      /* Borda de alerta */
      color: #155724;             /* Texto verde escuro */
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 5px;
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="login-box">
    <div class="login-box" style="position: relative; padding-top: 40px;">
  

      <h2>Login</h2>
      

      <!-- Exibe o alerta de sucesso, se houver uma mensagem na sessão -->
      <?php
      if (isset($_SESSION['mensagem'])) {
          echo '<div class="custom-alert" role="alert">' . $_SESSION['mensagem'] . '</div>';
          unset($_SESSION['mensagem']); // Limpa a mensagem após exibi-la
      }
      if (isset($_GET['erro'])) {
        $erro = $_GET['erro'];
    
        if ($erro === 'senha') {
            echo '<div class="alert alert-danger" role="alert">Senha incorreta. Tente novamente.</div>';
        } elseif ($erro === 'usuario') {
            echo '<div class="alert alert-danger" role="alert">Usuário não encontrado.</div>';
        } elseif ($erro === 'campos') {
            echo '<div class="alert alert-warning" role="alert">Preencha todos os campos.</div>';
        }
    }if (isset($_SESSION['mensagem'])) {
      echo '<div class="custom-alert" role="alert">' . $_SESSION['mensagem'] . '</div>';
      unset($_SESSION['mensagem']);
  }
      ?>

      <!-- Formulário de login -->
      <form action="../actions/action_login.php" method="POST">
        <div class="input-group">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required>
        </div>

        <div class="input-group">
          <label for="senha">Senha</label>
          <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
        </div>

        <div class="remember-me">
          <input type="checkbox" id="remember" name="remember">
          <label for="remember">Lembrar-me</label>
        </div>

        <button type="submit" class="btn btn-primary">Entrar</button>

        <div class="additional-links">
          <a href="esqueci_senha.php">Esqueceu sua senha?</a>
          <p>Ainda não tem uma conta? <a href="registrar.php">Cadastre-se</a></p>
        </div>
      </form>
    </div>
  </div>

  <div class="popup" id="loginPopup">
    <div class="message">
      <h2>Você precisa estar logado para acessar esta página!</h2>
      <button onclick="closePopup()">Fechar</button>
    </div>
  </div>
  <button id="btnVoltar" class="btn btn-outline-primary position-absolute" 
  style="top: 12px; left: 12px; width: 32px; height: 32px; padding: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 1-.5.5H3.707l4.147 4.146a.5.5 0 0 1-.708.708l-5-5a.5.5 0 0 1 0-.708l5-5a.5.5 0 1 1 .708.708L3.707 7.5H14.5A.5.5 0 0 1 15 8z"/>
  </svg>
</button>
  <!-- Script do Bootstrap (opcional, mas recomendado para alguns componentes) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Verifica se o parâmetro 'error' está presente na URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
      // Exibe o popup
      const popup = document.getElementById('loginPopup');
      popup.style.display = 'flex';
    }

    // Função para fechar o popup
    function closePopup() {
      const popup = document.getElementById('loginPopup');
      popup.style.display = 'none';
    }
  </script>
<script>
  setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
      alert.style.transition = "opacity 0.5s";
      alert.style.opacity = 0;
      setTimeout(() => alert.remove(), 500);
    });
  }, 2000); // 2 segundos
</script>
<script>
  document.getElementById('btnVoltar').addEventListener('click', () => {
    history.back();
  });
</script>
</body>
</html>
