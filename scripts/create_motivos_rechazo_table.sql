-- Crear tabla para almacenar motivos de rechazo de promociones
CREATE TABLE IF NOT EXISTS motivos_rechazo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codPromocion INT NOT NULL,
    motivo TEXT NOT NULL,
    fechaRechazo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (codPromocion) REFERENCES promociones(codPromocion) ON DELETE CASCADE
);

-- Agregar índice para mejorar el rendimiento
CREATE INDEX idx_motivos_rechazo_codPromocion ON motivos_rechazo(codPromocion);
