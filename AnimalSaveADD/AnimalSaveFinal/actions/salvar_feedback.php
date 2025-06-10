<?php
session_start();
include('../config/config.php');

if (!isset($_SESSION['id_cliente'])) {
    header('Location: ../pages/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_agendamento'], $_POST['avaliacao'])) {
    $id_cliente = $_SESSION['id_cliente'];
    $id_agendamento = $_POST['id_agendamento'];
    $avaliacao = $_POST['avaliacao'];
    $comentarios = $_POST['comentarios'] ?? null;

    // Verificar se o agendamento pertence ao cliente e está concluído
    $stmt = $pdo->prepare("
        SELECT a.id_agendamento 
        FROM agendamentos a
        JOIN animais an ON a.id_animal = an.id_animal
        WHERE a.id_agendamento = ? 
        AND an.id_cliente = ?
        AND a.status = 'concluído'
    ");
    $stmt->execute([$id_agendamento, $id_cliente]);
    
    if ($stmt->rowCount() > 0) {
        // Verificar se já existe feedback para este agendamento
        $stmt_check = $pdo->prepare("SELECT id_feedback FROM feedbacks WHERE id_agendamento = ?");
        $stmt_check->execute([$id_agendamento]);
        
        if ($stmt_check->rowCount() > 0) {
            $_SESSION['alertas'] = [
                'tipo' => 'warning',
                'mensagem' => 'Você já enviou feedback para este atendimento.'
            ];
        } else {
            // Inserir o feedback
            $stmt_insert = $pdo->prepare("
                INSERT INTO feedbacks (id_cliente, id_agendamento, comentarios, avaliacao)
                VALUES (?, ?, ?, ?)
            ");
            
            if ($stmt_insert->execute([$id_cliente, $id_agendamento, $comentarios, $avaliacao])) {
                $_SESSION['alertas'] = [
                    'tipo' => 'success',
                    'mensagem' => 'Obrigado pelo seu feedback!'
                ];
            } else {
                $_SESSION['alertas'] = [
                    'tipo' => 'danger',
                    'mensagem' => 'Erro ao enviar feedback. Tente novamente.'
                ];
            }
        }
    } else {
        $_SESSION['alertas'] = [
            'tipo' => 'danger',
            'mensagem' => 'Agendamento não encontrado ou não está concluído.'
        ];
    }
    
    header('Location: ../pages/perfil_cliente.php');
    exit();
} else {
    header('Location: ../pages/perfil_cliente.php');
    exit();
}
?>