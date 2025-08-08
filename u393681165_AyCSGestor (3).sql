-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 08-08-2025 a las 15:26:48
-- Versión del servidor: 10.11.10-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u393681165_AyCSGestor`
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

--
-- Volcado de datos para la tabla `bobinas`
--

INSERT INTO `bobinas` (`bobina_id`, `product_id`, `metros_actuales`, `identificador`, `is_active`, `created_at`) VALUES
(1, 37, 0.00, 'Bobina #1', 1, '2025-07-14 15:27:58'),
(8, 72, 0.00, 'Bobina #1', 1, '2025-07-16 20:05:04'),
(9, 72, 0.00, 'Bobina #2', 1, '2025-07-16 20:08:12'),
(10, 72, 0.00, 'Bobina #3', 1, '2025-07-16 20:10:51'),
(11, 72, 0.00, 'Bobina #4', 1, '2025-07-16 20:11:10'),
(12, 72, 0.00, 'Bobina #5', 1, '2025-07-22 23:14:08'),
(13, 72, 0.00, 'Bobina #6', 1, '2025-07-22 23:15:37'),
(14, 72, 0.00, 'Bobina #7', 1, '2025-07-22 23:15:37'),
(15, 72, 305.00, 'Bobina #8', 1, '2025-07-22 23:15:37'),
(16, 72, 0.00, 'Bobina #9', 1, '2025-07-30 19:34:09'),
(17, 72, 0.00, 'Bobina #10', 1, '2025-07-30 19:34:09'),
(18, 72, 305.00, 'Bobina #11', 1, '2025-07-30 19:34:09'),
(19, 72, 305.00, 'Bobina #12', 1, '2025-07-30 19:34:09');

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
(1, 'DVR', NULL),
(2, 'Cámaras IP', NULL),
(3, 'DVR 8CH', NULL),
(4, 'DVR 4CH', NULL),
(5, 'Accesorios', NULL),
(6, 'Cámaras PTZ', NULL),
(7, 'Cámaras', NULL),
(8, 'Control de Asistencias', NULL),
(9, 'Switch Poe', NULL),
(10, 'Cámaras Domo', NULL),
(11, 'Cámaras Full color', NULL),
(12, 'Switch', NULL),
(13, 'Router', NULL),
(14, 'Dash Cam', NULL),
(15, 'Alarma', NULL),
(17, 'Sensores de Alarmas', NULL),
(18, 'Paquetes de alarmas', NULL),
(19, 'Monitores', NULL),
(27, 'Facial', NULL),
(28, 'Cables', NULL),
(29, 'Cámara Wifi', NULL),
(30, 'Terminal Acceso', NULL),
(31, 'Cámara Bullet', NULL),
(32, 'Soporte y Montaje', NULL),
(33, 'Energía', NULL),
(34, 'Modulos de Expansión', NULL),
(35, 'Videoportero', NULL),
(36, 'Mesh', NULL),
(37, 'Gabinete', NULL),
(38, 'DISCOS', NULL),
(39, 'Registros', NULL),
(40, 'NVR', NULL),
(41, 'MEMORIAS', NULL),
(42, 'CONTROL  DE ACCESO', NULL),
(43, 'CERRADURAS', NULL),
(44, 'ENERGIZADORES', NULL);

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

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`cliente_id`, `nombre`, `telefono`, `ubicacion`, `email`) VALUES
(1, 'Mario Bajce', '9993351219', 'Mérida Yucatán', ''),
(2, 'Felipe de Jesus Avila', '', '', ''),
(3, 'SR. FERNANDO', '', '', ''),
(4, 'DANIEL ALVAREZ', '', '', ''),
(5, 'Andres Cantón', '9999688266', 'Dzityá/ Yucatán', ''),
(6, 'Rosa Yolanda', '961 142 29', '', ''),
(7, 'Llantera Aries', '9991754198', 'Mérida/ Yucatán', ''),
(8, 'KARINA VARGAS', '999 562 44', '', ''),
(9, 'Hotel Los Arcos', '9994582176', 'Mérida/ Yucatán', ''),
(10, 'Josué Molina Herrera', '9994940979', 'calle 88 588 por 13-1 pensiones 7 etapa', '');

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

--
-- Volcado de datos para la tabla `cotizaciones`
--

INSERT INTO `cotizaciones` (`cotizacion_id`, `numero_cotizacion`, `cliente_id`, `fecha_cotizacion`, `validez_dias`, `subtotal`, `descuento_porcentaje`, `descuento_monto`, `total`, `condiciones_pago`, `observaciones`, `estado_id`, `user_id`, `created_at`, `updated_at`) VALUES
(2, 'COT-2025-0001', 1, '2025-07-31', 30, 41426.61, 0.00, 4142.66, 43249.38, '', '0', 5, 1, '2025-07-31 17:47:45', '2025-08-06 16:06:26'),
(3, 'COT-2025-0002', 2, '2025-07-31', 30, 4500.26, 0.00, 0.00, 4500.26, '', '0', 2, 1, '2025-07-31 23:03:29', '2025-07-31 23:03:29'),
(4, 'COT-2025-0003', 3, '2025-08-05', 30, 9597.62, 0.00, 0.00, 9597.62, '0', '', 2, 3, '2025-08-05 18:23:54', '2025-08-05 18:23:54'),
(5, 'COT-2025-0004', 4, '2025-08-05', 30, 3230.00, 15.00, 484.50, 2745.50, '0', '0', 2, 2, '2025-08-05 18:48:36', '2025-08-05 18:59:41'),
(6, 'COT-2025-0005', 5, '2025-08-06', 30, 194520.00, 10.00, 19452.00, 175068.00, '0', '0', 2, 5, '2025-08-06 17:23:36', '2025-08-08 14:47:25'),
(7, 'COT-2025-0006', 5, '2025-08-06', 30, 194770.00, 10.00, 19477.00, 175293.00, 'En caso de requerir factura se agregará IVA', '0', 2, 5, '2025-08-06 17:43:37', '2025-08-06 21:45:41'),
(8, 'COT-2025-0007', 6, '2025-08-06', 30, 1900.00, 0.00, 0.00, 1900.00, '0', 'no incluye conectores ballum, machos, hembras, fuentes.', 2, 3, '2025-08-06 20:29:03', '2025-08-06 20:29:03'),
(9, 'COT-2025-0008', 7, '2025-08-06', 30, 4190.00, 0.00, 0.00, 4190.00, '0', '[DESCRIPCIONES:eyI1MCI6IkRBSFVBIEhBQy1CMUEyMU4tVS0yOCAtIENhbWFyYSBCdWxsZXQgZGUgMiBNZWdhcGl4ZWxlc1wvIExlbnRlIGRlIDIuOCBtbVwvIDMwIE1ldHJvcyBkZSBJUlwvIDEwMCBHcmFkb3MgZGUgQXBlcnR1cmFcLyBJUDY3XC8gU29wb3J0YTogQ1ZJXC9UVklcL0FIRCB5IENWQlNcLyAjTG9OdWV2byAjVm9sREggI00xIiwiNCI6IkRBSFVBIFhWUjFCMDQtSSAtRFZSIGRlIDQgQ2FuYWxlcyAxMDgwcCBMaXRlV2l6U2Vuc2UgeSBDb29wZXItSS4gQ29tcGF0aWJsZSBjb24gSC4yNjUrLCBhZG1pdGUgaGFzdGEgNSBjYW5hbGVzIElQIHkgNCBjYW5hbGVzIGNvbiBTTUQgUGx1cyBwYXJhIGRldGVjY2lcdTAwZjNuIGF2YW56YWRhLiBCXHUwMGZhc3F1ZWRhIGludGVsaWdlbnRlIGRlIHBlcnNvbmFzIHkgdmVoXHUwMGVkY3Vsb3MsIGNvZGlmaWNhY2lcdTAwZjNuIGVmaWNpZW50ZSAjREFIUTFNICNMRiNWb2xESCIsIjcyIjoiU0FYWE9OIE9VVFA1RUNDQUVYVCAtIENhYmxlIFVUUCBDQ0EsIGNhdGVnb3JcdTAwZWRhIDVFLCBjb2xvciBuZWdybywgMzA1IG1ldHJvcyBwYXJhIGV4dGVyaW9yLCBjb24gNCBwYXJlcyB5IGRvYmxlIGZvcnJvIiwiNDciOiJUUkFOU0NFUFRPUkVTIEhEIDJNUCBFTlNPTiBFTlMtVlQxMDAgQUhEXC9UVklcL0NWSSBQVVNILUlOIENPTiBDT05FQ1RPUiwgQUlTTEFET1IgREUgUlVJRE8gWSBQUk9URUNUT1IgREUgVk9MVEFKRS4gQ09ORUNUT1IgMTAwJSBDT0JSRSIsIjc5IjoiU0FYWE9OIFBTVTEyMDREIC0gRnVlbnRlIGRlIHBvZGVyIHJlZ3VsYWRhIGRlIDEyIFZjYyA0LjEgQW1wZXJlc1wvIENvbiBDYWJsZSBkZSAxLjIgTWV0cm9zXC8gUGFyYSBVc29zIE11bHRpcGxlczogU2lzdGVtYXMgZGUgQ0NUViwgQWNjZXNvLCBFVENcLyBDZXJ0aWZpY2FjaVx1MDBmM24gVUwiLCI3NSI6IkRpc2NvIER1cm8gR0VORVJJQ08gTmV3IFB1bGwsIDUwMCBHQiwgU0FUQSwgMy41IHB1bGdhZGFzLCBQQyJ9]', 2, 5, '2025-08-06 22:00:44', '2025-08-06 22:00:44'),
(10, 'COT-2025-0009', 6, '2025-08-07', 30, 1739.00, 0.00, 0.00, 1739.00, '0', '0', 2, 3, '2025-08-07 16:16:12', '2025-08-07 16:21:08'),
(11, 'COT-2025-0010', 6, '2025-08-07', 30, 1739.00, 0.00, 0.00, 1739.00, '0', '0', 2, 3, '2025-08-07 16:16:15', '2025-08-07 17:23:13'),
(12, 'COT-2025-0011', 5, '2025-08-07', 30, 675.30, 15.00, 101.30, 574.01, '0', '0', 1, 5, '2025-08-07 16:17:45', '2025-08-07 16:28:35'),
(13, 'COT-2025-0012', 8, '2025-08-07', 30, 1350.00, 0.00, 0.00, 1350.00, '0', 'No incluye materiales ni otro tipo de mano de obra', 2, 3, '2025-08-07 16:31:41', '2025-08-07 16:31:41'),
(14, 'COT-2025-0013', 9, '2025-08-07', 30, 11420.00, 0.00, 0.00, 11420.00, '0', '0', 2, 5, '2025-08-07 17:13:38', '2025-08-07 20:05:09'),
(15, 'COT-2025-0014', 6, '2025-08-07', 30, 1835.11, 0.00, 0.00, 1835.11, '0', '[DESCRIPCIONES:eyI1MiI6IkRhaHVhIElQQy1XUFQxNTM5REQtU1ctNUUyLVBWIC0gQ1x1MDBlMW1hcmEgSVAgUFQgV2lmaSBEdWFsIGRlIDEwIE1QLCAyIExlbnRlcyBkZSA1IE1QIGNhZGEgdW5vIChGaWpvIHkgUFQpLCBJbHVtaW5hZG9yIER1YWwgSW50ZWxpZ2VudGVcLyBJUiBkZSA1MG0sIE1pY3JvZm9ubyB5IEFsdGF2b3ogSW50ZWdyYWRvcywgSUEsIEF1dG90cmFja2luZywgRGlzdWFjaVx1MDBmM24gYWN0aXZhLCBSYW51cmEgTWljcm9TRCwgSVA2NiAjTG9OdWV2byAjRERQVCAjREhXaWZpIn0=]', 2, 3, '2025-08-07 18:22:44', '2025-08-07 18:22:44'),
(16, 'COT-2025-0015', 6, '2025-08-07', 30, 2189.11, 0.00, 0.00, 2189.11, '0', '[DESCRIPCIONES:eyI1MiI6IkRhaHVhIElQQy1XUFQxNTM5REQtU1ctNUUyLVBWIC0gQ1x1MDBlMW1hcmEgSVAgUFQgV2lmaSBEdWFsIGRlIDEwIE1QLCAyIExlbnRlcyBkZSA1IE1QIGNhZGEgdW5vIChGaWpvIHkgUFQpLCBJbHVtaW5hZG9yIER1YWwgSW50ZWxpZ2VudGVcLyBJUiBkZSA1MG0sIE1pY3JvZm9ubyB5IEFsdGF2b3ogSW50ZWdyYWRvcywgSUEsIEF1dG90cmFja2luZywgRGlzdWFjaVx1MDBmM24gYWN0aXZhLCBSYW51cmEgTWljcm9TRCwgSVA2NiAjTG9OdWV2byAjRERQVCAjREhXaWZpIn0=]', 2, 3, '2025-08-07 18:24:17', '2025-08-07 18:24:17'),
(17, 'COT-2025-0016', 10, '2025-08-08', 30, 5278.00, 0.00, 0.00, 5278.00, '0', '[DESCRIPCIONES:eyIxMTYiOiJEYWh1YSBJUEMtV1BUMTUzOURELVNXLTVFMi1QViAtIENcdTAwZTFtYXJhIElQIFBUIFdpZmkgRHVhbCBkZSAxMCBNUCwgMiBMZW50ZXMgZGUgNSBNUCBjYWRhIHVubyAoRmlqbyB5IFBUKSwgSWx1bWluYWRvciBEdWFsIEludGVsaWdlbnRlXC8gSVIgZGUgNTBtLCBNaWNyb2Zvbm8geSBBbHRhdm96IEludGVncmFkb3MsIElBLCBBdXRvdHJhY2tpbmcsIERpc3VhY2lcdTAwZjNuIGFjdGl2YSwgUmFudXJhIE1pY3JvU0QsIElQNjYiLCI5OSI6Ik1lbW9yaWEgTWljcm8gU0QgQURBVEEgQVVTRFgxMjhHVUlDTDEwQTEtUkExLCAxMjggR0IsIDEwMCBNQlwvcywgTmVncm8sIENsYXNlIDEwIn0=]', 2, 3, '2025-08-08 14:19:05', '2025-08-08 14:19:05');

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

--
-- Volcado de datos para la tabla `cotizaciones_historial`
--

INSERT INTO `cotizaciones_historial` (`historial_id`, `cotizacion_id`, `accion_id`, `comentario`, `user_id`, `fecha_accion`) VALUES
(2, 2, 1, 'Cotización creada con 6 productos por un total de $43,249.38', 1, '2025-07-31 17:47:45'),
(3, 2, 4, 'Estado cambiado de \'Enviada\' a \'Rechazada\'', 1, '2025-07-31 18:04:20'),
(4, 2, 3, 'Estado cambiado de \'Rechazada\' a \'Aprobada\'', 1, '2025-07-31 22:02:39'),
(5, 2, 4, 'Estado cambiado de \'Aprobada\' a \'Rechazada\'', 1, '2025-07-31 22:02:44'),
(6, 2, 3, 'Estado cambiado de \'Rechazada\' a \'Aprobada\'', 1, '2025-07-31 22:12:44'),
(7, 2, 4, 'Estado cambiado de \'Aprobada\' a \'Rechazada\'', 1, '2025-07-31 22:12:53'),
(8, 2, 3, 'Estado cambiado de \'Rechazada\' a \'Aprobada\'', 1, '2025-07-31 22:33:11'),
(9, 3, 1, 'Cotización creada con 7 productos por un total de $4,500.26', 1, '2025-07-31 23:03:29'),
(10, 4, 1, 'Cotización creada con 6 productos por un total de $9,597.62', 3, '2025-08-05 18:23:54'),
(11, 5, 1, 'Cotización creada con 9 productos por un total de $2,939.40', 2, '2025-08-05 18:48:36'),
(12, 5, 6, 'Cotización modificada con 9 productos por un total de $2,745.50', 2, '2025-08-05 18:59:41'),
(13, 2, 5, 'Cotización convertida a venta (sin afectar inventario)', 6, '2025-08-06 16:06:26'),
(14, 6, 1, 'Cotización creada con 6 productos por un total de $3,020.00', 5, '2025-08-06 17:23:36'),
(15, 6, 6, 'Cotización modificada con 6 productos por un total de $2,511.00', 5, '2025-08-06 17:34:22'),
(16, 6, 6, 'Cotización modificada con 6 productos por un total de $2,700.00', 5, '2025-08-06 17:36:50'),
(17, 6, 6, 'Cotización modificada con 6 productos por un total de $2,700.00', 5, '2025-08-06 17:38:55'),
(18, 7, 1, 'Cotización creada con 6 productos por un total de $4,275.00', 5, '2025-08-06 17:43:37'),
(19, 7, 6, 'Cotización modificada con 6 productos por un total de $2,762.50', 5, '2025-08-06 17:45:21'),
(20, 7, 6, 'Cotización modificada con 6 productos por un total de $2,925.00', 5, '2025-08-06 17:49:11'),
(21, 7, 6, 'Cotización modificada con 6 productos por un total de $3,250.00', 5, '2025-08-06 17:52:42'),
(22, 8, 1, 'Cotización creada con 2 productos por un total de $1,900.00', 3, '2025-08-06 20:29:03'),
(23, 7, 6, 'Cotización modificada con 6 productos por un total de $165,554.50', 5, '2025-08-06 21:44:24'),
(24, 7, 6, 'Cotización modificada con 6 productos por un total de $175,293.00', 5, '2025-08-06 21:45:41'),
(25, 9, 1, 'Cotización creada con 7 productos por un total de $4,190.00', 5, '2025-08-06 22:00:44'),
(26, 10, 1, 'Cotización creada con 1 productos por un total de $2,189.00', 3, '2025-08-07 16:16:12'),
(27, 11, 1, 'Cotización creada con 1 productos por un total de $2,189.00', 3, '2025-08-07 16:16:15'),
(28, 12, 1, 'Cotización creada con 1 productos por un total de $580.80', 5, '2025-08-07 16:17:45'),
(29, 12, 6, 'Cotización modificada con 1 productos por un total de $574.01', 5, '2025-08-07 16:20:22'),
(30, 10, 6, 'Cotización modificada con 1 productos por un total de $1,739.00', 3, '2025-08-07 16:21:08'),
(31, 12, 6, 'Cotización modificada con 1 productos por un total de $574.01', 5, '2025-08-07 16:28:35'),
(32, 13, 1, 'Cotización creada con 0 productos por un total de $1,350.00', 3, '2025-08-07 16:31:41'),
(33, 14, 1, 'Cotización creada con 2 productos por un total de $5,570.00', 5, '2025-08-07 17:13:38'),
(34, 14, 6, 'Cotización modificada con 2 productos por un total de $4,280.00', 5, '2025-08-07 17:14:04'),
(35, 14, 6, 'Cotización modificada con 2 productos por un total de $1,007,480.00', 5, '2025-08-07 17:15:05'),
(36, 14, 6, 'Cotización modificada con 2 productos por un total de $4,280.00', 5, '2025-08-07 17:18:02'),
(37, 14, 6, 'Cotización modificada con 2 productos por un total de $1,007,480.00', 5, '2025-08-07 17:18:59'),
(38, 14, 6, 'Cotización modificada con 2 productos por un total de $1,007,480.00', 5, '2025-08-07 17:19:57'),
(39, 11, 6, 'Cotización modificada con 1 productos por un total de $1,739.00', 3, '2025-08-07 17:23:13'),
(40, 11, 6, 'Cotización modificada con 1 productos por un total de $1,739.00', 3, '2025-08-07 17:23:39'),
(41, 11, 6, 'Cotización modificada con 1 productos por un total de $1,739.00', 3, '2025-08-07 17:25:25'),
(42, 15, 1, 'Cotización creada con 1 productos por un total de $1,835.11', 3, '2025-08-07 18:22:44'),
(43, 16, 1, 'Cotización creada con 1 productos por un total de $2,189.11', 3, '2025-08-07 18:24:17'),
(44, 7, 6, 'Cotización modificada con 6 productos por un total de $175,293.00', 1, '2025-08-07 20:14:21'),
(45, 17, 1, 'Cotización creada con 2 productos por un total de $5,278.00', 3, '2025-08-08 14:19:05'),
(46, 6, 6, 'Cotización modificada con 6 productos por un total de $175,068.00', 6, '2025-08-08 14:47:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones_insumos`
--

