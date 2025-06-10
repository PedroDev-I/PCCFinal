<?php
session_start();
if (!isset($_SESSION['id_funcionario'])) {
    header('Location: login_adm.php');
    exit;
}
require '../config/config.php';

// Consulta clientes
$sqlClientes = $pdo->query("SELECT id_cliente,cpf,nome, email, telefone,endereco, created FROM clientes");
$clientes = $sqlClientes->fetchAll(PDO::FETCH_ASSOC);

// Consulta serviços
$sqlServicos = $pdo->query("SELECT id_servico, nome, descricao, preco FROM servicos");
$servicos = $sqlServicos->fetchAll(PDO::FETCH_ASSOC);

// Consulta agendamentos (modificada)
$sqlAgendamentos = $sql = "
SELECT 
  a.id_agendamento,
  a.data_hora,
  a.status,
  a.valor,
  a.observacoes,
  s.nome AS servico_nome,
  c.id_cliente,
  ani.nome AS nome_pet,
  ani.peso AS peso_pet,
  ani.idade AS idade_pet
FROM agendamentos a
LEFT JOIN servicos s ON a.id_servico = s.id_servico
LEFT JOIN clientes c ON a.id_cliente = c.id_cliente
LEFT JOIN animais ani ON a.id_animal = ani.id_animal
ORDER BY a.data_hora DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta animais
$sqlAnimais = $pdo->query("SELECT a.id_animal, a.nome, a.tipo, a.raca, a.idade, a.peso, c.nome AS cliente_nome
FROM animais a
LEFT JOIN clientes c ON a.id_cliente = c.id_cliente");
$animais = $sqlAnimais->fetchAll(PDO::FETCH_ASSOC);

// Relatório financeiro - MODIFICADO PARA MELHOR VISUALIZAÇÃO
$dataFiltro = $_GET['data'] ?? date('Y-m-d');
$codigoFiltro = $_GET['codigo'] ?? null;

$sqlRelatorio = "SELECT COUNT(*) AS total_atendimentos, COALESCE(SUM(valor), 0) AS total_valor
FROM agendamentos
WHERE DATE(data_hora) = :data";
$params = [':data' => $dataFiltro];

if ($codigoFiltro) {
    $sqlRelatorio .= " AND id_agendamento = :codigo";
    $params[':codigo'] = $codigoFiltro;
}

$stmtRelatorio = $pdo->prepare($sqlRelatorio);
$stmtRelatorio->execute($params);
$relatorio = $stmtRelatorio->fetch(PDO::FETCH_ASSOC);

// Gráfico dos últimos 7 dias
$sqlGrafico = $pdo->query("
    SELECT DATE(data_hora) AS dia, COUNT(*) AS qtd
    FROM agendamentos
    WHERE data_hora >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY dia ORDER BY dia ASC
");
$dadosGrafico = $sqlGrafico->fetchAll(PDO::FETCH_ASSOC);
$labelsGrafico = array_map(fn($d) => date('d/m', strtotime($d['dia'])), $dadosGrafico);
$valoresGrafico = array_column($dadosGrafico, 'qtd');

// Dados do painel
$dataHoje = date('Y-m-d');

// Atendimentos e total arrecadado de hoje
$stmtPainelHoje = $pdo->prepare("SELECT COUNT(*) AS total_hoje, COALESCE(SUM(valor), 0) AS total_valor
FROM agendamentos WHERE DATE(data_hora) = :hoje");
$stmtPainelHoje->execute([':hoje' => $dataHoje]);
$painelHoje = $stmtPainelHoje->fetch(PDO::FETCH_ASSOC);

// Próximos atendimentos
$stmtProximos = $pdo->query("SELECT data_hora, status, s.nome AS servico
FROM agendamentos a
LEFT JOIN servicos s ON s.id_servico = a.id_servico
WHERE DATE(data_hora) >= CURDATE()
ORDER BY data_hora ASC LIMIT 3");
$proximosAtendimentos = $stmtProximos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard</title>
  <link rel="shortcut icon" href="../assets/img/ícones/logo.png" type="image/x-icon">
  <link rel="stylesheet" href="../assets/css/paginas/adm.css?=v1" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .dropdown { position: relative; display: inline-block; }
    .dropdown-content {
      display: none; position: absolute; background-color: #f1f1f1;
      min-width: 160px; box-shadow: 0px 8px 16px rgba(0,0,0,0.2); z-index: 1;
    }
    .dropdown-content a {
      color: black; padding: 12px 16px; text-decoration: none; display: block;
    }
    .dropdown-content a:hover { background-color: #ddd; }
    .dropdown:hover .dropdown-content { display: block; }
    .aba { display: none; }
    .aba.ativa { display: block; }
    .botao-nota {
      padding: 5px 10px; background-color: #28a745; color: white;
      text-decoration: none; border-radius: 4px;
    }
    .botao-nota:hover { background-color: #218838; }
    .card-resumo {
      border-left: 4px solid #0d6efd;
      transition: transform 0.2s;
    }
    .card-resumo:hover {
      transform: translateY(-5px);
    }

    .badge.bg-warning { color: #000; } /* Para o status pendente */
    .badge.bg-primary { color: #fff; } /* Para o status confirmado */
    .badge.bg-success { color: #fff; } /* Para o status finalizado */
    .badge.bg-danger { color: #fff; }  /* Para o status cancelado */

    .action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}
  </style>
</head>
<body>

<div class="dashboard-container">
  <aside class="sidebar">
    <header>
      <div class="dropdown">
        <button class="dropbtn"><?= $_SESSION['nome'] ?> <span>&#9660;</span></button>
        <div class="dropdown-content">
          <a href="../actions/logout_adm.php">Sair</a>
        </div>
      </div>
    </header>
    <h2>Admin DashBoard</h2>
    <ul class="menu">
      <li onclick="mostrarAba('painel')">Painel</li>
      <li onclick="mostrarAba('clientes')">Clientes</li>
      <li onclick="mostrarAba('atendimentos')">Atendimentos</li>
      <li onclick="mostrarAba('servicos')">Serviços</li>
      <li onclick="mostrarAba('animais')">Animais</li>
      <li onclick="mostrarAba('relatorio')">Relatório</li>
      <li onclick="mostrarAba('feedback')">FeedBack</li>
    </ul>
  </aside>

  <main class="main-content">

    <!-- Painel -->
    <section id="painel" class="aba ativa">
      <h2>Painel Geral</h2>
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="card text-white bg-primary mb-3">
            <div class="card-body">
              <h5 class="card-title">Atendimentos Hoje</h5>
              <p class="card-text fs-4"><?= $painelHoje['total_hoje'] ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-white bg-success mb-3">
            <div class="card-body">
              <h5 class="card-title">Total Arrecadado</h5>
              <p class="card-text fs-4">R$ <?= number_format($painelHoje['total_valor'], 2, ',', '.') ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-white bg-info mb-3">
            <div class="card-body">
              <h5 class="card-title">Clientes Cadastrados</h5>
              <p class="card-text fs-4"><?= count($clientes) ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-white bg-warning mb-3">
            <div class="card-body">
              <h5 class="card-title">Animais Registrados</h5>
              <p class="card-text fs-4"><?= count($animais) ?></p>
            </div>
          </div>
        </div>
      </div>

      <h4>Próximos Atendimentos</h4>
      <?php if (count($proximosAtendimentos) > 0): ?>
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Data/Hora</th>
            <th>Serviço</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($proximosAtendimentos as $prox): ?>
            <tr>
              <td><?= date('d/m/Y H:i', strtotime($prox['data_hora'])) ?></td>
              <td><?= $prox['servico'] ?></td>
              <td><?= ucfirst($prox['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p class="text-muted">Nenhum atendimento agendado nos próximos dias.</p>
      <?php endif; ?>
    </section>

    <!-- Clientes -->
    <section id="clientes" class="aba">
      <h2>Clientes</h2>
      <table id="tabela-clientes" class="display" style="width:100%">
        <thead><tr><th>CPF</th><th>ID</th><th>Nome</th><th>Email</th><th>Telefone</th><th>Endereço</th><th>Data de Cadastro</th></tr></thead>
        <tbody>
          <?php foreach ($clientes as $cliente): ?>
            <tr>
            <td><?= htmlspecialchars($cliente['cpf']) ?></td>
            <td><?= htmlspecialchars($cliente['id_cliente']) ?></td>
              <td><?= htmlspecialchars($cliente['nome']) ?></td>
              <td><?= htmlspecialchars($cliente['email']) ?></td>
              <td><?= htmlspecialchars($cliente['telefone'] ?? '-') ?></td>
              <td><?= htmlspecialchars($cliente['endereco']) ?></td>
              <td><?= date('d/m/Y', strtotime($cliente['created'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

<!-- Atendimentos -->
<section id="atendimentos" class="aba">
  <h2>Atendimentos</h2>
  <table id="tabela-agendamentos" class="display" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Data/Hora</th>
        <th>Serviço</th>
        <th>Status</th>
        <th>Valor</th>
        <th>Observações</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($agendamentos as $ag): 
        $status_raw = $ag['status'] ?? '';
        $status_trimmed = trim($status_raw);
        $status = strtolower($status_trimmed);

        if ($status == 'concluído') {
          $badgeClass = 'badge bg-success';
          $style = '';
        } else if ($status == 'cancelado') {
          $badgeClass = 'badge bg-danger';
          $style = '';
        } else if ($status == 'confirmado') {
          $badgeClass = 'badge bg-primary';
          $style = '';
        } else if ($status == 'pendente' || $status === '') {
          $badgeClass = 'badge bg-warning text-dark';
          $style = '';
        } else {
          $badgeClass = 'badge bg-secondary';
          $style = '';
        }

        $textoExibido = $status_trimmed === '' ? 'Pendente' : ucfirst($status);
      ?>
        <tr>
          <td><?= $ag['id_agendamento'] ?></td>
          <td><?= date('d/m/Y H:i', strtotime($ag['data_hora'])) ?></td>
          <td><?= htmlspecialchars($ag['servico_nome']) ?></td>
          <td>
            <span class="<?= $badgeClass ?>" style="<?= $style ?>">
              <?= $textoExibido ?>
            </span>
          </td>
          <td>R$ <?= number_format($ag['valor'], 2, ',', '.') ?></td>
          <td>
            <?php if (!empty($ag['observacoes'])): ?>
              <button class="btn btn-sm btn-outline-secondary" 
                      data-bs-toggle="modal" 
                      data-bs-target="#modalObservacoes"
                      data-observacoes="<?= htmlspecialchars($ag['observacoes']) ?>">
                <i class="fas fa-eye"></i> Ver
              </button>
            <?php else: ?>
              <span class="text-muted">Nenhuma</span>
            <?php endif; ?>
          </td>
          <td>
  <div class="action-buttons">
    <?php if ($status != 'concluído' && $status != 'cancelado'): ?>
      <!-- Botão Finalizar -->
      <form method="POST" action="../actions/atualizar_status.php">
        <input type="hidden" name="id_agendamento" value="<?= $ag['id_agendamento'] ?>">
        <input type="hidden" name="novo_status" value="concluído">
        <button type="submit" class="btn btn-sm btn-success" title="Finalizar agendamento">
          <i class="fas fa-check"></i> Finalizar
        </button>
      </form>

      <!-- Botão Cancelar -->
      <form method="POST" action="../actions/atualizar_status.php" onsubmit="return confirm('Tem certeza que deseja cancelar este agendamento?');">
        <input type="hidden" name="id_agendamento" value="<?= $ag['id_agendamento'] ?>">
        <input type="hidden" name="novo_status" value="cancelado">
        <button type="submit" class="btn btn-sm btn-danger" title="Cancelar agendamento">
          <i class="fas fa-times"></i> Cancelar
        </button>
      </form>
    <?php endif; ?>

    <?php if ($status == 'cancelado'): ?>
      <span class="text-danger">Nota cancelada</span>
    <?php else: ?>
      <a href="nota.php?id_agendamento=<?= $ag['id_agendamento'] ?>" class="btn btn-sm btn-info">
        <i class="fas fa-file-invoice"></i> Nota
      </a>
    <?php endif; ?>
  </div>
</td>

        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<!-- Modal para exibir observações -->
<div class="modal fade" id="modalObservacoes" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Observações do Atendimento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="texto-observacoes"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

    <!-- Serviços -->
    <section id="servicos" class="aba">
      <h2>Serviços</h2>
      <table id="tabela-servicos" class="display" style="width:100%">
        <thead><tr><th>ID</th><th>Nome</th><th>Descrição</th><th>Preço</th><th>Ações</th></tr></thead>
        <tbody>
          <?php foreach ($servicos as $servico): ?>
            <tr>
              <td><?= $servico['id_servico'] ?></td>
              <td><?= $servico['nome'] ?></td>
              <td><?= $servico['descricao'] ?? '-' ?></td>
              <td>R$ <?= number_format($servico['preco'], 2, ',', '.') ?></td>
              <td>
                <button class="btn btn-sm btn-primary editar-servico" 
                        data-id="<?= $servico['id_servico'] ?>"
                        data-nome="<?= htmlspecialchars($servico['nome']) ?>"
                        data-descricao="<?= htmlspecialchars($servico['descricao'] ?? '') ?>"
                        data-preco="<?= $servico['preco'] ?>">
                  <i class="fas fa-edit"></i> Editar
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <!-- Modal de Edição -->
    <div class="modal fade" id="modalEditarServico" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Editar Serviço</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="formEditarServico" method="POST" action="../actions/editar_servico.php">
            <div class="modal-body">
              <input type="hidden" name="id_servico" id="editar-id">
              <div class="mb-3">
                <label for="editar-nome" class="form-label">Nome</label>
                <input type="text" class="form-control" id="editar-nome" name="nome" required>
              </div>
              <div class="mb-3">
                <label for="editar-descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="editar-descricao" name="descricao" rows="3"></textarea>
              </div>
              <div class="mb-3">
                <label for="editar-preco" class="form-label">Preço (R$)</label>
                <input type="number" step="0.01" class="form-control" id="editar-preco" name="preco" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Animais -->
    <section id="animais" class="aba">
      <h2>Animais</h2>
      <table id="tabela-animais" class="display" style="width:100%">
        <thead><tr><th>ID</th><th>Nome</th><th>Tipo</th><th>Raça</th><th>Idade</th><th>Peso</th><th>Tutor</th></tr></thead>
        <tbody>
          <?php foreach ($animais as $animal): ?>
            <tr>
              <td><?= $animal['id_animal'] ?></td>
              <td><?= $animal['nome'] ?></td>
              <td><?= $animal['tipo'] ?></td>
              <td><?= $animal['raca'] ?></td>
              <td><?= $animal['idade'] ?></td>
              <td><?= number_format($animal['peso'], 2, ',', '.') ?></td>
              <td><?= $animal['cliente_nome'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <!-- Relatório -->
    <section id="relatorio" class="aba">
      <h2>Relatório Financeiro</h2>
      
      <form method="GET" class="row g-3 mb-4 p-3 bg-light rounded">
        <div class="col-md-5">
          <label for="data" class="form-label">Data:</label>
          <input type="date" class="form-control" name="data" value="<?= htmlspecialchars($dataFiltro) ?>" required>
        </div>
        <div class="col-md-5">
          <label for="codigo" class="form-label">Código do Agendamento (opcional):</label>
          <input type="number" class="form-control" name="codigo" value="<?= htmlspecialchars($codigoFiltro) ?>" placeholder="Filtrar por ID">
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
      </form>

      <div id="conteudo-relatorio" class="p-3">
        <div class="row mb-4">
          <div class="col-md-6 mb-3">
            <div class="card card-resumo h-100">
              <div class="card-body">
                <h5 class="card-title text-primary">
                  <i class="fas fa-calendar-check me-2"></i> Atendimentos
                </h5>
                <div class="d-flex align-items-center">
                  <span class="display-4 fw-bold me-3"><?= $relatorio['total_atendimentos'] ?></span>
                  <span class="text-muted">em <?= date('d/m/Y', strtotime($dataFiltro)) ?></span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-6 mb-3">
            <div class="card card-resumo h-100">
              <div class="card-body">
                <h5 class="card-title text-success">
                  <i class="fas fa-money-bill-wave me-2"></i> Valor Total
                </h5>
                <div class="d-flex align-items-center">
                  <span class="display-4 fw-bold me-3">R$ <?= number_format($relatorio['total_valor'], 2, ',', '.') ?></span>
                  <span class="text-success">
                    <?php if ($relatorio['total_valor'] > 0): ?>
                      <i class="fas fa-arrow-up"></i>
                    <?php else: ?>
                      <i class="fas fa-equals"></i>
                    <?php endif; ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white p-4 rounded shadow-sm">
          <h5 class="mb-4">
            <i class="fas fa-chart-line me-2"></i> Movimento dos últimos 7 dias
          </h5>
          <canvas id="graficoRelatorio" height="120"></canvas>
        </div>

        <div class="mt-4 text-end">
          <button class="btn btn-success" onclick="imprimirRelatorio()">
            <i class="fas fa-print me-2"></i>Imprimir Relatório
          </button>
        </div>
      </div>
    </section>

<!-- Section FeedBack -->
<section id="feedback" class="aba">
    <h2>Feedbacks dos Clientes</h2>
    
    <?php
    $sqlFeedbacks = $pdo->query("
        SELECT f.*, 
               c.nome AS cliente_nome,
               c.email AS cliente_email,
               s.nome AS servico_nome,
               a.data_hora,
               an.nome AS animal_nome
        FROM feedbacks f
        JOIN clientes c ON f.id_cliente = c.id_cliente
        JOIN agendamentos a ON f.id_agendamento = a.id_agendamento
        LEFT JOIN servicos s ON a.id_servico = s.id_servico
        JOIN animais an ON a.id_animal = an.id_animal
        ORDER BY f.created DESC
    ");
    $feedbacks = $sqlFeedbacks->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div class="table-responsive">
        <table id="tabela-feedbacks" class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Serviço</th>
                    <th>Animal</th>
                    <th>Avaliação</th>
                    <th>Comentário</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($feedbacks) > 0): ?>
                    <?php foreach ($feedbacks as $feedback): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($feedback['data_hora'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($feedback['cliente_nome']) ?></strong><br>
                                <small><?= htmlspecialchars($feedback['cliente_email']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($feedback['servico_nome'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($feedback['animal_nome']) ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= $feedback['avaliacao'] ? 'text-warning' : 'text-secondary' ?> me-1"></i>
                                    <?php endfor; ?>
                                    <span class="badge bg-primary ms-2"><?= $feedback['avaliacao'] ?>/5</span>
                                </div>
                            </td>
                            <td><?= !empty($feedback['comentarios']) ? nl2br(htmlspecialchars($feedback['comentarios'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="fas fa-comment-slash fa-2x text-muted mb-2"></i><br>
                            Nenhum feedback recebido ainda.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-chart-pie me-2"></i> Resumo de Avaliações
                </div>
                <div class="card-body">
                    <canvas id="graficoAvaliacoes" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-comment-dots me-2"></i> Últimos Comentários
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <?php if (count($feedbacks) > 0): ?>
                        <?php foreach (array_slice($feedbacks, 0, 5) as $feedback): ?>
                            <?php if (!empty($feedback['comentarios'])): ?>
                                <div class="mb-3 border-bottom pb-2">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= htmlspecialchars($feedback['cliente_nome']) ?></strong>
                                        <small class="text-muted"><?= date('d/m/Y', strtotime($feedback['created'])) ?></small>
                                    </div>
                                    <div class="mt-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $feedback['avaliacao'] ? 'text-warning' : 'text-secondary' ?>" style="font-size: 0.8rem;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($feedback['comentarios'])) ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Nenhum comentário recebido ainda.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

  </main>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script>
function mostrarAba(id) {
  document.querySelectorAll('.aba').forEach(aba => aba.classList.remove('ativa'));
  document.getElementById(id).classList.add('ativa');
}

function imprimirRelatorio() {
  const conteudo = document.getElementById('conteudo-relatorio').innerHTML;
  const win = window.open('', '', 'height=700,width=900');
  win.document.write('<html><head><title>Relatório Financeiro</title>');
  win.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />');
  win.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />');
  win.document.write('<style>body{padding:20px}</style>');
  win.document.write('</head><body>');
  win.document.write('<h2 class="text-center mb-4"><i class="fas fa-paw me-2"></i> AnimalSave - Relatório</h2>');
  win.document.write(conteudo);
  win.document.write('</body></html>');
  win.document.close();
  win.focus();
  setTimeout(() => { win.print(); win.close(); }, 500);
}

$(document).ready(function() {
  $('#tabela-clientes, #tabela-servicos, #tabela-agendamentos, #tabela-animais').DataTable({
    language: { 
      url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
      search: "Pesquisar:",
      lengthMenu: "Mostrar _MENU_ registros por página",
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      paginate: {
        first: "Primeira",
        last: "Última",
        next: "Próxima",
        previous: "Anterior"
      }
    }
  });

  const ctx = document.getElementById('graficoRelatorio').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($labelsGrafico) ?>,
      datasets: [{
        label: 'Atendimentos por dia',
        data: <?= json_encode($valoresGrafico) ?>,
        backgroundColor: '#0d6efd',
        borderRadius: 6,
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: { backgroundColor: '#333', padding: 12 }
      },
      scales: { 
        y: { 
          beginAtZero: true,
          grid: { color: '#f1f1f1' }
        },
        x: { 
          grid: { display: false }
        }
      }
    }
  });

  // Edição de serviços
  $(document).on('click', '.editar-servico', function() {
    const id = $(this).data('id');
    const nome = $(this).data('nome');
    const descricao = $(this).data('descricao');
    const preco = $(this).data('preco');
    
    $('#editar-id').val(id);
    $('#editar-nome').val(nome);
    $('#editar-descricao').val(descricao);
    $('#editar-preco').val(preco);
    
    const modal = new bootstrap.Modal(document.getElementById('modalEditarServico'));
    modal.show();
  });
});

// Mostrar mensagens de feedback
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['mensagem'])): ?>
        const mensagem = <?= json_encode($_SESSION['mensagem']) ?>;
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${mensagem.tipo} alert-dismissible fade show fixed-top mx-auto mt-3`;
        alerta.style.maxWidth = '500px';
        alerta.innerHTML = `
            ${mensagem.texto}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.prepend(alerta);
        setTimeout(() => alerta.remove(), 5000);
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>
});
</script>
<script>
  // Gráfico de avaliações
document.addEventListener('DOMContentLoaded', function() {
    // Inicializa a tabela de feedbacks com DataTables
    $('#tabela-feedbacks').DataTable({
        language: { 
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
            search: "Pesquisar:",
            lengthMenu: "Mostrar _MENU_ registros por página",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            paginate: {
                first: "Primeira",
                last: "Última",
                next: "Próxima",
                previous: "Anterior"
            }
        },
        order: [[0, 'desc']]
    });

    // Gráfico de distribuição de avaliações
    <?php
    // Conta quantas avaliações de cada tipo existem
    $distribuicao = [0, 0, 0, 0, 0]; // Índices 0-4 representam 1-5 estrelas
    foreach ($feedbacks as $feedback) {
        $distribuicao[$feedback['avaliacao'] - 1]++;
    }
    ?>
    
    const ctxAvaliacoes = document.getElementById('graficoAvaliacoes').getContext('2d');
    new Chart(ctxAvaliacoes, {
        type: 'bar',
        data: {
            labels: ['1 Estrela', '2 Estrelas', '3 Estrelas', '4 Estrelas', '5 Estrelas'],
            datasets: [{
                label: 'Quantidade de Avaliações',
                data: <?= json_encode($distribuicao) ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(255, 205, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(54, 162, 235, 0.7)'
                ],
                borderColor: [
                    'rgb(255, 99, 132)',
                    'rgb(255, 159, 64)',
                    'rgb(255, 205, 86)',
                    'rgb(75, 192, 192)',
                    'rgb(54, 162, 235)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.parsed.y} avaliações`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    }
                }
            }
        }
    });
});
</script>
<script>// Script para o modal de observações
document.addEventListener('DOMContentLoaded', function() {
    const modalObservacoes = document.getElementById('modalObservacoes');
    if (modalObservacoes) {
        modalObservacoes.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const observacoes = button.getAttribute('data-observacoes');
            const modalBody = modalObservacoes.querySelector('.modal-body #texto-observacoes');
            modalBody.textContent = observacoes;
        });
    }
});</script>
</body>
</html>