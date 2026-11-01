<?php
// Incluido desde admin/index.php
// Ya tiene $idioma, $web_user, config, lang, etc.

$mensaje = '';
$tipo = '';

// ============================================
// AÑADIR ITEM
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_shop_item'])) {
    $item_entry = (int)($_POST['item_entry'] ?? 0);
    $nombre     = trim($_POST['item_nombre'] ?? '');
    $precio_oro = (float)($_POST['item_precio'] ?? 0);
    $activo     = isset($_POST['item_activo']) ? 1 : 0;

    if ($item_entry <= 0 || empty($nombre) || $precio_oro <= 0) {
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
// BORRAR ITEM
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_shop_item'])) {
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
// ACTIVAR / DESACTIVAR ITEM
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_shop_item'])) {
    $tog_id = (int)($_POST['toggle_id'] ?? 0);
    if ($tog_id > 0) {
        try {
            $pdo_shop = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . SHOP_DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $stmt = $pdo_shop->prepare("UPDATE web_shop_items SET activo = 1 - activo WHERE id = ?");
            $stmt->execute([$tog_id]);
        } catch (PDOException $e) {
            $mensaje = t('db_error') . ': ' . $e->getMessage(); $tipo = 'error';
        }
    }
}

// ============================================
// CARGAR ITEMS
// ============================================
$productos_admin = [];
try {
    $pdo_shop = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . SHOP_DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt_all = $pdo_shop->query("SELECT * FROM web_shop_items ORDER BY id DESC");
    $productos_admin = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>

<?php if ($mensaje): ?>
    <div class="alerta <?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<div class="admin-section">
    <h3><?= t('add_item') ?></h3>
    <form method="POST">
        <label for="item_entry"><?= t('item_id') ?></label>
        <input type="number" id="item_entry" name="item_entry" placeholder="Ej: 50818" required min="1">
        <label for="item_nombre"><?= t('item_name') ?></label>
        <input type="text" id="item_nombre" name="item_nombre" placeholder="Ej: Riendas de Invencible" required maxlength="100">
        <label for="item_precio"><?= t('item_price') ?></label>
        <input type="number" id="item_precio" name="item_precio" placeholder="Ej: 1000" required min="1" step="0.01">
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
                        <td>
                            <form method="POST" style="margin:0; display:inline;">
                                <input type="hidden" name="toggle_id" value="<?= $p['id'] ?>">
                                <button type="submit" name="toggle_shop_item" class="btn-borrar" style="opacity:.85;" title="Activar/Desactivar">
                                    <?= $p['activo'] ? '✅' : '❌' ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" style="margin:0;" onsubmit="return confirm(<?= json_encode(t('confirm_delete')) ?>);">
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