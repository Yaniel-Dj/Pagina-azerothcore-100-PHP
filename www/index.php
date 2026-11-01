<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';

$mensaje = '';
$tipo = '';

$logueado = !empty($_SESSION['web_logueado']);
$es_admin = !empty($_SESSION['es_admin']);
$web_user = $_SESSION['web_username'] ?? '';
$web_id   = $_SESSION['web_user_id'] ?? 0;

// ============================================
// LOGOUT
// ============================================
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// ============================================
// VERIFICAR SRP6 (para login)
// ============================================
function verifySRP6Password($username, $password, $salt_binario, $verifier_esperado) {
    $g = gmp_init(7);
    $N = gmp_init('894B645E89E1535BBDAD5B8B290650530801B18EBFBF5E8FAB3C82872A3E9BB7', 16);

    $h1 = sha1(strtoupper($username) . ':' . strtoupper($password), true);
    $h2 = sha1($salt_binario . $h1, true);
    $h2_int = gmp_import($h2, 1, GMP_LSW_FIRST);
    $verifier_calc = gmp_powm($g, $h2_int, $N);
    $verifier_bin = gmp_export($verifier_calc, 1, GMP_LSW_FIRST);
    $verifier_bin = str_pad($verifier_bin, 32, chr(0), STR_PAD_RIGHT);

    return hash_equals($verifier_esperado, $verifier_bin);
}

// ============================================
// LOGIN WEB
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_web'])) {
    $user = trim($_POST['login_user'] ?? '');
    $pass = $_POST['login_pass'] ?? '';
    if (empty($user) || empty($pass)) {
        $mensaje = t('login_empty'); $tipo = 'error';
    } else {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $stmt = $pdo->prepare("SELECT id, salt, verifier FROM account WHERE username = ?");
            $stmt->execute([strtoupper($user)]);
            $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($cuenta && verifySRP6Password($user, $pass, $cuenta['salt'], $cuenta['verifier'])) {
                $_SESSION['web_user_id']  = $cuenta['id'];
                $_SESSION['web_username'] = strtoupper($user);
                $_SESSION['web_logueado'] = true;
                $stmt_gm = $pdo->prepare("SELECT gmlevel FROM account_access WHERE id = ? AND RealmID IN (-1, 1) LIMIT 1");
                $stmt_gm->execute([$cuenta['id']]);
                $gm = $stmt_gm->fetch(PDO::FETCH_ASSOC);
                $_SESSION['es_admin'] = ($gm && (int)$gm['gmlevel'] >= 3);
                header('Location: index.php');
                exit;
            } else {
                $mensaje = t('login_invalid'); $tipo = 'error';
            }
        } catch (PDOException $e) {
            $mensaje = t('db_error'); $tipo = 'error';
        }
    }
}

// ============================================
// SRP6 REGISTRO
// ============================================
function calculateSRP6Verifier($username, $password) {
    $g = gmp_init(7);
    $N = gmp_init('894B645E89E1535BBDAD5B8B290650530801B18EBFBF5E8FAB3C82872A3E9BB7', 16);

    $h1 = sha1(strtoupper($username) . ':' . strtoupper($password), true);
    $salt = random_bytes(32);
    $h2 = sha1($salt . $h1, true);

    $h2_int = gmp_import($h2, 1, GMP_LSW_FIRST);
    $verifier = gmp_powm($g, $h2_int, $N);

    $verifier_bin = gmp_export($verifier, 1, GMP_LSW_FIRST);
    $verifier_bin = str_pad($verifier_bin, 32, chr(0), STR_PAD_RIGHT);

    return ['salt' => $salt, 'verifier' => $verifier_bin];
}

// ============================================
// ESTADO SERVIDOR
// ============================================
$conexion = @fsockopen(SERVER_IP, SERVER_PORT, $errno, $errstr, SERVER_TIMEOUT);
if ($conexion) {
    $server_online = true; $estado_clase = 'online'; $estado_texto = t('online');
    fclose($conexion);
} else {
    $server_online = false; $estado_clase = 'offline'; $estado_texto = t('offline');
}

