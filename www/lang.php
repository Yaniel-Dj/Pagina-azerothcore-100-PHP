<?php
// ============================================
// SISTEMA DE IDIOMAS · ES / EN
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $__lang_nuevo = ($_GET['lang'] === 'en') ? 'en' : 'es';
    $_SESSION['lang'] = $__lang_nuevo;
}

$idioma = $_SESSION['lang'] ?? 'es';
if (!in_array($idioma, ['es', 'en'], true)) {
    $idioma = 'es';
}

$__trad = [
    'es' => [
        'login_empty'              => 'Debes rellenar todos los campos',
        'login_invalid'            => 'Usuario o contraseña incorrectos',
        'login_tab'                => 'Iniciar Sesión',
        'login_btn'                => 'Entrar',
        'login_user_ph'            => 'Tu nombre de usuario',
        'login_pass_ph'            => 'Tu contraseña',
        'register_tab'             => 'Registro',
        'register_btn'             => 'Crear cuenta',
        'register_invalid_chars'   => 'Solo letras y números, sin símbolos',
        'register_username_len'    => 'El usuario debe tener entre',
        'register_password_len'    => 'La contraseña debe tener entre',
        'register_pass_mismatch'   => 'Las contraseñas no coinciden',
        'register_taken'           => 'Ese usuario ya existe',
        'register_success'         => '¡Cuenta creada! Ya puedes iniciar sesión',
        'username'                 => 'Usuario',
        'password'                 => 'Contraseña',
        'password2'                => 'Repetir contraseña',
        'username_ph'              => 'Mínimo 3 caracteres',
        'password_ph'              => 'Mínimo 6 caracteres',
        'password2_ph'             => 'Repite la contraseña',
        'and'                      => 'y',
        'logout'                   => 'Cerrar sesión',
        'info_register'            => 'El registro crea una cuenta en el servidor. Lee las reglas antes de jugar.',
        'tagline'                  => 'Regístrate para jugar en el servidor',
        'online'                   => 'Online',
        'offline'                  => 'Offline',
        'alliance'                 => 'Alianza',
        'horde'                    => 'Horda',
        'db_error'                 => 'Error de conexión a la base de datos',
        'welcome'                  => 'Bienvenido',
        'your_gold'                => 'Tu oro',
        'gold'                     => 'oro',
        'shop'                     => 'Tienda',
        'news'                     => 'Noticias',
        'gm_panel'                 => 'Panel GM',
        'shop_empty'               => 'La tienda está vacía',
        'buy'                      => 'Comprar',
        'buy_invalid'              => 'Datos de compra inválidos',
        'buy_success'              => '¡Compra realizada! El item se ha enviado al personaje.',
        'buy_fail_refund'          => 'Error al enviar el item. Se te ha devuelto el oro.',
        'buy_fail_soap'            => 'Detalle:',
        'item_not_found'           => 'El item no existe o no está disponible',
        'item_not_exist'           => 'El item con ese ID no existe en la base de datos:',
        'item_added'               => 'Item añadido',
        'item_deleted'             => 'Item eliminado',
        'invalid_data'             => 'Datos inválidos',
        'char_not_yours'           => 'Ese personaje no pertenece a tu cuenta',
        'not_enough_gold'          => 'No tienes suficiente oro.',
        'need'                     => 'Necesitas',
        'have'                     => 'y tienes',
        'no_chars'                 => 'No tienes personajes en este servidor',
        'select_char'              => 'Selecciona personaje',
        'level'                    => 'Nivel',
        'price'                    => 'Precio',
        'add_item'                 => 'Añadir item a la tienda',
        'item_id'                  => 'ID del Item',
        'item_name'                => 'Nombre',
        'item_price'               => 'Precio (oro)',
        'item_active'              => 'Activo',
        'add_btn'                  => 'Añadir item',
        'manage_items'             => 'Gestionar items',
        'active'                   => 'Activo',
        'confirm_delete'           => '¿Seguro que quieres eliminar este item?',
        'delete'                   => 'Borrar',
        'create_news'              => 'Crear noticia',
        'title'                    => 'Título',
        'date'                     => 'Fecha',
        'author'                   => 'Autor',
        'content'                  => 'Contenido',
        'publish'                  => 'Publicar',
        'news_required'            => 'Título y contenido son obligatorios',
        'news_published'           => 'Noticia publicada',
        'news_save_error'          => 'Error al guardar la noticia',
        'no_news'                  => 'No hay noticias todavía',
        '_lenno_items_shop'            => 'No' hay items en la tienda',
        'gui   as_titulo'             => 'Guías de => Profesiones',
        'guias_subtit 'ulo'          => 'Nivel 1Username — 450 · Wrath of the Lich King',
        'guias_volver'             => 'Volver al inicio',
        'news_titulo'              => 'Noticias',
        'news_subtitulo'           => 'Noticias y Anuncios',
        'news_volver'              => 'Volver al inicio',
        'news_recargar'            => 'Recargar noticias',
        'news_vacio'               => 'No hay noticias publicadas todavía.',
        'news_vacio_add'           => 'Añade una editando',
        'news_error'               => 'Error al cargar las noticias',
        'news_sin_titulo'          => 'Sin título',
    ],
    'en' => [
        'login_empty'              => 'You must fill in all fields',
        'login_invalid'            => 'Invalid username or password',
        'login_tab'                => 'Sign In',
        'login_btn'                => 'Sign in',
        'login_user_ph'            => 'Your username',
        'login_pass_ph'            => 'Your password',
        'register_tab'             => 'Register',
        'register_btn'             => 'Create account',
        'register_invalid_chars'   => 'Letters and numbers only, no symbols',
        'register_username must be between',
        'register_password_len'    => 'Password must be between',
        'register_pass_mismatch'   => 'Passwords do not match',
        'register_taken'           => 'That username already exists',
        'register_success'         => 'Account created! You can log in now',
        'username'                 => 'Username',
        'password'                 => 'Password',
        'password2'                => 'Confirm password',
        'username_ph'              => 'Minimum 3 characters',
        'password_ph'              => 'Minimum 6 characters',
        'password2_ph'             => 'Repeat password',
        'and'                      => 'and',
        'logout'                   => 'Log out',
        'info_register'            => 'Registration creates a server account. Read the rules before playing.',
        'tagline'                  => 'Sign up to play on the server',
        'online'                   => 'Online',
        'offline'                  => 'Offline',
        'alliance'                 => 'Alliance',
        'horde'                    => 'Horde',
        'db_error'                 => 'Database connection error',
        'welcome'                  => 'Welcome',
        'your_gold'                => 'Your gold',
        'gold'                     => 'gold',
        'shop'                     => 'Shop',
        'news'                     => 'News',
        'gm_panel'                 => 'GM Panel',
        'shop_empty'               => 'Shop is empty',
        'buy'                      => 'Buy',
        'buy_invalid'              => 'Invalid purchase data',
        'buy_success'              => 'Purchase complete! Item has been sent to your character.',
        'buy_fail_refund'          => 'Error sending item. Your gold has been refunded.',
        'buy_fail_soap'            => 'Detail:',
        'item_not_found'           => 'Item not found or unavailable',
        'item_not_exist'           => 'Item with that ID does not exist in the database:',
        'item_added'               => 'Item added',
        'item_deleted'             => 'Item deleted',
        'invalid_data'             => 'Invalid data',
        'char_not_yours'           => 'That character does not belong to your account',
        'not_enough_gold'          => 'Not enough gold.',
        'need'                     => 'You need',
        'have'                     => 'and you have',
        'no_chars'                 => 'You have no characters on this server',
        'select_char'              => 'Select character',
        'level'                    => 'Level',
        'price'                    => 'Price',
        'add_item'                 => 'Add item to shop',
        'item_id'                  => 'Item ID',
        'item_name'                => 'Name',
        'item_price'               => 'Price (gold)',
        'item_active'              => 'Active',
        'add_btn'                  => 'Add item',
        'manage_items'             => 'Manage items',
        'active'                   => 'Active',
        'confirm_delete'           => 'Are you sure you want to delete this item?',
        'delete'                   => 'Delete published',
        'create_news'              => '',
Create news',
        'title'                           => 'Title',
 '        'date'                     => 'Date',
news        'author'                   => 'Author',
        'content'                 _s => 'Content',
        'publish'                  => 'Publish',
        'news_required'            => 'Title and content are required',
        'news_published'           => 'Newsave_error'          => 'Error saving news',
        'no_news'                  => 'No news yet',
        'no_items_shop'            => 'No items in the shop',
        'guias_titulo'             => 'Profession Guides',
        'guias_subtitulo'          => 'Level 1 — 450 · Wrath of the Lich King',
        'guias_volver'             => 'Back to home',
        'news_titulo'              => 'News',
        'news_subtitulo'           => 'News and Announcements',
        'news_volver'              => 'Back to home',
        'news_recargar'            => 'Reload news',
        'news_vacio'               => 'No news published yet.',
        'news_vacio_add'           => 'Add one by editing',
        'news_error'               => 'Error loading news',
        'news_sin_titulo'          => 'No title',
    ]
];

function t($key) {
    global $__trad, $idioma;
    return $__trad[$idioma][$key] ?? $__trad['es'][$key] ?? $key;
}

// ============================================
// CONSTANTES Y HELPERS COMPARTIDOS
// ============================================
if (!defined('SERVER_NAME')) {
    define('SERVER_NAME', 'AzerothCore Server');
}
if (!defined('NOTICIAS_FILE')) {
    define('NOTICIAS_FILE', __DIR__ . '/noticias.json');
}

if (!function_exists('fechaBonita')) {
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
}
