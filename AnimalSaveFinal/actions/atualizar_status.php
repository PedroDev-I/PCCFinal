<?php
session_start();
require '../config/config.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_SESSION['id_funcionario'])) {
    header('Location: login_adm.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_agendamento'], $_POST['novo_status'])) {
    $id_agendamento = $_POST['id_agendamento'];
    $novo_status = $_POST['novo_status'];
    
    $status_validos = ['confirmado', 'cancelado', 'concluído','pendente'];
    if (!in_array($novo_status, $status_validos)) {
        $_SESSION['mensagem'] = [
            'tipo' => 'danger',
            'texto' => 'Status inválido!'
        ];
        header('Location: ../pages/admdashboard.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE agendamentos SET status = ? WHERE id_agendamento = ?");
        $stmt->execute([$novo_status, $id_agendamento]);
        $_SESSION['mensagem'] = [
            'tipo' => 'success',
            'texto' => 'Status do agendamento atualizado com sucesso!'
        ];
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = [
            'tipo' => 'danger',
            'texto' => 'Erro ao atualizar: ' . $e->getMessage()
        ];
    }

    header('Location: ../pages/admdashboard.php');
    exit;
}

header('Location: ../pages/admdashboard.php');
exit;