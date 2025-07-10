<?php
// core/Router.php - Versión Simple
class Router {
    private $routes = [];
    
    // Agregar ruta GET
    public function get($uri, $action) {
        $this->routes['GET'][$uri] = $action;
    }
    
    // Agregar ruta POST  
    public function post($uri, $action) {
        $this->routes['POST'][$uri] = $action;
    }
    
    // Procesar la ruta actual
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getCurrentUri();
        
        // Buscar la ruta
        if (isset($this->routes[$method][$uri])) {
            $action = $this->routes[$method][$uri];
            $this->executeAction($action);
        } else {
            $this->show404();
        }
    }
    
    // Obtener URI actual
    private function getCurrentUri() {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remover query string (?param=value)
        if (strpos($uri, '?') !== false) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        
        // Limpiar barras
        return trim($uri, '/');
    }
    
    // Ejecutar la acción
    private function executeAction($action) {
        if (strpos($action, '@') !== false) {
            // Formato: "Controller@method"
            list($controller, $method) = explode('@', $action);
            $this->callController($controller, $method);
        } else {
            // Es una función
            call_user_func($action);
        }
    }
    
    // Llamar controlador
    private function callController($controller, $method) {
        $controllerFile = __DIR__ . "/../controllers/{$controller}Controller.php";
        
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $controllerClass = $controller . 'Controller';
            $instance = new $controllerClass();
            
            if (method_exists($instance, $method)) {
                $instance->$method();
            } else {
                die("Método {$method} no encontrado en {$controllerClass}");
            }
        } else {
            die("Controlador {$controller} no encontrado");
        }
    }
    
    // Página 404
    private function show404() {
        http_response_code(404);
        echo "<h1>404 - Página no encontrada</h1>";
    }
    
    // Redirigir
    public static function redirect($url) {
        header("Location: /" . ltrim($url, '/'));
        exit;
    }
}
