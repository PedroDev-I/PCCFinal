-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 10/06/2025 às 09:19
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `banco_animalsave`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `agendamentos`
--

CREATE TABLE `agendamentos` (
  `id_agendamento` int(11) NOT NULL,
  `id_animal` int(11) DEFAULT NULL,
  `data_hora` datetime NOT NULL,
  `id_servico` int(11) DEFAULT NULL,
  `status` enum('confirmado','cancelado','concluído') DEFAULT 'confirmado',
  `valor` decimal(10,2) NOT NULL,
  `observacoes` text DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_cliente` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `agendamentos`
--

INSERT INTO `agendamentos` (`id_agendamento`, `id_animal`, `data_hora`, `id_servico`, `status`, `valor`, `observacoes`, `created`, `modified`, `id_cliente`) VALUES
(43, 13, '2025-06-12 11:45:00', 3, 'concluído', 89.99, 'Olá', '2025-06-10 04:55:25', '2025-06-10 05:17:24', 12),
(48, 13, '2025-05-10 11:11:00', 1, 'cancelado', 49.99, '', '2025-06-10 05:41:30', '2025-06-10 05:53:14', 12),
(49, 13, '2025-07-10 11:11:00', 3, 'cancelado', 89.99, '', '2025-06-10 05:47:57', '2025-06-10 05:56:30', 12),
(50, 13, '2025-07-11 11:11:00', 3, 'cancelado', 89.99, '', '2025-06-10 05:55:16', '2025-06-10 05:56:27', NULL),
(51, 13, '2025-11-11 11:11:00', 2, 'concluído', 39.99, '', '2025-06-10 06:09:19', '2025-06-10 06:46:54', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `animais`
--

CREATE TABLE `animais` (
  `id_animal` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `raca` varchar(100) DEFAULT NULL,
  `idade` int(11) DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `animais`
--

INSERT INTO `animais` (`id_animal`, `id_cliente`, `nome`, `foto`, `tipo`, `raca`, `idade`, `peso`, `observacoes`, `created`, `modified`) VALUES
(13, 12, 'Jack', '', 'Cachorro', 'Husky', 3, 3.00, NULL, '2025-06-10 04:55:12', '2025-06-10 04:55:12');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `foto` varchar(255) NOT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `nome`, `cpf`, `foto`, `telefone`, `endereco`, `email`, `senha`, `created`, `modified`) VALUES
(12, 'Pedro', NULL, '', NULL, NULL, 'pedro123@gmail.com', '$2y$10$NhjGdN1H7nOLxaWauGc2GePfyyZqU7gMra7ZmpzF6uo2p7H9SREkG', '2025-06-10 04:44:22', '2025-06-10 04:44:22');

-- --------------------------------------------------------

--
-- Estrutura para tabela `code`
--

CREATE TABLE `code` (
  `code` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `code`
--

INSERT INTO `code` (`code`) VALUES
(123456);

-- --------------------------------------------------------

--
-- Estrutura para tabela `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id_feedback` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_agendamento` int(11) DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  `avaliacao` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `feedbacks`
--

INSERT INTO `feedbacks` (`id_feedback`, `id_cliente`, `id_agendamento`, `comentarios`, `avaliacao`, `created`, `modified`) VALUES
(8, 12, 43, 'Ótimo!', 5, '2025-06-10 05:00:02', '2025-06-10 05:00:02');

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionarios`
--

CREATE TABLE `funcionarios` (
  `id_funcionario` int(11) NOT NULL,
  `codigo` int(6) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  `servicos_que_realiza` text DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `senha` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `funcionarios`
--

INSERT INTO `funcionarios` (`id_funcionario`, `codigo`, `nome`, `foto`, `telefone`, `servicos_que_realiza`, `created`, `modified`, `senha`) VALUES
(2, 555666, 'Pedro', '', NULL, NULL, '2025-06-04 00:24:33', '2025-06-04 00:24:33', '$2y$10$TII9sQjwav9H1P2U/H5C7O2qaepp.sjdaIHa9gmXeXxcbh5SYiUcm'),
(3, 777888, 'Isaac', '', NULL, NULL, '2025-06-04 00:25:31', '2025-06-04 00:25:31', '$2y$10$UX1tZEfe/eujveKp/1c2xedSoE8a1afq2zHAiaFzVxZgHg2KOe5ym');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id_pagamento` int(11) NOT NULL,
  `id_agendamento` int(11) DEFAULT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `forma_pagamento` enum('dinheiro','cartão','pix') NOT NULL,
  `data_pagamento` datetime NOT NULL,
  `status` enum('pago','pendente') DEFAULT 'pendente',
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pagamentos`
--

INSERT INTO `pagamentos` (`id_pagamento`, `id_agendamento`, `valor_pago`, `forma_pagamento`, `data_pagamento`, `status`, `created`, `modified`) VALUES
(44, 43, 89.99, 'pix', '2025-06-10 06:55:27', 'pendente', '2025-06-10 04:55:27', '2025-06-10 04:55:27'),
(49, 48, 49.99, 'pix', '2025-06-10 07:41:32', 'pendente', '2025-06-10 05:41:32', '2025-06-10 05:41:32'),
(50, 49, 89.99, 'pix', '2025-06-10 07:47:59', 'pendente', '2025-06-10 05:47:59', '2025-06-10 05:47:59'),
(51, 50, 89.99, 'pix', '2025-06-10 07:55:19', 'pendente', '2025-06-10 05:55:19', '2025-06-10 05:55:19'),
(52, 51, 39.99, 'pix', '2025-06-10 08:09:21', 'pendente', '2025-06-10 06:09:21', '2025-06-10 06:09:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos`
--

CREATE TABLE `servicos` (
  `id_servico` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco` decimal(10,2) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `servicos`
--

INSERT INTO `servicos` (`id_servico`, `nome`, `descricao`, `preco`, `created`, `modified`) VALUES
(1, 'Banho', '', 49.99, '2025-06-03 04:20:39', '2025-06-08 23:47:00'),
(2, 'Tosa', '', 39.99, '2025-06-03 04:20:39', '2025-06-08 23:39:26'),
(3, 'Banho com Tosa', '', 89.99, '2025-06-03 04:20:39', '2025-06-10 02:02:22');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD PRIMARY KEY (`id_agendamento`),
  ADD KEY `id_animal` (`id_animal`),
  ADD KEY `id_servico` (`id_servico`),
  ADD KEY `fk_agendamento_cliente` (`id_cliente`);

--
-- Índices de tabela `animais`
--
ALTER TABLE `animais`
  ADD PRIMARY KEY (`id_animal`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices de tabela `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id_feedback`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_agendamento` (`id_agendamento`);

--
-- Índices de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD PRIMARY KEY (`id_funcionario`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id_pagamento`),
  ADD KEY `id_agendamento` (`id_agendamento`);

--
-- Índices de tabela `servicos`
--
ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id_servico`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  MODIFY `id_agendamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT de tabela `animais`
--
ALTER TABLE `animais`
  MODIFY `id_animal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id_feedback` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  MODIFY `id_funcionario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id_pagamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de tabela `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id_servico` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD CONSTRAINT `agendamentos_ibfk_1` FOREIGN KEY (`id_animal`) REFERENCES `animais` (`id_animal`) ON DELETE CASCADE,
  ADD CONSTRAINT `agendamentos_ibfk_2` FOREIGN KEY (`id_servico`) REFERENCES `servicos` (`id_servico`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_agendamento_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`);

--
-- Restrições para tabelas `animais`
--
ALTER TABLE `animais`
  ADD CONSTRAINT `animais_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE;

--
-- Restrições para tabelas `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `feedbacks_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedbacks_ibfk_2` FOREIGN KEY (`id_agendamento`) REFERENCES `agendamentos` (`id_agendamento`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`id_agendamento`) REFERENCES `agendamentos` (`id_agendamento`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
