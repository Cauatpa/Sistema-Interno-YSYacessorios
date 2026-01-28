-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 28/01/2026 às 19:30
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

-- --------------------------------------------------------

--
-- Estrutura para tabela `lotes`
--

CREATE TABLE `lotes` (
  `id` int(11) NOT NULL,
  `competencia` char(7) NOT NULL,
  `codigo` varchar(60) NOT NULL,
  `data_recebimento` date DEFAULT NULL,
  `fornecedor` varchar(120) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('aberto','conferido','fechado') NOT NULL DEFAULT 'aberto',
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `lote_itens`
--

CREATE TABLE `lote_itens` (
  `id` int(11) NOT NULL,
  `lote_id` int(11) NOT NULL,
  `recebimento_id` int(11) DEFAULT NULL,
  `produto_id` int(10) UNSIGNED DEFAULT NULL,
  `produto_nome` varchar(200) NOT NULL,
  `variacao` varchar(80) DEFAULT NULL,
  `qtd_prevista` int(11) NOT NULL DEFAULT 0,
  `qtd_conferida` int(11) DEFAULT NULL,
  `situacao` enum('ok','faltando','a_mais','banho_trocado','quebra','outro') NOT NULL DEFAULT 'ok',
  `nota` varchar(255) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `lote_recebimentos`
--

CREATE TABLE `lote_recebimentos` (
  `id` int(11) NOT NULL,
  `lote_id` int(11) NOT NULL,
  `data_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `recebido_por` int(11) DEFAULT NULL,
  `volume_label` varchar(40) DEFAULT NULL,
  `rastreio` varchar(80) DEFAULT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(255) NOT NULL,
  `nome_norm` varchar(255) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
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
  `itens_solicitados` int(11) NOT NULL DEFAULT 1,
  `solicitante` varchar(255) NOT NULL COMMENT 'Quem solicitou',
  `data_pedido` datetime NOT NULL COMMENT 'Data e hora do pedido',
  `quantidade_retirada` int(11) DEFAULT NULL COMMENT 'Qtd retirada no estoque',
  `responsavel_estoque` varchar(255) DEFAULT NULL COMMENT 'Quem foi ao estoque',
  `data_finalizacao` datetime DEFAULT NULL COMMENT 'Data e hora da retirada',
  `precisa_balanco` tinyint(1) DEFAULT 0 COMMENT 'Se precisa fazer balanço',
  `balanco_feito` tinyint(1) NOT NULL DEFAULT 0,
  `balanco_feito_em` datetime DEFAULT NULL,
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
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `nome`, `usuario`, `senha_hash`, `role`, `ativo`, `created_at`, `last_login_at`) VALUES
(1, 'Cauã', 'caua', '$2y$10$VTwpwLB9/SLY6ybWGbou1uvPNhvzXS0xRCaS2wjPvOS8yDm3t5/aW', 'admin', 1, '2026-01-09 11:39:10', NULL),
(2, 'Teste', 'Teste', '$2y$10$SE4WxVT4gLcmN/CJLRCZDe0x9adem7qzFT2ZCY9aKnbFASxG18s1W', 'operador', 1, '2026-01-09 13:22:17', NULL),
(3, 'Teste2', 'Teste2', '$2y$10$S.IFAIMEtCUuFmE3o2OXreFCqqdbVh9/l.VfKCnkD5//.XrhxEoKm', 'visualizador', 1, '2026-01-12 15:32:17', NULL),
(8, 'Jocyene', 'Jocyene', '$2y$10$Z85pjc.vayqqk6NjXiLUfeUXpdn0jHwqKKWLYf68L/opaUYNR5S7m', 'admin', 1, '2026-01-15 12:20:50', NULL);

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
  ADD KEY `idx_event_code` (`event_code`),
  ADD KEY `idx_audit_created` (`created_at`),
  ADD KEY `idx_audit_action_entity` (`action`,`entity`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_event` (`event_code`);

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
-- Índices de tabela `lotes`
--
ALTER TABLE `lotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lotes_codigo` (`codigo`),
  ADD KEY `idx_lotes_data` (`data_recebimento`),
  ADD KEY `idx_lotes_status` (`status`),
  ADD KEY `fk_lotes_user` (`criado_por`),
  ADD KEY `idx_lotes_competencia` (`competencia`),
  ADD KEY `idx_lotes_deleted_at` (`deleted_at`);

--
-- Índices de tabela `lote_itens`
--
ALTER TABLE `lote_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_itens_lote` (`lote_id`),
  ADD KEY `idx_itens_produto` (`produto_id`),
  ADD KEY `idx_itens_situacao` (`situacao`),
  ADD KEY `idx_itens_recebimento` (`recebimento_id`),
  ADD KEY `idx_lote_itens_deleted_at` (`deleted_at`);

--
-- Índices de tabela `lote_recebimentos`
--
ALTER TABLE `lote_recebimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rec_lote` (`lote_id`),
  ADD KEY `idx_rec_data` (`data_hora`),
  ADD KEY `idx_rec_recebido_por` (`recebido_por`),
  ADD KEY `idx_lote_recebimentos_deleted_at` (`deleted_at`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_produtos_nome_norm` (`nome_norm`);

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
-- AUTO_INCREMENT de tabela `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `lotes`
--
ALTER TABLE `lotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `lote_itens`
--
ALTER TABLE `lote_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `lote_recebimentos`
--
ALTER TABLE `lote_recebimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `retiradas`
--
ALTER TABLE `retiradas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `lotes`
--
ALTER TABLE `lotes`
  ADD CONSTRAINT `fk_lotes_user` FOREIGN KEY (`criado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `lote_itens`
--
ALTER TABLE `lote_itens`
  ADD CONSTRAINT `fk_itens_lote` FOREIGN KEY (`lote_id`) REFERENCES `lotes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_itens_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_itens_recebimento` FOREIGN KEY (`recebimento_id`) REFERENCES `lote_recebimentos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `lote_recebimentos`
--
ALTER TABLE `lote_recebimentos`
  ADD CONSTRAINT `fk_rec_lote` FOREIGN KEY (`lote_id`) REFERENCES `lotes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rec_user` FOREIGN KEY (`recebido_por`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
