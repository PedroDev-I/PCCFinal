<?php
session_start();
if (!isset($_SESSION['id_funcionario'])) {
    header('Location: ../login_adm.php');
    exit;
}

require '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_POST['id_cliente'] ?? null;
    $cpf = $_POST['cpf'] ?? null;
    $nome = $_POST['nome'] ?? null;
    $email = $_POST['email'] ?? null;
    $telefone = $_POST['telefone'] ?? null;
    $endereco = $_POST['endereco'] ?? null;

    if ($id_cliente && $cpf && $nome && $email) {
        $sql = "UPDATE clientes SET 
                cpf = :cpf, 
                nome = :nome, 
                email = :email, 
                telefone = :telefone,
                endereco = :endereco
                WHERE id_cliente = :id_cliente";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_cliente' => $id_cliente,
            ':cpf' => $cpf,
            ':nome' => $nome,
            ':email' => $email,
            ':telefone' => $telefone,
            ':endereco' => $endereco
        ]);

        $_SESSION['mensagem'] = [
            'tipo' => 'success',
            'texto' => 'Cliente atualizado com sucesso!'
        ];
    } else {
        $_SESSION['mensagem'] = [
            'tipo' => 'danger',
            'texto' => 'Dados incompletos para atualização.'
        ];
    }
}

header('Location: ../pages/admdashboard.php');
exit;
?>