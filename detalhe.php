<?php
require_once 'db.php';
require_once 'helpers.php';
require_once 'teams.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'status') {
        $novo = $_POST['status'] ?? '';
        $resp = trim($_POST['responsavel'] ?? '');
        if (in_array($novo, ['Aberto','EmAndamento','Aguardando','Resolvido','Fechado'], true)) {
            // busca estado atual para detectar mudanca real
            $atual = db()->prepare("SELECT status, titulo, prioridade, solicitante FROM chamados WHERE id = :id");
            $atual->execute([':id' => $id]);
            $antes = $atual->fetch();

            $stmt = db()->prepare(
                "UPDATE chamados SET status = :st, responsavel = :r WHERE id = :id"
            );
            $stmt->execute([
                ':st' => $novo, ':r' => $resp !== '' ? $resp : null, ':id' => $id,
            ]);

            // notifica Teams apenas se o status realmente mudou
            if ($antes && $antes['status'] !== $novo) {
                notificarMudancaStatus(
                    $id,
                    $antes['titulo'],
                    $antes['status'],
                    $novo,
                    $antes['prioridade'],
                    $resp !== '' ? $resp : null,
                    $antes['solicitante']
                );
            }
        }
        redirect("detalhe.php?id={$id}&atualizado=1");
    }
    if ($acao === 'comentar') {
        $autor = trim($_POST['autor'] ?? '');
        $texto = trim($_POST['texto'] ?? '');
        if ($autor !== '' && $texto !== '') {
            $stmt = db()->prepare(
                "INSERT INTO comentarios (chamado_id, autor, texto) VALUES (:c, :a, :t)"
            );
            $stmt->execute([':c' => $id, ':a' => $autor, ':t' => $texto]);
        }
        redirect("detalhe.php?id={$id}#comentarios");
    }
    if ($acao === 'excluir') {
        $stmt = db()->prepare("DELETE FROM chamados WHERE id = :id");
        $stmt->execute([':id' => $id]);
        redirect('index.php?excluido=1');
    }
}

$stmt = db()->prepare("SELECT * FROM chamados WHERE id = :id");
$stmt->execute([':id' => $id]);
$c = $stmt->fetch();
if (!$c) {
    http_response_code(404);
    redirect('index.php');
}

$stmt = db()->prepare(
    "SELECT autor, texto, data_criacao FROM comentarios
     WHERE chamado_id = :id ORDER BY data_criacao DESC"
);
$stmt->execute([':id' => $id]);
$comentarios = $stmt->fetchAll();

$stClass = 'tag-status-' . strtolower($c['status']);
$prClass = 'tag-prio-'   . strtolower($c['prioridade']);
[$slaClass, $slaTexto] = classeSLA($c['previsao_sla'], $c['status']);

$pageTitle = 'Chamado #' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
require 'header.php';
?>

<?php if (isset($_GET['criado'])): ?>
    <div class="flash flash-ok">
        <?= icone('check-circle', 18) ?> Chamado aberto com sucesso.
    </div>
<?php elseif (isset($_GET['atualizado'])): ?>
    <div class="flash flash-ok">
        <?= icone('check-circle', 18) ?> Chamado atualizado.
    </div>
<?php endif; ?>

<a href="index.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--slate-500);font-size:13px;font-weight:500;margin-bottom:14px;">
    <?= icone('arrow-left', 14) ?> Voltar ao painel
</a>

