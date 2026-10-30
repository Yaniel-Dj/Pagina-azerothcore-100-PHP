<?php
session_start();
require_once __DIR__ . '/lang.php';

// ============================================
// CONFIGURACIÓN
// ============================================
define('SERVER_NAME', 'AzerothCore Server');
define('NOTICIAS_FILE', __DIR__ . '/noticias.json');

// ============================================
// CARGAR NOTICIAS
// ============================================
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

function fechaBonita($fecha) {
    global $idioma;
    if (!$fecha) return '';
    $ts = strtotime($fecha);
    if (!$ts) return htmlspecialchars($fecha);
    if ($idioma === 'en') {
        return date('F j, Y', $ts);
    }
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return date('j', $ts) . ' de ' . $meses[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}
?>
<!DOCTYPE html>
<html lang="<?= $idioma ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(SERVER_NAME) ?> - <?= $idioma === 'en' ? 'News' : 'Noticias' ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
            padding: 40px 20px;
            background-color: #0a1a3c;
            background-image: url('img/fondo.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed; inset: 0;
            background: radial-gradient(ellipse at center, transparent 0%, rgba(0,0,0,0.5) 100%),
                        linear-gradient(160deg, rgba(10,26,60,0.85) 0%, rgba(16,42,107,0.78) 45%, rgba(30,77,156,0.72) 100%);
            z-index: 0; pointer-events: none;
        }
        .contenedor {
            position: relative; z-index: 1;
            max-width: 820px; margin: 0 auto;
        }
        .cabecera {
            text-align: center; margin-bottom: 40px;
            animation: fadeIn 0.8s ease-out both;
        }
        .logo-name {
            color: #f0c94a; font-size: 32px; font-weight: 800;
            letter-spacing: 1px; line-height: 1.2;
            text-shadow: 0 0 18px rgba(240,201,74,0.55), 0 0 40px rgba(240,201,74,0.25), 0 2px 4px rgba(0,0,0,0.6);
        }
        .logo-name::before, .logo-name::after {
            content: '⚔'; color: #d4af37; font-size: 18px;
            vertical-align: middle; margin: 0 12px; opacity: 0.9;
        }
        .subtitulo {
            color: #9db4d8; font-size: 14px; margin-top: 8px;
            letter-spacing: 2px; text-transform: uppercase;
        }
        .nav-botones {
            display: flex; gap: 12px; justify-content: center;
            margin-bottom: 35px; flex-wrap: wrap;
            animation: fadeIn 0.8s ease-out 0.2s both;
        }
        .btn-nav {
            display: inline-block; padding: 11px 22px;
            background: linear-gradient(135deg, #12408f 0%, #1e5bc6 100%);
            border: 2px solid #d4af37; border-radius: 9px;
            color: #f0c94a; font-size: 14px; font-weight: 700;
            letter-spacing: 0.5px; text-decoration: none;
            transition: transform 0.15s, box-shadow 0.2s, background 0.2s;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
        }
        .btn-nav:hover {
            background: linear-gradient(135deg, #1a52b0 0%, #2a6fe0 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 22px rgba(212,175,55,0.35), 0 0 18px rgba(240,201,74,0.25);
        }
        .noticias-lista { display: flex; flex-direction: column; gap: 22px; }
        .noticia {
            background: rgba(8,20,50,0.88);
            border: 2px solid #d4af37; border-radius: 14px;
            padding: 26px 28px;
            box-shadow: 0 0 0 1px rgba(212,175,55,0.25), 0 8px 30px rgba(0,0,0,0.55);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            animation: fadeIn 0.6s ease-out both;
        }
        .noticia:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 0 1px rgba(240,201,74,0.4), 0 12px 40px rgba(0,0,0,0.65), 0 0 30px rgba(240,201,74,0.15);
        }
        .noticia-titulo {
            color: #f0c94a; font-size: 20px; font-weight: 800;
            margin-bottom: 8px; line-height: 1.3;
            text-shadow: 0 0 12px rgba(240,201,74,0.35);
        }
        .noticia-meta {
            display: flex; gap: 18px; flex-wrap: wrap;
            font-size: 12px; color: #9db4d8;
            margin-bottom: 16px; padding-bottom: 14px;
            border-bottom: 1px solid rgba(212,175,55,0.25);
        }
        .noticia-meta span::before { margin-right: 6px; color: #d4af37; }
        .meta-fecha::before { content: '📅'; }
        .meta-autor::before { content: '👤'; }
        .noticia-contenido {
            color: #c9d8f0; font-size: 14px; line-height: 1.7;
            white-space: pre-line;
        }
        .vacio, .error-json {
            background: rgba(8,20,50,0.88);
            border: 2px solid #d4af37; border-radius: 14px;
            padding: 40px 28px; text-align: center;
            color: #9db4d8; font-size: 15px;
            animation: fadeIn 0.6s ease-out both;
        }
        .error-json { border-color: #dc3545; color: #ff9aa4; }
        .error-json strong { color: #ff9aa4; display: block; margin-bottom: 10px; font-size: 16px; }
        .error-json code {
            display: inline-block; background: rgba(0,0,0,0.4);
            padding: 2px 8px; border-radius: 4px;
            color: #ffd6da; font-size: 12px; margin-top: 8px; word-break: break-all;
        }
        .vacio .icono { font-size: 40px; margin-bottom: 12px; display: block; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 600px) {
            body { padding: 25px 14px; }
            .logo-name { font-size: 24px; }
            .logo-name::before, .logo-name::after { font-size: 14px; margin: 0 8px; }
            .noticia { padding: 20px 18px; }
            .noticia-titulo { font-size: 17px; }
            .btn-nav { padding: 10px 18px; font-size: 13px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <div class="contenedor">

        <div class="cabecera">
            <div class="logo-name"><?= htmlspecialchars(SERVER_NAME) ?></div>
            <p class="subtitulo"><?= $idioma === 'en' ? 'News and Announcements' : 'Noticias y Anuncios' ?></p>
        </div>

        <div class="nav-botones">
            <a href="index.php" class="btn-nav">⚔ <?= $idioma === 'en' ? 'Back to home' : 'Volver al inicio' ?></a>
            <a href="noticias.php" class="btn-nav">📰 <?= $idioma === 'en' ? 'Reload news' : 'Recargar noticias' ?></a>
            <a href="?lang=es" class="btn-nav" style="<?= $idioma === 'es' ? 'border-color:#f0c94a;' : '' ?>">🇪🇸 ES</a>
            <a href="?lang=en" class="btn-nav" style="<?= $idioma === 'en' ? 'border-color:#f0c94a;' : '' ?>">🇬🇧 EN</a>
        </div>

        <div class="noticias-lista">
            <?php if ($error_json): ?>
                <div class="error-json">
                    <strong>⚠ <?= $idioma === 'en' ? 'Error loading news' : 'Error al cargar las noticias' ?></strong>
                    <?= htmlspecialchars($error_msg) ?>
                    <br>
                    <code><?= htmlspecialchars(NOTICIAS_FILE) ?></code>
                </div>

            <?php elseif (empty($noticias)): ?>
                <div class="vacio">
                    <span class="icono">📭</span>
                    <?= $idioma === 'en' ? 'No news published yet.' : 'No hay noticias publicadas todavía.' ?><br>
                    <?= $idioma === 'en' ? 'Add one by editing' : 'Añade una editando' ?> <code>noticias.json</code>.
                </div>

            <?php else: ?>
                <?php foreach ($noticias as $i => $n): ?>
                    <article class="noticia" style="animation-delay: <?= min($i * 0.08, 0.6) ?>s">
                        <h2 class="noticia-titulo"><?= htmlspecialchars($n['titulo'] ?? ($idioma === 'en' ? 'No title' : 'Sin título')) ?></h2>
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