// ============================================
// FACCIONES
// ============================================
function getFactionCounts() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . CHAR_DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo->query("SELECT COUNT(*) FROM characters WHERE online = 1 AND race IN (1, 3, 4, 7, 11)");
        $alianza = (int)$stmt->fetchColumn();
        $stmt = $pdo->query("SELECT COUNT(*) FROM characters WHERE online = 1 AND race IN (2, 5, 6, 8, 10)");
        $horda = (int)$stmt->fetchColumn();
        return ['alianza' => $alianza, 'horda' => $horda];
    } catch (PDOException $e) { return ['alianza' => 0, 'horda' => 0]; }
}
$facciones = getFactionCounts();

// ============================================
// UPTIME
// ============================================
function getServerUptimeSeconds() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo->query("SELECT starttime FROM uptime ORDER BY starttime DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int)$row['starttime'] > 0) {
            $diff = time() - (int)$row['starttime'];
            return $diff > 0 ? $diff : 0;
        }
        return 0;
    } catch (PDOException $e) { return 0; }
}
function formatUptime($seconds) {
    if ($seconds <= 0) return '—';
    $d = floor($seconds / 86400); $h = floor(($seconds % 86400) / 3600); $m = floor(($seconds % 3600) / 60); $s = $seconds % 60;
    if ($d > 0) return "{$d}d {$h}h {$m}m";
    if ($h > 0) return "{$h}h {$m}m";
    if ($m > 0) return "{$m}m {$s}s";
    return "{$s}s";
}
$uptime_segundos = $server_online ? getServerUptimeSeconds() : 0;
$tiempo_online   = $server_online ? formatUptime($uptime_segundos) : null;

