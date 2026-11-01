<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';

if (empty($_SESSION['web_logueado'])) {
    header('Location: index.php');
    exit;
}

$web_user = $_SESSION['web_username'] ?? '';
$web_id   = $_SESSION['web_user_id'] ?? 0;

$mensaje = '';
$tipo = '';

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
// COMPRAR ITEM
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comprar_item'])) {
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
                    $stmt_compra = $pdo_shop->prepare("INSERT INTO web_shop_purchases (account_id, character_guid, item_entry, cantidad, precio_pagado, estado) VALUES (?, ?, ?, 1, ?, 'pendiente')");
                    $stmt_compra->execute([$web_id, $char_guid, $item['item_entry'], $item['precio']]);
                    $compra_id = $pdo_shop->lastInsertId();
                    $stmt_restar = $pdo_char->prepare("UPDATE characters SET money = money - ? WHERE guid = ?");
                    $stmt_restar->execute([$item['precio'], $char_guid]);
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
                        $mensaje = t('buy_fail_refund') . ' ' . t('buy_fail_soap') . ' ' . $detalle; $tipo = 'error';
                    }
                }
            }
        } catch (PDOException $e) {
            $mensaje = t('db_error') . ': ' . $e->getMessage(); $tipo = 'error';
        }
    }
}

// ============================================
// CARGAR DATOS
// ============================================
$productos_tienda = [];
$personajes = [];

try {
    $pdo_shop = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . SHOP_DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo_shop->query("SELECT * FROM web_shop_items WHERE activo = 1");
    $productos_tienda = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

try {
    $pdo_char = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . CHAR_DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt_char = $pdo_char->prepare("SELECT guid, name, level, money FROM characters WHERE account = ? ORDER BY level DESC");
    $stmt_char->execute([$web_id]);
    $personajes = $stmt_char->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(SERVER_NAME) ?> - <?= t('shop') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/tienda.css">
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
            <a href="index.php" class="btn-nav">← <?= t('news_volver') ?></a>
            <a href="noticias.php" class="btn-nav">📰 <?= t('news_titulo') ?></a>
            <a href="guias.php" class="btn-nav">📖 <?= t('guias_titulo') ?></a>
            <span class="user-tag">
                <span class="ico">👤</span> <strong><?= htmlspecialchars($web_user) ?></strong>
            </span>
            <a href="index.php?logout=1" class="btn-nav btn-logout" title="<?= t('logout') ?>"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </nav>
</div>

<div class="contenedor">
    <div class="caja caja-logueado">

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
</div>

</body>
</html>