<?php
require_once 'helpers.php';
$pageTitle = $pageTitle ?? 'Chamados';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#FF6B35">
<title><?= e($pageTitle) ?> · Chamados</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="brand">
            <span class="brand-logo"><?= icone('layers', 20) ?></span>
            <span class="brand-text">
                <span class="marca">Chamados</span>
                <span class="sub">Service Desk</span>
            </span>
        </a>
        <a href="novo.php" class="btn-novo" aria-label="Novo chamado"><?= icone('plus', 20) ?></a>
    </div>
</header>
<main class="container"> <!-- Conteúdo principal -->
    
