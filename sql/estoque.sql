-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 15/01/2026 às 12:52
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
-- Estrutura para tabela `app_settings`
--

CREATE TABLE `app_settings` (
  `key` varchar(64) NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `app_settings`
--

INSERT INTO `app_settings` (`key`, `value`) VALUES
('init_admin_done', '1');

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
  `success` tinyint(1) NOT NULL DEFAULT 1,
  `event_code` varchar(80) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `before_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`before_json`)),
  `after_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`after_json`)),
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_json`)),
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity`, `entity_id`, `success`, `event_code`, `message`, `before_json`, `after_json`, `payload_json`, `ip`, `user_agent`, `created_at`) VALUES
(77, 1, 'create', 'retirada', 36, 1, NULL, 'OK: create retirada#36', NULL, '{\"id\":36,\"produto\":\"Anel\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"Cauã2\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"Anel\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"Cauã2\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 08:43:38'),
(78, 1, 'create', 'retirada', 37, 1, NULL, 'OK: create retirada#37', NULL, '{\"id\":37,\"produto\":\"Anel\",\"quantidade_solicitada\":1,\"tipo\":\"ouro\",\"solicitante\":\"Cauã2\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"Anel\",\"tipo\":\"ouro\",\"quantidade_solicitada\":1,\"solicitante\":\"Cauã2\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 08:43:48'),
(79, 1, 'create', 'retirada', 38, 1, NULL, 'OK: create retirada#38', NULL, '{\"id\":38,\"produto\":\"Colar\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"Cauã2\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"Colar\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"Cauã2\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 08:43:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fechamentos`
--

CREATE TABLE `fechamentos` (
  `id` int(11) NOT NULL,
  `competencia` char(7) NOT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `fechado_por` varchar(255) NOT NULL,
  `fechado_em` datetime NOT NULL,
  `total_registros` int(11) NOT NULL DEFAULT 0,
  `observacao` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `usuario` varchar(191) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip`, `usuario`, `success`, `created_at`) VALUES
(14, '::1', 'caua', 0, '2026-01-13 09:35:23'),
(15, '::1', 'caua', 1, '2026-01-13 09:35:25'),
(16, '::1', 'caua', 1, '2026-01-13 15:19:25'),
(17, '::1', 'caua', 1, '2026-01-13 16:55:34'),
(18, '::1', 'caua', 1, '2026-01-14 08:02:27'),
(19, '::1', 'teste', 1, '2026-01-14 14:43:22'),
(20, '::1', 'caua', 1, '2026-01-14 14:43:48'),
(21, '::1', 'caua', 1, '2026-01-15 08:03:24');

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

--
-- Despejando dados para a tabela `retiradas`
--

INSERT INTO `retiradas` (`id`, `competencia`, `status_mes`, `fechado_em`, `fechado_por`, `produto`, `tipo`, `quantidade_solicitada`, `solicitante`, `data_pedido`, `quantidade_retirada`, `responsavel_estoque`, `data_finalizacao`, `precisa_balanco`, `falta_estoque`, `status`, `created_at`, `deleted_at`, `deleted_by`, `sem_estoque`) VALUES
(36, '2026-01', 'ABERTO', NULL, NULL, 'Anel', 'prata', 1, 'Cauã2', '2026-01-15 08:43:38', 0, 'Cauã', '2026-01-15 08:44:09', 0, 0, 'finalizado', '2026-01-15 11:43:38', NULL, NULL, 1),
(37, '2026-01', 'ABERTO', NULL, NULL, 'Anel', 'ouro', 1, 'Cauã2', '2026-01-15 08:43:48', 1, 'Cauã', '2026-01-15 08:44:16', 1, 0, 'finalizado', '2026-01-15 11:43:48', NULL, NULL, 0),
(38, '2026-01', 'ABERTO', NULL, NULL, 'Colar', 'prata', 1, 'Cauã2', '2026-01-15 08:43:56', NULL, NULL, NULL, 0, 0, 'pedido', '2026-01-15 11:43:56', NULL, NULL, 0);

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
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `nome`, `usuario`, `senha_hash`, `role`, `ativo`, `created_at`, `last_login_at`) VALUES
(1, 'Cauã', 'caua', '$2y$10$X0rTnjOtppJ8/1baVl1l6.wmi5HbGUaO9AciUI8yn9nDwZI4uGCUS', 'admin', 1, '2026-01-09 11:39:10', NULL),
(2, 'Teste', 'Teste', '$2y$10$SE4WxVT4gLcmN/CJLRCZDe0x9adem7qzFT2ZCY9aKnbFASxG18s1W', 'operador', 1, '2026-01-09 13:22:17', NULL),
(3, 'Teste2', 'Teste2', '$2y$10$nUXYRS8jLEpTnfs4MvShv.OWaV6WvSX5jb3Skk6HHnPp1wAUOxGha', 'visualizador', 0, '2026-01-12 15:32:17', NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`key`);

--
-- Índices de tabela `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entity` (`entity`,`entity_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_success` (`success`),
  ADD KEY `idx_event_code` (`event_code`);

--
-- Índices de tabela `fechamentos`
--
ALTER TABLE `fechamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fechamentos_competencia` (`competencia`);

--
-- Índices de tabela `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip`,`created_at`),
  ADD KEY `idx_usuario_time` (`usuario`,`created_at`),
  ADD KEY `idx_ip_usuario_time` (`ip`,`usuario`,`created_at`),
  ADD KEY `idx_login_attempts_ip_user_time` (`ip`,`usuario`,`created_at`),
  ADD KEY `idx_login_attempts_time` (`created_at`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT de tabela `fechamentos`
--
ALTER TABLE `fechamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de tabela `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `retiradas`
--
ALTER TABLE `retiradas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
