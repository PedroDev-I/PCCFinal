<?php
session_start();
if (!isset($_SESSION['id_funcionario'])) {
    header('Location: login_adm.php');
    exit;
}

require '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = $_POST['cpf'] ?? null;
    $nome = $_POST['nome'] ?? null;
    $email = $_POST['email'] ?? null;
    $telefone = $_POST['telefone'] ?? null;

    if ($cpf && $nome && $email) {
        $sql = "UPDATE clientes SET nome = :nome, email = :email, telefone = :telefone WHERE cpf = :cpf";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
          ':nome' => $nome,
          ':email' => $email,
          ':telefone' => $telefone,
          ':cpf' => $cpf
        ]);
    }
}

header('Location: seus_clientes.php'); // coloque a página da lista de clientes aqui
exit;
?>