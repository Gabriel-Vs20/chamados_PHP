<?php
// helpers.php - utilitarios de UI (icones SVG, tempo relativo, classe de SLA)

function icone(string $nome, int $tamanho = 18): string {
    $svgs = [
        'plus' => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'inbox' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'tag' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
        'chevron-right' => '<polyline points="9 18 15 12 9 6"/>',
        'check' => '<polyline points="20 6 9 17 4 12"/>',
        'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'alert' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
        'send' => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
        'trash' => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'edit' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'layers' => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
        'activity' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
        'zap' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        'pause' => '<rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>',
        'archive' => '<polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/>',
        'arrow-left' => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
        'message' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'help' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    ];
    $body = $svgs[$nome] ?? '';
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $tamanho . '" height="' . $tamanho .
           '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' .
           'stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
}

function tempoRelativo(string $dataIso): string {
    $diff = time() - strtotime($dataIso);
    if ($diff < 60)       return 'agora';
    if ($diff < 3600)     return 'há ' . (int)($diff / 60) . ' min';
    if ($diff < 86400)    return 'há ' . (int)($diff / 3600) . ' h';
    if ($diff < 2592000)  return 'há ' . (int)($diff / 86400) . ' d';
    return (new DateTime($dataIso))->format('d/m/Y');
}

function classeSLA(?string $previsao, string $status): array {
    if (in_array($status, ['Resolvido','Fechado'], true) || !$previsao) {
        return ['', '-'];
    }
    $hoje  = new DateTime('today');
    $prazo = new DateTime($previsao);
    $dias  = (int)$hoje->diff($prazo)->format('%r%a');
    if ($dias < 0)  return ['sla-vencido',  'Vencido'];
    if ($dias <= 1) return ['sla-proximo',  'SLA ' . $dias . 'd'];
    return ['sla-ok', 'SLA ' . $dias . 'd'];
}

function iniciais(string $nome): string {
    $partes = preg_split('/\s+/', trim($nome));
    if (!$partes) return '?';
    $primeira = mb_substr($partes[0], 0, 1);
    $ultima   = count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '';
    return mb_strtoupper($primeira . $ultima);
}

function rotuloStatus(string $st): string {
    return match ($st) {
        'EmAndamento' => 'Em andamento',
        default       => $st,
    };
}
