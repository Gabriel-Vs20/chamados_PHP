<?php
require_once 'db.php';
require_once 'helpers.php';
require_once 'feriados.php';

$erros = [];
$dados = [
    'titulo' => '', 'descricao' => '', 'categoria' => 'Software',
    'prioridade' => 'Media', 'solicitante' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($dados as $k => $_) {
        $dados[$k] = trim($_POST[$k] ?? '');
    }
    if ($dados['titulo'] === '' || mb_strlen($dados['titulo']) > 150) {
        $erros[] = 'Título obrigatório (até 150 caracteres).';
    }
    if ($dados['descricao'] === '') {
        $erros[] = 'Descrição obrigatória.';
    }
    if ($dados['solicitante'] === '') {
        $erros[] = 'Solicitante obrigatório.';
    }
    if (!in_array($dados['categoria'], ['Hardware','Software','Rede','Acesso','Outros'], true)) {
        $erros[] = 'Categoria inválida.';
    }
    if (!in_array($dados['prioridade'], ['Baixa','Media','Alta','Critica'], true)) {
        $erros[] = 'Prioridade inválida.';
    }
    if (!$erros) {
        $previsao = calcularPrevisaoSLA($dados['prioridade']);
        $stmt = db()->prepare(
            "INSERT INTO chamados
                (titulo, descricao, categoria, prioridade, solicitante, previsao_sla)
             VALUES (:t, :d, :c, :p, :s, :sla)"
        );
        $stmt->execute([
            ':t' => $dados['titulo'], ':d' => $dados['descricao'],
            ':c' => $dados['categoria'], ':p' => $dados['prioridade'],
            ':s' => $dados['solicitante'], ':sla' => $previsao,
        ]);
        $id = (int)db()->lastInsertId();
        redirect("detalhe.php?id={$id}&criado=1");
    }
}

$pageTitle = 'Novo chamado';
require 'header.php';
?>

<div class="titulo-pagina">Novo chamado</div>
<div class="subtitulo-pagina">Descreva o problema e a equipe responsável receberá a solicitação.</div>

<?php if ($erros): ?>
    <div class="flash flash-erro">
        <?= icone('alert', 18) ?>
        <div>
            <?php foreach ($erros as $erro): ?>
                <div><?= e($erro) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<form method="post" class="form-card">
    <div class="form-grid">
        <div class="form-group">
            <label for="titulo">Título do chamado</label>
            <input type="text" id="titulo" name="titulo" maxlength="150"
                   placeholder="Ex.: Impressora do faturamento sem conexão"
                   value="<?= e($dados['titulo']) ?>" required>
        </div>
        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="solicitante">Solicitante</label>
                <input type="text" id="solicitante" name="solicitante" maxlength="100"
                       placeholder="Seu nome completo"
                       value="<?= e($dados['solicitante']) ?>" required>
            </div>
            <div class="form-group">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria">
                    <?php foreach (['Hardware','Software','Rede','Acesso','Outros'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $dados['categoria'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="prioridade">Prioridade</label>
            <select id="prioridade" name="prioridade">
                <?php foreach (['Baixa' => '10 dias úteis','Media' => '5 dias úteis','Alta' => '2 dias úteis','Critica' => '1 dia útil'] as $opt => $sla): ?>
                    <option value="<?= $opt ?>" <?= $dados['prioridade'] === $opt ? 'selected' : '' ?>>
                        <?= $opt ?> · SLA <?= $sla ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="hint">O SLA é calculado descontando finais de semana e feriados nacionais (BrasilAPI).</span>
        </div>
        <div class="form-group">
            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao"
                      placeholder="Descreva com detalhes o que está acontecendo, quando começou e o que você já tentou."
                      required><?= e($dados['descricao']) ?></textarea>
        </div>
    </div>

    <div class="btn-fileira">
        <a href="index.php" class="btn btn-sec"><?= icone('arrow-left', 16) ?> Cancelar</a>
        <button type="submit" class="btn"><?= icone('send', 16) ?> Abrir chamado</button>
    </div>
</form>

<?php require 'footer.php'; ?>
