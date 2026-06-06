<?php
// teams.php - notificacao Microsoft Teams via Workflows webhook (Adaptive Card)
// Formato moderno apos descontinuacao dos Office 365 Connectors (mai/2026).

require_once 'db.php';
require_once 'helpers.php';

function notificarMudancaStatus(
    int $id,
    string $titulo,
    string $statusAntigo,
    string $statusNovo,
    string $prioridade,
    ?string $responsavel,
    string $solicitante
): bool {
    if (TEAMS_WEBHOOK_URL === '') {
        return false; // webhook nao configurado, ignora silenciosamente
    }

    $cor = match ($statusNovo) {
        'Resolvido', 'Fechado' => 'good',
        'Aguardando'           => 'warning',
        default                => 'accent',
    };
    if ($prioridade === 'Critica' && !in_array($statusNovo, ['Resolvido','Fechado'], true)) {
        $cor = 'attention';
    }

    $idFmt    = '#' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
    $linkVer  = rtrim(APP_URL, '/') . '/detalhe.php?id=' . $id;
    $rotuloDe = rotuloStatus($statusAntigo);
    $rotuloPara = rotuloStatus($statusNovo);

    $card = [
        'type' => 'message',
        'attachments' => [[
            'contentType' => 'application/vnd.microsoft.card.adaptive',
            'contentUrl'  => null,
            'content' => [
                '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                'type'    => 'AdaptiveCard',
                'version' => '1.4',
                'msteams' => ['width' => 'Full'],
                'body' => [
                    [
                        'type' => 'Container',
                        'style' => $cor,
                        'bleed' => true,
                        'items' => [
                            [
                                'type' => 'TextBlock',
                                'text' => "Chamado {$idFmt} · status alterado",
                                'weight' => 'Bolder',
                                'size'   => 'Medium',
                                'color'  => 'Light',
                                'wrap'   => true,
                            ],
                        ],
                    ],
                    [
                        'type' => 'TextBlock',
                        'text' => $titulo,
                        'weight' => 'Bolder',
                        'size'   => 'Large',
                        'wrap'   => true,
                        'spacing'=> 'Medium',
                    ],
                    [
                        'type' => 'ColumnSet',
                        'spacing' => 'Medium',
                        'columns' => [
                            [
                                'type'  => 'Column',
                                'width' => 'stretch',
                                'items' => [
                                    ['type'=>'TextBlock','text'=>'De','weight'=>'Bolder','size'=>'Small','color'=>'Accent','spacing'=>'None'],
                                    ['type'=>'TextBlock','text'=>$rotuloDe,'weight'=>'Bolder','size'=>'Medium','spacing'=>'None','wrap'=>true],
                                ],
                            ],
                            [
                                'type'  => 'Column',
                                'width' => 'auto',
                                'verticalContentAlignment' => 'Center',
                                'items' => [
                                    ['type'=>'TextBlock','text'=>'→','size'=>'ExtraLarge','color'=>'Accent','weight'=>'Bolder'],
                                ],
                            ],
                            [
                                'type'  => 'Column',
                                'width' => 'stretch',
                                'items' => [
                                    ['type'=>'TextBlock','text'=>'Para','weight'=>'Bolder','size'=>'Small','color'=>'Accent','spacing'=>'None'],
                                    ['type'=>'TextBlock','text'=>$rotuloPara,'weight'=>'Bolder','size'=>'Medium','spacing'=>'None','wrap'=>true],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'FactSet',
                        'spacing' => 'Medium',
                        'facts' => [
                            ['title' => 'Solicitante',  'value' => $solicitante],
                            ['title' => 'Responsável',  'value' => $responsavel ?: '—'],
                            ['title' => 'Prioridade',   'value' => $prioridade],
                            ['title' => 'Atualizado',   'value' => (new DateTime())->format('d/m/Y H:i')],
                        ],
                    ],
                ],
                'actions' => [
                    [
                        'type'  => 'Action.OpenUrl',
                        'title' => 'Abrir chamado',
                        'url'   => $linkVer,
                        'style' => 'positive',
                    ],
                ],
            ],
        ]],
    ];

    $ch = curl_init(TEAMS_WEBHOOK_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($card, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'ChamadosCOP/1.0',
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($code >= 200 && $code < 300) {
        return true;
    }
    error_log("[teams] falha ao notificar #{$id}: HTTP {$code} | {$err} | resp: {$resp}");
    return false;
}