<div class="detalhe-grid">
    <div>
        <div class="chamado-header" style="margin-bottom:16px;">
            <div class="id-label">#<?= str_pad((string)$c['id'], 4, '0', STR_PAD_LEFT) ?></div>
            <h1><?= e($c['titulo']) ?></h1>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <span class="tag <?= $stClass ?>"><?= e(rotuloStatus($c['status'])) ?></span>
                <span class="tag <?= $prClass ?>"><?= e($c['prioridade']) ?></span>
                <?php if ($slaClass): ?>
                    <span class="sla-indicador <?= $slaClass ?>">
                        <?= icone('clock', 12) ?> <?= e($slaTexto) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="desc"><?= e($c['descricao']) ?></div>
        </div>

        <div class="bloco" id="comentarios" style="margin-bottom:16px;">
            <h2>Histórico (<?= count($comentarios) ?>)</h2>
            <?php if (!$comentarios): ?>
                <p style="color:var(--slate-500);font-size:13px;">Nenhum comentário ainda. Seja o primeiro a registrar uma atualização.</p>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($comentarios as $co): ?>
                        <div class="timeline-item">
                            <div class="timeline-autor">
                                <span class="timeline-avatar"><?= e(iniciais($co['autor'])) ?></span>
                                <span class="timeline-nome"><?= e($co['autor']) ?></span>
                                <span class="timeline-quando">· <?= tempoRelativo($co['data_criacao']) ?></span>
                            </div>
                            <div class="timeline-texto"><?= nl2br(e($co['texto'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" style="margin-top:18px;padding-top:18px;border-top:1px solid var(--slate-100);">
                <input type="hidden" name="acao" value="comentar">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="autor">Seu nome</label>
                        <input type="text" id="autor" name="autor" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label for="texto">Adicionar comentário</label>
                        <textarea id="texto" name="texto" placeholder="Escreva uma atualização sobre o chamado..." required></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-bloco" style="margin-top:14px;">
                    <?= icone('send', 16) ?> Publicar
                </button>
            </form>
        </div>
    </div>

    <aside>
        <div class="bloco" style="margin-bottom:16px;">
            <h2>Detalhes</h2>
            <div class="meta-lista">
                <div class="meta-item">
                    <span class="rotulo"><?= icone('user', 14) ?> Solicitante</span>
                    <span class="valor"><?= e($c['solicitante']) ?></span>
                </div>
                <div class="meta-item">
                    <span class="rotulo"><?= icone('user', 14) ?> Responsável</span>
                    <span class="valor"><?= $c['responsavel'] ? e($c['responsavel']) : '—' ?></span>
                </div>
                <div class="meta-item">
                    <span class="rotulo"><?= icone('tag', 14) ?> Categoria</span>
                    <span class="valor"><?= e($c['categoria']) ?></span>
                </div>
                <div class="meta-item">
                    <span class="rotulo"><?= icone('clock', 14) ?> Aberto em</span>
                    <span class="valor"><?= (new DateTime($c['data_abertura']))->format('d/m/Y H:i') ?></span>
                </div>
                <div class="meta-item">
                    <span class="rotulo"><?= icone('activity', 14) ?> Atualizado</span>
                    <span class="valor"><?= tempoRelativo($c['data_atualizacao']) ?></span>
                </div>
                <div class="meta-item">
                    <span class="rotulo"><?= icone('calendar', 14) ?> Prazo SLA</span>
                    <span class="valor">
                        <?= $c['previsao_sla']
                            ? (new DateTime($c['previsao_sla']))->format('d/m/Y')
                            : '—' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="bloco" style="margin-bottom:16px;">
            <h2>Atualizar</h2>
            <form method="post">
                <input type="hidden" name="acao" value="status">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <?php foreach (['Aberto','EmAndamento','Aguardando','Resolvido','Fechado'] as $opt): ?>
                                <option value="<?= $opt ?>" <?= $c['status'] === $opt ? 'selected' : '' ?>>
                                    <?= rotuloStatus($opt) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="responsavel">Responsável</label>
                        <input type="text" id="responsavel" name="responsavel" maxlength="100"
                               value="<?= e($c['responsavel'] ?? '') ?>"
                               placeholder="Quem está cuidando">
                    </div>
                </div>
                <button type="submit" class="btn btn-bloco" style="margin-top:14px;">
                    <?= icone('check', 16) ?> Salvar alterações
                </button>
            </form>
        </div>

        <form method="post" onsubmit="return confirm('Excluir este chamado? Esta ação não pode ser desfeita.');">
            <input type="hidden" name="acao" value="excluir">
            <button type="submit" class="btn btn-perigo btn-bloco">
                <?= icone('trash', 16) ?> Excluir chamado
            </button>
        </form>
    </aside>
</div>

<?php require 'footer.php'; ?>
