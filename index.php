
<?php
// Iniciar sesión
session_start();

// Configuración básica
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar y ejecutar rutas
require_once __DIR__ . '/vendor/autoload.php';
require_once 'routes.php';
