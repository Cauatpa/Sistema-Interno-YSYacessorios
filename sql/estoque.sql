-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 16/01/2026 às 16:03
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
(99, 8, 'create', 'retirada', 43, 1, NULL, 'OK: create retirada#43', NULL, '{\"id\":43,\"produto\":\"CL Bolinha Veneziana\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Bolinha Veneziana\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:17:36'),
(100, 8, 'create', 'retirada', 44, 1, NULL, 'OK: create retirada#44', NULL, '{\"id\":44,\"produto\":\"CL Gravata Allis\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Gravata Allis\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:17:51'),
(101, 8, 'create', 'retirada', 45, 1, NULL, 'OK: create retirada#45', NULL, '{\"id\":45,\"produto\":\"ESCAP. Red. Esp. Santo\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"ESCAP. Red. Esp. Santo\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:18:13'),
(102, 8, 'create', 'retirada', 46, 1, NULL, 'OK: create retirada#46', NULL, '{\"id\":46,\"produto\":\"CL Gravata Gotas\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Gravata Gotas\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:18:24'),
(103, 8, 'create', 'retirada', 47, 1, NULL, 'OK: create retirada#47', NULL, '{\"id\":47,\"produto\":\"CL Roma\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Roma\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:18:35'),
(104, 8, 'create', 'retirada', 48, 1, NULL, 'OK: create retirada#48', NULL, '{\"id\":48,\"produto\":\"CL Ponto de Luz\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Ponto de Luz\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:18:44'),
(105, 8, 'create', 'retirada', 49, 1, NULL, 'OK: create retirada#49', NULL, '{\"id\":49,\"produto\":\"CL Ponto de Luz Oval\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Ponto de Luz Oval\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:19:00'),
(106, 8, 'create', 'retirada', 50, 1, NULL, 'OK: create retirada#50', NULL, '{\"id\":50,\"produto\":\"CL Medalha Inicial - Prata - G\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Medalha Inicial - Prata - G\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:19:19'),
(107, 8, 'create', 'retirada', 51, 1, NULL, 'OK: create retirada#51', NULL, '{\"id\":51,\"produto\":\"CL Medalha Inicial - Prata - E\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Medalha Inicial - Prata - E\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:19:38'),
(108, 8, 'create', 'retirada', 52, 1, NULL, 'OK: create retirada#52', NULL, '{\"id\":52,\"produto\":\"CL Amalfi Duplo\",\"quantidade_solicitada\":50,\"tipo\":\"ouro\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Amalfi Duplo\",\"tipo\":\"ouro\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:19:49'),
(109, 8, 'create', 'retirada', 53, 1, NULL, 'OK: create retirada#53', NULL, '{\"id\":53,\"produto\":\"CL Aura\",\"quantidade_solicitada\":50,\"tipo\":\"ouro\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Aura\",\"tipo\":\"ouro\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:19:59'),
(110, 8, 'create', 'retirada', 54, 1, NULL, 'OK: create retirada#54', NULL, '{\"id\":54,\"produto\":\"ESCAP . Nª Senhora\",\"quantidade_solicitada\":50,\"tipo\":\"ouro\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"ESCAP . Nª Senhora\",\"tipo\":\"ouro\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:20:14'),
(111, 8, 'create', 'retirada', 55, 1, NULL, 'OK: create retirada#55', NULL, '{\"id\":55,\"produto\":\"CL Gravata Gotas\",\"quantidade_solicitada\":50,\"tipo\":\"ouro\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Gravata Gotas\",\"tipo\":\"ouro\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:20:26'),
(112, 8, 'create', 'retirada', 56, 1, NULL, 'OK: create retirada#56', NULL, '{\"id\":56,\"produto\":\"CL Mini Cristo\",\"quantidade_solicitada\":50,\"tipo\":\"ouro\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Mini Cristo\",\"tipo\":\"ouro\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:20:39'),
(113, 8, 'create', 'retirada', 57, 1, NULL, 'OK: create retirada#57', NULL, '{\"id\":57,\"produto\":\"AN Bojudo - Reg. - P\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AN Bojudo - Reg. - P\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:29:09'),
(114, 8, 'create', 'retirada', 58, 1, NULL, 'OK: create retirada#58', NULL, '{\"id\":58,\"produto\":\"HC Elos\",\"quantidade_solicitada\":25,\"tipo\":\"prata\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"HC Elos\",\"tipo\":\"prata\",\"quantidade_solicitada\":25,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:29:48'),
(115, 8, 'create', 'retirada', 59, 1, NULL, 'OK: create retirada#59', NULL, '{\"id\":59,\"produto\":\"TZ Riviera\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"TZ Riviera\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:30:06'),
(116, 8, 'delete', 'retirada', 59, 1, NULL, 'Excluiu o pedido #59 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 14:30:33\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:30:33'),
(117, 8, 'delete', 'retirada', 58, 1, NULL, 'Excluiu o pedido #58 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 14:30:44\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:30:44'),
(118, 8, 'create', 'retirada', 60, 1, NULL, 'OK: create retirada#60', NULL, '{\"id\":60,\"produto\":\"AN Bojudo - Reg. - G\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AN Bojudo - Reg. - G\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:31:05'),
(119, 8, 'create', 'retirada', 61, 1, NULL, 'OK: create retirada#61', NULL, '{\"id\":61,\"produto\":\"AN Coração Trabalhado - Reg. - G\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AN Coração Trabalhado - Reg. - G\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:31:39'),
(120, 8, 'create', 'retirada', 62, 1, NULL, 'OK: create retirada#62', NULL, '{\"id\":62,\"produto\":\"AN Cravejado - Reg. - P\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AN Cravejado - Reg. - P\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:32:13'),
(121, 8, 'create', 'retirada', 63, 1, NULL, 'OK: create retirada#63', NULL, '{\"id\":63,\"produto\":\"AN Duplo - Reg. - G\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AN Duplo - Reg. - G\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:32:36'),
(122, 8, 'create', 'retirada', 64, 1, NULL, 'OK: create retirada#64', NULL, '{\"id\":64,\"produto\":\"AN Cloe - Reg. - G\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AN Cloe - Reg. - G\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:33:15'),
(123, 8, 'create', 'retirada', 65, 1, NULL, 'OK: create retirada#65', NULL, '{\"id\":65,\"produto\":\"AN Mini Zircônias - Reg. - P\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AN Mini Zircônias - Reg. - P\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:33:46'),
(124, 8, 'create', 'retirada', 66, 1, NULL, 'OK: create retirada#66', NULL, '{\"id\":66,\"produto\":\"AN Três Zircônias - Reg. - P\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AN Três Zircônias - Reg. - P\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:34:17'),
(125, 8, 'create', 'retirada', 67, 1, NULL, 'OK: create retirada#67', NULL, '{\"id\":67,\"produto\":\"PC Furo Torcido (nariz)\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"PC Furo Torcido (nariz)\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:35:02'),
(126, 8, 'delete', 'retirada', NULL, 1, NULL, 'OK: delete retirada', NULL, NULL, '{\"competencia\":\"2026-01\"}', '192.168.1.95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:35:14'),
(127, 8, 'reopen_month', 'fechamento', NULL, 1, NULL, 'Reabriu o mês 2026-01.', '{\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"status\":\"aberto\"}', '{\"competencia\":\"2026-01\",\"had_closure\":1,\"xlsx\":{\"status\":\"removed\",\"sheet\":\"2026-01\"}}', '192.168.1.95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:36:51'),
(128, 8, 'create', 'retirada', 68, 1, NULL, 'OK: create retirada#68', NULL, '{\"id\":68,\"produto\":\"PC Furo Zara\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"PC Furo Zara\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:37:12'),
(129, 8, 'create', 'retirada', 69, 1, NULL, 'OK: create retirada#69', NULL, '{\"id\":69,\"produto\":\"PC Furo Zara\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"PC Furo Zara\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 14:37:34'),
(130, 8, 'edit', 'retirada', 39, 1, NULL, 'Editou pedido #39 (2026-01).', '[]', '[]', '{\"competencia\":\"2026-01\"}', '192.168.1.95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 15:21:19'),
(131, 8, 'create', 'retirada', 70, 1, NULL, 'OK: create retirada#70', NULL, '{\"id\":70,\"produto\":\"TR Shine\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thalia\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"TR Shine\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thalia\"}', '192.168.1.95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 15:24:32'),
(132, 8, 'create', 'retirada', 71, 1, NULL, 'OK: create retirada#71', NULL, '{\"id\":71,\"produto\":\"AG Lisa M\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Thalia\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AG Lisa M\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Thalia\"}', '192.168.1.95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 15:26:50'),
(133, 8, 'create', 'retirada', 72, 1, NULL, 'OK: create retirada#72', NULL, '{\"id\":72,\"produto\":\"AG Lisa M\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Thalia\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AG Lisa M\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Thalia\"}', '192.168.1.95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 15:27:02'),
(134, 8, 'create', 'retirada', 73, 1, NULL, 'OK: create retirada#73', NULL, '{\"id\":73,\"produto\":\"AG Lisa G\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Thalia\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AG Lisa G\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Thalia\"}', '192.168.1.95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 15:27:20'),
(135, 8, 'create', 'retirada', 74, 1, NULL, 'OK: create retirada#74', NULL, '{\"id\":74,\"produto\":\"AG Lisa G\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Thalia\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AG Lisa G\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Thalia\"}', '192.168.1.95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 15:27:28'),
(136, 1, 'create', 'retirada', 75, 1, NULL, 'OK: create retirada#75', NULL, '{\"id\":75,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"Cauã2\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"Cauã2\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 16:31:18'),
(137, 1, 'edit', 'retirada', 71, 1, NULL, 'Editou pedido #71 (2026-01).', '{\"solicitante\":\"Thalia\"}', '{\"solicitante\":\"Thalia, Izadora\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 16:34:07'),
(138, 1, 'edit', 'retirada', 72, 1, NULL, 'Editou pedido #72 (2026-01).', '{\"solicitante\":\"Thalia\"}', '{\"solicitante\":\"Thalia, Izadora\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 16:34:16'),
(139, 1, 'edit', 'retirada', 73, 1, NULL, 'Editou pedido #73 (2026-01).', '{\"solicitante\":\"Thalia\"}', '{\"solicitante\":\"Thalia, Izadora\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 16:34:30'),
(140, 1, 'edit', 'retirada', 74, 1, NULL, 'Editou pedido #74 (2026-01).', '{\"solicitante\":\"Thalia\"}', '{\"solicitante\":\"Thalia, Izadora\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 16:34:45'),
(141, 1, 'create', 'retirada', 76, 1, NULL, 'OK: create retirada#76', NULL, '{\"id\":76,\"produto\":\"teste2\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"Cauã2\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste2\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"Cauã2\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 16:34:57'),
(142, 1, 'delete', 'retirada', 76, 1, NULL, 'Excluiu o pedido #76 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 16:37:43\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 16:37:43'),
(143, 1, 'create', 'retirada', 77, 1, NULL, 'OK: create retirada#77', NULL, '{\"id\":77,\"produto\":\"teste2\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"Cauã\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste2\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"Cauã\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 16:37:55'),
(144, 1, 'delete', 'retirada', 77, 1, NULL, 'Excluiu o pedido #77 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 16:38:05\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 16:38:05'),
(145, 1, 'create', 'retirada', 78, 1, NULL, 'OK: create retirada#78', NULL, '{\"id\":78,\"produto\":\"teste2\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"Cauã\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste2\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"Cauã\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 16:38:12'),
(146, 1, 'delete', 'retirada', 78, 1, NULL, 'Excluiu o pedido #78 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 17:01:46\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:01:46'),
(147, 1, 'delete', 'retirada', 75, 1, NULL, 'Excluiu o pedido #75 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 17:01:51\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:01:51'),
(148, 1, 'create', 'retirada', 79, 1, NULL, 'OK: create retirada#79', NULL, '{\"id\":79,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"caua\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"caua\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:03:22'),
(149, 1, 'delete', 'retirada', 79, 1, NULL, 'Excluiu o pedido #79 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 17:03:31\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:03:31'),
(150, 1, 'create', 'retirada', 80, 1, NULL, 'OK: create retirada#80', NULL, '{\"id\":80,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"caua\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"caua\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:03:40'),
(151, 1, 'delete', 'retirada', 80, 1, NULL, 'Excluiu o pedido #80 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 17:03:48\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:03:48'),
(152, 1, 'create', 'retirada', 81, 1, NULL, 'OK: create retirada#81', NULL, '{\"id\":81,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:16:58'),
(153, 1, 'delete', 'retirada', 81, 1, NULL, 'Excluiu o pedido #81 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 17:17:07\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:17:07'),
(154, 1, 'create', 'retirada', 82, 1, NULL, 'OK: create retirada#82', NULL, '{\"id\":82,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:24:10'),
(155, 1, 'create', 'retirada', 83, 1, NULL, 'OK: create retirada#83', NULL, '{\"id\":83,\"produto\":\"yteste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"yteste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:24:24'),
(156, 1, 'delete', 'retirada', 83, 1, NULL, 'Excluiu o pedido #83 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 17:24:37\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:24:37'),
(157, 1, 'delete', 'retirada', 82, 1, NULL, 'Excluiu o pedido #82 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 17:24:44\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:24:44'),
(158, 1, 'create', 'retirada', 84, 1, NULL, 'OK: create retirada#84', NULL, '{\"id\":84,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:33:19'),
(159, 1, 'delete', 'retirada', 84, 1, NULL, 'Excluiu o pedido #84 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 17:33:28\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:33:28'),
(160, 1, 'create', 'retirada', 85, 1, NULL, 'OK: create retirada#85', NULL, '{\"id\":85,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:40:53'),
(161, 1, 'create', 'retirada', 86, 1, NULL, 'OK: create retirada#86', NULL, '{\"id\":86,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:41:08'),
(162, 1, 'delete', 'retirada', 86, 1, NULL, 'Excluiu o pedido #86 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 17:41:18\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:41:18'),
(163, 1, 'delete', 'retirada', 85, 1, NULL, 'Excluiu o pedido #85 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 17:41:23\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:41:23'),
(164, 1, 'create', 'retirada', 87, 1, NULL, 'OK: create retirada#87', NULL, '{\"id\":87,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:47:21'),
(165, 1, 'create', 'retirada', 88, 1, NULL, 'OK: create retirada#88', NULL, '{\"id\":88,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:48:20'),
(166, 1, 'create', 'retirada', 89, 1, NULL, 'OK: create retirada#89', NULL, '{\"id\":89,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:48:28'),
(167, 1, 'create', 'retirada', 90, 1, NULL, 'OK: create retirada#90', NULL, '{\"id\":90,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:48:34'),
(168, 1, 'delete', 'retirada', 89, 1, NULL, 'Excluiu o pedido #89 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 17:48:58\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:48:58'),
(169, 1, 'delete', 'retirada', 90, 1, NULL, 'Excluiu o pedido #90 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 17:49:03\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:49:03'),
(170, 1, 'delete', 'retirada', 88, 1, NULL, 'Excluiu o pedido #88 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 17:54:31\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:54:31'),
(171, 1, 'create', 'retirada', 91, 1, NULL, 'OK: create retirada#91', NULL, '{\"id\":91,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 17:54:40'),
(172, 1, 'delete', 'retirada', 91, 1, NULL, 'Excluiu o pedido #91 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 18:00:10\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 18:00:10'),
(173, 1, 'delete', 'retirada', 87, 1, NULL, 'Excluiu o pedido #87 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 18:00:16\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 18:00:16'),
(174, 1, 'create', 'retirada', 92, 1, NULL, 'OK: create retirada#92', NULL, '{\"id\":92,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 18:00:29'),
(175, 1, 'delete', 'retirada', 92, 1, NULL, 'Excluiu o pedido #92 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-15 18:00:37\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 18:00:37'),
(176, 8, 'create', 'retirada', 93, 1, NULL, 'OK: create retirada#93', NULL, '{\"id\":93,\"produto\":\"AN Croissant\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AN Croissant\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:04:48'),
(177, 8, 'create', 'retirada', 94, 1, NULL, 'OK: create retirada#94', NULL, '{\"id\":94,\"produto\":\"AN Olho Grego - 16\",\"quantidade_solicitada\":10,\"tipo\":\"prata\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AN Olho Grego - 16\",\"tipo\":\"prata\",\"quantidade_solicitada\":10,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:05:30'),
(178, 8, 'create', 'retirada', 95, 1, NULL, 'OK: create retirada#95', NULL, '{\"id\":95,\"produto\":\"AN dedinho inicial - N\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AN dedinho inicial - N\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:05:57'),
(179, 8, 'create', 'retirada', 96, 1, NULL, 'OK: create retirada#96', NULL, '{\"id\":96,\"produto\":\"AN Dedinho inicial - N\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AN Dedinho inicial - N\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:06:24'),
(180, 8, 'create', 'retirada', 97, 1, NULL, 'OK: create retirada#97', NULL, '{\"id\":97,\"produto\":\"AN Dedinho Inicial - D\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AN Dedinho Inicial - D\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:07:10'),
(181, 8, 'create', 'retirada', 98, 1, NULL, 'OK: create retirada#98', NULL, '{\"id\":98,\"produto\":\"AN Dedinho Inicial - A\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AN Dedinho Inicial - A\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:07:43'),
(182, 8, 'create', 'retirada', 99, 1, NULL, 'OK: create retirada#99', NULL, '{\"id\":99,\"produto\":\"BC Allis\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"BC Allis\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:08:18'),
(183, 8, 'create', 'retirada', 100, 1, NULL, 'OK: create retirada#100', NULL, '{\"id\":100,\"produto\":\"HC Elos\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"HC Elos\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:09:00'),
(184, 8, 'create', 'retirada', 101, 1, NULL, 'OK: create retirada#101', NULL, '{\"id\":101,\"produto\":\"HC Estrela\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"HC Estrela\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:09:22'),
(185, 8, 'create', 'retirada', 102, 1, NULL, 'OK: create retirada#102', NULL, '{\"id\":102,\"produto\":\"PC Tubo Liso\",\"quantidade_solicitada\":25,\"tipo\":\"ouro\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"PC Tubo Liso\",\"tipo\":\"ouro\",\"quantidade_solicitada\":25,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:09:42'),
(186, 8, 'create', 'retirada', 103, 1, NULL, 'OK: create retirada#103', NULL, '{\"id\":103,\"produto\":\"PC Pressão Coração\",\"quantidade_solicitada\":25,\"tipo\":\"ouro\",\"solicitante\":\"Josi\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"PC Pressão Coração\",\"tipo\":\"ouro\",\"quantidade_solicitada\":25,\"solicitante\":\"Josi\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:09:59'),
(187, 8, 'create', 'retirada', 104, 1, NULL, 'OK: create retirada#104', NULL, '{\"id\":104,\"produto\":\"AG Tubular P\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Thalia e Isa\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AG Tubular P\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Thalia e Isa\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:12:00'),
(188, 8, 'create', 'retirada', 105, 1, NULL, 'OK: create retirada#105', NULL, '{\"id\":105,\"produto\":\"BR Fio Zoe\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Thalia e Isa\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"BR Fio Zoe\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Thalia e Isa\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:12:21'),
(189, 8, 'create', 'retirada', 106, 1, NULL, 'OK: create retirada#106', NULL, '{\"id\":106,\"produto\":\"BR Fio Zoe\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Thalia e Isa\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"BR Fio Zoe\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Thalia e Isa\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:12:45'),
(190, 8, 'create', 'retirada', 107, 1, NULL, 'OK: create retirada#107', NULL, '{\"id\":107,\"produto\":\"TR Esmeralda\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thalia e Isa\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"TR Esmeralda\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thalia e Isa\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:13:13'),
(191, 8, 'create', 'retirada', 108, 1, NULL, 'OK: create retirada#108', NULL, '{\"id\":108,\"produto\":\"TR Tina\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thalia e Isa\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"TR Tina\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thalia e Isa\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:13:26'),
(192, 8, 'create', 'retirada', 109, 1, NULL, 'OK: create retirada#109', NULL, '{\"id\":109,\"produto\":\"AG Maison\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Thalia e Isa\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AG Maison\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Thalia e Isa\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:13:39'),
(193, 8, 'create', 'retirada', 110, 1, NULL, 'OK: create retirada#110', NULL, '{\"id\":110,\"produto\":\"BR Gil\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Thalia e Isa\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"BR Gil\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Thalia e Isa\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:13:48'),
(194, 8, 'create', 'retirada', 111, 1, NULL, 'OK: create retirada#111', NULL, '{\"id\":111,\"produto\":\"AG Click Cravej. P\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Thalia e Isa\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AG Click Cravej. P\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Thalia e Isa\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:14:10'),
(195, 8, 'create', 'retirada', 112, 1, NULL, 'OK: create retirada#112', NULL, '{\"id\":112,\"produto\":\"AG Click Cravej. P\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Thalia e Isa\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AG Click Cravej. P\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Thalia e Isa\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:14:22'),
(196, 8, 'create', 'retirada', 113, 1, NULL, 'OK: create retirada#113', NULL, '{\"id\":113,\"produto\":\"AG Click Cravej. M\",\"quantidade_solicitada\":30,\"tipo\":\"prata\",\"solicitante\":\"Thalia e Isa\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AG Click Cravej. M\",\"tipo\":\"prata\",\"quantidade_solicitada\":30,\"solicitante\":\"Thalia e Isa\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:14:36'),
(197, 8, 'create', 'retirada', 114, 1, NULL, 'OK: create retirada#114', NULL, '{\"id\":114,\"produto\":\"AG Click Cravej. M\",\"quantidade_solicitada\":30,\"tipo\":\"ouro\",\"solicitante\":\"Thalia e Isa\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"AG Click Cravej. M\",\"tipo\":\"ouro\",\"quantidade_solicitada\":30,\"solicitante\":\"Thalia e Isa\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:14:46'),
(198, 8, 'create', 'retirada', 115, 1, NULL, 'OK: create retirada#115', NULL, '{\"id\":115,\"produto\":\"TR Ponto de Luz\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thalia e Isa\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"TR Ponto de Luz\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thalia e Isa\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 08:15:02'),
(199, 1, 'create', 'retirada', 116, 1, NULL, 'OK: create retirada#116', NULL, '{\"id\":116,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 08:49:18'),
(200, 1, 'create', 'retirada', 117, 1, NULL, 'OK: create retirada#117', NULL, '{\"id\":117,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 08:54:46'),
(201, 1, 'create', 'retirada', 118, 1, NULL, 'OK: create retirada#118', NULL, '{\"id\":118,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 08:58:45'),
(202, 1, 'edit', 'retirada', 74, 1, NULL, 'Editou pedido #74 (2026-01).', '{\"solicitante\":\"Thalia, Izadora\"}', '{\"solicitante\":\"Thalia e Isa\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 09:01:44');
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity`, `entity_id`, `success`, `event_code`, `message`, `before_json`, `after_json`, `payload_json`, `ip`, `user_agent`, `created_at`) VALUES
(203, 1, 'edit', 'retirada', 73, 1, NULL, 'Editou pedido #73 (2026-01).', '{\"solicitante\":\"Thalia, Izadora\"}', '{\"solicitante\":\"Thalia e Isa\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 09:01:55'),
(204, 1, 'edit', 'retirada', 72, 1, NULL, 'Editou pedido #72 (2026-01).', '{\"solicitante\":\"Thalia, Izadora\"}', '{\"solicitante\":\"Thalia e Isa\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 09:02:07'),
(205, 1, 'edit', 'retirada', 71, 1, NULL, 'Editou pedido #71 (2026-01).', '{\"solicitante\":\"Thalia, Izadora\"}', '{\"solicitante\":\"Thalia e Isa\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 09:02:21'),
(206, 1, 'edit', 'retirada', 70, 1, NULL, 'Editou pedido #70 (2026-01).', '{\"solicitante\":\"Thalia\"}', '{\"solicitante\":\"Thalia e Isa\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 09:02:35'),
(207, 1, 'create', 'retirada', 119, 1, NULL, 'OK: create retirada#119', NULL, '{\"id\":119,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 09:05:58'),
(208, 1, 'delete', 'retirada', 119, 1, NULL, 'Excluiu o pedido #119 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-16 09:06:57\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 09:06:57'),
(209, 1, 'delete', 'retirada', 118, 1, NULL, 'Excluiu o pedido #118 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-16 09:29:28\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 09:29:28'),
(210, 1, 'delete', 'retirada', 117, 1, NULL, 'Excluiu o pedido #117 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-16 09:29:32\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 09:29:32'),
(211, 1, 'delete', 'retirada', 116, 1, NULL, 'Excluiu o pedido #116 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-16 09:29:38\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 09:29:38'),
(212, 8, 'create', 'retirada', 120, 1, NULL, 'OK: create retirada#120', NULL, '{\"id\":120,\"produto\":\"CL Brilho\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Brilho\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:33:11'),
(213, 8, 'create', 'retirada', 121, 1, NULL, 'OK: create retirada#121', NULL, '{\"id\":121,\"produto\":\"CL Círculo Cravej.\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Círculo Cravej.\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:33:32'),
(214, 8, 'create', 'retirada', 122, 1, NULL, 'OK: create retirada#122', NULL, '{\"id\":122,\"produto\":\"CL Coração Cravej.\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Coração Cravej.\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:33:47'),
(215, 8, 'create', 'retirada', 123, 1, NULL, 'OK: create retirada#123', NULL, '{\"id\":123,\"produto\":\"CL Trançado\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Trançado\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:33:58'),
(216, 8, 'create', 'retirada', 124, 1, NULL, 'OK: create retirada#124', NULL, '{\"id\":124,\"produto\":\"Escap. Nª Senhora\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"Escap. Nª Senhora\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:34:24'),
(217, 8, 'create', 'retirada', 125, 1, NULL, 'OK: create retirada#125', NULL, '{\"id\":125,\"produto\":\"CL Pérola\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Pérola\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:34:38'),
(218, 8, 'create', 'retirada', 126, 1, NULL, 'OK: create retirada#126', NULL, '{\"id\":126,\"produto\":\"CL Rabo de Rato 60 cm\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Rabo de Rato 60 cm\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:35:05'),
(219, 8, 'create', 'retirada', 127, 1, NULL, 'OK: create retirada#127', NULL, '{\"id\":127,\"produto\":\"CL Trace 60cm\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Trace 60cm\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:35:27'),
(220, 8, 'create', 'retirada', 128, 1, NULL, 'OK: create retirada#128', NULL, '{\"id\":128,\"produto\":\"Terço Bolinha Nª Senhora Aparecida\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"Terço Bolinha Nª Senhora Aparecida\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:35:55'),
(221, 8, 'create', 'retirada', 129, 1, NULL, 'OK: create retirada#129', NULL, '{\"id\":129,\"produto\":\"Terço Cravej. Nª Senhora Aparecida\",\"quantidade_solicitada\":50,\"tipo\":\"prata\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"Terço Cravej. Nª Senhora Aparecida\",\"tipo\":\"prata\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:36:27'),
(222, 8, 'create', 'retirada', 130, 1, NULL, 'OK: create retirada#130', NULL, '{\"id\":130,\"produto\":\"CL Bolinha Veneziana\",\"quantidade_solicitada\":50,\"tipo\":\"ouro\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Bolinha Veneziana\",\"tipo\":\"ouro\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:36:44'),
(223, 8, 'create', 'retirada', 131, 1, NULL, 'OK: create retirada#131', NULL, '{\"id\":131,\"produto\":\"CL Allis 45cm\",\"quantidade_solicitada\":50,\"tipo\":\"ouro\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Allis 45cm\",\"tipo\":\"ouro\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:36:59'),
(224, 8, 'create', 'retirada', 132, 1, NULL, 'OK: create retirada#132', NULL, '{\"id\":132,\"produto\":\"Escap. Cravej. Nª Senhora e Cruz\",\"quantidade_solicitada\":50,\"tipo\":\"ouro\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"Escap. Cravej. Nª Senhora e Cruz\",\"tipo\":\"ouro\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:37:25'),
(225, 8, 'create', 'retirada', 133, 1, NULL, 'OK: create retirada#133', NULL, '{\"id\":133,\"produto\":\"Terço Cravej. Nª Senhora Aparecida\",\"quantidade_solicitada\":50,\"tipo\":\"ouro\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"Terço Cravej. Nª Senhora Aparecida\",\"tipo\":\"ouro\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:37:53'),
(226, 8, 'create', 'retirada', 134, 1, NULL, 'OK: create retirada#134', NULL, '{\"id\":134,\"produto\":\"CL Sarah\",\"quantidade_solicitada\":50,\"tipo\":\"ouro\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Sarah\",\"tipo\":\"ouro\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:38:08'),
(227, 8, 'create', 'retirada', 135, 1, NULL, 'OK: create retirada#135', NULL, '{\"id\":135,\"produto\":\"CL Inicial - K\",\"quantidade_solicitada\":50,\"tipo\":\"ouro\",\"solicitante\":\"Thay\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"CL Inicial - K\",\"tipo\":\"ouro\",\"quantidade_solicitada\":50,\"solicitante\":\"Thay\"}', '192.168.1.23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 09:38:46'),
(228, 1, 'create', 'retirada', 136, 1, NULL, 'OK: create retirada#136', NULL, '{\"id\":136,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 10:15:01'),
(229, 1, 'create', 'retirada', 137, 1, NULL, 'OK: create retirada#137', NULL, '{\"id\":137,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 10:15:12'),
(230, 1, 'delete', 'retirada', 137, 1, NULL, 'Excluiu o pedido #137 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-16 10:15:28\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 10:15:28'),
(231, 1, 'delete', 'retirada', 136, 1, NULL, 'Excluiu o pedido #136 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-16 10:15:34\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 10:15:34'),
(232, 1, 'create', 'retirada', 138, 1, NULL, 'OK: create retirada#138', NULL, '{\"id\":138,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 10:37:07'),
(233, 1, 'create', 'retirada', 139, 1, NULL, 'OK: create retirada#139', NULL, '{\"id\":139,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"ouro\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"ouro\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 10:37:15'),
(234, 1, 'delete', 'retirada', 138, 1, NULL, 'Excluiu o pedido #138 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-16 10:37:30\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 10:37:30'),
(235, 1, 'delete', 'retirada', 139, 1, NULL, 'Excluiu o pedido #139 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-16 10:37:36\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 10:37:36'),
(236, 1, 'create', 'retirada', 140, 1, NULL, 'OK: create retirada#140', NULL, '{\"id\":140,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 10:43:19'),
(237, 1, 'create', 'retirada', 141, 1, NULL, 'OK: create retirada#141', NULL, '{\"id\":141,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 10:43:33'),
(238, 1, 'delete', 'retirada', 141, 1, NULL, 'Excluiu o pedido #141 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-16 10:43:46\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 10:43:46'),
(239, 1, 'create', 'retirada', 142, 1, NULL, 'OK: create retirada#142', NULL, '{\"id\":142,\"produto\":\"teste\",\"quantidade_solicitada\":1,\"tipo\":\"prata\",\"solicitante\":\"cau\",\"status\":\"pedido\",\"competencia\":\"2026-01\"}', '{\"competencia\":\"2026-01\",\"produto\":\"teste\",\"tipo\":\"prata\",\"quantidade_solicitada\":1,\"solicitante\":\"cau\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 11:02:48'),
(240, 1, 'delete', 'retirada', 142, 1, NULL, 'Excluiu o pedido #142 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-16 11:03:08\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 11:03:08'),
(241, 1, 'delete', 'retirada', 140, 1, NULL, 'Excluiu o pedido #140 (2026-01).', '[]', '{\"deleted_at\":\"2026-01-16 11:03:13\"}', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 11:03:13'),
(242, 1, 'edit', 'retirada', 135, 1, NULL, 'Editou pedido #135 (2026-01).', '[]', '[]', '{\"competencia\":\"2026-01\"}', '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 11:37:10');

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
(21, '::1', 'caua', 1, '2026-01-15 08:03:24'),
(22, '::1', 'caua', 1, '2026-01-15 08:55:11'),
(23, '::1', 'caua', 1, '2026-01-15 09:05:22'),
(24, '192.168.1.84', 'caua', 1, '2026-01-15 09:06:39'),
(25, '192.168.1.84', 'caua', 1, '2026-01-15 09:21:54'),
(26, '192.168.1.84', 'jocyene', 0, '2026-01-15 09:22:12'),
(27, '192.168.1.84', 'caua', 1, '2026-01-15 09:22:24'),
(28, '192.168.1.84', 'jocyene', 1, '2026-01-15 09:23:25'),
(29, '192.168.1.49', 'teste2', 0, '2026-01-15 09:46:13'),
(30, '192.168.1.49', 'teste2', 0, '2026-01-15 09:46:33'),
(31, '192.168.1.84', 'teste2', 0, '2026-01-15 09:46:44'),
(32, '192.168.1.84', 'caua', 1, '2026-01-15 09:46:47'),
(33, '192.168.1.84', 'teste2', 0, '2026-01-15 09:47:14'),
(34, '192.168.1.84', 'caua', 1, '2026-01-15 09:47:17'),
(35, '192.168.1.49', 'teste2', 0, '2026-01-15 09:47:27'),
(36, '192.168.1.84', 'teste2', 0, '2026-01-15 09:47:34'),
(37, '192.168.1.84', 'caua', 1, '2026-01-15 09:47:38'),
(38, '192.168.1.49', 'teste2', 0, '2026-01-15 09:47:39'),
(39, '192.168.1.49', 'teste2', 0, '2026-01-15 09:47:56'),
(40, '192.168.1.49', 'teste2', 1, '2026-01-15 09:48:35'),
(41, '192.168.1.84', 'teste2', 1, '2026-01-15 09:48:39'),
(42, '192.168.1.84', 'caua', 1, '2026-01-15 09:48:50'),
(43, '192.168.1.23', 'jocyene', 1, '2026-01-15 14:16:50'),
(44, '192.168.1.95', 'jocyene', 1, '2026-01-15 14:24:55'),
(45, '192.168.1.96', 'caua', 1, '2026-01-15 14:39:53'),
(46, '192.168.1.84', 'caua', 1, '2026-01-15 16:59:05'),
(47, '192.168.1.84', 'caua', 1, '2026-01-16 08:03:23'),
(48, '192.168.1.23', 'jocyene', 1, '2026-01-16 08:04:25'),
(49, '192.168.1.95', 'jocyene', 1, '2026-01-16 08:18:47'),
(50, '192.168.1.23', 'jocyene', 1, '2026-01-16 09:32:52'),
(51, '192.168.1.95', 'jocyene', 1, '2026-01-16 09:44:55'),
(52, '192.168.1.84', 'caua', 1, '2026-01-16 10:24:18'),
(53, '192.168.1.84', 'caua', 1, '2026-01-16 10:34:12'),
(54, '192.168.1.84', 'teste2', 0, '2026-01-16 11:06:25'),
(55, '192.168.1.84', 'teste2', 1, '2026-01-16 11:06:33'),
(56, '192.168.1.84', 'caua', 1, '2026-01-16 11:13:31'),
(57, '192.168.1.84', 'teste2', 1, '2026-01-16 11:13:44'),
(58, '192.168.1.84', 'caua', 1, '2026-01-16 11:15:12'),
(59, '192.168.1.84', 'teste', 1, '2026-01-16 11:15:24'),
(60, '192.168.1.84', 'caua', 1, '2026-01-16 11:15:37'),
(61, '192.168.1.84', 'teste2', 1, '2026-01-16 11:16:06'),
(62, '192.168.1.84', 'caua', 1, '2026-01-16 11:19:21'),
(63, '192.168.1.84', 'teste2', 1, '2026-01-16 11:24:43'),
(64, '192.168.1.84', 'caua', 1, '2026-01-16 11:25:54'),
(65, '192.168.1.84', 'teste2', 1, '2026-01-16 11:32:40'),
(66, '192.168.1.84', 'caua', 1, '2026-01-16 11:33:13');

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

INSERT INTO `retiradas` (`id`, `competencia`, `status_mes`, `fechado_em`, `fechado_por`, `produto`, `tipo`, `quantidade_solicitada`, `itens_solicitados`, `solicitante`, `data_pedido`, `quantidade_retirada`, `responsavel_estoque`, `data_finalizacao`, `precisa_balanco`, `falta_estoque`, `status`, `created_at`, `deleted_at`, `deleted_by`, `sem_estoque`) VALUES
(43, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'CL Bolinha Veneziana', 'prata', 50, 1, 'Thay', '2026-01-15 14:17:36', 50, 'Jocyene', '2026-01-15 15:16:48', 0, 0, 'finalizado', '2026-01-15 17:17:36', NULL, NULL, 0),
(44, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'CL Gravata Allis', 'prata', 50, 1, 'Thay', '2026-01-15 14:17:51', 47, 'Jocyene', '2026-01-15 15:16:57', 0, 1, 'finalizado', '2026-01-15 17:17:51', NULL, NULL, 0),
(45, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'ESCAP. Red. Esp. Santo', 'prata', 50, 1, 'Thay', '2026-01-15 14:18:13', 77, 'Jocyene', '2026-01-15 15:17:29', 0, 0, 'finalizado', '2026-01-15 17:18:13', NULL, NULL, 0),
(46, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'CL Gravata Gotas', 'prata', 50, 1, 'Thay', '2026-01-15 14:18:24', 50, 'Jocyene', '2026-01-15 14:37:51', 0, 0, 'finalizado', '2026-01-15 17:18:24', NULL, NULL, 0),
(47, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'CL Roma', 'prata', 50, 1, 'Thay', '2026-01-15 14:18:35', 61, 'Jocyene', '2026-01-15 15:17:18', 0, 0, 'finalizado', '2026-01-15 17:18:35', NULL, NULL, 0),
(48, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'CL Ponto de Luz', 'prata', 50, 1, 'Thay', '2026-01-15 14:18:44', 81, 'Jocyene', '2026-01-15 15:15:00', 0, 0, 'finalizado', '2026-01-15 17:18:44', NULL, NULL, 0),
(49, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'CL Ponto de Luz Oval', 'prata', 50, 1, 'Thay', '2026-01-15 14:19:00', 30, 'Jocyene', '2026-01-15 15:14:34', 1, 1, 'finalizado', '2026-01-15 17:19:00', NULL, NULL, 0),
(50, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'CL Medalha Inicial - Prata - G', 'prata', 50, 1, 'Thay', '2026-01-15 14:19:19', 45, 'Jocyene', '2026-01-15 14:32:06', 1, 1, 'finalizado', '2026-01-15 17:19:19', NULL, NULL, 0),
(51, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'CL Medalha Inicial - Prata - E', 'prata', 50, 1, 'Thay', '2026-01-15 14:19:38', 30, 'Jocyene', '2026-01-15 14:31:12', 1, 1, 'finalizado', '2026-01-15 17:19:38', NULL, NULL, 0),
(52, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'CL Amalfi Duplo', 'ouro', 50, 1, 'Thay', '2026-01-15 14:19:49', 0, 'Jocyene', '2026-01-15 14:28:18', 0, 1, 'finalizado', '2026-01-15 17:19:49', NULL, NULL, 1),
(53, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'CL Aura', 'ouro', 50, 1, 'Thay', '2026-01-15 14:19:59', 73, 'Jocyene', '2026-01-15 14:27:38', 0, 0, 'finalizado', '2026-01-15 17:19:59', NULL, NULL, 0),
(54, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'ESCAP . Nª Senhora', 'ouro', 50, 1, 'Thay', '2026-01-15 14:20:14', 54, 'Jocyene', '2026-01-15 14:27:54', 0, 0, 'finalizado', '2026-01-15 17:20:14', NULL, NULL, 0),
(55, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'CL Gravata Gotas', 'ouro', 50, 1, 'Thay', '2026-01-15 14:20:26', 50, 'Jocyene', '2026-01-15 14:26:16', 0, 0, 'finalizado', '2026-01-15 17:20:26', NULL, NULL, 0),
(56, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'CL Mini Cristo', 'ouro', 50, 1, 'Thay', '2026-01-15 14:20:39', 50, 'Jocyene', '2026-01-15 14:25:23', 0, 0, 'finalizado', '2026-01-15 17:20:39', NULL, NULL, 0),
(57, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'AN Bojudo - Reg. - P', 'prata', 30, 1, 'Josi', '2026-01-15 14:29:09', 30, 'Jocyene', '2026-01-15 15:15:21', 1, 0, 'finalizado', '2026-01-15 17:29:09', NULL, NULL, 0),
(58, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'HC Elos', 'prata', 25, 1, 'Josi', '2026-01-15 14:29:48', NULL, NULL, NULL, 0, 0, 'pedido', '2026-01-15 17:29:48', '2026-01-15 14:30:44', NULL, 0),
(59, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'TZ Riviera', 'prata', 30, 1, 'Josi', '2026-01-15 14:30:06', NULL, NULL, NULL, 0, 0, 'pedido', '2026-01-15 17:30:06', '2026-01-15 14:30:33', NULL, 0),
(60, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'AN Bojudo - Reg. - G', 'prata', 30, 1, 'Josi', '2026-01-15 14:31:05', 0, 'Jocyene', '2026-01-15 15:14:00', 0, 1, 'finalizado', '2026-01-15 17:31:05', NULL, NULL, 1),
(61, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'AN Coração Trabalhado - Reg. - G', 'prata', 30, 1, 'Josi', '2026-01-15 14:31:39', 33, 'Jocyene', '2026-01-15 15:13:49', 0, 0, 'finalizado', '2026-01-15 17:31:39', NULL, NULL, 0),
(62, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'AN Cravejado - Reg. - P', 'ouro', 30, 1, 'Josi', '2026-01-15 14:32:13', 60, 'Jocyene', '2026-01-15 14:57:42', 1, 0, 'finalizado', '2026-01-15 17:32:13', NULL, NULL, 0),
(63, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'AN Duplo - Reg. - G', 'ouro', 30, 1, 'Josi', '2026-01-15 14:32:36', 29, 'Jocyene', '2026-01-15 14:56:14', 1, 1, 'finalizado', '2026-01-15 17:32:36', NULL, NULL, 0),
(64, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'AN Cloe - Reg. - G', 'ouro', 30, 1, 'Josi', '2026-01-15 14:33:15', 39, 'Jocyene', '2026-01-15 14:54:20', 0, 0, 'finalizado', '2026-01-15 17:33:15', NULL, NULL, 0),
(65, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'AN Mini Zircônias - Reg. - P', 'ouro', 30, 1, 'Josi', '2026-01-15 14:33:46', 0, 'Jocyene', '2026-01-15 14:52:09', 0, 1, 'finalizado', '2026-01-15 17:33:46', NULL, NULL, 1),
(66, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'AN Três Zircônias - Reg. - P', 'ouro', 30, 1, 'Josi', '2026-01-15 14:34:17', 30, 'Jocyene', '2026-01-15 14:41:42', 1, 0, 'finalizado', '2026-01-15 17:34:17', NULL, NULL, 0),
(67, '2026-01', 'FECHADO', '2026-01-15 14:35:14', 'Jocyene', 'PC Furo Torcido (nariz)', 'ouro', 30, 1, 'Josi', '2026-01-15 14:35:02', 47, 'Jocyene', '2026-01-15 14:55:13', 1, 0, 'finalizado', '2026-01-15 17:35:02', NULL, NULL, 0),
(68, '2026-01', 'ABERTO', NULL, NULL, 'PC Furo Zara', 'ouro', 30, 1, 'Josi', '2026-01-15 14:37:12', 65, 'Jocyene', '2026-01-15 14:40:46', 1, 0, 'finalizado', '2026-01-15 17:37:12', NULL, NULL, 0),
(69, '2026-01', 'ABERTO', NULL, NULL, 'PC Furo Zara', 'prata', 30, 1, 'Josi', '2026-01-15 14:37:34', 55, 'Jocyene', '2026-01-15 14:40:34', 0, 0, 'finalizado', '2026-01-15 17:37:34', NULL, NULL, 0),
(70, '2026-01', 'ABERTO', NULL, NULL, 'TR Shine', 'prata', 50, 1, 'Thalia e Isa', '2026-01-15 15:24:32', 40, 'Jocyene', '2026-01-15 15:28:26', 0, 1, 'finalizado', '2026-01-15 18:24:32', NULL, NULL, 0),
(71, '2026-01', 'ABERTO', NULL, NULL, 'AG Lisa M', 'prata', 30, 1, 'Thalia e Isa', '2026-01-15 15:26:50', 49, 'Jocyene', '2026-01-15 15:28:17', 0, 0, 'finalizado', '2026-01-15 18:26:50', NULL, NULL, 0),
(72, '2026-01', 'ABERTO', NULL, NULL, 'AG Lisa M', 'ouro', 30, 1, 'Thalia e Isa', '2026-01-15 15:27:02', 37, 'Jocyene', '2026-01-15 15:28:06', 0, 0, 'finalizado', '2026-01-15 18:27:02', NULL, NULL, 0),
(73, '2026-01', 'ABERTO', NULL, NULL, 'AG Lisa G', 'prata', 30, 1, 'Thalia e Isa', '2026-01-15 15:27:20', 31, 'Jocyene', '2026-01-15 15:27:52', 0, 0, 'finalizado', '2026-01-15 18:27:20', NULL, NULL, 0),
(74, '2026-01', 'ABERTO', NULL, NULL, 'AG Lisa G', 'ouro', 30, 1, 'Thalia e Isa', '2026-01-15 15:27:28', 40, 'Jocyene', '2026-01-15 15:27:42', 0, 0, 'finalizado', '2026-01-15 18:27:28', NULL, NULL, 0),
(93, '2026-01', 'ABERTO', NULL, NULL, 'AN Croissant', 'prata', 30, 1, 'Josi', '2026-01-16 08:04:48', 30, 'Jocyene', '2026-01-16 08:28:52', 0, 0, 'finalizado', '2026-01-16 11:04:48', NULL, NULL, 0),
(94, '2026-01', 'ABERTO', NULL, NULL, 'AN Olho Grego - 16', 'prata', 10, 1, 'Josi', '2026-01-16 08:05:30', 10, 'Jocyene', '2026-01-16 08:29:00', 0, 0, 'finalizado', '2026-01-16 11:05:30', NULL, NULL, 0),
(95, '2026-01', 'ABERTO', NULL, NULL, 'AN dedinho inicial - N', 'prata', 30, 1, 'Josi', '2026-01-16 08:05:57', 0, 'Jocyene', '2026-01-16 08:34:03', 1, 1, 'finalizado', '2026-01-16 11:05:57', NULL, NULL, 1),
(96, '2026-01', 'ABERTO', NULL, NULL, 'AN Dedinho inicial - N', 'ouro', 30, 1, 'Josi', '2026-01-16 08:06:24', 27, 'Jocyene', '2026-01-16 08:33:55', 1, 1, 'finalizado', '2026-01-16 11:06:24', NULL, NULL, 0),
(97, '2026-01', 'ABERTO', NULL, NULL, 'AN Dedinho Inicial - D', 'prata', 30, 1, 'Josi', '2026-01-16 08:07:10', 39, 'Jocyene', '2026-01-16 08:33:41', 1, 0, 'finalizado', '2026-01-16 11:07:10', NULL, NULL, 0),
(98, '2026-01', 'ABERTO', NULL, NULL, 'AN Dedinho Inicial - A', 'ouro', 30, 1, 'Josi', '2026-01-16 08:07:43', 30, 'Jocyene', '2026-01-16 08:33:29', 0, 0, 'finalizado', '2026-01-16 11:07:43', NULL, NULL, 0),
(99, '2026-01', 'ABERTO', NULL, NULL, 'BC Allis', 'prata', 30, 1, 'Josi', '2026-01-16 08:08:18', 30, 'Jocyene', '2026-01-16 08:32:51', 1, 0, 'finalizado', '2026-01-16 11:08:18', NULL, NULL, 0),
(100, '2026-01', 'ABERTO', NULL, NULL, 'HC Elos', 'ouro', 30, 1, 'Josi', '2026-01-16 08:09:00', 47, 'Jocyene', '2026-01-16 08:32:40', 0, 0, 'finalizado', '2026-01-16 11:09:00', NULL, NULL, 0),
(101, '2026-01', 'ABERTO', NULL, NULL, 'HC Estrela', 'ouro', 30, 1, 'Josi', '2026-01-16 08:09:22', 0, 'Jocyene', '2026-01-16 08:31:43', 1, 1, 'finalizado', '2026-01-16 11:09:22', NULL, NULL, 1),
(102, '2026-01', 'ABERTO', NULL, NULL, 'PC Tubo Liso', 'ouro', 25, 1, 'Josi', '2026-01-16 08:09:42', 36, 'Jocyene', '2026-01-16 08:31:35', 1, 0, 'finalizado', '2026-01-16 11:09:42', NULL, NULL, 0),
(103, '2026-01', 'ABERTO', NULL, NULL, 'PC Pressão Coração', 'ouro', 25, 1, 'Josi', '2026-01-16 08:09:59', 0, 'Jocyene', '2026-01-16 08:31:17', 1, 1, 'finalizado', '2026-01-16 11:09:59', NULL, NULL, 1),
(104, '2026-01', 'ABERTO', NULL, NULL, 'AG Tubular P', 'prata', 30, 1, 'Thalia e Isa', '2026-01-16 08:12:00', 26, 'Jocyene', '2026-01-16 08:27:05', 0, 1, 'finalizado', '2026-01-16 11:12:00', NULL, NULL, 0),
(105, '2026-01', 'ABERTO', NULL, NULL, 'BR Fio Zoe', 'prata', 30, 1, 'Thalia e Isa', '2026-01-16 08:12:21', 0, 'Jocyene', '2026-01-16 08:26:33', 1, 1, 'finalizado', '2026-01-16 11:12:21', NULL, NULL, 1),
(106, '2026-01', 'ABERTO', NULL, NULL, 'BR Fio Zoe', 'ouro', 30, 1, 'Thalia e Isa', '2026-01-16 08:12:45', 0, 'Jocyene', '2026-01-16 08:25:59', 1, 1, 'finalizado', '2026-01-16 11:12:45', NULL, NULL, 1),
(107, '2026-01', 'ABERTO', NULL, NULL, 'TR Esmeralda', 'prata', 50, 1, 'Thalia e Isa', '2026-01-16 08:13:13', 50, 'Jocyene', '2026-01-16 08:27:57', 0, 0, 'finalizado', '2026-01-16 11:13:13', NULL, NULL, 0),
(108, '2026-01', 'ABERTO', NULL, NULL, 'TR Tina', 'prata', 50, 1, 'Thalia e Isa', '2026-01-16 08:13:26', 50, 'Jocyene', '2026-01-16 08:27:45', 0, 0, 'finalizado', '2026-01-16 11:13:26', NULL, NULL, 0),
(109, '2026-01', 'ABERTO', NULL, NULL, 'AG Maison', 'ouro', 30, 1, 'Thalia e Isa', '2026-01-16 08:13:39', 30, 'Jocyene', '2026-01-16 08:27:33', 0, 0, 'finalizado', '2026-01-16 11:13:39', NULL, NULL, 0),
(110, '2026-01', 'ABERTO', NULL, NULL, 'BR Gil', 'ouro', 30, 1, 'Thalia e Isa', '2026-01-16 08:13:48', 30, 'Jocyene', '2026-01-16 08:22:01', 0, 0, 'finalizado', '2026-01-16 11:13:48', NULL, NULL, 0),
(111, '2026-01', 'ABERTO', NULL, NULL, 'AG Click Cravej. P', 'prata', 30, 1, 'Thalia e Isa', '2026-01-16 08:14:10', 0, 'Jocyene', '2026-01-16 08:21:29', 1, 1, 'finalizado', '2026-01-16 11:14:10', NULL, NULL, 1),
(112, '2026-01', 'ABERTO', NULL, NULL, 'AG Click Cravej. P', 'ouro', 30, 1, 'Thalia e Isa', '2026-01-16 08:14:22', 0, 'Jocyene', '2026-01-16 08:21:05', 1, 1, 'finalizado', '2026-01-16 11:14:22', NULL, NULL, 1),
(113, '2026-01', 'ABERTO', NULL, NULL, 'AG Click Cravej. M', 'prata', 30, 1, 'Thalia e Isa', '2026-01-16 08:14:36', 30, 'Jocyene', '2026-01-16 08:20:58', 0, 0, 'finalizado', '2026-01-16 11:14:36', NULL, NULL, 0),
(114, '2026-01', 'ABERTO', NULL, NULL, 'AG Click Cravej. M', 'ouro', 30, 1, 'Thalia e Isa', '2026-01-16 08:14:46', 1, 'Jocyene', '2026-01-16 08:20:38', 1, 1, 'finalizado', '2026-01-16 11:14:46', NULL, NULL, 0),
(115, '2026-01', 'ABERTO', NULL, NULL, 'TR Ponto de Luz', 'prata', 50, 1, 'Thalia e Isa', '2026-01-16 08:15:02', 50, 'Jocyene', '2026-01-16 08:21:47', 1, 0, 'finalizado', '2026-01-16 11:15:02', NULL, NULL, 0),
(120, '2026-01', 'ABERTO', NULL, NULL, 'CL Brilho', 'prata', 50, 1, 'Thay', '2026-01-16 09:33:11', 29, 'Jocyene', '2026-01-16 09:45:23', 0, 1, 'finalizado', '2026-01-16 12:33:11', NULL, NULL, 0),
(121, '2026-01', 'ABERTO', NULL, NULL, 'CL Círculo Cravej.', 'prata', 50, 1, 'Thay', '2026-01-16 09:33:32', 50, 'Jocyene', '2026-01-16 09:45:34', 0, 0, 'finalizado', '2026-01-16 12:33:32', NULL, NULL, 0),
(122, '2026-01', 'ABERTO', NULL, NULL, 'CL Coração Cravej.', 'prata', 50, 1, 'Thay', '2026-01-16 09:33:47', 0, 'Jocyene', '2026-01-16 09:45:44', 1, 1, 'finalizado', '2026-01-16 12:33:47', NULL, NULL, 1),
(123, '2026-01', 'ABERTO', NULL, NULL, 'CL Trançado', 'prata', 50, 1, 'Thay', '2026-01-16 09:33:58', 54, 'Jocyene', '2026-01-16 09:45:54', 0, 0, 'finalizado', '2026-01-16 12:33:58', NULL, NULL, 0),
(124, '2026-01', 'ABERTO', NULL, NULL, 'Escap. Nª Senhora', 'prata', 50, 1, 'Thay', '2026-01-16 09:34:24', 50, 'Jocyene', '2026-01-16 09:46:02', 0, 0, 'finalizado', '2026-01-16 12:34:24', NULL, NULL, 0),
(125, '2026-01', 'ABERTO', NULL, NULL, 'CL Pérola', 'prata', 50, 1, 'Thay', '2026-01-16 09:34:38', 70, 'Jocyene', '2026-01-16 09:46:11', 1, 0, 'finalizado', '2026-01-16 12:34:38', NULL, NULL, 0),
(126, '2026-01', 'ABERTO', NULL, NULL, 'CL Rabo de Rato 60 cm', 'prata', 50, 1, 'Thay', '2026-01-16 09:35:05', 40, 'Jocyene', '2026-01-16 09:46:20', 1, 1, 'finalizado', '2026-01-16 12:35:05', NULL, NULL, 0),
(127, '2026-01', 'ABERTO', NULL, NULL, 'CL Trace 60cm', 'prata', 50, 1, 'Thay', '2026-01-16 09:35:27', 19, 'Jocyene', '2026-01-16 09:46:39', 1, 1, 'finalizado', '2026-01-16 12:35:27', NULL, NULL, 0),
(128, '2026-01', 'ABERTO', NULL, NULL, 'Terço Bolinha Nª Senhora Aparecida', 'prata', 50, 1, 'Thay', '2026-01-16 09:35:55', 0, 'Jocyene', '2026-01-16 09:46:47', 1, 1, 'finalizado', '2026-01-16 12:35:55', NULL, NULL, 1),
(129, '2026-01', 'ABERTO', NULL, NULL, 'Terço Cravej. Nª Senhora Aparecida', 'prata', 50, 1, 'Thay', '2026-01-16 09:36:27', 0, 'Jocyene', '2026-01-16 09:47:09', 1, 1, 'finalizado', '2026-01-16 12:36:27', NULL, NULL, 1),
(130, '2026-01', 'ABERTO', NULL, NULL, 'CL Bolinha Veneziana', 'ouro', 50, 1, 'Thay', '2026-01-16 09:36:44', 48, 'Jocyene', '2026-01-16 09:47:17', 0, 1, 'finalizado', '2026-01-16 12:36:44', NULL, NULL, 0),
(131, '2026-01', 'ABERTO', NULL, NULL, 'CL Allis 45cm', 'ouro', 50, 1, 'Thay', '2026-01-16 09:36:59', 55, 'Jocyene', '2026-01-16 09:47:24', 0, 0, 'finalizado', '2026-01-16 12:36:59', NULL, NULL, 0),
(132, '2026-01', 'ABERTO', NULL, NULL, 'Escap. Cravej. Nª Senhora e Cruz', 'ouro', 50, 1, 'Thay', '2026-01-16 09:37:25', 50, 'Jocyene', '2026-01-16 09:47:38', 1, 0, 'finalizado', '2026-01-16 12:37:25', NULL, NULL, 0),
(133, '2026-01', 'ABERTO', NULL, NULL, 'Terço Cravej. Nª Senhora Aparecida', 'ouro', 50, 1, 'Thay', '2026-01-16 09:37:53', 0, 'Jocyene', '2026-01-16 09:47:47', 1, 1, 'finalizado', '2026-01-16 12:37:53', NULL, NULL, 1),
(134, '2026-01', 'ABERTO', NULL, NULL, 'CL Sarah', 'ouro', 50, 1, 'Thay', '2026-01-16 09:38:08', 0, 'Jocyene', '2026-01-16 09:48:07', 1, 1, 'finalizado', '2026-01-16 12:38:08', NULL, NULL, 1),
(135, '2026-01', 'ABERTO', NULL, NULL, 'CL Inicial - K', 'ouro', 50, 1, 'Thay', '2026-01-16 09:38:46', 50, 'Jocyene', '2026-01-16 09:48:18', 1, 0, 'finalizado', '2026-01-16 12:38:46', NULL, NULL, 0);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=243;

--
-- AUTO_INCREMENT de tabela `fechamentos`
--
ALTER TABLE `fechamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de tabela `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT de tabela `retiradas`
--
ALTER TABLE `retiradas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
