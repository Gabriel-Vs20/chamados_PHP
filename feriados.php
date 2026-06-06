<?php
// feriados.php - Integracao com BrasilAPI (sistema externo distribuido)
// Calcula previsao de SLA pulando finais de semana e feriados nacionais.

function buscarFeriados(int $ano): array {
    $cacheFile = sys_get_temp_dir() . "/feriados_{$ano}.json";
    if (is_file($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) return $cached;
    }

    $ch = curl_init("https://brasilapi.com.br/api/feriados/v1/{$ano}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'ChamadosCOP/1.0',
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$resp) return [];

    $data = json_decode($resp, true);
    if (!is_array($data)) return [];

    $datas = array_map(fn($f) => $f['date'], $data);
    @file_put_contents($cacheFile, json_encode($datas));
    return $datas;
}

function calcularPrevisaoSLA(string $prioridade, ?DateTime $inicio = null): string {
    $diasUteis = match ($prioridade) {
        'Critica' => 1,
        'Alta'    => 2,
        'Media'   => 5,
        'Baixa'   => 10,
        default   => 5,
    };

    $data = $inicio ?? new DateTime();
    $feriados = buscarFeriados((int)$data->format('Y'));
    $adicionados = 0;

    while ($adicionados < $diasUteis) {
        $data->modify('+1 day');
        $diaSemana = (int)$data->format('N'); // 6=sab, 7=dom
        $diaStr = $data->format('Y-m-d');
        if ($diaSemana < 6 && !in_array($diaStr, $feriados, true)) {
            $adicionados++;
        }
    }
    return $data->format('Y-m-d');
}
