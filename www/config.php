<?php
// ============================================
// CONFIGURACIÓN GLOBAL
// ============================================

// Base de datos
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', 'admin');
define('DB_NAME', 'acore_auth');
define('CHAR_DB_NAME', 'acore_characters');
define('WORLD_DB_NAME', 'acore_world');
define('SHOP_DB_NAME', 'acore_shop');

// Reglas de registro
define('MIN_USERNAME_LENGTH', 3);
define('MAX_USERNAME_LENGTH', 16);
define('MIN_PASSWORD_LENGTH', 6);
define('MAX_PASSWORD_LENGTH', 16);
define('EXPANSION', 2);

// Servidor
define('SERVER_IP', '127.0.0.1');
define('SERVER_PORT', 8085);
define('SERVER_TIMEOUT', 1);

// Rutas / marca
define('BG_IMAGE', 'img/fondo.jpg');
define('SERVER_NAME',    'AzerothCore Server');
define('SERVER_TAGLINE', 'Regístrate para jugar en el servidor');
define('NOTICIAS_FILE', __DIR__ . '/noticias.json');

// SOAP
define('SOAP_HOST', '127.0.0.1');
define('SOAP_PORT', 7878);
define('SOAP_URI', 'urn:AC');
define('SOAP_GM_USER', 'YANIEL');
define('SOAP_GM_PASS', 'yaniel1230');

// Economía
define('COBRE_POR_ORO', 10000);
