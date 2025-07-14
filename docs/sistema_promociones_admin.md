# Sistema de Gestión de Promociones - Admin

## Descripción
Este sistema permite a los administradores gestionar las solicitudes de promociones enviadas por los dueños de locales.

## Características

### Visualización de Promociones Pendientes
- Muestra todas las promociones con estado 'PENDIENTE'
- Información completa de cada promoción:
  - Nombre de la promoción
  - Descripción
  - Descuento
  - Tipo de promoción
  - Fechas de inicio y fin
  - Condiciones (opcional)
  - Información del local y dueño
  - Fecha de solicitud

### Acciones Disponibles

#### Aprobar Promoción
- Cambia el estado de la promoción a 'ACTIVA'
- Solicita confirmación antes de ejecutar la acción

#### Rechazar Promoción
- Cambia el estado de la promoción a 'RECHAZADA'
- Requiere especificar un motivo del rechazo
- El motivo se almacena en la tabla `motivos_rechazo`
- El motivo puede ser comunicado al dueño del local

### Base de Datos

#### Tabla promociones
```sql
- codPromocion (INT, PRIMARY KEY)
- codLocal (INT, FOREIGN KEY)
- nombrePromocion (VARCHAR)
- descripcion (TEXT)
- descuento (DECIMAL)
- tipoPromocion (VARCHAR)
- fechaInicio (DATE)
- fechaFin (DATE)
- condiciones (TEXT, NULLABLE)
- estado (ENUM: 'PENDIENTE', 'ACTIVA', 'RECHAZADA')
- fechaCreacion (TIMESTAMP)
```

#### Tabla motivos_rechazo
```sql
- id (INT, PRIMARY KEY)
- codPromocion (INT, FOREIGN KEY)
- motivo (TEXT)
- fechaRechazo (TIMESTAMP)
```

### Flujo de Trabajo

1. **Creación**: El dueño del local crea una promoción (estado: PENDIENTE)
2. **Revisión**: El admin revisa la promoción en esta interfaz
3. **Decisión**: 
   - Si aprueba: estado cambia a ACTIVA
   - Si rechaza: estado cambia a RECHAZADA + se registra motivo

### Archivos Relacionados
- `views/admin/solicitudes_promociones.php` - Interfaz principal
- `views/duenio/crearPromociones.php` - Creación de promociones
- `scripts/create_motivos_rechazo_table.sql` - Script para crear tabla de motivos

### Requisitos
- PHP 7.4+
- MySQL 5.7+
- Bootstrap 5.3.3
- Font Awesome 6.0.0