CREATE TABLE `cotizaciones_insumos` (
  `cotizacion_insumo_id` int(11) NOT NULL,
  `cotizacion_id` int(11) NOT NULL,
  `insumo_id` int(11) NOT NULL,
  `nombre_insumo` varchar(255) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `proveedor` varchar(100) DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `precio_total` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `stock_disponible` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizaciones_insumos`
--

INSERT INTO `cotizaciones_insumos` (`cotizacion_insumo_id`, `cotizacion_id`, `insumo_id`, `nombre_insumo`, `categoria`, `proveedor`, `cantidad`, `precio_unitario`, `precio_total`, `imagen`, `stock_disponible`) VALUES
(51, 2, 16, 'Par de Plugs RJ45', 'Accesorios', 'MERCADO LIBRE', 16.00, 10.00, 160.00, NULL, NULL),
(54, 5, 16, 'Par de Plugs RJ45', 'Accesorios', '0', 2.00, 10.00, 20.00, NULL, 1000.00),
(55, 5, 17, 'CONECTOR MACHO', 'Accesorios', '0', 2.00, 8.00, 16.00, NULL, 500.00),
(66, 9, 18, 'Caja Estanca de Registro para Cámara', 'Accesorios', 'Vaqueiros', 4.00, 50.00, 200.00, NULL, 0.00),
(69, 12, 17, 'CONECTOR MACHO', 'Accesorios', '0', 1.00, 8.00, 8.00, NULL, 500.00),
(80, 14, 17, 'CONECTOR MACHO', 'Accesorios', '0', 14.00, 10.00, 140.00, NULL, 500.00),
(81, 14, 18, 'Caja Estanca de Registro para Cámara', 'Accesorios', '0', 14.00, 50.00, 700.00, NULL, 0.00),
(82, 7, 18, 'Caja Estanca de Registro para Cámara', 'Accesorios', '0', 3.00, 50.00, 150.00, NULL, 0.00),
(83, 6, 18, 'Caja Estanca de Registro para Cámara', 'Accesorios', '0', 3.00, 50.00, 150.00, NULL, 0.00);

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

--
-- Volcado de datos para la tabla `cotizaciones_productos`
--

INSERT INTO `cotizaciones_productos` (`cotizacion_producto_id`, `cotizacion_id`, `product_id`, `cantidad`, `precio_unitario`, `precio_total`) VALUES
(7, 2, 28, 16, 950.00, 15200.00),
(8, 2, 72, 950, 5.70, 5415.00),
(9, 2, 10, 3, 1598.04, 4794.12),
(10, 2, 80, 16, 50.00, 800.00),
(11, 2, 104, 1, 4642.86, 4642.86),
(12, 2, 96, 1, 2414.63, 2414.63),
(13, 3, 50, 6, 257.27, 1543.62),
(14, 3, 3, 1, 664.33, 664.33),
(15, 3, 72, 150, 7.00, 1050.00),
(16, 3, 73, 1, 680.11, 680.11),
(17, 3, 57, 1, 254.88, 254.88),
(18, 3, 80, 6, 24.05, 144.30),
(19, 3, 47, 6, 27.17, 163.02),
(20, 4, 109, 1, 1758.00, 1758.00),
(21, 4, 110, 6, 489.00, 2934.00),
(22, 4, 111, 2, 489.00, 978.00),
(23, 4, 21, 1, 1540.06, 1540.06),
(24, 4, 20, 1, 1037.56, 1037.56),
(25, 4, 112, 1, 1350.00, 1350.00),
(35, 5, 4, 1, 720.00, 720.00),
(36, 5, 26, 1, 260.00, 260.00),
(37, 5, 80, 2, 50.00, 100.00),
(38, 5, 75, 1, 600.00, 600.00),
(39, 5, 72, 60, 7.00, 420.00),
(40, 5, 47, 2, 70.00, 140.00),
(41, 5, 83, 1, 90.00, 90.00),
(42, 5, 100, 1, 450.00, 450.00),
(43, 5, 12, 1, 450.00, 450.00),
(92, 8, 73, 1, 950.00, 950.00),
(93, 8, 4, 1, 950.00, 950.00),
(106, 9, 50, 4, 257.27, 1029.08),
(107, 9, 4, 1, 530.17, 530.17),
(108, 9, 72, 100, 7.00, 700.00),
(109, 9, 47, 4, 27.17, 108.68),
(110, 9, 114, 4, 10.00, 40.00),
(111, 9, 79, 1, 224.83, 224.83),
(112, 9, 75, 1, 349.39, 349.39),
(117, 10, 116, 1, 1739.00, 1739.00),
(118, 12, 61, 2, 337.65, 675.30),
(129, 14, 72, 610, 1650.00, 1006500.00),
(130, 14, 47, 14, 70.00, 980.00),
(133, 11, 116, 1, 1739.00, 1739.00),
(134, 15, 52, 1, 1385.11, 1385.11),
(135, 16, 52, 1, 1739.11, 1739.11),
(136, 7, 23, 1, 650.00, 650.00),
(137, 7, 12, 2, 690.00, 1380.00),
(138, 7, 72, 90, 2135.00, 192150.00),
(139, 7, 47, 3, 70.00, 210.00),
(140, 7, 114, 3, 10.00, 30.00),
(141, 7, 79, 1, 350.00, 350.00),
(142, 17, 116, 2, 1739.00, 3478.00),
(143, 17, 99, 2, 450.00, 900.00),
(144, 6, 23, 1, 650.00, 650.00),
(145, 6, 12, 2, 690.00, 1380.00),
(146, 6, 72, 90, 2135.00, 192150.00),
(147, 6, 47, 3, 70.00, 210.00),
(148, 6, 114, 3, 10.00, 30.00),
(149, 6, 115, 1, 100.00, 100.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones_servicios`
--

CREATE TABLE `cotizaciones_servicios` (
  `cotizacion_servicio_id` int(11) NOT NULL,
  `cotizacion_id` int(11) NOT NULL,
  `servicio_id` int(11) DEFAULT NULL,
  `nombre_servicio` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `precio_total` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizaciones_servicios`
--

INSERT INTO `cotizaciones_servicios` (`cotizacion_servicio_id`, `cotizacion_id`, `servicio_id`, `nombre_servicio`, `descripcion`, `cantidad`, `precio_unitario`, `precio_total`, `imagen`, `created_at`) VALUES
(2, 2, 1, 'Instalación de cámara', 'Instalación completa de cámara de seguridad incluyendo cableado y configuración.', 16.00, 500.00, 8000.00, '', '2025-07-31 17:47:45'),
(3, 3, 4, 'Servicio de Instalación y Configuración  de Cámaras', 'Servicio de Instalación y Configuración \r\nde Cámaras \r\nSKU: CYA-EXTERA1-UTP', 6.00, 0.00, 0.00, 'servicio_1754002609_688bf4b1d977a.png', '2025-07-31 23:03:29'),
(4, 4, 5, 'Servicio de Instalación Configuración de Alarma', 'Servicio de Instalación Configuración de Alarma', 1.00, 0.00, 0.00, 'servicio_1754417648_689249f0aa3bd.png', '2025-08-05 18:23:54'),
(15, 9, 4, 'Servicio de Instalación y Configuración  de Cámaras', 'Servicio de Instalación y Configuración \r\nde Cámaras \r\nSKU: CYA-EXTERA1-UTP', 1.00, 0.00, 0.00, 'servicio_1754002609_688bf4b1d977a.png', '2025-08-06 22:00:44'),
(19, 10, 4, 'Servicio de Instalación y Configuración  de Cámaras', 'Servicio de Instalación y Configuración \nde Cámaras \nSKU: CYA-EXTERA1-UTP', 1.00, 450.00, 450.00, 'uploads/services/servicio_1754002609_688bf4b1d977a.png', '2025-08-07 16:21:08'),
(20, 13, 4, 'Servicio de Instalación y Configuración  de Cámaras', 'Servicio de Instalación y Configuración \nde Cámaras \nSKU: CYA-EXTERA1-UTP', 3.00, 450.00, 1350.00, 'servicio_1754002609_688bf4b1d977a.png', '2025-08-07 16:31:41'),
(26, 14, 6, 'Servicio Técnico de Cableado a Cámara (Solo mano de obra)', '', 14.00, 450.00, 6300.00, 'uploads/services/servicio_1754586485_6894dd759fea8.png', '2025-08-07 17:19:57'),
(29, 11, 4, 'Servicio de Instalación y Configuración  de Cámaras', 'Servicio de Instalación y Configuración \nde Cámaras \nSKU: CYA-EXTERA1-UTP', 1.00, 450.00, 450.00, 'servicio_1754002609_688bf4b1d977a.png', '2025-08-07 17:25:25'),
(30, 15, 4, 'Servicio de Instalación y Configuración  de Cámaras', 'Servicio de Instalación y Configuración \nde Cámaras \nSKU: CYA-EXTERA1-UTP', 1.00, 450.00, 450.00, 'servicio_1754002609_688bf4b1d977a.png', '2025-08-07 18:22:44'),
(31, 16, 4, 'Servicio de Instalación y Configuración  de Cámaras', 'Servicio de Instalación y Configuración \nde Cámaras \nSKU: CYA-EXTERA1-UTP', 1.00, 450.00, 450.00, 'servicio_1754002609_688bf4b1d977a.png', '2025-08-07 18:24:17'),
(32, 7, NULL, 'Servicio de Instalación y Configuración  de Cámaras', 'Servicio de Instalación y Configuración \nde Cámaras \nSKU: CYA-EXTERA1-UTP', 3.00, 450.00, 1350.00, 'uploads/services/servicio_1754002609_688bf4b1d977a.png', '2025-08-07 20:14:21'),
(33, 17, 4, 'Servicio de Instalación y Configuración  de Cámaras', 'Servicio de Instalación y Configuración \nde Cámaras \nSKU: CYA-EXTERA1-UTP', 2.00, 450.00, 900.00, 'servicio_1754002609_688bf4b1d977a.png', '2025-08-08 14:19:05'),
(34, 6, NULL, 'Servicio de Instalación y Configuración  de Cámaras', 'Servicio de Instalación y Configuración \nde Cámaras \nSKU: CYA-EXTERA1-UTP', 3.00, 450.00, 1350.00, 'uploads/services/servicio_1754002609_688bf4b1d977a.png', '2025-08-08 14:47:25');

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
(1, 'Borrador'),
(2, 'Enviada'),
(3, 'Aprobada'),
(4, 'Rechazada'),
(5, 'Convertida');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos`
--

CREATE TABLE `insumos` (
  `insumo_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `nombre` varchar(500) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `cantidad` decimal(12,4) DEFAULT NULL,
  `minimo` decimal(10,2) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `estado` enum('disponible','bajo_stock','agotado') DEFAULT NULL,
  `consumo_semanal` decimal(10,2) DEFAULT NULL,
  `ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cost_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `insumos`
--

INSERT INTO `insumos` (`insumo_id`, `product_id`, `category_id`, `supplier_id`, `nombre`, `categoria`, `unidad`, `imagen`, `cantidad`, `minimo`, `precio_unitario`, `ubicacion`, `estado`, `consumo_semanal`, `ultima_actualizacion`, `is_active`, `created_at`, `updated_at`, `cost_price`) VALUES
(16, NULL, 5, 9, 'Par de Plugs RJ45', 'Accesorios', 'Pieza', 'uploads/insumos/insumo_688ba99adfd31.webp', 1000.0000, 10.00, 10.00, '', 'disponible', 0.00, '2025-07-31 18:41:42', 1, '2025-07-31 17:36:26', '2025-07-31 18:41:42', 20.00),
(17, NULL, 5, 7, 'CONECTOR MACHO', 'Accesorios', 'Pieza', 'uploads/insumos/insumo_689251a233dec.png', 500.0000, 1000.00, 8.00, '', 'bajo_stock', 0.00, '2025-08-05 18:46:58', 1, '2025-08-05 18:46:58', '2025-08-05 18:46:58', NULL),
(18, NULL, 5, 8, 'Caja Estanca de Registro para Cámara', 'Accesorios', 'Pieza', 'uploads/insumos/insumo_68938ed4a3799.png', 0.0000, 10.00, 50.00, '', 'agotado', 0.00, '2025-08-06 17:20:20', 1, '2025-08-06 17:20:20', '2025-08-06 17:20:20', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos_movements`
--

CREATE TABLE `insumos_movements` (
  `insumo_movement_id` int(11) NOT NULL,
  `insumo_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tipo_movimiento` enum('entrada','salida') DEFAULT NULL,
  `cantidad` decimal(12,4) DEFAULT NULL,
  `piezas_movidas` decimal(10,2) DEFAULT NULL COMMENT 'Cantidad exacta de piezas movidas, si aplica',
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

--
-- Volcado de datos para la tabla `logs`
--

INSERT INTO `logs` (`log_id`, `user_id`, `action`, `description`, `created_at`) VALUES
(1, 1, 'login', 'Usuario Admin inició sesión', '2025-07-12 05:23:35'),
(2, 1, 'login', 'Usuario Admin inició sesión', '2025-07-13 03:01:23'),
(3, 1, 'login', 'Usuario Admin inició sesión', '2025-07-14 04:40:50'),
(4, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 04:57:22'),
(5, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 05:10:42'),
(6, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 14:13:34'),
(7, 1, 'login', 'Usuario Admin inició sesión', '2025-07-14 14:59:44'),
(8, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 15:53:22'),
(9, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 19:02:57'),
(10, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 19:04:17'),
(11, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 20:38:58'),
(12, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 22:32:23'),
(13, 1, 'login', 'Usuario admin inició sesión', '2025-07-14 22:45:22'),
(14, 1, 'login', 'Usuario admin inició sesión', '2025-07-15 14:04:18'),
(15, 1, 'login', 'Usuario admin inició sesión', '2025-07-15 14:15:06'),
(16, 1, 'login', 'Usuario admin inició sesión', '2025-07-15 14:53:49'),
(17, 2, 'login', 'Usuario Raul inició sesión', '2025-07-15 14:54:47'),
(18, 1, 'login', 'Usuario admin inició sesión', '2025-07-15 14:55:18'),
(19, 2, 'login', 'Usuario Raul inició sesión', '2025-07-15 18:52:59'),
(20, 1, 'login', 'Usuario admin inició sesión', '2025-07-15 18:54:44'),
(21, 1, 'login', 'Usuario admin inició sesión', '2025-07-15 19:16:48'),
(22, 1, 'login', 'Usuario admin inició sesión', '2025-07-15 19:16:48'),
(23, 1, 'login', 'Usuario admin inició sesión', '2025-07-15 21:50:09'),
(24, 2, 'login', 'Usuario Raul inició sesión', '2025-07-15 21:50:44'),
(25, 1, 'login', 'Usuario admin inició sesión', '2025-07-15 22:23:07'),
(26, 1, 'login', 'Usuario admin inició sesión', '2025-07-16 00:16:41'),
(27, 1, 'login', 'Usuario admin inició sesión', '2025-07-16 00:18:46'),
(28, 2, 'login', 'Usuario Raul inició sesión', '2025-07-16 14:10:51'),
(29, 2, 'login', 'Usuario Raul inició sesión', '2025-07-16 15:48:41'),
(30, 1, 'login', 'Usuario admin inició sesión', '2025-07-16 16:55:55'),
(31, 1, 'login', 'Usuario admin inició sesión', '2025-07-16 19:14:48'),
(32, 1, 'login', 'Usuario Admin inició sesión', '2025-07-16 19:15:09'),
(33, 1, 'login', 'Usuario admin inició sesión', '2025-07-17 05:10:46'),
(34, 1, 'login', 'Usuario admin inició sesión', '2025-07-17 05:16:57'),
(35, 2, 'login', 'Usuario Raul inició sesión', '2025-07-17 14:11:08'),
(36, 1, 'login', 'Usuario Admin inició sesión', '2025-07-17 17:25:48'),
(37, 1, 'login', 'Usuario admin inició sesión', '2025-07-17 17:51:59'),
(38, 1, 'login', 'Usuario admin inició sesión', '2025-07-17 17:52:37'),
(39, 1, 'login', 'Usuario Admin inició sesión', '2025-07-18 02:45:46'),
(40, 1, 'login', 'Usuario admin inició sesión', '2025-07-18 07:13:46'),
(41, 2, 'login', 'Usuario Raul inició sesión', '2025-07-18 14:06:46'),
(42, 1, 'login', 'Usuario Admin inició sesión', '2025-07-19 02:07:09'),
(43, 2, 'login', 'Usuario Raul inició sesión', '2025-07-19 14:23:43'),
(44, 1, 'login', 'Usuario admin inició sesión', '2025-07-20 16:58:09'),
(45, 1, 'login', 'Usuario admin inició sesión', '2025-07-21 14:25:45'),
(46, 2, 'login', 'Usuario Raul inició sesión', '2025-07-21 14:38:01'),
(47, 1, 'login', 'Usuario admin inició sesión', '2025-07-21 14:52:38'),
(48, 2, 'login', 'Usuario Raul inició sesión', '2025-07-22 14:16:22'),
(49, 2, 'login', 'Usuario Raul inició sesión', '2025-07-23 14:06:44'),
(50, 1, 'login', 'Usuario Admin inició sesión', '2025-07-23 22:48:59'),
(51, 2, 'login', 'Usuario Raul inició sesión', '2025-07-24 14:14:00'),
(52, 2, 'login', 'Usuario Raul inició sesión', '2025-07-24 20:00:34'),
(53, 2, 'login', 'Usuario Raul inició sesión', '2025-07-25 22:03:27'),
(54, 2, 'login', 'Usuario Raul inició sesión', '2025-07-26 14:13:46'),
(55, 2, 'login', 'Usuario Raul inició sesión', '2025-07-28 14:14:11'),
(56, 1, 'login', 'Usuario Admin inició sesión', '2025-07-29 00:34:16'),
(57, 2, 'login', 'Usuario Raul inició sesión', '2025-07-29 14:19:47'),
(58, 1, 'login', 'Usuario Admin inició sesión', '2025-07-29 23:04:47'),
(59, 1, 'login', 'Usuario admin inició sesión', '2025-07-29 23:04:50'),
(60, 2, 'login', 'Usuario Raul inició sesión', '2025-07-29 23:31:19'),
(61, 2, 'login', 'Usuario Raul inició sesión', '2025-07-30 14:09:45'),
(62, 1, 'login', 'Usuario Admin inició sesión', '2025-07-31 05:24:34'),
(63, 2, 'login', 'Usuario Raul inició sesión', '2025-07-31 14:17:52'),
(64, 1, 'login', 'Usuario admin inició sesión', '2025-07-31 16:45:02'),
(65, 1, 'login', 'Usuario admin inició sesión', '2025-07-31 17:28:15'),
(66, 1, 'login', 'Usuario admin inició sesión', '2025-07-31 22:00:53'),
(67, 1, 'login', 'Usuario admin inició sesión', '2025-07-31 22:41:35'),
(68, 2, 'login', 'Usuario Raul inició sesión', '2025-08-01 14:11:52'),
(69, 1, 'login', 'Usuario Admin inició sesión', '2025-08-01 20:15:34'),
(70, 1, 'login', 'Usuario Admin inició sesión', '2025-08-01 20:15:34'),
(71, 2, 'login', 'Usuario Raul inició sesión', '2025-08-02 14:29:41'),
(72, 1, 'login', 'Usuario admin inició sesión', '2025-08-04 20:47:09'),
(73, 2, 'login', 'Usuario Raul inició sesión', '2025-08-05 14:54:02'),
(74, 6, 'login', 'Usuario EstadiasUTM inició sesión', '2025-08-05 17:46:12'),
(75, 3, 'login', 'Usuario Liz inició sesión', '2025-08-05 17:53:43'),
(76, 2, 'login', 'Usuario Raul inició sesión', '2025-08-05 19:30:10'),
(77, 6, 'login', 'Usuario EstadiasUTM inició sesión', '2025-08-05 19:30:44'),
(78, 1, 'login', 'Usuario admin inició sesión', '2025-08-05 20:14:47'),
(79, 2, 'login', 'Usuario Raul inició sesión', '2025-08-05 20:59:11'),
(80, 6, 'login', 'Usuario EstadiasUTM inició sesión', '2025-08-05 20:59:58'),
(81, 3, 'login', 'Usuario liz inició sesión', '2025-08-05 21:16:46'),
(82, 2, 'login', 'Usuario Raul inició sesión', '2025-08-06 14:10:10'),
(83, 3, 'login', 'Usuario liz inició sesión', '2025-08-06 15:21:51'),
(84, 5, 'login', 'Usuario Hiromi inició sesión', '2025-08-06 15:59:21'),
(85, 1, 'login', 'Usuario admin inició sesión', '2025-08-06 20:57:04'),
(86, 5, 'login', 'Usuario Hiromi inició sesión', '2025-08-06 21:43:40'),
(87, 1, 'login', 'Usuario admin inició sesión', '2025-08-06 21:54:02'),
(88, 4, 'login', 'Usuario Jaasiel inició sesión', '2025-08-07 14:14:00'),
(89, 2, 'login', 'Usuario Raul inició sesión', '2025-08-07 14:53:06'),
(90, 3, 'login', 'Usuario liz inició sesión', '2025-08-07 15:06:47'),
(91, 5, 'login', 'Usuario Hiromi inició sesión', '2025-08-07 16:11:17'),
(92, 1, 'login', 'Usuario admin inició sesión', '2025-08-07 17:29:30'),
(93, 1, 'login', 'Usuario Admin inició sesión', '2025-08-07 18:20:12'),
(94, 3, 'login', 'Usuario Liz inició sesión', '2025-08-07 18:31:03'),
(95, 5, 'login', 'Usuario Hiromi inició sesión', '2025-08-07 18:37:40'),
(96, 1, 'login', 'Usuario admin inició sesión', '2025-08-07 18:49:03'),
(97, 2, 'login', 'Usuario Raul inició sesión', '2025-08-08 14:11:24'),
(98, 3, 'login', 'Usuario liz inició sesión', '2025-08-08 14:15:31'),
(99, 1, 'login', 'Usuario admin inició sesión', '2025-08-08 14:32:31');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tecnico_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movements`
--

INSERT INTO `movements` (`movement_id`, `product_id`, `bobina_id`, `movement_type_id`, `quantity`, `unit_price`, `total_amount`, `reference`, `notes`, `user_id`, `movement_date`, `created_at`, `updated_at`, `tecnico_id`) VALUES
(1, 1, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 20:13:41', '2025-07-11 20:13:41', '2025-07-11 20:13:41', NULL),
(2, 2, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 20:41:25', '2025-07-11 20:41:25', '2025-07-11 20:41:25', NULL),
(3, 3, NULL, 1, 5, NULL, NULL, NULL, NULL, NULL, '2025-07-11 20:44:13', '2025-07-11 20:44:13', '2025-07-11 20:44:13', NULL),
(4, 4, NULL, 1, 4, NULL, NULL, NULL, NULL, NULL, '2025-07-11 20:47:38', '2025-07-11 20:47:38', '2025-07-11 20:47:38', NULL),
(5, 5, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 20:53:33', '2025-07-11 20:53:33', '2025-07-11 20:53:33', NULL),
(6, 6, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 20:55:08', '2025-07-11 20:55:08', '2025-07-11 20:55:08', NULL),
(7, 7, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-11 21:04:49', '2025-07-11 21:04:49', '2025-07-11 21:04:49', NULL),
(8, 8, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-11 21:06:48', '2025-07-11 21:06:48', '2025-07-11 21:06:48', NULL),
(9, 9, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 21:09:04', '2025-07-11 21:09:04', '2025-07-11 21:09:04', NULL),
(10, 10, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 21:12:48', '2025-07-11 21:12:48', '2025-07-11 21:12:48', NULL),
(11, 11, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 21:19:43', '2025-07-11 21:19:43', '2025-07-11 21:19:43', NULL),
(12, 12, NULL, 1, 13, NULL, NULL, NULL, NULL, NULL, '2025-07-11 21:23:50', '2025-07-11 21:23:50', '2025-07-11 21:23:50', NULL),
(13, 13, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-11 21:28:28', '2025-07-11 21:28:28', '2025-07-11 21:28:28', NULL),
(14, 14, NULL, 1, 5, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:06:30', '2025-07-11 22:06:30', '2025-07-11 22:06:30', NULL),
(15, 15, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:11:20', '2025-07-11 22:11:20', '2025-07-11 22:11:20', NULL),
(16, 16, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:14:10', '2025-07-11 22:14:10', '2025-07-11 22:14:10', NULL),
(17, 17, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:18:39', '2025-07-11 22:18:39', '2025-07-11 22:18:39', NULL),
(18, 18, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:21:54', '2025-07-11 22:21:54', '2025-07-11 22:21:54', NULL),
(19, 19, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:24:18', '2025-07-11 22:24:18', '2025-07-11 22:24:18', NULL),
(20, 20, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:25:36', '2025-07-11 22:25:36', '2025-07-11 22:25:36', NULL),
(21, 21, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:29:08', '2025-07-11 22:29:08', '2025-07-11 22:29:08', NULL),
(22, 22, NULL, 1, 19, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:31:15', '2025-07-11 22:31:15', '2025-07-11 22:31:15', NULL),
(23, 23, NULL, 1, 6, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:35:43', '2025-07-11 22:35:43', '2025-07-11 22:35:43', NULL),
(24, 24, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:37:57', '2025-07-11 22:37:57', '2025-07-11 22:37:57', NULL),
(25, 25, NULL, 1, 7, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:46:06', '2025-07-11 22:46:06', '2025-07-11 22:46:06', NULL),
(26, 26, NULL, 1, 26, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:49:55', '2025-07-11 22:49:55', '2025-07-11 22:49:55', NULL),
(27, 27, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-11 22:57:16', '2025-07-11 22:57:16', '2025-07-11 22:57:16', NULL),
(28, 28, NULL, 1, 20, NULL, NULL, NULL, NULL, NULL, '2025-07-11 23:00:44', '2025-07-11 23:00:44', '2025-07-11 23:00:44', NULL),
(29, 29, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-11 23:03:42', '2025-07-11 23:03:42', '2025-07-11 23:03:42', NULL),
(30, 30, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-11 23:10:33', '2025-07-11 23:10:33', '2025-07-11 23:10:33', NULL),
(31, 31, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-11 23:12:13', '2025-07-11 23:12:13', '2025-07-11 23:12:13', NULL),
(32, 32, NULL, 1, 5, NULL, NULL, NULL, NULL, NULL, '2025-07-14 14:21:26', '2025-07-14 14:21:26', '2025-07-14 14:21:26', NULL),
(33, 33, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-14 14:28:52', '2025-07-14 14:28:52', '2025-07-14 14:28:52', NULL),
(34, 34, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 14:32:38', '2025-07-14 14:32:38', '2025-07-14 14:32:38', NULL),
(35, 35, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:14:29', '2025-07-14 15:14:29', '2025-07-14 15:14:29', NULL),
(36, 36, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:23:27', '2025-07-14 15:23:27', '2025-07-14 15:23:27', NULL),
(37, 37, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:27:58', '2025-07-14 15:27:58', '2025-07-14 15:27:58', NULL),
(38, 38, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:33:34', '2025-07-14 15:33:34', '2025-07-14 15:33:34', NULL),
(39, 39, NULL, 1, 5, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:37:16', '2025-07-14 15:37:16', '2025-07-14 15:37:16', NULL),
(40, 40, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:41:48', '2025-07-14 15:41:48', '2025-07-14 15:41:48', NULL),
(41, 41, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:45:34', '2025-07-14 15:45:34', '2025-07-14 15:45:34', NULL),
(42, 42, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:48:20', '2025-07-14 15:48:20', '2025-07-14 15:48:20', NULL),
(43, 43, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:51:18', '2025-07-14 15:51:18', '2025-07-14 15:51:18', NULL),
(44, 44, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 15:57:53', '2025-07-14 15:57:53', '2025-07-14 15:57:53', NULL),
(45, 45, NULL, 1, 10, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:01:44', '2025-07-14 16:01:44', '2025-07-14 16:01:44', NULL),
(46, 46, NULL, 1, 4, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:04:29', '2025-07-14 16:04:29', '2025-07-14 16:04:29', NULL),
(47, 47, NULL, 1, 15, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:08:38', '2025-07-14 16:08:38', '2025-07-14 16:08:38', NULL),
(48, 48, NULL, 1, 6, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:25:29', '2025-07-14 16:25:29', '2025-07-14 16:25:29', NULL),
(49, 49, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:29:28', '2025-07-14 16:29:28', '2025-07-14 16:29:28', NULL),
(50, 50, NULL, 1, 182, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:35:23', '2025-07-14 16:35:23', '2025-07-14 16:35:23', NULL),
(51, 51, NULL, 1, 8, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:42:36', '2025-07-14 16:42:36', '2025-07-14 16:42:36', NULL),
(52, 52, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:46:48', '2025-07-14 16:46:48', '2025-07-14 16:46:48', NULL),
(53, 53, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:55:40', '2025-07-14 16:55:40', '2025-07-14 16:55:40', NULL),
(54, 54, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 16:58:24', '2025-07-14 16:58:24', '2025-07-14 16:58:24', NULL),
(55, 55, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:02:05', '2025-07-14 17:02:05', '2025-07-14 17:02:05', NULL),
(56, 56, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:07:19', '2025-07-14 17:07:19', '2025-07-14 17:07:19', NULL),
(57, 57, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:19:11', '2025-07-14 17:19:11', '2025-07-14 17:19:11', NULL),
(58, 58, NULL, 1, 5, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:26:43', '2025-07-14 17:26:43', '2025-07-14 17:26:43', NULL),
(59, 59, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:34:44', '2025-07-14 17:34:44', '2025-07-14 17:34:44', NULL),
(60, 60, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:37:45', '2025-07-14 17:37:45', '2025-07-14 17:37:45', NULL),
(61, 61, NULL, 1, 4, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:44:37', '2025-07-14 17:44:37', '2025-07-14 17:44:37', NULL),
(62, 50, NULL, 2, -1, NULL, NULL, NULL, NULL, 1, '2025-07-14 17:47:09', '2025-07-14 17:47:09', '2025-07-14 17:47:09', NULL),
(63, 23, NULL, 2, -4, NULL, NULL, NULL, NULL, 1, '2025-07-14 17:47:53', '2025-07-14 17:47:53', '2025-07-14 17:47:53', NULL),
(64, 3, NULL, 2, -1, NULL, NULL, NULL, NULL, 1, '2025-07-14 17:48:05', '2025-07-14 17:48:05', '2025-07-14 17:48:05', NULL),
(65, 62, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:50:59', '2025-07-14 17:50:59', '2025-07-14 17:50:59', NULL),
(66, 63, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-14 17:52:00', '2025-07-14 17:52:00', '2025-07-14 17:52:00', NULL),
(67, 64, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 18:03:24', '2025-07-14 18:03:24', '2025-07-14 18:03:24', NULL),
(68, 65, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 18:03:32', '2025-07-14 18:03:32', '2025-07-14 18:03:32', NULL),
(69, 66, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 18:07:17', '2025-07-14 18:07:17', '2025-07-14 18:07:17', NULL),
(70, 67, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-14 18:09:55', '2025-07-14 18:09:55', '2025-07-14 18:09:55', NULL),
(71, 68, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-14 18:15:07', '2025-07-14 18:15:07', '2025-07-14 18:15:07', NULL),
(72, 69, NULL, 1, 4, NULL, NULL, NULL, NULL, NULL, '2025-07-14 18:26:42', '2025-07-14 18:26:42', '2025-07-14 18:26:42', NULL),
(73, 70, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 19:07:56', '2025-07-14 19:07:56', '2025-07-14 19:07:56', NULL),
(74, 23, NULL, 2, -2, NULL, NULL, NULL, NULL, 1, '2025-07-14 19:11:18', '2025-07-14 19:11:18', '2025-07-14 19:11:18', NULL),
(75, 71, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-14 19:47:15', '2025-07-14 19:47:15', '2025-07-14 19:47:15', NULL),
(76, 72, NULL, 1, 305, NULL, NULL, NULL, NULL, NULL, '2025-07-14 19:57:57', '2025-07-14 19:57:57', '2025-07-14 21:34:38', NULL),
(85, 12, NULL, 1, 56, NULL, NULL, NULL, NULL, 1, '2025-07-14 21:28:18', '2025-07-14 21:28:18', '2025-07-14 21:28:18', NULL),
(87, 23, NULL, 1, 30, NULL, NULL, NULL, NULL, 1, '2025-07-14 21:30:09', '2025-07-14 21:30:09', '2025-07-14 21:30:09', NULL),
(89, 4, NULL, 1, 10, NULL, NULL, NULL, NULL, 1, '2025-07-14 21:31:44', '2025-07-14 21:31:44', '2025-07-14 21:31:44', NULL),
(91, 3, NULL, 1, 10, NULL, NULL, NULL, NULL, 1, '2025-07-14 21:32:16', '2025-07-14 21:32:16', '2025-07-14 21:32:16', NULL),
(92, 73, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-14 22:51:26', '2025-07-14 22:51:26', '2025-07-14 22:51:26', NULL),
(93, 3, NULL, 2, -2, NULL, NULL, NULL, NULL, 1, '2025-07-14 23:00:52', '2025-07-14 23:00:52', '2025-07-14 23:00:52', NULL),
(94, 12, NULL, 2, -5, NULL, NULL, NULL, NULL, 1, '2025-07-14 23:01:59', '2025-07-14 23:01:59', '2025-07-14 23:01:59', NULL),
(95, 59, NULL, 2, -1, NULL, NULL, NULL, NULL, 1, '2025-07-14 23:03:30', '2025-07-14 23:03:30', '2025-07-14 23:03:30', NULL),
(96, 23, NULL, 2, -1, NULL, NULL, NULL, NULL, 1, '2025-07-14 23:04:57', '2025-07-14 23:04:57', '2025-07-14 23:04:57', NULL),
(97, 51, NULL, 2, -1, NULL, NULL, NULL, NULL, 1, '2025-07-14 23:11:51', '2025-07-14 23:11:51', '2025-07-14 23:11:51', NULL),
(98, 73, NULL, 2, -2, NULL, NULL, NULL, NULL, 1, '2025-07-14 23:13:22', '2025-07-14 23:13:22', '2025-07-14 23:13:22', NULL),
(99, 57, NULL, 2, -2, NULL, NULL, NULL, NULL, 1, '2025-07-14 23:24:02', '2025-07-14 23:24:02', '2025-07-14 23:24:02', NULL),
(100, 23, NULL, 2, -2, NULL, NULL, NULL, NULL, 1, '2025-07-15 14:15:43', '2025-07-15 14:15:43', '2025-07-15 14:15:43', NULL),
(101, 12, NULL, 2, -2, NULL, NULL, NULL, NULL, 1, '2025-07-15 14:18:00', '2025-07-15 14:18:00', '2025-07-15 14:18:00', NULL),
(102, 23, NULL, 1, 2, NULL, NULL, NULL, NULL, 1, '2025-07-15 14:19:49', '2025-07-15 14:19:49', '2025-07-15 14:19:49', NULL),
(103, 72, 2, 2, -305, NULL, NULL, NULL, NULL, 1, '2025-07-15 14:29:03', '2025-07-15 14:29:03', '2025-07-15 14:29:03', NULL),
(104, 57, NULL, 2, -1, NULL, NULL, NULL, NULL, 1, '2025-07-15 14:45:21', '2025-07-15 14:45:21', '2025-07-15 14:45:21', NULL),
(105, 3, NULL, 2, -1, NULL, NULL, NULL, NULL, 1, '2025-07-15 14:45:43', '2025-07-15 14:45:43', '2025-07-15 14:45:43', NULL),
(106, 47, NULL, 1, 43, NULL, NULL, NULL, NULL, 1, '2025-07-15 18:43:45', '2025-07-15 18:43:45', '2025-07-15 18:43:45', NULL),
(107, 74, NULL, 1, 31, NULL, NULL, NULL, NULL, NULL, '2025-07-15 18:51:58', '2025-07-15 18:51:58', '2025-07-15 18:51:58', NULL),
(108, 75, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-15 19:05:56', '2025-07-15 19:05:56', '2025-07-15 19:05:56', NULL),
(109, 76, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-15 20:39:30', '2025-07-15 20:39:30', '2025-07-15 20:39:30', NULL),
(110, 77, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-15 20:51:13', '2025-07-15 20:51:13', '2025-07-15 20:51:13', NULL),
(111, 78, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-15 21:00:02', '2025-07-15 21:00:02', '2025-07-15 21:00:02', NULL),
(114, 3, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-16 18:08:45', '2025-07-16 18:08:45', '2025-07-16 18:08:45', 4),
(115, 47, NULL, 2, -6, NULL, NULL, NULL, NULL, 2, '2025-07-16 18:11:24', '2025-07-16 18:11:24', '2025-07-16 18:11:24', 4),
(116, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-16 18:11:57', '2025-07-16 18:11:57', '2025-07-16 18:11:57', 2),
(117, 79, NULL, 1, 10, NULL, NULL, NULL, NULL, NULL, '2025-07-16 18:15:59', '2025-07-16 18:15:59', '2025-07-16 18:15:59', NULL),
(118, 23, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-16 18:23:33', '2025-07-16 18:23:33', '2025-07-16 18:23:33', 1),
(119, 34, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-16 18:31:21', '2025-07-16 18:31:21', '2025-07-16 18:31:21', 1),
(120, 33, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-16 18:31:21', '2025-07-16 18:31:21', '2025-07-16 18:31:21', 1),
(121, 47, NULL, 2, -3, NULL, NULL, NULL, NULL, 2, '2025-07-16 18:31:21', '2025-07-16 18:31:21', '2025-07-16 18:31:21', 1),
(122, 11, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-16 18:31:21', '2025-07-16 18:31:21', '2025-07-16 18:31:21', 1),
(124, 72, 8, 1, 305, NULL, NULL, NULL, NULL, 1, '2025-07-16 20:05:04', '2025-07-16 20:05:04', '2025-07-16 20:05:04', NULL),
(125, 72, 9, 1, 305, NULL, NULL, NULL, NULL, 1, '2025-07-16 20:08:12', '2025-07-16 20:08:12', '2025-07-16 20:08:12', NULL),
(126, 72, 10, 1, 305, NULL, NULL, NULL, NULL, 1, '2025-07-16 20:10:51', '2025-07-16 20:10:51', '2025-07-16 20:10:51', NULL),
(127, 72, 11, 1, 305, NULL, NULL, NULL, NULL, 1, '2025-07-16 20:11:10', '2025-07-16 20:11:10', '2025-07-16 20:11:10', NULL),
(128, 79, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-16 20:20:07', '2025-07-16 20:20:07', '2025-07-16 20:20:07', 2),
(129, 72, 8, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-07-16 20:56:46', '2025-07-16 20:56:46', '2025-07-16 20:56:46', 6),
(130, 79, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-16 20:56:46', '2025-07-16 20:56:46', '2025-07-16 20:56:46', 6),
(131, 80, NULL, 1, 10, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:02:45', '2025-07-16 21:02:45', '2025-07-16 21:02:45', NULL),
(132, 80, NULL, 1, 4, NULL, NULL, NULL, NULL, 2, '2025-07-16 22:50:03', '2025-07-16 22:50:03', '2025-07-16 22:50:03', 2),
(133, 57, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-16 22:50:37', '2025-07-16 22:50:37', '2025-07-16 22:50:37', 2),
(134, 47, NULL, 7, 4, NULL, NULL, NULL, NULL, 2, '2025-07-16 22:50:37', '2025-07-16 22:50:37', '2025-07-16 22:50:37', 2),
(135, 47, NULL, 7, 5, NULL, NULL, NULL, NULL, 2, '2025-07-16 23:24:10', '2025-07-16 23:24:10', '2025-07-16 23:24:10', 4),
(136, 80, NULL, 1, 5, NULL, NULL, NULL, NULL, 2, '2025-07-16 23:24:45', '2025-07-16 23:24:45', '2025-07-16 23:24:45', 4),
(137, 10, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-16 23:29:57', '2025-07-16 23:29:57', '2025-07-16 23:29:57', 9),
(138, 72, 8, 7, 224, NULL, NULL, NULL, NULL, 2, '2025-07-16 23:31:40', '2025-07-16 23:31:40', '2025-07-16 23:31:40', 6),
(139, 47, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-17 14:11:53', '2025-07-17 14:11:53', '2025-07-17 14:11:53', NULL),
(140, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-17 14:28:44', '2025-07-17 14:28:44', '2025-07-17 14:28:44', 6),
(141, 50, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-17 14:28:44', '2025-07-17 14:28:44', '2025-07-17 14:28:44', 6),
(142, 80, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-17 14:28:44', '2025-07-17 14:28:44', '2025-07-17 14:28:44', 6),
(143, 50, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-17 14:37:51', '2025-07-17 14:37:51', '2025-07-17 14:37:51', 6),
(144, 4, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-17 14:37:51', '2025-07-17 14:37:51', '2025-07-17 14:37:51', 6),
(145, 79, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-17 14:37:51', '2025-07-17 14:37:51', '2025-07-17 14:37:51', 6),
(146, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-17 14:37:51', '2025-07-17 14:37:51', '2025-07-17 14:37:51', 6),
(147, 75, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-17 14:37:51', '2025-07-17 14:37:51', '2025-07-17 14:37:51', 6),
(148, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-17 14:37:51', '2025-07-17 14:37:51', '2025-07-17 14:37:51', 6),
(149, 72, 9, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-07-17 14:37:51', '2025-07-17 14:37:51', '2025-07-17 14:37:51', 6),
(150, 72, 8, 2, -224, NULL, NULL, NULL, NULL, 2, '2025-07-17 14:37:51', '2025-07-17 14:37:51', '2025-07-17 14:37:51', 6),
(151, 76, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-17 14:37:51', '2025-07-17 14:37:51', '2025-07-17 14:37:51', 6),
(152, 12, NULL, 7, 2, NULL, NULL, NULL, NULL, 2, '2025-07-17 14:50:36', '2025-07-17 14:50:36', '2025-07-17 14:50:36', 1),
(153, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-17 17:14:40', '2025-07-17 17:14:40', '2025-07-17 17:14:40', 1),
(154, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-17 17:14:40', '2025-07-17 17:14:40', '2025-07-17 17:14:40', 1),
(155, 4, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-17 17:14:40', '2025-07-17 17:14:40', '2025-07-17 17:14:40', 1),
(156, 79, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-17 17:14:40', '2025-07-17 17:14:40', '2025-07-17 17:14:40', 1),
(157, 12, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-17 17:14:40', '2025-07-17 17:14:40', '2025-07-17 17:14:40', 1),
(158, 3, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-17 17:15:42', '2025-07-17 17:15:42', '2025-07-17 17:15:42', 10),
(159, 72, 8, 7, 170, NULL, NULL, NULL, NULL, 2, '2025-07-17 17:54:38', '2025-07-17 17:54:38', '2025-07-17 17:54:38', 6),
(160, 72, 9, 7, 305, NULL, NULL, NULL, NULL, 2, '2025-07-17 17:54:38', '2025-07-17 17:54:38', '2025-07-17 17:54:38', 6),
(161, 50, NULL, 7, 4, NULL, NULL, NULL, NULL, 2, '2025-07-17 17:54:38', '2025-07-17 17:54:38', '2025-07-17 17:54:38', 6),
(162, 76, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-17 17:54:38', '2025-07-17 17:54:38', '2025-07-17 17:54:38', 6),
(163, 75, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-17 17:54:38', '2025-07-17 17:54:38', '2025-07-17 17:54:38', 6),
(164, 79, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-17 17:54:38', '2025-07-17 17:54:38', '2025-07-17 17:54:38', 6),
(165, 80, NULL, 7, 2, NULL, NULL, NULL, NULL, 2, '2025-07-17 17:54:38', '2025-07-17 17:54:38', '2025-07-17 17:54:38', 6),
(166, 80, NULL, 7, 2, NULL, NULL, NULL, NULL, 2, '2025-07-17 18:02:33', '2025-07-17 18:02:33', '2025-07-17 18:02:33', 6),
(167, 75, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-17 18:12:09', '2025-07-17 18:12:09', '2025-07-17 18:12:09', 1),
(168, 12, NULL, 7, 4, NULL, NULL, NULL, NULL, 2, '2025-07-17 18:12:09', '2025-07-17 18:12:09', '2025-07-17 18:12:09', 1),
(169, 76, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-17 18:12:09', '2025-07-17 18:12:09', '2025-07-17 18:12:09', 1),
(170, 72, 8, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-17 18:12:09', '2025-07-17 18:12:09', '2025-07-17 18:12:09', 1),
(171, 73, NULL, 1, 3, NULL, NULL, NULL, NULL, 2, '2025-07-17 20:38:25', '2025-07-17 20:38:25', '2025-07-17 20:38:25', NULL),
(172, 75, NULL, 1, 6, NULL, NULL, NULL, NULL, 2, '2025-07-17 20:38:25', '2025-07-17 20:38:25', '2025-07-17 20:38:25', NULL),
(173, 81, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-17 20:41:56', '2025-07-17 20:41:56', '2025-07-17 20:41:56', NULL),
(174, 42, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:08:24', '2025-07-18 14:08:24', '2025-07-18 14:08:24', 9),
(175, 47, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:20:15', '2025-07-18 14:20:15', '2025-07-18 14:20:15', 1),
(176, 47, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:32:02', '2025-07-18 14:32:02', '2025-07-18 14:32:02', 3),
(177, 80, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:32:02', '2025-07-18 14:32:02', '2025-07-18 14:32:02', 3),
(178, 68, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:34:00', '2025-07-18 14:34:00', '2025-07-18 14:34:00', 1),
(179, 47, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:41:51', '2025-07-18 14:41:51', '2025-07-18 14:41:51', 1),
(180, 80, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:41:51', '2025-07-18 14:41:51', '2025-07-18 14:41:51', 1),
(181, 72, 9, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:43:05', '2025-07-18 14:43:05', '2025-07-18 14:43:05', 2),
(182, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:43:05', '2025-07-18 14:43:05', '2025-07-18 14:43:05', 2),
(183, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:43:05', '2025-07-18 14:43:05', '2025-07-18 14:43:05', 2),
(184, 50, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:58:05', '2025-07-18 14:58:05', '2025-07-18 14:58:05', 6),
(185, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:58:05', '2025-07-18 14:58:05', '2025-07-18 14:58:05', 6),
(186, 4, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:58:05', '2025-07-18 14:58:05', '2025-07-18 14:58:05', 6),
(187, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:58:05', '2025-07-18 14:58:05', '2025-07-18 14:58:05', 6),
(188, 75, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:58:05', '2025-07-18 14:58:05', '2025-07-18 14:58:05', 6),
(189, 79, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:58:05', '2025-07-18 14:58:05', '2025-07-18 14:58:05', 6),
(190, 72, 10, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-18 14:58:05', '2025-07-18 14:58:05', '2025-07-18 14:58:05', 6),
(191, 80, NULL, 7, 4, NULL, NULL, NULL, NULL, 2, '2025-07-18 21:19:54', '2025-07-18 21:19:54', '2025-07-18 21:19:54', 6),
(192, 72, 10, 7, 304, NULL, NULL, NULL, NULL, 2, '2025-07-18 21:37:59', '2025-07-18 21:37:59', '2025-07-18 21:37:59', NULL),
(193, 72, 10, 3, 304, NULL, NULL, NULL, NULL, 2, '2025-07-18 21:38:43', '2025-07-18 21:38:43', '2025-07-18 21:38:43', 6),
(194, 81, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-18 22:01:40', '2025-07-18 22:01:40', '2025-07-18 22:01:40', 3),
(195, 10, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-18 23:19:51', '2025-07-18 23:19:51', '2025-07-18 23:19:51', 11),
(196, 68, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-18 23:39:17', '2025-07-18 23:39:17', '2025-07-18 23:39:17', 1),
(197, 47, NULL, 4, -4, NULL, NULL, NULL, NULL, 2, '2025-07-19 14:29:03', '2025-07-19 14:29:03', '2025-07-19 14:29:03', 12),
(198, 4, NULL, 4, -1, NULL, NULL, NULL, NULL, 2, '2025-07-19 14:29:03', '2025-07-19 14:29:03', '2025-07-19 14:29:03', 12),
(199, 50, NULL, 4, -4, NULL, NULL, NULL, NULL, 2, '2025-07-19 14:29:03', '2025-07-19 14:29:03', '2025-07-19 14:29:03', 12),
(200, 3, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-19 14:37:31', '2025-07-19 14:37:31', '2025-07-19 14:37:31', 3),
(201, 72, 10, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-07-19 15:51:30', '2025-07-19 15:51:30', '2025-07-19 15:51:30', 6),
(202, 4, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-19 19:58:10', '2025-07-19 19:58:10', '2025-07-19 19:58:10', 2),
(203, 3, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-19 20:00:17', '2025-07-19 20:00:17', '2025-07-19 20:00:17', 3),
(204, 72, 10, 7, 306, NULL, NULL, NULL, NULL, 2, '2025-07-19 20:23:50', '2025-07-19 20:23:50', '2025-07-19 20:23:50', 3),
(205, 72, 10, 7, 305, NULL, NULL, NULL, NULL, 2, '2025-07-19 20:24:56', '2025-07-19 20:24:56', '2025-07-19 20:24:56', 3),
(206, 79, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-21 14:46:16', '2025-07-21 14:46:16', '2025-07-21 14:46:16', 1),
(207, 80, NULL, 2, -5, NULL, NULL, NULL, NULL, 2, '2025-07-21 14:46:16', '2025-07-21 14:46:16', '2025-07-21 14:46:16', 1),
(208, 23, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-21 14:46:16', '2025-07-21 14:46:16', '2025-07-21 14:46:16', 1),
(209, 58, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-21 14:46:16', '2025-07-21 14:46:16', '2025-07-21 14:46:16', 1),
(210, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-21 14:46:16', '2025-07-21 14:46:16', '2025-07-21 14:46:16', 1),
(211, 75, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-21 14:46:16', '2025-07-21 14:46:16', '2025-07-21 14:46:16', 1),
(212, 3, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-21 14:46:16', '2025-07-21 14:46:16', '2025-07-21 14:46:16', 1),
(213, 62, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-21 15:01:38', '2025-07-21 15:01:38', '2025-07-21 15:01:38', 2),
(214, 66, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-21 15:01:38', '2025-07-21 15:01:38', '2025-07-21 15:01:38', 2),
(215, 32, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-21 15:01:38', '2025-07-21 15:01:38', '2025-07-21 15:01:38', 2),
(219, 72, 8, 2, -169, NULL, NULL, NULL, NULL, 2, '2025-07-21 15:11:01', '2025-07-21 15:11:01', '2025-07-21 15:11:01', 1),
(221, 72, 10, 3, -140, NULL, NULL, NULL, NULL, 2, '2025-07-21 15:34:08', '2025-07-21 15:34:08', '2025-07-21 15:34:08', NULL),
(222, 23, NULL, 7, 4, NULL, NULL, NULL, NULL, 2, '2025-07-21 16:56:18', '2025-07-21 16:56:18', '2025-07-21 16:56:18', 1),
(223, 82, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-21 16:56:33', '2025-07-21 16:56:33', '2025-07-21 16:56:33', NULL),
(224, 82, NULL, 2, -2, NULL, NULL, NULL, NULL, 1, '2025-07-21 16:57:25', '2025-07-21 16:57:25', '2025-07-21 16:57:25', NULL),
(225, 83, NULL, 1, 100, NULL, NULL, NULL, NULL, NULL, '2025-07-21 17:08:36', '2025-07-21 17:08:36', '2025-07-21 17:08:36', NULL),
(226, 84, NULL, 1, 4, NULL, NULL, NULL, NULL, NULL, '2025-07-21 21:19:47', '2025-07-21 21:19:47', '2025-07-21 21:19:47', NULL),
(227, 85, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-21 21:22:57', '2025-07-21 21:22:57', '2025-07-21 21:22:57', NULL),
(228, 86, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-21 21:28:48', '2025-07-21 21:28:48', '2025-07-21 21:28:48', NULL),
(229, 80, NULL, 1, 35, NULL, NULL, NULL, NULL, 2, '2025-07-21 21:38:17', '2025-07-21 21:38:17', '2025-07-21 21:38:17', NULL),
(230, 80, NULL, 2, -5, NULL, NULL, NULL, NULL, 2, '2025-07-21 21:39:57', '2025-07-21 21:39:57', '2025-07-21 21:39:57', 3),
(231, 47, NULL, 2, -3, NULL, NULL, NULL, NULL, 2, '2025-07-21 21:39:57', '2025-07-21 21:39:57', '2025-07-21 21:39:57', 3),
(232, 50, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-21 21:39:57', '2025-07-21 21:39:57', '2025-07-21 21:39:57', 3),
(233, 25, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-21 21:39:57', '2025-07-21 21:39:57', '2025-07-21 21:39:57', 3),
(234, 79, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-21 21:41:47', '2025-07-21 21:41:47', '2025-07-21 21:41:47', 3),
(235, 66, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-21 23:02:46', '2025-07-21 23:02:46', '2025-07-21 23:02:46', 2),
(236, 62, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-21 23:02:46', '2025-07-21 23:02:46', '2025-07-21 23:02:46', 2),
(237, 87, NULL, 1, 8, NULL, NULL, NULL, NULL, NULL, '2025-07-21 23:07:16', '2025-07-21 23:07:16', '2025-07-21 23:07:16', NULL),
(238, 88, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-21 23:09:46', '2025-07-21 23:09:46', '2025-07-21 23:09:46', NULL),
(239, 89, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-21 23:12:16', '2025-07-21 23:12:16', '2025-07-21 23:12:16', NULL),
(240, 90, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-21 23:14:20', '2025-07-21 23:14:20', '2025-07-21 23:14:20', NULL),
(241, 91, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-21 23:16:39', '2025-07-21 23:16:39', '2025-07-21 23:16:39', NULL),
(242, 92, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-21 23:32:09', '2025-07-21 23:32:09', '2025-07-21 23:32:09', NULL),
(243, 47, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-21 23:32:54', '2025-07-21 23:32:54', '2025-07-21 23:32:54', 3),
(244, 79, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-22 14:17:08', '2025-07-22 14:17:08', '2025-07-22 14:17:08', 3),
(245, 47, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-22 14:17:08', '2025-07-22 14:17:08', '2025-07-22 14:17:08', 3),
(246, 80, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-07-22 14:24:04', '2025-07-22 14:24:04', '2025-07-22 14:24:04', 2),
(247, 47, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-07-22 14:24:04', '2025-07-22 14:24:04', '2025-07-22 14:24:04', 2),
(248, 79, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-22 14:24:04', '2025-07-22 14:24:04', '2025-07-22 14:24:04', 2),
(249, 50, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-07-22 14:24:04', '2025-07-22 14:24:04', '2025-07-22 14:24:04', 2),
(250, 76, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-22 14:24:04', '2025-07-22 14:24:04', '2025-07-22 14:24:04', 2),
(251, 3, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-22 14:24:04', '2025-07-22 14:24:04', '2025-07-22 14:24:04', 2),
(252, 84, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-22 15:01:47', '2025-07-22 15:01:47', '2025-07-22 15:01:47', 1),
(253, 85, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-22 15:01:47', '2025-07-22 15:01:47', '2025-07-22 15:01:47', 1),
(254, 92, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-22 15:01:47', '2025-07-22 15:01:47', '2025-07-22 15:01:47', 1),
(255, 86, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-22 15:01:47', '2025-07-22 15:01:47', '2025-07-22 15:01:47', 1),
(256, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-22 15:01:47', '2025-07-22 15:01:47', '2025-07-22 15:01:47', 1),
(257, 47, NULL, 7, 2, NULL, NULL, NULL, NULL, 2, '2025-07-22 15:02:06', '2025-07-22 15:02:06', '2025-07-22 15:02:06', 2),
(258, 93, NULL, 1, 6, NULL, NULL, NULL, NULL, NULL, '2025-07-22 15:11:40', '2025-07-22 15:11:40', '2025-07-22 15:11:40', NULL),
(259, 94, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-07-22 15:16:11', '2025-07-22 15:16:11', '2025-07-22 15:16:11', NULL),
(260, 95, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-22 15:23:59', '2025-07-22 15:23:59', '2025-07-22 15:23:59', NULL),
(261, 47, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-22 15:32:37', '2025-07-22 15:32:37', '2025-07-22 15:32:37', 6),
(262, 51, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-22 15:32:37', '2025-07-22 15:32:37', '2025-07-22 15:32:37', 6),
(263, 80, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-22 15:32:37', '2025-07-22 15:32:37', '2025-07-22 15:32:37', 6),
(264, 58, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-22 16:26:52', '2025-07-22 16:26:52', '2025-07-22 16:26:52', 6),
(265, 80, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-22 16:26:52', '2025-07-22 16:26:52', '2025-07-22 16:26:52', 6),
(266, 72, 11, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-07-22 21:41:17', '2025-07-22 21:41:17', '2025-07-22 21:41:17', 2),
(267, 72, 11, 7, 240, NULL, NULL, NULL, NULL, 2, '2025-07-22 22:43:59', '2025-07-22 22:43:59', '2025-07-22 22:43:59', 2),
(268, 72, 12, 1, 305, NULL, NULL, NULL, NULL, 2, '2025-07-22 23:14:08', '2025-07-22 23:14:08', '2025-07-22 23:14:08', 7),
(269, 72, 13, 1, 305, NULL, NULL, NULL, NULL, 2, '2025-07-22 23:15:37', '2025-07-22 23:15:37', '2025-07-22 23:15:37', 7),
(270, 72, 14, 1, 305, NULL, NULL, NULL, NULL, 2, '2025-07-22 23:15:37', '2025-07-22 23:15:37', '2025-07-22 23:15:37', 7),
(271, 72, 15, 1, 305, NULL, NULL, NULL, NULL, 2, '2025-07-22 23:15:37', '2025-07-22 23:15:37', '2025-07-22 23:15:37', 7),
(272, 96, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-22 23:20:07', '2025-07-22 23:20:07', '2025-07-22 23:20:07', NULL),
(273, 97, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-22 23:22:25', '2025-07-22 23:22:25', '2025-07-22 23:22:25', NULL),
(274, 98, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-22 23:25:48', '2025-07-22 23:25:48', '2025-07-22 23:25:48', NULL),
(275, 80, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(276, 96, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(277, 70, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(278, 72, 12, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(279, 97, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(280, 28, NULL, 2, -7, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(281, 98, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(282, 80, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(283, 96, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(284, 70, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(285, 97, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(286, 28, NULL, 2, -7, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(287, 98, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(288, 80, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(289, 96, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(290, 70, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(291, 97, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(292, 28, NULL, 2, -7, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(293, 98, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:48', '2025-07-23 14:18:48', '2025-07-23 14:18:48', 1),
(294, 80, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:49', '2025-07-23 14:18:49', '2025-07-23 14:18:49', 1),
(295, 96, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:49', '2025-07-23 14:18:49', '2025-07-23 14:18:49', 1),
(296, 70, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:49', '2025-07-23 14:18:49', '2025-07-23 14:18:49', 1),
(297, 97, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:49', '2025-07-23 14:18:49', '2025-07-23 14:18:49', 1),
(298, 28, NULL, 2, -7, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:49', '2025-07-23 14:18:49', '2025-07-23 14:18:49', 1),
(299, 98, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:18:49', '2025-07-23 14:18:49', '2025-07-23 14:18:49', 1),
(300, 42, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:23:42', '2025-07-23 14:23:42', '2025-07-23 14:23:42', 1),
(301, 80, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:40:56', '2025-07-23 14:40:56', '2025-07-23 14:40:56', 4),
(302, 47, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:40:56', '2025-07-23 14:40:56', '2025-07-23 14:40:56', 4),
(303, 23, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:40:56', '2025-07-23 14:40:56', '2025-07-23 14:40:56', 4),
(304, 83, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:40:56', '2025-07-23 14:40:56', '2025-07-23 14:40:56', 4),
(305, 7, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:54:05', '2025-07-23 14:54:05', '2025-07-23 14:54:05', 1),
(306, 83, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:54:05', '2025-07-23 14:54:05', '2025-07-23 14:54:05', 1),
(307, 28, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:54:05', '2025-07-23 14:54:05', '2025-07-23 14:54:05', 1),
(308, 47, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 14:54:05', '2025-07-23 14:54:05', '2025-07-23 14:54:05', 1),
(309, 92, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-23 15:04:17', '2025-07-23 15:04:17', '2025-07-23 15:04:17', 1),
(310, 3, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 15:44:27', '2025-07-23 15:44:27', '2025-07-23 15:44:27', 7),
(311, 80, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 15:44:27', '2025-07-23 15:44:27', '2025-07-23 15:44:27', 7),
(312, 47, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 15:44:27', '2025-07-23 15:44:27', '2025-07-23 15:44:27', 7),
(313, 50, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-23 15:44:27', '2025-07-23 15:44:27', '2025-07-23 15:44:27', 7),
(314, 6, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 16:30:31', '2025-07-23 16:30:31', '2025-07-23 16:30:31', 2),
(315, 52, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 16:30:31', '2025-07-23 16:30:31', '2025-07-23 16:30:31', 2),
(316, 10, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 16:30:31', '2025-07-23 16:30:31', '2025-07-23 16:30:31', 2),
(317, 67, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 16:30:31', '2025-07-23 16:30:31', '2025-07-23 16:30:31', 2),
(318, 28, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 16:30:31', '2025-07-23 16:30:31', '2025-07-23 16:30:31', 2),
(319, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-23 17:37:00', '2025-07-23 17:37:00', '2025-07-23 17:37:00', 2),
(320, 80, NULL, 1, 35, NULL, NULL, NULL, NULL, 2, '2025-07-23 17:37:42', '2025-07-23 17:37:42', '2025-07-23 17:37:42', NULL),
(321, 23, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-23 22:13:32', '2025-07-23 22:13:32', '2025-07-23 22:13:32', 4),
(322, 7, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 22:15:29', '2025-07-23 22:15:29', '2025-07-23 22:15:29', 4),
(323, 80, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 22:15:29', '2025-07-23 22:15:29', '2025-07-23 22:15:29', 4),
(324, 47, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 22:15:29', '2025-07-23 22:15:29', '2025-07-23 22:15:29', 4),
(325, 83, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-23 22:15:29', '2025-07-23 22:15:29', '2025-07-23 22:15:29', 4),
(332, 10, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-24 14:14:43', '2025-07-24 14:14:43', '2025-07-24 14:14:43', 11),
(333, 42, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-24 14:15:30', '2025-07-24 14:15:30', '2025-07-24 14:15:30', 7),
(334, 72, 10, 2, -233, NULL, NULL, NULL, NULL, 2, '2025-07-24 14:18:23', '2025-07-24 14:18:23', '2025-07-24 14:18:23', 4),
(335, 72, 10, 7, 167, NULL, NULL, NULL, NULL, 2, '2025-07-24 14:18:44', '2025-07-24 14:18:44', '2025-07-24 14:18:44', 4),
(336, 48, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-24 14:24:36', '2025-07-24 14:24:36', '2025-07-24 14:24:36', 4),
(337, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-24 14:24:36', '2025-07-24 14:24:36', '2025-07-24 14:24:36', 4),
(338, 72, 13, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-07-24 14:24:36', '2025-07-24 14:24:36', '2025-07-24 14:24:36', 4),
(339, 67, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-24 14:35:23', '2025-07-24 14:35:23', '2025-07-24 14:35:23', 4),
(345, 52, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-24 15:17:35', '2025-07-24 15:17:35', '2025-07-24 15:17:35', 1),
(346, 93, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-24 15:17:35', '2025-07-24 15:17:35', '2025-07-24 15:17:35', 1),
(347, 80, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-24 15:17:35', '2025-07-24 15:17:35', '2025-07-24 15:17:35', 1),
(348, 47, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-24 15:17:35', '2025-07-24 15:17:35', '2025-07-24 15:17:35', 1),
(350, 99, NULL, 1, 10, NULL, NULL, NULL, NULL, NULL, '2025-07-24 15:20:02', '2025-07-24 15:20:02', '2025-07-24 15:20:02', NULL),
(351, 99, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-24 15:25:39', '2025-07-24 15:25:39', '2025-07-24 15:25:39', 1),
(353, 72, 10, 2, -167, NULL, NULL, NULL, NULL, 2, '2025-07-24 15:27:05', '2025-07-24 15:27:05', '2025-07-24 15:27:05', 1),
(354, 47, NULL, 1, 200, NULL, NULL, NULL, NULL, 2, '2025-07-24 15:27:51', '2025-07-24 15:27:51', '2025-07-24 15:27:51', NULL),
(355, 47, NULL, 3, 7, NULL, NULL, NULL, NULL, 2, '2025-07-24 15:39:29', '2025-07-24 15:39:29', '2025-07-24 15:39:29', NULL),
(356, 80, NULL, 7, 2, NULL, NULL, NULL, NULL, 2, '2025-07-24 15:42:00', '2025-07-24 15:42:00', '2025-07-24 15:42:00', 3),
(357, 100, NULL, 1, 5, NULL, NULL, NULL, NULL, NULL, '2025-07-24 20:25:13', '2025-07-24 20:25:13', '2025-07-24 20:25:13', NULL),
(358, 48, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-25 14:17:44', '2025-07-25 14:17:44', '2025-07-25 14:17:44', 4),
(359, 80, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-07-25 14:32:29', '2025-07-25 14:32:29', '2025-07-25 14:32:29', 1),
(360, 47, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-07-25 14:32:29', '2025-07-25 14:32:29', '2025-07-25 14:32:29', 1),
(361, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-25 15:07:43', '2025-07-25 15:07:43', '2025-07-25 15:07:43', 4),
(362, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-25 15:07:43', '2025-07-25 15:07:43', '2025-07-25 15:07:43', 4),
(363, 83, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-25 15:07:43', '2025-07-25 15:07:43', '2025-07-25 15:07:43', 4),
(364, 72, 11, 2, -240, NULL, NULL, NULL, NULL, 2, '2025-07-25 15:07:43', '2025-07-25 15:07:43', '2025-07-25 15:07:43', 4),
(365, 80, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-25 15:10:18', '2025-07-25 15:10:18', '2025-07-25 15:10:18', 2),
(366, 47, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-25 15:10:18', '2025-07-25 15:10:18', '2025-07-25 15:10:18', 2),
(367, 79, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-25 15:10:18', '2025-07-25 15:10:18', '2025-07-25 15:10:18', 2),
(368, 72, 14, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-07-25 15:10:18', '2025-07-25 15:10:18', '2025-07-25 15:10:18', 2),
(369, 12, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-25 15:10:18', '2025-07-25 15:10:18', '2025-07-25 15:10:18', 2),
(370, 57, NULL, 1, 2, NULL, NULL, NULL, NULL, 2, '2025-07-25 15:16:40', '2025-07-25 15:16:40', '2025-07-25 15:16:40', NULL),
(371, 67, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-25 15:16:40', '2025-07-25 15:16:40', '2025-07-25 15:16:40', NULL),
(372, 80, NULL, 7, 3, NULL, NULL, NULL, NULL, 2, '2025-07-25 15:16:40', '2025-07-25 15:16:40', '2025-07-25 15:16:40', NULL),
(373, 63, NULL, 1, 1, NULL, NULL, NULL, NULL, 2, '2025-07-25 18:47:43', '2025-07-25 18:47:43', '2025-07-25 18:47:43', NULL),
(374, 101, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-25 18:52:16', '2025-07-25 18:52:16', '2025-07-25 18:52:16', NULL),
(375, 102, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-25 18:54:33', '2025-07-25 18:54:33', '2025-07-25 18:54:33', NULL),
(376, 103, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-07-25 18:56:22', '2025-07-25 18:56:22', '2025-07-25 18:56:22', NULL),
(377, 51, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-25 19:15:42', '2025-07-25 19:15:42', '2025-07-25 19:15:42', 2),
(378, 12, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-25 21:47:27', '2025-07-25 21:47:27', '2025-07-25 21:47:27', 2),
(379, 51, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-25 21:47:27', '2025-07-25 21:47:27', '2025-07-25 21:47:27', 2),
(380, 80, NULL, 7, 3, NULL, NULL, NULL, NULL, 2, '2025-07-25 23:10:16', '2025-07-25 23:10:16', '2025-07-25 23:10:16', 4),
(381, 47, NULL, 7, 3, NULL, NULL, NULL, NULL, 2, '2025-07-25 23:10:16', '2025-07-25 23:10:16', '2025-07-25 23:10:16', 4),
(382, 83, NULL, 7, 2, NULL, NULL, NULL, NULL, 2, '2025-07-25 23:10:16', '2025-07-25 23:10:16', '2025-07-25 23:10:16', 4),
(383, 80, NULL, 7, 4, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:15:27', '2025-07-26 14:15:27', '2025-07-26 14:15:27', 1),
(384, 47, NULL, 7, 7, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:15:27', '2025-07-26 14:15:27', '2025-07-26 14:15:27', 1),
(385, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:23:07', '2025-07-26 14:23:07', '2025-07-26 14:23:07', 1),
(386, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:23:07', '2025-07-26 14:23:07', '2025-07-26 14:23:07', 1),
(387, 73, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:23:07', '2025-07-26 14:23:07', '2025-07-26 14:23:07', 1),
(388, 57, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:23:07', '2025-07-26 14:23:07', '2025-07-26 14:23:07', 1),
(389, 61, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:23:07', '2025-07-26 14:23:07', '2025-07-26 14:23:07', 1),
(390, 80, NULL, 2, -10, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:53:42', '2025-07-26 14:53:42', '2025-07-26 14:53:42', 1),
(391, 47, NULL, 2, -7, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:53:42', '2025-07-26 14:53:42', '2025-07-26 14:53:42', 1),
(392, 50, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:53:42', '2025-07-26 14:53:42', '2025-07-26 14:53:42', 1),
(393, 79, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:53:42', '2025-07-26 14:53:42', '2025-07-26 14:53:42', 1),
(394, 83, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:53:42', '2025-07-26 14:53:42', '2025-07-26 14:53:42', 1),
(395, 72, 15, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:53:42', '2025-07-26 14:53:42', '2025-07-26 14:53:42', 1),
(396, 74, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:53:42', '2025-07-26 14:53:42', '2025-07-26 14:53:42', 1),
(397, 23, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:53:42', '2025-07-26 14:53:42', '2025-07-26 14:53:42', 1),
(398, 3, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-26 14:53:42', '2025-07-26 14:53:42', '2025-07-26 14:53:42', 1),
(399, 80, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-26 17:20:31', '2025-07-26 17:20:31', '2025-07-26 17:20:31', NULL),
(400, 80, NULL, 7, 7, NULL, NULL, NULL, NULL, 2, '2025-07-26 19:23:15', '2025-07-26 19:23:15', '2025-07-26 19:23:15', 2),
(401, 47, NULL, 7, 7, NULL, NULL, NULL, NULL, 2, '2025-07-26 19:23:15', '2025-07-26 19:23:15', '2025-07-26 19:23:15', 2),
(402, 79, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-26 19:23:15', '2025-07-26 19:23:15', '2025-07-26 19:23:15', 2),
(403, 72, 13, 7, 187, NULL, NULL, NULL, NULL, 2, '2025-07-26 19:23:15', '2025-07-26 19:23:15', '2025-07-26 19:23:15', 2),
(404, 72, 14, 7, 170, NULL, NULL, NULL, NULL, 2, '2025-07-26 19:25:37', '2025-07-26 19:25:37', '2025-07-26 19:25:37', 4),
(405, 72, 15, 7, 305, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:23:55', '2025-07-28 14:23:55', '2025-07-28 14:23:55', 6),
(406, 80, NULL, 7, 10, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:23:55', '2025-07-28 14:23:55', '2025-07-28 14:23:55', 6),
(407, 83, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:23:55', '2025-07-28 14:23:55', '2025-07-28 14:23:55', 6),
(408, 47, NULL, 7, 5, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:23:55', '2025-07-28 14:23:55', '2025-07-28 14:23:55', 6),
(409, 50, NULL, 7, 2, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:23:55', '2025-07-28 14:23:55', '2025-07-28 14:23:55', 6),
(410, 72, 12, 7, 163, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:29:01', '2025-07-28 14:29:01', '2025-07-28 14:29:01', 1),
(411, 72, 11, 7, 96, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:29:32', '2025-07-28 14:29:32', '2025-07-28 14:29:32', 2),
(412, 72, 10, 7, 97, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:30:04', '2025-07-28 14:30:04', '2025-07-28 14:30:04', 6),
(413, 66, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:41:21', '2025-07-28 14:41:21', '2025-07-28 14:41:21', 2),
(414, 93, NULL, 2, -5, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:41:21', '2025-07-28 14:41:21', '2025-07-28 14:41:21', 2),
(415, 87, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:41:21', '2025-07-28 14:41:21', '2025-07-28 14:41:21', 2),
(416, 94, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:41:21', '2025-07-28 14:41:21', '2025-07-28 14:41:21', 2),
(417, 71, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:41:21', '2025-07-28 14:41:21', '2025-07-28 14:41:21', 2),
(418, 88, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:41:21', '2025-07-28 14:41:21', '2025-07-28 14:41:21', 2),
(419, 91, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:41:21', '2025-07-28 14:41:21', '2025-07-28 14:41:21', 2),
(420, 90, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:41:21', '2025-07-28 14:41:21', '2025-07-28 14:41:21', 2),
(421, 20, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-28 14:41:21', '2025-07-28 14:41:21', '2025-07-28 14:41:21', 2),
(422, 80, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-07-28 15:01:37', '2025-07-28 15:01:37', '2025-07-28 15:01:37', 1),
(423, 47, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-07-28 15:01:37', '2025-07-28 15:01:37', '2025-07-28 15:01:37', 1),
(424, 72, 15, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-07-28 15:01:37', '2025-07-28 15:01:37', '2025-07-28 15:01:37', 1),
(425, 72, 11, 2, -96, NULL, NULL, NULL, NULL, 2, '2025-07-28 15:11:34', '2025-07-28 15:11:34', '2025-07-28 15:11:34', 6),
(426, 75, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-28 18:00:40', '2025-07-28 18:00:40', '2025-07-28 18:00:40', 6),
(427, 83, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-28 18:00:41', '2025-07-28 18:00:41', '2025-07-28 18:00:41', 6),
(428, 47, NULL, 7, 7, NULL, NULL, NULL, NULL, 2, '2025-07-28 23:17:48', '2025-07-28 23:17:48', '2025-07-28 23:17:48', 1),
(429, 80, NULL, 7, 5, NULL, NULL, NULL, NULL, 2, '2025-07-28 23:17:48', '2025-07-28 23:17:48', '2025-07-28 23:17:48', 1),
(430, 72, 15, 7, 239, NULL, NULL, NULL, NULL, 2, '2025-07-28 23:17:48', '2025-07-28 23:17:48', '2025-07-28 23:17:48', 1),
(431, 47, NULL, 2, -6, NULL, NULL, NULL, NULL, 2, '2025-07-29 15:17:22', '2025-07-29 15:17:22', '2025-07-29 15:17:22', 1),
(432, 80, NULL, 2, -6, NULL, NULL, NULL, NULL, 2, '2025-07-29 15:17:22', '2025-07-29 15:17:22', '2025-07-29 15:17:22', 1),
(433, 73, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-29 15:17:22', '2025-07-29 15:17:22', '2025-07-29 15:17:22', 1),
(434, 100, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-29 15:17:22', '2025-07-29 15:17:22', '2025-07-29 15:17:22', 1),
(435, 50, NULL, 2, -6, NULL, NULL, NULL, NULL, 2, '2025-07-29 15:17:22', '2025-07-29 15:17:22', '2025-07-29 15:17:22', 1),
(436, 3, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-29 15:17:22', '2025-07-29 15:17:22', '2025-07-29 15:17:22', 1),
(437, 83, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-29 15:17:22', '2025-07-29 15:17:22', '2025-07-29 15:17:22', 1),
(438, 79, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-29 15:17:22', '2025-07-29 15:17:22', '2025-07-29 15:17:22', 1);
INSERT INTO `movements` (`movement_id`, `product_id`, `bobina_id`, `movement_type_id`, `quantity`, `unit_price`, `total_amount`, `reference`, `notes`, `user_id`, `movement_date`, `created_at`, `updated_at`, `tecnico_id`) VALUES
(439, 72, 10, 2, -97, NULL, NULL, NULL, NULL, 2, '2025-07-29 15:17:22', '2025-07-29 15:17:22', '2025-07-29 15:17:22', 1),
(440, 72, 15, 2, -239, NULL, NULL, NULL, NULL, 2, '2025-07-29 15:17:22', '2025-07-29 15:17:22', '2025-07-29 15:17:22', 1),
(441, 50, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-29 15:18:33', '2025-07-29 15:18:33', '2025-07-29 15:18:33', 12),
(442, 83, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-29 15:18:33', '2025-07-29 15:18:33', '2025-07-29 15:18:33', 12),
(443, 4, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-29 15:18:33', '2025-07-29 15:18:33', '2025-07-29 15:18:33', 12),
(444, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-29 15:18:33', '2025-07-29 15:18:33', '2025-07-29 15:18:33', 12),
(445, 4, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-29 21:49:41', '2025-07-29 21:49:41', '2025-07-29 21:49:41', 1),
(446, 75, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-29 21:49:41', '2025-07-29 21:49:41', '2025-07-29 21:49:41', 1),
(447, 75, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-29 23:13:46', '2025-07-29 23:13:46', '2025-07-29 23:13:46', 1),
(448, 37, 1, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-07-30 14:11:06', '2025-07-30 14:11:06', '2025-07-30 14:11:06', 1),
(449, 66, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-30 14:24:24', '2025-07-30 14:24:24', '2025-07-30 14:24:24', 2),
(450, 88, NULL, 7, 2, NULL, NULL, NULL, NULL, 2, '2025-07-30 14:24:24', '2025-07-30 14:24:24', '2025-07-30 14:24:24', 2),
(451, 71, NULL, 7, 2, NULL, NULL, NULL, NULL, 2, '2025-07-30 14:24:24', '2025-07-30 14:24:24', '2025-07-30 14:24:24', 2),
(452, 93, NULL, 7, 5, NULL, NULL, NULL, NULL, 2, '2025-07-30 14:24:24', '2025-07-30 14:24:24', '2025-07-30 14:24:24', 2),
(453, 91, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-30 14:24:24', '2025-07-30 14:24:24', '2025-07-30 14:24:24', 2),
(454, 100, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-30 14:24:52', '2025-07-30 14:24:52', '2025-07-30 14:24:52', 1),
(455, 80, NULL, 2, -3, NULL, NULL, NULL, NULL, 2, '2025-07-30 15:03:37', '2025-07-30 15:03:37', '2025-07-30 15:03:37', 2),
(456, 83, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-30 15:03:37', '2025-07-30 15:03:37', '2025-07-30 15:03:37', 2),
(457, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-30 16:09:36', '2025-07-30 16:09:36', '2025-07-30 16:09:36', 5),
(458, 72, 13, 2, -187, NULL, NULL, NULL, NULL, 2, '2025-07-30 16:09:36', '2025-07-30 16:09:36', '2025-07-30 16:09:36', 5),
(459, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-07-30 16:09:36', '2025-07-30 16:09:36', '2025-07-30 16:09:36', 5),
(460, 47, NULL, 1, 6, NULL, NULL, NULL, NULL, 2, '2025-07-30 16:10:35', '2025-07-30 16:10:35', '2025-07-30 16:10:35', NULL),
(461, 83, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-07-30 16:10:35', '2025-07-30 16:10:35', '2025-07-30 16:10:35', NULL),
(462, 72, 16, 1, 305, NULL, NULL, NULL, NULL, 2, '2025-07-30 19:34:09', '2025-07-30 19:34:09', '2025-07-30 19:34:09', NULL),
(463, 72, 17, 1, 305, NULL, NULL, NULL, NULL, 2, '2025-07-30 19:34:09', '2025-07-30 19:34:09', '2025-07-30 19:34:09', NULL),
(464, 72, 18, 1, 305, NULL, NULL, NULL, NULL, 2, '2025-07-30 19:34:09', '2025-07-30 19:34:09', '2025-07-30 19:34:09', NULL),
(465, 72, 19, 1, 305, NULL, NULL, NULL, NULL, 2, '2025-07-30 19:34:09', '2025-07-30 19:34:09', '2025-07-30 19:34:09', NULL),
(466, 47, NULL, 7, 3, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:18:40', '2025-07-31 14:18:40', '2025-07-31 14:18:40', 5),
(467, 80, NULL, 7, 4, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:18:40', '2025-07-31 14:18:40', '2025-07-31 14:18:40', 5),
(468, 72, 12, 7, 138, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:21:45', '2025-07-31 14:21:45', '2025-07-31 14:21:45', 5),
(469, 72, 12, 7, 301, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:25:37', '2025-07-31 14:25:37', '2025-07-31 14:25:37', 5),
(470, 72, 15, 7, 138, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:26:46', '2025-07-31 14:26:46', '2025-07-31 14:26:46', 1),
(471, 72, 12, 2, -602, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:30:11', '2025-07-31 14:30:11', '2025-07-31 14:30:11', 1),
(472, 50, NULL, 2, -6, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:37:40', '2025-07-31 14:37:40', '2025-07-31 14:37:40', 6),
(473, 83, NULL, 2, -3, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:37:40', '2025-07-31 14:37:40', '2025-07-31 14:37:40', 6),
(474, 3, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:37:40', '2025-07-31 14:37:40', '2025-07-31 14:37:40', 6),
(475, 57, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:37:40', '2025-07-31 14:37:40', '2025-07-31 14:37:40', 6),
(476, 80, NULL, 2, -6, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:37:40', '2025-07-31 14:37:40', '2025-07-31 14:37:40', 6),
(477, 47, NULL, 2, -6, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:37:40', '2025-07-31 14:37:40', '2025-07-31 14:37:40', 6),
(478, 73, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:37:40', '2025-07-31 14:37:40', '2025-07-31 14:37:40', 6),
(479, 47, NULL, 2, -10, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:46:01', '2025-07-31 14:46:01', '2025-07-31 14:46:01', 1),
(480, 80, NULL, 2, -10, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:46:01', '2025-07-31 14:46:01', '2025-07-31 14:46:01', 1),
(481, 26, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:46:01', '2025-07-31 14:46:01', '2025-07-31 14:46:01', 1),
(482, 25, NULL, 2, -6, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:46:01', '2025-07-31 14:46:01', '2025-07-31 14:46:01', 1),
(483, 23, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:46:01', '2025-07-31 14:46:01', '2025-07-31 14:46:01', 1),
(484, 79, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:46:01', '2025-07-31 14:46:01', '2025-07-31 14:46:01', 1),
(485, 72, 16, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:46:01', '2025-07-31 14:46:01', '2025-07-31 14:46:01', 1),
(486, 83, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-31 14:46:01', '2025-07-31 14:46:01', '2025-07-31 14:46:01', 1),
(487, 104, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-07-31 15:13:39', '2025-07-31 15:13:39', '2025-07-31 15:13:39', NULL),
(488, 104, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-31 15:14:20', '2025-07-31 15:14:20', '2025-07-31 15:14:20', 1),
(489, 72, 15, 2, -138, NULL, NULL, NULL, NULL, 2, '2025-07-31 15:15:30', '2025-07-31 15:15:30', '2025-07-31 15:15:30', 6),
(490, 79, NULL, 1, 1, NULL, NULL, NULL, NULL, 2, '2025-07-31 21:23:30', '2025-07-31 21:23:30', '2025-07-31 21:23:30', NULL),
(491, 57, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-31 21:24:39', '2025-07-31 21:24:39', '2025-07-31 21:24:39', NULL),
(492, 100, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-31 21:32:44', '2025-07-31 21:32:44', '2025-07-31 21:32:44', 1),
(493, 57, NULL, 1, 1, NULL, NULL, NULL, NULL, 2, '2025-07-31 21:50:07', '2025-07-31 21:50:07', '2025-07-31 21:50:07', 7),
(494, 47, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-07-31 22:01:26', '2025-07-31 22:01:26', '2025-07-31 22:01:26', 6),
(495, 83, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-07-31 22:01:26', '2025-07-31 22:01:26', '2025-07-31 22:01:26', 6),
(496, 83, NULL, 7, 3, NULL, NULL, NULL, NULL, 2, '2025-07-31 23:13:02', '2025-07-31 23:13:02', '2025-07-31 23:13:02', 6),
(497, 72, 17, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-08-01 15:17:46', '2025-08-01 15:17:46', '2025-08-01 15:17:46', 6),
(498, 57, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-01 15:17:46', '2025-08-01 15:17:46', '2025-08-01 15:17:46', 6),
(499, 83, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-08-01 15:17:46', '2025-08-01 15:17:46', '2025-08-01 15:17:46', 6),
(500, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-08-01 15:17:46', '2025-08-01 15:17:46', '2025-08-01 15:17:46', 6),
(501, 47, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-08-01 15:18:44', '2025-08-01 15:18:44', '2025-08-01 15:18:44', 1),
(502, 83, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-08-01 15:18:44', '2025-08-01 15:18:44', '2025-08-01 15:18:44', 1),
(503, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-08-01 15:18:44', '2025-08-01 15:18:44', '2025-08-01 15:18:44', 1),
(504, 83, NULL, 1, 2, NULL, NULL, NULL, NULL, 2, '2025-08-01 15:19:02', '2025-08-01 15:19:02', '2025-08-01 15:19:02', NULL),
(505, 81, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-08-01 17:49:40', '2025-08-01 17:49:40', '2025-08-01 17:49:40', 7),
(506, 104, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-01 20:24:47', '2025-08-01 20:24:47', '2025-08-01 20:24:47', 5),
(507, 47, NULL, 2, -10, NULL, NULL, NULL, NULL, 2, '2025-08-01 20:24:47', '2025-08-01 20:24:47', '2025-08-01 20:24:47', 5),
(508, 72, 18, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-08-01 20:24:47', '2025-08-01 20:24:47', '2025-08-01 20:24:47', 5),
(509, 50, NULL, 2, -5, NULL, NULL, NULL, NULL, 2, '2025-08-01 20:24:47', '2025-08-01 20:24:47', '2025-08-01 20:24:47', 5),
(510, 80, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-08-01 20:24:47', '2025-08-01 20:24:47', '2025-08-01 20:24:47', 5),
(511, 83, NULL, 7, 2, NULL, NULL, NULL, NULL, 2, '2025-08-01 23:20:45', '2025-08-01 23:20:45', '2025-08-01 23:20:45', 6),
(512, 57, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-08-01 23:20:45', '2025-08-01 23:20:45', '2025-08-01 23:20:45', 6),
(513, 47, NULL, 7, 4, NULL, NULL, NULL, NULL, 2, '2025-08-01 23:20:45', '2025-08-01 23:20:45', '2025-08-01 23:20:45', 6),
(514, 80, NULL, 1, 35, NULL, NULL, NULL, NULL, 2, '2025-08-02 14:30:11', '2025-08-02 14:30:11', '2025-08-02 14:30:11', NULL),
(515, 47, NULL, 2, -5, NULL, NULL, NULL, NULL, 2, '2025-08-02 14:30:54', '2025-08-02 14:30:54', '2025-08-02 14:30:54', 6),
(516, 80, NULL, 2, -5, NULL, NULL, NULL, NULL, 2, '2025-08-02 14:30:54', '2025-08-02 14:30:54', '2025-08-02 14:30:54', 6),
(517, 57, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-02 14:31:16', '2025-08-02 14:31:16', '2025-08-02 14:31:16', 5),
(518, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-08-02 14:48:06', '2025-08-02 14:48:06', '2025-08-02 14:48:06', 5),
(519, 57, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-08-02 15:13:41', '2025-08-02 15:13:41', '2025-08-02 15:13:41', 3),
(520, 75, NULL, 1, 2, NULL, NULL, NULL, NULL, 2, '2025-08-02 15:14:23', '2025-08-02 15:14:23', '2025-08-02 15:14:23', NULL),
(521, 80, NULL, 7, 5, NULL, NULL, NULL, NULL, 2, '2025-08-04 14:46:11', '2025-08-04 14:46:11', '2025-08-04 14:46:11', NULL),
(522, 47, NULL, 7, 4, NULL, NULL, NULL, NULL, 2, '2025-08-04 14:46:11', '2025-08-04 14:46:11', '2025-08-04 14:46:11', NULL),
(523, 104, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-08-04 14:46:11', '2025-08-04 14:46:11', '2025-08-04 14:46:11', NULL),
(524, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-08-04 15:03:58', '2025-08-04 15:03:58', '2025-08-04 15:03:58', 1),
(525, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-08-04 15:03:58', '2025-08-04 15:03:58', '2025-08-04 15:03:58', 1),
(526, 57, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-04 15:03:58', '2025-08-04 15:03:58', '2025-08-04 15:03:58', 1),
(527, 100, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-04 15:03:58', '2025-08-04 15:03:58', '2025-08-04 15:03:58', 1),
(528, 75, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-04 15:03:58', '2025-08-04 15:03:58', '2025-08-04 15:03:58', 1),
(529, 4, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-04 15:03:58', '2025-08-04 15:03:58', '2025-08-04 15:03:58', 1),
(530, 12, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-08-04 15:03:58', '2025-08-04 15:03:58', '2025-08-04 15:03:58', 1),
(531, 50, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-08-04 15:03:58', '2025-08-04 15:03:58', '2025-08-04 15:03:58', 1),
(532, 3, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-04 15:05:08', '2025-08-04 15:05:08', '2025-08-04 15:05:08', 5),
(533, 72, 19, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-04 15:05:08', '2025-08-04 15:05:08', '2025-08-04 15:05:08', 5),
(534, 72, 18, 7, 154, NULL, NULL, NULL, NULL, 2, '2025-08-04 15:17:02', '2025-08-04 15:17:02', '2025-08-04 15:17:02', 5),
(535, 72, 17, 7, 163, NULL, NULL, NULL, NULL, 2, '2025-08-04 15:17:02', '2025-08-04 15:17:02', '2025-08-04 15:17:02', 5),
(536, 72, 16, 7, 107, NULL, NULL, NULL, NULL, 2, '2025-08-04 15:17:02', '2025-08-04 15:17:02', '2025-08-04 15:17:02', 5),
(537, 2, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-04 18:09:27', '2025-08-04 18:09:27', '2025-08-04 18:09:27', NULL),
(538, 72, 19, 2, -304, NULL, NULL, NULL, NULL, 2, '2025-08-04 19:03:45', '2025-08-04 19:03:45', '2025-08-04 19:03:45', 5),
(539, 72, 14, 2, -170, NULL, NULL, NULL, NULL, 2, '2025-08-04 19:04:40', '2025-08-04 19:04:40', '2025-08-04 19:04:40', NULL),
(540, 105, NULL, 1, 2, NULL, NULL, NULL, NULL, NULL, '2025-08-04 20:55:35', '2025-08-04 20:55:35', '2025-08-04 20:55:35', NULL),
(541, 105, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-04 20:57:18', '2025-08-04 20:57:18', '2025-08-04 20:57:18', 6),
(542, 106, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-08-04 21:00:27', '2025-08-04 21:00:27', '2025-08-04 21:00:27', NULL),
(543, 57, NULL, 1, 5, NULL, NULL, NULL, NULL, 2, '2025-08-04 21:01:21', '2025-08-04 21:01:21', '2025-08-04 21:01:21', NULL),
(544, 107, NULL, 1, 3, NULL, NULL, NULL, NULL, NULL, '2025-08-04 21:03:27', '2025-08-04 21:03:27', '2025-08-04 21:03:27', NULL),
(545, 107, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-04 21:03:50', '2025-08-04 21:03:50', '2025-08-04 21:03:50', 6),
(546, 4, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-08-04 21:32:12', '2025-08-04 21:32:12', '2025-08-04 21:32:12', 1),
(547, 57, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-08-04 21:32:12', '2025-08-04 21:32:12', '2025-08-04 21:32:12', 1),
(548, 104, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-05 14:58:07', '2025-08-05 14:58:07', '2025-08-05 14:58:07', 5),
(549, 80, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-08-05 14:58:07', '2025-08-05 14:58:07', '2025-08-05 14:58:07', 5),
(550, 47, NULL, 2, -13, NULL, NULL, NULL, NULL, 2, '2025-08-05 14:58:07', '2025-08-05 14:58:07', '2025-08-05 14:58:07', 5),
(551, 26, NULL, 2, -3, NULL, NULL, NULL, NULL, 2, '2025-08-05 14:58:07', '2025-08-05 14:58:07', '2025-08-05 14:58:07', 5),
(552, 72, 17, 2, -163, NULL, NULL, NULL, NULL, 2, '2025-08-05 14:58:07', '2025-08-05 14:58:07', '2025-08-05 14:58:07', 5),
(553, 72, 18, 2, -154, NULL, NULL, NULL, NULL, 2, '2025-08-05 14:58:07', '2025-08-05 14:58:07', '2025-08-05 14:58:07', 5),
(554, 83, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-05 15:14:47', '2025-08-05 15:14:47', '2025-08-05 15:14:47', 3),
(555, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-08-05 17:16:49', '2025-08-05 17:16:49', '2025-08-05 17:16:49', 1),
(556, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-08-05 17:16:49', '2025-08-05 17:16:49', '2025-08-05 17:16:49', 1),
(557, 79, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-05 17:16:49', '2025-08-05 17:16:49', '2025-08-05 17:16:49', 1),
(558, 72, 17, 1, 305, NULL, NULL, NULL, NULL, 2, '2025-08-05 17:19:16', '2025-08-05 17:19:16', '2025-08-05 17:19:16', NULL),
(559, 72, 18, 1, 305, NULL, NULL, NULL, NULL, 2, '2025-08-05 17:19:16', '2025-08-05 17:19:16', '2025-08-05 17:19:16', NULL),
(560, 72, 19, 1, 305, NULL, NULL, NULL, NULL, 2, '2025-08-05 17:19:16', '2025-08-05 17:19:16', '2025-08-05 17:19:16', NULL),
(561, 72, 15, 1, 305, NULL, NULL, NULL, NULL, 2, '2025-08-05 17:19:16', '2025-08-05 17:19:16', '2025-08-05 17:19:16', NULL),
(562, 72, 14, 1, 305, NULL, NULL, NULL, NULL, 2, '2025-08-05 17:19:16', '2025-08-05 17:19:16', '2025-08-05 17:19:16', NULL),
(563, 72, 13, 1, 305, NULL, NULL, NULL, NULL, 2, '2025-08-05 17:19:16', '2025-08-05 17:19:16', '2025-08-05 17:19:16', NULL),
(564, 79, NULL, 1, 5, NULL, NULL, NULL, NULL, 2, '2025-08-05 17:19:16', '2025-08-05 17:19:16', '2025-08-05 17:19:16', NULL),
(565, 72, 13, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-08-05 17:19:43', '2025-08-05 17:19:43', '2025-08-05 17:19:43', 1),
(566, 47, NULL, 2, -6, NULL, NULL, NULL, NULL, 2, '2025-08-05 19:29:35', '2025-08-05 19:29:35', '2025-08-05 19:29:35', 5),
(567, 72, 17, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-05 19:29:35', '2025-08-05 19:29:35', '2025-08-05 19:29:35', 5),
(568, 83, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-05 19:29:35', '2025-08-05 19:29:35', '2025-08-05 19:29:35', 5),
(569, 80, NULL, 2, -6, NULL, NULL, NULL, NULL, 2, '2025-08-05 19:29:35', '2025-08-05 19:29:35', '2025-08-05 19:29:35', 5),
(570, 50, NULL, 2, -5, NULL, NULL, NULL, NULL, 2, '2025-08-05 19:29:35', '2025-08-05 19:29:35', '2025-08-05 19:29:35', 5),
(571, 12, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-05 19:29:35', '2025-08-05 19:29:35', '2025-08-05 19:29:35', 5),
(572, 57, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-05 19:29:35', '2025-08-05 19:29:35', '2025-08-05 19:29:35', 5),
(573, 2, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-05 19:29:35', '2025-08-05 19:29:35', '2025-08-05 19:29:35', 5),
(574, 100, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-05 19:29:35', '2025-08-05 19:29:35', '2025-08-05 19:29:35', 5),
(575, 79, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-08-05 23:32:12', '2025-08-05 23:32:12', '2025-08-05 23:32:12', 1),
(576, 80, NULL, 7, 4, NULL, NULL, NULL, NULL, 2, '2025-08-05 23:32:12', '2025-08-05 23:32:12', '2025-08-05 23:32:12', 1),
(577, 47, NULL, 7, 3, NULL, NULL, NULL, NULL, 2, '2025-08-05 23:32:12', '2025-08-05 23:32:12', '2025-08-05 23:32:12', 1),
(578, 83, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-06 14:10:37', '2025-08-06 14:10:37', '2025-08-06 14:10:37', 1),
(579, 83, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-06 14:41:05', '2025-08-06 14:41:05', '2025-08-06 14:41:05', 3),
(580, 79, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-06 14:41:31', '2025-08-06 14:41:31', '2025-08-06 14:41:31', 7),
(581, 47, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-06 15:18:41', '2025-08-06 15:18:41', '2025-08-06 15:18:41', NULL),
(582, 73, NULL, 1, 3, NULL, NULL, NULL, NULL, 2, '2025-08-06 17:32:12', '2025-08-06 17:32:12', '2025-08-06 17:32:12', NULL),
(583, 81, NULL, 1, 3, NULL, NULL, NULL, NULL, 2, '2025-08-06 18:26:30', '2025-08-06 18:26:30', '2025-08-06 18:26:30', NULL),
(584, 80, NULL, 2, -3, NULL, NULL, NULL, NULL, 2, '2025-08-06 22:29:58', '2025-08-06 22:29:58', '2025-08-06 22:29:58', 5),
(585, 50, NULL, 2, -3, NULL, NULL, NULL, NULL, 2, '2025-08-06 22:29:58', '2025-08-06 22:29:58', '2025-08-06 22:29:58', 5),
(586, 93, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-08-06 22:29:58', '2025-08-06 22:29:58', '2025-08-06 22:29:58', 5),
(587, 72, 16, 2, -107, NULL, NULL, NULL, NULL, 2, '2025-08-06 22:32:02', '2025-08-06 22:32:02', '2025-08-06 22:32:02', 5),
(588, 5, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-06 22:34:22', '2025-08-06 22:34:22', '2025-08-06 22:34:22', 5),
(589, 80, NULL, 7, 4, NULL, NULL, NULL, NULL, 2, '2025-08-06 23:44:00', '2025-08-06 23:44:00', '2025-08-06 23:44:00', 1),
(590, 47, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-08-07 14:56:38', '2025-08-07 14:56:38', '2025-08-07 14:56:38', 1),
(591, 80, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-08-07 14:56:38', '2025-08-07 14:56:38', '2025-08-07 14:56:38', 1),
(592, 57, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-07 14:56:38', '2025-08-07 14:56:38', '2025-08-07 14:56:38', 1),
(593, 4, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-07 14:56:38', '2025-08-07 14:56:38', '2025-08-07 14:56:38', 1),
(594, 75, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-07 14:56:38', '2025-08-07 14:56:38', '2025-08-07 14:56:38', 1),
(595, 12, NULL, 2, -4, NULL, NULL, NULL, NULL, 2, '2025-08-07 14:56:38', '2025-08-07 14:56:38', '2025-08-07 14:56:38', 1),
(596, 72, 17, 2, -304, NULL, NULL, NULL, NULL, 2, '2025-08-07 14:56:38', '2025-08-07 14:56:38', '2025-08-07 14:56:38', 1),
(597, 47, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-08-07 15:01:40', '2025-08-07 15:01:40', '2025-08-07 15:01:40', 6),
(598, 75, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-07 15:01:40', '2025-08-07 15:01:40', '2025-08-07 15:01:40', 6),
(599, 26, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-07 15:01:40', '2025-08-07 15:01:40', '2025-08-07 15:01:40', 6),
(600, 80, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-08-07 15:01:40', '2025-08-07 15:01:40', '2025-08-07 15:01:40', 6),
(601, 100, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-07 15:01:40', '2025-08-07 15:01:40', '2025-08-07 15:01:40', 6),
(602, 83, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-07 15:01:40', '2025-08-07 15:01:40', '2025-08-07 15:01:40', 6),
(603, 12, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-07 15:01:40', '2025-08-07 15:01:40', '2025-08-07 15:01:40', 6),
(604, 80, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-08-07 15:27:09', '2025-08-07 15:27:09', '2025-08-07 15:27:09', 4),
(605, 83, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-07 15:27:09', '2025-08-07 15:27:09', '2025-08-07 15:27:09', 4),
(606, 47, NULL, 2, -8, NULL, NULL, NULL, NULL, 2, '2025-08-07 15:27:09', '2025-08-07 15:27:09', '2025-08-07 15:27:09', 4),
(607, 79, NULL, 2, -2, NULL, NULL, NULL, NULL, 2, '2025-08-07 15:27:09', '2025-08-07 15:27:09', '2025-08-07 15:27:09', 4),
(608, 72, 14, 2, -305, NULL, NULL, NULL, NULL, 2, '2025-08-07 15:27:09', '2025-08-07 15:27:09', '2025-08-07 15:27:09', 4),
(609, 47, NULL, 1, 200, NULL, NULL, NULL, NULL, 2, '2025-08-07 16:28:38', '2025-08-07 16:28:38', '2025-08-07 16:28:38', NULL),
(610, 79, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-07 20:59:17', '2025-08-07 20:59:17', '2025-08-07 20:59:17', 5),
(611, 79, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-08-07 22:54:11', '2025-08-07 22:54:11', '2025-08-07 22:54:11', 7),
(612, 79, NULL, 7, 1, NULL, NULL, NULL, NULL, 2, '2025-08-07 22:54:34', '2025-08-07 22:54:34', '2025-08-07 22:54:34', 5),
(613, 57, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-07 22:54:50', '2025-08-07 22:54:50', '2025-08-07 22:54:50', 5),
(614, 106, NULL, 2, -1, NULL, NULL, NULL, NULL, 2, '2025-08-07 22:55:43', '2025-08-07 22:55:43', '2025-08-07 22:55:43', 6);

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
(4, 'Venta', 0),
(7, 'Devolucion', 1);

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
  `product_name` varchar(500) DEFAULT NULL,
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

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `sku`, `barcode`, `price`, `cost_price`, `quantity`, `min_stock`, `max_stock`, `supplier_id`, `category_id`, `description`, `image`, `tipo_gestion`, `unit_measure`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'DAHUA IPC-HDBW1235E-W-S2', 'DHT0040056', '6939554996726', 1165.42, 896.48, 1, NULL, NULL, 1, 2, 'DAHUA IPC-HDBW1235E-W-S2 - Camara IP Domo Wifi de 2 Megapixeles/ Lente de 2.8 mm/ 113 Grados de Apertura/ IR de 30 Metros/ H.265/ Ranura para MicroSD/ IP67/ Antivandalica IK10/ DWDR/', 'uploads/products/prod_687170751cb4f.png', 'normal', '0', NULL, '2025-07-11 20:13:41', '2025-07-11 20:13:41'),
(2, 'DAHUA XVR1B08', 'DHT0360018', '6923172547330', 1661.48, 1278.06, -1, NULL, NULL, 1, 3, 'DAHUA XVR1B08-I-SSD - DVR de 8 canales 1080p Lite/ Con disco SSD de 512GB especial para Videovigilancia/ S-XVR Series/ WizSense/ H.265+/ 4 canales con SMD Plus/ Búsqueda inteligente (Humanos y vehículos) / #S-XVR #DVM', 'uploads/products/prod_687176f55b254.png', 'normal', '0', NULL, '2025-07-11 20:41:25', '2025-08-05 19:29:35'),
(3, 'DAHUA XVR1B08-I -DVR de 8 Canales', 'DHT0360008', '6923172504043', 664.33, 511.02, 4, NULL, NULL, 1, 3, 'DAHUA XVR1B08-I -DVR de 8 Canales 1080p Lite WizSense y Cooper-I. Soporta H.265+, hasta 10 canales IP, y 4 canales con SMD Plus para detección avanzada. Incluye búsqueda inteligente de personas y vehículos y codificación inteligente #DAHQ1M #LF#VolDH', 'uploads/products/prod_6871779dcdf71.png', 'normal', '0', NULL, '2025-07-11 20:44:13', '2025-08-04 15:05:08'),
(4, 'DAHUA XVR1B04-I -DVR de 4 Canales', 'DHT0350010', '6923172503992', 530.17, 407.82, 6, NULL, NULL, 1, 4, 'DAHUA XVR1B04-I -DVR de 4 Canales 1080p LiteWizSense y Cooper-I. Compatible con H.265+, admite hasta 5 canales IP y 4 canales con SMD Plus para detección avanzada. Búsqueda inteligente de personas y vehículos, codificación eficiente #DAHQ1M #LF#VolDH', 'uploads/products/prod_687178eec45a9.png', 'normal', '0', NULL, '2025-07-11 20:47:38', '2025-08-07 14:56:38'),
(5, 'SOPORTE DE CAMARA DAHUA PFB203W', 'DAH124015', '6939554903632', 228.98, 176.14, 0, NULL, NULL, 1, 5, 'DAHUA PFB203W - Brazo de pared para camaras domo DAHUA / HDW2120 / 2220 / 2221RZ / HDW1000 / 1100R / HDW1120 / 1220 / 1320S / HDW2120', 'uploads/products/prod_687179cd3f5df.png', 'normal', '0', NULL, '2025-07-11 20:53:33', '2025-08-06 22:34:22'),
(6, 'Camara IP PTZ de 4 MP TiOC de 5x de Zoom Optico', 'DHT0060100', '6923172548184', 3303.44, 2541.11, 0, NULL, NULL, 1, 6, 'DAHUA SD3E405DB-GNY-A-PV1 - Camara IP PTZ de 4 MP TiOC de 5x de Zoom Optico/ Iluminación Dual Inteligente/ Disuasión Activa con Luz Roja y Azul/ IR de 50 Metros/ Micrófono y Altavoz Integrado/ Audio 2 Vías/ Ranura para MicroSD/ IP66/ PoE', 'uploads/products/prod_68717ac1057bb.png', 'normal', 'piezas', NULL, '2025-07-11 20:55:08', '2025-07-23 16:30:31'),
(7, 'Cámara Oculta en Sensor de Movimiento/ 2 Megapixeles', 'DHT0310017', '6923172544629', 678.08, 521.60, 0, NULL, NULL, 1, 7, 'DAHUA HAC-HUM3200A - Cámara Oculta en Sensor de Movimiento/ 2 Megapixeles/ Super Adapt/ Lente de 2.8mm/ 105 Grados de Apertura/ IR de 10 Metros/ DWDR/ Soporta: CVI/CVBS/AHD/TVI/', 'uploads/products/prod_68717c7143317.png', 'normal', '0', NULL, '2025-07-11 21:04:49', '2025-07-23 22:15:29'),
(8, 'Cámara PTZ Mini Domo Antivandálica de 2 Megapíxeles', 'DHT0330021', '6923172591425', 2556.53, 1966.56, 2, NULL, NULL, 1, 6, 'DAHUA SD22204DB-GC - Cámara PTZ Mini Domo Antivandálica de 2 Megapíxeles/ analógica / 4X de Zoom Óptico/ IP66/ IK10/ Starlight/ HLC/ 30FPS/ 3DNR/ #MCI2 #MCC', 'uploads/products/prod_68717ce855daf.png', 'normal', '0', NULL, '2025-07-11 21:06:48', '2025-07-11 21:06:48'),
(9, 'Control de Asistencia Stand Alone con Batería Incluida', 'DHT0800005', '6923172582454', 1193.70, 918.23, 1, NULL, NULL, 1, 8, 'DAHUA ASA1222GL-D - Control de Asistencia Stand Alone con Batería Incluida/ 1000 Usuarios, Passwords y Tarjetas ID/ 2000 Huellas/ 100,000 Registros de Asistencias/ Protocolos TCP/IP/UDP/IPv4/ USB p/Exportar Registros/ Horarios', 'uploads/products/prod_68717d7003e68.png', 'normal', '0', NULL, '2025-07-11 21:09:04', '2025-07-11 21:09:04'),
(10, 'Switch Poe de 10 Puertos/ 8 Puertos PoE 10/100/ 2 Puertos Uplink 10/100/1000', 'DH-CS4010-8ET-110', 'AK05447PAJ00002', 1506.52, 1165.91, -1, NULL, NULL, 1, 9, 'DAHUA CS4010-8ET-110 - Switch Poe de 10 Puertos/ 8 Puertos PoE 10/100/ 2 Puertos Uplink 10/100/1000/ 110 Watts Totales/ Administrable en la Nube por DoLynk Care/ PoE 250 Metros/ Carcasa Metalica/ Switching 5.6 Gbps/', 'uploads/products/prod_68717e50c2b50.png', 'normal', 'Pieza', NULL, '2025-07-11 21:12:48', '2025-07-24 14:14:43'),
(11, 'Cámara Domo de 2 Megapíxeles Antivandálica/ 1080p', 'DHT0300065', '6923172594860', 593.94, 456.88, 0, NULL, NULL, 1, 10, 'DAHUA HAC-HDBW1200EA - Cámara Domo de 2 Megapíxeles Antivandálica/ 1080p/ Lente 2.8 mm/ 115 Grados de Apertura/ IR de 40 Metros/ Super Adapt/ Protección IK10/ Uso Exterior IP67/ Soporta: HDCVI/TVI/AHD y CVBS/', 'uploads/products/prod_68717fef2d852.png', 'normal', '0', NULL, '2025-07-11 21:19:43', '2025-07-16 18:31:21'),
(12, 'DAHUA HAC-HFW1209CN-A-LED - Cámara Bullet Full Color 1080p/ Lente de 2.8 mm/ 106 Grados de Apertura/', 'DH-HAC-HFW1209CN-A-LED', '6939554916724', 443.90, 343.54, 55, NULL, NULL, 1, 11, 'DAHUA HAC-HFW1209CN-A-LED - Cámara Bullet Full Color 1080p/ Lente de 2.8 mm/ 106 Grados de Apertura/ Micrófono Integrado/ Luz Blanca de 20 Mts/ DWDR/ Starlight/ IP67/ Soporta CVI/AHD y CVBS #VolDH #IngDahua', 'uploads/products/prod_687180e662438.png', 'normal', '0', NULL, '2025-07-11 21:23:50', '2025-08-07 15:01:40'),
(13, 'Switch para Escritorio 5 Puertos Fast Ethernet con velocidad de transmisión de 10/100 Mbps en un dis', 'DHT3700020', '6923172574725', 117.34, 90.26, 3, NULL, NULL, 1, 12, 'DAHUA DH-SF1005L - Switch para Escritorio 5 Puertos Fast Ethernet con velocidad de transmisión de 10/100 Mbps en un diseño compacto. Su capa 2 soporta un switching de hasta 1 Gbps y una velocidad de reenvío de 0.744 Mbps. #SwitchDpxv #VolDH', 'uploads/products/prod_687181fc6e1cc.png', 'normal', '0', NULL, '2025-07-11 21:28:28', '2025-07-11 21:28:28'),
(14, 'Router Dahua Ethernet DH-N3', 'ROUDAH010', '6923172560100', 195.10, 150.08, 5, NULL, NULL, 2, 13, 'Router Dahua Ethernet DH-N3, Inalámbrico, 300Mbit/s, 4x RJ-45, 2.4GHz, 2 Antenas Externas NB', 'uploads/products/prod_68718ae67fe19.jpg', 'normal', '0', NULL, '2025-07-11 22:06:30', '2025-07-11 22:06:30'),
(15, 'DashCam equipada con Wi-Fi y G-Sensor, con capacidad de MicroSD de hasta 128 Gb y cuenta con Micrófo', 'DHT0390029', '6923172553324', 762.50, 586.54, 1, NULL, NULL, 1, 14, 'DAHUA M1pro - DashCam equipada con Wi-Fi y G-Sensor, con capacidad de MicroSD de hasta 128 Gb y cuenta con Micrófono y altavoz integrados. #LoNuevo #DAMOV #MDAB', 'uploads/products/prod_68718c08250bb.png', 'normal', '0', NULL, '2025-07-11 22:11:20', '2025-07-11 22:11:20'),
(16, 'Cámara de Tablero (DashCam)/ Soporta ADAS (Sistemas Avanzados de Asistencia al Conductor)', 'DHT0390030', '6939554913303', 1675.10, 1288.54, 1, NULL, NULL, 1, 14, 'DAHUA H10 - Cámara de Tablero (DashCam)/ Soporta ADAS (Sistemas Avanzados de Asistencia al Conductor)/2160P Ultra Alta Resolución/Conectividad Wi-Fi/ Sensor de Fuerza G/ Ranura MicroSD p/hasta 256 GB/ Micrófono y Altavoz integrados/ #LoNuevo #DAMOV #MDAB', 'uploads/products/prod_68718cb22dcf2.png', 'normal', '0', NULL, '2025-07-11 22:14:10', '2025-07-11 22:14:33'),
(17, 'Cámara HDCVI Pinhole 1080p/2 Megapíxeles', 'DHT0310001', '6939554977329', 932.91, 717.62, 1, NULL, NULL, 1, 7, 'DAHUA HAC-HUM3201B-P - Cámara HDCVI Pinhole 1080p/2 Megapíxeles/ Lente de 2.8 MM/ 103 Grados de Apertura/ 1 Entrada de Audio/ WDR/ BLC/ HLC/ Starlight', 'uploads/products/prod_68718dbf40a6f.jpg', 'normal', '0', NULL, '2025-07-11 22:18:39', '2025-07-11 22:18:39'),
(18, 'Interruptor Inalámbrico/ 1 Salida de Relevador', 'DHT1200001', '6923172558527', 447.21, 344.01, 2, NULL, NULL, 1, 5, 'DAHUA DHI-ARM7012-W2 - Interruptor Inalámbrico/ 1 Salida de Relevador NO/NC de 100–240 VAC Max 13 A/ Entrada de 100-240 Vca 50/60 Hz/ Comunicación Estable/ Detector de Interferencias/ Indicador de Estatus/ #alarmasdahua #MAYAL', 'uploads/products/prod_68718e82e288e.png', 'normal', '0', NULL, '2025-07-11 22:21:54', '2025-07-11 22:21:54'),
(19, 'Control Remoto Tipo Llavero de 4 Botones / Armado', 'DHT2480009', '6923172504586', 308.13, 237.02, 2, NULL, NULL, 1, 5, 'DAHUA DHI-ARA24-W2 - Control Remoto Tipo Llavero de 4 Botones / Armado - Desarmado - En Casa - Emergencia / Función de Salto de Frecuencia / Led Indicador de Estado Color Rojo o Verde / #AlarmasDahua #VM', 'uploads/products/prod_68718f1269aee.png', 'normal', '0', NULL, '2025-07-11 22:24:18', '2025-07-11 22:24:18'),
(20, 'Teclado Inalámbrico Interior Touch para Armado y Desarmado', 'DHI-ARK30T-W2', '6923172538680', 1055.56, 811.97, 0, NULL, NULL, 1, 5, 'DAHUA DHI-ARK30T-W2 - Teclado Inalámbrico Interior Touch para Armado y Desarmado / Soporta hasta 32 usuarios con Pin o Tarjetas Mifare / Indicadores Led de Status del Panel / Alarma de Batería Baja / #AlarmasDahua', 'uploads/products/prod_68718f60edfa4.png', 'normal', '0', NULL, '2025-07-11 22:25:36', '2025-07-28 14:41:21'),
(21, 'Sirena Inalámbrica para Exterior con Estrobo Rojo', 'DHT2480007', '6923172535627', 1567.06, 1205.43, 1, NULL, NULL, 1, 15, 'DAHUA DHI-ARA13-W2 - Sirena Inalámbrica para Exterior con Estrobo Rojo/ 110dB / Múltiples sonidos de Alarma/ IP65/ Alarma de Batería Baja/ #AlarmasDahua #VM #AMYV', 'uploads/products/prod_687190347d911.png', 'normal', '0', NULL, '2025-07-11 22:29:08', '2025-07-11 22:29:08'),
(22, 'DAHUA HAC-HFW1509C-LED-28 - Cámara Bullet Full Color de 5 Megapixeles/ Lente de 2.8 mm/ 112 Grados d', 'DH-HAC-HFW1509CN-LED-0280B', '6923172522788', 682.40, 524.92, 19, NULL, NULL, 1, 11, 'DAHUA HAC-HFW1509C-LED-28 - Cámara Bullet Full Color de 5 Megapixeles/ Lente de 2.8 mm/ 112 Grados de Apertura/ Leds para 20 Mts/ WDR de 120 dB/ Starlight/ IP67/ #ProHDCVI #FULLC #1CM #MCI2 #MCC', 'uploads/products/prod_687190b36d03f.png', 'normal', '0', NULL, '2025-07-11 22:31:15', '2025-07-14 23:00:04'),
(23, 'Cámara domo Dahua de 2 MP con lente de 2.8 mm, ángulo de 102 grados, IR de 40 m, micrófono integrado', 'DHT0300064', '6923172593672', 462.90, 356.08, 24, NULL, NULL, 1, 10, 'DAHUA HAC-HDW1200TQ-A-Cámara domo Dahua de 2 MP con lente de 2.8 mm, ángulo de 102 grados, IR de 40 m, micrófono integrado, instalación rápida, DWDR, IP67, y diseño en metal y policarbonato.', 'uploads/products/prod_687191bfe0834.png', 'normal', '0', NULL, '2025-07-11 22:35:43', '2025-07-31 14:46:01'),
(24, 'Cámara Domo FullColor de 2 MP con resolución 1080p, lente de 2.8 mm y 106° de apertura. Ofrece visió', 'DHT0300073', '6939554921483', 430.11, 330.85, 1, NULL, NULL, 1, 10, 'DAHUA DH-HAC-HDW1209TLQN-LED-0280B-S3 - Cámara Domo FullColor de 2 MP con resolución 1080p, lente de 2.8 mm y 106° de apertura. Ofrece visión nocturna de 20 metros, instalación rápida, protección IP67, tecnología Starlight y DWDR.', 'uploads/products/prod_6871924592d74.png', 'normal', '0', NULL, '2025-07-11 22:37:57', '2025-07-11 22:37:57'),
(25, 'Cámara Domo con resolución 1080p, lente de 2.8 mm y ángulo de visión de 103°, Smart IR de 20 m para ', 'SCA397013', '6939554970535', 252.46, 194.20, 0, NULL, NULL, 1, 7, 'DAHUA HAC-T1A21-28 - Cámara Domo con resolución 1080p, lente de 2.8 mm y ángulo de visión de 103°, Smart IR de 20 m para mejor visión nocturna, ideal para interiores. Compatible con los formatos CVI, TVI, AHD y CVBS. #DAHQ1M', 'uploads/products/prod_6871942eb6f3a.png', 'normal', '0', NULL, '2025-07-11 22:46:06', '2025-07-31 14:46:01'),
(26, 'Cámara Domo de 2 Megapixeles/ Lente 2.8 mm', 'DHT0300069', '6923172582003', 254.50, 195.77, 20, NULL, NULL, 1, 10, 'DAHUA HAC-T1A21N-U-28 - Cámara Domo de 2 Megapixeles/ Lente 2.8 mm / 100 Grados de Apertura/ Smart ir 25 Mts/ Uso Interior/ CVI/TVI/AHD/CBVS/ #DAHQ1M #VFL #1CM#VolDH', 'uploads/products/prod_687195135af8a.png', 'normal', '0', NULL, '2025-07-11 22:49:55', '2025-08-07 15:01:40'),
(27, 'Cámara IP PT de 2 Megapíxeles Full Color/ Disuasión Activa', 'DHT0060020', '6923172526588', 2288.20, 1760.15, 2, NULL, NULL, 1, 2, 'DAHUA SD3A200-GN-A-PV - Cámara IP PT de 2 Megapíxeles Full Color/ Disuasión Activa/ Lente Fijo/ Luz Blanca de 30 Metros/ IR de 30 Metros/ H.265+/ Ranura MicroSD/ Audio Bidireccional con Altavoz Integrado/ IP66/ PoE/', 'uploads/products/prod_687196ccc7e5b.png', 'normal', '0', NULL, '2025-07-11 22:57:16', '2025-07-11 22:57:16'),
(28, 'Camara IP Bullet de 2 MP, lente 2.8 mm, 99° de visión, IR 30 m, IP67 y PoE.', 'DHT0030158', '6939554913990', 639.60, 492.00, -8, NULL, NULL, 1, 2, 'DAHUA IPC-B1E20 - Camara IP Bullet de 2 MP, lente 2.8 mm, 99° de visión, IR 30 m, IP67 y PoE. Incluye DWDR, 3D NR, HLC, BLC y compresión H.265+ para videovigilancia eficiente #SwitchD1 #MDIP #D50#VolDH', 'uploads/products/prod_6871979c5421e.png', 'normal', '0', NULL, '2025-07-11 23:00:44', '2025-07-23 16:30:31'),
(29, 'DAHUA SD2A200HB-GN-A-PV-S2 - Camara IP PT de 2 Megapixeles/ Full Color+Disuasion Activa/ Iluminador', 'DHT0060054', '6923172581488', 1328.29, 1021.76, 2, NULL, NULL, 1, 6, 'DAHUA SD2A200HB-GN-A-PV-S2 - Camara IP PT de 2 Megapixeles/ Full Color+Disuasion Activa/ Iluminador Dual Inteligente/ Lente fijo/ 30 Metros de Iluminación IR y Visible/ Audio 2 Vias/ IP66/ PoE/ Detección de Humanos/ Ranura MicroSD/', 'uploads/products/prod_687198871606a.png', 'normal', '0', NULL, '2025-07-11 23:03:42', '2025-07-11 23:04:39'),
(30, 'Camara Bullet de 2 Megapixeles/ Lente Fijo de 3.6mm', 'DH-HAC-HFW1200TN-0360B-S4', '6939554990274', 444.28, 341.75, 1, NULL, NULL, 1, 31, 'DAHUA HAC-HFW1200T-36- Camara Bullet de 2 Megapixeles/ Lente Fijo de 3.6mm/ 83 Grados de Apertura/ Smart IR 30 Metros/ IP67/ Metalica/ BLC/ HLC/ DWDR/ TVI AHD y CBVS/', 'uploads/products/prod_687199e92e522.png', 'normal', '0', NULL, '2025-07-11 23:10:33', '2025-07-14 19:11:13'),
(31, 'Camara Bullet HDCVI 1080p/ Lente 3.6 mm', 'DAH395016', '6923172592569', 638.04, 490.80, 2, NULL, NULL, 1, 31, 'DAHUA HAC-HFW1200D-036- Camara Bullet HDCVI 1080p/ Lente 3.6 mm/ 87.5 Grados de Apertura/ Smart IR 80 Mts/ IP67/ Metálica/ DWDR/ BLC/ HLC/ TVI AHD y CVBS #IngDahua\r\nTipo: Más Vendidos    Etapa: De Línea', 'uploads/products/prod_68719a4d94a15.png', 'normal', '0', NULL, '2025-07-11 23:12:13', '2025-07-14 17:00:11'),
(32, 'DAHUA DHI-ARD323-W2(S) - Contacto Magnético Inalámbrico Interior/ Diseño Compacto/ 1 Entrada de Cont', 'DHI- ARD323- W2(S)', '6923172531483', 399.09, 306.99, 3, NULL, NULL, 1, 17, 'DAHUA DHI-ARD323-W2(S) - Contacto Magnético Inalámbrico Interior/ Diseño Compacto/ 1 Entrada de Contacto Seco / Led Indicador / Alarma de Batería Baja / Detección de Intensidad de Señal / Función de Salto de Frecuencia/ #Alarmasdahua #MAYAL #AMYV', 'uploads/products/prod_68751266507d5.png', 'normal', '0', NULL, '2025-07-14 14:21:26', '2025-07-21 15:01:38'),
(33, 'DAHUA DHI-ARD1233-W2 - Detector PIR Inalámbrico Interior/ Inmunidad de Mascotas/ Led Indicador/ 3 Ni', 'DHI-ARD1233 - W2', '6923172504715', 672.84, 517.57, 1, NULL, NULL, 1, 17, 'DAHUA DHI-ARD1233-W2 - Detector PIR Inalámbrico Interior/ Inmunidad de Mascotas/ Led Indicador/ 3 Niveles de Sensibilidad/ Compensación Automática de Temperatura/ Alarma de Batería Baja/ #AlarmasDahua #AMYV', 'uploads/products/prod_687514243694a.png', 'normal', '0', NULL, '2025-07-14 14:28:52', '2025-07-16 18:31:21'),
(34, 'DAHUA DHI-ART-ARC3000H-03-W2 - Kit de Alarma Inalámbrico con Conexión Wifi y Ethernet / Monitoreo po', 'DHI-ART-ARC3000H-03-W2', '6923172522870', 2777.05, 2136.19, 0, NULL, NULL, 1, 18, 'DAHUA DHI-ART-ARC3000H-03-W2 - Kit de Alarma Inalámbrico con Conexión Wifi y Ethernet / Monitoreo por APP / Incluye Panel WiFi Ethernet; Un Sensor de Movimiento; Un Contacto Magnético; Un Control Remoto/ #AlarmasDahua #DICDAL #Anivdahua4', 'uploads/products/prod_68751506b02ac.png', 'normal', '0', NULL, '2025-07-14 14:32:38', '2025-07-16 18:31:21'),
(35, 'ZKTECO ZD192KSB - Monitor LED HD de 19 pulgadas / Operación 24/7 Ideal para Seguridad/ Resolución 14', 'ZD19-2K', 'WCH202404280541', 1273.73, 979.79, 1, NULL, NULL, 1, 19, 'ZKTECO ZD192KSB - Monitor LED HD de 19 pulgadas / Operación 24/7 Ideal para Seguridad/ Resolución 1440 x 900 / 1 Entrada de video HDMI y 1 VGA / Ángulo de Visión Horizontal 170° / Soporte VESA / Incluye Cable HDMI / Sin Altavoces #HD1 #MCI2', 'uploads/products/prod_68751ed5b780e.png', 'normal', '0', NULL, '2025-07-14 15:14:29', '2025-07-14 15:14:29'),
(36, 'ZKTECO MB10VL - Control de Asistencia y Acceso Básico Visible Light con Autenticación Facial (100 ro', 'MB10VL', 'CMYD232460060', 1868.30, 1437.15, 1, NULL, NULL, 1, 27, 'ZKTECO MB10VL - Control de Asistencia y Acceso Básico Visible Light con Autenticación Facial (100 rostros), Huella Digital BioID (500), Registro de 50,000 Eventos, Conexión TCP/IP y SSR (Reporte en Hoja de Cálculo Mediante USB) #ZKL #CM1', 'uploads/products/prod_687520ef4aebe.webp', 'normal', '0', NULL, '2025-07-14 15:23:27', '2025-07-14 15:23:27'),
(37, 'SAXXON OUTPCAT5ECOPEXT - Bobina de Cable UTP Cat5e 100% Cobre/ 305 Metros/ Exterior con Doble Forro/', 'AUTO-0001', 'TVD119047', 7.00, 2123.41, 0, NULL, NULL, 1, 28, 'SAXXON OUTPCAT5ECOPEXT - Bobina de Cable UTP Cat5e 100% Cobre/ 305 Metros/ Exterior con Doble Forro/ Color Negro/ Ideal para Cableado de Redes de Datos y Video/', 'uploads/products/prod_687521fe8539c.png', 'bobina', '0', NULL, '2025-07-14 15:27:58', '2025-07-30 14:11:06'),
(38, 'IMOU RANGER IQ (IPC-A26HIN-imou) - Cámara IP PT de 2 Megapíxeles/ WiFi/ Con Gateway de Alarma/Detecc', 'IPC-A26HIN-imou', '6939554968617', 2166.62, 1666.63, 1, NULL, NULL, 1, 29, 'IMOU RANGER IQ (IPC-A26HIN-imou) - Cámara IP PT de 2 Megapíxeles/ WiFi/ Con Gateway de Alarma/Detección de Humanos con IA/Lente de 3.6 mm/ AutoTracking/ Sirena Incorporada Personalizable/ Audio 2 Vias/ Modo de Privacidad/ Alarma de Sonido Anormal/', 'uploads/products/prod_6875234ee6520.png', 'normal', '0', NULL, '2025-07-14 15:33:34', '2025-07-14 15:33:34'),
(39, 'IMOU RANGER PRO (IPC-A26HN) - Cámara IP Domo Motorizado 2 Megapíxeles/ Audio Bidireccional/ Auto Tra', 'IPC-A26HN (Ranger Pro)', '6939554961076', 1420.09, 1092.38, 5, NULL, NULL, 1, 29, 'IMOU RANGER PRO (IPC-A26HN) - Cámara IP Domo Motorizado 2 Megapíxeles/ Audio Bidireccional/ Auto Tracking/ Modo de Privacidad/ Lente de 3.6mm/ Ir 10 Mts/ WiFi/ Compatible con Alexa y Asistente de Google/', 'uploads/products/prod_6875242c9d789.png', 'normal', '0', NULL, '2025-07-14 15:37:16', '2025-07-14 15:37:16'),
(40, 'DAHUA ASI1201E-D - Control de Acceso Independiente con Teclado Touch y Tarjetas ID/ 30,000 Usuarios,', 'DHI-ASI1201E-D', '6923172514646', 872.63, 671.25, 1, NULL, NULL, 1, 30, 'DAHUA ASI1201E-D - Control de Acceso Independiente con Teclado Touch y Tarjetas ID/ 30,000 Usuarios, 60,000 Registros/ TCP/IP/ Soporta Lectora Esclavo por Wiegand y RS-485/ Uso Exterior IP66/ Desbloqueo con Tarjeta, Pasword o Combinación/ #BuenFinDahua20', 'uploads/products/prod_6875253cdab1c.png', 'normal', '0', NULL, '2025-07-14 15:41:48', '2025-07-14 15:41:48'),
(41, 'DAHUA DH-SF1006LP - Switch PoE de 6 Puertos Fast Ethernet/ 4 Puertos PoE 10/100/ 36 Watts Totales/ 2', 'DH-SF1006LP', '6923172571403', 487.83, 375.25, 3, NULL, NULL, 1, 9, 'DAHUA DH-SF1006LP - Switch PoE de 6 Puertos Fast Ethernet/ 4 Puertos PoE 10/100/ 36 Watts Totales/ 2 Puertos Uplink RJ45 10/100/ PoE Watchdog/ Soporta hasta 250mts sobre UTP CAT 6/ Protección Contra Descargas/', 'uploads/products/prod_6875261ed4540.png', 'normal', '0', NULL, '2025-07-14 15:45:34', '2025-07-14 15:45:34'),
(42, 'DAHUA SF1010LP - Switch PoE de 10 Puertos Fast Ethernet/ 8 Puertos PoE 10/100 / 65 Watts Totales / 2', 'DH-SF1010LP', '6923172571410', 852.88, 656.06, 4, NULL, NULL, 1, 9, 'DAHUA SF1010LP - Switch PoE de 10 Puertos Fast Ethernet/ 8 Puertos PoE 10/100 / 65 Watts Totales / 2 Puertos Uplink RJ-45/ PoE watchdog/ Hasta 250 metros/ Switching 12 Gbps/ Protección Contra Descargas', 'uploads/products/prod_687526c4247a1.png', 'normal', '0', NULL, '2025-07-14 15:48:20', '2025-07-24 14:15:30'),
(43, 'DAHUA HAC-PT1200B-IL-A-E2Z - Cámara PT Dual de 2 Megapíxeles/ 6x Zoom Hibrido 2 Lentes/ Lentes de 2.', 'DH-HAC-PT1200BN-IL-A-E2Z', '6939554941788', 1214.49, 934.22, 3, NULL, NULL, 1, 6, 'DAHUA HAC-PT1200B-IL-A-E2Z - Cámara PT Dual de 2 Megapíxeles/ 6x Zoom Hibrido 2 Lentes/ Lentes de 2.8mm y 6mm/ Angulo de 109 y 47.2 mm/ Iluminador Dual Inteligente 50 Mts/ Micrófono Integrado/ Super Adapt/ IP66/ #LoNuevo #MCI1 #DP', 'uploads/products/prod_68752776bea16.png', 'normal', '0', NULL, '2025-07-14 15:51:18', '2025-07-14 15:51:18'),
(44, 'DAHUA ASI1201A-D- Teclado Touch para Control de Acceso con Paantalla LCD/ Lectora de Tarjetas ID/ Fu', 'DHI-ASI1201A-D', '6939554945380', 1565.03, 1203.87, 1, NULL, NULL, 1, 8, 'DAHUA ASI1201A-D- Teclado Touch para Control de Acceso con Paantalla LCD/ Lectora de Tarjetas ID/ Funcion Independiente/ 30,000 Usuarios/ 150,000 Registros/ Desbloqueo con Password y/o Tarjetas/ TCP/IP/ RS-485 y Wiegand/ Anti-passback/', 'uploads/products/prod_68752901ee378.png', 'normal', '0', NULL, '2025-07-14 15:57:53', '2025-07-14 15:57:53'),
(45, 'DAHUA HAC-HFW1200T-A - Cámara Bullet HDCVI 1080p micrófono integrado, lente 2.8 mm, ángulo de visión', 'DH-HAC-HFW1200TN-A-0280B-S4', '6923172593115', 457.13, 351.64, 10, NULL, NULL, 1, 31, 'DAHUA HAC-HFW1200T-A - Cámara Bullet HDCVI 1080p micrófono integrado, lente 2.8 mm, ángulo de visión de 103°, IR 30 m, IP67, carcasa metálica, DWDR, BLC, HLC. Ideal para vigilancia con alta definición y resistencia en exteriores. #MCI2', 'uploads/products/prod_687529e82512a.png', 'normal', '0', NULL, '2025-07-14 16:01:44', '2025-07-14 16:01:44'),
(46, 'DAHUA HAC-PT1239A-A-LED - Camara PT de 2 Megapixeles HDCVI/ Full Color/ Lente de 2.8 mm/ 106 Grados ', 'DH-HAC-PT1239AN-A-LED', '6923172599452', 880.80, 677.54, 4, NULL, NULL, 1, 6, 'DAHUA HAC-PT1239A-A-LED - Camara PT de 2 Megapixeles HDCVI/ Full Color/ Lente de 2.8 mm/ 106 Grados de Apertura/ Microfono Integrado/ 40 Metros de Iluminación LED/ Super Adapt/ WDR Real de 130 dB/ IP66/', 'uploads/products/prod_68752a8d240fd.png', 'normal', '0', NULL, '2025-07-14 16:04:29', '2025-07-14 16:04:29'),
(47, 'TRANSCEPTORES HD 2MP ENSON ENS-VT100 AHD/TVI/CVI PUSH-IN CON CONECTOR, AISLADOR DE RUIDO Y PROTECTOR', 'ENS-VT100', '', 27.17, 24.24, 340, NULL, NULL, 3, 5, 'TRANSCEPTORES HD 2MP ENSON ENS-VT100 AHD/TVI/CVI PUSH-IN CON CONECTOR, AISLADOR DE RUIDO Y PROTECTOR DE VOLTAJE. CONECTOR 100% COBRE', 'uploads/products/prod_68752b86c202e.jpg', 'par', '0', NULL, '2025-07-14 16:08:38', '2025-08-07 16:28:38'),
(48, 'IMOU Cruiser SC 3MP (IPC-K7FN-3H0WE) - Cámara IP PT de 3 Megapíxeles/ Wifi/ Full Color/Disuasión act', 'IPC-K7FN-3H0WE', '6976391034020', 1192.44, 917.26, 3, NULL, NULL, 1, 29, 'IMOU Cruiser SC 3MP (IPC-K7FN-3H0WE) - Cámara IP PT de 3 Megapíxeles/ Wifi/ Full Color/Disuasión activa luces Rojo-Azul/ Audio 2 Vías/ 30 Metros Visión Nocturna/ Sirena de 110 dB/ Smart tracking/ Ranura para MicroSD/ IP66/ #TopIMOU #CONGIMOU1', 'uploads/products/prod_68752f7970be1.png', 'normal', '0', NULL, '2025-07-14 16:25:29', '2025-07-25 14:17:44'),
(49, 'IMOU Cruiser SE+ 3MP (IPC-K7CN-3H1WE) - Cámara IP PT de 3MP con WiFi ofrece Full Color, audio bidire', 'IPC-K7CN-3H1WE', '6976391038943', 998.57, 768.13, 1, NULL, NULL, 1, 29, 'IMOU Cruiser SE+ 3MP (IPC-K7CN-3H1WE) - Cámara IP PT de 3MP con WiFi ofrece Full Color, audio bidireccional, 30m, micrófono y altavoz, disuasión activa sirena 110dB, autotracking, ranura MicroSD e IP66. #TopIMOU #INGJUL', 'uploads/products/prod_687530684564d.png', 'normal', '0', NULL, '2025-07-14 16:29:28', '2025-07-14 16:29:28'),
(50, 'Camara Bullet de 2 Megapixeles', 'DH-HAC-B1A21N-U-0280B', '6923172577917', 257.27, 197.90, 129, NULL, NULL, 1, 31, 'DAHUA HAC-B1A21N-U-28 - Camara Bullet de 2 Megapixeles/ Lente de 2.8 mm/ 30 Metros de IR/ 100 Grados de Apertura/ IP67/ Soporta: CVI/TVI/AHD y CVBS/ #LoNuevo #VolDH #M1', 'uploads/products/prod_687531cbac53b.png', 'normal', '0', NULL, '2025-07-14 16:35:23', '2025-08-06 22:29:58'),
(51, 'DAHUA IPC-WPT1339DD-SW-3E2-PV -Cámara IP PT Wifi Dual de 6 MP/ Dos lentes de 3 MP cada uno (fijo y P', 'DH-IPC-WPT1339DD-SW-3E2-PV', '6939554932311', 1249.33, 961.02, 5, NULL, NULL, 1, 29, 'DAHUA IPC-WPT1339DD-SW-3E2-PV -Cámara IP PT Wifi Dual de 6 MP/ Dos lentes de 3 MP cada uno (fijo y PT), Iluminador Dual Inteligente, IR 50m, Microfono y Altavoz Integrados, IA, Autotracking, Disuasión activa, Ranura MicroSD, IP66#LoNuevo #DDPT #DHWifi #MC', 'uploads/products/prod_6875337cf0e5c.png', 'normal', '0', NULL, '2025-07-14 16:42:36', '2025-07-25 21:47:27'),
(52, 'Dahua IPC-WPT1539DD-SW-5E2-PV - Cámara IP PT Wifi Dual de 10 MP, 2 Lentes de 5 MP cada uno (Fijo y P', 'DH-IPC-WPT1539DD-SW-5E2-PV', '6939554932489', 1385.11, 1065.47, -1, NULL, NULL, 1, 29, 'Dahua IPC-WPT1539DD-SW-5E2-PV - Cámara IP PT Wifi Dual de 10 MP, 2 Lentes de 5 MP cada uno (Fijo y PT), Iluminador Dual Inteligente/ IR de 50m, Microfono y Altavoz Integrados, IA, Autotracking, Disuación activa, Ranura MicroSD, IP66 #LoNuevo #DDPT #DHWifi', 'uploads/products/prod_68753478a3c10.png', 'normal', '0', NULL, '2025-07-14 16:46:48', '2025-07-24 15:17:35'),
(53, 'Cámara Bullet 5 Megapixeles', 'DH-HAC-B1A51N-0280B', 'PRODCM12098159', 461.47, 354.98, 1, NULL, NULL, 1, 31, 'DAHUA HAC-B1A51N-0280B - Cámara Bullet 5 Megapixeles con lente de 2.8 mm y ángulo de 106°. Visión nocturna IR de hasta 20 m, certificación IP67 para exteriores, compatible con CVI, CVBS, AHD y TVI. #HDCVI9.0 #5MP #VIVA #TECNOWEEN #TW1.', 'uploads/products/prod_6875368ccdd1d.png', 'normal', '0', NULL, '2025-07-14 16:55:40', '2025-07-14 16:55:40'),
(54, 'DAHUA PFA150 - Montaje para poste compatible con camaras PTZ series SD65XX / SD69 / SD63 / SD64 / SD', 'DH-PFA150-V2', '6939554903717', 474.25, 364.81, 1, NULL, NULL, 1, 32, 'DAHUA PFA150 - Montaje para poste compatible con camaras PTZ series SD65XX / SD69 / SD63 / SD64 / SD6A / SD6C', 'uploads/products/prod_6875373006060.png', 'normal', '0', NULL, '2025-07-14 16:58:24', '2025-07-14 16:58:24'),
(55, 'Camara bullet HDCVI 4 MP Metalica', 'DH-HAC-B2A41N-0280B', 'PRODCAM12328029', 541.29, 416.38, 1, NULL, NULL, 1, 31, 'DAHUA COOPER B2A41 - Camara bullet HDCVI 4 MP / TVI / A HD / CVBS / Lente 2.8 mm / Smart ir 20 Mts / IP67 / Apertura lente 97 grados / Metalica/', 'uploads/products/prod_6875380db06b3.png', 'normal', '0', NULL, '2025-07-14 17:02:05', '2025-07-14 17:02:05'),
(56, 'Cámara IP Domo Antivandalica 4k/ 8 Megapixeles', 'DH-IPC-HDBW2831EN-S-0280B-S2', 'PRODCM12724262', 2449.99, 1884.61, 1, NULL, NULL, 1, 10, 'DAHUA IPC-HDBW2831E-S-S2 -Cámara IP Domo Antivandalica 4k/ 8 Megapixeles/ Lente de 2.8mm/ 105 Grados de Apertura/ H.265+/ WDR Real de 120 dB/ IR de 30 Mts/ Videoanaliticos con IVS/ IP67/ IK10/ PoE/ Ranura para MicroSD/', 'uploads/products/prod_68753947d7aba.png', 'normal', '0', NULL, '2025-07-14 17:07:19', '2025-07-24 15:04:10'),
(57, 'Fuente de Alimentación de 4 Salidas de 11 - 15 Vcc / 5 Amper / Voltaje de Entrada 110- 240 Vac / Fus', 'PS-12-DC-4C', '697477291003', 254.88, 196.06, 0, NULL, NULL, 4, 33, 'Fuente de Alimentación de 4 Salidas de 11 - 15 Vcc / 5 Amper / Voltaje de Entrada 110- 240 Vac / Fusible Termico PTC Integrado para Protección / Salida de Voltaje Inteligente hasta 3 Amper por Salida', 'uploads/products/prod_68753c0fad4ae.png', 'normal', '0', NULL, '2025-07-14 17:19:11', '2025-08-07 22:54:50'),
(58, 'Cámara IP PT WiFi de 5 Megapíxeles con audio bidireccional', 'DH-P5B-PV', '6923172563477', 1047.15, 805.50, 3, NULL, NULL, 1, 2, 'DAHUA DH-P5B-PV- Cámara IP PT WiFi de 5 Megapíxeles con audio bidireccional (micrófono y altavoz), sirena de 110dB, WiFi 6, detección de personas y vehículos, IP65 y ranura MicroSD. #WiFiDahua#DHWifi', 'uploads/products/prod_68753dd30a2fe.png', 'normal', '0', NULL, '2025-07-14 17:26:43', '2025-07-22 16:26:52'),
(59, 'Cámara IP PT WiFi de 3 Megapíxeles con audio bidireccional', 'DH-P3B-PV', '6923172563460', 984.07, 756.98, 0, NULL, NULL, 1, 2, 'DAHUA DH-P3B-PV- Cámara IP PT WiFi de 3 Megapíxeles con audio bidireccional (micrófono y altavoz), sirena de 110 dB, WiFi 6, detección de personas y vehículos, IP65 y ranura MicroSD. #WiFiDahua#DHWifi', 'uploads/products/prod_68753fb492471.png', 'normal', '0', NULL, '2025-07-14 17:34:44', '2025-07-14 23:03:30'),
(60, '(AX PRO) Repetidor de Señal Hikvision / LED Indicador / Batería de Respaldo/ No compatible con el pa', 'DS-PR1-WB', '6941264077992', 1845.80, 1419.85, 1, NULL, NULL, 4, 34, '(AX PRO) Repetidor de Señal Hikvision / LED Indicador / Batería de Respaldo/ No compatible con el panel DS-PHA64-LP', 'uploads/products/prod_6875406929bc2.png', 'normal', '0', NULL, '2025-07-14 17:37:45', '2025-07-14 17:37:45'),
(61, 'Bala TURBOHD 2 Megapíxeles (1080p)', 'B8-TURBO-G2W', '300512030', 337.65, 259.73, 0, NULL, NULL, 4, 31, 'Bala TURBOHD 2 Megapíxeles (1080p) / METALICA / Gran Angular 103° / Lente 2.8 mm / IR EXIR Inteligente 20 mts / Exterior IP66 / TVI-AHD-CVI-CVBS', 'uploads/products/prod_687542053584b.png', 'normal', '0', NULL, '2025-07-14 17:44:37', '2025-07-26 14:23:07'),
(62, '(AX HYBRID PRO) Teclado Compatible con el Panel Hybrid Pro Hikvision DS-PHA64-LP y DS-PHA64-LP(B) / ', 'DS-PK1-LRT-HWB', '6931847164393', 1424.31, 1095.62, 1, NULL, NULL, 4, 5, '(AX HYBRID PRO) Teclado Compatible con el Panel Hybrid Pro Hikvision DS-PHA64-LP y DS-PHA64-LP(B) / Pantalla LCD / 2 zonas cableadas / 1 salida de alarma / 64 Llaveros', 'uploads/products/prod_68754383a52f6.png', 'normal', '0', NULL, '2025-07-14 17:50:59', '2025-07-21 23:02:46'),
(63, 'Kit de Videopotero Hibrido', 'DHI-KTH01', '6923172566768', 1996.03, 1535.41, 4, NULL, NULL, 1, 35, 'DAHUA DHI-KTH01 - Kit de Videopotero Hibrido/ Frente de Calle Analogico con Camara de 1.3 MP/ Monitor Touch de 7 Pulgadas WiFi/ 4 Hilos al Frente de Calle/ 6&1 E&S de Alarma/ Soporta Camaras IP/ Compattible App DMSS/ Apertura remota puerta / #VDP #MCI2', 'uploads/products/prod_687543c031ca2.png', 'normal', '0', NULL, '2025-07-14 17:52:00', '2025-07-25 18:47:43'),
(64, 'HiLook Series / Turret IP 4 Megapixel / 30 mts IR / Exterior IP67 / PoE / Lente 2.8 mm / WDR 120 dB ', 'IPC-T240H(C)', '6941264092483', 1307.48, 1005.75, 1, NULL, NULL, 4, 7, 'HiLook Series / Turret IP 4 Megapixel / 30 mts IR / Exterior IP67 / PoE / Lente 2.8 mm / WDR 120 dB / ONVIF', 'uploads/products/prod_6875466cb712c.png', 'normal', '0', NULL, '2025-07-14 18:03:24', '2025-07-14 18:03:24'),
(65, 'CABLE VORAGO VGA MACHO-MACHO 10 METROS', 'CAB-205', NULL, 374.15, 287.81, 1, NULL, NULL, 5, 5, 'CABLE VORAGO VGA MACHO-MACHO 10 METROS NEGRO CAB-205', 'uploads/products/prod_6875467472a24.jpeg', 'normal', '0', NULL, '2025-07-14 18:03:32', '2025-07-14 18:03:32'),
(66, 'Panel de Alarma AX HYBRID PRO', 'DS-PHA64-LP(B)', NULL, 2819.66, 2168.97, 1, NULL, NULL, 4, 15, 'Panel de Alarma AX HYBRID PRO / Wi-Fi / 8 Zonas Cableadas Directas al Panel / 56 Zonas Expandibles: Inalámbricas o Cableadas por Medio de Módulos / Soporta Integración de Batería de Respaldo / 32 Particiones (Áreas)', 'uploads/products/prod_6875475556234.png', 'normal', '0', NULL, '2025-07-14 18:07:17', '2025-07-30 14:24:24'),
(67, 'Router Inalámbrico WISP en Banda 2.4 GHz / Hasta 300 Mbps / 4 Puertos 10/100 Mbps / 2 Antenas Omnidi', 'DS-3WR3N', '6931847137236', 272.51, 209.62, 1, NULL, NULL, 4, 13, 'Router Inalámbrico WISP en Banda 2.4 GHz / Hasta 300 Mbps / 4 Puertos 10/100 Mbps / 2 Antenas Omnidireccional de 5 dBi / Interior', 'uploads/products/prod_687547f314c3d.png', 'normal', '0', NULL, '2025-07-14 18:09:55', '2025-07-25 15:16:40'),
(68, 'TP-Link MERCUSYS Halo Mesh', 'Halo H30(2-Pack)', '849439000669', 1285.57, 988.90, 2, NULL, NULL, 6, 36, 'TP-Link MERCUSYS Halo Mesh, Sistema WiFi Mesh para tu hogar, Doble Banda AC1200, 10/100Mbps Puertos, Cobertura de hasta 2,800 Pies Cuadrados y Más de 100 Dispositivos. Halo H30(2-Pack)', 'uploads/products/prod_6875492b48ccd.jpg', 'normal', '0', NULL, '2025-07-14 18:15:07', '2025-07-18 23:39:17'),
(69, 'DAHUA HAC-HFW1801CN-A-0280B-S2 - Cámara Bullet 4k con Micrófono Integrado/ 8 Megapixeles/ Lente de 2', 'DH-HAC-HFW1801CN-A', NULL, 900.45, 692.65, 4, NULL, NULL, 1, 31, 'DAHUA HAC-HFW1801CN-A-0280B-S2 - Cámara Bullet 4k con Micrófono Integrado/ 8 Megapixeles/ Lente de 2.8 mm/ 106 Grados de Apertura/ 30 Metros de IR/ WDR Real de 120 dB/ Soporta: CVI/TVI/AHD/CVBS/ #ProHDCVI', 'uploads/products/prod_68754be297a18.png', 'normal', '0', NULL, '2025-07-14 18:26:42', '2025-07-14 18:26:42'),
(70, 'Gabinete de Acero IP66 Uso en Intemperie (250 x 300 x 150 mm) con Placa Trasera Interior Metálica y ', 'PST-2530-15A', 'PRODGAB20063699', 768.16, 590.89, -3, NULL, NULL, 4, 37, 'Gabinete de Acero IP66 Uso en Intemperie (250 x 300 x 150 mm) con Placa Trasera Interior Metálica y Compuerta Inferior Atornillable (Incluye Chapa y Llave T).', 'uploads/products/prod_6875558ca5ad9.png', 'normal', '0', NULL, '2025-07-14 19:07:56', '2025-07-23 14:18:49'),
(71, 'GABINETE ENSON LINCE7', 'lince7', 'LINCE07ENE10', 256.10, 197.00, 1, 1, 5, 4, 37, 'GABINETE ENSON LINCE7 PARA SIRENA DE 15W COMPATIBLE CON MODELO PM-SRE108 Y COMPARTIMIENTO PARA TAMPER', 'uploads/products/prod_68755ec387ea7.png', 'normal', '0', NULL, '2025-07-14 19:47:15', '2025-07-30 14:24:24'),
(72, 'Cable UTP CCA, categoría 5E, color negro', 'SAXXON OUTP5ECCAEXT', 'TVD119038', 7.00, 867.88, 915, 305, NULL, 1, 28, 'SAXXON OUTP5ECCAEXT - Cable UTP CCA, categoría 5E, color negro, 305 metros para exterior, con 4 pares y doble forro', 'uploads/products/prod_6875614596ed8.png', 'bobina', '0', NULL, '2025-07-14 19:57:57', '2025-08-07 15:27:09'),
(73, 'DISCO DE 1 TB NEW PULL 3.5', 'AUTO-0002', 'PRODDIS33296609', 680.11, 523.16, 3, 2, 5, 2, 38, 'Disco Duro GENERICO New Pull, 1 TB, SATA, 3.5 pulgadas, PC', 'uploads/products/prod_687589ee85059.png', 'normal', '0', NULL, '2025-07-14 22:51:26', '2025-08-06 17:32:12'),
(74, 'BATERIA DVR CR 1220', 'Tianqiu Cr1220', '6927799682269', 52.50, 35.00, 30, 10, NULL, 7, 5, 'Pilas Baterias Tianqiu Cr1220 Tamaño Botón 3 Voltios', 'uploads/products/prod_6876a34e818da.png', 'normal', '0', NULL, '2025-07-15 18:51:58', '2025-07-26 14:53:42'),
(75, 'Disco Duro New Pull, 500 GB', 'new pull 500g', '0A37097', 349.39, 268.76, 2, 1, 10, 2, 38, 'Disco Duro GENERICO New Pull, 500 GB, SATA, 3.5 pulgadas, PC', 'uploads/products/prod_6876a6942e95a.png', 'normal', '0', NULL, '2025-07-15 19:05:56', '2025-08-07 15:01:40'),
(76, 'Regulador VICA', 'vica t1', '2430\'19523792', 289.90, 223.00, 0, 2, 6, 2, 33, 'Regulador VICA T1 750VA/400WATTS - Voltaje 120 VCA +/-10% 60Hz - breaker térmico - 8 tomas protegidas - indicadores LED - empotrable en pared', 'uploads/products/prod_6876bc8210adb.png', 'normal', '0', NULL, '2025-07-15 20:39:30', '2025-07-22 14:24:04'),
(77, 'Disco Duro de Estado Solido de 512 Gb', 'DHI-SSD-C800AS512G', '9L0352BPAA02535', 1012.70, 779.00, 2, 1, 2, 1, 38, 'DAHUA SSD-C800AS512G - Disco Duro de Estado Solido de 512 Gb 2.5\"/ Alta Velocidad/ Puerto 6 Gb/s SATA/ 3D TLC/ Para Usos Multiples', 'uploads/products/prod_6876bf41e8a3f.png', 'normal', '0', NULL, '2025-07-15 20:51:13', '2025-07-15 20:51:13'),
(78, 'PANEL DE ALARMA VISTA 48', 'VISTA48/6162RF', '781410804791', 1417.00, 1090.00, 1, 1, 1, 4, 15, 'PANEL de Alarma de 8 Zonas con Teclado LCD Alfanumerico', 'uploads/products/prod_6876c152664d4.png', 'normal', '0', NULL, '2025-07-15 21:00:02', '2025-07-15 21:00:02'),
(79, 'Fuente de poder regulada de 12 Vcc 4.1 Amperes', 'PSU1204-D', 'tvn0830052', 224.83, 172.95, 3, 1, 10, 1, 33, 'SAXXON PSU1204D - Fuente de poder regulada de 12 Vcc 4.1 Amperes/ Con Cable de 1.2 Metros/ Para Usos Multiples: Sistemas de CCTV, Acceso, ETC/ Certificación UL', 'uploads/products/prod_6877ec5f3bfc6.png', 'normal', '0', NULL, '2025-07-16 18:15:59', '2025-08-07 22:54:34'),
(80, 'REGISTRO BLANCO DE 8X8', '003-CE808045LH', 'PRODREG99720773', 24.05, 18.50, 0, 10, 200, 8, 39, 'Caja Estanca Lisa 8x8x45 Conexión En Exterior Ip55', 'uploads/products/prod_68781375dab2d.png', 'normal', '0', NULL, '2025-07-16 21:02:45', '2025-08-07 15:27:09'),
(81, 'CABLE HDMI DE 1.8 MTS MANHATTAN', '306119', '766623306119', 49.40, 38.00, 2, 1, 5, 2, 28, 'Cable HDMI Blindado de 1.8mts; HDMI Macho a HDMI Macho resolucion 4K 30Hz, 3D.', 'uploads/products/prod_68796014b01ea.png', 'normal', '0', NULL, '2025-07-17 20:41:56', '2025-08-06 18:26:30'),
(82, 'DAHUA HAC-HFW1200CN-A - Cámara Bullet 1080p/ Micrófono Integrado/ Lente de 2.8mm/ 30 Mts de Ir/ IP67', 'DH-HAC-HFW1200CN-A-0280B-S5', '6923172592842', 435.47, 334.98, 0, NULL, NULL, 1, 31, 'DAHUA HAC-HFW1200CN-A - Cámara Bullet 1080p/ Micrófono Integrado/ Lente de 2.8mm/ 30 Mts de Ir/ IP67/ Policarbonato/ CVI/CVBS/AHD/TVI/ BLC/HLC/DWDR #VolDH #IngDahua', 'uploads/products/prod_687e71410a894.png', 'normal', '0', NULL, '2025-07-21 16:56:33', '2025-07-21 16:57:25'),
(83, 'FUENTE DE ALIMENTACION DE 12 VOLTS A 2 AMP', 'Huawei12V', '2102220856aah6063392', 42.90, 33.00, 90, 5, 100, 9, 33, 'FUENTE DE ALIMENTACION DE ENERGIA DE 12 VOLTS A 2 AMP HAWEI', 'uploads/products/prod_687e7414a6f8e.png', 'normal', '0', NULL, '2025-07-21 17:08:36', '2025-08-07 15:27:09'),
(84, 'DAHUA DH-IPC-HDW2449T-S-PRO', 'DAHUA DH-IPC-HDW2449T-S-PRO', '6939554949210', 2026.35, 1558.73, 0, 2, 5, 1, 11, 'DAHUA DH-IPC-HDW2449T-S-PRO - Cámara IP Domo de 4 MP ofrece tecnología WizColor y WizSense con IA, SMD Plus, lente de 3.6 mm, micrófono integrado, WDR 120 dB, ranura MicroSD, PoE, y protección IP67', 'uploads/products/prod_687eaef388ff5.png', 'normal', '0', NULL, '2025-07-21 21:19:47', '2025-07-22 15:01:47'),
(85, 'NVR de 8 Megapixeles/ 4k', 'DAHUA NVR2116HS-I2', '6923172504944', 2686.39, 2066.45, 0, 1, 1, 1, 40, 'DAHUA NVR2116HS-I2 - NVR de 8 Megapixeles/ 4k/ 16 Canales IP/ WizSense/ Con IA/ Rendimiento de 144 Mbps/ Smart H.265+/ 1 Ch de Reconocimiento Facial o 1 Canal de Protección Perimetral o 4 Canales de SMD/ 1 Puerto SATA 10 TB/ HDMI&VGA/ Onvif', 'uploads/products/prod_687eafb146b3b.png', 'normal', '0', NULL, '2025-07-21 21:22:57', '2025-07-22 15:01:47'),
(86, 'Switch Poe de 8 Puertos', 'DAHUA DH-CS4010-8ET-60', '6923172566201', 1061.11, 816.24, 0, 1, 2, 1, 9, 'DAHUA DH-CS4010-8ET-60 Switch Poe de 8 Puertos / Poe Inteligente/ 60Watts Totales/ 2 Puertos uplink/ Switching 4.8Gbps/ Protección de Descargas/ DoLynk Care/', 'uploads/products/prod_687eb110b68b1.png', 'normal', '0', NULL, '2025-07-21 21:28:48', '2025-07-22 15:01:47'),
(87, 'Detector PIR Cableado para Interior', 'DS-PDP18-EG2(P)', '6941264044208', 184.21, 141.70, 0, 1, 10, 4, 15, 'Detector PIR Cableado para Interior / Inmunidad a Mascotas / Rango de Detección de 18 mts / Angulo de 85.9° de Cobertura', 'uploads/products/prod_687ec82441248.png', 'normal', '0', NULL, '2025-07-21 23:07:16', '2025-07-28 14:41:21'),
(88, 'Sirena de 110dB / 15 W', 'SF-520A', 'PRODSIR39312218', 106.64, 82.03, 2, 1, 3, 4, 15, 'Sirena de 110dB / 15 W / Pequeña y Potente / Cableada', 'uploads/products/prod_687ec8ba8c189.png', 'normal', '0', NULL, '2025-07-21 23:09:46', '2025-07-30 14:24:24'),
(89, 'Gabinete de Sirena de 15 Watt', 'IMP15NV2', 'PRODGAB39456470', 268.07, 206.21, 2, 1, 5, 4, 15, 'Gabinete diseñado para el Resguardo de Sirena de 15 Watt.', 'uploads/products/prod_687ec950d2085.png', 'normal', '0', NULL, '2025-07-21 23:12:16', '2025-07-21 23:12:16'),
(90, 'Batería 12 V / 7 Ah', 'LK712', 'PRODBAT39589335', 334.17, 257.05, 0, 1, 2, 4, 15, 'Batería 12 V / 7 Ah / UL / Tecnología AGM / Vida útil promedio 5 años / Uso en equipo electrónico, Alarmas de Intrusión / Incendio / Control de acceso / Video Vigilancia / Terminales F1 ( Incluye adaptador F2 )', 'uploads/products/prod_687ec9cc4e8d6.png', 'normal', '0', NULL, '2025-07-21 23:14:20', '2025-07-28 14:41:21'),
(91, 'Contacto Magnético de Uso Rudo', 'DS-PD1-MC-RS', '6954273685720', 235.61, 181.24, 1, 1, 2, 4, 15, 'Contacto Magnético de Uso Rudo / Uso en Cortinas o Puertas de Emergencia de Metal o Madera / Interior', 'uploads/products/prod_687eca5721e11.png', 'normal', '0', NULL, '2025-07-21 23:16:39', '2025-07-30 14:24:24'),
(92, 'Extensor PoE', 'DS-3E0103DP-E/R', '6942160472959', 373.82, 287.55, 2, 1, 5, 4, 15, 'Extensor PoE+ (30 Watts Entrada) / 100 Mbps con 2 Salidas PoE+ (30 Watts Totales de Salida) / Uso Interior / Podemos Alimentar 2 Cámaras IP', 'uploads/products/prod_687ecdf95ec41.png', 'normal', '0', NULL, '2025-07-21 23:32:09', '2025-07-23 15:04:17'),
(93, 'ACRILICO BLANCO', '18570', '7506240602088', 76.70, 59.00, 3, 1, 10, 8, 5, 'Sellador acrílico Truper blanco 280 ml. Mod. SACRI-100B', 'uploads/products/prod_687faa2c4417c.png', 'normal', '0', NULL, '2025-07-22 15:11:40', '2025-08-06 22:29:58'),
(94, 'Contacto Magnético para Puertas y Ventanas', 'DS-PD1-MC-WS', '6954273685706', 40.11, 30.85, 0, 1, 10, 4, 15, 'Contacto Magnético para Puertas y Ventanas / Uso en Interior / ABS', 'uploads/products/prod_687fab3b0bbc4.png', 'normal', '0', NULL, '2025-07-22 15:16:11', '2025-07-28 14:41:21'),
(95, 'Contacto magnético para puertas y ventanas color blanco', 'SF-2033', 'PRODCON97723787', 38.01, 29.24, 3, 1, 5, 4, 15, 'Contacto magnético para puertas y ventanas color blanco / GAP: 30mm', 'uploads/products/prod_687fad0f5bd90.png', 'normal', '0', NULL, '2025-07-22 15:23:59', '2025-07-22 15:23:59'),
(96, 'Disco duro de 2TB', 'WD23PURZ', 'PRODDIS26358607', 1631.46, 1254.97, 0, 1, 1, 1, 28, 'WESTERN DIGITAL WD23PURZ - Disco duro de 2TB / Serie Purple para videovigilancia / Trabajo 24/7/ Interface: Sata 6 Gb/s/ Hasta 64 Cámaras/ Hasta 8 Bahías de Discos Duros/ 3 Años de Garantía', 'uploads/products/prod_68801ca7619f2.png', 'normal', '0', NULL, '2025-07-22 23:20:07', '2025-07-24 14:59:45'),
(97, 'Cámara IP Domo Antivandalica de 2 Megapixeles', 'DH-IPC-HDBW1230EN-0280B-S4', '6923172500670', 1009.40, 776.46, 0, 1, 1, 1, 2, 'DAHUA IPC-HDBW1230E S4- Cámara IP Domo Antivandalica de 2 Megapixeles/ Lente de 2.8 mm/ 104 Grados de Apertura/ Metalica/ IR de 30 Mts/ IP67/ IK10/ PoE/ DWDR', 'uploads/products/prod_68801d31deb4b.png', 'normal', '0', NULL, '2025-07-22 23:22:25', '2025-07-24 15:04:10'),
(98, 'NVR de 8 canales 5MP Lite', 'DH-XVR5108HS-I3', '6939554930386', 1997.74, 1536.72, 0, 1, 1, 1, 2, 'DAHUA XVR5108HS-I3 - DVR de 8 canales 5MP Lite, WizSense con IA, H.265+, 8 Canales HDCVI+4 IP o hasta 12 canales IP, 1 canal de reconocimiento facial, SMD Plus, 1 canal de protección perimetral y 1 bahía HDD', 'uploads/products/prod_68801dfc7dac9.png', 'normal', '0', NULL, '2025-07-22 23:25:48', '2025-07-24 14:56:15'),
(99, 'MICRO SD ADATA DE 128 GB', 'MEMDAT2890', '4713218461940', 129.51, 99.62, 9, 2, 10, 2, 41, 'Memoria Micro SD ADATA AUSDX128GUICL10A1-RA1, 128 GB, 100 MB/s, Negro, Clase 10', 'uploads/products/prod_68824fb64260c.png', 'normal', '0', NULL, '2025-07-24 15:20:02', '2025-07-24 15:25:39'),
(100, 'REGULADOR DE VOLTAJE FORZA', 'FAB: FVR-901M', '798302107977', 226.34, 174.11, 1, 1, 5, 10, 33, 'REGULADOR AUTOMATICO DE VOLTAJE FORZA FVR-901M 900VA / 450W 8 SLDS MONTAJE PARED', 'uploads/products/prod_688296a90959e.png', 'normal', '0', NULL, '2025-07-24 20:25:13', '2025-08-07 15:01:40'),
(101, 'Control de Acceso y Asistencia con Reconocimiento Facial', 'DAHUA ASI3203E', '6923172514097', 1103.22, 848.63, 1, 1, 1, 1, 42, 'DAHUA ASI3203E - Control de Acceso y Asistencia con Reconocimiento Facial / Pantalla de 2.4\" / 1,000 Rostros / 1,000 Usuarios / 1,000 Contraseñas / 3,000 tarjetas Mifare / Admite 50 administradores / 100,000 registros / Salida 12v 1A', 'uploads/products/prod_6883d2607cc44.png', 'normal', '0', NULL, '2025-07-25 18:52:16', '2025-07-25 18:52:16'),
(102, 'Cerradura Magnetica de 600 Lbs', 'DAHUA ASF280A-V1', '6923169766805', 498.95, 383.81, 1, 1, 1, 1, 43, 'DAHUA ASF280A-V1 - Cerradura Magnetica de 600 Lbs/ 280 Kg/ Indicador de Estado LED / Material Antidesgaste y Magnetismo Anti-residual/ Aplicaciones en Puerta de Metal, Madera, Etc/ Ideal para Controles de Acceso y Videoporteros', 'uploads/products/prod_6883d2e9cadc2.png', 'normal', '0', NULL, '2025-07-25 18:54:33', '2025-07-25 18:54:33'),
(103, 'Soporte en ZL', 'DAHUA ASF280ZL-V1', '6923169766843', 201.81, 155.24, 1, 1, 1, 1, 5, 'DAHUA ASF280ZL-V1 - Soporte en ZL/ Para instalación de Contra Chapa Magnetica/ Compatible con: ASF280A-V1/ Aleación de Aluminio/', 'uploads/products/prod_6883d35668750.png', 'normal', '0', NULL, '2025-07-25 18:56:22', '2025-07-25 18:56:22'),
(104, 'DVR de 16 canales 1080p Lite WizSense', 'DAHUA XVR1B16-I', '6939554919299', 1317.25, 1013.27, 1, 1, 3, 1, 1, 'DAHUA XVR1B16-I -DVR de 16 canales 1080p Lite WizSense y Cooper-I. Compatible con H.265+, admite hasta18 canales IP y 8 canales con SMD Plus. Búsqueda inteligente personas y vehículo', 'uploads/products/prod_688b8823e4556.png', 'normal', '0', NULL, '2025-07-31 15:13:39', '2025-08-05 14:58:07'),
(105, 'Energizador 2 Joules / 6000 mts lineales de proteccion', 'SF-SMART-FENCE', '046202411281714', 2904.81, 2234.47, 1, 1, 2, 4, 44, 'Energizador 2 Joules / 6000 mts lineales de proteccion / WIFI y Receptor para Control Remoto y Tag integrados', 'uploads/products/prod_68911e47a1009.png', 'normal', NULL, NULL, '2025-08-04 20:55:35', '2025-08-04 20:57:18'),
(106, 'Fuente PREMIUM / 11-15 V / 18 canales / 30 A', 'XP18DC30UD', '697477291078', 1594.79, 1226.76, 0, 1, 1, 4, 33, 'Fuente PREMIUM / 11-15 V / 18 canales / 30 A / Alta tecnología en seguridad / Doble ventilador / Hasta 1.85 Amperes por Salida / Filtro de Ruido por canal / Diseño de alta gama.', 'uploads/products/prod_68911f6b9fedf.png', 'normal', NULL, NULL, '2025-08-04 21:00:27', '2025-08-07 22:55:43'),
(107, 'Batería 12 V / 1.2 Ah', 'LK1.212', 'PRODBAT41322529', 158.95, 122.27, 2, 1, 3, 4, 33, 'Batería 12 V / 1.2 Ah / UL / 5 Años vida útil promedio / Para uso en equipo electrónico /Alarmas de Intrusión / Incendio/ Control de acceso / Video Vigilancia / Terminales F1 ( Incluye adaptador F2 )', 'uploads/products/prod_6891201f3242b.png', 'normal', NULL, NULL, '2025-08-04 21:03:27', '2025-08-04 21:03:50'),
(108, 'DAHUA DHI-ARD323-W2 - Contacto Magnético Inalámbrico Interior / 1 Entrada de Contacto Seco / Led Indicador / Alarma de Batería Baja / Detección de Intensidad de Señal / Función de Salto de Frecuencia /  ', 'DAH-6155', NULL, 450.00, 354.00, 0, NULL, NULL, NULL, NULL, 'DAHUA DHI-ARD323-W2 - Contacto Magnético Inalámbrico Interior / 1 Entrada de Contacto Seco / Led Indicador / Alarma de Batería Baja / Detección de Intensidad de Señal / Función de Salto de Frecuencia /  ', 'uploads/products/prod_689246390f484.png', NULL, NULL, NULL, '2025-08-05 17:58:17', '2025-08-05 17:58:17'),
(109, 'DAHUA DHI-ARC3000H-W2 - Panel de Alarma Inalámbrico con Comunicación Wifi y Ethernet/ Soporta Hasta 150 Dispositivos (6 Sirenas y 64 Controles)/ Batería de Respaldo/ Zumbador Integrado/ Luz Indicadora de Estado/ Enlace Por App/', 'DAH-7866', NULL, 1758.00, 1407.00, 0, NULL, NULL, NULL, NULL, 'DAHUA DHI-ARC3000H-W2 - Panel de Alarma Inalámbrico con Comunicación Wifi y Ethernet/ Soporta Hasta 150 Dispositivos (6 Sirenas y 64 Controles)/ Batería de Respaldo/ Zumbador Integrado/ Luz Indicadora de Estado/ Enlace Por App/', 'uploads/products/prod_689246d4ea9aa.png', NULL, NULL, NULL, '2025-08-05 18:00:52', '2025-08-05 18:00:52'),
(110, 'DAHUA DHI-ARD323-W2 - Contacto Magnético Inalámbrico Interior / 1 Entrada de Contacto Seco / Led Indicador / Alarma de Batería Baja / Detección de Intensidad de Señal / Función de Salto de Frecuencia /  PARA VENTANALES', 'DAH-3318', NULL, 450.00, 358.00, 0, NULL, NULL, NULL, NULL, 'DAHUA DHI-ARD323-W2 - Contacto Magnético Inalámbrico Interior / 1 Entrada de Contacto Seco / Led Indicador / Alarma de Batería Baja / Detección de Intensidad de Señal / Función de Salto de Frecuencia /  PARA VENTANALES', 'uploads/products/prod_6892474db0a7c.png', NULL, NULL, NULL, '2025-08-05 18:02:53', '2025-08-05 18:02:53'),
(111, 'DAHUA DHI-ARD323-W2 - Contacto Magnético Inalámbrico Interior / 1 Entrada de Contacto Seco / Led Indicador / Alarma de Batería Baja / Detección de Intensidad de Señal / Función de Salto de Frecuencia /  PARA PUERTA', 'DAH-1534', NULL, 450.00, 358.00, 0, NULL, NULL, NULL, NULL, 'DAHUA DHI-ARD323-W2 - Contacto Magnético Inalámbrico Interior / 1 Entrada de Contacto Seco / Led Indicador / Alarma de Batería Baja / Detección de Intensidad de Señal / Función de Salto de Frecuencia /  PARA PUERTA', 'uploads/products/prod_689247940e095.png', NULL, NULL, NULL, '2025-08-05 18:04:04', '2025-08-05 18:04:04'),
(112, 'DAHUA DHI-ARA43-W2 - Repetidor Inalámbrico de Alarma/ Puede Conectar hasta 32 Dispositivos Inalámbricos/ Indicador de Estado con Luz Led/', 'DAH-9669', NULL, 1350.00, 1080.00, 0, NULL, NULL, NULL, NULL, 'DAHUA DHI-ARA43-W2 - Repetidor Inalámbrico de Alarma/ Puede Conectar hasta 32 Dispositivos Inalámbricos/ Indicador de Estado con Luz Led/', 'uploads/products/prod_689248c44ad1a.png', NULL, NULL, NULL, '2025-08-05 18:09:08', '2025-08-05 18:09:08'),
(113, 'CAMARA FULL COLOR 1080P  SMART LIGTH', 'DH-HAC-HFW1209CN-A-LED-S3', '', 480.00, 349.28, 1, NULL, NULL, 1, NULL, 'DAHUA HAC-HFW1209CN-A-LED-S3 - Cámara Bullet Full Color 1080p/ Llente de 2.8 mm/ 107.8° de Apertura/ Micrófono Integrado/ 30 Metros de Luz Visible/ DWDR/ Starlight/ IP67/ Compatible con CVI/AHD/CVBS', 'uploads/products/prod_68924f4652a57.png', 'normal', '', NULL, '2025-08-05 18:36:54', '2025-08-06 20:35:43'),
(114, 'Conector macho', 'ENS-MC01 MACHO', '', 10.00, 7.00, 0, NULL, NULL, NULL, NULL, NULL, 'uploads/products/prod_6893890aa12c8.png', 'normal', 'Piezas', NULL, '2025-08-06 16:53:15', '2025-08-06 16:55:38'),
(115, 'Fuente de Poder Regulada de 5 Vcc 2 Amperes/ Para Usos Multiples/ Acceso, Asistencia, CCTV, Etc./', 'PSU0502-E', NULL, 100.00, 52.00, 0, NULL, NULL, NULL, NULL, '', 'uploads/products/prod_68938d9ec86c3.png', NULL, NULL, NULL, '2025-08-06 17:15:10', '2025-08-06 17:15:10');
INSERT INTO `products` (`product_id`, `product_name`, `sku`, `barcode`, `price`, `cost_price`, `quantity`, `min_stock`, `max_stock`, `supplier_id`, `category_id`, `description`, `image`, `tipo_gestion`, `unit_measure`, `is_active`, `created_at`, `updated_at`) VALUES
(116, 'Dahua IPC-WPT1539DD-SW-5E2-PV - Cámara IP PT Wifi Dual de 10 MP, 2 Lentes de 5 MP cada uno (Fijo y PT), Iluminador Dual Inteligente/ IR de 50m, Microfono y Altavoz Integrados, IA, Autotracking, Disuación activa, Ranura MicroSD, IP66', 'DAH-3041', NULL, 1739.00, 1077.00, 0, NULL, NULL, NULL, NULL, 'Dahua IPC-WPT1539DD-SW-5E2-PV - Cámara IP PT Wifi Dual de 10 MP, 2 Lentes de 5 MP cada uno (Fijo y PT), Iluminador Dual Inteligente/ IR de 50m, Microfono y Altavoz Integrados, IA, Autotracking, Disuación activa, Ranura MicroSD, IP66', 'uploads/products/prod_6894d100591b5.png', NULL, NULL, NULL, '2025-08-07 16:14:56', '2025-08-07 16:14:56');

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
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `servicio_id` int(11) NOT NULL,
  `nombre` varchar(300) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`servicio_id`, `nombre`, `descripcion`, `categoria`, `precio`, `imagen`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Instalación de cámara', 'Instalación completa de cámara de seguridad incluyendo cableado y configuración.', 'Instalación', 150.00, NULL, 0, '2025-07-31 16:31:10', '2025-07-31 21:10:49'),
(2, 'Configuración de DVR', 'Configuración y programación de grabador digital de video.', 'Configuración', 80.00, NULL, 0, '2025-07-31 16:32:19', '2025-07-31 21:10:52'),
(3, 'Servicio de Cableado UTP desde SITE a Terminales de Uso /  Cable UTP Cat5e 100% Cobre / Uso Interior', 'Servicio de Cableado UTP\r\ndesde SITE a Terminales de Uso / \r\nCable UTP Cat5e 100% Cobre / Uso Interior\r\nSKU: CYA-INTER1-UTP', 'Instalación', 550.00, 'servicio_1753996243_688bdbd394f58.png', 1, '2025-07-31 21:10:43', '2025-07-31 21:10:43'),
(4, 'Servicio de Instalación y Configuración  de Cámaras', 'Servicio de Instalación y Configuración \r\nde Cámaras \r\nSKU: CYA-EXTERA1-UTP', 'Instalación', 0.00, 'servicio_1754002609_688bf4b1d977a.png', 1, '2025-07-31 22:56:49', '2025-07-31 22:57:42'),
(5, 'Servicio de Instalación Configuración de Alarma', 'Servicio de Instalación Configuración de Alarma', 'Instalación', 0.00, 'servicio_1754417648_689249f0aa3bd.png', 1, '2025-08-05 18:14:08', '2025-08-05 18:14:08'),
(6, 'Servicio Técnico de Cableado a Cámara (Solo mano de obra)', '', 'Mantenimiento', 450.00, 'servicio_1754586485_6894dd759fea8.png', 1, '2025-08-07 17:08:05', '2025-08-07 17:08:05');

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

--
-- Volcado de datos para la tabla `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `name`, `contact_name`, `phone`, `email`, `address`) VALUES
(1, 'TVC', '', '', '', ''),
(2, 'CT', '', '', '', ''),
(3, 'Tecnosinergia', '', '', '', ''),
(4, 'Syscom', '', '', '', ''),
(5, 'PCH', '', '', '', ''),
(6, 'Amazon', '', '', '', ''),
(7, 'ALIEXPRESS', '', '', '', ''),
(8, 'Vaqueiros', '', '', '', ''),
(9, 'MERCADO LIBRE', '', '', '', ''),
(10, 'CVA', '', '', '', '');

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

CREATE TABLE `tecnicos` (
  `tecnico_id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tecnicos`
--

INSERT INTO `tecnicos` (`tecnico_id`, `codigo`, `nombre`, `fecha_ingreso`, `created_at`, `updated_at`) VALUES
(1, 'TEC-0001', 'RAUL CHAN', '2025-07-15', '2025-07-15 22:34:18', '2025-07-15 22:34:18'),
(2, 'TEC-0002', 'GERARDO VICINAIZ', '2025-07-15', '2025-07-15 22:34:55', '2025-07-15 22:34:55'),
(3, 'TEC-0003', 'EDUARDO MAY', '2025-07-15', '2025-07-15 22:35:14', '2025-07-15 22:35:14'),
(4, 'TEC-0004', 'JORGE SANCHEZ', '2025-07-15', '2025-07-15 22:35:28', '2025-07-15 22:35:28'),
(5, 'TEC-0005', 'JOSUE CANCHE', '2025-07-15', '2025-07-15 22:35:52', '2025-07-15 22:35:52'),
(6, 'TEC-0006', 'ADRIAN CANCHE', '2025-07-15', '2025-07-15 22:36:16', '2025-07-15 22:36:16'),
(7, 'TEC-0007', 'JAASIEL IBAÑEZ', '2025-07-15', '2025-07-15 22:36:51', '2025-07-15 22:36:51'),
(8, 'TEC-0008', 'DIEGO GONZALEZ', '2025-07-15', '2025-07-15 22:37:10', '2025-07-15 22:37:10'),
(9, 'TEC-0009', 'JUAN PABLO', '2025-07-16', '2025-07-16 23:28:35', '2025-07-16 23:28:35'),
(10, 'TEC-0010', 'RAMIRO TELLEZ', '2025-07-17', '2025-07-17 17:15:06', '2025-07-17 17:15:06'),
(11, 'TEC-0011', 'ISAI HAU', '2025-07-18', '2025-07-18 23:19:05', '2025-07-18 23:19:05'),
(12, 'TEC-0012', 'ALEX BATILLO', '2025-07-19', '2025-07-19 14:25:40', '2025-07-19 14:25:40');

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
(1, 'admin', 'admin@example.com', '$2y$10$hbvAV3/j8TccjhI8q8l9a.SmTbg8Y1uHf5LNEGKV3VrRIoGCLWyO6', 'Administrador', 'Sistema', NULL, 1, NULL, 1, NULL, '2025-07-08 18:13:35', '2025-07-08 18:13:35'),
(2, 'Raul', 'Trabajador@gmail.com', '$2y$10$LggiZ6IRDwuskKEU4ztFvOo1dNlwx/GdwXfQUS6xSM7M.B.3BvD8m', NULL, NULL, NULL, 1, NULL, 1, NULL, '2025-07-11 23:12:58', '2025-07-15 14:54:37'),
(3, 'Liz', 'Camaras@gmail.com', '$2y$10$HlAZ.jqLj7cau3CSCcffDea41D8M9UFCCTVqZdGkdLlfnSxI4ySC.', NULL, NULL, NULL, 1, NULL, 1, NULL, '2025-08-05 17:35:01', '2025-08-05 17:35:01'),
(4, 'Jaasiel', 'camaras123@gmail.com', '$2y$10$KwdvPyBW9jO1sJZeNLSLme9Kz6bi0ChryKHGy4W5wiW7LMdHd1hKe', NULL, NULL, NULL, 1, NULL, 1, NULL, '2025-08-05 17:38:41', '2025-08-05 17:38:41'),
(5, 'Hiromi', 'ventas@gmail.com', '$2y$10$k9lRiTaxIt6YEhiqxC4uqOHNUB04yFUeqEJ2YsbrNVh4tzIjTp8v2', NULL, NULL, NULL, 1, NULL, 1, NULL, '2025-08-05 17:42:24', '2025-08-05 17:42:24'),
(6, 'EstadiasUTM', 'pasantes@gmail.com', '$2y$10$OZVA4EnT.Yv2EhYH0Amywepf2yYTuQ2m5VYZ9Mdja2JpFVPx9fi/a', NULL, NULL, NULL, 1, NULL, 1, NULL, '2025-08-05 17:45:25', '2025-08-05 17:45:25');

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
-- Estructura de tabla para la tabla `v_cotizaciones_historial`
--

CREATE TABLE `v_cotizaciones_historial` (
  `historial_id` int(11) DEFAULT NULL,
  `numero_cotizacion` varchar(20) DEFAULT NULL,
  `nombre_accion` varchar(50) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `realizado_por` varchar(50) DEFAULT NULL,
  `fecha_accion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indices de la tabla `cotizaciones_insumos`
--
ALTER TABLE `cotizaciones_insumos`
  ADD PRIMARY KEY (`cotizacion_insumo_id`),
  ADD KEY `cotizacion_id` (`cotizacion_id`),
  ADD KEY `insumo_id` (`insumo_id`);

--
-- Indices de la tabla `cotizaciones_productos`
--
ALTER TABLE `cotizaciones_productos`
  ADD PRIMARY KEY (`cotizacion_producto_id`),
  ADD KEY `cotizacion_id` (`cotizacion_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `cotizaciones_servicios`
--
ALTER TABLE `cotizaciones_servicios`
  ADD PRIMARY KEY (`cotizacion_servicio_id`),
  ADD KEY `cotizacion_id` (`cotizacion_id`),
  ADD KEY `servicio_id` (`servicio_id`);

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
  ADD PRIMARY KEY (`estado_id`);

--
-- Indices de la tabla `est_cotizacion`
--
ALTER TABLE `est_cotizacion`
  ADD PRIMARY KEY (`est_cot_id`);

--
-- Indices de la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD PRIMARY KEY (`insumo_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_supplier_id` (`supplier_id`);

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
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_movements_tecnico` (`tecnico_id`);

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
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`servicio_id`);

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
  ADD PRIMARY KEY (`tecnico_id`);

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
  MODIFY `bobina_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `cliente_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `cotizacion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_acciones`
--
ALTER TABLE `cotizaciones_acciones`
  MODIFY `accion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_historial`
--
ALTER TABLE `cotizaciones_historial`
  MODIFY `historial_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_insumos`
--
ALTER TABLE `cotizaciones_insumos`
  MODIFY `cotizacion_insumo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_productos`
--
ALTER TABLE `cotizaciones_productos`
  MODIFY `cotizacion_producto_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_servicios`
--
ALTER TABLE `cotizaciones_servicios`
  MODIFY `cotizacion_servicio_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

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
  MODIFY `insumo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `insumos_movements`
--
ALTER TABLE `insumos_movements`
  MODIFY `insumo_movement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT de la tabla `movements`
--
ALTER TABLE `movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=615;

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
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `servicio_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `system_config`
--
ALTER TABLE `system_config`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tecnicos`
--
ALTER TABLE `tecnicos`
  MODIFY `tecnico_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_cotizaciones_complete`
--
DROP TABLE IF EXISTS `v_cotizaciones_complete`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u393681165_camarasyalarma`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_cotizaciones_complete`  AS SELECT `c`.`cotizacion_id` AS `cotizacion_id`, `c`.`numero_cotizacion` AS `numero_cotizacion`, `c`.`fecha_cotizacion` AS `fecha_cotizacion`, `c`.`validez_dias` AS `validez_dias`, `c`.`subtotal` AS `subtotal`, `c`.`descuento_porcentaje` AS `descuento_porcentaje`, `c`.`descuento_monto` AS `descuento_monto`, `c`.`total` AS `total`, `c`.`condiciones_pago` AS `condiciones_pago`, `c`.`observaciones` AS `observaciones`, `est`.`nombre_estado` AS `estado`, `cl`.`nombre` AS `cliente_nombre`, `u`.`username` AS `usuario_creador`, count(`cp`.`cotizacion_producto_id`) AS `productos_total`, sum(`cp`.`cantidad` * `cp`.`precio_unitario`) AS `subtotal_calculado` FROM ((((`cotizaciones` `c` left join `est_cotizacion` `est` on(`c`.`estado_id` = `est`.`est_cot_id`)) left join `clientes` `cl` on(`c`.`cliente_id` = `cl`.`cliente_id`)) left join `users` `u` on(`c`.`user_id` = `u`.`user_id`)) left join `cotizaciones_productos` `cp` on(`c`.`cotizacion_id` = `cp`.`cotizacion_id`)) GROUP BY `c`.`cotizacion_id` ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cotizaciones_insumos`
--
ALTER TABLE `cotizaciones_insumos`
  ADD CONSTRAINT `cotizaciones_insumos_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`cotizacion_id`),
  ADD CONSTRAINT `cotizaciones_insumos_ibfk_2` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`insumo_id`);

--
-- Filtros para la tabla `cotizaciones_servicios`
--
ALTER TABLE `cotizaciones_servicios`
  ADD CONSTRAINT `cotizaciones_servicios_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`cotizacion_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cotizaciones_servicios_ibfk_2` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`servicio_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD CONSTRAINT `insumos_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `insumos_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `insumos_ibfk_3` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `movements`
--
ALTER TABLE `movements`
  ADD CONSTRAINT `fk_movements_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `tecnicos` (`tecnico_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
