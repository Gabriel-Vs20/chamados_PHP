<?php
require_once 'db.php';
require_once 'helpers.php';

$filtroStatus = $_GET['status'] ?? 'todos';
$statusValidos = ['Aberto','EmAndamento','Aguardando','Resolvido','Fechado'];

$sql = "SELECT id, titulo, categoria, prioridade, status, solicitante, data_abertura, previsao_sla
        FROM chamados";
$params = [];
if (in_array($filtroStatus, $statusValidos, true)) {
    $sql .= " WHERE status = :st";
    $params[':st'] = $filtroStatus;
}
$sql .= " ORDER BY
            FIELD(prioridade,'Critica','Alta','Media','Baixa'),
            FIELD(status,'Aberto','EmAndamento','Aguardando','Resolvido','Fechado'),
            data_abertura DESC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$chamados = $stmt->fetchAll();

$contagem = db()->query(
    "SELECT status, COUNT(*) AS qtd FROM chamados GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$abertos      = (int)($contagem['Aberto']      ?? 0);
$andamento    = (int)($contagem['EmAndamento'] ?? 0);
$aguardando   = (int)($contagem['Aguardando']  ?? 0);
$resolvidos   = (int)($contagem['Resolvido']   ?? 0);
$total        = array_sum(array_map('intval', $contagem));

$pageTitle = 'Painel';
require 'header.php';
?>

<div class="titulo-pagina">Painel de chamados</div>
<div class="subtitulo-pagina">Acompanhe e gerencie todas as solicitações em um só lugar.</div>

<section class="stats">
    <div class="stat">
        <div class="stat-icone azul"><?= icone('alert', 18) ?></div>
        <div class="stat-valor"><?= $abertos ?></div>
        <div class="stat-rotulo">Abertos</div>
    </div>
    <div class="stat">
        <div class="stat-icone laranja"><?= icone('activity', 18) ?></div>
        <div class="stat-valor"><?= $andamento ?></div>
        <div class="stat-rotulo">Em andamento</div>
    </div>
    <div class="stat">
        <div class="stat-icone amarelo"><?= icone('pause', 18) ?></div>
        <div class="stat-valor"><?= $aguardando ?></div>
        <div class="stat-rotulo">Aguardando</div>
    </div>
    <div class="stat">
        <div class="stat-icone verde"><?= icone('check', 18) ?></div>
        <div class="stat-valor"><?= $resolvidos ?></div>
        <div class="stat-rotulo">Resolvidos</div>
    </div>
</section>

<div class="titulo-secao">Filtrar por status</div>
<nav class="filtros">
    <a href="?status=todos" class="filtro <?= $filtroStatus === 'todos' ? 'ativo' : '' ?>">
        Todos <span class="qtd"><?= $total ?></span>
    </a>
    <?php
    $rotulos = [
        'Aberto' => 'Abertos', 'EmAndamento' => 'Em andamento',
        'Aguardando' => 'Aguardando', 'Resolvido' => 'Resolvidos', 'Fechado' => 'Fechados',
    ];
    foreach ($statusValidos as $st):
        $qtd = (int)($contagem[$st] ?? 0);
    ?>
        <a href="?status=<?= $st ?>" class="filtro <?= $filtroStatus === $st ? 'ativo' : '' ?>">
            <?= e($rotulos[$st]) ?> <span class="qtd"><?= $qtd ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if (!$chamados): ?>
    <div class="vazio">
        <div class="vazio-icone"><?= icone('inbox', 28) ?></div>
        <h3>Nenhum chamado por aqui</h3>
        <p>Quando uma solicitação for aberta, ela aparece nesta lista.</p>
        <a href="novo.php" class="btn" style="display:inline-flex;width:auto;">
            <?= icone('plus', 16) ?> Abrir primeiro chamado
        </a>
    </div>
<?php else: foreach ($chamados as $c):
    $stClass  = 'tag-status-' . strtolower($c['status']);
    $prClass  = 'tag-prio-'   . strtolower($c['prioridade']);
    [$slaClass, $slaTexto] = classeSLA($c['previsao_sla'], $c['status']);
?>
    <a href="detalhe.php?id=<?= (int)$c['id'] ?>" class="card-chamado">
        <div class="card-chamado-topo">
            <div class="card-id-titulo">
                <div class="card-id">#<?= str_pad((string)$c['id'], 4, '0', STR_PAD_LEFT) ?></div>
                <div class="card-titulo"><?= e($c['titulo']) ?></div>
            </div>
            <?php if ($slaClass): ?>
                <div class="sla-indicador <?= $slaClass ?>">
                    <?= icone('clock', 12) ?> <?= e($slaTexto) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-info">
            <span><?= icone('user', 13) ?> <?= e($c['solicitante']) ?></span>
            <span><?= icone('tag', 13) ?> <?= e($c['categoria']) ?></span>
            <span><?= icone('clock', 13) ?> <?= tempoRelativo($c['data_abertura']) ?></span>
        </div>
        <div class="card-tags">
            <span class="tag <?= $stClass ?>"><?= e(rotuloStatus($c['status'])) ?></span>
            <span class="tag <?= $prClass ?>"><?= e($c['prioridade']) ?></span>
        </div>
    </a>
<?php endforeach; endif; ?>

<?php require 'footer.php'; ?>
