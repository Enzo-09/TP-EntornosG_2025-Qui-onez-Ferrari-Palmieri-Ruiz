<?php
require_once 'core/Router.php';

$router = new Router();

// ===== RUTAS BÁSICAS =====
$router->get('', 'Home@index');           // Página principal
$router->get('home', 'Home@index');       // Página principal alternativa

// ===== RUTAS DE AUTENTICACIÓN =====
$router->get('register', 'Register@showRegister');
$router->post('register', 'Register@processRegister');

$router->get('verify', 'Verify@show');
$router->post('verify', 'Verify@check');
$router->post('verify/resend', 'Verify@resend');

$router->get('login', 'Login@showLogin');
$router->post('login', 'Login@processLogin');

$router->get('logout', 'Auth@logout');      // Cerrar sesión

// ===== RUTAS ADMIN ===========
$router->get('admin/dashboard', 'AdminDashboard@index');
$router->get('admin/aprobaciones', 'AdminDashboard@aprobaciones');
$router->get('admin/consultas-soporte', 'AdminDashboard@soporte');
$router->get('admin/aprobaciones-cuenta', 'AdminDashboard@solicitudCuentas');
$router->post('admin/aprobaciones-cuenta', 'AdminDashboard@solicitudCuentas');
$router->get('admin/solicitudes-locales', 'AdminDashboard@solicitudLocales');
$router->post('admin/solicitudes-locales', 'AdminDashboard@solicitudLocales');
$router->get('admin/aprobaciones-promociones', 'AdminDashboard@aprobarPromos');//FALTA
$router->post('admin/aprobaciones-promociones', 'AdminDashboard@aprobarPromos');//FALTA
// ===== RUTAS DUEÑO ===========
$router->get('duenio/dashboard','DuenioDashboard@index');
$router->get('duenio/panellocal', 'DuenioDashboard@panellocal');
$router->post('duenio/panellocal', 'DuenioDashboard@panellocal');
$router->get('duenio/misPromosLocal', 'DuenioDashboard@misPromosLocal');//FALTA
$router->get('duenio/mis-promociones/crear', 'DuenioDashboard@crearPromoLocal');//FALTA
$router->post('duenio/mis-promociones/crear', 'DuenioDashboard@crearPromoLocal');//FALTA




// Ejecutar el router
$router->dispatch();