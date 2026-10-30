<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';

// Cargar noticias
$noticias = [];
$error_json = false;
$error_msg = '';

if (!file_exists(NOTICIAS_FILE)) {
    $error_json = true;
    $error_msg = 'No se encuentra el archivo: ' . NOTICIAS_FILE;
} else {
    $json = file_get_contents(NOTICIAS_FILE);
    if ($json === false) {
        $error_json = true;
        $error_msg = 'No se puede leer el archivo.';
    } else {
        $datos = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_json = true;
            $error_msg = 'JSON inválido: ' . json_last_error_msg();
        } elseif (!is_array($datos)) {
            $error_json = true;
            $error_msg = 'El JSON no es un array válido.';
        } else {
            $noticias = $datos;
            usort($noticias, function($a, $b) {
                return strtotime($b['fecha'] ?? '1970-01-01') - strtotime($a['fecha'] ?? '1970-01-01');
            });
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(SERVER_NAME) ?> - <?= t('news_titulo') ?></title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/noticias.css">
</head>
<body>
    <div class="contenedor">
        <div class="cabecera" style="text-align:center; margin-bottom:40px;">
            <div class="logo-name" style="color:#f0c94a; font-size:32px; font-weight:800; text-shadow:0 0 18px rgba(240,201,74,0.55);">
                <?= htmlspecialchars(SERVER_NAME) ?>
            </div>
            <p class="subtitulo"><?= t('news_subtitulo') ?></p>
        </div>

        <div class="nav-botones" style="display:flex; gap:12px; justify-content:center; margin-bottom:35px; flex-wrap:wrap;">
            <a href="index.php" class="btn-nav">⚔ <?= t('news_volver') ?></a>
            <a href="noticias.php" class="btn-nav">📰 <?= t('news_recargar') ?></a>
            <a href="?lang=es" class="btn-nav" style="<?= $idioma === 'es' ? 'border-color:#f0c94a;' : '' ?>">🇪🇸 ES</a>
            <a href="?lang=en" class="btn-nav" style="<?= $idioma === 'en' ? 'border-color:#f0c94a;' : '' ?>">🇬🇧 EN</a>
        </div>

        <div class="noticias-lista">
            <?php if ($error_json): ?>
                <div class="alerta error">
                    <strong>⚠ <?= t('news_error') ?></strong><br>
                    <?= htmlspecialchars($error_msg) ?><br>
                    <code><?= htmlspecialchars(NOTICIAS_FILE) ?></code>
                </div>

            <?php elseif (empty($noticias)): ?>
                <div class="vacio">
                    <span class="icono">📭</span>
                    <?= t('news_vacio') ?><br>
                    <?= t('news_vacio_add') ?> <code>noticias.json</code>.
                </div>

            <?php else: ?>
                <?php foreach ($noticias as $n): ?>
                    <article class="noticia">
                        <h2 class="noticia-titulo"><?= htmlspecialchars($n['titulo'] ?? t('news_sin_titulo')) ?></h2>
                        <div class="noticia-meta">
                            <?php if (!empty($n['fecha'])): ?>
                                <span class="meta-fecha"><?= htmlspecialchars(fechaBonita($n['fecha'])) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($n['autor'])): ?>
                                <span class="meta-autor"><?= htmlspecialchars($n['autor']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="noticia-contenido"><?= htmlspecialchars($n['contenido'] ?? '') ?></div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
