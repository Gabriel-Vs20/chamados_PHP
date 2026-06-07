<?php
// db.php - Conexao PDO MySQL
// Preencher com os dados do painel do InfinityFree (MySQL Databases)

const DB_HOST = 'sql303.infinityfree.com'; // ex: sql105.infinityfree.com
const DB_NAME = 'if0_42097670_projeto_pd';    // nome do banco gerado pelo painel
const DB_USER = 'if0_42097670';             // usuario gerado pelo painel
const DB_PASS = 'JiRiMKdkql2s';          // senha definida na criacao

// URL publica da aplicacao (usada no botao "Abrir chamado" do Teams).
const APP_URL = 'https://projetoprogacaodistribuida.site.je';

// Webhook do Microsoft Teams (Workflows -> Post to a channel when a webhook request is received).
// Cole a URL gerada pelo Workflow. Deixe vazio para desativar notificacoes.
const TEAMS_WEBHOOK_URL = 'https://default38ae2f0257104e1280bb83600c3fdf.1e.environment.api.powerplatform.com:443/powerautomate/automations/direct/workflows/9a167b09bda946eea3457ad2f243c24f/triggers/manual/paths/invoke?api-version=1&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=iF5otrEwt1zkSoyk9v4gDxvl2hSAhJWNgziYwzGCGd4'; // ex: https://prod-XX.westus.logic.azure.com:443/workflows/...

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