// ============================================
// REGISTRO
// ============================================
if (!$logueado && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario'])) {
    $usuario   = trim($_POST['usuario'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if (empty($usuario) || empty($password)) { $mensaje = t('login_empty'); $tipo = 'error'; }
    elseif (!preg_match('/^[a-zA-Z0-9]+$/', $usuario)) { $mensaje = t('register_invalid_chars'); $tipo = 'error'; }
    elseif (strlen($usuario) < MIN_USERNAME_LENGTH || strlen($usuario) > MAX_USERNAME_LENGTH) { $mensaje = t('register_username_len') . ' ' . MIN_USERNAME_LENGTH . ' ' . t('and') . ' ' . MAX_USERNAME_LENGTH; $tipo = 'error'; }
    elseif (strlen($password) < MIN_PASSWORD_LENGTH || strlen($password) > MAX_PASSWORD_LENGTH) { $mensaje = t('register_password_len') . ' ' . MIN_PASSWORD_LENGTH . ' ' . t('and') . ' ' . MAX_PASSWORD_LENGTH; $tipo = 'error'; }
    elseif ($password !== $password2) { $mensaje = t('register_pass_mismatch'); $tipo = 'error'; }
    else {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $stmt = $pdo->prepare("SELECT id FROM account WHERE username = ?");
            $stmt->execute([strtoupper($usuario)]);
            if ($stmt->fetch()) { $mensaje = t('register_taken'); $tipo = 'error'; }
            else {
                $srp = calculateSRP6Verifier($usuario, $password);
                $stmt = $pdo->prepare("INSERT INTO account (username, salt, verifier, email, reg_mail, joindate, last_ip, expansion) VALUES (?, ?, ?, '', '', NOW(), ?, ?)");
                $stmt->execute([strtoupper($usuario), $srp['salt'], $srp['verifier'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', EXPANSION]);
                $mensaje = t('register_success'); $tipo = 'exito';
            }
        } catch (PDOException $e) { $mensaje = t('db_error') . ': ' . $e->getMessage(); $tipo = 'error'; }
    }
}

// ============================================
// CARGAR DATOS PARA DASHBOARD
// ============================================
$noticias_mostrar = [];
$personajes = [];

if ($logueado) {
    if (file_exists(NOTICIAS_FILE)) {
        $json = file_get_contents(NOTICIAS_FILE);
        $datos = json_decode($json, true);
        if (is_array($datos)) {
            $noticias_mostrar = $datos;
            usort($noticias_mostrar, function($a, $b) {
                return strtotime($b['fecha'] ?? '1970-01-01') - strtotime($a['fecha'] ?? '1970-01-01');
            });
        }
    }
    try {
        $pdo_char = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . CHAR_DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt_char = $pdo_char->prepare("SELECT guid, name, level, money FROM characters WHERE account = ? ORDER BY level DESC");
        $stmt_char->execute([$web_id]);
        $personajes = $stmt_char->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(SERVER_NAME) ?> - <?= $logueado ? t('welcome') : t('register_btn') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/noticias.css">
</head>
<body>

<div class="topbar">
    <nav class="nav">
        <div class="brand-group">
            <a href="index.php" class="brand"><?= htmlspecialchars(SERVER_NAME) ?></a>
            <span class="lang">
                <a href="?lang=es" class="<?= $idioma === 'es' ? 'active' : '' ?>">🇪🇸 ES</a>
                <a href="?lang=en" class="<?= $idioma === 'en' ? 'active' : '' ?>">🇬🇧 EN</a>
            </span>
        </div>
        <div class="menu">
            <?php if ($logueado): ?>
                <a href="tienda.php" class="btn-nav"><span class="ico">🛒</span> <?= t('shop') ?></a>
                <a href="noticias.php" class="btn-nav"><span class="ico">📰</span> <?= t('news_titulo') ?></a>
                <a href="guias.php" class="btn-nav"><span class="ico">📖</span> <?= t('guias_titulo') ?></a>
                <?php if ($es_admin): ?>
                    <a href="admin/index.php" class="btn-nav gm"><span class="ico">🛡️</span> <?= t('gm_panel') ?></a>
                <?php endif; ?>
                <span class="user-tag">
                    <span class="ico">👤</span> <strong><?= htmlspecialchars($web_user) ?></strong>
                    <?php if ($es_admin): ?><span class="badge-gm">GM</span><?php endif; ?>
                </span>
                <a href="index.php?logout=1" class="btn-nav btn-logout" title="<?= t('logout') ?>"><i class="fa-solid fa-right-from-bracket"></i></a>
            <?php else: ?>
                <a href="#" class="btn-nav outline" onclick="cambiarTab('login', document.querySelector('.tab')); return false;">→ <?= t('login_tab') ?></a>
                <a href="#" class="btn-nav primary" onclick="cambiarTab('registro', document.querySelectorAll('.tab')[1]); return false;">+ <?= t('register_tab') ?></a>
            <?php endif; ?>
        </div>
    </nav>
</div>

<div class="contenedor">
    <div class="caja <?= $logueado ? 'caja-logueado' : '' ?>">

        <?php if ($logueado): ?>
            <div class="bienvenida">
                <?= t('welcome') ?>, <strong><?= htmlspecialchars($web_user) ?></strong>
            </div>

            <?php if (!empty($personajes)): ?>
                <div class="oro-badge">
                    <div class="oro-titulo">💰 <?= t('your_gold') ?></div>
                    <ul class="oro-lista">
                        <?php foreach ($personajes as $pj): ?>
                            <li>
                                <span class="oro-pj"><?= htmlspecialchars($pj['name']) ?></span>
                                <span class="oro-cantidad"><?= number_format(floor($pj['money'] / COBRE_POR_ORO)) ?> <?= t('gold') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($mensaje): ?>
                <div class="alerta <?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>

            <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:center; margin-bottom:30px;">
                <a href="tienda.php" class="btn-nav">🛒 <?= t('shop') ?></a>
                <a href="noticias.php" class="btn-nav">📰 <?= t('news_titulo') ?></a>
                <a href="guias.php" class="btn-nav">📖 <?= t('guias_titulo') ?></a>
            </div>

            <div id="seccion-noticias">
                <h3 style="color:#f0c94a; font-size:16px; margin-bottom:15px; text-align:center;"><?= t('news_titulo') ?></h3>
                <?php if (empty($noticias_mostrar)): ?>
                    <div class="vacio"><span class="icono">📭</span><?= t('no_news') ?></div>
                <?php else: ?>
                    <div class="noticias-lista">
                        <?php foreach (array_slice($noticias_mostrar, 0, 3) as $n): ?>
                            <article class="noticia">
                                <h2 class="noticia-titulo"><?= htmlspecialchars($n['titulo'] ?? t('no_news')) ?></h2>
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
                    </div>
                    <div style="text-align:center; margin-top:20px;">
                        <a href="noticias.php" class="btn-nav"><?= t('news_recargar') ?> →</a>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <p class="subtitulo"><?= t('tagline') ?></p>
            <div class="server-status <?= $estado_clase ?>">
                <span class="status-text"><?= $estado_texto ?></span>
                <?php if ($server_online && $tiempo_online !== null && $tiempo_online !== '—'): ?>
                    <span class="uptime-text" id="uptime-display" data-segundos="<?= (int)$uptime_segundos ?>">⏱️ <?= $tiempo_online ?></span>
                <?php endif; ?>
            </div>
            <div class="faction-counts">
                <div class="faction alliance"><span class="faction-circle blue"></span><span class="faction-name"><?= t('alliance') ?></span><span class="faction-number"><?= $facciones['alianza'] ?></span></div>
                <div class="faction horde"><span class="faction-circle red"></span><span class="faction-name"><?= t('horde') ?></span><span class="faction-number"><?= $facciones['horda'] ?></span></div>
            </div>
            <?php if ($mensaje): ?>
                <div class="alerta <?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <div class="tabs">
                <div class="tab activo" onclick="cambiarTab('login', this)"><?= t('login_tab') ?></div>
                <div class="tab" onclick="cambiarTab('registro', this)"><?= t('register_tab') ?></div>
            </div>
            <div id="tab-login" class="tab-content activo">
                <form method="POST">
                    <label for="login_user"><?= t('username') ?></label>
                    <input type="text" id="login_user" name="login_user" placeholder="<?= t('login_user_ph') ?>" required>
                    <label for="login_pass"><?= t('password') ?></label>
                    <input type="password" id="login_pass" name="login_pass" placeholder="<?= t('login_pass_ph') ?>" required>
                    <button type="submit" name="login_web"><?= t('login_btn') ?></button>
                </form>
            </div>
            <div id="tab-registro" class="tab-content">
                <form method="POST">
                    <label for="usuario"><?= t('username') ?></label>
                    <input type="text" id="usuario" name="usuario" placeholder="<?= t('username_ph') ?>" required minlength="<?= MIN_USERNAME_LENGTH ?>" maxlength="<?= MAX_USERNAME_LENGTH ?>" value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
                    <label for="password"><?= t('password') ?></label>
                    <input type="password" id="password" name="password" placeholder="<?= t('password_ph') ?>" required minlength="<?= MIN_PASSWORD_LENGTH ?>" maxlength="<?= MAX_PASSWORD_LENGTH ?>">
                    <label for="password2"><?= t('password2') ?></label>
                    <input type="password" id="password2" name="password2" placeholder="<?= t('password2_ph') ?>" required minlength="<?= MIN_PASSWORD_LENGTH ?>" maxlength="<?= MAX_PASSWORD_LENGTH ?>">
                    <button type="submit"><?= t('register_btn') ?></button>
                </form>
            </div>
            <p class="info"><?= t('info_register') ?></p>
        <?php endif; ?>
    </div>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(SERVER_NAME) ?> · Hecho con ❤</p>
</footer>

<script src="js/tabs.js"></script>
<script src="js/uptime.js"></script>
</body>
</html>