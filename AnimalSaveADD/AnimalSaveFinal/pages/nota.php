<?php
include('../config/config.php');
session_start();

if (!isset($_SESSION['id_cliente'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id_agendamento'])) {
    echo "Agendamento inválido.";
    exit();
}

$id_agendamento = $_GET['id_agendamento'];

// Consulta completa com id_servico
$stmt = $pdo->prepare("
    SELECT 
        Ag.*, 
        An.nome AS nome_animal,
        Cl.nome AS nome_cliente,
        Cl.cpf,
        S.id_servico,
        S.nome AS nome_servico,
        Pg.forma_pagamento,
        Pg.valor_pago,
        Pg.data_pagamento
    FROM Agendamentos Ag
    JOIN Animais An ON Ag.id_animal = An.id_animal
    JOIN Clientes Cl ON An.id_cliente = Cl.id_cliente
    JOIN Servicos S ON Ag.id_servico = S.id_servico
    LEFT JOIN Pagamentos Pg ON Pg.id_agendamento = Ag.id_agendamento
    WHERE Ag.id_agendamento = ?
");

$stmt->execute([$id_agendamento]);
$nota = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$nota) {
    echo "Dados da nota não encontrados.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Nota de Atendimento #<?= htmlspecialchars($nota['id_agendamento']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../assets/img/ícones/logo.png" type="image/x-icon">
    <style>
        body {
            font-family: "Arial", sans-serif;
            background-color: #f8f9fa;
            padding: 30px;
        }

        .nota-container {
            background: #fff;
            padding: 25px 30px;
            border-radius: 8px;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.08);
            max-width: 800px;
            margin: auto;
        }

        .nota-header {
            text-align: center;
            border-bottom: 2px solid #343a40;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }

        .nota-header h2 {
            font-weight: bold;
        }

        .nota-section {
            margin-bottom: 20px;
        }

        .nota-section strong {
            display: inline-block;
            width: 200px;
            color: #343a40;
        }

        .nota-alert {
            font-size: 0.9rem;
        }

        .nota-footer {
            text-align: center;
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 30px;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
        }

        .no-print {
            margin-top: 20px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="nota-container">
        <div class="nota-header">
            <h2>Nota de Atendimento</h2>
        </div>

        <!-- Cliente e Animal -->
        <div class="nota-section">
            <strong>Cliente:</strong> <?= htmlspecialchars($nota['nome_cliente']) ?><br>
            <strong>CPF:</strong> <?= htmlspecialchars($nota['cpf']) ?><br>
            <strong>Animal:</strong> <?= htmlspecialchars($nota['nome_animal']) ?>
        </div>

        <!-- Detalhes do Serviço -->
        <div class="nota-section">
            <strong>NÚMERO DO ATENDIMENTO:</strong> <?= htmlspecialchars($nota['id_agendamento']) ?><br>
            <strong>Serviço:</strong> <?= htmlspecialchars($nota['nome_servico']) ?> (ID: <?= htmlspecialchars($nota['id_servico']) ?>)<br>
            <strong>Data do Agendamento:</strong> <?= date('d/m/Y H:i', strtotime($nota['data_hora'])) ?><br>
            <strong>Status:</strong> <?= htmlspecialchars($nota['status']) ?>
        </div>

        <!-- Informações de Pagamento -->
        <div class="nota-section">
            <strong>Valor Total:</strong> R$ <?= number_format($nota['valor'], 2, ',', '.') ?><br>
            <strong>Valor Pago:</strong> R$ <?= number_format($nota['valor_pago'], 2, ',', '.') ?><br>
            <strong>Forma de Pagamento:</strong> <?= htmlspecialchars($nota['forma_pagamento']) ?><br>
            <strong>Data do Pagamento:</strong> <?= $nota['data_pagamento'] ? date('d/m/Y H:i', strtotime($nota['data_pagamento'])) : 'Pendente' ?>
        </div>

        <!-- Avisos -->
        <div class="alert alert-warning nota-alert" role="alert">
            <strong>Aviso:</strong><br>
            - O transporte do pet até o local é responsabilidade do cliente (não oferecemos serviço de TaxiPet).<br>
            - A tolerância para atrasos é de até 1 hora após o horário marcado.
        </div>

        <!-- Endereço da loja -->
        <div class="nota-section">
            <strong>Endereço da Loja:</strong><br>
            P.sul QNP 38 CEP 12345-678
        </div>

        <!-- Destaque com número do atendimento -->
        <div class="text-center my-4">
            <p style="font-size: 1.5rem; font-weight: bold; color: #000;">
                NÚMERO DO ATENDIMENTO: <?= htmlspecialchars($nota['id_agendamento']) ?>
            </p>
        </div>

        <!-- Botões -->
        <div class="d-flex gap-2 no-print">
            <a href="../index.php" class="btn btn-secondary">Voltar ao Início</a>
            <button class="btn btn-success" onclick="window.print()">Imprimir Nota</button>
        </div>

        <!-- Rodapé -->
        <div class="nota-footer">
            &copy; <?= date('Y') ?> - AnimalSave | Todos os direitos reservados.
        </div>
    </div>
</body>
</html>
