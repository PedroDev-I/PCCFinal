<?php
session_start();
require '../config/config.php';

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$senha = filter_input(INPUT_POST, 'senha');

if ($email && $senha) {
    $sql = $pdo->prepare("SELECT * FROM clientes WHERE email = :email");
    $sql->bindValue(':email', $email);
    $sql->execute();

    if ($sql->rowCount() > 0) {
        $usuario = $sql->fetch(PDO::FETCH_ASSOC);

        if (password_verify($senha, $usuario['senha'])) {
            session_regenerate_id(true);
            $_SESSION['id_cliente'] = $usuario['id_cliente'];
            $_SESSION['nome'] = $usuario['nome'];

            header("Location: ../index.php");
            exit;
        } else {
            // Senha incorreta - redireciona com erro
            header("Location: ../pages/login.php?erro=senha");
            exit;
        }
    } else {
        // Usuário não encontrado - redireciona com erro
        header("Location: ../pages/login.php?erro=usuario");
        exit;
    }
} else {
    // Campos não preenchidos
    header("Location: ../pages/login.php?erro=campos");
    exit;
}


?>
