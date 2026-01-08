<?php
date_default_timezone_set('America/Sao_Paulo');

function competencia_atual(): string
{
    return date('Y-m');
}

function competencia_from_datetime(string $datetime): string
{
    // $datetime tipo: "2026-01-08 11:22:42"
    $ts = strtotime($datetime);
    return date('Y-m', $ts);
}

function competencia_valida(string $comp): bool
{
    return (bool) preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $comp);
}
