<?php
session_start();
require_once __DIR__ . '/lang.php';

// ============================================
// CONFIGURACIÓN
// ============================================
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', 'admin');
define('DB_NAME', 'acore_auth');
define('CHAR_DB_NAME', 'acore_characters');
define('WORLD_DB_NAME', 'acore_world');
define('SHOP_DB_NAME', 'acore_shop');

define('MIN_USERNAME_LENGTH', 3);
define('MAX_USERNAME_LENGTH', 16);
define('MIN_PASSWORD_LENGTH', 6);
define('MAX_PASSWORD_LENGTH', 16);
define('EXPANSION', 2);

define('SERVER_IP', '127.0.0.1');
define('SERVER_PORT', 8085);
define('SERVER_TIMEOUT', 1);

define('BG_IMAGE', 'img/fondo.jpg');
define('SERVER_NAME',    'AzerothCore Server');
define('SERVER_TAGLINE', 'Regístrate para jugar en el servidor');

define('NOTICIAS_FILE', __DIR__ . '/noticias.json');

// ============================================
// CONFIGURACIÓN SOAP
// ============================================
define('SOAP_HOST', '127.0.0.1');
define('SOAP_PORT', 7878);
define('SOAP_URI', 'urn:AC');
define('SOAP_GM_USER', 'YANIEL');
define('SOAP_GM_PASS', 'yaniel1230');

define('COBRE_POR_ORO', 10000);

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
                $_SESSION['es_admin'] = ($gm && (int)$gm['gmlevel'] >= 2);
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
// SOAP ENVIAR ITEM
// ============================================
function enviarItemPorSOAP($personaje, $itemID, $cantidad = 1) {
    try {
        $conn = new SoapClient(NULL, array(
            'location' => "http://" . SOAP_HOST . ":" . SOAP_PORT . "/",
            'uri'      => SOAP_URI,
            'style'    => SOAP_RPC,
            'login'    => SOAP_GM_USER,
            'password' => SOAP_GM_PASS,
            'connection_timeout' => 5
        ));
        $comando = ".send items " . $personaje . " \"Tienda\" \"Gracias por tu compra\" " . $itemID . ":" . $cantidad;
        $resultado = $conn->executeCommand(new SoapParam($comando, 'command'));
        unset($conn);
        return [true, (string)$resultado];
    } catch (Exception $e) {
        return [false, $e->getMessage()];
    }
}

// ============================================
// COMPRAR ITEM (con ORO)
// ============================================
if ($logueado && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comprar_item'])) {
    $item_id = (int)($_POST['item_id'] ?? 0);
    $char_guid = (int)($_POST['char_guid'] ?? 0);
    if ($item_id <= 0 || $char_guid <= 0) {
        $mensaje = t('buy_invalid'); $tipo = 'error';
    } else {
        try {
            $pdo_shop = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . SHOP_DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $stmt = $pdo_shop->prepare("SELECT * FROM web_shop_items WHERE id = ? AND activo = 1");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$item) {
                $mensaje = t('item_not_found'); $tipo = 'error';
            } else {
                $pdo_char = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . CHAR_DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $stmt_char = $pdo_char->prepare("SELECT name, money FROM characters WHERE guid = ? AND account = ?");
                $stmt_char->execute([$char_guid, $web_id]);
                $personaje = $stmt_char->fetch(PDO::FETCH_ASSOC);
                if (!$personaje) {
                    $mensaje = t('char_not_yours'); $tipo = 'error';
                } elseif ($personaje['money'] < $item['precio']) {
                    $oro_tiene = floor($personaje['money'] / COBRE_POR_ORO);
                    $oro_necesita = floor($item['precio'] / COBRE_POR_ORO);
                    $mensaje = t('not_enough_gold') . ' ' . t('need') . ' ' . $oro_necesita . ' ' . t('gold') . ' ' . t('have') . ' ' . $oro_tiene . ' ' . t('gold'); $tipo = 'error';
                } else {
                    $stmt_restar = $pdo_char->prepare("UPDATE characters SET money = money - ? WHERE guid = ?");
                    $stmt_restar->execute([$item['precio'], $char_guid]);
                    $stmt_compra = $pdo_shop->prepare("INSERT INTO web_shop_purchases (account_id, character_guid, item_entry, cantidad, precio_pagado, estado) VALUES (?, ?, ?, 1, ?, 'pendiente')");
                    $stmt_compra->execute([$web_id, $char_guid, $item['item_entry'], $item['precio']]);
                    $compra_id = $pdo_shop->lastInsertId();
                    list($ok, $detalle) = enviarItemPorSOAP($personaje['name'], $item['item_entry'], 1);
                    if ($ok) {
                        $stmt_estado = $pdo_shop->prepare("UPDATE web_shop_purchases SET estado = 'enviado' WHERE id = ?");
                        $stmt_estado->execute([$compra_id]);
                        $mensaje = t('buy_success'); $tipo = 'exito';
                    } else {
                        $stmt_devolver = $pdo_char->prepare("UPDATE characters SET money = money + ? WHERE guid = ?");
                        $stmt_devolver->execute([$item['precio'], $char_guid]);
                        $stmt_estado = $pdo_shop->prepare("UPDATE web_shop_purchases SET estado = 'fallido' WHERE id = ?");
                        $stmt_estado->execute([$compra_id]);
                        $mensaje = t('buy_fail_refund') . ' ' . t('buy_fail_soap') . ' ' . htmlspecialchars($detalle); $tipo = 'error';
                    }
                }
            }
        } catch (PDOException $e) {
            $mensaje = t('db_error') . ': ' . $e->getMessage(); $tipo = 'error';
        }
    }
}

