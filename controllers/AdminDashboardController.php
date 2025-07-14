<?php
class AdminDashboardController {
    public function index() {
        require_once __DIR__ . '/../views/admin/admin_dashboard.php'; // o donde esté tu HTML
    }
    public function aprobaciones() {
        require_once __DIR__ . '/../views/admin/aprobaciones.php';
    }

    public function soporte() {
        require_once __DIR__ . '/../views/admin/consultas_soporte.php';
    }
    public function solicitudCuentas(){
        require_once __DIR__ . '/../views/admin/solicitudes_cuentas.php';
    }
    public function solicitudLocales() {
        require_once __DIR__ . '/../views/admin/solicitudes_creacion_locales.php';
    }
    public function aprobarPromos() {
        require_once __DIR__ . '/../views/admin/solicitudes_promociones.php';
    }
}
?>