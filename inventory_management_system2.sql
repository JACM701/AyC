-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-07-2025 a las 06:48:39
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `inventory_management_system2`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bobinas`
--

CREATE TABLE `bobinas` (
  `bobina_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `metros_actuales` decimal(10,2) DEFAULT NULL,
  `identificador` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `parent_category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `parent_category_id`) VALUES
(1, 'Cámaras', NULL),
(2, 'Equipo', NULL),
(4, 'Cables', NULL),
(5, 'Teléfono', NULL),
(6, 'Switch', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `cliente_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones`
--

CREATE TABLE `cotizaciones` (
  `cotizacion_id` int(11) NOT NULL,
  `numero_cotizacion` varchar(20) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `fecha_cotizacion` date DEFAULT NULL,
  `validez_dias` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `descuento_porcentaje` decimal(5,2) DEFAULT NULL,
  `descuento_monto` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `condiciones_pago` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones_acciones`
--

CREATE TABLE `cotizaciones_acciones` (
  `accion_id` int(11) NOT NULL,
  `nombre_accion` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizaciones_acciones`
--

INSERT INTO `cotizaciones_acciones` (`accion_id`, `nombre_accion`) VALUES
(3, 'Aprobada'),
(5, 'Convertida'),
(1, 'Creada'),
(2, 'Enviada'),
(6, 'Modificada'),
(4, 'Rechazada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones_historial`
--

CREATE TABLE `cotizaciones_historial` (
  `historial_id` int(11) NOT NULL,
  `cotizacion_id` int(11) NOT NULL,
  `accion_id` int(11) NOT NULL,
  `comentario` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `fecha_accion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones_productos`
--

CREATE TABLE `cotizaciones_productos` (
  `cotizacion_producto_id` int(11) NOT NULL,
  `cotizacion_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `precio_total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos`
--

CREATE TABLE `equipos` (
  `equipo_id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `estado` enum('activo','inactivo','en_reparacion') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos_asignaciones`
--

CREATE TABLE `equipos_asignaciones` (
  `asignacion_id` int(11) NOT NULL,
  `equipo_id` int(11) DEFAULT NULL,
  `tecnico_id` int(11) DEFAULT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_devolucion` timestamp NULL DEFAULT NULL,
  `estado` enum('asignado','devuelto','perdido') DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_tecnico`
--

CREATE TABLE `estado_tecnico` (
  `estado_id` int(11) NOT NULL,
  `nombre_estado` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `est_cotizacion`
--

CREATE TABLE `est_cotizacion` (
  `est_cot_id` int(11) NOT NULL,
  `nombre_estado` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `est_cotizacion`
--

INSERT INTO `est_cotizacion` (`est_cot_id`, `nombre_estado`) VALUES
(3, 'Aprobada'),
(1, 'Borrador'),
(5, 'Convertida'),
(2, 'Enviada'),
(4, 'Rechazada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos`
--

CREATE TABLE `insumos` (
  `insumo_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `minimo` decimal(10,2) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `estado` enum('disponible','bajo_stock','agotado') DEFAULT NULL,
  `consumo_semanal` decimal(10,2) DEFAULT NULL,
  `ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos_movements`
--

CREATE TABLE `insumos_movements` (
  `insumo_movement_id` int(11) NOT NULL,
  `insumo_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tipo_movimiento` enum('entrada','salida') DEFAULT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `fecha_movimiento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs`
--

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movements`
--

CREATE TABLE `movements` (
  `movement_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `bobina_id` int(11) DEFAULT NULL,
  `movement_type_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movement_types`
--

CREATE TABLE `movement_types` (
  `movement_type_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `is_entry` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movement_types`
--

INSERT INTO `movement_types` (`movement_type_id`, `name`, `is_entry`) VALUES
(1, 'Entrada', 1),
(2, 'Salida', 0),
(3, 'Ajuste', 1),
(4, 'Venta', 1),
(5, 'Compra', 1),
(6, 'Transferencia', 1),
(7, 'Devolución', 1),
(8, 'Pérdida', 1),
(9, 'Merma', 1),
(10, 'Inventario', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `precios_proveedores`
--

CREATE TABLE `precios_proveedores` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `min_stock` int(11) DEFAULT NULL,
  `max_stock` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `tipo_gestion` enum('normal','bobina','bolsa','par','kit') DEFAULT NULL,
  `unit_measure` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'Administrador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system_config`
--

CREATE TABLE `system_config` (
  `config_id` int(11) NOT NULL,
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tecnicos`
--

CREATE TABLE IF NOT EXISTS `tecnicos` (
  `tecnico_id` INT(11) NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(20) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `fecha_ingreso` DATE DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tecnico_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `custom_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_permissions`)),
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `role_id`, `custom_permissions`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$hbvAV3/j8TccjhI8q8l9a.SmTbg8Y1uHf5LNEGKV3VrRIoGCLWyO6', 'Administrador', 'Sistema', NULL, 1, NULL, 1, NULL, '2025-07-08 18:13:35', '2025-07-08 18:13:35');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_cotizaciones_complete`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_cotizaciones_complete` (
`cotizacion_id` int(11)
,`numero_cotizacion` varchar(20)
,`fecha_cotizacion` date
,`validez_dias` int(11)
,`subtotal` decimal(10,2)
,`descuento_porcentaje` decimal(5,2)
,`descuento_monto` decimal(10,2)
,`total` decimal(10,2)
,`condiciones_pago` text
,`observaciones` text
,`estado` varchar(50)
,`cliente_nombre` varchar(100)
,`usuario_creador` varchar(50)
,`productos_total` bigint(21)
,`subtotal_calculado` decimal(42,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_cotizaciones_historial`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_cotizaciones_historial` (
`historial_id` int(11)
,`numero_cotizacion` varchar(20)
,`nombre_accion` varchar(50)
,`comentario` text
,`realizado_por` varchar(50)
,`fecha_accion` timestamp
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_cotizaciones_complete`
--
DROP TABLE IF EXISTS `v_cotizaciones_complete`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_cotizaciones_complete`  AS SELECT `c`.`cotizacion_id` AS `cotizacion_id`, `c`.`numero_cotizacion` AS `numero_cotizacion`, `c`.`fecha_cotizacion` AS `fecha_cotizacion`, `c`.`validez_dias` AS `validez_dias`, `c`.`subtotal` AS `subtotal`, `c`.`descuento_porcentaje` AS `descuento_porcentaje`, `c`.`descuento_monto` AS `descuento_monto`, `c`.`total` AS `total`, `c`.`condiciones_pago` AS `condiciones_pago`, `c`.`observaciones` AS `observaciones`, `est`.`nombre_estado` AS `estado`, `cl`.`nombre` AS `cliente_nombre`, `u`.`username` AS `usuario_creador`, count(`cp`.`cotizacion_producto_id`) AS `productos_total`, sum(`cp`.`cantidad` * `cp`.`precio_unitario`) AS `subtotal_calculado` FROM ((((`cotizaciones` `c` left join `est_cotizacion` `est` on(`c`.`estado_id` = `est`.`est_cot_id`)) left join `clientes` `cl` on(`c`.`cliente_id` = `cl`.`cliente_id`)) left join `users` `u` on(`c`.`user_id` = `u`.`user_id`)) left join `cotizaciones_productos` `cp` on(`c`.`cotizacion_id` = `cp`.`cotizacion_id`)) GROUP BY `c`.`cotizacion_id` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_cotizaciones_historial`
--
DROP TABLE IF EXISTS `v_cotizaciones_historial`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_cotizaciones_historial`  AS SELECT `h`.`historial_id` AS `historial_id`, `c`.`numero_cotizacion` AS `numero_cotizacion`, `a`.`nombre_accion` AS `nombre_accion`, `h`.`comentario` AS `comentario`, `u`.`username` AS `realizado_por`, `h`.`fecha_accion` AS `fecha_accion` FROM (((`cotizaciones_historial` `h` left join `cotizaciones` `c` on(`h`.`cotizacion_id` = `c`.`cotizacion_id`)) left join `cotizaciones_acciones` `a` on(`h`.`accion_id` = `a`.`accion_id`)) left join `users` `u` on(`h`.`user_id` = `u`.`user_id`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bobinas`
--
ALTER TABLE `bobinas`
  ADD PRIMARY KEY (`bobina_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `unique_category_name` (`name`),
  ADD KEY `parent_category_id` (`parent_category_id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`cliente_id`);

--
-- Indices de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD PRIMARY KEY (`cotizacion_id`),
  ADD UNIQUE KEY `numero_cotizacion` (`numero_cotizacion`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `estado_id` (`estado_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `cotizaciones_acciones`
--
ALTER TABLE `cotizaciones_acciones`
  ADD PRIMARY KEY (`accion_id`),
  ADD UNIQUE KEY `nombre_accion` (`nombre_accion`);

--
-- Indices de la tabla `cotizaciones_historial`
--
ALTER TABLE `cotizaciones_historial`
  ADD PRIMARY KEY (`historial_id`),
  ADD KEY `cotizacion_id` (`cotizacion_id`),
  ADD KEY `accion_id` (`accion_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `cotizaciones_productos`
--
ALTER TABLE `cotizaciones_productos`
  ADD PRIMARY KEY (`cotizacion_producto_id`),
  ADD KEY `cotizacion_id` (`cotizacion_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD PRIMARY KEY (`equipo_id`);

--
-- Indices de la tabla `equipos_asignaciones`
--
ALTER TABLE `equipos_asignaciones`
  ADD PRIMARY KEY (`asignacion_id`),
  ADD KEY `equipo_id` (`equipo_id`),
  ADD KEY `tecnico_id` (`tecnico_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `estado_tecnico`
--
ALTER TABLE `estado_tecnico`
  ADD PRIMARY KEY (`estado_id`),
  ADD UNIQUE KEY `nombre_estado` (`nombre_estado`);

--
-- Indices de la tabla `est_cotizacion`
--
ALTER TABLE `est_cotizacion`
  ADD PRIMARY KEY (`est_cot_id`),
  ADD UNIQUE KEY `nombre_estado` (`nombre_estado`);

--
-- Indices de la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD PRIMARY KEY (`insumo_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `insumos_movements`
--
ALTER TABLE `insumos_movements`
  ADD PRIMARY KEY (`insumo_movement_id`),
  ADD KEY `insumo_id` (`insumo_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `movements`
--
ALTER TABLE `movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `bobina_id` (`bobina_id`),
  ADD KEY `movement_type_id` (`movement_type_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `movement_types`
--
ALTER TABLE `movement_types`
  ADD PRIMARY KEY (`movement_type_id`);

--
-- Indices de la tabla `precios_proveedores`
--
ALTER TABLE `precios_proveedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indices de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`config_id`),
  ADD UNIQUE KEY `config_key` (`config_key`);

--
-- Indices de la tabla `tecnicos`
--
ALTER TABLE `tecnicos`
  ADD PRIMARY KEY (`tecnico_id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `estado_id` (`estado_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bobinas`
--
ALTER TABLE `bobinas`
  MODIFY `bobina_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `cliente_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `cotizacion_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_acciones`
--
ALTER TABLE `cotizaciones_acciones`
  MODIFY `accion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_historial`
--
ALTER TABLE `cotizaciones_historial`
  MODIFY `historial_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_productos`
--
ALTER TABLE `cotizaciones_productos`
  MODIFY `cotizacion_producto_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `equipo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `equipos_asignaciones`
--
ALTER TABLE `equipos_asignaciones`
  MODIFY `asignacion_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estado_tecnico`
--
ALTER TABLE `estado_tecnico`
  MODIFY `estado_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `est_cotizacion`
--
ALTER TABLE `est_cotizacion`
  MODIFY `est_cot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `insumos`
--
ALTER TABLE `insumos`
  MODIFY `insumo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `insumos_movements`
--
ALTER TABLE `insumos_movements`
  MODIFY `insumo_movement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movements`
--
ALTER TABLE `movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movement_types`
--
ALTER TABLE `movement_types`
  MODIFY `movement_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `precios_proveedores`
--
ALTER TABLE `precios_proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `system_config`
--
ALTER TABLE `system_config`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tecnicos`
--
ALTER TABLE `tecnicos`
  MODIFY `tecnico_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bobinas`
--
ALTER TABLE `bobinas`
  ADD CONSTRAINT `bobinas_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`cliente_id`),
  ADD CONSTRAINT `cotizaciones_ibfk_2` FOREIGN KEY (`estado_id`) REFERENCES `est_cotizacion` (`est_cot_id`),
  ADD CONSTRAINT `cotizaciones_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `cotizaciones_historial`
--
ALTER TABLE `cotizaciones_historial`
  ADD CONSTRAINT `cotizaciones_historial_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`cotizacion_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cotizaciones_historial_ibfk_2` FOREIGN KEY (`accion_id`) REFERENCES `cotizaciones_acciones` (`accion_id`),
  ADD CONSTRAINT `cotizaciones_historial_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `cotizaciones_productos`
--
ALTER TABLE `cotizaciones_productos`
  ADD CONSTRAINT `cotizaciones_productos_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`cotizacion_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cotizaciones_productos_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `equipos_asignaciones`
--
ALTER TABLE `equipos_asignaciones`
  ADD CONSTRAINT `equipos_asignaciones_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`equipo_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `equipos_asignaciones_ibfk_2` FOREIGN KEY (`tecnico_id`) REFERENCES `tecnicos` (`tecnico_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `equipos_asignaciones_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD CONSTRAINT `insumos_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `insumos_movements`
--
ALTER TABLE `insumos_movements`
  ADD CONSTRAINT `insumos_movements_ibfk_1` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`insumo_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `insumos_movements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `movements`
--
ALTER TABLE `movements`
  ADD CONSTRAINT `movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movements_ibfk_2` FOREIGN KEY (`bobina_id`) REFERENCES `bobinas` (`bobina_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movements_ibfk_3` FOREIGN KEY (`movement_type_id`) REFERENCES `movement_types` (`movement_type_id`),
  ADD CONSTRAINT `movements_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `precios_proveedores`
--
ALTER TABLE `precios_proveedores`
  ADD CONSTRAINT `precios_proveedores_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `precios_proveedores_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Filtros para la tabla `tecnicos`
--
ALTER TABLE `tecnicos`
  ADD CONSTRAINT `tecnicos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tecnicos_ibfk_2` FOREIGN KEY (`estado_id`) REFERENCES `estado_tecnico` (`estado_id`);

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Crear tabla de servicios
CREATE TABLE servicios (
  servicio_id INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  categoria VARCHAR(50) DEFAULT NULL,
  precio DECIMAL(10,2) NOT NULL,
  imagen VARCHAR(255) DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (servicio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla de relación cotizaciones-servicios
CREATE TABLE cotizaciones_servicios (
  cotizacion_servicio_id INT(11) NOT NULL AUTO_INCREMENT,
  cotizacion_id INT(11) NOT NULL,
  servicio_id INT(11) DEFAULT NULL,
  nombre_servicio VARCHAR(100) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  cantidad DECIMAL(10,2) NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  precio_total DECIMAL(10,2) NOT NULL,
  imagen VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cotizacion_servicio_id),
  KEY cotizacion_id (cotizacion_id),
  KEY servicio_id (servicio_id),
  CONSTRAINT cotizaciones_servicios_ibfk_1 FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones (cotizacion_id) ON DELETE CASCADE,
  CONSTRAINT cotizaciones_servicios_ibfk_2 FOREIGN KEY (servicio_id) REFERENCES servicios (servicio_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar servicios de ejemplo
INSERT INTO servicios (nombre, descripcion, categoria, precio, imagen) VALUES
('Instalación de cámara', 'Instalación completa de cámara de seguridad incluyendo cableado y configuración.', 'Instalación', 150.00, NULL),
('Configuración de DVR', 'Configuración y programación de grabador digital de video.', 'Configuración', 80.00, NULL),
('Mantenimiento preventivo', 'Revisión y verificación de funcionamiento de equipos.', 'Mantenimiento', 120.00, NULL),
('Reparación de cableado', 'Reparación o reemplazo de cables de video y alimentación.', 'Reparación', 200.00, NULL),
('Actualización de firmware', 'Actualización de software de equipos de seguridad.', 'Software', 60.00, NULL),
('Consultoría técnica', 'Asesoramiento técnico para sistemas de seguridad.', 'Consultoría', 100.00, NULL),
('Configuración remota', 'Configuración de equipos vía remota.', 'Configuración', 50.00, NULL),
('Capacitación de usuarios', 'Entrenamiento en el uso de sistemas de seguridad.', 'Capacitación', 150.00, NULL);