// ============================================
// AÑADIR ITEM A LA TIENDA (GM)
// ============================================
if ($es_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_shop_item'])) {
    $item_entry = (int)($_POST['item_entry'] ?? 0);
    $nombre     = trim($_POST['item_nombre'] ?? '');
    $precio_oro = (float)($_POST['item_precio'] ?? 0);
    $activo     = isset($_POST['item_activo']) ? 1 : 0;

    if ($item_entry <= 0 || empty($nombre) || $precio_oro < 0) {
        $mensaje = t('invalid_data'); $tipo = 'error';
    } else {
        try {
            $pdo_world = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . WORLD_DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $stmt_w = $pdo_world->prepare("SELECT name FROM item_template WHERE entry = ?");
            $stmt_w->execute([$item_entry]);
            $item_world = $stmt_w->fetch(PDO::FETCH_ASSOC);
            if (!$item_world) {
                $mensaje = t('item_not_exist') . ' ' . $item_entry; $tipo = 'error';
            } else {
                $precio_cobre = (int)round($precio_oro * COBRE_POR_ORO);
                $pdo_shop = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . SHOP_DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $stmt = $pdo_shop->prepare("INSERT INTO web_shop_items (item_entry, nombre, precio, activo) VALUES (?, ?, ?, ?)");
                $stmt->execute([$item_entry, $nombre, $precio_cobre, $activo]);
                $mensaje = t('item_added') . ' (' . htmlspecialchars($item_world['name']) . ' — ' . $precio_oro . ' ' . t('gold') . ')'; $tipo = 'exito';
            }
        } catch (PDOException $e) {
            $mensaje = t('db_error') . ': ' . $e->getMessage(); $tipo = 'error';
        }
    }
}

// ============================================
// ELIMINAR ITEM DE LA TIENDA (GM)
// ============================================
if ($es_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_shop_item'])) {
    $del_id = (int)($_POST['delete_id'] ?? 0);
    if ($del_id > 0) {
        try {
            $pdo_shop = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . SHOP_DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $stmt = $pdo_shop->prepare("DELETE FROM web_shop_items WHERE id = ?");
            $stmt->execute([$del_id]);
            $mensaje = t('item_deleted'); $tipo = 'exito';
        } catch (PDOException $e) {
            $mensaje = t('db_error') . ': ' . $e->getMessage(); $tipo = 'error';
        }
    }
}

