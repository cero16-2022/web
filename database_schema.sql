-- Elimina la tabla si ya existe para evitar conflictos.
DROP TABLE IF EXISTS `servicios`;

-- Crea la tabla de `servicios` con la estructura correcta.
CREATE TABLE `servicios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nro_ticket` VARCHAR(50) NULL,
  `cliente` VARCHAR(255) NOT NULL,
  `local` VARCHAR(255) NULL,
  `descripcion` TEXT NOT NULL,
  `fecha_solicitud` DATE NULL,
  `fecha_ejecucion` DATE NULL, -- Added
  `sub_total` DECIMAL(10, 2) NOT NULL DEFAULT 0.00, -- Added
  `total` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `estado` VARCHAR(50) NOT NULL DEFAULT 'Pendiente',
  `nro_oc_os` VARCHAR(100) NULL, -- Added
  `nro_certificacion` VARCHAR(100) NULL, -- Added
  `fecha_sustentado` DATE NULL, -- Added
  `nro_factura` VARCHAR(100) NULL, -- Added
  `fecha_facturacion` DATE NULL, -- Added
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;