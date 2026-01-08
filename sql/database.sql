-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 08/01/2026 às 16:35
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
-- Estrutura para tabela `retiradas`
--

CREATE TABLE `retiradas` (
  `id` int(11) NOT NULL,
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
  `sem_estoque` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `retiradas`
--

INSERT INTO `retiradas` (`id`, `produto`, `tipo`, `quantidade_solicitada`, `solicitante`, `data_pedido`, `quantidade_retirada`, `responsavel_estoque`, `data_finalizacao`, `precisa_balanco`, `falta_estoque`, `status`, `created_at`, `sem_estoque`) VALUES
(1, 'Anel', 'prata', 1, 'Cauã', '2026-01-08 11:22:42', NULL, 'Cauã', '2026-01-08 11:28:40', 0, 0, 'finalizado', '2026-01-08 14:22:42', 0),
(13, 'Colar', 'ouro', 1, 'Cauã', '2026-01-08 12:17:03', 1, 'Cauã', '2026-01-08 12:17:29', 1, 0, 'finalizado', '2026-01-08 15:17:03', 0),
(14, 'Colar', 'prata', 1, 'Cauã', '2026-01-08 12:17:37', 0, 'Cauã', '2026-01-08 12:17:42', 1, 0, 'finalizado', '2026-01-08 15:17:37', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `retiradas`
--
ALTER TABLE `retiradas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `retiradas`
--
ALTER TABLE `retiradas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
