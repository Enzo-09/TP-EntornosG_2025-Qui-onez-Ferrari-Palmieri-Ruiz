<?php
class DuenioDashboardController {
    public function index() {
        require_once __DIR__ . '/../views/duenio/duenio_dashboard.php'; // o donde esté tu HTML
    }
    public function solicitudes() {
        require_once __DIR__ . '/../views/duenio/dos.php';
    }
    public function panellocal() {
        require_once __DIR__ . '/../views/duenio/panel_local.php';
    }
    public function consultas() {
        require_once __DIR__ . '/../views/duenio/consultas_soporte.php';
    }
    public function misPromosLocal(){
        require_once __DIR__ . '/../views/duenio/misPromosLoc.php';
    }
    public function crearPromoLocal() {
        require_once __DIR__ . '/../views/duenio/crearPromociones.php';
    }
}
?>