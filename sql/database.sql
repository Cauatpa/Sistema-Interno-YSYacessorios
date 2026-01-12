-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 12/01/2026 às 20:38
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `estoque`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_json`)),
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fechamentos`
--

CREATE TABLE `fechamentos` (
  `id` int(11) NOT NULL,
  `competencia` char(7) NOT NULL,
  `fechado_por` varchar(255) NOT NULL,
  `fechado_em` datetime NOT NULL,
  `total_registros` int(11) NOT NULL DEFAULT 0,
  `observacao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `retiradas`
--

CREATE TABLE `retiradas` (
  `id` int(11) NOT NULL,
  `competencia` char(7) NOT NULL,
  `status_mes` enum('ABERTO','FECHADO') NOT NULL DEFAULT 'ABERTO',
  `fechado_em` datetime DEFAULT NULL,
  `fechado_por` varchar(255) DEFAULT NULL,
  `produto` varchar(255) NOT NULL COMMENT 'Nome da peça',
  `tipo` varchar(20) NOT NULL,
  `quantidade_solicitada` int(11) NOT NULL COMMENT 'Qtd solicitada no pedido',
  `solicitante` varchar(255) NOT NULL COMMENT 'Quem solicitou',
  `data_pedido` datetime NOT NULL COMMENT 'Data e hora do pedido',
  `quantidade_retirada` int(11) DEFAULT NULL COMMENT 'Qtd retirada no estoque',
  `responsavel_estoque` varchar(255) DEFAULT NULL COMMENT 'Quem foi ao estoque',
  `data_finalizacao` datetime DEFAULT NULL COMMENT 'Data e hora da retirada',
  `precisa_balanco` tinyint(1) DEFAULT 0 COMMENT 'Se precisa fazer balanço',
  `falta_estoque` tinyint(1) DEFAULT 0,
  `status` enum('pedido','finalizado') DEFAULT 'pedido',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(255) DEFAULT NULL,
  `sem_estoque` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `role` enum('admin','operador','visualizador') NOT NULL DEFAULT 'visualizador',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entity` (`entity`,`entity_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Índices de tabela `fechamentos`
--
ALTER TABLE `fechamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fechamentos_competencia` (`competencia`);

--
-- Índices de tabela `retiradas`
--
ALTER TABLE `retiradas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_retiradas_competencia_status` (`competencia`,`status_mes`),
  ADD KEY `idx_retiradas_data_pedido` (`data_pedido`),
  ADD KEY `idx_retiradas_deleted_at` (`deleted_at`),
  ADD KEY `idx_retiradas_comp_data` (`competencia`,`data_pedido`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `fechamentos`
--
ALTER TABLE `fechamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `retiradas`
--
ALTER TABLE `retiradas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
