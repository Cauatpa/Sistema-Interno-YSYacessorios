<?php
require_once __DIR__ . '/../helpers/competencia.php';

function mes_esta_fechado(PDO $pdo, string $competencia): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM fechamentos WHERE competencia = ? LIMIT 1");
    $stmt->execute([$competencia]);
    return (bool) $stmt->fetchColumn();
}

function fechar_mes(PDO $pdo, string $competencia, string $usuario, ?string $observacao = null): array
{
    if (!competencia_valida($competencia)) {
        return ['ok' => false, 'error' => 'Competência inválida. Use YYYY-MM.'];
    }

    if (mes_esta_fechado($pdo, $competencia)) {
        return ['ok' => false, 'error' => "Esse mês ($competencia) já está fechado."];
    }

    // Conta quantos registros do mês existem
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM retiradas WHERE competencia = ?");
    $stmtCount->execute([$competencia]);
    $total = (int) $stmtCount->fetchColumn();

    $pdo->beginTransaction();
    try {
        // Fecha registros ABERTOS do mês
        $stmtUpdate = $pdo->prepare("
      UPDATE retiradas
      SET status_mes='FECHADO', fechado_em=NOW(), fechado_por=?
      WHERE competencia = ? AND status_mes='ABERTO'
    ");
        $stmtUpdate->execute([$usuario, $competencia]);

        // Loga fechamento (mesmo que não tenha registros)
        $stmtLog = $pdo->prepare("
      INSERT INTO fechamentos (competencia, fechado_por, fechado_em, total_registros, observacao)
      VALUES (?, ?, NOW(), ?, ?)
    ");
        $stmtLog->execute([$competencia, $usuario, $total, $observacao]);

        $pdo->commit();
        return ['ok' => true, 'competencia' => $competencia, 'total_registros' => $total];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['ok' => false, 'error' => 'Erro ao fechar mês: ' . $e->getMessage()];
    }
}
