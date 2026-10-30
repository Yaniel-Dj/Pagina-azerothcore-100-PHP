<?php
// Incluido desde admin/index.php

$mensaje = '';
$tipo = '';

// ============================================
// CREAR NOTICIA
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_noticia'])) {
    $titulo    = trim($_POST['titulo'] ?? '');
    $fecha     = trim($_POST['fecha'] ?? date('Y-m-d'));
    $autor     = trim($_POST['autor'] ?? $web_user);
    $contenido = trim($_POST['contenido'] ?? '');
    if (empty($titulo) || empty($contenido)) {
        $mensaje = t('news_required'); $tipo = 'error';
    } else {
        $noticias = [];
        if (file_exists(NOTICIAS_FILE)) {
            $json = file_get_contents(NOTICIAS_FILE);
            $datos = json_decode($json, true);
            if (is_array($datos)) $noticias = $datos;
        }
        $noticias[] = ['id' => time(), 'titulo' => $titulo, 'fecha' => $fecha, 'autor' => $autor, 'contenido' => $contenido];
        $json_nuevo = json_encode($noticias, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents(NOTICIAS_FILE, $json_nuevo) !== false) {
            $mensaje = t('news_published'); $tipo = 'exito';
        } else {
            $mensaje = t('news_save_error'); $tipo = 'error';
        }
    }
}
?>

<?php if ($mensaje): ?>
    <div class="alerta <?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<div class="admin-section">
    <h3><?= t('create_news') ?></h3>
    <form method="POST">
        <label for="titulo"><?= t('title') ?></label>
        <input type="text" id="titulo" name="titulo" placeholder="<?= t('title') ?>" required>
        <label for="fecha"><?= t('date') ?></label>
        <input type="date" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>">
        <label for="autor"><?= t('author') ?></label>
        <input type="text" id="autor" name="autor" value="<?= htmlspecialchars($web_user) ?>">
        <label for="contenido"><?= t('content') ?></label>
        <textarea id="contenido" name="contenido" placeholder="<?= t('content') ?>" required></textarea>
        <button type="submit" name="crear_noticia"><?= t('publish') ?></button>
    </form>
</div>
