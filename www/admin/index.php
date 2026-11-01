<?php
require_once __DIR__ . '/../lang.php';
require_once __DIR__ . '/../config.php';

// Solo admin
if (empty($_SESSION['es_admin'])) {
    header('Location: ../index.php');
    exit;
}

$web_user = $_SESSION['web_username'] ?? '';
$tab = $_GET['tab'] ?? 'items';
if (!in_array($tab, ['items', 'noticias'], true)) $tab = 'items';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(SERVER_NAME) ?> - <?= t('gm_panel') ?></title>
    <link rel="stylesheet" href="../css/base.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/forms.css">
    <link rel="stylesheet" href="../css/tienda.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<div class="topbar">
    <nav class="nav">
        <div class="brand-group">
            <a href="../index.php" class="brand"><?= htmlspecialchars(SERVER_NAME) ?></a>
            <span class="lang">
                <a href="?tab=<?= $tab ?>&lang=es" class="<?= $idioma === 'es' ? 'active' : '' ?>">🇪🇸 ES</a>
                <a href="?tab=<?= $tab ?>&lang=en" class="<?= $idioma === 'en' ? 'active' : '' ?>">🇬🇧 EN</a>
            </span>
        </div>
        <div class="menu">
            <span class="user-tag">
                <span class="ico">👤</span> <strong><?= htmlspecialchars($web_user) ?></strong>
                <span class="badge-gm">GM</span>
            </span>
            <a href="../index.php" class="btn-nav">← <?= t('news_volver') ?></a>
            <a href="../index.php?logout=1" class="btn-nav btn-logout" title="Cerrar sesión">⏻</a>
        </div>
    </nav>
</div>

<div class="contenedor">
    <div class="caja caja-logueado">

        <div class="tabs" style="margin-bottom:25px;">
            <a href="?tab=items" class="tab <?= $tab === 'items' ? 'activo' : '' ?>" style="text-decoration:none;">🛒 <?= t('manage_items') ?></a>
            <a href="?tab=noticias" class="tab <?= $tab === 'noticias' ? 'activo' : '' ?>" style="text-decoration:none;">📰 <?= t('create_news') ?></a>
        </div>

        <?php if ($tab === 'items'): ?>
            <?php require __DIR__ . '/items.php'; ?>
        <?php else: ?>
            <?php require __DIR__ . '/noticias.php'; ?>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
