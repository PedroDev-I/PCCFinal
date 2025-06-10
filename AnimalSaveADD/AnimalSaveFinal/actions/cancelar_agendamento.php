<?php
require_once '../config/config.php'; // Ajuste o caminho se necessário
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_agendamento'])) {
    $id_agendamento = intval($_POST['id_agendamento']);

    $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id_agendamento = ?");
    if ($stmt->execute([$id_agendamento])) {
        $_SESSION['mensagem'] = 'Agendamento cancelado com sucesso.';
    } else {
        $_SESSION['mensagem'] = 'Erro ao cancelar agendamento.';
    }
}

header("Location: ../pages/perfil_cliente.php"); // Redirecione de volta à página original
exit;