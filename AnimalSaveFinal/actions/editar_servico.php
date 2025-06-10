<?php
session_start();
require '../config/config.php';

if (!isset($_SESSION['id_funcionario'])) {
    header('Location: login_adm.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_servico = $_POST['id_servico'] ?? null;
    $nome = $_POST['nome'] ?? null;
    $descricao = $_POST['descricao'] ?? null;
    $preco = $_POST['preco'] ?? null;

    if ($id_servico && $nome && $preco) {
        try {
            $stmt = $pdo->prepare("UPDATE servicos SET nome = ?, descricao = ?, preco = ?, modified = NOW() WHERE id_servico = ?");
            $stmt->execute([$nome, $descricao, $preco, $id_servico]);
            
            $_SESSION['mensagem'] = [
                'texto' => 'Serviço atualizado com sucesso!',
                'tipo' => 'success'
            ];
        } catch (PDOException $e) {
            $_SESSION['mensagem'] = [
                'texto' => 'Erro ao atualizar serviço: ' . $e->getMessage(),
                'tipo' => 'danger'
            ];
        }
    } else {
        $_SESSION['mensagem'] = [
            'texto' => 'Preencha todos os campos obrigatórios!',
            'tipo' => 'warning'
        ];
    }
    
    header('Location: ../pages/admdashboard.php#servicos');
    exit;
}