// ============================================
// CREAR NOTICIA (GM)
// ============================================
if ($es_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_noticia'])) {
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
// CARGAR DATOS VISTA LOGUEADA
// ============================================
$noticias_mostrar = [];
$productos_tienda = [];
$productos_admin  = [];
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
        $pdo_shop = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . SHOP_DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo_shop->query("SELECT * FROM web_shop_items WHERE activo = 1");
        $productos_tienda = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt_all = $pdo_shop->query("SELECT * FROM web_shop_items ORDER BY id DESC");
        $productos_admin = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
    try {
        $pdo_char = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . CHAR_DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt_char = $pdo_char->prepare("SELECT guid, name, level, money FROM characters WHERE account = ? ORDER BY level DESC");
        $stmt_char->execute([$web_id]);
        $personajes = $stmt_char->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
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
    <title><?= htmlspecialchars(SERVER_NAME) ?> - <?= $logueado ? t('shop') : t('register_btn') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scrollbar-width: thin; scrollbar-color: #d4af37 rgba(8, 20, 50, 0.8); scroll-behavior: smooth; }
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: rgba(8, 20, 50, 0.8); }
        ::-webkit-scrollbar-thumb { background: linear-gradient(180deg, #d4af37, #b8860b); border-radius: 5px; border: 2px solid rgba(8, 20, 50, 0.8); }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
            padding: 0;
            background-color: #0a1a3c;
            background-image: url('<?= BG_IMAGE ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
            overflow-x: hidden;
            color: #e6e8ee;
        }
        body::before {
            content: '';
            position: fixed; inset: 0;
            background: radial-gradient(ellipse at center, transparent 0%, rgba(0,0,0,0.5) 100%),
                        linear-gradient(160deg, rgba(10,26,60,0.82) 0%, rgba(16,42,107,0.75) 45%, rgba(30,77,156,0.70) 100%);
            z-index: -1;
            pointer-events: none;
        }

        .topbar {
            position: sticky; top: 0; z-index: 100;
            background: rgba(8, 18, 40, 0.72);
            backdrop-filter: blur(20px) saturate(160%);
            -webkit-backdrop-filter: blur(20px) saturate(160%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.4);
        }
        .topbar::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(212,175,55,0.5) 20%, rgba(240,216,120,0.8) 50%, rgba(212,175,55,0.5) 80%, transparent 100%);
            opacity: 0.7;
            pointer-events: none;
        }
        .topbar .nav {
            max-width: 1300px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 28px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .topbar .brand-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .topbar .brand {
            display: inline-flex;
            align-items: center;
            font-weight: 800;
            font-size: 1.05rem;
            letter-spacing: 2.5px;
            color: #f0c94a;
            text-decoration: none;
            text-transform: uppercase;
            text-shadow: 0 0 15px rgba(240, 201, 74, 0.5);
        }
        .topbar .lang {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            padding: 3px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
        }
        .topbar .lang a {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 10px;
            border-radius: 14px;
            font-size: 0.72rem;
            font-weight: 700;
            text-decoration: none;
            color: #9db4d8;
            transition: 0.2s;
            white-space: nowrap;
        }
        .topbar .lang a:hover { color: #f0c94a; background: rgba(212,175,55,0.1); }
        .topbar .lang a.active {
            background: linear-gradient(180deg, rgba(212,175,55,0.25), rgba(212,175,55,0.12));
            color: #f0c94a;
            border: 1px solid rgba(212,175,55,0.4);
        }

        .topbar .menu {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }
        .topbar .btn-nav {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 16px;
            border-radius: 8px;
            color: #d1d9e6;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.88rem;
            border: 1.5px solid transparent;
            transition: all 0.25s;
            background: transparent;
            cursor: pointer;
            font-family: inherit;
            white-space: nowrap;
        }
        .topbar .btn-nav .ico { font-size: 0.95rem; }
        .topbar .btn-nav:hover {
            background: rgba(212,175,55,0.08);
            border-color: rgba(212,175,55,0.3);
            color: #f0c94a;
            transform: translateY(-1px);
        }
        .topbar .btn-nav.outline {
            border-color: #d4af37;
            color: #f0c94a;
            background: rgba(212,175,55,0.08);
        }
        .topbar .btn-nav.outline:hover {
            background: rgba(212,175,55,0.18);
            border-color: #f0c94a;
            box-shadow: 0 0 20px rgba(212,175,55,0.35);
        }
        .topbar .btn-nav.primary {
            background: linear-gradient(180deg, #d4af37, #b8860b);
            color: #1a1200;
            border-color: #f0c94a;
            box-shadow: 0 2px 10px rgba(212,175,55,0.4);
        }
        .topbar .btn-nav.primary:hover {
            filter: brightness(1.1);
            color: #1a1200;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212,175,55,0.55);
        }
        .topbar .btn-nav.gm {
            border-color: rgba(212,175,55,0.35);
            color: #f0c94a;
        }
        .topbar .user-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #9db4d8;
            font-size: 0.82rem;
            padding: 6px 12px;
            border-left: 1px solid rgba(255,255,255,0.12);
            margin-left: 6px;
        }
        .topbar .user-tag strong { color: #f0c94a; }
        .topbar .badge-gm {
            display: inline-block;
            background: rgba(240,201,74,0.2);
            border: 1px solid #f0c94a;
            color: #f0c94a;
            font-size: 0.58rem;
            padding: 1px 6px;
            border-radius: 8px;
            font-weight: 700;
            margin-left: 4px;
            letter-spacing: 1px;
        }
        .topbar .btn-logout {
            color: #ff9aa4 !important;
            padding: 9px 12px !important;
        }
        .topbar .btn-logout:hover {
            background: rgba(220,53,69,0.15) !important;
            border-color: rgba(220,53,69,0.4) !important;
            color: #ff9aa4 !important;
        }

        .contenedor {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .caja {
            position: relative;
            z-index: 1;
            background: rgba(8, 20, 50, 0.88);
            border: 2px solid #d4af37;
            border-radius: 18px;
            padding: 40px 36px;
            width: 100%;
            max-width: <?= $logueado ? '960' : '460' ?>px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.65), 0 0 60px rgba(30,77,156,0.45);
            animation: fadeIn 0.8s ease-out both;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .subtitulo { color: #9db4d8; text-align: center; font-size: 13px; margin-bottom: 20px; margin-top: 8px; }
        .server-status { display: flex; justify-content: center; align-items: center; gap: 12px; flex-wrap: wrap; padding: 11px 18px; margin-bottom: 16px; border-radius: 9px; font-size: 14px; font-weight: 700; border: 2px solid transparent; }
        .server-status.online { background: rgba(40, 167, 69, 0.18); color: #8ff0a0; border-color: #28a745; }
        .server-status.offline { background: rgba(220, 53, 69, 0.18); color: #ff9aa4; border-color: #dc3545; }
        .status-text { font-size: 14px; }
        .uptime-text { font-size: 12px; font-weight: 600; padding-left: 12px; border-left: 2px solid rgba(255,255,255,0.2); color: #e9dca0; font-variant-numeric: tabular-nums; }
        .faction-counts { display: flex; gap: 12px; margin-bottom: 25px; }
        .faction { flex: 1; display: flex; align-items: center; justify-content: center; gap: 9px; padding: 11px 8px; border-radius: 9px; background: rgba(0,10,30,0.6); border: 1.5px solid rgba(212,175,55,0.3); }
        .faction.alliance { border-color: rgba(0,112,221,0.6); }
        .faction.horde { border-color: rgba(204,0,0,0.6); }
        .faction-circle { display: inline-block; width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; }
        .faction-circle.blue { background: radial-gradient(circle at 35% 30%, #4da3ff, #0066cc 70%); box-shadow: 0 0 10px rgba(0,112,221,0.7); }
        .faction-circle.red { background: radial-gradient(circle at 35% 30%, #ff5c5c, #b30000 70%); box-shadow: 0 0 10px rgba(204,0,0,0.7); }
        .faction-name { color: #c9d8f0; font-size: 13px; font-weight: 600; }
        .faction-number { color: #f0c94a; font-size: 18px; font-weight: 700; }

        label { display: block; color: #c9d8f0; font-size: 13px; margin-bottom: 6px; font-weight: 600; }
        input, select, textarea { width: 100%; padding: 12px 14px; margin-bottom: 18px; background: rgba(0,10,30,0.65); border: 1.5px solid rgba(212,175,55,0.45); border-radius: 9px; color: #fff; font-size: 14px; font-family: inherit; }
        textarea { min-height: 120px; resize: vertical; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #f0c94a; }
        button { width: 100%; padding: 13px; background: linear-gradient(135deg, #12408f 0%, #1e5bc6 100%); border: 2px solid #d4af37; border-radius: 9px; color: #f0c94a; font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 5px; font-family: inherit; transition: 0.2s; }
        button:hover { background: linear-gradient(135deg, #1a52b0 0%, #2a6fe0 100%); transform: translateY(-2px); box-shadow: 0 6px 22px rgba(212,175,55,0.35); }
        button:disabled { opacity: 0.5; cursor: not-allowed; }
        .alerta { padding: 12px 14px; border-radius: 9px; font-size: 13px; margin-bottom: 20px; text-align: center; word-break: break-word; }
        .error { background: rgba(220,53,69,0.22); border: 1.5px solid rgba(220,53,69,0.55); color: #ff9aa4; }
        .exito { background: rgba(40,167,69,0.22); border: 1.5px solid rgba(212,175,55,0.55); color: #8ff0a0; }
        .info { color: #9dafd0; font-size: 11px; text-align: center; margin-top: 20px; line-height: 1.6; }
        .tabs { display: flex; gap: 10px; margin-bottom: 25px; }
        .tab { flex: 1; text-align: center; padding: 10px; border: 1.5px solid rgba(212,175,55,0.4); border-radius: 8px; color: #c9d8f0; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .tab.activo { background: rgba(212,175,55,0.15); border-color: #f0c94a; color: #f0c94a; }
        .tab:hover { border-color: #f0c94a; }
        .tab-content { display: none; }
        .tab-content.activo { display: block; }
        .bienvenida { text-align: center; margin-bottom: 20px; color: #c9d8f0; font-size: 14px; }
        .bienvenida strong { color: #f0c94a; }
        .nav-botones { display: none; }
        .tienda-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 18px; margin-bottom: 25px; }
        .producto-card { background: rgba(0,10,30,0.6); border: 1.5px solid rgba(212,175,55,0.4); border-radius: 12px; padding: 20px; text-align: center; }
        .producto-nombre { color: #f0c94a; font-size: 16px; font-weight: 700; margin-bottom: 8px; }
        .producto-precio { color: #c9d8f0; font-size: 14px; margin-bottom: 12px; }
        .producto-precio span { color: #f0c94a; font-weight: 700; }
        .producto-form { margin-top: 10px; }
        .producto-form select { margin-bottom: 10px; font-size: 13px; padding: 10px; }
        .producto-form button { padding: 10px; font-size: 13px; }
        .noticias-lista { display: flex; flex-direction: column; gap: 18px; }
        .noticia { background: rgba(0,10,30,0.6); border: 1.5px solid rgba(212,175,55,0.4); border-radius: 12px; padding: 20px 22px; }
        .noticia-titulo { color: #f0c94a; font-size: 17px; font-weight: 800; margin-bottom: 6px; }
        .noticia-meta { display: flex; gap: 16px; font-size: 11px; color: #9db4d8; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid rgba(212,175,55,0.2); }
        .noticia-meta span::before { margin-right: 5px; color: #d4af37; }
        .meta-fecha::before { content: '📅'; }
        .meta-autor::before { content: '👤'; }
        .noticia-contenido { color: #c9d8f0; font-size: 13px; line-height: 1.7; white-space: pre-line; }
        .vacio { text-align: center; color: #9db4d8; padding: 40px 20px; font-size: 14px; }
        .vacio .icono { font-size: 40px; display: block; margin-bottom: 12px; }
        .cerrar-sesion { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid rgba(212,175,55,0.25); }
        .cerrar-sesion a { color: #ff9aa4; font-size: 12px; text-decoration: none; }
        .cerrar-sesion a:hover { text-decoration: underline; }
        .panel-gm { margin-bottom: 25px; padding-bottom: 20px; border-bottom: 2px solid rgba(212,175,55,0.3); display: none; }
        .admin-section { margin-bottom: 30px; padding: 20px; background: rgba(0,10,30,0.4); border: 1px solid rgba(212,175,55,0.3); border-radius: 12px; }
        .admin-section h3 { color: #f0c94a; font-size: 16px; margin-bottom: 15px; text-align: center; }
        .admin-tabla { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        .admin-tabla th { background: rgba(212,175,55,0.15); color: #f0c94a; padding: 10px; text-align: left; border-bottom: 2px solid rgba(212,175,55,0.4); }
        .admin-tabla td { padding: 8px 10px; color: #c9d8f0; border-bottom: 1px solid rgba(212,175,55,0.15); }
        .admin-tabla tr:hover td { background: rgba(212,175,55,0.05); }
        .btn-borrar { padding: 5px 10px; background: rgba(220,53,69,0.3); border: 1px solid #dc3545; color: #ff9aa4; border-radius: 5px; cursor: pointer; font-size: 11px; width: auto; margin: 0; }
        .btn-borrar:hover { background: rgba(220,53,69,0.5); transform: none; box-shadow: none; }
        .checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 18px; }
        .checkbox-row input[type="checkbox"] { width: auto; margin: 0; }
        .checkbox-row label { margin: 0; }
        .oro-badge { text-align: center; color: #f0c94a; font-size: 14px; font-weight: 700; margin-bottom: 20px; padding: 10px; background: rgba(240,201,74,0.1); border-radius: 8px; border: 1px solid rgba(212,175,55,0.3); line-height: 1.8; }
        .oro-badge small { color: #9db4d8; font-size: 11px; font-weight: 400; }

        footer {
            border-top: 1px solid rgba(212,175,55,0.2);
            padding: 24px;
            text-align: center;
            color: #9db4d8;
            font-size: 0.82rem;
            background: rgba(8,18,40,0.6);
            margin-top: 40px;
        }

        @media (max-width: 700px) {
            body { background-attachment: scroll; }
            .topbar .nav { padding: 12px 16px; gap: 10px; }
            .topbar .brand-group { width: 100%; justify-content: center; }
            .topbar .brand { font-size: 0.9rem; letter-spacing: 1.5px; }
            .topbar .menu { width: 100%; justify-content: center; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 10px; margin-top: 4px; }
            .topbar .btn-nav { font-size: 0.78rem; padding: 7px 11px; }
            .topbar .user-tag { display: none; }
            .contenedor { padding: 20px 12px; }
            .caja { padding: 30px 22px; max-width: 100% !important; }
            .tienda-grid { grid-template-columns: 1fr; }
            .admin-tabla { font-size: 11px; }
        }
    </style>
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
                <a href="#" class="btn-nav" onclick="mostrarSeccion('tienda'); return false;"><span class="ico">🛒</span> <?= t('shop') ?></a>
                <a href="#" class="btn-nav" onclick="mostrarSeccion('noticias'); return false;"><span class="ico">📰</span> <?= t('news') ?></a>
                <a href="guias.php" class="btn-nav"><span class="ico">📖</span> Guías</a>
                <?php if ($es_admin): ?>
                    <a href="#" class="btn-nav gm" onclick="mostrarSeccion('gm'); return false;"><span class="ico">🛡️</span> <?= t('gm_panel') ?></a>
                <?php endif; ?>
                <span class="user-tag">
                    <span class="ico">👤</span> <strong><?= htmlspecialchars($web_user) ?></strong>
                    <?php if ($es_admin): ?><span class="badge-gm">GM</span><?php endif; ?>
                </span>
                <a href="index.php?logout=1" class="btn-nav btn-logout" title="Cerrar sesión">⏻</a>
            <?php else: ?>
                <a href="#" class="btn-nav outline" onclick="cambiarTab('login', document.querySelector('.tab')); return false;">→ <?= t('login_tab') ?></a>
                <a href="#" class="btn-nav primary" onclick="cambiarTab('registro', document.querySelectorAll('.tab')[1]); return false;">+ <?= t('register_tab') ?></a>
            <?php endif; ?>
        </div>
    </nav>
</div>

<div class="contenedor">
    <div class="caja">

        <?php if ($logueado): ?>
            <div class="bienvenida">
                <?= t('welcome') ?>, <strong><?= htmlspecialchars($web_user) ?></strong>
            </div>

            <?php if (!empty($personajes)): ?>
                <div class="oro-badge">
                    <?= t('your_gold') ?><br>
                    <?php foreach ($personajes as $pj): ?>
                        <small><?= htmlspecialchars($pj['name']) ?>: <strong><?= number_format(floor($pj['money'] / COBRE_POR_ORO)) ?> <?= t('gold') ?></strong></small><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($mensaje): ?>
                <div class="alerta <?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>

            <?php if ($es_admin): ?>
            <div id="seccion-gm" class="panel-gm">
                <div class="admin-section">
                    <h3><?= t('add_item') ?></h3>
                    <form method="POST">
                        <label for="item_entry"><?= t('item_id') ?></label>
                        <input type="number" id="item_entry" name="item_entry" placeholder="Ej: 50818" required min="1">
                        <label for="item_nombre"><?= t('item_name') ?></label>
                        <input type="text" id="item_nombre" name="item_nombre" placeholder="Ej: Riendas de Invencible" required maxlength="100">
                        <label for="item_precio"><?= t('item_price') ?></label>
                        <input type="number" id="item_precio" name="item_precio" placeholder="Ej: 1000" required min="0" step="0.01">
                        <div class="checkbox-row">
                            <input type="checkbox" id="item_activo" name="item_activo" checked>
                            <label for="item_activo"><?= t('item_active') ?></label>
                        </div>
                        <button type="submit" name="add_shop_item"><?= t('add_btn') ?></button>
                    </form>
                </div>

                <div class="admin-section">
                    <h3><?= t('manage_items') ?></h3>
                    <?php if (empty($productos_admin)): ?>
                        <p style="color:#9db4d8; text-align:center; font-size:13px;"><?= t('no_items_shop') ?></p>
                    <?php else: ?>
                        <table class="admin-tabla">
                            <thead>
                                <tr><th>ID</th><th>Item</th><th><?= t('item_name') ?></th><th><?= t('price') ?></th><th><?= t('active') ?></th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos_admin as $p): ?>
                                    <tr>
                                        <td><?= $p['id'] ?></td>
                                        <td><?= $p['item_entry'] ?></td>
                                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                                        <td><?= number_format($p['precio'] / COBRE_POR_ORO) ?> <?= t('gold') ?></td>
                                        <td><?= $p['activo'] ? '✅' : '❌' ?></td>
                                        <td>
                                            <form method="POST" style="margin:0;" onsubmit="return confirm('<?= t('confirm_delete') ?>');">
                                                <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                                                <button type="submit" name="delete_shop_item" class="btn-borrar"><?= t('delete') ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

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
            </div>
            <?php endif; ?>

            <div id="seccion-tienda">
                <?php if (empty($productos_tienda)): ?>
                    <div class="vacio"><span class="icono">🛒</span><?= t('shop_empty') ?></div>
                <?php else: ?>
                    <div class="tienda-grid">
                        <?php foreach ($productos_tienda as $prod): ?>
                            <div class="producto-card">
                                <div class="producto-nombre"><?= htmlspecialchars($prod['nombre']) ?></div>
                                <div class="producto-precio"><?= t('price') ?>: <span><?= number_format($prod['precio'] / COBRE_POR_ORO) ?> <?= t('gold') ?></span></div>
                                <form method="POST" class="producto-form">
                                    <input type="hidden" name="item_id" value="<?= $prod['id'] ?>">
                                    <?php if (empty($personajes)): ?>
                                        <p style="color:#ff9aa4; font-size:12px; margin-bottom:10px;"><?= t('no_chars') ?></p>
                                        <button type="button" disabled><?= t('buy') ?></button>
                                    <?php else: ?>
                                        <select name="char_guid" required>
                                            <option value=""><?= t('select_char') ?></option>
                                            <?php foreach ($personajes as $pj): ?>
                                                <option value="<?= $pj['guid'] ?>">
                                                    <?= htmlspecialchars($pj['name']) ?> (<?= t('level') ?> <?= $pj['level'] ?> — <?= number_format(floor($pj['money'] / COBRE_POR_ORO)) ?> <?= t('gold') ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="comprar_item"><?= t('buy') ?></button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div id="seccion-noticias" style="display:none;">
                <?php if (empty($noticias_mostrar)): ?>
                    <div class="vacio"><span class="icono">📭</span><?= t('no_news') ?></div>
                <?php else: ?>
                    <div class="noticias-lista">
                        <?php foreach ($noticias_mostrar as $n): ?>
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

<script>
    function cambiarTab(tab, el) {
        document.querySelectorAll('.tab').forEach(function(t) { t.classList.remove('activo'); });
        document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('activo'); });
        el.classList.add('activo');
        document.getElementById('tab-' + tab).classList.add('activo');
    }
    function mostrarSeccion(sec) {
        ['tienda', 'noticias', 'gm'].forEach(function(s) {
            var e = document.getElementById('seccion-' + s);
            if (e) e.style.display = (s === sec) ? 'block' : 'none';
        });
    }
    (function() {
        var el = document.getElementById('uptime-display');
        if (!el) return;
        var segundos = parseInt(el.getAttribute('data-segundos'), 10);
        if (isNaN(segundos) || segundos < 0) return;
        function formatear(s) {
            var d = Math.floor(s / 86400); var h = Math.floor((s % 86400) / 3600); var m = Math.floor((s % 3600) / 60); var sec = s % 60;
            if (d > 0) return d + 'd ' + h + 'h ' + m + 'm';
            if (h > 0) return h + 'h ' + m + 'm';
            if (m > 0) return m + 'm ' + sec + 's';
            return sec + 's';
        }
        setInterval(function() { segundos++; el.textContent = '⏱️ ' + formatear(segundos); }, 1000);
    })();
</script>
</body>
</html>
