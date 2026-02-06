<?php

declare(strict_types=1);

final class TinyClient
{
    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    private function post(string $url, array $data): array
    {
        $postFields = http_build_query(array_merge([
            'token'   => $this->token,
            'formato' => 'JSON',
        ], $data));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_TIMEOUT        => 20,
        ]);

        $resp = curl_exec($ch);
        if ($resp === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Tiny CURL: {$err}");
        }

        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($resp, true);
        if (!is_array($json)) {
            throw new RuntimeException("Tiny resposta inválida (HTTP {$http}).");
        }

        return $json;
    }

    public function pesquisarProdutos(string $pesquisa, int $pagina = 1): array
    {
        return $this->post(
            'https://api.tiny.com.br/api2/produtos.pesquisa.php',
            ['pesquisa' => $pesquisa, 'pagina' => $pagina]
        );
    }

    public function obterEstoquePorId(string $tinyId): array
    {
        return $this->post(
            'https://api.tiny.com.br/api2/produto.obter.estoque.php',
            ['id' => $tinyId]
        );
    }

    public static function pickProdutoPorVariacao(array $produtos, string $variacao): ?array
    {
        $v = mb_strtolower(trim($variacao), 'UTF-8');

        foreach ($produtos as $row) {
            $p = $row['produto'] ?? null;
            if (!is_array($p)) continue;

            $nome = mb_strtolower((string)($p['nome'] ?? ''), 'UTF-8');

            if ($v === 'prata' && str_contains($nome, 'prata')) return $p;
            if ($v === 'ouro'  && (str_contains($nome, 'dourado') || str_contains($nome, 'ouro'))) return $p;
        }

        return $produtos[0]['produto'] ?? null;
    }

    public static function saldoUsavel(array $produto): int
    {
        $deps = $produto['depositos'] ?? [];
        $sum = 0;

        if (is_array($deps)) {
            foreach ($deps as $d) {
                $dep = $d['deposito'] ?? [];
                if (!is_array($dep)) continue;

                if (($dep['desconsiderar'] ?? 'S') === 'N') {
                    $sum += (int)($dep['saldo'] ?? 0);
                }
            }
        }

        if ($sum <= 0 && isset($produto['saldo'])) {
            $sum = (int)$produto['saldo'];
        }

        return $sum;
    }
}
